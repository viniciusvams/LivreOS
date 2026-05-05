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

namespace Atendimento\Services;

use Atendimento\Models\Atendimento;
use Atendimento\Models\AtendimentoFoto;
use App\Models\ChecklistModel;
use App\Models\ChecklistAnswer;
use App\Models\OrdemServico;
use App\Models\OrdemServicoAnexo;
use App\Models\OrdemServicoAssinatura;
use App\Models\OrdemServicoLog;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

/**
 * Converte um Atendimento (ficha de visita) em Ordem de Serviço, importando
 * checklist, fotos e assinatura do cliente. Isolado no plugin.
 */
class AttendanceService
{
    /**
     * Converte atendimento em OS: cria OS, importa checklist, fotos (com legenda/tipo) e assinatura como anexos,
     * registra log de conversão com usuário que fez a conversão.
     */
    public static function convertToServiceOrder(int $atendimentoId): OrdemServico
    {
        $atendimento = Atendimento::with(['cliente.enderecos', 'endereco', 'prioridade', 'fotos', 'checklistModel'])
            ->findOrFail($atendimentoId);

        if ($atendimento->ordem_servico_id) {
            return OrdemServico::findOrFail($atendimento->ordem_servico_id);
        }

        $cliente = $atendimento->cliente;
        if (! $cliente) {
            throw new \InvalidArgumentException('Cliente não encontrado.');
        }

        $enderecoId = null;
        if ($atendimento->endereco_tipo === Atendimento::ENDERECO_CLIENTE && $atendimento->endereco_id) {
            $enderecoId = $atendimento->endereco_id;
        } else {
            $principal = $cliente->enderecos()->orderByDesc('principal')->orderBy('id')->first();
            $enderecoId = $principal?->id;
        }

        $diagnostico = trim(($atendimento->diagnostico_tecnico ?? '') . "\n\n" . ($atendimento->observacoes_tecnicas ?? ''));
        $refAtendimento = 'Atendimento externo #' . $atendimento->id;

        return DB::transaction(function () use ($atendimento, $cliente, $enderecoId, $diagnostico, $refAtendimento) {
            $osData = [
                'cliente_id' => $cliente->id,
                'endereco_id' => $enderecoId,
                'relato_cliente' => $atendimento->problema_reportado,
                'diagnostico_tecnico' => $diagnostico,
                'data_abertura' => $atendimento->data_hora_atendimento->format('Y-m-d'),
                'status' => 'Aberta',
                'prioridade' => $atendimento->prioridade->nome ?? 'Média',
                'origem_os' => 'Atendimento externo',
                'observacoes_internas' => 'Convertido do ' . $refAtendimento . '.',
                'created_by' => auth()->id(),
                'updated_by' => auth()->id(),
            ];

            if (\Illuminate\Support\Facades\Schema::hasColumn('ordem_servicos', 'tenant_id')) {
                $osData['tenant_id'] = $atendimento->getAttribute('tenant_id') ?? null;
            }

            $os = OrdemServico::create($osData);

            $checklistModelId = $atendimento->checklist_model_id;
            $respostas = $atendimento->checklist_respostas;
            $camposAdhoc = $atendimento->checklist_campos_adhoc;

            if ((\is_array($respostas) && $respostas !== []) || (\is_array($camposAdhoc) && $camposAdhoc !== [])) {
                if ($checklistModelId && class_exists(ChecklistModel::class)) {
                    $modelExists = ChecklistModel::where('id', $checklistModelId)->exists();
                    if ($modelExists) {
                        ChecklistAnswer::create([
                            'ordem_servico_id' => $os->id,
                            'checklist_model_id' => $checklistModelId,
                            'respostas' => $respostas ?? [],
                            'observacoes' => null,
                            'created_by' => auth()->id(),
                            'updated_by' => auth()->id(),
                        ]);
                    }
                } elseif (\is_array($camposAdhoc) && $camposAdhoc !== [] && class_exists(ChecklistModel::class)) {
                    $novoModel = ChecklistModel::create([
                        'nome' => 'Checklist Atendimento #' . $atendimento->id,
                        'descricao' => 'Importado do atendimento externo',
                        'campos' => $camposAdhoc,
                        'ativo' => true,
                        'created_by' => auth()->id(),
                        'updated_by' => auth()->id(),
                    ]);
                    ChecklistAnswer::create([
                        'ordem_servico_id' => $os->id,
                        'checklist_model_id' => $novoModel->id,
                        'respostas' => $respostas ?? [],
                        'observacoes' => null,
                        'created_by' => auth()->id(),
                        'updated_by' => auth()->id(),
                    ]);
                }
            }

            // Fotos do atendimento → anexos da OS (disk public para exibição na OS)
            $dirPublic = 'ordens-servico/' . $os->id;
            foreach ($atendimento->fotos as $foto) {
                $origem = $foto->caminho;
                if (! $origem || ! Storage::disk('local')->exists($origem)) {
                    continue;
                }
                $ext = strtolower(pathinfo($origem, PATHINFO_EXTENSION)) ?: 'jpg';
                $nomeArquivo = 'atendimento_' . $foto->tipo . '_' . $foto->id . '.' . $ext;
                $destino = $dirPublic . '/' . $nomeArquivo;
                $conteudo = Storage::disk('local')->get($origem);
                Storage::disk('public')->put($destino, $conteudo);
                $nomeExibicao = ($foto->legenda ?: ucfirst($foto->tipo)) . '.' . $ext;
                OrdemServicoAnexo::create([
                    'ordem_servico_id' => $os->id,
                    'nome_arquivo' => $nomeExibicao,
                    'caminho_arquivo' => $destino,
                    'tipo' => $foto->tipo,
                    'descricao' => $foto->legenda,
                    'metadata' => ['atendimento_foto_id' => $foto->id],
                    'tipo_mime' => 'image/' . ($ext === 'jpg' ? 'jpeg' : $ext),
                    'tamanho' => \strlen($conteudo),
                    'created_by' => auth()->id(),
                ]);
            }

            // Assinatura do cliente: modelo de assinatura (se existir) + anexo na OS (sempre PNG para exibição)
            $pathAssinaturaNormalized = $atendimento->assinatura_cliente_path ? str_replace('\\', '/', trim($atendimento->assinatura_cliente_path)) : null;
            if ($pathAssinaturaNormalized && Storage::disk('local')->exists($pathAssinaturaNormalized)) {
                $conteudoAssinatura = Storage::disk('local')->get($pathAssinaturaNormalized);
                $conteudoPng = self::convertImageToPng($conteudoAssinatura);
                if ($conteudoPng === null) {
                    $conteudoPng = $conteudoAssinatura;
                }
                $extAss = 'png';
                $nomeAssinaturaArquivo = 'assinatura_cliente_atendimento_' . $atendimento->id . '.png';
                $destinoAssinaturaAnexo = $dirPublic . '/' . $nomeAssinaturaArquivo;
                Storage::disk('public')->put($destinoAssinaturaAnexo, $conteudoPng);
                $descAssinatura = 'Assinatura do cliente (Atendimento externo #' . $atendimento->id . ')';
                if ($atendimento->assinatura_data || $atendimento->assinatura_local) {
                    $partes = [];
                    if ($atendimento->assinatura_data) {
                        $partes[] = 'Data: ' . $atendimento->assinatura_data->format('d/m/Y H:i');
                    }
                    if ($atendimento->assinatura_local) {
                        $partes[] = 'Local: ' . $atendimento->assinatura_local;
                    }
                    $descAssinatura .= ' — ' . implode(' | ', $partes);
                }
                OrdemServicoAnexo::create([
                    'ordem_servico_id' => $os->id,
                    'nome_arquivo' => $nomeAssinaturaArquivo,
                    'caminho_arquivo' => $destinoAssinaturaAnexo,
                    'tipo' => 'assinatura',
                    'descricao' => $descAssinatura,
                    'metadata' => ['atendimento_id' => $atendimento->id],
                    'tipo_mime' => 'image/png',
                    'tamanho' => \strlen($conteudoPng),
                    'created_by' => auth()->id(),
                ]);
                if (class_exists(OrdemServicoAssinatura::class)) {
                    $dirAssinaturas = 'ordem_servico_assinaturas/' . $os->id;
                    $destinoAssinaturaModel = $dirAssinaturas . '/cliente_atendimento_' . $atendimento->id . '.png';
                    Storage::disk('local')->makeDirectory($dirAssinaturas);
                    Storage::disk('local')->put($destinoAssinaturaModel, $conteudoPng);
                    OrdemServicoAssinatura::create([
                        'ordem_servico_id' => $os->id,
                        'tipo' => 'cliente',
                        'user_id' => null,
                        'signature_path' => $destinoAssinaturaModel,
                        'signed_at' => $atendimento->assinatura_data ?? now(),
                    ]);
                }
            }

            // Histórico da OS: registro de conversão com usuário logado
            $user = auth()->user();
            if (class_exists(OrdemServicoLog::class)) {
                OrdemServicoLog::create([
                    'ordem_servico_id' => $os->id,
                    'user_id' => auth()->id(),
                    'tipo' => 'conversao_atendimento',
                    'descricao' => 'OS criada pelo sistema na conversão do Atendimento externo #' . $atendimento->id,
                    'dados' => [
                        'atendimento_id' => $atendimento->id,
                        'usuario_id' => $user?->id,
                        'usuario_nome' => $user?->name,
                        'usuario_email' => $user?->email,
                        'convertido_em' => now()->toIso8601String(),
                    ],
                    'ip' => request()->ip(),
                ]);
            }

            $atendimento->update([
                'ordem_servico_id' => $os->id,
                'fase' => Atendimento::FASE_CONVERTIDO_OS,
            ]);

            return $os;
        });
    }

