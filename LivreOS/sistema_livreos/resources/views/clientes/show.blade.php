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
        <h1 class="mb-2 text-2xl font-semibold text-gray-800 dark:text-white/90">Detalhes do Cliente</h1>
        <p class="text-gray-600 dark:text-gray-400">{{ $cliente->nome }}</p>
    </div>
    <div class="flex gap-2">
        @if($cliente->tipo_pessoa === 'J' && $cliente->tipo_unidade === 'matriz')
            <a href="{{ route('clientes.create', ['grupo_economico_id' => $cliente->grupo_economico_id, 'tipo_unidade' => 'filial', 'matriz_id' => $cliente->id, 'tipo_pessoa' => 'J']) }}" class="rounded-lg border border-brand-200 bg-white px-4 py-2.5 text-sm font-medium text-brand-600 hover:bg-brand-50 dark:border-brand-500/30 dark:bg-gray-800 dark:text-brand-400">Nova Filial</a>
        @endif
        <a href="{{ route('clientes.edit', $cliente) }}" class="rounded-lg bg-brand-500 px-4 py-2.5 text-sm font-medium text-white hover:bg-brand-600">Editar</a>
        <a href="{{ route('clientes.index') }}" class="rounded-lg border border-gray-300 bg-white px-4 py-2.5 text-sm font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-300">Voltar</a>
    </div>
</div>

