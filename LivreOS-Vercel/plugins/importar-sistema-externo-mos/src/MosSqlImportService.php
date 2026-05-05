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

namespace Plugins\ImportarSistemaExternoMos\Services;

use App\Models\Cliente;
use App\Models\BaixaTitulo;
use App\Models\ContaBancaria;
use App\Models\ContaPagar;
use App\Models\ContaPagarAnexo;
use App\Models\ContaReceber;
use App\Models\ContaReceberAnexo;
use App\Models\Contato;
use App\Models\FormaPagamento;
use App\Models\Documento;
use App\Models\Equipamento;
use App\Models\Empresa;
use App\Models\Endereco;
use App\Models\OrdemServico;
use App\Models\OrdemServicoAnexo;
use App\Models\OrdemServicoLog;
use App\Models\OrdemServicoProduto;
use App\Models\OrdemServicoServico;
use App\Models\Produto;
use App\Models\Servico;
use App\Models\MovimentacaoFinanceira;
use App\Models\PedidoVenda;
use App\Models\PedidoVendaItem;

class MosSqlImportService
{
    private ?int $contaBancariaImportacaoId = null;
    private int $conciliacoesReceber = 0;
    private int $conciliacoesPagar = 0;
    private bool $usarLancamentosComoFonteFinanceira = false;
    /** @var array<string, int|null> */
    private array $formaPagamentoCache = [];

    /**
     * Importa um dump SQL do sistema externo (contrato M-OS / legado) para entidades equivalentes no LivreOS.
     *
     * @param  callable(string):void|null  $log
     * @param  bool  $allowEmptySql  true para partes só com binários (SQL vazio ou só comentários)
     * @param  string|null  $multipartExportId  export_id (v2) para acumular itens de venda / nomes de produtos entre partes ZIP
     * @return array<string, int>
     */
    public function importFromSqlPath(string $path, ?callable $log = null, bool $allowEmptySql = false, ?string $multipartExportId = null): array
    {
        $logFn = $log ?? static fn () => null;
        $userId = auth()->id();
        $multipartExportId = $multipartExportId !== null ? trim($multipartExportId) : '';
        $multipartExportId = $multipartExportId !== '' ? $multipartExportId : null;
        /** @var array{produtos_venda: list<array<string, mixed>>, produto_nome_por_id: array<int, string>} $accPrev */
        $accPrev = $multipartExportId !== null
            ? app(MosImportMultipartAccumulator::class)->load($multipartExportId)
            : ['produtos_venda' => [], 'produto_nome_por_id' => []];

        $counts = [
            'empresa' => 0,
            'clientes' => 0,
            'contatos' => 0,
            'enderecos' => 0,
            'documentos' => 0,
            'equipamentos' => 0,
            'equipamentos_os' => 0,
            'produtos' => 0,
            'servicos' => 0,
            'ordens_servico' => 0,
            'os_produtos' => 0,
            'os_servicos' => 0,
            'contas_receber' => 0,
            'contas_pagar' => 0,
            'pedidos_venda' => 0,
            'vendas' => 0,
            'cobrancas' => 0,
            'anotacoes_os_logs' => 0,
            'logs_os' => 0,
            'conta_receber_anexos' => 0,
            'conta_pagar_anexos' => 0,
            'ordem_servico_anexos' => 0,
            'conciliacoes_receber' => 0,
            'conciliacoes_pagar' => 0,
            'linhas_lidas' => 0,
            'linhas_ignoradas' => 0,
            'linhas_com_erro' => 0,
        ];
        $this->contaBancariaImportacaoId = null;
        $this->conciliacoesReceber = 0;
        $this->conciliacoesPagar = 0;
        $this->usarLancamentosComoFonteFinanceira = false;
        $this->formaPagamentoCache = [];

        /** @var array<int, int> $clienteIdMap */
        $clienteIdMap = [];
        /** @var array<string, int> $clienteNameMap */
        $clienteNameMap = [];
        /** @var array<int, int> $produtoIdMap */
        $produtoIdMap = [];
        /** @var array<int, int> $servicoIdMap */
        $servicoIdMap = [];
        /** @var array<int, int> $osIdMap */
        $osIdMap = [];
        /** @var array<int, int> $osIdByLancamentoMap */
        $osIdByLancamentoMap = [];
        /** @var array<int, int> $equipamentoIdMap */
        $equipamentoIdMap = [];

        $supportedTables = $this->supportedLegacyDumpTableNames();

        /** @var array<string, array<int, array<string,mixed>>> $tableRows */
        $tableRows = [];
        foreach ($supportedTables as $table) {
            $tableRows[$table] = [];
        }

        $dumpRowsFound = false;
        $logFn('[' . date('H:i:s') . '] Lendo dump do export (ficheiro em streaming; adequado a SQL grande)...');

        foreach ($this->iterateStatementsFromPath($path) as $statement) {
            $parsed = $this->parseInsertStatement($statement);
            if ($parsed === null) {
                continue;
            }

            $table = $parsed['table'];
            if (!$this->isSupportedLegacyDumpTable($table)) {
                continue;
            }

            $dumpRowsFound = true;
            foreach ($parsed['rows'] as $row) {
                $tableRows[$table][] = $row;
            }
        }

        /** Linhas de itens de venda acumuladas nas partes anteriores (export v2) + desta execução. */
        $produtosVendaRowsMerged = array_merge($accPrev['produtos_venda'], $tableRows['produtos_venda']);

        $summaryParts = [];
        foreach ($supportedTables as $t) {
            $c = count($tableRows[$t]);
            if ($c > 0) {
                $summaryParts[] = $t . '=' . $c;
            }
        }
        if ($summaryParts !== []) {
            $logFn('[' . date('H:i:s') . '] INSERTs reconhecidos por tabela: ' . implode(', ', $summaryParts));
        }

        if (!$dumpRowsFound) {
            if ($allowEmptySql) {
                $logFn('[' . date('H:i:s') . '] Nenhum INSERT reconhecido nesta parte (SQL vazio/stub) — a ignorar dados, seguindo para manifest se existir.');
                $logFn('[' . date('H:i:s') . '] Importação (sem dados SQL nesta parte) finalizada.');

                return $counts;
            }
            throw new \RuntimeException(
                'Não foram encontradas instruções INSERT das tabelas esperadas do export legado (emitente, clientes, documentos, equipamentos, equipamentos_os, produtos, servicos, os, produtos_os, servicos_os, lancamentos, vendas, produtos_venda/itens_de_vendas, cobrancas, anotacoes_os, logs, anexos).'
            );
        }

        if (!empty($tableRows['emitente'])) {
            try {
                if ($this->importEmpresa($tableRows['emitente'][0])) {
                    $counts['empresa']++;
                }
            } catch (\Throwable $e) {
                $counts['linhas_com_erro']++;
                $logFn('  [ERRO] Empresa: ' . $e->getMessage());
            }
        }

        foreach ($tableRows['clientes'] as $row) {
            $counts['linhas_lidas']++;
            try {
                $imported = $this->importCliente($row, $clienteNameMap);
                if ($imported === null) {
                    $counts['linhas_ignoradas']++;
                    continue;
                }

                $counts['clientes']++;
                if ($imported['contato']) {
                    $counts['contatos']++;
                }
                if ($imported['endereco']) {
                    $counts['enderecos']++;
                }

                $sourceId = $this->toInt($this->first($row, ['idclientes', 'idcliente', 'id']));
                if ($sourceId !== null) {
                    $clienteIdMap[$sourceId] = $imported['cliente_id'];
                }
            } catch (\Throwable $e) {
                $counts['linhas_com_erro']++;
                $logFn('  [ERRO] Cliente ignorado: ' . $e->getMessage());
            }
        }

        foreach ($tableRows['documentos'] as $row) {
            $counts['linhas_lidas']++;
            try {
                if ($this->importDocumento($row, $clienteNameMap)) {
                    $counts['documentos']++;
                } else {
                    $counts['linhas_ignoradas']++;
                }
            } catch (\Throwable $e) {
                $counts['linhas_com_erro']++;
                $logFn('  [ERRO] Documento ignorado: ' . $e->getMessage());
            }
        }

        foreach ($tableRows['equipamentos'] as $row) {
            $counts['linhas_lidas']++;
            try {
                $imported = $this->importEquipamento($row, $clienteIdMap, $clienteNameMap);
                if ($imported === null) {
                    $counts['linhas_ignoradas']++;
                    continue;
                }
                $counts['equipamentos']++;
                $equipamentoIdMap[$imported['mos_source_id']] = $imported['equipamento_id'];
            } catch (\Throwable $e) {
                $counts['linhas_com_erro']++;
                $logFn('  [ERRO] Equipamento ignorado: ' . $e->getMessage());
            }
        }

        foreach ($tableRows['produtos'] as $row) {
            $counts['linhas_lidas']++;
            try {
                $produtoId = $this->importProduto($row, $userId);
                if ($produtoId === null) {
                    $counts['linhas_ignoradas']++;
                    continue;
                }
                $counts['produtos']++;
                $sourceId = $this->toInt($this->first($row, ['idprodutos', 'idproduto', 'id']));
                if ($sourceId !== null) {
                    $produtoIdMap[$sourceId] = $produtoId;
                }
            } catch (\Throwable $e) {
                $counts['linhas_com_erro']++;
                $logFn('  [ERRO] Produto ignorado: ' . $e->getMessage());
            }
        }

        foreach ($tableRows['servicos'] as $row) {
            $counts['linhas_lidas']++;
            try {
                $servicoId = $this->importServico($row, $userId);
                if ($servicoId === null) {
                    $counts['linhas_ignoradas']++;
                    continue;
                }
                $counts['servicos']++;
                $sourceId = $this->toInt($this->first($row, ['idservicos', 'idservico', 'id']));
                if ($sourceId !== null) {
                    $servicoIdMap[$sourceId] = $servicoId;
                }
            } catch (\Throwable $e) {
                $counts['linhas_com_erro']++;
                $logFn('  [ERRO] Serviço ignorado: ' . $e->getMessage());
            }
        }

        foreach ($tableRows['os'] as $row) {
            $counts['linhas_lidas']++;
            try {
                $imported = $this->importOrdemServico($row, $clienteIdMap, $userId);
                if ($imported === null) {
                    $counts['linhas_ignoradas']++;
                    continue;
                }
                $counts['ordens_servico']++;
                $osIdMap[$imported['source_os_id']] = $imported['ordem_servico_id'];
                if ($imported['source_lancamento_id'] !== null) {
                    $osIdByLancamentoMap[$imported['source_lancamento_id']] = $imported['ordem_servico_id'];
                }
            } catch (\Throwable $e) {
                $counts['linhas_com_erro']++;
                $logFn('  [ERRO] OS ignorada: ' . $e->getMessage());
            }
        }

        // Após importar todas as OS com IDs forçados, sincroniza o auto-increment do banco
        $this->resetOrdemServicoAutoIncrement();

        $this->mergeLegacyAnotacoesIntoObservacoesInternas($tableRows['anotacoes_os'], $osIdMap, $logFn, $counts);

        foreach ($tableRows['produtos_os'] as $row) {
            $counts['linhas_lidas']++;
            try {
                if ($this->importOrdemServicoProduto($row, $osIdMap, $produtoIdMap)) {
                    $counts['os_produtos']++;
                } else {
                    $counts['linhas_ignoradas']++;
                }
            } catch (\Throwable $e) {
                $counts['linhas_com_erro']++;
                $logFn('  [ERRO] Item de produto da OS ignorado: ' . $e->getMessage());
            }
        }

        foreach ($tableRows['servicos_os'] as $row) {
            $counts['linhas_lidas']++;
            try {
                if ($this->importOrdemServicoServico($row, $osIdMap, $servicoIdMap)) {
                    $counts['os_servicos']++;
                } else {
                    $counts['linhas_ignoradas']++;
                }
            } catch (\Throwable $e) {
                $counts['linhas_com_erro']++;
                $logFn('  [ERRO] Item de serviço da OS ignorado: ' . $e->getMessage());
            }
        }

        foreach ($tableRows['lancamentos'] as $row) {
            $counts['linhas_lidas']++;
            try {
                $financeiro = $this->importLancamentoFinanceiro(
                    $row,
                    $clienteIdMap,
                    $clienteNameMap,
                    $osIdMap,
                    $osIdByLancamentoMap,
                    $userId
                );

                if ($financeiro === null) {
                    $counts['linhas_ignoradas']++;
                    continue;
                }

                if ($financeiro['tipo'] === 'receber') {
                    $counts['contas_receber']++;
                    if ($financeiro['anexo']) {
                        $counts['conta_receber_anexos']++;
                    }
                } else {
                    $counts['contas_pagar']++;
                    if ($financeiro['anexo']) {
                        $counts['conta_pagar_anexos']++;
                    }
                }
            } catch (\Throwable $e) {
                $counts['linhas_com_erro']++;
                $logFn('  [ERRO] Lançamento financeiro ignorado: ' . $e->getMessage());
            }
        }

        // Quando há lançamentos no dump, eles já representam o financeiro consolidado
        // (receitas/despesas), então evitamos importar vendas como contas a receber para não duplicar.
        // As vendas passam a ser importadas sempre como pedidos de venda; o financeiro continua só pelos lançamentos.
        $this->usarLancamentosComoFonteFinanceira = !empty($tableRows['lancamentos']);

        /** @var array<int, string> $dumpProdutoNomePorId nomes/descrições dos produtos no dump de origem (id → texto) */
        $dumpProdutoNomePorId = array_replace(
            $accPrev['produto_nome_por_id'],
            $this->buildDumpProdutoNomePorId($tableRows['produtos'] ?? [])
        );
        /** @var array<int, string> $dumpServicoNomePorId nomes dos serviços no dump de origem (id → texto) */
        $dumpServicoNomePorId = $this->buildDumpServicoNomePorId($tableRows['servicos'] ?? []);

        /** @var array<int, list<array<string, mixed>>> $itensVendaPorVendaId */
        $itensVendaPorVendaId = $this->indexProdutosVendaPorVendaId($produtosVendaRowsMerged);
        /** @var array<int, list<array<string, mixed>>> $itensFallbackPorVendaId */
        $itensFallbackPorVendaId = $this->indexProdutosOsPorOsId($tableRows['produtos_os'] ?? []);
        $itensFallbackPorVendaId = $this->mergeItensPorVendaId(
            $itensFallbackPorVendaId,
            $this->indexServicosOsPorOsId($tableRows['servicos_os'] ?? [])
        );

        foreach ($tableRows['anexos'] as $row) {
            $counts['linhas_lidas']++;
            try {
                if ($this->importOrdemServicoAnexo($row, $osIdMap, $userId)) {
                    $counts['ordem_servico_anexos']++;
                } else {
                    $counts['linhas_ignoradas']++;
                }
            } catch (\Throwable $e) {
                $counts['linhas_com_erro']++;
                $logFn('  [ERRO] Anexo da OS ignorado: ' . $e->getMessage());
            }
        }

        foreach ($tableRows['equipamentos_os'] as $row) {
            $counts['linhas_lidas']++;
            try {
                if ($this->importEquipamentoOs($row, $osIdMap, $equipamentoIdMap)) {
                    $counts['equipamentos_os']++;
                } else {
                    $counts['linhas_ignoradas']++;
                }
            } catch (\Throwable $e) {
                $counts['linhas_com_erro']++;
                $logFn('  [ERRO] Vínculo equipamento/OS ignorado: ' . $e->getMessage());
            }
        }

        foreach ($tableRows['vendas'] as $row) {
            $counts['linhas_lidas']++;
            try {
                $sourceVendaId = $this->toInt($this->first($row, ['idvendas', 'id']));
                $pedidoRes = $this->importVendaComoPedido(
                    $row,
                    $clienteIdMap,
                    $produtoIdMap,
                    $servicoIdMap,
                    $itensVendaPorVendaId,
                    $itensFallbackPorVendaId,
                    $dumpProdutoNomePorId,
                    $dumpServicoNomePorId,
                    $userId,
                    $logFn
                );
                if ($pedidoRes['created']) {
                    $counts['pedidos_venda']++;
                }
                $pedidoVenda = $pedidoRes['pedido'];

                $financeiroImportado = false;
                if (!$this->usarLancamentosComoFonteFinanceira) {
                    $financeiroImportado = $this->importVendaFinanceiro($row, $clienteIdMap, $osIdByLancamentoMap, $userId, $pedidoVenda);
                    if ($financeiroImportado) {
                        $counts['vendas']++;
                    }
                }

                if (!$financeiroImportado && !$this->usarLancamentosComoFonteFinanceira) {
                    $counts['linhas_ignoradas']++;
                }
                if ($this->usarLancamentosComoFonteFinanceira && $pedidoVenda === null) {
                    $counts['linhas_ignoradas']++;
                }

                if ($sourceVendaId !== null) {
                    $this->aplicarFinanceiroGeradoPedidoVendaMos($sourceVendaId, $pedidoVenda, $this->usarLancamentosComoFonteFinanceira);
                }
            } catch (\Throwable $e) {
                $counts['linhas_com_erro']++;
                $logFn('  [ERRO] Venda ignorada: ' . $e->getMessage());
            }
        }

        $nSinc = $this->sincronizarPedidosVendaItensDumpMos(
            $itensVendaPorVendaId,
            $itensFallbackPorVendaId,
            $produtoIdMap,
            $servicoIdMap,
            $dumpProdutoNomePorId,
            $dumpServicoNomePorId,
            $userId,
            $logFn
        );
        if ($nSinc > 0) {
            $logFn('[' . date('H:i:s') . '] Pedidos (export): ' . $nSinc . ' atualizado(s) com itens reais (linha sintética substituída).');
        }

        foreach ($tableRows['cobrancas'] as $row) {
            $counts['linhas_lidas']++;
            try {
                if ($this->importCobrancaFinanceiro($row, $clienteIdMap, $osIdMap, $userId)) {
                    $counts['cobrancas']++;
                } else {
                    $counts['linhas_ignoradas']++;
                }
            } catch (\Throwable $e) {
                $counts['linhas_com_erro']++;
                $logFn('  [ERRO] Cobrança ignorada: ' . $e->getMessage());
            }
        }

        foreach ($tableRows['logs'] as $row) {
            $counts['linhas_lidas']++;
            try {
                if ($this->importLegacySystemLogOs($row, $osIdMap, $userId)) {
                    $counts['logs_os']++;
                } else {
                    $counts['linhas_ignoradas']++;
                }
            } catch (\Throwable $e) {
                $counts['linhas_com_erro']++;
                $logFn('  [ERRO] Log de origem ignorado: ' . $e->getMessage());
            }
        }

        $counts['conciliacoes_receber'] = $this->conciliacoesReceber;
        $counts['conciliacoes_pagar'] = $this->conciliacoesPagar;

        if ($multipartExportId !== null) {
            app(MosImportMultipartAccumulator::class)->save($multipartExportId, [
                'produtos_venda' => $produtosVendaRowsMerged,
                'produto_nome_por_id' => $dumpProdutoNomePorId,
            ]);
        }

        $logFn('[' . date('H:i:s') . '] Importação finalizada.');
        return $counts;
    }

