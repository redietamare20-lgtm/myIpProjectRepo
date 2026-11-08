<?php /** includes/navbar.php - public/customer navigation (uses $user, $isCustomer, $cartCount, $notifCount from header.php) */
$current = basename($_SERVER['SCRIPT_NAME']);
$is = function (string $file) use ($current): string { return $current === $file ? ' aria-current="page" class="nav-active"' : ''; };
?>
<header class="site-header">
  <div class="container nav-wrap">
    <a class="brand" href="<?= e(url('index.php')) ?>"><img src="<?= e(url('assets/images/logo.svg')) ?>" alt="" width="30" height="30"> <span>Online Bookstore</span></a>
    <button type="button" class="nav-toggle" aria-label="Toggle menu" aria-expanded="false"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M4 7h16M4 12h16M4 17h16"/></svg></button>
    <nav class="main-nav" id="main-nav" aria-label="Main">
      <a href="<?= e(url('index.php')) ?>"<?= $is('index.php') ?>>Home</a>
      <a href="<?= e(url('books.php')) ?>"<?= $is('books.php') ?><?= $current === 'book-details.php' ? ' aria-current="page" class="nav-active"' : '' ?>>Books</a>
      <a href="<?= e(url('about.php')) ?>"<?= $is('about.php') ?>>About</a>
      <a href="<?= e(url('contact.php')) ?>"<?= $is('contact.php') ?>>Contact</a>
      <form class="nav-search" action="<?= e(url('books.php')) ?>" method="get" role="search">
        <input type="search" name="q" placeholder="Search books..." aria-label="Search books" maxlength="100">
      </form>
<?php if ($isCustomer): ?>
      <a class="icon-link" href="<?= e(url('customer/cart.php')) ?>" title="Shopping cart" aria-label="Shopping cart"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M3 4h2l2.2 11.1a2 2 0 0 0 2 1.6h8.9a2 2 0 0 0 1.9-1.4L22 8H6"/><circle cx="10" cy="20" r="1"/><circle cx="18" cy="20" r="1"/></svg><span class="count-badge js-cart-count" <?= $cartCount ? '' : 'hidden' ?>><?= (int)$cartCount ?></span></a>
<?php endif; ?>
<?php if ($user): ?>
      <a class="icon-link" href="<?= e(url($user['role'] === 'admin' ? 'admin/notifications.php' : 'customer/notifications.php')) ?>" title="Notifications" aria-label="Notifications"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M18 9a6 6 0 0 0-12 0c0 7-3 7-3 9h18c0-2-3-2-3-9M10 21h4"/></svg><span class="count-badge js-notif-badge" <?= $notifCount ? '' : 'hidden' ?>><?= (int)$notifCount ?></span></a>
      <a href="<?= e(url(home_for($user['role']))) ?>">My account</a>
      <form method="post" action="<?= e(url('logout.php')) ?>" class="inline-form"><?= csrf_field() ?><button class="btn btn-outline btn-sm" type="submit">Log out</button></form>
<?php else: ?>
      <a class="btn btn-outline btn-sm" href="<?= e(url('login.php')) ?>">Login</a>
      <a class="btn btn-primary btn-sm" href="<?= e(url('register.php')) ?>">Register</a>
<?php endif; ?>
    </nav>
  </div>
</header>
