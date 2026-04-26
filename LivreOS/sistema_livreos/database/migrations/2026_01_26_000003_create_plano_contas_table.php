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
        Schema::create('plano_contas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->nullable()->constrained('tenants')->nullOnDelete();
            $table->foreignId('pai_id')->nullable()->constrained('plano_contas')->nullOnDelete();
            $table->string('codigo', 20)->nullable()->comment('Código hierárquico (ex: 1.1.1)');
            $table->string('nome', 200);
            $table->enum('tipo', ['receita', 'despesa']); // receita ou despesa
            $table->enum('categoria', ['operacional', 'nao_operacional', 'financeira'])->default('operacional');
            $table->text('descricao')->nullable();
            $table->boolean('ativo')->default(true);
            $table->integer('ordem')->default(0);
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['tenant_id', 'tipo', 'ativo']);
            $table->index(['pai_id']);
            $table->index(['codigo']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('plano_contas');
    }
};
