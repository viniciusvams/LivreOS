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
    $categorias = \App\Models\CategoriaProduto::where('ativo', true)->orderBy('nome')->get(['id', 'nome']);
    $produtos = \App\Models\Produto::orderBy('nome')->get(['id', 'nome', 'categoria_id']);
    $categoriasComSerial = get_option('categorias_com_serial', [], 'pdv');
    $produtosComSerial = get_option('produtos_com_serial', [], 'pdv');
    if (!is_array($categoriasComSerial)) {
        $categoriasComSerial = is_string($categoriasComSerial) ? (json_decode($categoriasComSerial, true) ?? []) : [];
    }
    if (!is_array($produtosComSerial)) {
        $produtosComSerial = is_string($produtosComSerial) ? (json_decode($produtosComSerial, true) ?? []) : [];
    }
    $categoriasComSerial = array_map('intval', $categoriasComSerial);
    $produtosComSerial = array_map('intval', $produtosComSerial);

    $clienteBalcaoModo = get_option('pdv_cliente_balcao_modo', 'auto', 'pdv');
    $clienteBalcaoId = get_option('pdv_cliente_balcao_id', null, 'pdv');
    $clientes = \App\Models\Cliente::orderBy('nome')->get(['id', 'nome']);
    $numeroVendaInicial = get_option('pdv_numero_venda_inicial', 1, 'pdv');
    $numeroVendaInicial = is_numeric($numeroVendaInicial) ? (int) $numeroVendaInicial : 1;
    if ($numeroVendaInicial < 1) $numeroVendaInicial = 1;

    // Dados para Usuários e permissões (habilitar caixa)
    $usuarios = \App\Models\User::orderBy('name')->get(['id', 'name', 'email']);
    $habilitados = get_option('pdv_usuarios_podem_abrir_caixa', null, 'pdv');
    if ($habilitados !== null && !is_array($habilitados)) {
        $habilitados = is_string($habilitados) ? (json_decode($habilitados, true) ?? []) : [];
    }
    $habilitados = $habilitados !== null ? array_map('intval', (array) $habilitados) : null;
    $permissoes = get_option('pdv_permissoes_usuarios', null, 'pdv');
    $permissoes = is_array($permissoes) ? $permissoes : [];
    $autorizadoresUserIds = get_option('pdv_autorizadores_user_ids', null, 'pdv');
    if ($autorizadoresUserIds !== null && !is_array($autorizadoresUserIds)) {
        $autorizadoresUserIds = is_string($autorizadoresUserIds) ? (json_decode($autorizadoresUserIds, true) ?? []) : [];
    }
    $autorizadoresUserIds = is_array($autorizadoresUserIds) ? array_values(array_map('intval', array_filter($autorizadoresUserIds))) : [];
    $descontoMaximoPercentual = get_option('pdv_desconto_maximo_percentual', null, 'pdv');
    $descontoMaximoPercentual = $descontoMaximoPercentual !== null && $descontoMaximoPercentual !== '' ? (float) $descontoMaximoPercentual : null;
    $quebraSobraLimite = get_option('pdv_quebra_sobra_limite_sem_aprovacao', 5, 'pdv');
    $quebraSobraLimite = $quebraSobraLimite !== null && $quebraSobraLimite !== '' ? (float) $quebraSobraLimite : 5.0;
    $controleEstoque = (bool) get_option('pdv_controle_estoque', false, 'pdv');
@endphp
@if(session('success'))
<div class="mb-4 rounded-lg border border-green-200 bg-green-50 p-4 text-sm text-green-800 dark:border-green-800 dark:bg-green-900/30 dark:text-green-200">
    {{ session('success') }}
</div>
@endif

