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

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Illuminate\Database\Eloquent\ModelNotFoundException;

$app = Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withSchedule(function (Schedule $schedule): void {
        $schedule->command('financeiro:gerar-recorrentes')->daily();
        $schedule->command('financeiro:gerar-contas-pagar-recorrentes')->daily();
        $schedule->command('financeiro:aplicar-reajuste-recorrentes')->monthlyOn(1, '03:00');
        if (function_exists('do_action')) {
            do_action('plugins.schedule', $schedule);
        }
    })
    ->withMiddleware(function (Middleware $middleware): void {
        $aliases = [
            'admin' => \App\Http\Middleware\IsAdmin::class,
            'operational' => \App\Http\Middleware\CanAccessOperational::class,
            'permission' => \App\Http\Middleware\HasPermission::class,
            'role' => \App\Http\Middleware\HasRole::class,
            'api.auth' => \App\Http\Middleware\AuthenticateApiToken::class,
            'api.operational' => \App\Http\Middleware\CanAccessOperationalApi::class,
            'api.permission' => \App\Http\Middleware\HasPermissionApi::class,
        ];
        if (function_exists('apply_filters')) {
            $aliases = array_merge($aliases, apply_filters('middleware.aliases', []));
        }
        $middleware->alias($aliases);
        $middleware->redirectGuestsTo(fn () => route('signin'));
        // Permite acesso pelo IP da máquina (ex.: http://192.168.x.x/erp/) em ambiente local / Laragon.
        // Não usar app()->environment() aqui: nessa fase o container ainda não tem o binding 'env' (erro ao acessar por subpasta).
        $appEnv = $_ENV['APP_ENV'] ?? getenv('APP_ENV') ?: 'production';
        if ($appEnv === 'local') {
            $middleware->trustProxies(at: '*');
        }
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        // Requisições à API sempre recebem exceções em JSON (404, 500, etc.)
        $exceptions->shouldRenderJsonWhen(function (Request $request, \Throwable $e) {
            return $request->is('api/*');
        });

        // Formato consistente de erro JSON para 404 na API
        $exceptions->renderable(function (NotFoundHttpException $e, Request $request) {
            if ($request->is('api/*')) {
                return response()->json([
                    'success' => false,
                    'message' => 'Recurso não encontrado.',
                    'error' => 'not_found',
                ], 404);
            }
        });

        $exceptions->renderable(function (ModelNotFoundException $e, Request $request) {
            if ($request->is('api/*')) {
                return response()->json([
                    'success' => false,
                    'message' => 'Recurso não encontrado.',
                    'error' => 'not_found',
                ], 404);
            }
        });
    })->create();

// Carregar plugins antes da resolução do HttpKernel (middleware.aliases precisa deles)
require_once $app->path('Helpers/plugin_helpers.php');
\App\Services\PluginManager::instance()->bootstrapEarly();

return $app;
