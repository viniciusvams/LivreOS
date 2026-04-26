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

use PhpOffice\PhpWord\TemplateProcessor;

/**
 * TemplateProcessor com passo extra após carregar o .docx: corrige caracteres que parecem $ e { }
 * mas estão em Unicode, para o PhpWord conseguir substituir ${proposta_descricao} e outras tags.
 */
final class ResilientTemplateProcessor extends TemplateProcessor
{
    public function __construct(string $documentTemplate)
    {
        parent::__construct($documentTemplate);

        $this->tempDocumentMainPart = DocxMacroXmlNormalizer::foldMacroLikeUnicode($this->tempDocumentMainPart);
        foreach ($this->tempDocumentHeaders as $i => $header) {
            $this->tempDocumentHeaders[$i] = DocxMacroXmlNormalizer::foldMacroLikeUnicode($header);
        }
        foreach ($this->tempDocumentFooters as $i => $footer) {
            $this->tempDocumentFooters[$i] = DocxMacroXmlNormalizer::foldMacroLikeUnicode($footer);
        }
    }
}
