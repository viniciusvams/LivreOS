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

/**
 * Plugin Name: PDV
 * Description: Ponto de Venda integrado: abertura/reforço/sangria/fechamento cego, checkout com bipagem, múltiplos pagamentos, orçamentos, impressão térmica e modo offline.
 * Version: 1.0.0
 * Author: ERP
 * Requires PHP: 8.1
 *
 * Totalmente isolado do sistema principal. Todas as rotas e tabelas no plugin.
 */

require_once __DIR__ . '/src/Models/Caixa.php';
require_once __DIR__ . '/src/Models/CaixaMovimentacao.php';
require_once __DIR__ . '/src/Models/CaixaFechamentoQuebraSobra.php';
require_once __DIR__ . '/src/Models/Venda.php';
require_once __DIR__ . '/src/Models/VendaItem.php';
require_once __DIR__ . '/src/Models/VendaPagamento.php';
require_once __DIR__ . '/src/Models/Orcamento.php';
require_once __DIR__ . '/src/Models/OrcamentoItem.php';
require_once __DIR__ . '/src/EstoqueHelper.php';
require_once __DIR__ . '/src/PdvController.php';
require_once __DIR__ . '/src/PdvApiController.php';
require_once __DIR__ . '/src/PdvRelatorioController.php';
require_once __DIR__ . '/src/PdvSettingsController.php';
require_once __DIR__ . '/src/PdvPermissoes.php';
require_once __DIR__ . '/src/LogoHelper.php';

