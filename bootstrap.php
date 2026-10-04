<?php

declare(strict_types=1);

/**
 * Single entry bootstrap for the whole monorepo.
 * All apps should require this file only.
 */

define('ROOT_PATH', __DIR__);

require_once ROOT_PATH . '/packages/core/src/Bootstrap.php';

App\Bootstrap::init(ROOT_PATH);
