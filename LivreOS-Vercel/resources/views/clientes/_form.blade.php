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

@php
    $isEdit = isset($cliente) && $cliente->exists;
    $cliente = $isEdit
        ? $cliente
        : new \App\Models\Cliente([
            'grupo_economico_id' => request()->query('grupo_economico_id'),
            'tipo_unidade' => request()->query('tipo_unidade'),
            'matriz_id' => request()->query('matriz_id'),
            'tipo_pessoa' => request()->query('tipo_pessoa'),
        ]);
    $enderecos = old('enderecos', $cliente->enderecos->toArray() ?: [['principal' => true]]);
    $contatos = old('contatos', $cliente->contatos->toArray() ?: []);
    $socios = old('socios', $cliente->socios->toArray() ?: []);
    $categorias = $categorias ?? \App\Models\CategoriaDocumento::where('ativo', true)->orderBy('nome')->get();
    $vendedores = $vendedores ?? \App\Models\User::orderBy('name')->get();
    $rawTipoPessoa = old('tipo_pessoa', $cliente->tipo_pessoa);
    $__tipoClienteForm = ($rawTipoPessoa !== null && $rawTipoPessoa !== '')
        ? strtoupper(trim((string) $rawTipoPessoa))
        : null;
    // Fornecedor/cadastro automático: CNPJ preenchido sem CPF mas tipo_pessoa nulo → exibir e enviar como PJ.
    if ($isEdit && ($__tipoClienteForm === null || $__tipoClienteForm === '')
        && filled($cliente->cnpj)
        && blank($cliente->cpf)) {
        $__tipoClienteForm = 'J';
    }
    // Blocos PF/PJ/estrangeiro no HTML; o JS deve usar o mesmo tipo (select desabilitado pode reportar value vazio).
    $__displayCamposPf = ($__tipoClienteForm === 'F') ? 'block' : 'none';
    $__displayCamposPj = ($__tipoClienteForm === 'J') ? 'block' : 'none';
    $__displayCamposEstrangeiro = ($__tipoClienteForm === 'E') ? 'block' : 'none';
@endphp

