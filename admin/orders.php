<?php
require_once __DIR__ . '/../includes/auth.php';
$user = require_role('admin');
$status = in_array($_GET['status'] ?? '', ORDER_STATUSES, true) ? $_GET['status'] : '';
$sql = 'SELECT o.id, o.total_amount, o.status, o.created_at, u.full_name FROM orders o JOIN users u ON u.id = o.user_id';
$p = [];
if ($status) { $sql .= ' WHERE o.status = ?'; $p[] = $status; }
$st = getDB()->prepare($sql . ' ORDER BY o.id DESC LIMIT 200');
$st->execute($p);
$orders = $st->fetchAll();
$layout = 'admin'; $pageTitle = 'Orders';
include __DIR__ . '/../includes/header.php';
?>
<div class="toolbar">
  <form method="get" class="inline-form">
    <select name="status" aria-label="Filter by status"><option value="">All statuses</option>
      <?php foreach (ORDER_STATUSES as $s): ?><option value="<?= e($s) ?>" <?= $status === $s ? 'selected' : '' ?>><?= e(ucfirst($s)) ?></option><?php endforeach; ?></select>
    <button class="btn btn-outline" type="submit">Filter</button>
  </form>
</div>
<div class="card" data-live="orders" data-live-id="admin-orders">
<?php if (!$orders): ?><div class="empty-state"><h3>No orders found</h3></div><?php else: ?>
  <div class="table-wrap"><table class="table">
    <thead><tr><th>#</th><th>Customer</th><th>Date</th><th>Total</th><th>Status</th><th>Actions</th></tr></thead><tbody>
    <?php foreach ($orders as $o): ?>
      <tr><td>#<?= (int)$o['id'] ?></td><td><?= e($o['full_name']) ?></td><td><?= e(fmt_date($o['created_at'])) ?></td><td><?= money($o['total_amount']) ?></td><td><?= status_badge($o['status']) ?></td>
        <td class="actions">
          <a class="btn btn-outline btn-sm" href="<?= e(url('admin/order-details.php?id=' . (int)$o['id'])) ?>">View</a>
          <?php if ($o['status'] === 'pending'): ?>
            <button type="button" class="btn btn-primary btn-sm js-order-status" data-id="<?= (int)$o['id'] ?>" data-status="approved">Approve</button>
            <button type="button" class="btn btn-danger btn-sm js-order-status" data-id="<?= (int)$o['id'] ?>" data-status="rejected" data-confirm="Reject this order? Stock will be returned.">Reject</button>
          <?php endif; ?></td></tr>
    <?php endforeach; ?></tbody></table></div>
<?php endif; ?>
</div>
<?php include __DIR__ . '/../includes/footer.php'; ?>
