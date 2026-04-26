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
 * Plugin Name: Área do Cliente
 * Description: Portal do cliente. Permite ao cliente visualizar e acompanhar suas ordens de serviço online.
 * Version: 1.0.0
 * Author: ERP
 * Requires PHP: 8.1
 *
 * Funcionalidades:
 * - Dashboard com resumo das OS
 * - Listar ordens de serviço do cliente
 * - Visualizar detalhes da OS (status, equipamento, totais)
 * - Acesso via usuário vinculado ao cliente (cliente_id em users)
 */

require_once __DIR__ . '/src/AreaClienteMiddleware.php';
require_once __DIR__ . '/src/AreaClienteHelper.php';
require_once __DIR__ . '/src/Chamado.php';
require_once __DIR__ . '/src/ChamadoAnexo.php';
require_once __DIR__ . '/src/ChamadoController.php';
require_once __DIR__ . '/src/ChamadoResposta.php';
require_once __DIR__ . '/src/AdminChamadoController.php';
require_once __DIR__ . '/src/SettingsController.php';
require_once __DIR__ . '/src/ConfigClientesController.php';
require_once __DIR__ . '/src/UsuarioClienteController.php';

add_filter('configuracoes.plugins.settings_view', function ($view, $plugin) {
    if (($plugin['slug'] ?? '') === 'area-cliente') {
        return 'area-cliente::settings';
    }
    return $view;
}, 10, 2);

erp_register_activation_hook(__FILE__, function () {
    if (!\Illuminate\Support\Facades\Schema::hasColumn('users', 'cliente_id')) {
        \Illuminate\Support\Facades\Schema::table('users', function (\Illuminate\Database\Schema\Blueprint $table) {
            $table->unsignedBigInteger('cliente_id')->nullable()->after('remember_token');
        });
    }
    if (!\Illuminate\Support\Facades\Schema::hasTable('plugin_area_cliente_chamados')) {
        \Illuminate\Support\Facades\Schema::create('plugin_area_cliente_chamados', function (\Illuminate\Database\Schema\Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('cliente_id');
            $table->unsignedBigInteger('user_id')->nullable();
            $table->string('titulo');
            $table->text('descricao')->nullable();
            $table->string('prioridade', 20)->default('media');
            $table->string('status', 30)->default('aberto');
            $table->timestamps();
            $table->foreign('cliente_id')->references('id')->on('clientes')->onDelete('cascade');
            $table->foreign('user_id')->references('id')->on('users')->onDelete('set null');
        });
    }
    if (\Illuminate\Support\Facades\Schema::hasTable('plugin_area_cliente_chamados') && !\Illuminate\Support\Facades\Schema::hasColumn('plugin_area_cliente_chamados', 'ordem_servico_id')) {
        \Illuminate\Support\Facades\Schema::table('plugin_area_cliente_chamados', function (\Illuminate\Database\Schema\Blueprint $table) {
            $table->unsignedBigInteger('ordem_servico_id')->nullable()->after('user_id');
        });
        if (\Illuminate\Support\Facades\Schema::hasTable('ordem_servicos')) {
            \Illuminate\Support\Facades\Schema::table('plugin_area_cliente_chamados', function (\Illuminate\Database\Schema\Blueprint $table) {
                $table->foreign('ordem_servico_id')->references('id')->on('ordem_servicos')->onDelete('set null');
            });
        }
    }
    if (!\Illuminate\Support\Facades\Schema::hasTable('plugin_area_cliente_chamado_anexos')) {
        \Illuminate\Support\Facades\Schema::create('plugin_area_cliente_chamado_anexos', function (\Illuminate\Database\Schema\Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('chamado_id');
            $table->unsignedBigInteger('chamado_resposta_id')->nullable();
            $table->string('path');
            $table->string('nome_original')->nullable();
            $table->timestamps();
            $table->foreign('chamado_id')->references('id')->on('plugin_area_cliente_chamados')->onDelete('cascade');
        });
    }
    if (\Illuminate\Support\Facades\Schema::hasTable('plugin_area_cliente_chamado_anexos') && !\Illuminate\Support\Facades\Schema::hasColumn('plugin_area_cliente_chamado_anexos', 'chamado_resposta_id')) {
        \Illuminate\Support\Facades\Schema::table('plugin_area_cliente_chamado_anexos', function (\Illuminate\Database\Schema\Blueprint $table) {
            $table->unsignedBigInteger('chamado_resposta_id')->nullable()->after('chamado_id');
        });
    }
    if (!\Illuminate\Support\Facades\Schema::hasTable('plugin_area_cliente_chamado_respostas')) {
        \Illuminate\Support\Facades\Schema::create('plugin_area_cliente_chamado_respostas', function (\Illuminate\Database\Schema\Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('chamado_id');
            $table->unsignedBigInteger('user_id')->nullable();
            $table->text('mensagem');
            $table->boolean('interno')->default(false);
            $table->timestamps();
            $table->foreign('chamado_id')->references('id')->on('plugin_area_cliente_chamados')->onDelete('cascade');
            $table->foreign('user_id')->references('id')->on('users')->onDelete('set null');
        });
    }
});

