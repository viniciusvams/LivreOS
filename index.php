<?php

use Illuminate\Foundation\Application;
use Illuminate\Http\Request;

define('LARAVEL_START', microtime(true));

error_reporting(E_ALL);
ini_set('display_errors', '0');

set_exception_handler(static function (Throwable $e): void {
    $msg = '[bootstrap exception] ' . $e->getMessage() . ' in ' . $e->getFile() . ':' . $e->getLine();
    @file_put_contents(__DIR__ . '/bootstrap-error.log', date('c') . ' ' . $msg . PHP_EOL . $e->getTraceAsString() . PHP_EOL . PHP_EOL, FILE_APPEND);
    http_response_code(500);
    echo 'Erro ao iniciar sistema. Veja bootstrap-error.log na pasta publica.';
    exit;
});

set_error_handler(static function (int $severity, string $message, string $file, int $line): bool {
    if (!(error_reporting() & $severity)) {
        return false;
    }
    throw new ErrorException($message, 0, $severity, $file, $line);
});

$lockFile = __DIR__ . '/installed.lock';
$configFile = __DIR__ . '/path-config.php';

if (!file_exists($lockFile)) {
    $uri = $_SERVER['REQUEST_URI'] ?? '/';
    if (strpos($uri, '/install') === false) {
        header('Location: /install/');
        exit;
    }
}

if (!file_exists($configFile)) {
    http_response_code(500);
    echo 'Arquivo de configuracao nao encontrado: path-config.php';
    exit;
}

$cfg = require $configFile;
$systemPath = rtrim((string)($cfg['system_path'] ?? ''), '/\\');

if ($systemPath === '' || !is_dir($systemPath)) {
    http_response_code(500);
    echo 'Caminho do sistema invalido em path-config.php';
    exit;
}

if (file_exists($maintenance = $systemPath . '/storage/framework/maintenance.php')) {
    require $maintenance;
}

require $systemPath . '/vendor/autoload.php';

/** @var Application $app */
$app = require_once $systemPath . '/bootstrap/app.php';

// Sempre a pasta onde esta este index.php (document root real). path-config.public_path pode estar
// errado no wizard e quebraria @vite / manifest em sistema_erp/public/build.
if (method_exists($app, 'usePublicPath')) {
    $app->usePublicPath(__DIR__);
}

$app->handleRequest(Request::capture());