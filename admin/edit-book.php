<?php
require_once __DIR__ . '/../includes/auth.php';
$user = require_role('admin');
$id = (int)($_GET['id'] ?? 0);
$existing = $id ? book_get($id) : null;
if (!$existing) { flash_set('error', 'Book not found.'); redirect('admin/books.php'); }
$errors = [];
$book = $existing;
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_require();
    $r = save_book($id, $_POST, $_FILES['cover'] ?? []);
    if ($r['ok']) {
        log_activity((int)$user['id'], 'book_updated', clean_str($_POST['title'], 100));
        flash_set('success', 'Book updated. Customers will see the changes automatically.');
        redirect('admin/books.php');
    }
    $errors = $r['errors'];
    $book = array_merge($existing, $_POST);
}
$layout = 'admin'; $pageTitle = 'Edit book';
$submitLabel = 'Save changes';
include __DIR__ . '/../includes/header.php';
include __DIR__ . '/../includes/book_form.php';
include __DIR__ . '/../includes/footer.php';
