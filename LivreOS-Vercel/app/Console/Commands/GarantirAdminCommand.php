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

namespace App\Console\Commands;

use App\Models\Role;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;

class GarantirAdminCommand extends Command
{
    protected $signature = 'erp:garantir-admin
                            {--email=admin@admin.com : E-mail do usuário admin}
                            {--password=password : Senha a ser definida}';

    protected $description = 'Garante que exista um usuário administrador (útil após restaurar backup e ficar sem acesso)';

    public function handle(): int
    {
        $email = $this->option('email');
        $password = $this->option('password');

        $admin = User::firstOrCreate(
            ['email' => $email],
            [
                'name' => 'Administrador',
                'password' => Hash::make($password),
                'is_admin' => true,
                'ativo' => true,
            ]
        );

        $admin->password = Hash::make($password);
        $admin->is_admin = true;
        $admin->ativo = true;
        $admin->save();

        $adminRole = Role::where('slug', 'admin')->first();
        if ($adminRole) {
            $admin->roles()->sync([$adminRole->id]);
        }

        $this->info('Usuário admin garantido: '.$email);
        $this->line('Senha definida conforme o parâmetro --password (padrão: password).');
        $this->line('Faça login em: '.url('/login'));

        return self::SUCCESS;
    }
}