erp_register_deactivation_hook(__FILE__, function () {
    // Não removemos cliente_id pois pode haver dados
});

add_filter('menu.items', function ($items) {
    if (!auth()->check()) {
        return $items;
    }
    $user = auth()->user();
    $clienteId = $user->cliente_id ?? null;
    if (!$clienteId) {
        return $items;
    }
    $cliente = \App\Models\Cliente::find($clienteId);
    if (!$cliente || !($cliente->portal_suporte ?? false)) {
        return $items;
    }

    // Cliente do portal: exibe apenas o menu da Área do Cliente (oculta menus sem funcionalidade)
    return [
        [
            'name' => 'Área do Cliente',
            'icon' => 'user-profile',
            'subItems' => [
                ['name' => 'Minhas Ordens', 'path' => '/plugin/area-cliente', 'pro' => false],
                ['name' => 'Tickets', 'path' => '/plugin/area-cliente/chamados', 'pro' => false],
                ['name' => 'Meu Perfil', 'path' => '/plugin/area-cliente/perfil', 'pro' => false],
                ['name' => 'Ajuda', 'path' => '/plugin/area-cliente/ajuda', 'pro' => false],
            ],
        ],
    ];
}, 5);

/**
 * Menu principal: item "Área do Cliente" para usuários do sistema (não portal).
 * Configuração (habilitar clientes) e Central de tickets.
 * Só aparece para quem tem acesso operacional ou é admin, e tem permissão manage-plugins.
 */
add_filter('menu.items', function ($items) {
    if (!auth()->check()) {
        return $items;
    }
    $user = auth()->user();
    $clienteId = $user->cliente_id ?? null;
    if ($clienteId) {
        $cliente = \App\Models\Cliente::find($clienteId);
        if ($cliente && ($cliente->portal_suporte ?? false)) {
            return $items;
        }
    }
    if (!method_exists($user, 'canAccessOperational') || !$user->canAccessOperational()) {
        if (empty($user->is_admin)) {
            return $items;
        }
    }
    $subItems = [];
    if ($user->is_admin || (method_exists($user, 'hasPermission') && $user->hasPermission('manage-plugins'))) {
        $subItems[] = ['name' => 'Configuração', 'path' => '/plugin/area-cliente/configuracoes/clientes', 'pro' => false];
    }
    $subItems[] = ['name' => 'Central de tickets', 'path' => '/plugin/area-cliente/admin/chamados', 'pro' => false];
    $items[] = [
        'name' => 'Área do Cliente',
        'icon' => 'user-profile',
        'subItems' => $subItems,
    ];
    return $items;
}, 10);

add_filter('menu.groups', function ($groups) {
    if (!auth()->check()) {
        return $groups;
    }
    $user = auth()->user();
    $clienteId = $user->cliente_id ?? null;
    if (!$clienteId) {
        return $groups;
    }
    $cliente = \App\Models\Cliente::find($clienteId);
    if (!$cliente || !($cliente->portal_suporte ?? false)) {
        return $groups;
    }
    // Cliente do portal: exibe apenas o grupo Menu (sem Others)
    return array_filter($groups, fn($g) => ($g['title'] ?? '') === 'Menu');
}, 5);

// --- Hooks de integração (sem alterar o core) ---

add_filter('user.fillable', function ($default) {
    $default[] = 'cliente_id';
    return $default;
});

add_filter('admin.users.create.data', function ($data) {
    $data['clientes'] = \App\Models\Cliente::orderBy('nome')->get();
    return $data;
});

