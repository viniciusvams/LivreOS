<?php

/**
 * One-off: remove duplicidades de movimentação (1ª leva + estornos), mantendo só a leva "Taxa adquirente".
 *
 * Caso típico: OS parcelada com Recebimento+Taxa de recebimento, estorno em massa, nova leva Recebimento+Taxa adquirente.
 * Este script faz soft delete das linhas de ajuste (Estorno:), das saídas "Taxa de recebimento:" e das entradas
 * duplicadas por parcela (mantém a de data_movimentacao mais recente).
 *
 * Uso (sempre dry-run primeiro):
 *   https://SEU-DOMINIO/repair-os-mov-duplicadas-oneoff.php?token=SEU_TOKEN&dry_run=1
 *   https://SEU-DOMINIO/repair-os-mov-duplicadas-oneoff.php?token=SEU_TOKEN
 * Opcional na URL: os_ref=OS-5470040&conta_id=123&conta_nome=Conta%20SumUp&parcelas=5&tenant_id=1&fix_baixas=1
 *
 * 1. Defina ONEOFF_TOKEN abaixo (string longa e secreta).
 * 2. Ajuste OS_REF_DEFAULT e CONTA_BANCARIA_* se não usar query string.
 * 3. Rode com dry_run=1 e confira a lista de IDs.
 * 4. Rode sem dry_run; com fix_baixas=1 alinha baixas órfãs (soft delete quando seguro).
 * 5. APAGUE este arquivo do public/ após o sucesso.
 */
declare(strict_types=1);

use App\Models\BaixaTitulo;
use App\Models\ContaBancaria;
use App\Models\MovimentacaoFinanceira;
use Illuminate\Support\Facades\DB;

const ONEOFF_TOKEN = 'ALTERE_ESTE_TOKEN_ANTES_DE_USAR';

/** Substring única nas descrições (ex.: OS-5470040) */
const OS_REF_DEFAULT = 'OS-5470040';

/** Se > 0, usa este ID; senão resolve por CONTA_BANCARIA_NOME_DEFAULT */
const CONTA_BANCARIA_ID_DEFAULT = 0;

const CONTA_BANCARIA_NOME_DEFAULT = 'Conta SumUp';

/** Número esperado de parcelas (validação) */
const PARCELAS_TOTAL_DEFAULT = 5;

/** null = não filtrar tenant */
const TENANT_ID_DEFAULT = null;

header('Content-Type: text/plain; charset=utf-8');
header('X-Robots-Tag: noindex');

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'GET') {
    http_response_code(405);
    exit("Method Not Allowed\n");
}

$expected = ONEOFF_TOKEN;
$given = isset($_GET['token']) ? (string) $_GET['token'] : '';

if ($expected === '' || $expected === 'ALTERE_ESTE_TOKEN_ANTES_DE_USAR' || $given === '' || ! hash_equals($expected, $given)) {
    http_response_code(403);
    exit("Forbidden\n");
}

$basePath = dirname(__DIR__);

require $basePath.'/vendor/autoload.php';

/** @var \Illuminate\Foundation\Application $app */
$app = require_once $basePath.'/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$osRef = isset($_GET['os_ref']) ? trim((string) $_GET['os_ref']) : OS_REF_DEFAULT;
if ($osRef === '') {
    exit("ERRO: os_ref vazio.\n");
}

$parcelasTotal = isset($_GET['parcelas']) ? max(1, (int) $_GET['parcelas']) : PARCELAS_TOTAL_DEFAULT;

$tenantId = TENANT_ID_DEFAULT;
if (array_key_exists('tenant_id', $_GET) && $_GET['tenant_id'] !== '' && $_GET['tenant_id'] !== null) {
    $tenantId = (int) $_GET['tenant_id'];
}

$contaId = CONTA_BANCARIA_ID_DEFAULT > 0 ? CONTA_BANCARIA_ID_DEFAULT : 0;
if (isset($_GET['conta_id']) && $_GET['conta_id'] !== '' && (int) $_GET['conta_id'] > 0) {
    $contaId = (int) $_GET['conta_id'];
}

if ($contaId <= 0) {
    $nome = isset($_GET['conta_nome']) ? trim((string) $_GET['conta_nome']) : CONTA_BANCARIA_NOME_DEFAULT;
    $conta = ContaBancaria::query()->where('nome', $nome)->first();
    if (! $conta) {
        exit("ERRO: Conta bancária não encontrada pelo nome: {$nome}\n");
    }
    $contaId = (int) $conta->id;
}

$dryRun = isset($_GET['dry_run']) && $_GET['dry_run'] !== '0' && $_GET['dry_run'] !== 'false';
$fixBaixas = isset($_GET['fix_baixas']) && $_GET['fix_baixas'] !== '0' && $_GET['fix_baixas'] !== 'false';

$likeOs = '%'.$osRef.'%';

/**
 * @return \Illuminate\Database\Eloquent\Builder<\App\Models\MovimentacaoFinanceira>
 */
