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

use App\Models\Configuracao;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        Configuracao::firstOrCreate(
            ['chave' => 'pedidos_venda.plano_conta_estorno'],
            ['valor' => '']
        );
    }

    public function down(): void
    {
        Configuracao::where('chave', 'pedidos_venda.plano_conta_estorno')->delete();
    }
};