erp_register_activation_hook(__FILE__, function () {
    if (!\Illuminate\Support\Facades\Schema::hasTable('plugin_pdv_caixas')) {
        \Illuminate\Support\Facades\Schema::create('plugin_pdv_caixas', function (\Illuminate\Database\Schema\Blueprint $table) {
            $table->id();
            $table->string('numero_caixa', 30)->default('1');
            $table->unsignedBigInteger('user_id');
            $table->decimal('valor_abertura', 15, 2)->default(0);
            $table->decimal('valor_fechamento_informado', 15, 2)->nullable();
            $table->decimal('valor_fechamento_sistema', 15, 2)->nullable();
            $table->string('status', 20)->default('aberto');
            $table->timestamp('opened_at')->nullable();
            $table->timestamp('closed_at')->nullable();
            $table->timestamps();
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
        });
    }
    if (\Illuminate\Support\Facades\Schema::hasTable('plugin_pdv_caixas') && !\Illuminate\Support\Facades\Schema::hasColumn('plugin_pdv_caixas', 'numero_caixa')) {
        \Illuminate\Support\Facades\Schema::table('plugin_pdv_caixas', function (\Illuminate\Database\Schema\Blueprint $table) {
            $table->string('numero_caixa', 30)->default('1')->after('id');
        });
    }
    if (!\Illuminate\Support\Facades\Schema::hasTable('plugin_pdv_caixa_movimentacoes')) {
        \Illuminate\Support\Facades\Schema::create('plugin_pdv_caixa_movimentacoes', function (\Illuminate\Database\Schema\Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('caixa_id');
            $table->string('tipo', 30);
            $table->decimal('valor', 15, 2);
            $table->unsignedBigInteger('plano_conta_id')->nullable();
            $table->text('justificativa')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamps();
            $table->foreign('caixa_id')->references('id')->on('plugin_pdv_caixas')->onDelete('cascade');
            $table->foreign('created_by')->references('id')->on('users')->onDelete('set null');
        });
    }
    if (!\Illuminate\Support\Facades\Schema::hasTable('plugin_pdv_vendas')) {
        \Illuminate\Support\Facades\Schema::create('plugin_pdv_vendas', function (\Illuminate\Database\Schema\Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('caixa_id');
            $table->unsignedBigInteger('cliente_id')->nullable();
            $table->string('tipo', 20)->default('venda');
            $table->string('status', 20)->default('aberta');
            $table->decimal('total', 15, 2)->default(0);
            $table->decimal('desconto', 15, 2)->default(0);
            $table->decimal('total_final', 15, 2)->default(0);
            $table->text('observacoes')->nullable();
            $table->text('motivo_cancelamento')->nullable();
            $table->json('seriais_garantia')->nullable();
            $table->timestamp('synced_at')->nullable();
            $table->timestamps();
            $table->foreign('caixa_id')->references('id')->on('plugin_pdv_caixas')->onDelete('cascade');
            $table->foreign('cliente_id')->references('id')->on('clientes')->onDelete('set null');
        });
    }
    if (\Illuminate\Support\Facades\Schema::hasTable('plugin_pdv_vendas') && !\Illuminate\Support\Facades\Schema::hasColumn('plugin_pdv_vendas', 'motivo_cancelamento')) {
        \Illuminate\Support\Facades\Schema::table('plugin_pdv_vendas', function (\Illuminate\Database\Schema\Blueprint $table) {
            $table->text('motivo_cancelamento')->nullable()->after('observacoes');
        });
    }
    if (\Illuminate\Support\Facades\Schema::hasTable('plugin_pdv_vendas') && !\Illuminate\Support\Facades\Schema::hasColumn('plugin_pdv_vendas', 'observacao_interna')) {
        \Illuminate\Support\Facades\Schema::table('plugin_pdv_vendas', function (\Illuminate\Database\Schema\Blueprint $table) {
            $table->text('observacao_interna')->nullable()->after('observacoes');
        });
    }
    if (\Illuminate\Support\Facades\Schema::hasTable('plugin_pdv_vendas') && !\Illuminate\Support\Facades\Schema::hasColumn('plugin_pdv_vendas', 'desconto_autorizado_por_user_id')) {
        \Illuminate\Support\Facades\Schema::table('plugin_pdv_vendas', function (\Illuminate\Database\Schema\Blueprint $table) {
            $table->unsignedBigInteger('desconto_autorizado_por_user_id')->nullable()->after('desconto');
            $table->foreign('desconto_autorizado_por_user_id', 'pdv_venda_fk_desc_aut_user')
                ->references('id')->on('users')->onDelete('set null');
        });
    }
    if (\Illuminate\Support\Facades\Schema::hasTable('plugin_pdv_vendas') && !\Illuminate\Support\Facades\Schema::hasColumn('plugin_pdv_vendas', 'cliente_bloqueado_autorizado_por_user_id')) {
        \Illuminate\Support\Facades\Schema::table('plugin_pdv_vendas', function (\Illuminate\Database\Schema\Blueprint $table) {
            $table->unsignedBigInteger('cliente_bloqueado_autorizado_por_user_id')->nullable()->after('desconto_autorizado_por_user_id');
            $table->foreign('cliente_bloqueado_autorizado_por_user_id', 'pdv_venda_fk_cli_blk_aut_user')
                ->references('id')->on('users')->onDelete('set null');
        });
    }
    if (\Illuminate\Support\Facades\Schema::hasTable('plugin_pdv_vendas') && !\Illuminate\Support\Facades\Schema::hasColumn('plugin_pdv_vendas', 'limite_credito_autorizado_por_user_id')) {
        \Illuminate\Support\Facades\Schema::table('plugin_pdv_vendas', function (\Illuminate\Database\Schema\Blueprint $table) {
            $table->unsignedBigInteger('limite_credito_autorizado_por_user_id')->nullable()->after('cliente_bloqueado_autorizado_por_user_id');
            $table->foreign('limite_credito_autorizado_por_user_id', 'pdv_venda_fk_lim_cred_aut_user')
                ->references('id')->on('users')->onDelete('set null');
        });
    }
    if (\Illuminate\Support\Facades\Schema::hasTable('plugin_pdv_vendas') && !\Illuminate\Support\Facades\Schema::hasColumn('plugin_pdv_vendas', 'estoque_zerado_autorizado_por_user_id')) {
        \Illuminate\Support\Facades\Schema::table('plugin_pdv_vendas', function (\Illuminate\Database\Schema\Blueprint $table) {
            $table->unsignedBigInteger('estoque_zerado_autorizado_por_user_id')->nullable()->after('limite_credito_autorizado_por_user_id');
            $table->foreign('estoque_zerado_autorizado_por_user_id', 'pdv_venda_fk_est_zero_aut_user')
                ->references('id')->on('users')->onDelete('set null');
        });
    }
    if (\Illuminate\Support\Facades\Schema::hasTable('plugin_pdv_vendas') && !\Illuminate\Support\Facades\Schema::hasColumn('plugin_pdv_vendas', 'cancelado_por_user_id')) {
        \Illuminate\Support\Facades\Schema::table('plugin_pdv_vendas', function (\Illuminate\Database\Schema\Blueprint $table) {
            $table->unsignedBigInteger('cancelado_por_user_id')->nullable()->after('motivo_cancelamento');
            $table->unsignedBigInteger('cancelamento_autorizado_por_user_id')->nullable()->after('cancelado_por_user_id');
            $table->timestamp('canceled_at')->nullable()->after('cancelamento_autorizado_por_user_id');
            $table->foreign('cancelado_por_user_id', 'pdv_venda_fk_cancelado_user')
                ->references('id')->on('users')->onDelete('set null');
            $table->foreign('cancelamento_autorizado_por_user_id', 'pdv_venda_fk_canc_aut_user')
                ->references('id')->on('users')->onDelete('set null');
        });
    }
    if (!\Illuminate\Support\Facades\Schema::hasTable('plugin_pdv_venda_itens')) {
        \Illuminate\Support\Facades\Schema::create('plugin_pdv_venda_itens', function (\Illuminate\Database\Schema\Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('venda_id');
            $table->string('tipo', 20);
            $table->unsignedBigInteger('produto_id')->nullable();
            $table->unsignedBigInteger('servico_id')->nullable();
            $table->string('descricao');
            $table->decimal('quantidade', 12, 4)->default(1);
            $table->decimal('preco_unitario', 15, 2);
            $table->decimal('total', 15, 2);
            $table->string('serial')->nullable();
            $table->json('kit_componentes')->nullable();
            $table->timestamps();
            $table->foreign('venda_id')->references('id')->on('plugin_pdv_vendas')->onDelete('cascade');
        });
    }
    if (\Illuminate\Support\Facades\Schema::hasTable('plugin_pdv_venda_itens') && !\Illuminate\Support\Facades\Schema::hasColumn('plugin_pdv_venda_itens', 'observacao')) {
        \Illuminate\Support\Facades\Schema::table('plugin_pdv_venda_itens', function (\Illuminate\Database\Schema\Blueprint $table) {
            $table->string('observacao', 255)->nullable()->after('kit_componentes');
        });
    }
    if (\Illuminate\Support\Facades\Schema::hasTable('plugin_pdv_venda_itens') && !\Illuminate\Support\Facades\Schema::hasColumn('plugin_pdv_venda_itens', 'quantidade_cancelada')) {
        \Illuminate\Support\Facades\Schema::table('plugin_pdv_venda_itens', function (\Illuminate\Database\Schema\Blueprint $table) {
            $table->decimal('quantidade_cancelada', 12, 4)->default(0)->after('quantidade');
        });
    }
    if (!\Illuminate\Support\Facades\Schema::hasTable('plugin_pdv_venda_pagamentos')) {
        \Illuminate\Support\Facades\Schema::create('plugin_pdv_venda_pagamentos', function (\Illuminate\Database\Schema\Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('venda_id');
            $table->unsignedBigInteger('forma_pagamento_id');
            $table->decimal('valor', 15, 2);
            $table->unsignedInteger('parcelas')->default(1);
            $table->unsignedBigInteger('conta_bancaria_id')->nullable();
            $table->unsignedBigInteger('adquirente_id')->nullable();
            $table->string('bandeira', 30)->nullable();
            $table->boolean('antecipado')->nullable()->comment('Recebimento antecipado (quando adquirente permite ambos)');
            $table->text('observacoes')->nullable();
            $table->string('pix_chave_id', 40)->nullable()->comment('Chave PIX escolhida no PDV (id dentro do JSON da forma)');
            $table->json('pix_parcela_vencimentos')->nullable()->comment('Datas Y-m-d por parcela (PIX parcelado)');
            $table->timestamps();
            $table->foreign('venda_id')->references('id')->on('plugin_pdv_vendas')->onDelete('cascade');
        });
    }
    if (\Illuminate\Support\Facades\Schema::hasTable('plugin_pdv_venda_pagamentos') && !\Illuminate\Support\Facades\Schema::hasColumn('plugin_pdv_venda_pagamentos', 'antecipado')) {
        \Illuminate\Support\Facades\Schema::table('plugin_pdv_venda_pagamentos', function (\Illuminate\Database\Schema\Blueprint $table) {
            $table->boolean('antecipado')->nullable()->after('bandeira');
        });
    }
    if (\Illuminate\Support\Facades\Schema::hasTable('plugin_pdv_venda_pagamentos') && !\Illuminate\Support\Facades\Schema::hasColumn('plugin_pdv_venda_pagamentos', 'pix_chave_id')) {
        \Illuminate\Support\Facades\Schema::table('plugin_pdv_venda_pagamentos', function (\Illuminate\Database\Schema\Blueprint $table) {
            $table->string('pix_chave_id', 40)->nullable()->after('observacoes');
        });
    }
    if (\Illuminate\Support\Facades\Schema::hasTable('plugin_pdv_venda_pagamentos') && !\Illuminate\Support\Facades\Schema::hasColumn('plugin_pdv_venda_pagamentos', 'pix_parcela_vencimentos')) {
        \Illuminate\Support\Facades\Schema::table('plugin_pdv_venda_pagamentos', function (\Illuminate\Database\Schema\Blueprint $table) {
            $table->json('pix_parcela_vencimentos')->nullable()->after('pix_chave_id');
        });
    }
    if (!\Illuminate\Support\Facades\Schema::hasTable('plugin_pdv_orcamentos')) {
        \Illuminate\Support\Facades\Schema::create('plugin_pdv_orcamentos', function (\Illuminate\Database\Schema\Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('caixa_id')->nullable();
            $table->unsignedBigInteger('cliente_id')->nullable();
            $table->string('nome_cliente')->nullable();
            $table->decimal('total', 15, 2)->default(0);
            $table->string('status', 20)->default('rascunho');
            $table->timestamps();
            $table->foreign('cliente_id')->references('id')->on('clientes')->onDelete('set null');
        });
    }
    if (\Illuminate\Support\Facades\Schema::hasTable('plugin_pdv_orcamentos')) {
        if (!\Illuminate\Support\Facades\Schema::hasColumn('plugin_pdv_orcamentos', 'observacao')) {
            \Illuminate\Support\Facades\Schema::table('plugin_pdv_orcamentos', function (\Illuminate\Database\Schema\Blueprint $table) {
                $table->text('observacao')->nullable()->after('status');
            });
        }
        if (!\Illuminate\Support\Facades\Schema::hasColumn('plugin_pdv_orcamentos', 'observacao_interna')) {
            \Illuminate\Support\Facades\Schema::table('plugin_pdv_orcamentos', function (\Illuminate\Database\Schema\Blueprint $table) {
                $table->text('observacao_interna')->nullable()->after('observacao');
            });
        }
    }
    if (!\Illuminate\Support\Facades\Schema::hasTable('plugin_pdv_orcamento_itens')) {
        \Illuminate\Support\Facades\Schema::create('plugin_pdv_orcamento_itens', function (\Illuminate\Database\Schema\Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('orcamento_id');
            $table->string('tipo', 20);
            $table->unsignedBigInteger('produto_id')->nullable();
            $table->unsignedBigInteger('servico_id')->nullable();
            $table->string('descricao');
            $table->decimal('quantidade', 12, 4)->default(1);
            $table->decimal('preco_unitario', 15, 2);
            $table->decimal('total', 15, 2);
            $table->string('serial')->nullable();
            $table->timestamps();
            $table->foreign('orcamento_id')->references('id')->on('plugin_pdv_orcamentos')->onDelete('cascade');
        });
    }
    if (\Illuminate\Support\Facades\Schema::hasTable('plugin_pdv_orcamento_itens') && !\Illuminate\Support\Facades\Schema::hasColumn('plugin_pdv_orcamento_itens', 'kit_componentes')) {
        \Illuminate\Support\Facades\Schema::table('plugin_pdv_orcamento_itens', function (\Illuminate\Database\Schema\Blueprint $table) {
            $table->json('kit_componentes')->nullable()->after('serial');
        });
    }
    if (\Illuminate\Support\Facades\Schema::hasTable('plugin_pdv_orcamento_itens') && !\Illuminate\Support\Facades\Schema::hasColumn('plugin_pdv_orcamento_itens', 'observacao')) {
        \Illuminate\Support\Facades\Schema::table('plugin_pdv_orcamento_itens', function (\Illuminate\Database\Schema\Blueprint $table) {
            $table->string('observacao', 255)->nullable()->after('kit_componentes');
        });
    }
    if (!\Illuminate\Support\Facades\Schema::hasTable('plugin_pdv_caixa_fechamento_quebra_sobra')) {
        \Illuminate\Support\Facades\Schema::create('plugin_pdv_caixa_fechamento_quebra_sobra', function (\Illuminate\Database\Schema\Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('caixa_id');
            $table->decimal('valor_quebra_sobra', 15, 2)->comment('Positivo = sobra, Negativo = quebra');
            $table->unsignedBigInteger('aprovado_por_user_id')->nullable();
            $table->json('dados')->nullable();
            $table->timestamps();
            $table->index('caixa_id', 'pdv_qs_idx_caixa');
            $table->index('aprovado_por_user_id', 'pdv_qs_idx_aprovador');
        });
    }
    if (\Illuminate\Support\Facades\Schema::hasTable('plugin_pdv_caixa_fechamento_quebra_sobra')) {
        try {
            \Illuminate\Support\Facades\Schema::table('plugin_pdv_caixa_fechamento_quebra_sobra', function (\Illuminate\Database\Schema\Blueprint $table) {
                $table->foreign('caixa_id', 'pdv_qs_fk_caixa')
                    ->references('id')
                    ->on('plugin_pdv_caixas')
                    ->onDelete('cascade');
            });
        } catch (\Throwable $e) {
            // Evita interromper instalação em esquemas legados/incompatíveis.
        }
        try {
            \Illuminate\Support\Facades\Schema::table('plugin_pdv_caixa_fechamento_quebra_sobra', function (\Illuminate\Database\Schema\Blueprint $table) {
                $table->foreign('aprovado_por_user_id', 'pdv_qs_fk_aprovador')
                    ->references('id')
                    ->on('users')
                    ->onDelete('set null');
            });
        } catch (\Throwable $e) {
            // Evita interromper instalação em esquemas legados/incompatíveis.
        }
    }
    pdv_maybe_create_aviso_oculto_table();
    Pdv\LogoHelper::copyOnActivation();
});

