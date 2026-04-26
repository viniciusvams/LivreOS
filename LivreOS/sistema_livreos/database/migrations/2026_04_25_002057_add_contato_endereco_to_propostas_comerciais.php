<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('propostas_comerciais', function (Blueprint $table) {
            $table->foreignId('contato_id')->nullable()->after('cliente_id')->constrained('contatos')->nullOnDelete();
            $table->foreignId('endereco_id')->nullable()->after('contato_id')->constrained('enderecos')->nullOnDelete();
            $table->foreignId('cliente_unidade_id')->nullable()->after('endereco_id')->constrained('clientes')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('propostas_comerciais', function (Blueprint $table) {
            $table->dropForeign(['contato_id']);
            $table->dropForeign(['endereco_id']);
            $table->dropForeign(['cliente_unidade_id']);
            $table->dropColumn(['contato_id', 'endereco_id', 'cliente_unidade_id']);
        });
    }
};
