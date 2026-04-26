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

use Atendimento\Models\Atendimento;
use Atendimento\Models\AtendimentoFoto;
use Atendimento\Models\PrioridadeAtendimento;
use Atendimento\Models\StatusAtendimento;
use Atendimento\Models\TempoEstimadoAtendimento;
use Atendimento\Services\RotaDiaDistanceService;
use Atendimento\Services\NominatimGeocodeService;
use Atendimento\Services\AttendanceService;
use App\Models\Cliente;
use App\Models\Configuracao;
use App\Models\Empresa;
use App\Models\Endereco;
use App\Models\OrdemServico;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

class AtendimentoController
{
    /** Salva assinatura do cliente (base64 data URL ou arquivo) no storage e define assinatura_cliente_path. Define assinatura_data (agora) e assinatura_local (vindo do request, preenchido via GPS no front). */
    protected static function processAssinaturaUpload(Request $request, Atendimento $atendimento): void
    {
        $path = null;
        $dataUrl = $request->input('assinatura_cliente_base64');
        if ($dataUrl && preg_match('#^data:image/(\w+);base64,(.+)$#', $dataUrl, $m)) {
            $ext = $m[1] === 'png' ? 'png' : 'jpg';
            $dir = 'atendimento_assinaturas/' . $atendimento->id;
            Storage::disk('local')->put($dir . '/assinatura.' . $ext, base64_decode($m[2]));
            $path = $dir . '/assinatura.' . $ext;
        } elseif ($request->hasFile('assinatura_cliente')) {
            $file = $request->file('assinatura_cliente');
            $dir = 'atendimento_assinaturas/' . $atendimento->id;
            $path = $file->storeAs($dir, 'assinatura.' . $file->getClientOriginalExtension(), 'local');
        }
        if ($path) {
            $local = $request->input('assinatura_local');
            $local = is_string($local) ? trim(mb_substr($local, 0, 255)) : null;
            $atendimento->update([
                'assinatura_cliente_path' => $path,
                'assinatura_data' => now(),
                'assinatura_local' => $local ?: null,
            ]);
        }
    }

    /** Processa upload de fotos (fotos[], fotos_tipo[], fotos_legenda[]) e cria AtendimentoFoto. */
    protected static function processFotosUpload(Request $request, Atendimento $atendimento): void
    {
        $files = $request->file('fotos');
        if (! is_array($files)) {
            return;
        }
        $tipos = $request->input('fotos_tipo', []);
        $legendas = $request->input('fotos_legenda', []);
        $ordem = 0;
        foreach ($files as $i => $file) {
            if (! $file || ! $file->isValid()) {
                continue;
            }
            $tipo = isset($tipos[$i]) && in_array($tipos[$i], [AtendimentoFoto::TIPO_ANTES, AtendimentoFoto::TIPO_DEPOIS, AtendimentoFoto::TIPO_EVIDENCIA], true)
                ? $tipos[$i] : AtendimentoFoto::TIPO_EVIDENCIA;
            $legenda = isset($legendas[$i]) ? (string) $legendas[$i] : null;
            $dir = 'atendimento_fotos/' . $atendimento->id;
            $path = $file->store($dir, 'local');
            if ($path) {
                AtendimentoFoto::create([
                    'atendimento_id' => $atendimento->id,
                    'tipo' => $tipo,
                    'legenda' => $legenda,
                    'caminho' => $path,
                    'ordem' => $ordem++,
                ]);
            }
        }
    }

    /**
     * Serve a imagem de uma foto do atendimento (arquivo em storage local/private).
     */
    public function servirFoto($foto)
    {
        $foto = AtendimentoFoto::findOrFail($foto);
        $path = $foto->caminho;
        if (! $path || ! Storage::disk('local')->exists($path)) {
            abort(404);
        }
        $mime = $this->mimeFromPath($path);
        return response()->file(Storage::disk('local')->path($path), [
            'Content-Type' => $mime,
            'Content-Disposition' => 'inline; filename="' . basename($path) . '"',
        ]);
    }

