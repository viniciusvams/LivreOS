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

use App\Http\Requests\OrdemServicoRequest;
use App\Models\Cliente;
use App\Models\Contato;
use App\Models\ContaReceber;
use App\Models\ContaPagar;
use App\Models\Empresa;
use App\Models\Equipamento;
use App\Models\Endereco;
use App\Models\OrdemServico;
use App\Models\OrdemServicoAnexo;
use App\Models\OrdemServicoLog;
use App\Models\OrdemServicoAssinaturaToken;
use App\Models\Produto;
use App\Models\Servico;
use App\Models\Tag;
use App\Models\Tenant;
use App\Models\User;
use App\Models\FormaPagamento;
use App\Models\ContaBancaria;
use App\Models\Adquirente;
use App\Models\MovimentacaoFinanceira;
use App\Services\AdquirenteService;
use App\Services\LimiteDescontoService;
use App\Services\AuditCancelExcluirService;
use App\Services\ConciliacaoService;
use App\Models\BaixaTitulo;
use App\Models\PlanoConta;
use App\Models\Configuracao;
use App\Models\ChecklistModel;
use App\Jobs\EnviarOsEmailJob;
use App\Jobs\EnviarOsWhatsAppJob;
use App\Services\NotificacaoOsEnvioService;
use App\Services\NotificacaoOsService;
use App\Models\ChecklistAnswer;
use App\Models\StatusOs;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\StreamedResponse;

class OrdemServicoController extends Controller
{
    /**
     * Admin (is_admin) ou permissão ordem_servico.editar_encerrada podem alterar OS já encerrada.
     */
    protected function usuarioPodeEditarOsEncerrada(): bool
    {
        $u = auth()->user();

        return $u && $u->hasPermission('ordem_servico.editar_encerrada');
    }

    /**
     * Atualiza Status e/ou Prioridade em massa para várias OS selecionadas.
     *
     * - Recebe: ordem_ids_csv (csv com ids), status (opcional), prioridade (opcional)
     * - Respeita: não alterar OS encerradas
     */
    public function bulkStatusPrioridade(Request $request)
    {
        $csv = (string) $request->input('ordem_ids_csv', '');
        $ids = array_values(array_filter(
            array_map(static fn ($v) => trim((string) $v), explode(',', $csv)),
            static fn ($v) => $v !== ''
        ));

        $request->merge([
            'ordem_ids' => $ids,
        ]);

        $this->validateWithFilters($request, [
            'ordem_ids' => 'required|array|min:1',
            'ordem_ids.*' => 'integer|distinct|exists:ordem_servicos,id',
            'status' => 'nullable|string|max:50',
            'prioridade' => 'nullable|string|in:baixa,normal,alta,critica',
            'tag_ids' => 'nullable|array',
            'tag_ids.*' => 'integer|distinct|exists:tags,id',
        ]);

        $status = filled($request->input('status')) ? (string) $request->input('status') : null;
        $prioridade = filled($request->input('prioridade')) ? (string) $request->input('prioridade') : null;
        $tagIdsRaw = $request->input('tag_ids', []);

        $tagsBloqueadasPorPagamento = ['Pago', 'Pagamento Pendente'];
        $tagsSelecionadas = Tag::whereIn('id', $tagIdsRaw)->get(['id', 'nome']);
        $tagsLiberadas = $tagsSelecionadas->reject(static function ($tag) use ($tagsBloqueadasPorPagamento) {
            return in_array((string) $tag->nome, $tagsBloqueadasPorPagamento, true);
        });

        $tagIdsParaAdicionar = $tagsLiberadas->pluck('id')->values()->all();

        if ($status === null && $prioridade === null && empty($tagIdsParaAdicionar)) {
            return redirect()->back()->with('error', 'Selecione pelo menos Status, Prioridade ou Tags para aplicar.');
        }

        if ($status !== null && in_array($status, ['Encerrado', 'Encerrada', 'Cancelado', 'Cancelada'], true)) {
            return redirect()->back()
                ->with('error', 'Use os botões do sistema para alterar status para Encerrado/Encerrada ou Cancelado/Cancelada.');
        }

        $ordens = OrdemServico::with(['cliente', 'tags'])->whereIn('id', $request->input('ordem_ids', []))->get();

        $total = $ordens->count();
        $alteradas = 0;
        $ignoradoEncerradas = 0;
        $erros = [];

        foreach ($ordens as $ordemServico) {
            try {
                if (in_array($ordemServico->status, ['Encerrado', 'Encerrada'], true)) {
                    ++$ignoradoEncerradas;
                    continue;
                }

                $alterouAtributos = false;
                $alterouQualquer = false;

                if ($status !== null && $ordemServico->status !== $status) {
                    $statusAnterior = $ordemServico->status;
                    if (function_exists('do_action')) {
                        do_action('ordens_servico.status.changing', $ordemServico, $statusAnterior, $status, $request);
                    }

                    $ordemServico->status = $status;
                    $ordemServico->updated_by = auth()->id();
                    $ordemServico->ip_atualizacao = $request->ip();

                    $statusOs = StatusOs::where('nome', $status)->first();
                    if ($statusOs) {
                        if ($statusOs->marca_inicio && ! $ordemServico->real_inicio) {
                            $ordemServico->real_inicio = now();
                        }
                        if ($statusOs->marca_conclusao) {
                            $ordemServico->real_conclusao = $ordemServico->real_conclusao ?: now();
                            $ordemServico->sla_cumprido = $this->calcularSlaCumprido($ordemServico);
                        }
                    }

                    if ($statusOs && $statusOs->marca_conclusao) {
                        $this->baixarEstoque($ordemServico);
                        $this->integrarFinanceiro($ordemServico);
                    }

                    $this->registrarLog(
                        $ordemServico,
                        'status',
                        'Status atualizado em massa',
                        [
                            'antes' => ['status' => $statusAnterior],
                            'depois' => ['status' => $status],
                        ]
                    );

                    if (NotificacaoOsService::deveEnviarEmailNaMudancaStatus()) {
                        $emails = NotificacaoOsService::getEmailsCliente($ordemServico);
                        $assunto = 'OS ' . format_os_display($ordemServico) . ' - Status alterado para ' . $status;
                        foreach ($emails as $email) {
                            NotificacaoOsEnvioService::registrarEDispararEmail($ordemServico, $email, $assunto, null, true, $status);
                        }
                    }
                    if (NotificacaoOsService::deveEnviarWhatsAppNaMudancaStatus()) {
                        NotificacaoOsEnvioService::registrarEDispararWhatsApp($ordemServico, $status);
                    }

                    if (function_exists('do_action')) {
                        do_action('ordens_servico.status.changed', $ordemServico, $statusAnterior, $status, $request);
                    }

                    $alterouAtributos = true;
                    $alterouQualquer = true;
                }

                if ($prioridade !== null && $ordemServico->prioridade !== $prioridade) {
                    $prioridadeAnterior = $ordemServico->prioridade;
                    $ordemServico->prioridade = $prioridade;
                    $ordemServico->updated_by = auth()->id();
                    $ordemServico->ip_atualizacao = $request->ip();

                    $this->registrarLog(
                        $ordemServico,
                        'prioridade',
                        'Prioridade atualizada em massa',
                        [
                            'antes' => ['prioridade' => $prioridadeAnterior],
                            'depois' => ['prioridade' => $prioridade],
                        ]
                    );
                    $alterouAtributos = true;
                    $alterouQualquer = true;
                }

                if (!empty($tagIdsParaAdicionar)) {
                    $tagsAntesIds = $ordemServico->tags->pluck('id')->values()->all();
                    $tagsAntesNomes = $ordemServico->tags->pluck('nome')->values()->all();

                    $idsNovos = array_values(array_diff($tagIdsParaAdicionar, $tagsAntesIds));
                    if (!empty($idsNovos)) {
                        $ordemServico->tags()->syncWithoutDetaching($tagIdsParaAdicionar);

                        $mapAntesPorId = $ordemServico->tags->pluck('nome', 'id')->toArray();
                        $mapNovasPorId = $tagsLiberadas->pluck('nome', 'id')->toArray();

                        $idsDepois = array_values(array_unique(array_merge($tagsAntesIds, $idsNovos)));
                        $tagsDepoisNomes = array_map(static function ($tagId) use ($mapAntesPorId, $mapNovasPorId) {
                            return $mapAntesPorId[$tagId] ?? $mapNovasPorId[$tagId] ?? (string) $tagId;
                        }, $idsDepois);

                        sort($tagsAntesNomes);
                        sort($tagsDepoisNomes);

                        $this->registrarLog(
                            $ordemServico,
                            'tags',
                            'Tags atualizadas em massa',
                            [
                                'tags_antes' => array_values($tagsAntesNomes),
                                'tags_depois' => array_values($tagsDepoisNomes),
                            ]
                        );

                        $alterouQualquer = true;
                    }
                }

                if ($alterouAtributos) {
                    $ordemServico->save();
                }
                if ($alterouQualquer) {
                    ++$alteradas;
                }
            } catch (\Throwable $e) {
                $erros[] = 'OS #' . ($ordemServico->codigo_interno ?? $ordemServico->id) . ': ' . $e->getMessage();
            }
        }

        if (! empty($erros)) {
            return redirect()->back()->with('error', 'Algumas OS não puderam ser atualizadas: ' . implode(' | ', array_slice($erros, 0, 5)));
        }

        $msg = sprintf(
            'Aplicação em massa concluída: %d/%d OS atualizadas%s.',
            $alteradas,
            $total,
            $ignoradoEncerradas > 0 ? ' (ignorada(s) ' . $ignoradoEncerradas . ' encerrada(s))' : ''
        );

        return redirect()->back()->with('success', $msg);
    }

    /**
     * Exclui várias OS selecionadas (mesma permissão e auditoria que destroy).
     * Só afeta registros visíveis ao utilizador (entity.index.query).
     */
    public function bulkDestroy(Request $request, AuditCancelExcluirService $auditService)
    {
        if (! $auditService->canExcluir(auth()->user(), 'ordem_servico')) {
            abort(403, 'Você não tem permissão para excluir ordens de serviço.');
        }

        $csv = (string) $request->input('ordem_ids_csv', '');
        $ids = array_values(array_unique(array_filter(
            array_map(static fn ($v) => (int) trim((string) $v), explode(',', $csv)),
            static fn ($v) => $v > 0
        )));

        $request->merge(['ordem_ids' => $ids]);

        $this->validateWithFilters($request, [
            'ordem_ids' => 'required|array|min:1',
            'ordem_ids.*' => 'integer|distinct|exists:ordem_servicos,id',
            'motivo' => 'required|string|min:10',
        ], [], ['motivo' => 'motivo da exclusão']);

        $query = OrdemServico::query()
            ->with('anexos')
            ->whereIn('id', $request->input('ordem_ids', []));
        $query = $this->applyEntityQuery($query, 'ordem_servico');
        $ordens = $query->get();

        $solicitadas = count($request->input('ordem_ids', []));
        if ($ordens->isEmpty()) {
            return redirect()->back()->with('error', 'Nenhuma das OS selecionadas pode ser excluída (sem permissão de acesso ou registros inválidos).');
        }

        DB::transaction(function () use ($ordens, $request, $auditService): void {
            foreach ($ordens as $ordemServico) {
                $this->excluirOrdemServicoComAuditoria($ordemServico, (string) $request->input('motivo'), $request, $auditService);
            }
        });

        $msg = $ordens->count() === 1
            ? '1 OS excluída com sucesso.'
            : $ordens->count().' OS excluídas com sucesso.';
        if ($ordens->count() < $solicitadas) {
            $msg .= ' '.($solicitadas - $ordens->count()).' não estavam ao seu alcance e foram ignoradas.';
        }

        return redirect()->route('ordens-servico.index')->with('success', $msg);
    }

    public function index(Request $request)
    {
        $query = OrdemServico::with(['cliente', 'unidade', 'tags']);

        if ($request->filled('status')) {
            if ($request->status === 'Encerrada') {
                $query->whereIn('status', ['Encerrado', 'Encerrada']);
            } else {
                $query->where('status', $request->status);
            }
        }
        if ($request->filled('cliente_id')) {
            $query->where('cliente_id', $request->cliente_id);
        }
        if ($request->filled('codigo')) {
            $query->where('codigo_interno', 'like', '%' . $request->codigo . '%');
        }
        if ($request->filled('data_inicio')) {
            $query->whereDate('data_abertura', '>=', $request->data_inicio);
        }
        if ($request->filled('data_fim')) {
            $query->whereDate('data_abertura', '<=', $request->data_fim);
        }
        if ($request->filled('prioridade')) {
            $query->where('prioridade', $request->prioridade);
        }
        if ($request->filled('tecnico_id')) {
            $tecnicoId = (int) $request->tecnico_id;
            if ($tecnicoId > 0) {
                $query->where(function ($q) use ($tecnicoId) {
                    $q->whereHas('tecnicos', fn ($sq) => $sq->where('tecnico_id', $tecnicoId))
                        ->orWhereHas('servicos', fn ($sq) => $sq->where('tecnico_id', $tecnicoId));
                });
            }
        }
        if ($request->filled('sla_filtro')) {
            $slaFiltro = strtolower((string) $request->sla_filtro);
            if ($slaFiltro === 'cumprido') {
                $query->where('sla_cumprido', true);
            } elseif ($slaFiltro === 'nao_cumprido') {
                $query->where('sla_cumprido', false);
            } elseif ($slaFiltro === 'com_sla') {
                $query->whereNotNull('sla_valor');
            } elseif ($slaFiltro === 'sem_sla') {
                $query->whereNull('sla_valor');
            }
        }
        if ($request->filled('tag_id')) {
            $query->whereHas('tags', function($q) use ($request) {
                $q->where('tags.id', $request->tag_id);
            });
        }
        
        // Busca geral em múltiplos campos
        if ($request->filled('busca_geral')) {
            $busca = $request->busca_geral;
            $query->where(function($q) use ($busca) {
                $q->where('codigo_interno', 'like', '%' . $busca . '%')
                  ->orWhere('relato_cliente', 'like', '%' . $busca . '%')
                  ->orWhere('diagnostico_tecnico', 'like', '%' . $busca . '%')
                  ->orWhere('servicos_realizados', 'like', '%' . $busca . '%')
                  ->orWhere('observacoes_internas', 'like', '%' . $busca . '%')
                  ->orWhere('observacoes_cliente', 'like', '%' . $busca . '%')
                  ->orWhere('equipamento_marca', 'like', '%' . $busca . '%')
                  ->orWhere('equipamento_modelo', 'like', '%' . $busca . '%')
                  ->orWhere('equipamento_numero_serie', 'like', '%' . $busca . '%')
                  ->orWhere('equipamento_numero_patrimonio', 'like', '%' . $busca . '%')
                  ->orWhereHas('cliente', function($q) use ($busca) {
                      $q->where('nome', 'like', '%' . $busca . '%')
                        ->orWhere('cpf', 'like', '%' . $busca . '%')
                        ->orWhere('cnpj', 'like', '%' . $busca . '%');
                  });
            });
        }

        $perPage = $request->get('per_page', 15);
        $perPage = in_array($perPage, [5, 10, 15, 25, 50, 100]) ? $perPage : 15;

        $query = $this->applyEntityQuery($query, 'ordem_servico');

        // Ordenação dinâmica por coluna (clicando no cabeçalho da tabela)
        $sortBy = (string) $request->get('sort_by', '');
        $sortDir = strtolower((string) $request->get('sort_dir', 'desc')) === 'asc' ? 'asc' : 'desc';
        $sortable = [
            'codigo',
            'cliente',
            'status',
            'prioridade',
            'abertura',
            'conclusao',
            'total',
        ];

        if (in_array($sortBy, $sortable, true)) {
            switch ($sortBy) {
                case 'codigo':
                    $query->orderBy('numero_sequencial', $sortDir)->orderBy('id', $sortDir);
                    break;
                case 'cliente':
                    $query->orderBy(
                        Cliente::select('nome')
                            ->whereColumn('clientes.id', 'ordem_servicos.cliente_id')
                            ->limit(1),
                        $sortDir
                    )->orderBy('id', 'desc');
                    break;
                case 'status':
                    $query->orderBy('status', $sortDir)->orderBy('id', 'desc');
                    break;
                case 'prioridade':
                    $query->orderBy('prioridade', $sortDir)->orderBy('id', 'desc');
                    break;
                case 'abertura':
                    $query->orderBy('data_abertura', $sortDir)->orderBy('id', 'desc');
                    break;
                case 'conclusao':
                    $query->orderBy('real_conclusao', $sortDir)->orderBy('id', 'desc');
                    break;
                case 'total':
                    $query->orderBy('total_geral', $sortDir)->orderBy('id', 'desc');
                    break;
            }
        } else {
            // Padrão anterior
            $query->orderByDesc('numero_sequencial')->orderByDesc('created_at');
        }

        $ordens = $query->paginate($perPage)->withQueryString();

        $statusOsList = StatusOs::where('ativo', true)->orderBy('ordem')->orderBy('nome')->get();
        $tecnicoIdsOs = DB::table('ordem_servico_tecnicos')
            ->whereNotNull('tecnico_id')
            ->pluck('tecnico_id');
        $tecnicoIdsSrv = DB::table('ordem_servico_servicos')
            ->whereNotNull('tecnico_id')
            ->pluck('tecnico_id');
        $tecnicoIds = $tecnicoIdsOs->merge($tecnicoIdsSrv)->filter()->unique()->values();
        $tecnicos = User::query()->whereIn('id', $tecnicoIds)->orderBy('name')->get(['id', 'name']);

        return erp_view('ordens_servico.index', [
            'title' => 'Ordens de Serviço',
            'ordens' => $ordens,
            'clientes' => Cliente::orderBy('nome')->get(),
            'tags' => Tag::where('ativo', true)->orderByRaw("CASE WHEN tipo = 'padrao' THEN 0 ELSE 1 END")->orderBy('nome')->get(),
            'statusOptions' => $statusOsList->pluck('nome')->toArray(),
            'statusOsPorNome' => $statusOsList->keyBy('nome'),
            'tecnicos' => $tecnicos,
        ]);
    }

    protected function queryOrdensComFiltros(Request $request)
    {
        $query = OrdemServico::with(['cliente', 'tags']);
        if ($request->filled('status')) {
            if ($request->status === 'Encerrada') {
                $query->whereIn('status', ['Encerrado', 'Encerrada']);
            } else {
                $query->where('status', $request->status);
            }
        }
        if ($request->filled('cliente_id')) $query->where('cliente_id', $request->cliente_id);
        if ($request->filled('codigo')) $query->where('codigo_interno', 'like', '%' . $request->codigo . '%');
        if ($request->filled('data_inicio')) $query->whereDate('data_abertura', '>=', $request->data_inicio);
        if ($request->filled('data_fim')) $query->whereDate('data_abertura', '<=', $request->data_fim);
        if ($request->filled('prioridade')) $query->where('prioridade', $request->prioridade);
        if ($request->filled('tecnico_id')) {
            $tecnicoId = (int) $request->tecnico_id;
            if ($tecnicoId > 0) {
                $query->where(function ($q) use ($tecnicoId) {
                    $q->whereHas('tecnicos', fn ($sq) => $sq->where('tecnico_id', $tecnicoId))
                        ->orWhereHas('servicos', fn ($sq) => $sq->where('tecnico_id', $tecnicoId));
                });
            }
        }
        if ($request->filled('sla_filtro')) {
            $slaFiltro = strtolower((string) $request->sla_filtro);
            if ($slaFiltro === 'cumprido') {
                $query->where('sla_cumprido', true);
            } elseif ($slaFiltro === 'nao_cumprido') {
                $query->where('sla_cumprido', false);
            } elseif ($slaFiltro === 'com_sla') {
                $query->whereNotNull('sla_valor');
            } elseif ($slaFiltro === 'sem_sla') {
                $query->whereNull('sla_valor');
            }
        }
        if ($request->filled('tag_id')) {
            $query->whereHas('tags', fn ($q) => $q->where('tags.id', $request->tag_id));
        }
        if ($request->filled('busca_geral')) {
            $busca = $request->busca_geral;
            $query->where(function ($q) use ($busca) {
                $q->where('codigo_interno', 'like', '%' . $busca . '%')
                    ->orWhere('relato_cliente', 'like', '%' . $busca . '%')
                    ->orWhere('diagnostico_tecnico', 'like', '%' . $busca . '%')
                    ->orWhere('servicos_realizados', 'like', '%' . $busca . '%')
                    ->orWhere('observacoes_internas', 'like', '%' . $busca . '%')
                    ->orWhere('observacoes_cliente', 'like', '%' . $busca . '%')
                    ->orWhere('equipamento_marca', 'like', '%' . $busca . '%')
                    ->orWhere('equipamento_modelo', 'like', '%' . $busca . '%')
                    ->orWhere('equipamento_numero_serie', 'like', '%' . $busca . '%')
                    ->orWhere('equipamento_numero_patrimonio', 'like', '%' . $busca . '%')
                    ->orWhereHas('cliente', fn ($q) => $q->where('nome', 'like', '%' . $busca . '%')
                        ->orWhere('cpf', 'like', '%' . $busca . '%')
                        ->orWhere('cnpj', 'like', '%' . $busca . '%'));
            });
        }
        return $this->applyEntityQuery(
            $query->orderByDesc('numero_sequencial')->orderByDesc('created_at'),
            'ordem_servico'
        );
    }

    public function export(Request $request): StreamedResponse
    {
        $ordens = $this->queryOrdensComFiltros($request)->limit(10000)->get();
        $filename = 'ordens_servico_' . now()->format('Y-m-d_His') . '.csv';
        $headers = ['Content-Type' => 'text/csv; charset=UTF-8', 'Content-Disposition' => 'attachment; filename="' . $filename . '"'];
        return response()->streamDownload(function () use ($ordens) {
            $out = fopen('php://output', 'w');
            fprintf($out, chr(0xEF) . chr(0xBB) . chr(0xBF));
            fputcsv($out, ['Código', 'Cliente', 'Status', 'Prioridade', 'Abertura', 'Conclusão', 'Total'], ';');
            foreach ($ordens as $o) {
                fputcsv($out, [
                    format_os_display($o),
                    $o->cliente?->nome ?? '',
                    $o->status ?? '',
                    $o->prioridade ?? '',
                    $o->data_abertura ? $o->data_abertura->format('d/m/Y') : '',
                    $o->real_conclusao ? \Carbon\Carbon::parse($o->real_conclusao)->format('d/m/Y') : ($o->prevista_conclusao ? \Carbon\Carbon::parse($o->prevista_conclusao)->format('d/m/Y') : ''),
                    $o->total_geral ? number_format($o->total_geral, 2, ',', '.') : '',
                ], ';');
            }
            fclose($out);
        }, $filename, $headers);
    }

    public function exportPdf(Request $request)
    {
        $ordens = $this->queryOrdensComFiltros($request)->limit(500)->get();
        $html = view('ordens_servico.pdf', ['ordens' => $ordens])->render();
        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadHTML($html);
        $pdf->setPaper('A4', 'landscape');
        $pdf->setOption('margin-top', '10mm');
        $pdf->setOption('margin-bottom', '10mm');
        $pdf->setOption('margin-left', '10mm');
        $pdf->setOption('margin-right', '10mm');
        return $pdf->download('ordens_servico_' . now()->format('Y-m-d_His') . '.pdf');
    }

    public function buscarClientes(Request $request)
    {
        $termo = $request->get('q', '');
        
        if (empty($termo)) {
            return response()->json([]);
        }
        
        $clientes = Cliente::where(function($q) use ($termo) {
                $q->where('nome', 'like', '%' . $termo . '%')
                  ->orWhere('cpf', 'like', '%' . $termo . '%')
                  ->orWhere('cnpj', 'like', '%' . $termo . '%');
            })
            ->orderBy('nome')
            ->limit(20)
            ->get(['id', 'nome', 'cpf', 'cnpj', 'tipo_pessoa']);

        return response()->json($clientes->map(function($cliente) {
            return [
                'id' => $cliente->id,
                'nome' => $cliente->nome,
                'documento' => $cliente->tipo_pessoa === 'F' ? $cliente->cpf : $cliente->cnpj,
                'text' => $cliente->nome . ($cliente->tipo_pessoa === 'F' && $cliente->cpf ? ' - ' . $cliente->cpf : ($cliente->tipo_pessoa === 'J' && $cliente->cnpj ? ' - ' . $cliente->cnpj : '')),
            ];
        }));
    }

    public function create(Request $request)
    {
        $data = $this->getFormData();
        $ordemDeRef = null;
        if ($request->filled('de_equipamento')) {
            $ordemDeRef = OrdemServico::with(['cliente', 'equipamento'])->find($request->de_equipamento);
        }
        if ($ordemDeRef) {
            $data['ordemDeRef'] = $ordemDeRef;
            $step = (int) $request->get('step', 3);
            $data['startStep'] = $step >= 1 && $step <= 4 ? $step : 3;
        }
        return erp_view('ordens_servico.create', $data);
    }

    public function store(OrdemServicoRequest $request)
    {
        $data = $this->normalizarDados($request);
        $data['created_by'] = auth()->id();
        $data['updated_by'] = auth()->id();
        $data['ip_criacao'] = $request->ip();
        $data['ip_atualizacao'] = $request->ip();
        if (function_exists('apply_filters')) {
            $data = apply_filters('ordens_servico.store.data', $data, $request);
        }

        $os = OrdemServico::create($data);

        $this->processarProdutos($os, $request->input('produtos', []));
        $this->processarServicos($os, $request->input('servicos', []));
        $this->processarTecnicos($os, $request->input('tecnicos', []));
        $this->processarApontamentos($os, $request->input('apontamentos', []));
        $this->processarAnexos($os, $request);
        $this->atualizarTotais($os, $request);
        $statusOs = \App\Models\StatusOs::where('nome', $os->status)->first();
        if ($statusOs && $statusOs->marca_conclusao) {
            $this->baixarEstoque($os);
            $this->integrarFinanceiro($os);
        }
        $this->registrarLog($os, 'criada', 'OS criada', []);
        if (function_exists('do_action')) {
            do_action('ordens_servico.stored', $os, $request, $data);
        }

        $enviarEmail = false;
        if (NotificacaoOsService::getTipoNotificacao() === NotificacaoOsService::TIPO_EMAIL_AUTOMATICO
            && NotificacaoOsService::getEmailAutomaticoAtivo()) {
            $enviarEmail = true;
        } elseif (NotificacaoOsService::getTipoNotificacao() === NotificacaoOsService::TIPO_EMAIL_MANUAL
            && $request->boolean('enviar_email')) {
            $enviarEmail = true;
        }
        if ($enviarEmail) {
            $emails = NotificacaoOsService::getEmailsCliente($os);
            foreach ($emails as $email) {
                NotificacaoOsEnvioService::registrarEDispararEmail($os, $email, 'Ordem de Serviço ' . format_os_display($os), null, true);
            }
        }

        return redirect()->route('ordens-servico.index')
            ->with('success', 'OS cadastrada com sucesso!');
    }

