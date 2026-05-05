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

use App\Http\Requests\ServicoRequest;
use App\Models\CategoriaServico;
use App\Models\Servico;
use App\Models\ServicoImagem;
use App\Services\AuditCancelExcluirService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ServicoController extends Controller
{
    public function index(Request $request)
    {
        $this->authorizePermission('view-services');
        $query = Servico::query()->with('categoria');

        if ($request->filled('nome')) {
            $query->where('nome', 'like', '%' . $request->nome . '%');
        }

        if ($request->filled('codigo_sku')) {
            $query->where('codigo_sku', 'like', '%' . $request->codigo_sku . '%');
        }

        if ($request->filled('categoria_id')) {
            $query->where('categoria_id', $request->categoria_id);
        }

        $ordenarPor = $request->input('ordenar_por', 'nome');
        $direcao = $request->input('direcao', 'asc') === 'desc' ? 'desc' : 'asc';
        $permitidos = ['nome', 'codigo_sku', 'preco', 'updated_at'];
        if (!in_array($ordenarPor, $permitidos, true)) {
            $ordenarPor = 'nome';
        }

        $porPagina = (int) $request->input('por_pagina', 15);
        if (!in_array($porPagina, [15, 30, 50, 100], true)) {
            $porPagina = 15;
        }

        $query = $this->applyEntityQuery($query->orderBy($ordenarPor, $direcao), 'servico');
        $servicos = $query->paginate($porPagina)->withQueryString();

        return erp_view('servicos.index', [
            'title' => 'Cadastro de Serviços',
            'servicos' => $servicos,
            'categorias' => CategoriaServico::orderBy('nome')->get(),
        ]);
    }

    protected function queryServicosComFiltros(Request $request)
    {
        $query = Servico::query()->with('categoria');
        if ($request->filled('nome')) $query->where('nome', 'like', '%' . $request->nome . '%');
        if ($request->filled('codigo_sku')) $query->where('codigo_sku', 'like', '%' . $request->codigo_sku . '%');
        if ($request->filled('categoria_id')) $query->where('categoria_id', $request->categoria_id);
        $ordenarPor = in_array($request->input('ordenar_por'), ['nome', 'codigo_sku', 'preco', 'updated_at'], true) ? $request->input('ordenar_por') : 'nome';
        $direcao = $request->input('direcao') === 'desc' ? 'desc' : 'asc';
        return $this->applyEntityQuery($query->orderBy($ordenarPor, $direcao), 'servico');
    }

    public function export(Request $request): StreamedResponse
    {
        $this->authorizePermission('view-services');
        $servicos = $this->queryServicosComFiltros($request)->limit(10000)->get();
        $filename = 'servicos_' . now()->format('Y-m-d_His') . '.csv';
        $headers = ['Content-Type' => 'text/csv; charset=UTF-8', 'Content-Disposition' => 'attachment; filename="' . $filename . '"'];
        return response()->streamDownload(function () use ($servicos) {
            $out = fopen('php://output', 'w');
            fprintf($out, chr(0xEF) . chr(0xBB) . chr(0xBF));
            fputcsv($out, ['Código', 'Nome', 'SKU', 'Unidade', 'Preço', 'Categoria'], ';');
            foreach ($servicos as $s) {
                fputcsv($out, [
                    $s->codigo_interno ?? '',
                    $s->nome,
                    $s->codigo_sku ?? '',
                    $s->unidade ?? '',
                    $s->preco ? number_format($s->preco, 2, ',', '.') : '',
                    $s->categoria?->nome ?? '',
                ], ';');
            }
            fclose($out);
        }, $filename, $headers);
    }

    public function exportPdf(Request $request)
    {
        $this->authorizePermission('view-services');
        $servicos = $this->queryServicosComFiltros($request)->limit(500)->get();
        $html = view('servicos.pdf', ['servicos' => $servicos])->render();
        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadHTML($html);
        $pdf->setPaper('A4', 'landscape');
        $pdf->setOption('margin-top', '10mm');
        $pdf->setOption('margin-bottom', '10mm');
        $pdf->setOption('margin-left', '10mm');
        $pdf->setOption('margin-right', '10mm');
        return $pdf->download('servicos_' . now()->format('Y-m-d_His') . '.pdf');
    }

    public function create()
    {
        $this->authorizePermission('create-services');
        return erp_view('servicos.create', [
            'title' => 'Novo Serviço',
            'categorias' => CategoriaServico::where('ativo', true)->orderBy('nome')->get(),
        ]);
    }

    public function store(ServicoRequest $request)
    {
        $this->authorizePermission('create-services');
        $data = $request->validated();
        if (($data['unidade'] ?? null) === 'outro') {
            $data['unidade'] = $data['unidade_outro'] ?? $data['unidade'];
        }
        $data['informacoes_fiscais'] = $this->normalizarFiscais($request->input('informacoes_fiscais', []));
        $data['campos_customizados'] = $this->normalizarCamposCustomizados($request->input('campos_customizados', []));
        $data['created_by'] = auth()->id();
        $data['updated_by'] = auth()->id();

        $servico = Servico::create($data);
        $this->processarImagens($servico, $request);

        return redirect()->route('servicos.index')
            ->with('success', 'Serviço cadastrado com sucesso!');
    }

    public function imagem(Request $request, ServicoImagem $servicoImagem)
    {
        if ($servicoImagem->tipo === 'url' && $servicoImagem->url_externa) {
            return redirect($servicoImagem->url_externa);
        }
        if ($servicoImagem->caminho_local && Storage::disk('public')->exists($servicoImagem->caminho_local)) {
            $path = Storage::disk('public')->path($servicoImagem->caminho_local);
            $headers = [
                'Content-Type' => mime_content_type($path) ?: 'image/jpeg',
            ];
            if ($request->boolean('download')) {
                $headers['Content-Disposition'] = 'attachment; filename="' . basename($servicoImagem->caminho_local) . '"';
            }
            return response()->file($path, $headers);
        }
        abort(404);
    }

    public function show(Servico $servico)
    {
        $this->authorizePermission('view-services');
        $servico->load(['categoria', 'imagens']);

        return erp_view('servicos.show', [
            'title' => 'Detalhes do Serviço',
            'servico' => $servico,
        ]);
    }

    public function edit(Servico $servico)
    {
        $this->authorizePermission('edit-services');
        $servico->load(['categoria', 'imagens']);

        return erp_view('servicos.edit', [
            'title' => 'Editar Serviço',
            'servico' => $servico,
            'categorias' => CategoriaServico::where('ativo', true)->orderBy('nome')->get(),
        ]);
    }

    public function update(ServicoRequest $request, Servico $servico)
    {
        $this->authorizePermission('edit-services');
        $data = $request->validated();
        if (($data['unidade'] ?? null) === 'outro') {
            $data['unidade'] = $data['unidade_outro'] ?? $data['unidade'];
        }
        $data['informacoes_fiscais'] = $this->normalizarFiscais($request->input('informacoes_fiscais', []));
        $data['campos_customizados'] = $this->normalizarCamposCustomizados($request->input('campos_customizados', []));
        $data['updated_by'] = auth()->id();

        $servico->update($data);
        $this->processarImagens($servico, $request);

        return redirect()->route('servicos.index')
            ->with('success', 'Serviço atualizado com sucesso!');
    }

    /**
     * Exclui vários serviços (permissão delete-services + auditoria; respeita entity.index.query).
     */
    public function bulkDestroy(Request $request, AuditCancelExcluirService $auditService)
    {
        $this->authorizePermission('delete-services');
        if (! $auditService->canExcluir(auth()->user(), 'servico')) {
            abort(403, 'Você não tem permissão para excluir serviços.');
        }

        $csv = (string) $request->input('servico_ids_csv', '');
        $ids = array_values(array_unique(array_filter(
            array_map(static fn ($v) => (int) trim((string) $v), explode(',', $csv)),
            static fn ($v) => $v > 0
        )));

        $request->merge(['servico_ids' => $ids]);

        $this->validateWithFilters($request, [
            'servico_ids' => 'required|array|min:1',
            'servico_ids.*' => 'integer|distinct|exists:servicos,id',
            'motivo' => 'required|string|min:10',
        ], [], ['motivo' => 'motivo da exclusão']);

        $query = Servico::query()
            ->with('imagens')
            ->whereIn('id', $request->input('servico_ids', []));
        $query = $this->applyEntityQuery($query, 'servico');
        $servicos = $query->get();

        $solicitados = count($request->input('servico_ids', []));
        if ($servicos->isEmpty()) {
            return redirect()->back()->with('error', 'Nenhum dos serviços selecionados pode ser excluído (sem permissão de acesso ou registros inválidos).');
        }

        DB::transaction(function () use ($servicos, $request, $auditService): void {
            foreach ($servicos as $servico) {
                $this->excluirServicoComAuditoria($servico, (string) $request->input('motivo'), $request, $auditService);
            }
        });

        $msg = $servicos->count() === 1
            ? '1 serviço excluído com sucesso.'
            : $servicos->count().' serviços excluídos com sucesso.';
        if ($servicos->count() < $solicitados) {
            $msg .= ' '.($solicitados - $servicos->count()).' não estavam ao seu alcance e foram ignorados.';
        }

        return redirect()->route('servicos.index')->with('success', $msg);
    }

    public function destroy(Request $request, Servico $servico, AuditCancelExcluirService $auditService)
    {
        $this->authorizePermission('delete-services');
        if (! $auditService->canExcluir(auth()->user(), 'servico')) {
            abort(403, 'Você não tem permissão para excluir serviços.');
        }

        $this->validateWithFilters($request, [
            'motivo' => 'required|string|min:10',
        ], [], ['motivo' => 'motivo da exclusão']);

        $servico->loadMissing('imagens');
        $this->excluirServicoComAuditoria($servico, (string) $request->input('motivo'), $request, $auditService);

        return redirect()->route('servicos.index')
            ->with('success', 'Serviço excluído com sucesso!');
    }

    protected function excluirServicoComAuditoria(
        Servico $servico,
        string $motivo,
        Request $request,
        AuditCancelExcluirService $auditService
    ): void {
        $descricao = $servico->nome ?? 'Serviço #'.$servico->id;
        $entityId = $servico->id;

        foreach ($servico->imagens as $imagem) {
            if ($imagem->caminho_local && Storage::disk('public')->exists($imagem->caminho_local)) {
                Storage::disk('public')->delete($imagem->caminho_local);
            }
        }

        $servico->delete();

        $auditService->log('excluir', 'servico', $entityId, $descricao, $motivo, $request);
    }

    private function normalizarFiscais(array $dados): array
    {
        return array_values(array_filter(array_map('trim', $dados)));
    }

    private function normalizarCamposCustomizados(array $dados): array
    {
        $limpos = [];
        foreach ($dados as $item) {
            $chave = trim($item['chave'] ?? '');
            $valor = trim($item['valor'] ?? '');
            if ($chave === '' && $valor === '') {
                continue;
            }
            $limpos[] = ['chave' => $chave, 'valor' => $valor];
        }
        return $limpos;
    }

    private function processarImagens(Servico $servico, Request $request): void
    {
        $tipos = $request->input('imagens_tipo', []);
        $urls = $request->input('imagens_url', []);
        $ordens = $request->input('imagens_ordem', []);
        $arquivos = $request->file('imagens_arquivo', []);
        $removerIds = array_filter((array) $request->input('imagens_remover', []));

        if (!empty($removerIds)) {
            $imagensRemover = $servico->imagens()->whereIn('id', $removerIds)->get();
            foreach ($imagensRemover as $imagem) {
                if ($imagem->caminho_local && Storage::disk('public')->exists($imagem->caminho_local)) {
                    Storage::disk('public')->delete($imagem->caminho_local);
                }
                $imagem->delete();
            }
        }

        $hasInput = false;
        foreach ($tipos as $tipo) {
            if ($tipo) {
                $hasInput = true;
                break;
            }
        }
        if (!$hasInput && empty(array_filter($urls)) && empty(array_filter($arquivos))) {
            return;
        }

        $max = max(count($tipos), count($urls), count($arquivos));
        for ($i = 0; $i < $max; $i++) {
            $tipo = $tipos[$i] ?? 'upload';
            $url = $urls[$i] ?? null;
            $ordem = $ordens[$i] ?? 0;
            $arquivo = $arquivos[$i] ?? null;

            if ($tipo === 'url' && !$url) {
                continue;
            }
            if ($tipo === 'upload' && !$arquivo) {
                continue;
            }

            $caminho = null;
            if ($tipo === 'upload' && $arquivo && $arquivo->isValid()) {
                $nome = Str::slug(pathinfo($arquivo->getClientOriginalName(), PATHINFO_FILENAME))
                    . '_' . time() . '_' . $i . '.' . $arquivo->getClientOriginalExtension();
                $caminho = $arquivo->storeAs('servicos/' . $servico->id, $nome, 'public');
            }

            ServicoImagem::create([
                'servico_id' => $servico->id,
                'tipo' => $tipo,
                'caminho_local' => $caminho,
                'url_externa' => $tipo === 'url' ? $url : null,
                'ordem' => (int) $ordem,
            ]);
        }
    }
}
