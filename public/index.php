<?php

use Illuminate\Foundation\Application;
use Illuminate\Http\Request;

define('LARAVEL_START', microtime(true));

// Determine if the application is in maintenance mode...
if (file_exists($maintenance = __DIR__.'/../storage/framework/maintenance.php')) {
    require $maintenance;
}

// Ensure Git is in PATH on Windows to prevent dev autoloader proc_open warnings
if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
    putenv('PATH=' . getenv('PATH') . ';C:\Program Files\Git\cmd');
}

// Register the Composer autoloader...
require __DIR__.'/../vendor/autoload.php';

// Bootstrap Laravel and handle the request...
/** @var Application $app */
$app = require_once __DIR__.'/../bootstrap/app.php';

$app->handleRequest(Request::capture());
