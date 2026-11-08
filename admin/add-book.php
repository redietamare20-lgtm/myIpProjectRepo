<?php
require_once __DIR__ . '/../includes/auth.php';
$user = require_role('admin');
$errors = [];
$book = $_POST ?: ['stock' => 0];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_require();
    $r = save_book(null, $_POST, $_FILES['cover'] ?? []);
    if ($r['ok']) {
        log_activity((int)$user['id'], 'book_added', clean_str($_POST['title'], 100));
        flash_set('success', 'Book added. Customers can see it right away.');
        redirect('admin/books.php');
    }
    $errors = $r['errors'];
}
$layout = 'admin'; $pageTitle = 'Add book';
$submitLabel = 'Add book';
include __DIR__ . '/../includes/header.php';
include __DIR__ . '/../includes/book_form.php';
include __DIR__ . '/../includes/footer.php';
