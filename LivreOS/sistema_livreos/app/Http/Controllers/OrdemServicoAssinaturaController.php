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

use App\Models\Empresa;
use App\Models\OrdemServico;
use App\Models\OrdemServicoAssinatura;
use App\Models\OrdemServicoAssinaturaEvent;
use App\Models\OrdemServicoAssinaturaToken;
use App\Models\OrdemServicoDocumento;
use App\Models\OrdemServicoLog;
use App\Models\UserSignature;
use App\Services\OrdemServicoAssinaturaClienteTokenService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Crypt;

class OrdemServicoAssinaturaController extends Controller
{
    private string $checkboxTexto = 'Declaro que li e concordo com os termos e condições apresentados para esta ordem de serviço e aprovo a execução deste serviço.';
    private string $termsVersion = '1.0';
    private string $privacyVersion = '1.0';
    private string $legalBasis = 'execucao_contrato';

    private function osEncerrada(OrdemServico $ordem): bool
    {
        return in_array($ordem->status, ['Encerrado', 'Encerrada'], true);
    }

    /**
     * Gestão interna (rotas operational): admin ou permissão ordem_servico.editar_encerrada.
     * Fluxo público do cliente (link com token) continua bloqueado quando encerrada.
     */
    private function osEncerradaBloqueiaGestao(OrdemServico $ordem): bool
    {
        if (! $this->osEncerrada($ordem)) {
            return false;
        }
        $u = auth()->user();

        return ! ($u && $u->hasPermission('ordem_servico.editar_encerrada'));
    }

    private function registrarOsLog(OrdemServico $ordemServico, string $tipo, string $descricao, array $dados): void
    {
        OrdemServicoLog::create([
            'ordem_servico_id' => $ordemServico->id,
            'user_id' => auth()->check() ? auth()->id() : null,
            'tipo' => $tipo,
            'descricao' => $descricao,
            'dados' => $dados ?: null,
            'ip' => request()->ip(),
        ]);
    }

    private function rejeitarSeOsEncerrada(OrdemServico $ordem)
    {
        if (! $this->osEncerradaBloqueiaGestao($ordem)) {
            return null;
        }
        if (request()->wantsJson() || request()->ajax()) {
            return response()->json(['message' => 'Não é possível alterar assinaturas em OS encerrada.'], 403);
        }
        return redirect()->back()->with('error', 'Não é possível alterar assinaturas em OS encerrada.');
    }

    public function showTecnico(OrdemServico $ordemServico)
    {
        if ($this->osEncerradaBloqueiaGestao($ordemServico)) {
            return erp_view('assinaturas.os_invalid', ['message' => 'Esta OS foi encerrada. Assinaturas não podem mais ser alteradas.']);
        }
        $ordemServico->load([
            'cliente', 
            'endereco', 
            'equipamento',
            'assinaturaTecnico',
            'produtos.produto.composicoes.componente',
            'produtos.variacao',
            'checklists.checklistModel',
        ]);
        $sessionUuid = $this->getSessionUuid();
        $this->logEvent($ordemServico, 'tecnico', 'link_aberto', [], request(), $sessionUuid);

        return erp_view('assinaturas.os', [
            'title' => 'Assinatura do Técnico',
            'ordem' => $ordemServico,
            'empresa' => Empresa::first(),
            'tipo' => 'tecnico',
            'checkboxTexto' => $this->checkboxTexto,
            'termsVersion' => $this->termsVersion,
            'privacyVersion' => $this->privacyVersion,
            'legalBasis' => $this->legalBasis,
            'sessionUuid' => $sessionUuid,
        ]);
    }

    public function showCliente(string $token)
    {
        $tokenModel = OrdemServicoAssinaturaToken::where('token', $token)->firstOrFail();
        $ordemServico = $tokenModel->ordemServico;
        $ordemServico->load([
            'cliente', 
            'endereco', 
            'equipamento',
            'produtos.produto.composicoes.componente',
            'produtos.variacao',
            'checklists.checklistModel',
        ]);
        $sessionUuid = $this->getSessionUuid();

        if ($tokenModel->status === 'revoked') {
            return erp_view('assinaturas.os_invalid', ['message' => 'Link revogado.']);
        }

        if ($tokenModel->expires_at && $tokenModel->expires_at->isPast()) {
            $tokenModel->update(['status' => 'expired']);
            return erp_view('assinaturas.os_invalid', ['message' => 'Link expirado.']);
        }

        if ($this->osEncerrada($ordemServico)) {
            return erp_view('assinaturas.os_invalid', ['message' => 'Esta OS foi encerrada. Assinaturas não podem mais ser alteradas.']);
        }

        if ($tokenModel->status === 'sent') {
            $tokenModel->update([
                'status' => 'validated',
                'validated_at' => now(),
            ]);
        }

        $this->logEvent($ordemServico, 'cliente', 'link_aberto', [
            'token' => $tokenModel->token,
        ], request(), $sessionUuid);

        $this->logEvent($ordemServico, 'cliente', 'token_validado', [
            'token' => $tokenModel->token,
        ], request(), $sessionUuid);

        return erp_view('assinaturas.os', [
            'title' => 'Assinatura do Cliente',
            'ordem' => $ordemServico,
            'empresa' => Empresa::first(),
            'tipo' => 'cliente',
            'checkboxTexto' => $this->checkboxTexto,
            'termsVersion' => $this->termsVersion,
            'privacyVersion' => $this->privacyVersion,
            'legalBasis' => $this->legalBasis,
            'sessionUuid' => $sessionUuid,
            'token' => $tokenModel->token,
            'assinaturaExistente' => $ordemServico->assinaturaCliente,
            'assinaturaTecnico' => $ordemServico->assinaturaTecnico,
        ]);
    }

