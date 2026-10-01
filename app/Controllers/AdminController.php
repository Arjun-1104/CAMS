<?php
/**
 * AdminController.php
 * HOD pages: account and subject CRUD, timetable, marks, reports, audit logs.
 */

declare(strict_types=1);

namespace App\Controllers;

use App\Core\App;
use App\Core\Auth;
use App\Core\Audit;
use App\Core\Database;
use App\Core\View;
use App\Services\ClassSessionService;
use App\Services\MarksService;
use App\Services\ReportService;

final class AdminController
{
    private function boot(): array
    {
        Auth::requireRole('ADMIN');
        return Auth::user();
    }

    public function dashboard(array $params = []): void
    {
        $this->boot();
        $today = App::today();
        $day = App::dayName();

        $stats = [
            'teachers' => (int) Database::fetch('SELECT COUNT(*) c FROM teachers WHERE status = ?', ['ACTIVE'])['c'],
            'students' => (int) Database::fetch('SELECT COUNT(*) c FROM students WHERE status = ?', ['ACTIVE'])['c'],
            'subjects' => (int) Database::fetch('SELECT COUNT(*) c FROM subjects WHERE status = ?', ['ACTIVE'])['c'],
        ];

        $slots = Database::fetchAll(
            'SELECT t.*, s.subject_code, s.subject_name, te.name AS teacher_name, te.teacher_code
             FROM timetable t
             JOIN subjects s ON s.id = t.subject_id
             JOIN teachers te ON te.id = t.teacher_id
             WHERE t.day = ? AND t.status = ? AND s.status = ?
             ORDER BY t.start_time',
            [$day, 'ACTIVE', 'ACTIVE']
        );

        $todayRows = [];
        foreach ($slots as $slot) {
            $session = ClassSessionService::existingSession((int) $slot['id'], $today);
            $teacher = $session
                ? Database::fetch('SELECT * FROM teachers WHERE id = ?', [$session['teacher_id']])
                : Database::fetch('SELECT * FROM teachers WHERE id = ?', [$slot['teacher_id']]);
            $todayRows[] = [
                'slot' => $slot,
                'session' => $session,
                'status' => $session['status'] ?? 'SCHEDULED',
                'teacher' => $teacher,
            ];
        }

        View::render('admin/dashboard', [
            'title' => 'HOD Dashboard',
            'stats' => $stats,
            'todayRows' => $todayRows,
            'today' => $today,
            'day' => $day,
            'success' => App::flash('success'),
            'error' => App::flash('error'),
        ]);
    }

    public function teachers(array $params = []): void
    {
        $this->boot();
        $rows = Database::fetchAll(
            'SELECT t.*, u.username, u.status AS user_status FROM teachers t JOIN users u ON u.id = t.user_id ORDER BY t.teacher_code'
        );
        View::render('admin/teachers', [
            'title' => 'Teachers',
            'rows' => $rows,
            'edit' => null,
            'success' => App::flash('success'),
            'error' => App::flash('error'),
        ]);
    }

    public function teacherEdit(array $params = []): void
    {
        $this->boot();
        $row = Database::fetch('SELECT t.*, u.username FROM teachers t JOIN users u ON u.id = t.user_id WHERE t.id = ?', [$params['id']]);
        if (!$row) {
            View::redirect('/admin/teachers');
        }
        $rows = Database::fetchAll(
            'SELECT t.*, u.username, u.status AS user_status FROM teachers t JOIN users u ON u.id = t.user_id ORDER BY t.teacher_code'
        );
        View::render('admin/teachers', [
            'title' => 'Edit Teacher',
            'rows' => $rows,
            'edit' => $row,
            'success' => App::flash('success'),
            'error' => App::flash('error'),
        ]);
    }