    public function show(OrdemServico $ordemServico)
    {
        $ordemServico->load([
            'cliente',
            'cliente.contatos',
            'unidade',
            'contato',
            'endereco',
            'produtos.produto',
            'servicos.servico',
            'servicos.tecnico',
            'tecnicos.tecnico',
            'apontamentos.tecnico',
            'anexos',
            'logs',
        ]);

        $this->registrarLog($ordemServico, 'acesso_show', 'OS visualizada (tela de detalhes)', [
            'url' => request()->fullUrl(),
            'rota' => 'ordens-servico.show',
        ]);

        return erp_view('ordens_servico.show', [
            'title' => 'Detalhes da OS',
            'ordem' => $ordemServico,
        ]);
    }

    public function print(OrdemServico $ordemServico)
    {
        $ordemServico->load([
            'cliente',
            'unidade',
            'contato',
            'endereco',
            'equipamento',
            'produtos.produto.composicoes.componente',
            'produtos.variacao',
            'servicos.servico',
            'servicos.tecnico',
            'checklists.checklistModel',
        ]);

        return erp_view('ordens_servico.print', [
            'ordem' => $ordemServico,
            'empresa' => Empresa::first(),
            'emitidoEm' => now()->format('d/m/Y \à\s H:i'),
            'userName' => auth()->user()?->name ?? 'Sistema',
            'osImpressaoObservacaoAntesAssinatura' => (string) (Configuracao::getValue('os_impressao_observacao_antes_assinatura', '') ?? ''),
            'osImpressaoObservacaoAntesAssinaturaAtivo' => Configuracao::osImpressaoObservacaoAntesAssinaturaAtivo(),
        ]);
    }

    public function enviarEmail(Request $request, OrdemServico $ordemServico)
    {
        $request->validate([
            'email' => 'required|email',
            'assunto' => 'nullable|string|max:200',
            'mensagem' => 'nullable|string|max:2000',
        ]);

        $email = $request->email;
        $assunto = $request->assunto ?: 'Ordem de Serviço ' . format_os_display($ordemServico);
        $mensagem = $request->mensagem;

        NotificacaoOsEnvioService::registrarEDispararEmail($ordemServico, $email, $assunto, $mensagem, true);

        return redirect()->back()->with('success', 'E-mail será enviado em breve para ' . $email . '. O envio usa fila de processamento.');
    }

    public function enviarWhatsApp(OrdemServico $ordemServico)
    {
        $telefone = NotificacaoOsService::getTelefoneWhatsAppCliente($ordemServico);
        if (!$telefone) {
            return redirect()->back()->with('error', 'Cliente não possui telefone cadastrado para WhatsApp.');
        }
        if (NotificacaoOsService::getWhatsAppModo() === 'manual') {
            $envio = NotificacaoOsEnvioService::registrarWhatsAppManual($ordemServico, $ordemServico->status);
            return redirect()->back()->with('success', 'Mensagem registrada. Copie o texto e envie manualmente pelo WhatsApp.');
        }
        NotificacaoOsEnvioService::registrarEDispararWhatsApp($ordemServico, $ordemServico->status);
        return redirect()->back()->with('success', 'Mensagem WhatsApp será enviada em breve. O envio usa fila de processamento.');
    }

    public function equipamentoEtiqueta(OrdemServico $ordemServico)
    {
        $ordemServico->load(['equipamento', 'cliente']);
        $ordemServico->garantirTokenPublico();

        $urlPublica = url()->route('equipamento.publico', ['token' => $ordemServico->equipamento_publico_token]);
        $qrUrl = 'https://api.qrserver.com/v1/create-qr-code/?size=300x300&data=' . rawurlencode($urlPublica);

        return erp_view('ordens_servico.equipamento_etiqueta', [
            'ordem' => $ordemServico,
            'qrUrl' => $qrUrl,
            'empresa' => Empresa::first(),
        ]);
    }

    /**
     * Duplica uma OS copiando apenas dados básicos: cliente, relato, observações, diagnóstico,
     * serviços realizados, equipamento, itens (produtos/serviços), tipo, origem, prioridade; status = Aberta.
     */
    public function duplicate(OrdemServico $ordemServico)
    {
        $origem = $ordemServico->load(['produtos', 'servicos']);

        $camposOs = [
            'cliente_id',
            'cliente_unidade_id',
            'contato_id',
            'endereco_id',
            'tipo_os',
            'origem_os',
            'prioridade',
            'relato_cliente',
            'observacoes_cliente',
            'diagnostico_tecnico',
            'servicos_realizados',
            'equipamento_id',
            'equipamento_nome',
            'equipamento_marca',
            'equipamento_modelo',
            'equipamento_numero_serie',
            'equipamento_numero_patrimonio',
            'equipamento_acessorios',
            'equipamento_unlock_type',
            'equipamento_unlock_code',
        ];

        $dados = $origem->only($camposOs);
        $dados['status'] = 'Aberta';
        $dados['created_by'] = auth()->id();
        $dados['updated_by'] = auth()->id();
        $dados['ip_criacao'] = request()->ip();
        $dados['ip_atualizacao'] = request()->ip();
        $dados['data_abertura'] = now();
        $dados['estoque_baixado'] = false;
        $dados['financeiro_gerado'] = false;

        $nova = OrdemServico::create($dados);

        foreach ($origem->produtos as $p) {
            $nova->produtos()->create($p->only([
                'produto_id', 'produto_variacao_id', 'descricao', 'quantidade', 'valor_unitario',
                'desconto_valor', 'desconto_percentual', 'tributos', 'impostos_total', 'total',
            ]));
        }

        foreach ($origem->servicos as $s) {
            $nova->servicos()->create($s->only([
                'servico_id', 'descricao', 'cobranca_tipo', 'quantidade', 'quantidade_horas',
                'tecnico_id', 'valor_unitario', 'desconto_valor', 'desconto_percentual', 'total',
            ]));
        }

        $nova->total_produtos = $nova->produtos()->sum('total');
        $nova->total_servicos = $nova->servicos()->sum('total');
        $nova->total_descontos = 0;
        $nova->total_acrescimos = 0;
        $nova->total_impostos = $nova->produtos()->sum('impostos_total');
        $nova->desconto_global = 0;
        $nova->acrescimos_global = 0;
        $nova->impostos_global = 0;
        $nova->total_geral = $nova->total_produtos + $nova->total_servicos;
        $nova->save();

        $this->registrarLog($nova, 'duplicada', 'OS duplicada a partir da ' . format_os_display($origem), [
            'origem_id' => $origem->id,
        ]);

        return redirect()
            ->route('ordens-servico.edit', $nova)
            ->with('success', 'OS duplicada com sucesso. Edite os dados se necessário.');
    }

    public function edit(OrdemServico $ordemServico)
    {
        $ordemServico->load([
            'cliente.contatos',
            'cliente.enderecos',
            'contato',
            'endereco',
            'produtos',
            'servicos',
            'tecnicos',
            'apontamentos',
            'anexos',
            'documentos',
            'assinaturaCliente',
            'assinaturaTecnico',
            'tags',
            'termoGarantia',
            'checklists.checklistModel',
            'contasReceber.formaPagamento',
            'contasReceber.adquirente',
            'contasReceber.contaBancaria',
        ]);

        $this->registrarLog($ordemServico, 'acesso_edit', 'OS acessada (tela de edição)', [
            'url' => request()->fullUrl(),
            'rota' => 'ordens-servico.edit',
        ]);

        // Carregar logs apenas se o usuário tiver permissão
        if (auth()->user()->hasPermission('view-os-history')) {
            $ordemServico->load([
                'logs' => function($query) {
                    $query->with('user')->orderByDesc('created_at');
                },
            ]);
        }

        $assinaturaToken = OrdemServicoAssinaturaToken::where('ordem_servico_id', $ordemServico->id)
            ->where('tipo', 'cliente')
            ->orderByDesc('created_at')
            ->first();

        $empresa = Empresa::first();
        
        return erp_view('ordens_servico.edit', array_merge($this->getFormData(), [
            'ordem' => $ordemServico,
            'assinaturaToken' => $assinaturaToken,
            'assinaturaCliente' => $ordemServico->assinaturaCliente,
            'assinaturaTecnico' => $ordemServico->assinaturaTecnico,
            'assinaturaUsuario' => auth()->user()?->assinatura,
            'tags' => Tag::where('ativo', true)->where('tipo', 'personalizado')->orderBy('nome')->get(),
            'empresa' => $empresa,
            'formasPagamento' => FormaPagamento::where('ativo', true)->orderBy('nome')->get(),
            'contasBancarias' => ContaBancaria::where('ativo', true)->orderBy('nome')->get(),
            'checklistModels' => ChecklistModel::where('ativo', true)->orderBy('nome')->get(),
            'checklists' => $ordemServico->checklists()->with('checklistModel')->get(),
            'podeEditarOsEncerrada' => $this->usuarioPodeEditarOsEncerrada(),
        ]));
    }

    public function update(OrdemServicoRequest $request, OrdemServico $ordemServico)
    {
        $respondJson = $request->wantsJson() || $request->ajax();

        // Verificar se está encerrada
        if (($ordemServico->status === 'Encerrado' || $ordemServico->status === 'Encerrada') && ! $this->usuarioPodeEditarOsEncerrada()) {
            $msg = 'Não é possível editar uma OS encerrada. Você pode apenas reimprimir a OS ou o comprovante.';
            if ($respondJson) {
                return response()->json(['message' => $msg], 422);
            }
            return redirect()->back()->with('error', $msg);
        }

        if ($request->input('save_scope') === 'equipe') {
            return $this->updateEquipeRapido($request, $ordemServico, $respondJson);
        }
        if ($request->input('save_scope') === 'sla') {
            return $this->updateSlaRapido($request, $ordemServico, $respondJson);
        }

        // Validar limites de desconto (para perfis sujeitos; admin não está sujeito)
        $limiteService = app(LimiteDescontoService::class);
        if ($limiteService->usuarioSujeitoALimite()) {
            foreach ($request->input('produtos', []) as $idx => $item) {
                $qtd = (float) ($item['quantidade'] ?? 0);
                $valor = (float) ($item['valor_unitario'] ?? 0);
                $subtotal = $qtd * $valor;
                $descontoPct = (float) ($item['desconto_percentual'] ?? 0);
                $descontoVal = (float) ($item['desconto_valor'] ?? 0);
                $validacao = $limiteService->validarDescontoItem($subtotal, $descontoVal, $descontoPct);
                if (!$validacao['valido']) {
                    $msg = 'Produto ' . ($idx + 1) . ': ' . $validacao['mensagem'];
                    if ($respondJson) {
                        return response()->json(['message' => $msg], 422);
                    }
                    return redirect()->back()->withInput()->with('error', $msg);
                }
            }
            foreach ($request->input('servicos', []) as $idx => $item) {
                $valor = (float) ($item['valor_unitario'] ?? 0);
                $cobrancaTipo = $item['cobranca_tipo'] ?? 'unidade';
                $qtd = (float) ($item['quantidade'] ?? 1);
                $qtdHoras = (float) ($item['quantidade_horas'] ?? 1);
                $base = $valor * ($cobrancaTipo === 'horas' ? max($qtdHoras, 1) : max($qtd, 1));
                $descontoPct = (float) ($item['desconto_percentual'] ?? 0);
                $descontoVal = (float) ($item['desconto_valor'] ?? 0);
                $validacao = $limiteService->validarDescontoItem($base, $descontoVal, $descontoPct);
                if (!$validacao['valido']) {
                    $msg = 'Serviço ' . ($idx + 1) . ': ' . $validacao['mensagem'];
                    if ($respondJson) {
                        return response()->json(['message' => $msg], 422);
                    }
                    return redirect()->back()->withInput()->with('error', $msg);
                }
            }
            // Total da OS a partir dos dados enviados no request (não do banco) para validar desconto global
            $valorTotalOs = $this->calcularTotalOsDoRequest($request);
            $descontoGlobal = (float) $request->input('desconto_global', 0);
            if ($descontoGlobal > 0 && $valorTotalOs > 0) {
                $validacao = $limiteService->validarDescontoGlobal($valorTotalOs, 'valor', $descontoGlobal);
                if (!$validacao['valido']) {
                    $msg = 'Desconto global: ' . $validacao['mensagem'];
                    if ($respondJson) {
                        return response()->json(['message' => $msg], 422);
                    }
                    return redirect()->back()->withInput()->with('error', $msg);
                }
            }
        }

        // Validar bloqueio de alteração de preço (produtos/serviços do cadastro)
        if ($limiteService->deveBloquearAlteracaoPreco()) {
            foreach ($request->input('produtos', []) as $idx => $item) {
                $produtoId = isset($item['produto_id']) ? (int) $item['produto_id'] : null;
                $valorUnitario = (float) ($item['valor_unitario'] ?? 0);
                $validacao = $limiteService->validarPrecoProduto($produtoId, $valorUnitario);
                if (!$validacao['valido']) {
                    $msg = 'Produto ' . ($idx + 1) . ': ' . $validacao['mensagem'];
                    if ($respondJson) {
                        return response()->json(['message' => $msg], 422);
                    }
                    return redirect()->back()->withInput()->with('error', $msg);
                }
            }
            foreach ($request->input('servicos', []) as $idx => $item) {
                $servicoId = isset($item['servico_id']) ? (int) $item['servico_id'] : null;
                $valorUnitario = (float) ($item['valor_unitario'] ?? 0);
                $validacao = $limiteService->validarPrecoServico($servicoId, $valorUnitario);
                if (!$validacao['valido']) {
                    $msg = 'Serviço ' . ($idx + 1) . ': ' . $validacao['mensagem'];
                    if ($respondJson) {
                        return response()->json(['message' => $msg], 422);
                    }
                    return redirect()->back()->withInput()->with('error', $msg);
                }
            }
        }

        // Validar permissão para produto/serviço avulso (itens sem vínculo com cadastro)
        $temProdutoAvulso = collect($request->input('produtos', []))->contains(fn ($p) => empty($p['produto_id']) && !empty(trim($p['descricao'] ?? '')));
        $temServicoAvulso = collect($request->input('servicos', []))->contains(fn ($s) => empty($s['servico_id']) && !empty(trim($s['descricao'] ?? '')));
        if (($temProdutoAvulso || $temServicoAvulso) && !auth()->user()->hasPermission('ordem_servico.produto_servico_avulso')) {
            $msg = 'Você não tem permissão para cadastrar produto ou serviço avulso na OS.';
            if ($respondJson) {
                return response()->json(['message' => $msg], 422);
            }
            return redirect()->back()->withInput()->with('error', $msg);
        }

        // Validar bloqueio de venda com estoque zero (respeita permissão produtos.vender_estoque_zero e admin)
        if (Configuracao::estoqueBloquearVendaZero() && !auth()->user()->hasPermission('produtos.vender_estoque_zero')) {
            foreach ($request->input('produtos', []) as $idx => $item) {
                $produtoId = isset($item['produto_id']) ? (int) $item['produto_id'] : null;
                if (!$produtoId) {
                    continue; // item avulso, não verifica estoque
                }
                $qtdSolicitada = (float) ($item['quantidade'] ?? 0);
                if ($qtdSolicitada <= 0) {
                    continue;
                }
                $produto = Produto::with('variacoes')->find($produtoId);
                if (!$produto) {
                    continue;
                }
                $variacaoId = isset($item['produto_variacao_id']) ? (int) $item['produto_variacao_id'] : null;
                $disponivel = null;
                $nomeItem = $produto->nome ?? 'Produto #' . $produtoId;
                if ($variacaoId) {
                    $variacao = $produto->variacoes->firstWhere('id', $variacaoId);
                    if ($variacao && $variacao->quantidade !== null) {
                        $disponivel = (float) $variacao->quantidade;
                    }
                } else {
                    if ($produto->estoque_quantidade !== null) {
                        $disponivel = (float) $produto->estoque_quantidade;
                    }
                }
                if ($disponivel === null) {
                    continue; // produto sem controle de estoque
                }
                if ($disponivel <= 0 || $qtdSolicitada > $disponivel) {
                    $msg = 'Produto "' . $nomeItem . '" (item ' . ($idx + 1) . '): estoque insuficiente. Disponível: ' . number_format($disponivel, 2, ',', '.') . '.';
                    if ($respondJson) {
                        return response()->json(['message' => $msg], 422);
                    }
                    return redirect()->back()->withInput()->with('error', $msg);
                }
            }
        }

        // Transação + lock na OS: evita duplicar itens quando dois salvamentos (ex.: double-click) correm
        // em paralelo (ambos dão DELETE e depois INSERT, somando o dobro de linhas).
        DB::beginTransaction();
        try {
            $ordemServico = OrdemServico::whereKey($ordemServico->id)->lockForUpdate()->firstOrFail();

        // Capturar estado antes das alterações (inclui filhos da OS)
        $ordemServico->loadMissing([
            'produtos.produto',
            'produtos.variacao',
            'servicos.servico',
            'tecnicos.tecnico',
            'apontamentos.tecnico',
            'anexos',
        ]);
        $itensAntesTexto = $this->snapshotItens($ordemServico);
        $equipeAntesTexto = $this->snapshotEquipe($ordemServico);
        $anexosAntesTexto = $this->snapshotAnexos($ordemServico);

        $antes = $ordemServico->getAttributes();
        $tagsAntes = $ordemServico->tags()->where('tipo', 'personalizado')->pluck('tags.id')->toArray();
        
        $data = $this->normalizarDados($request);
        $data['updated_by'] = auth()->id();
        $data['ip_atualizacao'] = $request->ip();
        if (function_exists('apply_filters')) {
            $data = apply_filters('ordens_servico.update.data', $data, $request, $ordemServico);
        }

        $ordemServico->update($data);
        $ordemServico->refresh();

        $this->processarProdutos($ordemServico, $request->input('produtos', []), true);
        $this->processarServicos($ordemServico, $request->input('servicos', []), true);
        $this->processarTecnicos($ordemServico, $request->input('tecnicos', []), true);
        $this->processarApontamentos($ordemServico, $request->input('apontamentos', []), true);
        $this->processarAnexos($ordemServico, $request);
        $this->atualizarTotais($ordemServico, $request);
        
        // Processar tags (apenas personalizadas)
        $tagsAlteradas = false;
        if ($request->has('tags')) {
            $tagsPersonalizadas = Tag::whereIn('id', $request->input('tags', []))
                ->where('tipo', 'personalizado')
                ->pluck('id')
                ->toArray();
            $ordemServico->tags()->sync($tagsPersonalizadas);
            $tagsAlteradas = true;
        } else {
            // Remover apenas tags personalizadas, manter padrão
            $tagsPersonalizadasAtuais = $ordemServico->tags()
                ->where('tipo', 'personalizado')
                ->pluck('tags.id')
                ->toArray();
            if (!empty($tagsPersonalizadasAtuais)) {
                foreach ($tagsPersonalizadasAtuais as $tagId) {
                    $ordemServico->tags()->detach($tagId);
                }
                $tagsAlteradas = true;
            }
        }

        // Capturar estado depois das alterações
        $depois = $ordemServico->fresh()->getAttributes();
        $tagsDepois = $ordemServico->tags()->where('tipo', 'personalizado')->pluck('tags.id')->toArray();

        $ordemServico->loadMissing([
            'produtos.produto',
            'produtos.variacao',
            'servicos.servico',
            'tecnicos.tecnico',
            'apontamentos.tecnico',
            'anexos',
        ]);
        $itensDepoisTexto = $this->snapshotItens($ordemServico);
        $equipeDepoisTexto = $this->snapshotEquipe($ordemServico);
        $anexosDepoisTexto = $this->snapshotAnexos($ordemServico);
        
        // Identificar campos alterados - TODOS os campos editáveis
        $alteracoes = [];
        $camposImportantes = [
            // Campos básicos
            'tipo_os', 'origem_os', 'status', 'prioridade', 'data_abertura',
            // Prazos
            'prevista_inicio', 'prevista_conclusao', 'real_inicio', 'real_conclusao',
            // Cliente e contato
            'cliente_id', 'cliente_unidade_id', 'contato_id', 'endereco_id',
            // Equipamento
            'equipamento_id', 'equipamento_nome', 'equipamento_marca', 'equipamento_modelo',
            'equipamento_numero_serie', 'equipamento_numero_patrimonio', 'equipamento_acessorios',
            'equipamento_unlock_type', 'equipamento_unlock_code',
            // Quilometragem e horímetro
            'quilometragem_entrada', 'quilometragem_saida', 'horimetro_entrada', 'horimetro_saida',
            // Textos e observações
            'relato_cliente', 'diagnostico_tecnico', 'servicos_realizados',
            'observacoes_internas', 'observacoes_cliente',
            // Garantia e contrato
            'garantia_dias', 'garantia_tipo', 'termo_garantia_id', 'contrato_numero', 'contrato_tipo',
            'coberto_por_contrato', 'recorrente', 'recorrencia_periodicidade',
            // SLA
            'sla_valor', 'sla_unidade', 'sla_cumprido',
            // Aprovação
            'aprovado_nome', 'aprovado_em', 'aprovado_canal', 'aprovado_cliente',
            // Totais e valores
            'total_geral', 'total_produtos', 'total_servicos', 'total_descontos',
            'total_acrescimos', 'total_impostos', 'desconto_global', 'acrescimos_global', 'impostos_global'
        ];
        
        foreach ($camposImportantes as $campo) {
            $valorAntes = $antes[$campo] ?? null;
            $valorDepois = $depois[$campo] ?? null;
            
            // Normalizar valores para comparação
            $valorAntesNormalizado = $valorAntes;
            $valorDepoisNormalizado = $valorDepois;
            
            // Para campos de data, converter para string formatada para comparação
            if (in_array($campo, ['data_abertura', 'prevista_inicio', 'prevista_conclusao', 'real_inicio', 'real_conclusao', 'aprovado_em'])) {
                if ($valorAntes) {
                    try {
                        $valorAntesNormalizado = is_string($valorAntes) ? $valorAntes : \Carbon\Carbon::parse($valorAntes)->toDateTimeString();
                    } catch (\Exception $e) {
                        $valorAntesNormalizado = (string)$valorAntes;
                    }
                } else {
                    $valorAntesNormalizado = null;
                }
                if ($valorDepois) {
                    try {
                        $valorDepoisNormalizado = is_string($valorDepois) ? $valorDepois : \Carbon\Carbon::parse($valorDepois)->toDateTimeString();
                    } catch (\Exception $e) {
                        $valorDepoisNormalizado = (string)$valorDepois;
                    }
                } else {
                    $valorDepoisNormalizado = null;
                }
            } 
            // Para campos HTML/texto, comparar conteúdo sem tags
            elseif (in_array($campo, ['relato_cliente', 'diagnostico_tecnico', 'servicos_realizados', 'observacoes_internas', 'observacoes_cliente', 'equipamento_acessorios'])) {
                $valorAntesNormalizado = $valorAntes ? strip_tags((string)$valorAntes) : '';
                $valorDepoisNormalizado = $valorDepois ? strip_tags((string)$valorDepois) : '';
                // Normalizar espaços em branco
                $valorAntesNormalizado = trim(preg_replace('/\s+/', ' ', $valorAntesNormalizado));
                $valorDepoisNormalizado = trim(preg_replace('/\s+/', ' ', $valorDepoisNormalizado));
            }
            // Para campos numéricos
            elseif (in_array($campo, ['quilometragem_entrada', 'quilometragem_saida', 'horimetro_entrada', 'horimetro_saida', 'sla_valor', 'garantia_dias'])) {
                $valorAntesNormalizado = $valorAntes !== null && $valorAntes !== '' ? (float)$valorAntes : null;
                $valorDepoisNormalizado = $valorDepois !== null && $valorDepois !== '' ? (float)$valorDepois : null;
            }
            // Para valores monetários
            elseif (in_array($campo, ['total_geral', 'total_produtos', 'total_servicos', 'total_descontos', 'total_acrescimos', 'total_impostos', 'desconto_global', 'acrescimos_global', 'impostos_global'])) {
                $valorAntesNormalizado = $valorAntes !== null && $valorAntes !== '' ? (float)$valorAntes : null;
                $valorDepoisNormalizado = $valorDepois !== null && $valorDepois !== '' ? (float)$valorDepois : null;
            }
            // Para campos boolean
            elseif (in_array($campo, ['recorrente', 'coberto_por_contrato', 'aprovado_cliente', 'sla_cumprido'])) {
                $valorAntesNormalizado = (bool)$valorAntes;
                $valorDepoisNormalizado = (bool)$valorDepois;
            }
            // Para outros campos, comparar como string
            else {
                $valorAntesNormalizado = $valorAntes !== null ? (string)$valorAntes : '';
                $valorDepoisNormalizado = $valorDepois !== null ? (string)$valorDepois : '';
            }
            
            // Comparar valores normalizados
            if ($valorAntesNormalizado != $valorDepoisNormalizado) {
                $alteracoes[$campo] = [
                    'antes' => $antes[$campo] ?? null, // Manter valor original para exibição
                    'depois' => $depois[$campo] ?? null // Manter valor original para exibição
                ];
            }
        }
        
        // Verificar alterações em tags
        if ($tagsAlteradas || $tagsAntes != $tagsDepois) {
            $alteracoes['tags'] = [
                'antes' => $tagsAntes,
                'depois' => $tagsDepois
            ];
        }
        
        // Registrar log com todas as alterações
        if (!empty($alteracoes)) {
            $this->registrarLog($ordemServico, 'atualizada', 'OS atualizada', [
                'alteracoes' => $alteracoes,
                'ip' => $request->ip(),
                'user_agent' => $request->userAgent(),
            ]);
        }

        // Logs extras para abas que alteram tabelas filhas (Itens/Equipe/Anexos)
        if ($itensAntesTexto !== $itensDepoisTexto) {
            $this->registrarLog($ordemServico, 'itens', 'Itens atualizados', [
                'antes' => ['itens' => $itensAntesTexto],
                'depois' => ['itens' => $itensDepoisTexto],
            ]);
        }
        if ($equipeAntesTexto !== $equipeDepoisTexto) {
            $this->registrarLog($ordemServico, 'equipe', 'Equipe atualizada', [
                'antes' => ['equipe' => $equipeAntesTexto],
                'depois' => ['equipe' => $equipeDepoisTexto],
            ]);
        }
        if ($anexosAntesTexto !== $anexosDepoisTexto) {
            $this->registrarLog($ordemServico, 'anexos', 'Anexos atualizados', [
                'antes' => ['anexos' => $anexosAntesTexto],
                'depois' => ['anexos' => $anexosDepoisTexto],
            ]);
        }

        if ($request->wantsJson() || $request->ajax()) {
            $ordem = $ordemServico->fresh()->load(['anexos', 'cliente', 'contato']);
            $anexos = $ordem->anexos->map(function ($a) {
                $path = $a->caminho_arquivo;
                $nome = $a->nome_arquivo ?? basename($path ?? '');
                $ext = strtolower(pathinfo($nome, PATHINFO_EXTENSION));
                $isImage = in_array($ext, ['jpg', 'jpeg', 'png', 'gif', 'webp', 'bmp', 'svg'], true)
                    || (isset($a->tipo_mime) && str_starts_with((string) $a->tipo_mime, 'image/'));
                return [
                    'id' => $a->id,
                    'nome_arquivo' => $a->nome_arquivo,
                    'caminho_arquivo' => $path,
                    'url' => $path ? route('ordens-servico.anexos.file', $a) : '',
                    'tipo' => $a->tipo,
                    'tags' => $a->tags,
                    'descricao' => $a->descricao,
                    'is_image' => $isImage,
                ];
            });
            $status = $ordem->status;
            $notifPreview = [
                'email_assunto' => NotificacaoOsService::aplicarTemplateTags(NotificacaoOsService::getEmailTemplateAssunto(), $ordem, $status),
                'email_corpo' => NotificacaoOsService::aplicarTemplateTags(NotificacaoOsService::getEmailTemplateCorpo(), $ordem, $status),
                'whatsapp_mensagem' => NotificacaoOsService::aplicarTemplateTags(NotificacaoOsService::getWhatsAppTemplate(), $ordem, $status),
            ];
            if ($ordem->contato) {
                $notifPreview['whatsapp_digits'] = preg_replace('/\D/', '', $ordem->contato->telefone ?? '');
            }
            if (function_exists('do_action')) {
                do_action('ordens_servico.updated', $ordemServico, $request, $data);
            }
            DB::commit();
            return response()->json([
                'message' => 'OS atualizada com sucesso!',
                'ordem' => $ordem,
                'anexos' => $anexos,
                'notificacao_preview' => $notifPreview,
            ]);
        }

        if (function_exists('do_action')) {
            do_action('ordens_servico.updated', $ordemServico, $request, $data);
        }
        DB::commit();
        return redirect()->route('ordens-servico.index')
            ->with('success', 'OS atualizada com sucesso!');
        } catch (\Throwable $e) {
            DB::rollBack();
            throw $e;
        }
    }

