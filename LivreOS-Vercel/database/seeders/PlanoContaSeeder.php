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

namespace Database\Seeders;

use App\Models\PlanoConta;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class PlanoContaSeeder extends Seeder
{
    public function run(): void
    {
        // Salva mapeamento id -> codigo dos planos ATIVOS antes de apagar (para restaurar referências depois)
        $oldMapping = DB::table('plano_contas')
            ->whereNull('deleted_at')
            ->pluck('codigo', 'id')
            ->toArray();

        // Desabilita FKs para não zerarem plano_conta_id nas outras tabelas pelo cascade
        if (DB::getDriverName() === 'sqlite') {
            DB::statement('PRAGMA foreign_keys = OFF');
        } else {
            DB::statement('SET FOREIGN_KEY_CHECKS=0');
        }

        // Apaga tudo (inclusive soft-deleted) via raw SQL
        DB::table('plano_contas')->update(['pai_id' => null]);
        DB::table('plano_contas')->delete();

        // Reseta numeração
        if (DB::getDriverName() === 'sqlite') {
            DB::table('sqlite_sequence')->where('name', 'plano_contas')->delete();
        } else {
            DB::statement('ALTER TABLE plano_contas AUTO_INCREMENT = 1');
        }

        // Reabilita FKs
        if (DB::getDriverName() === 'sqlite') {
            DB::statement('PRAGMA foreign_keys = ON');
        } else {
            DB::statement('SET FOREIGN_KEY_CHECKS=1');
        }

        // RECEITAS
        // 1. Receitas Operacionais
        $receitasOperacionais = PlanoConta::create([
            'codigo' => '1',
            'nome' => 'Receitas Operacionais',
            'tipo' => 'receita',
            'categoria' => 'operacional',
            'descricao' => 'Receitas provenientes das atividades principais da empresa',
            'ativo' => true,
            'ordem' => 1,
        ]);

        PlanoConta::create([
            'pai_id' => $receitasOperacionais->id,
            'codigo' => '1.1',
            'nome' => 'Receita de Serviços',
            'tipo' => 'receita',
            'categoria' => 'operacional',
            'descricao' => 'Receitas provenientes da prestação de serviços',
            'ativo' => true,
            'ordem' => 1,
        ]);

        PlanoConta::create([
            'pai_id' => $receitasOperacionais->id,
            'codigo' => '1.2',
            'nome' => 'Receita de Vendas',
            'tipo' => 'receita',
            'categoria' => 'operacional',
            'descricao' => 'Receitas provenientes da venda de produtos',
            'ativo' => true,
            'ordem' => 2,
        ]);

        PlanoConta::create([
            'pai_id' => $receitasOperacionais->id,
            'codigo' => '1.3',
            'nome' => 'Receita de Manutenção',
            'tipo' => 'receita',
            'categoria' => 'operacional',
            'descricao' => 'Receitas provenientes de serviços de manutenção',
            'ativo' => true,
            'ordem' => 3,
        ]);

        PlanoConta::create([
            'pai_id' => $receitasOperacionais->id,
            'codigo' => '1.4',
            'nome' => 'Receita de Instalação',
            'tipo' => 'receita',
            'categoria' => 'operacional',
            'descricao' => 'Receitas provenientes de serviços de instalação',
            'ativo' => true,
            'ordem' => 4,
        ]);

        PlanoConta::create([
            'pai_id' => $receitasOperacionais->id,
            'codigo' => '1.5',
            'nome' => 'Receita de Locação',
            'tipo' => 'receita',
            'categoria' => 'operacional',
            'descricao' => 'Receitas provenientes de locação de equipamentos',
            'ativo' => true,
            'ordem' => 5,
        ]);

        // 2. Receitas Não Operacionais
        $receitasNaoOperacionais = PlanoConta::create([
            'codigo' => '2',
            'nome' => 'Receitas Não Operacionais',
            'tipo' => 'receita',
            'categoria' => 'nao_operacional',
            'descricao' => 'Receitas provenientes de atividades não relacionadas à operação principal',
            'ativo' => true,
            'ordem' => 2,
        ]);

        PlanoConta::create([
            'pai_id' => $receitasNaoOperacionais->id,
            'codigo' => '2.1',
            'nome' => 'Receitas Financeiras',
            'tipo' => 'receita',
            'categoria' => 'nao_operacional',
            'descricao' => 'Juros recebidos, rendimentos de aplicações financeiras',
            'ativo' => true,
            'ordem' => 1,
        ]);

        PlanoConta::create([
            'pai_id' => $receitasNaoOperacionais->id,
            'codigo' => '2.2',
            'nome' => 'Outras Receitas',
            'tipo' => 'receita',
            'categoria' => 'nao_operacional',
            'descricao' => 'Outras receitas não operacionais',
            'ativo' => true,
            'ordem' => 2,
        ]);

        // DESPESAS
        // 3. Despesas Operacionais
        $despesasOperacionais = PlanoConta::create([
            'codigo' => '3',
            'nome' => 'Despesas Operacionais',
            'tipo' => 'despesa',
            'categoria' => 'operacional',
            'descricao' => 'Despesas relacionadas às atividades principais da empresa',
            'ativo' => true,
            'ordem' => 3,
        ]);

        // 3.1 Custos de Serviços
        $custosServicos = PlanoConta::create([
            'pai_id' => $despesasOperacionais->id,
            'codigo' => '3.1',
            'nome' => 'Custos de Serviços',
            'tipo' => 'despesa',
            'categoria' => 'operacional',
            'descricao' => 'Custos diretos relacionados à prestação de serviços',
            'ativo' => true,
            'ordem' => 1,
        ]);

        PlanoConta::create([
            'pai_id' => $custosServicos->id,
            'codigo' => '3.1.1',
            'nome' => 'Material de Consumo',
            'tipo' => 'despesa',
            'categoria' => 'operacional',
            'descricao' => 'Materiais e insumos utilizados na prestação de serviços',
            'ativo' => true,
            'ordem' => 1,
        ]);

        PlanoConta::create([
            'pai_id' => $custosServicos->id,
            'codigo' => '3.1.2',
            'nome' => 'Peças e Componentes',
            'tipo' => 'despesa',
            'categoria' => 'operacional',
            'descricao' => 'Peças e componentes utilizados nos serviços',
            'ativo' => true,
            'ordem' => 2,
        ]);

        PlanoConta::create([
            'pai_id' => $custosServicos->id,
            'codigo' => '3.1.3',
            'nome' => 'Mão de Obra Direta',
            'tipo' => 'despesa',
            'categoria' => 'operacional',
            'descricao' => 'Salários e encargos da equipe técnica',
            'ativo' => true,
            'ordem' => 3,
        ]);

        // 3.2 Despesas Administrativas
        $despesasAdministrativas = PlanoConta::create([
            'pai_id' => $despesasOperacionais->id,
            'codigo' => '3.2',
            'nome' => 'Despesas Administrativas',
            'tipo' => 'despesa',
            'categoria' => 'operacional',
            'descricao' => 'Despesas relacionadas à administração da empresa',
            'ativo' => true,
            'ordem' => 2,
        ]);

        PlanoConta::create([
            'pai_id' => $despesasAdministrativas->id,
            'codigo' => '3.2.1',
            'nome' => 'Salários e Encargos',
            'tipo' => 'despesa',
            'categoria' => 'operacional',
            'descricao' => 'Salários, 13º salário, férias e encargos sociais',
            'ativo' => true,
            'ordem' => 1,
        ]);

        PlanoConta::create([
            'pai_id' => $despesasAdministrativas->id,
            'codigo' => '3.2.2',
            'nome' => 'Aluguel',
            'tipo' => 'despesa',
            'categoria' => 'operacional',
            'descricao' => 'Aluguel de imóvel comercial ou escritório',
            'ativo' => true,
            'ordem' => 2,
        ]);

        PlanoConta::create([
            'pai_id' => $despesasAdministrativas->id,
            'codigo' => '3.2.3',
            'nome' => 'Energia Elétrica',
            'tipo' => 'despesa',
            'categoria' => 'operacional',
            'descricao' => 'Conta de energia elétrica',
            'ativo' => true,
            'ordem' => 3,
        ]);

        PlanoConta::create([
            'pai_id' => $despesasAdministrativas->id,
            'codigo' => '3.2.4',
            'nome' => 'Água e Esgoto',
            'tipo' => 'despesa',
            'categoria' => 'operacional',
            'descricao' => 'Conta de água e esgoto',
            'ativo' => true,
            'ordem' => 4,
        ]);

        PlanoConta::create([
            'pai_id' => $despesasAdministrativas->id,
            'codigo' => '3.2.5',
            'nome' => 'Telefone e Internet',
            'tipo' => 'despesa',
            'categoria' => 'operacional',
            'descricao' => 'Telefone fixo, móvel e internet',
            'ativo' => true,
            'ordem' => 5,
        ]);

        PlanoConta::create([
            'pai_id' => $despesasAdministrativas->id,
            'codigo' => '3.2.6',
            'nome' => 'Material de Escritório',
            'tipo' => 'despesa',
            'categoria' => 'operacional',
            'descricao' => 'Material de escritório e papelaria',
            'ativo' => true,
            'ordem' => 6,
        ]);

        PlanoConta::create([
            'pai_id' => $despesasAdministrativas->id,
            'codigo' => '3.2.7',
            'nome' => 'Limpeza e Conservação',
            'tipo' => 'despesa',
            'categoria' => 'operacional',
            'descricao' => 'Serviços de limpeza e produtos de limpeza',
            'ativo' => true,
            'ordem' => 7,
        ]);

        // 3.3 Despesas Comerciais
        $despesasComerciais = PlanoConta::create([
            'pai_id' => $despesasOperacionais->id,
            'codigo' => '3.3',
            'nome' => 'Despesas Comerciais',
            'tipo' => 'despesa',
            'categoria' => 'operacional',
            'descricao' => 'Despesas relacionadas à comercialização e marketing',
            'ativo' => true,
            'ordem' => 3,
        ]);

        PlanoConta::create([
            'pai_id' => $despesasComerciais->id,
            'codigo' => '3.3.1',
            'nome' => 'Marketing e Publicidade',
            'tipo' => 'despesa',
            'categoria' => 'operacional',
            'descricao' => 'Gastos com marketing, publicidade e propaganda',
            'ativo' => true,
            'ordem' => 1,
        ]);

        PlanoConta::create([
            'pai_id' => $despesasComerciais->id,
            'codigo' => '3.3.2',
            'nome' => 'Comissões de Vendas',
            'tipo' => 'despesa',
            'categoria' => 'operacional',
            'descricao' => 'Comissões pagas aos vendedores',
            'ativo' => true,
            'ordem' => 2,
        ]);

        // 3.4 Despesas Operacionais Diversas
        PlanoConta::create([
            'pai_id' => $despesasOperacionais->id,
            'codigo' => '3.4',
            'nome' => 'Combustível e Locomoção',
            'tipo' => 'despesa',
            'categoria' => 'operacional',
            'descricao' => 'Combustível para veículos da empresa',
            'ativo' => true,
            'ordem' => 4,
        ]);

        PlanoConta::create([
            'pai_id' => $despesasOperacionais->id,
            'codigo' => '3.5',
            'nome' => 'Manutenção de Equipamentos',
            'tipo' => 'despesa',
            'categoria' => 'operacional',
            'descricao' => 'Manutenção e reparo de equipamentos',
            'ativo' => true,
            'ordem' => 5,
        ]);

        PlanoConta::create([
            'pai_id' => $despesasOperacionais->id,
            'codigo' => '3.6',
            'nome' => 'Seguros',
            'tipo' => 'despesa',
            'categoria' => 'operacional',
            'descricao' => 'Seguros diversos (veículos, equipamentos, etc)',
            'ativo' => true,
            'ordem' => 6,
        ]);

        PlanoConta::create([
            'pai_id' => $despesasOperacionais->id,
            'codigo' => '3.7',
            'nome' => 'Impostos e Taxas',
            'tipo' => 'despesa',
            'categoria' => 'operacional',
            'descricao' => 'Impostos e taxas operacionais',
            'ativo' => true,
            'ordem' => 7,
        ]);

        // 4. Despesas Financeiras
        $despesasFinanceiras = PlanoConta::create([
            'codigo' => '4',
            'nome' => 'Despesas Financeiras',
            'tipo' => 'despesa',
            'categoria' => 'financeira',
            'descricao' => 'Despesas relacionadas a operações financeiras',
            'ativo' => true,
            'ordem' => 4,
        ]);

        PlanoConta::create([
            'pai_id' => $despesasFinanceiras->id,
            'codigo' => '4.1',
            'nome' => 'Juros Pagos',
            'tipo' => 'despesa',
            'categoria' => 'financeira',
            'descricao' => 'Juros pagos em empréstimos e financiamentos',
            'ativo' => true,
            'ordem' => 1,
        ]);

        PlanoConta::create([
            'pai_id' => $despesasFinanceiras->id,
            'codigo' => '4.2',
            'nome' => 'Taxas Bancárias',
            'tipo' => 'despesa',
            'categoria' => 'financeira',
            'descricao' => 'Taxas de manutenção de conta, tarifas bancárias',
            'ativo' => true,
            'ordem' => 2,
        ]);

        PlanoConta::create([
            'pai_id' => $despesasFinanceiras->id,
            'codigo' => '4.3',
            'nome' => 'Taxas de Recebimento',
            'tipo' => 'despesa',
            'categoria' => 'financeira',
            'descricao' => 'Taxas de cartão de crédito, boleto e outras formas de pagamento',
            'ativo' => true,
            'ordem' => 3,
        ]);

        // 5. Despesas Não Operacionais
        $despesasNaoOperacionais = PlanoConta::create([
            'codigo' => '5',
            'nome' => 'Despesas Não Operacionais',
            'tipo' => 'despesa',
            'categoria' => 'nao_operacional',
            'descricao' => 'Despesas não relacionadas à operação principal',
            'ativo' => true,
            'ordem' => 5,
        ]);

        PlanoConta::create([
            'pai_id' => $despesasNaoOperacionais->id,
            'codigo' => '5.1',
            'nome' => 'Multas e Juros de Impostos',
            'tipo' => 'despesa',
            'categoria' => 'nao_operacional',
            'descricao' => 'Multas e juros sobre impostos em atraso',
            'ativo' => true,
            'ordem' => 1,
        ]);

        PlanoConta::create([
            'pai_id' => $despesasNaoOperacionais->id,
            'codigo' => '5.2',
            'nome' => 'Perdas Diversas',
            'tipo' => 'despesa',
            'categoria' => 'nao_operacional',
            'descricao' => 'Perdas não operacionais diversas',
            'ativo' => true,
            'ordem' => 2,
        ]);

        // Atualiza referências nas demais tabelas usando o mapeamento código -> novo id
        if (!empty($oldMapping)) {
            $newMapping = DB::table('plano_contas')->pluck('id', 'codigo')->toArray();
            $tabelas = ['movimentacoes_financeiras', 'contas_receber', 'contas_pagar', 'pagamentos_recorrentes', 'contas_pagar_recorrentes'];
            foreach ($oldMapping as $oldId => $codigo) {
                $newId = $newMapping[$codigo] ?? null;
                if (!$newId || (int) $oldId === (int) $newId) {
                    continue;
                }
                foreach ($tabelas as $tabela) {
                    if (\Schema::hasColumn($tabela, 'plano_conta_id')) {
                        DB::table($tabela)->where('plano_conta_id', $oldId)->update(['plano_conta_id' => $newId]);
                    }
                }
            }
        }
    }
}
