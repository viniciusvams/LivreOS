<?php

/**
 * Valida formato e dígito verificador da chave de acesso da NF-e (44 posições).
 *
 * @copyright 2024-2026 LivreOS
 * @license   https://www.gnu.org/licenses/agpl-3.0.txt AGPL-3.0
 */
namespace Compras\Services;

class NfeChaveValidator
{
    /**
     * @return array{ok: bool, motivo?: string}
     */
    public static function validar(string $chave): array
    {
        $chave = preg_replace('/\D/', '', $chave);
        if (strlen($chave) !== 44) {
            return ['ok' => false, 'motivo' => 'A chave deve conter 44 dígitos.'];
        }
        $dvInformado = (int) substr($chave, -1);
        $base = substr($chave, 0, 43);
        $dvCalc = self::calcularDv($base);
        if ($dvCalc !== $dvInformado) {
            return ['ok' => false, 'motivo' => 'Dígito verificador da chave não confere.'];
        }

        return ['ok' => true];
    }

    public static function calcularDv(string $chave43): int
    {
        $multiplicadores = [2, 3, 4, 5, 6, 7, 8, 9];
        $soma = 0;
        $pos = 0;
        for ($i = 42; $i >= 0; $i--) {
            $soma += (int) $chave43[$i] * $multiplicadores[$pos % 8];
            $pos++;
        }
        $resto = $soma % 11;

        return $resto < 2 ? 0 : 11 - $resto;
    }
}
