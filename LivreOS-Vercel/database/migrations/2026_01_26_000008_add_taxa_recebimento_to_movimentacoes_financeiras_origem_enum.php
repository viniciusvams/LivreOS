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
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Para SQLite, precisamos recriar a tabela com o novo enum
        if (DB::getDriverName() === 'sqlite') {
            // SQLite não suporta ALTER ENUM, então vamos usar uma abordagem diferente
            // Vamos apenas garantir que o valor seja aceito (SQLite não valida enums)
            // Mas vamos adicionar uma constraint check se necessário
        } else {
            // Para MySQL/PostgreSQL, alterar o enum
            DB::statement("ALTER TABLE movimentacoes_financeiras MODIFY COLUMN origem ENUM('conta_receber', 'conta_pagar', 'transferencia', 'ajuste', 'taxa_recebimento', 'outro') DEFAULT 'outro'");
        }
    }

    public function down(): void
    {
        if (DB::getDriverName() !== 'sqlite') {
            DB::statement("ALTER TABLE movimentacoes_financeiras MODIFY COLUMN origem ENUM('conta_receber', 'conta_pagar', 'transferencia', 'ajuste', 'outro') DEFAULT 'outro'");
        }
    }
};
