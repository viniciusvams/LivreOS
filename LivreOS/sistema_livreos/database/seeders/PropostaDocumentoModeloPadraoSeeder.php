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

use App\Models\PropostaDocumentoModelo;
use App\Services\PropostaDocumentoModeloDocxFabrica;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use PhpOffice\PhpWord\PhpWord;
use PhpOffice\PhpWord\TemplateProcessor;

/**
 * Padrão de fábrica — modelos de documento para propostas comerciais.
 *
 * Instalação limpa (tabela vazia): cria **ids 1–4** na ordem:
 * 1 — Padrão — estilo impressão OS (Word)
 * 2 — Proposta Padrão DOCX (Word, mesmo layout base gerado pela fábrica)
 * 3 — Proposta Padrão HTML (ficheiro em proposta_documento_modelos/3/template.html)
 * 4 — Proposta — estilo impressão OS (HTML), layout alinhado à impressão de OS
 *
 * Executar: php artisan db:seed --class=PropostaDocumentoModeloPadraoSeeder
 * Regenerar só o primeiro DOCX: php artisan proposta:instalar-modelo-docx-padrao --force
 */
class PropostaDocumentoModeloPadraoSeeder extends Seeder
{
    private const NOME_DOCX_OS = 'Padrão — estilo impressão OS';

    private const NOME_DOCX_PADRAO = 'Proposta Padrão DOCX';

    private const NOME_HTML = 'Proposta Padrão HTML';

    private const NOME_HTML_ESTILO_OS = 'Proposta — estilo impressão OS (HTML)';

    public function run(): void
    {
        if (PropostaDocumentoModelo::query()->exists()) {
            $this->seedIdempotentePorNome();

            return;
        }

        $docx = $this->phpWordDocxDisponivel();
        if (! class_exists(PhpWord::class)) {
            $docx = false;
            if ($this->command) {
                $this->command->warn('Pacote phpoffice/phpword não encontrado. Serão instalados apenas modelos HTML.');
            }
        } elseif (! $docx && $this->command) {
            $this->command->warn('Extensão PHP zip (ZipArchive) ausente. Modelos Word (.docx) não serão gerados; ative extension=zip no php.ini e execute: php artisan db:seed --class=PropostaDocumentoModeloPadraoSeeder');
        }

        $now = now();
        $rowsDocx = [
            [
                'id' => 1,
                'nome' => self::NOME_DOCX_OS,
                'descricao' => 'Modelo inicial inspirado no layout da impressão de OS. Edite no Word: cores, logo e textos. Tags ${...} — ver docs/PROPOSTA_DOCX_TAGS.md. Regenerar: php artisan proposta:instalar-modelo-docx-padrao --force',
                'arquivo_path' => 'proposta_documento_modelos/1/template.docx',
                'formato' => PropostaDocumentoModelo::FORMATO_DOCX,
                'ativo' => true,
                'ordem' => 0,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'id' => 2,
                'nome' => self::NOME_DOCX_PADRAO,
                'descricao' => 'Segundo modelo Word gerado pelo sistema (layout alinhado ao PDF padrão). Use como cópia editável para variantes comerciais.',
                'arquivo_path' => 'proposta_documento_modelos/2/template.docx',
                'formato' => PropostaDocumentoModelo::FORMATO_DOCX,
                'ativo' => true,
                'ordem' => 1,
                'created_at' => $now,
                'updated_at' => $now,
            ],
        ];
        $rowsHtml = [
            [
                'id' => 3,
                'nome' => self::NOME_HTML,
                'descricao' => 'Modelo HTML (documento 02 — ver docs/PROPOSTA_HTML_02.md). Usa *_html para descrição/observações com formatação; texto plano: tags sem _html. Imprimir/PDF pelo navegador. Ficheiro: storage/app/private/proposta_documento_modelos/3/template.html',
                'arquivo_path' => 'proposta_documento_modelos/3/template.html',
                'formato' => PropostaDocumentoModelo::FORMATO_HTML,
                'ativo' => true,
                'ordem' => 0,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'id' => 4,
                'nome' => self::NOME_HTML_ESTILO_OS,
                'descricao' => 'HTML com cabeçalho em 3 colunas, tabelas e totais no estilo da impressão de ordem de serviço, adaptado a proposta (referência, itens, assinaturas). Ver docs/PROPOSTA_HTML_02.md para tags.',
                'arquivo_path' => 'proposta_documento_modelos/4/template.html',
                'formato' => PropostaDocumentoModelo::FORMATO_HTML,
                'ativo' => true,
                'ordem' => 2,
                'created_at' => $now,
                'updated_at' => $now,
            ],
        ];

        $rows = $docx ? array_merge($rowsDocx, $rowsHtml) : $rowsHtml;

        DB::table('proposta_documento_modelos')->insert($rows);
        $this->syncSqliteAutoincrement('proposta_documento_modelos', 4);

        if ($docx) {
            foreach ([1, 2] as $id) {
                $rel = 'proposta_documento_modelos/'.$id.'/template.docx';
                Storage::disk('local')->makeDirectory(dirname($rel));
                $abs = Storage::disk('local')->path($rel);
                try {
                    PropostaDocumentoModeloDocxFabrica::gerarEstiloImpressaoOs($abs);
                    new TemplateProcessor($abs);
                } catch (\Throwable $e) {
                    DB::table('proposta_documento_modelos')->whereIn('id', [1, 2, 3, 4])->delete();
                    $this->syncSqliteAutoincrement('proposta_documento_modelos', 0);
                    if ($this->command) {
                        $this->command->error('Falha ao gerar modelo DOCX (id '.$id.'): '.$e->getMessage());
                    }
                    throw $e;
                }
            }
        }

        $this->instalarTemplateHtmlId3();
        $this->instalarTemplateHtmlEstiloOsId4();

        if ($this->command) {
            $this->command->info($docx
                ? 'Modelos de proposta (fábrica): ids 1–4 criados (2× Word + 2× HTML).'
                : 'Modelos de proposta: ids 3–4 criados (2× HTML). Ative extension=zip no PHP para gerar modelos Word.');
        }
    }

