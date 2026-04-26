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

@extends('layouts.fullscreen-layout')

@section('content')
<div class="min-h-screen bg-gray-100 px-4 py-8 dark:bg-gray-900">
    <div class="mx-auto w-full max-w-3xl rounded-lg border border-gray-200 bg-white p-6 shadow-lg dark:border-gray-700 dark:bg-gray-800">
        @php
            $empresaNome = $empresa?->nome ?? 'Empresa';
            $empresaRazao = $empresa?->razao_social ?? null;
            $empresaCnpj = $empresa?->cnpj ?? null;
            $empresaTelefone = $empresa?->telefone ?? null;
            $empresaEmail = $empresa?->email ?? null;
            // Logo em base64 para garantir exibição (não depende de storage:link)
            $empresaLogo = null;
            if ($empresa?->logo_path) {
                $logoPath = storage_path('app/public/' . $empresa->logo_path);
                if (file_exists($logoPath)) {
                    $mime = @mime_content_type($logoPath) ?: 'image/png';
                    $empresaLogo = 'data:' . $mime . ';base64,' . base64_encode(file_get_contents($logoPath));
                } else {
                    $empresaLogo = asset('storage/' . $empresa->logo_path);
                }
            }
        @endphp

        <div class="mb-4 rounded-lg border border-gray-200 bg-white p-4 shadow-theme-sm dark:border-gray-700 dark:bg-gray-800">
            <div class="grid grid-cols-1 gap-3 md:grid-cols-[140px_1fr_220px] md:items-center">
                <div>
                    @if($empresaLogo)
                        <img src="{{ $empresaLogo }}" alt="Logotipo" class="h-16 w-auto">
                    @endif
                </div>
                <div class="text-sm text-gray-700 dark:text-gray-300">
                    <div class="text-base font-semibold text-gray-800 dark:text-white/90">{{ $empresaNome }}</div>
                    @if($empresaRazao)<div>{{ $empresaRazao }}</div>@endif
                    @if($empresaCnpj)<div>CNPJ: {{ $empresaCnpj }}</div>@endif
                    @if($empresa && ($empresa->logradouro || $empresa->cidade))
                        <div>{{ trim($empresa->logradouro . ' ' . $empresa->numero . ($empresa->complemento ? ' - ' . $empresa->complemento : '') . ', ' . $empresa->bairro . ' - ' . $empresa->cidade . '/' . $empresa->estado) }}</div>
                        @if($empresa->cep)<div>CEP: {{ $empresa->cep }}</div>@endif
                    @endif
                </div>
                <div class="text-sm text-gray-700 dark:text-gray-300 md:text-right">
                    @if($empresaTelefone)<div><strong>Tel:</strong> {{ $empresaTelefone }}</div>@endif
                    @if($empresaEmail)<div><strong>{{ $empresaEmail }}</strong></div>@endif
                </div>
            </div>
        </div>

        <div class="mb-4">
            <h1 class="text-xl font-semibold text-gray-800 dark:text-white/90">
                {{ $tipo === 'cliente' ? 'Assinatura do Cliente' : 'Assinatura do Técnico' }}
            </h1>
            <p class="text-sm text-gray-600 dark:text-gray-400">
                {{ format_os_display($ordem) }} • {{ $ordem->cliente?->nome ?? '-' }}
            </p>
        </div>

        @php
            $clienteBase = $ordem->unidade ?? $ordem->cliente;
            $contato = $ordem->contato;
            $endereco = $ordem->endereco ?? $clienteBase?->enderecos?->firstWhere('principal', true);
            $enderecoTexto = $endereco
                ? trim($endereco->logradouro . ' ' . $endereco->numero . ($endereco->complemento ? ' - ' . $endereco->complemento : '') . ', ' . $endereco->bairro . ' - ' . $endereco->cidade . '/' . $endereco->estado)
                : '-';
            $totalProdutos = $ordem->produtos->sum('total');
            $totalServicos = $ordem->servicos->sum('total');
            $totalGeral = $ordem->total_geral ?? ($totalProdutos + $totalServicos);
            $descontos = $ordem->total_descontos ?? 0;
            $acrescimos = $ordem->total_acrescimos ?? $ordem->acrescimos_global ?? 0;
            $garantiaDias = $ordem->garantia_dias ?? null;
        @endphp

        <div class="mb-4 rounded-lg border border-gray-200 bg-gray-50 p-4 text-sm text-gray-700 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300">
            <div class="grid grid-cols-1 gap-2 md:grid-cols-2">
                <div><strong>Status:</strong> {{ $ordem->status ?? '-' }}</div>
                <div><strong>Data de abertura:</strong> {{ optional($ordem->data_abertura)->format('d/m/Y H:i') ?? '-' }}</div>
                @if($ordem->equipamento_nome || $ordem->equipamento?->nome)
                <div><strong>Equipamento:</strong> {{ $ordem->equipamento_nome ?? $ordem->equipamento?->nome }}</div>
                @endif
                <div><strong>Endereço:</strong> {{ $enderecoTexto }}</div>
                <div>
                    <strong>Garantia:</strong> {{ $ordem->garantia_tipo ?? '-' }}
                    @if($garantiaDias) ({{ $garantiaDias }} dias) @endif
                </div>
            </div>
        </div>

        <div class="mb-4 rounded-lg border border-gray-200 bg-white p-4 text-sm text-gray-700 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-300">
            <h2 class="mb-2 text-sm font-semibold text-gray-800 dark:text-white/90">Dados do Cliente</h2>
            <div class="grid grid-cols-1 gap-2 md:grid-cols-2">
                <div>
                    <div><strong>{{ $clienteBase->nome ?? '-' }}</strong></div>
                    <div>CPF/CNPJ: {{ $clienteBase->cnpj ?? $clienteBase->cpf ?? $clienteBase->documento_estrangeiro ?? '-' }}</div>
                    <div>{{ $contato->nome ?? '-' }} {{ $contato?->telefone ? '(' . $contato->telefone . ')' : '' }}</div>
                    <div>{{ $contato->email ?? '-' }}</div>
                </div>
                <div>
                    <div>{{ $enderecoTexto }}</div>
                    <div>CEP: {{ $endereco->cep ?? '-' }}</div>
                </div>
            </div>
        </div>

        @if($ordem->equipamento_nome || $ordem->equipamento_marca || $ordem->equipamento_modelo || $ordem->equipamento_numero_serie || $ordem->equipamento_numero_patrimonio || $ordem->equipamento_acessorios || $ordem->equipamento_condicoes || $ordem->equipamento)
        <div class="mb-4 rounded-lg border border-gray-200 bg-white p-4 text-sm text-gray-700 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-300">
            <h2 class="mb-2 text-sm font-semibold text-gray-800 dark:text-white/90">Equipamento</h2>
            <div class="grid grid-cols-1 gap-2 md:grid-cols-2">
                <div>
                    <div><strong>{{ $ordem->equipamento_nome ?? $ordem->equipamento?->nome ?? '-' }}</strong></div>
                    <div>Marca: {{ $ordem->equipamento_marca ?? '-' }}</div>
                    <div>Modelo: {{ $ordem->equipamento_modelo ?? '-' }}</div>
                </div>
                <div>
                    <div>Nº Série: {{ $ordem->equipamento_numero_serie ?? '-' }}</div>
                    <div>Nº Patrimônio: {{ $ordem->equipamento_numero_patrimonio ?? '-' }}</div>
                </div>
            </div>
            @if($ordem->equipamento_acessorios)
                <div class="mt-2 rounded-lg border border-gray-200 bg-gray-50 p-2 text-xs text-gray-600 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300">
                    <strong>Acessórios:</strong> {!! $ordem->equipamento_acessorios !!}
                </div>
            @endif
            @if($ordem->equipamento_condicoes)
                <div class="mt-2 rounded-lg border border-gray-200 bg-gray-50 p-2 text-xs text-gray-600 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300">
                    <strong>Condições do equipamento:</strong> {!! $ordem->equipamento_condicoes !!}
                </div>
            @endif
        </div>
        @endif

        @if($ordem->relato_cliente)
            <div class="mb-4 rounded-lg border border-gray-200 bg-white p-4 text-sm text-gray-700 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-300">
                <h2 class="mb-2 text-sm font-semibold text-gray-800 dark:text-white/90">Problemas/Relato do Cliente</h2>
                <div class="prose max-w-none text-sm">{!! $ordem->relato_cliente !!}</div>
            </div>
        @endif

        @if($ordem->diagnostico_tecnico)
            <div class="mb-4 rounded-lg border border-gray-200 bg-white p-4 text-sm text-gray-700 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-300">
                <h2 class="mb-2 text-sm font-semibold text-gray-800 dark:text-white/90">Diagnóstico Técnico</h2>
                <div class="prose max-w-none text-sm">{!! $ordem->diagnostico_tecnico !!}</div>
            </div>
        @endif

        @if($ordem->observacoes_cliente)
            <div class="mb-4 rounded-lg border border-gray-200 bg-white p-4 text-sm text-gray-700 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-300">
                <h2 class="mb-2 text-sm font-semibold text-gray-800 dark:text-white/90">Observações</h2>
                <div class="prose max-w-none text-sm">{!! $ordem->observacoes_cliente !!}</div>
            </div>
        @endif

        @php
            $checklistsPreenchidos = $ordem->checklists->filter(function($c) {
                $respostas = $c->respostas ?? [];
                $temRespostas = collect($respostas)->filter(fn($v) => $v !== '' && $v !== null && $v !== false)->count() > 0;
                $temObservacoes = !empty(trim($c->observacoes ?? ''));
                return $temRespostas || $temObservacoes;
            });
        @endphp
        @if($checklistsPreenchidos->count() > 0)
            <div class="mb-4 rounded-lg border border-gray-200 bg-white p-4 text-sm text-gray-700 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-300">
                <h2 class="mb-3 text-sm font-semibold text-gray-800 dark:text-white/90">Checklists</h2>
                @foreach($checklistsPreenchidos as $checklist)
                    @php
                        $modelo = $checklist->checklistModel;
                        $campos = $modelo->campos ?? [];
                        $respostas = $checklist->respostas ?? [];
                    @endphp
                    <div class="mb-4 rounded-lg border border-gray-200 bg-gray-50 p-3 last:mb-0 dark:border-gray-700 dark:bg-gray-900">
                        <h3 class="mb-2 text-sm font-medium text-gray-800 dark:text-white/90">{{ $modelo->nome }}</h3>
                        <div class="space-y-1.5 text-xs">
                            @foreach($campos as $index => $campo)
                                @php
                                    $valor = $respostas[$index] ?? null;
                                    if ($valor === '' || $valor === null) continue;
                                    if (($campo['tipo'] ?? '') === 'checkbox') {
                                        $valor = $valor ? 'Sim' : 'Não';
                                    }
                                    if (($campo['tipo'] ?? '') === 'data' && $valor) {
                                        try { $valor = \Carbon\Carbon::parse($valor)->format('d/m/Y'); } catch (\Exception $e) {}
                                    }
                                @endphp
                                <div class="flex gap-2">
                                    <span class="font-medium text-gray-600 dark:text-gray-400 min-w-[120px]">{{ $campo['label'] ?? 'Campo ' . ($index + 1) }}:</span>
                                    <span class="text-gray-800 dark:text-gray-200">{{ is_string($valor) ? $valor : (is_bool($valor) ? ($valor ? 'Sim' : 'Não') : (string) $valor) }}</span>
                                </div>
                            @endforeach
                            @if(!empty(trim($checklist->observacoes ?? '')))
                                <div class="mt-2 flex gap-2 border-t border-gray-200 pt-2 dark:border-gray-700">
                                    <span class="font-medium text-gray-600 dark:text-gray-400 min-w-[120px]">Observações:</span>
                                    <span class="text-gray-800 dark:text-gray-200">{{ $checklist->observacoes }}</span>
                                </div>
                            @endif
                        </div>
                    </div>
                @endforeach
            </div>
        @endif

        @if($ordem->servicos_realizados)
            <div class="mb-4 rounded-lg border border-gray-200 bg-white p-4 text-sm text-gray-700 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-300">
                <h2 class="mb-2 text-sm font-semibold text-gray-800 dark:text-white/90">Descrição</h2>
                <div class="prose max-w-none text-sm">{!! $ordem->servicos_realizados !!}</div>
            </div>
        @endif

        @if($ordem->servicos->count())
            <div class="mb-4 rounded-lg border border-gray-200 bg-white p-4 text-sm text-gray-700 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-300">
                <h2 class="mb-2 text-sm font-semibold text-gray-800 dark:text-white/90">Serviços</h2>
                <div class="overflow-x-auto">
                    <table class="w-full border-collapse text-xs">
                        <thead>
                            <tr class="bg-gray-100 text-left dark:bg-gray-900">
                                <th class="border border-gray-200 px-2 py-1 dark:border-gray-700">Serviço</th>
                                <th class="border border-gray-200 px-2 py-1 text-center dark:border-gray-700">Qtd</th>
                                <th class="border border-gray-200 px-2 py-1 text-center dark:border-gray-700">Unt</th>
                                <th class="border border-gray-200 px-2 py-1 text-right dark:border-gray-700">Subtotal</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($ordem->servicos as $item)
                                <tr>
                                    <td class="border border-gray-200 px-2 py-1 dark:border-gray-700">{{ $item->descricao ?? $item->servico?->nome ?? '-' }}</td>
                                    <td class="border border-gray-200 px-2 py-1 text-center dark:border-gray-700">{{ $item->cobranca_tipo === 'horas' ? $item->quantidade_horas : $item->quantidade }}</td>
                                    <td class="border border-gray-200 px-2 py-1 text-center dark:border-gray-700">{{ number_format($item->valor_unitario ?? 0, 2, ',', '.') }}</td>
                                    <td class="border border-gray-200 px-2 py-1 text-right dark:border-gray-700">R$ {{ number_format($item->total ?? 0, 2, ',', '.') }}</td>
                                </tr>
                            @endforeach
                            <tr>
                                <td colspan="3" class="border border-gray-200 px-2 py-1 text-right font-semibold dark:border-gray-700">Total serviços</td>
                                <td class="border border-gray-200 px-2 py-1 text-right font-semibold dark:border-gray-700">R$ {{ number_format($totalServicos, 2, ',', '.') }}</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        @endif

        @if($ordem->produtos->count())
            <div class="mb-4 rounded-lg border border-gray-200 bg-white p-4 text-sm text-gray-700 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-300">
                <h2 class="mb-2 text-sm font-semibold text-gray-800 dark:text-white/90">Produtos / Peças</h2>
                <div class="overflow-x-auto">
                    <table class="w-full border-collapse text-xs">
                        <thead>
                            <tr class="bg-gray-100 text-left dark:bg-gray-900">
                                <th class="border border-gray-200 px-2 py-1 dark:border-gray-700">Produto</th>
                                <th class="border border-gray-200 px-2 py-1 text-center dark:border-gray-700">Qtd</th>
                                <th class="border border-gray-200 px-2 py-1 text-center dark:border-gray-700">Unt</th>
                                <th class="border border-gray-200 px-2 py-1 text-right dark:border-gray-700">Subtotal</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($ordem->produtos as $item)
                                <tr>
                                    <td class="border border-gray-200 px-2 py-1 dark:border-gray-700">
                                        @php
                                            $produto = $item->produto;
                                            $nomeProduto = $item->descricao ?? $produto?->nome ?? '-';
                                            
                                            // Verificar se é um kit (tem composições)
                                            if ($produto) {
                                                // Carregar composições se não estiverem carregadas
                                                if (!$produto->relationLoaded('composicoes')) {
                                                    $produto->load('composicoes.componente');
                                                }
                                                
                                                // Verificar se tem composições
                                                $composicoes = $produto->composicoes ?? collect();
                                                if ($composicoes->count() > 0) {
                                                    // Montar lista de componentes
                                                    $componentes = $composicoes->map(function($comp) {
                                                        return $comp->componente?->nome ?? null;
                                                    })->filter()->implode(', ');
                                                    
                                                    // Mostrar nome do kit + componentes
                                                    echo $produto->nome . ($componentes ? ' (' . $componentes . ')' : '');
                                                } elseif ($item->variacao) {
                                                    // Item com variação: mostrar nome do produto + variação (não a descrição)
                                                    $var = $item->variacao;
                                                    $opcoes = is_array($var->opcoes_valores ?? null) ? implode(', ', $var->opcoes_valores) : ($var->opcoes_valores ?? '');
                                                    $labelVariacao = trim(($var->referencia_sku ?? '') . ($opcoes ? (' (' . $opcoes . ')') : ''));
                                                    echo $produto->nome . ($labelVariacao ? ' - ' . $labelVariacao : '');
                                                } else {
                                                    // Produto normal: mostrar descricao ou nome
                                                    echo $nomeProduto;
                                                }
                                            } else {
                                                // Produto normal: mostrar descricao ou nome
                                                echo $nomeProduto;
                                            }
                                        @endphp
                                    </td>
                                    <td class="border border-gray-200 px-2 py-1 text-center dark:border-gray-700">{{ format_quantity($item->quantidade ?? 0) }}</td>
                                    <td class="border border-gray-200 px-2 py-1 text-center dark:border-gray-700">{{ number_format($item->valor_unitario ?? 0, 2, ',', '.') }}</td>
                                    <td class="border border-gray-200 px-2 py-1 text-right dark:border-gray-700">R$ {{ number_format($item->total ?? 0, 2, ',', '.') }}</td>
                                </tr>
                            @endforeach
                            <tr>
                                <td colspan="3" class="border border-gray-200 px-2 py-1 text-right font-semibold dark:border-gray-700">Total produtos</td>
                                <td class="border border-gray-200 px-2 py-1 text-right font-semibold dark:border-gray-700">R$ {{ number_format($totalProdutos, 2, ',', '.') }}</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        @endif

        <div class="mb-4 rounded-lg border border-gray-200 bg-white p-4 text-sm text-gray-700 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-300">
            <h2 class="mb-2 text-sm font-semibold text-gray-800 dark:text-white/90">Resumo financeiro</h2>
            <div class="grid grid-cols-1 gap-2 md:grid-cols-2">
                <div>Total serviços: <strong>R$ {{ number_format($totalServicos, 2, ',', '.') }}</strong></div>
                <div>Total produtos: <strong>R$ {{ number_format($totalProdutos, 2, ',', '.') }}</strong></div>
                <div>Descontos: <strong>R$ {{ number_format($descontos, 2, ',', '.') }}</strong></div>
                <div>Acréscimos: <strong>R$ {{ number_format($acrescimos, 2, ',', '.') }}</strong></div>
                <div>Total geral: <strong>R$ {{ number_format($totalGeral, 2, ',', '.') }}</strong></div>
            </div>
        </div>

        @if($tipo === 'cliente' && !empty($assinaturaExistente))
            <div class="rounded-lg border border-success-200 bg-success-50 p-4 text-sm text-success-700 dark:border-success-700 dark:bg-success-500/10 dark:text-success-300">
                Assinatura do cliente já registrada em {{ optional($assinaturaExistente->signed_at)->format('d/m/Y H:i') }}.
            </div>
        @endif

        @if($tipo === 'cliente' && !empty($assinaturaExistente))
            <div class="mt-4 rounded-lg border border-gray-200 bg-white p-4 text-sm text-gray-700 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-300">
                <h2 class="mb-2 text-sm font-semibold text-gray-800 dark:text-white/90">Dados de integridade do documento</h2>

                @php
                    $confirmacao = $assinaturaExistente->metadata['confirmacao'] ?? [];
                    $extraMetaCliente = $assinaturaExistente->metadata['extra'] ?? [];
                    $extraMetaTec = $assinaturaTecnico?->metadata['extra'] ?? [];
                @endphp

                <div class="rounded-lg border border-gray-200 bg-gray-50 p-3 text-xs text-gray-700 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300">
                    <div class="mb-2 text-sm font-semibold text-gray-800 dark:text-white/90">Sessão do cliente</div>
                    <div class="grid grid-cols-1 gap-2 md:grid-cols-2">
                        <div><strong>Data/hora:</strong> {{ optional($assinaturaExistente->signed_at)->format('d/m/Y H:i') ?? '-' }}</div>
                        <div><strong>IP:</strong> {{ $assinaturaExistente->signed_ip ?? '-' }}</div>
                        <div><strong>User-agent:</strong> {{ $assinaturaExistente->signed_user_agent ?? '-' }}</div>
                        <div><strong>Sessão:</strong> {{ $assinaturaExistente->session_uuid ?? '-' }}</div>
                        <div><strong>CPF informado pelo cliente:</strong> {{ $confirmacao['cpf'] ?? '-' }}</div>
                        <div><strong>Celular informado pelo cliente:</strong> {{ $confirmacao['celular'] ?? '-' }}</div>
                        <div><strong>Tipo de dispositivo:</strong> {{ $extraMetaCliente['device'] ?? '-' }}</div>
                        <div><strong>Fuso horário:</strong> {{ $extraMetaCliente['timezone'] ?? '-' }}</div>
                        <div><strong>Idioma do navegador:</strong> {{ $extraMetaCliente['language'] ?? '-' }}</div>
                        <div><strong>Tipo de assinatura:</strong> {{ $extraMetaCliente['assinatura_tipo'] ?? '-' }}</div>
                        @if(!empty($extraMetaCliente['assinatura_digitada']))
                            <div><strong>Assinatura digitada:</strong> {{ $extraMetaCliente['assinatura_digitada'] }}</div>
                        @endif
                        @if(!empty($extraMetaCliente['duracao_ms']))
                            <div><strong>Duração da assinatura:</strong> {{ round($extraMetaCliente['duracao_ms'] / 1000, 1) }}s</div>
                        @endif
                    </div>
                </div>

                @if(!empty($assinaturaTecnico))
                    <div class="mt-3 rounded-lg border border-gray-200 bg-gray-50 p-3 text-xs text-gray-700 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300">
                        <div class="mb-2 text-sm font-semibold text-gray-800 dark:text-white/90">Sessão do técnico</div>
                        <div class="grid grid-cols-1 gap-2 md:grid-cols-2">
                            <div><strong>Data/hora:</strong> {{ optional($assinaturaTecnico->signed_at)->format('d/m/Y H:i') ?? '-' }}</div>
                            <div><strong>IP:</strong> {{ $assinaturaTecnico->signed_ip ?? '-' }}</div>
                            <div><strong>User-agent:</strong> {{ $assinaturaTecnico->signed_user_agent ?? '-' }}</div>
                            <div><strong>Sessão:</strong> {{ $assinaturaTecnico->session_uuid ?? '-' }}</div>
                            <div><strong>Tipo de dispositivo:</strong> {{ $extraMetaTec['device'] ?? '-' }}</div>
                            <div><strong>Fuso horário:</strong> {{ $extraMetaTec['timezone'] ?? '-' }}</div>
                            <div><strong>Idioma do navegador:</strong> {{ $extraMetaTec['language'] ?? '-' }}</div>
                            <div><strong>Tipo de assinatura:</strong> {{ $extraMetaTec['assinatura_tipo'] ?? '-' }}</div>
                            @if(!empty($extraMetaTec['assinatura_digitada']))
                                <div><strong>Assinatura digitada:</strong> {{ $extraMetaTec['assinatura_digitada'] }}</div>
                            @endif
                            @if(!empty($extraMetaTec['duracao_ms']))
                                <div><strong>Duração da assinatura:</strong> {{ round($extraMetaTec['duracao_ms'] / 1000, 1) }}s</div>
                            @endif
                        </div>
                    </div>
                @endif

                <div class="mt-4 rounded-lg border border-success-200 bg-success-50 p-3 text-xs text-success-700 dark:border-success-700 dark:bg-success-500/10 dark:text-success-300">
                    <strong>Declaração de aceite do cliente:</strong>
                    <div class="mt-1">{{ $assinaturaExistente->checkbox_text ?? $checkboxTexto }}</div>
                </div>
                @if(!empty($assinaturaExistente->geo))
                    <div class="mt-2 text-xs text-gray-600 dark:text-gray-400">
                        Localização: {{ $assinaturaExistente->geo['lat'] ?? '-' }}, {{ $assinaturaExistente->geo['lng'] ?? '-' }}
                        (± {{ $assinaturaExistente->geo['accuracy'] ?? '-' }}m)
                    </div>
                @endif
                <div class="mt-3 grid grid-cols-1 gap-3 md:grid-cols-2">
                    <div>
                        <div class="mb-1 text-xs text-gray-500 dark:text-gray-400">Assinatura do cliente</div>
                        <img src="{{ route('assinaturas.imagem', $assinaturaExistente) }}{{ isset($token) && $token !== '' ? '?token=' . urlencode($token) : '' }}" alt="Assinatura do cliente" class="h-28 rounded border border-gray-200 bg-white p-2">
                    </div>
                    @if(!empty($assinaturaTecnico))
                        <div>
                            <div class="mb-1 text-xs text-gray-500 dark:text-gray-400">Assinatura do técnico</div>
                            <img src="{{ route('assinaturas.imagem', $assinaturaTecnico) }}{{ isset($token) && $token !== '' ? '?token=' . urlencode($token) : '' }}" alt="Assinatura do técnico" class="h-28 rounded border border-gray-200 bg-white p-2">
                        </div>
                    @endif
                </div>
            </div>
        @else
        <form method="POST" novalidate action="{{ $tipo === 'cliente' ? route('assinaturas.cliente.store', $token) : route('assinaturas.tecnico.store', $ordem) }}">
            @csrf
            <input type="hidden" name="signature_data" id="signature_data">
            <input type="hidden" name="signature_points" id="signature_points">
            <input type="hidden" name="geo" id="geo">
            <input type="hidden" name="extra_metadata" id="extra_metadata">
            <input type="hidden" name="checkbox_text" value="{{ $checkboxTexto }}">
            <input type="hidden" name="terms_version" value="{{ $termsVersion }}">
            <input type="hidden" name="privacy_version" value="{{ $privacyVersion }}">
            <input type="hidden" name="legal_basis" value="{{ $legalBasis }}">
            <input type="hidden" name="session_uuid" value="{{ $sessionUuid }}">
            <input type="hidden" name="checkbox_checked" id="checkbox_checked" value="0">

            @if($tipo === 'cliente')
                <div class="mb-4 rounded-lg border border-gray-200 bg-white p-4 text-sm text-gray-700 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-300">
                    <h2 class="mb-2 text-sm font-semibold text-gray-800 dark:text-white/90">Confirmação do cliente</h2>
                    <div class="grid grid-cols-1 gap-3 md:grid-cols-2">
                        <div class="md:col-span-2">
                            <label class="mb-1 block text-xs font-medium text-gray-600 dark:text-gray-400">Nome completo</label>
                            <input type="text" name="nome_confirmacao" id="nome_confirmacao" placeholder="Digite seu nome completo" class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm text-gray-800 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90" required>
                            <p id="nome_erro" class="mt-1 text-xs text-error-600 dark:text-error-400 hidden"></p>
                        </div>
                        <div>
                            <label class="mb-1 block text-xs font-medium text-gray-600 dark:text-gray-400">CPF</label>
                            <input type="text" name="cpf_confirmacao" id="cpf_confirmacao" placeholder="000.000.000-00" class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm text-gray-800 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90" required>
                            <p id="cpf_erro" class="mt-1 text-xs text-error-600 dark:text-error-400 hidden"></p>
                        </div>
                        <div>
                            <label class="mb-1 block text-xs font-medium text-gray-600 dark:text-gray-400">Celular</label>
                            <input type="text" name="celular_confirmacao" id="celular_confirmacao" placeholder="(00) 00000-0000" class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm text-gray-800 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90" required>
                            <p id="celular_erro" class="mt-1 text-xs text-error-600 dark:text-error-400 hidden"></p>
                        </div>
                    </div>
                </div>
            @endif

            <div class="mb-4">
                <label class="flex items-start gap-2 text-sm text-gray-700 dark:text-gray-300">
                    <input type="checkbox" id="checkbox_concordo" class="mt-1 rounded border-gray-300 text-brand-500 focus:ring-brand-500">
                    <span>{{ $checkboxTexto }}</span>
                </label>
                <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                    Termos v{{ $termsVersion }} • Privacidade v{{ $privacyVersion }} • Base legal: {{ $legalBasis }}
                </p>
            </div>

            <div class="mb-4 rounded-lg border border-gray-200 bg-white p-4 text-sm text-gray-700 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-300">
                <h2 class="mb-2 text-sm font-semibold text-gray-800 dark:text-white/90">Tipo de assinatura</h2>
                <div class="flex flex-wrap items-center gap-4">
                    <label class="flex items-center gap-2">
                        <input type="radio" name="signature_type" value="desenho" checked class="text-brand-500 focus:ring-brand-500">
                        <span>Desenhar assinatura</span>
                    </label>
                    <label class="flex items-center gap-2">
                        <input type="radio" name="signature_type" value="digitada" class="text-brand-500 focus:ring-brand-500">
                        <span>Assinatura digitada</span>
                    </label>
                </div>
                <div id="assinatura_desenho" class="mt-4">
                    <div class="mb-2 flex items-center justify-between">
                        <span class="text-sm font-medium text-gray-700 dark:text-gray-300">Assine no quadro abaixo</span>
                        <button type="button" id="limpar_assinatura" class="text-xs text-error-600 hover:underline">Limpar</button>
                    </div>
                    <div class="touch-none rounded-lg border border-gray-300 bg-white p-2 dark:border-gray-700">
                        <canvas id="canvas_assinatura" class="h-40 w-full touch-none rounded bg-white" style="touch-action: none;"></canvas>
                    </div>
                </div>
                <div id="assinatura_digitada" class="mt-4 hidden">
                    <label class="mb-1 block text-xs font-medium text-gray-600 dark:text-gray-400">Assinatura digitada (nome completo)</label>
                    <input type="text" name="signature_typed" id="signature_typed" placeholder="Digite seu nome completo" class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm text-gray-800 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90">
                    <div class="mt-3 grid grid-cols-1 gap-3 md:grid-cols-2">
                        <div>
                            <label class="mb-1 block text-xs font-medium text-gray-600 dark:text-gray-400">Fonte</label>
                            <select id="signature_font" class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm text-gray-800 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90">
                                <option value='"Segoe Script", "Brush Script MT", cursive'>Segoe Script</option>
                                <option value='"Brush Script MT", cursive'>Brush Script</option>
                                <option value='"Pacifico", cursive'>Pacifico</option>
                                <option value='"Dancing Script", cursive'>Dancing Script</option>
                                <option value='"Great Vibes", cursive'>Great Vibes</option>
                                <option value='"Caveat", cursive'>Caveat</option>
                            </select>
                        </div>
                        <div>
                            <label class="mb-1 block text-xs font-medium text-gray-600 dark:text-gray-400">Prévia</label>
                            <div class="rounded-lg border border-gray-300 bg-white px-3 py-2 text-lg text-gray-800 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90">
                                <span id="signature_preview"></span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="mb-4 text-xs text-gray-500 dark:text-gray-400">
                A localização será coletada automaticamente se o navegador permitir.
                <span id="geo_status" class="ml-1"></span>
            </div>

            <div class="flex flex-wrap items-center gap-3">
                <button type="submit" id="btn_confirmar" class="rounded-lg bg-brand-500 px-4 py-2 text-sm font-semibold text-white hover:bg-brand-600" disabled>
                    Confirmar assinatura
                </button>
                @if($tipo === 'tecnico')
                    <label class="flex items-center gap-2 text-sm text-gray-700 dark:text-gray-300">
                        <input type="checkbox" name="salvar_assinatura" value="1" class="rounded border-gray-300 text-brand-500 focus:ring-brand-500">
                        Salvar assinatura do técnico para reutilizar
                    </label>
                @endif
            </div>
        </form>
        @endif
    </div>