    public function storeTecnico(Request $request, OrdemServico $ordemServico)
    {
        if ($rejeitar = $this->rejeitarSeOsEncerrada($ordemServico)) {
            return $rejeitar;
        }
        $data = $this->validarAssinatura($request);
        $user = $request->user();
        $sessionUuid = $this->getSessionUuid();

        $ordemServico->loadMissing(['assinaturaTecnico']);
        $assinaturaAntesPath = $ordemServico->assinaturaTecnico?->signature_path ?? null;

        $assinaturaPath = $this->salvarAssinatura($data['signature_data'], $ordemServico->id, 'tecnico');
        $assinatura = $this->salvarAssinaturaOs($ordemServico, 'tecnico', $assinaturaPath, $data, $sessionUuid, $user?->id);

        $this->registrarOsLog($ordemServico, 'assinatura', 'Assinatura do técnico concluída', [
            'antes' => ['assinatura' => $assinaturaAntesPath],
            'depois' => ['assinatura' => $assinatura->signature_path ?? null],
        ]);

        if ($request->boolean('salvar_assinatura')) {
            $this->salvarAssinaturaUsuario($user?->id, $assinaturaPath, $data);
        }

        $this->logEvent($ordemServico, 'tecnico', 'assinatura_tecnico_concluida', [
            'assinatura_id' => $assinatura->id,
        ], $request, $sessionUuid);

        // Verificar se ambas assinaturas estão completas e gerar PDF
        $this->verificarEGerarPDF($ordemServico);

        return erp_view('assinaturas.os_sucesso', ['title' => 'Assinatura concluída']);
    }

    public function storeCliente(Request $request, string $token)
    {
        $tokenModel = OrdemServicoAssinaturaToken::where('token', $token)->firstOrFail();
        $ordemServico = $tokenModel->ordemServico;
        $sessionUuid = $this->getSessionUuid();

        if ($this->osEncerrada($ordemServico)) {
            return erp_view('assinaturas.os_invalid', ['message' => 'Esta OS foi encerrada. Assinaturas não podem mais ser alteradas.']);
        }

        if ($tokenModel->status === 'revoked') {
            return erp_view('assinaturas.os_invalid', ['message' => 'Link revogado.']);
        }
        if ($tokenModel->expires_at && $tokenModel->expires_at->isPast()) {
            $tokenModel->update(['status' => 'expired']);
            return erp_view('assinaturas.os_invalid', ['message' => 'Link expirado.']);
        }

        $data = $this->validarAssinatura($request);
        $ordemServico->loadMissing(['assinaturaCliente']);
        $assinaturaAntesPath = $ordemServico->assinaturaCliente?->signature_path ?? null;

        $assinaturaPath = $this->salvarAssinatura($data['signature_data'], $ordemServico->id, 'cliente');
        $assinatura = $this->salvarAssinaturaOs($ordemServico, 'cliente', $assinaturaPath, $data, $sessionUuid, null);

        $this->registrarOsLog($ordemServico, 'assinatura', 'Assinatura do cliente concluída', [
            'antes' => ['assinatura' => $assinaturaAntesPath],
            'depois' => ['assinatura' => $assinatura->signature_path ?? null],
        ]);

        $tokenModel->update([
            'status' => 'used',
            'used_at' => now(),
        ]);

        $statusAnterior = $ordemServico->status;
        $aplicouAprovacaoOrcamento = ($statusAnterior === 'Aguardando aprovação');

        if ($aplicouAprovacaoOrcamento) {
            $aprovAntes = [
                'status' => $statusAnterior,
                'aprovado_cliente' => (bool) ($ordemServico->aprovado_cliente ?? false),
                'aprovado_em' => $ordemServico->aprovado_em ? $ordemServico->aprovado_em->format('d/m/Y H:i') : null,
                'aprovado_nome' => $ordemServico->aprovado_nome ?? null,
                'aprovado_canal' => $ordemServico->aprovado_canal ?? null,
            ];

            $ordemServico->status = 'Aprovado pelo cliente';
            $ordemServico->aprovado_cliente = true;
            $ordemServico->aprovado_em = now();
            $ordemServico->aprovado_nome = 'Cliente (via assinatura digital)';
            $ordemServico->aprovado_canal = 'Assinatura digital online';
            $ordemServico->save();

            $aprovDepois = [
                'status' => $ordemServico->status,
                'aprovado_cliente' => (bool) ($ordemServico->aprovado_cliente ?? false),
                'aprovado_em' => $ordemServico->aprovado_em ? $ordemServico->aprovado_em->format('d/m/Y H:i') : null,
                'aprovado_nome' => $ordemServico->aprovado_nome ?? null,
                'aprovado_canal' => $ordemServico->aprovado_canal ?? null,
            ];

            $this->registrarOsLog($ordemServico, 'aprovacao', 'Aprovação concluída (assinatura do cliente)', [
                'antes' => $aprovAntes,
                'depois' => $aprovDepois,
            ]);

            if (function_exists('do_action')) {
                do_action('ordem_servico.aprovada_cliente_assinatura', $ordemServico, $statusAnterior);
            }
        }

        $ordemServico->refresh();

        $this->logEvent($ordemServico, 'cliente', 'assinatura_cliente_concluida', [
            'assinatura_id' => $assinatura->id,
            'token' => $tokenModel->token,
            'status_anterior' => $statusAnterior,
            'status_novo' => $aplicouAprovacaoOrcamento ? 'Aprovado pelo cliente' : $ordemServico->status,
            'aprovacao_orcamento' => $aplicouAprovacaoOrcamento,
        ], $request, $sessionUuid);

        // Verificar se ambas assinaturas estão completas e gerar PDF
        $this->verificarEGerarPDF($ordemServico);

        return erp_view('assinaturas.os_sucesso', ['title' => 'Assinatura concluída']);
    }

