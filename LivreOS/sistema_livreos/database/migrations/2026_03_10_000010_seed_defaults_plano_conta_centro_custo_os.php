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

return new class extends Migration
{
    public function up(): void
    {
        $planoConta = DB::table('plano_contas')
            ->whereNull('deleted_at')
            ->where(function ($q) {
                $q->where('codigo', '1.1')->orWhere('nome', 'Receita de Serviços');
            })
            ->where('ativo', true)
            ->first();

        $centroCusto = DB::table('centros_custos')
            ->whereNull('deleted_at')
            ->where('codigo', 'OPE')
            ->where('ativo', true)
            ->first();

        $now = now();
        if ($planoConta) {
            DB::table('configuracoes')->updateOrInsert(
                ['chave' => 'plano_conta_os_encerramento'],
                ['valor' => (string) $planoConta->id, 'created_at' => $now, 'updated_at' => $now]
            );
        }

        if ($centroCusto) {
            DB::table('configuracoes')->updateOrInsert(
                ['chave' => 'centro_custo_os_encerramento'],
                ['valor' => (string) $centroCusto->id, 'created_at' => $now, 'updated_at' => $now]
            );
        }
    }

    public function down(): void
    {
        DB::table('configuracoes')
            ->whereIn('chave', ['plano_conta_os_encerramento', 'centro_custo_os_encerramento'])
            ->delete();
    }
};
