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
use Illuminate\Support\Facades\Session;

/**
 * Estado da importação multi-parte M-OS (v2) entre pedidos HTTP.
 *
 * Persiste também em cache (além da sessão) para sobreviver a perda de cookie
 * em pedidos XHR ou regeneração de sessão. part_total efetivo usa o máximo entre
 * meta JSON, nome do ficheiro (partXXofYY) e valor já guardado — evita limpar
 * estado cedo quando export_meta.json vem com part_total=1 por engano.
 */
class MosMultipartSessionService
{
    public const SESSION_KEY = 'mos_import_multipart_v2';

    protected function cacheKey(string $exportId): string
    {
        $uid = Auth::id();
        if ($uid === null) {
            return '';
        }

        return 'mos_import_v2:' . $uid . ':' . hash('sha256', $exportId);
    }

    /**
     * Total de partes inferido do nome do ficheiro (ex.: ..._part019of019.zip).
     */
    public static function inferPartTotalFromFilename(?string $filename): ?int
    {
        if ($filename === null || $filename === '') {
            return null;
        }
        if (preg_match('/part\s*0*(\d+)\s*of\s*0*(\d+)/i', $filename, $m)) {
            $total = (int) $m[2];

            return $total > 0 ? $total : null;
        }

        return null;
    }

    /**
     * @return array{export_id:string, part_total:int, last_completed_part:int}|null
     */
    public function getState(): ?array
    {
        $s = Session::get(self::SESSION_KEY);
        if (!is_array($s) || empty($s['export_id'])) {
            return null;
        }

        return [
            'export_id' => (string) $s['export_id'],
            'part_total' => (int) ($s['part_total'] ?? 0),
            'last_completed_part' => (int) ($s['last_completed_part'] ?? 0),
        ];
    }

    /**
     * Recupera progresso em cache quando a sessão Laravel ficou vazia (ex.: novo pedido XHR).
     */
    public function hydrateFromCacheIfEmpty(string $exportId): void
    {
        if ($this->getState() !== null) {
            return;
        }
        $key = $this->cacheKey($exportId);
        if ($key === '') {
            return;
        }
        $cached = Cache::get($key);
        if (!is_array($cached) || empty($cached['export_id']) || (string) $cached['export_id'] !== $exportId) {
            return;
        }
        Session::put(self::SESSION_KEY, [
            'export_id' => (string) $cached['export_id'],
            'part_total' => (int) ($cached['part_total'] ?? 0),
            'last_completed_part' => (int) ($cached['last_completed_part'] ?? 0),
        ]);
    }

    public function clear(): void
    {
        $s = Session::get(self::SESSION_KEY);
        $exportId = is_array($s) && !empty($s['export_id']) ? (string) $s['export_id'] : '';
        Session::forget(self::SESSION_KEY);
        if ($exportId !== '') {
            $key = $this->cacheKey($exportId);
            if ($key !== '') {
                Cache::forget($key);
            }
            app(MosImportMultipartAccumulator::class)->forget($exportId);
        }
    }

    /**
     * Valida ordem e coerência; não altera sessão (use markPartCompleted após sucesso).
     *
     * @throws \RuntimeException
     */
    public function assertCanImportPart(string $exportId, int $partIndex, int $partTotal): void
    {
        if ($partIndex < 1 || $partTotal < 1) {
            throw new \RuntimeException('export_meta.json inválido: part_index ou part_total inconsistentes.');
        }

        $this->hydrateFromCacheIfEmpty($exportId);

        $state = $this->getState();

        if ($state === null) {
            if ($partIndex !== 1) {
                throw new \RuntimeException(
                    'É necessário importar primeiro a parte 1 desta exportação (part_index=1), ou a sessão expirou — importe de novo a parte 1 (o progresso pode ser recuperado do servidor se usar a mesma conta). ' .
                    'Se já concluiu outra exportação, envie a parte 1 do novo conjunto de ZIPs.'
                );
            }

            return;
        }

        if ($state['export_id'] !== $exportId) {
            if ($partIndex === 1) {
                // Novo export: utilizador recomeça — limpar estado antigo implicitamente no mark após sucesso
                return;
            }

            throw new \RuntimeException(
                'export_id diferente da importação em curso. Esperado sessão para export_id "' .
                $state['export_id'] . '", recebido "' . $exportId . '". Envie a parte seguinte do mesmo conjunto ou comece pela parte 1 de um novo export.'
            );
        }

        $mergedTotal = max((int) $state['part_total'], $partTotal);
        if ($partIndex > $mergedTotal) {
            throw new \RuntimeException(
                'part_index (' . $partIndex . ') é maior que o total de partes conhecido (' . $mergedTotal . '). Utilize ZIPs da mesma corrida de exportação.'
            );
        }

        $next = $state['last_completed_part'] + 1;
        if ($partIndex < $next) {
            throw new \RuntimeException(
                'A parte ' . $partIndex . ' já foi importada. Próxima parte esperada: ' . $next . '/' . $mergedTotal . '.'
            );
        }
        if ($partIndex > $next) {
            throw new \RuntimeException(
                'Parte fora de sequência: enviou parte ' . $partIndex . ' mas a próxima esperada é ' . $next . '/' . $mergedTotal . '. ' .
                'Se enviou vários ZIPs de uma vez, ordene pelo número da parte no nome do ficheiro (part01of19, part02of19, …).'
            );
        }
    }

    /**
     * Após SQL + manifest da parte concluírem com sucesso.
     */
    public function markPartCompleted(string $exportId, int $partIndex, int $partTotal, ?string $originalFilename = null): void
    {
        $fromName = self::inferPartTotalFromFilename($originalFilename);
        $state = $this->getState();
        $prevTotal = ($state !== null && $state['export_id'] === $exportId) ? (int) $state['part_total'] : 0;
        $effectiveTotal = max($partTotal, $fromName ?? 0, $prevTotal);

        MosSqliteBusyRetry::run(function () use ($exportId, $effectiveTotal, $partIndex): void {
            Session::put(self::SESSION_KEY, [
                'export_id' => $exportId,
                'part_total' => $effectiveTotal,
                'last_completed_part' => $partIndex,
            ]);

            $key = $this->cacheKey($exportId);
            if ($key !== '') {
                Cache::put($key, Session::get(self::SESSION_KEY), now()->addDays(7));
            }
        });

        if ($partIndex >= $effectiveTotal) {
            $this->clear();
        }
    }

    /**
     * Recuperação manual (ex.: `php artisan tinker`): grava estado em cache; a sessão web hidrata no próximo ZIP.
     * Use o mesmo Auth::id() que o utilizador que importa no browser.
     */
    public function seedResumeState(string $exportId, int $lastCompletedPart, int $partTotal): void
    {
        if (!Auth::check()) {
            throw new \RuntimeException('É necessário um utilizador autenticado (ex.: Auth::loginUsingId(1) na tinker).');
        }
        $exportId = trim($exportId);
        if ($exportId === '' || $lastCompletedPart < 1 || $partTotal < 1 || $lastCompletedPart > $partTotal) {
            throw new \InvalidArgumentException('export_id, lastCompletedPart ou partTotal inválidos.');
        }
        $key = $this->cacheKey($exportId);
        if ($key === '') {
            throw new \RuntimeException('Não foi possível gerar chave de cache.');
        }
        $data = [
            'export_id' => $exportId,
            'part_total' => $partTotal,
            'last_completed_part' => $lastCompletedPart,
        ];
        MosSqliteBusyRetry::run(static function () use ($key, $data): void {
            Cache::put($key, $data, now()->addDays(7));
        });
    }
}
