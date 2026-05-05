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

namespace AreaCliente;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Storage;

class AdminChamadoController extends Controller
{
    protected function ensureAdminAccess(): void
    {
        if (!auth()->check()) {
            abort(403, 'Acesso restrito.');
        }
        $user = auth()->user();
        if ($user->cliente_id) {
            $cliente = \App\Models\Cliente::find($user->cliente_id);
            if ($cliente && ($cliente->portal_suporte ?? false)) {
                abort(403, 'Usuários do portal não acessam o painel de chamados.');
            }
        }
        if (!method_exists($user, 'canAccessOperational') || !$user->canAccessOperational()) {
            if (empty($user->is_admin)) {
                abort(403, 'Sem permissão para gerenciar chamados.');
            }
        }
    }

    public function index(Request $request)
    {
        $this->ensureAdminAccess();

        $query = Chamado::with(['cliente', 'user', 'anexos', 'ordemServico'])
            ->orderByRaw("CASE status WHEN 'aberto' THEN 0 WHEN 'em_atendimento' THEN 1 WHEN 'aguardando_cliente' THEN 2 WHEN 'resolvido' THEN 3 WHEN 'cancelado' THEN 4 ELSE 5 END")
            ->orderByDesc('created_at');

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }
        if ($request->filled('prioridade')) {
            $query->where('prioridade', $request->prioridade);
        }
        if ($request->filled('cliente_id')) {
            $query->where('cliente_id', $request->cliente_id);
        }
        if ($request->filled('busca')) {
            $busca = $request->busca;
            $query->where(function ($q) use ($busca) {
                $q->where('titulo', 'like', "%{$busca}%")
                    ->orWhere('descricao', 'like', "%{$busca}%")
                    ->orWhere('id', '=', is_numeric($busca) ? (int) $busca : 0);
            });
        }

        $chamados = $query->paginate(20)->withQueryString();
        $clientes = \App\Models\Cliente::orderBy('nome')->get();

