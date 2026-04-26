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

@php
    $isEdit = isset($ordem) && $ordem->exists;
    $ordem = $isEdit ? $ordem : new \App\Models\OrdemServico();
    $osEncerrada = in_array($ordem->status ?? '', ['Encerrado', 'Encerrada'], true);
    $podeEditarOsEncerrada = (bool) ($podeEditarOsEncerrada ?? false);
    $osSomenteLeitura = $osEncerrada && ! $podeEditarOsEncerrada;
    $equipamentoAtual = $ordem->equipamento;
    $equipamentoNomeDefault = $ordem->equipamento_nome;
    if (!$equipamentoNomeDefault && $equipamentoAtual) {
        $equipamentoNomeDefault = trim(($equipamentoAtual->marca ?? '') . ' ' . ($equipamentoAtual->modelo ?? '') . ' ' . ($equipamentoAtual->numero_serie ?? ''))
            ?: ('Equipamento #' . $equipamentoAtual->id);
    }
    $produtosItens = old('produtos', $ordem->produtos->toArray() ?: []);
    $servicosItens = old('servicos', $ordem->servicos->toArray() ?: []);
    $tecnicosItens = old('tecnicos', $ordem->tecnicos->toArray() ?: []);
    $apontamentos = old('apontamentos', $ordem->apontamentos->toArray() ?: []);

    $tipos = ['manutencao' => 'Manutenção', 'instalacao' => 'Instalação', 'garantia' => 'Garantia', 'visita' => 'Visita técnica', 'projeto' => 'Projeto', 'contrato' => 'Contrato', 'outro' => 'Outro'];
    $origens = ['balcao' => 'Balcão', 'telefone' => 'Telefone', 'app' => 'App', 'site' => 'Site', 'indicacao' => 'Indicação', 'contrato' => 'Contrato', 'outro' => 'Outro'];
    $prioridades = ['baixa' => 'Baixa', 'normal' => 'Normal', 'alta' => 'Alta', 'critica' => 'Crítica'];
    $statusOptions = $statusOptions ?? array_merge(
        \App\Models\StatusOs::where('ativo', true)->where('sistema', false)->orderBy('ordem')->orderBy('nome')->pluck('nome')->toArray(),
        ['Outro']
    );

    $statusAtual = old('status', $ordem->status ?: 'Aberta');
    $statusEhOutro = $statusAtual && !in_array($statusAtual, $statusOptions, true);

    $assinaturaToken = $assinaturaToken ?? null;
    $assinaturaCliente = $assinaturaCliente ?? null;
    $assinaturaTecnico = $assinaturaTecnico ?? null;
    $assinaturaUsuario = $assinaturaUsuario ?? null;

    // URLs das assinaturas (rota protegida que serve a imagem; não expõe arquivo direto)
    $assinaturaTecnicoImg = null;
    if (!empty($assinaturaTecnico) && !empty($assinaturaTecnico->signature_path)) {
        $assinaturaTecnicoImg = route('assinaturas.imagem', $assinaturaTecnico);
    }
    $assinaturaClienteImg = null;
    if (!empty($assinaturaCliente) && !empty($assinaturaCliente->signature_path)) {
        $assinaturaClienteImg = route('assinaturas.imagem', $assinaturaCliente);
    }
@endphp

