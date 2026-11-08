<?php
require_once __DIR__ . '/includes/auth.php';
$filters = [
    'q'         => clean_str($_GET['q'] ?? '', 100),
    'search_in' => in_array($_GET['search_in'] ?? '', ['all', 'title', 'author'], true) ? $_GET['search_in'] : 'all',
    'category'  => (int)($_GET['category'] ?? 0),
    'sort'      => $_GET['sort'] ?? 'newest',
];
$books = books_search($filters);
$cats = categories_all();
$pageTitle = 'Books';
include __DIR__ . '/includes/header.php';
?>
<h1 class="page-title">Browse books</h1>
<form class="filter-bar" method="get" action="">
  <input type="search" name="q" value="<?= e($filters['q']) ?>" placeholder="Search..." maxlength="100" aria-label="Search">
  <select name="search_in" aria-label="Search in">
    <option value="all" <?= $filters['search_in'] === 'all' ? 'selected' : '' ?>>Title &amp; author</option>
    <option value="title" <?= $filters['search_in'] === 'title' ? 'selected' : '' ?>>Title only</option>
    <option value="author" <?= $filters['search_in'] === 'author' ? 'selected' : '' ?>>Author only</option>
  </select>
  <select name="category" aria-label="Category">
    <option value="0">All categories</option>
    <?php foreach ($cats as $c): ?>
      <option value="<?= (int)$c['id'] ?>" <?= $filters['category'] === (int)$c['id'] ? 'selected' : '' ?>><?= e($c['name']) ?></option>
    <?php endforeach; ?>
  </select>
  <select name="sort" aria-label="Sort">
    <option value="newest" <?= $filters['sort'] === 'newest' ? 'selected' : '' ?>>Newest</option>
    <option value="price_asc" <?= $filters['sort'] === 'price_asc' ? 'selected' : '' ?>>Price: low to high</option>
    <option value="price_desc" <?= $filters['sort'] === 'price_desc' ? 'selected' : '' ?>>Price: high to low</option>
    <option value="title" <?= $filters['sort'] === 'title' ? 'selected' : '' ?>>Title A-Z</option>
  </select>
  <button class="btn btn-primary" type="submit">Apply</button>
  <a class="btn btn-outline" href="<?= e(url('books.php')) ?>">Reset</a>
</form>
<div data-live="books" data-live-id="books-list">
  <p class="muted"><?= count($books) ?> book<?= count($books) === 1 ? '' : 's' ?> found</p>
  <?= render_book_cards($books) ?>
</div>
<?php include __DIR__ . '/includes/footer.php'; ?>
