<?php
require_once __DIR__ . '/includes/auth.php';
$pdo = getDB();
$totalBooks = (int)$pdo->query('SELECT COUNT(*) FROM books')->fetchColumn();
$cats = categories_all();
$latest = books_search(['sort' => 'newest', 'limit' => 8]);
$pageTitle = 'Home';
include __DIR__ . '/includes/header.php';
?>
<section class="hero">
  <div class="hero-text">
    <h1>Find your next favourite book</h1>
    <p>Browse our collection, order online and follow your order in real time.</p>
    <form class="hero-search" action="<?= e(url('books.php')) ?>" method="get">
      <input type="search" name="q" placeholder="Search by title or author..." maxlength="100" aria-label="Search">
      <button class="btn btn-accent" type="submit">Search</button>
    </form>
    <p class="hero-note"><?= $totalBooks ?> books &middot; <?= count($cats) ?> categories</p>
  </div>
</section>

<section class="section">
  <h2 class="section-title">Browse by category</h2>
  <div class="chips">
    <?php foreach ($cats as $c): ?>
      <a class="chip" href="<?= e(url('books.php?category=' . (int)$c['id'])) ?>"><?= e($c['name']) ?></a>
    <?php endforeach; ?>
    <?php if (!$cats): ?><p class="muted">No categories yet.</p><?php endif; ?>
  </div>
</section>

<section class="section">
  <div class="section-head"><h2 class="section-title">Latest books</h2><a href="<?= e(url('books.php')) ?>">View all &rarr;</a></div>
  <div data-live="books" data-live-id="home-books"><?= render_book_cards($latest) ?></div>
</section>
<?php include __DIR__ . '/includes/footer.php'; ?>
