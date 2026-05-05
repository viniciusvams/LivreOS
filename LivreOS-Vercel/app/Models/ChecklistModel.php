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

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ChecklistModel extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'checklist_models';

    protected $fillable = [
        'nome',
        'descricao',
        'campos',
        'ativo',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'campos' => 'array',
        'ativo' => 'boolean',
    ];

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updatedBy()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function answers()
    {
        return $this->hasMany(ChecklistAnswer::class, 'checklist_model_id');
    }
}
