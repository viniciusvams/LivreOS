{{-- JS do modal Notificar cliente. Requer $ordem. Incluir dentro de <script> (sem tags extras). Na edição, initSingleRichTextEditor já vem do _form. --}}
if (typeof initSingleRichTextEditor !== 'function') {
    window.initSingleRichTextEditor = function(textarea) {
        if (!textarea || textarea.dataset.joditInited === '1' || typeof Jodit === 'undefined') {
            return;
        }
        textarea.dataset.joditInited = '1';
        var editor = Jodit.make(textarea, {
            theme: 'default',
            toolbarAdaptive: true,
            disablePlugins: 'source',
            buttons: [
                'bold', 'italic', 'underline', '|',
                'ul', 'ol', '|',
                'link', '|',
                'hr'
            ],
            height: 360
        });
        editor.events.on('change', function() {
            textarea.value = editor.value;
        });
    };
}

document.addEventListener('DOMContentLoaded', function() {
    var formNotif = document.getElementById('formNotifEmail');
    if (formNotif) {
        formNotif.addEventListener('submit', function() {
            var dest = document.getElementById('formNotifEmailDestino');
            var ass = document.getElementById('formNotifEmailAssunto');
            var msgH = document.getElementById('formNotifEmailMensagem');
            if (dest) dest.value = document.getElementById('notifEmailDestino').value;
            if (ass) ass.value = document.getElementById('notifEmailAssunto').value;
            var msgTa = document.getElementById('notifEmailMensagem');
            var msgHtml = msgTa && msgTa.jodit && typeof msgTa.jodit.value === 'string' ? msgTa.jodit.value : (msgTa ? msgTa.value : '');
            if (msgH) msgH.value = msgHtml;
        });
    }
});

function linkifyPreviewText(text) {
    if (text == null || text === '') return '';
    text = String(text).replace(/<\/?br\s*\/?>/gi, '\n');
    var div = document.createElement('div');
    div.textContent = text;
    var escaped = div.innerHTML;
    escaped = escaped.replace(/\n/g, '<br>');
    return escaped.replace(/(https?:\/\/[^\s<]+)/g, '<a href="$1" target="_blank" rel="noopener noreferrer" class="text-brand-600 underline hover:text-brand-700 dark:text-brand-400 dark:hover:text-brand-300">$1</a>');
}

function linkifyHtml(html) {
    if (html == null || html === '') return '';
    return html.split(/(<[^>]*>)/).map(function(part) {
        if (part.indexOf('<') === 0) return part;
        var div = document.createElement('div');
        div.textContent = part;
        var escaped = div.innerHTML;
        return escaped.replace(/(https?:\/\/[^\s<&]+(?:&amp;[^\s<]*)?)/g, '<a href="$1" target="_blank" rel="noopener noreferrer" class="text-brand-600 underline hover:text-brand-700 dark:text-brand-400 dark:hover:text-brand-300">$1</a>');
    }).join('');
}

(function() {
    var el = document.getElementById('notifWhatsappPreview');
    if (el && el.textContent && typeof linkifyPreviewText === 'function') el.innerHTML = linkifyPreviewText(el.textContent);
    var emailPreview = document.getElementById('notifEmailPreview');
    var emailMensagem = document.getElementById('notifEmailMensagem');
    if (emailPreview && emailMensagem && emailMensagem.value && typeof linkifyHtml === 'function') emailPreview.innerHTML = linkifyHtml(emailMensagem.value);
})();

function compartilharWhatsappOs(ordemId) {
    if (!ordemId || typeof fetch === 'undefined') {
        return;
    }
    var previewUrl = @json(route('ordens-servico.notificacao-preview', $ordem));
    fetch(previewUrl, { headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }, credentials: 'same-origin' })
        .then(function (r) { return r.ok ? r.json() : null; })
        .then(function (data) {
            var msg = (data && data.whatsapp_mensagem !== undefined && data.whatsapp_mensagem !== null) ? String(data.whatsapp_mensagem) : '';
            var digits = (data && data.whatsapp_digits) ? String(data.whatsapp_digits).replace(/\D/g, '') : '';
            var contatoSelect = document.getElementById('contato_id');
            if ((!digits || digits.length < 8) && contatoSelect) {
                var opt = contatoSelect.options[contatoSelect.selectedIndex];
                if (opt && opt.dataset && opt.dataset.whatsapp) {
                    digits = String(opt.dataset.whatsapp || '').replace(/\D/g, '');
                }
            }
            if (digits && !digits.startsWith('55') && (digits.length === 10 || digits.length === 11)) {
                digits = '55' + digits;
            }
            if (!digits || digits.length < 10) {
                window.alert('Não há número de WhatsApp disponível para esta OS. Cadastre telefone no contato do cliente ou selecione um contato com WhatsApp.');
                return;
            }
            var url = 'https://wa.me/' + digits + (msg ? '?text=' + encodeURIComponent(msg) : '');
            window.open(url, '_blank', 'noopener,noreferrer');
        })
        .catch(function () {
            window.alert('Não foi possível carregar a mensagem. Verifique a conexão e tente de novo.');
        });
}

function scheduleInitJoditNotifEmail() {
    setTimeout(function() {
        var ta = document.getElementById('notifEmailMensagem');
        if (!ta || typeof initSingleRichTextEditor !== 'function') return;
        if (ta.jodit) {
            ta.jodit.value = ta.value || '';
            return;
        }
        initSingleRichTextEditor(ta);
    }, 0);
}