    protected function importEmpresa(array $row): bool
    {
        $nome = $this->limit($this->first($row, ['nome']), 200);
        if ($nome === null) {
            return false;
        }

        $empresa = Empresa::query()->first() ?? new Empresa();
        $empresa->nome = $nome;
        $empresa->razao_social = $this->limit($this->first($row, ['nome']), 200);
        $empresa->cnpj = $this->limit($this->first($row, ['cnpj']), 20);
        $empresa->inscricao_estadual = $this->limit($this->first($row, ['ie']), 50);
        $empresa->email = $this->limit($this->first($row, ['email']), 150);
        $empresa->telefone = $this->limit($this->first($row, ['telefone']), 30);
        $empresa->cep = $this->limit($this->first($row, ['cep']), 15);
        $empresa->logradouro = $this->limit($this->first($row, ['rua']), 200);
        $empresa->numero = $this->limit($this->first($row, ['numero']), 30);
        $empresa->bairro = $this->limit($this->first($row, ['bairro']), 100);
        $empresa->cidade = $this->limit($this->first($row, ['cidade']), 120);
        $empresa->estado = $this->limit($this->first($row, ['uf']), 60);
        $empresa->pais = $empresa->pais ?: 'Brasil';

        $urlLogo = $this->first($row, ['url_logo']);
        $observacoes = trim((string) ($empresa->observacoes ?? ''));
        if ($urlLogo !== null && $urlLogo !== '') {
            $nota = 'Logo externa (origem): ' . $urlLogo;
            if (!str_contains($observacoes, $nota)) {
                $observacoes = trim($observacoes . "\n" . $nota);
            }
        }
        $empresa->observacoes = $observacoes !== '' ? $observacoes : null;
        $empresa->save();

        return true;
    }

    /**
     * @param  array<string, int>  $clienteNameMap
     * @return array{cliente_id:int, contato:bool, endereco:bool}|null
     */
    protected function importCliente(array $row, array &$clienteNameMap): ?array
    {
        $sourceClienteId = $this->toInt($this->first($row, ['idclientes', 'idcliente', 'id']));
        $nome = trim((string) ($this->first($row, ['nomecliente', 'nome', 'cliente']) ?? ''));
        if ($nome === '') {
            $nome = $sourceClienteId !== null
                ? ('Cliente (origem M-OS) #' . $sourceClienteId)
                : 'Cliente importado (M-OS)';
        }

        $documentoRaw = trim((string) ($this->first($row, ['documento', 'cpf', 'cnpj']) ?? ''));
        $tipoPessoa = 'F';
        $cpf = null;
        $cnpj = null;

        if ($documentoRaw !== '') {
            // Preparado para CNPJ alfanumérico: mantém letras (sem máscara), em uppercase.
            $documentoAlfaNum = preg_replace('/[^0-9A-Za-z]/', '', (string) $documentoRaw) ?? '';
            $temLetras = preg_match('/[A-Za-z]/', $documentoAlfaNum) === 1;

            if ($temLetras) {
                $tipoPessoa = 'J';
                $cnpj = strtoupper($documentoAlfaNum);
            } else {
                $documentoDigits = preg_replace('/\D+/', '', (string) $documentoRaw) ?? '';
                if ($documentoDigits !== '') {
                    if (strlen($documentoDigits) > 11) {
                        $tipoPessoa = 'J';
                        $cnpjDigits = substr($documentoDigits, 0, 14);
                        if (strlen($cnpjDigits) >= 14) {
                            $cnpj = substr($cnpjDigits, 0, 2) . '.' .
                                substr($cnpjDigits, 2, 3) . '.' .
                                substr($cnpjDigits, 5, 3) . '/' .
                                substr($cnpjDigits, 8, 4) . '-' .
                                substr($cnpjDigits, 12, 2);
                        } else {
                            $cnpj = $cnpjDigits; // incompleto: salva sem máscara
                        }
                    } else {
                        $cpfDigits = substr($documentoDigits, 0, 11);
                        if (strlen($cpfDigits) >= 11) {
                            $cpf = substr($cpfDigits, 0, 3) . '.' .
                                substr($cpfDigits, 3, 3) . '.' .
                                substr($cpfDigits, 6, 3) . '-' .
                                substr($cpfDigits, 9, 2);
                        } else {
                            $cpf = $cpfDigits; // incompleto: salva sem máscara
                        }
                    }
                }
            }
        }

        $isFornecedor = $this->toBool($this->first($row, ['fornecedor'])) ?? false;

        if ($sourceClienteId !== null) {
            $existingByMarker = Cliente::query()
                ->where('observacoes', 'like', '%[MOS_CLIENTE_ID:' . $sourceClienteId . ']%')
                ->first();
            if ($existingByMarker !== null) {
                $normalizedName = $this->normalizeName($nome);
                if ($normalizedName !== '') {
                    $clienteNameMap[$normalizedName] = (int) $existingByMarker->id;
                }

                $contatoCriado = false;
                $telefone = $this->first($row, ['telefone']);
                $celular = $this->first($row, ['celular']);
                $email = $this->first($row, ['email']);
                $nomeContato = $this->first($row, ['contato']);
                $temContato = $telefone !== null || $celular !== null || $email !== null || $nomeContato !== null;
                if ($temContato) {
                    $contatoExistente = Contato::query()->where('cliente_id', $existingByMarker->id)->first();
                    if ($contatoExistente === null) {
                        Contato::create([
                            'cliente_id' => $existingByMarker->id,
                            'nome' => $this->limit($nomeContato ?? $nome, 100),
                            'telefone' => $this->limit($telefone, 30),
                            'telefone2' => $this->limit($celular, 30),
                            'email' => $this->limit($email, 150),
                            'principal' => true,
                            'cobranca' => true,
                            'is_fornecedor' => $isFornecedor,
                        ]);
                        $contatoCriado = true;
                    }
                }

                $enderecoCriado = false;
                $logradouro = $this->first($row, ['rua']);
                $numero = $this->first($row, ['numero']);
                $bairro = $this->first($row, ['bairro']);
                $cidade = $this->first($row, ['cidade']);
                $estado = $this->first($row, ['estado', 'uf']);
                $cep = $this->first($row, ['cep']);
                $complemento = $this->first($row, ['complemento']);
                $temEndereco = $logradouro !== null || $numero !== null || $bairro !== null || $cidade !== null || $estado !== null || $cep !== null;
                if ($temEndereco) {
                    $enderecoExistente = Endereco::query()->where('cliente_id', $existingByMarker->id)->first();
                    if ($enderecoExistente === null) {
                        Endereco::create([
                            'cliente_id' => $existingByMarker->id,
                            'cep' => $this->limit($cep, 15),
                            'logradouro' => $this->limit($logradouro, 200),
                            'numero' => $this->limit($numero, 30),
                            'complemento' => $this->limit($complemento, 100),
                            'bairro' => $this->limit($bairro, 100),
                            'cidade' => $this->limit($cidade, 120),
                            'estado' => $this->limit($estado, 60),
                            'pais' => 'Brasil',
                            'principal' => true,
                            'cobranca' => true,
                            'entrega' => true,
                        ]);
                        $enderecoCriado = true;
                    }
                }

                // Atualiza CPF/CNPJ do cliente existente (marker) com base nos dados do MapOS.
                $atualizado = false;
                if ($cpf !== null && ($existingByMarker->cpf === null || (string) $existingByMarker->cpf !== (string) $cpf)) {
                    $existingByMarker->cpf = $cpf;
                    $atualizado = true;
                }
                if ($cnpj !== null && ($existingByMarker->cnpj === null || (string) $existingByMarker->cnpj !== (string) $cnpj)) {
                    $existingByMarker->cnpj = $cnpj;
                    $atualizado = true;
                }
                if (($existingByMarker->tipo_pessoa ?? null) !== $tipoPessoa) {
                    $existingByMarker->tipo_pessoa = $tipoPessoa;
                    $atualizado = true;
                }
                if ($atualizado) {
                    $existingByMarker->save();
                }

                return [
                    'cliente_id' => (int) $existingByMarker->id,
                    'contato' => $contatoCriado,
                    'endereco' => $enderecoCriado,
                ];
            }
        }

        $observacoes = $this->buildClienteObservacao($row, $isFornecedor);
        if ($sourceClienteId !== null) {
            $marker = '[MOS_CLIENTE_ID:' . $sourceClienteId . ']';
            $observacoes = $observacoes ? ($observacoes . "\n" . $marker) : $marker;
        }

        $cliente = Cliente::create([
            'tipo_pessoa' => $tipoPessoa,
            'pais_origem' => 'Brasil',
            'nome' => mb_substr($nome, 0, 100),
            'razao_social' => $tipoPessoa === 'J' ? mb_substr($nome, 0, 255) : null,
            'cpf' => $cpf,
            'cnpj' => $cnpj,
            'observacoes' => $observacoes,
        ]);

        $contatoCriado = false;
        $telefone = $this->first($row, ['telefone']);
        $celular = $this->first($row, ['celular']);
        $email = $this->first($row, ['email']);
        $nomeContato = $this->first($row, ['contato']);
        if ($telefone !== null || $celular !== null || $email !== null || $nomeContato !== null) {
            Contato::create([
                'cliente_id' => $cliente->id,
                'nome' => $this->limit($nomeContato ?? $nome, 100),
                'telefone' => $this->limit($telefone, 30),
                'telefone2' => $this->limit($celular, 30),
                'email' => $this->limit($email, 150),
                'principal' => true,
                'cobranca' => true,
                'is_fornecedor' => $isFornecedor,
            ]);
            $contatoCriado = true;
        }

        $enderecoCriado = false;
        $logradouro = $this->first($row, ['rua']);
        $numero = $this->first($row, ['numero']);
        $bairro = $this->first($row, ['bairro']);
        $cidade = $this->first($row, ['cidade']);
        $estado = $this->first($row, ['estado', 'uf']);
        $cep = $this->first($row, ['cep']);
        $complemento = $this->first($row, ['complemento']);
        if ($logradouro !== null || $numero !== null || $bairro !== null || $cidade !== null || $estado !== null || $cep !== null) {
            Endereco::create([
                'cliente_id' => $cliente->id,
                'cep' => $this->limit($cep, 15),
                'logradouro' => $this->limit($logradouro, 200),
                'numero' => $this->limit($numero, 30),
                'complemento' => $this->limit($complemento, 100),
                'bairro' => $this->limit($bairro, 100),
                'cidade' => $this->limit($cidade, 120),
                'estado' => $this->limit($estado, 60),
                'pais' => 'Brasil',
                'principal' => true,
                'cobranca' => true,
                'entrega' => true,
            ]);
            $enderecoCriado = true;
        }

        $normalizedName = $this->normalizeName($nome);
        if ($normalizedName !== '') {
            $clienteNameMap[$normalizedName] = (int) $cliente->id;
        }

        return [
            'cliente_id' => (int) $cliente->id,
            'contato' => $contatoCriado,
            'endereco' => $enderecoCriado,
        ];
    }

    protected function importProduto(array $row, ?int $userId): ?int
    {
        $sourceId = $this->toInt($this->first($row, ['idprodutos', 'idproduto', 'id']));
        $nome = trim((string) ($this->first($row, ['descricao', 'nome', 'produto']) ?? ''));
        if ($nome === '') {
            $nome = $sourceId !== null ? ('Produto (origem M-OS) #' . $sourceId) : 'Produto importado (M-OS)';
        }
        $sku = $sourceId !== null ? 'MOS-PROD-' . $sourceId : null;
        if ($sku !== null) {
            $existing = Produto::query()->where('codigo_sku', $sku)->first();
            if ($existing !== null) {
                return (int) $existing->id;
            }
        }

        $produto = Produto::create([
            'nome' => mb_substr($nome, 0, 255),
            'codigo_sku' => $this->limit($sku, 100),
            'preco_venda' => $this->toDecimal($this->first($row, ['precovenda', 'preco', 'valor', 'valorvenda'])),
            'unidade' => $this->limit($this->first($row, ['unidade']) ?? 'UN', 30) ?: 'UN',
            'formato' => 'simples',
            'condicao' => 'novo',
            'observacoes' => $this->buildProdutoObservacao($row),
            'estoque_quantidade' => $this->normalizeProdutoEstoqueQuantidade(
                $this->toDecimal($this->first($row, ['estoque', 'quantidade']))
            ),
            'estoque_preco_compra_unitario' => $this->toDecimal($this->first($row, ['precocompra', 'valorcompra'])),
            'estoque_preco_custo_unitario' => $this->toDecimal($this->first($row, ['precocompra', 'valorcompra'])),
            'created_by' => $userId,
            'updated_by' => $userId,
        ]);

        return (int) $produto->id;
    }

    /**
     * @param  array<string, int>  $clienteNameMap
     */
    protected function importDocumento(array $row, array &$clienteNameMap): bool
    {
        $sourceDocId = $this->toInt($this->first($row, ['iddocumentos', 'id']));
        $file = $this->limit($this->first($row, ['file']), 255);
        $titulo = $this->limit($this->first($row, ['documento']), 255);
        if ($file === null && $titulo === null) {
            return false;
        }

        $nomeBase = $file ?? $titulo;
        $path = trim((string) ($this->first($row, ['path']) ?? ''));
        $url = trim((string) ($this->first($row, ['url']) ?? ''));
        $caminho = $path !== '' ? $path : ($url !== '' ? $url : (string) $nomeBase);

        $clienteId = $this->resolveOrCreateClienteByName('(M-OS) Documentos gerais', $clienteNameMap, false);
        if ($clienteId === null) {
            return false;
        }

        if ($sourceDocId !== null) {
            $existsByMarker = Documento::query()
                ->where('cliente_id', $clienteId)
                ->where('descricao', 'like', '%[MOS_DOCUMENTO_ID:' . $sourceDocId . ']%')
                ->exists();
            if ($existsByMarker) {
                return true;
            }
        }

        $descricao = $this->limit($this->first($row, ['descricao']), 65535);
        if ($url !== '') {
            $extra = 'URL origem: ' . $url;
            $descricao = $descricao ? ($descricao . "\n" . $extra) : $extra;
        }
        if ($sourceDocId !== null) {
            $marker = '[MOS_DOCUMENTO_ID:' . $sourceDocId . ']';
            $descricao = $descricao ? ($descricao . "\n" . $marker) : $marker;
        }

        $tamanhoRaw = $this->toDecimal($this->first($row, ['tamanho']));
        $tamanho = $tamanhoRaw !== null ? (int) round($tamanhoRaw * 1024) : 0;
        if ($tamanho < 0) {
            $tamanho = 0;
        }

        Documento::create([
            'cliente_id' => $clienteId,
            'nome_arquivo' => $nomeBase,
            'caminho_arquivo' => mb_substr($caminho, 0, 500),
            'tipo_mime' => $this->guessMimeFromFilename((string) $nomeBase) ?? 'application/octet-stream',
            'tamanho' => $tamanho,
            'categoria' => $this->limit($this->first($row, ['categoria']), 100),
            'descricao' => $descricao,
        ]);

        return true;
    }

    /**
     * @param  array<int, int>  $clienteIdMap
     * @param  array<string, int>  $clienteNameMap
     * @return array{mos_source_id:int,equipamento_id:int}|null
     */
    protected function importEquipamento(array $row, array $clienteIdMap, array &$clienteNameMap): ?array
    {
        $sourceRowId = $this->toInt($this->first($row, ['idequipamentos', 'id']));
        if ($sourceRowId === null) {
            return null;
        }

        $clienteOrigemId = $this->toInt($this->first($row, ['clientes_id']));
        $clienteId = $clienteOrigemId !== null ? ($clienteIdMap[$clienteOrigemId] ?? null) : null;
        if ($clienteId === null) {
            $clienteId = $this->resolveOrCreateClienteByName('(M-OS) Equipamentos sem cliente', $clienteNameMap, false);
        }
        if ($clienteId === null) {
            return null;
        }

        $existing = Equipamento::query()
            ->where('cliente_id', $clienteId)
            ->where('acessorios', 'like', '%[MOS_EQUIP_ID:' . $sourceRowId . ']%')
            ->first();
        if ($existing !== null) {
            return [
                'mos_source_id' => $sourceRowId,
                'equipamento_id' => (int) $existing->id,
            ];
        }

        $equipamentoNome = $this->limit($this->first($row, ['equipamento']), 150);
        $acessorios = [];
        if ($equipamentoNome !== null) {
            $acessorios[] = 'Equipamento: ' . $equipamentoNome;
        }
        foreach (['cor', 'descricao', 'tensao', 'potencia', 'voltagem'] as $campo) {
            $val = $this->limit($this->first($row, [$campo]), 255);
            if ($val !== null) {
                $acessorios[] = ucfirst($campo) . ': ' . $val;
            }
        }
        $dataFab = $this->toDate($this->first($row, ['data_fabricacao']));
        if ($dataFab !== null) {
            $acessorios[] = 'Data fabricação: ' . $dataFab;
        }
        $acessorios[] = '[MOS_EQUIP_ID:' . $sourceRowId . ']';

        $equipamento = Equipamento::create([
            'cliente_id' => $clienteId,
            'marca' => $this->limit($this->first($row, ['marca']), 100),
            'modelo' => $this->limit($this->first($row, ['modelo']), 100),
            'numero_serie' => $this->limit($this->first($row, ['num_serie', 'numero_serie']), 100),
            'acessorios' => empty($acessorios) ? null : implode("\n", $acessorios),
        ]);

        return [
            'mos_source_id' => $sourceRowId,
            'equipamento_id' => (int) $equipamento->id,
        ];
    }

