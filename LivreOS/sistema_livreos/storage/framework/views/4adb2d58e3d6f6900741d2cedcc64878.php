



<?php $__env->startSection('content'); ?>
<?php
    $statusColor = \AreaCliente\AreaClienteHelper::chamadoStatusColor($chamado->status);
    $prioridadeColor = \AreaCliente\AreaClienteHelper::chamadoPrioridadeColor($chamado->prioridade);
?>
<div class="mx-auto max-w-4xl pb-6">
    <?php if(session('success')): ?>
    <div class="mb-4 rounded-2xl bg-success-100 px-4 py-3 text-sm text-success-700 dark:bg-success-500/20 dark:text-success-400"><?php echo e(session('success')); ?></div>
    <?php endif; ?>
    <?php if(session('error')): ?>
    <div class="mb-4 rounded-2xl bg-error-100 px-4 py-3 text-sm text-error-700 dark:bg-error-500/20 dark:text-error-400"><?php echo e(session('error')); ?></div>
    <?php endif; ?>

    <div class="mb-6 flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
        <div>
            <h1 class="text-2xl font-bold text-gray-800 dark:text-white/90">Chamado #<?php echo e($chamado->id); ?></h1>
            <p class="mt-1 text-lg text-gray-600 dark:text-gray-400"><?php echo e($chamado->titulo); ?></p>
            <p class="mt-2 text-sm text-gray-500 dark:text-gray-500">Cliente: <?php echo e($chamado->cliente ? ($chamado->cliente->nome ?? $chamado->cliente->razao_social) : '–'); ?> · <?php echo e($chamado->created_at->format('d/m/Y H:i')); ?></p>
        </div>
        <div class="flex flex-wrap gap-2">
            <span class="inline-flex rounded-full px-4 py-2 text-sm font-semibold <?php echo e($statusColor); ?>"><?php echo e(\AreaCliente\Chamado::statusLabel($chamado->status)); ?></span>
            <span class="inline-flex rounded-full px-4 py-2 text-sm font-semibold <?php echo e($prioridadeColor); ?>"><?php echo e(\AreaCliente\Chamado::prioridadeLabel($chamado->prioridade)); ?></span>
        </div>
    </div>

    <div class="mb-6 flex flex-wrap gap-3">
        <a href="<?php echo e(route('plugin.area-cliente.admin.chamados.index')); ?>" class="inline-flex min-h-[48px] items-center justify-center gap-2 rounded-xl border-2 border-gray-200 bg-white px-5 py-3 text-base font-semibold text-gray-700 transition dark:border-gray-600 dark:bg-gray-800 dark:text-gray-200 hover:bg-gray-50 dark:hover:bg-gray-700">← Voltar</a>
    </div>

    
    <div class="mb-6 rounded-2xl border border-gray-200 bg-white p-4 shadow-sm dark:border-gray-700 dark:bg-gray-800 sm:p-6">
        <h2 class="mb-4 text-lg font-bold text-gray-800 dark:text-white/90">Alterar status</h2>
        <form action="<?php echo e(route('plugin.area-cliente.admin.chamados.update', $chamado->id)); ?>" method="POST" class="flex flex-wrap items-end gap-4">
            <?php echo csrf_field(); ?>
            <?php echo method_field('PATCH'); ?>
            <div class="min-w-[200px]">
                <label for="status" class="mb-1.5 block text-sm font-medium text-gray-600 dark:text-gray-400">Status</label>
                <select id="status" name="status" class="w-full rounded-xl border border-gray-300 px-4 py-3 text-base dark:border-gray-600 dark:bg-gray-900 dark:text-white/90">
                    <?php $__currentLoopData = \AreaCliente\Chamado::STATUS; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $v => $l): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                    <option value="<?php echo e($v); ?>" <?php echo e($chamado->status === $v ? 'selected' : ''); ?>><?php echo e($l); ?></option>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                </select>
            </div>
            <button type="submit" class="min-h-[48px] rounded-xl bg-brand-500 px-5 py-3 text-base font-semibold text-white hover:bg-brand-600">Atualizar status</button>
        </form>
    </div>

    <?php
        $anexosSemResposta = $chamado->anexos->whereNull('chamado_resposta_id');
        $timeline = collect([['type' => 'abertura', 'created_at' => $chamado->created_at, 'chamado' => $chamado]])
            ->concat($chamado->respostas->map(fn($r) => ['type' => 'resposta', 'created_at' => $r->created_at, 'item' => $r]))
            ->concat($anexosSemResposta->map(fn($a) => ['type' => 'anexo', 'created_at' => $a->created_at, 'item' => $a]))
            ->sortBy('created_at')->values();
    ?>

    
    <div class="mb-6 rounded-2xl border border-gray-200 bg-white p-4 shadow-sm dark:border-gray-700 dark:bg-gray-800 sm:p-6">
        <h2 class="mb-4 text-lg font-bold text-gray-800 dark:text-white/90">Linha do tempo</h2>
        <ul class="mb-6 space-y-4">
            <?php $__currentLoopData = $timeline; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $entry): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
            <?php if($entry['type'] === 'abertura'): ?>
            <li class="rounded-xl border border-gray-100 p-4 dark:border-gray-700 bg-gray-50 dark:bg-gray-700/30">
                <p class="mb-2 text-sm font-medium text-gray-700 dark:text-gray-300">
                    Abertura · <?php echo e($entry['chamado']->created_at->format('d/m/Y H:i')); ?>

                    <?php if($entry['chamado']->user): ?>
                        · <?php echo e($entry['chamado']->user->name ?? $entry['chamado']->user->email); ?>

                    <?php endif; ?>
                </p>
                <div class="prose prose-sm max-w-none text-gray-700 dark:text-gray-300">
                    <?php if($entry['chamado']->descricao): ?>
                        <?php echo $entry['chamado']->descricao; ?>

                    <?php else: ?>
                        <p class="text-gray-500 dark:text-gray-400">Sem descrição.</p>
                    <?php endif; ?>
                </div>
            </li>
            <?php elseif($entry['type'] === 'resposta'): ?>
            <li class="rounded-xl border p-4 <?php echo e($entry['item']->interno ? 'border-amber-200 bg-amber-50/50 dark:border-amber-800 dark:bg-amber-900/10' : 'border-gray-100 bg-gray-50 dark:border-gray-700 dark:bg-gray-700/30'); ?>">
                <div class="mb-2 flex flex-wrap items-center gap-2 text-sm">
                    <span class="font-medium text-gray-800 dark:text-white/90"><?php echo e($entry['item']->user ? ($entry['item']->user->name ?? $entry['item']->user->email) : 'Sistema'); ?></span>
                    <span class="text-gray-500 dark:text-gray-400"><?php echo e($entry['item']->created_at->format('d/m/Y H:i')); ?></span>
                    <?php if($entry['item']->interno): ?>
                    <span class="rounded-full bg-amber-200 px-2 py-0.5 text-xs font-medium text-amber-800 dark:bg-amber-800 dark:text-amber-200">Interno</span>
                    <?php endif; ?>
                </div>
                <?php if($entry['item']->mensagem && $entry['item']->mensagem !== '—'): ?>
                <div class="prose prose-sm max-w-none text-gray-700 dark:text-gray-300"><?php echo $entry['item']->mensagem; ?></div>
                <?php endif; ?>
                <?php if($entry['item']->anexos && $entry['item']->anexos->count() > 0): ?>
                <ul class="mt-3 space-y-2">
                    <?php $__currentLoopData = $entry['item']->anexos; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $a): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                    <li>
                        <a href="<?php echo e(route('plugin.area-cliente.admin.chamados.anexo', [$chamado->id, $a->id])); ?>" target="_blank" rel="noopener" class="inline-flex items-center gap-2 rounded-lg border border-gray-200 bg-white px-3 py-2 text-sm font-medium text-gray-700 hover:bg-gray-100 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-200 dark:hover:bg-gray-700">
                            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.172 7l-6.586 6.586a2 2 0 102.828 2.828l6.414-6.586a4 4 0 00-5.656-5.656l-6.415 6.585a6 6 0 108.486 8.486L20.5 13"/></svg>
                            <?php echo e($a->nome_original ?: 'Anexo'); ?>

                        </a>
                    </li>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                </ul>
                <?php endif; ?>
            </li>
            <?php else: ?>
            <li>
                <a href="<?php echo e(route('plugin.area-cliente.admin.chamados.anexo', [$chamado->id, $entry['item']->id])); ?>" target="_blank" rel="noopener" class="inline-flex items-center gap-2 rounded-xl border border-gray-200 bg-gray-50 px-4 py-3 text-sm font-medium text-gray-700 hover:bg-gray-100 dark:border-gray-600 dark:bg-gray-700/50 dark:text-gray-200 dark:hover:bg-gray-700">
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.172 7l-6.586 6.586a2 2 0 102.828 2.828l6.414-6.586a4 4 0 00-5.656-5.656l-6.415 6.585a6 6 0 108.486 8.486L20.5 13"/></svg>
                    <?php echo e($entry['item']->nome_original ?: 'Anexo'); ?>

                    <span class="text-xs text-gray-500"><?php echo e($entry['item']->created_at->format('d/m/Y H:i')); ?></span>
                </a>
            </li>
            <?php endif; ?>
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
        </ul>

        <h3 class="mb-3 text-base font-semibold text-gray-800 dark:text-white/90">Nova resposta e/ou anexo</h3>
        <p class="mb-3 text-xs text-gray-500 dark:text-gray-400">Envie mensagem e anexos juntos – aparecerão no histórico em um único bloco.</p>
        <form action="<?php echo e(route('plugin.area-cliente.admin.chamados.respostas.store', $chamado->id)); ?>" method="POST" enctype="multipart/form-data" class="space-y-4" id="form-resposta-admin">
            <?php echo csrf_field(); ?>
            <div>
                <label for="mensagem" class="mb-1.5 block text-sm font-medium text-gray-600 dark:text-gray-400">Mensagem (opcional se enviar anexos)</label>
                <textarea id="mensagem" name="mensagem" class="js-jodit w-full rounded-xl border border-gray-300 px-4 py-3 text-base dark:border-gray-600 dark:bg-gray-900 dark:text-white/90 <?php $__errorArgs = ['mensagem'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> border-error-500 <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>" rows="4" placeholder="Digite sua resposta e/ou selecione arquivos..."><?php echo e(old('mensagem')); ?></textarea>
                <?php $__errorArgs = ['mensagem'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?><p class="mt-1 text-sm text-error-600 dark:text-error-400"><?php echo e($message); ?></p><?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
            </div>
            <div>
                <label class="mb-1.5 block text-sm font-medium text-gray-600 dark:text-gray-400">Anexos (opcional)</label>
                <input type="file" name="anexos[]" accept=".jpg,.jpeg,.png,.gif,.webp,.pdf" multiple class="w-full rounded-xl border border-gray-300 px-4 py-3 text-base dark:border-gray-600 dark:bg-gray-900 dark:text-white/90">
                <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">Máx. 10 MB por arquivo.</p>
            </div>
            <div class="flex flex-wrap items-center gap-4">
                <label class="inline-flex items-center gap-2">
                    <input type="hidden" name="interno" value="0">
                    <input type="checkbox" name="interno" value="1" <?php echo e(old('interno') ? 'checked' : ''); ?> class="rounded border-gray-300 text-brand-600 focus:ring-brand-500">
                    <span class="text-sm text-gray-600 dark:text-gray-400">Apenas interno (não visível para o cliente)</span>
                </label>
                <button type="submit" class="min-h-[48px] rounded-xl bg-brand-500 px-5 py-3 text-base font-semibold text-white hover:bg-brand-600">Enviar</button>
            </div>
        </form>
    </div>

    
    <div class="mb-6 rounded-2xl border border-gray-200 bg-white p-4 shadow-sm dark:border-gray-700 dark:bg-gray-800 sm:p-6">
        <h2 class="mb-4 text-lg font-bold text-gray-800 dark:text-white/90">Converter em Ordem de Serviço</h2>
        <?php if($chamado->ordem_servico_id && $chamado->ordemServico): ?>
        <p class="mb-2 text-gray-600 dark:text-gray-400">Este chamado já foi convertido em ordem de serviço.</p>
        <a href="<?php echo e(url('/ordens-servico/' . $chamado->ordem_servico_id)); ?>" class="inline-flex min-h-[48px] items-center gap-2 rounded-xl bg-brand-500 px-5 py-3 text-base font-semibold text-white hover:bg-brand-600">Ver <?php echo e($chamado->ordemServico->codigo_interno ?? '#' . $chamado->ordem_servico_id); ?></a>
        <?php elseif($chamado->cliente): ?>
        <form action="<?php echo e(route('plugin.area-cliente.admin.chamados.converter-os', $chamado->id)); ?>" method="POST" class="space-y-4" id="form-converter-os">
            <?php echo csrf_field(); ?>
            <div>
                <label class="mb-1.5 block text-sm font-medium text-gray-600 dark:text-gray-400">Equipamento</label>
                <select name="equipamento_id" id="equipamento_id" class="w-full rounded-xl border border-gray-300 px-4 py-3 text-base dark:border-gray-600 dark:bg-gray-900 dark:text-white/90">
                    <option value="">— Selecionar equipamento do cliente —</option>
                    <?php $__currentLoopData = $equipamentos ?? []; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $eq): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                    <option value="<?php echo e($eq->id); ?>"><?php echo e($eq->marca); ?> <?php echo e($eq->modelo); ?> <?php if($eq->numero_serie): ?>(<?php echo e($eq->numero_serie); ?>)<?php endif; ?></option>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                    <option value="novo">+ Cadastrar novo equipamento</option>
                </select>
            </div>
            <div id="novo-equipamento" class="hidden space-y-4 rounded-xl border border-gray-200 bg-gray-50 p-4 dark:border-gray-700 dark:bg-gray-800">
                <p class="text-sm font-medium text-gray-700 dark:text-gray-300">Dados do novo equipamento</p>
                <input type="hidden" name="equipamento_novo" value="0" id="equipamento_novo_hidden">
                <div class="grid gap-4 sm:grid-cols-2">
                    <div>
                        <label for="marca" class="mb-1 block text-sm text-gray-600 dark:text-gray-400">Marca</label>
                        <input type="text" name="marca" id="marca" class="w-full rounded-xl border border-gray-300 px-4 py-2 text-base dark:border-gray-600 dark:bg-gray-900 dark:text-white/90" maxlength="100">
                    </div>
                    <div>
                        <label for="modelo" class="mb-1 block text-sm text-gray-600 dark:text-gray-400">Modelo</label>
                        <input type="text" name="modelo" id="modelo" class="w-full rounded-xl border border-gray-300 px-4 py-2 text-base dark:border-gray-600 dark:bg-gray-900 dark:text-white/90" maxlength="100">
                    </div>
                    <div>
                        <label for="numero_serie" class="mb-1 block text-sm text-gray-600 dark:text-gray-400">Nº Série</label>
                        <input type="text" name="numero_serie" id="numero_serie" class="w-full rounded-xl border border-gray-300 px-4 py-2 text-base dark:border-gray-600 dark:bg-gray-900 dark:text-white/90" maxlength="100">
                    </div>
                    <div>
                        <label for="numero_patrimonio" class="mb-1 block text-sm text-gray-600 dark:text-gray-400">Nº Patrimônio</label>
                        <input type="text" name="numero_patrimonio" id="numero_patrimonio" class="w-full rounded-xl border border-gray-300 px-4 py-2 text-base dark:border-gray-600 dark:bg-gray-900 dark:text-white/90" maxlength="100">
                    </div>
                </div>
                <div>
                    <label for="acessorios" class="mb-1 block text-sm text-gray-600 dark:text-gray-400">Acessórios</label>
                    <textarea name="acessorios" id="acessorios" rows="2" class="w-full rounded-xl border border-gray-300 px-4 py-2 text-base dark:border-gray-600 dark:bg-gray-900 dark:text-white/90" maxlength="500"></textarea>
                </div>
            </div>
            <button type="submit" class="min-h-[48px] rounded-xl bg-brand-500 px-5 py-3 text-base font-semibold text-white hover:bg-brand-600">Gerar Ordem de Serviço</button>
        </form>
        <script>
        document.getElementById('equipamento_id').addEventListener('change', function() {
            var isNovo = this.value === 'novo';
            document.getElementById('novo-equipamento').classList.toggle('hidden', !isNovo);
            document.getElementById('equipamento_novo_hidden').value = isNovo ? '1' : '0';
            document.querySelector('#novo-equipamento input[name="marca"]').required = isNovo;
            document.querySelector('#novo-equipamento input[name="modelo"]').required = isNovo;
        });
        document.getElementById('form-converter-os').addEventListener('submit', function() {
            if (document.getElementById('equipamento_id').value === 'novo') {
                document.getElementById('equipamento_id').removeAttribute('name');
            }
        });
        </script>
        <?php else: ?>
        <p class="text-gray-500 dark:text-gray-400">Cliente não encontrado para converter em ordem de serviço.</p>
        <?php endif; ?>
    </div>

</div>

<?php echo $__env->make('area-cliente::partials.jodit-editor', ['formId' => 'form-resposta-admin', 'height' => 220], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\laragon\www\LivreOS\LivreOS\sistema_livreos\plugins\area-cliente\views\admin\chamados\show.blade.php ENDPATH**/ ?>