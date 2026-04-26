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

return [

    /*
    |--------------------------------------------------------------------------
    | Roles operacionais
    |--------------------------------------------------------------------------
    |
    | Slugs dos roles que podem acessar o sistema (dashboard, OS, clientes,
    | produtos, serviços, etc.). Usuários com is_admin=true sempre têm acesso.
    |
    */
    'operational_roles' => [
        'admin',
        'manager',
        'user',
        'seller',    // Vendedor
        'vendedor',  // slug alternativo para role Vendedor
        'attendant',
        'technician',
    ],

];
