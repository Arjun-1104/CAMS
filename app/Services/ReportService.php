<?php
/**
 * ReportService.php
 * Counts conducted / cancelled / late classes for HOD and teacher reports.
 * Timetable rows are NOT counted as "class taken".
 */

declare(strict_types=1);

namespace App\Services;

use App\Core\App;
use App\Core\Database;

final class ReportService
{
    public static function slotOutcome(array $slot, string $date): string
    {
        $session = Database::fetch(
            'SELECT status FROM class_sessions WHERE timetable_id = ? AND class_date = ?',
            [$slot['id'], $date]
        );
        if ($session) {
            return $session['status'];
        }
        if ($date < App::today()) {
            return 'NOT_CONDUCTED';
        }
        return 'SCHEDULED';
    }

    public static function teacherStats(int $teacherId): array
    {
        $conducted = Database::fetch('SELECT COUNT(*) c FROM class_sessions WHERE teacher_id = ? AND status = ?', [$teacherId, 'COMPLETED']);
        $cancelled = Database::fetch('SELECT COUNT(*) c FROM class_sessions WHERE teacher_id = ? AND status = ?', [$teacherId, 'CANCELLED']);
        $late = Database::fetch('SELECT COUNT(*) c FROM class_sessions WHERE teacher_id = ? AND status = ? AND is_late_start = 1', [$teacherId, 'COMPLETED']);
        $short = Database::fetch('SELECT COUNT(*) c FROM class_sessions WHERE teacher_id = ? AND is_short_session = 1', [$teacherId]);
        $ontime = Database::fetch('SELECT COUNT(*) c FROM class_sessions WHERE teacher_id = ? AND status = ? AND is_late_start = 0', [$teacherId, 'COMPLETED']);

        return [
            'conducted' => (int) $conducted['c'],
            'cancelled' => (int) $cancelled['c'],
            'late' => (int) $late['c'],
            'short' => (int) $short['c'],
            'ontime' => (int) $ontime['c'],
        ];
    }

    public static function subjectAttendance(int $subjectId): array
    {
        $conducted = (int) Database::fetch('SELECT COUNT(*) c FROM class_sessions WHERE subject_id = ? AND status = ?', [$subjectId, 'COMPLETED'])['c'];
        $avg = Database::fetch(
            'SELECT AVG(pct) avg_pct FROM (
                SELECT (SUM(a.status = "PRESENT") / COUNT(*)) * 100 AS pct
                FROM attendance a
                JOIN class_sessions cs ON cs.id = a.session_id
                WHERE cs.subject_id = ? AND cs.status = "COMPLETED"
                GROUP BY cs.id
             ) t',
            [$subjectId]
        );
        return [
            'conducted' => $conducted,
            'average' => round((float) ($avg['avg_pct'] ?? 0), 1),
        ];
    }

    public static function datesForDay(string $dayName, string $from, string $to): array
    {
        $dates = [];
        $cur = new \DateTimeImmutable($from);
        $end = new \DateTimeImmutable($to);
        while ($cur <= $end) {
            if ($cur->format('l') === $dayName) {
                $dates[] = $cur->format('Y-m-d');
            }
            $cur = $cur->modify('+1 day');
        }
        return $dates;
    }
}
