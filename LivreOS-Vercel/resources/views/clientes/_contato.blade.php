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

<div class="mb-4 rounded-lg border border-gray-200 p-4 dark:border-gray-700" data-index="{{ $index }}">
    <div class="mb-3 flex items-center justify-between">
        <h3 class="font-medium text-gray-700 dark:text-gray-300">Contato {{ $index + 1 }}</h3>
        @php
            $naoRemovivel = !empty($contato['is_fornecedor'])
                || (!empty($contato['produto_fornecedores_count']) && $contato['produto_fornecedores_count'] > 0);
        @endphp
        <button type="button" onclick="removerContato(this)" class="text-error-500 hover:text-error-700 disabled:cursor-not-allowed disabled:text-gray-400" {{ $naoRemovivel ? 'disabled' : '' }}>Remover</button>
    </div>
    @if($naoRemovivel)
        <div class="mb-3 rounded-lg border border-warning-100 bg-warning-50 px-3 py-2 text-xs text-warning-700 dark:border-warning-900/40 dark:bg-gray-900 dark:text-warning-300">
            Este contato está vinculado a um fornecedor/produto e não pode ser removido aqui.
        </div>
    @endif
    <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
        @if(!empty($contato['id']))
            <input type="hidden" name="contatos[{{ $index }}][id]" value="{{ $contato['id'] }}">
        @endif
        <div>
            <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300">Nome</label>
            <input type="text" name="contatos[{{ $index }}][nome]" value="{{ old("contatos.{$index}.nome", $contato['nome'] ?? '') }}" class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm text-gray-800 focus:border-brand-500 focus:ring-brand-500 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90">
        </div>
        <div>
            <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300">Telefone</label>
            <input type="text" name="contatos[{{ $index }}][telefone]" value="{{ old("contatos.{$index}.telefone", $contato['telefone'] ?? '') }}" class="js-phone-mask w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm text-gray-800 focus:border-brand-500 focus:ring-brand-500 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90">
            <label class="mt-2 flex items-center gap-2 text-xs text-gray-600 dark:text-gray-400">
                <input type="checkbox" name="contatos[{{ $index }}][telefone_whatsapp]" value="1" {{ old("contatos.{$index}.telefone_whatsapp", $contato['telefone_whatsapp'] ?? false) ? 'checked' : '' }} class="rounded border-gray-300 text-brand-500 focus:ring-brand-500">
                WhatsApp
            </label>
        </div>
        <div>
            <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300">Telefone 2</label>
            <input type="text" name="contatos[{{ $index }}][telefone2]" value="{{ old("contatos.{$index}.telefone2", $contato['telefone2'] ?? '') }}" class="js-phone-mask w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm text-gray-800 focus:border-brand-500 focus:ring-brand-500 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90">
        </div>
        <div>
            <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300">E-mail</label>
            <input type="email" name="contatos[{{ $index }}][email]" value="{{ old("contatos.{$index}.email", $contato['email'] ?? '') }}" class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm text-gray-800 focus:border-brand-500 focus:ring-brand-500 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90">
        </div>
        <div>
            <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300">E-mail 2</label>
            <input type="email" name="contatos[{{ $index }}][email2]" value="{{ old("contatos.{$index}.email2", $contato['email2'] ?? '') }}" class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm text-gray-800 focus:border-brand-500 focus:ring-brand-500 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90">
        </div>
        <div>
            <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300">Cargo</label>
            <input type="text" name="contatos[{{ $index }}][cargo]" value="{{ old("contatos.{$index}.cargo", $contato['cargo'] ?? '') }}" class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm text-gray-800 focus:border-brand-500 focus:ring-brand-500 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90">
        </div>
        <div class="flex items-center gap-4">
            <label class="flex items-center gap-2">
                <input type="checkbox" name="contatos[{{ $index }}][principal]" value="1" {{ old("contatos.{$index}.principal", $contato['principal'] ?? false) ? 'checked' : '' }} class="rounded border-gray-300 text-brand-500 focus:ring-brand-500">
                <span class="text-sm text-gray-700 dark:text-gray-300">Principal</span>
            </label>
            <label class="flex items-center gap-2">
                <input type="checkbox" name="contatos[{{ $index }}][cobranca]" value="1" {{ old("contatos.{$index}.cobranca", $contato['cobranca'] ?? false) ? 'checked' : '' }} class="rounded border-gray-300 text-brand-500 focus:ring-brand-500">
                <span class="text-sm text-gray-700 dark:text-gray-300">Cobrança</span>
            </label>
        </div>
    </div>
</div>