    /**
     * Serve a assinatura do cliente de um atendimento (arquivo em storage local/private).
     */
    public function servirAssinatura($atendimento)
    {
        $atendimento = Atendimento::findOrFail($atendimento);
        $path = $atendimento->assinatura_cliente_path;
        if (! $path || ! Storage::disk('local')->exists($path)) {
            abort(404);
        }
        $mime = $this->mimeFromPath($path);
        return response()->file(Storage::disk('local')->path($path), [
            'Content-Type' => $mime,
            'Content-Disposition' => 'inline; filename="assinatura.' . pathinfo($path, PATHINFO_EXTENSION) . '"',
        ]);
    }

    private function mimeFromPath($path): string
    {
        $ext = strtolower(pathinfo($path, PATHINFO_EXTENSION));
        $map = ['jpg' => 'image/jpeg', 'jpeg' => 'image/jpeg', 'png' => 'image/png', 'gif' => 'image/gif', 'webp' => 'image/webp'];
        return $map[$ext] ?? 'application/octet-stream';
    }

    /** Retorna checklist models do sistema principal (quando existir). Isolado: não quebra se tabela não existir. */
    protected static function getChecklistModels()
    {
        if (! \Illuminate\Support\Facades\Schema::hasTable('checklist_models')) {
            return collect();
        }
        if (! class_exists(\App\Models\ChecklistModel::class)) {
            return collect();
        }
        return \App\Models\ChecklistModel::where('ativo', true)->orderBy('nome')->get();
    }

    public function index(Request $request)
    {
        $query = Atendimento::with(['cliente.enderecos', 'cliente.contatos', 'status', 'prioridade', 'endereco', 'tecnico'])
            ->orderByDesc('data_hora_atendimento');

        if ($request->filled('cliente_id')) {
            $query->where('cliente_id', $request->cliente_id);
        }
        if ($request->filled('status_id')) {
            $query->where('status_id', $request->status_id);
        }
        if ($request->filled('prioridade_id')) {
            $query->where('prioridade_id', $request->prioridade_id);
        }
        if ($request->filled('data_inicio')) {
            $query->whereDate('data_hora_atendimento', '>=', $request->data_inicio);
        }
        if ($request->filled('data_fim')) {
            $query->whereDate('data_hora_atendimento', '<=', $request->data_fim);
        }
        if ($request->filled('busca')) {
            $busca = '%' . $request->busca . '%';
            $query->where(function ($q) use ($busca) {
                $q->where('problema_reportado', 'like', $busca)
                    ->orWhere('diagnostico_tecnico', 'like', $busca)
                    ->orWhere('endereco_completo', 'like', $busca)
                    ->orWhereHas('cliente', fn ($c) => $c->where('nome', 'like', $busca));
            });
        }

        $atendimentos = $query->paginate(20)->withQueryString();
        $statusList = StatusAtendimento::orderBy('ordem')->orderBy('nome')->get();
        $prioridadesList = PrioridadeAtendimento::orderBy('ordem')->orderBy('nome')->get();
        $clientes = Cliente::orderBy('nome')->get(['id', 'nome']);

        return view('atendimento::index', [
            'atendimentos' => $atendimentos,
            'statusList' => $statusList,
            'prioridadesList' => $prioridadesList,
            'clientes' => $clientes,
        ]);
    }

    public function create()
    {
        $clientes = Cliente::with('enderecos')->orderBy('nome')->get(['id', 'nome']);
        $statusList = StatusAtendimento::orderBy('ordem')->orderBy('nome')->get();
        $prioridadesList = PrioridadeAtendimento::orderBy('ordem')->orderBy('nome')->get();
        $temposEstimadosList = TempoEstimadoAtendimento::orderBy('ordem')->orderBy('minutos')->get();
        $defaultMinutos = $temposEstimadosList->first()?->minutos ?? 60;
        $tecnicos = User::orderBy('name')->get(['id', 'name']);

        return view('atendimento::create', [
            'atendimento' => new Atendimento([
                'data_hora_atendimento' => now()->addHour()->startOfHour(),
                'endereco_tipo' => Atendimento::ENDERECO_USAR_CADASTRADO,
                'tempo_estimado_minutos' => $defaultMinutos,
                'fase' => Atendimento::FASE_RASCUNHO,
            ]),
            'clientes' => $clientes,
            'statusList' => $statusList,
            'prioridadesList' => $prioridadesList,
            'temposEstimadosList' => $temposEstimadosList,
            'tecnicos' => $tecnicos,
            'enderecosPorCliente' => [],
        ]);
    }