add_filter('admin.users.edit.data', function ($data) {
    $data['clientes'] = \App\Models\Cliente::orderBy('nome')->get();
    return $data;
});

add_action('admin.users.form.extra', function ($user) {
    $clientes = \App\Models\Cliente::orderBy('nome')->get();
    $selected = $user ? $user->cliente_id : old('cliente_id');
    include __DIR__ . '/views/users-form-extra.php';
});

add_filter('admin.user.store.rules', function ($rules) {
    $rules['cliente_id'] = 'nullable|exists:clientes,id';
    return $rules;
});

add_filter('admin.user.store.data', function ($extra, $request) {
    $extra['cliente_id'] = $request->filled('cliente_id') ? $request->input('cliente_id') : null;
    return $extra;
}, 10, 2);

add_filter('admin.user.update.rules', function ($rules) {
    $rules['cliente_id'] = 'nullable|exists:clientes,id';
    return $rules;
});

add_filter('admin.user.update.data', function ($extra, $request) {
    $extra['cliente_id'] = $request->filled('cliente_id') ? $request->input('cliente_id') : null;
    return $extra;
}, 10, 2);

add_filter('auth.login.redirect', function ($redirect, $user) {
    $clienteId = $user->cliente_id ?? null;
    if (!$clienteId) {
        return $redirect;
    }
    $cliente = \App\Models\Cliente::find($clienteId);
    if ($cliente && ($cliente->portal_suporte ?? false)) {
        return route('plugin.area-cliente.index');
    }
    return $redirect;
}, 10, 2);

/**
 * Busca OS no header: na área do cliente redireciona para o índice do plugin com param "busca".
 * Só altera o comportamento quando o plugin está ativo e o usuário é do portal.
 */
add_filter('header.busca.os', function ($data) {
    if (!auth()->check()) {
        return $data;
    }
    $user = auth()->user();
    $clienteId = $user->cliente_id ?? null;
    if (!$clienteId) {
        return $data;
    }
    $cliente = \App\Models\Cliente::find($clienteId);
    if (!$cliente || !($cliente->portal_suporte ?? false)) {
        return $data;
    }
    return [
        'url' => route('plugin.area-cliente.index'),
        'param' => 'busca',
        'valor' => request('busca', ''),
    ];
});

add_filter('middleware.aliases', function ($aliases) {
    $aliases['area-cliente'] = \AreaCliente\AreaClienteMiddleware::class;
    return $aliases;
});

/**
 * Notificações no header: mensagens conforme a última ação no ticket.
 * Portal (cliente): seus tickets — "Você abriu", "Equipe respondeu", "Você respondeu".
 * Admin: todos os tickets — "Cliente abriu", "Equipe respondeu", "Cliente respondeu".
 */
