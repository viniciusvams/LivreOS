

<?php $__env->startSection('content'); ?>
<div class="mb-6 flex flex-wrap items-center justify-between gap-3">
    <div>
        <h1 class="mb-2 text-2xl font-semibold text-gray-800 dark:text-white/90"><?php echo e($title); ?></h1>
        <p class="text-gray-600 dark:text-gray-400">Conferência fiscal e financeira antes de gerar títulos.</p>
    </div>
    <a href="<?php echo e(route('plugin.compras.index')); ?>" class="rounded-lg border border-gray-300 px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-600 dark:text-gray-300">Voltar</a>
</div>

<?php if(session('success')): ?>
<div class="mb-4 rounded-lg bg-success-100 px-4 py-3 text-sm text-success-700 dark:bg-success-500/20 dark:text-success-400"><?php echo e(session('success')); ?></div>
<?php endif; ?>
<?php if(session('error')): ?>
<div class="mb-4 rounded-lg bg-error-100 px-4 py-3 text-sm text-error-700 dark:bg-error-500/20 dark:text-error-400"><?php echo e(session('error')); ?></div>
<?php endif; ?>
<?php if(session('warning')): ?>
<div class="mb-4 rounded-lg bg-amber-100 px-4 py-3 text-sm text-amber-800 dark:bg-amber-900/30 dark:text-amber-200"><?php echo e(session('warning')); ?></div>
<?php endif; ?>

<?php if($imp->tipo_fonte === 'pdf'): ?>
<div class="mb-6 rounded-lg border border-amber-300 bg-amber-50 p-4 text-sm text-amber-900 dark:border-amber-700 dark:bg-amber-900/20 dark:text-amber-100">
    <strong>Modo PDF.</strong> A confirmação automática (contas a pagar e estoque) exige XML da NF-e. Use esta tela apenas como rascunho ou complemente no financeiro.
</div>
<?php endif; ?>

<?php if(!empty($imp->parse_warnings)): ?>
<div class="mb-6 rounded-lg border border-blue-200 bg-blue-50 p-4 text-sm text-blue-900 dark:border-blue-800 dark:bg-blue-900/20 dark:text-blue-100">
    <strong>Avisos de parse:</strong>
    <ul class="mt-2 list-disc pl-5">
        <?php $__currentLoopData = array_filter($imp->parse_warnings); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $w): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
        <li><?php echo e($w); ?></li>
        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
    </ul>
</div>
<?php endif; ?>

<?php if($imp->sefaz_resultado): ?>
<div class="mb-6 rounded-lg border border-gray-200 bg-gray-50 p-4 text-sm dark:border-gray-700 dark:bg-gray-900">
    <strong>SEFAZ / chave:</strong> <?php echo e($imp->sefaz_resultado['mensagem'] ?? '—'); ?>

</div>
<?php endif; ?>

<div class="mb-6 grid gap-4 md:grid-cols-2">
    <div class="rounded-lg border border-gray-200 bg-white p-4 dark:border-gray-800 dark:bg-gray-800">
        <h3 class="mb-2 font-semibold text-gray-800 dark:text-white/90">Documento</h3>
        <dl class="space-y-1 text-sm text-gray-600 dark:text-gray-400">
            <div><dt class="inline font-medium">Número / Série:</dt> <dd class="inline"><?php echo e($imp->numero); ?> / <?php echo e($imp->serie); ?></dd></div>
            <div><dt class="inline font-medium">Emissão:</dt> <dd class="inline"><?php echo e($imp->data_emissao?->format('d/m/Y') ?? '—'); ?></dd></div>
            <div><dt class="inline font-medium">Chave:</dt> <dd class="inline font-mono text-xs break-all"><?php echo e($imp->chave_acesso ?? '—'); ?></dd></div>
            <div><dt class="inline font-medium">Total NF (vNF):</dt> <dd class="inline">R$ <?php echo e(number_format((float)($imp->totais['vNF'] ?? 0), 2, ',', '.')); ?></dd></div>
        </dl>
    </div>
    <div class="rounded-lg border border-gray-200 bg-white p-4 dark:border-gray-800 dark:bg-gray-800">
        <h3 class="mb-2 font-semibold text-gray-800 dark:text-white/90">Emitente (fornecedor)</h3>
        <dl class="space-y-1 text-sm text-gray-600 dark:text-gray-400">
            <div><dt class="inline font-medium">CNPJ:</dt> <dd class="inline"><?php echo e($imp->fornecedor_cnpj); ?></dd></div>
            <div><dt class="inline font-medium">Razão:</dt> <dd class="inline"><?php echo e($imp->fornecedor_nome); ?></dd></div>
            <div><dt class="inline font-medium">Cadastro ERP:</dt> <dd class="inline"><?php echo e($imp->fornecedor ? ($imp->fornecedor->nome ?? 'ID '.$imp->fornecedor_id) : 'Não vinculado'); ?></dd></div>
        </dl>
    </div>
</div>