    public function store(Request $request)
    {
        $minutosValidos = TempoEstimadoAtendimento::pluck('minutos')->toArray();
        $request->validate([
            'cliente_id' => 'required|integer|exists:clientes,id',
            'data_hora_atendimento' => 'required|date',
            'tempo_estimado_minutos' => ['nullable', 'integer', Rule::in($minutosValidos)],
            'endereco_tipo' => 'required|in:usar_cadastrado,endereco_cliente,novo',
            'endereco_id' => 'nullable|required_if:endereco_tipo,endereco_cliente|integer|exists:enderecos,id',
            'endereco_completo' => 'nullable|string|max:1000',
            'endereco_cep' => 'nullable|string|max:20',
            'endereco_logradouro' => 'nullable|string|max:255',
            'endereco_numero' => 'nullable|string|max:30',
            'endereco_complemento' => 'nullable|string|max:100',
            'endereco_bairro' => 'nullable|string|max:100',
            'endereco_cidade' => 'nullable|string|max:100',
            'endereco_uf' => 'nullable|string|max:5',
            'problema_reportado' => 'nullable|string|max:5000',
            'diagnostico_tecnico' => 'nullable|string|max:5000',
            'observacoes_tecnicas' => 'nullable|string|max:5000',
            'fase' => 'nullable|in:rascunho,concluido',
            'checklist_model_id' => 'nullable|integer',
            'status_id' => 'required|integer|exists:plugin_atendimento_status,id',
            'prioridade_id' => 'required|integer|exists:plugin_atendimento_prioridades,id',
            'tecnico_id' => 'nullable|integer|exists:users,id',
        ]);

        $data = $request->only([
            'cliente_id', 'data_hora_atendimento', 'tempo_estimado_minutos',
            'endereco_tipo', 'endereco_id', 'endereco_completo',
            'endereco_cep', 'endereco_logradouro', 'endereco_numero',
            'endereco_complemento', 'endereco_bairro', 'endereco_cidade', 'endereco_uf',
            'problema_reportado', 'diagnostico_tecnico', 'observacoes_tecnicas', 'fase', 'checklist_model_id',
            'status_id', 'prioridade_id', 'tecnico_id',
        ]);
        $data['created_by'] = auth()->id();
        $data['fase'] = $data['fase'] ?? Atendimento::FASE_RASCUNHO;
        $data['checklist_respostas'] = $request->input('checklist_respostas') ?: null;
        $data['checklist_campos_adhoc'] = $request->input('checklist_campos_adhoc') ?: null;
        if (is_string($data['checklist_respostas'])) {
            $decoded = json_decode($data['checklist_respostas'], true);
            $data['checklist_respostas'] = is_array($decoded) ? $decoded : null;
        }
        if (is_string($data['checklist_campos_adhoc'])) {
            $decoded = json_decode($data['checklist_campos_adhoc'], true);
            $data['checklist_campos_adhoc'] = is_array($decoded) ? $decoded : null;
        }
        if (empty($data['checklist_model_id'])) {
            $data['checklist_model_id'] = null;
        }
        if ($data['endereco_tipo'] !== 'endereco_cliente') {
            $data['endereco_id'] = null;
        }
        if ($data['endereco_tipo'] !== 'novo') {
            $data['endereco_completo'] = $data['endereco_cep'] = $data['endereco_logradouro'] = $data['endereco_numero'] = $data['endereco_complemento'] = $data['endereco_bairro'] = $data['endereco_cidade'] = $data['endereco_uf'] = null;
        }

        $atendimento = Atendimento::create($data);

        return redirect()->route('plugin.atendimento.show', $atendimento)
            ->with('success', 'Atendimento cadastrado com sucesso.');
    }

