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
use App\Models\ContaPagar;
use App\Models\ContaReceber;
use App\Models\PedidoVenda;
use App\Models\PedidoVendaItem;
use App\Models\PedidoVendaLog;
use App\Models\PlanoConta;
use App\Models\OrdemServico;
use App\Models\ProdutoVariacao;
use App\Services\PedidoVendaEstoqueHelper;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class PedidoVendaService
{
    // -----------------------------------------------------------------------
    // Criação / edição
    // -----------------------------------------------------------------------

    /**
     * Cria ou actualiza um pedido a partir dos dados do request.
     * Salva os itens, recalcula totais e registra log.
     */
    public function salvar(array $dados, ?PedidoVenda $pedido = null): PedidoVenda
    {
        $novo = $pedido === null;
        $pedido = $pedido ?? new PedidoVenda();

        $clienteId = (int) ($dados['cliente_id'] ?? $pedido->cliente_id ?? 0);
        $validacaoComercial = app(PedidoVendaComercialValidationService::class);
        $validacaoComercial->garantirClienteNaoBloqueadoParaVendas($clienteId);
        $validacaoComercial->garantirDescontosDentroDoLimiteSistema($dados, $novo ? null : $pedido);

        if (array_key_exists('validade_orcamento_dias', $dados)) {
            $v = $dados['validade_orcamento_dias'];
            $pedido->validade_orcamento_dias = ($v === null || $v === '') ? null : (int) $v;
        }

        $campos = [
            'cliente_id', 'cliente_unidade_id', 'contato_id', 'endereco_id',
            'vendedor_id', 'tabela_preco_id', 'canal_venda', 'observacoes',
            'observacoes_internas', 'data_pedido', 'data_prevista_entrega',
            'endereco_entrega_id', 'desconto_global', 'acrescimos_global',
        ];
        foreach ($campos as $campo) {
            if (! array_key_exists($campo, $dados)) {
                continue;
            }
            $v = $dados[$campo];
            // Não usar ?: — preserva HTML/texto válido e só normaliza vazio explícito a null.
            $pedido->$campo = ($v === null || $v === '') ? null : $v;
        }

        if ($novo && empty($pedido->status)) {
            $pedido->status = 'rascunho';
        }

        $pedido->save();

        // Itens
        if (isset($dados['itens']) && is_array($dados['itens'])) {
            $this->sincronizarItens($pedido, $dados['itens']);
        }

        // Recalcular totais
        $pedido->load('itens');
        $pedido->recalcularTotais();
        $pedido->saveQuietly();

        PedidoVendaLog::create([
            'pedido_venda_id' => $pedido->id,
            'acao'            => $novo ? 'criado' : 'editado',
            'descricao'       => $novo ? 'Pedido criado.' : 'Pedido editado.',
        ]);

        return $pedido;
    }

    private function sincronizarItens(PedidoVenda $pedido, array $itens): void
    {
        $existingIds = [];
        foreach ($itens as $ordem => $dadosItem) {
            if (empty($dadosItem['descricao']) && empty($dadosItem['produto_id']) && empty($dadosItem['servico_id'])) {
                continue;
            }
            $id = $dadosItem['id'] ?? null;
            $item = $id ? PedidoVendaItem::where('pedido_venda_id', $pedido->id)->find($id) : null;
            $item = $item ?? new PedidoVendaItem(['pedido_venda_id' => $pedido->id]);

            $item->tipo           = $dadosItem['tipo'] ?? 'produto';
            $item->produto_id     = ($item->tipo === 'produto' && !empty($dadosItem['produto_id'])) ? $dadosItem['produto_id'] : null;
            $item->produto_variacao_id = null;
            if ($item->tipo === 'produto' && $item->produto_id && ! empty($dadosItem['produto_variacao_id'])) {
                $vid = (int) $dadosItem['produto_variacao_id'];
                $ok = ProdutoVariacao::query()
                    ->whereKey($vid)
                    ->where('produto_id', $item->produto_id)
                    ->exists();
                if (! $ok) {
                    throw new \RuntimeException('Variação inválida para o produto na linha '.($ordem + 1).'.');
                }
                $item->produto_variacao_id = $vid;
            }
            $item->servico_id     = ($item->tipo === 'servico' && !empty($dadosItem['servico_id'])) ? $dadosItem['servico_id'] : null;
            $item->descricao      = $dadosItem['descricao'] ?? '';
            $item->quantidade     = (float) ($dadosItem['quantidade'] ?? 1);
            $item->preco_unitario = parse_br_number($dadosItem['preco_unitario'] ?? 0);
            $item->desconto       = parse_br_number($dadosItem['desconto'] ?? 0);
            $item->desconto_inicial = $item->desconto;
            $item->serial         = $dadosItem['serial'] ?? null;
            $item->gerar_os       = !empty($dadosItem['gerar_os']);
            $item->observacao     = $dadosItem['observacao'] ?? null;
            $item->ordem          = (int) $ordem;
            $item->save();

            $existingIds[] = $item->id;
        }

        // Remover itens não enviados (apenas se não gerou OS)
        PedidoVendaItem::where('pedido_venda_id', $pedido->id)
            ->whereNull('os_gerada_id')
            ->when(count($existingIds) > 0, fn ($q) => $q->whereNotIn('id', $existingIds))
            ->delete();
    }

    // -----------------------------------------------------------------------
    // Transições de status
    // -----------------------------------------------------------------------

    public function confirmar(PedidoVenda $pedido): void
    {
        if (!$pedido->podeConfirmar()) {
            throw new \RuntimeException('Pedido não pode ser confirmado no status atual: ' . $pedido->status);
        }

        $statusAnt = $pedido->status;

        if (Configuracao::getValue('pedidos_venda.estoque_modo') === 'reservar_confirmar') {
            $this->reservarEstoque($pedido);
        }

        $pedido->status          = 'confirmado';
        $pedido->data_confirmacao = now();
        $pedido->save();

        PedidoVendaLog::create([
            'pedido_venda_id' => $pedido->id,
            'status_anterior' => $statusAnt,
            'status_novo'     => 'confirmado',
            'acao'            => 'confirmado',
            'descricao'       => 'Pedido confirmado.',
        ]);
    }

    public function entregar(PedidoVenda $pedido): void
    {
        if (!$pedido->podeEntregar()) {
            throw new \RuntimeException('Pedido não pode ser marcado como entregue no status atual: ' . $pedido->status);
        }

        $pedido->status       = 'entregue';
        $pedido->data_entrega = now();
        $pedido->save();

        PedidoVendaLog::create([
            'pedido_venda_id' => $pedido->id,
            'status_anterior' => 'faturado',
            'status_novo'     => 'entregue',
            'acao'            => 'entregue',
            'descricao'       => 'Pedido marcado como entregue.',
        ]);
    }

    /**
     * @param  array<int, array{item_id: int, quantidade_cancelar: float}>  $itensPayload
     */
    public function cancelar(PedidoVenda $pedido, string $motivo, string $tipo = 'total', array $itensPayload = []): void
    {
        if (! $pedido->podeCancelar()) {
            throw new \RuntimeException('Pedido não pode ser cancelado no status atual: ' . $pedido->status);
        }
        $motivo = trim($motivo);
        if ($motivo === '') {
            throw new \RuntimeException('Informe o motivo do cancelamento.');
        }

        $tipo = $tipo === 'parcial' ? 'parcial' : 'total';

        if ($pedido->podeCancelarPosFaturamento()) {
            $this->garantirSemContaReceberPendenteVinculadaAoPedido($pedido);
            if ($tipo === 'parcial') {
                $this->cancelarPosFaturamentoParcial($pedido, $motivo, $itensPayload);
            } else {
                $this->cancelarPosFaturamentoTotal($pedido, $motivo);
            }

            return;
        }

        if ($tipo === 'parcial' && $itensPayload !== []) {
            $this->cancelarPreFaturamentoParcial($pedido, $motivo, $itensPayload);

            return;
        }

        $this->cancelarPreFaturamentoTotal($pedido, $motivo);
    }

    /**
     * Com recebimento já quitado (pago), o cancelamento não estorna o CR: gera conta a pagar de estorno.
     * Bloqueia enquanto existir título em aberto/parcial/vencido (boleto, TED, fiado não liquidado).
     */
    private function garantirSemContaReceberPendenteVinculadaAoPedido(PedidoVenda $pedido): void
    {
        $pendentes = $pedido->contasReceber()
            ->whereIn('status', ['aberto', 'parcial', 'vencido'])
            ->orderBy('id')
            ->get();

        if ($pendentes->isEmpty()) {
            return;
        }

        $detalhes = $pendentes->map(function ($cr) {
            $v = number_format((float) $cr->valor, 2, ',', '.');

            return '#' . $cr->id . ' — ' . ucfirst((string) $cr->status) . ' (R$ ' . $v . ')';
        })->implode('; ');

        throw new \RuntimeException(
            'Não é possível cancelar o pedido enquanto existirem contas a receber não quitadas vinculadas a ele: ' . $detalhes . '. '
            . 'Quite, cancele ou regularize esses títulos em Financeiro → Contas a receber (ex.: boleto ou TED em aberto). '
            . 'Quando o cliente já tiver pago, o cancelamento aqui gera um lançamento em Contas a pagar (estorno) sem desfazer o recebimento registrado.'
        );
    }

    private function cancelarPreFaturamentoTotal(PedidoVenda $pedido, string $motivo): void
    {
        $statusAnt = $pedido->status;

        if ($pedido->estoque_reservado && ! $pedido->estoque_baixado) {
            $this->devolverReservaEstoque($pedido);
        }

        $pedido->status = 'cancelado';
        $pedido->data_cancelamento = now();
        $pedido->motivo_cancelamento = $motivo;
        $pedido->save();

        PedidoVendaLog::create([
            'pedido_venda_id' => $pedido->id,
            'status_anterior' => $statusAnt,
            'status_novo' => 'cancelado',
            'acao' => 'cancelado',
            'descricao' => 'Pedido cancelado.' . ($motivo ? ' Motivo: ' . $motivo : ''),
        ]);
    }

    /**
     * @param  array<int, array{item_id: int, quantidade_cancelar: float}>  $itensPayload
     */
    private function cancelarPreFaturamentoParcial(PedidoVenda $pedido, string $motivo, array $itensPayload): void
    {
        $pedido->load('itens');
        $snapProd = (float) $pedido->total_produtos;
        $snapServ = (float) $pedido->total_servicos;
        $snapGlobal = (float) $pedido->desconto_global;
        $baseOld = $snapProd + $snapServ;
        $porId = $pedido->itens->keyBy('id');
        $deltas = [];
        foreach ($itensPayload as $row) {
            $id = (int) ($row['item_id'] ?? 0);
            $q = (float) ($row['quantidade_cancelar'] ?? 0);
            if ($id <= 0 || $q <= 0) {
                continue;
            }
            $deltas[$id] = ($deltas[$id] ?? 0) + $q;
        }
        if ($deltas === []) {
            throw new \InvalidArgumentException('Informe ao menos um item com quantidade a cancelar.');
        }

        foreach ($deltas as $itemId => $delta) {
            $item = $porId->get($itemId);
            if (! $item) {
                throw new \InvalidArgumentException('Item #' . $itemId . ' não pertence a este pedido.');
            }
            if (! empty($item->os_gerada_id)) {
                throw new \RuntimeException('Não é possível cancelar o item "' . $item->descricao . '" pois possui OS vinculada.');
            }
            $ja = (float) ($item->quantidade_cancelada ?? 0);
            $qtd = (float) $item->quantidade;
            $disp = max(0, $qtd - $ja);
            if ($delta > $disp + 0.0001) {
                throw new \InvalidArgumentException('Quantidade a cancelar inválida no item "' . $item->descricao . '".');
            }
            $item->quantidade_cancelada = round($ja + $delta, 4);
            $item->save();
        }

        $pedido->refresh();
        $pedido->load('itens');
        $baseNew = 0.0;
        foreach ($pedido->itens as $it) {
            $q = (float) $it->quantidade;
            $qc = (float) ($it->quantidade_cancelada ?? 0);
            $qEff = max(0, $q - $qc);
            $baseNew += (float) $it->preco_unitario * $qEff;
        }
        $baseNew = round($baseNew, 2);
        $pedido->desconto_global = $baseOld > 0 ? round($snapGlobal * ($baseNew / $baseOld), 2) : 0;
        $pedido->recalcularTotais();
        $pedido->saveQuietly();

        $tudoZerado = true;
        foreach ($pedido->itens as $it) {
            if (PedidoVendaEstoqueHelper::quantidadeLinhaAindaVendida($it) > 0.00001) {
                $tudoZerado = false;
                break;
            }
        }

        if ($tudoZerado) {
            $statusAnt = $pedido->status;
            $pedido->status = 'cancelado';
            $pedido->data_cancelamento = now();
            $pedido->motivo_cancelamento = $motivo;
            $pedido->save();
            PedidoVendaLog::create([
                'pedido_venda_id' => $pedido->id,
                'status_anterior' => $statusAnt,
                'status_novo' => 'cancelado',
                'acao' => 'cancelado',
                'descricao' => 'Pedido cancelado (itens integralmente cancelados). Motivo: ' . $motivo,
            ]);

            return;
        }

        PedidoVendaLog::create([
            'pedido_venda_id' => $pedido->id,
            'acao' => 'cancelamento_parcial',
            'descricao' => 'Cancelamento parcial (pré-faturamento). Motivo: ' . $motivo,
        ]);
    }

    private function cancelarPosFaturamentoTotal(PedidoVenda $pedido, string $motivo): void
    {
        $pedido->load(['itens.produto.composicoes.componente']);
        $valorEstorno = (float) $pedido->total_geral;
        $statusAnt = $pedido->status;

        foreach ($pedido->itens as $item) {
            $q = PedidoVendaEstoqueHelper::quantidadeLinhaAindaVendida($item);
            if ($pedido->estoque_baixado && $q > 0 && $item->tipo === 'produto') {
                PedidoVendaEstoqueHelper::estornarItemQuantidade($item, $q);
            }
            $item->quantidade_cancelada = (float) $item->quantidade;
            $item->save();
        }

        $pedido->refresh();
        $pedido->load('itens');
        $pedido->desconto_global = 0;
        $pedido->acrescimos_global = 0;
        $pedido->recalcularTotais();
        $pedido->status = 'cancelado';
        $pedido->data_cancelamento = now();
        $pedido->motivo_cancelamento = $motivo;
        $pedido->save();

        if ($valorEstorno > 0.009) {
            $this->criarContaPagarEstorno(
                $pedido,
                $valorEstorno,
                'PV-CANC-' . $pedido->id,
                'Estorno — pedido ' . $pedido->numero_sequencial . ' cancelado (total)',
                $motivo,
                'total',
                []
            );
        }

        PedidoVendaLog::create([
            'pedido_venda_id' => $pedido->id,
            'status_anterior' => $statusAnt,
            'status_novo' => 'cancelado',
            'acao' => 'cancelado',
            'descricao' => 'Pedido cancelado (pós-faturamento, total). Motivo: ' . $motivo,
        ]);
    }

    /**
     * @param  array<int, array{item_id: int, quantidade_cancelar: float}>  $itensPayload
     */
    private function cancelarPosFaturamentoParcial(PedidoVenda $pedido, string $motivo, array $itensPayload): void
    {
        $pedido->load('itens');
        $snapProd = (float) $pedido->total_produtos;
        $snapServ = (float) $pedido->total_servicos;
        $snapGlobal = (float) $pedido->desconto_global;
        $oldTotalGeral = (float) $pedido->total_geral;
        $baseOld = $snapProd + $snapServ;

        $porId = $pedido->itens->keyBy('id');
        $deltas = [];
        foreach ($itensPayload as $row) {
            $id = (int) ($row['item_id'] ?? 0);
            $q = (float) ($row['quantidade_cancelar'] ?? 0);
            if ($id <= 0 || $q <= 0) {
                continue;
            }
            $deltas[$id] = ($deltas[$id] ?? 0) + $q;
        }
        if ($deltas === []) {
            throw new \InvalidArgumentException('Informe ao menos um item com quantidade a cancelar.');
        }

        $pedido->load(['itens.produto.composicoes.componente']);

        /** @var array<int, array{descricao: string, tipo: string, quantidade_antes: float, quantidade_cancelada: float, quantidade_restante: float}> $resumoLinhasCp */
        $resumoLinhasCp = [];

        foreach ($deltas as $itemId => $delta) {
            $item = $porId->get($itemId);
            if (! $item) {
                throw new \InvalidArgumentException('Item #' . $itemId . ' não pertence a este pedido.');
            }
            if (! empty($item->os_gerada_id)) {
                throw new \RuntimeException('Não é possível cancelar o item "' . $item->descricao . '" pois possui OS vinculada.');
            }
            $ja = (float) ($item->quantidade_cancelada ?? 0);
            $qtd = (float) $item->quantidade;
            $disp = max(0, $qtd - $ja);
            if ($delta > $disp + 0.0001) {
                throw new \InvalidArgumentException('Quantidade a cancelar inválida no item "' . $item->descricao . '".');
            }
            $qEfetivaAntes = max(0, $qtd - $ja);

            if ($item->tipo === 'produto') {
                PedidoVendaEstoqueHelper::estornarItemQuantidade($item, $delta);
            }

            // Quantidade vendida no pedido passa a ser só o que resta (o estornado sai da linha).
            $novaQuantidade = round($qtd - $delta, 4);
            $resumoLinhasCp[] = [
                'descricao' => (string) $item->descricao,
                'tipo' => (string) $item->tipo,
                'quantidade_antes' => $qEfetivaAntes,
                'quantidade_cancelada' => $delta,
                'quantidade_restante' => max(0, round($qEfetivaAntes - $delta, 4)),
            ];

            if ($novaQuantidade <= 0.00001) {
                $item->delete();
            } else {
                // Com quantidade_cancelada = 0, o hook do item faz desconto = di * (qEff/q) = di sempre.
                // É preciso escalar desconto_inicial na mesma proporção da quantidade efetiva vendida.
                if ($qEfetivaAntes > 0.00001) {
                    $ratio = $novaQuantidade / $qEfetivaAntes;
                    $diAntes = $item->desconto_inicial !== null && $item->desconto_inicial !== ''
                        ? (float) $item->desconto_inicial
                        : (float) $item->desconto;
                    $item->desconto_inicial = round(max(0, $diAntes * $ratio), 2);
                } else {
                    $item->desconto_inicial = 0;
                }
                $item->quantidade = $novaQuantidade;
                $item->quantidade_cancelada = 0;
                $item->save();
            }
        }

        $pedido->refresh();
        $pedido->load('itens');
        $baseNew = 0.0;
        foreach ($pedido->itens as $it) {
            $q = (float) $it->quantidade;
            $qc = (float) ($it->quantidade_cancelada ?? 0);
            $qEff = max(0, $q - $qc);
            $baseNew += (float) $it->preco_unitario * $qEff;
        }
        $baseNew = round($baseNew, 2);
        $pedido->desconto_global = $baseOld > 0 ? round($snapGlobal * ($baseNew / $baseOld), 2) : 0;
        $pedido->recalcularTotais();
        $pedido->saveQuietly();

        $novoTotal = (float) $pedido->total_geral;
        $valorEstorno = round($oldTotalGeral - $novoTotal, 2);
        if ($valorEstorno > 0.009) {
            $doc = 'PV-PARC-' . $pedido->id . '-' . substr(preg_replace('/\D/', '', uniqid('', true)), -10);
            $this->criarContaPagarEstorno(
                $pedido->fresh(['itens', 'cliente', 'contasReceber']),
                $valorEstorno,
                $doc,
                'Estorno parcial — pedido ' . $pedido->numero_sequencial,
                $motivo,
                'parcial',
                $resumoLinhasCp
            );
        }

        $tudoZerado = true;
        foreach ($pedido->itens as $it) {
            if (PedidoVendaEstoqueHelper::quantidadeLinhaAindaVendida($it) > 0.00001) {
                $tudoZerado = false;
                break;
            }
        }

        $frasesHistorico = [];
        foreach ($resumoLinhasCp as $r) {
            $frasesHistorico[] = sprintf(
                '%s: havia %s no pedido; estorno de %s; ficou %s',
                $r['descricao'],
                format_quantidade_br($r['quantidade_antes']),
                format_quantidade_br($r['quantidade_cancelada']),
                format_quantidade_br($r['quantidade_restante'])
            );
        }
        $txtValorCp = $valorEstorno > 0.009
            ? 'Valor do estorno (conta a pagar): R$ ' . number_format($valorEstorno, 2, ',', '.')
            : 'Sem lançamento em contas a pagar (impacto financeiro zero após recalcular totais e descontos).';
        $descricaoLog = 'Cancelamento parcial (pós-faturamento). '
            . implode(' | ', $frasesHistorico)
            . '. ' . $txtValorCp
            . '. Motivo: '
            . $motivo;

        if ($tudoZerado) {
            $statusAnt = $pedido->status;
            $pedido->status = 'cancelado';
            $pedido->data_cancelamento = now();
            $pedido->motivo_cancelamento = $motivo;
            $pedido->save();
            PedidoVendaLog::create([
                'pedido_venda_id' => $pedido->id,
                'status_anterior' => $statusAnt,
                'status_novo' => 'cancelado',
                'acao' => 'cancelado',
                'descricao' => $descricaoLog . ' Pedido encerrado (sem itens ativos).',
                'metadata' => [
                    'cancelamento_parcial' => $resumoLinhasCp,
                    'valor_estorno' => $valorEstorno,
                ],
            ]);

            return;
        }

        PedidoVendaLog::create([
            'pedido_venda_id' => $pedido->id,
            'acao' => 'cancelamento_parcial',
            'descricao' => $descricaoLog,
            'metadata' => [
                'cancelamento_parcial' => $resumoLinhasCp,
                'valor_estorno' => $valorEstorno,
            ],
        ]);
    }

    private function criarContaPagarEstorno(
        PedidoVenda $pedido,
        float $valor,
        string $numeroDocumento,
        string $descricao,
        string $motivo,
        string $modo,
        array $linhasParciaisResumo = []
    ): void {
        $pedido->loadMissing([
            'cliente',
            'itens',
            'contasReceber.formaPagamento.contaBancaria',
            'contasReceber.adquirente',
        ]);
        $planoId = $this->getPlanoContaEstorno();
        $obs = $this->montarObservacoesEstornoPedidoVenda($pedido, $valor, $motivo, $modo, $linhasParciaisResumo);
        ContaPagar::create([
            'tenant_id' => auth()->user()->tenant_id ?? null,
            'plano_conta_id' => $planoId,
            'descricao' => $descricao,
            'numero_documento' => $numeroDocumento,
            'valor' => $valor,
            'valor_original' => $valor,
            'data_vencimento' => now()->toDateString(),
            'tipo' => 'outro',
            'status' => 'aberto',
            'observacoes' => $obs,
            'created_by' => auth()->id(),
        ]);
    }

    private function getPlanoContaEstorno(): ?int
    {
        $id = Configuracao::getValue('pedidos_venda.plano_conta_estorno');
        if ($id !== null && $id !== '' && is_numeric($id)) {
            return (int) $id;
        }

        return PlanoConta::where('tipo', 'despesa')->where('ativo', true)->orderBy('ordem')->orderBy('nome')->value('id');
    }

    /**
     * Observações da conta a pagar de estorno — mesmo padrão rastreável do PDV (plugins/pdv PdvApiController).
     *
     * @param  array<int, array{descricao: string, tipo: string, quantidade_cancelada: float}>  $linhasParciais
     */
    private function montarObservacoesEstornoPedidoVenda(
        PedidoVenda $pedido,
        float $valorEstorno,
        string $motivoOperacao,
        string $modo,
        array $linhasParciais = []
    ): string {
        $pedido->loadMissing([
            'cliente',
            'itens',
            'contasReceber.formaPagamento.contaBancaria',
            'contasReceber.adquirente',
        ]);

        $linhas = [];
        $titulo = $modo === 'parcial'
            ? '--- ESTORNO PARCIAL DE VENDA PEDIDO ---'
            : '--- ESTORNO DE VENDA PEDIDO (CANCELADA) ---';
        $linhas[] = $titulo;
        $linhas[] = 'ID do pedido: #' . $pedido->id . ' (' . $pedido->numero_sequencial . ')';
        $dataRef = $pedido->data_faturamento
            ? $pedido->data_faturamento->format('d/m/Y H:i')
            : ($pedido->data_pedido ? $pedido->data_pedido->format('d/m/Y') : '—');
        $linhas[] = 'Data do pedido / faturamento: ' . $dataRef;
        $linhas[] = 'Data/hora do lançamento: ' . now()->format('d/m/Y H:i');
        $linhas[] = '';
        $linhas[] = 'Valor deste estorno: R$ ' . number_format($valorEstorno, 2, ',', '.');
        $linhas[] = '';
        $nomeCliente = $pedido->cliente
            ? ($pedido->cliente->nome ?? $pedido->cliente->razao_social ?? 'Cliente #' . $pedido->cliente_id)
            : 'Balcão (sem cliente)';
        $linhas[] = 'Cliente: ' . $nomeCliente;
        if ($pedido->cliente_id) {
            $linhas[] = 'ID do cliente: ' . $pedido->cliente_id;
        }
        $linhas[] = '';
        $linhas[] = 'Total atual do pedido (após operação): R$ ' . number_format((float) $pedido->total_geral, 2, ',', '.');
        $linhas[] = 'Subtotal atual (itens): R$ ' . number_format((float) $pedido->total_produtos + (float) $pedido->total_servicos, 2, ',', '.');
        if ((float) $pedido->desconto_global > 0) {
            $linhas[] = 'Desconto global atual: R$ ' . number_format((float) $pedido->desconto_global, 2, ',', '.');
        }
        if ($modo === 'parcial' && $linhasParciais !== []) {
            $linhas[] = '';
            $linhas[] = '--- ITENS NESTE CANCELAMENTO PARCIAL ---';
            foreach ($linhasParciais as $lr) {
                $linhas[] = '  • ' . ($lr['descricao'] ?? 'Item') . ' [' . ($lr['tipo'] ?? '') . '] — Qtd cancelada: '
                    . number_format((float) ($lr['quantidade_cancelada'] ?? 0), 4, ',', '.');
            }
        }
        $linhas[] = '';
        $motivoTxt = $motivoOperacao !== '' ? $motivoOperacao : '(não informado)';
        $linhas[] = 'Motivo do cancelamento: ' . $motivoTxt;
        $operador = auth()->user();
        $linhas[] = 'Operador/registro: ' . ($operador?->name ?? '—');
        $linhas[] = '';
        $linhas[] = 'Canal: Pedido de venda (não PDV) | Registro: ' . ($operador?->name ?? '—');
        $linhas[] = '';
        $linhas[] = '--- FORMAS DE PAGAMENTO / TÍTULOS DA VENDA (para referência do estorno) ---';
        $crs = $pedido->contasReceber;
        if ($crs->isEmpty()) {
            $linhas[] = '  (Nenhuma conta a receber vinculada ao pedido.)';
        } else {
            foreach ($crs as $cr) {
                $forma = $cr->formaPagamento;
                $rotulo = $forma?->nome ?? ('Título / conta #' . $cr->id);
                $linhas[] = '  • ' . $rotulo . ': R$ ' . number_format((float) $cr->valor, 2, ',', '.') . ' — Status: ' . $cr->status;
                $tp = (int) ($cr->total_parcelas ?? 1);
                $np = (int) ($cr->numero_parcela ?? 1);
                if ($tp > 1) {
                    $linhas[] = '    Parcelas: ' . $tp . 'x (parcela ' . $np . '/' . $tp . ')';
                }
                if ($cr->adquirente_id) {
                    $adqNome = $cr->adquirente ? $cr->adquirente->nome : 'Adquirente #' . $cr->adquirente_id;
                    $linhas[] = '    Adquirente: ' . $adqNome;
                }
                if (! empty($cr->bandeira)) {
                    $linhas[] = '    Bandeira: ' . $cr->bandeira;
                }
                $conta = $cr->contaBancaria ?? $forma?->contaBancaria;
                if ($conta) {
                    $linhas[] = '    Conta (referência): ' . $conta->nome;
                }
                if ($forma && ((float) ($forma->taxa_percentual ?? 0) !== 0.0 || (float) ($forma->taxa_fixa ?? 0) !== 0.0)) {
                    $taxaValor = $forma->calcularTaxa((float) $cr->valor);
                    $linhas[] = '    Taxa/desconto da forma de pagamento (estimada sobre o título): R$ ' . number_format($taxaValor, 2, ',', '.');
                    $linhas[] = '      (Forma: ' . number_format((float) ($forma->taxa_percentual ?? 0), 2, ',', '') . '% + R$ '
                        . number_format((float) ($forma->taxa_fixa ?? 0), 2, ',', '.') . ' fixo)';
                }
                $linhas[] = '';
            }
        }
        $linhas[] = 'O operador do financeiro deve definir a melhor forma de estorno (devolução ao cliente, ajuste em caixa, etc.) e dar baixa neste título.';

        return implode("\n", $linhas);
    }

    // -----------------------------------------------------------------------
    // Faturamento (principal)
    // -----------------------------------------------------------------------

    /**
     * Fatura o pedido: processa pagamentos, baixa estoque e (opcional) gera OS para serviços.
     *
     * @param  array $dados  Dados do request (tipo_pagamento, pagamentos[], desconto_valor, etc.)
     */
    public function faturar(PedidoVenda $pedido, array $dados): void
    {
        if (!$pedido->podeFaturar()) {
            throw new \RuntimeException('Pedido não pode ser faturado no status atual: ' . $pedido->status);
        }

        $statusAnt = $pedido->status;

        // Aplicar desconto adicional informado no momento do faturamento
        $desconto = (float) ($dados['desconto_valor'] ?? 0);
        $valorAReceber = max((float) $pedido->total_geral - $desconto, 0);

        $planoContaId  = $this->getPlanoContaReceita();
        $centroCustoId = $this->getCentroCusto();

        $contasReceberCriadas = [];
        $foiPago = false;

        $tipoPagamento = $dados['tipo_pagamento'] ?? 'agora';

        if ($tipoPagamento === 'agora' && ! empty($dados['pagamentos']) && is_array($dados['pagamentos']) && $valorAReceber > 0) {
            $pagamentosValidos = array_values(array_filter(
                $dados['pagamentos'],
                fn ($p) => ! empty($p['forma_pagamento_id']) && isset($p['valor']) && (float) $p['valor'] > 0
            ));

            if ($pagamentosValidos !== [] && $pedido->cliente_id) {
                $financeiro = app(FinanceiroVendaPagamentosService::class);
                $numero = $pedido->numero_sequencial;
                $resultado = $financeiro->processar((int) $pedido->cliente_id, $pagamentosValidos, [
                    'plano_conta_id' => $planoContaId,
                    'centro_custo_id' => $centroCustoId,
                    'entidade_tipo' => 'pedido_venda',
                    'entidade_id' => $pedido->id,
                    'data_referencia' => now(),
                    'prefixo_descricao' => $numero,
                    'baixa_label' => 'Baixa automática no faturamento do pedido de venda',
                    'origem_tipo' => 'pedido_faturamento',
                    'tenant_id' => auth()->user()->tenant_id ?? null,
                    'origem_obs_fn' => static function ($forma) use ($numero) {
                        return sprintf('Origem: Pedido de venda %s | Forma: %s', $numero, $forma->nome);
                    },
                ]);
                $contasReceberCriadas = $resultado['contas'];
                $foiPago = $resultado['foi_pago'];
            }
        } elseif ($tipoPagamento === 'faturado' && $valorAReceber > 0) {
            // Faturado: conta a receber sem baixa automática
            $cr = ContaReceber::create([
                'entidade_tipo'   => 'pedido_venda',
                'entidade_id'     => $pedido->id,
                'cliente_id'      => $pedido->cliente_id,
                'descricao'       => $pedido->numero_sequencial,
                'valor'           => $valorAReceber,
                'valor_original'  => $valorAReceber,
                'data_vencimento' => now()->addDays(30),
                'numero_parcela'  => 1,
                'total_parcelas'  => 1,
                'plano_conta_id'  => $planoContaId,
                'centro_custo_id' => $centroCustoId,
                'status'          => 'aberto',
                'desconto'        => $desconto,
                'observacoes'     => "PV: {$pedido->numero_sequencial} | Faturado/Fiado",
                'origem_tipo'     => 'pedido_faturado_fiado',
                'created_by'      => auth()->id(),
            ]);
            $contasReceberCriadas[] = $cr;
        }

        // Baixar estoque
        $this->baixarEstoque($pedido);

        // Gerar OS para serviços (se configurado)
        if (Configuracao::getValue('pedidos_venda.gerar_os_servico') === '1') {
            $this->gerarOsParaServicos($pedido);
        }

        // Atualizar status
        if ($desconto > 0) {
            $pedido->desconto_global = (float) $pedido->desconto_global + $desconto;
            $pedido->total_descontos = (float) $pedido->total_descontos + $desconto;
            $pedido->total_geral     = $valorAReceber;
        }

        $pedido->status             = 'faturado';
        $pedido->data_faturamento   = now();
        $pedido->financeiro_gerado  = true;
        $pedido->save();

        PedidoVendaLog::create([
            'pedido_venda_id' => $pedido->id,
            'status_anterior' => $statusAnt,
            'status_novo'     => 'faturado',
            'acao'            => 'faturado',
            'descricao'       => 'Pedido faturado.' . ($foiPago ? ' Pago no ato.' : ' Conta a receber gerada.'),
            'metadata'        => ['contas_receber' => count($contasReceberCriadas)],
        ]);
    }

    // -----------------------------------------------------------------------
    // Estoque
    // -----------------------------------------------------------------------

    public function reservarEstoque(PedidoVenda $pedido): void
    {
        if ($pedido->estoque_reservado) {
            return;
        }

        foreach ($pedido->itens()->with(['produto.composicoes.componente'])->get() as $item) {
            if ($item->tipo !== 'produto' || !$item->produto) {
                continue;
            }
            $produto = $item->produto;
            $qtd     = (float) $item->quantidade;
            // Reserva simples (sem campo dedicado): apenas registrar flag
            // Uma implementação completa utilizaria uma tabela de reservas
        }

        $pedido->estoque_reservado = true;
        $pedido->saveQuietly();

        PedidoVendaLog::create([
            'pedido_venda_id' => $pedido->id,
            'acao'            => 'estoque_reservado',
            'descricao'       => 'Estoque reservado ao confirmar.',
        ]);
    }

    public function baixarEstoque(PedidoVenda $pedido): void
    {
        if ($pedido->estoque_baixado) {
            return;
        }

        foreach ($pedido->itens()->with(['produto.composicoes.componente'])->get() as $item) {
            if ($item->tipo !== 'produto' || !$item->produto) {
                continue;
            }
            $produto = $item->produto;
            $qtd     = (float) $item->quantidade;

            // Kit: baixar componentes
            if ($produto->formato === 'composicao' && $produto->composicoes->count() > 0) {
                foreach ($produto->composicoes as $comp) {
                    $componente = $comp->componente;
                    if (!$componente || $componente->estoque_quantidade === null) {
                        continue;
                    }
                    $qtdBaixa = (float) ($comp->quantidade ?? 0) * $qtd;
                    if ($qtdBaixa <= 0) {
                        continue;
                    }
                    $componente->estoque_quantidade = max((float) $componente->estoque_quantidade - $qtdBaixa, 0);
                    $componente->save();
                }
                continue;
            }

            if ($produto->estoque_quantidade === null) {
                continue;
            }
            $produto->estoque_quantidade = max((float) $produto->estoque_quantidade - $qtd, 0);
            $produto->save();
        }

        $pedido->estoque_baixado = true;
        $pedido->saveQuietly();
    }

    private function devolverReservaEstoque(PedidoVenda $pedido): void
    {
        $pedido->estoque_reservado = false;
        $pedido->saveQuietly();

        PedidoVendaLog::create([
            'pedido_venda_id' => $pedido->id,
            'acao'            => 'estoque_reserva_devolvida',
            'descricao'       => 'Reserva de estoque devolvida no cancelamento.',
        ]);
    }

    // -----------------------------------------------------------------------
    // Gerar OS para serviços
    // -----------------------------------------------------------------------

    public function gerarOsParaServicos(PedidoVenda $pedido): void
    {
        $itensServico = $pedido->itens()
            ->where('tipo', 'servico')
            ->where('gerar_os', true)
            ->whereNull('os_gerada_id')
            ->get();

        foreach ($itensServico as $item) {
            $os = OrdemServico::create([
                'cliente_id'       => $pedido->cliente_id,
                'status'           => 'Aberta',
                'data_abertura'    => now(),
                'relato_cliente'   => $item->descricao,
                'observacoes_internas' => "Gerada a partir do PV {$pedido->numero_sequencial}.",
                'created_by'       => auth()->id(),
                'financeiro_gerado'=> true,
            ]);

            $item->os_gerada_id = $os->id;
            $item->saveQuietly();

            PedidoVendaLog::create([
                'pedido_venda_id' => $pedido->id,
                'acao'            => 'os_gerada',
                'descricao'       => "OS #{$os->id} gerada para o item: {$item->descricao}",
                'metadata'        => ['os_id' => $os->id, 'item_id' => $item->id],
            ]);
        }
    }

    // -----------------------------------------------------------------------
    // Duplicar pedido
    // -----------------------------------------------------------------------

    public function duplicar(PedidoVenda $pedido): PedidoVenda
    {
        $novo = $pedido->replicate(['numero_sequencial', 'status', 'data_confirmacao', 'data_faturamento', 'data_entrega', 'data_cancelamento', 'estoque_reservado', 'estoque_baixado', 'financeiro_gerado', 'motivo_cancelamento', 'deleted_at']);
        $novo->status              = 'rascunho';
        $novo->origem_pedido_id    = $pedido->id;
        $novo->origem_tipo         = 'duplicado';
        $novo->data_pedido         = now()->toDateString();
        $novo->data_prevista_entrega = null;
        $novo->save();

        foreach ($pedido->itens as $item) {
            $novoItem = $item->replicate(['os_gerada_id']);
            $novoItem->pedido_venda_id = $novo->id;
            $novoItem->quantidade_cancelada = 0;
            $novoItem->desconto_inicial = $novoItem->desconto;
            $novoItem->save();
        }

        PedidoVendaLog::create([
            'pedido_venda_id' => $novo->id,
            'acao'            => 'duplicado',
            'descricao'       => "Duplicado do pedido {$pedido->numero_sequencial}.",
        ]);

        return $novo;
    }

    // -----------------------------------------------------------------------
    // Auxiliares
    // -----------------------------------------------------------------------

    private function getPlanoContaReceita(): ?int
    {
        $id = Configuracao::getValue('pedidos_venda.plano_conta_receita');
        if ($id && is_numeric($id)) {
            return (int) $id;
        }
        return PlanoConta::where('codigo', '1.1')->orWhere('nome', 'Receita de Serviços')->first()?->id;
    }

    private function getCentroCusto(): ?int
    {
        $id = Configuracao::getValue('pedidos_venda.centro_custo');
        return ($id && is_numeric($id)) ? (int) $id : null;
    }
}
