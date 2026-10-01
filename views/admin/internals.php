<?php
use App\Core\App;
use App\Core\Csrf;
use App\Core\View;
?>
<?php if (!empty($success)): ?><div class="flash ok"><?= View::e($success) ?></div><?php endif; ?>
<?php if (!empty($error)): ?><div class="flash bad"><?= View::e($error) ?></div><?php endif; ?>
<div class="card">
  <h3 class="serif" style="margin-top:0">Configure internal exams</h3>
  <p class="muted">The number of internal exams and each exam's maximum marks are set here by the HOD.</p>
  <form method="post" action="<?= App::url('/admin/internals') ?>">
    <?= Csrf::field() ?>
    <input type="hidden" name="id" id="internal-id">
    <div class="form-row">
      <div><label>Name</label><input name="name" id="internal-name" required maxlength="80" placeholder="Internal 1"></div>
      <div><label>Maximum marks</label><input type="number" name="max_marks" id="internal-max" min="0.01" max="9999" step="0.01" required></div>
      <div><label>Display order</label><input type="number" name="sort_order" id="internal-order" min="1" value="<?= count($rows) + 1 ?>" required></div>
    </div>
    <button class="btn gold" type="submit">Save internal</button>
    <button class="btn ghost" type="reset" onclick="document.getElementById('internal-id').value=''">Clear</button>
  </form>
</div>
<div class="card" style="margin-top:16px">
  <table class="table">
    <thead><tr><th>Name</th><th>Maximum marks</th><th>Order</th><th>Actions</th></tr></thead>
    <tbody>
    <?php foreach ($rows as $row): ?>
      <tr>
        <td><?= View::e($row['name']) ?></td>
        <td><?= View::e($row['max_marks']) ?></td>
        <td><?= (int)$row['sort_order'] ?></td>
        <td>
          <button class="btn ghost small" type="button" onclick="document.getElementById('internal-id').value='<?= (int)$row['id'] ?>';document.getElementById('internal-name').value='<?= View::e($row['name']) ?>';document.getElementById('internal-max').value='<?= View::e($row['max_marks']) ?>';document.getElementById('internal-order').value='<?= (int)$row['sort_order'] ?>'">Edit</button>
          <form style="display:inline" method="post" action="<?= App::url('/admin/internals/'.$row['id'].'/delete') ?>">
            <?= Csrf::field() ?>
            <button class="btn bad small" type="submit" onclick="return confirm('Remove this internal and its marks?')">Delete</button>
          </form>
        </td>
      </tr>
    <?php endforeach; ?>
    <?php if (!$rows): ?><tr><td colspan="4" class="muted">No internal exams configured.</td></tr><?php endif; ?>
    </tbody>
  </table>
</div>