    public function show($id)
    {
        $atendimento = Atendimento::with(['fotos', 'checklistModel'])->findOrFail($id);
        $atendimento->load(['cliente.enderecos', 'status', 'prioridade', 'endereco', 'tecnico', 'createdBy']);
        return view('atendimento::show', ['atendimento' => $atendimento]);
    }

    public function edit($id)
    {
        $atendimento = Atendimento::with('fotos')->findOrFail($id);
        $clientes = Cliente::with('enderecos')->orderBy('nome')->get(['id', 'nome']);
        $statusList = StatusAtendimento::orderBy('ordem')->orderBy('nome')->get();
        $prioridadesList = PrioridadeAtendimento::orderBy('ordem')->orderBy('nome')->get();
        $temposEstimadosList = TempoEstimadoAtendimento::orderBy('ordem')->orderBy('minutos')->get();
        $enderecosPorCliente = [];
        if ($atendimento->cliente_id) {
            $enderecosPorCliente[$atendimento->cliente_id] = Endereco::where('cliente_id', $atendimento->cliente_id)->orderByDesc('principal')->orderBy('id')->get();
        }
        $tecnicos = User::orderBy('name')->get(['id', 'name']);

        return view('atendimento::edit', [
            'atendimento' => $atendimento,
            'clientes' => $clientes,
            'statusList' => $statusList,
            'prioridadesList' => $prioridadesList,
            'temposEstimadosList' => $temposEstimadosList,
            'tecnicos' => $tecnicos,
            'enderecosPorCliente' => $enderecosPorCliente,
            'checklistModels' => self::getChecklistModels(),
        ]);
    }

    public function update(Request $request, $id)
    {
        $atendimento = Atendimento::findOrFail($id);
        $minutosValidos = TempoEstimadoAtendimento::pluck('minutos')->toArray();
        $request->validate([
            'cliente_id' => 'required|integer|exists:clientes,id',
            'data_hora_atendimento' => 'required|date',
            'tempo_estimado_minutos' => ['nullable', 'integer', Rule::in($minutosValidos)],
            'endereco_tipo' => 'required|in:usar_cadastrado,endereco_cliente,novo',
            'endereco_id' => 'nullable|required_if:endereco_tipo,endereco_cliente|integer|exists:enderecos,id',
            'endereco_completo' => 'nullable|string|max:1000',
            'endereco_cep' => 'nullable|string|max:20',
            'endereco_logradouro' => 'nullable|string|max:255',
            'endereco_numero' => 'nullable|string|max:30',
            'endereco_complemento' => 'nullable|string|max:100',
            'endereco_bairro' => 'nullable|string|max:100',
            'endereco_cidade' => 'nullable|string|max:100',
            'endereco_uf' => 'nullable|string|max:5',
            'problema_reportado' => 'nullable|string|max:5000',
            'diagnostico_tecnico' => 'nullable|string|max:5000',
            'observacoes_tecnicas' => 'nullable|string|max:5000',
            'fase' => 'nullable|in:rascunho,concluido',
            'checklist_model_id' => 'nullable|integer',
            'status_id' => 'required|integer|exists:plugin_atendimento_status,id',
            'prioridade_id' => 'required|integer|exists:plugin_atendimento_prioridades,id',
            'tecnico_id' => 'nullable|integer|exists:users,id',
        ]);

        $data = $request->only([
            'cliente_id', 'data_hora_atendimento', 'tempo_estimado_minutos',
            'endereco_tipo', 'endereco_id', 'endereco_completo',
            'endereco_cep', 'endereco_logradouro', 'endereco_numero',
            'endereco_complemento', 'endereco_bairro', 'endereco_cidade', 'endereco_uf',
            'problema_reportado', 'diagnostico_tecnico', 'observacoes_tecnicas', 'fase', 'checklist_model_id',
            'status_id', 'prioridade_id', 'tecnico_id',
        ]);
        $data['fase'] = $data['fase'] ?? Atendimento::FASE_RASCUNHO;
        $data['checklist_respostas'] = $request->input('checklist_respostas') ?: null;
        $data['checklist_campos_adhoc'] = $request->input('checklist_campos_adhoc') ?: null;
        if (is_string($data['checklist_respostas'])) {
            $decoded = json_decode($data['checklist_respostas'], true);
            $data['checklist_respostas'] = is_array($decoded) ? $decoded : null;
        }
        if (is_string($data['checklist_campos_adhoc'])) {
            $decoded = json_decode($data['checklist_campos_adhoc'], true);
            $data['checklist_campos_adhoc'] = is_array($decoded) ? $decoded : null;
        }
        if (empty($data['checklist_model_id'])) {
            $data['checklist_model_id'] = null;
        }
        if ($data['endereco_tipo'] !== 'endereco_cliente') {
            $data['endereco_id'] = null;
        }
        if ($data['endereco_tipo'] !== 'novo') {
            $data['endereco_completo'] = $data['endereco_cep'] = $data['endereco_logradouro'] = $data['endereco_numero'] = $data['endereco_complemento'] = $data['endereco_bairro'] = $data['endereco_cidade'] = $data['endereco_uf'] = null;
        }

        $atendimento->update($data);

        self::processAssinaturaUpload($request, $atendimento);
        self::processFotosUpload($request, $atendimento);

        return redirect()->route('plugin.atendimento.show', $atendimento)
            ->with('success', 'Atendimento atualizado.');
    }

