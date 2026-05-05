<?php

/**
 * Snapshot gravado na confirmação para desfazer importação (estoque + títulos + produtos do plugin).
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
        if (! Schema::hasTable('plugin_compras_importacoes')) {
            return;
        }
        if (! Schema::hasColumn('plugin_compras_importacoes', 'undo_snapshot')) {
            Schema::table('plugin_compras_importacoes', function (Blueprint $table) {
                $table->json('undo_snapshot')->nullable()->after('confirmed_at');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('plugin_compras_importacoes') && Schema::hasColumn('plugin_compras_importacoes', 'undo_snapshot')) {
            Schema::table('plugin_compras_importacoes', function (Blueprint $table) {
                $table->dropColumn('undo_snapshot');
            });
        }
    }
};
