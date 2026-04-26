<?php

/**
 * Componente da aplicação LivreOS
 *
 * @copyright 2024-2026 LivreOS
 * @license   https://www.gnu.org/licenses/agpl-3.0.txt AGPL-3.0
 */

use App\Models\BaixaTitulo;
use App\Services\Financeiro\BaixaTituloTaxaMovimentacaoResolver;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('baixas_titulos', function (Blueprint $table) {
            if (! Schema::hasColumn('baixas_titulos', 'taxa_movimentacao_id')) {
                $table->foreignId('taxa_movimentacao_id')
                    ->nullable()
                    ->after('movimentacao_id')
                    ->constrained('movimentacoes_financeiras')
                    ->nullOnDelete();
            }
        });

        BaixaTitulo::query()
            ->where('tipo_titulo', 'conta_receber')
            ->whereNull('taxa_movimentacao_id')
            ->with('movimentacao')
            ->orderBy('id')
            ->chunkById(200, function ($baixas) {
                foreach ($baixas as $baixa) {
                    $movEntrada = $baixa->movimentacao;
                    if (! $movEntrada || $movEntrada->tipo !== 'entrada' || $movEntrada->origem !== 'conta_receber') {
                        continue;
                    }
                    $taxa = BaixaTituloTaxaMovimentacaoResolver::findTaxaPorEstrutura($baixa, $movEntrada);
                    if ($taxa) {
                        BaixaTitulo::whereKey($baixa->id)->update(['taxa_movimentacao_id' => $taxa->id]);
                    }
                }
            });
    }

    public function down(): void
    {
        Schema::table('baixas_titulos', function (Blueprint $table) {
            if (Schema::hasColumn('baixas_titulos', 'taxa_movimentacao_id')) {
                $table->dropForeign(['taxa_movimentacao_id']);
                $table->dropColumn('taxa_movimentacao_id');
            }
        });
    }
};
