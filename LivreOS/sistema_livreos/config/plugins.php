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
    | Modo de debug (sandbox)
    |--------------------------------------------------------------------------
    | Quando true, erros ao carregar um plugin são logados mas não interrompem
    | a aplicação. O plugin com erro simplesmente não é carregado.
    */
    'debug' => env('PLUGINS_DEBUG', false),
];
