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

namespace App\Http\Controllers\ConfiguracoesSistema;

use App\Http\Controllers\Controller;
use App\Models\Configuracao;
use App\Models\PropostaComercial;
use Illuminate\Http\Request;

class PropostasComerciaisConfigController extends Controller
{
    public function edit()
    {
        $maiorIndice = PropostaComercial::maiorIndiceNumericoNumeroSequencial();
        $proximoNatural = $maiorIndice + 1;
        $configurado = (int) Configuracao::getValue('propostas_comerciais.proximo_numero', (string) $proximoNatural);
        if ($configurado < 1) {
            $configurado = $proximoNatural;
        }
        $proximoEfetivo = max($proximoNatural, $configurado);
        $ultimoCodigoRecente = PropostaComercial::query()->orderByDesc('id')->value('numero_sequencial');

        return erp_view('configuracoes_sistema.propostas_comerciais.edit', [
            'title' => 'Configurações — Propostas comerciais (numeração)',
            'mascara_numero' => Configuracao::getValue('propostas_comerciais.mascara_numero', 'PC{numero}'),
            'numero_pad' => (int) Configuracao::getValue('propostas_comerciais.numero_pad', '6'),
            'maior_indice_numerico' => $maiorIndice,
            'ultimo_codigo_recente' => $ultimoCodigoRecente,
            'proximo_natural' => $proximoNatural,
            'proximo_configurado' => $configurado,
            'proximo_efetivo' => $proximoEfetivo,
        ]);
    }

    public function update(Request $request)
    {
        $this->validateWithFilters($request, [
            'mascara_numero' => 'required|string|max:40',
            'numero_pad' => 'required|integer|min:1|max:12',
            'proximo_numero' => 'required|integer|min:1|max:99999999',
        ], [
            'mascara_numero.required' => 'Informe a máscara do número da proposta.',
            'proximo_numero.required' => 'Informe o próximo número da sequência.',
        ]);

        $mascara = (string) $request->input('mascara_numero');
        if (! str_contains($mascara, '{numero}')) {
            return redirect()->route('configuracoes-sistema.propostas-comerciais.edit')
                ->withErrors(['mascara_numero' => 'A máscara deve conter o placeholder {numero} (ex.: PC{numero} ou PROP-{numero}).'])
                ->withInput();
        }

        $pad = (int) $request->input('numero_pad');
        $exemplo = str_replace('{numero}', str_repeat('0', $pad), $mascara);
        if (strlen($exemplo) > 30) {
            return redirect()->route('configuracoes-sistema.propostas-comerciais.edit')
                ->withErrors(['mascara_numero' => 'A combinação de máscara e quantidade de dígitos gera código com mais de 30 caracteres. Reduza o texto ou o padding.'])
                ->withInput();
        }

        $maiorIndice = PropostaComercial::maiorIndiceNumericoNumeroSequencial();
        $proximoNumero = (int) $request->input('proximo_numero');
        if ($maiorIndice > 0 && $proximoNumero <= $maiorIndice) {
            return redirect()->route('configuracoes-sistema.propostas-comerciais.edit')
                ->with('error', 'Para não repetir ou retroceder na sequência, o próximo número deve ser maior que o maior índice já usado nas propostas ('.$maiorIndice.').')
                ->withInput();
        }

        Configuracao::setValue('propostas_comerciais.mascara_numero', $mascara);
        Configuracao::setValue('propostas_comerciais.numero_pad', (string) $pad);
        Configuracao::setValue('propostas_comerciais.proximo_numero', (string) $proximoNumero);

        return redirect()->route('configuracoes-sistema.propostas-comerciais.edit')
            ->with('success', 'Numeração das propostas comerciais salva com sucesso.');
    }
}
