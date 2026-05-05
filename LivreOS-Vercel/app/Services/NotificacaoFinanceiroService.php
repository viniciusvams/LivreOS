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

namespace App\Services;

use App\Models\ContaPagar;
use App\Models\ContaReceber;
use App\Models\MovimentacaoFinanceira;
use App\Models\NotificacaoUsuario;
use App\Models\Produto;
use App\Models\PropostaComercial;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class NotificacaoFinanceiroService
{
    /** Expressão SQL do valor pendente (alinha ao accessor de ContaReceber). */
    private function sqlPendenteCr(): string
    {
        return '(valor + COALESCE(juros,0) + COALESCE(multa,0) - COALESCE(desconto,0) - COALESCE(valor_recebido,0))';
    }

    /** Expressão SQL do valor pendente em contas a pagar. */
    private function sqlPendenteCp(): string
    {
        return '(valor - COALESCE(valor_pago,0))';
    }

    private function baseReceberQuery(): Builder
    {
        $q = ContaReceber::query();
        if ($this->safeHasColumn('contas_receber', 'status_estrutura')) {
            $q->whereNotIn('status_estrutura', ['agrupado', 'desmembrado']);
        }

        return function_exists('apply_filters')
            ? apply_filters('entity.index.query', $q, 'conta_receber')
            : $q;
    }

    private function basePagarQuery(): Builder
    {
        $q = ContaPagar::query();
        if ($this->safeHasColumn('contas_pagar', 'status_estrutura')) {
            $q->whereNotIn('status_estrutura', ['agrupado', 'desmembrado']);
        }

        return function_exists('apply_filters')
            ? apply_filters('entity.index.query', $q, 'conta_pagar')
            : $q;
    }

    private function basePropostaQuery(): Builder
    {
        $q = PropostaComercial::query();
        if ($this->safeHasColumn('propostas_comerciais', 'is_versao_atual')) {
            $q->where('is_versao_atual', true);
        }

        return function_exists('apply_filters')
            ? apply_filters('entity.index.query', $q, 'proposta_comercial')
            : $q;
    }

    /** Evita SQL 500 quando migrations não foram aplicadas por completo (ex.: hospedagem compartilhada). */
    private function safeHasColumn(string $table, string $column): bool
    {
        try {
            return Schema::hasTable($table) && Schema::hasColumn($table, $column);
        } catch (\Throwable) {
            return false;
        }
    }

    private function safeHasTable(string $table): bool
    {
        try {
            return Schema::hasTable($table);
        } catch (\Throwable) {
            return false;
        }
    }

    private function baseProdutoQuery(): Builder
    {
        $q = Produto::query();

        return function_exists('apply_filters')
            ? apply_filters('entity.index.query', $q, 'produto')
            : $q;
    }

    private function jaGerouNotif(User $user, string $referenciaTipo, string $dataRef): bool
    {
        return NotificacaoUsuario::withTrashed()
            ->where('user_id', $user->id)
            ->where('referencia_tipo', $referenciaTipo)
            ->whereDate('referencia_data', $dataRef)
            ->exists();
    }

    /**
     * Gera notificações do sininho (idempotente: até uma vez por dia por usuário).
     * Inclui financeiro (vence hoje, vencidas), movimentações não conciliadas, propostas e estoque baixo.
     */
    public function garantirNotificacoesDiarias(User $user): void
    {
        $hoje     = now()->toDateString();
        $cacheKey = "notif_header_diarias_{$user->id}_{$hoje}";

        if (cache()->has($cacheKey)) {
            return;
        }

        try {
            $this->gerarNotificacoesDiariasInterno($user, $hoje);
            cache()->put($cacheKey, true, now()->endOfDay());
        } catch (\Throwable $e) {
            report($e);
        }
    }

    /**
     * Gera notificações (chamado dentro de {@see garantirNotificacoesDiarias} com try/catch).
     */
    private function gerarNotificacoesDiariasInterno(User $user, string $hoje): void
    {
        $podeVerCR = $user->is_admin || $user->hasPermission('contas_receber.view') || $user->canAccessOperational();
        $podeVerCP = $user->is_admin || $user->hasPermission('contas_pagar.view') || $user->canAccessOperational();
        $podeFin   = $user->is_admin || $user->hasPermission('access-financeiro');
        $podeProp  = $user->is_admin || $user->hasPermission('view-propostas-comerciais');
        $podeOp    = $user->canAccessOperational();

        $pendCr = $this->sqlPendenteCr();
        $pendCp = $this->sqlPendenteCp();

        // --- Contas a receber: vencem hoje ---
        if ($podeVerCR && !$this->jaGerouNotif($user, 'vence_hoje_cr', $hoje)) {
            $q = (clone $this->baseReceberQuery())
                ->whereIn('status', ['aberto', 'parcial'])
                ->whereDate('data_vencimento', $hoje)
                ->whereRaw("{$pendCr} > 0.005");
            $totalCR = (clone $q)->count();
            if ($totalCR > 0) {
                $valorCR = (clone $q)->sum(DB::raw($pendCr));
                NotificacaoUsuario::create([
                    'user_id'         => $user->id,
                    'tipo'            => 'financeiro',
                    'titulo'          => 'Contas a receber vencem hoje',
                    'mensagem'        => "{$totalCR} conta(s) com saldo pendente total de R$ " . number_format((float) $valorCR, 2, ',', '.') . ' vencem hoje.',
                    'url'             => route('financeiro.contas-receber.index'),
                    'icone'           => 'alerta',
                    'referencia_tipo' => 'vence_hoje_cr',
                    'referencia_data' => $hoje,
                ]);
            }
        }

        // --- Contas a pagar: vencem hoje ---
        if ($podeVerCP && !$this->jaGerouNotif($user, 'vence_hoje_cp', $hoje)) {
            $q = (clone $this->basePagarQuery())
                ->whereIn('status', ['aberto', 'parcial'])
                ->whereDate('data_vencimento', $hoje)
                ->whereRaw("{$pendCp} > 0.005");
            $totalCP = (clone $q)->count();
            if ($totalCP > 0) {
                $valorCP = (clone $q)->sum(DB::raw($pendCp));
                NotificacaoUsuario::create([
                    'user_id'         => $user->id,
                    'tipo'            => 'financeiro',
                    'titulo'          => 'Contas a pagar vencem hoje',
                    'mensagem'        => "{$totalCP} conta(s) com saldo pendente total de R$ " . number_format((float) $valorCP, 2, ',', '.') . ' vencem hoje.',
                    'url'             => route('financeiro.contas-pagar.index'),
                    'icone'           => 'erro',
                    'referencia_tipo' => 'vence_hoje_cp',
                    'referencia_data' => $hoje,
                ]);
            }
        }

        // --- Contas a receber: vencidas ---
        if ($podeVerCR && !$this->jaGerouNotif($user, 'vencidas_cr', $hoje)) {
            $q = (clone $this->baseReceberQuery())
                ->whereIn('status', ['aberto', 'parcial'])
                ->whereDate('data_vencimento', '<', $hoje)
                ->whereRaw("{$pendCr} > 0.005");
            $n = (clone $q)->count();
            if ($n > 0) {
                $valor = (clone $q)->sum(DB::raw($pendCr));
                NotificacaoUsuario::create([
                    'user_id'         => $user->id,
                    'tipo'            => 'financeiro',
                    'titulo'          => 'Contas a receber vencidas',
                    'mensagem'        => "{$n} conta(s) em atraso com saldo pendente de R$ " . number_format((float) $valor, 2, ',', '.') . '.',
                    'url'             => route('financeiro.contas-receber.index', ['vencidas' => 1]),
                    'icone'           => 'erro',
                    'referencia_tipo' => 'vencidas_cr',
                    'referencia_data' => $hoje,
                ]);
            }
        }

        // --- Contas a pagar: vencidas ---
        if ($podeVerCP && !$this->jaGerouNotif($user, 'vencidas_cp', $hoje)) {
            $q = (clone $this->basePagarQuery())
                ->whereIn('status', ['aberto', 'parcial'])
                ->whereDate('data_vencimento', '<', $hoje)
                ->whereRaw("{$pendCp} > 0.005");
            $n = (clone $q)->count();
            if ($n > 0) {
                $valor = (clone $q)->sum(DB::raw($pendCp));
                NotificacaoUsuario::create([
                    'user_id'         => $user->id,
                    'tipo'            => 'financeiro',
                    'titulo'          => 'Contas a pagar vencidas',
                    'mensagem'        => "{$n} conta(s) em atraso com saldo pendente de R$ " . number_format((float) $valor, 2, ',', '.') . '.',
                    'url'             => route('financeiro.contas-pagar.index', ['vencidas' => 1]),
                    'icone'           => 'erro',
                    'referencia_tipo' => 'vencidas_cp',
                    'referencia_data' => $hoje,
                ]);
            }
        }

        // --- Movimentações não conciliadas ---
        if ($podeFin && !$this->jaGerouNotif($user, 'movimentacoes_nao_conciliadas', $hoje)) {
            $tenantId = $user->tenant_id ?? null;
            $qMov     = MovimentacaoFinanceira::query()
                ->whereNull('data_cancelamento')
                ->where('conciliado', false);
            if ($tenantId !== null) {
                $qMov->where('tenant_id', $tenantId);
            }
            $nMov = $qMov->count();
            if ($nMov > 0) {
                NotificacaoUsuario::create([
                    'user_id'         => $user->id,
                    'tipo'            => 'financeiro',
                    'titulo'          => 'Movimentações pendentes de conciliação',
                    'mensagem'        => "{$nMov} movimentação(ões) ainda não conciliada(s).",
                    'url'             => route('financeiro.movimentacoes.index', ['conciliado' => '0']),
                    'icone'           => 'alerta',
                    'referencia_tipo' => 'movimentacoes_nao_conciliadas',
                    'referencia_data' => $hoje,
                ]);
            }
        }

        // --- Propostas: validade hoje (versão atual, rascunho ou enviada) ---
        if ($podeProp && $this->safeHasTable('propostas_comerciais')
            && $this->safeHasColumn('propostas_comerciais', 'validade_em')
            && !$this->jaGerouNotif($user, 'propostas_validade_hoje', $hoje)) {
            $nHoje = (clone $this->basePropostaQuery())
                ->whereIn('status', [PropostaComercial::STATUS_RASCUNHO, PropostaComercial::STATUS_ENVIADA])
                ->whereNotNull('validade_em')
                ->whereDate('validade_em', $hoje)
                ->count();
            if ($nHoje > 0) {
                NotificacaoUsuario::create([
                    'user_id'         => $user->id,
                    'tipo'            => 'comercial',
                    'titulo'          => 'Propostas com validade hoje',
                    'mensagem'        => "{$nHoje} proposta(s) em rascunho ou enviada(s) com validade para hoje.",
                    'url'             => route('propostas-comerciais.index', ['alerta_proposta_validade' => 'hoje']),
                    'icone'           => 'alerta',
                    'referencia_tipo' => 'propostas_validade_hoje',
                    'referencia_data' => $hoje,
                ]);
            }
        }

        // --- Propostas: validade já vencida ---
        if ($podeProp && $this->safeHasTable('propostas_comerciais')
            && $this->safeHasColumn('propostas_comerciais', 'validade_em')
            && !$this->jaGerouNotif($user, 'propostas_validade_vencida', $hoje)) {
            $nVenc = (clone $this->basePropostaQuery())
                ->whereIn('status', [PropostaComercial::STATUS_RASCUNHO, PropostaComercial::STATUS_ENVIADA])
                ->whereNotNull('validade_em')
                ->whereDate('validade_em', '<', $hoje)
                ->count();
            if ($nVenc > 0) {
                NotificacaoUsuario::create([
                    'user_id'         => $user->id,
                    'tipo'            => 'comercial',
                    'titulo'          => 'Propostas com validade vencida',
                    'mensagem'        => "{$nVenc} proposta(s) em rascunho ou enviada(s) com data de validade anterior a hoje.",
                    'url'             => route('propostas-comerciais.index', ['alerta_proposta_validade' => 'vencida']),
                    'icone'           => 'erro',
                    'referencia_tipo' => 'propostas_validade_vencida',
                    'referencia_data' => $hoje,
                ]);
            }
        }

        // --- Estoque em nível baixo ---
        if ($podeOp && $this->safeHasTable('produtos')
            && $this->safeHasColumn('produtos', 'estoque_minimo')
            && $this->safeHasColumn('produtos', 'estoque_quantidade')
            && !$this->jaGerouNotif($user, 'produtos_estoque_baixo', $hoje)) {
            $nEst = (clone $this->baseProdutoQuery())
                ->whereNotNull('estoque_minimo')
                ->whereColumn('estoque_quantidade', '<=', 'estoque_minimo')
                ->count();
            if ($nEst > 0) {
                NotificacaoUsuario::create([
                    'user_id'         => $user->id,
                    'tipo'            => 'estoque',
                    'titulo'          => 'Produtos com estoque baixo',
                    'mensagem'        => "{$nEst} produto(s) com quantidade igual ou abaixo do mínimo cadastrado.",
                    'url'             => route('produtos.index', ['estoque_status' => 'baixo']),
                    'icone'           => 'alerta',
                    'referencia_tipo' => 'produtos_estoque_baixo',
                    'referencia_data' => $hoje,
                ]);
            }
        }

        if (function_exists('do_action')) {
            do_action('notificacoes.header.diarias.depois', $user);
        }
    }

    /**
     * @deprecated Use {@see garantirNotificacoesDiarias}; mantido para compatibilidade.
     */
    public function garantirNotificacoesVenceHoje(User $user): void
    {
        $this->garantirNotificacoesDiarias($user);
    }

    /**
     * Retorna notificações não lidas do usuário (para o dropdown).
     */
    public function naoLidas(User $user, int $limit = 10): \Illuminate\Support\Collection
    {
        return NotificacaoUsuario::where('user_id', $user->id)
            ->where('lida', false)
            ->orderByDesc('created_at')
            ->limit($limit)
            ->get();
    }

    /**
     * Retorna todas as notificações do usuário (para a página de histórico).
     */
    public function todas(User $user, int $limit = 50): \Illuminate\Support\Collection
    {
        return NotificacaoUsuario::where('user_id', $user->id)
            ->orderByDesc('created_at')
            ->limit($limit)
            ->get();
    }
}
