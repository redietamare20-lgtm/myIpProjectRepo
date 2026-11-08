<?php
require_once __DIR__ . '/../includes/auth.php';
$user = require_role('admin');
$pdo = getDB();
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_require();
    $id = (int)($_POST['id'] ?? 0);
    $stock = filter_var($_POST['stock'] ?? '', FILTER_VALIDATE_INT, ['options' => ['min_range' => 0, 'max_range' => 100000]]);
    if ($stock === false) {
        flash_set('error', 'Enter a valid stock quantity.');
    } else {
        $st = $pdo->prepare('UPDATE books SET stock = ? WHERE id = ?');
        $st->execute([$stock, $id]);
        log_activity((int)$user['id'], 'stock_updated', 'Book #' . $id . ' stock = ' . $stock);
        flash_set('success', 'Stock updated.');
    }
    redirect('admin/inventory.php' . (!empty($_GET['low']) ? '?low=1' : ''));
}
$onlyLow = !empty($_GET['low']);
$books = $pdo->query('SELECT id, title, isbn, stock, price FROM books ' . ($onlyLow ? 'WHERE stock <= ' . LOW_STOCK . ' ' : '') . 'ORDER BY stock ASC, title')->fetchAll();
$value = (float)$pdo->query('SELECT COALESCE(SUM(stock * price),0) FROM books')->fetchColumn();
$layout = 'admin'; $pageTitle = 'Inventory';
include __DIR__ . '/../includes/header.php';
?>
<div class="toolbar">
  <div>
    <a class="btn <?= $onlyLow ? 'btn-outline' : 'btn-primary' ?> btn-sm" href="<?= e(url('admin/inventory.php')) ?>">All books</a>
    <a class="btn <?= $onlyLow ? 'btn-primary' : 'btn-outline' ?> btn-sm" href="<?= e(url('admin/inventory.php?low=1')) ?>">Low stock (<?= LOW_STOCK ?> or less)</a>
  </div>
  <span class="muted">Stock value: <strong><?= money($value) ?></strong></span>
</div>
<div class="card" data-live="books" data-live-id="admin-inventory">
<?php if (!$books): ?><div class="empty-state"><h3>Nothing to show</h3><p>No books match this filter.</p></div><?php else: ?>
  <div class="table-wrap"><table class="table">
    <thead><tr><th>Title</th><th>ISBN</th><th>Price</th><th>Status</th><th>Set stock</th></tr></thead><tbody>
    <?php foreach ($books as $b): ?>
      <tr><td><?= e($b['title']) ?></td><td><?= e($b['isbn'] ?? '-') ?></td><td><?= money($b['price']) ?></td><td><?= stock_badge((int)$b['stock']) ?></td>
        <td><form method="post" class="inline-form"><?= csrf_field() ?><input type="hidden" name="id" value="<?= (int)$b['id'] ?>">
          <input type="number" name="stock" min="0" class="qty-input" value="<?= (int)$b['stock'] ?>" aria-label="Stock for <?= e($b['title']) ?>">
          <button class="btn btn-outline btn-sm" type="submit">Update</button></form></td></tr>
    <?php endforeach; ?></tbody></table></div>
<?php endif; ?>
</div>
<?php include __DIR__ . '/../includes/footer.php'; ?>
