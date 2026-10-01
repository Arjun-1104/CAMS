<?php
use App\Core\App;
use App\Core\Csrf;
use App\Core\View;
?>
<?php if (!empty($success)): ?><div class="flash ok"><?= View::e($success) ?></div><?php endif; ?>
<?php if (!empty($error)): ?><div class="flash bad"><?= View::e($error) ?></div><?php endif; ?>
<p class="muted">Active accounts can sign in and appear in assignment lists. Disabled accounts cannot sign in and are excluded from new assignments.</p>
<div class="grid cards-2">
  <div class="card">
    <h3 class="serif" style="margin-top:0"><?= $edit ? 'Edit teacher' : 'Add teacher' ?></h3>
    <form method="post" action="<?= App::url('/admin/teachers') ?>">
      <?= Csrf::field() ?>
      <input type="hidden" name="id" value="<?= View::e($edit['id'] ?? '') ?>">
      <div class="form-row">
        <div><label>Teacher code</label><input name="teacher_code" value="<?= View::e($edit['teacher_code'] ?? '') ?>" required></div>
        <div><label>Name</label><input name="name" value="<?= View::e($edit['name'] ?? '') ?>" required></div>
      </div>
      <div class="form-row">
        <div><label>Email</label><input name="email" value="<?= View::e($edit['email'] ?? '') ?>"></div>
        <div><label>Phone</label><input name="phone" value="<?= View::e($edit['phone'] ?? '') ?>"></div>
      </div>
      <div class="form-row">
        <div><label>Username</label><input name="username" value="<?= View::e($edit['username'] ?? '') ?>" required></div>
        <div><label>Password <?= $edit ? '(leave blank to keep)' : '' ?></label><input name="password" type="password" <?= $edit ? '' : 'required' ?>></div>
      </div>
      <button class="btn gold" type="submit">Save</button>
    </form>
  </div>
  <div class="card">
    <p class="muted">Login identity is linked to the teacher profile. Teachers can conduct classes for subjects assigned to them.</p>
  </div>
</div>
<div class="card" style="margin-top:16px">
  <table class="table">
    <thead><tr><th>Code</th><th>Name</th><th>Username</th><th>Status</th><th></th></tr></thead>
    <tbody>
    <?php foreach ($rows as $r): ?>
      <tr>
        <td><?= View::e($r['teacher_code']) ?></td>
        <td><?= View::e($r['name']) ?></td>
        <td><?= View::e($r['username']) ?></td>
        <td><?= View::e($r['user_status']) ?></td>
        <td>
          <a class="btn ghost small" href="<?= App::url('/admin/teachers/'.$r['id'].'/edit') ?>">Edit</a>
          <form style="display:inline" method="post" action="<?= App::url('/admin/teachers/'.$r['id'].'/toggle') ?>"><?= Csrf::field() ?><button class="btn small ghost"><?= $r['user_status'] === 'ACTIVE' ? 'Disable' : 'Enable' ?></button></form>
        </td>
      </tr>
    <?php endforeach; ?>
    </tbody>
  </table>
</div>
