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
<div class="mb-6">
    <h1 class="mb-2 text-2xl font-semibold text-gray-800 dark:text-white/90">Utilidades</h1>
    <p class="text-gray-600 dark:text-gray-400">Exporte e importe os dados do sistema por completo, somente o banco de dados ou somente os anexos.</p>
</div>

{{-- Terminal em iframe: centralizado na tela, exibido ao executar Restaurar, Importar ou Zerar sistema --}}
<div id="terminal-overlay" class="fixed inset-0 z-50 flex hidden items-center justify-center bg-black/60 p-4" aria-hidden="true">
    <div id="terminal-panel" class="flex max-h-[90vh] w-full max-w-4xl flex-col rounded-lg border border-gray-700 bg-gray-900 shadow-xl">
        <div class="flex items-center justify-between border-b border-gray-700 px-4 py-3">
            <span id="terminal-title" class="text-sm font-medium text-gray-300">Executando ação — acompanhe o log abaixo</span>
            <button type="button" id="terminal-close" class="rounded px-2 py-1 text-xs text-gray-400 hover:bg-gray-700 hover:text-gray-200" aria-label="Fechar">Ocultar</button>
        </div>
        <div class="border-b border-amber-700/50 bg-amber-900/20 px-4 py-2 text-center text-sm text-amber-200">
            <strong>Não saia desta tela</strong> enquanto a operação estiver em andamento. Aguarde a finalização.
        </div>
        <div id="mos-export-seq-upload-bar-wrap" class="hidden border-b border-gray-700 bg-gray-900/80 px-4 py-3">
            <p id="mos-export-seq-upload-label" class="mb-2 text-xs text-gray-300"></p>
            <div class="h-2 w-full overflow-hidden rounded bg-gray-800">
                <div id="mos-export-seq-upload-bar" class="h-full bg-brand-500 transition-[width] duration-100" style="width:0%"></div>
            </div>
        </div>
        {{-- Sem sandbox: combinação allow-scripts + allow-same-origin faz o Chrome avisar que o sandbox pode ser contornado; o conteúdo é sempre HTML da própria app (importar/restaurar/zerar). --}}
        <iframe name="iframe-terminal" id="iframe-terminal" class="block min-h-[420px] flex-1 w-full rounded-b-lg border-0 bg-gray-950" title="Log da operação"></iframe>
    </div>
