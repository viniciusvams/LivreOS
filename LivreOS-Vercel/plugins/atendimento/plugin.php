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
 * Plugin Name: Atendimento
 * Description: Controle de atendimentos externos: agendamento, endereço, problema/diagnóstico, status e prioridades configuráveis. Conversão para Ordem de Serviço.
 * Version: 1.0.0
 * Author: ERP
 * Requires PHP: 8.1
 *
 * Totalmente isolado do sistema principal. Todas as rotas e tabelas no plugin.
 */

require_once __DIR__ . '/src/Models/StatusAtendimento.php';
require_once __DIR__ . '/src/Models/PrioridadeAtendimento.php';
require_once __DIR__ . '/src/Models/TempoEstimadoAtendimento.php';
require_once __DIR__ . '/src/Models/Atendimento.php';
require_once __DIR__ . '/src/Models/AtendimentoFoto.php';
require_once __DIR__ . '/src/Models/RotaDiaDistanceCache.php';
require_once __DIR__ . '/src/Services/RotaDiaDistanceService.php';
require_once __DIR__ . '/src/Services/NominatimGeocodeService.php';
require_once __DIR__ . '/src/Services/AttendanceService.php';
require_once __DIR__ . '/src/AtendimentoController.php';
require_once __DIR__ . '/src/AtendimentoSettingsController.php';

