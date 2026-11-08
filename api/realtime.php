<?php
// Polled every few seconds by assets/js/realtime.js.
// Returns small "change tokens"; the browser reloads a section only when its token changed.
// GET api/realtime.php?n=<last seen notification id, or -1 on first call>
define('IS_API', true);
require_once __DIR__ . '/../includes/auth.php';
$user = api_guard();   // guests are allowed (they only get the books token)
if ($_SERVER['REQUEST_METHOD'] !== 'GET') json_out(['ok' => false, 'message' => 'Method not allowed.'], 405);

$out = ['ok' => true, 'books' => books_token(), 'orders' => null, 'notifs' => null, 'cart_count' => null];

if ($user) {
    $uid = (int)$user['id'];
    $role = $user['role'];
    $out['orders'] = orders_token($role, $uid);
    if ($role === 'customer') $out['cart_count'] = cart_count($uid);

    [$w, $p] = notif_where($role, $uid);
    $pdo = getDB();
    $st = $pdo->prepare("SELECT COALESCE(MAX(id),0) AS max_id, COALESCE(SUM(is_read = 0),0) AS unread FROM notifications WHERE $w");
    $st->execute($p);
    $row = $st->fetch();
    $new = [];
    $last = (int)($_GET['n'] ?? -1);
    if ($last >= 0 && (int)$row['max_id'] > $last) {   // only fetch rows when something new exists
        $st = $pdo->prepare("SELECT id, type, message, link FROM notifications WHERE $w AND id > ? ORDER BY id ASC LIMIT 20");
        $st->execute(array_merge($p, [$last]));
        $new = $st->fetchAll();
    }
    $out['notifs'] = ['max_id' => (int)$row['max_id'], 'unread' => (int)$row['unread'], 'new' => $new];
}
json_out($out);
