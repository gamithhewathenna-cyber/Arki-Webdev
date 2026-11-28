<?php
declare(strict_types=1);

/**
 * Contact page enquiry form: validation, spam protection, saving and the
 * optional notification email. Uses Post/Redirect/Get – results are handed
 * to the page template through the session.
 */
final class ContactForm
{
    private const SESSION_KEY = 'contact_form';

    /** Process a POST and redirect back to the form. */
    public static function handle(): void
    {
        csrf_verify();
        $back = url('contact/') . '#contact-form';

        // Honeypot: real visitors never fill this hidden field. Pretend success for bots.
        if (trim((string)($_POST['website'] ?? '')) !== '') {
            $_SESSION[self::SESSION_KEY] = ['sent' => true];
            redirect($back);
        }

        $section = ContentRepository::page('contact')['contact_intro']['data'] ?? [];
        $topics = self::topics($section);

        $in = static fn(string $k, int $max) => mb_substr(trim(str_replace("\r\n", "\n", (string)($_POST[$k] ?? ''))), 0, $max);
        $d = [
            'first_name' => $in('first_name', 100),
            'last_name'  => $in('last_name', 100),
            'email'      => $in('email', 190),
            'phone'      => $in('phone', 40),
            'topic'      => $in('topic', 150),
            'message'    => $in('message', 5000),
            'newsletter' => !empty($_POST['newsletter']) ? 1 : 0,
        ];

        $errors = [];
        if ($d['first_name'] === '') $errors['first_name'] = 'Please enter your first name.';
        if ($d['last_name'] === '')  $errors['last_name'] = 'Please enter your last name.';
        if (!filter_var($d['email'], FILTER_VALIDATE_EMAIL)) $errors['email'] = 'Please enter a valid email address.';
        if ($d['phone'] !== '' && !preg_match('/^[0-9 +()\-.]{6,40}$/', $d['phone'])) $errors['phone'] = 'Please enter a valid phone number.';
        if ($d['topic'] !== '' && !in_array($d['topic'], $topics, true)) $d['topic'] = '';
        if (mb_strlen($d['message']) < 5) $errors['message'] = 'Please write a short message.';

        if (!$errors && !throttle_allowed('contact', 5, 30)) {
            $errors['form'] = 'You have sent several messages in a short time. Please try again a little later or contact us directly.';
        }
        if ($errors) {
            $_SESSION[self::SESSION_KEY] = ['errors' => $errors, 'old' => $d];
            redirect($back);
        }

        throttle_hit('contact'); // counts sent enquiries per visitor (max 5 per 30 minutes)
        Database::run(
            'INSERT INTO enquiries (first_name, last_name, email, phone, topic, message, newsletter, status, ip, user_agent, created_at)
             VALUES (?, ?, ?, ?, ?, ?, ?, \'new\', ?, ?, NOW())',
            [$d['first_name'], $d['last_name'], $d['email'], $d['phone'] ?: null, $d['topic'] ?: null, $d['message'], $d['newsletter'],
             client_ip(), mb_substr((string)($_SERVER['HTTP_USER_AGENT'] ?? ''), 0, 255)]
        );
        $id = Database::lastId();
        if (self::notify($d, (string)($section['notify_email'] ?? ''))) {
            Database::run('UPDATE enquiries SET emailed = 1 WHERE id = ?', [$id]);
        }

        $_SESSION[self::SESSION_KEY] = ['sent' => true];
        redirect($back);
    }

    /** Result of the last submission (read once): ['sent' => bool, 'errors' => [], 'old' => []]. */
    public static function state(): array
    {
        $s = $_SESSION[self::SESSION_KEY] ?? [];
        unset($_SESSION[self::SESSION_KEY]);
        return $s + ['sent' => false, 'errors' => [], 'old' => []];
    }

    /** "I'm enquiring about…" options from the CMS (one per line). */
    public static function topics(array $section): array
    {
        return array_values(array_filter(array_map('trim', preg_split('/\R/', (string)($section['topics'] ?? '')))));
    }

    /** Email the enquiry to the addresses set in the CMS. Returns true when sent. */
    private static function notify(array $d, string $to): bool
    {
        $list = array_filter(array_map('trim', explode(',', $to)), static fn($a) => filter_var($a, FILTER_VALIDATE_EMAIL));
        if (!$list || !function_exists('mail')) {
            return false;
        }
        $site = (string)Settings::get('site_name', 'Website');
        $host = preg_replace('/[^a-z0-9.\-]/i', '', (string)($_SERVER['HTTP_HOST'] ?? 'localhost'));
        $host = preg_replace('/^www\./i', '', $host) ?: 'localhost';
        $clean = static fn(string $s) => trim(str_replace(["\r", "\n"], ' ', $s)); // no header injection
        $name = $clean($d['first_name'] . ' ' . $d['last_name']);

        $body = "New enquiry from the website contact form\n\n"
            . "Name:    {$name}\n"
            . "Email:   {$d['email']}\n"
            . 'Phone:   ' . ($d['phone'] ?: '–') . "\n"
            . 'Topic:   ' . ($d['topic'] ?: '–') . "\n"
            . 'Updates: ' . ($d['newsletter'] ? 'Yes, wants occasional updates' : 'No') . "\n\n"
            . "Message:\n{$d['message']}\n\n"
            . "— Also saved in the CMS under Enquiries.\n";
        $headers = [
            'From: ' . $clean($site) . ' <no-reply@' . $host . '>',
            'Reply-To: ' . $clean($name) . ' <' . $clean($d['email']) . '>',
            'Content-Type: text/plain; charset=UTF-8',
            'X-Mailer: ARKI CMS',
        ];
        $subject = '=?UTF-8?B?' . base64_encode('New enquiry: ' . $name . ($d['topic'] ? ' – ' . $d['topic'] : '')) . '?=';
        return @mail(implode(', ', $list), $subject, $body, implode("\r\n", $headers));
    }
}
