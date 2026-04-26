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

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function index()
    {
        $viewData = [
            'title' => 'Dashboard Admin',
        ];
        if (function_exists('apply_filters')) {
            $viewData = apply_filters('admin.dashboard.data', $viewData);
        }
        if (!isset($viewData['extraCards'])) {
            $viewData['extraCards'] = [];
        }
        return erp_view('admin.dashboard', $viewData);
    }
}
