<?php
require_once __DIR__ . '/includes/auth.php';
if ($u = current_user()) redirect(home_for($u['role']));
$errors = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_require();
    [$errors, $newId] = register_user($_POST);
    if (!$errors) {
        $err = login_user((string)$_POST['email'], (string)$_POST['password']);
        if (!$err) { flash_set('success', 'Welcome! Your account has been created.'); redirect('customer/dashboard.php'); }
        flash_set('success', 'Account created. Please log in.');
        redirect('login.php');
    }
}
$pageTitle = 'Register';
include __DIR__ . '/includes/header.php';
?>
<div class="auth-card">
  <h1>Create your account</h1>
  <p class="muted">Join to save your details, place orders, and track your delivery status.</p>
  <?php foreach ($errors as $er): ?><div class="alert alert-error" role="alert"><?= e($er) ?></div><?php endforeach; ?>
  <form method="post" action="">
    <?= csrf_field() ?>
    <label>Full name<input type="text" name="full_name" required maxlength="100" value="<?= e($_POST['full_name'] ?? '') ?>"></label>
    <label>Email<input type="email" name="email" required maxlength="150" autocomplete="email" value="<?= e($_POST['email'] ?? '') ?>"></label>
    <label>Phone (optional)<input type="tel" name="phone" maxlength="30" value="<?= e($_POST['phone'] ?? '') ?>"></label>
    <label>Password<input type="password" name="password" required minlength="8" autocomplete="new-password"><small class="muted">At least 8 characters, with letters and numbers.</small></label>
    <label>Confirm password<input type="password" name="password2" required minlength="8" autocomplete="new-password"></label>
    <button class="btn btn-primary btn-block" type="submit">Create account</button>
  </form>
  <p class="auth-alt">Already registered? <a href="<?= e(url('login.php')) ?>">Log in</a></p>
</div>
<?php include __DIR__ . '/includes/footer.php'; ?>
