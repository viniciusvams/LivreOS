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

use App\Models\CategoriaFinanceira;
use App\Models\Tag;
use Illuminate\Database\Seeder;

class FinanceiroCategoriasTagsPadraoSeeder extends Seeder
{
    /**
     * Categorias e tags padrão para classificação opcional de contas a receber e a pagar.
     */
    public function run(): void
    {
        $this->seedCategoriasReceber();
        $this->seedCategoriasPagar();
        $this->seedTagsReceber();
        $this->seedTagsPagar();
    }

    private function upsertCategoria(string $escopo, ?int $parentId, string $nome, int $ordem, string $corFundo, string $corFonte, ?string $descricao = null): CategoriaFinanceira
    {
        $cat = CategoriaFinanceira::firstOrCreate(
            ['escopo' => $escopo, 'parent_id' => $parentId, 'nome' => $nome],
            ['ordem' => $ordem, 'ativo' => true, 'cor_fundo' => $corFundo, 'cor_fonte' => $corFonte, 'descricao' => $descricao]
        );
        $cat->update(['ordem' => $ordem, 'cor_fundo' => $corFundo, 'cor_fonte' => $corFonte, 'descricao' => $descricao]);

        return $cat;
    }

    private function seedCategoriasReceber(): void
    {
        $vendas = $this->upsertCategoria('receber', null, 'Vendas', 10, '#059669', '#ffffff');
        $this->upsertCategoria('receber', $vendas->id, 'Produtos', 11, '#10b981', '#ffffff');
        $this->upsertCategoria('receber', $vendas->id, 'Serviços', 12, '#34d399', '#064e3b');

        $this->upsertCategoria('receber', null, 'Receitas recorrentes', 20, '#0ea5e9', '#ffffff');
        $this->upsertCategoria('receber', null, 'Contratos e assinaturas', 30, '#8b5cf6', '#ffffff');
        $this->upsertCategoria('receber', null, 'Outras receitas', 90, '#64748b', '#ffffff');
    }

    private function seedCategoriasPagar(): void
    {
        $op = $this->upsertCategoria('pagar', null, 'Despesas operacionais', 10, '#475569', '#ffffff');
        $this->upsertCategoria('pagar', $op->id, 'Pessoal e encargos', 11, '#be123c', '#ffffff');
        $this->upsertCategoria('pagar', $op->id, 'Infraestrutura e utilidades', 12, '#0284c7', '#ffffff');
        $this->upsertCategoria('pagar', $op->id, 'Marketing e vendas', 13, '#c026d3', '#ffffff');

        $imp = $this->upsertCategoria('pagar', null, 'Impostos e obrigações', 20, '#b45309', '#ffffff');
        $this->upsertCategoria('pagar', $imp->id, 'Tributos', 21, '#d97706', '#ffffff');
        $this->upsertCategoria('pagar', $imp->id, 'Taxas e contribuições', 22, '#ea580c', '#ffffff');

        $this->upsertCategoria('pagar', null, 'Insumos e matéria-prima', 30, '#0d9488', '#ffffff');
        $this->upsertCategoria('pagar', null, 'Despesas financeiras', 40, '#7c3aed', '#ffffff');
        $this->upsertCategoria('pagar', null, 'Outras despesas', 90, '#78716c', '#ffffff');
    }

    /**
     * @param  list<array{nome:string,cor_fundo:string,cor_fonte:string,descricao?:string}>  $definicoes
     */
    private function criarTagsFinanceiras(string $escopo, array $definicoes): void
    {
        foreach ($definicoes as $def) {
            Tag::firstOrCreate(
                ['nome' => $def['nome'], 'escopo_financeiro' => $escopo],
                [
                    'tipo' => 'personalizado',
                    'descricao' => $def['descricao'] ?? null,
                    'cor_fundo' => $def['cor_fundo'],
                    'cor_fonte' => $def['cor_fonte'],
                    'ativo' => true,
                ]
            );
        }
    }

    private function seedTagsReceber(): void
    {
        $this->criarTagsFinanceiras('conta_receber', [
            ['nome' => 'Recorrente', 'cor_fundo' => '#0ea5e9', 'cor_fonte' => '#ffffff', 'descricao' => 'Recebimento periódico (aluguel, assinatura, mensalidade).'],
            ['nome' => 'Parcelado', 'cor_fundo' => '#6366f1', 'cor_fonte' => '#ffffff', 'descricao' => 'Título fracionado em parcelas.'],
            ['nome' => 'À vista', 'cor_fundo' => '#22c55e', 'cor_fonte' => '#ffffff', 'descricao' => 'Pagamento único integral.'],
            ['nome' => 'Boleto', 'cor_fundo' => '#64748b', 'cor_fonte' => '#ffffff', 'descricao' => 'Cobrança via boleto.'],
            ['nome' => 'PIX', 'cor_fundo' => '#10b981', 'cor_fonte' => '#ffffff', 'descricao' => 'Recebimento via PIX.'],
            ['nome' => 'Cartão', 'cor_fundo' => '#8b5cf6', 'cor_fonte' => '#ffffff', 'descricao' => 'Recebimento em cartão (crédito/débito).'],
            ['nome' => 'Adiantamento', 'cor_fundo' => '#f59e0b', 'cor_fonte' => '#ffffff', 'descricao' => 'Valor antecipado do cliente.'],
            ['nome' => 'Nota fiscal', 'cor_fundo' => '#ec4899', 'cor_fonte' => '#ffffff', 'descricao' => 'Título já faturado/documentado.'],
        ]);
    }

    private function seedTagsPagar(): void
    {
        $this->criarTagsFinanceiras('conta_pagar', [
            ['nome' => 'Despesa fixa', 'cor_fundo' => '#475569', 'cor_fonte' => '#ffffff', 'descricao' => 'Valor recorrente previsível (aluguel, software).'],
            ['nome' => 'Despesa variável', 'cor_fundo' => '#78716c', 'cor_fonte' => '#ffffff', 'descricao' => 'Valor flutua conforme uso ou volume.'],
            ['nome' => 'Imposto', 'cor_fundo' => '#b45309', 'cor_fonte' => '#ffffff', 'descricao' => 'Obrigação tributária.'],
            ['nome' => 'Energia e água', 'cor_fundo' => '#0284c7', 'cor_fonte' => '#ffffff', 'descricao' => 'Utilidades.'],
            ['nome' => 'Aluguel', 'cor_fundo' => '#7c3aed', 'cor_fonte' => '#ffffff', 'descricao' => 'Locação de imóvel ou equipamento.'],
            ['nome' => 'Fornecedor', 'cor_fundo' => '#0d9488', 'cor_fonte' => '#ffffff', 'descricao' => 'Pagamento a fornecedor.'],
            ['nome' => 'Folha', 'cor_fundo' => '#be123c', 'cor_fonte' => '#ffffff', 'descricao' => 'Salários, pró-labore ou honorários.'],
            ['nome' => 'Urgente', 'cor_fundo' => '#dc2626', 'cor_fonte' => '#ffffff', 'descricao' => 'Prioridade de pagamento.'],
        ]);
    }
}
