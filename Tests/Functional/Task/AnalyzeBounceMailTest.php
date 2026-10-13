<?php

declare(strict_types=1);

namespace RSM\Rsmbouncemailprocessor\Tests\Functional\Task;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use RSM\Rsmbouncemailprocessor\Task\AnalyzeBounceMail;
use TYPO3\CMS\Core\Core\SystemEnvironmentBuilder;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Http\ServerRequest;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;
use Undkonsorten\CuteMailing\Domain\Model\Newsletter;
use Undkonsorten\CuteMailing\Domain\Model\RecipientList;
use Undkonsorten\CuteMailing\Domain\Model\SendOut;
use Undkonsorten\CuteMailing\Domain\Repository\SendOutRepository;

#[CoversClass(AnalyzeBounceMail::class)]
final class AnalyzeBounceMailTest extends FunctionalTestCase
{
    private const BOUNCE_TABLE = 'tx_rsmbouncemailprocessor_domain_model_bouncereport';
    private const RECIPIENT_TABLE = 'tx_rsmbouncemailprocessor_domain_model_recipientreport';

    protected array $coreExtensionsToLoad = ['scheduler'];

    protected array $testExtensionsToLoad = [
        'undkonsorten/taskqueue',
        'undkonsorten/typo3-cute-mailing',
        'ressourcenmangel/rsmbouncemailprocessor',
    ];

    private AnalyzeBounceMail $task;

    protected function setUp(): void
    {
        parent::setUp();
        $this->importCSVDataSet(__DIR__ . '/../Fixtures/Reports.csv');
        $GLOBALS['TYPO3_REQUEST'] = (new ServerRequest('https://localhost/', 'GET'))
            ->withAttribute('applicationType', SystemEnvironmentBuilder::REQUESTTYPE_BE);
        $this->task = $this->get(AnalyzeBounceMail::class);
        // reads the TypoScript of the extension (reason texts, limits)
        $this->task->initClass();
    }

    // ------------------------------------------------------------------ recipient / newsletter

    public static function recipientDataProvider(): array
    {
        return [
            'X-TYPO3RCPT in the original headers' => ['550-user-unknown', 'user@example.test'],
            'X-TYPO3RCPT only in the body' => ['body-only-headers', 'user@example.test'],
            'qmail' => ['qmail-550', 'user@example.test'],
            'no recipient information' => ['no-recipient-headers', ''],
        ];
    }

    #[Test]
    #[DataProvider('recipientDataProvider')]
    public function finalRecipientIsFoundInBounce(string $template, string $expected): void
    {
        [$header, $body] = $this->bounce($template);

        $recipient = $this->task->getMailFinalRecipient($header);
        if ($recipient === '') {
            $recipient = $this->task->getMailFinalRecipient($this->flatten($body));
        }

        self::assertSame($expected, $recipient);
    }

    #[Test]
    public function failedRecipientsHeaderIsUsedWhenThereIsNoTypo3Header(): void
    {
        $content = "Subject: bounce\nX-Failed-Recipients: gone@example.test\n\nbody";

        self::assertSame('gone@example.test', $this->task->getMailFinalRecipient($content));
    }

    #[Test]
    public function sendoutUidIsFoundInBodyAndMissingIsNull(): void
    {
        [, $body] = $this->bounce('550-user-unknown', ['SENDOUT_UID' => '55']);
        [$header] = $this->bounce('no-recipient-headers');

        self::assertSame(55, $this->task->getMailNewsletterUID($this->flatten($body)));
        self::assertNull($this->task->getMailNewsletterUID($header));
    }

    // ------------------------------------------------------------------ reasons

