<?php
/**
 * Main layout: left menu + page content.
 * Used after login (HOD, teacher, student).
 */
use App\Core\App;
use App\Core\Auth;
use App\Core\Csrf;
use App\Core\View;

$user = Auth::user();
$role = $user['role'] ?? '';
$name = $user['admin']['name'] ?? $user['teacher']['name'] ?? $user['student']['name'] ?? ($user['username'] ?? '');
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title><?= View::e($title ?? 'CAMS') ?> · MCA Attendance</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link href="https://fonts.googleapis.com/css2?family=Fraunces:opsz,wght@9..144,600;9..144,700&family=IBM+Plex+Mono:wght@500&family=IBM+Plex+Sans:wght@400;500;600&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="<?= View::e(App::url('/public/assets/css/app.css')) ?>">
</head>
<body>
<div class="layout">
  <aside class="sidebar">
    <p class="brand">CAMS<small>MCA CLASS & ATTENDANCE</small></p>
    <nav class="nav">
      <?php if ($role === 'ADMIN'): ?>
        <a href="<?= App::url('/admin') ?>">Dashboard</a>
        <a href="<?= App::url('/admin/teachers') ?>">Teachers</a>
        <a href="<?= App::url('/admin/students') ?>">Students</a>
        <a href="<?= App::url('/admin/subjects') ?>">Subjects</a>
        <a href="<?= App::url('/admin/assignments') ?>">Assign teachers</a>
        <a href="<?= App::url('/admin/timetable') ?>">Timetable</a>
        <a href="<?= App::url('/admin/marks') ?>">Internal marks</a>
        <a href="<?= App::url('/admin/internals') ?>">Manage internals</a>
        <a href="<?= App::url('/admin/reports') ?>">Reports</a>
        <a href="<?= App::url('/admin/audit') ?>">Audit logs</a>
      <?php elseif ($role === 'TEACHER'): ?>
        <a href="<?= App::url('/teacher') ?>">Today's classes</a>
        <a href="<?= App::url('/teacher/history') ?>">Conducted classes</a>
        <a href="<?= App::url('/teacher/marks') ?>">Internal marks</a>
        <a href="<?= App::url('/teacher/reports') ?>">Reports</a>
      <?php else: ?>
        <a href="<?= App::url('/student') ?>">My attendance</a>
        <a href="<?= App::url('/student/history') ?>">History</a>
        <a href="<?= App::url('/student#internal-marks') ?>">Internal marks</a>
      <?php endif; ?>
      <form method="post" action="<?= App::url('/logout') ?>">
        <?= Csrf::field() ?>
        <button type="submit">Sign out</button>
      </form>
    </nav>
    <p class="hint" style="margin-top:28px;color:#d9c9a8"><?= View::e($name) ?><br><?= View::e($role) ?></p>
  </aside>
  <main class="main">
    <div class="topbar">
      <div>
        <h1 class="serif" style="margin:0;font-size:28px"><?= View::e($title ?? '') ?></h1>
        <p class="muted" style="margin:4px 0 0">College class and attendance management</p>
      </div>
      <div id="server-clock" class="clock" data-url="<?= App::url('/api/server-time') ?>">Loading server time…</div>
    </div>
    <?= $content ?>
  </main>
</div>
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script src="<?= View::e(App::url('/public/assets/js/app.js')) ?>"></script>
</body>
</html>
