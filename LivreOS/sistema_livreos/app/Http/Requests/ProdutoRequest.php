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

use Illuminate\Validation\Rule;

class ProdutoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function defaultRules(): array
    {
        $produtoId = $this->route('produto')?->id;

        return [
            // Básicos
            'nome' => 'required|string|max:255',
            'codigo_sku' => [
                'nullable',
                'string',
                'max:100',
                Rule::unique('produtos', 'codigo_sku')->ignore($produtoId),
            ],
            'preco_venda' => 'nullable|numeric|min:0',
            'unidade' => 'required|string|max:30',
            'formato' => ['nullable', Rule::in(['simples', 'variacao', 'composicao'])],
            'condicao' => ['nullable', Rule::in(['novo', 'usado', 'recondicionado'])],
            'linha_produto' => 'nullable|string|max:100',
            'categoria_id' => 'nullable|integer|exists:categorias_produtos,id',

            // Características
            'marca' => 'nullable|string|max:100',
            'producao' => ['nullable', Rule::in(['propria', 'terceiros'])],
            'data_validade' => 'nullable|date',
            'frete_gratis' => 'boolean',
            'peso_liquido' => 'nullable|numeric|min:0',
            'peso_bruto' => 'nullable|numeric|min:0',
            'largura' => 'nullable|numeric|min:0',
            'altura' => 'nullable|numeric|min:0',
            'profundidade' => 'nullable|numeric|min:0',
            'volumes' => 'nullable|integer|min:0',
            'itens_por_caixa' => 'nullable|integer|min:0',
            'ean' => ['nullable', 'string', 'max:80', function ($attribute, $value, $fail) use ($produtoId) {
                if (!$value) {
                    return;
                }
                if ($produtoId) {
                    $original = \App\Models\Produto::whereKey($produtoId)->value('ean');
                    if ($original === $value) {
                        return;
                    }
                }
                if (!$this->validarEAN($value)) {
                    $fail('O EAN informado é inválido.');
                }
            }],
            'ean_tributario' => 'nullable|string|max:80',
            'descricao_curta' => 'nullable|string|max:255',
            'descricao_complementar' => 'nullable|string|max:2000',
            'link_externo' => 'nullable|url|max:255',
            'video_url' => 'nullable|url|max:255',
            'observacoes' => 'nullable|string|max:2000',
            'tags' => 'nullable',

            // Estoque
            'estoque_minimo' => 'nullable|integer|min:0',
            'estoque_maximo' => 'nullable|integer|min:0',
            'estoque_crossdocking' => 'nullable|integer|min:0',
            'estoque_localizacao' => 'nullable|string|max:100',
            'estoque_deposito_id' => 'nullable|integer|exists:depositos,id',
            'estoque_quantidade' => 'nullable|numeric|min:0',
            'estoque_preco_compra_unitario' => 'nullable|numeric|min:0',
            'estoque_preco_custo_unitario' => 'nullable|numeric|min:0',
            'estoque_observacao' => 'nullable|string|max:2000',

            // Tributação
            'tributacao_origem' => ['nullable', Rule::in(['0','1','2','3','4','5','6','7','8'])],
            'tributacao_ncm' => ['nullable', 'regex:/^\d{8}$/', function ($attribute, $value, $fail) {
                if (!$value) {
                    return;
                }
                $temBase = \App\Models\NcmCode::query()->exists();
                if ($temBase && !\App\Models\NcmCode::where('codigo', $value)->exists()) {
                    $fail('O NCM informado não existe na base oficial.');
                }
            }],
            'tributacao_cest' => ['nullable', 'regex:/^\d{7}$/'],
            'tributacao_tipo_item' => 'nullable|string|max:2',
            'tributacao_percentual_tributos' => 'nullable|numeric|min:0',
            'tributacao_grupo_produtos' => 'nullable|string|max:100',
            'tributacao_info_adicionais' => 'nullable|string|max:500',

            // Tabelas de preço
            'tabelas_precos' => 'nullable|array',
            'tabelas_precos.*.tabela_preco_id' => 'nullable|integer|exists:tabelas_precos,id',
            'tabelas_precos.*.preco' => 'nullable|numeric|min:0',

            // Imagens
            'imagens_tipo' => 'nullable|array',
            'imagens_tipo.*' => ['nullable', Rule::in(['upload', 'url'])],
            'imagens_url' => 'nullable|array',
            'imagens_url.*' => 'nullable|url|max:255',
            'imagens_ordem' => 'nullable|array',
            'imagens_ordem.*' => 'nullable|integer|min:0',
            'imagens_arquivo' => 'nullable|array',
            'imagens_arquivo.*' => 'nullable|file|mimes:jpg,jpeg,png,gif,webp,avif,heic,heif|max:5120',
            'imagens_remover' => 'nullable|array',
            'imagens_remover.*' => 'nullable|integer|exists:produto_imagens,id',

            // Fornecedores
            'fornecedores' => 'nullable|array',
            'fornecedores.*.fornecedor_id' => 'nullable|integer|exists:contatos,id',
            'fornecedores.*.descricao' => 'nullable|string|max:255',
            'fornecedores.*.codigo_fornecedor' => 'nullable|string|max:100',
            'fornecedores.*.preco_compra' => 'nullable|numeric|min:0',
            'fornecedores.*.preco_custo' => 'nullable|numeric|min:0',
            'fornecedores.*.garantia_meses' => 'nullable|integer|min:0',

            // Variações
            'variacao_atributos' => 'nullable|array',
            'variacao_atributos.*.atributo_nome' => 'nullable|string|max:100',
            'variacao_atributos.*.opcoes' => 'nullable|string',

            'variacoes' => 'nullable|array',
            'variacoes.*.referencia_sku' => 'nullable|string|max:100',
            'variacoes.*.opcoes_valores' => 'nullable|string',
            'variacoes.*.ean_variacao' => 'nullable|string|max:80',
            'variacoes.*.quantidade' => 'nullable|numeric|min:0',
            'variacoes.*.herdar_do_pai' => 'nullable|boolean',

            // Composição
            'composicoes' => 'nullable|array',
            'composicoes.*.componente_id' => 'nullable|integer|exists:produtos,id',
            'composicoes.*.quantidade' => 'nullable|integer|min:1',
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->convertBrNumbers([
            'preco_venda',
            'peso_liquido',
            'peso_bruto',
            'largura',
            'altura',
            'profundidade',
            'estoque_quantidade',
            'estoque_preco_compra_unitario',
            'estoque_preco_custo_unitario',
            'tributacao_percentual_tributos',
            'tabelas_precos.*.preco',
            'fornecedores.*.preco_compra',
            'fornecedores.*.preco_custo',
            'variacoes.*.quantidade',
        ]);
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            if ($this->input('formato') !== 'variacao') {
                return;
            }
            $estoqueQuantidade = $this->input('estoque_quantidade');
            if ($estoqueQuantidade === null || $estoqueQuantidade === '') {
                return;
            }
            $estoqueQuantidade = (float) $estoqueQuantidade;
            $variacoes = $this->input('variacoes', []);
            $somaVariacoes = 0;
            foreach ($variacoes as $v) {
                $qtd = $v['quantidade'] ?? null;
                if ($qtd !== null && $qtd !== '') {
                    $somaVariacoes += (float) $qtd;
                }
            }
            if ($somaVariacoes > $estoqueQuantidade) {
                $validator->errors()->add(
                    'variacoes',
                    'A soma das quantidades das variações (' . number_format($somaVariacoes, 2, ',', '.') . ') não pode superar o estoque do produto (' . number_format($estoqueQuantidade, 2, ',', '.') . ').'
                );
            }
        });
    }

    private function validarEAN(string $ean): bool
    {
        $ean = preg_replace('/\D/', '', $ean);
        $length = strlen($ean);
        if (!in_array($length, [8, 12, 13, 14], true)) {
            return false;
        }
        $sum = 0;
        $checkDigit = (int) $ean[$length - 1];
        for ($i = $length - 2, $pos = 0; $i >= 0; $i--, $pos++) {
            $digit = (int) $ean[$i];
            $sum += ($pos % 2 === 0) ? $digit * 3 : $digit;
        }
        $calc = (10 - ($sum % 10)) % 10;
        return $calc === $checkDigit;
    }
}
