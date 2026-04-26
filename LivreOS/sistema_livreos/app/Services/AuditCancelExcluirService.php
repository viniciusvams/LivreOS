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

namespace App\Services;

use App\Models\AuditCancelExcluir;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * Autorização e registro de cancelamentos/exclusões.
 * Exige motivo, verifica permissão (ou admin) e grava log para o administrador consultar.
 */
class AuditCancelExcluirService
{
    /**
     * Mapa: entity_type => [permission_cancelar, permission_excluir, permission_baixar, permission_estornar]
     * Admin sempre pode. null = nenhuma permissão específica necessária (qualquer autenticado).
     */
    protected const PERMISSIONS = [
        'conta_receber' => [
            'financeiro.contas-receber.cancelar',
            'financeiro.contas-receber.excluir',
            'financeiro.contas-receber.baixar',
            'financeiro.contas-receber.estornar',
        ],
        'conta_pagar' => [
            'financeiro.contas-pagar.cancelar',
            'financeiro.contas-pagar.excluir',
            'financeiro.contas-pagar.baixar',
            'financeiro.contas-pagar.estornar',
        ],
        'ordem_servico'         => [null, 'ordem-servico.excluir', null, null],
        'cliente'               => [null, 'delete-clients', null, null],
        'fornecedor'            => [null, 'delete-fornecedores', null, null],
        'pagamento_recorrente'  => [null, 'financeiro.pagamentos-recorrentes.excluir', null, null],
        'conta_pagar_recorrente'=> [null, 'financeiro.contas-pagar-recorrentes.excluir', null, null],
        'produto'               => [null, 'delete-products', null, null],
        'servico'               => [null, 'delete-services', null, null],
        'usuario'               => [null, 'delete-users', null, null],
        'role'                  => [null, 'manage-roles', null, null],
        'permission'            => [null, 'manage-permissions', null, null],
    ];

    /**
     * Verifica se o usuário pode cancelar a entidade (é admin ou tem permissão).
     */
    public function canCancel(?User $user, string $entityType): bool
    {
        $user = $user ?? Auth::user();
        if (!$user) {
            return false;
        }
        if ($user->is_admin) {
            return true;
        }
        $perms = self::PERMISSIONS[$entityType] ?? null;
        $slug = $perms[0] ?? null;
        return $slug && $user->hasPermission($slug);
    }

    /**
     * Verifica se o usuário pode excluir a entidade (é admin ou tem permissão).
     */
    public function canExcluir(?User $user, string $entityType): bool
    {
        $user = $user ?? Auth::user();
        if (!$user) {
            return false;
        }
        if ($user->is_admin) {
            return true;
        }
        $perms = self::PERMISSIONS[$entityType] ?? null;
        $slug = $perms[1] ?? null;
        return $slug && $user->hasPermission($slug);
    }

    /**
     * Registra cancelamento ou exclusão (motivo obrigatório).
     */
    public function log(
        string $action,
        string $entityType,
        ?int $entityId,
        ?string $entityDescricao,
        string $motivo,
        ?Request $request = null
    ): AuditCancelExcluir {
        $request = $request ?? request();
        return AuditCancelExcluir::create([
            'user_id' => Auth::id(),
            'action' => $action,
            'entity_type' => $entityType,
            'entity_id' => $entityId,
            'entity_descricao' => $entityDescricao ? mb_substr($entityDescricao, 0, 500) : null,
            'motivo' => $motivo,
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]);
    }

    /**
     * Verifica se o usuário pode baixar (registrar recebimento/pagamento).
     */
    public function canBaixar(?User $user, string $entityType): bool
    {
        $user = $user ?? Auth::user();
        if (!$user) {
            return false;
        }
        if ($user->is_admin) {
            return true;
        }
        $slug = (self::PERMISSIONS[$entityType] ?? null)[2] ?? null;
        return $slug ? $user->hasPermission($slug) : true; // sem slug = qualquer autenticado
    }

    /**
     * Verifica se o usuário pode estornar (desfazer baixa).
     */
    public function canEstornar(?User $user, string $entityType): bool
    {
        $user = $user ?? Auth::user();
        if (!$user) {
            return false;
        }
        if ($user->is_admin) {
            return true;
        }
        $slug = (self::PERMISSIONS[$entityType] ?? null)[3] ?? null;
        return $slug ? $user->hasPermission($slug) : true;
    }

    /** Retorna a permissão necessária para cancelar (para uso em UI). */
    public static function permissionCancelar(string $entityType): ?string
    {
        return (self::PERMISSIONS[$entityType] ?? null)[0] ?? null;
    }

    /** Retorna a permissão necessária para excluir. */
    public static function permissionExcluir(string $entityType): ?string
    {
        return (self::PERMISSIONS[$entityType] ?? null)[1] ?? null;
    }

    /** Retorna a permissão necessária para baixar. */
    public static function permissionBaixar(string $entityType): ?string
    {
        return (self::PERMISSIONS[$entityType] ?? null)[2] ?? null;
    }

    /** Retorna a permissão necessária para estornar. */
    public static function permissionEstornar(string $entityType): ?string
    {
        return (self::PERMISSIONS[$entityType] ?? null)[3] ?? null;
    }
}
