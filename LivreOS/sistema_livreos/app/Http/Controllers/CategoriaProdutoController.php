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

use App\Models\CategoriaProduto;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class CategoriaProdutoController extends Controller
{
    public function index(Request $request)
    {
        $query = CategoriaProduto::query();
        if ($request->filled('nome')) {
            $query->where('nome', 'like', '%' . $request->nome . '%');
        }
        $query = $this->applyEntityQuery($query->orderBy('nome'), 'categoria_produto');
        $categorias = $query->paginate(15);

        return erp_view('categorias_produtos.index', [
            'title' => 'Categorias de Produtos',
            'categorias' => $categorias,
        ]);
    }

    public function create()
    {
        return erp_view('categorias_produtos.create', [
            'title' => 'Nova Categoria',
        ]);
    }

    public function store(Request $request)
    {
        $data = $this->validateWithFilters($request, [
            'nome' => 'required|string|max:150',
            'ativo' => 'boolean',
        ]);

        $data['slug'] = Str::slug($data['nome']);
        $data['ativo'] = !empty($data['ativo']);

        CategoriaProduto::create($data);

        return redirect()->route('categorias-produtos.index')
            ->with('success', 'Categoria cadastrada com sucesso!');
    }

    public function edit(CategoriaProduto $categoriaProduto)
    {
        return erp_view('categorias_produtos.edit', [
            'title' => 'Editar Categoria',
            'categoria' => $categoriaProduto,
        ]);
    }

    public function update(Request $request, CategoriaProduto $categoriaProduto)
    {
        $data = $this->validateWithFilters($request, [
            'nome' => 'required|string|max:150',
            'ativo' => 'boolean',
        ]);

        $data['slug'] = Str::slug($data['nome']);
        $data['ativo'] = !empty($data['ativo']);

        $categoriaProduto->update($data);

        return redirect()->route('categorias-produtos.index')
            ->with('success', 'Categoria atualizada com sucesso!');
    }

    public function destroy(CategoriaProduto $categoriaProduto)
    {
        $categoriaProduto->delete();

        return redirect()->route('categorias-produtos.index')
            ->with('success', 'Categoria excluída com sucesso!');
    }
}
