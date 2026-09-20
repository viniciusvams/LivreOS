



<?php $__env->startSection('content'); ?>
<div class="mb-6 flex items-center justify-between">
    <div>
        <h1 class="mb-2 text-2xl font-semibold text-gray-800 dark:text-white/90">Detalhes do Serviço</h1>
        <p class="text-gray-600 dark:text-gray-400"><?php echo e($servico->nome); ?></p>
    </div>
    <div class="flex gap-2">
        <a href="<?php echo e(route('servicos.edit', $servico)); ?>" class="rounded-lg bg-brand-500 px-4 py-2.5 text-sm font-medium text-white hover:bg-brand-600">Editar</a>
        <a href="<?php echo e(route('servicos.index')); ?>" class="rounded-lg border border-gray-300 bg-white px-4 py-2.5 text-sm font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-300">Voltar</a>
    </div>
</div>

<div class="grid grid-cols-1 gap-6 lg:grid-cols-2">
    <div class="rounded-lg border border-gray-200 bg-white p-6 shadow-theme-sm dark:border-gray-800 dark:bg-gray-800">
        <h2 class="mb-4 text-lg font-semibold text-gray-800 dark:text-white/90">Campos Básicos</h2>
        <dl class="space-y-3">
            <div>
                <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Código interno</dt>
                <dd class="mt-1 text-sm text-gray-800 dark:text-white/90"><?php echo e($servico->codigo_interno); ?></dd>
            </div>
            <div>
                <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Nome</dt>
                <dd class="mt-1 text-sm text-gray-800 dark:text-white/90"><?php echo e($servico->nome); ?></dd>
            </div>
            <div>
                <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Preço</dt>
                <dd class="mt-1 text-sm text-gray-800 dark:text-white/90"><?php echo e($servico->preco ? number_format($servico->preco, 2, ',', '.') : '-'); ?></dd>
            </div>
            <div>
                <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Unidade</dt>
                <dd class="mt-1 text-sm text-gray-800 dark:text-white/90"><?php echo e($servico->unidade); ?></dd>
            </div>
        </dl>
    </div>

    <div class="rounded-lg border border-gray-200 bg-white p-6 shadow-theme-sm dark:border-gray-800 dark:bg-gray-800">
        <h2 class="mb-4 text-lg font-semibold text-gray-800 dark:text-white/90">Campos Opcionais</h2>
        <dl class="space-y-3">
            <div>
                <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">SKU</dt>
                <dd class="mt-1 text-sm text-gray-800 dark:text-white/90"><?php echo e($servico->codigo_sku ?: '-'); ?></dd>
            </div>
            <div>
                <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Categoria</dt>
                <dd class="mt-1 text-sm text-gray-800 dark:text-white/90"><?php echo e($servico->categoria?->nome ?: '-'); ?></dd>
            </div>
            <div>
                <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Valor de custo</dt>
                <dd class="mt-1 text-sm text-gray-800 dark:text-white/90"><?php echo e($servico->valor_custo ?? '-'); ?></dd>
            </div>
            <div>
                <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Valor da comissão</dt>
                <dd class="mt-1 text-sm text-gray-800 dark:text-white/90">
                    <?php if($servico->valor_comissao !== null): ?>
                        <?php echo e($servico->valor_comissao); ?><?php echo e(($servico->tipo_comissao ?? 'valor') === 'percentual' ? '%' : ' R$'); ?>

                    <?php else: ?>
                        -
                    <?php endif; ?>
                </dd>
            </div>
        </dl>
    </div>

    <div class="rounded-lg border border-gray-200 bg-white p-6 shadow-theme-sm dark:border-gray-800 dark:bg-gray-800">
        <h2 class="mb-4 text-lg font-semibold text-gray-800 dark:text-white/90">Descrição e prazos</h2>
        <p class="text-sm text-gray-700 dark:text-gray-300"><?php echo $servico->descricao ? nl2br(e($servico->descricao)) : '-'; ?></p>
        <div class="mt-4">
            <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Prazos</dt>
            <dd class="mt-1 text-sm text-gray-800 dark:text-white/90"><?php echo $servico->prazos ? nl2br(e($servico->prazos)) : '-'; ?></dd>
        </div>
    </div>

    <div class="rounded-lg border border-gray-200 bg-white p-6 shadow-theme-sm dark:border-gray-800 dark:bg-gray-800">
        <h2 class="mb-4 text-lg font-semibold text-gray-800 dark:text-white/90">Informações fiscais</h2>
        <?php if($servico->informacoes_fiscais): ?>
            <ul class="list-disc pl-4 text-sm text-gray-700 dark:text-gray-300">
                <?php $__currentLoopData = $servico->informacoes_fiscais; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $item): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                    <li><?php echo e($item); ?></li>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
            </ul>
        <?php else: ?>
            <p class="text-sm text-gray-500 dark:text-gray-400">Nenhuma informação fiscal cadastrada</p>
        <?php endif; ?>
    </div>

    <div class="rounded-lg border border-gray-200 bg-white p-6 shadow-theme-sm dark:border-gray-800 dark:bg-gray-800">
        <h2 class="mb-4 text-lg font-semibold text-gray-800 dark:text-white/90">Campos customizados</h2>
        <?php if($servico->campos_customizados): ?>
            <div class="space-y-2">
                <?php $__currentLoopData = $servico->campos_customizados; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $item): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                    <div class="rounded-lg border border-gray-200 px-3 py-2 text-sm dark:border-gray-700">
                        <span class="font-medium"><?php echo e($item['chave'] ?? 'Campo'); ?>:</span>
                        <span><?php echo e($item['valor'] ?? '-'); ?></span>
                    </div>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
            </div>
        <?php else: ?>
            <p class="text-sm text-gray-500 dark:text-gray-400">Nenhum campo customizado cadastrado</p>
        <?php endif; ?>
    </div>

    <div class="rounded-lg border border-gray-200 bg-white p-6 shadow-theme-sm dark:border-gray-800 dark:bg-gray-800 lg:col-span-2">
        <h2 class="mb-4 text-lg font-semibold text-gray-800 dark:text-white/90">Imagens</h2>
        <?php if($servico->imagens->count()): ?>
            <div class="grid grid-cols-2 gap-4 md:grid-cols-4">
                <?php $__currentLoopData = $servico->imagens; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $img): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                    <?php
                        if ($img->tipo === 'url') {
                            $src = $img->url_externa;
                        } else {
                            $path = storage_path('app/public/' . ltrim($img->caminho_local ?? '', '/'));
                            if ($img->caminho_local && file_exists($path)) {
                                $mime = mime_content_type($path) ?: 'image/jpeg';
                                $src = 'data:' . $mime . ';base64,' . base64_encode(file_get_contents($path));
                            } else {
                                $src = asset('storage/' . ltrim($img->caminho_local ?? '', '/'));
                            }
                        }
                        $abrirHref = $img->tipo === 'url' ? $img->url_externa : route('servicos.imagem', $img);
                    ?>
                    <div class="rounded-lg border border-gray-200 p-2 dark:border-gray-700">
                        <img src="<?php echo e($src); ?>" alt="Imagem do serviço" class="h-24 w-full rounded object-cover">
                        <div class="mt-2 flex items-center justify-between text-xs text-gray-600 dark:text-gray-400">
                            <span><?php echo e($img->tipo === 'url' ? 'URL' : 'Upload'); ?></span>
                            <a href="<?php echo e($abrirHref); ?>" target="_blank" class="text-brand-500 hover:underline">Abrir</a>
                        </div>
                    </div>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
            </div>
        <?php else: ?>
            <p class="text-sm text-gray-500 dark:text-gray-400">Nenhuma imagem cadastrada</p>
        <?php endif; ?>
    </div>
</div>

<?php if(function_exists('do_action')): ?>
<?php do_action('admin.servicos.show.extra', $servico); ?>
<?php endif; ?>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\laragon\www\LivreOS\LivreOS\sistema_livreos\resources\views\servicos\show.blade.php ENDPATH**/ ?>