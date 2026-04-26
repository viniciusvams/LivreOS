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

namespace App\Support\PhpWord;

/**
 * Normaliza o XML interno do .docx para macros ${...} reconhecidas pelo PhpWord.
 *
 * O Word (ou cópia de PDF/browser) pode inserir dólar ou chavetas em Unicode “fullwidth”,
 * equivalentes visuais a $ e { }, mas o motor faz str_replace pela string ASCII e não encontra a tag.
 */
final class DocxMacroXmlNormalizer
{
    /**
     * Substitui variantes Unicode comuns de $ { } usadas em macros partidas ou coladas de outras fontes.
     */
    public static function foldMacroLikeUnicode(string $xml): string
    {
        static $map = [
            "\u{FF04}" => '$',
            "\u{FE69}" => '$',
            "\u{FF5B}" => '{',
            "\u{FF5D}" => '}',
        ];

        return str_replace(array_keys($map), array_values($map), $xml);
    }
}
