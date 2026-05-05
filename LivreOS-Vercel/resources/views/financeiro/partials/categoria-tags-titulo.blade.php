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
    $suffix = $escopoCategoria;
    $selectedCategoriaId = $selectedCategoriaId ?? null;
    $selectedTags = $selectedTags ?? collect();
    $categoriaOpcoes = $categoriaOpcoes ?? [];
@endphp
<div class="md:col-span-2 rounded-lg border border-dashed border-gray-200 bg-gray-50/80 p-4 dark:border-gray-700 dark:bg-gray-900/40" data-fin-cat-tag="{{ $suffix }}">
    <p class="mb-3 text-xs font-medium uppercase tracking-wide text-gray-500 dark:text-gray-400">Classificação opcional</p>
    <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
        <div>
            <div class="mb-1.5 flex flex-wrap items-center gap-2">
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Categoria</label>
                <button type="button" class="inline-flex h-7 w-7 items-center justify-center rounded-md border border-brand-200 bg-brand-50 text-sm font-bold text-brand-700 hover:bg-brand-100 dark:border-brand-800 dark:bg-brand-900/40 dark:text-brand-300 dark:hover:bg-brand-900/60" title="Nova categoria" aria-label="Adicionar categoria" onclick="finCatTagOpenModalCategoria_{{ str_replace('-', '_', $suffix) }}('create')">+</button>
                <button type="button" class="text-xs font-medium text-brand-600 hover:text-brand-800 dark:text-brand-400 dark:hover:text-brand-300" onclick="finCatTagOpenModalGerenciar_{{ str_replace('-', '_', $suffix) }}()">Gerenciar</button>
            </div>
            <select name="categoria_financeira_id" id="fin-cat-select-{{ $suffix }}" class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm text-gray-800 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90">
                <option value="">Nenhuma</option>
                @foreach($categoriaOpcoes as $opt)
                    <option value="{{ $opt['id'] }}" @selected((string) old('categoria_financeira_id', $selectedCategoriaId) === (string) $opt['id'])>{{ $opt['label'] }}</option>
                @endforeach
            </select>
            @error('categoria_financeira_id')<p class="mt-1 text-xs text-red-600 dark:text-red-400">{{ $message }}</p>@enderror
        </div>
        <div>
            <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300">Tags</label>
            <div class="relative">
                <input type="text" id="fin-tag-input-{{ $suffix }}" autocomplete="off" placeholder="Digite para buscar ou criar…" class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm text-gray-800 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90"
                    data-fin-tag-input="{{ $suffix }}">
                <div id="fin-tag-suggest-{{ $suffix }}" class="absolute z-20 mt-1 hidden max-h-48 w-full overflow-auto rounded-lg border border-gray-200 bg-white shadow-lg dark:border-gray-600 dark:bg-gray-800"></div>
            </div>
            <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">Enter ou clique para adicionar; sugestões com autocomplete. Opcional.</p>
            <div class="mt-3 rounded-lg border border-gray-200 bg-white/60 p-3 dark:border-gray-600 dark:bg-gray-900/40">
                <p class="mb-2 text-xs font-medium text-gray-600 dark:text-gray-400">Cores ao criar nova tag</p>
                <div class="grid grid-cols-1 gap-3 sm:grid-cols-2">
                    <div>
                        <label class="mb-1 block text-xs text-gray-600 dark:text-gray-400">Fundo</label>
                        <div class="flex items-center gap-2">
                            <input type="color" id="fin-tag-nova-bg-{{ $suffix }}" value="#6366f1" class="h-9 w-12 cursor-pointer rounded border border-gray-300 bg-white p-0.5 dark:border-gray-600">
                            <input type="text" id="fin-tag-nova-bg-hex-{{ $suffix }}" value="#6366f1" maxlength="7" class="min-w-0 flex-1 rounded border border-gray-300 px-2 py-1.5 font-mono text-xs dark:border-gray-600 dark:bg-gray-900 dark:text-white">
                        </div>
                    </div>
                    <div>
                        <label class="mb-1 block text-xs text-gray-600 dark:text-gray-400">Texto</label>
                        <div class="flex items-center gap-2">
                            <input type="color" id="fin-tag-nova-fg-{{ $suffix }}" value="#ffffff" class="h-9 w-12 cursor-pointer rounded border border-gray-300 bg-white p-0.5 dark:border-gray-600">
                            <input type="text" id="fin-tag-nova-fg-hex-{{ $suffix }}" value="#ffffff" maxlength="7" class="min-w-0 flex-1 rounded border border-gray-300 px-2 py-1.5 font-mono text-xs dark:border-gray-600 dark:bg-gray-900 dark:text-white">
                        </div>
                    </div>
                </div>
            </div>
            <div id="fin-tag-chips-{{ $suffix }}" class="mt-2 flex flex-wrap gap-2">
                @foreach($selectedTags as $t)
                    <span class="fin-tag-chip inline-flex items-center gap-1 rounded-full border px-2.5 py-1 text-xs font-medium shadow-sm" style="background-color: {{ $t->cor_fundo ?? '#e5e7eb' }}; color: {{ $t->cor_fonte ?? '#374151' }}; border-color: {{ $t->cor_fundo ?? '#d1d5db' }};" data-tag-id="{{ $t->id }}">
                        {{ $t->nome }}
                        <button type="button" class="ml-0.5 rounded-full p-0.5 text-gray-500 hover:bg-gray-100 hover:text-red-600 dark:hover:bg-gray-700" aria-label="Remover tag" onclick="finCatTagRemoveChip_{{ str_replace('-', '_', $suffix) }}({{ $t->id }})">&times;</button>
                    </span>
                @endforeach
            </div>
            <div id="fin-tag-hiddens-{{ $suffix }}" class="hidden">
                @foreach($selectedTags as $t)
                    <input type="hidden" name="tag_ids[]" value="{{ $t->id }}">
                @endforeach
            </div>
            @error('tag_ids')<p class="mt-1 text-xs text-red-600 dark:text-red-400">{{ $message }}</p>@enderror
            @error('tag_ids.*')<p class="mt-1 text-xs text-red-600 dark:text-red-400">{{ $message }}</p>@enderror
        </div>
    </div>
