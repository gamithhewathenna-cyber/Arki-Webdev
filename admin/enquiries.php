<?php
/**
 * Enquiries from the Contact page form.
 */
declare(strict_types=1);
require __DIR__ . '/_init.php';

const ENQ_STATUSES = ['new' => 'New', 'read' => 'Read', 'replied' => 'Replied', 'archived' => 'Archived'];

if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
    csrf_verify();
    $ids = array_values(array_filter(array_map('intval', (array)($_POST['ids'] ?? [$_POST['id'] ?? 0]))));
    $do = (string)($_POST['do'] ?? '');
    if ($ids) {
        $in = implode(',', $ids);
        if (isset(ENQ_STATUSES[$do])) {
            Database::run("UPDATE enquiries SET status = ?, updated_at = NOW() WHERE id IN ($in)", [$do]);
            flash('success', count($ids) === 1 ? 'Enquiry marked as ' . strtolower(ENQ_STATUSES[$do]) . '.' : count($ids) . ' enquiries updated.');
        } elseif ($do === 'delete') {
            Database::run("DELETE FROM enquiries WHERE id IN ($in)");
            flash('success', count($ids) === 1 ? 'Enquiry deleted.' : count($ids) . ' enquiries deleted.');
            redirect('enquiries.php');
        }
    }
    redirect('enquiries.php' . (!empty($_POST['back']) ? '?' . preg_replace('/[^a-z0-9=&_]/i', '', (string)$_POST['back']) : ''));
}

// CSV export of the current filter
$status = isset(ENQ_STATUSES[$_GET['status'] ?? '']) ? (string)$_GET['status'] : '';
$q = mb_substr(trim((string)($_GET['q'] ?? '')), 0, 100);
$where = ['1 = 1'];
$args = [];
if ($status !== '') { $where[] = 'status = ?'; $args[] = $status; }
if ($status === '' && empty($_GET['all'])) { $where[] = "status <> 'archived'"; }
if ($q !== '') {
    $like = '%' . str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], $q) . '%';
    $where[] = '(first_name LIKE ? OR last_name LIKE ? OR email LIKE ? OR message LIKE ? OR topic LIKE ?)';
    array_push($args, $like, $like, $like, $like, $like);
}
$whereSql = implode(' AND ', $where);

if (isset($_GET['export'])) {
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="enquiries-' . date('Y-m-d') . '.csv"');
    $out = fopen('php://output', 'w');
    fwrite($out, "\xEF\xBB\xBF"); // Excel: UTF-8
    fputcsv($out, ['Date', 'First name', 'Last name', 'Email', 'Phone', 'Topic', 'Message', 'Wants updates', 'Status']);
    foreach (Database::all("SELECT * FROM enquiries WHERE $whereSql ORDER BY created_at DESC", $args) as $r) {
        // Prefix cells that spreadsheet apps would treat as formulas.
        $safe = static fn($v) => preg_match('/^[=+\-@\t\r]/', (string)$v) ? "'" . $v : (string)$v;
        fputcsv($out, array_map($safe, [$r['created_at'], $r['first_name'], $r['last_name'], $r['email'], $r['phone'], $r['topic'], $r['message'], $r['newsletter'] ? 'Yes' : 'No', ENQ_STATUSES[$r['status']] ?? $r['status']]));
    }
    exit;
}

$per = 30;
$page = max(1, (int)($_GET['page'] ?? 1));
$total = (int)Database::value("SELECT COUNT(*) FROM enquiries WHERE $whereSql", $args);
$rows = Database::all("SELECT * FROM enquiries WHERE $whereSql ORDER BY created_at DESC LIMIT $per OFFSET " . (($page - 1) * $per), $args);
$counts = array_column(Database::all('SELECT status, COUNT(*) n FROM enquiries GROUP BY status'), 'n', 'status');

// Opening an enquiry marks it as read.
$view = (int)($_GET['view'] ?? 0);
$current = $view ? Database::one('SELECT * FROM enquiries WHERE id = ?', [$view]) : null;
if ($current && $current['status'] === 'new') {
    Database::run("UPDATE enquiries SET status = 'read', updated_at = NOW() WHERE id = ?", [$view]);
    $current['status'] = 'read';
}
$qs = static fn(array $x = []) => '?' . http_build_query(array_filter($x + ['status' => $status, 'q' => $q]));

admin_header('Enquiries', 'enquiries');
?>
<header class="adm-head">
    <div>
        <h1>Enquiries</h1>
        <p class="muted">Messages sent through the Contact page form. <?= (int)($counts['new'] ?? 0) ?> new.
            Email copies: set the address under <a href="<?= e(url('admin/?page=contact')) ?>">Contact Page</a> → Introduction &amp; Form.</p>
    </div>
    <a class="adm-btn adm-btn--ghost" href="<?= e($qs(['export' => 1])) ?>">Download CSV</a>
</header>

<form class="adm-card adm-filters" method="get">
    <input type="search" name="q" value="<?= e($q) ?>" placeholder="Search name, email or message…" aria-label="Search enquiries">
    <select name="status" aria-label="Status">
        <option value="">Inbox (all except archived)</option>
        <?php foreach (ENQ_STATUSES as $k => $l): ?><option value="<?= $k ?>"<?= $status === $k ? ' selected' : '' ?>><?= $l ?> (<?= (int)($counts[$k] ?? 0) ?>)</option><?php endforeach; ?>
    </select>
    <button class="adm-btn adm-btn--sm" type="submit">Filter</button>
    <?php if ($status !== '' || $q !== ''): ?><a class="adm-link" href="enquiries.php">Clear</a><?php endif; ?>