    /**
     * PhpWord + ZipArchive são necessários para criar ficheiros .docx.
     */
    private function phpWordDocxDisponivel(): bool
    {
        return class_exists(PhpWord::class)
            && extension_loaded('zip')
            && class_exists(\ZipArchive::class);
    }

    /**
     * Bases já populadas: garante os três nomes e ficheiros sem forçar ids.
     */
    private function seedIdempotentePorNome(): void
    {
        if ($this->phpWordDocxDisponivel()) {
            $this->ensureDocxModelo(
                self::NOME_DOCX_OS,
                0,
                'Modelo inicial inspirado no layout da impressão de OS. Edite no Word: cores, logo e textos. Regenerar: php artisan proposta:instalar-modelo-docx-padrao --force'
            );
            $this->ensureDocxModelo(
                self::NOME_DOCX_PADRAO,
                1,
                'Segundo modelo Word gerado pelo sistema (layout alinhado ao PDF padrão).'
            );
        } elseif ($this->command) {
            if (! class_exists(PhpWord::class)) {
                $this->command->warn('PhpWord ausente: modelos Word não foram verificados/criados.');
            } else {
                $this->command->warn('Extensão zip (ZipArchive) ausente: modelos Word não foram gerados. Ative extension=zip no php.ini.');
            }
        }

        $this->ensureHtmlModelo();
        $this->ensureHtmlModeloEstiloOs();
    }

    private function ensureDocxModelo(string $nome, int $ordem, string $descricao): void
    {
        if (! $this->phpWordDocxDisponivel()) {
            return;
        }

        $modelo = PropostaDocumentoModelo::query()->where('nome', $nome)->first();
        if ($modelo) {
            $rel = $modelo->arquivo_path;
            if ($rel === null || $rel === '' || $rel === '_pending') {
                $rel = 'proposta_documento_modelos/'.$modelo->id.'/template.docx';
                Storage::disk('local')->makeDirectory(dirname($rel));
                $modelo->update(['arquivo_path' => $rel]);
            }
            $abs = Storage::disk('local')->path($rel);
            if (! is_file($abs) || filesize($abs) < 64) {
                PropostaDocumentoModeloDocxFabrica::gerarEstiloImpressaoOs($abs);
                new TemplateProcessor($abs);
            }

            return;
        }

        $modelo = PropostaDocumentoModelo::create([
            'nome' => $nome,
            'descricao' => $descricao,
            'arquivo_path' => '_pending',
            'formato' => PropostaDocumentoModelo::FORMATO_DOCX,
            'ativo' => true,
            'ordem' => $ordem,
        ]);

        $rel = 'proposta_documento_modelos/'.$modelo->id.'/template.docx';
        Storage::disk('local')->makeDirectory(dirname($rel));
        $abs = Storage::disk('local')->path($rel);
        PropostaDocumentoModeloDocxFabrica::gerarEstiloImpressaoOs($abs);
        new TemplateProcessor($abs);
        $modelo->update(['arquivo_path' => $rel]);
    }

