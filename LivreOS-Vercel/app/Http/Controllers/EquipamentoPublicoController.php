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

namespace App\Http\Controllers;

use App\Models\Empresa;
use App\Models\EquipamentoPublicoAcesso;
use App\Models\OrdemServico;
use Illuminate\Http\Request;

class EquipamentoPublicoController extends Controller
{
    /**
     * Página pública para identificação de equipamento via QR Code.
     * Exibe dados do equipamento (sem senha), cliente e permite ações com autenticação.
     * Por padrão só aceita o token opaco (equipamento_publico_token). ID numérico na URL
     * exige EQUIPAMENTO_PUBLICO_PERMITIR_ID_NA_URL=true no .env (evita enumeração).
     */
    public function show(Request $request, string $token)
    {
        $ordemServico = $this->resolverOrdem($token);
        if (!$ordemServico) {
            abort(404, 'Equipamento não encontrado.');
        }

        $ordemServico->garantirTokenPublico();
        $ordemServico->load(['cliente', 'equipamento']);

        $this->registrarAcesso($ordemServico, $request);

        $historico = $this->obterHistoricoEquipamento($ordemServico);

        return erp_view('equipamento_publico.show', [
            'title' => 'Equipamento - ' . ($ordemServico->equipamento_nome ?? $ordemServico->equipamento?->marca ?? 'Identificação'),
            'ordem' => $ordemServico,
            'ordemServico' => $ordemServico,
            'empresa' => Empresa::first(),
            'historico' => $historico,
        ]);
    }

    private function resolverOrdem(string $token): ?OrdemServico
    {
        if (ctype_digit($token) && config('equipamento_publico.permitir_id_na_url')) {
            return OrdemServico::find((int) $token);
        }

        return OrdemServico::where('equipamento_publico_token', $token)->first();
    }

    private function registrarAcesso(OrdemServico $ordem, Request $request): void
    {
        try {
            EquipamentoPublicoAcesso::create([
                'ordem_servico_id' => $ordem->id,
                'ip' => $request->ip(),
                'user_agent' => substr($request->userAgent() ?? '', 0, 500),
            ]);
        } catch (\Throwable $e) {
            // Silenciar falhas de log (ex: DB indisponível)
        }
    }

    private function obterHistoricoEquipamento(OrdemServico $ordem): \Illuminate\Database\Eloquent\Collection
    {
        $query = OrdemServico::query()
            ->with(['cliente'])
            ->where('id', '!=', $ordem->id)
            ->orderByDesc('data_abertura');

        if ($ordem->equipamento_id) {
            $query->where('equipamento_id', $ordem->equipamento_id);
        } elseif ($ordem->equipamento_numero_serie && $ordem->cliente_id) {
            $query->where('cliente_id', $ordem->cliente_id)
                ->where('equipamento_numero_serie', $ordem->equipamento_numero_serie);
        } else {
            return collect();
        }

        return $query->limit(10)->get();
    }
}
