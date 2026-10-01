<?php
/**
 * config.php
 * Change database settings here if needed.
 *
 * XAMPP default:
 *   user = root
 *   password = (empty)
 *   database name = college_attendance
 */

return [
    'app' => [
        'name' => 'CAMS',
        'title' => 'Smart College Class & Attendance Management System',
        'timezone' => 'Asia/Kolkata',
        'base_url' => '', // leave empty; the app detects http://localhost/CAMS
    ],
    'db' => [
        'host' => '127.0.0.1',
        'port' => 3306,
        'name' => 'college_attendance',
        'user' => 'root',
        'pass' => '', // if XAMPP MySQL has a password, type it here
        'charset' => 'utf8mb4',
    ],
    'security' => [
        'max_login_attempts' => 8,
        'lockout_minutes' => 15,
    ],
];
