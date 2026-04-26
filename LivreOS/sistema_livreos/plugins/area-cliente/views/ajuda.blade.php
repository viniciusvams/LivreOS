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
<div class="mx-auto max-w-3xl pb-6">
    <div class="mb-6 rounded-2xl bg-gradient-to-br from-brand-500 to-brand-600 p-5 text-white shadow-lg dark:from-brand-600 dark:to-brand-700 sm:p-6">
        <h1 class="text-xl font-bold sm:text-2xl">Ajuda — Área do Cliente</h1>
        <p class="mt-1 text-sm opacity-90">Passo a passo para usar o portal e acompanhar suas ordens e tickets.</p>
    </div>

    <div class="mb-6 rounded-2xl border-2 border-brand-200 bg-brand-50 p-4 dark:border-brand-700 dark:bg-brand-900/20">
        <h2 class="mb-2 text-base font-semibold text-gray-800 dark:text-white/90">URL para acessar este portal</h2>
        <p class="mb-2 text-sm text-gray-600 dark:text-gray-400">Você pode salvar nos favoritos ou enviar este endereço para acessar a Área do Cliente:</p>
        <p class="break-all rounded-lg bg-white px-3 py-2 font-mono text-sm text-gray-800 dark:bg-gray-800 dark:text-gray-200">{{ url()->route('plugin.area-cliente.index') }}</p>
    </div>

    <div class="space-y-6">
        <section class="rounded-2xl border border-gray-200 bg-white p-5 shadow-sm dark:border-gray-700 dark:bg-gray-800 sm:p-6">
            <h2 class="mb-3 flex items-center gap-2 text-lg font-semibold text-gray-800 dark:text-white/90">
                <span class="flex h-8 w-8 items-center justify-center rounded-lg bg-brand-100 text-brand-600 dark:bg-brand-500/20 dark:text-brand-400">1</span>
                Minhas Ordens
            </h2>
            <p class="mb-2 text-sm text-gray-600 dark:text-gray-400">
                Na tela inicial você vê um resumo das suas ordens de serviço (total, encerradas, em andamento, aguardando). Use os filtros para buscar por código, equipamento ou status.
            </p>
            <ul class="list-inside list-disc space-y-1 text-sm text-gray-600 dark:text-gray-400">
                <li>Clique em uma ordem para ver todos os detalhes (equipamento, serviços, valores, anexos).</li>
                <li>Você pode baixar o comprovante em PDF pela tela da ordem.</li>
                <li>Use <strong>Exportar CSV</strong> para baixar a lista de ordens em planilha.</li>
            </ul>
        </section>

        <section class="rounded-2xl border border-gray-200 bg-white p-5 shadow-sm dark:border-gray-700 dark:bg-gray-800 sm:p-6">
            <h2 class="mb-3 flex items-center gap-2 text-lg font-semibold text-gray-800 dark:text-white/90">
                <span class="flex h-8 w-8 items-center justify-center rounded-lg bg-amber-100 text-amber-600 dark:bg-amber-500/20 dark:text-amber-400">2</span>
                Tickets de suporte
            </h2>
            <p class="mb-2 text-sm text-gray-600 dark:text-gray-400">
                Os tickets servem para você pedir ajuda, reportar problemas ou tirar dúvidas. A equipe responde e você acompanha tudo na mesma conversa.
            </p>
            <ul class="list-inside list-disc space-y-1 text-sm text-gray-600 dark:text-gray-400">
                <li><strong>Abrir ticket:</strong> clique em <strong>Tickets</strong> no menu e depois em <strong>Novo ticket</strong>. Preencha título, descrição, prioridade e anexe arquivos se precisar.</li>
                <li><strong>Ver resposta:</strong> ao receber uma resposta da equipe, você verá na conversa do ticket. Pode responder quantas vezes precisar (enquanto o ticket estiver aberto).</li>
                <li><strong>Confirmar solução:</strong> quando o problema for resolvido, a equipe pode pedir que você confirme. Use o botão de confirmar ou reabrir se ainda houver dúvida.</li>
                <li><strong>Desistir:</strong> em alguns casos você pode encerrar o ticket por desistência (ex.: não precisa mais do suporte).</li>
            </ul>
        </section>

        <section class="rounded-2xl border border-gray-200 bg-white p-5 shadow-sm dark:border-gray-700 dark:bg-gray-800 sm:p-6">
            <h2 class="mb-3 flex items-center gap-2 text-lg font-semibold text-gray-800 dark:text-white/90">
                <span class="flex h-8 w-8 items-center justify-center rounded-lg bg-gray-100 text-gray-600 dark:bg-gray-700 dark:text-gray-300">3</span>
                Meu Perfil
            </h2>
            <p class="mb-2 text-sm text-gray-600 dark:text-gray-400">
                No seu perfil você pode alterar a senha de acesso. Mantenha uma senha segura e não compartilhe seu login.
            </p>
        </section>

        <section class="rounded-2xl border border-gray-200 bg-white p-5 shadow-sm dark:border-gray-700 dark:bg-gray-800 sm:p-6">
            <h2 class="mb-3 text-lg font-semibold text-gray-800 dark:text-white/90">Dúvidas?</h2>
            <p class="text-sm text-gray-600 dark:text-gray-400">
                Se precisar de ajuda para usar o portal ou tiver problemas de acesso, entre em contato com a equipe que atende sua empresa. Você também pode abrir um ticket pelo menu <strong>Tickets</strong> para suporte técnico.
            </p>
        </section>
    </div>
</div>
@endsection
