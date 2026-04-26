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

use App\Http\Controllers\Concerns\HasEntityHooks;
use App\Models\PropostaDocumentoModelo;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use PhpOffice\PhpWord\TemplateProcessor;

class PropostaDocumentoModeloController extends Controller
{
    use HasEntityHooks;

    public function index()
    {
        $this->authorizePermission('manage-proposta-documento-modelos');

        $query = PropostaDocumentoModelo::query()->orderBy('ordem')->orderBy('nome');
        $query = $this->applyEntityQuery($query, 'proposta_documento_modelo');
        $modelos = $query->paginate(20);

        return erp_view('propostas-comerciais.modelos-documento.index', [
            'title' => 'Modelos de documento — propostas',
            'modelos' => $modelos,
        ]);
    }

    public function create()
    {
        $this->authorizePermission('manage-proposta-documento-modelos');

        return erp_view('propostas-comerciais.modelos-documento.create', [
            'title' => 'Novo modelo (Word ou HTML)',
        ]);
    }

    public function store(Request $request)
    {
        $this->authorizePermission('manage-proposta-documento-modelos');

        $validated = $this->validateWithFilters($request, [
            'nome' => 'required|string|max:160',
            'descricao' => 'nullable|string|max:2000',
            'ordem' => 'nullable|integer|min:0|max:65535',
            'ativo' => 'nullable|boolean',
            'arquivo' => 'required|file|max:15360',
        ]);

        $file = $request->file('arquivo');
        $ext = strtolower((string) $file->getClientOriginalExtension());
        $formato = $this->formatoPorExtensao($ext);
        if ($formato === null) {
            return redirect()->back()->withInput()->with('error', 'Envie um arquivo .docx (Word) ou .html / .htm.');
        }

        $modelo = PropostaDocumentoModelo::create([
            'nome' => $validated['nome'],
            'descricao' => $validated['descricao'] ?? null,
            'arquivo_path' => 'temp',
            'formato' => $formato,
            'ativo' => $request->boolean('ativo', true),
            'ordem' => (int) ($validated['ordem'] ?? 0),
        ]);

        $dir = 'proposta_documento_modelos/'.$modelo->id;

        if ($formato === PropostaDocumentoModelo::FORMATO_DOCX) {
            $path = $file->storeAs($dir, 'template.docx', 'local');
            try {
                new TemplateProcessor(Storage::disk('local')->path($path));
            } catch (\Throwable $e) {
                Storage::disk('local')->delete($path);
                $modelo->delete();
                Log::warning('Upload DOCX inválido para modelo de proposta', ['e' => $e->getMessage()]);

                return redirect()->back()->withInput()->with('error', 'O arquivo não pôde ser lido como modelo Word (.docx). Verifique se é um documento válido.');
            }
            $modelo->update(['arquivo_path' => $path]);
        } else {
            $raw = file_get_contents($file->getRealPath());
            if ($raw === false || trim($raw) === '') {
                $modelo->delete();

                return redirect()->back()->withInput()->with('error', 'O arquivo HTML está vazio ou ilegível.');
            }
            $sanitizado = $this->sanitizarConteudoHtmlModelo($raw);
            if (trim($sanitizado) === '') {
                $modelo->delete();

                return redirect()->back()->withInput()->with('error', 'Após validação de segurança o HTML ficou vazio. Remova scripts e tente de novo.');
            }
            Storage::disk('local')->makeDirectory($dir);
            $path = $dir.'/template.html';
            Storage::disk('local')->put($path, $sanitizado);
            $modelo->update(['arquivo_path' => $path]);
        }

        return redirect()->route('propostas-comerciais.modelos-documento.index')
            ->with('success', 'Modelo cadastrado.');
    }

    public function edit(PropostaDocumentoModelo $propostaDocumentoModelo)
    {
        $this->authorizePermission('manage-proposta-documento-modelos');

        return erp_view('propostas-comerciais.modelos-documento.edit', [
            'title' => 'Editar modelo',
            'modelo' => $propostaDocumentoModelo,
        ]);
    }

