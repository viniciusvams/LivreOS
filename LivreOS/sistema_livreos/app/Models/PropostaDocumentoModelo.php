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

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class PropostaDocumentoModelo extends Model
{
    protected $table = 'proposta_documento_modelos';

    public const FORMATO_DOCX = 'docx';

    public const FORMATO_HTML = 'html';

    protected $fillable = [
        'nome',
        'descricao',
        'arquivo_path',
        'formato',
        'ativo',
        'ordem',
    ];

    protected $casts = [
        'ativo' => 'boolean',
        'ordem' => 'integer',
    ];

    public function scopeAtivos(Builder $q): Builder
    {
        return $q->where('ativo', true);
    }

    public function scopeOrdenados(Builder $q): Builder
    {
        return $q->orderBy('ordem')->orderBy('nome');
    }

    public function caminhoAbsolutoArquivo(): ?string
    {
        if ($this->arquivo_path === null || $this->arquivo_path === '') {
            return null;
        }
        if (! Storage::disk('local')->exists($this->arquivo_path)) {
            return null;
        }

        return Storage::disk('local')->path($this->arquivo_path);
    }

    public function isDocx(): bool
    {
        return ($this->formato ?? self::FORMATO_DOCX) === self::FORMATO_DOCX;
    }

    public function isHtml(): bool
    {
        return ($this->formato ?? '') === self::FORMATO_HTML;
    }

    public function labelFormato(): string
    {
        return $this->isHtml() ? 'HTML' : 'Word';
    }
}
