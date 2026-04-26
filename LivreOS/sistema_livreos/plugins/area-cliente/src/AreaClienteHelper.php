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

namespace AreaCliente;

/**
 * Helpers do plugin Área do Cliente.
 */
class AreaClienteHelper
{
    /** Cores de status para badges (Tailwind) */
    protected static array $statusColors = [
        'Encerrada' => 'bg-success-100 text-success-800 dark:bg-success-500/20 dark:text-success-400',
        'Encerrado' => 'bg-success-100 text-success-800 dark:bg-success-500/20 dark:text-success-400',
        'Em andamento' => 'bg-warning-100 text-warning-800 dark:bg-warning-500/20 dark:text-warning-400',
        'Aguardando aprovação' => 'bg-brand-100 text-brand-800 dark:bg-brand-500/20 dark:text-brand-400',
        'Aguardando peças' => 'bg-gray-100 text-gray-800 dark:bg-gray-500/20 dark:text-gray-400',
        'Cancelada' => 'bg-error-100 text-error-800 dark:bg-error-500/20 dark:text-error-400',
    ];

    public static function statusColor(?string $status): string
    {
        $normalizado = $status === 'Encerrado' ? 'Encerrada' : $status;
        return self::$statusColors[$normalizado ?? ''] ?? 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300';
    }

    /** Cores para status de chamado (ticket) */
    protected static array $chamadoStatusColors = [
        'aberto' => 'bg-blue-100 text-blue-800 dark:bg-blue-500/20 dark:text-blue-400',
        'em_atendimento' => 'bg-amber-100 text-amber-800 dark:bg-amber-500/20 dark:text-amber-400',
        'aguardando_cliente' => 'bg-gray-100 text-gray-800 dark:bg-gray-500/20 dark:text-gray-400',
        'aguardando_confirmacao_cliente' => 'bg-success-100 text-success-800 dark:bg-success-500/20 dark:text-success-400',
        'resolvido_confirmado' => 'bg-success-100 text-success-800 dark:bg-success-500/20 dark:text-success-400',
        'encerrado_orcamento' => 'bg-brand-100 text-brand-800 dark:bg-brand-500/20 dark:text-brand-400',
        'cancelado_duplicado' => 'bg-error-100 text-error-800 dark:bg-error-500/20 dark:text-error-400',
        'sem_sucesso' => 'bg-amber-100 text-amber-800 dark:bg-amber-500/20 dark:text-amber-400',
        'encerrado_inatividade' => 'bg-gray-100 text-gray-600 dark:bg-gray-500/20 dark:text-gray-400',
        'desistencia_cliente' => 'bg-gray-100 text-gray-600 dark:bg-gray-500/20 dark:text-gray-400',
        'resolvido' => 'bg-success-100 text-success-800 dark:bg-success-500/20 dark:text-success-400',
        'cancelado' => 'bg-error-100 text-error-800 dark:bg-error-500/20 dark:text-error-400',
        'fechado' => 'bg-gray-100 text-gray-600 dark:bg-gray-500/20 dark:text-gray-400',
    ];

    /** Cores para prioridade de chamado */
    protected static array $chamadoPrioridadeColors = [
        'baixa' => 'bg-gray-100 text-gray-700 dark:bg-gray-500/20 dark:text-gray-400',
        'media' => 'bg-blue-100 text-blue-700 dark:bg-blue-500/20 dark:text-blue-400',
        'alta' => 'bg-amber-100 text-amber-700 dark:bg-amber-500/20 dark:text-amber-400',
        'urgente' => 'bg-error-100 text-error-700 dark:bg-error-500/20 dark:text-error-400',
    ];

    public static function chamadoStatusColor(?string $status): string
    {
        return self::$chamadoStatusColors[$status ?? ''] ?? 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300';
    }

    public static function chamadoPrioridadeColor(?string $prioridade): string
    {
        return self::$chamadoPrioridadeColors[$prioridade ?? ''] ?? 'bg-gray-100 text-gray-700 dark:bg-gray-500/20 dark:text-gray-400';
    }

    /**
     * Sanitiza HTML para exibição segura (evita XSS).
     */
    public static function sanitizeHtml(?string $html): string
    {
        if (empty($html)) {
            return '';
        }
        $allowed = '<p><br><strong><b><em><i><u><ul><ol><li><div><span>';
        return strip_tags($html, $allowed);
    }

    /**
     * Processa texto HTML para PDF (tags básicas suportadas pelo Dompdf).
     */
    public static function htmlForPdf(?string $html): string
    {
        if (empty($html)) {
            return '';
        }
        $html = html_entity_decode($html, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $html = strip_tags($html, '<p><br><strong><b><em><i><u><ul><ol><li><div><span>');
        $html = preg_replace('/(?<!>)\r\n|\r|\n(?!<)/', '<br>', $html ?? '');
        return trim($html);
    }
}
