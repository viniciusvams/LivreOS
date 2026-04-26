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

class ClienteRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $br = $this->input('limite_credito');
        if (is_string($br) && $br !== '') {
            $clean = preg_replace('/\s/', '', $br);
            $clean = str_replace('.', '', $clean);
            $clean = str_replace(',', '.', $clean);
            $this->merge(['limite_credito' => $clean === '' ? null : $clean]);
        }
        // Selects "Selecione..." enviam string vazia; normalizar para null para passar em nullable|integer
        $merge = [];
        if ($this->input('vendedor_id') === '') {
            $merge['vendedor_id'] = null;
        }
        if ($this->input('grupo_economico_id') === '') {
            $merge['grupo_economico_id'] = null;
        }
        if ($this->input('matriz_id') === '') {
            $merge['matriz_id'] = null;
        }
        if (!empty($merge)) {
            $this->merge($merge);
        }
    }

    protected function defaultRules(): array
    {
        $clienteId = $this->route('cliente')?->id;
        $tipoPessoa = $this->input('tipo_pessoa');

        $rules = [
            // Dados Básicos
            'tipo_pessoa' => ['required', Rule::in(['F', 'J', 'E'])],
            'pais_origem' => 'required|string|max:100',
            'nome' => 'required|string|max:100',
            'razao_social' => 'nullable|string|max:255',
            'natureza_juridica' => 'nullable|string|max:255',
            'porte_empresa' => 'nullable|string|max:100',
            'capital_social' => 'nullable|string|max:50',
            'data_inicio_atividade' => 'nullable|date',
            'ramo_atividade' => 'nullable|string|max:255',
            'produtor_rural' => 'boolean',
            'condominio' => 'boolean',
            'vendedor_id' => 'nullable|integer|exists:users,id',
            'limite_credito' => 'nullable|numeric|min:0',
            'bloqueado_vendas' => 'boolean',
            'grupo_economico_id' => 'nullable|integer|exists:grupos_economicos,id',
            'tipo_unidade' => ['nullable', Rule::in(['matriz', 'filial'])],
            'matriz_id' => [
                'nullable',
                'integer',
                'exists:clientes,id',
                Rule::requiredIf($this->input('tipo_unidade') === 'filial'),
            ],
            
            // Endereços
            'enderecos' => 'nullable|array',
            'enderecos.*.cep' => 'nullable|string|max:10',
            'enderecos.*.logradouro' => 'nullable|string|max:255',
            'enderecos.*.numero' => 'nullable|string|max:20',
            'enderecos.*.complemento' => 'nullable|string|max:100',
            'enderecos.*.bairro' => 'nullable|string|max:100',
            'enderecos.*.cidade' => 'nullable|string|max:100',
            'enderecos.*.estado' => 'nullable|string|size:2',
            'enderecos.*.pais' => 'nullable|string|max:100',
            'enderecos.*.inscricao_produtor_rural' => 'nullable|string|max:20',
            
            // Contatos
            'contatos' => 'nullable|array',
            'contatos.*.nome' => 'nullable|string|max:100',
            'contatos.*.telefone' => 'nullable|string|max:20',
            'contatos.*.telefone_whatsapp' => 'nullable|boolean',
            'contatos.*.telefone2' => 'nullable|string|max:20',
            'contatos.*.email' => 'nullable|email|max:255',
            'contatos.*.email2' => 'nullable|email|max:255',
            'contatos.*.cargo' => 'nullable|string|max:100',
            
            // Faturamento
            'dias_antecedencia_faturamento' => 'nullable|integer|min:1|max:365',
            'dia_faturamento' => 'nullable|integer|min:1|max:31',
            'emissao_nfse' => ['nullable', Rule::in(['config_empresa', 'faturamento', 'quitacao'])],
            
            // Observações
            'observacoes' => 'nullable|string|max:2000',

            // Documentos existentes (edição de tags/descrição)
            'documentos_existentes' => 'sometimes|array',
            'documentos_existentes.*.categoria' => 'nullable|string|max:255',
            'documentos_existentes.*.tags' => 'nullable|string|max:255',
            'documentos_existentes.*.descricao' => 'nullable|string|max:2000',

            // Sócios (PJ)
            'socios' => 'nullable|array',
            'socios.*.nome_completo' => 'nullable|string|max:255',
            'socios.*.cpf' => ['nullable', 'string', 'max:20', function ($attribute, $value, $fail) {
                if (!$value) {
                    return;
                }
                if (!$this->validarCPFNumerico($value)) {
                    $fail('O CPF do sócio é inválido.');
                }
            }],
            'socios.*.rg_orgao_emissor' => 'nullable|string|max:100',
            'socios.*.nacionalidade' => 'nullable|string|max:100',
            'socios.*.estado_civil' => 'nullable|string|max:50',
            'socios.*.profissao' => 'nullable|string|max:100',
            'socios.*.cep' => 'nullable|string|max:10',
            'socios.*.logradouro' => 'nullable|string|max:255',
            'socios.*.numero' => 'nullable|string|max:20',
            'socios.*.bairro' => 'nullable|string|max:100',
            'socios.*.cidade' => 'nullable|string|max:100',
            'socios.*.uf' => 'nullable|string|size:2',
            'socios.*.socio_administrador' => 'nullable|boolean',
            'socios.*.tipo_assinatura' => ['nullable', Rule::in(['isoladamente', 'conjunto'])],
            'socios.*.percentual_participacao' => 'nullable|numeric|min:0|max:100',
            'socios.*.score_credito' => 'nullable|integer',
            'socios.*.possui_restricoes' => 'nullable|boolean',
            'socios.*.patrimonio_estimado' => 'nullable|string|max:255',
        ];

        // Validações específicas por tipo de pessoa
        if ($tipoPessoa === 'F') {
            // Pessoa Física Brasil
            $rules['cpf'] = [
                'nullable',
                'string',
                'size:14',
                function ($attribute, $value, $fail) use ($clienteId) {
                    if (!$value) {
                        return;
                    }
                    if (!$this->validarCPF($value)) {
                        $fail('O CPF informado é inválido.');
                    }
                    // Verificar duplicidade
                    $exists = \App\Models\Cliente::where('cpf', $value)
                        ->when($clienteId, fn($q) => $q->where('id', '!=', $clienteId))
                        ->exists();
                    if ($exists) {
                        $fail('Este CPF já está cadastrado.');
                    }
                },
            ];
            $rules['rg'] = 'nullable|string|max:20';
            $rules['data_nascimento'] = 'nullable|date';
            $rules['sexo'] = 'nullable|in:M,F';
        } elseif ($tipoPessoa === 'J') {
            // Pessoa Jurídica Brasil
            $rules['cnpj'] = [
                'nullable',
                'string',
                'max:18',
                function ($attribute, $value, $fail) use ($clienteId) {
                    if (!$value) {
                        return;
                    }
                    if (!$this->validarCNPJ($value)) {
                        $fail('O CNPJ informado é inválido.');
                    }
                    // Verificar duplicidade
                    $exists = \App\Models\Cliente::where('cnpj', $value)
                        ->when($clienteId, fn($q) => $q->where('id', '!=', $clienteId))
                        ->exists();
                    if ($exists) {
                        $fail('Este CNPJ já está cadastrado.');
                    }
                },
            ];
            $rules['razao_social'] = 'nullable|string|max:255';
            $rules['inscricao_estadual'] = 'nullable|string|max:20';
            $rules['uf_ie'] = 'nullable|string|size:2';
            $rules['inscricao_municipal'] = 'nullable|string|max:20';
            $rules['optante_simples_nacional'] = 'boolean';
            $rules['ignorar_im_nfse'] = 'boolean';
        } elseif ($tipoPessoa === 'E') {
            // Estrangeira
            $rules['documento_estrangeiro'] = 'nullable|string|max:30';
        }

        return $rules;
    }

    private function validarCPF($cpf): bool
    {
        $cpf = preg_replace('/[^0-9]/', '', $cpf);
        
        if (strlen($cpf) != 11 || preg_match('/(\d)\1{10}/', $cpf)) {
            return false;
        }

        for ($t = 9; $t < 11; $t++) {
            for ($d = 0, $c = 0; $c < $t; $c++) {
                $d += $cpf[$c] * (($t + 1) - $c);
            }
            $d = ((10 * $d) % 11) % 10;
            if ($cpf[$c] != $d) {
                return false;
            }
        }

        return true;
    }

    private function validarCPFNumerico($cpf): bool
    {
        $cpf = preg_replace('/[^0-9]/', '', (string) $cpf);
        if (strlen($cpf) !== 11) {
            return false;
        }
        if (preg_match('/(\d)\1{10}/', $cpf)) {
            return false;
        }

        for ($t = 9; $t < 11; $t++) {
            for ($d = 0, $c = 0; $c < $t; $c++) {
                $d += $cpf[$c] * (($t + 1) - $c);
            }
            $d = ((10 * $d) % 11) % 10;
            if ($cpf[$c] != $d) {
                return false;
            }
        }

        return true;
    }

    private function validarCNPJ($cnpj): bool
    {
        // Validação completa de CNPJ numérico e alfanumérico
        $cnpj = trim($cnpj);
        $clean = strtoupper(preg_replace('/[^0-9A-Za-z]/', '', $cnpj));

        if (strlen($clean) !== 14) {
            return false;
        }

        if (preg_match('/^([A-Z0-9])\1{13}$/', $clean)) {
            return false;
        }

        $base = substr($clean, 0, 12);
        $dvInformado = substr($clean, 12, 2);

        if (!ctype_digit($dvInformado)) {
            return false;
        }

        $dv1 = $this->calcularDVCNPJAlfanumerico($base);
        $dv2 = $this->calcularDVCNPJAlfanumerico($base . $dv1);

        return $dvInformado === ($dv1 . $dv2);
    }

    private function calcularDVCNPJAlfanumerico(string $base): string
    {
        $weights = [5, 4, 3, 2, 9, 8, 7, 6, 5, 4, 3, 2];
        if (strlen($base) === 13) {
            $weights = [6, 5, 4, 3, 2, 9, 8, 7, 6, 5, 4, 3, 2];
        }

        $sum = 0;
        $chars = str_split($base);
        foreach ($chars as $i => $char) {
            $sum += $this->mapCNPJCharToValue($char) * $weights[$i];
        }

        $resto = $sum % 11;
        $dv = ($resto < 2) ? 0 : (11 - $resto);
        return (string) $dv;
    }

    private function mapCNPJCharToValue(string $char): int
    {
        if (ctype_digit($char)) {
            return (int) $char;
        }

        $ord = ord($char);
        if ($ord >= 65 && $ord <= 90) {
            // Mapeamento alfanumérico conforme implementação do repositório (ASCII - 48)
            return $ord - 48; // A=17, B=18, ..., Z=42
        }

        return 0;
    }

    public function messages(): array
    {
        return [
            'tipo_pessoa.required' => 'O tipo de pessoa é obrigatório.',
            'nome.required' => 'O nome do cliente é obrigatório.',
            'nome.max' => 'O nome do cliente não pode ter mais de 100 caracteres.',
        ];
    }
}
