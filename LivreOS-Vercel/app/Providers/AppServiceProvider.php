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

namespace App\Providers;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\ServiceProvider;

/**
 * LivreOS - Application Service Provider
 *
 * @author viniciusvams
 * @license GPL-3.0
 */
class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->applySqliteConcurrencyPragmas();
        $this->rebindDompdfComCaminhoPublicoResolvivel();

        // Compartilha com todas as views se as tarefas devem rodar ao acessar (para script oculto no layout)
        try {
            if (\Illuminate\Support\Facades\Schema::hasTable('configuracoes')) {
                \Illuminate\Support\Facades\View::share('run_tarefas_ao_acessar', \App\Models\Configuracao::runTarefasAoAcessar());
            } else {
                \Illuminate\Support\Facades\View::share('run_tarefas_ao_acessar', false);
            }
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\View::share('run_tarefas_ao_acessar', false);
        }

        // Blade directives para verificar permissões
        \Illuminate\Support\Facades\Blade::if('hasPermission', function ($permission) {
            return auth()->check() && auth()->user()->hasPermission($permission);
        });

        \Illuminate\Support\Facades\Blade::if('hasRole', function ($role) {
            return auth()->check() && auth()->user()->hasRole($role);
        });

        \Illuminate\Support\Facades\Blade::if('isAdmin', function () {
            return auth()->check() && auth()->user()->is_admin;
        });

        // Notificações financeiras no dropdown do header
        if (function_exists('add_filter')) {
            add_filter('header.notifications', function (array $items) {
                if (!auth()->check()) {
                    return $items;
                }

                try {
                    $user = auth()->user();
                    $service = app(\App\Services\NotificacaoFinanceiroService::class);
                    $service->garantirNotificacoesDiarias($user);

                    $notifs = \App\Models\NotificacaoUsuario::where('user_id', $user->id)
                        ->where('lida', false)
                        ->orderByDesc('created_at')
                        ->limit(12)
                        ->get();

                    foreach ($notifs as $n) {
                        $items[] = [
                            'id'      => $n->id,
                            'titulo'  => $n->titulo,
                            'mensagem' => $n->mensagem,
                            'url'     => $n->url ?? '#',
                            'icone'   => $n->icone ?? 'info',
                            'tipo'    => $n->tipo,
                            'time'    => $n->created_at->diffForHumans(),
                            'lida'    => $n->lida,
                        ];
                    }
                } catch (\Throwable $e) {
                    report($e);
                }

                return $items;
            }, 10);

            add_filter('header.notifications.view_all_url', function ($url) {
                try {
                    return route('notificacoes.index');
                } catch (\Throwable $e) {
                    return $url;
                }
            }, 10);
        }

        if ($this->app->runningInConsole()) {
            $this->commands([
                \App\Console\Commands\ImportNcmCodes::class,
                \App\Console\Commands\GerarContasRecorrentes::class,
                \App\Console\Commands\AplicarReajusteRecorrentes::class,
                \App\Console\Commands\GerarContasPagarRecorrentes::class,
            ]);
        }

        if (!$this->app->runningInConsole() && $this->app->environment('local')) {
            try {
                $request = request();
                if ($request && $request->getSchemeAndHttpHost()) {
                    $basePath = $request->getBasePath();
                    $baseUrl = rtrim($request->getSchemeAndHttpHost() . $basePath, '/');
                    if ($baseUrl !== '' && $baseUrl !== '0') {
                        \Illuminate\Support\Facades\URL::forceRootUrl($baseUrl);
                        \Illuminate\Support\Facades\URL::forceScheme($request->getScheme());
                        config([
                            'app.url' => $baseUrl,
                            'app.asset_url' => $baseUrl,
                        ]);
                        // Cookie de sessão no path correto quando a app está em subpasta (ex.: /erp/)
                        if ($basePath !== '' && $basePath !== '/') {
                            config(['session.path' => $basePath]);
                        }
                    }
                }
            } catch (\Throwable $e) {
                report($e);
            }
        }
    }

    /**
     * DomPDF (barryvdh): o binding original faz realpath() e lança se falhar. Em alguns hosts realpath(public_path())
     * retorna false (open_basedir, FS), embora o diretório exista — o pacote ainda assim quebra ao instanciar.
     * Substitui o singleton para usar caminho canônico quando possível, ou o string de public_path() se for diretório.
     */
    private function rebindDompdfComCaminhoPublicoResolvivel(): void
    {
        if (! class_exists(\Dompdf\Dompdf::class)) {
            return;
        }

        $this->app->bind('dompdf', function ($app) {
            $options = $app->make('dompdf.options');
            $dompdf = new \Dompdf\Dompdf($options);
            $dompdf->setBasePath($this->resolverCaminhoPublicoDompdf($app));

            return $dompdf;
        });
    }

    /**
     * @param  \Illuminate\Contracts\Foundation\Application  $app
     */
    private function resolverCaminhoPublicoDompdf($app): string
    {
        $candidates = [];
        $cfg = $app['config']->get('dompdf.public_path');
        if (is_string($cfg) && $cfg !== '') {
            $candidates[] = $cfg;
        }
        $candidates[] = $app->publicPath();
        $candidates[] = base_path('public');

        foreach ($candidates as $candidate) {
            if (! is_string($candidate) || $candidate === '') {
                continue;
            }
            $resolved = @realpath($candidate);
            if ($resolved !== false) {
                return $resolved;
            }
            if (@is_dir($candidate)) {
                return rtrim($candidate, '/\\');
            }
        }

        throw new \RuntimeException('DomPDF: não foi possível resolver o caminho público (tente verificar public_path e permissões).');
    }

    /**
     * SQLite: PRAGMA busy_timeout e WAL reduzem "database is locked" sob concorrência (ex.: sessão + fila + HTTP).
     */
    private function applySqliteConcurrencyPragmas(): void
    {
        foreach (config('database.connections', []) as $name => $cfg) {
            if (($cfg['driver'] ?? '') !== 'sqlite') {
                continue;
            }
            try {
                $pdo = DB::connection($name)->getPdo();
                if ($pdo->getAttribute(\PDO::ATTR_DRIVER_NAME) !== 'sqlite') {
                    continue;
                }
                $busy = (int) ($cfg['busy_timeout'] ?? env('SQLITE_BUSY_TIMEOUT_MS', 10000));
                if ($busy > 0) {
                    $pdo->exec('PRAGMA busy_timeout=' . $busy);
                }
                $journal = $cfg['journal_mode'] ?? null;
                if ($journal === null) {
                    $journal = filter_var(env('SQLITE_WAL', true), FILTER_VALIDATE_BOOLEAN) ? 'WAL' : 'DELETE';
                }
                $allowedJournal = ['WAL', 'DELETE', 'TRUNCATE', 'PERSIST', 'MEMORY', 'OFF'];
                if (is_string($journal) && in_array(strtoupper($journal), $allowedJournal, true)) {
                    $pdo->exec('PRAGMA journal_mode=' . strtoupper($journal));
                }
            } catch (\Throwable $e) {
                // Banco ainda não criado, artisan sem DB, etc.
            }
        }
    }
}
