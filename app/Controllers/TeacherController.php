<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\App;
use App\Core\Audit;
use App\Core\Auth;
use App\Core\Database;
use App\Core\View;
use App\Services\AttendanceService;
use App\Services\ClassSessionService;
use App\Services\MarksService;
use App\Services\ReportService;

final class TeacherController
{
    private function boot(): array
    {
        Auth::requireRole('TEACHER');
        $user = Auth::user();
        if (!$user['teacher']) {
            http_response_code(403);
            echo 'Teacher profile missing.';
            exit;
        }
        return $user;
    }

    public function dashboard(array $params = []): void
    {
        $user = $this->boot();
        $teacher = $user['teacher'];
        $today = App::today();
        $day = App::dayName();
        $slots = Database::fetchAll(
            'SELECT DISTINCT t.*, s.subject_code, s.subject_name
             FROM timetable t
             JOIN subjects s ON s.id = t.subject_id
             LEFT JOIN teacher_subjects ts ON ts.subject_id = t.subject_id AND ts.teacher_id = ?
             WHERE t.day = ? AND t.status = ? AND s.status = ?
               AND (t.teacher_id = ? OR ts.id IS NOT NULL)
             ORDER BY t.start_time',
            [$teacher['id'], $day, 'ACTIVE', 'ACTIVE', $teacher['id']]
        );

        $mine = [];
        foreach ($slots as $slot) {
            $session = ClassSessionService::existingSession((int) $slot['id'], $today);
            $check = ClassSessionService::canTeacherStart($teacher, $slot, $today);
            $actualTeacher = $session
                ? Database::fetch('SELECT name FROM teachers WHERE id = ?', [$session['teacher_id']])
                : null;
            $mine[] = [
                'slot' => $slot,
                'session' => $session,
                'can_start' => $check[0],
                'start_reason' => $check[1],
                'actual_teacher' => $actualTeacher['name'] ?? null,
                'owns_session' => $session && (int) $session['teacher_id'] === (int) $teacher['id'],
            ];
        }

        View::render('teacher/dashboard', [
            'title' => 'Teacher Dashboard',
            'teacher' => $teacher,
            'mine' => $mine,
            'stats' => ReportService::teacherStats((int) $teacher['id']),
            'students' => (int) Database::fetch('SELECT COUNT(*) c FROM students WHERE status = ?', ['ACTIVE'])['c'],
            'today' => $today,
            'day' => $day,
            'success' => App::flash('success'),
            'error' => App::flash('error'),
        ]);
    }

    public function start(array $params = []): void
    {
        $user = $this->boot();
        [$ok, $message, $session] = ClassSessionService::startClass($user['teacher'], (int) $params['id']);
        App::flash($ok ? 'success' : 'error', $message);
        if ($ok && $session) {
            View::redirect('/teacher/session/' . $session['id']);
        }
        View::redirect('/teacher');
    }

    public function reportNotConducted(array $params = []): void
    {
        $user = $this->boot();
        [$ok, $message] = ClassSessionService::reportNotConducted(
            $user['teacher'],
            (int) $params['id'],
            (string) ($_POST['reason'] ?? '')
        );
        App::flash($ok ? 'success' : 'error', $message);
        View::redirect('/teacher');
    }

    public function session(array $params = []): void
    {
        $user = $this->boot();
        $session = $this->loadSession((int) $params['id'], $user['teacher']);
        $subject = Database::fetch('SELECT * FROM subjects WHERE id = ?', [$session['subject_id']]);
        $students = Database::fetchAll(
            'SELECT s.*, a.status AS att_status, a.method
             FROM students s
             LEFT JOIN attendance a ON a.student_id = s.id AND a.session_id = ?
             WHERE s.status = ?
             ORDER BY s.roll_no',
            [$session['id'], 'ACTIVE']
        );
        $present = count(array_filter($students, static fn (array $student): bool => ($student['att_status'] ?? '') === 'PRESENT'));

        View::render('teacher/session', [
            'title' => $subject['subject_name'],
            'session' => $session,
            'subject' => $subject,
            'students' => $students,
            'present' => $present,
            'success' => App::flash('success'),
            'error' => App::flash('error'),
        ]);
    }

    public function end(array $params = []): void
    {
        $user = $this->boot();
        [$ok, $message] = ClassSessionService::endClass($user['teacher'], (int) $params['id']);
        App::flash($ok ? 'success' : 'error', $message);
        View::redirect('/teacher/session/' . $params['id']);
    }

    public function attendanceSave(array $params = []): void
    {
        $user = $this->boot();
        [$ok, $message] = AttendanceService::teacherSet(
            (int) $params['id'],
            (int) ($_POST['student_id'] ?? 0),
            (string) ($_POST['status'] ?? ''),
            (int) $user['id'],
            $user['teacher']
        );
        App::flash($ok ? 'success' : 'error', $message);
        View::redirect('/teacher/session/' . $params['id']);
    }

