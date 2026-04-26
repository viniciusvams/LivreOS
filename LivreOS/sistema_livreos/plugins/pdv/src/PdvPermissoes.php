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

namespace Pdv;

use App\Models\User;
use Illuminate\Support\Facades\Hash;

class PdvPermissoes
{
    public const PERMISSAO_CANCELAR_TOTAL = 'pdv.cancelar_venda_total';
    public const PERMISSAO_CANCELAR_PARCIAL = 'pdv.cancelar_venda_parcial';

    /**
     * @deprecated Use podeCancelarVendaTotal() / podeCancelarVendaParcial().
     */
    public static function podeCancelarVendas(?int $userId): bool
    {
        return self::podeCancelarVendaTotal($userId) || self::podeCancelarVendaParcial($userId);
    }

    /**
     * Cancelamento total: administrador, permissão Laravel ou flag no PDV (legado/granular).
     */
    public static function podeCancelarVendaTotal(?int $userId): bool
    {
        return self::usuarioPodeCancelarVendaTotal(User::find($userId));
    }

    /**
     * Cancelamento parcial de itens.
     */
    public static function podeCancelarVendaParcial(?int $userId): bool
    {
        return self::usuarioPodeCancelarVendaParcial(User::find($userId));
    }

    public static function usuarioPodeCancelarVendaTotal(?User $user): bool
    {
        if (!$user) {
            return false;
        }
        if ($user->is_admin ?? false) {
            return true;
        }
        if ($user->hasPermission(self::PERMISSAO_CANCELAR_TOTAL)) {
            return true;
        }
        $p = self::getPermissoesUsuario((int) $user->id);

        return (bool) ($p['pode_cancelar_venda_total'] ?? false);
    }

    public static function usuarioPodeCancelarVendaParcial(?User $user): bool
    {
        if (!$user) {
            return false;
        }
        if ($user->is_admin ?? false) {
            return true;
        }
        if ($user->hasPermission(self::PERMISSAO_CANCELAR_PARCIAL)) {
            return true;
        }
        $p = self::getPermissoesUsuario((int) $user->id);

        return (bool) ($p['pode_cancelar_venda_parcial'] ?? false);
    }

    /**
     * Valida senha do autorizador que tenha permissão para o tipo de cancelamento solicitado.
     *
     * @param 'total'|'parcial' $tipoCancelamento
     */
    public static function validarSenhaAutorizadorParaCancelamento(string $senha, string $tipoCancelamento): ?int
    {
        $senha = trim($senha);
        if ($senha === '') {
            return null;
        }
        $current = auth()->user();
        if ($current && $current->password && \Illuminate\Support\Facades\Hash::check($senha, $current->password)) {
            if ($tipoCancelamento === 'parcial' && self::usuarioPodeCancelarVendaParcial($current)) {
                return (int) $current->id;
            }
            if ($tipoCancelamento === 'total' && self::usuarioPodeCancelarVendaTotal($current)) {
                return (int) $current->id;
            }
        }
        $ids = self::getAutorizadorUserIds();
        foreach ($ids as $autorizadorId) {
            $autorizador = User::find($autorizadorId);
            if (!$autorizador || !$autorizador->password || !\Illuminate\Support\Facades\Hash::check($senha, $autorizador->password)) {
                continue;
            }
            if ($tipoCancelamento === 'parcial' && self::usuarioPodeCancelarVendaParcial($autorizador)) {
                return (int) $autorizador->id;
            }
            if ($tipoCancelamento === 'total' && self::usuarioPodeCancelarVendaTotal($autorizador)) {
                return (int) $autorizador->id;
            }
        }

        return null;
    }

    /**
     * Verifica se o usuário pode fazer sangria.
     */
    public static function podeSangria(?int $userId): bool
    {
        $permissoes = self::getPermissoesUsuario($userId);
        return $permissoes['pode_sangria'] ?? true;
    }

