<?php
declare(strict_types=1);

/**
 * Under Construction Mode (Admin → Website Settings).
 *  - On:  visitors see the "Under construction" page.
 *  - Off: the website is open to everyone.
 * Logged-in administrators always see the full website, so they can preview it.
 */
final class Construction
{
    public static function enabled(): bool
    {
        return Settings::get('construction_enabled', '0') === '1';
    }

    /** Show the holding page to visitors (and stop) when the mode is on. */
    public static function enforce(): void
    {
        if (!self::enabled() || Auth::check()) {
            return;
        }
        // 503 + Retry-After tells search engines this is temporary.
        http_response_code(503);
        header('Retry-After: 86400');
        header('Cache-Control: no-store');
        render('construction', [
            'siteName' => (string)Settings::get('site_name', 'Website'),
            'heading'  => (string)(Settings::get('construction_heading', '') ?: 'We’re Building Something New'),
            'message'  => (string)(Settings::get('construction_message', '') ?: 'Our new website is under construction and will be ready soon. Thank you for your patience.'),
            'header'   => ContentRepository::page('global')['header']['data'] ?? [],
        ]);
        exit;
    }
}
