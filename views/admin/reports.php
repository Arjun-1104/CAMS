<?php
use App\Core\View;
?>
<div class="grid cards-2">
  <div class="card">
    <h3 class="serif" style="margin-top:0">Teacher report</h3>
    <p class="muted">Counts come from <code>class_sessions</code>, not from timetable rows.</p>
    <table class="table">
      <thead><tr><th>Teacher</th><th>Conducted</th><th>On time</th><th>Late</th><th>Cancelled</th><th>Short</th></tr></thead>
      <tbody>
      <?php foreach ($teacherReports as $r): ?>
        <tr>
          <td><?= View::e($r['teacher']['teacher_code'].' '.$r['teacher']['name']) ?></td>
          <td><?= (int)$r['stats']['conducted'] ?></td>
          <td><?= (int)$r['stats']['ontime'] ?></td>
          <td><?= (int)$r['stats']['late'] ?></td>
          <td><?= (int)$r['stats']['cancelled'] ?></td>
          <td><?= (int)$r['stats']['short'] ?></td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
  </div>
  <div class="card">
    <h3 class="serif" style="margin-top:0">Subject report</h3>
    <table class="table">
      <thead><tr><th>Subject</th><th>Conducted</th><th>Avg attendance</th></tr></thead>
      <tbody>
      <?php foreach ($subjectReports as $r): ?>
        <tr>
          <td><?= View::e($r['subject']['subject_code'].' '.$r['subject']['subject_name']) ?></td>
          <td><?= (int)$r['stats']['conducted'] ?></td>
          <td><?= View::e($r['stats']['average']) ?>%</td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>
<div class="card" style="margin-top:16px">
  <h3 class="serif" style="margin-top:0">Student overall %</h3>
  <table class="table">
    <thead><tr><th>Roll</th><th>Name</th><th>Overall</th><th>By subject</th></tr></thead>
    <tbody>
    <?php foreach ($studentReports as $r): ?>
      <tr>
        <td><?= View::e($r['student']['roll_no']) ?></td>
        <td><?= View::e($r['student']['name']) ?></td>
        <td><strong><?= View::e($r['overall']) ?>%</strong></td>
        <td>
          <?php foreach ($r['subjects'] as $sub): ?>
            <span class="hint"><?= View::e($sub['subject']['subject_code']) ?> <?= View::e($sub['percent']) ?>% · </span>
          <?php endforeach; ?>
        </td>
      </tr>
    <?php endforeach; ?>
    </tbody>
  </table>
</div>
<div class="card" style="margin-top:16px">
  <h3 class="serif" style="margin-top:0">Recent sessions</h3>
  <table class="table">
    <thead><tr><th>Date</th><th>Subject</th><th>Teacher</th><th>Scheduled</th><th>Actual start</th><th>Delay</th><th>Flags</th><th>Status</th></tr></thead>
    <tbody>
    <?php foreach ($sessions as $cs): ?>
      <tr>
        <td><?= View::e($cs['class_date']) ?></td>
        <td><?= View::e($cs['subject_code']) ?></td>
        <td><?= View::e($cs['teacher_name']) ?></td>
        <td><?= View::e(fmt_time($cs['scheduled_start'])) ?></td>
        <td><?= View::e($cs['actual_start'] ?? '—') ?></td>
        <td><?= $cs['actual_start'] ? View::e(delay_label((int)$cs['start_delay_seconds'])) : '—' ?></td>
        <td>
          <?php if ($cs['is_late_start']): ?><span class="badge b-late">Late start</span><?php endif; ?>
          <?php if ($cs['is_short_session']): ?><span class="badge b-off">Short session</span><?php endif; ?>
        </td>
        <td><?= status_badge($cs['status']) ?></td>
      </tr>
    <?php endforeach; ?>
    </tbody>
  </table>
</div>
