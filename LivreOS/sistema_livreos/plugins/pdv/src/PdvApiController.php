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

namespace Pdv;

use App\Models\Cliente;
use App\Models\FormaPagamento;
use App\Models\PlanoConta;
use App\Services\FinanceiroVendaPagamentosService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Pdv\Models\Caixa;
use Pdv\Models\CaixaMovimentacao;
use Pdv\Models\CaixaFechamentoQuebraSobra;
use Pdv\Models\Venda;
use Pdv\Models\VendaItem;
use Pdv\Models\VendaPagamento;
use Pdv\Models\Orcamento;
use Pdv\Models\OrcamentoItem;
use Pdv\EstoqueHelper;

class PdvApiController
{
    protected function numeroCaixa(Request $request): string
    {
        return (string) ($request->input('numero_caixa') ?: $request->header('X-PDV-Caixa') ?: '1');
    }

    public function caixaStatus(Request $request)
    {
        $pedirMeuCaixa = $request->boolean('meu') || ($request->input('numero_caixa') === null && !$request->header('X-PDV-Caixa'));
        $caixa = $pedirMeuCaixa
            ? $this->getCaixaAberto(null)
            : $this->getCaixaAberto($this->numeroCaixa($request));

        $planoContaAporteId = get_option('plano_conta_aporte_id', null, 'pdv');
        $terminais = get_option('pdv_terminais', ['Caixa 1', 'Caixa 2', 'Caixa 3'], 'pdv');
        if (!is_array($terminais)) {
            $terminais = ['Caixa 1'];
        }
        $ultimaVendaId = $caixa
            ? Venda::where('caixa_id', $caixa->id)->where('status', Venda::STATUS_FINALIZADA)->max('id')
            : null;
        $numeroVendaInicial = (int) get_option('pdv_numero_venda_inicial', 1, 'pdv');
        if ($numeroVendaInicial < 1) $numeroVendaInicial = 1;
        $podeVerSaldoFechamento = PdvPermissoes::podeVerSaldoNoFechamento(auth()->id());
        return response()->json([
            'caixa' => $caixa ? [
                'id' => $caixa->id,
                'numero_caixa' => (string) ($caixa->user_id ?? $caixa->numero_caixa ?? '1'),
                'valor_abertura' => (float) $caixa->valor_abertura,
                'saldo_atual' => $caixa->saldo_atual,
                'detalhe_saldo' => $caixa->getDetalheSaldo(),
                'opened_at' => $caixa->opened_at?->toIso8601String(),
                'ultima_venda_id' => $ultimaVendaId,
            ] : null,
            'pode_ver_saldo_fechamento' => $podeVerSaldoFechamento,
            'pdv_numero_venda_inicial' => $numeroVendaInicial,
            'pode_abrir_caixa' => $this->usuarioPodeAbrirCaixa(),
            'plano_conta_aporte_id' => $planoContaAporteId,
            'terminais' => $terminais,
            'desconto_maximo_percentual' => PdvPermissoes::getDescontoMaximoPercentual(),
            'desconto_autorizador_nome' => PdvPermissoes::getAutorizadorNome(),
            'controle_estoque' => PdvPermissoes::getControleEstoque(),
            'estoque_zerado_autorizador_nome' => PdvPermissoes::getEstoqueZeroAutorizadorNome(),
            'pode_vender_estoque_zerado' => (PdvPermissoes::getPermissoesUsuario(auth()->id())['pode_vender_estoque_zerado'] ?? false),
            'pode_cancelar_venda_total' => PdvPermissoes::podeCancelarVendaTotal(auth()->id()),
            'pode_cancelar_venda_parcial' => PdvPermissoes::podeCancelarVendaParcial(auth()->id()),
        ]);
    }

    /**
     * Valida senha do autorizador para aplicar desconto acima do máximo permitido.
     * Body: senha_autorizacao, desconto_valor (número), subtotal (número).
     * Retorna 200 { ok: true } ou 403 { ok: false, message, autorizador_nome }.
     */
    public function validarSenhaDesconto(Request $request)
    {
        $request->validate([
            'senha_autorizacao' => 'required|string',
            'desconto_valor' => 'required|numeric|min:0',
            'subtotal' => 'required|numeric|min:0',
        ]);
        $descontoValor = (float) $request->desconto_valor;
        $subtotal = (float) $request->subtotal;
        $maxPercentual = PdvPermissoes::getDescontoMaximoPercentual();

        if ($maxPercentual === null || $subtotal <= 0) {
            return response()->json(['ok' => true]);
        }
        $percentualAplicado = $subtotal > 0 ? (($descontoValor / $subtotal) * 100) : 0;
        if ($percentualAplicado <= $maxPercentual) {
            return response()->json(['ok' => true]);
        }

        $senha = $request->input('senha_autorizacao');
        if (is_string($senha) && trim($senha) !== '' && PdvPermissoes::validarSenhaAutorizador(trim($senha))) {
            $autorizadorUserId = PdvPermissoes::validarSenhaAutorizadorRetornaUserId(trim($senha));
            return response()->json(['ok' => true, 'autorizador_user_id' => $autorizadorUserId]);
        }

        $autorizadorNome = PdvPermissoes::getAutorizadorNome();
        return response()->json([
            'ok' => false,
            'message' => 'Senha incorreta. Desconto de ' . number_format($percentualAplicado, 1, ',', '') . '% está acima do permitido (' . number_format($maxPercentual, 1, ',', '') . '%). Digite a senha do autorizador.',
            'autorizador_nome' => $autorizadorNome,
        ], 403);
    }

    /**
     * Valida senha do autorizador (genérico). Usado para cliente bloqueado e limite de crédito.
     * Body: senha_autorizacao. Retorna 200 { ok: true, autorizador_user_id, autorizador_nome } ou 403.
     */
    public function validarSenhaAutorizador(Request $request)
    {
        $request->validate(['senha_autorizacao' => 'required|string']);
        $senha = trim((string) $request->input('senha_autorizacao'));
        if ($senha !== '' && PdvPermissoes::validarSenhaAutorizador($senha)) {
            $userId = PdvPermissoes::validarSenhaAutorizadorRetornaUserId($senha);
            return response()->json([
                'ok' => true,
                'autorizador_user_id' => $userId,
                'autorizador_nome' => PdvPermissoes::getAutorizadorNome(),
            ]);
        }
        return response()->json([
            'ok' => false,
            'message' => 'Senha incorreta. Digite a senha do administrador ou de um usuário autorizador.',
            'autorizador_nome' => PdvPermissoes::getAutorizadorNome(),
        ], 403);
    }

    /**
     * Valida senha para autorizar venda com estoque zerado/insuficiente.
     * Body: senha_autorizacao. Retorna 200 { ok: true, autorizador_user_id, autorizador_nome } ou 403.
     */
    public function validarSenhaEstoqueZero(Request $request)
    {
        $request->validate(['senha_autorizacao' => 'required|string']);
        $senha = trim((string) $request->input('senha_autorizacao'));
        $userId = PdvPermissoes::validarSenhaEstoqueZeroRetornaUserId($senha);
        if ($userId !== null) {
            return response()->json([
                'ok' => true,
                'autorizador_user_id' => $userId,
                'autorizador_nome' => PdvPermissoes::getEstoqueZeroAutorizadorNome(),
            ]);
        }
        return response()->json([
            'ok' => false,
            'message' => 'Senha incorreta. Digite a senha do administrador ou de um usuário com permissão para vender com estoque zerado.',
            'autorizador_nome' => PdvPermissoes::getEstoqueZeroAutorizadorNome(),
        ], 403);
    }

    /**
     * Verifica se o carrinho (itens) respeita o estoque. Usado ao adicionar produto no PDV.
     * Body: itens = [{ tipo, produto_id?, servico_id?, quantidade }]. Retorna { allowed: true } ou { allowed: false, code, message, produtos, estoque_zerado_autorizador_nome }.
     */
    public function checkEstoqueCarrinho(Request $request)
    {
        if (!PdvPermissoes::getControleEstoque()) {
            return response()->json(['allowed' => true]);
        }
        $request->validate([
            'itens' => 'required|array|min:1',
            'itens.*.tipo' => 'required|in:produto,servico',
            'itens.*.produto_id' => 'nullable',
            'itens.*.quantidade' => 'required|numeric|min:0',
        ]);
        $itens = array_map(fn ($i) => [
            'tipo' => $i['tipo'] ?? 'produto',
            'produto_id' => ($i['tipo'] ?? '') === 'produto' ? ($i['produto_id'] ?? null) : null,
            'quantidade' => (float) ($i['quantidade'] ?? 0),
        ], $request->itens);
        $insuficientes = EstoqueHelper::verificarEstoqueVenda($itens);
        if ($insuficientes === null) {
            return response()->json(['allowed' => true]);
        }
        $nomes = array_column($insuficientes, 'nome');
        return response()->json([
            'allowed' => false,
            'code' => 'estoque_insuficiente',
            'message' => 'Estoque insuficiente ou zerado para: ' . implode(', ', array_slice($nomes, 0, 3)) . (count($nomes) > 3 ? ' e outros.' : '') . ' Digite a senha do administrador ou de um usuário com permissão para autorizar a inclusão.',
            'produtos' => $insuficientes,
            'estoque_zerado_autorizador_nome' => PdvPermissoes::getEstoqueZeroAutorizadorNome(),
        ]);
    }

    /**
     * Verifica se o usuário atual pode abrir caixa (lista habilitada ou admin).
     */
    protected function usuarioPodeAbrirCaixa(): bool
    {
        $user = auth()->user();
        if ($user->is_admin ?? false) {
            return true;
        }
        $habilitados = get_option('pdv_usuarios_podem_abrir_caixa', null, 'pdv');
        if ($habilitados === null) {
            return true; // Nunca configurado: todos os operacionais podem (retrocompat).
        }
        $habilitados = is_array($habilitados) ? $habilitados : (json_decode($habilitados, true) ?? []);
        return in_array((int) $user->id, array_map('intval', $habilitados), true);
    }

    /**
     * Número do caixa é sempre o ID do usuário: cada usuário só abre o caixa dele.
     */
    protected function numeroCaixaDoUsuario(): string
    {
        return (string) auth()->id();
    }

    public function caixaAbrir(Request $request)
    {
        if (!$this->usuarioPodeAbrirCaixa()) {
            return response()->json(['message' => 'Você não está habilitado a abrir caixa. Peça a um administrador para liberar em PDV > Habilitar caixa por usuário.'], 403);
        }
        $request->validate([
            'valor_fundo' => 'required|numeric|min:0',
        ], [
            'valor_fundo.required' => 'Informe o valor de fundo de caixa (pode ser 0).',
        ]);
        $meuCaixaAberto = $this->getCaixaAberto(null);
        if ($meuCaixaAberto) {
            return response()->json(['message' => 'Você já possui um caixa aberto. Retome-o pelo PDV.'], 422);
        }
        $numeroCaixa = $this->numeroCaixaDoUsuario();
        $valor = (float) $request->valor_fundo;
        DB::beginTransaction();
        try {
            $caixa = Caixa::create([
                'numero_caixa' => $numeroCaixa,
                'user_id' => auth()->id(),
                'valor_abertura' => $valor,
                'status' => Caixa::STATUS_ABERTO,
                'opened_at' => now(),
            ]);
            CaixaMovimentacao::create([
                'caixa_id' => $caixa->id,
                'tipo' => CaixaMovimentacao::TIPO_ABERTURA,
                'valor' => $valor,
                'created_by' => auth()->id(),
            ]);
            DB::commit();
        } catch (\Throwable $e) {
            DB::rollBack();
            return response()->json(['message' => 'Erro ao abrir caixa: ' . $e->getMessage()], 500);
        }
        return response()->json(['caixa' => ['id' => $caixa->id, 'numero_caixa' => $caixa->numero_caixa, 'valor_abertura' => $valor, 'saldo_atual' => $valor]]);
    }

