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

use App\Http\Controllers\Concerns\BuscaCatalogoVendas;
use App\Http\Controllers\Concerns\HasEntityHooks;
use App\Http\Requests\PropostaComercialRequest;
use App\Models\Cliente;
use App\Models\Configuracao;
use App\Models\Contato;
use App\Models\Endereco;
use App\Models\Produto;
use App\Models\PropostaComercial;
use App\Models\PropostaDocumentoModelo;
use App\Models\Servico;
use App\Models\TabelaPreco;
use App\Models\User;
use App\Services\PropostaComercialDocxService;
use App\Services\PropostaComercialHtmlModeloService;
use App\Services\PropostaComercialService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class PropostaComercialController extends Controller
{
    use BuscaCatalogoVendas;
    use HasEntityHooks;

    public function __construct(
        private PropostaComercialService $service,
        private PropostaComercialDocxService $docxService,
        private PropostaComercialHtmlModeloService $htmlModeloService
    ) {}

    public function index(Request $request)
    {
        $this->authorizePermission('view-propostas-comerciais');

        $query = PropostaComercial::query()
            ->where('is_versao_atual', true)
            ->with(['cliente', 'vendedor']);

        if ($request->filled('search')) {
            $term = trim((string) $request->search);
            $s = '%'.str_replace(['%', '_'], ['\\%', '\\_'], $term).'%';
            $query->where(function ($q) use ($s, $term) {
                $q->where('titulo', 'like', $s)
                    ->orWhere('numero_sequencial', 'like', $s)
                    ->orWhereHas('cliente', fn ($c) => $c->where('nome', 'like', $s));
                if (ctype_digit($term)) {
                    $q->orWhere('id', (int) $term);
                }
            });
        }
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        // Atalho do sininho de notificações: rascunho/enviada + validade hoje ou vencida
        $alertaVal = $request->query('alerta_proposta_validade');
        if ($alertaVal !== null && $alertaVal !== '' && in_array($alertaVal, ['hoje', 'vencida'], true)) {
            $query->whereIn('status', [PropostaComercial::STATUS_RASCUNHO, PropostaComercial::STATUS_ENVIADA])
                ->whereNotNull('validade_em');
            $hoje = now()->toDateString();
            if ($alertaVal === 'hoje') {
                $query->whereDate('validade_em', $hoje);
            } else {
                $query->whereDate('validade_em', '<', $hoje);
            }
        }

        $query = $this->applyEntityQuery($query, 'proposta_comercial');

        if (function_exists('do_action')) {
            do_action('proposta_comercial.index.before', $query, $request);
        }

        $propostas = $query->orderByDesc('updated_at')->paginate(20)->withQueryString();

        $data = [
            'title' => 'Propostas comerciais',
            'propostas' => $propostas,
        ];
        if (function_exists('apply_filters')) {
            $data = apply_filters('proposta_comercial.index.data', $data, $request);
        }

        return erp_view('propostas-comerciais.index', $data);
    }

    public function create()
    {
        $this->authorizePermission('create-propostas-comerciais');

        return erp_view('propostas-comerciais.create', $this->dadosFormulario());
    }

    public function store(PropostaComercialRequest $request)
    {
        $this->authorizePermission('create-propostas-comerciais');

        DB::beginTransaction();
        try {
            $proposta = $this->service->salvar($request->validated());
            DB::commit();
        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error('Erro ao criar PropostaComercial', ['exception' => $e->getMessage()]);

            return redirect()->back()->withInput()->with('error', 'Erro ao salvar: '.$e->getMessage());
        }

        return redirect()->route('propostas-comerciais.show', $proposta)
            ->with('success', 'Proposta criada. Referência: '.$proposta->referenciaGrupo());
    }

    public function show(PropostaComercial $propostaComercial)
    {
        $this->authorizePermission('view-propostas-comerciais');

        $proposta = $propostaComercial;
        $proposta->load([
            'cliente.contatos', 'cliente.enderecos', 'vendedor', 'tabelaPreco', 'pedidoVenda', 'ordemServico',
            'itens.produto', 'itens.servico', 'itens.variacao',
        ]);
        $versoes = PropostaComercial::where('grupo_uuid', $proposta->grupo_uuid)
            ->orderBy('versao')
            ->get(['id', 'versao', 'status', 'is_versao_atual', 'total_geral', 'updated_at']);

        $modelosDocumento = PropostaDocumentoModelo::ativos()->ordenados()->get(['id', 'nome', 'formato']);

        $data = [
            'title' => 'Proposta '.$proposta->referenciaGrupo(),
            'proposta' => $proposta,
            'versoes' => $versoes,
            'modelosDocumento' => $modelosDocumento,
            'modelosDocx' => $modelosDocumento,
        ];
        if (function_exists('apply_filters')) {
            $data = apply_filters('proposta_comercial.show.data', $data, $proposta);
        }

        return erp_view('propostas-comerciais.show', $data);
    }

    public function edit(PropostaComercial $propostaComercial)
    {
        $this->authorizePermission('edit-propostas-comerciais');

        $proposta = $propostaComercial;
        if (! $proposta->podeEditar()) {
            return redirect()->route('propostas-comerciais.show', $proposta)
                ->with('error', 'Esta proposta não pode ser editada.');
        }

        $proposta->load([
            'cliente.contatos', 'cliente.enderecos',
            'unidade.contatos', 'unidade.enderecos',
            'contato', 'endereco',
            'itens.produto.variacoes',
            'itens.produto.composicoes.componente',
            'itens.servico',
        ]);

        return erp_view('propostas-comerciais.edit', array_merge($this->dadosFormulario(), [
            'proposta' => $proposta,
        ]));
    }

    public function update(PropostaComercialRequest $request, PropostaComercial $propostaComercial)
    {
        $this->authorizePermission('edit-propostas-comerciais');

        $proposta = $propostaComercial;
        if (! $proposta->podeEditar()) {
            return redirect()->back()->with('error', 'Esta proposta não pode ser editada.');
        }

        DB::beginTransaction();
        try {
            $this->service->salvar($request->validated(), $proposta);
            DB::commit();
        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error('Erro ao atualizar PropostaComercial', ['id' => $proposta->id, 'exception' => $e->getMessage()]);

            return redirect()->back()->withInput()->with('error', 'Erro ao salvar: '.$e->getMessage());
        }

        return redirect()->route('propostas-comerciais.show', $proposta)
            ->with('success', 'Proposta atualizada.');
    }

    public function destroy(Request $request, PropostaComercial $propostaComercial)
    {
        $this->authorizePermission('delete-propostas-comerciais');

        $proposta = $propostaComercial;
        if (! $proposta->podeExcluir()) {
            return redirect()->back()->with('error', 'Apenas propostas em rascunho podem ser excluídas.');
        }

        $proposta->delete();

        return redirect()->route('propostas-comerciais.index')
            ->with('success', 'Proposta excluída.');
    }

    public function novaVersao(Request $request, PropostaComercial $propostaComercial)
    {
        $this->authorizePermission('create-propostas-comerciais');

        $proposta = $propostaComercial;
        DB::beginTransaction();
        try {
            $nova = $this->service->novaVersao($proposta);
            DB::commit();
        } catch (\Throwable $e) {
            DB::rollBack();

            return $this->redirectBackOrShow($request, $proposta, $e->getMessage());
        }

        return redirect()->route('propostas-comerciais.edit', $nova)
            ->with('success', 'Nova versão criada: v'.$nova->versao);
    }

    public function converterPedido(Request $request, PropostaComercial $propostaComercial)
    {
        $this->authorizePermission('edit-propostas-comerciais');
        $this->authorizePermission('create-pedidos-venda');

        $proposta = $propostaComercial;
        DB::beginTransaction();
        try {
            $pedido = $this->service->converterParaPedidoVenda($proposta);
            DB::commit();
        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error('Erro ao converter proposta em pedido', ['proposta_id' => $proposta->id, 'exception' => $e->getMessage()]);

            return $this->redirectBackOrShow($request, $proposta, $e->getMessage());
        }

        return redirect()->route('pedidos-venda.show', $pedido)
            ->with('success', 'Pedido '.$pedido->numero_sequencial.' criado a partir da proposta.');
    }

    public function exportPdf(PropostaComercial $propostaComercial)
    {
        $this->authorizePermission('view-propostas-comerciais');

        [$view, $data] = $this->viewEPayloadPdfPadrao($propostaComercial);
        $pdf = Pdf::loadView($view, $data);
        $p = $data['proposta'] ?? $propostaComercial;

        return $pdf->download('proposta-'.$p->id.'-v'.$p->versao.'.pdf');
    }

    /**
     * Mesmo conteúdo do PDF padrão, em HTML, para imprimir ou guardar como PDF no browser (Ctrl+P).
     */
    public function verImpressaoPadrao(PropostaComercial $propostaComercial)
    {
        $this->authorizePermission('view-propostas-comerciais');

        [, $data] = $this->viewEPayloadPdfPadrao($propostaComercial);

        $viewImpressao = 'propostas-comerciais.impressao-padrao';
        if (function_exists('apply_filters')) {
            $filtered = apply_filters('proposta_comercial.impressao.view', $viewImpressao, $data['proposta']);
            if (is_string($filtered) && $filtered !== '') {
                $viewImpressao = $filtered;
            }
        }

        return response()->view($viewImpressao, $data)
            ->header('X-Frame-Options', 'SAMEORIGIN');
    }

    /**
     * @return array{0: string, 1: array<string, mixed>}
     */
    private function viewEPayloadPdfPadrao(PropostaComercial $propostaComercial): array
    {
        $proposta = $propostaComercial;
        $proposta->load(['cliente', 'vendedor', 'itens.produto', 'itens.servico']);

        $data = ['proposta' => $proposta];
        if (function_exists('apply_filters')) {
            $filtered = apply_filters('proposta_comercial.pdf.data', $data, $proposta);
            if (is_array($filtered)) {
                $data = $filtered;
            }
        }

        $view = 'propostas-comerciais.pdf';
        if (function_exists('apply_filters')) {
            $filteredView = apply_filters('proposta_comercial.pdf.view', $view, $proposta);
            if (is_string($filteredView) && $filteredView !== '') {
                $view = $filteredView;
            }
        }

        return [$view, $data];
    }

    /**
     * Gera .docx a partir de modelo Word (PhpWord — sem conversores externos).
     * PDF continua disponível via exportPdf (DomPDF).
     */
    public function exportDocx(Request $request, PropostaComercial $propostaComercial)
    {
        $this->authorizePermission('view-propostas-comerciais');

        $request->validate([
            'modelo_id' => 'required|integer|exists:proposta_documento_modelos,id',
        ]);

        $modelo = PropostaDocumentoModelo::whereKey((int) $request->modelo_id)->where('ativo', true)->firstOrFail();
        if (! $modelo->isDocx()) {
            abort(422, 'Este modelo não é Word (.docx). Use “Abrir página HTML” para modelos HTML.');
        }
        $abs = $modelo->caminhoAbsolutoArquivo();
        if ($abs === null) {
            abort(404, 'Modelo sem arquivo ou ficheiro em falta no servidor.');
        }

        $proposta = $propostaComercial;
        $proposta->load(['cliente', 'vendedor', 'tabelaPreco', 'itens']);

        $out = tempnam(sys_get_temp_dir(), 'erp_pc_');
        if ($out === false) {
            return redirect()->back()->with('error', 'Não foi possível criar ficheiro temporário.');
        }
        $outDocx = $out.'.docx';
        @unlink($out);

        try {
            $this->docxService->preencher($proposta, $abs, $outDocx);
        } catch (\Throwable $e) {
            @unlink($outDocx);
            Log::error('Erro ao gerar DOCX da proposta', ['proposta_id' => $proposta->id, 'exception' => $e->getMessage()]);

            // Em fluxo de download, retornar redirect costuma fazer o browser gravar HTML como `.docx`.
            // Preferimos erro explícito para o utilizador/caller.
            if ($request->wantsJson()) {
                return response()->json([
                    'ok' => false,
                    'error' => 'Não foi possível gerar o documento Word. Verifique o modelo e as tags.',
                    'details' => $e->getMessage(),
                ], 422);
            }

            abort(422, 'Não foi possível gerar o documento Word. Verifique o modelo e as tags. Detalhe: ' . $e->getMessage());
        }

        $slug = preg_replace('/[^a-zA-Z0-9_-]+/u', '_', $modelo->nome);
        $slug = $slug !== '' ? $slug : 'modelo';
        $filename = 'proposta-'.$proposta->id.'-v'.$proposta->versao.'-'.$slug.'.docx';

        // Segurança adicional: DOCX é um ZIP; se falhar, não devolvemos um ficheiro corrompido.
        try {
            if (! is_file($outDocx) || filesize($outDocx) === false || (int) filesize($outDocx) < 1024) {
                throw new \RuntimeException('DOCX gerado está vazio/ilegível (tamanho inválido).');
            }

            $za = new \ZipArchive();
            $ok = $za->open($outDocx) === true;
            if (! $ok) {
                $za->close();
                throw new \RuntimeException('DOCX gerado não é um ZIP válido (corrompido).');
            }

            // Validação mínima de um DOCX:
            // - [Content_Types].xml
            // - word/document.xml
            $required = ['[content_types].xml', 'word/document.xml'];
            $foundMap = [];
            for ($i = 0; $i < $za->numFiles; $i++) {
                $name = $za->getNameIndex($i);
                if (! is_string($name) || $name === '') {
                    continue;
                }

                $lower = strtolower($name);
                if (in_array($lower, $required, true)) {
                    $foundMap[$lower] = true;
                }
            }

            foreach ($required as $entryLower) {
                if (empty($foundMap[$entryLower])) {
                    $za->close();
                    // devolve nome “humano” com casing mais esperado
                    $hum = $entryLower === '[content_types].xml' ? '[Content_Types].xml' : $entryLower;
                    throw new \RuntimeException('DOCX inválido: falta ficheiro interno ' . $hum . '.');
                }
            }

            $za->close();

            Log::info('DOCX validado antes do download', [
                'proposta_id' => $proposta->id,
                'modelo_id' => $modelo->id,
                'size_bytes' => (int) filesize($outDocx),
            ]);
        } catch (\Throwable $e) {
            @unlink($outDocx);
            Log::error('DOCX inválido antes do download', [
                'proposta_id' => $proposta->id,
                'modelo_id' => $modelo->id,
                'exception' => $e->getMessage(),
            ]);

            if ($request->wantsJson()) {
                return response()->json([
                    'ok' => false,
                    'error' => 'DOCX gerado inválido (corrompido).',
                    'details' => $e->getMessage(),
                ], 500);
            }

            abort(500, 'DOCX gerado inválido (corrompido). Detalhe: ' . $e->getMessage());
        }

        return response()->download($outDocx, $filename, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        ])->deleteFileAfterSend(true);
    }

    /**
     * Abre numa página o modelo HTML preenchido com os dados da proposta.
     */
    public function verModeloHtml(Request $request, PropostaComercial $propostaComercial)
    {
        $this->authorizePermission('view-propostas-comerciais');

        $request->validate([
            'modelo_id' => 'required|integer|exists:proposta_documento_modelos,id',
        ]);

        $modelo = PropostaDocumentoModelo::whereKey((int) $request->modelo_id)->where('ativo', true)->firstOrFail();
        if (! $modelo->isHtml()) {
            return redirect()->back()->with('error', 'Este modelo não é HTML. Para Word use “Descarregar DOCX”.');
        }
        $abs = $modelo->caminhoAbsolutoArquivo();
        if ($abs === null) {
            return redirect()->back()->with('error', 'Modelo sem arquivo ou ficheiro em falta no servidor.');
        }

        $proposta = $propostaComercial;
        $proposta->load(['cliente', 'vendedor', 'tabelaPreco', 'itens.produto', 'itens.servico']);

        try {
            $html = $this->htmlModeloService->renderizarConteudo($proposta, $abs);
        } catch (\Throwable $e) {
            Log::error('Erro ao renderizar modelo HTML da proposta', [
                'proposta_id' => $proposta->id,
                'modelo_id' => $modelo->id,
                'exception' => $e->getMessage(),
            ]);

            return redirect()->back()->with('error', 'Não foi possível gerar a página a partir do modelo HTML. Detalhe: '.$e->getMessage());
        }

        $html = $this->envolverHtmlModeloSeFragmento($html, $proposta);
        $html = $this->aplicarAjustesImpressaoModeloHtml($html);

        if (function_exists('do_action')) {
            do_action('proposta_comercial.html_modelo.visualizado', $proposta, $modelo);
        }

        return response()->make($html, 200, [
            'Content-Type' => 'text/html; charset=UTF-8',
            'X-Frame-Options' => 'SAMEORIGIN',
        ]);
    }

    /**
     * Se o modelo for só um fragmento (sem &lt;html&gt;), envolve em documento mínimo para o browser.
     */
    private function envolverHtmlModeloSeFragmento(string $html, PropostaComercial $proposta): string
    {
        $trim = ltrim($html);
        if (preg_match('/^<!DOCTYPE/i', $trim) === 1 || preg_match('/^<html[\s>]/i', $trim) === 1) {
            return $html;
        }

        $titulo = htmlspecialchars($proposta->referenciaGrupo(), ENT_QUOTES, 'UTF-8');

        return '<!DOCTYPE html><html lang="pt-BR"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1"><title>'.$titulo.'</title></head><body>'.$html.'</body></html>';
    }

    /**
     * Ajustes defensivos para impressão em browser (Ctrl+P) de modelos HTML.
     * Evita quebra estranha no meio de blocos/tabelas em layouts personalizados.
     */
    private function aplicarAjustesImpressaoModeloHtml(string $html): string
    {
        $printCss = <<<'CSS'
<style id="livreos-print-fix">
@media print {
    @page { size: A4; margin: 10mm; }
    html, body { margin: 0 !important; }
    .no-print, [data-no-print="1"] { display: none !important; }

    /* Mantém cabeçalho/rodapé de tabela entre páginas, quando houver */
    thead { display: table-header-group; }
    tfoot { display: table-footer-group; }

    /* Evita empurrar blocos grandes para a próxima página */
    table, tbody, .container, .content, .section, .bloco, .box, .card {
        break-inside: auto !important;
        page-break-inside: auto !important;
    }
    .descricao, .descricao-geral, .descricao-proposta, .objeto, .objeto-proposta,
    [class*="descricao"], [id*="descricao"], [class*="objeto"], [id*="objeto"] {
        break-inside: auto !important;
        page-break-inside: auto !important;
    }
    .page, .pagina, [class*="page"], [class*="pagina"] {
        height: auto !important;
        min-height: 0 !important;
    }

    /* Evita cortar linhas de tabela no meio */
    tr, td, th {
        break-inside: avoid;
        page-break-inside: avoid;
    }

    h1, h2, h3, h4, h5, h6 {
        break-after: avoid;
        page-break-after: avoid;
    }
}
</style>
CSS;

        // Evita inserir duplicado ao recarregar.
        if (str_contains($html, 'id="livreos-print-fix"')) {
            return $html;
        }

        if (preg_match('/<\/head>/i', $html) === 1) {
            return (string) preg_replace('/<\/head>/i', $printCss.'</head>', $html, 1);
        }

        // Modelos sem <head>: injeta no início para ainda aplicar no print.
        return $printCss.$html;
    }

    public function buscarProduto(Request $request)
    {
        $this->authorizePermission('view-propostas-comerciais');

        $term = '%'.$request->input('q', '').'%';
        $tipo = $request->input('tipo', 'produto');
        $tabelaPrecoId = $this->normalizarTabelaPrecoIdRequest($request->input('tabela_preco_id'));
        $produtoId = (int) $request->input('produto_id', 0);
        $percentualAjuste = null;
        if ($tabelaPrecoId !== null) {
            $percentualAjuste = (float) (TabelaPreco::query()->whereKey($tabelaPrecoId)->value('percentual_ajuste') ?? 0);
        }

        if ($tipo === 'servico') {
            $resultados = Servico::where(function ($q) use ($term) {
                $q->where('nome', 'like', $term)
                    ->orWhere('codigo_interno', 'like', $term)
                    ->orWhere('codigo_sku', 'like', $term);
            })
                ->select('id', 'nome as descricao', 'preco')
                ->limit(20)->get();

            return response()->json($resultados)->header('Cache-Control', 'no-store, no-cache');
        }

        if ($produtoId > 0) {
            $p = Produto::query()
                ->whereKey($produtoId)
                ->with([
                    'variacoes' => fn ($q) => $q->orderBy('id'),
                    'composicoes' => fn ($q) => $q->orderBy('id'),
                    'composicoes.componente',
                ])
                ->select('id', 'nome', 'formato', 'preco_venda', 'estoque_quantidade', 'estoque_localizacao')
                ->first();
            if (! $p) {
                return response()->json([])->header('Cache-Control', 'no-store, no-cache');
            }
            $precosTabelaMap = $this->mapaPrecosProdutoTabela(collect([(int) $p->id]), $tabelaPrecoId);

            return response()->json([
                $this->mapearProdutoLinhaBuscaPedido($p, $precosTabelaMap, $tabelaPrecoId, $percentualAjuste),
            ])->header('Cache-Control', 'no-store, no-cache');
        }

        $produtos = Produto::where(function ($q) use ($term) {
            $q->where('nome', 'like', $term)
                ->orWhere('codigo_sku', 'like', $term);
        })
            ->with([
                'variacoes' => fn ($q) => $q->orderBy('id'),
                'composicoes' => fn ($q) => $q->orderBy('id'),
                'composicoes.componente',
            ])
            ->select('id', 'nome', 'formato', 'preco_venda', 'estoque_quantidade', 'estoque_localizacao')
            ->limit(20)
            ->get();

        $precosTabelaMap = $this->mapaPrecosProdutoTabela($produtos->pluck('id'), $tabelaPrecoId);

        return response()->json($produtos->map(function (Produto $p) use ($precosTabelaMap, $tabelaPrecoId, $percentualAjuste) {
            return $this->mapearProdutoLinhaBuscaPedido($p, $precosTabelaMap, $tabelaPrecoId, $percentualAjuste);
        })->values())->header('Cache-Control', 'no-store, no-cache');
    }

    private function dadosFormulario(): array
    {
        $vdCfg = Configuracao::getValue('pedidos_venda.validade_orcamento_dias', '');
        $data = [
            'title' => 'Proposta comercial',
            'clientes' => Cliente::orderBy('nome')->select('id', 'nome', 'tipo_pessoa', 'cpf', 'cnpj', 'razao_social', 'grupo_economico_id')->get(),
            'unidades' => Cliente::where('tipo_unidade', 'filial')->orWhereNotNull('matriz_id')->orderBy('nome')->select('id', 'nome', 'grupo_economico_id', 'matriz_id')->get(),
            'contatos' => Contato::orderBy('nome')->get(),
            'enderecos' => Endereco::with('cliente')->orderBy('id')->get(),
            'vendedores' => User::where('ativo', true)->orderBy('name')->select('id', 'name')->get(),
            'tabelasPrecos' => TabelaPreco::where('ativo', true)->orderBy('nome')->select('id', 'nome', 'percentual_ajuste')->get(),
            'validade_orcamento_dias_config' => (is_numeric($vdCfg) && (int) $vdCfg > 0) ? (int) $vdCfg : 0,
        ];
        if (function_exists('apply_filters')) {
            $data = apply_filters('proposta_comercial.form.data', $data);
        }

        return $data;
    }

    public function updateCliente(Request $request, PropostaComercial $propostaComercial)
    {
        $this->authorizePermission('edit-propostas-comerciais');

        $proposta = $propostaComercial;
        if (! $proposta->podeEditar()) {
            return response()->json(['message' => 'Esta proposta não pode ser editada.'], 422);
        }

        $data = $this->validateWithFilters($request, [
            'cliente_id' => 'required|integer|exists:clientes,id',
            'cliente_unidade_id' => 'nullable|integer|exists:clientes,id',
            'contato_id' => 'nullable|integer|exists:contatos,id',
            'endereco_id' => 'nullable|integer|exists:enderecos,id',
        ]);

        $clienteId = $data['cliente_id'];
        $unidadeId = $data['cliente_unidade_id'] ?? null;
        $contatoId = $data['contato_id'] ?? null;
        $enderecoId = $data['endereco_id'] ?? null;

        if ($unidadeId) {
            if ($contatoId && !Contato::whereKey($contatoId)->where('cliente_id', $unidadeId)->exists()) {
                return response()->json(['message' => 'Contato inválido para a filial.'], 422);
            }
            if ($enderecoId && !Endereco::whereKey($enderecoId)->where('cliente_id', $unidadeId)->exists()) {
                return response()->json(['message' => 'Endereço inválido para a filial.'], 422);
            }
            if (! $contatoId) {
                $contatoId = Contato::where('cliente_id', $unidadeId)->orderByDesc('principal')->orderBy('id')->value('id');
            }
            if (! $enderecoId) {
                $enderecoId = Endereco::where('cliente_id', $unidadeId)->orderByDesc('principal')->orderBy('id')->value('id');
            }
        } else {
            if ($contatoId && !Contato::whereKey($contatoId)->where('cliente_id', $clienteId)->exists()) {
                return response()->json(['message' => 'Contato inválido para o cliente.'], 422);
            }
            if ($enderecoId && !Endereco::whereKey($enderecoId)->where('cliente_id', $clienteId)->exists()) {
                return response()->json(['message' => 'Endereço inválido para o cliente.'], 422);
            }
        }

        $proposta->cliente_id = $clienteId;
        $proposta->cliente_unidade_id = $unidadeId;
        $proposta->contato_id = $contatoId;
        $proposta->endereco_id = $enderecoId;
        $proposta->updated_by = auth()->id();
        $proposta->save();

        $proposta->load([
            'cliente.contatos', 'cliente.enderecos',
            'unidade.contatos', 'unidade.enderecos',
            'contato', 'endereco',
        ]);

        $clienteAtual = $proposta->cliente;
        $unidadeAtual = $proposta->unidade;
        $baseCliente = $unidadeAtual ?? $clienteAtual;
        $contatosCliente = $baseCliente?->contatos ?? collect();
        $contatoPreferencial = $proposta->contato
            ?? $contatosCliente->firstWhere('principal', true)
            ?? $contatosCliente->sortBy('id')->first();
        $enderecoAtual = $proposta->endereco
            ?? $baseCliente?->enderecos?->firstWhere('principal', true);
        $contatoWhatsappPreferencial = $baseCliente
            ? $baseCliente->contatos
                ->where('telefone_whatsapp', true)
                ->sortByDesc(fn($c) => (int) $c->principal)
                ->first()
            : null;

        $contatosResumo = $contatosCliente
            ->where('principal', false)
            ->sortBy('id')
            ->take(3)
            ->map(fn($c) => [
                'nome' => $c->nome,
                'telefone' => $c->telefone,
                'email' => $c->email,
            ])
            ->values()
            ->toArray();

        return response()->json([
            'cliente_nome' => $clienteAtual?->nome ?? '-',
            'endereco_texto' => $enderecoAtual
                ? trim($enderecoAtual->logradouro . ' ' . $enderecoAtual->numero . ' ' . ($enderecoAtual->complemento ? '- ' . $enderecoAtual->complemento : '') . ', ' . $enderecoAtual->bairro . ' - ' . $enderecoAtual->cidade . '/' . $enderecoAtual->estado)
                : '-',
            'contato_telefone' => $contatoPreferencial?->telefone ?? '-',
            'contato_nome' => $contatoPreferencial?->nome ?? '-',
            'contato_email' => $contatoPreferencial?->email ?? '-',
            'whatsapp' => $contatoWhatsappPreferencial?->telefone ?? null,
            'whatsapp_digits' => $contatoWhatsappPreferencial?->telefone ? preg_replace('/[^0-9]/', '', $contatoWhatsappPreferencial->telefone) : null,
            'contatos_resumo' => $contatosResumo,
        ]);
    }

    private function redirectBackOrShow(Request $request, PropostaComercial $proposta, string $msg)
    {
        if ($request->wantsJson()) {
            return response()->json(['message' => $msg], 422);
        }

        return redirect()->route('propostas-comerciais.show', $proposta)->with('error', $msg);
    }
}
