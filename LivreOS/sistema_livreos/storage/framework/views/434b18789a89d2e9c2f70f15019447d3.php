



<?php $__env->startSection('content'); ?>
<div class="mb-6 flex flex-wrap items-center justify-between gap-3">
    <div>
        <h1 class="mb-2 text-2xl font-semibold text-gray-800 dark:text-white/90">Pagamentos Recorrentes</h1>
        <p class="text-gray-600 dark:text-gray-400">Recebimentos que se repetem (aluguel, assinatura, contrato). As contas a receber são geradas automaticamente.</p>
    </div>
    <a href="<?php echo e(route('financeiro.pagamentos-recorrentes.create')); ?>" class="inline-flex items-center gap-2 rounded-lg bg-brand-500 px-4 py-2.5 text-sm font-medium text-white hover:bg-brand-600">
        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
        Novo Recorrente
    </a>
</div>

<?php if(session('success')): ?>
    <div class="mb-4 rounded-lg bg-green-50 p-4 text-sm text-green-800 dark:bg-green-900/20 dark:text-green-400"><?php echo e(session('success')); ?></div>
<?php endif; ?>
<?php if(session('error')): ?>
    <div class="mb-4 rounded-lg bg-red-50 p-4 text-sm text-red-800 dark:bg-red-900/20 dark:text-red-400"><?php echo e(session('error')); ?></div>
<?php endif; ?>

<div class="mb-6 rounded-lg border border-gray-200 bg-white p-4 shadow-theme-sm dark:border-gray-800 dark:bg-gray-800">
    <form method="GET" action="<?php echo e(route('financeiro.pagamentos-recorrentes.index')); ?>" class="flex flex-wrap items-end gap-4">
        <div>
            <label class="mb-1.5 block text-xs font-medium text-gray-700 dark:text-gray-300">Cliente</label>
            <select name="cliente_id" class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm dark:border-gray-700 dark:bg-gray-900 dark:text-white/90">
                <option value="">Todos</option>
                <?php $__currentLoopData = $clientes; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $c): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                    <option value="<?php echo e($c->id); ?>" <?php echo e(request('cliente_id') == $c->id ? 'selected' : ''); ?>><?php echo e($c->nome); ?></option>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
            </select>
        </div>
        <div>
            <label class="mb-1.5 block text-xs font-medium text-gray-700 dark:text-gray-300">Tipo</label>
            <select name="tipo" class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm dark:border-gray-700 dark:bg-gray-900 dark:text-white/90">
                <option value="">Todos</option>
                <option value="aluguel" <?php echo e(request('tipo') === 'aluguel' ? 'selected' : ''); ?>>Aluguel</option>
                <option value="contrato" <?php echo e(request('tipo') === 'contrato' ? 'selected' : ''); ?>>Contrato</option>
                <option value="assinatura" <?php echo e(request('tipo') === 'assinatura' ? 'selected' : ''); ?>>Assinatura</option>
                <option value="manutencao" <?php echo e(request('tipo') === 'manutencao' ? 'selected' : ''); ?>>Manutenção</option>
                <option value="outro" <?php echo e(request('tipo') === 'outro' ? 'selected' : ''); ?>>Outro</option>
            </select>
        </div>
        <div>
            <label class="mb-1.5 block text-xs font-medium text-gray-700 dark:text-gray-300">Frequência</label>
            <select name="frequencia" class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm dark:border-gray-700 dark:bg-gray-900 dark:text-white/90">
                <option value="">Todas</option>
                <option value="diaria" <?php echo e(request('frequencia') === 'diaria' ? 'selected' : ''); ?>>Diária</option>
                <option value="semanal" <?php echo e(request('frequencia') === 'semanal' ? 'selected' : ''); ?>>Semanal</option>
                <option value="quinzenal" <?php echo e(request('frequencia') === 'quinzenal' ? 'selected' : ''); ?>>Quinzenal</option>
                <option value="mensal" <?php echo e(request('frequencia') === 'mensal' ? 'selected' : ''); ?>>Mensal</option>
                <option value="bimestral" <?php echo e(request('frequencia') === 'bimestral' ? 'selected' : ''); ?>>Bimestral</option>
                <option value="trimestral" <?php echo e(request('frequencia') === 'trimestral' ? 'selected' : ''); ?>>Trimestral</option>
                <option value="semestral" <?php echo e(request('frequencia') === 'semestral' ? 'selected' : ''); ?>>Semestral</option>
                <option value="anual" <?php echo e(request('frequencia') === 'anual' ? 'selected' : ''); ?>>Anual</option>
            </select>
        </div>
        <div>
            <label class="mb-1.5 block text-xs font-medium text-gray-700 dark:text-gray-300">Status</label>
            <select name="ativo" class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm dark:border-gray-700 dark:bg-gray-900 dark:text-white/90">
                <option value="">Todos</option>
                <option value="1" <?php echo e(request('ativo') === '1' ? 'selected' : ''); ?>>Ativos</option>
                <option value="0" <?php echo e(request('ativo') === '0' ? 'selected' : ''); ?>>Inativos</option>
            </select>
        </div>
        <button type="submit" class="rounded-lg bg-brand-500 px-4 py-2 text-sm font-medium text-white hover:bg-brand-600">Filtrar</button>
        <a href="<?php echo e(route('financeiro.pagamentos-recorrentes.index')); ?>" class="rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-300">Limpar</a>
    </form>
