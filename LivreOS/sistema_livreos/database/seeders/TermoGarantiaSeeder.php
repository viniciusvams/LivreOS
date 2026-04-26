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

use App\Models\TermoGarantia;
use Illuminate\Database\Seeder;

class TermoGarantiaSeeder extends Seeder
{
    /**
     * Cria o termo de garantia padrão do sistema.
     */
    public function run(): void
    {
        $termo = <<<'HTML'
<p><strong>TERMO DE GARANTIA - SERVIÇOS DE ASSISTÊNCIA TÉCNICA</strong></p>

<p>O presente termo estabelece as condições de garantia aplicáveis aos serviços de manutenção, reparo e assistência técnica prestados.</p>

<p><strong>1. OBJETO DA GARANTIA</strong></p>
<p>A garantia cobre defeitos de mão de obra e falhas nos serviços executados no prazo estabelecido na Ordem de Serviço. A garantia inicia-se na data de conclusão/entrega do equipamento ao cliente.</p>

<p><strong>2. PRAZO DE GARANTIA</strong></p>
<p>O prazo de garantia será informado no ato do encerramento da Ordem de Serviço e poderá ser de 30, 60, 90 dias ou conforme acordado.</p>

<p><strong>3. O QUE ESTÁ COBERTO</strong></p>
<ul>
<li>Correção de defeitos decorrentes da mão de obra e dos serviços realizados</li>
<li>Substituição de peças instaladas que apresentarem defeito de fabricação</li>
<li>Retrabalho necessário para correção de falhas nos serviços prestados</li>
</ul>

<p><strong>4. O QUE NÃO ESTÁ COBERTO</strong></p>
<ul>
<li>Danos causados por mau uso, quedas, impactos ou manuseio incorreto</li>
<li>Desgaste natural de peças e componentes</li>
<li>Problemas decorrentes de instalação ou manuseio por terceiros após a entrega</li>
<li>Defeitos em peças ou componentes não substituídos durante o serviço</li>
<li>Danos provocados por acidentes, fenômenos da natureza ou força maior</li>
</ul>

<p><strong>5. CONDIÇÕES PARA VALIDADE DA GARANTIA</strong></p>
<p>Para usufruir da garantia, o cliente deverá apresentar a Ordem de Serviço ou comprovante de entrega original. A garantia é válida apenas para o equipamento e o serviço específico relacionados na OS.</p>

<p><strong>6. PROCEDIMENTO EM CASO DE DEFEITO</strong></p>
<p>Em caso de defeito coberto pela garantia, o cliente deve contactar a empresa no prazo vigente, apresentando o documento da OS e descrevendo o problema. A empresa se compromete a analisar e, se aplicável, corrigir o defeito sem custo adicional para o cliente.</p>

<p><strong>7. DISPOSIÇÕES GERAIS</strong></p>
<p>Este termo constitui o acordo integral entre as partes em relação à garantia dos serviços. A empresa reserva-se o direito de esclarecer quaisquer dúvidas sobre a interpretação destas condições.</p>
HTML;

        $existe = TermoGarantia::where('nome', 'Termo de Garantia Padrão')->first();

        if (!$existe) {
            TermoGarantia::create([
                'nome' => 'Termo de Garantia Padrão',
                'termo' => $termo,
                'ativo' => true,
            ]);
        }
    }
}