// Tabela para o usuário ocultar avisos de quebra/sobra no dashboard (por usuário) — criada no activation e em plugins.loaded
if (!function_exists('pdv_maybe_create_aviso_oculto_table')) {
    function pdv_maybe_create_aviso_oculto_table(): void
    {
        if (!\Illuminate\Support\Facades\Schema::hasTable('plugin_pdv_aviso_quebra_sobra_oculto')) {
            \Illuminate\Support\Facades\Schema::create('plugin_pdv_aviso_quebra_sobra_oculto', function (\Illuminate\Database\Schema\Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('user_id');
                $table->unsignedBigInteger('caixa_fechamento_quebra_sobra_id');
                $table->timestamps();
                // MySQL limita nomes de índices/constraints a 64 caracteres.
                $table->unique(['user_id', 'caixa_fechamento_quebra_sobra_id'], 'pdv_aviso_uq_user_caixa');
                $table->index('user_id', 'pdv_aviso_idx_user');
                $table->index('caixa_fechamento_quebra_sobra_id', 'pdv_aviso_idx_caixa_qs');
            });
        }
        if (\Illuminate\Support\Facades\Schema::hasTable('plugin_pdv_aviso_quebra_sobra_oculto')) {
            try {
                \Illuminate\Support\Facades\Schema::table('plugin_pdv_aviso_quebra_sobra_oculto', function (\Illuminate\Database\Schema\Blueprint $table) {
                    $table->foreign('user_id', 'pdv_aviso_fk_user')
                        ->references('id')
                        ->on('users')
                        ->onDelete('cascade');
                });
            } catch (\Throwable $e) {
                // Em alguns ambientes o users.id pode estar incompatível; não interromper instalação.
            }
            try {
                \Illuminate\Support\Facades\Schema::table('plugin_pdv_aviso_quebra_sobra_oculto', function (\Illuminate\Database\Schema\Blueprint $table) {
                    $table->foreign('caixa_fechamento_quebra_sobra_id', 'pdv_aviso_fk_caixa_qs')
                        ->references('id')
                        ->on('plugin_pdv_caixa_fechamento_quebra_sobra')
                        ->onDelete('cascade');
                });
            } catch (\Throwable $e) {
                // Em alguns ambientes a tabela de origem pode estar em migração; não interromper instalação.
            }
        }
    }
}

