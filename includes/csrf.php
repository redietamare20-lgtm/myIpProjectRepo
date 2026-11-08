<?php
/** includes/csrf.php - CSRF protection helpers */
function csrf_token(): string {
    if (empty($_SESSION['csrf'])) $_SESSION['csrf'] = bin2hex(random_bytes(32));
    return $_SESSION['csrf'];
}
function csrf_field(): string {
    return '<input type="hidden" name="csrf_token" value="' . e(csrf_token()) . '">';
}
function csrf_valid(): bool {
    $t = $_POST['csrf_token'] ?? ($_SERVER['HTTP_X_CSRF_TOKEN'] ?? '');
    return is_string($t) && $t !== '' && !empty($_SESSION['csrf']) && hash_equals($_SESSION['csrf'], $t);
}
/** For normal pages: stop the request if the token is wrong. */
function csrf_require(): void {
    if (!csrf_valid()) {
        http_response_code(419);
        die('Security check failed (invalid or expired form token). Go back, refresh the page and try again.');
    }
}
