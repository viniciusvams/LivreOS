



<?php $__env->startSection('content'); ?>
<div class="mb-6 flex flex-wrap items-center justify-between gap-3">
    <div>
        <h1 class="mb-2 text-2xl font-semibold text-gray-800 dark:text-white/90">Auditoria de Login e Segurança</h1>
        <p class="text-gray-600 dark:text-gray-400">Registro de tentativas de login, bloqueios anti-bot e limite de tentativas</p>
    </div>
    <form method="POST" action="<?php echo e(route('admin.audit-login.clear')); ?>" onsubmit="return confirm('Tem certeza que deseja limpar os registros da auditoria de login? Esta ação não pode ser desfeita.');">
        <?php echo csrf_field(); ?>
        <input type="hidden" name="evento" value="<?php echo e(request('evento')); ?>">
        <input type="hidden" name="resultado" value="<?php echo e(request('resultado')); ?>">
        <input type="hidden" name="user_id" value="<?php echo e(request('user_id')); ?>">
        <input type="hidden" name="email" value="<?php echo e(request('email')); ?>">
        <input type="hidden" name="ip" value="<?php echo e(request('ip')); ?>">
        <input type="hidden" name="data_de" value="<?php echo e(request('data_de')); ?>">
        <input type="hidden" name="data_ate" value="<?php echo e(request('data_ate')); ?>">
        <button type="submit" class="rounded-lg border border-red-300 bg-red-50 px-4 py-2.5 text-sm font-medium text-red-700 hover:bg-red-100 dark:border-red-800 dark:bg-red-900/30 dark:text-red-300 dark:hover:bg-red-900/50">
            Limpar registros
        </button>
    </form>
</div>

<div class="mb-6 rounded-lg border border-gray-200 bg-white p-4 shadow-theme-sm dark:border-gray-800 dark:bg-gray-800">
    <form method="GET" action="<?php echo e(route('admin.audit-login.index')); ?>" class="flex flex-wrap items-end gap-4">
        <div>
            <label class="mb-1 block text-xs font-medium text-gray-700 dark:text-gray-300">Evento</label>
            <select name="evento" class="rounded-lg border border-gray-300 px-3 py-2 text-sm dark:border-gray-700 dark:bg-gray-900 dark:text-white/90">
                <option value="">Todos</option>
                <?php $__currentLoopData = ['login_success', 'invalid_credentials', 'rate_limited', 'honeypot', 'robot_token']; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $evento): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                    <option value="<?php echo e($evento); ?>" <?php echo e(request('evento') === $evento ? 'selected' : ''); ?>>
                        <?php echo e(\App\Models\AuditLoginAcesso::eventoLabel($evento)); ?>

                    </option>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
            </select>
        </div>
        <div>
            <label class="mb-1 block text-xs font-medium text-gray-700 dark:text-gray-300">Resultado</label>
            <select name="resultado" class="rounded-lg border border-gray-300 px-3 py-2 text-sm dark:border-gray-700 dark:bg-gray-900 dark:text-white/90">
                <option value="">Todos</option>
                <?php $__currentLoopData = ['success', 'failed', 'blocked']; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $resultado): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                    <option value="<?php echo e($resultado); ?>" <?php echo e(request('resultado') === $resultado ? 'selected' : ''); ?>>
                        <?php echo e(\App\Models\AuditLoginAcesso::resultadoLabel($resultado)); ?>

                    </option>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
            </select>
        </div>
        <div>
            <label class="mb-1 block text-xs font-medium text-gray-700 dark:text-gray-300">Usuário</label>
            <select name="user_id" class="rounded-lg border border-gray-300 px-3 py-2 text-sm dark:border-gray-700 dark:bg-gray-900 dark:text-white/90">
                <option value="">Todos</option>
                <?php $__currentLoopData = $users; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $u): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                    <option value="<?php echo e($u->id); ?>" <?php echo e(request('user_id') == $u->id ? 'selected' : ''); ?>><?php echo e($u->name); ?></option>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
            </select>
        </div>
        <div>
            <label class="mb-1 block text-xs font-medium text-gray-700 dark:text-gray-300">E-mail</label>
            <input type="text" name="email" value="<?php echo e(request('email')); ?>" class="rounded-lg border border-gray-300 px-3 py-2 text-sm dark:border-gray-700 dark:bg-gray-900 dark:text-white/90">
        </div>
        <div>
            <label class="mb-1 block text-xs font-medium text-gray-700 dark:text-gray-300">IP</label>
            <input type="text" name="ip" value="<?php echo e(request('ip')); ?>" class="rounded-lg border border-gray-300 px-3 py-2 text-sm dark:border-gray-700 dark:bg-gray-900 dark:text-white/90">
        </div>
        <div>
            <label class="mb-1 block text-xs font-medium text-gray-700 dark:text-gray-300">Data de</label>
            <input type="date" name="data_de" value="<?php echo e(request('data_de')); ?>" class="rounded-lg border border-gray-300 px-3 py-2 text-sm dark:border-gray-700 dark:bg-gray-900 dark:text-white/90">
        </div>
        <div>
            <label class="mb-1 block text-xs font-medium text-gray-700 dark:text-gray-300">Data até</label>
            <input type="date" name="data_ate" value="<?php echo e(request('data_ate')); ?>" class="rounded-lg border border-gray-300 px-3 py-2 text-sm dark:border-gray-700 dark:bg-gray-900 dark:text-white/90">
        </div>
        <div class="flex gap-2">
            <button type="submit" class="rounded-lg bg-brand-500 px-4 py-2 text-sm font-medium text-white hover:bg-brand-600">Filtrar</button>
            <a href="<?php echo e(route('admin.audit-login.index')); ?>" class="rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-300">Limpar</a>
        </div>
    </form>
