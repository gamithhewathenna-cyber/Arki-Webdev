<?php
/**
 * Contact Us page (/contact/). Content: Admin → Contact Page.
 * Form submissions are saved under Admin → Enquiries (and optionally emailed).
 */
declare(strict_types=1);

require dirname(__DIR__) . '/app/bootstrap.php';
Construction::enforce();

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST' && isset($_POST['contact_form'])) {
    ContentRepository::sync('contact');
    ContactForm::handle(); // always redirects back to the form
}

render_inner_page('contact', 'Contact Us');
