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
    <h1 class="mb-2 text-2xl font-semibold text-gray-800 dark:text-white/90">Notificações de OS</h1>
    <p class="text-gray-600 dark:text-gray-400">Configure o envio de e-mail e WhatsApp para ordens de serviço (cadastro e mudança de status).</p>
</div>

@if(empty($empresa) || empty(trim($empresa->email ?? '')))
<div class="mb-4 rounded-lg border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-800 dark:border-amber-700 dark:bg-amber-500/20 dark:text-amber-200">
    <strong>E-mail do emitente não configurado.</strong> Para que os e-mails enviados (teste e notificações de OS) exibam o remetente correto, configure o e-mail em
    <a href="{{ route('configuracoes-sistema.empresa') }}" class="font-medium underline hover:no-underline">Cadastro da Empresa</a>.
</div>
@endif

@if(session('success'))
<div class="mb-4 rounded-lg bg-success-100 px-4 py-3 text-sm text-success-700 dark:bg-success-500/20 dark:text-success-400">
    {{ session('success') }}
</div>
@endif

<form method="POST" action="{{ route('configuracoes-sistema.notificacoes-os.update') }}" id="form_notificacoes_os">
    @csrf
    @method('PUT')

    <div class="space-y-6">
        {{-- Aba Notificações --}}
        <div class="rounded-lg border border-gray-200 bg-white p-6 shadow-theme-sm dark:border-gray-800 dark:bg-gray-800">
            <h2 class="mb-4 text-lg font-semibold text-gray-800 dark:text-white/90">Notificação de OS</h2>

            <div class="space-y-4">
                <div>
                    <label class="mb-2 block text-sm font-medium text-gray-700 dark:text-gray-300">Notificação de OS</label>
                    <select name="notificacao_os_tipo" class="w-full max-w-md rounded-lg border border-gray-300 px-4 py-2.5 text-sm dark:border-gray-700 dark:bg-gray-900 dark:text-white/90">
                        <option value="nao_notificar" {{ $tipo === 'nao_notificar' ? 'selected' : '' }}>Não Notificar</option>
                        <option value="email_manual" {{ $tipo === 'email_manual' ? 'selected' : '' }}>Selecione a opção de notificação por e-mail no cadastro de OS</option>
                        <option value="email_automatico" {{ $tipo === 'email_automatico' ? 'selected' : '' }}>Enviar E-mail Automático</option>
                    </select>
                    <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">Define quando enviar e-mail com a OS em anexo (cadastro e/ou mudança de status).</p>
                </div>

                <div class="flex items-center gap-3">
                    <input type="hidden" name="notificacao_os_email_automatico" value="0">
                    <input type="checkbox" name="notificacao_os_email_automatico" id="email_automatico" value="1"
                        {{ $emailAutomatico ? 'checked' : '' }}
                        class="rounded border-gray-300 text-brand-500 focus:ring-brand-500">
                    <label for="email_automatico" class="text-sm text-gray-700 dark:text-gray-300">Ativar ou desativar o envio de e-mail automático na mudança de status da OS</label>
                </div>

                <div class="border-t border-gray-200 pt-4 dark:border-gray-700">
                    <h3 class="mb-2 text-sm font-medium text-gray-700 dark:text-gray-300">Template do E-mail</h3>
                    <p class="mb-3 text-xs text-gray-500 dark:text-gray-400">Use as mesmas tags do WhatsApp. O corpo aceita HTML (ex: &lt;p&gt;, &lt;strong&gt;, &lt;br&gt;). Use <strong>{SE_LINK_ASSINATURA}</strong> e <strong>{FIM_LINK_ASSINATURA}</strong> para marcar o trecho que só deve aparecer quando o status for o escolhido abaixo (ex.: link de aprovação). Entre as duas tags use <strong>{LINK_ASSINATURA_APROVACAO}</strong> para o link.</p>
                    <div class="mb-3">
                        <label class="mb-1 block text-xs font-medium text-gray-600 dark:text-gray-400">Incluir link de assinatura de aprovação quando o status for</label>
                        <select name="notificacao_os_status_link_assinatura" class="w-full max-w-md rounded-lg border border-gray-300 px-4 py-2.5 text-sm dark:border-gray-700 dark:bg-gray-900 dark:text-white/90">
                            <option value="">Nunca</option>
                            @foreach($statusOsList ?? [] as $nome => $label)
                                <option value="{{ $nome }}" {{ ($statusParaLinkAssinatura ?? '') === $nome ? 'selected' : '' }}>{{ $label }}</option>
                            @endforeach
                        </select>
                        <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">Quando a OS estiver nesse status, o trecho entre {SE_LINK_ASSINATURA} e {FIM_LINK_ASSINATURA} será mantido (com o link); caso contrário, esse trecho inteiro será removido da mensagem.</p>
                    </div>
                    <div class="mb-3">
                        <label class="mb-1 block text-xs font-medium text-gray-600 dark:text-gray-400">Assunto</label>
                        <input type="text" name="notificacao_os_email_template_assunto" id="email_template_assunto" value="{{ old('notificacao_os_email_template_assunto', $emailTemplateAssunto ?? '') }}"
                            placeholder="Ordem de Serviço {NUMERO_OS} - Status: {STATUS_OS}"
                            class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm dark:border-gray-700 dark:bg-gray-900 dark:text-white/90">
                    </div>
                    <div>
                        <label class="mb-1 block text-xs font-medium text-gray-600 dark:text-gray-400">Corpo da mensagem (HTML)</label>
                        <div class="flex gap-2 mb-1">
                            <button type="button" id="btn_preview_email" class="rounded-lg border border-gray-300 bg-white px-3 py-1.5 text-xs font-medium text-gray-700 shadow-sm hover:bg-gray-50 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-200 dark:hover:bg-gray-700">
                                Visualizar e-mail
                            </button>
                        </div>
                        <textarea name="notificacao_os_email_template_corpo" id="notificacao_os_email_template_corpo" rows="6"
                            class="js-jodit w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm text-gray-800 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90"
                            placeholder="<p>Prezado(a) {CLIENTE_NOME}, ...</p>">{!! old('notificacao_os_email_template_corpo', $emailTemplateCorpo ?? '') !!}</textarea>
                    </div>
                </div>

                <div class="border-t border-gray-200 pt-4 dark:border-gray-700" x-data="whatsappPreview($el.dataset.replacements ? JSON.parse($el.dataset.replacements) : {})" data-replacements='{{ json_encode($previewReplacements ?? []) }}'>
                    <div class="flex flex-wrap items-center justify-between gap-3 mb-3">
                        <h3 class="text-sm font-medium text-gray-700 dark:text-gray-300">Notificação do WhatsApp</h3>
                        <button type="button" @click="openPreview()" class="rounded-lg border border-gray-300 bg-white px-3 py-1.5 text-xs font-medium text-gray-700 shadow-sm hover:bg-gray-50 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-200 dark:hover:bg-gray-700">
                            Visualizar mensagem
                        </button>
                    </div>
                    <div class="mb-3 flex items-center gap-3">
                        <label class="text-sm text-gray-700 dark:text-gray-300">Modo de envio:</label>
                        <label class="flex items-center gap-2">
                            <input type="radio" name="whatsapp_modo" value="api" {{ ($whatsappModo ?? 'manual') === 'api' ? 'checked' : '' }}>
                            <span class="text-sm">API (envio automático)</span>
                        </label>
                        <label class="flex items-center gap-2">
                            <input type="radio" name="whatsapp_modo" value="manual" {{ ($whatsappModo ?? 'manual') === 'manual' ? 'checked' : '' }}>
                            <span class="text-sm">Manual (gera texto para copiar e enviar)</span>
                        </label>
                    </div>
                    <div class="flex items-center gap-3 mb-3">
                        <input type="hidden" name="notificacao_os_whatsapp_ativo" value="0">
                        <input type="checkbox" name="notificacao_os_whatsapp_ativo" id="whatsapp_ativo" value="1"
                            {{ $whatsappAtivo ? 'checked' : '' }}
                            class="rounded border-gray-300 text-brand-500 focus:ring-brand-500">
                        <label for="whatsapp_ativo" class="text-sm text-gray-700 dark:text-gray-300">Ativar notificação por WhatsApp</label>
                    </div>
                    <textarea name="notificacao_os_whatsapp_template" id="notificacao_os_whatsapp_template" rows="5"
                        x-model="template"
                        class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm dark:border-gray-700 dark:bg-gray-900 dark:text-white/90"
                        placeholder="Prezado(a), {CLIENTE_NOME} a OS de nº {NUMERO_OS} teve o status alterado para: {STATUS_OS}...">{{ old('notificacao_os_whatsapp_template', $whatsappTemplate) }}</textarea>
                    <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">Para negrito use: *palavra* | Para itálico use: _palavra_ | Para riscado use: ~palavra~</p>
                    {{-- Modal preview WhatsApp --}}
                    <div x-show="open" x-cloak class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/50" x-transition>
                        <div @click.outside="open = false" class="w-full max-w-md rounded-xl bg-white shadow-xl dark:bg-gray-800 border border-gray-200 dark:border-gray-700 overflow-hidden">
                            <div class="flex items-center justify-between border-b border-gray-200 px-4 py-3 dark:border-gray-700">
                                <h4 class="text-sm font-semibold text-gray-800 dark:text-white">Pré-visualização WhatsApp</h4>
                                <button type="button" @click="open = false" class="rounded-lg p-1 text-gray-500 hover:bg-gray-100 dark:hover:bg-gray-700">&times;</button>
                            </div>
                            <div class="max-h-[60vh] overflow-y-auto p-4">
                                <p class="whitespace-pre-wrap text-sm text-gray-700 dark:text-gray-300" x-html="previewHtml"></p>
                            </div>
                            <div class="border-t border-gray-200 px-4 py-2 dark:border-gray-700 text-xs text-gray-500 dark:text-gray-400">
                                Como aparecerá no WhatsApp (tags substituídas por exemplo).
                            </div>
                        </div>
                    </div>
                </div>

                <div class="rounded-lg bg-gray-50 p-3 dark:bg-gray-900">
                    <p class="mb-2 text-xs font-medium text-gray-600 dark:text-gray-400">Tags de preenchimento (clique para copiar):</p>
                    <div class="flex flex-wrap gap-2 text-xs">
                        @foreach($tags as $tag => $desc)
                            <span class="rounded bg-gray-200 px-2 py-1 dark:bg-gray-700" title="{{ $desc }}">{{ $tag }}</span>
                        @endforeach
                    </div>
                </div>

                {{-- Config WhatsApp API --}}
                <div class="border-t border-gray-200 pt-4 dark:border-gray-700">
                    <h3 class="mb-2 text-sm font-medium text-gray-700 dark:text-gray-300">API WhatsApp (Evolution API ou similar)</h3>
                    <div class="grid gap-3 md:grid-cols-2">
                        <div>
                            <label class="mb-1 block text-xs text-gray-600 dark:text-gray-400">URL da API</label>
                            <input type="url" name="whatsapp_api_url" value="{{ old('whatsapp_api_url', $whatsappApiUrl) }}"
                                placeholder="https://sua-api.com"
                                class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm dark:border-gray-700 dark:bg-gray-900 dark:text-white/90">
                        </div>
                        <div>
                            <label class="mb-1 block text-xs text-gray-600 dark:text-gray-400">Instância</label>
                            <input type="text" name="whatsapp_instance" value="{{ old('whatsapp_instance', $whatsappInstance) }}"
                                placeholder="nome_da_instancia"
                                class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm dark:border-gray-700 dark:bg-gray-900 dark:text-white/90">
                        </div>
                        <div>
                            <label class="mb-1 block text-xs text-gray-600 dark:text-gray-400">API Key (opcional)</label>
                            <input type="text" name="whatsapp_api_key" value="{{ old('whatsapp_api_key', $whatsappApiKey) }}"
                                class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm dark:border-gray-700 dark:bg-gray-900 dark:text-white/90">
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Aba E-mail --}}
        <div class="rounded-lg border border-gray-200 bg-white p-6 shadow-theme-sm dark:border-gray-800 dark:bg-gray-800">
            <h2 class="mb-4 text-lg font-semibold text-gray-800 dark:text-white/90">Protocolo de E-mail</h2>
            <p class="mb-4 text-sm text-gray-500 dark:text-gray-400">Configure o SMTP para envio de e-mails com a OS em anexo (PDF). O envio usa sistema de filas.</p>

            <div class="grid gap-4 md:grid-cols-2">
                <div>
                    <label class="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-300">Protocolo</label>
                    <input type="text" value="smtp" readonly
                        class="w-full rounded-lg border border-gray-200 bg-gray-50 px-4 py-2.5 text-sm text-gray-500 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-400">
                    <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">Protocolo utilizado para envio</p>
                </div>
                <div>
                    <label class="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-300">Endereço do Host</label>
                    <input type="text" name="email_smtp_host" value="{{ old('email_smtp_host', $smtpHost) }}"
                        placeholder="smtp.gmail.com"
                        class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm dark:border-gray-700 dark:bg-gray-900 dark:text-white/90">
                    <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">Informe o endereço do host</p>
                </div>
                <div>
                    <label class="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-300">Porta</label>
                    <input type="number" name="email_smtp_port" value="{{ old('email_smtp_port', $smtpPort) }}"
                        placeholder="587"
                        class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm dark:border-gray-700 dark:bg-gray-900 dark:text-white/90">
                    <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">Informe a porta</p>
                </div>
                <div>
                    <label class="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-300">Tipo de criptografia</label>
                    <select name="email_smtp_encryption" class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm dark:border-gray-700 dark:bg-gray-900 dark:text-white/90">
                        <option value="tls" {{ $smtpEncryption === 'tls' ? 'selected' : '' }}>TLS</option>
                        <option value="ssl" {{ $smtpEncryption === 'ssl' ? 'selected' : '' }}>SSL</option>
                        <option value="" {{ empty($smtpEncryption) ? 'selected' : '' }}>Nenhuma</option>
                    </select>
                    <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">Tipo de criptografia que será utilizada</p>
                </div>
                <div>
                    <label class="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-300">Usuário</label>
                    <input type="text" name="email_smtp_username" value="{{ old('email_smtp_username', $smtpUsername) }}"
                        placeholder="seuemail@gmail.com"
                        class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm dark:border-gray-700 dark:bg-gray-900 dark:text-white/90">
                    <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">Informe o nome de usuário do e-mail</p>
                </div>
                <div class="md:col-span-2">
                    <label class="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-300">Senha</label>
                    <div class="relative max-w-md">
                        <input type="password" name="email_smtp_password" id="email_smtp_password" value="{{ old('email_smtp_password', $smtpPassword) }}"
                            placeholder="Deixe em branco para manter a atual"
                            class="w-full rounded-lg border border-gray-300 py-2.5 pr-10 pl-4 text-sm dark:border-gray-700 dark:bg-gray-900 dark:text-white/90">
                        <button type="button" id="toggle_smtp_password" title="Mostrar/ocultar senha" aria-label="Mostrar senha"
                            class="absolute right-2 top-1/2 -translate-y-1/2 rounded p-1.5 text-gray-500 hover:bg-gray-100 hover:text-gray-700 dark:hover:bg-gray-700 dark:hover:text-gray-300">
                            <svg id="icon_smtp_password_show" class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                            <svg id="icon_smtp_password_hide" class="h-5 w-5 hidden" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878a4.5 4.5 0 106.262 6.262M4.031 11.117A9.953 9.953 0 003 12c0 4.478 2.943 8.268 7 9.543 3.974-1.271 7.26-3.942 8.617-7.424M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a9.952 9.952 0 01-2.207 5.758M21 21l-3.59-3.59"/></svg>
                        </button>
                    </div>
                    <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">Informe a senha do e-mail. Deixe em branco para manter a atual.</p>
                </div>
                <div class="md:col-span-2 flex items-center gap-3">
                    <button type="button" id="btn_testar_smtp" class="rounded-lg border border-gray-300 bg-white px-4 py-2.5 text-sm font-medium text-gray-700 shadow-sm hover:bg-gray-50 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-200 dark:hover:bg-gray-700">
                        Testar SMTP
                    </button>
                    <span id="testar_smtp_result" class="text-sm hidden"></span>
                </div>
            </div>

            <details class="mt-4 rounded-lg border border-gray-200 bg-gray-50 p-3 dark:border-gray-700 dark:bg-gray-900">
                <summary class="cursor-pointer text-sm font-medium text-gray-700 dark:text-gray-300">Dicas de configuração SMTP (erro 535 / autenticação)</summary>
                <ul class="mt-2 list-inside list-disc space-y-1 text-xs text-gray-600 dark:text-gray-400">
                    <li><strong>Usuário:</strong> use o e-mail completo (ex.: contato@seudominio.com.br), salvo se o provedor pedir só a parte antes do @.</li>
                    <li><strong>Senha:</strong> se o provedor usa verificação em duas etapas (2FA), crie uma &quot;senha de app&quot; ou &quot;senha para aplicativos&quot; no painel do e-mail e use essa senha aqui.</li>
                    <li><strong>Porta e criptografia:</strong> 587 com TLS é comum; 465 com SSL também. Em hospedagem compartilhada, use os dados que o painel de e-mail (cPanel, etc.) informar para SMTP.</li>
                    <li><strong>Hospedagem:</strong> confirme se o SMTP está liberado e se não exige envio apenas de um IP específico ou após ativar &quot;acesso SMTP&quot; no painel.</li>
                </ul>
            </details>
        </div>
    </div>

    <div class="mt-6">
        <button type="submit" class="rounded-lg bg-brand-500 px-6 py-2.5 text-sm font-medium text-white hover:bg-brand-600">
            Salvar configurações
        </button>
    </div>
