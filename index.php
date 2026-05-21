<?php

declare(strict_types=1);

date_default_timezone_set('America/Caracas');

require_once __DIR__ . '/vendor/autoload.php';

// Define the base URL dynamically to avoid issues with absolute/relative paths in views
$baseDir = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME']));
define('BASE_URL', rtrim($baseDir, '/') . '/');

use SysInescolara\controllers\FrontController;

new FrontController();