    public function update(Request $request, PropostaDocumentoModelo $propostaDocumentoModelo)
    {
        $this->authorizePermission('manage-proposta-documento-modelos');

        $validated = $this->validateWithFilters($request, [
            'nome' => 'required|string|max:160',
            'descricao' => 'nullable|string|max:2000',
            'ordem' => 'nullable|integer|min:0|max:65535',
            'ativo' => 'nullable|boolean',
            'arquivo' => 'nullable|file|max:15360',
        ]);

        $propostaDocumentoModelo->nome = $validated['nome'];
        $propostaDocumentoModelo->descricao = $validated['descricao'] ?? null;
        $propostaDocumentoModelo->ordem = (int) ($validated['ordem'] ?? 0);
        $propostaDocumentoModelo->ativo = $request->boolean('ativo', true);

        if ($request->hasFile('arquivo')) {
            $file = $request->file('arquivo');
            $ext = strtolower((string) $file->getClientOriginalExtension());
            $formato = $this->formatoPorExtensao($ext);
            if ($formato === null) {
                return redirect()->back()->withInput()->with('error', 'Envie .docx ou .html / .htm.');
            }

            $old = $propostaDocumentoModelo->arquivo_path;
            $dir = 'proposta_documento_modelos/'.$propostaDocumentoModelo->id;

            if ($formato === PropostaDocumentoModelo::FORMATO_DOCX) {
                $path = $file->storeAs($dir, 'template.docx', 'local');
                try {
                    new TemplateProcessor(Storage::disk('local')->path($path));
                } catch (\Throwable $e) {
                    Storage::disk('local')->delete($path);
                    Log::warning('Upload DOCX inválido (update modelo proposta)', ['e' => $e->getMessage()]);

                    return redirect()->back()->withInput()->with('error', 'O arquivo não pôde ser lido como modelo Word (.docx).');
                }
            } else {
                $raw = file_get_contents($file->getRealPath());
                if ($raw === false || trim($raw) === '') {
                    return redirect()->back()->withInput()->with('error', 'O arquivo HTML está vazio ou ilegível.');
                }
                $sanitizado = $this->sanitizarConteudoHtmlModelo($raw);
                if (trim($sanitizado) === '') {
                    return redirect()->back()->withInput()->with('error', 'Após validação de segurança o HTML ficou vazio.');
                }
                Storage::disk('local')->makeDirectory($dir);
                $path = $dir.'/template.html';
                Storage::disk('local')->put($path, $sanitizado);
            }

            if ($old && $old !== 'temp' && Storage::disk('local')->exists($old)) {
                Storage::disk('local')->delete($old);
            }
            $propostaDocumentoModelo->arquivo_path = $path;
            $propostaDocumentoModelo->formato = $formato;
        }

        $propostaDocumentoModelo->save();

        return redirect()->route('propostas-comerciais.modelos-documento.index')
            ->with('success', 'Modelo atualizado.');
    }

    public function destroy(PropostaDocumentoModelo $propostaDocumentoModelo)
    {
        $this->authorizePermission('manage-proposta-documento-modelos');

        if ($propostaDocumentoModelo->arquivo_path && Storage::disk('local')->exists($propostaDocumentoModelo->arquivo_path)) {
            Storage::disk('local')->delete($propostaDocumentoModelo->arquivo_path);
        }
        $propostaDocumentoModelo->delete();

        return redirect()->route('propostas-comerciais.modelos-documento.index')
            ->with('success', 'Modelo removido.');
    }

    private function formatoPorExtensao(string $ext): ?string
    {
        return match ($ext) {
            'docx' => PropostaDocumentoModelo::FORMATO_DOCX,
            'html', 'htm' => PropostaDocumentoModelo::FORMATO_HTML,
            default => null,
        };
    }

    /**
     * Reduz risco de XSS ao abrir o modelo preenchido no browser (conteúdo vem de admins).
     */
    private function sanitizarConteudoHtmlModelo(string $conteudo): string
    {
        $conteudo = preg_replace('#<script\b[^>]*>.*?</script>#is', '', $conteudo) ?? $conteudo;
        $conteudo = preg_replace('#<iframe\b[^>]*>.*?</iframe>#is', '', $conteudo) ?? $conteudo;
        $conteudo = preg_replace('#\s+on[a-z]+\s*=\s*("[^"]*"|\'[^\']*\'|[^\s>]+)#i', '', $conteudo) ?? $conteudo;

        return $conteudo;
    }
}
