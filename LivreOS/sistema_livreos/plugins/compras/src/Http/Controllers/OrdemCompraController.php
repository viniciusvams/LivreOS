<?php

namespace Compras\Http\Controllers;

use App\Models\Contato;
use App\Models\Deposito;
use App\Models\Produto;
use Compras\Models\OrdemCompra;
use Compras\Services\OrdemCompraService;
use Compras\Services\RecebimentoCompraService;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;

class OrdemCompraController extends Controller
{
    public function index()
    {
        $ordens = OrdemCompra::with(['fornecedor', 'itens', 'recebimentos'])
            ->orderByDesc('id')
            ->paginate(20);

        $fornecedores = Contato::query()
            ->where('is_fornecedor', true)
            ->with('cliente')
            ->orderBy('nome')
            ->get();

        return view('compras::ordens.index', [
            'title' => 'Ordens de Compra',
            'ordens' => $ordens,
            'fornecedores' => $fornecedores,
            'produtos' => Produto::orderBy('nome')->limit(500)->get(['id', 'nome', 'codigo_sku', 'unidade', 'estoque_quantidade']),
        ]);
    }

    public function criar(Request $request, OrdemCompraService $service)
    {
        $validated = $request->validate([
            'fornecedor_id' => 'required|exists:contatos,id',
            'data_previsao' => 'nullable|date',
            'observacoes' => 'nullable|string|max:1000',
            'itens' => 'required|array|min:1',
            'itens.*.produto_id' => 'required|exists:produtos,id',
            'itens.*.quantidade' => 'required|numeric|min:0.0001',
            'itens.*.preco_unitario' => 'required|numeric|min:0',
        ]);

        try {
            $ordem = $service->criar($validated, auth()->id());
            return redirect()->route('plugin.compras.ordens.index')->with('success', 'Ordem de compra criada com sucesso.');
        } catch (\Throwable $e) {
            return back()->with('error', $e->getMessage())->withInput();
        }
    }

    public function mostrar(OrdemCompra $ordem)
    {
        $ordem->load(['fornecedor', 'itens.produto', 'recebimentos.itens']);

        return view('compras::ordens.show', [
            'title' => 'Ordem de Compra #' . $ordem->numero_ordem,
            'ordem' => $ordem,
        ]);
    }

    public function aprovar(OrdemCompra $ordem, OrdemCompraService $service)
    {
        try {
            $service->aprovar($ordem, auth()->id());
            return redirect()->back()->with('success', 'Ordem aprovada com sucesso.');
        } catch (\Throwable $e) {
            return redirect()->back()->with('error', $e->getMessage());
        }
    }

    public function rejeitar(Request $request, OrdemCompra $ordem, OrdemCompraService $service)
    {
        $request->validate([
            'motivo' => 'required|string|min:3',
        ]);

        try {
            $service->rejeitar($ordem, $request->motivo, auth()->id());
            return redirect()->back()->with('success', 'Ordem rejeitada.');
        } catch (\Throwable $e) {
            return redirect()->back()->with('error', $e->getMessage());
        }
    }

    public function recebimento(OrdemCompra $ordem)
    {
        $ordem->load(['fornecedor', 'itens.produto']);

        return view('compras::ordens.receber', [
            'title' => 'Receber compra',
            'ordem' => $ordem,
            'depositos' => Deposito::query()->where('ativo', true)->orderBy('nome')->get(),
        ]);
    }

    public function registrarRecebimento(Request $request, OrdemCompra $ordem, RecebimentoCompraService $service)
    {
        $validated = $request->validate([
            'data_recebimento' => 'required|date',
            'deposito_id' => 'required|integer|exists:depositos,id',
            'numero_documento' => 'nullable|string|max:80',
            'observacoes' => 'nullable|string|max:1000',
            'itens' => 'required|array|min:1',
            'itens.*.ordem_item_id' => 'required|exists:plugin_compras_ordens_itens,id',
            'itens.*.quantidade_recebida' => 'required|numeric|min:0.0001',
            'itens.*.preco_unitario' => 'nullable|numeric|min:0',
            'itens.*.lote' => 'nullable|string|max:120',
            'itens.*.validade' => 'nullable|date',
        ]);

        try {
            $service->registrarRecebimento($ordem, $validated, auth()->id());
            return redirect()->route('plugin.compras.ordens.show', $ordem)->with('success', 'Recebimento registrado e estoque atualizado.');
        } catch (\Throwable $e) {
            return back()->with('error', $e->getMessage())->withInput();
        }
    }
}
