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

class ServicoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function defaultRules(): array
    {
        $servicoId = $this->route('servico')?->id;

        return [
            'nome' => 'required|string|max:255',
            'preco' => 'nullable|numeric|min:0',
            'unidade' => 'required|string|max:50',
            'unidade_outro' => [
                'nullable',
                'string',
                'max:50',
                Rule::requiredIf($this->input('unidade') === 'outro'),
            ],
            'codigo_sku' => [
                'nullable',
                'string',
                'max:100',
                Rule::unique('servicos', 'codigo_sku')->ignore($servicoId),
            ],
            'descricao' => 'nullable|string|max:4000',
            'categoria_id' => 'nullable|integer|exists:categorias_servicos,id',
            'valor_custo' => 'nullable|numeric|min:0',
            'valor_comissao' => 'nullable|numeric|min:0',
            'tipo_comissao' => ['nullable', Rule::in(['valor', 'percentual'])],
            'prazos' => 'nullable|string|max:2000',

            'informacoes_fiscais' => 'nullable|array',
            'informacoes_fiscais.*' => 'nullable|string|max:255',

            'campos_customizados' => 'nullable|array',
            'campos_customizados.*.chave' => 'nullable|string|max:100',
            'campos_customizados.*.valor' => 'nullable|string|max:255',

            'imagens_tipo' => 'nullable|array',
            'imagens_tipo.*' => ['nullable', Rule::in(['upload', 'url'])],
            'imagens_url' => 'nullable|array',
            'imagens_url.*' => 'nullable|url|max:255',
            'imagens_ordem' => 'nullable|array',
            'imagens_ordem.*' => 'nullable|integer|min:0',
            'imagens_arquivo' => 'nullable|array',
            'imagens_arquivo.*' => 'nullable|file|mimes:jpg,jpeg,png,gif,webp,avif,heic,heif|max:5120',
            'imagens_remover' => 'nullable|array',
            'imagens_remover.*' => 'nullable|integer|exists:servico_imagens,id',
        ];
    }
}
