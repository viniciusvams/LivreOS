<?php

/**
 * Componente da aplicação LivreOS
 *
 * @author    viniciusvams
 * @copyright 2024-2026 LivreOS
 * @license   https://www.gnu.org/licenses/agpl-3.0.txt AGPL-3.0
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 */

namespace App\Services;

use App\Models\ContaReceber;
use App\Models\OrdemServico;
use App\Models\Tag;

class OrdemServicoPagamentoTagSyncService
{
    /** Status considerados em aberto (alinhado ao financeiro / ContaReceberController). */
    public const STATUS_TITULO_ABERTO = ['aberto', 'parcial', 'vencido'];

    /**
     * Se a OS está encerrada, ainda tem a tag "Pagamento Pendente" e não há mais título a receber
     * em aberto vinculado, troca para a tag "Pago".
     */
    public static function syncSePendenteSemTituloAberto(?int $ordemServicoId): void
    {
        if (! $ordemServicoId) {
            return;
        }

        $os = OrdemServico::query()->find($ordemServicoId);
        if (! $os || $os->status !== 'Encerrada') {
            return;
        }

        $tagPendente = Tag::query()
            ->where('nome', 'Pagamento Pendente')
            ->where('tipo', 'padrao')
            ->first();
        $tagPago = Tag::query()
            ->where('nome', 'Pago')
            ->where('tipo', 'padrao')
            ->first();

        if (! $tagPendente || ! $tagPago) {
            return;
        }

        if (! $os->tags()->where('tags.id', $tagPendente->id)->exists()) {
            return;
        }

        $abertas = ContaReceber::query()
            ->where('ordem_servico_id', $os->id)
            ->whereIn('status', self::STATUS_TITULO_ABERTO)
            ->count();

        if ($abertas > 0) {
            return;
        }

        $os->tags()->detach($tagPendente->id);
        if (! $os->tags()->where('tags.id', $tagPago->id)->exists()) {
            $os->tags()->attach($tagPago->id);
        }
    }

    /**
     * Varre todas as OS com tag "Pagamento Pendente" e aplica a mesma regra (uso em comando / correção em lote).
     *
     * @return array{atualizadas: list<array{os_id: int, codigo: string|null}>, mantidas: list<array{os_id: int, codigo: string|null, contas_abertas: int}>}
     */
    public static function sincronizarTodasComTagPendente(): array
    {
        $tagPendente = Tag::query()
            ->where('nome', 'Pagamento Pendente')
            ->where('tipo', 'padrao')
            ->first();

        if (! $tagPendente) {
            return ['atualizadas' => [], 'mantidas' => []];
        }

        $atualizadas = [];
        $mantidas = [];

        $osIds = OrdemServico::query()
            ->whereHas('tags', fn ($q) => $q->where('tags.id', $tagPendente->id))
            ->pluck('id');

        foreach ($osIds as $id) {
            $abertas = ContaReceber::query()
                ->where('ordem_servico_id', $id)
                ->whereIn('status', self::STATUS_TITULO_ABERTO)
                ->count();

            $os = OrdemServico::query()->find($id);
            $codigo = $os?->codigo_interno;

            if ($abertas > 0) {
                $mantidas[] = [
                    'os_id' => (int) $id,
                    'codigo' => $codigo,
                    'contas_abertas' => $abertas,
                ];
                continue;
            }

            self::syncSePendenteSemTituloAberto((int) $id);
            $atualizadas[] = ['os_id' => (int) $id, 'codigo' => $codigo];
        }

        return ['atualizadas' => $atualizadas, 'mantidas' => $mantidas];
    }
}
