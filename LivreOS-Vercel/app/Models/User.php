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

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable, SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'ativo',
        'cpf',
        'rg',
        'rg_orgao_emissor',
        'telefone',
        'cargo',
        'departamento',
        'custo_hora_tecnica',
        'cep',
        'logradouro',
        'numero',
        'complemento',
        'bairro',
        'cidade',
        'uf',
    ];

    public function getFillable(): array
    {
        $default = [
            'name', 'email', 'password', 'ativo',
            'cpf', 'rg', 'rg_orgao_emissor', 'telefone',
            'cargo', 'departamento', 'custo_hora_tecnica',
            'cep', 'logradouro', 'numero', 'complemento', 'bairro', 'cidade', 'uf',
        ];
        return function_exists('apply_filters') ? apply_filters('user.fillable', $default) : $default;
    }

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
        'api_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_admin' => 'boolean',
            'ativo' => 'boolean',
            'custo_hora_tecnica' => 'decimal:2',
            'deleted_at' => 'datetime',
        ];
    }

    public function roles()
    {
        return $this->belongsToMany(Role::class);
    }

    public function hasRole(string $role): bool
    {
        return $this->roles()->where('slug', $role)->exists();
    }

    public function hasPermission(string $permission): bool
    {
        if ($this->is_admin) {
            return true;
        }

        return $this->roles()->whereHas('permissions', function ($query) use ($permission) {
            $query->where('slug', $permission);
        })->exists();
    }

    public function hasAnyRole(array $roles): bool
    {
        return $this->roles()->whereIn('slug', $roles)->exists();
    }

    /**
     * Verifica se o usuário pode acessar a área operacional (dashboard, OS, clientes, etc.).
     * Admin (is_admin) ou qualquer um dos roles configurados em config('access.operational_roles').
     */
    public function canAccessOperational(): bool
    {
        if ($this->is_admin) {
            return true;
        }

        return $this->hasAnyRole(config('access.operational_roles', []));
    }

    public function assignRole(string $role): void
    {
        $roleModel = Role::where('slug', $role)->first();
        if ($roleModel && !$this->hasRole($role)) {
            $this->roles()->attach($roleModel);
        }
    }

    public function removeRole(string $role): void
    {
        $roleModel = Role::where('slug', $role)->first();
        if ($roleModel) {
            $this->roles()->detach($roleModel);
        }
    }

    public function assinatura()
    {
        return $this->hasOne(UserSignature::class);
    }

    /**
     * Cliente vinculado quando o usuário é do portal (área do cliente).
     */
    public function cliente()
    {
        return $this->belongsTo(Cliente::class);
    }

    /**
     * Gera um novo token de API e persiste no usuário. Retorna o token em texto puro.
     */
    public function createApiToken(): string
    {
        $token = \Illuminate\Support\Str::random(64);
        $this->forceFill(['api_token' => $token])->save();

        return $token;
    }

    /**
     * Remove o token de API do usuário (logout da API).
     */
    public function revokeApiToken(): void
    {
        $this->forceFill(['api_token' => null])->save();
    }
}
