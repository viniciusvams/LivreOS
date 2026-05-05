<?php

/**
 * Componente da aplicação LivreOS
 *
 * @copyright 2024-2026 LivreOS
 * @license   https://www.gnu.org/licenses/agpl-3.0.txt AGPL-3.0
 */

use App\Models\Permission;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        Permission::firstOrCreate(
            ['slug' => 'financeiro.contas-receber.corrigir_datas_baixa'],
            [
                'name' => 'Corrigir datas de baixa (conta a receber)',
                'description' => 'Ajustar data de movimentação e conciliação de recebimentos sem estornar',
            ]
        );
    }

    public function down(): void
    {
        Permission::where('slug', 'financeiro.contas-receber.corrigir_datas_baixa')->delete();
    }
};
