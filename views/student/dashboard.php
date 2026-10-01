<?php
// Student home: attendance and internal marks.
use App\Core\View;
?>
<?php if (!empty($success)): ?><div class="flash ok"><?= View::e($success) ?></div><?php endif; ?>
<?php if (!empty($error)): ?><div class="flash bad"><?= View::e($error) ?></div><?php endif; ?>

<div class="grid cards-2">
  <div class="card">
    <p class="muted"><?= View::e($student['name']) ?> · <?= View::e($student['roll_no']) ?><?= $student['section'] ? ' · Section '.View::e($student['section']) : '' ?></p>
    <p class="percent"><?= View::e($stats['overall']) ?>%</p>
    <p class="muted">Overall · <?= (int)$stats['present'] ?> of <?= (int)$stats['conducted'] ?> conducted classes</p>
    <canvas id="attChart" height="120" data-labels='<?= View::e(json_encode(array_map(fn($r) => $r['subject']['subject_code'], $stats['subjects']))) ?>' data-values='<?= View::e(json_encode(array_map(fn($r) => $r['percent'], $stats['subjects']))) ?>'></canvas>
  </div>
  <div class="card">
    <h3 class="serif" style="margin-top:0">Subject-wise</h3>
    <?php foreach ($stats['subjects'] as $row): ?>
      <p style="margin-bottom:6px"><strong><?= View::e($row['subject']['subject_name']) ?></strong> <?= View::e($row['percent']) ?>%</p>
      <div class="bar"><span style="width:<?= min(100, (float)$row['percent']) ?>%"></span></div>
      <p class="hint">Attended <?= (int)$row['present'] ?> / Conducted <?= (int)$row['conducted'] ?></p>
    <?php endforeach; ?>
  </div>
</div>
<div class="card" id="internal-marks" style="margin-top:16px">
  <h3 class="serif" style="margin-top:0">Internal marks</h3>
  <?php if (!$marks['internals']): ?>
    <p class="muted">Internal marks have not been configured yet.</p>
  <?php else: ?>
    <p class="muted">Overall: <strong><?= View::e($marks['obtained']) ?> / <?= View::e($marks['maximum']) ?> (<?= View::e($marks['percent']) ?>%)</strong></p>
    <div class="table-wrap">
    <table class="table">
      <thead><tr><th>Subject</th><?php foreach ($marks['internals'] as $exam): ?><th><?= View::e($exam['name']) ?> / <?= View::e($exam['max_marks']) ?></th><?php endforeach; ?><th>Total</th><th>Percent</th></tr></thead>
      <tbody>
      <?php foreach ($marks['subjects'] as $row): ?>
        <tr>
          <td><?= View::e($row['subject']['subject_code'].' '.$row['subject']['subject_name']) ?></td>
          <?php foreach ($row['cells'] as $cell): ?><td><?= $cell['marks'] === null ? '—' : View::e($cell['marks']) ?></td><?php endforeach; ?>
          <td><?= View::e($row['obtained']) ?> / <?= View::e($row['maximum']) ?></td>
          <td><?= View::e($row['percent']) ?>%</td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
    </div>
  <?php endif; ?>
</div>
