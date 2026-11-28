<?php
/** Create an account – customer or agent (/account/register/) */
declare(strict_types=1);

require dirname(__DIR__, 2) . '/app/bootstrap.php';
Account::register();