    public function caixaReforco(Request $request)
    {
        $caixa = $this->getCaixaAberto(null);
        if (!$caixa) {
            return response()->json(['message' => 'Nenhum caixa aberto.'], 422);
        }
        $request->validate([
            'valor' => 'required|numeric|min:0.01',
            'plano_conta_id' => 'required|exists:plano_contas,id',
        ]);
        $valor = (float) $request->valor;
        DB::beginTransaction();
        try {
            CaixaMovimentacao::create([
                'caixa_id' => $caixa->id,
                'tipo' => CaixaMovimentacao::TIPO_REFORCO,
                'valor' => $valor,
                'plano_conta_id' => $request->plano_conta_id,
                'created_by' => auth()->id(),
            ]);
            DB::commit();
        } catch (\Throwable $e) {
            DB::rollBack();
            return response()->json(['message' => 'Erro ao registrar reforço: ' . $e->getMessage()], 500);
        }
        $caixa->load('movimentacoes');
        return response()->json(['saldo_atual' => $caixa->saldo_atual, 'detalhe_saldo' => $caixa->getDetalheSaldo()]);
    }

    public function caixaSangria(Request $request)
    {
        $caixa = $this->getCaixaAberto(null);
        if (!$caixa) {
            return response()->json(['message' => 'Nenhum caixa aberto.'], 422);
        }

        $userId = auth()->id();
        $podeSangria = PdvPermissoes::podeSangria($userId);
        if (!$podeSangria) {
            $senha = $request->input('senha_autorizacao');
            if (is_string($senha) && trim($senha) !== '' && PdvPermissoes::validarSenhaAutorizador($senha)) {
                // Senha do autorizador correta, permite
            } else {
                $autorizadorNome = PdvPermissoes::getAutorizadorNome();
                return response()->json([
                    'message' => 'Você não tem permissão para fazer sangria. Digite a senha do autorizador para continuar.',
                    'requires_authorization' => true,
                    'autorizador_nome' => $autorizadorNome,
                ], 403);
            }
        }

        $request->validate([
            'valor' => 'required|numeric|min:0.01',
            'justificativa' => 'required|string|max:500',
        ]);
        $valor = (float) $request->valor;
        if ($valor > $caixa->saldo_atual) {
            return response()->json(['message' => 'Valor da sangria maior que o saldo em caixa.'], 422);
        }
        DB::beginTransaction();
        try {
            CaixaMovimentacao::create([
                'caixa_id' => $caixa->id,
                'tipo' => CaixaMovimentacao::TIPO_SANGRIA,
                'valor' => $valor,
                'justificativa' => $request->justificativa,
                'created_by' => auth()->id(),
            ]);
            DB::commit();
        } catch (\Throwable $e) {
            DB::rollBack();
            return response()->json(['message' => 'Erro ao registrar sangria: ' . $e->getMessage()], 500);
        }
        $caixa->load('movimentacoes');
        return response()->json(['saldo_atual' => $caixa->saldo_atual, 'detalhe_saldo' => $caixa->getDetalheSaldo(), 'comprovante' => ['tipo' => 'sangria', 'valor' => $valor, 'justificativa' => $request->justificativa]]);
    }

    /**
     * Formas de pagamento para o fechamento com total calculado por forma (ou 0 se operador não pode ver saldo).
     */
    public function caixaFechamentoFormas(Request $request)
    {
        $caixa = $this->getCaixaAberto(null);
        if (!$caixa) {
            return response()->json(['message' => 'Nenhum caixa aberto.'], 422);
        }
        $podeVerSaldo = PdvPermissoes::podeVerSaldoNoFechamento(auth()->id());
        $totaisPorForma = $podeVerSaldo ? $caixa->getTotaisPorFormaPagamento() : [];
        $formaIds = array_keys($totaisPorForma);
        if (empty($formaIds)) {
            $formaDinheiro = FormaPagamento::where('tipo', 'dinheiro')->where('ativo', true)->first();
            if ($formaDinheiro) {
                $formaIds = [$formaDinheiro->id];
                $totaisPorForma[$formaDinheiro->id] = $podeVerSaldo ? (float) $caixa->saldo_atual : 0.0;
            }
        }
        $formas = FormaPagamento::whereIn('id', $formaIds)->where('ativo', true)->orderBy('nome')->get(['id', 'nome', 'tipo']);
        $lista = $formas->map(function ($f) use ($totaisPorForma) {
            return [
                'id' => $f->id,
                'nome' => $f->nome,
                'tipo' => $f->tipo ?? 'outro',
                'total_calculado' => round($totaisPorForma[$f->id] ?? 0, 2),
            ];
        })->values()->all();
        return response()->json(['formas' => $lista, 'pode_ver_saldo_fechamento' => $podeVerSaldo]);
    }

    public function caixaFechar(Request $request)
    {
        $caixa = $this->getCaixaAberto(null);
        if (!$caixa) {
            return response()->json(['message' => 'Nenhum caixa aberto.'], 422);
        }

        $valoresInformados = $request->input('valores_informados');
        if (is_array($valoresInformados) && count($valoresInformados) > 0) {
            $valorInformado = 0.0;
            foreach ($valoresInformados as $item) {
                $valorInformado += (float) ($item['valor'] ?? 0);
            }
            $totaisPorForma = $caixa->getTotaisPorFormaPagamento();
            $valorCalculado = array_sum($totaisPorForma);
            $valoresInformadosPorForma = [];
            $valoresCalculadosPorForma = [];
            $diferencasPorForma = [];
            foreach ($valoresInformados as $item) {
                $formaId = (int) ($item['forma_pagamento_id'] ?? 0);
                $v = (float) ($item['valor'] ?? 0);
                $valoresInformadosPorForma[$formaId] = $v;
                $valoresCalculadosPorForma[$formaId] = $totaisPorForma[$formaId] ?? 0;
                $diferencasPorForma[$formaId] = round($v - ($totaisPorForma[$formaId] ?? 0), 2);
            }
        } else {
            $request->validate(['valor_informado' => 'required|numeric|min:0']);
            $valorInformado = (float) $request->valor_informado;
            $valorCalculado = (float) $caixa->saldo_atual;
            $valoresInformadosPorForma = null;
            $valoresCalculadosPorForma = null;
            $diferencasPorForma = null;
        }

        $quebraSobra = round($valorInformado - $valorCalculado, 2);
        $limite = PdvPermissoes::getQuebraSobraLimiteSemAprovacao();
        $exigeAprovacao = abs($quebraSobra) > $limite;
        $aprovadoPorUserId = null;

        if ($exigeAprovacao) {
            $senha = $request->input('senha_autorizacao');
            if (is_string($senha) && trim($senha) !== '' && PdvPermissoes::validarSenhaAutorizadorRetornaUserId(trim($senha)) !== null) {
                $aprovadoPorUserId = PdvPermissoes::validarSenhaAutorizadorRetornaUserId(trim($senha));
            } else {
                return response()->json([
                    'message' => 'Diferença de R$ ' . number_format(abs($quebraSobra), 2, ',', '.') . ' (' . ($quebraSobra >= 0 ? 'sobra' : 'quebra') . '). Acima do limite de R$ ' . number_format($limite, 2, ',', '.') . '. Digite a senha do administrador ou autorizador para concluir o fechamento.',
                    'requires_authorization' => true,
                    'autorizador_nome' => PdvPermissoes::getAutorizadorNome(),
                    'valor_quebra_sobra' => $quebraSobra,
                    'limite' => $limite,
                ], 403);
            }
        }

        DB::beginTransaction();
        try {
            CaixaMovimentacao::create([
                'caixa_id' => $caixa->id,
                'tipo' => CaixaMovimentacao::TIPO_FECHAMENTO,
                'valor' => $valorCalculado,
                'justificativa' => json_encode([
                    'valor_informado' => $valorInformado,
                    'valor_sistema' => $valorCalculado,
                    'quebra_sobra' => $quebraSobra,
                ]),
                'created_by' => auth()->id(),
            ]);
            $caixa->update([
                'status' => Caixa::STATUS_FECHADO,
                'valor_fechamento_informado' => $valorInformado,
                'valor_fechamento_sistema' => $valorCalculado,
                'closed_at' => now(),
            ]);
            CaixaFechamentoQuebraSobra::create([
                'caixa_id' => $caixa->id,
                'valor_quebra_sobra' => $quebraSobra,
                'aprovado_por_user_id' => $aprovadoPorUserId,
                'dados' => [
                    'valores_informados_por_forma' => $valoresInformadosPorForma,
                    'valores_calculados_por_forma' => $valoresCalculadosPorForma,
                    'diferencas_por_forma' => $diferencasPorForma,
                ],
            ]);
            DB::commit();
        } catch (\Throwable $e) {
            DB::rollBack();
            return response()->json(['message' => 'Erro ao fechar caixa: ' . $e->getMessage()], 500);
        }

        $relatorio = $quebraSobra == 0
            ? 'Fechamento conferido. Sem diferença.'
            : 'Fechamento registrado. ' . ($quebraSobra > 0 ? 'Sobra' : 'Quebra') . ': R$ ' . number_format(abs($quebraSobra), 2, ',', '.');
        return response()->json([
            'valor_informado' => $valorInformado,
            'valor_sistema' => $valorCalculado,
            'quebra_caixa' => $quebraSobra,
            'detalhe_saldo' => $caixa->getDetalheSaldo(),
            'valores_por_forma' => $valoresInformadosPorForma !== null ? [
                'informados' => $valoresInformadosPorForma,
                'calculados' => $valoresCalculadosPorForma,
                'diferencas' => $diferencasPorForma,
            ] : null,
            'relatorio' => $relatorio,
        ]);
    }