    public function aplicarAssinaturaUsuario(OrdemServico $ordemServico, Request $request)
    {
        if ($rejeitar = $this->rejeitarSeOsEncerrada($ordemServico)) {
            return $rejeitar;
        }
        $user = $request->user();
        $assinaturaUsuario = $user?->assinatura;
        if (!$assinaturaUsuario) {
            if (request()->wantsJson() || request()->ajax()) {
                return response()->json(['message' => 'Nenhuma assinatura salva para este usuário.'], 404);
            }
            return redirect()->back()->with('error', 'Nenhuma assinatura salva para este usuário.');
        }

        try {
            if (empty($assinaturaUsuario->signature_path)) {
                throw new \Exception('Assinatura salva não possui arquivo válido.');
            }

            // Verificar se o arquivo da assinatura salva existe
            $caminhoAssinatura = trim($assinaturaUsuario->signature_path);
            
            if (empty($caminhoAssinatura)) {
                throw new \Exception('Caminho da assinatura salva está vazio.');
            }

            // Log para debug
            Log::info('Tentando usar assinatura salva', [
                'user_id' => $user->id,
                'signature_path' => $caminhoAssinatura,
                'path_length' => strlen($caminhoAssinatura),
            ]);

            $arquivoExiste = Storage::disk('public')->exists($caminhoAssinatura);
            
            if (!$arquivoExiste) {
                // Se o arquivo não existe, verificar se é um caminho antigo de OS
                if (strpos($caminhoAssinatura, 'assinaturas/usuarios/') !== 0) {
                    // É um caminho antigo (provavelmente de OS que foi deletado)
                    Log::warning('Assinatura salva aponta para caminho antigo que não existe mais', [
                        'user_id' => $user->id,
                        'old_path' => $caminhoAssinatura,
                    ]);
                    
                    // Limpar o caminho inválido do banco para forçar recriação
                    $assinaturaUsuario->signature_path = null;
                    $assinaturaUsuario->save();
                    
                    throw new \Exception('A assinatura salva está apontando para um arquivo que foi deletado. Por favor, crie uma nova assinatura e salve-a novamente usando a opção "Salvar assinatura para reutilização".');
                } else {
                    throw new \Exception('Arquivo de assinatura salva não encontrado: ' . $caminhoAssinatura . '. Por favor, crie uma nova assinatura.');
                }
            }

            $sessionUuid = $this->getSessionUuid();
            $ordemServico->loadMissing(['assinaturaTecnico']);
            $assinaturaAntesPath = $ordemServico->assinaturaTecnico?->signature_path ?? null;
            $novaPath = $this->copiarAssinatura($caminhoAssinatura, $ordemServico->id, 'tecnico');

            $assinatura = $this->salvarAssinaturaOs($ordemServico, 'tecnico', $novaPath, [
                'signature_points' => $assinaturaUsuario->signature_points ? json_encode($assinaturaUsuario->signature_points) : null,
                'checkbox_checked' => true,
                'checkbox_text' => $this->checkboxTexto,
                'terms_version' => $this->termsVersion,
                'privacy_version' => $this->privacyVersion,
                'legal_basis' => $this->legalBasis,
                'geo' => null,
                'cpf_confirmacao' => null,
                'celular_confirmacao' => null,
                'extra_metadata' => null,
            ], $sessionUuid, $user?->id);

            $this->registrarOsLog($ordemServico, 'assinatura', 'Assinatura do técnico aplicada à OS', [
                'antes' => ['assinatura' => $assinaturaAntesPath],
                'depois' => ['assinatura' => $assinatura->signature_path ?? null],
            ]);

            $this->logEvent($ordemServico, 'tecnico', 'assinatura_tecnico_utilizou_salva', [
                'assinatura_id' => $assinatura->id,
            ], $request, $sessionUuid);

            // Verificar se ambas assinaturas estão completas e gerar PDF
            $this->verificarEGerarPDF($ordemServico);

            if (request()->wantsJson() || request()->ajax()) {
                return response()->json(['message' => 'Assinatura do técnico aplicada à OS com sucesso.']);
            }

            return redirect()->back()->with('success', 'Assinatura do técnico aplicada à OS.');
        } catch (\Exception $e) {
            if (request()->wantsJson() || request()->ajax()) {
                return response()->json(['message' => 'Erro ao aplicar assinatura: ' . $e->getMessage()], 500);
            }
            return redirect()->back()->with('error', 'Erro ao aplicar assinatura: ' . $e->getMessage());
        }
    }

