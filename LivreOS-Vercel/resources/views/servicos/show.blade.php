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
<div class="mb-6 flex items-center justify-between">
    <div>
        <h1 class="mb-2 text-2xl font-semibold text-gray-800 dark:text-white/90">Detalhes do Serviço</h1>
        <p class="text-gray-600 dark:text-gray-400">{{ $servico->nome }}</p>
    </div>
    <div class="flex gap-2">
        <a href="{{ route('servicos.edit', $servico) }}" class="rounded-lg bg-brand-500 px-4 py-2.5 text-sm font-medium text-white hover:bg-brand-600">Editar</a>
        <a href="{{ route('servicos.index') }}" class="rounded-lg border border-gray-300 bg-white px-4 py-2.5 text-sm font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-300">Voltar</a>
    </div>
</div>

<div class="grid grid-cols-1 gap-6 lg:grid-cols-2">
    <div class="rounded-lg border border-gray-200 bg-white p-6 shadow-theme-sm dark:border-gray-800 dark:bg-gray-800">
        <h2 class="mb-4 text-lg font-semibold text-gray-800 dark:text-white/90">Campos Básicos</h2>
        <dl class="space-y-3">
            <div>
                <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Código interno</dt>
                <dd class="mt-1 text-sm text-gray-800 dark:text-white/90">{{ $servico->codigo_interno }}</dd>
            </div>
            <div>
                <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Nome</dt>
                <dd class="mt-1 text-sm text-gray-800 dark:text-white/90">{{ $servico->nome }}</dd>
            </div>
            <div>
                <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Preço</dt>
                <dd class="mt-1 text-sm text-gray-800 dark:text-white/90">{{ $servico->preco ? number_format($servico->preco, 2, ',', '.') : '-' }}</dd>
            </div>
            <div>
                <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Unidade</dt>
                <dd class="mt-1 text-sm text-gray-800 dark:text-white/90">{{ $servico->unidade }}</dd>
            </div>
        </dl>
    </div>

    <div class="rounded-lg border border-gray-200 bg-white p-6 shadow-theme-sm dark:border-gray-800 dark:bg-gray-800">
        <h2 class="mb-4 text-lg font-semibold text-gray-800 dark:text-white/90">Campos Opcionais</h2>
        <dl class="space-y-3">
            <div>
                <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">SKU</dt>
                <dd class="mt-1 text-sm text-gray-800 dark:text-white/90">{{ $servico->codigo_sku ?: '-' }}</dd>
            </div>
            <div>
                <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Categoria</dt>
                <dd class="mt-1 text-sm text-gray-800 dark:text-white/90">{{ $servico->categoria?->nome ?: '-' }}</dd>
            </div>
            <div>
                <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Valor de custo</dt>
                <dd class="mt-1 text-sm text-gray-800 dark:text-white/90">{{ $servico->valor_custo ?? '-' }}</dd>
            </div>
            <div>
                <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Valor da comissão</dt>
                <dd class="mt-1 text-sm text-gray-800 dark:text-white/90">
                    @if($servico->valor_comissao !== null)
                        {{ $servico->valor_comissao }}{{ ($servico->tipo_comissao ?? 'valor') === 'percentual' ? '%' : ' R$' }}
                    @else
                        -
                    @endif
                </dd>
            </div>
        </dl>
    </div>

    <div class="rounded-lg border border-gray-200 bg-white p-6 shadow-theme-sm dark:border-gray-800 dark:bg-gray-800">
        <h2 class="mb-4 text-lg font-semibold text-gray-800 dark:text-white/90">Descrição e prazos</h2>
        <p class="text-sm text-gray-700 dark:text-gray-300">{!! $servico->descricao ? nl2br(e($servico->descricao)) : '-' !!}</p>
        <div class="mt-4">
            <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Prazos</dt>
            <dd class="mt-1 text-sm text-gray-800 dark:text-white/90">{!! $servico->prazos ? nl2br(e($servico->prazos)) : '-' !!}</dd>
        </div>
    </div>

    <div class="rounded-lg border border-gray-200 bg-white p-6 shadow-theme-sm dark:border-gray-800 dark:bg-gray-800">
        <h2 class="mb-4 text-lg font-semibold text-gray-800 dark:text-white/90">Informações fiscais</h2>
        @if($servico->informacoes_fiscais)
            <ul class="list-disc pl-4 text-sm text-gray-700 dark:text-gray-300">
                @foreach($servico->informacoes_fiscais as $item)
                    <li>{{ $item }}</li>
                @endforeach
            </ul>
        @else
            <p class="text-sm text-gray-500 dark:text-gray-400">Nenhuma informação fiscal cadastrada</p>
        @endif
    </div>

    <div class="rounded-lg border border-gray-200 bg-white p-6 shadow-theme-sm dark:border-gray-800 dark:bg-gray-800">
        <h2 class="mb-4 text-lg font-semibold text-gray-800 dark:text-white/90">Campos customizados</h2>
        @if($servico->campos_customizados)
            <div class="space-y-2">
                @foreach($servico->campos_customizados as $item)
                    <div class="rounded-lg border border-gray-200 px-3 py-2 text-sm dark:border-gray-700">
                        <span class="font-medium">{{ $item['chave'] ?? 'Campo' }}:</span>
                        <span>{{ $item['valor'] ?? '-' }}</span>
                    </div>
                @endforeach
            </div>
        @else
            <p class="text-sm text-gray-500 dark:text-gray-400">Nenhum campo customizado cadastrado</p>
        @endif
    </div>

    <div class="rounded-lg border border-gray-200 bg-white p-6 shadow-theme-sm dark:border-gray-800 dark:bg-gray-800 lg:col-span-2">
        <h2 class="mb-4 text-lg font-semibold text-gray-800 dark:text-white/90">Imagens</h2>
        @if($servico->imagens->count())
            <div class="grid grid-cols-2 gap-4 md:grid-cols-4">
                @foreach($servico->imagens as $img)
                    @php
                        if ($img->tipo === 'url') {
                            $src = $img->url_externa;
                        } else {
                            $path = storage_path('app/public/' . ltrim($img->caminho_local ?? '', '/'));
                            if ($img->caminho_local && file_exists($path)) {
                                $mime = mime_content_type($path) ?: 'image/jpeg';
                                $src = 'data:' . $mime . ';base64,' . base64_encode(file_get_contents($path));
                            } else {
                                $src = asset('storage/' . ltrim($img->caminho_local ?? '', '/'));
                            }
                        }
                        $abrirHref = $img->tipo === 'url' ? $img->url_externa : route('servicos.imagem', $img);
                    @endphp
                    <div class="rounded-lg border border-gray-200 p-2 dark:border-gray-700">
                        <img src="{{ $src }}" alt="Imagem do serviço" class="h-24 w-full rounded object-cover">
                        <div class="mt-2 flex items-center justify-between text-xs text-gray-600 dark:text-gray-400">
                            <span>{{ $img->tipo === 'url' ? 'URL' : 'Upload' }}</span>
                            <a href="{{ $abrirHref }}" target="_blank" class="text-brand-500 hover:underline">Abrir</a>
                        </div>
                    </div>
                @endforeach
            </div>
        @else
            <p class="text-sm text-gray-500 dark:text-gray-400">Nenhuma imagem cadastrada</p>
        @endif
    </div>
</div>

@if(function_exists('do_action'))
<?php do_action('admin.servicos.show.extra', $servico); ?>
@endif
@endsection
