<?php
require_once __DIR__ . '/../includes/auth.php';
$user = require_role('admin');
$pdo = getDB();
$errors = [];
$formName = '';
$formEmail = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_require();
    $action = (string)($_POST['action'] ?? '');

    if ($action === 'create') {
        $formName = clean_str($_POST['full_name'] ?? '', 100);
        $formEmail = strtolower(clean_str($_POST['email'] ?? '', 150));
        $password = (string)($_POST['password'] ?? '');
        if (mb_strlen($formName) < 2) $errors[] = 'Enter the administrator’s full name.';
        if (!filter_var($formEmail, FILTER_VALIDATE_EMAIL)) $errors[] = 'Enter a valid email address.';
        if ($error = validate_password($password)) $errors[] = $error;

        if (!$errors) {
            $check = $pdo->prepare('SELECT id FROM users WHERE email = ?');
            $check->execute([$formEmail]);
            if ($check->fetch()) {
                $errors[] = 'An account with that email already exists.';
            } else {
                try {
                    $insert = $pdo->prepare("INSERT INTO users (full_name, email, password_hash, role, status) VALUES (?, ?, ?, 'admin', 'active')");
                    $insert->execute([$formName, $formEmail, password_hash($password, PASSWORD_DEFAULT)]);
                    $newAdminId = (int)$pdo->lastInsertId();
                    log_activity((int)$user['id'], 'admin_created', 'Admin #' . $newAdminId);
                    flash_set('success', 'Administrator account created.');
                    redirect('admin/admin-users.php');
                } catch (PDOException $exception) {
                    if ($exception->getCode() === '23000') {
                        $errors[] = 'An account with that email already exists.';
                    } else {
                        throw $exception;
                    }
                }
            }
        }
    } elseif ($action === 'set_status') {
        $adminId = filter_var($_POST['admin_id'] ?? '', FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
        $newStatus = (string)($_POST['status'] ?? '');
        if ($adminId === false || !in_array($newStatus, ['active', 'blocked'], true)) {
            $errors[] = 'Choose a valid administrator and account status.';
        } elseif ((int)$adminId === (int)$user['id']) {
            $errors[] = 'You cannot change your own administrator status.';
        } else {
            $pdo->beginTransaction();
            try {
                $admins = $pdo->query("SELECT id, status FROM users WHERE role = 'admin' FOR UPDATE")->fetchAll();
                $target = null;
                $activeCount = 0;
                foreach ($admins as $admin) {
                    if ($admin['status'] === 'active') $activeCount++;
                    if ((int)$admin['id'] === (int)$adminId) $target = $admin;
                }
                if (!$target) {
                    $errors[] = 'Administrator account not found.';
                } elseif ($target['status'] === $newStatus) {
                    $errors[] = 'That administrator already has the selected status.';
                } elseif ($newStatus === 'blocked' && $target['status'] === 'active' && $activeCount <= 1) {
                    $errors[] = 'The last active administrator cannot be blocked.';
                } else {
                    $update = $pdo->prepare("UPDATE users SET status = ? WHERE id = ? AND role = 'admin'");
                    $update->execute([$newStatus, $adminId]);
                }
                if ($errors) {
                    $pdo->rollBack();
                } else {
                    $pdo->commit();
                    log_activity((int)$user['id'], 'admin_' . $newStatus, 'Admin #' . (int)$adminId);
                    flash_set('success', 'Administrator account is now ' . $newStatus . '.');
                    redirect('admin/admin-users.php');
                }
            } catch (Throwable $exception) {
                if ($pdo->inTransaction()) $pdo->rollBack();
                throw $exception;
            }
        }
    } else {
        $errors[] = 'Unknown administrator action.';
    }
}

$admins = $pdo->query("SELECT id, full_name, email, status, created_at FROM users WHERE role = 'admin' ORDER BY id ASC")->fetchAll();
$layout = 'admin';
$pageTitle = 'Admin users';
include __DIR__ . '/../includes/header.php';
?>
<p class="page-lead">Create administrator access and manage existing admin accounts.</p>
<?php foreach ($errors as $error): ?><div class="alert alert-error" role="alert"><?= e($error) ?></div><?php endforeach; ?>
<div class="two-col">
  <section class="card">
    <h2>Add administrator</h2>
    <p class="muted">New administrators receive access to the management dashboard.</p>
    <form method="post" action="<?= e(url('admin/admin-users.php')) ?>">
      <?= csrf_field() ?><input type="hidden" name="action" value="create">
      <label>Full name<input type="text" name="full_name" required minlength="2" maxlength="100" autocomplete="name" value="<?= e($formName) ?>"></label>
      <label>Email address<input type="email" name="email" required maxlength="150" autocomplete="email" value="<?= e($formEmail) ?>"></label>
      <label>Temporary password<input type="password" name="password" required minlength="8" autocomplete="new-password"><small class="muted">At least 8 characters, including letters and numbers.</small></label>
      <button class="btn btn-primary" type="submit">Create admin account</button>
    </form>
  </section>
  <section class="card">
    <h2>Access and safety</h2>
    <ul class="summary-list">
      <li><span>Current account</span><strong><?= e($user['full_name']) ?></strong></li>
      <li><span>Active administrators</span><strong><?= count(array_filter($admins, static function ($admin) { return $admin['status'] === 'active'; })) ?></strong></li>
      <li><span>Account passwords</span><strong>Hashed before storage</strong></li>
    </ul>
    <p class="muted">Your own access cannot be changed here, and the final active administrator cannot be blocked.</p>
  </section>
</div>
<section class="card">
  <h2>Administrator accounts</h2>
  <?php if (!$admins): ?>
    <div class="empty-state"><p>No administrator accounts found.</p></div>
  <?php else: ?>
    <div class="table-wrap"><table class="table">
      <thead><tr><th>Name</th><th>Email</th><th>Status</th><th>Joined</th><th>Access</th></tr></thead>
      <tbody>
      <?php foreach ($admins as $admin): ?>
        <tr>
          <td><?= e($admin['full_name']) ?><?= (int)$admin['id'] === (int)$user['id'] ? ' (you)' : '' ?></td>
          <td><?= e($admin['email']) ?></td>
          <td><span class="badge badge-<?= $admin['status'] === 'active' ? 'delivered' : 'rejected' ?>"><?= e(ucfirst($admin['status'])) ?></span></td>
          <td><?= e(fmt_date($admin['created_at'])) ?></td>
          <td>
            <?php if ((int)$admin['id'] === (int)$user['id']): ?>
              <span class="muted">Current account</span>
            <?php else: ?>
              <form method="post" class="inline-form" data-confirm="Change this administrator’s access?">
                <?= csrf_field() ?><input type="hidden" name="action" value="set_status"><input type="hidden" name="admin_id" value="<?= (int)$admin['id'] ?>">
                <input type="hidden" name="status" value="<?= $admin['status'] === 'active' ? 'blocked' : 'active' ?>">
                <button class="btn btn-sm <?= $admin['status'] === 'active' ? 'btn-danger' : 'btn-primary' ?>" type="submit"><?= $admin['status'] === 'active' ? 'Block access' : 'Restore access' ?></button>
              </form>
            <?php endif; ?>
          </td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table></div>
  <?php endif; ?>
</section>
<?php include __DIR__ . '/../includes/footer.php'; ?>
