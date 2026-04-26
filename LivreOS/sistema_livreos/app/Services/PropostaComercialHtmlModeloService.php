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

/**
 * Preenche modelo .html/.htm com as mesmas tags ${...} dos modelos Word.
 * Use ${itens_tabela_linhas} dentro do &lt;tbody&gt; ou uma linha &lt;tr&gt; com ${item_linha}…${item_total} (duplicada por item).
 */
class PropostaComercialHtmlModeloService
{
    public function __construct(
        private PropostaComercialModeloVariaveisService $variaveis
    ) {}

    /**
     * @throws \RuntimeException
     */
    public function renderizarConteudo(PropostaComercial $proposta, string $caminhoAbsolutoHtml): string
    {
        if (! is_readable($caminhoAbsolutoHtml)) {
            throw new \RuntimeException('Modelo HTML não encontrado ou ilegível.');
        }

        $raw = file_get_contents($caminhoAbsolutoHtml);
        if ($raw === false) {
            throw new \RuntimeException('Não foi possível ler o ficheiro modelo HTML.');
        }

        $raw = $this->normalizarMarcadoresEmpresaLogoHtml($raw);

        $proposta->loadMissing(['cliente', 'vendedor', 'tabelaPreco', 'itens']);

        $vars = $this->variaveis->variaveisEscalaresParaTemplate($proposta);
        $vars['itens_tabela_linhas'] = $this->montarLinhasTabelaHtml($proposta);
        // HTML dos editores; só para modelos .html — inserção sem htmlspecialchars (ver substituirPlaceholders).
        $vars['proposta_descricao_html'] = filled($proposta->descricao)
            ? trim((string) $proposta->descricao)
            : '';
        $vars['observacoes_html'] = filled($proposta->observacoes)
            ? trim((string) $proposta->observacoes)
            : '';
        $vars['observacoes_internas_html'] = filled($proposta->observacoes_internas)
            ? trim((string) $proposta->observacoes_internas)
            : '';

        if (function_exists('apply_filters')) {
            $filtered = apply_filters('proposta_comercial.html_modelo.variaveis', $vars, $proposta);
            if (is_array($filtered)) {
                $vars = $filtered;
            }
        }

        if (trim((string) ($vars['empresa_logo'] ?? '')) === '') {
            $dataUri = $this->variaveis->empresaLogoDataUriParaModeloHtml();
            $vars['empresa_logo'] = $dataUri !== ''
                ? $dataUri
                : $this->variaveis->empresaLogoPublicUrlParaModeloHtml();
        }

        $html = $this->substituirPlaceholders($raw, $vars);
        $html = $this->expandirLinhasItensSeTemplate($html, $proposta);

        if (function_exists('apply_filters')) {
            $htmlFiltered = apply_filters('proposta_comercial.html_modelo.conteudo', $html, $proposta);
            if (is_string($htmlFiltered) && $htmlFiltered !== '') {
                $html = $htmlFiltered;
            }
        }

        return $html;
    }