erp_register_activation_hook(__FILE__, function () {
    if (!\Illuminate\Support\Facades\Schema::hasTable('plugin_atendimento_status')) {
        \Illuminate\Support\Facades\Schema::create('plugin_atendimento_status', function (\Illuminate\Database\Schema\Blueprint $table) {
            $table->id();
            $table->string('nome', 80);
            $table->string('cor', 20)->nullable();
            $table->unsignedSmallInteger('ordem')->default(0);
            $table->timestamps();
        });
        // Status padrão
        $defaults = [
            ['nome' => 'Pendente', 'cor' => '#6b7280', 'ordem' => 1],
            ['nome' => 'Em andamento', 'cor' => '#2563eb', 'ordem' => 2],
            ['nome' => 'Concluído', 'cor' => '#16a34a', 'ordem' => 3],
            ['nome' => 'Requer novo agendamento', 'cor' => '#dc2626', 'ordem' => 4],
        ];
        foreach ($defaults as $i => $row) {
            \Illuminate\Support\Facades\DB::table('plugin_atendimento_status')->insert(array_merge($row, [
                'created_at' => now(),
                'updated_at' => now(),
            ]));
        }
    }

    if (!\Illuminate\Support\Facades\Schema::hasTable('plugin_atendimento_prioridades')) {
        \Illuminate\Support\Facades\Schema::create('plugin_atendimento_prioridades', function (\Illuminate\Database\Schema\Blueprint $table) {
            $table->id();
            $table->string('nome', 80);
            $table->string('cor', 20)->nullable();
            $table->unsignedSmallInteger('ordem')->default(0);
            $table->timestamps();
        });
        $prioridades = [
            ['nome' => 'Baixa', 'cor' => '#22c55e', 'ordem' => 1],
            ['nome' => 'Média', 'cor' => '#eab308', 'ordem' => 2],
            ['nome' => 'Alta', 'cor' => '#f97316', 'ordem' => 3],
            ['nome' => 'Urgente', 'cor' => '#ef4444', 'ordem' => 4],
        ];
        foreach ($prioridades as $row) {
            \Illuminate\Support\Facades\DB::table('plugin_atendimento_prioridades')->insert(array_merge($row, [
                'created_at' => now(),
                'updated_at' => now(),
            ]));
        }
    }

    if (!\Illuminate\Support\Facades\Schema::hasTable('plugin_atendimento_tempos_estimados')) {
        \Illuminate\Support\Facades\Schema::create('plugin_atendimento_tempos_estimados', function (\Illuminate\Database\Schema\Blueprint $table) {
            $table->id();
            $table->string('label', 80);
            $table->unsignedSmallInteger('minutos');
            $table->unsignedSmallInteger('ordem')->default(0);
            $table->timestamps();
        });
        $tempos = [
            ['label' => '30 min', 'minutos' => 30, 'ordem' => 1],
            ['label' => '1 h', 'minutos' => 60, 'ordem' => 2],
            ['label' => '2 h', 'minutos' => 120, 'ordem' => 3],
        ];
        foreach ($tempos as $row) {
            \Illuminate\Support\Facades\DB::table('plugin_atendimento_tempos_estimados')->insert(array_merge($row, [
                'created_at' => now(),
                'updated_at' => now(),
            ]));
        }
    }

    if (!\Illuminate\Support\Facades\Schema::hasTable('plugin_atendimento_atendimentos')) {
        \Illuminate\Support\Facades\Schema::create('plugin_atendimento_atendimentos', function (\Illuminate\Database\Schema\Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('cliente_id');
            $table->dateTime('data_hora_atendimento');
            $table->unsignedSmallInteger('tempo_estimado_minutos')->nullable()->comment('30, 60, 120 etc');
            $table->string('endereco_tipo', 20)->default('usar_cadastrado')->comment('usar_cadastrado, endereco_cliente, novo');
            $table->unsignedBigInteger('endereco_id')->nullable()->comment('FK enderecos quando endereco_cliente');
            $table->text('endereco_completo')->nullable()->comment('Texto livre ou montado para novo');
            $table->string('endereco_cep', 20)->nullable();
            $table->string('endereco_logradouro', 255)->nullable();
            $table->string('endereco_numero', 30)->nullable();
            $table->string('endereco_complemento', 100)->nullable();
            $table->string('endereco_bairro', 100)->nullable();
            $table->string('endereco_cidade', 100)->nullable();
            $table->string('endereco_uf', 5)->nullable();
            $table->text('problema_reportado')->nullable();
            $table->text('diagnostico_tecnico')->nullable();
            $table->text('observacoes_tecnicas')->nullable();
            $table->string('fase', 20)->default('rascunho')->comment('rascunho, concluido, convertido_os');
            $table->unsignedBigInteger('checklist_model_id')->nullable()->comment('FK checklist_models do sistema principal');
            $table->json('checklist_respostas')->nullable()->comment('Respostas do checklist (chave => valor)');
            $table->json('checklist_campos_adhoc')->nullable()->comment('Itens ad-hoc quando não usa checklist do sistema');
            $table->string('assinatura_cliente_path', 500)->nullable()->comment('Caminho da imagem da assinatura do cliente');
            $table->dateTime('assinatura_data')->nullable()->comment('Data e hora em que o cliente assinou');
            $table->string('assinatura_local', 255)->nullable()->comment('Local/cidade onde o cliente assinou');
            $table->unsignedBigInteger('status_id');
            $table->unsignedBigInteger('prioridade_id');
            $table->unsignedBigInteger('ordem_servico_id')->nullable()->comment('OS do sistema principal quando convertido');
            $table->unsignedBigInteger('tecnico_id')->nullable()->comment('Técnico responsável pelo atendimento');
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamps();
            $table->foreign('cliente_id')->references('id')->on('clientes')->onDelete('cascade');
            $table->foreign('endereco_id')->references('id')->on('enderecos')->onDelete('set null');
            $table->foreign('status_id')->references('id')->on('plugin_atendimento_status')->onDelete('restrict');
            $table->foreign('prioridade_id')->references('id')->on('plugin_atendimento_prioridades')->onDelete('restrict');
            $table->foreign('created_by')->references('id')->on('users')->onDelete('set null');
            $table->foreign('tecnico_id')->references('id')->on('users')->onDelete('set null');
        });
    }

    if (\Illuminate\Support\Facades\Schema::hasTable('plugin_atendimento_atendimentos')) {
        $t = 'plugin_atendimento_atendimentos';
        if (!\Illuminate\Support\Facades\Schema::hasColumn($t, 'fase')) {
            \Illuminate\Support\Facades\Schema::table($t, fn (\Illuminate\Database\Schema\Blueprint $table) => $table->string('fase', 20)->default('rascunho')->after('diagnostico_tecnico'));
        }
        if (!\Illuminate\Support\Facades\Schema::hasColumn($t, 'observacoes_tecnicas')) {
            \Illuminate\Support\Facades\Schema::table($t, fn (\Illuminate\Database\Schema\Blueprint $table) => $table->text('observacoes_tecnicas')->nullable()->after('diagnostico_tecnico'));
        }
        if (!\Illuminate\Support\Facades\Schema::hasColumn($t, 'checklist_model_id')) {
            \Illuminate\Support\Facades\Schema::table($t, fn (\Illuminate\Database\Schema\Blueprint $table) => $table->unsignedBigInteger('checklist_model_id')->nullable()->after('fase'));
        }
        if (!\Illuminate\Support\Facades\Schema::hasColumn($t, 'checklist_respostas')) {
            \Illuminate\Support\Facades\Schema::table($t, fn (\Illuminate\Database\Schema\Blueprint $table) => $table->json('checklist_respostas')->nullable()->after('checklist_model_id'));
        }
        if (!\Illuminate\Support\Facades\Schema::hasColumn($t, 'checklist_campos_adhoc')) {
            \Illuminate\Support\Facades\Schema::table($t, fn (\Illuminate\Database\Schema\Blueprint $table) => $table->json('checklist_campos_adhoc')->nullable()->after('checklist_respostas'));
        }
        if (!\Illuminate\Support\Facades\Schema::hasColumn($t, 'assinatura_cliente_path')) {
            \Illuminate\Support\Facades\Schema::table($t, fn (\Illuminate\Database\Schema\Blueprint $table) => $table->string('assinatura_cliente_path', 500)->nullable()->after('checklist_campos_adhoc'));
        }
        if (!\Illuminate\Support\Facades\Schema::hasColumn($t, 'assinatura_data')) {
            \Illuminate\Support\Facades\Schema::table($t, fn (\Illuminate\Database\Schema\Blueprint $table) => $table->dateTime('assinatura_data')->nullable()->after('assinatura_cliente_path'));
        }
        if (!\Illuminate\Support\Facades\Schema::hasColumn($t, 'assinatura_local')) {
            \Illuminate\Support\Facades\Schema::table($t, fn (\Illuminate\Database\Schema\Blueprint $table) => $table->string('assinatura_local', 255)->nullable()->after('assinatura_data'));
        }
    }

    if (!\Illuminate\Support\Facades\Schema::hasTable('plugin_atendimento_fotos')) {
        try {
            \Illuminate\Support\Facades\Schema::create('plugin_atendimento_fotos', function (\Illuminate\Database\Schema\Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('atendimento_id');
                $table->string('tipo', 20)->default('evidencia')->comment('antes, depois, evidencia');
                $table->string('legenda', 255)->nullable();
                $table->string('caminho', 500);
                $table->unsignedSmallInteger('ordem')->default(0);
                $table->timestamps();
                $table->index('atendimento_id', 'atd_fotos_idx_atendimento');
            });
        } catch (\Illuminate\Database\QueryException $e) {
            if (! str_contains($e->getMessage(), 'already exists')) {
                throw $e;
            }
        }
    }
    if (\Illuminate\Support\Facades\Schema::hasTable('plugin_atendimento_fotos')) {
        try {
            \Illuminate\Support\Facades\Schema::table('plugin_atendimento_fotos', function (\Illuminate\Database\Schema\Blueprint $table) {
                $table->foreign('atendimento_id', 'atd_fotos_fk_atendimento')
                    ->references('id')
                    ->on('plugin_atendimento_atendimentos')
                    ->onDelete('cascade');
            });
        } catch (\Throwable $e) {
            // Evita interromper instalação em bancos com esquema legado/incompatível.
        }
    }

    if (\Illuminate\Support\Facades\Schema::hasTable('plugin_atendimento_atendimentos') && !\Illuminate\Support\Facades\Schema::hasColumn('plugin_atendimento_atendimentos', 'tecnico_id')) {
        \Illuminate\Support\Facades\Schema::table('plugin_atendimento_atendimentos', function (\Illuminate\Database\Schema\Blueprint $table) {
            $table->unsignedBigInteger('tecnico_id')->nullable()->after('prioridade_id')->comment('Técnico responsável pelo atendimento');
            $table->foreign('tecnico_id')->references('id')->on('users')->onDelete('set null');
        });
    }

    if (\Illuminate\Support\Facades\Schema::hasTable('plugin_atendimento_atendimentos') && !\Illuminate\Support\Facades\Schema::hasColumn('plugin_atendimento_atendimentos', 'ordem_servico_id')) {
        \Illuminate\Support\Facades\Schema::table('plugin_atendimento_atendimentos', function (\Illuminate\Database\Schema\Blueprint $table) {
            $table->unsignedBigInteger('ordem_servico_id')->nullable()->after('prioridade_id');
        });
    }

    if (!\Illuminate\Support\Facades\Schema::hasTable('plugin_atendimento_distance_cache')) {
        \Illuminate\Support\Facades\Schema::create('plugin_atendimento_distance_cache', function (\Illuminate\Database\Schema\Blueprint $table) {
            $table->id();
            $table->string('segment_key', 32)->unique()->comment('md5(origem|destino)');
            $table->string('distance', 50)->nullable();
            $table->string('duration', 50)->nullable();
            $table->timestamp('updated_at')->nullable();
        });
    }
});

