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
use App\Models\Role;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        $p = Permission::firstOrCreate(
            ['slug' => 'manage-proposta-documento-modelos'],
            [
                'name'        => 'Gerir modelos DOCX de propostas',
                'description' => 'Cadastrar e editar modelos Word (.docx) usados na geração de documentos de proposta comercial',
            ]
        );

        foreach (['admin', 'manager'] as $roleSlug) {
            $role = Role::where('slug', $roleSlug)->first();
            if ($role) {
                $role->permissions()->syncWithoutDetaching([$p->id]);
            }
        }
    }

    public function down(): void
    {
        Permission::where('slug', 'manage-proposta-documento-modelos')->delete();
    }
};
