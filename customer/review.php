<?php
require_once __DIR__ . '/../includes/auth.php';
$user = require_role('customer');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Allow: POST');
    http_response_code(405);
    exit('Method not allowed.');
}

csrf_require();
$bookId = filter_var($_POST['book_id'] ?? null, FILTER_VALIDATE_INT);
$rating = filter_var($_POST['rating'] ?? null, FILTER_VALIDATE_INT);
$reviewText = clean_str($_POST['review_text'] ?? '', 2000);

if (!$bookId || $bookId < 1 || !$rating || $rating < 1 || $rating > 5 || mb_strlen($reviewText) < 10) {
    flash_set('error', 'Choose a rating and write a review of at least 10 characters.');
    redirect('books.php');
}

$pdo = getDB();
$st = $pdo->prepare('SELECT id FROM books WHERE id = ?');
$st->execute([$bookId]);
if (!$st->fetchColumn()) {
    flash_set('warning', 'That book is no longer available.');
    redirect('books.php');
}

$st = $pdo->prepare("SELECT 1 FROM orders o JOIN order_items oi ON oi.order_id = o.id
    WHERE o.user_id = ? AND oi.book_id = ? AND o.status = 'delivered' LIMIT 1");
$st->execute([(int)$user['id'], $bookId]);
if (!$st->fetchColumn()) {
    flash_set('error', 'Only customers with a delivered order for this book can leave a review.');
    redirect('book-details.php?id=' . $bookId . '#reviews');
}

$st = $pdo->prepare('SELECT id FROM reviews WHERE user_id = ? AND book_id = ?');
$st->execute([(int)$user['id'], $bookId]);
if ($st->fetchColumn()) {
    flash_set('warning', 'You have already reviewed this book.');
    redirect('book-details.php?id=' . $bookId . '#reviews');
}

try {
    $st = $pdo->prepare('INSERT INTO reviews (user_id, book_id, rating, review_text) VALUES (?,?,?,?)');
    $st->execute([(int)$user['id'], $bookId, $rating, $reviewText]);
} catch (PDOException $ex) {
    if (($ex->errorInfo[1] ?? null) === 1062) {
        flash_set('warning', 'You have already reviewed this book.');
    } else {
        error_log((string)$ex);
        flash_set('error', 'Your review could not be saved. Please try again.');
    }
    redirect('book-details.php?id=' . $bookId . '#reviews');
}

flash_set('success', 'Thank you for sharing your review.');
redirect('book-details.php?id=' . $bookId . '#reviews');
