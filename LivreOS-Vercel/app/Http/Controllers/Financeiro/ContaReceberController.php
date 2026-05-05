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
use App\Models\ContaReceber;
use App\Models\ContaReceberAnexo;
use App\Models\Cliente;
use App\Models\OrdemServico;
use App\Models\FormaPagamento;
use App\Models\ContaBancaria;
use App\Models\PlanoConta;
use App\Models\CentroCusto;
use App\Models\MovimentacaoFinanceira;
use App\Models\BaixaTitulo;
use App\Models\TaxaAdquirente;
use App\Models\PagamentoRecorrente;
use App\Models\Empresa;
use App\Models\CategoriaFinanceira;
use App\Models\Tag;
use App\Services\AdquirenteService;
use App\Services\AuditCancelExcluirService;
use App\Services\ConciliacaoService;
use App\Services\Financeiro\BaixaTituloTaxaMovimentacaoResolver;
use App\Services\Financeiro\CorrigirDatasBaixaContaReceberService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class ContaReceberController extends Controller
{
    public function index(Request $request)
    {
        $aba = $request->get('aba', 'contas');

        $hoje = now()->toDateString();
        $mesInicio = now()->startOfMonth()->toDateString();
        $mesFim = now()->endOfMonth()->toDateString();

        $baseReceber = $this->applyEntityQuery(
            ContaReceber::query()->whereNotIn('status_estrutura', ['agrupado', 'desmembrado']),
            'conta_receber'
        );

        $indicadores = [
            'totalPendente' => (clone $baseReceber)->whereIn('status', ['aberto', 'parcial'])
                ->sum(DB::raw('valor - COALESCE(valor_recebido, 0)')),
            'totalVencido' => (clone $baseReceber)->whereIn('status', ['aberto', 'parcial'])
                ->where('data_vencimento', '<', $hoje)
                ->sum(DB::raw('valor - COALESCE(valor_recebido, 0)')),
            'totalAVencer' => (clone $baseReceber)->whereIn('status', ['aberto', 'parcial'])
                ->where('data_vencimento', '>=', $hoje)
                ->sum(DB::raw('valor - COALESCE(valor_recebido, 0)')),
            'recebidoMes' => (clone $baseReceber)->where('status', 'pago')
                ->whereBetween('data_recebimento', [$mesInicio, $mesFim])
                ->sum('valor_recebido'),
            'qtdPendente' => (clone $baseReceber)->whereIn('status', ['aberto', 'parcial'])->count(),
            'qtdVencido' => (clone $baseReceber)->whereIn('status', ['aberto', 'parcial'])
                ->where('data_vencimento', '<', $hoje)->count(),
            'vencendo7dias' => (clone $baseReceber)->whereIn('status', ['aberto', 'parcial'])
                ->whereBetween('data_vencimento', [$hoje, now()->addDays(7)->toDateString()])->count(),
            'ticketMedio' => 0,
        ];
        $totalPagoMes = (clone $baseReceber)->where('status', 'pago')
            ->whereBetween('data_recebimento', [$mesInicio, $mesFim])->count();
        $indicadores['ticketMedio'] = $totalPagoMes > 0
            ? $indicadores['recebidoMes'] / $totalPagoMes : 0;

        // Aba Contas: query de contas a receber (só quando na aba Contas)
        $contas = new \Illuminate\Pagination\LengthAwarePaginator([], 0, 15);
        if ($aba !== 'recorrentes') {
            $query = ContaReceber::with(['cliente', 'ordemServico', 'formaPagamento', 'contaBancaria', 'planoConta', 'centroCusto'])
                ->whereNotIn('status_estrutura', ['agrupado', 'desmembrado']);

        // Filtro: status (ignorado se estiver usando atalho rápido)
        $usarAtalho = $request->filled('vencidas') || $request->filled('a_vencer') || $request->filled('pendentes');
        if (!$usarAtalho && $request->filled('status')) {
            $query->where('status', $request->status);
        }

        // Filtro: cliente
        if ($request->filled('cliente_id')) {
            $query->where('cliente_id', $request->cliente_id);
        }

        // Filtro: forma de pagamento
        if ($request->filled('forma_pagamento_id')) {
            $query->where('forma_pagamento_id', $request->forma_pagamento_id);
        }

        // Filtro: conta bancária
        if ($request->filled('conta_bancaria_id')) {
            $query->where('conta_bancaria_id', $request->conta_bancaria_id);
        }

        if ($request->filled('plano_conta_id')) {
            $query->where('plano_conta_id', (int) $request->plano_conta_id);
        }

        if ($request->filled('centro_custo_id')) {
            $query->where('centro_custo_id', (int) $request->centro_custo_id);
        }

        if ($request->filled('categoria_financeira_id')) {
            $query->where('categoria_financeira_id', (int) $request->categoria_financeira_id);
        }

        if ($request->filled('tag_id')) {
            $tagIdFiltro = (int) $request->tag_id;
            if ($tagIdFiltro > 0) {
                $query->whereHas('tags', static function ($q) use ($tagIdFiltro) {
                    $q->where('tags.id', $tagIdFiltro);
                });
            }
        }

        // Filtro: período de vencimento
        if ($request->filled('data_inicio')) {
            $query->where('data_vencimento', '>=', $request->data_inicio);
        }
        if ($request->filled('data_fim')) {
            $query->where('data_vencimento', '<=', $request->data_fim);
        }

        // Filtro: apenas vencidas (status != pago e data_vencimento < hoje)
        if ($request->filled('vencidas') && $request->boolean('vencidas')) {
            $query->whereIn('status', ['aberto', 'parcial', 'vencido'])
                ->where('data_vencimento', '<', now()->toDateString());
        }

        // Filtro: a vencer (abertas com vencimento a partir de hoje)
        if ($request->filled('a_vencer') && $request->boolean('a_vencer')) {
            $query->whereIn('status', ['aberto', 'parcial'])
                ->where('data_vencimento', '>=', now()->toDateString());
        }

        // Filtro: pendentes (aberto ou parcial, não pagos)
        if ($request->filled('pendentes') && $request->boolean('pendentes')) {
            $query->whereIn('status', ['aberto', 'parcial']);
        }

        // Filtro: busca por descrição ou observações
        if ($request->filled('busca')) {
            $termo = $request->busca;
            $query->where(function ($q) use ($termo) {
                $q->where('descricao', 'like', "%{$termo}%")
                    ->orWhere('observacoes', 'like', "%{$termo}%");
            });
        }

        // Filtro: valor mínimo / máximo
        if ($request->filled('valor_min')) {
            $query->where('valor', '>=', (float) str_replace(',', '.', $request->valor_min));
        }
        if ($request->filled('valor_max')) {
            $query->where('valor', '<=', (float) str_replace(',', '.', $request->valor_max));
        }

        // Ordenação: padrão por data de criação decrescente (lançamentos de hoje primeiro)
        $ordenar = $request->get('ordenar', 'created_at');
        $direcao = $request->get('ordenar_direcao', 'desc');
        if (!in_array($ordenar, ['data_vencimento', 'valor', 'cliente_id', 'created_at'])) {
            $ordenar = 'data_vencimento';
        }
        if (!in_array($direcao, ['asc', 'desc'])) {
            $direcao = 'desc';
        }
        if ($ordenar === 'cliente_id') {
            $query->join('clientes', 'contas_receber.cliente_id', '=', 'clientes.id')
                ->orderBy('clientes.nome', $direcao)
                ->select('contas_receber.*');
        } else {
            $query->orderBy($ordenar, $direcao);
        }

        $query = $this->applyEntityQuery($query, 'conta_receber');
        $contas = $query->paginate(15)->withQueryString();
        }

        $clientes = Cliente::query()
            ->get(['id', 'nome', 'razao_social'])
            ->sortBy(function ($c) {
                $label = trim((string) ($c->nome ?: $c->razao_social));

                return mb_strtolower($label, 'UTF-8');
            })
            ->values();
        $formasPagamento = FormaPagamento::where('ativo', true)
            ->with(['adquirentes' => fn ($q) => $q->where('ativo', true)->orderBy('nome')])
            ->orderBy('nome')
            ->get();
        $bandeirasCartaoIdx = TaxaAdquirente::query()
            ->where('ativo', true)
            ->whereNotNull('bandeira')
            ->where('bandeira', '!=', '')
            ->distinct()
            ->orderBy('bandeira')
            ->pluck('bandeira');
        if ($bandeirasCartaoIdx->isEmpty()) {
            $bandeirasCartaoIdx = collect(['master', 'visa', 'elo', 'amex', 'hipercard', 'outros']);
        }
        $formasCartaoMetaIdx = $this->montarFormasCartaoMetaForView($formasPagamento);
        $contasBancarias = ContaBancaria::where('ativo', true)->orderBy('nome')->get(['id', 'nome']);
        $planosContasFiltro = PlanoConta::where('tipo', 'receita')
            ->where('ativo', true)
            ->orderBy('nome')
            ->orderByRaw('COALESCE(codigo, "")')
            ->get(['id', 'codigo', 'nome']);
        $centrosCustoFiltro = CentroCusto::where('ativo', true)->orderBy('nome')->orderBy('ordem')->get(['id', 'codigo', 'nome']);
        $categoriasFinanceirasFiltroOpcoes = CategoriaFinanceira::opcoesParaSelect('receber');
        $tagsFinanceirasFiltro = Tag::query()->paraTituloFinanceiro('conta_receber')->orderBy('nome')->get(['id', 'nome']);

        // Aba Recorrentes: dados dos pagamentos recorrentes (quando na aba)
        $recorrentes = null;
        if ($aba === 'recorrentes') {
            $qRec = PagamentoRecorrente::with(['cliente', 'formaPagamento', 'contaBancaria']);
            $qRec = $this->applyEntityQuery($qRec, 'pagamento_recorrente');
            if ($request->filled('rec_cliente_id')) {
                $qRec->where('cliente_id', $request->rec_cliente_id);
            }
            if ($request->filled('rec_ativo')) {
                if ($request->rec_ativo === '1') {
                    $qRec->where('ativo', true);
                } elseif ($request->rec_ativo === '0') {
                    $qRec->where('ativo', false);
                }
            }
            if ($request->filled('rec_tipo')) {
                $qRec->where('tipo', $request->rec_tipo);
            }
            if ($request->filled('rec_frequencia')) {
                $qRec->where('frequencia', $request->rec_frequencia);
            }
            $recorrentes = $qRec->orderBy('proxima_geracao_em')->paginate(15)->withQueryString();
        }

        return erp_view('financeiro.contas-receber.index', [
            'title' => 'Contas a Receber',
            'aba' => $aba,
            'contas' => $contas,
            'recorrentes' => $recorrentes,
            'clientes' => $clientes,
            'formasPagamento' => $formasPagamento,
            'formasCartaoMeta' => $formasCartaoMetaIdx,
            'bandeirasCartao' => $bandeirasCartaoIdx,
            'contasBancarias' => $contasBancarias,
            'planosContasFiltro' => $planosContasFiltro,
            'centrosCustoFiltro' => $centrosCustoFiltro,
            'categoriasFinanceirasFiltroOpcoes' => $categoriasFinanceirasFiltroOpcoes,
            'tagsFinanceirasFiltro' => $tagsFinanceirasFiltro,
            'indicadores' => $indicadores,
        ]);
    }

    /**
     * Retorna as contas a receber geradas por recorrentes para um cliente (para o modal na aba Recorrentes).
     * Busca por pagamento_recorrente_id para não depender de texto na descrição.
     */
    public function lancamentosRecorrentesCliente(Request $request)
    {
        $request->validate(['cliente_id' => 'required|exists:clientes,id']);
        $clienteId = $request->cliente_id;
        $cliente = Cliente::find($clienteId);
        $recorrenteIds = PagamentoRecorrente::where('cliente_id', $clienteId)->pluck('id');
        $contas = ContaReceber::with(['formaPagamento'])
            ->whereIn('pagamento_recorrente_id', $recorrenteIds)
            ->orderByDesc('data_vencimento')
            ->limit(200)
            ->get();

        return response()->json([
            'cliente_nome' => $cliente ? $cliente->nome : '',
            'html' => view('financeiro.contas-receber._lancamentos-recorrentes-cliente', [
                'contas' => $contas,
                'cliente_nome' => $cliente ? $cliente->nome : '',
            ])->render(),
        ]);
    }

    public function exportarPdf(Request $request)
    {
        $query = ContaReceber::with(['cliente', 'formaPagamento', 'contaBancaria', 'planoConta']);

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }
        if ($request->filled('cliente_id')) {
            $query->where('cliente_id', $request->cliente_id);
        }
        if ($request->filled('forma_pagamento_id')) {
            $query->where('forma_pagamento_id', $request->forma_pagamento_id);
        }
        if ($request->filled('conta_bancaria_id')) {
            $query->where('conta_bancaria_id', $request->conta_bancaria_id);
        }
        if ($request->filled('plano_conta_id')) {
            $query->where('plano_conta_id', (int) $request->plano_conta_id);
        }
        if ($request->filled('centro_custo_id')) {
            $query->where('centro_custo_id', (int) $request->centro_custo_id);
        }
        if ($request->filled('categoria_financeira_id')) {
            $query->where('categoria_financeira_id', (int) $request->categoria_financeira_id);
        }
        if ($request->filled('tag_id')) {
            $tagIdFiltro = (int) $request->tag_id;
            if ($tagIdFiltro > 0) {
                $query->whereHas('tags', static function ($q) use ($tagIdFiltro) {
                    $q->where('tags.id', $tagIdFiltro);
                });
            }
        }
        if ($request->filled('data_inicio')) {
            $query->where('data_vencimento', '>=', $request->data_inicio);
        }
        if ($request->filled('data_fim')) {
            $query->where('data_vencimento', '<=', $request->data_fim);
        }
        if ($request->filled('busca')) {
            $termo = $request->busca;
            $query->where(function ($q) use ($termo) {
                $q->where('descricao', 'like', "%{$termo}%")
                    ->orWhere('observacoes', 'like', "%{$termo}%");
            });
        }
        if ($request->filled('vencidas') && $request->boolean('vencidas')) {
            $query->whereIn('status', ['aberto', 'parcial', 'vencido'])
                ->where('data_vencimento', '<', now()->toDateString());
        }
        if ($request->filled('a_vencer') && $request->boolean('a_vencer')) {
            $query->whereIn('status', ['aberto', 'parcial'])
                ->where('data_vencimento', '>=', now()->toDateString());
        }
        if ($request->filled('pendentes') && $request->boolean('pendentes')) {
            $query->whereIn('status', ['aberto', 'parcial']);
        }

        $query = $this->applyEntityQuery($query, 'conta_receber');
        $contas = $query->orderBy('data_vencimento')->limit(2000)->get();

        $hoje = now()->toDateString();
        $totais = [
            'valor' => $contas->sum('valor'),
            'recebido' => $contas->sum('valor_recebido'),
            'pendente' => $contas->sum(fn ($c) => $c->valor_pendente ?? 0),
            'juros' => $contas->sum('juros'),
            'multa' => $contas->sum('multa'),
            'desconto' => $contas->sum('desconto'),
        ];

        $agrupado = $contas->groupBy(function ($c) {
            return match ($c->status) {
                'pago' => 'Pagas',
                'parcial' => 'Parcialmente Recebidas',
                'cancelado' => 'Canceladas',
                default => $c->data_vencimento && $c->data_vencimento->format('Y-m-d') < now()->toDateString()
                    ? 'Vencidas' : 'Em Aberto (A Vencer)',
            };
        });

        $ordemGrupos = ['Vencidas', 'Em Aberto (A Vencer)', 'Parcialmente Recebidas', 'Pagas', 'Canceladas'];
        $grupos = collect();
        foreach ($ordemGrupos as $nomeGrupo) {
            if ($agrupado->has($nomeGrupo)) {
                $itens = $agrupado->get($nomeGrupo);
                $grupos->put($nomeGrupo, [
                    'itens' => $itens,
                    'subtotais' => [
                        'valor' => $itens->sum('valor'),
                        'recebido' => $itens->sum('valor_recebido'),
                        'pendente' => $itens->sum(fn ($c) => $c->valor_pendente ?? 0),
                        'juros' => $itens->sum('juros'),
                        'multa' => $itens->sum('multa'),
                        'desconto' => $itens->sum('desconto'),
                    ],
                ]);
            }
        }

        $filtrosTexto = [];
        if ($request->filled('status')) $filtrosTexto[] = 'Status: ' . ucfirst($request->status);
        if ($request->filled('cliente_id')) {
            $cli = Cliente::find($request->cliente_id);
            if ($cli) $filtrosTexto[] = 'Cliente: ' . ($cli->nome ?? $cli->razao_social);
        }
        if ($request->filled('plano_conta_id')) {
            $pc = PlanoConta::find($request->plano_conta_id);
            if ($pc) {
                $filtrosTexto[] = 'Plano de contas: '.trim(($pc->codigo ? $pc->codigo.' — ' : '').$pc->nome);
            }
        }
        if ($request->filled('centro_custo_id')) {
            $cc = CentroCusto::find($request->centro_custo_id);
            if ($cc) {
                $filtrosTexto[] = 'Centro de custo: '.trim(($cc->codigo ? $cc->codigo.' — ' : '').$cc->nome);
            }
        }
        if ($request->filled('categoria_financeira_id')) {
            $cf = CategoriaFinanceira::find($request->categoria_financeira_id);
            if ($cf) {
                $filtrosTexto[] = 'Categoria: '.$cf->nome;
            }
        }
        if ($request->filled('tag_id')) {
            $tg = Tag::find($request->tag_id);
            if ($tg) {
                $filtrosTexto[] = 'Tag: '.$tg->nome;
            }
        }
        if ($request->filled('data_inicio')) $filtrosTexto[] = 'De: ' . \Carbon\Carbon::parse($request->data_inicio)->format('d/m/Y');
        if ($request->filled('data_fim')) $filtrosTexto[] = 'Até: ' . \Carbon\Carbon::parse($request->data_fim)->format('d/m/Y');
        if ($request->filled('vencidas')) $filtrosTexto[] = 'Apenas vencidas';
        if ($request->filled('a_vencer')) $filtrosTexto[] = 'Apenas a vencer';
        if ($request->filled('pendentes')) $filtrosTexto[] = 'Apenas pendentes';

        $html = view('financeiro.contas-receber.pdf-completo', [
            'grupos' => $grupos,
            'totais' => $totais,
            'totalRegistros' => $contas->count(),
            'filtrosTexto' => $filtrosTexto,
        ])->render();

        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadHTML($html);
        $pdf->setPaper('A4', 'landscape');

        return $pdf->stream('contas-a-receber-' . now()->format('Y-m-d') . '.pdf');
    }

    /**
     * Recibo de pagamento em PDF para uma conta a receber paga (dados do cliente, recebimento, forma de pagamento e referente).
     */
    public function recibo(ContaReceber $contaReceber)
    {
        if ($contaReceber->status !== 'pago') {
            return redirect()->route('financeiro.contas-receber.index')
                ->with('error', 'Recibo de pagamento está disponível apenas para contas com status Pago.');
        }

        $contaReceber->load(['cliente', 'formaPagamento', 'ordemServico', 'pagamentoRecorrente']);

        $referente = $contaReceber->descricao;
        $tipoReferente = 'outro';

        if ($contaReceber->ordem_servico_id && $contaReceber->ordemServico) {
            $codigo = $contaReceber->ordemServico->codigo_interno ?? (string) $contaReceber->ordemServico->id;
            $referente = 'Ordem de Serviço nº ' . $codigo;
            $tipoReferente = 'ordem_servico';
        } elseif ($contaReceber->pagamento_recorrente_id && $contaReceber->pagamentoRecorrente) {
            $referente = $contaReceber->pagamentoRecorrente->descricao
                ? 'Pagamento recorrente: ' . $contaReceber->pagamentoRecorrente->descricao
                : 'Pagamento recorrente';
            $tipoReferente = 'recorrente';
        } elseif ($contaReceber->origem_tipo === 'adiantamento_os') {
            $referente = $contaReceber->descricao;
            $tipoReferente = 'adiantamento';
        } elseif ($contaReceber->descricao && stripos($contaReceber->descricao, 'Venda PDV') !== false) {
            $referente = $contaReceber->descricao;
            $tipoReferente = 'venda_pdv';
        }

        $empresa = Empresa::first();
        $valorRecebido = (float) ($contaReceber->valor_recebido ?? $contaReceber->valor);
        $dataRecebimento = $contaReceber->data_recebimento
            ? $contaReceber->data_recebimento->format('d/m/Y')
            : ($contaReceber->data_vencimento ? $contaReceber->data_vencimento->format('d/m/Y') : '-');

        $formaPagamentoTexto = optional($contaReceber->formaPagamento)->nome ?? '-';
        $forma = $contaReceber->formaPagamento;
        if ($forma && ($forma->tipo ?? '') === 'cartao_credito' && (int) $contaReceber->total_parcelas > 1) {
            $formaPagamentoTexto = 'Cartão de Crédito ' . (int) $contaReceber->total_parcelas . ' vezes';
        }

        $valorPorExtenso = function_exists('valor_por_extenso_ptbr') ? valor_por_extenso_ptbr($valorRecebido) : '';

        $html = view('financeiro.contas-receber.recibo-pagamento', [
            'conta' => $contaReceber,
            'empresa' => $empresa,
            'cliente' => $contaReceber->cliente,
            'referente' => $referente,
            'tipoReferente' => $tipoReferente,
            'valorRecebido' => $valorRecebido,
            'valorPorExtenso' => $valorPorExtenso,
            'dataRecebimento' => $dataRecebimento,
            'formaPagamento' => $contaReceber->formaPagamento,
            'formaPagamentoTexto' => $formaPagamentoTexto,
        ])->render();

        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadHTML($html);
        $pdf->setPaper('A4', 'portrait');

        $nomeArquivo = 'recibo-pagamento-' . $contaReceber->id . '-' . now()->format('Y-m-d') . '.pdf';
        return $pdf->stream($nomeArquivo);
    }

    public function create()
    {
        $clientes = Cliente::orderBy('nome')->select('id', 'nome', 'tipo_pessoa', 'cpf', 'cnpj', 'razao_social', 'grupo_economico_id')->get();
        $ordensServico = OrdemServico::where('financeiro_gerado', false)
            ->orderByDesc('created_at')
            ->get(['id', 'codigo_interno', 'cliente_id', 'total_geral']);
        $formasPagamento = FormaPagamento::where('ativo', true)
            ->with(['adquirentes' => fn ($q) => $q->where('ativo', true)->orderBy('nome')])
            ->orderBy('nome')
            ->get();
        $contasBancarias = ContaBancaria::where('ativo', true)->orderBy('nome')->get();
        $planoContas = PlanoConta::where('tipo', 'receita')
            ->where('ativo', true)
            ->orderBy('nome')
            ->get();
        $centrosCusto = CentroCusto::where('ativo', true)->orderBy('ordem')->orderBy('nome')->get();
        $categoriaFinanceiraOpcoes = CategoriaFinanceira::opcoesParaSelect('receber', old('categoria_financeira_id') ? (int) old('categoria_financeira_id') : null);
        $oldTagIds = collect(old('tag_ids', []))->map(fn ($v) => (int) $v)->filter(fn ($v) => $v > 0)->unique()->values()->all();
        $tagsFormulario = !empty($oldTagIds)
            ? Tag::query()->whereIn('id', $oldTagIds)->get()
            : collect();

        $bandeirasCartao = TaxaAdquirente::query()
            ->where('ativo', true)
            ->whereNotNull('bandeira')
            ->where('bandeira', '!=', '')
            ->distinct()
            ->orderBy('bandeira')
            ->pluck('bandeira');
        if ($bandeirasCartao->isEmpty()) {
            $bandeirasCartao = collect(['master', 'visa', 'elo', 'amex', 'hipercard', 'outros']);
        }

        $formasCartaoMeta = $this->montarFormasCartaoMetaForView($formasPagamento);

        return erp_view('financeiro.contas-receber.create', [
            'title' => 'Nova Conta a Receber',
            'clientes' => $clientes,
            'ordensServico' => $ordensServico,
            'formasPagamento' => $formasPagamento,
            'formasCartaoMeta' => $formasCartaoMeta,
            'bandeirasCartao' => $bandeirasCartao,
            'contasBancarias' => $contasBancarias,
            'planoContas' => $planoContas,
            'centrosCusto' => $centrosCusto,
            'categoriaFinanceiraOpcoes' => $categoriaFinanceiraOpcoes,
            'tagsFormulario' => $tagsFormulario,
        ]);
    }

    public function previewTaxaCartao(Request $request)
    {
        $data = $this->validateWithFilters($request, [
            'valor' => 'required|numeric|min:0',
            'forma_pagamento_id' => 'required|exists:formas_pagamento,id',
            'adquirente_id' => 'required|exists:adquirentes,id',
            'bandeira' => 'nullable|string|max:32',
            'numero_parcelas' => 'nullable|integer|min:1|max:120',
            'cartao_antecipacao' => 'nullable|boolean',
            'data_referencia' => 'nullable|date',
        ]);

        $forma = FormaPagamento::findOrFail($data['forma_pagamento_id']);
        $tipo = strtolower((string) $forma->tipo);
        if (! in_array($tipo, ['cartao_credito', 'cartao_debito'], true)) {
            return response()->json(['ok' => false, 'message' => 'Forma de pagamento não é cartão.'], 422);
        }

        $bandeira = strtolower(trim((string) ($data['bandeira'] ?? 'master'))) ?: 'master';
        $parcelas = max(1, (int) ($data['numero_parcelas'] ?? 1));
        $antecipado = (bool) ($data['cartao_antecipacao'] ?? false) && $tipo === 'cartao_credito';
        $dataRef = isset($data['data_referencia']) ? Carbon::parse($data['data_referencia']) : Carbon::now();

        $est = app(AdquirenteService::class)->estimativaTaxaRecebimento(
            (float) $data['valor'],
            $parcelas,
            (int) $data['adquirente_id'],
            $tipo,
            $bandeira,
            $antecipado,
            $dataRef
        );

        if ($est === null) {
            return response()->json([
                'ok' => false,
                'message' => 'Não foi possível calcular taxa para esta combinação. Verifique as taxas cadastradas no adquirente ou use a taxa da forma de pagamento.',
            ]);
        }

        return response()->json([
            'ok' => true,
            'taxa' => $est['taxa'],
            'valor_liquido' => $est['valor_liquido'],
            'adquirente_nome' => $est['adquirente_nome'],
            'bandeira' => $bandeira,
        ]);
    }

    public function store(Request $request)
    {
        $validated = $this->validateWithFilters($request, [
            'cliente_id' => 'required|exists:clientes,id',
            'ordem_servico_id' => 'nullable|exists:ordem_servicos,id',
            'descricao' => 'required|string|max:255',
            'valor' => 'required|numeric|min:0.01',
            'data_vencimento' => 'required|date',
            'numero_parcelas' => 'nullable|integer|min:1|max:120',
            'dias_entre_parcelas' => 'nullable|integer|min:1',
            'forma_pagamento_id' => 'nullable|exists:formas_pagamento,id',
            'adquirente_id' => 'nullable|exists:adquirentes,id',
            'bandeira' => 'nullable|string|max:32',
            'cartao_antecipacao' => 'nullable|boolean',
            'conta_bancaria_id' => 'nullable|exists:contas_bancarias,id',
            'plano_conta_id' => 'nullable|exists:plano_contas,id',
            'centro_custo_id' => 'nullable|exists:centros_custos,id',
            'categoria_financeira_id' => [
                'nullable',
                Rule::exists('categorias_financeiras', 'id')->where('escopo', 'receber'),
            ],
            'tag_ids' => 'nullable|array',
            'tag_ids.*' => 'integer|exists:tags,id',
            'observacoes' => 'nullable|string',
            'anexos' => 'nullable|array',
            'anexos.*' => 'file|max:10240',
        ]);

        $formaPagamentoStore = $validated['forma_pagamento_id']
            ? FormaPagamento::find($validated['forma_pagamento_id'])
            : null;
        if ($formaPagamentoStore && in_array($formaPagamentoStore->tipo, ['cartao_credito', 'cartao_debito'], true)) {
            if (empty($validated['adquirente_id'])) {
                return redirect()->back()
                    ->withErrors(['adquirente_id' => 'Selecione o adquirente para cartão de crédito ou débito.'])
                    ->withInput();
            }
        }

        $tagIdsSync = $this->filtrarTagIdsParaContaReceber($request->input('tag_ids', []));

        $antecipadoStore = (bool) ($validated['cartao_antecipacao'] ?? false);
        $tipoFpStore = $formaPagamentoStore ? strtolower((string) $formaPagamentoStore->tipo) : '';
        $recebimentoCartaoAutomatico = $formaPagamentoStore && in_array($tipoFpStore, ['cartao_credito', 'cartao_debito'], true)
            && ($tipoFpStore === 'cartao_debito' || ($tipoFpStore === 'cartao_credito' && $antecipadoStore));

        // Determinar conta bancária padrão (da forma de pagamento ou informada) — antes do transaction para validar recebimento auto
        $contaBancariaId = $validated['conta_bancaria_id'] ?? null;
        if (! $contaBancariaId && $validated['forma_pagamento_id']) {
            $formaPagamento = FormaPagamento::find($validated['forma_pagamento_id']);
            if ($formaPagamento && $formaPagamento->conta_bancaria_id) {
                $contaBancariaId = $formaPagamento->conta_bancaria_id;
            }
        }

        if ($recebimentoCartaoAutomatico && ! $contaBancariaId) {
            return redirect()->back()
                ->withErrors(['conta_bancaria_id' => 'Informe a conta bancária para registrar o recebimento, as taxas e a conciliação do cartão.'])
                ->withInput();
        }

        if ($recebimentoCartaoAutomatico) {
            $auditSvc = app(AuditCancelExcluirService::class);
            if (! $auditSvc->canBaixar(auth()->user(), 'conta_receber')) {
                return redirect()->back()
                    ->withErrors(['cartao_antecipacao' => 'Sem permissão para registrar recebimento automático de cartão.'])
                    ->withInput();
            }
        }

        DB::beginTransaction();
        try {
            $parcelas = $request->numero_parcelas ?? 1;
            $diasEntreParcelas = $request->dias_entre_parcelas ?? 30;
            $dataBase = \Carbon\Carbon::parse($validated['data_vencimento']);
            
            $valorTotal = (float)$validated['valor'];
            
            // Calcular valor de cada parcela garantindo que a soma seja exata
            // Usar cálculo em centavos para evitar problemas de ponto flutuante
            $valorTotalCentavos = (int)round($valorTotal * 100, 0);
            $valorParcelaCentavos = (int)($valorTotalCentavos / $parcelas);
            $restoCentavos = $valorTotalCentavos % $parcelas; // Resto da divisão inteira

            $adquirenteIdStore = isset($validated['adquirente_id']) ? (int) $validated['adquirente_id'] : null;
            $bandeiraStore = strtolower(trim((string) ($validated['bandeira'] ?? 'master'))) ?: 'master';
            /** @var AdquirenteService $adqSvc */
            $adqSvc = app(AdquirenteService::class);
            $ehCartaoStore = $formaPagamentoStore && in_array($formaPagamentoStore->tipo, ['cartao_credito', 'cartao_debito'], true);

            $dataBaixaRecebimento = now()->toDateString();

            $contasCriadas = [];
            for ($i = 1; $i <= $parcelas; $i++) {
                // Distribuir o resto nas primeiras parcelas para garantir soma exata
                $valorParcelaCentavosAtual = $valorParcelaCentavos;
                if ($i <= $restoCentavos) {
                    $valorParcelaCentavosAtual += 1; // Adiciona 1 centavo nas primeiras parcelas
                }
                // Converter de centavos para reais (garantindo 2 casas decimais)
                $valorParcela = $valorParcelaCentavosAtual / 100;
                
                $observacoes = $validated['observacoes'] ?? '';
                $linhaTaxaEst = '';
                if ($formaPagamentoStore) {
                    $tipoFp = strtolower((string) $formaPagamentoStore->tipo);
                    $estParcela = null;
                    if ($adquirenteIdStore && in_array($tipoFp, ['cartao_credito', 'cartao_debito'], true)) {
                        $estParcela = $adqSvc->estimativaTaxaRecebimento(
                            $valorParcela,
                            $parcelas,
                            $adquirenteIdStore,
                            $tipoFp,
                            $bandeiraStore,
                            $antecipadoStore && $tipoFp === 'cartao_credito',
                            $dataBase->copy()->addDays(($i - 1) * $diasEntreParcelas)
                        );
                    }
                    if ($estParcela !== null) {
                        $linhaTaxaEst = 'Taxa estimada ('.$estParcela['adquirente_nome'].', '.$bandeiraStore.'): R$ '
                            .number_format($estParcela['taxa'], 2, ',', '.')
                            .' | Líquido estimado: R$ '.number_format($estParcela['valor_liquido'], 2, ',', '.');
                    } else {
                        $txF = $formaPagamentoStore->calcularTaxa($valorParcela);
                        if ($txF > 0) {
                            $linhaTaxaEst = 'Taxa estimada ('.$formaPagamentoStore->nome.'): R$ '.number_format($txF, 2, ',', '.');
                        }
                    }
                }
                if ($linhaTaxaEst !== '' && $i === 1) {
                    $observacoes = $observacoes !== '' ? $observacoes."\n".$linhaTaxaEst : $linhaTaxaEst;
                }

                $conta = ContaReceber::create([
                    'cliente_id' => $validated['cliente_id'],
                    'ordem_servico_id' => $validated['ordem_servico_id'] ?? null,
                    'descricao' => $parcelas > 1 ? "{$validated['descricao']} - Parcela {$i}/{$parcelas}" : $validated['descricao'],
                    'valor' => $valorParcela,
                    'valor_original' => $valorParcela,
                    'numero_parcela' => $i,
                    'total_parcelas' => $parcelas,
                    'data_vencimento' => $dataBase->copy()->addDays(($i - 1) * $diasEntreParcelas),
                    'forma_pagamento_id' => $validated['forma_pagamento_id'] ?? null,
                    'conta_bancaria_id' => $contaBancariaId,
                    'adquirente_id' => $ehCartaoStore ? $adquirenteIdStore : null,
                    'bandeira' => $ehCartaoStore ? $bandeiraStore : null,
                    'plano_conta_id' => $validated['plano_conta_id'] ?? null,
                    'centro_custo_id' => $validated['centro_custo_id'] ?? null,
                    'categoria_financeira_id' => $validated['categoria_financeira_id'] ?? null,
                    'status' => 'aberto',
                    'observacoes' => $observacoes ?: null,
                    'created_by' => auth()->id(),
                ]);
                if (!empty($tagIdsSync)) {
                    $conta->tags()->sync($tagIdsSync);
                }
                if ($recebimentoCartaoAutomatico && $formaPagamentoStore && $adquirenteIdStore && $contaBancariaId) {
                    $this->registrarRecebimentoCartaoNaCriacao(
                        $conta,
                        $formaPagamentoStore,
                        (int) $contaBancariaId,
                        $adquirenteIdStore,
                        $bandeiraStore,
                        $tipoFpStore === 'cartao_credito' && $antecipadoStore,
                        $dataBaixaRecebimento,
                        trim((string) ($validated['observacoes'] ?? ''))
                    );
                }
                $contasCriadas[] = $conta;
            }

            // Marcar OS como financeiro gerado se vinculada
            $ordemServicoIdStore = $validated['ordem_servico_id'] ?? null;
            if ($ordemServicoIdStore) {
                OrdemServico::where('id', $ordemServicoIdStore)
                    ->update(['financeiro_gerado' => true]);
            }

            // Anexos: apenas quando for uma única parcela
            if (count($contasCriadas) === 1) {
                $this->salvarAnexosContaReceber($contasCriadas[0], $request);
            }

            DB::commit();
            $msgSucesso = 'Conta(s) a receber criada(s) com sucesso!';
            if ($recebimentoCartaoAutomatico) {
                $msgSucesso .= ' Recebimento de cartão lançado com taxas descontadas e conciliação conforme regras do adquirente.';
            }

            return redirect()->route('financeiro.contas-receber.index')
                ->with('success', $msgSucesso);
        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()
                ->withInput()
                ->with('error', 'Erro ao criar conta: ' . $e->getMessage());
        }
    }

    public function edit(ContaReceber $contaReceber)
    {
        $contaReceber->load([
            'cliente',
            'ordemServico',
            'formaPagamento',
            'contaBancaria',
            'planoConta',
            'tags',
            'baixasTodas.movimentacao',
            'anexos',
            'children',
        ]);
        
        $clientes = Cliente::orderBy('nome')->select('id', 'nome', 'tipo_pessoa', 'cpf', 'cnpj', 'razao_social', 'grupo_economico_id')->get();
        $formasPagamento = FormaPagamento::where('ativo', true)
            ->with(['adquirentes' => fn ($q) => $q->where('ativo', true)->orderBy('nome')])
            ->orderBy('nome')
            ->get();
        $contasBancarias = ContaBancaria::where('ativo', true)->orderBy('nome')->get();
        $planoContas = PlanoConta::where('tipo', 'receita')
            ->where('ativo', true)
            ->orderBy('nome')
            ->get();
        $centrosCusto = CentroCusto::where('ativo', true)->orderBy('ordem')->orderBy('nome')->get();

        $bandeirasCartao = TaxaAdquirente::query()
            ->where('ativo', true)
            ->whereNotNull('bandeira')
            ->where('bandeira', '!=', '')
            ->distinct()
            ->orderBy('bandeira')
            ->pluck('bandeira');
        if ($bandeirasCartao->isEmpty()) {
            $bandeirasCartao = collect(['master', 'visa', 'elo', 'amex', 'hipercard', 'outros']);
        }
        $formasCartaoMeta = $this->montarFormasCartaoMetaForView($formasPagamento);

        $jurosMultaSugeridos = ConfiguracoesFinanceiroController::calcularJurosMultaSugeridos(
            (float) ($contaReceber->valor_original ?? $contaReceber->valor),
            $contaReceber->data_vencimento->format('Y-m-d')
        );

        $selCat = old('categoria_financeira_id', $contaReceber->categoria_financeira_id);
        $categoriaFinanceiraOpcoes = CategoriaFinanceira::opcoesParaSelect('receber', $selCat ? (int) $selCat : null);
        $rawTagIds = old('tag_ids', $contaReceber->tags->pluck('id')->all());
        $oldTagIds = collect($rawTagIds)->map(fn ($v) => (int) $v)->filter(fn ($v) => $v > 0)->unique()->values()->all();
        $tagsFormulario = !empty($oldTagIds)
            ? Tag::query()->whereIn('id', $oldTagIds)->get()
            : collect();

        return erp_view('financeiro.contas-receber.edit', [
            'title' => 'Editar Conta a Receber',
            'conta' => $contaReceber,
            'clientes' => $clientes,
            'formasPagamento' => $formasPagamento,
            'formasCartaoMeta' => $formasCartaoMeta,
            'bandeirasCartao' => $bandeirasCartao,
            'contasBancarias' => $contasBancarias,
            'planoContas' => $planoContas,
            'centrosCusto' => $centrosCusto,
            'juros_sugerido' => $jurosMultaSugeridos['juros'],
            'multa_sugerido' => $jurosMultaSugeridos['multa'],
            'categoriaFinanceiraOpcoes' => $categoriaFinanceiraOpcoes,
            'tagsFormulario' => $tagsFormulario,
        ]);
    }

    public function update(Request $request, ContaReceber $contaReceber)
    {
        $validated = $this->validateWithFilters($request, [
            'cliente_id' => 'required|exists:clientes,id',
            'descricao' => 'required|string|max:255',
            'valor' => 'required|numeric|min:0.01',
            'data_vencimento' => 'required|date',
            'forma_pagamento_id' => 'nullable|exists:formas_pagamento,id',
            'adquirente_id' => 'nullable|exists:adquirentes,id',
            'bandeira' => 'nullable|string|max:32',
            'conta_bancaria_id' => 'nullable|exists:contas_bancarias,id',
            'plano_conta_id' => 'nullable|exists:plano_contas,id',
            'centro_custo_id' => 'nullable|exists:centros_custos,id',
            'categoria_financeira_id' => [
                'nullable',
                Rule::exists('categorias_financeiras', 'id')->where('escopo', 'receber'),
            ],
            'tag_ids' => 'nullable|array',
            'tag_ids.*' => 'integer|exists:tags,id',
            'juros' => 'nullable|numeric|min:0',
            'multa' => 'nullable|numeric|min:0',
            'desconto' => 'nullable|numeric|min:0',
            'observacoes' => 'nullable|string',
            'anexos' => 'nullable|array',
            'anexos.*' => 'file|max:10240',
        ]);

        $formaUp = isset($validated['forma_pagamento_id']) && $validated['forma_pagamento_id']
            ? FormaPagamento::find($validated['forma_pagamento_id'])
            : null;
        if ($formaUp && in_array($formaUp->tipo, ['cartao_credito', 'cartao_debito'], true)) {
            if (empty($validated['adquirente_id'])) {
                return redirect()->back()
                    ->withErrors(['adquirente_id' => 'Selecione o adquirente para cartão de crédito ou débito.'])
                    ->withInput();
            }
        }

        $bandeiraUp = strtolower(trim((string) ($validated['bandeira'] ?? '')));
        if ($bandeiraUp === '') {
            $bandeiraUp = 'master';
        }

        $ehCartaoUp = $formaUp && in_array($formaUp->tipo, ['cartao_credito', 'cartao_debito'], true);

        $contaReceber->update([
            'cliente_id' => $validated['cliente_id'],
            'descricao' => $validated['descricao'],
            'valor' => $validated['valor'],
            'valor_original' => $validated['valor'],
            'data_vencimento' => $validated['data_vencimento'],
            'forma_pagamento_id' => $validated['forma_pagamento_id'] ?? null,
            'adquirente_id' => $ehCartaoUp ? ($validated['adquirente_id'] ?? null) : null,
            'bandeira' => $ehCartaoUp ? $bandeiraUp : null,
            'conta_bancaria_id' => $validated['conta_bancaria_id'] ?? null,
            'plano_conta_id' => $validated['plano_conta_id'] ?? null,
            'centro_custo_id' => $validated['centro_custo_id'] ?? null,
            'categoria_financeira_id' => $validated['categoria_financeira_id'] ?? null,
            'juros' => $validated['juros'] ?? 0,
            'multa' => $validated['multa'] ?? 0,
            'desconto' => $validated['desconto'] ?? 0,
            'observacoes' => $validated['observacoes'] ?? null,
            'updated_by' => auth()->id(),
        ]);

        $tagIdsSync = $this->filtrarTagIdsParaContaReceber($request->input('tag_ids', []));
        $contaReceber->tags()->sync($tagIdsSync);

        $this->salvarAnexosContaReceber($contaReceber, $request);

        return redirect()->route('financeiro.contas-receber.index')
            ->with('success', 'Conta a receber atualizada com sucesso!');
    }

    public function storeAnexos(Request $request, ContaReceber $contaReceber)
    {
        $this->salvarAnexosContaReceber($contaReceber, $request);
        return redirect()->route('financeiro.contas-receber.edit', $contaReceber)
            ->with('success', 'Anexos adicionados com sucesso.');
    }

    public function downloadAnexo(ContaReceberAnexo $anexo)
    {
        $path = $anexo->caminho_arquivo;
        if (!$path || !Storage::disk('public')->exists($path)) {
            abort(404);
        }
        return response()->file(Storage::disk('public')->path($path), [
            'Content-Type' => $anexo->tipo_mime ?? 'application/octet-stream',
            'Content-Disposition' => 'inline; filename="' . ($anexo->nome_arquivo ?? basename($path)) . '"',
        ]);
    }

    public function destroyAnexo(ContaReceber $contaReceber, ContaReceberAnexo $anexo)
    {
        if ($anexo->conta_receber_id !== $contaReceber->id) {
            return redirect()->back()->with('error', 'Anexo não pertence a esta conta.');
        }
        if (Storage::disk('public')->exists($anexo->caminho_arquivo)) {
            Storage::disk('public')->delete($anexo->caminho_arquivo);
        }
        $anexo->delete();
        return redirect()->route('financeiro.contas-receber.edit', $contaReceber)
            ->with('success', 'Anexo excluído.');
    }

    private function salvarAnexosContaReceber(ContaReceber $conta, Request $request): void
    {
        $arquivos = $request->file('anexos');
        if ($arquivos === null) {
            $all = $request->allFiles();
            $arquivos = $all['anexos'] ?? $all['anexos[]'] ?? [];
        }
        if (!is_array($arquivos)) {
            $arquivos = $arquivos ? [$arquivos] : [];
        }
        foreach ($arquivos as $index => $arquivo) {
            if (!$arquivo instanceof \Illuminate\Http\UploadedFile || !$arquivo->isValid()) {
                continue;
            }
            if ($arquivo->getSize() > 10 * 1024 * 1024) { // 10MB
                continue;
            }
            $nomeOriginal = $arquivo->getClientOriginalName();
            $ext = $arquivo->getClientOriginalExtension() ?: pathinfo($nomeOriginal, PATHINFO_EXTENSION) ?: 'bin';
            $nomeArquivo = Str::slug(pathinfo($nomeOriginal, PATHINFO_FILENAME))
                . '_' . time() . '_' . $index . '.' . $ext;
            $caminho = $arquivo->storeAs('contas-receber/' . $conta->id, $nomeArquivo, 'public');
            if ($caminho === false) {
                continue;
            }
            $conta->anexos()->create([
                'nome_arquivo' => $nomeOriginal,
                'caminho_arquivo' => $caminho,
                'tipo_mime' => $arquivo->getMimeType(),
                'tamanho' => $arquivo->getSize(),
                'created_by' => auth()->id(),
            ]);
        }
    }

    public function baixar(Request $request, ContaReceber $contaReceber)
    {
        if ($contaReceber->estrutura_tipo === 'lote_pai') {
            return $this->baixarLoteEspelhoTotal($request, $contaReceber);
        }

        $auditSvc = app(AuditCancelExcluirService::class);
        if (!$auditSvc->canBaixar(auth()->user(), 'conta_receber')) {
            abort(403, 'Você não tem permissão para baixar contas a receber.');
        }

        if (! $request->filled('forma_pagamento_id') && $contaReceber->forma_pagamento_id) {
            $request->merge(['forma_pagamento_id' => $contaReceber->forma_pagamento_id]);
        }
        if (! $request->filled('conta_bancaria_id') && $contaReceber->conta_bancaria_id) {
            $request->merge(['conta_bancaria_id' => $contaReceber->conta_bancaria_id]);
        }
        if (! $request->filled('adquirente_id') && $contaReceber->adquirente_id) {
            $request->merge(['adquirente_id' => $contaReceber->adquirente_id]);
        }
        if (! $request->filled('bandeira') && $contaReceber->bandeira) {
            $request->merge(['bandeira' => $contaReceber->bandeira]);
        }

        if ($request->filled('forma_pagamento_id') && $request->filled('pix_chave_id')) {
            $formaPix = FormaPagamento::find($request->input('forma_pagamento_id'));
            if ($formaPix) {
                $pixChave = $formaPix->encontrarPixChavePorId($request->input('pix_chave_id'));
                if ($pixChave && !empty($pixChave['conta_bancaria_id'])) {
                    $request->merge(['conta_bancaria_id' => $pixChave['conta_bancaria_id']]);
                }
            }
        }

        $this->validateWithFilters($request, [
            'valor_baixa' => 'required|numeric|min:0.01|max:' . $contaReceber->valor_pendente,
            'data_baixa' => 'required|date',
            'conta_bancaria_id' => 'required|exists:contas_bancarias,id',
            'forma_pagamento_id' => 'required|exists:formas_pagamento,id',
            'ignorar_taxa' => 'nullable|boolean',
            'adquirente_id' => 'nullable|exists:adquirentes,id',
            'bandeira' => 'nullable|string|max:32',
            'cartao_antecipacao' => 'nullable|boolean',
            'pix_chave_id' => 'nullable|string|max:40',
            'juros' => 'nullable|numeric|min:0',
            'multa' => 'nullable|numeric|min:0',
            'desconto' => 'nullable|numeric|min:0',
            'observacoes' => 'nullable|string',
        ]);

        DB::beginTransaction();
        try {
            $valorBaixa = (float)$request->valor_baixa;
            $juros = (float)($request->juros ?? 0);
            $multa = (float)($request->multa ?? 0);
            $desconto = (float)($request->desconto ?? 0);
            $valorBrutoBaixa = $valorBaixa + $juros + $multa - $desconto;
            $ignorarTaxa = $request->boolean('ignorar_taxa');
            $formaPagamento = FormaPagamento::find($request->forma_pagamento_id);
            if (! $formaPagamento) {
                throw new \RuntimeException('Forma de pagamento inválida.');
            }
            $pixChave = $formaPagamento->encontrarPixChavePorId($request->input('pix_chave_id'));

            [$taxaBaixa, , $obsTaxaResolvida] = $this->resolverTaxaBaixaContaReceber(
                $formaPagamento,
                $contaReceber,
                $valorBrutoBaixa,
                $ignorarTaxa,
                $request->filled('adquirente_id') ? (int) $request->input('adquirente_id') : null,
                $request->input('bandeira'),
                $request->boolean('cartao_antecipacao'),
                (string) $request->input('data_baixa')
            );
            if ($pixChave && !$ignorarTaxa) {
                $taxaBaixa = round(($valorBrutoBaixa * ((float) ($pixChave['taxa_percentual'] ?? 0) / 100)) + (float) ($pixChave['taxa_fixa'] ?? 0), 2);
                $obsTaxaResolvida = $taxaBaixa > 0
                    ? ' | Taxa PIX chave "'.($pixChave['nome'] ?? 'PIX').'": R$ '.number_format($taxaBaixa, 2, ',', '.')
                    : '';
            }

            $adqIdEfetivo = (int) ($request->input('adquirente_id') ?: $contaReceber->adquirente_id);
            $adquirente = $adqIdEfetivo > 0 ? Adquirente::find($adqIdEfetivo) : null;
            if (! $adquirente) {
                $adquirente = $formaPagamento->adquirentePadrao();
            }

            $conciliacao = ConciliacaoService::calcular($formaPagamento, $adquirente, $request->data_baixa);

            $obsBase = trim($request->observacoes ?? '');
            $obsSemTaxa = $ignorarTaxa ? ' | Taxa da forma não descontada (valor já líquido / importação).' : '';
            $obsTaxa = $obsTaxaResolvida;
            $obsLiq  = $conciliacao['obs_liquidacao'] ? ' | ' . $conciliacao['obs_liquidacao'] : '';

            // Entrada = valor bruto da baixa; taxa em saída separada → saldo líquido = bruto − taxa (evita descontar a taxa duas vezes)
            $movimentacao = MovimentacaoFinanceira::create([
                'tenant_id' => $contaReceber->tenant_id ?? auth()->user()->tenant_id ?? null,
                'conta_bancaria_id' => $request->conta_bancaria_id,
                'tipo' => 'entrada',
                'origem' => 'conta_receber',
                'conta_receber_id' => $contaReceber->id,
                'plano_conta_id' => $contaReceber->plano_conta_id,
                'valor' => $valorBrutoBaixa,
                'data_movimentacao' => $request->data_baixa,
                'descricao' => "Recebimento: {$contaReceber->descricao}",
                'observacoes' => $obsBase . $obsSemTaxa . $obsTaxa . $obsLiq,
                'conciliado' => $conciliacao['conciliado'],
                'data_conciliacao' => $conciliacao['data_conciliacao'],
                'conciliado_por' => $conciliacao['conciliado'] ? auth()->id() : null,
                'created_by' => auth()->id(),
            ]);

            // Criar movimentação de saída para a taxa (se houver)
            // Taxa é imediata (desconto no crédito) → sempre conciliada com a mesma data
            $taxaMovId = null;
            if ($taxaBaixa > 0) {
                $movTaxa = MovimentacaoFinanceira::create([
                    'tenant_id' => $contaReceber->tenant_id ?? auth()->user()->tenant_id ?? null,
                    'conta_bancaria_id' => $request->conta_bancaria_id,
                    'tipo' => 'saida',
                    'origem' => 'outro',
                    'conta_receber_id' => $contaReceber->id,
                    'plano_conta_id' => $contaReceber->plano_conta_id,
                    'valor' => $taxaBaixa,
                    'data_movimentacao' => $request->data_baixa,
                    'descricao' => "Taxa de recebimento: {$contaReceber->descricao}",
                    'observacoes' => 'Taxa aplicada na baixa da conta a receber',
                    'conciliado' => $conciliacao['conciliado'],
                    'data_conciliacao' => $conciliacao['data_conciliacao'],
                    'conciliado_por' => $conciliacao['conciliado'] ? auth()->id() : null,
                    'created_by' => auth()->id(),
                ]);
                $taxaMovId = $movTaxa->id;
            }

            // Criar baixa
            BaixaTitulo::create([
                'tipo_titulo' => 'conta_receber',
                'titulo_id' => $contaReceber->id,
                'movimentacao_id' => $movimentacao->id,
                'taxa_movimentacao_id' => $taxaMovId,
                'valor_baixa' => $valorBaixa,
                'data_baixa' => $request->data_baixa,
                'juros' => $juros,
                'multa' => $multa,
                'desconto' => $desconto,
                'observacoes' => $request->observacoes,
                'created_by' => auth()->id(),
            ]);

            // Atualizar conta (valor recebido é o valor bruto, não o líquido)
            $contaReceber->valor_recebido += $valorBrutoBaixa;
            $contaReceber->juros += $juros;
            $contaReceber->multa += $multa;
            $contaReceber->desconto += $desconto;

            // Calcular valor total a receber (incluindo juros e multa, menos desconto)
            $valorTotalAReceber = $contaReceber->valor + $contaReceber->juros + $contaReceber->multa - $contaReceber->desconto;
            
            if ($contaReceber->valor_recebido >= $valorTotalAReceber) {
                $contaReceber->status = 'pago';
                $contaReceber->data_recebimento = $request->data_baixa;
            } else {
                $contaReceber->status = 'parcial';
            }

            $contaReceber->updated_by = auth()->id();
            $contaReceber->save();

            DB::commit();
            return redirect()->back()->with('success', 'Baixa realizada com sucesso!');
        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()->with('error', 'Erro ao realizar baixa: ' . $e->getMessage());
        }
    }

    public function desmembrar(Request $request, ContaReceber $contaReceber)
    {
        if (!in_array($contaReceber->status, ['aberto', 'parcial', 'vencido'], true)) {
            return back()->with('error', 'Somente contas abertas, parciais ou vencidas (em aberto) podem ser desmembradas.');
        }
        if ($contaReceber->estrutura_tipo !== 'normal') {
            return back()->with('error', 'Nao e possivel desmembrar uma conta que ja pertence a estrutura de lote/desmembramento.');
        }

        $data = $this->validateWithFilters($request, [
            'parcelas' => 'required|array|min:2',
            'parcelas.*.valor' => 'required',
            'parcelas.*.data_vencimento' => 'required|date',
            'parcelas.*.descricao' => 'nullable|string|max:255',
        ]);

        $valorPendente = (float) $contaReceber->valor_pendente;
        $somaParcelas = 0.0;
        foreach ($data['parcelas'] as $parcela) {
            $somaParcelas += $this->parseMoney($parcela['valor']);
        }

        if (abs($somaParcelas - $valorPendente) > 0.01) {
            return back()->with('error', 'A soma das parcelas deve ser igual ao valor pendente da conta.');
        }

        DB::beginTransaction();
        try {
            $contaReceber->status_estrutura = 'desmembrado';
            $contaReceber->estrutura_tipo = 'desmembrado_pai';
            $contaReceber->updated_by = auth()->id();
            $contaReceber->save();

            foreach ($data['parcelas'] as $idx => $parcela) {
                $valor = $this->parseMoney($parcela['valor']);
                ContaReceber::create([
                    'parent_id' => $contaReceber->id,
                    'estrutura_tipo' => 'desmembrado_filho',
                    'status_estrutura' => 'ativo',
                    'ordem_no_lote' => $idx + 1,
                    'ordem_servico_id' => $contaReceber->ordem_servico_id,
                    'pagamento_recorrente_id' => $contaReceber->pagamento_recorrente_id,
                    'cliente_id' => $contaReceber->cliente_id,
                    'forma_pagamento_id' => $contaReceber->forma_pagamento_id,
                    'conta_bancaria_id' => $contaReceber->conta_bancaria_id,
                    'adquirente_id' => $contaReceber->adquirente_id,
                    'bandeira' => $contaReceber->bandeira,
                    'plano_conta_id' => $contaReceber->plano_conta_id,
                    'centro_custo_id' => $contaReceber->centro_custo_id,
                    'descricao' => $parcela['descricao'] ?: $contaReceber->descricao . ' (Parcela ' . ($idx + 1) . ')',
                    'valor' => $valor,
                    'valor_original' => $valor,
                    'valor_recebido' => 0,
                    'data_vencimento' => $parcela['data_vencimento'],
                    'status' => 'aberto',
                    'observacoes' => trim(($contaReceber->observacoes ?? '') . "\nDesmembramento da conta #{$contaReceber->id}"),
                    'tenant_id' => $contaReceber->tenant_id,
                    'created_by' => auth()->id(),
                    'updated_by' => auth()->id(),
                ]);
            }

            DB::commit();
            return back()->with('success', 'Conta desmembrada com sucesso.');
        } catch (\Throwable $e) {
            DB::rollBack();
            return back()->with('error', 'Erro ao desmembrar conta: ' . $e->getMessage());
        }
    }

    public function agrupar(Request $request)
    {
        $data = $this->validateWithFilters($request, [
            'ids' => 'nullable|array|min:2',
            'ids.*' => 'required|integer|exists:contas_receber,id',
            'ids_texto' => 'nullable|string',
            'descricao' => 'required|string|max:255',
            'data_vencimento' => 'required|date',
        ]);

        $ids = $data['ids'] ?? [];
        if (empty($ids) && !empty($data['ids_texto'])) {
            $ids = collect(explode(',', (string) $data['ids_texto']))
                ->map(fn ($id) => (int) trim($id))
                ->filter(fn ($id) => $id > 0)
                ->unique()
                ->values()
                ->all();
        }
        if (count($ids) < 2) {
            return back()->with('error', 'Informe pelo menos 2 IDs para agrupar.');
        }

        $idsUnicos = array_values(array_unique(array_map('intval', $ids)));

        $contas = ContaReceber::query()
            ->whereIn('id', $idsUnicos)
            ->whereIn('status', ['aberto', 'parcial', 'vencido'])
            ->where('status_estrutura', '!=', 'agrupado')
            ->get();

        if ($contas->count() !== count($idsUnicos)) {
            return back()->with('error', 'Um ou mais IDs não puderam ser agrupados (conta inexistente, status não permitido ou já vinculada a um lote). Ajuste a seleção.');
        }

        if ($contas->count() < 2) {
            return back()->with('error', 'Selecione ao menos 2 contas abertas/parciais/vencidas para agrupar.');
        }

        $clienteId = (int) $contas->first()->cliente_id;
        if ($contas->contains(fn ($c) => (int) $c->cliente_id !== $clienteId)) {
            return back()->with('error', 'Somente contas do mesmo cliente podem ser agrupadas.');
        }
        if ($contas->contains(fn ($c) => !in_array($c->estrutura_tipo, ['normal', 'desmembrado_filho'], true))) {
            return back()->with('error', 'Somente contas normais ou desmembradas filhas podem ser agrupadas.');
        }
        $planoContaId = (int) ($contas->first()->plano_conta_id ?? 0);
        if ($contas->contains(fn ($c) => (int) ($c->plano_conta_id ?? 0) !== $planoContaId)) {
            return back()->with('error', 'Todas as contas devem ter o mesmo plano de conta para agrupar.');
        }
        $contaBancariaId = (int) ($contas->first()->conta_bancaria_id ?? 0);
        if ($contas->contains(fn ($c) => (int) ($c->conta_bancaria_id ?? 0) !== $contaBancariaId)) {
            return back()->with('error', 'Todas as contas devem ter a mesma conta bancaria para agrupar.');
        }

        $erroDesmembramento = $this->validarAgrupamentoIncluiTodosFilhosDesmembramento($contas);
        if ($erroDesmembramento !== null) {
            return back()->with('error', $erroDesmembramento);
        }

        $loteUuid = (string) Str::uuid();
        $total = (float) $contas->sum(fn ($c) => (float) $c->valor_pendente);

        DB::beginTransaction();
        try {
            $lote = ContaReceber::create([
                'estrutura_tipo' => 'lote_pai',
                'status_estrutura' => 'ativo',
                'lote_uuid' => $loteUuid,
                'cliente_id' => $clienteId,
                'descricao' => $data['descricao'],
                'valor' => $total,
                'valor_original' => $total,
                'valor_recebido' => 0,
                'data_vencimento' => $data['data_vencimento'],
                'status' => 'aberto',
                'plano_conta_id' => $contas->first()->plano_conta_id,
                'conta_bancaria_id' => $contas->first()->conta_bancaria_id,
                'forma_pagamento_id' => $contas->first()->forma_pagamento_id,
                'centro_custo_id' => $contas->first()->centro_custo_id,
                'tenant_id' => $contas->first()->tenant_id,
                'created_by' => auth()->id(),
                'updated_by' => auth()->id(),
            ]);

            foreach ($contas->values() as $idx => $conta) {
                $meta = is_array($conta->metadata) ? $conta->metadata : [];
                $meta['agrupamento_origem'] = [
                    'parent_id' => $conta->parent_id,
                    'estrutura_tipo' => $conta->estrutura_tipo,
                    'status_estrutura' => $conta->status_estrutura,
                    'ordem_no_lote' => $conta->ordem_no_lote,
                ];
                $conta->update([
                    'parent_id' => $lote->id,
                    'estrutura_tipo' => 'lote_filho',
                    'status_estrutura' => 'agrupado',
                    'lote_uuid' => $loteUuid,
                    'ordem_no_lote' => $idx + 1,
                    'metadata' => $meta,
                    'updated_by' => auth()->id(),
                ]);
            }

            DB::commit();
            return back()->with('success', 'Lote criado com espelho total.');
        } catch (\Throwable $e) {
            DB::rollBack();
            return back()->with('error', 'Erro ao agrupar contas: ' . $e->getMessage());
        }
    }

    public function desagrupar(ContaReceber $contaReceber)
    {
        if ($contaReceber->estrutura_tipo !== 'lote_pai') {
            return back()->with('error', 'Somente lotes podem ser desagrupados.');
        }
        if ($contaReceber->valor_recebido > 0 || $contaReceber->status === 'pago') {
            return back()->with('error', 'Nao e possivel desagrupar lote que ja teve baixa.');
        }

        $children = ContaReceber::query()
            ->where('parent_id', $contaReceber->id)
            ->where('estrutura_tipo', 'lote_filho')
            ->get();

        DB::beginTransaction();
        try {
            foreach ($children as $child) {
                if ($child->valor_recebido > 0 || $child->status === 'pago') {
                    throw new \RuntimeException('Ha contas filhas baixadas neste lote.');
                }
                $this->restaurarTituloAposDesagruparReceber($child);
            }

            $contaReceber->delete();
            DB::commit();
            return redirect()->route('financeiro.contas-receber.index')
                ->with('success', 'Lote desfeito com sucesso.');
        } catch (\Throwable $e) {
            DB::rollBack();
            return redirect()->route('financeiro.contas-receber.index')
                ->with('error', 'Erro ao desfazer lote: ' . $e->getMessage());
        }
    }

    /**
     * Politica B: se incluir qualquer filha de um desmembramento, deve incluir todas as filhas em aberto daquele pai.
     *
     * @param  \Illuminate\Support\Collection<int, ContaReceber>  $contas
     */
    private function validarAgrupamentoIncluiTodosFilhosDesmembramento($contas): ?string
    {
        $parentIds = $contas
            ->filter(fn (ContaReceber $c) => $c->estrutura_tipo === 'desmembrado_filho' && $c->parent_id)
            ->pluck('parent_id')
            ->unique()
            ->filter();

        foreach ($parentIds as $parentId) {
            $parentId = (int) $parentId;
            $obrigatorios = ContaReceber::query()
                ->where('parent_id', $parentId)
                ->where('estrutura_tipo', 'desmembrado_filho')
                ->whereIn('status', ['aberto', 'parcial', 'vencido'])
                ->orderBy('id')
                ->pluck('id')
                ->map(fn ($id) => (int) $id)
                ->values()
                ->all();

            $selecionados = $contas
                ->filter(fn (ContaReceber $c) => (int) ($c->parent_id ?? 0) === $parentId && $c->estrutura_tipo === 'desmembrado_filho')
                ->pluck('id')
                ->map(fn ($id) => (int) $id)
                ->sort()
                ->values()
                ->all();

            if ($selecionados === []) {
                continue;
            }

            sort($obrigatorios);
            if ($obrigatorios !== $selecionados) {
                return 'Para agrupar parcelas de um desmembramento, selecione todas as contas filhas em aberto desse desmembramento ('
                    . count($obrigatorios) . ' parcela(s), titulo pai #' . $parentId . ').';
            }
        }

        return null;
    }

    private function restaurarTituloAposDesagruparReceber(ContaReceber $child): void
    {
        $meta = is_array($child->metadata) ? $child->metadata : [];
        $origem = $meta['agrupamento_origem'] ?? null;
        unset($meta['agrupamento_origem']);

        $parentId = null;
        $estrutura = 'normal';
        $statusEst = 'ativo';
        $ordem = null;

        if (is_array($origem)) {
            $estruturaOrig = (string) ($origem['estrutura_tipo'] ?? 'normal');
            if ($estruturaOrig === 'desmembrado_filho') {
                $candidatoPai = isset($origem['parent_id']) ? (int) $origem['parent_id'] : 0;
                $paiValido = $candidatoPai > 0
                    && ContaReceber::query()
                        ->whereKey($candidatoPai)
                        ->where('estrutura_tipo', 'desmembrado_pai')
                        ->exists();
                if ($paiValido) {
                    $parentId = $candidatoPai;
                    $estrutura = 'desmembrado_filho';
                    $statusEst = (string) ($origem['status_estrutura'] ?? 'ativo');
                    $ordem = isset($origem['ordem_no_lote']) ? (int) $origem['ordem_no_lote'] : null;
                }
            }
        }

        $child->update([
            'parent_id' => $parentId,
            'estrutura_tipo' => $estrutura,
            'status_estrutura' => $statusEst,
            'lote_uuid' => null,
            'ordem_no_lote' => $ordem,
            'metadata' => $meta === [] ? null : $meta,
            'updated_by' => auth()->id(),
        ]);
    }

    private function baixarLoteEspelhoTotal(Request $request, ContaReceber $lote)
    {
        $this->validateWithFilters($request, [
            'data_baixa' => 'required|date',
            'conta_bancaria_id' => 'required|exists:contas_bancarias,id',
            'forma_pagamento_id' => 'required|exists:formas_pagamento,id',
            'observacoes' => 'nullable|string',
        ]);

        $children = ContaReceber::query()
            ->where('parent_id', $lote->id)
            ->where('estrutura_tipo', 'lote_filho')
            ->whereIn('status', ['aberto', 'parcial'])
            ->orderBy('ordem_no_lote')
            ->get();

        if ($children->isEmpty()) {
            return back()->with('error', 'Este lote nao possui contas filhas pendentes.');
        }

        DB::beginTransaction();
        try {
            $totalRecebido = 0.0;
            foreach ($children as $filho) {
                $valorBaixa = (float) $filho->valor_pendente;
                if ($valorBaixa <= 0) {
                    continue;
                }

                $mov = MovimentacaoFinanceira::create([
                    'tenant_id' => $filho->tenant_id ?? auth()->user()->tenant_id ?? null,
                    'conta_bancaria_id' => $request->conta_bancaria_id,
                    'tipo' => 'entrada',
                    'origem' => 'conta_receber',
                    'conta_receber_id' => $filho->id,
                    'plano_conta_id' => $filho->plano_conta_id,
                    'valor' => $valorBaixa,
                    'data_movimentacao' => $request->data_baixa,
                    'descricao' => 'Espelho total lote #' . $lote->id . ': ' . $filho->descricao,
                    'observacoes' => $request->observacoes,
                    'conciliado' => true,
                    'data_conciliacao' => $request->data_baixa,
                    'conciliado_por' => auth()->id(),
                    'created_by' => auth()->id(),
                ]);

                BaixaTitulo::create([
                    'tipo_titulo' => 'conta_receber',
                    'titulo_id' => $filho->id,
                    'movimentacao_id' => $mov->id,
                    'valor_baixa' => $valorBaixa,
                    'data_baixa' => $request->data_baixa,
                    'juros' => 0,
                    'multa' => 0,
                    'desconto' => 0,
                    'observacoes' => 'Baixa unificada do lote #' . $lote->id,
                    'created_by' => auth()->id(),
                ]);

                $filho->update([
                    'valor_recebido' => (float) $filho->valor,
                    'status' => 'pago',
                    'data_recebimento' => $request->data_baixa,
                    'updated_by' => auth()->id(),
                ]);

                $totalRecebido += $valorBaixa;
            }

            $lote->update([
                'valor_recebido' => $totalRecebido,
                'status' => 'pago',
                'data_recebimento' => $request->data_baixa,
                'updated_by' => auth()->id(),
            ]);

            DB::commit();
            return back()->with('success', 'Baixa unificada do lote realizada com espelho total.');
        } catch (\Throwable $e) {
            DB::rollBack();
            return back()->with('error', 'Erro ao baixar lote: ' . $e->getMessage());
        }
    }

    private function parseMoney($value): float
    {
        if (is_numeric($value)) {
            return (float) $value;
        }
        $normalized = str_replace(['.', ','], ['', '.'], (string) $value);
        return (float) $normalized;
    }

    public function estornar(Request $request, ContaReceber $contaReceber)
    {
        $auditSvc = app(AuditCancelExcluirService::class);
        if (!$auditSvc->canEstornar(auth()->user(), 'conta_receber')) {
            abort(403, 'Você não tem permissão para estornar contas a receber.');
        }

        $this->validateWithFilters($request, [
            'baixa_id' => 'required|exists:baixas_titulos,id',
            'motivo' => 'required|string|min:10',
            'modo_estorno' => 'required|in:finaliza,pagou',
            // Com modo "finaliza", os selects enviam string vazia; sem nullable o exists falha e o estorno não ocorre.
            'conta_bancaria_id_estorno' => 'nullable|required_if:modo_estorno,pagou|exists:contas_bancarias,id',
            'forma_pagamento_id_estorno' => 'nullable|required_if:modo_estorno,pagou|exists:formas_pagamento,id',
            // Valores opcionais para ajuste do estorno (quando modo_estorno = pagou)
            'acrescimo' => 'nullable|numeric|min:0',
            'juros' => 'nullable|numeric|min:0',
            'desconto' => 'nullable|numeric|min:0',
        ]);

        $baixa = BaixaTitulo::with('movimentacao')->findOrFail($request->baixa_id);

        if ($baixa->estornado) {
            return redirect()->back()->with('error', 'Esta baixa já foi estornada!');
        }

        if ($baixa->titulo_id != $contaReceber->id || $baixa->tipo_titulo != 'conta_receber') {
            return redirect()->back()->with('error', 'Baixa não pertence a esta conta!');
        }

        if (! $baixa->movimentacao) {
            return redirect()->back()->with('error', 'Movimentação financeira desta baixa não foi encontrada. Não é possível estornar.');
        }

        DB::beginTransaction();
        try {
            $modoEstorno = (string) $request->input('modo_estorno', 'finaliza');
            $extraAcrescimo = (float) ($request->input('acrescimo') ?? 0);
            $extraJuros = (float) ($request->input('juros') ?? 0);
            $extraDesconto = (float) ($request->input('desconto') ?? 0);

            // Consistência: o ContaReceber guarda os componentes (juros/multa/desconto) e o valor_recebido é o "bruto".
            // No modo "pagou", somamos ajustes ao bruto revertido.
            $jurosTotalReversao = (float) $baixa->juros + ($modoEstorno === 'pagou' ? $extraJuros : 0);
            $multaTotalReversao = (float) $baixa->multa + ($modoEstorno === 'pagou' ? $extraAcrescimo : 0);
            $descontoTotalReversao = (float) $baixa->desconto + ($modoEstorno === 'pagou' ? $extraDesconto : 0);

            $valorTotalBaixa = (float) $baixa->valor_baixa
                + $jurosTotalReversao
                + $multaTotalReversao
                - $descontoTotalReversao;

            $contaBancariaIdMov = $modoEstorno === 'pagou'
                ? (int) $request->input('conta_bancaria_id_estorno')
                : (int) $baixa->movimentacao->conta_bancaria_id;

            $observacoes = 'Motivo: ' . $request->motivo;
            if ($modoEstorno === 'pagou') {
                $formaPgtoId = (int) $request->input('forma_pagamento_id_estorno');
                $formaPgtoNome = FormaPagamento::find($formaPgtoId)?->nome;
                $observacoes .= ' | Forma de pagamento estorno: ' . ($formaPgtoNome ?? $formaPgtoId);
                $detalhes = [];
                if ($extraAcrescimo > 0) $detalhes[] = 'Acrescimo: ' . $extraAcrescimo;
                if ($extraJuros > 0) $detalhes[] = 'Juros: ' . $extraJuros;
                if ($extraDesconto > 0) $detalhes[] = 'Desconto: ' . $extraDesconto;
                $observacoes .= ' | Pagou estorno' . ' | Conta saída: ' . $contaBancariaIdMov;
                if (!empty($detalhes)) {
                    $observacoes .= ' | ' . implode(' | ', $detalhes);
                }
            }

            $movEntrada = $baixa->movimentacao;

            // Criar movimentação reversa do recebimento (saída bancária = devolução do crédito recebido)
            MovimentacaoFinanceira::create([
                'tenant_id' => $movEntrada->tenant_id,
                'conta_bancaria_id' => $contaBancariaIdMov,
                'tipo' => 'saida',
                'origem' => 'ajuste',
                'conta_receber_id' => $contaReceber->id,
                'plano_conta_id' => $movEntrada->plano_conta_id,
                'centro_custo_id' => $movEntrada->centro_custo_id,
                'valor' => $valorTotalBaixa,
                'data_movimentacao' => now(),
                'descricao' => "Estorno: {$movEntrada->descricao}",
                'observacoes' => $observacoes,
                'conciliado' => false,
                'created_by' => auth()->id(),
            ]);

            // Taxa: vínculo em taxa_movimentacao_id ou primeira saída após a entrada (sem depender do texto da descrição).
            $taxaMov = BaixaTituloTaxaMovimentacaoResolver::findTaxaMovimentacao($baixa, $movEntrada);

            if ($taxaMov) {
                MovimentacaoFinanceira::create([
                    'tenant_id' => $taxaMov->tenant_id,
                    'conta_bancaria_id' => $taxaMov->conta_bancaria_id,
                    'tipo' => 'entrada',
                    'origem' => 'ajuste',
                    'conta_receber_id' => $contaReceber->id,
                    'plano_conta_id' => $taxaMov->plano_conta_id,
                    'centro_custo_id' => $taxaMov->centro_custo_id,
                    'valor' => $taxaMov->valor,
                    'data_movimentacao' => now(),
                    'descricao' => 'Estorno: '.$taxaMov->descricao,
                    'observacoes' => 'Estorno automático da taxa vinculada à baixa #'.$baixa->id.'. '.$observacoes,
                    'conciliado' => false,
                    'created_by' => auth()->id(),
                ]);
            }

            // Marcar baixa como estornada
            $baixa->estornado = true;
            $baixa->data_estorno = now();
            $baixa->estornado_por = auth()->id();
            $baixa->motivo_estorno = $request->motivo;
            $baixa->save();

            // Atualizar conta
            $contaReceber->valor_recebido -= $valorTotalBaixa;
            $contaReceber->juros -= $jurosTotalReversao;
            $contaReceber->multa -= $multaTotalReversao;
            $contaReceber->desconto -= $descontoTotalReversao;

            // Garantir que valores não fiquem negativos
            $contaReceber->valor_recebido = max(0, $contaReceber->valor_recebido);
            $contaReceber->juros = max(0, $contaReceber->juros);
            $contaReceber->multa = max(0, $contaReceber->multa);
            $contaReceber->desconto = max(0, $contaReceber->desconto);

            // Calcular valor total a receber
            $valorTotalAReceber = $contaReceber->valor + $contaReceber->juros + $contaReceber->multa - $contaReceber->desconto;

            if ($contaReceber->valor_recebido <= 0 || $contaReceber->valor_recebido < ($contaReceber->valor - $contaReceber->desconto)) {
                $contaReceber->status = 'aberto';
                $contaReceber->data_recebimento = null;
            } else if ($contaReceber->valor_recebido >= $valorTotalAReceber) {
                $contaReceber->status = 'pago';
            } else {
                $contaReceber->status = 'parcial';
            }

            $contaReceber->updated_by = auth()->id();
            $contaReceber->save();

            DB::commit();
            return redirect()->back()->with('success', 'Estorno realizado com sucesso!');
        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()->with('error', 'Erro ao realizar estorno: ' . $e->getMessage());
        }
    }

    public function corrigirDatasBaixa(
        Request $request,
        ContaReceber $contaReceber,
        BaixaTitulo $baixa,
        CorrigirDatasBaixaContaReceberService $corrigirDatasBaixaContaReceberService,
    ) {
        if ((int) $baixa->titulo_id !== (int) $contaReceber->id || $baixa->tipo_titulo !== 'conta_receber') {
            abort(404);
        }

        $validated = $this->validateWithFilters($request, [
            'data_movimentacao' => 'required|date',
            'data_conciliacao' => 'nullable|date',
        ], [], [
            'data_movimentacao' => 'data do movimento',
            'data_conciliacao' => 'data da conciliação',
        ]);

        try {
            $corrigirDatasBaixaContaReceberService->executar(
                $contaReceber,
                $baixa,
                auth()->user(),
                $validated['data_movimentacao'],
                $validated['data_conciliacao'] ?? null,
            );
        } catch (\InvalidArgumentException $e) {
            return redirect()->back()->with('error', $e->getMessage());
        }

        return redirect()->back()->with(
            'success',
            'Datas da baixa, dos lançamentos financeiros e da data de vencimento do título foram atualizadas. Nenhum estorno foi gerado.'
        );
    }

    public function cancelar(Request $request, ContaReceber $contaReceber, AuditCancelExcluirService $auditService)
    {
        if (!$auditService->canCancel(auth()->user(), 'conta_receber')) {
            abort(403, 'Você não tem permissão para cancelar contas a receber.');
        }

        $this->validateWithFilters($request, [
            'motivo' => 'required|string|min:10',
        ]);

        if ($contaReceber->status === 'cancelado') {
            return redirect()->back()->with('error', 'Esta conta já está cancelada!');
        }

        if ($contaReceber->status === 'pago' && $contaReceber->valor_recebido > 0) {
            return redirect()->back()->with('error', 'Não é possível cancelar uma conta que já foi paga. Use o estorno primeiro.');
        }

        DB::beginTransaction();
        try {
            $contaReceber->status = 'cancelado';
            $observacoesAtuais = $contaReceber->observacoes ?? '';
            $contaReceber->observacoes = $observacoesAtuais . ($observacoesAtuais ? "\n\n" : '') . "CANCELADA em " . now()->format('d/m/Y H:i') . " - Motivo: {$request->motivo}";
            $contaReceber->updated_by = auth()->id();
            $contaReceber->save();

            $auditService->log('cancelar', 'conta_receber', $contaReceber->id, $contaReceber->descricao, $request->motivo, $request);

            DB::commit();
            return redirect()->route('financeiro.contas-receber.index')
                ->with('success', 'Conta cancelada com sucesso!');
        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()->with('error', 'Erro ao cancelar conta: ' . $e->getMessage());
        }
    }

    public function destroy(Request $request, ContaReceber $contaReceber, AuditCancelExcluirService $auditService)
    {
        if (!$auditService->canExcluir(auth()->user(), 'conta_receber')) {
            abort(403, 'Você não tem permissão para excluir contas a receber.');
        }

        $this->validateWithFilters($request, [
            'motivo' => 'required|string|min:10',
        ], [], ['motivo' => 'motivo da exclusão']);

        if ($contaReceber->status === 'pago' && $contaReceber->valor_recebido > 0) {
            return redirect()->back()->with('error', 'Não é possível excluir uma conta que já foi paga. Use o cancelamento ou estorno primeiro.');
        }

        if ($contaReceber->baixas()->count() > 0) {
            return redirect()->back()->with('error', 'Não é possível excluir uma conta que possui baixas não estornadas. Estorne as baixas primeiro.');
        }

        $descricao = $contaReceber->descricao;
        $entityId = $contaReceber->id;

        DB::beginTransaction();
        try {
            $contaReceber->baixas()->delete();
            MovimentacaoFinanceira::where('conta_receber_id', $contaReceber->id)->delete();
            $contaReceber->delete();

            $auditService->log('excluir', 'conta_receber', $entityId, $descricao, $request->motivo, $request);

            DB::commit();
            return redirect()->route('financeiro.contas-receber.index')
                ->with('success', 'Conta excluída com sucesso!');
        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()->with('error', 'Erro ao excluir conta: ' . $e->getMessage());
        }
    }

    public function baixarMassa(Request $request)
    {
        $auditSvc = app(AuditCancelExcluirService::class);
        if (!$auditSvc->canBaixar(auth()->user(), 'conta_receber')) {
            abort(403, 'Você não tem permissão para baixar contas a receber.');
        }

        $this->validateWithFilters($request, [
            'contas_ids' => 'required|array|min:1',
            'contas_ids.*' => 'exists:contas_receber,id',
            'data_baixa' => 'required|date',
            'conta_bancaria_id' => 'required|exists:contas_bancarias,id',
            'forma_pagamento_id' => 'required|exists:formas_pagamento,id',
            'ignorar_taxa' => 'nullable|boolean',
            'adquirente_id' => 'nullable|exists:adquirentes,id',
            'bandeira' => 'nullable|string|max:32',
            'cartao_antecipacao' => 'nullable|boolean',
            'pix_chave_id' => 'nullable|string|max:40',
            'observacoes' => 'nullable|string',
        ]);

        $contasIds = $request->contas_ids;
        $contas = ContaReceber::whereIn('id', $contasIds)
            ->where('status', '!=', 'pago')
            ->where('status', '!=', 'cancelado')
            ->get();

        if ($contas->isEmpty()) {
            return redirect()->back()->with('error', 'Nenhuma conta válida selecionada para baixa.');
        }

        DB::beginTransaction();
        try {
            $formaPagamento = FormaPagamento::find($request->forma_pagamento_id);
            if (! $formaPagamento) {
                return redirect()->back()->with('error', 'Forma de pagamento inválida.');
            }
            $ignorarTaxa = $request->boolean('ignorar_taxa');
            $pixChave = $formaPagamento?->encontrarPixChavePorId($request->input('pix_chave_id'));
            if ($pixChave && !empty($pixChave['conta_bancaria_id'])) {
                $request->merge(['conta_bancaria_id' => $pixChave['conta_bancaria_id']]);
            }
            $sucesso = 0;
            $erros = [];

            foreach ($contas as $conta) {
                try {
                    $valorBaixa = $conta->valor_pendente;
                    $juros = 0;
                    $multa = 0;
                    $desconto = 0;
                    $valorBrutoBaixa = $valorBaixa;

                    [$taxaBaixa, , $obsTaxaResolvida] = $this->resolverTaxaBaixaContaReceber(
                        $formaPagamento,
                        $conta,
                        $valorBrutoBaixa,
                        $ignorarTaxa,
                        $request->filled('adquirente_id') ? (int) $request->input('adquirente_id') : null,
                        $request->input('bandeira'),
                        $request->boolean('cartao_antecipacao'),
                        (string) $request->input('data_baixa')
                    );
                    if ($pixChave && !$ignorarTaxa) {
                        $taxaBaixa = round(($valorBrutoBaixa * ((float) ($pixChave['taxa_percentual'] ?? 0) / 100)) + (float) ($pixChave['taxa_fixa'] ?? 0), 2);
                        $obsTaxaResolvida = $taxaBaixa > 0
                            ? ' | Taxa PIX chave "'.($pixChave['nome'] ?? 'PIX').'": R$ '.number_format($taxaBaixa, 2, ',', '.')
                            : '';
                    }

                    $adqIdMassa = (int) ($request->input('adquirente_id') ?: $conta->adquirente_id);
                    $adquirenteConta = $adqIdMassa > 0 ? Adquirente::find($adqIdMassa) : null;
                    if (! $adquirenteConta) {
                        $adquirenteConta = $formaPagamento->adquirentePadrao();
                    }
                    $conciliacao = ConciliacaoService::calcular($formaPagamento, $adquirenteConta, $request->data_baixa);

                    $obsBase = trim($request->observacoes ?? '');
                    $obsSemTaxa = $ignorarTaxa ? ' | Taxa da forma não descontada (valor já líquido / importação).' : '';
                    $obsTaxa = $obsTaxaResolvida;
                    $obsLiq  = $conciliacao['obs_liquidacao'] ? ' | ' . $conciliacao['obs_liquidacao'] : '';

                    // Entrada = bruto; taxa em saída → líquido na conta = bruto − taxa
                    $movimentacao = MovimentacaoFinanceira::create([
                        'tenant_id' => $conta->tenant_id ?? auth()->user()->tenant_id ?? null,
                        'conta_bancaria_id' => $request->conta_bancaria_id,
                        'tipo' => 'entrada',
                        'origem' => 'conta_receber',
                        'conta_receber_id' => $conta->id,
                        'plano_conta_id' => $conta->plano_conta_id,
                        'valor' => $valorBrutoBaixa,
                        'data_movimentacao' => $request->data_baixa,
                        'descricao' => "Recebimento: {$conta->descricao}",
                        'observacoes' => $obsBase . $obsSemTaxa . $obsTaxa . $obsLiq,
                        'conciliado' => $conciliacao['conciliado'],
                        'data_conciliacao' => $conciliacao['data_conciliacao'],
                        'conciliado_por' => $conciliacao['conciliado'] ? auth()->id() : null,
                        'created_by' => auth()->id(),
                    ]);

                    // Taxa sempre com mesmo status de conciliação que o recebimento
                    $taxaMovIdMassa = null;
                    if ($taxaBaixa > 0) {
                        $movTaxaMassa = MovimentacaoFinanceira::create([
                            'tenant_id' => $conta->tenant_id ?? auth()->user()->tenant_id ?? null,
                            'conta_bancaria_id' => $request->conta_bancaria_id,
                            'tipo' => 'saida',
                            'origem' => 'outro',
                            'conta_receber_id' => $conta->id,
                            'plano_conta_id' => $conta->plano_conta_id,
                            'valor' => $taxaBaixa,
                            'data_movimentacao' => $request->data_baixa,
                            'descricao' => "Taxa de recebimento: {$conta->descricao}",
                            'observacoes' => 'Taxa aplicada na baixa da conta a receber',
                            'conciliado' => $conciliacao['conciliado'],
                            'data_conciliacao' => $conciliacao['data_conciliacao'],
                            'conciliado_por' => $conciliacao['conciliado'] ? auth()->id() : null,
                            'created_by' => auth()->id(),
                        ]);
                        $taxaMovIdMassa = $movTaxaMassa->id;
                    }

                    // Criar baixa
                    BaixaTitulo::create([
                        'tipo_titulo' => 'conta_receber',
                        'titulo_id' => $conta->id,
                        'movimentacao_id' => $movimentacao->id,
                        'taxa_movimentacao_id' => $taxaMovIdMassa,
                        'valor_baixa' => $valorBaixa,
                        'data_baixa' => $request->data_baixa,
                        'juros' => $juros,
                        'multa' => $multa,
                        'desconto' => $desconto,
                        'observacoes' => $request->observacoes,
                        'created_by' => auth()->id(),
                    ]);

                    // Atualizar conta
                    $conta->valor_recebido += $valorBrutoBaixa;
                    
                    // Calcular valor total a receber
                    $valorTotalAReceber = $conta->valor + $conta->juros + $conta->multa - $conta->desconto;
                    
                    if ($conta->valor_recebido >= $valorTotalAReceber) {
                        $conta->status = 'pago';
                        $conta->data_recebimento = $request->data_baixa;
                    } else {
                        $conta->status = 'parcial';
                    }
                    $conta->updated_by = auth()->id();
                    $conta->save();

                    $sucesso++;
                } catch (\Exception $e) {
                    $erros[] = "Conta #{$conta->id}: " . $e->getMessage();
                }
            }

            DB::commit();
            
            $mensagem = "{$sucesso} conta(s) baixada(s) com sucesso!";
            if (!empty($erros)) {
                $mensagem .= " Erros: " . implode(', ', $erros);
            }
            
            return redirect()->route('financeiro.contas-receber.index')
                ->with('success', $mensagem);
        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()->with('error', 'Erro ao realizar baixa em massa: ' . $e->getMessage());
        }
    }

    public function cancelarMassa(Request $request)
    {
        $auditSvc = app(AuditCancelExcluirService::class);
        if (!$auditSvc->canCancel(auth()->user(), 'conta_receber')) {
            abort(403, 'Você não tem permissão para cancelar contas a receber.');
        }

        $this->validateWithFilters($request, [
            'contas_ids' => 'required|array|min:1',
            'contas_ids.*' => 'exists:contas_receber,id',
            'motivo' => 'required|string|min:10',
        ]);

        $contasIds = $request->contas_ids;
        $contas = ContaReceber::whereIn('id', $contasIds)
            ->where('status', '!=', 'cancelado')
            ->where(function($query) {
                $query->where('status', '!=', 'pago')
                    ->orWhere(function($q) {
                        $q->where('status', 'pago')
                          ->where('valor_recebido', 0);
                    });
            })
            ->get();

        if ($contas->isEmpty()) {
            return redirect()->back()->with('error', 'Nenhuma conta válida selecionada para cancelamento. Verifique se as contas não estão pagas ou já canceladas.');
        }

        DB::beginTransaction();
        try {
            $sucesso = 0;
            $erros = [];
            
            foreach ($contas as $conta) {
                try {
                    if ($conta->status === 'pago' && $conta->valor_recebido > 0) {
                        $erros[] = "Conta #{$conta->id}: não pode ser cancelada pois já foi paga";
                        continue;
                    }
                    
                    $conta->status = 'cancelado';
                    $observacoesAtuais = $conta->observacoes ?? '';
                    $conta->observacoes = $observacoesAtuais . ($observacoesAtuais ? "\n\n" : '') . "CANCELADA em " . now()->format('d/m/Y H:i') . " - Motivo: {$request->motivo}";
                    $conta->updated_by = auth()->id();
                    $conta->save();
                    $sucesso++;
                } catch (\Exception $e) {
                    $erros[] = "Conta #{$conta->id}: " . $e->getMessage();
                }
            }

            DB::commit();
            
            $mensagem = "{$sucesso} conta(s) cancelada(s) com sucesso!";
            if (!empty($erros)) {
                $mensagem .= " Erros: " . implode(', ', $erros);
            }
            
            return redirect()->route('financeiro.contas-receber.index')
                ->with('success', $mensagem);
        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()->with('error', 'Erro ao cancelar contas em massa: ' . $e->getMessage());
        }
    }

    public function excluirMassa(Request $request)
    {
        $auditSvc = app(AuditCancelExcluirService::class);
        if (!$auditSvc->canExcluir(auth()->user(), 'conta_receber')) {
            abort(403, 'Você não tem permissão para excluir contas a receber.');
        }

        $this->validateWithFilters($request, [
            'contas_ids' => 'required|array|min:1',
            'contas_ids.*' => 'exists:contas_receber,id',
        ]);

        $contasIds = $request->contas_ids;
        $contas = ContaReceber::whereIn('id', $contasIds)
            ->where(function($query) {
                $query->where('status', '!=', 'pago')
                    ->orWhere('valor_recebido', 0);
            })
            ->get();

        if ($contas->isEmpty()) {
            return redirect()->back()->with('error', 'Nenhuma conta válida selecionada para exclusão.');
        }

        DB::beginTransaction();
        try {
            $sucesso = 0;
            $erros = [];

            foreach ($contas as $conta) {
                try {
                    if ($conta->baixas()->count() > 0) {
                        $erros[] = "Conta #{$conta->id}: possui baixas não estornadas";
                        continue;
                    }

                    $conta->baixas()->delete();
                    MovimentacaoFinanceira::where('conta_receber_id', $conta->id)->delete();
                    $conta->delete();
                    $sucesso++;
                } catch (\Exception $e) {
                    $erros[] = "Conta #{$conta->id}: " . $e->getMessage();
                }
            }

            DB::commit();
            
            $mensagem = "{$sucesso} conta(s) excluída(s) com sucesso!";
            if (!empty($erros)) {
                $mensagem .= " Erros: " . implode(', ', $erros);
            }
            
            return redirect()->route('financeiro.contas-receber.index')
                ->with('success', $mensagem);
        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()->with('error', 'Erro ao excluir contas em massa: ' . $e->getMessage());
        }
    }

    /**
     * Na criação: cartão de débito, ou crédito com "antecipado", gera baixa imediata com líquido/taxa e conciliação.
     */
    private function registrarRecebimentoCartaoNaCriacao(
        ContaReceber $contaReceber,
        FormaPagamento $formaPagamento,
        int $contaBancariaId,
        int $adquirenteId,
        string $bandeira,
        bool $antecipacaoCredito,
        string $dataBaixaYmd,
        string $obsBaseUsuario
    ): void {
        $valorBaixa = (float) $contaReceber->valor;
        $juros = 0.0;
        $multa = 0.0;
        $desconto = 0.0;
        $valorBrutoBaixa = $valorBaixa + $juros + $multa - $desconto;

        [$taxaBaixa, , $obsTaxaResolvida] = $this->resolverTaxaBaixaContaReceber(
            $formaPagamento,
            $contaReceber,
            $valorBrutoBaixa,
            false,
            $adquirenteId,
            $bandeira,
            $antecipacaoCredito,
            $dataBaixaYmd
        );

        $adquirente = Adquirente::find($adquirenteId) ?: $formaPagamento->adquirentePadrao();
        $cartaoDebitoConciliarNaData = strtolower((string) $formaPagamento->tipo) === 'cartao_debito';
        $conciliacao = ConciliacaoService::calcular($formaPagamento, $adquirente, $dataBaixaYmd, $cartaoDebitoConciliarNaData);

        $obsAuto = 'Recebimento registrado na criação (cartão'
            .($antecipacaoCredito ? ', crédito antecipado).' : ', débito).');
        $obsBase = trim($obsBaseUsuario);
        $obsMov = ($obsBase !== '' ? $obsBase.' | ' : '').$obsAuto.$obsTaxaResolvida;
        if (! empty($conciliacao['obs_liquidacao'])) {
            $obsMov .= ' | '.$conciliacao['obs_liquidacao'];
        }

        $movimentacao = MovimentacaoFinanceira::create([
            'tenant_id' => $contaReceber->tenant_id ?? auth()->user()->tenant_id ?? null,
            'conta_bancaria_id' => $contaBancariaId,
            'tipo' => 'entrada',
            'origem' => 'conta_receber',
            'conta_receber_id' => $contaReceber->id,
            'plano_conta_id' => $contaReceber->plano_conta_id,
            'valor' => $valorBrutoBaixa,
            'data_movimentacao' => $dataBaixaYmd,
            'descricao' => "Recebimento: {$contaReceber->descricao}",
            'observacoes' => $obsMov,
            'conciliado' => $conciliacao['conciliado'],
            'data_conciliacao' => $conciliacao['data_conciliacao'],
            'conciliado_por' => $conciliacao['conciliado'] ? auth()->id() : null,
            'created_by' => auth()->id(),
        ]);

        $taxaMovIdCriacao = null;
        if ($taxaBaixa > 0) {
            $movTaxaCriacao = MovimentacaoFinanceira::create([
                'tenant_id' => $contaReceber->tenant_id ?? auth()->user()->tenant_id ?? null,
                'conta_bancaria_id' => $contaBancariaId,
                'tipo' => 'saida',
                'origem' => 'outro',
                'conta_receber_id' => $contaReceber->id,
                'plano_conta_id' => $contaReceber->plano_conta_id,
                'valor' => $taxaBaixa,
                'data_movimentacao' => $dataBaixaYmd,
                'descricao' => "Taxa de recebimento: {$contaReceber->descricao}",
                'observacoes' => 'Taxa na criação (cartão); o valor da entrada é o bruto da parcela.',
                'conciliado' => $conciliacao['conciliado'],
                'data_conciliacao' => $conciliacao['data_conciliacao'],
                'conciliado_por' => $conciliacao['conciliado'] ? auth()->id() : null,
                'created_by' => auth()->id(),
            ]);
            $taxaMovIdCriacao = $movTaxaCriacao->id;
        }

        BaixaTitulo::create([
            'tipo_titulo' => 'conta_receber',
            'titulo_id' => $contaReceber->id,
            'movimentacao_id' => $movimentacao->id,
            'taxa_movimentacao_id' => $taxaMovIdCriacao,
            'valor_baixa' => $valorBaixa,
            'data_baixa' => $dataBaixaYmd,
            'juros' => $juros,
            'multa' => $multa,
            'desconto' => $desconto,
            'observacoes' => $obsAuto,
            'created_by' => auth()->id(),
        ]);

        $contaReceber->valor_recebido = round((float) ($contaReceber->valor_recebido ?? 0) + $valorBrutoBaixa, 2);
        $valorTotalAReceber = (float) $contaReceber->valor + (float) ($contaReceber->juros ?? 0) + (float) ($contaReceber->multa ?? 0) - (float) ($contaReceber->desconto ?? 0);
        if ($contaReceber->valor_recebido >= $valorTotalAReceber - 0.00001) {
            $contaReceber->status = 'pago';
            $contaReceber->data_recebimento = $dataBaixaYmd;
        } else {
            $contaReceber->status = 'parcial';
        }
        $contaReceber->updated_by = auth()->id();
        $contaReceber->save();
    }

    /**
     * Metadados por forma de pagamento (cartão) para o front: adquirentes e tipo normalizado.
     * Se a forma não tiver adquirentes na pivot, lista todos os adquirentes ativos (fallback).
     *
     * @param  \Illuminate\Support\Collection<int, FormaPagamento>  $formasPagamento
     * @return array<int, array{tipo: string, nome: string, adquirentes: list<array{id: int, nome: string}>}>
     */
    private function montarFormasCartaoMetaForView($formasPagamento): array
    {
        $todosAdquirentesOpcoes = Adquirente::query()
            ->where('ativo', true)
            ->with(['contas' => function ($q) {
                $q->where('contas_bancarias.ativo', true)->wherePivot('padrao', true);
            }])
            ->orderBy('nome')
            ->get()
            ->map(function (Adquirente $a) {
                $cbc = $a->contas->first();

                return [
                    'id' => $a->id,
                    'nome' => $a->nome,
                    'conta_bancaria_padrao_id' => $cbc ? (int) $cbc->id : null,
                ];
            })
            ->values()
            ->all();

        $meta = [];
        foreach ($formasPagamento as $fp) {
            $tipo = strtolower(trim((string) $fp->tipo));
            if (! in_array($tipo, ['cartao_credito', 'cartao_debito'], true)) {
                continue;
            }
            $meta[$fp->id] = [
                'tipo' => $tipo,
                'nome' => $fp->nome,
                'adquirentes' => $todosAdquirentesOpcoes,
            ];
        }

        return $meta;
    }

    /**
     * @return array{0: float, 1: float, 2: string} taxa, valor líquido, complemento para observações
     */
    private function resolverTaxaBaixaContaReceber(
        FormaPagamento $forma,
        ContaReceber $conta,
        float $valorBruto,
        bool $ignorarTaxa,
        ?int $adquirenteIdOverride,
        ?string $bandeiraOverride,
        bool $antecipado,
        string $dataBaixaYmd
    ): array {
        if ($ignorarTaxa) {
            return [0.0, $valorBruto, ''];
        }

        $tipo = strtolower((string) $forma->tipo);
        $adqId = (int) ($adquirenteIdOverride ?: $conta->adquirente_id);
        $bandeira = strtolower(trim((string) ($bandeiraOverride ?? $conta->bandeira ?? 'master')));
        if ($bandeira === '') {
            $bandeira = 'master';
        }

        $totalParcelas = max(1, (int) ($conta->total_parcelas ?? 1));
        /** @var AdquirenteService $svc */
        $svc = app(AdquirenteService::class);

        if (in_array($tipo, ['cartao_credito', 'cartao_debito'], true) && $adqId > 0) {
            $antecipadoEfetivo = $antecipado && $tipo === 'cartao_credito';
            $est = $svc->estimativaTaxaRecebimento(
                $valorBruto,
                $totalParcelas,
                $adqId,
                $tipo,
                $bandeira,
                $antecipadoEfetivo,
                Carbon::parse($dataBaixaYmd)
            );
            if ($est !== null) {
                $taxa = $est['taxa'];
                $liq = $est['valor_liquido'];
                $obs = ' | Taxa cartão ('.$est['adquirente_nome'].', '.$bandeira.'): R$ '.number_format($taxa, 2, ',', '.');
                if ($antecipadoEfetivo) {
                    $obs .= ' (antecipado)';
                }

                return [$taxa, $liq, $obs];
            }
        }

        $taxa = $forma->calcularTaxa($valorBruto);

        return [
            $taxa,
            $valorBruto - $taxa,
            $taxa > 0 ? ' | Taxa forma "'.$forma->nome.'": R$ '.number_format($taxa, 2, ',', '.') : '',
        ];
    }

    /**
     * @param  array<int, mixed>|null  $tagIds
     * @return list<int>
     */
    private function filtrarTagIdsParaContaReceber(?array $tagIds): array
    {
        $ids = collect($tagIds ?? [])->map(fn ($v) => (int) $v)->filter(fn ($v) => $v > 0)->unique()->values()->all();
        if ($ids === []) {
            return [];
        }

        return Tag::query()->whereIn('id', $ids)->paraTituloFinanceiro('conta_receber')->pluck('id')->all();
    }
}
