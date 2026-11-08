<?php /** includes/navbar.php - public/customer navigation (uses $user, $isCustomer, $cartCount, $notifCount from header.php) */ ?>
<header class="site-header">
  <div class="container nav-wrap">
    <a class="brand" href="<?= e(url('index.php')) ?>"><img src="<?= e(url('assets/images/logo.svg')) ?>" alt="" width="30" height="30"> <span>Online Bookstore</span></a>
    <button type="button" class="nav-toggle" aria-label="Toggle menu">&#9776;</button>
    <nav class="main-nav" id="main-nav">
      <a href="<?= e(url('index.php')) ?>">Home</a>
      <a href="<?= e(url('books.php')) ?>">Books</a>
      <a href="<?= e(url('about.php')) ?>">About</a>
      <a href="<?= e(url('contact.php')) ?>">Contact</a>
      <form class="nav-search" action="<?= e(url('books.php')) ?>" method="get" role="search">
        <input type="search" name="q" placeholder="Search books..." aria-label="Search books" maxlength="100">
      </form>
<?php if ($isCustomer): ?>
      <a class="icon-link" href="<?= e(url('customer/cart.php')) ?>" title="Shopping cart">&#128722;<span class="count-badge js-cart-count" <?= $cartCount ? '' : 'hidden' ?>><?= (int)$cartCount ?></span></a>
<?php endif; ?>
<?php if ($user): ?>
      <a class="icon-link" href="<?= e(url($user['role'] === 'admin' ? 'admin/notifications.php' : 'customer/notifications.php')) ?>" title="Notifications">&#128276;<span class="count-badge js-notif-badge" <?= $notifCount ? '' : 'hidden' ?>><?= (int)$notifCount ?></span></a>
      <a href="<?= e(url(home_for($user['role']))) ?>">My account</a>
      <form method="post" action="<?= e(url('logout.php')) ?>" class="inline-form"><?= csrf_field() ?><button class="btn btn-outline btn-sm" type="submit">Log out</button></form>
<?php else: ?>
      <a class="btn btn-outline btn-sm" href="<?= e(url('login.php')) ?>">Login</a>
      <a class="btn btn-primary btn-sm" href="<?= e(url('register.php')) ?>">Register</a>
<?php endif; ?>
    </nav>
  </div>
</header>
