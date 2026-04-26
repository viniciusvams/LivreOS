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
        Schema::create('taxas_adquirente', function (Blueprint $table) {
            $table->id();
            $table->foreignId('adquirente_id')->constrained('adquirentes')->onDelete('cascade');
            
            // Bandeira do cartão
            $table->enum('bandeira', ['master', 'visa', 'elo', 'amex', 'hipercard', 'outros'])
                ->default('master')
                ->comment('Bandeira do cartão');
            
            // Modalidade de pagamento
            $table->enum('modalidade', ['debito', 'credito_vista', 'credito_parcelado'])
                ->comment('Modalidade: débito, crédito à vista ou parcelado');
            
            // Faixa de parcelas (para crédito parcelado)
            $table->integer('parcelas_min')->nullable()
                ->comment('Número mínimo de parcelas (null = sem limite mínimo)');
            $table->integer('parcelas_max')->nullable()
                ->comment('Número máximo de parcelas (null = sem limite máximo)');
            
            // Estrutura de taxas (composta)
            $table->decimal('taxa_percentual', 8, 4)->default(0)
                ->comment('Taxa percentual fixa (ex: 2.5% = 2.5000)');
            $table->decimal('taxa_por_parcela', 8, 4)->default(0)
                ->comment('Taxa adicional por parcela (ex: 0.5% por parcela = 0.5000)');
            $table->decimal('taxa_fixa', 10, 2)->default(0)
                ->comment('Taxa fixa em reais (ex: R$ 0,50 por transação)');
            
            // Antecipação (se aplicável)
            $table->boolean('usa_antecipacao')->default(false)
                ->comment('Se esta taxa é para recebimento antecipado');
            $table->decimal('taxa_antecipacao_percentual', 8, 4)->nullable()
                ->comment('Taxa adicional de antecipação (percentual sobre o valor)');
            
            // Regras de liquidação (dias para recebimento)
            $table->integer('dias_recebimento_debito')->default(1)
                ->comment('Dias para recebimento de débito (padrão D+1)');
            $table->integer('dias_recebimento_credito_vista')->default(30)
                ->comment('Dias para recebimento de crédito à vista (padrão D+30)');
            $table->integer('dias_recebimento_parcela')->default(30)
                ->comment('Dias entre cada parcela (ex: 30 dias = 1ª parcela em 30 dias, 2ª em 60, etc)');
            
            $table->date('data_inicio')->nullable()
                ->comment('Data de início de vigência da taxa');
            $table->date('data_fim')->nullable()
                ->comment('Data de fim de vigência da taxa (null = sem prazo)');
            
            $table->boolean('ativo')->default(true);
            $table->text('observacoes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            // MySQL limita identificadores a 64 caracteres.
            $table->index(['adquirente_id', 'bandeira', 'modalidade', 'ativo'], 'taxa_adq_idx_adq_band_mod_ativo');
            $table->index(['adquirente_id', 'modalidade', 'parcelas_min', 'parcelas_max'], 'taxa_adq_idx_adq_mod_parc');
            $table->index(['data_inicio', 'data_fim'], 'taxa_adq_idx_vigencia');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('taxas_adquirente');
    }
};
