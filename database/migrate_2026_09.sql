-- Data-preserving upgrade for an existing CAMS database.
-- Back up college_attendance before importing this file in phpMyAdmin.
USE college_attendance;

ALTER TABLE class_sessions
  MODIFY COLUMN status ENUM('SCHEDULED','ONGOING','COMPLETED','CANCELLED','NOT_CONDUCTED','SKIPPED')
  NOT NULL DEFAULT 'SCHEDULED';

SET @has_skip_reason = (
  SELECT COUNT(*) FROM information_schema.COLUMNS
  WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'class_sessions' AND COLUMN_NAME = 'skip_reason'
);
SET @sql = IF(@has_skip_reason = 0,
  'ALTER TABLE class_sessions ADD COLUMN skip_reason VARCHAR(255) NULL AFTER cancel_reason',
  'SELECT 1');
PREPARE migration_stmt FROM @sql;
EXECUTE migration_stmt;
DEALLOCATE PREPARE migration_stmt;

SET @has_student_semester = (
  SELECT COUNT(*) FROM information_schema.COLUMNS
  WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'students' AND COLUMN_NAME = 'semester'
);
SET @sql = IF(@has_student_semester > 0, 'ALTER TABLE students DROP COLUMN semester', 'SELECT 1');
PREPARE migration_stmt FROM @sql;
EXECUTE migration_stmt;
DEALLOCATE PREPARE migration_stmt;

SET @has_subject_semester = (
  SELECT COUNT(*) FROM information_schema.COLUMNS
  WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'subjects' AND COLUMN_NAME = 'semester'
);
SET @sql = IF(@has_subject_semester > 0, 'ALTER TABLE subjects DROP COLUMN semester', 'SELECT 1');
PREPARE migration_stmt FROM @sql;
EXECUTE migration_stmt;
DEALLOCATE PREPARE migration_stmt;

SET @has_timetable_semester = (
  SELECT COUNT(*) FROM information_schema.COLUMNS
  WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'timetable' AND COLUMN_NAME = 'semester'
);
SET @sql = IF(@has_timetable_semester > 0, 'ALTER TABLE timetable DROP COLUMN semester', 'SELECT 1');
PREPARE migration_stmt FROM @sql;
EXECUTE migration_stmt;
DEALLOCATE PREPARE migration_stmt;

SET @has_room_number = (
  SELECT COUNT(*) FROM information_schema.COLUMNS
  WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'timetable' AND COLUMN_NAME = 'room_no'
);
SET @sql = IF(@has_room_number > 0, 'ALTER TABLE timetable DROP COLUMN room_no', 'SELECT 1');
PREPARE migration_stmt FROM @sql;
EXECUTE migration_stmt;
DEALLOCATE PREPARE migration_stmt;

UPDATE attendance SET method = 'TEACHER' WHERE method = 'QR';
ALTER TABLE attendance MODIFY COLUMN method ENUM('TEACHER','MANUAL') NOT NULL DEFAULT 'TEACHER';

DROP TABLE IF EXISTS session_tokens;
DROP TABLE IF EXISTS class_substitutions;
DROP TABLE IF EXISTS settings;

CREATE TABLE IF NOT EXISTS internal_exams (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(80) NOT NULL,
  max_marks DECIMAL(6,2) NOT NULL DEFAULT 20,
  sort_order INT NOT NULL DEFAULT 1
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS internal_marks (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  student_id INT UNSIGNED NOT NULL,
  subject_id INT UNSIGNED NOT NULL,
  exam_id INT UNSIGNED NOT NULL,
  marks DECIMAL(6,2) NOT NULL,
  updated_by INT UNSIGNED NULL,
  UNIQUE KEY uq_mark (student_id, subject_id, exam_id),
  CONSTRAINT fk_im_st FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE CASCADE,
  CONSTRAINT fk_im_sub FOREIGN KEY (subject_id) REFERENCES subjects(id) ON DELETE CASCADE,
  CONSTRAINT fk_im_ex FOREIGN KEY (exam_id) REFERENCES internal_exams(id) ON DELETE CASCADE
) ENGINE=InnoDB;
