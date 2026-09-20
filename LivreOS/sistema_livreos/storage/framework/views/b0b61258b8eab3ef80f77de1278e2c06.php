



<?php $__env->startSection('content'); ?>
<div class="mb-6">
    <h1 class="mb-2 text-2xl font-semibold text-gray-800 dark:text-white/90">Criar Novo Usuário</h1>
    <p class="text-gray-600 dark:text-gray-400">Preencha os dados abaixo organizados em blocos</p>
</div>

<div class="rounded-lg border border-gray-200 bg-white p-6 shadow-theme-sm dark:border-gray-800 dark:bg-gray-800" id="form-user-container">
    <form action="<?php echo e(route('admin.users.store')); ?>" method="POST">
        <?php echo csrf_field(); ?>

        
        <div class="mb-8">
            <h2 class="mb-4 border-b border-gray-200 pb-2 text-lg font-semibold text-gray-800 dark:border-gray-700 dark:text-white/90">1. Dados de Autenticação e Acesso</h2>
            <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                <div>
                    <label for="name" class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300">Nome *</label>
                    <input type="text" id="name" name="name" value="<?php echo e(old('name')); ?>" required class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm text-gray-800 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90">
                    <?php $__errorArgs = ['name'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?><p class="mt-1 text-sm text-error-600 dark:text-error-400"><?php echo e($message); ?></p><?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                </div>
                <div>
                    <label for="email" class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300">E-mail corporativo (único) *</label>
                    <input type="email" id="email" name="email" value="<?php echo e(old('email')); ?>" required class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm text-gray-800 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90">
                    <?php $__errorArgs = ['email'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?><p class="mt-1 text-sm text-error-600 dark:text-error-400"><?php echo e($message); ?></p><?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                </div>
                <div>
                    <label for="password" class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300">Senha *</label>
                    <input type="password" id="password" name="password" required class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm text-gray-800 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90">
                    <?php $__errorArgs = ['password'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?><p class="mt-1 text-sm text-error-600 dark:text-error-400"><?php echo e($message); ?></p><?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                    <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">Mínimo 8 caracteres</p>
                </div>
                <div>
                    <label for="password_confirmation" class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300">Confirmar Senha *</label>
                    <input type="password" id="password_confirmation" name="password_confirmation" required class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm text-gray-800 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90">
                </div>
                <div class="md:col-span-2 flex flex-wrap gap-6">
                    <label class="flex items-center gap-2">
                        <input type="checkbox" name="ativo" value="1" <?php echo e(old('ativo', true) ? 'checked' : ''); ?> class="rounded border-gray-300 text-brand-500 focus:ring-brand-500">
                        <span class="text-sm font-medium text-gray-700 dark:text-gray-300">Status: Ativo</span>
                    </label>
                    <label class="flex items-center gap-2">
                        <input type="checkbox" name="is_admin" value="1" <?php echo e(old('is_admin') ? 'checked' : ''); ?> class="rounded border-gray-300 text-brand-500 focus:ring-brand-500">
                        <span class="text-sm font-medium text-gray-700 dark:text-gray-300">É Administrador</span>
                    </label>
                </div>
                <div class="md:col-span-2">
                    <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300">Perfil de Acesso (Roles)</label>
                    <div class="flex flex-wrap gap-4">
                        <?php $__currentLoopData = $roles; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $role): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                        <label class="flex items-center gap-2">
                            <input type="checkbox" name="roles[]" value="<?php echo e($role->id); ?>" <?php echo e(in_array($role->id, old('roles', [])) ? 'checked' : ''); ?> class="rounded border-gray-300 text-brand-500 focus:ring-brand-500">
                            <span class="text-sm text-gray-700 dark:text-gray-300"><?php echo e($role->name); ?></span>
                        </label>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                    </div>
                </div>
            </div>
        </div>

        
        <div class="mb-8">
            <h2 class="mb-4 border-b border-gray-200 pb-2 text-lg font-semibold text-gray-800 dark:border-gray-700 dark:text-white/90">2. Dados Pessoais</h2>
            <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                <div>
                    <label for="cpf" class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300">CPF</label>
                    <input type="text" id="cpf" name="cpf" value="<?php echo e(old('cpf')); ?>" placeholder="000.000.000-00" maxlength="14" class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm text-gray-800 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90">
                    <?php $__errorArgs = ['cpf'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?><p class="mt-1 text-sm text-error-600 dark:text-error-400"><?php echo e($message); ?></p><?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                </div>
                <div>
                    <label for="rg" class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300">RG</label>
                    <input type="text" id="rg" name="rg" value="<?php echo e(old('rg')); ?>" maxlength="20" class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm text-gray-800 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90">
                    <?php $__errorArgs = ['rg'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?><p class="mt-1 text-sm text-error-600 dark:text-error-400"><?php echo e($message); ?></p><?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                </div>
                <div>
                    <label for="rg_orgao_emissor" class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300">Órgão Emissor do RG</label>
                    <input type="text" id="rg_orgao_emissor" name="rg_orgao_emissor" value="<?php echo e(old('rg_orgao_emissor')); ?>" placeholder="Ex: SSP/SP" maxlength="20" class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm text-gray-800 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90">
                    <?php $__errorArgs = ['rg_orgao_emissor'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?><p class="mt-1 text-sm text-error-600 dark:text-error-400"><?php echo e($message); ?></p><?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                </div>
                <div>
                    <label for="telefone" class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300">Telefone / WhatsApp</label>
                    <input type="text" id="telefone" name="telefone" value="<?php echo e(old('telefone')); ?>" placeholder="(00) 00000-0000" maxlength="20" class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm text-gray-800 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90">
                    <?php $__errorArgs = ['telefone'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?><p class="mt-1 text-sm text-error-600 dark:text-error-400"><?php echo e($message); ?></p><?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                </div>
            </div>
        </div>

        
        <div class="mb-8">
            <h2 class="mb-4 border-b border-gray-200 pb-2 text-lg font-semibold text-gray-800 dark:border-gray-700 dark:text-white/90">3. Dados Profissionais / Operacionais</h2>
            <div class="grid grid-cols-1 gap-4 md:grid-cols-3">
                <div>
                    <label for="cargo" class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300">Cargo</label>
                    <input type="text" id="cargo" name="cargo" value="<?php echo e(old('cargo')); ?>" placeholder="Ex: Técnico de campo" maxlength="100" class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm text-gray-800 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90">
                    <?php $__errorArgs = ['cargo'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?><p class="mt-1 text-sm text-error-600 dark:text-error-400"><?php echo e($message); ?></p><?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                </div>
                <div>
                    <label for="departamento" class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300">Departamento</label>
                    <input type="text" id="departamento" name="departamento" value="<?php echo e(old('departamento')); ?>" maxlength="100" class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm text-gray-800 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90">
                    <?php $__errorArgs = ['departamento'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?><p class="mt-1 text-sm text-error-600 dark:text-error-400"><?php echo e($message); ?></p><?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                </div>
                <div>
                    <label for="custo_hora_tecnica" class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300">Custo da Hora Técnica (R$)</label>
                    <input type="number" id="custo_hora_tecnica" name="custo_hora_tecnica" value="<?php echo e(old('custo_hora_tecnica')); ?>" step="0.01" min="0" placeholder="0,00" class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm text-gray-800 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90">
                    <?php $__errorArgs = ['custo_hora_tecnica'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?><p class="mt-1 text-sm text-error-600 dark:text-error-400"><?php echo e($message); ?></p><?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                </div>
            </div>
        </div>

        
        <div class="mb-8">
            <h2 class="mb-4 border-b border-gray-200 pb-2 text-lg font-semibold text-gray-800 dark:border-gray-700 dark:text-white/90">4. Endereço Completo</h2>
            <p class="mb-4 text-sm text-gray-600 dark:text-gray-400">Informe o CEP primeiro para preencher logradouro, bairro, cidade e UF automaticamente.</p>
            <div class="grid grid-cols-1 gap-4 md:grid-cols-2 lg:grid-cols-4">
                <div class="md:col-span-2 lg:col-span-1">
                    <label for="cep" class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300">CEP</label>
                    <div class="flex gap-2">
                        <input type="text" id="cep" name="cep" value="<?php echo e(old('cep')); ?>" placeholder="00000-000" data-endereco-container="#form-user-container" maxlength="10" class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm text-gray-800 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90" aria-describedby="cep-hint">
                        <button type="button" id="btn-buscar-cep" title="Buscar endereço pelo CEP" class="shrink-0 rounded-lg border border-gray-300 bg-gray-100 px-4 py-2.5 text-sm font-medium text-gray-700 hover:bg-gray-200 dark:border-gray-700 dark:bg-gray-700 dark:text-gray-300 dark:hover:bg-gray-600">
                            <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                        </button>
                    </div>
                    <p id="cep-hint" class="mt-1 text-xs text-gray-500 dark:text-gray-400">Saia do campo ou clique em buscar para preencher automaticamente (ViaCEP)</p>
                    <p id="cep-status" class="mt-1 text-xs hidden" role="status"></p>
                    <?php $__errorArgs = ['cep'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?><p class="mt-1 text-sm text-error-600 dark:text-error-400"><?php echo e($message); ?></p><?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                </div>
                <div class="md:col-span-2 lg:col-span-3">
                    <label for="logradouro" class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300">Logradouro</label>
                    <input type="text" id="logradouro" name="logradouro" value="<?php echo e(old('logradouro')); ?>" class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm text-gray-800 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90">
                    <?php $__errorArgs = ['logradouro'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?><p class="mt-1 text-sm text-error-600 dark:text-error-400"><?php echo e($message); ?></p><?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                </div>
                <div>
                    <label for="numero" class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300">Número</label>
                    <input type="text" id="numero" name="numero" value="<?php echo e(old('numero')); ?>" maxlength="20" class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm text-gray-800 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90">
                    <?php $__errorArgs = ['numero'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?><p class="mt-1 text-sm text-error-600 dark:text-error-400"><?php echo e($message); ?></p><?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                </div>
                <div>
                    <label for="complemento" class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300">Complemento</label>
                    <input type="text" id="complemento" name="complemento" value="<?php echo e(old('complemento')); ?>" maxlength="100" class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm text-gray-800 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90">
                    <?php $__errorArgs = ['complemento'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?><p class="mt-1 text-sm text-error-600 dark:text-error-400"><?php echo e($message); ?></p><?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                </div>
                <div>
                    <label for="bairro" class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300">Bairro</label>
                    <input type="text" id="bairro" name="bairro" value="<?php echo e(old('bairro')); ?>" maxlength="100" class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm text-gray-800 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90">
                    <?php $__errorArgs = ['bairro'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?><p class="mt-1 text-sm text-error-600 dark:text-error-400"><?php echo e($message); ?></p><?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                </div>
                <div>
                    <label for="uf" class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300">UF</label>
                    <select id="uf" name="uf" class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm text-gray-800 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90">
                        <option value="">Selecione...</option>
                        <?php $__currentLoopData = ['AC','AL','AP','AM','BA','CE','DF','ES','GO','MA','MT','MS','MG','PA','PB','PR','PE','PI','RJ','RN','RS','RO','RR','SC','SP','SE','TO']; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $uf): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                            <option value="<?php echo e($uf); ?>" <?php echo e(old('uf') === $uf ? 'selected' : ''); ?>><?php echo e($uf); ?></option>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                    </select>
                    <?php $__errorArgs = ['uf'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?><p class="mt-1 text-sm text-error-600 dark:text-error-400"><?php echo e($message); ?></p><?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                </div>
                <div>
                    <label for="cidade" class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300">Cidade</label>
                    <select id="cidade" name="cidade" data-selected="<?php echo e(old('cidade')); ?>" class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm text-gray-800 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90">
                        <option value="">Selecione a UF primeiro...</option>
                    </select>
                    <?php $__errorArgs = ['cidade'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?><p class="mt-1 text-sm text-error-600 dark:text-error-400"><?php echo e($message); ?></p><?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                </div>
            </div>
        </div>

        <?php if(function_exists('do_action')): ?>
        <?php do_action('admin.users.form.extra', null); ?>
        <?php endif; ?>

        <div class="flex gap-3 border-t border-gray-200 pt-4 dark:border-gray-700">
            <button type="submit" class="rounded-lg bg-brand-500 px-5 py-2.5 text-sm font-medium text-white hover:bg-brand-600">Criar Usuário</button>
            <a href="<?php echo e(route('admin.users.index')); ?>" class="rounded-lg border border-gray-300 bg-white px-5 py-2.5 text-sm font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-300 dark:hover:bg-gray-700">Cancelar</a>
        </div>
    </form>
</div>

<?php $__env->startPush('scripts'); ?>
<script>
(function() {
    const ibgeCidadesCache = {};
    async function carregarCidadesPorUf(uf) {
        var ufUpper = String(uf || '').trim().toUpperCase();
        if (ufUpper.length !== 2) return [];
        if (ibgeCidadesCache[ufUpper]) return ibgeCidadesCache[ufUpper];
        var resp = await fetch('https://servicodados.ibge.gov.br/api/v1/localidades/estados/' + ufUpper + '/municipios');
        if (!resp.ok) return [];
        var data = await resp.json();
        var cidades = Array.isArray(data) ? data.map(function(c){ return String((c && c.nome) || '').trim(); }).filter(Boolean) : [];
        ibgeCidadesCache[ufUpper] = cidades;
        return cidades;
    }
    async function popularSelectCidade(cidadeEl, ufEl, cidadeSelecionada) {
        if (!cidadeEl || !ufEl) return;
        var uf = String(ufEl.value || '').trim().toUpperCase();
        var cidadeAtual = String(cidadeSelecionada || cidadeEl.dataset.selected || cidadeEl.value || '').trim();
        cidadeEl.innerHTML = '';
        if (uf.length !== 2) {
            cidadeEl.innerHTML = '<option value="">Selecione a UF primeiro...</option>';
            return;
        }
        cidadeEl.innerHTML = '<option value="">Carregando cidades...</option>';
        var cidades = await carregarCidadesPorUf(uf);
        cidadeEl.innerHTML = '<option value="">Selecione...</option>';
        cidades.forEach(function(cidade) {
            var opt = document.createElement('option');
            opt.value = cidade;
            opt.textContent = cidade;
            if (cidadeAtual && cidade.toLowerCase() === cidadeAtual.toLowerCase()) opt.selected = true;
            cidadeEl.appendChild(opt);
        });
        if (cidadeAtual && !cidades.some(function(c){ return c.toLowerCase() === cidadeAtual.toLowerCase(); })) {
            var extra = document.createElement('option');
            extra.value = cidadeAtual;
            extra.textContent = cidadeAtual + ' (fora da lista)';
            extra.selected = true;
            cidadeEl.appendChild(extra);
        }
    }

    function formatCPF(v) {
        v = (v || '').replace(/\D/g, '').slice(0, 11);
        return v.replace(/(\d{3})(\d)/, '$1.$2').replace(/(\d{3})(\d)/, '$1.$2').replace(/(\d{3})(\d{1,2})$/, '$1-$2');
    }
    function formatCEP(v) {
        v = (v || '').replace(/\D/g, '').slice(0, 8);
        return v.length > 5 ? v.replace(/(\d{5})(\d)/, '$1-$2') : v;
    }
    function buscarCep() {
        var cepEl = document.getElementById('cep');
        var statusEl = document.getElementById('cep-status');
        var btn = document.getElementById('btn-buscar-cep');
        if (!cepEl || !statusEl) return;
        var val = (cepEl.value || '').replace(/\D/g, '');
        if (val.length !== 8) {
            statusEl.textContent = 'Informe um CEP com 8 dígitos.';
            statusEl.classList.remove('hidden', 'text-green-600', 'text-error-600');
            statusEl.classList.add('text-amber-600');
            return;
        }
        statusEl.textContent = 'Buscando endereço...';
        statusEl.classList.remove('hidden', 'text-green-600', 'text-error-600', 'text-amber-600');
        statusEl.classList.add('text-gray-600');
        if (btn) btn.disabled = true;
        fetch('https://viacep.com.br/ws/' + val + '/json/')
            .then(function(r) { return r.json(); })
            .then(function(data) {
                if (data.erro) {
                    statusEl.textContent = 'CEP não encontrado.';
                    statusEl.classList.remove('text-gray-600', 'text-green-600');
                    statusEl.classList.add('text-error-600');
                } else {
                    var logradouro = document.getElementById('logradouro'); if (logradouro) logradouro.value = data.logradouro || '';
                    var bairro = document.getElementById('bairro'); if (bairro) bairro.value = data.bairro || '';
                    var cidade = document.getElementById('cidade');
                    var uf = document.getElementById('uf');
                    if (uf) uf.value = (data.uf || '').toUpperCase();
                    if (cidade) {
                        cidade.dataset.selected = data.localidade || '';
                        popularSelectCidade(cidade, uf, data.localidade || '');
                    }
                    statusEl.textContent = 'Endereço preenchido automaticamente.';
                    statusEl.classList.remove('text-gray-600', 'text-error-600');
                    statusEl.classList.add('text-green-600');
                }
            })
            .catch(function() {
                statusEl.textContent = 'Erro ao buscar CEP. Tente novamente.';
                statusEl.classList.remove('text-gray-600', 'text-green-600');
                statusEl.classList.add('text-error-600');
            })
            .finally(function() {
                if (btn) btn.disabled = false;
            });
    }
    var cpf = document.getElementById('cpf');
    if (cpf) cpf.addEventListener('input', function() { this.value = formatCPF(this.value); });
    var cep = document.getElementById('cep');
    if (cep) {
        cep.addEventListener('input', function() { this.value = formatCEP(this.value); });
        cep.addEventListener('blur', buscarCep);
    }
    var btnBuscar = document.getElementById('btn-buscar-cep');
    if (btnBuscar) btnBuscar.addEventListener('click', buscarCep);
    var ufEl = document.getElementById('uf');
    var cidadeEl = document.getElementById('cidade');
    if (ufEl && cidadeEl) {
        popularSelectCidade(cidadeEl, ufEl, cidadeEl.dataset.selected || cidadeEl.value || '');
        ufEl.addEventListener('change', function() {
            cidadeEl.dataset.selected = '';
            popularSelectCidade(cidadeEl, ufEl, '');
        });
    }
})();
</script>
<?php $__env->stopPush(); ?>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\laragon\www\LivreOS\LivreOS\sistema_livreos\resources\views\admin\users\create.blade.php ENDPATH**/ ?>