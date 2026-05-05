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

namespace Atendimento\Services;

use Atendimento\Models\RotaDiaDistanceCache;
use App\Models\Configuracao;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Calcula distância e tempo de deslocamento entre endereços (Google Distance Matrix API).
 * Usa configuração do plugin (ativar + chave) e cache por segmento (origem → destino);
 * só chama a API para trechos que ainda não estão em cache.
 */
class RotaDiaDistanceService
{
    protected ?string $apiKey = null;
    protected bool $enabled = false;

    public function __construct()
    {
        $this->enabled = (bool) (Configuracao::getValue('plugin_atendimento_maps_enabled', '0') === '1');
        $this->apiKey = Configuracao::getValue('plugin_atendimento_google_maps_api_key', '') ?: null;
        if (! $this->apiKey) {
            $this->apiKey = config('services.google_maps.api_key') ?: env('GOOGLE_MAPS_API_KEY');
        }
    }

    /**
     * Retorna os segmentos entre endereços consecutivos. Usa cache; chama a API só para trechos não cacheados.
     *
     * @param array<int, string> $enderecos Endereços em ordem (endereco_para_mapa)
     * @return array<int, array{distance: string, duration: string}>
     */
    public function segmentosEntreEnderecos(array $enderecos): array
    {
        $enderecos = array_values(array_filter(array_map('trim', $enderecos)));
        if (count($enderecos) < 2 || ! $this->enabled || ! $this->apiKey) {
            return [];
        }

        $segmentos = [];
        $missing = []; // [ index => [origem, destino] ]

        for ($i = 0; $i < count($enderecos) - 1; $i++) {
            $origem = $enderecos[$i];
            $destino = $enderecos[$i + 1];
            $cached = RotaDiaDistanceCache::getSegment($origem, $destino);
            if ($cached !== null) {
                $segmentos[$i] = $cached;
            } else {
                $segmentos[$i] = null;
                $missing[$i] = [$origem, $destino];
            }
        }

        if (empty($missing)) {
            return array_values($segmentos);
        }

        $fetched = $this->fetchSegmentosFromApi($missing);
        foreach ($fetched as $index => $seg) {
            $segmentos[$index] = $seg;
            [$origem, $destino] = $missing[$index];
            RotaDiaDistanceCache::putSegment($origem, $destino, $seg['distance'], $seg['duration']);
        }

        return array_values($segmentos);
    }

    /**
     * Chama a Distance Matrix API para os pares (origem, destino) indicados em $missing.
     * $missing = [ index => [origem, destino], ... ]
     *
     * @param array<int, array{0: string, 1: string}> $missing
     * @return array<int, array{distance: string, duration: string}>
     */
    protected function fetchSegmentosFromApi(array $missing): array
    {
        if (empty($missing)) {
            return [];
        }

        $origins = array_column($missing, 0);
        $destinations = array_column($missing, 1);
        $indices = array_keys($missing);

        $url = 'https://maps.googleapis.com/maps/api/distancematrix/json';
        $params = [
            // Deixe o Http client cuidar da codificação; use endereços brutos separados por pipe.
            'origins' => implode('|', $origins),
            'destinations' => implode('|', $destinations),
            'key' => $this->apiKey,
            'units' => 'metric',
            'language' => 'pt-BR',
        ];

        try {
            $response = Http::timeout(15)->get($url, $params);
            $data = $response->json();

            if (($data['status'] ?? '') !== 'OK' || empty($data['rows'] ?? [])) {
                Log::debug('RotaDiaDistanceService: status não OK ou sem rows', ['response' => $data] ?? []);
                return $this->emptySegmentosFor($missing);
            }

            $result = [];
            foreach ($indices as $pos => $index) {
                $row = $data['rows'][$pos] ?? null;
                $element = $row['elements'][$pos] ?? null;
                if (! $element || ($element['status'] ?? '') !== 'OK') {
                    $result[$index] = ['distance' => '—', 'duration' => '—'];
                    continue;
                }
                $result[$index] = [
                    'distance' => $element['distance']['text'] ?? '—',
                    'duration' => $element['duration']['text'] ?? '—',
                ];
            }
            return $result;
        } catch (\Throwable $e) {
            Log::warning('RotaDiaDistanceService: falha ao chamar API', ['message' => $e->getMessage()]);
            return $this->emptySegmentosFor($missing);
        }
    }

    /**
     * @param array<int, array{0: string, 1: string}> $missing
     * @return array<int, array{distance: string, duration: string}>
     */
    protected function emptySegmentosFor(array $missing): array
    {
        $out = [];
        foreach (array_keys($missing) as $index) {
            $out[$index] = ['distance' => '—', 'duration' => '—'];
        }
        return $out;
    }
}