    private function updateEquipeRapido(OrdemServicoRequest $request, OrdemServico $ordemServico, bool $respondJson)
    {
        $ordemServico->loadMissing([
            'tecnicos.tecnico',
            'apontamentos.tecnico',
        ]);
        $equipeAntesTexto = $this->snapshotEquipe($ordemServico);

        $ordemServico->fill([
            'updated_by' => auth()->id(),
            'ip_atualizacao' => $request->ip(),
        ]);
        $ordemServico->save();

        $this->processarTecnicos($ordemServico, $request->input('tecnicos', []), true);
        $this->processarApontamentos($ordemServico, $request->input('apontamentos', []), true);

        $ordemServico->load([
            'tecnicos.tecnico',
            'apontamentos.tecnico',
        ]);
        $equipeDepoisTexto = $this->snapshotEquipe($ordemServico);

        if ($equipeAntesTexto !== $equipeDepoisTexto) {
            $this->registrarLog($ordemServico, 'equipe', 'Equipe atualizada', [
                'antes' => ['equipe' => $equipeAntesTexto],
                'depois' => ['equipe' => $equipeDepoisTexto],
            ]);
        }

        if ($respondJson) {
            return response()->json([
                'message' => 'Equipe atualizada com sucesso!',
            ]);
        }

        return redirect()->back()->with('success', 'Equipe atualizada com sucesso!');
    }

    private function updateSlaRapido(OrdemServicoRequest $request, OrdemServico $ordemServico, bool $respondJson)
    {
        $antes = [
            'prevista_inicio' => $ordemServico->prevista_inicio?->toDateTimeString(),
            'prevista_conclusao' => $ordemServico->prevista_conclusao?->toDateTimeString(),
            'real_inicio' => $ordemServico->real_inicio?->toDateTimeString(),
            'real_conclusao' => $ordemServico->real_conclusao?->toDateTimeString(),
            'sla_valor' => $ordemServico->sla_valor,
            'sla_unidade' => $ordemServico->sla_unidade,
            'sla_cumprido' => $ordemServico->sla_cumprido,
        ];

        $ordemServico->fill([
            'prevista_inicio' => $request->filled('prevista_inicio') ? $request->input('prevista_inicio') : null,
            'prevista_conclusao' => $request->filled('prevista_conclusao') ? $request->input('prevista_conclusao') : null,
            'real_inicio' => $request->filled('real_inicio') ? $request->input('real_inicio') : null,
            'real_conclusao' => $request->filled('real_conclusao') ? $request->input('real_conclusao') : null,
            'sla_valor' => $request->filled('sla_valor') ? (int) $request->input('sla_valor') : null,
            'sla_unidade' => $request->filled('sla_unidade') ? (string) $request->input('sla_unidade') : null,
            'updated_by' => auth()->id(),
            'ip_atualizacao' => $request->ip(),
        ]);

        $ordemServico->sla_cumprido = $this->calcularSlaCumprido($ordemServico);
        $ordemServico->save();
        $ordemServico->refresh();

        $depois = [
            'prevista_inicio' => $ordemServico->prevista_inicio?->toDateTimeString(),
            'prevista_conclusao' => $ordemServico->prevista_conclusao?->toDateTimeString(),
            'real_inicio' => $ordemServico->real_inicio?->toDateTimeString(),
            'real_conclusao' => $ordemServico->real_conclusao?->toDateTimeString(),
            'sla_valor' => $ordemServico->sla_valor,
            'sla_unidade' => $ordemServico->sla_unidade,
            'sla_cumprido' => $ordemServico->sla_cumprido,
        ];

        if ($antes != $depois) {
            $this->registrarLog($ordemServico, 'sla', 'SLA e execução atualizados', [
                'antes' => $antes,
                'depois' => $depois,
            ]);
        }

        if ($respondJson) {
            return response()->json([
                'message' => 'SLA atualizado com sucesso!',
                'sla_cumprido' => $ordemServico->sla_cumprido,
            ]);
        }

        return redirect()->back()->with('success', 'SLA atualizado com sucesso!');
    }

    public function updateTags(Request $request, OrdemServico $ordemServico)
    {
        $this->validateWithFilters($request, [
            'tags' => 'nullable|array',
            'tags.*' => 'exists:tags,id',
        ]);

        // Capturar tags antes
        $tagsAntes = $ordemServico->tags()->where('tags.tipo', 'personalizado')->pluck('tags.id')->toArray();
        $tagsAntesNomes = $ordemServico->tags()->where('tags.tipo', 'personalizado')->pluck('tags.nome')->toArray();

        // Filtrar apenas tags personalizadas
        $tagsPersonalizadas = Tag::whereIn('id', $request->input('tags', []))
            ->where('tipo', 'personalizado')
            ->pluck('id')
            ->toArray();
        
        $tagsDepoisNomes = Tag::whereIn('id', $tagsPersonalizadas)->pluck('nome')->toArray();

        $ordemServico->tags()->sync($tagsPersonalizadas);

        // Registrar log de alteração de tags
        if ($tagsAntes != $tagsPersonalizadas) {
            $this->registrarLog($ordemServico, 'tags', 'Tags atualizadas', [
                'tags_antes' => $tagsAntesNomes,
                'tags_depois' => $tagsDepoisNomes,
                'ip' => $request->ip(),
                'user_agent' => $request->userAgent(),
            ]);
        }

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json([
                'message' => 'Tags atualizadas com sucesso!',
                'tags' => $ordemServico->tags()->where('tipo', 'personalizado')->get()->map(function($tag) {
                    return [
                        'id' => $tag->id,
                        'nome' => $tag->nome,
                        'cor_fundo' => $tag->cor_fundo,
                        'cor_fonte' => $tag->cor_fonte,
                    ];
                }),
            ]);
        }

