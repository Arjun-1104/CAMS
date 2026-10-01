<?php
/**
 * Login screen HTML.
 */
use App\Core\App;
use App\Core\Csrf;
use App\Core\View;
?>
<div class="auth-card">
  <div class="auth-side">
    <span class="pill">MCA · CAMS</span>
    <h1>College Class & Attendance</h1>
    <p>Flexible class sessions, manually recorded attendance, internal marks, and clear HOD oversight.</p>
  </div>
  <div class="auth-form">
    <h2 class="serif">Sign in</h2>
    <?php if (!empty($error)): ?><div class="flash bad"><?= View::e($error) ?></div><?php endif; ?>
    <?php if (!empty($success)): ?><div class="flash ok"><?= View::e($success) ?></div><?php endif; ?>
    <form method="post" action="<?= App::url('/login') ?>">
      <?= Csrf::field() ?>
      <label>Username</label>
      <input name="username" value="<?= View::e(App::old('username')) ?>" required autofocus>
      <label>Password</label>
      <input type="password" name="password" required>
      <button class="btn gold" type="submit">Enter system</button>
    </form>
    <p class="hint"><a href="<?= App::url('/install') ?>">First-time setup / install database</a></p>
  </div>
</div>