    protected function importServico(array $row, ?int $userId): ?int
    {
        $nome = trim((string) ($this->first($row, ['nome', 'descricao', 'servico']) ?? ''));
        if ($nome === '') {
            return null;
        }

        $sourceId = $this->toInt($this->first($row, ['idservicos', 'idservico', 'id']));
        $codigo = $sourceId !== null ? 'MOS-SRV-' . $sourceId : null;
        if ($codigo !== null) {
            $existing = Servico::query()->where('codigo_sku', $codigo)->first();
            if ($existing !== null) {
                return (int) $existing->id;
            }
        }

        $servico = Servico::create([
            'codigo_sku' => $this->limit($codigo, 100),
            'nome' => mb_substr($nome, 0, 255),
            'preco' => $this->toDecimal($this->first($row, ['preco', 'valor'])),
            'unidade' => $this->limit($this->first($row, ['unidade']) ?? 'UN', 50) ?: 'UN',
            'descricao' => $this->first($row, ['descricao']),
            'created_by' => $userId,
            'updated_by' => $userId,
        ]);

        return (int) $servico->id;
    }

    /**
     * @param  array<int, int>  $clienteIdMap
     * @return array{source_os_id:int, ordem_servico_id:int, source_lancamento_id:?int}|null
     */
    protected function importOrdemServico(array $row, array $clienteIdMap, ?int $userId): ?array
    {
        $sourceOsId = $this->toInt($this->first($row, ['idos', 'id_os', 'id']));
        if ($sourceOsId === null) {
            return null;
        }

        $clienteOrigemId = $this->toInt($this->first($row, ['clientes_id', 'idclientes', 'cliente_id']));
        $clienteId = $clienteOrigemId !== null ? ($clienteIdMap[$clienteOrigemId] ?? null) : null;
        if ($clienteId === null) {
            return null;
        }

        $valorTotal = $this->toDecimal($this->first($row, ['valortotal', 'valor_total'])) ?? 0.0;
        $valorDesconto = $this->toDecimal($this->first($row, ['valor_desconto'])) ?? 0.0;
        $desconto = $this->toDecimal($this->first($row, ['desconto'])) ?? 0.0;
        $totalGeral = $valorDesconto > 0 ? $valorDesconto : $valorTotal;
        $status = $this->normalizeOsStatus($this->first($row, ['status']) ?? 'Aberta');
        $dataAbertura = $this->toDateTime($this->first($row, ['datainicial', 'data_inicial', 'dataabertura']));
        $dataConclusao = $this->toDateTime($this->first($row, ['datafinal', 'data_final', 'datafinalizado']));
        $isEncerrada = in_array($status, ['Encerrada', 'Encerrado'], true);
        $marker = '[MOS_OS_ID:' . $sourceOsId . ']';
        $contatoId = Contato::query()
            ->where('cliente_id', $clienteId)
            ->orderByDesc('principal')
            ->orderBy('id')
            ->value('id');
        $enderecoId = Endereco::query()
            ->where('cliente_id', $clienteId)
            ->orderByDesc('principal')
            ->orderBy('id')
            ->value('id');

        $existente = OrdemServico::query()
            ->where(function ($q) use ($clienteId, $marker, $sourceOsId) {
                // Após a migração de IDs forçados, busca direta pelo id é a mais confiável
                $q->where('id', $sourceOsId)
                    // Compatibilidade com registros importados antes da migração de IDs
                    ->orWhere(function ($q2) use ($clienteId, $marker) {
                        $q2->where('cliente_id', $clienteId)
                            ->where(function ($q3) use ($marker) {
                                $q3->where('observacoes_internas', 'like', '%' . $marker . '%')
                                    ->orWhere('observacoes_cliente', 'like', '%' . $marker . '%');
                            });
                    });
            })
            ->first();
        if ($existente !== null) {
            $codigoInternoGerado = $this->buildCodigoInternoMos($sourceOsId);
            $updates = [];
            if ((int) ($existente->numero_sequencial ?? 0) !== $sourceOsId) {
                $updates['numero_sequencial'] = $sourceOsId;
            }
            if (($existente->codigo_interno ?? '') !== $codigoInternoGerado) {
                $updates['codigo_interno'] = $codigoInternoGerado;
            }
            if (empty($existente->contato_id) && $contatoId !== null) {
                $updates['contato_id'] = $contatoId;
            }
            if (empty($existente->endereco_id) && $enderecoId !== null) {
                $updates['endereco_id'] = $enderecoId;
            }
            if (($existente->status ?? null) !== $status) {
                $updates['status'] = $status;
            }
            if (empty($existente->data_abertura) && $dataAbertura !== null) {
                $updates['data_abertura'] = $dataAbertura;
            }
            if (empty($existente->real_inicio) && $dataAbertura !== null) {
                $updates['real_inicio'] = $dataAbertura;
            }
            if (empty($existente->prevista_conclusao) && $dataConclusao !== null) {
                $updates['prevista_conclusao'] = $dataConclusao;
            }
            if ($isEncerrada && empty($existente->real_conclusao) && $dataConclusao !== null) {
                $updates['real_conclusao'] = $dataConclusao;
            }
            if (!empty($updates)) {
                $existente->fill($updates);
                $existente->save();
            }
            return [
                'source_os_id' => $sourceOsId,
                'ordem_servico_id' => (int) $existente->id,
                'source_lancamento_id' => $this->toInt($this->first($row, ['lancamento'])),
            ];
        }

        $obsInterna = trim((string) ($this->first($row, ['observacoes_internas']) ?? ''));
        $obsInterna = trim($obsInterna . "\n" . $marker);

        // Forçar id = $sourceOsId para que a URL reflita o número original no sistema de origem
        $ordemServico = new OrdemServico([
            'tenant_id' => auth()->user()?->tenant_id,
            'numero_sequencial' => $sourceOsId,
            'codigo_interno' => $this->buildCodigoInternoMos($sourceOsId),
            'cliente_id' => $clienteId,
            'contato_id' => $contatoId,
            'endereco_id' => $enderecoId,
            'status' => $status,
            'prioridade' => 'normal',
            'data_abertura' => $dataAbertura,
            'real_inicio' => $dataAbertura,
            'prevista_conclusao' => $dataConclusao,
            'real_conclusao' => $isEncerrada ? $dataConclusao : null,
            'relato_cliente' => $this->first($row, ['defeito']),
            'diagnostico_tecnico' => $this->first($row, ['laudotecnico']),
            'servicos_realizados' => $this->first($row, ['descricaoproduto', 'descricao_produto']),
            'observacoes_cliente' => $this->first($row, ['observacoes']),
            'observacoes_internas' => $obsInterna !== '' ? $obsInterna : $marker,
            'garantia_dias' => $this->toInt($this->first($row, ['garantia'])),
            'desconto_global' => $desconto,
            'total_descontos' => $desconto,
            'total_geral' => $totalGeral,
            'financeiro_gerado' => $this->toBool($this->first($row, ['faturado'])) ?? false,
            'created_by' => $userId,
            'updated_by' => $userId,
        ]);
        $ordemServico->id = $sourceOsId;
        $ordemServico->save();

        return [
            'source_os_id' => $sourceOsId,
            'ordem_servico_id' => $sourceOsId,
            'source_lancamento_id' => $this->toInt($this->first($row, ['lancamento'])),
        ];
    }

    protected function buildCodigoInternoMos(int $numero): string
    {
        return 'OS-' . str_pad((string) $numero, 6, '0', STR_PAD_LEFT);
    }

    /**
     * @param  array<int, int>  $osIdMap
     * @param  array<int, int>  $produtoIdMap
     */
    protected function importOrdemServicoProduto(array $row, array $osIdMap, array $produtoIdMap): bool
    {
        $sourceOsId = $this->toInt($this->first($row, ['os_id', 'idos', 'id_os']));
        $ordemServicoId = $sourceOsId !== null ? $this->resolveLivreOrdemServicoIdFromSource($sourceOsId, $osIdMap) : null;
        if ($ordemServicoId === null) {
            return false;
        }

        $dumpProdutoId = $this->toInt($this->first($row, ['produtos_id', 'produto_id']));
        $produtoId = $dumpProdutoId !== null ? ($produtoIdMap[$dumpProdutoId] ?? null) : null;
        $quantidade = $this->toDecimal($this->first($row, ['quantidade'])) ?? 1.0;
        if ($quantidade <= 0) {
            $quantidade = 1.0;
        }
        $precoItemOs = $this->toDecimal($this->first($row, ['preco', 'valor']));
        $subtotalItemOs = $this->toDecimal($this->first($row, ['subtotal', 'sub_total']));

        // Prioridade: valor digitado no item da OS na origem, não o cadastro.
        $valorUnitario = $precoItemOs;
        if ($valorUnitario === null && $subtotalItemOs !== null && $quantidade > 0) {
            $valorUnitario = $subtotalItemOs / $quantidade;
        }
        if ($valorUnitario === null && $produtoId !== null) {
            $valorUnitario = $this->toDecimal(Produto::query()->whereKey($produtoId)->value('preco_venda'));
        }
        $valorUnitario = (float) ($valorUnitario ?? 0.0);
        $total = (float) ($subtotalItemOs ?? ($quantidade * $valorUnitario));
        $descricao = $this->first($row, ['descricao']);

        $existente = OrdemServicoProduto::query()
            ->where('ordem_servico_id', $ordemServicoId)
            ->where('produto_id', $produtoId)
            ->where(function ($q) use ($descricao) {
                if ($descricao === null || $descricao === '') {
                    $q->whereNull('descricao')->orWhere('descricao', '');
                } else {
                    $q->where('descricao', $descricao);
                }
            })
            ->where('quantidade', $quantidade)
            ->first();
        if ($existente !== null) {
            $updates = [];
            if ((float) $existente->valor_unitario !== (float) $valorUnitario) {
                $updates['valor_unitario'] = $valorUnitario;
            }
            if ((float) $existente->total !== (float) $total) {
                $updates['total'] = $total;
            }
            if (!empty($updates)) {
                $existente->fill($updates);
                $existente->save();
            }
            return true;
        }

        OrdemServicoProduto::create([
            'ordem_servico_id' => $ordemServicoId,
            'produto_id' => $produtoId,
            'descricao' => $descricao,
            'quantidade' => $quantidade,
            'valor_unitario' => $valorUnitario,
            'total' => $total,
        ]);

        return true;
    }

    /**
     * @param  array<int, int>  $osIdMap
     * @param  array<int, int>  $servicoIdMap
     */
    protected function importOrdemServicoServico(array $row, array $osIdMap, array $servicoIdMap): bool
    {
        $sourceOsId = $this->toInt($this->first($row, ['os_id', 'idos', 'id_os']));
        $ordemServicoId = $sourceOsId !== null ? $this->resolveLivreOrdemServicoIdFromSource($sourceOsId, $osIdMap) : null;
        if ($ordemServicoId === null) {
            return false;
        }

        $dumpServicoId = $this->toInt($this->first($row, ['servicos_id', 'servico_id']));
        $servicoId = $dumpServicoId !== null ? ($servicoIdMap[$dumpServicoId] ?? null) : null;
        $quantidade = $this->toDecimal($this->first($row, ['quantidade'])) ?? 1.0;
        if ($quantidade <= 0) {
            $quantidade = 1.0;
        }
        $precoItemOs = $this->toDecimal($this->first($row, ['preco', 'valor']));
        $subtotalItemOs = $this->toDecimal($this->first($row, ['subtotal', 'sub_total']));

        // Prioridade: valor digitado no item da OS na origem, não o cadastro.
        $valorUnitario = $precoItemOs;
        if ($valorUnitario === null && $subtotalItemOs !== null && $quantidade > 0) {
            $valorUnitario = $subtotalItemOs / $quantidade;
        }
        if ($valorUnitario === null && $servicoId !== null) {
            $valorUnitario = $this->toDecimal(Servico::query()->whereKey($servicoId)->value('preco'));
        }
        $valorUnitario = (float) ($valorUnitario ?? 0.0);
        $total = (float) ($subtotalItemOs ?? ($quantidade * $valorUnitario));
        $descricao = $this->first($row, ['servico']);

        $existente = OrdemServicoServico::query()
            ->where('ordem_servico_id', $ordemServicoId)
            ->where('servico_id', $servicoId)
            ->where(function ($q) use ($descricao) {
                if ($descricao === null || $descricao === '') {
                    $q->whereNull('descricao')->orWhere('descricao', '');
                } else {
                    $q->where('descricao', $descricao);
                }
            })
            ->where('quantidade', $quantidade)
            ->first();
        if ($existente !== null) {
            $updates = [];
            if ((float) $existente->valor_unitario !== (float) $valorUnitario) {
                $updates['valor_unitario'] = $valorUnitario;
            }
            if ((float) $existente->total !== (float) $total) {
                $updates['total'] = $total;
            }
            if (!empty($updates)) {
                $existente->fill($updates);
                $existente->save();
            }
            return true;
        }

        OrdemServicoServico::create([
            'ordem_servico_id' => $ordemServicoId,
            'servico_id' => $servicoId,
            'descricao' => $descricao,
            'cobranca_tipo' => 'fixo',
            'quantidade' => $quantidade,
            'quantidade_horas' => $quantidade,
            'valor_unitario' => $valorUnitario,
            'total' => $total,
        ]);

        return true;
    }

    /**
     * @param  array<int, int>  $clienteIdMap
     * @param  array<string, int>  $clienteNameMap
     * @param  array<int, int>  $osIdMap
     * @param  array<int, int>  $osIdByLancamentoMap
     * @return array{tipo:'receber'|'pagar', anexo:bool}|null
     */
    protected function importLancamentoFinanceiro(
        array $row,
        array $clienteIdMap,
        array &$clienteNameMap,
        array $osIdMap,
        array $osIdByLancamentoMap,
        ?int $userId
    ): ?array {
        $tipoRaw = strtolower(trim((string) ($this->first($row, ['tipo']) ?? '')));
        $tipo = match (true) {
            str_contains($tipoRaw, 'desp') => 'despesa',
            str_contains($tipoRaw, 'sai') => 'despesa',
            str_contains($tipoRaw, 'rece') => 'receita',
            str_contains($tipoRaw, 'ent') => 'receita',
            default => 'receita',
        };

        $sourceRowId = $this->toInt($this->first($row, ['idlancamentos', 'id']));
        if ($sourceRowId === null) {
            return null;
        }
        $referencia = 'mos:lancamentos:' . $sourceRowId;
        if (
            ContaReceber::query()->where('referencia_externa', $referencia)->exists() ||
            ContaPagar::query()->where('referencia_externa', $referencia)->exists()
        ) {
            return null;
        }

        $descricao = $this->limit($this->first($row, ['descricao']), 255) ?? 'Lançamento importado (M-OS)';
        $observacoes = $this->first($row, ['observacoes']);
        $anexo = $this->first($row, ['anexo']);
        $formaPgto = $this->first($row, ['forma_pgto']);
        $formaPagamentoId = $this->resolveFormaPagamentoLegacyId($formaPgto);
        $dataVencimento = $this->toDate($this->first($row, ['data_vencimento']));
        $dataPagamento = $this->toDate($this->first($row, ['data_pagamento']));
        // Regra: usar estritamente a coluna "baixado" do export de origem.
        // baixado=0 -> pendente | baixado=1 -> pago/conciliado.
        $baixado = $this->toInt($this->first($row, ['baixado'])) ?? 0;
        $statusPago = $baixado === 1;

        $valorOriginal = abs((float) ($this->toDecimal($this->first($row, ['valor'])) ?? 0.0));
        // Em bases reais o campo valor_desconto pode vir inconsistente.
        // Para evitar perda indevida no financeiro importado, consideramos o valor bruto.
        $valorLiquido = $valorOriginal;
        $desconto = 0.0;

        $ordemServicoId = $osIdByLancamentoMap[$sourceRowId] ?? null;
        if ($ordemServicoId === null) {
            $candidatosTexto = [
                (string) $descricao,
                (string) ($this->first($row, ['observacoes']) ?? ''),
            ];
            foreach ($candidatosTexto as $texto) {
                $sourceOsIdFromTexto = $this->extractOsIdFromText($texto);
                if ($sourceOsIdFromTexto !== null) {
                    $ordemServicoId = $this->resolveLivreOrdemServicoIdFromSource($sourceOsIdFromTexto, $osIdMap);
                    if ($ordemServicoId !== null) {
                        break;
                    }
                }
            }
        }

        $clienteOrigemId = $this->toInt($this->first($row, ['clientes_id', 'cliente_id']));
        $clienteId = $clienteOrigemId !== null ? ($clienteIdMap[$clienteOrigemId] ?? null) : null;
        $nomeClienteFornecedor = $this->limit($this->first($row, ['cliente_fornecedor']), 255);

        if ($tipo === 'receita') {
            if ($clienteId === null && $nomeClienteFornecedor !== null) {
                $clienteId = $this->resolveOrCreateClienteByName($nomeClienteFornecedor, $clienteNameMap, false);
            }
            if ($clienteId === null) {
                $clienteId = $this->resolveOrCreateClienteGenerico('(M-OS) Cliente não localizado (lancamento)', false);
            }
            if ($clienteId === null) {
                return null;
            }

            $conta = ContaReceber::create([
                'ordem_servico_id' => $ordemServicoId,
                'origem_tipo' => 'mos_import',
                'entidade_tipo' => 'mos_lancamento',
                'entidade_id' => $sourceRowId,
                'cliente_id' => $clienteId,
                'descricao' => $descricao,
                'valor' => $valorLiquido,
                'valor_original' => $valorOriginal,
                'valor_recebido' => $statusPago ? $valorLiquido : 0,
                'forma_pagamento_id' => $formaPagamentoId,
                'data_vencimento' => $dataVencimento,
                'data_recebimento' => $statusPago ? ($dataPagamento ?? $dataVencimento ?? now()->toDateString()) : null,
                'status' => $statusPago ? 'pago' : 'aberto',
                'desconto' => $desconto,
                'observacoes' => $observacoes,
                'metadata' => [
                    'fonte' => 'mos_export',
                    'mos_source_id' => $sourceRowId,
                    'vendas_id' => $this->toInt($this->first($row, ['vendas_id'])),
                    'forma_pgto' => $formaPgto,
                ],
                'referencia_externa' => $referencia,
                'created_by' => $userId,
                'updated_by' => $userId,
            ]);

            $anexoCriado = false;
            if ($anexo !== null && trim((string) $anexo) !== '') {
                ContaReceberAnexo::create([
                    'conta_receber_id' => $conta->id,
                    'nome_arquivo' => basename((string) $anexo),
                    'caminho_arquivo' => (string) $anexo,
                    'tipo_mime' => $this->guessMimeFromFilename((string) $anexo),
                    'created_by' => $userId,
                ]);
                $anexoCriado = true;
            }

            if ($statusPago && $this->conciliarContaReceberImportada($conta, $userId)) {
                $this->conciliacoesReceber++;
            }

            return ['tipo' => 'receber', 'anexo' => $anexoCriado];
        }

        $fornecedorId = $clienteId;
        if ($fornecedorId === null && $nomeClienteFornecedor !== null) {
            $fornecedorId = $this->resolveOrCreateClienteByName($nomeClienteFornecedor, $clienteNameMap, true);
        }
        if ($fornecedorId === null) {
            $fornecedorId = $this->resolveOrCreateClienteGenerico('(M-OS) Fornecedor não localizado (lancamento)', true);
        }

        $conta = ContaPagar::create([
            'fornecedor_id' => $fornecedorId,
            'ordem_servico_id' => $ordemServicoId,
            'origem_tipo' => 'mos_import',
            'entidade_tipo' => 'mos_lancamento',
            'entidade_id' => $sourceRowId,
            'descricao' => $descricao,
            'valor' => $valorLiquido,
            'valor_original' => $valorOriginal > 0 ? $valorOriginal : $valorLiquido,
            'valor_pago' => $statusPago ? $valorLiquido : 0,
            'forma_pagamento_id' => $formaPagamentoId,
            'data_vencimento' => $dataVencimento ?? now()->toDateString(),
            'data_pagamento' => $statusPago ? ($dataPagamento ?? $dataVencimento ?? now()->toDateString()) : null,
            'tipo' => 'operacional',
            'status' => $statusPago ? 'pago' : 'aberto',
            'desconto' => $desconto,
            'observacoes' => $observacoes,
            'metadata' => [
                'fonte' => 'mos_export',
                'mos_source_id' => $sourceRowId,
                'forma_pgto' => $formaPgto,
            ],
            'referencia_externa' => $referencia,
            'created_by' => $userId,
            'updated_by' => $userId,
        ]);

        $anexoCriado = false;
        if ($anexo !== null && trim((string) $anexo) !== '') {
            ContaPagarAnexo::create([
                'conta_pagar_id' => $conta->id,
                'nome_arquivo' => basename((string) $anexo),
                'caminho_arquivo' => (string) $anexo,
                'tipo_mime' => $this->guessMimeFromFilename((string) $anexo),
                'created_by' => $userId,
            ]);
            $anexoCriado = true;
        }

        if ($statusPago && $this->conciliarContaPagarImportada($conta, $userId)) {
            $this->conciliacoesPagar++;
        }

        return ['tipo' => 'pagar', 'anexo' => $anexoCriado];
    }

