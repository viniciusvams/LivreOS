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

namespace App\Console\Commands;

use App\Models\PropostaDocumentoModelo;
use App\Services\PropostaDocumentoModeloDocxFabrica;
use Database\Seeders\PropostaDocumentoModeloPadraoSeeder;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;
use PhpOffice\PhpWord\TemplateProcessor;

class InstalarModeloDocxPropostaCommand extends Command
{
    protected $signature = 'proposta:instalar-modelo-docx-padrao {--force : Regenerar o .docx do modelo «Padrão — estilo impressão OS» se já existir}';

    protected $description = 'Cria o modelo Word (.docx) padrão para propostas (layout alinhado ao PDF padrão do ERP), se ainda não existir';

    public function handle(): int
    {
        $nome = 'Padrão — estilo impressão OS';

        if ($this->option('force')) {
            $modelo = PropostaDocumentoModelo::where('nome', $nome)->where('formato', PropostaDocumentoModelo::FORMATO_DOCX)->first();
            if (! $modelo) {
                $this->warn('Modelo «'.$nome.'» não encontrado. Execute sem --force para criar.');

                return self::FAILURE;
            }
            $rel = $modelo->arquivo_path;
            if ($rel === null || $rel === '' || $rel === '_pending') {
                $rel = 'proposta_documento_modelos/'.$modelo->id.'/template.docx';
                Storage::disk('local')->makeDirectory(dirname($rel));
                $modelo->update(['arquivo_path' => $rel]);
            }
            $abs = Storage::disk('local')->path($rel);
            try {
                PropostaDocumentoModeloDocxFabrica::gerarEstiloImpressaoOs($abs);
                new TemplateProcessor($abs);
            } catch (\Throwable $e) {
                $this->error('Falha ao regenerar DOCX: '.$e->getMessage());

                return self::FAILURE;
            }
            $this->info('Ficheiro .docx regenerado para o modelo id '.$modelo->id.' (layout PDF padrão).');

            return self::SUCCESS;
        }

        $this->call('db:seed', ['--class' => PropostaDocumentoModeloPadraoSeeder::class]);

        return self::SUCCESS;
    }
}
