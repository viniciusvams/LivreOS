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

use App\Http\Requests\ProdutoRequest;
use App\Models\CategoriaProduto;
use App\Models\Contato;
use App\Models\Deposito;
use App\Models\Produto;
use App\Models\ProdutoComposicao;
use App\Models\ProdutoFornecedor;
use App\Models\ProdutoImagem;
use App\Models\ProdutoVariacao;
use App\Models\ProdutoVariacaoAtributo;
use App\Models\TabelaPreco;
use App\Services\AuditCancelExcluirService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ProdutoController extends Controller
{
    public function index(Request $request)
    {
        $this->authorizePermission('view-products');
        $query = Produto::query()->with(['categoria', 'deposito']);

        if ($request->filled('nome')) {
            $query->where('nome', 'like', '%' . $request->nome . '%');
        }

        if ($request->filled('codigo_sku')) {
            $query->where('codigo_sku', 'like', '%' . $request->codigo_sku . '%');
        }

        if ($request->filled('categoria_id')) {
            $query->where('categoria_id', $request->categoria_id);
        }

        if ($request->filled('formato')) {
            $query->where('formato', $request->formato);
        }

        if ($request->filled('preco_min')) {
            $query->where('preco_venda', '>=', $request->preco_min);
        }

        if ($request->filled('preco_max')) {
            $query->where('preco_venda', '<=', $request->preco_max);
        }

        if ($request->filled('estoque_status')) {
            if ($request->estoque_status === 'baixo') {
                $query->whereNotNull('estoque_minimo')
                    ->whereColumn('estoque_quantidade', '<=', 'estoque_minimo');
            }
            if ($request->estoque_status === 'zerado') {
                $query->where('estoque_quantidade', '<=', 0);
            }
            if ($request->estoque_status === 'ok') {
                $query->where(function ($sub) {
                    $sub->whereNull('estoque_minimo')
                        ->orWhereColumn('estoque_quantidade', '>', 'estoque_minimo');
                });
            }
        }

        $ordenarPor = $request->input('ordenar_por', 'nome');
        $direcao = $request->input('direcao', 'asc');
        $permitidos = ['nome', 'codigo_sku', 'preco_venda', 'estoque_quantidade', 'updated_at'];
        if (!in_array($ordenarPor, $permitidos, true)) {
            $ordenarPor = 'nome';
        }
        $direcao = $direcao === 'desc' ? 'desc' : 'asc';
        $query->orderBy($ordenarPor, $direcao);

        $porPagina = (int) $request->input('por_pagina', 15);
        if (!in_array($porPagina, [15, 30, 50, 100], true)) {
            $porPagina = 15;
        }

        $query = $this->applyEntityQuery($query, 'produto');
        $produtos = $query->paginate($porPagina)->withQueryString();

        return erp_view('produtos.index', [
            'title' => 'Cadastro de Produtos',
            'produtos' => $produtos,
            'categorias' => CategoriaProduto::orderBy('nome')->get(),
        ]);
    }

    protected function queryProdutosComFiltros(Request $request)
    {
        $query = Produto::query()->with(['categoria', 'deposito']);
        if ($request->filled('nome')) $query->where('nome', 'like', '%' . $request->nome . '%');
        if ($request->filled('codigo_sku')) $query->where('codigo_sku', 'like', '%' . $request->codigo_sku . '%');
        if ($request->filled('categoria_id')) $query->where('categoria_id', $request->categoria_id);
        if ($request->filled('formato')) $query->where('formato', $request->formato);
        if ($request->filled('preco_min')) $query->where('preco_venda', '>=', $request->preco_min);
        if ($request->filled('preco_max')) $query->where('preco_venda', '<=', $request->preco_max);
        if ($request->filled('estoque_status')) {
            if ($request->estoque_status === 'baixo') $query->whereNotNull('estoque_minimo')->whereColumn('estoque_quantidade', '<=', 'estoque_minimo');
            if ($request->estoque_status === 'zerado') $query->where('estoque_quantidade', '<=', 0);
            if ($request->estoque_status === 'ok') $query->where(fn ($sub) => $sub->whereNull('estoque_minimo')->orWhereColumn('estoque_quantidade', '>', 'estoque_minimo'));
        }
        $ordenarPor = in_array($request->input('ordenar_por'), ['nome', 'codigo_sku', 'preco_venda', 'estoque_quantidade', 'updated_at'], true) ? $request->input('ordenar_por') : 'nome';
        $direcao = $request->input('direcao') === 'desc' ? 'desc' : 'asc';
        return $this->applyEntityQuery($query->orderBy($ordenarPor, $direcao), 'produto');
    }

    public function export(Request $request): StreamedResponse
    {
        $this->authorizePermission('view-products');
        $produtos = $this->queryProdutosComFiltros($request)->limit(10000)->get();
        $filename = 'produtos_' . now()->format('Y-m-d_His') . '.csv';
        $headers = ['Content-Type' => 'text/csv; charset=UTF-8', 'Content-Disposition' => 'attachment; filename="' . $filename . '"'];
        return response()->streamDownload(function () use ($produtos) {
            $out = fopen('php://output', 'w');
            fprintf($out, chr(0xEF) . chr(0xBB) . chr(0xBF));
            fputcsv($out, ['Nome', 'SKU', 'Formato', 'Unidade', 'Preço', 'Estoque', 'Depósito', 'Categoria'], ';');
            foreach ($produtos as $p) {
                fputcsv($out, [
                    $p->nome,
                    $p->codigo_sku ?? '',
                    $p->formato ?? 'simples',
                    $p->unidade ?? '',
                    $p->preco_venda ? number_format($p->preco_venda, 2, ',', '.') : '',
                    $p->estoque_quantidade ?? '',
                    $p->deposito?->nome ?? '',
                    $p->categoria?->nome ?? '',
                ], ';');
            }
            fclose($out);
        }, $filename, $headers);
    }

    public function exportPdf(Request $request)
    {
        $this->authorizePermission('view-products');
        $produtos = $this->queryProdutosComFiltros($request)->limit(500)->get();
        $html = view('produtos.pdf', ['produtos' => $produtos])->render();
        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadHTML($html);
        $pdf->setPaper('A4', 'landscape');
        $pdf->setOption('margin-top', '10mm');
        $pdf->setOption('margin-bottom', '10mm');
        $pdf->setOption('margin-left', '10mm');
        $pdf->setOption('margin-right', '10mm');
        return $pdf->download('produtos_' . now()->format('Y-m-d_His') . '.pdf');
    }

    public function create()
    {
        $this->authorizePermission('create-products');
        return erp_view('produtos.create', [
            'title' => 'Novo Produto',
            'categorias' => CategoriaProduto::orderBy('nome')->get(),
            'depositos' => Deposito::orderBy('nome')->get(),
            'tabelasPrecos' => TabelaPreco::orderBy('nome')->get(),
            'fornecedores' => Contato::with('cliente')->orderBy('nome')->get(),
            'produtos' => Produto::orderBy('nome')->get(),
        ]);
    }

    public function store(ProdutoRequest $request)
    {
        $this->authorizePermission('create-products');
        $this->normalizarValoresBr($request);
        $data = $request->validated();
        $this->validarVariacoes($request, null);
        $data['formato'] = $request->input('formato');
        $data['tags'] = $this->normalizarTags($request->input('tags'));
        $data['created_by'] = auth()->id();
        $data['updated_by'] = auth()->id();

        if (!isset($data['estoque_preco_custo_unitario']) && isset($data['estoque_preco_compra_unitario'])) {
            $data['estoque_preco_custo_unitario'] = $data['estoque_preco_compra_unitario'];
        }

        $produto = Produto::create($data);

        $this->syncTabelasPrecos($produto, $request->input('tabelas_precos', []));
        $this->processarImagens($produto, $request);
        $this->processarFornecedores($produto, $request->input('fornecedores', []));
        $this->processarVariacoes($produto, $request);
        $this->processarComposicoes($produto, $request->input('composicoes', []));

        return redirect()->route('produtos.index')
            ->with('success', 'Produto cadastrado com sucesso!');
    }

    public function imagem(Request $request, ProdutoImagem $produtoImagem)
    {
        if ($produtoImagem->tipo === 'url' && $produtoImagem->url_externa) {
            return redirect($produtoImagem->url_externa);
        }
        if ($produtoImagem->caminho_local && Storage::disk('public')->exists($produtoImagem->caminho_local)) {
            $path = Storage::disk('public')->path($produtoImagem->caminho_local);
            $headers = [
                'Content-Type' => mime_content_type($path) ?: 'image/jpeg',
            ];
            if ($request->boolean('download')) {
                $headers['Content-Disposition'] = 'attachment; filename="' . basename($produtoImagem->caminho_local) . '"';
            }
            return response()->file($path, $headers);
        }
        abort(404);
    }

    public function show(Produto $produto)
    {
        $this->authorizePermission('view-products');
        $produto->load([
            'categoria',
            'deposito',
            'imagens',
            'fornecedores.fornecedor',
            'variacaoAtributos',
            'variacoes',
            'composicoes.componente',
            'tabelasPrecos',
        ]);

        return erp_view('produtos.show', [
            'title' => 'Detalhes do Produto',
            'produto' => $produto,
        ]);
    }

    public function edit(Produto $produto)
    {
        $this->authorizePermission('edit-products');
        $produto->load([
            'imagens',
            'fornecedores',
            'variacaoAtributos',
            'variacoes',
            'composicoes',
            'tabelasPrecos',
        ]);

        return erp_view('produtos.edit', [
            'title' => 'Editar Produto',
            'produto' => $produto,
            'categorias' => CategoriaProduto::orderBy('nome')->get(),
            'depositos' => Deposito::orderBy('nome')->get(),
            'tabelasPrecos' => TabelaPreco::orderBy('nome')->get(),
            'fornecedores' => Contato::with('cliente')->orderBy('nome')->get(),
            'produtos' => Produto::where('id', '!=', $produto->id)->orderBy('nome')->get(),
        ]);
    }

    public function update(ProdutoRequest $request, Produto $produto)
    {
        $this->authorizePermission('edit-products');
        $this->normalizarValoresBr($request);
        $data = $request->validated();
        $this->validarVariacoes($request, $produto);
        $data['formato'] = $request->input('formato');
        $data['tags'] = $this->normalizarTags($request->input('tags'));
        $data['updated_by'] = auth()->id();

        if (!isset($data['estoque_preco_custo_unitario']) && isset($data['estoque_preco_compra_unitario'])) {
            $data['estoque_preco_custo_unitario'] = $data['estoque_preco_compra_unitario'];
        }

        $produto->update($data);

        $this->syncTabelasPrecos($produto, $request->input('tabelas_precos', []));
        $this->processarImagens($produto, $request);
        $this->processarFornecedores($produto, $request->input('fornecedores', []));
        $this->processarVariacoes($produto, $request);
        $this->processarComposicoes($produto, $request->input('composicoes', []));

        return redirect()->route('produtos.index')
            ->with('success', 'Produto atualizado com sucesso!');
    }

    /**
     * Exclui vários produtos (permissão delete-products + auditoria; respeita entity.index.query).
     */
    public function bulkDestroy(Request $request, AuditCancelExcluirService $auditService)
    {
        $this->authorizePermission('delete-products');
        if (! $auditService->canExcluir(auth()->user(), 'produto')) {
            abort(403, 'Você não tem permissão para excluir produtos.');
        }

        $csv = (string) $request->input('produto_ids_csv', '');
        $ids = array_values(array_unique(array_filter(
            array_map(static fn ($v) => (int) trim((string) $v), explode(',', $csv)),
            static fn ($v) => $v > 0
        )));

        $request->merge(['produto_ids' => $ids]);

        $this->validateWithFilters($request, [
            'produto_ids' => 'required|array|min:1',
            'produto_ids.*' => 'integer|distinct|exists:produtos,id',
            'motivo' => 'required|string|min:10',
        ], [], ['motivo' => 'motivo da exclusão']);

        $query = Produto::query()
            ->with('imagens')
            ->whereIn('id', $request->input('produto_ids', []));
        $query = $this->applyEntityQuery($query, 'produto');
        $produtos = $query->get();

        $solicitados = count($request->input('produto_ids', []));
        if ($produtos->isEmpty()) {
            return redirect()->back()->with('error', 'Nenhum dos produtos selecionados pode ser excluído (sem permissão de acesso ou registros inválidos).');
        }

        DB::transaction(function () use ($produtos, $request, $auditService): void {
            foreach ($produtos as $produto) {
                $this->excluirProdutoComAuditoria($produto, (string) $request->input('motivo'), $request, $auditService);
            }
        });

        $msg = $produtos->count() === 1
            ? '1 produto excluído com sucesso.'
            : $produtos->count().' produtos excluídos com sucesso.';
        if ($produtos->count() < $solicitados) {
            $msg .= ' '.($solicitados - $produtos->count()).' não estavam ao seu alcance e foram ignorados.';
        }

        return redirect()->route('produtos.index')->with('success', $msg);
    }

    public function destroy(Request $request, Produto $produto, AuditCancelExcluirService $auditService)
    {
        $this->authorizePermission('delete-products');
        if (! $auditService->canExcluir(auth()->user(), 'produto')) {
            abort(403, 'Você não tem permissão para excluir produtos.');
        }

        $this->validateWithFilters($request, [
            'motivo' => 'required|string|min:10',
        ], [], ['motivo' => 'motivo da exclusão']);

        $produto->loadMissing('imagens');
        $this->excluirProdutoComAuditoria($produto, (string) $request->input('motivo'), $request, $auditService);

        return redirect()->route('produtos.index')
            ->with('success', 'Produto excluído com sucesso!');
    }

    protected function excluirProdutoComAuditoria(
        Produto $produto,
        string $motivo,
        Request $request,
        AuditCancelExcluirService $auditService
    ): void {
        $descricao = $produto->nome ?? 'Produto #'.$produto->id;
        $entityId = $produto->id;

        foreach ($produto->imagens as $imagem) {
            if ($imagem->caminho_local && Storage::disk('public')->exists($imagem->caminho_local)) {
                Storage::disk('public')->delete($imagem->caminho_local);
            }
        }

        $produto->delete();

        $auditService->log('excluir', 'produto', $entityId, $descricao, $motivo, $request);
    }

    /**
     * Converte valores monetários do formulário (formato BR: 1.279,90) para numérico.
     */
    private function normalizarValoresBr(Request $request): void
    {
        $request->merge([
            'preco_venda' => $request->filled('preco_venda') ? parse_br_number($request->preco_venda) : null,
            'estoque_preco_compra_unitario' => $request->filled('estoque_preco_compra_unitario') ? parse_br_number($request->estoque_preco_compra_unitario) : null,
            'estoque_preco_custo_unitario' => $request->filled('estoque_preco_custo_unitario') ? parse_br_number($request->estoque_preco_custo_unitario) : null,
        ]);

        $tabelas = $request->input('tabelas_precos', []);
        foreach ($tabelas as $id => $row) {
            if (isset($row['preco'])) {
                $tabelas[$id]['preco'] = trim((string) $row['preco']) !== '' ? parse_br_number($row['preco']) : null;
            }
        }
        $request->merge(['tabelas_precos' => $tabelas]);

        $fornecedores = $request->input('fornecedores', []);
        foreach ($fornecedores as $i => $f) {
            if (array_key_exists('preco_compra', $f)) {
                $fornecedores[$i]['preco_compra'] = isset($f['preco_compra']) && trim((string) $f['preco_compra']) !== '' ? parse_br_number($f['preco_compra']) : null;
            }
            if (array_key_exists('preco_custo', $f)) {
                $fornecedores[$i]['preco_custo'] = isset($f['preco_custo']) && trim((string) $f['preco_custo']) !== '' ? parse_br_number($f['preco_custo']) : null;
            }
        }
        $request->merge(['fornecedores' => $fornecedores]);
    }

    private function syncTabelasPrecos(Produto $produto, array $tabelas)
    {
        $sync = [];
        foreach ($tabelas as $item) {
            if (!empty($item['ativo']) && !empty($item['tabela_preco_id'])) {
                $sync[$item['tabela_preco_id']] = ['preco' => $item['preco'] ?? null];
            }
        }
        $produto->tabelasPrecos()->sync($sync);
    }

    private function processarImagens(Produto $produto, Request $request)
    {
        $tipos = $request->input('imagens_tipo', []);
        $urls = $request->input('imagens_url', []);
        $ordens = $request->input('imagens_ordem', []);
        $arquivos = $request->file('imagens_arquivo', []);
        $removerIds = array_filter((array) $request->input('imagens_remover', []));

        if (!empty($removerIds)) {
            $imagensRemover = $produto->imagens()->whereIn('id', $removerIds)->get();
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
                $caminho = $arquivo->storeAs('produtos/' . $produto->id, $nome, 'public');
            }

            $produto->imagens()->create([
                'tipo' => $tipo,
                'caminho_local' => $caminho,
                'url_externa' => $tipo === 'url' ? $url : null,
                'ordem' => (int) $ordem,
            ]);
        }
    }

    private function processarFornecedores(Produto $produto, array $fornecedores)
    {
        $produto->fornecedores()->delete();
        foreach ($fornecedores as $item) {
            if (empty($item['fornecedor_id'])) {
                continue;
            }
            $produto->fornecedores()->create([
                'fornecedor_id' => $item['fornecedor_id'],
                'descricao' => $item['descricao'] ?? null,
                'codigo_fornecedor' => $item['codigo_fornecedor'] ?? null,
                'preco_compra' => $item['preco_compra'] ?? null,
                'preco_custo' => $item['preco_custo'] ?? null,
                'garantia_meses' => $item['garantia_meses'] ?? null,
            ]);
        }
    }

    private function processarVariacoes(Produto $produto, Request $request)
    {
        $produto->variacaoAtributos()->delete();
        $produto->variacoes()->delete();

        if ($request->input('formato') !== 'variacao') {
            return;
        }

        $atributos = $request->input('variacao_atributos', []);
        foreach ($atributos as $atributo) {
            if (empty($atributo['atributo_nome'])) {
                continue;
            }
            $opcoes = $this->normalizarLista($atributo['opcoes'] ?? null);
            $produto->variacaoAtributos()->create([
                'atributo_nome' => $atributo['atributo_nome'],
                'opcoes' => $opcoes,
            ]);
        }

        $variacoes = $request->input('variacoes', []);
        foreach ($variacoes as $variacao) {
            if (empty($variacao['referencia_sku'])) {
                continue;
            }
            $opcoes = $this->normalizarLista($variacao['opcoes_valores'] ?? null);
            $produto->variacoes()->create([
                'referencia_sku' => $variacao['referencia_sku'],
                'opcoes_valores' => $opcoes,
                'ean_variacao' => $variacao['ean_variacao'] ?? null,
                'quantidade' => $variacao['quantidade'] ?? null,
                'herdar_do_pai' => !empty($variacao['herdar_do_pai']),
            ]);
        }
    }

    private function processarComposicoes(Produto $produto, array $composicoes)
    {
        $produto->composicoes()->delete();
        if (request()->input('formato') !== 'composicao') {
            return;
        }
        foreach ($composicoes as $item) {
            if (empty($item['componente_id'])) {
                continue;
            }
            $componenteId = (int) $item['componente_id'];
            if ($this->temCicloComposicao($produto->id, $componenteId)) {
                continue;
            }

            $quantidade = (int) ($item['quantidade'] ?? 1);
            $componente = Produto::find($componenteId);
            $custoUnitario = $item['custo_unitario'] ?? ($componente?->estoque_preco_custo_unitario);
            $custoTotal = $custoUnitario !== null ? $custoUnitario * $quantidade : null;

            $produto->composicoes()->create([
                'componente_id' => $componenteId,
                'quantidade' => $quantidade,
                'custo_unitario' => $custoUnitario,
                'custo_total' => $custoTotal,
            ]);
        }
    }

    private function temCicloComposicao(int $produtoId, int $componenteId, array $visitados = []): bool
    {
        if ($produtoId === $componenteId) {
            return true;
        }
        if (in_array($componenteId, $visitados, true)) {
            return false;
        }
        $visitados[] = $componenteId;
        $filhos = ProdutoComposicao::where('produto_id', $componenteId)->pluck('componente_id')->all();
        foreach ($filhos as $filhoId) {
            if ($this->temCicloComposicao($produtoId, (int) $filhoId, $visitados)) {
                return true;
            }
        }
        return false;
    }

    private function normalizarTags($tags): array
    {
        if (is_array($tags)) {
            return array_values(array_filter(array_map('trim', $tags)));
        }
        if (!is_string($tags)) {
            return [];
        }
        return array_values(array_filter(array_map('trim', explode(',', $tags))));
    }

    private function normalizarLista($valor): array
    {
        if (is_array($valor)) {
            return array_values(array_filter(array_map('trim', $valor)));
        }
        if (!is_string($valor) || $valor === '') {
            return [];
        }
        $valor = trim($valor);
        if (Str::startsWith($valor, '[')) {
            $json = json_decode($valor, true);
            if (is_array($json)) {
                return array_values(array_filter(array_map('trim', $json)));
            }
        }
        return array_values(array_filter(array_map('trim', explode(',', $valor))));
    }

    private function validarVariacoes(Request $request, ?Produto $produto): void
    {
        if ($request->input('formato') !== 'variacao') {
            return;
        }

        $variacoes = collect($request->input('variacoes', []))
            ->pluck('referencia_sku')
            ->filter(fn($v) => !empty($v))
            ->map(fn($v) => strtoupper(trim($v)));

        if ($variacoes->isEmpty()) {
            $request->merge(['formato' => 'simples']);
            return;
        }

        $variacoesInput = $request->input('variacoes', []);
        $faltandoSku = collect($variacoesInput)->contains(function ($item) {
            return empty($item['referencia_sku']);
        });
        if ($faltandoSku) {
            throw \Illuminate\Validation\ValidationException::withMessages([
                'variacoes' => 'SKU da variação é obrigatório.',
            ]);
        }

        if ($variacoes->count() !== $variacoes->unique()->count()) {
            throw \Illuminate\Validation\ValidationException::withMessages([
                'variacoes' => 'Existem SKUs de variação duplicados.',
            ]);
        }

        $query = ProdutoVariacao::whereIn('referencia_sku', $variacoes->all());
        if ($produto) {
            $query->where('produto_id', '!=', $produto->id);
        }

        if ($query->exists()) {
            throw \Illuminate\Validation\ValidationException::withMessages([
                'variacoes' => 'Já existe SKU de variação cadastrado para outro produto.',
            ]);
        }
    }
}