add_filter('header.notifications', function ($notifications) {
    if (!auth()->check() || !\Illuminate\Support\Facades\Schema::hasTable('plugin_area_cliente_chamados')) {
        return $notifications;
    }
    $user = auth()->user();
    $clienteId = $user->cliente_id ?? null;
    $isPortal = false;
    if ($clienteId) {
        $cliente = \App\Models\Cliente::find($clienteId);
        if ($cliente && ($cliente->portal_suporte ?? false)) {
            $isPortal = true;
        }
    }

    if ($isPortal) {
        $ids = [$clienteId];
        if ($cliente->grupo_economico_id ?? null) {
            $unidades = \App\Models\Cliente::where('grupo_economico_id', $cliente->grupo_economico_id)->pluck('id')->toArray();
            $ids = array_unique(array_merge($ids, $unidades));
        }
        $filiais = \App\Models\Cliente::where('matriz_id', $clienteId)->pluck('id')->toArray();
        $ids = array_unique(array_merge($ids, $filiais));
        $chamados = AreaCliente\Chamado::with('respostas')
            ->whereIn('cliente_id', $ids)
            ->orderByDesc('updated_at')
            ->take(5)
            ->get();
        foreach ($chamados as $c) {
            $ultimaResposta = $c->respostas->sortByDesc('created_at')->first();
            if (!$ultimaResposta) {
                $titulo = 'Você abriu o ticket';
                $time   = $c->created_at;
                $icone  = 'info';
            } elseif ($ultimaResposta->interno) {
                $titulo = 'Equipe respondeu no ticket';
                $time   = $ultimaResposta->created_at;
                $icone  = 'alerta';
            } else {
                $titulo = 'Você respondeu no ticket';
                $time   = $ultimaResposta->created_at;
                $icone  = 'info';
            }
            $notifications[] = [
                'id'      => null, // gerado dinamicamente; não está na tabela notificacoes_usuarios
                'titulo'  => $titulo,
                'mensagem'=> '#' . $c->id . ' – ' . AreaCliente\Chamado::statusLabel($c->status),
                'url'     => route('plugin.area-cliente.chamados.show', $c->id),
                'icone'   => $icone,
                'tipo'    => 'Ticket',
                'time'    => $time->diffForHumans(),
            ];
        }
        return $notifications;
    }

    $chamados = AreaCliente\Chamado::with(['cliente', 'respostas'])
        ->orderByDesc('updated_at')
        ->take(5)
        ->get();
    foreach ($chamados as $c) {
        $clienteNome    = $c->cliente ? ($c->cliente->nome ?? $c->cliente->razao_social ?? 'Cliente') : 'Cliente';
        $ultimaResposta = $c->respostas->sortByDesc('created_at')->first();
        if (!$ultimaResposta) {
            $titulo = $clienteNome . ' abriu o ticket';
            $time   = $c->created_at;
            $icone  = 'alerta';
        } elseif ($ultimaResposta->interno) {
            $titulo = 'Equipe respondeu no ticket';
            $time   = $ultimaResposta->created_at;
            $icone  = 'sucesso';
        } else {
            $titulo = $clienteNome . ' respondeu no ticket';
            $time   = $ultimaResposta->created_at;
            $icone  = 'alerta';
        }
        $notifications[] = [
            'id'      => null, // gerado dinamicamente; não está na tabela notificacoes_usuarios
            'titulo'  => $titulo,
            'mensagem'=> '#' . $c->id . ' – ' . \Illuminate\Support\Str::limit($c->titulo, 40),
            'url'     => route('plugin.area-cliente.admin.chamados.show', $c->id),
            'icone'   => $icone,
            'tipo'    => 'Ticket',
            'time'    => $time->diffForHumans(),
        ];
    }
    return $notifications;
});

/**
 * Link "Meu Perfil" no dropdown do usuário para clientes do portal (evita referência direta no sistema).
 */
add_filter('header.user.portal_profile', function ($value, $ehPortal) {
    if (!$ehPortal || !\Illuminate\Support\Facades\Route::has('plugin.area-cliente.perfil')) {
        return $value;
    }
    return [
        'path' => route('plugin.area-cliente.perfil'),
        'label' => 'Meu Perfil',
    ];
}, 10, 2);

/**
 * URL do botão "Ver todas" no dropdown de notificações.
 * Portal (cliente): lista de chamados do cliente; Admin: lista de chamados do painel.
 */
add_filter('header.notifications.view_all_url', function ($url) {
    if (!auth()->check() || !\Illuminate\Support\Facades\Schema::hasTable('plugin_area_cliente_chamados')) {
        return $url;
    }
    $clienteId = auth()->user()->cliente_id ?? null;
    if ($clienteId) {
        $cliente = \App\Models\Cliente::find($clienteId);
        if ($cliente && ($cliente->portal_suporte ?? false)) {
            return route('plugin.area-cliente.chamados.index');
        }
    }
    return route('plugin.area-cliente.admin.chamados.index');
});

// --- portal_suporte: leitura/cast no model; habilitação só em Configurações do plugin (configuracoes/clientes) ---

add_filter('cliente.casts', function ($default) {
    $default['portal_suporte'] = 'boolean';

    return $default;
});

