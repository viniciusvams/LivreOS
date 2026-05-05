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

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Geocodificação via Nominatim (OpenStreetMap) no servidor.
 * Respeita limite de 1 requisição por segundo. Usa busca estruturada (city, state) quando
 * possível para evitar resultado em município errado (ex.: mesma rua em outra cidade).
 */
class NominatimGeocodeService
{
    protected static ?float $lastRequestTime = null;

    protected static function throttle(): void
    {
        $now = microtime(true);
        if (self::$lastRequestTime !== null) {
            $elapsed = $now - self::$lastRequestTime;
            if ($elapsed < 1.0) {
                usleep((int) ((1.0 - $elapsed) * 1000000));
            }
        }
        self::$lastRequestTime = microtime(true);
    }

    /**
     * Limpa e formata o endereço para o Nominatim (formato "rua, número, bairro, cidade, estado, Brasil").
     */
    protected static function buildQuery(string $address): string
    {
        $address = trim(preg_replace('/\s+/', ' ', $address));
        if ($address === '') {
            return '';
        }
        // Remove CEP (ex.: "CEP: 01426-001" ou "CEP 01426-001")
        $address = preg_replace('/\s*CEP:?\s*\d{5}-?\d{3}\s*/ui', ' ', $address);
        $address = trim(preg_replace('/\s+/', ' ', $address));
        // Normaliza "Cidade/UF" para "Cidade, UF"
        $address = preg_replace('/\s*\/\s*/u', ', ', $address);
        // Troca " - " por ", " para formato mais compatível com Nominatim
        $address = preg_replace('/\s*-\s*/u', ', ', $address);
        $address = trim(preg_replace('/\s*,\s*/u', ', ', $address));
        if ($address === '') {
            return '';
        }
        if (stripos($address, 'Brasil') !== false || stripos($address, 'Brazil') !== false) {
            return $address;
        }

        return $address . ', Brasil';
    }

    /**
     * Formato usado na interface do Nominatim (https://nominatim.openstreetmap.org/ui/search.html).
     * Mantém " - " e "Cidade/UF" — só remove CEP e adiciona ", Brasil".
     * Ex.: "Av. Brigadeiro Faria Lima, 370 - Centro - São Paulo/SP" → "Av. Brigadeiro Faria Lima, 370 - Centro - São Paulo/SP, Brasil"
     */
    protected static function buildQueryNominatimUiStyle(string $address): string
    {
        $address = trim(preg_replace('/\s+/', ' ', $address));
        if ($address === '') {
            return '';
        }
        $address = preg_replace('/\s*CEP:?\s*\d{5}-?\d{3}\s*/ui', ' ', $address);
        $address = trim(preg_replace('/\s+/', ' ', $address));
        if ($address === '') {
            return '';
        }
        if (stripos($address, 'Brasil') !== false || stripos($address, 'Brazil') !== false) {
            return $address;
        }

        return $address . ', Brasil';
    }

    /**
     * Extrai cidade e UF do endereço no formato "... Cidade/UF" ou "... - Cidade/UF".
     */
    protected static function parseCityStateFromOriginal(string $address): ?array
    {
        $address = preg_replace('/\s*CEP:?\s*\d{5}-?\d{3}\s*/ui', ' ', $address);
        $address = trim(preg_replace('/\s+/', ' ', $address));
        if (preg_match('/(?:[\s\-])([^\-\/]+)\/([A-Za-z]{2})\s*$/u', $address, $m)) {
            $city = trim($m[1]);
            $state = strtoupper(trim($m[2]));
            if (strlen($state) === 2 && $city !== '') {
                return ['city' => $city, 'state' => $state];
            }
        }

        return null;
    }

    /**
     * Extrai cidade e UF do endereço no formato "... , Cidade, UF, Brasil".
     * Retorna ['street' => string, 'city' => string, 'state' => string] ou null se não conseguir.
     */
    protected static function parseBrazilianAddress(string $query): ?array
    {
        $parts = array_map('trim', explode(',', $query));
        $n = count($parts);
        if ($n < 4) {
            return null;
        }
        $last = $parts[$n - 1] ?? '';
        if (stripos($last, 'Brasil') === false) {
            return null;
        }
        $state = $parts[$n - 2] ?? '';
        $city = $parts[$n - 3] ?? '';
        // UF deve ser 2 caracteres (sigla)
        if (strlen($state) !== 2 || ! ctype_alpha($state)) {
            return null;
        }
        $street = implode(', ', array_slice($parts, 0, $n - 3));
        if ($street === '' || $city === '') {
            return null;
        }

        return [
            'street' => $street,
            'city' => $city,
            'state' => strtoupper($state),
        ];
    }

    /**
     * Normaliza nome de cidade para comparação (minúsculo, sem acentos).
     */
    protected static function normalizeCityName(string $name): string
    {
        $name = mb_strtolower(trim($name), 'UTF-8');
        $map = ['á' => 'a', 'à' => 'a', 'ã' => 'a', 'â' => 'a', 'é' => 'e', 'ê' => 'e', 'í' => 'i', 'ó' => 'o', 'ô' => 'o', 'õ' => 'o', 'ú' => 'u', 'ç' => 'c'];
        return strtr($name, $map);
    }

