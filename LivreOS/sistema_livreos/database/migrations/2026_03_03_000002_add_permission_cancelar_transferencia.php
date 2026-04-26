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

use App\Models\Permission;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        Permission::firstOrCreate(
            ['slug' => 'financeiro.transferencias.cancelar'],
            [
                'name' => 'Cancelar transferência entre contas',
                'description' => 'Cancelar transferência entre contas (com registro de motivo e responsável)',
            ]
        );
    }

    public function down(): void
    {
        Permission::where('slug', 'financeiro.transferencias.cancelar')->delete();
    }
};
