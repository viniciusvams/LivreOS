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

use App\Mail\OrdemServicoEmail;
use App\Models\Empresa;
use App\Models\NotificacaoOsEnvio;
use App\Models\OrdemServico;
use App\Services\NotificacaoOsService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Barryvdh\DomPDF\Facade\Pdf;

class EnviarOsEmailJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        public OrdemServico $ordem,
        public string $emailDestino,
        public string $assunto,
        public ?string $mensagem = null,
        public bool $anexarPdf = true,
        public ?string $statusAlterado = null,
        public ?int $envioId = null,
        public bool $mensagemHtml = false
    ) {}

    public function handle(): void
    {
        $ordem = $this->ordem->load([
            'cliente',
            'unidade',
            'contato',
            'endereco',
            'equipamento',
            'produtos.produto.composicoes.componente',
            'produtos.variacao',
            'servicos.servico',
            'servicos.tecnico',
            'checklists.checklistModel',
        ]);

        $caminhoPdf = null;
        if ($this->anexarPdf) {
            try {
                $html = view('ordens_servico.print', [
                    'ordem' => $ordem,
                    'empresa' => Empresa::first(),
                ])->render();
                $pdf = Pdf::loadHTML($html);
                $pdf->setPaper('A4');
                $nomeArquivo = 'os_' . preg_replace('/[^a-zA-Z0-9_-]/', '_', format_os_display($ordem)) . '_' . time() . '.pdf';
                $caminhoPdf = storage_path('app/temp/' . $nomeArquivo);
                if (!is_dir(dirname($caminhoPdf))) {
                    mkdir(dirname($caminhoPdf), 0755, true);
                }
                $pdf->save($caminhoPdf);
            } catch (\Throwable $e) {
                Log::warning('EnviarOsEmailJob: falha ao gerar PDF', ['erro' => $e->getMessage()]);
            }
        }

        $assunto = $this->assunto ?: 'Ordem de Serviço ' . format_os_display($ordem);

        try {
            $config = NotificacaoOsService::getMailerConfig();
            if ($config) {
                config([
                    'mail.mailers.notificacao_os' => array_merge(
                        [
                            'transport' => 'smtp',
                            'timeout' => null,
                        ],
                        $config
                    ),
                ]);
                Mail::mailer('notificacao_os')
                    ->to($this->emailDestino)
                    ->send(new OrdemServicoEmail($ordem, $assunto, $this->mensagem, $caminhoPdf, $this->mensagemHtml));
            } else {
                Mail::to($this->emailDestino)
                    ->send(new OrdemServicoEmail($ordem, $assunto, $this->mensagem, $caminhoPdf, $this->mensagemHtml));
            }
        } catch (\Throwable $e) {
            Log::error('EnviarOsEmailJob: falha ao enviar e-mail', [
                'ordem_id' => $ordem->id,
                'email' => $this->emailDestino,
                'erro' => $e->getMessage(),
            ]);
            if ($this->envioId) {
                NotificacaoOsEnvio::where('id', $this->envioId)->update([
                    'status' => NotificacaoOsEnvio::STATUS_FALHA,
                    'erro' => $e->getMessage(),
                ]);
            }
            if ($caminhoPdf && file_exists($caminhoPdf)) {
                @unlink($caminhoPdf);
            }
            return;
        }
        if ($this->envioId) {
            NotificacaoOsEnvio::where('id', $this->envioId)->update([
                'status' => NotificacaoOsEnvio::STATUS_ENVIADO,
                'enviado_em' => now(),
            ]);
        }
        if ($caminhoPdf && file_exists($caminhoPdf)) {
            @unlink($caminhoPdf);
        }
    }
}
