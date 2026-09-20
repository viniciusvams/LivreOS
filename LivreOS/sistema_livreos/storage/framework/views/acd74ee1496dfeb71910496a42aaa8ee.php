

<?php
    $isEdit = isset($servico) && $servico->exists;
    $servico = $isEdit ? $servico : new \App\Models\Servico();
    $informacoesFiscais = old('informacoes_fiscais', $servico->informacoes_fiscais ?: []);
    $camposCustomizados = old('campos_customizados', $servico->campos_customizados ?: []);
?>

<form action="<?php echo e($isEdit ? route('servicos.update', $servico) : route('servicos.store')); ?>" method="POST" enctype="multipart/form-data">
    <?php echo csrf_field(); ?>
    <?php if($isEdit): ?>
        <?php echo method_field('PUT'); ?>
    <?php endif; ?>

    <div class="grid grid-cols-1 gap-6 lg:grid-cols-2">
        <div class="rounded-lg border border-gray-200 bg-white p-6 shadow-theme-sm dark:border-gray-800 dark:bg-gray-800">
            <h2 class="mb-4 text-lg font-semibold text-gray-800 dark:text-white/90">Campos Básicos</h2>
            <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                <div>
                    <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300">Código interno</label>
                    <input type="text" value="<?php echo e($servico->codigo_interno ?: 'Será gerado automaticamente'); ?>" readonly class="w-full rounded-lg border border-gray-300 bg-gray-50 px-4 py-2.5 text-sm text-gray-600 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-400">
                </div>
                <div>
                    <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300">Nome <span class="text-error-500">*</span></label>
                    <input type="text" name="nome" required value="<?php echo e(old('nome', $servico->nome)); ?>" class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm text-gray-800 focus:border-brand-500 focus:ring-brand-500 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90">
                </div>
                <div>
                    <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300">Preço</label>
                    <input type="number" step="0.01" min="0" name="preco" value="<?php echo e(old('preco', $servico->preco)); ?>" class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm text-gray-800 focus:border-brand-500 focus:ring-brand-500 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90">
                </div>
                <div>
                    <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300">Unidade <span class="text-error-500">*</span></label>
                    <?php
                        $unidades = [
                            'hora' => 'Hora',
                            'visita' => 'Visita',
                            'projeto' => 'Projeto',
                            'diaria' => 'Diária',
                            'mensal' => 'Mensal',
                            'instalacao' => 'Instalação',
                            'manutencao' => 'Manutenção',
                            'pacote' => 'Pacote',
                            'unidade' => 'Unidade',
                            'outro' => 'Outro',
                        ];
                        $unidadeAtual = old('unidade', $servico->unidade);
                        $unidadesPadrao = array_keys($unidades);
                        $unidadeEhOutro = $unidadeAtual && !in_array($unidadeAtual, $unidadesPadrao, true);
                    ?>
                    <select name="unidade" id="servico_unidade" required class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm text-gray-800 focus:border-brand-500 focus:ring-brand-500 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90">
                        <option value="">Selecione...</option>
                        <?php $__currentLoopData = $unidades; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $valor => $label): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                            <option value="<?php echo e($valor); ?>" <?php echo e((!$unidadeEhOutro && $unidadeAtual === $valor) ? 'selected' : ''); ?>><?php echo e($label); ?></option>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                    </select>
                    <input type="text" name="unidade_outro" id="servico_unidade_outro" value="<?php echo e($unidadeEhOutro ? $unidadeAtual : old('unidade_outro')); ?>" placeholder="Informe a unidade" class="mt-2 w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm text-gray-800 focus:border-brand-500 focus:ring-brand-500 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90 <?php echo e($unidadeEhOutro ? '' : 'hidden'); ?>">
                </div>
            </div>
        </div>

        <div class="rounded-lg border border-gray-200 bg-white p-6 shadow-theme-sm dark:border-gray-800 dark:bg-gray-800">
            <h2 class="mb-4 text-lg font-semibold text-gray-800 dark:text-white/90">Opcional</h2>
            <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                <div>
                    <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300">Código (SKU)</label>
                    <input type="text" name="codigo_sku" value="<?php echo e(old('codigo_sku', $servico->codigo_sku)); ?>" class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm text-gray-800 focus:border-brand-500 focus:ring-brand-500 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90">
                </div>
                <div>
                    <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300">Categoria</label>
                    <div class="flex gap-2">
                        <select name="categoria_id" class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm text-gray-800 focus:border-brand-500 focus:ring-brand-500 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90">
                            <option value="">Selecione...</option>
                            <?php $__currentLoopData = $categorias; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $categoria): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                            <option value="<?php echo e($categoria->id); ?>"
                                <?php echo e(old('categoria_id', session('categoria_servico_id', $servico->categoria_id)) == $categoria->id ? 'selected' : ''); ?>>
                                <?php echo e($categoria->nome); ?>

                            </option>
                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                        </select>
                        <button type="button" id="openCategoriaModal" class="rounded-lg border border-gray-300 px-3 py-2.5 text-sm font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-700 dark:text-gray-300">+</button>
                    </div>
                </div>
                <div>
                    <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300">Valor de custo</label>
                    <input type="number" step="0.01" min="0" name="valor_custo" value="<?php echo e(old('valor_custo', $servico->valor_custo)); ?>" class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm text-gray-800 focus:border-brand-500 focus:ring-brand-500 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90">
                </div>
                <div>
                    <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300">Valor da comissão</label>
                    <div class="flex gap-2">
                        <input type="number" step="0.01" min="0" name="valor_comissao" value="<?php echo e(old('valor_comissao', $servico->valor_comissao)); ?>" class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm text-gray-800 focus:border-brand-500 focus:ring-brand-500 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90">
                        <select name="tipo_comissao" class="w-36 rounded-lg border border-gray-300 px-3 py-2.5 text-sm text-gray-800 focus:border-brand-500 focus:ring-brand-500 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90">
                            <option value="valor" <?php echo e(old('tipo_comissao', $servico->tipo_comissao ?? 'valor') === 'valor' ? 'selected' : ''); ?>>R$</option>
                            <option value="percentual" <?php echo e(old('tipo_comissao', $servico->tipo_comissao) === 'percentual' ? 'selected' : ''); ?>>%</option>
                        </select>
                    </div>
                </div>
                <div class="md:col-span-2">
                    <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300">Descrição detalhada</label>
                    <textarea name="descricao" rows="3" class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm text-gray-800 focus:border-brand-500 focus:ring-brand-500 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90"><?php echo e(old('descricao', $servico->descricao)); ?></textarea>
                </div>
                <div class="md:col-span-2">
                    <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300">Prazos</label>
                    <textarea name="prazos" rows="2" placeholder="Ex: execução em até 5 dias úteis; garantia de 90 dias" class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm text-gray-800 focus:border-brand-500 focus:ring-brand-500 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90"><?php echo e(old('prazos', $servico->prazos)); ?></textarea>
                </div>
            </div>
        </div>

        <div class="rounded-lg border border-gray-200 bg-white p-6 shadow-theme-sm dark:border-gray-800 dark:bg-gray-800">
            <div class="mb-4 flex items-center justify-between">
                <h2 class="text-lg font-semibold text-gray-800 dark:text-white/90">Informações fiscais (NFS-e)</h2>
                <button type="button" onclick="adicionarFiscal()" class="rounded-lg border border-gray-300 px-3 py-1.5 text-sm font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-700 dark:text-gray-300">+ Campo</button>
            </div>
            <div id="fiscais_container" class="space-y-3">
                <?php $__currentLoopData = $informacoesFiscais; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $index => $valor): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                    <div class="flex gap-2">
                        <input type="text" name="informacoes_fiscais[<?php echo e($index); ?>]" value="<?php echo e($valor); ?>" placeholder="Ex: Código de serviço municipal" class="w-full rounded-lg border border-gray-300 px-4 py-2 text-sm text-gray-800 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90">
                        <button type="button" onclick="removerLinhaFiscal(this)" class="rounded-lg border border-gray-300 px-3 text-xs text-gray-600 hover:bg-gray-50 dark:border-gray-700 dark:text-gray-300">Remover</button>
                    </div>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
            </div>
        </div>

        <div class="rounded-lg border border-gray-200 bg-white p-6 shadow-theme-sm dark:border-gray-800 dark:bg-gray-800">
            <div class="mb-4 flex items-center justify-between">
                <h2 class="text-lg font-semibold text-gray-800 dark:text-white/90">Campos customizados</h2>
                <button type="button" onclick="adicionarCustomizado()" class="rounded-lg border border-gray-300 px-3 py-1.5 text-sm font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-700 dark:text-gray-300">+ Campo</button>
            </div>
            <div id="customizados_container" class="space-y-3">
                <?php $__currentLoopData = $camposCustomizados; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $index => $item): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                    <div class="grid grid-cols-1 gap-2 md:grid-cols-[1fr_1fr_auto]">
                        <input type="text" name="campos_customizados[<?php echo e($index); ?>][chave]" value="<?php echo e($item['chave'] ?? ''); ?>" placeholder="Nome do campo" class="w-full rounded-lg border border-gray-300 px-4 py-2 text-sm text-gray-800 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90">
                        <input type="text" name="campos_customizados[<?php echo e($index); ?>][valor]" value="<?php echo e($item['valor'] ?? ''); ?>" placeholder="Valor" class="w-full rounded-lg border border-gray-300 px-4 py-2 text-sm text-gray-800 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90">
                        <button type="button" onclick="removerLinhaCustomizada(this)" class="rounded-lg border border-gray-300 px-3 text-xs text-gray-600 hover:bg-gray-50 dark:border-gray-700 dark:text-gray-300">Remover</button>
                    </div>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
            </div>
        </div>

        <div class="rounded-lg border border-gray-200 bg-white p-6 shadow-theme-sm dark:border-gray-800 dark:bg-gray-800 lg:col-span-2">
            <div class="mb-4 flex items-center justify-between">
                <h2 class="text-lg font-semibold text-gray-800 dark:text-white/90">Fotos / Imagens</h2>
                <button type="button" onclick="adicionarImagem()" class="rounded-lg bg-brand-500 px-3 py-1.5 text-sm font-medium text-white hover:bg-brand-600">+ Adicionar Imagem</button>
            </div>
            <div id="imagens_container">
                <?php if($isEdit && $servico->imagens->count()): ?>
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
                    <div class="mb-4 rounded-lg border border-gray-200 p-4 dark:border-gray-700" data-imagem-index="existing-<?php echo e($loop->index); ?>">
                        <div class="grid grid-cols-1 gap-4 md:grid-cols-4">
                            <div class="md:col-span-1">
                                <img src="<?php echo e($src); ?>" alt="Imagem do serviço" class="h-24 w-24 rounded border border-gray-200 object-cover dark:border-gray-700">
                            </div>
                            <div class="md:col-span-2">
                                <p class="text-sm text-gray-600 dark:text-gray-400 break-all">
                                    <?php echo e($img->tipo === 'url' ? $img->url_externa : $img->caminho_local); ?>

                                </p>
                                <p class="mt-1 text-xs text-gray-500 dark:text-gray-500">Tipo: <?php echo e($img->tipo === 'url' ? 'URL' : 'Upload'); ?></p>
                            </div>
                            <div class="flex items-center gap-2 md:col-span-1">
                                <a href="<?php echo e($abrirHref); ?>" target="_blank" class="rounded-lg border border-gray-300 px-3 py-1.5 text-xs font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-700 dark:text-gray-300">Abrir</a>
                                <?php if($img->tipo !== 'url'): ?>
                                    <a href="<?php echo e(route('servicos.imagem', ['servicoImagem' => $img, 'download' => 1])); ?>" download class="rounded-lg border border-gray-300 px-3 py-1.5 text-xs font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-700 dark:text-gray-300">Download</a>
                                <?php endif; ?>
                                <button type="button" onclick="marcarImagemParaRemover(<?php echo e($img->id); ?>, this)" class="rounded-lg border border-error-300 px-3 py-1.5 text-xs font-medium text-error-600 hover:bg-error-50 dark:border-error-700 dark:text-error-400">Excluir</button>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                <?php endif; ?>
            </div>
            <div id="imagens_remover_container"></div>
        </div>
    </div>

    <?php if(function_exists('do_action')): ?>
    <?php do_action('admin.servicos.form.extra', $servico ?? null); ?>
    <?php endif; ?>
    <div class="mt-6 flex justify-end">
        <button type="submit" class="rounded-lg bg-brand-500 px-6 py-2.5 text-sm font-medium text-white hover:bg-brand-600">Salvar</button>
    </div>
