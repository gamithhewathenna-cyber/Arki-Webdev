<?php
/**
 * View / edit one website account (customer or agent).
 */
declare(strict_types=1);
require __DIR__ . '/_init.php';

$id = (int)($_GET['id'] ?? 0);
$u = Database::one('SELECT * FROM users WHERE id = ?', [$id]);
if (!$u) {
    http_response_code(404);
    exit('Account not found.');
}
$self = 'account-edit.php?id=' . $id;

if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
    csrf_verify();
    if (($_POST['do'] ?? '') === 'delete') {
        Database::run('DELETE FROM users WHERE id = ?', [$id]);
        flash('success', 'Account deleted.');
        redirect('accounts.php?type=' . $u['account_type']);
    }
    $t = static fn(string $k, int $max) => mb_substr(trim((string)($_POST[$k] ?? '')), 0, $max);
    $errors = [];
    $d = [
        'first_name' => $t('first_name', 100), 'last_name' => $t('last_name', 100), 'email' => mb_strtolower($t('email', 190)),
        'phone' => $t('phone', 40), 'company' => $t('company', 150), 'abn' => preg_replace('/\s+/', '', $t('abn', 20)),
        'job_title' => $t('job_title', 100), 'website' => $t('website', 255), 'state' => $t('state', 40),
        'about' => mb_substr(trim(str_replace("\r\n", "\n", (string)($_POST['about'] ?? ''))), 0, 3000),
        'admin_note' => mb_substr(trim(str_replace("\r\n", "\n", (string)($_POST['admin_note'] ?? ''))), 0, 3000),
    ];
    if ($d['first_name'] === '' || $d['last_name'] === '') $errors[] = 'First and last name are required.';
    if (!filter_var($d['email'], FILTER_VALIDATE_EMAIL)) $errors[] = 'Please enter a valid email address.';
    elseif (Database::value('SELECT id FROM users WHERE email = ? AND id <> ?', [$d['email'], $id])) $errors[] = 'Another account already uses this email address.';
    if ($d['abn'] !== '' && !preg_match('/^\d{11}$/', $d['abn'])) $errors[] = 'An ABN has 11 digits.';
    if ($d['website'] !== '' && !filter_var(preg_match('#^https?://#i', $d['website']) ? $d['website'] : 'https://' . $d['website'], FILTER_VALIDATE_URL)) $errors[] = 'The website address is not valid.';
    $type = ($_POST['account_type'] ?? '') === 'agent' ? 'agent' : 'customer';
    $status = isset(UserAuth::STATUSES[$_POST['status'] ?? '']) ? $_POST['status'] : $u['status'];
    $newPass = (string)($_POST['new_password'] ?? '');
    if ($newPass !== '' && ($e = UserAuth::passwordProblem($newPass))) $errors[] = 'New password: ' . $e;

    if ($errors) {
        foreach ($errors as $er) flash('error', $er);
        redirect($self);
    }
    if ($d['website'] !== '' && !preg_match('#^https?://#i', $d['website'])) $d['website'] = 'https://' . $d['website'];
    Database::run(
        'UPDATE users SET first_name = ?, last_name = ?, email = ?, phone = ?, company = ?, abn = ?, job_title = ?, website = ?, state = ?, about = ?,
                admin_note = ?, newsletter = ?, account_type = ?, updated_at = NOW() WHERE id = ?',
        [$d['first_name'], $d['last_name'], $d['email'], $d['phone'] ?: null, $d['company'] ?: null, $d['abn'] ?: null, $d['job_title'] ?: null,
         $d['website'] ?: null, $d['state'] ?: null, $d['about'] ?: null, $d['admin_note'] ?: null, !empty($_POST['newsletter']) ? 1 : 0, $type, $id]
    );
    UserAuth::setStatus($id, $status, (int)$ADMIN['id']); // handles approval date + queued emails
    if ($newPass !== '') {
        Database::run('UPDATE users SET password_hash = ? WHERE id = ?', [password_hash($newPass, PASSWORD_DEFAULT), $id]);
        Database::run("DELETE FROM user_tokens WHERE user_id = ? AND type IN ('remember', 'reset')", [$id]);
    }
    flash('success', 'Account saved.' . ($newPass !== '' ? ' The new password is active – let the customer know.' : ''));
    redirect($self);
}

$approver = $u['approved_by'] ? Database::value('SELECT username FROM admin_users WHERE id = ?', [$u['approved_by']]) : null;
$isAgent = $u['account_type'] === 'agent';
$f = static fn(string $name, string $label, $value, string $type = 'text', int $max = 190) =>
    '<div class="field"><label for="f_' . $name . '">' . e($label) . '</label><input id="f_' . $name . '" name="' . $name . '" type="' . $type . '" value="' . e((string)$value) . '" maxlength="' . $max . '"></div>';

admin_header($u['first_name'] . ' ' . $u['last_name'], 'acc:' . $u['account_type']);
?>
<nav class="adm-crumbs"><a href="accounts.php?type=<?= e($u['account_type']) ?>"><?= $isAgent ? 'Agents' : 'Customers' ?></a> / <?= e($u['first_name'] . ' ' . $u['last_name']) ?></nav>
<header class="adm-head">
    <div>
        <h1><?= e($u['first_name'] . ' ' . $u['last_name']) ?></h1>
        <p class="muted"><?= e(UserAuth::TYPES[$u['account_type']]) ?> · <span class="adm-pill adm-pill--acc-<?= e($u['status']) ?>"><?= e(UserAuth::STATUSES[$u['status']] ?? $u['status']) ?></span>
            · registered <?= e(date('j M Y, g:i a', strtotime($u['created_at']))) ?></p>
    </div>
    <a class="adm-btn adm-btn--ghost" href="mailto:<?= e($u['email']) ?>">Email <?= e($u['first_name']) ?></a>