</form>

{{-- Modal preview E-mail (fora do form para não enviar) --}}
<div id="email_preview_modal" class="fixed inset-0 z-50 hidden items-center justify-center p-4 bg-black/50" style="display: none;">
    <div class="w-full max-w-2xl rounded-xl bg-white shadow-xl dark:bg-gray-800 border border-gray-200 dark:border-gray-700 overflow-hidden">
        <div class="flex items-center justify-between border-b border-gray-200 px-4 py-3 dark:border-gray-700">
            <h4 class="text-sm font-semibold text-gray-800 dark:text-white">Pré-visualização do E-mail</h4>
            <button type="button" id="email_preview_close" class="rounded-lg p-1 text-gray-500 hover:bg-gray-100 dark:hover:bg-gray-700">&times;</button>
        </div>
        <div class="max-h-[70vh] overflow-y-auto p-4 space-y-3">
            <div>
                <span class="text-xs font-medium text-gray-500 dark:text-gray-400">Assunto:</span>
                <p id="email_preview_assunto" class="text-sm font-medium text-gray-800 dark:text-white"></p>
            </div>
            <div class="border-t border-gray-200 pt-3 dark:border-gray-700">
                <span class="text-xs font-medium text-gray-500 dark:text-gray-400">Corpo:</span>
                <div id="email_preview_corpo" class="mt-1 text-sm text-gray-700 dark:text-gray-300 prose prose-sm max-w-none dark:prose-invert"></div>
            </div>
        </div>
        <div class="border-t border-gray-200 px-4 py-2 dark:border-gray-700 text-xs text-gray-500 dark:text-gray-400">
            Tags substituídas por valores de exemplo.
        </div>
    </div>
