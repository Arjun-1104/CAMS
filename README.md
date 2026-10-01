# CAMS — College Class & Attendance Management

CAMS is a PHP and MySQL application for HODs, teachers, and students. It uses a single cohort, a configurable weekly timetable, manually recorded attendance, and configurable internal assessments.

## Run with XAMPP

1. Start Apache and MySQL from XAMPP.
2. Open `http://localhost/CAMS/install` for a new, empty database.
3. Enter the MySQL connection details and create the initial HOD account.
4. Sign in with the HOD credentials created in setup.

The installer refuses to rebuild a database that already contains user accounts. It does not install sample teachers, students, subjects, marks, or a sample timetable.

## Upgrade an existing database

1. Back up the `college_attendance` database in phpMyAdmin.
2. Select the database and import `database/migrate_2026_09.sql`.
3. The migration preserves user, subject, class, and attendance records while removing the room/semester columns and obsolete QR/substitution tables.
4. Import `database/remove_seed_demo_data.sql` only to clean the original demo dataset. It is guarded by the original seed marker, removes only the seeded sample accounts, timetable, sessions, and attendance, keeps the HOD account and subjects, and activates the newly added `T006` teacher and subjects.

Do not import `database/schema.sql` into an existing database: it is for fresh installations only.

## HOD workflow

- Add or edit teachers and students; assign teachers to subjects before creating timetable slots.
- Create and update subjects. Disabled subjects remain listed and can be re-enabled. Saving an already-used subject code reactivates that record instead of inserting a duplicate.
- Configure the weekly timetable. Its times are editable and are not a start-time lock.
- Add as many internal exams as needed, set each maximum mark, then view student marks under **Internal marks**.
- Daily class information and teacher-submitted reasons are read-only on the HOD dashboard.

## Teacher and student workflow

- Teachers can start an assigned subject's class at any time on its scheduled day, record actual start/end time, manually mark attendance, and submit a reason when a class will not be conducted.
- Teachers assigned to a subject may conduct its timetable class, even when it starts later in the day. A class has one recorded outcome per timetable slot and day.
- Teachers manage marks for their assigned subjects. Leaving a mark blank removes that mark; totals and percentages update automatically.
- Students can view their attendance and subject-wise internal marks on their dashboard.

## Account status

**Active** accounts can sign in and appear in assignment and attendance lists. **Disabled** accounts cannot sign in or be selected for new assignments/attendance. Records are retained; changing the toggle back to Active restores access.

## Project layout

```
index.php              URL routes
config/config.php      MySQL connection and application settings
database/schema.sql    Fresh-install schema
database/migrate_2026_09.sql  Existing-database upgrade
database/seed.sql      Non-user application defaults only
app/Controllers/       HOD, teacher, student, and setup actions
app/Services/          Attendance, class sessions, marks, and reports
views/                 Role-specific screens
public/assets/         Responsive CSS and JavaScript
```
