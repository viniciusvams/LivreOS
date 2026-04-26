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
<div class="mx-auto max-w-5xl">
    <div class="mb-8">
        <h1 class="mb-2 text-3xl font-bold text-gray-800 dark:text-white/90">Guia de Plugins para Desenvolvedores</h1>
        <p class="text-lg text-gray-600 dark:text-gray-400">Documentação técnica completa para criação de plugins no ERP.</p>
    </div>

    <div class="mb-6 rounded-xl border border-brand-200 bg-brand-50 p-4 text-sm text-brand-900 dark:border-brand-900/40 dark:bg-brand-900/20 dark:text-brand-200">
        <strong>Dica:</strong> este conteúdo vem do arquivo <code class="rounded bg-brand-100 px-1 dark:bg-brand-900/30">docs/CRIANDO-PLUGINS.md</code> para manter o guia sempre atualizado para os devs.
    </div>

    <div class="prose prose-slate max-w-none dark:prose-invert prose-headings:scroll-mt-20 prose-table:w-full prose-th:text-left prose-th:font-semibold prose-td:align-top prose-pre:rounded-lg prose-pre:border prose-pre:border-gray-200 dark:prose-pre:border-gray-700 prose-pre:bg-gray-50 dark:prose-pre:bg-gray-900">
        {!! $pluginsGuideHtml !!}
    </div>
</div>
@endsection