    public static function reasonDataProvider(): array
    {
        return [
            'user unknown' => ['550-user-unknown', '550'],
            'quota exceeded' => ['551-mailbox-full', '551'],
            'connection refused' => ['552-connection-refused', '552'],
            'header error' => ['554-header-error', '554'],
            'out of office' => ['out-of-office', 'XOUT'],
            'spam' => ['spam', 'XSPAM'],
            'postfix user unknown' => ['postfix-550', '550'],
            'qmail user unknown' => ['qmail-550', '550'],
            'filter list' => ['xfilter-blocked', 'XFILTER'],
            'message size' => ['xsize-too-large', 'XSIZE'],
            'lotus notes user unknown' => ['lotus-notes', '550'],
            'outlook user unknown' => ['outlook', '550'],
            'cannot be delivered, quota' => ['cannot-be-delivered', '551'],
            'unknown reason' => ['unknown-reason', '-1'],
            // Postfix 553 returned a reason that does not exist in ERR_REASON
            'postfix 553 mailbox name not allowed' => ['postfix-553', '550'],
        ];
    }

    #[Test]
    #[DataProvider('reasonDataProvider')]
    public function reasonOfBounceIsDetected(string $template, string $expectedReason): void
    {
        [, $body] = $this->bounce($template);

        $report = $this->task->analyseReturnError($this->flatten($body));

        self::assertSame($expectedReason, (string)$report['reason']);
        self::assertArrayHasKey($report['reason'], ERR_REASON);
    }

    // ------------------------------------------------------------------ list unsubscribe

    public static function listUnsubscribeSubjectDataProvider(): array
    {
        $valid = ['action' => 'listunsubscribe', 'rcptuid' => 5, 'rcptemail' => 'a@example.test', 'sendout' => 7];
        return [
            'valid' => ['action=listunsubscribe&rcptuid=5&rcptemail=a@example.test&sendout=7', $valid],
            'url encoded by the mail client' => [rawurlencode('action=listunsubscribe&rcptuid=5&rcptemail=a@example.test&sendout=7'), $valid],
            'missing sendout' => ['action=listunsubscribe&rcptuid=5&rcptemail=a@example.test', null],
            'other action' => ['action=other&rcptuid=5&rcptemail=a@example.test&sendout=7', null],
            'zero recipient' => ['action=listunsubscribe&rcptuid=0&rcptemail=a@example.test&sendout=7', null],
            'zero sendout' => ['action=listunsubscribe&rcptuid=5&rcptemail=a@example.test&sendout=0', null],
            'normal subject' => ['Undelivered Mail Returned to Sender', null],
        ];
    }

    #[Test]
    #[DataProvider('listUnsubscribeSubjectDataProvider')]
    public function listUnsubscribeSubjectIsParsed(string $subject, ?array $expected): void
    {
        $message = $this->createStub(\RSM\Rsmbouncemailprocessor\Utility\Mailmessage::class);
        $message->method('getSubject')->willReturn($subject);

        self::assertSame($expected, $this->call('getListunsubscribeHeader', $message));
    }

    #[Test]
    public function listUnsubscribeRemovesRecipientAndReportsSuccess(): void
    {
        $recipientList = $this->createMock(RecipientList::class);
        $recipientList->method('getRecipientListPage')->willReturn(95);
        $recipientList->expects(self::once())->method('removeRecipientByEmail')->with('a@example.test');
        $this->task = $this->taskWithSendout($this->sendoutWithRecipientList($recipientList));
        $this->enableDeleteLog();

        $result = $this->call('processlistunsubscribeHeader', ['action' => 'listunsubscribe', 'rcptuid' => 5, 'rcptemail' => 'a@example.test', 'sendout' => 7]);

        self::assertTrue($result, 'the mail has to be deleted after a successful unsubscribe');
        $logs = $this->rows('tx_rsmbouncemailprocessor_domain_model_listunsubscribeheaderlog');
        self::assertCount(1, $logs);
        self::assertSame('a@example.test', $logs[0]['email']);
        self::assertSame(95, (int)$logs[0]['origpid']);
    }

    #[Test]
    public function listUnsubscribeIsNotSuccessfulWhenRecipientCannotBeRemoved(): void
    {
        $recipientList = $this->createStub(RecipientList::class);
        $recipientList->method('removeRecipientByEmail')->willThrowException(new \RuntimeException('Not implemented'));
        $this->task = $this->taskWithSendout($this->sendoutWithRecipientList($recipientList));

        $result = $this->call('processlistunsubscribeHeader', ['action' => 'listunsubscribe', 'rcptuid' => 5, 'rcptemail' => 'a@example.test', 'sendout' => 7]);

        self::assertFalse($result);
    }

