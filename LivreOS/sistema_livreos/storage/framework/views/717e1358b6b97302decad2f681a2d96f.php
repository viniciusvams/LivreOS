



<?php $__env->startSection('content'); ?>
<?php
    $badgeColors = ['rascunho'=>'gray','confirmado'=>'sky','faturado'=>'amber','entregue'=>'green','cancelado'=>'red'];
    $cor = $badgeColors[$pedido->status] ?? 'gray';
    $itensParcialCancel = [];
    foreach ($pedido->itens as $_it) {
        $_disp = max(0, (float) $_it->quantidade - (float) ($_it->quantidade_cancelada ?? 0));
        if ($_disp > 0 && empty($_it->os_gerada_id)) {
            $itensParcialCancel[] = ['item' => $_it, 'disponivel' => $_disp];
        }
    }
?>
<div x-data="pedidoShow()" x-init="init()">


<div class="mb-6 flex flex-wrap items-start justify-between gap-3">
    <div class="flex items-center gap-3">
        <a href="<?php echo e(route('pedidos-venda.index')); ?>" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
        </a>
        <div>
            <div class="flex items-center gap-2">
                <h1 class="text-2xl font-semibold text-gray-800 dark:text-white/90"><?php echo e($pedido->numero_sequencial); ?></h1>
                <span class="inline-flex rounded-full px-2 py-0.5 text-xs font-semibold
                    <?php if($cor==='gray'): ?> bg-gray-100 text-gray-700 dark:bg-gray-700 dark:text-gray-300
                    <?php elseif($cor==='sky'): ?> bg-sky-100 text-sky-700 dark:bg-sky-500/20 dark:text-sky-300
                    <?php elseif($cor==='amber'): ?> bg-amber-100 text-amber-700 dark:bg-amber-500/20 dark:text-amber-300
                    <?php elseif($cor==='green'): ?> bg-green-100 text-green-700 dark:bg-green-500/20 dark:text-green-300
                    <?php elseif($cor==='red'): ?> bg-red-100 text-red-700 dark:bg-red-500/20 dark:text-red-300
                    <?php endif; ?>"><?php echo e(ucfirst($pedido->status)); ?></span>
            </div>
            <p class="text-sm text-gray-500 dark:text-gray-400"><?php echo e($pedido->cliente?->nome); ?> · <?php echo e($pedido->data_pedido?->format('d/m/Y')); ?></p>
        </div>
    </div>

    
    <div class="flex flex-wrap gap-2">
        <?php if($pedido->podeEditar()): ?>
        <a href="<?php echo e(route('pedidos-venda.edit', $pedido)); ?>" class="rounded-lg border border-gray-300 px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-700 dark:text-gray-300">Editar</a>
        <?php endif; ?>

        <?php if($pedido->podeConfirmar()): ?>
        <form method="POST" action="<?php echo e(route('pedidos-venda.confirmar', $pedido)); ?>" class="inline">
            <?php echo csrf_field(); ?>
            <button type="submit" class="rounded-lg bg-sky-500 px-4 py-2 text-sm font-medium text-white hover:bg-sky-600"
                    onclick="return confirm('Confirmar este pedido?')">Confirmar</button>
        </form>
        <?php endif; ?>

        <?php if($pedido->podeFaturar()): ?>
        <button type="button" @click="abrirModalFaturar()"
                <?php if($pedido->cliente && $pedido->cliente->bloqueado_vendas): ?> disabled title="Cliente bloqueado para vendas" <?php endif; ?>
                class="rounded-lg bg-amber-500 px-4 py-2 text-sm font-medium text-white hover:bg-amber-600 disabled:cursor-not-allowed disabled:opacity-50">Faturar</button>
        <?php endif; ?>

        <?php if($pedido->podeEntregar()): ?>
        <form method="POST" action="<?php echo e(route('pedidos-venda.entregar', $pedido)); ?>" class="inline">
            <?php echo csrf_field(); ?>
            <button type="submit" class="rounded-lg bg-green-500 px-4 py-2 text-sm font-medium text-white hover:bg-green-600"
                    onclick="return confirm('Marcar como entregue?')">Marcar como Entregue</button>
        </form>
        <?php endif; ?>

        <?php
            $bloqueioCrPendente = $bloqueioCancelamentoContaReceberPendente ?? false;
            $podeExibirBotaoCancelar = $pedido->podeCancelar()
                && ! $bloqueioCrPendente
                && (
                    ($podeCancelarTotal ?? false)
                    || (($podeCancelarParcial ?? false) && count($itensParcialCancel ?? []) > 0)
                );
        ?>
        <?php if($podeExibirBotaoCancelar): ?>
        <button type="button" @click="abrirModalCancelar()"
                class="rounded-lg border border-red-300 px-4 py-2 text-sm font-medium text-red-600 hover:bg-red-50 dark:border-red-700 dark:text-red-400">Cancelar</button>
        <?php endif; ?>

        <form method="POST" action="<?php echo e(route('pedidos-venda.duplicar', $pedido)); ?>" class="inline">
            <?php echo csrf_field(); ?>
            <button type="submit" class="rounded-lg border border-gray-300 px-4 py-2 text-sm font-medium text-gray-600 hover:bg-gray-50 dark:border-gray-700 dark:text-gray-300"
                    onclick="return confirm('Criar duplicata deste pedido?')">Duplicar</button>
        </form>

        <a href="<?php echo e(route('pedidos-venda.pdf', $pedido)); ?>?v=<?php echo e($pedido->updated_at?->timestamp ?? $pedido->id); ?>" target="_blank"
           class="rounded-lg border border-gray-300 px-4 py-2 text-sm font-medium text-gray-600 hover:bg-gray-50 dark:border-gray-700 dark:text-gray-300">PDF</a>

        <?php if($pedido->isRascunho()): ?>
        <form method="POST" action="<?php echo e(route('pedidos-venda.destroy', $pedido)); ?>" class="inline"
              onsubmit="return confirm('Excluir este pedido?')">
            <?php echo csrf_field(); ?> <?php echo method_field('DELETE'); ?>
            <button type="submit" class="rounded-lg border border-red-300 px-4 py-2 text-sm font-medium text-red-600 hover:bg-red-50 dark:border-red-700 dark:text-red-400">Excluir</button>
        </form>
        <?php endif; ?>
    </div>
</div>

