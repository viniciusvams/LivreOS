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

namespace App\Http\Controllers\ConfiguracoesSistema;

use App\Http\Controllers\Controller;
use App\Models\Configuracao;
use Illuminate\Http\Request;

class ProdutosEstoqueController extends Controller
{
    public function edit()
    {
        $bloquearVendaZero = Configuracao::estoqueBloquearVendaZero();

        return erp_view('configuracoes_sistema.produtos_estoque.edit', [
            'title' => 'Produtos e Estoque',
            'estoque_bloquear_venda_zero' => $bloquearVendaZero,
        ]);
    }

    public function update(Request $request)
    {
        $this->validateWithFilters($request, [
            'estoque_bloquear_venda_zero' => 'nullable|boolean',
        ]);

        Configuracao::setValue(
            'estoque_bloquear_venda_zero',
            $request->boolean('estoque_bloquear_venda_zero') ? '1' : '0'
        );

        return redirect()->route('configuracoes-sistema.produtos-estoque.edit')
            ->with('success', 'Configurações de produtos e estoque atualizadas com sucesso.');
    }
}