    /**
     * Verifica se o resultado do Nominatim pertence à cidade/estado informados.
     * Usa apenas os campos de endereço (city, town, municipality) para evitar falso positivo:
     * em SP, "Região Metropolitana de São Paulo" aparece no display_name de cidades vizinhas.
     */
    protected static function resultMatchesCity(array $item, string $requestedCity, string $requestedState): bool
    {
        $cityNorm = self::normalizeCityName($requestedCity);
        $stateNorm = strtoupper(trim($requestedState));

        $addr = $item['address'] ?? null;
        if (is_array($addr)) {
            $fields = ['city', 'town', 'municipality'];
            foreach ($fields as $field) {
                $value = $addr[$field] ?? '';
                if ($value === '') {
                    continue;
                }
                $valueNorm = self::normalizeCityName($value);
                if ($valueNorm === $cityNorm) {
                    $stateInAddr = $addr['state'] ?? '';
                    if ($stateInAddr === '' || strtoupper($stateInAddr) === $stateNorm) {
                        return true;
                    }
                }
            }
        }

        $displayName = isset($item['display_name']) ? $item['display_name'] : '';
        if ($displayName === '') {
            return false;
        }
        $displayNorm = self::normalizeCityName($displayName);
        $stateNormLower = strtolower($stateNorm);
        if (str_contains($displayNorm, ', ' . $cityNorm . ', ') && (str_contains($displayNorm, ', ' . $stateNormLower) || str_contains($displayNorm, ', ' . $stateNorm . ','))) {
            return true;
        }
        if (preg_match('/,\s*' . preg_quote($cityNorm, '/') . '\s*,\s*' . preg_quote($stateNormLower, '/') . '[,\s]/u', $displayNorm)) {
            return true;
        }

        return false;
    }

    /**
     * Busca o centro aproximado de uma cidade/UF.
     */
    protected static function fetchCityCenter(string $city, string $state): ?array
    {
        self::throttle();

        try {
            $response = Http::timeout(15)
                ->withHeaders([
                    'User-Agent' => 'LivreOS-Atendimento-RotaDia/1.0 (rota do dia; geocodificação servidor)',
                    'Accept-Language' => 'pt-BR,pt;q=0.9',
                ])
                ->get('https://nominatim.openstreetmap.org/search', [
                    'city' => $city,
                    'state' => $state,
                    'country' => 'Brasil',
                    'format' => 'jsonv2',
                    'limit' => 1,
                    'addressdetails' => 1,
                ]);

            $data = $response->json();
            if (! is_array($data) || count($data) === 0) {
                return null;
            }

            $lat = isset($data[0]['lat']) ? (float) $data[0]['lat'] : null;
            $lon = isset($data[0]['lon']) ? (float) $data[0]['lon'] : null;

            if ($lat === null || $lon === null) {
                return null;
            }

            return ['lat' => $lat, 'lng' => $lon];
        } catch (\Throwable $e) {
            Log::debug('NominatimGeocodeService: falha city_center', ['message' => $e->getMessage()]);

            return null;
        }
    }

    /**
     * Faz requisição ao Nominatim com parâmetros estruturados e retorna vários resultados.
     * Filtra pelo município informado; se nenhum bater, retorna null.
     */
    protected static function fetchStructured(string $street, string $city, string $state): ?array
    {
        self::throttle();

        try {
            $params = [
                'street' => $street,
                'city' => $city,
                'state' => $state,
                'country' => 'Brasil',
                'countrycodes' => 'br',
                'format' => 'jsonv2',
                'limit' => 10,
                'addressdetails' => 1,
            ];

            $response = Http::timeout(15)
                ->withHeaders([
                    'User-Agent' => 'LivreOS-Atendimento-RotaDia/1.0 (rota do dia; geocodificação servidor)',
                    'Accept-Language' => 'pt-BR,pt;q=0.9',
                ])
                ->get('https://nominatim.openstreetmap.org/search', $params);

            $data = $response->json();
            if (! is_array($data) || count($data) === 0) {
                return null;
            }

            foreach ($data as $item) {
                if (self::resultMatchesCity($item, $city, $state)) {
                    $lat = isset($item['lat']) ? (float) $item['lat'] : null;
                    $lon = isset($item['lon']) ? (float) $item['lon'] : null;
                    if ($lat !== null && $lon !== null) {
                        return ['lat' => $lat, 'lng' => $lon];
                    }
                }
            }

            return null;
        } catch (\Throwable $e) {
            Log::debug('NominatimGeocodeService: falha structured', ['message' => $e->getMessage()]);

            return null;
        }
    }

