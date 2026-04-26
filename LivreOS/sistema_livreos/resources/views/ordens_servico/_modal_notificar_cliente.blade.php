{{-- Modal Notificar Cliente (e-mail / WhatsApp). Requer $ordem. --}}
@php
    $emailNotifAssunto = \App\Services\NotificacaoOsService::aplicarTemplateTags(\App\Services\NotificacaoOsService::getEmailTemplateAssunto(), $ordem, $ordem->status);
    $emailNotifCorpo = \App\Services\NotificacaoOsService::aplicarTemplateTags(\App\Services\NotificacaoOsService::getEmailTemplateCorpo(), $ordem, $ordem->status);
    $whatsappTelefone = \App\Services\NotificacaoOsService::getTelefoneWhatsAppCliente($ordem);
    $whatsappMensagem = \App\Services\NotificacaoOsService::aplicarTemplateTags(\App\Services\NotificacaoOsService::getWhatsAppTemplate(), $ordem, $ordem->status);
    $whatsappLink = $whatsappTelefone ? 'https://wa.me/' . preg_replace('/\D/', '', $whatsappTelefone) . '?text=' . rawurlencode($whatsappMensagem) : null;
@endphp

<div id="modalNotificarCliente" class="fixed inset-0 hidden overflow-y-auto bg-black/50 p-2 sm:p-4" style="z-index:2147483000;" onclick="if(event.target===this)fecharModalNotificarCliente()">
    <div class="flex min-h-full items-end justify-center sm:items-center">
        <div class="flex max-h-[calc(100dvh-1rem)] w-full max-w-2xl flex-col overflow-hidden rounded-2xl bg-white shadow-xl dark:bg-gray-800 sm:max-h-[calc(100dvh-3rem)] sm:rounded-lg" onclick="event.stopPropagation()">
            <div class="relative z-10 flex shrink-0 items-center justify-between border-b border-gray-200 bg-white px-4 pb-3 pt-[max(0.75rem,env(safe-area-inset-top))] dark:border-gray-700 dark:bg-gray-800 sm:pt-4">
                <h3 class="min-w-0 flex-1 pr-2 text-lg font-semibold leading-snug text-gray-800 dark:text-white/90">Notificar cliente</h3>
                <button type="button" onclick="fecharModalNotificarCliente()" class="shrink-0 text-gray-500 hover:text-gray-700" aria-label="Fechar">✕</button>
            </div>
            <div class="min-h-0 flex-1 overflow-y-auto overscroll-y-contain p-4 sm:max-h-[min(78dvh,70vh)]">
            <div class="mb-4 flex gap-2 border-b border-gray-200 dark:border-gray-700">
                <button type="button" id="tabNotifEmail" onclick="mostrarTabNotif('email')" class="border-b-2 border-brand-500 px-4 py-2 text-sm font-medium text-brand-600 dark:text-brand-400">E-mail</button>
                <button type="button" id="tabNotifWhatsapp" onclick="mostrarTabNotif('whatsapp')" class="border-b-2 border-transparent px-4 py-2 text-sm font-medium text-gray-600 hover:text-gray-800 dark:text-gray-400">WhatsApp</button>
            </div>
            <div id="conteudoNotifEmail" class="space-y-4">
                <div>
                    <label class="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-300">Preview da mensagem</label>
                    <div class="max-h-48 overflow-auto rounded-lg border border-gray-200 bg-gray-50 p-4 text-sm text-gray-800 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90">
                        <p class="mb-1"><span class="font-medium text-gray-500 dark:text-gray-400">Assunto:</span> {{ $emailNotifAssunto }}</p>
                        <div class="mt-2 border-t border-gray-200 pt-2 dark:border-gray-600 prose prose-sm max-w-none dark:prose-invert">{!! $emailNotifCorpo !!}</div>
                    </div>
                </div>
                <div>
                    <label class="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-300">E-mail destino</label>
                    <input type="email" id="notifEmailDestino" value="{{ old('email', $ordem->contato?->email ?? $ordem->cliente?->contatos->whereNotNull('email')->first()?->email ?? '') }}" class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm dark:border-gray-700 dark:bg-gray-900 dark:text-white/90 @error('email') border-error-500 @enderror">
                    @error('email')
                        <p class="mt-1 text-sm text-error-600 dark:text-error-400">{{ $message }}</p>
                    @enderror
                </div>
                <div>
                    <label class="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-300">Assunto</label>
                    <input type="text" id="notifEmailAssunto" value="{{ e($emailNotifAssunto) }}" class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm dark:border-gray-700 dark:bg-gray-900 dark:text-white/90">
                </div>
                <div>
                    <label class="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-300">Preview da mensagem</label>
                    <div class="max-h-32 overflow-auto rounded-lg border border-gray-200 bg-gray-50 p-4 text-sm text-gray-800 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90 prose prose-sm max-w-none" id="notifEmailPreview"></div>
                </div>
                <div>
                    <label class="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-300">Mensagem (opcional)</label>
                    <textarea id="notifEmailMensagem" rows="5" placeholder="Deixe em branco para usar o template configurado..." class="js-jodit js-jodit-notif-email w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm dark:border-gray-700 dark:bg-gray-900 dark:text-white/90">{{ $emailNotifCorpo }}</textarea>
                </div>
                <p class="text-xs text-gray-500 dark:text-gray-400">A OS será enviada em PDF em anexo. Assunto e mensagem acima vêm do template configurado em Configurações → Notificações de OS; você pode editá-los.</p>
                <form method="POST" action="{{ route('ordens-servico.enviar-email', $ordem) }}" id="formNotifEmail" onsubmit="return confirm('Confirmar envio do e-mail com a OS em anexo?');">
                    @csrf
                    <input type="hidden" name="email" id="formNotifEmailDestino">
                    <input type="hidden" name="assunto" id="formNotifEmailAssunto">
                    <input type="hidden" name="mensagem" id="formNotifEmailMensagem">
                    <button type="submit" class="rounded-lg bg-brand-500 px-4 py-2.5 text-sm font-medium text-white hover:bg-brand-600">Confirmar e enviar e-mail</button>
                </form>
            </div>
            <div id="conteudoNotifWhatsapp" class="hidden space-y-4">
                <div>
                    <label class="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-300">Preview da mensagem</label>
                    <div class="max-h-48 overflow-auto rounded-lg border border-gray-200 bg-gray-50 p-4 text-sm text-gray-800 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90 whitespace-pre-wrap" id="notifWhatsappPreview">{{ \App\Services\NotificacaoOsService::aplicarTemplateTags(\App\Services\NotificacaoOsService::getWhatsAppTemplate(), $ordem, $ordem->status) }}</div>
                </div>
                <p class="text-xs text-gray-500 dark:text-gray-400">
                    @if(\App\Services\NotificacaoOsService::getWhatsAppModo() === 'manual')
                        Modo manual: use "Enviar Manualmente" para abrir o WhatsApp (no celular abre no app) com o número e a mensagem já preenchidos. Depois clique em "Registrar envio".
                    @else
                        A mensagem será enviada automaticamente via API.
                    @endif
                </p>
                @if(\App\Services\NotificacaoOsService::getWhatsAppModo() === 'manual')
                <div class="flex gap-2">
                    @if($whatsappLink)
                    <a href="{{ $whatsappLink }}" id="notifWhatsappLink" target="_blank" rel="noopener noreferrer" class="rounded-lg border border-gray-300 bg-white px-4 py-2.5 text-sm font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-300 inline-flex items-center gap-2">
                        Enviar Manualmente
                    </a>
                    @else
                    <span class="rounded-lg border border-gray-200 bg-gray-100 px-4 py-2.5 text-sm text-gray-500 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-400">Enviar Manualmente (sem número WhatsApp)</span>
                    @endif
                    <form method="POST" action="{{ route('ordens-servico.enviar-whatsapp', $ordem) }}" class="inline" onsubmit="return confirm('Registrar que a mensagem foi enviada manualmente ao cliente?');">
                        @csrf
                        <button type="submit" class="rounded-lg bg-brand-500 px-4 py-2.5 text-sm font-medium text-white hover:bg-brand-600">Registrar envio manual</button>
                    </form>
                </div>
                @else
                <form method="POST" action="{{ route('ordens-servico.enviar-whatsapp', $ordem) }}" onsubmit="return confirm('Confirmar envio da mensagem WhatsApp ao cliente?');">
                    @csrf
                    <button type="submit" class="rounded-lg bg-brand-500 px-4 py-2.5 text-sm font-medium text-white hover:bg-brand-600">Confirmar e enviar WhatsApp</button>
                </form>
                @endif
            </div>
        </div>
        </div>
    </div>
</div>
