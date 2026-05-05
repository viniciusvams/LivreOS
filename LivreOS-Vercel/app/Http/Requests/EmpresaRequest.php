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

class EmpresaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function defaultRules(): array
    {
        return [
            'nome' => 'nullable|string|max:200',
            'razao_social' => 'nullable|string|max:200',
            'cnpj' => 'nullable|string|max:20',
            'inscricao_estadual' => 'nullable|string|max:50',
            'inscricao_municipal' => 'nullable|string|max:50',
            'email' => 'nullable|email|max:150',
            'telefone' => 'nullable|string|max:30',
            'celular' => 'nullable|string|max:30',
            'celular_whatsapp' => 'nullable|boolean',
            'site' => 'nullable|string|max:150',
            'cep' => 'nullable|string|max:15',
            'logradouro' => 'nullable|string|max:200',
            'numero' => 'nullable|string|max:30',
            'complemento' => 'nullable|string|max:100',
            'bairro' => 'nullable|string|max:100',
            'cidade' => 'nullable|string|max:120',
            'estado' => 'nullable|string|max:60',
            'pais' => 'nullable|string|max:60',
            'observacoes' => 'nullable|string|max:4000',
            'logo' => 'nullable|image|mimes:jpg,jpeg,png,webp,avif,heic,heif|max:2048',
        ];
    }
}
