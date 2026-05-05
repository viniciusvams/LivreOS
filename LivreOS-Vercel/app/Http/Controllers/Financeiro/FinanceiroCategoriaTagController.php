<?php

/**
 * Componente da aplicação LivreOS
 *
 * @author    viniciusvams
 * @copyright 2024-2026 LivreOS
 * @license   https://www.gnu.org/licenses/agpl-3.0.txt AGPL-3.0
 */

namespace App\Http\Controllers\Financeiro;

use App\Http\Controllers\Controller;
use App\Models\CategoriaFinanceira;
use App\Models\Tag;
use Illuminate\Http\Request;

class FinanceiroCategoriaTagController extends Controller
{
    public function categoriasOpcoes(Request $request)
    {
        $escopo = $this->validarEscopoCategoria($request);
        $incluir = $request->filled('incluir_id') ? (int) $request->incluir_id : null;
        $opcoes = CategoriaFinanceira::opcoesParaSelect($escopo, $incluir);

        return response()->json(['opcoes' => $opcoes]);
    }

    public function categoriasIndex(Request $request)
    {
        $escopo = $this->validarEscopoCategoria($request);
        $lista = CategoriaFinanceira::query()
            ->where('escopo', $escopo)
            ->with('parent:id,nome')
            ->orderBy('ordem')
            ->orderBy('nome')
            ->get(['id', 'parent_id', 'nome', 'ativo', 'ordem', 'cor_fundo', 'cor_fonte', 'descricao']);

        return response()->json(['data' => $lista]);
    }

