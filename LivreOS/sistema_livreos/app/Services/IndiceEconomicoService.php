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

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

/**
 * Serviço para obter variações de índices econômicos via APIs gratuitas (BCB).
 * Usado pelo reajuste automático de pagamentos recorrentes.
 */
class IndiceEconomicoService
{
    /** Códigos das séries no SGS do Banco Central (variação mensal %). */
    protected const BCB_SERIES = [
        'IPCA' => 433,   // Índice Nacional de Preços ao Consumidor Amplo
        'INPC' => 188,   // Índice Nacional de Preços ao Consumidor
        'IGPM' => 189,   // IGP-M (Índice Geral de Preços - Mercado) - FGV, divulgado pelo BCB
    ];

    protected const CACHE_PREFIX = 'indice_economico_';
    protected const CACHE_TTL_MINUTES = 60 * 24; // 24 horas

    /**
     * Retorna a variação acumulada (%) do índice nos últimos N meses.
     * Fórmula: (1 + v1/100) * (1 + v2/100) * ... * (1 + vN/100) - 1, em percentual.
     *
     * @param  string  $codigo  Código do índice: IPCA, INPC, IGPM (IPC_FIPE não disponível via BCB)
     * @param  int  $meses  Número de meses para acumular
     * @return float|null  Variação acumulada em % ou null se indisponível
     */
    public function variacaoAcumuladaMeses(string $codigo, int $meses): ?float
    {
        $codigo = strtoupper(trim((string) $codigo));
        if ($codigo === '') {
            Log::warning('IndiceEconomicoService: código do índice vazio.');
            return null;
        }

        if ($codigo === 'IPC_FIPE') {
            return $this->variacaoAcumuladaIPCFipe($meses);
        }

        if (!isset(self::BCB_SERIES[$codigo])) {
            Log::warning("IndiceEconomicoService: índice não mapeado: {$codigo}");
            return null;
        }

        $cacheKey = self::CACHE_PREFIX . "{$codigo}_{$meses}";
        $cached = Cache::get($cacheKey);
        if ($cached !== null && $cached !== false) {
            return (float) $cached;
        }
        $result = $this->buscarVariacaoAcumuladaBCB(self::BCB_SERIES[$codigo], $meses);
        if ($result !== null) {
            Cache::put($cacheKey, $result, self::CACHE_TTL_MINUTES * 60);
        }
        return $result;
    }

    /**
     * Busca variações mensais no BCB e calcula a acumulada.
     * Usa o endpoint "últimos N" para evitar 404 quando o mês atual ainda não tem dado publicado.
     */
    protected function buscarVariacaoAcumuladaBCB(int $serieCodigo, int $meses): ?float
    {
        $url = 'https://api.bcb.gov.br/dados/serie/bcdata.sgs.' . $serieCodigo . '/dados/ultimos/' . max(1, $meses);
        $params = ['formato' => 'json'];

        try {
            $response = Http::timeout(15)
                ->withHeaders([
                    'User-Agent' => 'ERP-Indices/1.0 (https://github.com)',
                    'Accept' => 'application/json',
                ])
                ->withOptions([
                    'verify' => $this->shouldVerifyBcbSsl(),
                ])
                ->get($url, $params);

            if (!$response->successful()) {
                Log::warning("IndiceEconomicoService: BCB retornou {$response->status()} para série {$serieCodigo}", [
                    'body' => $response->body(),
                ]);
                return null;
            }

            $dados = $response->json();
            if (!is_array($dados)) {
                Log::warning("IndiceEconomicoService: BCB retornou resposta inválida para série {$serieCodigo}");
                return null;
            }

            $total = count($dados);
            if ($total < 1) {
                Log::warning("IndiceEconomicoService: BCB retornou nenhum dado para série {$serieCodigo}");
                return null;
            }

            // Usa os últimos N disponíveis (pode ser menos que $meses se a série for recente)
            $n = min($total, $meses);
            $ultimos = array_slice($dados, -$n);
            $acumulado = 1.0;
            foreach ($ultimos as $item) {
                $valor = (float) (is_array($item) ? ($item['valor'] ?? 0) : 0);
                $acumulado *= (1 + $valor / 100);
            }
            return round((($acumulado - 1) * 100), 4);
        } catch (\Illuminate\Http\Client\ConnectionException $e) {
            Log::warning("IndiceEconomicoService: falha de conexão ao BCB (série {$serieCodigo}): " . $e->getMessage());
            return null;
        } catch (\Throwable $e) {
            Log::warning("IndiceEconomicoService: erro ao consultar BCB: " . $e->getMessage());
            return null;
        }
    }

    /**
     * IPC-FIPE: não há API pública gratuita estável. Retorna null (módulo/plugin pode implementar).
     */
    protected function variacaoAcumuladaIPCFipe(int $meses): ?float
    {
        return null;
    }

    /**
     * Verificação SSL para requisições ao BCB. Lê env primeiro para ambiente local sem config:cache.
     */
    protected function shouldVerifyBcbSsl(): bool
    {
        $env = env('BCB_VERIFY_SSL');
        if ($env !== null && $env !== '') {
            return filter_var($env, FILTER_VALIDATE_BOOLEAN);
        }
        return config('app.verify_bcb_ssl', true);
    }

    /**
     * Índices suportados para reajuste automático (com fonte disponível).
     */
    public static function indicesDisponiveis(): array
    {
        return [
            'IPCA' => 'IPCA (BCB)',
            'INPC' => 'INPC (BCB)',
            'IGPM' => 'IGP-M (BCB)',
            'IPC_FIPE' => 'IPC-FIPE (não disponível via API no momento)',
        ];
    }
}
