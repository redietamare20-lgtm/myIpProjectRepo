<?php
/**
 * includes/functions.php - reusable helper functions
 * (HTTP helpers, validation, uploads, books, cart, orders, notifications, stats)
 */
require_once __DIR__ . '/../config/database.php';

if (!defined('BASE_URL'))   define('BASE_URL', '/online_bookstore');
if (!defined('APP_DEBUG'))  define('APP_DEBUG', false);   // set true on your own PC to see errors on screen
define('UPLOAD_DIR', dirname(__DIR__) . '/uploads/books/');
define('ORDER_STATUSES', ['pending', 'approved', 'processing', 'shipped', 'delivered', 'rejected', 'cancelled']);
define('FINAL_STATUSES', ['delivered', 'rejected', 'cancelled']);
define('SALES_STATUSES', "'approved','processing','shipped','delivered'");
define('LOW_STOCK', 5);

error_reporting(E_ALL);
ini_set('display_errors', APP_DEBUG ? '1' : '0');
ini_set('log_errors', '1');

set_exception_handler(function ($ex) {
    error_log((string)$ex);
    if (!headers_sent()) { http_response_code(500); }
    if (defined('IS_API')) {
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['ok' => false, 'message' => 'Server error. Please try again.']);
    } else {
        echo '<h1>Something went wrong</h1><p>Please try again later.</p>';
        if (APP_DEBUG) echo '<pre>' . htmlspecialchars((string)$ex) . '</pre>';
    }
    exit;
});

/* ---------- HTTP / output helpers ---------- */
function e($v): string { return htmlspecialchars((string)$v, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); }
function url(string $p = ''): string { return BASE_URL . '/' . ltrim($p, '/'); }
function redirect(string $p): void { header('Location: ' . url($p)); exit; }
function money($n): string { return '$' . number_format((float)$n, 2); }
function clean_str($v, int $max = 255): string { return mb_substr(trim((string)$v), 0, $max); }
function fmt_date($d): string { return $d ? date('M j, Y g:i A', strtotime($d)) : ''; }

function flash_set(string $type, string $msg): void { $_SESSION['flash'][] = ['type' => $type, 'msg' => $msg]; }
function flash_render(): string {
    if (empty($_SESSION['flash'])) return '';
    $o = '';
    foreach ($_SESSION['flash'] as $f) {
        $o .= '<div class="alert alert-' . e($f['type']) . '" role="alert">' . e($f['msg']) . '</div>';
    }
    unset($_SESSION['flash']);
    return $o;
}

function json_out(array $data, int $code = 200): void {
    http_response_code($code);
    header('Content-Type: application/json; charset=utf-8');
    header('Cache-Control: no-store');
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}
function request_data(): array {
    $data = $_POST;
    $ct = $_SERVER['CONTENT_TYPE'] ?? '';
    if (stripos($ct, 'application/json') !== false) {
        $j = json_decode(file_get_contents('php://input'), true);
        if (is_array($j)) $data = $j;
    }
    return $data;
}
function safe_next(?string $n): ?string {
    if ($n && preg_match('~^[a-z0-9_\-/]+\.php(\?[a-zA-Z0-9_=&\-]*)?$~', $n) && strpos($n, '//') === false && $n[0] !== '/') return $n;
    return null;
}