    private function ensureHtmlModelo(): void
    {
        $modelo = PropostaDocumentoModelo::query()->where('nome', self::NOME_HTML)->first();
        if ($modelo) {
            $rel = $modelo->arquivo_path;
            if ($rel === null || $rel === '' || $rel === '_pending') {
                $rel = 'proposta_documento_modelos/'.$modelo->id.'/template.html';
                $modelo->update(['arquivo_path' => $rel, 'formato' => PropostaDocumentoModelo::FORMATO_HTML]);
            }
            $abs = Storage::disk('local')->path($rel);
            if (! is_file($abs) || filesize($abs) < 64) {
                $this->copiarTemplateHtmlPara($abs);
            }

            return;
        }

        $modelo = PropostaDocumentoModelo::create([
            'nome' => self::NOME_HTML,
            'descricao' => 'Modelo HTML (documento 02). Tags *_html para formatação; ver docs/PROPOSTA_HTML_02.md.',
            'arquivo_path' => '_pending',
            'formato' => PropostaDocumentoModelo::FORMATO_HTML,
            'ativo' => true,
            'ordem' => 0,
        ]);

        $rel = 'proposta_documento_modelos/'.$modelo->id.'/template.html';
        Storage::disk('local')->makeDirectory(dirname($rel));
        $abs = Storage::disk('local')->path($rel);
        $this->copiarTemplateHtmlPara($abs);
        $modelo->update(['arquivo_path' => $rel]);
    }

    private function instalarTemplateHtmlId3(): void
    {
        $rel = 'proposta_documento_modelos/3/template.html';
        Storage::disk('local')->makeDirectory(dirname($rel));
        $abs = Storage::disk('local')->path($rel);
        $this->copiarTemplateHtmlPara($abs, '3');
    }

    private function instalarTemplateHtmlEstiloOsId4(): void
    {
        $rel = 'proposta_documento_modelos/4/template.html';
        Storage::disk('local')->makeDirectory(dirname($rel));
        $abs = Storage::disk('local')->path($rel);
        $this->copiarTemplateHtmlPara($abs, '4');
    }

    private function copiarTemplateHtmlPara(string $destinoAbsoluto, string $installSubdir = '3'): void
    {
        $dir = dirname($destinoAbsoluto);
        if (! is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        $origem = base_path('database/install/proposta_documento_modelos/'.$installSubdir.'/template.html');
        if (! is_file($origem)) {
            if ($this->command) {
                $this->command->warn('Template HTML de instalação não encontrado em database/install/proposta_documento_modelos/'.$installSubdir.'/template.html');
            }

            File::put($destinoAbsoluto, "<!DOCTYPE html><html><head><meta charset=\"UTF-8\"><title>Proposta</title></head><body><p>Substitua por template completo (docs/PROPOSTA_HTML_02.md).</p></body></html>\n");

            return;
        }

        File::copy($origem, $destinoAbsoluto);
    }

    private function ensureHtmlModeloEstiloOs(): void
    {
        $modelo = PropostaDocumentoModelo::query()->where('nome', self::NOME_HTML_ESTILO_OS)->first();
        if ($modelo) {
            $rel = $modelo->arquivo_path;
            if ($rel === null || $rel === '' || $rel === '_pending') {
                $rel = 'proposta_documento_modelos/'.$modelo->id.'/template.html';
                $modelo->update(['arquivo_path' => $rel, 'formato' => PropostaDocumentoModelo::FORMATO_HTML]);
            }
            $abs = Storage::disk('local')->path($rel);
            if (! is_file($abs) || filesize($abs) < 64) {
                $this->copiarTemplateHtmlPara($abs, '4');
            }

            return;
        }

        $modelo = PropostaDocumentoModelo::create([
            'nome' => self::NOME_HTML_ESTILO_OS,
            'descricao' => 'HTML estilo impressão OS adaptado à proposta. Tags: docs/PROPOSTA_HTML_02.md.',
            'arquivo_path' => '_pending',
            'formato' => PropostaDocumentoModelo::FORMATO_HTML,
            'ativo' => true,
            'ordem' => 2,
        ]);

        $rel = 'proposta_documento_modelos/'.$modelo->id.'/template.html';
        Storage::disk('local')->makeDirectory(dirname($rel));
        $abs = Storage::disk('local')->path($rel);
        $this->copiarTemplateHtmlPara($abs, '4');
        $modelo->update(['arquivo_path' => $rel]);
    }

    private function syncSqliteAutoincrement(string $table, int $lastId): void
    {
        if (DB::connection()->getDriverName() !== 'sqlite') {
            return;
        }

        if ($lastId < 1) {
            DB::table('sqlite_sequence')->where('name', $table)->delete();

            return;
        }

        DB::table('sqlite_sequence')->updateOrInsert(
            ['name' => $table],
            ['seq' => $lastId]
        );
    }
}