</form>

<div id="categoriaModal" class="fixed inset-0 z-50 hidden items-center justify-center bg-black/40 px-4">
    <div class="w-full max-w-md rounded-lg bg-white p-6 shadow-theme-sm dark:bg-gray-900">
        <div class="mb-4 flex items-center justify-between">
            <h3 class="text-lg font-semibold text-gray-800 dark:text-white/90">Nova categoria de serviço</h3>
            <button type="button" id="closeCategoriaModal" class="text-gray-500 hover:text-gray-700 dark:text-gray-400">✕</button>
        </div>
        <form method="POST" action="<?php echo e(route('servicos.categorias.store')); ?>">
            <?php echo csrf_field(); ?>
            <div class="mb-4">
                <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300">Nome</label>
                <input type="text" name="nome" required class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm text-gray-800 focus:border-brand-500 focus:ring-brand-500 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90">
            </div>
            <div class="flex justify-end gap-2">
                <button type="button" id="cancelCategoriaModal" class="rounded-lg border border-gray-300 px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-700 dark:text-gray-300">Cancelar</button>
                <button type="submit" class="rounded-lg bg-brand-500 px-4 py-2 text-sm font-medium text-white hover:bg-brand-600">Salvar</button>
            </div>
        </form>
    </div>
</div>

