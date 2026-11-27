<?php
/**
 * Shared helpers for the Products screens (admin/products/*).
 */
declare(strict_types=1);

if (basename($_SERVER['SCRIPT_FILENAME'] ?? '') === '_lib.php') {
    http_response_code(404);
    exit;
}

require dirname(__DIR__) . '/_init.php';

/** Crop shape for every product image placement (mandatory cropping). */
const P_CROP = [
    'main'       => ['w' => 1000, 'h' => 1000],              // product cards (square)
    'gallery'    => ['w' => 1000, 'h' => 1250],              // product page gallery (4:5)
    'variant'    => ['w' => 1000, 'h' => 1250],              // shown in the gallery when chosen
    'video'      => ['w' => 1600, 'h' => 900],               // video thumbnail (16:9)
    'collection' => ['w' => 1500, 'h' => 1000],              // collection image beside the video
    'drawing'    => ['w' => 1600, 'h' => 1200, 'fit' => true], // technical drawings (keep whole drawing)
    'cta'        => ['w' => 2400, 'h' => 1125],              // product-specific call to action
    'family'     => ['w' => 1500, 'h' => 1000],              // family / collection image
];

/** Product editing tabs. */
const P_TABS = [
    'general'    => 'General Information',
    'pricing'    => 'Pricing',
    'media'      => 'Images & Media',
    'options'    => 'Product Options',
    'specs'      => 'Specifications',
    'technical'  => 'Technical Information',
    'downloads'  => 'Downloads',
    'family'     => 'Product Family',
    'related'    => 'Related Products',
    'visibility' => 'Inventory & Visibility',
];

function p_tabs(?int $id, string $current): void
{
    echo '<nav class="adm-tabs" aria-label="Product sections"><ul>';
    foreach (P_TABS as $k => $label) {
        $cls = 'adm-tab' . ($k === $current ? ' is-active' : '');
        if (!$id && $k !== 'general') {
            echo '<li><span class="' . $cls . ' is-disabled" title="Save the general information first">' . e($label) . '</span></li>';
            continue;
        }
        $href = 'edit.php?' . ($id ? 'id=' . $id . '&' : '') . 'tab=' . $k;
        echo '<li><a class="' . $cls . '" href="' . e($href) . '"' . ($k === $current ? ' aria-current="page"' : '') . '>' . e($label) . '</a></li>';
    }
    echo '</ul></nav>';
}

/** <select> with a "none" option. $opts: value => label; $attrs: extra attributes per value (e.g. data-parent). */
function p_select(string $name, array $opts, $current, string $none = '— None —', string $id = '', array $attrs = []): string
{
    $id = $id ?: 'f_' . preg_replace('/\W+/', '_', $name);
    $h = '<select id="' . e($id) . '" name="' . e($name) . '">';
    if ($none !== '') {
        $h .= '<option value="">' . e($none) . '</option>';
    }
    foreach ($opts as $v => $l) {
        $h .= '<option value="' . e((string)$v) . '"' . ((string)$v === (string)$current ? ' selected' : '') . ($attrs[$v] ?? '') . '>' . e($l) . '</option>';
    }
    return $h . '</select>';
}

function p_field(string $label, string $control, string $help = '', string $for = '', bool $required = false): string
{
    return '<div class="field"><label' . ($for ? ' for="' . e($for) . '"' : '') . '>' . e($label) . ($required ? ' <span class="req">*</span>' : '') . '</label>'
        . $control . ($help !== '' ? '<p class="help">' . e($help) . '</p>' : '') . '</div>';
}

function p_input(string $name, $value, array $o = []): string
{
    $id = $o['id'] ?? 'f_' . preg_replace('/\W+/', '_', $name);
    return '<input id="' . e($id) . '" name="' . e($name) . '" type="' . e($o['type'] ?? 'text') . '" value="' . e((string)($value ?? '')) . '"'
        . (isset($o['max']) ? ' maxlength="' . (int)$o['max'] . '"' : '')
        . (isset($o['step']) ? ' step="' . e($o['step']) . '" min="0" inputmode="decimal"' : '')
        . (!empty($o['required']) ? ' required' : '')
        . (isset($o['placeholder']) ? ' placeholder="' . e($o['placeholder']) . '"' : '') . '>';
}

function p_textarea(string $name, $value, int $rows = 4, array $o = []): string
{
    $id = $o['id'] ?? 'f_' . preg_replace('/\W+/', '_', $name);
    return '<textarea id="' . e($id) . '" name="' . e($name) . '" rows="' . $rows . '"' . (!empty($o['rich']) ? ' data-richtext' : '')
        . (isset($o['max']) ? ' maxlength="' . (int)$o['max'] . '"' : '') . '>' . e((string)($value ?? '')) . '</textarea>';
}

function p_check(string $name, string $label, bool $on, string $value = '1', string $extra = ''): string
{
    return '<label class="check"><input type="checkbox" name="' . e($name) . '" value="' . e($value) . '"' . ($on ? ' checked' : '') . $extra . '> ' . e($label) . '</label>';
}

/** Text from POST, trimmed and length-limited. */
function p_str(string $key, int $max = 500, ?array $src = null): string
{
    $src ??= $_POST;
    $v = is_string($src[$key] ?? null) ? str_replace("\r\n", "\n", trim($src[$key])) : '';
    return mb_substr($v, 0, $max);
}

/**
 * Money value from POST: '' → null; otherwise a non-negative number with
 * up to 2 decimals. Adds to $errors when invalid.
 */
function p_money($raw, string $label, array &$errors): ?string
{
    $raw = trim(str_replace([',', '$', ' '], '', (string)$raw));
    if ($raw === '') return null;
    if (!preg_match('/^\d{1,9}(\.\d{1,2})?$/', $raw)) {
        $errors[] = $label . ': enter a price such as 1950 or 1950.50.';
        return null;
    }
    return $raw;
}

/** Term id from POST that must belong to a taxonomy (null when empty/invalid). */
function p_term($raw, string $taxonomy): ?int
{
    $id = (int)$raw;
    if ($id <= 0) return null;
    $t = Catalog::term($id);
    return ($t && $t['taxonomy'] === $taxonomy) ? $id : null;
}

/** Upload a cropped image if one was sent. Returns new path, or null when nothing was uploaded. */
function p_image_upload(string $input, array $crop, array &$errors, string $label): ?string
{
    $f = $_FILES[$input] ?? null;
    if (!Uploader::has($f)) return null;
    try {
        return Uploader::image($f, $crop);
    } catch (Throwable $ex) {
        $errors[] = $label . ': ' . $ex->getMessage();
        return null;
    }
}

/** Badge for a storefront list like "learn,work". */
function p_store_badges(?string $stores): string
{
    $set = array_filter(explode(',', (string)$stores));
    $h = '';
    foreach (['work' => 'Work', 'learn' => 'Learn'] as $k => $l) {
        if (in_array($k, $set, true)) $h .= '<span class="adm-pill adm-pill--' . $k . '">' . $l . '</span>';
    }
    return $h . '<span class="adm-pill">Store</span>';
}

/** Redirect back after a POST with flash messages. */
function p_done(array $errors, string $success, string $to): void
{
    foreach ($errors as $er) flash('error', $er);
    if ($success !== '') flash($errors ? 'success' : 'success', $success);
    redirect($to);
}
