<?php
require_once __DIR__ . '/includes/auth.php';
if ($u = current_user()) redirect(home_for($u['role']));
$error = null;
$next = safe_next($_GET['next'] ?? $_POST['next'] ?? null);
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_require();
    $email = clean_str($_POST['email'] ?? '', 150);
    $error = login_user($email, (string)($_POST['password'] ?? ''));
    if (!$error) {
        $u = current_user(true);
        if ($u && $u['role'] === 'customer' && $next) redirect($next);
        redirect(home_for($u['role'] ?? 'customer'));
    }
}
$pageTitle = 'Login';
include __DIR__ . '/includes/header.php';
?>
<div class="auth-card">
  <h1>Welcome back</h1>
  <p class="muted">Log in to manage orders, track deliveries, and continue shopping.</p>
  <?php if ($error): ?><div class="alert alert-error" role="alert"><?= e($error) ?></div><?php endif; ?>
  <form method="post" action="">
    <?= csrf_field() ?>
    <input type="hidden" name="next" value="<?= e($next ?? '') ?>">
    <label>Email<input type="email" name="email" required maxlength="150" autocomplete="email" value="<?= e($_POST['email'] ?? '') ?>"></label>
    <label>Password<input type="password" name="password" required autocomplete="current-password"></label>
    <button class="btn btn-primary btn-block" type="submit">Log in</button>
  </form>
  <p class="auth-alt">New here? <a href="<?= e(url('register.php')) ?>">Create an account</a></p>
</div>
<?php include __DIR__ . '/includes/footer.php'; ?>
