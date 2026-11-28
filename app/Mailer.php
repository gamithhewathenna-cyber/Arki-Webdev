<?php
declare(strict_types=1);

/**
 * Account emails – prepared, NOT sent yet.
 *
 * Every message the website will eventually send (welcome, agent received /
 * approved / rejected, password reset) is written to the `email_outbox`
 * table with status 'pending'. At launch, a sender (cron job or SMTP
 * library) only needs to read pending rows, send them and set
 * status = 'sent'. Nothing else in the account system has to change.
 */
final class Mailer
{
    public static function queue(string $template, string $toEmail, string $toName, string $subject, array $payload = []): void
    {
        try {
            Database::run(
                'INSERT INTO email_outbox (template, to_email, to_name, subject, payload, status, created_at) VALUES (?, ?, ?, ?, ?, \'pending\', NOW())',
                [$template, $toEmail, $toName, $subject, json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)]
            );
        } catch (Throwable $e) {
            error_log('Mailer::queue failed: ' . $e->getMessage());
        }
    }

    /** Absolute URL for links inside emails. */
    public static function absoluteUrl(string $path): string
    {
        $host = preg_replace('/[^a-z0-9.\-:]/i', '', (string)($_SERVER['HTTP_HOST'] ?? 'localhost'));
        return (is_https() ? 'https://' : 'http://') . $host . url($path);
    }
}
