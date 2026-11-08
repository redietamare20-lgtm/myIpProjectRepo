<?php
require_once __DIR__ . '/../includes/auth.php';
$user = require_role('admin');
$list = notifications_list('admin', (int)$user['id'], 100);
$layout = 'admin'; $pageTitle = 'Notifications';
include __DIR__ . '/../includes/header.php';
?>
<div class="toolbar"><span class="muted">New orders, new customers, low stock and contact messages.</span>
  <button type="button" class="btn btn-outline btn-sm js-mark-all">Mark all as read</button></div>
<div data-live="notifs" data-live-id="admin-notifs">
<?php if (!$list): ?>
  <div class="empty-state card"><h3>No notifications</h3><p>You are all caught up.</p></div>
<?php else: ?>
  <ul class="notif-list card">
  <?php foreach ($list as $n): ?>
    <li class="<?= $n['is_read'] ? '' : 'unread' ?>">
      <div><?php if ($n['link']): ?><a href="<?= e(url($n['link'])) ?>"><?= e($n['message']) ?></a><?php else: echo e($n['message']); endif; ?>
      <small><?= e(fmt_date($n['created_at'])) ?></small></div>
      <?php if (!$n['is_read']): ?><button type="button" class="btn btn-outline btn-sm js-mark-read" data-id="<?= (int)$n['id'] ?>">Mark read</button><?php endif; ?>
    </li>
  <?php endforeach; ?>
  </ul>
<?php endif; ?>
</div>
<?php include __DIR__ . '/../includes/footer.php'; ?>