    public function teacherSave(array $params = []): void
    {
        $user = $this->boot();
        $id = (int) ($_POST['id'] ?? 0);
        $code = trim((string) $_POST['teacher_code']);
        $name = trim((string) $_POST['name']);
        $email = trim((string) $_POST['email']);
        $phone = trim((string) $_POST['phone']);
        $username = trim((string) $_POST['username']);
        $password = (string) ($_POST['password'] ?? '');

        if ($code === '' || $name === '' || $username === '' || ($id === 0 && $password === '')) {
            App::flash('error', 'Code, name, username, and a password for new teachers are required.');
            View::redirect('/admin/teachers');
        }

        if ($id > 0) {
            $t = Database::fetch('SELECT * FROM teachers WHERE id = ?', [$id]);
            Database::query('UPDATE teachers SET teacher_code=?, name=?, email=?, phone=? WHERE id=?', [$code, $name, $email, $phone, $id]);
            Database::query('UPDATE users SET username=? WHERE id=?', [$username, $t['user_id']]);
            if ($password !== '') {
                Database::query('UPDATE users SET password_hash=? WHERE id=?', [password_hash($password, PASSWORD_DEFAULT), $t['user_id']]);
            }
            Audit::log((int) $user['id'], 'TEACHER_UPDATE', $code . ' ' . $name);
            App::flash('success', 'Teacher updated.');
        } else {
            Database::query(
                'INSERT INTO users (username, password_hash, role, status) VALUES (?,?,?,?)',
                [$username, password_hash($password, PASSWORD_DEFAULT), 'TEACHER', 'ACTIVE']
            );
            $uid = (int) Database::lastId();
            Database::query(
                'INSERT INTO teachers (user_id, teacher_code, name, email, phone, department) VALUES (?,?,?,?,?,?)',
                [$uid, $code, $name, $email, $phone, 'MCA']
            );
            Audit::log((int) $user['id'], 'TEACHER_CREATE', $code . ' ' . $name);
            App::flash('success', 'Teacher added.');
        }
        View::redirect('/admin/teachers');
    }

    public function teacherToggle(array $params = []): void
    {
        $user = $this->boot();
        $t = Database::fetch('SELECT * FROM teachers WHERE id = ?', [$params['id']]);
        if ($t) {
            $u = Database::fetch('SELECT * FROM users WHERE id = ?', [$t['user_id']]);
            $next = $u['status'] === 'ACTIVE' ? 'DISABLED' : 'ACTIVE';
            Database::query('UPDATE users SET status=? WHERE id=?', [$next, $u['id']]);
            Database::query('UPDATE teachers SET status=? WHERE id=?', [$next, $t['id']]);
            Audit::log((int) $user['id'], 'TEACHER_TOGGLE', $t['teacher_code'] . ' -> ' . $next);
        }
        View::redirect('/admin/teachers');
    }

    public function students(array $params = []): void
    {
        $this->boot();
        $rows = Database::fetchAll(
            'SELECT s.*, u.username, u.status AS user_status FROM students s JOIN users u ON u.id = s.user_id ORDER BY s.roll_no'
        );
        View::render('admin/students', [
            'title' => 'Students',
            'rows' => $rows,
            'edit' => null,
            'success' => App::flash('success'),
            'error' => App::flash('error'),
        ]);
    }

    public function studentEdit(array $params = []): void
    {
        $this->boot();
        $row = Database::fetch('SELECT s.*, u.username FROM students s JOIN users u ON u.id = s.user_id WHERE s.id = ?', [$params['id']]);
        $rows = Database::fetchAll(
            'SELECT s.*, u.username, u.status AS user_status FROM students s JOIN users u ON u.id = s.user_id ORDER BY s.roll_no'
        );
        View::render('admin/students', [
            'title' => 'Edit Student',
            'rows' => $rows,
            'edit' => $row,
            'success' => App::flash('success'),
            'error' => App::flash('error'),
        ]);
    }

