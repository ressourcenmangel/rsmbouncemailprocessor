<?php

declare(strict_types=1);

namespace RSM\Rsmbouncemailprocessor\Service;

use RSM\Rsmbouncemailprocessor\Domain\Model\Bouncereport;
use RSM\Rsmbouncemailprocessor\Domain\Model\Recipientreport;

/**
 * Prepares the data shown in the backend module, so the templates only have to display it.
 */
class ReportPresenter
{
    public const SEVERITY_SUCCESS = 'success';
    public const SEVERITY_WARNING = 'warning';
    public const SEVERITY_DANGER = 'danger';
    public const SEVERITY_NEUTRAL = 'secondary';

    /** Bounce rate in percent from which the rate is shown as warning / danger */
    public const RATE_WARNING = 2.0;
    public const RATE_DANGER = 5.0;

    /**
     * Bounce reasons in the order they are shown: name of the count property => severity.
     * The language key of a reason is module.bouncereports.list.tableheader.count<name>.
     */
    private const REASONS = [
        'userunknown' => self::SEVERITY_DANGER,
        'connectionrefused' => self::SEVERITY_DANGER,
        'headererror' => self::SEVERITY_DANGER,
        'quotaexceeded' => self::SEVERITY_WARNING,
        'possiblespam' => self::SEVERITY_WARNING,
        'filterlist' => self::SEVERITY_WARNING,
        'messagesize' => self::SEVERITY_WARNING,
        'outofoffice' => self::SEVERITY_NEUTRAL,
        'unknownreason' => self::SEVERITY_NEUTRAL,
        'nosenderfound' => self::SEVERITY_NEUTRAL,
    ];

    /**
     * @param iterable<Bouncereport> $bouncereports
     * @return array{rows: list<array<string, mixed>>, summary: array<string, mixed>}
     */
    public function presentBouncereports(iterable $bouncereports): array
    {
        $rows = [];
        $sentTotal = 0;
        $bouncesTotal = 0;
        foreach ($bouncereports as $bouncereport) {
            $newsletter = $bouncereport->getNewsletterid();
            $planned = 0;
            $sent = 0;
            if ($newsletter !== null) {
                foreach ($newsletter->getSendOuts() as $sendOut) {
                    $planned += $sendOut->getTotal();
                    $sent += $sendOut->getCompleted();
                }
            }
            $bounces = $bouncereport->getCountprocessed();
            $counts = $this->countsOf($bouncereport);

            $rows[] = [
                'report' => $bouncereport,
                'newsletter' => $newsletter,
                'planned' => $planned,
                'sent' => $sent,
                'sentPercent' => $this->percent($sent, $planned),
                'bounces' => $bounces,
                'delivered' => max($sent - $bounces, 0),
                'bouncePercent' => $this->percent($bounces, $sent),
                'deliveredPercent' => $sent > 0 ? 100.0 - $this->percent($bounces, $sent) : 0.0,
                'severity' => $this->rateSeverity($bounces, $sent),
                'moreBouncesThanSent' => $bounces > $sent,
                'reasons' => $this->reasons($counts, $bounces),
            ];
            $sentTotal += $sent;
            $bouncesTotal += $bounces;
        }

        return [
            'rows' => $rows,
            'summary' => [
                'reports' => count($rows),
                'sent' => $sentTotal,
                'bounces' => $bouncesTotal,
                'bouncePercent' => $this->percent($bouncesTotal, $sentTotal),
                'severity' => $this->rateSeverity($bouncesTotal, $sentTotal),
            ],
        ];
    }

    /**
     * @param iterable<Recipientreport> $recipientreports
     * @return array{rows: list<array<string, mixed>>, summary: array<string, mixed>}
     */
    public function presentRecipientreports(iterable $recipientreports): array
    {
        $rows = [];
        foreach ($recipientreports as $recipientreport) {
            $counts = $this->countsOf($recipientreport);
            $total = array_sum($counts);
            $rows[] = [
                'report' => $recipientreport,
                'total' => $total,
                'reasons' => $this->reasons($counts, $total),
            ];
        }

        return [
            'rows' => $rows,
            'summary' => ['recipients' => count($rows)],
        ];
    }

    /**
     * Percentage of $part in $total, between 0 and 100 (0 if there is no total).
     */
    public function percent(int|float $part, int|float $total): float
    {
        if ($total <= 0 || $part <= 0) {
            return 0.0;
        }
        return min($part / $total * 100.0, 100.0);
    }

    public function rateSeverity(int $bounces, int $sent): string
    {
        if ($sent <= 0 || $bounces <= 0) {
            return $bounces > 0 ? self::SEVERITY_DANGER : self::SEVERITY_SUCCESS;
        }
        $rate = $bounces / $sent * 100.0;
        return match (true) {
            $rate >= self::RATE_DANGER => self::SEVERITY_DANGER,
            $rate >= self::RATE_WARNING => self::SEVERITY_WARNING,
            default => self::SEVERITY_SUCCESS,
        };
    }

    /**
     * @return array<string, int> reason name => count
     */
    private function countsOf(Bouncereport|Recipientreport $report): array
    {
        $counts = [];
        foreach (array_keys(self::REASONS) as $reason) {
            $counts[$reason] = (int)$report->{'getCount' . $reason}();
        }
        return $counts;
    }

    /**
     * Only the reasons that occurred, most frequent first.
     *
     * @param array<string, int> $counts
     * @return list<array{name: string, countKey: string, count: int, percent: float, severity: string}>
     */
    private function reasons(array $counts, int $total): array
    {
        $reasons = [];
        foreach (self::REASONS as $reason => $severity) {
            if ($counts[$reason] > 0) {
                $reasons[] = [
                    'name' => $reason,
                    'countKey' => 'count' . $reason,
                    'count' => $counts[$reason],
                    'percent' => $this->percent($counts[$reason], $total),
                    'severity' => $severity,
                ];
            }
        }
        // stable sort: reasons with the same count keep the order of self::REASONS
        usort($reasons, static fn(array $a, array $b): int => $b['count'] <=> $a['count']);
        return $reasons;
    }
}