</div>

<div class="rounded-lg border border-gray-200 bg-white shadow-theme-sm dark:border-gray-800 dark:bg-gray-800">
    <div class="overflow-x-auto">
        <table class="w-full">
            <thead class="bg-gray-50 dark:bg-gray-900">
                <tr>
                    <th class="px-4 py-3 text-left text-xs font-medium uppercase text-gray-500 dark:text-gray-400">Cliente</th>
                    <th class="px-4 py-3 text-left text-xs font-medium uppercase text-gray-500 dark:text-gray-400">Descrição</th>
                    <th class="px-4 py-3 text-center text-xs font-medium uppercase text-gray-500 dark:text-gray-400">Tipo</th>
                    <th class="px-4 py-3 text-left text-xs font-medium uppercase text-gray-500 dark:text-gray-400">Vínculo</th>
                    <th class="px-4 py-3 text-right text-xs font-medium uppercase text-gray-500 dark:text-gray-400">Valor</th>
                    <th class="px-4 py-3 text-center text-xs font-medium uppercase text-gray-500 dark:text-gray-400">Frequência</th>
                    <th class="px-4 py-3 text-center text-xs font-medium uppercase text-gray-500 dark:text-gray-400">Próxima geração</th>
                    <th class="px-4 py-3 text-center text-xs font-medium uppercase text-gray-500 dark:text-gray-400">Ativo</th>
                    <th class="px-4 py-3 text-center text-xs font-medium uppercase text-gray-500 dark:text-gray-400">Ações</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                <?php $__empty_1 = true; $__currentLoopData = $recorrentes; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $r): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                    <tr class="hover:bg-gray-50 dark:hover:bg-gray-800/50">
                        <td class="px-4 py-3 text-sm text-gray-700 dark:text-gray-300"><?php echo e($r->cliente->nome ?? '-'); ?></td>
                        <td class="px-4 py-3 text-sm text-gray-700 dark:text-gray-300"><?php echo e($r->descricao); ?></td>
                        <td class="px-4 py-3 text-center text-sm text-gray-600 dark:text-gray-400"><?php echo e(\App\Models\PagamentoRecorrente::tipoLabel($r->tipo)); ?></td>
                        <td class="px-4 py-3 text-xs text-gray-600 dark:text-gray-400">
                            <?php if($r->controle_entidade_tipo || $r->controle_entidade_id || $r->controle_origem): ?>
                                <div><strong><?php echo e($r->controle_entidade_tipo ?: '-'); ?></strong></div>
                                <div>ID: <?php echo e($r->controle_entidade_id ?: '-'); ?></div>
                                <?php if($r->controle_origem): ?><div>Origem: <?php echo e($r->controle_origem); ?></div><?php endif; ?>
                                <?php if($r->controle_referencia): ?><div>Ref: <?php echo e($r->controle_referencia); ?></div><?php endif; ?>
                            <?php else: ?>
                                <span class="text-gray-400 dark:text-gray-500">—</span>
                            <?php endif; ?>
                        </td>
                        <td class="px-4 py-3 text-right text-sm font-medium text-gray-800 dark:text-white/90">R$ <?php echo e(number_format($r->valor, 2, ',', '.')); ?></td>
                        <td class="px-4 py-3 text-center text-sm text-gray-600 dark:text-gray-400"><?php echo e(\App\Models\PagamentoRecorrente::frequenciaLabel($r->frequencia)); ?></td>
                        <td class="px-4 py-3 text-center text-sm text-gray-600 dark:text-gray-400"><?php echo e($r->proxima_geracao_em->format('d/m/Y')); ?></td>
                        <td class="px-4 py-3 text-center">
                            <span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium <?php echo e($r->ativo ? 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-300' : 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300'); ?>">
                                <?php echo e($r->ativo ? 'Sim' : 'Não'); ?>

                            </span>
                        </td>
                        <td class="px-4 py-3 text-center">
                            <a href="<?php echo e(route('financeiro.pagamentos-recorrentes.edit', $r)); ?>" class="text-blue-600 hover:text-blue-800 dark:text-blue-400" title="Editar">
                                <svg class="h-5 w-5 inline" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                            </a>
                            <button type="button" onclick="abrirModalExcluirMotivo('<?php echo e(route('financeiro.pagamentos-recorrentes.destroy', $r)); ?>')" class="text-red-600 hover:text-red-800 dark:text-red-400" title="Excluir">
                                    <svg class="h-5 w-5 inline" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                </button>
                        </td>
                    </tr>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                    <tr>
                        <td colspan="9" class="px-4 py-8 text-center text-gray-500 dark:text-gray-400">Nenhum pagamento recorrente cadastrado. Crie um para gerar contas a receber automaticamente (aluguel, assinatura, contrato).</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
    <div class="border-t border-gray-200 px-4 py-3 dark:border-gray-700">
        <?php echo e($recorrentes->links()); ?>

    </div>
