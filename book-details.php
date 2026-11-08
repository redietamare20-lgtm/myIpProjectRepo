<?php
require_once __DIR__ . '/includes/auth.php';
$id = (int)($_GET['id'] ?? 0);
$book = $id ? book_get($id) : null;
$viewer = current_user();
$reviews = [];
$canReview = false;
$hasReviewed = false;
if ($book) {
    $st = getDB()->prepare('SELECT r.rating, r.review_text, r.created_at, u.full_name
        FROM reviews r JOIN users u ON u.id = r.user_id
        WHERE r.book_id = ? ORDER BY r.created_at DESC, r.id DESC');
    $st->execute([(int)$book['id']]);
    $reviews = $st->fetchAll();

    if ($viewer && $viewer['role'] === 'customer') {
        $st = getDB()->prepare("SELECT 1 FROM orders o JOIN order_items oi ON oi.order_id = o.id
            WHERE o.user_id = ? AND oi.book_id = ? AND o.status = 'delivered' LIMIT 1");
        $st->execute([(int)$viewer['id'], (int)$book['id']]);
        $canReview = (bool)$st->fetchColumn();

        $st = getDB()->prepare('SELECT 1 FROM reviews WHERE user_id = ? AND book_id = ?');
        $st->execute([(int)$viewer['id'], (int)$book['id']]);
        $hasReviewed = (bool)$st->fetchColumn();
    }
}
$reviewCount = count($reviews);
$averageRating = $reviewCount ? array_sum(array_column($reviews, 'rating')) / $reviewCount : 0;
$pageTitle = $book ? $book['title'] : 'Book not found';
include __DIR__ . '/includes/header.php';
?>
<p><a href="<?= e(url('books.php')) ?>">&larr; Back to books</a></p>
<div data-live="books" data-live-id="book-detail">
<?php if (!$book): ?>
  <div class="empty-state"><h3>Book not found</h3><p>This book may have been removed or is no longer available.</p><a class="btn btn-primary" href="<?= e(url('books.php')) ?>">Browse books</a></div>
<?php else: $out = (int)$book['stock'] < 1; ?>
  <div class="detail-grid">
    <div><?= cover_img($book, 'detail-cover') ?></div>
    <div>
      <span class="tag"><?= e($book['category_name'] ?? 'Uncategorized') ?></span>
      <h1><?= e($book['title']) ?></h1>
      <p class="book-author">by <?= e($book['author_name'] ?? 'Unknown author') ?></p>
      <p class="price price-lg"><?= money($book['price']) ?></p>
      <p><?= stock_badge((int)$book['stock']) ?></p>
      <?php if ($book['isbn']): ?><p class="muted"><strong>ISBN:</strong> <?= e($book['isbn']) ?></p><?php endif; ?>
      <p class="description"><?= nl2br(e($book['description'] ?? 'No description available.')) ?></p>
      <div class="buy-row">
        <input type="number" id="qty" value="1" min="1" max="<?= max(1, (int)$book['stock']) ?>" aria-label="Quantity" <?= $out ? 'disabled' : '' ?>>
        <button type="button" class="btn btn-primary js-add-cart" data-id="<?= (int)$book['id'] ?>" data-qty="#qty" <?= $out ? 'disabled' : '' ?>><?= $out ? 'Out of stock' : 'Add to cart' ?></button>
      </div>
    </div>
  </div>
<?php endif; ?>
</div>
<?php if ($book): ?>
<section class="reviews-section" id="reviews" aria-labelledby="reviews-title">
  <div class="section-head">
    <div>
      <span class="section-kicker">From fellow readers</span>
      <h2 class="section-title" id="reviews-title">Customer reviews</h2>
    </div>
    <?php if ($reviewCount): ?>
      <p class="review-summary"><span class="rating-stars" aria-label="<?= e(number_format($averageRating, 1)) ?> out of 5 stars"><?= str_repeat('★', (int)round($averageRating)) ?><?= str_repeat('☆', 5 - (int)round($averageRating)) ?></span>
        <strong><?= e(number_format($averageRating, 1)) ?></strong> · <?= $reviewCount ?> <?= $reviewCount === 1 ? 'review' : 'reviews' ?></p>
    <?php else: ?><p class="muted">No reviews yet</p><?php endif; ?>
  </div>
  <div class="review-layout">
    <div class="review-list">
      <?php if (!$reviews): ?>
        <div class="empty-state"><h3>Be the first to share your thoughts</h3><p>Verified reviews from readers help others find their next book.</p></div>
      <?php else: foreach ($reviews as $review): ?>
        <article class="review-item">
          <div class="review-item-head">
            <strong><?= e($review['full_name']) ?></strong>
            <span class="rating-stars" aria-label="<?= (int)$review['rating'] ?> out of 5 stars"><?= str_repeat('★', (int)$review['rating']) ?><?= str_repeat('☆', 5 - (int)$review['rating']) ?></span>
          </div>
          <p><?= nl2br(e($review['review_text'])) ?></p>
          <time class="muted" datetime="<?= e(date('c', strtotime($review['created_at']))) ?>"><?= e(date('M j, Y', strtotime($review['created_at']))) ?></time>
        </article>
      <?php endforeach; endif; ?>
    </div>
    <aside class="card review-form-card">
      <h3>Share your experience</h3>
      <?php if ($canReview && !$hasReviewed): ?>
        <p class="muted">Your delivered order is verified. Share an honest review to help other readers.</p>
        <form method="post" action="<?= e(url('customer/review.php')) ?>">
          <?= csrf_field() ?>
          <input type="hidden" name="book_id" value="<?= (int)$book['id'] ?>">
          <label for="review-rating">Your rating</label>
          <select id="review-rating" name="rating" required>
            <option value="">Select a rating</option>
            <option value="5">5 — Loved it</option><option value="4">4 — Enjoyed it</option>
            <option value="3">3 — It was good</option><option value="2">2 — Not for me</option>
            <option value="1">1 — Disappointed</option>
          </select>
          <label for="review-text">Your review<textarea id="review-text" name="review_text" required minlength="10" maxlength="2000" rows="5" placeholder="What did you think of this book?"></textarea></label>
          <button class="btn btn-primary btn-block" type="submit">Submit review</button>
        </form>
      <?php elseif ($hasReviewed): ?>
        <p class="muted">You have already reviewed this book. Thank you for helping other readers.</p>
      <?php elseif ($viewer && $viewer['role'] === 'customer'): ?>
        <p class="muted">You can review this book after an order containing it has been delivered.</p>
      <?php else: ?>
        <p class="muted">Log in as a customer with a delivered order to review this book.</p>
        <a class="btn btn-outline btn-block" href="<?= e(url('login.php?next=' . rawurlencode('book-details.php?id=' . (int)$book['id']))) ?>">Log in to review</a>
      <?php endif; ?>
    </aside>
  </div>
</section>
<?php endif; ?>
<?php include __DIR__ . '/includes/footer.php'; ?>
