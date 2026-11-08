<?php
require_once __DIR__ . '/../includes/auth.php';
$user = require_role('admin');
$pdo = getDB();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_require();
    $paymentId = filter_var($_POST['payment_id'] ?? '', FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
    $newStatus = (string)($_POST['status'] ?? '');
    if ($paymentId === false || !in_array($newStatus, ['pending', 'paid', 'failed'], true)) {
        flash_set('error', 'Choose a valid payment and status.');
        redirect('admin/payments.php');
    }

    $st = $pdo->prepare('SELECT p.id, p.status, p.order_id, o.status AS order_status
        FROM payments p JOIN orders o ON o.id = p.order_id WHERE p.id = ?');
    $st->execute([$paymentId]);
    $payment = $st->fetch();
    if (!$payment) {
        flash_set('error', 'Payment not found.');
        redirect('admin/payments.php');
    }

    $completedOrder = in_array($payment['order_status'], ['shipped', 'delivered'], true);
    $cancelledOrder = in_array($payment['order_status'], ['rejected', 'cancelled'], true);
    $validTransition = $newStatus === 'paid' ? $completedOrder
        : ($newStatus === 'failed' ? $cancelledOrder : !$completedOrder && !$cancelledOrder);
    if (!$validTransition) {
        flash_set('error', 'Payment status must match the order status. Mark shipped or delivered orders paid, and rejected or cancelled orders failed.');
        redirect('admin/payments.php');
    }
    if ($payment['status'] === $newStatus) {
        flash_set('warning', 'That payment already has the selected status.');
        redirect('admin/payments.php');
    }

    $paidAt = $newStatus === 'paid' ? date('Y-m-d H:i:s') : null;
    $pdo->prepare('UPDATE payments SET status = ?, paid_at = ? WHERE id = ?')->execute([$newStatus, $paidAt, $paymentId]);
    log_activity((int)$user['id'], 'payment_' . $newStatus, 'Payment #' . $paymentId . ' for order #' . (int)$payment['order_id']);
    flash_set('success', 'Payment status updated.');
    redirect('admin/payments.php');
}

$status = in_array($_GET['status'] ?? '', ['pending', 'paid', 'failed'], true) ? $_GET['status'] : '';
$sql = 'SELECT p.id, p.order_id, p.method, p.status, p.amount, p.paid_at, p.created_at,
               o.status AS order_status, u.full_name
        FROM payments p
        JOIN orders o ON o.id = p.order_id
        JOIN users u ON u.id = o.user_id';
$params = [];
if ($status !== '') {
    $sql .= ' WHERE p.status = ?';
    $params[] = $status;
}
$sql .= ' ORDER BY p.id DESC LIMIT 200';
$st = $pdo->prepare($sql);
$st->execute($params);
$payments = $st->fetchAll();

$layout = 'admin';
$pageTitle = 'Payments';
include __DIR__ . '/../includes/header.php';
?>
<p class="page-lead">Review order payments and reconcile cash-on-delivery collections.</p>
<div class="toolbar">
  <form method="get" class="inline-form">
    <label class="sr-only" for="payment-status">Filter payments by status</label>
    <select id="payment-status" name="status">
      <option value="">All payment statuses</option>
      <?php foreach (['pending', 'paid', 'failed'] as $option): ?>
        <option value="<?= e($option) ?>" <?= $status === $option ? 'selected' : '' ?>><?= e(ucfirst($option)) ?></option>
      <?php endforeach; ?>
    </select>
    <button class="btn btn-outline" type="submit">Filter</button>
  </form>
</div>
<section class="card" data-live="orders" data-live-id="admin-payments">
  <?php if (!$payments): ?>
    <div class="empty-state"><h3>No payments found</h3><p>Payments will appear here when customers place orders.</p></div>
  <?php else: ?>
    <div class="table-wrap"><table class="table">
      <thead><tr><th>Payment</th><th>Order</th><th>Customer</th><th>Method</th><th>Amount</th><th>Status</th><th>Update</th></tr></thead>
      <tbody>
      <?php foreach ($payments as $payment): ?>
        <tr>
          <td>#<?= (int)$payment['id'] ?><small class="muted"><?= e(fmt_date($payment['created_at'])) ?></small></td>
          <td><a href="<?= e(url('admin/order-details.php?id=' . (int)$payment['order_id'])) ?>">#<?= (int)$payment['order_id'] ?></a><small class="muted"><?= e(ucfirst($payment['order_status'])) ?></small></td>
          <td><?= e($payment['full_name']) ?></td>
          <td><?= e(str_replace('_', ' ', ucfirst($payment['method']))) ?></td>
          <td><?= money($payment['amount']) ?></td>
          <td><?= status_badge($payment['status']) ?><?php if ($payment['paid_at']): ?><small class="muted"><?= e(fmt_date($payment['paid_at'])) ?></small><?php endif; ?></td>
          <td>
            <?php if (in_array($payment['order_status'], ['shipped', 'delivered'], true) && $payment['status'] !== 'paid'): ?>
              <form method="post" class="inline-form"><?= csrf_field() ?><input type="hidden" name="payment_id" value="<?= (int)$payment['id'] ?>"><input type="hidden" name="status" value="paid"><button class="btn btn-primary btn-sm" type="submit">Mark paid</button></form>
            <?php elseif (in_array($payment['order_status'], ['rejected', 'cancelled'], true) && $payment['status'] !== 'failed'): ?>
              <form method="post" class="inline-form"><?= csrf_field() ?><input type="hidden" name="payment_id" value="<?= (int)$payment['id'] ?>"><input type="hidden" name="status" value="failed"><button class="btn btn-danger btn-sm" type="submit">Mark failed</button></form>
            <?php else: ?><span class="muted">Follows order status</span><?php endif; ?>
          </td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table></div>
  <?php endif; ?>
</section>
<?php include __DIR__ . '/../includes/footer.php'; ?>
