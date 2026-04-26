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

namespace App\Services;

use App\Models\LimiteDesconto;
use App\Models\Produto;
use App\Models\Servico;
use App\Models\User;
use Illuminate\Support\Facades\Cache;

class LimiteDescontoService
{
    /** Tolerância em reais para comparação de preço (evitar erro de float). */
    private const TOLERANCIA_PRECO = 0.009;

    /**
     * Verifica se o usuário está sujeito aos limites de desconto.
     * Administrador (is_admin) NUNCA está sujeito.
     * Quando há limites configurados mas nenhum perfil selecionado, todos os não-admin estão sujeitos.
     */
    public function usuarioSujeitoALimite(?User $user = null): bool
    {
        $user = $user ?? auth()->user();
        if (!$user) {
            return false;
        }
        if ($user->is_admin) {
            return false;
        }
        $config = $this->getConfig();
        $temLimite = $config->limite_valor !== null && (float) $config->limite_valor > 0
            || $config->limite_percentual !== null && (float) $config->limite_percentual > 0;
        if (!$temLimite) {
            return false;
        }
        $roleIds = $config->roles()->get()->pluck('id')->toArray();
        if (empty($roleIds)) {
            return true;
        }
        $userRoleIds = $user->roles()->get()->pluck('id')->toArray();
        return !empty(array_intersect($roleIds, $userRoleIds));
    }

    /**
     * Retorna a configuração de limites (com cache curto).
     */
    public function getConfig(): LimiteDesconto
    {
        return Cache::remember('limite_desconto_config', 60, function () {
            return LimiteDesconto::config()->load('roles');
        });
    }

    /**
     * Limpa o cache da configuração (chamar após atualizar).
     */
    public function limparCache(): void
    {
        Cache::forget('limite_desconto_config');
    }

    /**
     * Valida um desconto em valor para um item (subtotal do item).
     * Retorna array ['valido' => bool, 'mensagem' => string].
     */
    public function validarDescontoValor(float $descontoValor, float $subtotalItem): array
    {
        if (!$this->usuarioSujeitoALimite()) {
            return ['valido' => true, 'mensagem' => ''];
        }
        $config = $this->getConfig();
        if ($config->limite_valor !== null && $descontoValor > (float) $config->limite_valor) {
            return [
                'valido' => false,
                'mensagem' => 'O desconto em valor (R$ ' . number_format($descontoValor, 2, ',', '.') . ') excede o limite permitido de R$ ' . number_format((float) $config->limite_valor, 2, ',', '.') . '.',
            ];
        }
        if ($config->limite_percentual !== null && $subtotalItem > 0) {
            $percentualAplicado = ($descontoValor / $subtotalItem) * 100;
            if ($percentualAplicado > (float) $config->limite_percentual) {
                return [
                    'valido' => false,
                    'mensagem' => 'O desconto em percentual (' . number_format($percentualAplicado, 1, ',', '.') . '%) excede o limite permitido de ' . number_format((float) $config->limite_percentual, 1, ',', '.') . '%.',
                ];
            }
        }
        return ['valido' => true, 'mensagem' => ''];
    }

    /**
     * Valida um desconto em percentual para um item.
     */
    public function validarDescontoPercentual(float $descontoPercentual, float $subtotalItem): array
    {
        if (!$this->usuarioSujeitoALimite()) {
            return ['valido' => true, 'mensagem' => ''];
        }
        $config = $this->getConfig();
        if ($config->limite_percentual !== null && $descontoPercentual > (float) $config->limite_percentual) {
            return [
                'valido' => false,
                'mensagem' => 'O desconto em percentual (' . number_format($descontoPercentual, 1, ',', '.') . '%) excede o limite permitido de ' . number_format((float) $config->limite_percentual, 1, ',', '.') . '%.',
            ];
        }
        $valorDesconto = $subtotalItem * ($descontoPercentual / 100);
        if ($config->limite_valor !== null && $valorDesconto > (float) $config->limite_valor) {
            return [
                'valido' => false,
                'mensagem' => 'O desconto resultante em valor (R$ ' . number_format($valorDesconto, 2, ',', '.') . ') excede o limite permitido de R$ ' . number_format((float) $config->limite_valor, 2, ',', '.') . '.',
            ];
        }
        return ['valido' => true, 'mensagem' => ''];
    }

