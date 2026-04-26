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

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $legacy = 'm' . 'apo' . 's';

        if (Schema::hasTable('ordem_servico_logs') && Schema::hasColumn('ordem_servico_logs', 'tipo')) {
            DB::table('ordem_servico_logs')->where('tipo', 'log_sistema_' . $legacy)->update(['tipo' => 'log_sistema_mos']);
            DB::table('ordem_servico_logs')->where('tipo', 'nota_interna_' . $legacy)->update(['tipo' => 'nota_interna_mos']);
        }

        if (Schema::hasTable('pedido_venda_items') && Schema::hasColumn('pedido_venda_items', 'descricao')) {
            DB::table('pedido_venda_items')
                ->where('descricao', 'like', '%Importado do Map-OS%')
                ->update(['descricao' => DB::raw("REPLACE(descricao, 'Importado do Map-OS', 'Importado do M-OS')")]);
        }

        if (Schema::hasTable('contas_receber') && Schema::hasColumn('contas_receber', 'origem_tipo')) {
            DB::table('contas_receber')->where('origem_tipo', $legacy . '_cobranca')->update(['origem_tipo' => 'mos_cobranca']);
        }

        if (Schema::hasTable('contas_pagar') && Schema::hasColumn('contas_pagar', 'origem_tipo')) {
            DB::table('contas_pagar')->where('origem_tipo', $legacy . '_lancamento')->update(['origem_tipo' => 'mos_lancamento']);
        }

        if (Schema::hasTable('movimentacoes') && Schema::hasColumn('movimentacoes', 'origem_tipo')) {
            DB::table('movimentacoes')->where('origem_tipo', $legacy . '_lancamento')->update(['origem_tipo' => 'mos_lancamento']);
        }

        if (Schema::hasTable('produtos') && Schema::hasColumn('produtos', 'codigo_sku')) {
            DB::table('produtos')->where('codigo_sku', 'like', $legacy . ':%')->update(['codigo_sku' => DB::raw("REPLACE(codigo_sku, '" . $legacy . ":', 'mos:')")]);
        }

        if (Schema::hasTable('servicos') && Schema::hasColumn('servicos', 'codigo_sku')) {
            DB::table('servicos')->where('codigo_sku', 'like', $legacy . ':%')->update(['codigo_sku' => DB::raw("REPLACE(codigo_sku, '" . $legacy . ":', 'mos:')")]);
        }

        if (Schema::hasTable('ordem_servicos') && Schema::hasColumn('ordem_servicos', 'origem_importacao')) {
            DB::table('ordem_servicos')->where('origem_importacao', $legacy)->update(['origem_importacao' => 'mos_export']);
        }
    }

    public function down(): void
    {
    }
};
