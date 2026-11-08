<?php
require_once __DIR__ . '/../includes/auth.php';
$user = require_role('customer');
$uid = (int)$user['id'];
$pdo = getDB();
$st = $pdo->prepare("SELECT COUNT(*) AS total, COALESCE(SUM(status='pending'),0) AS pending,
    COALESCE(SUM(CASE WHEN status IN (" . SALES_STATUSES . ") THEN total_amount ELSE 0 END),0) AS spent FROM orders WHERE user_id = ?");
$st->execute([$uid]);
$stats = $st->fetch();
$st = $pdo->prepare('SELECT id, total_amount, status, created_at FROM orders WHERE user_id = ? ORDER BY id DESC LIMIT 5');
$st->execute([$uid]);
$recent = $st->fetchAll();
$notifs = notifications_list('customer', $uid, 5);
$pageTitle = 'My dashboard';
include __DIR__ . '/../includes/header.php';
?>
<h1 class="page-title">Hello, <?= e($user['full_name']) ?></h1>
<div class="stat-grid" data-live="orders notifs" data-live-id="cust-stats">
  <div class="stat-card"><span class="stat-label">Total orders</span><span class="stat-value"><?= (int)$stats['total'] ?></span></div>
  <div class="stat-card"><span class="stat-label">Pending orders</span><span class="stat-value"><?= (int)$stats['pending'] ?></span></div>
  <div class="stat-card"><span class="stat-label">Total spent</span><span class="stat-value"><?= money($stats['spent']) ?></span></div>
  <div class="stat-card"><span class="stat-label">Unread notifications</span><span class="stat-value"><?= notifications_unread('customer', $uid) ?></span></div>
</div>
<div class="two-col">
  <section class="card" data-live="orders" data-live-id="cust-recent-orders">
    <h2>Recent orders</h2>
    <?php if (!$recent): ?>
      <div class="empty-state"><p>You have not placed any orders yet.</p><a class="btn btn-primary" href="<?= e(url('books.php')) ?>">Browse books</a></div>
    <?php else: ?>
      <div class="table-wrap"><table class="table">
        <thead><tr><th>Order</th><th>Date</th><th>Total</th><th>Status</th></tr></thead>
        <tbody>
        <?php foreach ($recent as $o): ?>
          <tr><td><a href="<?= e(url('customer/order-details.php?id=' . (int)$o['id'])) ?>">#<?= (int)$o['id'] ?></a></td>
          <td><?= e(fmt_date($o['created_at'])) ?></td><td><?= money($o['total_amount']) ?></td><td><?= status_badge($o['status']) ?></td></tr>
        <?php endforeach; ?>
        </tbody></table></div>
      <p><a href="<?= e(url('customer/orders.php')) ?>">All orders &rarr;</a></p>
    <?php endif; ?>
  </section>
  <section class="card" data-live="notifs" data-live-id="cust-recent-notifs">
    <h2>Latest notifications</h2>
    <?php if (!$notifs): ?><div class="empty-state"><p>No notifications yet.</p></div>
    <?php else: ?><ul class="notif-list">
      <?php foreach ($notifs as $n): ?>
        <li class="<?= $n['is_read'] ? '' : 'unread' ?>"><?= e($n['message']) ?><small><?= e(fmt_date($n['created_at'])) ?></small></li>
      <?php endforeach; ?></ul>
      <p><a href="<?= e(url('customer/notifications.php')) ?>">All notifications &rarr;</a></p>
    <?php endif; ?>
  </section>
</div>
<?php include __DIR__ . '/../includes/footer.php'; ?>
