<?php
/** Log out (POST only) (/account/logout/) */
declare(strict_types=1);

require dirname(__DIR__, 2) . '/app/bootstrap.php';
Account::logout();