/* ---------- Small render helpers ---------- */
function status_badge(string $s): string { return '<span class="badge badge-' . e($s) . '">' . e(ucfirst($s)) . '</span>'; }
function stock_badge(int $stock): string {
    if ($stock < 1) return '<span class="badge badge-rejected">Out of stock</span>';
    if ($stock <= LOW_STOCK) return '<span class="badge badge-pending">Only ' . $stock . ' left</span>';
    return '<span class="badge badge-delivered">In stock (' . $stock . ')</span>';
}
function cover_img(array $b, string $class = 'book-cover', bool $googleLookup = false): string {
    if (!empty($b['cover_image'])) {
        return '<img class="' . e($class) . '" src="' . e(url('uploads/books/' . $b['cover_image'])) . '" alt="' . e($b['title']) . '" loading="lazy">';
    }
    $lookupAttributes = $googleLookup
        ? ' data-google-cover data-title="' . e($b['title']) . '" data-author="' . e($b['author_name'] ?? '') . '" data-isbn="' . e($b['isbn'] ?? '') . '"'
        : '';
    return '<div class="' . e($class) . ' cover-placeholder"' . $lookupAttributes . '><span>' . e(mb_strtoupper(mb_substr($b['title'], 0, 1))) . '</span></div>';
}
function render_book_cards(array $books): string {
    if (!$books) {
        return '<div class="empty-state"><h3>No books found</h3><p>Try a different search or category.</p></div>';
    }
    $o = '<div class="book-grid">';
    foreach ($books as $b) {
        $out = (int)$b['stock'] < 1;
        $link = e(url('book-details.php?id=' . (int)$b['id']));
        $o .= '<article class="book-card"><a href="' . $link . '">' . cover_img($b, 'book-cover', true) . '</a><div class="book-card-body">'
            . '<span class="tag">' . e($b['category_name'] ?? 'Uncategorized') . '</span>'
            . '<h3 class="book-title"><a href="' . $link . '">' . e($b['title']) . '</a></h3>'
            . '<p class="book-author">' . e($b['author_name'] ?? 'Unknown author') . '</p>'
            . '<div class="book-meta"><span class="price">' . money($b['price']) . '</span>' . stock_badge((int)$b['stock']) . '</div>'
            . '<button type="button" class="btn btn-primary btn-block js-add-cart" data-id="' . (int)$b['id'] . '"' . ($out ? ' disabled' : '') . '>'
            . ($out ? 'Out of stock' : 'Add to cart') . '</button></div></article>';
    }
    return $o . '</div>';
}

/* ---------- Activity log ---------- */
function log_activity(?int $uid, string $action, ?string $details = null): void {
    $st = getDB()->prepare('INSERT INTO activity_logs (user_id, action, details, ip_address) VALUES (?,?,?,?)');
    $st->execute([$uid, $action, $details ? mb_substr($details, 0, 255) : null, $_SERVER['REMOTE_ADDR'] ?? null]);
}

/* ---------- Image upload (secure) ---------- */
function save_cover(array $file): ?string {
    if (!isset($file['error']) || $file['error'] === UPLOAD_ERR_NO_FILE) return null;
    if ($file['error'] !== UPLOAD_ERR_OK) throw new RuntimeException('Image upload failed. Try a smaller file.');
    if ($file['size'] > 2 * 1024 * 1024) throw new RuntimeException('Image must be 2 MB or smaller.');
    $mime = (new finfo(FILEINFO_MIME_TYPE))->file($file['tmp_name']);
    $allowed = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp'];
    if (!isset($allowed[$mime]) || @getimagesize($file['tmp_name']) === false) {
        throw new RuntimeException('Only JPG, PNG or WEBP images are allowed.');
    }
    if (!is_dir(UPLOAD_DIR)) mkdir(UPLOAD_DIR, 0755, true);
    $name = bin2hex(random_bytes(12)) . '.' . $allowed[$mime];
    if (!move_uploaded_file($file['tmp_name'], UPLOAD_DIR . $name)) throw new RuntimeException('Could not save the image.');
    return $name;
}
function delete_cover(?string $name): void {
    if ($name && preg_match('/^[a-f0-9]{24}\.(jpg|png|webp)$/', $name)) @unlink(UPLOAD_DIR . $name);
}

