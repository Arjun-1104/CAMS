<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Database;

final class MarksService
{
    public static function internals(): array
    {
        return Database::fetchAll('SELECT * FROM internal_exams ORDER BY sort_order, id');
    }

    public static function subjectTotals(int $studentId, int $subjectId): array
    {
        $internals = self::internals();
        $cells = [];
        $obtained = 0.0;
        $maximum = 0.0;
        foreach ($internals as $exam) {
            $row = Database::fetch(
                'SELECT marks FROM internal_marks WHERE student_id = ? AND subject_id = ? AND exam_id = ?',
                [$studentId, $subjectId, $exam['id']]
            );
            $marks = $row ? (float) $row['marks'] : null;
            $cells[] = ['exam' => $exam, 'marks' => $marks];
            $maximum += (float) $exam['max_marks'];
            $obtained += $marks ?? 0;
        }

        $percent = $maximum > 0 ? round(($obtained / $maximum) * 100, 1) : 0;
        return compact('cells', 'obtained', 'maximum', 'percent');
    }

    public static function studentCard(int $studentId): array
    {
        $subjects = Database::fetchAll('SELECT * FROM subjects WHERE status = ? ORDER BY subject_code', ['ACTIVE']);
        $rows = [];
        $obtained = 0.0;
        $maximum = 0.0;
        foreach ($subjects as $subject) {
            $totals = self::subjectTotals($studentId, (int) $subject['id']);
            $rows[] = ['subject' => $subject] + $totals;
            $obtained += $totals['obtained'];
            $maximum += $totals['maximum'];
        }

        return [
            'subjects' => $rows,
            'obtained' => $obtained,
            'maximum' => $maximum,
            'percent' => $maximum > 0 ? round(($obtained / $maximum) * 100, 1) : 0,
            'internals' => self::internals(),
        ];
    }

    public static function saveMark(int $studentId, int $subjectId, int $examId, float $marks, int $userId): void
    {
        $exam = Database::fetch('SELECT max_marks FROM internal_exams WHERE id = ?', [$examId]);
        if (!$exam || $marks < 0 || $marks > (float) $exam['max_marks']) {
            throw new \InvalidArgumentException('Marks must be between zero and the internal maximum.');
        }

        Database::query(
            'INSERT INTO internal_marks (student_id, subject_id, exam_id, marks, updated_by)
             VALUES (?, ?, ?, ?, ?)
             ON DUPLICATE KEY UPDATE marks = VALUES(marks), updated_by = VALUES(updated_by)',
            [$studentId, $subjectId, $examId, $marks, $userId]
        );
    }

    public static function deleteMark(int $studentId, int $subjectId, int $examId): void
    {
        Database::query(
            'DELETE FROM internal_marks WHERE student_id = ? AND subject_id = ? AND exam_id = ?',
            [$studentId, $subjectId, $examId]
        );
    }
}
