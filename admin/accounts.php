<?php
/**
 * Website accounts: customers and agents (separate lists).
 * Agents register as "pending" and are approved or rejected here.
 */
declare(strict_types=1);
require __DIR__ . '/_init.php';

$type = ($_GET['type'] ?? $_POST['type'] ?? 'customer') === 'agent' ? 'agent' : 'customer';
$self = 'accounts.php?type=' . $type;

if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
    csrf_verify();
    $ids = array_values(array_filter(array_map('intval', (array)($_POST['ids'] ?? []))));
    $do = (string)($_POST['do'] ?? '');
    // A button on a single row ("approve:12") wins over the bulk selection.
    if (preg_match('/^(approve|reject|activate|deactivate):(\d+)$/', (string)($_POST['row'] ?? ''), $m)) {
        $do = $m[1];
        $ids = [(int)$m[2]];
    }
    $map = ['approve' => 'active', 'activate' => 'active', 'reject' => 'rejected', 'deactivate' => 'inactive'];
    if ($ids && isset($map[$do])) {
        foreach ($ids as $id) {
            if (Database::value('SELECT account_type FROM users WHERE id = ?', [$id]) === $type) {
                UserAuth::setStatus($id, $map[$do], (int)$ADMIN['id']);
            }
        }
        $verb = ['approve' => 'approved', 'activate' => 'activated', 'reject' => 'rejected', 'deactivate' => 'deactivated'][$do];
        flash('success', (count($ids) === 1 ? 'Account ' : count($ids) . ' accounts ') . $verb . '.');
    } elseif ($ids && $do === 'delete') {
        $in = implode(',', $ids);
        Database::run("DELETE FROM users WHERE id IN ($in) AND account_type = ?", [$type]);
        flash('success', count($ids) === 1 ? 'Account deleted.' : count($ids) . ' accounts deleted.');
    } else {
        flash('error', 'Choose an action and at least one account.');
    }
    $back = preg_replace('/[^a-z0-9=&_]/i', '', (string)($_POST['back'] ?? ''));
    redirect($self . ($back !== '' ? '&' . $back : ''));
}

$status = isset(UserAuth::STATUSES[$_GET['status'] ?? '']) ? (string)$_GET['status'] : '';
$q = mb_substr(trim((string)($_GET['q'] ?? '')), 0, 100);
$where = ['account_type = ?'];
$args = [$type];
if ($status !== '') { $where[] = 'status = ?'; $args[] = $status; }
if ($q !== '') {
    $like = '%' . str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], $q) . '%';
    $where[] = '(first_name LIKE ? OR last_name LIKE ? OR email LIKE ? OR company LIKE ? OR phone LIKE ?)';
    array_push($args, $like, $like, $like, $like, $like);
}
$w = implode(' AND ', $where);
$per = 30;
$page = max(1, (int)($_GET['page'] ?? 1));
$total = (int)Database::value("SELECT COUNT(*) FROM users WHERE $w", $args);
// Pending agents first, then newest.
$rows = Database::all("SELECT * FROM users WHERE $w ORDER BY status = 'pending' DESC, created_at DESC LIMIT $per OFFSET " . (($page - 1) * $per), $args);
$counts = array_column(Database::all('SELECT status, COUNT(*) n FROM users WHERE account_type = ? GROUP BY status', [$type]), 'n', 'status');
$qs = static fn(array $x = []) => $self . '&' . http_build_query(array_filter($x + ['status' => $status, 'q' => $q]));
$label = $type === 'agent' ? 'Agents' : 'Customers';
$back = http_build_query(array_filter(['status' => $status, 'q' => $q, 'page' => $page > 1 ? $page : null]));

admin_header($label, 'acc:' . $type);
?>
<header class="adm-head">
    <div>
        <h1><?= $label ?></h1>
        <p class="muted"><?= $type === 'agent'
            ? 'Agents register on the website and wait here as <strong>Pending</strong> until you approve them. Approved agents see agent / dealer prices.'
            : 'Customers can use their account straight after registering.' ?></p>
    </div>
    <a class="adm-btn adm-btn--ghost" href="accounts.php?type=<?= $type === 'agent' ? 'customer' : 'agent' ?>">View <?= $type === 'agent' ? 'customers' : 'agents' ?> →</a>
</header>

<nav class="adm-statusbar" aria-label="Filter by status">
    <a class="<?= $status === '' ? 'is-active' : '' ?>" href="<?= e($self) ?>">All <span><?= array_sum($counts) ?></span></a>
    <?php foreach (UserAuth::STATUSES as $k => $l): if ($type === 'customer' && in_array($k, ['pending', 'rejected'], true) && empty($counts[$k])) continue; ?>
    <a class="<?= $status === $k ? 'is-active' : '' ?><?= $k === 'pending' && !empty($counts[$k]) ? ' has-pending' : '' ?>" href="<?= e($self . '&status=' . $k) ?>"><?= e($l) ?> <span><?= (int)($counts[$k] ?? 0) ?></span></a>
    <?php endforeach; ?>