<?php if(session('success')): ?>
<div class="mb-4 rounded-lg bg-success-100 px-4 py-3 text-sm text-success-700 dark:bg-success-500/20 dark:text-success-400"><?php echo e(session('success')); ?></div>
<?php endif; ?>
<?php if(session('error')): ?>
<div class="mb-4 rounded-lg bg-red-100 px-4 py-3 text-sm text-red-700 dark:bg-red-500/20 dark:text-red-400"><?php echo e(session('error')); ?></div>
<?php endif; ?>
<?php if($errors->any()): ?>
<div class="mb-4 rounded-lg bg-red-100 px-4 py-3 text-sm text-red-700 dark:bg-red-500/20 dark:text-red-400">
    <p class="font-medium">Não foi possível concluir a ação:</p>
    <ul class="mt-1 list-inside list-disc">
        <?php $__currentLoopData = $errors->all(); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $err): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
        <li><?php echo e($err); ?></li>
        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
    </ul>
</div>
<?php endif; ?>

<?php if(($bloqueioCancelamentoContaReceberPendente ?? false) && ($contasReceberPendentesDoPedido ?? collect())->isNotEmpty()): ?>
<div class="mb-4 rounded-lg border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-900 dark:border-amber-500/40 dark:bg-amber-500/10 dark:text-amber-200">
    <p class="font-semibold">Cancelamento do pedido indisponível</p>
    <p class="mt-1">Existem <strong>contas a receber em aberto</strong> (não quitadas) ligadas a este pedido — por exemplo boleto, TED ou fiado ainda não recebido. Enquanto houver valor pendente no financeiro, o cancelamento fica bloqueado até você baixar, cancelar ou regularizar esses títulos.</p>
    <p class="mt-2">Quando o recebimento já estiver <strong>pago</strong>, o cancelamento do pedido gera um <strong>novo lançamento em Contas a pagar</strong> (estorno da venda), sem estornar automaticamente o recebimento no caixa.</p>
    <p class="mt-2"><strong>Títulos pendentes:</strong></p>
    <ul class="mt-1 list-inside list-disc text-xs">
        <?php $__currentLoopData = $contasReceberPendentesDoPedido; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $crp): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
        <li>
            <a href="<?php echo e(route('financeiro.contas-receber.edit', $crp)); ?>" class="font-medium underline hover:no-underline">CR #<?php echo e($crp->id); ?></a>
            — <?php echo e(ucfirst($crp->status)); ?> — R$ <?php echo e(number_format((float) $crp->valor, 2, ',', '.')); ?>

            <?php if($crp->descricao): ?><span class="text-amber-800/80 dark:text-amber-200/80"> — <?php echo e(\Illuminate\Support\Str::limit($crp->descricao, 60)); ?></span><?php endif; ?>
        </li>
        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
    </ul>
    <p class="mt-2"><a href="<?php echo e(route('financeiro.contas-receber.index')); ?>" class="font-medium text-amber-800 underline hover:no-underline dark:text-amber-100">Abrir Contas a receber →</a></p>
</div>
<?php endif; ?>