    /**
     * Faz a requisição ao Nominatim (busca livre) e retorna ['lat' => float, 'lng' => float] ou null.
     * Se city e state forem informados, pede vários resultados e escolhe o que bater com o município.
     */
    protected static function fetch(string $query, ?string $filterCity = null, ?string $filterState = null): ?array
    {
        self::throttle();

        try {
            $params = [
                'q' => $query,
                'format' => 'jsonv2',
                'limit' => $filterCity ? 10 : 1,
                'countrycodes' => 'br',
            ];
            if ($filterCity) {
                $params['addressdetails'] = 1;
            }

            $response = Http::timeout(15)
                ->withHeaders([
                    'User-Agent' => 'LivreOS-Atendimento-RotaDia/1.0 (rota do dia; geocodificação servidor)',
                    'Accept-Language' => 'pt-BR,pt;q=0.9',
                ])
                ->get('https://nominatim.openstreetmap.org/search', $params);

            $data = $response->json();
            if (! is_array($data) || count($data) === 0) {
                return null;
            }

            if ($filterCity !== null && $filterState !== null) {
                foreach ($data as $item) {
                    if (self::resultMatchesCity($item, $filterCity, $filterState)) {
                        $lat = isset($item['lat']) ? (float) $item['lat'] : null;
                        $lon = isset($item['lon']) ? (float) $item['lon'] : null;
                        if ($lat !== null && $lon !== null) {
                            return ['lat' => $lat, 'lng' => $lon];
                        }
                    }
                }
                return null;
            }

            $lat = isset($data[0]['lat']) ? (float) $data[0]['lat'] : null;
            $lon = isset($data[0]['lon']) ? (float) $data[0]['lon'] : null;

            if ($lat === null || $lon === null) {
                return null;
            }

            return ['lat' => $lat, 'lng' => $lon];
        } catch (\Throwable $e) {
            Log::debug('NominatimGeocodeService: falha', ['message' => $e->getMessage()]);

            return null;
        }
    }

    /**
     * Retorna ['lat' => float, 'lng' => float] ou null se não encontrar.
     * Primeiro tenta o formato da interface do Nominatim (ex.: "Rua, 123 - Bairro - Cidade/SP, Brasil").
     */
    public static function geocode(string $address): ?array
    {
        $queryNormalized = self::buildQuery($address);
        if ($queryNormalized === '') {
            return null;
        }

        $cacheKey = 'atendimento_geocode_v4_' . md5($queryNormalized);

        $cached = Cache::get($cacheKey);
        if ($cached !== null) {
            return $cached === false ? null : $cached;
        }

        $parsedFromOriginal = self::parseCityStateFromOriginal($address);
        $queryUi = self::buildQueryNominatimUiStyle($address);

        if ($queryUi !== '' && $parsedFromOriginal !== null) {
            $result = self::fetch($queryUi, $parsedFromOriginal['city'], $parsedFromOriginal['state']);
            if ($result !== null) {
                Cache::put($cacheKey, $result, 60 * 24 * 30);
                return $result;
            }
        }

        $parsed = self::parseBrazilianAddress($queryNormalized);
        if ($parsed !== null) {
            $result = self::fetchStructured($parsed['street'], $parsed['city'], $parsed['state']);
            if ($result !== null) {
                Cache::put($cacheKey, $result, 60 * 24 * 30);
                return $result;
            }
            $result = self::fetch($queryNormalized, $parsed['city'], $parsed['state']);
            if ($result !== null) {
                Cache::put($cacheKey, $result, 60 * 24 * 30);
                return $result;
            }
        } else {
            $result = self::fetch($queryNormalized);
        }

        if ($result !== null) {
            Cache::put($cacheKey, $result, 60 * 24 * 30);
            return $result;
        }

        $parts = array_map('trim', explode(',', $queryNormalized));
        if (count($parts) >= 4) {
            $withoutBairro = $parts[0] . ', ' . $parts[1] . ', ' . implode(', ', array_slice($parts, 3));
            if ($withoutBairro !== $queryNormalized) {
                $result = $parsed !== null
                    ? self::fetch($withoutBairro, $parsed['city'], $parsed['state'])
                    : self::fetch($withoutBairro);
                if ($result !== null) {
                    Cache::put($cacheKey, $result, 60 * 24 * 30);
                    return $result;
                }
            }
        }

        // Último recurso: se sabemos a cidade/UF, usar o centro aproximado dela
        if ($parsedFromOriginal !== null) {
            $cityCenter = self::fetchCityCenter($parsedFromOriginal['city'], $parsedFromOriginal['state']);
            if ($cityCenter !== null) {
                Cache::put($cacheKey, $cityCenter, 60 * 24 * 30);
                return $cityCenter;
            }
        } elseif ($parsed !== null) {
            $cityCenter = self::fetchCityCenter($parsed['city'], $parsed['state']);
            if ($cityCenter !== null) {
                Cache::put($cacheKey, $cityCenter, 60 * 24 * 30);
                return $cityCenter;
            }
        }

        Cache::put($cacheKey, false, 60);

        return null;
    }
}
