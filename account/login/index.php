<?php
/** Log in (/account/login/) */
declare(strict_types=1);

require dirname(__DIR__, 2) . '/app/bootstrap.php';
Account::login();
