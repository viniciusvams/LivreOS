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

namespace App\Services;

use App\Models\Configuracao;
use App\Models\Empresa;
use App\Models\OrdemServicoAssinaturaToken;
use Illuminate\Support\Facades\Crypt;
use App\Models\OrdemServico;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

class NotificacaoOsService
{
    public const TIPO_NAO_NOTIFICAR = 'nao_notificar';
    public const TIPO_EMAIL_MANUAL = 'email_manual';
    public const TIPO_EMAIL_AUTOMATICO = 'email_automatico';

    public static function getTipoNotificacao(): string
    {
        return Configuracao::getValue('notificacao_os_tipo', self::TIPO_NAO_NOTIFICAR);
    }

    public static function getEmailAutomaticoAtivo(): bool
    {
        return Configuracao::getValue('notificacao_os_email_automatico', '0') === '1';
    }

    public static function getWhatsAppAtivo(): bool
    {
        return Configuracao::getValue('notificacao_os_whatsapp_ativo', '0') === '1';
    }

    public static function getWhatsAppTemplate(): string
    {
        return Configuracao::getValue('notificacao_os_whatsapp_template', self::getTemplatePadrao());
    }

    public static function getTemplatePadrao(): string
    {
        return "Prezado(a), {CLIENTE_NOME} a OS de nº {NUMERO_OS} teve o status alterado para: {STATUS_OS} segue a descrição {DESCRI_PRODUTOS} com valor total de {VALOR_OS}! Para mais informações entre em contato conosco. Atenciosamente, {EMITENTE} {TELEFONE_EMITENTE}.";
    }

    public static function getEmailTemplateAssunto(): string
    {
        return Configuracao::getValue('notificacao_os_email_template_assunto', self::getEmailTemplateAssuntoPadrao());
    }

    public static function getEmailTemplateAssuntoPadrao(): string
    {
        return 'Ordem de Serviço {NUMERO_OS} - Status: {STATUS_OS}';
    }

    public static function getEmailTemplateCorpo(): string
    {
        return Configuracao::getValue('notificacao_os_email_template_corpo', self::getEmailTemplateCorpoPadrao());
    }

    public static function getEmailTemplateCorpoPadrao(): string
    {
        return "<p>Prezado(a) <strong>{CLIENTE_NOME}</strong>,</p>\n\n<p>Informamos que a Ordem de Serviço <strong>{NUMERO_OS}</strong> teve o status alterado para: <strong>{STATUS_OS}</strong>.</p>\n\n<p>Descrição: {DESCRI_PRODUTOS}</p>\n<p>Valor total: {VALOR_OS}</p>\n\n<p>Segue em anexo o documento da OS.</p>\n\n<p>Atenciosamente,<br>{EMITENTE}<br>{TELEFONE_EMITENTE}</p>";
    }

    public static function getTagsDisponiveis(): array
    {
        return [
            '{CLIENTE_NOME}' => 'Nome do cliente',
            '{NUMERO_OS}' => 'Número/código da OS',
            '{STATUS_OS}' => 'Status da OS',
            '{DESCRI_PRODUTOS}' => 'Descrição dos produtos/serviços',
            '{VALOR_OS}' => 'Valor total da OS',
            '{EMITENTE}' => 'Nome da empresa',
            '{TELEFONE_EMITENTE}' => 'Telefone da empresa',
            '{LINK_ASSINATURA_APROVACAO}' => 'Link para assinatura de aprovação do cliente (quando status = configurado)',
            '{SE_LINK_ASSINATURA}' => 'Início do bloco condicional: só incluir quando status = configurado para link',
            '{FIM_LINK_ASSINATURA}' => 'Fim do bloco condicional (par de {SE_LINK_ASSINATURA})',
        ];
    }

    /** Status em que o link de assinatura do cliente é incluído no e-mail/WhatsApp. Vazio = nunca. */
    public static function getStatusParaLinkAssinatura(): string
    {
        return Configuracao::getValue('notificacao_os_status_link_assinatura', 'Aguardando aprovação');
    }

    /** Retorna valores de exemplo para preview dos templates (sem OS real). */
    public static function getPreviewReplacements(): array
    {
        $empresa = Empresa::first();
        $statusLink = self::getStatusParaLinkAssinatura();
        $linkExemplo = $statusLink
            ? '(Link para assinatura de aprovação – exibido quando o status for: ' . $statusLink . ')'
            : '(Link desativado – configure o status em "Incluir link de assinatura")';
        return [
            '{CLIENTE_NOME}' => 'João da Silva',
            '{NUMERO_OS}' => 'OS-2026-001',
            '{STATUS_OS}' => 'Em andamento',
            '{DESCRI_PRODUTOS}' => 'Manutenção preventiva, Troca de peças x 1',
            '{VALOR_OS}' => 'R$ 1.250,00',
            '{EMITENTE}' => $empresa?->nome ?? config('app.name'),
            '{TELEFONE_EMITENTE}' => $empresa?->telefone ?? $empresa?->celular ?? '(11) 99999-9999',
            '{LINK_ASSINATURA_APROVACAO}' => $linkExemplo,
            '{SE_LINK_ASSINATURA}' => '',
            '{FIM_LINK_ASSINATURA}' => '',
        ];
    }