// Tabela para rastreio de quebra/sobra no fechamento do caixa (um registro por fechamento)
if (!function_exists('pdv_maybe_create_fechamento_quebra_sobra_table')) {
    function pdv_maybe_create_fechamento_quebra_sobra_table(): void
    {
        if (!\Illuminate\Support\Facades\Schema::hasTable('plugin_pdv_caixa_fechamento_quebra_sobra')) {
            \Illuminate\Support\Facades\Schema::create('plugin_pdv_caixa_fechamento_quebra_sobra', function (\Illuminate\Database\Schema\Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('caixa_id');
                $table->decimal('valor_quebra_sobra', 15, 2)->comment('Positivo = sobra, Negativo = quebra');
                $table->unsignedBigInteger('aprovado_por_user_id')->nullable()->comment('Preenchido quando diferença excedeu limite e gerente autorizou');
                $table->json('dados')->nullable()->comment('valores_informados_por_forma, valores_calculados_por_forma, diferencas_por_forma');
                $table->timestamps();
                $table->index('caixa_id', 'pdv_qs_idx_caixa');
                $table->index('aprovado_por_user_id', 'pdv_qs_idx_aprovador');
            });
        }
        if (\Illuminate\Support\Facades\Schema::hasTable('plugin_pdv_caixa_fechamento_quebra_sobra')) {
            try {
                \Illuminate\Support\Facades\Schema::table('plugin_pdv_caixa_fechamento_quebra_sobra', function (\Illuminate\Database\Schema\Blueprint $table) {
                    $table->foreign('caixa_id', 'pdv_qs_fk_caixa')
                        ->references('id')
                        ->on('plugin_pdv_caixas')
                        ->onDelete('cascade');
                });
            } catch (\Throwable $e) {
                // Evita interromper instalação em esquemas legados/incompatíveis.
            }
            try {
                \Illuminate\Support\Facades\Schema::table('plugin_pdv_caixa_fechamento_quebra_sobra', function (\Illuminate\Database\Schema\Blueprint $table) {
                    $table->foreign('aprovado_por_user_id', 'pdv_qs_fk_aprovador')
                        ->references('id')
                        ->on('users')
                        ->onDelete('set null');
                });
            } catch (\Throwable $e) {
                // Evita interromper instalação em esquemas legados/incompatíveis.
            }
        }
    }
}
// Só cria a tabela quando o app já estiver bootado (evita "facade root has not been set")
add_action('plugins.loaded', function (): void {
    pdv_maybe_create_fechamento_quebra_sobra_table();
    pdv_maybe_create_aviso_oculto_table();
}, 5);

