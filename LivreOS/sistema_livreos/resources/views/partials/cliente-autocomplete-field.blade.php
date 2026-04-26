{{--
    Campo cliente com busca + datalist + select oculto (mesmo padrão de financeiro/contas-receber/create).

    Parâmetros esperados:
    - $prefix (string) — prefixo único dos ids, ex.: conta_receber, pedido_venda
    - $clientes — coleção com id, nome; opcional: tipo_pessoa, cpf, cnpj, razao_social, bloqueado_vendas, grupo_economico_id
    - $selectedId — valor selecionado (ex.: old('cliente_id', $model?->cliente_id))
    - $idSuffix (string, opcional) — sufixo nos ids, ex. '_edit' → …_cliente_busca_edit
    - $required (bool, opcional, default true)
    - $label (string, opcional)
    - $labelClass, $inputClass (opcional)
    - $showBloqueadoVendasHint (bool, opcional) — sufixo "— bloqueado p/ vendas" no rótulo (pedidos)
    - $extraSelectAttributes (string, opcional) — ex.: @change="aoMudarCliente($event)" para Alpine
    - $dispatchChangeOnSync (bool, opcional) — dispara event change no select ao escolher pelo datalist
    - $errorName (string, opcional, default cliente_id)
--}}
@php
    $prefix = $prefix ?? 'cliente_ac';
    $idSuffix = $idSuffix ?? '';
    $buscaId = $prefix . '_cliente_busca' . $idSuffix;
    $datalistId = $prefix . '_clientes_datalist' . $idSuffix;
    $selectId = $prefix . '_cliente_id' . $idSuffix;
    $required = $required ?? true;
    $selectedId = $selectedId ?? old('cliente_id');
    $showBloqueadoVendasHint = $showBloqueadoVendasHint ?? false;
    $label = $label ?? 'Cliente';
    $labelClass = $labelClass ?? 'mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300';
    $inputClass = $inputClass ?? 'mb-2 w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm text-gray-800 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90';
    $extraSelectAttributes = $extraSelectAttributes ?? '';
    $dispatchChangeOnSync = $dispatchChangeOnSync ?? false;
    $errorName = $errorName ?? 'cliente_id';
@endphp
<div>
    <label class="{{ $labelClass }}" for="{{ $buscaId }}">{{ $label }} @if($required)<span class="text-red-500">*</span>@endif</label>
    <input
        type="text"
        id="{{ $buscaId }}"
        list="{{ $datalistId }}"
        placeholder="Digite nome, razão social, CPF ou CNPJ..."
        class="{{ $inputClass }}"
        autocomplete="off"
    >
    <datalist id="{{ $datalistId }}">
        @foreach($clientes as $cliente)
            @php
                $docCli = ($cliente->tipo_pessoa ?? '') === 'F' ? ($cliente->cpf ?? '') : ($cliente->cnpj ?? '');
                $rotuloBase = trim($cliente->nome . (($cliente->razao_social ?? '') ? ' - ' . $cliente->razao_social : '') . ($docCli ? ' - ' . $docCli : ''));
                $bloqueadoHint = ($showBloqueadoVendasHint && !empty($cliente->bloqueado_vendas)) ? ' — bloqueado p/ vendas' : '';
                $rotuloCli = $rotuloBase . $bloqueadoHint;
            @endphp
            <option value="{{ $rotuloCli }}"></option>
        @endforeach
    </datalist>
    <select name="cliente_id" id="{{ $selectId }}" @if($required) required @endif class="hidden" {!! $extraSelectAttributes !!}>
        <option value="">Selecione...</option>
        @foreach($clientes as $cliente)
            @php
                $docCli = ($cliente->tipo_pessoa ?? '') === 'F' ? ($cliente->cpf ?? '') : ($cliente->cnpj ?? '');
                $rotuloBase = trim($cliente->nome . (($cliente->razao_social ?? '') ? ' - ' . $cliente->razao_social : '') . ($docCli ? ' - ' . $docCli : ''));
                $bloqueadoHint = ($showBloqueadoVendasHint && !empty($cliente->bloqueado_vendas)) ? ' — bloqueado p/ vendas' : '';
                $rotuloCli = $rotuloBase . $bloqueadoHint;
                $buscaCli = strtolower(trim(
                    $cliente->nome . ' ' .
                    ($cliente->razao_social ?? '') . ' ' .
                    ($cliente->cpf ?? '') . ' ' .
                    ($cliente->cnpj ?? '') .
                    ($bloqueadoHint !== '' ? ' bloqueado vendas' : '')
                ));
            @endphp
            <option value="{{ $cliente->id }}"
                    data-label="{{ $rotuloCli }}"
                    data-search="{{ $buscaCli }}"
                    data-grupo="{{ $cliente->grupo_economico_id ?? '' }}"
                    {{ (string) $selectedId !== '' && (int) $selectedId === (int) $cliente->id ? 'selected' : '' }}>{{ $rotuloCli }}</option>
        @endforeach
    </select>
    @error($errorName)<p class="mt-1 text-xs text-red-600 dark:text-red-400">{{ $message }}</p>@enderror
</div>
<script>
document.addEventListener('DOMContentLoaded', function () {
    (function () {
        const selectId = @json($selectId);
        const buscaId = @json($buscaId);
        const dispatchChange = @json($dispatchChangeOnSync);
        const clienteSelect = document.getElementById(selectId);
        const clienteBusca = document.getElementById(buscaId);
        if (!clienteSelect || !clienteBusca) return;

        const labelToId = {};
        Array.from(clienteSelect.options).forEach(function (opt) {
            if (!opt.value) return;
            const label = (opt.dataset.label || opt.textContent || '').trim();
            if (label) labelToId[label] = opt.value;
        });

        const syncBuscaFromSelect = function () {
            const sel = clienteSelect.selectedOptions && clienteSelect.selectedOptions[0];
            if (sel && sel.value) {
                clienteBusca.value = sel.dataset.label || sel.textContent || '';
            }
        };

        const filtrar = function () {
            const q = (clienteBusca.value || '').toLowerCase().trim();
            Array.from(clienteSelect.options).forEach(function (opt) {
                if (!opt.value) return;
                const hay = (opt.dataset.search || (opt.textContent || '').toLowerCase());
                opt.hidden = q !== '' && !hay.includes(q);
            });
        };

        const syncSelectFromBusca = function () {
            const typed = clienteBusca.value.trim();
            const id = labelToId[typed];
            if (id) {
                clienteSelect.value = id;
                if (dispatchChange) {
                    clienteSelect.dispatchEvent(new Event('change'));
                }
            }
        };

        clienteBusca.addEventListener('input', function () {
            filtrar();
            syncSelectFromBusca();
        });
        clienteBusca.addEventListener('change', syncSelectFromBusca);
        clienteSelect.addEventListener('change', syncBuscaFromSelect);
        syncBuscaFromSelect();
    })();
});
</script>