    /**
     * Regression test: $sendout was undefined when the sendout could not be found.
     */
    #[Test]
    public function listUnsubscribeWithUnknownSendoutDoesNotFail(): void
    {
        $this->task = $this->taskWithSendout(null);

        $result = $this->call('processlistunsubscribeHeader', ['action' => 'listunsubscribe', 'rcptuid' => 5, 'rcptemail' => 'a@example.test']);

        self::assertFalse($result);
    }

    // ------------------------------------------------------------------ reports

    #[Test]
    public function newsletterReportsUseThePidOfTheirOwnNewsletter(): void
    {
        $this->setBounces([
            $this->bounceItem(1, 'a@example.test', '550'),
            $this->bounceItem(2, 'b@example.test', '551'),
            // unknown newsletter in between must not take over the pid of the previous one
            $this->bounceItem(0, 'c@example.test', '550'),
            $this->bounceItem(99, 'd@example.test', '550'),
        ]);

        $this->call('createReport');
        $this->call('saveReportBouncePerNl');

        $rows = $this->rows(self::BOUNCE_TABLE, 'newsletterid');
        self::assertCount(2, $rows, 'reports only for the newsletters that exist');
        self::assertSame([94, 96], array_map(static fn(array $r): int => (int)$r['pid'], $rows));
        self::assertSame(1, (int)$rows[0]['countuserunknown']);
        self::assertSame(1, (int)$rows[1]['countquotaexceeded']);
    }

    #[Test]
    public function fetchedMailsAreCountedOncePerRun(): void
    {
        $this->setProperty('messages', [new \stdClass(), new \stdClass(), new \stdClass()]);
        $this->setBounces([
            $this->bounceItem(1, 'a@example.test', '550'),
            $this->bounceItem(2, 'b@example.test', '550'),
        ]);

        $this->call('createReport');
        $this->call('saveReportBouncePerNl');

        $total = array_sum(array_column($this->rows(self::BOUNCE_TABLE), 'countmails'));
        self::assertSame(3, $total);
    }

    /**
     * Regression test: the recipient report counted "unknown reason" as "no sender found" and vice versa.
     */
    #[Test]
    public function recipientReportCountsUnknownReasonAndMissingSenderCorrectly(): void
    {
        $this->setBounces([
            $this->bounceItem(1, 'a@example.test', '-1'),
            $this->bounceItem(1, 'b@example.test', '-2'),
            $this->bounceItem(1, 'c@example.test', '550'),
        ]);

        $this->call('createReport');
        $this->call('saveReportBouncePerRecipient');

        $rows = array_column($this->rows(self::RECIPIENT_TABLE), null, 'email');
        self::assertSame(1, (int)$rows['a@example.test']['countunknownreason']);
        self::assertSame(0, (int)$rows['a@example.test']['countnosenderfound']);
        self::assertSame(0, (int)$rows['b@example.test']['countunknownreason']);
        self::assertSame(1, (int)$rows['b@example.test']['countnosenderfound']);
        self::assertSame(1, (int)$rows['c@example.test']['countuserunknown']);
    }

    #[Test]
    public function recipientReportCountsAreCumulativeAcrossRuns(): void
    {
        for ($run = 0; $run < 2; $run++) {
            $this->setProperty('reports', []);
            $this->setBounces([$this->bounceItem(1, 'a@example.test', '550')]);
            $this->call('createReport');
            $this->call('saveReportBouncePerRecipient');
        }

        $rows = $this->rows(self::RECIPIENT_TABLE);
        self::assertCount(1, $rows);
        self::assertSame(2, (int)$rows[0]['countuserunknown']);
    }

    public static function fallbackPidDataProvider(): array
    {
        return [
            'recipientreport.fallbackpid' => [['recipientreport.' => ['fallbackpid' => '96'], 'deletelog.' => ['pid' => '94']], 96],
            'delete log pid' => [['deletelog.' => ['pid' => '94']], 94],
            'nothing configured, report is not saved' => [[], null],
        ];
    }

