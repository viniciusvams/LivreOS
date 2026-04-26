<?php

/**
 * Consulta de situação da NF-e na SEFAZ.
 *
 * A consulta plena exige certificado digital A1/A3 e integração com Web Service
 * (NFeDistribuicaoDFe / consulta por chave). Este serviço valida a chave localmente
 * e deixa ganchos para integração futura (URL configurável ou pacote fiscal).
 *
 * @copyright 2024-2026 LivreOS
 * @license   https://www.gnu.org/licenses/agpl-3.0.txt AGPL-3.0
 */
namespace Compras\Services;

class NfeSefazConsultaService
{
    /**
     * @return array{ok: bool, mensagem: string, detalhes?: array}
     */
    public function consultarChave(?string $chave): array
    {
        if ($chave === null || $chave === '') {
            return [
                'ok' => false,
                'mensagem' => 'Chave de acesso ausente; consulta SEFAZ não executada.',
            ];
        }
        $val = NfeChaveValidator::validar($chave);
        if (! $val['ok']) {
            return [
                'ok' => false,
                'mensagem' => $val['motivo'] ?? 'Chave inválida.',
                'detalhes' => ['tipo' => 'validacao_local'],
            ];
        }

        // Integração WS SEFAZ com certificado: implementar via apply_filters no projeto ou serviço externo.
        if (function_exists('apply_filters')) {
            $externo = apply_filters('compras.sefaz.consulta_chave', null, $chave);
            if (is_array($externo) && isset($externo['ok'])) {
                return $externo;
            }
        }

        return [
            'ok' => true,
            'mensagem' => 'Chave com formato e DV válidos. Consulta on-line à SEFAZ não configurada (exige certificado digital).',
            'detalhes' => ['tipo' => 'validacao_local_apenas'],
        ];
    }
}
