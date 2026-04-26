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

namespace App\Jobs;

use App\Models\Configuracao;
use App\Models\NotificacaoOsEnvio;
use App\Models\OrdemServico;
use App\Services\NotificacaoOsService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class EnviarOsWhatsAppJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        public OrdemServico $ordem,
        public ?string $statusAlterado = null,
        public ?int $envioId = null
    ) {}

    public function handle(): void
    {
        $ordem = $this->ordem->load(['cliente', 'produtos', 'servicos']);
        $telefone = NotificacaoOsService::getTelefoneWhatsAppCliente($ordem);
        if (!$telefone) {
            Log::info('EnviarOsWhatsAppJob: cliente sem telefone WhatsApp', ['ordem_id' => $ordem->id]);
            if ($this->envioId) {
                NotificacaoOsEnvio::where('id', $this->envioId)->update([
                    'status' => NotificacaoOsEnvio::STATUS_FALHA,
                    'erro' => 'Cliente sem telefone cadastrado para WhatsApp',
                ]);
            }
            return;
        }

        $template = NotificacaoOsService::getWhatsAppTemplate();
        $mensagem = NotificacaoOsService::aplicarTemplateTags(
            $template,
            $ordem,
            $this->statusAlterado ?? $ordem->status
        );

        $apiUrl = Configuracao::getValue('whatsapp_api_url');
        $apiKey = Configuracao::getValue('whatsapp_api_key');
        $instance = Configuracao::getValue('whatsapp_instance');

        if (empty($apiUrl) || empty($instance)) {
            Log::info('EnviarOsWhatsAppJob: WhatsApp não configurado. Mensagem que seria enviada:', [
                'telefone' => $telefone,
                'mensagem' => $mensagem,
            ]);
            if ($this->envioId) {
                NotificacaoOsEnvio::where('id', $this->envioId)->update([
                    'status' => NotificacaoOsEnvio::STATUS_FALHA,
                    'erro' => 'WhatsApp API não configurada',
                ]);
            }
            return;
        }

        try {
            $url = rtrim($apiUrl, '/') . '/message/sendText/' . $instance;
            $payload = [
                'number' => $telefone,
                'text' => $mensagem,
            ];
            $headers = ['Content-Type' => 'application/json'];
            if ($apiKey) {
                $headers['apikey'] = $apiKey;
            }
            $response = Http::withHeaders($headers)->post($url, $payload);
            if (!$response->successful()) {
                $erro = 'API retornou ' . $response->status() . ': ' . $response->body();
                Log::warning('EnviarOsWhatsAppJob: falha na API', [
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);
                if ($this->envioId) {
                    NotificacaoOsEnvio::where('id', $this->envioId)->update([
                        'status' => NotificacaoOsEnvio::STATUS_FALHA,
                        'erro' => $erro,
                    ]);
                }
                return;
            }
            if ($this->envioId) {
                NotificacaoOsEnvio::where('id', $this->envioId)->update([
                    'status' => NotificacaoOsEnvio::STATUS_ENVIADO,
                    'enviado_em' => now(),
                ]);
            }
        } catch (\Throwable $e) {
            Log::error('EnviarOsWhatsAppJob: erro ao enviar', [
                'ordem_id' => $ordem->id,
                'erro' => $e->getMessage(),
            ]);
            if ($this->envioId) {
                NotificacaoOsEnvio::where('id', $this->envioId)->update([
                    'status' => NotificacaoOsEnvio::STATUS_FALHA,
                    'erro' => $e->getMessage(),
                ]);
            }
        }
    }
}
