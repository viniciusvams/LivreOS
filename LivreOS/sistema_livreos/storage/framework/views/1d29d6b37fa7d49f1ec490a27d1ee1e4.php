


<div class="space-y-6">
    <div>
        <h4 class="text-base font-semibold text-gray-800 dark:text-white/90">Cliente: <?php echo e($cliente->nome ?: $cliente->razao_social ?: '#' . $cliente->id); ?></h4>
    </div>

    <?php if($usuarios->isEmpty()): ?>
        
        <div class="space-y-4">
            <p class="text-sm text-gray-600 dark:text-gray-400">Nenhum usuário vinculado. Cadastre um novo ou vincule um existente.</p>

            
            <div id="formNovoUsuario" class="rounded-lg border border-gray-200 p-4 dark:border-gray-700">
                <h5 class="mb-3 text-sm font-medium text-gray-700 dark:text-gray-300">Cadastrar novo usuário</h5>
                <form method="POST" action="<?php echo e(route('plugin.area-cliente.configuracoes.clientes.usuarios.store', $cliente)); ?>">
                    <?php echo csrf_field(); ?>
                    <input type="hidden" name="busca" value="<?php echo e(request('busca')); ?>">
                    <input type="hidden" name="portal" value="<?php echo e(request('portal')); ?>">
                    <input type="hidden" name="page" value="<?php echo e(request('page')); ?>">
                    <div class="grid gap-3 sm:grid-cols-2">
                        <div>
                            <label class="mb-1 block text-xs font-medium text-gray-600 dark:text-gray-400">Nome *</label>
                            <input type="text" name="name" required class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm dark:border-gray-700 dark:bg-gray-900 dark:text-white/90">
                        </div>
                        <div>
                            <label class="mb-1 block text-xs font-medium text-gray-600 dark:text-gray-400">E-mail *</label>
                            <input type="email" name="email" required class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm dark:border-gray-700 dark:bg-gray-900 dark:text-white/90">
                        </div>
                        <div>
                            <label class="mb-1 block text-xs font-medium text-gray-600 dark:text-gray-400">Senha *</label>
                            <input type="password" name="password" required minlength="8" class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm dark:border-gray-700 dark:bg-gray-900 dark:text-white/90">
                        </div>
                        <div>
                            <label class="mb-1 block text-xs font-medium text-gray-600 dark:text-gray-400">Confirmar senha *</label>
                            <input type="password" name="password_confirmation" required minlength="8" class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm dark:border-gray-700 dark:bg-gray-900 dark:text-white/90">
                        </div>
                        <div class="sm:col-span-2">
                            <label class="flex items-center gap-2">
                                <input type="checkbox" name="ativo" value="1" checked class="rounded border-gray-300 text-brand-500 focus:ring-brand-500">
                                <span class="text-sm text-gray-700 dark:text-gray-300">Ativo</span>
                            </label>
                        </div>
                    </div>
                    <div class="mt-4">
                        <h6 class="mb-2 text-xs font-semibold text-gray-700 dark:text-gray-300">2. Dados Pessoais</h6>
                        <div class="grid gap-3 sm:grid-cols-2">
                            <div>
                                <label class="mb-1 block text-xs font-medium text-gray-600 dark:text-gray-400">CPF</label>
                                <input type="text" name="cpf" placeholder="000.000.000-00" maxlength="14" class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm dark:border-gray-700 dark:bg-gray-900 dark:text-white/90">
                            </div>
                            <div>
                                <label class="mb-1 block text-xs font-medium text-gray-600 dark:text-gray-400">RG</label>
                                <input type="text" name="rg" maxlength="20" class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm dark:border-gray-700 dark:bg-gray-900 dark:text-white/90">
                            </div>
                            <div>
                                <label class="mb-1 block text-xs font-medium text-gray-600 dark:text-gray-400">Órgão Emissor do RG</label>
                                <input type="text" name="rg_orgao_emissor" placeholder="Ex: SSP/SP" maxlength="20" class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm dark:border-gray-700 dark:bg-gray-900 dark:text-white/90">
                            </div>
                            <div>
                                <label class="mb-1 block text-xs font-medium text-gray-600 dark:text-gray-400">Telefone / WhatsApp</label>
                                <input type="text" name="telefone" placeholder="(00) 00000-0000" maxlength="20" class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm dark:border-gray-700 dark:bg-gray-900 dark:text-white/90">
                            </div>
                        </div>
                    </div>
                    <div class="mt-4">
                        <h6 class="mb-2 text-xs font-semibold text-gray-700 dark:text-gray-300">3. Dados Profissionais / Operacionais</h6>
                        <div class="grid gap-3 sm:grid-cols-2">
                            <div>
                                <label class="mb-1 block text-xs font-medium text-gray-600 dark:text-gray-400">Cargo</label>
                                <input type="text" name="cargo" placeholder="Ex: Técnico de campo" maxlength="100" class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm dark:border-gray-700 dark:bg-gray-900 dark:text-white/90">
                            </div>
                            <div>
                                <label class="mb-1 block text-xs font-medium text-gray-600 dark:text-gray-400">Departamento</label>
                                <input type="text" name="departamento" maxlength="100" class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm dark:border-gray-700 dark:bg-gray-900 dark:text-white/90">
                            </div>
                        </div>
                    </div>
                    <div class="mt-4">
                        <h6 class="mb-2 text-xs font-semibold text-gray-700 dark:text-gray-300">4. Endereço Completo</h6>
                        <p class="mb-2 text-xs text-gray-500 dark:text-gray-400">Informe o CEP para preencher automaticamente (ViaCEP).</p>
                        <div class="grid gap-3 sm:grid-cols-2">
                            <div>
                                <label class="mb-1 block text-xs font-medium text-gray-600 dark:text-gray-400">CEP</label>
                                <div class="flex gap-1">
                                    <input type="text" name="cep" placeholder="00000-000" maxlength="10" class="cep-input w-full rounded-lg border border-gray-300 px-3 py-2 text-sm dark:border-gray-700 dark:bg-gray-900 dark:text-white/90">
                                    <button type="button" class="btn-buscar-cep shrink-0 rounded-lg border border-gray-300 bg-gray-100 px-2 py-2 text-sm dark:border-gray-700 dark:bg-gray-700 dark:text-gray-300" title="Buscar CEP">🔍</button>
                                </div>
                            </div>
                            <div class="sm:col-span-2">
                                <label class="mb-1 block text-xs font-medium text-gray-600 dark:text-gray-400">Logradouro</label>
                                <input type="text" name="logradouro" class="cep-logradouro w-full rounded-lg border border-gray-300 px-3 py-2 text-sm dark:border-gray-700 dark:bg-gray-900 dark:text-white/90">
                            </div>
                            <div>
                                <label class="mb-1 block text-xs font-medium text-gray-600 dark:text-gray-400">Número</label>
                                <input type="text" name="numero" maxlength="20" class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm dark:border-gray-700 dark:bg-gray-900 dark:text-white/90">
                            </div>
                            <div>
                                <label class="mb-1 block text-xs font-medium text-gray-600 dark:text-gray-400">Complemento</label>
                                <input type="text" name="complemento" maxlength="100" class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm dark:border-gray-700 dark:bg-gray-900 dark:text-white/90">
                            </div>
                            <div>
                                <label class="mb-1 block text-xs font-medium text-gray-600 dark:text-gray-400">Bairro</label>
                                <input type="text" name="bairro" class="cep-bairro w-full rounded-lg border border-gray-300 px-3 py-2 text-sm dark:border-gray-700 dark:bg-gray-900 dark:text-white/90">
                            </div>
                            <div>
                                <label class="mb-1 block text-xs font-medium text-gray-600 dark:text-gray-400">Cidade</label>
                                <input type="text" name="cidade" class="cep-cidade w-full rounded-lg border border-gray-300 px-3 py-2 text-sm dark:border-gray-700 dark:bg-gray-900 dark:text-white/90">
                            </div>
                            <div>
                                <label class="mb-1 block text-xs font-medium text-gray-600 dark:text-gray-400">UF</label>
                                <input type="text" name="uf" maxlength="2" placeholder="SP" class="cep-uf w-full rounded-lg border border-gray-300 px-3 py-2 text-sm uppercase dark:border-gray-700 dark:bg-gray-900 dark:text-white/90">
                            </div>
                        </div>
                    </div>
                    <div class="mt-4">
                        <button type="submit" class="rounded-lg bg-brand-500 px-4 py-2 text-sm font-medium text-white hover:bg-brand-600">Cadastrar e vincular</button>
                    </div>
                </form>
            </div>

            <?php if($usuariosDisponiveis->isNotEmpty()): ?>
            <div class="border-t border-gray-200 pt-4 dark:border-gray-700">
                <h5 class="mb-3 text-sm font-medium text-gray-700 dark:text-gray-300">Ou vincular usuário existente</h5>
                <form method="POST" action="<?php echo e(route('plugin.area-cliente.configuracoes.clientes.usuarios.vincular', $cliente)); ?>">
                    <?php echo csrf_field(); ?>
                    <input type="hidden" name="busca" value="<?php echo e(request('busca')); ?>">
                    <input type="hidden" name="portal" value="<?php echo e(request('portal')); ?>">
                    <input type="hidden" name="page" value="<?php echo e(request('page')); ?>">
                    <div class="flex gap-2">
                        <select name="user_id" required class="flex-1 rounded-lg border border-gray-300 px-3 py-2 text-sm dark:border-gray-700 dark:bg-gray-900 dark:text-white/90">
                            <option value="">Selecione um usuário...</option>
                            <?php $__currentLoopData = $usuariosDisponiveis; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $u): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                <option value="<?php echo e($u->id); ?>"><?php echo e($u->name); ?> (<?php echo e($u->email); ?>)</option>
                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                        </select>
                        <button type="submit" class="rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-300">Vincular</button>
                    </div>
                </form>
            </div>
            <?php endif; ?>
        </div>
    <?php else: ?>
        
        <div class="space-y-4">
            <div class="space-y-3">
                <?php $__currentLoopData = $usuarios; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $user): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                <div class="flex flex-wrap items-center justify-between gap-3 rounded-lg border border-gray-200 p-3 dark:border-gray-700">
                    <div>
                        <span class="font-medium text-gray-800 dark:text-white/90"><?php echo e($user->name); ?></span>
                        <span class="text-sm text-gray-500 dark:text-gray-400">(<?php echo e($user->email); ?>)</span>
                        <?php if(!$user->ativo): ?>
                            <span class="ml-2 rounded bg-gray-200 px-1.5 py-0.5 text-xs dark:bg-gray-700">Inativo</span>
                        <?php endif; ?>
                    </div>
                    <div class="flex gap-2">
                        <button type="button" onclick="abrirFormEditar(<?php echo e($user->id); ?>)" class="rounded-lg border border-gray-300 bg-white px-3 py-1.5 text-xs font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-300">
                            Editar
                        </button>
                        <form method="POST" action="<?php echo e(route('plugin.area-cliente.configuracoes.clientes.usuarios.desvincular', [$cliente, $user])); ?>" class="inline" onsubmit="return confirm('Desvincular este usuário do cliente?');">
                            <?php echo csrf_field(); ?>
                            <input type="hidden" name="busca" value="<?php echo e(request('busca')); ?>">
                            <input type="hidden" name="portal" value="<?php echo e(request('portal')); ?>">
                            <input type="hidden" name="page" value="<?php echo e(request('page')); ?>">
                            <button type="submit" class="rounded-lg border border-amber-300 bg-amber-50 px-3 py-1.5 text-xs font-medium text-amber-800 hover:bg-amber-100 dark:border-amber-700 dark:bg-amber-900/20 dark:text-amber-400">Desvincular</button>
                        </form>
                    </div>
                </div>

                
                <div id="formEditar<?php echo e($user->id); ?>" class="hidden rounded-lg border border-brand-200 bg-brand-50/30 p-4 dark:border-brand-800 dark:bg-brand-900/10">
                    <h5 class="mb-3 text-sm font-medium text-gray-700 dark:text-gray-300">Editar usuário</h5>
                    <form method="POST" action="<?php echo e(route('plugin.area-cliente.configuracoes.clientes.usuarios.update', [$cliente, $user])); ?>">
                        <?php echo csrf_field(); ?>
                        <?php echo method_field('PUT'); ?>
                        <input type="hidden" name="busca" value="<?php echo e(request('busca')); ?>">
                        <input type="hidden" name="portal" value="<?php echo e(request('portal')); ?>">
                        <input type="hidden" name="page" value="<?php echo e(request('page')); ?>">
                        <div class="grid gap-3 sm:grid-cols-2">
                            <div>
                                <label class="mb-1 block text-xs font-medium text-gray-600 dark:text-gray-400">Nome *</label>
                                <input type="text" name="name" value="<?php echo e($user->name); ?>" required class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm dark:border-gray-700 dark:bg-gray-900 dark:text-white/90">
                            </div>
                            <div>
                                <label class="mb-1 block text-xs font-medium text-gray-600 dark:text-gray-400">E-mail *</label>
                                <input type="email" name="email" value="<?php echo e($user->email); ?>" required class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm dark:border-gray-700 dark:bg-gray-900 dark:text-white/90">
                            </div>
                            <div>
                                <label class="mb-1 block text-xs font-medium text-gray-600 dark:text-gray-400">Nova senha (deixe em branco para manter)</label>
                                <input type="password" name="password" minlength="8" class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm dark:border-gray-700 dark:bg-gray-900 dark:text-white/90">
                            </div>
                            <div>
                                <label class="mb-1 block text-xs font-medium text-gray-600 dark:text-gray-400">Confirmar senha</label>
                                <input type="password" name="password_confirmation" minlength="8" class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm dark:border-gray-700 dark:bg-gray-900 dark:text-white/90">
                            </div>
                            <div class="sm:col-span-2">
                                <label class="flex items-center gap-2">
                                    <input type="checkbox" name="ativo" value="1" <?php echo e($user->ativo ? 'checked' : ''); ?> class="rounded border-gray-300 text-brand-500 focus:ring-brand-500">
                                    <span class="text-sm text-gray-700 dark:text-gray-300">Ativo</span>
                                </label>
                            </div>
                        </div>
                        <div class="mt-4">
                            <h6 class="mb-2 text-xs font-semibold text-gray-700 dark:text-gray-300">2. Dados Pessoais</h6>
                            <div class="grid gap-3 sm:grid-cols-2">
                                <div>
                                    <label class="mb-1 block text-xs font-medium text-gray-600 dark:text-gray-400">CPF</label>
                                    <input type="text" name="cpf" value="<?php echo e(old('cpf', $user->cpf)); ?>" placeholder="000.000.000-00" maxlength="14" class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm dark:border-gray-700 dark:bg-gray-900 dark:text-white/90">
                                </div>
                                <div>
                                    <label class="mb-1 block text-xs font-medium text-gray-600 dark:text-gray-400">RG</label>
                                    <input type="text" name="rg" value="<?php echo e($user->rg); ?>" maxlength="20" class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm dark:border-gray-700 dark:bg-gray-900 dark:text-white/90">
                                </div>
                                <div>
                                    <label class="mb-1 block text-xs font-medium text-gray-600 dark:text-gray-400">Órgão Emissor do RG</label>
                                    <input type="text" name="rg_orgao_emissor" value="<?php echo e($user->rg_orgao_emissor); ?>" placeholder="Ex: SSP/SP" maxlength="20" class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm dark:border-gray-700 dark:bg-gray-900 dark:text-white/90">
                                </div>
                                <div>
                                    <label class="mb-1 block text-xs font-medium text-gray-600 dark:text-gray-400">Telefone / WhatsApp</label>
                                    <input type="text" name="telefone" value="<?php echo e($user->telefone); ?>" placeholder="(00) 00000-0000" maxlength="20" class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm dark:border-gray-700 dark:bg-gray-900 dark:text-white/90">
                                </div>
                            </div>
                        </div>
                        <div class="mt-4">
                            <h6 class="mb-2 text-xs font-semibold text-gray-700 dark:text-gray-300">3. Dados Profissionais / Operacionais</h6>
                            <div class="grid gap-3 sm:grid-cols-2">
                                <div>
                                    <label class="mb-1 block text-xs font-medium text-gray-600 dark:text-gray-400">Cargo</label>
                                    <input type="text" name="cargo" value="<?php echo e($user->cargo); ?>" placeholder="Ex: Técnico de campo" maxlength="100" class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm dark:border-gray-700 dark:bg-gray-900 dark:text-white/90">
                                </div>
                                <div>
                                    <label class="mb-1 block text-xs font-medium text-gray-600 dark:text-gray-400">Departamento</label>
                                    <input type="text" name="departamento" value="<?php echo e($user->departamento); ?>" maxlength="100" class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm dark:border-gray-700 dark:bg-gray-900 dark:text-white/90">
                                </div>
                            </div>
                        </div>
                        <div class="mt-4">
                            <h6 class="mb-2 text-xs font-semibold text-gray-700 dark:text-gray-300">4. Endereço Completo</h6>
                            <div class="grid gap-3 sm:grid-cols-2">
                                <div>
                                    <label class="mb-1 block text-xs font-medium text-gray-600 dark:text-gray-400">CEP</label>
                                    <div class="flex gap-1">
                                        <input type="text" name="cep" value="<?php echo e($user->cep); ?>" placeholder="00000-000" maxlength="10" class="cep-input w-full rounded-lg border border-gray-300 px-3 py-2 text-sm dark:border-gray-700 dark:bg-gray-900 dark:text-white/90">
                                        <button type="button" class="btn-buscar-cep shrink-0 rounded-lg border border-gray-300 bg-gray-100 px-2 py-2 text-sm dark:border-gray-700 dark:bg-gray-700 dark:text-gray-300" title="Buscar CEP">🔍</button>
                                    </div>
                                </div>
                                <div class="sm:col-span-2">
                                    <label class="mb-1 block text-xs font-medium text-gray-600 dark:text-gray-400">Logradouro</label>
                                    <input type="text" name="logradouro" value="<?php echo e($user->logradouro); ?>" class="cep-logradouro w-full rounded-lg border border-gray-300 px-3 py-2 text-sm dark:border-gray-700 dark:bg-gray-900 dark:text-white/90">
                                </div>
                                <div>
                                    <label class="mb-1 block text-xs font-medium text-gray-600 dark:text-gray-400">Número</label>
                                    <input type="text" name="numero" value="<?php echo e($user->numero); ?>" maxlength="20" class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm dark:border-gray-700 dark:bg-gray-900 dark:text-white/90">
                                </div>
                                <div>
                                    <label class="mb-1 block text-xs font-medium text-gray-600 dark:text-gray-400">Complemento</label>
                                    <input type="text" name="complemento" value="<?php echo e($user->complemento); ?>" maxlength="100" class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm dark:border-gray-700 dark:bg-gray-900 dark:text-white/90">
                                </div>
                                <div>
                                    <label class="mb-1 block text-xs font-medium text-gray-600 dark:text-gray-400">Bairro</label>
                                    <input type="text" name="bairro" value="<?php echo e($user->bairro); ?>" class="cep-bairro w-full rounded-lg border border-gray-300 px-3 py-2 text-sm dark:border-gray-700 dark:bg-gray-900 dark:text-white/90">
                                </div>
                                <div>
                                    <label class="mb-1 block text-xs font-medium text-gray-600 dark:text-gray-400">Cidade</label>
                                    <input type="text" name="cidade" value="<?php echo e($user->cidade); ?>" class="cep-cidade w-full rounded-lg border border-gray-300 px-3 py-2 text-sm dark:border-gray-700 dark:bg-gray-900 dark:text-white/90">
                                </div>
                                <div>
                                    <label class="mb-1 block text-xs font-medium text-gray-600 dark:text-gray-400">UF</label>
                                    <input type="text" name="uf" value="<?php echo e($user->uf); ?>" maxlength="2" placeholder="SP" class="cep-uf w-full rounded-lg border border-gray-300 px-3 py-2 text-sm uppercase dark:border-gray-700 dark:bg-gray-900 dark:text-white/90">
                                </div>
                            </div>
                        </div>
                        <div class="mt-4 flex gap-2">
                            <button type="submit" class="rounded-lg bg-brand-500 px-4 py-2 text-sm font-medium text-white hover:bg-brand-600">Salvar</button>
                            <button type="button" onclick="fecharFormEditar(<?php echo e($user->id); ?>)" class="rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-300">Cancelar</button>
                        </div>
                    </form>
                </div>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
            </div>

            
            <?php if($usuariosDisponiveis->isNotEmpty()): ?>
            <div class="border-t border-gray-200 pt-4 dark:border-gray-700">
                <h5 class="mb-3 text-sm font-medium text-gray-700 dark:text-gray-300">Vincular outro usuário ao cliente</h5>
                <form method="POST" action="<?php echo e(route('plugin.area-cliente.configuracoes.clientes.usuarios.vincular', $cliente)); ?>">
                    <?php echo csrf_field(); ?>
                    <input type="hidden" name="busca" value="<?php echo e(request('busca')); ?>">
                    <input type="hidden" name="portal" value="<?php echo e(request('portal')); ?>">
                    <input type="hidden" name="page" value="<?php echo e(request('page')); ?>">
                    <div class="flex gap-2">
                        <select name="user_id" required class="flex-1 rounded-lg border border-gray-300 px-3 py-2 text-sm dark:border-gray-700 dark:bg-gray-900 dark:text-white/90">
                            <option value="">Selecione um usuário...</option>
                            <?php $__currentLoopData = $usuariosDisponiveis; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $u): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                <option value="<?php echo e($u->id); ?>"><?php echo e($u->name); ?> (<?php echo e($u->email); ?>)</option>
                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                        </select>
                        <button type="submit" class="rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-300">Vincular</button>
                    </div>
                </form>
            </div>
            <?php endif; ?>

            
            <div class="border-t border-gray-200 pt-4 dark:border-gray-700">
                <details class="group">
                    <summary class="cursor-pointer text-sm font-medium text-brand-600 hover:text-brand-700 dark:text-brand-400">+ Cadastrar outro usuário</summary>
                    <div class="mt-3 rounded-lg border border-gray-200 p-4 dark:border-gray-700">
                        <form method="POST" action="<?php echo e(route('plugin.area-cliente.configuracoes.clientes.usuarios.store', $cliente)); ?>">
                            <?php echo csrf_field(); ?>
                            <input type="hidden" name="busca" value="<?php echo e(request('busca')); ?>">
                            <input type="hidden" name="portal" value="<?php echo e(request('portal')); ?>">
                            <input type="hidden" name="page" value="<?php echo e(request('page')); ?>">
                            <div class="grid gap-3 sm:grid-cols-2">
                                <div>
                                    <label class="mb-1 block text-xs font-medium text-gray-600 dark:text-gray-400">Nome *</label>
                                    <input type="text" name="name" required class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm dark:border-gray-700 dark:bg-gray-900 dark:text-white/90">
                                </div>
                                <div>
                                    <label class="mb-1 block text-xs font-medium text-gray-600 dark:text-gray-400">E-mail *</label>
                                    <input type="email" name="email" required class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm dark:border-gray-700 dark:bg-gray-900 dark:text-white/90">
                                </div>
                                <div>
                                    <label class="mb-1 block text-xs font-medium text-gray-600 dark:text-gray-400">Senha *</label>
                                    <input type="password" name="password" required minlength="8" class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm dark:border-gray-700 dark:bg-gray-900 dark:text-white/90">
                                </div>
                                <div>
                                    <label class="mb-1 block text-xs font-medium text-gray-600 dark:text-gray-400">Confirmar senha *</label>
                                    <input type="password" name="password_confirmation" required minlength="8" class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm dark:border-gray-700 dark:bg-gray-900 dark:text-white/90">
                                </div>
                                <div class="sm:col-span-2">
                                    <label class="flex items-center gap-2">
                                        <input type="checkbox" name="ativo" value="1" checked class="rounded border-gray-300 text-brand-500 focus:ring-brand-500">
                                        <span class="text-sm text-gray-700 dark:text-gray-300">Ativo</span>
                                    </label>
                                </div>
                            </div>
                            <div class="mt-4">
                                <h6 class="mb-2 text-xs font-semibold text-gray-700 dark:text-gray-300">2. Dados Pessoais</h6>
                                <div class="grid gap-3 sm:grid-cols-2">
                                    <div><label class="mb-1 block text-xs font-medium text-gray-600 dark:text-gray-400">CPF</label><input type="text" name="cpf" placeholder="000.000.000-00" maxlength="14" class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm dark:border-gray-700 dark:bg-gray-900 dark:text-white/90"></div>
                                    <div><label class="mb-1 block text-xs font-medium text-gray-600 dark:text-gray-400">RG</label><input type="text" name="rg" maxlength="20" class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm dark:border-gray-700 dark:bg-gray-900 dark:text-white/90"></div>
                                    <div><label class="mb-1 block text-xs font-medium text-gray-600 dark:text-gray-400">Órgão Emissor do RG</label><input type="text" name="rg_orgao_emissor" placeholder="Ex: SSP/SP" maxlength="20" class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm dark:border-gray-700 dark:bg-gray-900 dark:text-white/90"></div>
                                    <div><label class="mb-1 block text-xs font-medium text-gray-600 dark:text-gray-400">Telefone / WhatsApp</label><input type="text" name="telefone" placeholder="(00) 00000-0000" maxlength="20" class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm dark:border-gray-700 dark:bg-gray-900 dark:text-white/90"></div>
                                </div>
                            </div>
                            <div class="mt-4">
                                <h6 class="mb-2 text-xs font-semibold text-gray-700 dark:text-gray-300">3. Dados Profissionais / Operacionais</h6>
                                <div class="grid gap-3 sm:grid-cols-2">
                                    <div><label class="mb-1 block text-xs font-medium text-gray-600 dark:text-gray-400">Cargo</label><input type="text" name="cargo" placeholder="Ex: Técnico de campo" maxlength="100" class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm dark:border-gray-700 dark:bg-gray-900 dark:text-white/90"></div>
                                    <div><label class="mb-1 block text-xs font-medium text-gray-600 dark:text-gray-400">Departamento</label><input type="text" name="departamento" maxlength="100" class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm dark:border-gray-700 dark:bg-gray-900 dark:text-white/90"></div>
                                </div>
                            </div>
                            <div class="mt-4">
                                <h6 class="mb-2 text-xs font-semibold text-gray-700 dark:text-gray-300">4. Endereço Completo</h6>
                                <div class="grid gap-3 sm:grid-cols-2">
                                    <div><label class="mb-1 block text-xs font-medium text-gray-600 dark:text-gray-400">CEP</label><div class="flex gap-1"><input type="text" name="cep" placeholder="00000-000" maxlength="10" class="cep-input w-full rounded-lg border border-gray-300 px-3 py-2 text-sm dark:border-gray-700 dark:bg-gray-900 dark:text-white/90"><button type="button" class="btn-buscar-cep shrink-0 rounded-lg border border-gray-300 bg-gray-100 px-2 py-2 text-sm dark:border-gray-700 dark:bg-gray-700 dark:text-gray-300" title="Buscar CEP">🔍</button></div></div>
                                    <div class="sm:col-span-2"><label class="mb-1 block text-xs font-medium text-gray-600 dark:text-gray-400">Logradouro</label><input type="text" name="logradouro" class="cep-logradouro w-full rounded-lg border border-gray-300 px-3 py-2 text-sm dark:border-gray-700 dark:bg-gray-900 dark:text-white/90"></div>
                                    <div><label class="mb-1 block text-xs font-medium text-gray-600 dark:text-gray-400">Número</label><input type="text" name="numero" maxlength="20" class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm dark:border-gray-700 dark:bg-gray-900 dark:text-white/90"></div>
                                    <div><label class="mb-1 block text-xs font-medium text-gray-600 dark:text-gray-400">Complemento</label><input type="text" name="complemento" maxlength="100" class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm dark:border-gray-700 dark:bg-gray-900 dark:text-white/90"></div>
                                    <div><label class="mb-1 block text-xs font-medium text-gray-600 dark:text-gray-400">Bairro</label><input type="text" name="bairro" class="cep-bairro w-full rounded-lg border border-gray-300 px-3 py-2 text-sm dark:border-gray-700 dark:bg-gray-900 dark:text-white/90"></div>
                                    <div><label class="mb-1 block text-xs font-medium text-gray-600 dark:text-gray-400">Cidade</label><input type="text" name="cidade" class="cep-cidade w-full rounded-lg border border-gray-300 px-3 py-2 text-sm dark:border-gray-700 dark:bg-gray-900 dark:text-white/90"></div>
                                    <div><label class="mb-1 block text-xs font-medium text-gray-600 dark:text-gray-400">UF</label><input type="text" name="uf" maxlength="2" placeholder="SP" class="cep-uf w-full rounded-lg border border-gray-300 px-3 py-2 text-sm uppercase dark:border-gray-700 dark:bg-gray-900 dark:text-white/90"></div>
                                </div>
                            </div>
                            <div class="mt-4">
                                <button type="submit" class="rounded-lg bg-brand-500 px-4 py-2 text-sm font-medium text-white hover:bg-brand-600">Cadastrar e vincular</button>
                            </div>
                        </form>
                    </div>
                </details>
            </div>
        </div>
    <?php endif; ?>
</div>
<?php /**PATH C:\laragon\www\LivreOS\LivreOS\sistema_livreos\plugins\area-cliente\views\partials\modal-usuario-cliente.blade.php ENDPATH**/ ?>