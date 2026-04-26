<?php

/**
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
 */

namespace App\Http\Controllers\ConfiguracoesSistema;

use App\Http\Controllers\Controller;
use App\Models\Configuracao;
use App\Models\Empresa;
use App\Models\StatusOs;
use App\Services\NotificacaoOsService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Config;

class NotificacoesOsController extends Controller
{
    public function index()
    {
        $tipo = NotificacaoOsService::getTipoNotificacao();
        $emailAutomatico = NotificacaoOsService::getEmailAutomaticoAtivo();
        $whatsappAtivo = NotificacaoOsService::getWhatsAppAtivo();
        $whatsappTemplate = NotificacaoOsService::getWhatsAppTemplate();
        $emailTemplateAssunto = NotificacaoOsService::getEmailTemplateAssunto();
        $emailTemplateCorpo = NotificacaoOsService::getEmailTemplateCorpo();
        $tags = NotificacaoOsService::getTagsDisponiveis();

        $smtpHost = Configuracao::getValue('email_smtp_host');
        $smtpPort = Configuracao::getValue('email_smtp_port', '587');
        $smtpEncryption = Configuracao::getValue('email_smtp_encryption', 'tls');
        $smtpUsername = Configuracao::getValue('email_smtp_username');
        $smtpPassword = Configuracao::getValue('email_smtp_password');
        if ($smtpPassword) {
            try {
                $smtpPassword = Crypt::decryptString($smtpPassword);
            } catch (\Throwable) {
                $smtpPassword = '';
            }
        }

        $whatsappApiUrl = Configuracao::getValue('whatsapp_api_url');
        $whatsappApiKey = Configuracao::getValue('whatsapp_api_key');
        $whatsappInstance = Configuracao::getValue('whatsapp_instance');
        $whatsappModo = Configuracao::getValue('whatsapp_modo', 'manual');
        $statusParaLinkAssinatura = NotificacaoOsService::getStatusParaLinkAssinatura();
        $statusOsList = StatusOs::ativos()->ordenados()->pluck('nome', 'nome')->toArray();
        if ($statusParaLinkAssinatura !== '' && !isset($statusOsList[$statusParaLinkAssinatura])) {
            $statusOsList = [$statusParaLinkAssinatura => $statusParaLinkAssinatura] + $statusOsList;
        }

        return erp_view('configuracoes_sistema.notificacoes_os', [
            'title' => 'Notificações de OS',
            'tipo' => $tipo,
            'emailAutomatico' => $emailAutomatico,
            'whatsappAtivo' => $whatsappAtivo,
            'whatsappTemplate' => $whatsappTemplate,
            'emailTemplateAssunto' => $emailTemplateAssunto,
            'emailTemplateCorpo' => $emailTemplateCorpo,
            'tags' => $tags,
            'previewReplacements' => NotificacaoOsService::getPreviewReplacements(),
            'smtpHost' => $smtpHost,
            'smtpPort' => $smtpPort,
            'smtpEncryption' => $smtpEncryption,
            'smtpUsername' => $smtpUsername,
            'smtpPassword' => $smtpPassword,
            'whatsappApiUrl' => $whatsappApiUrl,
            'whatsappApiKey' => $whatsappApiKey,
            'whatsappInstance' => $whatsappInstance,
            'whatsappModo' => $whatsappModo,
            'statusParaLinkAssinatura' => $statusParaLinkAssinatura,
            'statusOsList' => $statusOsList,
            'empresa' => Empresa::first(),
        ]);
    }

    public function update(Request $request)
    {
        $request->validate([
            'notificacao_os_tipo' => 'required|in:nao_notificar,email_manual,email_automatico',
            'notificacao_os_email_automatico' => 'boolean',
            'notificacao_os_whatsapp_ativo' => 'boolean',
            'notificacao_os_whatsapp_template' => 'nullable|string|max:2000',
            'notificacao_os_email_template_assunto' => 'nullable|string|max:255',
            'notificacao_os_email_template_corpo' => 'nullable|string|max:5000',
            'notificacao_os_status_link_assinatura' => 'nullable|string|max:100',
            'email_smtp_host' => 'nullable|string|max:255',
            'email_smtp_port' => 'nullable|integer|min:1|max:65535',
            'email_smtp_encryption' => 'nullable|string|in:tls,ssl,',
            'email_smtp_username' => 'nullable|string|max:255',
            'email_smtp_password' => 'nullable|string|max:255',
            'whatsapp_api_url' => 'nullable|string|max:500',
            'whatsapp_api_key' => 'nullable|string|max:255',
            'whatsapp_instance' => 'nullable|string|max:100',
        ]);

        Configuracao::setValue('notificacao_os_tipo', $request->notificacao_os_tipo);
        Configuracao::setValue('notificacao_os_email_automatico', $request->boolean('notificacao_os_email_automatico') ? '1' : '0');
        Configuracao::setValue('notificacao_os_whatsapp_ativo', $request->boolean('notificacao_os_whatsapp_ativo') ? '1' : '0');
        Configuracao::setValue('notificacao_os_whatsapp_template', $request->notificacao_os_whatsapp_template ?? NotificacaoOsService::getTemplatePadrao());
        Configuracao::setValue('notificacao_os_email_template_assunto', $request->notificacao_os_email_template_assunto ?? NotificacaoOsService::getEmailTemplateAssuntoPadrao());
        Configuracao::setValue('notificacao_os_email_template_corpo', $request->notificacao_os_email_template_corpo ?? NotificacaoOsService::getEmailTemplateCorpoPadrao());
        Configuracao::setValue('notificacao_os_status_link_assinatura', $request->input('notificacao_os_status_link_assinatura', NotificacaoOsService::getStatusParaLinkAssinatura()) ?? '');
        Configuracao::setValue('whatsapp_modo', $request->input('whatsapp_modo', 'manual'));

        if ($request->filled('email_smtp_host')) {
            Configuracao::setValue('email_smtp_host', $request->email_smtp_host);
            Configuracao::setValue('email_smtp_port', $request->email_smtp_port ?? '587');
            Configuracao::setValue('email_smtp_encryption', $request->email_smtp_encryption ?? 'tls');
            Configuracao::setValue('email_smtp_username', $request->email_smtp_username ?? '');
            if ($request->filled('email_smtp_password')) {
                Configuracao::setValue('email_smtp_password', Crypt::encryptString($request->email_smtp_password));
            }
        } else {
            Configuracao::setValue('email_smtp_host', '');
            Configuracao::setValue('email_smtp_port', '');
            Configuracao::setValue('email_smtp_username', '');
            Configuracao::setValue('email_smtp_password', '');
        }

        if ($request->filled('whatsapp_api_url')) {
            Configuracao::setValue('whatsapp_api_url', $request->whatsapp_api_url);
            Configuracao::setValue('whatsapp_api_key', $request->whatsapp_api_key ?? '');
            Configuracao::setValue('whatsapp_instance', $request->whatsapp_instance ?? '');
        } else {
            Configuracao::setValue('whatsapp_api_url', '');
            Configuracao::setValue('whatsapp_api_key', '');
            Configuracao::setValue('whatsapp_instance', '');
        }

        return redirect()->route('configuracoes-sistema.notificacoes-os.index')
            ->with('success', 'Configurações de notificações salvas com sucesso!');
    }

