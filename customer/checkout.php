<?php
require_once __DIR__ . '/../includes/auth.php';
$user = require_role('customer');
$items = cart_items((int)$user['id']);
if (!$items) { flash_set('warning', 'Your cart is empty.'); redirect('customer/cart.php'); }
$total = cart_total($items);
$pageTitle = 'Checkout';
include __DIR__ . '/../includes/header.php';
?>
<h1 class="page-title">Checkout</h1>
<p class="muted" style="margin:-.25rem 0 1.25rem;">Confirm delivery details and review your order before placing it.</p>
<div class="two-col">
  <section class="card">
    <h2>Delivery details</h2>
    <form id="checkout-form" novalidate>
      <label>Delivery address<input type="text" name="address" required maxlength="255" placeholder="Street, city, and any delivery notes" value="<?= e($user['address'] ?? '') ?>"></label>
      <label>Phone<input type="tel" name="phone" required maxlength="30" placeholder="Best number for delivery updates" value="<?= e($user['phone'] ?? '') ?>"></label>
      <label>Notes (optional)<textarea name="notes" rows="3" maxlength="255" placeholder="Gate code, preferred time, or other instructions"></textarea></label>
      <div class="alert alert-info"><strong>Payment:</strong> Cash on delivery. You pay when the books arrive.</div>
      <div id="checkout-error" class="alert alert-error" hidden></div>
      <button class="btn btn-primary btn-block" type="submit">Place order</button>
    </form>
  </section>
  <section class="card">
    <h2>Order summary</h2>
    <ul class="summary-list">
      <?php foreach ($items as $i): ?>
        <li><span><?= e($i['title']) ?> &times; <?= (int)$i['quantity'] ?></span><span><?= money((float)$i['price'] * (int)$i['quantity']) ?></span></li>
      <?php endforeach; ?>
      <li class="summary-total"><span>Total</span><span><?= money($total) ?></span></li>
    </ul>
    <p class="muted">Final price and stock are verified on the server when you place the order.</p>
  </section>
</div>
<?php include __DIR__ . '/../includes/footer.php'; ?>
