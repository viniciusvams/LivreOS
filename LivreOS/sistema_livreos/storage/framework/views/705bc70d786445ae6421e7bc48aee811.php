

<?php
    $modelo = $checklist->checklistModel;
    $campos = $modelo->campos ?? [];
    $respostas = $checklist->respostas ?? [];
?>

<div class="rounded-lg border border-gray-200 bg-white p-6 shadow-theme-sm dark:border-gray-700 dark:bg-gray-800" data-checklist-id="<?php echo e($checklist->id); ?>">
    <div class="mb-4 flex items-center justify-between">
        <div>
            <h3 class="text-lg font-semibold text-gray-800 dark:text-white/90"><?php echo e($modelo->nome); ?></h3>
            <?php if($modelo->descricao): ?>
                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400"><?php echo e($modelo->descricao); ?></p>
            <?php endif; ?>
        </div>
        <button type="button" onclick="if(confirm('Tem certeza que deseja remover este checklist?')) removerChecklist(<?php echo e($checklist->id); ?>, this.closest('[data-checklist-id]'))" class="text-error-500 hover:text-error-700 dark:text-error-400">
            <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
            </svg>
        </button>
    </div>

    <div class="checklist-form" data-checklist-model-id="<?php echo e($modelo->id); ?>" data-checklist-answer-id="<?php echo e($checklist->id); ?>">
        <?php echo csrf_field(); ?>
        <input type="hidden" name="checklist_model_id" value="<?php echo e($modelo->id); ?>">
        
        <div class="space-y-4">
            <?php $__currentLoopData = $campos; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $index => $campo): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                <div class="checklist-field" data-field-index="<?php echo e($index); ?>">
                    <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300">
                        <?php echo e($campo['label'] ?? 'Campo ' . ($index + 1)); ?>

                        <?php if($campo['obrigatorio'] ?? false): ?>
                            <span class="text-error-500">*</span>
                        <?php endif; ?>
                    </label>
                    
                    <?php if(($campo['tipo'] ?? 'texto') === 'texto'): ?>
                        <input type="text" 
                               name="respostas[<?php echo e($index); ?>]" 
                               value="<?php echo e($respostas[$index] ?? ''); ?>"
                               <?php echo e(($campo['obrigatorio'] ?? false) ? 'required' : ''); ?>

                               class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm text-gray-800 focus:border-brand-500 focus:ring-brand-500 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90">
                    
                    <?php elseif($campo['tipo'] === 'numero'): ?>
                        <input type="number" 
                               step="0.01"
                               name="respostas[<?php echo e($index); ?>]" 
                               value="<?php echo e($respostas[$index] ?? ''); ?>"
                               <?php echo e(($campo['obrigatorio'] ?? false) ? 'required' : ''); ?>

                               class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm text-gray-800 focus:border-brand-500 focus:ring-brand-500 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90">
                    
                    <?php elseif($campo['tipo'] === 'data'): ?>
                        <input type="date" 
                               name="respostas[<?php echo e($index); ?>]" 
                               value="<?php echo e($respostas[$index] ?? ''); ?>"
                               <?php echo e(($campo['obrigatorio'] ?? false) ? 'required' : ''); ?>

                               class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm text-gray-800 focus:border-brand-500 focus:ring-brand-500 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90">
                    
                    <?php elseif($campo['tipo'] === 'checkbox'): ?>
                        <div class="flex items-center">
                            <input type="checkbox" 
                                   name="respostas[<?php echo e($index); ?>]" 
                                   value="1"
                                   <?php echo e(($respostas[$index] ?? false) ? 'checked' : ''); ?>

                                   class="h-4 w-4 rounded border-gray-300 text-brand-500 focus:ring-brand-500">
                            <label class="ml-2 text-sm text-gray-700 dark:text-gray-300">Sim</label>
                        </div>
                    
                    <?php elseif($campo['tipo'] === 'selecao'): ?>
                        <select name="respostas[<?php echo e($index); ?>]" 
                                <?php echo e(($campo['obrigatorio'] ?? false) ? 'required' : ''); ?>

                                class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm text-gray-800 focus:border-brand-500 focus:ring-brand-500 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90">
                            <option value="">Selecione...</option>
                            <?php $__currentLoopData = ($campo['opcoes'] ?? []); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $opcao): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                <option value="<?php echo e($opcao); ?>" <?php echo e(($respostas[$index] ?? '') === $opcao ? 'selected' : ''); ?>>
                                    <?php echo e($opcao); ?>

                                </option>
                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                        </select>
                    <?php endif; ?>
                </div>
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
        </div>

        <div class="mt-4">
            <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300">Observações</label>
            <textarea name="observacoes" rows="2" class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm text-gray-800 focus:border-brand-500 focus:ring-brand-500 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90"><?php echo e($checklist->observacoes ?? ''); ?></textarea>
        </div>

        <div class="mt-4 flex justify-end">
            <button type="button" class="btn-salvar-checklist rounded-lg bg-brand-500 px-4 py-2 text-sm font-medium text-white hover:bg-brand-600">
                Salvar Checklist
            </button>
        </div>
    </div>
</div>
<?php /**PATH C:\laragon\www\LivreOS\LivreOS\sistema_livreos\resources\views\ordens_servico\_checklist_card.blade.php ENDPATH**/ ?>