</div>

{{-- Modal: criar/editar categoria --}}
<div id="fin-modal-cat-{{ $suffix }}" class="fixed inset-0 z-50 hidden flex items-center justify-center bg-black/40 p-4" role="dialog" aria-modal="true">
    <div class="max-h-[90vh] w-full max-w-md overflow-y-auto rounded-xl border border-gray-200 bg-white p-6 shadow-xl dark:border-gray-700 dark:bg-gray-800">
        <h3 class="mb-4 text-lg font-semibold text-gray-800 dark:text-white" id="fin-modal-cat-title-{{ $suffix }}">Nova categoria</h3>
        <input type="hidden" id="fin-modal-cat-edit-id-{{ $suffix }}" value="">
        <div class="space-y-4">
            <div>
                <label class="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-300">Nome</label>
                <input type="text" id="fin-modal-cat-nome-{{ $suffix }}" class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm dark:border-gray-600 dark:bg-gray-900 dark:text-white">
            </div>
            <div>
                <label class="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-300">Subcategoria de (opcional)</label>
                <select id="fin-modal-cat-parent-{{ $suffix }}" class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm dark:border-gray-600 dark:bg-gray-900 dark:text-white"></select>
            </div>
            <div class="grid grid-cols-1 gap-3 sm:grid-cols-2">
                <div>
                    <label class="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-300">Cor de fundo</label>
                    <div class="flex items-center gap-2">
                        <input type="color" id="fin-modal-cat-cor-fundo-{{ $suffix }}" value="#64748b" class="h-10 w-14 cursor-pointer rounded border border-gray-300 bg-white p-0.5 dark:border-gray-600" title="Cor de fundo">
                        <input type="text" id="fin-modal-cat-cor-fundo-hex-{{ $suffix }}" value="#64748b" maxlength="7" autocomplete="off" class="min-w-0 flex-1 rounded-lg border border-gray-300 px-2 py-2 font-mono text-xs uppercase text-gray-800 dark:border-gray-600 dark:bg-gray-900 dark:text-white" placeholder="#RRGGBB">
                    </div>
                </div>
                <div>
                    <label class="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-300">Cor do texto</label>
                    <div class="flex items-center gap-2">
                        <input type="color" id="fin-modal-cat-cor-fonte-{{ $suffix }}" value="#ffffff" class="h-10 w-14 cursor-pointer rounded border border-gray-300 bg-white p-0.5 dark:border-gray-600" title="Cor do texto">
                        <input type="text" id="fin-modal-cat-cor-fonte-hex-{{ $suffix }}" value="#ffffff" maxlength="7" autocomplete="off" class="min-w-0 flex-1 rounded-lg border border-gray-300 px-2 py-2 font-mono text-xs uppercase text-gray-800 dark:border-gray-600 dark:bg-gray-900 dark:text-white" placeholder="#RRGGBB">
                    </div>
                </div>
            </div>
            <div>
                <label class="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-300">Descrição <span class="font-normal text-gray-500">(opcional)</span></label>
                <textarea id="fin-modal-cat-desc-{{ $suffix }}" rows="3" maxlength="2000" placeholder="Observações sobre o uso desta categoria…" class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm dark:border-gray-600 dark:bg-gray-900 dark:text-white"></textarea>
            </div>
            <div id="fin-modal-cat-ativo-wrap-{{ $suffix }}" class="hidden">
                <label class="inline-flex items-center gap-2 text-sm text-gray-700 dark:text-gray-300">
                    <input type="checkbox" id="fin-modal-cat-ativo-{{ $suffix }}" class="rounded border-gray-300" checked>
                    Ativa
                </label>
            </div>
            <p id="fin-modal-cat-err-{{ $suffix }}" class="hidden text-sm text-red-600"></p>
        </div>
        <div class="mt-6 flex justify-end gap-2">
            <button type="button" class="rounded-lg border border-gray-300 px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-600 dark:text-gray-300 dark:hover:bg-gray-700" onclick="document.getElementById('fin-modal-cat-{{ $suffix }}').classList.add('hidden')">Cancelar</button>
            <button type="button" class="rounded-lg bg-brand-500 px-4 py-2 text-sm font-medium text-white hover:bg-brand-600" onclick="finCatTagSaveCategoria_{{ str_replace('-', '_', $suffix) }}()">Salvar</button>
        </div>
    </div>
