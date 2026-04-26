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
use App\Models\ContaReceber;
use App\Models\ContaPagar;
use App\Models\MovimentacaoFinanceira;
use App\Models\ContaBancaria;
use App\Models\PlanoConta;
use App\Models\CentroCusto;
use App\Models\PagamentoRecorrente;
use App\Models\Cliente;
use App\Models\PedidoVenda;
use App\Services\RelatorioVendasServicoProdutoService;
use App\Models\Produto;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class RelatorioController extends Controller
{
    public function index(Request $request)
    {
        $categorias = [
            [
                'titulo' => 'O Coração Financeiro (Obrigatórios)',
                'subtitulo' => 'Relatórios que mostram se a empresa está saudável ou à beira da falência.',
                'reportes' => [
                    [
                        'name' => 'Fluxo de Caixa (Diário e Mensal)',
                        'description' => 'Cruza saldo das contas com o que há para receber e pagar nos próximos dias/meses. O mais acessado.',
                        'route' => 'financeiro.fluxo-caixa',
                        'icon' => 'cash',
                        'pdf_route' => 'financeiro.fluxo-caixa.pdf',
                    ],
                    [
                        'name' => 'Contas a Receber',
                        'description' => 'Vencidos, vencem hoje e a vencer. Faturas de manutenção e títulos que os clientes ainda não pagaram.',
                        'route' => 'financeiro.contas-receber.index',
                        'icon' => 'receber',
                        'pdf_route' => 'financeiro.relatorios.contas-receber.pdf',
                    ],
                    [
                        'name' => 'Contas a Pagar',
                        'description' => 'Vencidos, vencem hoje e a vencer. Boletos de fornecedores e compromissos a pagar.',
                        'route' => 'financeiro.contas-pagar.index',
                        'icon' => 'pagar',
                        'pdf_route' => 'financeiro.relatorios.contas-pagar.pdf',
                    ],
                    [
                        'name' => 'DRE Gerencial',
                        'description' => 'Demonstração por competência: faturamento menos custos e despesas. Mostra lucro ou prejuízo do mês.',
                        'route' => 'financeiro.dre',
                        'icon' => 'chart',
                        'pdf_route' => 'financeiro.dre.pdf',
                    ],
                    [
                        'name' => 'Extrato de Movimentação (Conciliação)',
                        'description' => 'Lista detalhada de entradas e saídas por conta, para bater com o extrato do banco.',
                        'route' => 'financeiro.movimentacoes.index',
                        'icon' => 'mov',
                        'pdf_route' => 'financeiro.relatorios.movimentacoes.pdf',
                    ],
                    [
                        'name' => 'Balancete',
                        'description' => 'Saldos por plano de conta em uma data. Receitas e despesas conciliadas até a data.',
                        'route' => 'financeiro.relatorios.balancete',
                        'icon' => 'chart',
                        'pdf_route' => 'financeiro.relatorios.balancete.pdf',
                    ],
                    [
                        'name' => 'Razão por conta',
                        'description' => 'Lançamentos (movimentações) por plano de conta e período.',
                        'route' => 'financeiro.relatorios.razao',
                        'icon' => 'mov',
                        'pdf_route' => 'financeiro.relatorios.razao.pdf',
                    ],
                ],
            ],
            [
                'titulo' => 'Análise de Clientes e Inadimplência',
                'subtitulo' => 'Proteja o caixa com contratos recorrentes. Quem deve, há quanto tempo e quanto.',
                'reportes' => [
                    [
                        'name' => 'Inadimplência (Aging de Cobrança)',
                        'description' => 'Quem está devendo, há quantos dias (1-15, 16-30, 31-60, +60) e valor total. Guia bloqueios de serviço.',
                        'route' => 'financeiro.relatorios.inadimplencia',
                        'icon' => 'aging',
                        'pdf_route' => 'financeiro.relatorios.inadimplencia.pdf',
                    ],
                    [
                        'name' => 'Curva ABC de Clientes',
                        'description' => 'Clientes A (20% que trazem 80% da receita), B e C. Saiba quem você não pode perder.',
                        'route' => 'financeiro.relatorios.curva-abc-clientes',
                        'icon' => 'abc',
                        'pdf_route' => 'financeiro.relatorios.curva-abc-clientes.pdf',
                    ],
                    [
                        'name' => 'Receita Recorrente Mensal (MRR)',
                        'description' => 'Quanto dinheiro "garantido" entra no mês com base nos contratos ativos, sem vendas avulsas.',
                        'route' => 'financeiro.relatorios.mrr',
                        'icon' => 'mrr',
                        'pdf_route' => 'financeiro.relatorios.mrr.pdf',
                    ],
                ],
            ],
            [
                'titulo' => 'Vendas e Operação',
                'subtitulo' => 'Desempenho da equipe, o que mais vende e giro de materiais.',
                'reportes' => [
                    [
                        'name' => 'Vendas por Vendedor / Comissão',
                        'description' => 'Receita recebida por vendedor ou técnico: contas de OS (rateio por técnico) e pedidos de venda (vendedor do pedido), por data de recebimento.',
                        'route' => 'financeiro.relatorios.vendas-vendedor',
                        'icon' => 'vendedor',
                        'pdf_route' => 'financeiro.relatorios.vendas-vendedor.pdf',
                    ],
                    [
                        'name' => 'Vendas por Serviço / Produto',
                        'description' => 'Volume e valor por serviço e produto: itens de ordens de serviço (abertura no período) somados aos itens de pedidos de venda (confirmado/faturado/entregue, data do pedido).',
                        'route' => 'financeiro.relatorios.vendas-servico-produto',
                        'icon' => 'servico',
                        'pdf_route' => 'financeiro.relatorios.vendas-servico-produto.pdf',
                    ],
                    [
                        'name' => 'Receitas x Despesas (Mensal)',
                        'description' => 'Comparativo mensal de entradas e saídas conciliadas.',
                        'route' => 'financeiro.dashboard',
                        'icon' => 'compare',
                        'pdf_route' => 'financeiro.relatorios.receitas-despesas.pdf',
                    ],
                    [
                        'name' => 'Posição de Estoque e Curva ABC de Produtos',
                        'description' => 'Valor financeiro do estoque e quais itens giram mais rápido (Curva ABC por produto).',
                        'route' => 'financeiro.relatorios.estoque-posicao',
                        'icon' => 'estoque',
                        'pdf_route' => 'financeiro.relatorios.estoque-posicao.pdf',
                    ],
                ],
            ],
        ];

        if (function_exists('apply_filters')) {
            $categorias = apply_filters('financeiro.relatorios.categorias', $categorias);
        }

        // KPIs e alertas: apenas pendentes (aberto/parcial) para refletir o que falta receber/pagar
        $totalReceber = $this->applyEntityQuery(
            ContaReceber::whereIn('status', ['aberto', 'parcial']),
            'conta_receber'
        )->sum(DB::raw('valor - COALESCE(valor_recebido, 0)'));
        $totalPagar = $this->applyEntityQuery(
            ContaPagar::whereIn('status', ['aberto', 'parcial']),
            'conta_pagar'
        )->sum(DB::raw('valor - COALESCE(valor_pago, 0)'));
        $inadimplenteReceber = $this->applyEntityQuery(
            ContaReceber::where('status', 'aberto')->where('data_vencimento', '<', now()->toDateString()),
            'conta_receber'
        )->sum('valor');
        $inadimplentePagar = $this->applyEntityQuery(
            ContaPagar::where('status', 'aberto')->where('data_vencimento', '<', now()->toDateString()),
            'conta_pagar'
        )->sum('valor');
        $contasVencendo7Receber = $this->applyEntityQuery(
            ContaReceber::where('status', 'aberto')
                ->whereBetween('data_vencimento', [now()->toDateString(), now()->addDays(7)->toDateString()]),
            'conta_receber'
        )->count();
        $contasVencendo7Pagar = $this->applyEntityQuery(
            ContaPagar::where('status', 'aberto')
                ->whereBetween('data_vencimento', [now()->toDateString(), now()->addDays(7)->toDateString()]),
            'conta_pagar'
        )->count();
        $saldoContas = $this->applyEntityQuery(
            ContaBancaria::where('ativo', true),
            'conta_bancaria'
        )->get()->sum(fn ($c) => $c->saldo_atual ?? 0);

        $mesAtualInicio = now()->startOfMonth();
        $mesAtualFim = now()->endOfMonth();
        $receitasMes = $this->applyEntityQuery(
            MovimentacaoFinanceira::where('tipo', 'entrada')->where('conciliado', true)
                ->whereNull('data_cancelamento')
                ->whereBetween('data_movimentacao', [$mesAtualInicio, $mesAtualFim]),
            'movimentacao_financeira'
        )->sum('valor');
        $despesasMes = $this->applyEntityQuery(
            MovimentacaoFinanceira::where('tipo', 'saida')->where('conciliado', true)
                ->whereNull('data_cancelamento')
                ->whereBetween('data_movimentacao', [$mesAtualInicio, $mesAtualFim]),
            'movimentacao_financeira'
        )->sum('valor');

        $viewData = [
            'title' => 'Relatórios e Inteligência de Dados',
            'categorias' => $categorias,
            'totalReceber' => $totalReceber,
            'totalPagar' => $totalPagar,
            'inadimplenteReceber' => $inadimplenteReceber,
            'inadimplentePagar' => $inadimplentePagar,
            'contasVencendo7Receber' => $contasVencendo7Receber,
            'contasVencendo7Pagar' => $contasVencendo7Pagar,
            'saldoContas' => $saldoContas,
            'receitasMes' => $receitasMes,
            'despesasMes' => $despesasMes,
        ];
        $viewData = function_exists('apply_filters') ? apply_filters('financeiro.relatorios.index.data', $viewData) : $viewData;
        return erp_view('financeiro.relatorios.index', $viewData);
    }

    public function fluxoCaixa(Request $request)
    {
        $dataInicio = $request->get('data_inicio', now()->startOfMonth()->format('Y-m-d'));
        $dataFim = $request->get('data_fim', now()->endOfMonth()->format('Y-m-d'));
        $hoje = now()->toDateString();

        // Saldo consolidado antes do período (todas as contas ativas)
        $contasBancarias = ContaBancaria::where('ativo', true);
        if (optional(auth()->user())->tenant_id !== null) {
            $contasBancarias->where('tenant_id', auth()->user()->tenant_id);
        }
        $contasBancarias = $contasBancarias->get();

        $saldoInicialConsolidado = 0;
        $dataAntes = \Carbon\Carbon::parse($dataInicio)->subDay()->format('Y-m-d');
        foreach ($contasBancarias as $conta) {
            $entradas = $conta->movimentacoes()
                ->where('tipo', 'entrada')
                ->where('conciliado', true)
                ->whereNull('data_cancelamento')
                ->where('data_movimentacao', '<=', $dataAntes)
                ->sum('valor');
            $saidas = $conta->movimentacoes()
                ->where('tipo', 'saida')
                ->where('conciliado', true)
                ->whereNull('data_cancelamento')
                ->where('data_movimentacao', '<=', $dataAntes)
                ->sum('valor');
            $saldoInicialConsolidado += (float) $conta->saldo_inicial + $entradas - $saidas;
        }

        // Previsto: apenas títulos pendentes (aberto/parcial), usando valor pendente
        $qReceitas = $this->applyEntityQuery(
            ContaReceber::whereBetween('data_vencimento', [$dataInicio, $dataFim])
                ->whereIn('status', ['aberto', 'parcial']),
            'conta_receber'
        );
        $receitasPrevistas = $qReceitas->selectRaw('DATE(data_vencimento) as data, SUM(valor - COALESCE(valor_recebido, 0)) as total')
            ->groupBy('data')->get()->keyBy('data');

        $qDespesas = $this->applyEntityQuery(
            ContaPagar::whereBetween('data_vencimento', [$dataInicio, $dataFim])
                ->whereIn('status', ['aberto', 'parcial']),
            'conta_pagar'
        );
        $despesasPrevistas = $qDespesas->selectRaw('DATE(data_vencimento) as data, SUM(valor - COALESCE(valor_pago, 0)) as total')
            ->groupBy('data')->get()->keyBy('data');

        // Realizado: movimentações conciliadas e não canceladas
        $qReceitasReal = $this->applyEntityQuery(
            MovimentacaoFinanceira::where('tipo', 'entrada')
                ->where('conciliado', true)
                ->whereNull('data_cancelamento')
                ->whereBetween('data_movimentacao', [$dataInicio, $dataFim]),
            'movimentacao_financeira'
        );
        $receitasRealizadas = $qReceitasReal->selectRaw('DATE(data_movimentacao) as data, SUM(valor) as total')
            ->groupBy('data')->get()->keyBy('data');

        $qDespesasReal = $this->applyEntityQuery(
            MovimentacaoFinanceira::where('tipo', 'saida')
                ->where('conciliado', true)
                ->whereNull('data_cancelamento')
                ->whereBetween('data_movimentacao', [$dataInicio, $dataFim]),
            'movimentacao_financeira'
        );
        $despesasRealizadas = $qDespesasReal->selectRaw('DATE(data_movimentacao) as data, SUM(valor) as total')
            ->groupBy('data')->get()->keyBy('data');

        // Combinar dados por data + saldo projetado acumulado
        $dias = [];
        $dataAtual = \Carbon\Carbon::parse($dataInicio);
        $dataFimCarbon = \Carbon\Carbon::parse($dataFim);
        $saldoAcumulado = 0;
        $saldoProjetado = $saldoInicialConsolidado;

        while ($dataAtual <= $dataFimCarbon) {
            $dataStr = $dataAtual->format('Y-m-d');
            $receitaPrevista = (float)(($receitasPrevistas[$dataStr] ?? null)?->total ?? 0);
            $receitaRealizada = (float)(($receitasRealizadas[$dataStr] ?? null)?->total ?? 0);
            $despesaPrevista = (float)(($despesasPrevistas[$dataStr] ?? null)?->total ?? 0);
            $despesaRealizada = (float)(($despesasRealizadas[$dataStr] ?? null)?->total ?? 0);

            $saldoPrevisto = $receitaPrevista - $despesaPrevista;
            $saldoRealizado = $receitaRealizada - $despesaRealizada;
            $saldoAcumulado += $saldoRealizado;

            // Saldo projetado: passado/hoje usa apenas realizado;
            // futuro soma realizado (já lançado) + previsto (pendente) — não se sobrepõem
            // porque CR/CP pagos saem do status 'aberto' e não aparecem no previsto
            $ehPassadoOuHoje = $dataStr <= $hoje;
            $fluxoDia = $ehPassadoOuHoje ? $saldoRealizado : ($saldoRealizado + $saldoPrevisto);
            $saldoProjetado += $fluxoDia;

            $dias[] = [
                'data' => $dataStr,
                'data_formatada' => $dataAtual->format('d/m/Y'),
                'receita_prevista' => $receitaPrevista,
                'receita_realizada' => $receitaRealizada,
                'despesa_prevista' => $despesaPrevista,
                'despesa_realizada' => $despesaRealizada,
                'saldo_previsto' => $saldoPrevisto,
                'saldo_realizado' => $saldoRealizado,
                'saldo_acumulado' => $saldoAcumulado,
                'saldo_projetado' => $saldoProjetado,
                'saldo_projetado_negativo' => $saldoProjetado < 0,
            ];
            $dataAtual->addDay();
        }

        return erp_view('financeiro.relatorios.fluxo-caixa', [
            'title' => 'Fluxo de Caixa',
            'dias' => $dias,
            'dataInicio' => $dataInicio,
            'dataFim' => $dataFim,
            'saldoInicialConsolidado' => $saldoInicialConsolidado,
        ]);
    }

    public function fluxoCaixaPdf(Request $request)
    {
        $dataInicio = $request->get('data_inicio', now()->startOfMonth()->format('Y-m-d'));
        $dataFim = $request->get('data_fim', now()->endOfMonth()->format('Y-m-d'));
        $hoje = now()->toDateString();

        $contasBancarias = ContaBancaria::where('ativo', true);
        if (optional(auth()->user())->tenant_id !== null) {
            $contasBancarias->where('tenant_id', auth()->user()->tenant_id);
        }
        $contasBancarias = $contasBancarias->get();
        $saldoInicialConsolidado = 0;
        $dataAntes = \Carbon\Carbon::parse($dataInicio)->subDay()->format('Y-m-d');
        foreach ($contasBancarias as $conta) {
            $entradas = $conta->movimentacoes()->where('tipo', 'entrada')->where('conciliado', true)->whereNull('data_cancelamento')->where('data_movimentacao', '<=', $dataAntes)->sum('valor');
            $saidas = $conta->movimentacoes()->where('tipo', 'saida')->where('conciliado', true)->whereNull('data_cancelamento')->where('data_movimentacao', '<=', $dataAntes)->sum('valor');
            $saldoInicialConsolidado += (float) $conta->saldo_inicial + $entradas - $saidas;
        }

        $qReceitas = $this->applyEntityQuery(
            ContaReceber::whereBetween('data_vencimento', [$dataInicio, $dataFim])->whereIn('status', ['aberto', 'parcial']),
            'conta_receber'
        );
        $receitasPrevistas = $qReceitas->selectRaw('DATE(data_vencimento) as data, SUM(valor - COALESCE(valor_recebido, 0)) as total')->groupBy('data')->get()->keyBy('data');
        $qDespesas = $this->applyEntityQuery(
            ContaPagar::whereBetween('data_vencimento', [$dataInicio, $dataFim])->whereIn('status', ['aberto', 'parcial']),
            'conta_pagar'
        );
        $despesasPrevistas = $qDespesas->selectRaw('DATE(data_vencimento) as data, SUM(valor - COALESCE(valor_pago, 0)) as total')->groupBy('data')->get()->keyBy('data');
        $qReceitasReal = $this->applyEntityQuery(
            MovimentacaoFinanceira::where('tipo', 'entrada')->where('conciliado', true)->whereNull('data_cancelamento')->whereBetween('data_movimentacao', [$dataInicio, $dataFim]),
            'movimentacao_financeira'
        );
        $receitasRealizadas = $qReceitasReal->selectRaw('DATE(data_movimentacao) as data, SUM(valor) as total')->groupBy('data')->get()->keyBy('data');
        $qDespesasReal = $this->applyEntityQuery(
            MovimentacaoFinanceira::where('tipo', 'saida')->where('conciliado', true)->whereNull('data_cancelamento')->whereBetween('data_movimentacao', [$dataInicio, $dataFim]),
            'movimentacao_financeira'
        );
        $despesasRealizadas = $qDespesasReal->selectRaw('DATE(data_movimentacao) as data, SUM(valor) as total')->groupBy('data')->get()->keyBy('data');

        $dias = [];
        $dataAtual = \Carbon\Carbon::parse($dataInicio);
        $dataFimCarbon = \Carbon\Carbon::parse($dataFim);
        $saldoAcumulado = 0;
        $saldoProjetado = $saldoInicialConsolidado;

        while ($dataAtual <= $dataFimCarbon) {
            $dataStr = $dataAtual->format('Y-m-d');
            $receitaPrevista = (float)(($receitasPrevistas[$dataStr] ?? null)?->total ?? 0);
            $receitaRealizada = (float)(($receitasRealizadas[$dataStr] ?? null)?->total ?? 0);
            $despesaPrevista = (float)(($despesasPrevistas[$dataStr] ?? null)?->total ?? 0);
            $despesaRealizada = (float)(($despesasRealizadas[$dataStr] ?? null)?->total ?? 0);
            $saldoRealizado = $receitaRealizada - $despesaRealizada;
            $saldoAcumulado += $saldoRealizado;
            $ehPassadoOuHoje = $dataStr <= $hoje;
            $fluxoDia = $ehPassadoOuHoje ? $saldoRealizado : ($saldoRealizado + $receitaPrevista - $despesaPrevista);
            $saldoProjetado += $fluxoDia;

            $dias[] = [
                'data_formatada' => $dataAtual->format('d/m/Y'),
                'receita_prevista' => $receitaPrevista,
                'receita_realizada' => $receitaRealizada,
                'despesa_prevista' => $despesaPrevista,
                'despesa_realizada' => $despesaRealizada,
                'saldo_realizado' => $saldoRealizado,
                'saldo_acumulado' => $saldoAcumulado,
                'saldo_projetado' => $saldoProjetado,
                'saldo_projetado_negativo' => $saldoProjetado < 0,
            ];
            $dataAtual->addDay();
        }

        $html = view('financeiro.relatorios.pdf.fluxo-caixa', [
            'dias' => $dias,
            'dataInicio' => $dataInicio,
            'dataFim' => $dataFim,
            'saldoInicialConsolidado' => $saldoInicialConsolidado,
        ])->render();

        $pdf = Pdf::loadHTML($html);
        $pdf->setPaper('A4', 'landscape');
        $pdf->setOption('margin-top', '15mm');
        $pdf->setOption('margin-bottom', '15mm');
        $pdf->setOption('margin-left', '12mm');
        $pdf->setOption('margin-right', '12mm');

        $filename = 'fluxo-caixa-' . $dataInicio . '-a-' . $dataFim . '.pdf';
        return $pdf->stream($filename);
    }

    /**
     * Balancete: saldos por plano de conta em uma data (receitas - despesas até a data).
     * Inclui todas as movimentações até a data (conciliadas ou não e sem excluir soft-deleted),
     * para o relatório trazer dados. Opção "Somente conciliados" pode ser adicionada depois.
     */
    public function balancete(Request $request)
    {
        $dataBalancete = $request->get('data_balancete', now()->format('Y-m-d'));

        $query = MovimentacaoFinanceira::with(['contaReceber', 'contaPagar', 'planoConta'])
            ->withTrashed()
            ->whereNull('data_cancelamento')
            ->where('data_movimentacao', '<=', $dataBalancete);
        $movimentacoes = $query->get();

        $saldosPorConta = [];
        foreach ($movimentacoes as $mov) {
            $planoId = $mov->plano_conta_id
                ?? $mov->contaReceber?->plano_conta_id
                ?? $mov->contaPagar?->plano_conta_id;
            if ($planoId === null) {
                continue;
            }
            if (!isset($saldosPorConta[$planoId])) {
                $saldosPorConta[$planoId] = 0;
            }
            $saldosPorConta[$planoId] += $mov->tipo === 'entrada' ? (float) $mov->valor : -(float) $mov->valor;
        }

        $planoIds = array_keys($saldosPorConta);
        $contas = PlanoConta::whereIn('id', $planoIds)->where('ativo', true)->orderBy('tipo')->orderBy('codigo')->get()->keyBy('id');
        $linhas = [];
        foreach ($saldosPorConta as $planoId => $saldo) {
            $conta = $contas->get($planoId);
            if ($conta) {
                $linhas[] = [
                    'codigo' => $conta->codigo,
                    'nome' => $conta->nome,
                    'tipo' => $conta->tipo,
                    'saldo' => $saldo,
                ];
            }
        }
        usort($linhas, fn ($a, $b) => strcmp($a['codigo'] ?? '', $b['codigo'] ?? ''));

        return erp_view('financeiro.relatorios.balancete', [
            'title' => 'Balancete',
            'linhas' => $linhas,
            'dataBalancete' => $dataBalancete,
        ]);
    }

    public function balancetePdf(Request $request)
    {
        $dataBalancete = $request->get('data_balancete', now()->format('Y-m-d'));
        $query = MovimentacaoFinanceira::with(['contaReceber', 'contaPagar', 'planoConta'])
            ->withTrashed()
            ->whereNull('data_cancelamento')
            ->where('data_movimentacao', '<=', $dataBalancete);
        $movimentacoes = $query->get();
        $saldosPorConta = [];
        foreach ($movimentacoes as $mov) {
            $planoId = $mov->plano_conta_id ?? $mov->contaReceber?->plano_conta_id ?? $mov->contaPagar?->plano_conta_id;
            if ($planoId === null) continue;
            if (!isset($saldosPorConta[$planoId])) $saldosPorConta[$planoId] = 0;
            $saldosPorConta[$planoId] += $mov->tipo === 'entrada' ? (float) $mov->valor : -(float) $mov->valor;
        }
        $contas = PlanoConta::whereIn('id', array_keys($saldosPorConta))->where('ativo', true)->orderBy('codigo')->get()->keyBy('id');
        $linhas = [];
        foreach ($saldosPorConta as $planoId => $saldo) {
            $c = $contas->get($planoId);
            if ($c) $linhas[] = ['codigo' => $c->codigo, 'nome' => $c->nome, 'tipo' => $c->tipo, 'saldo' => $saldo];
        }
        usort($linhas, fn ($a, $b) => strcmp($a['codigo'] ?? '', $b['codigo'] ?? ''));
        $html = view('financeiro.relatorios.pdf.balancete', ['linhas' => $linhas, 'dataBalancete' => $dataBalancete])->render();
        $pdf = Pdf::loadHTML($html)->setPaper('A4', 'portrait')->setOption('margin-top', '15mm')->setOption('margin-bottom', '15mm');
        return $pdf->stream('balancete-' . $dataBalancete . '.pdf');
    }

    /**
     * Razão por conta: lançamentos por plano de conta e período.
     * Busca movimentações do período e filtra em PHP pelo plano efetivo (mov ou CR/CP) para evitar falhas na query.
     */
    public function razao(Request $request)
    {
        // Lista de planos para o select
        $planoContas = PlanoConta::where('ativo', true)
            ->whereIn('tipo', ['receita', 'despesa'])
            ->orderBy('codigo')
            ->get(['id', 'codigo', 'nome', 'tipo'])
            ->unique('id')
            ->values();
        if (!$request->filled('plano_conta_id') || !$request->filled('data_inicio') || !$request->filled('data_fim')) {
            return erp_view('financeiro.relatorios.razao', [
                'title' => 'Razão por conta',
                'planoConta' => null,
                'planoContas' => $planoContas,
                'itens' => [],
                'dataInicio' => $request->get('data_inicio', now()->startOfMonth()->format('Y-m-d')),
                'dataFim' => $request->get('data_fim', now()->format('Y-m-d')),
                'somenteConciliados' => false,
            ]);
        }
        $request->validate([
            'plano_conta_id' => 'required|exists:plano_contas,id',
            'data_inicio' => 'required|date',
            'data_fim' => 'required|date|after_or_equal:data_inicio',
        ]);
        $planoContaId = (int) $request->plano_conta_id;
        $dataInicio = $request->data_inicio;
        $dataFim = $request->data_fim;
        $somenteConciliados = $request->boolean('somente_conciliados', false);

        // IDs da conta + filhas (máx 50 iterações para evitar loop)
        $planoContaIds = $this->idsContaEDescendentes($planoContaId);
        if (empty($planoContaIds)) {
            $planoContaIds = [$planoContaId];
        }

        $query = MovimentacaoFinanceira::with([
            'contaBancaria',
            'contaReceber' => fn ($q) => $q->withTrashed(),
            'contaPagar' => fn ($q) => $q->withTrashed(),
        ])
            ->withTrashed()
            ->whereNull('data_cancelamento')
            ->whereDate('data_movimentacao', '>=', $dataInicio)
            ->whereDate('data_movimentacao', '<=', $dataFim);
        if ($somenteConciliados) {
            $query->where('conciliado', true);
        }
        $todas = $query->orderBy('data_movimentacao')->orderBy('id')->get();

        $planoContaIdsSet = array_flip(array_map('intval', $planoContaIds));
        $lancamentos = $todas->filter(function ($mov) use ($planoContaIdsSet) {
            $efetivo = $mov->plano_conta_id
                ?? $mov->contaReceber?->plano_conta_id
                ?? $mov->contaPagar?->plano_conta_id;
            return $efetivo !== null && isset($planoContaIdsSet[(int) $efetivo]);
        })->values();

        $planoConta = PlanoConta::find($planoContaId);

        $saldoAcum = 0;
        $itens = [];
        foreach ($lancamentos as $mov) {
            $valor = (float) $mov->valor;
            $entrada = $mov->tipo === 'entrada' ? $valor : 0;
            $saida = $mov->tipo === 'saida' ? $valor : 0;
            $saldoAcum += $entrada - $saida;
            $itens[] = [
                'data' => $mov->data_movimentacao->format('d/m/Y'),
                'descricao' => $mov->descricao ?? ('Mov #' . $mov->id),
                'entrada' => $entrada,
                'saida' => $saida,
                'saldo' => $saldoAcum,
            ];
        }

        return erp_view('financeiro.relatorios.razao', [
            'title' => 'Razão por conta',
            'planoConta' => $planoConta,
            'planoContas' => $planoContas,
            'itens' => $itens,
            'dataInicio' => $dataInicio,
            'dataFim' => $dataFim,
            'somenteConciliados' => $somenteConciliados,
        ]);
    }

    public function razaoPdf(Request $request)
    {
        $request->validate([
            'plano_conta_id' => 'required|exists:plano_contas,id',
            'data_inicio' => 'required|date',
            'data_fim' => 'required|date|after_or_equal:data_inicio',
        ]);
        $planoContaId = (int) $request->plano_conta_id;
        $dataInicio = $request->data_inicio;
        $dataFim = $request->data_fim;
        $somenteConciliados = $request->boolean('somente_conciliados', false);

        $planoContaIds = $this->idsContaEDescendentes($planoContaId);
        if (empty($planoContaIds)) {
            $planoContaIds = [$planoContaId];
        }

        $query = MovimentacaoFinanceira::with([
            'contaBancaria',
            'contaReceber' => fn ($q) => $q->withTrashed(),
            'contaPagar' => fn ($q) => $q->withTrashed(),
        ])
            ->withTrashed()
            ->whereNull('data_cancelamento')
            ->whereDate('data_movimentacao', '>=', $dataInicio)
            ->whereDate('data_movimentacao', '<=', $dataFim);
        if ($somenteConciliados) {
            $query->where('conciliado', true);
        }
        $todas = $query->orderBy('data_movimentacao')->orderBy('id')->get();

        $planoContaIdsSet = array_flip(array_map('intval', $planoContaIds));
        $lancamentos = $todas->filter(function ($mov) use ($planoContaIdsSet) {
            $efetivo = $mov->plano_conta_id ?? $mov->contaReceber?->plano_conta_id ?? $mov->contaPagar?->plano_conta_id;
            return $efetivo !== null && isset($planoContaIdsSet[(int) $efetivo]);
        })->values();

        $planoConta = PlanoConta::find($planoContaId);
        $saldoAcum = 0;
        $itens = [];
        foreach ($lancamentos as $mov) {
            $valor = (float) $mov->valor;
            $entrada = $mov->tipo === 'entrada' ? $valor : 0;
            $saida = $mov->tipo === 'saida' ? $valor : 0;
            $saldoAcum += $entrada - $saida;
            $itens[] = ['data' => $mov->data_movimentacao->format('d/m/Y'), 'descricao' => $mov->descricao ?? ('Mov #' . $mov->id), 'entrada' => $entrada, 'saida' => $saida, 'saldo' => $saldoAcum];
        }
        $html = view('financeiro.relatorios.pdf.razao', ['planoConta' => $planoConta, 'itens' => $itens, 'dataInicio' => $dataInicio, 'dataFim' => $dataFim])->render();
        $pdf = Pdf::loadHTML($html)->setPaper('A4', 'portrait')->setOption('margin-top', '15mm')->setOption('margin-bottom', '15mm');
        return $pdf->stream('razao-conta-' . $planoContaId . '-' . $dataInicio . '-' . $dataFim . '.pdf');
    }

    public function dre(Request $request)
    {
        $dataInicio = $request->get('data_inicio', now()->startOfYear()->format('Y-m-d'));
        $dataFim = $request->get('data_fim', now()->endOfYear()->format('Y-m-d'));
        $centroCustoId = $request->filled('centro_custo_id') ? (int) $request->centro_custo_id : null;

        // Receitas Operacionais: via CR com plano operacional OU plano_conta_id direto na movimentação
        $qRecOp = $this->applyEntityQuery(
            MovimentacaoFinanceira::where('tipo', 'entrada')
                ->where('conciliado', true)
                ->whereNull('data_cancelamento')
                ->where(function ($q) {
                    $q->whereHas('contaReceber.planoConta', fn($sq) => $sq->where('tipo', 'receita')->where('categoria', 'operacional'))
                      ->orWhereHas('planoConta', fn($sq) => $sq->where('tipo', 'receita')->where('categoria', 'operacional'));
                })
                ->whereBetween('data_movimentacao', [$dataInicio, $dataFim])
                ->porCentroCusto($centroCustoId),
            'movimentacao_financeira'
        );
        $receitasOperacionais = $qRecOp->sum('valor');

        // Despesas Operacionais: via CP com plano operacional OU plano_conta_id direto
        $qDespOp = $this->applyEntityQuery(
            MovimentacaoFinanceira::where('tipo', 'saida')
                ->where('conciliado', true)
                ->whereNull('data_cancelamento')
                ->where(function ($q) {
                    $q->whereHas('contaPagar.planoConta', fn($sq) => $sq->where('tipo', 'despesa')->where('categoria', 'operacional'))
                      ->orWhereHas('planoConta', fn($sq) => $sq->where('tipo', 'despesa')->where('categoria', 'operacional'));
                })
                ->whereBetween('data_movimentacao', [$dataInicio, $dataFim])
                ->porCentroCusto($centroCustoId),
            'movimentacao_financeira'
        );
        $despesasOperacionais = $qDespOp->sum('valor');

        // Receitas Não Operacionais: via CR ou plano_conta_id direto
        $qRecNaoOp = $this->applyEntityQuery(
            MovimentacaoFinanceira::where('tipo', 'entrada')
                ->where('conciliado', true)
                ->whereNull('data_cancelamento')
                ->where(function ($q) {
                    $q->whereHas('contaReceber.planoConta', fn($sq) => $sq->where('tipo', 'receita')->where('categoria', 'nao_operacional'))
                      ->orWhereHas('planoConta', fn($sq) => $sq->where('tipo', 'receita')->where('categoria', 'nao_operacional'));
                })
                ->whereBetween('data_movimentacao', [$dataInicio, $dataFim])
                ->porCentroCusto($centroCustoId),
            'movimentacao_financeira'
        );
        $receitasNaoOperacionais = $qRecNaoOp->sum('valor');

        // Despesas Financeiras: via CP ou plano_conta_id direto
        $qDespFin = $this->applyEntityQuery(
            MovimentacaoFinanceira::where('tipo', 'saida')
                ->where('conciliado', true)
                ->whereNull('data_cancelamento')
                ->where(function ($q) {
                    $q->whereHas('contaPagar.planoConta', fn($sq) => $sq->where('tipo', 'despesa')->where('categoria', 'financeira'))
                      ->orWhereHas('planoConta', fn($sq) => $sq->where('tipo', 'despesa')->where('categoria', 'financeira'));
                })
                ->whereBetween('data_movimentacao', [$dataInicio, $dataFim])
                ->porCentroCusto($centroCustoId),
            'movimentacao_financeira'
        );
        $despesasFinanceiras = $qDespFin->sum('valor');

        $receitaLiquida = $receitasOperacionais - $despesasOperacionais;
        $resultadoAntesIR = $receitaLiquida + $receitasNaoOperacionais - $despesasFinanceiras;

        $centrosCusto = CentroCusto::orderBy('nome')->get(['id', 'nome']);

        return erp_view('financeiro.relatorios.dre', [
            'title' => 'DRE - Demonstrativo do Resultado do Exercício',
            'receitasOperacionais' => $receitasOperacionais,
            'despesasOperacionais' => $despesasOperacionais,
            'receitaLiquida' => $receitaLiquida,
            'receitasNaoOperacionais' => $receitasNaoOperacionais,
            'despesasFinanceiras' => $despesasFinanceiras,
            'resultadoAntesIR' => $resultadoAntesIR,
            'dataInicio' => $dataInicio,
            'dataFim' => $dataFim,
            'centrosCusto' => $centrosCusto,
            'centroCustoId' => $centroCustoId,
        ]);
    }

    public function drePdf(Request $request)
    {
        $dataInicio = $request->get('data_inicio', now()->startOfYear()->format('Y-m-d'));
        $dataFim = $request->get('data_fim', now()->endOfYear()->format('Y-m-d'));
        $centroCustoId = $request->filled('centro_custo_id') ? (int) $request->centro_custo_id : null;

        $qRecOp = $this->applyEntityQuery(
            MovimentacaoFinanceira::where('tipo', 'entrada')
                ->where('conciliado', true)
                ->whereNull('data_cancelamento')
                ->where(function ($q) {
                    $q->whereHas('contaReceber.planoConta', fn($sq) => $sq->where('tipo', 'receita')->where('categoria', 'operacional'))
                      ->orWhereHas('planoConta', fn($sq) => $sq->where('tipo', 'receita')->where('categoria', 'operacional'));
                })
                ->whereBetween('data_movimentacao', [$dataInicio, $dataFim])
                ->porCentroCusto($centroCustoId),
            'movimentacao_financeira'
        );
        $receitasOperacionais = $qRecOp->sum('valor');

        $qDespOp = $this->applyEntityQuery(
            MovimentacaoFinanceira::where('tipo', 'saida')
                ->where('conciliado', true)
                ->whereNull('data_cancelamento')
                ->where(function ($q) {
                    $q->whereHas('contaPagar.planoConta', fn($sq) => $sq->where('tipo', 'despesa')->where('categoria', 'operacional'))
                      ->orWhereHas('planoConta', fn($sq) => $sq->where('tipo', 'despesa')->where('categoria', 'operacional'));
                })
                ->whereBetween('data_movimentacao', [$dataInicio, $dataFim])
                ->porCentroCusto($centroCustoId),
            'movimentacao_financeira'
        );
        $despesasOperacionais = $qDespOp->sum('valor');

        $qRecNaoOp = $this->applyEntityQuery(
            MovimentacaoFinanceira::where('tipo', 'entrada')
                ->where('conciliado', true)
                ->whereNull('data_cancelamento')
                ->where(function ($q) {
                    $q->whereHas('contaReceber.planoConta', fn($sq) => $sq->where('tipo', 'receita')->where('categoria', 'nao_operacional'))
                      ->orWhereHas('planoConta', fn($sq) => $sq->where('tipo', 'receita')->where('categoria', 'nao_operacional'));
                })
                ->whereBetween('data_movimentacao', [$dataInicio, $dataFim])
                ->porCentroCusto($centroCustoId),
            'movimentacao_financeira'
        );
        $receitasNaoOperacionais = $qRecNaoOp->sum('valor');

        $qDespFin = $this->applyEntityQuery(
            MovimentacaoFinanceira::where('tipo', 'saida')
                ->where('conciliado', true)
                ->whereNull('data_cancelamento')
                ->where(function ($q) {
                    $q->whereHas('contaPagar.planoConta', fn($sq) => $sq->where('tipo', 'despesa')->where('categoria', 'financeira'))
                      ->orWhereHas('planoConta', fn($sq) => $sq->where('tipo', 'despesa')->where('categoria', 'financeira'));
                })
                ->whereBetween('data_movimentacao', [$dataInicio, $dataFim])
                ->porCentroCusto($centroCustoId),
            'movimentacao_financeira'
        );
        $despesasFinanceiras = $qDespFin->sum('valor');

        $receitaLiquida = $receitasOperacionais - $despesasOperacionais;
        $resultadoAntesIR = $receitaLiquida + $receitasNaoOperacionais - $despesasFinanceiras;

        $centroCusto = $centroCustoId ? CentroCusto::find($centroCustoId) : null;

        $html = view('financeiro.relatorios.pdf.dre', [
            'receitasOperacionais' => $receitasOperacionais,
            'despesasOperacionais' => $despesasOperacionais,
            'receitaLiquida' => $receitaLiquida,
            'receitasNaoOperacionais' => $receitasNaoOperacionais,
            'despesasFinanceiras' => $despesasFinanceiras,
            'resultadoAntesIR' => $resultadoAntesIR,
            'dataInicio' => $dataInicio,
            'dataFim' => $dataFim,
            'centroCusto' => $centroCusto,
        ])->render();

        $pdf = Pdf::loadHTML($html);
        $pdf->setPaper('A4', 'portrait');
        $pdf->setOption('margin-top', '15mm');
        $pdf->setOption('margin-bottom', '15mm');
        $pdf->setOption('margin-left', '15mm');
        $pdf->setOption('margin-right', '15mm');

        $filename = 'dre-' . $dataInicio . '-a-' . $dataFim . '.pdf';
        return $pdf->stream($filename);
    }

    public function contasReceberPdf(Request $request)
    {
        $dataInicio = $request->get('data_inicio', now()->startOfMonth()->format('Y-m-d'));
        $dataFim = $request->get('data_fim', now()->endOfMonth()->format('Y-m-d'));

        $query = ContaReceber::with(['cliente'])
            ->where('status', '!=', 'cancelado')
            ->whereBetween('data_vencimento', [$dataInicio, $dataFim])
            ->orderBy('data_vencimento');
        $query = $this->applyEntityQuery($query, 'conta_receber');
        $contas = $query->limit(1000)->get();

        $html = view('financeiro.relatorios.pdf.contas-receber', [
            'contas' => $contas,
            'dataInicio' => $dataInicio,
            'dataFim' => $dataFim,
        ])->render();

        $pdf = Pdf::loadHTML($html);
        $pdf->setPaper('A4', 'landscape');
        $pdf->setOption('margin-top', '15mm');
        $pdf->setOption('margin-bottom', '15mm');
        $pdf->setOption('margin-left', '12mm');
        $pdf->setOption('margin-right', '12mm');
        return $pdf->stream('contas-a-receber-' . $dataInicio . '-a-' . $dataFim . '.pdf');
    }

    public function contasPagarPdf(Request $request)
    {
        $dataInicio = $request->get('data_inicio', now()->startOfMonth()->format('Y-m-d'));
        $dataFim = $request->get('data_fim', now()->endOfMonth()->format('Y-m-d'));

        $query = ContaPagar::with(['fornecedor'])
            ->where('status', '!=', 'cancelado')
            ->whereBetween('data_vencimento', [$dataInicio, $dataFim])
            ->orderBy('data_vencimento');
        $query = $this->applyEntityQuery($query, 'conta_pagar');
        $contas = $query->limit(1000)->get();

        $html = view('financeiro.relatorios.pdf.contas-pagar', [
            'contas' => $contas,
            'dataInicio' => $dataInicio,
            'dataFim' => $dataFim,
        ])->render();

        $pdf = Pdf::loadHTML($html);
        $pdf->setPaper('A4', 'landscape');
        $pdf->setOption('margin-top', '15mm');
        $pdf->setOption('margin-bottom', '15mm');
        $pdf->setOption('margin-left', '12mm');
        $pdf->setOption('margin-right', '12mm');
        return $pdf->stream('contas-a-pagar-' . $dataInicio . '-a-' . $dataFim . '.pdf');
    }

    public function movimentacoesPdf(Request $request)
    {
        $dataInicio = $request->get('data_inicio', now()->startOfMonth()->format('Y-m-d'));
        $dataFim = $request->get('data_fim', now()->endOfMonth()->format('Y-m-d'));

        $query = MovimentacaoFinanceira::with(['contaBancaria'])
            ->whereNull('data_cancelamento')
            ->whereBetween('data_movimentacao', [$dataInicio, $dataFim])
            ->orderBy('data_movimentacao')->orderBy('id');
        $query = $this->applyEntityQuery($query, 'movimentacao_financeira');
        $movimentacoes = $query->limit(1000)->get();

        $html = view('financeiro.relatorios.pdf.movimentacoes', [
            'movimentacoes' => $movimentacoes,
            'dataInicio' => $dataInicio,
            'dataFim' => $dataFim,
        ])->render();

        $pdf = Pdf::loadHTML($html);
        $pdf->setPaper('A4', 'landscape');
        $pdf->setOption('margin-top', '15mm');
        $pdf->setOption('margin-bottom', '15mm');
        $pdf->setOption('margin-left', '12mm');
        $pdf->setOption('margin-right', '12mm');
        return $pdf->stream('movimentacoes-' . $dataInicio . '-a-' . $dataFim . '.pdf');
    }

    public function receitasDespesasPdf(Request $request)
    {
        $dataInicio = $request->get('data_inicio', now()->startOfYear()->format('Y-m-d'));
        $dataFim = $request->get('data_fim', now()->endOfYear()->format('Y-m-d'));
        $centroCustoId = $request->filled('centro_custo_id') ? (int) $request->centro_custo_id : null;

        $inicio = \Carbon\Carbon::parse($dataInicio)->startOfMonth();
        $fim = \Carbon\Carbon::parse($dataFim)->endOfMonth();
        $meses = [];
        while ($inicio <= $fim) {
            $mesInicio = $inicio->copy()->startOfMonth();
            $mesFim = $inicio->copy()->endOfMonth();
            $receitas = $this->applyEntityQuery(
                MovimentacaoFinanceira::where('tipo', 'entrada')->where('conciliado', true)
                    ->whereNull('data_cancelamento')
                    ->whereBetween('data_movimentacao', [$mesInicio, $mesFim])
                    ->porCentroCusto($centroCustoId),
                'movimentacao_financeira'
            )->sum('valor');
            $despesas = $this->applyEntityQuery(
                MovimentacaoFinanceira::where('tipo', 'saida')->where('conciliado', true)
                    ->whereNull('data_cancelamento')
                    ->whereBetween('data_movimentacao', [$mesInicio, $mesFim])
                    ->porCentroCusto($centroCustoId),
                'movimentacao_financeira'
            )->sum('valor');
            $meses[] = [
                'mes' => $inicio->format('m/Y'),
                'mes_nome' => $inicio->locale('pt_BR')->translatedFormat('F Y'),
                'receitas' => (float) $receitas,
                'despesas' => (float) $despesas,
                'resultado' => (float) $receitas - (float) $despesas,
            ];
            $inicio->addMonth();
        }

        $centroCusto = $centroCustoId ? CentroCusto::find($centroCustoId) : null;

        $html = view('financeiro.relatorios.pdf.receitas-despesas', [
            'meses' => $meses,
            'dataInicio' => $dataInicio,
            'dataFim' => $dataFim,
            'centroCusto' => $centroCusto,
        ])->render();

        $pdf = Pdf::loadHTML($html);
        $pdf->setPaper('A4', 'portrait');
        $pdf->setOption('margin-top', '15mm');
        $pdf->setOption('margin-bottom', '15mm');
        $pdf->setOption('margin-left', '15mm');
        $pdf->setOption('margin-right', '15mm');
        return $pdf->stream('receitas-despesas-' . $dataInicio . '-a-' . $dataFim . '.pdf');
    }

    /** Relatório de Inadimplência (Aging de Cobrança) */
    public function inadimplencia(Request $request)
    {
        $query = ContaReceber::with(['cliente'])
            ->whereIn('status', ['aberto', 'parcial'])
            ->where('data_vencimento', '<', now()->toDateString())
            ->orderBy('data_vencimento');
        $query = $this->applyEntityQuery($query, 'conta_receber');
        $contas = $query->get();

        $hoje = now()->toDateString();
        $faixas = [
            '1_15' => ['min' => 1, 'max' => 15, 'label' => '1 a 15 dias', 'contas' => [], 'total' => 0],
            '16_30' => ['min' => 16, 'max' => 30, 'label' => '16 a 30 dias', 'contas' => [], 'total' => 0],
            '31_60' => ['min' => 31, 'max' => 60, 'label' => '31 a 60 dias', 'contas' => [], 'total' => 0],
            '61_mais' => ['min' => 61, 'max' => 9999, 'label' => 'Mais de 60 dias', 'contas' => [], 'total' => 0],
        ];

        foreach ($contas as $c) {
            $dias = (int) \Carbon\Carbon::parse($c->data_vencimento)->diffInDays($hoje, false);
            $valor = $c->valor_pendente ?? $c->valor;
            if ($dias <= 15) {
                $faixas['1_15']['contas'][] = $c;
                $faixas['1_15']['total'] += $valor;
            } elseif ($dias <= 30) {
                $faixas['16_30']['contas'][] = $c;
                $faixas['16_30']['total'] += $valor;
            } elseif ($dias <= 60) {
                $faixas['31_60']['contas'][] = $c;
                $faixas['31_60']['total'] += $valor;
            } else {
                $faixas['61_mais']['contas'][] = $c;
                $faixas['61_mais']['total'] += $valor;
            }
        }

        return erp_view('financeiro.relatorios.inadimplencia', [
            'title' => 'Inadimplência (Aging de Cobrança)',
            'faixas' => $faixas,
            'totalGeral' => $contas->sum(fn ($c) => $c->valor_pendente ?? $c->valor),
        ]);
    }

    public function inadimplenciaPdf(Request $request)
    {
        $query = ContaReceber::with(['cliente'])
            ->whereIn('status', ['aberto', 'parcial'])
            ->where('data_vencimento', '<', now()->toDateString())
            ->orderBy('data_vencimento');
        $query = $this->applyEntityQuery($query, 'conta_receber');
        $contas = $query->limit(500)->get();

        $hoje = now()->toDateString();
        $faixas = [
            '1_15' => ['label' => '1 a 15 dias', 'itens' => [], 'total' => 0],
            '16_30' => ['label' => '16 a 30 dias', 'itens' => [], 'total' => 0],
            '31_60' => ['label' => '31 a 60 dias', 'itens' => [], 'total' => 0],
            '61_mais' => ['label' => 'Mais de 60 dias', 'itens' => [], 'total' => 0],
        ];

        foreach ($contas as $c) {
            $dias = (int) \Carbon\Carbon::parse($c->data_vencimento)->diffInDays($hoje, false);
            $valor = $c->valor_pendente ?? $c->valor;
            $sug = \App\Http\Controllers\Financeiro\ConfiguracoesFinanceiroController::calcularJurosMultaSugeridos((float)($c->valor_original ?? $c->valor), $c->data_vencimento->format('Y-m-d'));
            $nome = $c->cliente->nome ?? $c->cliente->razao_social ?? '—';
            $item = ['cliente' => $nome, 'vencimento' => $c->data_vencimento->format('d/m/Y'), 'dias' => $dias, 'valor' => $valor, 'juros_sugerido' => $sug['juros'], 'multa_sugerido' => $sug['multa']];
            if ($dias <= 15) {
                $faixas['1_15']['itens'][] = $item;
                $faixas['1_15']['total'] += $valor;
            } elseif ($dias <= 30) {
                $faixas['16_30']['itens'][] = $item;
                $faixas['16_30']['total'] += $valor;
            } elseif ($dias <= 60) {
                $faixas['31_60']['itens'][] = $item;
                $faixas['31_60']['total'] += $valor;
            } else {
                $faixas['61_mais']['itens'][] = $item;
                $faixas['61_mais']['total'] += $valor;
            }
        }

        $html = view('financeiro.relatorios.pdf.inadimplencia', ['faixas' => $faixas])->render();
        $pdf = Pdf::loadHTML($html);
        $pdf->setPaper('A4', 'portrait');
        $pdf->setOption('margin-top', '15mm');
        $pdf->setOption('margin-bottom', '15mm');
        return $pdf->stream('inadimplencia-aging.pdf');
    }

    /** Curva ABC de Clientes */
    public function curvaAbcClientes(Request $request)
    {
        $dataInicio = $request->get('data_inicio', now()->startOfYear()->format('Y-m-d'));
        $dataFim = $request->get('data_fim', now()->endOfMonth()->format('Y-m-d'));

        $receitaPorCliente = ContaReceber::query()
            ->selectRaw('cliente_id, SUM(COALESCE(valor_recebido, valor)) as total')
            ->where('status', 'pago')
            ->whereBetween('data_recebimento', [$dataInicio, $dataFim])
            ->groupBy('cliente_id');
        $receitaPorCliente = $this->applyEntityQuery($receitaPorCliente, 'conta_receber');
        $receitaPorCliente = $receitaPorCliente->get();

        $clientes = Cliente::whereIn('id', $receitaPorCliente->pluck('cliente_id'))->get()->keyBy('id');
        $lista = $receitaPorCliente->map(function ($r) use ($clientes) {
            $c = $clientes->get($r->cliente_id);
            return [
                'cliente_id' => $r->cliente_id,
                'nome' => $c ? ($c->nome ?? $c->razao_social) : '—',
                'total' => (float) $r->total,
            ];
        })->sortByDesc('total')->values();

        $totalGeral = $lista->sum('total');
        $acum = 0;
        $lista = $lista->map(function ($item) use ($totalGeral, &$acum) {
            $acum += $item['total'];
            $pct = $totalGeral > 0 ? ($acum / $totalGeral) * 100 : 0;
            $item['classe'] = $pct <= 80 ? 'A' : ($pct <= 95 ? 'B' : 'C');
            $item['acumulado_pct'] = round($pct, 1);
            return $item;
        });

        return erp_view('financeiro.relatorios.curva-abc-clientes', [
            'title' => 'Curva ABC de Clientes',
            'lista' => $lista,
            'totalGeral' => $totalGeral,
            'dataInicio' => $dataInicio,
            'dataFim' => $dataFim,
        ]);
    }

    public function curvaAbcClientesPdf(Request $request)
    {
        $dataInicio = $request->get('data_inicio', now()->startOfYear()->format('Y-m-d'));
        $dataFim = $request->get('data_fim', now()->endOfMonth()->format('Y-m-d'));

        $receitaPorCliente = ContaReceber::query()
            ->selectRaw('cliente_id, SUM(COALESCE(valor_recebido, valor)) as total')
            ->where('status', 'pago')
            ->whereBetween('data_recebimento', [$dataInicio, $dataFim])
            ->groupBy('cliente_id');
        $receitaPorCliente = $this->applyEntityQuery($receitaPorCliente, 'conta_receber');
        $receitaPorCliente = $receitaPorCliente->get();

        $clientes = Cliente::whereIn('id', $receitaPorCliente->pluck('cliente_id'))->get()->keyBy('id');
        $lista = $receitaPorCliente->map(function ($r) use ($clientes) {
            $c = $clientes->get($r->cliente_id);
            return ['nome' => $c ? ($c->nome ?? $c->razao_social) : '—', 'total' => (float) $r->total];
        })->sortByDesc('total')->values();

        $totalGeral = $lista->sum('total');
        $acum = 0;
        $lista = $lista->map(function ($item) use ($totalGeral, &$acum) {
            $acum += $item['total'];
            $pct = $totalGeral > 0 ? ($acum / $totalGeral) * 100 : 0;
            $item['classe'] = $pct <= 80 ? 'A' : ($pct <= 95 ? 'B' : 'C');
            $item['acumulado_pct'] = round($pct, 1);
            return $item;
        })->take(200);

        $html = view('financeiro.relatorios.pdf.curva-abc-clientes', [
            'lista' => $lista,
            'totalGeral' => $totalGeral,
            'dataInicio' => $dataInicio,
            'dataFim' => $dataFim,
        ])->render();
        $pdf = Pdf::loadHTML($html);
        $pdf->setPaper('A4', 'portrait');
        $pdf->setOption('margin-top', '15mm');
        $pdf->setOption('margin-bottom', '15mm');
        return $pdf->stream('curva-abc-clientes.pdf');
    }

    /** MRR - Receita Recorrente Mensal */
    public function mrr(Request $request)
    {
        $query = PagamentoRecorrente::with(['cliente'])
            ->where('ativo', true)
            ->orderBy('valor', 'desc');
        $query = $this->applyEntityQuery($query, 'pagamento_recorrente');
        $recorrentes = $query->get();

        $mrrTotal = $recorrentes->sum('valor');
        $porCliente = $recorrentes->groupBy('cliente_id')->map(function ($items) {
            $c = $items->first()->cliente;
            return [
                'nome' => $c ? ($c->nome ?? $c->razao_social) : '—',
                'valor_mensal' => $items->sum('valor'),
                'quantidade' => $items->count(),
            ];
        })->sortByDesc('valor_mensal')->values();

        return erp_view('financeiro.relatorios.mrr', [
            'title' => 'Receita Recorrente Mensal (MRR)',
            'mrrTotal' => $mrrTotal,
            'porCliente' => $porCliente,
            'totalContratos' => $recorrentes->count(),
        ]);
    }

    public function mrrPdf(Request $request)
    {
        $query = PagamentoRecorrente::with(['cliente'])
            ->where('ativo', true)
            ->orderBy('valor', 'desc');
        $query = $this->applyEntityQuery($query, 'pagamento_recorrente');
        $recorrentes = $query->get();
        $mrrTotal = $recorrentes->sum('valor');
        $porCliente = $recorrentes->groupBy('cliente_id')->map(function ($items) {
            $c = $items->first()->cliente;
            return [
                'nome' => $c ? ($c->nome ?? $c->razao_social) : '—',
                'valor_mensal' => $items->sum('valor'),
                'quantidade' => $items->count(),
            ];
        })->sortByDesc('valor_mensal')->take(100)->values();

        $html = view('financeiro.relatorios.pdf.mrr', [
            'mrrTotal' => $mrrTotal,
            'porCliente' => $porCliente,
            'totalContratos' => $recorrentes->count(),
        ])->render();
        $pdf = Pdf::loadHTML($html);
        $pdf->setPaper('A4', 'portrait');
        $pdf->setOption('margin-top', '15mm');
        $pdf->setOption('margin-bottom', '15mm');
        return $pdf->stream('mrr.pdf');
    }

    /** Vendas por Vendedor */
    public function vendasVendedor(Request $request)
    {
        $dataInicio = $request->get('data_inicio', now()->startOfYear()->format('Y-m-d'));
        $dataFim = $request->get('data_fim', now()->endOfMonth()->format('Y-m-d'));

        // Recebimentos da OS (ordem_servico_id): o “vendedor” é o(s) técnico(s) da OS, considerando:
        // - Técnico marcado no item de serviço (ordem_servico_servicos.tecnico_id)
        // - Técnicos/apontamentos da OS (ordem_servico_tecnicos) quando não houver técnico por serviço.
        $contas = ContaReceber::with([
            'ordemServico.cliente',
            'ordemServico.tecnicos.tecnico',
            'ordemServico.servicos.servico',
            'ordemServico.servicos.tecnico',
            'ordemServico.produtos.produto',
        ])
            ->whereNotNull('ordem_servico_id')
            ->whereIn('status', ['pago', 'parcial'])
            ->whereBetween('data_recebimento', [$dataInicio, $dataFim]);
        $contas = $this->applyEntityQuery($contas, 'conta_receber');
        $contas = $contas->get();

        $porVendedor = [];
        foreach ($contas as $conta) {
            $valorReceita = (float) ($conta->valor_recebido ?? $conta->valor ?? 0);
            $os = $conta->ordemServico;
            if (!$os) {
                $key = 'sem';
                if (!isset($porVendedor[$key])) {
                    $porVendedor[$key] = [
                        'vendedor_id' => null,
                        'vendedor_nome' => 'Sem técnico',
                        'valor' => 0,
                        'quantidade' => 0,
                        'ordens' => [],
                    ];
                }
                $osKey = 0;
                if (!isset($porVendedor[$key]['ordens'][$osKey])) {
                    $porVendedor[$key]['ordens'][$osKey] = [
                        'os_id' => null,
                        'os_codigo' => null,
                        'cliente' => '—',
                        'valor' => 0,
                        'servicos' => [],
                    ];
                }
                $porVendedor[$key]['valor'] += $valorReceita;
                $porVendedor[$key]['quantidade']++;
                $porVendedor[$key]['ordens'][$osKey]['valor'] += $valorReceita;
                continue;
            }

            $tecnicosOs = $os->tecnicos ?? collect();
            $servicosOs = $os->servicos ?? collect();
            $produtosOs = $os->produtos ?? collect();
            $valorServicosOs = (float) $servicosOs->sum('total');
            $valorProdutosOs = (float) $produtosOs->sum('total');

            // 1) Tenta distribuir pela atribuição de técnico em cada serviço
            $mapServicoTech = [];
            $totalServicosTech = 0.0;
            foreach ($servicosOs as $s) {
                if ($s->tecnico_id) {
                    $tid = $s->tecnico_id;
                    if (!isset($mapServicoTech[$tid])) {
                        $mapServicoTech[$tid] = [
                            'user' => $s->tecnico,
                            'total' => 0.0,
                            'servicos' => [],
                        ];
                    }
                    $mapServicoTech[$tid]['total'] += (float) $s->total;
                    $mapServicoTech[$tid]['servicos'][] = $s;
                    $totalServicosTech += (float) $s->total;
                }
            }

            if ($totalServicosTech > 0 && !empty($mapServicoTech)) {
                // Distribui a receita proporcionalmente ao total dos serviços de cada técnico
                foreach ($mapServicoTech as $tid => $info) {
                    $user = $info['user'];
                    $peso = $info['total'] > 0 ? $info['total'] / $totalServicosTech : 0;
                    $valorTecnico = $valorReceita * $peso;

                    $key = $tid ?: 'sem';
                    if (!isset($porVendedor[$key])) {
                        $porVendedor[$key] = [
                            'vendedor_id' => $tid,
                            'vendedor_nome' => $user?->name ?? 'Sem técnico',
                            'valor' => 0,
                            'quantidade' => 0,
                            'ordens' => [],
                        ];
                    }

                    $osKey = $os->id;
                    if (!isset($porVendedor[$key]['ordens'][$osKey])) {
                        $porVendedor[$key]['ordens'][$osKey] = [
                            'os_id' => $os->id,
                            'os_codigo' => $os->codigo_interno,
                            'cliente' => $os->cliente ? ($os->cliente->nome ?? $os->cliente->razao_social) : '—',
                            'valor' => 0,
                            'valor_servicos' => $valorServicosOs,
                            'valor_produtos' => $valorProdutosOs,
                            'servicos' => [],
                            'produtos' => [],
                        ];
                    }

                    foreach ($info['servicos'] as $s) {
                        $porVendedor[$key]['ordens'][$osKey]['servicos'][] = [
                            'nome' => $s->servico->nome ?? $s->descricao ?? 'Serviço',
                            'descricao' => $s->descricao,
                            'quantidade' => $s->quantidade,
                            'total' => (float) $s->total,
                        ];
                    }
                    foreach ($produtosOs as $p) {
                        $porVendedor[$key]['ordens'][$osKey]['produtos'][] = [
                            'nome' => $p->produto->nome ?? $p->descricao ?? 'Produto',
                            'descricao' => $p->descricao,
                            'quantidade' => $p->quantidade,
                            'total' => (float) $p->total,
                        ];
                    }

                    $porVendedor[$key]['valor'] += $valorTecnico;
                    $porVendedor[$key]['quantidade']++;
                    $porVendedor[$key]['ordens'][$osKey]['valor'] += $valorTecnico;
                }
                continue;
            }

            // 2) Se não houver técnico por serviço, cai no critério de técnicos/apontamentos
            if ($tecnicosOs->isEmpty()) {
                $key = 'sem';
                if (!isset($porVendedor[$key])) {
                    $porVendedor[$key] = [
                        'vendedor_id' => null,
                        'vendedor_nome' => 'Sem técnico',
                        'valor' => 0,
                        'quantidade' => 0,
                        'ordens' => [],
                    ];
                }
                $osKey = $os->id;
                if (!isset($porVendedor[$key]['ordens'][$osKey])) {
                    $porVendedor[$key]['ordens'][$osKey] = [
                        'os_id' => $os->id,
                        'os_codigo' => $os->codigo_interno,
                        'cliente' => $os->cliente ? ($os->cliente->nome ?? $os->cliente->razao_social) : '—',
                        'valor' => 0,
                        'valor_servicos' => $valorServicosOs,
                        'valor_produtos' => $valorProdutosOs,
                        'servicos' => [],
                        'produtos' => [],
                    ];
                }
                $porVendedor[$key]['valor'] += $valorReceita;
                $porVendedor[$key]['quantidade']++;
                $porVendedor[$key]['ordens'][$osKey]['valor'] += $valorReceita;
                continue;
            }

            $totalHoras = (float) $tecnicosOs->sum('horas_trabalhadas') ?: 0.0;
            foreach ($tecnicosOs as $tec) {
                $user = $tec->tecnico;
                $id = $user?->id;
                $key = $id ?: 'sem';

                if (!isset($porVendedor[$key])) {
                    $porVendedor[$key] = [
                        'vendedor_id' => $id,
                        'vendedor_nome' => $user?->name ?? 'Sem técnico',
                        'valor' => 0,
                        'quantidade' => 0,
                        'ordens' => [],
                    ];
                }

                if ($totalHoras > 0 && $tec->horas_trabalhadas !== null) {
                    $peso = max((float) $tec->horas_trabalhadas, 0) / $totalHoras;
                } else {
                    $peso = 1 / max($tecnicosOs->count(), 1);
                }

                $valorTecnico = $valorReceita * $peso;

                $osKey = $os->id;
                if (!isset($porVendedor[$key]['ordens'][$osKey])) {
                    $porVendedor[$key]['ordens'][$osKey] = [
                        'os_id' => $os->id,
                        'os_codigo' => $os->codigo_interno,
                        'cliente' => $os->cliente ? ($os->cliente->nome ?? $os->cliente->razao_social) : '—',
                        'valor' => 0,
                        'valor_servicos' => $valorServicosOs,
                        'valor_produtos' => $valorProdutosOs,
                        'servicos' => [],
                        'produtos' => [],
                    ];

                    foreach ($servicosOs as $s) {
                        $porVendedor[$key]['ordens'][$osKey]['servicos'][] = [
                            'nome' => $s->servico->nome ?? $s->descricao ?? 'Serviço',
                            'descricao' => $s->descricao,
                            'quantidade' => $s->quantidade,
                            'total' => (float) $s->total,
                        ];
                    }
                    foreach ($produtosOs as $p) {
                        $porVendedor[$key]['ordens'][$osKey]['produtos'][] = [
                            'nome' => $p->produto->nome ?? $p->descricao ?? 'Produto',
                            'descricao' => $p->descricao,
                            'quantidade' => $p->quantidade,
                            'total' => (float) $p->total,
                        ];
                    }
                }

                $porVendedor[$key]['valor'] += $valorTecnico;
                $porVendedor[$key]['quantidade']++;
                $porVendedor[$key]['ordens'][$osKey]['valor'] += $valorTecnico;
            }
        }

        $this->mergePedidosVendaEmPorVendedor($porVendedor, $dataInicio, $dataFim, true);

        // Normaliza estrutura de ordens para arrays indexados e ordena por valor
        $porVendedor = collect($porVendedor)->map(function ($row) {
            if (!empty($row['ordens'])) {
                $row['ordens'] = collect($row['ordens'])->sortByDesc('valor')->values()->all();
            } else {
                $row['ordens'] = [];
            }
            return $row;
        })->sortByDesc('valor')->values();

        return erp_view('financeiro.relatorios.vendas-vendedor', [
            'title' => 'Vendas por Vendedor',
            'porVendedor' => $porVendedor,
            'totalGeral' => $porVendedor->sum('valor'),
            'dataInicio' => $dataInicio,
            'dataFim' => $dataFim,
        ]);
    }

    public function vendasVendedorPdf(Request $request)
    {
        $dataInicio = $request->get('data_inicio', now()->startOfYear()->format('Y-m-d'));
        $dataFim = $request->get('data_fim', now()->endOfMonth()->format('Y-m-d'));

        // Mesmo critério da tela (OS + pedidos de venda); PDF só totais por nome.
        // OS: técnicos (item de serviço ou apontamentos). Pedido: vendedor_id do pedido.
        $contas = ContaReceber::with([
            'ordemServico.tecnicos.tecnico',
            'ordemServico.servicos.tecnico',
        ])
            ->whereNotNull('ordem_servico_id')
            ->whereIn('status', ['pago', 'parcial'])
            ->whereBetween('data_recebimento', [$dataInicio, $dataFim]);
        $contas = $this->applyEntityQuery($contas, 'conta_receber');
        $contas = $contas->get();

        $porVendedor = [];
        foreach ($contas as $conta) {
            $valorReceita = (float) ($conta->valor_recebido ?? $conta->valor ?? 0);
            $os = $conta->ordemServico;
            if (!$os) {
                $key = 'sem';
                if (!isset($porVendedor[$key])) {
                    $porVendedor[$key] = [
                        'vendedor_nome' => 'Sem técnico',
                        'valor' => 0,
                        'quantidade' => 0,
                    ];
                }
                $porVendedor[$key]['valor'] += $valorReceita;
                $porVendedor[$key]['quantidade']++;
                continue;
            }

            $tecnicosOs = $os->tecnicos ?? collect();
            $servicosOs = $os->servicos ?? collect();

            // Primeiro tenta distribuir pela atribuição de técnico em cada serviço
            $mapServicoTech = [];
            $totalServicosTech = 0.0;
            foreach ($servicosOs as $s) {
                if ($s->tecnico_id) {
                    $tid = $s->tecnico_id;
                    if (!isset($mapServicoTech[$tid])) {
                        $mapServicoTech[$tid] = [
                            'user' => $s->tecnico,
                            'total' => 0.0,
                        ];
                    }
                    $mapServicoTech[$tid]['total'] += (float) $s->total;
                    $totalServicosTech += (float) $s->total;
                }
            }

            if ($totalServicosTech > 0 && !empty($mapServicoTech)) {
                foreach ($mapServicoTech as $tid => $info) {
                    $user = $info['user'];
                    $peso = $info['total'] > 0 ? $info['total'] / $totalServicosTech : 0;
                    $valorTecnico = $valorReceita * $peso;

                    $key = $tid ?: 'sem';
                    if (!isset($porVendedor[$key])) {
                        $porVendedor[$key] = [
                            'vendedor_nome' => $user?->name ?? 'Sem técnico',
                            'valor' => 0,
                            'quantidade' => 0,
                        ];
                    }
                    $porVendedor[$key]['valor'] += $valorTecnico;
                    $porVendedor[$key]['quantidade']++;
                }
                continue;
            }

            // Caso não exista técnico por serviço, distribui pelos técnicos/apontamentos
            if ($tecnicosOs->isEmpty()) {
                $key = 'sem';
                if (!isset($porVendedor[$key])) {
                    $porVendedor[$key] = [
                        'vendedor_nome' => 'Sem técnico',
                        'valor' => 0,
                        'quantidade' => 0,
                    ];
                }
                $porVendedor[$key]['valor'] += $valorReceita;
                $porVendedor[$key]['quantidade']++;
                continue;
            }

            $totalHoras = (float) $tecnicosOs->sum('horas_trabalhadas') ?: 0.0;
            foreach ($tecnicosOs as $tec) {
                $user = $tec->tecnico;
                $id = $user?->id;
                $key = $id ?: 'sem';

                if (!isset($porVendedor[$key])) {
                    $porVendedor[$key] = [
                        'vendedor_nome' => $user?->name ?? 'Sem técnico',
                        'valor' => 0,
                        'quantidade' => 0,
                    ];
                }

                if ($totalHoras > 0 && $tec->horas_trabalhadas !== null) {
                    $peso = max((float) $tec->horas_trabalhadas, 0) / $totalHoras;
                } else {
                    $peso = 1 / max($tecnicosOs->count(), 1);
                }

                $porVendedor[$key]['valor'] += $valorReceita * $peso;
                $porVendedor[$key]['quantidade']++;
            }
        }

        $this->mergePedidosVendaEmPorVendedor($porVendedor, $dataInicio, $dataFim, false);

        $porVendedor = collect($porVendedor)->sortByDesc('valor')->values();

        $html = view('financeiro.relatorios.pdf.vendas-vendedor', [
            'porVendedor' => $porVendedor,
            'totalGeral' => $porVendedor->sum('valor'),
            'dataInicio' => $dataInicio,
            'dataFim' => $dataFim,
        ])->render();
        $pdf = Pdf::loadHTML($html);
        $pdf->setPaper('A4', 'portrait');
        $pdf->setOption('margin-top', '15mm');
        $pdf->setOption('margin-bottom', '15mm');
        return $pdf->stream('vendas-por-vendedor.pdf');
    }

    /** Vendas por Serviço / Produto */
    public function vendasServicoProduto(Request $request)
    {
        $dataInicio = $request->get('data_inicio', now()->startOfYear()->format('Y-m-d'));
        $dataFim = $request->get('data_fim', now()->endOfMonth()->format('Y-m-d'));
        $criterio = $this->normalizarCriterioVendasServicoProduto($request->get('criterio', 'operacional'));
        $incluirPedidosOperacional = $request->boolean('incluir_pedidos');
        $tecnicoId = (int) $request->get('tecnico_id', 0);
        $tecnicoId = $tecnicoId > 0 ? $tecnicoId : null;
        $slaFiltro = $this->normalizarSlaFiltroVendasServicoProduto($request->get('sla_filtro'));

        ['servicos' => $servicos, 'produtos' => $produtos] = $this->buildServicosProdutosRelatorioVendas(
            $dataInicio,
            $dataFim,
            $criterio,
            $criterio === 'operacional' && $incluirPedidosOperacional,
            $tecnicoId,
            $slaFiltro
        );

        $tecnicoIdsOs = DB::table('ordem_servico_tecnicos')
            ->whereNotNull('tecnico_id')
            ->pluck('tecnico_id');
        $tecnicoIdsSrv = DB::table('ordem_servico_servicos')
            ->whereNotNull('tecnico_id')
            ->pluck('tecnico_id');
        $tecnicoIds = $tecnicoIdsOs->merge($tecnicoIdsSrv)->filter()->unique()->values();

        $tecnicos = DB::table('users')
            ->whereIn('id', $tecnicoIds)
            ->orderBy('name')
            ->get(['id', 'name']);

        return erp_view('financeiro.relatorios.vendas-servico-produto', [
            'title' => 'Vendas por Serviço e Produto',
            'servicos' => $servicos,
            'produtos' => $produtos,
            'dataInicio' => $dataInicio,
            'dataFim' => $dataFim,
            'criterio' => $criterio,
            'incluir_pedidos' => $incluirPedidosOperacional,
            'tecnicos' => $tecnicos,
            'tecnico_id' => $tecnicoId,
            'sla_filtro' => $slaFiltro,
        ]);
    }

    public function vendasServicoProdutoPdf(Request $request)
    {
        $dataInicio = $request->get('data_inicio', now()->startOfYear()->format('Y-m-d'));
        $dataFim = $request->get('data_fim', now()->endOfMonth()->format('Y-m-d'));
        $criterio = $this->normalizarCriterioVendasServicoProduto($request->get('criterio', 'operacional'));
        $incluirPedidosOperacional = $request->boolean('incluir_pedidos');
        $tecnicoId = (int) $request->get('tecnico_id', 0);
        $tecnicoId = $tecnicoId > 0 ? $tecnicoId : null;
        $slaFiltro = $this->normalizarSlaFiltroVendasServicoProduto($request->get('sla_filtro'));

        ['servicos' => $servicos, 'produtos' => $produtos] = $this->buildServicosProdutosRelatorioVendas(
            $dataInicio,
            $dataFim,
            $criterio,
            $criterio === 'operacional' && $incluirPedidosOperacional,
            $tecnicoId,
            $slaFiltro
        );
        $servicos = $servicos->take(50)->values();
        $produtos = $produtos->take(50)->values();

        $html = view('financeiro.relatorios.pdf.vendas-servico-produto', [
            'servicos' => $servicos,
            'produtos' => $produtos,
            'dataInicio' => $dataInicio,
            'dataFim' => $dataFim,
            'criterio' => $criterio,
            'incluir_pedidos' => $incluirPedidosOperacional,
            'tecnico_id' => $tecnicoId,
            'sla_filtro' => $slaFiltro,
        ])->render();
        $pdf = Pdf::loadHTML($html);
        $pdf->setPaper('A4', 'landscape');
        $pdf->setOption('margin-top', '15mm');
        $pdf->setOption('margin-bottom', '15mm');
        return $pdf->stream('vendas-servico-produto.pdf');
    }

    /** Posição de Estoque e Curva ABC de Produtos */
    public function estoquePosicao(Request $request)
    {
        $query = Produto::with('categoria')->whereNotNull('estoque_quantidade');
        $query = $this->applyEntityQuery($query, 'produto');
        $produtos = $query->get();

        $valorEstoque = $produtos->sum(fn ($p) => ($p->estoque_quantidade ?? 0) * ($p->estoque_preco_custo_unitario ?? 0));
        $lista = $produtos->map(fn ($p) => [
            'nome' => $p->nome,
            'codigo' => $p->codigo_sku,
            'quantidade' => $p->estoque_quantidade,
            'custo_unit' => $p->estoque_preco_custo_unitario ?? 0,
            'valor_total' => ($p->estoque_quantidade ?? 0) * ($p->estoque_preco_custo_unitario ?? 0),
        ])->sortByDesc('valor_total')->values();

        $totalAcum = 0;
        $lista = $lista->map(function ($item) use ($valorEstoque, &$totalAcum) {
            $totalAcum += $item['valor_total'];
            $item['classe_abc'] = $valorEstoque > 0 && ($totalAcum / $valorEstoque) <= 0.8 ? 'A' : ($valorEstoque > 0 && ($totalAcum / $valorEstoque) <= 0.95 ? 'B' : 'C');
            return $item;
        });

        return erp_view('financeiro.relatorios.estoque-posicao', [
            'title' => 'Posição de Estoque e Curva ABC',
            'lista' => $lista,
            'valorTotalEstoque' => $valorEstoque,
            'dataInicio' => $request->get('data_inicio'),
            'dataFim' => $request->get('data_fim'),
        ]);
    }

    public function estoquePosicaoPdf(Request $request)
    {
        $query = Produto::whereNotNull('estoque_quantidade');
        $query = $this->applyEntityQuery($query, 'produto');
        $produtos = $query->get();

        $valorEstoque = $produtos->sum(fn ($p) => ($p->estoque_quantidade ?? 0) * ($p->estoque_preco_custo_unitario ?? 0));
        $lista = $produtos->map(fn ($p) => [
            'nome' => $p->nome,
            'codigo' => $p->codigo_sku,
            'quantidade' => $p->estoque_quantidade,
            'valor_total' => ($p->estoque_quantidade ?? 0) * ($p->estoque_preco_custo_unitario ?? 0),
        ])->sortByDesc('valor_total')->take(100)->values();

        $totalAcum = 0;
        $lista = $lista->map(function ($item) use ($valorEstoque, &$totalAcum) {
            $totalAcum += $item['valor_total'];
            $item['classe_abc'] = $valorEstoque > 0 && ($totalAcum / $valorEstoque) <= 0.8 ? 'A' : ($valorEstoque > 0 && ($totalAcum / $valorEstoque) <= 0.95 ? 'B' : 'C');
            return $item;
        });

        $html = view('financeiro.relatorios.pdf.estoque-posicao', [
            'lista' => $lista,
            'valorTotalEstoque' => $valorEstoque,
        ])->render();
        $pdf = Pdf::loadHTML($html);
        $pdf->setPaper('A4', 'landscape');
        $pdf->setOption('margin-top', '15mm');
        $pdf->setOption('margin-bottom', '15mm');
        return $pdf->stream('estoque-posicao.pdf');
    }

    /**
     * Soma recebimentos de pedidos de venda (contas a receber com entidade_tipo pedido_venda)
     * ao mapa por vendedor/técnico. Quando $comDetalheOrdens, inclui linhas no mesmo formato do detalhe por OS.
     *
     * @param  array<string, array<string, mixed>>  $porVendedor
     */
    private function mergePedidosVendaEmPorVendedor(array &$porVendedor, string $dataInicio, string $dataFim, bool $comDetalheOrdens): void
    {
        $contas = ContaReceber::query()
            ->where('entidade_tipo', 'pedido_venda')
            ->whereNotNull('entidade_id')
            ->whereIn('status', ['pago', 'parcial'])
            ->whereBetween('data_recebimento', [$dataInicio, $dataFim]);
        $contas = $this->applyEntityQuery($contas, 'conta_receber');
        $contas = $contas->get();

        if ($contas->isEmpty()) {
            return;
        }

        $ids = $contas->pluck('entidade_id')->unique()->filter()->all();
        $pedidosQuery = PedidoVenda::query()
            ->with(['vendedor', 'cliente', 'itens.produto', 'itens.servico'])
            ->whereIn('id', $ids);
        $pedidosQuery = $this->applyEntityQuery($pedidosQuery, 'pedido_venda');
        $pedidos = $pedidosQuery->get()->keyBy('id');

        foreach ($contas as $conta) {
            $pedido = $pedidos->get((int) $conta->entidade_id);
            if (! $pedido) {
                continue;
            }

            $valorReceita = (float) ($conta->valor_recebido ?? $conta->valor ?? 0);
            $vid = $pedido->vendedor_id;
            $key = $vid !== null ? (string) $vid : 'sem';
            $nomeVendedor = $pedido->vendedor?->name ?? 'Sem vendedor';

            if (! isset($porVendedor[$key])) {
                $porVendedor[$key] = [
                    'vendedor_id' => $vid,
                    'vendedor_nome' => $nomeVendedor,
                    'valor' => 0.0,
                    'quantidade' => 0,
                    'ordens' => [],
                ];
            }

            $porVendedor[$key]['valor'] += $valorReceita;
            $porVendedor[$key]['quantidade']++;

            if (! $comDetalheOrdens) {
                continue;
            }

            $valorServicos = 0.0;
            $valorProdutos = 0.0;
            $servicosLinhas = [];
            $produtosLinhas = [];

            foreach ($pedido->itens as $it) {
                $qEff = max(0, (float) $it->quantidade - (float) ($it->quantidade_cancelada ?? 0));
                if ($qEff <= 0) {
                    continue;
                }
                if ($it->tipo === 'servico') {
                    $valorServicos += (float) $it->total;
                    $servicosLinhas[] = [
                        'nome' => $it->servico->nome ?? $it->descricao ?? 'Serviço',
                        'descricao' => $it->descricao,
                        'quantidade' => $qEff,
                        'total' => (float) $it->total,
                    ];
                } elseif ($it->tipo === 'produto') {
                    $valorProdutos += (float) $it->total;
                    $produtosLinhas[] = [
                        'nome' => $it->produto->nome ?? $it->descricao ?? 'Produto',
                        'descricao' => $it->descricao,
                        'quantidade' => $qEff,
                        'total' => (float) $it->total,
                    ];
                }
            }

            $osKey = 'pv_'.$pedido->id;
            if (! isset($porVendedor[$key]['ordens'][$osKey])) {
                $cliente = $pedido->cliente;
                $porVendedor[$key]['ordens'][$osKey] = [
                    'os_id' => null,
                    'os_codigo' => $pedido->numero_sequencial,
                    'origem' => 'pedido_venda',
                    'cliente' => $cliente ? ($cliente->nome ?? $cliente->razao_social ?? '—') : '—',
                    'valor' => 0.0,
                    'valor_servicos' => $valorServicos,
                    'valor_produtos' => $valorProdutos,
                    'servicos' => $servicosLinhas,
                    'produtos' => $produtosLinhas,
                ];
            }

            $porVendedor[$key]['ordens'][$osKey]['valor'] += $valorReceita;
        }
    }

    private function normalizarCriterioVendasServicoProduto(mixed $criterio): string
    {
        $c = is_string($criterio) ? strtolower(trim($criterio)) : '';

        return $c === 'operacional' ? 'operacional' : 'financeiro';
    }

    private function normalizarSlaFiltroVendasServicoProduto(mixed $filtro): ?string
    {
        $f = is_string($filtro) ? strtolower(trim($filtro)) : '';
        return in_array($f, ['cumprido', 'nao_cumprido', 'com_sla', 'sem_sla'], true) ? $f : null;
    }

    /**
     * Agrega serviços/produtos: financeiro (recebimentos em CR) ou operacional (OS encerrada / data do pedido), sem CR.
     *
     * @return array{servicos: \Illuminate\Support\Collection, produtos: \Illuminate\Support\Collection}
     */
    private function buildServicosProdutosRelatorioVendas(
        string $dataInicio,
        string $dataFim,
        string $criterio = 'financeiro',
        bool $incluirPedidosOperacional = false,
        ?int $tecnicoId = null,
        ?string $slaFiltro = null,
    ): array {
        return app(RelatorioVendasServicoProdutoService::class)->aggregate(
            $dataInicio,
            $dataFim,
            fn ($query, string $entity) => $this->applyEntityQuery($query, $entity),
            $criterio,
            $incluirPedidosOperacional,
            $tecnicoId,
            $slaFiltro
        );
    }

    /**
     * Retorna array com o id da conta e de todas as contas filhas (recursivo).
     * Usado no Razão para que conta pai mostre lançamentos da conta e das filhas.
     * Usa withTrashed() para incluir filhas soft-deleted e não zerar o resultado.
     */
    private function idsContaEDescendentes(int $planoContaId): array
    {
        if (!Schema::hasColumn((new PlanoConta())->getTable(), 'pai_id')) {
            return [$planoContaId];
        }
        $ids = [$planoContaId];
        $maxIteracoes = 50;
        $iteracao = 0;
        do {
            $newIds = PlanoConta::whereIn('pai_id', $ids)
                ->pluck('id')
                ->toArray();
            $antes = count($ids);
            $ids = array_unique(array_merge($ids, $newIds));
            $iteracao++;
            if (empty($newIds) || count($ids) === $antes || $iteracao >= $maxIteracoes) {
                break;
            }
        } while (true);
        return array_values($ids);
    }
}