</div>
<script>
document.addEventListener('DOMContentLoaded', function() {
    var overlay = document.getElementById('terminal-overlay');
    var panel = document.getElementById('terminal-panel');
    var closeBtn = document.getElementById('terminal-close');
    var terminalTitle = document.getElementById('terminal-title');
    var utilidadesIndexUrl = @json(route('configuracoes-sistema.utilidades.index'));
    var importPostUrl = @json(route('configuracoes-sistema.utilidades.import'));
    if (closeBtn) closeBtn.addEventListener('click', function() {
        overlay.classList.add('hidden');
        overlay.classList.remove('flex');
    });
    window.showTerminalPanel = function() {
        overlay.classList.remove('hidden');
        overlay.classList.add('flex');
    };

    function startExportProgressLabel() {
        var startedAt = Date.now();
        if (terminalTitle) {
            terminalTitle.textContent = 'Exportação em andamento — processando backup...';
        }
        var timer = window.setInterval(function() {
            if (!overlay.classList.contains('flex')) {
                window.clearInterval(timer);
                return;
            }
            if (!terminalTitle) return;
            var secs = Math.max(0, Math.floor((Date.now() - startedAt) / 1000));
            terminalTitle.textContent = 'Exportação em andamento — ' + secs + 's';
        }, 1000);
        return timer;
    }

    function utilidadesParseImportHtml(html) {
        try {
            var d = new DOMParser().parseFromString(html, 'text/html');
            var log = d.querySelector('pre#log');
            var logText = log ? log.textContent : '';
            var msg = d.querySelector('.msg');
            var msgEl = msg || d.querySelector('.msg.success') || d.querySelector('.msg.error');
            var msgText = msgEl ? msgEl.textContent.trim() : '';
            var success = msgEl && msgEl.classList.contains('success');
            return { logText: logText, msgText: msgText, success: success };
        } catch (e) {
            return { logText: html, msgText: '', success: false };
        }
    }

    function utilidadesInitIframeLog(iframe) {
        var doc = iframe.contentDocument;
        doc.open();
        doc.write('<!DOCTYPE html><html><head><meta charset="utf-8"><title>Importação M-OS</title><style>body{font-family:Consolas,monospace;background:#1a1a2e;color:#a0e0a0;padding:1rem;margin:0;} pre.chunk{white-space:pre-wrap;word-break:break-all;margin:0 0 1rem 0;} .part-hdr{color:#6eb6ff;margin:1rem 0 0.25rem 0;font-weight:bold;font-size:0.85rem;} .footer-msg{margin-top:1rem;padding:0.75rem;border-radius:6px;} .footer-msg.ok{background:#0d3d0d;color:#90ee90;} .footer-msg.err{background:#3d0d0d;color:#ff9090;} a{color:#6eb6ff;}</style></head><body></body></html>');
        doc.close();
    }

    function utilidadesAppendIframeChunk(iframe, title, parsed) {
        var doc = iframe.contentDocument;
        if (!doc || !doc.body) return;
        var h = doc.createElement('div');
        h.className = 'part-hdr';
        h.textContent = title;
        doc.body.appendChild(h);
        if (parsed.logText) {
            var pre = doc.createElement('pre');
            pre.className = 'chunk';
            pre.textContent = parsed.logText;
            doc.body.appendChild(pre);
        }
        if (parsed.msgText) {
            var foot = doc.createElement('div');
            foot.className = 'footer-msg ' + (parsed.success ? 'ok' : 'err');
            foot.textContent = parsed.msgText;
            doc.body.appendChild(foot);
        }
    }

    function utilidadesAppendVoltarLink(iframe) {
        var doc = iframe.contentDocument;
        if (!doc || !doc.body) return;
        var p = doc.createElement('p');
        p.style.marginTop = '1rem';
        var a = doc.createElement('a');
        a.href = utilidadesIndexUrl;
        a.target = '_parent';
        a.textContent = 'Voltar para Utilidades';
        p.appendChild(a);
        doc.body.appendChild(p);
    }

    window.utilidadesImportOnSubmit = function(ev) {
        var form = document.getElementById('form-utilidades-import');
        if (!form) {
            if (typeof showTerminalPanel === 'function') showTerminalPanel();
            return true;
        }
        var tipoEl = form.querySelector('input[name="tipo"]:checked');
        var tipo = tipoEl ? tipoEl.value : '';
        var modoRadio = form.querySelector('input[name="import_modo_envio_arquivo"]:checked');
        var modo = modoRadio ? modoRadio.value : 'unico';
        var isCompletoPartes = (tipo === 'completo' && modo === 'partes');

        if (isCompletoPartes) {
            var inpPartes = document.getElementById('arquivos_partes');
            if (!inpPartes || !inpPartes.files || inpPartes.files.length === 0) {
                return true;
            }
            var files = Array.prototype.slice.call(inpPartes.files);
            function completoPartIndexFromName(name) {
                var m = String(name).match(/part\s*0*(\d+)/i);
                return m ? parseInt(m[1], 10) : 999999;
            }
            files.sort(function(a, b) {
                return completoPartIndexFromName(a.name) - completoPartIndexFromName(b.name);
            });

            ev.preventDefault();
            var tokenInp = form.querySelector('input[name="_token"]');
            var token = tokenInp ? tokenInp.value : '';
            var barWrap = document.getElementById('mos-export-seq-upload-bar-wrap');
            var bar = document.getElementById('mos-export-seq-upload-bar');
            var barLabel = document.getElementById('mos-export-seq-upload-label');
            var iframe = document.getElementById('iframe-terminal');
            var submitBtn = form.querySelector('button[type="submit"]');

            var seqToken = 'cmp_' + Date.now() + '_' + Math.random().toString(16).slice(2);

            if (typeof showTerminalPanel === 'function') showTerminalPanel();
            if (barWrap) barWrap.classList.remove('hidden');
            if (bar) bar.style.width = '0%';
            utilidadesInitIframeLog(iframe);
            if (submitBtn) submitBtn.disabled = true;

            function setBar(pct, label) {
                if (bar) bar.style.width = Math.min(100, Math.max(0, pct)) + '%';
                if (barLabel) barLabel.textContent = label;
            }

            function postOne(index) {
                if (index >= files.length) {
                    if (barWrap) barWrap.classList.add('hidden');
                    if (submitBtn) submitBtn.disabled = false;
                    utilidadesAppendVoltarLink(iframe);
                    return;
                }
                var file = files[index];
                var n = files.length;
                var fd = new FormData();
                fd.append('_token', token);
                fd.append('tipo', tipo);
                fd.append('stream', '1');
                fd.append('seq_token', seqToken);
                fd.append('part_index', String(index + 1));
                fd.append('part_total', String(n));
                fd.append('arquivo_part', file, file.name);

                var xhr = new XMLHttpRequest();
                xhr.open('POST', importPostUrl);
                xhr.withCredentials = true;
                xhr.setRequestHeader('X-Requested-With', 'XMLHttpRequest');
                xhr.setRequestHeader('Accept', 'text/html, application/json');
                xhr.upload.onprogress = function(e) {
                    if (e.lengthComputable) {
                        var pct = (e.loaded / e.total) * 100;
                        setBar(pct * 0.92, 'A enviar parte ' + (index + 1) + '/' + n + ': ' + file.name + ' (' + Math.round(pct) + '%)');
                    } else {
                        setBar(30, 'A enviar parte ' + (index + 1) + '/' + n + ': ' + file.name + '…');
                    }
                };
                xhr.onerror = function() {
                    setBar(0, 'Falha de rede ao enviar.');
                    if (barWrap) barWrap.classList.add('hidden');
                    if (submitBtn) submitBtn.disabled = false;
                    utilidadesAppendIframeChunk(iframe, 'Erro de rede', { logText: 'Não foi possível contactar o servidor.', msgText: 'Verifique a ligação e tente novamente.', success: false });
                    utilidadesAppendVoltarLink(iframe);
                };
                xhr.onload = function() {
                    var ct = (xhr.getResponseHeader('Content-Type') || '').toLowerCase();
                    if (xhr.status === 422 && ct.indexOf('json') !== -1) {
                        try {
                            var j = JSON.parse(xhr.responseText);
                            var msg = j.message || 'Validação falhou.';
                            if (j.errors) {
                                Object.keys(j.errors).forEach(function(k) {
                                    (j.errors[k] || []).forEach(function(line) { msg += ' ' + line; });
                                });
                            }
                            setBar(0, '');
                            if (barWrap) barWrap.classList.add('hidden');
                            if (submitBtn) submitBtn.disabled = false;
                            utilidadesAppendIframeChunk(iframe, 'Erro de validação', { logText: msg, msgText: 'Corrija e tente novamente.', success: false });
                            utilidadesAppendVoltarLink(iframe);
                            return;
                        } catch (err) {
                            // segue caminho padrão
                        }
                    }
                    if (xhr.status < 200 || xhr.status >= 300) {
                        setBar(0, '');
                        if (barWrap) barWrap.classList.add('hidden');
                        if (submitBtn) submitBtn.disabled = false;
                        utilidadesAppendIframeChunk(iframe, 'HTTP ' + xhr.status, { logText: (xhr.responseText || '').slice(0, 4000), msgText: 'Pedido rejeitado pelo servidor.', success: false });
                        utilidadesAppendVoltarLink(iframe);
                        return;
                    }
                    setBar(100, 'A processar no servidor: ' + file.name + '…');
                    var parsed = utilidadesParseImportHtml(xhr.responseText);
                    utilidadesAppendIframeChunk(iframe, '=== Parte ' + (index + 1) + '/' + n + ': ' + file.name + ' ===', parsed);
                    if (parsed.success === false && parsed.msgText) {
                        if (barWrap) barWrap.classList.add('hidden');
                        if (submitBtn) submitBtn.disabled = false;
                        utilidadesAppendVoltarLink(iframe);
                        return;
                    }
                    setBar(0, '');
                    postOne(index + 1);
                };
                xhr.send(fd);
            }
            postOne(0);
            return false;
        }

        if (tipo !== 'mos_export_full') {
            if (typeof showTerminalPanel === 'function') showTerminalPanel();
            return true;
        }
        var inpMosExport = document.getElementById('arquivos_mos_export');
        if (!inpMosExport || !inpMosExport.files || inpMosExport.files.length === 0) {
            return true;
        }
        var files = Array.prototype.slice.call(inpMosExport.files);
        function utilidadesMosExportPartIndexFromName(name) {
            var m = String(name).match(/part\s*0*(\d+)\s*of\s*0*(\d+)/i);
            return m ? parseInt(m[1], 10) : 999999;
        }
        files.sort(function(a, b) {
            return utilidadesMosExportPartIndexFromName(a.name) - utilidadesMosExportPartIndexFromName(b.name);
        });
        ev.preventDefault();
        var tokenInp = form.querySelector('input[name="_token"]');
        var token = tokenInp ? tokenInp.value : '';
        var barWrap = document.getElementById('mos-export-seq-upload-bar-wrap');
        var bar = document.getElementById('mos-export-seq-upload-bar');
        var barLabel = document.getElementById('mos-export-seq-upload-label');
        var iframe = document.getElementById('iframe-terminal');
        var submitBtn = form.querySelector('button[type="submit"]');

        if (typeof showTerminalPanel === 'function') showTerminalPanel();
        if (barWrap) barWrap.classList.remove('hidden');
        if (bar) bar.style.width = '0%';
        utilidadesInitIframeLog(iframe);
        if (submitBtn) submitBtn.disabled = true;

        function setBar(pct, label) {
            if (bar) bar.style.width = Math.min(100, Math.max(0, pct)) + '%';
            if (barLabel) barLabel.textContent = label;
        }

        function postOne(index) {
            if (index >= files.length) {
                if (barWrap) barWrap.classList.add('hidden');
                if (submitBtn) submitBtn.disabled = false;
                utilidadesAppendVoltarLink(iframe);
                return;
            }
            var file = files[index];
            var n = files.length;
            var fd = new FormData();
            fd.append('_token', token);
            fd.append('tipo', tipo);
            fd.append('stream', '1');
            fd.append('arquivo', file, file.name);
            var xhr = new XMLHttpRequest();
            xhr.open('POST', importPostUrl);
            xhr.withCredentials = true;
            xhr.setRequestHeader('X-Requested-With', 'XMLHttpRequest');
            xhr.setRequestHeader('Accept', 'text/html, application/json');
            xhr.upload.onprogress = function(e) {
                if (e.lengthComputable) {
                    var pct = (e.loaded / e.total) * 100;
                    setBar(pct * 0.92, 'A enviar ficheiro ' + (index + 1) + '/' + n + ': ' + file.name + ' (' + Math.round(pct) + '%)');
                } else {
                    setBar(30, 'A enviar ficheiro ' + (index + 1) + '/' + n + ': ' + file.name + '…');
                }
            };
            xhr.onerror = function() {
                setBar(0, 'Falha de rede ao enviar.');
                if (barWrap) barWrap.classList.add('hidden');
                if (submitBtn) submitBtn.disabled = false;
                utilidadesAppendIframeChunk(iframe, 'Erro de rede', { logText: 'Não foi possível contactar o servidor.', msgText: 'Verifique a ligação e tente novamente.', success: false });
                utilidadesAppendVoltarLink(iframe);
            };
            xhr.onload = function() {
                var ct = (xhr.getResponseHeader('Content-Type') || '').toLowerCase();
                if (xhr.status === 422 && ct.indexOf('json') !== -1) {
                    try {
                        var j = JSON.parse(xhr.responseText);
                        var msg = j.message || 'Validação falhou.';
                        if (j.errors) {
                            Object.keys(j.errors).forEach(function(k) {
                                (j.errors[k] || []).forEach(function(line) { msg += ' ' + line; });
                            });
                        }
                        setBar(0, '');
                        if (barWrap) barWrap.classList.add('hidden');
                        if (submitBtn) submitBtn.disabled = false;
                        utilidadesAppendIframeChunk(iframe, 'Ficheiro ' + (index + 1) + '/' + n, { logText: msg, msgText: 'Corrija e tente de novo.', success: false });
                        utilidadesAppendVoltarLink(iframe);
                    } catch (err) {
                        xhr.onerror();
                    }
                    return;
                }
                if (xhr.status < 200 || xhr.status >= 300) {
                    setBar(0, '');
                    if (barWrap) barWrap.classList.add('hidden');
                    if (submitBtn) submitBtn.disabled = false;
                    utilidadesAppendIframeChunk(iframe, 'HTTP ' + xhr.status, { logText: xhr.responseText.slice(0, 4000), msgText: 'Pedido rejeitado pelo servidor.', success: false });
                    utilidadesAppendVoltarLink(iframe);
                    return;
                }
                setBar(100, 'A processar no servidor: ' + file.name + '…');
                var parsed = utilidadesParseImportHtml(xhr.responseText);
                utilidadesAppendIframeChunk(iframe, '=== Ficheiro ' + (index + 1) + '/' + n + ': ' + file.name + ' ===', parsed);
                if (parsed.success === false && parsed.msgText) {
                    if (barWrap) barWrap.classList.add('hidden');
                    if (submitBtn) submitBtn.disabled = false;
                    utilidadesAppendVoltarLink(iframe);
                    return;
                }
                setBar(0, '');
                postOne(index + 1);
            };
            xhr.send(fd);
        }
        postOne(0);
        return false;
    };

    function utilidadesToggleImportInputs() {
        var t = document.querySelector('input[name="tipo"]:checked');
        var v = t ? t.value : '';
        var isMosExport = v === 'mos_export_full';
        var wrapUnico = document.getElementById('wrap-arquivo-unico');
        var wrapPartes = document.getElementById('wrap-arquivos-partes');
        var wrapMosExport = document.getElementById('wrap-arquivos-mos-export');
        var inpArquivo = document.getElementById('arquivo');
        var inpPartes = document.getElementById('arquivos_partes');
        var inpMosExport = document.getElementById('arquivos_mos_export');

        var modoRadio = document.querySelector('input[name="import_modo_envio_arquivo"]:checked');
        var modo = modoRadio ? modoRadio.value : 'unico';
        var isPartes = modo === 'partes';

        if (wrapUnico) wrapUnico.classList.toggle('hidden', isMosExport || isPartes);
        if (wrapPartes) wrapPartes.classList.toggle('hidden', isMosExport || !isPartes);
        if (wrapMosExport) wrapMosExport.classList.toggle('hidden', !isMosExport);
        if (inpArquivo) {
            inpArquivo.disabled = isMosExport || isPartes;
            if (isMosExport || isPartes) inpArquivo.removeAttribute('name'); else inpArquivo.setAttribute('name', 'arquivo');
        }
        if (inpPartes) {
            inpPartes.disabled = isMosExport || !isPartes;
            if (isMosExport || !isPartes) inpPartes.removeAttribute('name'); else inpPartes.setAttribute('name', 'arquivos_partes[]');
        }
        if (inpMosExport) {
            inpMosExport.disabled = !isMosExport;
            if (!isMosExport) { inpMosExport.value = ''; }
        }
        document.querySelectorAll('.import-opt-hint').forEach(function (p) {
            var d = p.getAttribute('data-tipo');
            p.classList.toggle('hidden', d !== v);
        });
    }
    document.querySelectorAll('input[name="tipo"]').forEach(function (r) {
        r.addEventListener('change', utilidadesToggleImportInputs);
    });
    document.querySelectorAll('input[name="import_modo_envio_arquivo"]').forEach(function (r) {
        r.addEventListener('change', utilidadesToggleImportInputs);
    });
    utilidadesToggleImportInputs();

    var formExport = document.getElementById('form-utilidades-export');
    if (formExport) {
        formExport.addEventListener('submit', function () {
            var chk = formExport.querySelector('input[name="particionado"]');
            var emPartes = !!(chk && chk.checked);
            if (!emPartes) return;
            if (typeof showTerminalPanel === 'function') showTerminalPanel();
            startExportProgressLabel();
        });
    }

    var chunkInput = document.getElementById('chunk_mb');
    var chunkWarn = document.getElementById('chunk_mb_warn');
    function updateChunkWarning() {
        if (!chunkInput || !chunkWarn) return;
        var v = parseInt(chunkInput.value || '50', 10);
        if (isNaN(v)) v = 50;
        chunkWarn.classList.toggle('hidden', v <= 100);
    }
    if (chunkInput) {
        chunkInput.addEventListener('input', updateChunkWarning);
        updateChunkWarning();
    }
});
</script>

