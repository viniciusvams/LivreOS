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

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class Configuracao extends Model
{
    protected $table = 'configuracoes';

    protected $fillable = ['chave', 'valor'];

    public $timestamps = true;

    /**
     * Retorna o valor de uma chave (com cache em memória por request).
     */
    public static function getValue(string $chave, $default = null)
    {
        $cacheKey = 'configuracao_'.$chave;
        $value = Cache::get($cacheKey);
        if ($value !== null) {
            return $value;
        }
        $row = static::where('chave', $chave)->first();
        $value = $row ? $row->valor : $default;
        Cache::put($cacheKey, $value, now()->addMinutes(5));

        return $value;
    }

    /**
     * Define o valor de uma chave.
     */
    public static function setValue(string $chave, $valor): void
    {
        static::updateOrCreate(
            ['chave' => $chave],
            ['valor' => $valor]
        );
        Cache::forget('configuracao_'.$chave);
    }

    /**
     * Retorna true se a opção "executar tarefas ao acessar" estiver habilitada.
     */
    public static function runTarefasAoAcessar(): bool
    {
        return (bool) (static::getValue('run_tarefas_ao_acessar', '0') === '1' || static::getValue('run_tarefas_ao_acessar', '0') === true);
    }

    /**
     * Retorna o ID do plano de conta configurado para lançamentos financeiros do encerramento de OS.
     * Retorna null se não houver configuração ou se a conta não existir/estiver inativa.
     */
    public static function getPlanoContaOsEncerramento(): ?int
    {
        $id = static::getValue('plano_conta_os_encerramento', null);
        if ($id !== null && $id !== '' && is_numeric($id)) {
            $existe = PlanoConta::where('id', (int) $id)->where('ativo', true)->exists();

            return $existe ? (int) $id : null;
        }

        return null;
    }

    /**
     * Retorna o ID do centro de custo configurado para lançamentos financeiros do encerramento de OS.
     * Retorna null se não houver configuração ou se o centro não existir/estiver inativo.
     */
    public static function getCentroCustoOsEncerramento(): ?int
    {
        $id = static::getValue('centro_custo_os_encerramento', null);
        if ($id !== null && $id !== '' && is_numeric($id)) {
            $existe = CentroCusto::where('id', (int) $id)->where('ativo', true)->exists();

            return $existe ? (int) $id : null;
        }

        return null;
    }

    /**
     * Retorna true se a opção "bloquear venda com estoque zero" estiver ativa.
     * Quando ativo, não é permitido incluir produtos com estoque zero (ou insuficiente) na OS,
     * exceto para usuários com permissão produtos.vender_estoque_zero ou administrador.
     */
    public static function estoqueBloquearVendaZero(): bool
    {
        return (bool) (static::getValue('estoque_bloquear_venda_zero', '0') === '1');
    }

    /** Texto padrão do comprovante (OS encerrada) quando o campo configurado está vazio e o uso está habilitado. */
    public static function textoComprovanteOsEncerradaPadrao(): string
    {
        return '<p><strong>Declaro estar recebendo materiais constantes nesta Ordem de Serviço devidamente reparados.</strong></p>';
    }

    public static function osImpressaoObservacaoAntesAssinaturaAtivo(): bool
    {
        $v = static::getValue('os_impressao_observacao_antes_assinatura_ativo', '1');

        return $v === '1' || $v === 1 || $v === true;
    }

    public static function osComprovanteObservacaoAntesAssinaturaAtivo(): bool
    {
        $v = static::getValue('os_comprovante_observacao_antes_assinatura_ativo', '1');

        return $v === '1' || $v === 1 || $v === true;
    }
}