    /**
     * @param  array<int, int>  $osIdMap
     */
    protected function importOrdemServicoAnexo(array $row, array $osIdMap, ?int $userId): bool
    {
        $sourceOsId = $this->toInt($this->first($row, [
            'os_id', 'idos', 'id_os', 'os', 'ordens_servico_id', 'id_os_id',
        ]));
        $ordemServicoId = $sourceOsId !== null ? $this->resolveLivreOrdemServicoIdFromSource($sourceOsId, $osIdMap) : null;
        if ($ordemServicoId === null) {
            return false;
        }

        $nomeArquivo = $this->limit($this->first($row, ['anexo', 'nome', 'nome_arquivo', 'arquivo', 'file', 'documento']), 255);
        if ($nomeArquivo === null) {
            foreach (['link', 'url', 'thumb', 'caminho', 'path'] as $lk) {
                $u = $this->first($row, [$lk]);
                if ($u === null || trim((string) $u) === '') {
                    continue;
                }
                $pathPart = parse_url(trim((string) $u), PHP_URL_PATH);
                if (is_string($pathPart) && $pathPart !== '' && $pathPart !== '/') {
                    $nomeArquivo = $this->limit(basename(str_replace('\\', '/', $pathPart)), 255);
                    break;
                }
                $nomeArquivo = $this->limit(basename(str_replace('\\', '/', trim((string) $u))), 255);
                if ($nomeArquivo !== null) {
                    break;
                }
            }
        }
        if ($nomeArquivo === null) {
            return false;
        }

        $baseUrl = trim((string) ($this->first($row, ['url']) ?? ''));
        $path = trim((string) ($this->first($row, ['path']) ?? ''));
        $arquivoUrl = $baseUrl !== '' ? rtrim($baseUrl, '/') . '/' . ltrim($nomeArquivo, '/') : null;
        $caminhoArquivo = $arquivoUrl ?: ($path !== '' ? rtrim($path, '/\\') . DIRECTORY_SEPARATOR . $nomeArquivo : $nomeArquivo);

        $duplicado = OrdemServicoAnexo::query()
            ->where('ordem_servico_id', $ordemServicoId)
            ->where('nome_arquivo', $nomeArquivo)
            ->where('caminho_arquivo', $caminhoArquivo)
            ->exists();
        if ($duplicado) {
            return true;
        }

        OrdemServicoAnexo::create([
            'ordem_servico_id' => $ordemServicoId,
            'nome_arquivo' => $nomeArquivo,
            'caminho_arquivo' => $caminhoArquivo,
            'tipo' => $this->detectAnexoTipo($nomeArquivo),
            'metadata' => [
                'fonte' => 'mos_export',
                'mos_source_id' => $this->toInt($this->first($row, ['idanexos', 'id_anexos', 'idanexo', 'id'])),
                'thumb' => $this->first($row, ['thumb']),
                'url_base' => $baseUrl !== '' ? $baseUrl : null,
                'path_base' => $path !== '' ? $path : null,
            ],
            'tipo_mime' => $this->guessMimeFromFilename($nomeArquivo),
            'created_by' => $userId,
        ]);

        return true;
    }

    /**
     * @param  array<int, int>  $osIdMap
     * @param  array<int, int>  $equipamentoIdMap
     */
    protected function importEquipamentoOs(array $row, array $osIdMap, array $equipamentoIdMap): bool
    {
        $dumpEquipOsVincId = $this->toInt($this->first($row, ['idequipamentos_os', 'id']));
        $sourceOsId = $this->toInt($this->first($row, ['os_id', 'idos', 'id_os']));
        $ordemServicoId = $sourceOsId !== null ? $this->resolveLivreOrdemServicoIdFromSource($sourceOsId, $osIdMap) : null;
        if ($ordemServicoId === null) {
            return false;
        }

        $dumpEquipamentoId = $this->toInt($this->first($row, ['equipamentos_id']));
        $equipamentoId = $dumpEquipamentoId !== null ? ($equipamentoIdMap[$dumpEquipamentoId] ?? null) : null;

        $os = OrdemServico::query()->find($ordemServicoId);
        if (!$os) {
            return false;
        }

        $updates = [];
        if ($equipamentoId !== null && !$os->equipamento_id) {
            $updates['equipamento_id'] = $equipamentoId;
        }

        $defeitoDeclarado = $this->limit($this->first($row, ['defeito_declarado']), 65535);
        $defeitoEncontrado = $this->limit($this->first($row, ['defeito_encontrado']), 65535);
        $solucao = $this->limit($this->first($row, ['solucao']), 65535);
        $blocos = [];
        if ($defeitoDeclarado) {
            $blocos[] = 'Defeito declarado: ' . $defeitoDeclarado;
        }
        if ($defeitoEncontrado) {
            $blocos[] = 'Defeito encontrado: ' . $defeitoEncontrado;
        }
        if ($solucao) {
            $blocos[] = 'Solução: ' . $solucao;
        }
        if ($dumpEquipOsVincId !== null) {
            $blocos[] = '[MOS_EQUIP_OS_ID:' . $dumpEquipOsVincId . ']';
            $alreadyTagged = str_contains((string) $os->observacoes_internas, '[MOS_EQUIP_OS_ID:' . $dumpEquipOsVincId . ']');
            if ($alreadyTagged) {
                return true;
            }
        }
        if (!empty($blocos)) {
            $texto = implode("\n", $blocos);
            $obsInterna = trim((string) $os->observacoes_internas);
            $updates['observacoes_internas'] = $obsInterna === '' ? $texto : ($obsInterna . "\n\n" . $texto);
        }

        if (!empty($updates)) {
            $os->fill($updates);
            $os->save();
        }

        return true;
    }

    /**
     * @param  list<array<string, mixed>>  $rows
     * @return array<int, list<array<string, mixed>>>
     */
    protected function indexProdutosVendaPorVendaId(array $rows): array
    {
        $out = [];
        foreach ($rows as $itemRow) {
            $vid = $this->toInt($this->first($itemRow, [
                'vendas_id', 'venda_id', 'idvendas', 'vendasid', 'id_vendas', 'venda', 'id_venda', 'idvenda', 'sale_id',
            ]));
            if ($vid === null || $vid < 1) {
                continue;
            }
            $out[$vid][] = $itemRow;
        }

        return $out;
    }

    /**
     * Fallback: alguns dumps não trazem produtos_venda/itens_de_vendas, mas têm produtos_os
     * com o mesmo id numérico da venda.
     *
     * @param  list<array<string, mixed>>  $rows
     * @return array<int, list<array<string, mixed>>>
     */
    protected function indexProdutosOsPorOsId(array $rows): array
    {
        $out = [];
        foreach ($rows as $itemRow) {
            $vid = $this->toInt($this->first($itemRow, ['os_id', 'idos', 'id_os']));
            if ($vid === null || $vid < 1) {
                continue;
            }
            $out[$vid][] = [
                'produtos_id' => $this->first($itemRow, ['produtos_id', 'idprodutos']),
                'quantidade' => $this->first($itemRow, ['quantidade', 'qtde', 'qtd']),
                'preco' => $this->first($itemRow, ['preco', 'valor']),
                'subtotal' => $this->first($itemRow, ['subtotal', 'sub_total', 'subtotalvenda']),
                'descricao_origem_os' => $this->first($itemRow, ['descricao']),
            ];
        }

        return $out;
    }

    /**
     * @param  list<array<string, mixed>>  $rows
     * @return array<int, list<array<string, mixed>>>
     */
    protected function indexServicosOsPorOsId(array $rows): array
    {
        $out = [];
        foreach ($rows as $itemRow) {
            $vid = $this->toInt($this->first($itemRow, ['os_id', 'idos', 'id_os']));
            if ($vid === null || $vid < 1) {
                continue;
            }
            $out[$vid][] = [
                'servicos_id' => $this->first($itemRow, ['servicos_id', 'idservicos', 'servico_id']),
                'quantidade' => $this->first($itemRow, ['quantidade', 'qtde', 'qtd']),
                'preco' => $this->first($itemRow, ['preco', 'valor']),
                'subtotal' => $this->first($itemRow, ['subtotal', 'sub_total', 'subtotalvenda']),
                'descricao_origem_os' => $this->first($itemRow, ['servico', 'descricao']),
            ];
        }

        return $out;
    }

    /**
     * @param array<int, list<array<string,mixed>>> $base
     * @param array<int, list<array<string,mixed>>> $extra
     * @return array<int, list<array<string,mixed>>>
     */
    protected function mergeItensPorVendaId(array $base, array $extra): array
    {
        foreach ($extra as $vendaId => $linhas) {
            if ($linhas === []) {
                continue;
            }
            if (!isset($base[$vendaId])) {
                $base[$vendaId] = [];
            }
            $base[$vendaId] = array_merge($base[$vendaId], $linhas);
        }

        return $base;
    }

    /**
     * Pedido criado na importação antiga só com linha única “Venda M-OS #id” (sem itens no SQL na altura).
     */
    protected function pedidoVendaExportTemSoLinhaSintetica(PedidoVenda $pedido): bool
    {
        $pedido->load('itens');
        if ($pedido->itens->count() !== 1) {
            return false;
        }
        $i = $pedido->itens->first();
        $desc = trim((string) ($i->descricao ?? ''));
        if (preg_match('/^Venda M-OS #\d+/i', $desc) === 1) {
            return true;
        }
        $obs = (string) ($i->observacao ?? '');

        return str_contains($obs, 'sem linhas em produtos_venda');
    }

    /**
     * @param  list<array<string, mixed>>  $linhasItem
     */
    protected function criarLinhasPedidoVendaItensMos(
        PedidoVenda $pedido,
        array $linhasItem,
        array $produtoIdMap,
        array $servicoIdMap,
        array $dumpProdutoNomePorId,
        array $dumpServicoNomePorId
    ): void {
        $ordem = 0;
        foreach ($linhasItem as $itemRow) {
            $dumpProdutoId = $this->toInt($this->first($itemRow, [
                'produtos_id', 'produto_id', 'idprodutos', 'produtosid',
            ]));
            $dumpServicoId = $this->toInt($this->first($itemRow, [
                'servicos_id', 'servico_id', 'idservicos', 'servicosid',
            ]));
            $q = $this->toDecimal($this->first($itemRow, ['quantidade', 'qtde', 'qtd']));
            if ($q === null || $q <= 0) {
                $q = 1.0;
            }
            $precoUnit = $this->toDecimal($this->first($itemRow, ['preco', 'precounitario', 'valor_unitario', 'valorunitario']));
            $subTotal = $this->toDecimal($this->first($itemRow, ['subtotal', 'sub_total', 'subtotalvenda']));
            $lineTotal = $subTotal !== null && $subTotal > 0
                ? (float) $subTotal
                : max(0.0, (float) ($precoUnit ?? 0) * (float) $q);
            $unit = (float) $q > 0 ? round($lineTotal / (float) $q, 2) : max(0.0, (float) ($precoUnit ?? 0));

            $livreProdutoId = $dumpProdutoId !== null ? ($produtoIdMap[$dumpProdutoId] ?? null) : null;
            $livreServicoId = $dumpServicoId !== null ? ($servicoIdMap[$dumpServicoId] ?? null) : null;
            $nomeDumpProduto = $dumpProdutoId !== null ? ($dumpProdutoNomePorId[$dumpProdutoId] ?? null) : null;
            $nomeDumpServico = $dumpServicoId !== null ? ($dumpServicoNomePorId[$dumpServicoId] ?? null) : null;
            $sufixoManual = ' — produto manual (sem cadastro no sistema)';

            if ($livreProdutoId !== null) {
                $nomeProd = Produto::query()->whereKey($livreProdutoId)->value('nome');
                $descricao = $nomeProd
                    ? $this->limit((string) $nomeProd, 255)
                    : $this->limit('Produto (origem M-OS) #' . $dumpProdutoId, 255);
                $obsItem = null;
                $tipoItem = 'produto';
            } elseif ($livreServicoId !== null) {
                $nomeSrv = Servico::query()->whereKey($livreServicoId)->value('nome');
                $descricao = $nomeSrv
                    ? $this->limit((string) $nomeSrv, 255)
                    : $this->limit('Serviço (origem M-OS) #' . $dumpServicoId, 255);
                $obsItem = null;
                $tipoItem = 'servico';
            } else {
                if ($nomeDumpProduto !== null && $nomeDumpProduto !== '') {
                    $base = $nomeDumpProduto . $sufixoManual;
                    $descricao = $this->limit($base, 255);
                } elseif ($nomeDumpServico !== null && $nomeDumpServico !== '') {
                    $descricao = $this->limit($nomeDumpServico . ' — serviço manual (sem cadastro no sistema)', 255);
                } elseif ($dumpProdutoId !== null) {
                    $descricao = $this->limit('Produto manual (sem cadastro no sistema) — origem #' . $dumpProdutoId, 255);
                } elseif ($dumpServicoId !== null) {
                    $descricao = $this->limit('Serviço manual (sem cadastro no sistema) — origem #' . $dumpServicoId, 255);
                } elseif (($descOrigem = trim((string) ($this->first($itemRow, ['descricao_origem_os']) ?? ''))) !== '') {
                    $descricao = $this->limit($descOrigem . ' — item manual (origem OS)', 255);
                } else {
                    $descricao = $this->limit('Produto manual (sem cadastro no sistema)', 255);
                }
                $obsItem = 'Item importado do export; produto não vinculado ao cadastro do ERP.';
                $tipoItem = $dumpServicoId !== null ? 'servico' : 'produto';
            }

            PedidoVendaItem::create([
                'pedido_venda_id' => $pedido->id,
                'tipo' => $tipoItem,
                'produto_id' => $livreProdutoId,
                'servico_id' => $livreServicoId,
                'descricao' => $descricao,
                'quantidade' => $q,
                'preco_unitario' => max(0, $unit),
                'desconto' => 0,
                'observacao' => $obsItem,
                'ordem' => $ordem++,
            ]);
        }
    }

    /**
     * Nome/descrição do produto na origem por id do produto no dump (para descrever linhas de venda sem match no ERP).
     *
     * @param  list<array<string, mixed>>  $produtosRows
     * @return array<int, string>
     */
    protected function buildDumpProdutoNomePorId(array $produtosRows): array
    {
        $map = [];
        foreach ($produtosRows as $row) {
            $id = $this->toInt($this->first($row, ['idprodutos', 'idproduto', 'id']));
            if ($id === null || $id < 1) {
                continue;
            }
            $nome = trim((string) ($this->first($row, ['descricao', 'nome', 'produto']) ?? ''));
            if ($nome !== '') {
                $map[$id] = $nome;
            }
        }

        return $map;
    }

