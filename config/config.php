<?php
/**
 * ARKI Environments – site configuration.
 *
 * Edit the four database values below with the details you created in
 * cPanel → MySQL® Databases. On cPanel the database name and user are
 * prefixed with your cPanel username, e.g. "cpuser_arki".
 *
 * This file is blocked from web access by config/.htaccess.
 */
return [
    'db' => [
        'host' => 'localhost',               // usually "localhost" on cPanel
        'port' => 3306,
        'name' => 'CPANELUSER_arki',         // ← your database name
        'user' => 'CPANELUSER_arkiuser',     // ← your database user
        'pass' => 'YOUR_DATABASE_PASSWORD',  // ← your database user's password
    ],

    // Leave empty when the site is in the domain root (public_html).
    // If you upload into a sub-folder, e.g. public_html/dev, set '/dev'.
    'base_url' => '',

    'timezone'     => 'Australia/Melbourne',
    'session_name' => 'arki_session',

    // Set to true only while troubleshooting – never on a live site.
    'debug' => false,
];
