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

{{--
  Cartão: adquirente, bandeira, antecipação (crédito) e prévia de taxa.
  Variáveis: $prefix (string único), $bandeirasCartao, $formasCartaoMeta (array), $cfg (array para JS)
  $cfg keys: formaSel, valorSel, parcelasSel|null, dataRefSel, defaultAdquirenteId|null, defaultBandeira|null,
  contaBancariaSel|null (ex.: #conta_bancaria_id — ao mudar adquirente, define conta padrão do adquirente se existir)
--}}
@php
    $pfx = $prefix ?? 'crc';
    $bandeirasCartao = $bandeirasCartao ?? collect();
    $formasCartaoMeta = $formasCartaoMeta ?? [];
    $cfg = $cfg ?? [];
    $__crCartaoJsCfg = array_merge([
        'prefix' => $pfx,
        'meta' => $formasCartaoMeta,
        'previewUrl' => route('financeiro.contas-receber.preview-taxa-cartao'),
        'previewDisable' => false,
    ], $cfg);
@endphp
<div id="{{ $pfx }}_bloco_cartao" class="md:col-span-2 hidden space-y-4 rounded-lg border border-indigo-200 bg-indigo-50/60 p-4 dark:border-indigo-800 dark:bg-indigo-950/40">
    <h4 class="text-sm font-semibold text-indigo-900 dark:text-indigo-200">Cartão — adquirente e taxas</h4>
    <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
        <div>
            <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300" for="{{ $pfx }}_adquirente_id">Adquirente *</label>
            <select name="adquirente_id" id="{{ $pfx }}_adquirente_id" class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm text-gray-800 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90">
                <option value="">Selecione a forma cartão primeiro…</option>
            </select>
            @error('adquirente_id')<p class="mt-1 text-xs text-red-600 dark:text-red-400">{{ $message }}</p>@enderror
        </div>
        <div>
            <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300" for="{{ $pfx }}_bandeira">Bandeira *</label>
            <select name="bandeira" id="{{ $pfx }}_bandeira" class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm text-gray-800 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90">
                @foreach($bandeirasCartao as $b)
                    <option value="{{ $b }}" {{ old('bandeira', $cfg['defaultBandeira'] ?? 'master') === $b ? 'selected' : '' }}>{{ ucfirst($b) }}</option>
                @endforeach
            </select>
        </div>
        <div id="{{ $pfx }}_antecipacao_wrap" class="hidden md:col-span-2">
            <label class="flex cursor-pointer items-center gap-2 text-sm text-gray-700 dark:text-gray-300">
                <input type="checkbox" name="cartao_antecipacao" value="1" id="{{ $pfx }}_cartao_antecipacao" class="rounded border-gray-300 dark:border-gray-600" {{ old('cartao_antecipacao') ? 'checked' : '' }}>
                <span>Recebimento antecipado (crédito)</span>
            </label>
        </div>
    </div>
    <div id="{{ $pfx }}_preview_taxa" class="rounded border border-indigo-100 bg-white/80 px-3 py-2 text-sm text-gray-700 dark:border-indigo-900 dark:bg-gray-900/50 dark:text-gray-300"></div>
</div>

@push('scripts')
<script>
(function () {
    const CFG = @json($__crCartaoJsCfg);

    function initCrCartaoBloco() {
        const p = CFG.prefix;
        const bloco = document.getElementById(p + '_bloco_cartao');
        const selAdq = document.getElementById(p + '_adquirente_id');
        const selBand = document.getElementById(p + '_bandeira');
        const chkAnt = document.getElementById(p + '_cartao_antecipacao');
        const wrapAnt = document.getElementById(p + '_antecipacao_wrap');
        const prevEl = document.getElementById(p + '_preview_taxa');
        const formaEl = document.querySelector(CFG.formaSel);
        if (!bloco || !selAdq || !formaEl || !prevEl) return;

        function metaForForma(formaId) {
            var m = CFG.meta;
            if (formaId === '' || formaId == null || !m || typeof m !== 'object' || Array.isArray(m)) return null;
            var sid = String(formaId);
            return m[sid] || m[formaId] || null;
        }

        function getValorNum() {
            const el = document.querySelector(CFG.valorSel);
            if (!el) return 0;
            return parseFloat(String(el.value).replace(',', '.')) || 0;
        }
        function getParcelas() {
            if (CFG.parcelasHiddenSel) {
                const h = document.querySelector(CFG.parcelasHiddenSel);
                if (h && h.value) return Math.max(1, parseInt(h.value, 10) || 1);
            }
            if (CFG.parcelasSel) {
                const el = document.querySelector(CFG.parcelasSel);
                if (el) return Math.max(1, parseInt(el.value, 10) || 1);
            }
            if (CFG.totalParcelasFixed != null) return Math.max(1, parseInt(CFG.totalParcelasFixed, 10) || 1);
            return 1;
        }
        function getDataRef() {
            const el = CFG.dataRefSel ? document.querySelector(CFG.dataRefSel) : null;
            return el && el.value ? el.value : null;
        }
        function tipoForma() {
            const opt = formaEl.options[formaEl.selectedIndex];
            if (!opt || !opt.dataset.tipo) return '';
            return String(opt.dataset.tipo).trim().toLowerCase();
        }
        function toggleBloco() {
            const tipo = tipoForma();
            const isCard = tipo === 'cartao_credito' || tipo === 'cartao_debito';
            bloco.classList.toggle('hidden', !isCard);
            if (wrapAnt) wrapAnt.classList.toggle('hidden', tipo !== 'cartao_credito');
            if (isCard) {
                populateAdquirentes(formaEl.value);
                schedulePreview();
            } else prevEl.textContent = '';
        }
        function defaultAdqFromHidden() {
            const h = CFG.defaultAdquirenteHiddenSel ? document.querySelector(CFG.defaultAdquirenteHiddenSel) : null;
            return h && h.value ? String(h.value) : (CFG.defaultAdquirenteId ? String(CFG.defaultAdquirenteId) : '');
        }
        function defaultBandFromHidden() {
            const h = CFG.defaultBandeiraHiddenSel ? document.querySelector(CFG.defaultBandeiraHiddenSel) : null;
            return h && h.value ? String(h.value) : (CFG.defaultBandeira || 'master');
        }
        function adquirenteRow(formaId, adqId) {
            if (!adqId) return null;
            const meta = metaForForma(formaId);
            if (!meta || !meta.adquirentes) return null;
            return meta.adquirentes.find(function (x) { return String(x.id) === String(adqId); }) || null;
        }
        function aplicarContaBancariaPadraoDoAdquirente(adqId) {
            if (!CFG.contaBancariaSel) return;
            const cb = document.querySelector(CFG.contaBancariaSel);
            if (!cb) return;
            const row = adquirenteRow(formaEl.value, adqId);
            const cid = row && row.conta_bancaria_padrao_id ? String(row.conta_bancaria_padrao_id) : '';
            if (!cid) return;
            if (cb.querySelector('option[value="' + cid + '"]')) cb.value = cid;
        }
        function populateAdquirentes(formaId) {
            const meta = metaForForma(formaId);
            const cur = selAdq.value;
            selAdq.innerHTML = '<option value="">Selecione…</option>';
            if (meta && meta.adquirentes && meta.adquirentes.length) {
                meta.adquirentes.forEach(function (a) {
                    const o = document.createElement('option');
                    o.value = String(a.id);
                    o.textContent = a.nome;
                    selAdq.appendChild(o);
                });
            }
            const def = defaultAdqFromHidden();
            if (cur && [...selAdq.options].some(function (o) { return o.value === cur; })) selAdq.value = cur;
            else if (def && [...selAdq.options].some(function (o) { return o.value === def; })) selAdq.value = def;
            const db = defaultBandFromHidden();
            if (db && selBand) {
                const opt = [...selBand.options].find(function (o) { return o.value === db; });
                if (opt) selBand.value = db;
            }
            aplicarContaBancariaPadraoDoAdquirente(selAdq.value);
        }
        let tmo = null;
        function schedulePreview() {
            clearTimeout(tmo);
            tmo = setTimeout(runPreview, 350);
        }
        function runPreview() {
            if (CFG.previewDisable) {
                prevEl.textContent = '';
                return;
            }
            if (bloco.classList.contains('hidden')) return;
            const tipo = tipoForma();
            if (tipo !== 'cartao_credito' && tipo !== 'cartao_debito') return;
            const valor = getValorNum();
            const adq = selAdq.value;
            if (valor <= 0 || !adq) {
                prevEl.textContent = '';
                return;
            }
            const token = document.querySelector('meta[name="csrf-token"]');
            const body = new URLSearchParams({
                _token: token ? token.getAttribute('content') : '',
                valor: String(valor),
                forma_pagamento_id: formaEl.value,
                adquirente_id: adq,
                bandeira: selBand.value || 'master',
                numero_parcelas: String(getParcelas()),
                cartao_antecipacao: (chkAnt && chkAnt.checked) ? '1' : '0',
            });
            const dr = getDataRef();
            if (dr) body.append('data_referencia', dr);
            prevEl.textContent = 'Calculando taxa…';
            fetch(CFG.previewUrl, { method: 'POST', headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' }, body })
                .then(function (r) { return r.json(); })
                .then(function (d) {
                    if (!d.ok) {
                        prevEl.innerHTML = '<span class="text-amber-700 dark:text-amber-300">' + (d.message || 'Taxa não calculada — na baixa será usada a taxa da forma de pagamento se aplicável.') + '</span>';
                        return;
                    }
                    prevEl.innerHTML = '<strong>Prévia:</strong> taxa R$ ' + Number(d.taxa).toFixed(2).replace('.', ',')
                        + ' · líquido R$ ' + Number(d.valor_liquido).toFixed(2).replace('.', ',')
                        + ' <span class="text-xs text-gray-500">(' + (d.adquirente_nome || '') + ', ' + (d.bandeira || '') + ')</span>';
                })
                .catch(function () {
                    prevEl.textContent = 'Não foi possível obter prévia. Salve normalmente; o servidor recalcula na baixa.';
                });
        }

        formaEl.addEventListener('change', function () { toggleBloco(); });
        selAdq.addEventListener('change', function () {
            aplicarContaBancariaPadraoDoAdquirente(selAdq.value);
            schedulePreview();
        });
        selBand.addEventListener('change', schedulePreview);
        if (chkAnt) chkAnt.addEventListener('change', schedulePreview);
        const vel = document.querySelector(CFG.valorSel);
        if (vel) vel.addEventListener('input', schedulePreview);
        if (CFG.parcelasSel) {
            const pel = document.querySelector(CFG.parcelasSel);
            if (pel) pel.addEventListener('input', schedulePreview);
        }
        if (CFG.dataRefSel) {
            const del = document.querySelector(CFG.dataRefSel);
            if (del) del.addEventListener('change', schedulePreview);
        }
        toggleBloco();
        schedulePreview();
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initCrCartaoBloco);
    } else {
        initCrCartaoBloco();
    }
})();
</script>
@endpush