    public function deleteAssinaturaTecnico(OrdemServico $ordemServico)
    {
        if ($rejeitar = $this->rejeitarSeOsEncerrada($ordemServico)) {
            return $rejeitar;
        }
        $assinatura = $ordemServico->assinaturaTecnico;
        if ($assinatura) {
            $assinaturaAntesPath = $assinatura->signature_path;
            $this->deleteSignatureFile($assinatura->signature_path);
            $assinatura->delete();
            
            // Marcar documento ativo como histórico (não apagar)
            OrdemServicoDocumento::where('ordem_servico_id', $ordemServico->id)
                ->where('ativo', true)
                ->update(['ativo' => false]);

            $this->registrarOsLog($ordemServico, 'assinatura', 'Assinatura do técnico removida', [
                'antes' => ['assinatura' => $assinaturaAntesPath],
                'depois' => ['assinatura' => null],
            ]);
        }

        if (request()->wantsJson() || request()->ajax()) {
            return response()->json(['message' => 'Assinatura do técnico removida com sucesso.']);
        }

        return redirect()->back()->with('success', 'Assinatura do técnico removida.');
    }

    public function deleteAssinaturaCliente(OrdemServico $ordemServico)
    {
        if ($rejeitar = $this->rejeitarSeOsEncerrada($ordemServico)) {
            return $rejeitar;
        }
        $assinatura = $ordemServico->assinaturaCliente;
        if ($assinatura) {
            $assinaturaAntesPath = $assinatura->signature_path;
            $this->deleteSignatureFile($assinatura->signature_path);
            $assinatura->delete();
            
            // Marcar documento ativo como histórico (não apagar)
            OrdemServicoDocumento::where('ordem_servico_id', $ordemServico->id)
                ->where('ativo', true)
                ->update(['ativo' => false]);

            $this->registrarOsLog($ordemServico, 'assinatura', 'Assinatura do cliente removida', [
                'antes' => ['assinatura' => $assinaturaAntesPath],
                'depois' => ['assinatura' => null],
            ]);
        }

        if (request()->wantsJson() || request()->ajax()) {
            return response()->json(['message' => 'Assinatura do cliente removida com sucesso.']);
        }

        return redirect()->back()->with('success', 'Assinatura do cliente removida.');
    }

    public function downloadDocumento($id)
    {
        $documento = OrdemServicoDocumento::findOrFail($id);

        if (!Storage::disk('public')->exists($documento->file_path)) {
            abort(404, 'Arquivo do documento não encontrado.');
        }

        return Storage::disk('public')->download(
            $documento->file_path,
            $documento->file_name
        );
    }

    public function deleteDocumento($id)
    {
        $documento = OrdemServicoDocumento::findOrFail($id);
        $ordem = $documento->ordemServico;
        if ($ordem && ($rejeitar = $this->rejeitarSeOsEncerrada($ordem))) {
            return $rejeitar;
        }

        // Só permitir deletar documentos históricos (não ativos)
        if ($documento->ativo) {
            if (request()->wantsJson() || request()->ajax()) {
                return response()->json(['message' => 'Não é possível deletar o documento ativo.'], 403);
            }
            return redirect()->back()->with('error', 'Não é possível deletar o documento ativo.');
        }

        try {
            if (Storage::disk('public')->exists($documento->file_path)) {
                Storage::disk('public')->delete($documento->file_path);
            }

            $documento->delete();

            if (request()->wantsJson() || request()->ajax()) {
                return response()->json(['message' => 'Documento removido do histórico.']);
            }

            return redirect()->back()->with('success', 'Documento removido do histórico.');
        } catch (\Exception $e) {
            if (request()->wantsJson() || request()->ajax()) {
                return response()->json(['message' => 'Erro ao remover documento: ' . $e->getMessage()], 500);
            }
            return redirect()->back()->with('error', 'Erro ao remover documento: ' . $e->getMessage());
        }
    }

    public function deleteAssinaturaUsuario(Request $request)
    {
        $assinatura = $request->user()?->assinatura;
        if ($assinatura) {
            $this->deleteSignatureFile($assinatura->signature_path);
            $assinatura->delete();
        }

        if (request()->wantsJson() || request()->ajax()) {
            return response()->json(['message' => 'Assinatura salva removida com sucesso.']);
        }

        return redirect()->back()->with('success', 'Assinatura salva removida.');
    }

    public function gerarTokenCliente(OrdemServico $ordemServico)
    {
        if ($rejeitar = $this->rejeitarSeOsEncerrada($ordemServico)) {
            return $rejeitar;
        }

        $service = app(OrdemServicoAssinaturaClienteTokenService::class);
        $tokenModel = $service->gerarNovoToken($ordemServico);
        $service->registrarLogTokenGerado($ordemServico, $tokenModel, request(), $this->getSessionUuid());

        if (request()->wantsJson() || request()->ajax()) {
            return response()->json([
                'message' => 'Novo link de assinatura do cliente gerado com sucesso.',
                'token' => $tokenModel->token,
            ]);
        }

        return redirect()->back()->with('success', 'Novo link de assinatura do cliente gerado.');
    }

