<?php
// HOD home: counts + today's timetable status
use App\Core\View;
?>
<?php if (!empty($success)): ?><div class="flash ok"><?= View::e($success) ?></div><?php endif; ?>
<?php if (!empty($error)): ?><div class="flash bad"><?= View::e($error) ?></div><?php endif; ?>

<div class="grid stats">
  <div class="card stat"><b><?= (int)$stats['teachers'] ?></b><span>Teachers</span></div>
  <div class="card stat"><b><?= (int)$stats['students'] ?></b><span>Students</span></div>
  <div class="card stat"><b><?= (int)$stats['subjects'] ?></b><span>Subjects</span></div>
</div>

<div class="card" style="margin-top:18px">
  <h3 class="serif" style="margin-top:0">Today's teaching · <?= View::e($today) ?></h3>
  <p class="muted">Read-only overview. Teachers manage class outcomes from their dashboards.</p>
  <table class="table">
    <thead>
      <tr><th>Subject</th><th>Teacher</th><th>Status</th><th>Reason</th></tr>
    </thead>
    <tbody>
    <?php foreach ($todayRows as $row): $s = $row['slot']; ?>
      <tr>
        <td><?= View::e($s['subject_code']) ?><br><span class="muted"><?= View::e($s['subject_name']) ?></span></td>
        <td><?= View::e($row['teacher']['name'] ?? 'Unassigned') ?></td>
        <td><?= status_badge($row['status']) ?></td>
        <td><?= View::e($row['session']['skip_reason'] ?? '—') ?></td>
      </tr>
    <?php endforeach; ?>
    <?php if (!$todayRows): ?><tr><td colspan="4" class="muted">No timetable slots for <?= View::e($day) ?>.</td></tr><?php endif; ?>
    </tbody>
  </table>
</div>
