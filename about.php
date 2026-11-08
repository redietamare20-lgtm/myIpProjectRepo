<?php
require_once __DIR__ . '/includes/auth.php';
$totalBooks = (int)getDB()->query('SELECT COUNT(*) FROM books')->fetchColumn();
$categoryCount = count(categories_all());
$pageTitle = 'About';
include __DIR__ . '/includes/header.php';
?>
<section class="story-hero">
  <div class="story-hero-copy">
    <span class="page-kicker">Our story</span>
    <h1 class="page-title">More than a shop.<br><em>A place to get lost.</em></h1>
    <p>We believe a good book can change the pace of a day, open a new door, or make you feel understood. We built this bookstore to make finding that book feel easy.</p>
    <a class="btn btn-accent" href="<?= e(url('books.php')) ?>">Find your next read <span aria-hidden="true">&rarr;</span></a>
  </div>
  <div class="story-hero-art" aria-hidden="true">
    <span class="story-art-sun"></span><span class="story-art-arch"></span>
    <span class="story-art-book story-art-book-one"></span><span class="story-art-book story-art-book-two"></span>
    <span class="story-art-star">✳</span>
  </div>
</section>

<section class="story-values">
  <article><span>01 / DISCOVER</span><h2>Follow your curiosity.</h2><p>Explore by author, category, or title, and find something you might not have searched for.</p></article>
  <article><span>02 / CHOOSE</span><h2>Take your time.</h2><p>Compare books, check availability, and choose the reads that feel right for you.</p></article>
  <article><span>03 / ENJOY</span><h2>Keep your next chapter close.</h2><p>Place your order with delivery details, then follow order updates from your account.</p></article>
</section>

<section class="story-note">
  <div class="story-note-mark" aria-hidden="true">“</div>
  <div><span class="page-kicker">A note from our shelves</span><h2>Every reader arrives with a different story.</h2><p>That is why our collection keeps growing. Browse what is available today, and come back when you are ready for what is next.</p></div>
  <div class="story-live-stats"><span><strong><?= $totalBooks ?></strong>Books in the collection</span><span><strong><?= $categoryCount ?></strong>Ways to explore</span></div>
</section>
<?php include __DIR__ . '/includes/footer.php'; ?>