@if(session('success'))
<div class="mb-6 rounded-lg border border-success-200 bg-success-50 p-4 text-success-700 dark:border-success-800 dark:bg-success-500/10 dark:text-success-400">
    <p>{{ session('success') }}</p>
    @if(session('restore_log_path'))
    <p class="mt-2 font-medium">Tente acessar o sistema com os novos dados restaurados.</p>
    @endif
</div>
@endif
@if(session('error'))
<div class="mb-6 rounded-lg border border-red-200 bg-red-50 p-4 text-red-700 dark:border-red-800 dark:bg-red-500/10 dark:text-red-400">
    {{ session('error') }}
</div>
@endif
@if($errors->any())
<div class="mb-6 rounded-lg border border-red-200 bg-red-50 p-4 text-red-700 dark:border-red-800 dark:bg-red-500/10 dark:text-red-400">
    <ul class="list-inside list-disc">
        @foreach($errors->all() as $err)
        <li>{{ $err }}</li>
        @endforeach
    </ul>
</div>
@endif

@if(!empty($restore_log))
<div class="mb-6 rounded-lg border border-gray-700 bg-gray-900 p-4 shadow-theme-sm">
    <h2 class="mb-2 text-sm font-semibold text-gray-300">Log da última restauração</h2>
    <p class="mb-2 text-xs text-gray-400">Acompanhe abaixo o que foi executado. Ao finalizar com sucesso, acesse o sistema com os dados restaurados.</p>
    <pre class="max-h-96 overflow-auto rounded bg-black/50 p-3 font-mono text-xs leading-relaxed text-green-400" style="white-space: pre-wrap; word-break: break-all;">{{ $restore_log }}</pre>