    /**
     * Exclui o atendimento.
     */
    public function destroy($id)
    {
        $atendimento = Atendimento::findOrFail($id);
        $atendimento->delete();
        return redirect()->route('plugin.atendimento.index')
            ->with('success', 'Atendimento excluído.');
    }

    /**
     * Rota do dia: atendimentos agrupados por técnico para uma data.
     */
    public function rotaDia(Request $request)
    {
        $data = $request->input('data', now()->format('Y-m-d'));
        $tecnicoIdFiltro = $request->input('tecnico_id');
        $tecnicosParaFiltro = User::where('is_admin', false)
            ->whereHas('roles')
            ->orderBy('name')
            ->get(['id', 'name']);

        $query = Atendimento::with(['cliente.enderecos', 'tecnico', 'status', 'prioridade', 'endereco'])
            ->whereDate('data_hora_atendimento', $data);
        if ($tecnicoIdFiltro !== null && $tecnicoIdFiltro !== '') {
            $query->where('tecnico_id', $tecnicoIdFiltro);
        }
        $atendimentos = $query->orderBy('tecnico_id')->orderBy('data_hora_atendimento')->get();
        $porTecnico = $atendimentos->groupBy('tecnico_id');
        $tecnicos = User::orderBy('name')->get(['id', 'name'])->keyBy('id');

        $segmentosPorTecnico = [];
        $distanceService = new RotaDiaDistanceService();
        foreach ($porTecnico as $tecnicoId => $lista) {
            $listaOrdenada = $lista->sortBy('data_hora_atendimento')->values();
            $enderecos = $listaOrdenada->map(fn ($a) => $a->endereco_para_mapa)->filter()->values()->all();
            $segmentosPorTecnico[$tecnicoId] = $distanceService->segmentosEntreEnderecos($enderecos);
        }

        return view('atendimento::rota-do-dia', [
            'data' => $data,
            'tecnicoIdFiltro' => $tecnicoIdFiltro,
            'tecnicosParaFiltro' => $tecnicosParaFiltro,
            'porTecnico' => $porTecnico,
            'tecnicos' => $tecnicos,
            'atendimentos' => $atendimentos,
            'segmentosPorTecnico' => $segmentosPorTecnico,
        ]);
    }