    public function logEventPublic(Request $request)
    {
        $this->validateWithFilters($request, [
            'token' => 'nullable|string',
            'ordem_servico_id' => 'nullable|integer',
            'evento' => 'required|string|max:100',
            'data' => 'nullable|array',
            'tipo' => 'nullable|string|max:20',
            'session_uuid' => 'nullable|string|max:64',
        ]);

        $ordemServicoId = null;
        if ($request->filled('token')) {
            $tokenModel = OrdemServicoAssinaturaToken::where('token', $request->input('token'))->first();
            if (!$tokenModel) {
                return response()->json(['ok' => false], 404);
            }
            $ordemServicoId = $tokenModel->ordem_servico_id;
        } elseif ($request->filled('ordem_servico_id')) {
            $ordemServicoId = (int) $request->input('ordem_servico_id');
        }

        if (!$ordemServicoId) {
            return response()->json(['ok' => false], 422);
        }

        OrdemServicoAssinaturaEvent::create([
            'ordem_servico_id' => $ordemServicoId,
            'tipo' => $request->input('tipo'),
            'evento' => $request->input('evento'),
            'data' => $request->input('data'),
            'ip' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'session_uuid' => $request->input('session_uuid'),
        ]);

        return response()->json(['ok' => true]);
    }

    private function validarAssinatura(Request $request): array
    {
        return $this->validateWithFilters($request, [
            'signature_data' => 'required|string',
            'signature_points' => 'nullable|string',
            'checkbox_checked' => 'required|in:1',
            'checkbox_text' => 'required|string|max:500',
            'terms_version' => 'required|string|max:20',
            'privacy_version' => 'required|string|max:20',
            'legal_basis' => 'required|string|max:100',
            'geo' => 'nullable|string',
            'session_uuid' => 'nullable|string|max:64',
            'cpf_confirmacao' => 'nullable|string|max:20',
            'celular_confirmacao' => 'nullable|string|max:30',
            'extra_metadata' => 'nullable|string',
        ]);
    }

    private function salvarAssinaturaOs(OrdemServico $ordemServico, string $tipo, string $assinaturaPath, array $data, string $sessionUuid, ?int $userId): OrdemServicoAssinatura
    {
        $ordemServico->assinaturas()->where('tipo', $tipo)->delete();

        return OrdemServicoAssinatura::create([
            'ordem_servico_id' => $ordemServico->id,
            'tipo' => $tipo,
            'user_id' => $userId,
            'signature_path' => $assinaturaPath,
            'signature_points' => isset($data['signature_points']) && $data['signature_points'] ? json_decode($data['signature_points'], true) : null,
            'metadata' => [
                'finalizado_em' => now()->toIso8601String(),
                'confirmacao' => [
                    'cpf' => $data['cpf_confirmacao'] ?? null,
                    'celular' => $data['celular_confirmacao'] ?? null,
                ],
                'extra' => !empty($data['extra_metadata']) ? json_decode($data['extra_metadata'], true) : null,
            ],
            'checkbox_text' => $data['checkbox_text'] ?? $this->checkboxTexto,
            'checkbox_checked' => isset($data['checkbox_checked']) ? (bool) $data['checkbox_checked'] : true,
            'checkbox_checked_at' => now(),
            'checkbox_checked_ip' => request()->ip(),
            'session_uuid' => $sessionUuid,
            'signed_ip' => request()->ip(),
            'signed_user_agent' => request()->userAgent(),
            'signed_at' => now(),
            'geo' => isset($data['geo']) && $data['geo'] ? json_decode($data['geo'], true) : null,
            'terms_version' => $data['terms_version'] ?? $this->termsVersion,
            'privacy_version' => $data['privacy_version'] ?? $this->privacyVersion,
            'legal_basis' => $data['legal_basis'] ?? $this->legalBasis,
        ]);
    }

    private function salvarAssinaturaUsuario(?int $userId, string $assinaturaPath, array $data): void
    {
        if (!$userId) {
            return;
        }

        // Copiar a assinatura para um caminho permanente do usuário (separado da OS)
        $conteudo = Storage::disk('public')->get($assinaturaPath);
        if ($conteudo) {
            $filename = 'user_' . $userId . '_signature_' . now()->format('Ymd_His') . '_' . Str::random(6) . '.png';
            $userPath = 'assinaturas/usuarios/' . $userId . '/' . $filename;
            Storage::disk('public')->put($userPath, $conteudo);
            
            // Deletar assinatura antiga do usuário se existir
            $assinaturaAntiga = UserSignature::where('user_id', $userId)->first();
            if ($assinaturaAntiga && $assinaturaAntiga->signature_path && $assinaturaAntiga->signature_path !== $userPath) {
                if (Storage::disk('public')->exists($assinaturaAntiga->signature_path)) {
                    Storage::disk('public')->delete($assinaturaAntiga->signature_path);
                }
            }

            UserSignature::updateOrCreate(
                ['user_id' => $userId],
                [
                    'signature_path' => $userPath,
                    'signature_points' => isset($data['signature_points']) && $data['signature_points'] ? json_decode($data['signature_points'], true) : null,
                    'metadata' => [
                        'ip' => request()->ip(),
                        'user_agent' => request()->userAgent(),
                    ],
                ]
            );
        }
    }

    private function salvarAssinatura(string $dataUrl, int $ordemId, string $tipo): string
    {
        $data = preg_replace('#^data:image/\w+;base64,#i', '', $dataUrl);
        $binary = base64_decode($data);
        $filename = 'os_' . $ordemId . '_' . $tipo . '_' . now()->format('Ymd_His') . '_' . Str::random(6) . '.png';
        $path = 'assinaturas/' . $ordemId . '/' . $filename;
        Storage::disk('public')->put($path, $binary);
        return $path;
    }