</div>
@endif

@if(!empty($import_log))
<div class="mb-6 rounded-lg border border-gray-700 bg-gray-900 p-4 shadow-theme-sm">
    <h2 class="mb-2 text-sm font-semibold text-gray-300">Log da última importação (somente banco)</h2>
    <pre class="max-h-96 overflow-auto rounded bg-black/50 p-3 font-mono text-xs leading-relaxed text-green-400" style="white-space: pre-wrap; word-break: break-all;">{{ $import_log }}</pre>
</div>
@endif

@if(!empty($export_parts))
<div class="mb-6 rounded-lg border border-blue-200 bg-blue-50 p-4 shadow-theme-sm dark:border-blue-800 dark:bg-blue-950/30">
    <h2 class="mb-2 text-sm font-semibold text-blue-900 dark:text-blue-200">Arquivos da exportação em partes</h2>
    <p class="mb-2 text-xs text-blue-800 dark:text-blue-300">
        Baixe <strong>todas</strong> as partes. A <strong>Parte 1 + manifest</strong> já traz o manifest junto para facilitar.
    </p>
    <div class="mb-2">
        <a href="{{ $export_parts['manifest_url'] }}" class="inline-flex items-center gap-2 rounded border border-blue-300 bg-white px-3 py-1.5 text-xs font-medium text-blue-700 hover:bg-blue-100 dark:border-blue-700 dark:bg-blue-900/40 dark:text-blue-200">
            Baixar manifest.json
        </a>
    </div>
    <ul class="space-y-1">
        @forelse($export_parts['files'] as $f)
            <li class="flex items-center justify-between rounded border border-blue-200 bg-white px-3 py-2 text-xs dark:border-blue-700 dark:bg-blue-900/40">
                <span class="font-mono text-blue-900 dark:text-blue-200">{{ $f['name'] }}</span>
                <div class="flex items-center gap-3">
                    @if(!empty($f['is_first']) && !empty($f['url_with_manifest']))
                        <a href="{{ $f['url_with_manifest'] }}" class="text-blue-700 hover:underline dark:text-blue-300">Parte 1 + manifest</a>
                    @endif
                    <a href="{{ $f['url'] }}" class="text-blue-700 hover:underline dark:text-blue-300">Baixar ({{ number_format(($f['size'] ?? 0) / 1048576, 2, ',', '.') }} MB)</a>
                </div>
            </li>
        @empty
            <li class="text-xs text-blue-900 dark:text-blue-200">Nenhuma parte disponível.</li>
        @endforelse
    </ul>
