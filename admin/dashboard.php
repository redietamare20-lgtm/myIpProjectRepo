<?php
require_once __DIR__ . '/../includes/auth.php';
$user = require_role('admin');
$pdo = getDB();
$stats = admin_stats();
$recent = $pdo->query('SELECT o.id, o.total_amount, o.status, o.created_at, u.full_name FROM orders o JOIN users u ON u.id = o.user_id ORDER BY o.id DESC LIMIT 6')->fetchAll();
$low = $pdo->query('SELECT id, title, stock FROM books WHERE stock <= ' . LOW_STOCK . ' ORDER BY stock ASC, title LIMIT 6')->fetchAll();
$logs = $pdo->query('SELECT l.action, l.details, l.created_at, u.full_name FROM activity_logs l LEFT JOIN users u ON u.id = l.user_id ORDER BY l.id DESC LIMIT 8')->fetchAll();
$layout = 'admin'; $pageTitle = 'Dashboard';
include __DIR__ . '/../includes/header.php';
?>
<div class="stat-grid" data-live="orders books" data-live-id="admin-stats">
  <div class="stat-card"><span class="stat-label">Total books</span><span class="stat-value"><?= (int)$stats['books'] ?></span></div>
  <div class="stat-card"><span class="stat-label">Customers</span><span class="stat-value"><?= (int)$stats['customers'] ?></span></div>
  <div class="stat-card"><span class="stat-label">Total orders</span><span class="stat-value"><?= (int)$stats['orders'] ?></span></div>
  <div class="stat-card"><span class="stat-label">Total sales</span><span class="stat-value"><?= money($stats['sales']) ?></span></div>
  <div class="stat-card stat-warn"><span class="stat-label">Pending orders</span><span class="stat-value"><?= (int)$stats['pending'] ?></span></div>
  <div class="stat-card stat-warn"><span class="stat-label">Low / out of stock</span><span class="stat-value"><?= (int)$stats['low_stock'] ?></span></div>
</div>
<div class="two-col">
  <section class="card" data-live="orders" data-live-id="admin-recent-orders">
    <h2>Recent orders</h2>
    <?php if (!$recent): ?><div class="empty-state"><p>No orders yet.</p></div><?php else: ?>
    <div class="table-wrap"><table class="table"><thead><tr><th>#</th><th>Customer</th><th>Total</th><th>Status</th></tr></thead><tbody>
      <?php foreach ($recent as $o): ?>
        <tr><td><a href="<?= e(url('admin/order-details.php?id=' . (int)$o['id'])) ?>">#<?= (int)$o['id'] ?></a></td><td><?= e($o['full_name']) ?></td><td><?= money($o['total_amount']) ?></td><td><?= status_badge($o['status']) ?></td></tr>
      <?php endforeach; ?></tbody></table></div>
    <?php endif; ?>
    <p><a href="<?= e(url('admin/orders.php')) ?>">Manage orders &rarr;</a></p>
  </section>
  <section class="card" data-live="books" data-live-id="admin-low-stock">
    <h2>Low stock</h2>
    <?php if (!$low): ?><div class="empty-state"><p>All books are well stocked.</p></div><?php else: ?>
    <ul class="summary-list"><?php foreach ($low as $b): ?>
      <li><a href="<?= e(url('admin/edit-book.php?id=' . (int)$b['id'])) ?>"><?= e($b['title']) ?></a><?= stock_badge((int)$b['stock']) ?></li>
    <?php endforeach; ?></ul><?php endif; ?>
    <p><a href="<?= e(url('admin/inventory.php')) ?>">Open inventory &rarr;</a></p>
  </section>
</div>
<section class="card">
  <h2>Recent activity</h2>
  <?php if (!$logs): ?><div class="empty-state"><p>No activity recorded yet.</p></div><?php else: ?>
  <div class="table-wrap"><table class="table"><thead><tr><th>When</th><th>User</th><th>Action</th><th>Details</th></tr></thead><tbody>
    <?php foreach ($logs as $l): ?>
      <tr><td><?= e(fmt_date($l['created_at'])) ?></td><td><?= e($l['full_name'] ?? 'Guest') ?></td><td><?= e($l['action']) ?></td><td><?= e($l['details'] ?? '') ?></td></tr>
    <?php endforeach; ?></tbody></table></div><?php endif; ?>
</section>
<?php include __DIR__ . '/../includes/footer.php'; ?>