{{-- Exportar / Importar dados --}}
<div class="mb-6 rounded-lg border border-gray-200 bg-white shadow-theme-sm dark:border-gray-800 dark:bg-gray-800">
    <div class="border-b border-gray-200 px-6 py-4 dark:border-gray-700">
        <h2 class="text-lg font-semibold text-gray-800 dark:text-white/90">Exportar / Importar dados</h2>
        <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Faça backup das configurações e dados do PDV (opções, caixas, vendas, orçamentos) ou restaure a partir de um arquivo exportado.</p>
    </div>
    <div class="p-6 flex flex-wrap items-center gap-3">
        <a href="{{ route('plugin.pdv.configuracoes.export') }}" class="inline-flex items-center gap-2 rounded-lg bg-brand-500 px-4 py-2.5 text-sm font-medium text-white hover:bg-brand-600 dark:bg-brand-600 dark:hover:bg-brand-700">
            <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
            Exportar dados (JSON)
        </a>
        <a href="{{ route('plugin.pdv.configuracoes.import') }}" class="inline-flex items-center gap-2 rounded-lg border border-gray-300 bg-white px-4 py-2.5 text-sm font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-300 dark:hover:bg-gray-700">
            <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"/></svg>
            Abrir tela de importação
        </a>
    </div>
</div>

