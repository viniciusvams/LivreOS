<?php

/**
 * Executa UMA migration via navegador (sem SSH/cron): taxa_movimentacao_id em baixas_titulos.
 *
 * Lista de arquivos para subir na hospedagem (raiz do projeto Laravel):
 *   database/migrations/2026_04_08_200000_add_taxa_movimentacao_id_to_baixas_titulos.php
 *   app/Services/Financeiro/BaixaTituloTaxaMovimentacaoResolver.php
 *   app/Models/BaixaTitulo.php
 *   app/Http/Controllers/Financeiro/ContaReceberController.php
 *   app/Http/Controllers/OrdemServicoController.php
 *   app/Services/FinanceiroVendaPagamentosService.php
 *   app/Console/Commands/CompletarEstornoTaxaAdquirenteCommand.php
 *   public/oneoff-migrate-taxa-baixa.php (este arquivo)
 *
 * Opcional (comando / reparo histórico):
 *   public/repair-estorno-taxa-oneoff.php
 *
 * Uso:
 *   1. Altere MIGRATE_ONEOFF_TOKEN abaixo.
 *   2. Simular SQL: .../oneoff-migrate-taxa-baixa.php?token=SEU_TOKEN&pretend=1
 *   3. Executar:   .../oneoff-migrate-taxa-baixa.php?token=SEU_TOKEN
 *   4. Apague este arquivo no servidor após "Nothing to migrate" ou sucesso.
 */
declare(strict_types=1);

/** @var string Mesmo valor em ?token= na URL */
const MIGRATE_ONEOFF_TOKEN = 'ALTERE_ESTE_TOKEN_ANTES_DE_USAR';

const MIGRATION_REL_PATH = 'database/migrations/2026_04_08_200000_add_taxa_movimentacao_id_to_baixas_titulos.php';

header('Content-Type: text/plain; charset=utf-8');
header('X-Robots-Tag: noindex');

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'GET') {
    http_response_code(405);
    exit("Method Not Allowed\n");
}

$basePath = dirname(__DIR__);
$expected = MIGRATE_ONEOFF_TOKEN;
$given = isset($_GET['token']) ? (string) $_GET['token'] : '';

if ($expected === '' || $expected === 'ALTERE_ESTE_TOKEN_ANTES_DE_USAR' || $given === '' || ! hash_equals($expected, $given)) {
    http_response_code(403);
    exit("Forbidden\n");
}

$migrationFullPath = $basePath.'/'.MIGRATION_REL_PATH;
if (! is_readable($migrationFullPath)) {
    http_response_code(500);
    exit("Arquivo da migration não encontrado: ".MIGRATION_REL_PATH."\n");
}

require $basePath.'/vendor/autoload.php';

/** @var \Illuminate\Foundation\Application $app */
$app = require_once $basePath.'/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$options = [
    '--path' => MIGRATION_REL_PATH,
    '--force' => true,
];

if (isset($_GET['pretend']) && $_GET['pretend'] !== '0' && $_GET['pretend'] !== 'false') {
    $options['--pretend'] = true;
}

\Illuminate\Support\Facades\Artisan::call('migrate', $options);
echo \Illuminate\Support\Facades\Artisan::output();
