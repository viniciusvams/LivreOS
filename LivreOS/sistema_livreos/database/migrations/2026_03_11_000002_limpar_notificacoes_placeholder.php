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
use Illuminate\Support\Facades\DB;

/**
 * Remove os registros placeholder "_sem_vencimento_*" criados pela versão inicial
 * do serviço de notificações, que aparecem indevidamente no histórico do usuário.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('notificacoes_usuarios')) {
            DB::table('notificacoes_usuarios')
                ->where('titulo', 'like', '_sem_vencimento%')
                ->delete();
        }
    }

    public function down(): void
    {
        // Não restaura os placeholders — eram registros de controle indesejados
    }
};
