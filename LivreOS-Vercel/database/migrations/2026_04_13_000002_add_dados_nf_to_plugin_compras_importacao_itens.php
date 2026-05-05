<?php

/**
 * Dados extras do grupo prod da NF-e para montar cadastro de produto completo.
 *
 * @copyright 2024-2026 LivreOS
 * @license   https://www.gnu.org/licenses/agpl-3.0.txt AGPL-3.0
 */

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('plugin_compras_importacao_itens')) {
            return;
        }
        if (! Schema::hasColumn('plugin_compras_importacao_itens', 'dados_nf')) {
            Schema::table('plugin_compras_importacao_itens', function (Blueprint $table) {
                $table->json('dados_nf')->nullable()->after('impostos');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('plugin_compras_importacao_itens') && Schema::hasColumn('plugin_compras_importacao_itens', 'dados_nf')) {
            Schema::table('plugin_compras_importacao_itens', function (Blueprint $table) {
                $table->dropColumn('dados_nf');
            });
        }
    }
};