<?php if(count($duplicatas)): ?>
<div class="mb-6 rounded-lg border border-gray-200 bg-white p-4 dark:border-gray-800 dark:bg-gray-800">
    <h3 class="mb-3 font-semibold text-gray-800 dark:text-white/90">Parcelas (duplicatas)</h3>
    <div class="overflow-x-auto">
        <table class="min-w-full text-sm">
            <thead><tr class="border-b border-gray-200 text-left dark:border-gray-700">
                <th class="py-2 pr-4">Nº</th>
                <th class="py-2 pr-4">Vencimento</th>
                <th class="py-2">Valor</th>
            </tr></thead>
            <tbody>
                <?php $__currentLoopData = $duplicatas; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $d): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                <tr class="border-b border-gray-100 dark:border-gray-700/50">
                    <td class="py-2 pr-4"><?php echo e($d['nDup'] ?? '—'); ?></td>
                    <td class="py-2 pr-4"><?php echo e($d['dVenc'] ?? '—'); ?></td>
                    <td class="py-2">R$ <?php echo e(number_format((float)($d['vDup'] ?? 0), 2, ',', '.')); ?></td>
                </tr>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
            </tbody>
        </table>
    </div>
</div>
<?php endif; ?>

<?php if($imp->itens->isEmpty() && $imp->tipo_fonte === 'xml'): ?>
<div class="mb-6 rounded-lg border border-amber-200 bg-amber-50 p-4 text-sm text-amber-900 dark:border-amber-800 dark:bg-amber-900/20 dark:text-amber-100">
    <strong>Nenhum item de produto foi lido do XML.</strong> Confira se o arquivo é NF-e autorizada (<code class="rounded bg-amber-100 px-1 dark:bg-amber-950">nfeProc</code> ou <code class="rounded bg-amber-100 px-1 dark:bg-amber-950">NFe</code> com grupo <code class="rounded bg-amber-100 px-1 dark:bg-amber-950">det</code>). XML resumido ou de outro documento não gera linhas de importação.
</div>
<?php endif; ?>

<?php if($imp->itens->isNotEmpty()): ?>
<form action="<?php echo e(route('plugin.compras.mapear', $imp)); ?>" method="post" class="mb-6">
    <?php echo csrf_field(); ?>
    <div class="rounded-lg border border-gray-200 bg-white p-4 dark:border-gray-800 dark:bg-gray-800">
        <h3 class="mb-3 font-semibold text-gray-800 dark:text-white/90">Itens e de-para (produto estoque)</h3>
        <div class="overflow-x-auto">
            <table class="min-w-full text-sm">
                <thead>
                    <tr class="border-b border-gray-200 text-left dark:border-gray-700">
                        <th class="py-2 pr-2">#</th>
                        <th class="py-2 pr-2">Cód. forn.</th>
                        <th class="py-2 pr-2">Descrição</th>
                        <th class="py-2 pr-2">NCM/CFOP</th>
                        <th class="py-2 pr-2">Qtd</th>
                        <th class="py-2 pr-2">V. unit.</th>
                        <th class="py-2 pr-2">Produto ERP</th>
                        <th class="py-2">Atualizar custo/qtd</th>
                    </tr>
                </thead>
                <tbody>
                    <?php $__currentLoopData = $imp->itens; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $item): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                    <tr class="border-b border-gray-100 dark:border-gray-700/50">
                        <td class="py-2 pr-2"><?php echo e($item->indice); ?></td>
                        <td class="py-2 pr-2 font-mono text-xs"><?php echo e($item->c_prod); ?></td>
                        <td class="py-2 pr-2 max-w-xs truncate" title="<?php echo e($item->x_prod); ?>"><?php echo e($item->x_prod); ?></td>
                        <td class="py-2 pr-2 text-xs"><?php echo e($item->ncm); ?> / <?php echo e($item->cfop); ?></td>
                        <td class="py-2 pr-2"><?php echo e(number_format((float)$item->q_com, 4, ',', '.')); ?></td>
                        <td class="py-2 pr-2">R$ <?php echo e(number_format((float)$item->v_un_com, 4, ',', '.')); ?></td>
                        <td class="py-2 pr-2">
                            <select name="item[<?php echo e($item->id); ?>][produto_id]" class="w-full max-w-xs rounded-lg border border-gray-300 text-xs dark:border-gray-600 dark:bg-gray-900">
                                <option value="">— Selecionar —</option>
                                <?php $__currentLoopData = $produtos; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $p): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                <option value="<?php echo e($p->id); ?>" <?php if($item->produto_id == $p->id): echo 'selected'; endif; ?>><?php echo e($p->nome); ?> <?php if($p->codigo_sku): ?>(<?php echo e($p->codigo_sku); ?>)<?php endif; ?></option>
                                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                            </select>
                        </td>
                        <td class="py-2">
                            <label class="inline-flex items-center gap-1 text-xs">
                                <input type="hidden" name="item[<?php echo e($item->id); ?>][atualizar_estoque]" value="0">
                                <input type="checkbox" name="item[<?php echo e($item->id); ?>][atualizar_estoque]" value="1" <?php if($item->atualizar_estoque): echo 'checked'; endif; ?>>
                                Sim
                            </label>
                        </td>
                    </tr>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                </tbody>
            </table>
        </div>
        <?php if($imp->status !== 'confirmada'): ?>
        <div class="mt-4 space-y-3 border-t border-gray-100 pt-4 dark:border-gray-700">
            <label class="flex max-w-xl items-start gap-2 text-sm text-gray-700 dark:text-gray-300">
                <input type="hidden" name="criar_produtos_novos" value="0">
                <input type="checkbox" name="criar_produtos_novos" value="1" checked class="mt-0.5 rounded border-gray-300 dark:border-gray-600">
                <span><strong>Criar produtos no ERP</strong> automaticamente para linhas em “Selecionar” (nome, NCM, EAN e preço da NF; SKU interno <code class="rounded bg-gray-100 px-1 text-xs dark:bg-gray-900">NF-IMP…</code>). Se o EAN já existir, vincula ao cadastro existente.</span>
            </label>
            <div>
                <button type="submit" class="rounded-lg bg-gray-700 px-4 py-2 text-sm font-medium text-white hover:bg-gray-800 dark:bg-gray-600">Salvar de-para</button>
            </div>
        </div>
        <?php endif; ?>
    </div>