</header>

<?php if ($u['status'] === 'pending'): ?>
<form method="post" action="accounts.php?type=agent" class="adm-card adm-approve">
    <?= csrf_field() ?><input type="hidden" name="type" value="agent">
    <div><strong>This agent is waiting for approval.</strong><p class="muted small">Approving lets them log in and see agent / dealer prices.</p></div>
    <div class="adm-actions">
        <button class="adm-btn" name="row" value="approve:<?= $id ?>">Approve agent</button>
        <button class="adm-btn adm-btn--danger" name="row" value="reject:<?= $id ?>" data-confirm-click="Reject this agent registration?">Reject</button>
    </div>
</form>
<?php endif; ?>

<form method="post" class="adm-form">
    <?= csrf_field() ?>
    <div class="adm-split adm-split--wide">
        <div>
            <section class="adm-card">
                <h2>Details</h2>
                <div class="adm-grid-2">
                    <?= $f('first_name', 'First name', $u['first_name'], 'text', 100) ?>
                    <?= $f('last_name', 'Last name', $u['last_name'], 'text', 100) ?>
                    <?= $f('email', 'Email address', $u['email'], 'email') ?>
                    <?= $f('phone', 'Phone', $u['phone'], 'text', 40) ?>
                </div>
                <div class="field"><label class="check"><input type="checkbox" name="newsletter" value="1"<?= $u['newsletter'] ? ' checked' : '' ?>> Wants occasional updates</label></div>
            </section>

            <section class="adm-card">
                <h2>Business details <span class="muted small">(agents)</span></h2>
                <div class="adm-grid-2">
                    <?= $f('company', 'Company', $u['company'], 'text', 150) ?>
                    <?= $f('abn', 'ABN', $u['abn'], 'text', 20) ?>
                    <?= $f('job_title', 'Role', $u['job_title'], 'text', 100) ?>
                    <?= $f('website', 'Website', $u['website'], 'text', 255) ?>
                    <div class="field"><label for="f_state">State / territory</label><select id="f_state" name="state"><option value="">—</option>
                        <?php foreach (UserAuth::STATES as $st): ?><option<?= $u['state'] === $st ? ' selected' : '' ?>><?= e($st) ?></option><?php endforeach; ?></select></div>
                </div>
                <div class="field"><label for="f_about">About their business</label><textarea id="f_about" name="about" rows="4"><?= e((string)$u['about']) ?></textarea></div>
            </section>
        </div>

        <div>
            <section class="adm-card">
                <h2>Account</h2>
                <div class="field"><label for="f_account_type">Account type</label>
                    <select id="f_account_type" name="account_type"><?php foreach (UserAuth::TYPES as $k => $l): ?><option value="<?= $k ?>"<?= $u['account_type'] === $k ? ' selected' : '' ?>><?= $l ?></option><?php endforeach; ?></select></div>
                <div class="field"><label for="f_status">Status</label>
                    <select id="f_status" name="status"><?php foreach (UserAuth::STATUSES as $k => $l): ?><option value="<?= $k ?>"<?= $u['status'] === $k ? ' selected' : '' ?>><?= $l ?></option><?php endforeach; ?></select>
                    <p class="help">Only <strong>Active</strong> accounts can log in.</p></div>
                <dl class="adm-enquiry__facts adm-facts--stack">
                    <div><dt>Registered</dt><dd><?= e(date('j M Y, g:i a', strtotime($u['created_at']))) ?></dd></div>
                    <div><dt>Last login</dt><dd><?= $u['last_login_at'] ? e(date('j M Y, g:i a', strtotime($u['last_login_at']))) : 'Never' ?></dd></div>
                    <?php if ($isAgent): ?><div><dt>Approved</dt><dd><?= $u['approved_at'] ? e(date('j M Y', strtotime($u['approved_at']))) . ($approver ? ' by ' . e($approver) : '') : '–' ?></dd></div><?php endif; ?>
                </dl>
            </section>

            <section class="adm-card">
                <h2>Set a new password</h2>
                <div class="field"><label for="f_new_password">New password (optional)</label>
                    <input id="f_new_password" name="new_password" type="password" autocomplete="new-password" maxlength="200">
                    <p class="help">Leave empty to keep their password. Until password emails are switched on, you can set one here and tell the customer. At least 8 characters with a letter and a number.</p></div>
            </section>

            <section class="adm-card">
                <h2>Internal note</h2>
                <div class="field"><label for="f_admin_note" class="sr-only">Internal note</label><textarea id="f_admin_note" name="admin_note" rows="4" placeholder="Only visible in the CMS"><?= e((string)$u['admin_note']) ?></textarea></div>
            </section>
        </div>
    </div>
    <div class="adm-actions adm-actions--sticky">
        <button class="adm-btn" type="submit">Save account</button>
        <a class="adm-link" href="accounts.php?type=<?= e($u['account_type']) ?>">Back to <?= $isAgent ? 'agents' : 'customers' ?></a>
    </div>
</form>

<form method="post" class="adm-danger-zone">
    <?= csrf_field() ?><input type="hidden" name="do" value="delete">
    <button class="adm-btn adm-btn--sm adm-btn--danger" type="submit" data-confirm-click="Delete this account permanently? Their cart is deleted too.">Delete account</button>
</form>
<?php admin_footer(); ?>
