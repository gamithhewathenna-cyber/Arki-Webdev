<?php
declare(strict_types=1);

/**
 * Shopping cart.
 *  - Guests: a random token in a cookie (only its hash is stored).
 *  - Signed-in users: the cart belongs to the account and follows them;
 *    a guest cart is merged into it on login.
 *  - Prices are always worked out on the server from the product database
 *    (approved agents get agent prices). Nothing price-related is trusted from the browser.
 */
final class Cart
{
    private const COOKIE = 'arki_cart';
    private const MAX_QTY = 999;

    private static ?int $id = null;

    /** Current cart id (created on first use when $create). */
    public static function id(bool $create = false): ?int
    {
        if (self::$id !== null) return self::$id;
        $user = UserAuth::user();
        if ($user) {
            $id = Database::value('SELECT id FROM carts WHERE user_id = ? ORDER BY id LIMIT 1', [$user['id']]);
            if (!$id && $create) {
                Database::run('INSERT INTO carts (user_id, created_at, updated_at) VALUES (?, NOW(), NOW())', [$user['id']]);
                $id = Database::lastId();
            }
            return self::$id = $id ? (int)$id : null;
        }
        $token = (string)($_COOKIE[self::COOKIE] ?? '');
        if (preg_match('/^[a-f0-9]{64}$/', $token)) {
            $id = Database::value('SELECT id FROM carts WHERE token_hash = ? AND user_id IS NULL', [hash('sha256', $token)]);
            if ($id) return self::$id = (int)$id;
        }
        if (!$create) return null;
        $token = bin2hex(random_bytes(32));
        Database::run('INSERT INTO carts (token_hash, created_at, updated_at) VALUES (?, NOW(), NOW())', [hash('sha256', $token)]);
        setcookie(self::COOKIE, $token, ['expires' => time() + 60 * 86400, 'path' => (BASE_URL ?: '') . '/', 'secure' => is_https(), 'httponly' => true, 'samesite' => 'Lax']);
        return self::$id = Database::lastId();
    }

    /** After login: move the guest cart's items into the account's cart. */
    public static function attachToUser(int $userId): void
    {
        $token = (string)($_COOKIE[self::COOKIE] ?? '');
        if (!preg_match('/^[a-f0-9]{64}$/', $token)) return;
        $guest = Database::value('SELECT id FROM carts WHERE token_hash = ? AND user_id IS NULL', [hash('sha256', $token)]);
        if (!$guest) return;
        $mine = Database::value('SELECT id FROM carts WHERE user_id = ? ORDER BY id LIMIT 1', [$userId]);
        if (!$mine) {
            Database::run('UPDATE carts SET user_id = ?, token_hash = NULL, updated_at = NOW() WHERE id = ?', [$userId, $guest]);
        } else {
            foreach (Database::all('SELECT * FROM cart_items WHERE cart_id = ?', [$guest]) as $it) {
                self::addRow((int)$mine, (int)$it['product_id'], $it['variant_id'] ? (int)$it['variant_id'] : null,
                    ['size' => $it['size_id'], 'colour' => $it['colour_id'], 'material' => $it['material_id']], (int)$it['qty']);
            }
            Database::run('DELETE FROM carts WHERE id = ?', [$guest]);
        }
        setcookie(self::COOKIE, '', ['expires' => time() - 3600, 'path' => (BASE_URL ?: '') . '/']);
        self::$id = null;
    }

    /** Add a product (with the chosen options) to the cart. Returns an error message or null. */
    public static function add(int $productId, array $opts, int $qty): ?string
    {
        $p = Database::one("SELECT id, name FROM products WHERE id = ? AND status = 'published'", [$productId]);
        if (!$p) return 'This product is no longer available.';
        $qty = max(1, min(self::MAX_QTY, $qty));
        $opts = self::validOptions($productId, $opts);
        $variant = self::matchVariant($productId, $opts);
        self::addRow((int)self::id(true), $productId, $variant, $opts, $qty);
        return null;
    }

    private static function addRow(int $cartId, int $productId, ?int $variantId, array $opts, int $qty): void
    {
        $same = Database::one(
            'SELECT id, qty FROM cart_items WHERE cart_id = ? AND product_id = ? AND variant_id <=> ? AND size_id <=> ? AND colour_id <=> ? AND material_id <=> ?',
            [$cartId, $productId, $variantId, $opts['size'] ?: null, $opts['colour'] ?: null, $opts['material'] ?: null]
        );
        if ($same) {
            Database::run('UPDATE cart_items SET qty = ?, updated_at = NOW() WHERE id = ?', [min(self::MAX_QTY, (int)$same['qty'] + $qty), $same['id']]);
        } else {
            Database::run(
                'INSERT INTO cart_items (cart_id, product_id, variant_id, size_id, colour_id, material_id, qty, created_at, updated_at) VALUES (?, ?, ?, ?, ?, ?, ?, NOW(), NOW())',
                [$cartId, $productId, $variantId, $opts['size'] ?: null, $opts['colour'] ?: null, $opts['material'] ?: null, $qty]
            );
        }
        Database::run('UPDATE carts SET updated_at = NOW() WHERE id = ?', [$cartId]);
    }

