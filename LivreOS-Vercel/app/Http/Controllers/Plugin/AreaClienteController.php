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

namespace App\Http\Controllers\Plugin;

use App\Http\Controllers\Controller;
use App\Models\OrdemServico;
use App\Models\OrdemServicoAnexo;
use App\Services\OrdemServicoAssinaturaClienteTokenService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rules\Password;

class AreaClienteController extends Controller
{
    protected function getClienteId(): ?int
    {
        return auth()->user()->cliente_id ?? null;
    }

    protected function getClienteIds(): array
    {
        $clienteId = $this->getClienteId();
        if (!$clienteId) {
            return [];
        }
        $cliente = \App\Models\Cliente::find($clienteId);
        if (!$cliente) {
            return [$clienteId];
        }
        $ids = [$clienteId];
        if ($cliente->grupo_economico_id) {
            $unidades = \App\Models\Cliente::where('grupo_economico_id', $cliente->grupo_economico_id)->pluck('id')->toArray();
            $ids = array_unique(array_merge($ids, $unidades));
        }
        $filiais = \App\Models\Cliente::where('matriz_id', $clienteId)->pluck('id')->toArray();
        return array_unique(array_merge($ids, $filiais));
    }

    protected function ensurePortalAccess(): \App\Models\Cliente
    {
        $clienteId = $this->getClienteId();
        if (!$clienteId) {
            abort(403, 'Acesso restrito à área do cliente.');
        }
        $cliente = \App\Models\Cliente::find($clienteId);
        if (!$cliente || !($cliente->portal_suporte ?? false)) {
            abort(403, 'O portal do cliente não está habilitado para sua conta.');
        }
        return $cliente;
    }

    public function index(Request $request)
    {
        $cliente = $this->ensurePortalAccess();
        $ids = $this->getClienteIds();

        $query = OrdemServico::with(['cliente'])
            ->whereIn('cliente_id', $ids);

        if ($request->filled('status')) {
            if ($request->status === 'Encerrada') {
                $query->whereIn('status', ['Encerrado', 'Encerrada']);
            } else {
                $query->where('status', $request->status);
            }
        }
        if ($request->filled('busca')) {
            $busca = $request->busca;
            $query->where(function ($q) use ($busca) {
                $q->where('codigo_interno', 'like', "%{$busca}%")
                    ->orWhere('relato_cliente', 'like', "%{$busca}%")
                    ->orWhere('equipamento_nome', 'like', "%{$busca}%");
            });
        }

        $ordemBy = $request->get('ordem', 'created_at');
        $ordemDir = $request->get('dir', 'desc');
        $query->orderBy($ordemBy === 'total' ? 'total_geral' : ($ordemBy === 'status' ? 'status' : 'created_at'), $ordemDir === 'asc' ? 'asc' : 'desc');

        $ordens = $query->paginate(15)->withQueryString();
        $statusList = \App\Models\StatusOs::ativos()->ordenados()->get();

        $stats = \App\Models\OrdemServico::whereIn('cliente_id', $ids)
            ->selectRaw("status, count(*) as total")
            ->groupBy('status')
            ->pluck('total', 'status')
            ->toArray();

        return view('area-cliente::index', [
            'ordens' => $ordens,
            'cliente' => $cliente,
            'statusList' => $statusList,
            'stats' => $stats,
            'title' => 'Minhas Ordens de Serviço',
        ]);
    }

    public function show(Request $request, OrdemServico $ordemServico)
    {
        $this->ensurePortalAccess();
        $ids = $this->getClienteIds();

        if (!in_array($ordemServico->cliente_id, $ids)) {
            abort(404, 'Ordem de serviço não encontrada.');
        }

        $ordemServico->load(['cliente', 'produtos.produto', 'servicos.servico', 'termoGarantia', 'anexos']);

        return view('area-cliente::show', [
            'ordem' => $ordemServico,
            'title' => format_os_display($ordemServico) . ' — Área do Cliente',
        ]);
    }

    /**
     * Gera token de assinatura do core e redireciona para a página pública de assinatura (aprovação com status Aguardando aprovação).
     */
    public function abrirAssinaturaAprovacao(Request $request, OrdemServico $ordemServico)
    {
        $this->ensurePortalAccess();
        $ids = $this->getClienteIds();

        if (!in_array($ordemServico->cliente_id, $ids)) {
            abort(404, 'Ordem de serviço não encontrada.');
        }

        if (in_array($ordemServico->status, ['Encerrado', 'Encerrada'], true)) {
            return redirect()->back()->with('error', 'Esta OS está encerrada.');
        }

        if ($ordemServico->status !== 'Aguardando aprovação') {
            return redirect()->back()->with('error', 'A assinatura de aprovação só está disponível quando o status é "Aguardando aprovação".');
        }

        $service = app(OrdemServicoAssinaturaClienteTokenService::class);
        $tokenModel = $service->gerarNovoToken($ordemServico);

        if (!session()->has('signature_session_uuid')) {
            session(['signature_session_uuid' => (string) Str::uuid()]);
        }
        $service->registrarLogTokenGerado($ordemServico, $tokenModel, $request, session('signature_session_uuid'));

        return redirect()->to($service->urlPublicaAssinatura($tokenModel->token));
    }