function baseMovQuery(int $contaId, string $likeOs, ?int $tenantId): \Illuminate\Database\Eloquent\Builder
{
    $q = MovimentacaoFinanceira::query()
        ->whereNull('data_cancelamento')
        ->where('conta_bancaria_id', $contaId)
        ->where('descricao', 'like', $likeOs);

    if ($tenantId !== null) {
        $q->where('tenant_id', $tenantId);
    }

    return $q;
}

/**
 * @return array{0:int,1:int}|null [parcela, total]
 */
function extrairParcela(string $desc): ?array
{
    if (preg_match('/Parcela\s+(\d+)\/(\d+)/u', $desc, $m)) {
        return [(int) $m[1], (int) $m[2]];
    }

    return null;
}

echo "=== repair-os-mov-duplicadas-oneoff ===\n";
echo "os_ref={$osRef} conta_bancaria_id={$contaId} parcelas={$parcelasTotal} tenant=".($tenantId === null ? '(todos)' : (string) $tenantId)."\n";
echo 'dry_run='.($dryRun ? 'SIM' : 'NAO').' fix_baixas='.($fixBaixas ? 'SIM' : 'NAO')."\n\n";

// --- (E) Estornos ajuste
$qEstorno = baseMovQuery($contaId, $likeOs, $tenantId)
    ->where('origem', 'ajuste')
    ->where('descricao', 'like', 'Estorno:%');

$idsEstorno = $qEstorno->pluck('id')->map(fn ($id) => (int) $id)->all();
echo 'Estornos (ajuste) a remover: '.count($idsEstorno)."\n";
foreach ($qEstorno->get(['id', 'tipo', 'origem', 'data_movimentacao', 'descricao']) as $m) {
    echo '  #'.$m->id.' '.$m->data_movimentacao->format('Y-m-d').' '.$m->tipo.' | '.mb_substr($m->descricao, 0, 80)."\n";
}

// --- (T1) Taxa de recebimento (1ª leva)
$qTaxaRec = baseMovQuery($contaId, $likeOs, $tenantId)
    ->where('tipo', 'saida')
    ->where('descricao', 'like', 'Taxa de recebimento:%');

$idsTaxaRec = $qTaxaRec->pluck('id')->map(fn ($id) => (int) $id)->all();
echo "\nSaidas Taxa de recebimento a remover: ".count($idsTaxaRec)."\n";
foreach ($qTaxaRec->get(['id', 'data_movimentacao', 'descricao']) as $m) {
    echo '  #'.$m->id.' '.$m->data_movimentacao->format('Y-m-d').' | '.mb_substr($m->descricao, 0, 80)."\n";
}

// --- (R1) Entradas duplicadas por parcela: manter max(data_movimentacao)
$qEntradas = baseMovQuery($contaId, $likeOs, $tenantId)
    ->where('tipo', 'entrada')
    ->where('origem', 'conta_receber')
    ->where('descricao', 'like', 'Recebimento:%');

$porParcela = [];
foreach ($qEntradas->get(['id', 'data_movimentacao', 'descricao']) as $m) {
    $p = extrairParcela($m->descricao);
    if ($p === null) {
        echo "\nAVISO: entrada #{$m->id} sem Parcela N/M na descricao — ignorada no agrupamento.\n";

        continue;
    }
    [$num, $tot] = $p;
    if ($tot !== $parcelasTotal) {
        echo "\nAVISO: entrada #{$m->id} parcela {$num}/{$tot} difere de parcelas esperadas ({$parcelasTotal}).\n";
    }
    $porParcela[$num][] = ['id' => (int) $m->id, 'data' => $m->data_movimentacao->format('Y-m-d'), 'desc' => $m->descricao];
}

$idsEntradaRemover = [];
$idsEntradaManter = [];

foreach ($porParcela as $num => $lista) {
    if (count($lista) <= 1) {
        $idsEntradaManter[$num] = $lista[0]['id'] ?? null;

        continue;
    }
    usort($lista, function ($a, $b) {
        return strcmp($b['data'], $a['data']);
    });
    $manter = $lista[0]['id'];
    $idsEntradaManter[$num] = $manter;
    foreach (array_slice($lista, 1) as $row) {
        $idsEntradaRemover[] = $row['id'];
    }
}

echo "\nEntradas Recebimento duplicadas a remover: ".count($idsEntradaRemover)."\n";
foreach ($idsEntradaRemover as $rid) {
    $m = MovimentacaoFinanceira::query()->find($rid);
    if ($m) {
        echo '  #'.$m->id.' '.$m->data_movimentacao->format('Y-m-d').' | '.mb_substr($m->descricao, 0, 80)."\n";
    }
}

echo "\nEntradas a MANTER (por parcela):\n";
ksort($idsEntradaManter);
foreach ($idsEntradaManter as $num => $mid) {
    if ($mid === null) {
        echo "  Parcela {$num}: (nenhuma)\n";
    } else {
        $m = MovimentacaoFinanceira::query()->find($mid);
        echo '  Parcela '.$num.': #'.$mid.($m ? ' '.$m->data_movimentacao->format('Y-m-d') : '')."\n";
    }
}

