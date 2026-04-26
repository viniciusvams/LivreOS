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

use App\Models\OrdemServico;
use App\Models\Produto;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class OrdemServicoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function defaultRules(): array
    {
        if ($this->input('save_scope') === 'equipe') {
            return [
                'save_scope' => 'nullable|string|in:equipe,full',
                'tecnicos' => 'nullable|array',
                'tecnicos.*.tecnico_id' => 'nullable|integer|exists:users,id',
                'tecnicos.*.status' => 'nullable|string|max:30',
                'tecnicos.*.inicio' => 'nullable|date',
                'tecnicos.*.fim' => 'nullable|date',
                'tecnicos.*.pausas_minutos' => 'nullable|integer|min:0',
                'tecnicos.*.horas_trabalhadas' => 'nullable|numeric|min:0',

                'apontamentos' => 'nullable|array',
                'apontamentos.*.tecnico_id' => 'nullable|integer|exists:users,id',
                'apontamentos.*.inicio' => 'required_with:apontamentos|date',
                'apontamentos.*.fim' => 'nullable|date',
                'apontamentos.*.pausas_minutos' => 'nullable|integer|min:0',
                'apontamentos.*.status' => 'nullable|string|max:30',
                'apontamentos.*.observacoes' => 'nullable|string|max:1000',
            ];
        }

        if ($this->input('save_scope') === 'sla') {
            return [
                'save_scope' => 'nullable|string|in:sla,full',
                'prevista_inicio' => 'nullable|date',
                'prevista_conclusao' => 'nullable|date',
                'real_inicio' => 'nullable|date',
                'real_conclusao' => 'nullable|date',
                'sla_valor' => 'nullable|integer|min:0',
                'sla_unidade' => 'nullable|string|max:10',
            ];
        }

        $rules = [
            'tenant_id' => 'nullable|integer|exists:tenants,id',
            'cliente_id' => 'required|integer|exists:clientes,id',
            'cliente_unidade_id' => 'nullable|integer|exists:clientes,id',
            'contato_id' => 'nullable|integer|exists:contatos,id',
            'endereco_id' => 'nullable|integer|exists:enderecos,id',

            'tipo_os' => 'nullable|string|max:50',
            'origem_os' => 'nullable|string|max:50',
            'prioridade' => 'nullable|string|max:20',
            'status' => 'nullable|string|max:50|not_in:Encerrado,Encerrada',
            'status_outro' => 'nullable|string|max:50|not_in:Encerrado,Encerrada',

            'data_abertura' => 'nullable|date',
            'prevista_inicio' => 'nullable|date',
            'prevista_conclusao' => 'nullable|date',
            'real_inicio' => 'nullable|date',
            'real_conclusao' => 'nullable|date',

            'sla_valor' => 'nullable|integer|min:0',
            'sla_unidade' => 'nullable|string|max:10',

            'relato_cliente' => 'nullable|string|max:5000',
            'diagnostico_tecnico' => 'nullable|string|max:5000',
            'servicos_realizados' => 'nullable|string|max:5000',
            // TEXT no MySQL (~64KB); importação M-OS funde anotacoes_os aqui (pode ultrapassar 5000)
            'observacoes_internas' => 'nullable|string|max:62000',
            'observacoes_cliente' => 'nullable|string|max:62000',
            'garantia_dias' => 'nullable|integer|min:0',

            'equipamento_id' => 'nullable|integer',
            'equipamento_nome' => 'nullable|string|max:150',
            'equipamento_marca' => 'nullable|string|max:100',
            'equipamento_modelo' => 'nullable|string|max:100',
            'equipamento_numero_serie' => 'nullable|string|max:100',
            'equipamento_numero_patrimonio' => 'nullable|string|max:100',
            'equipamento_acessorios' => 'nullable|string|max:2000',
            'equipamento_unlock_type' => 'nullable|string|in:pattern,pin,none',
            'equipamento_unlock_code' => 'nullable|string',
            'quilometragem_entrada' => 'nullable|numeric|min:0',
            'quilometragem_saida' => 'nullable|numeric|min:0',
            'horimetro_entrada' => 'nullable|numeric|min:0',
            'horimetro_saida' => 'nullable|numeric|min:0',

            'garantia_tipo' => 'nullable|string|max:20',
            'termo_garantia_id' => 'nullable|integer|exists:termos_garantia,id',
            'contrato_numero' => 'nullable|string|max:100',
            'contrato_tipo' => 'nullable|string|max:50',
            'recorrente' => 'nullable|boolean',
            'recorrencia_periodicidade' => 'nullable|string|max:50',
            'coberto_por_contrato' => 'nullable|boolean',

            'aprovado_nome' => 'nullable|string|max:150',
            'aprovado_em' => 'nullable|date',
            'aprovado_canal' => 'nullable|string|max:50',
            'aprovado_assinatura' => 'nullable|string|max:255',
            'aprovado_cliente' => 'nullable|boolean',

            'enviar_email' => 'nullable|boolean',

            'desconto_global' => 'nullable|numeric|min:0',
            'acrescimos_global' => 'nullable|numeric|min:0',
            'impostos_global' => 'nullable|numeric|min:0',

            'produtos' => 'nullable|array',
            'produtos.*.id' => 'nullable|integer',
            'produtos.*.produto_id' => 'nullable|integer|exists:produtos,id',
            'produtos.*.produto_variacao_id' => 'nullable|integer|exists:produto_variacoes,id',
            'produtos.*.descricao' => 'nullable|string|max:255',
            'produtos.*.quantidade' => 'nullable|numeric|min:0',
            'produtos.*.valor_unitario' => 'nullable|numeric|min:0',
            'produtos.*.desconto_valor' => 'nullable|numeric|min:0',
            'produtos.*.desconto_percentual' => 'nullable|numeric|min:0|max:100',
            'produtos.*.tributos' => 'nullable|array',
            'produtos.*.tributos.ibs' => 'nullable|numeric|min:0',
            'produtos.*.tributos.cbs' => 'nullable|numeric|min:0',
            'produtos.*.tributos.icms' => 'nullable|numeric|min:0',
            'produtos.*.tributos.pis' => 'nullable|numeric|min:0',
            'produtos.*.tributos.cofins' => 'nullable|numeric|min:0',
            'produtos.*.tributos.outros' => 'nullable|numeric|min:0',

            'servicos' => 'nullable|array',
            'servicos.*.id' => 'nullable|integer',
            'servicos.*.servico_id' => 'nullable|integer|exists:servicos,id',
            'servicos.*.descricao' => 'nullable|string|max:255',
            'servicos.*.cobranca_tipo' => 'nullable|string|in:unidade,horas',
            'servicos.*.quantidade' => 'nullable|numeric|min:0',
            'servicos.*.quantidade_horas' => 'nullable|numeric|min:0',
            'servicos.*.tecnico_id' => 'nullable|integer|exists:users,id',
            'servicos.*.valor_unitario' => 'nullable|numeric|min:0',
            'servicos.*.desconto_valor' => 'nullable|numeric|min:0',
            'servicos.*.desconto_percentual' => 'nullable|numeric|min:0|max:100',

            'tecnicos' => 'nullable|array',
            'tecnicos.*.tecnico_id' => 'nullable|integer|exists:users,id',
            'tecnicos.*.status' => 'nullable|string|max:30',
            'tecnicos.*.inicio' => 'nullable|date',
            'tecnicos.*.fim' => 'nullable|date',
            'tecnicos.*.pausas_minutos' => 'nullable|integer|min:0',
            'tecnicos.*.horas_trabalhadas' => 'nullable|numeric|min:0',

            'apontamentos' => 'nullable|array',
            'apontamentos.*.tecnico_id' => 'nullable|integer|exists:users,id',
            'apontamentos.*.inicio' => 'required_with:apontamentos|date',
            'apontamentos.*.fim' => 'nullable|date',
            'apontamentos.*.pausas_minutos' => 'nullable|integer|min:0',
            'apontamentos.*.status' => 'nullable|string|max:30',
            'apontamentos.*.observacoes' => 'nullable|string|max:1000',

            'anexos_arquivo' => 'nullable|array',
            'anexos_arquivo.*' => 'nullable|file|max:10240',
            'anexos_tipo' => 'nullable|array',
            'anexos_tipo.*' => 'nullable|string|max:50',
            'anexos_tags' => 'nullable|array',
            'anexos_tags.*' => 'nullable|string|max:255',
            'anexos_descricao' => 'nullable|array',
            'anexos_descricao.*' => 'nullable|string|max:2000',
            'anexos_metadata' => 'nullable|array',
            'anexos_metadata.*' => 'nullable|string|max:2000',
            'anexos_remover' => 'nullable|array',
            'anexos_remover.*' => 'nullable|integer',
        ];

        // OS já encerrada envia status fixo (hidden); not_in:Encerrado,Encerrada quebraria todo save.
        $ordemRota = $this->route('ordemServico');
        if ($ordemRota instanceof OrdemServico && in_array($ordemRota->status, ['Encerrado', 'Encerrada'], true)) {
            $rules['status'] = ['required', 'string', 'max:50', Rule::in([(string) $ordemRota->status])];
        }

        return $rules;
    }

    public function messages(): array
    {
        return [
            'status.not_in' => 'O status "Encerrada" é exclusivo do sistema. Use o botão Encerrar OS para encerrar a ordem de serviço.',
            'status_outro.not_in' => 'O status "Encerrada" é exclusivo do sistema. Use o botão Encerrar OS para encerrar a ordem de serviço.',
        ];
    }

    public function withValidator(Validator $validator): void
    {
        if (in_array($this->input('save_scope'), ['equipe', 'sla'], true)) {
            return;
        }

        $validator->after(function (Validator $validator) {
            $produtos = $this->input('produtos', []);
            foreach ($produtos as $i => $item) {
                if (empty($item['produto_id']) || !empty($item['produto_variacao_id'])) {
                    continue;
                }
                $produto = Produto::with('variacoes')->find($item['produto_id']);
                if (!$produto || $produto->formato !== 'variacao' || $produto->variacoes->isEmpty()) {
                    continue;
                }
                $validator->errors()->add(
                    "produtos.{$i}.produto_variacao_id",
                    'Selecione a variação do produto.'
                );
            }
        });
    }
}
