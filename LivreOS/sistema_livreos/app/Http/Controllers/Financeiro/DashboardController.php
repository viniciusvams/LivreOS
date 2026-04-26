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
use App\Models\ContaBancaria;
use App\Models\MovimentacaoFinanceira;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function index()
    {
        // Resumo Financeiro — apenas pendentes (aberto)
        // Bug fix: status != cancelado incluía contas JÁ PAGAS inflando o total
        $totalReceber = $this->applyEntityQuery(ContaReceber::where('status', 'aberto'), 'conta_receber')->sum('valor');
        $totalRecebido = $this->applyEntityQuery(ContaReceber::where('status', 'pago'), 'conta_receber')->sum('valor_recebido');
        $totalPagar = $this->applyEntityQuery(ContaPagar::where('status', 'aberto'), 'conta_pagar')->sum('valor');
        $totalPago = $this->applyEntityQuery(ContaPagar::where('status', 'pago'), 'conta_pagar')->sum('valor_pago');
        $totalVencido = $this->applyEntityQuery(
            ContaReceber::where('status', 'aberto')->where('data_vencimento', '<', now()->toDateString()),
            'conta_receber'
        )->sum('valor');
        $totalVencidoPagar = $this->applyEntityQuery(
            ContaPagar::where('status', 'aberto')->where('data_vencimento', '<', now()->toDateString()),
            'conta_pagar'
        )->sum('valor');

        // Saldo das contas bancárias (apenas movimentações conciliadas = saldo real/limpo)
        $qContas = $this->applyEntityQuery(
            ContaBancaria::where('ativo', true)->with(['movimentacoes' => fn($q) => $q->where('conciliado', true)]),
            'conta_bancaria'
        );
        $contasBancarias = $qContas->get()
            ->map(function($conta) {
                return [
                    'id' => $conta->id,
                    'nome' => $conta->nome,
                    'saldo' => $conta->saldo_atual,
                ];
            });
        
        $saldoTotal = $contasBancarias->sum('saldo');

        // Movimentações não conciliadas (dinheiro em trânsito: baixado mas aguardando confirmação bancária)
        // Necessário para não subestimar o saldo projetado quando baixas ainda não foram conciliadas
        $movPendentesEntrada = $this->applyEntityQuery(
            MovimentacaoFinanceira::where('tipo', 'entrada')
                ->where('conciliado', false)
                ->whereNull('data_cancelamento'),
            'movimentacao_financeira'
        )->sum('valor');

        $movPendentesSaida = $this->applyEntityQuery(
            MovimentacaoFinanceira::where('tipo', 'saida')
                ->where('conciliado', false)
                ->whereNull('data_cancelamento'),
            'movimentacao_financeira'
        )->sum('valor');

        // Base para projeção = saldo conciliado + movimentações em trânsito
        // Isso evita que baixas recentes (não conciliadas) "sumam" do projetado
        $saldoBaseProjetado = $saldoTotal + $movPendentesEntrada - $movPendentesSaida;

        // Vence hoje
        $venceHojeReceber = $this->applyEntityQuery(
            ContaReceber::where('status', 'aberto')->where('data_vencimento', now()->toDateString()),
            'conta_receber'
        )->sum('valor');

        $venceHojePagar = $this->applyEntityQuery(
            ContaPagar::where('status', 'aberto')->where('data_vencimento', now()->toDateString()),
            'conta_pagar'
        )->sum('valor');

        $contasReceberHoje = $this->applyEntityQuery(
            ContaReceber::where('status', 'aberto')
                ->where('data_vencimento', now()->toDateString())
                ->with('cliente')
                ->orderByDesc('valor')
                ->limit(15),
            'conta_receber'
        )->get();

        $contasPagarHoje = $this->applyEntityQuery(
            ContaPagar::where('status', 'aberto')
                ->where('data_vencimento', now()->toDateString())
                ->with('fornecedor')
                ->orderByDesc('valor')
                ->limit(15),
            'conta_pagar'
        )->get();

        // Saldo projetado: base (conciliado + em trânsito) + CR abertos pendentes - CP abertos pendentes
        $crProj7 = $this->applyEntityQuery(
            ContaReceber::where('status', 'aberto')
                ->where('data_vencimento', '<=', now()->addDays(7)->toDateString())
                ->where(function ($q) {
                    $q->whereDoesntHave('movimentacoes')
                      ->orWhere(fn($q2) => $q2->whereHas('movimentacoes')->where('valor_recebido', '>', 0));
                }),
            'conta_receber'
        )->sum(DB::raw('valor - COALESCE(valor_recebido, 0)'));

        $cpProj7 = $this->applyEntityQuery(
            ContaPagar::where('status', 'aberto')
                ->where('data_vencimento', '<=', now()->addDays(7)->toDateString())
                ->where(function ($q) {
                    $q->whereDoesntHave('movimentacoes')
                      ->orWhere(fn($q2) => $q2->whereHas('movimentacoes')->where('valor_pago', '>', 0));
                }),
            'conta_pagar'
        )->sum(DB::raw('valor - COALESCE(valor_pago, 0)'));

        $saldoProjetado7d = $saldoBaseProjetado + $crProj7 - $cpProj7;

        $crProj30 = $this->applyEntityQuery(
            ContaReceber::where('status', 'aberto')
                ->where('data_vencimento', '<=', now()->addDays(30)->toDateString())
                ->where(function ($q) {
                    $q->whereDoesntHave('movimentacoes')
                      ->orWhere(fn($q2) => $q2->whereHas('movimentacoes')->where('valor_recebido', '>', 0));
                }),
            'conta_receber'
        )->sum(DB::raw('valor - COALESCE(valor_recebido, 0)'));

        $cpProj30 = $this->applyEntityQuery(
            ContaPagar::where('status', 'aberto')
                ->where('data_vencimento', '<=', now()->addDays(30)->toDateString())
                ->where(function ($q) {
                    $q->whereDoesntHave('movimentacoes')
                      ->orWhere(fn($q2) => $q2->whereHas('movimentacoes')->where('valor_pago', '>', 0));
                }),
            'conta_pagar'
        )->sum(DB::raw('valor - COALESCE(valor_pago, 0)'));

        $saldoProjetado30d = $saldoBaseProjetado + $crProj30 - $cpProj30;

        // Contas a receber próximas do vencimento (próximos 7 dias)
        $contasVencendo = $this->applyEntityQuery(
            ContaReceber::where('status', 'aberto')
                ->whereBetween('data_vencimento', [now()->toDateString(), now()->addDays(7)->toDateString()])
                ->with('cliente')
                ->orderBy('data_vencimento')
                ->limit(10),
            'conta_receber'
        )->get();

        // Contas a pagar próximas do vencimento
        $contasPagarVencendo = $this->applyEntityQuery(
            ContaPagar::where('status', 'aberto')
                ->whereBetween('data_vencimento', [now()->toDateString(), now()->addDays(7)->toDateString()])
                ->with('fornecedor')
                ->orderBy('data_vencimento')
                ->limit(10),
            'conta_pagar'
        )->get();

        // Movimentações recentes (excluindo canceladas) — ordenação por data de criação decrescente (lançamentos de hoje primeiro)
        $movimentacoesRecentes = $this->applyEntityQuery(
            MovimentacaoFinanceira::with(['contaBancaria', 'contaReceber', 'contaPagar'])
                ->whereNull('data_cancelamento')
                ->orderByDesc('created_at')
                ->limit(20),
            'movimentacao_financeira'
        )->get();
        
        // Gráfico de receitas vs despesas (últimos 6 meses)
        $receitasDespesas = $this->getReceitasDespesasUltimosMeses(6);

        $viewData = [
            'title' => 'Dashboard Financeiro',
            'totalReceber' => $totalReceber,
            'totalRecebido' => $totalRecebido,
            'totalPagar' => $totalPagar,
            'totalPago' => $totalPago,
            'totalVencido' => $totalVencido,
            'totalVencidoPagar' => $totalVencidoPagar,
            'contasBancarias' => $contasBancarias,
            'saldoTotal' => $saldoTotal,
            'movPendentesEntrada' => $movPendentesEntrada,
            'movPendentesSaida' => $movPendentesSaida,
            'saldoBaseProjetado' => $saldoBaseProjetado,
            'venceHojeReceber' => $venceHojeReceber,
            'venceHojePagar' => $venceHojePagar,
            'contasReceberHoje' => $contasReceberHoje,
            'contasPagarHoje' => $contasPagarHoje,
            'saldoProjetado7d' => $saldoProjetado7d,
            'saldoProjetado30d' => $saldoProjetado30d,
            'crProj7' => $crProj7,
            'cpProj7' => $cpProj7,
            'crProj30' => $crProj30,
            'cpProj30' => $cpProj30,
            'contasVencendo' => $contasVencendo,
            'contasPagarVencendo' => $contasPagarVencendo,
            'movimentacoesRecentes' => $movimentacoesRecentes,
            'receitasDespesas' => $receitasDespesas,
        ];

        $viewData = function_exists('apply_filters') ? apply_filters('financeiro.dashboard.data', $viewData) : $viewData;
        $viewData['dashboardCards'] = function_exists('apply_filters') ? apply_filters('financeiro.dashboard.cards', []) : [];
        return erp_view('financeiro.dashboard', $viewData);
    }
    
    private function getReceitasDespesasUltimosMeses($meses)
    {
        $data = [];
        for ($i = $meses - 1; $i >= 0; $i--) {
            $mes = now()->subMonths($i);
            $mesInicio = $mes->copy()->startOfMonth();
            $mesFim = $mes->copy()->endOfMonth();
            
            $receitas = $this->applyEntityQuery(
                MovimentacaoFinanceira::where('tipo', 'entrada')
                    ->where('conciliado', true)
                    ->whereNull('data_cancelamento')
                    ->whereBetween('data_movimentacao', [$mesInicio, $mesFim]),
                'movimentacao_financeira'
            )->sum('valor');

            $despesas = $this->applyEntityQuery(
                MovimentacaoFinanceira::where('tipo', 'saida')
                    ->where('conciliado', true)
                    ->whereNull('data_cancelamento')
                    ->whereBetween('data_movimentacao', [$mesInicio, $mesFim]),
                'movimentacao_financeira'
            )->sum('valor');
            
            $data[] = [
                'mes' => $mes->format('M/Y'),
                'receitas' => (float)$receitas,
                'despesas' => (float)$despesas,
            ];
        }
        
        return $data;
    }
}
