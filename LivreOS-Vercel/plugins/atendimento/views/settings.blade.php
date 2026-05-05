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
    use Atendimento\Models\StatusAtendimento;
    use Atendimento\Models\PrioridadeAtendimento;
    use Atendimento\Models\TempoEstimadoAtendimento;
    use App\Models\Configuracao;
    $statusList = StatusAtendimento::orderBy('ordem')->orderBy('nome')->get();
    $prioridadesList = PrioridadeAtendimento::orderBy('ordem')->orderBy('nome')->get();
    $temposList = TempoEstimadoAtendimento::orderBy('ordem')->orderBy('minutos')->get();
    $mapsEnabled = (bool) (Configuracao::getValue('plugin_atendimento_maps_enabled', '0') === '1');
    $mapsApiKey = Configuracao::getValue('plugin_atendimento_google_maps_api_key', '');
@endphp
<div class="mt-6 rounded-lg border border-gray-200 bg-white shadow-theme-sm dark:border-gray-800 dark:bg-gray-800">
    <div class="border-b border-gray-200 px-6 py-4 dark:border-gray-700">
        <h2 class="text-lg font-semibold text-gray-800 dark:text-white/90">Configurações do plugin Atendimento</h2>
        <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Gerencie os status, prioridades e tempos estimados usados nos atendimentos externos.</p>
    </div>
    <div class="p-6 space-y-8">
        @if(session('success'))
            <div class="rounded-lg bg-success-100 px-4 py-3 text-sm text-success-700 dark:bg-success-500/20 dark:text-success-400">{{ session('success') }}</div>
        @endif
        @if(session('error'))
            <div class="rounded-lg bg-error-100 px-4 py-3 text-sm text-error-700 dark:bg-error-500/20 dark:text-error-400">{{ session('error') }}</div>
        @endif

        {{-- Google Maps (Rota do dia) --}}
        <div>
            <h3 class="mb-3 text-base font-semibold text-gray-800 dark:text-white/90">Google Maps (Rota do dia)</h3>
            <p class="mb-4 text-sm text-gray-500 dark:text-gray-400">Exibir distância e tempo de deslocamento entre endereços na página "Rota do dia". Os resultados são armazenados em cache; a API só é chamada para trechos novos ou alterados.</p>
            <form action="{{ route('plugin.atendimento.configuracoes.maps.update') }}" method="POST" class="max-w-xl space-y-4">
                @csrf
                <div class="flex items-center gap-3">
                    <label class="flex items-center gap-2">
                        <input type="hidden" name="maps_enabled" value="0">
                        <input type="checkbox" name="maps_enabled" value="1" {{ $mapsEnabled ? 'checked' : '' }} class="rounded border-gray-300 text-brand-500">
                        <span class="text-sm font-medium text-gray-700 dark:text-gray-300">Usar distância e tempo entre endereços na Rota do dia</span>
                    </label>
                </div>
                <div>
                    <label class="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-300">Chave API Google Maps (Distance Matrix)</label>
                    <input type="password" name="google_maps_api_key" value="" autocomplete="off" placeholder="{{ $mapsApiKey ? '•••••••• (deixe em branco para manter a chave atual)' : 'Sua chave da API' }}" class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm dark:border-gray-600 dark:bg-gray-900 dark:text-white/90">
                    <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">Ative a "Distance Matrix API" no Google Cloud Console. Deixe em branco para não alterar a chave já salva.</p>
                </div>
                <button type="submit" class="rounded-lg bg-brand-500 px-4 py-2 text-sm font-medium text-white hover:bg-brand-600">Salvar configuração Maps</button>
            </form>
        </div>

        {{-- Status do Atendimento --}}
        <div>
            <h3 class="mb-3 text-base font-semibold text-gray-800 dark:text-white/90">Status do Atendimento</h3>
            <p class="mb-4 text-sm text-gray-500 dark:text-gray-400">Crie e edite os status exibidos no cadastro (ex.: Pendente, Em andamento, Concluído).</p>
            <div class="mb-4 overflow-hidden rounded-lg border border-gray-200 dark:border-gray-700">
                <table class="w-full text-sm">
                    <thead class="bg-gray-50 dark:bg-gray-900">
                        <tr>
                            <th class="px-4 py-2 text-left font-medium text-gray-700 dark:text-gray-300">Nome</th>
                            <th class="px-4 py-2 text-left font-medium text-gray-700 dark:text-gray-300">Cor</th>
                            <th class="px-4 py-2 text-left font-medium text-gray-700 dark:text-gray-300">Ordem</th>
                            <th class="px-4 py-2 text-right font-medium text-gray-700 dark:text-gray-300">Ações</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                        @foreach($statusList as $s)
                        <tr>
                            <td class="px-4 py-2">
                                <span class="inline-flex rounded-full px-2.5 py-0.5 text-xs font-medium" style="background-color: {{ $s->cor ?: '#6b7280' }}20; color: {{ $s->cor ?: '#6b7280' }};">{{ $s->nome }}</span>
                            </td>
                            <td class="px-4 py-2"><code class="text-xs">{{ $s->cor ?: '—' }}</code></td>
                            <td class="px-4 py-2">{{ $s->ordem }}</td>
                            <td class="px-4 py-2 text-right">
                                <form action="{{ route('plugin.atendimento.configuracoes.status.update', $s->id) }}" method="POST" class="inline-block">
                                    @csrf
                                    @method('PUT')
                                    <input type="text" name="nome" value="{{ $s->nome }}" class="mr-1 w-40 rounded border border-gray-300 px-2 py-1 text-sm dark:border-gray-600 dark:bg-gray-900 dark:text-white/90" placeholder="Nome">
                                    <input type="text" name="cor" value="{{ $s->cor }}" class="mr-1 w-24 rounded border border-gray-300 px-2 py-1 text-sm dark:border-gray-600 dark:bg-gray-900 dark:text-white/90" placeholder="#hex">
                                    <input type="number" name="ordem" value="{{ $s->ordem }}" min="0" class="mr-1 w-16 rounded border border-gray-300 px-2 py-1 text-sm dark:border-gray-600 dark:bg-gray-900 dark:text-white/90">
                                    <button type="submit" class="rounded border border-gray-300 bg-white px-2 py-1 text-xs font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-300">Atualizar</button>
                                </form>
                                <form action="{{ route('plugin.atendimento.configuracoes.status.destroy', $s->id) }}" method="POST" class="ml-1 inline-block" onsubmit="return confirm('Excluir este status?');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="rounded border border-red-200 bg-red-50 px-2 py-1 text-xs font-medium text-red-700 hover:bg-red-100 dark:border-red-800 dark:bg-red-900/30 dark:text-red-400">Excluir</button>
                                </form>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <form action="{{ route('plugin.atendimento.configuracoes.status.store') }}" method="POST" class="flex flex-wrap items-end gap-2">
                @csrf
                <input type="text" name="nome" required class="rounded-lg border border-gray-300 px-3 py-2 text-sm dark:border-gray-600 dark:bg-gray-900 dark:text-white/90" placeholder="Novo status">
                <input type="text" name="cor" class="rounded-lg border border-gray-300 px-3 py-2 text-sm dark:border-gray-600 dark:bg-gray-900 dark:text-white/90" placeholder="#cor (ex: #2563eb)">
                <input type="number" name="ordem" value="0" min="0" class="w-20 rounded-lg border border-gray-300 px-3 py-2 text-sm dark:border-gray-600 dark:bg-gray-900 dark:text-white/90">
                <button type="submit" class="rounded-lg bg-brand-500 px-4 py-2 text-sm font-medium text-white hover:bg-brand-600">Adicionar status</button>
            </form>
        </div>

        {{-- Prioridades --}}
        <div>
            <h3 class="mb-3 text-base font-semibold text-gray-800 dark:text-white/90">Prioridades (com cores)</h3>
            <p class="mb-4 text-sm text-gray-500 dark:text-gray-400">Crie e edite as prioridades exibidas no cadastro (ex.: Baixa, Média, Alta, Urgente).</p>
            <div class="mb-4 overflow-hidden rounded-lg border border-gray-200 dark:border-gray-700">
                <table class="w-full text-sm">
                    <thead class="bg-gray-50 dark:bg-gray-900">
                        <tr>
                            <th class="px-4 py-2 text-left font-medium text-gray-700 dark:text-gray-300">Nome</th>
                            <th class="px-4 py-2 text-left font-medium text-gray-700 dark:text-gray-300">Cor</th>
                            <th class="px-4 py-2 text-left font-medium text-gray-700 dark:text-gray-300">Ordem</th>
                            <th class="px-4 py-2 text-right font-medium text-gray-700 dark:text-gray-300">Ações</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                        @foreach($prioridadesList as $p)
                        <tr>
                            <td class="px-4 py-2">
                                <span class="inline-flex rounded-full px-2.5 py-0.5 text-xs font-medium" style="background-color: {{ $p->cor ?: '#6b7280' }}20; color: {{ $p->cor ?: '#6b7280' }};">{{ $p->nome }}</span>
                            </td>
                            <td class="px-4 py-2"><code class="text-xs">{{ $p->cor ?: '—' }}</code></td>
                            <td class="px-4 py-2">{{ $p->ordem }}</td>
                            <td class="px-4 py-2 text-right">
                                <form action="{{ route('plugin.atendimento.configuracoes.prioridades.update', $p->id) }}" method="POST" class="inline-block">
                                    @csrf
                                    @method('PUT')
                                    <input type="text" name="nome" value="{{ $p->nome }}" class="mr-1 w-40 rounded border border-gray-300 px-2 py-1 text-sm dark:border-gray-600 dark:bg-gray-900 dark:text-white/90" placeholder="Nome">
                                    <input type="text" name="cor" value="{{ $p->cor }}" class="mr-1 w-24 rounded border border-gray-300 px-2 py-1 text-sm dark:border-gray-600 dark:bg-gray-900 dark:text-white/90" placeholder="#hex">
                                    <input type="number" name="ordem" value="{{ $p->ordem }}" min="0" class="mr-1 w-16 rounded border border-gray-300 px-2 py-1 text-sm dark:border-gray-600 dark:bg-gray-900 dark:text-white/90">
                                    <button type="submit" class="rounded border border-gray-300 bg-white px-2 py-1 text-xs font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-300">Atualizar</button>
                                </form>
                                <form action="{{ route('plugin.atendimento.configuracoes.prioridades.destroy', $p->id) }}" method="POST" class="ml-1 inline-block" onsubmit="return confirm('Excluir esta prioridade?');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="rounded border border-red-200 bg-red-50 px-2 py-1 text-xs font-medium text-red-700 hover:bg-red-100 dark:border-red-800 dark:bg-red-900/30 dark:text-red-400">Excluir</button>
                                </form>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <form action="{{ route('plugin.atendimento.configuracoes.prioridades.store') }}" method="POST" class="flex flex-wrap items-end gap-2">
                @csrf
                <input type="text" name="nome" required class="rounded-lg border border-gray-300 px-3 py-2 text-sm dark:border-gray-600 dark:bg-gray-900 dark:text-white/90" placeholder="Nova prioridade">
                <input type="text" name="cor" class="rounded-lg border border-gray-300 px-3 py-2 text-sm dark:border-gray-600 dark:bg-gray-900 dark:text-white/90" placeholder="#cor (ex: #ef4444)">
                <input type="number" name="ordem" value="0" min="0" class="w-20 rounded-lg border border-gray-300 px-3 py-2 text-sm dark:border-gray-600 dark:bg-gray-900 dark:text-white/90">
                <button type="submit" class="rounded-lg bg-brand-500 px-4 py-2 text-sm font-medium text-white hover:bg-brand-600">Adicionar prioridade</button>
            </form>
        </div>

        {{-- Tempo estimado --}}
        <div>
            <h3 class="mb-3 text-base font-semibold text-gray-800 dark:text-white/90">Tempo estimado</h3>
            <p class="mb-4 text-sm text-gray-500 dark:text-gray-400">Opções exibidas no cadastro de atendimento (ex.: 30 min, 1 h, 2 h).</p>
            <div class="mb-4 overflow-hidden rounded-lg border border-gray-200 dark:border-gray-700">
                <table class="w-full text-sm">
                    <thead class="bg-gray-50 dark:bg-gray-900">
                        <tr>
                            <th class="px-4 py-2 text-left font-medium text-gray-700 dark:text-gray-300">Label</th>
                            <th class="px-4 py-2 text-left font-medium text-gray-700 dark:text-gray-300">Minutos</th>
                            <th class="px-4 py-2 text-left font-medium text-gray-700 dark:text-gray-300">Ordem</th>
                            <th class="px-4 py-2 text-right font-medium text-gray-700 dark:text-gray-300">Ações</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                        @foreach($temposList as $t)
                        <tr>
                            <td class="px-4 py-2 text-gray-800 dark:text-white/90">{{ $t->label }}</td>
                            <td class="px-4 py-2">{{ $t->minutos }}</td>
                            <td class="px-4 py-2">{{ $t->ordem }}</td>
                            <td class="px-4 py-2 text-right">
                                <form action="{{ route('plugin.atendimento.configuracoes.tempos.update', $t->id) }}" method="POST" class="inline-block">
                                    @csrf
                                    @method('PUT')
                                    <input type="text" name="label" value="{{ $t->label }}" class="mr-1 w-32 rounded border border-gray-300 px-2 py-1 text-sm dark:border-gray-600 dark:bg-gray-900 dark:text-white/90" placeholder="Ex: 1 h">
                                    <input type="number" name="minutos" value="{{ $t->minutos }}" min="1" max="999" class="mr-1 w-20 rounded border border-gray-300 px-2 py-1 text-sm dark:border-gray-600 dark:bg-gray-900 dark:text-white/90">
                                    <input type="number" name="ordem" value="{{ $t->ordem }}" min="0" class="mr-1 w-16 rounded border border-gray-300 px-2 py-1 text-sm dark:border-gray-600 dark:bg-gray-900 dark:text-white/90">
                                    <button type="submit" class="rounded border border-gray-300 bg-white px-2 py-1 text-xs font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-300">Atualizar</button>
                                </form>
                                <form action="{{ route('plugin.atendimento.configuracoes.tempos.destroy', $t->id) }}" method="POST" class="ml-1 inline-block" onsubmit="return confirm('Excluir este tempo?');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="rounded border border-red-200 bg-red-50 px-2 py-1 text-xs font-medium text-red-700 hover:bg-red-100 dark:border-red-800 dark:bg-red-900/30 dark:text-red-400">Excluir</button>
                                </form>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <form action="{{ route('plugin.atendimento.configuracoes.tempos.store') }}" method="POST" class="flex flex-wrap items-end gap-2">
                @csrf
                <input type="text" name="label" required class="rounded-lg border border-gray-300 px-3 py-2 text-sm dark:border-gray-600 dark:bg-gray-900 dark:text-white/90" placeholder="Ex: 45 min">
                <input type="number" name="minutos" required min="1" max="999" class="w-24 rounded-lg border border-gray-300 px-3 py-2 text-sm dark:border-gray-600 dark:bg-gray-900 dark:text-white/90" placeholder="Minutos">
                <input type="number" name="ordem" value="0" min="0" class="w-20 rounded-lg border border-gray-300 px-3 py-2 text-sm dark:border-gray-600 dark:bg-gray-900 dark:text-white/90">
                <button type="submit" class="rounded-lg bg-brand-500 px-4 py-2 text-sm font-medium text-white hover:bg-brand-600">Adicionar tempo</button>
            </form>
        </div>
    </div>
</div>
