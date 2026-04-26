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

namespace App\Http\Controllers\Api;

use Illuminate\Http\JsonResponse;

class ApiInfoController extends ApiController
{
    /**
     * Informações da API (versão e recursos). Rota pública para o app verificar disponibilidade.
     * GET /api
     */
    public function index(): JsonResponse
    {
        return $this->success([
            'name' => 'ERP API',
            'version' => '1.0',
            'documentation' => 'Consulte docs/API.md para autenticação e exemplos.',
            'endpoints' => [
                ['method' => 'POST', 'path' => '/api/auth/login'],
                ['method' => 'GET', 'path' => '/api/auth/me', 'auth' => true],
                ['method' => 'POST', 'path' => '/api/auth/logout', 'auth' => true],
                ['method' => 'POST', 'path' => '/api/auth/alterar-senha', 'auth' => true],
                ['method' => 'GET', 'path' => '/api/clientes', 'auth' => true],
                ['method' => 'GET', 'path' => '/api/produtos', 'auth' => true],
                ['method' => 'GET', 'path' => '/api/servicos', 'auth' => true],
                ['method' => 'GET', 'path' => '/api/categorias-produtos', 'auth' => true],
                ['method' => 'GET', 'path' => '/api/categorias-servicos', 'auth' => true],
                ['method' => 'GET', 'path' => '/api/fornecedores', 'auth' => true],
                ['method' => 'GET', 'path' => '/api/grupos-economicos', 'auth' => true],
                ['method' => 'GET', 'path' => '/api/ordens-servico', 'auth' => true],
                ['method' => 'GET', 'path' => '/api/status-os', 'auth' => true],
                ['method' => 'GET', 'path' => '/api/tags', 'auth' => true],
                ['method' => 'GET', 'path' => '/api/notificacoes', 'auth' => true],
                ['method' => 'GET', 'path' => '/api/contas-receber', 'auth' => true],
                ['method' => 'GET', 'path' => '/api/contas-pagar', 'auth' => true],
                ['method' => 'GET', 'path' => '/api/contas-bancarias', 'auth' => true],
                ['method' => 'GET', 'path' => '/api/formas-pagamento', 'auth' => true],
                ['method' => 'GET', 'path' => '/api/plano-contas', 'auth' => true],
                ['method' => 'GET', 'path' => '/api/centros-custo', 'auth' => true],
                ['method' => 'GET', 'path' => '/api/movimentacoes', 'auth' => true],
                ['method' => 'GET', 'path' => '/api/adquirentes', 'auth' => true],
            ],
        ]);
    }
}
