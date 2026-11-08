<?php
require_once __DIR__ . '/includes/auth.php';
$id = (int)($_GET['id'] ?? 0);
$book = $id ? book_get($id) : null;
$pageTitle = $book ? $book['title'] : 'Book not found';
include __DIR__ . '/includes/header.php';
?>
<p><a href="<?= e(url('books.php')) ?>">&larr; Back to books</a></p>
<div data-live="books" data-live-id="book-detail">
<?php if (!$book): ?>
  <div class="empty-state"><h3>Book not found</h3><p>This book may have been removed.</p></div>
<?php else: $out = (int)$book['stock'] < 1; ?>
  <div class="detail-grid">
    <div><?= cover_img($book, 'detail-cover') ?></div>
    <div>
      <span class="tag"><?= e($book['category_name'] ?? 'Uncategorized') ?></span>
      <h1><?= e($book['title']) ?></h1>
      <p class="book-author">by <?= e($book['author_name'] ?? 'Unknown author') ?></p>
      <p class="price price-lg"><?= money($book['price']) ?></p>
      <p><?= stock_badge((int)$book['stock']) ?></p>
      <?php if ($book['isbn']): ?><p class="muted">ISBN: <?= e($book['isbn']) ?></p><?php endif; ?>
      <p class="description"><?= nl2br(e($book['description'] ?? 'No description available.')) ?></p>
      <div class="buy-row">
        <input type="number" id="qty" value="1" min="1" max="<?= max(1, (int)$book['stock']) ?>" aria-label="Quantity" <?= $out ? 'disabled' : '' ?>>
        <button type="button" class="btn btn-primary js-add-cart" data-id="<?= (int)$book['id'] ?>" data-qty="#qty" <?= $out ? 'disabled' : '' ?>><?= $out ? 'Out of stock' : 'Add to cart' ?></button>
      </div>
    </div>
  </div>
<?php endif; ?>
</div>
<?php include __DIR__ . '/includes/footer.php'; ?>
