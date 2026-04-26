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

use App\Models\CategoriaServico;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class CategoriaServicoController extends Controller
{
    public function index()
    {
        $query = $this->applyEntityQuery(CategoriaServico::query()->orderBy('nome'), 'categoria_servico');
        return erp_view('servicos.categorias.index', [
            'categorias' => $query->get(),
        ]);
    }

    public function create()
    {
        return erp_view('servicos.categorias.create');
    }

    public function store(Request $request)
    {
        $data = $this->validateWithFilters($request, [
            'nome' => 'required|string|max:120',
            'ativo' => 'nullable|boolean',
        ]);

        $slug = Str::slug($data['nome']);
        $categoria = CategoriaServico::firstOrCreate(
            ['slug' => $slug],
            ['nome' => $data['nome'], 'ativo' => !empty($data['ativo'])]
        );

        return redirect()
            ->back()
            ->with('success', 'Categoria de serviço cadastrada com sucesso!')
            ->with('categoria_servico_id', $categoria->id);
    }

    public function edit(CategoriaServico $categoriaServico)
    {
        return erp_view('servicos.categorias.edit', [
            'categoria' => $categoriaServico,
        ]);
    }

    public function update(Request $request, CategoriaServico $categoriaServico)
    {
        $data = $this->validateWithFilters($request, [
            'nome' => 'required|string|max:120',
            'ativo' => 'nullable|boolean',
        ]);

        $slug = Str::slug($data['nome']);
        if (CategoriaServico::where('slug', $slug)->where('id', '!=', $categoriaServico->id)->exists()) {
            return redirect()->back()->withErrors(['nome' => 'Já existe uma categoria com esse nome.']);
        }

        $categoriaServico->update([
            'nome' => $data['nome'],
            'slug' => $slug,
            'ativo' => !empty($data['ativo']),
        ]);

        return redirect()
            ->route('categorias-servicos.index')
            ->with('success', 'Categoria atualizada com sucesso!');
    }

    public function destroy(CategoriaServico $categoriaServico)
    {
        $categoriaServico->delete();

        return redirect()
            ->route('categorias-servicos.index')
            ->with('success', 'Categoria excluída com sucesso!');
    }

    public function updateStatus(Request $request, CategoriaServico $categoriaServico)
    {
        $data = $this->validateWithFilters($request, [
            'ativo' => 'required|boolean',
        ]);

        $categoriaServico->update([
            'ativo' => (bool) $data['ativo'],
        ]);

        return redirect()
            ->route('categorias-servicos.index')
            ->with('success', 'Status da categoria atualizado com sucesso!');
    }
}
