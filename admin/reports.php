<?php
require_once __DIR__ . '/../includes/auth.php';
$user = require_role('admin');
$pdo = getDB();
$valid = function ($d) { $x = DateTime::createFromFormat('Y-m-d', (string)$d); return $x && $x->format('Y-m-d') === $d; };
$to = $valid($_GET['to'] ?? '') ? $_GET['to'] : date('Y-m-d');
$from = $valid($_GET['from'] ?? '') ? $_GET['from'] : date('Y-m-d', strtotime('-29 days'));
if ($from > $to) { $t = $from; $from = $to; $to = $t; }
$range = [$from . ' 00:00:00', $to . ' 23:59:59'];

$st = $pdo->prepare('SELECT COUNT(*) AS n, COALESCE(SUM(total_amount),0) AS revenue FROM orders WHERE status IN (' . SALES_STATUSES . ') AND created_at BETWEEN ? AND ?');
$st->execute($range);
$sum = $st->fetch();
$avg = $sum['n'] ? $sum['revenue'] / $sum['n'] : 0;

$st = $pdo->prepare('SELECT DATE(created_at) AS d, COUNT(*) AS n, SUM(total_amount) AS s FROM orders WHERE status IN (' . SALES_STATUSES . ') AND created_at BETWEEN ? AND ? GROUP BY DATE(created_at) ORDER BY d');
$st->execute($range);
$daily = $st->fetchAll();
$maxDay = 0; foreach ($daily as $d) $maxDay = max($maxDay, (float)$d['s']);

$st = $pdo->prepare('SELECT oi.book_title, SUM(oi.quantity) AS qty, SUM(oi.quantity * oi.unit_price) AS revenue
    FROM order_items oi JOIN orders o ON o.id = oi.order_id
    WHERE o.status IN (' . SALES_STATUSES . ') AND o.created_at BETWEEN ? AND ?
    GROUP BY oi.book_id, oi.book_title ORDER BY qty DESC, revenue DESC LIMIT 10');
$st->execute($range);
$top = $st->fetchAll();

$st = $pdo->prepare('SELECT COALESCE(c.name, \'Uncategorized\') AS cat, SUM(oi.quantity * oi.unit_price) AS revenue
    FROM order_items oi JOIN orders o ON o.id = oi.order_id LEFT JOIN books b ON b.id = oi.book_id LEFT JOIN categories c ON c.id = b.category_id
    WHERE o.status IN (' . SALES_STATUSES . ') AND o.created_at BETWEEN ? AND ? GROUP BY cat ORDER BY revenue DESC');
$st->execute($range);
$byCat = $st->fetchAll();

$st = $pdo->prepare('SELECT status, COUNT(*) AS n FROM orders WHERE created_at BETWEEN ? AND ? GROUP BY status ORDER BY n DESC');
$st->execute($range);
$byStatus = $st->fetchAll();

$layout = 'admin'; $pageTitle = 'Sales reports';
include __DIR__ . '/../includes/header.php';
?>
<form class="toolbar" method="get">
  <label class="inline-label">From <input type="date" name="from" value="<?= e($from) ?>"></label>
  <label class="inline-label">To <input type="date" name="to" value="<?= e($to) ?>"></label>
  <button class="btn btn-primary" type="submit">Show report</button>
</form>
<p class="muted">Sales count orders that are approved, processing, shipped or delivered.</p>
<div class="stat-grid">
  <div class="stat-card"><span class="stat-label">Orders in range</span><span class="stat-value"><?= (int)$sum['n'] ?></span></div>
  <div class="stat-card"><span class="stat-label">Revenue</span><span class="stat-value"><?= money($sum['revenue']) ?></span></div>
  <div class="stat-card"><span class="stat-label">Average order</span><span class="stat-value"><?= money($avg) ?></span></div>
</div>
<section class="card">
  <h2>Revenue by day</h2>
  <?php if (!$daily): ?><div class="empty-state"><p>No sales in this period.</p></div><?php else: ?>
  <div class="bars"><?php foreach ($daily as $d): $pct = $maxDay > 0 ? round($d['s'] / $maxDay * 100) : 0; ?>
    <div class="bar-row"><span class="bar-label"><?= e($d['d']) ?></span><div class="bar-track"><div class="bar-fill" style="width: <?= (int)$pct ?>%"></div></div><span class="bar-value"><?= money($d['s']) ?> (<?= (int)$d['n'] ?>)</span></div>
  <?php endforeach; ?></div><?php endif; ?>
</section>
<div class="two-col">
  <section class="card"><h2>Top selling books</h2>
    <?php if (!$top): ?><div class="empty-state"><p>No data.</p></div><?php else: ?>
    <div class="table-wrap"><table class="table"><thead><tr><th>Book</th><th>Sold</th><th>Revenue</th></tr></thead><tbody>
      <?php foreach ($top as $t): ?><tr><td><?= e($t['book_title']) ?></td><td><?= (int)$t['qty'] ?></td><td><?= money($t['revenue']) ?></td></tr><?php endforeach; ?></tbody></table></div><?php endif; ?>
  </section>
  <section class="card"><h2>Revenue by category</h2>
    <?php if (!$byCat): ?><div class="empty-state"><p>No data.</p></div><?php else: ?>
    <div class="table-wrap"><table class="table"><thead><tr><th>Category</th><th>Revenue</th></tr></thead><tbody>
      <?php foreach ($byCat as $c): ?><tr><td><?= e($c['cat']) ?></td><td><?= money($c['revenue']) ?></td></tr><?php endforeach; ?></tbody></table></div><?php endif; ?>
    <h2>Orders by status</h2>
    <?php if (!$byStatus): ?><div class="empty-state"><p>No orders.</p></div><?php else: ?>
    <ul class="summary-list"><?php foreach ($byStatus as $s): ?><li><?= status_badge($s['status']) ?><span><?= (int)$s['n'] ?></span></li><?php endforeach; ?></ul><?php endif; ?>
  </section>
</div>
<?php include __DIR__ . '/../includes/footer.php'; ?>
