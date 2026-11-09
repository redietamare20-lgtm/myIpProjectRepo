<?php
require_once __DIR__ . '/includes/auth.php';
$priceError = null;
$cleanPrice = static function (string $value) use (&$priceError): string {
    if ($value === '') return '';
    $validPrice = filter_var($value, FILTER_VALIDATE_FLOAT);
    if ($validPrice === false || $validPrice < 0 || $validPrice > 100000) {
        $priceError = 'Price filters must be between 0 and 100000.';
        return '';
    }
    return (string)$validPrice;
};
$minPrice = $cleanPrice(trim((string)($_GET['min_price'] ?? '')));
$maxPrice = $cleanPrice(trim((string)($_GET['max_price'] ?? '')));
if ($minPrice !== '' && $maxPrice !== '' && (float)$minPrice > (float)$maxPrice) {
    $priceError = 'Minimum price cannot be greater than maximum price.';
}
$filters = [
    'q'         => clean_str($_GET['q'] ?? '', 100),
    'search_in' => in_array($_GET['search_in'] ?? '', ['all', 'title', 'author'], true) ? $_GET['search_in'] : 'all',
    'category'  => (int)($_GET['category'] ?? 0),
    'sort'      => $_GET['sort'] ?? 'newest',
    'min_price' => $minPrice,
    'max_price' => $maxPrice,
];
$perPage = 12;
$totalBooks = books_count($filters);
$totalPages = max(1, (int)ceil($totalBooks / $perPage));
$page = min(max(1, (int)($_GET['page'] ?? 1)), $totalPages);
$books = books_search(array_merge($filters, ['limit' => $perPage, 'offset' => ($page - 1) * $perPage]));
$firstBook = $totalBooks ? (($page - 1) * $perPage) + 1 : 0;
$lastBook = min($page * $perPage, $totalBooks);
$firstPage = max(1, min($page - 2, $totalPages - 4));
$lastPage = min($totalPages, $firstPage + 4);
$cats = categories_all();
$pageTitle = 'Books';
include __DIR__ . '/includes/header.php';
?>
<section class="catalog-intro">
  <div class="catalog-intro-copy">
    <span class="page-kicker">The collection</span>
    <h1 class="page-title">A good book is<br><em>closer than you think.</em></h1>
    <p>Find your next favourite by title, author, category, or price. Take a look around.</p>
    <div class="catalog-highlights">
      <span><strong><?= $totalBooks ?></strong> books to discover</span>
      <span><strong><?= count($cats) ?></strong> thoughtful categories</span>
    </div>
  </div>
  <div class="catalog-intro-art" aria-hidden="true">
    <span class="catalog-art-sun"></span><span class="catalog-art-book catalog-art-book-back"></span>
    <span class="catalog-art-book catalog-art-book-front"></span><span class="catalog-art-note">A LITTLE<br>ESCAPE</span>
  </div>
