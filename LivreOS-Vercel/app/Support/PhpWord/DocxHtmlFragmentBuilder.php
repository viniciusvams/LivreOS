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

use PhpOffice\PhpWord\PhpWord;
use PhpOffice\PhpWord\Shared\Html;
use PhpOffice\PhpWord\Shared\XMLWriter;
use PhpOffice\PhpWord\Writer\Word2007\Element\Container;

/**
 * Converte HTML (ex.: descrição da proposta) em fragmento OOXML (w:p, w:r, w:t…) para colar no documento via TemplateProcessor::replaceXmlBlock.
 */
final class DocxHtmlFragmentBuilder
{
    /**
     * @throws \Throwable Erros de parse DOM ou falhas internas do PhpWord ao interpretar o HTML
     */
    public static function fragmentoXmlApartirDeHtml(string $html): string
    {
        $html = trim($html);
        if ($html === '') {
            return '<w:p><w:r><w:t xml:space="preserve"></w:t></w:r></w:p>';
        }

        $html = self::sanitizarHtml($html);

        $phpWord = new PhpWord;
        $section = $phpWord->addSection();
        Html::addHtml($section, $html, false, true);

        $xmlWriter = new XMLWriter(XMLWriter::STORAGE_MEMORY, null, true);
        $writer = new Container($xmlWriter, $section);
        $writer->write();

        $out = trim($xmlWriter->getData());

        return $out !== '' ? $out : '<w:p><w:r><w:t xml:space="preserve"></w:t></w:r></w:p>';
    }

    private static function sanitizarHtml(string $html): string
    {
        $html = preg_replace('#<(script|iframe|object|embed|form|input|button|textarea|select|option)\b[^>]*>[\s\S]*?</\1>#iu', '', $html) ?? $html;
        $html = preg_replace('/\son\w+\s*=\s*("|\').*?\1/iu', '', $html) ?? $html;
        $html = preg_replace('/\son\w+\s*=\s*[^\s>]+/iu', '', $html) ?? $html;
        $html = preg_replace('#<img\b[^>]*>#iu', '', $html) ?? $html;

        $permitidas = '<p><br><br/><strong><b><em><i><u><ul><ol><li><div><span><h1><h2><h3><h4><h5><h6>'
            .'<table><thead><tbody><tfoot><tr><th><td><colgroup><col><a><blockquote><pre><code><hr>';

        return strip_tags($html, $permitidas);
    }
}
