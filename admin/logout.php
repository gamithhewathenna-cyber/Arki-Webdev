<?php
declare(strict_types=1);
require __DIR__ . '/_init.php';
admin_post();
Auth::logout();
redirect(url('admin/login.php'));
