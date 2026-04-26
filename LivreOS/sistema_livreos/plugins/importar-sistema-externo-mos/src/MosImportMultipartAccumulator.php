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

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;

/**
 * Guarda entre partes ZIP (export M-OS v2) as linhas de itens de venda e nomes de produtos
 * já lidas do SQL, para que vendas e itens possam vir em ficheiros diferentes.
 */
class MosImportMultipartAccumulator
{
    /**
     * @return array{produtos_venda: list<array<string, mixed>>, produto_nome_por_id: array<int, string>}
     */
    public function load(string $exportId): array
    {
        $exportId = trim($exportId);
        if ($exportId === '') {
            return $this->emptyState();
        }
        $key = $this->cacheKey($exportId);
        if ($key === '') {
            return $this->emptyState();
        }
        $raw = Cache::get($key);
        if (! is_string($raw) || $raw === '') {
            return $this->emptyState();
        }
        $data = @unserialize($raw, ['allowed_classes' => false]);
        if (! is_array($data)) {
            return $this->emptyState();
        }
        $pv = $data['produtos_venda'] ?? [];
        $nomes = $data['produto_nome_por_id'] ?? [];
        if (! is_array($pv)) {
            $pv = [];
        }
        if (! is_array($nomes)) {
            $nomes = [];
        }

        /** @var list<array<string, mixed>> $pv */
        /** @var array<int, string> $nomes */
        return [
            'produtos_venda' => $pv,
            'produto_nome_por_id' => $nomes,
        ];
    }

    /**
     * @param  array{produtos_venda: list<array<string, mixed>>, produto_nome_por_id: array<int, string>}  $state
     */
    public function save(string $exportId, array $state): void
    {
        $exportId = trim($exportId);
        if ($exportId === '') {
            return;
        }
        $key = $this->cacheKey($exportId);
        if ($key === '') {
            return;
        }
        $payload = [
            'produtos_venda' => $state['produtos_venda'] ?? [],
            'produto_nome_por_id' => $state['produto_nome_por_id'] ?? [],
        ];
        MosSqliteBusyRetry::run(static function () use ($key, $payload): void {
            Cache::put($key, serialize($payload), now()->addDays(7));
        });
    }

    public function forget(string $exportId): void
    {
        $exportId = trim($exportId);
        if ($exportId === '') {
            return;
        }
        $key = $this->cacheKey($exportId);
        if ($key !== '') {
            Cache::forget($key);
        }
    }

    /**
     * @return array{produtos_venda: list<array<string, mixed>>, produto_nome_por_id: array<int, string>}
     */
    protected function emptyState(): array
    {
        return [
            'produtos_venda' => [],
            'produto_nome_por_id' => [],
        ];
    }

    protected function cacheKey(string $exportId): string
    {
        $uid = Auth::id();
        if ($uid === null) {
            return '';
        }

        return 'mos_imp_accum:' . (int) $uid . ':' . hash('sha256', $exportId);
    }
}
