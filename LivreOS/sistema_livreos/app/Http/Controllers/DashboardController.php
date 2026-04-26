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

use App\Models\Cliente;
use App\Models\ContaPagar;
use App\Models\ContaReceber;
use App\Models\MovimentacaoFinanceira;
use App\Models\OrdemServico;
use App\Models\Produto;
use App\Models\Servico;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        $today = now()->toDateString();

        // Estatísticas do sistema (com filtros de plugins quando existirem)
        $totalClientes = $this->applyEntityQuery(Cliente::query(), 'cliente')->count();
        $totalProdutos = $this->applyEntityQuery(Produto::query(), 'produto')->count();
        $totalServicos = $this->applyEntityQuery(Servico::query(), 'servico')->count();
        $totalOrdens = $this->applyEntityQuery(OrdemServico::query(), 'ordem_servico')->count();
        $totalGarantias = $this->applyEntityQuery(
            OrdemServico::where('tipo_os', 'garantia'),
            'ordem_servico'
        )->count();

        // Vendas PDV (se plugin ativo)
        $totalVendas = 0;
        if (Schema::hasTable('plugin_pdv_vendas')) {
            $totalVendas = (int) DB::table('plugin_pdv_vendas')
                ->where('status', 'finalizada')
                ->count();
        }

        // Receita e despesa do dia (movimentações com data_movimentacao = hoje)
        $receitaDia = (float) $this->applyEntityQuery(
            MovimentacaoFinanceira::where('tipo', 'entrada')
                ->whereDate('data_movimentacao', $today)
                ->whereNull('data_cancelamento'),
            'movimentacao_financeira'
        )->sum('valor');

        $despesaDia = (float) $this->applyEntityQuery(
            MovimentacaoFinanceira::where('tipo', 'saida')
                ->whereDate('data_movimentacao', $today)
                ->whereNull('data_cancelamento'),
            'movimentacao_financeira'
        )->sum('valor');

        // Agenda das OS: formato mensal
        $ano = (int) $request->input('agenda_ano', now()->year);
        $mes = (int) $request->input('agenda_mes', now()->month);
        $agendaData = $this->getAgendaData($mes, $ano);
        $agendaPorDia = $agendaData['agendaPorDia'];
        $agendaParaModal = $agendaData['agendaParaModal'];
        $agendaPendentes = $agendaData['agendaPendentes'];
        $mesNome = $agendaData['mesNome'];
        $diasNoMes = $agendaData['diasNoMes'];
        $primeiroDiaSemana = $agendaData['primeiroDiaSemana'];
        $agendaMesAno = $agendaData['agendaMesAno'];
        $agendaMesMes = $agendaData['agendaMesMes'];
        $agendaMesAnoPrev = $agendaData['agendaMesAnoPrev'];
        $agendaMesMesPrev = $agendaData['agendaMesMesPrev'];
        $agendaMesAnoNext = $agendaData['agendaMesAnoNext'];
        $agendaMesMesNext = $agendaData['agendaMesMesNext'];
        $agendaPrimeiroDia = $agendaData['agendaPrimeiroDia'];

        // Contas a receber que vencem hoje
        $contasReceberHoje = $this->applyEntityQuery(
            ContaReceber::where('status', 'aberto')
                ->where('data_vencimento', $today)
                ->with('cliente')
                ->orderByDesc('valor')
                ->limit(10),
            'conta_receber'
        )->get();

        $totalReceberHoje = $contasReceberHoje->sum('valor');

        // Contas a pagar que vencem hoje
        $contasPagarHoje = $this->applyEntityQuery(
            ContaPagar::where('status', 'aberto')
                ->where('data_vencimento', $today)
                ->with('fornecedor')
                ->orderByDesc('valor')
                ->limit(10),
            'conta_pagar'
        )->get();

        $totalPagarHoje = $contasPagarHoje->sum('valor');

        // Últimas ordens de serviço (para card "Últimas OS")
        $ultimasOs = $this->applyEntityQuery(
            OrdemServico::with(['cliente'])
                ->orderByDesc('created_at')
                ->limit(8),
            'ordem_servico'
        )->get();

        $viewData = [
            'title' => 'Dashboard',
            'totalClientes' => $totalClientes,
            'totalProdutos' => $totalProdutos,
            'totalServicos' => $totalServicos,
            'totalOrdens' => $totalOrdens,
            'totalGarantias' => $totalGarantias,
            'totalVendas' => $totalVendas,
            'receitaDia' => $receitaDia,
            'despesaDia' => $despesaDia,
            'agendaOs' => null,
            'agendaPorDia' => $agendaPorDia,
            'agendaParaModal' => $agendaParaModal,
            'agendaPendentes' => $agendaPendentes,
            'agendaMesAno' => $agendaMesAno,
            'agendaMesMes' => $agendaMesMes,
            'agendaMesNome' => $mesNome,
            'agendaDiasNoMes' => $diasNoMes,
            'agendaPrimeiroDiaSemana' => $primeiroDiaSemana,
            'agendaMesAnoPrev' => $agendaMesAnoPrev,
            'agendaMesMesPrev' => $agendaMesMesPrev,
            'agendaMesAnoNext' => $agendaMesAnoNext,
            'agendaMesMesNext' => $agendaMesMesNext,
            'agendaPrimeiroDia' => $agendaPrimeiroDia,
            'contasReceberHoje' => $contasReceberHoje,
            'totalReceberHoje' => $totalReceberHoje,
            'contasPagarHoje' => $contasPagarHoje,
            'totalPagarHoje' => $totalPagarHoje,
            'ultimasOs' => $ultimasOs,
            'dashboard_extra_cards' => [],
            'dashboard_stats' => [
                ['label' => 'Clientes', 'value' => $totalClientes, 'format' => 'number'],
                ['label' => 'Produtos', 'value' => $totalProdutos, 'format' => 'number'],
                ['label' => 'Serviços', 'value' => $totalServicos, 'format' => 'number'],
                ['label' => 'Ordens', 'value' => $totalOrdens, 'format' => 'number'],
                ['label' => 'Garantias', 'value' => $totalGarantias, 'format' => 'number'],
                ['label' => 'Vendas', 'value' => $totalVendas, 'format' => 'number'],
                ['label' => 'Receita do dia', 'value' => $receitaDia, 'format' => 'currency', 'class' => 'text-green-600 dark:text-green-400'],
                ['label' => 'Despesa do dia', 'value' => $despesaDia, 'format' => 'currency', 'class' => 'text-red-600 dark:text-red-400'],
            ],
        ];

        if (function_exists('apply_filters')) {
            $viewData = apply_filters('dashboard.main.data', $viewData);
            $viewData['dashboard_stats'] = apply_filters('dashboard.stats', $viewData['dashboard_stats'] ?? []);
        }

        // Permite plugins adicionarem cards ao dashboard (array de ['title' => ..., 'view' => 'partial', 'data' => []] ou ['title' => ..., 'html' => '...'])
        if (function_exists('apply_filters')) {
            $viewData['dashboard_extra_cards'] = apply_filters('dashboard.extra_cards', $viewData['dashboard_extra_cards'] ?? []);
        }

        return erp_view('pages.dashboard.index', $viewData);
    }

    /** Status de OS considerados "ativos" (aparecem na agenda). */
    protected const AGENDA_STATUS_EXCLUIDOS = ['Encerrada', 'Encerrado', 'Cancelada', 'Cancelado'];

    /**
     * Retorna dados da agenda das OS para um mês/ano (usado no index e no endpoint JSON).
     */
    protected function getAgendaData(int $mes, int $ano): array
    {
        $primeiroDia = Carbon::createFromDate($ano, $mes, 1)->startOfDay();
        $ultimoDia = $primeiroDia->copy()->endOfMonth();
        $inicioStr = $primeiroDia->toDateString();
        $fimStr = $ultimoDia->toDateString();

        // OS com data dentro do mês selecionado
        $agendaOsQuery = $this->applyEntityQuery(
            OrdemServico::with(['cliente'])
                ->whereNotIn('status', self::AGENDA_STATUS_EXCLUIDOS)
                ->where(function ($q) use ($inicioStr, $fimStr) {
                    $q->whereBetween(DB::raw('DATE(prevista_conclusao)'), [$inicioStr, $fimStr])
                        ->orWhereBetween(DB::raw('DATE(prevista_inicio)'), [$inicioStr, $fimStr])
                        ->orWhereBetween(DB::raw('DATE(data_abertura)'), [$inicioStr, $fimStr]);
                }),
            'ordem_servico'
        )->get();

        // OS ativas com datas ANTERIORES ao mês selecionado (pendentes/atrasadas)
        $osPendentes = $this->applyEntityQuery(
            OrdemServico::with(['cliente'])
                ->whereNotIn('status', self::AGENDA_STATUS_EXCLUIDOS)
                ->where(function ($q) use ($inicioStr) {
                    $q->where(function ($q2) use ($inicioStr) {
                        $q2->whereNotNull('prevista_conclusao')
                            ->where(DB::raw('DATE(prevista_conclusao)'), '<', $inicioStr);
                    })->orWhere(function ($q2) use ($inicioStr) {
                        $q2->whereNull('prevista_conclusao')
                            ->whereNotNull('prevista_inicio')
                            ->where(DB::raw('DATE(prevista_inicio)'), '<', $inicioStr);
                    })->orWhere(function ($q2) use ($inicioStr) {
                        $q2->whereNull('prevista_conclusao')
                            ->whereNull('prevista_inicio')
                            ->whereNotNull('data_abertura')
                            ->where(DB::raw('DATE(data_abertura)'), '<', $inicioStr);
                    });
                }),
            'ordem_servico'
        )->orderBy('data_abertura')->get();

        $agendaPorDia = [];
        foreach ($agendaOsQuery as $os) {
            $dataRef = $os->prevista_conclusao ?? $os->prevista_inicio ?? $os->data_abertura;
            if (!$dataRef) {
                continue;
            }
            $dataRef = $dataRef instanceof \Carbon\Carbon ? $dataRef : Carbon::parse($dataRef);
            $chave = $dataRef->format('Y-m-d');
            if ($chave < $inicioStr || $chave > $fimStr) {
                continue;
            }
            if (!isset($agendaPorDia[$chave])) {
                $agendaPorDia[$chave] = [];
            }
            $agendaPorDia[$chave][] = $os;
        }

        foreach ($agendaPorDia as $chave => $lista) {
            usort($agendaPorDia[$chave], function ($a, $b) {
                $da = $a->prevista_conclusao ?? $a->prevista_inicio ?? $a->data_abertura;
                $db = $b->prevista_conclusao ?? $b->prevista_inicio ?? $b->data_abertura;
                return ($da <=> $db);
            });
        }

        $agendaParaModal = [];
        foreach ($agendaPorDia as $chave => $osList) {
            $agendaParaModal[$chave] = [];
            foreach ($osList as $os) {
                $agendaParaModal[$chave][] = [
                    'id' => $os->id,
                    'codigo' => $os->codigo_interno ?? 'OS #' . $os->id,
                    'cliente' => $os->cliente ? ($os->cliente->nome ?? $os->cliente->razao_social ?? '—') : '—',
                    'status' => $os->status ?? '—',
                    'show_url' => route('ordens-servico.show', $os),
                    'edit_url' => route('ordens-servico.edit', $os),
                    'pode_editar' => !in_array($os->status ?? '', ['Encerrada', 'Encerrado']),
                ];
            }
        }

        $agendaPendentes = [];
        foreach ($osPendentes as $os) {
            $dataRef = $os->prevista_conclusao ?? $os->prevista_inicio ?? $os->data_abertura;
            $dataFormatada = $dataRef
                ? (($dataRef instanceof \Carbon\Carbon ? $dataRef : Carbon::parse($dataRef))->format('d/m/Y'))
                : null;
            $agendaPendentes[] = [
                'id' => $os->id,
                'codigo' => $os->codigo_interno ?? 'OS #' . $os->id,
                'cliente' => $os->cliente ? ($os->cliente->nome ?? $os->cliente->razao_social ?? '—') : '—',
                'status' => $os->status ?? '—',
                'data_ref' => $dataFormatada,
                'show_url' => route('ordens-servico.show', $os),
                'edit_url' => route('ordens-servico.edit', $os),
                'pode_editar' => !in_array($os->status ?? '', ['Encerrada', 'Encerrado']),
            ];
        }

        $mesNome = $primeiroDia->locale('pt_BR')->monthName;
        $diasNoMes = $ultimoDia->day;
        $primeiroDiaSemana = (int) $primeiroDia->format('w');
        $agendaMesAnoPrev = $mes <= 1 ? $ano - 1 : $ano;
        $agendaMesMesPrev = $mes <= 1 ? 12 : $mes - 1;
        $agendaMesAnoNext = $mes >= 12 ? $ano + 1 : $ano;
        $agendaMesMesNext = $mes >= 12 ? 1 : $mes + 1;

        return [
            'agendaPorDia' => $agendaPorDia,
            'agendaParaModal' => $agendaParaModal,
            'agendaPendentes' => $agendaPendentes,
            'mesNome' => $mesNome,
            'ano' => $ano,
            'mes' => $mes,
            'diasNoMes' => $diasNoMes,
            'primeiroDiaSemana' => $primeiroDiaSemana,
            'agendaMesAno' => $ano,
            'agendaMesMes' => $mes,
            'agendaMesAnoPrev' => $agendaMesAnoPrev,
            'agendaMesMesPrev' => $agendaMesMesPrev,
            'agendaMesAnoNext' => $agendaMesAnoNext,
            'agendaMesMesNext' => $agendaMesMesNext,
            'agendaPrimeiroDia' => $primeiroDia,
        ];
    }

    /**
     * API da agenda das OS (mês/ano) para atualização sem recarregar a página.
     */
    public function agendaApi(Request $request)
    {
        $ano = (int) $request->input('agenda_ano', now()->year);
        $mes = (int) $request->input('agenda_mes', now()->month);
        $mes = max(1, min(12, $mes));
        $data = $this->getAgendaData($mes, $ano);
        return response()->json([
            'mesNome' => $data['mesNome'],
            'ano' => $data['ano'],
            'mes' => $data['mes'],
            'diasNoMes' => $data['diasNoMes'],
            'primeiroDiaSemana' => $data['primeiroDiaSemana'],
            'agendaParaModal' => $data['agendaParaModal'],
            'agendaPendentes' => $data['agendaPendentes'],
            'agendaMesAnoPrev' => $data['agendaMesAnoPrev'],
            'agendaMesMesPrev' => $data['agendaMesMesPrev'],
            'agendaMesAnoNext' => $data['agendaMesAnoNext'],
            'agendaMesMesNext' => $data['agendaMesMesNext'],
        ]);
    }
}
