<?php

/**
 * @copyright 2024-2026 LivreOS
 * @license   https://www.gnu.org/licenses/agpl-3.0.txt AGPL-3.0
 */
namespace Compras;

use App\Models\Cliente;
use App\Models\ContaPagar;
use App\Models\ContaPagarAnexo;
use App\Models\FormaPagamento;
use App\Models\ContaBancaria;
use App\Models\PlanoConta;
use App\Models\Produto;
use Compras\Models\CompraAuditLog;
use Compras\Models\CompraImportacao;
use Compras\Models\CompraImportacaoItem;
use Compras\Models\CompraProdutoFornecedorMap;
use Compras\Services\CompraPdfExtratorService;
use Compras\Services\ComprasFornecedorService;
use Compras\Services\NfeSefazConsultaService;
use Compras\Services\CompraImportacaoDesfazerService;
use Compras\Services\NfeXmlParserService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ComprasController
{
    public function index()
    {
        $lista = CompraImportacao::query()
            ->with('user')
            ->orderByDesc('id')
            ->paginate(20);

        return view('compras::index', [
            'title' => 'Compras — Importação NF-e',
            'importacoes' => $lista,
        ]);
    }

    public function upload(Request $request)
    {
        $request->validate([
            'arquivo' => 'required|file|max:15360',
        ]);

        $file = $request->file('arquivo');
        $ext = strtolower($file->getClientOriginalExtension());
        if (! in_array($ext, ['xml', 'pdf'], true)) {
            return redirect()->route('plugin.compras.index')->with('error', 'Envie um arquivo .xml (NF-e) ou .pdf.');
        }

        if ($ext === 'xml') {
            return $this->processarXml($request, $file);
        }

        return $this->processarPdf($request, $file);
    }

    private function processarXml(Request $request, \Illuminate\Http\UploadedFile $file)
    {
        $conteudo = file_get_contents($file->getRealPath());
        $parser = new NfeXmlParserService;
        $dados = $parser->parse($conteudo);
        if (! ($dados['ok'] ?? false)) {
            return redirect()
                ->route('plugin.compras.index')
                ->with('error', $dados['erro'] ?? 'Falha ao interpretar XML.');
        }

        $path = $file->store('plugin-compras/xml', 'local');
        $cnpj = ComprasFornecedorService::normalizarCnpj($dados['emitente']['cnpj'] ?? '');
        $fornecedor = (new ComprasFornecedorService)->buscarPorCnpj($cnpj);

        $sefaz = (new NfeSefazConsultaService)->consultarChave($dados['chave_acesso'] ?? null);

        $imp = CompraImportacao::create([
            'user_id' => auth()->id(),
            'tenant_id' => auth()->user()->tenant_id ?? null,
            'status' => 'rascunho',
            'tipo_fonte' => 'xml',
            'arquivo_original_path' => $path,
            'chave_acesso' => $dados['chave_acesso'],
            'numero' => $dados['numero'] ?? null,
            'serie' => $dados['serie'] ?? null,
            'data_emissao' => $dados['data_emissao'] ?? null,
            'fornecedor_cnpj' => $cnpj,
            'fornecedor_nome' => $dados['emitente']['xnome'] ?? '',
            'fornecedor_id' => $fornecedor?->id,
            'totais' => $dados['totais'] ?? [],
            'emitente' => $dados['emitente'] ?? [],
            'destinatario' => $dados['destinatario'] ?? [],
            'cobranca' => ['duplicatas' => $dados['duplicatas'] ?? []],
            'sefaz_resultado' => $sefaz,
            'parse_warnings' => array_merge($dados['warnings'] ?? [], $sefaz['ok'] ? [] : [$sefaz['mensagem'] ?? '']),
        ]);

        $this->criarItensImportacao($imp, $dados['itens'] ?? []);
        $this->aplicarMapsSalvos($imp);
        $this->audit($imp, 'upload_xml', ['arquivo' => $file->getClientOriginalName()]);

        $imp->status = $this->statusPorMapeamento($imp);
        $imp->save();

        return redirect()
            ->route('plugin.compras.revisar', $imp)
            ->with('success', 'NF-e importada. Revise o de-para e as parcelas antes de confirmar.');
    }

    private function processarPdf(Request $request, \Illuminate\Http\UploadedFile $file)
    {
        $path = $file->store('plugin-compras/pdf', 'local');
        $full = Storage::disk('local')->path($path);
        $extrator = new CompraPdfExtratorService;
        $dados = $extrator->extrair($full);

        if (! ($dados['ok'] ?? false)) {
            if ($path) {
                Storage::disk('local')->delete($path);
            }

            return redirect()
                ->route('plugin.compras.index')
                ->with('error', $dados['erro'] ?? 'Não foi possível processar o PDF.');
        }

        $imp = CompraImportacao::create([
            'user_id' => auth()->id(),
            'tenant_id' => auth()->user()->tenant_id ?? null,
            'status' => 'rascunho_pdf',
            'tipo_fonte' => 'pdf',
            'arquivo_original_path' => $path,
            'parse_warnings' => array_filter([
                $dados['aviso'] ?? 'Importação PDF: conferência manual obrigatória.',
            ]),
            'totais' => $dados['campos'] ?? [],
        ]);

        $this->audit($imp, 'upload_pdf', ['arquivo' => $file->getClientOriginalName(), 'extracao' => $dados]);

        return redirect()
            ->route('plugin.compras.revisar', $imp)
            ->with('warning', 'PDF processado com extração parcial. XML da NF-e é recomendado para automação completa.');
    }

    private function criarItensImportacao(CompraImportacao $imp, array $itens): void
    {
        foreach ($itens as $row) {
            $dadosNf = array_filter([
                'u_com' => $row['u_com'] ?? null,
                'ean_tributario' => $row['ean_tributario'] ?? null,
                'cest' => $row['cest'] ?? null,
                'ext_ipi' => $row['ext_ipi'] ?? null,
                'orig_icms' => $row['orig_icms'] ?? null,
                'peso_liquido' => $row['peso_liquido'] ?? null,
                'peso_bruto' => $row['peso_bruto'] ?? null,
            ], static function ($v) {
                if ($v === null || $v === '') {
                    return false;
                }
                if (is_float($v) && $v <= 0) {
                    return false;
                }

                return true;
            });

            CompraImportacaoItem::create([
                'compra_importacao_id' => $imp->id,
                'indice' => $row['indice'] ?? 0,
                'c_prod' => $row['c_prod'] ?? '',
                'x_prod' => $row['x_prod'] ?? '',
                'ncm' => $row['ncm'] ?? '',
                'cfop' => $row['cfop'] ?? '',
                'cst_icms' => $row['cst_icms'] ?? null,
                'ean' => $row['ean'] ?? '',
                'q_com' => $row['q_com'] ?? 0,
                'v_un_com' => $row['v_un_com'] ?? 0,
                'v_prod' => $row['v_prod'] ?? 0,
                'impostos' => $row['impostos'] ?? [],
                'dados_nf' => $dadosNf !== [] ? $dadosNf : null,
                'produto_id' => null,
                'atualizar_estoque' => true,
            ]);
        }
    }

    private function aplicarMapsSalvos(CompraImportacao $imp): void
    {
        $cnpj = $imp->fornecedor_cnpj;
        if ($cnpj === null || $cnpj === '') {
            return;
        }
        foreach ($imp->itens as $item) {
            $cod = trim((string) $item->c_prod);
            if ($cod === '') {
                continue;
            }
            $map = CompraProdutoFornecedorMap::query()
                ->where('fornecedor_cnpj', $cnpj)
                ->where('codigo_fornecedor', $cod)
                ->first();
            if ($map) {
                $item->produto_id = $map->produto_id;
                $item->save();
            }
        }
    }

    private function statusPorMapeamento(CompraImportacao $imp): string
    {
        if ($imp->tipo_fonte === 'pdf' && $imp->status === 'rascunho_pdf') {
            return 'rascunho_pdf';
        }
        foreach ($imp->itens as $item) {
            if ($item->produto_id === null && $item->atualizar_estoque) {
                return 'aguardando_mapeamento';
            }
        }

        return 'pronta';
    }

    public function revisar(CompraImportacao $compra_importacao)
    {
        $importacao = $compra_importacao;
        $this->autorizarImportacao($importacao);
        $importacao->load(['itens.produto', 'fornecedor']);

        $produtos = Produto::query()
            ->orderBy('nome')
            ->limit(800)
            ->get(['id', 'nome', 'codigo_sku', 'ean']);

        $formas = FormaPagamento::where('ativo', true)->orderBy('nome')->get(['id', 'nome']);
        $contasBancarias = ContaBancaria::where('ativo', true)->orderBy('nome')->get(['id', 'nome']);
        $planos = PlanoConta::where('tipo', 'despesa')->where('ativo', true)
            ->orderByRaw('COALESCE(codigo, "")')->orderBy('nome')
            ->get(['id', 'codigo', 'nome']);

        $dup = $importacao->cobranca['duplicatas'] ?? [];
        if (count($dup) === 0 && ($importacao->totais['vNF'] ?? 0) > 0) {
            $vencFallback = $importacao->data_emissao
                ? $importacao->data_emissao->copy()->addDays(30)->format('Y-m-d')
                : now()->addDays(30)->format('Y-m-d');
            $dup = [[
                'nDup' => '001',
                'dVenc' => $vencFallback,
                'vDup' => (float) $importacao->totais['vNF'],
            ]];
        }

        return view('compras::revisar', [
            'title' => 'Conferir importação #' . $importacao->id,
            'imp' => $importacao,
            'duplicatas' => $dup,
            'produtos' => $produtos,
            'formas' => $formas,
            'contasBancarias' => $contasBancarias,
            'planos' => $planos,
        ]);
    }

    public function salvarMapeamento(Request $request, CompraImportacao $compra_importacao)
    {
        $importacao = $compra_importacao;
        $this->autorizarImportacao($importacao);
        if (in_array($importacao->status, ['confirmada'], true)) {
            return redirect()->back()->with('error', 'Importação já confirmada.');
        }

        $request->validate([
            'item' => 'required|array',
            'item.*.produto_id' => 'nullable|integer|exists:produtos,id',
            'item.*.atualizar_estoque' => 'nullable|boolean',
            'criar_produtos_novos' => 'nullable|boolean',
        ]);

        $cnpj = $importacao->fornecedor_cnpj ?? '';
        $criarProdutosNovos = $request->boolean('criar_produtos_novos');
        $userId = (int) auth()->id();

        foreach ($request->input('item', []) as $itemId => $payload) {
            $item = CompraImportacaoItem::where('compra_importacao_id', $importacao->id)->whereKey($itemId)->first();
            if (! $item) {
                continue;
            }
            $pid = ! empty($payload['produto_id']) ? (int) $payload['produto_id'] : null;
            $item->atualizar_estoque = filter_var($payload['atualizar_estoque'] ?? true, FILTER_VALIDATE_BOOLEAN);
            if ($pid === null && $criarProdutosNovos) {
                $criado = $this->criarOuResolverProdutoDoItem($importacao, $item, $userId);
                $pid = $criado?->id;
            }
            $item->produto_id = $pid;
            $item->save();

            if ($pid && $cnpj !== '' && trim((string) $item->c_prod) !== '') {
                CompraProdutoFornecedorMap::updateOrCreate(
                    [
                        'fornecedor_cnpj' => $cnpj,
                        'codigo_fornecedor' => trim((string) $item->c_prod),
                    ],
                    [
                        'produto_id' => $pid,
                        'created_by' => auth()->id(),
                    ]
                );
            }
        }

        $importacao->refresh();
        $importacao->status = $this->statusPorMapeamento($importacao);
        $importacao->save();

        $this->audit($importacao, 'mapeamento', ['payload' => $request->input('item')]);

        return redirect()->back()->with('success', 'De-para salvo.');
    }

    public function confirmar(Request $request, CompraImportacao $compra_importacao)
    {
        $importacao = $compra_importacao;
        $this->autorizarImportacao($importacao);
        if ($importacao->status === 'confirmada') {
            return redirect()->back()->with('error', 'Já confirmada.');
        }

        if ($importacao->tipo_fonte === 'pdf' && $importacao->status === 'rascunho_pdf') {
            return redirect()->back()->with('error', 'Confirmação automática requer dados completos (use XML da NF-e ou complete o cadastro manualmente no financeiro).');
        }

        $request->validate([
            'forma_pagamento_id' => 'required|exists:formas_pagamento,id',
            'plano_conta_id' => 'required|exists:plano_contas,id',
            'conta_bancaria_id' => 'nullable|exists:contas_bancarias,id',
            'criar_fornecedor' => 'nullable|boolean',
        ]);

        foreach ($importacao->itens as $item) {
            if ($item->atualizar_estoque && $item->produto_id === null) {
                return redirect()->back()->with('error', 'Item "' . ($item->x_prod ?: $item->c_prod) . '" sem produto vinculado. Mapeie ou desmarque atualizar estoque.');
            }
        }

        $chave = $importacao->chave_acesso;
        if ($chave && CompraImportacao::where('chave_acesso', $chave)->where('status', 'confirmada')->where('id', '!=', $importacao->id)->exists()) {
            return redirect()->back()->with('error', 'Esta chave de NF-e já foi confirmada anteriormente.');
        }

        $dup = $importacao->cobranca['duplicatas'] ?? [];
        if (count($dup) === 0 && ($importacao->totais['vNF'] ?? 0) > 0) {
            $dup = [[
                'nDup' => '001',
                'dVenc' => $importacao->data_emissao?->format('Y-m-d') ?? now()->format('Y-m-d'),
                'vDup' => (float) $importacao->totais['vNF'],
            ]];
        }
        if (count($dup) === 0) {
            return redirect()->back()->with('error', 'Nenhuma parcela ou valor total encontrado.');
        }

        $fornecedorService = new ComprasFornecedorService;
        $fornecedor = null;
        $fornecedorCriadoNestaConfirmacao = false;
        if ($importacao->fornecedor_id) {
            $fornecedor = Cliente::find($importacao->fornecedor_id);
        }
        if (! $fornecedor && $importacao->fornecedor_cnpj) {
            $fornecedor = $fornecedorService->buscarPorCnpj($importacao->fornecedor_cnpj);
        }
        if (! $fornecedor && $request->boolean('criar_fornecedor') && $importacao->fornecedor_cnpj) {
            $emitente = is_array($importacao->emitente) ? $importacao->emitente : [];
            $fornecedor = $fornecedorService->criarFornecedor(
                $importacao->fornecedor_cnpj,
                $importacao->fornecedor_nome ?? '',
                $emitente['xfant'] ?? null,
                $emitente
            );
            $fornecedorCriadoNestaConfirmacao = true;
            $importacao->fornecedor_id = $fornecedor->id;
            $importacao->save();
        }
        if (! $fornecedor) {
            return redirect()->back()->with('error', 'Fornecedor não encontrado. Marque "Cadastrar fornecedor" ou vincule manualmente.');
        }

        // Fornecedor já existia no upload (mesmo CNPJ): antes não rodava criarFornecedor — dados da NF/API nunca entravam.
        if ($importacao->tipo_fonte === 'xml'
            && ! $fornecedorCriadoNestaConfirmacao
            && is_array($importacao->emitente)
            && $importacao->emitente !== []
            && $importacao->fornecedor_cnpj) {
            try {
                $fornecedorService->enriquecerFornecedorComDadosNfe($fornecedor, $importacao->emitente, $importacao->fornecedor_cnpj);
            } catch (\Throwable $e) {
                \Illuminate\Support\Facades\Log::warning('compras.enriquecer_fornecedor', [
                    'cliente_id' => $fornecedor->id,
                    'importacao_id' => $importacao->id,
                    'erro' => $e->getMessage(),
                ]);
            }
        }

        $tenantId = auth()->user()->tenant_id ?? null;
        $userId = auth()->id();
        $snapshotAntes = [
            'itens' => $importacao->itens->map(fn ($i) => $i->only(['id', 'produto_id', 'q_com', 'v_un_com']))->all(),
        ];

        DB::transaction(function () use ($importacao, $dup, $fornecedor, $request, $tenantId, $userId) {
            $importacao->load('itens');

            $estoqueAntes = [];
            foreach ($importacao->itens as $item) {
                if (! $item->atualizar_estoque || ! $item->produto_id) {
                    continue;
                }
                $produto = Produto::lockForUpdate()->find($item->produto_id);
                if (! $produto) {
                    continue;
                }
                $estoqueAntes[] = [
                    'produto_id' => $produto->id,
                    'q_antes' => (float) ($produto->estoque_quantidade ?? 0),
                    'custo_medio_antes' => (float) ($produto->estoque_preco_custo_unitario ?? 0),
                    'preco_compra_antes' => (float) ($produto->estoque_preco_compra_unitario ?? 0),
                ];
            }

            $produtosCriadosPluginIds = Produto::query()
                ->where('codigo_sku', 'like', 'NF-IMP'.$importacao->id.'-%')
                ->pluck('id')
                ->all();

            $contaIds = [];
            $n = 0;
            foreach ($dup as $parcela) {
                $n++;
                $venc = $parcela['dVenc'] ?? null;
                $valor = (float) ($parcela['vDup'] ?? 0);
                if (! $venc || $valor <= 0) {
                    continue;
                }
                try {
                    $vencDate = \Carbon\Carbon::parse($venc)->toDateString();
                } catch (\Throwable) {
                    continue;
                }
                $conta = ContaPagar::create([
                    'tenant_id' => $tenantId,
                    'fornecedor_id' => $fornecedor->id,
                    'origem_tipo' => 'plugin_compras_nf',
                    'entidade_tipo' => 'compra_importacao',
                    'entidade_id' => $importacao->id,
                    'plano_conta_id' => (int) $request->plano_conta_id,
                    'descricao' => sprintf(
                        'NF-e %s/%s — Parcela %s (%s)',
                        $importacao->numero ?? '-',
                        $importacao->serie ?? '-',
                        $parcela['nDup'] ?? (string) $n,
                        $importacao->chave_acesso ? substr($importacao->chave_acesso, -8) : 's/chave'
                    ),
                    'numero_documento' => $importacao->chave_acesso ?? ($importacao->numero . '/' . $importacao->serie),
                    'valor' => $valor,
                    'valor_original' => $valor,
                    'data_vencimento' => $vencDate,
                    'forma_pagamento_id' => (int) $request->forma_pagamento_id,
                    'conta_bancaria_id' => $request->conta_bancaria_id ? (int) $request->conta_bancaria_id : null,
                    'tipo' => 'nf_compra',
                    'status' => 'aberto',
                    'metadata' => [
                        'plugin' => 'compras',
                        'compra_importacao_id' => $importacao->id,
                        'parcela' => $parcela,
                    ],
                    'created_by' => $userId,
                ]);
                $contaIds[] = $conta->id;
                $this->anexarXmlNfeNaContaPagar($conta, $importacao, $userId);
            }

            foreach ($importacao->itens as $item) {
                if (! $item->atualizar_estoque || ! $item->produto_id) {
                    continue;
                }
                $produto = Produto::lockForUpdate()->find($item->produto_id);
                if (! $produto) {
                    continue;
                }
                $qAnt = (float) ($produto->estoque_quantidade ?? 0);
                $cAnt = (float) ($produto->estoque_preco_custo_unitario ?? 0);
                $qNov = (float) $item->q_com;
                $cNov = (float) $item->v_un_com;
                $den = $qAnt + $qNov;
                $custoMedio = $den > 0 ? (($qAnt * $cAnt) + ($qNov * $cNov)) / $den : $cNov;
                $produto->estoque_quantidade = $den;
                $produto->estoque_preco_custo_unitario = round($custoMedio, 2);
                $produto->estoque_preco_compra_unitario = round($cNov, 2);
                if ($item->ncm && empty($produto->tributacao_ncm)) {
                    $produto->tributacao_ncm = $item->ncm;
                }
                $produto->updated_by = $userId;
                $produto->save();
            }

            $importacao->undo_snapshot = [
                'conta_pagar_ids' => $contaIds,
                'estoque' => $estoqueAntes,
                'produtos_criados_plugin_ids' => $produtosCriadosPluginIds,
            ];
            $importacao->status = 'confirmada';
            $importacao->fornecedor_id = $fornecedor->id;
            $importacao->confirmed_at = now();
            $importacao->save();
        });

        $this->audit($importacao, 'confirmar', [
            'antes' => $snapshotAntes,
            'contas_geradas' => count($dup),
            'forma_pagamento_id' => $request->forma_pagamento_id,
            'plano_conta_id' => $request->plano_conta_id,
        ]);

        if (function_exists('do_action')) {
            do_action('compras.importacao.confirmada', $importacao);
        }

        return redirect()
            ->route('plugin.compras.index')
            ->with('success', 'Entrada confirmada: contas a pagar geradas (XML da NF-e anexado quando o arquivo ainda está no servidor), estoque/custo conforme o de-para.');
    }

    public function desfazerForm(CompraImportacao $compra_importacao)
    {
        $importacao = $compra_importacao;
        $this->autorizarImportacao($importacao);
        if ($importacao->status !== 'confirmada') {
            return redirect()->route('plugin.compras.index')->with('error', 'Desfazer só se aplica a importações já confirmadas.');
        }

        return view('compras::desfazer', [
            'title' => 'Desfazer importação #'.$importacao->id,
            'imp' => $importacao,
        ]);
    }

    public function desfazer(Request $request, CompraImportacao $compra_importacao)
    {
        $importacao = $compra_importacao;
        $this->autorizarImportacao($importacao);
        if ($importacao->status !== 'confirmada') {
            return redirect()->route('plugin.compras.index')->with('error', 'Esta importação não está confirmada.');
        }

        $request->validate([
            'reverter_estoque' => 'nullable|boolean',
            'excluir_produtos_plugin' => 'nullable|boolean',
            'confirmar_desfazer' => 'required|string|in:DESFAZER',
        ]);

        $importacaoId = $importacao->id;
        $svc = new CompraImportacaoDesfazerService;
        $res = $svc->desfazerConfirmada($importacao, [
            'reverter_estoque' => $request->boolean('reverter_estoque', true),
            'excluir_produtos_plugin' => $request->boolean('excluir_produtos_plugin', true),
        ], (int) auth()->id());

        if (! $res['ok']) {
            return redirect()->route('plugin.compras.index')->with('error', $res['mensagem']);
        }

        if (function_exists('do_action')) {
            do_action('compras.importacao.desfeita', $importacaoId, [
                'reverter_estoque' => $request->boolean('reverter_estoque', true),
                'excluir_produtos_plugin' => $request->boolean('excluir_produtos_plugin', true),
            ]);
        }

        return redirect()->route('plugin.compras.index')->with('success', $res['mensagem']);
    }

    public function excluirImportacao(Request $request, CompraImportacao $compra_importacao)
    {
        $importacao = $compra_importacao;
        $this->autorizarImportacao($importacao);
        if ($importacao->status === 'confirmada') {
            return redirect()->route('plugin.compras.index')->with('error', 'Para NF confirmada use “Desfazer entrada”.');
        }

        $svc = new CompraImportacaoDesfazerService;
        $res = $svc->excluirRascunho($importacao);
        if (! $res['ok']) {
            return redirect()->route('plugin.compras.index')->with('error', $res['mensagem']);
        }

        return redirect()->route('plugin.compras.index')->with('success', $res['mensagem']);
    }

    /**
     * Cria produto novo a partir do item da NF ou reutiliza cadastro pelo EAN (GTIN numérico).
     */
    private function criarOuResolverProdutoDoItem(CompraImportacao $imp, CompraImportacaoItem $item, int $userId): ?Produto
    {
        $ean = trim((string) $item->ean);
        if ($ean !== '' && preg_match('/^\d{8,14}$/', $ean)) {
            $porEan = Produto::query()->where('ean', $ean)->first();
            if ($porEan) {
                return $porEan;
            }
        }

        $nome = trim((string) $item->x_prod);
        if ($nome === '') {
            $nome = 'Item '.$item->indice.' ('.trim((string) $item->c_prod).')';
        }
        $nome = Str::limit($nome, 255, '');

        $baseSku = sprintf('NF-IMP%d-I%02d', $imp->id, (int) $item->indice);
        $sku = $baseSku;
        $suf = 0;
        while (Produto::where('codigo_sku', $sku)->exists()) {
            $suf++;
            $sku = Str::limit($baseSku.'-'.$suf, 100, '');
        }

        $ncmDigits = preg_replace('/\D/', '', (string) $item->ncm);
        $ncm = strlen($ncmDigits) >= 8 ? substr($ncmDigits, 0, 8) : null;

        $vUn = round((float) $item->v_un_com, 2);

        $dn = is_array($item->dados_nf) ? $item->dados_nf : [];
        $unidade = strtoupper(trim((string) ($dn['u_com'] ?? 'UN')));
        if ($unidade === '') {
            $unidade = 'UN';
        }
        $unidade = Str::limit($unidade, 30, '');

        $eanTrib = isset($dn['ean_tributario']) && is_string($dn['ean_tributario']) && preg_match('/^\d{8,14}$/', $dn['ean_tributario'])
            ? $dn['ean_tributario']
            : null;

        $cest = null;
        if (! empty($dn['cest']) && is_string($dn['cest'])) {
            $cd = preg_replace('/\D/', '', $dn['cest']);
            $cest = strlen($cd) >= 7 ? substr($cd, 0, 7) : null;
        }

        $orig = isset($dn['orig_icms']) && preg_match('/^[0-8]$/', (string) $dn['orig_icms'])
            ? (string) $dn['orig_icms']
            : null;

        $pesoL = isset($dn['peso_liquido']) && is_numeric($dn['peso_liquido']) && (float) $dn['peso_liquido'] > 0
            ? round((float) $dn['peso_liquido'], 4)
            : null;
        $pesoB = isset($dn['peso_bruto']) && is_numeric($dn['peso_bruto']) && (float) $dn['peso_bruto'] > 0
            ? round((float) $dn['peso_bruto'], 4)
            : null;

        $xProd = trim((string) $item->x_prod);
        $descricaoCurta = Str::limit($xProd !== '' ? $xProd : $nome, 255, '');
        $descricaoCompl = (mb_strlen($xProd) > 255) ? $xProd : null;

        $obsLinhas = [
            'Origem: importação NF-e (plugin Compras).',
        ];
        if ($imp->chave_acesso) {
            $obsLinhas[] = 'Chave NF-e: '.$imp->chave_acesso;
        }
        $cProd = trim((string) $item->c_prod);
        if ($cProd !== '') {
            $obsLinhas[] = 'Cód. produto no fornecedor: '.$cProd;
        }
        if ($item->cfop) {
            $obsLinhas[] = 'CFOP (NF-e): '.$item->cfop;
        }

        $infoTrib = array_filter([
            $item->cfop ? 'CFOP: '.$item->cfop : null,
            $item->cst_icms ? 'CST ICMS (NF-e): '.$item->cst_icms : null,
            $orig !== null ? 'Origem mercadoria (NF-e): '.$orig : null,
            (! empty($dn['ext_ipi']) && is_string($dn['ext_ipi'])) ? 'EXTIPI (NF-e): '.$dn['ext_ipi'] : null,
        ]);
        $tributacaoInfo = $infoTrib !== [] ? implode("\n", $infoTrib) : null;

        $impostos = is_array($item->impostos) ? $item->impostos : [];
        $tributacaoIcms = isset($impostos['ICMS']) && is_array($impostos['ICMS']) ? $impostos['ICMS'] : null;
        $pisCofins = array_filter([
            'PIS' => (isset($impostos['PIS']) && is_array($impostos['PIS'])) ? $impostos['PIS'] : null,
            'COFINS' => (isset($impostos['COFINS']) && is_array($impostos['COFINS'])) ? $impostos['COFINS'] : null,
            'IPI' => (isset($impostos['IPI']) && is_array($impostos['IPI'])) ? $impostos['IPI'] : null,
        ]);

        $payload = [
            'nome' => $nome,
            'codigo_sku' => $sku,
            'unidade' => $unidade,
            'formato' => 'simples',
            'condicao' => 'novo',
            'producao' => 'terceiros',
            'preco_venda' => $vUn,
            'ean' => ($ean !== '' && preg_match('/^\d{8,14}$/', $ean)) ? $ean : null,
            'ean_tributario' => $eanTrib,
            'descricao_curta' => $descricaoCurta !== '' ? $descricaoCurta : null,
            'descricao_complementar' => $descricaoCompl,
            'observacoes' => implode("\n", $obsLinhas),
            'tributacao_ncm' => $ncm,
            'tributacao_cest' => $cest,
            'tributacao_origem' => $orig,
            'tributacao_info_adicionais' => $tributacaoInfo,
            'peso_liquido' => $pesoL,
            'peso_bruto' => $pesoB,
            'estoque_quantidade' => 0,
            'estoque_preco_compra_unitario' => $vUn,
            'estoque_preco_custo_unitario' => $vUn,
            'created_by' => $userId,
            'updated_by' => $userId,
        ];

        if ($tributacaoIcms !== null) {
            $payload['tributacao_icms'] = $tributacaoIcms;
        }
        if ($pisCofins !== []) {
            $payload['tributacao_pis_cofins'] = $pisCofins;
        }

        return Produto::create($payload);
    }

    /**
     * Copia o XML da importação para o disco público e registra anexo (mesmo padrão do financeiro).
     */
    private function anexarXmlNfeNaContaPagar(ContaPagar $conta, CompraImportacao $importacao, int $userId): void
    {
        if ($importacao->tipo_fonte !== 'xml') {
            return;
        }
        $rel = $importacao->arquivo_original_path;
        if ($rel === null || $rel === '' || ! Storage::disk('local')->exists($rel)) {
            return;
        }
        $bytes = Storage::disk('local')->get($rel);
        if ($bytes === null || $bytes === '') {
            return;
        }

        $chave = preg_replace('/\D/', '', (string) ($importacao->chave_acesso ?? ''));
        $nomeOriginal = $chave !== ''
            ? 'NFe-'.$chave.'.xml'
            : 'NF-importacao-'.$importacao->id.'.xml';
        $nomeOriginal = Str::limit($nomeOriginal, 255, '');

        $slug = Str::slug(pathinfo($nomeOriginal, PATHINFO_FILENAME)) ?: 'nfe';
        $nomeArmazenado = $slug.'_'.$conta->id.'_'.time().'.xml';
        $caminho = 'contas-pagar/'.$conta->id.'/'.$nomeArmazenado;

        Storage::disk('public')->put($caminho, $bytes);

        ContaPagarAnexo::create([
            'conta_pagar_id' => $conta->id,
            'nome_arquivo' => $nomeOriginal,
            'caminho_arquivo' => $caminho,
            'tipo_mime' => 'application/xml',
            'tamanho' => strlen($bytes),
            'created_by' => $userId,
        ]);
    }

    private function audit(CompraImportacao $imp, string $acao, array $dados): void
    {
        CompraAuditLog::create([
            'compra_importacao_id' => $imp->id,
            'user_id' => auth()->id(),
            'acao' => $acao,
            'dados' => $dados,
        ]);
    }

    private function autorizarImportacao(CompraImportacao $importacao): void
    {
        if (auth()->user()->is_admin) {
            return;
        }
        if ($importacao->user_id !== auth()->id()) {
            abort(403);
        }
    }
}