        return view('area-cliente::admin.chamados.index', [
            'chamados' => $chamados,
            'clientes' => $clientes,
            'title' => 'Chamados (Admin)',
        ]);
    }

    public function show(int $chamado)
    {
        $this->ensureAdminAccess();

        $model = Chamado::with(['cliente', 'user', 'anexos', 'respostas.user', 'respostas.anexos', 'ordemServico'])->findOrFail($chamado);
        $equipamentos = $model->cliente
            ? \App\Models\Equipamento::where('cliente_id', $model->cliente_id)->orderBy('marca')->orderBy('modelo')->get()
            : collect();

        return view('area-cliente::admin.chamados.show', [
            'chamado' => $model,
            'equipamentos' => $equipamentos,
            'title' => 'Chamado #' . $model->id,
        ]);
    }

    public function update(Request $request, int $chamado)
    {
        $this->ensureAdminAccess();

        $model = Chamado::findOrFail($chamado);

        $validated = $request->validate([
            'status' => 'required|in:' . implode(',', Chamado::STATUS_OPERADOR),
        ]);

        $model->status = $validated['status'];
        $model->save();

        return redirect()
            ->route('plugin.area-cliente.admin.chamados.show', $model)
            ->with('success', 'Status atualizado.');
    }

    public function storeResposta(Request $request, int $chamado)
    {
        $this->ensureAdminAccess();

        $chamadoModel = Chamado::findOrFail($chamado);

        $request->validate([
            'mensagem' => 'nullable|string|max:50000',
            'interno' => 'boolean',
            'anexos' => 'nullable|array',
            'anexos.*' => 'file|mimes:jpeg,jpg,png,gif,webp,pdf|max:10240',
        ], ['anexos.*.max' => 'Cada anexo não pode ter mais de 10 MB.']);

        $mensagem = trim((string) $request->input('mensagem', ''));
        $files = $request->file('anexos', []);
        $hasFiles = is_array($files) && count(array_filter($files)) > 0;
        if ($mensagem === '' && !$hasFiles) {
            return redirect()
                ->route('plugin.area-cliente.admin.chamados.show', $chamadoModel)
                ->withErrors(['mensagem' => 'Envie uma mensagem e/ou pelo menos um anexo.'])
                ->withInput();
        }

        $r = new ChamadoResposta();
        $r->chamado_id = $chamadoModel->id;
        $r->user_id = auth()->id();
        $r->mensagem = $mensagem !== '' ? $mensagem : '—';
        $r->interno = !empty($request->boolean('interno'));
        $r->save();

        $dir = 'plugin_area_cliente/chamados/' . $chamadoModel->id;
        if ($hasFiles) {
            foreach ($files as $file) {
                if (!$file || !$file->isValid()) {
                    continue;
                }
                $path = $file->store($dir, 'local');
                $anexo = new ChamadoAnexo();
                $anexo->chamado_id = $chamadoModel->id;
                $anexo->chamado_resposta_id = $r->id;
                $anexo->path = $path;
                $anexo->nome_original = $file->getClientOriginalName();
                $anexo->save();
            }
        }

        $chamadoModel->touch();

        $msg = $hasFiles && $mensagem !== '' ? 'Resposta e anexo(s) adicionados.' : ($hasFiles ? 'Anexo(s) adicionado(s).' : 'Resposta adicionada.');
        return redirect()
            ->route('plugin.area-cliente.admin.chamados.show', $chamadoModel)
            ->with('success', $msg);
    }

    public function downloadAnexo(int $chamado, int $anexo)
    {
        $this->ensureAdminAccess();

        $chamadoModel = Chamado::findOrFail($chamado);
        $anexoModel = ChamadoAnexo::where('chamado_id', $chamadoModel->id)->findOrFail($anexo);

        if (!Storage::disk('local')->exists($anexoModel->path)) {
            abort(404, 'Arquivo não encontrado.');
        }

        return Storage::disk('local')->download(
            $anexoModel->path,
            $anexoModel->nome_original ?: 'anexo'
        );
    }

    public function storeAnexo(Request $request, int $chamado)
    {
        $this->ensureAdminAccess();

        $chamadoModel = Chamado::findOrFail($chamado);

        $request->validate([
            'anexos' => 'required|array',
            'anexos.*' => 'required|file|mimes:jpeg,jpg,png,gif,webp,pdf|max:10240',
        ], [
            'anexos.required' => 'Selecione ao menos um arquivo.',
            'anexos.*.max' => 'Cada anexo não pode ter mais de 10 MB.',
        ]);

        $dir = 'plugin_area_cliente/chamados/' . $chamadoModel->id;
        $count = 0;
        foreach ($request->file('anexos') as $file) {
            if (!$file->isValid()) {
                continue;
            }
            $path = $file->store($dir, 'local');
            $anexo = new ChamadoAnexo();
            $anexo->chamado_id = $chamadoModel->id;
            $anexo->path = $path;
            $anexo->nome_original = $file->getClientOriginalName();
            $anexo->save();
            $count++;
        }

        $msg = $count === 1 ? 'Anexo adicionado.' : $count . ' anexos adicionados.';
        return redirect()
            ->route('plugin.area-cliente.admin.chamados.show', $chamadoModel)
            ->with('success', $msg);
    }

    /** Converter chamado em Ordem de Serviço (sistema principal). */
    public function convertToOs(Request $request, int $chamado)
    {
        $this->ensureAdminAccess();

        $chamadoModel = Chamado::with('cliente')->findOrFail($chamado);
        if ($chamadoModel->ordem_servico_id) {
            return redirect()
                ->route('plugin.area-cliente.admin.chamados.show', $chamadoModel)
                ->with('error', 'Este chamado já foi convertido em ordem de serviço.');
        }
        if (!$chamadoModel->cliente) {
            return redirect()->back()->with('error', 'Cliente não encontrado.');
        }

        $validated = $request->validate([
            'equipamento_id' => 'nullable|exists:equipamentos,id',
            'equipamento_novo' => 'boolean',
            'marca' => 'required_if:equipamento_novo,1|nullable|string|max:100',
            'modelo' => 'required_if:equipamento_novo,1|nullable|string|max:100',
            'numero_serie' => 'nullable|string|max:100',
            'numero_patrimonio' => 'nullable|string|max:100',
            'acessorios' => 'nullable|string|max:500',
        ]);

        $equipamentoId = null;
        if (!empty($validated['equipamento_novo'])) {
            $eq = new \App\Models\Equipamento();
            $eq->cliente_id = $chamadoModel->cliente_id;
            $eq->marca = $validated['marca'] ?? '';
            $eq->modelo = $validated['modelo'] ?? '';
            $eq->numero_serie = $validated['numero_serie'] ?? null;
            $eq->numero_patrimonio = $validated['numero_patrimonio'] ?? null;
            $eq->acessorios = $validated['acessorios'] ?? null;
            $eq->save();
            $equipamentoId = $eq->id;
        } else {
            $equipamentoId = $validated['equipamento_id'] ?? null;
        }

        $relato = '<p><strong>Chamado #' . $chamadoModel->id . ' – ' . e($chamadoModel->titulo) . '</strong></p>';
        if ($chamadoModel->descricao) {
            $relato .= $chamadoModel->descricao;
        }

        $tenantId = class_exists(\App\Models\Tenant::class) ? \App\Models\Tenant::first()?->id : null;
        $osData = [
            'tenant_id' => $tenantId,
            'cliente_id' => $chamadoModel->cliente_id,
            'cliente_unidade_id' => null,
            'contato_id' => null,
            'endereco_id' => null,
            'equipamento_id' => $equipamentoId,
            'relato_cliente' => $relato,
            'status' => 'Aberta',
            'data_abertura' => now(),
            'prioridade' => $chamadoModel->prioridade === 'urgente' ? 'Alta' : ($chamadoModel->prioridade === 'alta' ? 'Alta' : 'Normal'),
            'created_by' => auth()->id(),
            'updated_by' => auth()->id(),
        ];

        if ($equipamentoId) {
            $equip = \App\Models\Equipamento::find($equipamentoId);
            if ($equip) {
                $osData['equipamento_marca'] = $equip->marca;
                $osData['equipamento_modelo'] = $equip->modelo;
                $osData['equipamento_numero_serie'] = $equip->numero_serie;
                $osData['equipamento_numero_patrimonio'] = $equip->numero_patrimonio;
                $osData['equipamento_acessorios'] = $equip->acessorios;
            }
        }

        $os = \App\Models\OrdemServico::create($osData);
        $chamadoModel->ordem_servico_id = $os->id;
        $chamadoModel->save();

        return redirect()
            ->route('plugin.area-cliente.admin.chamados.show', $chamadoModel)
            ->with('success', 'Ordem de serviço ' . ($os->codigo_interno ?? '#' . $os->id) . ' criada e vinculada ao chamado.');
    }
}
