<?php

declare(strict_types=1);

/**
 * Componente da aplicação LivreOS
 *
 * @author    viniciusvams
 * @copyright 2024-2026 LivreOS
 * @license   https://www.gnu.org/licenses/agpl-3.0.txt AGPL-3.0
 */

namespace Plugins\ImportarSistemaExternoMos\Services;

use Illuminate\Support\Facades\DB;
use Throwable;

/**
 * SQLite permite um escritor de cada vez; importações M-OS fazem muitos INSERTs no mesmo
 * ficheiro .sqlite enquanto cache/sessão (predefinição CACHE_STORE=database) também escrevem lá —
 * origina SQLSTATE[HY000] / "database is locked". Retentativas curtas costumam resolver.
 */
final class MosSqliteBusyRetry
{
    public static function isSqliteDriver(): bool
    {
        try {
            return DB::getDriverName() === 'sqlite';
        } catch (Throwable) {
            return false;
        }
    }

    public static function isBusyException(Throwable $e): bool
    {
        if (! self::isSqliteDriver()) {
            return false;
        }
        $m = strtolower($e->getMessage());

        return str_contains($m, 'database is locked')
            || str_contains($m, 'sqlite_busy')
            || preg_match('/\bSQLITE_BUSY\b/', $e->getMessage()) === 1
            || preg_match('/\bgeneral error:\s*5\b/i', $e->getMessage()) === 1;
    }

    /**
     * @template T
     * @param  callable():T  $operation
     * @return T
     */
    public static function run(callable $operation, int $maxAttempts = 15, int $initialDelayUs = 20000): mixed
    {
        if (! self::isSqliteDriver()) {
            return $operation();
        }

        $delay = max(1000, $initialDelayUs);
        $last = null;

        for ($attempt = 0; $attempt < $maxAttempts; $attempt++) {
            try {
                return $operation();
            } catch (Throwable $e) {
                $last = $e;
                if (! self::isBusyException($e) || $attempt >= $maxAttempts - 1) {
                    throw $e;
                }
                usleep($delay);
                $delay = min((int) ($delay * 1.6), 500000);
            }
        }

        throw $last ?? new \RuntimeException('MosSqliteBusyRetry: falha sem excepção.');
    }
}