    public function studentSave(array $params = []): void
    {
        $user = $this->boot();
        $id = (int) ($_POST['id'] ?? 0);
        $roll = trim((string) $_POST['roll_no']);
        $name = trim((string) $_POST['name']);
        $email = trim((string) $_POST['email']);
        $section = trim((string) $_POST['section']);
        $username = trim((string) $_POST['username']);
        $password = (string) ($_POST['password'] ?? '');

        if ($roll === '' || $name === '' || $username === '' || ($id === 0 && $password === '')) {
            App::flash('error', 'Roll number, name, username, and a password for new students are required.');
            View::redirect('/admin/students');
        }

        if ($id > 0) {
            $s = Database::fetch('SELECT * FROM students WHERE id = ?', [$id]);
            Database::query(
                'UPDATE students SET roll_no=?, name=?, email=?, section=? WHERE id=?',
                [$roll, $name, $email, $section, $id]
            );
            Database::query('UPDATE users SET username=? WHERE id=?', [$username, $s['user_id']]);
            if ($password !== '') {
                Database::query('UPDATE users SET password_hash=? WHERE id=?', [password_hash($password, PASSWORD_DEFAULT), $s['user_id']]);
            }
            Audit::log((int) $user['id'], 'STUDENT_UPDATE', $roll);
            App::flash('success', 'Student updated.');
        } else {
            Database::query(
                'INSERT INTO users (username, password_hash, role, status) VALUES (?,?,?,?)',
                [$username, password_hash($password, PASSWORD_DEFAULT), 'STUDENT', 'ACTIVE']
            );
            $uid = (int) Database::lastId();
            Database::query(
                'INSERT INTO students (user_id, roll_no, name, email, section, department) VALUES (?,?,?,?,?,?)',
                [$uid, $roll, $name, $email, $section, 'MCA']
            );
            Audit::log((int) $user['id'], 'STUDENT_CREATE', $roll);
            App::flash('success', 'Student added.');
        }
        View::redirect('/admin/students');
    }

    public function studentToggle(array $params = []): void
    {
        $user = $this->boot();
        $s = Database::fetch('SELECT * FROM students WHERE id = ?', [$params['id']]);
        if ($s) {
            $u = Database::fetch('SELECT * FROM users WHERE id = ?', [$s['user_id']]);
            $next = $u['status'] === 'ACTIVE' ? 'DISABLED' : 'ACTIVE';
            Database::query('UPDATE users SET status=? WHERE id=?', [$next, $u['id']]);
            Database::query('UPDATE students SET status=? WHERE id=?', [$next, $s['id']]);
            Audit::log((int) $user['id'], 'STUDENT_TOGGLE', $s['roll_no'] . ' -> ' . $next);
        }
        View::redirect('/admin/students');
    }

    public function subjects(array $params = []): void
    {
        $this->boot();
        View::render('admin/subjects', [
            'title' => 'Subjects',
            'rows' => Database::fetchAll('SELECT * FROM subjects ORDER BY subject_code'),
            'success' => App::flash('success'),
            'error' => App::flash('error'),
        ]);
    }

    public function subjectSave(array $params = []): void
    {
        $user = $this->boot();
        $id = (int) ($_POST['id'] ?? 0);
        $code = trim((string) $_POST['subject_code']);
        $name = trim((string) $_POST['subject_name']);
        if ($code === '' || $name === '') {
            App::flash('error', 'Subject code and name are required.');
            View::redirect('/admin/subjects');
        }
        if ($id > 0) {
            $duplicate = Database::fetch(
                'SELECT id FROM subjects WHERE subject_code = ? AND id <> ?',
                [$code, $id]
            );
            if ($duplicate) {
                App::flash('error', 'That subject code is already in use. Edit or re-enable the existing subject instead.');
                View::redirect('/admin/subjects');
            }
            Database::query('UPDATE subjects SET subject_code=?, subject_name=?, status=? WHERE id=?', [$code, $name, 'ACTIVE', $id]);
            Audit::log((int) $user['id'], 'SUBJECT_UPDATE', $code);
        } else {
            $existing = Database::fetch('SELECT id, status FROM subjects WHERE subject_code = ?', [$code]);
            if ($existing) {
                if ($existing['status'] === 'ACTIVE') {
                    App::flash('error', 'That subject is already active. Use Edit to change its name.');
                    View::redirect('/admin/subjects');
                }
                Database::query(
                    'UPDATE subjects SET subject_name = ?, status = ? WHERE id = ?',
                    [$name, 'ACTIVE', $existing['id']]
                );
                Audit::log((int) $user['id'], 'SUBJECT_REACTIVATE', $code);
            } else {
                Database::query(
                    'INSERT INTO subjects (subject_code, subject_name, department, status) VALUES (?,?,?,?)',
                    [$code, $name, 'MCA', 'ACTIVE']
                );
                Audit::log((int) $user['id'], 'SUBJECT_CREATE', $code);
            }
        }
        App::flash('success', 'Subject saved.');
        View::redirect('/admin/subjects');
    }