    /**
     * Retorna as permissões do usuário (cancelamento total/parcial, sangria, estoque zerado).
     * Sem entrada no JSON: mesmo padrão antigo (cancelar/sangria liberados).
     */
    public static function getPermissoesUsuario(?int $userId): array
    {
        if ($userId === null) {
            return [
                'pode_cancelar_vendas' => true,
                'pode_cancelar_venda_total' => true,
                'pode_cancelar_venda_parcial' => true,
                'pode_sangria' => true,
                'pode_vender_estoque_zerado' => false,
            ];
        }
        $todas = get_option('pdv_permissoes_usuarios', null, 'pdv');
        if (!is_array($todas)) {
            $todas = [];
        }
        $p = $todas[(string) $userId] ?? null;
        if (!is_array($p)) {
            return [
                'pode_cancelar_vendas' => true,
                'pode_cancelar_venda_total' => true,
                'pode_cancelar_venda_parcial' => true,
                'pode_sangria' => true,
                'pode_vender_estoque_zerado' => false,
            ];
        }
        $legacyCancel = (bool) ($p['pode_cancelar_vendas'] ?? true);
        $hasGranular = array_key_exists('pode_cancelar_venda_total', $p) || array_key_exists('pode_cancelar_venda_parcial', $p);
        if ($hasGranular) {
            $podeTotal = (bool) ($p['pode_cancelar_venda_total'] ?? false);
            $podeParcial = (bool) ($p['pode_cancelar_venda_parcial'] ?? false);
        } else {
            $podeTotal = $legacyCancel;
            $podeParcial = $legacyCancel;
        }

        return [
            'pode_cancelar_vendas' => $legacyCancel,
            'pode_cancelar_venda_total' => $podeTotal,
            'pode_cancelar_venda_parcial' => $podeParcial,
            'pode_sangria' => (bool) ($p['pode_sangria'] ?? true),
            'pode_vender_estoque_zerado' => (bool) ($p['pode_vender_estoque_zerado'] ?? false),
        ];
    }

    /**
     * Controle de estoque ativo: impede venda com estoque zerado/insuficiente sem autorização.
     */
    public static function getControleEstoque(): bool
    {
        $v = get_option('pdv_controle_estoque', false, 'pdv');
        return $v === true || $v === 1 || $v === '1';
    }

    /**
     * IDs dos usuários que podem autorizar venda com estoque zerado (admin + quem tem permissão).
     * Usado para validar senha no modal de autorização.
     */
    public static function getEstoqueZeroAutorizadorUserIds(): array
    {
        $usuarios = User::orderBy('name')->get(['id', 'is_admin']);
        $todas = get_option('pdv_permissoes_usuarios', null, 'pdv');
        if (!is_array($todas)) {
            $todas = [];
        }
        $ids = [];
        foreach ($usuarios as $u) {
            if ($u->is_admin ?? false) {
                $ids[] = (int) $u->id;
                continue;
            }
            $p = $todas[(string) $u->id] ?? null;
            if (is_array($p) && !empty($p['pode_vender_estoque_zerado'])) {
                $ids[] = (int) $u->id;
            }
        }
        return array_values(array_unique($ids));
    }

    /**
     * Valida senha para autorizar venda com estoque zerado. Retorna user_id do autorizador ou null.
     */
    public static function validarSenhaEstoqueZeroRetornaUserId(string $senha): ?int
    {
        $senha = trim($senha);
        if ($senha === '') {
            return null;
        }
        $user = auth()->user();
        if ($user && ($user->is_admin ?? false) && $user->password && Hash::check($senha, $user->password)) {
            return (int) $user->id;
        }
        $ids = self::getEstoqueZeroAutorizadorUserIds();
        foreach ($ids as $autorizadorId) {
            $autorizador = User::find($autorizadorId);
            if ($autorizador && $autorizador->password && Hash::check($senha, $autorizador->password)) {
                return (int) $autorizador->id;
            }
        }
        return null;
    }

