<?php
declare(strict_types=1);

/**
 * Builds CMS forms from the schema in app/schema/pages.php and turns
 * submitted values back into clean data arrays.
 */
final class AdminForm
{
    /** Render all fields of a schema. */
    public static function render(array $fields, array $data): string
    {
        $html = '';
        foreach ($fields as $key => $f) {
            $html .= self::field((string)$key, $f, $data[$key] ?? ($f['default'] ?? ''));
        }
        return $html;
    }

    private static function field(string $key, array $f, $value): string
    {
        $id    = 'f_' . $key;
        $name  = 'f[' . $key . ']';
        $label = e($f['label'] ?? $key) . (!empty($f['required']) ? ' <span class="req">*</span>' : '');
        $help  = !empty($f['help']) ? '<p class="help">' . e($f['help']) . '</p>' : '';
        $req   = !empty($f['required']) && ($f['type'] ?? 'text') !== 'image' ? ' required' : '';
        $value = is_string($value) ? $value : (string)($value ?? '');

        switch ($f['type'] ?? 'text') {
            case 'textarea':
                $input = '<textarea id="' . $id . '" name="' . $name . '" rows="6"' . $req . '>' . e($value) . '</textarea>';
                break;
            case 'multiline':
                $input = '<textarea id="' . $id . '" name="' . $name . '" rows="2" class="short"' . $req . '>' . e($value) . '</textarea>';
                $help  = $help ?: '<p class="help">Press Enter to force a line break.</p>';
                break;
            case 'url':
                $input = '<input id="' . $id . '" name="' . $name . '" type="text" inputmode="url" value="' . e($value) . '" placeholder="/page/ or https://…"' . $req . '>';
                break;
            case 'select':
                $input = '<select id="' . $id . '" name="' . $name . '">';
                foreach ($f['options'] ?? [] as $ov => $ol) {
                    $input .= '<option value="' . e($ov) . '"' . ((string)$ov === $value ? ' selected' : '') . '>' . e($ol) . '</option>';
                }
                $input .= '</select>';
                break;
            case 'image':
                $preview = '';
                if ($value !== '' && is_file(ROOT_PATH . '/' . ltrim($value, '/'))) {
                    $preview = '<div class="img-current"><img src="' . e(upload_url($value)) . '" alt="" data-preview="' . $id . '">'
                        . '<label class="check"><input type="checkbox" name="remove[' . e($key) . ']" value="1"> Remove image</label></div>';
                } else {
                    $preview = '<div class="img-current is-empty"><img alt="" data-preview="' . $id . '" hidden></div>';
                }
                $input = $preview . '<input id="' . $id . '" name="img_' . e($key) . '" type="file" accept="image/jpeg,image/png,image/webp,image/gif" data-image-input>';
                $help .= '<p class="help">JPG, PNG, WebP or GIF up to 10 MB. Large images are resized and a WebP copy is created automatically.</p>';
                break;
            default:
                $input = '<input id="' . $id . '" name="' . $name . '" type="text" value="' . e($value) . '"' . $req . '>';
        }

        return '<div class="field field--' . e($f['type'] ?? 'text') . '"><label for="' . $id . '">' . $label . '</label>' . $input . $help . '</div>';
    }

    /**
     * Read the POSTed values for a schema.
     * @return array{0: array, 1: string[], 2: string[]} [data, errors, replaced image paths to clean up]
     */
    public static function collect(array $fields, array $old): array
    {
        $in      = $_POST['f'] ?? [];
        $remove  = $_POST['remove'] ?? [];
        $data    = [];
        $errors  = [];
        $orphans = [];

        foreach ($fields as $key => $f) {
            $type  = $f['type'] ?? 'text';
            $label = $f['label'] ?? $key;

            if ($type === 'image') {
                $current = (string)($old[$key] ?? '');
                $file    = $_FILES['img_' . $key] ?? null;
                if ($file && ($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE) {
                    try {
                        $data[$key] = Uploader::image($file);
                        if ($current !== '') {
                            $orphans[] = $current;
                        }
                    } catch (Throwable $ex) {
                        $errors[] = $label . ': ' . $ex->getMessage();
                        $data[$key] = $current;
                    }
                } elseif (!empty($remove[$key])) {
                    $data[$key] = '';
                    if ($current !== '') {
                        $orphans[] = $current;
                    }
                } else {
                    $data[$key] = $current;
                }
                if (!empty($f['required']) && $data[$key] === '') {
                    $errors[] = $label . ' is required.';
                }
                continue;
            }

            $v = is_string($in[$key] ?? null) ? $in[$key] : '';
            $v = str_replace("\r\n", "\n", $v);
            $v = $type === 'textarea' || $type === 'multiline' ? trim($v) : trim(preg_replace('/\s+/', ' ', $v));
            $v = mb_substr($v, 0, $type === 'textarea' ? 10000 : 500);

            if ($type === 'url' && $v !== '' && safe_url($v) === '#' && $v !== '#') {
                $errors[] = $label . ': only web addresses, relative paths, mailto: and tel: links are allowed.';
                $v = '';
            }
            if ($type === 'select' && !array_key_exists($v, $f['options'] ?? [])) {
                $v = (string)array_key_first($f['options'] ?? ['' => '']);
            }
            if (!empty($f['required']) && $v === '') {
                $errors[] = $label . ' is required.';
            }
            $data[$key] = $v;
        }
        return [$data, $errors, $orphans];
    }
}
