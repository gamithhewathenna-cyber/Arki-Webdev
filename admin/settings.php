<?php
declare(strict_types=1);
require __DIR__ . '/_init.php';

/*
 * Website logos. These are stored in the shared Header and Footer sections
 * (same fields as Header & Footer → Header / Footer), so editing them here
 * or there always changes the same logo.
 */
admin_sync_sections('global');
// Field definitions (incl. crop sizes) come from the schema so both places stay identical.
$hdr = ContentRepository::sectionSchema('global', 'header')['fields'];
$ftr = ContentRepository::sectionSchema('global', 'footer')['fields'];
$logoGroups = [
    'header' => [
        'logo'       => ['label' => 'Header logo'] + $hdr['logo'],
        'logo_light' => ['label' => 'Header logo – white version (over banner images)'] + $hdr['logo_light'],
        'logo_alt' => ['label' => 'Header logo description (alt text)', 'help' => 'e.g. Steelcase | ARKI Environments'] + $hdr['logo_alt'],
    ],
    'footer' => [
        'brand_logo'  => ['label' => 'Footer logo', 'help' => 'Shown in the first footer column. Transparent PNG works best.'] + $ftr['brand_logo'],
        'dealer_logo' => ['label' => 'Footer partner logo', 'help' => 'Second footer column (e.g. Steelcase Authorised Dealer). Transparent PNG works best.'] + $ftr['dealer_logo'],
    ],
];
$faviconField = ['favicon' => ['type' => 'image', 'label' => 'Favicon', 'help' => 'The small icon in the browser tab. Transparent PNG works best.', 'crop' => ['w' => 512, 'h' => 512, 'fit' => true]]];
$logoSections = [];
foreach (array_keys($logoGroups) as $key) {
    $row = Database::one("SELECT id FROM sections WHERE page = 'global' AND section_key = ?", [$key]);
    $logoSections[$key] = $row ? ContentRepository::section((int)$row['id']) : null;
}

if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
    csrf_verify();

    // Logos → saved into the Header / Footer sections, other fields there untouched.
    foreach ($logoGroups as $key => $fields) {
        $sec = $logoSections[$key];
        if (!$sec) {
            continue;
        }
        [$data, $errors, $orphans] = AdminForm::collect($fields, $sec['data']);
        ContentRepository::saveSectionData((int)$sec['id'], array_merge($sec['data'], $data));
        foreach ($orphans as $o) {
            Uploader::deleteIfUnused($o);
        }
        foreach ($errors as $er) {
            flash('error', $er);
        }
    }

    $name  = trim((string)($_POST['site_name'] ?? ''));
    $title = trim((string)($_POST['home_title'] ?? ''));
    Settings::set('site_name', mb_substr($name !== '' ? $name : 'Website', 0, 120));
    Settings::set('home_title', mb_substr($title, 0, 200));

    // Favicon (same cropped upload as every other image field)
    [$favData, $favErrors, $favOrphans] = AdminForm::collect($faviconField, ['favicon' => (string)Settings::get('favicon', '')]);
    Settings::set('favicon', $favData['favicon']);
    foreach ($favOrphans as $o) {
        Uploader::deleteIfUnused($o); // old favicon, already replaced in the setting
    }
    foreach ($favErrors as $er) {
        flash('error', $er);
    }

    // Under Construction Mode
    Settings::set('construction_enabled', !empty($_POST['construction_enabled']) ? '1' : '0');
    Settings::set('construction_heading', mb_substr(trim((string)($_POST['construction_heading'] ?? '')), 0, 200));
    Settings::set('construction_message', mb_substr(trim(str_replace("\r\n", "\n", (string)($_POST['construction_message'] ?? ''))), 0, 2000));

    flash('success', 'Settings saved.');
    redirect('settings.php');
}

$fav = (string)Settings::get('favicon', '');
$underConstruction = Construction::enabled();
admin_header('Website Settings', 'settings');
?>
<header class="adm-head"><div><h1>Website Settings</h1><p class="muted">General website settings.</p></div></header>

<form method="post" enctype="multipart/form-data" class="adm-form">
    <?= csrf_field() ?>
    <section class="adm-card">
        <h2>General</h2>
        <div class="field"><label for="site_name">Site name</label><input id="site_name" name="site_name" value="<?= e(Settings::get('site_name', '')) ?>" required></div>
        <div class="field"><label for="home_title">Homepage browser title</label><input id="home_title" name="home_title" value="<?= e(Settings::get('home_title', '')) ?>"><p class="help">Shown in the browser tab.</p></div>
        <?= AdminForm::render($faviconField, ['favicon' => $fav]) ?>
    </section>

    <section class="adm-card">
        <h2>Website logos</h2>
        <p class="help adm-card__intro">These are the same logos as in Header &amp; Footer, so changing them here updates the website header and footer.</p>
        <div class="adm-grid-2">
            <div>
                <h3 class="adm-h3">Header</h3>
                <?= AdminForm::render($logoGroups['header'], $logoSections['header']['data'] ?? []) ?>
            </div>
            <div>
                <h3 class="adm-h3">Footer</h3>
                <?= AdminForm::render($logoGroups['footer'], $logoSections['footer']['data'] ?? []) ?>
            </div>
        </div>
    </section>

    <section class="adm-card">
        <h2>Under Construction Mode <span class="adm-pill <?= $underConstruction ? 'adm-pill--draft' : 'adm-pill--published' ?>"><?= $underConstruction ? 'On – visitors see the holding page' : 'Off – website is open' ?></span></h2>
        <div class="field">
            <label class="adm-switch">
                <input type="checkbox" name="construction_enabled" value="1"<?= $underConstruction ? ' checked' : '' ?>>
                <span class="adm-switch__track" aria-hidden="true"></span>
                <span>Show the “Under construction” page to visitors</span>
            </label>
            <p class="help">While this is on, visitors only see the holding page. You stay logged in to the CMS and can still preview the whole website. Turn it off to open the website to everyone.</p>
        </div>
        <div class="adm-grid-2">
            <div class="field"><label for="construction_heading">Holding page heading</label>
                <input id="construction_heading" name="construction_heading" maxlength="200" value="<?= e((string)Settings::get('construction_heading', '')) ?>" placeholder="We’re Building Something New"></div>
            <div class="field"><label for="construction_message">Holding page message</label>
                <textarea id="construction_message" name="construction_message" rows="3" maxlength="2000" placeholder="Our new website is under construction and will be ready soon. Thank you for your patience."><?= e((string)Settings::get('construction_message', '')) ?></textarea></div>
        </div>
        <p class="help">Leave them empty to use the grey example texts. <a href="<?= e(url()) ?>" target="_blank" rel="noopener">Open the website ↗</a> (as a logged-in administrator you see the full site; log out or use a private window to see what visitors see).</p>
        <div class="adm-note">
            <strong>Search engines:</strong> indexing is disabled. Every page sends <code>noindex, nofollow</code> as a meta tag and an <code>X-Robots-Tag</code> header.
            Remove these at launch (see README → “Going live”).
        </div>
    </section>

    <div class="adm-actions"><button class="adm-btn" type="submit">Save settings</button></div>
</form>
<?php admin_footer(); ?>
