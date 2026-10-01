<?php
use App\Core\App;
use App\Core\View;
?>
<div class="grid stats">
  <div class="card stat"><b><?= (int)$stats['conducted'] ?></b><span>Conducted</span></div>
  <div class="card stat"><b><?= (int)$stats['cancelled'] ?></b><span>Cancelled</span></div>
  <div class="card stat"><b><?= (int)$stats['late'] ?></b><span>Late starts</span></div>
  <div class="card stat"><b><?= (int)$stats['short'] ?></b><span>Short-session flags</span></div>
</div>
<div class="card" style="margin-top:16px">
  <table class="table">
    <thead><tr><th>Date</th><th>Subject</th><th>Scheduled</th><th>Actual start / end</th><th>Delay</th><th>Present</th><th>Status</th><th></th></tr></thead>
    <tbody>
    <?php foreach ($rows as $r): ?>
      <tr>
        <td><?= View::e($r['class_date']) ?></td>
        <td><?= View::e($r['subject_code'].' '.$r['subject_name']) ?></td>
        <td><?= View::e(fmt_time($r['scheduled_start']).'–'.fmt_time($r['scheduled_end'])) ?></td>
        <td><?= View::e(($r['actual_start'] ?? '—').' / '.($r['actual_end'] ?? '—')) ?></td>
        <td><?= $r['actual_start'] ? View::e(delay_label((int)$r['start_delay_seconds'])) : '—' ?></td>
        <td><?= (int)$r['present_count'] ?>/<?= (int)$r['total_marked'] ?></td>
        <td><?= status_badge($r['status']) ?></td>
        <td><a href="<?= App::url('/teacher/session/'.$r['id']) ?>">Open</a></td>
      </tr>
    <?php endforeach; ?>
    </tbody>
  </table>
</div>
