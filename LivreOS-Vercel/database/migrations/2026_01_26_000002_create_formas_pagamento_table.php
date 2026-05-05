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
        Schema::create('formas_pagamento', function (Blueprint $table) {
            $table->id();
            $table->string('nome', 100);
            $table->string('tipo', 30); // pix, boleto, cartao_credito, cartao_debito, dinheiro, transferencia, cheque
            $table->decimal('taxa_percentual', 5, 2)->default(0);
            $table->decimal('taxa_fixa', 10, 2)->default(0);
            $table->integer('dias_recebimento')->default(0)->comment('Dias para recebimento (ex: cartão = 30 dias)');
            $table->boolean('ativo')->default(true);
            $table->boolean('permite_parcela')->default(false);
            $table->integer('max_parcelas')->nullable();
            $table->text('observacoes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['tipo', 'ativo']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('formas_pagamento');
    }
};
