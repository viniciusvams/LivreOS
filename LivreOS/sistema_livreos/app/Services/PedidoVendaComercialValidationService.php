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

use App\Models\Cliente;
use App\Models\FormaPagamento;
use App\Models\PedidoVenda;
use App\Models\PedidoVendaItem;
use App\Models\User;

class PedidoVendaComercialValidationService
{
    public function __construct(
        private LimiteDescontoService $limiteDescontoService
    ) {}

    public function garantirClienteNaoBloqueadoParaVendas(int $clienteId): void
    {
        if ($clienteId <= 0) {
            return;
        }
        $cliente = Cliente::query()->find($clienteId);
        if ($cliente && $cliente->bloqueado_vendas) {
            throw new \RuntimeException(
                'Este cliente está bloqueado para vendas. Ajuste o cadastro do cliente (desmarque "Bloqueado para Vendas") ou selecione outro cliente.'
            );
        }
    }

    /**
     * Quem pode informar ou aumentar descontos no pedido (admin ou permissão específica).
     */
    public function usuarioPodeAplicarDescontoPedidoVenda(?User $user = null): bool
    {
        $user = $user ?? auth()->user();
        if (! $user) {
            return false;
        }
        if ($user->is_admin) {
            return true;
        }

        return $user->hasPermission('pedidos_venda.aplicar_desconto');
    }

    /**
     * Valida descontos: permissão para aplicar desconto + limites em Configurações → Limites de desconto.
     *
     * @param  array<string, mixed>  $dados  Dados validados do PedidoVendaRequest
     */
    public function garantirDescontosDentroDoLimiteSistema(array $dados, ?PedidoVenda $pedidoExistente = null): void
    {
        $itens = $dados['itens'] ?? [];
        if (! is_array($itens)) {
            $itens = [];
        }

        $podeDesconto = $this->usuarioPodeAplicarDescontoPedidoVenda();

        if (! $podeDesconto) {
            $this->garantirSemAumentoDescontoSemPermissao($dados, $itens, $pedidoExistente);

            return;
        }

        if (! $this->limiteDescontoService->usuarioSujeitoALimite()) {
            return;
        }

        foreach ($itens as $idx => $item) {
            if (! is_array($item)) {
                continue;
            }
            if (empty($item['descricao']) && empty($item['produto_id']) && empty($item['servico_id'])) {
                continue;
            }
            $q = (float) ($item['quantidade'] ?? 0);
            $pu = parse_br_number((string) ($item['preco_unitario'] ?? 0));
            $sub = $q * $pu;
            $descontoVal = (float) ($item['desconto'] ?? 0);
            $v = $this->limiteDescontoService->validarDescontoItem($sub, $descontoVal, 0);
            if (! $v['valido']) {
                throw new \RuntimeException('Item '.($idx + 1).': '.$v['mensagem']);
            }
        }

        $subBruto = 0.0;
        $somaDescItens = 0.0;
        foreach ($itens as $item) {
            if (! is_array($item)) {
                continue;
            }
            if (empty($item['descricao']) && empty($item['produto_id']) && empty($item['servico_id'])) {
                continue;
            }
            $q = (float) ($item['quantidade'] ?? 0);
            $pu = parse_br_number((string) ($item['preco_unitario'] ?? 0));
            $subBruto += $q * $pu;
            $somaDescItens += (float) ($item['desconto'] ?? 0);
        }

        $acrescimos = isset($dados['acrescimos_global']) ? (float) $dados['acrescimos_global'] : 0.0;
        $descontoGlobal = isset($dados['desconto_global']) ? (float) $dados['desconto_global'] : 0.0;

        $baseParaDescontoGlobal = max(0, $subBruto - $somaDescItens + $acrescimos);
        if ($baseParaDescontoGlobal > 0 && $descontoGlobal > 0) {
            $v = $this->limiteDescontoService->validarDescontoGlobal($baseParaDescontoGlobal, 'valor', $descontoGlobal);
            if (! $v['valido']) {
                throw new \RuntimeException('Desconto global: '.$v['mensagem']);
            }
        }
    }