    public function subjectDelete(array $params = []): void
    {
        $user = $this->boot();
        $subject = Database::fetch('SELECT * FROM subjects WHERE id = ?', [$params['id']]);
        if ($subject) {
            $next = $subject['status'] === 'ACTIVE' ? 'DISABLED' : 'ACTIVE';
            Database::query('UPDATE subjects SET status = ? WHERE id = ?', [$next, $params['id']]);
            Audit::log((int) $user['id'], 'SUBJECT_STATUS', $subject['subject_code'] . ' -> ' . $next);
            App::flash('success', 'Subject ' . strtolower($next) . '.');
        }
        View::redirect('/admin/subjects');
    }

    public function assignments(array $params = []): void
    {
        $this->boot();
        View::render('admin/assignments', [
            'title' => 'Assign Teachers',
            'teachers' => Database::fetchAll('SELECT * FROM teachers WHERE status = ? ORDER BY name', ['ACTIVE']),
            'subjects' => Database::fetchAll('SELECT * FROM subjects WHERE status = ? ORDER BY subject_code', ['ACTIVE']),
            'rows' => Database::fetchAll(
                'SELECT ts.*, t.name AS teacher_name, t.teacher_code, s.subject_code, s.subject_name
                 FROM teacher_subjects ts
                 JOIN teachers t ON t.id = ts.teacher_id
                 JOIN subjects s ON s.id = ts.subject_id
                 WHERE t.status = ? AND s.status = ?
                 ORDER BY s.subject_code',
                ['ACTIVE', 'ACTIVE']
            ),
            'success' => App::flash('success'),
            'error' => App::flash('error'),
        ]);
    }

    public function assignmentSave(array $params = []): void
    {
        $user = $this->boot();
        $tid = (int) $_POST['teacher_id'];
        $sid = (int) $_POST['subject_id'];
        $teacher = Database::fetch('SELECT id FROM teachers WHERE id = ? AND status = ?', [$tid, 'ACTIVE']);
        $subject = Database::fetch('SELECT id FROM subjects WHERE id = ? AND status = ?', [$sid, 'ACTIVE']);
        if (!$teacher || !$subject) {
            App::flash('error', 'Select an active teacher and subject.');
        } else {
            Database::query(
                'INSERT INTO teacher_subjects (teacher_id, subject_id, assigned_by) VALUES (?,?,?)
                 ON DUPLICATE KEY UPDATE assigned_by = VALUES(assigned_by)',
                [$tid, $sid, $user['id']]
            );
            Audit::log((int) $user['id'], 'ASSIGN_SUBJECT', "teacher {$tid} subject {$sid}");
            App::flash('success', 'Teacher assigned to subject.');
        }
        View::redirect('/admin/assignments');
    }

    public function assignmentDelete(array $params = []): void
    {
        $user = $this->boot();
        Database::query('DELETE FROM teacher_subjects WHERE id = ?', [$params['id']]);
        Audit::log((int) $user['id'], 'UNASSIGN_SUBJECT', 'id ' . $params['id']);
        App::flash('success', 'Assignment removed.');
        View::redirect('/admin/assignments');
    }

