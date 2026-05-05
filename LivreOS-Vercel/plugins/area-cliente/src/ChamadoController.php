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
use Illuminate\Support\Facades\Storage;
use Illuminate\Routing\Controller;

class ChamadoController extends Controller
{
    protected function getClienteId(): ?int
    {
        return auth()->user()->cliente_id ?? null;
    }

    protected function ensurePortalAccess(): \App\Models\Cliente
    {
        $clienteId = $this->getClienteId();
        if (!$clienteId) {
            abort(403, 'Acesso restrito à área do cliente.');
        }
        $cliente = \App\Models\Cliente::find($clienteId);
        if (!$cliente || !($cliente->portal_suporte ?? false)) {
            abort(403, 'O portal do cliente não está habilitado para sua conta.');
        }
        return $cliente;
    }

    protected function getClienteIds(): array
    {
        $clienteId = $this->getClienteId();
        if (!$clienteId) {
            return [];
        }
        $cliente = \App\Models\Cliente::find($clienteId);
        if (!$cliente) {
            return [$clienteId];
        }
        $ids = [$clienteId];
        if ($cliente->grupo_economico_id) {
            $unidades = \App\Models\Cliente::where('grupo_economico_id', $cliente->grupo_economico_id)->pluck('id')->toArray();
            $ids = array_unique(array_merge($ids, $unidades));
        }
        $filiais = \App\Models\Cliente::where('matriz_id', $clienteId)->pluck('id')->toArray();
        return array_unique(array_merge($ids, $filiais));
    }

    public function index(Request $request)
    {
        $cliente = $this->ensurePortalAccess();
        $ids = $this->getClienteIds();

        $query = Chamado::with(['anexos', 'user'])
            ->whereIn('cliente_id', $ids);

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }
        if ($request->filled('prioridade')) {
            $query->where('prioridade', $request->prioridade);
        }
        if ($request->filled('busca')) {
            $busca = $request->busca;
            $query->where(function ($q) use ($busca) {
                $q->where('titulo', 'like', "%{$busca}%")
                    ->orWhere('descricao', 'like', "%{$busca}%");
            });
        }

        $chamados = $query->orderByDesc('updated_at')->paginate(15)->withQueryString();

        $baseQuery = Chamado::whereIn('cliente_id', $ids);
        $contagemStatus = [
            'abertos' => (clone $baseQuery)->whereIn('status', ['aberto', 'em_atendimento', 'aguardando_cliente', 'aguardando_confirmacao_cliente'])->count(),
            'encerrados' => (clone $baseQuery)->whereIn('status', \AreaCliente\Chamado::STATUS_ENCERRADOS)->count(),
        ];

