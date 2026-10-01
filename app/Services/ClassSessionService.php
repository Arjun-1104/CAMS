<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\App;
use App\Core\Audit;
use App\Core\Auth;
use App\Core\Database;

final class ClassSessionService
{
    public static function existingSession(int $timetableId, string $date): ?array
    {
        return Database::fetch(
            'SELECT * FROM class_sessions WHERE timetable_id = ? AND class_date = ?',
            [$timetableId, $date]
        );
    }

    public static function canTeacherStart(array $teacher, array $slot, string $date): array
    {
        if ($date !== App::today()) {
            return [false, 'Classes can only be started on their scheduled day.'];
        }
        if ($slot['day'] !== App::dayName()) {
            return [false, 'This class is not scheduled for today.'];
        }

        $assignment = Database::fetch(
            'SELECT id FROM teacher_subjects WHERE teacher_id = ? AND subject_id = ?',
            [$teacher['id'], $slot['subject_id']]
        );
        if ((int) $slot['teacher_id'] !== (int) $teacher['id'] && !$assignment) {
            return [false, 'You must be assigned to this subject to conduct its class.'];
        }

        $existing = self::existingSession((int) $slot['id'], $date);
        if ($existing) {
            if ($existing['status'] === 'NOT_CONDUCTED') {
                return [false, 'A reason has already been submitted for this class.'];
            }
            if ($existing['status'] === 'ONGOING') {
                return [false, 'Class already started.', $existing];
            }
            if ($existing['status'] === 'COMPLETED') {
                return [false, 'This class is already completed.'];
            }
        }

        return [true, 'Ready to start.'];
    }

    public static function startClass(array $teacher, int $timetableId): array
    {
        $slot = Database::fetch(
            'SELECT t.*, s.subject_code, s.subject_name
             FROM timetable t JOIN subjects s ON s.id = t.subject_id
             WHERE t.id = ? AND t.status = ? AND s.status = ?',
            [$timetableId, 'ACTIVE', 'ACTIVE']
        );
        if (!$slot) {
            return [false, 'Timetable slot not found.', null];
        }

        $date = App::today();
        $check = self::canTeacherStart($teacher, $slot, $date);
        if (!$check[0]) {
            return [$check[0], $check[1], $check[2] ?? null];
        }

        $now = App::now();
        $scheduled = new \DateTimeImmutable($date . ' ' . $slot['start_time'], $now->getTimezone());
        $delay = max(0, $now->getTimestamp() - $scheduled->getTimestamp());
        Database::query(
            'INSERT INTO class_sessions
                (timetable_id, teacher_id, subject_id, class_date, scheduled_start, scheduled_end,
                 actual_start, start_delay_seconds, is_late_start, status)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)',
            [
                $slot['id'],
                $teacher['id'],
                $slot['subject_id'],
                $date,
                $slot['start_time'],
                $slot['end_time'],
                $now->format('Y-m-d H:i:s'),
                $delay,
                $delay > 0 ? 1 : 0,
                'ONGOING',
            ]
        );
        $sessionId = (int) Database::lastId();
        Audit::log(Auth::id(), 'START_CLASS', sprintf(
            'Session #%d | %s | actual start %s',
            $sessionId,
            $slot['subject_code'],
            $now->format('Y-m-d H:i:s')
        ));

        return [true, 'Class started.', Database::fetch('SELECT * FROM class_sessions WHERE id = ?', [$sessionId])];
    }

    public static function endClass(array $teacher, int $sessionId): array
    {
        $session = Database::fetch('SELECT * FROM class_sessions WHERE id = ?', [$sessionId]);
        if (!$session || (int) $session['teacher_id'] !== (int) $teacher['id']) {
            return [false, 'Session not found.'];
        }
        if ($session['status'] !== 'ONGOING') {
            return [false, 'Session is not ongoing.'];
        }

        $now = App::now();
        $actualStart = new \DateTimeImmutable($session['actual_start'], $now->getTimezone());
        $duration = max(0, $now->getTimestamp() - $actualStart->getTimestamp());
        $scheduledEnd = new \DateTimeImmutable(
            $session['class_date'] . ' ' . $session['scheduled_end'],
            $now->getTimezone()
        );
        $endDelay = $now->getTimestamp() - $scheduledEnd->getTimestamp();

        Database::query(
            'UPDATE class_sessions
             SET actual_end = ?, end_delay_seconds = ?, duration_seconds = ?, status = ?
             WHERE id = ?',
            [$now->format('Y-m-d H:i:s'), $endDelay, $duration, 'COMPLETED', $sessionId]
        );
        AttendanceService::markRemainingAbsent($sessionId, (int) Auth::id());
        Audit::log(Auth::id(), 'END_CLASS', "Session #{$sessionId} ended at " . $now->format('Y-m-d H:i:s'));
        return [true, 'Class ended.', Database::fetch('SELECT * FROM class_sessions WHERE id = ?', [$sessionId])];
    }

    public static function reportNotConducted(array $teacher, int $timetableId, string $reason): array
    {
        $reason = trim($reason);
        if ($reason === '' || strlen($reason) > 255) {
            return [false, 'Enter a reason of up to 255 characters.'];
        }
        $slot = Database::fetch('SELECT * FROM timetable WHERE id = ? AND status = ?', [$timetableId, 'ACTIVE']);
        if (!$slot || $slot['day'] !== App::dayName()) {
            return [false, 'Class not found for today.'];
        }
        $assignment = Database::fetch(
            'SELECT id FROM teacher_subjects WHERE teacher_id = ? AND subject_id = ?',
            [$teacher['id'], $slot['subject_id']]
        );
        if ((int) $slot['teacher_id'] !== (int) $teacher['id'] && !$assignment) {
            return [false, 'You are not assigned to this class or subject.'];
        }
        if (self::existingSession($timetableId, App::today())) {
            return [false, 'This class already has a recorded outcome.'];
        }

        Database::query(
            'INSERT INTO class_sessions
                (timetable_id, teacher_id, subject_id, class_date, scheduled_start, scheduled_end, status, skip_reason)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?)',
            [$slot['id'], $teacher['id'], $slot['subject_id'], App::today(), $slot['start_time'], $slot['end_time'], 'NOT_CONDUCTED', $reason]
        );
        Audit::log(Auth::id(), 'CLASS_NOT_CONDUCTED', "Timetable #{$timetableId}: {$reason}");
        return [true, 'Reason submitted to HOD.'];
    }
}
