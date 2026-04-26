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

use App\Http\Controllers\Concerns\BuscaCatalogoVendas;
use App\Http\Requests\PedidoVendaRequest;
use App\Models\Adquirente;
use App\Models\Cliente;
use App\Models\Configuracao;
use App\Models\ContaBancaria;
use App\Models\Contato;
use App\Models\Endereco;
use App\Models\FormaPagamento;
use App\Models\PedidoVenda;
use App\Models\PedidoVendaItem;
use App\Models\PlanoConta;
use App\Models\Produto;
use App\Models\Servico;
use App\Models\TabelaPreco;
use App\Models\User;
use App\Services\LimiteDescontoService;
use App\Services\PedidoVendaComercialValidationService;
use App\Services\PedidoVendaService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class PedidoVendaController extends Controller
{
    use BuscaCatalogoVendas;

    public function __construct(private PedidoVendaService $service) {}

    // -----------------------------------------------------------------------
    // CRUD
    // -----------------------------------------------------------------------

    public function index(Request $request)
    {
        $this->authorizePermission('view-pedidos-venda');

        $query = PedidoVenda::with(['cliente', 'vendedor']);

        if ($request->filled('search')) {
            $search = '%' . $request->search . '%';
            $query->where(function ($q) use ($search) {
                $q->where('numero_sequencial', 'like', $search)
                  ->orWhereHas('cliente', fn ($cq) => $cq->where('nome', 'like', $search));
            });
        }

        $filtraPrevEntregaFaturado = $request->filled('prev_entrega_de') || $request->filled('prev_entrega_ate');
        if ($filtraPrevEntregaFaturado) {
            $query->where('status', 'faturado');
            if ($request->filled('prev_entrega_de')) {
                $query->whereDate('data_prevista_entrega', '>=', $request->prev_entrega_de);
            }
            if ($request->filled('prev_entrega_ate')) {
                $query->whereDate('data_prevista_entrega', '<=', $request->prev_entrega_ate);
            }
        } elseif ($request->filled('status')) {
            $query->where('status', $request->status);
        }
        if ($request->filled('cliente_id')) {
            $query->where('cliente_id', $request->cliente_id);
        }
        if ($request->filled('data_inicio')) {
            $query->where('data_pedido', '>=', $request->data_inicio);
        }
        if ($request->filled('data_fim')) {
            $query->where('data_pedido', '<=', $request->data_fim);
        }

        $query = $this->applyEntityQuery($query->orderByDesc('id'), 'pedido_venda');
        $pedidos = $query->paginate(20)->withQueryString();

        if (function_exists('do_action')) {
            do_action('pedidos_venda.index.before', $pedidos, $request);
        }

        return erp_view('pedidos-venda.index', [
            'title'   => 'Pedidos de Venda',
            'pedidos' => $pedidos,
            'status'  => PedidoVenda::select('status')->distinct()->pluck('status'),
        ]);
    }

    public function create()
    {
        $this->authorizePermission('create-pedidos-venda');
        return erp_view('pedidos-venda.create', $this->dadosFormulario());
    }

    public function store(PedidoVendaRequest $request)
    {
        $this->authorizePermission('create-pedidos-venda');

        DB::beginTransaction();
        try {
            $dados = $request->validated();
            foreach (['observacoes', 'observacoes_internas'] as $k) {
                if ($request->exists($k)) {
                    $dados[$k] = $request->input($k);
                }
            }
            $pedido = $this->service->salvar($dados);
            DB::commit();
        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error('Erro ao criar PedidoVenda', ['exception' => $e->getMessage()]);
            return redirect()->back()->withInput()->with('error', 'Erro ao criar pedido: ' . $e->getMessage());
        }

        return redirect()->route('pedidos-venda.show', $pedido)
            ->with('success', "Pedido {$pedido->numero_sequencial} criado com sucesso.");
    }

    public function show(PedidoVenda $pedidosVenda)
    {
        $this->authorizePermission('view-pedidos-venda');

        $pedido = $pedidosVenda;
        $pedido->load(['cliente', 'vendedor', 'itens.produto.deposito', 'itens.servico', 'itens.osGerada', 'logs.user', 'contasReceber']);

        $formasPagamento = FormaPagamento::where('ativo', true)->orderBy('nome')->get();
        $contasBancarias = ContaBancaria::where('ativo', true)->orderBy('nome')->get();
        $adquirentes     = Adquirente::where('ativo', true)->orderBy('nome')->get();
        $bandeirasPagamento = function_exists('listar_bandeiras_pagamento') ? listar_bandeiras_pagamento() : [];

        $formasPagamentoModal = $formasPagamento->map(function ($fp) {
            return [
                'id' => $fp->id,
                'nome' => $fp->nome,
                'tipo' => $fp->tipo,
                'imediato' => FormaPagamento::tipoEhRecebimentoImediato($fp->tipo),
                'permite_parcela' => (bool) $fp->permite_parcela,
                'max_parcelas' => (int) ($fp->max_parcelas ?? 1),
                'conta_bancaria_id' => $fp->conta_bancaria_id,
                'pix_chaves' => $fp->pix_chaves_ativas,
            ];
        })->values()->all();

        if (function_exists('do_action')) {
            do_action('pedidos_venda.show.before', $pedido);
        }

        $limiteDescontoService = app(LimiteDescontoService::class);

        $user = auth()->user();
        $podeCancelarTotal = $user && ($user->is_admin || $user->hasPermission('pedidos_venda.cancelar_total') || $user->hasPermission('pedidos_venda.cancelar'));
        $podeCancelarParcial = $user && ($user->is_admin || $user->hasPermission('pedidos_venda.cancelar_parcial') || $user->hasPermission('pedidos_venda.cancelar'));

        $statusCrPendente = ['aberto', 'parcial', 'vencido'];
        $contasReceberPendentesDoPedido = $pedido->contasReceber
            ->filter(fn ($cr) => in_array($cr->status, $statusCrPendente, true))
            ->values();
        $bloqueioCancelamentoContaReceberPendente = $pedido->podeCancelarPosFaturamento()
            && $contasReceberPendentesDoPedido->isNotEmpty();

        return erp_view('pedidos-venda.show', [
            'title'                         => "Pedido {$pedido->numero_sequencial}",
            'pedido'                        => $pedido,
            'formasPagamento'               => $formasPagamento,
            'formasPagamentoModal'          => $formasPagamentoModal,
            'contasBancarias'               => $contasBancarias,
            'adquirentes'                   => $adquirentes,
            'bandeirasPagamento'            => $bandeirasPagamento,
            'limiteDesconto'                => $limiteDescontoService->getConfig(),
            'usuarioSujeitoALimiteDesconto' => $limiteDescontoService->usuarioSujeitoALimite(),
            'podeCancelarTotal'             => $podeCancelarTotal,
            'podeCancelarParcial'           => $podeCancelarParcial,
            'contasReceberPendentesDoPedido' => $contasReceberPendentesDoPedido,
            'bloqueioCancelamentoContaReceberPendente' => $bloqueioCancelamentoContaReceberPendente,
        ]);
    }

    public function edit(PedidoVenda $pedidosVenda)
    {
        $this->authorizePermission('edit-pedidos-venda');

        $pedido = $pedidosVenda;

        if (!$pedido->podeEditar()) {
            return redirect()->route('pedidos-venda.show', $pedido)
                ->with('error', 'Este pedido não pode ser editado no status atual.');
        }

        $pedido->load([
            'itens.produto.variacoes',
            'itens.produto.composicoes.componente',
            'itens.servico',
        ]);
        return erp_view('pedidos-venda.edit', array_merge($this->dadosFormulario(), [
            'pedido' => $pedido,
        ]));
    }

    public function update(PedidoVendaRequest $request, PedidoVenda $pedidosVenda)
    {
        $this->authorizePermission('edit-pedidos-venda');

        $pedido = $pedidosVenda;

        if (!$pedido->podeEditar()) {
            return redirect()->back()->with('error', 'Este pedido não pode ser editado no status atual.');
        }

        DB::beginTransaction();
        try {
            $dados = $request->validated();
            foreach (['observacoes', 'observacoes_internas'] as $k) {
                if ($request->exists($k)) {
                    $dados[$k] = $request->input($k);
                }
            }
            $this->service->salvar($dados, $pedido);
            DB::commit();
        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error('Erro ao atualizar PedidoVenda', ['id' => $pedido->id, 'exception' => $e->getMessage()]);
            return redirect()->back()->withInput()->with('error', 'Erro ao salvar pedido: ' . $e->getMessage());
        }

        return redirect()->route('pedidos-venda.show', $pedido)
            ->with('success', 'Pedido atualizado com sucesso.');
    }

    public function destroy(Request $request, PedidoVenda $pedidosVenda)
    {
        $this->authorizePermission('delete-pedidos-venda');

        $pedido = $pedidosVenda;

        if (!$pedido->isRascunho()) {
            $msg = 'Apenas pedidos em rascunho podem ser excluídos.';
            if ($request->wantsJson()) {
                return response()->json(['message' => $msg], 422);
            }
            return redirect()->back()->with('error', $msg);
        }

        $pedido->delete();

        if ($request->wantsJson()) {
            return response()->json(['message' => 'Pedido excluído com sucesso.']);
        }
        return redirect()->route('pedidos-venda.index')->with('success', 'Pedido excluído.');
    }

    // -----------------------------------------------------------------------
    // Ações de status
    // -----------------------------------------------------------------------

    public function confirmar(Request $request, PedidoVenda $pedidosVenda)
    {
        $this->authorizePermission('edit-pedidos-venda');
        $pedido = $pedidosVenda;

        DB::beginTransaction();
        try {
            $this->service->confirmar($pedido);
            DB::commit();
        } catch (\Throwable $e) {
            DB::rollBack();
            return $this->redirectWithError($request, $pedido, $e->getMessage());
        }

        return $this->redirectWithSuccess($request, $pedido, 'Pedido confirmado com sucesso.');
    }

    public function faturar(Request $request, PedidoVenda $pedidosVenda)
    {
        $this->authorizePermission('pedidos_venda.faturar');
        $pedido = $pedidosVenda;

        // Normalizar valores br
        $request->merge(['desconto_valor' => parse_br_number($request->desconto_valor ?? 0)]);
        if (is_array($request->pagamentos)) {
            $pags = $request->pagamentos;
            foreach ($pags as $i => $p) {
                if (isset($p['valor'])) {
                    $pags[$i]['valor'] = parse_br_number($p['valor']);
                }
            }
            $request->merge(['pagamentos' => $pags]);
        }

        $tipoPagamento = $request->input('tipo_pagamento', 'agora');
        $descontoFaturamento = (float) ($request->desconto_valor ?? 0);
        $valorTotalPedido = (float) $pedido->total_geral;
        $valorAReceber = max($valorTotalPedido - $descontoFaturamento, 0);

        $limiteService = app(LimiteDescontoService::class);
        if ($limiteService->usuarioSujeitoALimite() && $valorTotalPedido > 0 && $descontoFaturamento > 0) {
            $validacaoDesconto = $limiteService->validarDescontoGlobal(
                $valorTotalPedido,
                'valor',
                $descontoFaturamento
            );
            if (! $validacaoDesconto['valido']) {
                return $this->redirectWithError($request, $pedido, 'Desconto no faturamento: ' . $validacaoDesconto['mensagem']);
            }
        }

        $comercial = app(PedidoVendaComercialValidationService::class);
        $clienteIdPedido = (int) ($pedido->cliente_id ?? 0);
        $cliente = $clienteIdPedido > 0 ? Cliente::query()->find($clienteIdPedido) : null;
        $pagamentosValidosFaturar = array_values(array_filter(
            $request->pagamentos ?? [],
            fn ($p) => is_array($p) && ! empty($p['forma_pagamento_id'] ?? null) && isset($p['valor']) && (float) $p['valor'] > 0
        ));
        try {
            $comercial->garantirClienteNaoBloqueadoParaVendas($clienteIdPedido);
            $comercial->garantirLimiteCreditoNoFaturamento(
                $cliente,
                $tipoPagamento,
                $valorAReceber,
                $pagamentosValidosFaturar
            );
        } catch (\RuntimeException $e) {
            return $this->redirectWithError($request, $pedido, $e->getMessage());
        }

        if ($tipoPagamento === 'agora' && $valorAReceber > 0.01) {
            $somaPagamentos = 0.0;
            foreach ($request->pagamentos ?? [] as $p) {
                if (! empty($p['forma_pagamento_id'] ?? null) && isset($p['valor']) && (float) $p['valor'] > 0) {
                    $somaPagamentos += (float) $p['valor'];
                }
            }
            if ($somaPagamentos + 0.02 < $valorAReceber) {
                return $this->redirectWithError($request, $pedido, 'Quite o valor total com as formas de pagamento ou escolha Faturado / Fiado.');
            }
        }

        DB::beginTransaction();
        try {
            $this->service->faturar($pedido, $request->all());
            DB::commit();
        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error('Erro ao faturar PedidoVenda', ['id' => $pedido->id, 'exception' => $e->getMessage()]);
            return $this->redirectWithError($request, $pedido, $e->getMessage());
        }

        return $this->redirectWithSuccess($request, $pedido, "Pedido {$pedido->numero_sequencial} faturado com sucesso.");
    }

    public function entregar(Request $request, PedidoVenda $pedidosVenda)
    {
        $this->authorizePermission('pedidos_venda.entregar');
        $pedido = $pedidosVenda;

        DB::beginTransaction();
        try {
            $this->service->entregar($pedido);
            DB::commit();
        } catch (\Throwable $e) {
            DB::rollBack();
            return $this->redirectWithError($request, $pedido, $e->getMessage());
        }

        return $this->redirectWithSuccess($request, $pedido, 'Pedido marcado como entregue.');
    }

    public function cancelar(Request $request, PedidoVenda $pedidosVenda)
    {
        $pedido = $pedidosVenda;
        $tipo = $request->input('tipo', 'total') === 'parcial' ? 'parcial' : 'total';

        // Parcial: o formulário envia uma linha por item; linhas sem quantidade devem ser ignoradas
        // (senão a validação falha em cada `itens.*.quantidade_cancelar` vazio).
        $itensLimpos = [];
        if ($tipo === 'parcial') {
            $rawItens = $request->input('itens', []);
            if (is_array($rawItens)) {
                foreach ($rawItens as $row) {
                    if (! is_array($row)) {
                        continue;
                    }
                    $qRaw = $row['quantidade_cancelar'] ?? null;
                    if ($qRaw === '' || $qRaw === null) {
                        continue;
                    }
                    $q = parse_br_number($qRaw);
                    if ($q <= 0) {
                        continue;
                    }
                    $itensLimpos[] = [
                        'item_id' => (int) ($row['item_id'] ?? 0),
                        'quantidade_cancelar' => $q,
                    ];
                }
            }
        }
        $request->merge(['itens' => $itensLimpos]);

        $this->authorizePedidoVendaCancelamento($tipo);

        $rules = [
            'motivo' => 'required|string|min:3',
            'tipo' => 'nullable|in:total,parcial',
        ];
        if ($tipo === 'parcial') {
            $rules['itens'] = 'required|array|min:1';
            $rules['itens.*.item_id'] = 'required|integer|min:1';
            $rules['itens.*.quantidade_cancelar'] = 'required|numeric|min:0.0001';
        }

        $request->validate($rules);

        $motivo = (string) $request->input('motivo', '');
        $itensPayload = [];
        if ($tipo === 'parcial') {
            foreach ($request->input('itens', []) as $row) {
                if (! is_array($row)) {
                    continue;
                }
                $itensPayload[] = [
                    'item_id' => (int) ($row['item_id'] ?? 0),
                    'quantidade_cancelar' => (float) ($row['quantidade_cancelar'] ?? 0),
                ];
            }
        }

        DB::beginTransaction();
        try {
            $this->service->cancelar($pedido, $motivo, $tipo, $itensPayload);
            DB::commit();
        } catch (\InvalidArgumentException $e) {
            DB::rollBack();

            return $this->redirectWithError($request, $pedido, $e->getMessage());
        } catch (\Throwable $e) {
            DB::rollBack();
            return $this->redirectWithError($request, $pedido, $e->getMessage());
        }

        $msg = $tipo === 'parcial'
            ? ($pedido->fresh()->isCancelado()
                ? 'Cancelamento concluído: pedido encerrado.'
                : 'Cancelamento parcial registrado (estoque e conta a pagar quando aplicável).')
            : 'Pedido cancelado.';

        return $this->redirectWithSuccess($request, $pedido, $msg);
    }

    public function duplicar(Request $request, PedidoVenda $pedidosVenda)
    {
        $this->authorizePermission('create-pedidos-venda');
        $pedido = $pedidosVenda;

        DB::beginTransaction();
        try {
            $novo = $this->service->duplicar($pedido);
            DB::commit();
        } catch (\Throwable $e) {
            DB::rollBack();
            return $this->redirectWithError($request, $pedido, $e->getMessage());
        }

        return redirect()->route('pedidos-venda.show', $novo)
            ->with('success', "Pedido {$novo->numero_sequencial} criado como duplicata de {$pedido->numero_sequencial}.");
    }

    // -----------------------------------------------------------------------
    // PDF
    // -----------------------------------------------------------------------

    public function exportPdf(PedidoVenda $pedidosVenda)
    {
        $this->authorizePermission('view-pedidos-venda');
        // Recarrega do banco para garantir observações/valores atuais (evita instância desatualizada ou cache de relações).
        $pedido = PedidoVenda::query()
            ->with(['cliente', 'vendedor', 'itens.produto.composicoes.componente', 'itens.variacao'])
            ->findOrFail($pedidosVenda->getKey());

        $pdf = Pdf::loadView('pedidos-venda.pdf', ['pedido' => $pedido]);

        return $pdf->download("pedido-{$pedido->numero_sequencial}.pdf")
            ->header('Cache-Control', 'private, no-store, no-cache, must-revalidate')
            ->header('Pragma', 'no-cache');
    }

    // -----------------------------------------------------------------------
    // Ajax — busca produto/serviço para o formulário
    // -----------------------------------------------------------------------

    public function buscarProduto(Request $request)
    {
        $term           = '%' . $request->input('q', '') . '%';
        $tipo           = $request->input('tipo', 'produto');
        $tabelaPrecoId  = $this->normalizarTabelaPrecoIdRequest($request->input('tabela_preco_id'));
        $produtoId      = (int) $request->input('produto_id', 0);
        $percentualAjuste = null;
        if ($tabelaPrecoId !== null) {
            $percentualAjuste = (float) (TabelaPreco::query()->whereKey($tabelaPrecoId)->value('percentual_ajuste') ?? 0);
        }

        if ($tipo === 'servico') {
            $resultados = Servico::where(function ($q) use ($term) {
                    $q->where('nome', 'like', $term)
                      ->orWhere('codigo_interno', 'like', $term)
                      ->orWhere('codigo_sku', 'like', $term);
                })
                ->select('id', 'nome as descricao', 'preco')
                ->limit(20)->get();

            return response()->json($resultados)->header('Cache-Control', 'no-store, no-cache');
        }

        if ($produtoId > 0) {
            $p = Produto::query()
                ->whereKey($produtoId)
                ->with([
                    'variacoes' => fn ($q) => $q->orderBy('id'),
                    'composicoes' => fn ($q) => $q->orderBy('id'),
                    'composicoes.componente',
                ])
                ->select('id', 'nome', 'formato', 'preco_venda', 'estoque_quantidade', 'estoque_localizacao')
                ->first();
            if (! $p) {
                return response()->json([])->header('Cache-Control', 'no-store, no-cache');
            }
            $precosTabelaMap = $this->mapaPrecosProdutoTabela(collect([(int) $p->id]), $tabelaPrecoId);

            return response()->json([
                $this->mapearProdutoLinhaBuscaPedido($p, $precosTabelaMap, $tabelaPrecoId, $percentualAjuste),
            ])->header('Cache-Control', 'no-store, no-cache');
        }

        $produtos = Produto::where(function ($q) use ($term) {
                $q->where('nome', 'like', $term)
                  ->orWhere('codigo_sku', 'like', $term);
            })
            ->with([
                'variacoes' => fn ($q) => $q->orderBy('id'),
                'composicoes' => fn ($q) => $q->orderBy('id'),
                'composicoes.componente',
            ])
            ->select('id', 'nome', 'formato', 'preco_venda', 'estoque_quantidade', 'estoque_localizacao')
            ->limit(20)
            ->get();

        $precosTabelaMap = $this->mapaPrecosProdutoTabela($produtos->pluck('id'), $tabelaPrecoId);

        return response()->json($produtos->map(function (Produto $p) use ($precosTabelaMap, $tabelaPrecoId, $percentualAjuste) {
            return $this->mapearProdutoLinhaBuscaPedido($p, $precosTabelaMap, $tabelaPrecoId, $percentualAjuste);
        })->values())->header('Cache-Control', 'no-store, no-cache');
    }

    /**
     * Lista pedidos em rascunho ou confirmado com itens (orçamento / proposta) para importação no formulário.
     */
    public function listarOrcamentos(Request $request)
    {
        $this->authorizeImportarItensDeOrcamento();

        $exceto = (int) $request->input('exceto', 0);
        $q = trim((string) $request->input('q', ''));

        $query = PedidoVenda::query()
            ->whereIn('status', ['rascunho', 'confirmado'])
            ->whereHas('itens')
            ->with(['cliente:id,nome'])
            ->orderByDesc('id')
            ->limit(80);

        if ($exceto > 0) {
            $query->where('id', '!=', $exceto);
        }

        if ($q !== '') {
            $like = '%' . addcslashes($q, '%_\\') . '%';
            $query->where(function ($sub) use ($like) {
                $sub->where('numero_sequencial', 'like', $like)
                    ->orWhereHas('cliente', fn ($c) => $c->where('nome', 'like', $like));
            });
        }

        $rows = $query->get(['id', 'numero_sequencial', 'cliente_id', 'data_pedido', 'status', 'total_geral']);

        if ($rows->isEmpty()) {
            return response()->json([]);
        }

        $pedidoIds = $rows->pluck('id');
        $maxPreview  = 12;

        $allItens = PedidoVendaItem::query()
            ->whereIn('pedido_venda_id', $pedidoIds)
            ->orderBy('pedido_venda_id')
            ->orderBy('ordem')
            ->orderBy('id')
            ->get(['pedido_venda_id', 'tipo', 'descricao', 'quantidade', 'preco_unitario']);

        $contagemPorPedido = $allItens->countBy('pedido_venda_id');
        $agrupado = $allItens->groupBy('pedido_venda_id');

        return response()->json($rows->map(function (PedidoVenda $p) use ($agrupado, $contagemPorPedido, $maxPreview) {
            $lista   = $agrupado->get($p->id, collect());
            $preview = $lista->take($maxPreview);
            $total   = (int) ($contagemPorPedido[$p->id] ?? 0);

            return [
                'id'                => $p->id,
                'numero_sequencial' => $p->numero_sequencial,
                'cliente_id'        => $p->cliente_id,
                'cliente_nome'      => $p->cliente?->nome ?? '-',
                'data_pedido'       => $p->data_pedido?->format('d/m/Y') ?? '',
                'status'            => $p->status,
                'total_geral'       => number_format((float) $p->total_geral, 2, ',', '.'),
                'itens'             => $preview->map(fn (PedidoVendaItem $i) => [
                    'tipo'           => $i->tipo,
                    'descricao'      => $i->descricao,
                    'quantidade'     => format_quantity($i->quantidade),
                    'preco_unitario' => number_format((float) $i->preco_unitario, 2, ',', '.'),
                ])->values(),
                'itens_count'       => $total,
                'itens_mais'        => max(0, $total - $preview->count()),
            ];
        })->values());
    }

    /**
     * Retorna itens de um pedido (orçamento) no formato do formulário para cópia no pedido atual.
     */
    public function itensImportacao(Request $request, PedidoVenda $pedidosVenda)
    {
        $this->authorizeImportarItensDeOrcamento();

        $exceto = (int) $request->input('exceto', 0);
        if ($exceto > 0 && (int) $pedidosVenda->id === $exceto) {
            return response()->json(['message' => 'Não é possível importar itens do próprio pedido.'], 422);
        }

        if (! in_array($pedidosVenda->status, ['rascunho', 'confirmado'], true)) {
            return response()->json([
                'message' => 'Só é possível importar itens de pedidos em rascunho ou confirmados (orçamento / proposta).',
            ], 422);
        }

        $pedidosVenda->load(['itens.produto.composicoes.componente', 'cliente:id,nome']);

        if ($pedidosVenda->itens->isEmpty()) {
            return response()->json(['message' => 'Este pedido não possui itens.'], 422);
        }

        $itens = $pedidosVenda->itens->map(function (PedidoVendaItem $i) {
            $kitComponentes = $i->tipo === 'produto'
                ? $this->kitComponentesDoProduto($i->produto)
                : [];

            return [
                'id'             => null,
                'tipo'           => $i->tipo,
                'produto_id'     => $i->produto_id,
                'produto_variacao_id' => $i->produto_variacao_id,
                'servico_id'     => $i->servico_id,
                'descricao'      => $i->descricao,
                'quantidade'     => format_quantity($i->quantidade) ?: '1',
                'preco_unitario' => number_format((float) $i->preco_unitario, 2, ',', '.'),
                'desconto'       => number_format((float) $i->desconto, 2, ',', '.'),
                'gerar_os'       => (bool) $i->gerar_os,
                'kit_componentes' => $kitComponentes,
            ];
        })->values();

        return response()->json([
            'numero_sequencial' => $pedidosVenda->numero_sequencial,
            'cliente_id'        => $pedidosVenda->cliente_id,
            'cliente_nome'      => $pedidosVenda->cliente?->nome ?? '',
            'itens'             => $itens,
        ]);
    }

    // -----------------------------------------------------------------------
    // Helpers privados
    // -----------------------------------------------------------------------

    /**
     * Total: pedidos_venda.cancelar_total ou pedidos_venda.cancelar (legado).
     * Parcial: pedidos_venda.cancelar_parcial ou pedidos_venda.cancelar (legado).
     */
    protected function authorizePedidoVendaCancelamento(string $tipo): void
    {
        $user = auth()->user();
        if (! $user) {
            abort(403);
        }
        if ($user->is_admin) {
            return;
        }
        if ($tipo === 'parcial') {
            if ($user->hasPermission('pedidos_venda.cancelar_parcial') || $user->hasPermission('pedidos_venda.cancelar')) {
                return;
            }
        } else {
            if ($user->hasPermission('pedidos_venda.cancelar_total') || $user->hasPermission('pedidos_venda.cancelar')) {
                return;
            }
        }
        abort(403, 'Sem permissão para este tipo de cancelamento de pedido.');
    }

    private function dadosFormulario(): array
    {
        $limiteDescontoService = app(LimiteDescontoService::class);
        $comercial = app(PedidoVendaComercialValidationService::class);
        $vdForm = (int) Configuracao::getValue('pedidos_venda.validade_orcamento_dias', '');

        $data = [
            'title'                         => 'Pedido de Venda',
            'validade_orcamento_dias_config' => $vdForm > 0 ? $vdForm : 0,
            'clientes'                      => Cliente::orderBy('nome')->select('id', 'nome', 'bloqueado_vendas', 'limite_credito', 'tipo_pessoa', 'cpf', 'cnpj', 'razao_social', 'grupo_economico_id')->get(),
            'unidades'                      => Cliente::where('tipo_unidade', 'filial')->orWhereNotNull('matriz_id')->orderBy('nome')->select('id', 'nome', 'grupo_economico_id', 'matriz_id')->get(),
            'contatos'                      => Contato::orderBy('nome')->get(),
            'enderecos'                     => Endereco::with('cliente')->orderBy('id')->get(),
            'vendedores'                    => User::where('ativo', true)->orderBy('name')->select('id', 'name')->get(),
            'canaisVenda'                   => ['presencial', 'whatsapp', 'telefone', 'email', 'site', 'outro'],
            'tabelasPrecos'                 => TabelaPreco::where('ativo', true)->orderBy('nome')->select('id', 'nome', 'percentual_ajuste')->get(),
            'limiteDesconto'                => $limiteDescontoService->getConfig(),
            'usuarioSujeitoALimiteDesconto' => $limiteDescontoService->usuarioSujeitoALimite(),
            'podeAplicarDescontoPedidoVenda' => $comercial->usuarioPodeAplicarDescontoPedidoVenda(),
        ];
        $data['podeImportarOrcamento'] = $this->userPodeImportarItensDeOrcamento();

        if (function_exists('apply_filters')) {
            $data = apply_filters('pedidos_venda.form.data', $data);
        }

        // Compat.: filtros legados podem expor só `validade_orcamento_dias` (antes era o padrão da config no form).
        if (! array_key_exists('validade_orcamento_dias_config', $data) && array_key_exists('validade_orcamento_dias', $data)) {
            $legacy = (int) ($data['validade_orcamento_dias'] ?? 0);
            $data['validade_orcamento_dias_config'] = $legacy > 0 ? $legacy : 0;
        }

        return $data;
    }

    protected function userPodeImportarItensDeOrcamento(): bool
    {
        $user = auth()->user();
        if (! $user) {
            return false;
        }
        if ($user->is_admin) {
            return true;
        }

        return $user->hasPermission('create-pedidos-venda') || $user->hasPermission('edit-pedidos-venda');
    }

    protected function authorizeImportarItensDeOrcamento(): void
    {
        if (! $this->userPodeImportarItensDeOrcamento()) {
            abort(403, 'Sem permissão para importar itens de orçamento.');
        }
    }

    private function redirectWithSuccess(Request $request, PedidoVenda $pedido, string $msg)
    {
        if ($request->wantsJson()) {
            return response()->json(['message' => $msg, 'redirect' => route('pedidos-venda.show', $pedido)]);
        }
        return redirect()->route('pedidos-venda.show', $pedido)->with('success', $msg);
    }

    private function redirectWithError(Request $request, PedidoVenda $pedido, string $msg)
    {
        if ($request->wantsJson()) {
            return response()->json(['message' => $msg], 422);
        }
        return redirect()->back()->with('error', $msg);
    }
}
