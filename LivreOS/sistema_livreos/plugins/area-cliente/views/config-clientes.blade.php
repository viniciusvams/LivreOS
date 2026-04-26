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
<div class="mb-6 flex flex-wrap items-start justify-between gap-4">
    <div>
        <h1 class="mb-2 text-2xl font-semibold text-gray-800 dark:text-white/90">Área do Cliente — Configuração</h1>
        <p class="text-gray-600 dark:text-gray-400">Aqui você define quais clientes acessam o portal (ordens de serviço, tickets e perfil). Esta permissão <strong>não</strong> fica no cadastro do cliente — apenas nesta tela.</p>
    </div>
    <a href="{{ route('configuracoes-sistema.plugins.show', 'area-cliente') }}" class="rounded-lg border border-gray-300 bg-white px-4 py-2.5 text-sm font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-300">
        Exportar / Importar dados
    </a>
</div>

@if(session('success'))
<div class="mb-4 rounded-lg bg-success-100 px-4 py-3 text-sm text-success-700 dark:bg-success-500/20 dark:text-success-400">
    {{ session('success') }}
</div>
@endif

@if(session('error'))
<div class="mb-4 rounded-lg bg-error-100 px-4 py-3 text-sm text-error-700 dark:bg-error-500/20 dark:text-error-400">
    {{ session('error') }}
</div>
@endif

<div class="mb-6 rounded-xl border-2 border-brand-200 bg-brand-50 p-4 dark:border-brand-700 dark:bg-brand-900/20">
    <h2 class="mb-2 text-base font-semibold text-gray-800 dark:text-white/90">URL para o cliente acessar a Área do Cliente</h2>
    <p class="mb-2 text-sm text-gray-600 dark:text-gray-400">Informe ao cliente o endereço abaixo para ele acessar o portal (após fazer login no sistema com o usuário vinculado ao cliente):</p>
    <p class="break-all rounded-lg bg-white px-3 py-2 font-mono text-sm text-gray-800 dark:bg-gray-800 dark:text-gray-200">{{ url()->route('plugin.area-cliente.index') }}</p>
</div>

{{-- Filtros --}}
<div class="mb-6 rounded-lg border border-gray-200 bg-white p-4 shadow-theme-sm dark:border-gray-800 dark:bg-gray-800">
    <form method="GET" action="{{ route('plugin.area-cliente.configuracoes.clientes') }}" class="flex flex-wrap gap-4">
        <div class="flex-1 min-w-[200px]">
            <input type="text" name="busca" value="{{ request('busca') }}" placeholder="Buscar por nome, razão social ou documento..."
                class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm dark:border-gray-700 dark:bg-gray-900 dark:text-white/90">
        </div>
        <div>
            <select name="portal" class="rounded-lg border border-gray-300 px-4 py-2.5 text-sm dark:border-gray-700 dark:bg-gray-900 dark:text-white/90">
                <option value="">Todos</option>
                <option value="habilitado" {{ request('portal') === 'habilitado' ? 'selected' : '' }}>Habilitados</option>
                <option value="desabilitado" {{ request('portal') === 'desabilitado' ? 'selected' : '' }}>Desabilitados</option>
            </select>
        </div>
        <div class="w-28">
            <input type="number" name="cliente" value="{{ request('cliente') }}" min="1" placeholder="ID"
                title="Filtrar por ID do cliente (ex.: vindo do cadastro)"
                class="w-full rounded-lg border border-gray-300 px-3 py-2.5 text-sm dark:border-gray-700 dark:bg-gray-900 dark:text-white/90">
        </div>
        <button type="submit" class="rounded-lg bg-brand-500 px-4 py-2.5 text-sm font-medium text-white hover:bg-brand-600">Filtrar</button>
    </form>
</div>

{{-- Resumo --}}
<div class="mb-4 flex flex-wrap gap-4 text-sm text-gray-600 dark:text-gray-400">
    <span><strong>{{ $clientes->total() }}</strong> cliente(s) encontrado(s)</span>
    <span>Habilitados: <strong>{{ \App\Models\Cliente::where('portal_suporte', true)->count() }}</strong></span>
</div>

