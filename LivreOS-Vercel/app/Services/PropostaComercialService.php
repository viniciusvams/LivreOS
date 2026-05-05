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

use App\Models\Configuracao;
use App\Models\PedidoVenda;
use App\Models\ProdutoVariacao;
use App\Models\PropostaComercial;
use App\Models\PropostaComercialItem;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class PropostaComercialService
{
    public function __construct(private PedidoVendaService $pedidoVendaService) {}

    public function salvar(array $dados, ?PropostaComercial $proposta = null): PropostaComercial
    {
        $novo = $proposta === null;
        $proposta = $proposta ?? new PropostaComercial;

        if ($novo) {
            $proposta->grupo_uuid = (string) Str::uuid();
            $proposta->versao = 1;
            $proposta->is_versao_atual = true;
            if (empty($proposta->status)) {
                $proposta->status = PropostaComercial::STATUS_RASCUNHO;
            }
        }

        if (array_key_exists('cliente_id', $dados)) {
            $proposta->cliente_id = (int) $dados['cliente_id'];
        }
        if (array_key_exists('vendedor_id', $dados)) {
            $v = $dados['vendedor_id'];
            $proposta->vendedor_id = ($v === null || $v === '') ? null : (int) $v;
        }
        if (array_key_exists('tabela_preco_id', $dados)) {
            $v = $dados['tabela_preco_id'];
            $proposta->tabela_preco_id = ($v === null || $v === '') ? null : (int) $v;
        }
        if (array_key_exists('titulo', $dados)) {
            $proposta->titulo = $dados['titulo'] !== null && $dados['titulo'] !== '' ? (string) $dados['titulo'] : null;
        }
        if (array_key_exists('descricao', $dados)) {
            $raw = $dados['descricao'] ?? null;
            $proposta->descricao = ($raw !== null && $raw !== '') ? (string) $raw : null;
        }
        if (array_key_exists('observacoes', $dados)) {
            $proposta->observacoes = $dados['observacoes'] ?: null;
        }
        if (array_key_exists('observacoes_internas', $dados)) {
            $proposta->observacoes_internas = $dados['observacoes_internas'] ?: null;
        }
        if (array_key_exists('data_emissao', $dados) && $dados['data_emissao']) {
            $proposta->data_emissao = $dados['data_emissao'];
        }
        if (array_key_exists('validade_dias', $dados)) {
            $v = $dados['validade_dias'];
            $proposta->validade_dias = ($v === null || $v === '') ? null : (int) $v;
        }

        $this->atualizarValidadeEm($proposta);

        $proposta->save();

        if (isset($dados['itens']) && is_array($dados['itens'])) {
            $this->sincronizarItens($proposta, $dados['itens']);
        }

        $proposta->load('itens');
        $proposta->recalcularTotais();
        $proposta->saveQuietly();

        if (function_exists('do_action')) {
            do_action('proposta_comercial.salva', $proposta, $novo);
        }

        return $proposta;
    }

    public function atualizarValidadeEm(PropostaComercial $proposta): void
    {
        $dias = $proposta->validade_dias;
        if ($dias === null || $dias < 1) {
            $raw = Configuracao::getValue('pedidos_venda.validade_orcamento_dias', '');
            $dias = (is_numeric($raw) && (int) $raw > 0) ? (int) $raw : null;
        }
        if ($dias !== null && $dias > 0 && $proposta->data_emissao) {
            $proposta->validade_em = $proposta->data_emissao->copy()->addDays($dias);
        } else {
            $proposta->validade_em = null;
        }
    }

    private function sincronizarItens(PropostaComercial $proposta, array $itens): void
    {
        $existingIds = [];
        foreach ($itens as $ordem => $row) {
            if (! is_array($row)) {
                continue;
            }
            if (empty($row['descricao']) && empty($row['produto_id']) && empty($row['servico_id'])) {
                continue;
            }

            $id = $row['id'] ?? null;
            $item = $id
                ? PropostaComercialItem::where('proposta_comercial_id', $proposta->id)->find($id)
                : null;
            $item = $item ?? new PropostaComercialItem(['proposta_comercial_id' => $proposta->id]);

            $item->tipo = $row['tipo'] ?? 'produto';
            $item->produto_id = ($item->tipo === 'produto' && ! empty($row['produto_id'])) ? (int) $row['produto_id'] : null;
            $item->produto_variacao_id = null;
            if ($item->tipo === 'produto' && $item->produto_id && ! empty($row['produto_variacao_id'])) {
                $vid = (int) $row['produto_variacao_id'];
                $ok = ProdutoVariacao::query()
                    ->whereKey($vid)
                    ->where('produto_id', $item->produto_id)
                    ->exists();
                if (! $ok) {
                    throw new \RuntimeException('Variação inválida para o produto na linha '.($ordem + 1).'.');
                }
                $item->produto_variacao_id = $vid;
            }
            $item->servico_id = ($item->tipo === 'servico' && ! empty($row['servico_id'])) ? (int) $row['servico_id'] : null;
            $item->descricao = (string) ($row['descricao'] ?? '');
            $item->quantidade = (float) ($row['quantidade'] ?? 1);
            $item->preco_unitario = parse_br_number($row['preco_unitario'] ?? 0);
            $item->desconto = parse_br_number($row['desconto'] ?? 0);
            $item->ordem = (int) $ordem;
            $item->save();

            $existingIds[] = $item->id;
        }

        PropostaComercialItem::where('proposta_comercial_id', $proposta->id)
            ->whereNotIn('id', $existingIds)
            ->delete();
    }

    public function novaVersao(PropostaComercial $origem): PropostaComercial
    {
        if (! $origem->podeNovaVersao()) {
            throw new \InvalidArgumentException('Não é possível criar nova versão desta proposta.');
        }

        return DB::transaction(function () use ($origem) {
            $origem->is_versao_atual = false;
            $origem->save();

            $nova = $origem->replicate([
                'pedido_venda_id', 'ordem_servico_id', 'created_at', 'updated_at',
            ]);
            $nova->versao = $origem->versao + 1;
            $nova->is_versao_atual = true;
            $nova->status = PropostaComercial::STATUS_RASCUNHO;
            $nova->pedido_venda_id = null;
            $nova->ordem_servico_id = null;

            if ($origem->numero_sequencial) {
                $nova->numero_sequencial = $origem->numero_sequencial;
            } else {
                $num = PropostaComercial::gerarNumeroSequencial();
                PropostaComercial::where('grupo_uuid', $origem->grupo_uuid)->update(['numero_sequencial' => $num]);
                $origem->refresh();
                $nova->numero_sequencial = $num;
            }

            $nova->save();

            foreach ($origem->itens as $it) {
                $ni = $it->replicate();
                $ni->proposta_comercial_id = $nova->id;
                $ni->save();
            }

            $nova->load('itens');
            $nova->recalcularTotais();
            $this->atualizarValidadeEm($nova);
            $nova->save();

            if (function_exists('do_action')) {
                do_action('proposta_comercial.nova_versao', $nova, $origem);
            }

            return $nova->fresh(['itens']);
        });
    }

    public function converterParaPedidoVenda(PropostaComercial $proposta): PedidoVenda
    {
        if (! $proposta->podeConverterParaPedido()) {
            throw new \InvalidArgumentException('Esta proposta não pode ser convertida em pedido de venda.');
        }

        $proposta->load('itens');

        return DB::transaction(function () use ($proposta) {
            $itensPayload = [];
            foreach ($proposta->itens as $i) {
                $itensPayload[] = [
                    'tipo' => $i->tipo,
                    'produto_id' => $i->produto_id,
                    'produto_variacao_id' => $i->produto_variacao_id,
                    'servico_id' => $i->servico_id,
                    'descricao' => $i->descricao,
                    'quantidade' => format_quantity($i->quantidade) ?: '1',
                    'preco_unitario' => number_format((float) $i->preco_unitario, 2, ',', '.'),
                    'desconto' => number_format((float) $i->desconto, 2, ',', '.'),
                    'gerar_os' => false,
                ];
            }

            $obsPedido = null;
            if (! empty($proposta->observacoes)) {
                $t = trim(strip_tags((string) $proposta->observacoes));
                $obsPedido = $t !== '' ? $t : null;
            }

            $dadosPedido = [
                'cliente_id' => $proposta->cliente_id,
                'vendedor_id' => $proposta->vendedor_id,
                'tabela_preco_id' => $proposta->tabela_preco_id,
                'observacoes' => $obsPedido,
                'observacoes_internas' => $proposta->observacoes_internas
                    ? ('Origem: Proposta comercial '.$proposta->referenciaGrupo()." (ID #{$proposta->id}).\n".$proposta->observacoes_internas)
                    : ('Origem: Proposta comercial '.$proposta->referenciaGrupo()." (ID #{$proposta->id})."),
                'data_pedido' => $proposta->data_emissao?->format('Y-m-d') ?? now()->format('Y-m-d'),
                'itens' => $itensPayload,
            ];

            $pedido = $this->pedidoVendaService->salvar($dadosPedido, null);

            $proposta->status = PropostaComercial::STATUS_CONVERTIDA;
            $proposta->pedido_venda_id = $pedido->id;
            $proposta->save();

            if (function_exists('do_action')) {
                do_action('proposta_comercial.convertida_pedido', $proposta, $pedido);
            }

            return $pedido;
        });
    }
}
