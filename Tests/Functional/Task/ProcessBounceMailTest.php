<?php

declare(strict_types=1);

namespace RSM\Rsmbouncemailprocessor\Tests\Functional\Task;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use RSM\Rsmbouncemailprocessor\Task\AnalyzeBounceMail;
use RSM\Rsmbouncemailprocessor\Task\ProcessBounceMail;
use TYPO3\CMS\Core\Core\SystemEnvironmentBuilder;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Http\ServerRequest;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;
use Undkonsorten\CuteMailing\Domain\Model\Newsletter;
use Undkonsorten\CuteMailing\Domain\Model\RecipientList;
use Undkonsorten\CuteMailing\Domain\Model\SendOut;
use Undkonsorten\CuteMailing\Domain\Model\SimpleRecipient;
use Undkonsorten\CuteMailing\Domain\Repository\NewsletterRepository;
use Undkonsorten\CuteMailing\Domain\Repository\SendOutRepository;

/**
 * Counting of bounces per recipient (AnalyzeBounceMail) and removing recipients that reached
 * a delete limit (ProcessBounceMail). Delete limits come from the TypoScript of the extension:
 * countuserunknown = 2, out of office, unknown reason and no sender found never delete.
 *
 * LineSeparatedRecipientList of cute_mailing cannot remove recipients ("Not implemented") and the
 * real connectors (tt_address, registeraddress) are not required for development. Therefore an
 * in-memory recipient list is used (see ArrayRecipientList below).
 */
#[CoversClass(ProcessBounceMail::class)]
#[CoversClass(AnalyzeBounceMail::class)]
final class ProcessBounceMailTest extends FunctionalTestCase
{
    private const RECIPIENT_TABLE = 'tx_rsmbouncemailprocessor_domain_model_recipientreport';
    private const DELETELOG_TABLE = 'tx_rsmbouncemailprocessor_domain_model_deletelog';
    private const EMAIL = 'user@example.test';

    protected array $coreExtensionsToLoad = ['scheduler'];

    protected array $testExtensionsToLoad = [
        'undkonsorten/taskqueue',
        'undkonsorten/typo3-cute-mailing',
        'ressourcenmangel/rsmbouncemailprocessor',
    ];

    private ArrayRecipientList $recipientList;
    private AnalyzeBounceMail $analyzer;

    protected function setUp(): void
    {
        parent::setUp();
        $this->importCSVDataSet(__DIR__ . '/../Fixtures/Reports.csv');
        $GLOBALS['TYPO3_REQUEST'] = (new ServerRequest('https://localhost/', 'GET'))
            ->withAttribute('applicationType', SystemEnvironmentBuilder::REQUESTTYPE_BE);

        $this->recipientList = new ArrayRecipientList(1, 95, [self::EMAIL, 'other@example.test']);
        $this->analyzer = $this->get(AnalyzeBounceMail::class);
        $this->analyzer->initClass();
    }

    // ------------------------------------------------------------------ existing behaviour

    #[Test]
    public function recipientIsRemovedWhenUserUnknownLimitIsReached(): void
    {
        $this->bounce('550', 2);
        $this->process(true);

        $report = $this->report();
        self::assertSame(2, (int)$report['countuserunknown']);
        self::assertGreaterThan(0, (int)$report['removed']);
        self::assertSame(['other@example.test'], $this->recipientList->getEmails());

        $logs = $this->rows(self::DELETELOG_TABLE);
        self::assertCount(1, $logs);
        self::assertSame(self::EMAIL, $logs[0]['email']);
        self::assertSame(95, (int)$logs[0]['origpid']);
        self::assertSame('countuserunknown', $logs[0]['reasontext']);
        self::assertSame(2, (int)$logs[0]['reasonvalue']);
    }

    #[Test]
    public function noDeleteLogIsWrittenWhenItIsDisabled(): void
    {
        $this->bounce('550', 2);
        $this->process(false);

        self::assertGreaterThan(0, (int)$this->report()['removed']);
        self::assertCount(0, $this->rows(self::DELETELOG_TABLE));
    }

    #[Test]
    public function recipientIsNotRemovedBelowTheLimit(): void
    {
        $this->bounce('550', 1);
        $this->process(true);

        self::assertSame(0, (int)$this->report()['removed']);
        self::assertSame([self::EMAIL, 'other@example.test'], $this->recipientList->getEmails());
        self::assertCount(0, $this->rows(self::DELETELOG_TABLE));
    }