    /**
     * Nome(s) dos autorizadores de estoque zerado para exibir no modal.
     */
    public static function getEstoqueZeroAutorizadorNome(): ?string
    {
        $ids = self::getEstoqueZeroAutorizadorUserIds();
        if (empty($ids)) {
            return null;
        }
        $nomes = User::whereIn('id', $ids)->pluck('name')->filter()->values()->all();
        return $nomes ? implode(', ', $nomes) : null;
    }

    /**
     * IDs dos usuários autorizadores (cuja senha pode autorizar cancelamento/sangria/desconto quando necessário).
     * Retorna array de IDs. Retrocompat: se pdv_autorizadores_user_ids vazio mas pdv_autorizador_user_id definido, usa este.
     */
    public static function getAutorizadorUserIds(): array
    {
        $ids = get_option('pdv_autorizadores_user_ids', null, 'pdv');
        if (is_array($ids) && count($ids) > 0) {
            return array_values(array_map('intval', array_filter($ids)));
        }
        $legado = get_option('pdv_autorizador_user_id', null, 'pdv');
        if ($legado !== null && $legado !== '') {
            return [(int) $legado];
        }
        return [];
    }

    /**
     * @deprecated Use getAutorizadorUserIds(). Retorna o primeiro autorizador para compatibilidade.
     */
    public static function getAutorizadorUserId(): ?int
    {
        $ids = self::getAutorizadorUserIds();
        return $ids[0] ?? null;
    }

    /**
     * Valida a senha do autorizador. Retorna true se a senha estiver correta para qualquer um dos autorizadores.
     */
    public static function validarSenhaAutorizador(string $senha): bool
    {
        return self::validarSenhaAutorizadorRetornaUserId($senha) !== null;
    }

    /**
     * Valida a senha do autorizador e retorna o ID do usuário que autorizou (qualquer um dos autorizadores).
     * Retorna null se a senha estiver incorreta ou não houver autorizadores.
     */
    public static function validarSenhaAutorizadorRetornaUserId(string $senha): ?int
    {
        $senha = trim($senha);
        if ($senha === '') {
            return null;
        }
        $user = auth()->user();
        if ($user && ($user->is_admin ?? false) && $user->password && Hash::check($senha, $user->password)) {
            return (int) $user->id;
        }
        $ids = self::getAutorizadorUserIds();
        if (empty($ids)) {
            return null;
        }
        foreach ($ids as $autorizadorId) {
            $autorizador = User::find($autorizadorId);
            if ($autorizador && $autorizador->password && Hash::check($senha, $autorizador->password)) {
                return (int) $autorizador->id;
            }
        }
        return null;
    }

    /**
     * Nome(s) dos usuários autorizadores para exibir no modal de senha (ex: "João" ou "João, Maria").
     */
    public static function getAutorizadorNome(): ?string
    {
        $ids = self::getAutorizadorUserIds();
        if (empty($ids)) {
            return null;
        }
        $nomes = User::whereIn('id', $ids)->pluck('name')->filter()->values()->all();
        return $nomes ? implode(', ', $nomes) : null;
    }

    /**
     * Desconto máximo permitido em % (null = sem limite). Acima disso exige senha do autorizador na tela de pagamento.
     */
    public static function getDescontoMaximoPercentual(): ?float
    {
        $v = get_option('pdv_desconto_maximo_percentual', null, 'pdv');
        return $v !== null && $v !== '' ? (float) $v : null;
    }

    /**
     * Se o usuário pode ver o saldo esperado no fechamento cego (admin ou autorizador).
     */
    public static function podeVerSaldoNoFechamento(?int $userId): bool
    {
        if ($userId === null) {
            return false;
        }
        $user = User::find($userId);
        if ($user && ($user->is_admin ?? false)) {
            return true;
        }
        return in_array((int) $userId, self::getAutorizadorUserIds(), true);
    }

    /**
     * Limite em R$ para dispensar aprovação no fechamento. Acima disso exige senha do autorizador.
     */
    public static function getQuebraSobraLimiteSemAprovacao(): float
    {
        $v = get_option('pdv_quebra_sobra_limite_sem_aprovacao', 5, 'pdv');
        return $v !== null && $v !== '' ? (float) $v : 5.0;
    }
}
