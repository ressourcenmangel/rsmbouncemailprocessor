<?php

declare(strict_types=1);

namespace RSM\Rsmbouncemailprocessor\Tests\Functional\Task;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Psr\Log\LoggerInterface;
use RSM\Rsmbouncemailprocessor\Task\AnalyzeBounceMail;
use RSM\Rsmbouncemailprocessor\Task\ProcessBounceMail;
use RSM\Rsmbouncemailprocessor\Utility\Mailmessage;
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
 * countuserunknown = 2, out of office, unknown reason, no sender found as well as spam, filter list
 * and connection problems (sending side, not the address) never delete.
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
            'possible spam (limit 0)' => ['XSPAM', 3],
            'filter list (limit 0)' => ['XFILTER', 3],
            'connection refused (limit 0)' => ['552', 3],
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

    /**
     * An address that has been removed and subscribes again must start with fresh counters,
     * otherwise a single out of office reply would remove it a second time.
     */
    #[Test]
    public function countersStartAtZeroWhenAddressBouncesAgainAfterRemoval(): void
    {
        $this->bounce('550', 2);
        $this->process(true);
        self::assertGreaterThan(0, (int)$this->report()['removed']);

        // subscribes again
        $this->recipientList->addEmail(self::EMAIL);

        $this->bounce('XOUT');
        $report = $this->report();
        self::assertSame(0, (int)$report['countuserunknown'], 'old counters must not be taken over');
        self::assertSame(1, (int)$report['countoutofoffice']);
        self::assertSame(0, (int)$report['removed']);

        $this->process(true);

        self::assertContains(self::EMAIL, $this->recipientList->getEmails());
        self::assertCount(1, $this->rows(self::DELETELOG_TABLE), 'no second delete log entry');
    }

    /**
     * 553 also comes with relay or sender rejections, it must not be taken as "user unknown" and
     * lead to removing the recipient. As in 2.2.1 it counts as unknown reason.
     */
    #[Test]
    public function postfix553CountsAsUnknownReasonAndDoesNotRemoveRecipient(): void
    {
        $this->bounceMail('postfix-553', 2);
        $this->process(true);

        $report = $this->report();
        self::assertSame(2, (int)$report['countunknownreason']);
        self::assertSame(0, (int)$report['countuserunknown']);
        self::assertSame(0, (int)$report['removed']);
        self::assertSame([self::EMAIL, 'other@example.test'], $this->recipientList->getEmails());
    }

    /**
     * Removing must not fail silently: the error is logged, the report is not marked as removed
     * so the next run tries again.
     */
    #[Test]
    public function failedRemovalIsLoggedAsWarning(): void
    {
        $this->bounce('550', 2);

        $list = $this->createStub(RecipientList::class);
        $list->method('getUid')->willReturn(7);
        $list->method('getRecipientsCount')->willReturn(2);
        $list->method('removeRecipientByEmail')->willThrowException(new \RuntimeException('connector broke for ' . self::EMAIL, 1700000000));

        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects(self::once())->method('warning')->with(
            self::stringContains('could not be removed'),
            // no personal data in the log: report uid, class names and code instead of address and message
            self::callback(function (array $context) use ($list): bool {
                return $context['recipientreport'] === (int)$this->report()['uid']
                    && $context['type'] === $list::class
                    && $context['exception'] === \RuntimeException::class
                    && $context['code'] === 1700000000
                    && !str_contains(json_encode($context), '@');
            })
        );

        $this->process(false, $list, $logger);

        self::assertSame(0, (int)$this->report()['removed']);
    }

    /**
     * Gmail rejects as spam with the same introduction as for unknown users ("could not be delivered
     * to one or more of its recipients"), only the status 5.7.1 tells the difference.
     */
    #[Test]
    public function gmailSpamRejectionDoesNotRemoveRecipient(): void
    {
        $this->bounceMail('gmail-spam', 3);
        $this->process(true);

        $report = $this->report();
        self::assertSame(3, (int)$report['countunknownreason']);
        self::assertSame(0, (int)$report['countuserunknown']);
        self::assertSame(0, (int)$report['removed']);
        self::assertSame([self::EMAIL, 'other@example.test'], $this->recipientList->getEmails());
    }

    #[Test]
    public function gmailUserUnknownRemovesRecipientAfterTwoBounces(): void
    {
        $this->bounceMail('gmail-user-unknown', 2);
        $this->process(true);

        self::assertSame(2, (int)$this->report()['countuserunknown']);
        self::assertGreaterThan(0, (int)$this->report()['removed']);
        self::assertSame(['other@example.test'], $this->recipientList->getEmails());
    }

    // ------------------------------------------------------------------ circuit breaker

    /**
     * Two addresses would be removed, more than the allowed maximum: nothing is removed or marked,
     * the scheduler gets an exception with a plain text message (shown in the scheduler module).
     */
    #[Test]
    public function nothingIsRemovedWhenMoreRecipientsWouldBeRemovedThanAllowed(): void
    {
        $this->bounce('550', 2);
        $this->bounce('550', 2, 'other@example.test');

        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects(self::once())->method('error')->with(
            self::stringContains('maxRemovalsPer24h'),
            self::callback(static fn(array $context): bool => $context['candidates'] === 2
                && $context['maxRemovalsPer24h'] === 1
                && !str_contains(json_encode($context), '@'))
        );

        $exception = null;
        try {
            $this->process(true, null, $logger, 1);
        } catch (\RuntimeException $exception) {
        }
        self::assertNotNull($exception, 'execute() must throw');
        self::assertStringContainsString('2 recipients', $exception->getMessage());
        self::assertStringContainsString('maxRemovalsPer24h = 1', $exception->getMessage());
        self::assertStringNotContainsString('@', $exception->getMessage());

        self::assertSame([self::EMAIL, 'other@example.test'], $this->recipientList->getEmails());
        foreach ($this->rows(self::RECIPIENT_TABLE) as $report) {
            self::assertSame(0, (int)$report['removed']);
        }
        self::assertCount(0, $this->rows(self::DELETELOG_TABLE));
    }

    /**
     * 30 recipients were removed in the last 24 hours and 30 more reached the limit: together more
     * than the allowed 50, nothing is removed.
     */
    #[Test]
    public function recentRemovalsCountTowardsTheMaximum(): void
    {
        $this->insertReports(30, 'gone', time() - 3600, 2);
        $this->insertReports(30, 'candidate', 0, 2);

        $exception = null;
        try {
            $this->process(false, null, null, 50);
        } catch (\RuntimeException $exception) {
        }

        self::assertNotNull($exception, 'execute() must throw');
        self::assertStringContainsString('60 recipients', $exception->getMessage());
        self::assertStringContainsString('30 removed in the last 24 hours', $exception->getMessage());
        self::assertSame(30, $this->countRows('removed = 0'), 'no candidate was removed');
    }

    #[Test]
    public function removalsOlderThan24HoursDoNotCountTowardsTheMaximum(): void
    {
        $this->insertReports(30, 'gone', time() - 90000, 2);
        $this->insertReports(30, 'candidate', 0, 2);

        $this->process(false, null, null, 50);

        self::assertSame(0, $this->countRows('removed = 0'), 'all candidates are removed');
    }

    public static function removalsWithinTheMaximumDataProvider(): array
    {
        return [
            'candidates equal the maximum' => [2],
            'check disabled with 0' => [0],
        ];
    }

    #[Test]
    #[DataProvider('removalsWithinTheMaximumDataProvider')]
    public function recipientsAreRemovedWhenTheMaximumIsNotExceeded(int $maxRemovalsPer24h): void
    {
        $this->bounce('550', 2);
        $this->bounce('550', 2, 'other@example.test');

        $this->process(true, null, null, $maxRemovalsPer24h);

        self::assertSame([], $this->recipientList->getEmails());
    }

    // ------------------------------------------------------------------ helpers

    /**
     * Analyses a bounce mail from Tests/Fixtures/Bounces like AnalyzeBounceMail::execute() does,
     * only the IMAP access is replaced by the fixture.
     */
    private function bounceMail(string $template, int $times = 1): void
    {
        $eml = str_replace(
            ['{{EMAIL}}', '{{RCPT_B64}}', '{{SENDOUT_UID}}', '{{RCPT_UID}}', '{{MSGID}}'],
            [self::EMAIL, base64_encode(self::EMAIL), '55', '1', 'test'],
            (string)file_get_contents(__DIR__ . '/../../Fixtures/Bounces/' . $template . '.eml')
        );
        [$header, $body] = explode("\n\n", $eml, 2);

        $analyzer = new class ($header . "\n", $body) extends AnalyzeBounceMail {
            public function __construct(private readonly string $header, private readonly string $body)
            {
                parent::__construct();
            }

            public function getRawHeaders($uid)
            {
                return $this->header;
            }

            public function imapGetmsg(int $mid): string
            {
                return $this->body;
            }
        };
        $analyzer->initClass();

        $newsletter = $this->createStub(Newsletter::class);
        $newsletter->method('getUid')->willReturn(1);
        $sendout = $this->createStub(SendOut::class);
        $sendout->method('getNewsletter')->willReturn($newsletter);
        $sendOutRepository = $this->createStub(SendOutRepository::class);
        $sendOutRepository->method('findByUid')->willReturn($sendout);
        $this->setProperty($analyzer, 'sendOutRepository', $sendOutRepository);

        $message = $this->createStub(Mailmessage::class);
        $message->method('getUid')->willReturn(1);
        $message->method('getSubject')->willReturn('Undelivered Mail Returned to Sender');

        for ($run = 0; $run < $times; $run++) {
            $this->setProperty($analyzer, 'reports', []);
            $this->setProperty($analyzer, 'arBounces', []);
            (new \ReflectionMethod(AnalyzeBounceMail::class, 'processBounceMail'))->invoke($analyzer, $message);
            $this->call($analyzer, 'createReport');
            $this->call($analyzer, 'saveReportBouncePerRecipient');
        }
    }

    /**
     * Counts $times bounces for the recipient, one analyzer run each (counters are cumulative).
     */
    private function bounce(string $reasonId, int $times = 1, string $email = self::EMAIL): void
    {
        for ($run = 0; $run < $times; $run++) {
            $this->setProperty($this->analyzer, 'reports', []);
            $this->setProperty($this->analyzer, 'arBounces', [[
                'nluid' => 1,
                'recipient' => $email,
                'reason_id' => $reasonId,
                'reason_text' => ERR_REASON[$reasonId],
                'sender' => SENDER_ORG,
            ]]);
            $this->call($this->analyzer, 'createReport');
            $this->call($this->analyzer, 'saveReportBouncePerRecipient');
        }
    }

    private function process(bool $deleteLog, ?RecipientList $recipientList = null, ?LoggerInterface $logger = null, ?int $maxRemovalsPer24h = null): void
    {
        // ProcessBounceMail fetches the (singleton) newsletter repository with makeInstance() in initClass()
        $newsletter = $this->createStub(Newsletter::class);
        $newsletter->method('getRecipientList')->willReturn($recipientList ?? $this->recipientList);
        $repository = $this->createStub(NewsletterRepository::class);
        $repository->method('findByUid')->willReturn($newsletter);
        GeneralUtility::setSingletonInstance(NewsletterRepository::class, $repository);

        $task = new class ($deleteLog, $maxRemovalsPer24h) extends ProcessBounceMail {
            public function __construct(private readonly bool $deleteLog, private readonly ?int $maxRemovalsPer24h)
            {
                parent::__construct();
            }

            public function initClass(): void
            {
                parent::initClass();
                $this->conf['settings.']['deletelog.'] = ['enabled' => $this->deleteLog ? '1' : '0', 'pid' => '94'];
                if ($this->maxRemovalsPer24h !== null) {
                    $this->conf['settings.']['maxRemovalsPer24h'] = (string)$this->maxRemovalsPer24h;
                }
            }
        };

        if ($logger !== null) {
            $task->setLogger($logger);
        }

        self::assertTrue($task->execute());
    }

    private function insertReports(int $count, string $prefix, int $removed, int $countUserUnknown): void
    {
        $connection = $this->get(ConnectionPool::class)->getConnectionForTable(self::RECIPIENT_TABLE);
        for ($i = 0; $i < $count; $i++) {
            $connection->insert(self::RECIPIENT_TABLE, [
                'pid' => 94,
                'email' => $prefix . $i . '@example.test',
                'countuserunknown' => $countUserUnknown,
                'removed' => $removed,
            ]);
        }
    }

    private function countRows(string $where): int
    {
        return (int)$this->get(ConnectionPool::class)->getConnectionForTable(self::RECIPIENT_TABLE)
            ->executeQuery('SELECT COUNT(*) FROM ' . self::RECIPIENT_TABLE . ' WHERE ' . $where)->fetchOne();
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

    public function addEmail(string $email): void
    {
        $this->emails[] = $email;
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
