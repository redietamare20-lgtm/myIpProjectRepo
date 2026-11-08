<?php
/** includes/header.php - page <head>, navigation. Set $pageTitle and optionally $layout = 'admin' before including. */
$user       = current_user();
$isAdmin    = (($layout ?? '') === 'admin');
$isCustomer = $user && $user['role'] === 'customer';
$cartCount  = $isCustomer ? cart_count((int)$user['id']) : 0;
$notifCount = $user ? notifications_unread($user['role'], (int)$user['id']) : 0;
$pageTitle  = $pageTitle ?? 'Online Bookstore';
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title><?= e($pageTitle) ?> | Online Bookstore</title>
  <meta name="csrf-token" content="<?= e(csrf_token()) ?>">
  <meta name="base-url" content="<?= e(BASE_URL) ?>">
  <link rel="icon" type="image/svg+xml" href="<?= e(url('assets/images/logo.svg')) ?>">
  <link rel="stylesheet" href="<?= e(url('assets/css/style.css')) ?>">
<?php if ($isAdmin): ?>
  <link rel="stylesheet" href="<?= e(url('assets/css/admin.css')) ?>">
<?php endif; ?>
  <link rel="stylesheet" href="<?= e(url('assets/css/responsive.css')) ?>">
</head>
<body class="<?= $isAdmin ? 'admin-body' : '' ?>" data-role="<?= e($user['role'] ?? 'guest') ?>" data-poll="4000">
<?php if ($isAdmin): ?>
<?php
  $script = basename($_SERVER['SCRIPT_NAME']);
  $nav = [
    'dashboard.php' => ['Dashboard', ['dashboard.php']],
    'books.php' => ['Books', ['books.php', 'add-book.php', 'edit-book.php']],
    'categories.php' => ['Categories & Authors', ['categories.php']],
    'inventory.php' => ['Inventory', ['inventory.php']],
    'orders.php' => ['Orders', ['orders.php', 'order-details.php']],
    'customers.php' => ['Customers', ['customers.php']],
    'reports.php' => ['Sales Reports', ['reports.php']],
    'notifications.php' => ['Notifications', ['notifications.php']],
    'settings.php' => ['Settings', ['settings.php']],
  ];
?>
<div class="admin-shell">
  <aside class="sidebar" id="sidebar">
    <a class="sidebar-brand" href="<?= e(url('admin/dashboard.php')) ?>"><img src="<?= e(url('assets/images/logo.svg')) ?>" alt="" width="28" height="28"> Bookstore Admin</a>
    <nav class="sidebar-nav">
<?php foreach ($nav as $file => $n): ?>
      <a href="<?= e(url('admin/' . $file)) ?>" class="<?= in_array($script, $n[1], true) ? 'active' : '' ?>">
        <?= e($n[0]) ?>
<?php if ($file === 'notifications.php'): ?><span class="count-badge js-notif-badge" <?= $notifCount ? '' : 'hidden' ?>><?= (int)$notifCount ?></span><?php endif; ?>
      </a>
<?php endforeach; ?>
    </nav>
    <div class="sidebar-footer">
      <a href="<?= e(url('index.php')) ?>" target="_blank" rel="noopener">View store &rarr;</a>
      <form method="post" action="<?= e(url('logout.php')) ?>"><?= csrf_field() ?><button class="btn btn-outline btn-block btn-sm" type="submit">Log out</button></form>
    </div>
  </aside>
  <div class="admin-main">
    <header class="admin-topbar">
      <button type="button" class="sidebar-toggle" aria-label="Toggle menu">&#9776;</button>
      <h1><?= e($pageTitle) ?></h1>
      <div class="topbar-right">
        <a class="icon-link" href="<?= e(url('admin/notifications.php')) ?>" title="Notifications">&#128276;<span class="count-badge js-notif-badge" <?= $notifCount ? '' : 'hidden' ?>><?= (int)$notifCount ?></span></a>
        <span class="topbar-user"><?= e($user['full_name'] ?? '') ?></span>
      </div>
    </header>
    <main class="admin-content">
      <?= flash_render() ?>
<?php else: ?>
<?php include __DIR__ . '/navbar.php'; ?>
<main class="container page">
  <?= flash_render() ?>
<?php endif; ?>
