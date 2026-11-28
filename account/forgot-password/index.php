<?php
/** Forgot password (/account/forgot-password/) */
declare(strict_types=1);

require dirname(__DIR__, 2) . '/app/bootstrap.php';
Account::forgot();
