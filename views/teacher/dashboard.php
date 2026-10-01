<?php
// Teacher home: only today's assigned slots + Start class button
use App\Core\App;
use App\Core\Csrf;
use App\Core\View;
?>
<?php if (!empty($success)): ?><div class="flash ok"><?= View::e($success) ?></div><?php endif; ?>
<?php if (!empty($error)): ?><div class="flash bad"><?= View::e($error) ?></div><?php endif; ?>

<div class="grid stats">
  <div class="card stat"><b><?= (int)$stats['conducted'] ?></b><span>Classes conducted</span></div>
  <div class="card stat"><b><?= (int)$stats['ontime'] ?></b><span>On-time starts</span></div>
  <div class="card stat"><b><?= (int)$stats['late'] ?></b><span>Late starts</span></div>
  <div class="card stat"><b><?= (int)$students ?></b><span>Active students</span></div>
</div>

<div class="card" style="margin-top:16px">
  <h3 class="serif" style="margin-top:0">Today · <?= View::e($day) ?> <?= View::e($today) ?></h3>
  <p class="muted">Classes can be started at any time today. Another teacher assigned to the subject may conduct it if needed.</p>
  <?php foreach ($mine as $row): $s = $row['slot']; $sess = $row['session']; ?>
    <div class="class-row">
      <div>
        <strong><?= View::e(fmt_time($s['start_time']).' – '.fmt_time($s['end_time'])) ?></strong>
        <br><?= View::e($s['subject_code']) ?> · <?= View::e($s['subject_name']) ?>
        <?php if ($sess): ?>
          <br><span class="hint">Status: <?= View::e($sess['status']) ?><?php if ($row['actual_teacher']): ?> · Teacher: <?= View::e($row['actual_teacher']) ?><?php endif; ?></span>
          <?php if ($sess['status'] === 'NOT_CONDUCTED' && $sess['skip_reason']): ?>
            <br><span class="hint">Reason: <?= View::e($sess['skip_reason']) ?></span>
          <?php endif; ?>
        <?php else: ?>
          <br><span class="hint">Not started yet</span>
        <?php endif; ?>
      </div>
      <div>
        <?php if ($row['owns_session'] && $sess && $sess['status'] === 'ONGOING'): ?>
          <a class="btn gold" href="<?= App::url('/teacher/session/'.$sess['id']) ?>">Open live class</a>
        <?php elseif ($row['owns_session'] && $sess && $sess['status'] === 'COMPLETED'): ?>
          <a class="btn ghost" href="<?= App::url('/teacher/session/'.$sess['id']) ?>">View</a>
        <?php elseif ($row['can_start']): ?>
          <form method="post" action="<?= App::url('/teacher/start/'.$s['id']) ?>">
            <?= Csrf::field() ?>
            <button class="btn gold" type="submit">Start class</button>
          </form>
          <form method="post" action="<?= App::url('/teacher/not-conducted/'.$s['id']) ?>" class="reason-form">
            <?= Csrf::field() ?>
            <label>Not taking this class? Tell HOD why</label>
            <input name="reason" maxlength="255" placeholder="Reason (up to 255 characters)" required>
            <button class="btn ghost small" type="submit">Submit reason</button>
          </form>
        <?php else: ?>
          <span class="badge b-wait"><?= View::e($sess['status'] ?? $row['start_reason']) ?></span>
        <?php endif; ?>
      </div>
    </div>
  <?php endforeach; ?>
  <?php if (!$mine): ?><p class="muted">No class assigned to you today.</p><?php endif; ?>
</div>