    /**
     * Obtém ou gera o link de assinatura do cliente para a OS (mesma lógica do "Gerar novo link" na OS).
     * Retorna URL completa ou string vazia se OS encerrada ou não permitido.
     */
    public static function getOrCreateLinkAssinaturaCliente(OrdemServico $ordem): string
    {
        if (in_array($ordem->status, ['Encerrado', 'Encerrada'], true)) {
            return '';
        }
        $tokenModel = OrdemServicoAssinaturaToken::where('ordem_servico_id', $ordem->id)
            ->where('tipo', 'cliente')
            ->whereIn('status', ['sent', 'validated'])
            ->where(function ($q) {
                $q->whereNull('expires_at')->orWhere('expires_at', '>', now());
            })
            ->first();
        if ($tokenModel) {
            return url()->route('assinaturas.cliente', ['token' => $tokenModel->token]);
        }
        OrdemServicoAssinaturaToken::where('ordem_servico_id', $ordem->id)
            ->where('tipo', 'cliente')
            ->update(['status' => 'revoked']);
        $token = Str::random(48);
        OrdemServicoAssinaturaToken::create([
            'ordem_servico_id' => $ordem->id,
            'tipo' => 'cliente',
            'token' => $token,
            'status' => 'sent',
            'expires_at' => now()->addDays(7),
        ]);
        return url()->route('assinaturas.cliente', ['token' => $token]);
    }

    public static function aplicarTemplateTags(string $template, OrdemServico $ordem, ?string $statusAlterado = null): string
    {
        $cliente = $ordem->cliente;
        $empresa = Empresa::first();

        $fmtQtd = static function ($q): string {
            if ($q === null || $q === '') {
                return '1';
            }
            $s = format_quantidade_br($q);

            return $s === '—' ? '1' : $s;
        };

        $descriProdutos = [];
        foreach ($ordem->produtos as $p) {
            $descriProdutos[] = ($p->descricao ?? $p->produto?->nome ?? 'Produto').' x '.$fmtQtd($p->quantidade ?? 1);
        }
        foreach ($ordem->servicos as $s) {
            $descriProdutos[] = ($s->descricao ?? $s->servico?->nome ?? 'Serviço').' x '.$fmtQtd($s->quantidade ?? 1);
        }
        $descriProdutosStr = implode(', ', $descriProdutos);
        if ($descriProdutosStr === '' || $descriProdutosStr === 'Sem itens') {
            foreach ([
                trim((string) ($ordem->servicos_realizados ?? '')),
                trim((string) ($ordem->relato_cliente ?? '')),
                trim((string) ($ordem->observacoes_cliente ?? '')),
            ] as $fallback) {
                if ($fallback !== '') {
                    $descriProdutosStr = $fallback;
                    break;
                }
            }
        }
        if ($descriProdutosStr === '') {
            $descriProdutosStr = 'Sem itens';
        }

        $totalGeral = (float) ($ordem->total_geral ?? 0);
        $somaItens = 0.0;
        foreach ($ordem->produtos as $p) {
            $somaItens += (float) ($p->total ?? 0);
        }
        foreach ($ordem->servicos as $s) {
            $somaItens += (float) ($s->total ?? 0);
        }
        if ($totalGeral <= 0 && $somaItens > 0) {
            $totalGeral = $somaItens;
        }

        $statusDisplay = $statusAlterado ?? $ordem->status ?? '-';
        if (function_exists('format_status_os_display')) {
            $statusDisplay = format_status_os_display($statusDisplay);
        }

        $replacements = [
            '{CLIENTE_NOME}' => $cliente?->nome ?? 'Cliente',
            '{NUMERO_OS}' => format_os_display($ordem),
            '{STATUS_OS}' => $statusDisplay,
            '{DESCRI_PRODUTOS}' => $descriProdutosStr,
            '{VALOR_OS}' => 'R$ ' . number_format($totalGeral, 2, ',', '.'),
            '{EMITENTE}' => $empresa?->nome ?? config('app.name'),
            '{TELEFONE_EMITENTE}' => $empresa?->telefone ?? $empresa?->celular ?? '-',
        ];
        $statusParaLink = self::getStatusParaLinkAssinatura();
        $statusAtual = $statusAlterado ?? $ordem->status ?? null;
        $incluirBlocoLink = $statusParaLink !== '' && $statusAtual === $statusParaLink;

        if ($incluirBlocoLink) {
            $replacements['{SE_LINK_ASSINATURA}'] = '';
            $replacements['{FIM_LINK_ASSINATURA}'] = '';
            $replacements['{LINK_ASSINATURA_APROVACAO}'] = self::getOrCreateLinkAssinaturaCliente($ordem);
        } else {
            $replacements['{SE_LINK_ASSINATURA}'] = '';
            $replacements['{FIM_LINK_ASSINATURA}'] = '';
            $replacements['{LINK_ASSINATURA_APROVACAO}'] = '';
            $template = preg_replace('/\{SE_LINK_ASSINATURA\}.*?\{FIM_LINK_ASSINATURA\}/s', '', $template);
        }

        return str_replace(array_keys($replacements), array_values($replacements), $template);
    }