<form action="{{ $isEdit ? route('ordens-servico.update', $ordem) : route('ordens-servico.store') }}" method="POST" enctype="multipart/form-data" id="osForm" data-ajax-save="{{ $isEdit ? '1' : '0' }}" data-equipamento-update-url="{{ $isEdit ? route('ordens-servico.equipamento', $ordem) : '' }}" data-cliente-update-url="{{ $isEdit ? route('ordens-servico.cliente', $ordem) : '' }}" data-anexos-url="{{ $isEdit ? route('ordens-servico.anexos.store', $ordem) : '' }}" data-anexos-delete-url="{{ $isEdit ? route('ordens-servico.anexos.destroy', [$ordem, '__ID__']) : '' }}" data-anexos-update-url="{{ $isEdit ? route('ordens-servico.anexos.update', [$ordem, '__ID__']) : '' }}" data-os-encerrada="{{ $osSomenteLeitura ? '1' : '0' }}">
    @csrf
    <input type="hidden" name="save_scope" id="os_save_scope" value="full">
    @if($isEdit)
        @method('PUT')
    @endif

    @if($isEdit)
        <div class="mb-4 rounded-lg border border-gray-200 bg-white px-4 py-3 text-sm font-semibold text-gray-800 shadow-theme-sm dark:border-gray-800 dark:bg-gray-800 dark:text-white/90">
            {{ format_os_display($ordem) }}
        </div>
    @endif

    <div id="os-tabs-nav" class="mb-6 flex flex-wrap gap-2">
        @if(function_exists('do_action'))
        <?php do_action('ordens_servico.form.tabs.before', $ordem ?? null, $isEdit); ?>
        @endif
        <button type="button" class="os-tab rounded-lg border border-gray-300 px-3 py-1.5 text-sm font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-700 dark:text-gray-300" data-tab="cadastro">Cadastro</button>
        <button type="button" class="os-tab rounded-lg border border-gray-300 px-3 py-1.5 text-sm font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-700 dark:text-gray-300" data-tab="equipamento">Equipamento</button>
        <button type="button" class="os-tab rounded-lg border border-gray-300 px-3 py-1.5 text-sm font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-700 dark:text-gray-300" data-tab="checklists">Checklists</button>
        <button type="button" class="os-tab rounded-lg border border-gray-300 px-3 py-1.5 text-sm font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-700 dark:text-gray-300" data-tab="itens">Itens</button>
        <button type="button" class="os-tab rounded-lg border border-gray-300 px-3 py-1.5 text-sm font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-700 dark:text-gray-300" data-tab="equipe">Equipe</button>
        <button type="button" class="os-tab rounded-lg border border-gray-300 px-3 py-1.5 text-sm font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-700 dark:text-gray-300" data-tab="sla">SLA</button>
        <button type="button" class="os-tab rounded-lg border border-gray-300 px-3 py-1.5 text-sm font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-700 dark:text-gray-300" data-tab="anexos">Anexos</button>
        <button type="button" class="os-tab rounded-lg border border-gray-300 px-3 py-1.5 text-sm font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-700 dark:text-gray-300" data-tab="garantia">Garantia e contrato</button>
        <button type="button" class="os-tab rounded-lg border border-gray-300 px-3 py-1.5 text-sm font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-700 dark:text-gray-300" data-tab="aprovacao">Aprovação</button>
        <button type="button" class="os-tab rounded-lg border border-gray-300 px-3 py-1.5 text-sm font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-700 dark:text-gray-300" data-tab="assinatura">Assinatura</button>
        <button type="button" class="os-tab rounded-lg border border-gray-300 px-3 py-1.5 text-sm font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-700 dark:text-gray-300" data-tab="totais">Totais</button>
        <button type="button" class="os-tab rounded-lg border border-gray-300 px-3 py-1.5 text-sm font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-700 dark:text-gray-300" data-tab="obs-internas">Obs. internas</button>
        @hasPermission('view-os-history')
        @if($isEdit)
        <button type="button" class="os-tab rounded-lg border border-gray-300 px-3 py-1.5 text-sm font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-700 dark:text-gray-300" data-tab="historico">Histórico</button>
        @endif
        @endhasPermission
        @if(function_exists('do_action'))
        <?php do_action('ordens_servico.form.tabs.after', $ordem ?? null, $isEdit); ?>
        @endif
    </div>

    <div class="grid grid-cols-1 gap-6 lg:grid-cols-2">
        @if(function_exists('do_action'))
        <?php do_action('ordens_servico.form.panels.before', $ordem ?? null, $isEdit); ?>
        @endif
        <div class="rounded-lg border border-gray-200 bg-white p-6 shadow-theme-sm dark:border-gray-800 dark:bg-gray-800 lg:col-span-2" data-tab-panel="cadastro">
            <h2 class="mb-4 text-lg font-semibold text-gray-800 dark:text-white/90">Dados principais</h2>
            @if($isEdit)
                @php
                    $clienteAtual = $ordem->cliente;
                    $baseCliente = $ordem->unidade ?? $clienteAtual;
                    $contatosCliente = $baseCliente?->contatos ?? collect();
                    $contatoPreferencial = $ordem->contato
                        ?? $contatosCliente->firstWhere('principal', true)
                        ?? $contatosCliente->sortBy('id')->first();
                    $contatosResumo = $contatosCliente
                        ->where('principal', false)
                        ->sortBy('id')
                        ->take(3);
                    $enderecoAtual = $ordem->endereco
                        ?? $baseCliente?->enderecos?->firstWhere('principal', true);
                    $contatoWhatsappPreferencial = $baseCliente
                        ? $baseCliente->contatos
                            ->where('telefone_whatsapp', true)
                            ->sortByDesc(fn($c) => (int) $c->principal)
                            ->first()
                        : null;
                    $enderecoTexto = $enderecoAtual
                        ? trim($enderecoAtual->logradouro . ' ' . $enderecoAtual->numero . ' ' . ($enderecoAtual->complemento ? '- ' . $enderecoAtual->complemento : '') . ', ' . $enderecoAtual->bairro . ' - ' . $enderecoAtual->cidade . '/' . $enderecoAtual->estado)
                        : '-';
                @endphp
                <div class="mb-5 rounded-lg border border-gray-200 bg-gray-50 p-4 text-sm text-gray-700 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300">
                <div class="mb-3 flex items-center justify-between">
                    <span class="font-medium">Dados do cliente</span>
                </div>
                    <div class="grid grid-cols-1 gap-2 md:grid-cols-2">
                    <div>
                        <span class="font-medium">Cliente:</span>
                        <span id="cliente_nome">{{ $clienteAtual->nome ?? '-' }}</span>
                        <button type="button" class="ml-2 inline-flex items-center gap-1 text-xs font-medium text-brand-600 hover:underline dark:text-brand-400" data-modal-open="osClienteModal">
                            <svg class="h-3.5 w-3.5" viewBox="0 0 20 20" aria-hidden="true">
                                <path fill="currentColor" d="M14.7 2.3a1 1 0 0 1 1.4 0l1.6 1.6a1 1 0 0 1 0 1.4l-8.6 8.6-3.2.5.5-3.2 8.6-8.6zM3 14.5V17h2.5l8.2-8.2-2.5-2.5L3 14.5z"/>
                            </svg>
                            Editar
                        </button>
                    </div>
                    <div>
                        <span class="font-medium">Endereço:</span>
                        <a id="cliente_endereco_link"
                           href="{{ $enderecoTexto && $enderecoTexto !== '-' ? 'https://www.google.com/maps/search/?api=1&query=' . urlencode($enderecoTexto) : '#' }}"
                           target="_blank" rel="noopener noreferrer"
                           class="text-brand-600 hover:underline dark:text-brand-400">
                            <span id="cliente_endereco">{{ $enderecoTexto }}</span>
                        </a>
                    </div>
                    <div>
                        <span class="font-medium">Telefone de contato:</span>
                        <a id="cliente_contato_telefone_link"
                           href="{{ !empty($contatoPreferencial?->telefone) ? 'tel:' . preg_replace('/\D+/', '', $contatoPreferencial->telefone) : '#' }}"
                           class="text-brand-600 hover:underline dark:text-brand-400">
                            <span id="cliente_contato_telefone">{{ $contatoPreferencial->telefone ?? '-' }}</span>
                        </a>
                    </div>
                    <div><span class="font-medium">Nome do contato:</span> <span id="cliente_contato_nome">{{ $contatoPreferencial->nome ?? '-' }}</span></div>
                    <div><span class="font-medium">E-mail:</span> <span id="cliente_contato_email">{{ $contatoPreferencial->email ?? '-' }}</span></div>
                        <div>
                            <span class="font-medium">WhatsApp:</span>
                        <a id="cliente_whatsapp_link"
                           href="{{ !empty($contatoWhatsappPreferencial?->telefone) ? 'https://wa.me/' . preg_replace('/\D+/', '', $contatoWhatsappPreferencial->telefone) : '#' }}"
                           target="_blank" rel="noopener noreferrer"
                           class="text-brand-600 hover:underline dark:text-brand-400">
                        <span id="cliente_whatsapp">
                            @if($contatoWhatsappPreferencial?->telefone)
                                <span class="inline-flex items-center gap-1">
                                    <svg class="h-4 w-4 text-green-500" viewBox="0 0 24 24" aria-hidden="true">
                                        <path fill="currentColor" d="M12.04 2C6.52 2 2 6.48 2 12c0 1.94.54 3.83 1.56 5.47L2 22l4.74-1.54A9.96 9.96 0 0 0 12.04 22C17.56 22 22 17.52 22 12S17.56 2 12.04 2zm0 18.2c-1.74 0-3.44-.5-4.9-1.45l-.35-.22-2.8.9.92-2.72-.23-.35A8.18 8.18 0 0 1 3.8 12c0-4.53 3.7-8.2 8.24-8.2 4.54 0 8.16 3.67 8.16 8.2 0 4.53-3.62 8.2-8.16 8.2zm4.6-6.14c-.25-.12-1.47-.72-1.7-.8-.23-.08-.39-.12-.56.12-.17.25-.64.8-.78.96-.14.17-.29.19-.53.07-.25-.12-1.05-.38-2-1.2-.74-.66-1.24-1.47-1.38-1.72-.14-.25-.02-.38.1-.5.1-.1.25-.29.37-.43.12-.14.17-.25.25-.41.08-.17.04-.31-.02-.43-.06-.12-.56-1.35-.77-1.85-.2-.48-.4-.41-.56-.42l-.48-.01c-.17 0-.43.06-.66.31-.23.25-.87.85-.87 2.07 0 1.22.9 2.4 1.02 2.56.12.17 1.77 2.7 4.3 3.79.6.26 1.07.42 1.44.54.61.19 1.17.16 1.61.1.49-.07 1.47-.6 1.68-1.18.2-.58.2-1.08.14-1.18-.06-.1-.22-.16-.47-.28z"/>
                                    </svg>
                                    {{ $contatoWhatsappPreferencial->telefone }}
                                </span>
                            @else
                                -
                            @endif
                        </span>
                        </a>
                        </div>
                    <div class="md:col-span-2" id="cliente_contatos_resumo">
                            <span class="font-medium">Contatos cadastrados:</span>
                            <div class="mt-1 space-y-1 text-xs text-gray-600 dark:text-gray-400">
                                @forelse($contatosResumo as $contato)
                                    <div>
                                        {{ $contato->nome ?? 'Contato' }}
                                        — {{ $contato->telefone ?? '-' }}
                                        @if(!empty($contato->email))
                                            • {{ $contato->email }}
                                        @endif
                                    </div>
                                @empty
                                    <div>-</div>
                                @endforelse
                            </div>
                        </div>
                    </div>
                </div>
            @endif
            <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                <div>
                    <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300">Tipo de OS</label>
                    <select name="tipo_os" class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm text-gray-800 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90">
                        <option value="">Selecione...</option>
                        @foreach($tipos as $valor => $label)
                            <option value="{{ $valor }}" {{ old('tipo_os', $ordem->tipo_os) == $valor ? 'selected' : '' }}>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300">Origem</label>
                    <select name="origem_os" class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm text-gray-800 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90">
                        <option value="">Selecione...</option>
                        @foreach($origens as $valor => $label)
                            <option value="{{ $valor }}" {{ old('origem_os', $ordem->origem_os) == $valor ? 'selected' : '' }}>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300">Prioridade</label>
                    <select name="prioridade" class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm text-gray-800 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90">
                        @foreach($prioridades as $valor => $label)
                            <option value="{{ $valor }}" {{ old('prioridade', $ordem->prioridade ?: 'normal') == $valor ? 'selected' : '' }}>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300">Status</label>
                    @if(in_array($ordem->status, ['Encerrado', 'Encerrada'], true))
                        <div class="rounded-lg border border-gray-300 bg-gray-50 px-4 py-2.5 text-sm font-medium text-gray-800 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90">Encerrada</div>
                        <input type="hidden" name="status" value="{{ $ordem->status }}">
                    @else
                        <select name="status" id="status_os" class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm text-gray-800 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90">
                            @foreach($statusOptions as $status)
                                <option value="{{ $status }}" {{ (!$statusEhOutro && $statusAtual === $status) ? 'selected' : '' }}>{{ $status }}</option>
                            @endforeach
                        </select>
                        <input type="text" name="status_outro" id="status_outro" value="{{ $statusEhOutro ? $statusAtual : old('status_outro') }}" placeholder="Informe o status" class="mt-2 w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm text-gray-800 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90 {{ $statusEhOutro ? '' : 'hidden' }}">
                    @endif
                </div>
                <div class="md:col-span-2">
                    <h3 class="mb-2 text-sm font-semibold text-gray-700 dark:text-gray-300">Prazos</h3>
                    <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                        <div>
                            <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300">Data de abertura</label>
                            <input type="datetime-local" name="data_abertura" value="{{ old('data_abertura', optional($ordem->data_abertura)->format('Y-m-d\TH:i')) }}" class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm text-gray-800 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90">
                        </div>
                        <div>
                            <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300">Garantia (dias)</label>
                            <input type="number" min="0" name="garantia_dias" value="{{ old('garantia_dias', $ordem->garantia_dias) }}" class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm text-gray-800 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90">
                        </div>
                    </div>
                </div>
            </div>

            <div class="mt-6">
                <h2 class="mb-4 text-lg font-semibold text-gray-800 dark:text-white/90">Descrição</h2>
                <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                    <div>
                        <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300">Problemas/Relato do cliente</label>
                        @if($osSomenteLeitura)
                            <div class="min-h-[120px] w-full rounded-lg border border-gray-300 bg-gray-50 px-4 py-2.5 text-sm text-gray-800 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90 [&_a]:text-brand-600 [&_ul]:list-disc [&_ul]:pl-5 [&_ol]:list-decimal [&_ol]:pl-5 [&_p]:my-1">{!! old('relato_cliente', $ordem->relato_cliente) !!}</div>
                        @else
                            <textarea name="relato_cliente" class="js-jodit w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm text-gray-800 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90">{!! old('relato_cliente', $ordem->relato_cliente) !!}</textarea>
                        @endif
                    </div>
                    <div>
                        <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300">Descrição</label>
                        @if($osSomenteLeitura)
                            <div class="min-h-[120px] w-full rounded-lg border border-gray-300 bg-gray-50 px-4 py-2.5 text-sm text-gray-800 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90 [&_a]:text-brand-600 [&_ul]:list-disc [&_ul]:pl-5 [&_ol]:list-decimal [&_ol]:pl-5 [&_p]:my-1">{!! old('servicos_realizados', $ordem->servicos_realizados) !!}</div>
                        @else
                            <textarea name="servicos_realizados" class="js-jodit w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm text-gray-800 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90">{!! old('servicos_realizados', $ordem->servicos_realizados) !!}</textarea>
                        @endif
                    </div>
                    <div>
                        <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300">Observações para cliente</label>
                        @if($osSomenteLeitura)
                            <div class="min-h-[120px] w-full rounded-lg border border-gray-300 bg-gray-50 px-4 py-2.5 text-sm text-gray-800 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90 [&_a]:text-brand-600 [&_ul]:list-disc [&_ul]:pl-5 [&_ol]:list-decimal [&_ol]:pl-5 [&_p]:my-1">{!! old('observacoes_cliente', $ordem->observacoes_cliente) !!}</div>
                        @else
                            <textarea name="observacoes_cliente" class="js-jodit w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm text-gray-800 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90">{!! old('observacoes_cliente', $ordem->observacoes_cliente) !!}</textarea>
                        @endif
                    </div>
                    <div>
                        <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300">Diagnóstico técnico</label>
                        @if($osSomenteLeitura)
                            <div class="min-h-[120px] w-full rounded-lg border border-gray-300 bg-gray-50 px-4 py-2.5 text-sm text-gray-800 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90 [&_a]:text-brand-600 [&_ul]:list-disc [&_ul]:pl-5 [&_ol]:list-decimal [&_ol]:pl-5 [&_p]:my-1">{!! old('diagnostico_tecnico', $ordem->diagnostico_tecnico) !!}</div>
                        @else
                            <textarea name="diagnostico_tecnico" class="js-jodit w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm text-gray-800 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90">{!! old('diagnostico_tecnico', $ordem->diagnostico_tecnico) !!}</textarea>
                        @endif
                    </div>
                </div>
            </div>
        </div>

        <div id="osClienteModal" class="fixed inset-0 z-[2147483001] hidden" style="z-index:2147483647 !important;">
            <div class="absolute inset-0 bg-gray-900/50" data-modal-close="osClienteModal"></div>
            <div class="relative mx-auto mt-20 w-full max-w-2xl rounded-lg border border-gray-200 bg-white p-6 shadow-lg dark:border-gray-700 dark:bg-gray-800">
                <div class="mb-4 flex items-center justify-between">
                    <h3 class="text-lg font-semibold text-gray-800 dark:text-white/90">Editar dados do cliente</h3>
                    <button type="button" class="text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200" data-modal-close="osClienteModal">✕</button>
                </div>
                <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                    <div>
                        <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300">Cliente <span class="text-error-500">*</span></label>
                        <input type="text" id="cliente_busca" list="clientes_lista" value="{{ $clienteAtual->nome ?? '' }}" placeholder="Digite para buscar..." class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm text-gray-800 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90">
                        <datalist id="clientes_lista">
                            @foreach($clientes as $cliente)
                                <option value="{{ $cliente->nome }}" data-id="{{ $cliente->id }}" data-grupo="{{ $cliente->grupo_economico_id }}"></option>
                            @endforeach
                        </datalist>
                        <select name="cliente_id" id="cliente_id" required class="hidden">
                            <option value="">Selecione...</option>
                            @foreach($clientes as $cliente)
                                <option value="{{ $cliente->id }}" data-grupo="{{ $cliente->grupo_economico_id }}" {{ old('cliente_id', $ordem->cliente_id) == $cliente->id ? 'selected' : '' }}>{{ $cliente->nome }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300">Unidade (filial)</label>
                        <select name="cliente_unidade_id" id="cliente_unidade_id" class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm text-gray-800 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90">
                            <option value="">Selecione...</option>
                            @foreach($unidades as $unidade)
                                <option value="{{ $unidade->id }}" data-grupo="{{ $unidade->grupo_economico_id }}" data-matriz="{{ $unidade->matriz_id }}" {{ old('cliente_unidade_id', $ordem->cliente_unidade_id) == $unidade->id ? 'selected' : '' }}>{{ $unidade->nome }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300">Contato principal</label>
                        <select name="contato_id" id="contato_id" class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm text-gray-800 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90">
                            <option value="">Selecione...</option>
                            @foreach($contatos as $contato)
                                @php
                                    $contatoWhatsapp = $contato->telefone_whatsapp ? ($contato->telefone ?? $contato->telefone2 ?? '') : ($contato->telefone ?? $contato->telefone2 ?? '');
                                @endphp
                                <option value="{{ $contato->id }}" data-cliente="{{ $contato->cliente_id }}" data-principal="{{ $contato->principal ? '1' : '0' }}" data-email="{{ e($contato->email ?? '') }}" data-telefone="{{ e($contato->telefone ?? '') }}" data-whatsapp="{{ e($contatoWhatsapp ?? '') }}" {{ old('contato_id', $ordem->contato_id) == $contato->id ? 'selected' : '' }}>{{ $contato->nome }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300">Endereço</label>
                        <select name="endereco_id" id="endereco_id" class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm text-gray-800 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90">
                            <option value="">Selecione...</option>
                            @foreach($enderecos as $endereco)
                                @php
                                    $enderecoLabel = trim(($endereco->logradouro ?? '') . ' ' . ($endereco->numero ?? '') . ' ' . ($endereco->complemento ? '- ' . $endereco->complemento : '') . ', ' . ($endereco->bairro ?? '') . ' - ' . ($endereco->cidade ?? '') . '/' . ($endereco->estado ?? ''));
                                @endphp
                                <option value="{{ $endereco->id }}" data-cliente="{{ $endereco->cliente_id }}" data-principal="{{ $endereco->principal ? '1' : '0' }}" {{ old('endereco_id', $ordem->endereco_id) == $endereco->id ? 'selected' : '' }}>{{ $enderecoLabel }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <div class="mt-6 flex justify-end gap-2">
                    <button type="button" class="rounded-lg border border-gray-300 px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-700 dark:text-gray-300" data-modal-close="osClienteModal">Fechar</button>
                    <button type="button" id="osClienteSalvar" class="rounded-lg bg-brand-500 px-4 py-2 text-sm font-medium text-white hover:bg-brand-600">Alterar</button>
                </div>
            </div>
        </div>

        <div id="osEquipamentoModal" class="fixed inset-0 z-[2147483001] hidden" style="z-index:2147483647 !important;">
            <div class="absolute inset-0 bg-gray-900/50" data-modal-close="osEquipamentoModal"></div>
            <div class="relative mx-auto mt-20 w-full max-w-xl rounded-lg border border-gray-200 bg-white p-6 shadow-lg dark:border-gray-700 dark:bg-gray-800">
                <div class="mb-4 flex items-center justify-between">
                    <h3 class="text-lg font-semibold text-gray-800 dark:text-white/90">Selecionar equipamento</h3>
                    <button type="button" class="text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200" data-modal-close="osEquipamentoModal">✕</button>
                </div>
                <div>
                    <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300">Equipamento do cliente</label>
                    <select id="equipamento_modal_id" class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm text-gray-800 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90">
                        <option value="">Selecione...</option>
                        @foreach($equipamentos as $equipamento)
                            @php
                                $label = trim(($equipamento->marca ?? '') . ' ' . ($equipamento->modelo ?? '') . ' ' . ($equipamento->numero_serie ?? '')) ?: 'Equipamento #' . $equipamento->id;
                            @endphp
                            <option value="{{ $equipamento->id }}"
                                data-cliente="{{ $equipamento->cliente_id }}"
                                data-marca="{{ $equipamento->marca }}"
                                data-modelo="{{ $equipamento->modelo }}"
                                data-numero-serie="{{ $equipamento->numero_serie }}"
                                data-numero-patrimonio="{{ $equipamento->numero_patrimonio }}"
                                data-acessorios="{{ $equipamento->acessorios }}"
                                {{ old('equipamento_id', $ordem->equipamento_id) == $equipamento->id ? 'selected' : '' }}>
                                {{ $label }}
                            </option>
                        @endforeach
                    </select>
                    <p class="mt-2 text-xs text-gray-500 dark:text-gray-400">A lista é filtrada pelo cliente selecionado.</p>
                </div>
                <div class="mt-5">
                    <button type="button" id="osEquipamentoNovoToggle" class="inline-flex items-center gap-2 text-xs font-medium text-brand-600 hover:underline dark:text-brand-400">
                        <svg class="h-3.5 w-3.5" viewBox="0 0 20 20" aria-hidden="true">
                            <path fill="currentColor" d="M10 3a1 1 0 0 1 1 1v5h5a1 1 0 1 1 0 2h-5v5a1 1 0 1 1-2 0v-5H4a1 1 0 1 1 0-2h5V4a1 1 0 0 1 1-1z"/>
                        </svg>
                        Cadastrar novo equipamento
                    </button>
                </div>
                <div id="osEquipamentoNovoForm" class="mt-4 hidden rounded-lg border border-gray-200 p-4 dark:border-gray-700">
                    <p class="mb-3 text-xs text-gray-500 dark:text-gray-400">O equipamento será cadastrado para o cliente selecionado.</p>
                    <div class="grid grid-cols-1 gap-3 md:grid-cols-2">
                        <input type="hidden" id="equipamento_novo_cliente_id">
                        <div class="md:col-span-2">
                            <label class="mb-1 block text-xs text-gray-600 dark:text-gray-400">Equipamento <span class="text-error-500">*</span></label>
                            <input type="text" id="equipamento_novo_nome" class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm text-gray-800 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90">
                        </div>
                        <div>
                            <label class="mb-1 block text-xs text-gray-600 dark:text-gray-400">Marca</label>
                            <input type="text" id="equipamento_novo_marca" class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm text-gray-800 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90">
                        </div>
                        <div>
                            <label class="mb-1 block text-xs text-gray-600 dark:text-gray-400">Modelo</label>
                            <input type="text" id="equipamento_novo_modelo" class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm text-gray-800 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90">
                        </div>
                        <div>
                            <label class="mb-1 block text-xs text-gray-600 dark:text-gray-400">Nº Série</label>
                            <input type="text" id="equipamento_novo_numero_serie" class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm text-gray-800 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90">
                        </div>
                        <div>
                            <label class="mb-1 block text-xs text-gray-600 dark:text-gray-400">Nº Patrimônio</label>
                            <input type="text" id="equipamento_novo_numero_patrimonio" class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm text-gray-800 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90">
                        </div>
                        <div class="md:col-span-2">
                            <label class="mb-1 block text-xs text-gray-600 dark:text-gray-400">Acessórios</label>
                            <textarea id="equipamento_novo_acessorios" rows="2" class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm text-gray-800 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90"></textarea>
                        </div>
                    </div>
                    <div class="mt-3 flex justify-end gap-2">
                        <button type="button" id="osEquipamentoNovoCancelar" class="rounded-lg border border-gray-300 px-3 py-1.5 text-xs font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-700 dark:text-gray-300">Cancelar</button>
                        <button type="button" id="osEquipamentoNovoSalvar" class="rounded-lg bg-brand-500 px-3 py-1.5 text-xs font-medium text-white hover:bg-brand-600">Salvar novo</button>
                    </div>
                </div>
                <div class="mt-6 flex justify-end gap-2">
                    <button type="button" class="rounded-lg border border-gray-300 px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-700 dark:text-gray-300" data-modal-close="osEquipamentoModal">Cancelar</button>
                    <button type="button" id="osEquipamentoSalvar" class="rounded-lg bg-brand-500 px-4 py-2 text-sm font-medium text-white hover:bg-brand-600">Salvar</button>
                </div>
            </div>
        </div>

        <div class="rounded-lg border border-gray-200 bg-white p-6 shadow-theme-sm dark:border-gray-800 dark:bg-gray-800" data-tab-panel="equipamento">
            <div class="flex items-center justify-between">
                <h2 class="text-lg font-semibold text-gray-800 dark:text-white/90">Equipamento</h2>
            </div>
            <div id="os-equipamento" class="mt-4">
                <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                <div class="md:col-span-2">
                    <div class="mb-1.5 flex items-center justify-between">
                        <label class="text-sm font-medium text-gray-700 dark:text-gray-300">Equipamento cadastrado</label>
                        <button type="button" class="inline-flex items-center gap-1 text-xs font-medium text-brand-600 hover:underline dark:text-brand-400" aria-label="Editar equipamento" data-modal-open="osEquipamentoModal">
                            <svg class="h-3.5 w-3.5" viewBox="0 0 20 20" aria-hidden="true">
                                <path fill="currentColor" d="M14.7 2.3a1 1 0 0 1 1.4 0l1.6 1.6a1 1 0 0 1 0 1.4l-8.6 8.6-3.2.5.5-3.2 8.6-8.6zM3 14.5V17h2.5l8.2-8.2-2.5-2.5L3 14.5z"/>
                            </svg>
                            Editar
                        </button>
                    </div>
                    <input type="hidden" name="equipamento_id" id="equipamento_id" value="{{ old('equipamento_id', $ordem->equipamento_id) }}">
                    <p class="text-xs text-gray-500 dark:text-gray-400">Clique em editar para escolher outro equipamento do cliente.</p>
                </div>
                <div class="md:col-span-2">
                    <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300">Equipamento / Nome</label>
                    <input type="text" name="equipamento_nome" value="{{ old('equipamento_nome', $equipamentoNomeDefault) }}" class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm text-gray-800 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90" placeholder="Opcional">
                </div>
                <div>
                    <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300">Marca</label>
                    <input type="text" name="equipamento_marca" value="{{ old('equipamento_marca', $ordem->equipamento_marca ?: ($equipamentoAtual->marca ?? null)) }}" class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm text-gray-800 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90">
                </div>
                <div>
                    <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300">Modelo</label>
                    <input type="text" name="equipamento_modelo" value="{{ old('equipamento_modelo', $ordem->equipamento_modelo ?: ($equipamentoAtual->modelo ?? null)) }}" class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm text-gray-800 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90">
                </div>
                <div>
                    <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300">Nº Série</label>
                    <input type="text" name="equipamento_numero_serie" value="{{ old('equipamento_numero_serie', $ordem->equipamento_numero_serie ?: ($equipamentoAtual->numero_serie ?? null)) }}" class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm text-gray-800 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90">
                </div>
                <div>
                    <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300">Nº Patrimônio</label>
                    <input type="text" name="equipamento_numero_patrimonio" value="{{ old('equipamento_numero_patrimonio', $ordem->equipamento_numero_patrimonio ?: ($equipamentoAtual->numero_patrimonio ?? null)) }}" class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm text-gray-800 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90">
                </div>
                <div class="md:col-span-2">
                    <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300">Acessórios</label>
                    <textarea name="equipamento_acessorios" rows="2" class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm text-gray-800 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90">{{ old('equipamento_acessorios', $ordem->equipamento_acessorios ?: ($equipamentoAtual->acessorios ?? null)) }}</textarea>
                </div>
                
                <!-- Senha de Desbloqueio -->
                <div class="md:col-span-2">
                    <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300">Senha de Desbloqueio</label>
                    <div class="space-y-4">
                        <!-- Tipo de Desbloqueio -->
                        <div>
                            <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300">Tipo de Desbloqueio</label>
                            <select name="equipamento_unlock_type" id="unlock_type" class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm text-gray-800 focus:border-brand-500 focus:ring-brand-500 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90">
                                <option value="">Selecione...</option>
                                <option value="pattern" {{ old('equipamento_unlock_type', $ordem->equipamento_unlock_type) === 'pattern' ? 'selected' : '' }}>Padrão (Desenho/Android)</option>
                                <option value="pin" {{ old('equipamento_unlock_type', $ordem->equipamento_unlock_type) === 'pin' ? 'selected' : '' }}>Senha Numérica (PIN) / Texto</option>
                                <option value="none" {{ old('equipamento_unlock_type', $ordem->equipamento_unlock_type) === 'none' ? 'selected' : '' }}>Biometria / Sem Senha</option>
                            </select>
                        </div>
                        
                        <!-- Container para os diferentes tipos -->
                        <div id="unlock_content_container">
                            <!-- Padrão (Desenho) -->
                            <div id="unlock_pattern_container" class="hidden">
                                <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300">Desenhe o padrão de desbloqueio</label>
                                <div id="pattern_draw_area" class="flex justify-center p-4 bg-gray-50 dark:bg-gray-900 rounded-lg"></div>
                                <input type="hidden" name="equipamento_unlock_code" id="pattern_code_input" value="{{ old('equipamento_unlock_code', $ordem->equipamento_unlock_code) }}">
                                <button type="button" id="clear_pattern_btn" class="mt-2 text-sm text-error-500 hover:text-error-700 dark:text-error-400">Limpar padrão</button>
                            </div>
                            
                            <!-- PIN/Texto -->
                            <div id="unlock_pin_container" class="hidden">
                                <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300">Senha/PIN</label>
                                <input type="text" name="equipamento_unlock_code" id="pin_code_input" value="{{ old('equipamento_unlock_code', $ordem->equipamento_unlock_code) }}" class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm text-gray-800 focus:border-brand-500 focus:ring-brand-500 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90" placeholder="Digite a senha ou PIN">
                            </div>
                            
                            <!-- Biometria/Sem Senha -->
                            <div id="unlock_none_container" class="hidden">
                                <div class="rounded-lg border border-amber-300 bg-amber-50 p-4 dark:border-amber-700 dark:bg-amber-900/20">
                                    <p class="text-sm text-amber-800 dark:text-amber-200">
                                        <strong>Cliente virá para desbloquear</strong> ou <strong>Aparelho sem senha</strong>
                                    </p>
                                </div>
                                <input type="hidden" name="equipamento_unlock_code" value="">
                            </div>
                        </div>
                    </div>
                </div>
                </div>
            </div>
        </div>

        <!-- Aba Checklists -->
        <div class="rounded-lg border border-gray-200 bg-white p-6 shadow-theme-sm dark:border-gray-800 dark:bg-gray-800 lg:col-span-2" data-tab-panel="checklists">
            <div class="flex items-center justify-between mb-4">
                <h2 class="text-lg font-semibold text-gray-800 dark:text-white/90">Checklists</h2>
                <button type="button" onclick="abrirModalVincularChecklist()" class="rounded-lg bg-brand-500 px-4 py-2.5 text-sm font-medium text-white hover:bg-brand-600">
                    + Vincular Checklist
                </button>
            </div>
            
            <div id="checklists_container" class="space-y-4">
                @if(isset($checklists) && $checklists->count() > 0)
                    @foreach($checklists as $checklist)
                        @include('ordens_servico._checklist_card', ['checklist' => $checklist])
                    @endforeach
                @else
                    <div class="rounded-lg border-2 border-dashed border-gray-300 p-8 text-center dark:border-gray-700">
                        <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                        </svg>
                        <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">Nenhum checklist vinculado</p>
                        <p class="text-xs text-gray-400 dark:text-gray-500">Clique em "Vincular Checklist" para adicionar um modelo de checklist</p>
                    </div>
                @endif
            </div>
        </div>

        <div class="rounded-lg border border-gray-200 bg-white p-6 shadow-theme-sm dark:border-gray-800 dark:bg-gray-800 lg:col-span-2" data-tab-panel="itens">
            <div class="mb-4 flex flex-col gap-3 md:flex-row md:items-center md:justify-between">
                <h2 class="text-lg font-semibold text-gray-800 dark:text-white/90">Produtos / Peças</h2>
                <div class="flex flex-wrap items-center gap-3">
                    <div class="flex items-center gap-2">
                        <label class="text-xs font-medium text-gray-600 dark:text-gray-400">Tabela de preços</label>
                        <select id="tabela_preco" name="tabela_preco" class="rounded-lg border border-gray-300 px-3 py-1.5 text-sm text-gray-800 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90">
                            <option value="varejo" selected>Varejo</option>
                            <option value="empresa">Empresa</option>
                            <option value="urgencia">Urgência</option>
                        </select>
                    </div>
                    <button type="button" onclick="adicionarProduto()" class="rounded-lg bg-brand-500 px-3 py-1.5 text-sm font-medium text-white hover:bg-brand-600">+ Produto</button>
                    @hasPermission('ordem_servico.produto_servico_avulso')
                    <button type="button" data-modal-open="produtoAvulsoModal" class="rounded-lg border border-gray-300 px-3 py-1.5 text-sm font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-700 dark:text-gray-300">+ Produto avulso</button>
                    @endhasPermission
                </div>
            </div>
            <div id="produtos_container" class="space-y-4">
                @foreach($produtosItens as $index => $item)
                    <div class="rounded-lg border border-gray-200 p-4 dark:border-gray-700" data-produto-index="{{ $index }}">
                        <div class="grid grid-cols-1 gap-3 md:grid-cols-7">
                            <div class="md:col-span-2">
                                <label class="mb-1 block text-xs text-gray-600 dark:text-gray-400">Produto</label>
                                <select name="produtos[{{ $index }}][produto_id]" class="produto-select w-full rounded-lg border border-gray-300 px-3 py-2 text-sm text-gray-800 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90">
                                    <option value="">Selecione...</option>
                                    @foreach($produtos as $produto)
                                        @php
                                            $tabelasProduto = $produto->tabelasPrecos ?? collect();
                                            $precoVarejo = $tabelasProduto->first(fn($t) => strcasecmp($t->nome, 'varejo') === 0)?->pivot?->preco ?? $produto->preco_venda;
                                            $precoEmpresa = $tabelasProduto->first(fn($t) => strcasecmp($t->nome, 'empresa') === 0)?->pivot?->preco ?? $produto->preco_venda;
                                            $precoUrgencia = $tabelasProduto->first(fn($t) => strcasecmp($t->nome, 'urgencia') === 0 || strcasecmp($t->nome, 'urgência') === 0)?->pivot?->preco ?? $produto->preco_venda;
                                            $imagensProduto = ($produto->imagens ?? collect())
                                                ->sortBy('ordem')
                                                ->map(function ($imagem) {
                                                    return $imagem->tipo === 'url'
                                                        ? $imagem->url_externa
                                                        : ($imagem->caminho_local ? asset('storage/' . $imagem->caminho_local) : null);
                                                })
                                                ->filter()
                                                ->values();
                                            $variacoesJson = ($produto->formato ?? '') === 'variacao' && ($produto->variacoes ?? collect())->isNotEmpty()
                                                ? $produto->variacoes->map(fn($v) => ['id' => $v->id, 'referencia_sku' => $v->referencia_sku ?? '', 'opcoes_valores' => is_array($v->opcoes_valores ?? null) ? implode(', ', $v->opcoes_valores) : ($v->opcoes_valores ?? ''), 'quantidade' => $v->quantidade])->values()->toJson()
                                                : '[]';
                                        @endphp
                                        <option value="{{ $produto->id }}"
                                            data-preco="{{ $produto->preco_venda }}"
                                            data-preco-varejo="{{ $precoVarejo }}"
                                            data-preco-empresa="{{ $precoEmpresa }}"
                                            data-preco-urgencia="{{ $precoUrgencia }}"
                                            data-descricao="{{ $produto->descricao_complementar }}"
                                            data-imagens='@json($imagensProduto)'
                                            data-variacoes='{{ $variacoesJson }}'
                                            data-estoque="{{ $produto->estoque_quantidade ?? 0 }}"
                                            {{ ($item['produto_id'] ?? null) == $produto->id ? 'selected' : '' }}>
                                            {{ $produto->nome }}
                                        </option>
                                    @endforeach
                                </select>
                                @php
                                    $produtoSelecionado = collect($produtos)->firstWhere('id', $item['produto_id'] ?? null);
                                    $variacoesItem = ($produtoSelecionado && ($produtoSelecionado->formato ?? '') === 'variacao') ? ($produtoSelecionado->variacoes ?? collect()) : collect();
                                @endphp
                                <div class="produto-variacao-wrapper mt-1.5 {{ $variacoesItem->isEmpty() ? 'hidden' : '' }}">
                                    <label class="mb-1 block text-xs text-gray-600 dark:text-gray-400">Variação <span class="text-error-500">*</span></label>
                                    <select name="produtos[{{ $index }}][produto_variacao_id]" class="variacao-select w-full rounded-lg border border-gray-300 px-3 py-2 text-sm text-gray-800 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90" {{ $variacoesItem->isNotEmpty() ? 'required' : '' }}>
                                        <option value="">Selecione a variação...</option>
                                        @foreach($variacoesItem as $var)
                                            <option value="{{ $var->id }}" data-quantidade="{{ $var->quantidade ?? '' }}" {{ ($item['produto_variacao_id'] ?? null) == $var->id ? 'selected' : '' }}>
                                                {{ $var->referencia_sku ?: 'SKU' }} {{ is_array($var->opcoes_valores) ? '(' . implode(', ', $var->opcoes_valores) . ')' : ($var->opcoes_valores ? '(' . $var->opcoes_valores . ')' : '') }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                            <div class="flex flex-col justify-end">
                                <label class="mb-1 block text-xs text-gray-600 dark:text-gray-400">Estoque</label>
                                <div class="min-h-[42px] rounded-lg border border-gray-200 bg-gray-50 px-3 py-2 text-sm font-medium text-gray-800 dark:border-gray-700 dark:bg-gray-900/50 dark:text-gray-200">
                                    <span class="produto-estoque-valor">—</span>
                                </div>
                            </div>
                            <div>
                                <label class="mb-1 block text-xs text-gray-600 dark:text-gray-400">Qtd</label>
                                <input type="number" step="0.001" min="0" name="produtos[{{ $index }}][quantidade]" value="{{ format_quantity($item['quantidade'] ?? 1) }}" class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm text-gray-800 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90">
                            </div>
                            <div>
                                <label class="mb-1 block text-xs text-gray-600 dark:text-gray-400">Valor unit.</label>
                                <input type="number" step="0.01" min="0" name="produtos[{{ $index }}][valor_unitario]" value="{{ $item['valor_unitario'] ?? 0 }}" @if(!empty($usuarioSujeitoALimiteDesconto) && ($limiteDesconto->bloquear_alteracao_preco ?? false) && !empty($item['produto_id'] ?? null)) readonly title="Preço do cadastro não pode ser alterado." @endif class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm text-gray-800 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90">
                            </div>
                            @php
                                $descontoTipoProduto = !empty($item['desconto_percentual']) ? 'percentual' : 'valor';
                                $descontoValorProduto = $item['desconto_percentual'] ?? ($item['desconto_valor'] ?? '');
                            @endphp
                            <div>
                                <label class="mb-1 block text-xs text-gray-600 dark:text-gray-400">Tipo de desconto</label>
                                <select name="produtos[{{ $index }}][desconto_tipo]" class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm text-gray-800 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90">
                                    <option value="valor" {{ $descontoTipoProduto === 'valor' ? 'selected' : '' }}>R$</option>
                                    <option value="percentual" {{ $descontoTipoProduto === 'percentual' ? 'selected' : '' }}>%</option>
                                </select>
                            </div>
                            <div class="desconto-item-cell">
                                <label class="mb-1 block text-xs text-gray-600 dark:text-gray-400">Desconto</label>
                                <input type="number" step="0.01" min="0" name="produtos[{{ $index }}][desconto_valor_unico]" value="{{ $descontoValorProduto }}" @if($descontoTipoProduto === 'percentual') max="100" @endif class="js-desconto-item-valor w-full rounded-lg border border-gray-300 px-3 py-2 text-sm text-gray-800 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90">
                                <p class="desconto-item-aviso mt-0.5 hidden text-xs text-error-600 dark:text-error-400" role="alert"></p>
                                <input type="hidden" name="produtos[{{ $index }}][desconto_valor]" value="{{ $item['desconto_valor'] ?? '' }}">
                                <input type="hidden" name="produtos[{{ $index }}][desconto_percentual]" value="{{ $item['desconto_percentual'] ?? '' }}">
                            </div>
                        </div>
                        <div class="mt-3 grid grid-cols-1 gap-2 md:grid-cols-6">
                            <div class="md:col-span-2">
                                <label class="mb-1 block text-xs text-gray-600 dark:text-gray-400">Descrição</label>
                                <input type="text" name="produtos[{{ $index }}][descricao]" value="{{ $item['descricao'] ?? '' }}" class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm text-gray-800 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90">
                            </div>
                            <div class="md:col-span-6 produto-imagens hidden">
                                <label class="mb-1 block text-xs text-gray-600 dark:text-gray-400">Imagens do produto</label>
                                <div class="flex flex-wrap gap-2"></div>
                            </div>
                        </div>
                        <div class="mt-3 flex justify-end">
                            <button type="button" onclick="removerLinha(this)" class="text-error-500 hover:underline">Remover</button>
                        </div>
                    </div>
                @endforeach
            </div>
            <div class="mt-4 rounded-lg border border-gray-200 bg-gray-50 p-4 text-sm text-gray-700 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300">
                <div class="grid grid-cols-1 gap-2 md:grid-cols-3">
                    <div><span class="font-medium">Subtotal produtos:</span> <span id="subtotal_produtos">R$ 0,00</span></div>
                    <div><span class="font-medium">Descontos produtos:</span> <span id="descontos_produtos">R$ 0,00</span></div>
                    <div><span class="font-medium">Total produtos:</span> <span id="total_produtos_itens">R$ 0,00</span></div>
                </div>
            </div>
        </div>

        <div class="rounded-lg border border-gray-200 bg-white p-6 shadow-theme-sm dark:border-gray-800 dark:bg-gray-800 lg:col-span-2" data-tab-panel="itens">
            <div class="mb-4 flex items-center justify-between">
                <h2 class="text-lg font-semibold text-gray-800 dark:text-white/90">Serviços</h2>
                <div class="flex flex-wrap items-center gap-2">
                    <button type="button" onclick="adicionarServico()" class="rounded-lg bg-brand-500 px-3 py-1.5 text-sm font-medium text-white hover:bg-brand-600">+ Serviço</button>
                    @hasPermission('ordem_servico.produto_servico_avulso')
                    <button type="button" data-modal-open="servicoAvulsoModal" class="rounded-lg border border-gray-300 px-3 py-1.5 text-sm font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-700 dark:text-gray-300">+ Serviço avulso</button>
                    @endhasPermission
                </div>
            </div>
            <div id="servicos_container" class="space-y-4">
                @foreach($servicosItens as $index => $item)
                    <div class="rounded-lg border border-gray-200 p-4 dark:border-gray-700" data-servico-index="{{ $index }}">
                        @php
                            $cobrancaTipoServico = $item['cobranca_tipo'] ?? 'unidade';
                            if ($cobrancaTipoServico === 'quantidade') {
                                $cobrancaTipoServico = 'unidade';
                            } elseif ($cobrancaTipoServico === 'tempo') {
                                $cobrancaTipoServico = 'horas';
                            }
                            $quantidadeServico = format_quantity($item['quantidade'] ?? 1);
                            $quantidadeHorasServico = format_quantity($item['quantidade_horas'] ?? 1);
                        @endphp
                        <div class="grid grid-cols-1 gap-3 md:grid-cols-8">
                            <div class="md:col-span-2">
                                <label class="mb-1 block text-xs text-gray-600 dark:text-gray-400">Serviço</label>
                                <select name="servicos[{{ $index }}][servico_id]" class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm text-gray-800 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90">
                                    <option value="">Selecione...</option>
                                    @foreach($servicos as $servico)
                                        <option value="{{ $servico->id }}" data-preco="{{ $servico->preco }}" {{ ($item['servico_id'] ?? null) == $servico->id ? 'selected' : '' }}>{{ $servico->nome }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="md:col-span-1">
                                <label class="mb-1 block text-xs text-gray-600 dark:text-gray-400">Cobrança por horas</label>
                                <div class="flex items-center gap-2 rounded-lg border border-gray-300 px-3 py-2 text-xs text-gray-700 dark:border-gray-700 dark:text-gray-300">
                                    <input type="checkbox" class="h-4 w-4" data-servico-cobranca-toggle {{ $cobrancaTipoServico === 'horas' ? 'checked' : '' }}>
                                    <span>Horas</span>
                                </div>
                                <input type="hidden" name="servicos[{{ $index }}][cobranca_tipo]" value="{{ $cobrancaTipoServico }}">
                            </div>
                            <div class="md:col-span-1 servico-cobranca-unidade {{ $cobrancaTipoServico === 'unidade' ? '' : 'hidden' }}">
                                <label class="mb-1 block text-xs text-gray-600 dark:text-gray-400">Qtd</label>
                                <input type="number" step="0.001" min="0" name="servicos[{{ $index }}][quantidade]" value="{{ $quantidadeServico }}" class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm text-gray-800 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90">
                            </div>
                            <div class="md:col-span-1 servico-cobranca-horas {{ $cobrancaTipoServico === 'horas' ? '' : 'hidden' }}">
                                <label class="mb-1 block text-xs text-gray-600 dark:text-gray-400">Horas</label>
                                <input type="number" step="0.001" min="0" name="servicos[{{ $index }}][quantidade_horas]" value="{{ $quantidadeHorasServico }}" class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm text-gray-800 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90">
                            </div>
                            <div class="md:col-span-1">
                                <label class="mb-1 block text-xs text-gray-600 dark:text-gray-400">Técnico</label>
                                <select name="servicos[{{ $index }}][tecnico_id]" class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm text-gray-800 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90">
                                    <option value="">Selecione...</option>
                                    @foreach($tecnicos as $tecnico)
                                        <option value="{{ $tecnico->id }}" {{ ($item['tecnico_id'] ?? null) == $tecnico->id ? 'selected' : '' }}>{{ $tecnico->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="md:col-span-1">
                                <label class="mb-1 block text-xs text-gray-600 dark:text-gray-400">Valor</label>
                                <input type="number" step="0.01" min="0" name="servicos[{{ $index }}][valor_unitario]" value="{{ $item['valor_unitario'] ?? 0 }}" @if(!empty($usuarioSujeitoALimiteDesconto) && ($limiteDesconto->bloquear_alteracao_preco ?? false) && !empty($item['servico_id'] ?? null)) readonly title="Preço do cadastro não pode ser alterado." @endif class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm text-gray-800 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90">
                            </div>
                        </div>
                        <div class="mt-3 grid grid-cols-1 gap-2 md:grid-cols-6">
                            <div class="md:col-span-2">
                                <label class="mb-1 block text-xs text-gray-600 dark:text-gray-400">Descrição</label>
                                <input type="text" name="servicos[{{ $index }}][descricao]" value="{{ $item['descricao'] ?? '' }}" class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm text-gray-800 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90">
                            </div>
                            @php
                                $descontoTipoServico = !empty($item['desconto_percentual']) ? 'percentual' : 'valor';
                                $descontoValorServico = $item['desconto_percentual'] ?? ($item['desconto_valor'] ?? '');
                            @endphp
                            <div>
                                <label class="mb-1 block text-xs text-gray-600 dark:text-gray-400">Tipo de desconto</label>
                                <select name="servicos[{{ $index }}][desconto_tipo]" class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm text-gray-800 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90">
                                    <option value="valor" {{ $descontoTipoServico === 'valor' ? 'selected' : '' }}>R$</option>
                                    <option value="percentual" {{ $descontoTipoServico === 'percentual' ? 'selected' : '' }}>%</option>
                                </select>
                            </div>
                            <div class="desconto-item-cell">
                                <label class="mb-1 block text-xs text-gray-600 dark:text-gray-400">Desconto</label>
                                <input type="number" step="0.01" min="0" name="servicos[{{ $index }}][desconto_valor_unico]" value="{{ $descontoValorServico }}" @if($descontoTipoServico === 'percentual') max="100" @endif class="js-desconto-item-valor w-full rounded-lg border border-gray-300 px-3 py-2 text-sm text-gray-800 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90">
                                <p class="desconto-item-aviso mt-0.5 hidden text-xs text-error-600 dark:text-error-400" role="alert"></p>
                                <input type="hidden" name="servicos[{{ $index }}][desconto_valor]" value="{{ $item['desconto_valor'] ?? '' }}">
                                <input type="hidden" name="servicos[{{ $index }}][desconto_percentual]" value="{{ $item['desconto_percentual'] ?? '' }}">
                            </div>
                        </div>
                        <div class="mt-3 flex justify-end">
                            <button type="button" onclick="removerLinha(this)" class="text-error-500 hover:underline">Remover</button>
                        </div>
                    </div>
                @endforeach
            </div>
            <div class="mt-4 rounded-lg border border-gray-200 bg-gray-50 p-4 text-sm text-gray-700 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300">
                <div class="grid grid-cols-1 gap-2 md:grid-cols-3">
                    <div><span class="font-medium">Subtotal serviços:</span> <span id="subtotal_servicos">R$ 0,00</span></div>
                    <div><span class="font-medium">Descontos serviços:</span> <span id="descontos_servicos">R$ 0,00</span></div>
                    <div><span class="font-medium">Total serviços:</span> <span id="total_servicos_itens">R$ 0,00</span></div>
                </div>
            </div>
        </div>

        <div class="rounded-lg border border-gray-200 bg-white p-6 shadow-theme-sm dark:border-gray-800 dark:bg-gray-800" data-tab-panel="sla">
            <div class="mb-4 flex items-center justify-between">
                <div class="flex items-center gap-2">
                    <h2 class="text-lg font-semibold text-gray-800 dark:text-white/90">SLA e execução</h2>
                    <button type="button" class="inline-flex h-6 w-6 items-center justify-center rounded-full border border-gray-300 text-xs font-semibold text-gray-600 hover:bg-gray-50 dark:border-gray-600 dark:text-gray-300 dark:hover:bg-gray-700" aria-label="Ajuda sobre SLA e execução" data-modal-open="slaAjudaModal">?</button>
                </div>
            </div>
            <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                <div>
                    <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300">Prevista início</label>
                    <input type="datetime-local" name="prevista_inicio" value="{{ old('prevista_inicio', optional($ordem->prevista_inicio)->format('Y-m-d\TH:i')) }}" class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm text-gray-800 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90">
                </div>
                <div>
                    <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300">Prevista conclusão</label>
                    <input type="datetime-local" name="prevista_conclusao" value="{{ old('prevista_conclusao', optional($ordem->prevista_conclusao)->format('Y-m-d\TH:i')) }}" class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm text-gray-800 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90">
                </div>
                <div>
                    <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300">Início real</label>
                    <input type="datetime-local" name="real_inicio" value="{{ old('real_inicio', optional($ordem->real_inicio)->format('Y-m-d\TH:i')) }}" class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm text-gray-800 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90">
                </div>
                <div>
                    <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300">Conclusão real</label>
                    <input type="datetime-local" name="real_conclusao" value="{{ old('real_conclusao', optional($ordem->real_conclusao)->format('Y-m-d\TH:i')) }}" class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm text-gray-800 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90">
                </div>
                <div>
                    <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300">SLA</label>
                    <input type="number" min="0" name="sla_valor" value="{{ old('sla_valor', $ordem->sla_valor) }}" class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm text-gray-800 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90">
                </div>
                <div>
                    <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300">Unidade</label>
                    <select name="sla_unidade" class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm text-gray-800 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90">
                        <option value="">Selecione...</option>
                        <option value="horas" {{ old('sla_unidade', $ordem->sla_unidade) === 'horas' ? 'selected' : '' }}>Horas</option>
                        <option value="dias" {{ old('sla_unidade', $ordem->sla_unidade) === 'dias' ? 'selected' : '' }}>Dias</option>
                    </select>
                </div>
            </div>
        </div>

        <div class="rounded-lg border border-gray-200 bg-white p-6 shadow-theme-sm dark:border-gray-800 dark:bg-gray-800 lg:col-span-2" data-tab-panel="equipe">
            <div class="mb-4 flex items-center justify-between">
                <div class="flex items-center gap-2">
                    <h2 class="text-lg font-semibold text-gray-800 dark:text-white/90">Técnicos e apontamentos</h2>
                    <button type="button" class="inline-flex h-6 w-6 items-center justify-center rounded-full border border-gray-300 text-xs font-semibold text-gray-600 hover:bg-gray-50 dark:border-gray-600 dark:text-gray-300 dark:hover:bg-gray-700" aria-label="Ajuda sobre técnicos e apontamentos" data-modal-open="equipeAjudaModal">?</button>
                </div>
                <button type="button" onclick="adicionarTecnico()" class="rounded-lg border border-gray-300 px-3 py-1.5 text-sm font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-700 dark:text-gray-300">+ Técnico</button>
            </div>
            <div id="tecnicos_container" class="space-y-3">
                @foreach($tecnicosItens as $index => $item)
                    <div class="grid grid-cols-1 gap-2 md:grid-cols-6">
                        <select name="tecnicos[{{ $index }}][tecnico_id]" class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm text-gray-800 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90">
                            <option value="">Selecione...</option>
                            @foreach($tecnicos as $tecnico)
                                <option value="{{ $tecnico->id }}" {{ ($item['tecnico_id'] ?? null) == $tecnico->id ? 'selected' : '' }}>{{ $tecnico->name }}</option>
                            @endforeach
                        </select>
                        <input type="text" name="tecnicos[{{ $index }}][status]" value="{{ $item['status'] ?? '' }}" placeholder="Status" class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm text-gray-800 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90">
                        <input type="datetime-local" name="tecnicos[{{ $index }}][inicio]" value="{{ !empty($item['inicio']) ? \Carbon\Carbon::parse($item['inicio'])->format('Y-m-d\TH:i') : '' }}" class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm text-gray-800 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90">
                        <input type="datetime-local" name="tecnicos[{{ $index }}][fim]" value="{{ !empty($item['fim']) ? \Carbon\Carbon::parse($item['fim'])->format('Y-m-d\TH:i') : '' }}" class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm text-gray-800 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90">
                        <input type="number" step="0.01" min="0" name="tecnicos[{{ $index }}][horas_trabalhadas]" value="{{ $item['horas_trabalhadas'] ?? '' }}" placeholder="Horas" class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm text-gray-800 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90">
                        <button type="button" onclick="removerLinha(this)" class="text-error-500 hover:underline text-sm">Remover</button>
                    </div>
                @endforeach
            </div>

            <div class="mt-6 flex items-center justify-between">
                <h3 class="text-md font-semibold text-gray-800 dark:text-white/90">Apontamentos</h3>
                <button type="button" onclick="adicionarApontamento()" class="rounded-lg border border-gray-300 px-3 py-1.5 text-sm font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-700 dark:text-gray-300">+ Apontamento</button>
            </div>
            <div id="apontamentos_container" class="mt-3 space-y-3">
                @foreach($apontamentos as $index => $item)
                    <div class="grid grid-cols-1 gap-2 md:grid-cols-6">
                        <select name="apontamentos[{{ $index }}][tecnico_id]" class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm text-gray-800 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90">
                            <option value="">Selecione...</option>
                            @foreach($tecnicos as $tecnico)
                                <option value="{{ $tecnico->id }}" {{ ($item['tecnico_id'] ?? null) == $tecnico->id ? 'selected' : '' }}>{{ $tecnico->name }}</option>
                            @endforeach
                        </select>
                        <input type="datetime-local" name="apontamentos[{{ $index }}][inicio]" value="{{ !empty($item['inicio']) ? \Carbon\Carbon::parse($item['inicio'])->format('Y-m-d\TH:i') : '' }}" class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm text-gray-800 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90">
                        <input type="datetime-local" name="apontamentos[{{ $index }}][fim]" value="{{ !empty($item['fim']) ? \Carbon\Carbon::parse($item['fim'])->format('Y-m-d\TH:i') : '' }}" class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm text-gray-800 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90">
                        <input type="number" min="0" name="apontamentos[{{ $index }}][pausas_minutos]" value="{{ $item['pausas_minutos'] ?? '' }}" placeholder="Pausas (min)" class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm text-gray-800 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90">
                        <input type="text" name="apontamentos[{{ $index }}][status]" value="{{ $item['status'] ?? '' }}" placeholder="Status" class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm text-gray-800 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90">
                        <button type="button" onclick="removerLinha(this)" class="text-error-500 hover:underline text-sm">Remover</button>
                        <textarea name="apontamentos[{{ $index }}][observacoes]" rows="1" placeholder="Observações" class="md:col-span-6 w-full rounded-lg border border-gray-300 px-3 py-2 text-sm text-gray-800 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90">{{ $item['observacoes'] ?? '' }}</textarea>
                    </div>
                @endforeach
            </div>
        </div>


        <div class="rounded-lg border border-gray-200 bg-white p-6 shadow-theme-sm dark:border-gray-800 dark:bg-gray-800 lg:col-span-2" data-tab-panel="anexos">
            <div class="mb-4 flex items-center justify-between">
                <h2 class="text-lg font-semibold text-gray-800 dark:text-white/90">Anexos</h2>
                @if($isEdit && ! $osSomenteLeitura)
                <datalist id="anexo-tipos-datalist">
                    <option value="Foto"><option value="Laudo"><option value="Documento"><option value="Orçamento"><option value="Comprovante"><option value="Evidência"><option value="Outro">
                </datalist>
                <label class="cursor-pointer rounded-lg border border-gray-300 px-3 py-1.5 text-sm font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-700 dark:text-gray-300">
                    <input type="file" id="anexos_file_input" multiple class="sr-only" accept="image/*,.pdf,.doc,.docx,.xls,.xlsx,.txt,.zip">
                    📎 Selecionar arquivos
                </label>
                @elseif($isEdit)
                <p class="text-sm text-gray-500 dark:text-gray-400">OS encerrada — anexos somente visualização.</p>
                @else
                <p class="text-sm text-gray-500 dark:text-gray-400">Salve a OS primeiro para adicionar anexos.</p>
                @endif
            </div>

            @if($isEdit && ! $osSomenteLeitura)
            {{-- Modal editar anexo - deve vir antes do script --}}
            <div id="anexoEditarModal" class="fixed inset-0 z-[110] hidden flex items-center justify-center bg-black/60 p-4" role="dialog" aria-modal="true" style="display:none;">
                <div class="relative w-full max-w-md rounded-lg border border-gray-200 bg-white p-6 shadow-xl dark:border-gray-700 dark:bg-gray-800" onclick="event.stopPropagation()">
                    <div class="mb-4 flex items-center justify-between">
                        <h3 class="text-lg font-semibold text-gray-800 dark:text-white/90">Editar metadados do anexo</h3>
                        <button type="button" id="anexoEditarModalFechar" class="text-2xl leading-none text-gray-500 hover:text-gray-700 dark:text-gray-400">&times;</button>
                    </div>
                    <p id="anexoEditarModalNome" class="mb-3 truncate text-sm text-gray-600 dark:text-gray-400"></p>
                    <div class="space-y-3">
                        <div>
                            <label class="mb-1 block text-xs font-medium text-gray-600 dark:text-gray-400">Tipo</label>
                            <input type="text" id="anexoEditarModalTipo" class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm dark:border-gray-600 dark:bg-gray-700 dark:text-white" placeholder="Ex: Foto, Laudo">
                        </div>
                        <div>
                            <label class="mb-1 block text-xs font-medium text-gray-600 dark:text-gray-400">Tags</label>
                            <input type="text" id="anexoEditarModalTags" class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm dark:border-gray-600 dark:bg-gray-700 dark:text-white" placeholder="Tags separadas por vírgula">
                        </div>
                        <div>
                            <label class="mb-1 block text-xs font-medium text-gray-600 dark:text-gray-400">Descrição</label>
                            <textarea id="anexoEditarModalDescricao" rows="3" class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm dark:border-gray-600 dark:bg-gray-700 dark:text-white" placeholder="Descrição do anexo"></textarea>
                        </div>
                    </div>
                    <div class="mt-5 flex justify-end gap-2">
                        <button type="button" id="anexoEditarModalCancelar" class="rounded-lg border border-gray-300 px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-700 dark:text-gray-300">Cancelar</button>
                        <button type="button" id="anexoEditarModalSalvar" class="rounded-lg bg-brand-500 px-4 py-2 text-sm font-medium text-white hover:bg-brand-600">Salvar</button>
                    </div>
                </div>
            </div>
            {{-- Lista de arquivos selecionados (pendentes) com campos tipo, tags, descrição --}}
            <div id="anexos_pendentes_wrapper" class="mb-4 hidden">
                <h3 class="mb-2 text-sm font-medium text-gray-700 dark:text-gray-300">Arquivos selecionados</h3>
                <div id="anexos_pendentes_lista" class="space-y-3"></div>
                <button type="button" id="anexos_salvar_btn" class="mt-3 rounded-lg bg-brand-500 px-4 py-2 text-sm font-medium text-white hover:bg-brand-600">Salvar anexos</button>
            </div>
            <script>
            function initAnexoEditarModal(){
                if(window._anexosEditReady)return;
                window._anexosEditReady=1;
                var modal=document.getElementById('anexoEditarModal');
                var inpTipo=document.getElementById('anexoEditarModalTipo');
                var inpTags=document.getElementById('anexoEditarModalTags');
                var inpDesc=document.getElementById('anexoEditarModalDescricao');
                var lblNome=document.getElementById('anexoEditarModalNome');
                var btnSalvar=document.getElementById('anexoEditarModalSalvar');
                var btnCancelar=document.getElementById('anexoEditarModalCancelar');
                var btnFechar=document.getElementById('anexoEditarModalFechar');
                if(!modal)return;
                var cardAtual=null;
                var updateUrlAtual='';
                function abrirModal(btn){
                    if(!btn||!modal)return;
                    var card=btn.closest('[data-anexo-id]');
                    if(!card)return;
                    cardAtual=card;
                    updateUrlAtual=btn.dataset.updateUrl||'';
                    var tipo=btn.dataset.anexoTipo||'';
                    var tags=btn.dataset.anexoTags||'';
                    var desc=btn.dataset.anexoDescricao||'';
                    var nome=btn.dataset.anexoNome||'';
                    if(inpTipo)inpTipo.value=tipo;
                    if(inpTags)inpTags.value=tags;
                    if(inpDesc)inpDesc.value=desc;
                    if(lblNome)lblNome.textContent=nome;
                    modal.classList.remove('hidden');
                    modal.style.display='flex';
                }
                function fecharModal(){ modal.classList.add('hidden'); modal.style.display='none'; cardAtual=null; updateUrlAtual=''; }
                function salvarModal(){
                    if(!updateUrlAtual||!cardAtual)return;
                    var tipo=(inpTipo&&inpTipo.value)?inpTipo.value.trim():'';
                    var tags=(inpTags&&inpTags.value)?inpTags.value.trim():'';
                    var desc=(inpDesc&&inpDesc.value)?inpDesc.value.trim():'';
                    var csrf=document.querySelector('meta[name="csrf-token"]')?.content||document.querySelector('input[name="_token"]')?.value;
                    btnSalvar&&(btnSalvar.disabled=true);
                    fetch(updateUrlAtual,{method:'PATCH',headers:{'Content-Type':'application/json','X-Requested-With':'XMLHttpRequest','Accept':'application/json',...(csrf?{'X-CSRF-TOKEN':csrf}:{})},credentials:'same-origin',body:JSON.stringify({tipo:tipo||null,tags:tags||null,descricao:desc||null})})
                    .then(r=>r.json().catch(()=>({})))
                    .then(data=>{
                        if(data.anexo){
                            var a=data.anexo;
                            var line=(a.tipo||'')+(a.tags?' - '+a.tags:'')+(a.descricao?' — '+(a.descricao.length>60?a.descricao.slice(0,57)+'...':a.descricao):'');
                            var card=a.id?document.querySelector('[data-anexo-id="'+a.id+'"]'):cardAtual;
                            if(card){
                                var p=card.querySelector('.anexo-view-content p');
                                if(p)p.textContent=line||'';
                                var eb=card.querySelector('.anexo-editar-btn');
                                if(eb){eb.dataset.anexoTipo=a.tipo||'';eb.dataset.anexoTags=a.tags||'';eb.dataset.anexoDescricao=(a.descricao||'').replace(/\r/g,' ').replace(/\n/g,' ');}
                            }
                            typeof showToast==='function'&&showToast(data.message||'Anexo atualizado.','success');
                        }else{typeof showToast==='function'&&showToast(data.message||'Erro ao salvar.','error');}
                    }).catch(e=>{typeof showToast==='function'&&showToast('Erro: '+e.message,'error');})
                    .finally(()=>{btnSalvar&&(btnSalvar.disabled=false);fecharModal();});
                }
                document.addEventListener('click',function(ev){
                    var el=ev.target;if(el&&el.nodeType!==1)el=el.parentElement;
                    if(!el||!el.closest)return;
                    var btn=el.closest('.anexo-editar-btn');
                    if(btn){ev.preventDefault();ev.stopPropagation();abrirModal(btn);}
                },true);
                if(btnSalvar)btnSalvar.onclick=salvarModal;
                if(btnCancelar)btnCancelar.onclick=fecharModal;
                if(btnFechar)btnFechar.onclick=fecharModal;
                if(modal)modal.onclick=function(e){if(e.target===modal)fecharModal();};
            }
            initAnexoEditarModal();
            </script>
            @endif

            {{-- Anexos já gravados: sempre em modo edição (inclui OS encerrada — import M-OS costuma vir encerrada) --}}
            @if($isEdit)
            <h3 class="mb-2 text-sm font-medium text-gray-700 dark:text-gray-300">Anexos salvos</h3>
            <div id="anexos_existentes_lista" class="space-y-2">
                @if($ordem->anexos->count())
                    @foreach($ordem->anexos as $anexo)
                        @php
                            $anexoUrl = route('ordens-servico.anexos.file', $anexo);
                            $ext = strtolower(pathinfo($anexo->nome_arquivo ?? '', PATHINFO_EXTENSION));
                            $isImg = in_array($ext, ['jpg', 'jpeg', 'png', 'gif', 'webp', 'bmp', 'svg'], true) || (isset($anexo->tipo_mime) && str_starts_with((string)($anexo->tipo_mime ?? ''), 'image/'));
                        @endphp
                        <div class="anexo-card rounded-lg border border-gray-200 p-3 text-sm dark:border-gray-700" data-anexo-id="{{ $anexo->id }}">
                            <div class="flex items-center gap-3">
                                @if($isImg)
                                    <button type="button" class="anexo-preview-img shrink-0 cursor-pointer" data-url="{{ $anexoUrl }}" data-nome="{{ $anexo->nome_arquivo }}">
                                        <img src="{{ $anexoUrl }}" alt="" class="h-14 w-14 rounded object-cover object-center" loading="lazy" onerror="this.style.display='none'; this.nextElementSibling && (this.nextElementSibling.style.display='flex');">
                                        <span class="hidden h-14 w-14 items-center justify-center rounded bg-gray-100 text-gray-400 text-xs" style="display:none;">?</span>
                                    </button>
                                @else
                                    <a href="{{ $anexoUrl }}" download="{{ $anexo->nome_arquivo }}" class="flex h-14 w-14 shrink-0 items-center justify-center rounded bg-gray-100 text-gray-500 dark:bg-gray-700">
                                        <svg class="h-8 w-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                                    </a>
                                @endif
                                <div class="min-w-0 flex-1">
                                    <p class="font-medium text-gray-800 dark:text-white/90 truncate">{{ $anexo->nome_arquivo }}</p>
                                    <div class="anexo-view-content">
                                        <p class="text-xs text-gray-500 dark:text-gray-400">{{ $anexo->tipo ?? '' }}{{ $anexo->tags ? ' - ' . $anexo->tags : '' }}{{ $anexo->descricao ? ' — ' . Str::limit($anexo->descricao, 60) : '' }}</p>
                                    </div>
                                </div>
                                <div class="flex shrink-0 flex-wrap gap-2">
                                    @if(! $osSomenteLeitura)
                                    <button type="button" class="anexo-editar-btn rounded-lg border border-gray-300 px-3 py-1.5 text-xs font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-700 dark:text-gray-300" data-anexo-id="{{ $anexo->id }}" data-update-url="{{ route('ordens-servico.anexos.update', [$ordem, $anexo]) }}" data-anexo-tipo="{{ $anexo->tipo ?? '' }}" data-anexo-tags="{{ $anexo->tags ?? '' }}" data-anexo-descricao="{{ str_replace(["\r","\n"], ' ', $anexo->descricao ?? '') }}" data-anexo-nome="{{ $anexo->nome_arquivo ?? '' }}">Editar</button>
                                    @endif
                                    @if($isImg)
                                    <button type="button" class="anexo-preview-img rounded-lg border border-gray-300 px-3 py-1.5 text-xs font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-700 dark:text-gray-300" data-url="{{ $anexoUrl }}" data-nome="{{ $anexo->nome_arquivo }}">Ver</button>
                                    @else
                                    <a href="{{ $anexoUrl }}" download="{{ $anexo->nome_arquivo }}" class="rounded-lg border border-gray-300 px-3 py-1.5 text-xs font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-700 dark:text-gray-300">Download</a>
                                    @endif
                                    @if(! $osSomenteLeitura)
                                    <button type="button" class="anexo-excluir-btn rounded-lg border border-error-300 px-3 py-1.5 text-xs font-medium text-error-600 hover:bg-error-50 dark:border-error-700 dark:text-error-400" data-anexo-id="{{ $anexo->id }}" data-delete-url="{{ route('ordens-servico.anexos.destroy', [$ordem, $anexo]) }}">Excluir</button>
                                    @endif
                                </div>
                            </div>
                        </div>
                    @endforeach
                @endif
            </div>
            <div id="anexos_vazio" class="mt-2 text-sm text-gray-500 dark:text-gray-400 {{ $ordem->anexos->count() ? 'hidden' : '' }}">Nenhum anexo ainda.</div>
            @else
            <div class="mb-4">
                <button type="button" onclick="adicionarAnexo()" class="rounded-lg border border-gray-300 px-3 py-1.5 text-sm font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-700 dark:text-gray-300">+ Anexo</button>
            </div>
            <div id="anexos_container" class="space-y-3"></div>
            <div id="anexos_remover_container"></div>
            @endif
        </div>

        <div class="rounded-lg border border-gray-200 bg-white p-6 shadow-theme-sm dark:border-gray-800 dark:bg-gray-800" data-tab-panel="garantia">
            <div class="mb-4 flex items-center justify-between">
                <div class="flex items-center gap-2">
                    <h2 class="text-lg font-semibold text-gray-800 dark:text-white/90">Garantia e contrato</h2>
                    <button type="button" class="inline-flex h-6 w-6 items-center justify-center rounded-full border border-gray-300 text-xs font-semibold text-gray-600 hover:bg-gray-50 dark:border-gray-600 dark:text-gray-300 dark:hover:bg-gray-700" aria-label="Ajuda sobre garantia e contrato" data-modal-open="garantiaAjudaModal">?</button>
                </div>
            </div>
            <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                <div>
                    <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300">Garantia</label>
                    <select name="garantia_tipo" class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm text-gray-800 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90">
                        <option value="">Selecione...</option>
                        <option value="dentro" {{ old('garantia_tipo', $ordem->garantia_tipo) === 'dentro' ? 'selected' : '' }}>Dentro de garantia</option>
                        <option value="fora" {{ old('garantia_tipo', $ordem->garantia_tipo) === 'fora' ? 'selected' : '' }}>Fora de garantia</option>
                        <option value="estendida" {{ old('garantia_tipo', $ordem->garantia_tipo) === 'estendida' ? 'selected' : '' }}>Garantia estendida</option>
                    </select>
                </div>
                <div>
                    <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300">Termo de Garantia</label>
                    <select name="termo_garantia_id" class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm text-gray-800 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90">
                        <option value="">Selecione...</option>
                        @foreach($termosGarantia ?? [] as $termo)
                            <option value="{{ $termo->id }}" {{ old('termo_garantia_id', $ordem->termo_garantia_id) == $termo->id ? 'selected' : '' }}>{{ $termo->nome }}</option>
                        @endforeach
                    </select>
                    <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                        <a href="{{ route('configuracoes-sistema.termos-garantia.index') }}" target="_blank" class="text-brand-600 hover:underline dark:text-brand-400">Cadastrar termos de garantia</a>
                    </p>
                </div>
                <div>
                    <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300">Número do contrato</label>
                    <input type="text" name="contrato_numero" value="{{ old('contrato_numero', $ordem->contrato_numero) }}" class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm text-gray-800 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90">
                </div>
                <div>
                    <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300">Tipo do contrato</label>
                    <input type="text" name="contrato_tipo" value="{{ old('contrato_tipo', $ordem->contrato_tipo) }}" class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm text-gray-800 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90">
                </div>
                <div class="flex items-center gap-2">
                    <input type="checkbox" name="recorrente" value="1" {{ old('recorrente', $ordem->recorrente) ? 'checked' : '' }} class="rounded border-gray-300 text-brand-500 focus:ring-brand-500">
                    <span class="text-sm text-gray-700 dark:text-gray-300">OS recorrente</span>
                </div>
                <div class="flex items-center gap-2">
                    <input type="checkbox" name="coberto_por_contrato" value="1" {{ old('coberto_por_contrato', $ordem->coberto_por_contrato) ? 'checked' : '' }} class="rounded border-gray-300 text-brand-500 focus:ring-brand-500">
                    <span class="text-sm text-gray-700 dark:text-gray-300">Coberto por contrato</span>
                </div>
                <div class="md:col-span-2">
                    <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300">Periodicidade</label>
                    <input type="text" name="recorrencia_periodicidade" value="{{ old('recorrencia_periodicidade', $ordem->recorrencia_periodicidade) }}" placeholder="Ex: mensal, trimestral" class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm text-gray-800 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90">
                </div>
            </div>
        </div>

        <div class="rounded-lg border border-gray-200 bg-white p-6 shadow-theme-sm dark:border-gray-800 dark:bg-gray-800" data-tab-panel="aprovacao">
            <h2 class="mb-4 text-lg font-semibold text-gray-800 dark:text-white/90">Aprovação</h2>
            <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                <div>
                    <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300">Aprovado por</label>
                    <input type="text" name="aprovado_nome" value="{{ old('aprovado_nome', $ordem->aprovado_nome) }}" class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm text-gray-800 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90">
                </div>
                <div>
                    <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300">Data da aprovação</label>
                    <input type="datetime-local" name="aprovado_em" value="{{ old('aprovado_em', optional($ordem->aprovado_em)->format('Y-m-d\TH:i')) }}" class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm text-gray-800 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90">
                </div>
                <div>
                    <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300">Canal</label>
                    <input type="text" name="aprovado_canal" value="{{ old('aprovado_canal', $ordem->aprovado_canal) }}" class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm text-gray-800 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90">
                </div>
                <div>
                    <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300">Assinatura (hash/arquivo)</label>
                    <input type="text" name="aprovado_assinatura" value="{{ old('aprovado_assinatura', $ordem->aprovado_assinatura) }}" class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm text-gray-800 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90">
                </div>
                <div class="flex items-center gap-2">
                    <input type="checkbox" name="aprovado_cliente" value="1" {{ old('aprovado_cliente', $ordem->aprovado_cliente) ? 'checked' : '' }} class="rounded border-gray-300 text-brand-500 focus:ring-brand-500">
                    <span class="text-sm text-gray-700 dark:text-gray-300">Aprovado pelo cliente</span>
                </div>
            </div>
        </div>

        <div class="rounded-lg border border-gray-200 bg-white p-6 shadow-theme-sm dark:border-gray-800 dark:bg-gray-800" data-tab-panel="assinatura">
            <h2 class="mb-4 text-lg font-semibold text-gray-800 dark:text-white/90">Assinatura</h2>
            @if($osSomenteLeitura)
            <div class="mb-4 rounded-lg border border-amber-300 bg-amber-50 p-4 text-sm text-amber-800 dark:border-amber-700 dark:bg-amber-900/20 dark:text-amber-200">
                <strong>Somente visualização.</strong> Não é possível assinar ou alterar assinaturas em OS encerrada. Todas as informações e o histórico permanecem visíveis.
            </div>
            @endif
            <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                <div class="rounded-lg border border-gray-200 p-4 dark:border-gray-700">
                    <h3 class="mb-2 text-sm font-semibold text-gray-700 dark:text-gray-300">Assinatura do técnico</h3>
                    <p class="text-sm text-gray-600 dark:text-gray-400">Link para assinatura do usuário logado.</p>
                    @if(! $osSomenteLeitura)
                    <div class="mt-3 flex flex-wrap gap-2">
                        <a href="{{ route('assinaturas.tecnico', $ordem) }}" target="_blank" class="rounded-lg border border-gray-300 px-3 py-1.5 text-sm font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-700 dark:text-gray-300">Abrir link</a>
                        @if(!empty($assinaturaUsuario))
                            <button type="button" data-assinatura-aplicar="{{ route('assinaturas.tecnico.aplicar', $ordem) }}" class="rounded-lg border border-brand-400 px-3 py-1.5 text-sm font-medium text-brand-600 hover:bg-brand-50 dark:border-brand-500 dark:text-brand-300">Usar assinatura salva</button>
                            <button type="button" data-assinatura-usuario-delete="{{ route('assinaturas.usuario.delete') }}" class="rounded-lg border border-error-300 px-3 py-1.5 text-sm font-medium text-error-600 hover:bg-error-50 dark:border-error-700 dark:text-error-400">Excluir assinatura salva</button>
                        @endif
                        @if(!empty($assinaturaTecnico))
                            <button type="button" data-assinatura-tecnico-delete="{{ route('assinaturas.tecnico.delete', $ordem) }}" class="rounded-lg border border-error-300 px-3 py-1.5 text-sm font-medium text-error-600 hover:bg-error-50 dark:border-error-700 dark:text-error-400">Apagar assinatura da OS</button>
                        @endif
                    </div>
                    @endif
                    @if(!empty($assinaturaTecnicoImg))
                        <div class="mt-3">
                            <img src="{{ $assinaturaTecnicoImg }}" alt="Assinatura do técnico" class="h-24 rounded border border-gray-200 bg-white p-2">
                        </div>
                    @endif
                    <div class="mt-3 text-xs text-gray-500 dark:text-gray-400">
                        @if(!empty($assinaturaTecnico))
                            Assinatura do técnico registrada em {{ optional($assinaturaTecnico->signed_at)->format('d/m/Y H:i') }}.
                        @else
                            Nenhuma assinatura do técnico registrada nesta OS.
                        @endif
                    </div>
                </div>

                <div class="rounded-lg border border-gray-200 p-4 dark:border-gray-700">
                    <h3 class="mb-2 text-sm font-semibold text-gray-700 dark:text-gray-300">Assinatura do cliente</h3>
                    <p class="text-sm text-gray-600 dark:text-gray-400">Link para o cliente assinar com token seguro.</p>
                    @if(! $osSomenteLeitura)
                    <div class="mt-3 flex flex-wrap gap-2">
                        @if(!empty($assinaturaToken))
                            <a href="{{ route('assinaturas.cliente', $assinaturaToken->token) }}" target="_blank" class="rounded-lg border border-gray-300 px-3 py-1.5 text-sm font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-700 dark:text-gray-300">Abrir link</a>
                        @endif
                        <button type="button" data-assinatura-cliente-token="{{ route('assinaturas.cliente.token', $ordem) }}" class="rounded-lg border border-brand-400 px-3 py-1.5 text-sm font-medium text-brand-600 hover:bg-brand-50 dark:border-brand-500 dark:text-brand-300">
                            {{ $assinaturaToken ? 'Gerar novo link' : 'Gerar link' }}
                        </button>
                        @if(!empty($assinaturaCliente))
                            <button type="button" data-assinatura-cliente-delete="{{ route('assinaturas.cliente.delete', $ordem) }}" class="rounded-lg border border-error-300 px-3 py-1.5 text-sm font-medium text-error-600 hover:bg-error-50 dark:border-error-700 dark:text-error-400">Apagar assinatura do cliente</button>
                        @endif
                    </div>
                    @endif
                    <div class="mt-3 text-xs text-gray-500 dark:text-gray-400">
                        @if(!empty($assinaturaToken))
                            Token: {{ $assinaturaToken->status ?? 'sent' }} • Expira em {{ optional($assinaturaToken->expires_at)->format('d/m/Y H:i') ?? '-' }}
                        @else
                            Nenhum link gerado para assinatura do cliente.
                        @endif
                    </div>
                    @if(!empty($assinaturaClienteImg))
                        <div class="mt-3">
                            <img src="{{ $assinaturaClienteImg }}" alt="Assinatura do cliente" class="h-24 rounded border border-gray-200 bg-white p-2">
                        </div>
                    @endif
                    <div class="mt-2 text-xs text-gray-500 dark:text-gray-400">
                        @if(!empty($assinaturaCliente))
                            Assinatura do cliente registrada em {{ optional($assinaturaCliente->signed_at)->format('d/m/Y H:i') }}.
                        @else
                            Nenhuma assinatura do cliente registrada nesta OS.
                        @endif
                    </div>
                </div>
            </div>

            @if($isEdit && $ordem->documentos->count())
            <div class="mt-6 rounded-lg border border-gray-200 p-4 dark:border-gray-700">
                <h3 class="mb-3 text-sm font-semibold text-gray-700 dark:text-gray-300">Documentos Assinados</h3>
                <div class="space-y-2">
                    @foreach($ordem->documentos->sortByDesc('gerado_em') as $documento)
                        <div class="flex items-center justify-between rounded-lg border border-gray-200 p-3 dark:border-gray-700 {{ $documento->ativo ? 'bg-brand-50 dark:bg-brand-900/20' : '' }}">
                            <div class="flex-1">
                                <div class="flex items-center gap-2">
                                    <span class="text-sm font-medium text-gray-800 dark:text-white/90">{{ $documento->file_name }}</span>
                                    @if($documento->ativo)
                                        <span class="rounded bg-brand-500 px-2 py-0.5 text-xs font-medium text-white">Ativo</span>
                                    @else
                                        <span class="rounded bg-gray-500 px-2 py-0.5 text-xs font-medium text-white">Histórico</span>
                                    @endif
                                </div>
                                <div class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                                    Gerado em {{ optional($documento->gerado_em)->format('d/m/Y H:i') ?? '-' }}
                                    @if($documento->file_size)
                                        • {{ number_format($documento->file_size / 1024, 2) }} KB
                                    @endif
                                </div>
                                @if($documento->metadata['seguranca']['hash_sha256'] ?? null)
                                <div class="mt-2 space-y-1 text-xs">
                                    <div class="font-medium text-gray-700 dark:text-gray-300">Hashes de Integridade:</div>
                                    <div class="font-mono text-[10px] text-gray-600 dark:text-gray-400 break-all">
                                        <div><strong>SHA-256:</strong> {{ $documento->metadata['seguranca']['hash_sha256'] }}</div>
                                        @if($documento->metadata['seguranca']['hash_md5'] ?? null)
                                        <div class="mt-1"><strong>MD5:</strong> {{ $documento->metadata['seguranca']['hash_md5'] }}</div>
                                        @endif
                                        @if($documento->metadata['seguranca']['assinatura_digital'] ?? null)
                                        <div class="mt-1"><strong>Assinatura Digital:</strong> {{ $documento->metadata['seguranca']['assinatura_digital'] }}</div>
                                        @endif
                                    </div>
                                </div>
                                @endif
                            </div>
                            <div class="flex gap-2">
                                <a href="{{ route('assinaturas.documento.download', $documento->id) }}" class="rounded-lg border border-gray-300 px-3 py-1.5 text-xs font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-700 dark:text-gray-300" download>
                                    Download
                                </a>
                                @if($documento->metadata['seguranca']['hash_sha256'] ?? null)
                                    <button type="button" data-documento-verificar="{{ route('assinaturas.documento.verificar', $documento->id) }}" class="rounded-lg border border-brand-300 px-3 py-1.5 text-xs font-medium text-brand-600 hover:bg-brand-50 dark:border-brand-700 dark:text-brand-400">
                                        Verificar
                                    </button>
                                @endif
                                @if(!$documento->ativo && ! $osSomenteLeitura)
                                    <button type="button" data-documento-delete="{{ route('assinaturas.documento.delete', $documento->id) }}" class="rounded-lg border border-error-300 px-3 py-1.5 text-xs font-medium text-error-600 hover:bg-error-50 dark:border-error-700 dark:text-error-400">
                                        Excluir
                                    </button>
                                @endif
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
            @endif
        </div>

        <div class="rounded-lg border border-gray-200 bg-white p-6 shadow-theme-sm dark:border-gray-800 dark:bg-gray-800 lg:col-span-2" data-tab-panel="totais">
            <h2 class="mb-4 text-lg font-semibold text-gray-800 dark:text-white/90">Totais</h2>
            <div class="grid grid-cols-1 gap-4 md:grid-cols-3">
                <div>
                    <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300">Desconto global (R$)</label>
                    <input type="number" step="0.01" min="0" name="desconto_global" id="desconto_global_input" value="{{ old('desconto_global', $ordem->desconto_global) }}" @if(!empty($usuarioSujeitoALimiteDesconto) && $limiteDesconto->limite_valor) max="{{ $limiteDesconto->limite_valor }}" @endif class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm text-gray-800 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90" oninput="atualizarTotaisNoPainel()">
                    @if(!empty($usuarioSujeitoALimiteDesconto) && ($limiteDesconto->limite_valor ?? $limiteDesconto->limite_percentual))
                        <p class="mt-1 text-xs text-amber-600 dark:text-amber-400">Limite: @if($limiteDesconto->limite_valor) R$ {{ number_format($limiteDesconto->limite_valor, 2, ',', '.') }} @endif @if($limiteDesconto->limite_valor && $limiteDesconto->limite_percentual) | @endif @if($limiteDesconto->limite_percentual) {{ number_format($limiteDesconto->limite_percentual, 1, ',', '.') }}% @endif</p>
                    @endif
                </div>
                <div>
                    <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300">Acréscimos</label>
                    <input type="number" step="0.01" min="0" name="acrescimos_global" value="{{ old('acrescimos_global', $ordem->acrescimos_global) }}" class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm text-gray-800 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90" oninput="atualizarTotaisNoPainel()">
                </div>
                <div>
                    <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300">Impostos globais</label>
                    <input type="number" step="0.01" min="0" name="impostos_global" value="{{ old('impostos_global', $ordem->impostos_global) }}" class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm text-gray-800 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90" oninput="atualizarTotaisNoPainel()">
                </div>
                <div>
                    <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300">Total produtos</label>
                    <input type="text" id="total_produtos_display" value="{{ number_format($ordem->total_produtos ?? 0, 2, ',', '.') }}" readonly class="w-full rounded-lg border border-gray-300 bg-gray-50 px-4 py-2.5 text-sm text-gray-600 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-400">
                </div>
                <div>
                    <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300">Total serviços</label>
                    <input type="text" id="total_servicos_display" value="{{ number_format($ordem->total_servicos ?? 0, 2, ',', '.') }}" readonly class="w-full rounded-lg border border-gray-300 bg-gray-50 px-4 py-2.5 text-sm text-gray-600 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-400">
                </div>
                <div>
                    <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300">Total descontos</label>
                    <input type="text" id="total_descontos_display" value="R$ {{ number_format($ordem->total_descontos ?? 0, 2, ',', '.') }}" readonly class="w-full rounded-lg border border-gray-300 bg-gray-50 px-4 py-2.5 text-sm text-gray-600 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-400">
                </div>
                <div>
                    <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300">Total geral</label>
                    <input type="text" id="total_geral_display" value="{{ number_format($ordem->total_geral ?? 0, 2, ',', '.') }}" readonly class="w-full rounded-lg border border-gray-300 bg-gray-50 px-4 py-2.5 text-sm text-gray-600 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-400">
                </div>
            </div>
        </div>

        <div class="rounded-lg border border-gray-200 bg-white p-6 shadow-theme-sm dark:border-gray-800 dark:bg-gray-800 lg:col-span-2" data-tab-panel="obs-internas">
            <h2 class="mb-4 text-lg font-semibold text-gray-800 dark:text-white/90">Observações internas</h2>
            <textarea name="observacoes_internas" class="js-jodit w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm text-gray-800 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90">{!! old('observacoes_internas', $ordem->observacoes_internas) !!}</textarea>
        </div>

        @hasPermission('view-os-history')
        @if($isEdit)
        <div class="rounded-lg border border-gray-200 bg-white p-6 shadow-theme-sm dark:border-gray-800 dark:bg-gray-800 lg:col-span-2" data-tab-panel="historico">
            <h2 class="mb-4 text-lg font-semibold text-gray-800 dark:text-white/90">Histórico de Alterações</h2>
            <p class="mb-4 text-sm text-gray-600 dark:text-gray-400">Registro completo de todas as modificações realizadas nesta OS</p>
            <div class="space-y-4">
                @forelse($ordem->logs as $log)
                    <div class="rounded-lg border border-gray-200 bg-gray-50 p-4 dark:border-gray-700 dark:bg-gray-900">
                        <div class="flex items-start justify-between gap-4">
                            <div class="flex-1">
                                <div class="mb-2 flex items-center gap-2">
                                    <span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium 
                                        @if($log->tipo === 'criada') bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-300
                                        @elseif($log->tipo === 'encerrada') bg-emerald-100 text-emerald-800 dark:bg-emerald-900 dark:text-emerald-300
                                        @elseif($log->tipo === 'atualizada') bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-300
                                        @elseif($log->tipo === 'status') bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-300
                                        @elseif($log->tipo === 'anexos') bg-purple-100 text-purple-800 dark:bg-purple-900 dark:text-purple-300
                                        @elseif($log->tipo === 'acesso_show') bg-cyan-100 text-cyan-800 dark:bg-cyan-900 dark:text-cyan-300
                                        @elseif($log->tipo === 'acesso_edit') bg-sky-100 text-sky-800 dark:bg-sky-900 dark:text-sky-300
                                        @elseif($log->tipo === 'conversao_atendimento') bg-indigo-100 text-indigo-800 dark:bg-indigo-900 dark:text-indigo-300
                                        @else bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300
                                        @endif">
                                        @if($log->tipo === 'anexos') Anexos
                                        @elseif($log->tipo === 'acesso_show') Acesso (Visualização)
                                        @elseif($log->tipo === 'acesso_edit') Acesso (Edição)
                                        @elseif($log->tipo === 'conversao_atendimento') Conversão (Atendimento)
                                        @elseif($log->tipo === 'log_sistema_mos' || $log->tipo === 'log_sistema_' . 'm' . 'apo' . 's') M-OS (histórico)
                                        @elseif($log->tipo === 'nota_interna_mos' || $log->tipo === 'nota_interna_' . 'm' . 'apo' . 's') M-OS (nota)
                                        @else {{ ucfirst(str_replace('_', ' ', $log->tipo)) }}
                                        @endif
                                    </span>
                                    <span class="text-sm font-medium text-gray-800 dark:text-white/90">{{ $log->descricao }}</span>
                                </div>
                                
                                @if(($log->tipo === 'log_sistema_mos' || $log->tipo === 'log_sistema_' . 'm' . 'apo' . 's') && $log->dados)
                                    <div class="mt-2 rounded border border-gray-200 bg-white p-3 text-sm text-gray-700 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-200">
                                        @if(!empty($log->dados['usuario']))
                                            <p class="mb-1"><span class="font-medium text-gray-600 dark:text-gray-400">Utilizador:</span> {{ $log->dados['usuario'] }}</p>
                                        @endif
                                        @if(!empty($log->dados['tarefa']))
                                            <p class="whitespace-pre-wrap">{{ $log->dados['tarefa'] }}</p>
                                        @endif
                                        @if(!empty($log->dados['data']) || !empty($log->dados['hora']))
                                            <p class="mt-2 text-xs text-gray-500 dark:text-gray-400">{{ trim(($log->dados['data'] ?? '') . ' ' . ($log->dados['hora'] ?? '')) }}</p>
                                        @endif
                                    </div>
                                @elseif(($log->tipo === 'nota_interna_mos' || $log->tipo === 'nota_interna_' . 'm' . 'apo' . 's') && $log->dados && !empty($log->dados['texto']))
                                    <div class="mt-2 rounded border border-gray-200 bg-white p-3 text-sm whitespace-pre-wrap text-gray-700 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-200">{{ $log->dados['texto'] }}</div>
                                @elseif($log->tipo === 'conversao_atendimento' && $log->dados)
                                    <dl class="mt-3 space-y-1 text-sm">
                                        <div><dt class="text-gray-500 dark:text-gray-400">Atendimento externo</dt><dd class="font-medium">#{{ $log->dados['atendimento_id'] ?? '-' }}</dd></div>
                                        @if(!empty($log->dados['usuario_nome']))
                                        <div><dt class="text-gray-500 dark:text-gray-400">Convertido por</dt><dd class="font-medium">{{ $log->dados['usuario_nome'] }}{{ !empty($log->dados['usuario_email']) ? ' (' . $log->dados['usuario_email'] . ')' : '' }}</dd></div>
                                        @endif
                                        @if(!empty($log->dados['convertido_em']))
                                        <div><dt class="text-gray-500 dark:text-gray-400">Em</dt><dd>{{ \Carbon\Carbon::parse($log->dados['convertido_em'])->format('d/m/Y H:i') }}</dd></div>
                                        @endif
                                    </dl>
                                @elseif($log->dados && isset($log->dados['alteracoes']))
                                    <div class="mt-3 space-y-2">
                                        @foreach($log->dados['alteracoes'] as $campo => $valores)
                                            @php
                                                $nomeCampo = ucfirst(str_replace('_', ' ', $campo));
                                                $tipos = ['manutencao' => 'Manutenção', 'instalacao' => 'Instalação', 'garantia' => 'Garantia', 'visita' => 'Visita técnica', 'projeto' => 'Projeto', 'contrato' => 'Contrato', 'outro' => 'Outro'];
                                                $origens = ['balcao' => 'Balcão', 'telefone' => 'Telefone', 'app' => 'App', 'site' => 'Site', 'indicacao' => 'Indicação', 'contrato' => 'Contrato', 'outro' => 'Outro'];
                                                $prioridades = ['baixa' => 'Baixa', 'normal' => 'Normal', 'alta' => 'Alta', 'critica' => 'Crítica'];
                                                
                                                $formatarValor = function($valor, $campo) use ($ordem, $tipos, $origens, $prioridades) {
                                                    if (is_array($valor)) {
                                                        return implode(', ', $valor) ?: '(nenhum)';
                                                    }
                                                    if ($valor === null || $valor === '') {
                                                        return '(vazio)';
                                                    }
                                                    // Formatação para tipo_os
                                                    if ($campo === 'tipo_os' && isset($tipos[$valor])) {
                                                        return $tipos[$valor];
                                                    }
                                                    // Formatação para origem_os
                                                    if ($campo === 'origem_os' && isset($origens[$valor])) {
                                                        return $origens[$valor];
                                                    }
                                                    // Formatação para prioridade
                                                    if ($campo === 'prioridade' && isset($prioridades[$valor])) {
                                                        return $prioridades[$valor];
                                                    }
                                                    // Formatação para sla_unidade
                                                    if ($campo === 'sla_unidade') {
                                                        $unidades = ['horas' => 'Horas', 'dias' => 'Dias', 'semanas' => 'Semanas', 'meses' => 'Meses'];
                                                        return isset($unidades[$valor]) ? $unidades[$valor] : $valor;
                                                    }
                                                    // Formatação para recorrencia_periodicidade
                                                    if ($campo === 'recorrencia_periodicidade') {
                                                        $periodicidades = ['diaria' => 'Diária', 'semanal' => 'Semanal', 'mensal' => 'Mensal', 'trimestral' => 'Trimestral', 'semestral' => 'Semestral', 'anual' => 'Anual'];
                                                        return isset($periodicidades[$valor]) ? $periodicidades[$valor] : $valor;
                                                    }
                                                    // Formatação para valores monetários
                                                    if (in_array($campo, ['total_geral', 'total_produtos', 'total_servicos', 'total_descontos', 'total_acrescimos', 'total_impostos', 'desconto_global', 'acrescimos_global', 'impostos_global']) && is_numeric($valor)) {
                                                        return 'R$ ' . number_format((float)$valor, 2, ',', '.');
                                                    }
                                                    // Formatação para datas
                                                    if (in_array($campo, ['data_abertura', 'prevista_inicio', 'prevista_conclusao', 'real_inicio', 'real_conclusao', 'aprovado_em']) && $valor) {
                                                        try {
                                                            return \Carbon\Carbon::parse($valor)->format('d/m/Y H:i');
                                                        } catch (\Exception $e) {
                                                            return $valor;
                                                        }
                                                    }
                                                    // Buscar nome de cliente se for cliente_id
                                                    if ($campo === 'cliente_id' && is_numeric($valor)) {
                                                        $cliente = \App\Models\Cliente::find($valor);
                                                        return $cliente ? $cliente->nome . ' (ID: ' . $valor . ')' : 'ID: ' . $valor;
                                                    }
                                                    // Buscar nome de unidade do cliente se for cliente_unidade_id
                                                    if ($campo === 'cliente_unidade_id' && is_numeric($valor)) {
                                                        $unidade = \App\Models\Cliente::find($valor);
                                                        return $unidade ? $unidade->nome . ' (ID: ' . $valor . ')' : 'ID: ' . $valor;
                                                    }
                                                    // Buscar nome de contato se for contato_id
                                                    if ($campo === 'contato_id' && is_numeric($valor)) {
                                                        $contato = \App\Models\Contato::find($valor);
                                                        return $contato ? $contato->nome . ' (ID: ' . $valor . ')' : 'ID: ' . $valor;
                                                    }
                                                    // Buscar nome de endereço se for endereco_id
                                                    if ($campo === 'endereco_id' && is_numeric($valor)) {
                                                        $endereco = \App\Models\Endereco::find($valor);
                                                        if ($endereco) {
                                                            $enderecoTexto = trim(($endereco->logradouro ?? '') . ', ' . ($endereco->numero ?? '') . ' - ' . ($endereco->bairro ?? '') . ', ' . ($endereco->cidade ?? '') . '/' . ($endereco->estado ?? ''));
                                                            return $enderecoTexto . ' (ID: ' . $valor . ')';
                                                        }
                                                        return 'ID: ' . $valor;
                                                    }
                                                    // Buscar nome de equipamento se for equipamento_id
                                                    if ($campo === 'equipamento_id' && is_numeric($valor)) {
                                                        $equipamento = \App\Models\Equipamento::find($valor);
                                                        if ($equipamento) {
                                                            $nomeEquipamento = trim(($equipamento->marca ?? '') . ' ' . ($equipamento->modelo ?? '') . ' ' . ($equipamento->numero_serie ?? ''));
                                                            return ($nomeEquipamento ?: 'Equipamento #' . $valor) . ' (ID: ' . $valor . ')';
                                                        }
                                                        return 'ID: ' . $valor;
                                                    }
                                                    // Buscar nome de termo de garantia se for termo_garantia_id
                                                    if ($campo === 'termo_garantia_id' && is_numeric($valor)) {
                                                        $termoGarantia = \App\Models\TermoGarantia::find($valor);
                                                        return $termoGarantia ? $termoGarantia->nome . ' (ID: ' . $valor . ')' : 'ID: ' . $valor;
                                                    }
                                                    // Formatação para números (quilometragem, horímetro, sla_valor)
                                                    if (in_array($campo, ['quilometragem_entrada', 'quilometragem_saida', 'horimetro_entrada', 'horimetro_saida', 'sla_valor']) && is_numeric($valor)) {
                                                        return number_format((float)$valor, 0, ',', '.');
                                                    }
                                                    // Formatação para boolean
                                                    if (is_bool($valor)) {
                                                        return $valor ? 'Sim' : 'Não';
                                                    }
                                                    // Para campos HTML/texto longo, remover tags e limitar exibição
                                                    if (in_array($campo, ['relato_cliente', 'diagnostico_tecnico', 'servicos_realizados', 'observacoes_internas', 'observacoes_cliente', 'equipamento_acessorios']) && is_string($valor)) {
                                                        $textoLimpo = strip_tags($valor);
                                                        $textoLimpo = html_entity_decode($textoLimpo, ENT_QUOTES, 'UTF-8');
                                                        $textoLimpo = trim(preg_replace('/\s+/', ' ', $textoLimpo));
                                                        if (strlen($textoLimpo) > 150) {
                                                            return substr($textoLimpo, 0, 150) . '...';
                                                        }
                                                        return $textoLimpo ?: '(vazio)';
                                                    }
                                                    return $valor;
                                                };
                                            @endphp
                                            <div class="rounded border border-gray-200 bg-white p-2 text-sm dark:border-gray-700 dark:bg-gray-800">
                                                <div class="font-medium text-gray-700 dark:text-gray-300">
                                                    {{ $nomeCampo }}:
                                                </div>
                                                <div class="mt-1 flex flex-wrap items-center gap-2">
                                                    <span class="text-red-600 dark:text-red-400">
                                                        <span class="font-medium">Antes:</span> 
                                                        {{ $formatarValor($valores['antes'], $campo) }}
                                                    </span>
                                                    <span class="text-gray-400">→</span>
                                                    <span class="text-green-600 dark:text-green-400">
                                                        <span class="font-medium">Depois:</span> 
                                                        {{ $formatarValor($valores['depois'], $campo) }}
                                                    </span>
                                                </div>
                                            </div>
                                        @endforeach
                                    </div>
                                @elseif($log->tipo === 'anexos' && $log->dados)
                                    <div class="mt-3 rounded border border-gray-200 bg-white p-2 text-sm dark:border-gray-700 dark:bg-gray-800">
                                        @if(isset($log->dados['antes']) && isset($log->dados['depois']))
                                            <div class="space-y-2">
                                                @foreach($log->dados['antes'] as $campo => $valorAntes)
                                                    @if(isset($log->dados['depois'][$campo]) && $log->dados['depois'][$campo] != $valorAntes)
                                                        <div class="rounded border border-gray-200 bg-white p-2 text-sm dark:border-gray-700 dark:bg-gray-800">
                                                            <div class="font-medium text-gray-700 dark:text-gray-300">
                                                                {{ ucfirst(str_replace('_', ' ', $campo)) }}:
                                                            </div>
                                                            <div class="mt-1 flex items-center gap-2">
                                                                <span class="text-red-600 dark:text-red-400">
                                                                    <span class="font-medium">Antes:</span> {{ $valorAntes ?? '(vazio)' }}
                                                                </span>
                                                                <span class="text-gray-400">→</span>
                                                                <span class="text-green-600 dark:text-green-400">
                                                                    <span class="font-medium">Depois:</span> {{ $log->dados['depois'][$campo] ?? '(vazio)' }}
                                                                </span>
                                                            </div>
                                                        </div>
                                                    @endif
                                                @endforeach
                                            </div>
                                        @elseif(isset($log->dados['arquivos']) && is_array($log->dados['arquivos']))
                                            <div class="font-medium text-gray-700 dark:text-gray-300">{{ $log->dados['quantidade'] ?? count($log->dados['arquivos']) }} arquivo(s) adicionado(s):</div>
                                            <ul class="mt-1 list-inside list-disc text-gray-600 dark:text-gray-400">
                                                @foreach($log->dados['arquivos'] as $arq)
                                                    <li>{{ $arq }}</li>
                                                @endforeach
                                            </ul>
                                        @elseif(isset($log->dados['arquivo']))
                                            <div class="font-medium text-gray-700 dark:text-gray-300">Arquivo excluído:</div>
                                            <div class="mt-1 text-gray-600 dark:text-gray-400">{{ $log->dados['arquivo'] }}</div>
                                        @endif
                                    </div>
                                @elseif($log->tipo === 'encerrada' && $log->dados)
                                    <div class="mt-3 space-y-3 text-sm">
                                        <div class="rounded border border-gray-200 bg-white p-3 dark:border-gray-700 dark:bg-gray-800">
                                            <div class="font-medium text-gray-700 dark:text-gray-300 mb-2">Resumo do encerramento</div>
                                            <dl class="grid grid-cols-1 gap-1.5 sm:grid-cols-2">
                                                @if(isset($log->dados['eh_garantia_ou_contrato']) && $log->dados['eh_garantia_ou_contrato'])
                                                    <div><dt class="text-gray-500 dark:text-gray-400">Cobertura:</dt><dd class="font-medium">Garantia/Contrato (valor R$ 0,00)</dd></div>
                                                @else
                                                    @if(isset($log->dados['valor_total_formatado']))<div><dt class="text-gray-500 dark:text-gray-400">Valor total:</dt><dd>{{ $log->dados['valor_total_formatado'] }}</dd></div>@endif
                                                    @if(isset($log->dados['desconto_formatado']))<div><dt class="text-gray-500 dark:text-gray-400">Desconto:</dt><dd>{{ $log->dados['desconto_formatado'] }} ({{ $log->dados['desconto_tipo'] ?? 'valor' }})</dd></div>@endif
                                                    @if(isset($log->dados['valor_a_receber_formatado']))<div><dt class="text-gray-500 dark:text-gray-400">Valor a receber:</dt><dd class="font-medium">{{ $log->dados['valor_a_receber_formatado'] }}</dd></div>@endif
                                                @endif
                                                @if(isset($log->dados['tipo_pagamento']))<div><dt class="text-gray-500 dark:text-gray-400">Tipo pagamento:</dt><dd>{{ $log->dados['tipo_pagamento'] === 'agora' ? 'Pagamento agora' : 'Faturado/Fiado' }}</dd></div>@endif
                                                @if(isset($log->dados['foi_pago']))<div><dt class="text-gray-500 dark:text-gray-400">Pago:</dt><dd>{{ $log->dados['foi_pago'] ? 'Sim' : 'Não' }}</dd></div>@endif
                                                @if(isset($log->dados['contas_criadas']))<div><dt class="text-gray-500 dark:text-gray-400">Contas a receber criadas:</dt><dd>{{ $log->dados['contas_criadas'] }}</dd></div>@endif
                                                @if(isset($log->dados['real_conclusao']))<div><dt class="text-gray-500 dark:text-gray-400">Data/hora conclusão:</dt><dd>{{ $log->dados['real_conclusao'] }}</dd></div>@endif
                                                @if(isset($log->dados['imprimir_comprovante']))<div><dt class="text-gray-500 dark:text-gray-400">Imprimir comprovante:</dt><dd>{{ $log->dados['imprimir_comprovante'] ? 'Sim' : 'Não' }}</dd></div>@endif
                                            </dl>
                                        </div>
                                        @if(!empty($log->dados['lancamentos']))
                                            <div class="rounded border border-gray-200 bg-white p-3 dark:border-gray-700 dark:bg-gray-800">
                                                <div class="font-medium text-gray-700 dark:text-gray-300 mb-2">Lançamentos (Contas a Receber)</div>
                                                <ul class="space-y-2">
                                                    @foreach($log->dados['lancamentos'] as $idx => $lanc)
                                                        <li class="rounded border border-gray-100 bg-gray-50 p-2 dark:border-gray-600 dark:bg-gray-900/50">
                                                            <div class="flex items-baseline justify-between gap-2">
                                                                <span class="font-medium">{{ $lanc['descricao'] ?? 'Lançamento ' . ($idx + 1) }}</span>
                                                                <span>{{ $lanc['valor_formatado'] ?? 'R$ ' . number_format($lanc['valor'] ?? 0, 2, ',', '.') }}</span>
                                                            </div>
                                                            @if(!empty($lanc['forma_pagamento']))<div class="text-gray-600 dark:text-gray-400">Forma: {{ $lanc['forma_pagamento'] }}</div>@endif
                                                            @if(!empty($lanc['adquirente']) || !empty($lanc['bandeira']))<div class="text-gray-600 dark:text-gray-400">Adquirente: {{ $lanc['adquirente'] ?? '-' }} | Bandeira: {{ $lanc['bandeira'] ?? '-' }}</div>@endif
                                                            @if(!empty($lanc['data_vencimento']))<div class="text-gray-600 dark:text-gray-400">Vencimento: {{ $lanc['data_vencimento'] }} | Status: {{ $lanc['status'] ?? '-' }}</div>@endif
                                                            @if(!empty($lanc['observacoes']))<div class="mt-1.5 text-xs text-gray-600 dark:text-gray-400 whitespace-pre-wrap">{{ $lanc['observacoes'] }}</div>@endif
                                                        </li>
                                                    @endforeach
                                                </ul>
                                            </div>
                                        @endif
                                    </div>
                                @elseif($log->dados && isset($log->dados['tags_antes']) && isset($log->dados['tags_depois']))
                                    <div class="mt-3 rounded border border-gray-200 bg-white p-2 text-sm dark:border-gray-700 dark:bg-gray-800">
                                        <div class="font-medium text-gray-700 dark:text-gray-300">Tags:</div>
                                        <div class="mt-1 flex flex-wrap items-center gap-2">
                                            <span class="text-red-600 dark:text-red-400">
                                                <span class="font-medium">Antes:</span> 
                                                {{ !empty($log->dados['tags_antes']) ? implode(', ', $log->dados['tags_antes']) : '(nenhuma)' }}
                                            </span>
                                            <span class="text-gray-400">→</span>
                                            <span class="text-green-600 dark:text-green-400">
                                                <span class="font-medium">Depois:</span> 
                                                {{ !empty($log->dados['tags_depois']) ? implode(', ', $log->dados['tags_depois']) : '(nenhuma)' }}
                                            </span>
                                        </div>
                                    </div>
                                @elseif($log->dados && isset($log->dados['antes']) && isset($log->dados['depois']))
                                    <div class="mt-3 space-y-2">
                                        @foreach($log->dados['antes'] as $campo => $valorAntes)
                                            @if(isset($log->dados['depois'][$campo]) && $log->dados['depois'][$campo] != $valorAntes)
                                                <div class="rounded border border-gray-200 bg-white p-2 text-sm dark:border-gray-700 dark:bg-gray-800">
                                                    <div class="font-medium text-gray-700 dark:text-gray-300">
                                                        {{ ucfirst(str_replace('_', ' ', $campo)) }}:
                                                    </div>
                                                    <div class="mt-1 flex items-center gap-2">
                                                        <span class="text-red-600 dark:text-red-400">
                                                            <span class="font-medium">Antes:</span> {{ $valorAntes ?? '(vazio)' }}
                                                        </span>
                                                        <span class="text-gray-400">→</span>
                                                        <span class="text-green-600 dark:text-green-400">
                                                            <span class="font-medium">Depois:</span> {{ $log->dados['depois'][$campo] ?? '(vazio)' }}
                                                        </span>
                                                    </div>
                                                </div>
                                            @endif
                                        @endforeach
                                    </div>
                                @endif
                            </div>
                            <div class="flex flex-col items-end gap-1 text-xs text-gray-500 dark:text-gray-400">
                                <div class="font-medium">{{ $log->user->name ?? 'Sistema' }}</div>
                                <div>{{ $log->created_at->format('d/m/Y H:i:s') }}</div>
                                @if($log->ip)
                                    <div class="text-xs">IP: {{ $log->ip }}</div>
                                @endif
                            </div>
                        </div>
                    </div>
                @empty
                    <div class="rounded-lg border border-gray-200 bg-gray-50 p-8 text-center dark:border-gray-700 dark:bg-gray-900">
                        <p class="text-gray-500 dark:text-gray-400">Nenhuma alteração registrada ainda</p>
                    </div>
                @endforelse
            </div>
        </div>
        @endif
        @endhasPermission

        @if(function_exists('do_action'))
        <?php do_action('ordens_servico.form.panels.after', $ordem ?? null, $isEdit); ?>
        @endif
    </div>

    <div class="mt-6 flex justify-end gap-3">
        @if(function_exists('do_action'))
        <?php do_action('ordens_servico.form.extra', $ordem ?? null); ?>
        @endif
        @if(! $osSomenteLeitura)
            <button type="submit" class="rounded-lg bg-brand-500 px-6 py-2.5 text-sm font-medium text-white hover:bg-brand-600">Salvar</button>
        @endif
        <a href="{{ route('ordens-servico.index') }}" class="rounded-lg border border-gray-300 bg-white px-6 py-2.5 text-sm font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-300">Cancelar</a>
    </div>
</form>

@if(function_exists('do_action'))
<?php do_action('ordens_servico.form.scripts', $ordem ?? null, $isEdit); ?>
@endif

@if($isEdit)
{{-- Modal carrossel de imagens --}}
<div id="anexosCarouselModal" class="fixed inset-0 z-[100] hidden items-center justify-center bg-black/90 p-4" role="dialog" aria-modal="true" aria-label="Visualizar imagem" style="display: none;">
    <div class="relative max-h-[90vh] max-w-4xl flex flex-col items-center" onclick="event.stopPropagation()">
        <button type="button" id="anexosCarouselFechar" class="absolute -right-2 -top-2 z-20 rounded-full bg-white/90 p-2 text-gray-800 hover:bg-white" aria-label="Fechar">&times;</button>
        <button type="button" id="anexosCarouselPrev" class="absolute left-2 top-1/2 -translate-y-1/2 z-20 rounded-full bg-white/90 p-2 text-gray-800 hover:bg-white" aria-label="Anterior">&#10094;</button>
        <img id="anexosCarouselImg" src="data:image/gif;base64,R0lGODlhAQABAIAAAAAAAP///yH5BAEAAAAALAAAAAABAAEAAAIBRAA7" alt="" class="max-h-[85vh] max-w-full rounded-lg object-contain bg-black/50">
        <button type="button" id="anexosCarouselNext" class="absolute right-2 top-1/2 -translate-y-1/2 z-20 rounded-full bg-white/90 p-2 text-gray-800 hover:bg-white" aria-label="Próximo">&#10095;</button>
        <p id="anexosCarouselLegenda" class="mt-2 text-center text-sm text-white"></p>
        <p id="anexosCarouselContador" class="text-center text-xs text-white/80"></p>
    </div>
</div>
@endif

@if($osSomenteLeitura)
<script>
// Desabilitar todos os campos do formulário quando a OS está encerrada
document.addEventListener('DOMContentLoaded', function() {
    const form = document.getElementById('osForm');
    if (form) {
        const inputs = form.querySelectorAll('input, select, textarea, button[type="submit"]');
        inputs.forEach(input => {
            if (input.type !== 'button' && input.name !== '_token' && input.name !== '_method') {
                input.disabled = true;
                input.readOnly = true;
            }
        });
        
        // Desabilitar botões de adicionar itens
        const addButtons = form.querySelectorAll('button[onclick*="adicionar"]');
        addButtons.forEach(btn => btn.disabled = true);
    }
});
</script>
@endif

<div id="toastContainer" class="fixed right-4 top-4 z-[60] space-y-2"></div>

<div id="equipeAjudaModal" class="fixed inset-0 z-[60] hidden">
    <div class="absolute inset-0 bg-gray-900/50" data-modal-close="equipeAjudaModal"></div>
    <div class="relative mx-auto mt-16 w-full max-w-2xl rounded-lg border border-gray-200 bg-white p-6 shadow-lg dark:border-gray-700 dark:bg-gray-800">
        <div class="mb-4 flex items-center justify-between">
            <h3 class="text-lg font-semibold text-gray-800 dark:text-white/90">Como usar: Técnicos e Apontamentos</h3>
            <button type="button" class="text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200" data-modal-close="equipeAjudaModal">✕</button>
        </div>
        <div class="space-y-4 text-sm text-gray-700 dark:text-gray-300">
            <div>
                <p class="font-medium text-gray-800 dark:text-gray-200">1) Técnicos (alocação)</p>
                <p>Use para definir <span class="font-medium">quem</span> vai atuar na OS e o período previsto/real do técnico.</p>
                <ol class="mt-2 list-decimal space-y-1 pl-5">
                    <li>Clique em <span class="font-medium">+ Técnico</span>.</li>
                    <li>Escolha o técnico.</li>
                    <li>Informe status, início, fim e horas trabalhadas.</li>
                </ol>
            </div>
            <div>
                <p class="font-medium text-gray-800 dark:text-gray-200">2) Apontamentos (registro do trabalho)</p>
                <p>Use para registrar o <span class="font-medium">que foi feito</span> e <span class="font-medium">quando</span>, com pausas e observações.</p>
                <ol class="mt-2 list-decimal space-y-1 pl-5">
                    <li>Clique em <span class="font-medium">+ Apontamento</span>.</li>
                    <li>Escolha o técnico responsável.</li>
                    <li>Informe início, fim, pausas e status.</li>
                    <li>Descreva detalhes no campo de observações.</li>
                </ol>
            </div>
            <div>
                <p class="font-medium text-gray-800 dark:text-gray-200">Exemplo prático</p>
                <p>OS com instalação:</p>
                <ul class="mt-2 list-disc space-y-1 pl-5">
                    <li><span class="font-medium">Técnicos:</span> João (08:00–12:00), Maria (13:00–17:00).</li>
                    <li><span class="font-medium">Apontamentos:</span> 08:15 início, 10:00 pausa 15min, 11:50 conclusão parcial; 13:10 retorno, 16:40 finalizado.</li>
                </ul>
            </div>
            <div>
                <p class="font-medium text-gray-800 dark:text-gray-200">Dica</p>
                <p>Use Técnicos para planejar/alocar e Apontamentos para auditar o que ocorreu de fato.</p>
            </div>
        </div>
        <div class="mt-6 flex justify-end">
            <button type="button" class="rounded-lg border border-gray-300 px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-700 dark:text-gray-300" data-modal-close="equipeAjudaModal">Fechar</button>
        </div>
    </div>
</div>

<div id="slaAjudaModal" class="fixed inset-0 z-[60] hidden">
    <div class="absolute inset-0 bg-gray-900/50" data-modal-close="slaAjudaModal"></div>
    <div class="relative mx-auto mt-10 max-h-[calc(100vh-5rem)] w-full max-w-3xl overflow-y-auto rounded-lg border border-gray-200 bg-white p-6 shadow-lg dark:border-gray-700 dark:bg-gray-800">
        <div class="mb-4 flex items-center justify-between">
            <h3 class="text-lg font-semibold text-gray-800 dark:text-white/90">Como usar: SLA e execução</h3>
            <button type="button" class="text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200" data-modal-close="slaAjudaModal">✕</button>
        </div>
        <div class="space-y-4 text-sm text-gray-700 dark:text-gray-300">
            <div>
                <p class="font-medium text-gray-800 dark:text-gray-200">O que é</p>
                <p>O SLA define o prazo máximo para atendimento/conclusão. Use estes campos para planejar e registrar o andamento real.</p>
            </div>
            <div>
                <p class="font-medium text-gray-800 dark:text-gray-200">Passo a passo</p>
                <ol class="mt-2 list-decimal space-y-1 pl-5">
                    <li>Informe <span class="font-medium">Prevista início</span> e <span class="font-medium">Prevista conclusão</span>.</li>
                    <li>Defina o <span class="font-medium">SLA</span> (valor) e a <span class="font-medium">Unidade</span> (horas/dias).</li>
                    <li>Quando houver execução, preencha <span class="font-medium">Início real</span> e <span class="font-medium">Conclusão real</span>.</li>
                </ol>
            </div>
            <div>
                <p class="font-medium text-gray-800 dark:text-gray-200">Exemplo prático</p>
                <p>Atendimento em até 48 horas:</p>
                <ul class="mt-2 list-disc space-y-1 pl-5">
                    <li>SLA: <span class="font-medium">48</span> e Unidade: <span class="font-medium">Horas</span>.</li>
                    <li>Prevista início: 24/01 08:00, Prevista conclusão: 26/01 08:00.</li>
                    <li>Início real: 24/01 09:10, Conclusão real: 25/01 17:30.</li>
                </ul>
                <p class="mt-4 font-medium text-gray-800 dark:text-gray-200">Legendas de SLA na listagem de OS</p>
                <p class="mt-1">Na coluna <span class="font-medium">Status</span> da lista de ordens de serviço aparece, abaixo do status da OS, um <span class="font-medium">selo extra</span> só sobre SLA. Cada texto significa o seguinte:</p>
                <ul class="mt-2 list-disc space-y-1.5 pl-5">
                    <li><span class="font-medium">Sem SLA</span> — não há valor de SLA preenchido nesta OS; nada é calculado.</li>
                    <li><span class="font-medium">SLA definido (aguardando conclusão)</span> — o SLA (valor e unidade) está preenchido, mas o sistema ainda <span class="font-medium">não pode concluir</span> se foi cumprido ou não. Em geral falta a <span class="font-medium">data real de conclusão</span> e/ou a OS ainda não está em situação em que as duas datas (prevista e real de conclusão) existam para comparar.</li>
                    <li><span class="font-medium">SLA: Cumprido</span> — já dá para avaliar: a <span class="font-medium">conclusão real</span> ocorreu <span class="font-medium">até</span> a <span class="font-medium">data prevista de conclusão</span>, ou o indicador <span class="font-medium">SLA cumprido</span> da OS foi marcado como sim.</li>
                    <li><span class="font-medium">SLA: Não cumprido</span> — já dá para avaliar: a conclusão real ficou <span class="font-medium">depois</span> da prevista, ou <span class="font-medium">SLA cumprido</span> foi marcado como não.</li>
                    <li><span class="font-medium">SLA sem conclusão registrada</span> — a OS já está em <span class="font-medium">status finalizado</span> (ex.: Concluída ou Encerrada), há SLA numérico, mas <span class="font-medium">falta preencher</span> a data prevista e/ou a data real de conclusão; por isso não aparece “Cumprido” nem “Não cumprido”. Ajuste os campos na aba <span class="font-medium">SLA e execução</span>.</li>
                </ul>
                <p class="mt-3 rounded-md bg-gray-50 px-3 py-2 text-xs text-gray-600 dark:bg-gray-900/50 dark:text-gray-400">No exemplo das 48 horas acima: com <span class="font-medium">Prevista conclusão</span> e <span class="font-medium">Conclusão real</span> preenchidas, a lista tende a mostrar <span class="font-medium">SLA: Cumprido</span> ou <span class="font-medium">SLA: Não cumprido</span>. Antes da conclusão real, aparece <span class="font-medium">SLA definido (aguardando conclusão)</span>.</p>
            </div>
        </div>
        <div class="mt-6 flex justify-end">
            <button type="button" class="rounded-lg border border-gray-300 px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-700 dark:text-gray-300" data-modal-close="slaAjudaModal">Fechar</button>
        </div>
    </div>
</div>

<div id="garantiaAjudaModal" class="fixed inset-0 z-[60] hidden">
    <div class="absolute inset-0 bg-gray-900/50" data-modal-close="garantiaAjudaModal"></div>
    <div class="relative mx-auto mt-16 w-full max-w-2xl rounded-lg border border-gray-200 bg-white p-6 shadow-lg dark:border-gray-700 dark:bg-gray-800">
        <div class="mb-4 flex items-center justify-between">
            <h3 class="text-lg font-semibold text-gray-800 dark:text-white/90">Como usar: Garantia e contrato</h3>
            <button type="button" class="text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200" data-modal-close="garantiaAjudaModal">✕</button>
        </div>
        <div class="space-y-4 text-sm text-gray-700 dark:text-gray-300">
            <div>
                <p class="font-medium text-gray-800 dark:text-gray-200">O que é</p>
                <p>Use para indicar se a OS está dentro/fora da garantia e para registrar dados do contrato relacionado. O campo <span class="font-medium">Coberto por contrato</span> indica quando o valor não será cobrado do cliente, pois está coberto por um contrato.</p>
            </div>
            <div>
                <p class="font-medium text-gray-800 dark:text-gray-200">Passo a passo</p>
                <ol class="mt-2 list-decimal space-y-1 pl-5">
                    <li>Selecione o tipo de <span class="font-medium">Garantia</span> (dentro/fora/estendida).</li>
                    <li>Informe <span class="font-medium">Número do contrato</span> e <span class="font-medium">Tipo do contrato</span>, se existir.</li>
                    <li>Marque <span class="font-medium">OS recorrente</span> quando houver periodicidade.</li>
                    <li>Preencha a <span class="font-medium">Periodicidade</span> (ex.: mensal, trimestral).</li>
                    <li>Marque <span class="font-medium">Coberto por contrato</span> quando o valor não será cobrado do cliente, pois está coberto por um contrato.</li>
                </ol>
            </div>
            <div>
                <p class="font-medium text-gray-800 dark:text-gray-200">Exemplo prático</p>
                <p>Contrato mensal:</p>
                <ul class="mt-2 list-disc space-y-1 pl-5">
                    <li>Garantia: <span class="font-medium">Dentro de garantia</span>.</li>
                    <li>Número do contrato: <span class="font-medium">CT-2026-001</span>.</li>
                    <li>Tipo: <span class="font-medium">Manutenção preventiva</span>.</li>
                    <li>OS recorrente: <span class="font-medium">Sim</span>, Periodicidade: <span class="font-medium">Mensal</span>.</li>
                    <li>Coberto por contrato: <span class="font-medium">Sim</span> (indica que o cliente não será cobrado, pois está coberto pelo contrato).</li>
                </ul>
            </div>
        </div>
        <div class="mt-6 flex justify-end">
            <button type="button" class="rounded-lg border border-gray-300 px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-700 dark:text-gray-300" data-modal-close="garantiaAjudaModal">Fechar</button>
        </div>
    </div>
</div>

<div id="produtoAvulsoModal" class="fixed inset-0 z-[60] hidden">
    <div class="absolute inset-0 bg-gray-900/50" data-modal-close="produtoAvulsoModal"></div>
    <div class="relative mx-auto mt-16 w-full max-w-xl rounded-lg border border-gray-200 bg-white p-6 shadow-lg dark:border-gray-700 dark:bg-gray-800">
        <div class="mb-4 flex items-center justify-between">
            <h3 class="text-lg font-semibold text-gray-800 dark:text-white/90">Produto avulso</h3>
            <button type="button" class="text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200" data-modal-close="produtoAvulsoModal">✕</button>
        </div>
        <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
            <div class="md:col-span-2">
                <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300">Descrição <span class="text-error-500">*</span></label>
                <input type="text" id="produtoAvulsoDescricao" class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm text-gray-800 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90">
            </div>
            <div>
                <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300">Quantidade</label>
                <input type="number" step="0.001" min="0" id="produtoAvulsoQuantidade" value="1" class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm text-gray-800 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90">
            </div>
            <div>
                <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300">Valor unitário <span class="text-error-500">*</span></label>
                <input type="number" step="0.01" min="0" id="produtoAvulsoValor" class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm text-gray-800 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90">
            </div>
            <div>
                <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300">Tipo de desconto</label>
                <select id="produtoAvulsoDescontoTipo" class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm text-gray-800 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90">
                    <option value="valor" selected>R$</option>
                    <option value="percentual">%</option>
                </select>
            </div>
            <div class="desconto-item-cell">
                <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300">Desconto</label>
                <input type="number" step="0.01" min="0" id="produtoAvulsoDescontoValor" class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm text-gray-800 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90">
                <p id="produtoAvulsoDescontoAviso" class="desconto-item-aviso mt-1 hidden text-xs text-error-600 dark:text-error-400" role="alert"></p>
            </div>
        </div>
        <div class="mt-6 flex justify-end gap-2">
            <button type="button" class="rounded-lg border border-gray-300 px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-700 dark:text-gray-300" data-modal-close="produtoAvulsoModal">Cancelar</button>
            <button type="button" id="produtoAvulsoSalvar" class="rounded-lg bg-brand-500 px-4 py-2 text-sm font-medium text-white hover:bg-brand-600">Adicionar</button>
        </div>
    </div>
</div>

<div id="servicoAvulsoModal" class="fixed inset-0 z-[60] hidden">
    <div class="absolute inset-0 bg-gray-900/50" data-modal-close="servicoAvulsoModal"></div>
    <div class="relative mx-auto mt-16 w-full max-w-xl rounded-lg border border-gray-200 bg-white p-6 shadow-lg dark:border-gray-700 dark:bg-gray-800">
        <div class="mb-4 flex items-center justify-between">
            <h3 class="text-lg font-semibold text-gray-800 dark:text-white/90">Serviço avulso</h3>
            <button type="button" class="text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200" data-modal-close="servicoAvulsoModal">✕</button>
        </div>
        <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
            <div class="md:col-span-2">
                <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300">Descrição <span class="text-error-500">*</span></label>
                <input type="text" id="servicoAvulsoDescricao" class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm text-gray-800 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90">
            </div>
            <div>
                <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300">Cobrança por horas</label>
                <div class="flex items-center gap-2 rounded-lg border border-gray-300 px-4 py-2.5 text-sm text-gray-700 dark:border-gray-700 dark:text-gray-300">
                    <input type="checkbox" id="servicoAvulsoCobrarHoras" class="h-4 w-4">
                    <span>Horas</span>
                </div>
            </div>
            <div id="servicoAvulsoQuantidadeWrap">
                <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300">Quantidade</label>
                <input type="number" step="0.001" min="0" id="servicoAvulsoQuantidade" value="1" class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm text-gray-800 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90">
            </div>
            <div id="servicoAvulsoHorasWrap" class="hidden">
                <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300">Horas</label>
                <input type="number" step="0.001" min="0" id="servicoAvulsoHoras" value="1" class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm text-gray-800 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90">
            </div>
            <div>
                <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300">Valor unitário <span class="text-error-500">*</span></label>
                <input type="number" step="0.01" min="0" id="servicoAvulsoValor" class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm text-gray-800 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90">
            </div>
            <div>
                <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300">Técnico</label>
                <select id="servicoAvulsoTecnico" class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm text-gray-800 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90">
                    <option value="">Selecione...</option>
                    @foreach($tecnicos as $tecnico)
                        <option value="{{ $tecnico->id }}">{{ $tecnico->name }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300">Tipo de desconto</label>
                <select id="servicoAvulsoDescontoTipo" class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm text-gray-800 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90">
                    <option value="valor" selected>R$</option>
                    <option value="percentual">%</option>
                </select>
            </div>
            <div class="desconto-item-cell">
                <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300">Desconto</label>
                <input type="number" step="0.01" min="0" id="servicoAvulsoDescontoValor" class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm text-gray-800 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90">
                <p id="servicoAvulsoDescontoAviso" class="desconto-item-aviso mt-1 hidden text-xs text-error-600 dark:text-error-400" role="alert"></p>
            </div>
        </div>
        <div class="mt-6 flex justify-end gap-2">
            <button type="button" class="rounded-lg border border-gray-300 px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-700 dark:text-gray-300" data-modal-close="servicoAvulsoModal">Cancelar</button>
            <button type="button" id="servicoAvulsoSalvar" class="rounded-lg bg-brand-500 px-4 py-2 text-sm font-medium text-white hover:bg-brand-600">Adicionar</button>
        </div>
    </div>
</div>
<div id="produtoImagemModal" class="fixed inset-0 z-[70] hidden">
    <div class="absolute inset-0 bg-gray-900/70" data-produto-imagem-close></div>
    <div class="relative mx-auto mt-12 w-full max-w-4xl px-4">
        <div class="rounded-lg bg-white p-4 shadow-lg dark:bg-gray-800">
            <div class="mb-3 flex items-center justify-between">
                <h3 class="text-sm font-semibold text-gray-700 dark:text-gray-200">Imagens do produto</h3>
                <button type="button" class="text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200" data-produto-imagem-close>✕</button>
            </div>
            <div class="relative flex items-center justify-center">
                <button type="button" id="produtoImagemPrev" class="absolute left-2 rounded-full bg-gray-100 px-3 py-2 text-sm text-gray-700 shadow hover:bg-gray-200 dark:bg-gray-700 dark:text-gray-200">‹</button>
                <img id="produtoImagemAtual" src="data:image/gif;base64,R0lGODlhAQABAIAAAAAAAP///yH5BAEAAAAALAAAAAABAAEAAAIBRAA7" alt="Imagem do produto" class="max-h-[70vh] w-auto rounded object-contain">
                <button type="button" id="produtoImagemNext" class="absolute right-2 rounded-full bg-gray-100 px-3 py-2 text-sm text-gray-700 shadow hover:bg-gray-200 dark:bg-gray-700 dark:text-gray-200">›</button>
            </div>
            <div id="produtoImagemThumbs" class="mt-4 flex flex-wrap justify-center gap-2"></div>
        </div>
    </div>
</div>

@include('partials.jodit-assets')
<script>
let produtoIndex = {{ count($produtosItens) }};
let servicoIndex = {{ count($servicosItens) }};
let tecnicoIndex = {{ count($tecnicosItens) }};
let apontamentoIndex = {{ count($apontamentos) }};
let anexoIndex = 0;
// Bloquear alteração de preço: quando ativo, perfis sujeitos não podem alterar preço de itens do cadastro
window.bloquearAlteracaoPreco = {{ json_encode(!empty($usuarioSujeitoALimiteDesconto) && ($limiteDesconto->bloquear_alteracao_preco ?? false)) }};
window.usuarioSujeitoALimiteDesconto = {{ json_encode(!empty($usuarioSujeitoALimiteDesconto)) }};

function removerLinha(btn) {
    const wrapper = btn.closest('[data-produto-index],[data-servico-index],.grid');
    if (wrapper) wrapper.remove();
}

function adicionarProduto() {
    const container = document.getElementById('produtos_container');
    const html = `
        <div class="rounded-lg border border-gray-200 p-4 dark:border-gray-700" data-produto-index="${produtoIndex}">
            <div class="grid grid-cols-1 gap-3 md:grid-cols-7">
                <div class="md:col-span-2">
                    <label class="mb-1 block text-xs text-gray-600 dark:text-gray-400">Produto</label>
                    <select name="produtos[${produtoIndex}][produto_id]" class="produto-select w-full rounded-lg border border-gray-300 px-3 py-2 text-sm text-gray-800 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90">
                        <option value="">Selecione...</option>
                        @foreach($produtos as $produto)
                            @php
                                $tabelasProduto = $produto->tabelasPrecos ?? collect();
                                $precoVarejo = $tabelasProduto->first(fn($t) => strcasecmp($t->nome, 'varejo') === 0)?->pivot?->preco ?? $produto->preco_venda;
                                $precoEmpresa = $tabelasProduto->first(fn($t) => strcasecmp($t->nome, 'empresa') === 0)?->pivot?->preco ?? $produto->preco_venda;
                                $precoUrgencia = $tabelasProduto->first(fn($t) => strcasecmp($t->nome, 'urgencia') === 0 || strcasecmp($t->nome, 'urgência') === 0)?->pivot?->preco ?? $produto->preco_venda;
                                $imagensProduto = ($produto->imagens ?? collect())
                                    ->sortBy('ordem')
                                    ->map(function ($imagem) {
                                        return $imagem->tipo === 'url'
                                            ? $imagem->url_externa
                                            : ($imagem->caminho_local ? asset('storage/' . $imagem->caminho_local) : null);
                                    })
                                    ->filter()
                                    ->values();
                                $variacoesJson = ($produto->formato ?? '') === 'variacao' && ($produto->variacoes ?? collect())->isNotEmpty()
                                    ? $produto->variacoes->map(fn($v) => ['id' => $v->id, 'referencia_sku' => $v->referencia_sku ?? '', 'opcoes_valores' => is_array($v->opcoes_valores ?? null) ? implode(', ', $v->opcoes_valores) : ($v->opcoes_valores ?? ''), 'quantidade' => $v->quantidade])->values()->toJson()
                                    : '[]';
                            @endphp
                            <option value="{{ $produto->id }}"
                                data-preco="{{ $produto->preco_venda }}"
                                data-preco-varejo="{{ $precoVarejo }}"
                                data-preco-empresa="{{ $precoEmpresa }}"
                                data-preco-urgencia="{{ $precoUrgencia }}"
                                data-descricao="{{ $produto->descricao_complementar }}"
                                data-imagens='@json($imagensProduto)'
                                data-variacoes='{{ $variacoesJson }}'
                                data-estoque="{{ $produto->estoque_quantidade ?? 0 }}">
                                {{ $produto->nome }}
                            </option>
                        @endforeach
                    </select>
                    <div class="produto-variacao-wrapper mt-1.5 hidden">
                        <label class="mb-1 block text-xs text-gray-600 dark:text-gray-400">Variação <span class="text-error-500">*</span></label>
                        <select name="produtos[${produtoIndex}][produto_variacao_id]" class="variacao-select w-full rounded-lg border border-gray-300 px-3 py-2 text-sm text-gray-800 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90">
                            <option value="">Selecione a variação...</option>
                        </select>
                    </div>
                </div>
                <div class="flex flex-col justify-end">
                    <label class="mb-1 block text-xs text-gray-600 dark:text-gray-400">Estoque</label>
                    <div class="min-h-[42px] rounded-lg border border-gray-200 bg-gray-50 px-3 py-2 text-sm font-medium text-gray-800 dark:border-gray-700 dark:bg-gray-900/50 dark:text-gray-200">
                        <span class="produto-estoque-valor">—</span>
                    </div>
                </div>
                <div>
                    <label class="mb-1 block text-xs text-gray-600 dark:text-gray-400">Qtd</label>
                    <input type="number" step="0.001" min="0" name="produtos[${produtoIndex}][quantidade]" value="1" class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm text-gray-800 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90">
                </div>
                <div>
                    <label class="mb-1 block text-xs text-gray-600 dark:text-gray-400">Valor unit.</label>
                    <input type="number" step="0.01" min="0" name="produtos[${produtoIndex}][valor_unitario]" value="0" class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm text-gray-800 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90">
                </div>
                <div>
                    <label class="mb-1 block text-xs text-gray-600 dark:text-gray-400">Tipo de desconto</label>
                    <select name="produtos[${produtoIndex}][desconto_tipo]" class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm text-gray-800 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90">
                        <option value="valor" selected>R$</option>
                        <option value="percentual">%</option>
                    </select>
                </div>
                <div class="desconto-item-cell">
                    <label class="mb-1 block text-xs text-gray-600 dark:text-gray-400">Desconto</label>
                    <input type="number" step="0.01" min="0" name="produtos[${produtoIndex}][desconto_valor_unico]" class="js-desconto-item-valor w-full rounded-lg border border-gray-300 px-3 py-2 text-sm text-gray-800 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90">
                    <p class="desconto-item-aviso mt-0.5 hidden text-xs text-error-600 dark:text-error-400" role="alert"></p>
                    <input type="hidden" name="produtos[${produtoIndex}][desconto_valor]">
                    <input type="hidden" name="produtos[${produtoIndex}][desconto_percentual]">
                </div>
            </div>
            <div class="mt-3 grid grid-cols-1 gap-2 md:grid-cols-6">
                <div class="md:col-span-2">
                    <label class="mb-1 block text-xs text-gray-600 dark:text-gray-400">Descrição</label>
                    <input type="text" name="produtos[${produtoIndex}][descricao]" class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm text-gray-800 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90">
                </div>
                <div class="md:col-span-6 produto-imagens hidden">
                    <label class="mb-1 block text-xs text-gray-600 dark:text-gray-400">Imagens do produto</label>
                    <div class="flex flex-wrap gap-2"></div>
                </div>
            </div>
            <div class="mt-3 flex justify-end">
                <button type="button" onclick="removerLinha(this)" class="text-error-500 hover:underline">Remover</button>
            </div>
        </div>
    `;
    container.insertAdjacentHTML('beforeend', html);
    const novoProduto = container.lastElementChild;
    if (novoProduto && typeof sincronizarDesconto === 'function') {
        sincronizarDesconto(novoProduto, 'produtos');
    }
    produtoIndex++;
}

function atualizarImagensProduto(wrapper, imagens) {
    const bloco = wrapper.querySelector('.produto-imagens');
    if (!bloco) return;
    const container = bloco.querySelector('div');
    if (!imagens || !imagens.length) {
        if (container) container.innerHTML = '';
        bloco.classList.add('hidden');
        return;
    }
    if (container) {
        container.innerHTML = imagens.map((url, index) => (
            `<button type="button" class="produto-imagem-thumb" data-imagens='${JSON.stringify(imagens)}' data-index="${index}">
                <img src="${url}" alt="Imagem do produto" class="h-16 w-16 rounded border border-gray-200 object-cover">
            </button>`
        )).join('');
    }
    bloco.classList.remove('hidden');
}

function atualizarLimiteCampoDescontoItem(tipoSelect, valorInput) {
    if (!tipoSelect || !valorInput) return;
    if (tipoSelect.value === 'percentual') {
        valorInput.setAttribute('max', '100');
    } else {
        valorInput.removeAttribute('max');
    }
}

function validarMensagemDescontoItem(tipoSelect, valorInput) {
    if (!valorInput) return true;
    const cell = valorInput.closest('.desconto-item-cell');
    const aviso = cell ? cell.querySelector('.desconto-item-aviso') : null;
    const raw = String(valorInput.value ?? '').trim().replace(',', '.');
    const v = parseFloat(raw);
    valorInput.setCustomValidity('');
    if (tipoSelect && tipoSelect.value === 'percentual' && raw !== '' && !Number.isNaN(v) && v > 100) {
        valorInput.setCustomValidity('O desconto percentual não pode ser maior que 100%.');
        if (aviso) {
            aviso.textContent = 'O desconto em % não pode ser maior que 100.';
            aviso.classList.remove('hidden');
        }
        return false;
    }
    if (aviso) {
        aviso.textContent = '';
        aviso.classList.add('hidden');
    }
    return true;
}

function sincronizarDesconto(wrapper, prefixo) {
    const tipo = wrapper.querySelector(`select[name^="${prefixo}"][name$="[desconto_tipo]"]`);
    const valorVisivel = wrapper.querySelector(`input[name^="${prefixo}"][name$="[desconto_valor_unico]"]`);
    const valorHidden = wrapper.querySelector(`input[name^="${prefixo}"][name$="[desconto_valor]"]`);
    const percentualHidden = wrapper.querySelector(`input[name^="${prefixo}"][name$="[desconto_percentual]"]`);
    if (!tipo || !valorVisivel || !valorHidden || !percentualHidden) return;
    if (tipo.value === 'percentual') {
        percentualHidden.value = valorVisivel.value || '';
        valorHidden.value = '';
    } else {
        valorHidden.value = valorVisivel.value || '';
        percentualHidden.value = '';
    }
    atualizarLimiteCampoDescontoItem(tipo, valorVisivel);
    validarMensagemDescontoItem(tipo, valorVisivel);
}

function preencherProduto(select, sobrescreverValores = true) {
    const wrapper = select.closest('[data-produto-index]');
    if (!wrapper) return;
    const option = select.selectedOptions[0];
    const variacaoWrapper = wrapper.querySelector('.produto-variacao-wrapper');
    const variacaoSelect = wrapper.querySelector('.variacao-select');
    const quantidadeInput = wrapper.querySelector('input[name$="[quantidade]"]');
    const valorInput = wrapper.querySelector('input[name$="[valor_unitario]"]');
    const descontoTipoInput = wrapper.querySelector('select[name$="[desconto_tipo]"]');
    const descontoValorUnicoInput = wrapper.querySelector('input[name$="[desconto_valor_unico]"]');
    const descontoValorInput = wrapper.querySelector('input[name$="[desconto_valor]"]');
    const descontoPercentualInput = wrapper.querySelector('input[name$="[desconto_percentual]"]');
    const descricaoInput = wrapper.querySelector('input[name$="[descricao]"]');
    if (!option || !option.value) {
        if (variacaoWrapper) variacaoWrapper.classList.add('hidden');
        if (variacaoSelect) { variacaoSelect.innerHTML = '<option value="">Selecione a variação...</option>'; variacaoSelect.value = ''; }
        if (quantidadeInput) quantidadeInput.value = 1;
        if (descontoTipoInput) descontoTipoInput.value = 'valor';
        if (descontoValorUnicoInput) descontoValorUnicoInput.value = '';
        if (descontoValorInput) descontoValorInput.value = '';
        if (descontoPercentualInput) descontoPercentualInput.value = '';
        if (descricaoInput) descricaoInput.value = '';
        if (valorInput) { valorInput.value = ''; valorInput.readOnly = false; valorInput.removeAttribute('title'); }
        atualizarImagensProduto(wrapper, []);
        atualizarExibicaoEstoque(wrapper);
        return;
    }
    const variacoes = option.dataset.variacoes ? JSON.parse(option.dataset.variacoes) : [];
    const valorVariacaoAntigo = variacaoSelect?.value;
    if (variacaoWrapper && variacaoSelect) {
        if (variacoes.length > 0) {
            variacaoWrapper.classList.remove('hidden');
            variacaoSelect.required = true;
            variacaoSelect.innerHTML = '<option value="">Selecione a variação...</option>' + variacoes.map(v => {
                const label = (v.referencia_sku || 'SKU') + (v.opcoes_valores ? ' (' + v.opcoes_valores + ')' : '');
                const qtd = (v.quantidade != null && v.quantidade !== '') ? Number(v.quantidade) : '';
                return `<option value="${v.id}" data-quantidade="${qtd}">${label}</option>`;
            }).join('');
            const mantemValor = valorVariacaoAntigo && variacoes.some(v => String(v.id) === String(valorVariacaoAntigo));
            variacaoSelect.value = mantemValor ? valorVariacaoAntigo : '';
        } else {
            variacaoWrapper.classList.add('hidden');
            variacaoSelect.required = false;
            variacaoSelect.innerHTML = '<option value="">Selecione a variação...</option>';
            variacaoSelect.value = '';
        }
    }
    const tabelaSelect = document.getElementById('tabela_preco');
    const tabela = tabelaSelect?.value || 'varejo';
    const precoTabela = option.dataset[`preco${tabela.charAt(0).toUpperCase()}${tabela.slice(1)}`];
    const preco = precoTabela || option.dataset.preco || '';
    const descricao = option.dataset.descricao?.trim() || '';
    const imagens = option.dataset.imagens ? JSON.parse(option.dataset.imagens) : [];
    if (sobrescreverValores) {
        if (quantidadeInput) quantidadeInput.value = 1;
        if (descontoTipoInput) descontoTipoInput.value = 'valor';
        if (descontoValorUnicoInput) descontoValorUnicoInput.value = '';
        if (descontoValorInput) descontoValorInput.value = '';
        if (descontoPercentualInput) descontoPercentualInput.value = '';
        if (descricaoInput) descricaoInput.value = descricao;
        if (valorInput) {
            valorInput.value = preco ? Number(preco).toFixed(2) : '';
            if (window.bloquearAlteracaoPreco && window.usuarioSujeitoALimiteDesconto) {
                valorInput.readOnly = true;
                valorInput.title = 'Preço do cadastro não pode ser alterado.';
            } else {
                valorInput.readOnly = false;
                valorInput.removeAttribute('title');
            }
        }
    } else {
        // Modo inicialização: não sobrescreve valores já carregados do servidor
        if (valorInput && window.bloquearAlteracaoPreco && window.usuarioSujeitoALimiteDesconto) {
            valorInput.readOnly = true;
            valorInput.title = 'Preço do cadastro não pode ser alterado.';
        }
        if (descricaoInput && (!descricaoInput.value || descricaoInput.value.trim() === '')) descricaoInput.value = descricao;
    }
    atualizarImagensProduto(wrapper, imagens);
    atualizarExibicaoEstoque(wrapper);
}

function atualizarExibicaoEstoque(wrapper) {
    const estoqueSpan = wrapper?.querySelector('.produto-estoque-valor');
    if (!estoqueSpan) return;
    const select = wrapper.querySelector('select[name^="produtos["][name$="[produto_id]"]');
    const option = select?.selectedOptions?.[0];
    if (!option || !option.value) {
        estoqueSpan.textContent = '—';
        return;
    }
    const variacaoSelect = wrapper.querySelector('.variacao-select');
    const variacoes = option.dataset.variacoes ? JSON.parse(option.dataset.variacoes) : [];
    if (variacoes.length > 0 && variacaoSelect) {
        const varOption = variacaoSelect.selectedOptions?.[0];
        if (varOption && varOption.value) {
            const qtd = varOption.dataset.quantidade !== undefined ? varOption.dataset.quantidade : (varOption.getAttribute('data-quantidade') ?? '');
            estoqueSpan.textContent = (qtd !== '' && qtd != null) ? formatQuantityDisplay(qtd) : '—';
        } else {
            estoqueSpan.textContent = 'Selecione a variação';
        }
    } else {
        const estoque = option.dataset.estoque;
        estoqueSpan.textContent = (estoque !== undefined && estoque !== '' && estoque != null) ? formatQuantityDisplay(estoque) : '—';
    }
}

function formatQuantityDisplay(val) {
    const n = Number(val);
    if (isNaN(n)) return val;
    return n % 1 === 0 ? String(n) : n.toFixed(3).replace(/\.?0+$/, '');
}

function adicionarServico() {
    const container = document.getElementById('servicos_container');
    const html = `
        <div class="rounded-lg border border-gray-200 p-4 dark:border-gray-700" data-servico-index="${servicoIndex}">
            <div class="grid grid-cols-1 gap-3 md:grid-cols-8">
                <div class="md:col-span-2">
                    <label class="mb-1 block text-xs text-gray-600 dark:text-gray-400">Serviço</label>
                    <select name="servicos[${servicoIndex}][servico_id]" class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm text-gray-800 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90">
                        <option value="">Selecione...</option>
                        @foreach($servicos as $servico)
                            <option value="{{ $servico->id }}" data-preco="{{ $servico->preco }}">{{ $servico->nome }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="md:col-span-1">
                    <label class="mb-1 block text-xs text-gray-600 dark:text-gray-400">Cobrança por horas</label>
                    <div class="flex items-center gap-2 rounded-lg border border-gray-300 px-3 py-2 text-xs text-gray-700 dark:border-gray-700 dark:text-gray-300">
                        <input type="checkbox" class="h-4 w-4" data-servico-cobranca-toggle>
                        <span>Horas</span>
                    </div>
                    <input type="hidden" name="servicos[${servicoIndex}][cobranca_tipo]" value="unidade">
                </div>
                <div class="md:col-span-1 servico-cobranca-unidade">
                    <label class="mb-1 block text-xs text-gray-600 dark:text-gray-400">Qtd</label>
                    <input type="number" step="0.001" min="0" name="servicos[${servicoIndex}][quantidade]" value="1" class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm text-gray-800 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90">
                </div>
                <div class="md:col-span-1 servico-cobranca-horas hidden">
                    <label class="mb-1 block text-xs text-gray-600 dark:text-gray-400">Horas</label>
                    <input type="number" step="0.001" min="0" name="servicos[${servicoIndex}][quantidade_horas]" value="1" class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm text-gray-800 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90">
                </div>
                <div class="md:col-span-1">
                    <label class="mb-1 block text-xs text-gray-600 dark:text-gray-400">Técnico</label>
                    <select name="servicos[${servicoIndex}][tecnico_id]" class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm text-gray-800 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90">
                        <option value="">Selecione...</option>
                        @foreach($tecnicos as $tecnico)
                            <option value="{{ $tecnico->id }}">{{ $tecnico->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="md:col-span-1">
                    <label class="mb-1 block text-xs text-gray-600 dark:text-gray-400">Valor</label>
                    <input type="number" step="0.01" min="0" name="servicos[${servicoIndex}][valor_unitario]" value="0" class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm text-gray-800 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90">
                </div>
            </div>
            <div class="mt-3 grid grid-cols-1 gap-2 md:grid-cols-6">
                <div class="md:col-span-2">
                    <label class="mb-1 block text-xs text-gray-600 dark:text-gray-400">Descrição</label>
                    <input type="text" name="servicos[${servicoIndex}][descricao]" class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm text-gray-800 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90">
                </div>
                <div>
                    <label class="mb-1 block text-xs text-gray-600 dark:text-gray-400">Tipo de desconto</label>
                    <select name="servicos[${servicoIndex}][desconto_tipo]" class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm text-gray-800 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90">
                        <option value="valor" selected>R$</option>
                        <option value="percentual">%</option>
                    </select>
                </div>
                <div class="desconto-item-cell">
                    <label class="mb-1 block text-xs text-gray-600 dark:text-gray-400">Desconto</label>
                    <input type="number" step="0.01" min="0" name="servicos[${servicoIndex}][desconto_valor_unico]" class="js-desconto-item-valor w-full rounded-lg border border-gray-300 px-3 py-2 text-sm text-gray-800 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90">
                    <p class="desconto-item-aviso mt-0.5 hidden text-xs text-error-600 dark:text-error-400" role="alert"></p>
                    <input type="hidden" name="servicos[${servicoIndex}][desconto_valor]">
                    <input type="hidden" name="servicos[${servicoIndex}][desconto_percentual]">
                </div>
            </div>
            <div class="mt-3 flex justify-end">
                <button type="button" onclick="removerLinha(this)" class="text-error-500 hover:underline">Remover</button>
            </div>
        </div>
    `;
    container.insertAdjacentHTML('beforeend', html);
    const novoServico = container.lastElementChild;
    if (novoServico && typeof sincronizarDesconto === 'function') {
        sincronizarDesconto(novoServico, 'servicos');
    }
    servicoIndex++;
}

function preencherServico(select, sobrescreverValores = true) {
    const wrapper = select.closest('[data-servico-index]');
    if (!wrapper) return;
    const option = select.selectedOptions[0];
    const valorInput = wrapper.querySelector('input[name$="[valor_unitario]"]');
    const descricaoInput = wrapper.querySelector('input[name$="[descricao]"]');
    const descontoTipoInput = wrapper.querySelector('select[name$="[desconto_tipo]"]');
    const descontoValorUnicoInput = wrapper.querySelector('input[name$="[desconto_valor_unico]"]');
    const descontoValorInput = wrapper.querySelector('input[name$="[desconto_valor]"]');
    const descontoPercentualInput = wrapper.querySelector('input[name$="[desconto_percentual]"]');
    const cobrancaToggle = wrapper.querySelector('[data-servico-cobranca-toggle]');
    const cobrancaHidden = wrapper.querySelector('input[name$="[cobranca_tipo]"]');
    const quantidadeInput = wrapper.querySelector('input[name$="[quantidade]"]');
    const quantidadeHorasInput = wrapper.querySelector('input[name$="[quantidade_horas]"]');

    if (!option || !option.value) {
        if (valorInput) { valorInput.value = ''; valorInput.readOnly = false; valorInput.removeAttribute('title'); }
        if (descricaoInput) descricaoInput.value = '';
        if (descontoTipoInput) descontoTipoInput.value = 'valor';
        if (descontoValorUnicoInput) descontoValorUnicoInput.value = '';
        if (descontoValorInput) descontoValorInput.value = '';
        if (descontoPercentualInput) descontoPercentualInput.value = '';
        if (cobrancaToggle) cobrancaToggle.checked = false;
        if (cobrancaHidden) cobrancaHidden.value = 'unidade';
        if (quantidadeInput) quantidadeInput.value = 1;
        if (quantidadeHorasInput) quantidadeHorasInput.value = 1;
        atualizarServicoCobranca(wrapper);
        return;
    }

    const preco = option.dataset.preco || '';
    const nome = option.textContent?.trim() || '';
    if (sobrescreverValores) {
        if (valorInput) {
            valorInput.value = preco ? Number(preco).toFixed(2) : '';
            if (window.bloquearAlteracaoPreco && window.usuarioSujeitoALimiteDesconto) {
                valorInput.readOnly = true;
                valorInput.title = 'Preço do cadastro não pode ser alterado.';
            } else {
                valorInput.readOnly = false;
                valorInput.removeAttribute('title');
            }
        }
        if (descricaoInput && (!descricaoInput.value || descricaoInput.value.trim() === '')) descricaoInput.value = nome;
        if (descontoTipoInput) descontoTipoInput.value = 'valor';
        if (descontoValorUnicoInput) descontoValorUnicoInput.value = '';
        if (descontoValorInput) descontoValorInput.value = '';
        if (descontoPercentualInput) descontoPercentualInput.value = '';
        if (cobrancaToggle) cobrancaToggle.checked = false;
        if (cobrancaHidden) cobrancaHidden.value = 'unidade';
        if (quantidadeInput) quantidadeInput.value = 1;
        if (quantidadeHorasInput) quantidadeHorasInput.value = 1;
    } else {
        // Modo inicialização: só ajusta readonly/título sem sobrescrever valores já carregados do servidor
        if (valorInput && window.bloquearAlteracaoPreco && window.usuarioSujeitoALimiteDesconto) {
            valorInput.readOnly = true;
            valorInput.title = 'Preço do cadastro não pode ser alterado.';
        }
        if (descricaoInput && (!descricaoInput.value || descricaoInput.value.trim() === '')) descricaoInput.value = nome;
    }
    atualizarServicoCobranca(wrapper);
}

function formatarMoeda(valor) {
    return `R$ ${valor.toFixed(2).replace('.', ',')}`;
}

function calcularTotaisProdutos() {
    let subtotal = 0;
    let descontoTotal = 0;
    document.querySelectorAll('[data-produto-index]').forEach(wrapper => {
        const quantidade = parseFloat(wrapper.querySelector('input[name$="[quantidade]"]')?.value || '0');
        const valorUnitario = parseFloat(wrapper.querySelector('input[name$="[valor_unitario]"]')?.value || '0');
        const descontoValor = parseFloat(wrapper.querySelector('input[name$="[desconto_valor]"]')?.value || '0');
        const descontoPercentual = parseFloat(wrapper.querySelector('input[name$="[desconto_percentual]"]')?.value || '0');
        const itemSubtotal = quantidade * valorUnitario;
        let itemDesconto = 0;
        if (descontoPercentual > 0) {
            itemDesconto = itemSubtotal * (descontoPercentual / 100);
        } else if (descontoValor > 0) {
            itemDesconto = descontoValor;
        }
        subtotal += itemSubtotal;
        descontoTotal += itemDesconto;
    });
    const total = Math.max(subtotal - descontoTotal, 0);
    const elSubtotal = document.getElementById('subtotal_produtos');
    const elDescontos = document.getElementById('descontos_produtos');
    const elTotal = document.getElementById('total_produtos_itens');
    if (elSubtotal) elSubtotal.textContent = formatarMoeda(subtotal);
    if (elDescontos) elDescontos.textContent = formatarMoeda(descontoTotal);
    if (elTotal) elTotal.textContent = formatarMoeda(total);
}

function calcularTotaisServicos() {
    let subtotal = 0;
    let descontoTotal = 0;
    document.querySelectorAll('[data-servico-index]').forEach(wrapper => {
        const valorUnitario = parseFloat(wrapper.querySelector('input[name$="[valor_unitario]"]')?.value || '0');
        const cobrancaTipo = wrapper.querySelector('input[name$="[cobranca_tipo]"]')?.value || 'unidade';
        const quantidade = parseFloat(wrapper.querySelector('input[name$="[quantidade]"]')?.value || '1');
        const quantidadeHoras = parseFloat(wrapper.querySelector('input[name$="[quantidade_horas]"]')?.value || '1');
        const descontoValor = parseFloat(wrapper.querySelector('input[name$="[desconto_valor]"]')?.value || '0');
        const descontoPercentual = parseFloat(wrapper.querySelector('input[name$="[desconto_percentual]"]')?.value || '0');
        const itemSubtotal = cobrancaTipo === 'horas'
            ? (valorUnitario * quantidadeHoras)
            : (valorUnitario * quantidade);
        let itemDesconto = 0;
        if (descontoPercentual > 0) {
            itemDesconto = itemSubtotal * (descontoPercentual / 100);
        } else if (descontoValor > 0) {
            itemDesconto = descontoValor;
        }
        subtotal += itemSubtotal;
        descontoTotal += itemDesconto;
    });
    const total = Math.max(subtotal - descontoTotal, 0);
    const elSubtotal = document.getElementById('subtotal_servicos');
    const elDescontos = document.getElementById('descontos_servicos');
    const elTotal = document.getElementById('total_servicos_itens');
    if (elSubtotal) elSubtotal.textContent = formatarMoeda(subtotal);
    if (elDescontos) elDescontos.textContent = formatarMoeda(descontoTotal);
    if (elTotal) elTotal.textContent = formatarMoeda(total);
    if (typeof atualizarTotaisNoPainel === 'function') atualizarTotaisNoPainel();
}

function atualizarTotaisNoPainel() {
    let totalProdutos = 0;
    let descontosProdutos = 0;
    document.querySelectorAll('[data-produto-index]').forEach(wrapper => {
        const quantidade = parseFloat(wrapper.querySelector('input[name$="[quantidade]"]')?.value || '0');
        const valorUnitario = parseFloat(wrapper.querySelector('input[name$="[valor_unitario]"]')?.value || '0');
        const descontoValor = parseFloat(wrapper.querySelector('input[name$="[desconto_valor]"]')?.value || '0');
        const descontoPercentual = parseFloat(wrapper.querySelector('input[name$="[desconto_percentual]"]')?.value || '0');
        const itemSubtotal = quantidade * valorUnitario;
        let itemDesconto = 0;
        if (descontoPercentual > 0) itemDesconto = itemSubtotal * (descontoPercentual / 100);
        else if (descontoValor > 0) itemDesconto = descontoValor;
        descontosProdutos += itemDesconto;
        totalProdutos += Math.max(itemSubtotal - itemDesconto, 0);
    });
    let totalServicos = 0;
    let descontosServicos = 0;
    document.querySelectorAll('[data-servico-index]').forEach(wrapper => {
        const valorUnitario = parseFloat(wrapper.querySelector('input[name$="[valor_unitario]"]')?.value || '0');
        const cobrancaTipo = wrapper.querySelector('input[name$="[cobranca_tipo]"]')?.value || 'unidade';
        const quantidade = parseFloat(wrapper.querySelector('input[name$="[quantidade]"]')?.value || '1');
        const quantidadeHoras = parseFloat(wrapper.querySelector('input[name$="[quantidade_horas]"]')?.value || '1');
        const descontoValor = parseFloat(wrapper.querySelector('input[name$="[desconto_valor]"]')?.value || '0');
        const descontoPercentual = parseFloat(wrapper.querySelector('input[name$="[desconto_percentual]"]')?.value || '0');
        const itemSubtotal = cobrancaTipo === 'horas' ? (valorUnitario * quantidadeHoras) : (valorUnitario * quantidade);
        let itemDesconto = 0;
        if (descontoPercentual > 0) itemDesconto = itemSubtotal * (descontoPercentual / 100);
        else if (descontoValor > 0) itemDesconto = descontoValor;
        descontosServicos += itemDesconto;
        totalServicos += Math.max(itemSubtotal - itemDesconto, 0);
    });
    const descontoGlobal = parseFloat(document.querySelector('input[name="desconto_global"]')?.value || '0');
    const acrescimosGlobal = parseFloat(document.querySelector('input[name="acrescimos_global"]')?.value || '0');
    const impostosGlobal = parseFloat(document.querySelector('input[name="impostos_global"]')?.value || '0');
    const totalDescontos = descontosProdutos + descontosServicos + descontoGlobal;
    const totalGeral = Math.max(0, totalProdutos + totalServicos - descontoGlobal + acrescimosGlobal + impostosGlobal);
    const fmt = (v) => v.toFixed(2).replace('.', ',').replace(/\B(?=(\d{3})+(?!\d))/g, '.');
    const elProd = document.getElementById('total_produtos_display');
    const elServ = document.getElementById('total_servicos_display');
    const elDescontos = document.getElementById('total_descontos_display');
    const elGeral = document.getElementById('total_geral_display');
    if (elProd) elProd.value = fmt(totalProdutos);
    if (elServ) elServ.value = fmt(totalServicos);
    if (elDescontos) elDescontos.value = 'R$ ' + fmt(totalDescontos);
    if (elGeral) elGeral.value = fmt(totalGeral);
}

function atualizarServicoCobranca(wrapper) {
    const toggle = wrapper.querySelector('[data-servico-cobranca-toggle]');
    const hidden = wrapper.querySelector('input[name$="[cobranca_tipo]"]');
    const quantidadeWrap = wrapper.querySelector('.servico-cobranca-unidade');
    const horasWrap = wrapper.querySelector('.servico-cobranca-horas');
    const quantidadeInput = wrapper.querySelector('input[name$="[quantidade]"]');
    const horasInput = wrapper.querySelector('input[name$="[quantidade_horas]"]');
    const isHoras = !!toggle?.checked;
    if (hidden) hidden.value = isHoras ? 'horas' : 'unidade';
    if (quantidadeWrap) quantidadeWrap.classList.toggle('hidden', isHoras);
    if (horasWrap) horasWrap.classList.toggle('hidden', !isHoras);
    if (!isHoras && quantidadeInput && !quantidadeInput.value) {
        quantidadeInput.value = 1;
    }
    if (isHoras && horasInput && !horasInput.value) {
        horasInput.value = 1;
    }
}

function adicionarTecnico() {
    const container = document.getElementById('tecnicos_container');
    const html = `
        <div class="grid grid-cols-1 gap-2 md:grid-cols-6">
            <select name="tecnicos[${tecnicoIndex}][tecnico_id]" class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm text-gray-800 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90">
                <option value="">Selecione...</option>
                @foreach($tecnicos as $tecnico)
                    <option value="{{ $tecnico->id }}">{{ $tecnico->name }}</option>
                @endforeach
            </select>
            <input type="text" name="tecnicos[${tecnicoIndex}][status]" placeholder="Status" class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm text-gray-800 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90">
            <input type="datetime-local" name="tecnicos[${tecnicoIndex}][inicio]" class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm text-gray-800 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90">
            <input type="datetime-local" name="tecnicos[${tecnicoIndex}][fim]" class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm text-gray-800 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90">
            <input type="number" step="0.01" min="0" name="tecnicos[${tecnicoIndex}][horas_trabalhadas]" placeholder="Horas" class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm text-gray-800 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90">
            <button type="button" onclick="removerLinha(this)" class="text-error-500 hover:underline text-sm">Remover</button>
        </div>
    `;
    container.insertAdjacentHTML('beforeend', html);
    tecnicoIndex++;
}

function adicionarApontamento() {
    const container = document.getElementById('apontamentos_container');
    const html = `
        <div class="grid grid-cols-1 gap-2 md:grid-cols-6">
            <select name="apontamentos[${apontamentoIndex}][tecnico_id]" class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm text-gray-800 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90">
                <option value="">Selecione...</option>
                @foreach($tecnicos as $tecnico)
                    <option value="{{ $tecnico->id }}">{{ $tecnico->name }}</option>
                @endforeach
            </select>
            <input type="datetime-local" name="apontamentos[${apontamentoIndex}][inicio]" class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm text-gray-800 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90">
            <input type="datetime-local" name="apontamentos[${apontamentoIndex}][fim]" class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm text-gray-800 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90">
            <input type="number" min="0" name="apontamentos[${apontamentoIndex}][pausas_minutos]" placeholder="Pausas (min)" class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm text-gray-800 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90">
            <input type="text" name="apontamentos[${apontamentoIndex}][status]" placeholder="Status" class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm text-gray-800 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90">
            <button type="button" onclick="removerLinha(this)" class="text-error-500 hover:underline text-sm">Remover</button>
            <textarea name="apontamentos[${apontamentoIndex}][observacoes]" rows="1" placeholder="Observações" class="md:col-span-6 w-full rounded-lg border border-gray-300 px-3 py-2 text-sm text-gray-800 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90"></textarea>
        </div>
    `;
    container.insertAdjacentHTML('beforeend', html);
    apontamentoIndex++;
}

function adicionarAnexo() {
    const container = document.getElementById('anexos_container');
    const html = `
        <div class="grid grid-cols-1 gap-2 md:grid-cols-6">
            <input type="file" name="anexos_arquivo[${anexoIndex}]" class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm text-gray-800 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90">
            <input type="text" name="anexos_tipo[${anexoIndex}]" placeholder="Tipo (foto, laudo...)" class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm text-gray-800 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90">
            <input type="text" name="anexos_tags[${anexoIndex}]" placeholder="Tags" class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm text-gray-800 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90">
            <input type="text" name="anexos_descricao[${anexoIndex}]" placeholder="Descrição" class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm text-gray-800 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90">
            <input type="text" name="anexos_metadata[${anexoIndex}]" placeholder="Metadados (JSON)" class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm text-gray-800 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90">
            <button type="button" onclick="removerLinha(this)" class="text-error-500 hover:underline text-sm">Remover</button>
        </div>
    `;
    container.insertAdjacentHTML('beforeend', html);
    anexoIndex++;
}

function removerAnexoExistente(id, btn) {
    const container = document.getElementById('anexos_remover_container');
    if (!container) return;
    const input = document.createElement('input');
    input.type = 'hidden';
    input.name = 'anexos_remover[]';
    input.value = id;
    container.appendChild(input);
    const wrapper = btn.closest('div');
    if (wrapper) wrapper.remove();
}

function renderizarAnexosExistentes(anexos) {
    const lista = document.getElementById('anexos_existentes_lista');
    const vazio = document.getElementById('anexos_vazio');
    if (!lista) return;
    const esc = (s) => (s == null ? '' : String(s)).replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;');
    const form = document.getElementById('osForm');
    const deleteUrlTmpl = form?.dataset.anexosDeleteUrl || '';
    const updateUrlTmpl = form?.dataset.anexosUpdateUrl || '';
    const osEncerrada = form?.dataset.osEncerrada === '1';
    const html = anexos.map(a => {
        const nome = esc(a.nome_arquivo || '');
        const tipo = esc(a.tipo || '');
        const tags = esc(a.tags || '');
        const descricao = esc(a.descricao || '');
        const url = a.url || '';
        const id = a.id || 0;
        const delUrl = esc(deleteUrlTmpl.replace('__ID__', id));
        const updUrl = esc(updateUrlTmpl.replace('__ID__', id));
        const descExibida = descricao ? (descricao.length > 60 ? descricao.slice(0, 57) + '...' : descricao) : '';
        const thumb = a.is_image
            ? `<button type="button" class="anexo-preview-img shrink-0 cursor-pointer text-left block overflow-hidden" data-url="${esc(url)}" data-nome="${nome}"><img src="${esc(url)}" alt="" class="h-14 w-14 rounded object-cover object-center" loading="lazy" onerror="this.style.display='none';this.nextElementSibling?.classList.remove('hidden');"><span class="hidden flex h-14 w-14 shrink-0 items-center justify-center rounded bg-gray-200 text-gray-500"><svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14"/></svg></span></button>`
            : `<a href="${esc(url)}" download="${nome}" class="flex h-14 w-14 shrink-0 items-center justify-center rounded bg-gray-100 text-gray-500 dark:bg-gray-700"><svg class="h-8 w-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg></a>`;
        const actionBtn = a.is_image
            ? `<button type="button" class="anexo-preview-img rounded-lg border border-gray-300 px-3 py-1.5 text-xs font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-700 dark:text-gray-300" data-url="${esc(url)}" data-nome="${nome}">Ver</button>`
            : `<a href="${esc(url)}" download="${nome}" class="rounded-lg border border-gray-300 px-3 py-1.5 text-xs font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-700 dark:text-gray-300">Download</a>`;
        const editarBtn = osEncerrada ? '' : `<button type="button" class="anexo-editar-btn rounded-lg border border-gray-300 px-3 py-1.5 text-xs font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-700 dark:text-gray-300" data-anexo-id="${id}" data-update-url="${updUrl}" data-anexo-tipo="${esc(tipo)}" data-anexo-tags="${esc(tags)}" data-anexo-descricao="${esc(descricao)}" data-anexo-nome="${esc(nome)}">Editar</button>`;
        const excluirBtn = osEncerrada ? '' : `<button type="button" class="anexo-excluir-btn rounded-lg border border-error-300 px-3 py-1.5 text-xs font-medium text-error-600 hover:bg-error-50 dark:border-error-700 dark:text-error-400" data-anexo-id="${id}" data-delete-url="${delUrl}">Excluir</button>`;
        const viewLine = `${tipo}${tags ? ' - ' + tags : ''}${descExibida ? ' — ' + esc(descExibida) : ''}`;
        return `<div class="rounded-lg border border-gray-200 p-3 text-sm dark:border-gray-700" data-anexo-id="${id}"><div class="flex items-center gap-3">${thumb}<div class="min-w-0 flex-1"><p class="font-medium text-gray-800 dark:text-white/90 truncate">${nome}</p><div class="anexo-view-content"><p class="text-xs text-gray-500 dark:text-gray-400">${viewLine}</p></div></div><div class="flex shrink-0 flex-wrap gap-2">${editarBtn}${actionBtn}${excluirBtn}</div></div></div>`;
    }).join('');
    lista.innerHTML = html || '';
    if (vazio) vazio.classList.toggle('hidden', (anexos?.length || 0) > 0);
    initAnexosCarouselAndDelete();
    initAnexosEdit();
}

let anexosPendentesFiles = [];

function initAnexosCarouselAndDelete() {
    document.querySelectorAll('.anexo-preview-img').forEach(btn => {
        btn.onclick = null;
        btn.addEventListener('click', function() {
            const url = this.dataset.url;
            const nome = this.dataset.nome || '';
            if (!url) return;
            const todas = Array.from(document.querySelectorAll('.anexo-preview-img')).map(b => ({ url: b.dataset.url, nome: b.dataset.nome || '' })).filter(x => x.url);
            abrirCarouselAnexos(todas, url, nome);
        });
    });
    document.querySelectorAll('.anexo-excluir-btn').forEach(btn => {
        btn.onclick = null;
        btn.addEventListener('click', async function() {
            const url = this.dataset.deleteUrl;
            if (!url || !confirm('Excluir este anexo?')) return;
            const csrf = document.querySelector('meta[name="csrf-token"]')?.content || document.querySelector('input[name="_token"]')?.value;
            try {
                const res = await fetch(url, { method: 'DELETE', headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json', ...(csrf ? { 'X-CSRF-TOKEN': csrf } : {}) }, credentials: 'same-origin' });
                const data = await res.json().catch(() => ({}));
                if (res.ok && data.anexos) {
                    renderizarAnexosExistentes(data.anexos);
                    if (typeof showToast === 'function') showToast(data.message || 'Anexo excluído.', 'success');
                } else {
                    if (typeof showToast === 'function') showToast(data?.message || 'Erro ao excluir.', 'error');
                }
            } catch (e) {
                if (typeof showToast === 'function') showToast('Erro ao excluir: ' + e.message, 'error');
            }
        });
    });
}

function initAnexosEdit() {
    // Edição de anexos usa modal - handlers configurados no script inline da seção anexos
}

function abrirCarouselAnexos(itens, urlInicial, nomeInicial) {
    const modal = document.getElementById('anexosCarouselModal');
    const img = document.getElementById('anexosCarouselImg');
    const legenda = document.getElementById('anexosCarouselLegenda');
    const contador = document.getElementById('anexosCarouselContador');
    const prev = document.getElementById('anexosCarouselPrev');
    const next = document.getElementById('anexosCarouselNext');
    const fechar = document.getElementById('anexosCarouselFechar');
    if (!modal || !img) return;
    const list = Array.isArray(itens) && itens[0] && typeof itens[0] === 'object'
        ? itens
        : (itens || []).map(u => ({ url: u, nome: '' }));
    let idx = list.findIndex(x => x.url === urlInicial);
    if (idx < 0) idx = 0;
    const keyHandler = (e) => { if (e.key === 'Escape') fecharModal(); };
    const fecharModal = () => { modal.style.display = 'none'; modal.classList.add('hidden'); document.removeEventListener('keydown', keyHandler); };
    const atualizar = () => {
        const cur = list[idx];
        img.src = cur?.url || '';
        if (legenda) legenda.textContent = cur?.nome || nomeInicial || '';
        if (contador) contador.textContent = list.length > 1 ? (idx + 1) + ' / ' + list.length : '';
        if (prev) prev.style.visibility = list.length <= 1 ? 'hidden' : 'visible';
        if (next) next.style.visibility = list.length <= 1 ? 'hidden' : 'visible';
    };
    if (prev) prev.onclick = (e) => { e.stopPropagation(); idx = (idx - 1 + list.length) % list.length; atualizar(); };
    if (next) next.onclick = (e) => { e.stopPropagation(); idx = (idx + 1) % list.length; atualizar(); };
    if (fechar) fechar.onclick = (e) => { e.stopPropagation(); fecharModal(); };
    modal.onclick = (e) => { if (e.target === modal) fecharModal(); };
    document.addEventListener('keydown', keyHandler);
    atualizar();
    modal.style.display = 'flex';
    modal.classList.remove('hidden');
}

document.addEventListener('DOMContentLoaded', function() {
    const fileInput = document.getElementById('anexos_file_input');
    const pendentesWrapper = document.getElementById('anexos_pendentes_wrapper');
    const pendentesLista = document.getElementById('anexos_pendentes_lista');
    const salvarBtn = document.getElementById('anexos_salvar_btn');
    const form = document.getElementById('osForm');
    if (fileInput && pendentesWrapper && pendentesLista && salvarBtn && form && form.dataset.anexosUrl) {
        fileInput.addEventListener('change', function() {
            const files = Array.from(this.files || []);
            anexosPendentesFiles = files;
            pendentesLista.innerHTML = '';
            files.forEach((file, i) => {
                const isImg = file.type.startsWith('image/');
                const preview = isImg
                    ? `<img src="${URL.createObjectURL(file)}" alt="" class="h-14 w-14 rounded object-cover object-center">`
                    : `<div class="flex h-14 w-14 items-center justify-center rounded bg-gray-100 text-gray-500"><svg class="h-8 w-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg></div>`;
                const div = document.createElement('div');
                div.className = 'flex flex-wrap items-center gap-3 rounded-lg border border-gray-200 p-3 dark:border-gray-700';
                div.innerHTML = `
                    <div class="shrink-0">${preview}</div>
                    <div class="min-w-0 flex-1 space-y-2">
                        <p class="truncate text-sm font-medium">${file.name}</p>
                        <div class="grid grid-cols-1 gap-2 sm:grid-cols-3">
                            <input type="text" class="anexo-pendente-tipo rounded-lg border border-gray-300 px-2 py-1.5 text-sm dark:border-gray-700 dark:bg-gray-900 dark:text-white/90" placeholder="Tipo" list="anexo-tipos-datalist" data-index="${i}">
                            <input type="text" class="anexo-pendente-tags rounded-lg border border-gray-300 px-2 py-1.5 text-sm dark:border-gray-700 dark:bg-gray-900 dark:text-white/90" placeholder="Tags" data-index="${i}">
                            <input type="text" class="anexo-pendente-desc rounded-lg border border-gray-300 px-2 py-1.5 text-sm dark:border-gray-700 dark:bg-gray-900 dark:text-white/90" placeholder="Descrição" data-index="${i}">
                        </div>
                    </div>
                `;
                pendentesLista.appendChild(div);
            });
            pendentesWrapper.classList.toggle('hidden', files.length === 0);
            this.value = '';
        });

        salvarBtn.addEventListener('click', async function(e) {
            e.preventDefault();
            e.stopPropagation();
            if (anexosPendentesFiles.length === 0) return;
            salvarBtn.disabled = true;
            salvarBtn.textContent = 'Salvando...';
            const fd = new FormData();
            const csrf = document.querySelector('meta[name="csrf-token"]')?.content || document.querySelector('input[name="_token"]')?.value;
            if (csrf) fd.append('_token', csrf);
            anexosPendentesFiles.forEach((file, i) => fd.append('arquivos[' + i + ']', file, file.name));
            document.querySelectorAll('.anexo-pendente-tipo').forEach((inp, i) => fd.append('tipo[' + i + ']', inp.value || ''));
            document.querySelectorAll('.anexo-pendente-tags').forEach((inp, i) => fd.append('tags[' + i + ']', inp.value || ''));
            document.querySelectorAll('.anexo-pendente-desc').forEach((inp, i) => fd.append('descricao[' + i + ']', inp.value || ''));
            try {
                const res = await fetch(form.dataset.anexosUrl, {
                    method: 'POST',
                    headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json', ...(csrf ? { 'X-CSRF-TOKEN': csrf } : {}) },
                    credentials: 'same-origin',
                    body: fd
                });
                const data = await res.json().catch(() => ({}));
                if (res.ok && data.anexos) {
                    renderizarAnexosExistentes(data.anexos);
                    anexosPendentesFiles = [];
                    pendentesLista.innerHTML = '';
                    pendentesWrapper.classList.add('hidden');
                    if (typeof showToast === 'function') showToast(data.message || 'Anexos salvos.', 'success');
                } else {
                    const msg = data?.message || (res.status === 419 ? 'Sessão expirada. Atualize a página.' : 'Erro ao salvar anexos.');
                    if (typeof showToast === 'function') showToast(msg, 'error');
                }
            } catch (e) {
                if (typeof showToast === 'function') showToast('Erro: ' + e.message, 'error');
            }
            salvarBtn.disabled = false;
            salvarBtn.textContent = 'Salvar anexos';
        });
    }

    initAnexosCarouselAndDelete();
    initAnexosEdit();
});

function initSingleRichTextEditor(textarea) {
    if (!textarea || textarea.dataset.joditInited === '1' || typeof Jodit === 'undefined') {
        return;
    }
    textarea.dataset.joditInited = '1';
    const editor = Jodit.make(textarea, {
        theme: 'default',
        toolbarAdaptive: true,
        disablePlugins: 'source',
        buttons: [
            'bold', 'italic', 'underline', '|',
            'ul', 'ol', '|',
            'link', '|',
            'hr'
        ],
        height: 360
    });
    editor.events.on('change', () => {
        textarea.value = editor.value;
    });
}

function initRichTextEditors(onlyVisible = false) {
    if (typeof Jodit === 'undefined') return;
    document.querySelectorAll('.js-jodit').forEach(textarea => {
        if (onlyVisible) {
            const hiddenPanel = textarea.closest('.hidden');
            if (hiddenPanel) return;
        }
        initSingleRichTextEditor(textarea);
    });
}

function atualizarStatusOutro() {
    const select = document.getElementById('status_os');
    const outro = document.getElementById('status_outro');
    if (!select || !outro) return;
    const isOutro = select.value === 'Outro';
    outro.classList.toggle('hidden', !isOutro);
    if (!isOutro) {
        outro.value = '';
    }
}

function filtrarPorCliente() {
    const clienteSelect = document.getElementById('cliente_id');
    const contatoSelect = document.getElementById('contato_id');
    const enderecoSelect = document.getElementById('endereco_id');
    const unidadeSelect = document.getElementById('cliente_unidade_id');
    const equipamentoSelect = document.getElementById('equipamento_modal_id');

    if (!clienteSelect) return;
    const clienteId = clienteSelect.value;
    const selectedClienteOption = clienteSelect.options[clienteSelect.selectedIndex] || null;
    const grupo = selectedClienteOption?.dataset?.grupo || '';

    const updateSelectVisibility = (select, shouldShow, tryPrincipal = false) => {
        if (!select) return;
        const options = select.options;
        let selectedHidden = false;
        let selectedHasValue = !!select.value;
        let firstVisibleValue = '';
        let principalVisibleValue = '';

        for (let i = 0; i < options.length; i++) {
            const option = options[i];
            if (!option.value) continue;
            const visible = !!shouldShow(option);
            const nextHidden = !visible;
            if (option.hidden !== nextHidden) {
                option.hidden = nextHidden;
            }
            if (!nextHidden) {
                if (!firstVisibleValue) firstVisibleValue = option.value;
                if (!principalVisibleValue && option.dataset.principal === '1') {
                    principalVisibleValue = option.value;
                }
            }
            if (selectedHasValue && option.value === select.value && nextHidden) {
                selectedHidden = true;
            }
        }

        if (selectedHidden) {
            select.value = '';
            selectedHasValue = false;
        }

        if (!selectedHasValue) {
            const fallback = tryPrincipal ? (principalVisibleValue || firstVisibleValue) : firstVisibleValue;
            if (fallback) select.value = fallback;
        }
    };

    updateSelectVisibility(
        unidadeSelect,
        (option) => !!grupo && option.dataset.grupo === grupo,
        false
    );

    const baseClienteId = unidadeSelect?.value || clienteId;

    updateSelectVisibility(
        contatoSelect,
        (option) => option.dataset.cliente === baseClienteId,
        true
    );

    updateSelectVisibility(
        enderecoSelect,
        (option) => option.dataset.cliente === baseClienteId,
        true
    );

    updateSelectVisibility(
        equipamentoSelect,
        (option) => option.dataset.cliente === clienteId,
        false
    );
}

function showToast(message, type = 'success') {
    const container = document.getElementById('toastContainer');
    if (!container) return;
    const toast = document.createElement('div');
    const base = 'rounded-lg px-4 py-2 text-sm shadow-lg';
    const colors = type === 'error'
        ? 'bg-error-500 text-white'
        : 'bg-emerald-600 text-white';
    toast.className = `${base} ${colors}`;
    toast.textContent = message;
    container.appendChild(toast);
    setTimeout(() => {
        toast.classList.add('opacity-0');
        setTimeout(() => toast.remove(), 300);
    }, 2500);
}

document.addEventListener('DOMContentLoaded', () => {
    const statusSelect = document.getElementById('status_os');
    if (statusSelect) {
        statusSelect.addEventListener('change', atualizarStatusOutro);
        atualizarStatusOutro();
    }

    const clienteSelect = document.getElementById('cliente_id');
    const unidadeSelect = document.getElementById('cliente_unidade_id');
    const clienteBusca = document.getElementById('cliente_busca');
    if (clienteSelect) {
        clienteSelect.addEventListener('change', filtrarPorCliente);
        requestAnimationFrame(() => filtrarPorCliente());
    }
    if (unidadeSelect) {
        unidadeSelect.addEventListener('change', filtrarPorCliente);
    }
    if (clienteBusca && clienteSelect) {
        const syncByValue = (value) => {
            const match = Array.from(clienteSelect.options).find(opt => opt.textContent === value);
            clienteSelect.value = match ? match.value : '';
            filtrarPorCliente();
        };
        clienteBusca.addEventListener('change', (e) => syncByValue(e.target.value));
        clienteBusca.addEventListener('blur', (e) => syncByValue(e.target.value));
    }

    const tabsContainer = document.getElementById('os-tabs-nav');
    const getTabs = () => tabsContainer
        ? tabsContainer.querySelectorAll('.os-tab')
        : document.querySelectorAll('.os-tab');
    const getPanels = () => document.querySelectorAll('[data-tab-panel]');
    let activeTab = null;
    const setTab = (tab) => {
        if (activeTab === tab) return;
        const tabs = getTabs();
        const panels = getPanels();
        const saveScopeInput = document.getElementById('os_save_scope');
        tabs.forEach(btn => {
            const shouldBeActive = btn.dataset.tab === tab;
            if (btn.classList.contains('os-tab-active') !== shouldBeActive) {
                btn.classList.toggle('os-tab-active', shouldBeActive);
            }
        });
        panels.forEach(panel => {
            const shouldHide = panel.dataset.tabPanel !== tab;
            if (panel.classList.contains('hidden') !== shouldHide) {
                panel.classList.toggle('hidden', shouldHide);
            }
        });
        activeTab = tab;
        if (saveScopeInput) {
            if (tab === 'equipe') {
                saveScopeInput.value = 'equipe';
            } else if (tab === 'sla') {
                saveScopeInput.value = 'sla';
            } else {
                saveScopeInput.value = 'full';
            }
        }

        document.dispatchEvent(new CustomEvent('ordens-servico:tab-change', {
            detail: { tab }
        }));
    };
    if (tabsContainer) {
        tabsContainer.addEventListener('click', (event) => {
            const tabButton = event.target.closest('.os-tab');
            if (!tabButton) return;
            setTab(tabButton.dataset.tab);
        });
    } else {
        getTabs().forEach(btn => btn.addEventListener('click', () => setTab(btn.dataset.tab)));
    }
    setTab('cadastro');

    window.ordensServicoTabs = {
        setTab,
        getTabs: () => Array.from(getTabs()),
        getPanels: () => Array.from(getPanels()),
    };
    document.dispatchEvent(new CustomEvent('ordens-servico:tabs-ready', {
        detail: window.ordensServicoTabs
    }));

    const toggleButtons = document.querySelectorAll('[data-toggle-target]');
    toggleButtons.forEach(btn => {
        const targetId = btn.dataset.toggleTarget;
        const target = document.getElementById(targetId);
        if (!target) return;
        const isHiddenDefault = btn.dataset.toggleDefault === 'hidden';
        target.classList.toggle('hidden', isHiddenDefault);
        btn.textContent = isHiddenDefault ? 'Mostrar' : 'Ocultar';
        btn.addEventListener('click', () => {
            const willShow = target.classList.contains('hidden');
            target.classList.toggle('hidden', !willShow);
            btn.textContent = willShow ? 'Ocultar' : 'Mostrar';
        });
    });

    const descTabs = document.querySelectorAll('.os-desc-tab');
    const descPanels = document.querySelectorAll('[data-desc-panel]');
    let activeDescTab = null;
    const setDescTab = (tab) => {
        if (activeDescTab === tab) return;
        descTabs.forEach(btn => {
            const shouldBeActive = btn.dataset.descTab === tab;
            if (btn.classList.contains('os-tab-active') !== shouldBeActive) {
                btn.classList.toggle('os-tab-active', shouldBeActive);
            }
        });
        descPanels.forEach(panel => {
            const shouldHide = panel.dataset.descPanel !== tab;
            if (panel.classList.contains('hidden') !== shouldHide) {
                panel.classList.toggle('hidden', shouldHide);
            }
        });
        activeDescTab = tab;
    };
    descTabs.forEach(btn => btn.addEventListener('click', () => setDescTab(btn.dataset.descTab)));
    setDescTab('relato');

    const scheduleLowPriority = (callback) => {
        setTimeout(callback, 60);
    };

    // Editores ricos são pesados; inicializa sob demanda no foco.
    // Os demais são inicializados sob demanda ao focar o campo.
    document.addEventListener('focusin', (event) => {
        const target = event.target;
        if (target && target.matches && target.matches('textarea.js-jodit')) {
            initSingleRichTextEditor(target);
        }
    });

    const produtosContainer = document.getElementById('produtos_container');
    if (produtosContainer) {
        produtosContainer.addEventListener('change', (event) => {
            const target = event.target;
            if (target && target.matches('select[name^="produtos["][name$="[produto_id]"]')) {
                preencherProduto(target);
                calcularTotaisProdutos();
            }
            if (target && target.matches('.variacao-select')) {
                const wrapper = target.closest('[data-produto-index]');
                if (wrapper) atualizarExibicaoEstoque(wrapper);
            }
            if (target && target.matches('select[name^="produtos["][name$="[desconto_tipo]"]')) {
                const wrapper = target.closest('[data-produto-index]');
                if (wrapper) sincronizarDesconto(wrapper, 'produtos');
                calcularTotaisProdutos();
            }
            if (target && target.matches('input[name^="produtos["][name$="[quantidade]"], input[name^="produtos["][name$="[valor_unitario]"]')) {
                calcularTotaisProdutos();
            }
        });
        produtosContainer.addEventListener('input', (event) => {
            const target = event.target;
            if (target && target.matches('input[name^="produtos["][name$="[desconto_valor_unico]"]')) {
                const wrapper = target.closest('[data-produto-index]');
                if (wrapper) sincronizarDesconto(wrapper, 'produtos');
                calcularTotaisProdutos();
            }
            if (target && target.matches('input[name^="produtos["][name$="[quantidade]"], input[name^="produtos["][name$="[valor_unitario]"]')) {
                calcularTotaisProdutos();
            }
        });
        produtosContainer.addEventListener('focusout', (event) => {
            const target = event.target;
            if (target && target.matches('input[name^="produtos["][name$="[desconto_valor_unico]"]')) {
                const wrapper = target.closest('[data-produto-index]');
                const tipo = wrapper?.querySelector('select[name$="[desconto_tipo]"]');
                if (tipo) validarMensagemDescontoItem(tipo, target);
            }
        }, true);
    }
    if (produtosContainer) {
        produtosContainer.addEventListener('click', (event) => {
            const target = event.target.closest('.produto-imagem-thumb');
            if (!target) return;
            const imagens = JSON.parse(target.dataset.imagens || '[]');
            const index = parseInt(target.dataset.index || '0', 10);
            abrirModalImagens(imagens, index);
        });
    }
    const servicosContainer = document.getElementById('servicos_container');
    if (servicosContainer) {
        servicosContainer.addEventListener('change', (event) => {
            const target = event.target;
            if (target && target.matches('select[name^="servicos["][name$="[servico_id]"]')) {
                preencherServico(target);
                calcularTotaisServicos();
            }
            if (target && target.matches('[data-servico-cobranca-toggle]')) {
                const wrapper = target.closest('[data-servico-index]');
                if (wrapper) atualizarServicoCobranca(wrapper);
                calcularTotaisServicos();
            }
            if (target && target.matches('select[name^="servicos["][name$="[desconto_tipo]"]')) {
                const wrapper = target.closest('[data-servico-index]');
                if (wrapper) sincronizarDesconto(wrapper, 'servicos');
                calcularTotaisServicos();
            }
            if (target && target.matches('input[name^="servicos["][name$="[valor_unitario]"]')) {
                calcularTotaisServicos();
            }
        });
        servicosContainer.addEventListener('input', (event) => {
            const target = event.target;
            if (target && target.matches('input[name^="servicos["][name$="[desconto_valor_unico]"]')) {
                const wrapper = target.closest('[data-servico-index]');
                if (wrapper) sincronizarDesconto(wrapper, 'servicos');
                calcularTotaisServicos();
            }
            if (target && target.matches('input[name^="servicos["][name$="[quantidade_horas]"]')) {
                calcularTotaisServicos();
            }
            if (target && target.matches('input[name^="servicos["][name$="[quantidade]"]')) {
                calcularTotaisServicos();
            }
            if (target && target.matches('input[name^="servicos["][name$="[valor_unitario]"]')) {
                calcularTotaisServicos();
            }
        });
        servicosContainer.addEventListener('focusout', (event) => {
            const target = event.target;
            if (target && target.matches('input[name^="servicos["][name$="[desconto_valor_unico]"]')) {
                const wrapper = target.closest('[data-servico-index]');
                const tipo = wrapper?.querySelector('select[name$="[desconto_tipo]"]');
                if (tipo) validarMensagemDescontoItem(tipo, target);
            }
        }, true);
    }
    let itensInicializados = false;
    const processarEmLotes = (items, handler, done, chunkSize = 8) => {
        let index = 0;
        const total = items.length;
        const next = () => {
            const end = Math.min(index + chunkSize, total);
            for (let i = index; i < end; i++) {
                handler(items[i], i);
            }
            index = end;
            if (index < total) {
                requestAnimationFrame(next);
            } else if (typeof done === 'function') {
                done();
            }
        };
        requestAnimationFrame(next);
    };
    const inicializarItensQuandoNecessario = () => {
        if (itensInicializados) return;
        itensInicializados = true;
        const tabelaPrecoSelect = document.getElementById('tabela_preco');
        if (tabelaPrecoSelect) {
            tabelaPrecoSelect.addEventListener('change', () => {
                document.querySelectorAll('select[name^="produtos["][name$="[produto_id]"]').forEach(select => {
                    if (select.value) preencherProduto(select);
                });
            });
        }
        const produtosSelects = Array.from(document.querySelectorAll('select[name^="produtos["][name$="[produto_id]"]'));
        const produtoWrappers = Array.from(document.querySelectorAll('[data-produto-index]'));
        const servicosSelects = Array.from(document.querySelectorAll('select[name^="servicos["][name$="[servico_id]"]'));
        const servicoWrappers = Array.from(document.querySelectorAll('[data-servico-index]'));

        processarEmLotes(produtosSelects, (select) => {
            if (select.value) preencherProduto(select, false); // false = não sobrescrever valores já carregados do servidor
        }, () => {
            processarEmLotes(produtoWrappers, (wrapper) => {
                sincronizarDesconto(wrapper, 'produtos');
            }, () => {
                processarEmLotes(servicosSelects, (select) => {
                    if (select.value) preencherServico(select, false); // false = não sobrescrever valores já carregados do servidor
                }, () => {
                    processarEmLotes(servicoWrappers, (wrapper) => {
                        sincronizarDesconto(wrapper, 'servicos');
                        atualizarServicoCobranca(wrapper);
                    }, () => {
                        requestAnimationFrame(() => {
                            calcularTotaisProdutos();
                            calcularTotaisServicos();
                        });
                    });
                });
            });
        });
    };

    document.addEventListener('ordens-servico:tab-change', (event) => {
        if (event?.detail?.tab === 'itens') {
            scheduleLowPriority(inicializarItensQuandoNecessario);
        }
    });

    const modalImagem = document.getElementById('produtoImagemModal');
    const modalImagemAtual = document.getElementById('produtoImagemAtual');
    const modalThumbs = document.getElementById('produtoImagemThumbs');
    const btnPrev = document.getElementById('produtoImagemPrev');
    const btnNext = document.getElementById('produtoImagemNext');
    let imagensAtuais = [];
    let indiceAtual = 0;

    function renderizarModal() {
        if (!modalImagemAtual) return;
        modalImagemAtual.src = imagensAtuais[indiceAtual] || '';
        if (modalThumbs) {
            modalThumbs.innerHTML = imagensAtuais.map((url, idx) => (
                `<button type="button" class="h-12 w-12 rounded border ${idx === indiceAtual ? 'border-brand-500' : 'border-gray-200'}">
                    <img src="${url}" alt="Imagem" class="h-12 w-12 rounded object-cover" data-thumb-index="${idx}">
                </button>`
            )).join('');
        }
        if (btnPrev) btnPrev.disabled = imagensAtuais.length <= 1;
        if (btnNext) btnNext.disabled = imagensAtuais.length <= 1;
    }

    function abrirModalImagens(imagens, index) {
        imagensAtuais = imagens || [];
        indiceAtual = Math.min(Math.max(index || 0, 0), imagensAtuais.length - 1);
        if (modalImagem) {
            modalImagem.classList.remove('hidden');
            renderizarModal();
        }
    }

    if (btnPrev) {
        btnPrev.addEventListener('click', () => {
            if (!imagensAtuais.length) return;
            indiceAtual = (indiceAtual - 1 + imagensAtuais.length) % imagensAtuais.length;
            renderizarModal();
        });
    }
    if (btnNext) {
        btnNext.addEventListener('click', () => {
            if (!imagensAtuais.length) return;
            indiceAtual = (indiceAtual + 1) % imagensAtuais.length;
            renderizarModal();
        });
    }
    if (modalThumbs) {
        modalThumbs.addEventListener('click', (event) => {
            const thumb = event.target.closest('img');
            if (!thumb) return;
            const idx = parseInt(thumb.dataset.thumbIndex || '0', 10);
            indiceAtual = idx;
            renderizarModal();
        });
    }
    document.querySelectorAll('[data-produto-imagem-close]').forEach(btn => {
        btn.addEventListener('click', () => {
            if (modalImagem) modalImagem.classList.add('hidden');
        });
    });

    const form = document.getElementById('osForm');
    if (form) {
        form.addEventListener('submit', async (event) => {
            const getTabAtiva = () => {
                const ativa = document.querySelector('.os-tab.os-tab-active');
                if (ativa?.dataset?.tab) {
                    return ativa.dataset.tab;
                }
                const painelVisivel = Array.from(document.querySelectorAll('[data-tab-panel]'))
                    .find((panel) => !panel.classList.contains('hidden'));
                return painelVisivel?.dataset?.tabPanel || 'cadastro';
            };
            const tabAtiva = getTabAtiva();
            const isEquipeSave = tabAtiva === 'equipe';
            const isSlaSave = tabAtiva === 'sla';
            const isLightweightSave = isEquipeSave || isSlaSave;
            const saveScopeInput = document.getElementById('os_save_scope');
            if (saveScopeInput) {
                if (isEquipeSave) {
                    saveScopeInput.value = 'equipe';
                } else if (isSlaSave) {
                    saveScopeInput.value = 'sla';
                } else {
                    saveScopeInput.value = 'full';
                }
            }

            document.querySelectorAll('.js-jodit').forEach(textarea => {
                const instance = textarea.jodit;
                if (instance) {
                    textarea.value = instance.value;
                }
            });
            const isAjaxSave = form.dataset.ajaxSave === '1';
            if (!isAjaxSave) {
                return;
            }
            event.preventDefault();
            event.stopPropagation();

            if (window.__osFormSaveInFlight) {
                return;
            }
            window.__osFormSaveInFlight = true;
            const submitButtonsSave = form.querySelectorAll('button[type="submit"]');
            submitButtonsSave.forEach((b) => { b.disabled = true; });

            // Se houver anexos pendentes, salvá-los primeiro antes de enviar o formulário
            if (!isLightweightSave && typeof anexosPendentesFiles !== 'undefined' && anexosPendentesFiles.length > 0 && form.dataset.anexosUrl) {
                const fd = new FormData();
                const csrf = document.querySelector('meta[name="csrf-token"]')?.content || document.querySelector('input[name="_token"]')?.value;
                if (csrf) fd.append('_token', csrf);
                anexosPendentesFiles.forEach((file, i) => fd.append('arquivos[' + i + ']', file, file.name));
                document.querySelectorAll('.anexo-pendente-tipo').forEach((inp, i) => fd.append('tipo[' + i + ']', inp.value || ''));
                document.querySelectorAll('.anexo-pendente-tags').forEach((inp, i) => fd.append('tags[' + i + ']', inp.value || ''));
                document.querySelectorAll('.anexo-pendente-desc').forEach((inp, i) => fd.append('descricao[' + i + ']', inp.value || ''));
                try {
                    const resAnexos = await fetch(form.dataset.anexosUrl, {
                        method: 'POST',
                        headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json', ...(csrf ? { 'X-CSRF-TOKEN': csrf } : {}) },
                        credentials: 'same-origin',
                        body: fd
                    });
                    const dataAnexos = await resAnexos.json().catch(() => ({}));
                    if (resAnexos.ok && dataAnexos.anexos && typeof renderizarAnexosExistentes === 'function') {
                        renderizarAnexosExistentes(dataAnexos.anexos);
                        anexosPendentesFiles.length = 0;
                        const pl = document.getElementById('anexos_pendentes_lista');
                        const pw = document.getElementById('anexos_pendentes_wrapper');
                        if (pl) pl.innerHTML = '';
                        if (pw) pw.classList.add('hidden');
                    }
                } catch (e) {
                    if (typeof showToast === 'function') showToast('Erro ao salvar anexos antes de continuar: ' + e.message, 'error');
                    window.__osFormSaveInFlight = false;
                    submitButtonsSave.forEach((b) => { b.disabled = false; });
                    return;
                }
            }

            // Garantir que o campo de padrão está atualizado ANTES de criar o FormData
            const unlockType = form.querySelector('#unlock_type')?.value;
            
            // Se for padrão, garantir que o valor está atualizado
            if (!isLightweightSave && unlockType === 'pattern') {
                const patternInput = form.querySelector('#pattern_code_input');
                if (patternInput) {
                    // Tentar pegar o padrão do componente global
                    let currentPattern = null;
                    if (window.patternComponent) {
                        currentPattern = window.patternComponent.getPattern();
                    } else {
                        // Tentar pegar do container
                        const patternContainer = document.getElementById('unlock_pattern_container');
                        if (patternContainer && patternContainer.patternComponent) {
                            currentPattern = patternContainer.patternComponent.getPattern();
                        }
                    }
                    
                    if (currentPattern && currentPattern.length > 0) {
                        const patternValue = JSON.stringify(currentPattern);
                        patternInput.value = patternValue;
                        patternInput.disabled = false;
                        patternInput.setAttribute('name', 'equipamento_unlock_code');
                    } else if (!patternInput.value || patternInput.value === '[]') {
                        // Se não houver padrão e o campo estiver vazio, limpar
                        patternInput.value = '';
                    }
                    // Se o campo já tem valor, manter (pode ter sido atualizado pelo callback)
                }
            }
            
            // Garantir que descontos visíveis estão sincronizados nos hidden antes de enviar
            if (!isLightweightSave) {
                document.querySelectorAll('[data-produto-index]').forEach(w => { if (typeof sincronizarDesconto === 'function') sincronizarDesconto(w, 'produtos'); });
                document.querySelectorAll('[data-servico-index]').forEach(w => { if (typeof sincronizarDesconto === 'function') sincronizarDesconto(w, 'servicos'); });
                const descontoUnicos = form.querySelectorAll('input[name$="[desconto_valor_unico]"]');
                for (const inp of descontoUnicos) {
                    if (!inp.checkValidity()) {
                        inp.reportValidity();
                        window.__osFormSaveInFlight = false;
                        submitButtonsSave.forEach((b) => { b.disabled = false; });
                        return;
                    }
                }
            }

            let formData;
            if (isLightweightSave) {
                formData = new FormData();
                const csrf = document.querySelector('meta[name="csrf-token"]')?.content || document.querySelector('input[name="_token"]')?.value;
                if (csrf) formData.append('_token', csrf);
                formData.append('save_scope', isEquipeSave ? 'equipe' : 'sla');
                const methodInput = form.querySelector('input[name="_method"]');
                if (methodInput?.value) {
                    formData.append('_method', methodInput.value);
                }

                const panel = document.querySelector(`[data-tab-panel="${tabAtiva}"]`);
                if (panel) {
                    panel.querySelectorAll('input[name], select[name], textarea[name]').forEach((field) => {
                        if (!field.name || field.disabled) return;
                        const type = (field.type || '').toLowerCase();
                        if ((type === 'checkbox' || type === 'radio') && !field.checked) return;
                        if (type === 'file') return;
                        formData.append(field.name, field.value ?? '');
                    });
                }
            } else {
                formData = new FormData(form);
            }
            
            // Remover TODOS os campos de unlock_code primeiro (pode haver múltiplos)
            const allUnlockCodes = formData.getAll('equipamento_unlock_code');
            allUnlockCodes.forEach(() => formData.delete('equipamento_unlock_code'));
            
            // Adicionar apenas o campo correto baseado no tipo selecionado
            if (!isLightweightSave && unlockType === 'pattern') {
                const patternInput = form.querySelector('#pattern_code_input');
                let patternValue = patternInput?.value || '';
                
                // Se o campo estiver vazio, tentar pegar do componente novamente
                if ((!patternValue || patternValue === '[]') && window.patternComponent) {
                    const currentPattern = window.patternComponent.getPattern();
                    if (currentPattern && currentPattern.length > 0) {
                        patternValue = JSON.stringify(currentPattern);
                        if (patternInput) {
                            patternInput.value = patternValue;
                        }
                    }
                }
                
                if (patternValue && patternValue !== '[]' && patternValue.trim() !== '') {
                    formData.append('equipamento_unlock_code', patternValue);
                }
            } else if (!isLightweightSave && unlockType === 'pin') {
                const pinInput = form.querySelector('#pin_code_input');
                if (pinInput && pinInput.value) {
                    formData.append('equipamento_unlock_code', pinInput.value);
                }
            } else if (!isLightweightSave && unlockType === 'none') {
                formData.append('equipamento_unlock_code', '');
            }
            
            // Garantir que o tipo está sendo enviado
            if (!isLightweightSave && unlockType) {
                formData.set('equipamento_unlock_type', unlockType);
            }
            
            const limparErros = () => {
                form.querySelectorAll('[data-has-error="1"]').forEach(el => {
                    el.classList.remove('border-error-500');
                    el.removeAttribute('data-has-error');
                });
                form.querySelectorAll('[data-error-msg]').forEach(el => el.remove());
            };
            const aplicarErros = (errors) => {
                if (!errors) return;
                Object.keys(errors).forEach((key) => {
                    const selector = `[name="${key}"]`;
                    const field = form.querySelector(selector);
                    if (!field) return;
                    field.classList.add('border-error-500');
                    field.setAttribute('data-has-error', '1');
                    const msg = document.createElement('div');
                    msg.className = 'mt-1 text-xs text-error-500';
                    msg.textContent = errors[key][0] || 'Campo inválido.';
                    msg.setAttribute('data-error-msg', '1');
                    field.parentElement?.appendChild(msg);
                });
            };
            try {
                limparErros();
                const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content || document.querySelector('input[name="_token"]')?.value;
                // Se for edição, garantir que o método PUT esteja no FormData
                const methodInput = form.querySelector('input[name="_method"]');
                if (methodInput && methodInput.value === 'PUT') {
                    formData.set('_method', 'PUT');
                }
                const response = await fetch(form.action, {
                    method: 'POST',
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest',
                        'Accept': 'application/json',
                        ...(csrfToken ? { 'X-CSRF-TOKEN': csrfToken } : {}),
                    },
                    credentials: 'same-origin',
                    body: formData,
                });
                if (!response.ok) {
                    let message = 'Erro ao salvar.';
                    if (response.status === 419) {
                        message = 'Sessão expirada. Atualize a página e tente novamente.';
                    } else if (response.status === 422) {
                        const data = await response.json().catch(() => null);
                        message = data?.message || 'Erro de validação.';
                        aplicarErros(data?.errors || {});
                    } else {
                        const text = await response.text().catch(() => '');
                        message = `Erro ${response.status}: ${text.substring(0, 100)}`;
                    }
                    showToast(message, 'error');
                    return;
                }
                const contentType = response.headers.get('content-type') || '';
                if (!contentType.includes('application/json')) {
                    showToast('Resposta inválida do servidor. Tente novamente.', 'error');
                    return;
                }
                const result = await response.json().catch(() => null);
                showToast(result?.message || 'Salvo com sucesso.', 'success');
                if (result?.anexos && Array.isArray(result.anexos)) {
                    renderizarAnexosExistentes(result.anexos);
                }
                if (result?.notificacao_preview) {
                    const p = result.notificacao_preview;
                    const notifEmailAssunto = document.getElementById('notifEmailAssunto');
                    const notifEmailMensagem = document.getElementById('notifEmailMensagem');
                    const notifEmailPreview = document.getElementById('notifEmailPreview');
                    const notifWhatsappPreview = document.getElementById('notifWhatsappPreview');
                    const notifWhatsappLink = document.getElementById('notifWhatsappLink');
                    if (notifEmailAssunto && p.hasOwnProperty('email_assunto')) notifEmailAssunto.value = p.email_assunto;
                    if (notifEmailMensagem && p.hasOwnProperty('email_corpo')) {
                        notifEmailMensagem.value = p.email_corpo;
                        if (notifEmailPreview && typeof linkifyHtml === 'function') notifEmailPreview.innerHTML = linkifyHtml(p.email_corpo);
                    }
                    if (notifWhatsappPreview && p.hasOwnProperty('whatsapp_mensagem')) {
                        if (typeof linkifyPreviewText === 'function') {
                            notifWhatsappPreview.innerHTML = linkifyPreviewText(p.whatsapp_mensagem);
                        } else {
                            notifWhatsappPreview.textContent = p.whatsapp_mensagem;
                        }
                    }
                    if (notifWhatsappLink && p.hasOwnProperty('whatsapp_mensagem')) {
                        const msg = p.whatsapp_mensagem ? encodeURIComponent(p.whatsapp_mensagem) : '';
                        const digits = p.whatsapp_digits || '';
                        notifWhatsappLink.href = digits ? ('https://wa.me/' + digits + (msg ? '?text=' + msg : '')) : '#';
                    }
                }
                return { ok: true };
            } catch (error) {
                showToast('Erro ao salvar: ' + error.message, 'error');
                throw error;
            } finally {
                window.__osFormSaveInFlight = false;
                submitButtonsSave.forEach((b) => { b.disabled = false; });
            }
        });
        // Função global para salvar antes de encerrar (persiste garantia e outros dados)
        window.salvarOsFormAsync = async function(silent = false) {
            const f = document.getElementById('osForm');
            if (!f || f.dataset.ajaxSave !== '1') return { ok: true };
            if (window.__osFormSaveInFlight) {
                for (let i = 0; i < 120 && window.__osFormSaveInFlight; i++) {
                    await new Promise((r) => setTimeout(r, 50));
                }
            }
            document.querySelectorAll('.js-jodit').forEach(ta => { if (ta.jodit) ta.value = ta.jodit.value; });
            document.querySelectorAll('[data-produto-index]').forEach(w => { if (typeof sincronizarDesconto === 'function') sincronizarDesconto(w, 'produtos'); });
            document.querySelectorAll('[data-servico-index]').forEach(w => { if (typeof sincronizarDesconto === 'function') sincronizarDesconto(w, 'servicos'); });
            const unlockType = f.querySelector('#unlock_type')?.value;
            if (unlockType === 'pattern') {
                const patternInput = f.querySelector('#pattern_code_input');
                if (patternInput) {
                    let currentPattern = window.patternComponent?.getPattern() ?? document.getElementById('unlock_pattern_container')?.patternComponent?.getPattern?.();
                    if (currentPattern?.length > 0) patternInput.value = JSON.stringify(currentPattern);
                }
            }
            const formData = new FormData(f);
            formData.getAll('equipamento_unlock_code').forEach(() => formData.delete('equipamento_unlock_code'));
            if (unlockType === 'pattern' && f.querySelector('#pattern_code_input')?.value) formData.append('equipamento_unlock_code', f.querySelector('#pattern_code_input').value);
            else if (unlockType === 'pin' && f.querySelector('#pin_code_input')?.value) formData.append('equipamento_unlock_code', f.querySelector('#pin_code_input').value);
            else if (unlockType === 'none') formData.append('equipamento_unlock_code', '');
            if (unlockType) formData.set('equipamento_unlock_type', unlockType);
            if (f.querySelector('input[name="_method"]')?.value === 'PUT') formData.append('_method', 'PUT');
            const csrf = document.querySelector('meta[name="csrf-token"]')?.content || f.querySelector('input[name="_token"]')?.value;
            const res = await fetch(f.action, { method: 'POST', headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json', ...(csrf ? { 'X-CSRF-TOKEN': csrf } : {}) }, credentials: 'same-origin', body: formData });
            if (!res.ok) {
                const err = await res.json().catch(() => ({}));
                if (!silent && typeof showToast === 'function') showToast(err?.message || 'Erro ao salvar.', 'error');
                throw new Error(err?.message || 'Erro ao salvar.');
            }
            return { ok: true };
        };
    }

    // Guard de camadas para modais da OS: evita header/sidebar sticky sobrepor o modal.
    let osModalLayerOpenCount = 0;
    const osLayerTargets = () => ([
        document.querySelector('header.sticky'),
        document.getElementById('sidebar'),
        document.getElementById('sidebar-backdrop'),
    ].filter(Boolean));
    const osAdjustLayoutLayers = (open) => {
        const targets = osLayerTargets();
        if (open) {
            osModalLayerOpenCount += 1;
            if (osModalLayerOpenCount > 1) return;
            targets.forEach((el) => {
                if (el.dataset.osModalPrevZ === undefined) {
                    el.dataset.osModalPrevZ = el.style.zIndex || '';
                }
                el.style.zIndex = '1';
            });
            return;
        }
        osModalLayerOpenCount = Math.max(0, osModalLayerOpenCount - 1);
        if (osModalLayerOpenCount > 0) return;
        targets.forEach((el) => {
            if (el.dataset.osModalPrevZ !== undefined) {
                el.style.zIndex = el.dataset.osModalPrevZ;
                delete el.dataset.osModalPrevZ;
            } else {
                el.style.zIndex = '';
            }
        });
    };
    window.__osAdjustLayoutLayers = osAdjustLayoutLayers;

    const openButtons = document.querySelectorAll('[data-modal-open]');
    const closeButtons = document.querySelectorAll('[data-modal-close]');
    openButtons.forEach(btn => {
        btn.addEventListener('click', () => {
            const target = document.getElementById(btn.dataset.modalOpen);
            if (target) {
                target.classList.remove('hidden');
                osAdjustLayoutLayers(true);
            }
        });
    });
    closeButtons.forEach(btn => {
        btn.addEventListener('click', () => {
            const target = document.getElementById(btn.dataset.modalClose);
            if (target) {
                target.classList.add('hidden');
                osAdjustLayoutLayers(false);
            }
        });
    });

    (function bindAvulsoDescontoLimite() {
        const tipoProd = document.getElementById('produtoAvulsoDescontoTipo');
        const valProd = document.getElementById('produtoAvulsoDescontoValor');
        const tipoServ = document.getElementById('servicoAvulsoDescontoTipo');
        const valServ = document.getElementById('servicoAvulsoDescontoValor');
        const syncAvulso = (tipo, val) => {
            if (!tipo || !val) return;
            atualizarLimiteCampoDescontoItem(tipo, val);
            validarMensagemDescontoItem(tipo, val);
        };
        if (tipoProd && valProd) {
            tipoProd.addEventListener('change', () => syncAvulso(tipoProd, valProd));
            valProd.addEventListener('input', () => syncAvulso(tipoProd, valProd));
            valProd.addEventListener('focusout', () => syncAvulso(tipoProd, valProd));
            syncAvulso(tipoProd, valProd);
        }
        if (tipoServ && valServ) {
            tipoServ.addEventListener('change', () => syncAvulso(tipoServ, valServ));
            valServ.addEventListener('input', () => syncAvulso(tipoServ, valServ));
            valServ.addEventListener('focusout', () => syncAvulso(tipoServ, valServ));
            syncAvulso(tipoServ, valServ);
        }
    })();

    const produtoAvulsoSalvar = document.getElementById('produtoAvulsoSalvar');
    if (produtoAvulsoSalvar) {
        produtoAvulsoSalvar.addEventListener('click', () => {
            const descricao = document.getElementById('produtoAvulsoDescricao')?.value?.trim() || '';
            const quantidade = document.getElementById('produtoAvulsoQuantidade')?.value || '1';
            const valor = document.getElementById('produtoAvulsoValor')?.value || '';
            const descontoTipo = document.getElementById('produtoAvulsoDescontoTipo')?.value || 'valor';
            const descontoValor = document.getElementById('produtoAvulsoDescontoValor')?.value || '';
            const descontoInputModal = document.getElementById('produtoAvulsoDescontoValor');
            const descontoTipoModal = document.getElementById('produtoAvulsoDescontoTipo');
            if (descontoInputModal && descontoTipoModal) {
                atualizarLimiteCampoDescontoItem(descontoTipoModal, descontoInputModal);
                validarMensagemDescontoItem(descontoTipoModal, descontoInputModal);
                if (!descontoInputModal.checkValidity()) {
                    descontoInputModal.reportValidity();
                    return;
                }
            }
            if (!descricao || !valor) {
                alert('Informe descrição e valor do produto avulso.');
                return;
            }
            adicionarProduto();
            const container = document.getElementById('produtos_container');
            const wrapper = container?.lastElementChild;
            if (!wrapper) return;
            const select = wrapper.querySelector('select[name^="produtos["][name$="[produto_id]"]');
            if (select) {
                select.value = '';
                select.disabled = true;
            }
            const quantidadeInput = wrapper.querySelector('input[name$="[quantidade]"]');
            if (quantidadeInput) quantidadeInput.value = quantidade;
            const valorInput = wrapper.querySelector('input[name$="[valor_unitario]"]');
            if (valorInput) valorInput.value = valor;
            const descricaoInput = wrapper.querySelector('input[name$="[descricao]"]');
            if (descricaoInput) descricaoInput.value = descricao;
            const tipoSelect = wrapper.querySelector('select[name$="[desconto_tipo]"]');
            const descontoInput = wrapper.querySelector('input[name$="[desconto_valor_unico]"]');
            if (tipoSelect) tipoSelect.value = descontoTipo;
            if (descontoInput) descontoInput.value = descontoValor;
            sincronizarDesconto(wrapper, 'produtos');
            calcularTotaisProdutos();
            const modal = document.getElementById('produtoAvulsoModal');
            if (modal) modal.classList.add('hidden');
        });
    }

    const servicoAvulsoToggle = document.getElementById('servicoAvulsoCobrarHoras');
    const servicoAvulsoQuantidadeWrap = document.getElementById('servicoAvulsoQuantidadeWrap');
    const servicoAvulsoHorasWrap = document.getElementById('servicoAvulsoHorasWrap');
    if (servicoAvulsoToggle) {
        servicoAvulsoToggle.addEventListener('change', () => {
            const isHoras = servicoAvulsoToggle.checked;
            if (servicoAvulsoQuantidadeWrap) servicoAvulsoQuantidadeWrap.classList.toggle('hidden', isHoras);
            if (servicoAvulsoHorasWrap) servicoAvulsoHorasWrap.classList.toggle('hidden', !isHoras);
        });
    }

    const servicoAvulsoSalvar = document.getElementById('servicoAvulsoSalvar');
    if (servicoAvulsoSalvar) {
        servicoAvulsoSalvar.addEventListener('click', () => {
            const descricao = document.getElementById('servicoAvulsoDescricao')?.value?.trim() || '';
            const valor = document.getElementById('servicoAvulsoValor')?.value || '';
            const tecnico = document.getElementById('servicoAvulsoTecnico')?.value || '';
            const descontoTipo = document.getElementById('servicoAvulsoDescontoTipo')?.value || 'valor';
            const descontoValor = document.getElementById('servicoAvulsoDescontoValor')?.value || '';
            const descontoInputModalS = document.getElementById('servicoAvulsoDescontoValor');
            const descontoTipoModalS = document.getElementById('servicoAvulsoDescontoTipo');
            if (descontoInputModalS && descontoTipoModalS) {
                atualizarLimiteCampoDescontoItem(descontoTipoModalS, descontoInputModalS);
                validarMensagemDescontoItem(descontoTipoModalS, descontoInputModalS);
                if (!descontoInputModalS.checkValidity()) {
                    descontoInputModalS.reportValidity();
                    return;
                }
            }
            const cobrarHoras = document.getElementById('servicoAvulsoCobrarHoras')?.checked || false;
            const quantidade = document.getElementById('servicoAvulsoQuantidade')?.value || '1';
            const horas = document.getElementById('servicoAvulsoHoras')?.value || '1';
            if (!descricao || !valor) {
                alert('Informe descrição e valor do serviço avulso.');
                return;
            }
            adicionarServico();
            const container = document.getElementById('servicos_container');
            const wrapper = container?.lastElementChild;
            if (!wrapper) return;
            const select = wrapper.querySelector('select[name^="servicos["][name$="[servico_id]"]');
            if (select) {
                select.value = '';
                select.disabled = true;
            }
            const descricaoInput = wrapper.querySelector('input[name$="[descricao]"]');
            const valorInput = wrapper.querySelector('input[name$="[valor_unitario]"]');
            const tecnicoSelect = wrapper.querySelector('select[name$="[tecnico_id]"]');
            if (descricaoInput) descricaoInput.value = descricao;
            if (valorInput) valorInput.value = valor;
            if (tecnicoSelect) tecnicoSelect.value = tecnico;
            const toggle = wrapper.querySelector('[data-servico-cobranca-toggle]');
            if (toggle) toggle.checked = cobrarHoras;
            const quantidadeInput = wrapper.querySelector('input[name$="[quantidade]"]');
            const horasInput = wrapper.querySelector('input[name$="[quantidade_horas]"]');
            if (quantidadeInput) quantidadeInput.value = quantidade;
            if (horasInput) horasInput.value = horas;
            atualizarServicoCobranca(wrapper);
            const tipoSelect = wrapper.querySelector('select[name$="[desconto_tipo]"]');
            const descontoInput = wrapper.querySelector('input[name$="[desconto_valor_unico]"]');
            if (tipoSelect) tipoSelect.value = descontoTipo;
            if (descontoInput) descontoInput.value = descontoValor;
            sincronizarDesconto(wrapper, 'servicos');
            calcularTotaisServicos();
            const modal = document.getElementById('servicoAvulsoModal');
            if (modal) modal.classList.add('hidden');
        });
    }

    const salvarClienteBtn = document.getElementById('osClienteSalvar');
    if (salvarClienteBtn) {
        salvarClienteBtn.addEventListener('click', async () => {
            const form = document.getElementById('osForm');
            const updateUrl = form?.dataset.clienteUpdateUrl;
            if (!updateUrl) return;

            const payload = {
                cliente_id: document.getElementById('cliente_id')?.value || null,
                cliente_unidade_id: document.getElementById('cliente_unidade_id')?.value || null,
                contato_id: document.getElementById('contato_id')?.value || null,
                endereco_id: document.getElementById('endereco_id')?.value || null,
            };

            const response = await fetch(updateUrl, {
                method: 'PATCH',
                headers: {
                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                    'Accept': 'application/json',
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify(payload),
            });

            if (!response.ok) {
                showToast('Erro ao atualizar cliente.', 'error');
                return;
            }

            const data = await response.json();
            const nome = document.getElementById('cliente_nome');
            const endereco = document.getElementById('cliente_endereco');
            const enderecoLink = document.getElementById('cliente_endereco_link');
            const tel = document.getElementById('cliente_contato_telefone');
            const telLink = document.getElementById('cliente_contato_telefone_link');
            const contatoNome = document.getElementById('cliente_contato_nome');
            const email = document.getElementById('cliente_contato_email');
            const whatsapp = document.getElementById('cliente_whatsapp');
            const whatsappLink = document.getElementById('cliente_whatsapp_link');
            const contatosResumo = document.getElementById('cliente_contatos_resumo');

            if (nome) nome.textContent = data.cliente_nome || '-';
            if (endereco) endereco.textContent = data.endereco_texto || '-';
            if (tel) tel.textContent = data.contato_telefone || '-';
            if (contatoNome) contatoNome.textContent = data.contato_nome || '-';
            if (email) email.textContent = data.contato_email || '-';

            if (enderecoLink) {
                const raw = (data.endereco_texto || '').trim();
                enderecoLink.href = raw && raw !== '-' ? ('https://www.google.com/maps/search/?api=1&query=' + encodeURIComponent(raw)) : '#';
            }
            if (telLink) {
                const digits = String(data.contato_telefone || '').replace(/\D+/g, '');
                telLink.href = digits ? ('tel:' + digits) : '#';
            }

            if (whatsapp) {
                if (data.whatsapp) {
                    whatsapp.innerHTML = `
                        <span class="inline-flex items-center gap-1">
                            <svg class="h-4 w-4 text-green-500" viewBox="0 0 24 24" aria-hidden="true">
                                <path fill="currentColor" d="M12.04 2C6.52 2 2 6.48 2 12c0 1.94.54 3.83 1.56 5.47L2 22l4.74-1.54A9.96 9.96 0 0 0 12.04 22C17.56 22 22 17.52 22 12S17.56 2 12.04 2zm0 18.2c-1.74 0-3.44-.5-4.9-1.45l-.35-.22-2.8.9.92-2.72-.23-.35A8.18 8.18 0 0 1 3.8 12c0-4.53 3.7-8.2 8.24-8.2 4.54 0 8.16 3.67 8.16 8.2 0 4.53-3.62 8.2-8.16 8.2zm4.6-6.14c-.25-.12-1.47-.72-1.7-.8-.23-.08-.39-.12-.56.12-.17.25-.64.8-.78.96-.14.17-.29.19-.53.07-.25-.12-1.05-.38-2-1.2-.74-.66-1.24-1.47-1.38-1.72-.14-.25-.02-.38.1-.5.1-.1.25-.29.37-.43.12-.14.17-.25.25-.41.08-.17.04-.31-.02-.43-.06-.12-.56-1.35-.77-1.85-.2-.48-.4-.41-.56-.42l-.48-.01c-.17 0-.43.06-.66.31-.23.25-.87.85-.87 2.07 0 1.22.9 2.4 1.02 2.56.12.17 1.77 2.7 4.3 3.79.6.26 1.07.42 1.44.54.61.19 1.17.16 1.61.1.49-.07 1.47-.6 1.68-1.18.2-.58.2-1.08.14-1.18-.06-.1-.22-.16-.47-.28z"/>
                            </svg>
                            ${data.whatsapp}
                        </span>
                    `;
                } else {
                    whatsapp.textContent = '-';
                }
            }
            if (whatsappLink) {
                const digits = (data.whatsapp_digits || String(data.whatsapp || '').replace(/\D+/g, '') || '').trim();
                whatsappLink.href = digits ? ('https://wa.me/' + digits) : '#';
            }

            if (contatosResumo && Array.isArray(data.contatos_resumo)) {
                const list = data.contatos_resumo.length
                    ? data.contatos_resumo.map(item => {
                        const emailTxt = item.email ? ` • ${item.email}` : '';
                        return `<div>${item.nome || 'Contato'} — ${item.telefone || '-'}${emailTxt}</div>`;
                    }).join('')
                    : '<div>-</div>';
                contatosResumo.querySelector('.mt-1')?.remove();
                const wrapper = document.createElement('div');
                wrapper.className = 'mt-1 space-y-1 text-xs text-gray-600 dark:text-gray-400';
                wrapper.innerHTML = list;
                contatosResumo.appendChild(wrapper);
            }

            const notifEmailDestino = document.getElementById('notifEmailDestino');
            if (notifEmailDestino) notifEmailDestino.value = data.contato_email || '';

            const notifWhatsappLink = document.getElementById('notifWhatsappLink');
            if (notifWhatsappLink && data.whatsapp_digits) {
                const previewEl = document.getElementById('notifWhatsappPreview');
                const msg = previewEl ? encodeURIComponent(previewEl.textContent || '') : '';
                notifWhatsappLink.href = 'https://wa.me/' + data.whatsapp_digits + (msg ? '?text=' + msg : '');
            } else if (notifWhatsappLink && data.hasOwnProperty('whatsapp_digits')) {
                notifWhatsappLink.href = '#';
            }

            showToast('Cliente atualizado com sucesso.');
            const modal = document.getElementById('osClienteModal');
            if (modal) modal.classList.add('hidden');
        });
    }

    const equipamentoModalSelect = document.getElementById('equipamento_modal_id');
    const equipamentoIdInput = document.getElementById('equipamento_id');
    const equipamentoMap = {};
    if (equipamentoModalSelect) {
        Array.from(equipamentoModalSelect.options).forEach(option => {
            if (!option.value) return;
            equipamentoMap[option.value] = {
                label: option.textContent?.trim() || '',
                marca: option.dataset.marca || '',
                modelo: option.dataset.modelo || '',
                numeroSerie: option.dataset.numeroSerie || '',
                numeroPatrimonio: option.dataset.numeroPatrimonio || '',
                acessorios: option.dataset.acessorios || '',
                cliente: option.dataset.cliente || '',
            };
        });
    }
    const equipamentoNomeInput = document.querySelector('input[name="equipamento_nome"]');
    if (equipamentoNomeInput && equipamentoIdInput?.value && equipamentoMap[equipamentoIdInput.value]) {
        if (!equipamentoNomeInput.value.trim()) {
            equipamentoNomeInput.value = equipamentoMap[equipamentoIdInput.value].label || '';
        }
    }

    const equipamentoNovoToggle = document.getElementById('osEquipamentoNovoToggle');
    const equipamentoNovoForm = document.getElementById('osEquipamentoNovoForm');
    const equipamentoNovoCancelar = document.getElementById('osEquipamentoNovoCancelar');
    const equipamentoNovoSalvar = document.getElementById('osEquipamentoNovoSalvar');

    if (equipamentoNovoToggle && equipamentoNovoForm) {
        equipamentoNovoToggle.addEventListener('click', () => {
            equipamentoNovoForm.classList.toggle('hidden', false);
        });
    }
    const limparEquipamentoNovo = () => {
        const fields = [
            'equipamento_novo_nome',
            'equipamento_novo_marca',
            'equipamento_novo_modelo',
            'equipamento_novo_numero_serie',
            'equipamento_novo_numero_patrimonio',
            'equipamento_novo_acessorios',
        ];
        fields.forEach(id => {
            const el = document.getElementById(id);
            if (el) el.value = '';
        });
    };

    if (equipamentoNovoCancelar && equipamentoNovoForm) {
        equipamentoNovoCancelar.addEventListener('click', () => {
            limparEquipamentoNovo();
            equipamentoNovoForm.classList.toggle('hidden', true);
        });
    }

    if (equipamentoNovoSalvar) {
        equipamentoNovoSalvar.addEventListener('click', async () => {
            const clienteId = document.getElementById('cliente_id')?.value;
            if (!clienteId) {
                alert('Selecione um cliente primeiro.');
                return;
            }
            const nomeEquipamento = document.getElementById('equipamento_novo_nome')?.value?.trim() || '';
            const marca = document.getElementById('equipamento_novo_marca')?.value || '';
            let modelo = document.getElementById('equipamento_novo_modelo')?.value || '';
            const numeroSerie = document.getElementById('equipamento_novo_numero_serie')?.value || '';
            const numeroPatrimonio = document.getElementById('equipamento_novo_numero_patrimonio')?.value || '';
            const acessorios = document.getElementById('equipamento_novo_acessorios')?.value || '';
            if (!nomeEquipamento) {
                alert('Informe o nome do equipamento.');
                return;
            }
            if (!modelo) {
                modelo = nomeEquipamento;
            }

            const formData = new FormData();
            formData.append('cliente_id', clienteId);
            formData.append('marca', marca);
            formData.append('modelo', modelo);
            formData.append('numero_serie', numeroSerie);
            formData.append('numero_patrimonio', numeroPatrimonio);
            formData.append('acessorios', acessorios);

            const response = await fetch('{{ route('equipamentos.store') }}', {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                    'Accept': 'application/json',
                },
                body: formData,
            });

            if (!response.ok) {
                alert('Erro ao salvar equipamento.');
                return;
            }

            const data = await response.json();
            const label = data.label || [nomeEquipamento, marca, modelo, numeroSerie].filter(Boolean).join(' ').trim() || `Equipamento #${data.id}`;
            if (equipamentoModalSelect) {
                const option = document.createElement('option');
                option.value = data.id;
                option.textContent = label;
                option.dataset.cliente = data.cliente_id || clienteId;
                option.dataset.marca = marca;
                option.dataset.modelo = modelo;
                option.dataset.numeroSerie = numeroSerie;
                option.dataset.numeroPatrimonio = numeroPatrimonio;
                option.dataset.acessorios = acessorios;
                equipamentoModalSelect.appendChild(option);
                equipamentoModalSelect.value = data.id;
            }
            equipamentoMap[data.id] = {
                label,
                marca,
                modelo,
                numeroSerie,
                numeroPatrimonio,
                acessorios,
                cliente: data.cliente_id || clienteId,
            };

            limparEquipamentoNovo();
            if (equipamentoNovoForm) {
                equipamentoNovoForm.classList.add('hidden');
            }
        });
    }

    const btnEditarEquipamento = document.querySelector('[data-modal-open="osEquipamentoModal"]');
    if (btnEditarEquipamento) {
        btnEditarEquipamento.addEventListener('click', () => {
            filtrarPorCliente();
            if (equipamentoIdInput && equipamentoModalSelect) {
                equipamentoModalSelect.value = equipamentoIdInput.value || '';
            }
        });
    }

    const salvarEquipamentoBtn = document.getElementById('osEquipamentoSalvar');
    if (salvarEquipamentoBtn) {
        salvarEquipamentoBtn.addEventListener('click', async () => {
            const selecionado = equipamentoModalSelect?.value || '';
            if (!selecionado || !equipamentoMap[selecionado]) {
                const modal = document.getElementById('osEquipamentoModal');
                if (modal) modal.classList.add('hidden');
                return;
            } else {
                if (equipamentoIdInput) equipamentoIdInput.value = selecionado;
                const dados = equipamentoMap[selecionado];
                const marca = document.querySelector('input[name="equipamento_marca"]');
                const modelo = document.querySelector('input[name="equipamento_modelo"]');
                const numeroSerie = document.querySelector('input[name="equipamento_numero_serie"]');
                const numeroPatrimonio = document.querySelector('input[name="equipamento_numero_patrimonio"]');
                const acessorios = document.querySelector('textarea[name="equipamento_acessorios"]');
            const nome = document.querySelector('input[name="equipamento_nome"]');
                if (marca) marca.value = dados.marca;
                if (modelo) modelo.value = dados.modelo;
                if (numeroSerie) numeroSerie.value = dados.numeroSerie;
                if (numeroPatrimonio) numeroPatrimonio.value = dados.numeroPatrimonio;
                if (acessorios) acessorios.value = dados.acessorios;
            if (nome) nome.value = dados.label || '';
            }
            const form = document.getElementById('osForm');
            const updateUrl = form?.dataset.equipamentoUpdateUrl;
            if (updateUrl) {
                const response = await fetch(updateUrl, {
                    method: 'PATCH',
                    headers: {
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        'Accept': 'application/json',
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({ equipamento_id: selecionado }),
                });
                if (!response.ok) {
                    showToast('Erro ao atualizar equipamento.', 'error');
                    return;
                }
                showToast('Equipamento atualizado com sucesso.');
            }
            const modal = document.getElementById('osEquipamentoModal');
            if (modal) modal.classList.add('hidden');
        });
    }

    // Event listeners para botões de assinatura (substituindo formulários aninhados)
    document.querySelectorAll('[data-assinatura-aplicar]').forEach(btn => {
        btn.addEventListener('click', async () => {
            const url = btn.dataset.assinaturaAplicar;
            if (!url) return;
            try {
                const response = await fetch(url, {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        'Accept': 'application/json',
                        'Content-Type': 'application/json',
                    },
                    credentials: 'same-origin',
                });
                
                if (response.ok) {
                    try {
                        const data = await response.json();
                        showToast(data.message || 'Assinatura aplicada com sucesso.', 'success');
                        setTimeout(() => location.reload(), 1000);
                    } catch (jsonError) {
                        // Se não for JSON, ainda pode ser sucesso
                        showToast('Assinatura aplicada com sucesso.', 'success');
                        setTimeout(() => location.reload(), 1000);
                    }
                } else {
                    const errorData = await response.json().catch(() => ({}));
                    showToast(errorData.message || 'Erro ao aplicar assinatura.', 'error');
                }
            } catch (error) {
                console.error('Erro ao aplicar assinatura:', error);
                showToast('Erro ao aplicar assinatura.', 'error');
            }
        });
    });

    document.querySelectorAll('[data-assinatura-usuario-delete]').forEach(btn => {
        btn.addEventListener('click', async () => {
            if (!confirm('Remover assinatura salva do técnico?')) return;
            const url = btn.dataset.assinaturaUsuarioDelete;
            if (!url) return;
            try {
                const response = await fetch(url, {
                    method: 'DELETE',
                    headers: {
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        'Accept': 'application/json',
                        'Content-Type': 'application/json',
                    },
                    credentials: 'same-origin',
                });
                
                if (response.ok) {
                    try {
                        const data = await response.json();
                        showToast(data.message || 'Assinatura removida com sucesso.', 'success');
                        setTimeout(() => location.reload(), 1000);
                    } catch (jsonError) {
                        // Se não for JSON, ainda pode ser sucesso
                        showToast('Assinatura removida com sucesso.', 'success');
                        setTimeout(() => location.reload(), 1000);
                    }
                } else {
                    const errorData = await response.json().catch(() => ({}));
                    showToast(errorData.message || 'Erro ao remover assinatura.', 'error');
                }
            } catch (error) {
                console.error('Erro ao remover assinatura:', error);
                showToast('Erro ao remover assinatura.', 'error');
            }
        });
    });

    document.querySelectorAll('[data-assinatura-tecnico-delete]').forEach(btn => {
        btn.addEventListener('click', async () => {
            if (!confirm('Remover assinatura do técnico na OS?')) return;
            const url = btn.dataset.assinaturaTecnicoDelete;
            if (!url) return;
            try {
                const response = await fetch(url, {
                    method: 'DELETE',
                    headers: {
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        'Accept': 'application/json',
                        'Content-Type': 'application/json',
                    },
                    credentials: 'same-origin',
                });
                
                if (response.ok) {
                    try {
                        const data = await response.json();
                        showToast(data.message || 'Assinatura removida com sucesso.', 'success');
                        setTimeout(() => location.reload(), 1000);
                    } catch (jsonError) {
                        // Se não for JSON, ainda pode ser sucesso
                        showToast('Assinatura removida com sucesso.', 'success');
                        setTimeout(() => location.reload(), 1000);
                    }
                } else {
                    const errorData = await response.json().catch(() => ({}));
                    showToast(errorData.message || 'Erro ao remover assinatura.', 'error');
                }
            } catch (error) {
                console.error('Erro ao remover assinatura:', error);
                showToast('Erro ao remover assinatura.', 'error');
            }
        });
    });

    document.querySelectorAll('[data-assinatura-cliente-token]').forEach(btn => {
        btn.addEventListener('click', async () => {
            const url = btn.dataset.assinaturaClienteToken;
            if (!url) return;
            try {
                const response = await fetch(url, {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        'Accept': 'application/json',
                        'Content-Type': 'application/json',
                    },
                    credentials: 'same-origin',
                });
                
                if (response.ok) {
                    try {
                        const data = await response.json();
                        showToast(data.message || 'Link gerado com sucesso.', 'success');
                        setTimeout(() => location.reload(), 1000);
                    } catch (jsonError) {
                        // Se não for JSON, ainda pode ser sucesso
                        showToast('Link gerado com sucesso.', 'success');
                        setTimeout(() => location.reload(), 1000);
                    }
                } else {
                    const errorData = await response.json().catch(() => ({}));
                    showToast(errorData.message || 'Erro ao gerar link.', 'error');
                }
            } catch (error) {
                console.error('Erro ao gerar link:', error);
                showToast('Erro ao gerar link.', 'error');
            }
        });
    });

    document.querySelectorAll('[data-assinatura-cliente-delete]').forEach(btn => {
        btn.addEventListener('click', async () => {
            if (!confirm('Remover assinatura do cliente na OS?')) return;
            const url = btn.dataset.assinaturaClienteDelete;
            if (!url) return;
            try {
                const response = await fetch(url, {
                    method: 'DELETE',
                    headers: {
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        'Accept': 'application/json',
                        'Content-Type': 'application/json',
                    },
                    credentials: 'same-origin',
                });
                
                if (response.ok) {
                    try {
                        const data = await response.json();
                        showToast(data.message || 'Assinatura removida com sucesso.', 'success');
                        setTimeout(() => location.reload(), 1000);
                    } catch (jsonError) {
                        // Se não for JSON, ainda pode ser sucesso
                        showToast('Assinatura removida com sucesso.', 'success');
                        setTimeout(() => location.reload(), 1000);
                    }
                } else {
                    const errorData = await response.json().catch(() => ({}));
                    showToast(errorData.message || 'Erro ao remover assinatura.', 'error');
                }
            } catch (error) {
                console.error('Erro ao remover assinatura:', error);
                showToast('Erro ao remover assinatura.', 'error');
            }
        });
    });

    // Event listeners para deletar documentos do histórico
    document.querySelectorAll('[data-documento-delete]').forEach(btn => {
        btn.addEventListener('click', async () => {
            if (!confirm('Remover este documento do histórico?')) return;
            const url = btn.dataset.documentoDelete;
            if (!url) return;
            try {
                const response = await fetch(url, {
                    method: 'DELETE',
                    headers: {
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                    credentials: 'same-origin',
                });
                const data = await response.json().catch(() => null);
                if (response.ok) {
                    showToast(data?.message || 'Documento removido com sucesso.', 'success');
                    setTimeout(() => location.reload(), 1000);
                } else {
                    showToast(data?.message || 'Erro ao remover documento.', 'error');
                }
            } catch (error) {
                console.error('Erro ao remover documento:', error);
                showToast('Erro ao remover documento: ' + error.message, 'error');
            }
        });
    });

    // Event listeners para verificar integridade dos documentos
    document.querySelectorAll('[data-documento-verificar]').forEach(btn => {
        btn.addEventListener('click', async () => {
            const url = btn.dataset.documentoVerificar;
            if (!url) return;
            try {
                const response = await fetch(url, {
                    method: 'GET',
                    headers: {
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                    credentials: 'same-origin',
                });
                const data = await response.json().catch(() => null);
                if (response.ok && data) {
                    if (data.valido) {
                        showToast('✓ Documento íntegro. Nenhuma alteração detectada.', 'success');
                    } else {
                        showToast('⚠ Documento alterado: ' + (data.erros?.join(', ') || 'Integridade comprometida'), 'error');
                    }
                } else {
                    showToast('Erro ao verificar integridade.', 'error');
                }
            } catch (error) {
                console.error('Erro ao verificar integridade:', error);
                showToast('Erro ao verificar integridade: ' + error.message, 'error');
            }
        });
    });

    // Componente UnlockPattern (inline)
    @include('ordens_servico._unlock_pattern_script')

    // Controle de Senha de Desbloqueio
    (function() {
        const unlockTypeSelect = document.getElementById('unlock_type');
        const patternContainer = document.getElementById('unlock_pattern_container');
        const pinContainer = document.getElementById('unlock_pin_container');
        const noneContainer = document.getElementById('unlock_none_container');
        const patternCodeInput = document.getElementById('pattern_code_input');
        const pinCodeInput = document.getElementById('pin_code_input');
        const clearPatternBtn = document.getElementById('clear_pattern_btn');
        
        // Função para obter o input hidden do tipo "none"
        function getNoneInput() {
            return noneContainer ? noneContainer.querySelector('input[type="hidden"]') : null;
        }
        
        let patternComponent = null;
        
        // Carregar script do componente de padrão
        function loadPatternScript() {
            return new Promise((resolve) => {
                if (window.UnlockPattern) {
                    resolve();
                    return;
                }
                // Componente já deve estar carregado via include
                // Se não estiver, aguardar um pouco
                setTimeout(() => {
                    if (window.UnlockPattern) {
                        resolve();
                    } else {
                        console.warn('UnlockPattern não disponível');
                        resolve(); // Continuar mesmo sem o componente
                    }
                }, 100);
            });
        }
        
        function showUnlockType(type) {
            // Desabilitar e remover name de todos os campos primeiro
            if (patternCodeInput) {
                patternCodeInput.disabled = true;
                patternCodeInput.removeAttribute('name');
            }
            if (pinCodeInput) {
                pinCodeInput.disabled = true;
                pinCodeInput.removeAttribute('name');
            }
            const noneInput = getNoneInput();
            if (noneInput) {
                noneInput.disabled = true;
                noneInput.removeAttribute('name');
            }
            
            // Esconder todos os containers
            if (patternContainer) patternContainer.classList.add('hidden');
            if (pinContainer) pinContainer.classList.add('hidden');
            if (noneContainer) noneContainer.classList.add('hidden');
            
            // Mostrar e habilitar o selecionado
            if (type === 'pattern') {
                if (patternContainer) patternContainer.classList.remove('hidden');
                if (patternCodeInput) {
                    patternCodeInput.disabled = false;
                    patternCodeInput.setAttribute('name', 'equipamento_unlock_code');
                }
                initPatternComponent();
            } else if (type === 'pin') {
                if (pinContainer) pinContainer.classList.remove('hidden');
                if (pinCodeInput) {
                    pinCodeInput.disabled = false;
                    pinCodeInput.setAttribute('name', 'equipamento_unlock_code');
                }
            } else if (type === 'none') {
                if (noneContainer) noneContainer.classList.remove('hidden');
                const noneInputActive = getNoneInput();
                if (noneInputActive) {
                    noneInputActive.disabled = false;
                    noneInputActive.setAttribute('name', 'equipamento_unlock_code');
                    noneInputActive.value = ''; // Garantir que está vazio
                }
            } else {
                // Se nenhum tipo selecionado, garantir que nenhum campo tenha name
                // (já foi feito acima)
            }
        }
        
        async function initPatternComponent() {
            if (patternComponent) {
                // Se já existe, verificar se precisa atualizar o padrão
                if (patternCodeInput && patternCodeInput.value) {
                    try {
                        const savedPattern = JSON.parse(patternCodeInput.value);
                        if (savedPattern && savedPattern.length > 0) {
                            patternComponent.setPattern(savedPattern);
                        }
                    } catch (e) {
                        console.error('Erro ao parsear padrão salvo:', e);
                    }
                }
                return;
            }
            
            await loadPatternScript();
            
            let savedPattern = [];
            if (patternCodeInput && patternCodeInput.value) {
                try {
                    const value = patternCodeInput.value.trim();
                    if (value && value !== '') {
                        savedPattern = JSON.parse(value);
                        // Validar se é um array válido
                        if (!Array.isArray(savedPattern)) {
                            savedPattern = [];
                        }
                    }
                } catch (e) {
                    console.error('Erro ao parsear padrão salvo:', e, 'Valor:', patternCodeInput.value);
                    savedPattern = [];
                }
            }
            
            patternComponent = new UnlockPattern('pattern_draw_area', {
                rows: 3,
                cols: 3,
                readonly: false,
                pattern: savedPattern,
                onPatternChange: (pattern) => {
                    if (patternCodeInput) {
                        const patternValue = pattern && pattern.length > 0 ? JSON.stringify(pattern) : '';
                        patternCodeInput.value = patternValue;
                        // Garantir que o campo está habilitado e tem o name
                        patternCodeInput.disabled = false;
                        patternCodeInput.setAttribute('name', 'equipamento_unlock_code');
                        // Forçar atualização do campo
                        patternCodeInput.dispatchEvent(new Event('input', { bubbles: true }));
                    }
                }
            });
            
            // Tornar o componente acessível globalmente para o submit do formulário
            window.patternComponent = patternComponent;
            
            // Também armazenar na variável local para referência
            if (patternContainer) {
                patternContainer.patternComponent = patternComponent;
            }
            
            // Adicionar listener adicional no canvas para garantir atualização
            const canvas = document.getElementById('pattern_draw_area')?.querySelector('canvas');
            if (canvas) {
                // Quando o mouse sair do canvas, garantir que o padrão está salvo
                canvas.addEventListener('mouseup', () => {
                    setTimeout(() => {
                        if (patternComponent && patternCodeInput) {
                            const currentPattern = patternComponent.getPattern();
                            if (currentPattern && currentPattern.length > 0) {
                                patternCodeInput.value = JSON.stringify(currentPattern);
                                patternCodeInput.disabled = false;
                                patternCodeInput.setAttribute('name', 'equipamento_unlock_code');
                            }
                        }
                    }, 100);
                });
                
                canvas.addEventListener('touchend', () => {
                    setTimeout(() => {
                        if (patternComponent && patternCodeInput) {
                            const currentPattern = patternComponent.getPattern();
                            if (currentPattern && currentPattern.length > 0) {
                                patternCodeInput.value = JSON.stringify(currentPattern);
                                patternCodeInput.disabled = false;
                                patternCodeInput.setAttribute('name', 'equipamento_unlock_code');
                            }
                        }
                    }, 100);
                });
            }
            
            if (clearPatternBtn) {
                clearPatternBtn.addEventListener('click', () => {
                    if (patternComponent) {
                        patternComponent.clear();
                    }
                });
            }
        }
        
        if (unlockTypeSelect) {
            // Inicializar com o valor atual após um pequeno delay para garantir que o DOM está pronto
            setTimeout(() => {
                const currentType = unlockTypeSelect.value;
                if (currentType) {
                    showUnlockType(currentType);
                    // Se for pattern, garantir que o componente foi inicializado com o padrão salvo
                    if (currentType === 'pattern' && patternCodeInput && patternCodeInput.value) {
                        // Aguardar um pouco mais para garantir que o componente foi criado
                        setTimeout(() => {
                            if (patternComponent && patternCodeInput.value) {
                                try {
                                    const savedPattern = JSON.parse(patternCodeInput.value);
                                    if (savedPattern && savedPattern.length > 0) {
                                        patternComponent.setPattern(savedPattern);
                                    }
                                } catch (e) {
                                    console.error('Erro ao carregar padrão salvo:', e);
                                }
                            }
                        }, 200);
                    }
                } else {
                    // Se não houver tipo selecionado, desabilitar todos os campos
                    showUnlockType('');
                }
            }, 100);
            
            // Listener para mudanças
            unlockTypeSelect.addEventListener('change', (e) => {
                showUnlockType(e.target.value);
            });
        }
    })();
});
</script>



