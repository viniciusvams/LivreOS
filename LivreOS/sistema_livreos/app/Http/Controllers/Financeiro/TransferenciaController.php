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
use App\Models\MovimentacaoFinanceira;
use App\Models\ContaBancaria;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class TransferenciaController extends Controller
{
    /**
     * Lista todas as transferências entre contas (ativas e canceladas).
     * Filtro: todas | ativas | canceladas.
     */
    public function index(Request $request)
    {
        $tenantId = optional(auth()->user())->tenant_id;

        $query = MovimentacaoFinanceira::withTrashed()
            ->where('origem', 'transferencia')
            ->whereNull('transferencia_id')
            ->with([
                'contaBancaria',
                'canceladoPor',
                'transferenciaEntrada' => fn ($q) => $q->withTrashed()->with('contaBancaria'),
            ])
            ->when($tenantId !== null, fn ($q) => $q->where('tenant_id', $tenantId));

        $status = $request->query('status', 'todas');
        if ($status === 'ativas') {
            $query->whereNull('deleted_at');
        } elseif ($status === 'canceladas') {
            $query->whereNotNull('deleted_at');
        }

        $query->orderBy('data_movimentacao', 'desc')->orderBy('id', 'desc');
        $transferencias = $query->paginate(20)->withQueryString();

        return erp_view('financeiro.transferencias.index', [
            'title' => 'Transferências entre contas',
            'transferencias' => $transferencias,
            'statusFiltro' => $status,
        ]);
    }

    public function create()
    {
        $tenantId = optional(auth()->user())->tenant_id;
        $contasBancarias = ContaBancaria::where('ativo', true)
            ->when($tenantId !== null, fn ($q) => $q->where('tenant_id', $tenantId))
            ->orderBy('nome')
            ->get();

        return erp_view('financeiro.transferencias.create', [
            'title' => 'Transferência entre contas',
            'contasBancarias' => $contasBancarias,
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'conta_origem_id' => 'required|exists:contas_bancarias,id',
            'conta_destino_id' => 'required|exists:contas_bancarias,id|different:conta_origem_id',
            'valor' => 'required|numeric|min:0.01',
            'data_movimentacao' => 'required|date',
            'descricao' => 'nullable|string|max:255',
        ], [
            'conta_destino_id.different' => 'A conta de destino deve ser diferente da conta de origem.',
        ]);

        $contaOrigemId = (int) $validated['conta_origem_id'];
        $contaDestinoId = (int) $validated['conta_destino_id'];
        $valor = (float) $validated['valor'];
        $data = $validated['data_movimentacao'];
        $descricao = $validated['descricao'] ?? 'Transferência entre contas';
        $tenantId = optional(auth()->user())->tenant_id;
        $userId = auth()->id();

        DB::beginTransaction();
        try {
            $movSaida = MovimentacaoFinanceira::create([
                'tenant_id' => $tenantId,
                'conta_bancaria_id' => $contaOrigemId,
                'tipo' => 'saida',
                'origem' => 'transferencia',
                'valor' => $valor,
                'data_movimentacao' => $data,
                'descricao' => $descricao,
                'conciliado' => true,
                'data_conciliacao' => now(),
                'conciliado_por' => $userId,
                'created_by' => $userId,
                'updated_by' => $userId,
            ]);

            MovimentacaoFinanceira::create([
                'tenant_id' => $tenantId,
                'conta_bancaria_id' => $contaDestinoId,
                'tipo' => 'entrada',
                'origem' => 'transferencia',
                'transferencia_id' => $movSaida->id,
                'valor' => $valor,
                'data_movimentacao' => $data,
                'descricao' => $descricao,
                'conciliado' => true,
                'data_conciliacao' => now(),
                'conciliado_por' => $userId,
                'created_by' => $userId,
                'updated_by' => $userId,
            ]);

            DB::commit();
            return redirect()
                ->route('financeiro.transferencias.index')
                ->with('success', 'Transferência realizada com sucesso!');
        } catch (\Throwable $e) {
            DB::rollBack();
            return redirect()
                ->back()
                ->withInput()
                ->with('error', 'Erro ao realizar transferência: ' . $e->getMessage());
        }
    }

    /**
     * Cancela uma transferência entre contas (soft delete das duas movimentações do par).
     * Requer motivo; grava cancelado_por e data_cancelamento. Apenas admin ou permissão financeiro.transferencias.cancelar.
     */
    public function cancelar(Request $request, MovimentacaoFinanceira $movimentacao)
    {
        $user = auth()->user();
        if (!$user->is_admin && !$user->hasPermission('financeiro.transferencias.cancelar')) {
            abort(403, 'Você não tem permissão para cancelar transferências.');
        }

        if ($movimentacao->origem !== 'transferencia') {
            return redirect()->back()->with('error', 'Esta movimentação não é uma transferência.');
        }

        $validated = $request->validate([
            'motivo_cancelamento_transferencia' => 'required|string|max:2000',
        ], [
            'motivo_cancelamento_transferencia.required' => 'Informe o motivo do cancelamento.',
        ]);

        $movimentacao->load(['transferencia', 'transferenciaEntrada']);
        $par = $movimentacao->getParTransferencia();
        if (count($par) !== 2) {
            return redirect()->back()->with('error', 'Par da transferência não encontrado.');
        }

        [$saida, $entrada] = $par;
        $tenantId = optional(auth()->user())->tenant_id;
        if ($tenantId !== null && ($saida->tenant_id !== $tenantId || $entrada->tenant_id !== $tenantId)) {
            abort(403, 'Transferência de outro tenant.');
        }

        $motivo = $validated['motivo_cancelamento_transferencia'];
        $userId = auth()->id();
        $now = now();

        DB::beginTransaction();
        try {
            foreach ([$saida, $entrada] as $mov) {
                $mov->update([
                    'motivo_cancelamento_transferencia' => $motivo,
                    'cancelado_por' => $userId,
                    'data_cancelamento' => $now,
                    'updated_by' => $userId,
                ]);
                $mov->delete();
            }
            DB::commit();
            return redirect()->route('financeiro.transferencias.index', ['status' => 'canceladas'])->with('success', 'Transferência cancelada. As duas movimentações foram removidas.');
        } catch (\Throwable $e) {
            DB::rollBack();
            return redirect()->back()->with('error', 'Erro ao cancelar transferência: ' . $e->getMessage());
        }
    }
}