    public static function deveEnviarEmailNoCadastro(): bool
    {
        $tipo = self::getTipoNotificacao();
        if ($tipo === self::TIPO_NAO_NOTIFICAR) {
            return false;
        }
        if ($tipo === self::TIPO_EMAIL_AUTOMATICO) {
            return self::getEmailAutomaticoAtivo();
        }
        return false; // email_manual = depende da opção no cadastro da OS
    }

    public static function deveEnviarEmailNaMudancaStatus(): bool
    {
        return self::getTipoNotificacao() === self::TIPO_EMAIL_AUTOMATICO && self::getEmailAutomaticoAtivo();
    }

    public static function deveEnviarWhatsAppNaMudancaStatus(): bool
    {
        return self::getWhatsAppAtivo() && self::getWhatsAppModo() === 'api';
    }

    public static function getWhatsAppModo(): string
    {
        return Configuracao::getValue('whatsapp_modo', 'manual'); // api ou manual
    }

    public static function getMailerConfig(): ?array
    {
        $host = Configuracao::getValue('email_smtp_host');
        if (empty($host)) {
            return null;
        }
        $password = Configuracao::getValue('email_smtp_password');
        if ($password) {
            try {
                $password = Crypt::decryptString($password);
            } catch (\Throwable) {
                $password = '';
            }
        }
        return [
            'transport' => 'smtp',
            'host' => $host,
            'port' => (int) Configuracao::getValue('email_smtp_port', 587),
            'encryption' => Configuracao::getValue('email_smtp_encryption') ?: 'tls',
            'username' => Configuracao::getValue('email_smtp_username'),
            'password' => $password,
        ];
    }

    public static function configurarMailerSmtp(): void
    {
        $config = self::getMailerConfig();
        if ($config) {
            config([
                'mail.mailers.notificacao_os' => array_merge(
                    config('mail.mailers.smtp', []),
                    $config
                ),
            ]);
        }
    }

    public static function getEmailsCliente(OrdemServico $ordem): array
    {
        $emails = [];
        $contato = $ordem->contato;
        if ($contato && $contato->email) {
            $emails[] = $contato->email;
        }
        $cliente = $ordem->cliente;
        if ($cliente) {
            foreach ($cliente->contatos as $c) {
                if ($c->email && !in_array($c->email, $emails)) {
                    $emails[] = $c->email;
                }
            }
        }
        return array_filter(array_unique($emails));
    }

    public static function getTelefoneWhatsAppCliente(OrdemServico $ordem): ?string
    {
        $contato = $ordem->contato;
        if ($contato) {
            $tel = $contato->telefone_whatsapp ? $contato->telefone : ($contato->telefone2 ?? $contato->telefone);
            if ($tel) {
                return self::formatarTelefoneWhatsApp($tel);
            }
        }
        $cliente = $ordem->cliente;
        if ($cliente) {
            foreach ($cliente->contatos as $c) {
                if ($c->telefone_whatsapp && $c->telefone) {
                    return self::formatarTelefoneWhatsApp($c->telefone);
                }
                if ($c->telefone) {
                    return self::formatarTelefoneWhatsApp($c->telefone);
                }
            }
        }
        return null;
    }

    public static function formatarTelefoneWhatsApp(string $telefone): string
    {
        $tel = preg_replace('/\D/', '', $telefone);
        if (strlen($tel) === 11 && str_starts_with($tel, '0')) {
            $tel = '55' . $tel;
        } elseif (strlen($tel) === 10 || strlen($tel) === 11) {
            $tel = '55' . $tel;
        }
        return $tel;
    }
}
