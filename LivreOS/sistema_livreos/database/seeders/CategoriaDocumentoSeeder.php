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

use App\Models\CategoriaDocumento;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class CategoriaDocumentoSeeder extends Seeder
{
    public function run(): void
    {
        $categorias = [
            ['nome' => 'Documento de Identidade', 'descricao' => 'RG, CNH, Passaporte'],
            ['nome' => 'CPF/CNPJ', 'descricao' => 'Documentos de identificação fiscal'],
            ['nome' => 'Inscrição Estadual', 'descricao' => 'IE e documentos relacionados'],
            ['nome' => 'Inscrição Municipal', 'descricao' => 'IM e documentos relacionados'],
            ['nome' => 'Contrato', 'descricao' => 'Contratos e acordos'],
            ['nome' => 'Procuração', 'descricao' => 'Procurações e documentos de representação'],
            ['nome' => 'Comprovante de Endereço', 'descricao' => 'Comprovantes de residência'],
            ['nome' => 'Certidões', 'descricao' => 'Certidões diversas'],
            ['nome' => 'Alvará', 'descricao' => 'Alvarás e licenças'],
            ['nome' => 'Outros', 'descricao' => 'Outros documentos'],
        ];

        foreach ($categorias as $categoria) {
            CategoriaDocumento::updateOrCreate(
                ['slug' => Str::slug($categoria['nome'])],
                [
                    'nome' => $categoria['nome'],
                    'descricao' => $categoria['descricao'],
                    'ativo' => true,
                ]
            );
        }
    }
}
