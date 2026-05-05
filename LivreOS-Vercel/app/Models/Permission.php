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

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Permission extends Model
{
    protected $fillable = [
        'name',
        'slug',
        'description',
    ];

    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(Role::class);
    }

    /**
     * Garante que as permissões de estoque zero e produto/serviço avulso existam no banco.
     * Chamado ao carregar Roles e Permissões para que apareçam mesmo se migration/seeder não rodou.
     */
    public static function ensureEstoqueAvulsoPermissions(): void
    {
        $definitions = [
            [
                'slug' => 'produtos.vender_estoque_zero',
                'name' => 'Vender com estoque zero',
                'description' => 'Permite incluir produtos com estoque zero ou insuficiente na OS mesmo quando a regra de bloqueio está ativa',
            ],
            [
                'slug' => 'ordem_servico.produto_servico_avulso',
                'name' => 'Cadastrar produto/serviço avulso na OS',
                'description' => 'Permite adicionar produto avulso e serviço avulso (itens sem vínculo com cadastro) na ordem de serviço',
            ],
        ];

        foreach ($definitions as $attrs) {
            self::firstOrCreate(
                ['slug' => $attrs['slug']],
                $attrs
            );
        }
    }
}
