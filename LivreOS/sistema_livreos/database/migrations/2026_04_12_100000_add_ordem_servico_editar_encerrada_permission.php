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
            ['slug' => 'ordem_servico.editar_encerrada'],
            [
                'name' => 'Editar ordem de serviço encerrada',
                'description' => 'Permite alterar dados, anexos e adiantamentos de OS com status Encerrada/Encerrado',
            ]
        );
    }

    public function down(): void
    {
        Permission::where('slug', 'ordem_servico.editar_encerrada')->delete();
    }
};