    public function categoriasStore(Request $request)
    {
        $validated = $this->validateWithFilters($request, [
            'escopo' => 'required|in:receber,pagar',
            'nome' => 'required|string|max:160',
            'parent_id' => 'nullable|exists:categorias_financeiras,id',
            'ordem' => 'nullable|integer|min:0|max:65535',
            'cor_fundo' => ['nullable', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'cor_fonte' => ['nullable', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'descricao' => 'nullable|string|max:2000',
        ]);

        if (!empty($validated['parent_id'])) {
            $pai = CategoriaFinanceira::query()->find($validated['parent_id']);
            if (!$pai || $pai->escopo !== $validated['escopo']) {
                return response()->json(['message' => 'Categoria pai inválida para este escopo.'], 422);
            }
        }

        $cat = CategoriaFinanceira::create([
            'escopo' => $validated['escopo'],
            'nome' => trim($validated['nome']),
            'parent_id' => $validated['parent_id'] ?? null,
            'ordem' => $validated['ordem'] ?? 0,
            'ativo' => true,
            'cor_fundo' => $this->normalizarCorHex($validated['cor_fundo'] ?? null, '#64748b'),
            'cor_fonte' => $this->normalizarCorHex($validated['cor_fonte'] ?? null, '#ffffff'),
            'descricao' => (isset($validated['descricao']) && trim((string) $validated['descricao']) !== '')
                ? trim((string) $validated['descricao'])
                : null,
        ]);

        return response()->json([
            'categoria' => $cat->only(['id', 'escopo', 'parent_id', 'nome', 'ordem', 'ativo', 'cor_fundo', 'cor_fonte', 'descricao']),
            'opcoes' => CategoriaFinanceira::opcoesParaSelect($validated['escopo']),
        ], 201);
    }

    public function categoriasUpdate(Request $request, CategoriaFinanceira $categoriaFinanceira)
    {
        $validated = $this->validateWithFilters($request, [
            'nome' => 'required|string|max:160',
            'parent_id' => 'nullable|exists:categorias_financeiras,id',
            'ativo' => 'sometimes|boolean',
            'ordem' => 'nullable|integer|min:0|max:65535',
            'cor_fundo' => ['nullable', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'cor_fonte' => ['nullable', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'descricao' => 'nullable|string|max:2000',
        ]);

        $parentId = array_key_exists('parent_id', $validated) ? $validated['parent_id'] : $categoriaFinanceira->parent_id;
        if ($parentId !== null && (int) $parentId === (int) $categoriaFinanceira->id) {
            return response()->json(['message' => 'A categoria não pode ser pai dela mesma.'], 422);
        }
        if ($parentId) {
            $pai = CategoriaFinanceira::query()->find($parentId);
            if (!$pai || $pai->escopo !== $categoriaFinanceira->escopo) {
                return response()->json(['message' => 'Categoria pai inválida.'], 422);
            }
            if ($categoriaFinanceira->criariaCicloComoPai((int) $parentId)) {
                return response()->json(['message' => 'Não é possível definir um subnível como categoria pai.'], 422);
            }
        }

        $fill = [
            'nome' => trim($validated['nome']),
            'parent_id' => $parentId,
            'ordem' => $validated['ordem'] ?? $categoriaFinanceira->ordem,
            'ativo' => array_key_exists('ativo', $validated) ? (bool) $validated['ativo'] : $categoriaFinanceira->ativo,
        ];
        if (array_key_exists('cor_fundo', $validated)) {
            $fill['cor_fundo'] = $this->normalizarCorHex($validated['cor_fundo'], $categoriaFinanceira->cor_fundo ?? '#64748b');
        }
        if (array_key_exists('cor_fonte', $validated)) {
            $fill['cor_fonte'] = $this->normalizarCorHex($validated['cor_fonte'], $categoriaFinanceira->cor_fonte ?? '#ffffff');
        }
        if (array_key_exists('descricao', $validated)) {
            $d = $validated['descricao'];
            $fill['descricao'] = ($d !== null && trim((string) $d) !== '') ? trim((string) $d) : null;
        }
        $categoriaFinanceira->fill($fill);
        $categoriaFinanceira->save();

        return response()->json([
            'categoria' => $categoriaFinanceira->only(['id', 'escopo', 'parent_id', 'nome', 'ordem', 'ativo', 'cor_fundo', 'cor_fonte', 'descricao']),
            'opcoes' => CategoriaFinanceira::opcoesParaSelect($categoriaFinanceira->escopo),
        ]);
    }

    public function categoriasDestroy(CategoriaFinanceira $categoriaFinanceira)
    {
        if ($categoriaFinanceira->children()->exists()) {
            return response()->json(['message' => 'Remova ou mova as subcategorias antes de excluir.'], 422);
        }
        $escopo = $categoriaFinanceira->escopo;
        $categoriaFinanceira->delete();

        return response()->json([
            'ok' => true,
            'opcoes' => CategoriaFinanceira::opcoesParaSelect($escopo),
        ]);
    }

    public function tagsAutocomplete(Request $request)
    {
        $request->validate([
            'escopo' => 'required|in:conta_receber,conta_pagar',
            'q' => 'nullable|string|max:100',
        ]);
        $q = trim((string) $request->get('q', ''));
        $escopo = $request->escopo;

        $query = Tag::query()->paraTituloFinanceiro($escopo)->orderBy('nome')->limit(25);
        if ($q !== '') {
            $query->where('nome', 'like', '%' . str_replace(['%', '_'], ['\\%', '\\_'], $q) . '%');
        }
        $tags = $query->get(['id', 'nome', 'cor_fundo', 'cor_fonte']);

        return response()->json(['data' => $tags]);
    }

    public function tagsStoreRapido(Request $request)
    {
        $validated = $this->validateWithFilters($request, [
            'nome' => 'required|string|max:255',
            'escopo' => 'required|in:conta_receber,conta_pagar',
            'cor_fundo' => ['nullable', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'cor_fonte' => ['nullable', 'regex:/^#[0-9A-Fa-f]{6}$/'],
        ]);
        $nome = trim($validated['nome']);
        if ($nome === '') {
            return response()->json(['message' => 'Nome inválido.'], 422);
        }

        $existente = Tag::query()
            ->where('nome', $nome)
            ->where(function ($w) use ($validated) {
                $w->whereNull('escopo_financeiro')->orWhere('escopo_financeiro', $validated['escopo']);
            })
            ->first();
        if ($existente) {
            return response()->json([
                'tag' => $existente->only(['id', 'nome', 'cor_fundo', 'cor_fonte']),
                'created' => false,
            ]);
        }

        $tag = Tag::create([
            'nome' => $nome,
            'tipo' => 'personalizado',
            'escopo_financeiro' => $validated['escopo'],
            'ativo' => true,
            'cor_fundo' => $this->normalizarCorHex($validated['cor_fundo'] ?? null, '#6366f1'),
            'cor_fonte' => $this->normalizarCorHex($validated['cor_fonte'] ?? null, '#ffffff'),
            'created_by' => auth()->id(),
        ]);

        return response()->json([
            'tag' => $tag->only(['id', 'nome', 'cor_fundo', 'cor_fonte']),
            'created' => true,
        ], 201);
    }

    private function validarEscopoCategoria(Request $request): string
    {
        $request->validate(['escopo' => 'required|in:receber,pagar']);

        return $request->escopo;
    }

    private function normalizarCorHex(?string $hex, string $padrao): string
    {
        if ($hex === null || $hex === '') {
            return strtolower($padrao);
        }
        if (!preg_match('/^#[0-9A-Fa-f]{6}$/', $hex)) {
            return strtolower($padrao);
        }

        return strtolower($hex);
    }
}
