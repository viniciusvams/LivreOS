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

/**
 * Garante que a extensão nativa {@see \ZipArchive} está carregada antes de ler ZIPs do export M-OS.
 */
final class MosZipSupport
{
    public static function assertAvailable(): void
    {
        if (extension_loaded('zip') && class_exists(\ZipArchive::class, false)) {
            return;
        }

        throw new \RuntimeException(
            'A extensão PHP «zip» (classe ZipArchive) não está disponível neste PHP. ' .
            'Sem ela não é possível importar ficheiros .zip do export M-OS. ' .
            'No Windows/Laragon: edite o php.ini em uso por este site, descomente ou adicione «extension=zip» ' .
            '(ou «extension=php_zip.dll») e reinicie Apache/nginx ou o PHP em modo embutido. ' .
            'Confirme com: php -m | findstr zip (Windows) ou php -m | grep zip (Linux).'
        );
    }
}
