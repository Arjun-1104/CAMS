<?php
use App\Core\View;
?>
<div class="card">
  <p class="muted">Class starts, attendance changes, marks updates, and logins are recorded for review.</p>
  <table class="table">
    <thead><tr><th>Time</th><th>User</th><th>Action</th><th>Details</th><th>IP</th></tr></thead>
    <tbody>
    <?php foreach ($rows as $r): ?>
      <tr>
        <td><?= View::e($r['created_at']) ?></td>
        <td><?= View::e(($r['username'] ?? 'system').' '.($r['role'] ?? '')) ?></td>
        <td><?= View::e($r['action']) ?></td>
        <td class="hint"><?= View::e($r['details']) ?></td>
        <td><?= View::e($r['ip_address']) ?></td>
      </tr>
    <?php endforeach; ?>
    </tbody>
  </table>
</div>