<form action="{{ $isEdit ? route('clientes.update', $cliente) : route('clientes.store') }}" method="POST" enctype="multipart/form-data" id="clienteForm">
    @csrf
    @if($isEdit)
        @method('PUT')
    @endif

    <!-- Seção 1: Dados Básicos -->
    <div class="mb-6 rounded-lg border border-gray-200 bg-white p-6 shadow-theme-sm dark:border-gray-800 dark:bg-gray-800">
        <h2 class="mb-4 text-lg font-semibold text-gray-800 dark:text-white/90">1. Dados Básicos</h2>
        
        <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
            <div>
                <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300">Tipo de Pessoa <span class="text-error-500">*</span></label>
                <select name="tipo_pessoa" id="tipo_pessoa" data-locked="{{ $cliente->bloqueado_alteracao_tipo ? '1' : '0' }}" {{ $cliente->bloqueado_alteracao_tipo ? '' : 'required' }} class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm text-gray-800 focus:border-brand-500 focus:ring-brand-500 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90" {{ $cliente->bloqueado_alteracao_tipo ? 'disabled' : '' }}>
                    <option value="">Selecione...</option>
                    <option value="F" {{ $__tipoClienteForm === 'F' ? 'selected' : '' }}>Pessoa Física</option>
                    <option value="J" {{ $__tipoClienteForm === 'J' ? 'selected' : '' }}>Pessoa Jurídica</option>
                    <option value="E" {{ $__tipoClienteForm === 'E' ? 'selected' : '' }}>Estrangeira</option>
                </select>
                @if($cliente->bloqueado_alteracao_tipo)
                <input type="hidden" name="tipo_pessoa" value="{{ $__tipoClienteForm }}">
                @endif
                @error('tipo_pessoa')<p class="mt-1 text-sm text-error-600 dark:text-error-400">{{ $message }}</p>@enderror
            </div>

            <div>
                <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300">País de Origem <span class="text-error-500">*</span></label>
                <input type="text" name="pais_origem" value="{{ old('pais_origem', $cliente->pais_origem ?: 'Brasil') }}" required class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm text-gray-800 focus:border-brand-500 focus:ring-brand-500 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90">
                @error('pais_origem')<p class="mt-1 text-sm text-error-600 dark:text-error-400">{{ $message }}</p>@enderror
            </div>

            <div>
                <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300">Nome do Cliente <span class="text-error-500">*</span></label>
                <input type="text" name="nome" value="{{ old('nome', $cliente->nome) }}" required maxlength="100" class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm text-gray-800 focus:border-brand-500 focus:ring-brand-500 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90">
                @error('nome')<p class="mt-1 text-sm text-error-600 dark:text-error-400">{{ $message }}</p>@enderror
            </div>

            <div>
                <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300">Vendedor (Interno)</label>
                <select name="vendedor_id" class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm text-gray-800 focus:border-brand-500 focus:ring-brand-500 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90">
                    <option value="">Selecione...</option>
                    @foreach($vendedores as $vendedor)
                    <option value="{{ $vendedor->id }}" {{ old('vendedor_id', $cliente->vendedor_id) == $vendedor->id ? 'selected' : '' }}>{{ $vendedor->name }}</option>
                    @endforeach
                </select>
                @error('vendedor_id')<p class="mt-1 text-sm text-error-600 dark:text-error-400">{{ $message }}</p>@enderror
            </div>

            <div id="cliente_bloco_grupo_unidade" data-cliente-pj-only class="md:col-span-2 grid grid-cols-1 gap-4 md:grid-cols-2 {{ $__tipoClienteForm === 'J' ? '' : 'hidden' }}">
                <div>
                    <div class="mb-1.5 flex items-center gap-2">
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Grupo Econômico</label>
                        <button type="button" data-help-toggle="grupo-economico-help" class="inline-flex h-6 w-6 items-center justify-center rounded-full border border-gray-300 text-xs text-gray-600 hover:bg-gray-50 dark:border-gray-700 dark:text-gray-300" aria-label="Ajuda sobre grupo econômico">?</button>
                    </div>
                    <select name="grupo_economico_id" id="grupo_economico_id" class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm text-gray-800 focus:border-brand-500 focus:ring-brand-500 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90">
                        <option value="">Selecione...</option>
                        @foreach($gruposEconomicos ?? [] as $grupo)
                        <option value="{{ $grupo->id }}" {{ old('grupo_economico_id', $cliente->grupo_economico_id) == $grupo->id ? 'selected' : '' }}>{{ $grupo->nome }}</option>
                        @endforeach
                    </select>
                    @error('grupo_economico_id')<p class="mt-1 text-sm text-error-600 dark:text-error-400">{{ $message }}</p>@enderror
                </div>

                <div>
                    <div class="mb-1.5 flex items-center gap-2">
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Tipo de Unidade</label>
                        <button type="button" data-help-toggle="tipo-unidade-help" class="inline-flex h-6 w-6 items-center justify-center rounded-full border border-gray-300 text-xs text-gray-600 hover:bg-gray-50 dark:border-gray-700 dark:text-gray-300" aria-label="Ajuda sobre tipo de unidade">?</button>
                    </div>
                    <select name="tipo_unidade" id="tipo_unidade" class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm text-gray-800 focus:border-brand-500 focus:ring-brand-500 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90">
                        <option value="">Selecione...</option>
                        <option value="matriz" {{ old('tipo_unidade', $cliente->tipo_unidade) == 'matriz' ? 'selected' : '' }}>Matriz</option>
                        <option value="filial" {{ old('tipo_unidade', $cliente->tipo_unidade) == 'filial' ? 'selected' : '' }}>Filial</option>
                    </select>
                    @error('tipo_unidade')<p class="mt-1 text-sm text-error-600 dark:text-error-400">{{ $message }}</p>@enderror
                </div>

                <div id="matriz_container" class="hidden md:col-span-2">
                    <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300">Matriz</label>
                    <select name="matriz_id" id="matriz_id" class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm text-gray-800 focus:border-brand-500 focus:ring-brand-500 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90">
                        <option value="" data-default-text="Selecione...">Selecione...</option>
                        @foreach($matrizes ?? [] as $matriz)
                        <option value="{{ $matriz->id }}" data-grupo="{{ $matriz->grupo_economico_id }}" {{ old('matriz_id', $cliente->matriz_id) == $matriz->id ? 'selected' : '' }}>{{ $matriz->nome }}</option>
                        @endforeach
                    </select>
                    @error('matriz_id')<p class="mt-1 text-sm text-error-600 dark:text-error-400">{{ $message }}</p>@enderror
                </div>

                <div id="grupo-economico-help" class="hidden md:col-span-2 rounded-lg border border-brand-100 bg-brand-50 p-4 text-sm text-gray-700 dark:border-brand-900/40 dark:bg-gray-900 dark:text-gray-300">
                    <p>Use o Grupo Econômico para vincular matriz e filiais. Primeiro crie o grupo no menu, depois escolha o tipo de unidade.</p>
                    <p class="mt-2">Para filiais: selecione o grupo, marque "Filial" e escolha a matriz do mesmo grupo. O sistema força PJ.</p>
                </div>
                <div id="tipo-unidade-help" class="hidden md:col-span-2 rounded-lg border border-brand-100 bg-brand-50 p-4 text-sm text-gray-700 dark:border-brand-900/40 dark:bg-gray-900 dark:text-gray-300">
                    <p>Matriz é a unidade principal da empresa. Filial é uma unidade vinculada com CNPJ próprio.</p>
                    <p class="mt-2">Para cadastrar: selecione o grupo econômico, marque Matriz ou Filial e preencha os dados fiscais da unidade.</p>
                </div>
            </div>

            <!-- Campos dinâmicos baseados no tipo de pessoa -->
            <div id="campos_pf" style="display: {{ $__displayCamposPf }};">
                <div>
                    <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300">CPF</label>
                    <input type="text" name="cpf" id="cpf" value="{{ old('cpf', $cliente->cpf) }}" placeholder="000.000.000-00" class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm text-gray-800 focus:border-brand-500 focus:ring-brand-500 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90">
                    @error('cpf')<p class="mt-1 text-sm text-error-600 dark:text-error-400">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300">RG</label>
                    <input type="text" name="rg" value="{{ old('rg', $cliente->rg) }}" class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm text-gray-800 focus:border-brand-500 focus:ring-brand-500 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90">
                </div>
                <div>
                    <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300">Data de Nascimento</label>
                    <input type="date" name="data_nascimento" value="{{ old('data_nascimento', $cliente->data_nascimento?->format('Y-m-d')) }}" class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm text-gray-800 focus:border-brand-500 focus:ring-brand-500 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90">
                </div>
                <div>
                    <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300">Sexo</label>
                    <select name="sexo" class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm text-gray-800 focus:border-brand-500 focus:ring-brand-500 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90">
                        <option value="">Selecione...</option>
                        <option value="M" {{ old('sexo', $cliente->sexo) == 'M' ? 'selected' : '' }}>Masculino</option>
                        <option value="F" {{ old('sexo', $cliente->sexo) == 'F' ? 'selected' : '' }}>Feminino</option>
                    </select>
                </div>
            </div>

            <div id="campos_pj" style="display: {{ $__displayCamposPj }};">
                <div>
                    <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300">CNPJ</label>
                    <div class="flex gap-2">
                        <input type="text" name="cnpj" id="cnpj" value="{{ old('cnpj', $cliente->cnpj) }}" placeholder="00.000.000/0000-00" class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm text-gray-800 focus:border-brand-500 focus:ring-brand-500 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90">
                        <button type="button" id="btnBuscarCnpj" class="whitespace-nowrap rounded-lg border border-gray-300 bg-white px-4 py-2.5 text-sm font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-300">Buscar CNPJ</button>
                    </div>
                    @error('cnpj')<p class="mt-1 text-sm text-error-600 dark:text-error-400">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300">Razão Social</label>
                    <input type="text" name="razao_social" value="{{ old('razao_social', $cliente->razao_social) }}" class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm text-gray-800 focus:border-brand-500 focus:ring-brand-500 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90">
                    @error('razao_social')<p class="mt-1 text-sm text-error-600 dark:text-error-400">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300">Natureza Jurídica</label>
                    <input type="text" name="natureza_juridica" value="{{ old('natureza_juridica', $cliente->natureza_juridica) }}" class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm text-gray-800 focus:border-brand-500 focus:ring-brand-500 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90">
                </div>
                <div>
                    <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300">Porte da Empresa</label>
                    <input type="text" name="porte_empresa" value="{{ old('porte_empresa', $cliente->porte_empresa) }}" class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm text-gray-800 focus:border-brand-500 focus:ring-brand-500 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90">
                </div>
                <div>
                    <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300">Capital Social</label>
                    <input type="text" name="capital_social" value="{{ old('capital_social', $cliente->capital_social) }}" class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm text-gray-800 focus:border-brand-500 focus:ring-brand-500 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90">
                </div>
                <div>
                    <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300">Data de Início de Atividade</label>
                    <input type="date" name="data_inicio_atividade" value="{{ old('data_inicio_atividade', $cliente->data_inicio_atividade?->format('Y-m-d')) }}" class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm text-gray-800 focus:border-brand-500 focus:ring-brand-500 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90">
                </div>
                <div>
                    <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300">Inscrição Estadual</label>
                    <input type="text" name="inscricao_estadual" id="inscricao_estadual" value="{{ old('inscricao_estadual', $cliente->inscricao_estadual) }}" placeholder="Isento" class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm text-gray-800 focus:border-brand-500 focus:ring-brand-500 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90">
                </div>
                <div>
                    <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300">UF da IE</label>
                    <select name="uf_ie" id="uf_ie" class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm text-gray-800 focus:border-brand-500 focus:ring-brand-500 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90">
                        <option value="">Selecione...</option>
                        @foreach(['AC','AL','AP','AM','BA','CE','DF','ES','GO','MA','MT','MS','MG','PA','PB','PR','PE','PI','RJ','RN','RS','RO','RR','SC','SP','SE','TO'] as $uf)
                        <option value="{{ $uf }}" {{ old('uf_ie', $cliente->uf_ie) == $uf ? 'selected' : '' }}>{{ $uf }}</option>
                        @endforeach
                    </select>
                    @error('uf_ie')<p class="mt-1 text-sm text-error-600 dark:text-error-400">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300">Inscrição Municipal</label>
                    <input type="text" name="inscricao_municipal" value="{{ old('inscricao_municipal', $cliente->inscricao_municipal) }}" class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm text-gray-800 focus:border-brand-500 focus:ring-brand-500 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90">
                </div>
                <div class="flex flex-wrap items-center gap-4">
                    <label class="flex items-center gap-2">
                        <input type="checkbox" name="condominio" value="1" {{ old('condominio', $cliente->condominio) ? 'checked' : '' }} class="rounded border-gray-300 text-brand-500 focus:ring-brand-500">
                        <span class="text-sm text-gray-700 dark:text-gray-300">Condomínio</span>
                    </label>
                    <label class="flex items-center gap-2">
                        <input type="checkbox" name="optante_simples_nacional" value="1" {{ old('optante_simples_nacional', $cliente->optante_simples_nacional) ? 'checked' : '' }} class="rounded border-gray-300 text-brand-500 focus:ring-brand-500">
                        <span class="text-sm text-gray-700 dark:text-gray-300">Optante Simples Nacional</span>
                    </label>
                    <label class="flex items-center gap-2">
                        <input type="checkbox" name="ignorar_im_nfse" value="1" {{ old('ignorar_im_nfse', $cliente->ignorar_im_nfse) ? 'checked' : '' }} class="rounded border-gray-300 text-brand-500 focus:ring-brand-500">
                        <span class="text-sm text-gray-700 dark:text-gray-300">Ignorar IM na NFS-e</span>
                    </label>
                </div>
            </div>

            <div id="campos_estrangeiro" style="display: {{ $__displayCamposEstrangeiro }};">
                <div>
                    <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300">Documento</label>
                    <input type="text" name="documento_estrangeiro" value="{{ old('documento_estrangeiro', $cliente->documento_estrangeiro) }}" maxlength="30" class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm text-gray-800 focus:border-brand-500 focus:ring-brand-500 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90">
                    @error('documento_estrangeiro')<p class="mt-1 text-sm text-error-600 dark:text-error-400">{{ $message }}</p>@enderror
                </div>
            </div>

            <div data-cliente-pj-only class="{{ $__tipoClienteForm === 'J' ? '' : 'hidden' }}">
                <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300">Ramo de Atividade</label>
                <input type="text" name="ramo_atividade" value="{{ old('ramo_atividade', $cliente->ramo_atividade) }}" class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm text-gray-800 focus:border-brand-500 focus:ring-brand-500 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90">
            </div>

            <div>
                <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300">Limite de Crédito</label>
                <input type="text" name="limite_credito" id="limite_credito" value="{{ old('limite_credito') !== null && old('limite_credito') !== '' ? number_format((float) str_replace(',', '.', old('limite_credito')), 2, ',', '.') : ($cliente->limite_credito !== null ? number_format((float) $cliente->limite_credito, 2, ',', '.') : '') }}" placeholder="Ex: 1.500,00" inputmode="decimal" autocomplete="off" class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm text-gray-800 focus:border-brand-500 focus:ring-brand-500 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90">
            </div>

            <div>
                <label class="flex items-center gap-2">
                    <input type="checkbox" name="produtor_rural" value="1" {{ old('produtor_rural', $cliente->produtor_rural) ? 'checked' : '' }} class="rounded border-gray-300 text-brand-500 focus:ring-brand-500">
                    <span class="text-sm text-gray-700 dark:text-gray-300">Produtor Rural</span>
                </label>
            </div>

            <div>
                <label class="flex items-center gap-2">
                    <input type="checkbox" name="bloqueado_vendas" value="1" {{ old('bloqueado_vendas', $cliente->bloqueado_vendas) ? 'checked' : '' }} class="rounded border-gray-300 text-brand-500 focus:ring-brand-500">
                    <span class="text-sm text-gray-700 dark:text-gray-300">Bloqueado para Vendas</span>
                </label>
            </div>
        </div>
    </div>

    <!-- Seção: Sócios (Pessoa Jurídica) -->
    <div id="secao_socios" class="mb-6 rounded-lg border border-gray-200 bg-white p-6 shadow-theme-sm dark:border-gray-800 dark:bg-gray-800 {{ $__tipoClienteForm === 'J' ? '' : 'hidden' }}">
        <div class="mb-4 flex items-center justify-between">
            <h2 class="text-lg font-semibold text-gray-800 dark:text-white/90">Sócios</h2>
            <button type="button" onclick="adicionarSocio()" class="rounded-lg bg-brand-500 px-3 py-1.5 text-sm font-medium text-white hover:bg-brand-600">+ Adicionar Sócio</button>
        </div>
        <div id="socios_container">
            @foreach($socios as $index => $socio)
            <div class="mb-4 rounded-lg border border-gray-200 p-4 dark:border-gray-700" data-socio-index="{{ $index }}">
                <div class="mb-3 flex items-center justify-between">
                    <h3 class="font-medium text-gray-700 dark:text-gray-300">Sócio {{ $index + 1 }}</h3>
                    <button type="button" onclick="removerSocio(this)" class="text-error-500 hover:text-error-700">Remover</button>
                </div>
                <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                    <div>
                        <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300">Nome Completo do Sócio</label>
                        <input type="text" name="socios[{{ $index }}][nome_completo]" value="{{ old("socios.{$index}.nome_completo", $socio['nome_completo'] ?? '') }}" class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm text-gray-800 focus:border-brand-500 focus:ring-brand-500 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90">
                    </div>
                    <div>
                        <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300">CPF</label>
                        <input type="text" name="socios[{{ $index }}][cpf]" value="{{ old("socios.{$index}.cpf", $socio['cpf'] ?? '') }}" placeholder="000.000.000-00" data-socio-cpf class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm text-gray-800 focus:border-brand-500 focus:ring-brand-500 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90">
                    </div>
                    <div>
                        <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300">RG e Órgão Emissor</label>
                        <input type="text" name="socios[{{ $index }}][rg_orgao_emissor]" value="{{ old("socios.{$index}.rg_orgao_emissor", $socio['rg_orgao_emissor'] ?? '') }}" placeholder="Ex: SSP/SP" class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm text-gray-800 focus:border-brand-500 focus:ring-brand-500 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90">
                    </div>
                    <div>
                        <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300">Nacionalidade</label>
                        <input type="text" name="socios[{{ $index }}][nacionalidade]" value="{{ old("socios.{$index}.nacionalidade", $socio['nacionalidade'] ?? '') }}" placeholder="Ex: Brasileiro" class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm text-gray-800 focus:border-brand-500 focus:ring-brand-500 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90">
                    </div>
                    <div>
                        <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300">Estado Civil</label>
                        <select name="socios[{{ $index }}][estado_civil]" class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm text-gray-800 focus:border-brand-500 focus:ring-brand-500 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90">
                            <option value="">Selecione...</option>
                            @foreach(['Solteiro', 'Casado', 'Divorciado', 'Viúvo', 'Separado', 'União Estável'] as $estadoCivil)
                            <option value="{{ $estadoCivil }}" {{ old("socios.{$index}.estado_civil", $socio['estado_civil'] ?? '') == $estadoCivil ? 'selected' : '' }}>{{ $estadoCivil }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300">Profissão</label>
                        <input type="text" name="socios[{{ $index }}][profissao]" value="{{ old("socios.{$index}.profissao", $socio['profissao'] ?? '') }}" class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm text-gray-800 focus:border-brand-500 focus:ring-brand-500 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90">
                    </div>
                </div>

                <div class="mt-4 grid grid-cols-1 gap-4 md:grid-cols-2">
                    <div>
                        <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300">CEP</label>
                        <input type="text" name="socios[{{ $index }}][cep]" value="{{ old("socios.{$index}.cep", $socio['cep'] ?? '') }}" data-socio-cep data-socio-field="cep" class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm text-gray-800 focus:border-brand-500 focus:ring-brand-500 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90">
                    </div>
                    <div>
                        <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300">Rua</label>
                        <input type="text" name="socios[{{ $index }}][logradouro]" value="{{ old("socios.{$index}.logradouro", $socio['logradouro'] ?? '') }}" data-socio-field="logradouro" class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm text-gray-800 focus:border-brand-500 focus:ring-brand-500 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90">
                    </div>
                    <div>
                        <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300">Nº</label>
                        <input type="text" name="socios[{{ $index }}][numero]" value="{{ old("socios.{$index}.numero", $socio['numero'] ?? '') }}" data-socio-field="numero" class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm text-gray-800 focus:border-brand-500 focus:ring-brand-500 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90">
                    </div>
                    <div>
                        <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300">Bairro</label>
                        <input type="text" name="socios[{{ $index }}][bairro]" value="{{ old("socios.{$index}.bairro", $socio['bairro'] ?? '') }}" data-socio-field="bairro" class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm text-gray-800 focus:border-brand-500 focus:ring-brand-500 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90">
                    </div>
                    <div>
                        <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300">UF</label>
                        <select name="socios[{{ $index }}][uf]" data-socio-field="uf" class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm text-gray-800 focus:border-brand-500 focus:ring-brand-500 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90">
                            <option value="">Selecione...</option>
                            @foreach(['AC','AL','AP','AM','BA','CE','DF','ES','GO','MA','MT','MS','MG','PA','PB','PR','PE','PI','RJ','RN','RS','RO','RR','SC','SP','SE','TO'] as $uf)
                            <option value="{{ $uf }}" {{ old("socios.{$index}.uf", $socio['uf'] ?? '') == $uf ? 'selected' : '' }}>{{ $uf }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300">Cidade</label>
                        <input type="text" name="socios[{{ $index }}][cidade]" value="{{ old("socios.{$index}.cidade", $socio['cidade'] ?? '') }}" data-socio-field="cidade" class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm text-gray-800 focus:border-brand-500 focus:ring-brand-500 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90">
                    </div>
                </div>

                <div class="mt-4 grid grid-cols-1 gap-4 md:grid-cols-3">
                    <div class="flex items-center gap-2">
                        <input type="checkbox" name="socios[{{ $index }}][socio_administrador]" value="1" {{ old("socios.{$index}.socio_administrador", $socio['socio_administrador'] ?? false) ? 'checked' : '' }} class="rounded border-gray-300 text-brand-500 focus:ring-brand-500">
                        <label class="text-sm text-gray-700 dark:text-gray-300">Sócio Administrador</label>
                    </div>
                    <div>
                        <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300">Assina</label>
                        <select name="socios[{{ $index }}][tipo_assinatura]" class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm text-gray-800 focus:border-brand-500 focus:ring-brand-500 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90">
                            <option value="">Selecione...</option>
                            <option value="isoladamente" {{ old("socios.{$index}.tipo_assinatura", $socio['tipo_assinatura'] ?? '') == 'isoladamente' ? 'selected' : '' }}>Isoladamente</option>
                            <option value="conjunto" {{ old("socios.{$index}.tipo_assinatura", $socio['tipo_assinatura'] ?? '') == 'conjunto' ? 'selected' : '' }}>Em Conjunto</option>
                        </select>
                    </div>
                    <div>
                        <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300">Percentual de Participação (%)</label>
                        <input type="number" step="0.01" min="0" max="100" name="socios[{{ $index }}][percentual_participacao]" value="{{ old("socios.{$index}.percentual_participacao", $socio['percentual_participacao'] ?? '') }}" class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm text-gray-800 focus:border-brand-500 focus:ring-brand-500 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90">
                    </div>
                </div>

                <div class="mt-4 grid grid-cols-1 gap-4 md:grid-cols-3">
                    <div>
                        <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300">Score de Crédito (PF)</label>
                        <input type="number" name="socios[{{ $index }}][score_credito]" value="{{ old("socios.{$index}.score_credito", $socio['score_credito'] ?? '') }}" class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm text-gray-800 focus:border-brand-500 focus:ring-brand-500 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90">
                    </div>
                    <div class="flex items-center gap-2">
                        <input type="checkbox" name="socios[{{ $index }}][possui_restricoes]" value="1" {{ old("socios.{$index}.possui_restricoes", $socio['possui_restricoes'] ?? false) ? 'checked' : '' }} class="rounded border-gray-300 text-brand-500 focus:ring-brand-500">
                        <label class="text-sm text-gray-700 dark:text-gray-300">Possui Restrições</label>
                    </div>
                    <div>
                        <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300">Patrimônio Estimado</label>
                        <input type="text" name="socios[{{ $index }}][patrimonio_estimado]" value="{{ old("socios.{$index}.patrimonio_estimado", $socio['patrimonio_estimado'] ?? '') }}" class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm text-gray-800 focus:border-brand-500 focus:ring-brand-500 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90">
                    </div>
                </div>
            </div>
            @endforeach
        </div>
    </div>

    <!-- Seção 2: Endereços -->
    <div class="mb-6 rounded-lg border border-gray-200 bg-white p-6 shadow-theme-sm dark:border-gray-800 dark:bg-gray-800">
        <div class="mb-4 flex items-center justify-between">
            <h2 class="text-lg font-semibold text-gray-800 dark:text-white/90">2. Endereços</h2>
            <button type="button" onclick="adicionarEndereco()" class="rounded-lg bg-brand-500 px-3 py-1.5 text-sm font-medium text-white hover:bg-brand-600">+ Adicionar</button>
        </div>
        <div id="enderecos_container">
            @foreach($enderecos as $index => $endereco)
            @include('clientes._endereco', ['index' => $index, 'endereco' => $endereco])
            @endforeach
        </div>
        @error('enderecos')<p class="mt-2 text-sm text-error-600 dark:text-error-400">{{ $message }}</p>@enderror
    </div>

    <!-- Seção 3: Contatos -->
    <div class="mb-6 rounded-lg border border-gray-200 bg-white p-6 shadow-theme-sm dark:border-gray-800 dark:bg-gray-800">
        <div class="mb-4 flex items-center justify-between">
            <h2 class="text-lg font-semibold text-gray-800 dark:text-white/90">3. Contatos</h2>
            <button type="button" onclick="adicionarContato()" class="rounded-lg bg-brand-500 px-3 py-1.5 text-sm font-medium text-white hover:bg-brand-600">+ Adicionar</button>
        </div>
        <div id="contatos_container">
            @foreach($contatos as $index => $contato)
            @include('clientes._contato', ['index' => $index, 'contato' => $contato])
            @endforeach
        </div>
    </div>

    <!-- Seção 5: Faturamento -->
    <div class="mb-6 rounded-lg border border-gray-200 bg-white p-6 shadow-theme-sm dark:border-gray-800 dark:bg-gray-800">
        <h2 class="mb-4 text-lg font-semibold text-gray-800 dark:text-white/90">5. Faturamento</h2>
        <div class="grid grid-cols-1 gap-4 md:grid-cols-3">
            <div>
                <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300">Dias de Antecedência</label>
                <input type="number" name="dias_antecedencia_faturamento" value="{{ old('dias_antecedencia_faturamento', $cliente->dias_antecedencia_faturamento) }}" min="1" max="365" class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm text-gray-800 focus:border-brand-500 focus:ring-brand-500 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90">
            </div>
            <div>
                <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300">Dia de Faturamento</label>
                <input type="number" name="dia_faturamento" value="{{ old('dia_faturamento', $cliente->dia_faturamento) }}" min="1" max="31" class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm text-gray-800 focus:border-brand-500 focus:ring-brand-500 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90">
            </div>
            <div>
                <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300">Emissão NFS-e</label>
                <select name="emissao_nfse" class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm text-gray-800 focus:border-brand-500 focus:ring-brand-500 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90">
                    <option value="config_empresa" {{ old('emissao_nfse', $cliente->emissao_nfse) == 'config_empresa' ? 'selected' : '' }}>Configuração da Empresa</option>
                    <option value="faturamento" {{ old('emissao_nfse', $cliente->emissao_nfse) == 'faturamento' ? 'selected' : '' }}>Emitir no Faturamento</option>
                    <option value="quitacao" {{ old('emissao_nfse', $cliente->emissao_nfse) == 'quitacao' ? 'selected' : '' }}>Emitir na Quitação</option>
                </select>
            </div>
        </div>
    </div>

    <!-- Seção 6: Observações -->
    <div class="mb-6 rounded-lg border border-gray-200 bg-white p-6 shadow-theme-sm dark:border-gray-800 dark:bg-gray-800">
        <h2 class="mb-4 text-lg font-semibold text-gray-800 dark:text-white/90">6. Observações</h2>
        <textarea name="observacoes" rows="4" maxlength="2000" class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm text-gray-800 focus:border-brand-500 focus:ring-brand-500 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90">{{ old('observacoes', $cliente->observacoes) }}</textarea>
    </div>

    @if(function_exists('do_action'))
    <?php do_action('admin.clientes.form.portal', $cliente); ?>
    <?php do_action('admin.clientes.form.extra', $cliente ?? null); ?>
    @endif

    <!-- Seção: Documentos -->
    <div class="mb-6 rounded-lg border border-gray-200 bg-white p-6 shadow-theme-sm dark:border-gray-800 dark:bg-gray-800">
        <div class="mb-4 flex items-center justify-between">
            <h2 class="text-lg font-semibold text-gray-800 dark:text-white/90">Documentos</h2>
            <button type="button" onclick="adicionarDocumento()" class="rounded-lg bg-brand-500 px-3 py-1.5 text-sm font-medium text-white hover:bg-brand-600">+ Adicionar Documento</button>
        </div>
        <div id="documentos_container">
            <!-- Novos documentos serão adicionados aqui via JavaScript -->
        </div>
        @if($isEdit && $cliente->documentos->count() > 0)
        <div class="mt-6 space-y-2">
            <h3 class="mb-3 text-sm font-medium text-gray-700 dark:text-gray-300">Documentos Existentes</h3>
            @foreach($cliente->documentos as $doc)
            <div class="flex items-center justify-between rounded-lg border border-gray-200 p-3 dark:border-gray-700">
                <div class="flex-1">
                    <p class="text-sm font-medium text-gray-800 dark:text-white/90">{{ $doc->nome_arquivo }}</p>
                    <div class="mt-1 flex flex-wrap gap-2 text-xs text-gray-500 dark:text-gray-400">
                        @if($doc->categoria)
                        <span class="rounded-full bg-brand-100 px-2 py-0.5 text-brand-700 dark:bg-brand-500/20 dark:text-brand-400">{{ $doc->categoria }}</span>
                        @endif
                        @if($doc->tags)
                        @foreach($doc->tags_array as $tag)
                        <span class="rounded-full bg-gray-100 px-2 py-0.5 dark:bg-gray-700">{{ trim($tag) }}</span>
                        @endforeach
                        @endif
                        <span>{{ $doc->tamanho_formatado }}</span>
                    </div>
                    @if($doc->descricao)
                    <p class="mt-1 text-xs text-gray-600 dark:text-gray-400">{{ $doc->descricao }}</p>
                    @endif
                    <div class="mt-3 grid grid-cols-1 gap-3 md:grid-cols-3">
                        <div>
                            <label class="mb-1 block text-xs font-medium text-gray-700 dark:text-gray-300">Categoria</label>
                            <select name="documentos_existentes[{{ $doc->id }}][categoria]" class="w-full rounded-lg border border-gray-300 px-3 py-2 text-xs text-gray-800 focus:border-brand-500 focus:ring-brand-500 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90">
                                <option value="">Selecione...</option>
                                @foreach($categorias as $categoria)
                                    <option value="{{ $categoria->slug }}" {{ old("documentos_existentes.$doc->id.categoria", $doc->categoria) == $categoria->slug ? 'selected' : '' }}>
                                        {{ $categoria->nome }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label class="mb-1 block text-xs font-medium text-gray-700 dark:text-gray-300">Tags</label>
                            <input type="text" name="documentos_existentes[{{ $doc->id }}][tags]" value="{{ old("documentos_existentes.$doc->id.tags", $doc->tags) }}" placeholder="Ex: importante, contrato, fiscal" class="w-full rounded-lg border border-gray-300 px-3 py-2 text-xs text-gray-800 focus:border-brand-500 focus:ring-brand-500 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90">
                        </div>
                        <div>
                            <label class="mb-1 block text-xs font-medium text-gray-700 dark:text-gray-300">Descrição</label>
                            <textarea name="documentos_existentes[{{ $doc->id }}][descricao]" rows="2" placeholder="Descrição do documento..." class="w-full rounded-lg border border-gray-300 px-3 py-2 text-xs text-gray-800 focus:border-brand-500 focus:ring-brand-500 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90">{{ old("documentos_existentes.$doc->id.descricao", $doc->descricao) }}</textarea>
                        </div>
                    </div>
                </div>
                <div class="flex gap-2">
                    <a href="{{ route('clientes.documentos.download', $doc) }}" class="rounded-lg border border-gray-300 bg-white px-3 py-1.5 text-sm font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-300">Download</a>
                    <button type="submit" form="delete-documento-{{ $doc->id }}" onclick="return confirm('Deletar este documento?');" class="rounded-lg border border-error-300 bg-white px-3 py-1.5 text-sm font-medium text-error-700 hover:bg-error-50 dark:border-error-700 dark:bg-gray-800 dark:text-error-400">Deletar</button>
                </div>
            </div>
            @endforeach
        </div>
        @endif
    </div>

    <div class="flex gap-3">
        <button type="submit" id="btnSalvar" class="rounded-lg bg-brand-500 px-5 py-2.5 text-sm font-medium text-white hover:bg-brand-600">Salvar Cliente</button>
        <a href="{{ route('clientes.index') }}" class="rounded-lg border border-gray-300 bg-white px-5 py-2.5 text-sm font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-300 dark:hover:bg-gray-700">Cancelar</a>
    </div>
</form>

@if($isEdit && $cliente->documentos->count() > 0)
    @foreach($cliente->documentos as $doc)
        <form id="delete-documento-{{ $doc->id }}" action="{{ route('clientes.documentos.destroy', $doc) }}" method="POST" class="hidden">
            @csrf
            @method('DELETE')
        </form>
    @endforeach
@endif

<script>
let enderecoIndex = {{ count($enderecos) }};
let contatoIndex = {{ count($contatos) }};
let socioIndex = {{ count($socios) }};

function normalizarCep(cep) {
    return (cep || '').replace(/\D/g, '').slice(0, 8);
}

async function buscarEnderecoPorCep(cep) {
    const url = `https://viacep.com.br/ws/${cep}/json/`;
    const response = await fetch(url);
    if (!response.ok) {
        return null;
    }
    const data = await response.json();
    if (data.erro) {
        return null;
    }
    return data;
}

function getEnderecoField(container, field) {
    return container.querySelector(`[data-endereco-field="${field}"]`);
}

const ibgeCidadesCacheClientes = {};
async function carregarCidadesPorUfClientes(uf) {
    const ufUpper = String(uf || '').trim().toUpperCase();
    if (ufUpper.length !== 2) return [];
    if (ibgeCidadesCacheClientes[ufUpper]) return ibgeCidadesCacheClientes[ufUpper];
    const resp = await fetch(`https://servicodados.ibge.gov.br/api/v1/localidades/estados/${ufUpper}/municipios`);
    if (!resp.ok) return [];
    const data = await resp.json();
    const cidades = Array.isArray(data) ? data.map((c) => String(c?.nome || '').trim()).filter(Boolean) : [];
    ibgeCidadesCacheClientes[ufUpper] = cidades;
    return cidades;
}

async function popularSelectCidadeEndereco(container, cidadeSelecionada = '') {
    const ufInput = getEnderecoField(container, 'estado');
    const cidadeInput = getEnderecoField(container, 'cidade');
    if (!ufInput || !cidadeInput) return;

    const uf = String(ufInput.value || '').trim().toUpperCase();
    const cidadeAtual = String(cidadeSelecionada || cidadeInput.dataset.selected || cidadeInput.value || '').trim();
    cidadeInput.innerHTML = '';
    if (uf.length !== 2) {
        cidadeInput.innerHTML = '<option value="">Selecione a UF primeiro...</option>';
        return;
    }
    cidadeInput.innerHTML = '<option value="">Carregando cidades...</option>';
    const cidades = await carregarCidadesPorUfClientes(uf);
    cidadeInput.innerHTML = '<option value="">Selecione...</option>';
    cidades.forEach((cidade) => {
        const opt = document.createElement('option');
        opt.value = cidade;
        opt.textContent = cidade;
        if (cidadeAtual && cidade.toLowerCase() === cidadeAtual.toLowerCase()) {
            opt.selected = true;
        }
        cidadeInput.appendChild(opt);
    });
    if (cidadeAtual && !cidades.some((c) => c.toLowerCase() === cidadeAtual.toLowerCase())) {
        const extra = document.createElement('option');
        extra.value = cidadeAtual;
        extra.textContent = `${cidadeAtual} (fora da lista)`;
        extra.selected = true;
        cidadeInput.appendChild(extra);
    }
}

async function aplicarEnderecoPorCep(container, cepValue) {
    const cep = normalizarCep(cepValue);
    if (cep.length !== 8) {
        return;
    }

    const data = await buscarEnderecoPorCep(cep);
    if (!data) {
        return;
    }

    const logradouro = getEnderecoField(container, 'logradouro');
    const bairro = getEnderecoField(container, 'bairro');
    const cidade = getEnderecoField(container, 'cidade');
    const estado = getEnderecoField(container, 'estado');

    if (logradouro) logradouro.value = data.logradouro || '';
    if (bairro) bairro.value = data.bairro || '';
    if (estado) estado.value = data.uf || '';
    if (cidade) {
        cidade.dataset.selected = data.localidade || '';
        await popularSelectCidadeEndereco(container, data.localidade || '');
    }

    const numero = getEnderecoField(container, 'numero');
    if (numero) {
        numero.focus();
    }
}

function bindCepLookup(container) {
    const cepInput = container.querySelector('[data-cep]');
    const ufInput = getEnderecoField(container, 'estado');
    if (!cepInput) return;

    if (ufInput) {
        ufInput.addEventListener('change', () => {
            const cidade = getEnderecoField(container, 'cidade');
            if (cidade) cidade.dataset.selected = '';
            popularSelectCidadeEndereco(container, '');
        });
        popularSelectCidadeEndereco(container, getEnderecoField(container, 'cidade')?.dataset?.selected || getEnderecoField(container, 'cidade')?.value || '');
    }

    cepInput.addEventListener('blur', () => {
        aplicarEnderecoPorCep(container, cepInput.value);
    });
}

function getSocioField(container, field) {
    return container.querySelector(`[data-socio-field="${field}"]`);
}

async function aplicarEnderecoSocioPorCep(container, cepValue) {
    const cep = normalizarCep(cepValue);
    if (cep.length !== 8) {
        return;
    }

    const data = await buscarEnderecoPorCep(cep);
    if (!data) {
        return;
    }

    const logradouro = getSocioField(container, 'logradouro');
    const bairro = getSocioField(container, 'bairro');
    const cidade = getSocioField(container, 'cidade');
    const uf = getSocioField(container, 'uf');

    if (logradouro) logradouro.value = data.logradouro || '';
    if (bairro) bairro.value = data.bairro || '';
    if (cidade) cidade.value = data.localidade || '';
    if (uf) uf.value = data.uf || '';

    const numero = getSocioField(container, 'numero');
    if (numero) {
        numero.focus();
    }
}

function bindSocioCepLookup(container) {
    const cepInput = container.querySelector('[data-socio-cep]');
    if (!cepInput) return;

    cepInput.addEventListener('blur', () => {
        aplicarEnderecoSocioPorCep(container, cepInput.value);
    });
}

function adicionarEndereco() {
    const container = document.getElementById('enderecos_container');
    const html = `
        <div class="mb-4 rounded-lg border border-gray-200 p-4 dark:border-gray-700" data-index="${enderecoIndex}" data-endereco-block>
            <div class="mb-3 flex items-center justify-between">
                <h3 class="font-medium text-gray-700 dark:text-gray-300">Endereço ${enderecoIndex + 1}</h3>
                <button type="button" onclick="removerEndereco(this)" class="text-error-500 hover:text-error-700">Remover</button>
            </div>
            <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                <div>
                    <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300">CEP</label>
                    <input type="text" name="enderecos[${enderecoIndex}][cep]" data-cep data-endereco-field="cep" class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm text-gray-800 focus:border-brand-500 focus:ring-brand-500 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90">
                </div>
                <div>
                    <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300">Logradouro</label>
                    <input type="text" name="enderecos[${enderecoIndex}][logradouro]" data-endereco-field="logradouro" class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm text-gray-800 focus:border-brand-500 focus:ring-brand-500 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90">
                </div>
                <div>
                    <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300">Número</label>
                    <input type="text" name="enderecos[${enderecoIndex}][numero]" data-endereco-field="numero" class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm text-gray-800 focus:border-brand-500 focus:ring-brand-500 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90">
                </div>
                <div>
                    <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300">Complemento</label>
                    <input type="text" name="enderecos[${enderecoIndex}][complemento]" data-endereco-field="complemento" class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm text-gray-800 focus:border-brand-500 focus:ring-brand-500 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90">
                </div>
                <div>
                    <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300">Bairro</label>
                    <input type="text" name="enderecos[${enderecoIndex}][bairro]" data-endereco-field="bairro" class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm text-gray-800 focus:border-brand-500 focus:ring-brand-500 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90">
                </div>
                <div>
                    <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300">Estado (UF)</label>
                    <select name="enderecos[${enderecoIndex}][estado]" data-endereco-field="estado" class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm text-gray-800 focus:border-brand-500 focus:ring-brand-500 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90">
                        <option value="">Selecione...</option>
                        @foreach(['AC','AL','AP','AM','BA','CE','DF','ES','GO','MA','MT','MS','MG','PA','PB','PR','PE','PI','RJ','RN','RS','RO','RR','SC','SP','SE','TO'] as $uf)
                        <option value="{{ $uf }}">{{ $uf }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300">Cidade</label>
                    <select name="enderecos[${enderecoIndex}][cidade]" data-endereco-field="cidade" class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm text-gray-800 focus:border-brand-500 focus:ring-brand-500 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90">
                        <option value="">Selecione a UF primeiro...</option>
                    </select>
                </div>
                <div class="flex items-center gap-4">
                    <label class="flex items-center gap-2">
                        <input type="checkbox" name="enderecos[${enderecoIndex}][principal]" value="1" class="rounded border-gray-300 text-brand-500 focus:ring-brand-500">
                        <span class="text-sm text-gray-700 dark:text-gray-300">Principal</span>
                    </label>
                    <label class="flex items-center gap-2">
                        <input type="checkbox" name="enderecos[${enderecoIndex}][cobranca]" value="1" class="rounded border-gray-300 text-brand-500 focus:ring-brand-500">
                        <span class="text-sm text-gray-700 dark:text-gray-300">Cobrança</span>
                    </label>
                    <label class="flex items-center gap-2">
                        <input type="checkbox" name="enderecos[${enderecoIndex}][entrega]" value="1" class="rounded border-gray-300 text-brand-500 focus:ring-brand-500">
                        <span class="text-sm text-gray-700 dark:text-gray-300">Entrega</span>
                    </label>
                </div>
            </div>
        </div>
    `;
    container.insertAdjacentHTML('beforeend', html);
    const blocks = container.querySelectorAll('[data-endereco-block]');
    const newBlock = blocks[blocks.length - 1];
    if (newBlock) {
        bindCepLookup(newBlock);
    }
    enderecoIndex++;
}

function removerEndereco(btn) {
    btn.closest('[data-index]').remove();
}

function adicionarContato() {
    const container = document.getElementById('contatos_container');
    const html = `
        <div class="mb-4 rounded-lg border border-gray-200 p-4 dark:border-gray-700" data-index="${contatoIndex}">
            <div class="mb-3 flex items-center justify-between">
                <h3 class="font-medium text-gray-700 dark:text-gray-300">Contato ${contatoIndex + 1}</h3>
                <button type="button" onclick="removerContato(this)" class="text-error-500 hover:text-error-700">Remover</button>
            </div>
            <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                <div>
                    <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300">Nome</label>
                    <input type="text" name="contatos[${contatoIndex}][nome]" class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm text-gray-800 focus:border-brand-500 focus:ring-brand-500 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90">
                </div>
                <div>
                    <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300">Telefone</label>
                    <input type="text" name="contatos[${contatoIndex}][telefone]" class="js-phone-mask w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm text-gray-800 focus:border-brand-500 focus:ring-brand-500 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90">
                    <label class="mt-2 flex items-center gap-2 text-xs text-gray-600 dark:text-gray-400">
                        <input type="checkbox" name="contatos[${contatoIndex}][telefone_whatsapp]" value="1" class="rounded border-gray-300 text-brand-500 focus:ring-brand-500">
                        WhatsApp
                    </label>
                </div>
                <div>
                    <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300">Telefone 2</label>
                    <input type="text" name="contatos[${contatoIndex}][telefone2]" class="js-phone-mask w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm text-gray-800 focus:border-brand-500 focus:ring-brand-500 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90">
                </div>
                <div>
                    <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300">E-mail</label>
                    <input type="email" name="contatos[${contatoIndex}][email]" class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm text-gray-800 focus:border-brand-500 focus:ring-brand-500 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90">
                </div>
                <div>
                    <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300">E-mail 2</label>
                    <input type="email" name="contatos[${contatoIndex}][email2]" class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm text-gray-800 focus:border-brand-500 focus:ring-brand-500 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90">
                </div>
                <div>
                    <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300">Cargo</label>
                    <input type="text" name="contatos[${contatoIndex}][cargo]" class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm text-gray-800 focus:border-brand-500 focus:ring-brand-500 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90">
                </div>
                <div class="flex items-center gap-4">
                    <label class="flex items-center gap-2">
                        <input type="checkbox" name="contatos[${contatoIndex}][principal]" value="1" class="rounded border-gray-300 text-brand-500 focus:ring-brand-500">
                        <span class="text-sm text-gray-700 dark:text-gray-300">Principal</span>
                    </label>
                    <label class="flex items-center gap-2">
                        <input type="checkbox" name="contatos[${contatoIndex}][cobranca]" value="1" class="rounded border-gray-300 text-brand-500 focus:ring-brand-500">
                        <span class="text-sm text-gray-700 dark:text-gray-300">Cobrança</span>
                    </label>
                </div>
            </div>
        </div>
    `;
    container.insertAdjacentHTML('beforeend', html);
    const newBlock = container.querySelector(`[data-index="${contatoIndex}"]`);
    if (newBlock) {
        newBlock.querySelectorAll('.js-phone-mask').forEach(bindPhoneMask);
    }
    contatoIndex++;
}

function removerContato(btn) {
    btn.closest('[data-index]').remove();
}

function formatarCPFInput(raw) {
    let value = (raw || '').replace(/\D/g, '').slice(0, 11);
    if (value.length <= 11) {
        value = value.replace(/(\d{3})(\d)/, '$1.$2');
        value = value.replace(/(\d{3})(\d)/, '$1.$2');
        value = value.replace(/(\d{3})(\d{1,2})$/, '$1-$2');
    }
    return value;
}

function validarCPFNumericoJS(cpf) {
    const clean = (cpf || '').replace(/\D/g, '');
    if (clean.length !== 11) return false;
    if (/^(\d)\1{10}$/.test(clean)) return false;

    for (let t = 9; t < 11; t++) {
        let sum = 0;
        for (let c = 0; c < t; c++) {
            sum += parseInt(clean[c], 10) * ((t + 1) - c);
        }
        const d = ((10 * sum) % 11) % 10;
        if (parseInt(clean[t], 10) !== d) return false;
    }

    return true;
}

function formatarTelefoneInput(value) {
    const digits = (value || '').replace(/\D/g, '').slice(0, 11);
    if (!digits) return '';
    if (digits.length <= 2) return `(${digits}`;
    const ddd = digits.slice(0, 2);
    const rest = digits.slice(2);
    if (rest.length <= 4) return `(${ddd}) ${rest}`;
    if (rest.length <= 8) return `(${ddd}) ${rest.slice(0, 4)}-${rest.slice(4)}`;
    return `(${ddd}) ${rest.slice(0, 5)}-${rest.slice(5, 9)}`;
}

function bindPhoneMask(input) {
    if (!input) return;
    input.addEventListener('input', function (e) {
        e.target.value = formatarTelefoneInput(e.target.value);
    });
}

function bindSocioCpfMask(input) {
    input.addEventListener('input', function(e) {
        e.target.value = formatarCPFInput(e.target.value);
    });

    input.addEventListener('blur', function(e) {
        const value = e.target.value;
        if (!value) {
            e.target.setCustomValidity('');
            return;
        }

        if (!validarCPFNumericoJS(value)) {
            e.target.setCustomValidity('CPF inválido.');
        } else {
            e.target.setCustomValidity('');
        }
    });

    input.addEventListener('invalid', function(e) {
        if (e.target.validity.customError) {
            return;
        }
        e.target.setCustomValidity('');
    });
}

function adicionarSocio() {
    const container = document.getElementById('socios_container');
    const html = `
        <div class="mb-4 rounded-lg border border-gray-200 p-4 dark:border-gray-700" data-socio-index="${socioIndex}">
            <div class="mb-3 flex items-center justify-between">
                <h3 class="font-medium text-gray-700 dark:text-gray-300">Sócio ${socioIndex + 1}</h3>
                <button type="button" onclick="removerSocio(this)" class="text-error-500 hover:text-error-700">Remover</button>
            </div>
            <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                <div>
                    <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300">Nome Completo do Sócio</label>
                    <input type="text" name="socios[${socioIndex}][nome_completo]" class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm text-gray-800 focus:border-brand-500 focus:ring-brand-500 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90">
                </div>
                <div>
                    <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300">CPF</label>
                    <input type="text" name="socios[${socioIndex}][cpf]" placeholder="000.000.000-00" data-socio-cpf class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm text-gray-800 focus:border-brand-500 focus:ring-brand-500 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90">
                </div>
                <div>
                    <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300">RG e Órgão Emissor</label>
                    <input type="text" name="socios[${socioIndex}][rg_orgao_emissor]" placeholder="Ex: SSP/SP" class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm text-gray-800 focus:border-brand-500 focus:ring-brand-500 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90">
                </div>
                <div>
                    <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300">Nacionalidade</label>
                    <input type="text" name="socios[${socioIndex}][nacionalidade]" placeholder="Ex: Brasileiro" class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm text-gray-800 focus:border-brand-500 focus:ring-brand-500 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90">
                </div>
                <div>
                    <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300">Estado Civil</label>
                    <select name="socios[${socioIndex}][estado_civil]" class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm text-gray-800 focus:border-brand-500 focus:ring-brand-500 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90">
                        <option value="">Selecione...</option>
                        <option value="Solteiro">Solteiro</option>
                        <option value="Casado">Casado</option>
                        <option value="Divorciado">Divorciado</option>
                        <option value="Viúvo">Viúvo</option>
                        <option value="Separado">Separado</option>
                        <option value="União Estável">União Estável</option>
                    </select>
                </div>
                <div>
                    <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300">Profissão</label>
                    <input type="text" name="socios[${socioIndex}][profissao]" class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm text-gray-800 focus:border-brand-500 focus:ring-brand-500 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90">
                </div>
            </div>

            <div class="mt-4 grid grid-cols-1 gap-4 md:grid-cols-2">
                <div>
                    <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300">CEP</label>
                    <input type="text" name="socios[${socioIndex}][cep]" data-socio-cep data-socio-field="cep" class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm text-gray-800 focus:border-brand-500 focus:ring-brand-500 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90">
                </div>
                <div>
                    <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300">Rua</label>
                    <input type="text" name="socios[${socioIndex}][logradouro]" data-socio-field="logradouro" class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm text-gray-800 focus:border-brand-500 focus:ring-brand-500 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90">
                </div>
                <div>
                    <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300">Nº</label>
                    <input type="text" name="socios[${socioIndex}][numero]" data-socio-field="numero" class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm text-gray-800 focus:border-brand-500 focus:ring-brand-500 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90">
                </div>
                <div>
                    <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300">Bairro</label>
                    <input type="text" name="socios[${socioIndex}][bairro]" data-socio-field="bairro" class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm text-gray-800 focus:border-brand-500 focus:ring-brand-500 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90">
                </div>
                <div>
                    <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300">UF</label>
                    <select name="socios[${socioIndex}][uf]" data-socio-field="uf" class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm text-gray-800 focus:border-brand-500 focus:ring-brand-500 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90">
                        <option value="">Selecione...</option>
                        @foreach(['AC','AL','AP','AM','BA','CE','DF','ES','GO','MA','MT','MS','MG','PA','PB','PR','PE','PI','RJ','RN','RS','RO','RR','SC','SP','SE','TO'] as $uf)
                        <option value="{{ $uf }}">{{ $uf }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300">Cidade</label>
                    <input type="text" name="socios[${socioIndex}][cidade]" data-socio-field="cidade" class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm text-gray-800 focus:border-brand-500 focus:ring-brand-500 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90">
                </div>
            </div>

            <div class="mt-4 grid grid-cols-1 gap-4 md:grid-cols-3">
                <div class="flex items-center gap-2">
                    <input type="checkbox" name="socios[${socioIndex}][socio_administrador]" value="1" class="rounded border-gray-300 text-brand-500 focus:ring-brand-500">
                    <label class="text-sm text-gray-700 dark:text-gray-300">Sócio Administrador</label>
                </div>
                <div>
                    <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300">Assina</label>
                    <select name="socios[${socioIndex}][tipo_assinatura]" class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm text-gray-800 focus:border-brand-500 focus:ring-brand-500 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90">
                        <option value="">Selecione...</option>
                        <option value="isoladamente">Isoladamente</option>
                        <option value="conjunto">Em Conjunto</option>
                    </select>
                </div>
                <div>
                    <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300">Percentual de Participação (%)</label>
                    <input type="number" step="0.01" min="0" max="100" name="socios[${socioIndex}][percentual_participacao]" class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm text-gray-800 focus:border-brand-500 focus:ring-brand-500 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90">
                </div>
            </div>

            <div class="mt-4 grid grid-cols-1 gap-4 md:grid-cols-3">
                <div>
                    <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300">Score de Crédito (PF)</label>
                    <input type="number" name="socios[${socioIndex}][score_credito]" class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm text-gray-800 focus:border-brand-500 focus:ring-brand-500 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90">
                </div>
                <div class="flex items-center gap-2">
                    <input type="checkbox" name="socios[${socioIndex}][possui_restricoes]" value="1" class="rounded border-gray-300 text-brand-500 focus:ring-brand-500">
                    <label class="text-sm text-gray-700 dark:text-gray-300">Possui Restrições</label>
                </div>
                <div>
                    <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300">Patrimônio Estimado</label>
                    <input type="text" name="socios[${socioIndex}][patrimonio_estimado]" class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm text-gray-800 focus:border-brand-500 focus:ring-brand-500 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90">
                </div>
            </div>
        </div>
    `;
    container.insertAdjacentHTML('beforeend', html);
    const blocks = container.querySelectorAll('[data-socio-index]');
    const newBlock = blocks[blocks.length - 1];
    if (newBlock) {
        const cpfInput = newBlock.querySelector('[data-socio-cpf]');
        if (cpfInput) {
            bindSocioCpfMask(cpfInput);
        }
        bindSocioCepLookup(newBlock);
    }
    socioIndex++;
}

function removerSocio(btn) {
    btn.closest('[data-socio-index]').remove();
}

let documentoIndex = 0;

function adicionarDocumento() {
    const container = document.getElementById('documentos_container');
    const categorias = @json($categorias ?? []);
    const categoriasOptions = categorias.map(cat => `<option value="${cat.slug}">${cat.nome}</option>`).join('');
    
    const html = `
        <div class="mb-4 rounded-lg border border-gray-200 p-4 dark:border-gray-700" data-documento-index="${documentoIndex}">
            <div class="mb-3 flex items-center justify-between">
                <h3 class="font-medium text-gray-700 dark:text-gray-300">Documento ${documentoIndex + 1}</h3>
                <button type="button" onclick="removerDocumento(this)" class="text-error-500 hover:text-error-700">Remover</button>
            </div>
            <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                <div>
                    <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300">Arquivo</label>
                    <input type="file" name="documentos[]" accept=".pdf,.doc,.docx,.jpg,.jpeg,.png,.xls,.xlsx" class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm text-gray-800 focus:border-brand-500 focus:ring-brand-500 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90">
                </div>
                <div>
                    <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300">Categoria</label>
                    <select name="documentos_categoria[]" class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm text-gray-800 focus:border-brand-500 focus:ring-brand-500 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90">
                        <option value="">Selecione uma categoria...</option>
                        ${categoriasOptions}
                    </select>
                </div>
                <div>
                    <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300">Tags</label>
                    <input type="text" name="documentos_tags[]" placeholder="Ex: importante, contrato, fiscal (separadas por vírgula)" class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm text-gray-800 focus:border-brand-500 focus:ring-brand-500 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90">
                    <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">Separe múltiplas tags por vírgula</p>
                </div>
                <div>
                    <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300">Descrição</label>
                    <textarea name="documentos_descricao[]" rows="2" placeholder="Descrição do documento..." class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm text-gray-800 focus:border-brand-500 focus:ring-brand-500 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90"></textarea>
                </div>
            </div>
        </div>
    `;
    container.insertAdjacentHTML('beforeend', html);
    documentoIndex++;
}

function removerDocumento(btn) {
    btn.closest('[data-documento-index]').remove();
}

// Habilita/desabilita controles (exceto tipo de pessoa quando bloqueado por filial)
function setClienteFormDescendantsDisabled(container, disabled) {
    if (!container) {
        return;
    }
    container.querySelectorAll('input, select, textarea, button').forEach((el) => {
        if (el.id === 'tipo_pessoa' || el.name === 'tipo_pessoa') {
            return;
        }
        el.disabled = disabled;
    });
}

// Tipo efetivo: select desabilitado (tipo bloqueado) pode reportar value vazio em alguns casos — usar hidden name="tipo_pessoa".
function obterTipoPessoaNoFormularioCliente() {
    const sel = document.getElementById('tipo_pessoa');
    if (!sel) {
        return '';
    }
    let v = (sel.value || '').trim().toUpperCase();
    if (v) {
        return v;
    }
    const form = document.getElementById('clienteForm');
    if (!form) {
        return '';
    }
    const hidden = form.querySelector('input[type="hidden"][name="tipo_pessoa"]');
    if (hidden && hidden.value) {
        return String(hidden.value).trim().toUpperCase();
    }
    return '';
}

// Mostrar/ocultar e desabilitar campos conforme PF / PJ / estrangeiro
function atualizarClienteFormPorTipoPessoa() {
    const tipoPessoaSelect = document.getElementById('tipo_pessoa');
    if (!tipoPessoaSelect) {
        return;
    }
    const tipo = obterTipoPessoaNoFormularioCliente();
    const isPf = tipo === 'F';
    const isPj = tipo === 'J';
    const isEst = tipo === 'E';

    const camposPF = document.getElementById('campos_pf');
    const camposPJ = document.getElementById('campos_pj');
    const camposEstrangeiro = document.getElementById('campos_estrangeiro');
    const secaoSocios = document.getElementById('secao_socios');

    if (camposPF) {
        camposPF.style.display = isPf ? 'block' : 'none';
        setClienteFormDescendantsDisabled(camposPF, !isPf);
    }
    if (camposPJ) {
        camposPJ.style.display = isPj ? 'block' : 'none';
        setClienteFormDescendantsDisabled(camposPJ, !isPj);
    }
    if (camposEstrangeiro) {
        camposEstrangeiro.style.display = isEst ? 'block' : 'none';
        setClienteFormDescendantsDisabled(camposEstrangeiro, !isEst);
    }
    if (secaoSocios) {
        secaoSocios.classList.toggle('hidden', !isPj);
        setClienteFormDescendantsDisabled(secaoSocios, !isPj);
    }

    document.querySelectorAll('[data-cliente-pj-only]').forEach((el) => {
        el.classList.toggle('hidden', !isPj);
        setClienteFormDescendantsDisabled(el, !isPj);
    });
}

function inicializarTipoPessoa() {
    const tipoPessoaSelect = document.getElementById('tipo_pessoa');
    if (!tipoPessoaSelect) {
        return;
    }

    tipoPessoaSelect.addEventListener('change', function () {
        atualizarClienteFormPorTipoPessoa();
    });

    if (obterTipoPessoaNoFormularioCliente()) {
        tipoPessoaSelect.dispatchEvent(new Event('change'));
    } else {
        atualizarClienteFormPorTipoPessoa();
    }
}

// Inicializar tudo quando o DOM estiver pronto
document.addEventListener('DOMContentLoaded', function() {
    inicializarTipoPessoa();
    document.querySelectorAll('.js-phone-mask').forEach(bindPhoneMask);

    // Limite de Crédito: formato brasileiro (1.234,56)
    const limiteCreditoEl = document.getElementById('limite_credito');
    if (limiteCreditoEl) {
        limiteCreditoEl.addEventListener('blur', function() {
            let v = this.value.replace(/\s/g, '').replace(/\./g, '').replace(',', '.');
            if (v === '' || v === '.') {
                this.value = '';
                return;
            }
            const n = parseFloat(v);
            if (!isNaN(n) && n >= 0) {
                const parts = v.split('.');
                const intPart = parts[0] || '0';
                const decPart = (parts[1] || '').padEnd(2, '0').slice(0, 2);
                const withDots = intPart.replace(/\B(?=(\d{3})+(?!\d))/g, '.');
                this.value = withDots + ',' + decPart;
            }
        });
    }

    const tipoUnidade = document.getElementById('tipo_unidade');
    const matrizContainer = document.getElementById('matriz_container');
    const grupoEconomicoSelect = document.getElementById('grupo_economico_id');
    const matrizSelect = document.getElementById('matriz_id');
    const tipoPessoaSelect = document.getElementById('tipo_pessoa');
    const form = document.getElementById('clienteForm');

    const aplicarRegraFilial = () => {
        if (!tipoUnidade || !tipoPessoaSelect) return;
        const isFilial = tipoUnidade.value === 'filial';
        const bloqueado = tipoPessoaSelect.dataset.locked === '1';

        if (isFilial) {
            tipoPessoaSelect.value = 'J';
            tipoPessoaSelect.dispatchEvent(new Event('change'));
            if (!bloqueado) {
                tipoPessoaSelect.disabled = true;
            }
        } else if (!bloqueado) {
            tipoPessoaSelect.disabled = false;
        }
    };

    const atualizarMatrizesPorGrupo = () => {
        if (!tipoUnidade || !matrizSelect) return;
        const isFilial = tipoUnidade.value === 'filial';
        const grupoSelecionado = grupoEconomicoSelect ? grupoEconomicoSelect.value : '';
        const placeholder = matrizSelect.querySelector('option[value=""]');
        const defaultText = placeholder?.dataset.defaultText || 'Selecione...';

        if (!isFilial) {
            matrizSelect.disabled = false;
            if (placeholder) {
                placeholder.textContent = defaultText;
            }
            Array.from(matrizSelect.options).forEach((opt) => {
                if (opt.value === '') return;
                opt.hidden = false;
                opt.disabled = false;
            });
            return;
        }

        Array.from(matrizSelect.options).forEach((opt) => {
            if (opt.value === '') return;
            const grupo = opt.dataset.grupo || '';
            const mostrar = grupoSelecionado && grupo === grupoSelecionado;
            opt.hidden = !mostrar;
            opt.disabled = !mostrar;
        });

        if (placeholder) {
            placeholder.textContent = grupoSelecionado ? defaultText : 'Selecione um grupo econômico';
        }

        if (!grupoSelecionado) {
            matrizSelect.value = '';
            matrizSelect.disabled = true;
        } else {
            matrizSelect.disabled = false;
            const selectedOption = matrizSelect.options[matrizSelect.selectedIndex];
            if (selectedOption && selectedOption.value && selectedOption.hidden) {
                matrizSelect.value = '';
            }
        }
    };

    if (tipoUnidade && matrizContainer) {
        const toggleMatriz = () => {
            const isFilial = tipoUnidade.value === 'filial';
            matrizContainer.classList.toggle('hidden', !isFilial);
            aplicarRegraFilial();
            atualizarMatrizesPorGrupo();
        };
        tipoUnidade.addEventListener('change', toggleMatriz);
        toggleMatriz();
    }

    if (grupoEconomicoSelect) {
        grupoEconomicoSelect.addEventListener('change', () => {
            atualizarMatrizesPorGrupo();
        });
    }

    if (form && tipoPessoaSelect) {
        form.addEventListener('submit', () => {
            if (!tipoPessoaSelect.disabled) {
                return;
            }
            // Bloqueio de tipo: o Blade já pode ter enviado hidden — não duplicar nem sobrescrever com select.value vazio.
            const existingBladeHidden = form.querySelector('input[type="hidden"][name="tipo_pessoa"]:not([data-auto])');
            if (existingBladeHidden && String(existingBladeHidden.value || '').trim() !== '') {
                return;
            }

            let hidden = form.querySelector('input[name="tipo_pessoa"][data-auto]');
            if (!hidden) {
                hidden = document.createElement('input');
                hidden.type = 'hidden';
                hidden.name = 'tipo_pessoa';
                hidden.setAttribute('data-auto', '1');
                form.appendChild(hidden);
            }
            hidden.value = obterTipoPessoaNoFormularioCliente() || tipoPessoaSelect.value || '';
        });
    }

    document.querySelectorAll('[data-help-toggle]').forEach(btn => {
        btn.addEventListener('click', () => {
            const targetId = btn.getAttribute('data-help-toggle');
            const target = document.getElementById(targetId);
            if (target) {
                target.classList.toggle('hidden');
            }
        });
    });

    document.querySelectorAll('[data-endereco-block]').forEach((block) => {
        bindCepLookup(block);
    });

    document.querySelectorAll('[data-socio-index]').forEach((block) => {
        bindSocioCepLookup(block);
    });
    
    // Máscaras para CPF/CNPJ
    const cpf = document.getElementById('cpf');
    if (cpf) {
        bindSocioCpfMask(cpf);
    }

    document.querySelectorAll('[data-socio-cpf]').forEach((input) => {
        bindSocioCpfMask(input);
    });

    const cnpj = document.getElementById('cnpj');
    if (cnpj) {
        cnpj.addEventListener('input', function(e) {
            const raw = e.target.value.replace(/[^0-9A-Za-z]/g, '').toUpperCase();
            e.target.value = formatarCNPJInput(raw);
        });

        cnpj.addEventListener('blur', function(e) {
            const raw = e.target.value.replace(/[^0-9A-Za-z]/g, '').toUpperCase();
            if (!raw) {
                e.target.setCustomValidity('');
                return;
            }

            if (!validarCNPJAlfanumerico(raw)) {
                e.target.setCustomValidity('CNPJ inválido.');
            } else {
                e.target.setCustomValidity('');
            }
        });

        cnpj.addEventListener('invalid', function(e) {
            if (e.target.validity.customError) {
                return;
            }
            e.target.setCustomValidity('');
        });
    }

    function formatarCNPJInput(raw) {
        if (!raw) return '';

        const clean = raw.substring(0, 14);
        const p1 = clean.substring(0, 2);
        const p2 = clean.substring(2, 5);
        const p3 = clean.substring(5, 8);
        const p4 = clean.substring(8, 12);
        const p5 = clean.substring(12, 14);

        let value = p1;
        if (p2) value += `.${p2}`;
        if (p3) value += `.${p3}`;
        if (p4) value += `/${p4}`;
        if (p5) value += `-${p5}`;
        return value;
    }

    function validarCNPJAlfanumerico(clean) {
        if (!clean || clean.length !== 14) return false;
        if (/^([A-Z0-9])\1{13}$/.test(clean)) return false;

        const base = clean.substring(0, 12);
        const dvInformado = clean.substring(12, 14);
        if (!/^\d{2}$/.test(dvInformado)) return false;

        const dv1 = calcularDVCNPJ(base);
        const dv2 = calcularDVCNPJ(base + dv1);
        return dvInformado === `${dv1}${dv2}`;
    }

    function calcularDVCNPJ(base) {
        let weights = [5, 4, 3, 2, 9, 8, 7, 6, 5, 4, 3, 2];
        if (base.length === 13) {
            weights = [6, 5, 4, 3, 2, 9, 8, 7, 6, 5, 4, 3, 2];
        }

        let sum = 0;
        for (let i = 0; i < base.length; i++) {
            sum += mapCNPJChar(base[i]) * weights[i];
        }

        const resto = sum % 11;
        return resto < 2 ? 0 : 11 - resto;
    }

    function mapCNPJChar(char) {
        if (/[0-9]/.test(char)) return parseInt(char, 10);
        const code = char.charCodeAt(0);
        if (code >= 65 && code <= 90) return code - 48; // A=17 ... Z=42
        return 0;
    }

    const btnBuscarCnpj = document.getElementById('btnBuscarCnpj');
    if (btnBuscarCnpj) {
        btnBuscarCnpj.addEventListener('click', async function() {
            const cnpjInput = document.getElementById('cnpj');
            if (!cnpjInput) return;

            const cnpjLimpo = (cnpjInput.value || '').replace(/\D/g, '');
            if (cnpjLimpo.length !== 14) {
                alert('Informe um CNPJ válido para buscar os dados.');
                return;
            }

            btnBuscarCnpj.disabled = true;
            const originalText = btnBuscarCnpj.textContent;
            btnBuscarCnpj.textContent = 'Buscando...';

            try {
                const dados = await buscarDadosCnpjPublico(cnpjLimpo);
                if (!dados) {
                    alert('Não foi possível conseguir os dados.');
                    return;
                }

                preencherDadosCnpj(dados);
            } catch (error) {
                alert('Não foi possível conseguir os dados.');
            } finally {
                btnBuscarCnpj.disabled = false;
                btnBuscarCnpj.textContent = originalText;
            }
        });
    }
});

async function buscarDadosCnpjPublico(cnpj) {
    const endpoints = [
        `https://publica.cnpj.ws/cnpj/${cnpj}`,
        `https://api.opencnpj.org/${cnpj}`,
    ];

    const responses = await Promise.all(
        endpoints.map(async (url) => {
            try {
                const response = await fetch(url, { headers: { 'Accept': 'application/json' } });
                if (!response.ok) return null;
                const data = await response.json();
                return data && Object.keys(data).length ? data : null;
            } catch (e) {
                return null;
            }
        })
    );

    const valid = responses.filter(Boolean);
    if (!valid.length) {
        return null;
    }

    const normalized = valid.map(normalizarCnpjResponse);
    console.log('[CNPJ] Respostas brutas:', valid);
    console.log('[CNPJ] Normalizado:', normalized);
    return mesclarDadosCnpj(normalized);
}

function normalizarDataCnpj(value) {
    if (!value) return '';
    const raw = String(value).trim();
    if (!raw) return '';
    if (/^\d{4}-\d{2}-\d{2}$/.test(raw)) return raw;
    const match = raw.match(/^(\d{2})\/(\d{2})\/(\d{4})$/);
    if (match) {
        return `${match[3]}-${match[2]}-${match[1]}`;
    }
    return '';
}

function normalizarCnpjResponse(data) {
    const estabelecimento = data.estabelecimento || data.endereco || data.address || data;
    const atividadePrincipal = data.estabelecimento?.atividade_principal
        || data.atividade_principal
        || data.atividadePrincipal
        || data.cnae_principal
        || data.main_activity
        || data.mainActivity;

    const atividadeDescricao = Array.isArray(atividadePrincipal)
        ? (atividadePrincipal[0]?.descricao || atividadePrincipal[0]?.text || '')
        : (atividadePrincipal?.descricao || atividadePrincipal?.text || atividadePrincipal || '');

    const cidade = estabelecimento?.cidade?.nome
        || estabelecimento?.municipio?.nome
        || estabelecimento?.cidade
        || estabelecimento?.municipio
        || data.municipio
        || estabelecimento?.localidade
        || '';

    const uf = estabelecimento?.estado?.sigla
        || estabelecimento?.estado?.uf
        || estabelecimento?.estado
        || estabelecimento?.uf
        || data.uf
        || '';

    return {
        razao_social: data.razao_social || data.razaoSocial || data.nome || data.company?.name || '',
        nome_fantasia: data.estabelecimento?.nome_fantasia
            || data.estabelecimento?.nomeFantasia
            || data.nome_fantasia
            || data.nomeFantasia
            || data.fantasia
            || data.nome_fantasia_empresarial
            || data.alias
            || '',
        ramo_atividade: atividadeDescricao || '',
        natureza_juridica: data.natureza_juridica?.descricao
            || data.natureza_juridica
            || data.naturezaJuridica
            || data.natureza_juridica_codigo
            || '',
        porte_empresa: data.porte?.descricao
            || data.porte_empresa
            || data.porte
            || data.porteEmpresa
            || '',
        capital_social: data.capital_social
            || data.capitalSocial
            || data.capital_social_empresa
            || '',
        data_inicio_atividade: normalizarDataCnpj(
            estabelecimento?.data_inicio_atividade
            || estabelecimento?.dataInicioAtividade
            || data.data_inicio_atividade
            || data.dataInicioAtividade
            || data.data_abertura
            || data.abertura
        ),
        optante_simples: data.simples?.opcao_simples
            || data.simples?.simples
            || data.opcao_simples
            || data.opcaoSimples
            || data.simples
            || null,
        cep: estabelecimento?.cep || data.cep || '',
        logradouro: estabelecimento?.logradouro || data.logradouro || estabelecimento?.endereco || estabelecimento?.street || '',
        numero: estabelecimento?.numero || data.numero || estabelecimento?.number || '',
        bairro: estabelecimento?.bairro || data.bairro || estabelecimento?.district || '',
        cidade: cidade,
        uf: uf,
        contatos: extrairContatosCnpj(data),
        socios: extrairSociosCnpj(data),
    };
}

function contarCamposCnpj(data) {
    const fields = ['razao_social', 'nome_fantasia', 'ramo_atividade', 'cep', 'logradouro', 'numero', 'bairro', 'cidade', 'uf', 'natureza_juridica', 'porte_empresa', 'capital_social', 'data_inicio_atividade'];
    let count = fields.reduce((sum, key) => sum + (data[key] ? 1 : 0), 0);
    if (Array.isArray(data.contatos) && data.contatos.length) count += 1;
    if (Array.isArray(data.socios) && data.socios.length) count += 1;
    return count;
}

function isEmptyValue(value) {
    if (value === null || value === undefined) return true;
    if (typeof value === 'string') return value.trim() === '';
    if (Array.isArray(value)) return value.length === 0;
    return false;
}

function mesclarDadosCnpj(lista) {
    const sorted = [...lista].sort((a, b) => contarCamposCnpj(b) - contarCamposCnpj(a));
    const merged = { ...sorted[0] };

    for (const item of sorted.slice(1)) {
        for (const key of Object.keys(merged)) {
            if (isEmptyValue(merged[key]) && !isEmptyValue(item[key])) {
                merged[key] = item[key];
            }
        }
    }

    return merged;
}

function preencherDadosCnpj(data) {
    console.log('[CNPJ] Mesclado:', data);
    const nomeInput = document.querySelector('input[name="nome"]');
    const razaoInput = document.querySelector('input[name="razao_social"]');
    const ramoInput = document.querySelector('input[name="ramo_atividade"]');
    const naturezaInput = document.querySelector('input[name="natureza_juridica"]');
    const porteInput = document.querySelector('input[name="porte_empresa"]');
    const capitalInput = document.querySelector('input[name="capital_social"]');
    const inicioAtividadeInput = document.querySelector('input[name="data_inicio_atividade"]');
    const optanteSimples = document.querySelector('input[name="optante_simples_nacional"]');
    const tipoPessoaSelect = document.getElementById('tipo_pessoa');

    if (nomeInput && data.nome_fantasia) nomeInput.value = data.nome_fantasia;
    if (razaoInput && data.razao_social) razaoInput.value = data.razao_social;
    if (ramoInput && data.ramo_atividade) ramoInput.value = data.ramo_atividade;
    if (naturezaInput && data.natureza_juridica) naturezaInput.value = data.natureza_juridica;
    if (porteInput && data.porte_empresa) porteInput.value = data.porte_empresa;
    if (capitalInput && data.capital_social) capitalInput.value = data.capital_social;
    if (inicioAtividadeInput && data.data_inicio_atividade) inicioAtividadeInput.value = data.data_inicio_atividade;
    if (optanteSimples && data.optante_simples !== null) {
        const v = String(data.optante_simples).toLowerCase();
        optanteSimples.checked = ['true', '1', 's', 'sim', 'yes'].includes(v) || data.optante_simples === true;
    }

    if (tipoPessoaSelect && tipoPessoaSelect.value !== 'J') {
        tipoPessoaSelect.value = 'J';
        tipoPessoaSelect.dispatchEvent(new Event('change'));
    }

    const enderecoContainer = document.querySelector('[data-endereco-block]');
    if (enderecoContainer) {
        const cepInput = enderecoContainer.querySelector('[data-endereco-field="cep"]');
        const logradouroInput = enderecoContainer.querySelector('[data-endereco-field="logradouro"]');
        const numeroInput = enderecoContainer.querySelector('[data-endereco-field="numero"]');
        const bairroInput = enderecoContainer.querySelector('[data-endereco-field="bairro"]');
        const cidadeInput = enderecoContainer.querySelector('[data-endereco-field="cidade"]');
        const ufInput = enderecoContainer.querySelector('[data-endereco-field="estado"]');

        if (cepInput && data.cep) cepInput.value = data.cep;
        if (logradouroInput && data.logradouro) logradouroInput.value = data.logradouro;
        if (numeroInput && data.numero) numeroInput.value = data.numero;
        if (bairroInput && data.bairro) bairroInput.value = data.bairro;
        if (ufInput && data.uf) ufInput.value = data.uf;
        if (cidadeInput && data.cidade) {
            cidadeInput.dataset.selected = data.cidade;
            popularSelectCidadeEndereco(enderecoContainer, data.cidade);
        }
    }

    preencherContatosCnpj(data.contatos || []);
    preencherSociosCnpj(data.socios || []);
}

function extrairContatosCnpj(data) {
    const contatos = [];
    const email = data.email || data.estabelecimento?.email || data.contact?.email || '';
    const nomeContato = data.nome_fantasia
        || data.nomeFantasia
        || data.razao_social
        || data.razaoSocial
        || data.nome
        || '';
    const telefones = [];

    if (Array.isArray(data.telefones)) {
        data.telefones.forEach((tel) => {
            const ddd = tel.ddd || '';
            const numero = tel.numero || '';
            if (ddd || numero) {
                telefones.push(`${ddd ? '(' + ddd + ') ' : ''}${numero}`);
            }
        });
    }

    const tel1 = data.estabelecimento?.telefone1 || data.telefone1 || data.telefone || '';
    const tel2 = data.estabelecimento?.telefone2 || data.telefone2 || '';
    const ddd1 = data.estabelecimento?.ddd1 || data.ddd1 || '';
    const ddd2 = data.estabelecimento?.ddd2 || data.ddd2 || '';

    if (tel1) telefones.push(`${ddd1 ? '(' + ddd1 + ') ' : ''}${tel1}`);
    if (tel2) telefones.push(`${ddd2 ? '(' + ddd2 + ') ' : ''}${tel2}`);

    if (email || telefones.length) {
        contatos.push({
            nome: nomeContato || '',
            email: email || '',
            telefone: telefones[0] || '',
            telefone2: telefones[1] || '',
        });
    }

    return contatos;
}

function extrairSociosCnpj(data) {
    const socios = [];
    const listas = [
        data.socios,
        data.QSA,
        data.qsa,
        data.socios?.socios,
        data.qsa?.socios,
        data.qsa?.qsa,
    ].filter(Boolean);

    let lista = [];
    for (const item of listas) {
        if (Array.isArray(item) && item.length) {
            lista = item;
            break;
        }
    }

    if (Array.isArray(lista)) {
        lista.forEach((socio) => {
            const nome = socio.nome_socio
                || socio.nome
                || socio.nome_razao
                || socio.razao_social
                || socio.razaoSocial
                || socio.nomeFantasia
                || socio.nome_socio_ou_nome
                || '';
            const cpf = socio.cnpj_cpf_socio
                || socio.cnpj_cpf
                || socio.cpf_cnpj
                || socio.cpf
                || socio.documento
                || socio.cnpj
                || '';
            const qualificacao = socio.qualificacao_socio
                || socio.qualificacao
                || socio.qualificacao_do_socio
                || socio.qualificacao_responsavel
                || '';
            const isAdmin = /administrador/i.test(qualificacao);

            if (nome || cpf) {
                socios.push({
                    nome_completo: nome,
                    cpf: cpf,
                    socio_administrador: isAdmin ? true : null,
                });
            }
        });
    }

    return socios;
}

function preencherContatosCnpj(contatos) {
    if (!contatos.length) return;
    const container = document.getElementById('contatos_container');
    if (!container) return;

    container.innerHTML = '';
    contatoIndex = 0;

    contatos.forEach((contato) => {
        adicionarContato();
        const blocks = Array.from(container.querySelectorAll('[data-index]'));
        const block = blocks[blocks.length - 1] || null;
        if (!block) return;

        const idx = block.getAttribute('data-index');
        const nomeInput = block.querySelector(`input[name="contatos[${idx}][nome]"]`);
        const emailInput = block.querySelector(`input[name="contatos[${idx}][email]"]`);
        const telefoneInput = block.querySelector(`input[name="contatos[${idx}][telefone]"]`);
        const telefone2Input = block.querySelector(`input[name="contatos[${idx}][telefone2]"]`);

        if (nomeInput && contato.nome) nomeInput.value = contato.nome;
        if (emailInput && contato.email) emailInput.value = contato.email;
        if (telefoneInput && contato.telefone) telefoneInput.value = contato.telefone;
        if (telefone2Input && contato.telefone2) telefone2Input.value = contato.telefone2;
    });
}

function preencherSociosCnpj(socios) {
    if (!socios.length) return;
    const container = document.getElementById('socios_container');
    if (!container) return;

    const secaoSocios = document.getElementById('secao_socios');
    if (secaoSocios) {
        secaoSocios.style.display = 'block';
    }

    // Limpar e recriar para garantir preenchimento completo
    container.innerHTML = '';
    socioIndex = 0;

    socios.forEach((socio) => {
        adicionarSocio();
        const blocks = Array.from(container.querySelectorAll('[data-socio-index]'));
        const block = blocks[blocks.length - 1] || null;
        if (!block) return;

        const idx = block.getAttribute('data-socio-index');
        const nomeInput = block.querySelector(`input[name="socios[${idx}][nome_completo]"]`);
        const cpfInput = block.querySelector(`input[name="socios[${idx}][cpf]"]`);
        const adminInput = block.querySelector(`input[name="socios[${idx}][socio_administrador]"]`);

        if (nomeInput) nomeInput.value = socio.nome_completo || '';
        if (cpfInput) cpfInput.value = socio.cpf || '';
        if (adminInput) adminInput.checked = !!socio.socio_administrador;
    });
}
</script>
