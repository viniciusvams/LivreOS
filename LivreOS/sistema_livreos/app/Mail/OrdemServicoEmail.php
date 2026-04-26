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

namespace App\Mail;

use App\Models\OrdemServico;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class OrdemServicoEmail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public OrdemServico $ordem,
        public string $assunto,
        public ?string $mensagem = null,
        public ?string $caminhoPdf = null,
        public bool $mensagemHtml = false
    ) {}

    public function envelope(): Envelope
    {
        $from = erp_mail_from();

        return new Envelope(
            subject: $this->assunto,
            from: new Address($from['address'], $from['name']),
            replyTo: [$from['address']],
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.ordem-servico',
        );
    }

    public function attachments(): array
    {
        $atts = [];
        if ($this->caminhoPdf && file_exists($this->caminhoPdf)) {
            $atts[] = Attachment::fromPath($this->caminhoPdf)
                ->as('OS_' . format_os_display($this->ordem) . '.pdf')
                ->withMime('application/pdf');
        }
        return $atts;
    }
}
