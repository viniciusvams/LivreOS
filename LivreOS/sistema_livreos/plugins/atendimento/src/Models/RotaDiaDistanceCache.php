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

namespace Atendimento\Models;

use Illuminate\Database\Eloquent\Model;

class RotaDiaDistanceCache extends Model
{
    protected $table = 'plugin_atendimento_distance_cache';

    protected $fillable = ['segment_key', 'distance', 'duration', 'updated_at'];

    public $timestamps = false;

    const UPDATED_AT = 'updated_at';

    protected $casts = ['updated_at' => 'datetime'];

    public static function segmentKey(string $origem, string $destino): string
    {
        $o = preg_replace('/\s+/', ' ', trim($origem));
        $d = preg_replace('/\s+/', ' ', trim($destino));
        return md5($o . '|' . $d);
    }

    public static function getSegment(string $origem, string $destino): ?array
    {
        $key = self::segmentKey($origem, $destino);
        $row = static::where('segment_key', $key)->first();
        if (! $row) {
            return null;
        }
        return ['distance' => $row->distance, 'duration' => $row->duration];
    }

    public static function putSegment(string $origem, string $destino, string $distance, string $duration): void
    {
        $key = self::segmentKey($origem, $destino);
        static::updateOrCreate(
            ['segment_key' => $key],
            ['distance' => $distance, 'duration' => $duration, 'updated_at' => now()]
        );
    }
}
