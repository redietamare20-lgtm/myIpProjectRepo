<?php
require_once __DIR__ . '/includes/auth.php';
$errors = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_require();
    $name = clean_str($_POST['name'] ?? '', 80);
    $email = clean_str($_POST['email'] ?? '', 150);
    $msg = clean_str($_POST['message'] ?? '', 1000);
    if (mb_strlen($name) < 2) $errors[] = 'Please enter your name.';
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = 'Please enter a valid email.';
    if (mb_strlen($msg) < 10) $errors[] = 'Message must be at least 10 characters.';
    if (!$errors) {
        // The message is delivered to the administrators as a notification.
        notify_admins('contact', 'Message from ' . $name . ' (' . $email . '): ' . mb_substr($msg, 0, 130));
        flash_set('success', 'Thank you! Your message was sent.');
        redirect('contact.php');
    }
}
$pageTitle = 'Contact';
include __DIR__ . '/includes/header.php';
?>
<h1 class="page-title">Contact us</h1>
<div class="auth-card wide">
  <?php foreach ($errors as $er): ?><div class="alert alert-error"><?= e($er) ?></div><?php endforeach; ?>
  <form method="post" action="">
    <?= csrf_field() ?>
    <label>Your name<input type="text" name="name" required maxlength="80" value="<?= e($_POST['name'] ?? ($user['full_name'] ?? '')) ?>"></label>
    <label>Email<input type="email" name="email" required maxlength="150" value="<?= e($_POST['email'] ?? ($user['email'] ?? '')) ?>"></label>
    <label>Message<textarea name="message" rows="5" required minlength="10" maxlength="1000"><?= e($_POST['message'] ?? '') ?></textarea></label>
    <button class="btn btn-primary" type="submit">Send message</button>
  </form>
</div>
<?php include __DIR__ . '/includes/footer.php'; ?>