<div class="grid grid-cols-1 gap-5 lg:grid-cols-3">

    
    <div class="lg:col-span-2 space-y-5">

        
        <div class="rounded-lg border border-gray-200 bg-white p-5 shadow-theme-sm dark:border-gray-800 dark:bg-gray-900">
            <h2 class="mb-3 text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Dados do Pedido</h2>
            <dl class="grid grid-cols-2 gap-x-4 gap-y-2 text-sm sm:grid-cols-3">
                <div><dt class="text-gray-500">Cliente</dt><dd class="font-medium text-gray-800 dark:text-white/90"><?php echo e($pedido->cliente?->nome ?? '—'); ?></dd></div>
                <div><dt class="text-gray-500">Canal</dt><dd class="text-gray-700 dark:text-gray-300"><?php echo e($pedido->canal_venda ? ucfirst($pedido->canal_venda) : '—'); ?></dd></div>
                <div><dt class="text-gray-500">Vendedor</dt><dd class="text-gray-700 dark:text-gray-300"><?php echo e($pedido->vendedor?->name ?? '—'); ?></dd></div>
                <div><dt class="text-gray-500">Data pedido</dt><dd class="text-gray-700 dark:text-gray-300"><?php echo e($pedido->data_pedido?->format('d/m/Y')); ?></dd></div>
                <div><dt class="text-gray-500">Entrega prevista</dt><dd class="text-gray-700 dark:text-gray-300"><?php echo e($pedido->data_prevista_entrega?->format('d/m/Y') ?? '—'); ?></dd></div>
                <?php
                    $vdShow = $pedido->validadeOrcamentoDiasEfetiva();
                    $dtLimShow = $pedido->dataValidadeOrcamentoReferencia();
                ?>
                <?php if($vdShow): ?>
                <div class="col-span-2 sm:col-span-3">
                    <dt class="text-gray-500">Validade do orçamento</dt>
                    <dd class="text-gray-700 dark:text-gray-300">
                        <?php echo e($vdShow); ?> dia(s) corrido(s) a partir da data do pedido
                        <?php if($dtLimShow): ?>
                            <span class="text-gray-500">·</span> referência: válido até <strong class="text-gray-800 dark:text-white/90"><?php echo e($dtLimShow->format('d/m/Y')); ?></strong>
                        <?php endif; ?>
                        <?php if($pedido->validade_orcamento_dias): ?>
                            <span class="block mt-0.5 text-xs text-gray-500 dark:text-gray-400">Validade específica desta proposta (não usa só o padrão da empresa).</span>
                        <?php endif; ?>
                    </dd>
                </div>
                <?php endif; ?>
                <?php if($pedido->data_faturamento): ?>
                <div><dt class="text-gray-500">Faturado em</dt><dd class="text-gray-700 dark:text-gray-300"><?php echo e($pedido->data_faturamento?->format('d/m/Y H:i')); ?></dd></div>
                <?php endif; ?>
                <?php if($pedido->data_entrega): ?>
                <div><dt class="text-gray-500">Entregue em</dt><dd class="text-green-600 dark:text-green-400 font-medium"><?php echo e($pedido->data_entrega?->format('d/m/Y H:i')); ?></dd></div>
                <?php endif; ?>
                <?php if(filled($pedido->observacoes)): ?>
                <div class="col-span-3">
                    <dt class="text-gray-500">Observações</dt>
                    <dd class="mt-1 text-sm text-gray-700 dark:text-gray-300 [&_a]:text-brand-600 [&_ul]:list-disc [&_ul]:pl-5 [&_ol]:list-decimal [&_ol]:pl-5 [&_p]:my-1"><?php echo $pedido->observacoes; ?></dd>
                </div>
                <?php endif; ?>
                <?php if(filled($pedido->observacoes_internas)): ?>
                <div class="col-span-3">
                    <dt class="text-gray-500">Observações internas</dt>
                    <dd class="mt-1 text-sm text-gray-600 dark:text-gray-400 [&_a]:text-brand-600 [&_ul]:list-disc [&_ul]:pl-5 [&_ol]:list-decimal [&_ol]:pl-5 [&_p]:my-1"><?php echo $pedido->observacoes_internas; ?></dd>
                </div>
                <?php endif; ?>
            </dl>

            <?php
                $clientePv = $pedido->cliente;
                $basePvCliente = $pedido->unidade ?? $clientePv;
                $pvContatos = $basePvCliente?->contatos ?? collect();
                $pvContatoPreferencial = $pedido->contato
                    ?? $pvContatos->firstWhere('principal', true)
                    ?? $pvContatos->sortBy('id')->first();
                $pvEndereco = $pedido->endereco
                    ?? $basePvCliente?->enderecos?->firstWhere('principal', true);
                $pvEnderecoTexto = $pvEndereco
                    ? trim(($pvEndereco->logradouro ?? '') . ' ' . ($pvEndereco->numero ?? '') . ' ' . ($pvEndereco->complemento ? '- ' . $pvEndereco->complemento : '') . ', ' . ($pvEndereco->bairro ?? '') . ' - ' . ($pvEndereco->cidade ?? '') . '/' . ($pvEndereco->estado ?? ''))
                    : '-';
                $pvWhatsappContato = $basePvCliente
                    ? $basePvCliente->contatos
                        ->where('telefone_whatsapp', true)
                        ->sortByDesc(fn($c) => (int) $c->principal)
                        ->first()
                    : null;
                $pvTelefone = $pvContatoPreferencial->telefone ?? '';
                $pvTelefoneDigits = $pvTelefone ? preg_replace('/\D+/', '', $pvTelefone) : '';
                $pvWhatsapp = $pvWhatsappContato->telefone ?? '';
                $pvWhatsappDigits = $pvWhatsapp ? preg_replace('/\D+/', '', $pvWhatsapp) : '';
                $pvMapsHref = ($pvEnderecoTexto && $pvEnderecoTexto !== '-') ? ('https://www.google.com/maps/search/?api=1&query=' . urlencode($pvEnderecoTexto)) : '#';
                $pvTelHref = $pvTelefoneDigits ? ('tel:' . $pvTelefoneDigits) : '#';
                $pvWaHref = $pvWhatsappDigits ? ('https://wa.me/' . $pvWhatsappDigits) : '#';
            ?>
            <div class="mt-4 rounded-lg border border-gray-200 bg-gray-50 p-4 text-sm text-gray-700 dark:border-gray-700 dark:bg-gray-800/40 dark:text-gray-300">
                <p class="mb-2 text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Dados do cliente</p>
                <div class="grid grid-cols-1 gap-2 md:grid-cols-2">
                    <div><span class="font-medium">Cliente:</span> <?php echo e($clientePv?->nome ?? '—'); ?></div>
                    <div>
                        <span class="font-medium">Endereço:</span>
                        <a href="<?php echo e($pvMapsHref); ?>" target="_blank" rel="noopener noreferrer" class="text-brand-600 hover:underline dark:text-brand-400">
                            <?php echo e($pvEnderecoTexto); ?>

                        </a>
                    </div>
                    <div>
                        <span class="font-medium">Telefone:</span>
                        <a href="<?php echo e($pvTelHref); ?>" class="text-brand-600 hover:underline dark:text-brand-400">
                            <?php echo e($pvContatoPreferencial->telefone ?? '—'); ?>

                        </a>
                    </div>
                    <div><span class="font-medium">Contato:</span> <?php echo e($pvContatoPreferencial->nome ?? '—'); ?></div>
                    <div><span class="font-medium">E-mail:</span> <?php echo e($pvContatoPreferencial->email ?? '—'); ?></div>
                    <div>
                        <span class="font-medium">WhatsApp:</span>
                        <a href="<?php echo e($pvWaHref); ?>" target="_blank" rel="noopener noreferrer" class="text-brand-600 hover:underline dark:text-brand-400">
                            <?php echo e($pvWhatsappContato?->telefone ?? '—'); ?>

                        </a>
                    </div>
                </div>
            </div>
        </div>

        
        <div class="overflow-hidden rounded-lg border border-gray-200 bg-white shadow-theme-sm dark:border-gray-800 dark:bg-gray-900">
            <div class="border-b border-gray-200 px-5 py-3 dark:border-gray-800">
                <h2 class="text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Itens</h2>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead class="bg-gray-50 text-xs uppercase text-gray-500 dark:bg-gray-800/50 dark:text-gray-400">
                        <tr>
                            <th class="px-4 py-2 text-left">Tipo</th>
                            <th class="px-4 py-2 text-left">Descrição</th>
                            <th class="px-4 py-2 text-left">Depósito</th>
                            <th class="px-4 py-2 text-left">Localização</th>
                            <th class="px-4 py-2 text-right">Estoque</th>
                            <th class="px-4 py-2 text-right">Qtd</th>
                            <th class="px-4 py-2 text-right">Unit.</th>
                            <th class="px-4 py-2 text-right">Desc.</th>
                            <th class="px-4 py-2 text-right">Total</th>
                            <th class="px-4 py-2 text-center">OS</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                        <?php $__empty_1 = true; $__currentLoopData = $pedido->itens; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $item): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                        <tr>
                            <td class="px-4 py-2">
                                <span class="rounded-full px-2 py-0.5 text-[10px] font-semibold <?php echo e($item->tipo === 'servico' ? 'bg-purple-100 text-purple-700 dark:bg-purple-500/20 dark:text-purple-300' : 'bg-brand-100 text-brand-700 dark:bg-brand-500/20 dark:text-brand-300'); ?>">
                                    <?php echo e($item->tipo === 'servico' ? 'Serviço' : 'Produto'); ?>

                                </span>
                            </td>
                            <td class="px-4 py-2 text-gray-800 dark:text-gray-200">
                                <?php echo e($item->descricao); ?>

                                <?php if($item->os_gerada_id): ?>
                                    <a href="<?php echo e(route('ordens-servico.show', $item->os_gerada_id)); ?>" class="ml-1 text-xs text-sky-500 hover:underline">→ OS</a>
                                <?php endif; ?>
                            </td>
                            <td class="px-4 py-2 text-xs text-gray-600 dark:text-gray-400">
                                <?php if($item->tipo === 'produto' && $item->produto?->deposito): ?>
                                    <?php echo e($item->produto->deposito->nome); ?>

                                <?php else: ?>
                                    <span class="text-gray-400">—</span>
                                <?php endif; ?>
                            </td>
                            <td class="px-4 py-2 text-xs text-gray-600 dark:text-gray-400">
                                <?php if($item->tipo === 'produto' && $item->produto?->estoque_localizacao): ?>
                                    <?php echo e($item->produto->estoque_localizacao); ?>

                                <?php else: ?>
                                    <span class="text-gray-400">—</span>
                                <?php endif; ?>
                            </td>
                            <td class="px-4 py-2 text-right text-xs text-gray-600 dark:text-gray-400">
                                <?php if($item->tipo === 'produto' && $item->produto && $item->produto->estoque_quantidade !== null): ?>
                                    <?php echo e(format_quantidade_br($item->produto->estoque_quantidade)); ?>

                                <?php else: ?>
                                    <span class="text-gray-400">—</span>
                                <?php endif; ?>
                            </td>
                            <td class="px-4 py-2 text-right text-gray-600"><?php echo e(format_quantidade_br($item->quantidade)); ?></td>
                            <td class="px-4 py-2 text-right text-gray-600">R$ <?php echo e(format_br_decimal($item->preco_unitario)); ?></td>
                            <td class="px-4 py-2 text-right text-red-500"><?php echo e($item->desconto > 0 ? '- R$ ' . format_br_decimal($item->desconto) : '—'); ?></td>
                            <td class="px-4 py-2 text-right font-medium text-gray-800 dark:text-gray-200">R$ <?php echo e(format_br_decimal($item->total)); ?></td>
                            <td class="px-4 py-2 text-center">
                                <?php if($item->tipo === 'servico' && $item->gerar_os): ?>
                                    <span class="text-xs text-green-600 dark:text-green-400">✓</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                        <tr><td colspan="10" class="px-4 py-6 text-center text-sm text-gray-400">Sem itens.</td></tr>
                        <?php endif; ?>
                    </tbody>
                    <tfoot class="bg-gray-50 dark:bg-gray-800/50">
                        <tr>
                            <td colspan="8" class="px-4 py-2 text-right text-sm text-gray-500">Subtotal produtos</td>
                            <td class="px-4 py-2 text-right text-sm">R$ <?php echo e(format_br_decimal($pedido->total_produtos)); ?></td>
                            <td></td>
                        </tr>
                        <tr>
                            <td colspan="8" class="px-4 py-2 text-right text-sm text-gray-500">Subtotal serviços</td>
                            <td class="px-4 py-2 text-right text-sm">R$ <?php echo e(format_br_decimal($pedido->total_servicos)); ?></td>
                            <td></td>
                        </tr>
                        <tr>
                            <td colspan="8" class="px-4 py-2 text-right text-sm text-red-500">Descontos</td>
                            <td class="px-4 py-2 text-right text-sm text-red-500">- R$ <?php echo e(format_br_decimal($pedido->total_descontos)); ?></td>
                            <td></td>
                        </tr>
                        <tr class="font-semibold">
                            <td colspan="8" class="px-4 py-2.5 text-right text-base">Total Geral</td>
                            <td class="px-4 py-2.5 text-right text-base text-brand-600 dark:text-brand-400">R$ <?php echo e(format_br_decimal($pedido->total_geral)); ?></td>
                            <td></td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>

        
        <?php if($pedido->contasReceber->count() > 0): ?>
        <div class="overflow-hidden rounded-lg border border-gray-200 bg-white shadow-theme-sm dark:border-gray-800 dark:bg-gray-900">
            <div class="border-b border-gray-200 px-5 py-3 dark:border-gray-800">
                <h2 class="text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Financeiro Gerado</h2>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead class="bg-gray-50 text-xs uppercase text-gray-500 dark:bg-gray-800/50 dark:text-gray-400">
                        <tr>
                            <th class="px-4 py-2 text-left">Descrição</th>
                            <th class="px-4 py-2 text-right">Valor</th>
                            <th class="px-4 py-2 text-center">Vencimento</th>
                            <th class="px-4 py-2 text-center">Status</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                        <?php $__currentLoopData = $pedido->contasReceber; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $cr): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                        <tr>
                            <td class="px-4 py-2 text-gray-700 dark:text-gray-300"><?php echo e($cr->descricao); ?></td>
                            <td class="px-4 py-2 text-right font-medium">R$ <?php echo e(number_format($cr->valor, 2, ',', '.')); ?></td>
                            <td class="px-4 py-2 text-center text-gray-500"><?php echo e($cr->data_vencimento?->format('d/m/Y')); ?></td>
                            <td class="px-4 py-2 text-center">
                                <span class="rounded-full px-2 py-0.5 text-[10px] font-semibold <?php echo e($cr->status === 'pago' ? 'bg-green-100 text-green-700' : 'bg-amber-100 text-amber-700'); ?>">
                                    <?php echo e(ucfirst($cr->status)); ?>

                                </span>
                            </td>
                        </tr>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                    </tbody>
                </table>
            </div>
        </div>
        <?php endif; ?>

    </div>

    
    <div class="space-y-5">

        
        <div class="rounded-lg border border-gray-200 bg-white p-5 shadow-theme-sm dark:border-gray-800 dark:bg-gray-900">
            <h2 class="mb-3 text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Resumo</h2>
            <div class="space-y-2 text-sm">
                <div class="flex justify-between"><span class="text-gray-500">Itens</span><span><?php echo e($pedido->itens->count()); ?></span></div>
                <div class="flex justify-between"><span class="text-gray-500">Produtos</span><span>R$ <?php echo e(number_format($pedido->total_produtos, 2, ',', '.')); ?></span></div>
                <div class="flex justify-between"><span class="text-gray-500">Serviços</span><span>R$ <?php echo e(number_format($pedido->total_servicos, 2, ',', '.')); ?></span></div>
                <div class="flex justify-between text-red-500"><span>Descontos</span><span>- R$ <?php echo e(number_format($pedido->total_descontos, 2, ',', '.')); ?></span></div>
                <div class="flex justify-between border-t border-gray-200 pt-2 font-semibold text-base dark:border-gray-700">
                    <span>Total Geral</span>
                    <span class="text-brand-600 dark:text-brand-400">R$ <?php echo e(number_format($pedido->total_geral, 2, ',', '.')); ?></span>
                </div>
            </div>
        </div>

        
        <div class="rounded-lg border border-gray-200 bg-white p-5 shadow-theme-sm dark:border-gray-800 dark:bg-gray-900">
            <h2 class="mb-3 text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Histórico</h2>
            <ol class="space-y-3">
                <?php $__currentLoopData = $pedido->logs; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $log): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                <li class="flex items-start gap-3 text-sm">
                    <div class="mt-0.5 h-2 w-2 flex-shrink-0 rounded-full bg-brand-400"></div>
                    <div class="min-w-0">
                        <p class="font-medium text-gray-700 dark:text-gray-300"><?php echo e($log->descricao ?? ucfirst($log->acao)); ?></p>
                        <p class="text-xs text-gray-400"><?php echo e($log->user?->name ?? 'Sistema'); ?> · <?php echo e($log->created_at?->format('d/m/Y H:i')); ?></p>
                    </div>
                </li>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
            </ol>
        </div>

    </div>