    /**
     * The recipient report used the hard-coded page 2 when the newsletter was unknown.
     */
    #[Test]
    #[DataProvider('fallbackPidDataProvider')]
    public function recipientReportOfUnknownNewsletterUsesConfiguredFallbackPid(array $settings, ?int $expectedPid): void
    {
        $this->setProperty('conf', ['settings.' => $settings]);
        $this->setBounces([$this->bounceItem(0, 'a@example.test', '550')]);

        $this->call('createReport');
        $this->call('saveReportBouncePerRecipient');

        $rows = $this->rows(self::RECIPIENT_TABLE);
        if ($expectedPid === null) {
            self::assertCount(0, $rows);
        } else {
            self::assertCount(1, $rows);
            self::assertSame($expectedPid, (int)$rows[0]['pid']);
        }
    }

    // ------------------------------------------------------------------ execute

    #[Test]
    public function executeFailsWithoutExceptionWhenMailboxIsNotReachable(): void
    {
        $this->task->setServer('127.0.0.1');
        $this->task->setPort(1);
        $this->task->setUser('nobody');
        $this->task->setPassword('nothing');
        $this->task->setService('imap');
        $this->task->setMaxProcessed(10);

        self::assertFalse($this->task->execute());
    }

    // ------------------------------------------------------------------ helpers

    /**
     * @return array{0: string, 1: string} header and body of a bounce template from Tests/Fixtures/Bounces
     */
    private function bounce(string $template, array $replace = []): array
    {
        $replace += ['EMAIL' => 'user@example.test', 'SENDOUT_UID' => '55', 'RCPT_UID' => '1', 'MSGID' => 'test'];
        $replace['RCPT_B64'] = base64_encode($replace['EMAIL']);
        $eml = file_get_contents(__DIR__ . '/../../Fixtures/Bounces/' . $template . '.eml');
        foreach ($replace as $key => $value) {
            $eml = str_replace('{{' . $key . '}}', (string)$value, $eml);
        }
        [$header, $body] = explode("\n\n", $eml, 2);
        return [$header . "\n", $body];
    }

    /**
     * Same flattening as processBounceMail()
     */
    private function flatten(string $body): string
    {
        return trim((string)preg_replace('/\s+/', ' ', str_replace(["\r", "\n"], ' ', $body)));
    }

    private function bounceItem(int $nluid, string $recipient, string $reason): array
    {
        return [
            'nluid' => $nluid,
            'recipient' => $recipient,
            'reason_id' => $reason,
            'reason_text' => ERR_REASON[$reason],
            'sender' => SENDER_ORG,
        ];
    }

    private function setBounces(array $bounces): void
    {
        $this->setProperty('arBounces', $bounces);
    }

    private function setProperty(string $name, mixed $value): void
    {
        $property = new \ReflectionProperty(AnalyzeBounceMail::class, $name);
        $property->setValue($this->task, $value);
    }

    private function call(string $method, mixed ...$arguments): mixed
    {
        return (new \ReflectionMethod(AnalyzeBounceMail::class, $method))->invoke($this->task, ...$arguments);
    }

    private function enableDeleteLog(): void
    {
        $this->setProperty('conf', ['settings.' => ['deletelog.' => ['enabled' => '1', 'pid' => '94']]]);
    }

    private function sendoutWithRecipientList(RecipientList $recipientList): SendOut
    {
        $newsletter = $this->createStub(Newsletter::class);
        $newsletter->method('getRecipientList')->willReturn($recipientList);
        $sendout = $this->createStub(SendOut::class);
        $sendout->method('getNewsletter')->willReturn($newsletter);
        return $sendout;
    }

    private function taskWithSendout(?SendOut $sendout): AnalyzeBounceMail
    {
        $repository = $this->createStub(SendOutRepository::class);
        $repository->method('findByUid')->willReturn($sendout);
        $this->setProperty('sendOutRepository', $repository);
        return $this->task;
    }

    private function rows(string $table, string $orderBy = 'uid'): array
    {
        return $this->get(ConnectionPool::class)->getConnectionForTable($table)
            ->createQueryBuilder()->select('*')->from($table)->orderBy($orderBy)
            ->executeQuery()->fetchAllAssociative();
    }
}
