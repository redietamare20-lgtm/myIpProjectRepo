<?php
/** Shared add/edit book form. Expects $book (array), $errors (array), $formTitle, $submitLabel. */
$cats = categories_all();
$authors = authors_all();
$v = function ($k) use ($book) { return e($book[$k] ?? ''); };
?>
<?php foreach ($errors as $er): ?><div class="alert alert-error"><?= e($er) ?></div><?php endforeach; ?>
<form method="post" action="" enctype="multipart/form-data" class="card form-grid">
  <?= csrf_field() ?>
  <label class="span-2">Title *<input type="text" name="title" required maxlength="200" value="<?= $v('title') ?>"></label>
  <label>Author
    <select name="author_id"><option value="0">- none -</option>
      <?php foreach ($authors as $a): ?><option value="<?= (int)$a['id'] ?>" <?= (int)($book['author_id'] ?? 0) === (int)$a['id'] ? 'selected' : '' ?>><?= e($a['name']) ?></option><?php endforeach; ?>
    </select></label>
  <label>...or add a new author<input type="text" name="new_author" maxlength="100" placeholder="New author name"></label>
  <label>Category
    <select name="category_id"><option value="0">- none -</option>
      <?php foreach ($cats as $c): ?><option value="<?= (int)$c['id'] ?>" <?= (int)($book['category_id'] ?? 0) === (int)$c['id'] ? 'selected' : '' ?>><?= e($c['name']) ?></option><?php endforeach; ?>
    </select></label>
  <label>ISBN<input type="text" name="isbn" maxlength="20" value="<?= $v('isbn') ?>"></label>
  <label>Price *<input type="number" name="price" step="0.01" min="0" required value="<?= $v('price') ?>"></label>
  <label>Stock quantity *<input type="number" name="stock" min="0" step="1" required value="<?= $v('stock') ?>"></label>
  <label class="span-2">Description<textarea name="description" rows="5" maxlength="5000"><?= $v('description') ?></textarea></label>
  <label class="span-2">Cover image (JPG, PNG or WEBP, max 2 MB)
    <input type="file" name="cover" accept="image/jpeg,image/png,image/webp">
    <?php if (!empty($book['cover_image'])): ?><span class="muted">Current cover:</span> <?= cover_img($book, 'thumb') ?><?php endif; ?>
  </label>
  <div class="span-2"><button class="btn btn-primary" type="submit"><?= e($submitLabel) ?></button>
    <a class="btn btn-outline" href="<?= e(url('admin/books.php')) ?>">Cancel</a></div>
</form>