</div>

{{-- Modal Testar SMTP --}}
<div id="testar_smtp_modal" class="fixed inset-0 z-50 hidden items-center justify-center p-4 bg-black/50" style="display: none;">
    <div class="w-full max-w-md rounded-xl bg-white shadow-xl dark:bg-gray-800 border border-gray-200 dark:border-gray-700 overflow-hidden">
        <div class="flex items-center justify-between border-b border-gray-200 px-4 py-3 dark:border-gray-700">
            <h4 class="text-sm font-semibold text-gray-800 dark:text-white">Testar SMTP</h4>
            <button type="button" id="testar_smtp_modal_close" class="rounded-lg p-1 text-gray-500 hover:bg-gray-100 dark:hover:bg-gray-700">&times;</button>
        </div>
        <div class="p-4 space-y-3">
            <p class="text-sm text-gray-600 dark:text-gray-400">Enviar um e-mail de teste para o endereço abaixo usando as configurações SMTP do formulário (ou as salvas, se algum campo estiver vazio).</p>
            <div>
                <label class="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-300">E-mail de destino</label>
                <input type="email" id="test_smtp_email" placeholder="seu@email.com" required
                    class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm dark:border-gray-700 dark:bg-gray-900 dark:text-white/90">
            </div>
            <div id="testar_smtp_modal_error" class="hidden whitespace-pre-line rounded-lg bg-error-100 px-3 py-2 text-sm text-error-700 dark:bg-error-500/20 dark:text-error-400"></div>
        </div>
        <div class="flex justify-end gap-2 border-t border-gray-200 px-4 py-3 dark:border-gray-700">
            <button type="button" id="testar_smtp_modal_cancel" class="rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-200 dark:hover:bg-gray-700">Cancelar</button>
            <button type="button" id="testar_smtp_modal_send" class="rounded-lg bg-brand-500 px-4 py-2 text-sm font-medium text-white hover:bg-brand-600">Enviar teste</button>
        </div>
    </div>
