<?php

/**
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
 */

namespace App\Http\Controllers;

use App\Http\Requests\EmpresaRequest;
use App\Models\CentroCusto;
use App\Models\Configuracao;
use App\Models\Empresa;
use App\Models\OrdemServico;
use App\Models\PlanoConta;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ConfiguracoesSistemaController extends Controller
{
    public function index()
    {
        return erp_view('configuracoes_sistema.index', [
            'title' => 'Configurações do Sistema',
        ]);
    }

    public function empresa()
    {
        return erp_view('configuracoes_sistema.empresa', [
            'title' => 'Cadastro da Empresa',
            'empresa' => Empresa::first(),
        ]);
    }

    public function atualizarEmpresa(EmpresaRequest $request)
    {
        $empresa = Empresa::first() ?? new Empresa;
        $data = $request->validated();
        $data['celular_whatsapp'] = ! empty($data['celular_whatsapp']);

        if ($request->filled('remover_logo') || $request->hasFile('logo')) {
            if ($empresa->logo_path && Storage::disk('public')->exists($empresa->logo_path)) {
                Storage::disk('public')->delete($empresa->logo_path);
            }
            $data['logo_path'] = null;
        }

        if ($request->hasFile('logo')) {
            $arquivo = $request->file('logo');
            $nomeArquivo = 'empresa_'.now()->format('Ymd_His').'_'.Str::random(6).'.'
                .$arquivo->getClientOriginalExtension();
            $data['logo_path'] = $arquivo->storeAs('empresa/logos', $nomeArquivo, 'public');
        }

        $empresa->fill($data);
        $empresa->save();

        return redirect()->route('configuracoes-sistema.empresa')
            ->with('success', 'Dados da empresa atualizados com sucesso!');
    }

    /**
     * Tarefas agendadas: opção para rodar via primeiro acesso do dia (sem cron).
     */
    public function tarefasAgendadas()
    {
        $enabled = false;
        try {
            $enabled = Configuracao::runTarefasAoAcessar();
        } catch (\Throwable) {
            $enabled = false;
        }

        return erp_view('configuracoes_sistema.tarefas_agendadas', [
            'title' => 'Tarefas Agendadas',
            'run_tarefas_ao_acessar' => $enabled,
        ]);
    }

    public function atualizarTarefasAgendadas(Request $request)
    {
        $request->validate(['run_tarefas_ao_acessar' => 'boolean']);
        try {
            Configuracao::setValue('run_tarefas_ao_acessar', $request->boolean('run_tarefas_ao_acessar') ? '1' : '0');
        } catch (\Throwable $e) {
            return redirect()->route('configuracoes-sistema.tarefas-agendadas')
                ->with('error', 'Não foi possível salvar. Verifique se as migrations foram executadas.');
        }

        return redirect()->route('configuracoes-sistema.tarefas-agendadas')
            ->with('success', 'Configuração salva com sucesso!');
    }

    /**
     * Força a execução das tarefas agendadas agora.
     * Chama os comandos diretamente (em vez de schedule:run) para não depender do horário agendado
     * — schedule:run só executa eventos "due" no minuto atual, então daily() à 00:00 não roda às 15h.
     */
    public function executarTarefasAgora()
    {
        $hoje = now()->format('Y-m-d');
        $cacheKey = 'run_tarefas_interno_'.$hoje;
        Cache::forget($cacheKey);
        try {
            Artisan::call('financeiro:gerar-recorrentes');
            Artisan::call('financeiro:gerar-contas-pagar-recorrentes');
            Artisan::call('financeiro:aplicar-reajuste-recorrentes');
        } catch (\Throwable $e) {
            Log::error('ExecutarTarefasAgora: '.$e->getMessage());

            return redirect()->route('configuracoes-sistema.tarefas-agendadas')
                ->with('error', 'Erro ao executar: '.$e->getMessage());
        }

        return redirect()->route('configuracoes-sistema.tarefas-agendadas')
            ->with('success', 'Tarefas executadas com sucesso. Contas a receber e a pagar recorrentes pendentes foram geradas (quando havia atraso, todas as parcelas em atraso são geradas de uma vez).');
    }

    /**
     * Configuração do plano de conta usado nos lançamentos financeiros do encerramento de OS.
     */
    public function planoContaOsEncerramento()
    {
        $planoContaId = Configuracao::getPlanoContaOsEncerramento();
        $centroCustoId = Configuracao::getCentroCustoOsEncerramento();
        $planoContasReceita = PlanoConta::where('tipo', 'receita')
            ->where('ativo', true)
            ->orderByRaw('COALESCE(pai_id, 0), ordem, codigo')
            ->get();
        $centrosCusto = CentroCusto::where('ativo', true)->orderBy('ordem')->orderBy('nome')->get();

        return erp_view('configuracoes_sistema.plano_conta_os', [
            'title' => 'Plano de Contas - Encerramento de OS',
            'plano_conta_id' => $planoContaId,
            'centro_custo_id' => $centroCustoId,
            'planoContasReceita' => $planoContasReceita,
            'centrosCusto' => $centrosCusto,
        ]);
    }

    public function atualizarPlanoContaOsEncerramento(Request $request)
    {
        $request->validate([
            'plano_conta_id' => 'nullable|exists:plano_contas,id',
            'centro_custo_id' => 'nullable|exists:centros_custos,id',
        ]);

        Configuracao::setValue('plano_conta_os_encerramento', $request->plano_conta_id ? (string) $request->plano_conta_id : '');
        Configuracao::setValue('centro_custo_os_encerramento', $request->centro_custo_id ? (string) $request->centro_custo_id : '');

        return redirect()->route('configuracoes-sistema.plano-conta-os')
            ->with('success', 'Configuração salva com sucesso!');
    }

    /**
     * Configuração da numeração de OS: permite definir qual será o próximo número.
     */
    public function numeracaoOs()
    {
        $ultimoNumero = (int) OrdemServico::max('numero_sequencial');
        $proximoNatural = $ultimoNumero > 0 ? ($ultimoNumero + 1) : 1;
        $configurado = (int) Configuracao::getValue('ordem_servico_proximo_numero', (string) $proximoNatural);
        if ($configurado < 1) {
            $configurado = $proximoNatural;
        }
        $proximoEfetivo = max($proximoNatural, $configurado);

        return erp_view('configuracoes_sistema.numeracao_os', [
            'title' => 'Numeração das Ordens de Serviço',
            'ultimo_numero' => $ultimoNumero,
            'proximo_natural' => $proximoNatural,
            'proximo_configurado' => $configurado,
            'proximo_efetivo' => $proximoEfetivo,
        ]);
    }

    public function atualizarNumeracaoOs(Request $request)
    {
        $validated = $request->validate([
            'proximo_numero' => 'required|integer|min:1|max:99999999',
        ], [
            'proximo_numero.required' => 'Informe o próximo número da OS.',
            'proximo_numero.integer' => 'O próximo número deve ser um número inteiro.',
            'proximo_numero.min' => 'O próximo número deve ser maior que zero.',
        ]);

        $ultimoNumero = (int) OrdemServico::max('numero_sequencial');
        $proximoNumero = (int) $validated['proximo_numero'];
        if ($ultimoNumero > 0 && $proximoNumero <= $ultimoNumero) {
            return redirect()->route('configuracoes-sistema.numeracao-os')
                ->with('error', 'Para não quebrar a sequência, o próximo número deve ser maior que a última OS ('.$ultimoNumero.').')
                ->withInput();
        }

        Configuracao::setValue('ordem_servico_proximo_numero', (string) $proximoNumero);

        return redirect()->route('configuracoes-sistema.numeracao-os')
            ->with('success', 'Próxima numeração de OS atualizada com sucesso!');
    }

    /**
     * Textos opcionais na impressão da OS e no comprovante (OS encerrada), antes das assinaturas.
     */
    public function impressaoOrdemServico()
    {
        return erp_view('configuracoes_sistema.impressao_os', [
            'title' => 'Impressão de Ordens de Serviço',
            'os_impressao_observacao_antes_assinatura' => (string) (Configuracao::getValue('os_impressao_observacao_antes_assinatura', '') ?? ''),
            'os_impressao_observacao_antes_assinatura_ativo' => Configuracao::osImpressaoObservacaoAntesAssinaturaAtivo(),
            'os_comprovante_observacao_antes_assinatura' => (string) (Configuracao::getValue('os_comprovante_observacao_antes_assinatura', '') ?? ''),
            'os_comprovante_observacao_antes_assinatura_ativo' => Configuracao::osComprovanteObservacaoAntesAssinaturaAtivo(),
        ]);
    }

    public function atualizarImpressaoOrdemServico(Request $request)
    {
        $validated = $request->validate([
            'os_impressao_observacao_antes_assinatura' => 'nullable|string|max:62000',
            'os_impressao_observacao_antes_assinatura_ativo' => 'required|in:0,1',
            'os_comprovante_observacao_antes_assinatura' => 'nullable|string|max:62000',
            'os_comprovante_observacao_antes_assinatura_ativo' => 'required|in:0,1',
        ]);

        Configuracao::setValue(
            'os_impressao_observacao_antes_assinatura',
            $validated['os_impressao_observacao_antes_assinatura'] ?? ''
        );
        Configuracao::setValue(
            'os_impressao_observacao_antes_assinatura_ativo',
            (string) $validated['os_impressao_observacao_antes_assinatura_ativo']
        );
        Configuracao::setValue(
            'os_comprovante_observacao_antes_assinatura',
            $validated['os_comprovante_observacao_antes_assinatura'] ?? ''
        );
        Configuracao::setValue(
            'os_comprovante_observacao_antes_assinatura_ativo',
            (string) $validated['os_comprovante_observacao_antes_assinatura_ativo']
        );

        return redirect()->route('configuracoes-sistema.impressao-os')
            ->with('success', 'Configurações de impressão da OS salvas com sucesso!');
    }
}
