<?php

declare(strict_types=1);

namespace RSM\Rsmbouncemailprocessor\Tests\Unit\Service;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use RSM\Rsmbouncemailprocessor\Domain\Model\Bouncereport;
use RSM\Rsmbouncemailprocessor\Domain\Model\Recipientreport;
use RSM\Rsmbouncemailprocessor\Service\ReportPresenter;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;
use Undkonsorten\CuteMailing\Domain\Model\Newsletter;
use Undkonsorten\CuteMailing\Domain\Model\SendOut;

#[CoversClass(ReportPresenter::class)]
final class ReportPresenterTest extends UnitTestCase
{
    private ReportPresenter $subject;

    protected function setUp(): void
    {
        parent::setUp();
        $this->subject = new ReportPresenter();
    }

    public static function percentDataProvider(): array
    {
        return [
            'half' => [1, 2, 50.0],
            'all' => [5, 5, 100.0],
            'none' => [0, 5, 0.0],
            'more than total is capped' => [17, 3, 100.0],
            'total zero' => [3, 0, 0.0],
            'both zero' => [0, 0, 0.0],
            'negative total' => [3, -1, 0.0],
        ];
    }

    #[Test]
    #[DataProvider('percentDataProvider')]
    public function percentIsBetweenZeroAndHundredAndNeverDividesByZero(int $part, int $total, float $expected): void
    {
        self::assertSame($expected, $this->subject->percent($part, $total));
    }

    public static function severityDataProvider(): array
    {
        return [
            'no bounces' => [0, 100, ReportPresenter::SEVERITY_SUCCESS],
            'below warning' => [1, 100, ReportPresenter::SEVERITY_SUCCESS],
            'warning limit' => [2, 100, ReportPresenter::SEVERITY_WARNING],
            'below danger' => [4, 100, ReportPresenter::SEVERITY_WARNING],
            'danger limit' => [5, 100, ReportPresenter::SEVERITY_DANGER],
            'more bounces than mails' => [17, 3, ReportPresenter::SEVERITY_DANGER],
            'bounces without any sent mail' => [3, 0, ReportPresenter::SEVERITY_DANGER],
            'nothing at all' => [0, 0, ReportPresenter::SEVERITY_SUCCESS],
        ];
    }

    #[Test]
    #[DataProvider('severityDataProvider')]
    public function bounceRateSeverityFollowsThresholds(int $bounces, int $sent, string $expected): void
    {
        self::assertSame($expected, $this->subject->rateSeverity($bounces, $sent));
    }

    #[Test]
    public function bounceReportRowContainsRateAndOnlyReasonsThatOccurred(): void
    {
        $report = $this->bouncereport(newsletter: $this->newsletter(100, 100), processed: 4, userUnknown: 3, quota: 1);

        $row = $this->subject->presentBouncereports([$report])['rows'][0];

        self::assertSame(100, $row['sent']);
        self::assertSame(4, $row['bounces']);
        self::assertSame(96, $row['delivered']);
        self::assertSame(4.0, $row['bouncePercent']);
        self::assertSame(96.0, $row['deliveredPercent']);
        self::assertSame(ReportPresenter::SEVERITY_WARNING, $row['severity']);
        self::assertFalse($row['moreBouncesThanSent']);
        self::assertSame(['userunknown', 'quotaexceeded'], array_column($row['reasons'], 'name'), 'most frequent first, zero counts left out');
        self::assertSame(75.0, $row['reasons'][0]['percent']);
        self::assertSame('countuserunknown', $row['reasons'][0]['countKey']);
    }

    /**
     * 17 bounces for 3 sent mails were shown as "17 / 3 (566,7%)".
     */
    #[Test]
    public function moreBouncesThanSentMailsAreCappedAndFlagged(): void
    {
        $report = $this->bouncereport(newsletter: $this->newsletter(3, 3), processed: 17, userUnknown: 17);

        $row = $this->subject->presentBouncereports([$report])['rows'][0];

        self::assertSame(100.0, $row['bouncePercent']);
        self::assertSame(0.0, $row['deliveredPercent']);
        self::assertSame(0, $row['delivered']);
        self::assertTrue($row['moreBouncesThanSent']);
        self::assertSame(ReportPresenter::SEVERITY_DANGER, $row['severity']);
    }

