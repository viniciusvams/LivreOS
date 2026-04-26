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

use App\Http\Requests\ClienteRequest;
use App\Models\AuditAcessoCliente;
use App\Models\CategoriaDocumento;
use App\Models\Cliente;
use App\Models\Documento;
use App\Models\GrupoEconomico;
use App\Models\PropostaComercial;
use App\Models\User;
use App\Models\PedidoVenda;
use App\Services\AuditCancelExcluirService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ClienteController extends Controller
{
    public function index(Request $request)
    {
        $this->authorizePermission('view-clients');
        $this->registrarAuditAcessoCliente(
            null,
            'acesso_index',
            'Acesso à listagem de clientes'
        );
        $query = Cliente::with(['enderecos', 'contatos', 'socios']);

        // Filtros
        if ($request->filled('tipo_pessoa')) {
            $query->where('tipo_pessoa', $request->tipo_pessoa);
        }

        if ($request->filled('nome')) {
            $nome = trim((string) $request->nome);
            $query->where(function ($q) use ($nome) {
                $q->where('nome', 'like', '%'.$nome.'%')
                    ->orWhere('razao_social', 'like', '%'.$nome.'%')
                    ->orWhereHas('contatos', function ($cq) use ($nome) {
                        $cq->where('nome', 'like', '%'.$nome.'%')
                            ->orWhere('telefone', 'like', '%'.$nome.'%')
                            ->orWhere('telefone2', 'like', '%'.$nome.'%')
                            ->orWhere('email', 'like', '%'.$nome.'%')
                            ->orWhere('email2', 'like', '%'.$nome.'%');
                    });
            });
        }

        if ($request->filled('documento')) {
            $query->where(function ($q) use ($request) {
                $q->where('cpf', 'like', '%'.$request->documento.'%')
                    ->orWhere('cnpj', 'like', '%'.$request->documento.'%')
                    ->orWhere('documento_estrangeiro', 'like', '%'.$request->documento.'%');
            });
        }

        if ($request->filled('busca_geral')) {
            $busca = trim((string) $request->busca_geral);
            $query->where(function ($q) use ($busca) {
                $q->where('nome', 'like', '%'.$busca.'%')
                    ->orWhere('razao_social', 'like', '%'.$busca.'%')
                    ->orWhere('cpf', 'like', '%'.$busca.'%')
                    ->orWhere('cnpj', 'like', '%'.$busca.'%')
                    ->orWhere('documento_estrangeiro', 'like', '%'.$busca.'%')
                    ->orWhere('rg', 'like', '%'.$busca.'%')
                    ->orWhere('inscricao_estadual', 'like', '%'.$busca.'%')
                    ->orWhere('inscricao_municipal', 'like', '%'.$busca.'%')
                    ->orWhere('observacoes', 'like', '%'.$busca.'%')
                    ->orWhereHas('contatos', function ($cq) use ($busca) {
                        $cq->where('nome', 'like', '%'.$busca.'%')
                            ->orWhere('telefone', 'like', '%'.$busca.'%')
                            ->orWhere('telefone2', 'like', '%'.$busca.'%')
                            ->orWhere('email', 'like', '%'.$busca.'%')
                            ->orWhere('email2', 'like', '%'.$busca.'%')
                            ->orWhere('cargo', 'like', '%'.$busca.'%');
                    })
                    ->orWhereHas('enderecos', function ($eq) use ($busca) {
                        $eq->where('cep', 'like', '%'.$busca.'%')
                            ->orWhere('logradouro', 'like', '%'.$busca.'%')
                            ->orWhere('numero', 'like', '%'.$busca.'%')
                            ->orWhere('complemento', 'like', '%'.$busca.'%')
                            ->orWhere('bairro', 'like', '%'.$busca.'%')
                            ->orWhere('cidade', 'like', '%'.$busca.'%')
                            ->orWhere('estado', 'like', '%'.$busca.'%');
                    });
            });
        }

        $query = $this->applyEntityQuery($query->orderBy('nome'), 'cliente');
        $clientes = $query->paginate(15);

        return erp_view('clientes.index', [
            'title' => 'Cadastro de Clientes',
            'clientes' => $clientes,
        ]);
    }

    protected function queryClientesComFiltros(Request $request)
    {
        $query = Cliente::with(['enderecos']);
        if ($request->filled('tipo_pessoa')) {
            $query->where('tipo_pessoa', $request->tipo_pessoa);
        }
        if ($request->filled('nome')) {
            $nome = trim((string) $request->nome);
            $query->where(function ($q) use ($nome) {
                $q->where('nome', 'like', '%'.$nome.'%')
                    ->orWhere('razao_social', 'like', '%'.$nome.'%')
                    ->orWhereHas('contatos', function ($cq) use ($nome) {
                        $cq->where('nome', 'like', '%'.$nome.'%')
                            ->orWhere('telefone', 'like', '%'.$nome.'%')
                            ->orWhere('telefone2', 'like', '%'.$nome.'%')
                            ->orWhere('email', 'like', '%'.$nome.'%')
                            ->orWhere('email2', 'like', '%'.$nome.'%');
                    });
            });
        }
        if ($request->filled('documento')) {
            $query->where(function ($q) use ($request) {
                $q->where('cpf', 'like', '%'.$request->documento.'%')
                    ->orWhere('cnpj', 'like', '%'.$request->documento.'%')
                    ->orWhere('documento_estrangeiro', 'like', '%'.$request->documento.'%');
            });
        }

        if ($request->filled('busca_geral')) {
            $busca = trim((string) $request->busca_geral);
            $query->where(function ($q) use ($busca) {
                $q->where('nome', 'like', '%'.$busca.'%')
                    ->orWhere('razao_social', 'like', '%'.$busca.'%')
                    ->orWhere('cpf', 'like', '%'.$busca.'%')
                    ->orWhere('cnpj', 'like', '%'.$busca.'%')
                    ->orWhere('documento_estrangeiro', 'like', '%'.$busca.'%')
                    ->orWhere('rg', 'like', '%'.$busca.'%')
                    ->orWhere('inscricao_estadual', 'like', '%'.$busca.'%')
                    ->orWhere('inscricao_municipal', 'like', '%'.$busca.'%')
                    ->orWhere('observacoes', 'like', '%'.$busca.'%')
                    ->orWhereHas('contatos', function ($cq) use ($busca) {
                        $cq->where('nome', 'like', '%'.$busca.'%')
                            ->orWhere('telefone', 'like', '%'.$busca.'%')
                            ->orWhere('telefone2', 'like', '%'.$busca.'%')
                            ->orWhere('email', 'like', '%'.$busca.'%')
                            ->orWhere('email2', 'like', '%'.$busca.'%')
                            ->orWhere('cargo', 'like', '%'.$busca.'%');
                    })
                    ->orWhereHas('enderecos', function ($eq) use ($busca) {
                        $eq->where('cep', 'like', '%'.$busca.'%')
                            ->orWhere('logradouro', 'like', '%'.$busca.'%')
                            ->orWhere('numero', 'like', '%'.$busca.'%')
                            ->orWhere('complemento', 'like', '%'.$busca.'%')
                            ->orWhere('bairro', 'like', '%'.$busca.'%')
                            ->orWhere('cidade', 'like', '%'.$busca.'%')
                            ->orWhere('estado', 'like', '%'.$busca.'%');
                    });
            });
        }

        return $this->applyEntityQuery($query->orderBy('nome'), 'cliente');
    }

    public function export(Request $request): StreamedResponse
    {
        $this->authorizePermission('view-clients');
        $clientes = $this->queryClientesComFiltros($request)->limit(10000)->get();
        $filename = 'clientes_'.now()->format('Y-m-d_His').'.csv';
        $headers = ['Content-Type' => 'text/csv; charset=UTF-8', 'Content-Disposition' => 'attachment; filename="'.$filename.'"'];

        return response()->streamDownload(function () use ($clientes) {
            $out = fopen('php://output', 'w');
            fprintf($out, chr(0xEF).chr(0xBB).chr(0xBF));
            fputcsv($out, ['Nome', 'Razão Social', 'Tipo', 'Documento', 'Cidade/UF'], ';');
            foreach ($clientes as $c) {
                $endereco = $c->enderecos->first();
                $cidadeUf = $endereco ? ($endereco->cidade ?? '').'/'.($endereco->estado ?? '') : '';
                fputcsv($out, [
                    $c->nome,
                    $c->razao_social ?? '',
                    $c->tipo_pessoa_texto,
                    $c->documento_principal ?? '',
                    $cidadeUf,
                ], ';');
            }
            fclose($out);
        }, $filename, $headers);
    }

    public function exportPdf(Request $request)
    {
        $this->authorizePermission('view-clients');
        $clientes = $this->queryClientesComFiltros($request)->limit(500)->get();
        $html = view('clientes.pdf', ['clientes' => $clientes])->render();
        $pdf = Pdf::loadHTML($html);
        $pdf->setPaper('A4', 'landscape');
        $pdf->setOption('margin-top', '10mm');
        $pdf->setOption('margin-bottom', '10mm');
        $pdf->setOption('margin-left', '10mm');
        $pdf->setOption('margin-right', '10mm');

        return $pdf->download('clientes_'.now()->format('Y-m-d_His').'.pdf');
    }

    public function create()
    {
        $this->authorizePermission('create-clients');
        $categorias = CategoriaDocumento::where('ativo', true)->orderBy('nome')->get();
        $vendedores = User::orderBy('name')->get();
        $gruposEconomicos = GrupoEconomico::where('ativo', true)->orderBy('nome')->get();
        $matrizes = Cliente::where('tipo_pessoa', 'J')->orderBy('nome')->get();

        return erp_view('clientes.create', [
            'title' => 'Novo Cliente',
            'categorias' => $categorias,
            'vendedores' => $vendedores,
            'gruposEconomicos' => $gruposEconomicos,
            'matrizes' => $matrizes,
        ]);
    }

    public function store(ClienteRequest $request)
    {
        $this->authorizePermission('create-clients');
        $data = $request->validated();

        // Checkboxes não são enviados quando desmarcados
        $data['condominio'] = $request->boolean('condominio');
        $data['optante_simples_nacional'] = $request->boolean('optante_simples_nacional');
        $data['ignorar_im_nfse'] = $request->boolean('ignorar_im_nfse');
        $data['produtor_rural'] = $request->boolean('produtor_rural');
        $data['bloqueado_vendas'] = $request->boolean('bloqueado_vendas');

        // Campos nullable: evitar string vazia no banco
        $data['vendedor_id'] = $request->filled('vendedor_id') ? (int) $request->vendedor_id : null;
        $data['grupo_economico_id'] = $request->filled('grupo_economico_id') ? (int) $request->grupo_economico_id : null;
        $data['matriz_id'] = $request->filled('matriz_id') ? (int) $request->matriz_id : null;

        // Formatar CPF/CNPJ
        if (isset($data['cpf'])) {
            $data['cpf'] = $this->formatarCPF($data['cpf']);
        }
        if (isset($data['cnpj'])) {
            $data['cnpj'] = $this->formatarCNPJ($data['cnpj']);
        }

        // Criar cliente
        $cliente = Cliente::create($data);

        // Salvar endereços (checkboxes principal/cobranca/entrega não vêm quando desmarcados)
        if ($request->has('enderecos')) {
            foreach ($request->enderecos as $enderecoData) {
                $payload = Arr::only($enderecoData, ['cep', 'logradouro', 'numero', 'complemento', 'bairro', 'cidade', 'estado', 'pais', 'inscricao_produtor_rural']);
                $payload['principal'] = ! empty($enderecoData['principal']);
                $payload['cobranca'] = ! empty($enderecoData['cobranca']);
                $payload['entrega'] = ! empty($enderecoData['entrega']);
                $cliente->enderecos()->create($payload);
            }
        }

        // Salvar contatos
        if ($request->has('contatos')) {
            foreach ($request->contatos as $contatoData) {
                $contatoData['telefone_whatsapp'] = ! empty($contatoData['telefone_whatsapp']);
                $cliente->contatos()->create($contatoData);
            }
        }

        // Salvar sócios
        $socios = $this->filtrarSocios($request->input('socios', []));
        if (! empty($socios)) {
            foreach ($socios as $socioData) {
                $cliente->socios()->create($socioData);
            }
        }

        // Upload de documentos
        if ($request->hasFile('documentos')) {
            $this->processarDocumentos($cliente, $request->file('documentos'), $request);
        }

        return redirect()->route('clientes.index')
            ->with('success', 'Cliente cadastrado com sucesso!');
    }

    public function show(Cliente $cliente)
    {
        $this->authorizePermission('view-clients');
        $cliente->load([
            'enderecos', 'contatos', 'documentos', 'socios', 'vendedor', 'grupoEconomico', 'matriz', 'filiais',
            'equipamentos',
            'ordensServico' => fn ($q) => $q->with('equipamento')->orderByDesc('created_at')->limit(100),
        ]);

        // Lista de vendas: pedidos de venda vinculados ao cliente.
        // (Não existe relação direta no model Cliente; carregamos via query para manter a tela leve.)
        $vendas = PedidoVenda::query()
            ->where('cliente_id', $cliente->id)
            ->with(['vendedor'])
            ->orderByDesc('data_pedido')
            ->limit(100)
            ->get();
        $cliente->setRelation('vendas', $vendas);

        // Lista de propostas comerciais vinculadas ao cliente.
        $propostas = PropostaComercial::query()
            ->where('cliente_id', $cliente->id)
            ->with(['vendedor'])
            ->orderByDesc('data_emissao')
            ->limit(100)
            ->get();
        $cliente->setRelation('propostas', $propostas);

        $this->registrarAuditAcessoCliente(
            $cliente,
            'acesso_show',
            'Cliente visualizado na tela de detalhes'
        );

        return erp_view('clientes.show', [
            'title' => 'Detalhes do Cliente',
            'cliente' => $cliente,
        ]);
    }

    public function edit(Cliente $cliente)
    {
        $this->authorizePermission('edit-clients');
        $cliente->load([
            'enderecos',
            'documentos',
            'socios',
            'contatos' => function ($query) {
                $query->withCount('produtoFornecedores');
            },
        ]);
        $categorias = CategoriaDocumento::where('ativo', true)->orderBy('nome')->get();
        $vendedores = User::orderBy('name')->get();
        $gruposEconomicos = GrupoEconomico::where('ativo', true)->orderBy('nome')->get();
        $matrizes = Cliente::where('tipo_pessoa', 'J')
            ->where('id', '!=', $cliente->id)
            ->orderBy('nome')
            ->get();
        $this->registrarAuditAcessoCliente(
            $cliente,
            'acesso_edit',
            'Cliente acessado na tela de edição'
        );

        return erp_view('clientes.edit', [
            'title' => 'Editar Cliente',
            'cliente' => $cliente,
            'categorias' => $categorias,
            'vendedores' => $vendedores,
            'gruposEconomicos' => $gruposEconomicos,
            'matrizes' => $matrizes,
        ]);
    }

    public function update(ClienteRequest $request, Cliente $cliente)
    {
        $this->authorizePermission('edit-clients');
        // Verificar se pode alterar tipo de pessoa
        if ($cliente->bloqueado_alteracao_tipo && $request->tipo_pessoa != $cliente->tipo_pessoa) {
            return redirect()->back()
                ->with('error', 'Não é possível alterar o tipo de pessoa após a primeira movimentação.');
        }

        $data = $request->validated();

        // Checkboxes não são enviados quando desmarcados; garantir que os booleanos sejam persistidos
        $data['condominio'] = $request->boolean('condominio');
        $data['optante_simples_nacional'] = $request->boolean('optante_simples_nacional');
        $data['ignorar_im_nfse'] = $request->boolean('ignorar_im_nfse');
        $data['produtor_rural'] = $request->boolean('produtor_rural');
        $data['bloqueado_vendas'] = $request->boolean('bloqueado_vendas');

        // Campos nullable: evitar string vazia no banco
        $data['vendedor_id'] = $request->filled('vendedor_id') ? (int) $request->vendedor_id : null;
        $data['grupo_economico_id'] = $request->filled('grupo_economico_id') ? (int) $request->grupo_economico_id : null;
        $data['matriz_id'] = $request->filled('matriz_id') ? (int) $request->matriz_id : null;

        // Formatar CPF/CNPJ
        if (isset($data['cpf'])) {
            $data['cpf'] = $this->formatarCPF($data['cpf']);
        }
        if (isset($data['cnpj'])) {
            $data['cnpj'] = $this->formatarCNPJ($data['cnpj']);
        }

        // Atualizar cliente
        $cliente->update($data);

        // Atualizar endereços (checkboxes principal/cobranca/entrega não vêm quando desmarcados)
        if ($request->has('enderecos')) {
            $cliente->enderecos()->delete();
            foreach ($request->enderecos as $enderecoData) {
                $payload = Arr::only($enderecoData, ['cep', 'logradouro', 'numero', 'complemento', 'bairro', 'cidade', 'estado', 'pais', 'inscricao_produtor_rural']);
                $payload['principal'] = ! empty($enderecoData['principal']);
                $payload['cobranca'] = ! empty($enderecoData['cobranca']);
                $payload['entrega'] = ! empty($enderecoData['entrega']);
                $cliente->enderecos()->create($payload);
            }
        }

        // Atualizar contatos (editar existentes e proteger fornecedores vinculados)
        if ($request->has('contatos')) {
            $contatosInput = $request->input('contatos', []);
            $idsMantidos = [];

            foreach ($contatosInput as $contatoData) {
                $payload = Arr::only($contatoData, [
                    'nome',
                    'telefone',
                    'telefone_whatsapp',
                    'telefone2',
                    'email',
                    'email2',
                    'cargo',
                ]);
                $payload['principal'] = ! empty($contatoData['principal']);
                $payload['cobranca'] = ! empty($contatoData['cobranca']);
                $payload['is_fornecedor'] = false;
                $payload['telefone_whatsapp'] = ! empty($contatoData['telefone_whatsapp']);

                if (! empty($contatoData['id'])) {
                    $contatoExistente = $cliente->contatos()->whereKey($contatoData['id'])->first();
                    if ($contatoExistente) {
                        $contatoExistente->update($payload);
                        $idsMantidos[] = $contatoExistente->id;

                        continue;
                    }
                }

                $novo = $cliente->contatos()->create($payload);
                $idsMantidos[] = $novo->id;
            }

            $contatosParaRemover = $cliente->contatos()
                ->where(function ($q) {
                    $q->where('is_fornecedor', false)
                        ->orWhereNull('is_fornecedor');
                })
                ->whereDoesntHave('produtoFornecedores');

            if (! empty($idsMantidos)) {
                $contatosParaRemover->whereNotIn('id', $idsMantidos);
            }

            $contatosParaRemover->delete();
        }

        // Sócios: apenas PJ mantém cadastro; PF/E remove vínculos (formulário não envia bloco desabilitado)
        if (($data['tipo_pessoa'] ?? '') === 'J') {
            $cliente->socios()->delete();
            $socios = $this->filtrarSocios($request->input('socios', []));
            if (! empty($socios)) {
                foreach ($socios as $socioData) {
                    $cliente->socios()->create($socioData);
                }
            }
        } else {
            $cliente->socios()->delete();
        }

        // Upload de novos documentos
        if ($request->hasFile('documentos')) {
            $this->processarDocumentos($cliente, $request->file('documentos'), $request);
        }

        // Atualizar tags/descrições de documentos existentes
        $documentosExistentes = $request->input('documentos_existentes', []);
        if (! empty($documentosExistentes)) {
            foreach ($documentosExistentes as $documentoId => $dados) {
                $documento = $cliente->documentos()->whereKey($documentoId)->first();
                if ($documento) {
                    $documento->update([
                        'categoria' => $dados['categoria'] ?? null,
                        'tags' => $dados['tags'] ?? null,
                        'descricao' => $dados['descricao'] ?? null,
                    ]);
                }
            }
        }

        return redirect()->route('clientes.index')
            ->with('success', 'Cliente atualizado com sucesso!');
    }

    /**
     * Exclui vários clientes selecionados (mesma permissão e auditoria que destroy).
     * Só afeta registros visíveis ao utilizador (entity.index.query).
     */
    public function bulkDestroy(Request $request, AuditCancelExcluirService $auditService)
    {
        $this->authorizePermission('delete-clients');
        if (! $auditService->canExcluir(auth()->user(), 'cliente')) {
            abort(403, 'Você não tem permissão para excluir clientes.');
        }

        $csv = (string) $request->input('cliente_ids_csv', '');
        $ids = array_values(array_unique(array_filter(
            array_map(static fn ($v) => (int) trim((string) $v), explode(',', $csv)),
            static fn ($v) => $v > 0
        )));

        $request->merge(['cliente_ids' => $ids]);

        $this->validateWithFilters($request, [
            'cliente_ids' => 'required|array|min:1',
            'cliente_ids.*' => 'integer|distinct|exists:clientes,id',
            'motivo' => 'required|string|min:10',
        ], [], ['motivo' => 'motivo da exclusão']);

        $query = Cliente::query()
            ->with('documentos')
            ->whereIn('id', $request->input('cliente_ids', []));
        $query = $this->applyEntityQuery($query, 'cliente');
        $clientes = $query->get();

        $solicitados = count($request->input('cliente_ids', []));
        if ($clientes->isEmpty()) {
            return redirect()->back()->with('error', 'Nenhum dos clientes selecionados pode ser excluído (sem permissão de acesso ou registros inválidos).');
        }

        DB::transaction(function () use ($clientes, $request, $auditService): void {
            foreach ($clientes as $cliente) {
                $this->excluirClienteComAuditoria($cliente, (string) $request->input('motivo'), $request, $auditService);
            }
        });

        $msg = $clientes->count() === 1
            ? '1 cliente excluído com sucesso.'
            : $clientes->count().' clientes excluídos com sucesso.';
        if ($clientes->count() < $solicitados) {
            $msg .= ' '.($solicitados - $clientes->count()).' não estavam ao seu alcance e foram ignorados.';
        }

        return redirect()->route('clientes.index')->with('success', $msg);
    }

    public function destroy(Request $request, Cliente $cliente, AuditCancelExcluirService $auditService)
    {
        $this->authorizePermission('delete-clients');
        if (! $auditService->canExcluir(auth()->user(), 'cliente')) {
            abort(403, 'Você não tem permissão para excluir clientes.');
        }

        $this->validateWithFilters($request, [
            'motivo' => 'required|string|min:10',
        ], [], ['motivo' => 'motivo da exclusão']);

        $cliente->loadMissing('documentos');
        $this->excluirClienteComAuditoria($cliente, (string) $request->input('motivo'), $request, $auditService);

        return redirect()->route('clientes.index')
            ->with('success', 'Cliente excluído com sucesso!');
    }

    protected function excluirClienteComAuditoria(
        Cliente $cliente,
        string $motivo,
        Request $request,
        AuditCancelExcluirService $auditService
    ): void {
        $descricao = $cliente->nome ?? $cliente->razao_social ?? 'Cliente #'.$cliente->id;
        $entityId = $cliente->id;

        foreach ($cliente->documentos as $documento) {
            if (Storage::disk('public')->exists($documento->caminho_arquivo)) {
                Storage::disk('public')->delete($documento->caminho_arquivo);
            }
        }

        $cliente->delete();

        $auditService->log('excluir', 'cliente', $entityId, $descricao, $motivo, $request);
    }

    public function downloadDocumento($documentoId)
    {
        $this->authorizePermission('view-clients');
        $documento = Documento::findOrFail($documentoId);

        if (! Storage::disk('public')->exists($documento->caminho_arquivo)) {
            abort(404, 'Arquivo não encontrado.');
        }

        return Storage::disk('public')->download($documento->caminho_arquivo, $documento->nome_arquivo);
    }

    public function deletarDocumento($documentoId)
    {
        $this->authorizePermission('edit-clients');
        $documento = Documento::findOrFail($documentoId);

        if (Storage::disk('public')->exists($documento->caminho_arquivo)) {
            Storage::disk('public')->delete($documento->caminho_arquivo);
        }

        $documento->delete();

        return redirect()->back()
            ->with('success', 'Documento excluído com sucesso!');
    }

    public function quickStore(Request $request)
    {
        $this->authorizePermission('create-clients');
        $data = $this->validateWithFilters($request, [
            'nome' => 'required|string|max:100',
            'tipo_pessoa' => 'required|string|in:F,J',
            'cpf' => ['nullable', 'string', 'max:20', function ($attribute, $value, $fail) {
                if (! $value) {
                    return;
                }
                if (! $this->validarCPFNumerico($value)) {
                    $fail('O CPF informado é inválido.');
                }
            }],
            'cnpj' => ['nullable', 'string', 'max:20', function ($attribute, $value, $fail) {
                if (! $value) {
                    return;
                }
                if (! $this->validarCNPJAlfanumerico($value)) {
                    $fail('O CNPJ informado é inválido.');
                }
            }],
            'contato_nome' => 'nullable|string|max:100',
            'contato_telefone' => 'nullable|string|max:20',
            'contato_email' => 'nullable|email|max:255',
            'contato_whatsapp' => 'nullable|boolean',
            'cep' => 'nullable|string|max:10',
            'logradouro' => 'nullable|string|max:255',
            'numero' => 'nullable|string|max:20',
            'complemento' => 'nullable|string|max:100',
            'bairro' => 'nullable|string|max:100',
            'cidade' => 'nullable|string|max:100',
            'estado' => 'nullable|string|max:2',
        ]);

        $cliente = Cliente::create([
            'nome' => $data['nome'],
            'tipo_pessoa' => $data['tipo_pessoa'],
            'pais_origem' => 'Brasil',
            'razao_social' => $data['tipo_pessoa'] === 'J' ? $data['nome'] : null,
            'cpf' => $data['cpf'] ? $this->formatarCPF($data['cpf']) : null,
            'cnpj' => $data['cnpj'] ? $this->formatarCNPJ($data['cnpj']) : null,
        ]);

        if (! empty($data['logradouro']) || ! empty($data['cidade'])) {
            $cliente->enderecos()->create([
                'cep' => $data['cep'] ?? null,
                'logradouro' => $data['logradouro'] ?? null,
                'numero' => $data['numero'] ?? null,
                'complemento' => $data['complemento'] ?? null,
                'bairro' => $data['bairro'] ?? null,
                'cidade' => $data['cidade'] ?? null,
                'estado' => $data['estado'] ?? null,
                'principal' => true,
            ]);
        }

        if (! empty($data['contato_nome']) || ! empty($data['contato_telefone']) || ! empty($data['contato_email'])) {
            $cliente->contatos()->create([
                'nome' => $data['contato_nome'] ?? $data['nome'],
                'telefone' => $data['contato_telefone'] ?? null,
                'email' => $data['contato_email'] ?? null,
                'telefone_whatsapp' => ! empty($data['contato_whatsapp']),
                'principal' => true,
            ]);
        }

        return response()->json([
            'id' => $cliente->id,
            'nome' => $cliente->nome,
            'grupo_economico_id' => $cliente->grupo_economico_id,
        ]);
    }

    protected function registrarAuditAcessoCliente(?Cliente $cliente, string $evento, string $mensagem): void
    {
        if (! Schema::hasTable('audit_acesso_clientes')) {
            return;
        }

        try {
            AuditAcessoCliente::create([
                'user_id' => auth()->id(),
                'cliente_id' => $cliente?->id,
                'evento' => $evento,
                'mensagem' => $mensagem,
                'ip_address' => request()->ip(),
                'user_agent' => request()->userAgent(),
                'metadata' => [
                    'url' => request()->fullUrl(),
                    'rota' => request()->route()?->getName(),
                ],
            ]);
        } catch (\Throwable $e) {
            report($e);
        }
    }

    private function processarDocumentos($cliente, $arquivos, $request)
    {
        $categorias = $request->input('documentos_categoria', []);
        $tags = $request->input('documentos_tags', []);
        $descricoes = $request->input('documentos_descricao', []);

        foreach ($arquivos as $index => $arquivo) {
            if ($arquivo->isValid()) {
                $categoria = $categorias[$index] ?? null;
                $tag = $tags[$index] ?? null;
                $descricao = $descricoes[$index] ?? null;

                $nomeOriginal = $arquivo->getClientOriginalName();
                $nomeArquivo = Str::slug(pathinfo($nomeOriginal, PATHINFO_FILENAME)).'_'.time().'_'.$index.'.'.$arquivo->getClientOriginalExtension();
                $caminho = $arquivo->storeAs('documentos/clientes/'.$cliente->id, $nomeArquivo, 'public');

                $cliente->documentos()->create([
                    'nome_arquivo' => $nomeOriginal,
                    'caminho_arquivo' => $caminho,
                    'tipo_mime' => $arquivo->getMimeType(),
                    'tamanho' => $arquivo->getSize(),
                    'categoria' => $categoria,
                    'tags' => $tag,
                    'descricao' => $descricao,
                ]);
            }
        }
    }

    private function formatarCPF($cpf)
    {
        $cpf = preg_replace('/[^0-9]/', '', $cpf);

        return substr($cpf, 0, 3).'.'.substr($cpf, 3, 3).'.'.substr($cpf, 6, 3).'-'.substr($cpf, 9, 2);
    }

    private function formatarCNPJ($cnpj)
    {
        $alfanumerico = preg_replace('/[^0-9A-Za-z]/', '', $cnpj);

        // Se tiver letras, retorna sem máscara (CNPJ alfanumérico)
        if (preg_match('/[A-Za-z]/', $alfanumerico)) {
            return strtoupper($alfanumerico);
        }

        $cnpj = preg_replace('/[^0-9]/', '', $cnpj);

        return substr($cnpj, 0, 2).'.'.substr($cnpj, 2, 3).'.'.substr($cnpj, 5, 3).'/'.substr($cnpj, 8, 4).'-'.substr($cnpj, 12, 2);
    }

    private function validarCPFNumerico($cpf): bool
    {
        $cpf = preg_replace('/[^0-9]/', '', (string) $cpf);
        if (strlen($cpf) !== 11) {
            return false;
        }
        if (preg_match('/(\d)\1{10}/', $cpf)) {
            return false;
        }

        for ($t = 9; $t < 11; $t++) {
            for ($d = 0, $c = 0; $c < $t; $c++) {
                $d += $cpf[$c] * (($t + 1) - $c);
            }
            $d = ((10 * $d) % 11) % 10;
            if ($cpf[$c] != $d) {
                return false;
            }
        }

        return true;
    }

    private function validarCNPJAlfanumerico(string $cnpj): bool
    {
        $clean = strtoupper(preg_replace('/[^0-9A-Za-z]/', '', $cnpj));
        if (strlen($clean) !== 14) {
            return false;
        }
        if (preg_match('/^([A-Z0-9])\1{13}$/', $clean)) {
            return false;
        }

        $base = substr($clean, 0, 12);
        $dvInformado = substr($clean, 12, 2);
        if (! ctype_digit($dvInformado)) {
            return false;
        }

        $dv1 = $this->calcularDVCNPJAlfanumerico($base);
        $dv2 = $this->calcularDVCNPJAlfanumerico($base.$dv1);

        return $dvInformado === ($dv1.$dv2);
    }

    private function calcularDVCNPJAlfanumerico(string $base): string
    {
        $weights = [5, 4, 3, 2, 9, 8, 7, 6, 5, 4, 3, 2];
        if (strlen($base) === 13) {
            $weights = [6, 5, 4, 3, 2, 9, 8, 7, 6, 5, 4, 3, 2];
        }

        $sum = 0;
        $chars = str_split($base);
        foreach ($chars as $i => $char) {
            $sum += $this->mapCNPJCharToValue($char) * $weights[$i];
        }

        $resto = $sum % 11;
        $dv = ($resto < 2) ? 0 : (11 - $resto);

        return (string) $dv;
    }

    private function mapCNPJCharToValue(string $char): int
    {
        if (ctype_digit($char)) {
            return (int) $char;
        }
        $ord = ord($char);
        if ($ord >= 65 && $ord <= 90) {
            return $ord - 48;
        }

        return 0;
    }

    private function filtrarSocios(array $socios): array
    {
        $filtrados = [];
        foreach ($socios as $socio) {
            $dados = array_filter($socio, function ($value) {
                return $value !== null && $value !== '' && $value !== [];
            });
            if (empty($dados)) {
                continue;
            }

            // Checkboxes não vêm quando desmarcados; sempre definir booleanos
            $socio['socio_administrador'] = ! empty($socio['socio_administrador'] ?? false);
            $socio['possui_restricoes'] = ! empty($socio['possui_restricoes'] ?? false);

            $filtrados[] = $socio;
        }

        return $filtrados;
    }
}
