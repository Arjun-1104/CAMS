<?php
/**
 * helpers.php
 * Small functions used in HTML pages.
 * status_badge() colours COMPLETED / CANCELLED / PRESENT etc.
 */

use App\Core\View;

function status_badge(string $s): string
{
    $map = [
        'COMPLETED' => ['b-on', 'Completed'],
        'ONGOING' => ['b-gold', 'Ongoing'],
        'CANCELLED' => ['b-off', 'Cancelled'],
        'NOT_CONDUCTED' => ['b-off', 'Not conducted'],
        'SKIPPED' => ['b-off', 'Not conducted'],
        'SCHEDULED' => ['b-wait', 'Scheduled'],
        'PRESENT' => ['b-on', 'Present'],
        'ABSENT' => ['b-off', 'Absent'],
        'PENDING' => ['b-wait', 'Pending'],
    ];
    $m = $map[$s] ?? ['b-wait', $s];
    return '<span class="badge ' . $m[0] . '">' . View::e($m[1]) . '</span>';
}

function fmt_time(?string $t): string
{
    if (!$t) {
        return '—';
    }
    return substr($t, 0, 5);
}

function delay_label(int $seconds): string
{
    $sign = $seconds < 0 ? '-' : '';
    $seconds = abs($seconds);
    return $sign . sprintf('%d min %02d sec', intdiv($seconds, 60), $seconds % 60);
}
