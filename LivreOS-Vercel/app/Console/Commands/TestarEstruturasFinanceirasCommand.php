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

namespace App\Console\Commands;

use App\Models\ContaPagar;
use App\Models\ContaReceber;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Builder;

class TestarEstruturasFinanceirasCommand extends Command
{
    protected $signature = 'financeiro:testar-estruturas
        {--tipo=all : receber|pagar|all}
        {--limite=50 : Maximo de exemplos por regra}
        {--estrito-snapshot : Trata ausencia de snapshot como inconsistencia critica}';

    protected $description = 'Valida consistencia de agrupamento e desmembramento em contas a receber/pagar';

    public function handle(): int
    {
        $tipo = strtolower((string) $this->option('tipo'));
        $limite = max(1, (int) $this->option('limite'));

        if (!in_array($tipo, ['receber', 'pagar', 'all'], true)) {
            $this->error("Opcao --tipo invalida: {$tipo}. Use receber, pagar ou all.");
            return self::INVALID;
        }

        $estritoSnapshot = (bool) $this->option('estrito-snapshot');
        $checagens = [];
        if ($tipo === 'all' || $tipo === 'receber') {
            $checagens[] = $this->validarEstrutura('contas_receber', ContaReceber::class, $limite, $estritoSnapshot);
        }
        if ($tipo === 'all' || $tipo === 'pagar') {
            $checagens[] = $this->validarEstrutura('contas_pagar', ContaPagar::class, $limite, $estritoSnapshot);
        }

        $totalInconsistencias = array_sum(array_column($checagens, 'inconsistencias'));
        $totalAlertas = array_sum(array_column($checagens, 'alertas'));
        $this->newLine();
        $this->info('Resumo final:');
        foreach ($checagens as $c) {
            $this->line("- {$c['nome']}: {$c['inconsistencias']} inconsistencias, {$c['alertas']} alertas");
        }

        if ($totalInconsistencias > 0) {
            $this->newLine();
            $this->error("Foram encontradas {$totalInconsistencias} inconsistencias no total.");
            return self::FAILURE;
        }

        $this->newLine();
        if ($totalAlertas > 0) {
            $this->warn("Nenhuma inconsistencia critica encontrada, mas existem {$totalAlertas} alerta(s) de snapshot legado.");
            return self::SUCCESS;
        }

        $this->info('Nenhuma inconsistencia encontrada nas estruturas financeiras.');
        return self::SUCCESS;
    }

    /**
     * @return array{nome:string,inconsistencias:int,alertas:int}
     */
    private function validarEstrutura(string $nome, string $modelClass, int $limite, bool $estritoSnapshot): array
    {
        $this->newLine();
        $this->info("Validando {$nome}...");

        $regrasCriticas = [
            'lote_filho_sem_pai' => $modelClass::query()
                ->where('estrutura_tipo', 'lote_filho')
                ->whereNull('parent_id'),

            'lote_filho_pai_invalido' => $modelClass::query()
                ->where('estrutura_tipo', 'lote_filho')
                ->whereNotNull('parent_id')
                ->whereDoesntHave('parent', function (Builder $q): void {
                    $q->where('estrutura_tipo', 'lote_pai');
                }),

            'lote_pai_sem_filho' => $modelClass::query()
                ->where('estrutura_tipo', 'lote_pai')
                ->whereDoesntHave('children', function (Builder $q): void {
                    $q->where('estrutura_tipo', 'lote_filho');
                }),

            'desmembrado_filho_sem_pai' => $modelClass::query()
                ->where('estrutura_tipo', 'desmembrado_filho')
                ->whereNull('parent_id'),

            'desmembrado_filho_pai_invalido' => $modelClass::query()
                ->where('estrutura_tipo', 'desmembrado_filho')
                ->whereNotNull('parent_id')
                ->whereDoesntHave('parent', function (Builder $q): void {
                    $q->where('estrutura_tipo', 'desmembrado_pai');
                }),

            'desmembrado_pai_sem_filho' => $modelClass::query()
                ->where('estrutura_tipo', 'desmembrado_pai')
                ->whereDoesntHave('children', function (Builder $q): void {
                    $q->where('estrutura_tipo', 'desmembrado_filho');
                }),
        ];

        $regrasAlerta = [
            'lote_filho_sem_snapshot_agrupamento' => $modelClass::query()
                ->where('estrutura_tipo', 'lote_filho')
                ->where(function (Builder $q): void {
                    $q->whereNull('metadata')
                        ->orWhereRaw("JSON_EXTRACT(metadata, '$.agrupamento_origem') IS NULL");
                }),
        ];

        $inconsistencias = 0;
        $alertas = 0;

        foreach ($regrasCriticas as $regra => $query) {
            $count = (clone $query)->count();
            $inconsistencias += $count;

            if ($count === 0) {
                $this->line("  [OK] {$regra}");
                continue;
            }

            $this->warn("  [ERRO] {$regra}: {$count}");

            $exemplos = (clone $query)
                ->orderBy('id')
                ->limit($limite)
                ->get(['id', 'parent_id', 'estrutura_tipo', 'status_estrutura', 'status'])
                ->map(fn ($m) => [
                    'id' => (int) $m->id,
                    'parent_id' => $m->parent_id ? (int) $m->parent_id : null,
                    'estrutura_tipo' => (string) $m->estrutura_tipo,
                    'status_estrutura' => (string) $m->status_estrutura,
                    'status' => (string) $m->status,
                ])
                ->all();

            foreach ($exemplos as $ex) {
                $this->line('    - id=' . $ex['id']
                    . ', parent_id=' . ($ex['parent_id'] ?? 'null')
                    . ', estrutura=' . $ex['estrutura_tipo']
                    . ', status_estrutura=' . $ex['status_estrutura']
                    . ', status=' . $ex['status']);
            }
        }

        foreach ($regrasAlerta as $regra => $query) {
            $count = (clone $query)->count();
            if ($estritoSnapshot) {
                $inconsistencias += $count;
            } else {
                $alertas += $count;
            }

            if ($count === 0) {
                $this->line("  [OK] {$regra}");
                continue;
            }

            if ($estritoSnapshot) {
                $this->warn("  [ERRO] {$regra}: {$count}");
            } else {
                $this->warn("  [ALERTA] {$regra}: {$count} (dados legados podem nao ter snapshot)");
            }

            $exemplos = (clone $query)
                ->orderBy('id')
                ->limit($limite)
                ->get(['id', 'parent_id', 'estrutura_tipo', 'status_estrutura', 'status'])
                ->map(fn ($m) => [
                    'id' => (int) $m->id,
                    'parent_id' => $m->parent_id ? (int) $m->parent_id : null,
                    'estrutura_tipo' => (string) $m->estrutura_tipo,
                    'status_estrutura' => (string) $m->status_estrutura,
                    'status' => (string) $m->status,
                ])
                ->all();

            foreach ($exemplos as $ex) {
                $this->line('    - id=' . $ex['id']
                    . ', parent_id=' . ($ex['parent_id'] ?? 'null')
                    . ', estrutura=' . $ex['estrutura_tipo']
                    . ', status_estrutura=' . $ex['status_estrutura']
                    . ', status=' . $ex['status']);
            }
        }

        return [
            'nome' => $nome,
            'inconsistencias' => $inconsistencias,
            'alertas' => $alertas,
        ];
    }
}

