



<?php $__env->startSection('content'); ?>
<div class="mb-6 flex flex-wrap items-center justify-between gap-3">
    <div>
        <h1 class="text-2xl font-semibold text-gray-800 dark:text-white/90">Vendas por Serviço e Produto</h1>
        <?php
            $crit = $criterio ?? 'operacional';
            $incPv = $incluir_pedidos ?? false;
            $tecnicoSel = $tecnico_id ?? null;
            $slaSel = $sla_filtro ?? null;
        ?>
        <?php if($crit === 'operacional'): ?>
        <p class="mt-1 text-gray-600 dark:text-gray-400"><strong>Modo execução (sem financeiro):</strong> por padrão só <strong>ordens de serviço fechadas</strong> (Encerrada, Concluída, Finalizado/Faturado vindos de sistema externo, etc.) com <strong>data de conclusão</strong> no período — ou, se não houver, a <strong>última alteração</strong> no período (útil em importações). <strong>Pedidos de venda</strong> só entram se você marcar a opção abaixo. OS geradas a partir de pedido não são somadas de novo pela OS.</p>
        <?php else: ?>
        <p class="mt-1 text-gray-600 dark:text-gray-400">Baseado em <strong>recebimentos</strong> (contas a receber pagas ou parciais com data de recebimento no período): o valor recebido é distribuído pelas linhas faturáveis do <strong>pedido</strong> (confirmado/faturado/entregue) ou da <strong>OS</strong> (linhas sem quantidade/horas efetiva não entram — ex.: orçamento só de referência). Adiantamentos de OS não entram.</p>
        <?php endif; ?>
    </div>
    <form method="get" action="<?php echo e(route('financeiro.relatorios.vendas-servico-produto')); ?>" class="flex flex-wrap items-end gap-3">
        <div>
            <label class="mb-1 block text-xs font-medium text-gray-600 dark:text-gray-400">De</label>
            <input type="date" name="data_inicio" value="<?php echo e($dataInicio ?? ''); ?>" class="rounded-lg border border-gray-300 px-3 py-2 text-sm dark:border-gray-600 dark:bg-gray-800 dark:text-white">
        </div>
        <div>
            <label class="mb-1 block text-xs font-medium text-gray-600 dark:text-gray-400">Até</label>
            <input type="date" name="data_fim" value="<?php echo e($dataFim ?? ''); ?>" class="rounded-lg border border-gray-300 px-3 py-2 text-sm dark:border-gray-600 dark:bg-gray-800 dark:text-white">
        </div>
        <div>
            <label class="mb-1 block text-xs font-medium text-gray-600 dark:text-gray-400">Critério</label>
            <select name="criterio" class="rounded-lg border border-gray-300 px-3 py-2 text-sm dark:border-gray-600 dark:bg-gray-800 dark:text-white">
                <option value="financeiro" <?php if($crit === 'financeiro'): echo 'selected'; endif; ?>>Recebimento (financeiro)</option>
                <option value="operacional" <?php if($crit === 'operacional'): echo 'selected'; endif; ?>>Execução (sem financeiro)</option>
            </select>
        </div>
        <div>
            <label class="mb-1 block text-xs font-medium text-gray-600 dark:text-gray-400">Técnico</label>
            <select name="tecnico_id" class="rounded-lg border border-gray-300 px-3 py-2 text-sm dark:border-gray-600 dark:bg-gray-800 dark:text-white">
                <option value="">Todos</option>
                <?php $__currentLoopData = ($tecnicos ?? []); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $tec): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                    <option value="<?php echo e($tec->id); ?>" <?php if((int) $tecnicoSel === (int) $tec->id): echo 'selected'; endif; ?>><?php echo e($tec->name); ?></option>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
            </select>
        </div>
        <div>
            <label class="mb-1 block text-xs font-medium text-gray-600 dark:text-gray-400">SLA</label>
            <select name="sla_filtro" class="rounded-lg border border-gray-300 px-3 py-2 text-sm dark:border-gray-600 dark:bg-gray-800 dark:text-white">
                <option value="">Todos</option>
                <option value="cumprido" <?php if($slaSel === 'cumprido'): echo 'selected'; endif; ?>>Cumprido</option>
                <option value="nao_cumprido" <?php if($slaSel === 'nao_cumprido'): echo 'selected'; endif; ?>>Não cumprido</option>
                <option value="com_sla" <?php if($slaSel === 'com_sla'): echo 'selected'; endif; ?>>Com SLA</option>
                <option value="sem_sla" <?php if($slaSel === 'sem_sla'): echo 'selected'; endif; ?>>Sem SLA</option>
            </select>
        </div>
        <div class="flex items-center gap-2 pb-0.5">
            <input type="checkbox" name="incluir_pedidos" value="1" id="incluir_pedidos" <?php if($incPv): echo 'checked'; endif; ?> class="rounded border-gray-300 text-brand-600 dark:border-gray-600">
            <label for="incluir_pedidos" class="text-xs text-gray-600 dark:text-gray-400">Incluir pedidos de venda (data do pedido; só afeta modo Execução)</label>
        </div>
        <button type="submit" class="rounded-lg bg-brand-600 px-4 py-2 text-sm font-medium text-white hover:bg-brand-700">Filtrar</button>
    </form>
</div>

<div class="mb-6 flex gap-4">
    <a href="<?php echo e(route('financeiro.relatorios.vendas-servico-produto.pdf', request()->query())); ?>" target="_blank" rel="noopener" class="inline-flex items-center gap-2 rounded-lg border border-gray-300 bg-white px-4 py-2.5 text-sm font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-300 dark:hover:bg-gray-700">
        <svg class="h-5 w-5 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
        Exportar PDF
    </a>