    /**
     * Sem permissão de desconto: não permite desconto em linhas novas nem aumento em linhas / global existentes.
     *
     * @param  array<int, array<string, mixed>>  $itens
     */
    private function garantirSemAumentoDescontoSemPermissao(array $dados, array $itens, ?PedidoVenda $pedidoExistente): void
    {
        $descontoGlobal = isset($dados['desconto_global']) ? (float) $dados['desconto_global'] : 0.0;

        if ($pedidoExistente) {
            $maxGlobal = (float) $pedidoExistente->desconto_global;
            if ($descontoGlobal > $maxGlobal + 0.02) {
                throw new \RuntimeException(
                    'Sem permissão para aumentar o desconto global. É necessária a permissão "Aplicar desconto em pedidos de venda".'
                );
            }
        } elseif ($descontoGlobal > 0.009) {
            throw new \RuntimeException(
                'Sem permissão para aplicar desconto global. É necessária a permissão "Aplicar desconto em pedidos de venda".'
            );
        }

        foreach ($itens as $idx => $item) {
            if (! is_array($item)) {
                continue;
            }
            if (empty($item['descricao']) && empty($item['produto_id']) && empty($item['servico_id'])) {
                continue;
            }
            $novoDesc = (float) ($item['desconto'] ?? 0);
            $itemId = isset($item['id']) ? (int) $item['id'] : 0;

            if ($itemId > 0 && $pedidoExistente) {
                $ex = PedidoVendaItem::query()
                    ->where('pedido_venda_id', $pedidoExistente->id)
                    ->whereKey($itemId)
                    ->first();
                if ($ex && $novoDesc > (float) $ex->desconto + 0.02) {
                    throw new \RuntimeException(
                        'Sem permissão para aumentar o desconto no item '.($idx + 1)
                        .'. É necessária a permissão "Aplicar desconto em pedidos de venda".'
                    );
                }
            } elseif ($novoDesc > 0.009) {
                throw new \RuntimeException(
                    'Sem permissão para aplicar desconto nos itens. É necessária a permissão "Aplicar desconto em pedidos de venda".'
                );
            }
        }
    }

    /**
     * Limite de crédito do cliente: aplica-se ao valor "a prazo" (fiado, boleto, TED, cheque, etc.), não a PIX/dinheiro/cartão.
     *
     * @param  array<int, array<string, mixed>>  $pagamentosLinhas
     */
    public function garantirLimiteCreditoNoFaturamento(
        ?Cliente $cliente,
        string $tipoPagamento,
        float $valorAReceber,
        array $pagamentosLinhas
    ): void {
        if (! $cliente || $cliente->limite_credito === null) {
            return;
        }

        $limite = (float) $cliente->limite_credito;
        $valorUsoCredito = 0.0;

        if ($tipoPagamento === 'faturado' && $valorAReceber > 0) {
            $valorUsoCredito = $valorAReceber;
        } elseif ($tipoPagamento === 'agora' && $pagamentosLinhas !== []) {
            $ids = array_values(array_unique(array_filter(array_map(
                fn ($p) => (int) ($p['forma_pagamento_id'] ?? 0),
                $pagamentosLinhas
            ))));
            if ($ids === []) {
                return;
            }
            $formas = FormaPagamento::query()->whereIn('id', $ids)->get(['id', 'tipo']);
            foreach ($pagamentosLinhas as $p) {
                $fid = (int) ($p['forma_pagamento_id'] ?? 0);
                if ($fid <= 0) {
                    continue;
                }
                $forma = $formas->firstWhere('id', $fid);
                if (! $forma) {
                    $valorUsoCredito += (float) ($p['valor'] ?? 0);

                    continue;
                }
                if (FormaPagamento::tipoEhRecebimentoImediato($forma->tipo)) {
                    continue;
                }
                $valorUsoCredito += (float) ($p['valor'] ?? 0);
            }
        }

        if ($valorUsoCredito > $limite + 0.009) {
            throw new \RuntimeException(
                'Limite de crédito do cliente excedido para pagamentos a prazo (faturado/fiado, boleto, transferência, cheque, etc.). '
                .'Valor sujeito ao limite: R$ '.number_format($valorUsoCredito, 2, ',', '.')
                .'. Limite cadastrado: R$ '.number_format($limite, 2, ',', '.').'. '
                .'Use PIX, dinheiro ou cartão para parte do valor, aumente o limite no cadastro do cliente ou escolha outro cliente.'
            );
        }
    }
}
