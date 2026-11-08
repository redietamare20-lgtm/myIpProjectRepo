<?php /** includes/footer.php - closes the layout and loads scripts */
$isAdmin = (($layout ?? '') === 'admin');
$role = $user['role'] ?? 'guest';
?>
<?php if ($isAdmin): ?>
    </main>
  </div>
</div>
<?php else: ?>
</main>
<footer class="site-footer">
  <div class="container footer-wrap">
    <div><strong>Online Bookstore</strong><p>Browse, search and order books online.</p></div>
    <div class="footer-links"><a href="<?= e(url('books.php')) ?>">Books</a><a href="<?= e(url('about.php')) ?>">About</a><a href="<?= e(url('contact.php')) ?>">Contact</a></div>
    <p class="copyright">&copy; <?= date('Y') ?> Online Bookstore Management System</p>
  </div>
</footer>
<?php endif; ?>
<div id="toasts" class="toasts" aria-live="polite"></div>
<script src="<?= e(url('assets/js/main.js')) ?>"></script>
<?php if ($role !== 'admin'): ?><script src="<?= e(url('assets/js/cart.js')) ?>"></script><?php endif; ?>
<?php if ($role === 'admin'): ?><script src="<?= e(url('assets/js/admin.js')) ?>"></script><?php endif; ?>
<script src="<?= e(url('assets/js/realtime.js')) ?>"></script>
</body>
</html>
