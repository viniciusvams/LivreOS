<?php

/**
 * Plugin Name: Compras (NF-e)
 * Description: Módulo de compras: importação XML NF-e (SEFAZ), de-para de produtos, fornecedor por CNPJ, geração de contas a pagar e custo médio; desfazer entrada (financeiro/estoque/produtos plugin). PDF: smalot/pdfparser; opcional Poppler/Tesseract ou filtro. Hooks: compras.sefaz.consulta_chave, compras.pdf.extrair; do_action compras.importacao.confirmada, compras.importacao.desfeita.
 * Version: 1.0.0
 * Author: ERP LivreOS
 * Requires PHP: 8.1
 *
 * Permissões: access-financeiro + compras.gerenciar (criadas na ativação).
 */

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

require_once __DIR__ . '/src/Models/CompraImportacao.php';
require_once __DIR__ . '/src/Models/CompraImportacaoItem.php';
require_once __DIR__ . '/src/Models/CompraProdutoFornecedorMap.php';
require_once __DIR__ . '/src/Models/CompraAuditLog.php';
require_once __DIR__ . '/src/Services/NfeChaveValidator.php';
require_once __DIR__ . '/src/Services/NfeXmlParserService.php';
require_once __DIR__ . '/src/Services/NfeSefazConsultaService.php';
require_once __DIR__ . '/src/Services/CompraPdfExtratorService.php';
require_once __DIR__ . '/src/Services/ComprasFornecedorService.php';
require_once __DIR__ . '/src/Services/CompraImportacaoDesfazerService.php';
require_once __DIR__ . '/src/ComprasController.php';

erp_register_activation_hook(__FILE__, function (): void {
    if (! Schema::hasTable('plugin_compras_importacoes')) {
        Schema::create('plugin_compras_importacoes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->unsignedBigInteger('tenant_id')->nullable();
            $table->string('status', 40)->default('rascunho');
            $table->string('tipo_fonte', 10)->default('xml');
            $table->string('arquivo_original_path')->nullable();
            $table->string('chave_acesso', 44)->nullable()->index();
            $table->string('numero', 20)->nullable();
            $table->string('serie', 10)->nullable();
            $table->date('data_emissao')->nullable();
            $table->string('fornecedor_cnpj', 14)->nullable()->index();
            $table->string('fornecedor_nome', 500)->nullable();
            $table->foreignId('fornecedor_id')->nullable()->constrained('clientes')->nullOnDelete();
            $table->json('totais')->nullable();
            $table->json('emitente')->nullable();
            $table->json('destinatario')->nullable();
            $table->json('cobranca')->nullable();
            $table->json('sefaz_resultado')->nullable();
            $table->json('parse_warnings')->nullable();
            $table->text('erro_mensagem')->nullable();
            $table->timestamp('confirmed_at')->nullable();
            $table->json('undo_snapshot')->nullable();
            $table->timestamps();
        });
    }

    if (Schema::hasTable('plugin_compras_importacoes') && ! Schema::hasColumn('plugin_compras_importacoes', 'undo_snapshot')) {
        Schema::table('plugin_compras_importacoes', function (Blueprint $table) {
            $table->json('undo_snapshot')->nullable()->after('confirmed_at');
        });
    }

    if (! Schema::hasTable('plugin_compras_importacao_itens')) {
        Schema::create('plugin_compras_importacao_itens', function (Blueprint $table) {
            $table->id();
            $table->foreignId('compra_importacao_id')->constrained('plugin_compras_importacoes')->cascadeOnDelete();
            $table->unsignedInteger('indice')->default(0);
            $table->string('c_prod', 60)->nullable();
            $table->string('x_prod', 500)->nullable();
            $table->string('ncm', 16)->nullable();
            $table->string('cfop', 10)->nullable();
            $table->string('cst_icms', 10)->nullable();
            $table->string('ean', 20)->nullable();
            $table->decimal('q_com', 16, 6)->default(0);
            $table->decimal('v_un_com', 18, 10)->default(0);
            $table->decimal('v_prod', 15, 2)->default(0);
            $table->json('impostos')->nullable();
            $table->json('dados_nf')->nullable();
            $table->foreignId('produto_id')->nullable()->constrained('produtos')->nullOnDelete();
            $table->boolean('atualizar_estoque')->default(true);
            $table->timestamps();
        });
    }

    if (Schema::hasTable('plugin_compras_importacao_itens') && ! Schema::hasColumn('plugin_compras_importacao_itens', 'dados_nf')) {
        Schema::table('plugin_compras_importacao_itens', function (Blueprint $table) {
            $table->json('dados_nf')->nullable()->after('impostos');
        });
    }

    if (! Schema::hasTable('plugin_compras_produto_fornecedor_maps')) {
        Schema::create('plugin_compras_produto_fornecedor_maps', function (Blueprint $table) {
            $table->id();
            $table->string('fornecedor_cnpj', 14);
            $table->string('codigo_fornecedor', 80);
            $table->foreignId('produto_id')->constrained('produtos')->cascadeOnDelete();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->unique(['fornecedor_cnpj', 'codigo_fornecedor'], 'compras_map_uq_cnpj_cod');
        });
    }

    if (! Schema::hasTable('plugin_compras_audit_logs')) {
        Schema::create('plugin_compras_audit_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('compra_importacao_id')->nullable()->constrained('plugin_compras_importacoes')->nullOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('acao', 80);
            $table->json('dados')->nullable();
            $table->timestamps();
        });
    }

    if (class_exists(\App\Models\Permission::class)) {
        \App\Models\Permission::firstOrCreate(
            ['slug' => 'compras.gerenciar'],
            [
                'name' => 'Compras — importação NF-e e entrada',
                'description' => 'Importar XML/PDF, mapear produtos e confirmar contas a pagar (plugin Compras).',
            ]
        );
    }
});

erp_register_deactivation_hook(__FILE__, function (): void {
});

add_filter('menu.items', function ($items) {
    if (! auth()->check()) {
        return $items;
    }
    $user = auth()->user();
    if (! method_exists($user, 'canAccessOperational') || ! $user->canAccessOperational()) {
        return $items;
    }
    if (! $user->hasPermission('access-financeiro') || ! $user->hasPermission('compras.gerenciar')) {
        return $items;
    }
    try {
        $path = parse_url(route('plugin.compras.index'), PHP_URL_PATH) ?: '/plugin/compras';
    } catch (\Throwable) {
        $path = '/plugin/compras';
    }
    $items[] = [
        'name' => 'Compras',
        'icon' => 'task',
        'subItems' => [
            ['name' => 'Importar NF-e', 'path' => $path, 'pro' => false],
        ],
    ];

    return $items;
}, 25);
