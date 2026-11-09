<?php
require_once __DIR__ . '/includes/auth.php';
$pdo = getDB();
$totalBooks = (int)$pdo->query('SELECT COUNT(*) FROM books')->fetchColumn();
$categories = categories_all();
$latest = books_search(['sort' => 'newest', 'limit' => 4]);
$popularStmt = $pdo->query(
    "SELECT b.*, a.name AS author_name, c.name AS category_name
     FROM order_items oi
     JOIN orders o ON o.id = oi.order_id
     JOIN books b ON b.id = oi.book_id
     LEFT JOIN authors a ON a.id = b.author_id
     LEFT JOIN categories c ON c.id = b.category_id
     WHERE o.status IN ('approved','processing','shipped','delivered')
     GROUP BY b.id
     ORDER BY SUM(oi.quantity) DESC, b.title ASC
     LIMIT 4"
);
$popular = $popularStmt->fetchAll();
$pageTitle = 'Home';
$bodyClass = 'home-page';
include __DIR__ . '/includes/header.php';
?>
<section class="hero">
  <div class="hero-text">
    <span class="eyebrow">A good book changes everything</span>
    <h1>Make room for your next favourite.</h1>
    <p>Thoughtful reads, a welcoming way to discover them, and dependable delivery from checkout to your door.</p>
    <form class="hero-search" action="<?= e(url('books.php')) ?>" method="get">
      <label class="sr-only" for="home-search">Search by title or author</label>
      <input id="home-search" type="search" name="q" placeholder="Search by title or author..." maxlength="100">
      <button class="btn btn-accent" type="submit">Explore books</button>
    </form>
    <p class="hero-note"><?= $totalBooks ?> books to explore <span aria-hidden="true">&middot;</span> <?= count($categories) ?> categories</p>
  </div>
  <div class="hero-art" data-live="books" data-live-id="home-featured" aria-label="Featured book">
  <?php if ($latest): $showcase = $latest[0]; ?>
    <div class="hero-orbit hero-orbit-one"></div>
    <div class="hero-orbit hero-orbit-two"></div>
    <article class="hero-book">
      <?= cover_img($showcase, 'hero-book-cover') ?>
      <div class="hero-book-caption">
        <span class="hero-book-kicker">JUST ARRIVED</span>
        <strong><?= e($showcase['title']) ?></strong>
        <span><?= e($showcase['author_name'] ?? 'Independent discovery') ?></span>
      </div>
    </article>
    <span class="hero-stamp">TURN<br>THE<br>PAGE</span>
  <?php else: ?>
    <div class="hero-empty">Your next great read is just around the corner.</div>
  <?php endif; ?>
  </div>
</section>

<div class="service-points" aria-label="Shopping benefits">
  <span><i aria-hidden="true">01</i> Carefully selected reads</span>
  <span><i aria-hidden="true">02</i> Stock checked at checkout</span>
  <span><i aria-hidden="true">03</i> Order updates as they happen</span>
</div>

<section class="section" id="categories">
  <div class="section-head">
    <div><span class="section-kicker">A shelf for every mood</span><h2 class="section-title">Explore the collection</h2></div>
    <a href="<?= e(url('books.php')) ?>">All books <span aria-hidden="true">&rarr;</span></a>
  </div>
  <div data-live="books" data-live-id="home-categories">
  <?php if ($categories): ?>
    <div class="category-grid">
      <?php foreach ($categories as $index => $category): ?>
        <a class="category-tile category-tone-<?= $index % 4 ?>" href="<?= e(url('books.php?category=' . (int)$category['id'])) ?>">
          <span class="category-number"><?= sprintf('%02d', $index + 1) ?></span>
          <strong><?= e($category['name']) ?></strong>
          <span class="category-link">Explore <span aria-hidden="true">&rarr;</span></span>
        </a>
      <?php endforeach; ?>
    </div>
  <?php else: ?>
    <div class="empty-state"><p>New categories are being curated. Browse all available books in the meantime.</p><a class="btn btn-outline" href="<?= e(url('books.php')) ?>">Browse books</a></div>
  <?php endif; ?>
  </div>
</section>

<section class="section" data-live="books" data-live-id="home-new-arrivals">
  <div class="section-head">
    <div><span class="section-kicker">Fresh from the shelves</span><h2 class="section-title">New arrivals</h2></div>
    <a href="<?= e(url('books.php?sort=newest')) ?>">View all books <span aria-hidden="true">&rarr;</span></a>
  </div>
  <?= render_book_cards($latest) ?>
</section>

<section class="section" data-live="books orders" data-live-id="home-bestsellers">
  <div class="section-head">
    <div><span class="section-kicker">Reader favourites</span><h2 class="section-title">Most-loved reads</h2></div>
    <a href="<?= e(url('books.php')) ?>">Discover more <span aria-hidden="true">&rarr;</span></a>
  </div>
  <?php if ($popular): ?>
    <?= render_book_cards($popular) ?>
  <?php else: ?>
    <div class="empty-state"><p>Popular reads will appear here as customers complete their orders.</p><a class="btn btn-outline" href="<?= e(url('books.php')) ?>">Explore the collection</a></div>
  <?php endif; ?>
</section>

<section class="closing-banner">
  <div><span class="section-kicker">Your next chapter starts here</span><h2>There&rsquo;s a story waiting for you.</h2><p>Browse by author, category, or simply follow your curiosity.</p></div>
  <a class="btn btn-accent" href="<?= e(url('books.php')) ?>">Find your next read <span aria-hidden="true">&rarr;</span></a>
</section>
<?php include __DIR__ . '/includes/footer.php'; ?>