    public static function neverDeletingBouncesDataProvider(): array
    {
        return [
            'out of office (limit 0)' => ['XOUT', 3],
            'mailbox full (limit 5)' => ['551', 3],
            'possible spam (limit 2)' => ['XSPAM', 1],
            'unknown reason (limit 0)' => ['-1', 3],
        ];
    }

    #[Test]
    #[DataProvider('neverDeletingBouncesDataProvider')]
    public function recipientIsNotRemovedForBouncesBelowTheirLimit(string $reason, int $times): void
    {
        $this->bounce($reason, $times);
        $this->process(true);

        self::assertSame(0, (int)$this->report()['removed']);
        self::assertSame([self::EMAIL, 'other@example.test'], $this->recipientList->getEmails());
        self::assertCount(0, $this->rows(self::DELETELOG_TABLE));
    }

    // ------------------------------------------------------------------ helpers

    /**
     * Counts $times bounces for the recipient, one analyzer run each (counters are cumulative).
     */
    private function bounce(string $reasonId, int $times = 1): void
    {
        for ($run = 0; $run < $times; $run++) {
            $this->setProperty($this->analyzer, 'reports', []);
            $this->setProperty($this->analyzer, 'arBounces', [[
                'nluid' => 1,
                'recipient' => self::EMAIL,
                'reason_id' => $reasonId,
                'reason_text' => ERR_REASON[$reasonId],
                'sender' => SENDER_ORG,
            ]]);
            $this->call($this->analyzer, 'createReport');
            $this->call($this->analyzer, 'saveReportBouncePerRecipient');
        }
    }

    private function process(bool $deleteLog): void
    {
        // ProcessBounceMail fetches the (singleton) newsletter repository with makeInstance() in initClass()
        $newsletter = $this->createStub(Newsletter::class);
        $newsletter->method('getRecipientList')->willReturn($this->recipientList);
        $repository = $this->createStub(NewsletterRepository::class);
        $repository->method('findByUid')->willReturn($newsletter);
        GeneralUtility::setSingletonInstance(NewsletterRepository::class, $repository);

        $task = new class ($deleteLog) extends ProcessBounceMail {
            public function __construct(private readonly bool $deleteLog)
            {
                parent::__construct();
            }

            public function initClass(): void
            {
                parent::initClass();
                $this->conf['settings.']['deletelog.'] = ['enabled' => $this->deleteLog ? '1' : '0', 'pid' => '94'];
            }
        };

        self::assertTrue($task->execute());
    }

    private function report(): array
    {
        $rows = $this->rows(self::RECIPIENT_TABLE);
        self::assertCount(1, $rows);
        return $rows[0];
    }

    private function rows(string $table): array
    {
        return $this->get(ConnectionPool::class)->getConnectionForTable($table)
            ->createQueryBuilder()->select('*')->from($table)->orderBy('uid')
            ->executeQuery()->fetchAllAssociative();
    }

    private function setProperty(object $object, string $name, mixed $value): void
    {
        (new \ReflectionProperty(AnalyzeBounceMail::class, $name))->setValue($object, $value);
    }

    private function call(object $object, string $method): void
    {
        (new \ReflectionMethod(AnalyzeBounceMail::class, $method))->invoke($object);
    }
}

/**
 * In-memory recipient list that really removes addresses.
 */
final class ArrayRecipientList extends RecipientList
{
    /**
     * @param list<string> $emails
     */
    public function __construct(int $uid, int $page, private array $emails)
    {
        $this->uid = $uid;
        $this->recipientListPage = $page;
    }

    /** @return list<string> */
    public function getEmails(): array
    {
        return array_values($this->emails);
    }

    public function getRecipients(?int $limit = null, ?int $offset = null): array
    {
        return array_map(
            static function (string $email, int $position): SimpleRecipient {
                $recipient = new SimpleRecipient();
                $recipient->setUid($position)->setEmail($email);
                return $recipient;
            },
            $this->emails,
            array_keys($this->emails)
        );
    }

    public function getRecipientsCount(): int
    {
        return count($this->emails);
    }

    public function getRecipient(int $recipient): ?SimpleRecipient
    {
        return $this->getRecipients()[$recipient] ?? null;
    }

    public function removeRecipientByEmail(string $email): void
    {
        $this->emails = array_values(array_filter($this->emails, static fn(string $e): bool => $e !== $email));
    }

    public function removeRecipientById(int $recipient): void
    {
        unset($this->emails[$recipient]);
        $this->emails = array_values($this->emails);
    }

    public function disableRecipientByEmail(string $email): void
    {
        throw new \Exception('Not implemented.', 1700484664);
    }

    public function disableRecipientById(int $recipient): void
    {
        throw new \Exception('Not implemented.', 1700484664);
    }
}
