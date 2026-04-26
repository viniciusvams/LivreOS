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
use App\Models\TaxaAdquirente;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class AdquirenteService
{
    /**
     * Simula uma transação e retorna os valores calculados
     * 
     * @param float $valorVenda Valor da venda
     * @param int $parcelas Número de parcelas (1 = à vista)
     * @param int $adquirenteId ID da adquirente
     * @param string $bandeira Bandeira do cartão (master, visa, elo, amex, etc)
     * @param bool $antecipado Se é recebimento antecipado
     * @param Carbon|null $dataVenda Data da venda (padrão: hoje)
     * @return array
     */
    public function simularTransacao(
        float $valorVenda,
        int $parcelas,
        int $adquirenteId,
        string $bandeira = 'master',
        bool $antecipado = false,
        ?Carbon $dataVenda = null
    ): array {
        $dataVenda = $dataVenda ?? Carbon::now();
        
        $adquirente = Adquirente::findOrFail($adquirenteId);
        
        // Determinar modalidade
        $modalidade = $this->determinarModalidade($parcelas);
        
        // Buscar taxa aplicável (primeiro tenta com antecipação, depois sem)
        $taxa = $this->buscarTaxaAplicavel(
            $adquirenteId,
            $bandeira,
            $modalidade,
            $parcelas,
            $antecipado
        );
        
        // Se não encontrou e está tentando antecipado, tenta sem antecipação
        if (!$taxa && $antecipado) {
            $taxa = $this->buscarTaxaAplicavel(
                $adquirenteId,
                $bandeira,
                $modalidade,
                $parcelas,
                false
            );
        }
        
        if (!$taxa) {
            throw new \Exception("Nenhuma taxa encontrada para os parâmetros informados.");
        }
        
        // Calcular taxa total
        $taxaTotal = $this->calcularTaxaTotal($valorVenda, $parcelas, $taxa);
        
        // Calcular valor líquido
        $valorLiquido = $valorVenda - $taxaTotal;
        
        // Calcular agenda de recebíveis
        $agendaRecebiveis = $this->calcularAgendaRecebiveis(
            $valorLiquido,
            $parcelas,
            $taxa,
            $adquirente,
            $antecipado,
            $dataVenda
        );
        
        return [
            'valor_venda' => $valorVenda,
            'parcelas' => $parcelas,
            'bandeira' => $bandeira,
            'modalidade' => $modalidade,
            'antecipado' => $antecipado,
            'taxa_aplicada' => [
                'id' => $taxa->id,
                'taxa_percentual' => $taxa->taxa_percentual,
                'taxa_por_parcela' => $taxa->taxa_por_parcela,
                'taxa_fixa' => $taxa->taxa_fixa,
                'taxa_antecipacao' => $taxa->taxa_antecipacao_percentual,
            ],
            'taxa_total_aplicada' => $taxaTotal,
            'valor_liquido' => $valorLiquido,
            'agenda_recebiveis' => $agendaRecebiveis,
            'adquirente' => [
                'id' => $adquirente->id,
                'nome' => $adquirente->nome,
            ],
        ];
    }

    /**
     * Determina a modalidade baseada no número de parcelas
     * Nota: Débito precisa ser informado separadamente, esta função assume crédito
     */
    protected function determinarModalidade(int $parcelas): string
    {
        if ($parcelas === 1) {
            return 'credito_vista';
        }
        
        return 'credito_parcelado';
    }
    
    /**
     * Simula transação com débito
     */
    public function simularTransacaoDebito(
        float $valorVenda,
        int $adquirenteId,
        string $bandeira = 'master',
        ?Carbon $dataVenda = null
    ): array {
        $dataVenda = $dataVenda ?? Carbon::now();
        
        $adquirente = Adquirente::findOrFail($adquirenteId);
        
        // Buscar taxa de débito (adquirentes podem cadastrar com usa_antecipacao true ou false)
        $taxa = $this->buscarTaxaAplicavel(
            $adquirenteId,
            $bandeira,
            'debito',
            1,
            false
        );
        if (!$taxa) {
            $taxa = $this->buscarTaxaAplicavel(
                $adquirenteId,
                $bandeira,
                'debito',
                1,
                true
            );
        }
        if (!$taxa) {
            throw new \Exception("Nenhuma taxa de débito encontrada para os parâmetros informados. Cadastre taxas de débito em Financeiro > Adquirentes > Gerenciar taxas.");
        }
        
        // Calcular taxa total
        $taxaTotal = $this->calcularTaxaTotal($valorVenda, 1, $taxa);
        
        // Calcular valor líquido
        $valorLiquido = $valorVenda - $taxaTotal;
        
        // Calcular agenda de recebíveis
        $agendaRecebiveis = $this->calcularAgendaRecebiveis(
            $valorLiquido,
            1,
            $taxa,
            $adquirente,
            false,
            $dataVenda
        );
        
        return [
            'valor_venda' => $valorVenda,
            'parcelas' => 1,
            'bandeira' => $bandeira,
            'modalidade' => 'debito',
            'antecipado' => false,
            'taxa_aplicada' => [
                'id' => $taxa->id,
                'taxa_percentual' => $taxa->taxa_percentual,
                'taxa_por_parcela' => $taxa->taxa_por_parcela,
                'taxa_fixa' => $taxa->taxa_fixa,
                'taxa_antecipacao' => $taxa->taxa_antecipacao_percentual,
            ],
            'taxa_total_aplicada' => $taxaTotal,
            'valor_liquido' => $valorLiquido,
            'agenda_recebiveis' => $agendaRecebiveis,
            'adquirente' => [
                'id' => $adquirente->id,
                'nome' => $adquirente->nome,
            ],
        ];
    }

    /**
     * Estima taxa e valor líquido para baixa/cadastro (cartão). Não lança exceção — retorna null para usar taxa da forma.
     *
     * @return array{taxa: float, valor_liquido: float, adquirente_nome: string}|null
     */
    public function estimativaTaxaRecebimento(
        float $valor,
        int $totalParcelasContrato,
        int $adquirenteId,
        string $formaTipo,
        string $bandeira = 'master',
        bool $antecipado = false,
        ?Carbon $dataReferencia = null
    ): ?array {
        $formaTipo = strtolower(trim($formaTipo));
        if (! in_array($formaTipo, ['cartao_credito', 'cartao_debito'], true)) {
            return null;
        }

        $bandeira = strtolower(trim($bandeira !== '' ? $bandeira : 'master'));
        $dataReferencia = $dataReferencia ?? Carbon::now();
        $adquirente = Adquirente::query()->where('id', $adquirenteId)->where('ativo', true)->first();
        if (! $adquirente) {
            return null;
        }

        try {
            if ($formaTipo === 'cartao_debito') {
                $r = $this->simularTransacaoDebito($valor, $adquirenteId, $bandeira, $dataReferencia);
            } else {
                $parcelas = max(1, min($totalParcelasContrato, 120));
                $r = $this->simularTransacao($valor, $parcelas, $adquirenteId, $bandeira, $antecipado, $dataReferencia);
            }

            return [
                'taxa' => (float) $r['taxa_total_aplicada'],
                'valor_liquido' => (float) $r['valor_liquido'],
                'adquirente_nome' => (string) ($r['adquirente']['nome'] ?? $adquirente->nome),
            ];
        } catch (\Throwable) {
            return null;
        }
    }

    /**
     * Busca a taxa aplicável para os parâmetros informados
     * Tenta primeiro a bandeira específica, depois 'outros' como fallback
     */
    protected function buscarTaxaAplicavel(
        int $adquirenteId,
        string $bandeira,
        string $modalidade,
        int $parcelas,
        bool $antecipado
    ): ?TaxaAdquirente {
        // Tentar primeiro com a bandeira específica
        $taxa = $this->buscarTaxaPorBandeira($adquirenteId, $bandeira, $modalidade, $parcelas, $antecipado);
        
        // Se não encontrar e não for 'outros', tentar com 'outros'
        if (!$taxa && $bandeira !== 'outros') {
            $taxa = $this->buscarTaxaPorBandeira($adquirenteId, 'outros', $modalidade, $parcelas, $antecipado);
        }
        
        return $taxa;
    }

    /**
     * Busca taxa para uma bandeira específica
     */
    protected function buscarTaxaPorBandeira(
        int $adquirenteId,
        string $bandeira,
        string $modalidade,
        int $parcelas,
        bool $antecipado
    ): ?TaxaAdquirente {
        $query = TaxaAdquirente::where('adquirente_id', $adquirenteId)
            ->where('bandeira', $bandeira)
            ->where('modalidade', $modalidade)
            ->where('ativo', true)
            ->where(function($q) use ($antecipado) {
                // Se busca antecipado, prioriza taxas com antecipação
                // Se não busca antecipado, busca apenas taxas sem antecipação
                if ($antecipado) {
                    $q->where('usa_antecipacao', true);
                } else {
                    $q->where('usa_antecipacao', false);
                }
            })
            ->where(function($q) {
                $q->whereNull('data_inicio')
                  ->orWhere('data_inicio', '<=', now());
            })
            ->where(function($q) {
                $q->whereNull('data_fim')
                  ->orWhere('data_fim', '>=', now());
            });
        
        // Se for parcelado, filtrar por faixa de parcelas
        if ($modalidade === 'credito_parcelado') {
            $query->where(function($q) use ($parcelas) {
                $q->whereNull('parcelas_min')
                  ->orWhere('parcelas_min', '<=', $parcelas);
            })
            ->where(function($q) use ($parcelas) {
                $q->whereNull('parcelas_max')
                  ->orWhere('parcelas_max', '>=', $parcelas);
            });
            
            // Ordenar por especificidade (taxas mais específicas primeiro)
            $query->orderByRaw('CASE 
                WHEN parcelas_min IS NOT NULL AND parcelas_max IS NOT NULL THEN 1
                WHEN parcelas_min IS NOT NULL OR parcelas_max IS NOT NULL THEN 2
                ELSE 3
            END')
            ->orderBy('parcelas_min', 'desc')
            ->orderBy('parcelas_max', 'asc');
        } else {
            // Para débito e crédito à vista, ordenar por bandeira (específica primeiro)
            $query->orderByRaw("CASE WHEN bandeira = 'outros' THEN 2 ELSE 1 END");
        }
        
        return $query->first();
    }

    /**
     * Calcula a taxa total aplicada
     * 
     * Fórmula: (valor * taxa_percentual / 100) + (valor * taxa_por_parcela * parcelas / 100) + taxa_fixa
     * Se antecipado: adiciona taxa_antecipacao_percentual
     */
    protected function calcularTaxaTotal(float $valorVenda, int $parcelas, TaxaAdquirente $taxa): float
    {
        // Taxa percentual fixa
        $taxaPercentual = ($valorVenda * $taxa->taxa_percentual) / 100;
        
        // Taxa por parcela (só aplica se for parcelado)
        $taxaPorParcela = 0;
        if ($parcelas > 1 && $taxa->taxa_por_parcela > 0) {
            $taxaPorParcela = ($valorVenda * $taxa->taxa_por_parcela * $parcelas) / 100;
        }
        
        // Taxa fixa
        $taxaFixa = $taxa->taxa_fixa;
        
        // Taxa de antecipação (se aplicável)
        $taxaAntecipacao = 0;
        if ($taxa->usa_antecipacao && $taxa->taxa_antecipacao_percentual) {
            $taxaAntecipacao = ($valorVenda * $taxa->taxa_antecipacao_percentual) / 100;
        }
        
        $total = $taxaPercentual + $taxaPorParcela + $taxaFixa + $taxaAntecipacao;
        
        return round($total, 2);
    }

    /**
     * Calcula a agenda de recebíveis (fluxo de caixa)
     */
    protected function calcularAgendaRecebiveis(
        float $valorLiquido,
        int $parcelas,
        TaxaAdquirente $taxa,
        Adquirente $adquirente,
        bool $antecipado,
        Carbon $dataVenda
    ): array {
        $agenda = [];
        
        if ($antecipado && $adquirente->permite_antecipacao_parcelado) {
            // Cenário B: Antecipação - recebe tudo em D+1 ou D+2
            $dataRecebimento = $dataVenda->copy()->addDays($adquirente->dias_antecipacao);
            $agenda[] = [
                'data' => $dataRecebimento->format('Y-m-d'),
                'valor' => round($valorLiquido, 2),
                'parcela' => null,
                'total_parcelas' => $parcelas,
            ];
        } else {
            // Cenário A: Fluxo normal
            $valorParcela = $valorLiquido / $parcelas;
            
            if ($taxa->modalidade === 'debito') {
                // Débito: recebe em D+1
                $dataRecebimento = $dataVenda->copy()->addDays($taxa->dias_recebimento_debito);
                $agenda[] = [
                    'data' => $dataRecebimento->format('Y-m-d'),
                    'valor' => round($valorLiquido, 2),
                    'parcela' => null,
                    'total_parcelas' => 1,
                ];
            } elseif ($taxa->modalidade === 'credito_vista') {
                // Crédito à vista: recebe em D+30 (ou conforme configurado)
                $dataRecebimento = $dataVenda->copy()->addDays($taxa->dias_recebimento_credito_vista);
                $agenda[] = [
                    'data' => $dataRecebimento->format('Y-m-d'),
                    'valor' => round($valorLiquido, 2),
                    'parcela' => null,
                    'total_parcelas' => 1,
                ];
            } else {
                // Crédito parcelado: recebe parcela a parcela
                for ($i = 1; $i <= $parcelas; $i++) {
                    $dias = $taxa->dias_recebimento_credito_vista + (($i - 1) * $taxa->dias_recebimento_parcela);
                    $dataRecebimento = $dataVenda->copy()->addDays($dias);
                    
                    $agenda[] = [
                        'data' => $dataRecebimento->format('Y-m-d'),
                        'valor' => round($valorParcela, 2),
                        'parcela' => $i,
                        'total_parcelas' => $parcelas,
                    ];
                }
            }
        }
        
        return $agenda;
    }

    /**
     * Busca todas as taxas disponíveis para uma adquirente
     */
    public function listarTaxasDisponiveis(int $adquirenteId): Collection
    {
        return TaxaAdquirente::where('adquirente_id', $adquirenteId)
            ->where('ativo', true)
            ->where(function($q) {
                $q->whereNull('data_fim')
                  ->orWhere('data_fim', '>=', now());
            })
            ->orderBy('bandeira')
            ->orderBy('modalidade')
            ->orderBy('parcelas_min')
            ->get();
    }

    /**
     * Compara diferentes adquirentes para uma mesma transação
     */
    public function compararAdquirentes(
        float $valorVenda,
        int $parcelas,
        array $adquirenteIds,
        string $bandeira = 'master',
        bool $antecipado = false
    ): array {
        $comparacao = [];
        
        foreach ($adquirenteIds as $adquirenteId) {
            try {
                $resultado = $this->simularTransacao(
                    $valorVenda,
                    $parcelas,
                    $adquirenteId,
                    $bandeira,
                    $antecipado
                );
                
                $comparacao[] = $resultado;
            } catch (\Exception $e) {
                // Ignorar adquirentes sem taxa configurada
                continue;
            }
        }
        
        // Ordenar por valor líquido (maior primeiro)
        usort($comparacao, function($a, $b) {
            return $b['valor_liquido'] <=> $a['valor_liquido'];
        });
        
        return $comparacao;
    }
}