    /**
     * Valida desconto de um item (pode ser valor ou percentual).
     */
    public function validarDescontoItem(float $subtotal, float $descontoValor, float $descontoPercentual): array
    {
        if ($descontoPercentual > 0) {
            return $this->validarDescontoPercentual($descontoPercentual, $subtotal);
        }
        return $this->validarDescontoValor($descontoValor, $subtotal);
    }

    /**
     * Valida desconto global ou do encerramento (valor total da OS e desconto aplicado).
     */
    public function validarDescontoGlobal(float $valorTotal, string $tipoDesconto, float $descontoInformado): array
    {
        if (!$this->usuarioSujeitoALimite()) {
            return ['valido' => true, 'mensagem' => ''];
        }
        $config = $this->getConfig();
        $valorDesconto = $tipoDesconto === 'percentual'
            ? ($valorTotal * ($descontoInformado / 100))
            : $descontoInformado;
        $percentualDesconto = $valorTotal > 0 ? ($valorDesconto / $valorTotal) * 100 : 0;

        if ($config->limite_valor !== null && $valorDesconto > (float) $config->limite_valor) {
            return [
                'valido' => false,
                'mensagem' => 'O desconto em valor (R$ ' . number_format($valorDesconto, 2, ',', '.') . ') excede o limite permitido de R$ ' . number_format((float) $config->limite_valor, 2, ',', '.') . '.',
            ];
        }
        if ($config->limite_percentual !== null && $percentualDesconto > (float) $config->limite_percentual) {
            return [
                'valido' => false,
                'mensagem' => 'O desconto em percentual (' . number_format($percentualDesconto, 1, ',', '.') . '%) excede o limite permitido de ' . number_format((float) $config->limite_percentual, 1, ',', '.') . '%.',
            ];
        }
        return ['valido' => true, 'mensagem' => ''];
    }

    /**
     * Verifica se está ativa a regra de bloquear alteração de preço para perfis sujeitos.
     */
    public function deveBloquearAlteracaoPreco(): bool
    {
        if (!$this->usuarioSujeitoALimite()) {
            return false;
        }
        $config = $this->getConfig();

        return !empty($config->bloquear_alteracao_preco);
    }

    /**
     * Valida se o preço informado para um produto (cadastrado) pode ser usado.
     * Retorna ['valido' => bool, 'mensagem' => string]. Aplica-se apenas quando bloquear_alteracao_preco está ativo.
     */
    public function validarPrecoProduto(?int $produtoId, float $valorUnitarioInformado): array
    {
        if (!$this->deveBloquearAlteracaoPreco() || !$produtoId) {
            return ['valido' => true, 'mensagem' => ''];
        }
        $produto = Produto::find($produtoId);
        if (!$produto) {
            return ['valido' => true, 'mensagem' => ''];
        }
        $precoCadastro = (float) $produto->preco_venda;
        if (abs($valorUnitarioInformado - $precoCadastro) > self::TOLERANCIA_PRECO) {
            return [
                'valido' => false,
                'mensagem' => 'Não é permitido alterar o preço do produto "' . $produto->nome . '". Use o preço de cadastro: R$ ' . number_format($precoCadastro, 2, ',', '.') . '.',
            ];
        }

        return ['valido' => true, 'mensagem' => ''];
    }

    /**
     * Valida se o preço informado para um serviço (cadastrado) pode ser usado.
     */
    public function validarPrecoServico(?int $servicoId, float $valorUnitarioInformado): array
    {
        if (!$this->deveBloquearAlteracaoPreco() || !$servicoId) {
            return ['valido' => true, 'mensagem' => ''];
        }
        $servico = Servico::find($servicoId);
        if (!$servico) {
            return ['valido' => true, 'mensagem' => ''];
        }
        $precoCadastro = (float) $servico->preco;
        if (abs($valorUnitarioInformado - $precoCadastro) > self::TOLERANCIA_PRECO) {
            return [
                'valido' => false,
                'mensagem' => 'Não é permitido alterar o preço do serviço "' . $servico->nome . '". Use o preço de cadastro: R$ ' . number_format($precoCadastro, 2, ',', '.') . '.',
            ];
        }

        return ['valido' => true, 'mensagem' => ''];
    }
}
