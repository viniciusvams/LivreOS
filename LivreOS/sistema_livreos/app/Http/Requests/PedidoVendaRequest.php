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

namespace App\Http\Requests;

class PedidoVendaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->convertBrNumbers([
            'desconto_global',
            'acrescimos_global',
            'itens.*.preco_unitario',
            'itens.*.desconto',
        ]);
        // Quantidade: não usar convertBrNumbers (remove todos os pontos e transforma "10.000" em 10000).
        // Usar parse_br_number do helpers, que distingue milhar BR (1.234,56) de decimal (10.000 = 10).
        $this->normalizeItensQuantidade();
        // Hidden inputs / Alpine podem enviar "", "undefined" ou "null" — evita falha em nullable|integer.
        $this->normalizeItensIdsOpcionais();
        $this->normalizeValidadeOrcamentoDiasPedido();
    }

    private function normalizeValidadeOrcamentoDiasPedido(): void
    {
        if (! $this->has('validade_orcamento_dias')) {
            return;
        }
        $v = $this->input('validade_orcamento_dias');
        if ($v === null || $v === '') {
            $this->merge(['validade_orcamento_dias' => null]);

            return;
        }
        if (is_string($v)) {
            $t = strtolower(trim($v));
            if ($t === 'undefined' || $t === 'null') {
                $this->merge(['validade_orcamento_dias' => null]);
            }
        }
    }

    private function normalizeItensQuantidade(): void
    {
        $items = $this->input('itens', []);
        if (!is_array($items)) {
            return;
        }
        foreach ($items as $idx => $item) {
            if (!is_array($item)) {
                continue;
            }
            $q = $item['quantidade'] ?? null;
            if ($q === null || $q === '') {
                continue;
            }
            if (is_numeric($q) && !is_string($q)) {
                continue;
            }
            $items[$idx]['quantidade'] = (string) parse_br_number((string) $q);
        }
        $this->merge(['itens' => $items]);
    }

    /**
     * Converte IDs opcionais vazios ou lixo de front-end em null para validação exists|integer.
     */
    private function normalizeItensIdsOpcionais(): void
    {
        $items = $this->input('itens', []);
        if (! is_array($items)) {
            return;
        }
        $limpar = static function ($v) {
            if ($v === null || $v === '') {
                return null;
            }
            if (is_string($v)) {
                $t = strtolower(trim($v));
                if ($t === '' || $t === 'undefined' || $t === 'null') {
                    return null;
                }
            }

            return $v;
        };
        foreach ($items as $idx => $item) {
            if (! is_array($item)) {
                continue;
            }
            foreach (['id', 'produto_id', 'servico_id', 'produto_variacao_id'] as $k) {
                if (! array_key_exists($k, $item)) {
                    continue;
                }
                $items[$idx][$k] = $limpar($item[$k]);
            }
        }
        $this->merge(['itens' => $items]);
    }

    protected function defaultRules(): array
    {
        return [
            'cliente_id'            => 'required|integer|exists:clientes,id',
            'cliente_unidade_id'    => 'nullable|integer|exists:clientes,id',
            'contato_id'            => 'nullable|integer|exists:contatos,id',
            'endereco_id'           => 'nullable|integer|exists:enderecos,id',
            'vendedor_id'           => 'nullable|integer|exists:users,id',
            'tabela_preco_id'       => 'nullable|integer|exists:tabelas_precos,id',
            'canal_venda'           => 'nullable|string|max:50',
            'observacoes'           => 'nullable|string|max:5000',
            'observacoes_internas'  => 'nullable|string|max:5000',
            'data_pedido'           => 'nullable|date',
            'data_prevista_entrega' => 'nullable|date',
            'validade_orcamento_dias' => 'nullable|integer|min:1|max:3650',
            'endereco_entrega_id'   => 'nullable|integer|exists:enderecos,id',
            'desconto_global'       => 'nullable|numeric|min:0',
            'acrescimos_global'     => 'nullable|numeric|min:0',

            'itens'                      => 'nullable|array',
            'itens.*.id'                 => 'nullable|integer',
            'itens.*.tipo'               => 'nullable|string|in:produto,servico',
            'itens.*.produto_id'         => 'nullable|integer|exists:produtos,id',
            'itens.*.produto_variacao_id' => 'nullable|integer|exists:produto_variacoes,id',
            'itens.*.servico_id'         => 'nullable|integer|exists:servicos,id',
            'itens.*.descricao'          => 'nullable|string|max:255',
            'itens.*.quantidade'         => 'nullable|numeric|min:0.0001',
            'itens.*.preco_unitario'     => 'nullable|string|max:20',
            'itens.*.desconto'           => 'nullable|string|max:20',
            'itens.*.serial'             => 'nullable|string|max:100',
            'itens.*.gerar_os'           => 'nullable|boolean',
            'itens.*.observacao'         => 'nullable|string|max:500',
        ];
    }
}