function abrirModalNotificarCliente(ordemId) {
    document.querySelectorAll('.jodit-popup, .jodit-dialog, .jodit-tooltip').forEach(function(el) {
        if (!el.closest('#modalNotificarCliente')) {
            el.style.display = 'none';
        }
    });

    var contatoSelect = document.getElementById('contato_id');
    var notifEmailDestino = document.getElementById('notifEmailDestino');
    if (contatoSelect && notifEmailDestino) {
        var opt = contatoSelect.options[contatoSelect.selectedIndex];
        if (opt && opt.value && opt.dataset.email !== undefined) {
            notifEmailDestino.value = opt.dataset.email || '';
        }
    }
    var notifWhatsappLink = document.getElementById('notifWhatsappLink');
    var previewEl = document.getElementById('notifWhatsappPreview');

    function mostrarModal() {
        if (contatoSelect && notifWhatsappLink && previewEl) {
            var opt2 = contatoSelect.options[contatoSelect.selectedIndex];
            if (opt2 && opt2.value && opt2.dataset.whatsapp) {
                var digits = (opt2.dataset.whatsapp || '').replace(/\D/g, '');
                if (digits) {
                    var msgEnc = encodeURIComponent(previewEl.textContent || '');
                    notifWhatsappLink.href = 'https://wa.me/' + digits + (msgEnc ? '?text=' + msgEnc : '');
                    notifWhatsappLink.style.display = '';
                } else {
                    notifWhatsappLink.href = '#';
                }
            }
        }
        var modalEl = document.getElementById('modalNotificarCliente');
        if (modalEl) {
            modalEl.classList.remove('hidden');
            document.body.style.overflow = 'hidden';
            scheduleInitJoditNotifEmail();
        }
    }

    if (ordemId && typeof fetch !== 'undefined') {
        fetch('/ordens-servico/' + ordemId + '/notificacao-preview', { headers: { 'Accept': 'application/json' }, credentials: 'same-origin' })
            .then(function(r) { return r.ok ? r.json() : null; })
            .then(function(data) {
                if (data) {
                    var notifEmailAssunto = document.getElementById('notifEmailAssunto');
                    var notifEmailMensagem = document.getElementById('notifEmailMensagem');
                    var notifEmailPreview = document.getElementById('notifEmailPreview');
                    if (notifEmailAssunto && data.email_assunto !== undefined) notifEmailAssunto.value = data.email_assunto;
                    if (notifEmailMensagem && data.email_corpo !== undefined) {
                        notifEmailMensagem.value = data.email_corpo;
                        if (notifEmailMensagem.jodit) {
                            notifEmailMensagem.jodit.value = data.email_corpo;
                        }
                        if (notifEmailPreview && typeof linkifyHtml === 'function') notifEmailPreview.innerHTML = linkifyHtml(data.email_corpo);
                    }
                    if (previewEl && data.whatsapp_mensagem !== undefined) {
                        if (typeof linkifyPreviewText === 'function') {
                            previewEl.innerHTML = linkifyPreviewText(data.whatsapp_mensagem);
                        } else {
                            previewEl.textContent = data.whatsapp_mensagem;
                        }
                    }
                    if (notifWhatsappLink && data.whatsapp_mensagem !== undefined) {
                        var msg = data.whatsapp_mensagem ? encodeURIComponent(data.whatsapp_mensagem) : '';
                        var digits = data.whatsapp_digits || '';
                        notifWhatsappLink.href = digits ? ('https://wa.me/' + digits + (msg ? '?text=' + msg : '')) : '#';
                    }
                }
                mostrarModal();
            })
            .catch(function() { mostrarModal(); });
    } else {
        if (contatoSelect && notifWhatsappLink && previewEl) {
            var opt3 = contatoSelect.options[contatoSelect.selectedIndex];
            if (opt3 && opt3.value && opt3.dataset.whatsapp) {
                var d = (opt3.dataset.whatsapp || '').replace(/\D/g, '');
                if (d) {
                    var m = encodeURIComponent(previewEl.textContent || '');
                    notifWhatsappLink.href = 'https://wa.me/' + d + (m ? '?text=' + m : '');
                    notifWhatsappLink.style.display = '';
                } else {
                    notifWhatsappLink.href = '#';
                }
            }
        }
        var modalEl2 = document.getElementById('modalNotificarCliente');
        if (modalEl2) {
            modalEl2.classList.remove('hidden');
            document.body.style.overflow = 'hidden';
            scheduleInitJoditNotifEmail();
        }
    }
}

function fecharModalNotificarCliente() {
    var m = document.getElementById('modalNotificarCliente');
    if (m) {
        m.classList.add('hidden');
        document.body.style.overflow = '';
    }
}

function mostrarTabNotif(tab) {
    if (tab === 'email') {
        document.getElementById('conteudoNotifEmail').classList.remove('hidden');
        document.getElementById('conteudoNotifWhatsapp').classList.add('hidden');
        document.getElementById('tabNotifEmail').classList.add('border-brand-500');
        document.getElementById('tabNotifEmail').classList.remove('border-transparent');
        document.getElementById('tabNotifWhatsapp').classList.remove('border-brand-500');
        document.getElementById('tabNotifWhatsapp').classList.add('border-transparent');
        scheduleInitJoditNotifEmail();
    } else {
        document.getElementById('conteudoNotifEmail').classList.add('hidden');
        document.getElementById('conteudoNotifWhatsapp').classList.remove('hidden');
        document.getElementById('tabNotifWhatsapp').classList.add('border-brand-500');
        document.getElementById('tabNotifWhatsapp').classList.remove('border-transparent');
        document.getElementById('tabNotifEmail').classList.remove('border-brand-500');
        document.getElementById('tabNotifEmail').classList.add('border-transparent');
    }
}
