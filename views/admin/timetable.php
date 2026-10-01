<?php
use App\Core\App;
use App\Core\Csrf;
use App\Core\View;
?>
<?php if (!empty($success)): ?><div class="flash ok"><?= View::e($success) ?></div><?php endif; ?>
<div class="card">
  <h3 class="serif" style="margin-top:0">Create timetable slot</h3>
  <p class="muted">Do not hard-code papers. HOD owns the weekly map of day + time + subject + assigned teacher.</p>
  <form method="post" action="<?= App::url('/admin/timetable') ?>">
    <?= Csrf::field() ?>
    <div class="form-row">
      <div><label>Day</label>
        <select name="day"><?php foreach ($days as $d): ?><option><?= $d ?></option><?php endforeach; ?></select>
      </div>
      <div><label>Subject</label>
        <select name="subject_id"><?php foreach ($subjects as $s): ?><option value="<?= (int)$s['id'] ?>"><?= View::e($s['subject_code'].' '.$s['subject_name']) ?></option><?php endforeach; ?></select>
      </div>
      <div><label>Teacher</label>
        <select name="teacher_id"><?php foreach ($teachers as $t): ?><option value="<?= (int)$t['id'] ?>"><?= View::e($t['teacher_code'].' '.$t['name']) ?></option><?php endforeach; ?></select>
      </div>
    </div>
    <div class="form-row">
      <div><label>Scheduled start</label><input type="time" name="start_time" required></div>
      <div><label>Scheduled end</label><input type="time" name="end_time" required></div>
    </div>
    <button class="btn gold" type="submit">Add slot</button>
  </form>
</div>
<?php foreach ($days as $d): ?>
<div class="card" style="margin-top:16px">
  <h3 class="serif" style="margin-top:0"><?= View::e($d) ?></h3>
  <table class="table">
    <thead><tr><th>Scheduled time</th><th>Subject</th><th>Teacher</th><th></th></tr></thead>
    <tbody>
    <?php foreach (($grid[$d] ?? []) as $s): ?>
      <tr>
        <td><?= View::e(substr($s['start_time'],0,5).'–'.substr($s['end_time'],0,5)) ?></td>
        <td><?= View::e($s['subject_code'].' · '.$s['subject_name']) ?></td>
        <td><?= View::e($s['teacher_code'].' '.$s['teacher_name']) ?></td>
        <td><form method="post" action="<?= App::url('/admin/timetable/'.$s['id'].'/delete') ?>"><?= Csrf::field() ?><button class="btn small ghost">Disable</button></form></td>
      </tr>
    <?php endforeach; ?>
    <?php if (empty($grid[$d])): ?><tr><td colspan="4" class="muted">No slots</td></tr><?php endif; ?>
    </tbody>
  </table>
</div>
<?php endforeach; ?>