    private function copiarAssinatura(string $origem, int $ordemId, string $tipo): string
    {
        if (empty($origem)) {
            throw new \Exception('Caminho da assinatura de origem não pode ser vazio.');
        }

        if (!Storage::disk('public')->exists($origem)) {
            throw new \Exception('Arquivo de assinatura não encontrado: ' . $origem);
        }

        $conteudo = Storage::disk('public')->get($origem);
        
        if ($conteudo === null || empty($conteudo)) {
            throw new \Exception('Conteúdo da assinatura está vazio ou inválido.');
        }

        $filename = 'os_' . $ordemId . '_' . $tipo . '_' . now()->format('Ymd_His') . '_' . Str::random(6) . '.png';
        $path = 'assinaturas/' . $ordemId . '/' . $filename;
        Storage::disk('public')->put($path, $conteudo);
        return $path;
    }

    /**
     * Serve a imagem da assinatura com verificação de acesso (token público ou usuário autenticado).
     * Não expõe o arquivo diretamente; exige token válido da OS ou usuário logado.
     */
    public function servirImagem(OrdemServicoAssinatura $assinatura)
    {
        if (empty($assinatura->signature_path) || !Storage::disk('public')->exists($assinatura->signature_path)) {
            abort(404);
        }

        $tokenStr = request()->query('token');
        if ($tokenStr !== null && $tokenStr !== '') {
            $tokenModel = OrdemServicoAssinaturaToken::where('token', $tokenStr)->first();
            if (!$tokenModel || $tokenModel->ordem_servico_id !== $assinatura->ordem_servico_id) {
                abort(403);
            }
            if ($tokenModel->status === 'revoked') {
                abort(403);
            }
            if ($tokenModel->expires_at && $tokenModel->expires_at->isPast()) {
                abort(403);
            }
        } else {
            if (!auth()->check()) {
                abort(403);
            }
        }

        $path = Storage::disk('public')->path($assinatura->signature_path);
        $mime = Storage::disk('public')->mimeType($assinatura->signature_path) ?: 'image/png';

        return response()->file($path, [
            'Content-Type' => $mime,
            'Content-Disposition' => 'inline; filename="' . basename($assinatura->signature_path) . '"',
        ]);
    }

    private function deleteSignatureFile(?string $path): void
    {
        if ($path && Storage::disk('public')->exists($path)) {
            Storage::disk('public')->delete($path);
        }
    }

    private function getSessionUuid(): string
    {
        if (!session()->has('signature_session_uuid')) {
            session(['signature_session_uuid' => (string) Str::uuid()]);
        }
        return session('signature_session_uuid');
    }

    private function logEvent(OrdemServico $ordemServico, string $tipo, string $evento, array $data, Request $request, string $sessionUuid): void
    {
        OrdemServicoAssinaturaEvent::create([
            'ordem_servico_id' => $ordemServico->id,
            'tipo' => $tipo,
            'evento' => $evento,
            'data' => $data,
            'ip' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'session_uuid' => $sessionUuid,
        ]);
    }

    private function verificarEGerarPDF(OrdemServico $ordemServico): void
    {
        $ordemServico->load(['assinaturaTecnico', 'assinaturaCliente', 'cliente', 'unidade', 'contato', 'endereco', 'equipamento', 'produtos.produto.composicoes.componente', 'servicos']);
        
        // Verificar se ambas assinaturas estão completas
        if (!$ordemServico->assinaturaTecnico || !$ordemServico->assinaturaCliente) {
            return;
        }

        // Marcar documento anterior como histórico (se existir)
        OrdemServicoDocumento::where('ordem_servico_id', $ordemServico->id)
            ->where('ativo', true)
            ->update(['ativo' => false]);

        // Gerar novo PDF
        $this->gerarPDF($ordemServico);
    }