</div>

<?php echo $__env->make('pedidos-venda._modal-faturar', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>


<div x-show="showCancelar" class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4"
     style="display:none">
    <div @click.outside="showCancelar = false" class="w-full max-w-lg max-h-[90vh] overflow-y-auto rounded-xl bg-white shadow-2xl dark:bg-gray-900">
        <div class="px-5 py-4">
            <h2 class="mb-3 text-lg font-semibold text-gray-800 dark:text-white/90">Cancelar pedido</h2>
            <?php if($pedido->podeCancelarPosFaturamento()): ?>
                <p class="mb-3 text-xs text-amber-700 dark:text-amber-300">Pedido faturado: será gerado um <strong>novo lançamento em Contas a pagar</strong> (estorno da venda) e devolução ao estoque nos produtos. O recebimento já registrado <strong>não é estornado</strong> automaticamente. Só é possível cancelar se <strong>não</strong> houver conta a receber em aberto (boleto/TED/fiado não quitado).</p>
            <?php endif; ?>
            <form x-ref="formCancelar" method="POST" action="<?php echo e(route('pedidos-venda.cancelar', $pedido)); ?>" @submit="validarSubmitCancelar($event)">
                <?php echo csrf_field(); ?>
                <div class="mb-4 space-y-2">
                    <?php if(($podeCancelarTotal ?? false) && ($podeCancelarParcial ?? false) && count($itensParcialCancel) > 0): ?>
                        <p class="text-xs font-medium text-gray-500 dark:text-gray-400">Tipo</p>
                        <label class="flex items-center gap-2 text-sm text-gray-700 dark:text-gray-300">
                            <input type="radio" name="tipo" value="total" x-model="cancelTipo" class="rounded border-gray-300">
                            Cancelar pedido inteiro (restante)
                        </label>
                        <label class="flex items-center gap-2 text-sm text-gray-700 dark:text-gray-300">
                            <input type="radio" name="tipo" value="parcial" x-model="cancelTipo" class="rounded border-gray-300">
                            Cancelar só alguns itens (parcial)
                        </label>
                    <?php elseif(($podeCancelarParcial ?? false) && !($podeCancelarTotal ?? false) && count($itensParcialCancel) > 0): ?>
                        <input type="hidden" name="tipo" value="parcial">
                        <p class="text-xs text-gray-500 dark:text-gray-400">Cancelamento parcial permitido para seu usuário.</p>
                    <?php else: ?>
                        <input type="hidden" name="tipo" value="total">
                    <?php endif; ?>
                </div>

                <div class="mb-4 space-y-2" x-show="cancelTipo === 'parcial' && <?php echo json_encode(count($itensParcialCancel) > 0 && (($podeCancelarParcial ?? false)), 15, 512) ?>">
                    <p class="text-xs font-medium text-gray-500 dark:text-gray-400">Quantidades a cancelar</p>
                    <?php $fi = 0; ?>
                    <?php $__currentLoopData = $itensParcialCancel; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $row): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                        <?php $it = $row['item']; $disp = $row['disponivel']; ?>
                        <div class="flex flex-col gap-1 rounded border border-gray-100 p-2 dark:border-gray-700">
                            <span class="text-sm text-gray-800 dark:text-gray-200"><?php echo e($it->descricao); ?></span>
                            <span class="text-xs text-gray-500">Disponível: <?php echo e(format_quantidade_br($disp)); ?> <?php if($it->tipo === 'servico'): ?>(serviço)<?php endif; ?></span>
                            <input type="hidden" name="itens[<?php echo e($fi); ?>][item_id]" value="<?php echo e($it->id); ?>">
                            <input type="text" name="itens[<?php echo e($fi); ?>][quantidade_cancelar]" value=""
                                   class="cancel-qtd w-full rounded border border-gray-300 px-2 py-1 text-sm dark:border-gray-600 dark:bg-gray-800 dark:text-white"
                                   placeholder="Qtd (máx. <?php echo e($disp); ?>)" autocomplete="off">
                        </div>
                        <?php $fi++; ?>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                </div>

                <div class="mb-4">
                    <label class="mb-1 block text-sm font-medium text-gray-600 dark:text-gray-400">Motivo do cancelamento <span class="text-red-500">*</span></label>
                    <textarea name="motivo" required rows="3" minlength="3" class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm dark:border-gray-700 dark:bg-gray-800 dark:text-white/90" placeholder="Obrigatório"></textarea>
                </div>
                <div class="flex justify-end gap-3">
                    <button type="button" @click="showCancelar = false"
                            class="rounded-lg border border-gray-300 px-4 py-2 text-sm font-medium text-gray-700 dark:border-gray-700 dark:text-gray-300">Voltar</button>
                    <button type="submit"
                            class="rounded-lg bg-red-500 px-5 py-2 text-sm font-medium text-white hover:bg-red-600">Confirmar</button>
                </div>
            </form>
        </div>
    </div>