    /**
     * Converte conteúdo de imagem (JPEG, PNG, GIF, WebP, etc.) para PNG com fundo branco,
     * para que a assinatura (geralmente preta em fundo transparente) seja sempre visível.
     * Retorna null se não for possível converter (usa conteúdo original no caller).
     */
    private static function convertImageToPng(string $imageContent): ?string
    {
        if ($imageContent === '') {
            return null;
        }
        if (! \function_exists('imagecreatefromstring') || ! \function_exists('imagepng')) {
            return null;
        }
        try {
            $im = @imagecreatefromstring($imageContent);
            if ($im === false) {
                return null;
            }
            $w = imagesx($im);
            $h = imagesy($im);
            if ($w <= 0 || $h <= 0) {
                imagedestroy($im);
                return null;
            }
            // Novo recurso com fundo branco para evitar "tudo preto" quando a origem tem transparência
            $white = imagecreatetruecolor($w, $h);
            if ($white === false) {
                imagedestroy($im);
                return null;
            }
            $bg = imagecolorallocate($white, 255, 255, 255);
            imagefill($white, 0, 0, $bg);
            imagecopy($white, $im, 0, 0, 0, 0, $w, $h);
            imagedestroy($im);
            ob_start();
            imagepng($white);
            $png = ob_get_clean();
            imagedestroy($white);
            return $png !== false ? $png : null;
        } catch (\Throwable $e) {
            return null;
        }
    }
}
