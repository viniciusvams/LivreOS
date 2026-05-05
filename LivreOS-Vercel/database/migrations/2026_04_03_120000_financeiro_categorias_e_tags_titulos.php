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
        Schema::create('categorias_financeiras', function (Blueprint $table) {
            $table->id();
            $table->enum('escopo', ['receber', 'pagar']);
            $table->foreignId('parent_id')->nullable()->constrained('categorias_financeiras')->nullOnDelete();
            $table->string('nome', 160);
            $table->unsignedSmallInteger('ordem')->default(0);
            $table->boolean('ativo')->default(true);
            $table->timestamps();
            $table->softDeletes();
            $table->index(['escopo', 'ativo']);
        });

        Schema::table('contas_receber', function (Blueprint $table) {
            $table->foreignId('categoria_financeira_id')
                ->nullable()
                ->after('centro_custo_id')
                ->constrained('categorias_financeiras')
                ->nullOnDelete();
        });

        Schema::table('contas_pagar', function (Blueprint $table) {
            $table->foreignId('categoria_financeira_id')
                ->nullable()
                ->after('centro_custo_id')
                ->constrained('categorias_financeiras')
                ->nullOnDelete();
        });

        Schema::table('tags', function (Blueprint $table) {
            $table->string('escopo_financeiro', 24)
                ->nullable()
                ->after('tipo')
                ->comment('conta_receber|conta_pagar|null');
            $table->index(['escopo_financeiro']);
        });

        Schema::create('conta_receber_tag', function (Blueprint $table) {
            $table->id();
            $table->foreignId('conta_receber_id')->constrained('contas_receber')->cascadeOnDelete();
            $table->foreignId('tag_id')->constrained('tags')->cascadeOnDelete();
            $table->timestamps();
            $table->unique(['conta_receber_id', 'tag_id'], 'cr_tag_unique');
        });

        Schema::create('conta_pagar_tag', function (Blueprint $table) {
            $table->id();
            $table->foreignId('conta_pagar_id')->constrained('contas_pagar')->cascadeOnDelete();
            $table->foreignId('tag_id')->constrained('tags')->cascadeOnDelete();
            $table->timestamps();
            $table->unique(['conta_pagar_id', 'tag_id'], 'cp_tag_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('conta_pagar_tag');
        Schema::dropIfExists('conta_receber_tag');

        Schema::table('tags', function (Blueprint $table) {
            $table->dropIndex(['escopo_financeiro']);
            $table->dropColumn('escopo_financeiro');
        });

        Schema::table('contas_pagar', function (Blueprint $table) {
            $table->dropForeign(['categoria_financeira_id']);
            $table->dropColumn('categoria_financeira_id');
        });

        Schema::table('contas_receber', function (Blueprint $table) {
            $table->dropForeign(['categoria_financeira_id']);
            $table->dropColumn('categoria_financeira_id');
        });

        Schema::dropIfExists('categorias_financeiras');
    }
};