erp_register_deactivation_hook(__FILE__, function () {
    // Não remove tabelas para preservar dados
});

add_filter('configuracoes.plugins.settings_view', function ($view, $plugin) {
    if (($plugin['slug'] ?? '') === 'pdv') {
        return 'pdv::settings';
    }
    return $view;
}, 10, 2);

add_filter('menu.items', function ($items) {
    if (!auth()->check() || !function_exists('auth')) {
        return $items;
    }
    $user = auth()->user();
    if (!method_exists($user, 'canAccessOperational') || !$user->canAccessOperational()) {
        return $items;
    }
    try {
        $historicoPath   = parse_url(route('plugin.pdv.historico-vendas'), PHP_URL_PATH) ?: '/plugin/pdv/historico-vendas';
        $relatoriosPath  = parse_url(route('plugin.pdv.relatorios.index'), PHP_URL_PATH) ?: '/plugin/pdv/relatorios';
    } catch (\Throwable $e) {
        $historicoPath  = '/plugin/pdv/historico-vendas';
        $relatoriosPath = '/plugin/pdv/relatorios';
    }
    $subItems = [
        ['name' => 'Abrir PDV',           'path' => '/plugin/pdv',       'pro' => false],
        ['name' => 'Gerenciar caixas',     'path' => '/plugin/pdv/caixas','pro' => false],
        ['name' => 'Histórico de Vendas',  'path' => $historicoPath,      'pro' => false],
        ['name' => 'Relatórios',           'path' => $relatoriosPath,     'pro' => false],
    ];
    if ($user->is_admin || (method_exists($user, 'hasPermission') && $user->hasPermission('manage-plugins'))) {
        $subItems[] = ['name' => 'Configurações', 'path' => '/configuracoes-sistema/plugins/pdv', 'pro' => false];
    }
    $items[] = [
        'name' => 'PDV',
        'icon' => 'task',
        'subItems' => $subItems,
    ];
    return $items;
}, 20);