    /**
     * Exporta a rota do dia em CSV (por técnico).
     */
    public function rotaDiaExportCsv(Request $request)
    {
        $data = $request->input('data', now()->format('Y-m-d'));
        $tecnicoIdFiltro = $request->input('tecnico_id');
        $query = Atendimento::with(['cliente.enderecos', 'tecnico', 'status', 'prioridade', 'endereco'])
            ->whereDate('data_hora_atendimento', $data);
        if ($tecnicoIdFiltro !== null && $tecnicoIdFiltro !== '') {
            $query->where('tecnico_id', $tecnicoIdFiltro);
        }
        $atendimentos = $query->orderBy('tecnico_id')->orderBy('data_hora_atendimento')->get();
        $porTecnico = $atendimentos->groupBy('tecnico_id');
        $tecnicos = User::orderBy('name')->get(['id', 'name'])->keyBy('id');

        $distanceService = new RotaDiaDistanceService();
        $segmentosPorTecnico = [];
        foreach ($porTecnico as $tecnicoId => $lista) {
            $listaOrdenada = $lista->sortBy('data_hora_atendimento')->values();
            $enderecos = $listaOrdenada->map(fn ($a) => $a->endereco_para_mapa)->filter()->values()->all();
            $segmentosPorTecnico[$tecnicoId] = $distanceService->segmentosEntreEnderecos($enderecos);
        }

        $handle = fopen('php://temp', 'r+');
        fputcsv($handle, [
            'Data',
            'Hora',
            'Técnico',
            'Cliente',
            'Endereço',
            'Status',
            'Prioridade',
            'Distância do trecho anterior',
            'Tempo do trecho anterior',
        ], ';');

        foreach ($porTecnico as $tecnicoId => $lista) {
            $listaOrdenada = $lista->sortBy('data_hora_atendimento')->values();
            $segmentos = $segmentosPorTecnico[$tecnicoId] ?? [];
            $tecnicoNome = $tecnicoId ? ($tecnicos->get($tecnicoId)->name ?? 'Sem técnico') : 'Sem técnico';

            foreach ($listaOrdenada as $index => $a) {
                $seg = $index > 0 && isset($segmentos[$index - 1]) ? $segmentos[$index - 1] : ['distance' => '', 'duration' => ''];
                fputcsv($handle, [
                    $a->data_hora_atendimento?->format('Y-m-d'),
                    $a->data_hora_atendimento?->format('H:i'),
                    $tecnicoNome,
                    $a->cliente->nome ?? '',
                    $a->endereco_para_mapa,
                    $a->status->nome ?? '',
                    $a->prioridade->nome ?? '',
                    $seg['distance'],
                    $seg['duration'],
                ], ';');
            }
        }

        rewind($handle);
        $csv = stream_get_contents($handle);
        fclose($handle);

        $filename = 'rota-do-dia-' . $data;
        if ($tecnicoIdFiltro && $tecnicos->has($tecnicoIdFiltro)) {
            $slug = \Illuminate\Support\Str::slug($tecnicos->get($tecnicoIdFiltro)->name);
            $filename .= '-' . $slug;
        }
        $filename .= '.csv';

        return response($csv, 200, [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="'.$filename.'"',
        ]);
    }

    /**
     * Exporta a rota do dia em PDF.
     */
    public function rotaDiaExportPdf(Request $request)
    {
        $data = $request->input('data', now()->format('Y-m-d'));
        $tecnicoIdFiltro = $request->input('tecnico_id');
        $query = Atendimento::with(['cliente.enderecos', 'tecnico', 'status', 'prioridade', 'endereco'])
            ->whereDate('data_hora_atendimento', $data);
        if ($tecnicoIdFiltro !== null && $tecnicoIdFiltro !== '') {
            $query->where('tecnico_id', $tecnicoIdFiltro);
        }
        $atendimentos = $query->orderBy('tecnico_id')->orderBy('data_hora_atendimento')->get();
        $porTecnico = $atendimentos->groupBy('tecnico_id');
        $tecnicos = User::orderBy('name')->get(['id', 'name'])->keyBy('id');

        $distanceService = new RotaDiaDistanceService();
        $segmentosPorTecnico = [];
        foreach ($porTecnico as $tecnicoId => $lista) {
            $listaOrdenada = $lista->sortBy('data_hora_atendimento')->values();
            $enderecos = $listaOrdenada->map(fn ($a) => $a->endereco_para_mapa)->filter()->values()->all();
            $segmentosPorTecnico[$tecnicoId] = $distanceService->segmentosEntreEnderecos($enderecos);
        }

        $html = view('atendimento::rota-do-dia-pdf', [
            'data' => $data,
            'tecnicoIdFiltro' => $tecnicoIdFiltro,
            'porTecnico' => $porTecnico,
            'tecnicos' => $tecnicos,
            'segmentosPorTecnico' => $segmentosPorTecnico,
            'empresa' => class_exists(Empresa::class) ? Empresa::first() : null,
            'userName' => auth()->user() ? auth()->user()->name : 'Sistema',
        ])->render();
        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadHTML($html)
            ->setPaper('A4', 'portrait')
            ->setOption('margin-top', '10mm')
            ->setOption('margin-bottom', '10mm')
            ->setOption('margin-left', '10mm')
            ->setOption('margin-right', '10mm');
        $filename = 'rota-do-dia-' . $data;
        if ($tecnicoIdFiltro && $tecnicos->has($tecnicoIdFiltro)) {
            $slug = \Illuminate\Support\Str::slug($tecnicos->get($tecnicoIdFiltro)->name);
            $filename .= '-' . $slug;
        }
        $filename .= '.pdf';
        return $pdf->download($filename);
    }

