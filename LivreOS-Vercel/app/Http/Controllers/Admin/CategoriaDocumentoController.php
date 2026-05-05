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

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\CategoriaDocumento;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class CategoriaDocumentoController extends Controller
{
    public function index()
    {
        $query = $this->applyEntityQuery(CategoriaDocumento::query()->orderBy('nome'), 'categoria_documento');
        $categorias = $query->paginate(15);
        
        return erp_view('admin.categorias-documentos.index', [
            'title' => 'Categorias de Documentos',
            'categorias' => $categorias,
        ]);
    }

    public function store(Request $request)
    {
        $this->validateWithFilters($request, [
            'nome' => 'required|string|max:100|unique:categorias_documentos',
            'descricao' => 'nullable|string',
        ]);

        CategoriaDocumento::create([
            'nome' => $request->nome,
            'slug' => Str::slug($request->nome),
            'descricao' => $request->descricao,
            'ativo' => true,
        ]);

        return redirect()->back()
            ->with('success', 'Categoria criada com sucesso!');
    }

    public function update(Request $request, CategoriaDocumento $categoriaDocumento)
    {
        $this->validateWithFilters($request, [
            'nome' => 'required|string|max:100|unique:categorias_documentos,nome,' . $categoriaDocumento->id,
            'descricao' => 'nullable|string',
            'ativo' => 'boolean',
        ]);

        $categoriaDocumento->update([
            'nome' => $request->nome,
            'slug' => Str::slug($request->nome),
            'descricao' => $request->descricao,
            'ativo' => $request->has('ativo'),
        ]);

        return redirect()->back()
            ->with('success', 'Categoria atualizada com sucesso!');
    }

    public function destroy(CategoriaDocumento $categoriaDocumento)
    {
        $categoriaDocumento->delete();
        
        return redirect()->back()
            ->with('success', 'Categoria excluída com sucesso!');
    }
}