<div class="overflow-hidden rounded-lg border border-gray-200 bg-white shadow-theme-sm dark:border-gray-800 dark:bg-gray-800">
    <div class="overflow-x-auto">
            <table class="w-full">
                <thead class="bg-gray-50 dark:bg-gray-900">
                    <tr>
                        <th class="px-6 py-4 text-left text-sm font-medium text-gray-700 dark:text-gray-300">Cliente</th>
                        <th class="px-6 py-4 text-left text-sm font-medium text-gray-700 dark:text-gray-300">Documento</th>
                        <th class="px-6 py-4 text-left text-sm font-medium text-gray-700 dark:text-gray-300">Usuários vinculados</th>
                        <th class="px-6 py-4 text-left text-sm font-medium text-gray-700 dark:text-gray-300">Status</th>
                        <th class="px-6 py-4 text-right text-sm font-medium text-gray-700 dark:text-gray-300">Ações</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                    @forelse($clientes as $cliente)
                    <tr class="hover:bg-gray-50 dark:hover:bg-gray-800/50">
                        <td class="px-6 py-4">
                            <a href="{{ route('clientes.edit', $cliente) }}" class="font-medium text-brand-600 hover:text-brand-700 dark:text-brand-400">
                                {{ $cliente->nome ?: $cliente->razao_social ?: 'Cliente #' . $cliente->id }}
                            </a>
                        </td>
                        <td class="px-6 py-4 text-sm text-gray-600 dark:text-gray-400">
                            {{ $cliente->documento_principal ?? $cliente->cpf ?? $cliente->cnpj ?? '-' }}
                        </td>
                        <td class="px-6 py-4 text-sm text-gray-600 dark:text-gray-400">
                            @php $usersCount = $usersPorCliente[$cliente->id] ?? 0; @endphp
                            @if($usersCount > 0)
                                <span class="text-success-600 dark:text-success-400">{{ $usersCount }} usuário(s)</span>
                            @else
                                <span class="text-amber-600 dark:text-amber-400">Nenhum</span>
                            @endif
                        </td>
                        <td class="px-6 py-4">
                            <div class="flex flex-col items-start gap-2 sm:flex-row sm:items-center sm:gap-3">
                                @if($cliente->portal_suporte ?? false)
                                    <span class="inline-flex shrink-0 rounded-full bg-success-100 px-2.5 py-0.5 text-xs font-medium text-success-800 dark:bg-success-900/30 dark:text-success-400">Habilitado</span>
                                    <form method="POST" action="{{ route('plugin.area-cliente.configuracoes.clientes.portal-suporte', $cliente) }}" class="inline">
                                        @csrf
                                        <input type="hidden" name="habilitar" value="0">
                                        <button type="submit" class="rounded-lg border border-gray-300 bg-white px-3 py-1.5 text-xs font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-300 dark:hover:bg-gray-700">
                                            Desativar
                                        </button>
                                    </form>
                                @else
                                    <span class="inline-flex shrink-0 rounded-full bg-gray-100 px-2.5 py-0.5 text-xs font-medium text-gray-600 dark:bg-gray-700 dark:text-gray-400">Desabilitado</span>
                                    <form method="POST" action="{{ route('plugin.area-cliente.configuracoes.clientes.portal-suporte', $cliente) }}" class="inline">
                                        @csrf
                                        <input type="hidden" name="habilitar" value="1">
                                        <button type="submit" class="rounded-lg bg-brand-500 px-3 py-1.5 text-xs font-medium text-white hover:bg-brand-600">
                                            Ativar
                                        </button>
                                    </form>
                                @endif
                            </div>
                        </td>
                        <td class="px-6 py-4 text-right">
                            <button type="button" onclick="abrirModalUsuario({{ $cliente->id }}, '{{ addslashes($cliente->nome ?: $cliente->razao_social ?: 'Cliente #'.$cliente->id) }}')"
                                class="rounded-lg border border-gray-300 bg-white px-3 py-1.5 text-xs font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-300">
                                Gerenciar usuário
                            </button>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="5" class="px-6 py-12 text-center text-gray-500 dark:text-gray-400">
                            Nenhum cliente encontrado. Ajuste os filtros ou cadastre clientes.
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
    </div>
    @if($clientes->hasPages())
    <div class="border-t border-gray-200 px-4 py-3 dark:border-gray-700">
        {{ $clientes->links() }}
    </div>
    @endif
</div>

{{-- Modal Gerenciar Usuário --}}
<div id="modalUsuarioCliente" class="fixed inset-0 z-50 hidden items-center justify-center bg-black/50 p-4" onclick="if(event.target===this)fecharModalUsuario()">
    <div class="max-h-[90vh] w-full max-w-lg overflow-hidden rounded-lg bg-white shadow-xl dark:bg-gray-800" onclick="event.stopPropagation()">
        <div class="flex items-center justify-between border-b border-gray-200 px-4 py-3 dark:border-gray-700">
            <h3 class="text-lg font-semibold text-gray-800 dark:text-white/90" id="modalUsuarioTitulo">Usuário do cliente</h3>
            <button type="button" onclick="fecharModalUsuario()" class="text-gray-500 hover:text-gray-700 dark:hover:text-gray-400">✕</button>
        </div>
        <div class="max-h-[70vh] overflow-y-auto p-4" id="modalUsuarioConteudo">
            <div class="flex items-center justify-center py-8">
                <span class="text-gray-500 dark:text-gray-400">Carregando...</span>
            </div>
        </div>
    </div>
</div>

