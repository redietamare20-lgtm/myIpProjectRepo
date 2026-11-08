<?php
require_once __DIR__ . '/../includes/auth.php';
$user = require_role('admin');
$pdo = getDB();
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_require();
    $id = (int)($_POST['id'] ?? 0);
    $new = ($_POST['action'] ?? '') === 'block' ? 'blocked' : 'active';
    $st = $pdo->prepare("UPDATE users SET status = ? WHERE id = ? AND role = 'customer'");
    $st->execute([$new, $id]);
    log_activity((int)$user['id'], 'customer_' . $new, '#' . $id);
    flash_set('success', 'Customer account is now ' . $new . '.');
    redirect('admin/customers.php' . (!empty($_POST['back_to_detail']) ? '?id=' . $id : ''));
}
$detailId = (int)($_GET['id'] ?? 0);
$layout = 'admin';
if ($detailId) {
    $st = $pdo->prepare("SELECT * FROM users WHERE id = ? AND role = 'customer'"); $st->execute([$detailId]);
    $c = $st->fetch();
    if (!$c) { flash_set('error', 'Customer not found.'); redirect('admin/customers.php'); }
    $st = $pdo->prepare('SELECT id, total_amount, status, created_at FROM orders WHERE user_id = ? ORDER BY id DESC'); $st->execute([$detailId]);
    $orders = $st->fetchAll();
    $pageTitle = 'Customer details';
    include __DIR__ . '/../includes/header.php';
?>
<p><a href="<?= e(url('admin/customers.php')) ?>">&larr; All customers</a></p>
<div class="two-col">
  <section class="card">
    <h2><?= e($c['full_name']) ?> <span class="badge badge-<?= $c['status'] === 'active' ? 'delivered' : 'rejected' ?>"><?= e(ucfirst($c['status'])) ?></span></h2>
    <p><strong>Email:</strong> <?= e($c['email']) ?></p>
    <p><strong>Phone:</strong> <?= e($c['phone'] ?? '-') ?></p>
    <p><strong>Address:</strong> <?= e($c['address'] ?? '-') ?></p>
    <p><strong>Registered:</strong> <?= e(fmt_date($c['created_at'])) ?></p>
    <form method="post" data-confirm="Change this account's status?">
      <?= csrf_field() ?><input type="hidden" name="id" value="<?= (int)$c['id'] ?>"><input type="hidden" name="back_to_detail" value="1">
      <input type="hidden" name="action" value="<?= $c['status'] === 'active' ? 'block' : 'unblock' ?>">
      <button class="btn <?= $c['status'] === 'active' ? 'btn-danger' : 'btn-primary' ?>" type="submit"><?= $c['status'] === 'active' ? 'Block account' : 'Unblock account' ?></button>
    </form>
  </section>
  <section class="card" data-live="orders" data-live-id="cust-detail-orders">
    <h2>Orders</h2>
    <?php if (!$orders): ?><div class="empty-state"><p>No orders yet.</p></div><?php else: ?>
    <div class="table-wrap"><table class="table"><thead><tr><th>#</th><th>Date</th><th>Total</th><th>Status</th></tr></thead><tbody>
      <?php foreach ($orders as $o): ?>
        <tr><td><a href="<?= e(url('admin/order-details.php?id=' . (int)$o['id'])) ?>">#<?= (int)$o['id'] ?></a></td><td><?= e(fmt_date($o['created_at'])) ?></td><td><?= money($o['total_amount']) ?></td><td><?= status_badge($o['status']) ?></td></tr>
      <?php endforeach; ?></tbody></table></div><?php endif; ?>
  </section>
</div>
<?php
    include __DIR__ . '/../includes/footer.php';
    exit;
}
$q = clean_str($_GET['q'] ?? '', 100);
$like = '%' . addcslashes($q, '%_\\') . '%';
$st = $pdo->prepare("SELECT u.*, COUNT(o.id) AS orders_count,
    COALESCE(SUM(CASE WHEN o.status IN (" . SALES_STATUSES . ") THEN o.total_amount END),0) AS spent
    FROM users u LEFT JOIN orders o ON o.user_id = u.id
    WHERE u.role = 'customer' AND (u.full_name LIKE ? OR u.email LIKE ?)
    GROUP BY u.id ORDER BY u.id DESC");
$st->execute([$like, $like]);
$customers = $st->fetchAll();
$pageTitle = 'Customers';
include __DIR__ . '/../includes/header.php';
?>
<div class="toolbar"><form method="get" class="inline-form"><input type="search" name="q" value="<?= e($q) ?>" placeholder="Search name or email" maxlength="100"><button class="btn btn-outline" type="submit">Search</button></form></div>
<div class="card" data-live="orders" data-live-id="admin-customers">
<?php if (!$customers): ?><div class="empty-state"><h3>No customers found</h3></div><?php else: ?>
  <div class="table-wrap"><table class="table">
    <thead><tr><th>Name</th><th>Email</th><th>Phone</th><th>Orders</th><th>Spent</th><th>Status</th><th>Joined</th><th>Actions</th></tr></thead><tbody>
    <?php foreach ($customers as $c): ?>
      <tr><td><?= e($c['full_name']) ?></td><td><?= e($c['email']) ?></td><td><?= e($c['phone'] ?? '-') ?></td>
        <td><?= (int)$c['orders_count'] ?></td><td><?= money($c['spent']) ?></td>
        <td><span class="badge badge-<?= $c['status'] === 'active' ? 'delivered' : 'rejected' ?>"><?= e(ucfirst($c['status'])) ?></span></td>
        <td><?= e(fmt_date($c['created_at'])) ?></td>
        <td class="actions"><a class="btn btn-outline btn-sm" href="<?= e(url('admin/customers.php?id=' . (int)$c['id'])) ?>">View</a>
          <form method="post" class="inline-form" data-confirm="Change this account's status?">
            <?= csrf_field() ?><input type="hidden" name="id" value="<?= (int)$c['id'] ?>">
            <input type="hidden" name="action" value="<?= $c['status'] === 'active' ? 'block' : 'unblock' ?>">
            <button class="btn btn-sm <?= $c['status'] === 'active' ? 'btn-danger' : 'btn-primary' ?>" type="submit"><?= $c['status'] === 'active' ? 'Block' : 'Unblock' ?></button></form></td></tr>
    <?php endforeach; ?></tbody></table></div>
<?php endif; ?>
</div>
<?php include __DIR__ . '/../includes/footer.php'; ?>
