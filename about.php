<?php
require_once __DIR__ . '/includes/auth.php';
$pageTitle = 'About';
include __DIR__ . '/includes/header.php';
?>
<h1 class="page-title">About us</h1>
<div class="card prose">
  <p>Online Bookstore is a database-driven web shop built with HTML, CSS, JavaScript, PHP and MySQL.</p>
  <p>Customers can search and order books, and administrators manage books, stock, customers and orders. Prices, stock and order statuses update automatically on every open page, without refreshing.</p>
  <h3>How ordering works</h3>
  <p>Add books to your cart, check out with cash on delivery, and follow your order status from your account. We confirm every order before it ships.</p>
</div>
<?php include __DIR__ . '/includes/footer.php'; ?>
