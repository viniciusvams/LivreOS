<?php

declare(strict_types=1);


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

namespace Plugins\ImportarSistemaExternoMos\Services;

use App\Models\OrdemServico;

/**
 * Resolve o id da OS no LivreOS a partir do id numérico da OS no sistema de origem (export M-OS).
 *
 * Em importação multi-parte o mapa $osIdMap da execução corrente pode estar vazio;
 * além disso, OS importada antes (ou fundida por cliente+marcador) pode ter id diferente
 * do número original — nesse caso o marcador [MOS_OS_ID:X] fica em observacoes_internas/cliente.
 */
final class MosOrdemServicoIdResolver
{
    /**
     * @param  array<int, int>  $osIdMap  id origem => ordem_servicos.id (fase SQL corrente)
     */
    public static function resolve(int $sourceOsId, array $osIdMap = []): ?int
    {
        if ($sourceOsId < 1) {
            return null;
        }

        if (isset($osIdMap[$sourceOsId])) {
            return (int) $osIdMap[$sourceOsId];
        }

        if (OrdemServico::query()->whereKey($sourceOsId)->exists()) {
            return $sourceOsId;
        }

        $marker = '[MOS_OS_ID:' . $sourceOsId . ']';
        $id = OrdemServico::query()
            ->where(function ($q) use ($marker) {
                $q->where('observacoes_internas', 'like', '%' . $marker . '%')
                    ->orWhere('observacoes_cliente', 'like', '%' . $marker . '%');
            })
            ->orderBy('id')
            ->value('id');

        return $id !== null ? (int) $id : null;
    }
}