add_action('plugins.loaded', function () {
    // Após "zerar fábrica" as tabelas podem existir, mas ficarem sem registros.
    // Precisamos repor os defaults para o formulário "create" ter opções em "Status e prioridade".

    if (!\Illuminate\Support\Facades\Schema::hasTable('plugin_atendimento_tempos_estimados')) {
        \Illuminate\Support\Facades\Schema::create('plugin_atendimento_tempos_estimados', function (\Illuminate\Database\Schema\Blueprint $table) {
            $table->id();
            $table->string('label', 80);
            $table->unsignedSmallInteger('minutos');
            $table->unsignedSmallInteger('ordem')->default(0);
            $table->timestamps();
        });
        $tempos = [
            ['label' => '30 min', 'minutos' => 30, 'ordem' => 1],
            ['label' => '1 h', 'minutos' => 60, 'ordem' => 2],
            ['label' => '2 h', 'minutos' => 120, 'ordem' => 3],
        ];
        foreach ($tempos as $row) {
            \Illuminate\Support\Facades\DB::table('plugin_atendimento_tempos_estimados')->insert(array_merge($row, [
                'created_at' => now(),
                'updated_at' => now(),
            ]));
        }
    } else {
        if (\Illuminate\Support\Facades\DB::table('plugin_atendimento_tempos_estimados')->count() === 0) {
            $tempos = [
                ['label' => '30 min', 'minutos' => 30, 'ordem' => 1],
                ['label' => '1 h', 'minutos' => 60, 'ordem' => 2],
                ['label' => '2 h', 'minutos' => 120, 'ordem' => 3],
            ];
            foreach ($tempos as $row) {
                \Illuminate\Support\Facades\DB::table('plugin_atendimento_tempos_estimados')->insert(array_merge($row, [
                    'created_at' => now(),
                    'updated_at' => now(),
                ]));
            }
        }
    }

    // Defaults: Status do Atendimento
    if (!\Illuminate\Support\Facades\Schema::hasTable('plugin_atendimento_status')) {
        \Illuminate\Support\Facades\Schema::create('plugin_atendimento_status', function (\Illuminate\Database\Schema\Blueprint $table) {
            $table->id();
            $table->string('nome', 80);
            $table->string('cor', 20)->nullable();
            $table->unsignedSmallInteger('ordem')->default(0);
            $table->timestamps();
        });
    }
    if (\Illuminate\Support\Facades\DB::table('plugin_atendimento_status')->count() === 0) {
        $defaults = [
            ['nome' => 'Pendente', 'cor' => '#6b7280', 'ordem' => 1],
            ['nome' => 'Em andamento', 'cor' => '#2563eb', 'ordem' => 2],
            ['nome' => 'Concluído', 'cor' => '#16a34a', 'ordem' => 3],
            ['nome' => 'Requer novo agendamento', 'cor' => '#dc2626', 'ordem' => 4],
        ];
        foreach ($defaults as $i => $row) {
            \Illuminate\Support\Facades\DB::table('plugin_atendimento_status')->insert(array_merge($row, [
                'created_at' => now(),
                'updated_at' => now(),
            ]));
        }
    }

    // Defaults: Prioridade
    if (!\Illuminate\Support\Facades\Schema::hasTable('plugin_atendimento_prioridades')) {
        \Illuminate\Support\Facades\Schema::create('plugin_atendimento_prioridades', function (\Illuminate\Database\Schema\Blueprint $table) {
            $table->id();
            $table->string('nome', 80);
            $table->string('cor', 20)->nullable();
            $table->unsignedSmallInteger('ordem')->default(0);
            $table->timestamps();
        });
    }
    if (\Illuminate\Support\Facades\DB::table('plugin_atendimento_prioridades')->count() === 0) {
        $prioridades = [
            ['nome' => 'Baixa', 'cor' => '#22c55e', 'ordem' => 1],
            ['nome' => 'Média', 'cor' => '#eab308', 'ordem' => 2],
            ['nome' => 'Alta', 'cor' => '#f97316', 'ordem' => 3],
            ['nome' => 'Urgente', 'cor' => '#ef4444', 'ordem' => 4],
        ];
        foreach ($prioridades as $row) {
            \Illuminate\Support\Facades\DB::table('plugin_atendimento_prioridades')->insert(array_merge($row, [
                'created_at' => now(),
                'updated_at' => now(),
            ]));
        }
    }

    if (\Illuminate\Support\Facades\Schema::hasTable('plugin_atendimento_atendimentos')) {
        $t = 'plugin_atendimento_atendimentos';
        if (!\Illuminate\Support\Facades\Schema::hasColumn($t, 'fase')) {
            \Illuminate\Support\Facades\Schema::table($t, fn (\Illuminate\Database\Schema\Blueprint $table) => $table->string('fase', 20)->default('rascunho')->after('diagnostico_tecnico'));
        }
        if (!\Illuminate\Support\Facades\Schema::hasColumn($t, 'observacoes_tecnicas')) {
            \Illuminate\Support\Facades\Schema::table($t, fn (\Illuminate\Database\Schema\Blueprint $table) => $table->text('observacoes_tecnicas')->nullable()->after('diagnostico_tecnico'));
        }
        if (!\Illuminate\Support\Facades\Schema::hasColumn($t, 'checklist_model_id')) {
            \Illuminate\Support\Facades\Schema::table($t, fn (\Illuminate\Database\Schema\Blueprint $table) => $table->unsignedBigInteger('checklist_model_id')->nullable()->after('fase'));
        }
        if (!\Illuminate\Support\Facades\Schema::hasColumn($t, 'checklist_respostas')) {
            \Illuminate\Support\Facades\Schema::table($t, fn (\Illuminate\Database\Schema\Blueprint $table) => $table->json('checklist_respostas')->nullable()->after('checklist_model_id'));
        }
        if (!\Illuminate\Support\Facades\Schema::hasColumn($t, 'checklist_campos_adhoc')) {
            \Illuminate\Support\Facades\Schema::table($t, fn (\Illuminate\Database\Schema\Blueprint $table) => $table->json('checklist_campos_adhoc')->nullable()->after('checklist_respostas'));
        }
        if (!\Illuminate\Support\Facades\Schema::hasColumn($t, 'assinatura_cliente_path')) {
            \Illuminate\Support\Facades\Schema::table($t, fn (\Illuminate\Database\Schema\Blueprint $table) => $table->string('assinatura_cliente_path', 500)->nullable()->after('checklist_campos_adhoc'));
        }
        if (!\Illuminate\Support\Facades\Schema::hasColumn($t, 'assinatura_data')) {
            \Illuminate\Support\Facades\Schema::table($t, fn (\Illuminate\Database\Schema\Blueprint $table) => $table->dateTime('assinatura_data')->nullable()->after('assinatura_cliente_path'));
        }
        if (!\Illuminate\Support\Facades\Schema::hasColumn($t, 'assinatura_local')) {
            \Illuminate\Support\Facades\Schema::table($t, fn (\Illuminate\Database\Schema\Blueprint $table) => $table->string('assinatura_local', 255)->nullable()->after('assinatura_data'));
        }
    }
    if (!\Illuminate\Support\Facades\Schema::hasTable('plugin_atendimento_fotos')) {
        try {
            \Illuminate\Support\Facades\Schema::create('plugin_atendimento_fotos', function (\Illuminate\Database\Schema\Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('atendimento_id');
                $table->string('tipo', 20)->default('evidencia');
                $table->string('legenda', 255)->nullable();
                $table->string('caminho', 500);
                $table->unsignedSmallInteger('ordem')->default(0);
                $table->timestamps();
                $table->index('atendimento_id', 'atd_fotos_idx_atendimento');
            });
        } catch (\Illuminate\Database\QueryException $e) {
            if (! str_contains($e->getMessage(), 'already exists')) {
                throw $e;
            }
        }
    }
    if (\Illuminate\Support\Facades\Schema::hasTable('plugin_atendimento_fotos')) {
        try {
            \Illuminate\Support\Facades\Schema::table('plugin_atendimento_fotos', function (\Illuminate\Database\Schema\Blueprint $table) {
                $table->foreign('atendimento_id', 'atd_fotos_fk_atendimento')
                    ->references('id')
                    ->on('plugin_atendimento_atendimentos')
                    ->onDelete('cascade');
            });
        } catch (\Throwable $e) {
            // Evita interromper instalação em bancos com esquema legado/incompatível.
        }
    }
    if (\Illuminate\Support\Facades\Schema::hasTable('plugin_atendimento_atendimentos') && !\Illuminate\Support\Facades\Schema::hasColumn('plugin_atendimento_atendimentos', 'tecnico_id')) {
        \Illuminate\Support\Facades\Schema::table('plugin_atendimento_atendimentos', function (\Illuminate\Database\Schema\Blueprint $table) {
            $table->unsignedBigInteger('tecnico_id')->nullable()->after('prioridade_id')->comment('Técnico responsável pelo atendimento');
        });
        try {
            \Illuminate\Support\Facades\Schema::table('plugin_atendimento_atendimentos', function (\Illuminate\Database\Schema\Blueprint $table) {
                $table->foreign('tecnico_id')->references('id')->on('users')->onDelete('set null');
            });
        } catch (\Throwable $e) {
            // FK opcional em alguns bancos ao alterar tabela
        }
    }
    if (!\Illuminate\Support\Facades\Schema::hasTable('plugin_atendimento_distance_cache')) {
        \Illuminate\Support\Facades\Schema::create('plugin_atendimento_distance_cache', function (\Illuminate\Database\Schema\Blueprint $table) {
            $table->id();
            $table->string('segment_key', 32)->unique();
            $table->string('distance', 50)->nullable();
            $table->string('duration', 50)->nullable();
            $table->timestamp('updated_at')->nullable();
        });
    }
}, 5);

