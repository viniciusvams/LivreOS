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
        Schema::create('adquirentes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->nullable()->constrained('tenants')->nullOnDelete();
            $table->string('nome', 100)->comment('Ex: Stone, Cielo, PagSeguro, etc');
            $table->string('codigo', 50)->nullable()->comment('Código interno da adquirente');
            $table->enum('tipo_antecipacao', ['fluxo', 'antecipado', 'ambos'])->default('fluxo')
                ->comment('fluxo = recebe no prazo normal, antecipado = recebe tudo em D+1/D+2, ambos = pode escolher');
            $table->integer('dias_antecipacao')->default(1)
                ->comment('Dias para recebimento quando antecipado (geralmente 1 ou 2)');
            $table->boolean('permite_antecipacao_parcelado')->default(false)
                ->comment('Se permite antecipar recebimento de parcelas');
            $table->text('observacoes')->nullable();
            $table->boolean('ativo')->default(true);
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['tenant_id', 'ativo']);
            $table->index('nome');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('adquirentes');
    }
};