    /**
     * @param  list<array<string, mixed>>  $servicosRows
     * @return array<int, string>
     */
    protected function buildDumpServicoNomePorId(array $servicosRows): array
    {
        $map = [];
        foreach ($servicosRows as $row) {
            $id = $this->toInt($this->first($row, ['idservicos', 'idservico', 'id']));
            if ($id === null || $id < 1) {
                continue;
            }
            $nome = trim((string) ($this->first($row, ['nome', 'descricao', 'servico']) ?? ''));
            if ($nome !== '') {
                $map[$id] = $nome;
            }
        }

        return $map;
    }

    protected function findPedidoVendaImportadoPorMarkerExport(int $sourceVendaId): ?PedidoVenda
    {
        $needle = '[MOS_VENDA_ID:' . $sourceVendaId . ']';

        return PedidoVenda::query()
            ->where('observacoes_internas', 'like', '%' . $needle . '%')
            ->first();
    }

    protected function contaReceberMosLancamentoReferenciaVendaId(int $sourceVendaId): bool
    {
        return ContaReceber::query()
            ->where('origem_tipo', 'mos_import')
            ->where('entidade_tipo', 'mos_lancamento')
            ->where(function ($q) use ($sourceVendaId) {
                $q->where('descricao', 'like', '%Venda%' . $sourceVendaId . '%')
                    ->orWhere('metadata', 'like', '%\"vendas_id\":' . $sourceVendaId . '%');
            })
            ->exists();
    }

    protected function aplicarFinanceiroGeradoPedidoVendaMos(int $sourceVendaId, ?PedidoVenda $pedido, bool $dumpUsaLancamentos): void
    {
        if ($pedido === null) {
            return;
        }
        if ($dumpUsaLancamentos) {
            if (!$pedido->financeiro_gerado) {
                $pedido->financeiro_gerado = true;
                $pedido->saveQuietly();
            }

            return;
        }
        if ($pedido->financeiro_gerado) {
            return;
        }
        $ref = 'mos:vendas:' . $sourceVendaId;
        $temContaVenda = ContaReceber::query()->where('referencia_externa', $ref)->exists();
        if ($temContaVenda || $this->contaReceberMosLancamentoReferenciaVendaId($sourceVendaId)) {
            $pedido->financeiro_gerado = true;
            $pedido->saveQuietly();
        }
    }

    protected function mapExportVendaStatusParaPedido(string $statusRaw, bool $faturadoFlag): string
    {
        $s = mb_strtolower(trim($statusRaw));
        if ($s !== '' && (str_contains($s, 'cancel') || in_array($s, ['cancelado', 'cancelada'], true))) {
            return 'cancelado';
        }
        if ($faturadoFlag || in_array($s, ['faturado', 'finalizado', 'pago', 'fechado'], true)) {
            return 'faturado';
        }

        return 'confirmado';
    }

    /**
     * @param  array<int, int>  $clienteIdMap
     * @param  array<int, int>  $produtoIdMap
     * @param  array<int, list<array<string, mixed>>>  $itensPorVendaId
     * @param  array<int, string>  $dumpProdutoNomePorId
     * @return array{created: bool, pedido: ?PedidoVenda}
     */
    protected function importVendaComoPedido(
        array $row,
        array $clienteIdMap,
        array $produtoIdMap,
        array $servicoIdMap,
        array $itensPorVendaId,
        array $itensFallbackPorVendaId,
        array $dumpProdutoNomePorId,
        array $dumpServicoNomePorId,
        ?int $userId,
        ?callable $log = null
    ): array {
        $logFn = $log ?? static fn () => null;
        $sourceVendaId = $this->toInt($this->first($row, ['idvendas', 'id']));
        if ($sourceVendaId === null) {
            return ['created' => false, 'pedido' => null];
        }

        $linhasDump = $itensPorVendaId[$sourceVendaId] ?? [];
        $origemLinhas = 'produtos_venda';
        if ($linhasDump === [] && isset($itensFallbackPorVendaId[$sourceVendaId])) {
            $linhasDump = $itensFallbackPorVendaId[$sourceVendaId];
            $origemLinhas = 'produtos_os';
            $logFn('  [vendas] Venda ' . $sourceVendaId . ': itens obtidos por fallback em produtos_os (mesmo id da venda).');
        }

        $existente = $this->findPedidoVendaImportadoPorMarkerExport($sourceVendaId);
        if ($existente !== null) {
            if ($linhasDump !== [] && $this->pedidoVendaExportTemSoLinhaSintetica($existente)) {
                PedidoVendaItem::query()->where('pedido_venda_id', $existente->id)->delete();
                $this->criarLinhasPedidoVendaItensMos(
                    $existente,
                    $linhasDump,
                    $produtoIdMap,
                    $servicoIdMap,
                    $dumpProdutoNomePorId,
                    $dumpServicoNomePorId
                );
                $existente->load('itens');
                $existente->recalcularTotais();
                if ($userId !== null) {
                    $existente->updated_by = $userId;
                }
                $existente->save();
            }

            return ['created' => false, 'pedido' => $existente];
        }

        $clienteOrigemId = $this->toInt($this->first($row, ['clientes_id', 'cliente_id']));
        $clienteId = $clienteOrigemId !== null ? ($clienteIdMap[$clienteOrigemId] ?? null) : null;
        if ($clienteId === null) {
            $clienteId = $this->resolveOrCreateClienteGenerico('(M-OS) Cliente não localizado (venda)', false);
            if ($clienteId === null) {
                return ['created' => false, 'pedido' => null];
            }
        }

        $statusRaw = (string) ($this->first($row, ['status']) ?? '');
        $faturado = $this->toBool($this->first($row, ['faturado'])) ?? false;
        $statusPedido = $this->mapExportVendaStatusParaPedido($statusRaw, $faturado);

        $dataPedido = $this->toDate($this->first($row, ['datavenda'])) ?? now()->toDateString();

        $valorOriginal = $this->toDecimal($this->first($row, ['valortotal'])) ?? 0.0;
        $valorLiquido = $this->toDecimal($this->first($row, ['valor_desconto']));
        if ($valorLiquido === null || $valorLiquido <= 0) {
            $valorLiquido = $valorOriginal;
        }
        $valorLiquido = (float) $valorLiquido;
        $valorOriginal = max((float) $valorOriginal, $valorLiquido);
        $descontoGlobal = max(0.0, round($valorOriginal - $valorLiquido, 2));

        $obs = trim((string) ($this->first($row, ['observacoes']) ?? ''));
        $obsCliente = trim((string) ($this->first($row, ['observacoes_cliente']) ?? ''));
        $observacoes = trim($obs . ($obs !== '' && $obsCliente !== '' ? "\n\n" : '') . $obsCliente);

        $marker = '[MOS_VENDA_ID:' . $sourceVendaId . ']';
        $obsInternas = $marker . "\n" . 'Importado do export (vendas.idVendas=' . $sourceVendaId . ').';

        $pedido = new PedidoVenda([
            'cliente_id' => $clienteId,
            'vendedor_id' => null,
            'status' => $statusPedido,
            'canal_venda' => 'outro',
            'observacoes' => $observacoes !== '' ? $this->limit($observacoes, 65535) : null,
            'observacoes_internas' => $obsInternas,
            'data_pedido' => $dataPedido,
            'origem_tipo' => 'mos_venda',
            'desconto_global' => $descontoGlobal,
            'acrescimos_global' => 0,
            'estoque_reservado' => false,
            'estoque_baixado' => false,
            'financeiro_gerado' => false,
            'created_by' => $userId,
            'updated_by' => $userId,
        ]);

        $dataCarbon = \Illuminate\Support\Carbon::parse((string) $dataPedido)->startOfDay();
        if (in_array($statusPedido, ['confirmado', 'faturado', 'cancelado'], true)) {
            $pedido->data_confirmacao = $dataCarbon;
        }
        if ($statusPedido === 'faturado') {
            $pedido->data_faturamento = $dataCarbon;
        }
        if ($statusPedido === 'cancelado') {
            $pedido->data_cancelamento = $dataCarbon;
        }

        $pedido->save();

        $linhasItem = $linhasDump;
        if ($linhasItem !== []) {
            $this->criarLinhasPedidoVendaItensMos(
                $pedido,
                $linhasItem,
                $produtoIdMap,
                $servicoIdMap,
                $dumpProdutoNomePorId,
                $dumpServicoNomePorId
            );
        } else {
            $logFn('  [vendas] Venda ' . $sourceVendaId . ' sem itens vinculados no dump (origem testada: ' . $origemLinhas . '); a usar linha sintética.');
            PedidoVendaItem::create([
                'pedido_venda_id' => $pedido->id,
                'tipo' => 'produto',
                'produto_id' => null,
                'servico_id' => null,
                'descricao' => $this->limit('Venda M-OS #' . $sourceVendaId, 255),
                'quantidade' => 1,
                'preco_unitario' => max(0, $valorLiquido),
                'desconto' => 0,
                'observacao' => 'Total importado do export (sem linhas em produtos_venda/itens_de_vendas).',
                'ordem' => 0,
            ]);
        }

        $pedido->load('itens');
        $pedido->recalcularTotais();
        $pedido->save();

        return ['created' => true, 'pedido' => $pedido];
    }

    /**
     * Corrige pedidos que ficaram só com a linha sintética quando os itens vieram noutra parte do ZIP
     * ou quando a venda foi processada antes dos INSERTs de produtos_venda no mesmo ficheiro.
     *
     * @param  array<int, list<array<string, mixed>>>  $itensPorVendaId
     */
    protected function sincronizarPedidosVendaItensDumpMos(
        array $itensPorVendaId,
        array $itensFallbackPorVendaId,
        array $produtoIdMap,
        array $servicoIdMap,
        array $dumpProdutoNomePorId,
        array $dumpServicoNomePorId,
        ?int $userId,
        ?callable $log = null
    ): int {
        $logFn = $log ?? static fn () => null;
        $n = 0;
        $allIds = array_unique(array_merge(array_keys($itensPorVendaId), array_keys($itensFallbackPorVendaId)));
        foreach ($allIds as $sourceVendaId) {
            $linhas = $itensPorVendaId[$sourceVendaId] ?? [];
            $origem = 'produtos_venda';
            if ($linhas === [] && isset($itensFallbackPorVendaId[$sourceVendaId])) {
                $linhas = $itensFallbackPorVendaId[$sourceVendaId];
                $origem = 'produtos_os';
            }
            if ($linhas === []) {
                continue;
            }
            $vid = $this->toInt($sourceVendaId);
            if ($vid === null || $vid < 1) {
                continue;
            }
            $pedido = $this->findPedidoVendaImportadoPorMarkerExport($vid);
            if ($pedido === null || !$this->pedidoVendaExportTemSoLinhaSintetica($pedido)) {
                continue;
            }
            PedidoVendaItem::query()->where('pedido_venda_id', $pedido->id)->delete();
            $this->criarLinhasPedidoVendaItensMos(
                $pedido,
                $linhas,
                $produtoIdMap,
                $servicoIdMap,
                $dumpProdutoNomePorId,
                $dumpServicoNomePorId
            );
            $pedido->load('itens');
            $pedido->recalcularTotais();
            if ($userId !== null) {
                $pedido->updated_by = $userId;
            }
            $pedido->save();
            if ($origem === 'produtos_os') {
                $logFn('  [vendas] Pedido export ' . $vid . ' sincronizado por fallback de produtos_os.');
            }
            $n++;
        }

        return $n;
    }

    /**
     * @param  array<int, int>  $clienteIdMap
     * @param  array<int, int>  $osIdByLancamentoMap
     */
    protected function importVendaFinanceiro(array $row, array $clienteIdMap, array $osIdByLancamentoMap, ?int $userId, ?PedidoVenda $pedidoVenda = null): bool
    {
        $sourceVendaId = $this->toInt($this->first($row, ['idvendas', 'id']));
        if ($sourceVendaId === null) {
            return false;
        }

        // Evita duplicidade: quando a venda já virou lançamento financeiro na origem,
        // ela já foi importada em contas_receber como mos_lancamento.
        if ($this->contaReceberMosLancamentoReferenciaVendaId($sourceVendaId)) {
            return false;
        }

        $clienteOrigemId = $this->toInt($this->first($row, ['clientes_id', 'cliente_id']));
        $clienteId = $clienteOrigemId !== null ? ($clienteIdMap[$clienteOrigemId] ?? null) : null;
        if ($clienteId === null) {
            $clienteId = $this->resolveOrCreateClienteGenerico('(M-OS) Cliente não localizado (venda)', false);
            if ($clienteId === null) {
                return false;
            }
        }

        $status = mb_strtolower((string) ($this->first($row, ['status']) ?? ''));
        $faturado = $this->toBool($this->first($row, ['faturado'])) ?? false;

        $referencia = 'mos:vendas:' . $sourceVendaId;
        if (ContaReceber::query()->where('referencia_externa', $referencia)->exists()) {
            return true;
        }

        $valorOriginal = $this->toDecimal($this->first($row, ['valortotal'])) ?? 0.0;
        $valorLiquido = $this->toDecimal($this->first($row, ['valor_desconto']));
        if ($valorLiquido === null || $valorLiquido <= 0) {
            $valorLiquido = $valorOriginal;
        }
        $valorLiquido = (float) $valorLiquido;
        $valorOriginal = max((float) $valorOriginal, $valorLiquido);

        $lancId = $this->toInt($this->first($row, ['lancamentos_id']));
        $ordemServicoId = $lancId !== null ? ($osIdByLancamentoMap[$lancId] ?? null) : null;
        $dataVenda = $this->toDate($this->first($row, ['datavenda'])) ?? now()->toDateString();
        $desconto = max(0, round($valorOriginal - $valorLiquido, 2));
        $descricao = 'Venda M-OS #' . $sourceVendaId;
        $obs = trim((string) ($this->first($row, ['observacoes']) ?? ''));
        $obsCliente = trim((string) ($this->first($row, ['observacoes_cliente']) ?? ''));
        $observacoes = trim($obs . ($obs !== '' && $obsCliente !== '' ? "\n\n" : '') . $obsCliente);

        $isPago = $faturado || in_array($status, ['faturado', 'finalizado', 'pago'], true);
        $metadata = [
            'fonte' => 'mos_export',
            'source_venda_id' => $sourceVendaId,
            'source_lancamento_id' => $lancId,
            'status_origem' => $this->first($row, ['status']),
        ];
        if ($pedidoVenda !== null) {
            $metadata['pedido_venda_id'] = $pedidoVenda->id;
        }
        $conta = ContaReceber::create([
            'ordem_servico_id' => $ordemServicoId,
            'origem_tipo' => 'mos_import',
            'entidade_tipo' => 'mos_venda',
            'entidade_id' => $sourceVendaId,
            'cliente_id' => $clienteId,
            'descricao' => $descricao,
            'valor' => $valorLiquido,
            'valor_original' => $valorOriginal > 0 ? $valorOriginal : $valorLiquido,
            'valor_recebido' => $isPago ? $valorLiquido : 0,
            'data_vencimento' => $dataVenda,
            'data_recebimento' => $isPago ? $dataVenda : null,
            'status' => $isPago ? 'pago' : 'aberto',
            'desconto' => $desconto,
            'observacoes' => $observacoes !== '' ? $observacoes : null,
            'metadata' => $metadata,
            'referencia_externa' => $referencia,
            'created_by' => $userId,
            'updated_by' => $userId,
        ]);

        if ($isPago && $this->conciliarContaReceberImportada($conta, $userId)) {
            $this->conciliacoesReceber++;
        }

        return true;
    }

    /**
     * @param  array<int, int>  $clienteIdMap
     * @param  array<int, int>  $osIdMap
     */
    protected function importCobrancaFinanceiro(array $row, array $clienteIdMap, array $osIdMap, ?int $userId): bool
    {
        $sourceRowId = $this->toInt($this->first($row, ['idcobranca', 'id']));
        if ($sourceRowId === null) {
            return false;
        }

        $referencia = 'mos:cobrancas:' . $sourceRowId;
        if (ContaReceber::query()->where('referencia_externa', $referencia)->exists()) {
            return true;
        }

        $clienteOrigemId = $this->toInt($this->first($row, ['clientes_id']));
        $clienteId = $clienteOrigemId !== null ? ($clienteIdMap[$clienteOrigemId] ?? null) : null;
        if ($clienteId === null) {
            return false;
        }

        $sourceOsId = $this->toInt($this->first($row, ['os_id', 'idos', 'id_os']));
        $ordemServicoId = $sourceOsId !== null ? $this->resolveLivreOrdemServicoIdFromSource($sourceOsId, $osIdMap) : null;
        $valor = $this->toDecimal($this->first($row, ['total']));
        if ($valor === null || $valor <= 0) {
            return false;
        }

        $statusOrigemExport = mb_strtolower((string) ($this->first($row, ['status']) ?? ''));
        $pago = in_array($statusOrigemExport, ['paid', 'pago', 'received', 'recebido'], true);
        $vencimento = $this->toDate($this->first($row, ['expire_at'])) ?? now()->toDateString();
        $dataPgto = $this->toDate($this->first($row, ['created_at']));

        $descricao = 'Cobrança M-OS #' . $sourceRowId;
        $mensagem = $this->limit($this->first($row, ['message']), 65535);

        $conta = ContaReceber::create([
            'ordem_servico_id' => $ordemServicoId,
            'origem_tipo' => 'mos_import',
            'entidade_tipo' => 'mos_cobranca',
            'entidade_id' => $sourceRowId,
            'cliente_id' => $clienteId,
            'descricao' => $descricao,
            'valor' => $valor,
            'valor_original' => $valor,
            'valor_recebido' => $pago ? $valor : 0,
            'data_vencimento' => $vencimento,
            'data_recebimento' => $pago ? $dataPgto : null,
            'status' => $pago ? 'pago' : 'aberto',
            'observacoes' => $mensagem,
            'metadata' => [
                'fonte' => 'mos_export',
                'charge_id' => $this->first($row, ['charge_id']),
                'payment_method' => $this->first($row, ['payment_method']),
                'payment_gateway' => $this->first($row, ['payment_gateway']),
                'payment_url' => $this->first($row, ['payment_url']),
                'barcode' => $this->first($row, ['barcode']),
                'pdf' => $this->first($row, ['pdf']),
                'link' => $this->first($row, ['link']),
            ],
            'referencia_externa' => $referencia,
            'created_by' => $userId,
            'updated_by' => $userId,
        ]);

        if ($pago && $this->conciliarContaReceberImportada($conta, $userId)) {
            $this->conciliacoesReceber++;
        }

        return true;
    }