</div>

<p class="mt-4 text-xs text-gray-500 dark:text-gray-400">As contas a receber são criadas automaticamente pelo agendamento diário (comando <code>financeiro:gerar-recorrentes</code>). Em hospedagem compartilhada sem cron, habilite <strong>Configurações → Tarefas agendadas → Executar tarefas ao acessar o sistema</strong> para que a geração rode na primeira visita do dia.</p>

<?php if (isset($component)) { $__componentOriginalcab7fd9f03f92aafb8fc56854afdeea5 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginalcab7fd9f03f92aafb8fc56854afdeea5 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.modal-excluir-motivo','data' => ['action' => '','titulo' => 'Excluir pagamento recorrente','descricao' => 'Tem certeza que deseja excluir este pagamento recorrente? Informe o motivo.']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('modal-excluir-motivo'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['action' => '','titulo' => 'Excluir pagamento recorrente','descricao' => 'Tem certeza que deseja excluir este pagamento recorrente? Informe o motivo.']); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginalcab7fd9f03f92aafb8fc56854afdeea5)): ?>
<?php $attributes = $__attributesOriginalcab7fd9f03f92aafb8fc56854afdeea5; ?>
<?php unset($__attributesOriginalcab7fd9f03f92aafb8fc56854afdeea5); ?>
<?php endif; ?>
<?php if (isset($__componentOriginalcab7fd9f03f92aafb8fc56854afdeea5)): ?>
<?php $component = $__componentOriginalcab7fd9f03f92aafb8fc56854afdeea5; ?>
<?php unset($__componentOriginalcab7fd9f03f92aafb8fc56854afdeea5); ?>
<?php endif; ?>
<script>
function abrirModalExcluirMotivo(url) {
    document.getElementById('formExcluirMotivo').action = url;
    document.getElementById('motivoExcluir').value = '';
    document.getElementById('modalExcluirMotivo').classList.remove('hidden');
    document.getElementById('modalExcluirMotivo').classList.add('flex');
    document.body.style.overflow = 'hidden';
}
</script>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\laragon\www\LivreOS\LivreOS\sistema_livreos\resources\views\financeiro\pagamentos-recorrentes\index.blade.php ENDPATH**/ ?>