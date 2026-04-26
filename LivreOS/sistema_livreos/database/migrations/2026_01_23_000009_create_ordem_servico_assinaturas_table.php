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
        Schema::create('ordem_servico_assinaturas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ordem_servico_id')->constrained('ordem_servicos')->cascadeOnDelete();
            $table->string('tipo', 20); // tecnico|cliente
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('signature_path', 255);
            $table->json('signature_points')->nullable();
            $table->json('metadata')->nullable();
            $table->string('checkbox_text', 500)->nullable();
            $table->boolean('checkbox_checked')->default(false);
            $table->timestamp('checkbox_checked_at')->nullable();
            $table->string('checkbox_checked_ip', 45)->nullable();
            $table->string('session_uuid', 64)->nullable();
            $table->string('signed_ip', 45)->nullable();
            $table->string('signed_user_agent', 500)->nullable();
            $table->timestamp('signed_at')->nullable();
            $table->json('geo')->nullable();
            $table->string('terms_version', 20)->nullable();
            $table->string('privacy_version', 20)->nullable();
            $table->string('legal_basis', 100)->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ordem_servico_assinaturas');
    }
};
