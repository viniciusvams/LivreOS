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

/**
 * Plugin Name: Boas-vindas
 * Description: Exibe notificação de boas-vindas no primeiro acesso após o login.
 * Version: 1.0.0
 * Author: ERP
 * Requires PHP: 8.1
 *
 * Usa os hooks do core: auth.login.success e header.notifications.
 * Totalmente independente - não modifica arquivos do sistema.
 */

add_action('auth.login.success', function ($user, $request) {
    $request->session()->put('show_welcome_notification', true);
}, 10, 2);

add_filter('header.notifications', function (array $notifications) {
    if (!session('show_welcome_notification')) {
        return $notifications;
    }
    session()->forget('show_welcome_notification');
    array_unshift($notifications, [
        'id' => 'welcome',
        'userName' => 'Sistema',
        'userImage' => '/images/logo/logo-icon.svg',
        'action' => 'Bem-vindo ao ERP! Este é seu primeiro acesso nesta sessão.',
        'project' => '',
        'type' => 'Boas-vindas',
        'time' => 'Agora',
        'status' => 'online',
    ]);
    return $notifications;
}, 5);
