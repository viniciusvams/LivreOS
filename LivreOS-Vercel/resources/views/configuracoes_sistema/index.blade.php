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
    <h1 class="mb-2 text-2xl font-semibold text-gray-800 dark:text-white/90">Configurações do Sistema</h1>
    <p class="text-gray-600 dark:text-gray-400">Gerencie as configurações gerais da aplicação.</p>
</div>

<div class="grid grid-cols-1 gap-4 md:grid-cols-2">
    <a href="{{ route('configuracoes-sistema.empresa') }}" class="rounded-lg border border-gray-200 bg-white p-5 shadow-theme-sm transition hover:border-brand-300 dark:border-gray-800 dark:bg-gray-800">
        <h2 class="mb-1 text-lg font-semibold text-gray-800 dark:text-white/90">Cadastro da empresa</h2>
        <p class="text-sm text-gray-600 dark:text-gray-400">Nome, CNPJ, endereço, logotipo e informações fiscais.</p>
    </a>
    
    <a href="{{ route('configuracoes-sistema.limites-desconto.edit') }}" class="rounded-lg border border-gray-200 bg-white p-5 shadow-theme-sm transition hover:border-brand-300 dark:border-gray-800 dark:bg-gray-800">
        <h2 class="mb-1 text-lg font-semibold text-gray-800 dark:text-white/90">Limites de Desconto</h2>
        <p class="text-sm text-gray-600 dark:text-gray-400">Defina limites em valor e percentual e os perfis (Roles) que devem respeitá-los em itens, total e encerramento da OS.</p>
    </a>

    <a href="{{ route('configuracoes-sistema.produtos-estoque.edit') }}" class="rounded-lg border border-gray-200 bg-white p-5 shadow-theme-sm transition hover:border-brand-300 dark:border-gray-800 dark:bg-gray-800">
        <h2 class="mb-1 text-lg font-semibold text-gray-800 dark:text-white/90">Produtos e Estoque</h2>
        <p class="text-sm text-gray-600 dark:text-gray-400">Bloquear venda de itens com estoque zero. Permissões para vender com estoque zero e cadastrar produto/serviço avulso.</p>
    </a>

    <a href="{{ route('configuracoes-sistema.plano-conta-os') }}" class="rounded-lg border border-gray-200 bg-white p-5 shadow-theme-sm transition hover:border-brand-300 dark:border-gray-800 dark:bg-gray-800">
        <h2 class="mb-1 text-lg font-semibold text-gray-800 dark:text-white/90">Plano de Contas e Centro de Custo - Encerramento de OS</h2>
        <p class="text-sm text-gray-600 dark:text-gray-400">Escolha em qual conta do plano de contas e centro de custo os lançamentos financeiros (contas a receber) serão registrados ao encerrar uma ordem de serviço.</p>
    </a>

    <a href="{{ route('configuracoes-sistema.pedidos-venda.edit') }}" class="rounded-lg border border-gray-200 bg-white p-5 shadow-theme-sm transition hover:border-brand-300 dark:border-gray-800 dark:bg-gray-800">
        <h2 class="mb-1 text-lg font-semibold text-gray-800 dark:text-white/90">Pedidos de Venda</h2>
        <p class="text-sm text-gray-600 dark:text-gray-400">Modo de baixa de estoque (ao confirmar ou ao faturar), geração automática de OS para serviços e plano de contas padrão.</p>
    </a>

    <a href="{{ route('configuracoes-sistema.propostas-comerciais.edit') }}" class="rounded-lg border border-gray-200 bg-white p-5 shadow-theme-sm transition hover:border-brand-300 dark:border-gray-800 dark:bg-gray-800">
        <h2 class="mb-1 text-lg font-semibold text-gray-800 dark:text-white/90">Propostas comerciais</h2>
        <p class="text-sm text-gray-600 dark:text-gray-400">Máscara, dígitos e próximo número da sequência (mesmo conceito dos pedidos de venda). Código único por grupo; versões repetem o número.</p>
    </a>

    @if(auth()->user()->is_admin ?? false)
    <a href="{{ route('configuracoes-sistema.numeracao-os') }}" class="rounded-lg border border-gray-200 bg-white p-5 shadow-theme-sm transition hover:border-brand-300 dark:border-gray-800 dark:bg-gray-800">
        <h2 class="mb-1 text-lg font-semibold text-gray-800 dark:text-white/90">Numeração das Ordens de Serviço</h2>
        <p class="text-sm text-gray-600 dark:text-gray-400">Defina o próximo número da OS (ex.: mudar de 10 para 1000) sem quebrar a sequência do sistema.</p>
    </a>
    @endif

    @if(auth()->user()->is_admin ?? false)
    <a href="{{ route('configuracoes-sistema.tarefas-agendadas') }}" class="rounded-lg border border-gray-200 bg-white p-5 shadow-theme-sm transition hover:border-brand-300 dark:border-gray-800 dark:bg-gray-800">
        <h2 class="mb-1 text-lg font-semibold text-gray-800 dark:text-white/90">Tarefas Agendadas</h2>
        <p class="text-sm text-gray-600 dark:text-gray-400">Execute as tarefas agendadas (recorrentes, reajustes) ao acessar o sistema, sem precisar de cron job.</p>
    </a>
    <a href="{{ route('configuracoes-sistema.utilidades.index') }}" class="rounded-lg border border-gray-200 bg-white p-5 shadow-theme-sm transition hover:border-brand-300 dark:border-gray-800 dark:bg-gray-800">
        <h2 class="mb-1 text-lg font-semibold text-gray-800 dark:text-white/90">Utilidades</h2>
        <p class="text-sm text-gray-600 dark:text-gray-400">Exportar e importar dados por completo, somente banco de dados ou somente anexos.</p>
    </a>
    @endif

    @if((auth()->user()->is_admin ?? false) || (auth()->user()->hasPermission('manage-plugins') ?? false))
    <a href="{{ route('configuracoes-sistema.plugins.index') }}" class="rounded-lg border border-gray-200 bg-white p-5 shadow-theme-sm transition hover:border-brand-300 dark:border-gray-800 dark:bg-gray-800">
        <h2 class="mb-1 text-lg font-semibold text-gray-800 dark:text-white/90">Plugins</h2>
        <p class="text-sm text-gray-600 dark:text-gray-400">Instale e gerencie plugins para estender as funcionalidades do ERP.</p>
    </a>
    @endif
    
    <a href="{{ route('configuracoes-sistema.notificacoes-os.index') }}" class="rounded-lg border border-gray-200 bg-white p-5 shadow-theme-sm transition hover:border-brand-300 dark:border-gray-800 dark:bg-gray-800">
        <h2 class="mb-1 text-lg font-semibold text-gray-800 dark:text-white/90">Notificações de OS</h2>
        <p class="text-sm text-gray-600 dark:text-gray-400">Configure envio de e-mail e WhatsApp (cadastro, mudança de status). SMTP e template WhatsApp.</p>
    </a>
    <a href="{{ route('configuracoes-sistema.fila-notificacoes.index') }}" class="rounded-lg border border-gray-200 bg-white p-5 shadow-theme-sm transition hover:border-brand-300 dark:border-gray-800 dark:bg-gray-800">
        <h2 class="mb-1 text-lg font-semibold text-gray-800 dark:text-white/90">Fila de E-mails e Notificações</h2>
        <p class="text-sm text-gray-600 dark:text-gray-400">Visualize os envios registrados, com preview e exclusão.</p>
    </a>

    <a href="{{ route('configuracoes-sistema.status-os.index') }}" class="rounded-lg border border-gray-200 bg-white p-5 shadow-theme-sm transition hover:border-brand-300 dark:border-gray-800 dark:bg-gray-800">
        <h2 class="mb-1 text-lg font-semibold text-gray-800 dark:text-white/90">Status das Ordens de Serviço</h2>
        <p class="text-sm text-gray-600 dark:text-gray-400">Crie e personalize os status padrão das ordens de serviço (Aberta, Em execução, Concluída, etc).</p>
    </a>

    <a href="{{ route('configuracoes-sistema.termos-garantia.index') }}" class="rounded-lg border border-gray-200 bg-white p-5 shadow-theme-sm transition hover:border-brand-300 dark:border-gray-800 dark:bg-gray-800">
        <h2 class="mb-1 text-lg font-semibold text-gray-800 dark:text-white/90">Termos de Garantia</h2>
        <p class="text-sm text-gray-600 dark:text-gray-400">Cadastre e gerencie termos de garantia para associar às ordens de serviço.</p>
    </a>

    <a href="{{ route('configuracoes-sistema.impressao-os') }}" class="rounded-lg border border-gray-200 bg-white p-5 shadow-theme-sm transition hover:border-brand-300 dark:border-gray-800 dark:bg-gray-800">
        <h2 class="mb-1 text-lg font-semibold text-gray-800 dark:text-white/90">Impressão de OS</h2>
        <p class="text-sm text-gray-600 dark:text-gray-400">Textos globais antes das assinaturas na impressão da OS e no comprovante (OS encerrada), com opção de habilitar ou desabilitar cada um.</p>
    </a>
</div>
@endsection
