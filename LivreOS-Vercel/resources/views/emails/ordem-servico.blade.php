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

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ordem de Serviço</title>
    <style>
        body { font-family: Arial, Helvetica, sans-serif; color: #333; line-height: 1.5; margin: 0; padding: 20px; }
        .container { max-width: 600px; margin: 0 auto; }
        .header { border-bottom: 2px solid #e5e7eb; padding-bottom: 16px; margin-bottom: 20px; }
        .footer { margin-top: 24px; padding-top: 16px; border-top: 1px solid #e5e7eb; font-size: 12px; color: #6b7280; }
        p { margin: 0 0 12px 0; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1 style="margin: 0 0 8px 0; font-size: 20px;">Ordem de Serviço {{ format_os_display($ordem) }}</h1>
            <p style="margin: 0; color: #6b7280;">Cliente: {{ $ordem->cliente?->nome ?? '-' }}</p>
        </div>

        @if($mensagem)
            <div style="margin-bottom: 20px;">
                @if($mensagemHtml ?? false)
                    {!! $mensagem !!}
                @else
                    {!! nl2br(e($mensagem)) !!}
                @endif
            </div>
        @else
            <p>Segue em anexo a Ordem de Serviço {{ format_os_display($ordem) }}.</p>
        @endif

        <p><strong>Status:</strong> {{ $ordem->status ?? '-' }}</p>
        @if($ordem->total_geral)
            <p><strong>Valor total:</strong> R$ {{ number_format($ordem->total_geral, 2, ',', '.') }}</p>
        @endif

        <div class="footer">
            <p>Este e-mail foi enviado automaticamente pelo {{ config('app.name') }}.</p>
        </div>
    </div>
</body>
</html>