</nav>

<form class="adm-card adm-filters" method="get">
    <input type="hidden" name="type" value="<?= $type ?>"><?php if ($status !== ''): ?><input type="hidden" name="status" value="<?= e($status) ?>"><?php endif; ?>
    <input type="search" name="q" value="<?= e($q) ?>" placeholder="Search name, email, phone<?= $type === 'agent' ? ', company' : '' ?>…" aria-label="Search accounts">
    <button class="adm-btn adm-btn--sm" type="submit">Search</button>
    <?php if ($q !== ''): ?><a class="adm-link" href="<?= e($self . ($status ? '&status=' . $status : '')) ?>">Clear</a><?php endif; ?>
</form>

<form method="post" class="adm-card adm-card--flush">
    <?= csrf_field() ?><input type="hidden" name="type" value="<?= $type ?>"><input type="hidden" name="back" value="<?= e($back) ?>">
    <div class="adm-card__head adm-bulkbar">
        <div class="adm-bulkbar__left">
            <select name="do" aria-label="Bulk action">
                <option value="">Bulk actions…</option>
                <?php if ($type === 'agent'): ?><option value="approve">Approve</option><option value="reject">Reject</option><?php endif; ?>
                <option value="activate">Activate</option>
                <option value="deactivate">Deactivate</option>
                <option value="delete">Delete</option>
            </select>
            <button class="adm-btn adm-btn--sm adm-btn--ghost" type="submit" data-confirm-click="Apply this action to the ticked accounts?">Apply</button>
        </div>
        <p class="muted small"><?= $total ?> account<?= $total === 1 ? '' : 's' ?></p>
    </div>
    <?php if (!$rows): ?>
    <p class="adm-empty"><?= ($q !== '' || $status !== '') ? 'No accounts match.' : 'No ' . strtolower($label) . ' yet. They appear here when someone registers on the website.' ?></p>
    <?php else: ?>
    <div class="adm-table-wrap">
    <table class="adm-table adm-table--accounts">
        <thead><tr>
            <th class="col-check"><input type="checkbox" data-check-all aria-label="Select all"></th>
            <th>Name</th><?php if ($type === 'agent'): ?><th>Company</th><?php endif; ?><th>Phone</th>
            <th>Registered</th><th>Last login</th><th>Status</th><th class="col-actions"></th>
        </tr></thead>
        <tbody>
        <?php foreach ($rows as $r): $st = $r['status']; $rid = (int)$r['id']; ?>
            <tr class="<?= $st === 'pending' ? 'is-pending' : '' ?><?= in_array($st, ['inactive', 'rejected'], true) ? ' is-off' : '' ?>">
                <td class="col-check"><input type="checkbox" name="ids[]" value="<?= $rid ?>" data-bulk-item aria-label="Select <?= e($r['first_name']) ?>"></td>
                <td><a class="adm-strong" href="account-edit.php?id=<?= $rid ?>"><?= e($r['first_name'] . ' ' . $r['last_name']) ?></a><br><a class="muted small" href="mailto:<?= e($r['email']) ?>"><?= e($r['email']) ?></a></td>
                <?php if ($type === 'agent'): ?><td class="small"><?= e($r['company'] ?: '–') ?><?= $r['state'] ? '<br><span class="muted">' . e($r['state']) . '</span>' : '' ?></td><?php endif; ?>
                <td class="small adm-nowrap"><?= e($r['phone'] ?: '–') ?></td>
                <td class="small adm-nowrap"><?= e(date('j M Y', strtotime($r['created_at']))) ?></td>
                <td class="small adm-nowrap"><?= $r['last_login_at'] ? e(date('j M Y', strtotime($r['last_login_at']))) : '<span class="muted">Never</span>' ?></td>
                <td><span class="adm-pill adm-pill--acc-<?= e($st) ?>"><?= e(UserAuth::STATUSES[$st] ?? $st) ?></span></td>
                <td class="col-actions">
                    <?php if ($st === 'pending'): ?>
                    <button class="adm-btn adm-btn--sm" name="row" value="approve:<?= $rid ?>">Approve</button>
                    <button class="adm-btn adm-btn--sm adm-btn--danger" name="row" value="reject:<?= $rid ?>" data-confirm-click="Reject this agent registration?">Reject</button>
                    <?php elseif ($st === 'active'): ?>
                    <button class="adm-btn adm-btn--sm adm-btn--ghost" name="row" value="deactivate:<?= $rid ?>" data-confirm-click="Deactivate this account? They will be signed out and cannot log in.">Deactivate</button>
                    <?php else: ?>
                    <button class="adm-btn adm-btn--sm adm-btn--ghost" name="row" value="activate:<?= $rid ?>">Activate</button>
                    <?php endif; ?>
                    <a class="adm-btn adm-btn--sm" href="account-edit.php?id=<?= $rid ?>">View / Edit</a>
                </td>
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