    /**
     * Envia um e-mail de teste usando os dados SMTP informados (ou os salvos, se algum campo vier vazio).
     */
    public function testSmtp(Request $request)
    {
        $request->validate([
            'test_email' => 'required|email',
            'email_smtp_host' => 'nullable|string|max:255',
            'email_smtp_port' => 'nullable|integer|min:1|max:65535',
            'email_smtp_encryption' => 'nullable|string|in:tls,ssl,',
            'email_smtp_username' => 'nullable|string|max:255',
            'email_smtp_password' => 'nullable|string|max:500',
        ]);

        $host = $request->filled('email_smtp_host') ? $request->email_smtp_host : Configuracao::getValue('email_smtp_host');
        $port = $request->filled('email_smtp_port') ? (string) $request->email_smtp_port : Configuracao::getValue('email_smtp_port', '587');
        $encryption = $request->filled('email_smtp_encryption') ? $request->email_smtp_encryption : Configuracao::getValue('email_smtp_encryption', 'tls');
        $username = $request->filled('email_smtp_username') ? $request->email_smtp_username : Configuracao::getValue('email_smtp_username');
        $password = $request->filled('email_smtp_password')
            ? $request->email_smtp_password
            : (function () {
                $stored = Configuracao::getValue('email_smtp_password');
                if (!$stored) {
                    return '';
                }
                try {
                    return Crypt::decryptString($stored);
                } catch (\Throwable) {
                    return '';
                }
            })();

        if (empty($host) || empty($username)) {
            return response()->json([
                'success' => false,
                'message' => 'Preencha Host e Usuário do SMTP (ou salve as configurações antes de testar).',
            ], 422);
        }

        if ($password === '') {
            return response()->json([
                'success' => false,
                'message' => 'A senha do SMTP está vazia. Digite a senha no campo e clique em Testar novamente (ou salve as configurações antes). Se você alterou a chave APP_KEY do Laravel, salve a senha do e-mail de novo.',
            ], 422);
        }

        $from = erp_mail_from();
        $fromAddress = $from['address'];
        $fromName = $from['name'];

        Config::set('mail.mailers.erp_test_smtp', [
            'transport' => 'smtp',
            'host' => $host,
            'port' => (int) $port,
            'encryption' => $encryption ?: null,
            'username' => $username,
            'password' => $password,
            'timeout' => null,
        ]);

        try {
            Mail::mailer('erp_test_smtp')
                ->raw(
                    'Este é um e-mail de teste do ' . config('app.name') . ".\n\nSe você recebeu esta mensagem, o SMTP está configurado corretamente.",
                    fn ($m) => $m->to($request->test_email)
                        ->from($fromAddress, $fromName)
                        ->subject('Teste SMTP - ' . config('app.name'))
                );
        } catch (\Throwable $e) {
            $msg = 'Falha no envio: ' . $e->getMessage();
            if (stripos($e->getMessage(), '535') !== false || stripos($e->getMessage(), 'authentication') !== false || stripos($e->getMessage(), 'Incorrect authentication') !== false) {
                $msg .= "\n\nDica: Confira usuário e senha. Se o provedor do e-mail usa verificação em duas etapas (2FA), use uma \"senha de app\" ou \"senha para aplicativos\" em vez da senha normal. Teste também porta 587 com TLS ou 465 com SSL, conforme indicado pelo seu provedor/hospedagem.";
            }
            return response()->json([
                'success' => false,
                'message' => $msg,
            ], 400);
        }

        return response()->json(['success' => true, 'message' => 'E-mail de teste enviado com sucesso. Verifique a caixa de entrada (e o spam).']);
    }
}