<script>
let fiscalIndex = <?php echo e(count($informacoesFiscais)); ?>;
let customIndex = <?php echo e(count($camposCustomizados)); ?>;
let imagemIndex = 0;

function adicionarFiscal() {
    const container = document.getElementById('fiscais_container');
    const wrapper = document.createElement('div');
    wrapper.className = 'flex gap-2';
    wrapper.innerHTML = `
        <input type="text" name="informacoes_fiscais[${fiscalIndex}]" placeholder="Ex: Código de serviço municipal" class="w-full rounded-lg border border-gray-300 px-4 py-2 text-sm text-gray-800 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90">
        <button type="button" onclick="removerLinhaFiscal(this)" class="rounded-lg border border-gray-300 px-3 text-xs text-gray-600 hover:bg-gray-50 dark:border-gray-700 dark:text-gray-300">Remover</button>
    `;
    container.appendChild(wrapper);
    fiscalIndex++;
}

function adicionarCustomizado() {
    const container = document.getElementById('customizados_container');
    const wrapper = document.createElement('div');
    wrapper.className = 'grid grid-cols-1 gap-2 md:grid-cols-[1fr_1fr_auto]';
    wrapper.innerHTML = `
        <input type="text" name="campos_customizados[${customIndex}][chave]" placeholder="Nome do campo" class="w-full rounded-lg border border-gray-300 px-4 py-2 text-sm text-gray-800 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90">
        <input type="text" name="campos_customizados[${customIndex}][valor]" placeholder="Valor" class="w-full rounded-lg border border-gray-300 px-4 py-2 text-sm text-gray-800 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90">
        <button type="button" onclick="removerLinhaCustomizada(this)" class="rounded-lg border border-gray-300 px-3 text-xs text-gray-600 hover:bg-gray-50 dark:border-gray-700 dark:text-gray-300">Remover</button>
    `;
    container.appendChild(wrapper);
    customIndex++;
}

