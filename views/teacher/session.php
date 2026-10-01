<?php
use App\Core\App;
use App\Core\Csrf;
use App\Core\View;
$absent = max(0, count($students) - (int)$present);
?>
<?php if (!empty($success)): ?><div class="flash ok"><?= View::e($success) ?></div><?php endif; ?>
<?php if (!empty($error)): ?><div class="flash bad"><?= View::e($error) ?></div><?php endif; ?>

<div class="card">
  <span class="pill"><?= View::e($subject['subject_code']) ?></span>
  <h2 class="serif"><?= View::e($subject['subject_name']) ?></h2>
  <p>Scheduled <?= View::e(fmt_time($session['scheduled_start'])) ?>–<?= View::e(fmt_time($session['scheduled_end'])) ?></p>
  <p>Started: <strong><?= View::e($session['actual_start'] ?? '—') ?></strong></p>
  <?php if ($session['actual_end']): ?><p>Ended: <strong><?= View::e($session['actual_end']) ?></strong></p><?php endif; ?>
  <p>Present <?= (int)$present ?> · Remaining <?= (int)$absent ?> · <?= status_badge($session['status']) ?></p>
  <?php if ($session['status'] === 'ONGOING'): ?>
    <form method="post" action="<?= App::url('/teacher/session/'.$session['id'].'/end') ?>" onsubmit="return confirm('End this class? Remaining students will be marked absent.')">
      <?= Csrf::field() ?>
      <button class="btn bad" type="submit">End class</button>
    </form>
  <?php endif; ?>
</div>

<div class="card" style="margin-top:16px">
  <h3 class="serif" style="margin-top:0">Manual attendance</h3>
  <p class="muted">Choose Present or Absent for each student. Unmarked students are recorded absent when you end class. You can correct entries later.</p>
  <div class="table-wrap">
  <table class="table">
    <thead><tr><th>Roll</th><th>Student</th><th>Status</th><th>Actions</th></tr></thead>
    <tbody>
    <?php foreach ($students as $st): ?>
      <tr>
        <td><?= View::e($st['roll_no']) ?></td>
        <td><?= View::e($st['name']) ?></td>
        <td><?= status_badge($st['att_status'] ?: 'PENDING') ?></td>
        <td class="actions">
          <form method="post" action="<?= App::url('/teacher/session/'.$session['id'].'/attendance') ?>">
            <?= Csrf::field() ?>
            <input type="hidden" name="student_id" value="<?= (int)$st['id'] ?>">
            <input type="hidden" name="status" value="PRESENT">
            <button class="btn small ok" type="submit">Present</button>
          </form>
          <form method="post" action="<?= App::url('/teacher/session/'.$session['id'].'/attendance') ?>">
            <?= Csrf::field() ?>
            <input type="hidden" name="student_id" value="<?= (int)$st['id'] ?>">
            <input type="hidden" name="status" value="ABSENT">
            <button class="btn small ghost" type="submit">Absent</button>
          </form>
        </td>
      </tr>
    <?php endforeach; ?>
    </tbody>
  </table>
  </div>
</div>
