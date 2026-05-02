@extends('layouts.app')

@section('content')
<div class="mx-auto max-w-screen-2xl p-4 md:p-6 2xl:p-10">
    <div class="mb-6 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <h2 class="text-title-md2 font-bold text-black dark:text-white">
            {{ $title }}
        </h2>
    </div>

    <div class="rounded-sm border border-stroke bg-white shadow-default dark:border-strokedark dark:bg-boxdark">
        <div class="border-b border-stroke py-4 px-6.5 dark:border-strokedark">
            <h3 class="font-medium text-black dark:text-white">
                Opções de Impressão
            </h3>
        </div>
        <form action="{{ route('plugin.os_termica.settings.store') }}" method="POST">
            @csrf
            <div class="p-6.5">
                <div class="mb-4.5">
                    <label class="mb-2.5 block text-black dark:text-white">
                        Formato de Impressão Térmica Padrão
                    </label>
                    <select name="os_termica_formato_impressao" class="w-full rounded border-[1.5px] border-stroke bg-transparent py-3 px-5 font-medium outline-none transition focus:border-primary active:border-primary disabled:cursor-default disabled:bg-whiter dark:border-form-strokedark dark:bg-form-input dark:focus:border-primary">
                        <option value="80mm" {{ $formatoImpressao === '80mm' ? 'selected' : '' }}>Bobina 80mm</option>
                        <option value="58mm" {{ $formatoImpressao === '58mm' ? 'selected' : '' }}>Bobina 58mm</option>
                    </select>
                    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Ao clicar em "Imprimir OS Térmica", o PDF será gerado neste formato de bobina.</p>
                </div>

                <button class="flex w-full justify-center rounded bg-primary p-3 font-medium text-gray hover:bg-opacity-90">
                    Salvar Configurações
                </button>
            </div>
        </form>
    </div>
</div>
@endsection
