<?php
use App\Core\View;
?>
<div class="card">
  <table class="table">
    <thead><tr><th>Date</th><th>Subject</th><th>Teacher</th><th>Time</th><th>Status</th></tr></thead>
    <tbody>
    <?php foreach ($rows as $r): ?>
      <tr>
        <td><?= View::e($r['class_date']) ?></td>
        <td><?= View::e($r['subject_code'].' '.$r['subject_name']) ?></td>
        <td><?= View::e($r['teacher_name']) ?></td>
        <td><?= View::e(fmt_time($r['scheduled_start']).'–'.fmt_time($r['scheduled_end'])) ?></td>
        <td><?= status_badge($r['att_status'] ?: ($r['session_status'] === 'ONGOING' ? 'SCHEDULED' : 'ABSENT')) ?></td>
      </tr>
    <?php endforeach; ?>
    </tbody>
  </table>
</div>