</div>

</div>

<?php $__env->startPush('scripts'); ?>
<script>
function pedidoShow() {
    const formasPagamento = <?php echo json_encode($formasPagamentoModal, 15, 512) ?>;
    const bandeirasPagamento = <?php echo json_encode($bandeirasPagamento ?? [], 15, 512) ?>;
    const usuarioSujeitoLimite = <?php echo json_encode($usuarioSujeitoALimiteDesconto ?? false, 15, 512) ?>;
    const limiteValorCfg = <?php echo json_encode(isset($limiteDesconto) && $limiteDesconto->limite_valor !== null && $limiteDesconto->limite_valor !== '' ? (float) $limiteDesconto->limite_valor : null, 15, 512) ?>;
    const limitePercentualCfg = <?php echo json_encode(isset($limiteDesconto) && $limiteDesconto->limite_percentual !== null && $limiteDesconto->limite_percentual !== '' ? (float) $limiteDesconto->limite_percentual : null, 15, 512) ?>;
    const limiteCreditoCliente = <?php echo json_encode($pedido->cliente && $pedido->cliente->limite_credito !== null && $pedido->cliente->limite_credito !== '' ? (float) $pedido->cliente->limite_credito : null, 15, 512) ?>;
    const clienteBloqueadoVendas = <?php echo json_encode($pedido->cliente ? (bool) $pedido->cliente->bloqueado_vendas : false, 15, 512) ?>;
    const defaultCancelTipo = <?php echo json_encode((($podeCancelarParcial ?? false) && !($podeCancelarTotal ?? false)) ? 'parcial' : 'total', 15, 512) ?>;

    return {
        showFaturar: false,
        showCancelar: false,
        cancelTipo: defaultCancelTipo,

        formasPagamento,
        bandeirasPagamento,
        totalPedidoBase: <?php echo e(json_encode((float) $pedido->total_geral)); ?>,

        tipoPagamento: 'agora',
        descontoTipoPagamento: 'valor',
        descontoInputFaturar: '',
        descontoAplicadoReais: 0,

        pagamentoFormaSelecionada: null,
        /** Objeto da forma no painel (Alpine reativo; evita x-if aninhado com getFormaFaturarById). */
        formaFaturarDetalhe: null,
        pagamentoValorAtualFaturar: '0,00',
        pagamentoAdquirenteIdFaturar: '',
        pagamentoBandeiraFaturar: '',
        pagamentoPixChaveIdFaturar: '',
        pagamentoParcelasFaturar: 1,
        pagamentoPixParcelaVencimentosFaturar: [],
        pagamentosInseridos: [],

        usuarioSujeitoLimite,
        limiteValorDesconto: limiteValorCfg,
        limitePercentualDesconto: limitePercentualCfg,
        limiteCreditoCliente,
        clienteBloqueadoVendas,

        init() {
            this.$watch('pagamentoParcelasFaturar', () => this.syncPixParcelasVencFaturar());
            this.$watch('pagamentoFormaSelecionada', () => this.syncPixParcelasVencFaturar());
            this.$watch('formaFaturarDetalhe', () => this.syncPixParcelasVencFaturar());
        },

        addDaysYmdFaturar(ymd, days) {
            const parts = String(ymd || '').split('-').map(Number);
            if (parts.length < 3 || isNaN(parts[0])) return String(ymd || '').slice(0, 10);
            const dt = new Date(parts[0], parts[1] - 1, parts[2]);
            dt.setDate(dt.getDate() + (parseInt(days, 10) || 0));
            const p = (n) => String(n).padStart(2, '0');
            return `${dt.getFullYear()}-${p(dt.getMonth() + 1)}-${p(dt.getDate())}`;
        },

        syncPixParcelasVencFaturar() {
            const forma = this.formaFaturarDetalhe || this.getFormaFaturarById(this.pagamentoFormaSelecionada);
            if (!forma || !this.formaPagamentoEhPix(forma)) {
                this.pagamentoPixParcelaVencimentosFaturar = [];
                return;
            }
            const n = this.formaPermiteParcelasFaturar(forma)
                ? (parseInt(this.pagamentoParcelasFaturar, 10) || 1)
                : 1;
            if (n <= 1) {
                this.pagamentoPixParcelaVencimentosFaturar = [];
                return;
            }
            const today = new Date();
            const pad = (x) => String(x).padStart(2, '0');
            const base = `${today.getFullYear()}-${pad(today.getMonth() + 1)}-${pad(today.getDate())}`;
            const prev = Array.isArray(this.pagamentoPixParcelaVencimentosFaturar)
                ? this.pagamentoPixParcelaVencimentosFaturar.slice()
                : [];
            const out = [];
            for (let i = 1; i <= n; i++) {
                const def = i === 1 ? base : this.addDaysYmdFaturar(base, (i - 1) * 30);
                const keep = prev[i - 1] && String(prev[i - 1]).length >= 10 ? String(prev[i - 1]).slice(0, 10) : null;
                out.push(keep || def);
            }
            this.pagamentoPixParcelaVencimentosFaturar = out;
        },

        abrirModalCancelar() {
            this.showCancelar = true;
            this.cancelTipo = defaultCancelTipo;
        },

        hasParcialQty() {
            const form = this.$refs.formCancelar;
            if (!form) return false;
            const inputs = form.querySelectorAll('input[name*="[quantidade_cancelar]"]');
            for (const inp of inputs) {
                if (this.parseValor(inp.value) > 0) return true;
            }
            return false;
        },

        validarSubmitCancelar(e) {
            if (this.cancelTipo === 'parcial' && !this.hasParcialQty()) {
                e.preventDefault();
                alert('Informe quantidade em ao menos um item.');
            }
        },

        /** Limite de crédito do cadastro: aplica-se a fiado e formas não imediatas (boleto, TED, cheque…). */
        validarLimiteCreditoPagamentoFaturar() {
            if (limiteCreditoCliente == null) {
                return { valido: true, mensagem: '' };
            }
            let uso = 0;
            if (this.tipoPagamento === 'faturado') {
                uso = this.valorAReceberFaturar;
            } else {
                for (const p of this.pagamentosInseridos) {
                    const f = this.getFormaFaturarById(p.forma_pagamento_id);
                    if (f && f.imediato === true) continue;
                    if (!f || f.imediato !== true) uso += this.parseValor(p.valor);
                }
            }
            if (uso > limiteCreditoCliente + 0.009) {
                return {
                    valido: false,
                    mensagem: 'Limite de crédito do cliente excedido para pagamentos a prazo. Valor sujeito ao limite: R$ '
                        + uso.toLocaleString('pt-BR', { minimumFractionDigits: 2, maximumFractionDigits: 2 })
                        + '. Limite cadastrado: R$ '
                        + limiteCreditoCliente.toLocaleString('pt-BR', { minimumFractionDigits: 2, maximumFractionDigits: 2 })
                        + '. Use PIX, dinheiro ou cartão para parte do valor ou aumente o limite no cadastro do cliente.',
                };
            }
            return { valido: true, mensagem: '' };
        },

        /**
         * Espelha LimiteDescontoService::validarDescontoGlobal (tipo valor) sobre o total do pedido.
         */
        validarLimiteDescontoFaturar(valorDescontoReais) {
            if (!this.usuarioSujeitoLimite || this.totalPedidoBase <= 0) {
                return { valido: true, mensagem: '' };
            }
            const vd = Number(valorDescontoReais) || 0;
            if (vd <= 0) {
                return { valido: true, mensagem: '' };
            }
            const vt = this.totalPedidoBase;
            const pct = vt > 0 ? (vd / vt) * 100 : 0;
            if (this.limiteValorDesconto != null && vd > this.limiteValorDesconto + 0.009) {
                return {
                    valido: false,
                    mensagem: 'O desconto em valor (R$ ' + vd.toLocaleString('pt-BR', { minimumFractionDigits: 2, maximumFractionDigits: 2 }) + ') excede o limite permitido de R$ ' + Number(this.limiteValorDesconto).toLocaleString('pt-BR', { minimumFractionDigits: 2, maximumFractionDigits: 2 }) + '.',
                };
            }
            if (this.limitePercentualDesconto != null && pct > this.limitePercentualDesconto + 0.05) {
                return {
                    valido: false,
                    mensagem: 'O desconto em percentual (' + pct.toFixed(1).replace('.', ',') + '%) excede o limite permitido de ' + Number(this.limitePercentualDesconto).toLocaleString('pt-BR', { minimumFractionDigits: 1, maximumFractionDigits: 1 }) + '%.',
                };
            }
            return { valido: true, mensagem: '' };
        },

        get valorAReceberFaturar() {
            return Math.max(0, this.totalPedidoBase - (this.descontoAplicadoReais || 0));
        },
        get somaPagamentosFaturar() {
            return this.pagamentosInseridos.reduce((s, p) => s + this.parseValor(p.valor), 0);
        },
        get saldoRestanteFaturar() {
            return Math.max(0, this.valorAReceberFaturar - this.somaPagamentosFaturar);
        },
        get descontoValorHidden() {
            return this.formatarValor(this.descontoAplicadoReais || 0);
        },

        formatarValor(v) {
            const n = typeof v === 'string' ? parseFloat(String(v).replace(/\D/g, '')) / 100 : Number(v);
            return isNaN(n) ? '0,00' : n.toLocaleString('pt-BR', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
        },

        parseValor(s) {
            if (typeof s !== 'string') return Number(s) || 0;
            const t = s.trim();
            if (!t) return 0;
            if (t.indexOf(',') !== -1) {
                return parseFloat(t.replace(/\./g, '').replace(',', '.')) || 0;
            }
            return parseFloat(t) || 0;
        },

        abrirModalFaturar() {
            if (this.clienteBloqueadoVendas) {
                alert('Este cliente está bloqueado para vendas. Ajuste o cadastro do cliente antes de faturar.');
                return;
            }
            this.showFaturar = true;
            this.tipoPagamento = 'agora';
            this.descontoTipoPagamento = 'valor';
            this.descontoInputFaturar = '';
            this.descontoAplicadoReais = 0;
            this.pagamentosInseridos = [];
            this.pagamentoFormaSelecionada = null;
            this.formaFaturarDetalhe = null;
            this.pagamentoAdquirenteIdFaturar = '';
            this.pagamentoBandeiraFaturar = '';
            this.pagamentoPixChaveIdFaturar = '';
            this.pagamentoParcelasFaturar = 1;
            this.pagamentoPixParcelaVencimentosFaturar = [];
            this.pagamentoValorAtualFaturar = this.formatarValor(this.totalPedidoBase);
        },

        aplicarDescontoFaturar() {
            const raw = this.parseValor(this.descontoInputFaturar);
            let candidato;
            if (this.descontoTipoPagamento === 'percentual') {
                const pct = Math.min(Math.max(raw, 0), 100);
                candidato = Math.round(this.totalPedidoBase * (pct / 100) * 100) / 100;
            } else {
                candidato = Math.min(Math.max(raw, 0), this.totalPedidoBase);
            }
            const chk = this.validarLimiteDescontoFaturar(candidato);
            if (!chk.valido) {
                alert(chk.mensagem);
                return;
            }
            this.descontoAplicadoReais = candidato;
            this.pagamentosInseridos = [];
            this.pagamentoFormaSelecionada = null;
            this.formaFaturarDetalhe = null;
            this.pagamentoValorAtualFaturar = this.formatarValor(this.saldoRestanteFaturar);
        },

        getFormaFaturarById(id) {
            if (id == null || id === '') return null;
            return this.formasPagamento.find(f => String(f.id) === String(id)) || null;
        },

        formaPagamentoEhCartao(forma) {
            if (!forma || !forma.tipo) return false;
            const t = String(forma.tipo).toLowerCase();
            return t === 'cartao_credito' || t === 'cartao_debito';
        },

        /**
         * Cartão: exige permite_parcela e max_parcelas >= 2 no JSON.
         * PIX: o controller envia max_parcelas = 1 quando o campo é null no banco — isso NÃO pode bloquear parcelamento.
         *       Se max_parcelas >= 2 no cadastro, respeita (até 24); senão usa 12x no modal.
         */
        getMaxParcelasFaturar(forma) {
            if (!forma) return 1;
            const raw = parseInt(forma.max_parcelas, 10);
            if (this.formaPagamentoEhPix(forma)) {
                if (Number.isFinite(raw) && raw >= 2) return Math.min(raw, 24);
                return 12;
            }
            if (!forma.permite_parcela) return 1;
            return Number.isFinite(raw) && raw >= 2 ? Math.min(raw, 24) : 1;
        },
        formaPermiteParcelasFaturar(forma) {
            if (!forma) return false;
            return this.getMaxParcelasFaturar(forma) > 1;
        },
        formaPagamentoEhPix(forma) {
            if (!forma || forma.tipo === undefined || forma.tipo === null) return false;
            return String(forma.tipo).toLowerCase().trim() === 'pix';
        },
        getPixChavesFaturar(forma) {
            if (!forma || !Array.isArray(forma.pix_chaves)) return [];
            return forma.pix_chaves.filter(k => k && k.chave);
        },

        getParcelasOptionsFaturar(forma) {
            const max = this.getMaxParcelasFaturar(forma);
            const arr = [];
            for (let i = 1; i <= max; i++) arr.push(i);
            return arr;
        },

        iconeFormaFaturar(tipo) {
            const t = (tipo || '').toLowerCase();
            if (t === 'dinheiro') return '💵';
            if (t === 'pix') return '📱';
            if (t === 'cartao_credito' || t === 'cartao_debito') return '💳';
            return '🏦';
        },

        selecionarFormaFaturar(f) {
            this.formaFaturarDetalhe = f;
            this.pagamentoFormaSelecionada = f.id;
            this.pagamentoAdquirenteIdFaturar = '';
            this.pagamentoBandeiraFaturar = '';
            this.pagamentoPixChaveIdFaturar = '';
            this.pagamentoParcelasFaturar = 1;
            this.pagamentoPixParcelaVencimentosFaturar = [];
            this.pagamentoValorAtualFaturar = this.formatarValor(this.saldoRestanteFaturar);
            this.syncPixParcelasVencFaturar();
        },

        confirmarValorPagamentoFaturar() {
            const forma = this.getFormaFaturarById(this.pagamentoFormaSelecionada);
            let valor = this.parseValor(this.pagamentoValorAtualFaturar);
            if (!forma || valor <= 0) return;
            if (this.formaPagamentoEhCartao(forma)) {
                if (!this.pagamentoAdquirenteIdFaturar || !this.pagamentoBandeiraFaturar) {
                    alert('Selecione adquirente e bandeira para cartão.');
                    return;
                }
            }
            let pixChave = null;
            if (this.formaPagamentoEhPix(forma)) {
                const chavesPix = this.getPixChavesFaturar(forma);
                if (chavesPix.length && !this.pagamentoPixChaveIdFaturar) {
                    alert('Selecione a chave PIX de recebimento.');
                    return;
                }
                if (chavesPix.length) {
                    pixChave = chavesPix.find(k => String(k.id) === String(this.pagamentoPixChaveIdFaturar)) || null;
                }
            }
            const maxPermitido = this.saldoRestanteFaturar + 0.005;
            if (valor > maxPermitido) {
                alert('O valor não pode ser maior que o saldo restante.');
                return;
            }
            const maxParc = this.getMaxParcelasFaturar(forma);
            let parcelas = this.formaPermiteParcelasFaturar(forma)
                ? (parseInt(this.pagamentoParcelasFaturar, 10) || 1)
                : 1;
            if (parcelas > maxParc) parcelas = maxParc;
            if (this.formaPagamentoEhPix(forma) && parcelas > 1) {
                const venc = this.pagamentoPixParcelaVencimentosFaturar || [];
                if (venc.length !== parcelas || venc.some((d) => !d || String(d).trim() === '')) {
                    alert('Informe a data de vencimento de cada parcela do PIX.');
                    return;
                }
            }
            const pixVencCopia = (this.formaPagamentoEhPix(forma) && parcelas > 1)
                ? (this.pagamentoPixParcelaVencimentosFaturar || []).map((d) => String(d).slice(0, 10))
                : [];
            this.pagamentosInseridos.push({
                forma_pagamento_id: forma.id,
                forma_nome: forma.nome,
                forma_tipo: forma.tipo,
                valor: this.formatarValor(valor),
                parcelas,
                adquirente_id: this.formaPagamentoEhCartao(forma) ? this.pagamentoAdquirenteIdFaturar : '',
                bandeira: this.formaPagamentoEhCartao(forma) ? this.pagamentoBandeiraFaturar : '',
                // Cartão: não enviar conta da forma (todas as linhas herdariam a mesma); o backend usa a conta do adquirente.
                conta_bancaria_id: this.formaPagamentoEhCartao(forma) ? '' : (pixChave?.conta_bancaria_id || forma.conta_bancaria_id || ''),
                pix_chave_id: pixChave?.id || '',
                pix_parcela_vencimentos: pixVencCopia,
            });
            this.pagamentoFormaSelecionada = null;
            this.formaFaturarDetalhe = null;
            this.pagamentoAdquirenteIdFaturar = '';
            this.pagamentoBandeiraFaturar = '';
            this.pagamentoPixChaveIdFaturar = '';
            this.pagamentoParcelasFaturar = 1;
            this.pagamentoPixParcelaVencimentosFaturar = [];
            this.pagamentoValorAtualFaturar = this.formatarValor(this.saldoRestanteFaturar);
        },

        removerPagamentoFaturar(idx) {
            this.pagamentosInseridos.splice(idx, 1);
        },

        validarSubmitFaturar(e) {
            const lim = this.validarLimiteDescontoFaturar(this.descontoAplicadoReais || 0);
            if (!lim.valido) {
                e.preventDefault();
                alert(lim.mensagem);
                return;
            }
            const cred = this.validarLimiteCreditoPagamentoFaturar();
            if (!cred.valido) {
                e.preventDefault();
                alert(cred.mensagem);
                return;
            }
            if (this.tipoPagamento === 'faturado') return;
            if (this.valorAReceberFaturar <= 0.01) return;
            if (this.saldoRestanteFaturar > 0.01) {
                e.preventDefault();
                alert('Quite o valor total com as formas de pagamento ou escolha Faturado / Fiado.');
                return;
            }
            for (const p of this.pagamentosInseridos) {
                const f = this.getFormaFaturarById(p.forma_pagamento_id);
                const parc = parseInt(p.parcelas, 10) || 1;
                if (f && this.formaPagamentoEhPix(f) && parc > 1) {
                    const v = p.pix_parcela_vencimentos || [];
                    if (v.length !== parc || v.some((d) => !d)) {
                        e.preventDefault();
                        alert('Há pagamento PIX parcelado sem datas de vencimento. Remova e inclua novamente ou corrija.');
                        return;
                    }
                }
            }
        },
    };
}
</script>
<?php $__env->stopPush(); ?>
<?php $__env->stopSection(); ?>

<?php echo $__env->yieldPushContent('scripts'); ?>

<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\laragon\www\LivreOS\LivreOS\sistema_livreos\resources\views/pedidos-venda/show.blade.php ENDPATH**/ ?>