<div class="grid grid-cols-1 gap-6 lg:grid-cols-2">
    <!-- Dados Básicos -->
    <div class="rounded-lg border border-gray-200 bg-white p-6 shadow-theme-sm dark:border-gray-800 dark:bg-gray-800">
        <h2 class="mb-4 text-lg font-semibold text-gray-800 dark:text-white/90">Dados Básicos</h2>
        <dl class="space-y-3">
            <div>
                <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Tipo de Pessoa</dt>
                <dd class="mt-1 text-sm text-gray-800 dark:text-white/90">{{ $cliente->tipo_pessoa_texto }}</dd>
            </div>
            <div>
                <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">País de Origem</dt>
                <dd class="mt-1 text-sm text-gray-800 dark:text-white/90">{{ $cliente->pais_origem }}</dd>
            </div>
            <div>
                <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Nome</dt>
                <dd class="mt-1 text-sm text-gray-800 dark:text-white/90">{{ $cliente->nome }}</dd>
            </div>
            <div>
                <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Vendedor</dt>
                <dd class="mt-1 text-sm text-gray-800 dark:text-white/90">{{ $cliente->vendedor?->name ?: 'Não informado' }}</dd>
            </div>
            @if($cliente->tipo_pessoa === 'J')
            <div>
                <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Grupo Econômico</dt>
                <dd class="mt-1 text-sm text-gray-800 dark:text-white/90">{{ $cliente->grupoEconomico?->nome ?: 'Não informado' }}</dd>
            </div>
            <div>
                <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Tipo de Unidade</dt>
                <dd class="mt-1 text-sm text-gray-800 dark:text-white/90">{{ $cliente->tipo_unidade ? ucfirst($cliente->tipo_unidade) : 'Não informado' }}</dd>
            </div>
            <div>
                <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Matriz</dt>
                <dd class="mt-1 text-sm text-gray-800 dark:text-white/90">
                    @if($cliente->matriz)
                        <a href="{{ route('clientes.show', $cliente->matriz) }}" class="text-brand-500 hover:underline">{{ $cliente->matriz->nome }}</a>
                    @else
                        Não informado
                    @endif
                </dd>
            </div>
            @endif
            <div>
                <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Limite de Crédito</dt>
                <dd class="mt-1 text-sm text-gray-800 dark:text-white/90">{{ $cliente->limite_credito !== null ? number_format($cliente->limite_credito, 2, ',', '.') : 'Não informado' }}</dd>
            </div>
            <div>
                <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Bloqueado para Vendas</dt>
                <dd class="mt-1 text-sm text-gray-800 dark:text-white/90">{{ $cliente->bloqueado_vendas ? 'Sim' : 'Não' }}</dd>
            </div>
            @if($cliente->razao_social)
            <div>
                <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Razão Social</dt>
                <dd class="mt-1 text-sm text-gray-800 dark:text-white/90">{{ $cliente->razao_social }}</dd>
            </div>
            @endif
            <div>
                <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Documento</dt>
                <dd class="mt-1 text-sm text-gray-800 dark:text-white/90">{{ $cliente->documento_principal }}</dd>
            </div>
            @if($cliente->tipo_pessoa === 'J' && $cliente->ramo_atividade)
            <div>
                <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Ramo de Atividade</dt>
                <dd class="mt-1 text-sm text-gray-800 dark:text-white/90">{{ $cliente->ramo_atividade }}</dd>
            </div>
            @endif
            <div>
                <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Classificação</dt>
                <dd class="mt-1 flex items-center gap-1">
                    @for($i = 0; $i < 4; $i++)
                    <svg class="h-4 w-4 {{ $i < $cliente->classificacao_rating ? 'text-yellow-400' : 'text-gray-300' }}" fill="currentColor" viewBox="0 0 20 20">
                        <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"></path>
                    </svg>
                    @endfor
                </dd>
            </div>
            <div>
                <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Produtor Rural</dt>
                <dd class="mt-1 text-sm text-gray-800 dark:text-white/90">{{ $cliente->produtor_rural ? 'Sim' : 'Não' }}</dd>
            </div>
            @if($cliente->tipo_pessoa === 'J')
            <div>
                <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Condomínio</dt>
                <dd class="mt-1 text-sm text-gray-800 dark:text-white/90">{{ $cliente->condominio ? 'Sim' : 'Não' }}</dd>
            </div>
            @endif
        </dl>
    </div>

    @if($cliente->tipo_pessoa === 'J')
    <!-- Unidades vinculadas -->
    <div class="rounded-lg border border-gray-200 bg-white p-6 shadow-theme-sm dark:border-gray-800 dark:bg-gray-800">
        <h2 class="mb-4 text-lg font-semibold text-gray-800 dark:text-white/90">Unidades vinculadas</h2>
        @if($cliente->filiais->count())
            <ul class="space-y-2 text-sm text-gray-700 dark:text-gray-300">
                @foreach($cliente->filiais as $filial)
                    <li>
                        <a href="{{ route('clientes.show', $filial) }}" class="text-brand-500 hover:underline">{{ $filial->nome }}</a>
                        <span class="text-gray-500 dark:text-gray-400">- {{ $filial->cnpj ?: $filial->documento_principal }}</span>
                    </li>
                @endforeach
            </ul>
        @else
            <p class="text-sm text-gray-500 dark:text-gray-400">Nenhuma unidade vinculada</p>
        @endif
    </div>
    @endif

    <!-- Documentação / Dados Fiscais -->
    <div class="rounded-lg border border-gray-200 bg-white p-6 shadow-theme-sm dark:border-gray-800 dark:bg-gray-800">
        <h2 class="mb-4 text-lg font-semibold text-gray-800 dark:text-white/90">Documentação e Dados Fiscais</h2>
        <dl class="space-y-3">
            @if($cliente->tipo_pessoa === 'F')
            <div>
                <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">CPF</dt>
                <dd class="mt-1 text-sm text-gray-800 dark:text-white/90">{{ $cliente->cpf }}</dd>
            </div>
            @if($cliente->rg)
            <div>
                <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">RG</dt>
                <dd class="mt-1 text-sm text-gray-800 dark:text-white/90">{{ $cliente->rg }}</dd>
            </div>
            @endif
            @if($cliente->data_nascimento)
            <div>
                <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Data de Nascimento</dt>
                <dd class="mt-1 text-sm text-gray-800 dark:text-white/90">{{ optional($cliente->data_nascimento)->format('d/m/Y') }}</dd>
            </div>
            @endif
            @if($cliente->sexo)
            <div>
                <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Sexo</dt>
                <dd class="mt-1 text-sm text-gray-800 dark:text-white/90">{{ $cliente->sexo === 'M' ? 'Masculino' : 'Feminino' }}</dd>
            </div>
            @endif
            @elseif($cliente->tipo_pessoa === 'J')
            <div>
                <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">CNPJ</dt>
                <dd class="mt-1 text-sm text-gray-800 dark:text-white/90">{{ $cliente->cnpj }}</dd>
            </div>
            @if($cliente->inscricao_estadual)
            <div>
                <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Inscrição Estadual</dt>
                <dd class="mt-1 text-sm text-gray-800 dark:text-white/90">{{ $cliente->inscricao_estadual }}</dd>
            </div>
            @endif
            @if($cliente->uf_ie)
            <div>
                <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">UF da IE</dt>
                <dd class="mt-1 text-sm text-gray-800 dark:text-white/90">{{ $cliente->uf_ie }}</dd>
            </div>
            @endif
            @if($cliente->inscricao_municipal)
            <div>
                <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Inscrição Municipal</dt>
                <dd class="mt-1 text-sm text-gray-800 dark:text-white/90">{{ $cliente->inscricao_municipal }}</dd>
            </div>
            @endif
            <div>
                <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Optante Simples Nacional</dt>
                <dd class="mt-1 text-sm text-gray-800 dark:text-white/90">{{ $cliente->optante_simples_nacional ? 'Sim' : 'Não' }}</dd>
            </div>
            <div>
                <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Ignorar IM na NFS-e</dt>
                <dd class="mt-1 text-sm text-gray-800 dark:text-white/90">{{ $cliente->ignorar_im_nfse ? 'Sim' : 'Não' }}</dd>
            </div>
            @if($cliente->natureza_juridica)
            <div>
                <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Natureza Jurídica</dt>
                <dd class="mt-1 text-sm text-gray-800 dark:text-white/90">{{ $cliente->natureza_juridica }}</dd>
            </div>
            @endif
            @if($cliente->porte_empresa)
            <div>
                <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Porte</dt>
                <dd class="mt-1 text-sm text-gray-800 dark:text-white/90">{{ $cliente->porte_empresa }}</dd>
            </div>
            @endif
            @if($cliente->capital_social)
            <div>
                <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Capital Social</dt>
                <dd class="mt-1 text-sm text-gray-800 dark:text-white/90">{{ $cliente->capital_social }}</dd>
            </div>
            @endif
            @if($cliente->data_inicio_atividade)
            <div>
                <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Data de Início de Atividade</dt>
                <dd class="mt-1 text-sm text-gray-800 dark:text-white/90">{{ optional($cliente->data_inicio_atividade)->format('d/m/Y') }}</dd>
            </div>
            @endif
            @else
            <div>
                <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Documento Estrangeiro</dt>
                <dd class="mt-1 text-sm text-gray-800 dark:text-white/90">{{ $cliente->documento_estrangeiro }}</dd>
            </div>
            @endif
        </dl>
    </div>

    <!-- Faturamento e Portal -->
    <div class="rounded-lg border border-gray-200 bg-white p-6 shadow-theme-sm dark:border-gray-800 dark:bg-gray-800">
        <h2 class="mb-4 text-lg font-semibold text-gray-800 dark:text-white/90">Faturamento e Portal</h2>
        <dl class="space-y-3">
            @if(!is_null($cliente->dias_antecedencia_faturamento))
            <div>
                <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Dias de Antecedência</dt>
                <dd class="mt-1 text-sm text-gray-800 dark:text-white/90">{{ $cliente->dias_antecedencia_faturamento }}</dd>
            </div>
            @endif
            @if(!is_null($cliente->dia_faturamento))
            <div>
                <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Dia de Faturamento</dt>
                <dd class="mt-1 text-sm text-gray-800 dark:text-white/90">{{ $cliente->dia_faturamento }}</dd>
            </div>
            @endif
            <div>
                <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Emissão de NFS-e</dt>
                <dd class="mt-1 text-sm text-gray-800 dark:text-white/90">
                    @switch($cliente->emissao_nfse)
                        @case('faturamento') Emitir no Faturamento @break
                        @case('quitacao') Emitir na Quitação @break
                        @default Utilizar Configuração da Empresa
                    @endswitch
                </dd>
            </div>
            @if(function_exists('do_action'))
            <?php do_action('admin.clientes.show.portal', $cliente); ?>
            <?php do_action('admin.clientes.show.extra', $cliente); ?>
            @endif
            @if(!empty($cliente->empresas_vinculadas))
            <div>
                <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Empresas Vinculadas</dt>
                <dd class="mt-1 text-sm text-gray-800 dark:text-white/90">{{ implode(', ', $cliente->empresas_vinculadas) }}</dd>
            </div>
            @endif
        </dl>
    </div>

    <!-- Observações -->
    <div class="rounded-lg border border-gray-200 bg-white p-6 shadow-theme-sm dark:border-gray-800 dark:bg-gray-800">
        <h2 class="mb-4 text-lg font-semibold text-gray-800 dark:text-white/90">Observações</h2>
        <p class="text-sm text-gray-700 dark:text-gray-300">{{ $cliente->observacoes ?: 'Nenhuma observação cadastrada' }}</p>
    </div>

    <!-- Endereços -->
    <div class="rounded-lg border border-gray-200 bg-white p-6 shadow-theme-sm dark:border-gray-800 dark:bg-gray-800">
        <h2 class="mb-4 text-lg font-semibold text-gray-800 dark:text-white/90">Endereços</h2>
        @forelse($cliente->enderecos as $endereco)
        <div class="mb-4 rounded-lg border border-gray-200 p-4 dark:border-gray-700">
            <div class="mb-2 flex items-center gap-2">
                @if($endereco->principal)
                <span class="rounded-full bg-brand-100 px-2 py-0.5 text-xs font-medium text-brand-700 dark:bg-brand-500/20 dark:text-brand-400">Principal</span>
                @endif
                @if($endereco->cobranca)
                <span class="rounded-full bg-success-100 px-2 py-0.5 text-xs font-medium text-success-700 dark:bg-success-500/20 dark:text-success-400">Cobrança</span>
                @endif
                @if($endereco->entrega)
                <span class="rounded-full bg-warning-100 px-2 py-0.5 text-xs font-medium text-warning-700 dark:bg-warning-500/20 dark:text-warning-400">Entrega</span>
                @endif
            </div>
            <p class="text-sm text-gray-800 dark:text-white/90">{{ $endereco->endereco_completo }}</p>
        </div>
        @empty
        <p class="text-sm text-gray-500 dark:text-gray-400">Nenhum endereço cadastrado</p>
        @endforelse
    </div>

    <!-- Contatos -->
    <div class="rounded-lg border border-gray-200 bg-white p-6 shadow-theme-sm dark:border-gray-800 dark:bg-gray-800">
        <h2 class="mb-4 text-lg font-semibold text-gray-800 dark:text-white/90">Contatos</h2>
        @forelse($cliente->contatos as $contato)
        <div class="mb-4 rounded-lg border border-gray-200 p-4 dark:border-gray-700">
            <div class="mb-2 flex items-center gap-2">
                <h3 class="font-medium text-gray-800 dark:text-white/90">{{ $contato->nome }}</h3>
                @if($contato->principal)
                <span class="rounded-full bg-brand-100 px-2 py-0.5 text-xs font-medium text-brand-700 dark:bg-brand-500/20 dark:text-brand-400">Principal</span>
                @endif
                @if($contato->cobranca)
                <span class="rounded-full bg-success-100 px-2 py-0.5 text-xs font-medium text-success-700 dark:bg-success-500/20 dark:text-success-400">Cobrança</span>
                @endif
            </div>
            <div class="space-y-1 text-sm text-gray-600 dark:text-gray-400">
                <p>📞 {{ $contato->telefone }}</p>
                @if($contato->telefone2)
                <p>📞 {{ $contato->telefone2 }}</p>
                @endif
                @if($contato->email)
                <p>✉️ {{ $contato->email }}</p>
                @endif
                @if($contato->cargo)
                <p>💼 {{ $contato->cargo }}</p>
                @endif
            </div>
        </div>
        @empty
        <p class="text-sm text-gray-500 dark:text-gray-400">Nenhum contato cadastrado</p>
        @endforelse
    </div>

    @if($cliente->tipo_pessoa === 'J')
    <!-- Sócios -->
    <div class="rounded-lg border border-gray-200 bg-white p-6 shadow-theme-sm dark:border-gray-800 dark:bg-gray-800">
        <h2 class="mb-4 text-lg font-semibold text-gray-800 dark:text-white/90">Sócios</h2>
        @forelse($cliente->socios as $socio)
        <div class="mb-4 rounded-lg border border-gray-200 p-4 dark:border-gray-700">
            <div class="mb-2 flex items-center justify-between">
                <p class="text-sm font-medium text-gray-800 dark:text-white/90">{{ $socio->nome_completo ?: 'Sócio' }}</p>
                @if($socio->socio_administrador)
                <span class="rounded-full bg-brand-100 px-2 py-0.5 text-xs font-medium text-brand-700 dark:bg-brand-500/20 dark:text-brand-400">Administrador</span>
                @endif
            </div>
            <div class="grid grid-cols-1 gap-2 text-sm text-gray-600 dark:text-gray-400">
                @if($socio->cpf)
                <p>CPF: {{ $socio->cpf }}</p>
                @endif
                @if($socio->rg_orgao_emissor)
                <p>RG/Órgão: {{ $socio->rg_orgao_emissor }}</p>
                @endif
                @if($socio->nacionalidade)
                <p>Nacionalidade: {{ $socio->nacionalidade }}</p>
                @endif
                @if($socio->estado_civil)
                <p>Estado Civil: {{ $socio->estado_civil }}</p>
                @endif
                @if($socio->profissao)
                <p>Profissão: {{ $socio->profissao }}</p>
                @endif
                @if($socio->logradouro || $socio->cidade)
                <p>Endereço: {{ trim("{$socio->logradouro}, {$socio->numero} {$socio->bairro} - {$socio->cidade}/{$socio->estado}") }}</p>
                @endif
                @if(!is_null($socio->percentual_participacao))
                <p>Participação: {{ $socio->percentual_participacao }}%</p>
                @endif
                @if($socio->assina_em_conjunto)
                <p>Assina: {{ $socio->assina_em_conjunto === 'isoladamente' ? 'Isoladamente' : 'Em Conjunto' }}</p>
                @endif
                @if(!is_null($socio->score_credito_pf))
                <p>Score: {{ $socio->score_credito_pf }}</p>
                @endif
                <p>Restrições: {{ $socio->possui_restricoes ? 'Sim' : 'Não' }}</p>
                @if($socio->patrimonio_estimado)
                <p>Patrimônio: {{ $socio->patrimonio_estimado }}</p>
                @endif
            </div>
        </div>
        @empty
        <p class="text-sm text-gray-500 dark:text-gray-400">Nenhum sócio cadastrado</p>
        @endforelse
    </div>
    @endif

    <!-- Documentos -->
    <div class="rounded-lg border border-gray-200 bg-white p-6 shadow-theme-sm dark:border-gray-800 dark:bg-gray-800">
        <h2 class="mb-4 text-lg font-semibold text-gray-800 dark:text-white/90">Documentos</h2>
        @forelse($cliente->documentos as $documento)
        <div class="mb-3 flex items-center justify-between rounded-lg border border-gray-200 p-3 dark:border-gray-700">
            <div>
                <p class="text-sm font-medium text-gray-800 dark:text-white/90">{{ $documento->nome_arquivo }}</p>
                <p class="text-xs text-gray-500 dark:text-gray-400">{{ $documento->categoria }} - {{ $documento->tamanho_formatado }}</p>
                @if($documento->tags)
                <p class="text-xs text-gray-500 dark:text-gray-400">Tags: {{ $documento->tags }}</p>
                @endif
                @if($documento->descricao)
                <p class="text-xs text-gray-500 dark:text-gray-400">Descrição: {{ $documento->descricao }}</p>
                @endif
            </div>
            <a href="{{ route('clientes.documentos.download', $documento) }}" class="rounded-lg border border-gray-300 bg-white px-3 py-1.5 text-sm font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-300">Download</a>
        </div>
        @empty
        <p class="text-sm text-gray-500 dark:text-gray-400">Nenhum documento cadastrado</p>
        @endforelse
    </div>

    <!-- Equipamentos e Ordens de Serviço (abas) -->
    <div class="lg:col-span-2 rounded-lg border border-gray-200 bg-white shadow-theme-sm dark:border-gray-800 dark:bg-gray-800">
        <nav class="flex border-b border-gray-200 dark:border-gray-700" aria-label="Abas">
            <button type="button" data-tab="equipamentos" class="tab-cliente px-4 py-3 text-sm font-medium border-b-2 border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300 dark:text-gray-400 dark:hover:text-gray-300" aria-current="page">Equipamentos</button>
            <button type="button" data-tab="ordens-servico" class="tab-cliente px-4 py-3 text-sm font-medium border-b-2 border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300 dark:text-gray-400 dark:hover:text-gray-300">Ordens de Serviço</button>
            <button type="button" data-tab="propostas" class="tab-cliente px-4 py-3 text-sm font-medium border-b-2 border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300 dark:text-gray-400 dark:hover:text-gray-300">Propostas</button>
            <button type="button" data-tab="vendas" class="tab-cliente px-4 py-3 text-sm font-medium border-b-2 border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300 dark:text-gray-400 dark:hover:text-gray-300">PDV Vendas</button>
        </nav>
        <div class="p-6">
            <div id="painel-equipamentos" class="tab-painel">
                <h3 class="mb-4 text-lg font-semibold text-gray-800 dark:text-white/90">Equipamentos vinculados</h3>
                @if($cliente->equipamentos->count())
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                            <thead>
                                <tr>
                                    <th class="px-4 py-2 text-left text-xs font-medium uppercase text-gray-500 dark:text-gray-400">Nome / Identificação</th>
                                    <th class="px-4 py-2 text-left text-xs font-medium uppercase text-gray-500 dark:text-gray-400">Marca</th>
                                    <th class="px-4 py-2 text-left text-xs font-medium uppercase text-gray-500 dark:text-gray-400">Modelo</th>
                                    <th class="px-4 py-2 text-left text-xs font-medium uppercase text-gray-500 dark:text-gray-400">Nº Série</th>
                                    <th class="px-4 py-2 text-left text-xs font-medium uppercase text-gray-500 dark:text-gray-400">Nº Patrimônio</th>
                                    <th class="px-4 py-2 text-left text-xs font-medium uppercase text-gray-500 dark:text-gray-400">Ações</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                                @foreach($cliente->equipamentos as $eq)
                                <tr>
                                    <td class="px-4 py-3 text-sm text-gray-800 dark:text-white/90">{{ $eq->modelo ? trim(($eq->marca ?? '') . ' ' . $eq->modelo) : ($eq->marca ?? 'Equip. #' . $eq->id) }}</td>
                                    <td class="px-4 py-3 text-sm text-gray-700 dark:text-gray-300">{{ $eq->marca ?? '-' }}</td>
                                    <td class="px-4 py-3 text-sm text-gray-700 dark:text-gray-300">{{ $eq->modelo ?? '-' }}</td>
                                    <td class="px-4 py-3 text-sm text-gray-700 dark:text-gray-300">{{ $eq->numero_serie ?? '-' }}</td>
                                    <td class="px-4 py-3 text-sm text-gray-700 dark:text-gray-300">{{ $eq->numero_patrimonio ?? '-' }}</td>
                                    <td class="px-4 py-3">
                                        <a href="{{ route('equipamentos.edit', $eq) }}" class="text-sm font-medium text-brand-600 hover:text-brand-700 dark:text-brand-400 dark:hover:text-brand-300">Editar</a>
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @else
                    <p class="text-sm text-gray-500 dark:text-gray-400">Nenhum equipamento vinculado a este cliente.</p>
                @endif
            </div>
            <div id="painel-ordens-servico" class="tab-painel hidden">
                <h3 class="mb-4 text-lg font-semibold text-gray-800 dark:text-white/90">Ordens de Serviço do cliente</h3>
                @if($cliente->ordensServico->count())
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                            <thead>
                                <tr>
                                    <th class="px-4 py-2 text-left text-xs font-medium uppercase text-gray-500 dark:text-gray-400">Código</th>
                                    <th class="px-4 py-2 text-left text-xs font-medium uppercase text-gray-500 dark:text-gray-400">Status</th>
                                    <th class="px-4 py-2 text-left text-xs font-medium uppercase text-gray-500 dark:text-gray-400">Abertura</th>
                                    <th class="px-4 py-2 text-left text-xs font-medium uppercase text-gray-500 dark:text-gray-400">Equipamento</th>
                                    <th class="px-4 py-2 text-right text-xs font-medium uppercase text-gray-500 dark:text-gray-400">Total</th>
                                    <th class="px-4 py-2 text-left text-xs font-medium uppercase text-gray-500 dark:text-gray-400">Ações</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                                @foreach($cliente->ordensServico as $os)
                                <tr>
                                    <td class="px-4 py-3 text-sm font-medium text-gray-800 dark:text-white/90">{{ $os->codigo_interno ?? 'OS-' . $os->id }}</td>
                                    <td class="px-4 py-3">
                                        <span class="inline-flex rounded-full px-2 py-0.5 text-xs font-medium
                                            @if(in_array($os->status ?? '', ['Encerrado', 'Encerrada'])) bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300
                                            @elseif($os->status === 'Cancelado') bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-300
                                            @else bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-300
                                            @endif">{{ $os->status ?? 'Aberto' }}</span>
                                    </td>
                                    <td class="px-4 py-3 text-sm text-gray-700 dark:text-gray-300">{{ optional($os->data_abertura)->format('d/m/Y') ?? '-' }}</td>
                                    <td class="px-4 py-3 text-sm text-gray-700 dark:text-gray-300">{{ $os->equipamento_nome ?? $os->equipamento?->modelo ?? $os->equipamento?->marca ?? '-' }}</td>
                                    <td class="px-4 py-3 text-sm text-right text-gray-800 dark:text-white/90">R$ {{ number_format((float)($os->total_geral ?? 0), 2, ',', '.') }}</td>
                                    <td class="px-4 py-3">
                                        <a href="{{ route('ordens-servico.edit', $os) }}" class="text-brand-600 hover:text-brand-800 dark:text-brand-400 dark:hover:text-brand-300 text-sm font-medium">Abrir</a>
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @else
                    <p class="text-sm text-gray-500 dark:text-gray-400">Nenhuma ordem de serviço encontrada para este cliente.</p>
                @endif
            </div>

            <div id="painel-propostas" class="tab-painel hidden">
                <h3 class="mb-4 text-lg font-semibold text-gray-800 dark:text-white/90">Propostas comerciais</h3>
                @if($cliente->propostas->count())
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                            <thead>
                                <tr>
                                    <th class="px-4 py-2 text-left text-xs font-medium uppercase text-gray-500 dark:text-gray-400">Referência</th>
                                    <th class="px-4 py-2 text-left text-xs font-medium uppercase text-gray-500 dark:text-gray-400">Título</th>
                                    <th class="px-4 py-2 text-left text-xs font-medium uppercase text-gray-500 dark:text-gray-400">Emissão</th>
                                    <th class="px-4 py-2 text-left text-xs font-medium uppercase text-gray-500 dark:text-gray-400">Status</th>
                                    <th class="px-4 py-2 text-right text-xs font-medium uppercase text-gray-500 dark:text-gray-400">Total</th>
                                    <th class="px-4 py-2 text-left text-xs font-medium uppercase text-gray-500 dark:text-gray-400">Ações</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                                @foreach($cliente->propostas as $proposta)
                                    <tr>
                                        <td class="px-4 py-3 text-sm font-medium text-gray-800 dark:text-white/90">
                                            <a href="{{ route('propostas-comerciais.show', $proposta) }}" class="text-brand-600 hover:underline dark:text-brand-400">
                                                {{ $proposta->referenciaGrupo() }}
                                            </a>
                                        </td>
                                        <td class="px-4 py-3 text-sm text-gray-700 dark:text-gray-300">{{ $proposta->titulo ?: '—' }}</td>
                                        <td class="px-4 py-3 text-sm text-gray-700 dark:text-gray-300">{{ optional($proposta->data_emissao)->format('d/m/Y') ?: '—' }}</td>
                                        <td class="px-4 py-3 text-sm">
                                            <span class="inline-flex rounded-full px-2 py-0.5 text-[11px] font-medium bg-gray-100 text-gray-700 dark:bg-gray-700 dark:text-gray-300">
                                                {{ $proposta->status_label ?? ucfirst((string) $proposta->status) }}
                                            </span>
                                        </td>
                                        <td class="px-4 py-3 text-sm text-right font-medium text-gray-800 dark:text-white/90">
                                            R$ {{ number_format((float) ($proposta->total_geral ?? 0), 2, ',', '.') }}
                                        </td>
                                        <td class="px-4 py-3">
                                            <a href="{{ route('propostas-comerciais.show', $proposta) }}" class="text-brand-600 hover:underline dark:text-brand-400 text-sm font-medium">Ver</a>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @else
                    <p class="text-sm text-gray-500 dark:text-gray-400">Nenhuma proposta encontrada para este cliente.</p>
                @endif
            </div>

            <div id="painel-vendas" class="tab-painel hidden">
                <h3 class="mb-4 text-lg font-semibold text-gray-800 dark:text-white/90">PDV Vendas (Pedidos de venda)</h3>
                @if($cliente->vendas->count())
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                            <thead>
                                <tr>
                                    <th class="px-4 py-2 text-left text-xs font-medium uppercase text-gray-500 dark:text-gray-400">Nº Pedido</th>
                                    <th class="px-4 py-2 text-left text-xs font-medium uppercase text-gray-500 dark:text-gray-400">Data</th>
                                    <th class="px-4 py-2 text-left text-xs font-medium uppercase text-gray-500 dark:text-gray-400">Status</th>
                                    <th class="px-4 py-2 text-right text-xs font-medium uppercase text-gray-500 dark:text-gray-400">Total</th>
                                    <th class="px-4 py-2 text-left text-xs font-medium uppercase text-gray-500 dark:text-gray-400">Ações</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                                @foreach($cliente->vendas as $pedido)
                                    <tr>
                                        <td class="px-4 py-3 text-sm font-medium text-gray-800 dark:text-white/90">
                                            <a href="{{ route('pedidos-venda.show', $pedido) }}" class="text-brand-600 hover:underline dark:text-brand-400">
                                                {{ $pedido->numero_sequencial }}
                                            </a>
                                        </td>
                                        <td class="px-4 py-3 text-sm text-gray-700 dark:text-gray-300">
                                            {{ optional($pedido->data_pedido)->format('d/m/Y') ?? '-' }}
                                        </td>
                                        <td class="px-4 py-3 text-sm">
                                            @php
                                                $cor = $pedido->status_badge ?? 'gray';
                                            @endphp
                                            <span class="inline-flex rounded-full px-2 py-0.5 text-[11px] font-semibold
                                                @if($cor==='gray') bg-gray-100 text-gray-700 dark:bg-gray-700 dark:text-gray-300
                                                @elseif($cor==='sky') bg-sky-100 text-sky-700 dark:bg-sky-500/20 dark:text-sky-300
                                                @elseif($cor==='amber') bg-amber-100 text-amber-700 dark:bg-amber-500/20 dark:text-amber-300
                                                @elseif($cor==='green') bg-green-100 text-green-700 dark:bg-green-500/20 dark:text-green-300
                                                @elseif($cor==='red') bg-red-100 text-red-700 dark:bg-red-500/20 dark:text-red-300
                                                @else bg-gray-100 text-gray-700 dark:bg-gray-700 dark:text-gray-300
                                                @endif">
                                                {{ $pedido->status_label ?? ucfirst($pedido->status ?? '—') }}
                                            </span>
                                        </td>
                                        <td class="px-4 py-3 text-sm text-right font-medium text-gray-800 dark:text-white/90">
                                            R$ {{ number_format((float) ($pedido->total_geral ?? 0), 2, ',', '.') }}
                                        </td>
                                        <td class="px-4 py-3">
                                            <a href="{{ route('pedidos-venda.show', $pedido) }}" class="text-brand-600 hover:underline dark:text-brand-400 text-sm font-medium">Ver</a>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @else
                    <p class="text-sm text-gray-500 dark:text-gray-400">Nenhuma venda encontrada para este cliente.</p>
                @endif
            </div>
        </div>
    </div>
