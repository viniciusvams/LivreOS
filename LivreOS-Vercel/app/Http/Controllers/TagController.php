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

use App\Models\Tag;
use Illuminate\Http\Request;

class TagController extends Controller
{
    public function index()
    {
        $query = Tag::orderBy('tipo')->orderBy('nome');
        $query = $this->applyEntityQuery($query, 'tag');
        $tags = $query->paginate(15);
        
        return erp_view('tags.index', [
            'title' => 'Cadastro de Tags',
            'tags' => $tags,
        ]);
    }

    public function create()
    {
        return erp_view('tags.create', [
            'title' => 'Nova Tag',
        ]);
    }

    public function store(Request $request)
    {
        $this->validateWithFilters($request, [
            'nome' => 'required|string|max:255',
            'descricao' => 'nullable|string',
            'cor_fundo' => 'required|string|regex:/^#[0-9A-Fa-f]{6}$/',
            'cor_fonte' => 'required|string|regex:/^#[0-9A-Fa-f]{6}$/',
        ]);

        Tag::create([
            'nome' => $request->nome,
            'tipo' => 'personalizado', // Sempre personalizado para novas tags
            'descricao' => $request->descricao,
            'cor_fundo' => $request->cor_fundo,
            'cor_fonte' => $request->cor_fonte,
            'ativo' => $request->has('ativo'),
            'created_by' => auth()->id(),
        ]);

        return redirect()->route('tags.index')
            ->with('success', 'Tag criada com sucesso!');
    }

    public function edit(Tag $tag)
    {
        // Não permitir editar tags padrão
        if ($tag->tipo === 'padrao') {
            return redirect()->route('tags.index')
                ->with('error', 'Tags padrão do sistema não podem ser editadas.');
        }

        return erp_view('tags.edit', [
            'title' => 'Editar Tag',
            'tag' => $tag,
        ]);
    }

    public function update(Request $request, Tag $tag)
    {
        // Não permitir editar tags padrão
        if ($tag->tipo === 'padrao') {
            return redirect()->route('tags.index')
                ->with('error', 'Tags padrão do sistema não podem ser editadas.');
        }

        $this->validateWithFilters($request, [
            'nome' => 'required|string|max:255',
            'descricao' => 'nullable|string',
            'cor_fundo' => 'required|string|regex:/^#[0-9A-Fa-f]{6}$/',
            'cor_fonte' => 'required|string|regex:/^#[0-9A-Fa-f]{6}$/',
        ]);

        $tag->update([
            'nome' => $request->nome,
            'descricao' => $request->descricao,
            'cor_fundo' => $request->cor_fundo,
            'cor_fonte' => $request->cor_fonte,
            'ativo' => $request->has('ativo'),
            'updated_by' => auth()->id(),
        ]);

        return redirect()->route('tags.index')
            ->with('success', 'Tag atualizada com sucesso!');
    }

    public function destroy(Tag $tag)
    {
        // Não permitir excluir tags padrão
        if ($tag->tipo === 'padrao') {
            return redirect()->back()
                ->with('error', 'Não é possível excluir tags padrão do sistema.');
        }

        $tag->delete();
        
        return redirect()->back()
            ->with('success', 'Tag excluída com sucesso!');
    }
}
