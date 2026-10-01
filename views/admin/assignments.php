<?php
use App\Core\App;
use App\Core\Csrf;
use App\Core\View;
?>
<?php if (!empty($success)): ?><div class="flash ok"><?= View::e($success) ?></div><?php endif; ?>
<?php if (!empty($error)): ?><div class="flash bad"><?= View::e($error) ?></div><?php endif; ?>
<div class="card">
  <h3 class="serif" style="margin-top:0">Assign teacher → subject</h3>
  <form method="post" action="<?= App::url('/admin/assignments') ?>">
    <?= Csrf::field() ?>
    <div class="form-row">
      <div>
        <label>Teacher</label>
        <select name="teacher_id"><?php foreach ($teachers as $t): ?><option value="<?= (int)$t['id'] ?>"><?= View::e($t['teacher_code'].' · '.$t['name']) ?></option><?php endforeach; ?></select>
      </div>
      <div>
        <label>Subject</label>
        <select name="subject_id"><?php foreach ($subjects as $s): ?><option value="<?= (int)$s['id'] ?>"><?= View::e($s['subject_code'].' · '.$s['subject_name']) ?></option><?php endforeach; ?></select>
      </div>
    </div>
    <button class="btn gold" type="submit">Assign</button>
  </form>
</div>
<div class="card" style="margin-top:16px">
  <table class="table">
    <thead><tr><th>Teacher</th><th>Subject</th><th>Assigned</th><th></th></tr></thead>
    <tbody>
    <?php foreach ($rows as $r): ?>
      <tr>
        <td><?= View::e($r['teacher_code'].' '.$r['teacher_name']) ?></td>
        <td><?= View::e($r['subject_code'].' · '.$r['subject_name']) ?></td>
        <td><?= View::e($r['assigned_at']) ?></td>
        <td>
          <form method="post" action="<?= App::url('/admin/assignments/'.$r['id'].'/delete') ?>"><?= Csrf::field() ?><button class="btn small bad">Remove</button></form>
        </td>
      </tr>
    <?php endforeach; ?>
    </tbody>
  </table>
</div>
