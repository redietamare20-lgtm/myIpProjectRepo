<?php
// GET  api/orders.php?action=list           (customer: own orders)
// POST api/orders.php {action: place, address, phone, notes}        (customer)
// POST api/orders.php {action: cancel, order_id}                    (customer, pending only)
// POST api/orders.php {action: status, order_id, status}            (admin)
define('IS_API', true);
require_once __DIR__ . '/../includes/auth.php';
$user = api_guard(['customer', 'admin']);
$uid = (int)$user['id'];

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    if ($user['role'] === 'admin') {
        $st = getDB()->query('SELECT o.id, o.total_amount, o.status, o.created_at, u.full_name FROM orders o JOIN users u ON u.id = o.user_id ORDER BY o.id DESC LIMIT 100');
    } else {
        $st = getDB()->prepare('SELECT id, total_amount, status, created_at, updated_at FROM orders WHERE user_id = ? ORDER BY id DESC LIMIT 100');
        $st->execute([$uid]);
    }
    json_out(['ok' => true, 'orders' => $st->fetchAll(), 'token' => orders_token($user['role'], $uid)]);
}
if ($_SERVER['REQUEST_METHOD'] !== 'POST') json_out(['ok' => false, 'message' => 'Method not allowed.'], 405);

$d = request_data();
$action = $d['action'] ?? '';

if ($action === 'place') {
    if ($user['role'] !== 'customer') json_out(['ok' => false, 'message' => 'Only customers can place orders.'], 403);
    $address = clean_str($d['address'] ?? '', 255);
    $phone = clean_str($d['phone'] ?? '', 30);
    $notes = clean_str($d['notes'] ?? '', 255);
    $errors = [];
    if (mb_strlen($address) < 5) $errors[] = 'Please enter your delivery address.';
    if (!preg_match('/^[0-9+\-\s()]{7,20}$/', $phone)) $errors[] = 'Please enter a valid phone number.';
    if ($errors) json_out(['ok' => false, 'message' => implode(' ', $errors)], 422);
    $r = place_order($uid, $user['full_name'], $address, $phone, $notes);
    json_out($r, $r['ok'] ? 201 : 422);
}
if ($action === 'cancel') {
    if ($user['role'] !== 'customer') json_out(['ok' => false, 'message' => 'Not allowed.'], 403);
    $r = update_order_status((int)($d['order_id'] ?? 0), 'cancelled', $uid, $uid);
    json_out($r, $r['ok'] ? 200 : 422);
}
if ($action === 'status') {
    if ($user['role'] !== 'admin') json_out(['ok' => false, 'message' => 'Admins only.'], 403);
    $r = update_order_status((int)($d['order_id'] ?? 0), (string)($d['status'] ?? ''), $uid);
    json_out($r, $r['ok'] ? 200 : 422);
}
json_out(['ok' => false, 'message' => 'Unknown action.'], 400);