</section>
<section class="catalog-controls" aria-labelledby="catalog-filter-title">
  <div class="catalog-controls-heading">
    <div><span class="page-kicker">Make it yours</span><h2 id="catalog-filter-title">Find a book</h2></div>
    <span class="catalog-count"><?= $totalBooks ?> titles</span>
  </div>
  <form class="filter-bar" method="get" action="<?= e(url('books.php')) ?>">
    <label class="catalog-search-field"><span class="sr-only">Search books</span><svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="10.8" cy="10.8" r="6.8"/><path d="m16 16 4.5 4.5"/></svg><input type="search" name="q" value="<?= e($filters['q']) ?>" placeholder="Search title or author..." maxlength="100"></label>
    <label><span class="sr-only">Search in</span><select name="search_in" aria-label="Search in">
      <option value="all" <?= $filters['search_in'] === 'all' ? 'selected' : '' ?>>Title &amp; author</option>
      <option value="title" <?= $filters['search_in'] === 'title' ? 'selected' : '' ?>>Title only</option>
      <option value="author" <?= $filters['search_in'] === 'author' ? 'selected' : '' ?>>Author only</option>
    </select></label>
    <label><span class="sr-only">Category</span><select name="category" aria-label="Category">
      <option value="0">All categories</option>
      <?php foreach ($cats as $c): ?>
        <option value="<?= (int)$c['id'] ?>" <?= $filters['category'] === (int)$c['id'] ? 'selected' : '' ?>><?= e($c['name']) ?></option>
      <?php endforeach; ?>
    </select></label>
    <label><span class="sr-only">Sort books</span><select name="sort" aria-label="Sort books">
      <option value="newest" <?= $filters['sort'] === 'newest' ? 'selected' : '' ?>>Recently added</option>
      <option value="price_asc" <?= $filters['sort'] === 'price_asc' ? 'selected' : '' ?>>Price: low to high</option>
      <option value="price_desc" <?= $filters['sort'] === 'price_desc' ? 'selected' : '' ?>>Price: high to low</option>
      <option value="title" <?= $filters['sort'] === 'title' ? 'selected' : '' ?>>Title A–Z</option>
    </select></label>
    <div class="catalog-price-fields">
      <label><span class="sr-only">Minimum price</span><input type="number" name="min_price" value="<?= e($minPrice) ?>" min="0" max="100000" step="0.01" placeholder="Min price" aria-label="Minimum price"></label>
      <span aria-hidden="true">–</span>
      <label><span class="sr-only">Maximum price</span><input type="number" name="max_price" value="<?= e($maxPrice) ?>" min="0" max="100000" step="0.01" placeholder="Max price" aria-label="Maximum price"></label>
    </div>
    <button class="btn btn-primary" type="submit">Apply filters <span aria-hidden="true">&rarr;</span></button>
    <a class="catalog-reset" href="<?= e(url('books.php')) ?>">Clear</a>
  </form>
  <?php if ($cats): ?>
    <div class="catalog-category-browse">
      <span class="catalog-category-label">Browse by category</span>
      <div class="catalog-chip-row" aria-label="Book categories">
      <?php foreach ($cats as $category): ?>
        <?php $isActive = (int)$filters['category'] === (int)$category['id']; ?>
        <?php $chipFilters = $filters; unset($chipFilters['category'], $chipFilters['page']); ?>
        <?php if (!$isActive) $chipFilters['category'] = (int)$category['id']; ?>
        <a class="catalog-chip<?= $isActive ? ' catalog-chip-active' : '' ?>" href="<?= e(url('books.php?' . http_build_query($chipFilters))) ?>"<?= $isActive ? ' aria-current="true"' : '' ?>>
          <?= e($category['name']) ?>
        </a>
      <?php endforeach; ?>
        <?php if ((int)$filters['category'] !== 0): ?>
          <?php $allBooksFilters = $filters; unset($allBooksFilters['category'], $allBooksFilters['page']); ?>
          <a class="catalog-chip catalog-chip-muted" href="<?= e(url('books.php?' . http_build_query($allBooksFilters))) ?>">All categories</a>
        <?php endif; ?>
      </div>
    </div>
  <?php endif; ?>
</section>
<div data-live="books" data-live-id="books-list">
  <?php if ($priceError): ?><div class="alert alert-warning" role="status"><?= e($priceError) ?></div><?php endif; ?>
  <div class="catalog-results-heading">
    <div><span class="page-kicker">The bookshelf</span><p class="muted">Showing <strong><?= $firstBook ?>–<?= $lastBook ?></strong> of <strong><?= $totalBooks ?></strong> book<?= $totalBooks === 1 ? '' : 's' ?></p></div>
    <span>CURATED FOR YOUR NEXT CHAPTER</span>
  </div>
  <?= render_book_cards($books) ?>
  <?php if ($totalPages > 1): ?>
    <nav class="pagination" aria-label="Book pages">
      <?php if ($page > 1): ?>
        <a href="<?= e(url('books.php?' . http_build_query(array_merge($filters, ['page' => $page - 1])))) ?>" aria-label="Previous page">&larr; Previous</a>
      <?php else: ?><span class="pagination-disabled" aria-disabled="true">Previous</span><?php endif; ?>
      <?php for ($pageNumber = $firstPage; $pageNumber <= $lastPage; $pageNumber++): ?>
        <?php if ($pageNumber === $page): ?>
          <span class="pagination-current" aria-current="page"><?= $pageNumber ?></span>
        <?php else: ?>
          <a href="<?= e(url('books.php?' . http_build_query(array_merge($filters, ['page' => $pageNumber])))) ?>" aria-label="Page <?= $pageNumber ?>"><?= $pageNumber ?></a>
        <?php endif; ?>
      <?php endfor; ?>
      <?php if ($page < $totalPages): ?>
        <a href="<?= e(url('books.php?' . http_build_query(array_merge($filters, ['page' => $page + 1])))) ?>" aria-label="Next page">Next &rarr;</a>
      <?php else: ?><span class="pagination-disabled" aria-disabled="true">Next</span><?php endif; ?>
    </nav>
  <?php endif; ?>
</div>
<?php include __DIR__ . '/includes/footer.php'; ?>
