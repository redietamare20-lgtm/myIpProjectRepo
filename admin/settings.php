<?php
require_once __DIR__ . '/../includes/auth.php';
$user = require_role('admin');
$uid = (int)$user['id'];
$errors = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_require();
    if (($_POST['form'] ?? '') === 'profile') {
        $name = clean_str($_POST['full_name'] ?? '', 100);
        if (mb_strlen($name) < 2) $errors[] = 'Please enter your name.';
        else {
            getDB()->prepare('UPDATE users SET full_name = ? WHERE id = ?')->execute([$name, $uid]);
            flash_set('success', 'Profile updated.');
            redirect('admin/settings.php');
        }
    } elseif (($_POST['form'] ?? '') === 'password') {
        $err = change_password($uid, (string)($_POST['current'] ?? ''), (string)($_POST['new'] ?? ''), (string)($_POST['confirm'] ?? ''));
        if ($err) $errors[] = $err;
        else { flash_set('success', 'Password changed.'); redirect('admin/settings.php'); }
    }
}
$layout = 'admin'; $pageTitle = 'Settings';
include __DIR__ . '/../includes/header.php';
?>
<?php foreach ($errors as $er): ?><div class="alert alert-error"><?= e($er) ?></div><?php endforeach; ?>
<div class="two-col">
  <section class="card"><h2>Admin profile</h2>
    <form method="post" action=""><?= csrf_field() ?><input type="hidden" name="form" value="profile">
      <label>Full name<input type="text" name="full_name" required maxlength="100" value="<?= e($user['full_name']) ?>"></label>
      <label>Email<input type="email" value="<?= e($user['email']) ?>" disabled></label>
      <button class="btn btn-primary" type="submit">Save</button></form>
  </section>
  <section class="card"><h2>Change password</h2>
    <form method="post" action=""><?= csrf_field() ?><input type="hidden" name="form" value="password">
      <label>Current password<input type="password" name="current" required autocomplete="current-password"></label>
      <label>New password<input type="password" name="new" required minlength="8" autocomplete="new-password"></label>
      <label>Confirm new password<input type="password" name="confirm" required minlength="8" autocomplete="new-password"></label>
      <button class="btn btn-primary" type="submit">Change password</button></form>
  </section>
</div>
<?php include __DIR__ . '/../includes/footer.php'; ?>
