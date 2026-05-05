<?php

/**
 * Execução única via navegador (hospedagem sem SSH).
 *
 * 1. Altere REPAIR_ONEOFF_TOKEN abaixo para um segredo forte (e único).
 * 2. Dry-run: .../repair-estorno-taxa-oneoff.php?token=SEU_TOKEN&dry_run=1
 * 3. Executar: .../repair-estorno-taxa-oneoff.php?token=SEU_TOKEN
 * 4. Opcional: &conta_receber_id=123
 * 5. APAGUE este arquivo depois de usar (o token fica visível no código).
 */
declare(strict_types=1);

/** @var string Defina aqui o mesmo valor que passará em ?token= na URL */
const REPAIR_ONEOFF_TOKEN = 'testtoken123';

header('Content-Type: text/plain; charset=utf-8; X-Robots-Tag: noindex');

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'GET') {
    http_response_code(405);
    exit("Method Not Allowed\n");
}

$basePath = dirname(__DIR__);
$expected = REPAIR_ONEOFF_TOKEN;
$given = isset($_GET['token']) ? (string) $_GET['token'] : '';

if ($expected === '' || $expected === 'ALTERE_ESTE_TOKEN_ANTES_DE_USAR' || $given === '' || ! hash_equals($expected, $given)) {
    http_response_code(403);
    exit("Forbidden\n");
}

require $basePath.'/vendor/autoload.php';

/** @var \Illuminate\Foundation\Application $app */
$app = require_once $basePath.'/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$params = [];
if (isset($_GET['dry_run']) && $_GET['dry_run'] !== '0' && $_GET['dry_run'] !== 'false') {
    $params['--dry-run'] = true;
}
if (! empty($_GET['conta_receber_id'])) {
    $params['--conta-receber-id'] = (string) (int) $_GET['conta_receber_id'];
}

\Illuminate\Support\Facades\Artisan::call('financeiro:completar-estorno-taxa-adquirente', $params);
echo \Illuminate\Support\Facades\Artisan::output();
