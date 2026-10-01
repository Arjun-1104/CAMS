<?php
use App\Core\View;
?>
<div class="card">
  <p class="muted">Average attendance is computed from completed sessions only.</p>
  <table class="table">
    <thead><tr><th>Subject</th><th>Your conducted classes</th><th>Subject conducted</th><th>Avg attendance</th></tr></thead>
    <tbody>
    <?php foreach ($reports as $r): ?>
      <tr>
        <td><?= View::e($r['subject']['subject_code'].' · '.$r['subject']['subject_name']) ?></td>
        <td><?= (int)$r['mine'] ?></td>
        <td><?= (int)$r['stats']['conducted'] ?></td>
        <td><?= View::e($r['stats']['average']) ?>%</td>
      </tr>
    <?php endforeach; ?>
    </tbody>
  </table>
</div>