// --- Validação: Taxa adquirente (1 por parcela)
$qTaxaAdq = baseMovQuery($contaId, $likeOs, $tenantId)
    ->where('tipo', 'saida')
    ->where('descricao', 'like', 'Taxa adquirente:%');

$taxaAdqPorParcela = [];
foreach ($qTaxaAdq->get(['id', 'data_movimentacao', 'descricao']) as $m) {
    $p = extrairParcela($m->descricao);
    if ($p === null) {
        echo "\nAVISO: Taxa adquirente #{$m->id} sem Parcela N/M — não contada na validação.\n";

        continue;
    }
    $taxaAdqPorParcela[$p[0]][] = (int) $m->id;
}

echo "\n--- Validação (esperado 1 entrada mantida e 1 Taxa adquirente por parcela 1..{$parcelasTotal}) ---\n";
$erros = [];
for ($n = 1; $n <= $parcelasTotal; $n++) {
    $entOk = isset($idsEntradaManter[$n]) && $idsEntradaManter[$n] !== null;
    $taxaN = $taxaAdqPorParcela[$n] ?? [];
    $taxaOk = count($taxaN) === 1;

    if (! $entOk) {
        $erros[] = "Parcela {$n}: falta entrada a manter.";
    }
    if (count($taxaN) === 0) {
        $erros[] = "Parcela {$n}: nenhuma saida Taxa adquirente.";
    }
    if (count($taxaN) > 1) {
        $erros[] = "Parcela {$n}: varias saidas Taxa adquirente (ids: ".implode(',', $taxaN).') — ajuste manual.';
    }
    echo "Parcela {$n}: entrada_manter=".($entOk ? '#'.$idsEntradaManter[$n] : 'FALTA').' taxa_adquirente='.(count($taxaN) === 1 ? '#'.$taxaN[0] : '('.count($taxaN).')')."\n";
}

if ($erros !== []) {
    echo "\nABORTADO — corrija os dados antes de executar:\n";
    foreach ($erros as $e) {
        echo ' - '.$e."\n";
    }
    exit(1);
}

$allDeleteIds = array_values(array_unique(array_merge($idsEstorno, $idsTaxaRec, $idsEntradaRemover)));
sort($allDeleteIds);

echo "\n--- Total IDs a soft-delete: ".count($allDeleteIds)." ---\n";
echo implode(', ', $allDeleteIds)."\n";

if ($dryRun) {
    echo "\nFIM dry-run (nenhuma alteração). Remova dry_run=1 para executar.\n";
    exit(0);
}

DB::beginTransaction();
try {
    foreach ($allDeleteIds as $mid) {
        $mov = MovimentacaoFinanceira::query()->find($mid);
        if (! $mov) {
            continue;
        }
        if ($mov->data_cancelamento !== null) {
            continue;
        }
        $mov->delete();
    }

    if ($fixBaixas) {
        echo "\n--- fix_baixas ---\n";
        $baixas = BaixaTitulo::query()
            ->where('tipo_titulo', 'conta_receber')
            ->where(function ($q) use ($allDeleteIds) {
                $q->whereIn('movimentacao_id', $allDeleteIds)
                    ->orWhereIn('taxa_movimentacao_id', $allDeleteIds);
            })
            ->get();

        $removidas = 0;
        foreach ($baixas as $bx) {
            $temIrmaValida = BaixaTitulo::query()
                ->where('titulo_id', $bx->titulo_id)
                ->where('id', '!=', $bx->id)
                ->where('tipo_titulo', 'conta_receber')
                ->where('estornado', false)
                ->whereNotNull('movimentacao_id')
                ->whereNotIn('movimentacao_id', $allDeleteIds)
                ->whereHas('movimentacao', function ($q) {
                    $q->whereNull('deleted_at');
                })
                ->exists();

            if ($temIrmaValida || $bx->estornado) {
                $bx->delete();
                $removidas++;
                echo "Baixa #{$bx->id} titulo_id={$bx->titulo_id} soft-deletada (irma_com_mov_mantida=".($temIrmaValida ? 'sim' : 'nao').' estornado='.($bx->estornado ? 'sim' : 'nao').").\n";
            } else {
                echo "AVISO: Baixa #{$bx->id} titulo_id={$bx->titulo_id} aponta para mov removido e sem irma com mov mantida — NAO removida (revise titulo).\n";
            }
        }
        echo "Baixas soft-deletadas: {$removidas}\n";
    }

    DB::commit();
} catch (\Throwable $e) {
    DB::rollBack();
    http_response_code(500);
    echo 'ERRO: '.$e->getMessage()."\n".$e->getFile().':'.$e->getLine()."\n";
    exit(1);
}

echo "\nOK. Soft-delete aplicado. Verifique o extrato e apague public/repair-os-mov-duplicadas-oneoff.php\n";