    public function timetable(array $params = []): void
    {
        $this->boot();
        $days = ['Monday','Tuesday','Wednesday','Thursday','Friday','Saturday'];
        $slots = Database::fetchAll(
            'SELECT t.*, s.subject_code, s.subject_name, te.name AS teacher_name, te.teacher_code
             FROM timetable t
             JOIN subjects s ON s.id = t.subject_id
             JOIN teachers te ON te.id = t.teacher_id
             WHERE t.status = ? AND s.status = ?
             ORDER BY FIELD(t.day,"Monday","Tuesday","Wednesday","Thursday","Friday","Saturday"), t.start_time',
            ['ACTIVE', 'ACTIVE']
        );
        $grid = [];
        foreach ($slots as $slot) {
            $grid[$slot['day']][] = $slot;
        }
        View::render('admin/timetable', [
            'title' => 'Timetable',
            'days' => $days,
            'grid' => $grid,
            'teachers' => Database::fetchAll('SELECT * FROM teachers WHERE status=? ORDER BY name', ['ACTIVE']),
            'subjects' => Database::fetchAll('SELECT * FROM subjects WHERE status=? ORDER BY subject_code', ['ACTIVE']),
            'success' => App::flash('success'),
            'error' => App::flash('error'),
        ]);
    }

    public function timetableSave(array $params = []): void
    {
        $user = $this->boot();
        $day = (string) ($_POST['day'] ?? '');
        $start = (string) ($_POST['start_time'] ?? '');
        $end = (string) ($_POST['end_time'] ?? '');
        $subjectId = (int) ($_POST['subject_id'] ?? 0);
        $teacherId = (int) ($_POST['teacher_id'] ?? 0);
        $assignment = Database::fetch(
            'SELECT id FROM teacher_subjects WHERE teacher_id = ? AND subject_id = ?',
            [$teacherId, $subjectId]
        );
        if (!in_array($day, ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'], true)
            || $start === '' || $end <= $start || !$assignment) {
            App::flash('error', 'Choose a valid day, time range, and teacher assigned to the subject.');
            View::redirect('/admin/timetable');
        }
        Database::query(
            'INSERT INTO timetable (day, subject_id, teacher_id, start_time, end_time, status) VALUES (?,?,?,?,?,?)',
            [$day, $subjectId, $teacherId, $start, $end, 'ACTIVE']
        );
        Audit::log((int) $user['id'], 'TIMETABLE_CREATE', $_POST['day'] . ' ' . $_POST['start_time']);
        App::flash('success', 'Timetable slot created.');
        View::redirect('/admin/timetable');
    }

    public function timetableDelete(array $params = []): void
    {
        $user = $this->boot();
        Database::query('UPDATE timetable SET status = ? WHERE id = ?', ['INACTIVE', $params['id']]);
        Audit::log((int) $user['id'], 'TIMETABLE_DISABLE', 'id ' . $params['id']);
        App::flash('success', 'Slot disabled.');
        View::redirect('/admin/timetable');
    }

    public function reports(array $params = []): void
    {
        $this->boot();
        $teachers = Database::fetchAll('SELECT * FROM teachers ORDER BY name');
        $teacherReports = [];
        foreach ($teachers as $t) {
            $teacherReports[] = ['teacher' => $t, 'stats' => ReportService::teacherStats((int) $t['id'])];
        }
        $subjects = Database::fetchAll('SELECT * FROM subjects WHERE status = ? ORDER BY subject_code', ['ACTIVE']);
        $subjectReports = [];
        foreach ($subjects as $s) {
            $subjectReports[] = ['subject' => $s, 'stats' => ReportService::subjectAttendance((int) $s['id'])];
        }

        $students = Database::fetchAll('SELECT * FROM students WHERE status=? ORDER BY roll_no', ['ACTIVE']);
        $studentReports = [];
        foreach ($students as $st) {
            $stats = \App\Services\AttendanceService::studentStats((int) $st['id']);
            $studentReports[] = ['student' => $st, 'overall' => $stats['overall'], 'subjects' => $stats['subjects']];
        }

        $sessions = Database::fetchAll(
            'SELECT cs.*, s.subject_code, s.subject_name, t.name AS teacher_name, t.teacher_code
             FROM class_sessions cs
             JOIN subjects s ON s.id = cs.subject_id
             JOIN teachers t ON t.id = cs.teacher_id
             ORDER BY cs.class_date DESC, cs.scheduled_start DESC
             LIMIT 80'
        );

        View::render('admin/reports', [
            'title' => 'Reports',
            'teacherReports' => $teacherReports,
            'subjectReports' => $subjectReports,
            'studentReports' => $studentReports,
            'sessions' => $sessions,
        ]);
    }

    public function marks(array $params = []): void
    {
        $this->boot();
        $cards = [];
        $students = Database::fetchAll('SELECT * FROM students WHERE status = ? ORDER BY roll_no', ['ACTIVE']);
        foreach ($students as $student) {
            $cards[] = ['student' => $student, 'card' => MarksService::studentCard((int) $student['id'])];
        }
        View::render('admin/marks', [
            'title' => 'Internal marks',
            'cards' => $cards,
            'internals' => MarksService::internals(),
        ]);
    }

    public function internals(array $params = []): void
    {
        $this->boot();
        View::render('admin/internals', [
            'title' => 'Manage internals',
            'rows' => MarksService::internals(),
            'success' => App::flash('success'),
            'error' => App::flash('error'),
        ]);
    }

    public function internalSave(array $params = []): void
    {
        $user = $this->boot();
        $id = (int) ($_POST['id'] ?? 0);
        $name = trim((string) ($_POST['name'] ?? ''));
        $maximumInput = $_POST['max_marks'] ?? null;
        $maximum = is_scalar($maximumInput) ? filter_var($maximumInput, FILTER_VALIDATE_FLOAT) : false;
        $order = max(1, (int) ($_POST['sort_order'] ?? 1));
        if ($name === '' || $maximum === false || $maximum <= 0 || $maximum > 9999) {
            App::flash('error', 'Enter an internal name and a maximum mark greater than zero.');
            View::redirect('/admin/internals');
        }
        if ($id > 0) {
            $highestExistingMark = Database::fetch(
                'SELECT MAX(marks) AS highest FROM internal_marks WHERE exam_id = ?',
                [$id]
            );
            if ($highestExistingMark['highest'] !== null && (float) $maximum < (float) $highestExistingMark['highest']) {
                App::flash('error', 'Maximum marks cannot be lower than marks already entered for this internal.');
                View::redirect('/admin/internals');
            }
            Database::query(
                'UPDATE internal_exams SET name = ?, max_marks = ?, sort_order = ? WHERE id = ?',
                [$name, $maximum, $order, $id]
            );
        } else {
            Database::query(
                'INSERT INTO internal_exams (name, max_marks, sort_order) VALUES (?, ?, ?)',
                [$name, $maximum, $order]
            );
        }
        Audit::log((int) $user['id'], 'INTERNAL_SAVE', $name);
        App::flash('success', 'Internal exam saved.');
        View::redirect('/admin/internals');
    }

    public function internalDelete(array $params = []): void
    {
        $user = $this->boot();
        $exam = Database::fetch('SELECT name FROM internal_exams WHERE id = ?', [$params['id']]);
        if ($exam) {
            Database::query('DELETE FROM internal_exams WHERE id = ?', [$params['id']]);
            Audit::log((int) $user['id'], 'INTERNAL_DELETE', $exam['name']);
        }
        App::flash('success', 'Internal exam removed.');
        View::redirect('/admin/internals');
    }

    public function audit(array $params = []): void
    {
        $this->boot();
        $rows = Database::fetchAll(
            'SELECT a.*, u.username, u.role FROM audit_logs a LEFT JOIN users u ON u.id = a.user_id ORDER BY a.id DESC LIMIT 200'
        );
        View::render('admin/audit', ['title' => 'Audit Logs', 'rows' => $rows]);
    }

}