</div>

@push('scripts')
<script>
(function () {
const canvas = document.getElementById('canvas_assinatura');
if (!canvas) {
    return;
}
const ctx = canvas.getContext('2d');
if (!ctx) {
    return;
}
let drawing = false;
let points = [];
let signatureStartAt = null;
const signatureTypeInputs = document.querySelectorAll('input[name="signature_type"]');
const assinaturaDesenho = document.getElementById('assinatura_desenho');
const assinaturaDigitada = document.getElementById('assinatura_digitada');
const signatureTyped = document.getElementById('signature_typed');
const nomeConfirmacao = document.getElementById('nome_confirmacao');
const signatureFont = document.getElementById('signature_font');
const signaturePreview = document.getElementById('signature_preview');

let resizeCanvasTimer = null;
function scheduleResizeCanvas() {
    if (resizeCanvasTimer) {
        clearTimeout(resizeCanvasTimer);
    }
    resizeCanvasTimer = setTimeout(() => {
        resizeCanvasTimer = null;
        resizeCanvasNow();
    }, 80);
}

function resizeCanvasNow() {
    const ratio = window.devicePixelRatio || 1;
    const cssW = Math.max(1, Math.floor(canvas.clientWidth));
    const cssH = Math.max(1, Math.floor(canvas.clientHeight));
    const newW = Math.max(1, Math.floor(cssW * ratio));
    const newH = Math.max(1, Math.floor(cssH * ratio));

    if (canvas.width === newW && canvas.height === newH) {
        return;
    }

    let snapshot = null;
    if (canvas.width > 0 && canvas.height > 0 && points.length > 2) {
        snapshot = document.createElement('canvas');
        snapshot.width = canvas.width;
        snapshot.height = canvas.height;
        const snapCtx = snapshot.getContext('2d');
        if (snapCtx) {
            snapCtx.drawImage(canvas, 0, 0);
        }
    }

    ctx.setTransform(1, 0, 0, 1, 0, 0);
    canvas.width = newW;
    canvas.height = newH;
    ctx.scale(ratio, ratio);
    ctx.lineWidth = 2;
    ctx.lineCap = 'round';
    ctx.strokeStyle = '#111827';

    if (snapshot) {
        ctx.drawImage(snapshot, 0, 0, snapshot.width, snapshot.height, 0, 0, cssW, cssH);
    }
}

function getPos(e) {
    const rect = canvas.getBoundingClientRect();
    let clientX = e.clientX;
    let clientY = e.clientY;
    if (e.touches && e.touches.length) {
        clientX = e.touches[0].clientX;
        clientY = e.touches[0].clientY;
    } else if (e.changedTouches && e.changedTouches.length) {
        clientX = e.changedTouches[0].clientX;
        clientY = e.changedTouches[0].clientY;
    }
    return { x: clientX - rect.left, y: clientY - rect.top, t: Date.now() };
}

function startDraw(e) {
    drawing = true;
    if (!signatureStartAt) signatureStartAt = Date.now();
    const pos = getPos(e);
    points.push(pos);
    ctx.beginPath();
    ctx.moveTo(pos.x, pos.y);
}

function draw(e) {
    if (!drawing) return;
    const pos = getPos(e);
    points.push(pos);
    ctx.lineTo(pos.x, pos.y);
    ctx.stroke();
}

function endDraw() {
    drawing = false;
    ctx.closePath();
    atualizarBotaoConfirmar();
}

function limparAssinatura() {
    ctx.save();
    ctx.setTransform(1, 0, 0, 1, 0, 0);
    ctx.clearRect(0, 0, canvas.width, canvas.height);
    ctx.restore();
    points = [];
    signatureStartAt = null;
    atualizarBotaoConfirmar();
}

function atualizarBotaoConfirmar() {
    const hasDraw = points.length > 2;
    const checked = document.getElementById('checkbox_concordo').checked;
    let confirmacaoOk = true;
    const cpfInput = document.getElementById('cpf_confirmacao');
    const celularInput = document.getElementById('celular_confirmacao');
    const nomeInput = document.getElementById('nome_confirmacao');
    const nomeErro = document.getElementById('nome_erro');
    const cpfErro = document.getElementById('cpf_erro');
    const celularErro = document.getElementById('celular_erro');
    if (cpfInput && celularInput) {
        const cpfValido = validarCPFNumericoJS(cpfInput.value);
        const celValido = validarTelefoneInput(celularInput.value);
        const nomeValido = nomeInput ? (nomeInput.value.trim().length >= 3) : true;
        confirmacaoOk = cpfValido && celValido && nomeValido;

        if (nomeInput && !nomeValido) {
            nomeInput.classList.add('border-error-500');
            nomeInput.classList.remove('border-gray-300', 'dark:border-gray-700');
            if (nomeErro) {
                nomeErro.textContent = 'Informe o nome completo.';
                nomeErro.classList.remove('hidden');
            }
        } else if (nomeInput) {
            nomeInput.classList.remove('border-error-500');
            if (nomeErro) nomeErro.classList.add('hidden');
        }

        if (!cpfValido) {
            cpfInput.classList.add('border-error-500');
            cpfInput.classList.remove('border-gray-300', 'dark:border-gray-700');
            if (cpfErro) {
                cpfErro.textContent = 'CPF inválido.';
                cpfErro.classList.remove('hidden');
            }
        } else {
            cpfInput.classList.remove('border-error-500');
            if (cpfErro) cpfErro.classList.add('hidden');
        }

        if (!celValido) {
            celularInput.classList.add('border-error-500');
            celularInput.classList.remove('border-gray-300', 'dark:border-gray-700');
            if (celularErro) {
                celularErro.textContent = 'Celular inválido.';
                celularErro.classList.remove('hidden');
            }
        } else {
            celularInput.classList.remove('border-error-500');
            if (celularErro) celularErro.classList.add('hidden');
        }
    }
    const tipoAssinatura = document.querySelector('input[name="signature_type"]:checked')?.value || 'desenho';
    const typedOk = tipoAssinatura === 'digitada'
        ? (signatureTyped && signatureTyped.value.trim().length >= 3)
        : hasDraw;
    document.getElementById('btn_confirmar').disabled = !(typedOk && checked && confirmacaoOk);
    document.getElementById('checkbox_checked').value = checked ? '1' : '0';
}

canvas.addEventListener('mousedown', startDraw);
canvas.addEventListener('mousemove', draw);
canvas.addEventListener('mouseup', endDraw);
canvas.addEventListener('mouseleave', endDraw);
canvas.addEventListener('touchstart', (e) => { e.preventDefault(); startDraw(e); });
canvas.addEventListener('touchmove', (e) => { e.preventDefault(); draw(e); });
canvas.addEventListener('touchend', (e) => { e.preventDefault(); endDraw(); });

document.getElementById('limpar_assinatura').addEventListener('click', limparAssinatura);
document.getElementById('checkbox_concordo').addEventListener('change', () => {
    atualizarBotaoConfirmar();
    registrarEvento('checkbox_marcado', { checked: document.getElementById('checkbox_concordo').checked });
});

signatureTypeInputs.forEach((input) => {
    input.addEventListener('change', () => {
        const tipo = document.querySelector('input[name="signature_type"]:checked')?.value || 'desenho';
        if (tipo === 'digitada') {
            assinaturaDesenho.classList.add('hidden');
            assinaturaDigitada.classList.remove('hidden');
            if (nomeConfirmacao && signatureTyped && !signatureTyped.value.trim()) {
                signatureTyped.value = nomeConfirmacao.value.trim();
            }
            atualizarPreviewAssinatura();
        } else {
            assinaturaDigitada.classList.add('hidden');
            assinaturaDesenho.classList.remove('hidden');
            requestAnimationFrame(() => resizeCanvasNow());
        }
        atualizarBotaoConfirmar();
    });
});

function tentarGeolocalizacao() {
    const status = document.getElementById('geo_status');
    if (!status) {
        return;
    }
    if (!navigator.geolocation) {
        status.textContent = 'Geolocalização indisponível.';
        return;
    }
    status.textContent = 'Tentando obter localização...';
    navigator.geolocation.getCurrentPosition((pos) => {
        document.getElementById('geo').value = JSON.stringify({
            lat: pos.coords.latitude,
            lng: pos.coords.longitude,
            accuracy: pos.coords.accuracy,
        });
        status.textContent = 'Localização capturada.';
    }, () => {
        status.textContent = 'Não foi possível obter localização.';
    }, { enableHighAccuracy: false, timeout: 6000 });
}

function registrarEvento(evento, data) {
    fetch("{{ route('assinaturas.event') }}", {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
        },
        body: JSON.stringify({
            token: "{{ $token ?? '' }}",
            ordem_servico_id: "{{ $ordem->id }}",
            evento: evento,
            data: data || {},
            tipo: "{{ $tipo }}",
            session_uuid: "{{ $sessionUuid }}"
        })
    }).catch(() => {});
}

function formatarCPFInput(value) {
    const digits = (value || '').replace(/\D/g, '').slice(0, 11);
    if (!digits) return '';
    let formatted = digits;
    if (digits.length > 3) formatted = digits.slice(0, 3) + '.' + digits.slice(3);
    if (digits.length > 6) formatted = formatted.slice(0, 7) + '.' + digits.slice(6);
    if (digits.length > 9) formatted = formatted.slice(0, 11) + '-' + digits.slice(9);
    return formatted;
}

function validarCPFNumericoJS(value) {
    const clean = (value || '').replace(/\D/g, '');
    if (!clean || clean.length !== 11) return false;
    if (/^(\d)\1{10}$/.test(clean)) return false;

    for (let t = 9; t < 11; t++) {
        let sum = 0;
        for (let c = 0; c < t; c++) {
            sum += parseInt(clean[c], 10) * ((t + 1) - c);
        }
        const d = ((10 * sum) % 11) % 10;
        if (parseInt(clean[t], 10) !== d) return false;
    }

    return true;
}

function formatarTelefoneInput(value) {
    const digits = (value || '').replace(/\D/g, '').slice(0, 11);
    if (!digits) return '';
    if (digits.length <= 2) return `(${digits}`;
    const ddd = digits.slice(0, 2);
    const rest = digits.slice(2);
    if (rest.length <= 4) return `(${ddd}) ${rest}`;
    if (rest.length <= 8) return `(${ddd}) ${rest.slice(0, 4)}-${rest.slice(4)}`;
    return `(${ddd}) ${rest.slice(0, 5)}-${rest.slice(5, 9)}`;
}

function validarTelefoneInput(value) {
    const digits = (value || '').replace(/\D/g, '');
    return digits.length === 10 || digits.length === 11;
}

const assinaturaForm = canvas.closest('form');
if (assinaturaForm) {
assinaturaForm.addEventListener('submit', (e) => {
    if (points.length < 3) {
        const tipo = document.querySelector('input[name="signature_type"]:checked')?.value || 'desenho';
        if (tipo === 'desenho') {
            e.preventDefault();
            alert('Faça a assinatura antes de confirmar.');
            return;
        }
    }
    const cpfInput = document.getElementById('cpf_confirmacao');
    const celularInput = document.getElementById('celular_confirmacao');
    if (cpfInput && celularInput) {
        if (!validarCPFNumericoJS(cpfInput.value)) {
            e.preventDefault();
            alert('CPF inválido.');
            return;
        }
        if (!validarTelefoneInput(celularInput.value)) {
            e.preventDefault();
            alert('Celular inválido.');
            return;
        }
    }
    const tipoAssinatura = document.querySelector('input[name="signature_type"]:checked')?.value || 'desenho';
    let dataUrl = '';
    if (tipoAssinatura === 'digitada' && signatureTyped) {
        dataUrl = gerarAssinaturaDigitada(signatureTyped.value.trim(), signatureFont?.value);
        document.getElementById('signature_points').value = '';
    } else {
        dataUrl = canvas.toDataURL('image/png');
        document.getElementById('signature_points').value = JSON.stringify(points);
    }
    document.getElementById('signature_data').value = dataUrl;
    document.getElementById('checkbox_checked').value = document.getElementById('checkbox_concordo').checked ? '1' : '0';
    registrarEvento('assinatura_confirmada', { pontos: points.length });

    const extra = coletarMetadados();
    document.getElementById('extra_metadata').value = JSON.stringify(extra);
});
}

resizeCanvasNow();
window.addEventListener('resize', scheduleResizeCanvas);
if (window.visualViewport) {
    window.visualViewport.addEventListener('resize', scheduleResizeCanvas);
}
registrarEvento('pagina_aberta', {});
tentarGeolocalizacao();

const cpfInput = document.getElementById('cpf_confirmacao');
if (cpfInput) {
    cpfInput.addEventListener('input', (e) => {
        e.target.value = formatarCPFInput(e.target.value);
        atualizarBotaoConfirmar();
    });
    cpfInput.addEventListener('blur', (e) => {
        if (!e.target.value) return;
        if (!validarCPFNumericoJS(e.target.value)) {
            e.target.setCustomValidity('CPF inválido.');
        } else {
            e.target.setCustomValidity('');
        }
        atualizarBotaoConfirmar();
    });
}

const celularInput = document.getElementById('celular_confirmacao');
if (celularInput) {
    celularInput.addEventListener('input', (e) => {
        e.target.value = formatarTelefoneInput(e.target.value);
        atualizarBotaoConfirmar();
    });
    celularInput.addEventListener('blur', (e) => {
        if (!e.target.value) return;
        if (!validarTelefoneInput(e.target.value)) {
            e.target.setCustomValidity('Celular inválido.');
        } else {
            e.target.setCustomValidity('');
        }
        atualizarBotaoConfirmar();
    });
}

if (nomeConfirmacao) {
    nomeConfirmacao.addEventListener('input', () => {
        if (signatureTyped && signatureTyped.value.trim().length === 0) {
            signatureTyped.value = nomeConfirmacao.value.trim();
            atualizarPreviewAssinatura();
        }
        atualizarBotaoConfirmar();
    });
}

if (signatureTyped) {
    signatureTyped.addEventListener('input', () => {
        atualizarPreviewAssinatura();
        atualizarBotaoConfirmar();
    });
}

function coletarMetadados() {
    const tipoAssinatura = document.querySelector('input[name="signature_type"]:checked')?.value || 'desenho';
    const tz = Intl.DateTimeFormat().resolvedOptions().timeZone || '';
    const screenInfo = {
        width: window.screen?.width || null,
        height: window.screen?.height || null,
        availWidth: window.screen?.availWidth || null,
        availHeight: window.screen?.availHeight || null,
        devicePixelRatio: window.devicePixelRatio || 1,
    };
    const connection = navigator.connection || navigator.mozConnection || navigator.webkitConnection;
    const network = connection ? {
        effectiveType: connection.effectiveType,
        downlink: connection.downlink,
        rtt: connection.rtt,
        saveData: connection.saveData,
    } : null;
    const durationMs = signatureStartAt ? (Date.now() - signatureStartAt) : null;
    return {
        assinatura_tipo: tipoAssinatura,
        assinatura_digitada: signatureTyped ? signatureTyped.value.trim() : null,
        assinatura_fonte: signatureFont ? signatureFont.value : null,
        pontos_total: points.length,
        inicio_desenho_em: signatureStartAt ? new Date(signatureStartAt).toISOString() : null,
        duracao_ms: durationMs,
        user_agent: navigator.userAgent,
        language: navigator.language,
        timezone: tz,
        screen: screenInfo,
        network: network,
        device: /Mobi|Android/i.test(navigator.userAgent) ? 'mobile' : 'desktop',
    };
}

function gerarAssinaturaDigitada(texto, fonte) {
    const tempCanvas = document.createElement('canvas');
    const fontSize = 36;
    const padding = 16;
    tempCanvas.width = 800;
    tempCanvas.height = 140;
    const tctx = tempCanvas.getContext('2d');
    tctx.fillStyle = '#ffffff';
    tctx.fillRect(0, 0, tempCanvas.width, tempCanvas.height);
    tctx.fillStyle = '#111827';
    tctx.font = `${fontSize}px ${fonte || '"Segoe Script", "Brush Script MT", "Pacifico", cursive'}`;
    tctx.textBaseline = 'middle';
    tctx.fillText(texto, padding, tempCanvas.height / 2);
    return tempCanvas.toDataURL('image/png');
}

function atualizarPreviewAssinatura() {
    if (!signaturePreview) return;
    const texto = signatureTyped ? signatureTyped.value.trim() : '';
    const fonte = signatureFont ? signatureFont.value : '';
    signaturePreview.textContent = texto || 'Prévia da assinatura';
    if (fonte) {
        signaturePreview.style.fontFamily = fonte;
    }
}

if (signatureFont) {
    signatureFont.addEventListener('change', atualizarPreviewAssinatura);
}
})();
</script>
@endpush
@endsection
