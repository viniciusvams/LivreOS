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

<div class="mb-4 rounded-lg border border-gray-200 p-4 dark:border-gray-700" data-index="{{ $index }}" data-endereco-block>
    <div class="mb-3 flex items-center justify-between">
        <h3 class="font-medium text-gray-700 dark:text-gray-300">Endereço {{ $index + 1 }}</h3>
        @if($index > 0)
        <button type="button" onclick="removerEndereco(this)" class="text-error-500 hover:text-error-700">Remover</button>
        @endif
    </div>
    <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
        <div>
            <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300">CEP</label>
            <input type="text" name="enderecos[{{ $index }}][cep]" value="{{ old("enderecos.{$index}.cep", $endereco['cep'] ?? '') }}" data-cep data-endereco-field="cep" class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm text-gray-800 focus:border-brand-500 focus:ring-brand-500 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90">
        </div>
        <div>
            <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300">Logradouro</label>
            <input type="text" name="enderecos[{{ $index }}][logradouro]" value="{{ old("enderecos.{$index}.logradouro", $endereco['logradouro'] ?? '') }}" data-endereco-field="logradouro" class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm text-gray-800 focus:border-brand-500 focus:ring-brand-500 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90">
        </div>
        <div>
            <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300">Número</label>
            <input type="text" name="enderecos[{{ $index }}][numero]" value="{{ old("enderecos.{$index}.numero", $endereco['numero'] ?? '') }}" data-endereco-field="numero" class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm text-gray-800 focus:border-brand-500 focus:ring-brand-500 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90">
        </div>
        <div>
            <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300">Complemento</label>
            <input type="text" name="enderecos[{{ $index }}][complemento]" value="{{ old("enderecos.{$index}.complemento", $endereco['complemento'] ?? '') }}" data-endereco-field="complemento" class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm text-gray-800 focus:border-brand-500 focus:ring-brand-500 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90">
        </div>
        <div>
            <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300">Bairro</label>
            <input type="text" name="enderecos[{{ $index }}][bairro]" value="{{ old("enderecos.{$index}.bairro", $endereco['bairro'] ?? '') }}" data-endereco-field="bairro" class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm text-gray-800 focus:border-brand-500 focus:ring-brand-500 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90">
        </div>
        <div>
            <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300">Estado (UF)</label>
            <select name="enderecos[{{ $index }}][estado]" data-endereco-field="estado" class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm text-gray-800 focus:border-brand-500 focus:ring-brand-500 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90">
                <option value="">Selecione...</option>
                @foreach(['AC','AL','AP','AM','BA','CE','DF','ES','GO','MA','MT','MS','MG','PA','PB','PR','PE','PI','RJ','RN','RS','RO','RR','SC','SP','SE','TO'] as $uf)
                <option value="{{ $uf }}" {{ old("enderecos.{$index}.estado", $endereco['estado'] ?? '') == $uf ? 'selected' : '' }}>{{ $uf }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300">Cidade</label>
            <select name="enderecos[{{ $index }}][cidade]" data-endereco-field="cidade" data-selected="{{ old("enderecos.{$index}.cidade", $endereco['cidade'] ?? '') }}" class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm text-gray-800 focus:border-brand-500 focus:ring-brand-500 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90">
                <option value="">Selecione a UF primeiro...</option>
            </select>
        </div>
        <div class="flex items-center gap-4">
            <label class="flex items-center gap-2">
                <input type="checkbox" name="enderecos[{{ $index }}][principal]" value="1" {{ old("enderecos.{$index}.principal", $endereco['principal'] ?? false) ? 'checked' : '' }} class="rounded border-gray-300 text-brand-500 focus:ring-brand-500">
                <span class="text-sm text-gray-700 dark:text-gray-300">Principal</span>
            </label>
            <label class="flex items-center gap-2">
                <input type="checkbox" name="enderecos[{{ $index }}][cobranca]" value="1" {{ old("enderecos.{$index}.cobranca", $endereco['cobranca'] ?? false) ? 'checked' : '' }} class="rounded border-gray-300 text-brand-500 focus:ring-brand-500">
                <span class="text-sm text-gray-700 dark:text-gray-300">Cobrança</span>
            </label>
            <label class="flex items-center gap-2">
                <input type="checkbox" name="enderecos[{{ $index }}][entrega]" value="1" {{ old("enderecos.{$index}.entrega", $endereco['entrega'] ?? false) ? 'checked' : '' }} class="rounded border-gray-300 text-brand-500 focus:ring-brand-500">
                <span class="text-sm text-gray-700 dark:text-gray-300">Entrega</span>
            </label>
        </div>
    </div>
</div>
