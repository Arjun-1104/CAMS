<?php
/**
 * One-time install form.
 * Database is already created on this PC — use this page only if tables are missing.
 */
use App\Core\App;
use App\Core\Csrf;
use App\Core\View;
?>
<div class="auth-card" style="max-width:640px;grid-template-columns:1fr">
  <div class="auth-form">
    <span class="pill">SETUP</span>
    <h2 class="serif">Create database (first time only)</h2>
    <p class="muted">This creates empty application tables and your first HOD login. Teachers, students, subjects, and timetable entries are added in the application.</p>
    <?php if (empty($pdo_mysql)): ?>
      <div class="flash bad">Open the site with XAMPP Apache: <code>http://localhost/CAMS</code> (not php -S).</div>
    <?php endif; ?>
    <?php if (!empty($error)): ?><div class="flash bad"><?= View::e($error) ?></div><?php endif; ?>
    <form method="post" action="<?= App::url('/install') ?>">
      <?= Csrf::field() ?>
      <div class="form-row">
        <div><label>Host</label><input name="host" value="127.0.0.1"></div>
        <div><label>Port</label><input name="port" value="3306"></div>
      </div>
      <label>Database name</label>
      <input name="name" value="college_attendance" readonly>
      <div class="form-row">
        <div><label>User</label><input name="user" value="root"></div>
        <div><label>Password</label><input name="pass" type="password" placeholder="Leave empty for XAMPP"></div>
      </div>
      <h3 class="serif">Initial HOD account</h3>
      <div class="form-row">
        <div><label>Username</label><input name="admin_username" required maxlength="64"></div>
        <div><label>Full name</label><input name="admin_name" required maxlength="120"></div>
      </div>
      <label>Email</label><input name="admin_email" type="email" required maxlength="120">
      <label>Password (at least 12 characters)</label><input name="admin_password" type="password" required minlength="12" autocomplete="new-password">
      <button class="btn gold" type="submit">Create empty application database</button>
    </form>
    <p class="hint"><a href="<?= App::url('/login') ?>">Back to login</a> — existing user data is never rebuilt by this setup page. Use the upgrade migration for an existing database.</p>
  </div>
</div>
