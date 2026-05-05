<?php

/**
 * Componente da aplicação LivreOS
 *
 * @author    viniciusvams
 * @copyright 2024-2026 LivreOS
 * @license   https://www.gnu.org/licenses/agpl-3.0.txt AGPL-3.0
 */

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class CategoriaFinanceira extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'categorias_financeiras';

    protected $fillable = [
        'escopo',
        'parent_id',
        'nome',
        'cor_fundo',
        'cor_fonte',
        'descricao',
        'ordem',
        'ativo',
    ];

    protected $casts = [
        'ativo' => 'boolean',
        'ordem' => 'integer',
    ];

    public function parent()
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    public function children()
    {
        return $this->hasMany(self::class, 'parent_id')->orderBy('ordem')->orderBy('nome');
    }

    /**
     * Opções para select: id, label (com indentação por nível), parent_id.
     *
     * @return list<array{id:int,label:string,parent_id:int|null,cor_fundo:string,cor_fonte:string}>
     */
    public static function opcoesParaSelect(string $escopo, ?int $incluirIdMesmoInativo = null): array
    {
        $q = static::query()->where('escopo', $escopo);
        if ($incluirIdMesmoInativo) {
            $q->where(function ($w) use ($incluirIdMesmoInativo) {
                $w->where('ativo', true)->orWhere('id', $incluirIdMesmoInativo);
            });
        } else {
            $q->where('ativo', true);
        }
        $todas = $q->orderBy('ordem')->orderBy('nome')->get(['id', 'parent_id', 'nome', 'cor_fundo', 'cor_fonte']);

        $porPai = $todas->groupBy(fn ($c) => $c->parent_id ?? 0);
        $out = [];

        $walk = function ($parentKey, $prefix) use (&$walk, &$out, $porPai) {
            foreach ($porPai->get($parentKey, collect()) as $cat) {
                $out[] = [
                    'id' => $cat->id,
                    'label' => $prefix . $cat->nome,
                    'parent_id' => $cat->parent_id,
                    'cor_fundo' => $cat->cor_fundo ?? '#64748b',
                    'cor_fonte' => $cat->cor_fonte ?? '#ffffff',
                ];
                $walk($cat->id, $prefix . '— ');
            }
        };

        $walk(0, '');

        return $out;
    }

    /** true se definir parent_id = $novoParentId criaria ciclo (pai dentro da própria subárvore). */
    public function criariaCicloComoPai(?int $novoParentId): bool
    {
        if ($novoParentId === null) {
            return false;
        }
        if ((int) $novoParentId === (int) $this->id) {
            return true;
        }
        $current = static::query()->find($novoParentId);
        $guard = 0;
        while ($current && $guard < 64) {
            if ((int) $current->id === (int) $this->id) {
                return true;
            }
            $current = $current->parent_id ? static::query()->find($current->parent_id) : null;
            $guard++;
        }

        return false;
    }
}
