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
use App\Models\ContaPagar;
use App\Models\ContaPagarAnexo;
use App\Models\ContaPagarRecorrente;
use App\Models\Cliente;
use App\Models\OrdemServico;
use App\Models\FormaPagamento;
use App\Models\ContaBancaria;
use App\Models\PlanoConta;
use App\Models\CentroCusto;
use App\Models\MovimentacaoFinanceira;
use App\Models\BaixaTitulo;
use App\Models\CategoriaFinanceira;
use App\Models\Tag;
use App\Services\AuditCancelExcluirService;
use App\Services\ConciliacaoService;
use App\Services\Financeiro\CorrigirDatasBaixaContaPagarService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class ContaPagarController extends Controller
{
    public function index(Request $request)
    {
        $aba = $request->get('aba', 'contas');

        $fornecedores = Cliente::orderByRaw('COALESCE(nome, razao_social)')
            ->get(['id', 'nome', 'razao_social']);

        $formasPagamento = FormaPagamento::where('ativo', true)->orderBy('nome')->get(['id', 'nome']);
        $contasBancarias = ContaBancaria::where('ativo', true)->orderBy('nome')->get(['id', 'nome']);
        $planosContasFiltro = PlanoConta::where('tipo', 'despesa')
            ->where('ativo', true)
            ->orderByRaw('COALESCE(codigo, "")')
            ->orderBy('nome')
            ->get(['id', 'codigo', 'nome']);
        $centrosCustoFiltro = CentroCusto::where('ativo', true)->orderBy('ordem')->orderBy('nome')->get(['id', 'codigo', 'nome']);
        $categoriasFinanceirasFiltroOpcoes = CategoriaFinanceira::opcoesParaSelect('pagar');
        $tagsFinanceirasFiltro = Tag::query()->paraTituloFinanceiro('conta_pagar')->orderBy('nome')->get(['id', 'nome']);

        $contas = new \Illuminate\Pagination\LengthAwarePaginator([], 0, 15);
        $recorrentes = null;

        $hoje = now()->toDateString();
        $mesInicio = now()->startOfMonth()->toDateString();
        $mesFim = now()->endOfMonth()->toDateString();

        $basePagar = $this->applyEntityQuery(
            ContaPagar::query()->whereNotIn('status_estrutura', ['agrupado', 'desmembrado']),
            'conta_pagar'
        );

        $indicadores = [
            'totalPendente' => (clone $basePagar)->whereIn('status', ['aberto', 'parcial'])
                ->sum(DB::raw('valor - COALESCE(valor_pago, 0)')),
            'totalVencido' => (clone $basePagar)->whereIn('status', ['aberto', 'parcial'])
                ->where('data_vencimento', '<', $hoje)
                ->sum(DB::raw('valor - COALESCE(valor_pago, 0)')),
            'totalAVencer' => (clone $basePagar)->whereIn('status', ['aberto', 'parcial'])
                ->where('data_vencimento', '>=', $hoje)
                ->sum(DB::raw('valor - COALESCE(valor_pago, 0)')),
            'pagoMes' => (clone $basePagar)->where('status', 'pago')
                ->whereBetween('data_pagamento', [$mesInicio, $mesFim])
                ->sum('valor_pago'),
            'qtdPendente' => (clone $basePagar)->whereIn('status', ['aberto', 'parcial'])->count(),
            'qtdVencido' => (clone $basePagar)->whereIn('status', ['aberto', 'parcial'])
                ->where('data_vencimento', '<', $hoje)->count(),
            'vencendo7dias' => (clone $basePagar)->whereIn('status', ['aberto', 'parcial'])
                ->whereBetween('data_vencimento', [$hoje, now()->addDays(7)->toDateString()])->count(),
            'ticketMedio' => 0,
        ];
        $totalPagoMesCount = (clone $basePagar)->where('status', 'pago')
            ->whereBetween('data_pagamento', [$mesInicio, $mesFim])->count();
        $indicadores['ticketMedio'] = $totalPagoMesCount > 0
            ? $indicadores['pagoMes'] / $totalPagoMesCount : 0;

        if ($aba !== 'recorrentes') {
            $query = ContaPagar::with(['fornecedor', 'ordemServico', 'formaPagamento', 'contaBancaria', 'planoConta'])
                ->whereNotIn('status_estrutura', ['agrupado', 'desmembrado']);

            $usarAtalho = $request->filled('vencidas') || $request->filled('a_vencer') || $request->filled('pendentes');

            if (!$usarAtalho && $request->filled('status')) {
                if ($request->status === 'vencido') {
                    $query->whereIn('status', ['aberto', 'parcial'])
                        ->where('data_vencimento', '<', now()->toDateString());
                } else {
                    $query->where('status', $request->status);
                }
            }

            if ($request->filled('fornecedor_id')) {
                $query->where('fornecedor_id', $request->fornecedor_id);
            }

            if ($request->filled('forma_pagamento_id')) {
                $query->where('forma_pagamento_id', $request->forma_pagamento_id);
            }

            if ($request->filled('conta_bancaria_id')) {
                $query->where('conta_bancaria_id', $request->conta_bancaria_id);
            }

            if ($request->filled('tipo')) {
                $query->where('tipo', $request->tipo);
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

            if ($request->filled('vencidas') && $request->boolean('vencidas')) {
                $query->whereIn('status', ['aberto', 'parcial'])
                    ->where('data_vencimento', '<', now()->toDateString());
            }

            if ($request->filled('a_vencer') && $request->boolean('a_vencer')) {
                $query->whereIn('status', ['aberto', 'parcial'])
                    ->where('data_vencimento', '>=', now()->toDateString());
            }

            if ($request->filled('pendentes') && $request->boolean('pendentes')) {
                $query->whereIn('status', ['aberto', 'parcial']);
            }

            if ($request->filled('q')) {
                $termo = '%' . trim($request->q) . '%';
                $query->where(function ($q) use ($termo) {
                    $q->where('descricao', 'like', $termo)
                        ->orWhere('numero_documento', 'like', $termo)
                        ->orWhere('observacoes', 'like', $termo);
                });
            }

            if ($request->filled('valor_min')) {
                $query->where('valor', '>=', (float) str_replace([',', ' '], ['.', ''], $request->valor_min));
            }
            if ($request->filled('valor_max')) {
                $query->where('valor', '<=', (float) str_replace([',', ' '], ['.', ''], $request->valor_max));
            }

            // Ordenação: padrão por vencimento ascendente (o que vence primeiro no topo — melhor para planejar pagamentos)
            $ordenar = $request->get('ordenar', 'data_vencimento');
            $direcao = $request->get('ordenar_direcao', 'asc');
            if (!in_array($ordenar, ['data_vencimento', 'valor', 'fornecedor_id', 'created_at'])) {
                $ordenar = 'data_vencimento';
            }
            if (!in_array($direcao, ['asc', 'desc'])) {
                $direcao = 'asc';
            }
            if ($ordenar === 'fornecedor_id') {
                $query->leftJoin('clientes', 'contas_pagar.fornecedor_id', '=', 'clientes.id')
                    ->orderByRaw('COALESCE(clientes.nome, clientes.razao_social) ' . $direcao)
                    ->select('contas_pagar.*');
            } else {
                $query->orderBy($ordenar, $direcao);
            }

            $query = $this->applyEntityQuery($query, 'conta_pagar');

            $perPage = (int) $request->get('per_page', 15);
            $perPage = in_array($perPage, [15, 25, 50, 100], true) ? $perPage : 15;

            $contas = $query->paginate($perPage)->withQueryString();
        } else {
            $qRec = ContaPagarRecorrente::with(['fornecedor', 'formaPagamento', 'contaBancaria']);
            $qRec = $this->applyEntityQuery($qRec, 'conta_pagar_recorrente');
            if ($request->filled('fornecedor_id')) {
                $qRec->where('fornecedor_id', $request->fornecedor_id);
            }
            if ($request->filled('ativo')) {
                if ($request->ativo === '1') {
                    $qRec->where('ativo', true);
                } elseif ($request->ativo === '0') {
                    $qRec->where('ativo', false);
                }
            }
            if ($request->filled('tipo')) {
                $qRec->where('tipo', $request->tipo);
            }
            if ($request->filled('frequencia')) {
                $qRec->where('frequencia', $request->frequencia);
            }
            $recorrentes = $qRec->orderBy('proxima_geracao_em')->paginate(15)->withQueryString();
        }

        return erp_view('financeiro.contas-pagar.index', [
            'title' => 'Contas a Pagar',
            'aba' => $aba,
            'contas' => $contas,
            'recorrentes' => $recorrentes,
            'fornecedores' => $fornecedores,
            'formasPagamento' => $formasPagamento,
            'contasBancarias' => $contasBancarias,
            'planosContasFiltro' => $planosContasFiltro,
            'centrosCustoFiltro' => $centrosCustoFiltro,
            'categoriasFinanceirasFiltroOpcoes' => $categoriasFinanceirasFiltroOpcoes,
            'tagsFinanceirasFiltro' => $tagsFinanceirasFiltro,
            'indicadores' => $indicadores,
        ]);
    }

    /**
     * Retorna as contas a pagar geradas por uma despesa recorrente (para o modal na aba Despesas recorrentes).
     * Busca por conta_pagar_recorrente_id para não depender de texto em observações.
     */
    public function lancamentosRecorrentesRecorrente(Request $request)
    {
        $request->validate(['conta_pagar_recorrente_id' => 'required|exists:contas_pagar_recorrentes,id']);
        $recorrenteId = (int) $request->conta_pagar_recorrente_id;
        $recorrente = ContaPagarRecorrente::find($recorrenteId);
        $contas = ContaPagar::with(['formaPagamento'])
            ->where('conta_pagar_recorrente_id', $recorrenteId)
            ->orderByDesc('data_vencimento')
            ->limit(200)
            ->get();

        return response()->json([
            'recorrente_descricao' => $recorrente ? $recorrente->descricao : '',
            'html' => view('financeiro.contas-pagar._lancamentos-recorrentes-recorrente', [
                'contas' => $contas,
                'recorrente_descricao' => $recorrente ? $recorrente->descricao : '',
            ])->render(),
        ]);
    }

    public function exportarPdf(Request $request)
    {
        $query = ContaPagar::with(['fornecedor', 'formaPagamento', 'contaBancaria', 'planoConta']);

        $usarAtalho = $request->filled('vencidas') || $request->filled('a_vencer') || $request->filled('pendentes');

        if (!$usarAtalho && $request->filled('status')) {
            if ($request->status === 'vencido') {
                $query->whereIn('status', ['aberto', 'parcial'])
                    ->where('data_vencimento', '<', now()->toDateString());
            } else {
                $query->where('status', $request->status);
            }
        }
        if ($request->filled('fornecedor_id')) {
            $query->where('fornecedor_id', $request->fornecedor_id);
        }
        if ($request->filled('forma_pagamento_id')) {
            $query->where('forma_pagamento_id', $request->forma_pagamento_id);
        }
        if ($request->filled('conta_bancaria_id')) {
            $query->where('conta_bancaria_id', $request->conta_bancaria_id);
        }
        if ($request->filled('tipo')) {
            $query->where('tipo', $request->tipo);
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
        if ($request->filled('q')) {
            $termo = '%' . trim($request->q) . '%';
            $query->where(function ($q) use ($termo) {
                $q->where('descricao', 'like', $termo)
                    ->orWhere('numero_documento', 'like', $termo)
                    ->orWhere('observacoes', 'like', $termo);
            });
        }
        if ($request->filled('vencidas') && $request->boolean('vencidas')) {
            $query->whereIn('status', ['aberto', 'parcial'])
                ->where('data_vencimento', '<', now()->toDateString());
        }
        if ($request->filled('a_vencer') && $request->boolean('a_vencer')) {
            $query->whereIn('status', ['aberto', 'parcial'])
                ->where('data_vencimento', '>=', now()->toDateString());
        }
        if ($request->filled('pendentes') && $request->boolean('pendentes')) {
            $query->whereIn('status', ['aberto', 'parcial']);
        }

        $query = $this->applyEntityQuery($query, 'conta_pagar');
        $contas = $query->orderBy('data_vencimento')->limit(2000)->get();

        $totais = [
            'valor' => $contas->sum('valor'),
            'pago' => $contas->sum('valor_pago'),
            'pendente' => $contas->sum(fn ($c) => $c->valor_pendente ?? 0),
            'juros' => $contas->sum('juros'),
            'multa' => $contas->sum('multa'),
            'desconto' => $contas->sum('desconto'),
        ];

        $agrupado = $contas->groupBy(function ($c) {
            return match ($c->status) {
                'pago' => 'Pagas',
                'parcial' => 'Parcialmente Pagas',
                'cancelado' => 'Canceladas',
                default => $c->data_vencimento && $c->data_vencimento->format('Y-m-d') < now()->toDateString()
                    ? 'Vencidas' : 'Em Aberto (A Vencer)',
            };
        });

        $ordemGrupos = ['Vencidas', 'Em Aberto (A Vencer)', 'Parcialmente Pagas', 'Pagas', 'Canceladas'];
        $grupos = collect();
        foreach ($ordemGrupos as $nomeGrupo) {
            if ($agrupado->has($nomeGrupo)) {
                $itens = $agrupado->get($nomeGrupo);
                $grupos->put($nomeGrupo, [
                    'itens' => $itens,
                    'subtotais' => [
                        'valor' => $itens->sum('valor'),
                        'pago' => $itens->sum('valor_pago'),
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
        if ($request->filled('fornecedor_id')) {
            $f = Cliente::find($request->fornecedor_id);
            if ($f) $filtrosTexto[] = 'Fornecedor: ' . ($f->nome ?? $f->razao_social);
        }
        if ($request->filled('tipo')) $filtrosTexto[] = 'Tipo: ' . ucfirst($request->tipo);
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

        $html = view('financeiro.contas-pagar.pdf-completo', [
            'grupos' => $grupos,
            'totais' => $totais,
            'totalRegistros' => $contas->count(),
            'filtrosTexto' => $filtrosTexto,
        ])->render();

        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadHTML($html);
        $pdf->setPaper('A4', 'landscape');

        return $pdf->stream('contas-a-pagar-' . now()->format('Y-m-d') . '.pdf');
    }

    public function create()
    {
        $fornecedores = Cliente::orderByRaw('COALESCE(nome, razao_social)')
            ->get(['id', 'nome', 'razao_social']);
        
        $ordensServico = OrdemServico::orderByDesc('created_at')->get(['id', 'codigo_interno']);
        $formasPagamento = FormaPagamento::where('ativo', true)->orderBy('nome')->get();
        $contasBancarias = ContaBancaria::where('ativo', true)->orderBy('nome')->get();
        $planoContas = PlanoConta::where('tipo', 'despesa')
            ->where('ativo', true)
            ->orderBy('nome')
            ->get();
        $centrosCusto = CentroCusto::where('ativo', true)->orderBy('ordem')->orderBy('nome')->get();
        $categoriaFinanceiraOpcoes = CategoriaFinanceira::opcoesParaSelect('pagar', old('categoria_financeira_id') ? (int) old('categoria_financeira_id') : null);
        $oldTagIds = collect(old('tag_ids', []))->map(fn ($v) => (int) $v)->filter(fn ($v) => $v > 0)->unique()->values()->all();
        $tagsFormulario = !empty($oldTagIds)
            ? Tag::query()->whereIn('id', $oldTagIds)->get()
            : collect();

        return erp_view('financeiro.contas-pagar.create', [
            'title' => 'Nova Conta a Pagar',
            'fornecedores' => $fornecedores,
            'ordensServico' => $ordensServico,
            'formasPagamento' => $formasPagamento,
            'contasBancarias' => $contasBancarias,
            'planoContas' => $planoContas,
            'centrosCusto' => $centrosCusto,
            'categoriaFinanceiraOpcoes' => $categoriaFinanceiraOpcoes,
            'tagsFormulario' => $tagsFormulario,
        ]);
    }

    public function store(Request $request)
    {
        $validated = $this->validateWithFilters($request, [
            'fornecedor_id' => 'nullable|exists:clientes,id',
            'ordem_servico_id' => 'nullable|exists:ordem_servicos,id',
            'descricao' => 'required|string|max:255',
            'numero_documento' => 'nullable|string|max:100',
            'valor' => 'required|numeric|min:0.01',
            'data_vencimento' => 'required|date',
            'tipo' => 'required|in:operacional,insumo,outro',
            'forma_pagamento_id' => 'nullable|exists:formas_pagamento,id',
            'conta_bancaria_id' => 'nullable|exists:contas_bancarias,id',
            'plano_conta_id' => 'nullable|exists:plano_contas,id',
            'centro_custo_id' => 'nullable|exists:centros_custos,id',
            'categoria_financeira_id' => [
                'nullable',
                Rule::exists('categorias_financeiras', 'id')->where('escopo', 'pagar'),
            ],
            'tag_ids' => 'nullable|array',
            'tag_ids.*' => 'integer|exists:tags,id',
            'observacoes' => 'nullable|string',
            'anexos' => 'nullable|array',
            'anexos.*' => 'file|max:10240',
        ]);

        $tagIdsSync = $this->filtrarTagIdsParaContaPagar($request->input('tag_ids', []));

        $conta = ContaPagar::create([
            'tenant_id' => auth()->user()->tenant_id ?? null,
            'fornecedor_id' => $validated['fornecedor_id'] ?? null,
            'ordem_servico_id' => $validated['ordem_servico_id'] ?? null,
            'descricao' => $validated['descricao'],
            'numero_documento' => $validated['numero_documento'] ?? null,
            'valor' => $validated['valor'],
            'valor_original' => $validated['valor'],
            'data_vencimento' => $validated['data_vencimento'],
            'tipo' => $validated['tipo'],
            'forma_pagamento_id' => $validated['forma_pagamento_id'] ?? null,
            'conta_bancaria_id' => $validated['conta_bancaria_id'] ?? null,
            'plano_conta_id' => $validated['plano_conta_id'] ?? null,
            'centro_custo_id' => $validated['centro_custo_id'] ?? null,
            'categoria_financeira_id' => $validated['categoria_financeira_id'] ?? null,
            'status' => 'aberto',
            'observacoes' => $validated['observacoes'] ?? null,
            'created_by' => auth()->id(),
        ]);

        if (!empty($tagIdsSync)) {
            $conta->tags()->sync($tagIdsSync);
        }

        $this->salvarAnexosContaPagar($conta, $request);

        return redirect()->route('financeiro.contas-pagar.index')
            ->with('success', 'Conta a pagar criada com sucesso!');
    }

    public function edit(ContaPagar $contaPagar)
    {
        $contaPagar->load(['fornecedor', 'ordemServico', 'formaPagamento', 'contaBancaria', 'planoConta', 'tags', 'baixas.movimentacao', 'anexos', 'children']);
        
        $fornecedores = Cliente::orderByRaw('COALESCE(nome, razao_social)')
            ->get(['id', 'nome', 'razao_social']);
        
        $ordensServico = OrdemServico::orderByDesc('created_at')->get(['id', 'codigo_interno']);
        $formasPagamento = FormaPagamento::where('ativo', true)->orderBy('nome')->get();
        $contasBancarias = ContaBancaria::where('ativo', true)->orderBy('nome')->get();
        $planoContas = PlanoConta::where('tipo', 'despesa')
            ->where('ativo', true)
            ->orderBy('nome')
            ->get();
        $centrosCusto = CentroCusto::where('ativo', true)->orderBy('ordem')->orderBy('nome')->get();

        $jurosMultaSugeridos = ConfiguracoesFinanceiroController::calcularJurosMultaSugeridos(
            (float) ($contaPagar->valor_original ?? $contaPagar->valor),
            $contaPagar->data_vencimento->format('Y-m-d')
        );

        $selCat = old('categoria_financeira_id', $contaPagar->categoria_financeira_id);
        $categoriaFinanceiraOpcoes = CategoriaFinanceira::opcoesParaSelect('pagar', $selCat ? (int) $selCat : null);
        $rawTagIds = old('tag_ids', $contaPagar->tags->pluck('id')->all());
        $oldTagIds = collect($rawTagIds)->map(fn ($v) => (int) $v)->filter(fn ($v) => $v > 0)->unique()->values()->all();
        $tagsFormulario = !empty($oldTagIds)
            ? Tag::query()->whereIn('id', $oldTagIds)->get()
            : collect();

        return erp_view('financeiro.contas-pagar.edit', [
            'title' => 'Editar Conta a Pagar',
            'conta' => $contaPagar,
            'fornecedores' => $fornecedores,
            'ordensServico' => $ordensServico,
            'formasPagamento' => $formasPagamento,
            'contasBancarias' => $contasBancarias,
            'planoContas' => $planoContas,
            'centrosCusto' => $centrosCusto,
            'juros_sugerido' => $jurosMultaSugeridos['juros'],
            'multa_sugerido' => $jurosMultaSugeridos['multa'],
            'categoriaFinanceiraOpcoes' => $categoriaFinanceiraOpcoes,
            'tagsFormulario' => $tagsFormulario,
        ]);
    }

    public function update(Request $request, ContaPagar $contaPagar)
    {
        $validated = $this->validateWithFilters($request, [
            'fornecedor_id' => 'nullable|exists:clientes,id',
            'descricao' => 'required|string|max:255',
            'numero_documento' => 'nullable|string|max:100',
            'valor' => 'required|numeric|min:0.01',
            'data_vencimento' => 'required|date',
            'tipo' => 'required|in:operacional,insumo,outro',
            'forma_pagamento_id' => 'nullable|exists:formas_pagamento,id',
            'conta_bancaria_id' => 'nullable|exists:contas_bancarias,id',
            'plano_conta_id' => 'nullable|exists:plano_contas,id',
            'centro_custo_id' => 'nullable|exists:centros_custos,id',
            'categoria_financeira_id' => [
                'nullable',
                Rule::exists('categorias_financeiras', 'id')->where('escopo', 'pagar'),
            ],
            'tag_ids' => 'nullable|array',
            'tag_ids.*' => 'integer|exists:tags,id',
            'observacoes' => 'nullable|string',
            'anexos' => 'nullable|array',
            'anexos.*' => 'file|max:10240',
        ]);

        $contaPagar->update([
            'fornecedor_id' => $validated['fornecedor_id'] ?? null,
            'descricao' => $validated['descricao'],
            'numero_documento' => $validated['numero_documento'] ?? null,
            'valor' => $validated['valor'],
            'valor_original' => $validated['valor'],
            'data_vencimento' => $validated['data_vencimento'],
            'tipo' => $validated['tipo'],
            'forma_pagamento_id' => $validated['forma_pagamento_id'] ?? null,
            'conta_bancaria_id' => $validated['conta_bancaria_id'] ?? null,
            'plano_conta_id' => $validated['plano_conta_id'] ?? null,
            'centro_custo_id' => $validated['centro_custo_id'] ?? null,
            'categoria_financeira_id' => $validated['categoria_financeira_id'] ?? null,
            'observacoes' => $validated['observacoes'] ?? null,
            'updated_by' => auth()->id(),
        ]);

        $tagIdsSync = $this->filtrarTagIdsParaContaPagar($request->input('tag_ids', []));
        $contaPagar->tags()->sync($tagIdsSync);

        $this->salvarAnexosContaPagar($contaPagar, $request);

        return redirect()->route('financeiro.contas-pagar.index')
            ->with('success', 'Conta a pagar atualizada com sucesso!');
    }

    public function storeAnexos(Request $request, ContaPagar $contaPagar)
    {
        $this->salvarAnexosContaPagar($contaPagar, $request);
        return redirect()->route('financeiro.contas-pagar.edit', $contaPagar)
            ->with('success', 'Anexos adicionados com sucesso.');
    }

    public function downloadAnexo(ContaPagarAnexo $anexo)
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

    public function destroyAnexo(ContaPagar $contaPagar, ContaPagarAnexo $anexo)
    {
        if ($anexo->conta_pagar_id !== $contaPagar->id) {
            return redirect()->back()->with('error', 'Anexo não pertence a esta conta.');
        }
        if (Storage::disk('public')->exists($anexo->caminho_arquivo)) {
            Storage::disk('public')->delete($anexo->caminho_arquivo);
        }
        $anexo->delete();
        return redirect()->route('financeiro.contas-pagar.edit', $contaPagar)
            ->with('success', 'Anexo excluído.');
    }

    private function salvarAnexosContaPagar(ContaPagar $conta, Request $request): void
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
            $caminho = $arquivo->storeAs('contas-pagar/' . $conta->id, $nomeArquivo, 'public');
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

    public function baixar(Request $request, ContaPagar $contaPagar)
    {
        if ($contaPagar->estrutura_tipo === 'lote_pai') {
            return $this->baixarLoteEspelhoTotal($request, $contaPagar);
        }

        $auditSvc = app(\App\Services\AuditCancelExcluirService::class);
        if (!$auditSvc->canBaixar(auth()->user(), 'conta_pagar')) {
            abort(403, 'Você não tem permissão para baixar contas a pagar.');
        }

        $this->validateWithFilters($request, [
            'valor_baixa' => 'required|numeric|min:0.01|max:' . $contaPagar->valor_pendente,
            'data_baixa' => 'required|date',
            'conta_bancaria_id' => 'required|exists:contas_bancarias,id',
            'forma_pagamento_id' => 'required|exists:formas_pagamento,id',
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
            $valorTotalBaixa = $valorBaixa + $juros + $multa - $desconto;

            // Determinar conciliação: PIX/dinheiro/transferência = imediato;
            // outros meios seguem dias_recebimento da forma de pagamento
            $formaPagamentoBaixa = FormaPagamento::find($request->forma_pagamento_id);
            $conciliacao = $formaPagamentoBaixa
                ? ConciliacaoService::calcularSemAdquirente($formaPagamentoBaixa, $request->data_baixa)
                : ['conciliado' => true, 'data_conciliacao' => $request->data_baixa, 'obs_liquidacao' => null];

            $obsBase = trim($request->observacoes ?? '');
            $obsLiq  = $conciliacao['obs_liquidacao'] ? ($obsBase ? ' | ' : '') . $conciliacao['obs_liquidacao'] : '';

            // Criar movimentação de saída
            $movimentacao = MovimentacaoFinanceira::create([
                'tenant_id' => $contaPagar->tenant_id ?? auth()->user()->tenant_id ?? null,
                'conta_bancaria_id' => $request->conta_bancaria_id,
                'tipo' => 'saida',
                'origem' => 'conta_pagar',
                'conta_pagar_id' => $contaPagar->id,
                'plano_conta_id' => $contaPagar->plano_conta_id,
                'valor' => $valorTotalBaixa,
                'data_movimentacao' => $request->data_baixa,
                'descricao' => "Pagamento: {$contaPagar->descricao}",
                'observacoes' => $obsBase . $obsLiq,
                'conciliado' => $conciliacao['conciliado'],
                'data_conciliacao' => $conciliacao['data_conciliacao'],
                'conciliado_por' => $conciliacao['conciliado'] ? auth()->id() : null,
                'created_by' => auth()->id(),
            ]);

            // Criar baixa
            BaixaTitulo::create([
                'tipo_titulo' => 'conta_pagar',
                'titulo_id' => $contaPagar->id,
                'movimentacao_id' => $movimentacao->id,
                'valor_baixa' => $valorBaixa,
                'data_baixa' => $request->data_baixa,
                'juros' => $juros,
                'multa' => $multa,
                'desconto' => $desconto,
                'observacoes' => $request->observacoes,
                'created_by' => auth()->id(),
            ]);

            // Atualizar conta
            $contaPagar->valor_pago += $valorTotalBaixa;
            $contaPagar->juros += $juros;
            $contaPagar->multa += $multa;
            $contaPagar->desconto += $desconto;

            if ($contaPagar->valor_pago >= $contaPagar->valor) {
                $contaPagar->status = 'pago';
                $contaPagar->data_pagamento = $request->data_baixa;
            } else {
                $contaPagar->status = 'parcial';
            }

            $contaPagar->updated_by = auth()->id();
            $contaPagar->save();

            DB::commit();
            return redirect()->route('financeiro.movimentacoes.index', ['origem' => 'conta_pagar'])
                ->with('success', 'Baixa realizada com sucesso! A saída foi lançada nas Movimentações Financeiras.');
        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()->with('error', 'Erro ao realizar baixa: ' . $e->getMessage());
        }
    }

    public function desmembrar(Request $request, ContaPagar $contaPagar)
    {
        if (!in_array($contaPagar->status, ['aberto', 'parcial', 'vencido'], true)) {
            return back()->with('error', 'Somente contas abertas, parciais ou vencidas (em aberto) podem ser desmembradas.');
        }
        if ($contaPagar->estrutura_tipo !== 'normal') {
            return back()->with('error', 'Nao e possivel desmembrar uma conta que ja pertence a estrutura de lote/desmembramento.');
        }

        $data = $this->validateWithFilters($request, [
            'parcelas' => 'required|array|min:2',
            'parcelas.*.valor' => 'required',
            'parcelas.*.data_vencimento' => 'required|date',
            'parcelas.*.descricao' => 'nullable|string|max:255',
        ]);

        $valorPendente = (float) $contaPagar->valor_pendente;
        $somaParcelas = 0.0;
        foreach ($data['parcelas'] as $parcela) {
            $somaParcelas += $this->parseMoney($parcela['valor']);
        }

        if (abs($somaParcelas - $valorPendente) > 0.01) {
            return back()->with('error', 'A soma das parcelas deve ser igual ao valor pendente da conta.');
        }

        DB::beginTransaction();
        try {
            $contaPagar->status_estrutura = 'desmembrado';
            $contaPagar->estrutura_tipo = 'desmembrado_pai';
            $contaPagar->updated_by = auth()->id();
            $contaPagar->save();

            foreach ($data['parcelas'] as $idx => $parcela) {
                $valor = $this->parseMoney($parcela['valor']);
                ContaPagar::create([
                    'parent_id' => $contaPagar->id,
                    'estrutura_tipo' => 'desmembrado_filho',
                    'status_estrutura' => 'ativo',
                    'ordem_no_lote' => $idx + 1,
                    'fornecedor_id' => $contaPagar->fornecedor_id,
                    'ordem_servico_id' => $contaPagar->ordem_servico_id,
                    'forma_pagamento_id' => $contaPagar->forma_pagamento_id,
                    'conta_bancaria_id' => $contaPagar->conta_bancaria_id,
                    'plano_conta_id' => $contaPagar->plano_conta_id,
                    'centro_custo_id' => $contaPagar->centro_custo_id,
                    'conta_pagar_recorrente_id' => $contaPagar->conta_pagar_recorrente_id,
                    'descricao' => $parcela['descricao'] ?: $contaPagar->descricao . ' (Parcela ' . ($idx + 1) . ')',
                    'numero_documento' => $contaPagar->numero_documento,
                    'valor' => $valor,
                    'valor_original' => $valor,
                    'valor_pago' => 0,
                    'data_vencimento' => $parcela['data_vencimento'],
                    'status' => 'aberto',
                    'tipo' => $contaPagar->tipo,
                    'observacoes' => trim(($contaPagar->observacoes ?? '') . "\nDesmembramento da conta #{$contaPagar->id}"),
                    'tenant_id' => $contaPagar->tenant_id,
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
            'ids.*' => 'required|integer|exists:contas_pagar,id',
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

        $contas = ContaPagar::query()
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

        $fornecedorId = (int) $contas->first()->fornecedor_id;
        if ($contas->contains(fn ($c) => (int) $c->fornecedor_id !== $fornecedorId)) {
            return back()->with('error', 'Somente contas do mesmo fornecedor podem ser agrupadas.');
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
            $lote = ContaPagar::create([
                'estrutura_tipo' => 'lote_pai',
                'status_estrutura' => 'ativo',
                'lote_uuid' => $loteUuid,
                'fornecedor_id' => $fornecedorId,
                'descricao' => $data['descricao'],
                'valor' => $total,
                'valor_original' => $total,
                'valor_pago' => 0,
                'data_vencimento' => $data['data_vencimento'],
                'status' => 'aberto',
                'tipo' => $contas->first()->tipo ?? 'outro',
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

    public function desagrupar(ContaPagar $contaPagar)
    {
        if ($contaPagar->estrutura_tipo !== 'lote_pai') {
            return back()->with('error', 'Somente lotes podem ser desagrupados.');
        }
        if ($contaPagar->valor_pago > 0 || $contaPagar->status === 'pago') {
            return back()->with('error', 'Nao e possivel desagrupar lote que ja teve baixa.');
        }

        $children = ContaPagar::query()
            ->where('parent_id', $contaPagar->id)
            ->where('estrutura_tipo', 'lote_filho')
            ->get();

        DB::beginTransaction();
        try {
            foreach ($children as $child) {
                if ($child->valor_pago > 0 || $child->status === 'pago') {
                    throw new \RuntimeException('Ha contas filhas baixadas neste lote.');
                }
                $this->restaurarTituloAposDesagruparPagar($child);
            }

            $contaPagar->delete();
            DB::commit();
            return redirect()->route('financeiro.contas-pagar.index')
                ->with('success', 'Lote desfeito com sucesso.');
        } catch (\Throwable $e) {
            DB::rollBack();
            return redirect()->route('financeiro.contas-pagar.index')
                ->with('error', 'Erro ao desfazer lote: ' . $e->getMessage());
        }
    }

    /**
     * Politica B: se incluir qualquer filha de um desmembramento, deve incluir todas as filhas em aberto daquele pai.
     *
     * @param  \Illuminate\Support\Collection<int, ContaPagar>  $contas
     */
    private function validarAgrupamentoIncluiTodosFilhosDesmembramento($contas): ?string
    {
        $parentIds = $contas
            ->filter(fn (ContaPagar $c) => $c->estrutura_tipo === 'desmembrado_filho' && $c->parent_id)
            ->pluck('parent_id')
            ->unique()
            ->filter();

        foreach ($parentIds as $parentId) {
            $parentId = (int) $parentId;
            $obrigatorios = ContaPagar::query()
                ->where('parent_id', $parentId)
                ->where('estrutura_tipo', 'desmembrado_filho')
                ->whereIn('status', ['aberto', 'parcial', 'vencido'])
                ->orderBy('id')
                ->pluck('id')
                ->map(fn ($id) => (int) $id)
                ->values()
                ->all();

            $selecionados = $contas
                ->filter(fn (ContaPagar $c) => (int) ($c->parent_id ?? 0) === $parentId && $c->estrutura_tipo === 'desmembrado_filho')
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

    private function restaurarTituloAposDesagruparPagar(ContaPagar $child): void
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
                    && ContaPagar::query()
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

    private function baixarLoteEspelhoTotal(Request $request, ContaPagar $lote)
    {
        $this->validateWithFilters($request, [
            'data_baixa' => 'required|date',
            'conta_bancaria_id' => 'required|exists:contas_bancarias,id',
            'forma_pagamento_id' => 'required|exists:formas_pagamento,id',
            'observacoes' => 'nullable|string',
        ]);

        $children = ContaPagar::query()
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
            $totalPago = 0.0;
            foreach ($children as $filho) {
                $valorBaixa = (float) $filho->valor_pendente;
                if ($valorBaixa <= 0) {
                    continue;
                }

                $mov = MovimentacaoFinanceira::create([
                    'tenant_id' => $filho->tenant_id ?? auth()->user()->tenant_id ?? null,
                    'conta_bancaria_id' => $request->conta_bancaria_id,
                    'tipo' => 'saida',
                    'origem' => 'conta_pagar',
                    'conta_pagar_id' => $filho->id,
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
                    'tipo_titulo' => 'conta_pagar',
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
                    'valor_pago' => (float) $filho->valor,
                    'status' => 'pago',
                    'data_pagamento' => $request->data_baixa,
                    'updated_by' => auth()->id(),
                ]);

                $totalPago += $valorBaixa;
            }

            $lote->update([
                'valor_pago' => $totalPago,
                'status' => 'pago',
                'data_pagamento' => $request->data_baixa,
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

    public function estornar(Request $request, ContaPagar $contaPagar)
    {
        $auditSvc = app(\App\Services\AuditCancelExcluirService::class);
        if (!$auditSvc->canEstornar(auth()->user(), 'conta_pagar')) {
            abort(403, 'Você não tem permissão para estornar contas a pagar.');
        }

        $this->validateWithFilters($request, [
            'baixa_id' => 'required|exists:baixas_titulos,id',
            'motivo' => 'required|string|min:10',
        ]);

        $baixa = BaixaTitulo::findOrFail($request->baixa_id);

        if ($baixa->estornado) {
            return redirect()->back()->with('error', 'Esta baixa já foi estornada!');
        }

        if ($baixa->titulo_id != $contaPagar->id || $baixa->tipo_titulo != 'conta_pagar') {
            return redirect()->back()->with('error', 'Baixa não pertence a esta conta!');
        }

        DB::beginTransaction();
        try {
            $valorTotalBaixa = $baixa->valor_baixa + $baixa->juros + $baixa->multa - $baixa->desconto;

            // Criar movimentação reversa
            MovimentacaoFinanceira::create([
                'conta_bancaria_id' => $baixa->movimentacao->conta_bancaria_id,
                'tipo' => 'entrada',
                'origem' => 'ajuste',
                'valor' => $valorTotalBaixa,
                'data_movimentacao' => now(),
                'descricao' => "Estorno: {$baixa->movimentacao->descricao}",
                'observacoes' => "Motivo: {$request->motivo}",
                'conciliado' => false,
                'created_by' => auth()->id(),
            ]);

            // Marcar baixa como estornada
            $baixa->estornado = true;
            $baixa->data_estorno = now();
            $baixa->estornado_por = auth()->id();
            $baixa->motivo_estorno = $request->motivo;
            $baixa->save();

            // Atualizar conta
            $contaPagar->valor_pago -= $valorTotalBaixa;
            $contaPagar->juros -= $baixa->juros;
            $contaPagar->multa -= $baixa->multa;
            $contaPagar->desconto -= $baixa->desconto;

            if ($contaPagar->valor_pago <= 0) {
                $contaPagar->status = 'aberto';
                $contaPagar->data_pagamento = null;
                $contaPagar->valor_pago = 0;
                $contaPagar->juros = 0;
                $contaPagar->multa = 0;
                $contaPagar->desconto = 0;
            } else {
                $contaPagar->status = 'parcial';
            }

            $contaPagar->updated_by = auth()->id();
            $contaPagar->save();

            DB::commit();
            return redirect()->back()->with('success', 'Estorno realizado com sucesso!');
        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()->with('error', 'Erro ao realizar estorno: ' . $e->getMessage());
        }
    }

    public function corrigirDatasBaixa(
        Request $request,
        ContaPagar $contaPagar,
        BaixaTitulo $baixa,
        CorrigirDatasBaixaContaPagarService $corrigirDatasBaixaContaPagarService,
    ) {
        if ((int) $baixa->titulo_id !== (int) $contaPagar->id || $baixa->tipo_titulo !== 'conta_pagar') {
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
            $corrigirDatasBaixaContaPagarService->executar(
                $contaPagar,
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

    public function cancelar(Request $request, ContaPagar $contaPagar, AuditCancelExcluirService $auditService)
    {
        if (!$auditService->canCancel(auth()->user(), 'conta_pagar')) {
            abort(403, 'Você não tem permissão para cancelar contas a pagar.');
        }

        $this->validateWithFilters($request, [
            'motivo' => 'required|string|min:10',
        ], [], ['motivo' => 'motivo do cancelamento']);

        if ($contaPagar->status === 'cancelado') {
            return redirect()->back()->with('error', 'Esta conta já está cancelada!');
        }

        if ($contaPagar->status === 'pago' && $contaPagar->valor_pago > 0) {
            return redirect()->back()->with('error', 'Não é possível cancelar uma conta que já foi paga. Use o estorno primeiro.');
        }

        DB::beginTransaction();
        try {
            $contaPagar->status = 'cancelado';
            $observacoesAtuais = $contaPagar->observacoes ?? '';
            $contaPagar->observacoes = $observacoesAtuais . ($observacoesAtuais ? "\n\n" : '') . "CANCELADA em " . now()->format('d/m/Y H:i') . " - Motivo: {$request->motivo}";
            $contaPagar->updated_by = auth()->id();
            $contaPagar->save();

            $auditService->log('cancelar', 'conta_pagar', $contaPagar->id, $contaPagar->descricao, $request->motivo, $request);

            DB::commit();
            return redirect()->route('financeiro.contas-pagar.index')
                ->with('success', 'Conta cancelada com sucesso!');
        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()->with('error', 'Erro ao cancelar conta: ' . $e->getMessage());
        }
    }

    public function destroy(Request $request, ContaPagar $contaPagar, AuditCancelExcluirService $auditService)
    {
        if (!$auditService->canExcluir(auth()->user(), 'conta_pagar')) {
            abort(403, 'Você não tem permissão para excluir contas a pagar.');
        }

        $this->validateWithFilters($request, [
            'motivo' => 'required|string|min:10',
        ], [], ['motivo' => 'motivo da exclusão']);

        if ($contaPagar->status === 'pago' && $contaPagar->valor_pago > 0) {
            return redirect()->back()->with('error', 'Não é possível excluir uma conta que já foi paga. Use o cancelamento ou estorno primeiro.');
        }

        if ($contaPagar->baixas()->count() > 0) {
            return redirect()->back()->with('error', 'Não é possível excluir uma conta que possui baixas não estornadas. Estorne as baixas primeiro.');
        }

        $descricao = $contaPagar->descricao;
        $entityId = $contaPagar->id;

        DB::beginTransaction();
        try {
            BaixaTitulo::where('tipo_titulo', 'conta_pagar')->where('titulo_id', $contaPagar->id)->delete();
            MovimentacaoFinanceira::where('conta_pagar_id', $contaPagar->id)->delete();
            $contaPagar->delete();

            $auditService->log('excluir', 'conta_pagar', $entityId, $descricao, $request->motivo, $request);

            DB::commit();
            return redirect()->route('financeiro.contas-pagar.index')
                ->with('success', 'Conta excluída com sucesso!');
        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()->with('error', 'Erro ao excluir conta: ' . $e->getMessage());
        }
    }

    /**
     * @param  array<int, mixed>|null  $tagIds
     * @return list<int>
     */
    private function filtrarTagIdsParaContaPagar(?array $tagIds): array
    {
        $ids = collect($tagIds ?? [])->map(fn ($v) => (int) $v)->filter(fn ($v) => $v > 0)->unique()->values()->all();
        if ($ids === []) {
            return [];
        }

        return Tag::query()->whereIn('id', $ids)->paraTituloFinanceiro('conta_pagar')->pluck('id')->all();
    }
}
