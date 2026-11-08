<?php
require_once __DIR__ . '/../includes/auth.php';
$user = require_role('customer');
$uid = (int)$user['id'];
$errors = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_require();
    if (($_POST['form'] ?? '') === 'profile') {
        $name = clean_str($_POST['full_name'] ?? '', 100);
        $phone = clean_str($_POST['phone'] ?? '', 30);
        $address = clean_str($_POST['address'] ?? '', 255);
        if (mb_strlen($name) < 2) $errors[] = 'Please enter your name.';
        if ($phone !== '' && !preg_match('/^[0-9+\-\s()]{7,20}$/', $phone)) $errors[] = 'Please enter a valid phone number.';
        if (!$errors) {
            getDB()->prepare('UPDATE users SET full_name = ?, phone = ?, address = ? WHERE id = ?')->execute([$name, $phone ?: null, $address ?: null, $uid]);
            flash_set('success', 'Profile updated.');
            redirect('customer/profile.php');
        }
    } elseif (($_POST['form'] ?? '') === 'password') {
        $err = change_password($uid, (string)($_POST['current'] ?? ''), (string)($_POST['new'] ?? ''), (string)($_POST['confirm'] ?? ''));
        if ($err) $errors[] = $err;
        else { flash_set('success', 'Password changed.'); redirect('customer/profile.php'); }
    }
}
$pageTitle = 'My profile';
include __DIR__ . '/../includes/header.php';
?>
<h1 class="page-title">My profile</h1>
<?php foreach ($errors as $er): ?><div class="alert alert-error"><?= e($er) ?></div><?php endforeach; ?>
<div class="two-col">
  <section class="card">
    <h2>Personal information</h2>
    <form method="post" action="">
      <?= csrf_field() ?><input type="hidden" name="form" value="profile">
      <label>Full name<input type="text" name="full_name" required maxlength="100" value="<?= e($user['full_name']) ?>"></label>
      <label>Email<input type="email" value="<?= e($user['email']) ?>" disabled></label>
      <label>Phone<input type="tel" name="phone" maxlength="30" value="<?= e($user['phone'] ?? '') ?>"></label>
      <label>Address<input type="text" name="address" maxlength="255" value="<?= e($user['address'] ?? '') ?>"></label>
      <button class="btn btn-primary" type="submit">Save changes</button>
    </form>
  </section>
  <section class="card">
    <h2>Change password</h2>
    <form method="post" action="">
      <?= csrf_field() ?><input type="hidden" name="form" value="password">
      <label>Current password<input type="password" name="current" required autocomplete="current-password"></label>
      <label>New password<input type="password" name="new" required minlength="8" autocomplete="new-password"></label>
      <label>Confirm new password<input type="password" name="confirm" required minlength="8" autocomplete="new-password"></label>
      <button class="btn btn-primary" type="submit">Change password</button>
    </form>
  </section>
</div>
<?php include __DIR__ . '/../includes/footer.php'; ?>