</div>

<p class="mb-4 text-sm text-gray-600 dark:text-gray-400">Período: <?php echo e(\Carbon\Carbon::parse($dataInicio ?? now())->format('d/m/Y')); ?> a <?php echo e(\Carbon\Carbon::parse($dataFim ?? now())->format('d/m/Y')); ?> ·
<?php if($crit === 'operacional'): ?>
<strong>OS:</strong> COALESCE(conclusão, alteração) no período · <?php if($incPv): ?><strong>Pedidos:</strong> data do pedido <?php else: ?><strong>Pedidos:</strong> fora <?php endif; ?>
<?php else: ?>
<strong>Contas a receber:</strong> data de recebimento (pago/parcial)
<?php endif; ?>
<?php if(!empty($tecnicoSel)): ?>
 · <strong>Técnico:</strong> <?php echo e(collect($tecnicos ?? [])->firstWhere('id', (int) $tecnicoSel)->name ?? ('#'.$tecnicoSel)); ?>

<?php endif; ?>
<?php if(!empty($slaSel)): ?>
 · <strong>SLA:</strong>
<?php switch($slaSel):
    case ('cumprido'): ?> Cumprido <?php break; ?>
    <?php case ('nao_cumprido'): ?> Não cumprido <?php break; ?>
    <?php case ('com_sla'): ?> Com SLA <?php break; ?>
    <?php case ('sem_sla'): ?> Sem SLA <?php break; ?>
<?php endswitch; ?>
<?php endif; ?>
</p>

<div class="mb-8 overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm dark:border-gray-700 dark:bg-gray-800">
    <div class="border-b border-gray-200 bg-gray-50 px-4 py-3 dark:border-gray-700 dark:bg-gray-900">
        <h2 class="text-lg font-semibold text-gray-800 dark:text-white/90">Serviços</h2>
    </div>
    <div class="overflow-x-auto">
        <table class="w-full">
            <thead class="bg-gray-50 dark:bg-gray-900">
                <tr>
                    <th class="px-4 py-3 text-left text-xs font-medium uppercase text-gray-500 dark:text-gray-400">Serviço</th>
                    <th class="px-4 py-3 text-right text-xs font-medium uppercase text-gray-500 dark:text-gray-400">Quantidade</th>
                    <th class="px-4 py-3 text-right text-xs font-medium uppercase text-gray-500 dark:text-gray-400">Total (R$)</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                <?php $__currentLoopData = $servicos ?? []; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $row): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                <tr class="hover:bg-gray-50 dark:hover:bg-gray-800/50">
                    <td class="px-4 py-3 text-sm font-medium text-gray-800 dark:text-white/90"><?php echo e($row['nome']); ?></td>
                    <td class="px-4 py-3 text-right text-sm text-gray-600 dark:text-gray-400"><?php echo e(number_format((float) ($row['quantidade'] ?? 0), 2, ',', '.')); ?></td>
                    <td class="px-4 py-3 text-right text-sm font-medium text-gray-800 dark:text-white/90">R$ <?php echo e(number_format($row['total'], 2, ',', '.')); ?></td>
                </tr>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
            </tbody>
        </table>
    </div>
    <?php if(empty($servicos) || count($servicos) === 0): ?>
    <div class="p-6 text-center text-gray-500 dark:text-gray-400">Nenhum serviço no período.</div>
    <?php endif; ?>
</div>

<div class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm dark:border-gray-700 dark:bg-gray-800">
    <div class="border-b border-gray-200 bg-gray-50 px-4 py-3 dark:border-gray-700 dark:bg-gray-900">
        <h2 class="text-lg font-semibold text-gray-800 dark:text-white/90">Produtos</h2>
    </div>
    <div class="overflow-x-auto">
        <table class="w-full">
            <thead class="bg-gray-50 dark:bg-gray-900">
                <tr>
                    <th class="px-4 py-3 text-left text-xs font-medium uppercase text-gray-500 dark:text-gray-400">Produto</th>
                    <th class="px-4 py-3 text-right text-xs font-medium uppercase text-gray-500 dark:text-gray-400">Quantidade</th>
                    <th class="px-4 py-3 text-right text-xs font-medium uppercase text-gray-500 dark:text-gray-400">Total (R$)</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                <?php $__currentLoopData = $produtos ?? []; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $row): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                <tr class="hover:bg-gray-50 dark:hover:bg-gray-800/50">
                    <td class="px-4 py-3 text-sm font-medium text-gray-800 dark:text-white/90"><?php echo e($row['nome']); ?></td>
                    <td class="px-4 py-3 text-right text-sm text-gray-600 dark:text-gray-400"><?php echo e(number_format((float) ($row['quantidade'] ?? 0), 2, ',', '.')); ?></td>
                    <td class="px-4 py-3 text-right text-sm font-medium text-gray-800 dark:text-white/90">R$ <?php echo e(number_format($row['total'], 2, ',', '.')); ?></td>
                </tr>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
            </tbody>
        </table>
    </div>
    <?php if(empty($produtos) || count($produtos) === 0): ?>
    <div class="p-6 text-center text-gray-500 dark:text-gray-400">Nenhum produto no período.</div>
    <?php endif; ?>
</div>

<div class="mt-4">
    <a href="<?php echo e(route('financeiro.relatorios.index')); ?>" class="text-sm font-medium text-brand-600 hover:underline dark:text-brand-400">← Voltar aos relatórios</a>
</div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\laragon\www\LivreOS\LivreOS\sistema_livreos\resources\views\financeiro\relatorios\vendas-servico-produto.blade.php ENDPATH**/ ?>