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

use Illuminate\Http\Request;
use Pdv\Models\Caixa;
use Pdv\Models\Venda;
use Pdv\Models\Orcamento;

class PdvController
{
    /**
     * Tela do PDV em layout fullscreen (sem sidebar).
     * Pode ser aberta em nova aba para uso dedicado.
     */
    public function index(Request $request)
    {
        if (!function_exists('auth') || !auth()->check()) {
            abort(403, 'Acesso não autorizado.');
        }
        $user = auth()->user();
        if (method_exists($user, 'canAccessOperational') && !$user->canAccessOperational()) {
            abort(403, 'Sem permissão para acessar o PDV.');
        }

        return view('pdv::index', [
            'title' => 'PDV',
        ]);
    }

    /**
     * Gerenciar caixas abertos e histórico recente (layout com sidebar).
     */
    public function caixas(Request $request)
    {
        if (!function_exists('auth') || !auth()->check()) {
            abort(403, 'Acesso não autorizado.');
        }
        $user = auth()->user();
        if (method_exists($user, 'canAccessOperational') && !$user->canAccessOperational()) {
            abort(403, 'Sem permissão para acessar o PDV.');
        }

        $caixasAbertos = Caixa::where('status', Caixa::STATUS_ABERTO)
            ->with('user')
            ->orderBy('numero_caixa')
            ->get();

        $caixasFechados = Caixa::where('status', Caixa::STATUS_FECHADO)
            ->with(['user', 'fechamentoQuebraSobra.aprovadoPor'])
            ->orderByDesc('closed_at')
            ->limit(30)
            ->get();

        $formaIds = [];
        foreach ($caixasFechados as $c) {
            $q = $c->fechamentoQuebraSobra;
            if ($q && !empty($q->dados)) {
                foreach (['valores_informados_por_forma', 'valores_calculados_por_forma', 'diferencas_por_forma'] as $key) {
                    if (!empty($q->dados[$key]) && is_array($q->dados[$key])) {
                        $formaIds = array_merge($formaIds, array_keys($q->dados[$key]));
                    }
                }
            }
        }
        $formaIds = array_unique(array_map('intval', $formaIds));
        $formasPagamento = $formaIds ? \App\Models\FormaPagamento::whereIn('id', $formaIds)->get()->keyBy('id') : collect();

        $caixasParaFiltro = Caixa::has('vendas')->orderBy('numero_caixa')->get(['id', 'numero_caixa']);
        $clientesParaFiltro = \App\Models\Cliente::whereIn('id', Venda::select('cliente_id')->whereNotNull('cliente_id')->distinct()->pluck('cliente_id'))
            ->orderByRaw('COALESCE(nome, razao_social)')
            ->get(['id', 'nome', 'razao_social']);

        $vendasQuery = Venda::with(['caixa.user', 'cliente', 'itens', 'descontoAutorizadoPorUser', 'canceladoPorUser', 'cancelamentoAutorizadoPorUser', 'clienteBloqueadoAutorizadoPorUser', 'limiteCreditoAutorizadoPorUser', 'estoqueZeradoAutorizadoPorUser'])
            ->orderByDesc('created_at');

        if ($request->filled('caixa_id')) {
            $vendasQuery->where('caixa_id', $request->caixa_id);
        }
        if ($request->filled('data_de')) {
            $vendasQuery->whereDate('created_at', '>=', $request->data_de);
        }
        if ($request->filled('data_ate')) {
            $vendasQuery->whereDate('created_at', '<=', $request->data_ate);
        }
        if ($request->filled('cliente_id')) {
            $vendasQuery->where('cliente_id', $request->cliente_id);
        }
        if ($request->filled('q')) {
            $q = $request->q;
            if (is_numeric($q)) {
                $vendasQuery->where('id', (int) $q);
            } else {
                $vendasQuery->where(function ($qb) use ($q) {
                    $qb->whereHas('cliente', function ($c) use ($q) {
                        $c->where('nome', 'like', '%' . $q . '%')->orWhere('razao_social', 'like', '%' . $q . '%');
                    })->orWhereHas('caixa', function ($c) use ($q) {
                        $c->where('numero_caixa', 'like', '%' . $q . '%');
                    });
                });
            }
        }

        $vendas = $vendasQuery->paginate(30, ['*'], 'vendas_page')->withQueryString();

        $orcamentos = Orcamento::with(['caixa.user', 'cliente'])
            ->orderByDesc('created_at')
            ->paginate(30, ['*'], 'orcamentos_page');

        $canVerAutorizacoes = $user && (($user->is_admin ?? false) || (method_exists($user, 'hasPermission') && $user->hasPermission('manage-plugins')));

        return view('pdv::caixas', [
            'title' => 'Gerenciar caixas',
            'caixasAbertos' => $caixasAbertos,
            'caixasFechados' => $caixasFechados,
            'formasPagamento' => $formasPagamento,
            'caixasParaFiltro' => $caixasParaFiltro,
            'clientesParaFiltro' => $clientesParaFiltro,
            'vendas' => $vendas,
            'orcamentos' => $orcamentos,
            'canVerAutorizacoes' => $canVerAutorizacoes,
            'filtrosVendas' => [
                'caixa_id' => $request->get('caixa_id'),
                'data_de' => $request->get('data_de'),
                'data_ate' => $request->get('data_ate'),
                'cliente_id' => $request->get('cliente_id'),
                'q' => $request->get('q'),
            ],
        ]);
    }

