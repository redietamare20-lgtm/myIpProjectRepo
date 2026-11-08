<?php
require_once __DIR__ . '/../includes/auth.php';
$user = require_role('customer');
$st = getDB()->prepare('SELECT id, total_amount, status, created_at, updated_at FROM orders WHERE user_id = ? ORDER BY id DESC');
$st->execute([(int)$user['id']]);
$orders = $st->fetchAll();
$pageTitle = 'My orders';
include __DIR__ . '/../includes/header.php';
?>
<h1 class="page-title">My orders</h1>
<div data-live="orders" data-live-id="cust-orders">
<?php if (!$orders): ?>
  <div class="empty-state"><h3>No orders yet</h3><a class="btn btn-primary" href="<?= e(url('books.php')) ?>">Browse books</a></div>
<?php else: ?>
  <div class="table-wrap"><table class="table">
    <thead><tr><th>Order</th><th>Placed</th><th>Total</th><th>Status</th><th>Last update</th><th></th></tr></thead>
    <tbody>
    <?php foreach ($orders as $o): ?>
      <tr><td>#<?= (int)$o['id'] ?></td><td><?= e(fmt_date($o['created_at'])) ?></td><td><?= money($o['total_amount']) ?></td>
        <td><?= status_badge($o['status']) ?></td><td><?= e(fmt_date($o['updated_at'])) ?></td>
        <td><a class="btn btn-outline btn-sm" href="<?= e(url('customer/order-details.php?id=' . (int)$o['id'])) ?>">Details</a></td></tr>
    <?php endforeach; ?>
    </tbody></table></div>
<?php endif; ?>
</div>
<?php include __DIR__ . '/../includes/footer.php'; ?>
