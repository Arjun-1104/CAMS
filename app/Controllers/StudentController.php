<?php
/**
 * StudentController.php
 * Student attendance history and personal internal marks.
 */

declare(strict_types=1);

namespace App\Controllers;

use App\Core\App;
use App\Core\Auth;
use App\Core\Database;
use App\Core\View;
use App\Services\AttendanceService;
use App\Services\MarksService;

final class StudentController
{
    private function boot(): array
    {
        Auth::requireRole('STUDENT');
        $user = Auth::user();
        if (!$user['student']) {
            http_response_code(403);
            echo 'Student profile missing.';
            exit;
        }
        return $user;
    }

    public function dashboard(array $params = []): void
    {
        $user = $this->boot();
        $stats = AttendanceService::studentStats((int) $user['student']['id']);
        View::render('student/dashboard', [
            'title' => 'My Attendance',
            'student' => $user['student'],
            'stats' => $stats,
            'marks' => MarksService::studentCard((int) $user['student']['id']),
            'success' => App::flash('success'),
            'error' => App::flash('error'),
        ]);
    }

    public function history(array $params = []): void
    {
        $user = $this->boot();
        $rows = Database::fetchAll(
            'SELECT cs.class_date, cs.scheduled_start, cs.scheduled_end, cs.status AS session_status,
                    s.subject_code, s.subject_name, t.name AS teacher_name,
                    a.status AS att_status
             FROM class_sessions cs
             JOIN subjects s ON s.id = cs.subject_id
             JOIN teachers t ON t.id = cs.teacher_id
             LEFT JOIN attendance a ON a.session_id = cs.id AND a.student_id = ?
             WHERE cs.status IN ("COMPLETED","ONGOING")
             ORDER BY cs.class_date DESC, cs.scheduled_start DESC',
            [$user['student']['id']]
        );
        View::render('student/history', [
            'title' => 'Attendance History',
            'student' => $user['student'],
            'rows' => $rows,
        ]);
    }

}
