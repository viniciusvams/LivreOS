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
        <h1 class="mb-2 text-2xl font-semibold text-gray-800 dark:text-white/90">Detalhes do Produto</h1>
        <p class="text-gray-600 dark:text-gray-400">{{ $produto->nome }}</p>
    </div>
    <div class="flex gap-2">
        <a href="{{ route('produtos.edit', $produto) }}" class="rounded-lg bg-brand-500 px-4 py-2.5 text-sm font-medium text-white hover:bg-brand-600">Editar</a>
        <a href="{{ route('produtos.index') }}" class="rounded-lg border border-gray-300 bg-white px-4 py-2.5 text-sm font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-300">Voltar</a>
    </div>
</div>

<div class="grid grid-cols-1 gap-6 lg:grid-cols-2">
    <div class="rounded-lg border border-gray-200 bg-white p-6 shadow-theme-sm dark:border-gray-800 dark:bg-gray-800">
        <h2 class="mb-4 text-lg font-semibold text-gray-800 dark:text-white/90">Dados Básicos</h2>
        <dl class="space-y-3">
            <div>
                <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Nome</dt>
                <dd class="mt-1 text-sm text-gray-800 dark:text-white/90">{{ $produto->nome }}</dd>
            </div>
            <div>
                <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">SKU</dt>
                <dd class="mt-1 text-sm text-gray-800 dark:text-white/90">{{ $produto->codigo_sku ?: '-' }}</dd>
            </div>
            <div>
                <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Unidade</dt>
                <dd class="mt-1 text-sm text-gray-800 dark:text-white/90">{{ $produto->unidade }}</dd>
            </div>
            <div>
                <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Preço Venda</dt>
                <dd class="mt-1 text-sm text-gray-800 dark:text-white/90">{{ $produto->preco_venda ? number_format($produto->preco_venda, 2, ',', '.') : '-' }}</dd>
            </div>
            <div>
                <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Condição</dt>
                <dd class="mt-1 text-sm text-gray-800 dark:text-white/90">{{ $produto->condicao ? ucfirst($produto->condicao) : '-' }}</dd>
            </div>
            <div>
                <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Linha de Produto</dt>
                <dd class="mt-1 text-sm text-gray-800 dark:text-white/90">{{ $produto->linha_produto ?: '-' }}</dd>
            </div>
            <div>
                <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Categoria</dt>
                <dd class="mt-1 text-sm text-gray-800 dark:text-white/90">{{ $produto->categoria?->nome ?: '-' }}</dd>
            </div>
            <div>
                <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Formato</dt>
                <dd class="mt-1 text-sm text-gray-800 dark:text-white/90">{{ ucfirst($produto->formato) }}</dd>
            </div>
        </dl>
    </div>

    <div class="rounded-lg border border-gray-200 bg-white p-6 shadow-theme-sm dark:border-gray-800 dark:bg-gray-800">
        <h2 class="mb-4 text-lg font-semibold text-gray-800 dark:text-white/90">Características</h2>
        <dl class="space-y-3">
            <div>
                <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Marca</dt>
                <dd class="mt-1 text-sm text-gray-800 dark:text-white/90">{{ $produto->marca ?: '-' }}</dd>
            </div>
            <div>
                <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Produção</dt>
                <dd class="mt-1 text-sm text-gray-800 dark:text-white/90">{{ $produto->producao ? ucfirst($produto->producao) : '-' }}</dd>
            </div>
            <div>
                <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Validade</dt>
                <dd class="mt-1 text-sm text-gray-800 dark:text-white/90">{{ $produto->data_validade?->format('d/m/Y') ?: '-' }}</dd>
            </div>
            <div>
                <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Frete grátis</dt>
                <dd class="mt-1 text-sm text-gray-800 dark:text-white/90">{{ $produto->frete_gratis ? 'Sim' : 'Não' }}</dd>
            </div>
            <div>
                <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">EAN</dt>
                <dd class="mt-1 text-sm text-gray-800 dark:text-white/90">{{ $produto->ean ?: '-' }}</dd>
            </div>
            <div>
                <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">EAN Tributário</dt>
                <dd class="mt-1 text-sm text-gray-800 dark:text-white/90">{{ $produto->ean_tributario ?: '-' }}</dd>
            </div>
            <div>
                <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Peso Líquido</dt>
                <dd class="mt-1 text-sm text-gray-800 dark:text-white/90">{{ $produto->peso_liquido ?? '-' }}</dd>
            </div>
            <div>
                <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Peso Bruto</dt>
                <dd class="mt-1 text-sm text-gray-800 dark:text-white/90">{{ $produto->peso_bruto ?? '-' }}</dd>
            </div>
            <div>
                <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Dimensões (L x A x P)</dt>
                <dd class="mt-1 text-sm text-gray-800 dark:text-white/90">
                    {{ $produto->largura ?? '-' }} x {{ $produto->altura ?? '-' }} x {{ $produto->profundidade ?? '-' }}
                </dd>
            </div>
            <div>
                <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Volumes</dt>
                <dd class="mt-1 text-sm text-gray-800 dark:text-white/90">{{ $produto->volumes ?? '-' }}</dd>
            </div>
            <div>
                <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Itens por Caixa</dt>
                <dd class="mt-1 text-sm text-gray-800 dark:text-white/90">{{ $produto->itens_por_caixa ?? '-' }}</dd>
            </div>
            <div>
                <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Descrição Curta</dt>
                <dd class="mt-1 text-sm text-gray-800 dark:text-white/90">{{ $produto->descricao_curta ?: '-' }}</dd>
            </div>
            <div>
                <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Descrição Complementar</dt>
                <dd class="mt-1 text-sm text-gray-800 dark:text-white/90">{!! $produto->descricao_complementar ? nl2br(e($produto->descricao_complementar)) : '-' !!}</dd>
            </div>
            <div>
                <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Link Externo</dt>
                <dd class="mt-1 text-sm text-gray-800 dark:text-white/90">
                    @if($produto->link_externo)
                        <a href="{{ $produto->link_externo }}" target="_blank" class="text-brand-500 hover:underline">Abrir link</a>
                    @else
                        -
                    @endif
                </dd>
            </div>
            <div>
                <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Vídeo</dt>
                <dd class="mt-1 text-sm text-gray-800 dark:text-white/90">
                    @if($produto->video_url)
                        <a href="{{ $produto->video_url }}" target="_blank" class="text-brand-500 hover:underline">Abrir vídeo</a>
                    @else
                        -
                    @endif
                </dd>
            </div>
            <div>
                <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Observações</dt>
                <dd class="mt-1 text-sm text-gray-800 dark:text-white/90">{!! $produto->observacoes ? nl2br(e($produto->observacoes)) : '-' !!}</dd>
            </div>
            <div>
                <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Tags</dt>
                <dd class="mt-1 text-sm text-gray-800 dark:text-white/90">{{ $produto->tags ? implode(', ', $produto->tags) : '-' }}</dd>
            </div>
        </dl>
    </div>

    <div class="rounded-lg border border-gray-200 bg-white p-6 shadow-theme-sm dark:border-gray-800 dark:bg-gray-800">
        <h2 class="mb-4 text-lg font-semibold text-gray-800 dark:text-white/90">Imagens</h2>
        @if($produto->imagens->count())
            <div class="grid grid-cols-2 gap-4 md:grid-cols-4">
                @foreach($produto->imagens as $img)
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
                    @endphp
                    <div class="rounded-lg border border-gray-200 p-2 dark:border-gray-700">
                        <img src="{{ $src }}" alt="Imagem do produto" class="h-24 w-full rounded object-cover">
                        <div class="mt-2 flex items-center justify-between text-xs text-gray-600 dark:text-gray-400">
                            <span>{{ $img->tipo === 'url' ? 'URL' : 'Upload' }}</span>
                            <a href="{{ $img->tipo === 'url' ? $img->url_externa : route('produtos.imagem', $img) }}" target="_blank" class="text-brand-500 hover:underline">Abrir</a>
                        </div>
                    </div>
                @endforeach
            </div>
        @else
            <p class="text-sm text-gray-500 dark:text-gray-400">Nenhuma imagem cadastrada</p>
        @endif
    </div>

    <div class="rounded-lg border border-gray-200 bg-white p-6 shadow-theme-sm dark:border-gray-800 dark:bg-gray-800">
        <h2 class="mb-4 text-lg font-semibold text-gray-800 dark:text-white/90">Estoque</h2>
        <dl class="space-y-3">
            <div>
                <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Quantidade</dt>
                <dd class="mt-1 text-sm text-gray-800 dark:text-white/90">{{ $produto->estoque_quantidade !== null && $produto->estoque_quantidade !== '' ? format_quantity($produto->estoque_quantidade) : '-' }}</dd>
            </div>
            <div>
                <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Mínimo</dt>
                <dd class="mt-1 text-sm text-gray-800 dark:text-white/90">{{ $produto->estoque_minimo !== null && $produto->estoque_minimo !== '' ? format_quantity($produto->estoque_minimo) : '-' }}</dd>
            </div>
            <div>
                <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Máximo</dt>
                <dd class="mt-1 text-sm text-gray-800 dark:text-white/90">{{ $produto->estoque_maximo !== null && $produto->estoque_maximo !== '' ? format_quantity($produto->estoque_maximo) : '-' }}</dd>
            </div>
            <div>
                <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Crossdocking (dias)</dt>
                <dd class="mt-1 text-sm text-gray-800 dark:text-white/90">{{ $produto->estoque_crossdocking ?? '-' }}</dd>
            </div>
            <div>
                <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Localização</dt>
                <dd class="mt-1 text-sm text-gray-800 dark:text-white/90">{{ $produto->estoque_localizacao ?? '-' }}</dd>
            </div>
            <div>
                <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Depósito</dt>
                <dd class="mt-1 text-sm text-gray-800 dark:text-white/90">{{ $produto->deposito?->nome ?: '-' }}</dd>
            </div>
            <div>
                <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Preço Compra Unitário</dt>
                <dd class="mt-1 text-sm text-gray-800 dark:text-white/90">{{ $produto->estoque_preco_compra_unitario !== null ? format_br_decimal($produto->estoque_preco_compra_unitario) : '-' }}</dd>
            </div>
            <div>
                <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Preço Custo</dt>
                <dd class="mt-1 text-sm text-gray-800 dark:text-white/90">{{ $produto->estoque_preco_custo_unitario !== null ? format_br_decimal($produto->estoque_preco_custo_unitario) : '-' }}</dd>
            </div>
            <div>
                <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Observação</dt>
                <dd class="mt-1 text-sm text-gray-800 dark:text-white/90">{!! $produto->estoque_observacao ? nl2br(e($produto->estoque_observacao)) : '-' !!}</dd>
            </div>
        </dl>
    </div>

    <div class="rounded-lg border border-gray-200 bg-white p-6 shadow-theme-sm dark:border-gray-800 dark:bg-gray-800">
        <h2 class="mb-4 text-lg font-semibold text-gray-800 dark:text-white/90">Tributação</h2>
        <dl class="space-y-3">
            <div>
                <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Origem</dt>
                <dd class="mt-1 text-sm text-gray-800 dark:text-white/90">{{ $produto->tributacao_origem ?? '-' }}</dd>
            </div>
            <div>
                <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">NCM</dt>
                <dd class="mt-1 text-sm text-gray-800 dark:text-white/90">{{ $produto->tributacao_ncm ?? '-' }}</dd>
            </div>
            <div>
                <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">CEST</dt>
                <dd class="mt-1 text-sm text-gray-800 dark:text-white/90">{{ $produto->tributacao_cest ?? '-' }}</dd>
            </div>
            <div>
                <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Tipo Item</dt>
                <dd class="mt-1 text-sm text-gray-800 dark:text-white/90">{{ $produto->tributacao_tipo_item ?? '-' }}</dd>
            </div>
            <div>
                <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">% Tributos</dt>
                <dd class="mt-1 text-sm text-gray-800 dark:text-white/90">{{ $produto->tributacao_percentual_tributos ?? '-' }}</dd>
            </div>
            <div>
                <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Grupo Produtos</dt>
                <dd class="mt-1 text-sm text-gray-800 dark:text-white/90">{{ $produto->tributacao_grupo_produtos ?? '-' }}</dd>
            </div>
            <div>
                <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Informações Adicionais</dt>
                <dd class="mt-1 text-sm text-gray-800 dark:text-white/90">{!! $produto->tributacao_info_adicionais ? nl2br(e($produto->tributacao_info_adicionais)) : '-' !!}</dd>
            </div>
        </dl>
    </div>

    <div class="rounded-lg border border-gray-200 bg-white p-6 shadow-theme-sm dark:border-gray-800 dark:bg-gray-800">
        <h2 class="mb-4 text-lg font-semibold text-gray-800 dark:text-white/90">Tabelas de Preço</h2>
        @if($produto->tabelasPrecos->count())
            <div class="space-y-2">
                @foreach($produto->tabelasPrecos as $tabela)
                    <div class="flex items-center justify-between rounded-lg border border-gray-200 px-3 py-2 text-sm dark:border-gray-700">
                        <span class="text-gray-700 dark:text-gray-300">{{ $tabela->nome }}</span>
                        <span class="text-gray-800 dark:text-white/90">{{ $tabela->pivot?->preco ? number_format($tabela->pivot->preco, 2, ',', '.') : '-' }}</span>
                    </div>
                @endforeach
            </div>
        @else
            <p class="text-sm text-gray-500 dark:text-gray-400">Nenhuma tabela de preço vinculada</p>
        @endif
    </div>

    <div class="rounded-lg border border-gray-200 bg-white p-6 shadow-theme-sm dark:border-gray-800 dark:bg-gray-800">
        <h2 class="mb-4 text-lg font-semibold text-gray-800 dark:text-white/90">Variações</h2>
        @if($produto->formato === 'variacao')
            @if($produto->variacaoAtributos->count())
                <div class="mb-4 space-y-2">
                    @foreach($produto->variacaoAtributos as $atributo)
                        <div class="text-sm text-gray-700 dark:text-gray-300">
                            <span class="font-medium">{{ $atributo->atributo_nome }}:</span>
                            {{ is_array($atributo->opcoes) ? implode(', ', $atributo->opcoes) : $atributo->opcoes }}
                        </div>
                    @endforeach
                </div>
            @endif
            @if($produto->variacoes->count())
                <div class="space-y-2">
                    @foreach($produto->variacoes as $variacao)
                        <div class="rounded-lg border border-gray-200 px-3 py-2 text-sm dark:border-gray-700">
                            <div class="flex flex-wrap items-center justify-between gap-2">
                                <span class="text-gray-800 dark:text-white/90">SKU: {{ $variacao->referencia_sku }}</span>
                                <span class="text-gray-600 dark:text-gray-400">EAN: {{ $variacao->ean_variacao ?: '-' }}</span>
                            </div>
                            <div class="mt-1 text-gray-600 dark:text-gray-400">
                                Opções: {{ is_array($variacao->opcoes_valores) ? implode(', ', $variacao->opcoes_valores) : $variacao->opcoes_valores }}
                                | Qtd: {{ $variacao->quantidade !== null && $variacao->quantidade !== '' ? format_quantity($variacao->quantidade) : '-' }}
                            </div>
                        </div>
                    @endforeach
                </div>
            @else
                <p class="text-sm text-gray-500 dark:text-gray-400">Nenhuma variação cadastrada</p>
            @endif
        @else
            <p class="text-sm text-gray-500 dark:text-gray-400">Produto não usa variações</p>
        @endif
    </div>

    <div class="rounded-lg border border-gray-200 bg-white p-6 shadow-theme-sm dark:border-gray-800 dark:bg-gray-800">
        <h2 class="mb-4 text-lg font-semibold text-gray-800 dark:text-white/90">Composição / Kit</h2>
        @if($produto->formato === 'composicao')
            @if($produto->composicoes->count())
                <div class="space-y-2">
                    @foreach($produto->composicoes as $comp)
                        <div class="rounded-lg border border-gray-200 px-3 py-2 text-sm dark:border-gray-700">
                            <div class="flex flex-wrap items-center justify-between gap-2">
                                <span class="text-gray-800 dark:text-white/90">{{ $comp->componente?->nome ?: 'Componente' }}</span>
                                <span class="text-gray-600 dark:text-gray-400">Qtd: {{ format_quantity($comp->quantidade) }}</span>
                            </div>
                            <div class="mt-1 text-gray-600 dark:text-gray-400">
                                Custo Unitário: {{ $comp->custo_unitario ?? '-' }}
                                | Custo Total: {{ $comp->custo_total ?? '-' }}
                            </div>
                        </div>
                    @endforeach
                </div>
            @else
                <p class="text-sm text-gray-500 dark:text-gray-400">Nenhum componente cadastrado</p>
            @endif
        @else
            <p class="text-sm text-gray-500 dark:text-gray-400">Produto não usa composição</p>
        @endif
    </div>

    <div class="rounded-lg border border-gray-200 bg-white p-6 shadow-theme-sm dark:border-gray-800 dark:bg-gray-800">
        <h2 class="mb-4 text-lg font-semibold text-gray-800 dark:text-white/90">Fornecedores</h2>
        @forelse($produto->fornecedores as $fornecedor)
            <div class="mb-3 rounded-lg border border-gray-200 p-3 dark:border-gray-700">
                <p class="text-sm font-medium text-gray-800 dark:text-white/90">{{ $fornecedor->fornecedor?->nome ?: 'Fornecedor' }}</p>
                <p class="text-xs text-gray-500 dark:text-gray-400">
                    Código: {{ $fornecedor->codigo_fornecedor ?: '-' }}
                    | Descrição: {{ $fornecedor->descricao ?: '-' }}
                </p>
                <p class="text-xs text-gray-500 dark:text-gray-400">
                    Preço compra: {{ $fornecedor->preco_compra !== null ? format_br_decimal($fornecedor->preco_compra) : '-' }}
                    | Preço custo: {{ $fornecedor->preco_custo !== null ? format_br_decimal($fornecedor->preco_custo) : '-' }}
                    | Garantia: {{ $fornecedor->garantia_meses ?: '-' }} meses
                </p>
            </div>
        @empty
            <p class="text-sm text-gray-500 dark:text-gray-400">Nenhum fornecedor cadastrado</p>
        @endforelse
    </div>
</div>

@if(function_exists('do_action'))
<?php do_action('admin.produtos.show.extra', $produto); ?>
@endif
@endsection
