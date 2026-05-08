<?php

if (!defined('LARAVEL_START')) {
    define('LARAVEL_START', microtime(true));
}

$basePath = dirname(__DIR__);
require $basePath . '/vendor/autoload.php';

// Configurações críticas para a Vercel
putenv('SESSION_DRIVER=cookie');
$_ENV['SESSION_DRIVER'] = 'cookie';
putenv('CACHE_DRIVER=array');
$_ENV['CACHE_DRIVER'] = 'array';
putenv('CACHE_STORE=array');
$_ENV['CACHE_STORE'] = 'array';

// Otimização do URL
$host = $_SERVER['HTTP_HOST'] ?? 'livreoos.vercel.app';
$secureUrl = "https://" . $host;
putenv("APP_URL=" . $secureUrl);
$_ENV['APP_URL'] = $secureUrl;

$_SERVER['HTTPS'] = 'on';
$_SERVER['HTTP_X_FORWARDED_PROTO'] = 'https';

$app = require_once $basePath . '/bootstrap/app.php';

$app->useStoragePath('/tmp/storage');
$app->useBootstrapPath('/tmp/bootstrap');

$dirs = [
    '/tmp/storage/app',
    '/tmp/storage/logs',
    '/tmp/storage/framework/cache/data',
    '/tmp/storage/framework/views',
    '/tmp/storage/framework/sessions',
    '/tmp/bootstrap/cache'
];

foreach ($dirs as $dir) {
    if (!is_dir($dir)) {
        mkdir($dir, 0755, true);
    }
}

$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
$request = Illuminate\Http\Request::capture();
$response = $kernel->handle($request);

$response->send();
$kernel->terminate($request, $response);