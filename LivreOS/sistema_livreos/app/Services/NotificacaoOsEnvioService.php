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

use App\Jobs\EnviarOsEmailJob;
use App\Jobs\EnviarOsWhatsAppJob;
use App\Models\NotificacaoOsEnvio;
use App\Models\OrdemServico;
use Illuminate\Validation\ValidationException;

class NotificacaoOsEnvioService
{
    /**
     * Verifica se o domínio do e-mail possui registros MX (configurado para receber e-mails).
     * Retorna mensagem de erro ou null se válido.
     */
    public static function validarDominioEmail(string $email): ?string
    {
        $email = trim($email);
        if ($email === '') {
            return 'Informe o e-mail.';
        }
        $at = strrpos($email, '@');
        if ($at === false || $at === strlen($email) - 1) {
            return 'E-mail inválido.';
        }
        $domain = substr($email, $at + 1);
        if ($domain === '' || strpos($domain, '.') === false) {
            return 'Domínio do e-mail inválido.';
        }
        if (!function_exists('checkdnsrr')) {
            return null;
        }
        if (checkdnsrr($domain, 'MX') === true) {
            return null;
        }
        return 'O domínio do e-mail informado não possui registros MX (não está configurado para receber e-mails). Verifique o endereço ou tente outro e-mail.';
    }

    public static function registrarEDispararEmail(
        OrdemServico $ordem,
        string $email,
        string $assunto,
        ?string $mensagem = null,
        bool $anexarPdf = true,
        ?string $statusAlterado = null
    ): NotificacaoOsEnvio {
        $erro = self::validarDominioEmail($email);
        if ($erro !== null) {
            throw ValidationException::withMessages(['email' => $erro]);
        }

        $status = $statusAlterado ?? $ordem->status;

        $mensagemHtml = false;
        if ($mensagem === null || $mensagem === '') {
            $mensagemHtml = true;
            $assunto = NotificacaoOsService::aplicarTemplateTags(
                NotificacaoOsService::getEmailTemplateAssunto(),
                $ordem,
                $status
            );
            $mensagem = NotificacaoOsService::aplicarTemplateTags(
                NotificacaoOsService::getEmailTemplateCorpo(),
                $ordem,
                $status
            );
        } else {
            // Se a mensagem contém tags HTML (ex.: template do Jodit), exibir como HTML no e-mail
            $mensagemHtml = strip_tags($mensagem) !== $mensagem;
        }

        $envio = NotificacaoOsEnvio::create([
            'tipo' => NotificacaoOsEnvio::TIPO_EMAIL,
            'ordem_servico_id' => $ordem->id,
            'destinatario' => $email,
            'assunto' => $assunto,
            'conteudo' => $mensagem,
            'status' => NotificacaoOsEnvio::STATUS_PENDING,
        ]);
        EnviarOsEmailJob::dispatch($ordem, $email, $assunto, $mensagem, $anexarPdf, $statusAlterado, $envio->id, $mensagemHtml);
        return $envio;
    }

    public static function registrarEDispararWhatsApp(
        OrdemServico $ordem,
        ?string $statusAlterado = null
    ): ?NotificacaoOsEnvio {
        $telefone = NotificacaoOsService::getTelefoneWhatsAppCliente($ordem);
        if (!$telefone) {
            return null;
        }
        $template = NotificacaoOsService::getWhatsAppTemplate();
        $conteudo = NotificacaoOsService::aplicarTemplateTags($template, $ordem, $statusAlterado ?? $ordem->status);

        $envio = NotificacaoOsEnvio::create([
            'tipo' => NotificacaoOsEnvio::TIPO_WHATSAPP,
            'ordem_servico_id' => $ordem->id,
            'destinatario' => $telefone,
            'assunto' => null,
            'conteudo' => $conteudo,
            'status' => NotificacaoOsEnvio::STATUS_PENDING,
        ]);
        EnviarOsWhatsAppJob::dispatch($ordem, $statusAlterado, $envio->id);
        return $envio;
    }

    public static function registrarWhatsAppManual(OrdemServico $ordem, ?string $statusAlterado = null): ?NotificacaoOsEnvio
    {
        $telefone = NotificacaoOsService::getTelefoneWhatsAppCliente($ordem);
        if (!$telefone) {
            return null;
        }
        $template = NotificacaoOsService::getWhatsAppTemplate();
        $conteudo = NotificacaoOsService::aplicarTemplateTags($template, $ordem, $statusAlterado ?? $ordem->status);

        return NotificacaoOsEnvio::create([
            'tipo' => NotificacaoOsEnvio::TIPO_WHATSAPP,
            'ordem_servico_id' => $ordem->id,
            'destinatario' => $telefone,
            'assunto' => null,
            'conteudo' => $conteudo,
            'status' => NotificacaoOsEnvio::STATUS_ENVIADO, // manual = já "enviado" pelo usuário
            'enviado_em' => now(),
        ]);
    }
}
