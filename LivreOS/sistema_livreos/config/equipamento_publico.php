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
 * Página pública de identificação de equipamento (rota /e/{token}).
 */

return [

    /*
    |--------------------------------------------------------------------------
    | Permitir ID numérico da OS na URL
    |--------------------------------------------------------------------------
    |
    | Quando false (padrão), só o token opaco equipamento_publico_token resolve a OS.
    | true reativa /e/123 (retrocompatível, porém permite enumeração de ordens).
    |
    */
    'permitir_id_na_url' => filter_var(
        env('EQUIPAMENTO_PUBLICO_PERMITIR_ID_NA_URL', '0'),
        FILTER_VALIDATE_BOOLEAN
    ),

];