function adicionarImagem() {
    const container = document.getElementById('imagens_container');
    const html = `
        <div class="mb-4 rounded-lg border border-gray-200 p-4 dark:border-gray-700" data-imagem-index="${imagemIndex}">
            <div class="grid grid-cols-1 gap-4 md:grid-cols-4">
                <div>
                    <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300">Tipo</label>
                    <select name="imagens_tipo[${imagemIndex}]" class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm text-gray-800 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90">
                        <option value="upload">Upload</option>
                        <option value="url">URL</option>
                    </select>
                </div>
                <div>
                    <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300">Arquivo</label>
                    <input type="file" name="imagens_arquivo[${imagemIndex}]" accept=".jpg,.jpeg,.png,.gif,.webp,.avif,.heic,.heif" class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm text-gray-800 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90">
                </div>
                <div>
                    <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300">URL Externa</label>
                    <input type="url" name="imagens_url[${imagemIndex}]" class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm text-gray-800 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90">
                </div>
                <div>
                    <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300">Ordem</label>
                    <input type="number" min="0" name="imagens_ordem[${imagemIndex}]" value="${imagemIndex}" class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm text-gray-800 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90">
                </div>
            </div>
            <div class="mt-3 text-right">
                <button type="button" onclick="removerImagem(this)" class="text-error-500 hover:underline">Remover</button>
            </div>
        </div>
    `;
    container.insertAdjacentHTML('beforeend', html);
    imagemIndex++;
}

