<?php

/**
 * Componente da aplicação LivreOS
 *
 * @copyright 2024-2026 LivreOS
 * @license   https://www.gnu.org/licenses/agpl-3.0.txt AGPL-3.0
 */

namespace App\Services\Financeiro;

use App\Models\BaixaTitulo;
use App\Models\ContaPagar;
use App\Models\MovimentacaoFinanceira;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

/**
 * Ajusta in-place data da baixa, data_movimentacao da saída, data_conciliacao quando aplicável,
 * data_vencimento do título (alinha ao novo movimento) e data_pagamento se quitado, sem estorno.
 */
class CorrigirDatasBaixaContaPagarService
{
    public function executar(
        ContaPagar $contaPagar,
        BaixaTitulo $baixa,
        User $user,
        string $dataMovimentacaoYmd,
        ?string $dataConciliacaoYmd,
    ): void {
        $this->assertPodeExecutar($contaPagar, $baixa, $user);

        $baixa->loadMissing('movimentacao');
        $movSaida = $baixa->movimentacao;
        if (! $movSaida) {
            throw new InvalidArgumentException('Movimentação financeira desta baixa não foi encontrada.');
        }

        if ($movSaida->data_cancelamento !== null) {
            throw new InvalidArgumentException('Movimentação cancelada não pode ter datas corrigidas por esta ação.');
        }

        if ($movSaida->tipo !== 'saida' || $movSaida->origem !== 'conta_pagar') {
            throw new InvalidArgumentException('Somente lançamentos de pagamento (saída / conta a pagar) podem ser corrigidos por esta baixa.');
        }

        if ((int) $movSaida->conta_pagar_id !== (int) $contaPagar->id) {
            throw new InvalidArgumentException('A movimentação não pertence a esta conta a pagar.');
        }

        $dataMov = Carbon::parse($dataMovimentacaoYmd)->startOfDay();
        $dataConc = null;
        if ($movSaida->conciliado) {
            $dataConc = $dataConciliacaoYmd
                ? Carbon::parse($dataConciliacaoYmd)->startOfDay()
                : $dataMov->copy();
        }

        $linhaAuditoria = 'Datas corrigidas em '.$dataMov->format('d/m/Y').' ('.now()->format('d/m/Y H:i').').';

        DB::transaction(function () use (
            $contaPagar,
            $baixa,
            $movSaida,
            $user,
            $dataMov,
            $dataConc,
            $linhaAuditoria,
        ) {
            $baixa->data_baixa = $dataMov->toDateString();
            $baixa->updated_by = $user->id;
            $baixa->save();

            $this->atualizarMovimentacao($movSaida, $dataMov, $dataConc, $user->id, $linhaAuditoria);

            $contaPagar->refresh();
            $contaPagar->data_vencimento = $dataMov->toDateString();
            $contaPagar->updated_by = $user->id;

            if ($contaPagar->status === 'pago') {
                $maxData = BaixaTitulo::query()
                    ->where('tipo_titulo', 'conta_pagar')
                    ->where('titulo_id', $contaPagar->id)
                    ->where('estornado', false)
                    ->max('data_baixa');
                if ($maxData) {
                    $contaPagar->data_pagamento = Carbon::parse($maxData)->toDateString();
                }
            }

            $contaPagar->save();
        });
    }

    private function assertPodeExecutar(ContaPagar $contaPagar, BaixaTitulo $baixa, User $user): void
    {
        if ($user->tenant_id !== null && $contaPagar->tenant_id !== null
            && (int) $user->tenant_id !== (int) $contaPagar->tenant_id) {
            throw new InvalidArgumentException('Conta a pagar não pertence ao seu tenant.');
        }

        if ((int) $baixa->titulo_id !== (int) $contaPagar->id || $baixa->tipo_titulo !== 'conta_pagar') {
            throw new InvalidArgumentException('Baixa não pertence a esta conta a pagar.');
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
