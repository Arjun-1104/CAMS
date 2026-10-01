-- Remove only the demo dataset inserted by the original seed.sql.
-- This is a no-op unless the original seed audit marker is present.
USE college_attendance;
SET @remove_seed_demo = (
  SELECT COUNT(*) > 0
  FROM audit_logs
  WHERE action = 'SEED' AND details = 'Demo dataset loaded for MCA semester 3'
);

START TRANSACTION;

DELETE cs
FROM class_sessions cs
JOIN timetable tt ON tt.id = cs.timetable_id
LEFT JOIN teachers t ON t.id = tt.teacher_id
WHERE @remove_seed_demo = 1
  AND (tt.id BETWEEN 1 AND 20 OR t.teacher_code IN ('T001','T002','T003','T004','T005'));

DELETE FROM attendance
WHERE @remove_seed_demo = 1
  AND student_id IN (SELECT id FROM students WHERE roll_no REGEXP '^MCA(00[1-9]|01[0-2])$');

DELETE FROM timetable
WHERE @remove_seed_demo = 1
  AND (id BETWEEN 1 AND 20 OR teacher_id IN (
    SELECT id FROM teachers WHERE teacher_code IN ('T001','T002','T003','T004','T005')
  ));

DELETE FROM teacher_subjects
WHERE @remove_seed_demo = 1
  AND teacher_id IN (SELECT id FROM teachers WHERE teacher_code IN ('T001','T002','T003','T004','T005'));

DELETE FROM students
WHERE @remove_seed_demo = 1
  AND roll_no REGEXP '^MCA(00[1-9]|01[0-2])$';

DELETE FROM teachers
WHERE @remove_seed_demo = 1
  AND teacher_code IN ('T001','T002','T003','T004','T005');

DELETE u
FROM users u
WHERE @remove_seed_demo = 1
  AND (
    (u.role = 'TEACHER' AND NOT EXISTS (SELECT 1 FROM teachers t WHERE t.user_id = u.id))
    OR (u.role = 'STUDENT' AND NOT EXISTS (SELECT 1 FROM students s WHERE s.user_id = u.id))
  );

DELETE FROM login_attempts
WHERE @remove_seed_demo = 1
  AND username IN (
    'neha_sahu','shruti_ghatge','nadini_sirothia','ss_chauhan','pushpendra_chaturvedi',
    'mca001','mca002','mca003','mca004','mca005','mca006',
    'mca007','mca008','mca009','mca010','mca011','mca012'
  );

UPDATE subjects SET status = 'ACTIVE' WHERE @remove_seed_demo = 1;
UPDATE teachers SET status = 'ACTIVE' WHERE @remove_seed_demo = 1 AND teacher_code = 'T006';
UPDATE users u
JOIN teachers t ON t.user_id = u.id
SET u.status = 'ACTIVE'
WHERE @remove_seed_demo = 1 AND t.teacher_code = 'T006';

DELETE FROM audit_logs
WHERE @remove_seed_demo = 1
  AND action = 'SEED' AND details = 'Demo dataset loaded for MCA semester 3';

COMMIT;
