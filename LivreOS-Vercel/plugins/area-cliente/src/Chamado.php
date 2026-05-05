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

namespace AreaCliente;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Chamado (ticket) aberto pelo cliente no portal.
 * Tabela: plugin_area_cliente_chamados
 */
class Chamado extends Model
{
    protected $table = 'plugin_area_cliente_chamados';

    protected $fillable = [
        'cliente_id',
        'user_id',
        'ordem_servico_id',
        'titulo',
        'descricao',
        'prioridade',
        'status',
    ];

    protected $casts = [
        'cliente_id' => 'integer',
        'user_id' => 'integer',
        'ordem_servico_id' => 'integer',
    ];

    public const PRIORIDADES = [
        'baixa' => 'Baixa',
        'media' => 'Média',
        'alta' => 'Alta',
        'urgente' => 'Urgente',
    ];

    public const STATUS = [
        'aberto' => 'Aberto',
        'em_atendimento' => 'Em atendimento',
        'aguardando_cliente' => 'Aguardando cliente',
        'aguardando_confirmacao_cliente' => 'Resolvido (aguardando confirmação)',
        'resolvido_confirmado' => 'Resolvido (confirmado pelo cliente)',
        'encerrado_orcamento' => 'Encerrado com Orçamento',
        'cancelado_duplicado' => 'Cancelado / Duplicado',
        'sem_sucesso' => 'Sem Sucesso (Irresolvível)',
        'encerrado_inatividade' => 'Encerrado por Inatividade',
        'desistencia_cliente' => 'Desistência do cliente',
        // Legado (mantidos para registros antigos)
        'resolvido' => 'Resolvido',
        'cancelado' => 'Cancelado',
        'fechado' => 'Fechado',
    ];

    /** Status que o operador pode definir (admin). */
    public const STATUS_OPERADOR = [
        'aberto', 'em_atendimento', 'aguardando_cliente', 'aguardando_confirmacao_cliente',
        'resolvido_confirmado', 'encerrado_orcamento', 'cancelado_duplicado', 'sem_sucesso', 'encerrado_inatividade', 'desistencia_cliente',
        'resolvido', 'cancelado', 'fechado',
    ];

    /** Status considerados encerrados (cliente não pode mais responder/reabrir). */
    public const STATUS_ENCERRADOS = [
        'resolvido_confirmado', 'encerrado_orcamento', 'cancelado_duplicado', 'sem_sucesso', 'encerrado_inatividade', 'desistencia_cliente',
        'resolvido', 'cancelado', 'fechado',
    ];

    /** Status em que o cliente pode confirmar solução ou reabrir. */
    public const STATUS_AGUARDANDO_CONFIRMACAO = ['aguardando_confirmacao_cliente'];

    /** Status em que o cliente pode desistir. */
    public const STATUS_PODE_DESISTIR = ['aberto', 'em_atendimento', 'aguardando_cliente'];

    public function cliente(): BelongsTo
    {
        return $this->belongsTo(\App\Models\Cliente::class, 'cliente_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class, 'user_id');
    }

    public function anexos(): HasMany
    {
        return $this->hasMany(ChamadoAnexo::class, 'chamado_id');
    }

    public function ordemServico(): BelongsTo
    {
        return $this->belongsTo(\App\Models\OrdemServico::class, 'ordem_servico_id');
    }

    public function respostas(): HasMany
    {
        return $this->hasMany(ChamadoResposta::class, 'chamado_id')->orderBy('created_at');
    }

    public static function prioridadeLabel(string $prioridade): string
    {
        return self::PRIORIDADES[$prioridade] ?? $prioridade;
    }

    public static function statusLabel(string $status): string
    {
        return self::STATUS[$status] ?? $status;
    }
}