function removerImagem(btn) {
    const wrapper = btn.closest('[data-imagem-index]');
    if (wrapper) wrapper.remove();
}

function marcarImagemParaRemover(imagemId, btn) {
    const container = document.getElementById('imagens_remover_container');
    const input = document.createElement('input');
    input.type = 'hidden';
    input.name = 'imagens_remover[]';
    input.value = imagemId;
    container.appendChild(input);

    const wrapper = btn.closest('[data-imagem-index]');
    if (wrapper) wrapper.remove();
}

function removerLinhaFiscal(btn) {
    const wrapper = btn.closest('div');
    if (wrapper) wrapper.remove();
}

function removerLinhaCustomizada(btn) {
    const wrapper = btn.closest('div');
    if (wrapper) wrapper.remove();
}

document.addEventListener('DOMContentLoaded', () => {
    const modal = document.getElementById('categoriaModal');
    const openBtn = document.getElementById('openCategoriaModal');
    const closeBtn = document.getElementById('closeCategoriaModal');
    const cancelBtn = document.getElementById('cancelCategoriaModal');

    const openModal = () => {
        modal.classList.remove('hidden');
        modal.classList.add('flex');
    };
    const closeModal = () => {
        modal.classList.add('hidden');
        modal.classList.remove('flex');
    };

    if (openBtn) openBtn.addEventListener('click', openModal);
    if (closeBtn) closeBtn.addEventListener('click', closeModal);
    if (cancelBtn) cancelBtn.addEventListener('click', closeModal);
    modal.addEventListener('click', (e) => {
        if (e.target === modal) closeModal();
    });

    const unidadeSelect = document.getElementById('servico_unidade');
    const unidadeOutro = document.getElementById('servico_unidade_outro');
    if (unidadeSelect && unidadeOutro) {
        const toggleOutro = () => {
            const isOutro = unidadeSelect.value === 'outro';
            unidadeOutro.classList.toggle('hidden', !isOutro);
            if (!isOutro) {
                unidadeOutro.value = '';
            }
        };
        unidadeSelect.addEventListener('change', toggleOutro);
        toggleOutro();
    }
});
</script>
<?php /**PATH C:\laragon\www\LivreOS\LivreOS\sistema_livreos\resources\views\servicos\_form.blade.php ENDPATH**/ ?>