<?php
// GET  api/notifications.php            -> latest notifications + unread count
// POST api/notifications.php {action: mark_read, id} | {action: mark_all}
define('IS_API', true);
require_once __DIR__ . '/../includes/auth.php';
$user = api_guard(['customer', 'admin']);
$uid = (int)$user['id'];

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    json_out(['ok' => true, 'unread' => notifications_unread($user['role'], $uid), 'notifications' => notifications_list($user['role'], $uid, 30)]);
}
if ($_SERVER['REQUEST_METHOD'] !== 'POST') json_out(['ok' => false, 'message' => 'Method not allowed.'], 405);

$d = request_data();
if (($d['action'] ?? '') === 'mark_read') {
    notifications_mark($user['role'], $uid, (int)($d['id'] ?? 0) ?: null);
} elseif (($d['action'] ?? '') === 'mark_all') {
    notifications_mark($user['role'], $uid, null);
} else {
    json_out(['ok' => false, 'message' => 'Unknown action.'], 400);
}
json_out(['ok' => true, 'unread' => notifications_unread($user['role'], $uid)]);