    /**
     * Página dedicada: Histórico de Vendas (filtros: período, status, cliente, nº venda; ação cancelar).
     */
    public function historicoVendas(Request $request)
    {
        if (!function_exists('auth') || !auth()->check()) {
            abort(403, 'Acesso não autorizado.');
        }
        $user = auth()->user();
        if (method_exists($user, 'canAccessOperational') && !$user->canAccessOperational()) {
            abort(403, 'Sem permissão para acessar o PDV.');
        }

        $clientesParaFiltro = \App\Models\Cliente::whereIn('id',
            Venda::where('tipo', Venda::TIPO_VENDA)
                ->whereHas('caixa', fn ($q) => $q->where('user_id', auth()->id()))
                ->whereNotNull('cliente_id')
                ->distinct()
                ->pluck('cliente_id')
        )->orderByRaw('COALESCE(nome, razao_social)')->get(['id', 'nome', 'razao_social']);

        $vendasQuery = Venda::with(['caixa.user', 'cliente', 'itens'])
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
        if ($request->filled('numero_venda')) {
            $num = $request->numero_venda;
            if (is_numeric($num)) {
                $vendasQuery->where('id', (int) $num);
            }
        }
        if ($request->filled('cliente_nome')) {
            $termo = '%' . trim($request->cliente_nome) . '%';
            $vendasQuery->whereHas('cliente', function ($q) use ($termo) {
                $q->where('nome', 'like', $termo)->orWhere('razao_social', 'like', $termo);
            });
        }

        $vendas = $vendasQuery->paginate(15, ['*'], 'page')->withQueryString();

        $filtros = [
            'data_de' => $request->get('data_de'),
            'data_ate' => $request->get('data_ate'),
            'status' => $request->get('status'),
            'cliente_id' => $request->get('cliente_id'),
            'cliente_nome' => $request->get('cliente_nome'),
            'numero_venda' => $request->get('numero_venda'),
            'embed' => $request->get('embed'),
        ];

        $embed = $request->boolean('embed');
        $viewName = $embed ? 'pdv::historico-vendas-embed' : 'pdv::historico-vendas';

        return view($viewName, [
            'title' => 'Histórico de Vendas',
            'vendas' => $vendas,
            'clientesParaFiltro' => $clientesParaFiltro,
            'filtros' => $filtros,
        ]);
    }