    /**
     * Consolida várias linhas de `anotacoes_os` do export no campo observacoes_internas da OS (com data/hora por registo).
     *
     * @param  array<int, array<string, mixed>>  $rows
     * @param  array<int, int>  $osIdMap
     */
    protected function mergeLegacyAnotacoesIntoObservacoesInternas(array $rows, array $osIdMap, callable $logFn, array &$counts): void
    {
        if ($rows === []) {
            $logFn('[' . date('H:i:s') . '] [anotacoes_os] Nenhuma linha extraída do SQL para esta tabela.');
            $logFn('  [anotacoes_os] Diagnóstico: verifique se o dump contém INSERT INTO `anotacoes_os` ou alias (anotacoes).');

            return;
        }

        $logFn('[' . date('H:i:s') . '] A consolidar anotações do export (anotacoes_os) em observações internas por OS...');
        $logFn('  [anotacoes_os] ' . count($rows) . ' linha(s) extraída(s). Chaves da 1.ª linha: ' . implode(', ', array_keys($rows[0] ?? [])));

        $byOs = [];
        $semOsId = 0;
        foreach ($rows as $row) {
            $counts['linhas_lidas']++;
            $sourceOsId = null;
            $foundKey = null;
            foreach (['os_id', 'idos', 'id_os', 'os', 'ordens_servico_id', 'idos_id', 'referencia_os'] as $osKey) {
                if (!array_key_exists($osKey, $row) || $row[$osKey] === null) {
                    continue;
                }
                $sourceOsId = $this->toInt($row[$osKey]);
                if ($sourceOsId !== null) {
                    $foundKey = $osKey;
                    break;
                }
            }
            if ($sourceOsId === null) {
                $counts['linhas_ignoradas']++;
                $semOsId++;

                continue;
            }
            $byOs[$sourceOsId][] = $row;
            unset($foundKey);
        }

        if ($semOsId > 0) {
            $logFn('  [anotacoes_os] ' . $semOsId . ' linha(s) sem os_id reconhecível — ignoradas. Chaves disponíveis: ' . implode(', ', array_keys($rows[0] ?? [])));
        }

        $logFn('  [anotacoes_os] ' . count($byOs) . ' OS(s) distintas com anotações.');

        $mergedCount = 0;
        $ignoredCount = 0;

        foreach ($byOs as $sourceOsId => $group) {
            usort($group, function (array $a, array $b): int {
                $ta = $this->toDateTime($this->first($a, ['data_hora', 'data', 'created_at', 'timestamp', 'dh'])) ?? '';
                $tb = $this->toDateTime($this->first($b, ['data_hora', 'data', 'created_at', 'timestamp', 'dh'])) ?? '';

                return strcmp((string) $ta, (string) $tb);
            });

            $livreOsId = $this->resolveLivreOrdemServicoIdFromSource($sourceOsId, $osIdMap);
            if ($livreOsId === null) {
                $counts['linhas_ignoradas'] += count($group);
                $ignoredCount++;
                $logFn('  [anotacoes_os] AVISO: OS origem id=' . $sourceOsId . ' não encontrada no LivreOS. (Verifique se a tabela `os` foi importada na mesma parte.)');

                continue;
            }

            $blocks = [];
            foreach ($group as $row) {
                $texto = $this->extractAnotacaoTextoFromRow($row);
                if ($texto === '') {
                    $logFn('  [anotacoes_os] Linha sem texto: OS origem id=' . $sourceOsId . '. Chaves: ' . implode(', ', array_keys($row)));

                    continue;
                }
                $dtRaw = $this->toDateTime($this->first($row, ['data_hora', 'data', 'created_at', 'timestamp', 'dh']));
                try {
                    $label = $dtRaw ? \Carbon\Carbon::parse($dtRaw)->format('d/m/Y H:i') : '(sem data)';
                } catch (\Throwable) {
                    $label = $dtRaw ?? '(sem data)';
                }
                $blocks[] = '--- [' . $label . '] ---' . "\n" . $texto;
            }

            if ($blocks === []) {
                $counts['linhas_ignoradas'] += count($group);
                $ignoredCount++;

                continue;
            }

            $append = implode("\n\n", $blocks);
            $separator = '────────── Observações internas (importadas do export) ──────────';

            $currentRaw = \Illuminate\Support\Facades\DB::table('ordem_servicos')
                ->where('id', $livreOsId)
                ->value('observacoes_internas');

            $current = trim((string) ($currentRaw ?? ''));

            if (str_contains($current, $separator)) {
                $parts = explode($separator, $current, 2);
                $before = rtrim($parts[0]);
                $existing = isset($parts[1]) ? trim($parts[1]) : '';
                $existingBlocks = array_map('trim', preg_split('/\n{2,}/', $existing) ?: []);
                $existingBlocks = array_filter($existingBlocks, static fn (string $b): bool => $b !== '');
                $newBlocks = array_filter($blocks, static function (string $block) use ($existingBlocks): bool {
                    foreach ($existingBlocks as $eb) {
                        if (mb_substr($block, 0, 60) !== '' && str_contains($eb, mb_substr($block, 0, 60))) {
                            return false;
                        }
                    }

                    return true;
                });
                if ($newBlocks === []) {
                    $logFn('  [anotacoes_os] OS origem id=' . $sourceOsId . ' (Livre #' . $livreOsId . '): ' . count($blocks) . ' anotação(ões) já estavam presentes — sem alteração.');
                    $counts['anotacoes_os_logs'] += count($blocks);

                    continue;
                }
                $merged = $before . "\n\n" . $separator . "\n" . $existing . "\n\n" . implode("\n\n", $newBlocks);
            } else {
                $merged = $current === '' ? $separator . "\n" . $append : $current . "\n\n" . $separator . "\n" . $append;
            }

            if (mb_strlen($merged) > 62000) {
                $merged = mb_substr($merged, 0, 62000) . "\n\n… (texto truncado por limite do campo)";
            }

            try {
                $updated = \Illuminate\Support\Facades\DB::table('ordem_servicos')
                    ->where('id', $livreOsId)
                    ->update([
                        'observacoes_internas' => $merged,
                        'updated_at' => now(),
                    ]);
                if ($updated === 0) {
                    $logFn('  [anotacoes_os] AVISO: DB::update retornou 0 linhas para OS id=' . $livreOsId . ' — registo não encontrado ou sem alteração.');
                } else {
                    $counts['anotacoes_os_logs'] += count($blocks);
                    $mergedCount++;
                }
            } catch (\Throwable $e) {
                $logFn('  [anotacoes_os] ERRO ao gravar observacoes_internas para OS id=' . $livreOsId . ': ' . $e->getMessage());
            }
        }

        $logFn('  [anotacoes_os] Concluído: ' . $mergedCount . ' OS(s) actualizadas, ' . $ignoredCount . ' ignoradas.');
    }

    /**
     * @param  array<int, int>  $osIdMap
     */
    protected function importLegacySystemLogOs(array $row, array $osIdMap, ?int $userId): bool
    {
        $sourceLogId = $this->toInt($this->first($row, ['idlogs', 'idlogs_id', 'id']));
        $tarefa = trim((string) ($this->first($row, ['tarefa', 'descricao', 'acao', 'mensagem', 'log']) ?? ''));
        if ($sourceLogId === null || $tarefa === '') {
            return false;
        }

        $sourceOsId = $this->extractOsIdFromText($tarefa);
        if ($sourceOsId === null) {
            return false;
        }

        $ordemServicoId = $this->resolveLivreOrdemServicoIdFromSource($sourceOsId, $osIdMap);
        if ($ordemServicoId === null) {
            return false;
        }

        $driver = \Illuminate\Support\Facades\DB::getDriverName();
        $exists = OrdemServicoLog::query()
            ->where('ordem_servico_id', $ordemServicoId)
            ->where('tipo', 'log_sistema_mos')
            ->where(function ($q) use ($sourceLogId, $driver) {
                if ($driver === 'sqlite') {
                    $q->whereRaw('CAST(json_extract(dados, \'$.mos_log_id\') AS INTEGER) = ?', [$sourceLogId])
                        ->orWhereRaw('json_extract(dados, \'$.mos_log_id\') = ?', [(string) $sourceLogId]);
                } else {
                    $q->where('dados->mos_log_id', $sourceLogId)
                        ->orWhere('dados->mos_log_id', (string) $sourceLogId);
                }
            })
            ->exists();
        if ($exists) {
            return true;
        }

        $usuario = trim((string) ($this->first($row, ['usuario', 'nome', 'user', 'user_nome', 'funcionario', 'email']) ?? ''));
        $descricaoLinha = $usuario !== '' ? ($usuario . ' — ' . $tarefa) : $tarefa;
        $descricaoLinha = mb_substr($descricaoLinha, 0, 2000);

        $data = $this->toDate($this->first($row, ['data']));
        $hora = trim((string) ($this->first($row, ['hora']) ?? ''));
        $createdAt = null;
        if ($data !== null && $hora !== '') {
            $createdAt = $this->toDateTime($data . ' ' . $hora);
        }
        if ($createdAt === null) {
            $createdAt = $this->toDateTime($this->first($row, ['created_at', 'timestamp', 'data_hora']));
        }
        if ($createdAt === null) {
            $createdAt = now()->format('Y-m-d H:i:s');
        }

        $log = new OrdemServicoLog([
            'ordem_servico_id' => $ordemServicoId,
            'user_id' => $userId,
            'tipo' => 'log_sistema_mos',
            'descricao' => $descricaoLinha,
            'dados' => [
                'fonte' => 'mos_export',
                'mos_log_id' => $sourceLogId,
                'usuario' => $usuario !== '' ? $usuario : null,
                'tarefa' => $tarefa,
                'data' => $this->first($row, ['data']),
                'hora' => $this->first($row, ['hora']),
                'ip' => $this->first($row, ['ip']),
            ],
            'ip' => $this->limit($this->first($row, ['ip']), 45),
        ]);
        $log->created_at = $createdAt;
        $log->updated_at = $createdAt;
        $log->save();

        return true;
    }

    protected function buildClienteObservacao(array $row, bool $isFornecedor): ?string
    {
        $parts = [];
        $obs = $this->first($row, ['observacoes', 'obs', 'anotacoes']);
        if ($obs !== null && trim((string) $obs) !== '') {
            $parts[] = trim((string) $obs);
        }
        if ($isFornecedor) {
            $parts[] = 'Marcado como fornecedor na base de origem.';
        }
        return empty($parts) ? null : implode("\n", $parts);
    }

    protected function buildProdutoObservacao(array $row): ?string
    {
        $parts = [];
        $obs = $this->first($row, ['observacoes', 'descricao']);
        if ($obs !== null && trim((string) $obs) !== '') {
            $parts[] = trim((string) $obs);
        }
        $codBarras = $this->first($row, ['coddebarra', 'codigobarras', 'ean']);
        if ($codBarras !== null && trim((string) $codBarras) !== '') {
            $parts[] = 'Código de barras (origem): ' . trim((string) $codBarras);
        }
        return empty($parts) ? null : implode("\n", $parts);
    }

    protected function normalizeOsStatus(mixed $status): string
    {
        $raw = trim((string) $status);
        if ($raw === '') {
            return 'Aberta';
        }
        $normalized = mb_strtolower($raw);
        return match ($normalized) {
            'aberto', 'aberta' => 'Aberta',
            'orcamento', 'orçamento' => 'Orçamento',
            'negociacao', 'negociação' => 'Negociação',
            'aprovado' => 'Aprovado',
            'aguardando pecas', 'aguardando peças' => 'Aguardando Peças',
            'em andamento' => 'Em Andamento',
            'finalizado' => 'Encerrada',
            'faturado' => 'Encerrada',
            'cancelado' => 'Cancelado',
            default => mb_substr($raw, 0, 50),
        };
    }

    /**
     * @param  array<string, int>  $clienteNameMap
     */
    protected function resolveOrCreateClienteByName(string $nome, array &$clienteNameMap, bool $fornecedor): ?int
    {
        $normalized = $this->normalizeName($nome);
        if ($normalized === '') {
            return null;
        }
        if (isset($clienteNameMap[$normalized])) {
            return $clienteNameMap[$normalized];
        }

        $existing = Cliente::query()
            ->whereRaw('LOWER(TRIM(nome)) = ?', [strtolower(trim($nome))])
            ->first();
        if ($existing !== null) {
            $clienteNameMap[$normalized] = (int) $existing->id;
            return (int) $existing->id;
        }

        $cliente = Cliente::create([
            'tipo_pessoa' => 'J',
            'pais_origem' => 'Brasil',
            'nome' => mb_substr($nome, 0, 100),
            'razao_social' => mb_substr($nome, 0, 255),
            'observacoes' => $fornecedor
                ? 'Fornecedor criado automaticamente pela importação (M-OS).'
                : 'Cliente criado automaticamente pela importação (M-OS).',
        ]);

        if ($fornecedor) {
            Contato::create([
                'cliente_id' => $cliente->id,
                'nome' => mb_substr($nome, 0, 100),
                'principal' => true,
                'cobranca' => true,
                'is_fornecedor' => true,
            ]);
        }

        $clienteNameMap[$normalized] = (int) $cliente->id;
        return (int) $cliente->id;
    }

    protected function resolveOrCreateClienteGenerico(string $nome, bool $fornecedor): ?int
    {
        $normalized = $this->normalizeName($nome);
        if ($normalized === '') {
            return null;
        }

        $existing = Cliente::query()
            ->whereRaw('LOWER(TRIM(nome)) = ?', [strtolower(trim($nome))])
            ->first();
        if ($existing !== null) {
            return (int) $existing->id;
        }

        $cliente = Cliente::create([
            'tipo_pessoa' => 'J',
            'pais_origem' => 'Brasil',
            'nome' => mb_substr($nome, 0, 100),
            'razao_social' => mb_substr($nome, 0, 255),
            'observacoes' => $fornecedor
                ? 'Fornecedor criado automaticamente pela importação (M-OS).'
                : 'Cliente criado automaticamente pela importação (M-OS).',
        ]);

        if ($fornecedor) {
            Contato::create([
                'cliente_id' => $cliente->id,
                'nome' => mb_substr($nome, 0, 100),
                'principal' => true,
                'cobranca' => true,
                'is_fornecedor' => true,
            ]);
        }

        return (int) $cliente->id;
    }

    protected function resolveContaBancariaImportacaoId(): ?int
    {
        if ($this->contaBancariaImportacaoId !== null) {
            return $this->contaBancariaImportacaoId;
        }
        $id = ContaBancaria::query()->where('ativo', true)->orderBy('id')->value('id');
        if ($id === null) {
            $id = ContaBancaria::query()->orderBy('id')->value('id');
        }
        $this->contaBancariaImportacaoId = $id !== null ? (int) $id : null;

        return $this->contaBancariaImportacaoId;
    }

    protected function conciliarContaReceberImportada(ContaReceber $conta, ?int $userId): bool
    {
        $valorBaixa = (float) ($conta->valor_recebido ?? 0);
        if ($valorBaixa <= 0) {
            return false;
        }

        $jaBaixada = BaixaTitulo::query()
            ->where('tipo_titulo', 'conta_receber')
            ->where('titulo_id', $conta->id)
            ->where('estornado', false)
            ->exists();
        if ($jaBaixada) {
            return false;
        }

        $contaBancariaId = $this->resolveContaBancariaImportacaoId();
        if ($contaBancariaId === null) {
            return false;
        }

        $data = $conta->data_recebimento?->format('Y-m-d')
            ?? $conta->data_vencimento?->format('Y-m-d')
            ?? now()->toDateString();

        $movimentacao = MovimentacaoFinanceira::create([
            'tenant_id' => null,
            'conta_bancaria_id' => $contaBancariaId,
            'tipo' => 'entrada',
            'origem' => 'conta_receber',
            'conta_receber_id' => $conta->id,
            'plano_conta_id' => $conta->plano_conta_id,
            'valor' => $valorBaixa,
            'data_movimentacao' => $data,
            'descricao' => 'Recebimento importado (M-OS): ' . ($conta->descricao ?? ('Conta #' . $conta->id)),
            'observacoes' => 'Conciliação automática durante importação (M-OS).',
            'conciliado' => true,
            'data_conciliacao' => $data,
            'conciliado_por' => $userId,
            'created_by' => $userId,
            'updated_by' => $userId,
        ]);

        BaixaTitulo::create([
            'tipo_titulo' => 'conta_receber',
            'titulo_id' => $conta->id,
            'movimentacao_id' => $movimentacao->id,
            'valor_baixa' => $valorBaixa,
            'data_baixa' => $data,
            'juros' => (float) ($conta->juros ?? 0),
            'multa' => (float) ($conta->multa ?? 0),
            'desconto' => (float) ($conta->desconto ?? 0),
            'observacoes' => 'Baixa gerada automaticamente na importação (M-OS).',
            'created_by' => $userId,
            'updated_by' => $userId,
        ]);

        return true;
    }

