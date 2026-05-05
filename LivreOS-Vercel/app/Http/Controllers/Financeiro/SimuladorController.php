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
use App\Models\Adquirente;
use App\Services\AdquirenteService;
use Carbon\Carbon;
use Illuminate\Http\Request;

class SimuladorController extends Controller
{
    protected $adquirenteService;

    public function __construct(AdquirenteService $adquirenteService)
    {
        $this->adquirenteService = $adquirenteService;
    }

    public function index()
    {
        $query = $this->applyEntityQuery(
            Adquirente::where('ativo', true)->orderBy('nome'),
            'adquirente'
        );
        $adquirentes = $query->get();

        return erp_view('financeiro.simulador.index', [
            'title' => 'Simulador de Transações',
            'adquirentes' => $adquirentes,
        ]);
    }

    public function simular(Request $request)
    {
        $validated = $this->validateWithFilters($request, [
            'valor_venda' => 'required|numeric|min:0.01',
            'parcelas' => 'required|integer|min:1|max:120',
            'adquirente_id' => 'required|exists:adquirentes,id',
            'bandeira' => 'required|in:master,visa,elo,amex,hipercard,outros',
            'antecipado' => 'nullable',
            'data_venda' => 'nullable|date',
        ]);

        try {
            $resultado = $this->adquirenteService->simularTransacao(
                valorVenda: (float) $validated['valor_venda'],
                parcelas: (int) $validated['parcelas'],
                adquirenteId: (int) $validated['adquirente_id'],
                bandeira: $validated['bandeira'],
                antecipado: $request->boolean('antecipado'),
                dataVenda: $validated['data_venda'] ? Carbon::parse($validated['data_venda']) : null
            );

            return erp_json(['success' => true, 'data' => $resultado], 200, [], 'simulador.simular');
        } catch (\Exception $e) {
            return erp_json(['success' => false, 'message' => $e->getMessage()], 400, [], 'simulador.simular.error');
        }
    }

    public function comparar(Request $request)
    {
        $validated = $this->validateWithFilters($request, [
            'valor_venda' => 'required|numeric|min:0.01',
            'parcelas' => 'required|integer|min:1|max:120',
            'adquirente_ids' => 'required|array|min:2',
            'adquirente_ids.*' => 'exists:adquirentes,id',
            'bandeira' => 'required|in:master,visa,elo,amex,hipercard,outros',
            'antecipado' => 'nullable',
        ]);

        try {
            $comparacao = $this->adquirenteService->compararAdquirentes(
                valorVenda: (float) $validated['valor_venda'],
                parcelas: (int) $validated['parcelas'],
                adquirenteIds: $validated['adquirente_ids'],
                bandeira: $validated['bandeira'],
                antecipado: $request->boolean('antecipado')
            );

            return erp_json(['success' => true, 'data' => $comparacao], 200, [], 'simulador.comparar');
        } catch (\Exception $e) {
            return erp_json(['success' => false, 'message' => $e->getMessage()], 400, [], 'simulador.comparar.error');
        }
    }
}