// --- Sininho: OS aguardando aprovação (usuários do portal) ---
add_action('model.updated', function ($model) {
    if (!$model instanceof \App\Models\OrdemServico || !$model->wasChanged('status')) {
        return;
    }
    if ($model->status !== 'Aguardando aprovação' || !\Illuminate\Support\Facades\Schema::hasTable('notificacoes_usuarios')) {
        return;
    }
    $cliente = $model->cliente ?? \App\Models\Cliente::find($model->cliente_id);
    if (!$cliente || !($cliente->portal_suporte ?? false)) {
        return;
    }
    $users = \App\Models\User::where('cliente_id', $model->cliente_id)->get();
    if ($users->isEmpty()) {
        return;
    }
    $osRef = function_exists('format_os_display') ? format_os_display($model) : ('OS #' . $model->id);
    foreach ($users as $u) {
        \App\Models\NotificacaoUsuario::create([
            'user_id' => $u->id,
            'tipo' => 'os',
            'titulo' => 'Assinatura necessária — ' . $osRef,
            'mensagem' => 'Esta ordem aguarda sua assinatura digital para aprovação. Abra a OS na área do cliente.',
            'url' => route('plugin.area-cliente.os.show', $model),
            'icone' => 'alerta',
        ]);
    }
}, 15);

// --- Sininho: OS aprovada pelo cliente (equipe interna; hook disparado pelo core após assinatura) ---
add_action('ordem_servico.aprovada_cliente_assinatura', function (\App\Models\OrdemServico $os, string $statusAnterior) {
    if (!\Illuminate\Support\Facades\Schema::hasTable('notificacoes_usuarios')) {
        return;
    }
    $osRef = function_exists('format_os_display') ? format_os_display($os) : ('OS #' . $os->id);
    $url = route('ordens-servico.edit', $os);

    $ids = \App\Models\User::query()
        ->where(function ($q) {
            $q->where('is_admin', true)
                ->orWhereHas('roles.permissions', function ($p) {
                    $p->where('slug', 'view-os-history');
                });
        })
        ->whereNull('cliente_id')
        ->pluck('id');

    $ids = $ids
        ->merge(collect([$os->created_by, $os->updated_by])->filter())
        ->unique()
        ->values();

    foreach ($ids as $uid) {
        $user = \App\Models\User::find($uid);
        if (!$user || $user->cliente_id) {
            continue;
        }
        \App\Models\NotificacaoUsuario::create([
            'user_id' => $user->id,
            'tipo' => 'os',
            'titulo' => 'Cliente aprovou — ' . $osRef,
            'mensagem' => 'A ordem foi assinada pelo cliente e o status passou para Aprovado pelo cliente.',
            'url' => $url,
            'icone' => 'sucesso',
        ]);
    }
}, 10, 2);

// --- Notificações por e-mail quando o status da OS muda ---
add_action('model.updated', function ($model) {
    if (!$model instanceof \App\Models\OrdemServico) {
        return;
    }
    if (!$model->wasChanged('status')) {
        return;
    }

    $cliente = $model->cliente ?? \App\Models\Cliente::find($model->cliente_id);
    if (!$cliente || !($cliente->portal_suporte ?? false)) {
        return;
    }

    $emails = [];
    $users = \App\Models\User::where('cliente_id', $model->cliente_id)->whereNotNull('email')->pluck('email');
    foreach ($users as $e) {
        if (filter_var($e, FILTER_VALIDATE_EMAIL)) {
            $emails[] = $e;
        }
    }
    if (empty($emails) && $cliente->email && filter_var($cliente->email, FILTER_VALIDATE_EMAIL)) {
        $emails[] = $cliente->email;
    }
    $contato = $model->contato ?? null;
    if (empty($emails) && $contato?->email && filter_var($contato->email, FILTER_VALIDATE_EMAIL)) {
        $emails[] = $contato->email;
    }
    if (empty($emails)) {
        return;
    }

    $novoStatus = format_status_os_display($model->status);
    $osDisplay = format_os_display($model);
    $url = route('plugin.area-cliente.os.show', $model);
    $assunto = 'Atualização da sua Ordem de Serviço - ' . $osDisplay . ' - Status: ' . $novoStatus;
    $corpo = "Olá,\n\nA ordem de serviço {$osDisplay} teve seu status alterado para: {$novoStatus}.\n\n";
    $corpo .= "Acesse a área do cliente para mais detalhes: {$url}\n\n";
    $corpo .= "Esta é uma mensagem automática. Por favor, não responda.";

    try {
        foreach (array_unique($emails) as $email) {
            \Illuminate\Support\Facades\Mail::raw($corpo, function ($msg) use ($email, $assunto) {
                $msg->to($email)->subject($assunto);
            });
        }
    } catch (\Throwable $e) {
        if (function_exists('error_log')) {
            error_log('[Área do Cliente] Erro ao enviar notificação de status: ' . $e->getMessage());
        }
    }
}, 20);
