<?php

/**
 * Desfaz importação confirmada (financeiro + estoque + produtos criados pelo plugin) ou remove rascunho.
 *
 * @copyright 2024-2026 LivreOS
 * @license   https://www.gnu.org/licenses/agpl-3.0.txt AGPL-3.0
 */
namespace Compras\Services;

use App\Models\ContaPagar;
use App\Models\Produto;
use Compras\Models\CompraImportacao;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class CompraImportacaoDesfazerService
{
    /**
     * @return array{ok: bool, mensagem: string}
     */
    public function excluirRascunho(CompraImportacao $importacao): array
    {
        if ($importacao->status === 'confirmada') {
            return ['ok' => false, 'mensagem' => 'Importação confirmada: use “Desfazer entrada” em vez de excluir rascunho.'];
        }

        DB::transaction(function () use ($importacao) {
            $this->apagarArquivoArmazenado($importacao);
            $importacao->itens()->delete();
            $importacao->auditLogs()->delete();
            $importacao->delete();
        });

        return ['ok' => true, 'mensagem' => 'Importação excluída.'];
    }

    /**
     * @param  array{reverter_estoque?: bool, excluir_produtos_plugin?: bool}  $opcoes
     * @return array{ok: bool, mensagem: string}
     */
    public function desfazerConfirmada(CompraImportacao $importacao, array $opcoes, int $userId): array
    {
        if ($importacao->status !== 'confirmada') {
            return ['ok' => false, 'mensagem' => 'Só é possível desfazer importações já confirmadas.'];
        }

        $reverterEstoque = (bool) ($opcoes['reverter_estoque'] ?? true);
        $excluirProdutosPlugin = (bool) ($opcoes['excluir_produtos_plugin'] ?? true);

        $snapshot = $importacao->undo_snapshot;
        if (! is_array($snapshot)) {
            $snapshot = [];
        }

        $contaIds = $snapshot['conta_pagar_ids'] ?? null;
        if (! is_array($contaIds) || $contaIds === []) {
            $contaIds = ContaPagar::query()
                ->where('entidade_tipo', 'compra_importacao')
                ->where('entidade_id', $importacao->id)
                ->pluck('id')
                ->all();
        }

        if ($contaIds !== []) {
            $contas = ContaPagar::query()->whereIn('id', $contaIds)->get();
            foreach ($contas as $conta) {
                if ((float) ($conta->valor_pago ?? 0) > 0.0001) {
                    return ['ok' => false, 'mensagem' => 'Existem parcelas com pagamento registrado. Estorne ou ajuste no financeiro antes de desfazer.'];
                }
                if (! in_array($conta->status, ['aberto', 'vencido'], true)) {
                    return ['ok' => false, 'mensagem' => 'Existem parcelas em status '.$conta->status.' (só é possível remover títulos em aberto ou vencidos sem pagamento).'];
                }
            }
        }

        DB::transaction(function () use ($importacao, $reverterEstoque, $excluirProdutosPlugin, $contaIds, $snapshot, $userId) {
            if ($contaIds !== []) {
                $contas = ContaPagar::query()->with('anexos')->whereIn('id', $contaIds)->lockForUpdate()->get();
                foreach ($contas as $conta) {
                    foreach ($conta->anexos as $anexo) {
                        if ($anexo->caminho_arquivo && Storage::disk('public')->exists($anexo->caminho_arquivo)) {
                            Storage::disk('public')->delete($anexo->caminho_arquivo);
                        }
                        $anexo->delete();
                    }
                    $conta->delete();
                }
            }

            if ($reverterEstoque && ! empty($snapshot['estoque']) && is_array($snapshot['estoque'])) {
                foreach ($snapshot['estoque'] as $row) {
                    $pid = (int) ($row['produto_id'] ?? 0);
                    if ($pid <= 0) {
                        continue;
                    }
                    $produto = Produto::lockForUpdate()->find($pid);
                    if (! $produto) {
                        continue;
                    }
                    $produto->estoque_quantidade = $row['q_antes'];
                    $produto->estoque_preco_custo_unitario = $row['custo_medio_antes'];
                    $produto->estoque_preco_compra_unitario = $row['preco_compra_antes'];
                    $produto->updated_by = $userId;
                    $produto->save();
                }
            }

            if ($excluirProdutosPlugin && ! empty($snapshot['produtos_criados_plugin_ids']) && is_array($snapshot['produtos_criados_plugin_ids'])) {
                foreach ($snapshot['produtos_criados_plugin_ids'] as $pid) {
                    $pid = (int) $pid;
                    if ($pid <= 0) {
                        continue;
                    }
                    $p = Produto::find($pid);
                    if (! $p) {
                        continue;
                    }
                    $sku = (string) ($p->codigo_sku ?? '');
                    if (! str_starts_with($sku, 'NF-IMP'.$importacao->id.'-')) {
                        continue;
                    }
                    $p->delete();
                }
            }

            $this->apagarArquivoArmazenado($importacao);

            $importacao->itens()->delete();
            $importacao->auditLogs()->delete();
            $importacao->delete();
        });

        return ['ok' => true, 'mensagem' => 'Importação desfeita e registro removido.'];
    }

    private function apagarArquivoArmazenado(CompraImportacao $importacao): void
    {
        $rel = $importacao->arquivo_original_path;
        if ($rel !== null && $rel !== '' && Storage::disk('local')->exists($rel)) {
            Storage::disk('local')->delete($rel);
        }
    }
}
