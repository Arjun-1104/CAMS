<?php
use App\Core\View;
?>
<?php if (!$internals): ?>
  <div class="card"><p class="muted">No internal exams configured yet. <a href="<?= \App\Core\App::url('/admin/internals') ?>">Manage internal exams</a>.</p></div>
<?php endif; ?>
<?php if ($internals): ?><p class="muted">View-only marks summary. <a href="<?= \App\Core\App::url('/admin/internals') ?>">Manage internal exams</a>.</p><?php endif; ?>
<?php foreach ($cards as $block): $st = $block['student']; $card = $block['card']; ?>
<div class="card" style="margin-bottom:12px">
  <strong><?= View::e($st['roll_no']) ?> · <?= View::e($st['name']) ?></strong>
  <span class="muted"> · Overall: <?= View::e($card['obtained']) ?> / <?= View::e($card['maximum']) ?> · <?= View::e($card['percent']) ?>%</span>
  <div class="table-wrap">
  <table class="table">
    <thead>
      <tr>
        <th>Subject</th>
        <?php foreach ($card['internals'] as $ex): ?><th><?= View::e($ex['name']) ?></th><?php endforeach; ?>
        <th>Total</th><th>%</th>
      </tr>
    </thead>
    <tbody>
    <?php foreach ($card['subjects'] as $row): ?>
      <tr>
        <td><?= View::e($row['subject']['subject_code']) ?></td>
        <?php foreach ($row['cells'] as $cell): ?>
          <td><?= $cell['marks'] === null ? '—' : View::e($cell['marks']) ?></td>
        <?php endforeach; ?>
        <td><?= View::e($row['obtained']) ?>/<?= View::e($row['maximum']) ?></td>
        <td><?= View::e($row['percent']) ?>%</td>
      </tr>
    <?php endforeach; ?>
    </tbody>
  </table>
  </div>
</div>
<?php endforeach; ?>
