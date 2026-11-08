<?php
/** includes/auth.php - sessions, login, registration, role checks */
require_once __DIR__ . '/functions.php';

if (session_status() === PHP_SESSION_NONE) {
    ini_set('session.use_strict_mode', '1');
    session_set_cookie_params([
        'lifetime' => 0, 'path' => '/', 'httponly' => true, 'samesite' => 'Lax',
        'secure' => !empty($_SERVER['HTTPS']),
    ]);
    session_start();
}
require_once __DIR__ . '/csrf.php';

// Log out after 2 hours without activity
if (!empty($_SESSION['last_active']) && time() - $_SESSION['last_active'] > 7200) {
    $_SESSION = [];
}
$_SESSION['last_active'] = time();

header('X-Frame-Options: SAMEORIGIN');
header('X-Content-Type-Options: nosniff');
header('Referrer-Policy: same-origin');
header("Content-Security-Policy: default-src 'self'; img-src 'self' data:; style-src 'self' 'unsafe-inline' https://fonts.googleapis.com; font-src 'self' https://fonts.gstatic.com; script-src 'self'; form-action 'self'; frame-ancestors 'self'");

/** The logged-in user row (re-checked in the database on every request, so blocked users are kicked out). */
function current_user(bool $refresh = false): ?array {
    static $cache = false;
    if ($refresh) $cache = false;
    if ($cache !== false) return $cache;
    if (empty($_SESSION['uid'])) return $cache = null;
    $st = getDB()->prepare('SELECT id, full_name, email, phone, address, role, status FROM users WHERE id = ?');
    $st->execute([(int)$_SESSION['uid']]);
    $u = $st->fetch();
    if (!$u || $u['status'] !== 'active') { unset($_SESSION['uid']); return $cache = null; }
    return $cache = $u;
}
function home_for(string $role): string { return $role === 'admin' ? 'admin/dashboard.php' : 'customer/dashboard.php'; }

function require_login(): array {
    $u = current_user();
    if (!$u) {
        flash_set('warning', 'Please log in to continue.');
        $next = ltrim(str_replace(BASE_URL, '', $_SERVER['REQUEST_URI'] ?? ''), '/');
        redirect('login.php' . (safe_next($next) ? '?next=' . urlencode($next) : ''));
    }
    return $u;
}
function require_role(string $role): array {
    $u = require_login();
    if ($u['role'] !== $role) redirect(home_for($u['role']));
    return $u;
}

function validate_password(string $pw): ?string {
    if (strlen($pw) < 8) return 'Password must be at least 8 characters.';
    if (!preg_match('/[A-Za-z]/', $pw) || !preg_match('/[0-9]/', $pw)) return 'Password must contain letters and numbers.';
    return null;
}

/** Returns an error message, or null on success. */
function login_user(string $email, string $password): ?string {
    $now = time();
    if (($_SESSION['login_lock'] ?? 0) > $now) return 'Too many failed attempts. Please wait a minute and try again.';
    $st = getDB()->prepare('SELECT * FROM users WHERE email = ?');
    $st->execute([strtolower(trim($email))]);
    $u = $st->fetch();
    if (!$u || !password_verify($password, $u['password_hash'])) {
        $_SESSION['login_fail'] = ($_SESSION['login_fail'] ?? 0) + 1;
        if ($_SESSION['login_fail'] >= 5) { $_SESSION['login_lock'] = $now + 60; $_SESSION['login_fail'] = 0; }
        return 'Incorrect email or password.';
    }
    if ($u['status'] !== 'active') return 'This account has been blocked. Please contact support.';
    session_regenerate_id(true);
    unset($_SESSION['login_fail'], $_SESSION['login_lock'], $_SESSION['csrf']);
    $_SESSION['uid'] = (int)$u['id'];
    log_activity((int)$u['id'], 'login');
    return null;
}

/** Returns [errors[], newUserId|null] */
function register_user(array $in): array {
    $errors = [];
    $name  = clean_str($in['full_name'] ?? '', 100);
    $email = strtolower(clean_str($in['email'] ?? '', 150));
    $phone = clean_str($in['phone'] ?? '', 30);
    $pw    = (string)($in['password'] ?? '');
    if (mb_strlen($name) < 2) $errors[] = 'Please enter your full name.';
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = 'Please enter a valid email address.';
    if ($phone !== '' && !preg_match('/^[0-9+\-\s()]{7,20}$/', $phone)) $errors[] = 'Please enter a valid phone number.';
    if ($e = validate_password($pw)) $errors[] = $e;
    if ($pw !== (string)($in['password2'] ?? '')) $errors[] = 'Passwords do not match.';
    if ($errors) return [$errors, null];

    $pdo = getDB();
    $st = $pdo->prepare('SELECT id FROM users WHERE email = ?'); $st->execute([$email]);
    if ($st->fetch()) return [['An account with this email already exists.'], null];
    $pdo->prepare('INSERT INTO users (full_name, email, password_hash, phone, role) VALUES (?,?,?,?, \'customer\')')
        ->execute([$name, $email, password_hash($pw, PASSWORD_DEFAULT), $phone ?: null]);
    $id = (int)$pdo->lastInsertId();
    notify_admins('new_customer', 'New customer registered: ' . $name, 'admin/customers.php?id=' . $id);
    log_activity($id, 'register');
    return [[], $id];
}

function change_password(int $uid, string $current, string $new, string $confirm): ?string {
    $st = getDB()->prepare('SELECT password_hash FROM users WHERE id = ?'); $st->execute([$uid]);
    $hash = $st->fetchColumn();
    if (!$hash || !password_verify($current, $hash)) return 'Current password is incorrect.';
    if ($e = validate_password($new)) return $e;
    if ($new !== $confirm) return 'New passwords do not match.';
    getDB()->prepare('UPDATE users SET password_hash = ? WHERE id = ?')->execute([password_hash($new, PASSWORD_DEFAULT), $uid]);
    log_activity($uid, 'password_changed');
    return null;
}

/**
 * Guard for API endpoints: checks the HTTP method, CSRF (for POST) and optional roles,
 * then releases the session lock so polling requests never block each other.
 * Returns the user (or null for guests when $roles is empty).
 */
function api_guard(array $roles = []): ?array {
    $u = current_user();
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && !csrf_valid()) {
        json_out(['ok' => false, 'message' => 'Security check failed. Refresh the page and try again.'], 419);
    }
    if ($roles) {
        if (!$u) json_out(['ok' => false, 'message' => 'Please log in first.', 'login_required' => true], 401);
        if (!in_array($u['role'], $roles, true)) json_out(['ok' => false, 'message' => 'You do not have permission.'], 403);
    }
    session_write_close();
    return $u;
}