/* ---------- Books / categories / authors ---------- */
function books_filter_sql(array $f): array {
    $where = ' WHERE 1=1';
    $p = [];
    $q = clean_str($f['q'] ?? '', 100);
    if ($q !== '') {
        $like = '%' . addcslashes($q, '%_\\') . '%';
        $in = $f['search_in'] ?? 'all';
        if ($in === 'title')       { $where .= ' AND b.title LIKE ?'; $p[] = $like; }
        elseif ($in === 'author')  { $where .= ' AND a.name LIKE ?';  $p[] = $like; }
        else                       { $where .= ' AND (b.title LIKE ? OR a.name LIKE ?)'; $p[] = $like; $p[] = $like; }
    }
    if (!empty($f['category'])) { $where .= ' AND b.category_id = ?'; $p[] = (int)$f['category']; }
    foreach (['min_price' => '>=', 'max_price' => '<='] as $key => $operator) {
        if (isset($f[$key]) && is_numeric($f[$key]) && (float)$f[$key] >= 0 && (float)$f[$key] <= 100000) {
            $where .= ' AND b.price ' . $operator . ' ?';
            $p[] = (float)$f[$key];
        }
    }
    return [$where, $p];
}
function books_count(array $f = []): int {
    [$where, $p] = books_filter_sql($f);
    $st = getDB()->prepare('SELECT COUNT(*) FROM books b LEFT JOIN authors a ON a.id = b.author_id' . $where);
    $st->execute($p);
    return (int)$st->fetchColumn();
}
function books_search(array $f = []): array {
    $sql = 'SELECT b.*, a.name AS author_name, c.name AS category_name FROM books b
            LEFT JOIN authors a ON a.id = b.author_id LEFT JOIN categories c ON c.id = b.category_id';
    [$where, $p] = books_filter_sql($f);
    $sql .= $where;
    $sorts = ['newest' => 'b.created_at DESC, b.id DESC', 'price_asc' => 'b.price ASC, b.id ASC',
              'price_desc' => 'b.price DESC, b.id ASC', 'title' => 'b.title ASC'];
    $sql .= ' ORDER BY ' . ($sorts[$f['sort'] ?? 'newest'] ?? $sorts['newest']);
    $limit = max(1, min(200, (int)($f['limit'] ?? 100)));
    $offset = max(0, (int)($f['offset'] ?? 0));
    $sql .= ' LIMIT ' . $limit . ' OFFSET ' . $offset;
    $st = getDB()->prepare($sql);
    $st->execute($p);
    return $st->fetchAll();
}
function book_get(int $id): ?array {
    $st = getDB()->prepare('SELECT b.*, a.name AS author_name, c.name AS category_name FROM books b
        LEFT JOIN authors a ON a.id = b.author_id LEFT JOIN categories c ON c.id = b.category_id WHERE b.id = ?');
    $st->execute([$id]);
    return $st->fetch() ?: null;
}
function categories_all(): array { return getDB()->query('SELECT * FROM categories ORDER BY name')->fetchAll(); }
function authors_all(): array    { return getDB()->query('SELECT * FROM authors ORDER BY name')->fetchAll(); }

function save_book(?int $id, array $in, array $file): array {
    $pdo = getDB();
    $errors = [];
    $title = clean_str($in['title'] ?? '', 200);
    if ($title === '') $errors[] = 'Title is required.';
    $price = filter_var($in['price'] ?? '', FILTER_VALIDATE_FLOAT);
    if ($price === false || $price < 0 || $price > 100000) $errors[] = 'Enter a valid price (0 - 100000).';
    $stock = filter_var($in['stock'] ?? '', FILTER_VALIDATE_INT, ['options' => ['min_range' => 0, 'max_range' => 100000]]);
    if ($stock === false) $errors[] = 'Enter a valid stock quantity (whole number, 0 or more).';
    $isbn = clean_str($in['isbn'] ?? '', 20);
    if ($isbn !== '' && !preg_match('/^[0-9Xx\- ]+$/', $isbn)) $errors[] = 'ISBN may only contain digits, spaces, X and dashes.';
    $desc = clean_str($in['description'] ?? '', 5000);

    $catId = (int)($in['category_id'] ?? 0) ?: null;
    if ($catId) {
        $c = $pdo->prepare('SELECT id FROM categories WHERE id = ?'); $c->execute([$catId]);
        if (!$c->fetch()) $errors[] = 'Selected category does not exist.';
    }
    $authorId = (int)($in['author_id'] ?? 0) ?: null;
    $newAuthor = clean_str($in['new_author'] ?? '', 100);
    if ($errors) return ['ok' => false, 'errors' => $errors];

    $newCover = null;
    try { $newCover = save_cover($file); }
    catch (RuntimeException $ex) { return ['ok' => false, 'errors' => [$ex->getMessage()]]; }

    if ($newAuthor !== '') {
        $a = $pdo->prepare('SELECT id FROM authors WHERE name = ?'); $a->execute([$newAuthor]);
        $row = $a->fetch();
        if ($row) { $authorId = (int)$row['id']; }
        else { $pdo->prepare('INSERT INTO authors (name) VALUES (?)')->execute([$newAuthor]); $authorId = (int)$pdo->lastInsertId(); }
    } elseif ($authorId) {
        $a = $pdo->prepare('SELECT id FROM authors WHERE id = ?'); $a->execute([$authorId]);
        if (!$a->fetch()) $authorId = null;
    }

    if ($id === null) {
        $st = $pdo->prepare('INSERT INTO books (title, author_id, category_id, isbn, description, price, stock, cover_image) VALUES (?,?,?,?,?,?,?,?)');
        $st->execute([$title, $authorId, $catId, $isbn ?: null, $desc ?: null, $price, $stock, $newCover]);
        $id = (int)$pdo->lastInsertId();
    } else {
        $old = $pdo->prepare('SELECT cover_image FROM books WHERE id = ?'); $old->execute([$id]);
        $oldRow = $old->fetch();
        if (!$oldRow) return ['ok' => false, 'errors' => ['Book not found.']];
        $st = $pdo->prepare('UPDATE books SET title=?, author_id=?, category_id=?, isbn=?, description=?, price=?, stock=?, cover_image=? WHERE id=?');
        $st->execute([$title, $authorId, $catId, $isbn ?: null, $desc ?: null, $price, $stock, $newCover ?: $oldRow['cover_image'], $id]);
        if ($newCover) delete_cover($oldRow['cover_image']);
    }
    return ['ok' => true, 'id' => $id];
}

/* ---------- Notifications ---------- */
function notify_user(int $uid, string $type, string $msg, ?string $link = null): void {
    $st = getDB()->prepare('INSERT INTO notifications (user_id, target_role, type, message, link) VALUES (?, \'customer\', ?, ?, ?)');
    $st->execute([$uid, $type, mb_substr($msg, 0, 255), $link]);
}
function notify_admins(string $type, string $msg, ?string $link = null): void {
    $st = getDB()->prepare('INSERT INTO notifications (user_id, target_role, type, message, link) VALUES (NULL, \'admin\', ?, ?, ?)');
    $st->execute([$type, mb_substr($msg, 0, 255), $link]);
}
function notif_where(string $role, int $uid): array {
    return $role === 'admin' ? ['target_role = \'admin\'', []] : ['user_id = ?', [$uid]];
}
function notifications_list(string $role, int $uid, int $limit = 50): array {
    [$w, $p] = notif_where($role, $uid);
    $st = getDB()->prepare("SELECT * FROM notifications WHERE $w ORDER BY id DESC LIMIT " . (int)$limit);
    $st->execute($p);
    return $st->fetchAll();
}
function notifications_unread(string $role, int $uid): int {
    [$w, $p] = notif_where($role, $uid);
    $st = getDB()->prepare("SELECT COUNT(*) FROM notifications WHERE $w AND is_read = 0");
    $st->execute($p);
    return (int)$st->fetchColumn();
}
function notifications_mark(string $role, int $uid, ?int $id = null): void {
    [$w, $p] = notif_where($role, $uid);
    $sql = "UPDATE notifications SET is_read = 1 WHERE $w AND is_read = 0";
    if ($id) { $sql .= ' AND id = ?'; $p[] = $id; }
    getDB()->prepare($sql)->execute($p);
}

/* ---------- Cart ---------- */
function cart_id(int $uid): int {
    $pdo = getDB();
    $st = $pdo->prepare('SELECT id FROM cart WHERE user_id = ?'); $st->execute([$uid]);
    $id = $st->fetchColumn();
    if ($id) return (int)$id;
    $pdo->prepare('INSERT INTO cart (user_id) VALUES (?)')->execute([$uid]);
    return (int)$pdo->lastInsertId();
}
function cart_items(int $uid): array {
    $st = getDB()->prepare('SELECT ci.book_id, ci.quantity, b.title, b.price, b.stock, b.cover_image, a.name AS author_name
        FROM cart c JOIN cart_items ci ON ci.cart_id = c.id JOIN books b ON b.id = ci.book_id
        LEFT JOIN authors a ON a.id = b.author_id WHERE c.user_id = ? ORDER BY ci.id');
    $st->execute([$uid]);
    return $st->fetchAll();
}
function cart_count(int $uid): int {
    $st = getDB()->prepare('SELECT COALESCE(SUM(ci.quantity),0) FROM cart c JOIN cart_items ci ON ci.cart_id = c.id WHERE c.user_id = ?');
    $st->execute([$uid]);
    return (int)$st->fetchColumn();
}
function cart_total(array $items): float {
    $t = 0.0;
    foreach ($items as $i) $t += (float)$i['price'] * (int)$i['quantity'];
    return $t;
}
function cart_add(int $uid, int $bookId, int $qty): array {
    if ($qty < 1 || $qty > 99) return ['ok' => false, 'message' => 'Invalid quantity.'];
    $book = book_get($bookId);
    if (!$book) return ['ok' => false, 'message' => 'Book not found.'];
    if ((int)$book['stock'] < 1) return ['ok' => false, 'message' => 'Sorry, this book is out of stock.'];
    $cid = cart_id($uid);
    $st = getDB()->prepare('SELECT quantity FROM cart_items WHERE cart_id = ? AND book_id = ?');
    $st->execute([$cid, $bookId]);
    $have = (int)$st->fetchColumn();
    $new = $have + $qty;
    if ($new > (int)$book['stock']) {
        return ['ok' => false, 'message' => 'Only ' . (int)$book['stock'] . ' copies available' . ($have ? " (you already have $have in your cart)." : '.')];
    }
    getDB()->prepare('INSERT INTO cart_items (cart_id, book_id, quantity) VALUES (?,?,?) ON DUPLICATE KEY UPDATE quantity = VALUES(quantity)')
        ->execute([$cid, $bookId, $new]);
    return ['ok' => true, 'message' => '"' . $book['title'] . '" added to your cart.'];
}
function cart_set(int $uid, int $bookId, int $qty): array {
    if ($qty < 1) return cart_remove($uid, $bookId);
    $book = book_get($bookId);
    if (!$book) return ['ok' => false, 'message' => 'Book not found.'];
    if ($qty > (int)$book['stock']) return ['ok' => false, 'message' => 'Only ' . (int)$book['stock'] . ' copies available.'];
    $cid = cart_id($uid);
    getDB()->prepare('UPDATE cart_items SET quantity = ? WHERE cart_id = ? AND book_id = ?')->execute([$qty, $cid, $bookId]);
    return ['ok' => true, 'message' => 'Cart updated.'];
}
function cart_remove(int $uid, int $bookId): array {
    $cid = cart_id($uid);
    getDB()->prepare('DELETE FROM cart_items WHERE cart_id = ? AND book_id = ?')->execute([$cid, $bookId]);
    return ['ok' => true, 'message' => 'Item removed.'];
}

/* ---------- Orders ---------- */
function place_order(int $uid, string $customerName, string $address, string $phone, string $notes): array {
    $pdo = getDB();
    $lowStock = [];
    try {
        $pdo->beginTransaction();
        // Lock the cart's books so two customers cannot oversell the same stock
        $st = $pdo->prepare('SELECT ci.book_id, ci.quantity, b.title, b.price, b.stock
            FROM cart c JOIN cart_items ci ON ci.cart_id = c.id JOIN books b ON b.id = ci.book_id
            WHERE c.user_id = ? ORDER BY b.id FOR UPDATE');
        $st->execute([$uid]);
        $rows = $st->fetchAll();
        if (!$rows) throw new RuntimeException('Your cart is empty.');
        $total = 0.0;
        foreach ($rows as $r) {
            if ((int)$r['stock'] < (int)$r['quantity']) {
                throw new RuntimeException('Not enough stock for "' . $r['title'] . '". Available: ' . (int)$r['stock'] . '. Please update your cart.');
            }
            $total += (float)$r['price'] * (int)$r['quantity'];   // total is calculated on the server
        }
        $pdo->prepare('INSERT INTO orders (user_id, total_amount, status, shipping_address, phone, notes) VALUES (?,?,?,?,?,?)')
            ->execute([$uid, $total, 'pending', $address, $phone, $notes ?: null]);
        $oid = (int)$pdo->lastInsertId();
        $ins = $pdo->prepare('INSERT INTO order_items (order_id, book_id, book_title, quantity, unit_price) VALUES (?,?,?,?,?)');
        $dec = $pdo->prepare('UPDATE books SET stock = stock - ? WHERE id = ? AND stock >= ?');
        foreach ($rows as $r) {
            $ins->execute([$oid, $r['book_id'], $r['title'], $r['quantity'], $r['price']]);
            $dec->execute([$r['quantity'], $r['book_id'], $r['quantity']]);
            if ($dec->rowCount() !== 1) throw new RuntimeException('Stock changed while ordering "' . $r['title'] . '". Please try again.');
            $left = (int)$r['stock'] - (int)$r['quantity'];
            if ($left <= LOW_STOCK) $lowStock[] = [$r['title'], $left];
        }
        $pdo->prepare('INSERT INTO payments (order_id, method, status, amount) VALUES (?, \'cash_on_delivery\', \'pending\', ?)')->execute([$oid, $total]);
        $pdo->prepare('DELETE ci FROM cart_items ci JOIN cart c ON c.id = ci.cart_id WHERE c.user_id = ?')->execute([$uid]);
        $pdo->commit();
    } catch (Throwable $ex) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        if (!($ex instanceof RuntimeException)) error_log((string)$ex);
        return ['ok' => false, 'message' => $ex instanceof RuntimeException ? $ex->getMessage() : 'Could not place the order. Please try again.'];
    }
    notify_admins('new_order', 'New order #' . $oid . ' from ' . $customerName . ' - ' . money($total), 'admin/order-details.php?id=' . $oid);
    foreach ($lowStock as $ls) notify_admins('low_stock', 'Low stock: "' . $ls[0] . '" has ' . $ls[1] . ' left.', 'admin/inventory.php');
    log_activity($uid, 'order_placed', 'Order #' . $oid . ' total ' . $total);
    return ['ok' => true, 'order_id' => $oid, 'message' => 'Order placed successfully!'];
}

/** Change an order's status. $actorId = admin or the customer (for cancelling). */
function update_order_status(int $oid, string $new, int $actorId, ?int $onlyUserId = null): array {
    if (!in_array($new, ORDER_STATUSES, true)) return ['ok' => false, 'message' => 'Invalid status.'];
    $pdo = getDB();
    try {
        $pdo->beginTransaction();
        $st = $pdo->prepare('SELECT * FROM orders WHERE id = ? FOR UPDATE');
        $st->execute([$oid]);
        $o = $st->fetch();
        if (!$o || ($onlyUserId !== null && (int)$o['user_id'] !== $onlyUserId)) throw new RuntimeException('Order not found.');
        if ($onlyUserId !== null && $o['status'] !== 'pending') throw new RuntimeException('Only pending orders can be cancelled.');
        if ($o['status'] === $new) throw new RuntimeException('The order already has this status.');
        if (in_array($o['status'], FINAL_STATUSES, true)) throw new RuntimeException('This order is already ' . $o['status'] . ' and cannot be changed.');
        if ($new === 'pending') throw new RuntimeException('An order cannot go back to pending.');

        $pdo->prepare('UPDATE orders SET status = ? WHERE id = ?')->execute([$new, $oid]);
        if ($new === 'rejected' || $new === 'cancelled') {   // give the stock back
            $items = $pdo->prepare('SELECT book_id, quantity FROM order_items WHERE order_id = ? AND book_id IS NOT NULL');
            $items->execute([$oid]);
            $up = $pdo->prepare('UPDATE books SET stock = stock + ? WHERE id = ?');
            foreach ($items->fetchAll() as $it) $up->execute([$it['quantity'], $it['book_id']]);
            $pdo->prepare('UPDATE payments SET status = \'failed\' WHERE order_id = ?')->execute([$oid]);
        } elseif ($new === 'delivered') {
            $pdo->prepare('UPDATE payments SET status = \'paid\', paid_at = NOW() WHERE order_id = ?')->execute([$oid]);
        }
        $pdo->commit();
    } catch (Throwable $ex) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        if (!($ex instanceof RuntimeException)) error_log((string)$ex);
        return ['ok' => false, 'message' => $ex instanceof RuntimeException ? $ex->getMessage() : 'Could not update the order.'];
    }
    if ($onlyUserId === null) {
        notify_user((int)$o['user_id'], 'order_status', 'Your order #' . $oid . ' is now ' . $new . '.', 'customer/order-details.php?id=' . $oid);
    } else {
        notify_admins('order_cancelled', 'Order #' . $oid . ' was cancelled by the customer.', 'admin/order-details.php?id=' . $oid);
    }
    log_activity($actorId, 'order_status', 'Order #' . $oid . ' -> ' . $new);
    return ['ok' => true, 'message' => 'Order #' . $oid . ' is now ' . $new . '.'];
}

/* ---------- Dashboard statistics ---------- */
function admin_stats(): array {
    return getDB()->query("SELECT
        (SELECT COUNT(*) FROM books) AS books,
        (SELECT COUNT(*) FROM users WHERE role = 'customer') AS customers,
        (SELECT COUNT(*) FROM orders) AS orders,
        (SELECT COALESCE(SUM(total_amount),0) FROM orders WHERE status IN (" . SALES_STATUSES . ")) AS sales,
        (SELECT COUNT(*) FROM orders WHERE status = 'pending') AS pending,
        (SELECT COUNT(*) FROM books WHERE stock <= " . LOW_STOCK . ") AS low_stock")->fetch();
}

/* ---------- Real-time change tokens (cheap checksums) ---------- */
function books_token(): string {
    $r = getDB()->query("SELECT
        (SELECT COUNT(*) FROM books) AS c,
        (SELECT COALESCE(SUM(CRC32(CONCAT_WS('|', id, title, author_id, category_id, price, stock, cover_image, description))),0) FROM books) AS s,
        (SELECT COALESCE(SUM(CRC32(CONCAT_WS('|', id, name))),0) FROM categories) AS k,
        (SELECT COALESCE(SUM(CRC32(CONCAT_WS('|', id, name))),0) FROM authors) AS a")->fetch();
    return $r['c'] . '-' . $r['s'] . '-' . $r['k'] . '-' . $r['a'];
}
function orders_token(string $role, int $uid): string {
    if ($role === 'admin') {
        $r = getDB()->query("SELECT COUNT(*) AS c, COALESCE(SUM(CRC32(CONCAT_WS('|', id, status,
                (SELECT p.status FROM payments p WHERE p.order_id = orders.id LIMIT 1)))),0) AS s,
            (SELECT COUNT(*) FROM users WHERE role='customer') AS u FROM orders")->fetch();
        return $r['c'] . '-' . $r['s'] . '-' . $r['u'];
    }
    $st = getDB()->prepare("SELECT COUNT(*) AS c, COALESCE(SUM(CRC32(CONCAT_WS('|', id, status,
        (SELECT p.status FROM payments p WHERE p.order_id = orders.id LIMIT 1)))),0) AS s FROM orders WHERE user_id = ?");
    $st->execute([$uid]);
    $r = $st->fetch();
    return $r['c'] . '-' . $r['s'];
}