<div class="mt-6 rounded-lg border border-gray-200 bg-white shadow-theme-sm dark:border-gray-800 dark:bg-gray-800">
    <div class="border-b border-gray-200 px-6 py-4 dark:border-gray-700">
        <h2 class="text-lg font-semibold text-gray-800 dark:text-white/90">Cliente padrão das vendas (Balcão)</h2>
        <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Define qual cliente será usado nas vendas sem cliente selecionado (venda balcão).</p>
    </div>
    <form action="{{ route('plugin.pdv.configuracoes.store') }}" method="POST" class="p-6 space-y-6" id="form-pdv-config">
        @csrf
        <div class="space-y-4">
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Modo</label>
                <div class="space-y-2">
                    <label class="flex items-center gap-2">
                        <input type="radio" name="pdv_cliente_balcao_modo" value="auto" {{ $clienteBalcaoModo === 'auto' ? 'checked' : '' }} class="rounded border-gray-300 text-brand-500 focus:ring-brand-500">
                        <span class="text-sm text-gray-700 dark:text-gray-300">Cadastrar automaticamente</span>
                    </label>
                    <p class="text-xs text-gray-500 dark:text-gray-400 ml-6">Cria ou usa um cliente chamado "Cliente Balcão" no sistema. Se já existir, será usado; caso contrário, será criado ao salvar.</p>
                    <label class="flex items-center gap-2 mt-3">
                        <input type="radio" name="pdv_cliente_balcao_modo" value="selecionar" {{ $clienteBalcaoModo === 'selecionar' ? 'checked' : '' }} class="rounded border-gray-300 text-brand-500 focus:ring-brand-500">
                        <span class="text-sm text-gray-700 dark:text-gray-300">Selecionar cliente padrão</span>
                    </label>
                    <div class="ml-6 mt-2" id="box-cliente-selecionar">
                        <select name="pdv_cliente_balcao_id" class="w-full max-w-md rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-900 text-gray-900 dark:text-white px-3 py-2 text-sm">
                            <option value="">— Selecione o cliente —</option>
                            @foreach($clientes as $c)
                                <option value="{{ $c->id }}" {{ (string)$clienteBalcaoId === (string)$c->id ? 'selected' : '' }}>{{ $c->nome }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
            </div>
        </div>
        <div class="border-t border-gray-200 pt-6 dark:border-gray-700">
            <h3 class="text-sm font-medium text-gray-800 dark:text-white/90 mb-2">Numeração das vendas</h3>
            <p class="text-xs text-gray-500 dark:text-gray-400 mb-3">Número inicial para exibição das vendas (ex.: 1 = primeira venda como #1; 1000 = primeira como #1000, segunda como #1001).</p>
            <input type="number" name="pdv_numero_venda_inicial" value="{{ $numeroVendaInicial }}" min="1" step="1" class="w-32 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-900 text-gray-900 dark:text-white px-3 py-2 text-sm">
        </div>
        <div class="border-t border-gray-200 pt-6 dark:border-gray-700">
            <h3 class="text-sm font-medium text-gray-800 dark:text-white/90 mb-2">Controle de estoque</h3>
            <label class="flex items-center gap-2 cursor-pointer mt-2">
                <input type="checkbox" name="pdv_controle_estoque" value="1" {{ $controleEstoque ? 'checked' : '' }} class="rounded border-gray-300 text-brand-500 focus:ring-brand-500 dark:border-gray-600 dark:bg-gray-700">
                <span class="text-sm text-gray-700 dark:text-gray-300">Impedir venda com estoque zerado ou insuficiente</span>
            </label>
            <p class="text-xs text-gray-500 dark:text-gray-400 mt-1 ml-6">Quando ativo, o PDV não permite finalizar venda se algum produto estiver sem estoque (ou quantidade solicitada maior que o disponível). Quem tiver permissão "Pode vender com estoque zerado" (em Usuários e permissões) poderá autorizar digitando a senha; a autorização fica registrada na venda.</p>
        </div>
        <div class="border-t border-gray-200 pt-6 dark:border-gray-700">
            <h3 class="text-sm font-medium text-gray-800 dark:text-white/90 mb-2">Categorias que exigem Serial/IMEI</h3>
            <select name="categorias_com_serial[]" multiple class="w-full rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-900 text-gray-900 dark:text-white min-h-[120px]">
                @foreach($categorias as $cat)
                    <option value="{{ $cat->id }}" {{ in_array($cat->id, $categoriasComSerial) ? 'selected' : '' }}>{{ $cat->nome }}</option>
                @endforeach
            </select>
            <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">Segure Ctrl (ou Cmd) para selecionar várias.</p>
        </div>
        <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Produtos que exigem Serial/IMEI</label>
            <select name="produtos_com_serial[]" multiple class="w-full rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-900 text-gray-900 dark:text-white min-h-[120px]">
                @foreach($produtos as $p)
                    <option value="{{ $p->id }}" {{ in_array($p->id, $produtosComSerial) ? 'selected' : '' }}>{{ $p->nome }}</option>
                @endforeach
            </select>
            <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">Segure Ctrl (ou Cmd) para selecionar vários. Sobrescreve a regra por categoria.</p>
        </div>
        <div class="flex justify-end">
            <button type="submit" class="rounded-lg bg-brand-500 px-4 py-2.5 text-sm font-medium text-white hover:bg-brand-600 dark:bg-brand-600 dark:hover:bg-brand-700">Salvar configurações</button>
        </div>
    </form>
</div>

{{-- Usuários e permissões (caixa, autorizadores, sangria) --}}
<div id="usuarios-permissoes" class="mt-6 rounded-lg border border-gray-200 bg-white shadow-theme-sm dark:border-gray-800 dark:bg-gray-800">
    <div class="border-b border-gray-200 px-6 py-4 dark:border-gray-700">
        <h2 class="text-lg font-semibold text-gray-800 dark:text-white/90">Usuários e permissões</h2>
        <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Marque os usuários que podem abrir caixa no PDV. Defina cancelamento total, parcial e sangria. Quem não tiver permissão poderá executar a ação digitando a senha de um autorizador com a mesma permissão (ou administrador).</p>
    </div>
    @if(session('success_usuarios'))
    <div class="mx-6 mt-4 rounded-lg border border-green-200 bg-green-50 p-4 text-sm text-green-800 dark:border-green-800 dark:bg-green-900/30 dark:text-green-200">
        {{ session('success_usuarios') }}
    </div>
    @endif
    <form action="{{ route('plugin.pdv.habilitar-caixa-usuarios.store') }}" method="POST" class="p-6">
        @csrf

        <div class="mb-6 rounded-lg border border-gray-200 bg-gray-50 p-4 dark:border-gray-600 dark:bg-gray-700/50">
            <h3 class="mb-2 text-sm font-semibold text-gray-800 dark:text-gray-200">Desconto máximo permitido (%)</h3>
            <p class="mb-3 text-xs text-gray-600 dark:text-gray-400">Na tela de pagamento do PDV, descontos acima deste percentual exigirão a senha de um usuário autorizador. Deixe em branco para não limitar desconto.</p>
            <input type="number" name="desconto_maximo_percentual" value="{{ $descontoMaximoPercentual !== null ? $descontoMaximoPercentual : '' }}"
                   min="0" max="100" step="0.5" placeholder="Ex: 10"
                   class="w-32 rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm text-gray-800 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200">
            <span class="ml-2 text-sm text-gray-500 dark:text-gray-400">%</span>
        </div>

        <div class="mb-6 rounded-lg border border-gray-200 bg-gray-50 p-4 dark:border-gray-600 dark:bg-gray-700/50">
            <h3 class="mb-2 text-sm font-semibold text-gray-800 dark:text-gray-200">Fechamento de caixa — limite para dispensar aprovação (R$)</h3>
            <p class="mb-3 text-xs text-gray-600 dark:text-gray-400">Se a diferença entre o valor contado e o valor do sistema (quebra ou sobra) for maior que este valor, o sistema exigirá a senha do administrador ou de um autorizador para concluir o fechamento.</p>
            <input type="number" name="quebra_sobra_limite_sem_aprovacao" value="{{ $quebraSobraLimite }}"
                   min="0" step="0.01" placeholder="5,00"
                   class="w-32 rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm text-gray-800 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200">
            <span class="ml-2 text-sm text-gray-500 dark:text-gray-400">R$</span>
        </div>

        <div class="mb-6 rounded-lg border border-gray-200 bg-gray-50 p-4 dark:border-gray-600 dark:bg-gray-700/50">
            <h3 class="mb-2 text-sm font-semibold text-gray-800 dark:text-gray-200">Usuários autorizadores</h3>
            <p class="mb-3 text-xs text-gray-600 dark:text-gray-400">Marque um ou mais usuários. Quando um vendedor não tiver permissão para cancelar venda ou fazer sangria, ou quando aplicar desconto acima do máximo, será pedida a senha de qualquer um destes usuários para autorizar.</p>
            <ul class="space-y-2">
                @foreach($usuarios as $u)
                <li>
                    <label class="flex items-center gap-2 cursor-pointer">
                        <input type="checkbox" name="autorizadores_user_ids[]" value="{{ $u->id }}"
                            @if(in_array((int)$u->id, $autorizadoresUserIds, true)) checked @endif
                            class="h-4 w-4 rounded border-gray-300 text-brand-600 focus:ring-brand-500 dark:border-gray-600 dark:bg-gray-700">
                        <span class="text-sm text-gray-800 dark:text-gray-200">{{ $u->name }} — {{ $u->email }}</span>
                    </label>
                </li>
                @endforeach
            </ul>
        </div>

        <p class="mb-4 text-sm text-gray-600 dark:text-gray-400">
            Se nenhum usuário for marcado em "Pode abrir caixa" e você salvar, apenas administradores poderão abrir caixa. Defina cancelamento total ou parcial por usuário e sangria; quem não tiver permissão poderá usar a senha de um autorizador que tenha a mesma permissão (ou administrador). No cadastro de papéis também existem as permissões <code class="text-xs">pdv.cancelar_venda_total</code> e <code class="text-xs">pdv.cancelar_venda_parcial</code>.
        </p>

        <ul class="space-y-3">
            @foreach($usuarios as $u)
            @php
                $uid = (string) $u->id;
                $perm = $permissoes[$uid] ?? [];
                $legadoCancel = (bool) ($perm['pode_cancelar_vendas'] ?? true);
                $hasGranular = array_key_exists('pode_cancelar_venda_total', $perm) || array_key_exists('pode_cancelar_venda_parcial', $perm);
                $podeCancelarTotal = $hasGranular ? (bool) ($perm['pode_cancelar_venda_total'] ?? false) : $legadoCancel;
                $podeCancelarParcial = $hasGranular ? (bool) ($perm['pode_cancelar_venda_parcial'] ?? false) : $legadoCancel;
                $podeSangria = $perm['pode_sangria'] ?? true;
                $podeVenderEstoqueZerado = $perm['pode_vender_estoque_zerado'] ?? false;
            @endphp
            <li class="rounded-lg border border-gray-200 p-4 dark:border-gray-600">
                <div class="flex flex-wrap items-center gap-x-4 gap-y-3">
                    <div class="flex items-center gap-2">
                        <input type="checkbox" name="usuarios[]" value="{{ $u->id }}" id="user-{{ $u->id }}"
                            @if($habilitados === null || in_array((int)$u->id, $habilitados ?? [], true)) checked @endif
                            class="h-4 w-4 rounded border-gray-300 text-brand-600 focus:ring-brand-500 dark:border-gray-600 dark:bg-gray-700">
                        <label for="user-{{ $u->id }}" class="cursor-pointer text-gray-800 dark:text-gray-200 font-medium">Pode abrir caixa</label>
                    </div>
                    <span class="text-gray-500 dark:text-gray-400">— {{ $u->name }} ({{ $u->email }})</span>
                </div>
                <div class="mt-3 flex flex-wrap gap-6 pl-6">
                    <label class="flex items-center gap-2 cursor-pointer">
                        <input type="hidden" name="permissoes[{{ $u->id }}][pode_cancelar_venda_total]" value="0">
                        <input type="checkbox" name="permissoes[{{ $u->id }}][pode_cancelar_venda_total]" value="1" id="pct-{{ $u->id }}"
                            @if($podeCancelarTotal) checked @endif
                            class="h-4 w-4 rounded border-gray-300 text-brand-600 focus:ring-brand-500 dark:border-gray-600 dark:bg-gray-700">
                        <span class="text-sm text-gray-700 dark:text-gray-300">Cancelar venda (total)</span>
                    </label>
                    <label class="flex items-center gap-2 cursor-pointer">
                        <input type="hidden" name="permissoes[{{ $u->id }}][pode_cancelar_venda_parcial]" value="0">
                        <input type="checkbox" name="permissoes[{{ $u->id }}][pode_cancelar_venda_parcial]" value="1" id="pcp-{{ $u->id }}"
                            @if($podeCancelarParcial) checked @endif
                            class="h-4 w-4 rounded border-gray-300 text-brand-600 focus:ring-brand-500 dark:border-gray-600 dark:bg-gray-700">
                        <span class="text-sm text-gray-700 dark:text-gray-300">Cancelar itens (parcial)</span>
                    </label>
                    <label class="flex items-center gap-2 cursor-pointer">
                        <input type="hidden" name="permissoes[{{ $u->id }}][pode_sangria]" value="0">
                        <input type="checkbox" name="permissoes[{{ $u->id }}][pode_sangria]" value="1" id="ps-{{ $u->id }}"
                            @if($podeSangria) checked @endif
                            class="h-4 w-4 rounded border-gray-300 text-brand-600 focus:ring-brand-500 dark:border-gray-600 dark:bg-gray-700">
                        <span class="text-sm text-gray-700 dark:text-gray-300">Pode fazer sangria</span>
                    </label>
                    <label class="flex items-center gap-2 cursor-pointer">
                        <input type="hidden" name="permissoes[{{ $u->id }}][pode_vender_estoque_zerado]" value="0">
                        <input type="checkbox" name="permissoes[{{ $u->id }}][pode_vender_estoque_zerado]" value="1" id="pvz-{{ $u->id }}"
                            @if($podeVenderEstoqueZerado) checked @endif
                            class="h-4 w-4 rounded border-gray-300 text-brand-600 focus:ring-brand-500 dark:border-gray-600 dark:bg-gray-700">
                        <span class="text-sm text-gray-700 dark:text-gray-300">Pode vender com estoque zerado</span>
                    </label>
                </div>
            </li>
            @endforeach
        </ul>

        @if($usuarios->isEmpty())
            <p class="py-4 text-gray-500 dark:text-gray-400">Nenhum usuário cadastrado.</p>
        @endif

        <div class="mt-6 flex justify-end">
            <button type="submit" class="rounded-lg bg-brand-500 px-4 py-2.5 text-sm font-medium text-white hover:bg-brand-600 dark:bg-brand-600 dark:hover:bg-brand-700">Salvar usuários e permissões</button>
        </div>
    </form>
</div>
<script>
document.addEventListener('DOMContentLoaded', function() {
    var form = document.getElementById('form-pdv-config');
    if (!form) return;
    var radios = form.querySelectorAll('input[name="pdv_cliente_balcao_modo"]');
    var box = document.getElementById('box-cliente-selecionar');
    function atualizar() {
        var selecionar = form.querySelector('input[name="pdv_cliente_balcao_modo"]:checked');
        if (box && selecionar) {
            box.style.opacity = selecionar.value === 'selecionar' ? '1' : '0.6';
            box.style.pointerEvents = selecionar.value === 'selecionar' ? 'auto' : 'none';
        }
    }
    radios.forEach(function(r) { r.addEventListener('change', atualizar); });
    atualizar();
});
</script>
