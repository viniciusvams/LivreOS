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

use Illuminate\Foundation\Http\FormRequest as BaseFormRequest;

/**
 * FormRequest base do ERP com suporte a hooks de plugins.
 * Plugins podem modificar as regras via: add_filter('request.validation.rules', ...)
 */
abstract class FormRequest extends BaseFormRequest
{
    public function rules(): array
    {
        $rules = $this->defaultRules();

        if (function_exists('apply_filters')) {
            $rules = apply_filters(
                'request.validation.rules',
                $rules,
                $this->route()?->getName(),
                $this
            );
        }

        return $rules;
    }

    /**
     * Define as regras de validação padrão. Subclasses devem implementar este método.
     * (Nome diferente de validationRules para evitar conflito com Illuminate\FormRequest)
     */
    abstract protected function defaultRules(): array;

    /**
     * Converte campos com formato BR (1.234,56) para decimal padrão (1234.56).
     * Aceita nomes simples ('preco') ou com wildcard de array ('itens.*.preco').
     */
    protected function convertBrNumbers(array $fields): void
    {
        $merge = [];

        foreach ($fields as $field) {
            if (!str_contains($field, '.*')) {
                $value = $this->input($field);
                if (is_string($value) && $value !== '') {
                    $merge[$field] = $this->parseSingleBrNumber($value);
                }
                continue;
            }

            // Campo aninhado com wildcard: ex. "tabelas_precos.*.preco"
            [$parent, $rest] = explode('.*.', $field, 2);
            $items = $this->input($parent);
            if (!is_array($items)) {
                continue;
            }
            foreach ($items as $idx => $item) {
                if (!is_array($item)) {
                    continue;
                }
                $parts = explode('.', $rest);
                $val = $item;
                foreach ($parts as $part) {
                    $val = is_array($val) ? ($val[$part] ?? null) : null;
                }
                if (is_string($val) && $val !== '') {
                    $items[$idx] = $this->setNestedValue($item, $parts, $this->parseSingleBrNumber($val));
                }
            }
            $merge[$parent] = $items;
        }

        if ($merge) {
            $this->merge($merge);
        }
    }

    private function parseSingleBrNumber(string $value): ?string
    {
        $clean = preg_replace('/\s/', '', $value);
        // Remove separadores de milhar (pontos antes de grupo de 3 dígitos)
        // e substitui vírgula decimal por ponto
        $clean = str_replace('.', '', $clean);
        $clean = str_replace(',', '.', $clean);
        return $clean === '' ? null : $clean;
    }

    private function setNestedValue(array $array, array $keys, mixed $value): array
    {
        $key = array_shift($keys);
        if (empty($keys)) {
            $array[$key] = $value;
        } else {
            $array[$key] = $this->setNestedValue($array[$key] ?? [], $keys, $value);
        }
        return $array;
    }
}