    #[Test]
    public function reportWithoutNewsletterOrSentMailsDoesNotDivideByZero(): void
    {
        $withoutNewsletter = $this->bouncereport(newsletter: null, processed: 2, nosender: 2);
        $withoutBounces = $this->bouncereport(newsletter: $this->newsletter(0, 0), processed: 0);

        $rows = $this->subject->presentBouncereports([$withoutNewsletter, $withoutBounces])['rows'];

        self::assertNull($rows[0]['newsletter']);
        self::assertSame(0.0, $rows[0]['bouncePercent']);
        self::assertSame(0.0, $rows[0]['deliveredPercent']);
        self::assertSame(['nosenderfound'], array_column($rows[0]['reasons'], 'name'));
        self::assertSame([], $rows[1]['reasons']);
        self::assertFalse($rows[1]['moreBouncesThanSent']);
    }

    #[Test]
    public function summaryAddsUpAllReports(): void
    {
        $reports = [
            $this->bouncereport(newsletter: $this->newsletter(100, 100), processed: 3, userUnknown: 3),
            $this->bouncereport(newsletter: $this->newsletter(100, 100), processed: 7, userUnknown: 7),
        ];

        $summary = $this->subject->presentBouncereports($reports)['summary'];

        self::assertSame(2, $summary['reports']);
        self::assertSame(200, $summary['sent']);
        self::assertSame(10, $summary['bounces']);
        self::assertSame(5.0, $summary['bouncePercent']);
        self::assertSame(ReportPresenter::SEVERITY_DANGER, $summary['severity']);
    }

    #[Test]
    public function emptyListGivesEmptySummary(): void
    {
        $result = $this->subject->presentBouncereports([]);

        self::assertSame([], $result['rows']);
        self::assertSame(0, $result['summary']['reports']);
        self::assertSame(0.0, $result['summary']['bouncePercent']);
    }

    #[Test]
    public function recipientRowsContainTotalAndReasons(): void
    {
        $report = new Recipientreport();
        $report->setCountuserunknown(2);
        $report->setCountquotaexceeded(1);
        $report->setCountpossiblespam(1);

        $result = $this->subject->presentRecipientreports([$report]);

        self::assertSame(1, $result['summary']['recipients']);
        self::assertSame(4, $result['rows'][0]['total']);
        self::assertSame(['userunknown', 'quotaexceeded', 'possiblespam'], array_column($result['rows'][0]['reasons'], 'name'));
        self::assertSame(50.0, $result['rows'][0]['reasons'][0]['percent']);
    }

    /**
     * getCountsum() counted the possible spam bounces twice.
     */
    #[Test]
    public function recipientReportSumCountsEveryReasonOnce(): void
    {
        $report = new Recipientreport();
        $report->setCountpossiblespam(3);
        $report->setCountuserunknown(1);

        self::assertSame(4, $report->getCountsum());
    }

    private function newsletter(int $planned, int $sent): Newsletter
    {
        $newsletter = new Newsletter();
        $newsletter->addSendOut((new SendOut())->setTotal($planned)->setCompleted($sent));
        return $newsletter;
    }

    private function bouncereport(?Newsletter $newsletter, int $processed, int $userUnknown = 0, int $quota = 0, int $nosender = 0): Bouncereport
    {
        $report = new Bouncereport();
        if ($newsletter !== null) {
            $report->setNewsletterid($newsletter);
        }
        $report->setCountprocessed($processed);
        $report->setCountuserunknown($userUnknown);
        $report->setCountquotaexceeded($quota);
        $report->setCountnosenderfound($nosender);
        foreach (['unknownreason', 'connectionrefused', 'headererror', 'outofoffice', 'filterlist', 'messagesize', 'possiblespam'] as $reason) {
            $report->{'setCount' . $reason}(0);
        }
        return $report;
    }
}
