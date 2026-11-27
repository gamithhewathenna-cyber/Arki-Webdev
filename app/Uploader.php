<?php
declare(strict_types=1);

/**
 * Secure image uploads.
 *  - Validates the real file type with finfo + getimagesize (not the name).
 *  - Random file names, stored in uploads/YYYY/MM/.
 *  - Re-encodes JPEG/PNG with GD (strips any embedded payload/EXIF),
 *    downsizes very large images and creates a WebP copy for faster loading.
 *  - SVG is intentionally not accepted (can carry scripts).
 */
final class Uploader
{
    private const MAX_BYTES = 10 * 1024 * 1024;
    private const MAX_DIM   = 2400;
    private const TYPES = [
        'image/jpeg' => 'jpg',
        'image/png'  => 'png',
        'image/webp' => 'webp',
        'image/gif'  => 'gif',
    ];

    /** Fallback crop shape for an image field that doesn't declare one. */
    public const DEFAULT_CROP = ['w' => 2400, 'h' => 1600];

    /**
     * Normalise a field's 'crop' spec into a list of allowed shapes.
     * @return array<int, array{label: string, w: int, h: int}>
     */
    public static function cropPresets(?array $crop): array
    {
        $crop = $crop ?: self::DEFAULT_CROP;
        $list = $crop['presets'] ?? [['', $crop['w'] ?? 0, $crop['h'] ?? 0]];
        $out = [];
        foreach ($list as $p) {
            $w = (int)($p['w'] ?? $p[1] ?? 0);
            $h = (int)($p['h'] ?? $p[2] ?? 0);
            if ($w > 0 && $h > 0) {
                $out[] = ['label' => (string)($p['label'] ?? $p[0] ?? ''), 'w' => $w, 'h' => $h];
            }
        }
        return $out ?: [['label' => '', 'w' => self::DEFAULT_CROP['w'], 'h' => self::DEFAULT_CROP['h']]];
    }

    /** Human-readable list of the required sizes, e.g. "840 × 1200 px". */
    public static function cropText(?array $crop): string
    {
        return implode(' or ', array_map(
            static fn($p) => ($p['label'] !== '' ? $p['label'] . ': ' : '') . $p['w'] . ' × ' . $p['h'] . ' px',
            self::cropPresets($crop)
        ));
    }

