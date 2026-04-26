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

use App\Models\Adquirente;
use App\Models\BaixaTitulo;
use App\Models\ContaBancaria;
use App\Models\ContaReceber;
use App\Models\FormaPagamento;
use App\Models\MovimentacaoFinanceira;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class FinanceiroVendaPagamentosService
{
    /**
     * Processa pagamentos informados (ex.: finalização PDV ou faturamento de pedido de venda).
     *
     * @param  array<int, array<string, mixed>>  $pagamentos  linhas com forma_pagamento_id, valor, parcelas?, conta_bancaria_id?, adquirente_id?, bandeira?, antecipado?
     * @param  array{
     *   plano_conta_id?: ?int,
     *   centro_custo_id?: ?int,
     *   entidade_tipo?: ?string,
     *   entidade_id?: ?int,
     *   data_referencia?: \Carbon\Carbon,
     *   origem_obs_fn: callable(\App\Models\FormaPagamento $forma): string,
     *   prefixo_descricao: string,
     *   baixa_label?: string,
     *   origem_tipo?: ?string,
     *   tenant_id?: ?int,
     * }  $opcoes
     * @return array{contas: array<int, \App\Models\ContaReceber>, foi_pago: bool}
     */
    public function processar(int $clienteId, array $pagamentos, array $opcoes): array
    {
        $planoContaId = $opcoes['plano_conta_id'] ?? null;
        $centroCustoId = $opcoes['centro_custo_id'] ?? null;
        $entidadeTipo = $opcoes['entidade_tipo'] ?? null;
        $entidadeId = $opcoes['entidade_id'] ?? null;
        $dataRef = $opcoes['data_referencia'] ?? Carbon::now();
        /** @var callable $origemObsFn */
        $origemObsFn = $opcoes['origem_obs_fn'];
        $prefixoDescricao = (string) ($opcoes['prefixo_descricao'] ?? '');
        $baixaLabel = (string) ($opcoes['baixa_label'] ?? 'Baixa automática');
        $origemTipo = $opcoes['origem_tipo'] ?? null;
        $tenantId = $opcoes['tenant_id'] ?? (auth()->user()->tenant_id ?? null);

        $adquirenteService = app(AdquirenteService::class);
        $contasCriadas = [];
        $foiPago = false;

        foreach ($pagamentos as $row) {
            $forma = FormaPagamento::find($row['forma_pagamento_id'] ?? null);
            if (!$forma) {
                continue;
            }

            $valorPagamento = (float) ($row['valor'] ?? 0);
            if ($valorPagamento <= 0) {
                continue;
            }

            $parcelas = max(1, (int) ($row['parcelas'] ?? 1));
            $tipoForma = strtolower((string) ($forma->tipo ?? ''));
            $ehCartao = in_array($tipoForma, ['cartao_credito', 'cartao_debito'], true);
            $adquirenteId = $forma->resolverAdquirenteId(isset($row['adquirente_id']) ? (int) $row['adquirente_id'] : null);
            $contaExplicita = ! empty($row['conta_bancaria_id']) ? (int) $row['conta_bancaria_id'] : null;
            $contaBancariaId = $forma->resolverContaBancariaIdParaRecebimento($contaExplicita, $adquirenteId);
            if (! $contaBancariaId) {
                $contaBancariaId = ContaBancaria::where('ativo', true)->first()?->id;
            }
            if (! $contaBancariaId) {
                Log::warning('FinanceiroVendaPagamentos: forma sem conta bancária', ['forma_id' => $forma->id]);

                continue;
            }

            $origemObs = $origemObsFn($forma);
            $bandeira = strtolower(trim((string) ($row['bandeira'] ?? 'outros')) ?: 'outros');

            $baseCr = [
                'cliente_id' => $clienteId,
                'plano_conta_id' => $planoContaId,
                'centro_custo_id' => $centroCustoId,
                'created_by' => auth()->id(),
            ];
            if ($origemTipo !== null && $origemTipo !== '') {
                $baseCr['origem_tipo'] = $origemTipo;
            }
            if ($entidadeTipo !== null && $entidadeId !== null) {
                $baseCr['entidade_tipo'] = $entidadeTipo;
                $baseCr['entidade_id'] = (int) $entidadeId;
            }

            // Cartão com adquirente: simular taxas e agenda (como no PDV)
            if ($ehCartao && $adquirenteId > 0) {
                $adquirente = Adquirente::find($adquirenteId);
                $antecipado = isset($row['antecipado'])
                    ? (bool) $row['antecipado']
                    : ($adquirente && $adquirente->tipo_antecipacao === 'antecipado');
                $simulacao = null;
                try {
                    $simulacao = $tipoForma === 'cartao_debito'
                        ? $adquirenteService->simularTransacaoDebito($valorPagamento, $adquirenteId, $bandeira, $dataRef)
                        : $adquirenteService->simularTransacao($valorPagamento, $parcelas, $adquirenteId, $bandeira, $antecipado, $dataRef);
                } catch (\Throwable $e) {
                    Log::warning('FinanceiroVendaPagamentos: taxa cartão não encontrada, usando fallback. ' . $e->getMessage());
                }

                if ($simulacao && ! empty($simulacao['agenda_recebiveis'])) {
                    $agenda = $simulacao['agenda_recebiveis'];
                    $adquirenteNome = Adquirente::find($adquirenteId)?->nome ?? 'N/A';
                    $numItens = count($agenda);
                    $valorBrutoPorParcela = $numItens > 0 ? round($valorPagamento / $numItens, 2) : $valorPagamento;
                    $restoBruto = round($valorPagamento - ($valorBrutoPorParcela * $numItens), 2);

                    foreach ($agenda as $idx => $item) {
                        $valorBruto = $idx === 0 ? $valorBrutoPorParcela + $restoBruto : $valorBrutoPorParcela;
                        $valorLiquido = (float) ($item['valor'] ?? $valorBruto);
                        $taxaParcela = round($valorBruto - $valorLiquido, 2);
                        $dataReceb = isset($item['data']) ? Carbon::parse($item['data']) : $dataRef;
                        $parcelaNum = $item['parcela'] ?? ($idx + 1);
                        $totalParc = $item['total_parcelas'] ?? $numItens;

                        $obs = sprintf(
                            '%s | Adquirente: %s | Bandeira: %s | Parcela: %s/%s | Valor bruto: R$ %s | Taxa: R$ %s | Valor líquido: R$ %s | Previsão: %s | Antecipado: %s | Status: pago (baixa automática)',
                            $origemObs,
                            $adquirenteNome,
                            $bandeira,
                            $parcelaNum,
                            $totalParc,
                            number_format($valorBruto, 2, ',', '.'),
                            number_format($taxaParcela, 2, ',', '.'),
                            number_format($valorLiquido, 2, ',', '.'),
                            $dataReceb->format('d/m/Y'),
                            $antecipado ? 'sim' : 'não'
                        );

                        $contaReceber = ContaReceber::create(array_merge($baseCr, [
                            'descricao' => $prefixoDescricao . ($totalParc > 1 ? " - Parcela {$parcelaNum}/{$totalParc}" : ''),
                            'valor' => $valorBruto,
                            'valor_original' => $valorBruto,
                            'valor_recebido' => $valorBruto,
                            'data_vencimento' => $dataReceb,
                            'data_recebimento' => $dataReceb,
                            'numero_parcela' => $totalParc > 1 ? (int) $parcelaNum : 1,
                            'total_parcelas' => $totalParc > 1 ? (int) $totalParc : 1,
                            'forma_pagamento_id' => $forma->id,
                            'adquirente_id' => $adquirenteId,
                            'bandeira' => $bandeira,
                            'conta_bancaria_id' => $contaBancariaId,
                            'status' => 'pago',
                            'desconto' => 0,
                            'observacoes' => $obs,
                        ]));
                        $contasCriadas[] = $contaReceber;

                        // Caixa: entrada no valor BRUTO + saída da taxa = líquido na conta (igual encerramento OS / doc adiantamento)
                        $obsEntradaCartao = sprintf(
                            'Recebimento bruto R$ %s | Líquido na conta R$ %s | Taxa R$ %s | %s',
                            number_format($valorBruto, 2, ',', '.'),
                            number_format($valorLiquido, 2, ',', '.'),
                            number_format($taxaParcela, 2, ',', '.'),
                            $origemObs
                        );
                        $mov = $this->criarMovimentacaoEntrada(
                            $contaBancariaId,
                            $contaReceber,
                            $valorBruto,
                            $dataReceb,
                            $obsEntradaCartao,
                            $planoContaId,
                            $centroCustoId,
                            true,
                            $dataReceb->toDateString(),
                            $tenantId
                        );

                        $taxaMovIdVendaCartao = null;
                        if ($taxaParcela > 0) {
                            $obsSaidaTaxa = sprintf(
                                'Taxa adquirente (desconto da tarifa) R$ %s | %s | Bandeira: %s | Parcela %s/%s | ContaReceber #%s',
                                number_format($taxaParcela, 2, ',', '.'),
                                $contaReceber->descricao,
                                $bandeira,
                                $parcelaNum,
                                $totalParc,
                                $contaReceber->id
                            );
                            $movTaxaVendaCartao = $this->criarMovimentacaoSaidaTaxa(
                                $contaBancariaId,
                                $contaReceber,
                                $taxaParcela,
                                $dataReceb,
                                'Taxa adquirente: ' . $contaReceber->descricao,
                                $obsSaidaTaxa,
                                $planoContaId,
                                $centroCustoId,
                                true,
                                $dataReceb->toDateString(),
                                $tenantId
                            );
                            $taxaMovIdVendaCartao = $movTaxaVendaCartao->id;
                        }

                        BaixaTitulo::create([
                            'tipo_titulo' => 'conta_receber',
                            'titulo_id' => $contaReceber->id,
                            'movimentacao_id' => $mov->id,
                            'taxa_movimentacao_id' => $taxaMovIdVendaCartao,
                            'valor_baixa' => $valorBruto,
                            'data_baixa' => $dataReceb,
                            'juros' => 0, 'multa' => 0, 'desconto' => 0,
                            'observacoes' => $origemObs . ' | ' . $baixaLabel,
                            'created_by' => auth()->id(),
                        ]);
                        $contaReceber->valor_recebido = $valorBruto;
                        $contaReceber->data_recebimento = $dataReceb;
                        $contaReceber->save();
                        $foiPago = true;
                    }

                    continue;
                }
                // Sem agenda: cair no fluxo genérico (como PDV)
            }

            // Boleto / transferência: títulos em aberto (valor dividido entre parcelas)
            if (in_array($tipoForma, ['boleto', 'transferencia'], true)) {
                $diasReceb = (int) ($forma->dias_recebimento ?? 1);
                $valParc = $parcelas > 1 ? round($valorPagamento / $parcelas, 2) : $valorPagamento;
                $resto = round($valorPagamento - ($valParc * $parcelas), 2);
                for ($i = 1; $i <= $parcelas; $i++) {
                    $vp = $i === 1 ? $valParc + $resto : $valParc;
                    $dataV = $i === 1
                        ? $dataRef->copy()->addDays($diasReceb)
                        : $dataRef->copy()->addDays($diasReceb + ($i - 1) * 30);
                    $taxaPrevista = method_exists($forma, 'calcularTaxa') ? $forma->calcularTaxa($vp) : 0;
                    $valorLiquidoEsperado = round($vp - $taxaPrevista, 2);
                    $obsBoletoTransf = sprintf(
                        '%s | Valor parcela: R$ %s | Taxa prevista (forma): R$ %s | Valor líquido esperado: R$ %s | Vencimento: %s | Status: aberto (pendente conciliação)',
                        $origemObs,
                        number_format($vp, 2, ',', '.'),
                        number_format($taxaPrevista, 2, ',', '.'),
                        number_format($valorLiquidoEsperado, 2, ',', '.'),
                        $dataV->format('d/m/Y')
                    );
                    $cr = ContaReceber::create(array_merge($baseCr, [
                        'descricao' => $prefixoDescricao . ($parcelas > 1 ? " - Parcela {$i}/{$parcelas}" : ''),
                        'valor' => $vp,
                        'valor_original' => $vp,
                        'data_vencimento' => $dataV,
                        'numero_parcela' => $i,
                        'total_parcelas' => $parcelas,
                        'forma_pagamento_id' => $forma->id,
                        'conta_bancaria_id' => $contaBancariaId,
                        'status' => 'aberto',
                        'desconto' => 0,
                        'observacoes' => $obsBoletoTransf,
                    ]));
                    $contasCriadas[] = $cr;
                }

                continue;
            }

            // PIX parcelado: datas por parcela — baixa automática só nas parcelas com vencimento até a data de referência
            if ($tipoForma === 'pix' && $parcelas > 1) {
                $vencimentos = $row['pix_parcela_vencimentos'] ?? [];
                if (! is_array($vencimentos) || count($vencimentos) !== $parcelas) {
                    // PDV pode não enviar agenda de vencimentos; gera grade mensal automaticamente.
                    $vencimentos = [];
                    for ($p = 1; $p <= $parcelas; $p++) {
                        $vencimentos[] = $dataRef->copy()->addMonthsNoOverflow($p - 1)->toDateString();
                    }
                }
                $chavesCadastradas = count($forma->pix_chaves_ativas ?? []) > 0;
                $pixChaveParcelado = $forma->encontrarPixChavePorId($row['pix_chave_id'] ?? null);
                if ($chavesCadastradas && ! $pixChaveParcelado) {
                    throw new \RuntimeException('Selecione a chave PIX de recebimento para pagamento parcelado.');
                }
                if ($pixChaveParcelado && ! empty($pixChaveParcelado['conta_bancaria_id'])) {
                    $contaBancariaId = (int) $pixChaveParcelado['conta_bancaria_id'];
                }
                if ($pixChaveParcelado) {
                    $taxaPix = round(
                        ($valorPagamento * ((float) ($pixChaveParcelado['taxa_percentual'] ?? 0) / 100))
                        + (float) ($pixChaveParcelado['taxa_fixa'] ?? 0),
                        2
                    );
                    if ($taxaPix <= 0) {
                        $taxaPix = method_exists($forma, 'calcularTaxa') ? (float) $forma->calcularTaxa($valorPagamento) : 0.0;
                    }
                } else {
                    $taxaPix = method_exists($forma, 'calcularTaxa') ? (float) $forma->calcularTaxa($valorPagamento) : 0.0;
                }
                $valorParcelaPix = round($valorPagamento / $parcelas, 2);
                $restoValorPix = round($valorPagamento - ($valorParcelaPix * $parcelas), 2);
                $taxaParcelaPix = round($taxaPix / $parcelas, 2);
                $restoTaxaPix = round($taxaPix - ($taxaParcelaPix * $parcelas), 2);
                $refDia = $dataRef->copy()->startOfDay();
                for ($i = 1; $i <= $parcelas; $i++) {
                    try {
                        $dataVencPix = Carbon::parse($vencimentos[$i - 1])->startOfDay();
                    } catch (\Throwable $e) {
                        throw new \RuntimeException('Data de vencimento inválida na parcela ' . $i . ' do PIX.');
                    }
                    $vp = $i === 1 ? $valorParcelaPix + $restoValorPix : $valorParcelaPix;
                    $tp = $i === 1 ? $taxaParcelaPix + $restoTaxaPix : $taxaParcelaPix;
                    $vl = round($vp - $tp, 2);
                    $recebidoNaData = $dataVencPix->lte($refDia);
                    if ($recebidoNaData) {
                        if (class_exists(ConciliacaoService::class)) {
                            $conciliacaoPix = ConciliacaoService::calcularSemAdquirente($forma, $dataRef->toDateString());
                        } else {
                            $conciliacaoPix = [
                                'conciliado' => true,
                                'data_conciliacao' => $dataRef->toDateString(),
                                'obs_liquidacao' => null,
                            ];
                        }
                        $statusConcilObsPix = ! empty($conciliacaoPix['obs_liquidacao'])
                            ? ' | ' . $conciliacaoPix['obs_liquidacao']
                            : ' | Status: pago (baixa na hora)';
                        $obsPixParcPago = sprintf(
                            '%s | PIX parcela %s/%s | Valor: R$ %s | Taxa: R$ %s | Líquido: R$ %s | Vencimento: %s%s',
                            $origemObs,
                            $i,
                            $parcelas,
                            number_format($vp, 2, ',', '.'),
                            number_format($tp, 2, ',', '.'),
                            number_format($vl, 2, ',', '.'),
                            $dataVencPix->format('d/m/Y'),
                            $statusConcilObsPix
                        );
                        $metaPix = $pixChaveParcelado ? ['pix_chave_id' => $pixChaveParcelado['id'] ?? null] : [];
                        $contaReceber = ContaReceber::create(array_merge($baseCr, [
                            'descricao' => $prefixoDescricao . " - Parcela {$i}/{$parcelas}",
                            'valor' => $vp,
                            'valor_original' => $vp,
                            'data_vencimento' => $dataVencPix,
                            'numero_parcela' => $i,
                            'total_parcelas' => $parcelas,
                            'forma_pagamento_id' => $forma->id,
                            'conta_bancaria_id' => $contaBancariaId,
                            'status' => 'pago',
                            'desconto' => 0,
                            'observacoes' => $obsPixParcPago,
                            'metadata' => $metaPix,
                        ]));
                        $contasCriadas[] = $contaReceber;
                        $obsEntradaPix = $tp > 0
                            ? sprintf(
                                'Recebimento bruto R$ %s | Líquido na conta R$ %s | Taxa R$ %s | %s',
                                number_format($vp, 2, ',', '.'),
                                number_format($vl, 2, ',', '.'),
                                number_format($tp, 2, ',', '.'),
                                $origemObs
                            )
                            : sprintf('Recebimento R$ %s | %s', number_format($vp, 2, ',', '.'), $origemObs);
                        $mov = $this->criarMovimentacaoEntrada(
                            $contaBancariaId,
                            $contaReceber,
                            $vp,
                            $dataRef->copy(),
                            $obsEntradaPix,
                            $planoContaId,
                            $centroCustoId,
                            (bool) ($conciliacaoPix['conciliado'] ?? false),
                            $conciliacaoPix['data_conciliacao'] ?? null,
                            $tenantId
                        );
                        $taxaMovIdVendaPixParc = null;
                        if ($tp > 0) {
                            $movTaxaVendaPixParc = $this->criarMovimentacaoSaidaTaxa(
                                $contaBancariaId,
                                $contaReceber,
                                $tp,
                                $dataRef->copy(),
                                'Taxa: ' . $contaReceber->descricao,
                                'Taxa PIX — faturamento (parcelado)',
                                $planoContaId,
                                $centroCustoId,
                                (bool) ($conciliacaoPix['conciliado'] ?? false),
                                $conciliacaoPix['data_conciliacao'] ?? null,
                                $tenantId
                            );
                            $taxaMovIdVendaPixParc = $movTaxaVendaPixParc->id;
                        }
                        BaixaTitulo::create([
                            'tipo_titulo' => 'conta_receber',
                            'titulo_id' => $contaReceber->id,
                            'movimentacao_id' => $mov->id,
                            'taxa_movimentacao_id' => $taxaMovIdVendaPixParc,
                            'valor_baixa' => $vp,
                            'data_baixa' => $dataRef->copy(),
                            'juros' => 0, 'multa' => 0, 'desconto' => 0,
                            'observacoes' => $origemObs . ' | ' . $baixaLabel . ' (PIX parcelado — vencimento até a data do faturamento)',
                            'created_by' => auth()->id(),
                        ]);
                        $contaReceber->valor_recebido = $vp;
                        $contaReceber->data_recebimento = $dataRef->copy();
                        $contaReceber->save();
                        $foiPago = true;
                    } else {
                        $obsPixFuturo = sprintf(
                            '%s | PIX parcela %s/%s | Valor: R$ %s | Vencimento: %s | Status: aberto (confirmar recebimento no financeiro)',
                            $origemObs,
                            $i,
                            $parcelas,
                            number_format($vp, 2, ',', '.'),
                            $dataVencPix->format('d/m/Y')
                        );
                        $metaPixF = $pixChaveParcelado ? ['pix_chave_id' => $pixChaveParcelado['id'] ?? null] : [];
                        $contaReceber = ContaReceber::create(array_merge($baseCr, [
                            'descricao' => $prefixoDescricao . " - Parcela {$i}/{$parcelas}",
                            'valor' => $vp,
                            'valor_original' => $vp,
                            'data_vencimento' => $dataVencPix,
                            'numero_parcela' => $i,
                            'total_parcelas' => $parcelas,
                            'forma_pagamento_id' => $forma->id,
                            'conta_bancaria_id' => $contaBancariaId,
                            'status' => 'aberto',
                            'desconto' => 0,
                            'observacoes' => $obsPixFuturo,
                            'metadata' => $metaPixF,
                        ]));
                        $contasCriadas[] = $contaReceber;
                    }
                }

                continue;
            }

            // Dinheiro, PIX (1x), outros e cartão sem agenda: parcelas com entrada BRUTA + saída da taxa (igual adiantamento OS / PDV)
            $diasReceb = (int) ($forma->dias_recebimento ?? 0);
            $dataBaixa = $diasReceb === 0 ? $dataRef->copy() : $dataRef->copy()->addDays($diasReceb);
            $pixChaveUnica = $forma->encontrarPixChavePorId($row['pix_chave_id'] ?? null);
            if ($tipoForma === 'pix' && $pixChaveUnica && ! empty($pixChaveUnica['conta_bancaria_id'])) {
                $contaBancariaId = (int) $pixChaveUnica['conta_bancaria_id'];
            }
            if ($tipoForma === 'pix' && $pixChaveUnica) {
                $taxaTotal = round(
                    ($valorPagamento * ((float) ($pixChaveUnica['taxa_percentual'] ?? 0) / 100))
                    + (float) ($pixChaveUnica['taxa_fixa'] ?? 0),
                    2
                );
                if ($taxaTotal <= 0) {
                    $taxaTotal = method_exists($forma, 'calcularTaxa') ? (float) $forma->calcularTaxa($valorPagamento) : 0.0;
                }
            } else {
                $taxaTotal = method_exists($forma, 'calcularTaxa') ? (float) $forma->calcularTaxa($valorPagamento) : 0.0;
            }

            if (class_exists(ConciliacaoService::class)) {
                $conciliacaoPdv = ConciliacaoService::calcularSemAdquirente($forma, $dataBaixa->toDateString());
            } else {
                $conciliadoAgora = $diasReceb === 0 || $dataBaixa->lte(Carbon::now()->endOfDay());
                $conciliacaoPdv = [
                    'conciliado' => $conciliadoAgora,
                    'data_conciliacao' => $conciliadoAgora ? $dataBaixa->toDateString() : null,
                    'obs_liquidacao' => $conciliadoAgora ? null : 'Liquidação prevista em ' . $dataBaixa->format('d/m/Y'),
                ];
            }

            $valorParcela = $parcelas > 1 ? round($valorPagamento / $parcelas, 2) : $valorPagamento;
            $restoValor = round($valorPagamento - ($valorParcela * $parcelas), 2);
            $taxaParcela = $parcelas > 1 ? round($taxaTotal / $parcelas, 2) : $taxaTotal;
            $restoTaxa = round($taxaTotal - ($taxaParcela * $parcelas), 2);
            $obsLiqPdv = ! empty($conciliacaoPdv['obs_liquidacao']) ? ' | ' . $conciliacaoPdv['obs_liquidacao'] : '';

            for ($i = 1; $i <= $parcelas; $i++) {
                $vp = $i === 1 ? $valorParcela + $restoValor : $valorParcela;
                $tp = $i === 1 ? $taxaParcela + $restoTaxa : $taxaParcela;
                $vl = round($vp - $tp, 2);
                $descricaoParcela = $prefixoDescricao . ($parcelas > 1 ? " - Parcela {$i}/{$parcelas}" : '');

                $obs = sprintf(
                    '%s | Valor bruto: R$ %s | Taxa: R$ %s | Valor líquido: R$ %s | Parcela %s/%s | Recebimento: %s | Status: pago (baixa automática)%s',
                    $origemObs,
                    number_format($vp, 2, ',', '.'),
                    number_format($tp, 2, ',', '.'),
                    number_format($vl, 2, ',', '.'),
                    $i,
                    $parcelas,
                    $dataBaixa->format('d/m/Y' . ($diasReceb === 0 ? ' H:i' : '')),
                    $obsLiqPdv
                );
                if ($ehCartao && $adquirenteId > 0) {
                    $obs .= ' | Adquirente ID: ' . $adquirenteId . ' | Bandeira: ' . $bandeira;
                }

                $contaReceber = ContaReceber::create(array_merge($baseCr, [
                    'descricao' => $descricaoParcela,
                    'valor' => $vp,
                    'valor_original' => $vp,
                    'data_vencimento' => $dataBaixa,
                    'data_recebimento' => $dataBaixa,
                    'numero_parcela' => $parcelas > 1 ? $i : 1,
                    'total_parcelas' => $parcelas > 1 ? $parcelas : 1,
                    'forma_pagamento_id' => $forma->id,
                    'adquirente_id' => $ehCartao ? ($adquirenteId > 0 ? $adquirenteId : null) : null,
                    'bandeira' => $ehCartao ? $bandeira : null,
                    'conta_bancaria_id' => $contaBancariaId,
                    'status' => 'pago',
                    'desconto' => 0,
                    'observacoes' => $obs,
                ]));
                $contasCriadas[] = $contaReceber;

                $obsEntrada = sprintf(
                    'Recebimento bruto R$ %s | Líquido na conta R$ %s | Taxa R$ %s | %s | Parcela %s/%s | ContaReceber #%s',
                    number_format($vp, 2, ',', '.'),
                    number_format($vl, 2, ',', '.'),
                    number_format($tp, 2, ',', '.'),
                    $origemObs,
                    $i,
                    $parcelas,
                    $contaReceber->id
                );
                $mov = $this->criarMovimentacaoEntrada(
                    $contaBancariaId,
                    $contaReceber,
                    $vp,
                    $dataBaixa,
                    $obsEntrada,
                    $planoContaId,
                    $centroCustoId,
                    (bool) ($conciliacaoPdv['conciliado'] ?? false),
                    $conciliacaoPdv['data_conciliacao'] ?? null,
                    $tenantId
                );

                $taxaMovIdVendaPdv = null;
                if ($tp > 0) {
                    $obsSaidaTaxa = sprintf(
                        'Taxa forma de pagamento (desconto da tarifa) R$ %s | %s | Parcela %s/%s | ContaReceber #%s',
                        number_format($tp, 2, ',', '.'),
                        $contaReceber->descricao,
                        $i,
                        $parcelas,
                        $contaReceber->id
                    );
                    $movTaxaVendaPdv = $this->criarMovimentacaoSaidaTaxa(
                        $contaBancariaId,
                        $contaReceber,
                        $tp,
                        $dataBaixa,
                        'Taxa: ' . $contaReceber->descricao,
                        $obsSaidaTaxa,
                        $planoContaId,
                        $centroCustoId,
                        (bool) ($conciliacaoPdv['conciliado'] ?? false),
                        $conciliacaoPdv['data_conciliacao'] ?? null,
                        $tenantId
                    );
                    $taxaMovIdVendaPdv = $movTaxaVendaPdv->id;
                }

                BaixaTitulo::create([
                    'tipo_titulo' => 'conta_receber',
                    'titulo_id' => $contaReceber->id,
                    'movimentacao_id' => $mov->id,
                    'taxa_movimentacao_id' => $taxaMovIdVendaPdv,
                    'valor_baixa' => $vp,
                    'data_baixa' => $dataBaixa,
                    'juros' => 0, 'multa' => 0, 'desconto' => 0,
                    'observacoes' => $origemObs . ' | ' . $baixaLabel,
                    'created_by' => auth()->id(),
                ]);
                $contaReceber->valor_recebido = $vp;
                $contaReceber->data_recebimento = $dataBaixa;
                $contaReceber->save();
                $foiPago = true;
            }
        }

        return ['contas' => $contasCriadas, 'foi_pago' => $foiPago];
    }

    private function criarMovimentacaoEntrada(
        int $contaBancariaId,
        ContaReceber $cr,
        float $valor,
        Carbon $data,
        string $observacoes,
        ?int $planoContaId,
        ?int $centroCustoId,
        bool $conciliado = true,
        $dataConciliacao = null,
        ?int $tenantId = null
    ): MovimentacaoFinanceira {
        $payload = [
            'conta_bancaria_id' => $contaBancariaId,
            'tipo' => 'entrada',
            'origem' => 'conta_receber',
            'conta_receber_id' => $cr->id,
            'valor' => $valor,
            'data_movimentacao' => $data,
            'descricao' => 'Recebimento: ' . $cr->descricao,
            'observacoes' => $observacoes,
            'conciliado' => $conciliado,
            'data_conciliacao' => $conciliado ? ($dataConciliacao ?? $data->toDateString()) : null,
            'conciliado_por' => $conciliado ? auth()->id() : null,
            'created_by' => auth()->id(),
        ];
        if ($tenantId !== null) {
            $payload['tenant_id'] = $tenantId;
        }
        if ($planoContaId) {
            $payload['plano_conta_id'] = $planoContaId;
        }
        if ($centroCustoId) {
            $payload['centro_custo_id'] = $centroCustoId;
        }

        return MovimentacaoFinanceira::create($payload);
    }

    private function criarMovimentacaoSaidaTaxa(
        int $contaBancariaId,
        ContaReceber $cr,
        float $valor,
        Carbon $data,
        string $descricao,
        string $observacoes,
        ?int $planoContaId,
        ?int $centroCustoId,
        bool $conciliado = true,
        $dataConciliacao = null,
        ?int $tenantId = null
    ): MovimentacaoFinanceira {
        $payload = [
            'conta_bancaria_id' => $contaBancariaId,
            'tipo' => 'saida',
            'origem' => 'outro',
            'conta_receber_id' => $cr->id,
            'valor' => $valor,
            'data_movimentacao' => $data,
            'descricao' => $descricao,
            'observacoes' => $observacoes,
            'conciliado' => $conciliado,
            'data_conciliacao' => $conciliado ? ($dataConciliacao ?? $data->toDateString()) : null,
            'conciliado_por' => $conciliado ? auth()->id() : null,
            'created_by' => auth()->id(),
        ];
        if ($tenantId !== null) {
            $payload['tenant_id'] = $tenantId;
        }
        if ($planoContaId) {
            $payload['plano_conta_id'] = $planoContaId;
        }
        if ($centroCustoId) {
            $payload['centro_custo_id'] = $centroCustoId;
        }

        return MovimentacaoFinanceira::create($payload);
    }
}
