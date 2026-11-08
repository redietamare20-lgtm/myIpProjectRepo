<?php
// GET  api/cart.php?action=count|list
// POST api/cart.php  {action: add|set|remove, book_id, quantity}
define('IS_API', true);
require_once __DIR__ . '/../includes/auth.php';
$user = api_guard(['customer']);
$uid = (int)$user['id'];

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $items = cart_items($uid);
    $out = ['ok' => true, 'cart_count' => cart_count($uid), 'total' => cart_total($items)];
    if (($_GET['action'] ?? '') === 'list') $out['items'] = $items;
    json_out($out);
}
if ($_SERVER['REQUEST_METHOD'] !== 'POST') json_out(['ok' => false, 'message' => 'Method not allowed.'], 405);

$d = request_data();
$action = $d['action'] ?? '';
$bookId = (int)($d['book_id'] ?? 0);
$qty = (int)($d['quantity'] ?? 1);
if ($bookId < 1) json_out(['ok' => false, 'message' => 'Invalid book.'], 422);

if ($action === 'add')         $r = cart_add($uid, $bookId, $qty);
elseif ($action === 'set')     $r = cart_set($uid, $bookId, $qty);
elseif ($action === 'remove')  $r = cart_remove($uid, $bookId);
else json_out(['ok' => false, 'message' => 'Unknown action.'], 400);

$r['cart_count'] = cart_count($uid);
json_out($r, $r['ok'] ? 200 : 422);
