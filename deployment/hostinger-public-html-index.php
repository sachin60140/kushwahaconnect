<?php

use Illuminate\Foundation\Application;
use Illuminate\Http\Request;

define('LARAVEL_START', microtime(true));

/*
|--------------------------------------------------------------------------
| Hostinger Shared Hosting public_html Front Controller
|--------------------------------------------------------------------------
|
| Use this file only when the domain document root cannot be pointed to the
| Laravel project's public directory. Place the Laravel project beside
| public_html in a folder named "kushwahaconnect".
|
*/

$appBasePath = __DIR__.'/../kushwahaconnect';

if (file_exists($maintenance = $appBasePath.'/storage/framework/maintenance.php')) {
    require $maintenance;
}

require $appBasePath.'/vendor/autoload.php';

/** @var Application $app */
$app = require_once $appBasePath.'/bootstrap/app.php';

$app->handleRequest(Request::capture());
