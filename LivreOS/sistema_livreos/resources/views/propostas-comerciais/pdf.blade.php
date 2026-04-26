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
<html lang="pt-BR">
<head>
<meta charset="UTF-8">
<meta http-equiv="Content-Type" content="text/html; charset=UTF-8"/>
<title>Proposta comercial — {{ $proposta->referenciaGrupo() }}</title>
<style>
    * { box-sizing: border-box; margin: 0; padding: 0; }
    body { font-family: DejaVu Sans, sans-serif; font-size: 10.5px; color: #1f2937; background: #fff; }
    .page { padding: 18px 22px 24px; }
    .cabecalho-empresa { width: 100%; border-collapse: collapse; margin-bottom: 12px; border-bottom: 2px solid #4f46e5; padding-bottom: 12px; }
    .cabecalho-empresa td { vertical-align: top; padding: 0 8px 0 0; }
    .cabecalho-empresa .col-logo { width: 130px; }
    .cabecalho-empresa .logo-box img { max-width: 120px; max-height: 72px; height: auto; display: block; }
    .emitente-nome { font-size: 16px; font-weight: bold; color: #1e1b4b; margin-bottom: 4px; }
    .emitente-linha { font-size: 9.5px; color: #374151; line-height: 1.45; margin: 1px 0; }
    .col-contato { width: 32%; text-align: right; }
    .contato-linha { font-size: 9.5px; color: #4b5563; margin: 2px 0; }
    table.faixa-doc { width: 100%; margin-bottom: 14px; border-collapse: collapse; }
    .titulo-doc { font-size: 13px; font-weight: bold; color: #4f46e5; text-transform: uppercase; letter-spacing: 0.04em; }
    .num-doc { font-size: 18px; font-weight: bold; color: #4f46e5; text-align: right; }
    .section-title { font-size: 9.5px; font-weight: bold; text-transform: uppercase; color: #6b7280; letter-spacing: .06em; margin: 12px 0 6px; border-bottom: 1px solid #e5e7eb; padding-bottom: 3px; }
    .info-grid { display: table; width: 100%; }
    .info-row { display: table-row; }
    .info-label { display: table-cell; font-weight: bold; color: #4b5563; padding: 2px 10px 2px 0; width: 130px; font-size: 9.5px; }
    .info-value { display: table-cell; color: #111827; padding: 2px 0; font-size: 10px; }
    table.itens { width: 100%; border-collapse: collapse; margin-bottom: 6px; }
    table.itens thead th {
        background: #4f46e5; color: #fff; font-size: 9px; padding: 6px 5px; text-align: left;
        -webkit-print-color-adjust: exact;
        print-color-adjust: exact;
    }
    table.itens thead th.r { text-align: right; }
    table.itens tbody tr { border-bottom: 1px solid #e5e7eb; }
    table.itens tbody td { padding: 5px 5px; font-size: 10px; vertical-align: top; }
    table.itens tbody td.r { text-align: right; }
    table.itens tfoot td { font-size: 10px; padding: 5px 5px; }
    table.itens tfoot td.r { text-align: right; }
    .tag-produto { background: #ede9fe; color: #5b21b6; padding: 1px 5px; border-radius: 6px; font-size: 8.5px; }
    .tag-servico { background: #faf5ff; color: #7c3aed; padding: 1px 5px; border-radius: 6px; font-size: 8.5px; }
    .descricao-geral, .obs { background: #f9fafb; border-left: 3px solid #6366f1; padding: 8px 10px; font-size: 10px; color: #374151; line-height: 1.5; margin-top: 4px; }
    .descricao-geral p, .descricao-geral ul, .descricao-geral ol, .obs p, .obs ul, .obs ol { margin: 0 0 6px; }
    .descricao-geral ul, .descricao-geral ol, .obs ul, .obs ol { padding-left: 18px; }
    .descricao-geral li, .obs li { margin: 2px 0; }
    .descricao-geral a, .obs a { color: #4f46e5; }
    .footer { margin-top: 18px; border-top: 1px solid #e5e7eb; padding-top: 8px; text-align: center; font-size: 8.5px; color: #9ca3af; }
</style>
</head>
<body>
@include('propostas-comerciais.partials._proposta-padrao-corpo')
</body>
</html>