    protected function conciliarContaPagarImportada(ContaPagar $conta, ?int $userId): bool
    {
        $valorBaixa = (float) ($conta->valor_pago ?? 0);
        if ($valorBaixa <= 0) {
            return false;
        }

        $jaBaixada = BaixaTitulo::query()
            ->where('tipo_titulo', 'conta_pagar')
            ->where('titulo_id', $conta->id)
            ->where('estornado', false)
            ->exists();
        if ($jaBaixada) {
            return false;
        }

        $contaBancariaId = $this->resolveContaBancariaImportacaoId();
        if ($contaBancariaId === null) {
            return false;
        }

        $data = $conta->data_pagamento?->format('Y-m-d')
            ?? $conta->data_vencimento?->format('Y-m-d')
            ?? now()->toDateString();

        $movimentacao = MovimentacaoFinanceira::create([
            'tenant_id' => null,
            'conta_bancaria_id' => $contaBancariaId,
            'tipo' => 'saida',
            'origem' => 'conta_pagar',
            'conta_pagar_id' => $conta->id,
            'plano_conta_id' => $conta->plano_conta_id,
            'valor' => $valorBaixa,
            'data_movimentacao' => $data,
            'descricao' => 'Pagamento importado (M-OS): ' . ($conta->descricao ?? ('Conta #' . $conta->id)),
            'observacoes' => 'Conciliação automática durante importação (M-OS).',
            'conciliado' => true,
            'data_conciliacao' => $data,
            'conciliado_por' => $userId,
            'created_by' => $userId,
            'updated_by' => $userId,
        ]);

        BaixaTitulo::create([
            'tipo_titulo' => 'conta_pagar',
            'titulo_id' => $conta->id,
            'movimentacao_id' => $movimentacao->id,
            'valor_baixa' => $valorBaixa,
            'data_baixa' => $data,
            'juros' => (float) ($conta->juros ?? 0),
            'multa' => (float) ($conta->multa ?? 0),
            'desconto' => (float) ($conta->desconto ?? 0),
            'observacoes' => 'Baixa gerada automaticamente na importação (M-OS).',
            'created_by' => $userId,
            'updated_by' => $userId,
        ]);

        return true;
    }

    protected function normalizeName(string $name): string
    {
        $name = trim(mb_strtolower($name));
        $name = preg_replace('/\s+/', ' ', $name) ?? '';
        return $name;
    }

    /**
     * Sincroniza o auto-increment da tabela ordem_servicos após inserções com ID forçado.
     * Necessário para que novas OS criadas normalmente não colidam com IDs importados.
     */
    protected function resetOrdemServicoAutoIncrement(): void
    {
        try {
            $maxId = (int) \Illuminate\Support\Facades\DB::table('ordem_servicos')->max('id');
            if ($maxId <= 0) {
                return;
            }
            $driver = \Illuminate\Support\Facades\DB::getDriverName();
            if ($driver === 'sqlite') {
                // Só existe após pelo menos um INSERT com AUTOINCREMENT; evita 500 em bases novas.
                if (\Illuminate\Support\Facades\Schema::hasTable('sqlite_sequence')) {
                    \Illuminate\Support\Facades\DB::statement(
                        "INSERT OR REPLACE INTO sqlite_sequence (name, seq) VALUES ('ordem_servicos', ?)",
                        [$maxId]
                    );
                }
            } elseif ($driver === 'mysql' || $driver === 'mariadb') {
                \Illuminate\Support\Facades\DB::statement(
                    'ALTER TABLE ordem_servicos AUTO_INCREMENT = ' . ($maxId + 1)
                );
            }
        } catch (\Throwable $e) {
            // Não-crítico: falha silenciosa; o banco usará max(id)+1 como fallback
        }
    }

    protected function extractOsIdFromText(string $text): ?int
    {
        $text = trim($text);
        if ($text === '') {
            return null;
        }

        // Normaliza variações comuns de acentuação/caracteres para facilitar o match.
        $normalized = strtr($text, [
            'º' => 'o',
            '°' => 'o',
            'ª' => 'a',
            '№' => 'n',
        ]);

        if (preg_match('/\bID\s*\(\s*OS\s*\)\s*:\s*(\d+)\b/iu', $normalized, $m)) {
            return $this->toInt($m[1]);
        }
        // "Alterou uma OS. ID: 5469790" e variações
        if (preg_match('/\bOS\.?\s*ID\s*:\s*(\d+)\b/iu', $normalized, $m)) {
            return $this->toInt($m[1]);
        }
        if (preg_match('/\bID\s*:\s*(\d{4,})\b/u', $normalized, $m)) {
            return $this->toInt($m[1]);
        }
        if (preg_match('/\bOS\s*ID\s*:\s*(\d+)\b/iu', $normalized, $m)) {
            return $this->toInt($m[1]);
        }
        if (preg_match('/\b(?:ORDEM\s+DE\s+SERVICO|OS)\s*(?:N[o]?\s*[:#-]?)?\s*(\d+)\b/iu', $normalized, $m)) {
            return $this->toInt($m[1]);
        }
        if (preg_match('/\bOS\D{0,10}(\d{3,})\b/iu', $normalized, $m)) {
            return $this->toInt($m[1]);
        }
        return null;
    }

    protected function detectAnexoTipo(string $filename): string
    {
        $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
        return match ($ext) {
            'jpg', 'jpeg', 'png', 'gif', 'webp', 'bmp' => 'imagem',
            'pdf' => 'pdf',
            default => 'arquivo',
        };
    }

    protected function guessMimeFromFilename(string $filename): ?string
    {
        $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
        return match ($ext) {
            'jpg', 'jpeg' => 'image/jpeg',
            'png' => 'image/png',
            'gif' => 'image/gif',
            'webp' => 'image/webp',
            'bmp' => 'image/bmp',
            'pdf' => 'application/pdf',
            'txt' => 'text/plain',
            'csv' => 'text/csv',
            'doc' => 'application/msword',
            'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            'xls' => 'application/vnd.ms-excel',
            'xlsx' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            default => null,
        };
    }

    /**
     * Alguns dumps usam nome alternativo da tabela.
     */
    protected function normalizeLegacyDumpTableAlias(string $table): string
    {
        if (
            str_ends_with($table, 'itens_de_vendas')
            || str_ends_with($table, 'itens_de_venda')
            || str_ends_with($table, 'itens_venda')
            || str_ends_with($table, 'itensvenda')
            || str_ends_with($table, 'venda_itens')
            || str_ends_with($table, 'vendas_itens')
        ) {
            return 'produtos_venda';
        }
        if (str_ends_with($table, 'produtos_venda') && $table !== 'produtos_venda') {
            return 'produtos_venda';
        }

        return match ($table) {
            'anotacoes' => 'anotacoes_os',
            'comentarios_os' => 'anotacoes_os',
            'vendas_produtos' => 'produtos_venda',
            'itensdevenda' => 'produtos_venda',
            'itensdevendas' => 'produtos_venda',
            default => $table,
        };
    }

    /**
     * Lista única de tabelas do export legado aceites no INSERT (usada em isSupported + resolução de prefixo).
     *
     * @return list<string>
     */
    protected function supportedLegacyDumpTableNames(): array
    {
        return [
            'emitente',
            'clientes',
            'documentos',
            'equipamentos',
            'equipamentos_os',
            'produtos',
            'servicos',
            'os',
            'produtos_os',
            'servicos_os',
            'lancamentos',
            'vendas',
            'produtos_venda',
            'cobrancas',
            'anotacoes_os',
            'logs',
            'anexos',
        ];
    }

    /**
     * Mesma lista, mais longos primeiro (evita confundir sufixo _os com tabela os).
     *
     * @return list<string>
     */
    protected function legacyExportCanonicalTableNamesByLengthDesc(): array
    {
        static $sorted = null;
        if ($sorted !== null) {
            return $sorted;
        }
        $names = $this->supportedLegacyDumpTableNames();
        usort($names, static fn (string $a, string $b): int => strlen($b) <=> strlen($a));
        $sorted = $names;

        return $sorted;
    }

    /**
     * Converte nome físico do dump (prefixo db/CI, sufixo canónico) para tabela do export reconhecida.
     * Ex.: ci_anotacoes_os → anotacoes_os; ci_anotacoes → anotacoes (alias) → anotacoes_os.
     */
    protected function resolveLegacyDumpTableIdentifier(string $table, int $depth = 0): string
    {
        if ($depth > 10) {
            return strtolower(trim($table));
        }
        $t = strtolower(trim($table));
        $t = $this->normalizeLegacyDumpTableAlias($t);
        if ($this->isSupportedLegacyDumpTable($t)) {
            return $t;
        }
        foreach ($this->legacyExportCanonicalTableNamesByLengthDesc() as $canonical) {
            $suffix = '_' . $canonical;
            if (str_ends_with($t, $suffix)) {
                $prefix = substr($t, 0, -strlen($suffix));
                if ($prefix !== '' && preg_match('/^[a-z0-9_]+$/', $prefix)) {
                    return $canonical;
                }
            }
        }
        if (preg_match('/^([a-z0-9]+)_(.+)$/', $t, $m)) {
            $resolved = $this->resolveLegacyDumpTableIdentifier($m[2], $depth + 1);
            if ($this->isSupportedLegacyDumpTable($resolved)) {
                return $resolved;
            }
        }

        return $this->normalizeLegacyDumpTableAlias($t);
    }

    /**
     * Remove comentários no início de um statement SQL antes de tentar o match do INSERT/REPLACE.
     *
     * Remove em cascata:
     *  - Comentários de bloco versionados MySQL: exclamação-asterisco ... asterisco-barra
     *  - Comentários de bloco normais:           barra-asterisco ... asterisco-barra
     *  - Comentários de linha:                   -- texto até fim da linha
     *  - Comentários de linha MySQL:             # texto até fim da linha
     *
     * Necessário porque dumps do script de export incluem linhas como
     * "-- (sem linhas) `cobrancas`" para tabelas vazias, SEM ponto-e-vírgula terminador.
     * Essas linhas ficam coladas ao início do próximo INSERT (parser acumula pelo `;`),
     * impedindo o match do regex ^INSERT.
     */
    protected function stripLeadingMysqlVersionedComments(string $sql): string
    {
        $changed = true;
        while ($changed) {
            $sql = ltrim($sql);
            $changed = false;

            if (str_starts_with($sql, '/*!') || str_starts_with($sql, '/*')) {
                $close = strpos($sql, '*/');
                if ($close !== false) {
                    $sql = ltrim(substr($sql, $close + 2));
                    $changed = true;
                    continue;
                }
            }

            if (str_starts_with($sql, '--')) {
                $nl = strpos($sql, "\n");
                $sql = $nl !== false ? ltrim(substr($sql, $nl + 1)) : '';
                $changed = true;
                continue;
            }

            if (str_starts_with($sql, '#')) {
                $nl = strpos($sql, "\n");
                $sql = $nl !== false ? ltrim(substr($sql, $nl + 1)) : '';
                $changed = true;
                continue;
            }
        }

        return $sql;
    }

    /**
     * Em importação multi-parte o mapa só tem OS do ZIP corrente; na BD o id da OS costuma coincidir com o da origem.
     *
     * @param  array<int, int>  $osIdMap
     */
    protected function resolveLivreOrdemServicoIdFromSource(int $sourceOsId, array $osIdMap): ?int
    {
        return MosOrdemServicoIdResolver::resolve($sourceOsId, $osIdMap);
    }

    /**
     * Texto da anotação: vários nomes de coluna no dump + fallback pela maior string "útil" da linha.
     */
    protected function extractAnotacaoTextoFromRow(array $row): string
    {
        // Não usar first(): colunas numéricas "0" ou strings só com espaços precisam de regras explícitas.
        $textKeys = [
            'anotacao', 'texto', 'texto_anotacao', 'textoanotacao', 'anotacao_texto',
            'descricao', 'observacao', 'obs', 'nota', 'mensagem',
            'comentario', 'corpo', 'conteudo', 'informacao', 'informacoes', 'historico',
            'detalhe', 'detalhes', 'observacoes', 'anotacoes', 'html', 'rich_text',
            'annotation', 'note', 'notes', 'feedback',
        ];
        foreach ($textKeys as $tk) {
            if (!array_key_exists($tk, $row) || $row[$tk] === null) {
                continue;
            }
            $texto = trim((string) $row[$tk]);
            if ($texto !== '') {
                return $texto;
            }
        }

        $skipKeys = [
            'id', 'idanotacoes', 'id_anotacoes', 'idanotacao', 'os_id', 'idos', 'id_os', 'ordens_servico_id',
            'data_hora', 'data', 'created_at', 'updated_at', 'timestamp', 'dh', 'usuario_id', 'usuarios_id',
            'users_id', 'user_id',
        ];
        $best = '';
        foreach ($row as $k => $v) {
            $key = strtolower((string) $k);
            if (in_array($key, $skipKeys, true)) {
                continue;
            }
            if ($v === null || is_array($v)) {
                continue;
            }
            $s = trim(strip_tags((string) $v));
            if ($s === '' || is_numeric($s) && strlen($s) < 4) {
                continue;
            }
            if (mb_strlen($s) > mb_strlen($best)) {
                $best = $s;
            }
        }

        return $best;
    }

    protected function isSupportedLegacyDumpTable(string $table): bool
    {
        static $set = null;
        if ($set === null) {
            $set = array_flip($this->supportedLegacyDumpTableNames());
        }

        return isset($set[$table]);
    }

    /**
     * Nome canónico da tabela do export a partir do token do INSERT (sem schema, com prefixo/sinónimos resolvidos).
     */
    protected function normalizeInsertTableName(string $tableRaw): string
    {
        $table = str_replace('`', '', $tableRaw);
        if (str_contains($table, '.')) {
            $parts = explode('.', $table);
            $table = (string) end($parts);
        }
        $table = strtolower(trim($table));

        return $this->resolveLegacyDumpTableIdentifier($table);
    }

    /**
     * @param  list<string>  $columns
     * @return array{table:string, columns:array<int,string>, rows:array<int,array<string,mixed>>}|null
     */
    protected function buildParsedInsertRows(string $tableRaw, array $columns, string $valuesRaw): ?array
    {
        $table = $this->normalizeInsertTableName($tableRaw);
        $valuesRaw = rtrim(trim($valuesRaw), ';');
        $tuples = $this->extractTuples($valuesRaw);
        if ($tuples === []) {
            return null;
        }

        $rows = [];
        foreach ($tuples as $tuple) {
            $vals = $this->parseTupleValues($tuple);
            if (count($vals) !== count($columns)) {
                continue;
            }
            $row = [];
            foreach ($columns as $i => $column) {
                $row[$column] = $this->decodeSqlToken($vals[$i]);
            }
            $rows[] = $row;
        }

        if ($rows === []) {
            return null;
        }

        return [
            'table' => $table,
            'columns' => $columns,
            'rows' => $rows,
        ];
    }

    /**
     * Ordem das colunas em dumps sem lista (VALUES apenas): tenta layouts comuns além do formato clássico do export.
     *
     * @param  list<string>  $vals
     * @return list<string>|null
     */
    protected function resolveAnotacoesOsColumnsFromFirstTuple(int $valueCount, array $vals): ?array
    {
        if ($valueCount === 5) {
            return ['idanotacoes', 'anotacao', 'data_hora', 'os_id', 'usuarios_id'];
        }
        if ($valueCount !== 4) {
            return null;
        }

        $candidates = [
            ['idanotacoes', 'anotacao', 'data_hora', 'os_id'],
            ['idanotacoes', 'os_id', 'anotacao', 'data_hora'],
            ['id', 'anotacao', 'data_hora', 'os_id'],
            ['id', 'os_id', 'anotacao', 'data_hora'],
        ];
        foreach ($candidates as $cols) {
            $row = [];
            foreach ($cols as $i => $c) {
                $row[$c] = $this->decodeSqlToken($vals[$i]);
            }
            $osId = $this->toInt($row['os_id'] ?? null);
            if ($osId === null || $osId < 1) {
                continue;
            }
            $dhOk = $this->toDateTime($row['data_hora'] ?? null) !== null;
            $anot = trim((string) ($row['anotacao'] ?? ''));
            if ($dhOk || $anot !== '') {
                return $cols;
            }
        }

        return ['idanotacoes', 'anotacao', 'data_hora', 'os_id'];
    }

    /**
     * Quando o dump não declara colunas (ex.: mysqldump: INSERT INTO `anotacoes_os` VALUES (...);),
     * inferimos nomes pela ordem física do dump de origem.
     *
     * @return list<string>|null
     */
    protected function resolveProdutosVendaColumnsFromFirstTuple(array $vals): ?array
    {
        if (count($vals) !== 6) {
            return null;
        }
        $tok = [];
        foreach ($vals as $raw) {
            $tok[] = $this->decodeSqlToken($raw);
        }
        $classic = ['iditens', 'subtotal', 'quantidade', 'preco', 'vendas_id', 'produtos_id'];
        $alt = ['iditens', 'vendas_id', 'produtos_id', 'quantidade', 'preco', 'subtotal'];
        $errClassic = $this->produtosVendaTupleLayoutPenalty($tok, 1, 2, 3, 4, 5);
        $errAlt = $this->produtosVendaTupleLayoutPenalty($tok, 5, 3, 4, 1, 2);

        return $errAlt < $errClassic ? $alt : $classic;
    }

    /**
     * INSERT sem colunas com 5, 6 ou 7 valores (export legado / forks).
     *
     * @param  list<string>  $vals
     * @return list<string>|null
     */
    protected function resolveProdutosVendaColumnsAnyCount(array $vals): ?array
    {
        $n = count($vals);
        if ($n === 5) {
            $tok = [];
            foreach ($vals as $raw) {
                $tok[] = $this->decodeSqlToken($raw);
            }
            $classic = ['subtotal', 'quantidade', 'preco', 'vendas_id', 'produtos_id'];
            $alt = ['vendas_id', 'produtos_id', 'quantidade', 'preco', 'subtotal'];
            $e1 = $this->produtosVendaTupleLayoutPenalty($tok, 0, 1, 2, 3, 4);
            $e2 = $this->produtosVendaTupleLayoutPenalty($tok, 4, 2, 3, 0, 1);

            return $e2 < $e1 ? $alt : $classic;
        }
        if ($n === 6) {
            return $this->resolveProdutosVendaColumnsFromFirstTuple($vals);
        }
        if ($n === 7) {
            return $this->resolveProdutosVendaSevenTuple($vals);
        }

        return null;
    }

