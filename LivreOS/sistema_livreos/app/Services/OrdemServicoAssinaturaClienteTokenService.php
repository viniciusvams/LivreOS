<?php

/**
 * Componente da aplicação LivreOS
 *
 * @author    viniciusvams
 * @copyright 2024-2026 LivreOS
 * @license   https://www.gnu.org/licenses/agpl-3.0.txt AGPL-3.0
 */

namespace App\Services;

use App\Models\OrdemServico;
use App\Models\OrdemServicoAssinaturaEvent;
use App\Models\OrdemServicoAssinaturaToken;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

/**
 * Geração de link/token público de assinatura do cliente (reutilizado pelo core e pelo portal).
 */
class OrdemServicoAssinaturaClienteTokenService
{
    public function gerarNovoToken(OrdemServico $ordem): OrdemServicoAssinaturaToken
    {
        OrdemServicoAssinaturaToken::where('ordem_servico_id', $ordem->id)
            ->where('tipo', 'cliente')
            ->update(['status' => 'revoked']);

        $token = Str::random(48);

        return OrdemServicoAssinaturaToken::create([
            'ordem_servico_id' => $ordem->id,
            'tipo' => 'cliente',
            'token' => $token,
            'status' => 'sent',
            'expires_at' => now()->addDays(7),
        ]);
    }

    public function urlPublicaAssinatura(string $token): string
    {
        return route('assinaturas.cliente', ['token' => $token]);
    }

    public function registrarLogTokenGerado(OrdemServico $ordem, OrdemServicoAssinaturaToken $tokenModel, Request $request, string $sessionUuid): void
    {
        OrdemServicoAssinaturaEvent::create([
            'ordem_servico_id' => $ordem->id,
            'tipo' => 'cliente',
            'evento' => 'token_gerado',
            'data' => [
                'token' => $tokenModel->token,
                'expires_at' => $tokenModel->expires_at?->toIso8601String(),
            ],
            'ip' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'session_uuid' => $sessionUuid,
        ]);
    }
}