</div>

<div class="overflow-hidden rounded-lg border border-gray-200 bg-white shadow-theme-sm dark:border-gray-800 dark:bg-gray-800">
    <div class="overflow-x-auto">
        <table class="w-full">
            <thead class="bg-gray-50 dark:bg-gray-900">
                <tr>
                    <th class="px-6 py-4 text-left text-sm font-medium text-gray-700 dark:text-gray-300">Data/Hora</th>
                    <th class="px-6 py-4 text-left text-sm font-medium text-gray-700 dark:text-gray-300">Usuário</th>
                    <th class="px-6 py-4 text-left text-sm font-medium text-gray-700 dark:text-gray-300">E-mail</th>
                    <th class="px-6 py-4 text-left text-sm font-medium text-gray-700 dark:text-gray-300">Evento</th>
                    <th class="px-6 py-4 text-left text-sm font-medium text-gray-700 dark:text-gray-300">Resultado</th>
                    <th class="px-6 py-4 text-left text-sm font-medium text-gray-700 dark:text-gray-300">IP</th>
                    <th class="px-6 py-4 text-left text-sm font-medium text-gray-700 dark:text-gray-300">Mensagem</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                <?php $__empty_1 = true; $__currentLoopData = $items; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $item): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                <tr class="hover:bg-gray-50 dark:hover:bg-gray-900">
                    <td class="whitespace-nowrap px-6 py-4 text-sm text-gray-800 dark:text-white/90"><?php echo e($item->created_at?->format('d/m/Y H:i:s')); ?></td>
                    <td class="px-6 py-4 text-sm text-gray-800 dark:text-white/90"><?php echo e($item->user?->name ?? '—'); ?></td>
                    <td class="px-6 py-4 text-sm text-gray-600 dark:text-gray-400"><?php echo e($item->email ?: '—'); ?></td>
                    <td class="px-6 py-4 text-sm text-gray-600 dark:text-gray-400"><?php echo e(\App\Models\AuditLoginAcesso::eventoLabel($item->evento)); ?></td>
                    <td class="px-6 py-4">
                        <?php if($item->resultado === 'success'): ?>
                            <span class="inline-flex items-center rounded-full bg-green-100 px-2.5 py-0.5 text-xs font-medium text-green-700 dark:bg-green-500/20 dark:text-green-400">Sucesso</span>
                        <?php elseif($item->resultado === 'blocked'): ?>
                            <span class="inline-flex items-center rounded-full bg-red-100 px-2.5 py-0.5 text-xs font-medium text-red-700 dark:bg-red-500/20 dark:text-red-400">Bloqueado</span>
                        <?php else: ?>
                            <span class="inline-flex items-center rounded-full bg-amber-100 px-2.5 py-0.5 text-xs font-medium text-amber-700 dark:bg-amber-500/20 dark:text-amber-400">Falha</span>
                        <?php endif; ?>
                    </td>
                    <td class="px-6 py-4 text-sm text-gray-600 dark:text-gray-400"><?php echo e($item->ip_address ?: '—'); ?></td>
                    <td class="max-w-sm px-6 py-4 text-sm text-gray-600 dark:text-gray-400" title="<?php echo e($item->mensagem); ?>"><?php echo e(Str::limit($item->mensagem, 90)); ?></td>
                </tr>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                <tr>
                    <td colspan="7" class="px-6 py-8 text-center text-gray-500 dark:text-gray-400">Nenhum registro encontrado</td>
                </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
    <div class="border-t border-gray-200 px-6 py-4 dark:border-gray-700">
        <?php echo e($items->links()); ?>

    </div>
</div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\laragon\www\LivreOS\LivreOS\sistema_livreos\resources\views\admin\audit-login\index.blade.php ENDPATH**/ ?>