    public function pdf(OrdemServico $ordemServico)
    {
        $this->ensurePortalAccess();
        $ids = $this->getClienteIds();

        if (!in_array($ordemServico->cliente_id, $ids)) {
            abort(404, 'Ordem de serviço não encontrada.');
        }

        $ordemServico->load(['cliente.enderecos', 'unidade.enderecos', 'contato', 'produtos.produto', 'servicos.servico', 'termoGarantia', 'anexos', 'checklists.checklistModel']);

        $empresa = class_exists(\App\Models\Empresa::class) ? \App\Models\Empresa::first() : null;
        $user = auth()->user();

        $html = view('area-cliente::comprovante-pdf', [
            'ordem' => $ordemServico,
            'empresa' => $empresa,
            'geradoPor' => $user->name ?? $user->email ?? 'Cliente',
            'geradoEm' => now()->format('d/m/Y H:i'),
            'documentoUrl' => url()->route('plugin.area-cliente.os.show', $ordemServico),
            'documentoId' => 'AC-' . ($ordemServico->codigo_interno ?? $ordemServico->id) . '-' . now()->format('YmdHis'),
            'ipDispositivo' => request()->ip(),
        ])->render();

        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadHTML($html);
        $pdf->setPaper('A4', 'portrait');
        return $pdf->download('comprovante-' . preg_replace('/[^a-z0-9]/i', '-', $ordemServico->codigo_interno ?? $ordemServico->id) . '.pdf');
    }

    public function exportCsv(Request $request)
    {
        $cliente = $this->ensurePortalAccess();
        $ids = $this->getClienteIds();

        $query = \App\Models\OrdemServico::whereIn('cliente_id', $ids);
        if ($request->filled('status')) {
            if ($request->status === 'Encerrada') {
                $query->whereIn('status', ['Encerrado', 'Encerrada']);
            } else {
                $query->where('status', $request->status);
            }
        }
        if ($request->filled('busca')) {
            $busca = $request->busca;
            $query->where(function ($q) use ($busca) {
                $q->where('codigo_interno', 'like', "%{$busca}%")
                    ->orWhere('relato_cliente', 'like', "%{$busca}%")
                    ->orWhere('equipamento_nome', 'like', "%{$busca}%");
            });
        }

        $ordens = $query->orderByDesc('created_at')->get();
        $filename = 'ordens-servico-' . date('Y-m-d-His') . '.csv';

        $headers = [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ];

        $callback = function () use ($ordens) {
            $file = fopen('php://output', 'w');
            fputcsv($file, ['OS', 'Equipamento', 'Status', 'Abertura', 'Total'], ';');
            foreach ($ordens as $o) {
                fputcsv($file, [
                    $o->codigo_interno ?? $o->numero_sequencial,
                    $o->equipamento_nome ?? '-',
                    format_status_os_display($o->status),
                    optional($o->data_abertura)->format('d/m/Y H:i') ?? '-',
                    number_format($o->total_geral ?? 0, 2, ',', '.'),
                ], ';');
            }
            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    public function downloadAnexo(OrdemServico $ordemServico, OrdemServicoAnexo $anexo)
    {
        $this->ensurePortalAccess();
        $ids = $this->getClienteIds();

        if (!in_array($ordemServico->cliente_id, $ids) || $anexo->ordem_servico_id !== $ordemServico->id) {
            abort(404, 'Anexo não encontrado.');
        }

        $path = $anexo->caminho_arquivo;
        if (!$path || !Storage::disk('public')->exists($path)) {
            abort(404, 'Arquivo não encontrado.');
        }

        return response()->download(Storage::disk('public')->path($path), $anexo->nome_arquivo ?? basename($path), [
            'Content-Type' => $anexo->tipo_mime ?? 'application/octet-stream',
        ]);
    }

    public function perfil()
    {
        $cliente = $this->ensurePortalAccess();
        $user = auth()->user();
        return view('area-cliente::perfil', [
            'cliente' => $cliente,
            'user' => $user,
            'title' => 'Meu Perfil',
        ]);
    }

    public function ajuda()
    {
        $this->ensurePortalAccess();
        return view('area-cliente::ajuda', [
            'title' => 'Ajuda — Área do Cliente',
        ]);
    }

    public function trocarSenha(Request $request)
    {
        $this->ensurePortalAccess();
        $request->validate([
            'senha_atual' => 'required',
            'nova_senha' => ['required', 'confirmed', Password::defaults()],
        ]);

        $user = auth()->user();
        if (!Hash::check($request->senha_atual, $user->password)) {
            return back()->withErrors(['senha_atual' => 'A senha atual está incorreta.']);
        }

        $user->update(['password' => Hash::make($request->nova_senha)]);

        return redirect()->route('plugin.area-cliente.perfil')
            ->with('success', 'Senha alterada com sucesso!');
    }
}
