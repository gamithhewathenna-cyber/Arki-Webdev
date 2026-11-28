<?php
/** Set a new password from a reset link (/account/reset-password/?t=…) */
declare(strict_types=1);

require dirname(__DIR__, 2) . '/app/bootstrap.php';
Account::reset();
