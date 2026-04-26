<?php

/**
 * Componente da aplicação LivreOS
 *
 * @copyright 2024-2026 LivreOS
 * @license   https://www.gnu.org/licenses/agpl-3.0.txt AGPL-3.0
 */

namespace App\Services\Financeiro;

use App\Models\BaixaTitulo;
use App\Models\ContaReceber;
use App\Models\MovimentacaoFinanceira;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

/**
 * Ajusta in-place data da baixa, data_movimentacao (entrada + taxa), data_conciliacao quando aplicável,
 * data_vencimento do título (alinha ao novo movimento) e data_recebimento se quitado, sem estorno.
 */
class CorrigirDatasBaixaContaReceberService
{
    public function executar(
        ContaReceber $contaReceber,
        BaixaTitulo $baixa,
        User $user,
        string $dataMovimentacaoYmd,
        ?string $dataConciliacaoYmd,
    ): void {
        $this->assertPodeExecutar($contaReceber, $baixa, $user);

        $baixa->loadMissing('movimentacao');
        $movEntrada = $baixa->movimentacao;
        if (! $movEntrada) {
            throw new InvalidArgumentException('Movimentação financeira desta baixa não foi encontrada.');
        }

        if ($movEntrada->data_cancelamento !== null) {
            throw new InvalidArgumentException('Movimentação cancelada não pode ter datas corrigidas por esta ação.');
        }

        if ($movEntrada->tipo !== 'entrada' || $movEntrada->origem !== 'conta_receber') {
            throw new InvalidArgumentException('Somente lançamentos de recebimento (entrada / conta a receber) podem ser corrigidos por esta baixa.');
        }

        if ((int) $movEntrada->conta_receber_id !== (int) $contaReceber->id) {
            throw new InvalidArgumentException('A movimentação não pertence a esta conta a receber.');
        }

        // Resolver taxa enquanto data_baixa / data_movimentacao ainda refletem o registro original
        $taxaMov = BaixaTituloTaxaMovimentacaoResolver::findTaxaMovimentacao($baixa, $movEntrada);
        if ($taxaMov && $taxaMov->data_cancelamento !== null) {
            throw new InvalidArgumentException('Movimentação de taxa vinculada está cancelada.');
        }

        $dataMov = Carbon::parse($dataMovimentacaoYmd)->startOfDay();
        $dataConc = null;
        if ($movEntrada->conciliado) {
            $dataConc = $dataConciliacaoYmd
                ? Carbon::parse($dataConciliacaoYmd)->startOfDay()
                : $dataMov->copy();
        }

        $linhaAuditoria = 'Datas corrigidas em '.$dataMov->format('d/m/Y').' ('.now()->format('d/m/Y H:i').').';

        DB::transaction(function () use (
            $contaReceber,
            $baixa,
            $movEntrada,
            $taxaMov,
            $user,
            $dataMov,
            $dataConc,
            $linhaAuditoria,
        ) {
            $baixa->data_baixa = $dataMov->toDateString();
            $baixa->updated_by = $user->id;
            $baixa->save();

            $this->atualizarMovimentacao($movEntrada, $dataMov, $dataConc, $user->id, $linhaAuditoria);

            if ($taxaMov) {
                $this->atualizarMovimentacao($taxaMov, $dataMov, $dataConc, $user->id, $linhaAuditoria);
            }

            $contaReceber->refresh();
            $contaReceber->data_vencimento = $dataMov->toDateString();
            $contaReceber->updated_by = $user->id;

            if ($contaReceber->status === 'pago') {
                $maxData = BaixaTitulo::query()
                    ->where('tipo_titulo', 'conta_receber')
                    ->where('titulo_id', $contaReceber->id)
                    ->where('estornado', false)
                    ->max('data_baixa');
                if ($maxData) {
                    $contaReceber->data_recebimento = Carbon::parse($maxData)->toDateString();
                }
            }

            $contaReceber->save();
        });
    }

    private function assertPodeExecutar(ContaReceber $contaReceber, BaixaTitulo $baixa, User $user): void
    {
        if ($user->tenant_id !== null && $contaReceber->tenant_id !== null
            && (int) $user->tenant_id !== (int) $contaReceber->tenant_id) {
            throw new InvalidArgumentException('Conta a receber não pertence ao seu tenant.');
        }

        if ((int) $baixa->titulo_id !== (int) $contaReceber->id || $baixa->tipo_titulo !== 'conta_receber') {
            throw new InvalidArgumentException('Baixa não pertence a esta conta a receber.');
        }

        if ($baixa->estornado) {
            throw new InvalidArgumentException('Não é possível corrigir datas de baixa estornada.');
        }
    }

    private function atualizarMovimentacao(
        MovimentacaoFinanceira $mov,
        Carbon $dataMov,
        ?Carbon $dataConc,
        int $userId,
        string $linhaAuditoria,
    ): void {
        $attrs = [
            'data_movimentacao' => $dataMov->toDateString(),
            'updated_by' => $userId,
        ];

        if ($mov->conciliado && $dataConc !== null) {
            $attrs['data_conciliacao'] = $dataConc->toDateString();
        }

        $obs = trim((string) ($mov->observacoes ?? ''));
        $attrs['observacoes'] = $obs === '' ? $linhaAuditoria : $obs.' | '.$linhaAuditoria;

        $mov->update($attrs);
    }
}