try {
    if (function_exists('add_action')) {
        add_action('plugins.loaded', function (): void {
            if (!class_exists(\Illuminate\Support\Facades\Schema::class)) {
                return;
            }
            if (\Illuminate\Support\Facades\Schema::hasTable('plugin_pdv_vendas')
                && !\Illuminate\Support\Facades\Schema::hasColumn('plugin_pdv_vendas', 'motivo_cancelamento')) {
                \Illuminate\Support\Facades\Schema::table('plugin_pdv_vendas', function (\Illuminate\Database\Schema\Blueprint $table) {
                    $table->text('motivo_cancelamento')->nullable()->after('observacoes');
                });
            }
            if (\Illuminate\Support\Facades\Schema::hasTable('plugin_pdv_venda_pagamentos')
                && !\Illuminate\Support\Facades\Schema::hasColumn('plugin_pdv_venda_pagamentos', 'antecipado')) {
                \Illuminate\Support\Facades\Schema::table('plugin_pdv_venda_pagamentos', function (\Illuminate\Database\Schema\Blueprint $table) {
                    $table->boolean('antecipado')->nullable()->after('bandeira');
                });
            }
            if (\Illuminate\Support\Facades\Schema::hasTable('plugin_pdv_venda_pagamentos')
                && !\Illuminate\Support\Facades\Schema::hasColumn('plugin_pdv_venda_pagamentos', 'pix_chave_id')) {
                \Illuminate\Support\Facades\Schema::table('plugin_pdv_venda_pagamentos', function (\Illuminate\Database\Schema\Blueprint $table) {
                    $table->string('pix_chave_id', 40)->nullable()->after('observacoes');
                });
            }
            if (\Illuminate\Support\Facades\Schema::hasTable('plugin_pdv_venda_pagamentos')
                && !\Illuminate\Support\Facades\Schema::hasColumn('plugin_pdv_venda_pagamentos', 'pix_parcela_vencimentos')) {
                \Illuminate\Support\Facades\Schema::table('plugin_pdv_venda_pagamentos', function (\Illuminate\Database\Schema\Blueprint $table) {
                    $table->json('pix_parcela_vencimentos')->nullable()->after('pix_chave_id');
                });
            }
            if (\Illuminate\Support\Facades\Schema::hasTable('plugin_pdv_venda_itens')
                && !\Illuminate\Support\Facades\Schema::hasColumn('plugin_pdv_venda_itens', 'quantidade_cancelada')) {
                \Illuminate\Support\Facades\Schema::table('plugin_pdv_venda_itens', function (\Illuminate\Database\Schema\Blueprint $table) {
                    $table->decimal('quantidade_cancelada', 12, 4)->default(0)->after('quantidade');
                });
            }
        }, 10);
    }
} catch (\Throwable $e) {
}