    /**
     * @param array|null $crop the field's crop spec; the image must already be cropped to one of its shapes
     * @return string relative path ("uploads/2026/11/abc.jpg")
     */
    public static function image(array $file, ?array $crop = null): string
    {
        if (($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
            throw new RuntimeException(self::errorMessage((int)($file['error'] ?? UPLOAD_ERR_NO_FILE)));
        }
        if (!is_uploaded_file($file['tmp_name'])) {
            throw new RuntimeException('Invalid upload.');
        }
        if ($file['size'] > self::MAX_BYTES) {
            throw new RuntimeException('Image is larger than 10 MB.');
        }
        $mime = (new finfo(FILEINFO_MIME_TYPE))->file($file['tmp_name']);
        if (!isset(self::TYPES[$mime])) {
            throw new RuntimeException('Only JPG, PNG, WebP and GIF images are allowed.');
        }
        $info = @getimagesize($file['tmp_name']);
        if (!$info || $info[0] < 1 || $info[1] < 1 || $info[0] * $info[1] > 60_000_000) {
            throw new RuntimeException('The file is not a valid image.');
        }

        // Mandatory crop: the image must match one of the field's shapes.
        $target = self::matchCrop((int)$info[0], (int)$info[1], self::cropPresets($crop));
        if (!$target) {
            throw new RuntimeException('The image was not cropped. Please choose it again and confirm the crop (' . self::cropText($crop) . ').');
        }

        $ext  = self::TYPES[$mime];
        $dir  = 'uploads/' . date('Y/m');
        $abs  = ROOT_PATH . '/' . $dir;
        if (!is_dir($abs) && !mkdir($abs, 0755, true)) {
            throw new RuntimeException('Upload folder is not writable.');
        }
        $base = bin2hex(random_bytes(8));
        $rel  = "$dir/$base.$ext";
        $dest = ROOT_PATH . '/' . $rel;

        if (!self::process($file['tmp_name'], $dest, $mime, $info[0], $info[1], $target)) {
            // GD unavailable or GIF – store the validated original.
            if (!move_uploaded_file($file['tmp_name'], $dest)) {
                throw new RuntimeException('Could not save the image.');
            }
        }
        @chmod($dest, 0644);
        return $rel;
    }

    /**
     * Find the crop shape the image matches (same aspect ratio, ±2% or ±1px
     * of rounding on small images). Returns null when it matches none.
     */
    private static function matchCrop(int $w, int $h, array $presets): ?array
    {
        foreach ($presets as $p) {
            $want = $p['w'] / $p['h'];
            $tol  = max(0.02, 1.5 / min($w, $h));
            if (abs(($w / $h) - $want) / $want <= $tol) {
                return $p;
            }
        }
        return null;
    }

    /** Re-encode / resize with GD and create a WebP sibling. */
    private static function process(string $src, string $dest, string $mime, int $w, int $h, ?array $target = null): bool
    {
        if (!extension_loaded('gd') || $mime === 'image/gif') {
            return false;
        }
        $img = match ($mime) {
            'image/jpeg' => @imagecreatefromjpeg($src),
            'image/png'  => @imagecreatefrompng($src),
            'image/webp' => function_exists('imagecreatefromwebp') ? @imagecreatefromwebp($src) : false,
            default      => false,
        };
        if (!$img) {
            return false;
        }
        if ($mime === 'image/jpeg') {
            $img = self::orient($img, $src);
            $w = imagesx($img);
            $h = imagesy($img);
        }
        // Larger than the crop's output size → scale down to exactly that size.
        $nw = $w;
        $nh = $h;
        if ($target && $w > $target['w']) {
            [$nw, $nh] = [$target['w'], $target['h']];
        }
        if (max($nw, $nh) > self::MAX_DIM) {
            $ratio = self::MAX_DIM / max($nw, $nh);
            $nw = (int)round($nw * $ratio);
            $nh = (int)round($nh * $ratio);
        }
        if ($nw !== $w || $nh !== $h) {
            $res = imagecreatetruecolor($nw, $nh);
            imagealphablending($res, false);
            imagesavealpha($res, true);
            imagecopyresampled($res, $img, 0, 0, 0, 0, $nw, $nh, $w, $h);
            imagedestroy($img);
            $img = $res;
        }
        imagesavealpha($img, true);
        if ($mime === 'image/jpeg') {
            imageinterlace($img, true); // progressive JPEG
        }

        $ok = match ($mime) {
            'image/jpeg' => imagejpeg($img, $dest, 82),
            'image/png'  => imagepng($img, $dest, 8),
            'image/webp' => imagewebp($img, $dest, 80),
        };
        if ($ok && $mime !== 'image/webp' && function_exists('imagewebp')) {
            if (!imageistruecolor($img)) {
                imagepalettetotruecolor($img);
            }
            @imagewebp($img, preg_replace('/\.(jpg|png)$/', '.webp', $dest), 78);
        }
        imagedestroy($img);
        return (bool)$ok;
    }

    /** Apply EXIF orientation so phone photos are not sideways. */
    private static function orient($img, string $src)
    {
        if (!function_exists('exif_read_data')) {
            return $img;
        }
        $exif = @exif_read_data($src);
        $o = (int)($exif['Orientation'] ?? 1);
        return match ($o) {
            3 => imagerotate($img, 180, 0),
            6 => imagerotate($img, -90, 0),
            8 => imagerotate($img, 90, 0),
            default => $img,
        };
    }

    /** Delete an uploaded file (and its WebP copy) if it is not referenced anywhere else. */
    public static function deleteIfUnused(?string $rel): void
    {
        $rel = ltrim((string)$rel, '/');
        if ($rel === '' || !str_starts_with($rel, 'uploads/') || str_contains($rel, '..')) {
            return;
        }
        $needle = '%' . str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], json_encode($rel, JSON_UNESCAPED_SLASHES)) . '%';
        $used = (int)Database::value('SELECT COUNT(*) FROM sections WHERE data LIKE ?', [$needle])
              + (int)Database::value('SELECT COUNT(*) FROM section_items WHERE data LIKE ?', [$needle]);
        if ($used > 0) {
            return;
        }
        foreach ([$rel, preg_replace('/\.(jpe?g|png)$/i', '.webp', $rel)] as $f) {
            $abs = ROOT_PATH . '/' . $f;
            if (is_file($abs)) {
                @unlink($abs);
            }
        }
    }

    private static function errorMessage(int $code): string
    {
        return match ($code) {
            UPLOAD_ERR_INI_SIZE, UPLOAD_ERR_FORM_SIZE => 'Image exceeds the server upload limit.',
            UPLOAD_ERR_PARTIAL => 'The image was only partially uploaded.',
            UPLOAD_ERR_NO_FILE => 'No file was uploaded.',
            default => 'Upload failed (code ' . $code . ').',
        };
    }
}