</form>
<?php endif; ?>

<?php if($imp->status !== 'confirmada' && $imp->tipo_fonte === 'xml'): ?>
<form action="<?php echo e(route('plugin.compras.confirmar', $imp)); ?>" method="post" class="rounded-lg border border-green-200 bg-green-50 p-6 dark:border-green-900 dark:bg-green-900/20">
    <?php echo csrf_field(); ?>
    <h3 class="mb-4 text-lg font-semibold text-gray-800 dark:text-white/90">Confirmar entrada e gerar contas a pagar</h3>
    <div class="grid gap-4 md:grid-cols-2 lg:grid-cols-3">
        <div>
            <label class="mb-1 block text-sm font-medium">Forma de pagamento *</label>
            <select name="forma_pagamento_id" required class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm dark:border-gray-600 dark:bg-gray-900">
                <?php $__currentLoopData = $formas; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $f): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                <option value="<?php echo e($f->id); ?>"><?php echo e($f->nome); ?></option>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
            </select>
        </div>
        <div>
            <label class="mb-1 block text-sm font-medium">Conta bancária (saída)</label>
            <select name="conta_bancaria_id" class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm dark:border-gray-600 dark:bg-gray-900">
                <option value="">—</option>
                <?php $__currentLoopData = $contasBancarias; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $cb): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                <option value="<?php echo e($cb->id); ?>"><?php echo e($cb->nome); ?></option>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
            </select>
        </div>
        <div class="md:col-span-2 lg:col-span-1">
            <label class="mb-1 block text-sm font-medium">Plano de contas (despesa) *</label>
            <select name="plano_conta_id" required class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm dark:border-gray-600 dark:bg-gray-900">
                <?php $__currentLoopData = $planos; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $pl): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                <option value="<?php echo e($pl->id); ?>"><?php echo e($pl->codigo ? $pl->codigo.' — ' : ''); ?><?php echo e($pl->nome); ?></option>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
            </select>
        </div>
    </div>
    <?php if(!$imp->fornecedor_id): ?>
    <div class="mt-4">
        <label class="inline-flex items-center gap-2 text-sm">
            <input type="hidden" name="criar_fornecedor" value="0">
            <input type="checkbox" name="criar_fornecedor" value="1" checked>
            Cadastrar fornecedor (PJ) automaticamente se não existir
        </label>
    </div>
    <?php endif; ?>
    <p class="mt-4 text-xs text-gray-600 dark:text-gray-400">Serão criados <?php echo e(count($duplicatas)); ?> título(s) em Contas a Pagar; produtos com de-para terão quantidade e custo médio atualizados.<?php if(($imp->tipo_fonte ?? '') === 'xml'): ?> Importação XML: se o fornecedor já estiver vinculado pelo CNPJ, o sistema completa endereço, contato e dados fiscais ainda vazios com a NF-e e com a consulta pública de CNPJ (sem sobrescrever o que já estiver preenchido).<?php endif; ?></p>
    <button type="submit" class="mt-4 rounded-lg bg-green-600 px-6 py-2.5 text-sm font-medium text-white hover:bg-green-700">Confirmar entrada</button>
</form>
<?php elseif($imp->status === 'confirmada'): ?>
<div class="rounded-lg border border-gray-200 bg-white p-4 text-sm text-gray-600 dark:border-gray-800 dark:bg-gray-800 dark:text-gray-400">
    Importação confirmada em <?php echo e($imp->confirmed_at?->format('d/m/Y H:i')); ?>. Consulte o financeiro — Contas a Pagar.
</div>
<?php endif; ?>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\laragon\www\LivreOS\LivreOS\sistema_livreos\plugins\compras\views\revisar.blade.php ENDPATH**/ ?>