// Card de avisos de quebra/sobra no dashboard financeiro (quando o plugin está ativo)
add_filter('financeiro.dashboard.data', function ($data) {
    if (!function_exists('auth') || !auth()->check()) {
        return $data;
    }
    try {
        if (!class_exists(\Pdv\Models\CaixaFechamentoQuebraSobra::class)) {
            return $data;
        }
        if (!\Illuminate\Support\Facades\Schema::hasTable('plugin_pdv_caixa_fechamento_quebra_sobra')) {
            return $data;
        }
        $userId = auth()->id();
        $ocultos = \Illuminate\Support\Facades\DB::table('plugin_pdv_aviso_quebra_sobra_oculto')
            ->where('user_id', $userId)
            ->pluck('caixa_fechamento_quebra_sobra_id')
            ->all();
        $avisos = \Pdv\Models\CaixaFechamentoQuebraSobra::with(['caixa', 'aprovadoPor'])
            ->where('valor_quebra_sobra', '!=', 0)
            ->when(count($ocultos) > 0, fn ($q) => $q->whereNotIn('id', $ocultos))
            ->orderByDesc('created_at')
            ->limit(15)
            ->get();
        $formasIds = [];
        foreach ($avisos as $a) {
            if (!empty($a->dados['valores_informados_por_forma']) && is_array($a->dados['valores_informados_por_forma'])) {
                $formasIds = array_merge($formasIds, array_keys($a->dados['valores_informados_por_forma']));
            }
        }
        $formasIds = array_unique(array_filter($formasIds));
        $formas = $formasIds ? \App\Models\FormaPagamento::whereIn('id', $formasIds)->get()->keyBy('id') : collect();
        $linkCaixas = function_exists('route') ? route('plugin.pdv.caixas') : url('/plugin/pdv/caixas');
        $data['pdvAvisos'] = $avisos->map(function ($a) use ($formas, $linkCaixas) {
            $caixa = $a->caixa;
            $dadosFormas = [];
            if (!empty($a->dados['valores_informados_por_forma']) && is_array($a->dados['valores_informados_por_forma'])) {
                $informados = $a->dados['valores_informados_por_forma'];
                $calculados = $a->dados['valores_calculados_por_forma'] ?? [];
                $diferencas = $a->dados['diferencas_por_forma'] ?? [];
                foreach ($informados as $formaId => $informado) {
                    $dadosFormas[] = [
                        'nome' => $formas->get($formaId)?->nome ?? 'Forma #' . $formaId,
                        'informado' => $informado,
                        'sistema' => $calculados[$formaId] ?? 0,
                        'diferenca' => $diferencas[$formaId] ?? 0,
                    ];
                }
            }
            $urlOcultar = function_exists('route') ? route('plugin.pdv.aviso-quebra-sobra.ocultar', ['id' => $a->id]) : url('/plugin/pdv/aviso-quebra-sobra/' . $a->id . '/ocultar');
            return [
                'id' => $a->id,
                'caixa_numero' => $caixa ? $caixa->numero_caixa : '—',
                'valor_quebra_sobra' => (float) $a->valor_quebra_sobra,
                'data_fechamento' => $caixa && $caixa->closed_at ? $caixa->closed_at->format('d/m/Y H:i') : ($a->created_at ? $a->created_at->format('d/m/Y H:i') : ''),
                'valor_informado' => $caixa ? (float) $caixa->valor_fechamento_informado : 0,
                'valor_sistema' => $caixa ? (float) $caixa->valor_fechamento_sistema : 0,
                'aprovado_por_nome' => $a->aprovadoPor ? $a->aprovadoPor->name : null,
                'dados_formas' => $dadosFormas,
                'url_ocultar' => $urlOcultar,
                'link_caixas' => $linkCaixas,
            ];
        })->all();
    } catch (\Throwable $e) {
        $data['pdvAvisos'] = $data['pdvAvisos'] ?? [];
    }
    return $data;
}, 10, 1);