</div>
@endif

<div class="grid gap-6 lg:grid-cols-2">
    {{-- Exportar --}}
    <div class="rounded-lg border border-gray-200 bg-white p-6 shadow-theme-sm dark:border-gray-800 dark:bg-gray-800">
        <h2 class="mb-4 text-lg font-semibold text-gray-800 dark:text-white/90">Exportar</h2>
        <p class="mb-4 text-sm text-gray-600 dark:text-gray-400">Baixe um backup conforme o tipo desejado.</p>
        <form id="form-utilidades-export" method="GET" action="{{ route('configuracoes-sistema.utilidades.export', ['tipo' => 'completo']) }}">
            <div class="mb-4 grid gap-3 sm:grid-cols-2">
                <label class="flex items-center gap-2">
                    <input type="checkbox" name="particionado" value="1" class="h-4 w-4 rounded border-gray-300 text-brand-500 focus:ring-brand-500 dark:border-gray-600 dark:bg-gray-700">
                    <span class="text-sm text-gray-700 dark:text-gray-300">Exportar em partes</span>
                </label>
                <div>
                    <label class="mb-1 block text-xs font-medium text-gray-700 dark:text-gray-300">Tamanho da parte (MB)</label>
                    <input type="number" id="chunk_mb" name="chunk_mb" min="1" max="1024" value="50" class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm dark:border-gray-700 dark:bg-gray-900 dark:text-white/90">
                    <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">Recomendado em hospedagem compartilhada: até 100MB por parte.</p>
                    <p id="chunk_mb_warn" class="mt-1 hidden text-xs text-amber-700 dark:text-amber-300">Valor alto pode aumentar risco de timeout e falha de download em hospedagem limitada.</p>
                </div>
            </div>
            <div class="space-y-3">
                <div>
                    <button type="submit" formaction="{{ route('configuracoes-sistema.utilidades.export', ['tipo' => 'completo']) }}" class="inline-flex items-center gap-2 rounded-lg bg-brand-500 px-4 py-2.5 text-sm font-medium text-white hover:bg-brand-600">
                        Exportar por completo
                    </button>
                    <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">ZIP com banco de dados e todos os anexos.</p>
                </div>
                <div>
                    <button type="submit" formaction="{{ route('configuracoes-sistema.utilidades.export', ['tipo' => 'banco']) }}" class="inline-flex items-center gap-2 rounded-lg border border-gray-300 bg-white px-4 py-2.5 text-sm font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-300">
                        Exportar somente banco de dados
                    </button>
                    <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">Arquivo .sql (MySQL/MariaDB) ou cópia do SQLite.</p>
                </div>
                <div>
                    <button type="submit" formaction="{{ route('configuracoes-sistema.utilidades.export', ['tipo' => 'anexos']) }}" class="inline-flex items-center gap-2 rounded-lg border border-gray-300 bg-white px-4 py-2.5 text-sm font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-300">
                        Exportar somente anexos
                    </button>
                    <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">ZIP com o conteúdo de storage/app/public.</p>
                </div>
            </div>
        </form>
    </div>

    {{-- Importar --}}
    <div class="rounded-lg border border-gray-200 bg-white p-6 shadow-theme-sm dark:border-gray-800 dark:bg-gray-800">
        <h2 class="mb-4 text-lg font-semibold text-gray-800 dark:text-white/90">Importar</h2>
        <p class="mb-4 text-sm text-gray-600 dark:text-gray-400">Envie um arquivo exportado anteriormente. A importação pode sobrescrever dados existentes.</p>
        <form id="form-utilidades-import" action="{{ route('configuracoes-sistema.utilidades.import') }}" method="POST" enctype="multipart/form-data" target="iframe-terminal" onsubmit="return window.utilidadesImportOnSubmit ? window.utilidadesImportOnSubmit(event) : (typeof showTerminalPanel === 'function' && showTerminalPanel(), true);">
            @csrf
            <input type="hidden" name="stream" value="1">
            <div class="space-y-4">
                <div>
                    <label class="mb-2 block text-sm font-medium text-gray-700 dark:text-gray-300">Tipo de importação</label>
                    @php
                        $customImportOptions = function_exists('apply_filters') ? apply_filters('utilidades.import.options', []) : [];
                        if (!is_array($customImportOptions)) {
                            $customImportOptions = [];
                        }
                    @endphp
                    <div class="space-y-2">
                        <label class="flex items-center gap-2">
                            <input type="radio" name="tipo" value="completo" {{ old('tipo') === 'completo' ? 'checked' : '' }} class="h-4 w-4 border-gray-300 text-brand-500 focus:ring-brand-500 dark:border-gray-600 dark:bg-gray-700">
                            <span class="text-sm text-gray-700 dark:text-gray-300">Importar completo</span>
                        </label>
                        <label class="flex items-center gap-2">
                            <input type="radio" name="tipo" value="banco" {{ old('tipo') === 'banco' ? 'checked' : '' }} class="h-4 w-4 border-gray-300 text-brand-500 focus:ring-brand-500 dark:border-gray-600 dark:bg-gray-700">
                            <span class="text-sm text-gray-700 dark:text-gray-300">Importar somente banco de dados</span>
                        </label>
                        <label class="flex items-center gap-2">
                            <input type="radio" name="tipo" value="anexos" {{ old('tipo') === 'anexos' ? 'checked' : '' }} class="h-4 w-4 border-gray-300 text-brand-500 focus:ring-brand-500 dark:border-gray-600 dark:bg-gray-700">
                            <span class="text-sm text-gray-700 dark:text-gray-300">Importar somente anexos</span>
                        </label>
                        @foreach($customImportOptions as $opt)
                            @if(is_array($opt) && !empty($opt['value']) && !empty($opt['label']))
                                <label class="flex flex-wrap items-center gap-2">
                                    <input type="radio" name="tipo" value="{{ $opt['value'] }}" {{ old('tipo') === $opt['value'] ? 'checked' : '' }} class="h-4 w-4 border-gray-300 text-brand-500 focus:ring-brand-500 dark:border-gray-600 dark:bg-gray-700">
                                    <span class="text-sm text-gray-700 dark:text-gray-300">{{ $opt['label'] }}</span>
                                    @if(!empty($opt['from_plugin']))
                                        <span class="inline-flex items-center rounded-md border border-violet-200 bg-violet-50 px-2 py-0.5 text-xs font-medium text-violet-800 dark:border-violet-800 dark:bg-violet-950/50 dark:text-violet-200" title="Funcionalidade fornecida por um plugin ativo">Plugin</span>
                                    @endif
                                </label>
                            @endif
                        @endforeach
                    </div>
                </div>
            <div class="mb-3 rounded-lg border border-gray-200 bg-gray-50 p-3 dark:border-gray-700 dark:bg-gray-900/20">
                <p class="mb-2 text-xs font-medium text-gray-700 dark:text-gray-300">Envio do arquivo</p>
                <div class="flex flex-wrap items-center gap-3">
                    <label class="flex items-center gap-2 text-sm text-gray-700 dark:text-gray-300">
                        <input type="radio" name="import_modo_envio_arquivo" value="unico" checked class="h-4 w-4 border-gray-300 text-brand-500 focus:ring-brand-500 dark:border-gray-600 dark:bg-gray-700">
                        Arquivo único
                    </label>
                    <label class="flex items-center gap-2 text-sm text-gray-700 dark:text-gray-300">
                        <input type="radio" name="import_modo_envio_arquivo" value="partes" class="h-4 w-4 border-gray-300 text-brand-500 focus:ring-brand-500 dark:border-gray-600 dark:bg-gray-700">
                        Arquivos em partes
                    </label>
                </div>
                <p class="mt-2 text-xs text-gray-600 dark:text-gray-400">Escolha um modo para não enviar campos/arquivos conflitantes.</p>
            </div>
                <div>
                    <label for="arquivo" class="mb-2 block text-sm font-medium text-gray-700 dark:text-gray-300">Arquivo(s)</label>
                    <div id="wrap-arquivo-unico">
                        <input type="file" name="arquivo" id="arquivo" accept=".zip,.sql" class="block w-full rounded-lg border border-gray-300 bg-white text-sm text-gray-700 file:mr-4 file:rounded-lg file:border-0 file:bg-gray-100 file:px-4 file:py-2 file:text-gray-700 focus:border-brand-500 focus:ring-brand-500 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-300 dark:file:bg-gray-600 dark:file:text-gray-200">
                        <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">Completo/anexos: .zip. Banco: .sql ou .zip com dump.</p>
                    </div>
                    <div id="wrap-arquivos-mos-export" class="mt-2 hidden">
                        <div class="mb-2 rounded-lg border border-blue-200 bg-blue-50 p-3 text-xs text-blue-900 dark:border-blue-800 dark:bg-blue-950/40 dark:text-blue-100">
                            <strong>Export M-OS em partes (~50 MB):</strong> todos os ZIPs devem ter o mesmo <code class="rounded bg-blue-100 px-0.5 dark:bg-blue-900">export_id</code> em <code class="rounded bg-blue-100 px-0.5 dark:bg-blue-900">export_meta.json</code>. Importe na ordem <strong>1 → N</strong> (pode selecionar vários ficheiros de uma vez — a ordem é definida por <code>part_index</code>) ou envie <strong>uma parte por vez</strong>; o sistema lembra a última parte concluída até terminar.
                        </div>
                        <input type="file" name="arquivos[]" id="arquivos_mos_export" multiple accept=".zip,.sql" disabled class="block w-full rounded-lg border border-gray-300 bg-white text-sm text-gray-700 file:mr-4 file:rounded-lg file:border-0 file:bg-gray-100 file:px-4 file:py-2 file:text-gray-700 focus:border-brand-500 focus:ring-brand-500 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-300 dark:file:bg-gray-600 dark:file:text-gray-200">
                        <p id="mos-export-upload-hint" class="mt-1 text-xs text-gray-500 dark:text-gray-400">Com vários ficheiros, o envio é <strong>sequencial</strong> (um de cada vez) com barra de progresso; a importação no servidor segue em seguida. A ordem de envio é a da sua seleção — para partes v2, deve corresponder a 1→N (<code>part_index</code>).</p>
                    </div>
                    <div id="wrap-arquivos-partes" class="mt-2 hidden">
                        <input type="file" name="arquivos_partes[]" id="arquivos_partes" multiple disabled accept=".zip,.part,.001,.002,.003,.004,.005,.006,.007,.008,.009" class="block w-full rounded-lg border border-gray-300 bg-white text-sm text-gray-700 file:mr-4 file:rounded-lg file:border-0 file:bg-gray-100 file:px-4 file:py-2 file:text-gray-700 focus:border-brand-500 focus:ring-brand-500 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-300 dark:file:bg-gray-600 dark:file:text-gray-200">
                        <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                            Para importação por partes (ex.: <code>.part001.zip</code>, <code>.part002.zip</code>...). Mesmo com “Parte 1 + manifest”, envie
                            <strong>todas</strong> as partes.
                        </p>
                    </div>
                    @foreach($customImportOptions as $opt)
                        @if(is_array($opt) && !empty($opt['hint']))
                            <p class="mt-1 text-xs text-gray-500 dark:text-gray-400 import-opt-hint" data-tipo="{{ $opt['value'] ?? '' }}">{{ $opt['hint'] }}</p>
                        @endif
                    @endforeach
                </div>
                <div class="flex gap-3 pt-2">
                    <button type="submit" class="rounded-lg bg-brand-500 px-4 py-2.5 text-sm font-medium text-white hover:bg-brand-600">Importar</button>
                    <a href="{{ route('configuracoes-sistema.index') }}" class="rounded-lg border border-gray-300 bg-white px-4 py-2.5 text-sm font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-300">Voltar</a>
                </div>
            </div>
        </form>
    </div>