    private function gerarPDF(OrdemServico $ordemServico): void
    {
        $empresa = Empresa::first();
        $ordemServico->load([
            'assinaturaTecnico.user', 
            'assinaturaCliente', 
            'documentoAtivo',
            'produtos.produto.composicoes.componente',
            'produtos.variacao',
            'checklists.checklistModel',
        ]);

        // Calcular hashes antes de renderizar (serão recalculados após renderização)
        // Mas vamos preparar os dados de segurança
        $dadosSeguranca = [
            'os_id' => $ordemServico->id,
            'os_codigo' => $ordemServico->codigo_interno,
            'gerado_em' => now()->toIso8601String(),
            'gerado_por' => auth()->id(),
        ];

        // Buscar documento ativo para obter hashes se já existir
        $documentoAtivo = $ordemServico->documentoAtivo;
        $hashSha256 = $documentoAtivo?->metadata['seguranca']['hash_sha256'] ?? null;
        $hashMd5 = $documentoAtivo?->metadata['seguranca']['hash_md5'] ?? null;
        $assinaturaDigital = $documentoAtivo?->metadata['seguranca']['assinatura_digital'] ?? null;

        // Renderizar view HTML
        $html = view('ordens_servico.documento_assinado', [
            'ordem' => $ordemServico,
            'empresa' => $empresa,
            'dadosSeguranca' => $dadosSeguranca,
            'hashSha256' => $hashSha256,
            'hashMd5' => $hashMd5,
            'assinaturaDigital' => $assinaturaDigital,
        ])->render();

        // Tentar usar DomPDF se disponível, senão salvar HTML
        $pdfContent = null;
        $extensao = 'html';
        
        try {
            // Tentar usar DomPDF via facade
            if (class_exists(\Barryvdh\DomPDF\Facade\Pdf::class)) {
                $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadHTML($html);
                $pdf->setPaper('A4', 'portrait');
                $pdf->setOption('margin-top', '25mm');
                $pdf->setOption('margin-right', '20mm');
                $pdf->setOption('margin-bottom', '20mm');
                $pdf->setOption('margin-left', '20mm');
                $pdf->setOption('enable-local-file-access', true);
                $pdf->setOption('isHtml5ParserEnabled', true);
                
                // Preparar variáveis
                $osInfo = ($ordemServico->codigo_interno ?? $ordemServico->id) . " - " . now()->format('d/m/Y H:i');
                
                // Converter mm para pontos: 1mm = 2.83465pt
                $marginRight = 20 * 2.83465;
                $marginLeft = 20 * 2.83465;
                $marginTop = 8 * 2.83465;
                $fontSize = 9;
                $fontSizeInfo = 8;
                $color = [0.42, 0.45, 0.51]; // #6b7280 em RGB
                
                // Obter instância do DomPDF
                $dompdf = $pdf->getDomPDF();
                
                // Renderizar primeiro para criar as páginas
                $dompdf->render();
                
                // Adicionar paginação e info da OS após render usando page_script que processa cada página
                $canvas = $dompdf->getCanvas();
                $fontMetrics = $dompdf->getFontMetrics();
                $font = $fontMetrics->getFont("Arial", "normal");
                
                // Usar page_script diretamente para garantir que funcione em todas as páginas
                $canvas->page_script(function ($pageNumber, $pageCount, $canvas, $fontMetrics) use ($osInfo, $marginRight, $marginLeft, $marginTop, $fontSize, $fontSizeInfo, $color) {
                    $font = $fontMetrics->getFont("Arial", "normal");
                    
                    // Info da OS no topo esquerdo
                    $canvas->text(
                        $marginLeft,
                        $marginTop,
                        $osInfo,
                        $font,
                        $fontSizeInfo,
                        $color
                    );
                    
                    // Paginação no topo direito
                    $canvas->text(
                        $canvas->get_width() - $marginRight - 50,
                        $marginTop,
                        "Página {$pageNumber} de {$pageCount}",
                        $font,
                        $fontSize,
                        $color
                    );
                });
                
                // Gerar o PDF final
                $pdfContent = $dompdf->output();
                $extensao = 'pdf';
            } 
            // Tentar usar DomPDF diretamente
            elseif (class_exists(\DomPDF\DomPDF::class)) {
                $options = new \DomPDF\Options();
                $options->set('defaultPaperSize', 'a4');
                $options->set('defaultPaperOrientation', 'portrait');
                $options->set('marginTop', '25mm');
                $options->set('marginRight', '20mm');
                $options->set('marginBottom', '20mm');
                $options->set('marginLeft', '20mm');
                $options->set('enableLocalFileAccess', true);
                $options->set('isHtml5ParserEnabled', true);
                $options->set('isPhpEnabled', true);
                
                // Preparar variáveis
                $osInfo = ($ordemServico->codigo_interno ?? $ordemServico->id) . " - " . now()->format('d/m/Y H:i');
                
                // Converter mm para pontos: 1mm = 2.83465pt
                $marginRight = 20 * 2.83465;
                $marginLeft = 20 * 2.83465;
                $marginTop = 8 * 2.83465;
                $fontSize = 9;
                $fontSizeInfo = 8;
                $color = [0.42, 0.45, 0.51]; // #6b7280 em RGB
                
                $dompdf = new \DomPDF\DomPDF($options);
                $dompdf->loadHtml($html);
                $dompdf->setPaper('A4', 'portrait');
                
                // Renderizar primeiro para criar as páginas
                $dompdf->render();
                
                // Adicionar paginação e info da OS após render usando page_script que processa cada página
                $canvas = $dompdf->getCanvas();
                $fontMetrics = $dompdf->getFontMetrics();
                $font = $fontMetrics->getFont("Arial", "normal");
                
                // Usar page_script diretamente para garantir que funcione em todas as páginas
                $canvas->page_script(function ($pageNumber, $pageCount, $canvas, $fontMetrics) use ($osInfo, $marginRight, $marginLeft, $marginTop, $fontSize, $fontSizeInfo, $color) {
                    $font = $fontMetrics->getFont("Arial", "normal");
                    
                    // Info da OS no topo esquerdo
                    $canvas->text(
                        $marginLeft,
                        $marginTop,
                        $osInfo,
                        $font,
                        $fontSizeInfo,
                        $color
                    );
                    
                    // Paginação no topo direito
                    $canvas->text(
                        $canvas->get_width() - $marginRight - 50,
                        $marginTop,
                        "Página {$pageNumber} de {$pageCount}",
                        $font,
                        $fontSize,
                        $color
                    );
                });
                
                // Gerar o PDF final
                $pdfContent = $dompdf->output();
                $extensao = 'pdf';
            }
        } catch (\Exception $e) {
            // Log do erro para debug
            \Log::error('Erro ao gerar PDF: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString()
            ]);
            // Se falhar, continuar com HTML
        }
        
        // Se não conseguiu gerar PDF, usar HTML
        if (!$pdfContent) {
            $pdfContent = $html;
            $extensao = 'html';
        }

        // Calcular hash de integridade do documento
        $hashSha256 = hash('sha256', $pdfContent);
        $hashMd5 = md5($pdfContent);
        $fileSize = strlen($pdfContent);
        