    /**
     * Marca um aviso de quebra/sobra como oculto para o usuário atual (dashboard financeiro).
     */
    public function avisoQuebraSobraOcultar(Request $request, $id)
    {
        if (!function_exists('auth') || !auth()->check()) {
            return response()->json(['success' => false, 'message' => 'Não autorizado.'], 403);
        }
        $id = (int) $id;
        if ($id < 1) {
            return response()->json(['success' => false, 'message' => 'ID inválido.'], 422);
        }
        if (!\Illuminate\Support\Facades\Schema::hasTable('plugin_pdv_caixa_fechamento_quebra_sobra') || !\Illuminate\Support\Facades\Schema::hasTable('plugin_pdv_aviso_quebra_sobra_oculto')) {
            return response()->json(['success' => false, 'message' => 'Recurso não disponível.'], 404);
        }
        $existe = \Illuminate\Support\Facades\DB::table('plugin_pdv_caixa_fechamento_quebra_sobra')->where('id', $id)->exists();
        if (!$existe) {
            return response()->json(['success' => false, 'message' => 'Aviso não encontrado.'], 404);
        }
        \Illuminate\Support\Facades\DB::table('plugin_pdv_aviso_quebra_sobra_oculto')->insertOrIgnore([
            'user_id' => auth()->id(),
            'caixa_fechamento_quebra_sobra_id' => $id,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        return response()->json(['success' => true]);
    }

    /**
     * Exporta histórico de vendas em CSV (uma linha por item).
     */
    public function historicoVendasExportCsv(Request $request)
    {
        if (!function_exists('auth') || !auth()->check()) {
            abort(403, 'Acesso não autorizado.');
        }
        $user = auth()->user();
        if (method_exists($user, 'canAccessOperational') && !$user->canAccessOperational()) {
            abort(403, 'Sem permissão para acessar o PDV.');
        }

        $vendasQuery = $this->historicoVendasQuery($request);
        $vendas = $vendasQuery->with(['cliente', 'itens'])->limit(5000)->orderBy('id')->get();

        $filename = 'historico-vendas_' . now()->format('Y-m-d_His') . '.csv';
        $headers = [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ];

        return response()->streamDownload(function () use ($vendas) {
            $out = fopen('php://output', 'w');
            fprintf($out, chr(0xEF) . chr(0xBB) . chr(0xBF));
            fputcsv($out, ['Nº Venda', 'Data/Hora', 'Cliente', 'Status', 'Item', 'Qtd', 'Preço Unit.', 'Total Item', 'Desconto', 'Observação'], ';');
            foreach ($vendas as $v) {
                $cliente = $v->cliente ? ($v->cliente->nome ?? $v->cliente->razao_social ?? '—') : 'Consumidor Final';
                $dataHora = $v->created_at?->format('d/m/Y H:i') ?? '—';
                $desconto = (float) $v->desconto;
                $descontoStr = $desconto > 0 ? number_format($desconto, 2, ',', '.') : '';
                $obs = trim((string) ($v->observacoes ?? ''));
                if ($v->itens->isEmpty()) {
                    fputcsv($out, [
                        str_pad($v->id, 5, '0', STR_PAD_LEFT),
                        $dataHora,
                        $cliente,
                        $v->status,
                        '(sem itens)',
                        '',
                        '',
                        number_format((float) $v->total_final, 2, ',', '.'),
                        $descontoStr,
                        $obs,
                    ], ';');
                } else {
                    foreach ($v->itens as $item) {
                        fputcsv($out, [
                            str_pad($v->id, 5, '0', STR_PAD_LEFT),
                            $dataHora,
                            $cliente,
                            $v->status,
                            $item->descricao ?? '—',
                            number_format((float) $item->quantidade, 4, ',', '.'),
                            number_format((float) $item->preco_unitario, 2, ',', '.'),
                            number_format((float) $item->total, 2, ',', '.'),
                            $descontoStr,
                            $obs,
                        ], ';');
                    }
                }
            }
            fclose($out);
        }, $filename, $headers);
    }

    /**
     * Exporta histórico de vendas em PDF.
     */
    public function historicoVendasExportPdf(Request $request)
    {
        if (!function_exists('auth') || !auth()->check()) {
            abort(403, 'Acesso não autorizado.');
        }
        $user = auth()->user();
        if (method_exists($user, 'canAccessOperational') && !$user->canAccessOperational()) {
            abort(403, 'Sem permissão para acessar o PDV.');
        }

        $vendasQuery = $this->historicoVendasQuery($request);
        $vendas = $vendasQuery->with(['cliente', 'itens'])->limit(500)->orderBy('id')->get();

        $html = view('pdv::historico-vendas-pdf', [
            'vendas' => $vendas,
            'filtros' => [
                'data_de' => $request->get('data_de'),
                'data_ate' => $request->get('data_ate'),
                'status' => $request->get('status'),
            ],
        ])->render();

        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadHTML($html);
        $pdf->setPaper('A4', 'portrait');
        $pdf->setOption('margin-top', '10mm');
        $pdf->setOption('margin-bottom', '10mm');
        $pdf->setOption('margin-left', '10mm');
        $pdf->setOption('margin-right', '10mm');
        $filename = 'historico-vendas_' . now()->format('Y-m-d_His') . '.pdf';
        return $pdf->download($filename);
    }

    /**
     * Monta a query de vendas para o histórico (mesmos filtros da página).
     */
    private function historicoVendasQuery(Request $request)
    {
        $query = Venda::where('tipo', Venda::TIPO_VENDA)
            ->whereHas('caixa', fn ($q) => $q->where('user_id', auth()->id()))
            ->orderByDesc('created_at');

        if ($request->filled('data_de')) {
            $query->whereDate('created_at', '>=', $request->data_de);
        }
        if ($request->filled('data_ate')) {
            $query->whereDate('created_at', '<=', $request->data_ate);
        }
        if ($request->filled('status') && $request->status !== '') {
            $query->where('status', $request->status);
        }
        if ($request->filled('cliente_id')) {
            $query->where('cliente_id', $request->cliente_id);
        }
        if ($request->filled('numero_venda') && is_numeric($request->numero_venda)) {
            $query->where('id', (int) $request->numero_venda);
        }
        if ($request->filled('cliente_nome')) {
            $termo = '%' . trim($request->cliente_nome) . '%';
            $query->whereHas('cliente', fn ($q) => $q->where('nome', 'like', $termo)->orWhere('razao_social', 'like', $termo));
        }

        return $query;
    }
}
