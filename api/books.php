<?php
// GET api/books.php?q=&search_in=&category=&sort=&limit=   or   ?id=5
define('IS_API', true);
require_once __DIR__ . '/../includes/auth.php';
api_guard();
if ($_SERVER['REQUEST_METHOD'] !== 'GET') json_out(['ok' => false, 'message' => 'Method not allowed.'], 405);

if (isset($_GET['id'])) {
    $b = book_get((int)$_GET['id']);
    if (!$b) json_out(['ok' => false, 'message' => 'Book not found.'], 404);
    unset($b['description']);
    json_out(['ok' => true, 'book' => $b]);
}
$books = books_search([
    'q' => $_GET['q'] ?? '', 'search_in' => $_GET['search_in'] ?? 'all',
    'category' => $_GET['category'] ?? 0, 'sort' => $_GET['sort'] ?? 'newest', 'limit' => $_GET['limit'] ?? 100,
]);
json_out(['ok' => true, 'count' => count($books), 'books' => $books, 'token' => books_token()]);