</div>

@push('scripts')
@include('partials.jodit-assets')
<script>
document.addEventListener('alpine:init', () => {
    Alpine.data('whatsappPreview', (replacements) => ({
        template: '',
        open: false,
        replacements: replacements || {},
        init() {
            const el = document.getElementById('notificacao_os_whatsapp_template');
            if (el && el.value) this.template = el.value;
        },
        get previewHtml() {
            let t = this.template || '';
            const rep = this.replacements;
            for (const tag of Object.keys(rep)) t = t.split(tag).join(rep[tag] || tag);
            return t.replace(/\*([^*]+)\*/g, '<strong>$1</strong>').replace(/_([^_]+)_/g, '<em>$1</em>').replace(/~([^~]+)~/g, '<s>$1</s>');
        },
        openPreview() { this.open = true; }
    }));
});
</script>
<script>
(function() {
    const replacements = @json($previewReplacements ?? []);
    function applyReplacements(text) {
        if (!text) return '';
        let out = text;
        for (const [tag, val] of Object.entries(replacements)) out = out.split(tag).join(val);
        return out;
    }
    function getEmailCorpo() {
        const ta = document.getElementById('notificacao_os_email_template_corpo');
        if (!ta) return '';
        if (ta.jodit && typeof ta.jodit.value === 'string') return ta.jodit.value;
        return ta.value || '';
    }
    document.getElementById('btn_preview_email').addEventListener('click', function() {
        const assunto = document.getElementById('email_template_assunto').value || '(sem assunto)';
        const corpo = getEmailCorpo();
        document.getElementById('email_preview_assunto').textContent = applyReplacements(assunto);
        document.getElementById('email_preview_corpo').innerHTML = applyReplacements(corpo);
        document.getElementById('email_preview_modal').style.display = 'flex';
        document.getElementById('email_preview_modal').classList.remove('hidden');
    });
    document.getElementById('email_preview_close').addEventListener('click', function() {
        document.getElementById('email_preview_modal').style.display = 'none';
        document.getElementById('email_preview_modal').classList.add('hidden');
    });
    document.getElementById('email_preview_modal').addEventListener('click', function(e) {
        if (e.target === this) { this.style.display = 'none'; this.classList.add('hidden'); }
    });
})();
</script>
<script>
(function initEmailTemplateEditor() {
    if (typeof Jodit === 'undefined') return;
    const ta = document.getElementById('notificacao_os_email_template_corpo');
    if (!ta || ta.jodit) return;
    const editor = Jodit.make(ta, {
        theme: 'default',
        toolbarAdaptive: true,
        buttons: ['bold', 'italic', 'underline', '|', 'ul', 'ol', '|', 'link', '|', 'hr', 'source'],
        height: 280
    });
    editor.events.on('change', function() { ta.value = editor.value; });
})();
</script>
<script>
    document.getElementById('form_notificacoes_os').addEventListener('submit', function() {
        document.querySelectorAll('.js-jodit').forEach(function(ta) {
            if (ta.jodit && typeof ta.jodit.value === 'string') ta.value = ta.jodit.value;
        });
    });
