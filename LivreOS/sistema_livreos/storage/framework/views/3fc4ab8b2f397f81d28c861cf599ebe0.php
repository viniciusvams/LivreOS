



<?php $__env->startSection('content'); ?>
<div class="mb-6 flex flex-wrap items-center justify-between gap-4">
    <div>
        <h1 class="text-2xl font-semibold text-gray-800 dark:text-white/90">Notificações</h1>
        <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Histórico de notificações do sistema para você</p>
    </div>
    <div class="flex gap-2">
        <form method="POST" action="<?php echo e(route('notificacoes.ler-todas')); ?>" class="inline">
            <?php echo csrf_field(); ?>
            <button type="submit"
                class="inline-flex items-center gap-1.5 rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-300 dark:hover:bg-gray-700">
                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                </svg>
                Marcar todas como lidas
            </button>
        </form>
        <form method="POST" action="<?php echo e(route('notificacoes.excluir-todas')); ?>" class="inline"
              onsubmit="return confirm('Excluir todas as notificações? Esta ação não pode ser desfeita.')">
            <?php echo csrf_field(); ?>
            <?php echo method_field('DELETE'); ?>
            <button type="submit"
                class="inline-flex items-center gap-1.5 rounded-lg border border-red-300 bg-white px-4 py-2 text-sm font-medium text-red-600 hover:bg-red-50 dark:border-red-700 dark:bg-gray-800 dark:text-red-400 dark:hover:bg-red-900/20">
                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                </svg>
                Excluir todas
            </button>
        </form>
    </div>
</div>

<?php if(session('success')): ?>
<div class="mb-4 rounded-lg border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-700 dark:border-green-800 dark:bg-green-900/20 dark:text-green-300">
    <?php echo e(session('success')); ?>

</div>
<?php endif; ?>

<?php
    /** Base com subpasta (APP_URL) para fetch de marcar lida / excluir */
    $notificacoesUrlBase = url('/notificacoes');
?>
<div class="rounded-xl border border-gray-200 bg-white shadow-theme-sm dark:border-gray-800 dark:bg-gray-800"
     x-data="notifPage(<?php echo \Illuminate\Support\Js::from($notificacoesUrlBase)->toHtml() ?>)">

    <?php $__empty_1 = true; $__currentLoopData = $notificacoes; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $notif): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
    <div class="flex items-start gap-4 border-b border-gray-100 px-5 py-4 transition-colors last:border-b-0 dark:border-gray-700
                <?php echo e($notif->lida ? 'bg-white dark:bg-gray-800' : 'bg-blue-50/40 dark:bg-blue-900/10'); ?>"
         id="notif-<?php echo e($notif->id); ?>">

        
        <div class="mt-0.5 flex h-9 w-9 shrink-0 items-center justify-center rounded-full <?php echo e($notif->corIcone()); ?>">
            <?php if($notif->icone === 'alerta'): ?>
            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
            </svg>
            <?php elseif($notif->icone === 'erro'): ?>
            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
            </svg>
            <?php elseif($notif->icone === 'sucesso'): ?>
            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
            </svg>
            <?php else: ?>
            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
            </svg>
            <?php endif; ?>
        </div>

        
        <div class="min-w-0 flex-1">
            <div class="flex flex-wrap items-start justify-between gap-2">
                <div>
                    <p class="text-sm font-medium <?php echo e($notif->lida ? 'text-gray-700 dark:text-gray-300' : 'text-gray-900 dark:text-white'); ?>">
                        <?php echo e($notif->titulo); ?>

                        <?php if(!$notif->lida): ?>
                        <span class="ml-1.5 inline-block h-2 w-2 rounded-full bg-blue-500"></span>
                        <?php endif; ?>
                    </p>
                    <?php if($notif->mensagem): ?>
                    <p class="mt-0.5 text-sm text-gray-500 dark:text-gray-400"><?php echo e($notif->mensagem); ?></p>
                    <?php endif; ?>
                    <p class="mt-1 text-xs text-gray-400 dark:text-gray-500">
                        <?php echo e($notif->created_at->diffForHumans()); ?>

                        &middot; <?php echo e(ucfirst($notif->tipo)); ?>

                    </p>
                </div>

                
                <div class="flex shrink-0 items-center gap-2">
                    <?php if($notif->url): ?>
                    <a href="<?php echo e($notif->url); ?>"
                       @click="marcarLida(<?php echo e($notif->id); ?>)"
                       class="text-xs font-medium text-brand-600 hover:underline dark:text-brand-400">
                        Ver →
                    </a>
                    <?php endif; ?>

                    <?php if(!$notif->lida): ?>
                    <button type="button"
                        @click="marcarLida(<?php echo e($notif->id); ?>)"
                        title="Marcar como lida"
                        class="rounded p-1 text-gray-400 hover:bg-gray-100 hover:text-blue-600 dark:hover:bg-gray-700 dark:hover:text-blue-400">
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                        </svg>
                    </button>
                    <?php endif; ?>

                    <button type="button"
                        @click="excluir(<?php echo e($notif->id); ?>)"
                        title="Excluir notificação"
                        class="rounded p-1 text-gray-400 hover:bg-red-50 hover:text-red-600 dark:hover:bg-red-900/20 dark:hover:text-red-400">
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                        </svg>
                    </button>
                </div>
            </div>
        </div>
    </div>
    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
    <div class="flex flex-col items-center justify-center py-16 text-gray-400 dark:text-gray-500">
        <svg class="mb-3 h-12 w-12 opacity-40" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/>
        </svg>
        <p class="text-sm">Nenhuma notificação encontrada</p>
    </div>
    <?php endif; ?>
</div>

<?php echo e($notificacoes->links()); ?>


<script>
function notifPage(baseUrl) {
    baseUrl = (baseUrl || '').replace(/\/$/, '');
    return {
        marcarLida(id) {
            const el = document.getElementById('notif-' + id);
            fetch(baseUrl + '/' + id + '/ler', {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    'Accept': 'application/json',
                    'Content-Type': 'application/json',
                },
            }).then(() => {
                if (el) {
                    el.classList.remove('bg-blue-50/40', 'dark:bg-blue-900/10');
                    el.querySelectorAll('.h-2.w-2.rounded-full.bg-blue-500').forEach(d => d.remove());
                    el.querySelectorAll('[\\@click="marcarLida(' + id + ')"]').forEach(b => {
                        if (b.tagName === 'BUTTON') b.remove();
                    });
                }
            });
        },

        excluir(id) {
            if (!confirm('Excluir esta notificação?')) return;
            const el = document.getElementById('notif-' + id);
            fetch(baseUrl + '/' + id, {
                method: 'DELETE',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    'Accept': 'application/json',
                    'Content-Type': 'application/json',
                },
            }).then(() => {
                if (el) {
                    el.style.transition = 'opacity 0.3s';
                    el.style.opacity = '0';
                    setTimeout(() => el.remove(), 300);
                }
            });
        }
    }
}
</script>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\laragon\www\LivreOS\LivreOS\sistema_livreos\resources\views\notificacoes\index.blade.php ENDPATH**/ ?>