    public static function update(int $itemId, int $qty): void
    {
        $cart = self::id();
        if (!$cart) return;
        if ($qty <= 0) {
            self::remove($itemId);
            return;
        }
        Database::run('UPDATE cart_items SET qty = ?, updated_at = NOW() WHERE id = ? AND cart_id = ?', [min(self::MAX_QTY, $qty), $itemId, $cart]);
    }

    public static function remove(int $itemId): void
    {
        $cart = self::id();
        if ($cart) Database::run('DELETE FROM cart_items WHERE id = ? AND cart_id = ?', [$itemId, $cart]);
    }

    /** Number of items (for the header badge). */
    public static function count(): int
    {
        try {
            $cart = self::id();
            return $cart ? (int)Database::value('SELECT COALESCE(SUM(qty), 0) FROM cart_items WHERE cart_id = ?', [$cart]) : 0;
        } catch (Throwable $e) {
            return 0;
        }
    }

    /**
     * Cart lines with current prices: [lines, subtotal, currency, has_unavailable].
     * Unpublished products are kept but marked unavailable and not counted.
     */
    public static function lines(): array
    {
        $cart = self::id();
        if (!$cart) return [[], 0.0, 'AUD', false];
        $agent = UserAuth::isAgent();
        $rows = Database::all(
            'SELECT ci.*, p.name, p.slug, p.sku, p.image, p.status, p.currency, p.retail_price, p.sale_price, p.agent_price,
                    v.sku AS v_sku, v.retail_price AS v_retail, v.agent_price AS v_agent, v.image AS v_image,
                    s.name AS size_name, c.name AS colour_name, m.name AS material_name
             FROM cart_items ci
             JOIN products p ON p.id = ci.product_id
             LEFT JOIN product_variants v ON v.id = ci.variant_id AND v.product_id = ci.product_id
             LEFT JOIN product_terms s ON s.id = ci.size_id
             LEFT JOIN product_terms c ON c.id = ci.colour_id
             LEFT JOIN product_terms m ON m.id = ci.material_id
             WHERE ci.cart_id = ? ORDER BY ci.id',
            [$cart]
        );
        $lines = [];
        $subtotal = 0.0;
        $currency = 'AUD';
        $unavailable = false;
        foreach ($rows as $r) {
            $available = $r['status'] === 'published';
            $retail = $r['v_retail'] ?? ($r['sale_price'] ?? $r['retail_price']);
            $price = $retail;
            $isAgentPrice = false;
            if ($agent) {
                $ap = $r['v_agent'] ?? $r['agent_price'];
                if ($ap !== null) { $price = $ap; $isAgentPrice = true; }
            }
            $price = $price !== null ? (float)$price : null;
            $line = $price !== null ? round($price * (int)$r['qty'], 2) : null;
            if ($available && $line !== null) $subtotal += $line;
            if (!$available) $unavailable = true;
            $currency = $r['currency'] ?: $currency;
            $lines[] = [
                'id' => (int)$r['id'], 'product_id' => (int)$r['product_id'], 'name' => $r['name'], 'slug' => $r['slug'],
                'sku' => $r['v_sku'] ?: $r['sku'], 'image' => $r['v_image'] ?: $r['image'], 'qty' => (int)$r['qty'],
                'options' => array_filter(['Size' => $r['size_name'], 'Colour' => $r['colour_name'], 'Material' => $r['material_name']]),
                'price' => $price, 'line' => $line, 'currency' => $r['currency'] ?: 'AUD', 'agent_price' => $isAgentPrice, 'available' => $available,
            ];
        }
        return [$lines, round($subtotal, 2), $currency, $unavailable];
    }

    /* ---------------- options → variant ---------------- */

    /** Keep only option ids that belong to this product (assigned options or its variants). */
    private static function validOptions(int $productId, array $opts): array
    {
        $out = ['size' => null, 'colour' => null, 'material' => null];
        $allowed = array_map('intval', array_column(Database::all(
            'SELECT term_id AS id FROM product_term_links WHERE product_id = ?
             UNION SELECT size_id FROM product_variants WHERE product_id = ? AND size_id IS NOT NULL
             UNION SELECT colour_id FROM product_variants WHERE product_id = ? AND colour_id IS NOT NULL
             UNION SELECT material_id FROM product_variants WHERE product_id = ? AND material_id IS NOT NULL',
            [$productId, $productId, $productId, $productId]
        ), 'id'));
        foreach ($out as $k => $_) {
            $v = (int)($opts[$k] ?? 0);
            if ($v > 0 && in_array($v, $allowed, true)) {
                $t = Catalog::term($v);
                if ($t && $t['taxonomy'] === $k) $out[$k] = $v;
            }
        }
        return $out;
    }

    /** Same rule as the product page: the variant whose set options all match the most choices. */
    private static function matchVariant(int $productId, array $opts): ?int
    {
        $best = null;
        $bestScore = 0;
        foreach (Database::all('SELECT id, size_id, colour_id, material_id FROM product_variants WHERE product_id = ? ORDER BY sort_order, id', [$productId]) as $v) {
            $score = 0;
            $ok = true;
            foreach (['size' => 'size_id', 'colour' => 'colour_id', 'material' => 'material_id'] as $k => $col) {
                if ($v[$col] === null) continue;
                if ((int)$v[$col] === (int)($opts[$k] ?? 0)) $score++; else $ok = false;
            }
            if ($ok && $score > $bestScore) { $best = (int)$v['id']; $bestScore = $score; }
        }
        return $best;
    }
}
