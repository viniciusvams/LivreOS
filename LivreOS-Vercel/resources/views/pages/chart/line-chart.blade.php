{{--
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
 --}}

@extends('layouts.app')

@section('content')
    <x-common.page-breadcrumb pageTitle="Line chart" />
    <div class="space-y-6">
        <x-common.component-card title="Line chart 1">
            <!-- ====== Line Chart One Start -->
            <div class="custom-scrollbar max-w-full overflow-x-auto">
                <div id="chartThree" class="min-w-[1000px]"></div>
            </div>
            <!-- ====== Line Chart One End -->
        </x-common.component-card>

        <x-common.component-card title="Line chart 2">
            <!-- ====== Line Chart Two Start -->
            <div class="custom-scrollbar max-w-full overflow-x-auto">
                <div id="chartEight" class="min-w-[1000px]"></div>
            </div>
            <!-- ====== Line Chart Two End -->
        </x-common.component-card>

        <x-common.component-card title="Line chart 3">
            <!-- ====== Line Chart Three Start -->
            <div class="custom-scrollbar max-w-full overflow-x-auto">
                <div id="chartThirteen" class="min-w-[1000px]"></div>
            </div>
            <!-- ====== Line Chart Three End -->
        </x-common.component-card>
    </div>
@endsection
