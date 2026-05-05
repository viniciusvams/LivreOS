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

namespace App\Http\Controllers\Financeiro;

use App\Http\Controllers\Controller;
use App\Models\Configuracao;
use Illuminate\Http\Request;

class ConfiguracoesFinanceiroController extends Controller
{
    private const CHAVE_MULTA = 'financeiro.multa_percentual';
    private const CHAVE_JUROS = 'financeiro.juros_mensal_percentual';
    private const CHAVE_CARENCIA = 'financeiro.dias_carencia_multa';

    public function index()
    {
        $multa = (float) Configuracao::getValue(self::CHAVE_MULTA, '2');
        $juros = (float) Configuracao::getValue(self::CHAVE_JUROS, '1');
        $carencia = (int) Configuracao::getValue(self::CHAVE_CARENCIA, '0');

        return erp_view('financeiro.configuracoes.index', [
            'title' => 'Configurações Financeiro',
            'multa_percentual' => $multa,
            'juros_mensal_percentual' => $juros,
            'dias_carencia_multa' => $carencia,
        ]);
    }

    public function update(Request $request)
    {
        $validated = $request->validate([
            'multa_percentual' => 'nullable|numeric|min:0|max:100',
            'juros_mensal_percentual' => 'nullable|numeric|min:0|max:100',
            'dias_carencia_multa' => 'nullable|integer|min:0|max:365',
        ]);

        Configuracao::setValue(self::CHAVE_MULTA, (string) ($validated['multa_percentual'] ?? 0));
        Configuracao::setValue(self::CHAVE_JUROS, (string) ($validated['juros_mensal_percentual'] ?? 0));
        Configuracao::setValue(self::CHAVE_CARENCIA, (string) ($validated['dias_carencia_multa'] ?? 0));

        return redirect()->route('financeiro.configuracoes.index')
            ->with('success', 'Configurações do financeiro salvas com sucesso.');
    }

    /**
     * Retorna os parâmetros de juros/multa para cálculo (uso em controllers e relatórios).
     */
    public static function getParametrosJurosMulta(): array
    {
        return [
            'multa_percentual' => (float) Configuracao::getValue(self::CHAVE_MULTA, '2'),
            'juros_mensal_percentual' => (float) Configuracao::getValue(self::CHAVE_JUROS, '1'),
            'dias_carencia_multa' => (int) Configuracao::getValue(self::CHAVE_CARENCIA, '0'),
        ];
    }

    /**
     * Calcula multa e juros sugeridos para um título vencido.
     * multa: percentual sobre valor original, aplicado após carência.
     * juros: percentual ao mês (proporcional aos dias) sobre valor original.
     */
    public static function calcularJurosMultaSugeridos(float $valorOriginal, string $dataVencimento): array
    {
        $params = self::getParametrosJurosMulta();
        $hoje = now()->toDateString();
        $venc = \Carbon\Carbon::parse($dataVencimento);
        if ($venc->toDateString() >= $hoje) {
            return ['multa' => 0.0, 'juros' => 0.0];
        }
        $diasAtraso = (int) $venc->diffInDays($hoje, false);
        $diasParaMulta = max(0, $diasAtraso - $params['dias_carencia_multa']);
        $multa = $diasParaMulta > 0 ? round($valorOriginal * ($params['multa_percentual'] / 100), 2) : 0.0;
        $mesesAtraso = $diasAtraso / 30.0;
        $juros = round($valorOriginal * ($params['juros_mensal_percentual'] / 100) * $mesesAtraso, 2);
        return ['multa' => $multa, 'juros' => $juros];
    }
}