        // Criar assinatura digital (hash do conteúdo + metadados)
        $dadosIntegridade = [
            'os_id' => $ordemServico->id,
            'os_codigo' => $ordemServico->codigo_interno,
            'gerado_em' => now()->toIso8601String(),
            'gerado_por' => auth()->id(),
            'file_size' => $fileSize,
            'hash_sha256' => $hashSha256,
            'hash_md5' => $hashMd5,
        ];
        $assinaturaDigital = hash('sha256', json_encode($dadosIntegridade) . config('app.key'));

        // Salvar arquivo
        $filename = 'os_' . $ordemServico->id . '_assinado_' . now()->format('Ymd_His') . '_' . Str::random(6) . '.' . $extensao;
        $path = 'documentos/os/' . $ordemServico->id . '/' . $filename;
        Storage::disk('public')->put($path, $pdfContent);

        // Criar registro no banco com dados de segurança
        $metadata = [
            'gerado_em' => now()->toIso8601String(),
            'gerado_por' => auth()->id(),
            'gerado_por_nome' => auth()->user()?->name,
            'assinatura_tecnico_id' => $ordemServico->assinaturaTecnico->id,
            'assinatura_cliente_id' => $ordemServico->assinaturaCliente->id,
            'assinatura_tecnico_signed_at' => optional($ordemServico->assinaturaTecnico->signed_at)->toIso8601String(),
            'assinatura_cliente_signed_at' => optional($ordemServico->assinaturaCliente->signed_at)->toIso8601String(),
            'seguranca' => [
                'hash_sha256' => $hashSha256,
                'hash_md5' => $hashMd5,
                'assinatura_digital' => $assinaturaDigital,
                'file_size_original' => $fileSize,
                'versao_documento' => '1.0',
                'algoritmo_hash' => 'SHA256',
            ],
        ];

        $documento = OrdemServicoDocumento::create([
            'ordem_servico_id' => $ordemServico->id,
            'tipo' => 'assinado',
            'file_path' => $path,
            'file_name' => $filename,
            'file_size' => $fileSize,
            'metadata' => $metadata,
            'ativo' => true,
            'gerado_em' => now(),
            'created_by' => auth()->id(),
        ]);

        // Verificar integridade após salvar (validação inicial)
        $verificacao = $this->verificarIntegridade($documento);
        if (!$verificacao['valido']) {
            \Log::warning('Documento gerado com problemas de integridade', [
                'documento_id' => $documento->id,
                'erros' => $verificacao['erros'] ?? [],
            ]);
        }
    }

    public function verificarIntegridadeDocumento($id)
    {
        $documento = OrdemServicoDocumento::findOrFail($id);

        $resultado = $this->verificarIntegridade($documento);
        
        if (request()->wantsJson() || request()->ajax()) {
            return response()->json($resultado);
        }
        
        return redirect()->back()->with($resultado['valido'] ? 'success' : 'error', 
            $resultado['valido'] 
                ? 'Documento íntegro. Nenhuma alteração detectada.' 
                : 'Documento alterado: ' . implode(' ', $resultado['erros'] ?? []));
    }

    private function verificarIntegridade(OrdemServicoDocumento $documento): array
    {
        if (!Storage::disk('public')->exists($documento->file_path)) {
            return [
                'valido' => false,
                'erro' => 'Arquivo não encontrado',
            ];
        }

        $conteudoAtual = Storage::disk('public')->get($documento->file_path);
        $hashAtualSha256 = hash('sha256', $conteudoAtual);
        $hashAtualMd5 = md5($conteudoAtual);
        
        $hashOriginalSha256 = $documento->metadata['seguranca']['hash_sha256'] ?? null;
        $hashOriginalMd5 = $documento->metadata['seguranca']['hash_md5'] ?? null;

        $valido = true;
        $erros = [];

        if ($hashOriginalSha256 && $hashAtualSha256 !== $hashOriginalSha256) {
            $valido = false;
            $erros[] = 'Hash SHA-256 não confere. Documento pode ter sido alterado.';
        }

        if ($hashOriginalMd5 && $hashAtualMd5 !== $hashOriginalMd5) {
            $valido = false;
            $erros[] = 'Hash MD5 não confere. Documento pode ter sido alterado.';
        }

        // Verificar assinatura digital
        if ($valido && isset($documento->metadata['seguranca']['assinatura_digital'])) {
            $dadosIntegridade = [
                'os_id' => $documento->ordem_servico_id,
                'os_codigo' => $documento->ordemServico->codigo_interno,
                'gerado_em' => $documento->metadata['gerado_em'],
                'gerado_por' => $documento->metadata['gerado_por'],
                'file_size' => $documento->file_size,
                'hash_sha256' => $hashAtualSha256,
                'hash_md5' => $hashAtualMd5,
            ];
            $assinaturaCalculada = hash('sha256', json_encode($dadosIntegridade) . config('app.key'));
            
            if ($assinaturaCalculada !== $documento->metadata['seguranca']['assinatura_digital']) {
                $valido = false;
                $erros[] = 'Assinatura digital não confere. Documento pode ter sido alterado.';
            }
        }

        return [
            'valido' => $valido,
            'erros' => $erros,
            'hash_sha256_atual' => $hashAtualSha256,
            'hash_md5_atual' => $hashAtualMd5,
            'hash_sha256_original' => $hashOriginalSha256,
            'hash_md5_original' => $hashOriginalMd5,
        ];
    }
}
