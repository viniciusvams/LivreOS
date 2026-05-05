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

namespace App\Http\Controllers;

use Illuminate\Support\Str;

class AjudaController extends Controller
{
    /**
     * Exibe a área de ajuda ao usuário (manual em linguagem simples).
     */
    public function index()
    {
        return erp_view('ajuda.index', ['title' => 'Ajuda']);
    }

    /**
     * Exibe o guia dedicado de plugins para desenvolvedores.
     */
    public function plugins()
    {
        $docPath = base_path('docs/CRIANDO-PLUGINS.md');
        $markdown = file_exists($docPath)
            ? file_get_contents($docPath)
            : "# Guia de Plugins\n\nDocumento não encontrado em docs/CRIANDO-PLUGINS.md.";

        return erp_view('ajuda.plugins', [
            'title' => 'Ajuda - Plugins',
            'pluginsGuideHtml' => Str::markdown($markdown),
        ]);
    }
}
