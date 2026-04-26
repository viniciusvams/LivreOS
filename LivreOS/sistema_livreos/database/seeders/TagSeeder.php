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

namespace Database\Seeders;

use App\Models\Tag;
use Illuminate\Database\Seeder;

class TagSeeder extends Seeder
{
    /**
     * Tags padrão do sistema. Aplicadas automaticamente no encerramento:
     * - Pago: quando a OS foi paga ou é garantia/contrato
     * - Pagamento Pendente: quando a OS foi faturada mas ainda não foi paga
     */
    public function run(): void
    {
        $tags = [
            // Tags de pagamento (aplicadas automaticamente no encerramento)
            [
                'nome' => 'Pago',
                'tipo' => 'padrao',
                'descricao' => 'OS encerrada e paga (ou em garantia/contrato).',
                'cor_fundo' => '#10b981',
                'cor_fonte' => '#ffffff',
                'ativo' => true,
            ],
            [
                'nome' => 'Pagamento Pendente',
                'tipo' => 'padrao',
                'descricao' => 'OS encerrada mas pagamento ainda pendente.',
                'cor_fundo' => '#f59e0b',
                'cor_fonte' => '#ffffff',
                'ativo' => true,
            ],
            // Sugestões de tags personalizadas (já cadastradas, aplicadas manualmente pelo usuário)
            [
                'nome' => 'Urgente',
                'tipo' => 'personalizado',
                'descricao' => 'OS que requer atenção prioritária.',
                'cor_fundo' => '#ef4444',
                'cor_fonte' => '#ffffff',
                'ativo' => true,
            ],
            [
                'nome' => 'Garantia',
                'tipo' => 'personalizado',
                'descricao' => 'Serviço coberto por garantia.',
                'cor_fundo' => '#3b82f6',
                'cor_fonte' => '#ffffff',
                'ativo' => true,
            ],
            [
                'nome' => 'Cliente VIP',
                'tipo' => 'personalizado',
                'descricao' => 'Cliente com tratamento prioritário.',
                'cor_fundo' => '#8b5cf6',
                'cor_fonte' => '#ffffff',
                'ativo' => true,
            ],
            [
                'nome' => 'Orçamento enviado',
                'tipo' => 'personalizado',
                'descricao' => 'Orçamento já enviado ao cliente.',
                'cor_fundo' => '#06b6d4',
                'cor_fonte' => '#ffffff',
                'ativo' => true,
            ],
            [
                'nome' => 'Aguardando peças',
                'tipo' => 'personalizado',
                'descricao' => 'OS aguardando chegada de peças.',
                'cor_fundo' => '#6366f1',
                'cor_fonte' => '#ffffff',
                'ativo' => true,
            ],
        ];

        foreach ($tags as $tag) {
            $model = Tag::where('nome', $tag['nome'])->first();
            if ($model) {
                $model->update($tag);
            } else {
                Tag::create($tag);
            }
        }
    }
}
