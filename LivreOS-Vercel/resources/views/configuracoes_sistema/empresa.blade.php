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
<div class="mb-6 flex items-center justify-between">
    <div>
        <h1 class="mb-2 text-2xl font-semibold text-gray-800 dark:text-white/90">Cadastro da Empresa</h1>
        <p class="text-gray-600 dark:text-gray-400">Dados para emissão de documentos e identidade visual.</p>
    </div>
    <a href="{{ route('configuracoes-sistema.index') }}" class="rounded-lg border border-gray-300 bg-white px-4 py-2.5 text-sm font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-300">Voltar</a>
</div>

@if(session('success'))
<div class="mb-4 rounded-lg bg-success-100 px-4 py-3 text-sm text-success-700 dark:bg-success-500/20 dark:text-success-400">
    {{ session('success') }}
</div>
@endif

@if($errors->any())
<div class="mb-4 rounded-lg bg-error-100 px-4 py-3 text-sm text-error-700 dark:bg-error-500/20 dark:text-error-400">
    <ul class="list-disc pl-4">
        @foreach($errors->all() as $error)
        <li>{{ $error }}</li>
        @endforeach
    </ul>
</div>
@endif

<form action="{{ route('configuracoes-sistema.empresa.update') }}" method="POST" enctype="multipart/form-data">
    @csrf
    @method('PUT')

    <div class="rounded-lg border border-gray-200 bg-white p-6 shadow-theme-sm dark:border-gray-800 dark:bg-gray-800">
        <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
            <div class="md:col-span-2">
                <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300">Nome</label>
                <input type="text" name="nome" value="{{ old('nome', $empresa->nome ?? '') }}" class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm text-gray-800 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90">
                <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">Exibido no menu e na interface. Deixe em branco para usar razão social.</p>
            </div>
            <div class="md:col-span-2">
                <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300">Razão social</label>
                <input type="text" name="razao_social" value="{{ old('razao_social', $empresa->razao_social ?? '') }}" class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm text-gray-800 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90">
            </div>
            <div>
                <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300">CNPJ</label>
                <div class="flex gap-2">
                    <input type="text" name="cnpj" id="cnpj" value="{{ old('cnpj', $empresa->cnpj ?? '') }}" placeholder="00.000.000/0000-00" class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm text-gray-800 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90">
                    <button type="button" id="btnBuscarCnpj" class="whitespace-nowrap rounded-lg border border-gray-300 bg-white px-4 py-2.5 text-sm font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-300">Buscar CNPJ</button>
                </div>
            </div>
            <div>
                <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300">Inscrição estadual</label>
                <input type="text" name="inscricao_estadual" value="{{ old('inscricao_estadual', $empresa->inscricao_estadual ?? '') }}" class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm text-gray-800 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90">
            </div>
            <div>
                <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300">Inscrição municipal</label>
                <input type="text" name="inscricao_municipal" value="{{ old('inscricao_municipal', $empresa->inscricao_municipal ?? '') }}" class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm text-gray-800 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90">
            </div>
            <div>
                <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300">E-mail</label>
                <input type="email" name="email" value="{{ old('email', $empresa->email ?? '') }}" class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm text-gray-800 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90">
            </div>
            <div>
                <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300">Telefone</label>
                <input type="text" name="telefone" value="{{ old('telefone', $empresa->telefone ?? '') }}" class="js-phone-mask w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm text-gray-800 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90">
            </div>
            <div>
                <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300">Celular</label>
                <input type="text" name="celular" value="{{ old('celular', $empresa->celular ?? '') }}" class="js-phone-mask w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm text-gray-800 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90">
                <label class="mt-2 flex items-center gap-2 text-sm text-gray-700 dark:text-gray-300">
                    <input type="checkbox" name="celular_whatsapp" value="1" {{ old('celular_whatsapp', $empresa->celular_whatsapp ?? false) ? 'checked' : '' }} class="rounded border-gray-300 text-brand-500 focus:ring-brand-500">
                    Número com WhatsApp
                </label>
            </div>
            <div>
                <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300">Site</label>
                <input type="text" name="site" value="{{ old('site', $empresa->site ?? '') }}" class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm text-gray-800 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90">
            </div>
            <div class="md:col-span-2" data-empresa-endereco>
                <h2 class="mt-2 text-sm font-semibold text-gray-700 dark:text-gray-300">Endereço</h2>
                <div class="mt-3 grid grid-cols-1 gap-4 md:grid-cols-2">
                    <div>
                        <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300">CEP</label>
                        <input type="text" name="cep" value="{{ old('cep', $empresa->cep ?? '') }}" data-cep class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm text-gray-800 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90">
                    </div>
                    <div>
                        <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300">Logradouro</label>
                        <input type="text" name="logradouro" value="{{ old('logradouro', $empresa->logradouro ?? '') }}" data-endereco-field="logradouro" class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm text-gray-800 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90">
                    </div>
                    <div>
                        <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300">Número</label>
                        <input type="text" name="numero" value="{{ old('numero', $empresa->numero ?? '') }}" data-endereco-field="numero" class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm text-gray-800 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90">
                    </div>
                    <div>
                        <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300">Complemento</label>
                        <input type="text" name="complemento" value="{{ old('complemento', $empresa->complemento ?? '') }}" data-endereco-field="complemento" class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm text-gray-800 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90">
                    </div>
                    <div>
                        <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300">Bairro</label>
                        <input type="text" name="bairro" value="{{ old('bairro', $empresa->bairro ?? '') }}" data-endereco-field="bairro" class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm text-gray-800 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90">
                    </div>
                    <div>
                        <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300">Estado</label>
                        <select name="estado" data-endereco-field="estado" class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm text-gray-800 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90">
                            <option value="">Selecione...</option>
                            @foreach(['AC','AL','AP','AM','BA','CE','DF','ES','GO','MA','MT','MS','MG','PA','PB','PR','PE','PI','RJ','RN','RS','RO','RR','SC','SP','SE','TO'] as $uf)
                                <option value="{{ $uf }}" {{ old('estado', $empresa->estado ?? '') == $uf ? 'selected' : '' }}>{{ $uf }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300">Cidade</label>
                        <select name="cidade" data-endereco-field="cidade" data-selected="{{ old('cidade', $empresa->cidade ?? '') }}" class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm text-gray-800 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90">
                            <option value="">Selecione a UF primeiro...</option>
                        </select>
                    </div>
                    <div>
                        <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300">País</label>
                        <input type="text" name="pais" value="{{ old('pais', $empresa->pais ?? '') }}" class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm text-gray-800 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90">
                    </div>
                </div>
            </div>
            <div class="md:col-span-2">
                <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300">Logotipo</label>
                @if(!empty($empresa?->logo_path))
                    @php
                        $logoPath = storage_path('app/public/' . $empresa->logo_path);
                        $logoUrl = null;
                        if (file_exists($logoPath)) {
                            $mime = @mime_content_type($logoPath) ?: 'image/png';
                            $logoUrl = 'data:' . $mime . ';base64,' . base64_encode(file_get_contents($logoPath));
                        } else {
                            $logoUrl = asset('storage/' . $empresa->logo_path);
                        }
                    @endphp
                    @if($logoUrl)
                    <div class="mb-2 flex items-center gap-4">
                        <img src="{{ $logoUrl }}" alt="Logotipo da empresa" class="h-20 w-auto rounded border border-gray-200 bg-white p-2">
                        <label class="flex items-center gap-2 text-sm text-gray-600 dark:text-gray-400">
                            <input type="checkbox" name="remover_logo" value="1" class="rounded border-gray-300 text-brand-500 focus:ring-brand-500">
                            Excluir logo
                        </label>
                    </div>
                    @endif
                @endif
                <input type="file" name="logo" accept="image/png,image/jpeg,image/webp,image/avif,image/heic,image/heif" class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm text-gray-800 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90">
            </div>
            <div class="md:col-span-2">
                <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300">Observações</label>
                <textarea name="observacoes" rows="3" class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm text-gray-800 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90">{{ old('observacoes', $empresa->observacoes ?? '') }}</textarea>
            </div>
        </div>

        <div class="mt-6 flex justify-end gap-3">
            <button type="submit" class="rounded-lg bg-brand-500 px-6 py-2.5 text-sm font-medium text-white hover:bg-brand-600">Salvar</button>
        </div>
    </div>
</form>

<script>
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

function bindPhoneMask(input) {
    if (!input) return;
    input.addEventListener('input', function (e) {
        e.target.value = formatarTelefoneInput(e.target.value);
    });
}

function normalizarCep(cep) {
    return (cep || '').replace(/\D/g, '').slice(0, 8);
}

async function buscarEnderecoPorCep(cep) {
    const url = `https://viacep.com.br/ws/${cep}/json/`;
    const response = await fetch(url);
    if (!response.ok) {
        return null;
    }
    const data = await response.json();
    if (data.erro) {
        return null;
    }
    return data;
}

function getEnderecoField(container, field) {
    return container.querySelector(`[data-endereco-field="${field}"]`);
}

const ibgeCidadesCacheEmpresa = {};
async function carregarCidadesPorUfEmpresa(uf) {
    const ufUpper = String(uf || '').trim().toUpperCase();
    if (ufUpper.length !== 2) return [];
    if (ibgeCidadesCacheEmpresa[ufUpper]) return ibgeCidadesCacheEmpresa[ufUpper];
    const resp = await fetch(`https://servicodados.ibge.gov.br/api/v1/localidades/estados/${ufUpper}/municipios`);
    if (!resp.ok) return [];
    const data = await resp.json();
    const cidades = Array.isArray(data) ? data.map((c) => String(c?.nome || '').trim()).filter(Boolean) : [];
    ibgeCidadesCacheEmpresa[ufUpper] = cidades;
    return cidades;
}

async function popularSelectCidadeEmpresa(container, cidadeSelecionada = '') {
    const estado = getEnderecoField(container, 'estado');
    const cidade = getEnderecoField(container, 'cidade');
    if (!estado || !cidade) return;
    const uf = String(estado.value || '').trim().toUpperCase();
    const cidadeAtual = String(cidadeSelecionada || cidade.dataset.selected || cidade.value || '').trim();
    cidade.innerHTML = '';
    if (uf.length !== 2) {
        cidade.innerHTML = '<option value="">Selecione a UF primeiro...</option>';
        return;
    }
    cidade.innerHTML = '<option value="">Carregando cidades...</option>';
    const cidades = await carregarCidadesPorUfEmpresa(uf);
    cidade.innerHTML = '<option value="">Selecione...</option>';
    cidades.forEach((nomeCidade) => {
        const opt = document.createElement('option');
        opt.value = nomeCidade;
        opt.textContent = nomeCidade;
        if (cidadeAtual && nomeCidade.toLowerCase() === cidadeAtual.toLowerCase()) opt.selected = true;
        cidade.appendChild(opt);
    });
    if (cidadeAtual && !cidades.some((c) => c.toLowerCase() === cidadeAtual.toLowerCase())) {
        const extra = document.createElement('option');
        extra.value = cidadeAtual;
        extra.textContent = `${cidadeAtual} (fora da lista)`;
        extra.selected = true;
        cidade.appendChild(extra);
    }
}

async function aplicarEnderecoPorCep(container, cepValue) {
    const cep = normalizarCep(cepValue);
    if (cep.length !== 8) {
        return;
    }

    const data = await buscarEnderecoPorCep(cep);
    if (!data) {
        return;
    }

    const logradouro = getEnderecoField(container, 'logradouro');
    const bairro = getEnderecoField(container, 'bairro');
    const cidade = getEnderecoField(container, 'cidade');
    const estado = getEnderecoField(container, 'estado');

    if (logradouro) logradouro.value = data.logradouro || '';
    if (bairro) bairro.value = data.bairro || '';
    if (estado) estado.value = data.uf || '';
    if (cidade) {
        cidade.dataset.selected = data.localidade || '';
        await popularSelectCidadeEmpresa(container, data.localidade || '');
    }

    const numero = getEnderecoField(container, 'numero');
    if (numero) {
        numero.focus();
    }
}

function bindCepLookup(container) {
    const cepInput = container.querySelector('[data-cep]');
    const estado = getEnderecoField(container, 'estado');
    if (!cepInput) return;

    if (estado) {
        estado.addEventListener('change', () => {
            const cidade = getEnderecoField(container, 'cidade');
            if (cidade) cidade.dataset.selected = '';
            popularSelectCidadeEmpresa(container, '');
        });
        popularSelectCidadeEmpresa(container, getEnderecoField(container, 'cidade')?.dataset?.selected || getEnderecoField(container, 'cidade')?.value || '');
    }

    cepInput.addEventListener('blur', () => {
        aplicarEnderecoPorCep(container, cepInput.value);
    });
}

function formatarCNPJInput(raw) {
    if (!raw) return '';

    const clean = raw.substring(0, 14);
    const p1 = clean.substring(0, 2);
    const p2 = clean.substring(2, 5);
    const p3 = clean.substring(5, 8);
    const p4 = clean.substring(8, 12);
    const p5 = clean.substring(12, 14);

    let value = p1;
    if (p2) value += `.${p2}`;
    if (p3) value += `.${p3}`;
    if (p4) value += `/${p4}`;
    if (p5) value += `-${p5}`;
    return value;
}

function validarCNPJAlfanumerico(clean) {
    if (!clean || clean.length !== 14) return false;
    if (/^([A-Z0-9])\1{13}$/.test(clean)) return false;

    const base = clean.substring(0, 12);
    const dvInformado = clean.substring(12, 14);
    if (!/^\d{2}$/.test(dvInformado)) return false;

    const dv1 = calcularDVCNPJ(base);
    const dv2 = calcularDVCNPJ(base + dv1);
    return dvInformado === `${dv1}${dv2}`;
}

function calcularDVCNPJ(base) {
    let weights = [5, 4, 3, 2, 9, 8, 7, 6, 5, 4, 3, 2];
    if (base.length === 13) {
        weights = [6, 5, 4, 3, 2, 9, 8, 7, 6, 5, 4, 3, 2];
    }

    let sum = 0;
    for (let i = 0; i < base.length; i++) {
        sum += mapCNPJChar(base[i]) * weights[i];
    }

    const resto = sum % 11;
    return resto < 2 ? 0 : 11 - resto;
}

function mapCNPJChar(char) {
    if (/[0-9]/.test(char)) return parseInt(char, 10);
    const code = char.charCodeAt(0);
    if (code >= 65 && code <= 90) return code - 48; // A=17 ... Z=42
    return 0;
}

async function buscarDadosCnpjPublico(cnpj) {
    const endpoints = [
        `https://publica.cnpj.ws/cnpj/${cnpj}`,
        `https://api.opencnpj.org/${cnpj}`,
    ];

    const responses = await Promise.all(
        endpoints.map(async (url) => {
            try {
                const response = await fetch(url, { headers: { 'Accept': 'application/json' } });
                if (!response.ok) return null;
                const data = await response.json();
                return data && Object.keys(data).length ? data : null;
            } catch (e) {
                return null;
            }
        })
    );

    const valid = responses.filter(Boolean);
    if (!valid.length) {
        return null;
    }

    const normalized = valid.map(normalizarCnpjResponse);
    return mesclarDadosCnpj(normalized);
}

function normalizarDataCnpj(value) {
    if (!value) return '';
    const raw = String(value).trim();
    if (!raw) return '';
    if (/^\d{4}-\d{2}-\d{2}$/.test(raw)) return raw;
    const match = raw.match(/^(\d{2})\/(\d{2})\/(\d{4})$/);
    if (match) {
        return `${match[3]}-${match[2]}-${match[1]}`;
    }
    return '';
}

function normalizarCnpjResponse(data) {
    const estabelecimento = data.estabelecimento || data.endereco || data.address || data;
    const cidade = estabelecimento?.cidade?.nome
        || estabelecimento?.municipio?.nome
        || estabelecimento?.cidade
        || estabelecimento?.municipio
        || data.municipio
        || estabelecimento?.localidade
        || '';
    const uf = estabelecimento?.estado?.sigla
        || estabelecimento?.estado?.uf
        || estabelecimento?.estado
        || estabelecimento?.uf
        || data.uf
        || '';

    return {
        razao_social: data.razao_social || data.razaoSocial || data.nome || data.company?.name || '',
        nome_fantasia: data.estabelecimento?.nome_fantasia
            || data.estabelecimento?.nomeFantasia
            || data.nome_fantasia
            || data.nomeFantasia
            || data.fantasia
            || data.nome_fantasia_empresarial
            || data.alias
            || '',
        data_inicio_atividade: normalizarDataCnpj(
            estabelecimento?.data_inicio_atividade
            || estabelecimento?.dataInicioAtividade
            || data.data_inicio_atividade
            || data.dataInicioAtividade
            || data.data_abertura
            || data.abertura
        ),
        cep: estabelecimento?.cep || data.cep || '',
        logradouro: estabelecimento?.logradouro || data.logradouro || estabelecimento?.endereco || estabelecimento?.street || '',
        numero: estabelecimento?.numero || data.numero || estabelecimento?.number || '',
        bairro: estabelecimento?.bairro || data.bairro || estabelecimento?.district || '',
        cidade: cidade,
        uf: uf,
        contatos: extrairContatosCnpj(data),
    };
}

function contarCamposCnpj(data) {
    const fields = ['razao_social', 'nome_fantasia', 'cep', 'logradouro', 'numero', 'bairro', 'cidade', 'uf'];
    let count = fields.reduce((sum, key) => sum + (data[key] ? 1 : 0), 0);
    if (Array.isArray(data.contatos) && data.contatos.length) count += 1;
    return count;
}

function isEmptyValue(value) {
    if (value === null || value === undefined) return true;
    if (typeof value === 'string') return value.trim() === '';
    if (Array.isArray(value)) return value.length === 0;
    return false;
}

function mesclarDadosCnpj(lista) {
    const sorted = [...lista].sort((a, b) => contarCamposCnpj(b) - contarCamposCnpj(a));
    const merged = { ...sorted[0] };

    for (const item of sorted.slice(1)) {
        for (const key of Object.keys(merged)) {
            if (isEmptyValue(merged[key]) && !isEmptyValue(item[key])) {
                merged[key] = item[key];
            }
        }
    }

    return merged;
}

function extrairContatosCnpj(data) {
    const contatos = [];
    const email = data.email || data.estabelecimento?.email || data.contact?.email || '';
    const telefones = [];

    if (Array.isArray(data.telefones)) {
        data.telefones.forEach((tel) => {
            const ddd = tel.ddd || '';
            const numero = tel.numero || '';
            if (ddd || numero) {
                telefones.push(`${ddd ? '(' + ddd + ') ' : ''}${numero}`);
            }
        });
    }

    const tel1 = data.estabelecimento?.telefone1 || data.telefone1 || data.telefone || '';
    const tel2 = data.estabelecimento?.telefone2 || data.telefone2 || '';
    const ddd1 = data.estabelecimento?.ddd1 || data.ddd1 || '';
    const ddd2 = data.estabelecimento?.ddd2 || data.ddd2 || '';

    if (tel1) telefones.push(`${ddd1 ? '(' + ddd1 + ') ' : ''}${tel1}`);
    if (tel2) telefones.push(`${ddd2 ? '(' + ddd2 + ') ' : ''}${tel2}`);

    if (email || telefones.length) {
        contatos.push({
            email: email || '',
            telefone: telefones[0] || '',
            telefone2: telefones[1] || '',
        });
    }

    return contatos;
}

function preencherDadosCnpj(data) {
    const nomeInput = document.querySelector('input[name="nome"]');
    const razaoInput = document.querySelector('input[name="razao_social"]');
    const emailInput = document.querySelector('input[name="email"]');
    const telefoneInput = document.querySelector('input[name="telefone"]');
    const celularInput = document.querySelector('input[name="celular"]');

    if (nomeInput && (data.nome_fantasia || data.razao_social)) {
        nomeInput.value = data.nome_fantasia || data.razao_social;
    }
    if (razaoInput && data.razao_social) razaoInput.value = data.razao_social;

    const contatos = data.contatos || [];
    if (emailInput && contatos[0]?.email) emailInput.value = contatos[0].email;
    if (telefoneInput && contatos[0]?.telefone) {
        telefoneInput.value = formatarTelefoneInput(contatos[0].telefone);
    }
    if (celularInput && contatos[0]?.telefone2) {
        celularInput.value = formatarTelefoneInput(contatos[0].telefone2);
    }

    const enderecoContainer = document.querySelector('[data-empresa-endereco]');
    if (enderecoContainer) {
        const cepInput = enderecoContainer.querySelector('[data-cep]');
        if (cepInput && data.cep) cepInput.value = data.cep;
        const logradouro = getEnderecoField(enderecoContainer, 'logradouro');
        const numero = getEnderecoField(enderecoContainer, 'numero');
        const bairro = getEnderecoField(enderecoContainer, 'bairro');
        const cidade = getEnderecoField(enderecoContainer, 'cidade');
        const estado = getEnderecoField(enderecoContainer, 'estado');
        if (logradouro && data.logradouro) logradouro.value = data.logradouro;
        if (numero && data.numero) numero.value = data.numero;
        if (bairro && data.bairro) bairro.value = data.bairro;
        if (estado && data.uf) estado.value = data.uf;
        if (cidade && data.cidade) {
            cidade.dataset.selected = data.cidade;
            popularSelectCidadeEmpresa(enderecoContainer, data.cidade);
        }
    }
}

document.addEventListener('DOMContentLoaded', () => {
    document.querySelectorAll('.js-phone-mask').forEach(bindPhoneMask);

    const enderecoContainer = document.querySelector('[data-empresa-endereco]');
    if (enderecoContainer) {
        bindCepLookup(enderecoContainer);
    }

    const cnpj = document.getElementById('cnpj');
    if (cnpj) {
        cnpj.addEventListener('input', function(e) {
            const raw = e.target.value.replace(/[^0-9A-Za-z]/g, '').toUpperCase();
            e.target.value = formatarCNPJInput(raw);
        });

        cnpj.addEventListener('blur', function(e) {
            const raw = e.target.value.replace(/[^0-9A-Za-z]/g, '').toUpperCase();
            if (!raw) {
                e.target.setCustomValidity('');
                return;
            }

            if (!validarCNPJAlfanumerico(raw)) {
                e.target.setCustomValidity('CNPJ inválido.');
            } else {
                e.target.setCustomValidity('');
            }
        });

        cnpj.addEventListener('invalid', function(e) {
            if (e.target.validity.customError) {
                return;
            }
            e.target.setCustomValidity('');
        });
    }

    const btnBuscarCnpj = document.getElementById('btnBuscarCnpj');
    if (btnBuscarCnpj) {
        btnBuscarCnpj.addEventListener('click', async function() {
            const cnpjInput = document.getElementById('cnpj');
            if (!cnpjInput) return;

            const raw = (cnpjInput.value || '').replace(/[^0-9A-Za-z]/g, '').toUpperCase();
            if (!validarCNPJAlfanumerico(raw)) {
                alert('Informe um CNPJ válido para buscar os dados.');
                return;
            }
            if (!/^\d{14}$/.test(raw)) {
                alert('A busca automática está disponível apenas para CNPJ numérico.');
                return;
            }

            btnBuscarCnpj.disabled = true;
            const originalText = btnBuscarCnpj.textContent;
            btnBuscarCnpj.textContent = 'Buscando...';

            try {
                const dados = await buscarDadosCnpjPublico(raw);
                if (!dados) {
                    alert('Não foi possível conseguir os dados.');
                    return;
                }

                preencherDadosCnpj(dados);
            } catch (error) {
                alert('Não foi possível conseguir os dados.');
            } finally {
                btnBuscarCnpj.disabled = false;
                btnBuscarCnpj.textContent = originalText;
            }
        });
    }
});
</script>
@endsection
