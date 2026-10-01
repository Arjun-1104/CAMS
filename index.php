<?php

declare(strict_types=1);

require __DIR__ . '/app/bootstrap.php';

use App\Core\Router;

$router = new Router();

$router->get('/', 'HomeController@index');
$router->get('/login', 'AuthController@showLogin');
$router->post('/login', 'AuthController@login');
$router->post('/logout', 'AuthController@logout');
$router->get('/install', 'InstallController@show');
$router->post('/install', 'InstallController@run');
$router->get('/api/server-time', 'HomeController@serverTime');

$router->get('/admin', 'AdminController@dashboard');
$router->get('/admin/teachers', 'AdminController@teachers');
$router->get('/admin/teachers/{id}/edit', 'AdminController@teacherEdit');
$router->post('/admin/teachers', 'AdminController@teacherSave');
$router->post('/admin/teachers/{id}/toggle', 'AdminController@teacherToggle');
$router->get('/admin/students', 'AdminController@students');
$router->get('/admin/students/{id}/edit', 'AdminController@studentEdit');
$router->post('/admin/students', 'AdminController@studentSave');
$router->post('/admin/students/{id}/toggle', 'AdminController@studentToggle');
$router->get('/admin/subjects', 'AdminController@subjects');
$router->post('/admin/subjects', 'AdminController@subjectSave');
$router->post('/admin/subjects/{id}/delete', 'AdminController@subjectDelete');
$router->get('/admin/assignments', 'AdminController@assignments');
$router->post('/admin/assignments', 'AdminController@assignmentSave');
$router->post('/admin/assignments/{id}/delete', 'AdminController@assignmentDelete');
$router->get('/admin/timetable', 'AdminController@timetable');
$router->post('/admin/timetable', 'AdminController@timetableSave');
$router->post('/admin/timetable/{id}/delete', 'AdminController@timetableDelete');
$router->get('/admin/marks', 'AdminController@marks');
$router->get('/admin/internals', 'AdminController@internals');
$router->post('/admin/internals', 'AdminController@internalSave');
$router->post('/admin/internals/{id}/delete', 'AdminController@internalDelete');
$router->get('/admin/reports', 'AdminController@reports');
$router->get('/admin/audit', 'AdminController@audit');

$router->get('/teacher', 'TeacherController@dashboard');
$router->post('/teacher/start/{id}', 'TeacherController@start');
$router->post('/teacher/not-conducted/{id}', 'TeacherController@reportNotConducted');
$router->get('/teacher/session/{id}', 'TeacherController@session');
$router->post('/teacher/session/{id}/end', 'TeacherController@end');
$router->post('/teacher/session/{id}/attendance', 'TeacherController@attendanceSave');
$router->get('/teacher/history', 'TeacherController@history');
$router->get('/teacher/reports', 'TeacherController@reports');
$router->get('/teacher/marks', 'TeacherController@marks');
$router->post('/teacher/marks', 'TeacherController@marksSave');

$router->get('/student', 'StudentController@dashboard');
$router->get('/student/history', 'StudentController@history');

$router->dispatch();
