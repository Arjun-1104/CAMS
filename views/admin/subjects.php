<?php
use App\Core\App;
use App\Core\Csrf;
use App\Core\View;
?>
<?php if (!empty($success)): ?><div class="flash ok"><?= View::e($success) ?></div><?php endif; ?>
<?php if (!empty($error)): ?><div class="flash bad"><?= View::e($error) ?></div><?php endif; ?>
<div class="card">
  <h3 class="serif" style="margin-top:0">Add or edit subject</h3>
  <p class="muted">Saving a disabled subject code re-enables its existing record. Edit active subjects with the Edit button.</p>
  <form method="post" action="<?= App::url('/admin/subjects') ?>">
    <?= Csrf::field() ?>
    <input type="hidden" name="id" id="sid">
    <div class="form-row">
      <div><label>Code</label><input name="subject_code" id="scode" placeholder="301 MCA" required></div>
      <div><label>Name</label><input name="subject_name" id="sname" required></div>
    </div>
    <button class="btn gold" type="submit">Save subject</button>
    <button class="btn ghost" type="reset" onclick="document.getElementById('sid').value=''">Clear</button>
  </form>
</div>
<div class="card" style="margin-top:16px">
  <table class="table">
    <thead><tr><th>Code</th><th>Name</th><th>Status</th><th>Actions</th></tr></thead>
    <tbody>
    <?php foreach ($rows as $r): ?>
      <tr>
        <td><?= View::e($r['subject_code']) ?></td>
        <td><?= View::e($r['subject_name']) ?></td>
        <td><?= View::e($r['status']) ?></td>
        <td>
          <button class="btn ghost small" type="button" onclick="document.getElementById('sid').value='<?= (int)$r['id'] ?>';document.getElementById('scode').value='<?= View::e($r['subject_code']) ?>';document.getElementById('sname').value='<?= View::e($r['subject_name']) ?>';window.scrollTo({top:0,behavior:'smooth'})">Edit</button>
          <form style="display:inline" method="post" action="<?= App::url('/admin/subjects/'.$r['id'].'/delete') ?>">
            <?= Csrf::field() ?>
            <button class="btn small <?= $r['status'] === 'ACTIVE' ? 'ghost' : 'gold' ?>" type="submit"><?= $r['status'] === 'ACTIVE' ? 'Disable' : 'Re-enable' ?></button>
          </form>
        </td>
      </tr>
    <?php endforeach; ?>
    <?php if (!$rows): ?><tr><td colspan="4" class="muted">No subjects yet.</td></tr><?php endif; ?>
    </tbody>
  </table>
</div>
