<?php
require_once __DIR__ . '/../includes/auth.php';
$user = require_role('customer');
$id = (int)($_GET['id'] ?? 0);
$pdo = getDB();
$st = $pdo->prepare('SELECT o.*, p.method AS pay_method, p.status AS pay_status FROM orders o LEFT JOIN payments p ON p.order_id = o.id WHERE o.id = ? AND o.user_id = ?');
$st->execute([$id, (int)$user['id']]);
$order = $st->fetch();
$items = [];
if ($order) {
    $st = $pdo->prepare('SELECT book_title, quantity, unit_price FROM order_items WHERE order_id = ?');
    $st->execute([$id]);
    $items = $st->fetchAll();
}
$pageTitle = $order ? 'Order #' . $order['id'] : 'Order not found';
include __DIR__ . '/../includes/header.php';
?>
<p><a href="<?= e(url('customer/orders.php')) ?>">&larr; My orders</a></p>
<div data-live="orders" data-live-id="cust-order-detail">
<?php if (!$order): ?>
  <div class="empty-state"><h3>Order not found</h3></div>
<?php else: ?>
  <h1 class="page-title">Order #<?= (int)$order['id'] ?> <?= status_badge($order['status']) ?></h1>
  <div class="two-col">
    <section class="card">
      <h2>Items</h2>
      <div class="table-wrap"><table class="table">
        <thead><tr><th>Book</th><th>Price</th><th>Qty</th><th>Subtotal</th></tr></thead><tbody>
        <?php foreach ($items as $i): ?>
          <tr><td><?= e($i['book_title']) ?></td><td><?= money($i['unit_price']) ?></td><td><?= (int)$i['quantity'] ?></td><td><?= money((float)$i['unit_price'] * (int)$i['quantity']) ?></td></tr>
        <?php endforeach; ?>
        </tbody><tfoot><tr><th colspan="3">Total</th><th><?= money($order['total_amount']) ?></th></tr></tfoot></table></div>
    </section>
    <section class="card">
      <h2>Details</h2>
      <p><strong>Placed:</strong> <?= e(fmt_date($order['created_at'])) ?></p>
      <p><strong>Last update:</strong> <?= e(fmt_date($order['updated_at'])) ?></p>
      <p><strong>Address:</strong> <?= e($order['shipping_address']) ?></p>
      <p><strong>Phone:</strong> <?= e($order['phone']) ?></p>
      <?php if ($order['notes']): ?><p><strong>Notes:</strong> <?= e($order['notes']) ?></p><?php endif; ?>
      <p><strong>Payment:</strong> Cash on delivery (<?= e($order['pay_status'] ?? 'pending') ?>)</p>
      <?php
        $flow = ['pending', 'approved', 'processing', 'shipped', 'delivered'];
        $cur = array_search($order['status'], $flow, true);
      ?>
      <?php if ($cur !== false): ?>
        <ol class="timeline">
          <?php foreach ($flow as $idx => $s): ?><li class="<?= $idx <= $cur ? 'done' : '' ?>"><?= e(ucfirst($s)) ?></li><?php endforeach; ?>
        </ol>
      <?php endif; ?>
      <?php if ($order['status'] === 'pending'): ?>
        <button type="button" class="btn btn-danger js-cancel-order" data-id="<?= (int)$order['id'] ?>">Cancel order</button>
      <?php endif; ?>
    </section>
  </div>
<?php endif; ?>
</div>
<?php include __DIR__ . '/../includes/footer.php'; ?>