</div>

{{-- Modal: gerenciar lista --}}
<div id="fin-modal-ger-{{ $suffix }}" class="fixed inset-0 z-50 hidden flex items-center justify-center bg-black/40 p-4" role="dialog" aria-modal="true">
    <div class="max-h-[90vh] w-full max-w-lg overflow-y-auto rounded-xl border border-gray-200 bg-white p-6 shadow-xl dark:border-gray-700 dark:bg-gray-800">
        <h3 class="mb-4 text-lg font-semibold text-gray-800 dark:text-white">Categorias</h3>
        <div id="fin-modal-ger-list-{{ $suffix }}" class="space-y-2 text-sm"></div>
        <div class="mt-6 flex justify-end">
            <button type="button" class="rounded-lg border border-gray-300 px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-600 dark:text-gray-300 dark:hover:bg-gray-700" onclick="document.getElementById('fin-modal-ger-{{ $suffix }}').classList.add('hidden')">Fechar</button>
        </div>
    </div>
</div>

<script>
(function() {
    const suffix = @json($suffix);
    const escopoCategoria = @json($escopoCategoria);
    const tagEscopo = @json($tagEscopo);
    const csrf = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
    const urls = {
        opcoes: @json(route('financeiro.categorias-financeiras.opcoes')),
        index: @json(route('financeiro.categorias-financeiras.index')),
        store: @json(route('financeiro.categorias-financeiras.store')),
        catBase: @json(url('/financeiro/categorias-financeiras')),
        tagAuto: @json(route('financeiro.titulos-tags.autocomplete')),
        tagRapido: @json(route('financeiro.titulos-tags.rapido')),
    };

    function finCatTagHeadersJson() {
        return { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrf, 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' };
    }

    function finCatTagEsc(s) {
        if (s == null || s === '') return '';
        return String(s)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;');
    }

    function finCatTagBindColorPair(colorEl, hexEl) {
        if (!colorEl || !hexEl) return;
        colorEl.addEventListener('input', function() { hexEl.value = colorEl.value; });
        hexEl.addEventListener('input', function() {
            var v = hexEl.value.trim();
            if (/^#[0-9A-Fa-f]{6}$/.test(v)) colorEl.value = v;
        });
    }

    finCatTagBindColorPair(
        document.getElementById('fin-modal-cat-cor-fundo-' + suffix),
        document.getElementById('fin-modal-cat-cor-fundo-hex-' + suffix)
    );
    finCatTagBindColorPair(
        document.getElementById('fin-modal-cat-cor-fonte-' + suffix),
        document.getElementById('fin-modal-cat-cor-fonte-hex-' + suffix)
    );
    finCatTagBindColorPair(
        document.getElementById('fin-tag-nova-bg-' + suffix),
        document.getElementById('fin-tag-nova-bg-hex-' + suffix)
    );
    finCatTagBindColorPair(
        document.getElementById('fin-tag-nova-fg-' + suffix),
        document.getElementById('fin-tag-nova-fg-hex-' + suffix)
    );

    function finCatTagResetModalCatColors() {
        var f = document.getElementById('fin-modal-cat-cor-fundo-' + suffix);
        var fh = document.getElementById('fin-modal-cat-cor-fundo-hex-' + suffix);
        var t = document.getElementById('fin-modal-cat-cor-fonte-' + suffix);
        var th = document.getElementById('fin-modal-cat-cor-fonte-hex-' + suffix);
        if (f && fh) { f.value = '#64748b'; fh.value = '#64748b'; }
        if (t && th) { t.value = '#ffffff'; th.value = '#ffffff'; }
    }

    window['finCatTagRefreshSelect_' + suffix.replace(/-/g, '_')] = async function(incluirId) {
        const u = new URL(urls.opcoes, window.location.origin);
        u.searchParams.set('escopo', escopoCategoria);
        if (incluirId) u.searchParams.set('incluir_id', incluirId);
        const r = await fetch(u, { headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' } });
        const j = await r.json();
        const sel = document.getElementById('fin-cat-select-' + suffix);
        const cur = sel.value;
        sel.innerHTML = '<option value="">Nenhuma</option>';
        (j.opcoes || []).forEach(function(o) {
            const opt = document.createElement('option');
            opt.value = o.id;
            opt.textContent = o.label;
            sel.appendChild(opt);
        });
        sel.value = cur;
    };

    window['finCatTagFillParentSelect_' + suffix.replace(/-/g, '_')] = async function() {
        const u = new URL(urls.opcoes, window.location.origin);
        u.searchParams.set('escopo', escopoCategoria);
        const r = await fetch(u, { headers: { 'Accept': 'application/json' } });
        const j = await r.json();
        const sel = document.getElementById('fin-modal-cat-parent-' + suffix);
        const editId = document.getElementById('fin-modal-cat-edit-id-' + suffix).value;
        sel.innerHTML = '<option value="">(categoria principal)</option>';
        (j.opcoes || []).forEach(function(o) {
            if (editId && String(o.id) === String(editId)) return;
            const opt = document.createElement('option');
            opt.value = o.id;
            opt.textContent = o.label;
            sel.appendChild(opt);
        });
    };

    window['finCatTagOpenModalCategoria_' + suffix.replace(/-/g, '_')] = async function(mode, id) {
        document.getElementById('fin-modal-cat-err-' + suffix).classList.add('hidden');
        document.getElementById('fin-modal-cat-edit-id-' + suffix).value = mode === 'edit' && id ? id : '';
        document.getElementById('fin-modal-cat-nome-' + suffix).value = '';
        document.getElementById('fin-modal-cat-desc-' + suffix).value = '';
        document.getElementById('fin-modal-cat-ativo-' + suffix).checked = true;
        finCatTagResetModalCatColors();
        await window['finCatTagFillParentSelect_' + suffix.replace(/-/g, '_')]();
        if (mode === 'edit' && id) {
            document.getElementById('fin-modal-cat-title-' + suffix).textContent = 'Editar categoria';
            document.getElementById('fin-modal-cat-ativo-wrap-' + suffix).classList.remove('hidden');
            const r = await fetch(urls.index + '?escopo=' + encodeURIComponent(escopoCategoria), { headers: { 'Accept': 'application/json' } });
            const j = await r.json();
            const row = (j.data || []).find(function(x) { return String(x.id) === String(id); });
            if (row) {
                document.getElementById('fin-modal-cat-nome-' + suffix).value = row.nome;
                document.getElementById('fin-modal-cat-parent-' + suffix).value = row.parent_id ? String(row.parent_id) : '';
                document.getElementById('fin-modal-cat-ativo-' + suffix).checked = !!row.ativo;
                var cf = row.cor_fundo || '#64748b';
                var ct = row.cor_fonte || '#ffffff';
                document.getElementById('fin-modal-cat-cor-fundo-' + suffix).value = cf;
                document.getElementById('fin-modal-cat-cor-fundo-hex-' + suffix).value = cf;
                document.getElementById('fin-modal-cat-cor-fonte-' + suffix).value = ct;
                document.getElementById('fin-modal-cat-cor-fonte-hex-' + suffix).value = ct;
                document.getElementById('fin-modal-cat-desc-' + suffix).value = row.descricao || '';
            }
        } else {
            document.getElementById('fin-modal-cat-title-' + suffix).textContent = 'Nova categoria';
            document.getElementById('fin-modal-cat-ativo-wrap-' + suffix).classList.add('hidden');
        }
        const m = document.getElementById('fin-modal-cat-' + suffix);
        m.classList.remove('hidden');
    };

    window['finCatTagSaveCategoria_' + suffix.replace(/-/g, '_')] = async function() {
        const err = document.getElementById('fin-modal-cat-err-' + suffix);
        err.classList.add('hidden');
        const editId = document.getElementById('fin-modal-cat-edit-id-' + suffix).value;
        const nome = document.getElementById('fin-modal-cat-nome-' + suffix).value.trim();
        const parentId = document.getElementById('fin-modal-cat-parent-' + suffix).value || null;
        if (!nome) { err.textContent = 'Informe o nome.'; err.classList.remove('hidden'); return; }
        var corFundo = document.getElementById('fin-modal-cat-cor-fundo-hex-' + suffix).value.trim();
        var corFonte = document.getElementById('fin-modal-cat-cor-fonte-hex-' + suffix).value.trim();
        if (!/^#[0-9A-Fa-f]{6}$/.test(corFundo)) { err.textContent = 'Cor de fundo inválida (use #RRGGBB).'; err.classList.remove('hidden'); return; }
        if (!/^#[0-9A-Fa-f]{6}$/.test(corFonte)) { err.textContent = 'Cor do texto inválida (use #RRGGBB).'; err.classList.remove('hidden'); return; }
        corFundo = corFundo.toLowerCase();
        corFonte = corFonte.toLowerCase();
        var desc = document.getElementById('fin-modal-cat-desc-' + suffix).value.trim();
        let r, j;
        if (editId) {
            const body = { nome: nome, parent_id: parentId ? parseInt(parentId, 10) : null, ativo: document.getElementById('fin-modal-cat-ativo-' + suffix).checked, cor_fundo: corFundo, cor_fonte: corFonte, descricao: desc || null };
            r = await fetch(urls.catBase + '/' + editId, { method: 'PUT', headers: finCatTagHeadersJson(), body: JSON.stringify(body) });
        } else {
            const body = { escopo: escopoCategoria, nome: nome, parent_id: parentId ? parseInt(parentId, 10) : null, cor_fundo: corFundo, cor_fonte: corFonte, descricao: desc || null };
            r = await fetch(urls.store, { method: 'POST', headers: finCatTagHeadersJson(), body: JSON.stringify(body) });
        }
        j = await r.json();
        if (!r.ok) { err.textContent = j.message || 'Erro ao salvar.'; err.classList.remove('hidden'); return; }
        const sel = document.getElementById('fin-cat-select-' + suffix);
        sel.innerHTML = '<option value="">Nenhuma</option>';
        (j.opcoes || []).forEach(function(o) {
            const opt = document.createElement('option');
            opt.value = o.id;
            opt.textContent = o.label;
            sel.appendChild(opt);
        });
        if (j.categoria) sel.value = String(j.categoria.id);
        document.getElementById('fin-modal-cat-' + suffix).classList.add('hidden');
    };

    window['finCatTagOpenModalGerenciar_' + suffix.replace(/-/g, '_')] = async function() {
        const list = document.getElementById('fin-modal-ger-list-' + suffix);
        list.innerHTML = '<p class="text-gray-500">Carregando…</p>';
        const m = document.getElementById('fin-modal-ger-' + suffix);
        m.classList.remove('hidden');
        const r = await fetch(urls.index + '?escopo=' + encodeURIComponent(escopoCategoria), { headers: { 'Accept': 'application/json' } });
        const j = await r.json();
        list.innerHTML = '';
        (j.data || []).forEach(function(row) {
            const div = document.createElement('div');
            div.className = 'flex items-center justify-between gap-2 rounded-lg border border-gray-100 bg-gray-50 px-3 py-2 dark:border-gray-700 dark:bg-gray-900/50';
            const pai = row.parent ? ' <span class="text-gray-400">← ' + finCatTagEsc(row.parent.nome) + '</span>' : '';
            var bg = row.cor_fundo || '#64748b';
            var sw = '<span class="inline-flex h-9 w-9 shrink-0 rounded-md border border-gray-200 shadow-sm dark:border-gray-600" style="background-color:' + bg + '" title="Cor de fundo"></span>';
            var descHtml = '';
            if (row.descricao) {
                var shortD = row.descricao.length > 140 ? row.descricao.substring(0, 140) + '…' : row.descricao;
                descHtml = '<p class="mt-1 max-w-full text-xs leading-snug text-gray-500 dark:text-gray-400" title="' + finCatTagEsc(row.descricao) + '">' + finCatTagEsc(shortD) + '</p>';
            }
            var lbl = '<span class="font-medium text-gray-800 dark:text-gray-200">' + finCatTagEsc(row.nome) + '</span>' + pai + (row.ativo ? '' : ' <em class="text-orange-600">(inativa)</em>');
            div.innerHTML = '<span class="flex min-w-0 flex-1 items-start gap-3">' + sw + '<span class="min-w-0 flex-1">' + lbl + descHtml + '</span></span>' +
                '<span class="flex shrink-0 gap-2"><button type="button" class="text-xs text-brand-600 hover:underline">Editar</button><button type="button" class="text-xs text-red-600 hover:underline">Excluir</button></span>';
            const btns = div.querySelectorAll('button');
            btns[0].onclick = function() { m.classList.add('hidden'); window['finCatTagOpenModalCategoria_' + suffix.replace(/-/g, '_')]('edit', row.id); };
            btns[1].onclick = async function() {
                if (!confirm('Excluir esta categoria?')) return;
                const rd = await fetch(urls.catBase + '/' + row.id, { method: 'DELETE', headers: finCatTagHeadersJson() });
                const jd = await rd.json();
                if (!rd.ok) { alert(jd.message || 'Erro'); return; }
                window['finCatTagRefreshSelect_' + suffix.replace(/-/g, '_')]();
                window['finCatTagOpenModalGerenciar_' + suffix.replace(/-/g, '_')]();
            };
            list.appendChild(div);
        });
        if (!(j.data || []).length) list.innerHTML = '<p class="text-gray-500">Nenhuma categoria cadastrada.</p>';
    };

    const tagInput = document.getElementById('fin-tag-input-' + suffix);
    const tagSuggest = document.getElementById('fin-tag-suggest-' + suffix);
    const tagChips = document.getElementById('fin-tag-chips-' + suffix);
    const tagHiddens = document.getElementById('fin-tag-hiddens-' + suffix);
    let tagTimer = null;

    function finCatTagGetSelectedIds() {
        return Array.from(tagHiddens.querySelectorAll('input[name="tag_ids[]"]')).map(function(i) { return parseInt(i.value, 10); });
    }

    function finCatTagAddChip(id, nome, corBg, corFg) {
        if (finCatTagGetSelectedIds().indexOf(id) >= 0) return;
        corBg = corBg || '#e5e7eb';
        corFg = corFg || '#374151';
        const span = document.createElement('span');
        span.className = 'fin-tag-chip inline-flex items-center gap-1 rounded-full border px-2.5 py-1 text-xs font-medium shadow-sm';
        span.style.backgroundColor = corBg;
        span.style.color = corFg;
        span.style.borderColor = corBg;
        span.setAttribute('data-tag-id', id);
        span.innerHTML = nome + ' <button type="button" class="ml-0.5 rounded-full p-0.5 opacity-80 hover:bg-black/10 hover:text-red-600" aria-label="Remover tag" onclick="finCatTagRemoveChip_' + suffix.replace(/-/g, '_') + '(' + id + ')">&times;</button>';
        tagChips.appendChild(span);
        const hid = document.createElement('input');
        hid.type = 'hidden';
        hid.name = 'tag_ids[]';
        hid.value = id;
        tagHiddens.appendChild(hid);
    }

    window['finCatTagRemoveChip_' + suffix.replace(/-/g, '_')] = function(id) {
        tagChips.querySelectorAll('[data-tag-id="' + id + '"]').forEach(function(el) { el.remove(); });
        tagHiddens.querySelectorAll('input[name="tag_ids[]"]').forEach(function(inp) {
            if (parseInt(inp.value, 10) === id) inp.remove();
        });
    };

    async function finCatTagRunSuggest(q) {
        const u = new URL(urls.tagAuto, window.location.origin);
        u.searchParams.set('escopo', tagEscopo);
        u.searchParams.set('q', q);
        const r = await fetch(u, { headers: { 'Accept': 'application/json' } });
        const j = await r.json();
        tagSuggest.innerHTML = '';
        const rows = j.data || [];
        if (!rows.length) {
            const div = document.createElement('div');
            div.className = 'cursor-pointer px-3 py-2 text-sm text-brand-600 hover:bg-gray-50 dark:hover:bg-gray-700';
            div.textContent = 'Criar tag "' + q + '"';
            div.onclick = function() { finCatTagCreateAndAdd(q); };
            tagSuggest.appendChild(div);
        } else {
            rows.forEach(function(t) {
                const div = document.createElement('div');
                div.className = 'flex cursor-pointer items-center gap-2 px-3 py-2 text-sm hover:bg-gray-50 dark:hover:bg-gray-700 dark:text-gray-200';
                var b = t.cor_fundo || '#94a3b8';
                div.innerHTML = '<span class="inline-block h-5 w-5 shrink-0 rounded border border-gray-200 dark:border-gray-600" style="background-color:' + b + '"></span><span>' + t.nome + '</span>';
                div.onclick = function() { tagSuggest.classList.add('hidden'); finCatTagAddChip(t.id, t.nome, t.cor_fundo, t.cor_fonte); tagInput.value = ''; };
                tagSuggest.appendChild(div);
            });
        }
        tagSuggest.classList.remove('hidden');
    }

    async function finCatTagCreateAndAdd(nome) {
        var corFundo = document.getElementById('fin-tag-nova-bg-hex-' + suffix).value.trim();
        var corFonte = document.getElementById('fin-tag-nova-fg-hex-' + suffix).value.trim();
        if (!/^#[0-9A-Fa-f]{6}$/.test(corFundo)) { alert('Cor de fundo inválida (#RRGGBB).'); return; }
        if (!/^#[0-9A-Fa-f]{6}$/.test(corFonte)) { alert('Cor do texto inválida (#RRGGBB).'); return; }
        const r = await fetch(urls.tagRapido, { method: 'POST', headers: finCatTagHeadersJson(), body: JSON.stringify({
            nome: nome,
            escopo: tagEscopo,
            cor_fundo: corFundo.toLowerCase(),
            cor_fonte: corFonte.toLowerCase(),
        }) });
        const j = await r.json();
        if (!r.ok) { alert(j.message || 'Erro'); return; }
        tagSuggest.classList.add('hidden');
        finCatTagAddChip(j.tag.id, j.tag.nome, j.tag.cor_fundo, j.tag.cor_fonte);
        tagInput.value = '';
    }

    if (tagInput) {
        tagInput.addEventListener('input', function() {
            clearTimeout(tagTimer);
            const q = tagInput.value.trim();
            if (q.length < 1) { tagSuggest.classList.add('hidden'); return; }
            tagTimer = setTimeout(function() { finCatTagRunSuggest(q); }, 200);
        });
        tagInput.addEventListener('keydown', function(e) {
            if (e.key === 'Enter') {
                e.preventDefault();
                const q = tagInput.value.trim();
                if (!q) return;
                finCatTagCreateAndAdd(q);
            }
        });
        document.addEventListener('click', function(e) {
            if (!tagSuggest.contains(e.target) && e.target !== tagInput) tagSuggest.classList.add('hidden');
        });
    }
})();
</script>