<div class="mt-6 rounded-lg border border-gray-200 bg-gray-50 p-4 dark:border-gray-700 dark:bg-gray-800/50">
    <h3 class="mb-2 text-sm font-semibold text-gray-800 dark:text-white/90">Como funciona</h3>
    <ul class="list-inside list-disc space-y-1 text-sm text-gray-600 dark:text-gray-400">
        <li>Na coluna <strong>Status</strong>, use <strong>Ativar</strong> ou <strong>Desativar</strong> para cada cliente.</li>
        <li>Use o campo <strong>ID</strong> nos filtros para localizar um cliente (ex.: <code class="rounded bg-gray-200 px-1 dark:bg-gray-700">?cliente=148</code> na URL).</li>
        <li>Para o cliente acessar, é necessário criar um <strong>usuário</strong> vinculado a ele (em Admin → Usuários, campo Cliente).</li>
        <li>O link para configurações gerais (exportar/importar) está em Configurações → Plugins → Área do Cliente.</li>
    </ul>
</div>

<script>
function abrirModalUsuario(clienteId, clienteNome) {
    const modal = document.getElementById('modalUsuarioCliente');
    const titulo = document.getElementById('modalUsuarioTitulo');
    const conteudo = document.getElementById('modalUsuarioConteudo');
    if (!modal || !conteudo) return;
    titulo.textContent = 'Usuário — ' + clienteNome;
    conteudo.innerHTML = '<div class="flex items-center justify-center py-8"><span class="text-gray-500 dark:text-gray-400">Carregando...</span></div>';
    modal.classList.remove('hidden');
    modal.classList.add('flex');
    document.body.style.overflow = 'hidden';

    const baseUrl = '{{ route("plugin.area-cliente.configuracoes.clientes.usuarios.modal", ["cliente" => "__ID__"]) }}'.replace('__ID__', clienteId);
    const url = baseUrl + (window.location.search || '');
    fetch(url, { headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' } })
        .then(r => r.json())
        .then(data => {
            if (data.html) {
                conteudo.innerHTML = data.html;
            } else {
                conteudo.innerHTML = '<p class="text-error-600 dark:text-error-400">Erro ao carregar.</p>';
            }
        })
        .catch(() => {
            conteudo.innerHTML = '<p class="text-error-600 dark:text-error-400">Erro ao carregar.</p>';
        });
}

function fecharModalUsuario() {
    const modal = document.getElementById('modalUsuarioCliente');
    if (modal) {
        modal.classList.add('hidden');
        modal.classList.remove('flex');
        document.body.style.overflow = '';
    }
}

function abrirFormEditar(userId) {
    const form = document.getElementById('formEditar' + userId);
    if (form) form.classList.remove('hidden');
}

function fecharFormEditar(userId) {
    const form = document.getElementById('formEditar' + userId);
    if (form) form.classList.add('hidden');
}

function buscarCepNoModal(btn) {
    const form = btn.closest('form');
    if (!form) return;
    const cepEl = form.querySelector('.cep-input');
    const logradouro = form.querySelector('.cep-logradouro');
    const bairro = form.querySelector('.cep-bairro');
    const cidade = form.querySelector('.cep-cidade');
    const uf = form.querySelector('.cep-uf');
    if (!cepEl) return;
    const val = (cepEl.value || '').replace(/\D/g, '');
    if (val.length !== 8) { alert('Informe um CEP com 8 dígitos.'); return; }
    btn.disabled = true;
    const cepUrl = '{{ route("plugin.area-cliente.configuracoes.cep.buscar", ["cep" => "__CEP__"]) }}'.replace('__CEP__', val);
    fetch(cepUrl, { headers: { 'Accept': 'application/json' } })
        .then(r => r.json())
        .then(data => {
            if (data.erro) { alert('CEP não encontrado.'); return; }
            if (logradouro) logradouro.value = data.logradouro || '';
            if (bairro) bairro.value = data.bairro || '';
            if (cidade) cidade.value = data.localidade || '';
            if (uf) uf.value = (data.uf || '').toUpperCase();
        })
        .catch(() => alert('Erro ao buscar CEP. Verifique sua conexão.'))
        .finally(() => { btn.disabled = false; });
}

document.addEventListener('click', function(e) {
    const btn = e.target.closest('.btn-buscar-cep');
    if (!btn) return;
    e.preventDefault();
    e.stopPropagation();
    buscarCepNoModal(btn);
});

document.addEventListener('blur', function(e) {
    if (!e.target.classList.contains('cep-input')) return;
    const val = (e.target.value || '').replace(/\D/g, '');
    if (val.length !== 8) return;
    const form = e.target.closest('form');
    if (!form) return;
    const btn = form.querySelector('.btn-buscar-cep');
    if (btn) buscarCepNoModal(btn);
}, true);
</script>
@endsection
