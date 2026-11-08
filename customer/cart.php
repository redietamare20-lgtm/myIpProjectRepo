<?php
require_once __DIR__ . '/../includes/auth.php';
$user = require_role('customer');
$items = cart_items((int)$user['id']);
$total = cart_total($items);
$problem = false;
foreach ($items as $i) if ((int)$i['quantity'] > (int)$i['stock']) $problem = true;
$pageTitle = 'Shopping cart';
include __DIR__ . '/../includes/header.php';
?>
<h1 class="page-title">Shopping cart</h1>
<div data-live="books cart" data-live-id="cart">
<?php if (!$items): ?>
  <div class="empty-state"><h3>Your cart is empty</h3><p>Find something to read.</p><a class="btn btn-primary" href="<?= e(url('books.php')) ?>">Browse books</a></div>
<?php else: ?>
  <?php if ($problem): ?><div class="alert alert-warning">Some items have less stock than you selected. Please lower the quantity.</div><?php endif; ?>
  <div class="table-wrap"><table class="table cart-table">
    <thead><tr><th>Book</th><th>Price</th><th>Quantity</th><th>Subtotal</th><th></th></tr></thead>
    <tbody>
    <?php foreach ($items as $i): $bad = (int)$i['quantity'] > (int)$i['stock']; ?>
      <tr>
        <td class="cart-book"><?= cover_img(['title' => $i['title'], 'cover_image' => $i['cover_image']], 'thumb') ?>
          <div><a href="<?= e(url('book-details.php?id=' . (int)$i['book_id'])) ?>"><?= e($i['title']) ?></a>
          <small class="muted"><?= e($i['author_name'] ?? '') ?></small>
          <?php if ((int)$i['stock'] < 1): ?><br><?= stock_badge(0) ?><?php endif; ?></div></td>
        <td><?= money($i['price']) ?></td>
        <td><input type="number" class="js-cart-qty qty-input <?= $bad ? 'input-error' : '' ?>" data-id="<?= (int)$i['book_id'] ?>" value="<?= (int)$i['quantity'] ?>" min="1" max="99" aria-label="Quantity"></td>
        <td><?= money((float)$i['price'] * (int)$i['quantity']) ?></td>
        <td><button type="button" class="btn btn-danger btn-sm js-cart-remove" data-id="<?= (int)$i['book_id'] ?>">Remove</button></td>
      </tr>
    <?php endforeach; ?>
    </tbody>
  </table></div>
  <div class="cart-summary">
    <p>Total: <strong class="price-lg"><?= money($total) ?></strong></p>
    <a class="btn btn-outline" href="<?= e(url('books.php')) ?>">Continue shopping</a>
    <?php if ($problem): ?><button class="btn btn-primary" disabled>Checkout</button>
    <?php else: ?><a class="btn btn-primary" href="<?= e(url('customer/checkout.php')) ?>">Proceed to checkout</a><?php endif; ?>
  </div>
<?php endif; ?>
</div>
<?php include __DIR__ . '/../includes/footer.php'; ?>
