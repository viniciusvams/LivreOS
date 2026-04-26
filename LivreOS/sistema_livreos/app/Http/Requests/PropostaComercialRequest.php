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

class PropostaComercialRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->convertBrNumbers([
            'itens.*.preco_unitario',
            'itens.*.desconto',
        ]);
        $this->normalizeItensQuantidade();
        $this->normalizeItensIdsOpcionais();
    }

    private function normalizeItensQuantidade(): void
    {
        $items = $this->input('itens', []);
        if (! is_array($items)) {
            return;
        }
        foreach ($items as $idx => $item) {
            if (! is_array($item)) {
                continue;
            }
            $q = $item['quantidade'] ?? null;
            if ($q === null || $q === '') {
                continue;
            }
            if (is_numeric($q) && ! is_string($q)) {
                continue;
            }
            $items[$idx]['quantidade'] = (string) parse_br_number((string) $q);
        }
        $this->merge(['itens' => $items]);
    }

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
            'vendedor_id'           => 'nullable|integer|exists:users,id',
            'tabela_preco_id'       => 'nullable|integer|exists:tabelas_precos,id',
            'titulo'                => 'nullable|string|max:255',
            'descricao'             => 'nullable|string|max:50000',
            'observacoes'           => 'nullable|string|max:50000',
            'observacoes_internas'  => 'nullable|string|max:50000',
            'data_emissao'          => 'required|date',
            'validade_dias'         => 'nullable|integer|min:1|max:3650',

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
        ];
    }
}
