<?php
require_once __DIR__ . '/../includes/auth.php';
$user = require_role('admin');
$id = (int)($_GET['id'] ?? 0);
$pdo = getDB();
$st = $pdo->prepare('SELECT o.*, u.full_name, u.email, p.status AS pay_status FROM orders o JOIN users u ON u.id = o.user_id LEFT JOIN payments p ON p.order_id = o.id WHERE o.id = ?');
$st->execute([$id]);
$order = $st->fetch();
if (!$order) { flash_set('error', 'Order not found.'); redirect('admin/orders.php'); }
$st = $pdo->prepare('SELECT book_title, quantity, unit_price FROM order_items WHERE order_id = ?');
$st->execute([$id]);
$items = $st->fetchAll();
$layout = 'admin'; $pageTitle = 'Order #' . $order['id'];
include __DIR__ . '/../includes/header.php';
?>
<p><a href="<?= e(url('admin/orders.php')) ?>">&larr; All orders</a></p>
<div data-live="orders" data-live-id="admin-order-detail">
  <div class="two-col">
    <section class="card">
      <h2>Order #<?= (int)$order['id'] ?> <?= status_badge($order['status']) ?></h2>
      <p><strong>Customer:</strong> <a href="<?= e(url('admin/customers.php?id=' . (int)$order['user_id'])) ?>"><?= e($order['full_name']) ?></a> (<?= e($order['email']) ?>)</p>
      <p><strong>Placed:</strong> <?= e(fmt_date($order['created_at'])) ?></p>
      <p><strong>Address:</strong> <?= e($order['shipping_address']) ?></p>
      <p><strong>Phone:</strong> <?= e($order['phone']) ?></p>
      <?php if ($order['notes']): ?><p><strong>Notes:</strong> <?= e($order['notes']) ?></p><?php endif; ?>
      <p><strong>Payment:</strong> Cash on delivery (<?= e($order['pay_status'] ?? 'pending') ?>)</p>
      <?php if (in_array($order['status'], FINAL_STATUSES, true)): ?>
        <p class="muted">This order is final and its status can no longer be changed.</p>
      <?php else: ?>
        <div class="status-form">
          <select id="status-select" aria-label="New status">
            <?php foreach (ORDER_STATUSES as $s): if ($s === 'pending') continue; ?>
              <option value="<?= e($s) ?>" <?= $s === $order['status'] ? 'selected' : '' ?>><?= e(ucfirst($s)) ?></option>
            <?php endforeach; ?>
          </select>
          <button type="button" class="btn btn-primary js-order-status" data-id="<?= (int)$order['id'] ?>" data-select="#status-select">Update status</button>
        </div>
      <?php endif; ?>
    </section>
    <section class="card">
      <h2>Items</h2>
      <div class="table-wrap"><table class="table"><thead><tr><th>Book</th><th>Price</th><th>Qty</th><th>Subtotal</th></tr></thead><tbody>
        <?php foreach ($items as $i): ?>
          <tr><td><?= e($i['book_title']) ?></td><td><?= money($i['unit_price']) ?></td><td><?= (int)$i['quantity'] ?></td><td><?= money((float)$i['unit_price'] * (int)$i['quantity']) ?></td></tr>
        <?php endforeach; ?></tbody>
        <tfoot><tr><th colspan="3">Total</th><th><?= money($order['total_amount']) ?></th></tr></tfoot></table></div>
    </section>
  </div>
</div>
<?php include __DIR__ . '/../includes/footer.php'; ?>
