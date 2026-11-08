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
<section class="contact-intro">
  <div><span class="page-kicker">We&rsquo;re listening</span><h1 class="page-title">Let&rsquo;s talk<br><em>about books.</em></h1>
    <p>Need help with an order, looking for a title, or want to share a thought? Send us a note and our store team will see it.</p>
  </div>
  <div class="contact-intro-note"><span class="contact-note-icon" aria-hidden="true"><svg viewBox="0 0 24 24"><path d="M4 5.5h16v13H4zM4 7l8 6 8-6"/></svg></span><strong>A direct line to our team</strong><span>Your message is delivered to store administrators.</span></div>
</section>
<div class="contact-layout">
  <section class="contact-form-card">
    <div class="contact-form-heading"><span class="page-kicker">Send us a message</span><h2>How can we help?</h2><p>Share a few details below. Fields marked required need to be completed.</p></div>
    <?php foreach ($errors as $er): ?><div class="alert alert-error" role="alert"><?= e($er) ?></div><?php endforeach; ?>
    <form method="post" action="<?= e(url('contact.php')) ?>">
      <?= csrf_field() ?>
      <div class="contact-field-row">
        <label>Your name <span aria-hidden="true">*</span><input type="text" name="name" required maxlength="80" autocomplete="name" placeholder="How should we address you?" value="<?= e($_POST['name'] ?? ($user['full_name'] ?? '')) ?>"></label>
        <label>Email address <span aria-hidden="true">*</span><input type="email" name="email" required maxlength="150" autocomplete="email" placeholder="you@example.com" value="<?= e($_POST['email'] ?? ($user['email'] ?? '')) ?>"></label>
      </div>
      <label>Your message <span aria-hidden="true">*</span><textarea name="message" rows="6" required minlength="10" maxlength="1000" placeholder="Tell us what is on your mind..."><?= e($_POST['message'] ?? '') ?></textarea><small>At least 10 characters, up to 1000.</small></label>
      <div class="contact-submit-row"><span>Your details are used to respond to your message.</span><button class="btn btn-primary" type="submit">Send message <span aria-hidden="true">&rarr;</span></button></div>
    </form>
  </section>
  <aside class="contact-side">
    <div class="contact-help-card"><span class="contact-card-number">01</span><span class="page-kicker">Order support</span><h2>A question about an order?</h2><p>Add your order number to help us find the right details.</p><?php if ($user): ?><a href="<?= e(url('customer/orders.php')) ?>">View your orders <span aria-hidden="true">&rarr;</span></a><?php else: ?><a href="<?= e(url('login.php?next=customer%2Forders.php')) ?>">Sign in to view orders <span aria-hidden="true">&rarr;</span></a><?php endif; ?></div>
    <div class="contact-help-card contact-help-card-alt"><span class="contact-card-number">02</span><span class="page-kicker">Looking for a book?</span><h2>Give us a few clues.</h2><p>Include a title, author, or subject and we&rsquo;ll understand what you&rsquo;re after.</p><a href="<?= e(url('books.php')) ?>">Browse the collection <span aria-hidden="true">&rarr;</span></a></div>
    <p class="contact-notice"><span aria-hidden="true">i</span> Messages are sent through the store and appear in the administrator notifications.</p>
  </aside>
</div>
<?php include __DIR__ . '/includes/footer.php'; ?>
