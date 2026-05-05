<?php

/**
 * Roda `php artisan migrate --force` pelo navegador (hospedagem sem SSH/cron).
 *
 * ATENÇÃO: apague este arquivo do servidor imediatamente após o uso.
 *
 * Uso:
 *   1. Defina ONEOFF_MIGRATE_TOKEN (string longa e secreta).
 *   2. Acesse: https://SEU-DOMINIO/oneoff-artisan-migrate.php?token=SUA_STRING_SECRETA
 *   3. Leia a saída (sucesso ou erros).
 *   4. Remova oneoff-artisan-migrate.php do public/.
 */
declare(strict_types=1);

use Illuminate\Support\Facades\Artisan;

const ONEOFF_MIGRATE_TOKEN = 'ALTERE_ESTE_TOKEN_ANTES_DE_USAR';

header('Content-Type: text/plain; charset=utf-8');
header('X-Robots-Tag: noindex');

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'GET') {
    http_response_code(405);
    exit("Method Not Allowed\n");
}

$expected = ONEOFF_MIGRATE_TOKEN;
$given = isset($_GET['token']) ? (string) $_GET['token'] : '';

if ($expected === '' || $expected === 'ALTERE_ESTE_TOKEN_ANTES_DE_USAR' || $given === '' || ! hash_equals($expected, $given)) {
    http_response_code(403);
    exit("Forbidden\n");
}

$base = dirname(__DIR__);

require $base.'/vendor/autoload.php';

/** @var \Illuminate\Foundation\Application $app */
$app = require_once $base.'/bootstrap/app.php';

$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

try {
    Artisan::call('migrate', ['--force' => true]);
    echo Artisan::output();
    echo "\n---\nOK. Apague public/oneoff-artisan-migrate.php agora.\n";
} catch (\Throwable $e) {
    http_response_code(500);
    echo 'Erro: '.$e->getMessage()."\n";
    echo $e->getFile().':'.$e->getLine()."\n";
}