    /**
     * @param  list<string>  $vals
     * @return list<string>|null
     */
    protected function resolveProdutosVendaSevenTuple(array $vals): ?array
    {
        if (count($vals) !== 7) {
            return null;
        }
        $tok = [];
        foreach ($vals as $raw) {
            $tok[] = $this->decodeSqlToken($raw);
        }
        $a = ['iditens', 'subtotal', 'quantidade', 'preco', 'vendas_id', 'produtos_id', 'desconto'];
        $b = ['iditens', 'vendas_id', 'produtos_id', 'quantidade', 'preco', 'subtotal', 'desconto'];
        $eA = $this->produtosVendaTupleLayoutPenalty($tok, 1, 2, 3, 4, 5);
        $eB = $this->produtosVendaTupleLayoutPenalty($tok, 5, 3, 4, 1, 2);

        return $eB < $eA ? $b : $a;
    }

    /**
     * Penalidade menor = layout mais provável (subtotal coerente com q×preço e FKs inteiras).
     *
     * @param  list<mixed>  $tok
     */
    protected function produtosVendaTupleLayoutPenalty(
        array $tok,
        int $iSub,
        int $iQty,
        int $iPreco,
        int $iVenda,
        int $iProd
    ): float {
        $sub = $this->toDecimal($tok[$iSub] ?? null);
        $qty = $this->toDecimal($tok[$iQty] ?? null);
        $pr = $this->toDecimal($tok[$iPreco] ?? null);
        $vid = $this->toInt($tok[$iVenda] ?? null);
        $pid = $this->toInt($tok[$iProd] ?? null);
        $penalty = 0.0;
        if ($vid === null || $vid < 1) {
            $penalty += 1000.0;
        }
        if ($pid === null || $pid < 1) {
            $penalty += 1000.0;
        }
        if ($qty === null || (float) $qty <= 0) {
            $penalty += 100.0;
        }
        if ($pr === null || (float) $pr < 0) {
            $penalty += 100.0;
        }
        if ($penalty >= 2000.0) {
            return $penalty;
        }
        $q = max(0.0001, (float) $qty);
        $p = (float) $pr;
        $expected = round($q * $p, 2);
        $actual = $sub !== null && (float) $sub > 0 ? (float) $sub : $expected;

        return $penalty + abs($actual - $expected);
    }

    /**
     * @return list<string>|null
     */
    protected function guessLegacyDumpColumnsForValuesOnlyInsert(string $tableNormalized, int $valueCount): ?array
    {
        return match ($tableNormalized) {
            'anotacoes_os' => match ($valueCount) {
                4 => ['idanotacoes', 'anotacao', 'data_hora', 'os_id'],
                5 => ['idanotacoes', 'anotacao', 'data_hora', 'os_id', 'usuarios_id'],
                default => null,
            },
            default => null,
        };
    }

    /**
     * INSERT/REPLACE sem lista de colunas — só tabelas com mapeamento conhecido.
     *
     * @return array{table:string, columns:array<int,string>, rows:array<int,array<string,mixed>>}|null
     */
    protected function parseInsertValuesWithoutColumnList(string $tableRaw, string $valuesRaw): ?array
    {
        $table = $this->normalizeInsertTableName($tableRaw);
        if (!$this->isSupportedLegacyDumpTable($table)) {
            return null;
        }

        $valuesRaw = rtrim(trim($valuesRaw), ';');
        $tuples = $this->extractTuples($valuesRaw);
        if ($tuples === []) {
            return null;
        }

        /** @var list<string>|null $columnsRef */
        $columnsRef = null;
        $rows = [];
        foreach ($tuples as $tuple) {
            $vals = $this->parseTupleValues($tuple);
            $n = count($vals);
            if ($columnsRef === null) {
                $columnsRef = $table === 'anotacoes_os'
                    ? $this->resolveAnotacoesOsColumnsFromFirstTuple($n, $vals)
                    : ($table === 'produtos_venda' && $n >= 5 && $n <= 7
                        ? $this->resolveProdutosVendaColumnsAnyCount($vals)
                        : $this->guessLegacyDumpColumnsForValuesOnlyInsert($table, $n));
                if ($columnsRef === null) {
                    return null;
                }
            } elseif ($n !== count($columnsRef)) {
                continue;
            }

            $row = [];
            foreach ($columnsRef as $i => $column) {
                $row[$column] = $this->decodeSqlToken($vals[$i]);
            }
            $rows[] = $row;
        }

        if ($rows === [] || $columnsRef === null) {
            return null;
        }

        return [
            'table' => $table,
            'columns' => $columnsRef,
            'rows' => $rows,
        ];
    }

    /**
     * @return array{table:string, columns:array<int,string>, rows:array<int,array<string,mixed>>}|null
     */
    protected function parseInsertStatement(string $statement): ?array
    {
        $statement = trim($statement);
        if (str_starts_with($statement, "\xEF\xBB\xBF")) {
            $statement = substr($statement, 3);
            $statement = trim($statement);
        }
        $statement = $this->stripLeadingMysqlVersionedComments($statement);

        $insertMods = '(?:\s+(?:IGNORE|LOW_PRIORITY|DELAYED|HIGH_PRIORITY))*';

        // INSERT [IGNORE|...] INTO t (cols) VALUES ...
        if (preg_match('/^\s*INSERT' . $insertMods . '\s+INTO\s+((?:`[^`]+`|\w+)(?:\.(?:`[^`]+`|\w+))?)\s*\((.*?)\)\s*VALUES\s*(.+)\s*$/is', $statement, $m)) {
            $columnsRaw = $m[2];
            $columns = array_map(function (string $col): string {
                return strtolower(trim(str_replace('`', '', $col)));
            }, array_map('trim', explode(',', $columnsRaw)));

            return $this->buildParsedInsertRows($m[1], $columns, $m[3]);
        }

        // REPLACE [LOW_PRIORITY] INTO t (cols) VALUES ...
        if (preg_match('/^\s*REPLACE\s*(?:LOW_PRIORITY\s*)?INTO\s+((?:`[^`]+`|\w+)(?:\.(?:`[^`]+`|\w+))?)\s*\((.*?)\)\s*VALUES\s*(.+)\s*$/is', $statement, $m)) {
            $columns = array_map(function (string $col): string {
                return strtolower(trim(str_replace('`', '', $col)));
            }, array_map('trim', explode(',', $m[2])));

            return $this->buildParsedInsertRows($m[1], $columns, $m[3]);
        }

        // INSERT INTO t VALUES ... — sem colunas (export / phpMyAdmin frequente)
        if (preg_match('/^\s*INSERT' . $insertMods . '\s+INTO\s+((?:`[^`]+`|\w+)(?:\.(?:`[^`]+`|\w+))?)\s+VALUES\s*(.+)\s*$/is', $statement, $m)) {
            return $this->parseInsertValuesWithoutColumnList($m[1], $m[2]);
        }

        if (preg_match('/^\s*REPLACE\s*(?:LOW_PRIORITY\s*)?INTO\s+((?:`[^`]+`|\w+)(?:\.(?:`[^`]+`|\w+))?)\s+VALUES\s*(.+)\s*$/is', $statement, $m)) {
            return $this->parseInsertValuesWithoutColumnList($m[1], $m[2]);
        }

        return null;
    }

    /**
     * @return array<int, string>
     */
    protected function extractTuples(string $valuesRaw): array
    {
        $tuples = [];
        $len = strlen($valuesRaw);
        $inString = false;
        $escaped = false;
        $depth = 0;
        $start = null;

        for ($i = 0; $i < $len; $i++) {
            $c = $valuesRaw[$i];

            if ($inString) {
                if ($escaped) {
                    $escaped = false;
                    continue;
                }
                if ($c === '\\') {
                    $escaped = true;
                    continue;
                }
                if ($c === "'" && $i + 1 < $len && $valuesRaw[$i + 1] === "'") {
                    $i++;
                    continue;
                }
                if ($c === "'") {
                    $inString = false;
                }
                continue;
            }

            if ($c === "'") {
                $inString = true;
                continue;
            }

            if ($c === '(') {
                if ($depth === 0) {
                    $start = $i + 1;
                }
                $depth++;
                continue;
            }

            if ($c === ')') {
                $depth--;
                if ($depth === 0 && $start !== null) {
                    $tuples[] = substr($valuesRaw, $start, $i - $start);
                    $start = null;
                }
            }
        }

        return $tuples;
    }

    /**
     * @return array<int, string>
     */
    protected function parseTupleValues(string $tuple): array
    {
        $values = [];
        $len = strlen($tuple);
        $inString = false;
        $escaped = false;
        $cur = '';

        for ($i = 0; $i < $len; $i++) {
            $c = $tuple[$i];

            if ($inString) {
                if ($escaped) {
                    $cur .= $c;
                    $escaped = false;
                    continue;
                }
                if ($c === '\\') {
                    $cur .= $c;
                    $escaped = true;
                    continue;
                }
                if ($c === "'" && $i + 1 < $len && $tuple[$i + 1] === "'") {
                    $cur .= "''";
                    $i++;
                    continue;
                }
                $cur .= $c;
                if ($c === "'") {
                    $inString = false;
                }
                continue;
            }

            if ($c === "'") {
                $inString = true;
                $cur .= $c;
                continue;
            }

            if ($c === ',') {
                $values[] = trim($cur);
                $cur = '';
                continue;
            }

            $cur .= $c;
        }

        $values[] = trim($cur);
        return $values;
    }

    protected function decodeSqlToken(string $token): mixed
    {
        $token = trim($token);
        if ($token === '' || strtoupper($token) === 'NULL') {
            return null;
        }
        if (strlen($token) >= 2 && $token[0] === "'" && substr($token, -1) === "'") {
            $inner = substr($token, 1, -1);
            $inner = str_replace("''", "'", $inner);
            return stripcslashes($inner);
        }
        if (is_numeric($token)) {
            return str_contains($token, '.') ? (float) $token : (int) $token;
        }
        return $token;
    }

    protected function first(array $row, array $keys): mixed
    {
        foreach ($keys as $key) {
            $k = strtolower($key);
            if (array_key_exists($k, $row) && $row[$k] !== null && $row[$k] !== '') {
                return $row[$k];
            }
        }
        return null;
    }

    protected function limit(mixed $value, int $limit): ?string
    {
        if ($value === null) {
            return null;
        }
        $v = trim((string) $value);
        if ($v === '') {
            return null;
        }
        return mb_substr($v, 0, $limit);
    }

    /**
     * Coluna {@see Produto} `estoque_quantidade`: DECIMAL(12,6) → |valor| ≤ 999999,999999.
     * Exports M-OS / MapOS usam INT máximo (2147483647) ou quantidades absurdas como marcador;
     * gravar esses valores quebra o INSERT no MySQL.
     */
    protected function normalizeProdutoEstoqueQuantidade(?float $value): ?float
    {
        if ($value === null) {
            return null;
        }
        if (! is_finite($value)) {
            return null;
        }
        $max = 999999.999999;
        if (abs($value) > $max) {
            return null;
        }

        return round($value, 6);
    }

    protected function toDecimal(mixed $value): ?float
    {
        if ($value === null || $value === '') {
            return null;
        }
        if (is_numeric($value)) {
            return (float) $value;
        }
        $v = trim((string) $value);
        $v = str_replace(["\xc2\xa0", ' '], '', $v);
        if (str_contains($v, ',') && str_contains($v, '.')) {
            $v = str_replace('.', '', $v);
            $v = str_replace(',', '.', $v);
        } elseif (str_contains($v, ',')) {
            $v = str_replace(',', '.', $v);
        }
        if (!is_numeric($v)) {
            return null;
        }
        return (float) $v;
    }

    protected function toInt(mixed $value): ?int
    {
        if ($value === null || $value === '') {
            return null;
        }
        if (is_bool($value)) {
            return null;
        }
        if (is_int($value)) {
            return $value;
        }
        if (is_float($value)) {
            return (int) $value;
        }
        $v = trim((string) $value);
        if ($v === '') {
            return null;
        }

        return is_numeric($v) ? (int) $v : null;
    }

    protected function toBool(mixed $value): ?bool
    {
        if ($value === null || $value === '') {
            return null;
        }
        if (is_bool($value)) {
            return $value;
        }
        if (is_numeric($value)) {
            return (int) $value === 1;
        }
        $v = mb_strtolower(trim((string) $value));
        if (in_array($v, ['1', 'true', 'sim', 'yes'], true)) {
            return true;
        }
        if (in_array($v, ['0', 'false', 'nao', 'não', 'no'], true)) {
            return false;
        }
        return null;
    }

    protected function toDate(mixed $value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }
        $v = trim((string) $value);
        if ($v === '0000-00-00' || $v === '0000-00-00 00:00:00') {
            return null;
        }
        try {
            return \Carbon\Carbon::parse($v)->format('Y-m-d');
        } catch (\Throwable) {
            return null;
        }
    }

    protected function toDateTime(mixed $value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }
        $v = trim((string) $value);
        if ($v === '0000-00-00' || $v === '0000-00-00 00:00:00') {
            return null;
        }
        try {
            return \Carbon\Carbon::parse($v)->format('Y-m-d H:i:s');
        } catch (\Throwable) {
            return null;
        }
    }

    /**
     * Percorre o ficheiro SQL em blocos e emite instruções completas (terminadas em `;` fora de strings).
     * Evita carregar o dump inteiro em memória (útil com ZIP/export grandes).
     *
     * @return \Generator<int, string>
     */
    protected function iterateStatementsFromPath(string $path): \Generator
    {
        $handle = fopen($path, 'rb');
        if ($handle === false) {
            throw new \RuntimeException('Não foi possível abrir o ficheiro SQL para importação: ' . $path);
        }

        $buffer = '';
        $stmtStart = 0;
        $inString = false;
        $escaped = false;
        $inLineComment = false;
        $chunkSize = 1024 * 1024;

        try {
            while (true) {
                if (!feof($handle)) {
                    $chunk = fread($handle, $chunkSize);
                    if ($chunk !== false && $chunk !== '') {
                        $buffer .= $chunk;
                    }
                }

                $len = strlen($buffer);
                for ($i = 0; $i < $len; $i++) {
                    $c = $buffer[$i];
                    if ($inLineComment) {
                        if ($c === "\n" || $c === "\r") {
                            $inLineComment = false;
                        }

                        continue;
                    }
                    if ($inString) {
                        if ($escaped) {
                            $escaped = false;

                            continue;
                        }
                        if ($c === '\\') {
                            $escaped = true;

                            continue;
                        }
                        if ($c === "'" && $i + 1 < $len && $buffer[$i + 1] === "'") {
                            $i++;

                            continue;
                        }
                        if ($c === "'") {
                            $inString = false;
                        }

                        continue;
                    }
                    if ($c === "'") {
                        $inString = true;

                        continue;
                    }
                    if ($c === '-' && $i + 1 < $len && $buffer[$i + 1] === '-') {
                        $inLineComment = true;
                        $i++;

                        continue;
                    }
                    if ($c === '#' && ($i === 0 || $buffer[$i - 1] === "\n" || $buffer[$i - 1] === "\r")) {
                        $inLineComment = true;

                        continue;
                    }
                    if ($c === ';') {
                        $stmt = substr($buffer, $stmtStart, $i - $stmtStart);
                        $stmtStart = $i + 1;
                        $trimmed = trim($stmt);
                        if (str_starts_with($trimmed, "\xEF\xBB\xBF")) {
                            $trimmed = trim(substr($trimmed, 3));
                        }
                        if ($trimmed !== '') {
                            yield $trimmed;
                        }
                    }
                }

                $buffer = substr($buffer, $stmtStart);
                $stmtStart = 0;

                if (feof($handle)) {
                    $tail = trim($buffer);
                    if (str_starts_with($tail, "\xEF\xBB\xBF")) {
                        $tail = trim(substr($tail, 3));
                    }
                    if ($tail !== '') {
                        yield $tail;
                    }
                    break;
                }
            }
        } finally {
            fclose($handle);
        }
    }

    /**
     * @return array<int, string>
     */
    protected function splitSqlStatements(string $sql): array
    {
        $statements = [];
        $len = strlen($sql);
        $start = 0;
        $inString = false;
        $escaped = false;
        $inLineComment = false;

        for ($i = 0; $i < $len; $i++) {
            $c = $sql[$i];
            if ($inLineComment) {
                if ($c === "\n" || $c === "\r") {
                    $inLineComment = false;
                }
                continue;
            }
            if ($inString) {
                if ($escaped) {
                    $escaped = false;
                    continue;
                }
                if ($c === '\\') {
                    $escaped = true;
                    continue;
                }
                if ($c === "'" && $i + 1 < $len && $sql[$i + 1] === "'") {
                    $i++;
                    continue;
                }
                if ($c === "'") {
                    $inString = false;
                }
                continue;
            }
            if ($c === "'") {
                $inString = true;
                continue;
            }
            if ($c === '-' && $i + 1 < $len && $sql[$i + 1] === '-') {
                $inLineComment = true;
                $i++;
                continue;
            }
            if ($c === '#' && ($i === 0 || $sql[$i - 1] === "\n" || $sql[$i - 1] === "\r")) {
                $inLineComment = true;
                continue;
            }
            if ($c === ';') {
                $statements[] = substr($sql, $start, $i - $start);
                $start = $i + 1;
            }
        }

        $rest = trim(substr($sql, $start));
        if ($rest !== '') {
            $statements[] = $rest;
        }

        return $statements;
    }

    protected function resolveFormaPagamentoLegacyId(mixed $formaPgto): ?int
    {
        $raw = trim((string) ($formaPgto ?? ''));
        if ($raw === '') {
            return null;
        }

        $key = mb_strtolower($raw);
        if (array_key_exists($key, $this->formaPagamentoCache)) {
            return $this->formaPagamentoCache[$key];
        }

        $normalizada = trim((string) preg_replace('/\s+/', ' ', $key));
        $formaId = FormaPagamento::query()
            ->whereRaw('LOWER(TRIM(nome)) = ?', [$normalizada])
            ->value('id');

        if ($formaId === null) {
            $formaId = FormaPagamento::query()
                ->whereRaw('LOWER(nome) LIKE ?', ['%' . $normalizada . '%'])
                ->orderByDesc('ativo')
                ->orderBy('id')
                ->value('id');
        }

        $this->formaPagamentoCache[$key] = $formaId !== null ? (int) $formaId : null;
        return $this->formaPagamentoCache[$key];
    }
}