</form>

<?php if ($current): ?>
<section class="adm-card adm-enquiry">
    <div class="adm-enquiry__head">
        <div>
            <h2><?= e($current['first_name'] . ' ' . $current['last_name']) ?></h2>
            <p class="muted small"><?= e(date('j M Y, g:i a', strtotime($current['created_at']))) ?><?= $current['topic'] ? ' · ' . e($current['topic']) : '' ?></p>
        </div>
        <a class="adm-link" href="<?= e($qs()) ?>">Close</a>
    </div>
    <dl class="adm-enquiry__facts">
        <div><dt>Email</dt><dd><a href="mailto:<?= e($current['email']) ?>"><?= e($current['email']) ?></a></dd></div>
        <div><dt>Phone</dt><dd><?= $current['phone'] ? '<a href="tel:' . e(preg_replace('/[^0-9+]/', '', $current['phone'])) . '">' . e($current['phone']) . '</a>' : '–' ?></dd></div>
        <div><dt>Wants updates</dt><dd><?= $current['newsletter'] ? 'Yes' : 'No' ?></dd></div>
        <div><dt>Email copy</dt><dd><?= $current['emailed'] ? 'Sent' : 'Not sent' ?></dd></div>
    </dl>
    <div class="adm-enquiry__message"><?= nl2br(e($current['message']), false) ?></div>
    <form method="post" class="adm-actions">
        <?= csrf_field() ?><input type="hidden" name="id" value="<?= (int)$current['id'] ?>"><input type="hidden" name="back" value="view=<?= (int)$current['id'] ?>">
        <a class="adm-btn" href="mailto:<?= e($current['email']) ?>?subject=<?= rawurlencode('Re: your enquiry' . ($current['topic'] ? ' – ' . $current['topic'] : '')) ?>">Reply by email</a>
        <button class="adm-btn adm-btn--ghost" name="do" value="replied">Mark as replied</button>
        <button class="adm-btn adm-btn--ghost" name="do" value="archived">Archive</button>
        <button class="adm-btn adm-btn--danger" name="do" value="delete" data-confirm-click="Delete this enquiry? This cannot be undone.">Delete</button>
    </form>
</section>
<?php endif; ?>

<form method="post" class="adm-card adm-card--flush">
    <?= csrf_field() ?>
    <div class="adm-card__head adm-bulkbar">
        <div class="adm-bulkbar__left">
            <select name="do" aria-label="Bulk action">
                <option value="">Bulk actions…</option>
                <?php foreach (ENQ_STATUSES as $k => $l): ?><option value="<?= $k ?>">Mark as <?= strtolower($l) ?></option><?php endforeach; ?>
                <option value="delete">Delete</option>
            </select>
            <button class="adm-btn adm-btn--sm adm-btn--ghost" type="submit" data-confirm-click="Apply this action to the ticked enquiries?">Apply</button>
        </div>
        <p class="muted small"><?= $total ?> enquir<?= $total === 1 ? 'y' : 'ies' ?></p>
    </div>
    <?php if (!$rows): ?>
    <p class="adm-empty"><?= ($status !== '' || $q !== '') ? 'No enquiries match.' : 'No enquiries yet. They appear here when someone uses the form on the Contact page.' ?></p>
    <?php else: ?>
    <div class="adm-table-wrap">
    <table class="adm-table adm-table--enquiries">
        <thead><tr><th class="col-check"><input type="checkbox" data-check-all aria-label="Select all"></th><th>From</th><th>Topic</th><th>Message</th><th>Received</th><th>Status</th></tr></thead>
        <tbody>
        <?php foreach ($rows as $r): $isNew = $r['status'] === 'new'; ?>
            <tr class="<?= $isNew ? 'is-new' : '' ?><?= $current && (int)$current['id'] === (int)$r['id'] ? ' is-current' : '' ?>">
                <td class="col-check"><input type="checkbox" name="ids[]" value="<?= (int)$r['id'] ?>" data-bulk-item aria-label="Select enquiry from <?= e($r['first_name']) ?>"></td>
                <td><a class="adm-strong" href="<?= e($qs(['view' => (int)$r['id'], 'page' => $page > 1 ? $page : null])) ?>"><?= e($r['first_name'] . ' ' . $r['last_name']) ?></a><br><span class="muted small"><?= e($r['email']) ?></span></td>
                <td class="small"><?= e($r['topic'] ?: '–') ?></td>
                <td class="small adm-excerpt"><?= e(mb_strimwidth(preg_replace('/\s+/', ' ', $r['message']), 0, 110, '…')) ?></td>
                <td class="small adm-nowrap"><?= e(date('j M Y, g:i a', strtotime($r['created_at']))) ?></td>
                <td><span class="adm-pill adm-pill--<?= e($r['status']) ?>"><?= e(ENQ_STATUSES[$r['status']] ?? $r['status']) ?></span></td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
    </div>
    <?php endif; ?>
</form>

<?php $pages = (int)ceil($total / $per); if ($pages > 1): ?>
<nav class="adm-pager" aria-label="Pages">
    <?php for ($n = 1; $n <= $pages; $n++): ?>
        <?php if ($n === $page): ?><span class="is-current"><?= $n ?></span><?php else: ?><a href="<?= e($qs(['page' => $n])) ?>"><?= $n ?></a><?php endif; ?>
    <?php endfor; ?>
</nav>
<?php endif; ?>
<?php admin_footer(); ?>