        return redirect()->back()
            ->with('success', 'Tags atualizadas com sucesso!');
    }

    public function destroy(Request $request, OrdemServico $ordemServico, AuditCancelExcluirService $auditService)
    {
        if (! $auditService->canExcluir(auth()->user(), 'ordem_servico')) {
            abort(403, 'Você não tem permissão para excluir ordens de serviço.');
        }

        $this->validateWithFilters($request, [
            'motivo' => 'required|string|min:10',
        ], [], ['motivo' => 'motivo da exclusão']);

        $ordemServico->loadMissing('anexos');
        $this->excluirOrdemServicoComAuditoria($ordemServico, (string) $request->input('motivo'), $request, $auditService);

        return redirect()->route('ordens-servico.index')
            ->with('success', 'OS excluída com sucesso!');
    }

    /**
     * Remove ficheiros de anexo, apaga a OS e regista auditoria (excluir).
     */
    protected function excluirOrdemServicoComAuditoria(
        OrdemServico $ordemServico,
        string $motivo,
        Request $request,
        AuditCancelExcluirService $auditService
    ): void {
        $descricao = $ordemServico->codigo_interno ?? 'OS #'.$ordemServico->id;
        $entityId = $ordemServico->id;

        foreach ($ordemServico->anexos as $anexo) {
            if (Storage::disk('public')->exists($anexo->caminho_arquivo)) {
                Storage::disk('public')->delete($anexo->caminho_arquivo);
            }
        }

        $ordemServico->delete();

        $auditService->log('excluir', 'ordem_servico', $entityId, $descricao, $motivo, $request);
    }

    public function updateStatus(Request $request, OrdemServico $ordemServico)
    {
        if (in_array($ordemServico->status, ['Encerrado', 'Encerrada'], true)) {
            return redirect()->back()->with('error', 'Não é possível alterar o status de uma OS encerrada.');
        }

        $this->validateWithFilters($request, [
            'status' => 'required|string|max:50',
        ]);

        $status = $request->status;
        if (in_array($status, ['Encerrado', 'Encerrada'], true)) {
            return redirect()->back()->with('error', 'O status "Encerrada" é exclusivo do sistema. Use o botão Encerrar para encerrar a OS.');
        }
        $statusAnterior = $ordemServico->status;
        if (function_exists('do_action')) {
            do_action('ordens_servico.status.changing', $ordemServico, $statusAnterior, $status, $request);
        }
        $ordemServico->status = $status;
        $ordemServico->updated_by = auth()->id();
        $ordemServico->ip_atualizacao = $request->ip();

        $statusOs = StatusOs::where('nome', $status)->first();
        if ($statusOs) {
            if ($statusOs->marca_inicio && !$ordemServico->real_inicio) {
                $ordemServico->real_inicio = now();
            }
            if ($statusOs->marca_conclusao) {
                $ordemServico->real_conclusao = $ordemServico->real_conclusao ?: now();
                $ordemServico->sla_cumprido = $this->calcularSlaCumprido($ordemServico);
            }
        }

        $ordemServico->save();
        if ($statusOs && $statusOs->marca_conclusao) {
            $this->baixarEstoque($ordemServico);
            $this->integrarFinanceiro($ordemServico);
        }
        $this->registrarLog(
            $ordemServico,
            'status',
            'Status atualizado',
            [
                'antes' => ['status' => $statusAnterior],
                'depois' => ['status' => $status],
            ]
        );

        if (NotificacaoOsService::deveEnviarEmailNaMudancaStatus()) {
            $emails = NotificacaoOsService::getEmailsCliente($ordemServico);
            $assunto = 'OS ' . format_os_display($ordemServico) . ' - Status alterado para ' . $status;
            foreach ($emails as $email) {
                NotificacaoOsEnvioService::registrarEDispararEmail($ordemServico, $email, $assunto, null, true, $status);
            }
        }
        if (NotificacaoOsService::deveEnviarWhatsAppNaMudancaStatus()) {
            NotificacaoOsEnvioService::registrarEDispararWhatsApp($ordemServico, $status);
        }
        if (function_exists('do_action')) {
            do_action('ordens_servico.status.changed', $ordemServico, $statusAnterior, $status, $request);
        }

        return redirect()->back()->with('success', 'Status atualizado.');
    }

    public function storeAnexos(Request $request, OrdemServico $ordemServico)
    {
        if (in_array($ordemServico->status, ['Encerrado', 'Encerrada'], true) && ! $this->usuarioPodeEditarOsEncerrada()) {
            return response()->json(['message' => 'Não é possível adicionar anexos em OS encerrada.'], 422);
        }

        try {
            $arquivos = $request->file('arquivos');
            if ($arquivos === null) {
                $all = $request->allFiles();
                $arquivos = $all['arquivos'] ?? $all['arquivos[]'] ?? [];
            }
            if (! is_array($arquivos)) {
                $arquivos = $arquivos ? [$arquivos] : [];
            }

            $tipos = (array) $request->input('tipo', []);
            $tags = (array) $request->input('tags', []);
            $descricoes = (array) $request->input('descricao', []);

            $saved = [];
            foreach ($arquivos as $index => $arquivo) {
            if (!$arquivo || !$arquivo->isValid()) {
                continue;
            }
            if ($arquivo->getSize() > 10 * 1024 * 1024) { // 10MB
                continue;
            }

            $nomeOriginal = $arquivo->getClientOriginalName();
            $nomeArquivo = Str::slug(pathinfo($nomeOriginal, PATHINFO_FILENAME))
                . '_' . time() . '_' . $index . '.' . $arquivo->getClientOriginalExtension();
            $caminho = $arquivo->storeAs('ordens-servico/' . $ordemServico->id, $nomeArquivo, 'public');

            $anexo = $ordemServico->anexos()->create([
                'nome_arquivo' => $nomeOriginal,
                'caminho_arquivo' => $caminho,
                'tipo' => $tipos[$index] ?? null,
                'tags' => $tags[$index] ?? null,
                'descricao' => $descricoes[$index] ?? null,
                'tipo_mime' => $arquivo->getMimeType(),
                'tamanho' => $arquivo->getSize(),
                'created_by' => auth()->id(),
            ]);

            $ext = strtolower(pathinfo($nomeOriginal, PATHINFO_EXTENSION));
            $isImage = in_array($ext, ['jpg', 'jpeg', 'png', 'gif', 'webp', 'bmp', 'svg'], true)
                || str_starts_with((string) ($arquivo->getMimeType() ?? ''), 'image/');

            $saved[] = [
                'id' => $anexo->id,
                'nome_arquivo' => $anexo->nome_arquivo,
                'url' => route('ordens-servico.anexos.file', $anexo),
                'tipo' => $anexo->tipo,
                'tags' => $anexo->tags,
                'descricao' => $anexo->descricao,
                'is_image' => $isImage,
            ];
        }

        $anexos = $ordemServico->fresh()->anexos->map(function ($a) {
            $ext = strtolower(pathinfo($a->nome_arquivo ?? '', PATHINFO_EXTENSION));
            $isImg = in_array($ext, ['jpg', 'jpeg', 'png', 'gif', 'webp', 'bmp', 'svg'], true)
                || (isset($a->tipo_mime) && str_starts_with((string) $a->tipo_mime, 'image/'));
            return [
                'id' => $a->id,
                'nome_arquivo' => $a->nome_arquivo,
                'url' => route('ordens-servico.anexos.file', $a),
                'tipo' => $a->tipo,
                'tags' => $a->tags,
                'descricao' => $a->descricao,
                'is_image' => $isImg,
            ];
        })->toArray();

            $msg = count($saved) > 0
                ? 'Anexos salvos com sucesso!'
                : (empty($arquivos) ? 'Nenhum arquivo enviado. Selecione os arquivos e clique em Salvar anexos.' : 'Nenhum arquivo válido (verifique tamanho máximo 10MB).');

            if (count($saved) > 0) {
                $nomesArquivos = array_map(static fn ($a) => $a['nome_arquivo'], $saved);
                $this->registrarLog($ordemServico, 'anexos', 'Anexos adicionados', [
                    'antes' => [
                        'quantidade' => 0,
                        'anexos' => '(nenhum)',
                    ],
                    'depois' => [
                        'quantidade' => count($saved),
                        'anexos' => implode(', ', $nomesArquivos),
                    ],
                ]);
            }

            return response()->json([
                'message' => $msg,
                'anexos' => $anexos,
            ]);
        } catch (\Throwable $e) {
            Log::error('storeAnexos: ' . $e->getMessage(), ['trace' => $e->getTraceAsString()]);

            return response()->json(['message' => 'Erro ao salvar anexos: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Caminho relativo no disco public (importações M-OS ou legado podem gravar "public/..." ou "\" ).
     */
    private function resolveOrdemServicoAnexoPublicPath(?string $caminho): ?string
    {
        if ($caminho === null || trim($caminho) === '') {
            return null;
        }
        $norm = str_replace('\\', '/', trim($caminho));
        if (preg_match('#^https?://#i', $norm)) {
            return null;
        }
        $candidates = array_values(array_unique(array_filter([
            $norm,
            ltrim($norm, '/'),
            str_starts_with($norm, 'public/') ? substr($norm, 7) : null,
            str_starts_with($norm, 'storage/') ? substr($norm, 8) : null,
        ], static fn ($v) => $v !== null && $v !== '')));

        foreach ($candidates as $rel) {
            if (Storage::disk('public')->exists($rel)) {
                return $rel;
            }
        }

        return null;
    }

    public function servirAnexo(OrdemServicoAnexo $anexo)
    {
        $rel = $this->resolveOrdemServicoAnexoPublicPath($anexo->caminho_arquivo);
        if ($rel === null) {
            abort(404);
        }

        return response()->file(Storage::disk('public')->path($rel), [
            'Content-Type' => $anexo->tipo_mime ?? 'application/octet-stream',
            'Content-Disposition' => 'inline; filename="' . ($anexo->nome_arquivo ?? basename($rel)) . '"',
        ]);
    }

    public function destroyAnexo(OrdemServico $ordemServico, OrdemServicoAnexo $anexo)
    {
        if ($anexo->ordem_servico_id !== $ordemServico->id) {
            return response()->json(['message' => 'Anexo não pertence a esta OS.'], 404);
        }
        if (in_array($ordemServico->status, ['Encerrado', 'Encerrada'], true) && ! $this->usuarioPodeEditarOsEncerrada()) {
            return response()->json(['message' => 'Não é possível excluir anexos de OS encerrada.'], 422);
        }

        $relDelete = $this->resolveOrdemServicoAnexoPublicPath($anexo->caminho_arquivo);
        if ($relDelete !== null) {
            Storage::disk('public')->delete($relDelete);
        }
        $nomeArquivo = $anexo->nome_arquivo;
        $anexo->delete();

        $this->registrarLog($ordemServico, 'anexos', 'Anexo excluído', [
            'antes' => [
                'arquivo' => $nomeArquivo,
            ],
            'depois' => [
                'arquivo' => null,
            ],
        ]);

        return response()->json([
            'message' => 'Anexo excluído.',
            'anexos' => $ordemServico->fresh()->anexos->map(function ($a) {
                $ext = strtolower(pathinfo($a->nome_arquivo ?? '', PATHINFO_EXTENSION));
                $isImg = in_array($ext, ['jpg', 'jpeg', 'png', 'gif', 'webp', 'bmp', 'svg'], true)
                    || (isset($a->tipo_mime) && str_starts_with((string) $a->tipo_mime, 'image/'));
                return [
                    'id' => $a->id,
                    'nome_arquivo' => $a->nome_arquivo,
                    'url' => route('ordens-servico.anexos.file', $a),
                    'tipo' => $a->tipo,
                    'tags' => $a->tags,
                    'descricao' => $a->descricao,
                    'is_image' => $isImg,
                ];
            })->toArray(),
        ]);
    }

    public function updateAnexo(Request $request, OrdemServico $ordemServico, OrdemServicoAnexo $anexo)
    {
        if ($anexo->ordem_servico_id !== $ordemServico->id) {
            return response()->json(['message' => 'Anexo não pertence a esta OS.'], 404);
        }
        if (in_array($ordemServico->status, ['Encerrado', 'Encerrada'], true) && ! $this->usuarioPodeEditarOsEncerrada()) {
            return response()->json(['message' => 'Não é possível editar anexos de OS encerrada.'], 422);
        }

        $data = $this->validateWithFilters($request, [
            'tipo' => 'nullable|string|max:100',
            'tags' => 'nullable|string|max:255',
            'descricao' => 'nullable|string|max:1000',
        ]);

        $antes = [
            'tipo' => $anexo->tipo ?? null,
            'tags' => $anexo->tags ?? null,
            'descricao' => $anexo->descricao ?? null,
        ];

        $anexo->update([
            'tipo' => $data['tipo'] ?? null,
            'tags' => $data['tags'] ?? null,
            'descricao' => $data['descricao'] ?? null,
        ]);

        $depois = [
            'tipo' => $anexo->tipo ?? null,
            'tags' => $anexo->tags ?? null,
            'descricao' => $anexo->descricao ?? null,
        ];

        $this->registrarLog($ordemServico, 'anexos', 'Anexo editado', [
            'antes' => $antes,
            'depois' => $depois,
        ]);

        return response()->json([
            'message' => 'Anexo atualizado.',
            'anexo' => [
                'id' => $anexo->id,
                'nome_arquivo' => $anexo->nome_arquivo,
                'url' => route('ordens-servico.anexos.file', $anexo),
                'tipo' => $anexo->tipo,
                'tags' => $anexo->tags,
                'descricao' => $anexo->descricao,
                'is_image' => (function () use ($anexo) {
                    $ext = strtolower(pathinfo($anexo->nome_arquivo ?? '', PATHINFO_EXTENSION));
                    return in_array($ext, ['jpg', 'jpeg', 'png', 'gif', 'webp', 'bmp', 'svg'], true)
                        || (isset($anexo->tipo_mime) && str_starts_with((string) $anexo->tipo_mime, 'image/'));
                })(),
            ],
        ]);
    }

    public function updateEquipamento(Request $request, OrdemServico $ordemServico)
    {
        $data = $this->validateWithFilters($request, [
            'equipamento_id' => 'required|integer|exists:equipamentos,id',
        ]);

        $equipamento = Equipamento::find($data['equipamento_id']);
        if (!$equipamento) {
            return response()->json(['message' => 'Equipamento não encontrado.'], 404);
        }

        $equipamentoAntes = [
            'equipamento_id' => $ordemServico->equipamento_id ?? null,
            'equipamento_nome' => $ordemServico->equipamento_nome ?? null,
            'equipamento_marca' => $ordemServico->equipamento_marca ?? null,
            'equipamento_modelo' => $ordemServico->equipamento_modelo ?? null,
            'equipamento_numero_serie' => $ordemServico->equipamento_numero_serie ?? null,
            'equipamento_numero_patrimonio' => $ordemServico->equipamento_numero_patrimonio ?? null,
            'equipamento_acessorios' => $ordemServico->equipamento_acessorios ?? null,
        ];

        $ordemServico->equipamento_id = $equipamento->id;
        $ordemServico->equipamento_marca = $equipamento->marca;
        $ordemServico->equipamento_modelo = $equipamento->modelo;
        $ordemServico->equipamento_numero_serie = $equipamento->numero_serie;
        $ordemServico->equipamento_numero_patrimonio = $equipamento->numero_patrimonio;
        $ordemServico->equipamento_acessorios = $equipamento->acessorios;
        $label = trim(($equipamento->marca ?? '') . ' ' . ($equipamento->modelo ?? '') . ' ' . ($equipamento->numero_serie ?? ''));
        if (empty($ordemServico->equipamento_nome)) {
            $ordemServico->equipamento_nome = $label ?: ('Equipamento #' . $equipamento->id);
        }
        $ordemServico->updated_by = auth()->id();
        $ordemServico->ip_atualizacao = $request->ip();
        $ordemServico->save();

        $equipamentoDepois = [
            'equipamento_id' => $ordemServico->equipamento_id ?? null,
            'equipamento_nome' => $ordemServico->equipamento_nome ?? null,
            'equipamento_marca' => $ordemServico->equipamento_marca ?? null,
            'equipamento_modelo' => $ordemServico->equipamento_modelo ?? null,
            'equipamento_numero_serie' => $ordemServico->equipamento_numero_serie ?? null,
            'equipamento_numero_patrimonio' => $ordemServico->equipamento_numero_patrimonio ?? null,
            'equipamento_acessorios' => $ordemServico->equipamento_acessorios ?? null,
        ];

        $this->registrarLog($ordemServico, 'equipamento', 'Equipamento atualizado', [
            'antes' => $equipamentoAntes,
            'depois' => $equipamentoDepois,
        ]);

        return response()->json([
            'ok' => true,
            'equipamento_id' => $equipamento->id,
        ]);
    }

    public function updateCliente(Request $request, OrdemServico $ordemServico)
    {
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
            if (!$contatoId) {
                $contatoId = Contato::where('cliente_id', $unidadeId)
                    ->orderByDesc('principal')
                    ->orderBy('id')
                    ->value('id');
            }
            if (!$enderecoId) {
                $enderecoId = Endereco::where('cliente_id', $unidadeId)
                    ->orderByDesc('principal')
                    ->orderBy('id')
                    ->value('id');
            }
        } else {
            if ($contatoId && !Contato::whereKey($contatoId)->where('cliente_id', $clienteId)->exists()) {
                return response()->json(['message' => 'Contato inválido para o cliente.'], 422);
            }
            if ($enderecoId && !Endereco::whereKey($enderecoId)->where('cliente_id', $clienteId)->exists()) {
                return response()->json(['message' => 'Endereço inválido para o cliente.'], 422);
            }
        }

        $clienteAntes = [
            'cliente_id' => $ordemServico->cliente_id ?? null,
            'cliente_unidade_id' => $ordemServico->cliente_unidade_id ?? null,
            'contato_id' => $ordemServico->contato_id ?? null,
            'endereco_id' => $ordemServico->endereco_id ?? null,
        ];

        $ordemServico->cliente_id = $clienteId;
        $ordemServico->cliente_unidade_id = $unidadeId;
        $ordemServico->contato_id = $contatoId;
        $ordemServico->endereco_id = $enderecoId;
        $ordemServico->updated_by = auth()->id();
        $ordemServico->ip_atualizacao = $request->ip();
        $ordemServico->save();

        $ordemServico->load([
            'cliente.contatos',
            'cliente.enderecos',
            'unidade.contatos',
            'unidade.enderecos',
            'contato',
            'endereco',
        ]);

        $clienteAtual = $ordemServico->cliente;
        $unidadeAtual = $ordemServico->unidade;
        $baseCliente = $unidadeAtual ?? $clienteAtual;
        $contatosCliente = $baseCliente?->contatos ?? collect();
        $contatoPreferencial = $ordemServico->contato
            ?? $contatosCliente->firstWhere('principal', true)
            ?? $contatosCliente->sortBy('id')->first();
        $enderecoAtual = $ordemServico->endereco
            ?? $baseCliente?->enderecos?->firstWhere('principal', true);
        $contatoWhatsappPreferencial = $baseCliente
            ? $baseCliente->contatos
                ->where('telefone_whatsapp', true)
                ->sortByDesc(fn($c) => (int) $c->principal)
                ->first()
            : null;

        $enderecoTexto = $enderecoAtual
            ? trim($enderecoAtual->logradouro . ' ' . $enderecoAtual->numero . ' ' . ($enderecoAtual->complemento ? '- ' . $enderecoAtual->complemento : '') . ', ' . $enderecoAtual->bairro . ' - ' . $enderecoAtual->cidade . '/' . $enderecoAtual->estado)
            : '-';

        $contatosResumo = $contatosCliente
            ->where('principal', false)
            ->sortBy('id')
            ->take(3)
            ->map(function ($contato) {
                return [
                    'nome' => $contato->nome,
                    'telefone' => $contato->telefone,
                    'email' => $contato->email,
                ];
            })
            ->values();

        $clienteDepois = [
            'cliente_id' => $ordemServico->cliente_id ?? null,
            'cliente_unidade_id' => $ordemServico->cliente_unidade_id ?? null,
            'contato_id' => $ordemServico->contato_id ?? null,
            'endereco_id' => $ordemServico->endereco_id ?? null,
        ];

        $this->registrarLog($ordemServico, 'cliente', 'Cliente atualizado', [
            'antes' => $clienteAntes,
            'depois' => $clienteDepois,
        ]);

        return response()->json([
            'ok' => true,
            'cliente_nome' => $clienteAtual?->nome,
            'endereco_texto' => $enderecoTexto,
            'contato_nome' => $contatoPreferencial?->nome,
            'contato_telefone' => $contatoPreferencial?->telefone,
            'contato_email' => $contatoPreferencial?->email,
            'whatsapp' => $contatoWhatsappPreferencial?->telefone,
            'whatsapp_digits' => $contatoWhatsappPreferencial ? preg_replace('/\D/', '', $contatoWhatsappPreferencial->telefone ?? '') : '',
            'contatos_resumo' => $contatosResumo,
        ]);
    }

    /**
     * Retorna o preview dos textos de notificação (e-mail e WhatsApp) para a OS atual.
     * Usado ao abrir o modal "Notificar cliente" para exibir sempre o status salvo.
     */
    public function notificacaoPreview(OrdemServico $ordemServico)
    {
        $ordem = $ordemServico->load([
            'cliente',
            'contato',
            'cliente.contatos',
            'produtos.produto',
            'produtos.variacao',
            'servicos.servico',
        ]);
        $status = $ordem->status;
        $telWa = NotificacaoOsService::getTelefoneWhatsAppCliente($ordem);
        $notifPreview = [
            'email_assunto' => NotificacaoOsService::aplicarTemplateTags(NotificacaoOsService::getEmailTemplateAssunto(), $ordem, $status),
            'email_corpo' => NotificacaoOsService::aplicarTemplateTags(NotificacaoOsService::getEmailTemplateCorpo(), $ordem, $status),
            'whatsapp_mensagem' => NotificacaoOsService::aplicarTemplateTags(NotificacaoOsService::getWhatsAppTemplate(), $ordem, $status),
            'whatsapp_digits' => $telWa !== null && $telWa !== '' ? preg_replace('/\D/', '', $telWa) : '',
        ];

        return response()->json($notifPreview);
    }

    /**
     * Registra adiantamento(s) na OS usando a mesma lógica de recebimento do modal Encerrar:
     * aceita array pagamentos[] (forma_pagamento_id, valor, conta_bancaria_id, adquirente_id, bandeira, parcelas)
     * e processa cada item com taxa, conciliação e baixa como no encerramento.
     */
    public function storeAdiantamento(Request $request, OrdemServico $ordemServico)
    {
        $respondJson = $request->wantsJson() || $request->ajax()
            || strtolower((string) $request->header('X-Requested-With')) === 'xmlhttprequest';

        if (in_array($ordemServico->status, ['Encerrado', 'Encerrada'], true) && ! $this->usuarioPodeEditarOsEncerrada()) {
            if ($respondJson) {
                return response()->json(['message' => 'Não é possível registrar adiantamento em OS encerrada.'], 422);
            }
            return redirect()->back()->with('error', 'Não é possível registrar adiantamento em OS encerrada.');
        }

        if (is_array($request->pagamentos ?? null)) {
            $pagamentos = $request->pagamentos;
            foreach ($pagamentos as $i => $p) {
                if (isset($p['valor'])) {
                    $pagamentos[$i]['valor'] = parse_br_number($p['valor']);
                }
            }
            $request->merge(['pagamentos' => $pagamentos]);
        }

        $this->validateWithFilters($request, [
            'data_adiantamento' => 'required|date',
            'pagamentos' => 'required|array|min:1',
            'pagamentos.*.forma_pagamento_id' => 'required|exists:formas_pagamento,id',
            'pagamentos.*.valor' => 'required|numeric|min:0.01',
            'pagamentos.*.parcelas' => 'nullable|integer|min:1',
            'pagamentos.*.conta_bancaria_id' => 'nullable|exists:contas_bancarias,id',
            'pagamentos.*.adquirente_id' => 'nullable|exists:adquirentes,id',
            'pagamentos.*.bandeira' => 'nullable|string|in:master,visa,elo,amex,hipercard,outros',
            'observacoes' => 'nullable|string|max:500',
        ], [], [
            'data_adiantamento' => 'data do adiantamento',
            'pagamentos.required' => 'Adicione pelo menos uma forma de pagamento.',
            'pagamentos.*.forma_pagamento_id.required' => 'Selecione a forma de pagamento.',
            'pagamentos.*.valor.required' => 'Informe o valor.',
        ]);

        $totalGeral = (float) $ordemServico->total_geral;
        $totalAdiantado = (float) $ordemServico->total_adiantado;
        $limiteAdiantamento = max(0, $totalGeral - $totalAdiantado);

        $pagamentosValidos = array_filter($request->pagamentos, function ($p) {
            return ! empty($p['forma_pagamento_id']) && isset($p['valor']) && (float) $p['valor'] > 0;
        });
        if (empty($pagamentosValidos)) {
            $msg = 'Nenhum pagamento válido. Preencha forma de pagamento e valor.';
            if ($respondJson) {
                return response()->json(['message' => $msg], 422);
            }
            return redirect()->back()->withInput()->with('error', $msg);
        }

        $totalPagamentos = collect($pagamentosValidos)->sum(fn ($p) => (float) ($p['valor'] ?? 0));
        if ($totalPagamentos > $limiteAdiantamento + 0.01) {
            $msg = sprintf(
                'O total do adiantamento (R$ %s) não pode ultrapassar o valor ainda não adiantado da OS (R$ %s).',
                number_format($totalPagamentos, 2, ',', '.'),
                number_format($limiteAdiantamento, 2, ',', '.')
            );
            if ($respondJson) {
                return response()->json(['message' => $msg], 422);
            }
            return redirect()->back()->withInput()->with('error', $msg);
        }

        $dataBaixa = \Carbon\Carbon::parse($request->data_adiantamento);
        $descricaoPrefix = 'Adiantamento ' . $ordemServico->codigo_interno;

        DB::beginTransaction();
        try {
            $contasReceberCriadas = $this->processarPagamentosAdiantamentoOs(
                $ordemServico,
                array_values($pagamentosValidos),
                $dataBaixa,
                $descricaoPrefix
            );

            DB::commit();

            $totalApos = (float) $ordemServico->fresh()->total_adiantado;
            $msg = 'Adiantamento de R$ ' . number_format($totalPagamentos, 2, ',', '.') . ' registrado com sucesso.';

            $linhas = [];
            foreach ($contasReceberCriadas as $cr) {
                $cr->load('formaPagamento');
                $dataCell = $cr->data_recebimento
                    ? $cr->data_recebimento->format('d/m/Y')
                    : ($cr->data_vencimento ? $cr->data_vencimento->format('d/m/Y') . ' (venc.)' : '-');
                $linhas[] = [
                    'id' => $cr->id,
                    'status' => $cr->status,
                    'data' => $dataCell,
                    'valor' => 'R$ ' . number_format((float) $cr->valor, 2, ',', '.'),
                    'valor_num' => (float) $cr->valor,
                    'forma' => $cr->formaPagamento->nome ?? '-',
                    'descricao' => $cr->descricao ?? '',
                ];
            }

            if ($respondJson) {
                return response()->json([
                    'message' => $msg,
                    'total_adiantado' => $totalApos,
                    'contas_criadas' => count($contasReceberCriadas),
                    'linhas' => $linhas,
                ]);
            }
            return redirect()->back()->with('success', $msg);
        } catch (\RuntimeException $e) {
            DB::rollBack();
            $msg = $e->getMessage();
            if ($respondJson) {
                return response()->json(['message' => $msg], 422);
            }
            return redirect()->back()->withInput()->with('error', $msg);
        } catch (\Throwable $e) {
            DB::rollBack();
            $msg = 'Erro ao registrar adiantamento: ' . $e->getMessage();
            if ($respondJson) {
                return response()->json(['message' => $msg], 500);
            }
            return redirect()->back()->withInput()->with('error', $msg);
        }
    }

    /**
     * Estorna um adiantamento (conta a receber de adiantamento OS).
     * Se status aberto: apenas cancela a conta. Se status pago: marca baixa estornada, reverte conta e gera Conta a Pagar para devolução.
     */
    public function estornarAdiantamento(Request $request, OrdemServico $ordemServico, ContaReceber $contaReceber)
    {
        $respondJson = $request->wantsJson() || $request->ajax()
            || strtolower((string) $request->header('X-Requested-With')) === 'xmlhttprequest';

        if ($contaReceber->ordem_servico_id != $ordemServico->id) {
            $msg = 'Esta conta não pertence a esta ordem de serviço.';
            if ($respondJson) return response()->json(['message' => $msg], 422);
            return redirect()->back()->with('error', $msg);
        }
        if ($contaReceber->origem_tipo !== 'adiantamento_os') {
            $msg = 'Esta conta não é um adiantamento de OS.';
            if ($respondJson) return response()->json(['message' => $msg], 422);
            return redirect()->back()->with('error', $msg);
        }
        $this->validateWithFilters($request, ['motivo' => 'required|string|min:10'], [], ['motivo' => 'motivo do estorno']);
        $motivo = trim($request->motivo);
        $userName = auth()->user()->name ?? 'Sistema';
        $dataHora = now()->format('d/m/Y H:i');

        DB::beginTransaction();
        try {
            if ($contaReceber->status === 'cancelado') {
                $msg = 'Este adiantamento já está cancelado.';
                if ($respondJson) return response()->json(['message' => $msg], 422);
                return redirect()->back()->with('error', $msg);
            }
            if ($contaReceber->status === 'aberto') {
                $contaReceber->status = 'cancelado';
                $obsAtual = $contaReceber->observacoes ?? '';
                $contaReceber->observacoes = $obsAtual . "\n\n--- ESTORNO/CANCELAMENTO ---\nData: {$dataHora}\nUsuário: {$userName}\nMotivo: {$motivo}\nRastreio: adiantamento cancelado (não havia recebimento confirmado).";
                $contaReceber->updated_by = auth()->id();
                $contaReceber->save();
                DB::commit();
                $msg = 'Adiantamento cancelado com sucesso.';
                if ($respondJson) return response()->json(['message' => $msg, 'total_adiantado' => (float) $ordemServico->fresh()->total_adiantado]);
                return redirect()->back()->with('success', $msg);
            }
            $baixa = BaixaTitulo::where('tipo_titulo', 'conta_receber')->where('titulo_id', $contaReceber->id)->where('estornado', false)->first();
            if (! $baixa) {
                DB::rollBack();
                $msg = 'Não foi encontrada baixa ativa para este adiantamento.';
                if ($respondJson) return response()->json(['message' => $msg], 422);
                return redirect()->back()->with('error', $msg);
            }
            $baixa->estornado = true;
            $baixa->data_estorno = now();
            $baixa->estornado_por = auth()->id();
            $baixa->motivo_estorno = $motivo;
            $baixa->save();
            $contaReceber->valor_recebido = 0;
            $contaReceber->data_recebimento = null;
            $contaReceber->status = 'aberto';
            $obsAtual = $contaReceber->observacoes ?? '';
            $contaReceber->observacoes = $obsAtual . "\n\n--- ESTORNO ---\nData: {$dataHora}\nUsuário: {$userName}\nMotivo: {$motivo}\nConta a Pagar gerada para devolução. Baixa ID: {$baixa->id} estornada.";
            $contaReceber->updated_by = auth()->id();
            $contaReceber->save();
            $planoContaId = Configuracao::getPlanoContaOsEncerramento() ?? PlanoConta::where('codigo', '1.1')->orWhere('nome', 'Receita de Serviços')->first()?->id;
            $centroCustoId = Configuracao::getCentroCustoOsEncerramento();
            $obsContaPagar = sprintf(
                "ESTORNO DE ADIANTAMENTO - DEVOLUÇÃO AO CLIENTE\nOrigem: %s\nConta a receber estornada ID: %s\nBaixa estornada ID: %s\nValor: R$ %s\nData estorno: %s\nUsuário: %s\nMotivo: %s\nCliente ID: %s | OS ID: %s | Código OS: %s",
                $contaReceber->descricao, $contaReceber->id, $baixa->id,
                number_format((float) $contaReceber->valor, 2, ',', '.'), $dataHora, $userName, $motivo,
                $ordemServico->cliente_id, $ordemServico->id, $ordemServico->codigo_interno ?? $ordemServico->id
            );
            ContaPagar::create([
                'tenant_id' => $ordemServico->tenant_id ?? auth()->user()->tenant_id ?? null,
                'fornecedor_id' => $ordemServico->cliente_id,
                'ordem_servico_id' => $ordemServico->id,
                'plano_conta_id' => $planoContaId,
                'centro_custo_id' => $centroCustoId,
                'descricao' => 'Estorno adiantamento ' . $contaReceber->descricao,
                'valor' => $contaReceber->valor,
                'valor_original' => $contaReceber->valor,
                'data_vencimento' => now()->addDays(7),
                'status' => 'aberto',
                'tipo' => 'outro',
                'observacoes' => $obsContaPagar,
                'origem_tipo' => 'estorno_adiantamento_os',
                'entidade_tipo' => 'conta_receber',
                'entidade_id' => $contaReceber->id,
                'metadata' => ['conta_receber_id' => $contaReceber->id, 'ordem_servico_id' => $ordemServico->id],
                'created_by' => auth()->id(),
            ]);
            DB::commit();
            $msg = 'Adiantamento estornado. Foi gerada uma Conta a Pagar para devolução ao cliente.';
            if ($respondJson) return response()->json(['message' => $msg, 'total_adiantado' => (float) $ordemServico->fresh()->total_adiantado]);
            return redirect()->back()->with('success', $msg);
        } catch (\Throwable $e) {
            DB::rollBack();
            $msg = 'Erro ao estornar adiantamento: ' . $e->getMessage();
            if ($respondJson) return response()->json(['message' => $msg], 500);
            return redirect()->back()->with('error', $msg);
        }
    }

    /**
     * Processa pagamentos para adiantamento na OS (mesma lógica de recebimento do encerrar: taxa, conciliação, baixa).
     * Lança \RuntimeException em caso de erro.
     *
     * @return array<int, ContaReceber>
     */
    private function processarPagamentosAdiantamentoOs(
        OrdemServico $ordemServico,
        array $pagamentos,
        \Carbon\Carbon $dataBaixaPadrao,
        string $descricaoPrefix
    ): array {
        $planoContaReceitaServico = Configuracao::getPlanoContaOsEncerramento()
            ?? PlanoConta::where('codigo', '1.1')->orWhere('nome', 'Receita de Serviços')->first()?->id;
        $centroCustoOsEncerramento = Configuracao::getCentroCustoOsEncerramento();
        $adquirenteService = app(AdquirenteService::class);
        $contasReceberCriadas = [];

        foreach ($pagamentos as $pagamento) {
            $formaPagamento = FormaPagamento::find($pagamento['forma_pagamento_id']);
            if (! $formaPagamento) {
                throw new \RuntimeException('Forma de pagamento não encontrada. Selecione uma forma válida.');
            }
            $valorPagamento = (float) $pagamento['valor'];
            $parcelas = (int) ($pagamento['parcelas'] ?? 1);
            $tipoForma = $formaPagamento->tipo ?? '';

            $adquirenteIdEfetivo = $formaPagamento->resolverAdquirenteId((int) ($pagamento['adquirente_id'] ?? 0));
            $contaExplicita = ! empty($pagamento['conta_bancaria_id']) ? (int) $pagamento['conta_bancaria_id'] : null;
            $contaBancariaId = $formaPagamento->resolverContaBancariaIdParaRecebimento($contaExplicita, $adquirenteIdEfetivo);
            if (! $contaBancariaId) {
                $contaBancariaPadrao = ContaBancaria::where('ativo', true)->first();
                if (! $contaBancariaPadrao) {
                    throw new \RuntimeException('É necessário ter pelo menos uma conta bancária ativa. Associe uma conta à forma de pagamento, ao adquirente ou escolha no formulário.');
                }
                $contaBancariaId = $contaBancariaPadrao->id;
            }

            // Transferência ou Boleto no adiantamento: conta a receber ABERTA, aguardando confirmação no financeiro (sem conciliação automática)
            if (in_array($tipoForma, ['transferencia', 'boleto'])) {
                $diasReceb = (int) ($formaPagamento->dias_recebimento ?? 1);
                $dataVenc = $dataBaixaPadrao->copy()->addDays($diasReceb);
                $obs = sprintf(
                    "ADIANTAMENTO OS - AGUARDANDO CONFIRMAÇÃO NO FINANCEIRO\n".
                    "Origem: %s\n".
                    "Forma de pagamento: %s\n".
                    "Valor: R$ %s\n".
                    "Data do registro do adiantamento: %s\n".
                    "Vencimento previsto: %s\n".
                    "Instrução: Ao confirmar o recebimento (transferência/boleto) no financeiro, dar baixa neste título. Rastreio: ordem_servico_id=%s",
                    $descricaoPrefix,
                    $formaPagamento->nome,
                    number_format($valorPagamento, 2, ',', '.'),
                    $dataBaixaPadrao->format('d/m/Y'),
                    $dataVenc->format('d/m/Y'),
                    $ordemServico->id
                );
                $contaReceber = ContaReceber::create([
                    'ordem_servico_id' => $ordemServico->id,
                    'cliente_id' => $ordemServico->cliente_id,
                    'descricao' => $descricaoPrefix,
                    'valor' => $valorPagamento,
                    'valor_original' => $valorPagamento,
                    'data_vencimento' => $dataVenc,
                    'numero_parcela' => 1,
                    'total_parcelas' => 1,
                    'forma_pagamento_id' => $formaPagamento->id,
                    'conta_bancaria_id' => $contaBancariaId,
                    'plano_conta_id' => $planoContaReceitaServico,
                    'centro_custo_id' => $centroCustoOsEncerramento,
                    'status' => 'aberto',
                    'observacoes' => $obs,
                    'origem_tipo' => 'adiantamento_os',
                    'entidade_tipo' => 'ordem_servico',
                    'entidade_id' => $ordemServico->id,
                    'created_by' => auth()->id(),
                ]);
                $contasReceberCriadas[] = $contaReceber;
                continue;
            }

            // Cartão de crédito
            if ($tipoForma === 'cartao_credito') {
                $adquirenteId = $adquirenteIdEfetivo;
                $bandeira = $pagamento['bandeira'] ?? '';
                if (! $adquirenteId || ! $bandeira) {
                    throw new \RuntimeException('Para Cartão de Crédito informe Adquirente e Bandeira do cartão.');
                }
                if (! $contaBancariaId) {
                    throw new \RuntimeException('Adquirente selecionada não possui conta bancária padrão. Cadastre em Financeiro > Adquirentes.');
                }
                try {
                    $simulacao = $adquirenteService->simularTransacao($valorPagamento, $parcelas, $adquirenteId, $bandeira, true);
                } catch (\Exception $e) {
                    throw new \RuntimeException('Erro ao simular taxas do cartão: ' . $e->getMessage());
                }
                $agenda = $simulacao['agenda_recebiveis'];
                $numItens = count($agenda);
                $valorBrutoPorParcela = $numItens > 0 ? round($valorPagamento / $numItens, 2) : $valorPagamento;
                $restoBruto = round($valorPagamento - ($valorBrutoPorParcela * $numItens), 2);
                foreach ($agenda as $idx => $item) {
                    $valorBruto = $idx === 0 ? $valorBrutoPorParcela + $restoBruto : $valorBrutoPorParcela;
                    $valorLiquido = (float) $item['valor'];
                    $taxaParcela = round($valorBruto - $valorLiquido, 2);
                    $dataReceb = \Carbon\Carbon::parse($item['data']);
                    $parcelaNum = $item['parcela'] ?? ($idx + 1);
                    $totalParc = $item['total_parcelas'] ?? $numItens;
                    $descricaoParcela = $descricaoPrefix . ($totalParc > 1 ? " - Parcela {$parcelaNum}/{$totalParc}" : '');
                    $adquirenteNome = Adquirente::find($adquirenteId)->nome ?? 'N/A';
                    $obsContaCredito = sprintf(
                        "ADIANTAMENTO OS | %s | Forma: %s | Adquirente: %s | Bandeira: %s | Valor bruto: R$ %s | Taxa: R$ %s | Valor líquido: R$ %s | Parcela %s/%s | Previsão recebimento: %s | Rastreio: ordem_servico_id=%s",
                        $descricaoPrefix,
                        $formaPagamento->nome,
                        $adquirenteNome,
                        $bandeira,
                        number_format($valorBruto, 2, ',', '.'),
                        number_format($taxaParcela, 2, ',', '.'),
                        number_format($valorLiquido, 2, ',', '.'),
                        $parcelaNum,
                        $totalParc,
                        $dataReceb->format('d/m/Y'),
                        $ordemServico->id
                    );
                    $contaReceber = ContaReceber::create([
                        'ordem_servico_id' => $ordemServico->id,
                        'cliente_id' => $ordemServico->cliente_id,
                        'descricao' => $descricaoParcela,
                        'valor' => $valorBruto,
                        'valor_original' => $valorBruto,
                        'data_vencimento' => $dataReceb,
                        'numero_parcela' => $totalParc > 1 ? $parcelaNum : 1,
                        'total_parcelas' => $totalParc > 1 ? $totalParc : 1,
                        'forma_pagamento_id' => $formaPagamento->id,
                        'adquirente_id' => $adquirenteId,
                        'bandeira' => $bandeira,
                        'conta_bancaria_id' => $contaBancariaId,
                        'plano_conta_id' => $planoContaReceitaServico,
                        'centro_custo_id' => $centroCustoOsEncerramento,
                        'status' => 'pago',
                        'desconto' => 0,
                        'observacoes' => $obsContaCredito,
                        'origem_tipo' => 'adiantamento_os',
                        'entidade_tipo' => 'ordem_servico',
                        'entidade_id' => $ordemServico->id,
                        'created_by' => auth()->id(),
                    ]);
                    $contasReceberCriadas[] = $contaReceber;
                    $obsEntradaCredito = sprintf(
                        "Recebimento adiantamento OS | Valor total (bruto): R$ %s | %s | Adquirente: %s | Bandeira: %s | Parcela %s/%s | Data: %s | ContaReceber ID: %s | OS ID: %s",
                        number_format($valorBruto, 2, ',', '.'),
                        $contaReceber->descricao,
                        $adquirenteNome,
                        $bandeira,
                        $parcelaNum,
                        $totalParc,
                        $dataReceb->format('d/m/Y'),
                        $contaReceber->id,
                        $ordemServico->id
                    );
                    $movimentacao = MovimentacaoFinanceira::create([
                        'tenant_id' => $ordemServico->tenant_id ?? auth()->user()->tenant_id ?? null,
                        'conta_bancaria_id' => $contaBancariaId,
                        'tipo' => 'entrada',
                        'origem' => 'conta_receber',
                        'conta_receber_id' => $contaReceber->id,
                        'plano_conta_id' => $planoContaReceitaServico,
                        'centro_custo_id' => $centroCustoOsEncerramento,
                        'valor' => $valorBruto,
                        'data_movimentacao' => $dataReceb,
                        'descricao' => 'Recebimento: ' . $contaReceber->descricao,
                        'observacoes' => $obsEntradaCredito,
                        'conciliado' => true,
                        'data_conciliacao' => $dataReceb->toDateString(),
                        'conciliado_por' => auth()->id(),
                        'created_by' => auth()->id(),
                    ]);
                    $taxaMovIdAdiantCredito = null;
                    if ($taxaParcela > 0) {
                        $obsSaidaTaxaCredito = sprintf(
                            "Taxa adquirente - Adiantamento OS | Valor taxa: R$ %s | %s | Adquirente: %s | Bandeira: %s | Parcela %s/%s | Data: %s | ContaReceber ID: %s | OS ID: %s",
                            number_format($taxaParcela, 2, ',', '.'),
                            $contaReceber->descricao,
                            $adquirenteNome,
                            $bandeira,
                            $parcelaNum,
                            $totalParc,
                            $dataReceb->format('d/m/Y'),
                            $contaReceber->id,
                            $ordemServico->id
                        );
                        $movTaxaAdiantCred = MovimentacaoFinanceira::create([
                            'tenant_id' => $ordemServico->tenant_id ?? auth()->user()->tenant_id ?? null,
                            'conta_bancaria_id' => $contaBancariaId,
                            'tipo' => 'saida',
                            'origem' => 'outro',
                            'conta_receber_id' => $contaReceber->id,
                            'plano_conta_id' => $planoContaReceitaServico,
                            'centro_custo_id' => $centroCustoOsEncerramento,
                            'valor' => $taxaParcela,
                            'data_movimentacao' => $dataReceb,
                            'descricao' => 'Taxa adquirente: ' . $contaReceber->descricao,
                            'observacoes' => $obsSaidaTaxaCredito,
                            'conciliado' => true,
                            'data_conciliacao' => $dataReceb->toDateString(),
                            'conciliado_por' => auth()->id(),
                            'created_by' => auth()->id(),
                        ]);
                        $taxaMovIdAdiantCredito = $movTaxaAdiantCred->id;
                    }
                    BaixaTitulo::create([
                        'tipo_titulo' => 'conta_receber',
                        'titulo_id' => $contaReceber->id,
                        'movimentacao_id' => $movimentacao->id,
                        'taxa_movimentacao_id' => $taxaMovIdAdiantCredito,
                        'valor_baixa' => $valorBruto,
                        'data_baixa' => $dataReceb,
                        'juros' => 0,
                        'multa' => 0,
                        'desconto' => 0,
                        'observacoes' => 'Baixa adiantamento OS (cartão crédito) | Valor total R$ ' . number_format($valorBruto, 2, ',', '.') . ' | ContaReceber ID: ' . $contaReceber->id . ' | OS ID: ' . $ordemServico->id,
                        'created_by' => auth()->id(),
                    ]);
                    $contaReceber->valor_recebido = $valorBruto;
                    $contaReceber->data_recebimento = $dataReceb;
                    $contaReceber->save();
                }
                continue;
            }

            // Cartão débito
            if ($tipoForma === 'cartao_debito') {
                $adquirenteId = $adquirenteIdEfetivo;
                $bandeira = $pagamento['bandeira'] ?? '';
                if (! $adquirenteId || ! $bandeira) {
                    throw new \RuntimeException('Para Cartão de Débito informe Adquirente e Bandeira do cartão.');
                }
                if (! $contaBancariaId) {
                    throw new \RuntimeException('Adquirente selecionada não possui conta bancária padrão.');
                }
                try {
                    $simulacao = $adquirenteService->simularTransacaoDebito($valorPagamento, $adquirenteId, $bandeira);
                } catch (\Exception $e) {
                    throw new \RuntimeException('Erro ao simular taxas do cartão de débito: ' . $e->getMessage());
                }
                $agenda = $simulacao['agenda_recebiveis'];
                if (empty($agenda)) {
                    throw new \RuntimeException('Adquirente não retornou data de recebimento para débito.');
                }
                $item = $agenda[0];
                $dataReceb = \Carbon\Carbon::parse($item['data']);
                $valorLiquido = (float) ($simulacao['valor_liquido'] ?? $item['valor'] ?? $valorPagamento);
                $taxaTotal = $valorPagamento - $valorLiquido;
                $adquirenteNomeDeb = Adquirente::find($adquirenteId)->nome ?? 'N/A';
                $obsContaDebito = sprintf(
                    "ADIANTAMENTO OS | %s | Forma: %s | Adquirente: %s | Bandeira: %s | Valor bruto: R$ %s | Taxa: R$ %s | Valor líquido: R$ %s | Previsão recebimento: %s | Rastreio: ordem_servico_id=%s",
                    $descricaoPrefix,
                    $formaPagamento->nome,
                    $adquirenteNomeDeb,
                    $bandeira,
                    number_format($valorPagamento, 2, ',', '.'),
                    number_format($taxaTotal, 2, ',', '.'),
                    number_format($valorLiquido, 2, ',', '.'),
                    $dataReceb->format('d/m/Y'),
                    $ordemServico->id
                );
                $contaReceber = ContaReceber::create([
                    'ordem_servico_id' => $ordemServico->id,
                    'cliente_id' => $ordemServico->cliente_id,
                    'descricao' => $descricaoPrefix,
                    'valor' => $valorPagamento,
                    'valor_original' => $valorPagamento,
                    'data_vencimento' => $dataReceb,
                    'numero_parcela' => 1,
                    'total_parcelas' => 1,
                    'forma_pagamento_id' => $formaPagamento->id,
                    'adquirente_id' => $adquirenteId,
                    'bandeira' => $bandeira,
                    'conta_bancaria_id' => $contaBancariaId,
                    'plano_conta_id' => $planoContaReceitaServico,
                    'centro_custo_id' => $centroCustoOsEncerramento,
                    'status' => 'pago',
                    'desconto' => 0,
                    'observacoes' => $obsContaDebito,
                    'origem_tipo' => 'adiantamento_os',
                    'entidade_tipo' => 'ordem_servico',
                    'entidade_id' => $ordemServico->id,
                    'created_by' => auth()->id(),
                ]);
                $contasReceberCriadas[] = $contaReceber;
                $obsEntradaDebito = sprintf(
                    "Recebimento adiantamento OS | Valor total (bruto): R$ %s | %s | Adquirente: %s | Bandeira: %s | Data: %s | ContaReceber ID: %s | OS ID: %s",
                    number_format($valorPagamento, 2, ',', '.'),
                    $contaReceber->descricao,
                    $adquirenteNomeDeb,
                    $bandeira,
                    $dataReceb->format('d/m/Y'),
                    $contaReceber->id,
                    $ordemServico->id
                );
                $movimentacao = MovimentacaoFinanceira::create([
                    'tenant_id' => $ordemServico->tenant_id ?? auth()->user()->tenant_id ?? null,
                    'conta_bancaria_id' => $contaBancariaId,
                    'tipo' => 'entrada',
                    'origem' => 'conta_receber',
                    'conta_receber_id' => $contaReceber->id,
                    'plano_conta_id' => $planoContaReceitaServico,
                    'centro_custo_id' => $centroCustoOsEncerramento,
                    'valor' => $valorPagamento,
                    'data_movimentacao' => $dataReceb,
                    'descricao' => 'Recebimento: ' . $contaReceber->descricao,
                    'observacoes' => $obsEntradaDebito,
                    'conciliado' => true,
                    'data_conciliacao' => $dataReceb->toDateString(),
                    'conciliado_por' => auth()->id(),
                    'created_by' => auth()->id(),
                ]);
                $taxaMovIdAdiantDebito = null;
                if ($taxaTotal > 0) {
                    $obsSaidaTaxaDebito = sprintf(
                        "Taxa adquirente - Adiantamento OS | Valor taxa: R$ %s | %s | Adquirente: %s | Bandeira: %s | Data: %s | ContaReceber ID: %s | OS ID: %s",
                        number_format($taxaTotal, 2, ',', '.'),
                        $contaReceber->descricao,
                        $adquirenteNomeDeb,
                        $bandeira,
                        $dataReceb->format('d/m/Y'),
                        $contaReceber->id,
                        $ordemServico->id
                    );
                    $movTaxaAdiantDeb = MovimentacaoFinanceira::create([
                        'tenant_id' => $ordemServico->tenant_id ?? auth()->user()->tenant_id ?? null,
                        'conta_bancaria_id' => $contaBancariaId,
                        'tipo' => 'saida',
                        'origem' => 'outro',
                        'conta_receber_id' => $contaReceber->id,
                        'plano_conta_id' => $planoContaReceitaServico,
                        'centro_custo_id' => $centroCustoOsEncerramento,
                        'valor' => $taxaTotal,
                        'data_movimentacao' => $dataReceb,
                        'descricao' => 'Taxa adquirente: ' . $contaReceber->descricao,
                        'observacoes' => $obsSaidaTaxaDebito,
                        'conciliado' => true,
                        'data_conciliacao' => $dataReceb->toDateString(),
                        'conciliado_por' => auth()->id(),
                        'created_by' => auth()->id(),
                    ]);
                    $taxaMovIdAdiantDebito = $movTaxaAdiantDeb->id;
                }
                BaixaTitulo::create([
                    'tipo_titulo' => 'conta_receber',
                    'titulo_id' => $contaReceber->id,
                    'movimentacao_id' => $movimentacao->id,
                    'taxa_movimentacao_id' => $taxaMovIdAdiantDebito,
                    'valor_baixa' => $valorPagamento,
                    'data_baixa' => $dataReceb,
                    'juros' => 0,
                    'multa' => 0,
                    'desconto' => 0,
                    'observacoes' => 'Baixa adiantamento OS (cartão débito) | Valor total R$ ' . number_format($valorPagamento, 2, ',', '.') . ' | ContaReceber ID: ' . $contaReceber->id . ' | OS ID: ' . $ordemServico->id,
                    'created_by' => auth()->id(),
                ]);
                $contaReceber->valor_recebido = $valorPagamento;
                $contaReceber->data_recebimento = $dataReceb;
                $contaReceber->save();
                continue;
            }

            // PIX parcelado: datas por parcela — baixa automática só nas parcelas com vencimento até a data do adiantamento
            if ($tipoForma === 'pix' && $parcelas > 1) {
                $vencimentos = $pagamento['pix_parcela_vencimentos'] ?? [];
                if (! is_array($vencimentos) || count($vencimentos) !== $parcelas) {
                    throw new \RuntimeException('Para PIX parcelado no adiantamento, informe a data de cada parcela.');
                }
                $pixChaveParcelado = $formaPagamento->encontrarPixChavePorId($pagamento['pix_chave_id'] ?? null);
                if (! $pixChaveParcelado) {
                    throw new \RuntimeException('Selecione a chave PIX de recebimento para pagamento parcelado.');
                }
                if (! empty($pixChaveParcelado['conta_bancaria_id'])) {
                    $contaBancariaId = (int) $pixChaveParcelado['conta_bancaria_id'];
                }
                $taxaPix = round(
                    ($valorPagamento * ((float) ($pixChaveParcelado['taxa_percentual'] ?? 0) / 100))
                    + (float) ($pixChaveParcelado['taxa_fixa'] ?? 0),
                    2
                );
                if ($taxaPix <= 0) {
                    $taxaPix = $formaPagamento->calcularTaxa($valorPagamento);
                }
                $valorParcelaPix = $parcelas > 1 ? round($valorPagamento / $parcelas, 2) : $valorPagamento;
                $restoValorPix = round($valorPagamento - ($valorParcelaPix * $parcelas), 2);
                $taxaParcelaPix = $parcelas > 1 ? round($taxaPix / $parcelas, 2) : $taxaPix;
                $restoTaxaPix = round($taxaPix - ($taxaParcelaPix * $parcelas), 2);
                $refAdiantamento = $dataBaixaPadrao->copy()->startOfDay();
                for ($i = 1; $i <= $parcelas; $i++) {
                    try {
                        $dataVencPix = \Carbon\Carbon::parse($vencimentos[$i - 1])->startOfDay();
                    } catch (\Throwable $e) {
                        throw new \RuntimeException('Data de vencimento inválida na parcela ' . $i . ' do PIX.');
                    }
                    $vp = $i === 1 ? $valorParcelaPix + $restoValorPix : $valorParcelaPix;
                    $tp = $i === 1 ? $taxaParcelaPix + $restoTaxaPix : $taxaParcelaPix;
                    $vl = $vp - $tp;
                    $recebidoNaData = $dataVencPix->lte($refAdiantamento);
                    if ($recebidoNaData) {
                        $conciliacaoPix = ConciliacaoService::calcularSemAdquirente($formaPagamento, $dataBaixaPadrao->format('Y-m-d'));
                        $statusConcilObsPix = $conciliacaoPix['obs_liquidacao']
                            ? ' | ' . $conciliacaoPix['obs_liquidacao']
                            : ' | Status: pago (baixa na hora)';
                        $obsPixParcPago = sprintf(
                            'ADIANTAMENTO OS | %s | Forma: %s | PIX parcela %s/%s | Valor: R$ %s | Taxa: R$ %s | Líquido: R$ %s | Vencimento: %s%s | Rastreio: ordem_servico_id=%s',
                            $descricaoPrefix,
                            $formaPagamento->nome,
                            $i,
                            $parcelas,
                            number_format($vp, 2, ',', '.'),
                            number_format($tp, 2, ',', '.'),
                            number_format($vl, 2, ',', '.'),
                            $dataVencPix->format('d/m/Y'),
                            $statusConcilObsPix,
                            $ordemServico->id
                        );
                        $contaReceber = ContaReceber::create([
                            'ordem_servico_id' => $ordemServico->id,
                            'cliente_id' => $ordemServico->cliente_id,
                            'descricao' => $descricaoPrefix . " - Parcela {$i}/{$parcelas}",
                            'valor' => $vp,
                            'valor_original' => $vp,
                            'data_vencimento' => $dataVencPix,
                            'numero_parcela' => $i,
                            'total_parcelas' => $parcelas,
                            'forma_pagamento_id' => $formaPagamento->id,
                            'adquirente_id' => $adquirenteIdEfetivo > 0 ? $adquirenteIdEfetivo : null,
                            'conta_bancaria_id' => $contaBancariaId,
                            'plano_conta_id' => $planoContaReceitaServico,
                            'centro_custo_id' => $centroCustoOsEncerramento,
                            'status' => 'pago',
                            'desconto' => 0,
                            'observacoes' => $obsPixParcPago,
                            'metadata' => ['pix_chave_id' => $pixChaveParcelado['id'] ?? null],
                            'origem_tipo' => 'adiantamento_os',
                            'entidade_tipo' => 'ordem_servico',
                            'entidade_id' => $ordemServico->id,
                            'created_by' => auth()->id(),
                        ]);
                        $contasReceberCriadas[] = $contaReceber;
                        $obsEntradaPix = $tp > 0
                            ? sprintf(
                                'Recebimento adiantamento OS | Valor bruto R$ %s | Líquido na conta R$ %s | Taxa R$ %s | %s | ContaReceber ID: %s | OS ID: %s',
                                number_format($vp, 2, ',', '.'),
                                number_format($vl, 2, ',', '.'),
                                number_format($tp, 2, ',', '.'),
                                $contaReceber->descricao,
                                $contaReceber->id,
                                $ordemServico->id
                            )
                            : sprintf(
                                'Recebimento adiantamento OS | Valor R$ %s | %s | ContaReceber ID: %s | OS ID: %s',
                                number_format($vp, 2, ',', '.'),
                                $contaReceber->descricao,
                                $contaReceber->id,
                                $ordemServico->id
                            );
                        $movimentacao = MovimentacaoFinanceira::create([
                            'tenant_id' => $ordemServico->tenant_id ?? auth()->user()->tenant_id ?? null,
                            'conta_bancaria_id' => $contaBancariaId,
                            'tipo' => 'entrada',
                            'origem' => 'conta_receber',
                            'conta_receber_id' => $contaReceber->id,
                            'plano_conta_id' => $planoContaReceitaServico,
                            'centro_custo_id' => $centroCustoOsEncerramento,
                            'valor' => $vp,
                            'data_movimentacao' => $dataBaixaPadrao,
                            'descricao' => 'Recebimento: ' . $contaReceber->descricao,
                            'observacoes' => $obsEntradaPix,
                            'conciliado' => $conciliacaoPix['conciliado'],
                            'data_conciliacao' => $conciliacaoPix['data_conciliacao'],
                            'conciliado_por' => $conciliacaoPix['conciliado'] ? auth()->id() : null,
                            'created_by' => auth()->id(),
                        ]);
                        $taxaMovIdAdiantPixParc = null;
                        if ($tp > 0) {
                            $movTaxaAdiantPixParc = MovimentacaoFinanceira::create([
                                'tenant_id' => $ordemServico->tenant_id ?? auth()->user()->tenant_id ?? null,
                                'conta_bancaria_id' => $contaBancariaId,
                                'tipo' => 'saida',
                                'origem' => 'outro',
                                'conta_receber_id' => $contaReceber->id,
                                'plano_conta_id' => $planoContaReceitaServico,
                                'centro_custo_id' => $centroCustoOsEncerramento,
                                'valor' => $tp,
                                'data_movimentacao' => $dataBaixaPadrao,
                                'descricao' => 'Taxa: ' . $contaReceber->descricao,
                                'observacoes' => 'Taxa PIX — adiantamento OS (parcelado)',
                                'conciliado' => $conciliacaoPix['conciliado'],
                                'data_conciliacao' => $conciliacaoPix['data_conciliacao'],
                                'conciliado_por' => $conciliacaoPix['conciliado'] ? auth()->id() : null,
                                'created_by' => auth()->id(),
                            ]);
                            $taxaMovIdAdiantPixParc = $movTaxaAdiantPixParc->id;
                        }
                        BaixaTitulo::create([
                            'tipo_titulo' => 'conta_receber',
                            'titulo_id' => $contaReceber->id,
                            'movimentacao_id' => $movimentacao->id,
                            'taxa_movimentacao_id' => $taxaMovIdAdiantPixParc,
                            'valor_baixa' => $vp,
                            'data_baixa' => $dataBaixaPadrao,
                            'juros' => 0,
                            'multa' => 0,
                            'desconto' => 0,
                            'observacoes' => 'Baixa adiantamento OS (PIX parcelado — vencimento até a data do adiantamento)',
                            'created_by' => auth()->id(),
                        ]);
                        $contaReceber->valor_recebido = $vp;
                        $contaReceber->data_recebimento = $dataBaixaPadrao;
                        $contaReceber->save();
                    } else {
                        $obsPixFuturo = sprintf(
                            'ADIANTAMENTO OS | %s | Forma: %s | PIX parcela %s/%s | Valor: R$ %s | Vencimento: %s | Status: aberto (confirmar recebimento no financeiro) | Rastreio: ordem_servico_id=%s',
                            $descricaoPrefix,
                            $formaPagamento->nome,
                            $i,
                            $parcelas,
                            number_format($vp, 2, ',', '.'),
                            $dataVencPix->format('d/m/Y'),
                            $ordemServico->id
                        );
                        $contaReceber = ContaReceber::create([
                            'ordem_servico_id' => $ordemServico->id,
                            'cliente_id' => $ordemServico->cliente_id,
                            'descricao' => $descricaoPrefix . " - Parcela {$i}/{$parcelas}",
                            'valor' => $vp,
                            'valor_original' => $vp,
                            'data_vencimento' => $dataVencPix,
                            'numero_parcela' => $i,
                            'total_parcelas' => $parcelas,
                            'forma_pagamento_id' => $formaPagamento->id,
                            'conta_bancaria_id' => $contaBancariaId,
                            'plano_conta_id' => $planoContaReceitaServico,
                            'centro_custo_id' => $centroCustoOsEncerramento,
                            'status' => 'aberto',
                            'desconto' => 0,
                            'observacoes' => $obsPixFuturo,
                            'metadata' => ['pix_chave_id' => $pixChaveParcelado['id'] ?? null],
                            'origem_tipo' => 'adiantamento_os',
                            'entidade_tipo' => 'ordem_servico',
                            'entidade_id' => $ordemServico->id,
                            'created_by' => auth()->id(),
                        ]);
                        $contasReceberCriadas[] = $contaReceber;
                    }
                }
                continue;
            }

            // Outros (dinheiro, PIX, etc.)
            $conciliacaoOS = ConciliacaoService::calcularSemAdquirente($formaPagamento, $dataBaixaPadrao->format('Y-m-d'));
            $pixChaveUnica = $formaPagamento->encontrarPixChavePorId($pagamento['pix_chave_id'] ?? null);
            if ($tipoForma === 'pix' && $pixChaveUnica && ! empty($pixChaveUnica['conta_bancaria_id'])) {
                $contaBancariaId = (int) $pixChaveUnica['conta_bancaria_id'];
            }
            if ($tipoForma === 'pix' && $pixChaveUnica) {
                $taxa = round(
                    ($valorPagamento * ((float) ($pixChaveUnica['taxa_percentual'] ?? 0) / 100))
                    + (float) ($pixChaveUnica['taxa_fixa'] ?? 0),
                    2
                );
                if ($taxa <= 0) {
                    $taxa = $formaPagamento->calcularTaxa($valorPagamento);
                }
            } else {
                $taxa = $formaPagamento->calcularTaxa($valorPagamento);
            }
            $valorLiquido = $valorPagamento - $taxa;
            $valorParcela = $parcelas > 1 ? round($valorPagamento / $parcelas, 2) : $valorPagamento;
            $restoValor = round($valorPagamento - ($valorParcela * $parcelas), 2);
            $taxaParcela = $parcelas > 1 ? round($taxa / $parcelas, 2) : $taxa;
            $restoTaxa = round($taxa - ($taxaParcela * $parcelas), 2);
            for ($i = 1; $i <= $parcelas; $i++) {
                $vp = $i === 1 ? $valorParcela + $restoValor : $valorParcela;
                $tp = $i === 1 ? $taxaParcela + $restoTaxa : $taxaParcela;
                $vl = $vp - $tp;
                $descricaoParcela = $descricaoPrefix . ($parcelas > 1 ? " - Parcela {$i}/{$parcelas}" : '');
                $obsContaOutros = sprintf(
                    "ADIANTAMENTO OS | %s | Forma: %s | Valor bruto: R$ %s | Taxa: R$ %s | Valor líquido: R$ %s | Parcela %s/%s | Recebimento: %s | Rastreio: ordem_servico_id=%s",
                    $descricaoPrefix,
                    $formaPagamento->nome,
                    number_format($vp, 2, ',', '.'),
                    number_format($tp, 2, ',', '.'),
                    number_format($vl, 2, ',', '.'),
                    $i,
                    $parcelas,
                    $dataBaixaPadrao->format('d/m/Y'),
                    $ordemServico->id
                );
                $attrsCrOutros = [
                    'ordem_servico_id' => $ordemServico->id,
                    'cliente_id' => $ordemServico->cliente_id,
                    'descricao' => $descricaoParcela,
                    'valor' => $vp,
                    'valor_original' => $vp,
                    'data_vencimento' => $conciliacaoOS['data_prevista'] ? \Carbon\Carbon::parse($conciliacaoOS['data_prevista']) : $dataBaixaPadrao,
                    'numero_parcela' => $parcelas > 1 ? $i : 1,
                    'total_parcelas' => $parcelas > 1 ? $parcelas : 1,
                    'forma_pagamento_id' => $formaPagamento->id,
                    'conta_bancaria_id' => $contaBancariaId,
                    'plano_conta_id' => $planoContaReceitaServico,
                    'centro_custo_id' => $centroCustoOsEncerramento,
                    'status' => 'pago',
                    'desconto' => 0,
                    'observacoes' => $obsContaOutros,
                    'origem_tipo' => 'adiantamento_os',
                    'entidade_tipo' => 'ordem_servico',
                    'entidade_id' => $ordemServico->id,
                    'created_by' => auth()->id(),
                ];
                if ($tipoForma === 'pix' && $pixChaveUnica) {
                    $attrsCrOutros['metadata'] = ['pix_chave_id' => $pixChaveUnica['id'] ?? null];
                }
                $contaReceber = ContaReceber::create($attrsCrOutros);
                $contasReceberCriadas[] = $contaReceber;
                $obsEntradaOutros = sprintf(
                    "Recebimento adiantamento OS | Valor total (bruto): R$ %s | %s | Forma: %s | Parcela %s/%s | Data: %s | ContaReceber ID: %s | OS ID: %s",
                    number_format($vp, 2, ',', '.'),
                    $contaReceber->descricao,
                    $formaPagamento->nome,
                    $i,
                    $parcelas,
                    $dataBaixaPadrao->format('d/m/Y'),
                    $contaReceber->id,
                    $ordemServico->id
                );
                $movimentacao = MovimentacaoFinanceira::create([
                    'tenant_id' => $ordemServico->tenant_id ?? auth()->user()->tenant_id ?? null,
                    'conta_bancaria_id' => $contaBancariaId,
                    'tipo' => 'entrada',
                    'origem' => 'conta_receber',
                    'conta_receber_id' => $contaReceber->id,
                    'plano_conta_id' => $planoContaReceitaServico,
                    'centro_custo_id' => $centroCustoOsEncerramento,
                    'valor' => $vp,
                    'data_movimentacao' => $dataBaixaPadrao,
                    'descricao' => 'Recebimento: ' . $contaReceber->descricao,
                    'observacoes' => $obsEntradaOutros,
                    'conciliado' => $conciliacaoOS['conciliado'],
                    'data_conciliacao' => $conciliacaoOS['data_conciliacao'] ?? $dataBaixaPadrao->toDateString(),
                    'conciliado_por' => $conciliacaoOS['conciliado'] ? auth()->id() : null,
                    'created_by' => auth()->id(),
                ]);
                $taxaMovIdAdiantOutros = null;
                if ($tp > 0) {
                    $obsSaidaTaxaOutros = sprintf(
                        "Taxa forma pagamento - Adiantamento OS | Valor taxa: R$ %s | %s | Forma: %s | Parcela %s/%s | Data: %s | ContaReceber ID: %s | OS ID: %s",
                        number_format($tp, 2, ',', '.'),
                        $contaReceber->descricao,
                        $formaPagamento->nome,
                        $i,
                        $parcelas,
                        $dataBaixaPadrao->format('d/m/Y'),
                        $contaReceber->id,
                        $ordemServico->id
                    );
                    $movTaxaAdiantOutros = MovimentacaoFinanceira::create([
                        'tenant_id' => $ordemServico->tenant_id ?? auth()->user()->tenant_id ?? null,
                        'conta_bancaria_id' => $contaBancariaId,
                        'tipo' => 'saida',
                        'origem' => 'outro',
                        'conta_receber_id' => $contaReceber->id,
                        'plano_conta_id' => $planoContaReceitaServico,
                        'centro_custo_id' => $centroCustoOsEncerramento,
                        'valor' => $tp,
                        'data_movimentacao' => $dataBaixaPadrao,
                        'descricao' => 'Taxa: ' . $contaReceber->descricao,
                        'observacoes' => $obsSaidaTaxaOutros,
                        'conciliado' => $conciliacaoOS['conciliado'],
                        'data_conciliacao' => $conciliacaoOS['data_conciliacao'] ?? $dataBaixaPadrao->toDateString(),
                        'conciliado_por' => $conciliacaoOS['conciliado'] ? auth()->id() : null,
                        'created_by' => auth()->id(),
                    ]);
                    $taxaMovIdAdiantOutros = $movTaxaAdiantOutros->id;
                }
                BaixaTitulo::create([
                    'tipo_titulo' => 'conta_receber',
                    'titulo_id' => $contaReceber->id,
                    'movimentacao_id' => $movimentacao->id,
                    'taxa_movimentacao_id' => $taxaMovIdAdiantOutros,
                    'valor_baixa' => $vp,
                    'data_baixa' => $dataBaixaPadrao,
                    'juros' => 0,
                    'multa' => 0,
                    'desconto' => 0,
                    'observacoes' => 'Baixa adiantamento OS | Valor total R$ ' . number_format($vp, 2, ',', '.') . ' | ContaReceber ID: ' . $contaReceber->id . ' | OS ID: ' . $ordemServico->id,
                    'created_by' => auth()->id(),
                ]);
                $contaReceber->valor_recebido = $vp;
                $contaReceber->data_recebimento = $dataBaixaPadrao;
                $contaReceber->save();
            }
        }

        return $contasReceberCriadas;
    }

    private function getStatusOptions(): array
    {
        $opcoes = StatusOs::where('ativo', true)->where('sistema', false)->orderBy('ordem')->orderBy('nome')->pluck('nome')->toArray();
        return array_merge($opcoes, ['Outro']);
    }

    private function getFormData(): array
    {
        return [
            'title' => 'Ordem de Serviço',
            'tenants' => Tenant::orderBy('nome')->get(),
            'clientes' => Cliente::orderBy('nome')->get(),
            'unidades' => Cliente::where('tipo_unidade', 'filial')->orderBy('nome')->get(),
            'contatos' => Contato::orderBy('nome')->get(),
            'enderecos' => Endereco::with('cliente')->get(),
            'produtos' => Produto::with(['tabelasPrecos', 'imagens', 'variacoes'])->orderBy('nome')->get(),
            'servicos' => Servico::orderBy('nome')->get(),
            'tecnicos' => User::orderBy('name')->get(),
            'equipamentos' => Equipamento::orderBy('id')->get(),
            'equipamentos_com_os' => Equipamento::whereHas('ordensServico')->pluck('id')->toArray(),
            'tags' => Tag::where('ativo', true)->where('tipo', 'personalizado')->orderBy('nome')->get(),
            'termosGarantia' => \App\Models\TermoGarantia::where('ativo', true)->orderBy('nome')->get(),
            'statusOptions' => $this->getStatusOptions(),
            'formasPagamento' => FormaPagamento::where('ativo', true)->orderBy('nome')->get(),
            'contasBancarias' => ContaBancaria::where('ativo', true)->orderBy('nome')->get(),
            'adquirentes' => Adquirente::where('ativo', true)->orderBy('nome')->get(),
            'checklistModels' => ChecklistModel::where('ativo', true)->orderBy('nome')->get(),
            'limiteDesconto' => app(LimiteDescontoService::class)->getConfig(),
            'usuarioSujeitoALimiteDesconto' => app(LimiteDescontoService::class)->usuarioSujeitoALimite(),
        ];
    }

    private function normalizarDados(OrdemServicoRequest $request): array
    {
        $data = $request->validated();
        $status = $data['status'] ?? null;
        if ($status === 'Outro' && !empty($data['status_outro'])) {
            $status = $data['status_outro'];
        }
        if ($status) {
            $data['status'] = $status;
        }

        $data['recorrente'] = !empty($data['recorrente']);
        $data['aprovado_cliente'] = !empty($data['aprovado_cliente']);
        $data['coberto_por_contrato'] = !empty($data['coberto_por_contrato']);

        if (!empty($data['equipamento_id'])) {
            $equipamento = Equipamento::find($data['equipamento_id']);
            if ($equipamento) {
                $campos = [
                    'equipamento_marca' => 'marca',
                    'equipamento_modelo' => 'modelo',
                    'equipamento_numero_serie' => 'numero_serie',
                    'equipamento_numero_patrimonio' => 'numero_patrimonio',
                    'equipamento_acessorios' => 'acessorios',
                ];
                foreach ($campos as $campoOs => $campoEquipamento) {
                    if (empty($data[$campoOs]) && !empty($equipamento->{$campoEquipamento})) {
                        $data[$campoOs] = $equipamento->{$campoEquipamento};
                    }
                }
            }
        }

        // Normalizar campos de desbloqueio
        if (empty($data['equipamento_unlock_type'])) {
            $data['equipamento_unlock_type'] = null;
            $data['equipamento_unlock_code'] = null;
        } elseif ($data['equipamento_unlock_type'] === 'none') {
            $data['equipamento_unlock_code'] = null;
        } elseif ($data['equipamento_unlock_type'] === 'pattern') {
            // Para pattern, aceitar apenas se tiver valor válido (não vazio e não '[]')
            if (empty($data['equipamento_unlock_code']) || 
                $data['equipamento_unlock_code'] === '[]' || 
                trim($data['equipamento_unlock_code']) === '') {
                $data['equipamento_unlock_code'] = null;
            }
            // Validar se é um JSON válido
            if ($data['equipamento_unlock_code']) {
                $decoded = json_decode($data['equipamento_unlock_code'], true);
                if (json_last_error() !== JSON_ERROR_NONE || !is_array($decoded) || empty($decoded)) {
                    $data['equipamento_unlock_code'] = null;
                }
            }
        } elseif (empty($data['equipamento_unlock_code'])) {
            $data['equipamento_unlock_code'] = null;
        }

        return Arr::except($data, ['status_outro']);
    }

    private function processarProdutos(OrdemServico $os, array $itens, bool $reset = false): void
    {
        if ($reset) {
            $os->produtos()->delete();
        }

        foreach ($itens as $item) {
            if (empty($item['produto_id']) && empty($item['descricao'])) {
                continue;
            }

            $item = $this->calcularItemProduto($item);
            $os->produtos()->create($item);
        }
    }

    private function processarServicos(OrdemServico $os, array $itens, bool $reset = false): void
    {
        if ($reset) {
            $os->servicos()->delete();
        }

        foreach ($itens as $item) {
            if (empty($item['servico_id']) && empty($item['descricao'])) {
                continue;
            }

            $item = $this->calcularItemServico($item);
            $os->servicos()->create($item);
        }
    }

    private function processarTecnicos(OrdemServico $os, array $itens, bool $reset = false): void
    {
        if ($reset) {
            $os->tecnicos()->delete();
        }

        foreach ($itens as $item) {
            if (empty($item['tecnico_id'])) {
                continue;
            }
            $os->tecnicos()->create($item);
        }
    }

    private function processarApontamentos(OrdemServico $os, array $itens, bool $reset = false): void
    {
        if ($reset) {
            $os->apontamentos()->delete();
        }

        foreach ($itens as $item) {
            if (empty($item['inicio'])) {
                continue;
            }
            $os->apontamentos()->create($item);
        }
    }

    private function processarAnexos(OrdemServico $os, Request $request): void
    {
        $remover = $request->input('anexos_remover', []);
        if (!empty($remover)) {
            $anexos = $os->anexos()->whereIn('id', $remover)->get();
            foreach ($anexos as $anexo) {
                $relDel = $this->resolveOrdemServicoAnexoPublicPath($anexo->caminho_arquivo);
                if ($relDel !== null) {
                    Storage::disk('public')->delete($relDel);
                }
                $anexo->delete();
            }
        }

        $arquivos = $request->file('anexos_arquivo', []);
        $tipos = $request->input('anexos_tipo', []);
        $tags = $request->input('anexos_tags', []);
        $descricoes = $request->input('anexos_descricao', []);
        $metadados = $request->input('anexos_metadata', []);

        foreach ($arquivos as $index => $arquivo) {
            if (!$arquivo || !$arquivo->isValid()) {
                continue;
            }

            $nomeOriginal = $arquivo->getClientOriginalName();
            $nomeArquivo = Str::slug(pathinfo($nomeOriginal, PATHINFO_FILENAME))
                . '_' . time() . '_' . $index . '.' . $arquivo->getClientOriginalExtension();
            $caminho = $arquivo->storeAs('ordens-servico/' . $os->id, $nomeArquivo, 'public');

            $metadata = $metadados[$index] ?? null;
            if (is_string($metadata)) {
                $decoded = json_decode($metadata, true);
                if (json_last_error() === JSON_ERROR_NONE) {
                    $metadata = $decoded;
                }
            }

            $os->anexos()->create([
                'nome_arquivo' => $nomeOriginal,
                'caminho_arquivo' => $caminho,
                'tipo' => $tipos[$index] ?? null,
                'tags' => $tags[$index] ?? null,
                'descricao' => $descricoes[$index] ?? null,
                'metadata' => $metadata,
                'tipo_mime' => $arquivo->getMimeType(),
                'tamanho' => $arquivo->getSize(),
                'created_by' => auth()->id(),
            ]);
        }
    }

    private function calcularItemProduto(array $item): array
    {
        $quantidade = (float) ($item['quantidade'] ?? 0);
        $valor = (float) ($item['valor_unitario'] ?? 0);
        $subtotal = $quantidade * $valor;
        $descontoPercentual = (float) ($item['desconto_percentual'] ?? 0);
        $descontoValor = (float) ($item['desconto_valor'] ?? 0);

        $desconto = $descontoPercentual > 0
            ? ($subtotal * ($descontoPercentual / 100))
            : $descontoValor;

        $tributos = $item['tributos'] ?? [];
        $impostos = collect($tributos)->sum(function ($v) {
            return (float) $v;
        });

        $total = max($subtotal - $desconto, 0);
        $totalComImposto = $total + $impostos;

        return [
            'produto_id' => $item['produto_id'] ?? null,
            'produto_variacao_id' => $item['produto_variacao_id'] ?? null,
            'descricao' => $item['descricao'] ?? null,
            'quantidade' => $quantidade,
            'valor_unitario' => $valor,
            'desconto_valor' => $descontoValor ?: null,
            'desconto_percentual' => $descontoPercentual ?: null,
            'tributos' => $tributos ?: null,
            'impostos_total' => $impostos,
            'total' => $totalComImposto,
        ];
    }

    private function calcularItemServico(array $item): array
    {
        $valor = (float) ($item['valor_unitario'] ?? 0);
        $cobrancaTipo = $item['cobranca_tipo'] ?? 'unidade';
        if ($cobrancaTipo === 'quantidade') {
            $cobrancaTipo = 'unidade';
        } elseif ($cobrancaTipo === 'tempo') {
            $cobrancaTipo = 'horas';
        }
        $quantidadeInformada = (float) ($item['quantidade'] ?? 0);
        $quantidadeHorasInformada = (float) ($item['quantidade_horas'] ?? 0);
        $quantidadeCalculo = $cobrancaTipo === 'horas'
            ? max($quantidadeHorasInformada, 1)
            : max($quantidadeInformada, 1);
        $base = $valor * $quantidadeCalculo;
        $descontoPercentual = (float) ($item['desconto_percentual'] ?? 0);
        $descontoValor = (float) ($item['desconto_valor'] ?? 0);

        $desconto = $descontoPercentual > 0
            ? ($base * ($descontoPercentual / 100))
            : $descontoValor;

        $total = max($base - $desconto, 0);

        return [
            'servico_id' => $item['servico_id'] ?? null,
            'descricao' => $item['descricao'] ?? null,
            'cobranca_tipo' => $cobrancaTipo,
            'quantidade' => $cobrancaTipo === 'unidade' ? $quantidadeCalculo : null,
            'quantidade_horas' => $cobrancaTipo === 'horas' ? $quantidadeCalculo : null,
            'tecnico_id' => $item['tecnico_id'] ?? null,
            'valor_unitario' => $valor,
            'desconto_valor' => $descontoValor ?: null,
            'desconto_percentual' => $descontoPercentual ?: null,
            'total' => $total,
        ];
    }

    /**
     * Calcula o total da OS (produtos + serviços) a partir dos dados do request.
     * Usado para validar desconto global contra o total que será salvo.
     */
    private function calcularTotalOsDoRequest(Request $request): float
    {
        $total = 0.0;
        foreach ($request->input('produtos', []) as $item) {
            $total += $this->calcularItemProduto($item)['total'];
        }
        foreach ($request->input('servicos', []) as $item) {
            $total += $this->calcularItemServico($item)['total'];
        }
        return $total;
    }

    private function atualizarTotais(OrdemServico $os, Request $request): void
    {
        $produtos = $os->produtos()->get();
        $servicos = $os->servicos()->get();

        $totalProdutos = $produtos->sum('total');
        $totalServicos = $servicos->sum('total');
        $descontosItens = $produtos->sum(function ($item) {
            $subtotal = (float) $item->quantidade * (float) $item->valor_unitario;
            return max($subtotal - ($item->total - $item->impostos_total), 0);
        }) + $servicos->sum(function ($item) {
            $base = $item->cobranca_tipo === 'quantidade' && $item->quantidade
                ? ((float) $item->quantidade * (float) $item->valor_unitario)
                : (float) $item->valor_unitario;
            return max($base - (float) $item->total, 0);
        });

        $impostosItens = $produtos->sum('impostos_total');
        $descontoGlobal = (float) $request->input('desconto_global', 0);
        $acrescimos = (float) $request->input('acrescimos_global', 0);
        $impostosGlobal = (float) $request->input('impostos_global', 0);

        $os->desconto_global = $descontoGlobal;
        $os->acrescimos_global = $acrescimos;
        $os->impostos_global = $impostosGlobal;
        $os->total_produtos = $totalProdutos;
        $os->total_servicos = $totalServicos;
        $os->total_descontos = $descontosItens + $descontoGlobal;
        $os->total_acrescimos = $acrescimos;
        $os->total_impostos = $impostosItens + $impostosGlobal;
        $os->total_geral = $totalProdutos + $totalServicos - $descontoGlobal + $acrescimos + $impostosGlobal;
        $os->save();
    }

    private function snapshotItens(OrdemServico $os): string
    {
        $linhas = [];

        foreach (($os->produtos ?? collect()) as $p) {
            $nome = $p->produto?->nome ?? $p->descricao ?? ('Produto #' . ($p->produto_id ?? '-'));
            $qtd = $p->quantidade ?? 0;
            $valor = $p->valor_unitario ?? 0;
            $total = $p->total ?? 0;
            $linhas[] = sprintf('Produto(%s) qtd=%s valor=%s total=%s', $nome, $qtd, $valor, $total);
        }

        foreach (($os->servicos ?? collect()) as $s) {
            $nome = $s->servico?->nome ?? $s->descricao ?? ('Serviço #' . ($s->servico_id ?? '-'));
            $qtd = $s->quantidade ?? null;
            $qtdHoras = $s->quantidade_horas ?? null;
            $qtdTexto = $qtdHoras !== null ? ($qtdHoras . 'h') : ($qtd !== null ? $qtd : '-');
            $valor = $s->valor_unitario ?? 0;
            $total = $s->total ?? 0;
            $linhas[] = sprintf('Serviço(%s) qtd=%s valor=%s total=%s', $nome, $qtdTexto, $valor, $total);
        }

        if (empty($linhas)) {
            return '(nenhum)';
        }

        return implode(' | ', $linhas);
    }

    private function snapshotEquipe(OrdemServico $os): string
    {
        $linhas = [];

        foreach (($os->tecnicos ?? collect()) as $t) {
            $nome = $t->tecnico?->name ?? ('Tec #' . ($t->tecnico_id ?? '-'));
            $inicio = $t->inicio ? $t->inicio->format('d/m/Y H:i') : '-';
            $fim = $t->fim ? $t->fim->format('d/m/Y H:i') : '-';
            $status = $t->status ?? '-';
            $horas = $t->horas_trabalhadas ?? '-';
            $linhas[] = sprintf('Técnico(%s) %s→%s status=%s horas=%s', $nome, $inicio, $fim, $status, $horas);
        }

        foreach (($os->apontamentos ?? collect()) as $a) {
            $nome = $a->tecnico?->name ?? ('Tec #' . ($a->tecnico_id ?? '-'));
            $inicio = $a->inicio ? $a->inicio->format('d/m/Y H:i') : '-';
            $fim = $a->fim ? $a->fim->format('d/m/Y H:i') : '-';
            $status = $a->status ?? '-';
            $linhas[] = sprintf('Apontamento(%s) %s→%s status=%s', $nome, $inicio, $fim, $status);
        }

        if (empty($linhas)) {
            return '(nenhuma)';
        }

        return implode(' | ', $linhas);
    }

    private function snapshotAnexos(OrdemServico $os): string
    {
        $linhas = [];

        foreach (($os->anexos ?? collect()) as $a) {
            $tipo = $a->tipo ?? '';
            $tags = $a->tags;
            if (is_array($tags)) {
                $tags = implode(',', $tags);
            }
            $tagsTexto = $tags !== null && $tags !== '' ? (' tags=' . (string) $tags) : '';
            $desc = $a->descricao ?? '';
            $descTexto = $desc !== '' ? (' desc=' . (string) $desc) : '';
            $linhas[] = sprintf('Anexo(%s)%s%s%s', $a->nome_arquivo ?? ('arquivo#' . ($a->id ?? '-')), $tipo !== '' ? (' tipo=' . $tipo) : '', $tagsTexto, $descTexto);
        }

        if (empty($linhas)) {
            return '(nenhum)';
        }

        return implode(' | ', $linhas);
    }

    private function calcularSlaCumprido(OrdemServico $os): ?bool
    {
        if (!$os->prevista_conclusao || !$os->real_conclusao) {
            return null;
        }
        return $os->real_conclusao->lessThanOrEqualTo($os->prevista_conclusao);
    }

    private function registrarLog(OrdemServico $os, string $tipo, string $descricao, array $dados): void
    {
        OrdemServicoLog::create([
            'ordem_servico_id' => $os->id,
            'user_id' => auth()->id(),
            'tipo' => $tipo,
            'descricao' => $descricao,
            'dados' => $dados ?: null,
            'ip' => request()->ip(),
        ]);
    }

    private function baixarEstoque(OrdemServico $os): void
    {
        if ($os->estoque_baixado) {
            return;
        }

        foreach ($os->produtos()->with(['produto.composicoes.componente', 'variacao'])->get() as $item) {
            if (!$item->produto) {
                continue;
            }
            $produto = $item->produto;
            $qtdItem = (float) $item->quantidade;

            // Variação: dar baixa na variação
            if ($item->produto_variacao_id && $item->variacao) {
                $variacao = $item->variacao;
                if ($variacao->quantidade !== null) {
                    $novaQuantidade = (float) $variacao->quantidade - $qtdItem;
                    $variacao->quantidade = max($novaQuantidade, 0);
                    $variacao->save();
                }
                continue;
            }

            // Kit: dar baixa nos componentes
            if ($produto->formato === 'composicao' && $produto->composicoes->count() > 0) {
                foreach ($produto->composicoes as $comp) {
                    $componente = $comp->componente;
                    if (!$componente || $componente->estoque_quantidade === null) {
                        continue;
                    }
                    $qtdBaixa = (float) ($comp->quantidade ?? 0) * $qtdItem;
                    if ($qtdBaixa <= 0) {
                        continue;
                    }
                    $novaQuantidade = (float) $componente->estoque_quantidade - $qtdBaixa;
                    $componente->estoque_quantidade = max($novaQuantidade, 0);
                    $componente->save();
                }
                continue;
            }

            // Produto unitário: dar baixa no próprio produto
            if ($produto->estoque_quantidade === null) {
                continue;
            }
            $novaQuantidade = (float) $produto->estoque_quantidade - $qtdItem;
            $produto->estoque_quantidade = max($novaQuantidade, 0);
            $produto->save();
        }

        $os->estoque_baixado = true;
        $os->save();
    }

    private function integrarFinanceiro(OrdemServico $os): void
    {
        if ($os->financeiro_gerado) {
            return;
        }

        $planoContaId = Configuracao::getPlanoContaOsEncerramento()
            ?? PlanoConta::where('codigo', '1.1')->orWhere('nome', 'Receita de Serviços')->first()?->id;
        $centroCustoId = Configuracao::getCentroCustoOsEncerramento();

        ContaReceber::create([
            'ordem_servico_id' => $os->id,
            'cliente_id' => $os->cliente_id,
            'descricao' => $os->codigo_interno,
            'valor' => $os->total_geral,
            'data_vencimento' => now()->addDays(7)->toDateString(),
            'numero_parcela' => 1,
            'total_parcelas' => 1,
            'status' => 'aberto',
            'plano_conta_id' => $planoContaId,
            'centro_custo_id' => $centroCustoId,
            'created_by' => auth()->id(),
        ]);

        $os->financeiro_gerado = true;
        $os->save();
    }

    public function encerrar(Request $request, OrdemServico $ordemServico)
    {
        $respondJson = $request->wantsJson() || $request->ajax()
            || strtolower((string) $request->header('X-Requested-With')) === 'xmlhttprequest';

        // Normalizar valores em formato brasileiro (1.738,36 → 1738.36) antes de qualquer validação
        $request->merge([
            'desconto_valor' => parse_br_number($request->desconto_valor),
        ]);
        if ($request->tipo_pagamento === 'agora' && is_array($request->pagamentos ?? null)) {
            $pagamentos = $request->pagamentos;
            foreach ($pagamentos as $i => $p) {
                if (isset($p['valor'])) {
                    $pagamentos[$i]['valor'] = parse_br_number($p['valor']);
                }
            }
            $request->merge(['pagamentos' => $pagamentos]);
        }

        // Verificar se já está encerrada
        if ($ordemServico->status === 'Encerrado' || $ordemServico->status === 'Encerrada') {
            $msg = 'Esta OS já está encerrada.';
            if ($respondJson) {
                return response()->json(['message' => $msg], 422);
            }
            return redirect()->back()->with('error', $msg);
        }

        // Validar limite de desconto no encerramento ANTES da transação (perfis sujeitos)
        $limiteServiceEncerrar = app(LimiteDescontoService::class);
        if ($limiteServiceEncerrar->usuarioSujeitoALimite()) {
            $ehGarantiaOuContrato = in_array($ordemServico->garantia_tipo, ['dentro', 'estendida'])
                || $ordemServico->coberto_por_contrato;
            $valorTotal = $ehGarantiaOuContrato ? 0 : (float) $ordemServico->total_geral;
            // Sempre validar desconto quando há valor a pagar (inclui desconto 0 ou qualquer valor informado)
            if ($valorTotal > 0) {
                $descontoTipo = $request->input('desconto_tipo', 'valor');
                $descontoValor = (float) $request->input('desconto_valor', 0);
                $validacaoDesconto = $limiteServiceEncerrar->validarDescontoGlobal(
                    $valorTotal,
                    in_array($descontoTipo, ['valor', 'percentual'], true) ? $descontoTipo : 'valor',
                    $descontoValor
                );
                if (!$validacaoDesconto['valido']) {
                    $msg = 'Desconto no encerramento: ' . $validacaoDesconto['mensagem'];
                    if ($respondJson) {
                        return response()->json(['message' => $msg], 422);
                    }
                    return redirect()->back()->withInput()->with('error', $msg);
                }
            }
        }

        // Validação customizada para pagamentos
        $rules = [
            'desconto_tipo' => 'nullable|in:valor,percentual',
            'desconto_valor' => 'nullable|numeric|min:0',
            'tipo_pagamento' => 'required|in:agora,faturado',
            'imprimir_comprovante' => 'nullable|boolean',
        ];
        $ehGarantiaOuContratoVal = in_array($ordemServico->garantia_tipo, ['dentro', 'estendida']) || $ordemServico->coberto_por_contrato;
        $valorTotalVal = $ehGarantiaOuContratoVal ? 0 : (float) $ordemServico->total_geral;
        $descontoVal = 0;
        if (!$ehGarantiaOuContratoVal && $request->desconto_valor) {
            $descontoVal = $request->desconto_tipo === 'percentual'
                ? ($valorTotalVal * (float) $request->desconto_valor) / 100
                : (float) $request->desconto_valor;
            $descontoVal = min($descontoVal, $valorTotalVal);
        }
        $valorAReceberVal = max(0, $valorTotalVal - $descontoVal - (float) $ordemServico->total_adiantado_confirmado);
        // Exigir pagamentos apenas quando há valor a receber e tipo é "agora"
        if ($request->tipo_pagamento === 'agora' && $valorAReceberVal > 0) {
            $rules['pagamentos'] = 'required|array|min:1';
            $rules['pagamentos.*.forma_pagamento_id'] = 'required|exists:formas_pagamento,id';
            $rules['pagamentos.*.valor'] = 'required|numeric|min:0.01';
            $rules['pagamentos.*.parcelas'] = 'nullable|integer|min:1';
            $rules['pagamentos.*.conta_bancaria_id'] = 'nullable|exists:contas_bancarias,id';
            $rules['pagamentos.*.adquirente_id'] = 'nullable|exists:adquirentes,id';
            $rules['pagamentos.*.bandeira'] = 'nullable|string|in:master,visa,elo,amex,hipercard,outros';
        }
        
        $validated = $this->validateWithFilters($request, $rules, [
            'tipo_pagamento.required' => 'Selecione o tipo de pagamento.',
            'pagamentos.required' => 'É necessário adicionar pelo menos uma forma de pagamento quando o tipo é "Pagamento Agora".',
            'pagamentos.min' => 'É necessário adicionar pelo menos uma forma de pagamento.',
            'pagamentos.*.forma_pagamento_id.required' => 'Selecione a forma de pagamento.',
            'pagamentos.*.forma_pagamento_id.exists' => 'A forma de pagamento selecionada não existe.',
            'pagamentos.*.valor.required' => 'Informe o valor do pagamento.',
            'pagamentos.*.valor.min' => 'O valor do pagamento deve ser maior que zero.',
        ]);

        DB::beginTransaction();
        try {
            // Verificar se é garantia/contrato (valor R$ 0,00)
            $ehGarantiaOuContrato = in_array($ordemServico->garantia_tipo, ['dentro', 'estendida']) 
                || $ordemServico->coberto_por_contrato;
            
            $valorTotal = $ehGarantiaOuContrato ? 0 : (float)$ordemServico->total_geral;
            
            // Calcular desconto
            $desconto = 0;
            if (!$ehGarantiaOuContrato && $request->desconto_valor) {
                if ($request->desconto_tipo === 'percentual') {
                    $desconto = ($valorTotal * (float)$request->desconto_valor) / 100;
                } else {
                    $desconto = (float)$request->desconto_valor;
                }
                $desconto = min($desconto, $valorTotal);
            }
            
            $valorAReceber = max(0, $valorTotal - $desconto);
            $totalAdiantado = (float) $ordemServico->total_adiantado_confirmado;
            $valorAReceber = max(0, $valorAReceber - $totalAdiantado);
            $valorJaCobertoPorAdiantamento = ($valorAReceber <= 0 && $totalAdiantado > 0);

            // Validar pagamentos se for "agora" e ainda houver valor a receber
            if ($request->tipo_pagamento === 'agora' && !$valorJaCobertoPorAdiantamento) {
                if (empty($request->pagamentos) || !is_array($request->pagamentos) || count($request->pagamentos) === 0) {
                    DB::rollBack();
                    $msg = 'É necessário adicionar pelo menos uma forma de pagamento quando o tipo é "Pagamento Agora".';
                    if ($respondJson) {
                        return response()->json(['message' => $msg], 422);
                    }
                    return redirect()->back()->withInput()->with('error', $msg);
                }
                
                // Filtrar pagamentos válidos
                $pagamentosValidos = array_filter($request->pagamentos, function($p) {
                    return !empty($p['forma_pagamento_id']) && !empty($p['valor']) && (float)$p['valor'] > 0;
                });
                
                if (empty($pagamentosValidos)) {
                    DB::rollBack();
                    $msg = 'É necessário preencher corretamente pelo menos uma forma de pagamento.';
                    if ($respondJson) {
                        return response()->json(['message' => $msg], 422);
                    }
                    return redirect()->back()->withInput()->with('error', $msg);
                }
                
                $totalPagamentos = collect($pagamentosValidos)->sum(function($p) {
                    return (float)($p['valor'] ?? 0);
                });
                $tolerancia = 0.01; // Tolerância de 1 centavo para arredondamentos
                if (abs($totalPagamentos - $valorAReceber) > $tolerancia) {
                    DB::rollBack();
                    $msg = "A soma dos pagamentos (R$ " . number_format($totalPagamentos, 2, ',', '.') . ") deve ser igual ao valor a receber (R$ " . number_format($valorAReceber, 2, ',', '.') . ").";
                    if ($respondJson) {
                        return response()->json(['message' => $msg], 422);
                    }
                    return redirect()->back()->withInput()->with('error', $msg);
                }
            }

            // Plano de conta e centro de custo: configurados em Configurações ou padrão
            $planoContaReceitaServico = Configuracao::getPlanoContaOsEncerramento()
                ?? PlanoConta::where('codigo', '1.1')->orWhere('nome', 'Receita de Serviços')->first()?->id;
            $centroCustoOsEncerramento = Configuracao::getCentroCustoOsEncerramento();

            // Processar pagamentos
            $contasReceberCriadas = [];
            $foiPago = $valorJaCobertoPorAdiantamento;
            $adquirenteService = app(AdquirenteService::class);

            if ($request->tipo_pagamento === 'agora' && !empty($request->pagamentos) && !$valorJaCobertoPorAdiantamento) {
                // Filtrar apenas pagamentos válidos
                $pagamentosValidos = array_filter($request->pagamentos, function($p) {
                    return !empty($p['forma_pagamento_id']) && !empty($p['valor']) && (float)$p['valor'] > 0;
                });

                if (empty($pagamentosValidos)) {
                    DB::rollBack();
                    return redirect()->back()
                        ->withInput()
                        ->with('error', 'Nenhum pagamento válido foi encontrado. Verifique se todos os campos estão preenchidos corretamente.');
                }

                foreach ($pagamentosValidos as $pagamento) {
                    $formaPagamento = FormaPagamento::find($pagamento['forma_pagamento_id']);
                    if (!$formaPagamento) {
                        Log::warning('Forma de pagamento não encontrada ao encerrar OS', [
                            'forma_pagamento_id' => $pagamento['forma_pagamento_id'] ?? null,
                            'os_id' => $ordemServico->id,
                        ]);
                        DB::rollBack();
                        return redirect()->back()
                            ->withInput()
                            ->with('error', 'Forma de pagamento não encontrada. Por favor, selecione uma forma de pagamento válida.');
                    }

                    $valorPagamento = (float)$pagamento['valor'];
                    $parcelas = (int)($pagamento['parcelas'] ?? 1);
                    $tipoForma = $formaPagamento->tipo ?? '';

                    $adquirenteIdEfetivo = $formaPagamento->resolverAdquirenteId((int) ($pagamento['adquirente_id'] ?? 0));
                    $contaExplicita = ! empty($pagamento['conta_bancaria_id']) ? (int) $pagamento['conta_bancaria_id'] : null;
                    $contaBancariaId = $formaPagamento->resolverContaBancariaIdParaRecebimento($contaExplicita, $adquirenteIdEfetivo);
                    if (! $contaBancariaId) {
                        $contaBancariaPadrao = ContaBancaria::where('ativo', true)->first();
                        if (! $contaBancariaPadrao) {
                            DB::rollBack();
                            return redirect()->back()
                                ->withInput()
                                ->with('error', 'É necessário ter pelo menos uma conta bancária ativa. Associe uma conta à forma de pagamento, ao adquirente ou escolha no encerramento.');
                        }
                        $contaBancariaId = $contaBancariaPadrao->id;
                    }

                    // Transferência ou Boleto: só cria conta a receber (aberto), sem baixa automática
                    if (in_array($tipoForma, ['transferencia', 'boleto'])) {
                        $valorParcela = $parcelas > 1 ? round($valorPagamento / $parcelas, 2) : $valorPagamento;
                        $resto = round($valorPagamento - ($valorParcela * $parcelas), 2);
                        for ($i = 1; $i <= $parcelas; $i++) {
                            $vp = $i === 1 ? $valorParcela + $resto : $valorParcela;
                            $dataVenc = $i === 1 ? now()->addDays($formaPagamento->dias_recebimento ?? 1) : now()->addDays(($formaPagamento->dias_recebimento ?? 1) + ($i - 1) * 30);
                            $obsTransferBoleto = sprintf(
                                'OS: %s | Forma: %s | Valor: R$ %s | Parcela: %s/%s | Vencimento: %s | Status: aberto (aguardando confirmação)',
                                $ordemServico->codigo_interno,
                                $formaPagamento->nome,
                                number_format($vp, 2, ',', '.'),
                                $i,
                                $parcelas,
                                $dataVenc->format('d/m/Y')
                            );
                            $contaReceber = ContaReceber::create([
                                'ordem_servico_id' => $ordemServico->id,
                                'cliente_id' => $ordemServico->cliente_id,
                                'descricao' => $ordemServico->codigo_interno . ($parcelas > 1 ? " - Parcela {$i}/{$parcelas}" : ''),
                                'valor' => $vp,
                                'valor_original' => $vp,
                                'data_vencimento' => $dataVenc,
                                'numero_parcela' => $parcelas > 1 ? $i : 1,
                                'total_parcelas' => $parcelas > 1 ? $parcelas : 1,
                                'forma_pagamento_id' => $formaPagamento->id,
                                'conta_bancaria_id' => $contaBancariaId,
                                'plano_conta_id' => $planoContaReceitaServico,
                                'centro_custo_id' => $centroCustoOsEncerramento,
                                'status' => 'aberto',
                                'desconto' => 0,
                                'observacoes' => $obsTransferBoleto,
                                'created_by' => auth()->id(),
                            ]);
                            $contasReceberCriadas[] = $contaReceber;
                        }
                        continue;
                    }

                    // Cartão de crédito: adquirente + bandeira obrigatórios; taxas e datas pela adquirente
                    if ($tipoForma === 'cartao_credito') {
                        $adquirenteId = $adquirenteIdEfetivo;
                        $bandeira = $pagamento['bandeira'] ?? '';
                        if (! $adquirenteId || ! $bandeira) {
                            DB::rollBack();
                            return redirect()->back()
                                ->withInput()
                                ->with('error', 'Para Cartão de Crédito informe Adquirente e Bandeira do cartão.');
                        }
                        if (! $contaBancariaId) {
                            DB::rollBack();
                            return redirect()->back()
                                ->withInput()
                                ->with('error', 'Adquirente selecionada não possui conta bancária padrão. Cadastre em Financeiro > Adquirentes.');
                        }
                        try {
                            $simulacao = $adquirenteService->simularTransacao($valorPagamento, $parcelas, $adquirenteId, $bandeira, true);
                        } catch (\Exception $e) {
                            DB::rollBack();
                            return redirect()->back()
                                ->withInput()
                                ->with('error', 'Erro ao simular taxas do cartão: ' . $e->getMessage());
                        }
                        $agenda = $simulacao['agenda_recebiveis'];
                        $taxaTotal = $simulacao['taxa_total_aplicada'];
                        $valorBrutoParcelas = $valorPagamento;
                        $numItens = count($agenda);
                        $valorBrutoPorParcela = $numItens > 0 ? round($valorBrutoParcelas / $numItens, 2) : $valorBrutoParcelas;
                        $restoBruto = round($valorBrutoParcelas - ($valorBrutoPorParcela * $numItens), 2);
                        $adquirenteNome = Adquirente::find($adquirenteId)->nome ?? 'N/A';
                        foreach ($agenda as $idx => $item) {
                            $valorBruto = $idx === 0 ? $valorBrutoPorParcela + $restoBruto : $valorBrutoPorParcela;
                            $valorLiquido = (float)$item['valor'];
                            $taxaParcela = round($valorBruto - $valorLiquido, 2);
                            $dataReceb = \Carbon\Carbon::parse($item['data']);
                            $parcelaNum = $item['parcela'] ?? ($idx + 1);
                            $totalParc = $item['total_parcelas'] ?? $numItens;
                            $obsCredito = sprintf(
                                'OS: %s | Forma: %s | Adquirente: %s | Bandeira: %s | Valor bruto: R$ %s | Taxa: R$ %s | Valor líquido: R$ %s | Parcela: %s/%s | Previsão recebimento: %s | Status: pago (baixa automática)',
                                $ordemServico->codigo_interno,
                                $formaPagamento->nome,
                                $adquirenteNome,
                                $bandeira,
                                number_format($valorBruto, 2, ',', '.'),
                                number_format($taxaParcela, 2, ',', '.'),
                                number_format($valorLiquido, 2, ',', '.'),
                                $parcelaNum,
                                $totalParc,
                                $dataReceb->format('d/m/Y')
                            );
                            $contaReceber = ContaReceber::create([
                                'ordem_servico_id' => $ordemServico->id,
                                'cliente_id' => $ordemServico->cliente_id,
                                'descricao' => $ordemServico->codigo_interno . ($totalParc > 1 ? " - Parcela {$parcelaNum}/{$totalParc}" : ''),
                                'valor' => $valorBruto,
                                'valor_original' => $valorBruto,
                                'data_vencimento' => $dataReceb,
                                'numero_parcela' => $totalParc > 1 ? $parcelaNum : 1,
                                'total_parcelas' => $totalParc > 1 ? $totalParc : 1,
                                'forma_pagamento_id' => $formaPagamento->id,
                                'adquirente_id' => $adquirenteId,
                                'bandeira' => $bandeira,
                                'conta_bancaria_id' => $contaBancariaId,
                                'plano_conta_id' => $planoContaReceitaServico,
                                'centro_custo_id' => $centroCustoOsEncerramento,
                                'status' => 'pago',
                                'desconto' => 0,
                                'observacoes' => $obsCredito,
                                'created_by' => auth()->id(),
                            ]);
                            $contasReceberCriadas[] = $contaReceber;

                            // Agenda da adquirente: pré-concilia com a data exata de liquidação.
                            // Parcelas futuras ficam conciliadas mas não entram no saldo_atual
                            // (que filtra por data_movimentacao <= hoje).
                            // Caixa: entrada no valor BRUTO + saída da taxa = líquido (igual PDV / FinanceiroVendaPagamentosService)
                            $obsEntradaCreditoOs = sprintf(
                                'Recebimento bruto R$ %s | Líquido na conta R$ %s | Taxa R$ %s | OS %s',
                                number_format($valorBruto, 2, ',', '.'),
                                number_format($valorLiquido, 2, ',', '.'),
                                number_format($taxaParcela, 2, ',', '.'),
                                $ordemServico->codigo_interno
                            );
                            $movimentacao = MovimentacaoFinanceira::create([
                                'conta_bancaria_id' => $contaBancariaId,
                                'tipo' => 'entrada',
                                'origem' => 'conta_receber',
                                'conta_receber_id' => $contaReceber->id,
                                'plano_conta_id' => $planoContaReceitaServico,
                                'centro_custo_id' => $centroCustoOsEncerramento,
                                'valor' => $valorBruto,
                                'data_movimentacao' => $dataReceb,
                                'descricao' => "Recebimento: {$contaReceber->descricao}",
                                'observacoes' => $obsEntradaCreditoOs,
                                'conciliado' => true,
                                'data_conciliacao' => $dataReceb->toDateString(),
                                'conciliado_por' => auth()->id(),
                                'created_by' => auth()->id(),
                            ]);
                            $taxaMovIdEncCred = null;
                            if ($taxaParcela > 0) {
                                $movTaxaEncCred = MovimentacaoFinanceira::create([
                                    'conta_bancaria_id' => $contaBancariaId,
                                    'tipo' => 'saida',
                                    'origem' => 'outro',
                                    'conta_receber_id' => $contaReceber->id,
                                    'plano_conta_id' => $planoContaReceitaServico,
                                    'centro_custo_id' => $centroCustoOsEncerramento,
                                    'valor' => $taxaParcela,
                                    'data_movimentacao' => $dataReceb,
                                    'descricao' => "Taxa adquirente: {$contaReceber->descricao}",
                                    'observacoes' => 'Taxa cartão crédito (encerramento OS)',
                                    'conciliado' => true,
                                    'data_conciliacao' => $dataReceb->toDateString(),
                                    'conciliado_por' => auth()->id(),
                                    'created_by' => auth()->id(),
                                ]);
                                $taxaMovIdEncCred = $movTaxaEncCred->id;
                            }
                            BaixaTitulo::create([
                                'tipo_titulo' => 'conta_receber',
                                'titulo_id' => $contaReceber->id,
                                'movimentacao_id' => $movimentacao->id,
                                'taxa_movimentacao_id' => $taxaMovIdEncCred,
                                'valor_baixa' => $valorBruto,
                                'data_baixa' => $dataReceb,
                                'juros' => 0,
                                'multa' => 0,
                                'desconto' => 0,
                                'observacoes' => 'Baixa automática no encerramento da OS (cartão)',
                                'created_by' => auth()->id(),
                            ]);
                            $contaReceber->valor_recebido = $valorBruto;
                            $contaReceber->data_recebimento = $dataReceb;
                            $contaReceber->save();
                            $foiPago = true;
                        }
                        continue;
                    }

                    // Cartão débito: mesma lógica do crédito — adquirente + bandeira; taxas e data pela adquirente
                    if ($tipoForma === 'cartao_debito') {
                        $adquirenteId = $adquirenteIdEfetivo;
                        $bandeira = $pagamento['bandeira'] ?? '';
                        if (! $adquirenteId || ! $bandeira) {
                            DB::rollBack();
                            return redirect()->back()
                                ->withInput()
                                ->with('error', 'Para Cartão de Débito informe Adquirente e Bandeira do cartão.');
                        }
                        if (! $contaBancariaId) {
                            DB::rollBack();
                            return redirect()->back()
                                ->withInput()
                                ->with('error', 'Adquirente selecionada não possui conta bancária padrão. Cadastre em Financeiro > Adquirentes.');
                        }
                        try {
                            $simulacao = $adquirenteService->simularTransacaoDebito($valorPagamento, $adquirenteId, $bandeira);
                        } catch (\Exception $e) {
                            DB::rollBack();
                            return redirect()->back()
                                ->withInput()
                                ->with('error', 'Erro ao simular taxas do cartão de débito: ' . $e->getMessage());
                        }
                        $agenda = $simulacao['agenda_recebiveis'];
                        $taxaTotal = $simulacao['taxa_total_aplicada'];
                        $valorLiquido = $simulacao['valor_liquido'];
                        if (empty($agenda)) {
                            DB::rollBack();
                            return redirect()->back()
                                ->withInput()
                                ->with('error', 'Adquirente não retornou data de recebimento para débito.');
                        }
                        $item = $agenda[0];
                        $dataReceb = \Carbon\Carbon::parse($item['data']);
                        $adquirenteNomeDeb = Adquirente::find($adquirenteId)->nome ?? 'N/A';
                        $obsDebito = sprintf(
                            'OS: %s | Forma: %s | Adquirente: %s | Bandeira: %s | Valor: R$ %s | Taxa: R$ %s | Valor líquido: R$ %s | Previsão recebimento: %s | Status: pago (baixa automática)',
                            $ordemServico->codigo_interno,
                            $formaPagamento->nome,
                            $adquirenteNomeDeb,
                            $bandeira,
                            number_format($valorPagamento, 2, ',', '.'),
                            number_format($taxaTotal, 2, ',', '.'),
                            number_format($valorLiquido, 2, ',', '.'),
                            $dataReceb->format('d/m/Y')
                        );
                        $contaReceber = ContaReceber::create([
                            'ordem_servico_id' => $ordemServico->id,
                            'cliente_id' => $ordemServico->cliente_id,
                            'descricao' => $ordemServico->codigo_interno,
                            'valor' => $valorPagamento,
                            'valor_original' => $valorPagamento,
                            'data_vencimento' => $dataReceb,
                            'numero_parcela' => 1,
                            'total_parcelas' => 1,
                            'forma_pagamento_id' => $formaPagamento->id,
                            'adquirente_id' => $adquirenteId,
                            'bandeira' => $bandeira,
                            'conta_bancaria_id' => $contaBancariaId,
                            'plano_conta_id' => $planoContaReceitaServico,
                            'centro_custo_id' => $centroCustoOsEncerramento,
                            'status' => 'pago',
                            'desconto' => 0,
                            'observacoes' => $obsDebito,
                            'created_by' => auth()->id(),
                        ]);
                        $contasReceberCriadas[] = $contaReceber;
                        // Agenda da adquirente: pré-concilia com a data exata de liquidação (D+1 débito).
                        // Não afeta o saldo_atual se a data for futura (filtro por data_movimentacao <= hoje).
                        $obsEntradaDebitoOs = sprintf(
                            'Recebimento bruto R$ %s | Líquido na conta R$ %s | Taxa R$ %s | OS %s',
                            number_format($valorPagamento, 2, ',', '.'),
                            number_format($valorLiquido, 2, ',', '.'),
                            number_format($taxaTotal, 2, ',', '.'),
                            $ordemServico->codigo_interno
                        );
                        $movimentacao = MovimentacaoFinanceira::create([
                            'conta_bancaria_id' => $contaBancariaId,
                            'tipo' => 'entrada',
                            'origem' => 'conta_receber',
                            'conta_receber_id' => $contaReceber->id,
                            'plano_conta_id' => $planoContaReceitaServico,
                            'centro_custo_id' => $centroCustoOsEncerramento,
                            'valor' => $valorPagamento,
                            'data_movimentacao' => $dataReceb,
                            'descricao' => "Recebimento: {$contaReceber->descricao}",
                            'observacoes' => $obsEntradaDebitoOs,
                            'conciliado' => true,
                            'data_conciliacao' => $dataReceb->toDateString(),
                            'conciliado_por' => auth()->id(),
                            'created_by' => auth()->id(),
                        ]);
                        $taxaMovIdEncDeb = null;
                        if ($taxaTotal > 0) {
                            $movTaxaEncDeb = MovimentacaoFinanceira::create([
                                'conta_bancaria_id' => $contaBancariaId,
                                'tipo' => 'saida',
                                'origem' => 'outro',
                                'conta_receber_id' => $contaReceber->id,
                                'plano_conta_id' => $planoContaReceitaServico,
                                'centro_custo_id' => $centroCustoOsEncerramento,
                                'valor' => $taxaTotal,
                                'data_movimentacao' => $dataReceb,
                                'descricao' => "Taxa adquirente: {$contaReceber->descricao}",
                                'observacoes' => 'Taxa cartão débito (encerramento OS)',
                                'conciliado' => true,
                                'data_conciliacao' => $dataReceb->toDateString(),
                                'conciliado_por' => auth()->id(),
                                'created_by' => auth()->id(),
                            ]);
                            $taxaMovIdEncDeb = $movTaxaEncDeb->id;
                        }
                        BaixaTitulo::create([
                            'tipo_titulo' => 'conta_receber',
                            'titulo_id' => $contaReceber->id,
                            'movimentacao_id' => $movimentacao->id,
                            'taxa_movimentacao_id' => $taxaMovIdEncDeb,
                            'valor_baixa' => $valorPagamento,
                            'data_baixa' => $dataReceb,
                            'juros' => 0,
                            'multa' => 0,
                            'desconto' => 0,
                            'observacoes' => 'Baixa automática no encerramento da OS (débito)',
                            'created_by' => auth()->id(),
                        ]);
                        $contaReceber->valor_recebido = $valorPagamento;
                        $contaReceber->data_recebimento = $dataReceb;
                        $contaReceber->save();
                        $foiPago = true;
                        continue;
                    }

                    // PIX parcelado: datas por parcela — só dá baixa automática nas parcelas com vencimento até hoje; demais ficam abertas no financeiro
                    if ($tipoForma === 'pix' && $parcelas > 1) {
                        $vencimentos = $pagamento['pix_parcela_vencimentos'] ?? [];
                        if (! is_array($vencimentos) || count($vencimentos) !== $parcelas) {
                            DB::rollBack();
                            $msg = 'Para PIX parcelado, informe a data de cada parcela.';
                            if ($respondJson) {
                                return response()->json(['message' => $msg], 422);
                            }

                            return redirect()->back()->withInput()->with('error', $msg);
                        }
                        $pixChaveParcelado = $formaPagamento->encontrarPixChavePorId($pagamento['pix_chave_id'] ?? null);
                        if (! $pixChaveParcelado) {
                            DB::rollBack();
                            $msg = 'Selecione a chave PIX de recebimento para pagamento parcelado.';
                            if ($respondJson) {
                                return response()->json(['message' => $msg], 422);
                            }

                            return redirect()->back()->withInput()->with('error', $msg);
                        }
                        if (! empty($pixChaveParcelado['conta_bancaria_id'])) {
                            $contaBancariaId = (int) $pixChaveParcelado['conta_bancaria_id'];
                        }
                        $taxaPix = round(
                            ($valorPagamento * ((float) ($pixChaveParcelado['taxa_percentual'] ?? 0) / 100))
                            + (float) ($pixChaveParcelado['taxa_fixa'] ?? 0),
                            2
                        );
                        if ($taxaPix <= 0) {
                            $taxaPix = $formaPagamento->calcularTaxa($valorPagamento);
                        }
                        $valorParcelaPix = $parcelas > 1 ? round($valorPagamento / $parcelas, 2) : $valorPagamento;
                        $restoValorPix = round($valorPagamento - ($valorParcelaPix * $parcelas), 2);
                        $taxaParcelaPix = $parcelas > 1 ? round($taxaPix / $parcelas, 2) : $taxaPix;
                        $restoTaxaPix = round($taxaPix - ($taxaParcelaPix * $parcelas), 2);
                        $hojeEnc = \Carbon\Carbon::today();
                        for ($i = 1; $i <= $parcelas; $i++) {
                            try {
                                $dataVencPix = \Carbon\Carbon::parse($vencimentos[$i - 1])->startOfDay();
                            } catch (\Throwable $e) {
                                DB::rollBack();
                                $msg = 'Data de vencimento inválida na parcela ' . $i . ' do PIX.';
                                if ($respondJson) {
                                    return response()->json(['message' => $msg], 422);
                                }

                                return redirect()->back()->withInput()->with('error', $msg);
                            }
                            $vp = $i === 1 ? $valorParcelaPix + $restoValorPix : $valorParcelaPix;
                            $tp = $i === 1 ? $taxaParcelaPix + $restoTaxaPix : $taxaParcelaPix;
                            $vl = $vp - $tp;
                            $recebidoHoje = $dataVencPix->lte($hojeEnc);
                            if ($recebidoHoje) {
                                $conciliacaoPix = ConciliacaoService::calcularSemAdquirente($formaPagamento, now()->toDateString());
                                $statusConcilObsPix = $conciliacaoPix['obs_liquidacao']
                                    ? ' | ' . $conciliacaoPix['obs_liquidacao']
                                    : ' | Status: pago (baixa na hora)';
                                $obsPixParcPago = sprintf(
                                    'OS: %s | Forma: %s | PIX parcela %s/%s | Valor: R$ %s | Taxa: R$ %s | Líquido: R$ %s | Vencimento: %s%s',
                                    $ordemServico->codigo_interno,
                                    $formaPagamento->nome,
                                    $i,
                                    $parcelas,
                                    number_format($vp, 2, ',', '.'),
                                    number_format($tp, 2, ',', '.'),
                                    number_format($vl, 2, ',', '.'),
                                    $dataVencPix->format('d/m/Y'),
                                    $statusConcilObsPix
                                );
                                $contaReceber = ContaReceber::create([
                                    'ordem_servico_id' => $ordemServico->id,
                                    'cliente_id' => $ordemServico->cliente_id,
                                    'descricao' => $ordemServico->codigo_interno . " - Parcela {$i}/{$parcelas}",
                                    'valor' => $vp,
                                    'valor_original' => $vp,
                                    'data_vencimento' => $dataVencPix,
                                    'numero_parcela' => $i,
                                    'total_parcelas' => $parcelas,
                                    'forma_pagamento_id' => $formaPagamento->id,
                                    'adquirente_id' => $adquirenteIdEfetivo > 0 ? $adquirenteIdEfetivo : null,
                                    'conta_bancaria_id' => $contaBancariaId,
                                    'plano_conta_id' => $planoContaReceitaServico,
                                    'centro_custo_id' => $centroCustoOsEncerramento,
                                    'status' => 'pago',
                                    'desconto' => 0,
                                    'observacoes' => $obsPixParcPago,
                                    'metadata' => ['pix_chave_id' => $pixChaveParcelado['id'] ?? null],
                                    'created_by' => auth()->id(),
                                ]);
                                $contasReceberCriadas[] = $contaReceber;
                                $obsEntradaPix = $tp > 0
                                    ? sprintf(
                                        'Recebimento bruto R$ %s | Líquido na conta R$ %s | Taxa R$ %s | OS %s',
                                        number_format($vp, 2, ',', '.'),
                                        number_format($vl, 2, ',', '.'),
                                        number_format($tp, 2, ',', '.'),
                                        $ordemServico->codigo_interno
                                    )
                                    : '';
                                $movimentacao = MovimentacaoFinanceira::create([
                                    'conta_bancaria_id' => $contaBancariaId,
                                    'tipo' => 'entrada',
                                    'origem' => 'conta_receber',
                                    'conta_receber_id' => $contaReceber->id,
                                    'plano_conta_id' => $planoContaReceitaServico,
                                    'centro_custo_id' => $centroCustoOsEncerramento,
                                    'valor' => $vp,
                                    'data_movimentacao' => now(),
                                    'descricao' => "Recebimento: {$contaReceber->descricao}",
                                    'observacoes' => $obsEntradaPix,
                                    'conciliado' => $conciliacaoPix['conciliado'],
                                    'data_conciliacao' => $conciliacaoPix['data_conciliacao'],
                                    'conciliado_por' => $conciliacaoPix['conciliado'] ? auth()->id() : null,
                                    'created_by' => auth()->id(),
                                ]);
                                $taxaMovIdEncPixParc = null;
                                if ($tp > 0) {
                                    $movTaxaEncPixParc = MovimentacaoFinanceira::create([
                                        'conta_bancaria_id' => $contaBancariaId,
                                        'tipo' => 'saida',
                                        'origem' => 'outro',
                                        'conta_receber_id' => $contaReceber->id,
                                        'plano_conta_id' => $planoContaReceitaServico,
                                        'centro_custo_id' => $centroCustoOsEncerramento,
                                        'valor' => $tp,
                                        'data_movimentacao' => now(),
                                        'descricao' => "Taxa: {$contaReceber->descricao}",
                                        'conciliado' => $conciliacaoPix['conciliado'],
                                        'data_conciliacao' => $conciliacaoPix['data_conciliacao'],
                                        'conciliado_por' => $conciliacaoPix['conciliado'] ? auth()->id() : null,
                                        'created_by' => auth()->id(),
                                    ]);
                                    $taxaMovIdEncPixParc = $movTaxaEncPixParc->id;
                                }
                                BaixaTitulo::create([
                                    'tipo_titulo' => 'conta_receber',
                                    'titulo_id' => $contaReceber->id,
                                    'movimentacao_id' => $movimentacao->id,
                                    'taxa_movimentacao_id' => $taxaMovIdEncPixParc,
                                    'valor_baixa' => $vp,
                                    'data_baixa' => now(),
                                    'juros' => 0,
                                    'multa' => 0,
                                    'desconto' => 0,
                                    'observacoes' => 'Baixa automática no encerramento da OS (PIX parcelado — vencimento até hoje)',
                                    'created_by' => auth()->id(),
                                ]);
                                $contaReceber->valor_recebido = $vp;
                                $contaReceber->data_recebimento = now();
                                $contaReceber->save();
                                $foiPago = true;
                            } else {
                                $obsPixFuturo = sprintf(
                                    'OS: %s | Forma: %s | PIX parcela %s/%s | Valor: R$ %s | Vencimento: %s | Status: aberto (confirmar recebimento no financeiro)',
                                    $ordemServico->codigo_interno,
                                    $formaPagamento->nome,
                                    $i,
                                    $parcelas,
                                    number_format($vp, 2, ',', '.'),
                                    $dataVencPix->format('d/m/Y')
                                );
                                $contaReceber = ContaReceber::create([
                                    'ordem_servico_id' => $ordemServico->id,
                                    'cliente_id' => $ordemServico->cliente_id,
                                    'descricao' => $ordemServico->codigo_interno . " - Parcela {$i}/{$parcelas}",
                                    'valor' => $vp,
                                    'valor_original' => $vp,
                                    'data_vencimento' => $dataVencPix,
                                    'numero_parcela' => $i,
                                    'total_parcelas' => $parcelas,
                                    'forma_pagamento_id' => $formaPagamento->id,
                                    'conta_bancaria_id' => $contaBancariaId,
                                    'plano_conta_id' => $planoContaReceitaServico,
                                    'centro_custo_id' => $centroCustoOsEncerramento,
                                    'status' => 'aberto',
                                    'desconto' => 0,
                                    'observacoes' => $obsPixFuturo,
                                    'metadata' => ['pix_chave_id' => $pixChaveParcelado['id'] ?? null],
                                    'created_by' => auth()->id(),
                                ]);
                                $contasReceberCriadas[] = $contaReceber;
                            }
                        }
                        continue;
                    }

                    // Determinar conciliação automática com base na forma de pagamento
                    // PIX/dinheiro/transferência → imediato; outros meios → conforme dias_recebimento
                    // (Cartão crédito/débito com adquirente são tratados nos blocos acima com agenda precisa)
                    $conciliacaoOS = ConciliacaoService::calcularSemAdquirente($formaPagamento, now()->toDateString());

                    $pixChaveUnica = $formaPagamento->encontrarPixChavePorId($pagamento['pix_chave_id'] ?? null);
                    if ($tipoForma === 'pix' && $pixChaveUnica && ! empty($pixChaveUnica['conta_bancaria_id'])) {
                        $contaBancariaId = (int) $pixChaveUnica['conta_bancaria_id'];
                    }
                    if ($tipoForma === 'pix' && $pixChaveUnica) {
                        $taxa = round(
                            ($valorPagamento * ((float) ($pixChaveUnica['taxa_percentual'] ?? 0) / 100))
                            + (float) ($pixChaveUnica['taxa_fixa'] ?? 0),
                            2
                        );
                        if ($taxa <= 0) {
                            $taxa = $formaPagamento->calcularTaxa($valorPagamento);
                        }
                    } else {
                        $taxa = $formaPagamento->calcularTaxa($valorPagamento);
                    }
                    $valorLiquido = $valorPagamento - $taxa;
                    $valorParcela = $parcelas > 1 ? round($valorPagamento / $parcelas, 2) : $valorPagamento;
                    $restoValor = round($valorPagamento - ($valorParcela * $parcelas), 2);
                    $taxaParcela = $parcelas > 1 ? round($taxa / $parcelas, 2) : $taxa;
                    $restoTaxa = round($taxa - ($taxaParcela * $parcelas), 2);
                    for ($i = 1; $i <= $parcelas; $i++) {
                        $vp = $i === 1 ? $valorParcela + $restoValor : $valorParcela;
                        $tp = $i === 1 ? $taxaParcela + $restoTaxa : $taxaParcela;
                        $vl = $vp - $tp;
                        $statusConcilObs = $conciliacaoOS['obs_liquidacao']
                            ? ' | ' . $conciliacaoOS['obs_liquidacao']
                            : ' | Status: pago (baixa na hora)';
                        $obsDinheiroPix = sprintf(
                            'OS: %s | Forma: %s | Valor: R$ %s | Parcela: %s/%s | Taxa: R$ %s | Valor líquido: R$ %s | Recebimento: %s%s',
                            $ordemServico->codigo_interno,
                            $formaPagamento->nome,
                            number_format($vp, 2, ',', '.'),
                            $i,
                            $parcelas,
                            number_format($tp, 2, ',', '.'),
                            number_format($vl, 2, ',', '.'),
                            now()->format('d/m/Y H:i'),
                            $statusConcilObs
                        );
                        $contaReceber = ContaReceber::create([
                            'ordem_servico_id' => $ordemServico->id,
                            'cliente_id' => $ordemServico->cliente_id,
                            'descricao' => $ordemServico->codigo_interno . ($parcelas > 1 ? " - Parcela {$i}/{$parcelas}" : ''),
                            'valor' => $vp,
                            'valor_original' => $vp,
                            'data_vencimento' => $conciliacaoOS['data_prevista'] ? \Carbon\Carbon::parse($conciliacaoOS['data_prevista']) : now(),
                            'numero_parcela' => $parcelas > 1 ? $i : 1,
                            'total_parcelas' => $parcelas > 1 ? $parcelas : 1,
                            'forma_pagamento_id' => $formaPagamento->id,
                            'adquirente_id' => $adquirenteIdEfetivo > 0 ? $adquirenteIdEfetivo : null,
                            'conta_bancaria_id' => $contaBancariaId,
                            'plano_conta_id' => $planoContaReceitaServico,
                            'centro_custo_id' => $centroCustoOsEncerramento,
                            'status' => 'pago',
                            'desconto' => 0,
                            'observacoes' => $obsDinheiroPix,
                            'created_by' => auth()->id(),
                        ]);
                        $contasReceberCriadas[] = $contaReceber;
                        // Entrada no valor BRUTO + saída da taxa (mesmo padrão do PDV / FinanceiroVendaPagamentosService)
                        $obsEntradaOs = $tp > 0
                            ? sprintf(
                                'Recebimento bruto R$ %s | Líquido na conta R$ %s | Taxa R$ %s | OS %s',
                                number_format($vp, 2, ',', '.'),
                                number_format($vl, 2, ',', '.'),
                                number_format($tp, 2, ',', '.'),
                                $ordemServico->codigo_interno
                            )
                            : '';
                        $movimentacao = MovimentacaoFinanceira::create([
                            'conta_bancaria_id' => $contaBancariaId,
                            'tipo' => 'entrada',
                            'origem' => 'conta_receber',
                            'conta_receber_id' => $contaReceber->id,
                            'plano_conta_id' => $planoContaReceitaServico,
                            'centro_custo_id' => $centroCustoOsEncerramento,
                            'valor' => $vp,
                            'data_movimentacao' => now(),
                            'descricao' => "Recebimento: {$contaReceber->descricao}",
                            'observacoes' => $obsEntradaOs,
                            'conciliado' => $conciliacaoOS['conciliado'],
                            'data_conciliacao' => $conciliacaoOS['data_conciliacao'],
                            'conciliado_por' => $conciliacaoOS['conciliado'] ? auth()->id() : null,
                            'created_by' => auth()->id(),
                        ]);
                        $taxaMovIdEncGen = null;
                        if ($tp > 0) {
                            $movTaxaEncGen = MovimentacaoFinanceira::create([
                                'conta_bancaria_id' => $contaBancariaId,
                                'tipo' => 'saida',
                                'origem' => 'outro',
                                'conta_receber_id' => $contaReceber->id,
                                'plano_conta_id' => $planoContaReceitaServico,
                                'centro_custo_id' => $centroCustoOsEncerramento,
                                'valor' => $tp,
                                'data_movimentacao' => now(),
                                'descricao' => "Taxa: {$contaReceber->descricao}",
                                'conciliado' => $conciliacaoOS['conciliado'],
                                'data_conciliacao' => $conciliacaoOS['data_conciliacao'],
                                'conciliado_por' => $conciliacaoOS['conciliado'] ? auth()->id() : null,
                                'created_by' => auth()->id(),
                            ]);
                            $taxaMovIdEncGen = $movTaxaEncGen->id;
                        }
                        BaixaTitulo::create([
                            'tipo_titulo' => 'conta_receber',
                            'titulo_id' => $contaReceber->id,
                            'movimentacao_id' => $movimentacao->id,
                            'taxa_movimentacao_id' => $taxaMovIdEncGen,
                            'valor_baixa' => $vp,
                            'data_baixa' => now(),
                            'juros' => 0,
                            'multa' => 0,
                            'desconto' => 0,
                            'observacoes' => 'Baixa automática no encerramento da OS',
                            'created_by' => auth()->id(),
                        ]);
                        $contaReceber->valor_recebido = $vp;
                        $contaReceber->data_recebimento = now();
                        $contaReceber->save();
                        $foiPago = true;
                    }
                }
            } else {
                // Faturado/Fiado - criar conta a receber sem baixa
                if ($valorAReceber > 0) {
                    $dataVencFaturado = now()->addDays(30);
                    $obsFaturado = sprintf(
                        'OS: %s | Tipo: Faturado/Fiado | Valor: R$ %s | Desconto aplicado: R$ %s | Vencimento: %s | Status: aberto (a receber)',
                        $ordemServico->codigo_interno,
                        number_format($valorAReceber, 2, ',', '.'),
                        number_format($desconto, 2, ',', '.'),
                        $dataVencFaturado->format('d/m/Y')
                    );
                    $contaReceber = ContaReceber::create([
                        'ordem_servico_id' => $ordemServico->id,
                        'cliente_id' => $ordemServico->cliente_id,
                        'descricao' => $ordemServico->codigo_interno,
                        'valor' => $valorAReceber,
                        'valor_original' => $valorAReceber,
                        'data_vencimento' => now()->addDays(30),
                        'numero_parcela' => 1,
                        'total_parcelas' => 1,
                        'status' => 'aberto',
                        'desconto' => $desconto,
                        'plano_conta_id' => $planoContaReceitaServico ?? null,
                        'centro_custo_id' => $centroCustoOsEncerramento,
                        'observacoes' => $obsFaturado,
                        'created_by' => auth()->id(),
                    ]);
                    $contasReceberCriadas[] = $contaReceber;
                }
            }

            // Baixar estoque
            $this->baixarEstoque($ordemServico);

            // Persistir desconto aplicado na finalização (para impressão e totais)
            $ordemServico->desconto_global = (float) ($ordemServico->desconto_global ?? 0) + $desconto;
            $ordemServico->total_descontos = (float) ($ordemServico->total_descontos ?? 0) + $desconto;
            $ordemServico->total_geral = $valorAReceber;

            // Atualizar status da OS
            $ordemServico->status = 'Encerrada';
            $ordemServico->real_conclusao = now();
            $ordemServico->financeiro_gerado = true;
            $ordemServico->updated_by = auth()->id();
            $ordemServico->save();

            // Adicionar tags
            $tagPago = Tag::firstOrCreate(
                ['nome' => 'Pago', 'tipo' => 'padrao'],
                ['ativo' => true, 'cor_fundo' => '#10b981', 'cor_fonte' => '#ffffff', 'created_by' => auth()->id()]
            );
            $tagPendente = Tag::firstOrCreate(
                ['nome' => 'Pagamento Pendente', 'tipo' => 'padrao'],
                ['ativo' => true, 'cor_fundo' => '#f59e0b', 'cor_fonte' => '#ffffff', 'created_by' => auth()->id()]
            );
            
            // Remover tags antigas de pagamento
            $ordemServico->tags()->detach([$tagPago->id, $tagPendente->id]);
            
            // Tag: "Pago" só se não houver título em aberto gerado neste encerramento (ex.: PIX parcelado com parcelas futuras)
            $algumTituloAbertoEncerramento = collect($contasReceberCriadas)->contains(
                fn ($cr) => ($cr->status ?? '') === 'aberto'
            );
            if ($ehGarantiaOuContrato) {
                $ordemServico->tags()->attach($tagPago->id);
            } elseif ($foiPago && ! $algumTituloAbertoEncerramento) {
                $ordemServico->tags()->attach($tagPago->id);
            } else {
                $ordemServico->tags()->attach($tagPendente->id);
            }

            // Resumo dos lançamentos (mesmas informações das observações do Contas a Receber) para o histórico
            $resumoLancamentos = [];
            foreach ($contasReceberCriadas as $cr) {
                $resumoLancamentos[] = [
                    'descricao' => $cr->descricao,
                    'valor' => (float) $cr->valor,
                    'valor_formatado' => 'R$ ' . number_format((float) $cr->valor, 2, ',', '.'),
                    'status' => $cr->status,
                    'data_vencimento' => $cr->data_vencimento ? $cr->data_vencimento->format('d/m/Y') : null,
                    'observacoes' => $cr->observacoes,
                    'forma_pagamento' => $cr->formaPagamento ? $cr->formaPagamento->nome : null,
                    'adquirente' => $cr->adquirente_id ? (optional($cr->adquirente)->nome ?? 'N/A') : null,
                    'bandeira' => $cr->bandeira ?? null,
                ];
            }

            // Notificações (e-mail e WhatsApp) na mudança de status para Encerrada
            if (NotificacaoOsService::deveEnviarEmailNaMudancaStatus()) {
                $emails = NotificacaoOsService::getEmailsCliente($ordemServico);
                $assunto = 'OS ' . format_os_display($ordemServico) . ' - Status alterado para Encerrada';
                foreach ($emails as $email) {
                    NotificacaoOsEnvioService::registrarEDispararEmail($ordemServico, $email, $assunto, null, true, 'Encerrada');
                }
            }
            if (NotificacaoOsService::deveEnviarWhatsAppNaMudancaStatus()) {
                NotificacaoOsEnvioService::registrarEDispararWhatsApp($ordemServico, 'Encerrada');
            }

            // Registrar log com dados completos (igual às observações do contas a receber + outras informações)
            $this->registrarLog($ordemServico, 'encerrada', 'OS encerrada', [
                'valor_total' => $valorTotal,
                'valor_total_formatado' => 'R$ ' . number_format($valorTotal, 2, ',', '.'),
                'desconto' => $desconto,
                'desconto_formatado' => 'R$ ' . number_format($desconto, 2, ',', '.'),
                'desconto_tipo' => $request->desconto_tipo ?? 'valor',
                'desconto_valor_informado' => $request->desconto_valor ?? 0,
                'valor_a_receber' => $valorAReceber,
                'valor_a_receber_formatado' => 'R$ ' . number_format($valorAReceber, 2, ',', '.'),
                'tipo_pagamento' => $request->tipo_pagamento,
                'eh_garantia_ou_contrato' => $ehGarantiaOuContrato,
                'contas_criadas' => count($contasReceberCriadas),
                'foi_pago' => $foiPago,
                'imprimir_comprovante' => $request->has('imprimir_comprovante') && $request->imprimir_comprovante,
                'real_conclusao' => now()->format('d/m/Y H:i'),
                'lancamentos' => $resumoLancamentos,
            ]);

            DB::commit();

            // Atualizar a OS para garantir que está salva
            $ordemServico->refresh();

            Log::info('OS encerrada com sucesso', [
                'os_id' => $ordemServico->id,
                'status' => $ordemServico->status,
                'tipo_pagamento' => $request->tipo_pagamento,
                'imprimir_comprovante' => $request->has('imprimir_comprovante') && $request->imprimir_comprovante,
            ]);

            // Resposta para requisição AJAX (modal): sempre volta à edição; comprovante abre em nova aba no front
            if ($respondJson) {
                $imprimirComprovante = $request->has('imprimir_comprovante') && $request->imprimir_comprovante;
                $payload = [
                    'message' => 'OS encerrada com sucesso!',
                    'redirect' => route('ordens-servico.edit', $ordemServico),
                ];
                if ($imprimirComprovante) {
                    $payload['comprovante_url'] = route('ordens-servico.comprovante', $ordemServico);
                }
                return response()->json($payload);
            }

            if ($request->has('imprimir_comprovante') && $request->imprimir_comprovante) {
                return redirect()->route('ordens-servico.comprovante', $ordemServico)
                    ->with('success', 'OS encerrada com sucesso!');
            }

            return redirect()->route('ordens-servico.edit', $ordemServico)
                ->with('success', 'OS encerrada com sucesso!');
        } catch (\Illuminate\Validation\ValidationException $e) {
            DB::rollBack();
            Log::warning('Validação falhou ao encerrar OS', [
                'os_id' => $ordemServico->id,
                'errors' => $e->errors(),
            ]);
            return redirect()->back()
                ->withErrors($e->errors())
                ->withInput();
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Erro ao encerrar OS: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString(),
                'request' => $request->all(),
                'os_id' => $ordemServico->id,
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ]);
            return redirect()->back()
                ->withInput()
                ->with('error', 'Erro ao encerrar OS: ' . $e->getMessage() . ' (Verifique os logs para mais detalhes)');
        }
    }

    public function comprovante(OrdemServico $ordemServico)
    {
        if (!in_array($ordemServico->status, ['Encerrado', 'Encerrada'], true)) {
            return redirect()->route('ordens-servico.edit', $ordemServico)
                ->with('error', 'Esta OS ainda não foi encerrada.');
        }

        $ordemServico->load([
            'cliente',
            'unidade',
            'contato',
            'endereco',
            'equipamento',
            'produtos.produto.composicoes.componente',
            'produtos.variacao',
            'servicos.servico',
            'servicos.tecnico',
            'termoGarantia',
            'checklists.checklistModel',
        ]);

        $comprovanteHtmlAntesAssinatura = '';
        if (Configuracao::osComprovanteObservacaoAntesAssinaturaAtivo()) {
            $textoCfg = trim((string) (Configuracao::getValue('os_comprovante_observacao_antes_assinatura', '') ?? ''));
            $comprovanteHtmlAntesAssinatura = $textoCfg !== ''
                ? $textoCfg
                : Configuracao::textoComprovanteOsEncerradaPadrao();
        }

        return erp_view('ordens_servico.comprovante', [
            'ordem' => $ordemServico,
            'empresa' => Empresa::first(),
            'comprovanteHtmlAntesAssinatura' => $comprovanteHtmlAntesAssinatura,
        ]);
    }

    public function salvarChecklist(Request $request, OrdemServico $ordemServico)
    {
        $validated = $this->validateWithFilters($request, [
            'checklist_model_id' => 'required|exists:checklist_models,id',
            'respostas' => 'required|array',
            'observacoes' => 'nullable|string',
        ]);

        try {
            DB::beginTransaction();

            $checklistNome = ChecklistModel::whereKey($validated['checklist_model_id'])->value('nome') ?? 'Checklist';
            $antes = [
                'checklist' => $checklistNome,
                'respostas' => '(nenhuma)',
                'observacoes' => null,
            ];
            $depois = $antes;

            // Verificar se já existe um checklist deste modelo vinculado à OS
            $checklistExistente = ChecklistAnswer::where('ordem_servico_id', $ordemServico->id)
                ->where('checklist_model_id', $validated['checklist_model_id'])
                ->first();

            if ($checklistExistente) {
                // Atualizar existente
                $antes['respostas'] = json_encode($checklistExistente->respostas ?? [], JSON_UNESCAPED_UNICODE);
                $antes['observacoes'] = $checklistExistente->observacoes ?? null;

                $checklistExistente->update([
                    'respostas' => $validated['respostas'],
                    'observacoes' => $validated['observacoes'] ?? null,
                    'updated_by' => auth()->id(),
                ]);
                $checklistId = $checklistExistente->id;

                $depois['respostas'] = json_encode($validated['respostas'] ?? [], JSON_UNESCAPED_UNICODE);
                $depois['observacoes'] = $validated['observacoes'] ?? null;
            } else {
                // Criar novo
                $checklistAnswer = ChecklistAnswer::create([
                    'ordem_servico_id' => $ordemServico->id,
                    'checklist_model_id' => $validated['checklist_model_id'],
                    'respostas' => $validated['respostas'],
                    'observacoes' => $validated['observacoes'] ?? null,
                    'created_by' => auth()->id(),
                ]);
                $checklistId = $checklistAnswer->id;

                $depois['respostas'] = json_encode($validated['respostas'] ?? [], JSON_UNESCAPED_UNICODE);
                $depois['observacoes'] = $validated['observacoes'] ?? null;
            }

            DB::commit();

            $this->registrarLog($ordemServico, 'checklists', 'Checklist salva', [
                'antes' => $antes,
                'depois' => $depois,
            ]);

            if ($request->wantsJson() || $request->ajax()) {
                return response()->json([
                    'message' => 'Checklist salvo com sucesso!',
                    'success' => true,
                    'checklist_id' => $checklistId,
                ]);
            }

            return redirect()->back()
                ->with('success', 'Checklist salvo com sucesso!');
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Erro ao salvar checklist: ' . $e->getMessage(), [
                'os_id' => $ordemServico->id,
                'checklist_model_id' => $validated['checklist_model_id'] ?? null,
            ]);

            if ($request->wantsJson() || $request->ajax()) {
                return response()->json([
                    'message' => 'Erro ao salvar checklist: ' . $e->getMessage(),
                    'success' => false,
                ], 500);
            }

            return redirect()->back()
                ->with('error', 'Erro ao salvar checklist: ' . $e->getMessage());
        }
    }

    public function removerChecklist(Request $request, OrdemServico $ordemServico, ChecklistAnswer $checklistAnswer)
    {
        try {
            // Verificar se o checklist pertence à OS
            if ($checklistAnswer->ordem_servico_id !== $ordemServico->id) {
                if ($request->wantsJson() || $request->ajax()) {
                    return response()->json([
                        'message' => 'Checklist não pertence a esta OS.',
                        'success' => false,
                    ], 403);
                }
                return redirect()->back()
                    ->with('error', 'Checklist não pertence a esta OS.');
            }

            $checklistNome = ChecklistModel::whereKey($checklistAnswer->checklist_model_id)->value('nome') ?? 'Checklist';
            $antes = [
                'checklist' => $checklistNome,
                'respostas' => json_encode($checklistAnswer->respostas ?? [], JSON_UNESCAPED_UNICODE),
                'observacoes' => $checklistAnswer->observacoes ?? null,
            ];

            $checklistAnswer->delete();

            $this->registrarLog($ordemServico, 'checklists', 'Checklist removido', [
                'antes' => $antes,
                'depois' => [
                    'checklist' => $checklistNome,
                    'respostas' => null,
                    'observacoes' => null,
                ],
            ]);

            if ($request->wantsJson() || $request->ajax()) {
                return response()->json([
                    'message' => 'Checklist removido com sucesso!',
                    'success' => true,
                ]);
            }

            return redirect()->back()
                ->with('success', 'Checklist removido com sucesso!');
        } catch (\Exception $e) {
            Log::error('Erro ao remover checklist: ' . $e->getMessage(), [
                'os_id' => $ordemServico->id,
                'checklist_answer_id' => $checklistAnswer->id,
            ]);

            if ($request->wantsJson() || $request->ajax()) {
                return response()->json([
                    'message' => 'Erro ao remover checklist: ' . $e->getMessage(),
                    'success' => false,
                ], 500);
            }

            return redirect()->back()
                ->with('error', 'Erro ao remover checklist: ' . $e->getMessage());
        }
    }
}

