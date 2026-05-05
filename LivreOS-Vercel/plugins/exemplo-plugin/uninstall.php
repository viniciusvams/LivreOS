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
 * Executado quando o plugin é excluído (opcional).
 * Defina ERPLUGIN_UNINSTALL antes de rodar. Use para remover tabelas, dados, etc.
 */
if (!defined('ERPLUGIN_UNINSTALL')) {
    exit;
}

\Illuminate\Support\Facades\Log::info('[exemplo-plugin] uninstall.php executado');