    public function buscaProdutos(Request $request)
    {
        try {
            $q = $request->get('q', '');
            $porEan = $request->get('ean');
            $query = \App\Models\Produto::query()->whereNull('deleted_at');
            if ($porEan && $porEan !== '') {
                $query->where(function ($q) use ($porEan) {
                    $q->where('ean', $porEan)->orWhere('codigo_sku', $porEan);
                });
            } elseif ($q !== '') {
                $query->where(function ($qb) use ($q) {
                    $qb->where('nome', 'like', "%{$q}%")
                        ->orWhere('codigo_sku', 'like', "%{$q}%")
                        ->orWhere('ean', 'like', "%{$q}%");
                });
            } else {
                $query->limit(50);
            }
            $produtos = $query->with([
                'imagens' => fn ($q) => $q->orderBy('ordem'),
                'variacoes',
                'variacaoAtributos',
                'composicoes.componente',
            ])
                ->limit(30)
                ->get(['id', 'nome', 'codigo_sku', 'ean', 'preco_venda', 'estoque_quantidade', 'categoria_id', 'formato']);
            $categoriasComSerial = get_option('categorias_com_serial', [], 'pdv');
            $produtosComSerial = get_option('produtos_com_serial', [], 'pdv');
            if (!is_array($categoriasComSerial)) {
                $categoriasComSerial = is_string($categoriasComSerial) ? (json_decode($categoriasComSerial, true) ?? []) : [];
            }
            if (!is_array($produtosComSerial)) {
                $produtosComSerial = is_string($produtosComSerial) ? (json_decode($produtosComSerial, true) ?? []) : [];
            }
            return response()->json([
                'produtos' => $produtos->map(function ($p) use ($categoriasComSerial, $produtosComSerial) {
                $exigeSerial = in_array((string) $p->id, array_map('strval', $produtosComSerial))
                    || in_array((string) $p->categoria_id, array_map('strval', $categoriasComSerial));
                $imagens = [];
                foreach ($p->imagens ?? [] as $img) {
                    if (!empty($img->url_externa)) {
                        $imagens[] = $img->url_externa;
                    } elseif (!empty($img->caminho_local)) {
                        $path = ltrim($img->caminho_local, '/');
                        $imagens[] = route('plugin.pdv.api.imagem', [], true) . '?path=' . rawurlencode($path);
                    }
                }
                $variacoes = [];
                if (($p->formato ?? '') === 'variacao' && $p->relationLoaded('variacoes')) {
                    foreach ($p->variacoes as $v) {
                        $variacoes[] = [
                            'id' => $v->id,
                            'referencia_sku' => $v->referencia_sku ?? '',
                            'opcoes_valores' => $v->opcoes_valores ?? [],
                            'ean_variacao' => $v->ean_variacao ?? null,
                            'quantidade' => $v->quantidade !== null ? (float) $v->quantidade : null,
                        ];
                    }
                }
                $variacaoAtributos = [];
                if ($p->relationLoaded('variacaoAtributos')) {
                    foreach ($p->variacaoAtributos as $a) {
                        $variacaoAtributos[] = [
                            'atributo_nome' => $a->atributo_nome ?? '',
                            'opcoes' => $a->opcoes ?? '',
                        ];
                    }
                }
                $kit_componentes = [];
                if (($p->formato ?? '') === 'composicao' && $p->relationLoaded('composicoes')) {
                    foreach ($p->composicoes as $comp) {
                        $qtd = (float) ($comp->quantidade ?? 1);
                        $nomeComp = $comp->componente?->nome ?? 'Item';
                        $kit_componentes[] = $qtd != 1 ? "{$nomeComp} ({$qtd}x)" : $nomeComp;
                    }
                }
                return [
                    'id' => $p->id,
                    'nome' => $p->nome,
                    'codigo' => $p->codigo_sku,
                    'ean' => $p->ean,
                    'preco' => (float) $p->preco_venda,
                    'estoque' => $p->estoque_quantidade !== null ? (float) $p->estoque_quantidade : null,
                    'exige_serial' => $exigeSerial,
                    'formato' => $p->formato ?? null,
                    'imagens' => $imagens,
                    'variacoes' => $variacoes,
                    'variacao_atributos' => $variacaoAtributos,
                    'kit_componentes' => $kit_componentes,
                ];
            }),
            ]);
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::error('PDV buscaProdutos: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString(),
                'q' => $request->get('q'),
                'ean' => $request->get('ean'),
            ]);
            return response()->json(['produtos' => [], 'message' => 'Erro ao buscar produtos. Tente novamente.'], 500);
        }
    }

    /**
     * Serve imagem de produto a partir do storage (evita 403 em ambiente com restrição a public/storage).
     * Requer autenticação.
     */
    public function imagem(Request $request)
    {
        $path = $request->query('path');
        if ($path === null || $path === '') {
            return response('', 404);
        }
        $path = ltrim($path, '/');
        if (preg_match('/\.\./u', $path) || preg_match('#^\.#u', $path)) {
            return response('', 404);
        }
        $fullPath = storage_path('app/public/' . $path);
        if (!is_file($fullPath)) {
            return response('', 404);
        }
        $mime = mime_content_type($fullPath) ?: 'application/octet-stream';
        return response()->file($fullPath, ['Content-Type' => $mime]);
    }

    public function buscaServicos(Request $request)
    {
        $q = $request->get('q', '');
        $query = \App\Models\Servico::query()->whereNull('deleted_at');
        if ($q !== '') {
            $query->where(function ($qb) use ($q) {
                $qb->where('nome', 'like', "%{$q}%")->orWhere('codigo_interno', 'like', "%{$q}%");
            });
        }
        $servicos = $query->limit(30)->get(['id', 'nome', 'codigo_interno', 'preco']);
        return response()->json(['servicos' => $servicos->map(fn ($s) => ['id' => $s->id, 'nome' => $s->nome, 'codigo' => $s->codigo_interno, 'preco' => (float) $s->preco])]);
    }

    public function buscaClientes(Request $request)
    {
        $q = trim((string) $request->get('q', ''));
        $query = \App\Models\Cliente::query()->whereNull('deleted_at');
        if ($q !== '') {
            $qDigits = preg_replace('/\D/', '', $q);
            $query->where(function ($qb) use ($q, $qDigits) {
                $qb->where('nome', 'like', '%' . addcslashes($q, '%_\\') . '%')
                    ->orWhere('razao_social', 'like', '%' . addcslashes($q, '%_\\') . '%')
                    ->orWhere('documento_estrangeiro', 'like', '%' . addcslashes($q, '%_\\') . '%');
                if ($qDigits !== '') {
                    $likeDigits = '%' . $qDigits . '%';
                    $qb->orWhereRaw('REPLACE(REPLACE(REPLACE(cpf, ".", ""), "-", ""), " ", "") LIKE ?', [$likeDigits]);
                    $qb->orWhereRaw('REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(cnpj, ".", ""), "/", ""), "-", ""), " ", ""), ",", "") LIKE ?', [$likeDigits]);
                }
            });
        }
        $clientes = $query->limit(20)->get();
        return response()->json([
            'clientes' => $clientes->map(fn ($c) => [
                'id' => $c->id,
                'nome' => $c->nome ?? $c->razao_social,
                'documento' => $c->documento_principal,
                'bloqueado_vendas' => (bool) ($c->bloqueado_vendas ?? false),
                'limite_credito' => $c->limite_credito !== null ? (float) $c->limite_credito : null,
            ]),
        ]);
    }

    public function formasPagamento(Request $request)
    {
        $campos = ['id', 'nome', 'tipo', 'permite_parcela', 'max_parcelas', 'dias_recebimento', 'conta_bancaria_id'];
        $temPixChaves = Schema::hasColumn('formas_pagamento', 'pix_chaves');
        if ($temPixChaves) {
            $campos[] = 'pix_chaves';
        }
        $formas = \App\Models\FormaPagamento::where('ativo', true)->orderBy('nome')->get($campos)->map(function ($f) use ($temPixChaves) {
            $f->pix_chaves = $temPixChaves ? ($f->pix_chaves_ativas ?? []) : [];
            return $f;
        });
        $bandeiras = $this->bandeirasCadastradas();

        return response()->json(['formas' => $formas, 'bandeiras' => $bandeiras])
            ->header('Cache-Control', 'private, no-store, must-revalidate')
            ->header('Pragma', 'no-cache');
    }

    /**
     * Bandeiras que possuem pelo menos uma taxa ativa cadastrada em algum adquirente.
     * Usado no PDV para exibir apenas as bandeiras configuradas no sistema.
     */
    protected function bandeirasCadastradas(): array
    {
        $bandeirasValidas = [
            'master' => 'Mastercard',
            'visa' => 'Visa',
            'elo' => 'Elo',
            'amex' => 'Amex',
            'hipercard' => 'Hipercard',
            'outros' => 'Outros',
        ];
        $valores = \App\Models\TaxaAdquirente::where('ativo', true)
            ->where(function ($q) {
                $q->whereNull('data_inicio')->orWhere('data_inicio', '<=', now());
            })
            ->where(function ($q) {
                $q->whereNull('data_fim')->orWhere('data_fim', '>=', now());
            })
            ->distinct()
            ->orderBy('bandeira')
            ->pluck('bandeira')
            ->values()
            ->all();
        $lista = [];
        foreach ($valores as $value) {
            $value = strtolower((string) $value);
            if (isset($bandeirasValidas[$value])) {
                $lista[] = ['value' => $value, 'label' => $bandeirasValidas[$value]];
            } else {
                $lista[] = ['value' => $value, 'label' => ucfirst($value)];
            }
        }
        return $lista;
    }

    /**
     * Lista adquirentes ativos (para cartão crédito/débito no pagamento).
     */
    public function adquirentes(Request $request)
    {
        $lista = \App\Models\Adquirente::where('ativo', true)->orderBy('nome')->get(['id', 'nome']);
        return response()->json(['adquirentes' => $lista]);
    }

    public function planoContasAporte(Request $request)
    {
        $contas = \App\Models\PlanoConta::where('ativo', true)->orderBy('codigo')->get(['id', 'codigo', 'nome']);
        return response()->json(['contas' => $contas]);
    }

    public function vendasList(Request $request)
    {
        $caixa = $this->getCaixaAberto(null);
        if (!$caixa) {
            return response()->json(['vendas' => []]);
        }
        $vendas = Venda::where('caixa_id', $caixa->id)->with('itens', 'pagamentos')->orderByDesc('created_at')->limit(100)->get();
        return response()->json(['vendas' => $vendas]);
    }

    /** Histórico de vendas do caixa atual (auditoria por operador/turno). */
    public function caixaHistorico(Request $request)
    {
        $caixa = $this->getCaixaAberto(null);
        if (!$caixa) {
            return response()->json(['vendas' => [], 'message' => 'Nenhum caixa aberto.']);
        }
        $vendas = Venda::where('caixa_id', $caixa->id)->where('status', Venda::STATUS_FINALIZADA)
            ->with('itens', 'pagamentos', 'cliente')
            ->orderByDesc('created_at')
            ->limit(200)
            ->get();
        return response()->json([
            'caixa_id' => $caixa->id,
            'numero_caixa' => $caixa->numero_caixa,
            'operador' => $caixa->user ? $caixa->user->name : null,
            'opened_at' => $caixa->opened_at?->toIso8601String(),
            'vendas' => $vendas,
        ]);
    }

    public function vendaStore(Request $request)
    {
        $caixa = $this->getCaixaAberto(null);
        if (!$caixa) {
            return response()->json(['message' => 'Abra o caixa antes de registrar vendas.'], 422);
        }
        $request->validate([
            'cliente_id' => 'nullable|exists:clientes,id',
            'itens' => 'required|array|min:1',
            'itens.*.tipo' => 'required|in:produto,servico',
            'itens.*.produto_id' => 'required_if:itens.*.tipo,produto',
            'itens.*.servico_id' => 'required_if:itens.*.tipo,servico',
            'itens.*.quantidade' => 'required|numeric|min:0.001',
            'itens.*.preco_unitario' => 'required|numeric|min:0',
            'itens.*.serial' => 'nullable|string|max:100',
            'itens.*.observacao' => 'nullable|string|max:255',
            'pagamentos' => 'required|array|min:1',
            'pagamentos.*.forma_pagamento_id' => 'required|exists:formas_pagamento,id',
            'pagamentos.*.valor' => 'required|numeric|min:0.01',
            'pagamentos.*.parcelas' => 'nullable|integer|min:1',
            'pagamentos.*.conta_bancaria_id' => 'nullable|exists:contas_bancarias,id',
            'pagamentos.*.adquirente_id' => 'nullable|exists:adquirentes,id',
            'pagamentos.*.bandeira' => 'nullable|string|max:50',
            'pagamentos.*.antecipado' => 'nullable|boolean',
            'pagamentos.*.pix_chave_id' => 'nullable|string|max:40',
            'pagamentos.*.pix_parcela_vencimentos' => 'nullable|array',
            'pagamentos.*.pix_parcela_vencimentos.*' => 'nullable|string|max:32',
            'pagamentos.*.observacoes' => 'nullable|string|max:500',
            'desconto' => 'nullable|numeric|min:0',
            'observacoes' => 'nullable|string|max:5000',
            'observacao_interna' => 'nullable|string|max:5000',
            'desconto_autorizado_por_user_id' => 'nullable|integer|exists:users,id',
            'cliente_bloqueado_autorizado_por_user_id' => 'nullable|integer|exists:users,id',
            'limite_credito_autorizado_por_user_id' => 'nullable|integer|exists:users,id',
            'estoque_zerado_autorizado_por_user_id' => 'nullable|integer|exists:users,id',
        ]);
        $clienteId = $request->cliente_id ? (int) $request->cliente_id : null;
        if ($clienteId) {
            $cliente = Cliente::find($clienteId);
            if ($cliente && $cliente->bloqueado_vendas) {
                $autorizadorIds = PdvPermissoes::getAutorizadorUserIds();
                $autorizadoPor = $request->filled('cliente_bloqueado_autorizado_por_user_id') ? (int) $request->cliente_bloqueado_autorizado_por_user_id : null;
                $currentUserId = auth()->id();
                $currentUserIsAdmin = (auth()->user()->is_admin ?? false);
                $autorizacaoValida = $autorizadoPor && (in_array($autorizadoPor, $autorizadorIds, true) || ($currentUserIsAdmin && $autorizadoPor === $currentUserId));
                if (!$autorizacaoValida) {
                    return response()->json([
                        'message' => 'Cliente bloqueado para vendas. É necessária a autorização do administrador ou de um usuário autorizador.',
                        'code' => 'cliente_bloqueado',
                    ], 422);
                }
            }
            $formasIds = array_unique(array_column($request->pagamentos ?? [], 'forma_pagamento_id'));
            $formas = \App\Models\FormaPagamento::whereIn('id', $formasIds)->get(['id', 'tipo']);
            $valorBoleto = 0;
            foreach ($request->pagamentos ?? [] as $p) {
                $forma = $formas->firstWhere('id', (int) $p['forma_pagamento_id']);
                if ($forma && strtolower((string) $forma->tipo) === 'boleto') {
                    $valorBoleto += (float) ($p['valor'] ?? 0);
                }
            }
            if ($valorBoleto > 0 && $cliente && $cliente->limite_credito !== null) {
                $limite = (float) $cliente->limite_credito;
                if ($valorBoleto > $limite) {
                    $autorizadorIds = PdvPermissoes::getAutorizadorUserIds();
                    $autorizadoPor = $request->filled('limite_credito_autorizado_por_user_id') ? (int) $request->limite_credito_autorizado_por_user_id : null;
                    $currentUserId = auth()->id();
                    $currentUserIsAdmin = (auth()->user()->is_admin ?? false);
                    $autorizacaoValida = $autorizadoPor && (in_array($autorizadoPor, $autorizadorIds, true) || ($currentUserIsAdmin && $autorizadoPor === $currentUserId));
                    if (!$autorizacaoValida) {
                        return response()->json([
                            'message' => 'Limite de crédito do cliente excedido. Valor em boleto: R$ ' . number_format($valorBoleto, 2, ',', '.') . '. Limite: R$ ' . number_format($limite, 2, ',', '.') . '. Autorização necessária.',
                            'code' => 'limite_credito_excedido',
                            'valor_boleto' => $valorBoleto,
                            'limite_credito' => $limite,
                        ], 422);
                    }
                }
            }
        }
        if (PdvPermissoes::getControleEstoque()) {
            $itensParaVerificar = array_map(fn ($i) => [
                'tipo' => $i['tipo'] ?? 'produto',
                'produto_id' => $i['tipo'] === 'produto' ? ($i['produto_id'] ?? null) : null,
                'quantidade' => (float) ($i['quantidade'] ?? 0),
            ], $request->itens);
            $insuficientes = EstoqueHelper::verificarEstoqueVenda($itensParaVerificar);
            if ($insuficientes !== null) {
                $currentUserId = auth()->id();
                $permissoes = PdvPermissoes::getPermissoesUsuario($currentUserId);
                $podeVenderZerado = $permissoes['pode_vender_estoque_zerado'] ?? false;
                $autorizadoPor = $request->filled('estoque_zerado_autorizado_por_user_id') ? (int) $request->estoque_zerado_autorizado_por_user_id : null;
                $estoqueZeroIds = PdvPermissoes::getEstoqueZeroAutorizadorUserIds();
                $autorizacaoValida = $autorizadoPor && in_array($autorizadoPor, $estoqueZeroIds, true);
                if (!$podeVenderZerado && !$autorizacaoValida) {
                    $nomes = array_column($insuficientes, 'nome');
                    return response()->json([
                        'message' => 'Estoque insuficiente ou zerado para: ' . implode(', ', array_slice($nomes, 0, 3)) . (count($nomes) > 3 ? ' e outros.' : '') . ' Digite a senha do administrador ou de um usuário com permissão para vender com estoque zerado.',
                        'code' => 'estoque_insuficiente',
                        'produtos' => $insuficientes,
                        'estoque_zerado_autorizador_nome' => PdvPermissoes::getEstoqueZeroAutorizadorNome(),
                    ], 422);
                }
            }
        }
        $total = 0;
        $seriaisGarantia = [];
        foreach ($request->itens as $item) {
            $qtd = (float) $item['quantidade'];
            $preco = (float) $item['preco_unitario'];
            $total += $qtd * $preco;
            if (!empty($item['serial'])) {
                $seriaisGarantia[] = $item['serial'];
            }
        }
        $desconto = (float) ($request->desconto ?? 0);
        $totalFinal = $total - $desconto;
        $somaPagamentos = collect($request->pagamentos)->sum(fn ($p) => (float) $p['valor']);
        if (abs($somaPagamentos - $totalFinal) > 0.02) {
            return response()->json(['message' => 'Soma dos pagamentos (R$ ' . number_format($somaPagamentos, 2, ',', '.') . ') deve ser igual ao total (R$ ' . number_format($totalFinal, 2, ',', '.') . ').'], 422);
        }
        DB::beginTransaction();
        try {
            $venda = Venda::create([
                'caixa_id' => $caixa->id,
                'cliente_id' => $request->cliente_id,
                'tipo' => Venda::TIPO_VENDA,
                'status' => Venda::STATUS_FINALIZADA,
                'total' => $total,
                'desconto' => $desconto,
                'desconto_autorizado_por_user_id' => $request->filled('desconto_autorizado_por_user_id') ? (int) $request->desconto_autorizado_por_user_id : null,
                'cliente_bloqueado_autorizado_por_user_id' => $request->filled('cliente_bloqueado_autorizado_por_user_id') ? (int) $request->cliente_bloqueado_autorizado_por_user_id : null,
                'limite_credito_autorizado_por_user_id' => $request->filled('limite_credito_autorizado_por_user_id') ? (int) $request->limite_credito_autorizado_por_user_id : null,
                'estoque_zerado_autorizado_por_user_id' => $request->filled('estoque_zerado_autorizado_por_user_id') ? (int) $request->estoque_zerado_autorizado_por_user_id : null,
                'total_final' => $totalFinal,
                'observacoes' => $request->observacoes ? trim((string) $request->observacoes) : null,
                'observacao_interna' => $request->observacao_interna ? trim((string) $request->observacao_interna) : null,
                'seriais_garantia' => $seriaisGarantia ?: null,
                'synced_at' => now(),
            ]);
            foreach ($request->itens as $item) {
                $qtd = (float) $item['quantidade'];
                $preco = (float) $item['preco_unitario'];
                VendaItem::create([
                    'venda_id' => $venda->id,
                    'tipo' => $item['tipo'],
                    'produto_id' => $item['tipo'] === 'produto' ? ($item['produto_id'] ?? null) : null,
                    'servico_id' => $item['tipo'] === 'servico' ? ($item['servico_id'] ?? null) : null,
                    'descricao' => $item['descricao'] ?? '',
                    'quantidade' => $qtd,
                    'preco_unitario' => $preco,
                    'total' => $qtd * $preco,
                    'serial' => $item['serial'] ?? null,
                    'kit_componentes' => $item['kit_componentes'] ?? null,
                    'observacao' => isset($item['observacao']) && (string) $item['observacao'] !== '' ? (string) $item['observacao'] : null,
                ]);
            }
            foreach ($request->pagamentos as $p) {
                $pixVenc = $p['pix_parcela_vencimentos'] ?? null;
                $pixVencNorm = is_array($pixVenc) ? array_values(array_filter($pixVenc, static fn ($d) => $d !== null && trim((string) $d) !== '')) : null;
                $pixVencNorm = $pixVencNorm !== null && $pixVencNorm !== [] ? $pixVencNorm : null;
                VendaPagamento::create([
                    'venda_id' => $venda->id,
                    'forma_pagamento_id' => $p['forma_pagamento_id'],
                    'valor' => (float) $p['valor'],
                    'parcelas' => (int) ($p['parcelas'] ?? 1),
                    'conta_bancaria_id' => $p['conta_bancaria_id'] ?? null,
                    'adquirente_id' => $p['adquirente_id'] ?? null,
                    'bandeira' => $p['bandeira'] ?? null,
                    'antecipado' => isset($p['antecipado']) ? (bool) $p['antecipado'] : null,
                    'observacoes' => isset($p['observacoes']) && trim((string) $p['observacoes']) !== '' ? trim((string) $p['observacoes']) : null,
                    'pix_chave_id' => isset($p['pix_chave_id']) && trim((string) $p['pix_chave_id']) !== '' ? substr(trim((string) $p['pix_chave_id']), 0, 40) : null,
                    'pix_parcela_vencimentos' => $pixVencNorm,
                ]);
            }
            DB::commit();
            EstoqueHelper::baixarEstoqueVenda($venda);
            try {
                $this->criarFinanceiroPdvVenda($venda);
            } catch (\Throwable $e) {
                Log::error('PDV: Erro ao criar lançamentos financeiros da venda #' . $venda->id . ': ' . $e->getMessage(), ['exception' => $e]);
            }
        } catch (\Throwable $e) {
            DB::rollBack();
            return response()->json(['message' => 'Erro ao registrar venda: ' . $e->getMessage()], 500);
        }
        $venda->load('itens', 'pagamentos.formaPagamento', 'cliente');
        $empresa = null;
        if (class_exists(\App\Models\Empresa::class)) {
            try {
                $empresa = \App\Models\Empresa::first();
            } catch (\Throwable $e) {
                $empresa = null;
            }
        }
        $venda->empresa_nome = $empresa?->nome ?? config('app.name', 'Loja');
        $venda->empresa_cnpj = $empresa?->cnpj ?? null;
        $numeroInicial = (int) get_option('pdv_numero_venda_inicial', 1, 'pdv');
        if ($numeroInicial < 1) $numeroInicial = 1;
        $venda->numero_exibicao = ($numeroInicial - 1) + $venda->id;
        return response()->json(['venda' => $venda, 'message' => 'Venda registrada. Estoque atualizado.']);
    }

    public function vendaShow(Request $request, $venda)
    {
        $v = Venda::with('caixa', 'itens.produto', 'itens.servico', 'pagamentos.formaPagamento', 'cliente')->find($venda);
        if (!$v) {
            return response()->json(['message' => 'Venda não encontrada.'], 404);
        }
        $user = auth()->user();
        $isAdmin = $user && ($user->is_admin ?? false);
        if (!$isAdmin && $v->caixa && (int) $v->caixa->user_id !== (int) $user->id) {
            return response()->json(['message' => 'Acesso não autorizado a esta venda.'], 403);
        }
        $this->vendaComDadosEmpresa($v);
        return response()->json(['venda' => $v]);
    }

    /**
     * Adiciona empresa_nome e empresa_cnpj à venda para exibição no comprovante (histórico/visualizar).
     */
    private function vendaComDadosEmpresa(Venda $v): Venda
    {
        $empresa = null;
        if (class_exists(\App\Models\Empresa::class)) {
            try {
                $empresa = \App\Models\Empresa::first();
            } catch (\Throwable $e) {
                $empresa = null;
            }
        }
        $v->empresa_nome = $empresa?->nome ?? config('app.name', 'Loja');
        $v->empresa_cnpj = $empresa?->cnpj ?? null;
        return $v;
    }

    /**
     * Gera PDF do comprovante da venda (para compartilhar/imprimir).
     * GET /api/vendas/{venda}/comprovante-pdf
     */
    public function vendaComprovantePdf(Request $request, $venda)
    {
        $v = Venda::with('itens', 'pagamentos.formaPagamento', 'cliente')->find($venda);
        if (!$v) {
            abort(404, 'Venda não encontrada.');
        }
        if ($v->status !== Venda::STATUS_FINALIZADA) {
            abort(422, 'Comprovante disponível apenas para vendas finalizadas.');
        }

        $empresa = null;
        if (class_exists(\App\Models\Empresa::class)) {
            try {
                $empresa = \App\Models\Empresa::first();
            } catch (\Throwable $e) {
                $empresa = null;
            }
        }
        $empresaLogoBase64 = null;
        if ($empresa?->logo_path) {
            $logoPath = ltrim(str_replace('\\', '/', (string) $empresa->logo_path), '/');
            if ($logoPath !== '' && Storage::disk('public')->exists($logoPath)) {
                $mime = Storage::disk('public')->mimeType($logoPath) ?: 'image/png';
                $empresaLogoBase64 = 'data:' . $mime . ';base64,' . base64_encode(Storage::disk('public')->get($logoPath));
            }
        }

        $clienteNome = $v->cliente ? ($v->cliente->nome ?? $v->cliente->razao_social ?? 'Cliente balcão') : 'Cliente balcão';
        $clienteDados = null;
        if ($v->cliente_id && $v->cliente) {
            $cliente = $v->cliente;
            try {
                $cliente->load(['enderecos', 'contatos']);
                $endereco = $cliente->enderecos->firstWhere('principal', true) ?? $cliente->enderecos->first();
                $enderecoTexto = $endereco ? trim($endereco->logradouro . ' ' . $endereco->numero . ($endereco->complemento ? ' - ' . $endereco->complemento : '') . ', ' . $endereco->bairro . ' - ' . $endereco->cidade . '/' . $endereco->estado) : '';
                if ($endereco && !empty($endereco->cep)) {
                    $enderecoTexto .= ($enderecoTexto ? '. ' : '') . 'CEP: ' . $endereco->cep;
                }
                $contato = $cliente->contatos->firstWhere('principal', true) ?? $cliente->contatos->first();
                $clienteDados = [
                    'nome' => $cliente->nome ?? $cliente->razao_social ?? $clienteNome,
                    'documento' => $cliente->documento_principal ?? $cliente->cpf ?? $cliente->cnpj ?? $cliente->documento_estrangeiro ?? null,
                    'endereco' => $enderecoTexto ?: null,
                    'telefone' => $contato ? ($contato->telefone ?? $contato->telefone2 ?? null) : null,
                    'email' => $contato ? ($contato->email ?? $contato->email2 ?? null) : null,
                ];
            } catch (\Throwable $e) {
                $clienteDados = null;
            }
        }
        $itens = $v->itens->map(function ($item) {
            return [
                'tipo' => $item->tipo,
                'descricao' => $item->descricao,
                'quantidade' => (float) $item->quantidade,
                'preco_unitario' => (float) $item->preco_unitario,
                'total' => (float) $item->total,
                'serial' => $item->serial,
                'observacao' => isset($item->observacao) && trim((string) $item->observacao) !== '' ? trim((string) $item->observacao) : null,
                'kit_componentes' => $item->kit_componentes ?? [],
            ];
        })->toArray();
        $pagamentos = $v->pagamentos->map(function ($p) {
            return [
                'forma_nome' => $p->formaPagamento ? $p->formaPagamento->nome : 'Pagamento',
                'valor' => (float) $p->valor,
                'parcelas' => (int) ($p->parcelas ?? 1),
            ];
        })->toArray();

        $data = [
            'venda' => $v,
            'numeroCupom' => (string) $v->id,
            'dataHora' => $v->created_at->format('d/m/Y H:i'),
            'clienteNome' => $clienteNome,
            'clienteDados' => $clienteDados,
            'itens' => $itens,
            'pagamentos' => $pagamentos,
            'subtotal' => (float) $v->total,
            'desconto' => (float) $v->desconto,
            'totalFinal' => (float) $v->total_final,
            'empresa' => $empresa,
            'empresaLogoBase64' => $empresaLogoBase64,
            'empresaLogoUrl' => null,
            'seriaisGarantia' => $v->seriais_garantia ?? [],
            'observacoesVenda' => $v->observacoes ? trim((string) $v->observacoes) : '',
        ];

        $html = view('pdv::comprovante-venda-pdf', $data)->render();
        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadHTML($html);
        $pdf->setPaper('A4', 'portrait');
        $pdf->setOption('margin-top', '6mm');
        $pdf->setOption('margin-bottom', '10mm');
        $pdf->setOption('margin-left', '10mm');
        $pdf->setOption('margin-right', '10mm');
        $filename = 'comprovante-venda-' . $v->id . '.pdf';
        return $pdf->stream($filename);
    }

    /**
     * Cancela uma venda (total ou parcial): estorno de estoque, conta a pagar proporcional.
     * Body: tipo total|parcial (default total), itens[] { item_id, quantidade_cancelar } para parcial,
     * motivo_cancelamento, senha_autorizacao se necessário.
     */
    public function vendaCancelar(Request $request, $venda)
    {
        $v = Venda::with(['itens', 'caixa'])->find($venda);
        if (!$v) {
            return response()->json(['message' => 'Venda não encontrada.'], 404);
        }
        if ($v->status === Venda::STATUS_CANCELADA) {
            return response()->json(['message' => 'Esta venda já está cancelada.'], 422);
        }
        if ($v->status !== Venda::STATUS_FINALIZADA) {
            return response()->json(['message' => 'Só é possível cancelar vendas finalizadas.'], 422);
        }

        $user = auth()->user();
        $userId = $user?->id;
        $isAdmin = $user && ($user->is_admin ?? false);
        if (!$isAdmin && $v->caixa && (int) $v->caixa->user_id !== (int) $userId) {
            return response()->json(['message' => 'Acesso não autorizado a esta venda.'], 403);
        }

        $tipo = $request->input('tipo', 'total');
        $tipo = $tipo === 'parcial' ? 'parcial' : 'total';
        $permTipo = $tipo === 'parcial' ? 'parcial' : 'total';

        $pode = $tipo === 'parcial'
            ? PdvPermissoes::podeCancelarVendaParcial($userId)
            : PdvPermissoes::podeCancelarVendaTotal($userId);

        $cancelamentoAutorizadoPorUserId = null;
        if (!$pode) {
            $senha = $request->input('senha_autorizacao');
            if (is_string($senha) && trim($senha) !== '') {
                $cancelamentoAutorizadoPorUserId = PdvPermissoes::validarSenhaAutorizadorParaCancelamento(trim($senha), $permTipo);
            }
            if ($cancelamentoAutorizadoPorUserId === null) {
                $msg = $tipo === 'parcial'
                    ? 'Você não tem permissão para cancelamento parcial. Digite a senha de um autorizador com essa permissão (ou administrador).'
                    : 'Você não tem permissão para cancelamento total. Digite a senha de um autorizador com essa permissão (ou administrador).';

                return response()->json([
                    'message' => $msg,
                    'requires_authorization' => true,
                    'cancelamento_tipo' => $permTipo,
                    'autorizador_nome' => PdvPermissoes::getAutorizadorNome(),
                ], 403);
            }
        }

        $motivo = $request->input('motivo_cancelamento');
        if (is_string($motivo)) {
            $motivo = trim($motivo) !== '' ? trim($motivo) : null;
        } else {
            $motivo = null;
        }

        if ($tipo === 'parcial') {
            $request->validate([
                'itens' => 'required|array|min:1',
                'itens.*.item_id' => 'required|integer',
                'itens.*.quantidade_cancelar' => 'required|numeric|min:0.0001',
            ]);
        }

        DB::beginTransaction();
        try {
            $v->refresh();
            $v->load('itens');

            if ($tipo === 'total') {
                $this->executarCancelamentoVendaTotal($v, $motivo, $userId, $cancelamentoAutorizadoPorUserId);
            } else {
                $this->executarCancelamentoVendaParcial($v, $request->input('itens', []), $motivo, $userId, $cancelamentoAutorizadoPorUserId);
            }

            DB::commit();
        } catch (\InvalidArgumentException $e) {
            DB::rollBack();

            return response()->json(['message' => $e->getMessage()], 422);
        } catch (\Throwable $e) {
            DB::rollBack();

            return response()->json(['message' => 'Erro ao cancelar venda: ' . $e->getMessage()], 500);
        }

        $vFresh = $v->fresh(['itens']);
        $msg = $tipo === 'parcial'
            ? ($vFresh->status === Venda::STATUS_CANCELADA
                ? 'Cancelamento concluído: venda totalmente cancelada. Conta a pagar e estoque atualizados.'
                : 'Cancelamento parcial registrado. Conta a pagar e estoque atualizados.')
            : 'Venda cancelada. Estoque estornado. Lançamento criado em Contas a Pagar para estorno.';

        return response()->json(['message' => $msg, 'venda' => $vFresh]);
    }

    /**
     * Cancelamento total: restante da venda (após parciais) vira estorno.
     */
    private function executarCancelamentoVendaTotal(Venda $v, ?string $motivo, ?int $userId, ?int $cancelamentoAutorizadoPorUserId): void
    {
        $oldTotalFinal = (float) $v->total_final;

        $v->update([
            'status' => Venda::STATUS_CANCELADA,
            'motivo_cancelamento' => $motivo,
            'cancelado_por_user_id' => $userId,
            'cancelamento_autorizado_por_user_id' => $cancelamentoAutorizadoPorUserId,
            'canceled_at' => now(),
        ]);

        EstoqueHelper::estornarEstoqueVenda($v->fresh('itens'));

        $v->refresh();
        $v->load('itens');
        foreach ($v->itens as $item) {
            $q = (float) $item->quantidade;
            $item->quantidade_cancelada = $q;
            $item->total = 0;
            $item->save();
        }

        $v->update([
            'total' => 0,
            'desconto' => 0,
            'total_final' => 0,
        ]);

        if ($oldTotalFinal <= 0.009) {
            return;
        }

        $v->load(['pagamentos.formaPagamento.contaBancaria', 'pagamentos.contaBancaria', 'pagamentos.adquirente', 'cliente', 'caixa.user', 'canceladoPorUser', 'cancelamentoAutorizadoPorUser']);
        $observacoes = $this->montarObservacoesContaPagarCancelamento($v->fresh(['itens', 'pagamentos.formaPagamento', 'cliente', 'caixa.user', 'canceladoPorUser', 'cancelamentoAutorizadoPorUser']), $oldTotalFinal, 'total', [], $motivo);
        $this->criarContaPagarEstornoPdv($v, $oldTotalFinal, 'PDV-CANC-' . $v->id, 'Estorno - Venda PDV #' . $v->id . ' cancelada (total)', $observacoes, $userId);
    }

    /**
     * @param array<int, array{item_id: int, quantidade_cancelar: float}> $linhasPayload
     */
    private function executarCancelamentoVendaParcial(Venda $v, array $linhasPayload, ?string $motivo, ?int $userId, ?int $cancelamentoAutorizadoPorUserId): void
    {
        $itensPorId = $v->itens->keyBy('id');
        $deltas = [];

        foreach ($linhasPayload as $row) {
            $itemId = (int) ($row['item_id'] ?? 0);
            $qCancel = (float) ($row['quantidade_cancelar'] ?? 0);
            if ($itemId <= 0 || $qCancel <= 0) {
                continue;
            }
            $item = $itensPorId->get($itemId);
            if (!$item) {
                throw new \InvalidArgumentException('Item #' . $itemId . ' não pertence a esta venda.');
            }
            $jaCancel = (float) ($item->quantidade_cancelada ?? 0);
            $qtd = (float) $item->quantidade;
            $disponivel = max(0, $qtd - $jaCancel);
            if ($qCancel > $disponivel + 1e-6) {
                throw new \InvalidArgumentException('Quantidade a cancelar maior que o disponível na linha "' . ($item->descricao ?? '#' . $itemId) . '".');
            }
            $deltas[$itemId] = ($deltas[$itemId] ?? 0) + $qCancel;
        }

        if (empty($deltas)) {
            throw new \InvalidArgumentException('Informe ao menos um item com quantidade válida para cancelar.');
        }

        $oldTotalFinal = (float) $v->total_final;
        $oldSubtotal = (float) $v->total;
        $oldDesconto = (float) $v->desconto;

        $linhasResumo = [];

        foreach ($deltas as $itemId => $delta) {
            $item = $itensPorId->get($itemId);
            $item->quantidade_cancelada = round((float) $item->quantidade_cancelada + $delta, 4);
            $qRest = EstoqueHelper::quantidadeLinhaAindaVendida($item);
            $pu = (float) $item->preco_unitario;
            $item->total = round($qRest * $pu, 2);
            $item->save();

            if ($item->tipo === VendaItem::TIPO_PRODUTO) {
                EstoqueHelper::estornarEstoqueItemQuantidade($item, $delta);
            }

            $linhasResumo[] = [
                'descricao' => $item->descricao,
                'quantidade_cancelada' => $delta,
                'tipo' => $item->tipo,
            ];
        }

        $newSubtotal = 0.0;
        foreach ($v->itens()->get() as $it) {
            $newSubtotal += (float) $it->total;
        }
        $newSubtotal = round($newSubtotal, 2);

        $newDesconto = $oldSubtotal > 1e-6
            ? round($oldDesconto * ($newSubtotal / $oldSubtotal), 2)
            : 0.0;
        $newTotalFinal = round(max(0, $newSubtotal - $newDesconto), 2);

        $valorEstorno = round($oldTotalFinal - $newTotalFinal, 2);
        if ($valorEstorno <= 0.009) {
            throw new \InvalidArgumentException('O cancelamento não altera o valor da venda.');
        }

        $tudoCancelado = true;
        foreach ($v->itens()->get() as $it) {
            if (EstoqueHelper::quantidadeLinhaAindaVendida($it) > 1e-6) {
                $tudoCancelado = false;
                break;
            }
        }

        $attrs = [
            'total' => $newSubtotal,
            'desconto' => $newDesconto,
            'total_final' => $newTotalFinal,
        ];

        if ($tudoCancelado) {
            $attrs['status'] = Venda::STATUS_CANCELADA;
            $attrs['motivo_cancelamento'] = $motivo;
            $attrs['cancelado_por_user_id'] = $userId;
            $attrs['cancelamento_autorizado_por_user_id'] = $cancelamentoAutorizadoPorUserId;
            $attrs['canceled_at'] = now();
        }

        $v->update($attrs);
        $v->refresh();

        $v->load(['pagamentos.formaPagamento.contaBancaria', 'pagamentos.contaBancaria', 'pagamentos.adquirente', 'cliente', 'caixa.user', 'canceladoPorUser', 'cancelamentoAutorizadoPorUser']);
        $observacoes = $this->montarObservacoesContaPagarCancelamento($v, $valorEstorno, 'parcial', $linhasResumo, $motivo);
        $doc = 'PDV-PARC-' . $v->id . '-' . substr(str_replace('.', '', uniqid('', true)), -10);
        $this->criarContaPagarEstornoPdv(
            $v,
            $valorEstorno,
            $doc,
            'Estorno parcial - Venda PDV #' . $v->id,
            $observacoes,
            $userId
        );
    }

    private function criarContaPagarEstornoPdv(Venda $v, float $valor, string $numeroDocumento, string $descricao, string $observacoes, ?int $userId): void
    {
        $planoContaEstornoId = get_option('plano_conta_estorno_pdv_id', null, 'pdv');
        if (!$planoContaEstornoId) {
            $primeiraDespesa = PlanoConta::where('tipo', 'despesa')->where('ativo', true)->orderBy('ordem')->orderBy('nome')->value('id');
            $planoContaEstornoId = $primeiraDespesa;
        }
        \App\Models\ContaPagar::create([
            'tenant_id' => auth()->user()->tenant_id ?? null,
            'plano_conta_id' => $planoContaEstornoId ? (int) $planoContaEstornoId : null,
            'descricao' => $descricao,
            'numero_documento' => $numeroDocumento,
            'valor' => $valor,
            'valor_original' => $valor,
            'data_vencimento' => now()->toDateString(),
            'tipo' => 'outro',
            'status' => 'aberto',
            'observacoes' => $observacoes,
            'created_by' => $userId,
        ]);
    }

    /**
     * @param 'total'|'parcial' $modo
     * @param array<int, array{descricao: string, quantidade_cancelada: float, tipo: string}> $linhasParciais
     */
    private function montarObservacoesContaPagarCancelamento(Venda $v, float $valorEstorno, string $modo = 'total', array $linhasParciais = [], ?string $motivoOperacao = null): string
    {
        $linhas = [];
        $titulo = $modo === 'parcial' ? '--- ESTORNO PARCIAL DE VENDA PDV ---' : '--- ESTORNO DE VENDA PDV CANCELADA ---';
        $linhas[] = $titulo;
        $linhas[] = 'ID da venda: #' . $v->id;
        $linhas[] = 'Data da venda: ' . ($v->created_at ? $v->created_at->format('d/m/Y H:i') : '—');
        $linhas[] = 'Data/hora do lançamento: ' . ($v->canceled_at ? $v->canceled_at->format('d/m/Y H:i') : now()->format('d/m/Y H:i'));
        $linhas[] = '';
        $linhas[] = 'Valor deste estorno: R$ ' . number_format($valorEstorno, 2, ',', '.');
        $linhas[] = '';
        $linhas[] = 'Cliente: ' . ($v->cliente ? ($v->cliente->nome ?? $v->cliente->razao_social ?? 'Cliente #' . $v->cliente_id) : 'Balcão (sem cliente)');
        if ($v->cliente_id) {
            $linhas[] = 'ID do cliente: ' . $v->cliente_id;
        }
        $linhas[] = '';
        $linhas[] = 'Total atual da venda (após operação): R$ ' . number_format((float) $v->total_final, 2, ',', '.');
        $linhas[] = 'Subtotal atual (itens): R$ ' . number_format((float) $v->total, 2, ',', '.');
        if ((float) $v->desconto > 0) {
            $linhas[] = 'Desconto proporcional atual: R$ ' . number_format((float) $v->desconto, 2, ',', '.');
        }
        if ($modo === 'parcial' && $linhasParciais !== []) {
            $linhas[] = '';
            $linhas[] = '--- ITENS NESTE CANCELAMENTO PARCIAL ---';
            foreach ($linhasParciais as $lr) {
                $linhas[] = '  • ' . ($lr['descricao'] ?? 'Item') . ' [' . ($lr['tipo'] ?? '') . '] — Qtd cancelada: ' . number_format((float) ($lr['quantidade_cancelada'] ?? 0), 4, ',', '.');
            }
        }
        $linhas[] = '';
        $motivoTxt = $motivoOperacao !== null && $motivoOperacao !== '' ? $motivoOperacao : ($v->motivo_cancelamento ?: '(não informado)');
        $linhas[] = 'Motivo do cancelamento: ' . $motivoTxt;
        $linhas[] = 'Operador/registro: ' . ($v->canceladoPorUser?->name ?? auth()->user()?->name ?? '—');
        if ($v->cancelamento_autorizado_por_user_id && $v->cancelamentoAutorizadoPorUser) {
            $linhas[] = 'Autorizado por (senha): ' . $v->cancelamentoAutorizadoPorUser->name;
        }
        $linhas[] = '';
        $linhas[] = 'Caixa: ' . ($v->caixa ? $v->caixa->numero_caixa : '—') . ' | Operador: ' . ($v->caixa && $v->caixa->user ? $v->caixa->user->name : '—');
        $linhas[] = '';
        $linhas[] = '--- FORMAS DE PAGAMENTO DA VENDA (para referência do estorno) ---';
        foreach ($v->pagamentos ?? [] as $pag) {
            $formaNome = $pag->formaPagamento ? $pag->formaPagamento->nome : 'Forma #' . $pag->forma_pagamento_id;
            $linhas[] = '  • ' . $formaNome . ': R$ ' . number_format((float) $pag->valor, 2, ',', '.');
            $parcelas = (int) ($pag->parcelas ?? 1);
            if ($parcelas > 1) {
                $linhas[] = '    Parcelas: ' . $parcelas . 'x';
            }
            if ($pag->adquirente_id) {
                $adqNome = $pag->adquirente ? $pag->adquirente->nome : 'Adquirente #' . $pag->adquirente_id;
                $linhas[] = '    Adquirente: ' . $adqNome;
            }
            if (!empty($pag->bandeira)) {
                $linhas[] = '    Bandeira: ' . $pag->bandeira;
            }
            $conta = $pag->contaBancaria ?? $pag->formaPagamento->contaBancaria ?? null;
            if ($conta) {
                $linhas[] = '    Conta (onde caiu o dinheiro): ' . $conta->nome;
            }
            $forma = $pag->formaPagamento;
            if ($forma && ((float) ($forma->taxa_percentual ?? 0) !== 0 || (float) ($forma->taxa_fixa ?? 0) !== 0)) {
                $taxaValor = $forma->calcularTaxa((float) $pag->valor);
                $linhas[] = '    Taxa/desconto da forma de pagamento: R$ ' . number_format($taxaValor, 2, ',', '.');
                $linhas[] = '      (Forma: ' . number_format((float) ($forma->taxa_percentual ?? 0), 2, ',', '') . '% + R$ ' . number_format((float) ($forma->taxa_fixa ?? 0), 2, ',', '.') . ' fixo)';
            }
            $linhas[] = '';
        }
        $linhas[] = 'O operador do financeiro deve definir a melhor forma de estorno (devolução ao cliente, ajuste em caixa, etc.) e dar baixa neste título.';
        return implode("\n", $linhas);
    }

    /**
     * API JSON para o modal Histórico de Vendas no PDV (filtros + paginação).
     */
    public function historicoVendasJson(Request $request)
    {
        $clientesParaFiltro = \App\Models\Cliente::whereIn('id',
            Venda::where('tipo', Venda::TIPO_VENDA)
                ->whereHas('caixa', fn ($q) => $q->where('user_id', auth()->id()))
                ->whereNotNull('cliente_id')
                ->distinct()
                ->pluck('cliente_id')
        )->orderByRaw('COALESCE(nome, razao_social)')->get(['id', 'nome', 'razao_social']);

        $vendasQuery = Venda::with(['cliente', 'caixa.user'])
            ->where('tipo', Venda::TIPO_VENDA)
            ->whereHas('caixa', function ($q) {
                $q->where('user_id', auth()->id());
            })
            ->orderByDesc('created_at');

        if ($request->filled('data_de')) {
            $vendasQuery->whereDate('created_at', '>=', $request->data_de);
        }
        if ($request->filled('data_ate')) {
            $vendasQuery->whereDate('created_at', '<=', $request->data_ate);
        }
        if ($request->filled('status') && $request->status !== '') {
            $vendasQuery->where('status', $request->status);
        }
        if ($request->filled('cliente_id')) {
            $vendasQuery->where('cliente_id', $request->cliente_id);
        }
        if ($request->filled('numero_venda') && is_numeric($request->numero_venda)) {
            $vendasQuery->where('id', (int) $request->numero_venda);
        }
        $q = trim((string) $request->get('q', ''));
        if ($q !== '') {
            if (is_numeric($q)) {
                $vendasQuery->where('id', (int) $q);
            } else {
                $termo = '%' . $q . '%';
                $vendasQuery->whereHas('cliente', function ($qb) use ($termo) {
                    $qb->where('nome', 'like', $termo)->orWhere('razao_social', 'like', $termo);
                });
            }
        }

        $perPage = (int) $request->get('per_page', 15);
        $perPage = max(5, min(50, $perPage));
        $paginator = $vendasQuery->paginate($perPage, ['*'], 'page');

        $meses = ['Jan', 'Fev', 'Mar', 'Abr', 'Mai', 'Jun', 'Jul', 'Ago', 'Set', 'Out', 'Nov', 'Dez'];
        $vendas = $paginator->getCollection()->map(function ($v) use ($meses) {
            $created = $v->created_at;
            $createdAtCard = $created
                ? $created->format('d') . ' ' . strtoupper($meses[(int) $created->format('n') - 1]) . ' ' . $created->format('Y') . ' • ' . $created->format('H:i')
                : '—';
            return [
                'id' => $v->id,
                'numero_formatado' => str_pad($v->id, 5, '0', STR_PAD_LEFT),
                'created_at' => $v->created_at?->format('d/m/Y H:i'),
                'created_at_card' => $createdAtCard,
                'cliente_nome' => $v->cliente ? ($v->cliente->nome ?? $v->cliente->razao_social ?? 'Consumidor Final') : 'Consumidor Final',
                'total_final' => (float) $v->total_final,
                'total_formatado' => 'R$ ' . number_format($v->total_final, 2, ',', '.'),
                'status' => $v->status,
                'operador_nome' => $v->caixa?->user?->name ?? '—',
            ];
        })->values()->all();

        return response()->json([
            'vendas' => $vendas,
            'clientes' => $clientesParaFiltro->map(fn ($c) => [
                'id' => $c->id,
                'nome' => $c->nome ?? $c->razao_social ?? '#' . $c->id,
            ])->values()->all(),
            'pagination' => [
                'current_page' => $paginator->currentPage(),
                'last_page' => $paginator->lastPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
                'path' => $paginator->path(),
            ],
        ]);
    }

    public function orcamentosList(Request $request)
    {
        $q = Orcamento::with('itens')->orderByDesc('created_at');
        $busca = $request->input('q');
        if ($busca !== null && trim((string) $busca) !== '') {
            $termo = '%' . trim($busca) . '%';
            $q->where(function ($w) use ($termo, $busca) {
                $w->where('nome_cliente', 'like', $termo)
                    ->orWhere('id', 'like', $termo);
            });
        }
        $lista = $q->limit(50)->get();
        return response()->json(['orcamentos' => $lista]);
    }

    public function orcamentoStore(Request $request)
    {
        $caixa = $this->getCaixaAberto(null);
        $request->validate([
            'cliente_id' => 'nullable|exists:clientes,id',
            'nome_cliente' => 'nullable|string|max:255',
            'observacao' => 'nullable|string|max:5000',
            'observacao_interna' => 'nullable|string|max:5000',
            'itens' => 'required|array|min:1',
            'itens.*.tipo' => 'required|in:produto,servico',
            'itens.*.quantidade' => 'required|numeric|min:0.001',
            'itens.*.preco_unitario' => 'required|numeric|min:0',
            'itens.*.descricao' => 'required|string',
            'itens.*.kit_componentes' => 'nullable|array',
            'itens.*.kit_componentes.*' => 'nullable|string',
            'itens.*.observacao' => 'nullable|string|max:255',
        ]);
        $total = 0;
        foreach ($request->itens as $item) {
            $total += (float) $item['quantidade'] * (float) $item['preco_unitario'];
        }
        $orc = Orcamento::create([
            'caixa_id' => $caixa?->id,
            'cliente_id' => $request->cliente_id,
            'nome_cliente' => $request->nome_cliente,
            'total' => $total,
            'status' => Orcamento::STATUS_RASCUNHO,
            'observacao' => $request->observacao ?: null,
            'observacao_interna' => $request->observacao_interna ?: null,
        ]);
        foreach ($request->itens as $item) {
            $kitComp = $item['kit_componentes'] ?? null;
            $kitComponentes = is_array($kitComp) ? array_values(array_filter(array_map('strval', $kitComp))) : null;
            OrcamentoItem::create([
                'orcamento_id' => $orc->id,
                'tipo' => $item['tipo'],
                'produto_id' => $item['produto_id'] ?? null,
                'servico_id' => $item['servico_id'] ?? null,
                'descricao' => $item['descricao'],
                'quantidade' => (float) $item['quantidade'],
                'preco_unitario' => (float) $item['preco_unitario'],
                'total' => (float) $item['quantidade'] * (float) $item['preco_unitario'],
                'serial' => $item['serial'] ?? null,
                'kit_componentes' => !empty($kitComponentes) ? $kitComponentes : null,
                'observacao' => isset($item['observacao']) && trim((string) $item['observacao']) !== '' ? trim((string) $item['observacao']) : null,
            ]);
        }
        return response()->json(['orcamento' => $orc->load('itens')]);
    }

    public function orcamentoShow(Request $request, $orcamento)
    {
        $o = Orcamento::with('itens', 'cliente')->find($orcamento);
        if (!$o) {
            return response()->json(['message' => 'Orçamento não encontrado.'], 404);
        }
        return response()->json(['orcamento' => $o]);
    }

    public function orcamentoImportarParaVenda(Request $request, $orcamento)
    {
        $o = Orcamento::with('itens')->find($orcamento);
        if (!$o) {
            return response()->json(['message' => 'Orçamento não encontrado.'], 404);
        }
        $caixa = $this->getCaixaAberto(null);
        if (!$caixa) {
            return response()->json(['message' => 'Abra o caixa antes de importar.'], 422);
        }
        $produtoIds = $o->itens->where('tipo', 'produto')->pluck('produto_id')->filter()->unique()->values()->all();
        $produtoImagens = [];
        if (!empty($produtoIds)) {
            $produtos = \App\Models\Produto::with(['imagens' => fn ($q) => $q->orderBy('ordem')])
                ->whereIn('id', $produtoIds)
                ->get(['id']);
            foreach ($produtos as $p) {
                $imagens = [];
                foreach ($p->imagens ?? [] as $img) {
                    if (!empty($img->url_externa)) {
                        $imagens[] = $img->url_externa;
                    } elseif (!empty($img->caminho_local)) {
                        $path = ltrim($img->caminho_local, '/');
                        $imagens[] = route('plugin.pdv.api.imagem', [], true) . '?path=' . rawurlencode($path);
                    }
                }
                $produtoImagens[(string) $p->id] = $imagens;
            }
        }
        return response()->json([
            'itens' => $o->itens->map(function ($i) use ($produtoImagens) {
                $arr = [
                    'tipo' => $i->tipo,
                    'produto_id' => $i->produto_id,
                    'servico_id' => $i->servico_id,
                    'descricao' => $i->descricao,
                    'quantidade' => (float) $i->quantidade,
                    'preco_unitario' => (float) $i->preco_unitario,
                    'serial' => $i->serial,
                    'kit_componentes' => is_array($i->kit_componentes ?? null) ? $i->kit_componentes : [],
                    'observacao' => isset($i->observacao) && trim((string) $i->observacao) !== '' ? trim((string) $i->observacao) : '',
                ];
                if ($i->tipo === 'produto' && $i->produto_id) {
                    $arr['imagens'] = $produtoImagens[(string) $i->produto_id] ?? [];
                } else {
                    $arr['imagens'] = [];
                }
                return $arr;
            }),
            'cliente_id' => $o->cliente_id,
            'nome_cliente' => $o->nome_cliente ?? ($o->cliente ? ($o->cliente->nome ?? $o->cliente->razao_social) : null),
            'observacao' => $o->observacao,
            'observacao_interna' => $o->observacao_interna,
        ]);
    }

    private function getCaixaAberto(?string $numeroCaixa = null): ?Caixa
    {
        $q = Caixa::where('status', Caixa::STATUS_ABERTO);
        if ($numeroCaixa !== null && $numeroCaixa !== '') {
            $q->where('numero_caixa', $numeroCaixa);
        } else {
            $q->where('user_id', auth()->id());
        }
        return $q->first();
    }

    /**
     * Dados para impressão/PDF do orçamento (reutilizado por orcamentoPrint e orcamentoPdf).
     * POST JSON: itens[], clienteNome, totalGeral, observacao?, numeroOrcamento?, cliente_id?
     */
    private function orcamentoPrintData(Request $request): array
    {
        $empresa = null;
        if (class_exists(\App\Models\Empresa::class)) {
            try {
                $empresa = \App\Models\Empresa::first();
            } catch (\Throwable $e) {
                $empresa = null;
            }
        }
        $itens = $request->input('itens', []);
        if (!is_array($itens)) {
            $itens = [];
        }
        $itens = array_map(function ($i) {
            $qtd = (float) ($i['quantidade'] ?? 0);
            $preco = (float) ($i['preco_unitario'] ?? 0);
            $kitComp = $i['kit_componentes'] ?? null;
            $kitComponentes = is_array($kitComp) ? $kitComp : [];
            $obs = isset($i['observacao']) && trim((string) $i['observacao']) !== '' ? trim((string) $i['observacao']) : null;
            $tipo = isset($i['tipo']) && $i['tipo'] === 'servico' ? 'servico' : 'produto';
            return [
                'tipo' => $tipo,
                'descricao' => $i['descricao'] ?? '-',
                'quantidade' => $qtd,
                'preco_unitario' => $preco,
                'total' => $qtd * $preco,
                'kit_componentes' => $kitComponentes,
                'observacao' => $obs,
            ];
        }, $itens);
        $numeroOrcamento = $request->input('numeroOrcamento') ?? $request->input('numero_orcamento');
        if ($numeroOrcamento !== null && $numeroOrcamento !== '') {
            $numeroOrcamento = (string) $numeroOrcamento;
        } else {
            $numeroOrcamento = null;
        }
        $clienteNome = $request->input('clienteNome', 'Cliente balcão');
        $clienteId = $request->input('cliente_id');
        $clienteDados = null;
        try {
            if ($clienteId && class_exists(\App\Models\Cliente::class)) {
                $cliente = \App\Models\Cliente::with(['enderecos', 'contatos'])->find($clienteId);
                if ($cliente) {
                    $endereco = $cliente->enderecos->firstWhere('principal', true) ?? $cliente->enderecos->first();
                    $enderecoTexto = $endereco ? trim($endereco->logradouro . ' ' . $endereco->numero . ($endereco->complemento ? ' - ' . $endereco->complemento : '') . ', ' . $endereco->bairro . ' - ' . $endereco->cidade . '/' . $endereco->estado) : '';
                    if ($endereco && !empty($endereco->cep)) {
                        $enderecoTexto .= ($enderecoTexto ? '. ' : '') . 'CEP: ' . $endereco->cep;
                    }
                    $contato = $cliente->contatos->firstWhere('principal', true) ?? $cliente->contatos->first();
                    $clienteDados = [
                        'nome' => $cliente->nome ?? $cliente->razao_social ?? $clienteNome,
                        'documento' => $cliente->documento_principal ?? $cliente->cpf ?? $cliente->cnpj ?? $cliente->documento_estrangeiro ?? null,
                        'endereco' => $enderecoTexto ?: null,
                        'telefone' => $contato ? ($contato->telefone ?? $contato->telefone2 ?? null) : null,
                        'email' => $contato ? ($contato->email ?? $contato->email2 ?? null) : null,
                    ];
                }
            }
        } catch (\Throwable $e) {
            $clienteDados = null;
        }
        $empresaLogoUrl = null;
        $empresaLogoBase64 = null;
        if ($empresa?->logo_path) {
            $logoPath = ltrim(str_replace('\\', '/', (string) $empresa->logo_path), '/');
            if ($logoPath !== '') {
                $empresaLogoUrl = route('plugin.pdv.api.imagem', [], true) . '?path=' . rawurlencode($logoPath);
                if (Storage::disk('public')->exists($logoPath)) {
                    $mime = Storage::disk('public')->mimeType($logoPath) ?: 'image/png';
                    $empresaLogoBase64 = 'data:' . $mime . ';base64,' . base64_encode(Storage::disk('public')->get($logoPath));
                }
            }
        }
        $totalGeral = (float) $request->input('totalGeral', 0);
        $desconto = (float) $request->input('desconto', 0);
        return [
            'itens' => $itens,
            'clienteNome' => ($clienteDados !== null && isset($clienteDados['nome'])) ? $clienteDados['nome'] : $clienteNome,
            'clienteDados' => $clienteDados,
            'totalGeral' => $totalGeral,
            'desconto' => $desconto,
            'observacao' => (string) $request->input('observacao', ''),
            'dataEmissao' => now()->format('d/m/Y H:i'),
            'numeroOrcamento' => $numeroOrcamento,
            'empresa' => $empresa,
            'empresaLogoUrl' => $empresaLogoUrl,
            'empresaLogoBase64' => $empresaLogoBase64,
        ];
    }

    /**
     * Gera PDF do orçamento e abre no navegador (stream inline). O usuário pode imprimir pelo navegador.
     * POST JSON: itens[], clienteNome, totalGeral, desconto?, observacao?, numeroOrcamento?, cliente_id?
     */
    public function orcamentoPdf(Request $request)
    {
        $data = $this->orcamentoPrintData($request);
        $html = view('pdv::orcamento-print', $data)->render();
        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadHTML($html);
        $pdf->setPaper('A4', 'portrait');
        $pdf->setOption('margin-top', '6mm');
        $pdf->setOption('margin-bottom', '10mm');
        $pdf->setOption('margin-left', '10mm');
        $pdf->setOption('margin-right', '10mm');
        $numero = $data['numeroOrcamento'] ?? 'orcamento';
        $filename = 'orcamento-' . preg_replace('/[^a-z0-9]/i', '-', (string) $numero) . '.pdf';
        return $pdf->stream($filename);
    }

    /**
     * Cria contas a receber e movimentações financeiras (já conciliadas) para cada pagamento da venda PDV.
     * Inclui nas observações dados para rastreamento: origem venda PDV, forma de pagamento, taxas, adquirente, bandeira, parcelas.
     */
    protected function criarFinanceiroPdvVenda(Venda $venda): void
    {
        $venda->load(['pagamentos.formaPagamento', 'caixa']);
        $clienteId = $venda->cliente_id ? (int) $venda->cliente_id : null;
        if (!$clienteId && class_exists(Cliente::class)) {
            // 1) Cliente balcão configurado nas opções do PDV
            $idBalcao = get_option('pdv_cliente_balcao_id', null, 'pdv');
            if ($idBalcao && Cliente::where('id', (int) $idBalcao)->exists()) {
                $clienteId = (int) $idBalcao;
            }
            // 2) Sem config ou cliente inexistente: buscar ou criar "Cliente Balcão" (não depende de opção salva)
            if (!$clienteId) {
                $balcao = Cliente::where('nome', 'Cliente Balcão')->orderBy('id')->first();
                if (!$balcao) {
                    try {
                        $balcao = Cliente::create([
                            'tipo_pessoa' => 'F',
                            'pais_origem' => 'Brasil',
                            'nome' => 'Cliente Balcão',
                            'bloqueado_vendas' => false,
                        ]);
                    } catch (\Throwable $e) {
                        Log::warning('PDV: Não foi possível criar Cliente Balcão: ' . $e->getMessage());
                        $balcao = null;
                    }
                }
                if ($balcao) {
                    $clienteId = $balcao->id;
                    if (function_exists('update_option')) {
                        update_option('pdv_cliente_balcao_id', $clienteId, 'pdv');
                    }
                }
            }
            // 3) Último recurso: primeiro cliente cadastrado (contas_receber exige cliente_id não nulo)
            if (!$clienteId) {
                $primeiro = Cliente::orderBy('id')->first();
                $clienteId = $primeiro?->id;
            }
        }
        if (!$clienteId) {
            Log::warning('PDV: Venda #' . $venda->id . ' sem cliente. Cadastre ao menos um cliente no sistema para gerar lançamentos no financeiro.');
            return;
        }

        $planoContaReceita = PlanoConta::where('codigo', '1.1')->orWhere('nome', 'Receita de Serviços')->first()?->id;
        $numeroCaixa = $venda->caixa ? ($venda->caixa->numero_caixa ?? '') : '';

        $rows = [];
        foreach ($venda->pagamentos as $pagamento) {
            if (! $pagamento->forma_pagamento_id) {
                continue;
            }
            $rows[] = [
                'forma_pagamento_id' => $pagamento->forma_pagamento_id,
                'valor' => (float) $pagamento->valor,
                'parcelas' => (int) ($pagamento->parcelas ?? 1),
                'conta_bancaria_id' => $pagamento->conta_bancaria_id,
                'adquirente_id' => $pagamento->adquirente_id,
                'bandeira' => $pagamento->bandeira,
                'antecipado' => $pagamento->antecipado,
                'pix_chave_id' => $pagamento->pix_chave_id,
                'pix_parcela_vencimentos' => is_array($pagamento->pix_parcela_vencimentos ?? null)
                    ? $pagamento->pix_parcela_vencimentos
                    : [],
            ];
        }

        if ($rows === []) {
            return;
        }

        $dataRef = $venda->created_at ? \Carbon\Carbon::parse($venda->created_at) : now();

        app(FinanceiroVendaPagamentosService::class)->processar($clienteId, $rows, [
            'plano_conta_id' => $planoContaReceita,
            'centro_custo_id' => null,
            'entidade_tipo' => null,
            'entidade_id' => null,
            'data_referencia' => $dataRef,
            'prefixo_descricao' => 'Venda PDV #' . $venda->id,
            'baixa_label' => 'Baixa automática PDV',
            'origem_tipo' => 'venda_pdv',
            'tenant_id' => auth()->user()->tenant_id ?? null,
            'origem_obs_fn' => function ($forma) use ($venda, $numeroCaixa) {
                return sprintf('Origem: Venda PDV #%s | Caixa: %s | Forma: %s', $venda->id, $numeroCaixa, $forma->nome);
            },
        ]);
    }
}
