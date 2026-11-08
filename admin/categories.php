<?php
require_once __DIR__ . '/../includes/auth.php';
$user = require_role('admin');
$pdo = getDB();
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_require();
    $act = $_POST['action'] ?? '';
    $id = (int)($_POST['id'] ?? 0);
    $name = clean_str($_POST['name'] ?? '', 100);
    $table = in_array($_POST['type'] ?? '', ['category', 'author'], true) ? $_POST['type'] : null;
    try {
        if (!$table) throw new RuntimeException('Invalid request.');
        $tbl = $table === 'category' ? 'categories' : 'authors';
        if ($act === 'add' || $act === 'save') {
            if ($name === '') throw new RuntimeException('Name is required.');
            if ($act === 'add') {
                $pdo->prepare("INSERT INTO $tbl (name) VALUES (?)")->execute([$name]);
                flash_set('success', ucfirst($table) . ' added.');
            } else {
                $pdo->prepare("UPDATE $tbl SET name = ? WHERE id = ?")->execute([$name, $id]);
                flash_set('success', ucfirst($table) . ' updated.');
            }
            log_activity((int)$user['id'], $table . '_' . $act, $name);
        } elseif ($act === 'delete') {
            $pdo->prepare("DELETE FROM $tbl WHERE id = ?")->execute([$id]);   // books keep existing; their link becomes empty
            flash_set('success', ucfirst($table) . ' deleted.');
            log_activity((int)$user['id'], $table . '_deleted', '#' . $id);
        }
    } catch (RuntimeException $ex) {
        flash_set('error', $ex->getMessage());
    } catch (PDOException $ex) {
        flash_set('error', $ex->getCode() === '23000' ? 'That name already exists.' : 'Database error.');
    }
    redirect('admin/categories.php');
}
$cats = $pdo->query('SELECT c.*, (SELECT COUNT(*) FROM books b WHERE b.category_id = c.id) AS book_count FROM categories c ORDER BY c.name')->fetchAll();
$authors = $pdo->query('SELECT a.*, (SELECT COUNT(*) FROM books b WHERE b.author_id = a.id) AS book_count FROM authors a ORDER BY a.name')->fetchAll();
$layout = 'admin'; $pageTitle = 'Categories & Authors';
include __DIR__ . '/../includes/header.php';

function manage_block(string $type, string $label, array $rows): void { ?>
  <section class="card" data-live="books" data-live-id="manage-<?= e($type) ?>">
    <h2><?= e($label) ?>s</h2>
    <form method="post" class="inline-form add-row">
      <?= csrf_field() ?><input type="hidden" name="action" value="add"><input type="hidden" name="type" value="<?= e($type) ?>">
      <input type="text" name="name" placeholder="New <?= e(strtolower($label)) ?> name" required maxlength="100">
      <button class="btn btn-primary" type="submit">Add</button>
    </form>
    <?php if (!$rows): ?><div class="empty-state"><p>No <?= e(strtolower($label)) ?>s yet.</p></div><?php else: ?>
    <div class="table-wrap"><table class="table"><thead><tr><th>Name</th><th>Books</th><th>Actions</th></tr></thead><tbody>
      <?php foreach ($rows as $r): ?>
        <tr>
          <td><form method="post" class="inline-form">
            <?= csrf_field() ?><input type="hidden" name="action" value="save"><input type="hidden" name="type" value="<?= e($type) ?>"><input type="hidden" name="id" value="<?= (int)$r['id'] ?>">
            <input type="text" name="name" value="<?= e($r['name']) ?>" required maxlength="100"><button class="btn btn-outline btn-sm" type="submit">Save</button></form></td>
          <td><?= (int)$r['book_count'] ?></td>
          <td><form method="post" class="inline-form" data-confirm="Delete this <?= e(strtolower($label)) ?>? Its books will be kept without one.">
            <?= csrf_field() ?><input type="hidden" name="action" value="delete"><input type="hidden" name="type" value="<?= e($type) ?>"><input type="hidden" name="id" value="<?= (int)$r['id'] ?>">
            <button class="btn btn-danger btn-sm" type="submit">Delete</button></form></td>
        </tr>
      <?php endforeach; ?></tbody></table></div>
    <?php endif; ?>
  </section>
<?php } ?>
<div class="two-col">
  <?php manage_block('category', 'Category', $cats); ?>
  <?php manage_block('author', 'Author', $authors); ?>
</div>
<?php include __DIR__ . '/../includes/footer.php'; ?>