    /**
     * @param  array<string, string>  $vars
     */
    private function substituirPlaceholders(string $html, array $vars): string
    {
        uksort($vars, static fn (string $a, string $b): int => strlen($b) <=> strlen($a));

        foreach ($vars as $key => $val) {
            $pattern = '/\$\{\s*'.preg_quote($key, '/').'\s*\}/u';
            $semEscapeHtml = [
                'itens_tabela_linhas',
                'empresa_logo',
                'proposta_descricao_html',
                'observacoes_html',
                'observacoes_internas_html',
            ];
            if (in_array($key, $semEscapeHtml, true)) {
                $html = preg_replace_callback(
                    $pattern,
                    static fn (): string => (string) $val,
                    $html
                );

                continue;
            }
            $escaped = htmlspecialchars((string) $val, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
            $html = preg_replace_callback(
                $pattern,
                static fn (): string => $escaped,
                $html
            );
        }

        return $html;
    }

    /**
     * O valor de ${empresa_logo} é URL ou data URI: tem de ir em &lt;img src="..."&gt;.
     * - &lt;span&gt; só com o marcador → &lt;img&gt; com os mesmos atributos.
     * - Marcador “solto” (ex.: dentro de &lt;div class="logo-wrapper"&gt;) → envolve em &lt;img src="${empresa_logo}"&gt;.
     * - &lt;img src="${empresa_logo}"&gt; já correcto → mantém-se (mascara-se para não duplicar).
     */
    private function normalizarMarcadoresEmpresaLogoHtml(string $html): string
    {
        $mask = '__ERP_EMPRESA_LOGO_SRC_PH__';

        $html = (string) preg_replace_callback(
            '/<span([^>]*)>\s*\$\{\s*empresa_logo\s*\}\s*<\/span>/iu',
            static function (array $m): string {
                $attrs = rtrim($m[1]);

                return '<img'.$attrs.' src="${empresa_logo}" alt=""'
                    .' style="max-height:52px;max-width:220px;height:auto;width:auto;object-fit:contain;vertical-align:middle;display:inline-block;" />';
            },
            $html
        );

        $html = (string) preg_replace_callback(
            '/<img\b[^>]*\ssrc\s*=\s*(["\'])\s*\$\{\s*empresa_logo\s*\}\s*\1[^>]*\/?>/iu',
            static function (array $m) use ($mask): string {
                return str_replace('${empresa_logo}', $mask, $m[0]);
            },
            $html
        );

        $html = (string) preg_replace(
            '/\$\{\s*empresa_logo\s*\}/u',
            '<img src="${empresa_logo}" alt="" style="max-height:72px;max-width:220px;height:auto;width:auto;object-fit:contain;display:block;" />',
            $html
        );

        return str_replace($mask, '${empresa_logo}', $html);
    }

    /**
     * Modelos Word duplicam a linha com ${item_linha} via cloneRow; em HTML isso não existe.
     * Se o &lt;tbody&gt; ainda contiver ${item_linha}, duplica o &lt;tr&gt; modelo para cada item.
     * (Alternativa documentada: usar só ${itens_tabela_linhas} dentro do tbody.)
     */
    private function expandirLinhasItensSeTemplate(string $html, PropostaComercial $proposta): string
    {
        if (preg_match('/\$\{\s*item_linha\s*\}/u', $html) !== 1) {
            return $html;
        }

        $linhas = $this->variaveis->linhasItens($proposta);

        return (string) preg_replace_callback(
            '/<tbody\b[^>]*>[\s\S]*?<\/tbody>/i',
            function (array $m) use ($linhas): string {
                $tbody = $m[0];
                if (preg_match('/\$\{\s*item_linha\s*\}/u', $tbody) !== 1) {
                    return $tbody;
                }
                if (! preg_match('/<tr\b[^>]*>[\s\S]*?\$\{\s*item_linha\s*\}[\s\S]*?<\/tr>/iu', $tbody, $trMatch)) {
                    return $tbody;
                }
                $templateTr = $trMatch[0];
                $rows = '';
                foreach ($linhas as $linha) {
                    $row = $templateTr;
                    foreach ($linha as $key => $val) {
                        $pattern = '/\$\{\s*'.preg_quote($key, '/').'\s*\}/u';
                        $escaped = htmlspecialchars((string) $val, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
                        $row = preg_replace_callback(
                            $pattern,
                            static fn (): string => $escaped,
                            $row
                        );
                    }
                    $rows .= $row;
                }

                return str_replace($templateTr, $rows, $tbody);
            },
            $html
        );
    }

    private function montarLinhasTabelaHtml(PropostaComercial $proposta): string
    {
        $rows = '';
        foreach ($this->variaveis->linhasItens($proposta) as $linha) {
            $rows .= '<tr>'
                .'<td>'.e($linha['item_linha']).'</td>'
                .'<td>'.e($linha['item_tipo']).'</td>'
                .'<td>'.e($linha['item_descricao']).'</td>'
                .'<td>'.e($linha['item_quantidade']).'</td>'
                .'<td>'.e($linha['item_preco_unitario']).'</td>'
                .'<td>'.e($linha['item_desconto']).'</td>'
                .'<td>'.e($linha['item_total']).'</td>'
                .'</tr>';
        }

        return $rows;
    }
}
