<?php
require_once __DIR__ . '/../includes/auth.php';
$user = require_role('admin');
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_require();
    if (($_POST['action'] ?? '') === 'delete') {
        $id = (int)($_POST['id'] ?? 0);
        $pdo = getDB();
        $st = $pdo->prepare('SELECT title, cover_image FROM books WHERE id = ?'); $st->execute([$id]);
        if ($b = $st->fetch()) {
            $pdo->prepare('DELETE FROM books WHERE id = ?')->execute([$id]);
            delete_cover($b['cover_image']);
            log_activity((int)$user['id'], 'book_deleted', $b['title']);
            flash_set('success', 'Book "' . $b['title'] . '" deleted.');
        } else flash_set('error', 'Book not found.');
    }
    redirect('admin/books.php');
}
$q = clean_str($_GET['q'] ?? '', 100);
$books = books_search(['q' => $q, 'sort' => 'newest', 'limit' => 200]);
$layout = 'admin'; $pageTitle = 'Books';
include __DIR__ . '/../includes/header.php';
?>
<div class="toolbar">
  <form method="get" class="inline-form"><input type="search" name="q" value="<?= e($q) ?>" placeholder="Search title or author" maxlength="100"><button class="btn btn-outline" type="submit">Search</button></form>
  <a class="btn btn-primary" href="<?= e(url('admin/add-book.php')) ?>">+ Add book</a>
</div>
<div class="card" data-live="books" data-live-id="admin-books">
<?php if (!$books): ?><div class="empty-state"><h3>No books found</h3></div><?php else: ?>
  <div class="table-wrap"><table class="table">
    <thead><tr><th>Cover</th><th>Title</th><th>Author</th><th>Category</th><th>Price</th><th>Stock</th><th>Actions</th></tr></thead><tbody>
    <?php foreach ($books as $b): ?>
      <tr>
        <td><?= cover_img($b, 'thumb') ?></td>
        <td><?= e($b['title']) ?></td><td><?= e($b['author_name'] ?? '-') ?></td><td><?= e($b['category_name'] ?? '-') ?></td>
        <td><?= money($b['price']) ?></td><td><?= stock_badge((int)$b['stock']) ?></td>
        <td class="actions">
          <a class="btn btn-outline btn-sm" href="<?= e(url('admin/edit-book.php?id=' . (int)$b['id'])) ?>">Edit</a>
          <form method="post" class="inline-form" data-confirm="Delete this book permanently?">
            <?= csrf_field() ?><input type="hidden" name="action" value="delete"><input type="hidden" name="id" value="<?= (int)$b['id'] ?>">
            <button class="btn btn-danger btn-sm" type="submit">Delete</button></form>
        </td>
      </tr>
    <?php endforeach; ?></tbody></table></div>
<?php endif; ?>
</div>
<?php include __DIR__ . '/../includes/footer.php'; ?>
