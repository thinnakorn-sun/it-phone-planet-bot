<?php

declare(strict_types=1);

require_once __DIR__ . '/_init.php';

use App\Auth;
use App\Helpers;

Auth::logout();
Helpers::redirect(Helpers::adminUrl('login.php'));
