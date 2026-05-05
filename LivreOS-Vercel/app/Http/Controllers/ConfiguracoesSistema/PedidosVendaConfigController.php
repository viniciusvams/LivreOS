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
use App\Models\CentroCusto;
use App\Models\PedidoVenda;
use App\Models\PlanoConta;
use Illuminate\Http\Request;

class PedidosVendaConfigController extends Controller
{
    public function edit()
    {
        $maiorIndice = PedidoVenda::maiorIndiceNumericoNumeroSequencial();
        $proximoNatural = $maiorIndice + 1;
        $configurado = (int) Configuracao::getValue('pedidos_venda.proximo_numero', (string) $proximoNatural);
        if ($configurado < 1) {
            $configurado = $proximoNatural;
        }
        $proximoEfetivo = max($proximoNatural, $configurado);
        $ultimoCodigoRecente = PedidoVenda::withTrashed()->orderByDesc('id')->value('numero_sequencial');

        $vdRaw = Configuracao::getValue('pedidos_venda.validade_orcamento_dias', '');
        $validadeOrcamentoDias = ($vdRaw !== null && $vdRaw !== '' && is_numeric($vdRaw)) ? (int) $vdRaw : null;

        return erp_view('configuracoes_sistema.pedidos_venda.edit', [
            'title'                 => 'Configurações — Pedidos de Venda',
            'estoque_modo'          => Configuracao::getValue('pedidos_venda.estoque_modo', 'faturar'),
            'gerar_os'              => Configuracao::getValue('pedidos_venda.gerar_os_servico', '0') === '1',
            'plano_conta_id'        => Configuracao::getValue('pedidos_venda.plano_conta_receita'),
            'plano_conta_estorno_id' => Configuracao::getValue('pedidos_venda.plano_conta_estorno'),
            'centro_custo_id'       => Configuracao::getValue('pedidos_venda.centro_custo'),
            'mascara_numero'        => Configuracao::getValue('pedidos_venda.mascara_numero', 'PV{numero}'),
            'numero_pad'            => (int) Configuracao::getValue('pedidos_venda.numero_pad', '6'),
            'maior_indice_numerico' => $maiorIndice,
            'ultimo_codigo_recente' => $ultimoCodigoRecente,
            'proximo_natural'       => $proximoNatural,
            'proximo_configurado'   => $configurado,
            'proximo_efetivo'       => $proximoEfetivo,
            'validade_orcamento_dias' => $validadeOrcamentoDias,
            'planos_conta'          => PlanoConta::where('ativo', true)->orderBy('codigo')->get(),
            'planos_conta_despesa'  => PlanoConta::where('ativo', true)->where('tipo', 'despesa')->orderBy('codigo')->orderBy('nome')->get(),
            'centros_custo'         => CentroCusto::where('ativo', true)->orderBy('nome')->get(),
        ]);
    }

    public function update(Request $request)
    {
        $this->validateWithFilters($request, [
            'estoque_modo'            => 'required|in:faturar,reservar_confirmar',
            'gerar_os'                => 'nullable|boolean',
            'plano_conta_id'          => 'nullable|integer|exists:plano_contas,id',
            'plano_conta_estorno_id'  => 'nullable|integer|exists:plano_contas,id',
            'centro_custo_id'         => 'nullable|integer|exists:centros_custos,id',
            'mascara_numero'          => 'required|string|max:40',
            'numero_pad'              => 'required|integer|min:1|max:12',
            'proximo_numero'          => 'required|integer|min:1|max:99999999',
            'validade_orcamento_dias' => 'nullable|integer|min:1|max:3650',
        ], [
            'mascara_numero.required' => 'Informe a máscara do número do pedido.',
            'proximo_numero.required' => 'Informe o próximo número da sequência.',
        ]);

        $mascara = (string) $request->input('mascara_numero');
        if (! str_contains($mascara, '{numero}')) {
            return redirect()->route('configuracoes-sistema.pedidos-venda.edit')
                ->withErrors(['mascara_numero' => 'A máscara deve conter o placeholder {numero} (ex.: PV{numero} ou PED-{numero}).'])
                ->withInput();
        }

        $pad = (int) $request->input('numero_pad');
        $exemplo = str_replace('{numero}', str_repeat('0', $pad), $mascara);
        if (strlen($exemplo) > 30) {
            return redirect()->route('configuracoes-sistema.pedidos-venda.edit')
                ->withErrors(['mascara_numero' => 'A combinação de máscara e quantidade de dígitos gera código com mais de 30 caracteres. Reduza o texto ou o padding.'])
                ->withInput();
        }

        $maiorIndice = PedidoVenda::maiorIndiceNumericoNumeroSequencial();
        $proximoNumero = (int) $request->input('proximo_numero');
        if ($maiorIndice > 0 && $proximoNumero <= $maiorIndice) {
            return redirect()->route('configuracoes-sistema.pedidos-venda.edit')
                ->with('error', 'Para não repetir ou retroceder na sequência, o próximo número deve ser maior que o maior índice já usado nos pedidos (' . $maiorIndice . ').')
                ->withInput();
        }

        Configuracao::setValue('pedidos_venda.estoque_modo', $request->estoque_modo);
        Configuracao::setValue('pedidos_venda.gerar_os_servico', $request->boolean('gerar_os') ? '1' : '0');
        Configuracao::setValue('pedidos_venda.plano_conta_receita', $request->input('plano_conta_id', ''));
        Configuracao::setValue('pedidos_venda.plano_conta_estorno', $request->input('plano_conta_estorno_id', ''));
        Configuracao::setValue('pedidos_venda.centro_custo', $request->input('centro_custo_id', ''));
        Configuracao::setValue('pedidos_venda.mascara_numero', $mascara);
        Configuracao::setValue('pedidos_venda.numero_pad', (string) $pad);
        Configuracao::setValue('pedidos_venda.proximo_numero', (string) $proximoNumero);

        $vd = $request->input('validade_orcamento_dias');
        if ($vd === null || $vd === '') {
            Configuracao::setValue('pedidos_venda.validade_orcamento_dias', '');
        } else {
            Configuracao::setValue('pedidos_venda.validade_orcamento_dias', (string) max(1, min(3650, (int) $vd)));
        }

        return redirect()->route('configuracoes-sistema.pedidos-venda.edit')
            ->with('success', 'Configurações de Pedidos de Venda salvas com sucesso.');
    }
}
