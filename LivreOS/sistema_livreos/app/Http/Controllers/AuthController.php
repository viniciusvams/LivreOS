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

namespace App\Http\Controllers;

use App\Models\AuditLoginAcesso;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    public function showLoginForm()
    {
        if (Auth::check()) {
            $user = Auth::user();
            $redirect = function_exists('apply_filters') ? apply_filters('auth.login.redirect', null, $user, request()) : null;
            if ($redirect) {
                return redirect($redirect);
            }
            if ($user->is_admin) {
                return redirect()->route('admin.dashboard');
            }
            return redirect()->route('dashboard');
        }

        $formToken = Str::random(40);
        session([
            'auth.login.form_token' => $formToken,
            'auth.login.form_started_at' => now()->timestamp,
        ]);

        return erp_view('pages.auth.signin', [
            'title' => 'Entrar',
            'loginFormToken' => $formToken,
        ]);
    }

    public function login(Request $request)
    {
        $this->ensureIsNotRateLimited($request);
        $this->ensureRobotProtection($request);

        $credentials = $this->validateWithFilters($request, [
            'email' => 'required|email',
            'password' => 'required',
        ]);

        if (Auth::attempt($credentials, $request->filled('remember'))) {
            RateLimiter::clear($this->throttleKey($request));
            $request->session()->regenerate();
            $user = Auth::user();
            $this->registrarAuditLogin($request, 'login_success', 'success', 'Login realizado com sucesso.', [
                'remember' => $request->filled('remember'),
                'user_id' => $user?->id,
            ]);
            if (function_exists('do_action')) {
                do_action('auth.login.success', $user, $request);
            }

            $intended = $request->session()->pull('url.intended');
            if ($intended && $this->isAuthRouteUrl($intended, $request)) {
                $intended = null;
            }

            $loginRedirect = function_exists('apply_filters') ? apply_filters('auth.login.redirect', null, $user, $request) : null;
            if ($loginRedirect) {
                return redirect()->to($intended ?: $loginRedirect);
            }

            if ($user->is_admin) {
                return redirect()->to($intended ?: route('admin.dashboard'));
            }

            return redirect()->to($intended ?: route('dashboard'));
        }

        RateLimiter::hit($this->throttleKey($request), 300);
        $this->registrarAuditLogin($request, 'invalid_credentials', 'failed', 'Credenciais inválidas.');

        return back()->withErrors([
            'email' => 'As credenciais fornecidas não correspondem aos nossos registros.',
        ])->onlyInput('email');
    }

    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        
        return redirect()->route('signin');
    }

    /**
     * Exibe o formulário para o usuário alterar a própria senha.
     */
    public function showAlterarSenhaForm()
    {
        return erp_view('pages.alterar-senha', ['title' => 'Alterar senha']);
    }

    /**
     * Atualiza a senha do usuário logado (senha atual + nova senha).
     */
    public function updatePassword(Request $request)
    {
        $validated = $this->validateWithFilters($request, [
            'senha_atual' => 'required|string',
            'password' => ['required', 'string', 'confirmed', Password::defaults()],
        ], [], [
            'senha_atual' => 'senha atual',
            'password' => 'nova senha',
        ]);

        $user = Auth::user();
        if (!Hash::check($request->senha_atual, $user->password)) {
            return redirect()->back()->withErrors(['senha_atual' => 'A senha atual informada está incorreta.']);
        }

        $user->password = Hash::make($validated['password']);
        $user->save();

        return redirect()->route('alterar-senha')->with('success', 'Senha alterada com sucesso!');
    }

    protected function ensureIsNotRateLimited(Request $request): void
    {
        $maxAttempts = 5;
        $key = $this->throttleKey($request);

        if (!RateLimiter::tooManyAttempts($key, $maxAttempts)) {
            return;
        }

        $seconds = RateLimiter::availableIn($key);
        $this->registrarAuditLogin($request, 'rate_limited', 'blocked', 'Bloqueado por excesso de tentativas.', [
            'retry_after_seconds' => $seconds,
        ]);

        throw ValidationException::withMessages([
            'email' => 'Muitas tentativas de login. Tente novamente em ' . $seconds . ' segundos.',
        ]);
    }

    protected function throttleKey(Request $request): string
    {
        return Str::transliterate(Str::lower((string) $request->input('email')) . '|' . $request->ip());
    }

    protected function ensureRobotProtection(Request $request): void
    {
        // Honeypot: bots costumam preencher todos os campos.
        if (filled((string) $request->input('website'))) {
            RateLimiter::hit($this->throttleKey($request), 300);
            $this->registrarAuditLogin($request, 'honeypot', 'blocked', 'Bloqueado por honeypot preenchido.');
            throw ValidationException::withMessages([
                'email' => 'Não foi possível concluir o login. Tente novamente.',
            ]);
        }

        $sessionToken = (string) session('auth.login.form_token', '');
        $postedToken = (string) $request->input('login_form_token', '');
        $startedAt = (int) session('auth.login.form_started_at', 0);
        $elapsed = $startedAt > 0 ? (time() - $startedAt) : 0;

        // Token do formulário (mesma sessão) + janela de tempo razoável.
        // Nota: exigir 2s+ bloqueava login rápido (autofill, gerenciador de senhas) e
        // elapsed=0 quando a sessão não gravou started_at ainda falhava sempre.
        $isValidToken = $sessionToken !== '' && $postedToken !== '' && hash_equals($sessionToken, $postedToken);
        $isHumanTiming = $elapsed >= 0 && $elapsed <= 86400;

        if (! $isValidToken || ! $isHumanTiming) {
            RateLimiter::hit($this->throttleKey($request), 300);
            $this->registrarAuditLogin($request, 'robot_token', 'blocked', 'Bloqueado por token/timing inválido.', [
                'elapsed_seconds' => $elapsed,
                'valid_token' => $isValidToken,
            ]);
            throw ValidationException::withMessages([
                'email' => 'Não foi possível concluir o login. Recarregue a página e tente novamente.',
            ]);
        }
    }

    protected function isAuthRouteUrl(string $url, Request $request): bool
    {
        $path = parse_url($url, PHP_URL_PATH) ?? '';
        $path = '/' . ltrim($path, '/');
        $path = rtrim($path, '/') ?: '/';

        $paths = [
            parse_url(route('signin'), PHP_URL_PATH) ?: '/signin',
            parse_url(route('login'), PHP_URL_PATH) ?: '/signin',
            parse_url(route('logout'), PHP_URL_PATH) ?: '/logout',
        ];

        $normalized = array_map(static function (string $p): string {
            $p = '/' . ltrim($p, '/');
            return rtrim($p, '/') ?: '/';
        }, $paths);

        return in_array($path, $normalized, true);
    }

    protected function registrarAuditLogin(
        Request $request,
        string $evento,
        string $resultado,
        string $mensagem,
        array $metadata = []
    ): void {
        try {
            if (! Schema::hasTable('audit_login_acessos')) {
                return;
            }

            AuditLoginAcesso::create([
                'user_id' => auth()->id(),
                'email' => Str::lower((string) $request->input('email')),
                'evento' => $evento,
                'resultado' => $resultado,
                'mensagem' => $mensagem,
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(),
                'metadata' => $metadata ?: null,
            ]);
        } catch (\Throwable) {
            // Não interrompe o fluxo de login por falha de auditoria.
        }
    }
}
