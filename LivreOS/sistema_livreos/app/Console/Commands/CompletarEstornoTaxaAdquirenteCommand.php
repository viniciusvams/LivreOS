<?php

/**
 * Componente da aplicação LivreOS
 *
 * @author    viniciusvams
 * @copyright 2024-2026 LivreOS
 * @license   https://www.gnu.org/licenses/agpl-3.0.txt AGPL-3.0
 */

namespace App\Console\Commands;

use App\Models\BaixaTitulo;
use App\Models\MovimentacaoFinanceira;
use App\Services\Financeiro\BaixaTituloTaxaMovimentacaoResolver;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * Cria movimentações de estorno da taxa (entrada ajuste) quando faltaram após estorno de baixa
 * (usa taxa_movimentacao_id ou vínculo estrutural, não o texto da descrição).
 *
 * Uso: php artisan financeiro:completar-estorno-taxa-adquirente
 *      php artisan financeiro:completar-estorno-taxa-adquirente --dry-run
 *      php artisan financeiro:completar-estorno-taxa-adquirente --conta-receber-id=123
 */
class CompletarEstornoTaxaAdquirenteCommand extends Command
{
    protected $signature = 'financeiro:completar-estorno-taxa-adquirente
                            {--dry-run : Apenas listar o que seria criado}
                            {--conta-receber-id= : Limitar a um título (parcela)}';

    protected $description = 'Insere entradas de estorno da taxa de adquirente que faltaram em estornos de conta a receber';

    public function handle(): int
    {
        $dryRun = (bool) $this->option('dry-run');
        $crIdFilter = $this->option('conta-receber-id');
        $crIdFilter = $crIdFilter !== null && $crIdFilter !== '' ? (int) $crIdFilter : null;

        $query = BaixaTitulo::query()
            ->where('tipo_titulo', 'conta_receber')
            ->where('estornado', true)
            ->with('movimentacao');

        if ($crIdFilter) {
            $query->where('titulo_id', $crIdFilter);
        }

        $total = 0;
        $query->orderBy('id')->chunk(100, function ($baixas) use ($dryRun, &$total) {
            foreach ($baixas as $baixa) {
                $movEntrada = $baixa->movimentacao;
                if (! $movEntrada || $movEntrada->tipo !== 'entrada' || $movEntrada->origem !== 'conta_receber') {
                    continue;
                }

                $taxaMov = BaixaTituloTaxaMovimentacaoResolver::findTaxaMovimentacao($baixa, $movEntrada);

                if (! $taxaMov) {
                    continue;
                }

                $descEstorno = 'Estorno: '.$taxaMov->descricao;
                $jaExiste = MovimentacaoFinanceira::query()
                    ->where('conta_receber_id', $movEntrada->conta_receber_id)
                    ->where('tipo', 'entrada')
                    ->where('origem', 'ajuste')
                    ->where('descricao', $descEstorno)
                    ->exists();

                if ($jaExiste) {
                    continue;
                }

                $dataMov = $baixa->data_estorno
                    ? $baixa->data_estorno->toDateString()
                    : now()->toDateString();

                $this->line(sprintf(
                    '[%s] CR#%d | Baixa#%d | Taxa R$ %s | %s',
                    $dryRun ? 'DRY-RUN' : 'CRIAR',
                    $movEntrada->conta_receber_id,
                    $baixa->id,
                    $taxaMov->valor,
                    $taxaMov->descricao
                ));

                if ($dryRun) {
                    $total++;

                    continue;
                }

                DB::transaction(function () use ($taxaMov, $movEntrada, $baixa, $descEstorno, $dataMov, &$total) {
                    MovimentacaoFinanceira::create([
                        'tenant_id' => $taxaMov->tenant_id,
                        'conta_bancaria_id' => $taxaMov->conta_bancaria_id,
                        'tipo' => 'entrada',
                        'origem' => 'ajuste',
                        'conta_receber_id' => $movEntrada->conta_receber_id,
                        'plano_conta_id' => $taxaMov->plano_conta_id,
                        'centro_custo_id' => $taxaMov->centro_custo_id,
                        'valor' => $taxaMov->valor,
                        'data_movimentacao' => $dataMov,
                        'descricao' => $descEstorno,
                        'observacoes' => 'Complemento automático: estorno da taxa vinculada à baixa #'.$baixa->id.'.',
                        'conciliado' => false,
                        'created_by' => null,
                    ]);
                    $total++;
                });
            }
        });

        if ($dryRun) {
            $this->info("Dry-run: {$total} movimentação(ões) seriam criadas.");
        } else {
            $this->info("Concluído: {$total} movimentação(ões) criada(s).");
        }

        return 0;
    }
}