    /**
     * Página com o mapa da rota do dia (pontos marcados).
     */
    public function rotaDiaMapa(Request $request)
    {
        $data = $request->input('data', now()->format('Y-m-d'));
        $tecnicoIdFiltro = $request->input('tecnico_id');
        $query = Atendimento::with(['cliente.enderecos', 'tecnico', 'status', 'prioridade', 'endereco'])
            ->whereDate('data_hora_atendimento', $data);
        if ($tecnicoIdFiltro !== null && $tecnicoIdFiltro !== '') {
            $query->where('tecnico_id', $tecnicoIdFiltro);
        }
        $atendimentos = $query->orderBy('tecnico_id')->orderBy('data_hora_atendimento')->get();

        $pontos = $atendimentos->filter(fn ($a) => $a->endereco_para_mapa !== '')
            ->map(function ($a) {
                return [
                    'endereco' => $a->endereco_para_mapa,
                    'cliente' => $a->cliente->nome ?? '',
                    'tecnico' => $a->tecnico->name ?? '',
                    'hora' => $a->data_hora_atendimento?->format('H:i'),
                    'status' => $a->status->nome ?? '',
                    'prioridade' => $a->prioridade->nome ?? '',
                    'maps_url' => $a->google_maps_url,
                ];
            })->values();

        $apiKey = Configuracao::getValue('plugin_atendimento_google_maps_api_key', '') ?: null;
        if (! $apiKey) {
            $apiKey = config('services.google_maps.api_key') ?: env('GOOGLE_MAPS_API_KEY');
        }

        if (! $apiKey) {
            $pontos = $pontos->map(function ($ponto) {
                $coords = NominatimGeocodeService::geocode($ponto['endereco'] ?? '');
                $ponto['lat'] = $coords['lat'] ?? null;
                $ponto['lng'] = $coords['lng'] ?? null;
                return $ponto;
            })->values();
        }

        return view('atendimento::rota-do-dia-mapa', [
            'data' => $data,
            'pontos' => $pontos->values()->toArray(),
            'googleMapsApiKey' => $apiKey,
        ]);
    }

    /**
     * Exporta a rota do mês em CSV (técnico obrigatório).
     */
    public function rotaMesExportCsv(Request $request)
    {
        $mes = $request->input('mes', now()->format('Y-m'));
        $tecnicoId = $request->input('tecnico_id');
        if (! $tecnicoId) {
            return redirect()->route('plugin.atendimento.rota-do-dia', ['data' => $mes . '-01'])
                ->with('error', 'Selecione um técnico para exportar a rota do mês.');
        }
        $tecnico = User::find($tecnicoId);
        if (! $tecnico) {
            return redirect()->route('plugin.atendimento.rota-do-dia')->with('error', 'Técnico não encontrado.');
        }
        [$ano, $mesNum] = explode('-', $mes);
        $atendimentos = Atendimento::with(['cliente.enderecos', 'tecnico', 'status', 'prioridade', 'endereco'])
            ->whereYear('data_hora_atendimento', $ano)
            ->whereMonth('data_hora_atendimento', $mesNum)
            ->where('tecnico_id', $tecnicoId)
            ->orderBy('data_hora_atendimento')
            ->get();
        $tecnicos = User::orderBy('name')->get(['id', 'name'])->keyBy('id');
        $tecnicoNome = $tecnicos->get($tecnicoId)->name ?? 'Sem técnico';

        $handle = fopen('php://temp', 'r+');
        fputcsv($handle, [
            'Data',
            'Hora',
            'Técnico',
            'Cliente',
            'Endereço',
            'Status',
            'Prioridade',
        ], ';');
        foreach ($atendimentos as $a) {
            fputcsv($handle, [
                $a->data_hora_atendimento?->format('Y-m-d'),
                $a->data_hora_atendimento?->format('H:i'),
                $tecnicoNome,
                $a->cliente->nome ?? '',
                $a->endereco_para_mapa,
                $a->status->nome ?? '',
                $a->prioridade->nome ?? '',
            ], ';');
        }
        rewind($handle);
        $csv = stream_get_contents($handle);
        fclose($handle);
        $slug = \Illuminate\Support\Str::slug($tecnicoNome);
        $filename = 'rota-do-mes-' . $mes . '-' . $slug . '.csv';
        return response($csv, 200, [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ]);
    }