</div>

<script>
document.querySelectorAll('.tab-cliente').forEach(function(btn) {
    btn.addEventListener('click', function() {
        var tab = this.getAttribute('data-tab');
        document.querySelectorAll('.tab-cliente').forEach(function(b) {
            b.classList.remove('border-brand-500', 'text-brand-600', 'dark:border-brand-400', 'dark:text-brand-400');
            b.classList.add('border-transparent', 'text-gray-500', 'dark:text-gray-400');
            b.setAttribute('aria-current', null);
        });
        this.classList.remove('border-transparent', 'text-gray-500', 'dark:text-gray-400');
        this.classList.add('border-brand-500', 'text-brand-600', 'dark:border-brand-400', 'dark:text-brand-400');
        this.setAttribute('aria-current', 'page');
        document.querySelectorAll('.tab-painel').forEach(function(p) { p.classList.add('hidden'); });
        var painelId = 'painel-' + tab;
        var painel = document.getElementById(painelId);
        if (painel) painel.classList.remove('hidden');
    });
});
document.querySelector('.tab-cliente[data-tab="equipamentos"]').classList.add('border-brand-500', 'text-brand-600', 'dark:border-brand-400', 'dark:text-brand-400');
document.querySelector('.tab-cliente[data-tab="equipamentos"]').setAttribute('aria-current', 'page');
</script>
@endsection
