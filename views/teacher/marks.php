<?php
use App\Core\App;
use App\Core\Csrf;
use App\Core\View;
?>
<?php if (!empty($success)): ?><div class="flash ok"><?= View::e($success) ?></div><?php endif; ?>
<?php if (!empty($error)): ?><div class="flash bad"><?= View::e($error) ?></div><?php endif; ?>

<?php if (!$internals): ?>
  <div class="card"><p class="muted">HOD has not configured any internal exams yet.</p></div>
<?php elseif (!$subjects): ?>
  <div class="card"><p class="muted">No subject assigned to you. Ask HOD to assign a subject.</p></div>
<?php else: ?>
<form method="get" action="<?= App::url('/teacher/marks') ?>" class="card">
  <label>Subject</label>
  <select name="subject_id" onchange="this.form.submit()">
    <?php foreach ($subjects as $s): ?>
      <option value="<?= (int)$s['id'] ?>" <?= (int)$s['id']===$subjectId ? 'selected' : '' ?>><?= View::e($s['subject_code'].' '.$s['subject_name']) ?></option>
    <?php endforeach; ?>
  </select>
</form>
<form method="post" action="<?= App::url('/teacher/marks') ?>" class="card" style="margin-top:16px">
  <?= Csrf::field() ?>
  <input type="hidden" name="subject_id" value="<?= (int)$subjectId ?>">
  <div class="table-wrap">
  <table class="table">
    <thead>
      <tr>
        <th>Roll</th><th>Name</th>
        <?php foreach ($internals as $ex): ?>
          <th><?= View::e($ex['name']) ?> / <?= View::e($ex['max_marks']) ?></th>
        <?php endforeach; ?>
        <th>Total</th><th>%</th>
      </tr>
    </thead>
    <tbody>
    <?php foreach ($grid as $row): $st = $row['student']; $t = $row['totals']; ?>
      <tr>
        <td><?= View::e($st['roll_no']) ?></td>
        <td><?= View::e($st['name']) ?></td>
        <?php foreach ($t['cells'] as $cell): ?>
          <td>
            <input type="number" step="0.01" min="0" max="<?= View::e($cell['exam']['max_marks']) ?>"
              name="marks[<?= (int)$st['id'] ?>][<?= (int)$cell['exam']['id'] ?>]"
              value="<?= $cell['marks'] === null ? '' : View::e($cell['marks']) ?>">
          </td>
        <?php endforeach; ?>
        <td><?= View::e($t['obtained']) ?> / <?= View::e($t['maximum']) ?></td>
        <td><?= View::e($t['percent']) ?>%</td>
      </tr>
    <?php endforeach; ?>
    </tbody>
  </table>
  </div>
  <button class="btn gold" type="submit">Save marks</button>
</form>
<?php endif; ?>