</div>

<div class="mt-6 rounded-lg border border-amber-200 bg-amber-50 p-4 text-sm text-amber-800 dark:border-amber-800 dark:bg-amber-500/10 dark:text-amber-200">
    <strong>Atenção:</strong> A importação de banco pode substituir todos os dados atuais. Use em ambiente de teste ou após backup. Em MySQL/MariaDB o arquivo .sql é executado de uma vez; em SQLite, cada instrução do dump é aplicada em sequência (o que permite login após importar).
</div>

{{-- Backups disponíveis --}}
@if(count($backups ?? []) > 0)
<div class="mt-8 rounded-lg border border-gray-200 bg-white p-6 shadow-theme-sm dark:border-gray-800 dark:bg-gray-800">
    <div class="mb-4">
        <h2 class="text-lg font-semibold text-gray-800 dark:text-white/90">Backups disponíveis</h2>
        <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">
            Pastas com <code class="rounded bg-gray-100 px-0.5 dark:bg-gray-700">dump.sql</code> e anexos (<code class="rounded bg-gray-100 px-0.5 dark:bg-gray-700">anexos.zip</code> legado ou vários <code class="rounded bg-gray-100 px-0.5 dark:bg-gray-700">anexos-part-NNN.zip</code>). <strong>Cópia antes de zerar</strong> = segurança ao usar &quot;Zerar sistema&quot;. <strong>Cópia antes de restaurar</strong> = estado atual salvo ao restaurar outro backup (em hospedagem fraca pode falhar por tempo/memória; use a opção abaixo). O terminal mostra o log em tempo real.
        </p>
    </div>
    <ul class="space-y-3">
        @foreach($backups as $backup)
        <li class="flex flex-wrap items-center justify-between gap-3 rounded-lg border border-gray-200 bg-gray-50 py-3 px-4 dark:border-gray-700 dark:bg-gray-700/50">
            <div class="min-w-0">
                <span class="block text-sm font-medium text-gray-800 dark:text-gray-200">{{ $backup['data_formatada'] ?? $backup['nome'] }}</span>
                <span class="block text-xs text-gray-600 dark:text-gray-400">{{ $backup['tipo_label'] ?? 'Backup' }}</span>
                <span class="mt-1 block font-mono text-xs text-gray-500 dark:text-gray-500">{{ $backup['nome'] }}</span>
            </div>
            <div class="flex shrink-0 items-center gap-2">
                <form action="{{ route('configuracoes-sistema.utilidades.restaurar-backup') }}" method="POST" class="inline max-w-full" target="iframe-terminal" onsubmit="(function(f){var p=f.querySelector('[name=pular_pre_backup]');var r=f.querySelector('[name=confirmar_risco_sem_pre_backup]');if(p&&p.checked){if(!r||!r.checked){alert('Marque também «Confirmo o risco…» para restaurar sem cópia de segurança.');return false;}if(!confirm('Sem cópia do estado atual: os anexos atuais serão apagados antes de importar o backup. Esta operação é irreversível se não tiver outro backup. Continuar?'))return false;}else{if(!confirm('Será criada uma cópia do estado atual (dump + anexos em partes) antes de restaurar. O log aparecerá no terminal. Continuar?'))return false;}typeof showTerminalPanel==='function'&&showTerminalPanel();return true;})(this);">
                    @csrf
                    <input type="hidden" name="pasta" value="{{ $backup['nome'] }}">
                    <input type="hidden" name="stream" value="1">
                    <div class="mb-2 flex w-full min-w-0 flex-col gap-1.5 text-left text-xs text-gray-600 dark:text-gray-400">
                        <label class="flex cursor-pointer items-start gap-2">
                            <input type="checkbox" name="pular_pre_backup" value="1" class="restore-pular-pre mt-0.5 h-4 w-4 rounded border-gray-300 text-brand-500 focus:ring-brand-500 dark:border-gray-600 dark:bg-gray-700">
                            <span><strong>Sem pré-backup</strong> (hospedagem fraca): não grava cópia do estado atual; apaga anexos em <code class="rounded bg-gray-100 px-0.5 dark:bg-gray-700">storage/app/public</code> e restaura o banco + anexos do backup selecionado.</span>
                        </label>
                        <label class="flex cursor-pointer items-start gap-2 pl-6">
                            <input type="checkbox" name="confirmar_risco_sem_pre_backup" value="1" class="mt-0.5 h-4 w-4 rounded border-gray-300 text-brand-500 focus:ring-brand-500 dark:border-gray-600 dark:bg-gray-700">
                            <span>Confirmo que não terei cópia automática do estado atual e aceito o risco.</span>
                        </label>
                    </div>
                    <button type="submit" class="rounded-lg bg-brand-500 px-3 py-1.5 text-sm font-medium text-white hover:bg-brand-600">Restaurar tudo</button>
                </form>
                <form action="{{ route('configuracoes-sistema.utilidades.excluir-backup') }}" method="POST" class="inline" onsubmit="return confirm('Excluir este backup? Esta ação não pode ser desfeita.');">
                    @csrf
                    <input type="hidden" name="pasta" value="{{ $backup['nome'] }}">
                    <button type="submit" class="rounded-lg border border-red-300 bg-red-50 px-3 py-1.5 text-sm font-medium text-red-700 hover:bg-red-100 dark:border-red-800 dark:bg-red-900/30 dark:text-red-300 dark:hover:bg-red-900/50">Excluir</button>
                </form>
            </div>
        </li>
        @endforeach
    </ul>