erp_register_deactivation_hook(__FILE__, function () {
    // Não remove tabelas para preservar dados
});

add_filter('menu.items', function ($items) {
    if (!auth()->check() || !function_exists('auth')) {
        return $items;
    }
    $user = auth()->user();
    if (!method_exists($user, 'canAccessOperational') || !$user->canAccessOperational()) {
        return $items;
    }
    try {
        $pathList = parse_url(route('plugin.atendimento.index'), PHP_URL_PATH) ?: '/plugin/atendimento';
        $pathConfig = '/configuracoes-sistema/plugins/atendimento';
    } catch (\Throwable $e) {
        $pathList = '/plugin/atendimento';
        $pathConfig = '/configuracoes-sistema/plugins/atendimento';
    }
    $subItems = [
        ['name' => 'Atendimentos', 'path' => $pathList, 'pro' => false],
        ['name' => 'Novo atendimento', 'path' => $pathList . '/create', 'pro' => false],
        ['name' => 'Rota do dia', 'path' => $pathList . '/rota-do-dia', 'pro' => false],
    ];
    if ($user->is_admin || (method_exists($user, 'hasPermission') && $user->hasPermission('manage-plugins'))) {
        $subItems[] = ['name' => 'Configurações', 'path' => $pathConfig, 'pro' => false];
    }
    $items[] = [
        'name' => 'Atendimento',
        'icon' => 'task',
        'subItems' => $subItems,
    ];
    return $items;
}, 20);

add_action('ordens_servico.show.extra', function ($ordem) {
    if (!$ordem || !isset($ordem->id)) {
        return;
    }
    try {
        echo view('atendimento::ordem-servico-atendimentos', ['ordem' => $ordem])->render();
    } catch (\Throwable $e) {
        // Plugin isolado: não quebrar a tela da OS se algo falhar
    }
}, 10);
