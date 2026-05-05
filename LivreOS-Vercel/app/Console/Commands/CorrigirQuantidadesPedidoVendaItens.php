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

namespace App\Console\Commands;

use App\Models\PedidoVenda;
use App\Models\PedidoVendaItem;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class CorrigirQuantidadesPedidoVendaItens extends Command
{
    protected $signature = 'pedidos-venda:corrigir-quantidades-itens
                            {--dry-run : Apenas listar o que seria alterado (padrão)}
                            {--apply : Gravar correções no banco}
                            {--factor=1000 : Divisor (ex.: 1000 para bug 10.000→10000)}
                            {--pedido= : Limitar a um pedidos_venda.id}';

    protected $description = 'Corrige quantidades infladas nos itens de pedido de venda (ex.: fator 1000)';

    public function handle(): int
    {
        $factor = max(1.0, (float) $this->option('factor'));
        $pedidoId = $this->option('pedido') !== null && $this->option('pedido') !== ''
            ? (int) $this->option('pedido')
            : null;
        $apply = (bool) $this->option('apply');

        if (!$apply && !$this->option('dry-run')) {
            $this->warn('Nenhuma opção: usando --dry-run. Use --apply para gravar.');
        }

        $query = PedidoVendaItem::query()->orderBy('pedido_venda_id')->orderBy('id');

        if ($pedidoId) {
            $query->where('pedido_venda_id', $pedidoId);
        }

        $candidates = [];
        foreach ($query->cursor() as $item) {
            $q = round((float) $item->quantidade, 6);
            if ($q < $factor) {
                continue;
            }
            $remainder = fmod($q, $factor);
            if (abs($remainder) > 0.00001 && abs($remainder - $factor) > 0.00001) {
                continue;
            }
            $newQ = $q / $factor;
            if ($newQ < 0.0001 || $newQ > 1_000_000) {
                continue;
            }
            $candidates[] = [
                'id'        => $item->id,
                'pedido_id' => $item->pedido_venda_id,
                'tipo'      => $item->tipo,
                'descricao' => $item->descricao,
                'old_q'     => $q,
                'new_q'     => $newQ,
                'old_total' => (float) $item->total,
                'preco'     => (float) $item->preco_unitario,
                'desconto'  => (float) $item->desconto,
            ];
        }

        if ($candidates === []) {
            $this->info('Nenhum item encontrado com quantidade múltipla de ' . $factor . ' (≥ ' . $factor . ').');

            return self::SUCCESS;
        }

        $rows = [];
        foreach ($candidates as $c) {
            $newTotal = max(
                round($c['preco'] * $c['new_q'] - $c['desconto'], 2),
                0
            );
            $rows[] = [
                $c['pedido_id'],
                $c['id'],
                $c['tipo'],
                mb_strimwidth((string) $c['descricao'], 0, 40, '…'),
                $c['old_q'],
                $c['new_q'],
                $c['old_total'],
                $newTotal,
            ];
        }

        $this->table(
            ['Pedido', 'Item', 'Tipo', 'Descrição', 'Qtd atual', 'Qtd nova', 'Total atual', 'Total novo'],
            $rows
        );

        if (!$apply) {
            $this->newLine();
            $this->comment('Dry-run: nada foi gravado. Rode com --apply para aplicar.');
            $this->comment('Revise se há pedidos legítimos com quantidades múltiplas de ' . $factor . ' (ex.: 10.000 unidades).');

            return self::SUCCESS;
        }

        if (!$this->confirm('Confirma a correção destes ' . count($candidates) . ' item(ns)?', false)) {
            $this->warn('Cancelado.');

            return self::FAILURE;
        }

        $pedidosAfetados = [];

        DB::transaction(function () use ($candidates, $factor, &$pedidosAfetados) {
            foreach ($candidates as $c) {
                $item = PedidoVendaItem::query()->find($c['id']);
                if (!$item) {
                    continue;
                }
                $q = round((float) $item->quantidade, 6);
                if ($q < $factor) {
                    continue;
                }
                $remainder = fmod($q, $factor);
                if (abs($remainder) > 0.00001 && abs($remainder - $factor) > 0.00001) {
                    continue;
                }
                $newQ = $q / $factor;
                $item->quantidade = $newQ;
                $item->save();
                $pedidosAfetados[$item->pedido_venda_id] = true;
            }
        });

        foreach (array_keys($pedidosAfetados) as $pid) {
            $pedido = PedidoVenda::with('itens')->find($pid);
            if ($pedido) {
                $pedido->recalcularTotais();
                $pedido->save();
            }
        }

        $this->info('Correção aplicada. Pedidos atualizados: ' . implode(', ', array_keys($pedidosAfetados)));

        return self::SUCCESS;
    }
}
