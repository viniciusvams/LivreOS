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

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->softDeletes();

            if (!Schema::hasColumn('users', 'ativo')) {
                $table->boolean('ativo')->default(true)->after('is_admin');
            }
            if (!Schema::hasColumn('users', 'cpf')) {
                $table->string('cpf', 14)->nullable()->unique()->after('remember_token');
            }
            if (!Schema::hasColumn('users', 'rg')) {
                $table->string('rg', 20)->nullable()->after('cpf');
            }
            if (!Schema::hasColumn('users', 'rg_orgao_emissor')) {
                $table->string('rg_orgao_emissor', 20)->nullable()->after('rg');
            }
            if (!Schema::hasColumn('users', 'telefone')) {
                $table->string('telefone', 20)->nullable()->after('rg_orgao_emissor');
            }
            if (!Schema::hasColumn('users', 'cargo')) {
                $table->string('cargo', 100)->nullable()->after('telefone');
            }
            if (!Schema::hasColumn('users', 'departamento')) {
                $table->string('departamento', 100)->nullable()->after('cargo');
            }
            if (!Schema::hasColumn('users', 'custo_hora_tecnica')) {
                $table->decimal('custo_hora_tecnica', 12, 2)->nullable()->after('departamento');
            }
            if (!Schema::hasColumn('users', 'cep')) {
                $table->string('cep', 10)->nullable()->after('custo_hora_tecnica');
            }
            if (!Schema::hasColumn('users', 'logradouro')) {
                $table->string('logradouro', 255)->nullable()->after('cep');
            }
            if (!Schema::hasColumn('users', 'numero')) {
                $table->string('numero', 20)->nullable()->after('logradouro');
            }
            if (!Schema::hasColumn('users', 'complemento')) {
                $table->string('complemento', 100)->nullable()->after('numero');
            }
            if (!Schema::hasColumn('users', 'bairro')) {
                $table->string('bairro', 100)->nullable()->after('complemento');
            }
            if (!Schema::hasColumn('users', 'cidade')) {
                $table->string('cidade', 100)->nullable()->after('bairro');
            }
            if (!Schema::hasColumn('users', 'uf')) {
                $table->string('uf', 2)->nullable()->after('cidade');
            }
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropSoftDeletes();
            $cols = ['ativo', 'cpf', 'rg', 'rg_orgao_emissor', 'telefone', 'cargo', 'departamento', 'custo_hora_tecnica', 'cep', 'logradouro', 'numero', 'complemento', 'bairro', 'cidade', 'uf'];
            foreach ($cols as $col) {
                if (Schema::hasColumn('users', $col)) {
                    $table->dropColumn($col);
                }
            }
        });
    }
};
