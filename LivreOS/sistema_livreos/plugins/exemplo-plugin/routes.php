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

use Illuminate\Support\Facades\Route;

/*
 * Rotas do plugin exemplo-plugin.
 * Acessíveis em: /plugin/exemplo-plugin/*
 */

Route::get('/', function () {
    return erp_view('exemplo-plugin::pagina', [
        'title' => 'Página do Plugin Exemplo',
    ]);
})->middleware('operational')->name('pagina');
