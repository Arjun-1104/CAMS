<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\App;
use App\Core\Audit;
use App\Core\Database;

final class AttendanceService
{
    public static function teacherSet(int $sessionId, int $studentId, string $status, int $teacherUserId, array $teacher): array
    {
        $session = Database::fetch('SELECT * FROM class_sessions WHERE id = ?', [$sessionId]);
        if (!$session || (int) $session['teacher_id'] !== (int) $teacher['id']) {
            return [false, 'Session not found.'];
        }
        if (!in_array($session['status'], ['ONGOING', 'COMPLETED'], true)) {
            return [false, 'Attendance can only be recorded for a conducted class.'];
        }
        if (!in_array($status, ['PRESENT', 'ABSENT'], true)) {
            return [false, 'Choose present or absent.'];
        }

        $student = Database::fetch(
            'SELECT id FROM students WHERE id = ? AND status = ?',
            [$studentId, 'ACTIVE']
        );
        if (!$student) {
            return [false, 'Student not found or disabled.'];
        }

        $existing = Database::fetch(
            'SELECT * FROM attendance WHERE session_id = ? AND student_id = ?',
            [$sessionId, $studentId]
        );
        $now = App::now()->format('Y-m-d H:i:s');
        if ($existing) {
            Database::query(
                'UPDATE attendance SET status = ?, marked_at = ?, marked_by = ?, method = ? WHERE id = ?',
                [$status, $now, $teacherUserId, 'TEACHER', $existing['id']]
            );
            Database::query(
                'INSERT INTO attendance_logs (attendance_id, session_id, student_id, old_status, new_status, changed_by)
                 VALUES (?, ?, ?, ?, ?, ?)',
                [$existing['id'], $sessionId, $studentId, $existing['status'], $status, $teacherUserId]
            );
        } else {
            Database::query(
                'INSERT INTO attendance (session_id, student_id, status, marked_at, marked_by, method)
                 VALUES (?, ?, ?, ?, ?, ?)',
                [$sessionId, $studentId, $status, $now, $teacherUserId, 'TEACHER']
            );
            $attendanceId = (int) Database::lastId();
            Database::query(
                'INSERT INTO attendance_logs (attendance_id, session_id, student_id, old_status, new_status, changed_by)
                 VALUES (?, ?, ?, ?, ?, ?)',
                [$attendanceId, $sessionId, $studentId, null, $status, $teacherUserId]
            );
        }

        Audit::log($teacherUserId, 'ATTENDANCE_EDIT', "Student #{$studentId} {$status} in session #{$sessionId}");
        return [true, 'Attendance updated.'];
    }

    public static function markRemainingAbsent(int $sessionId, int $userId): void
    {
        $session = Database::fetch('SELECT subject_id FROM class_sessions WHERE id = ?', [$sessionId]);
        if (!$session) {
            throw new \RuntimeException('Class session not found while closing attendance.');
        }
        $students = Database::fetchAll('SELECT id FROM students WHERE status = ?', ['ACTIVE']);
        foreach ($students as $student) {
            Database::query(
                'INSERT INTO attendance (session_id, student_id, status, marked_at, marked_by, method)
                 VALUES (?, ?, ?, NULL, ?, ?)
                 ON DUPLICATE KEY UPDATE id = id',
                [$sessionId, $student['id'], 'ABSENT', $userId, 'TEACHER']
            );
        }
    }

    public static function studentStats(int $studentId): array
    {
        $subjects = Database::fetchAll('SELECT * FROM subjects WHERE status = ? ORDER BY subject_code', ['ACTIVE']);
        $rows = [];
        $totalConducted = 0;
        $totalPresent = 0;
        foreach ($subjects as $subject) {
            $conducted = Database::fetch(
                'SELECT COUNT(*) AS c FROM class_sessions WHERE subject_id = ? AND status = ?',
                [$subject['id'], 'COMPLETED']
            );
            $present = Database::fetch(
                'SELECT COUNT(*) AS c FROM attendance a
                 JOIN class_sessions cs ON cs.id = a.session_id
                 WHERE a.student_id = ? AND cs.subject_id = ? AND cs.status = ? AND a.status = ?',
                [$studentId, $subject['id'], 'COMPLETED', 'PRESENT']
            );
            $count = (int) $conducted['c'];
            $attended = (int) $present['c'];
            $rows[] = [
                'subject' => $subject,
                'conducted' => $count,
                'present' => $attended,
                'absent' => max(0, $count - $attended),
                'percent' => $count > 0 ? round(($attended / $count) * 100, 1) : 0,
            ];
            $totalConducted += $count;
            $totalPresent += $attended;
        }

        return [
            'subjects' => $rows,
            'conducted' => $totalConducted,
            'present' => $totalPresent,
            'overall' => $totalConducted > 0 ? round(($totalPresent / $totalConducted) * 100, 1) : 0,
        ];
    }
}