</script>
<script>
(function() {
    const modal = document.getElementById('testar_smtp_modal');
    const btnOpen = document.getElementById('btn_testar_smtp');
    const btnClose = document.getElementById('testar_smtp_modal_close');
    const btnCancel = document.getElementById('testar_smtp_modal_cancel');
    const btnSend = document.getElementById('testar_smtp_modal_send');
    const inputEmail = document.getElementById('test_smtp_email');
    const elError = document.getElementById('testar_smtp_modal_error');
    const elResult = document.getElementById('testar_smtp_result');

    function showModal() {
        elError.classList.add('hidden');
        elError.textContent = '';
        inputEmail.value = document.querySelector('input[name="email_smtp_username"]')?.value || '';
        modal.style.display = 'flex';
        modal.classList.remove('hidden');
    }
    function hideModal() {
        modal.style.display = 'none';
        modal.classList.add('hidden');
    }

    btnOpen.addEventListener('click', showModal);
    btnClose.addEventListener('click', hideModal);
    btnCancel.addEventListener('click', hideModal);
    modal.addEventListener('click', function(e) { if (e.target === modal) hideModal(); });

    (function toggleSmtpPassword() {
        var btn = document.getElementById('toggle_smtp_password');
        var input = document.getElementById('email_smtp_password');
        var iconShow = document.getElementById('icon_smtp_password_show');
        var iconHide = document.getElementById('icon_smtp_password_hide');
        if (!btn || !input) return;
        btn.addEventListener('click', function() {
            if (input.type === 'password') {
                input.type = 'text';
                iconShow.classList.add('hidden');
                iconHide.classList.remove('hidden');
                btn.setAttribute('aria-label', 'Ocultar senha');
            } else {
                input.type = 'password';
                iconShow.classList.remove('hidden');
                iconHide.classList.add('hidden');
                btn.setAttribute('aria-label', 'Mostrar senha');
            }
        });
    })();

    btnSend.addEventListener('click', function() {
        const email = (inputEmail.value || '').trim();
        if (!email) {
            elError.textContent = 'Informe o e-mail de destino.';
            elError.classList.remove('hidden');
            return;
        }
        elError.classList.add('hidden');
        btnSend.disabled = true;
        btnSend.textContent = 'Enviando...';

        const form = document.getElementById('form_notificacoes_os');
        const fd = new FormData();
        fd.append('_token', form.querySelector('input[name="_token"]').value);
        fd.append('test_email', email);
        fd.append('email_smtp_host', form.querySelector('input[name="email_smtp_host"]').value);
        fd.append('email_smtp_port', form.querySelector('input[name="email_smtp_port"]').value);
        fd.append('email_smtp_encryption', form.querySelector('select[name="email_smtp_encryption"]').value);
        fd.append('email_smtp_username', form.querySelector('input[name="email_smtp_username"]').value);
        fd.append('email_smtp_password', document.getElementById('email_smtp_password').value);

        fetch('{{ route("configuracoes-sistema.notificacoes-os.test-smtp") }}', {
            method: 'POST',
            body: fd,
            headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' }
        })
        .then(function(r) { return r.json().then(function(data) { return { ok: r.ok, status: r.status, data }; }); })
        .then(function(result) {
            if (result.ok && result.data.success) {
                elResult.textContent = result.data.message || 'E-mail de teste enviado.';
                elResult.classList.remove('hidden');
                elResult.classList.remove('text-error-600', 'dark:text-error-400');
                elResult.classList.add('text-success-600', 'dark:text-success-400');
                hideModal();
            } else {
                elError.textContent = result.data.message || 'Erro ao enviar.';
                elError.classList.remove('hidden');
            }
        })
        .catch(function(err) {
            elError.textContent = 'Erro de conexão: ' + (err.message || 'tente novamente.');
            elError.classList.remove('hidden');
        })
        .finally(function() {
            btnSend.disabled = false;
            btnSend.textContent = 'Enviar teste';
        });
    });
})();
</script>
@endpush
@endsection