    /**
     * Exporta a rota do mês em PDF (técnico obrigatório).
     */
    public function rotaMesExportPdf(Request $request)
    {
        $mes = $request->input('mes', now()->format('Y-m'));
        $tecnicoId = $request->input('tecnico_id');
        if (! $tecnicoId) {
            return redirect()->route('plugin.atendimento.rota-do-dia', ['data' => $mes . '-01'])
                ->with('error', 'Selecione um técnico para exportar a rota do mês.');
        }
        $tecnico = User::find($tecnicoId);
        if (! $tecnico) {
            return redirect()->route('plugin.atendimento.rota-do-dia')->with('error', 'Técnico não encontrado.');
        }
        [$ano, $mesNum] = explode('-', $mes);
        $atendimentos = Atendimento::with(['cliente.enderecos', 'tecnico', 'status', 'prioridade', 'endereco'])
            ->whereYear('data_hora_atendimento', $ano)
            ->whereMonth('data_hora_atendimento', $mesNum)
            ->where('tecnico_id', $tecnicoId)
            ->orderBy('data_hora_atendimento')
            ->get();
        $porDia = $atendimentos->groupBy(fn ($a) => $a->data_hora_atendimento->format('Y-m-d'));
        $tecnicos = User::orderBy('name')->get(['id', 'name'])->keyBy('id');
        $tecnicoNome = $tecnicos->get($tecnicoId)->name ?? 'Sem técnico';

        $html = view('atendimento::rota-do-mes-pdf', [
            'mes' => $mes,
            'mesLabel' => \Carbon\Carbon::createFromFormat('Y-m', $mes)->locale('pt_BR')->translatedFormat('F Y'),
            'tecnicoNome' => $tecnicoNome,
            'porDia' => $porDia,
            'empresa' => class_exists(Empresa::class) ? Empresa::first() : null,
            'userName' => auth()->user() ? auth()->user()->name : 'Sistema',
        ])->render();
        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadHTML($html)
            ->setPaper('A4', 'portrait')
            ->setOption('margin-top', '10mm')
            ->setOption('margin-bottom', '10mm')
            ->setOption('margin-left', '10mm')
            ->setOption('margin-right', '10mm');
        $slug = \Illuminate\Support\Str::slug($tecnicoNome);
        $filename = 'rota-do-mes-' . $mes . '-' . $slug . '.pdf';
        return $pdf->download($filename);
    }

    /**
     * Converte o atendimento em uma Ordem de Serviço no sistema principal e redireciona para ela.
     */
    public function converterParaOs($id)
    {
        $atendimento = Atendimento::findOrFail($id);
        if ($atendimento->ordem_servico_id) {
            return redirect()->route('ordens-servico.show', $atendimento->ordem_servico_id)
                ->with('info', 'Este atendimento já foi convertido em OS.');
        }

        try {
            $os = AttendanceService::convertToServiceOrder((int) $id);
        } catch (\Throwable $e) {
            return redirect()->back()->with('error', 'Erro ao converter: ' . $e->getMessage());
        }

        return redirect()->route('ordens-servico.show', $os)
            ->with('success', 'Atendimento convertido em Ordem de Serviço.');
    }
}