</div>
@else
{{-- Backups disponíveis (vazio) --}}
<div class="mt-8 rounded-lg border border-gray-200 bg-white p-6 shadow-theme-sm dark:border-gray-800 dark:bg-gray-800">
    <h2 class="text-lg font-semibold text-gray-800 dark:text-white/90">Backups disponíveis</h2>
    <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">Nenhum backup disponível no momento.</p>
</div>
@endif

{{-- Zerar sistema (padrão de fábrica) --}}
<div class="mt-8 rounded-lg border border-red-200 bg-white p-6 shadow-theme-sm dark:border-red-900/50 dark:bg-gray-800">
    <h2 class="mb-2 text-lg font-semibold text-gray-800 dark:text-white/90">Zerar sistema (padrão de fábrica)</h2>
    <p class="mb-3 text-sm text-gray-600 dark:text-gray-400">
        Remove todos os dados do sistema (clientes, OS, financeiro, produtos, pedidos, propostas comerciais, etc.) e os anexos em <code class="rounded bg-gray-100 px-1 dark:bg-gray-700">storage/app/public</code>, mantendo apenas <strong>usuários, perfis e permissões</strong>. Por defeito é criada uma cópia em <code class="rounded bg-gray-100 px-1 dark:bg-gray-700">storage/app/backups/pre-reset-{data-hora}</code> (<code class="rounded bg-gray-100 px-0.5 dark:bg-gray-700">dump.sql</code> + anexos em vários ZIPs quando necessário). Se o backup falhar (tempo/memória), marque <strong>sem pré-backup</strong> abaixo: os anexos são apagados primeiro e o processo segue para o padrão de fábrica <strong>sem</strong> cópia de segurança automática.
    </p>
    <p class="mb-2 text-sm font-medium text-gray-700 dark:text-gray-300">Após o truncate, o sistema recria automaticamente o padrão de fábrica:</p>
    <ul class="mb-4 list-disc space-y-1 pl-5 text-sm text-gray-600 dark:text-gray-400">
        <li>Categorias de documento, tabelas de preço, tags, termos de garantia, status de OS</li>
        <li>Contas bancárias, formas de pagamento, plano de contas, configurações financeiras, centros de custo</li>
        <li>Configuração de encerramento de OS (plano de conta / centro de custo) e adquirentes padrão</li>
        <li><strong>Numeração de pedidos de venda e de propostas comerciais</strong> (máscara, dígitos e próximo número em Configurações)</li>
        <li><strong>Modelos de documento de propostas comerciais</strong> (Word estilo impressão OS, segundo DOCX padrão e modelo HTML — em base vazia, ids 1 a 3 com ficheiros em <code class="rounded bg-gray-100 px-0.5 dark:bg-gray-700">storage/app/private/proposta_documento_modelos/</code>)</li>
        <li>Cadastro da empresa de exemplo e <strong>~20 produtos iniciais</strong> de informática (kits e variações)</li>
    </ul>
    <p class="mb-4 text-xs text-gray-500 dark:text-gray-500">Não roda o seeder de permissões (já preservado) nem ordens de serviço de demonstração. Ficheiros só em <code class="rounded bg-gray-100 px-0.5 dark:bg-gray-700">storage/app/private</code> fora de propostas não são apagados por esta rotina.</p>
    <form action="{{ route('configuracoes-sistema.utilidades.zerar-fabrica') }}" method="POST" id="form-zerar-fabrica" target="iframe-terminal" onsubmit="(function(f){var p=f.querySelector('[name=pular_pre_backup]');var r=f.querySelector('[name=confirmar_risco_sem_pre_backup]');if(p&&p.checked){if(!r||!r.checked){alert('Marque também «Confirmo o risco…» para prosseguir sem cópia de segurança.');return false;}if(!confirm('Sem pré-backup: os anexos serão apagados e o sistema irá para o padrão de fábrica. Não haverá cópia automática do estado atual. Continuar?'))return false;}else{if(!confirm('Será criado backup (dump + anexos em partes) e o sistema será zerado. O log aparecerá no terminal. Não saia até finalizar. Continuar?'))return false;}typeof showTerminalPanel==='function'&&showTerminalPanel();return true;})(this);">
        @csrf
        <input type="hidden" name="stream" value="1">
        <div class="mb-4 space-y-3">
            <label class="flex cursor-pointer items-start gap-3">
                <input type="checkbox" name="pular_pre_backup" value="1" {{ old('pular_pre_backup') ? 'checked' : '' }}
                    class="mt-1 h-4 w-4 rounded border-gray-300 text-brand-500 focus:ring-brand-500 dark:border-gray-600 dark:bg-gray-700">
                <span class="text-sm text-gray-700 dark:text-gray-300">
                    <strong>Sem pré-backup</strong> (hospedagem fraca): não criar cópia em <code class="rounded bg-gray-100 px-0.5 dark:bg-gray-700">storage/app/backups</code>; apagar anexos em <code class="rounded bg-gray-100 px-0.5 dark:bg-gray-700">storage/app/public</code> e aplicar o zeramento (truncate + seeders padrão de fábrica).
                </span>
            </label>
            <label class="flex cursor-pointer items-start gap-3 pl-8">
                <input type="checkbox" name="confirmar_risco_sem_pre_backup" value="1" {{ old('confirmar_risco_sem_pre_backup') ? 'checked' : '' }}
                    class="mt-1 h-4 w-4 rounded border-gray-300 text-brand-500 focus:ring-brand-500 dark:border-gray-600 dark:bg-gray-700">
                <span class="text-sm text-gray-700 dark:text-gray-300">
                    Confirmo que não terei cópia automática do estado atual e aceito o risco (obrigatório se marcou a opção acima).
                </span>
            </label>
        </div>
        <label class="mb-4 flex cursor-pointer items-start gap-3">
            <input type="hidden" name="confirmar_zerar" value="0">
            <input type="checkbox" name="confirmar_zerar" value="1" {{ old('confirmar_zerar') ? 'checked' : '' }} required
                class="mt-1 h-4 w-4 rounded border-gray-300 text-brand-500 focus:ring-brand-500 dark:border-gray-600 dark:bg-gray-700">
            <span class="text-sm text-gray-700 dark:text-gray-300">
                Li que <strong>usuários, perfis e permissões serão mantidos</strong> e que os restantes dados e anexos serão repostos pelo padrão de fábrica. Desejo zerar o sistema.
            </span>
        </label>
        <button type="submit" class="rounded-lg bg-red-600 px-4 py-2.5 text-sm font-medium text-white hover:bg-red-700">
            Zerar sistema (padrão de fábrica)
        </button>
    </form>
</div>

@endsection
