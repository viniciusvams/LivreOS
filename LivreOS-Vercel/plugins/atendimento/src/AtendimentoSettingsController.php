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

namespace Atendimento;

use Atendimento\Models\PrioridadeAtendimento;
use Atendimento\Models\StatusAtendimento;
use Atendimento\Models\TempoEstimadoAtendimento;
use App\Models\Configuracao;
use Illuminate\Http\Request;

class AtendimentoSettingsController
{
    /**
     * Lista status e prioridades para a view de configurações (injetada pelo filter).
     */
    public function index()
    {
        $statusList = StatusAtendimento::orderBy('ordem')->orderBy('nome')->get();
        $prioridadesList = PrioridadeAtendimento::orderBy('ordem')->orderBy('nome')->get();
        return view('atendimento::settings', [
            'statusList' => $statusList,
            'prioridadesList' => $prioridadesList,
        ]);
    }

    // ---------- Status ----------

    public function storeStatus(Request $request)
    {
        $request->validate([
            'nome' => 'required|string|max:80',
            'cor' => 'nullable|string|max:20',
            'ordem' => 'nullable|integer|min:0',
        ]);
        StatusAtendimento::create([
            'nome' => $request->nome,
            'cor' => $request->cor ?: '#6b7280',
            'ordem' => (int) ($request->ordem ?? 0),
        ]);
        return redirect()->route('configuracoes-sistema.plugins.show', 'atendimento')
            ->with('success', 'Status adicionado.');
    }

    public function updateStatus(Request $request, $id)
    {
        $status = StatusAtendimento::findOrFail($id);
        $request->validate([
            'nome' => 'required|string|max:80',
            'cor' => 'nullable|string|max:20',
            'ordem' => 'nullable|integer|min:0',
        ]);
        $status->update([
            'nome' => $request->nome,
            'cor' => $request->cor ?: '#6b7280',
            'ordem' => (int) ($request->ordem ?? 0),
        ]);
        return redirect()->route('configuracoes-sistema.plugins.show', 'atendimento')
            ->with('success', 'Status atualizado.');
    }

    public function destroyStatus($id)
    {
        $status = StatusAtendimento::findOrFail($id);
        if ($status->atendimentos()->exists()) {
            return redirect()->route('configuracoes-sistema.plugins.show', 'atendimento')
                ->with('error', 'Não é possível excluir: existem atendimentos com este status.');
        }
        $status->delete();
        return redirect()->route('configuracoes-sistema.plugins.show', 'atendimento')
            ->with('success', 'Status excluído.');
    }

    // ---------- Prioridades ----------

    public function storePrioridade(Request $request)
    {
        $request->validate([
            'nome' => 'required|string|max:80',
            'cor' => 'nullable|string|max:20',
            'ordem' => 'nullable|integer|min:0',
        ]);
        PrioridadeAtendimento::create([
            'nome' => $request->nome,
            'cor' => $request->cor ?: '#6b7280',
            'ordem' => (int) ($request->ordem ?? 0),
        ]);
        return redirect()->route('configuracoes-sistema.plugins.show', 'atendimento')
            ->with('success', 'Prioridade adicionada.');
    }

    public function updatePrioridade(Request $request, $id)
    {
        $prioridade = PrioridadeAtendimento::findOrFail($id);
        $request->validate([
            'nome' => 'required|string|max:80',
            'cor' => 'nullable|string|max:20',
            'ordem' => 'nullable|integer|min:0',
        ]);
        $prioridade->update([
            'nome' => $request->nome,
            'cor' => $request->cor ?: '#6b7280',
            'ordem' => (int) ($request->ordem ?? 0),
        ]);
        return redirect()->route('configuracoes-sistema.plugins.show', 'atendimento')
            ->with('success', 'Prioridade atualizada.');
    }

    public function destroyPrioridade($id)
    {
        $prioridade = PrioridadeAtendimento::findOrFail($id);
        if ($prioridade->atendimentos()->exists()) {
            return redirect()->route('configuracoes-sistema.plugins.show', 'atendimento')
                ->with('error', 'Não é possível excluir: existem atendimentos com esta prioridade.');
        }
        $prioridade->delete();
        return redirect()->route('configuracoes-sistema.plugins.show', 'atendimento')
            ->with('success', 'Prioridade excluída.');
    }

    // ---------- Tempos estimados ----------

    public function storeTempo(Request $request)
    {
        $request->validate([
            'label' => 'required|string|max:80',
            'minutos' => 'required|integer|min:1|max:999',
            'ordem' => 'nullable|integer|min:0',
        ]);
        TempoEstimadoAtendimento::create([
            'label' => $request->label,
            'minutos' => (int) $request->minutos,
            'ordem' => (int) ($request->ordem ?? 0),
        ]);
        return redirect()->route('configuracoes-sistema.plugins.show', 'atendimento')
            ->with('success', 'Tempo estimado adicionado.');
    }

    public function updateTempo(Request $request, $id)
    {
        $tempo = TempoEstimadoAtendimento::findOrFail($id);
        $request->validate([
            'label' => 'required|string|max:80',
            'minutos' => 'required|integer|min:1|max:999',
            'ordem' => 'nullable|integer|min:0',
        ]);
        $tempo->update([
            'label' => $request->label,
            'minutos' => (int) $request->minutos,
            'ordem' => (int) ($request->ordem ?? 0),
        ]);
        return redirect()->route('configuracoes-sistema.plugins.show', 'atendimento')
            ->with('success', 'Tempo estimado atualizado.');
    }

    public function destroyTempo($id)
    {
        $tempo = TempoEstimadoAtendimento::findOrFail($id);
        if (Atendimento\Models\Atendimento::where('tempo_estimado_minutos', $tempo->minutos)->exists()) {
            return redirect()->route('configuracoes-sistema.plugins.show', 'atendimento')
                ->with('error', 'Não é possível excluir: existem atendimentos com este tempo estimado.');
        }
        $tempo->delete();
        return redirect()->route('configuracoes-sistema.plugins.show', 'atendimento')
            ->with('success', 'Tempo estimado excluído.');
    }

    /**
     * Salva configuração do Google Maps (Rota do dia): ativar/desativar e chave API.
     */
    public function updateMaps(Request $request)
    {
        $request->validate([
            'maps_enabled' => 'nullable|in:0,1',
            'google_maps_api_key' => 'nullable|string|max:500',
        ]);

        $enabled = $request->input('maps_enabled', '0') === '1';
        Configuracao::setValue('plugin_atendimento_maps_enabled', $enabled ? '1' : '0');

        if ($request->filled('google_maps_api_key')) {
            Configuracao::setValue('plugin_atendimento_google_maps_api_key', $request->google_maps_api_key);
        }

        return redirect()->route('configuracoes-sistema.plugins.show', 'atendimento')
            ->with('success', 'Configuração do Maps salva.');
    }
}
