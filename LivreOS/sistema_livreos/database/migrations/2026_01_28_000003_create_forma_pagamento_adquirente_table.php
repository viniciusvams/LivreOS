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
        Schema::create('forma_pagamento_adquirente', function (Blueprint $table) {
            $table->id();
            $table->foreignId('forma_pagamento_id')->constrained('formas_pagamento')->onDelete('cascade');
            $table->foreignId('adquirente_id')->constrained('adquirentes')->onDelete('cascade');
            $table->boolean('padrao')->default(false)->comment('Adquirente padrão para esta forma de pagamento');
            $table->integer('ordem')->default(0)->comment('Ordem de prioridade');
            $table->timestamps();

            // MySQL limita identificadores a 64 caracteres.
            $table->unique(['forma_pagamento_id', 'adquirente_id'], 'fpa_uq_forma_adq');
            $table->index(['forma_pagamento_id', 'padrao'], 'fpa_idx_forma_padrao');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('forma_pagamento_adquirente');
    }
};
