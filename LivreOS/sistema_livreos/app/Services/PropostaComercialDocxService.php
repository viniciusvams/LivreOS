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

use App\Models\PropostaComercial;
use App\Support\PhpWord\DocxHtmlFragmentBuilder;
use App\Support\PhpWord\ResilientTemplateProcessor;
use PhpOffice\PhpWord\Exception\Exception as PhpWordException;
use PhpOffice\PhpWord\Settings;
use PhpOffice\PhpWord\TemplateProcessor;

class PropostaComercialDocxService
{
    /**
     * Macros preenchidas com HTML rico (negrito, listas, etc.) quando o modelo .docx contém a tag.
     * Valor: atributo em {@see PropostaComercial} com o HTML do editor.
     *
     * @var array<string, string>
     */
    private const MACROS_CAMPO_HTML = [
        'proposta_descricao' => 'descricao',
        'observacoes' => 'observacoes',
        'observacoes_internas' => 'observacoes_internas',
    ];

    public function __construct(
        private PropostaComercialModeloVariaveisService $variaveis
    ) {}

    /**
     * Preenche o template .docx e grava em $caminhoSaida (caminho absoluto).
     *
     * @throws PhpWordException|\RuntimeException
     */
    public function preencher(PropostaComercial $proposta, string $caminhoTemplate, string $caminhoSaida): void
    {
        if (! is_readable($caminhoTemplate)) {
            throw new \RuntimeException('Arquivo de modelo não encontrado ou ilegível.');
        }

        $proposta->loadMissing(['cliente', 'vendedor', 'tabelaPreco', 'itens']);

        $processor = new ResilientTemplateProcessor($caminhoTemplate);

        $vars = $this->variaveis->variaveisEscalaresParaTemplate($proposta);
        unset($vars['empresa_logo']);
        $varsCompletas = $vars;

        $contagens = $processor->getVariableCount();
        $fragmentosXml = [];
        foreach (self::MACROS_CAMPO_HTML as $macro => $attr) {
            if (! isset($contagens[$macro]) || $contagens[$macro] === 0) {
                continue;
            }
            $bruto = trim((string) ($proposta->{$attr} ?? ''));
            if ($bruto === '') {
                continue;
            }
            try {
                $fragmentosXml[$macro] = DocxHtmlFragmentBuilder::fragmentoXmlApartirDeHtml($bruto);
                unset($vars[$macro]);
            } catch (\Throwable) {
                // Modelo continua com texto plano já presente em $vars
            }
        }

        $escapingAntes = Settings::isOutputEscapingEnabled();
        Settings::setOutputEscapingEnabled(true);
        try {
            $processor->setValues($vars);

            foreach (['proposta_descricao_html', 'observacoes_html', 'observacoes_internas_html'] as $htmlOnly) {
                $processor->setValue($htmlOnly, '');
            }

            foreach ($fragmentosXml as $macro => $xml) {
                $this->substituirMacroPorFragmentoXml(
                    $processor,
                    $macro,
                    $xml,
                    (string) ($varsCompletas[$macro] ?? '')
                );
            }

            $linhas = $this->variaveis->linhasItens($proposta);
            try {
                $processor->cloneRowAndSetValues('item_linha', $linhas);
            } catch (PhpWordException $e) {
                if (str_contains(strtolower($e->getMessage()), 'clone row')
                    || str_contains(strtolower($e->getMessage()), 'template variable not found')) {
                    // Modelo sem tabela de itens com ${item_linha} — apenas variáveis escalares.
                } else {
                    throw $e;
                }
            }

            $this->aplicarLogoEmpresaNoDocx($processor);

            $processor->saveAs($caminhoSaida);
        } finally {
            Settings::setOutputEscapingEnabled($escapingAntes);
        }
    }

    /**
     * Substitui ${empresa_logo} no .docx: imagem da empresa ou do sistema (config app.logo_path);
     * se não houver ficheiro, limpa o marcador de texto. Marcador só de imagem pode ficar em branco no Word.
     */
    private function aplicarLogoEmpresaNoDocx(TemplateProcessor $processor): void
    {
        $counts = $processor->getVariableCount();
        if (! array_key_exists('empresa_logo', $counts)) {
            return;
        }

        $path = $this->variaveis->caminhoAbsolutoLogoParaTemplates();
        if ($path !== null && is_readable($path)) {
            try {
                $processor->setImageValue('empresa_logo', ['path' => $path, 'width' => 120, 'height' => 72]);
            } catch (\Throwable) {
                $processor->setValue('empresa_logo', '');
            }

            return;
        }

        $processor->setValue('empresa_logo', '');
    }

    /**
     * Substitui o parágrafo que contém ${macro} por fragmento OOXML (vários w:p). Se não conseguir, usa texto plano.
     */
    private function substituirMacroPorFragmentoXml(
        TemplateProcessor $processor,
        string $macro,
        string $xmlFragmento,
        string $fallbackTextoPlano
    ): void {
        $guard = 0;
        $remaining = $processor->getVariableCount()[$macro] ?? 0;

        while ($remaining > 0 && $guard < 64) {
            $processor->replaceXmlBlock($macro, $xmlFragmento, 'w:p');
            $newRemaining = $processor->getVariableCount()[$macro] ?? 0;
            if ($newRemaining >= $remaining) {
                break;
            }
            $remaining = $newRemaining;
            ++$guard;
        }

        if (($processor->getVariableCount()[$macro] ?? 0) > 0) {
            $processor->setValue($macro, $fallbackTextoPlano);
        }
    }
}