    public function history(array $params = []): void
    {
        $user = $this->boot();
        $rows = Database::fetchAll(
            'SELECT cs.*, s.subject_code, s.subject_name,
                    (SELECT COUNT(*) FROM attendance a WHERE a.session_id = cs.id AND a.status = "PRESENT") AS present_count,
                    (SELECT COUNT(*) FROM attendance a WHERE a.session_id = cs.id) AS total_marked
             FROM class_sessions cs
             JOIN subjects s ON s.id = cs.subject_id
             WHERE cs.teacher_id = ?
             ORDER BY cs.class_date DESC, cs.scheduled_start DESC',
            [$user['teacher']['id']]
        );
        View::render('teacher/history', [
            'title' => 'My Classes',
            'rows' => $rows,
            'stats' => ReportService::teacherStats((int) $user['teacher']['id']),
        ]);
    }

    public function reports(array $params = []): void
    {
        $user = $this->boot();
        $subjects = Database::fetchAll(
            'SELECT s.* FROM subjects s
             JOIN teacher_subjects ts ON ts.subject_id = s.id
             WHERE ts.teacher_id = ? AND s.status = ?
             ORDER BY s.subject_code',
            [$user['teacher']['id'], 'ACTIVE']
        );
        $reports = [];
        foreach ($subjects as $subject) {
            $stats = ReportService::subjectAttendance((int) $subject['id']);
            $count = Database::fetch(
                'SELECT COUNT(*) c FROM class_sessions WHERE teacher_id = ? AND subject_id = ? AND status = ?',
                [$user['teacher']['id'], $subject['id'], 'COMPLETED']
            );
            $reports[] = ['subject' => $subject, 'stats' => $stats, 'mine' => (int) $count['c']];
        }
        View::render('teacher/reports', ['title' => 'Subject Reports', 'reports' => $reports]);
    }

    public function marks(array $params = []): void
    {
        $user = $this->boot();
        $subjects = Database::fetchAll(
            'SELECT s.* FROM subjects s
             JOIN teacher_subjects ts ON ts.subject_id = s.id
             WHERE ts.teacher_id = ? AND s.status = ?
             ORDER BY s.subject_code',
            [$user['teacher']['id'], 'ACTIVE']
        );
        $subjectId = (int) ($_GET['subject_id'] ?? ($subjects[0]['id'] ?? 0));
        $subject = null;
        foreach ($subjects as $candidate) {
            if ((int) $candidate['id'] === $subjectId) {
                $subject = $candidate;
                break;
            }
        }

        $internals = MarksService::internals();
        $grid = [];
        if ($subject) {
            $students = Database::fetchAll('SELECT * FROM students WHERE status = ? ORDER BY roll_no', ['ACTIVE']);
            foreach ($students as $student) {
                $grid[] = [
                    'student' => $student,
                    'totals' => MarksService::subjectTotals((int) $student['id'], $subjectId),
                ];
            }
        }
        View::render('teacher/marks', [
            'title' => 'Manage internal marks',
            'subjects' => $subjects,
            'subjectId' => $subjectId,
            'internals' => $internals,
            'grid' => $grid,
            'success' => App::flash('success'),
            'error' => App::flash('error'),
        ]);
    }

    public function marksSave(array $params = []): void
    {
        $user = $this->boot();
        $subjectId = (int) ($_POST['subject_id'] ?? 0);
        $assigned = Database::fetch(
            'SELECT s.id FROM subjects s
             JOIN teacher_subjects ts ON ts.subject_id = s.id
             WHERE s.id = ? AND s.status = ? AND ts.teacher_id = ?',
            [$subjectId, 'ACTIVE', $user['teacher']['id']]
        );
        if (!$assigned) {
            App::flash('error', 'You can only edit marks for your assigned active subjects.');
            View::redirect('/teacher/marks');
        }
        $markRows = $_POST['marks'] ?? [];
        if (!is_array($markRows)) {
            App::flash('error', 'Marks were not submitted in a valid format.');
            View::redirect('/teacher/marks?subject_id=' . $subjectId);
        }

        try {
            foreach ($markRows as $studentId => $examMarks) {
                $student = Database::fetch(
                    'SELECT id FROM students WHERE id = ? AND status = ?',
                    [(int) $studentId, 'ACTIVE']
                );
                if (!$student || !is_array($examMarks)) {
                    throw new \InvalidArgumentException('Invalid student selected.');
                }
                foreach ($examMarks as $examId => $value) {
                    if ($value === '') {
                        MarksService::deleteMark((int) $studentId, $subjectId, (int) $examId);
                    } elseif (!is_numeric($value)) {
                        throw new \InvalidArgumentException('Marks must be numeric.');
                    } else {
                        MarksService::saveMark(
                            (int) $studentId,
                            $subjectId,
                            (int) $examId,
                            (float) $value,
                            (int) $user['id']
                        );
                    }
                }
            }
        } catch (\InvalidArgumentException $exception) {
            App::flash('error', $exception->getMessage());
            View::redirect('/teacher/marks?subject_id=' . $subjectId);
        }
        Audit::log((int) $user['id'], 'INTERNAL_MARKS_SAVE', 'Subject #' . $subjectId);
        App::flash('success', 'Marks saved.');
        View::redirect('/teacher/marks?subject_id=' . $subjectId);
    }

    private function loadSession(int $id, array $teacher): array
    {
        $session = Database::fetch('SELECT * FROM class_sessions WHERE id = ?', [$id]);
        if (!$session || (int) $session['teacher_id'] !== (int) $teacher['id']) {
            App::flash('error', 'Session not found.');
            View::redirect('/teacher');
        }
        return $session;
    }
}