        return view('area-cliente::chamados.index', [
            'chamados' => $chamados,
            'cliente' => $cliente,
            'contagemStatus' => $contagemStatus,
            'title' => 'Meus Tickets',
        ]);
    }

    public function create()
    {
        $cliente = $this->ensurePortalAccess();
        return view('area-cliente::chamados.create', [
            'cliente' => $cliente,
            'title' => 'Abrir Chamado',
        ]);
    }

    public function store(Request $request)
    {
        $cliente = $this->ensurePortalAccess();
        $ids = $this->getClienteIds();
        if (!in_array($cliente->id, $ids)) {
            abort(403);
        }

        $validated = $request->validate([
            'titulo' => 'required|string|max:255',
            'descricao' => 'nullable|string|max:50000',
            'prioridade' => 'required|in:baixa,media,alta,urgente',
            'anexos' => 'nullable|array',
            'anexos.*' => 'file|mimes:jpeg,jpg,png,gif,webp,pdf|max:10240',
        ], [
            'titulo.required' => 'Informe o assunto do chamado.',
            'prioridade.required' => 'Selecione a prioridade.',
            'anexos.*.max' => 'Cada anexo não pode ter mais de 10 MB.',
        ]);

        $chamado = new Chamado();
        $chamado->cliente_id = $cliente->id;
        $chamado->user_id = auth()->id();
        $chamado->titulo = $validated['titulo'];
        $chamado->descricao = $validated['descricao'] ?? null;
        $chamado->prioridade = $validated['prioridade'];
        $chamado->status = 'aberto';
        $chamado->save();

        if ($request->hasFile('anexos')) {
            $dir = 'plugin_area_cliente/chamados/' . $chamado->id;
            foreach ($request->file('anexos') as $file) {
                if (!$file->isValid()) {
                    continue;
                }
                $path = $file->store($dir, 'local');
                $anexo = new ChamadoAnexo();
                $anexo->chamado_id = $chamado->id;
                $anexo->path = $path;
                $anexo->nome_original = $file->getClientOriginalName();
                $anexo->save();
            }
        }

        return redirect()
            ->route('plugin.area-cliente.chamados.show', $chamado)
            ->with('success', 'Chamado #' . $chamado->id . ' aberto com sucesso. Em breve nossa equipe entrará em contato.');
    }

    public function show(int $chamado)
    {
        $cliente = $this->ensurePortalAccess();
        $ids = $this->getClienteIds();
        $model = Chamado::whereIn('cliente_id', $ids)->findOrFail($chamado);
        $model->load('anexos', 'user', 'respostas.user', 'respostas.anexos');
        return view('area-cliente::chamados.show', [
            'chamado' => $model,
            'cliente' => $cliente,
            'title' => 'Chamado #' . $model->id,
        ]);
    }

    public function downloadAnexo(int $chamado, int $anexo)
    {
        $cliente = $this->ensurePortalAccess();
        $ids = $this->getClienteIds();
        $chamadoModel = Chamado::whereIn('cliente_id', $ids)->findOrFail($chamado);
        $anexoModel = ChamadoAnexo::where('chamado_id', $chamadoModel->id)->findOrFail($anexo);
        if (!Storage::disk('local')->exists($anexoModel->path)) {
            abort(404, 'Arquivo não encontrado.');
        }
        return Storage::disk('local')->download(
            $anexoModel->path,
            $anexoModel->nome_original ?: 'anexo'
        );
    }

    /** Fechar chamado (cliente): marcar como resolvido ou cancelado (legado). */
    public function fechar(Request $request, int $chamado)
    {
        $this->ensurePortalAccess();
        $ids = $this->getClienteIds();
        $chamadoModel = Chamado::whereIn('cliente_id', $ids)->findOrFail($chamado);

        if (in_array($chamadoModel->status, Chamado::STATUS_ENCERRADOS, true)) {
            return redirect()
                ->route('plugin.area-cliente.chamados.show', $chamadoModel)
                ->with('success', 'Este chamado já está fechado.');
        }

        $request->validate([
            'status' => 'required|in:resolvido,cancelado',
        ], [
            'status.required' => 'Escolha como deseja fechar o chamado.',
        ]);

        $chamadoModel->status = $request->input('status');
        $chamadoModel->save();

        $msg = $chamadoModel->status === 'resolvido'
            ? 'Chamado marcado como resolvido.'
            : 'Chamado cancelado.';
        return redirect()
            ->route('plugin.area-cliente.chamados.show', $chamadoModel)
            ->with('success', $msg);
    }

    /** Cliente confirma que a solução foi atendida (dispara fluxo NPS). */
    public function confirmarSolucao(Request $request, int $chamado)
    {
        $this->ensurePortalAccess();
        $ids = $this->getClienteIds();
        $chamadoModel = Chamado::whereIn('cliente_id', $ids)->findOrFail($chamado);

        if (!in_array($chamadoModel->status, Chamado::STATUS_AGUARDANDO_CONFIRMACAO, true)) {
            return redirect()
                ->route('plugin.area-cliente.chamados.show', $chamadoModel)
                ->with('error', 'Esta ação só é válida quando o chamado está aguardando sua confirmação.');
        }

        $chamadoModel->status = 'resolvido_confirmado';
        $chamadoModel->save();

        return redirect()
            ->route('plugin.area-cliente.chamados.show', $chamadoModel)
            ->with('success', 'Obrigado por confirmar! Sua avaliação nos ajuda a melhorar. Em breve você poderá receber uma pesquisa de satisfação (NPS).');
    }

    /** Cliente reabre o chamado (ex.: problema voltou). */
    public function reabrir(Request $request, int $chamado)
    {
        $this->ensurePortalAccess();
        $ids = $this->getClienteIds();
        $chamadoModel = Chamado::whereIn('cliente_id', $ids)->findOrFail($chamado);

        if (!in_array($chamadoModel->status, Chamado::STATUS_AGUARDANDO_CONFIRMACAO, true)) {
            return redirect()
                ->route('plugin.area-cliente.chamados.show', $chamadoModel)
                ->with('error', 'Esta ação só é válida quando o chamado está aguardando sua confirmação.');
        }

        $chamadoModel->status = 'em_atendimento';
        $chamadoModel->save();

        return redirect()
            ->route('plugin.area-cliente.chamados.show', $chamadoModel)
            ->with('success', 'Chamado reaberto. A equipe dará sequência ao atendimento.');
    }

    /** Cliente desiste da solicitação (ex.: resolveu sozinho). */
    public function desistir(Request $request, int $chamado)
    {
        $this->ensurePortalAccess();
        $ids = $this->getClienteIds();
        $chamadoModel = Chamado::whereIn('cliente_id', $ids)->findOrFail($chamado);

        if (!in_array($chamadoModel->status, Chamado::STATUS_PODE_DESISTIR, true)) {
            return redirect()
                ->route('plugin.area-cliente.chamados.show', $chamadoModel)
                ->with('error', 'Não é possível desistir neste estado do chamado.');
        }

        $chamadoModel->status = 'desistencia_cliente';
        $chamadoModel->save();

        return redirect()
            ->route('plugin.area-cliente.chamados.show', $chamadoModel)
            ->with('success', 'Chamado encerrado por desistência. Se precisar novamente, abra um novo chamado.');
    }

    /** Resposta do cliente ao chamado (mensagem e/ou anexos juntos, sempre pública). */
    public function storeResposta(Request $request, int $chamado)
    {
        $this->ensurePortalAccess();
        $ids = $this->getClienteIds();
        $chamadoModel = Chamado::whereIn('cliente_id', $ids)->findOrFail($chamado);

        $request->validate([
            'mensagem' => 'nullable|string|max:50000',
            'anexos' => 'nullable|array',
            'anexos.*' => 'file|mimes:jpeg,jpg,png,gif,webp,pdf|max:10240',
        ], [
            'anexos.*.max' => 'Cada anexo não pode ter mais de 10 MB.',
        ]);

        $mensagem = trim((string) $request->input('mensagem', ''));
        $files = $request->file('anexos', []);
        $hasFiles = is_array($files) && count(array_filter($files)) > 0;
        if ($mensagem === '' && !$hasFiles) {
            return redirect()
                ->route('plugin.area-cliente.chamados.show', $chamadoModel)
                ->withErrors(['mensagem' => 'Envie uma mensagem e/ou pelo menos um anexo.'])
                ->withInput();
        }

        $r = new ChamadoResposta();
        $r->chamado_id = $chamadoModel->id;
        $r->user_id = auth()->id();
        $r->mensagem = $mensagem !== '' ? $mensagem : '—';
        $r->interno = false;
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

        $msg = $hasFiles && $mensagem !== '' ? 'Mensagem e anexo(s) enviados.' : ($hasFiles ? 'Anexo(s) enviado(s).' : 'Sua resposta foi enviada.');
        return redirect()
            ->route('plugin.area-cliente.chamados.show', $chamadoModel)
            ->with('success', $msg);
    }

    /** Adicionar apenas anexo(s) sem mensagem (portal) – anexos ficam soltos no histórico. */
    public function storeAnexo(Request $request, int $chamado)
    {
        $this->ensurePortalAccess();
        $ids = $this->getClienteIds();
        $chamadoModel = Chamado::whereIn('cliente_id', $ids)->findOrFail($chamado);

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

        $chamadoModel->touch();

        $msg = $count === 1 ? 'Anexo adicionado.' : $count . ' anexos adicionados.';
        return redirect()
            ->route('plugin.area-cliente.chamados.show', $chamadoModel)
            ->with('success', $msg);
    }
}
