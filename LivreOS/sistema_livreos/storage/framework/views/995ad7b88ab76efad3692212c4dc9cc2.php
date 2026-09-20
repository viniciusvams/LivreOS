

<?php
    $isEdit = isset($produto) && $produto->exists;
    $produto = $isEdit ? $produto : new \App\Models\Produto();
    $fornecedoresSelecionados = old('fornecedores', $produto->fornecedores->toArray() ?: []);
    $variacaoAtributos = old('variacao_atributos', $produto->variacaoAtributos->toArray() ?: []);
    $variacoes = old('variacoes', $produto->variacoes->toArray() ?: []);
    $composicoes = old('composicoes', $produto->composicoes->toArray() ?: []);
    $tagsString = old('tags', is_array($produto->tags) ? implode(', ', $produto->tags) : '');
?>

<form action="<?php echo e($isEdit ? route('produtos.update', $produto) : route('produtos.store')); ?>" method="POST" enctype="multipart/form-data" id="produtoForm">
    <?php echo csrf_field(); ?>
    <?php if($isEdit): ?>
        <?php echo method_field('PUT'); ?>
    <?php endif; ?>

    <div class="mb-6 rounded-lg border border-gray-200 bg-white p-4 shadow-theme-sm dark:border-gray-800 dark:bg-gray-800">
        <div class="flex flex-wrap gap-2">
            <button type="button" data-tab="dados" class="tab-btn rounded-lg bg-brand-500 px-3 py-2 text-sm font-medium text-white">Dados Básicos</button>
            <button type="button" data-tab="caracteristicas" class="tab-btn rounded-lg border border-gray-300 px-3 py-2 text-sm text-gray-700 dark:border-gray-700 dark:text-gray-300">Características</button>
            <button type="button" data-tab="imagens" class="tab-btn rounded-lg border border-gray-300 px-3 py-2 text-sm text-gray-700 dark:border-gray-700 dark:text-gray-300">Imagens</button>
            <button type="button" data-tab="estoque" class="tab-btn rounded-lg border border-gray-300 px-3 py-2 text-sm text-gray-700 dark:border-gray-700 dark:text-gray-300">Estoque</button>
            <button type="button" data-tab="fornecedores" class="tab-btn rounded-lg border border-gray-300 px-3 py-2 text-sm text-gray-700 dark:border-gray-700 dark:text-gray-300">Fornecedores</button>
            <button type="button" data-tab="tributacao" class="tab-btn rounded-lg border border-gray-300 px-3 py-2 text-sm text-gray-700 dark:border-gray-700 dark:text-gray-300">Tributação</button>
            <button type="button" data-tab="variacoes" class="tab-btn rounded-lg border border-gray-300 px-3 py-2 text-sm text-gray-700 dark:border-gray-700 dark:text-gray-300">Variações</button>
            <button type="button" data-tab="composicao" class="tab-btn rounded-lg border border-gray-300 px-3 py-2 text-sm text-gray-700 dark:border-gray-700 dark:text-gray-300">Composição</button>
        </div>
    </div>

    <!-- ABA 1: Dados Básicos -->
    <div class="tab-panel mb-6 rounded-lg border border-gray-200 bg-white p-6 shadow-theme-sm dark:border-gray-800 dark:bg-gray-800" data-tab-panel="dados">
        <div class="mb-4 flex items-center gap-2">
            <h2 class="text-lg font-semibold text-gray-800 dark:text-white/90">1. Dados Básicos</h2>
            <button type="button" data-help-toggle="dados-help" class="inline-flex h-7 w-7 items-center justify-center rounded-full border border-gray-300 text-sm text-gray-600 hover:bg-gray-50 dark:border-gray-700 dark:text-gray-300" aria-label="Ajuda sobre dados básicos">?</button>
        </div>
        <div id="dados-help" class="mb-4 hidden rounded-lg border border-brand-100 bg-brand-50 p-4 text-sm text-gray-700 dark:border-brand-900/40 dark:bg-gray-900 dark:text-gray-300">
            <p class="mb-2 font-medium text-gray-800 dark:text-white/90">Como funciona</p>
            <p>Defina os dados principais do produto. Esses campos são usados para identificação, preço e organização do cadastro.</p>
            <p class="mt-2 font-medium text-gray-800 dark:text-white/90">Como cadastrar</p>
            <p>Preencha Nome e Unidade. Se usar variações ou composição, selecione o formato correto.</p>
            <p class="mt-2 font-medium text-gray-800 dark:text-white/90">Como usar</p>
            <p>SKU ajuda na busca e controle interno. Tabelas de preço permitem valores diferentes por tabela.</p>
        </div>
        <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
            <div>
                <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300">Nome <span class="text-error-500">*</span></label>
                <input type="text" name="nome" required value="<?php echo e(old('nome', $produto->nome)); ?>" class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm text-gray-800 focus:border-brand-500 focus:ring-brand-500 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90">
            </div>
            <div>
                <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300">SKU</label>
                <input type="text" name="codigo_sku" value="<?php echo e(old('codigo_sku', $produto->codigo_sku)); ?>" class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm text-gray-800 focus:border-brand-500 focus:ring-brand-500 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90">
            </div>
            <div>
                <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300">Preço de Venda</label>
                <input type="text" inputmode="decimal" name="preco_venda" value="<?php echo e(format_br_decimal(old('preco_venda', $produto->preco_venda))); ?>" placeholder="0,00" class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm text-gray-800 focus:border-brand-500 focus:ring-brand-500 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90">
            </div>
            <div>
                <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300">Unidade <span class="text-error-500">*</span></label>
                <input type="text" name="unidade" required value="<?php echo e(old('unidade', $produto->unidade)); ?>" placeholder="UN, PÇ, KG, M..." class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm text-gray-800 focus:border-brand-500 focus:ring-brand-500 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90">
            </div>
            <div>
                <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300">Formato</label>
                <select name="formato" id="formato" class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm text-gray-800 focus:border-brand-500 focus:ring-brand-500 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90">
                    <option value="simples" <?php echo e(old('formato', $produto->formato) === 'simples' ? 'selected' : ''); ?>>Simples</option>
                    <option value="variacao" <?php echo e(old('formato', $produto->formato) === 'variacao' ? 'selected' : ''); ?>>Variação</option>
                    <option value="composicao" <?php echo e(old('formato', $produto->formato) === 'composicao' ? 'selected' : ''); ?>>Composição</option>
                </select>
            </div>
            <div>
                <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300">Condição</label>
                <select name="condicao" class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm text-gray-800 focus:border-brand-500 focus:ring-brand-500 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90">
                    <option value="">Selecione...</option>
                    <option value="novo" <?php echo e(old('condicao', $produto->condicao) === 'novo' ? 'selected' : ''); ?>>Novo</option>
                    <option value="usado" <?php echo e(old('condicao', $produto->condicao) === 'usado' ? 'selected' : ''); ?>>Usado</option>
                    <option value="recondicionado" <?php echo e(old('condicao', $produto->condicao) === 'recondicionado' ? 'selected' : ''); ?>>Recondicionado</option>
                </select>
            </div>
            <div>
                <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300">Linha de Produto</label>
                <input type="text" name="linha_produto" value="<?php echo e(old('linha_produto', $produto->linha_produto)); ?>" class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm text-gray-800 focus:border-brand-500 focus:ring-brand-500 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90">
            </div>
            <div>
                <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300">Categoria</label>
                <select name="categoria_id" class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm text-gray-800 focus:border-brand-500 focus:ring-brand-500 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90">
                    <option value="">Selecione...</option>
                    <?php $__currentLoopData = $categorias; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $categoria): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                    <option value="<?php echo e($categoria->id); ?>" <?php echo e(old('categoria_id', $produto->categoria_id) == $categoria->id ? 'selected' : ''); ?>><?php echo e($categoria->nome); ?></option>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                </select>
            </div>
        </div>

        <div class="mt-6">
            <h3 class="mb-3 text-sm font-semibold text-gray-800 dark:text-white/90">Tabelas de Preço</h3>
            <div class="grid grid-cols-1 gap-4 md:grid-cols-3">
                <?php $__currentLoopData = $tabelasPrecos; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $tabela): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                    <?php
                        $pivot = $produto->tabelasPrecos->firstWhere('id', $tabela->id);
                        $ativo = old("tabelas_precos.{$tabela->id}.ativo", $pivot ? 1 : 0);
                    ?>
                    <div class="rounded-lg border border-gray-200 p-3 dark:border-gray-700">
                        <label class="mb-2 flex items-center gap-2 text-sm text-gray-700 dark:text-gray-300">
                            <input type="checkbox" name="tabelas_precos[<?php echo e($tabela->id); ?>][ativo]" value="1" <?php echo e($ativo ? 'checked' : ''); ?> class="rounded border-gray-300 text-brand-500 focus:ring-brand-500">
                            <?php echo e($tabela->nome); ?>

                        </label>
                        <input type="hidden" name="tabelas_precos[<?php echo e($tabela->id); ?>][tabela_preco_id]" value="<?php echo e($tabela->id); ?>">
                        <input type="text" inputmode="decimal" name="tabelas_precos[<?php echo e($tabela->id); ?>][preco]" value="<?php echo e(format_br_decimal(old("tabelas_precos.{$tabela->id}.preco", $pivot?->pivot?->preco))); ?>" placeholder="0,00" class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm text-gray-800 focus:border-brand-500 focus:ring-brand-500 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90">
                    </div>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
            </div>
        </div>
    </div>

    <!-- ABA 2: Características -->
    <div class="tab-panel mb-6 hidden rounded-lg border border-gray-200 bg-white p-6 shadow-theme-sm dark:border-gray-800 dark:bg-gray-800" data-tab-panel="caracteristicas">
        <div class="mb-4 flex items-center gap-2">
            <h2 class="text-lg font-semibold text-gray-800 dark:text-white/90">2. Características</h2>
            <button type="button" data-help-toggle="caracteristicas-help" class="inline-flex h-7 w-7 items-center justify-center rounded-full border border-gray-300 text-sm text-gray-600 hover:bg-gray-50 dark:border-gray-700 dark:text-gray-300" aria-label="Ajuda sobre características">?</button>
        </div>
        <div id="caracteristicas-help" class="mb-4 hidden rounded-lg border border-brand-100 bg-brand-50 p-4 text-sm text-gray-700 dark:border-brand-900/40 dark:bg-gray-900 dark:text-gray-300">
            <p class="mb-2 font-medium text-gray-800 dark:text-white/90">Como funciona</p>
            <p>Preencha detalhes que ajudam na descrição do produto e em integrações.</p>
            <p class="mt-2 font-medium text-gray-800 dark:text-white/90">Como cadastrar</p>
            <p>Informe marca, dimensões, EAN, descrição e links quando disponíveis.</p>
            <p class="mt-2 font-medium text-gray-800 dark:text-white/90">Como usar</p>
            <p>Dados como EAN e dimensões facilitam vendas e logística.</p>
        </div>
        <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
            <div>
                <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300">Marca</label>
                <input type="text" name="marca" value="<?php echo e(old('marca', $produto->marca)); ?>" class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm text-gray-800 focus:border-brand-500 focus:ring-brand-500 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90">
            </div>
            <div>
                <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300">Produção</label>
                <select name="producao" class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm text-gray-800 focus:border-brand-500 focus:ring-brand-500 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90">
                    <option value="">Selecione...</option>
                    <option value="propria" <?php echo e(old('producao', $produto->producao) === 'propria' ? 'selected' : ''); ?>>Própria</option>
                    <option value="terceiros" <?php echo e(old('producao', $produto->producao) === 'terceiros' ? 'selected' : ''); ?>>Terceiros</option>
                </select>
            </div>
            <div>
                <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300">Data de Validade</label>
                <input type="date" name="data_validade" value="<?php echo e(old('data_validade', $produto->data_validade?->format('Y-m-d'))); ?>" class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm text-gray-800 focus:border-brand-500 focus:ring-brand-500 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90">
            </div>
            <div class="flex items-center gap-2">
                <input type="checkbox" name="frete_gratis" value="1" <?php echo e(old('frete_gratis', $produto->frete_gratis) ? 'checked' : ''); ?> class="rounded border-gray-300 text-brand-500 focus:ring-brand-500">
                <label class="text-sm text-gray-700 dark:text-gray-300">Frete grátis</label>
            </div>
            <div>
                <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300">Peso Líquido (kg)</label>
                <input type="number" step="0.01" min="0" name="peso_liquido" value="<?php echo e(old('peso_liquido', $produto->peso_liquido !== null ? (float) $produto->peso_liquido : '')); ?>" class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm text-gray-800 focus:border-brand-500 focus:ring-brand-500 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90">
            </div>
            <div>
                <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300">Peso Bruto (kg)</label>
                <input type="number" step="0.01" min="0" name="peso_bruto" value="<?php echo e(old('peso_bruto', $produto->peso_bruto !== null ? (float) $produto->peso_bruto : '')); ?>" class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm text-gray-800 focus:border-brand-500 focus:ring-brand-500 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90">
            </div>
            <div>
                <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300">Largura (cm)</label>
                <input type="number" step="0.01" min="0" name="largura" value="<?php echo e(old('largura', $produto->largura !== null ? (float) $produto->largura : '')); ?>" class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm text-gray-800 focus:border-brand-500 focus:ring-brand-500 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90">
            </div>
            <div>
                <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300">Altura (cm)</label>
                <input type="number" step="0.01" min="0" name="altura" value="<?php echo e(old('altura', $produto->altura !== null ? (float) $produto->altura : '')); ?>" class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm text-gray-800 focus:border-brand-500 focus:ring-brand-500 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90">
            </div>
            <div>
                <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300">Profundidade (cm)</label>
                <input type="number" step="0.01" min="0" name="profundidade" value="<?php echo e(old('profundidade', $produto->profundidade !== null ? (float) $produto->profundidade : '')); ?>" class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm text-gray-800 focus:border-brand-500 focus:ring-brand-500 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90">
            </div>
            <div>
                <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300">Volumes</label>
                <input type="number" min="0" name="volumes" value="<?php echo e(old('volumes', $produto->volumes)); ?>" class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm text-gray-800 focus:border-brand-500 focus:ring-brand-500 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90">
            </div>
            <div>
                <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300">Itens por Caixa</label>
                <input type="number" min="0" name="itens_por_caixa" value="<?php echo e(old('itens_por_caixa', $produto->itens_por_caixa)); ?>" class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm text-gray-800 focus:border-brand-500 focus:ring-brand-500 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90">
            </div>
            <div>
                <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300">EAN</label>
                <input type="text" name="ean" id="ean" value="<?php echo e(old('ean', $produto->ean)); ?>" class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm text-gray-800 focus:border-brand-500 focus:ring-brand-500 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90">
            </div>
            <div>
                <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300">EAN Tributário</label>
                <input type="text" name="ean_tributario" value="<?php echo e(old('ean_tributario', $produto->ean_tributario)); ?>" class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm text-gray-800 focus:border-brand-500 focus:ring-brand-500 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90">
            </div>
            <div>
                <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300">Descrição Curta</label>
                <input type="text" name="descricao_curta" value="<?php echo e(old('descricao_curta', $produto->descricao_curta)); ?>" class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm text-gray-800 focus:border-brand-500 focus:ring-brand-500 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90">
            </div>
            <div>
                <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300">Link Externo</label>
                <input type="url" name="link_externo" value="<?php echo e(old('link_externo', $produto->link_externo)); ?>" class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm text-gray-800 focus:border-brand-500 focus:ring-brand-500 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90">
            </div>
            <div>
                <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300">Vídeo (URL)</label>
                <input type="url" name="video_url" value="<?php echo e(old('video_url', $produto->video_url)); ?>" class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm text-gray-800 focus:border-brand-500 focus:ring-brand-500 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90">
            </div>
            <div class="md:col-span-2">
                <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300">Descrição Complementar</label>
                <textarea name="descricao_complementar" rows="4" class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm text-gray-800 focus:border-brand-500 focus:ring-brand-500 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90"><?php echo e(old('descricao_complementar', $produto->descricao_complementar)); ?></textarea>
            </div>
            <div class="md:col-span-2">
                <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300">Observações</label>
                <textarea name="observacoes" rows="3" class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm text-gray-800 focus:border-brand-500 focus:ring-brand-500 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90"><?php echo e(old('observacoes', $produto->observacoes)); ?></textarea>
            </div>
            <div class="md:col-span-2">
                <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300">Tags</label>
                <input type="text" name="tags" value="<?php echo e($tagsString); ?>" placeholder="ex: camera, dvr, instalacao" class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm text-gray-800 focus:border-brand-500 focus:ring-brand-500 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90">
            </div>
        </div>
    </div>

    <!-- ABA 3: Imagens -->
    <div class="tab-panel mb-6 hidden rounded-lg border border-gray-200 bg-white p-6 shadow-theme-sm dark:border-gray-800 dark:bg-gray-800" data-tab-panel="imagens">
        <div class="mb-4 flex flex-wrap items-center justify-between gap-2">
            <div class="flex items-center gap-2">
                <h2 class="text-lg font-semibold text-gray-800 dark:text-white/90">3. Imagens</h2>
                <button type="button" data-help-toggle="imagens-help" class="inline-flex h-7 w-7 items-center justify-center rounded-full border border-gray-300 text-sm text-gray-600 hover:bg-gray-50 dark:border-gray-700 dark:text-gray-300" aria-label="Ajuda sobre imagens">?</button>
            </div>
            <button type="button" onclick="adicionarImagem()" class="rounded-lg bg-brand-500 px-3 py-1.5 text-sm font-medium text-white hover:bg-brand-600">+ Adicionar Imagem</button>
        </div>
        <div id="imagens-help" class="mb-4 hidden rounded-lg border border-brand-100 bg-brand-50 p-4 text-sm text-gray-700 dark:border-brand-900/40 dark:bg-gray-900 dark:text-gray-300">
            <p class="mb-2 font-medium text-gray-800 dark:text-white/90">Como funciona</p>
            <p>Você pode enviar imagens do produto ou informar uma URL externa.</p>
            <p class="mt-2 font-medium text-gray-800 dark:text-white/90">Como cadastrar</p>
            <p>Clique em “+ Adicionar Imagem”, escolha o tipo (upload ou URL) e preencha o campo correspondente.</p>
            <p class="mt-2 font-medium text-gray-800 dark:text-white/90">Como usar</p>
            <p>A ordem controla a sequência de exibição. Use imagens claras do produto.</p>
        </div>
        <div id="imagens_container">
            <?php if($isEdit && $produto->imagens->count()): ?>
                <?php $__currentLoopData = $produto->imagens; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $img): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
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
                ?>
                <div class="mb-4 rounded-lg border border-gray-200 p-4 dark:border-gray-700" data-imagem-index="existing-<?php echo e($loop->index); ?>">
                    <div class="grid grid-cols-1 gap-4 md:grid-cols-4">
                        <div class="md:col-span-1">
                            <img src="<?php echo e($src); ?>" alt="Imagem do produto" class="h-24 w-24 rounded border border-gray-200 object-cover dark:border-gray-700">
                        </div>
                        <div class="md:col-span-2">
                            <p class="text-sm text-gray-600 dark:text-gray-400 break-all">
                                <?php echo e($img->tipo === 'url' ? $img->url_externa : $img->caminho_local); ?>

                            </p>
                            <p class="mt-1 text-xs text-gray-500 dark:text-gray-500">Tipo: <?php echo e($img->tipo === 'url' ? 'URL' : 'Upload'); ?></p>
                        </div>
                        <div class="flex items-center gap-2 md:col-span-1">
                            <?php
                                $abrirHref = $img->tipo === 'url' ? $img->url_externa : route('produtos.imagem', $img);
                            ?>
                            <a href="<?php echo e($abrirHref); ?>" target="_blank" class="rounded-lg border border-gray-300 px-3 py-1.5 text-xs font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-700 dark:text-gray-300">Abrir</a>
                            <?php if($img->tipo !== 'url'): ?>
                                <a href="<?php echo e(route('produtos.imagem', ['produtoImagem' => $img, 'download' => 1])); ?>" download class="rounded-lg border border-gray-300 px-3 py-1.5 text-xs font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-700 dark:text-gray-300">Download</a>
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

    <!-- ABA 4: Estoque -->
    <div class="tab-panel mb-6 hidden rounded-lg border border-gray-200 bg-white p-6 shadow-theme-sm dark:border-gray-800 dark:bg-gray-800" data-tab-panel="estoque">
        <div class="mb-4 flex items-center gap-2">
            <h2 class="text-lg font-semibold text-gray-800 dark:text-white/90">4. Estoque</h2>
            <button type="button" data-help-toggle="estoque-help" class="inline-flex h-7 w-7 items-center justify-center rounded-full border border-gray-300 text-sm text-gray-600 hover:bg-gray-50 dark:border-gray-700 dark:text-gray-300" aria-label="Ajuda sobre estoque">?</button>
        </div>
        <div id="estoque-help" class="mb-4 hidden rounded-lg border border-brand-100 bg-brand-50 p-4 text-sm text-gray-700 dark:border-brand-900/40 dark:bg-gray-900 dark:text-gray-300">
            <p class="mb-2 font-medium text-gray-800 dark:text-white/90">Como funciona</p>
            <p>Controle quantidade, limites e custos do produto.</p>
            <p class="mt-2 font-medium text-gray-800 dark:text-white/90">Como cadastrar</p>
            <p>Informe quantidade, depósito e custos unitários. Use mínimo/máximo para alertas.</p>
            <p class="mt-2 font-medium text-gray-800 dark:text-white/90">Como usar</p>
            <p>Crossdocking define o prazo médio de reposição. A observação é livre.</p>
        </div>
        <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
            <div>
                <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300">Mínimo</label>
                <input type="number" min="0" name="estoque_minimo" value="<?php echo e(old('estoque_minimo', $produto->estoque_minimo)); ?>" class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm text-gray-800 focus:border-brand-500 focus:ring-brand-500 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90">
            </div>
            <div>
                <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300">Máximo</label>
                <input type="number" min="0" name="estoque_maximo" value="<?php echo e(old('estoque_maximo', $produto->estoque_maximo)); ?>" class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm text-gray-800 focus:border-brand-500 focus:ring-brand-500 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90">
            </div>
            <div>
                <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300">Crossdocking (dias)</label>
                <input type="number" min="0" name="estoque_crossdocking" value="<?php echo e(old('estoque_crossdocking', $produto->estoque_crossdocking)); ?>" class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm text-gray-800 focus:border-brand-500 focus:ring-brand-500 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90">
            </div>
            <div>
                <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300">Localização</label>
                <input type="text" name="estoque_localizacao" value="<?php echo e(old('estoque_localizacao', $produto->estoque_localizacao)); ?>" placeholder="Prateleira A3" class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm text-gray-800 focus:border-brand-500 focus:ring-brand-500 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90">
            </div>
            <div>
                <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300">Depósito</label>
                <select name="estoque_deposito_id" class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm text-gray-800 focus:border-brand-500 focus:ring-brand-500 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90">
                    <option value="">Selecione...</option>
                    <?php $__currentLoopData = $depositos; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $deposito): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                    <option value="<?php echo e($deposito->id); ?>" <?php echo e(old('estoque_deposito_id', $produto->estoque_deposito_id) == $deposito->id ? 'selected' : ''); ?>><?php echo e($deposito->nome); ?></option>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                </select>
            </div>
            <div>
                <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300">Quantidade</label>
                <input type="number" step="0.001" min="0" name="estoque_quantidade" value="<?php echo e(format_quantity(old('estoque_quantidade', $produto->estoque_quantidade !== null ? (float) $produto->estoque_quantidade : ''))); ?>" class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm text-gray-800 focus:border-brand-500 focus:ring-brand-500 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90">
            </div>
            <div>
                <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300">Preço Compra Unitário</label>
                <input type="text" inputmode="decimal" name="estoque_preco_compra_unitario" value="<?php echo e(format_br_decimal(old('estoque_preco_compra_unitario', $produto->estoque_preco_compra_unitario))); ?>" placeholder="0,00" class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm text-gray-800 focus:border-brand-500 focus:ring-brand-500 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90">
            </div>
            <div>
                <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300">Preço Custo Unitário</label>
                <input type="text" inputmode="decimal" name="estoque_preco_custo_unitario" value="<?php echo e(format_br_decimal(old('estoque_preco_custo_unitario', $produto->estoque_preco_custo_unitario))); ?>" placeholder="0,00" class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm text-gray-800 focus:border-brand-500 focus:ring-brand-500 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90">
            </div>
            <div class="md:col-span-2">
                <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300">Observação</label>
                <textarea name="estoque_observacao" rows="3" class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm text-gray-800 focus:border-brand-500 focus:ring-brand-500 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90"><?php echo e(old('estoque_observacao', $produto->estoque_observacao)); ?></textarea>
            </div>
        </div>
    </div>

    <!-- ABA 5: Fornecedores -->
    <div class="tab-panel mb-6 hidden rounded-lg border border-gray-200 bg-white p-6 shadow-theme-sm dark:border-gray-800 dark:bg-gray-800" data-tab-panel="fornecedores">
        <div class="mb-4 flex flex-wrap items-center justify-between gap-2">
            <div class="flex items-center gap-2">
                <h2 class="text-lg font-semibold text-gray-800 dark:text-white/90">5. Fornecedores</h2>
                <button type="button" data-help-toggle="fornecedores-help" class="inline-flex h-7 w-7 items-center justify-center rounded-full border border-gray-300 text-sm text-gray-600 hover:bg-gray-50 dark:border-gray-700 dark:text-gray-300" aria-label="Ajuda sobre fornecedores">?</button>
            </div>
            <button type="button" onclick="adicionarFornecedor()" class="rounded-lg bg-brand-500 px-3 py-1.5 text-sm font-medium text-white hover:bg-brand-600">+ Adicionar Fornecedor</button>
        </div>
        <div id="fornecedores-help" class="mb-4 hidden rounded-lg border border-brand-100 bg-brand-50 p-4 text-sm text-gray-700 dark:border-brand-900/40 dark:bg-gray-900 dark:text-gray-300">
            <p class="mb-2 font-medium text-gray-800 dark:text-white/90">Como funciona</p>
            <p>Vincule fornecedores ao produto para registrar custos e códigos específicos.</p>
            <p class="mt-2 font-medium text-gray-800 dark:text-white/90">Como cadastrar</p>
            <p>Adicione o fornecedor e preencha descrição, código e preços, se necessário.</p>
            <p class="mt-2 font-medium text-gray-800 dark:text-white/90">Como usar</p>
            <p>Essas informações ajudam na compra e comparação de custos.</p>
        </div>
        <div id="fornecedores_container">
            <?php $__currentLoopData = $fornecedoresSelecionados; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $index => $fornecedor): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                <div class="mb-4 rounded-lg border border-gray-200 p-4 dark:border-gray-700" data-fornecedor-index="<?php echo e($index); ?>">
                    <div class="grid grid-cols-1 gap-4 md:grid-cols-3">
                        <div>
                            <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300">Fornecedor</label>
                            <select name="fornecedores[<?php echo e($index); ?>][fornecedor_id]" class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm text-gray-800 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90">
                                <option value="">Selecione...</option>
                                <?php $__currentLoopData = $fornecedores; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $contato): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                    <option value="<?php echo e($contato->id); ?>" <?php echo e(($fornecedor['fornecedor_id'] ?? null) == $contato->id ? 'selected' : ''); ?>>
                                        <?php echo e($contato->nome); ?><?php echo e($contato->cliente ? ' - ' . $contato->cliente->nome : ''); ?>

                                    </option>
                                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                            </select>
                        </div>
                        <div>
                            <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300">Descrição</label>
                            <input type="text" name="fornecedores[<?php echo e($index); ?>][descricao]" value="<?php echo e($fornecedor['descricao'] ?? ''); ?>" class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm text-gray-800 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90">
                        </div>
                        <div>
                            <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300">Código do Fornecedor</label>
                            <input type="text" name="fornecedores[<?php echo e($index); ?>][codigo_fornecedor]" value="<?php echo e($fornecedor['codigo_fornecedor'] ?? ''); ?>" class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm text-gray-800 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90">
                        </div>
                        <div>
                            <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300">Preço Compra</label>
                            <input type="text" inputmode="decimal" name="fornecedores[<?php echo e($index); ?>][preco_compra]" value="<?php echo e(format_br_decimal($fornecedor['preco_compra'] ?? '')); ?>" placeholder="0,00" class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm text-gray-800 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90">
                        </div>
                        <div>
                            <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300">Preço Custo</label>
                            <input type="text" inputmode="decimal" name="fornecedores[<?php echo e($index); ?>][preco_custo]" value="<?php echo e(format_br_decimal($fornecedor['preco_custo'] ?? '')); ?>" placeholder="0,00" class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm text-gray-800 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90">
                        </div>
                        <div>
                            <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300">Garantia (meses)</label>
                            <input type="number" min="0" name="fornecedores[<?php echo e($index); ?>][garantia_meses]" value="<?php echo e($fornecedor['garantia_meses'] ?? ''); ?>" class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm text-gray-800 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90">
                        </div>
                    </div>
                    <div class="mt-3 text-right">
                        <button type="button" onclick="removerItem(this)" class="text-error-500 hover:underline">Remover</button>
                    </div>
                </div>
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
        </div>
    </div>

    <!-- ABA 6: Tributação -->
    <div class="tab-panel mb-6 hidden rounded-lg border border-gray-200 bg-white p-6 shadow-theme-sm dark:border-gray-800 dark:bg-gray-800" data-tab-panel="tributacao">
        <div class="mb-4 flex items-center gap-2">
            <h2 class="text-lg font-semibold text-gray-800 dark:text-white/90">6. Tributação</h2>
            <button type="button" data-help-toggle="tributacao-help" class="inline-flex h-7 w-7 items-center justify-center rounded-full border border-gray-300 text-sm text-gray-600 hover:bg-gray-50 dark:border-gray-700 dark:text-gray-300" aria-label="Ajuda sobre tributação">?</button>
        </div>
        <div id="tributacao-help" class="mb-4 hidden rounded-lg border border-brand-100 bg-brand-50 p-4 text-sm text-gray-700 dark:border-brand-900/40 dark:bg-gray-900 dark:text-gray-300">
            <p class="mb-2 font-medium text-gray-800 dark:text-white/90">Como funciona</p>
            <p>Campos fiscais para emissão de documentos e integrações.</p>
            <p class="mt-2 font-medium text-gray-800 dark:text-white/90">Como cadastrar</p>
            <p>Informe NCM, CEST e demais campos conforme a classificação fiscal do produto.</p>
            <p class="mt-2 font-medium text-gray-800 dark:text-white/90">Como usar</p>
            <p>Use as informações oficiais da sua contabilidade para evitar erros fiscais.</p>
        </div>
        <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
            <div>
                <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300">Origem</label>
                <select name="tributacao_origem" class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm text-gray-800 focus:border-brand-500 focus:ring-brand-500 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90">
                    <option value="">Selecione...</option>
                    <?php $__currentLoopData = ['0','1','2','3','4','5','6','7','8']; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $origem): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                    <option value="<?php echo e($origem); ?>" <?php echo e(old('tributacao_origem', $produto->tributacao_origem) == $origem ? 'selected' : ''); ?>><?php echo e($origem); ?></option>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                </select>
            </div>
            <div>
                <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300">NCM</label>
                <input type="text" name="tributacao_ncm" id="tributacao_ncm" value="<?php echo e(old('tributacao_ncm', $produto->tributacao_ncm)); ?>" class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm text-gray-800 focus:border-brand-500 focus:ring-brand-500 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90">
            </div>
            <div>
                <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300">CEST</label>
                <input type="text" name="tributacao_cest" value="<?php echo e(old('tributacao_cest', $produto->tributacao_cest)); ?>" class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm text-gray-800 focus:border-brand-500 focus:ring-brand-500 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90">
            </div>
            <div>
                <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300">Tipo Item</label>
                <input type="text" name="tributacao_tipo_item" value="<?php echo e(old('tributacao_tipo_item', $produto->tributacao_tipo_item)); ?>" class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm text-gray-800 focus:border-brand-500 focus:ring-brand-500 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90">
            </div>
            <div>
                <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300">% Tributos</label>
                <input type="number" step="0.01" min="0" name="tributacao_percentual_tributos" value="<?php echo e(old('tributacao_percentual_tributos', $produto->tributacao_percentual_tributos !== null ? (float) $produto->tributacao_percentual_tributos : '')); ?>" class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm text-gray-800 focus:border-brand-500 focus:ring-brand-500 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90">
            </div>
            <div>
                <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300">Grupo Produtos</label>
                <input type="text" name="tributacao_grupo_produtos" value="<?php echo e(old('tributacao_grupo_produtos', $produto->tributacao_grupo_produtos)); ?>" class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm text-gray-800 focus:border-brand-500 focus:ring-brand-500 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90">
            </div>
            <div class="md:col-span-2">
                <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300">Informações Adicionais</label>
                <textarea name="tributacao_info_adicionais" rows="3" class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm text-gray-800 focus:border-brand-500 focus:ring-brand-500 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90"><?php echo e(old('tributacao_info_adicionais', $produto->tributacao_info_adicionais)); ?></textarea>
            </div>
        </div>
    </div>

    <!-- ABA 7: Variações -->
    <div class="tab-panel mb-6 hidden rounded-lg border border-gray-200 bg-white p-6 shadow-theme-sm dark:border-gray-800 dark:bg-gray-800" data-tab-panel="variacoes">
        <div class="mb-4 flex flex-wrap items-center justify-between gap-2">
            <div class="flex items-center gap-2">
                <h2 class="text-lg font-semibold text-gray-800 dark:text-white/90">7. Variações</h2>
                <button type="button" data-help-toggle="variacoes-help" class="inline-flex h-7 w-7 items-center justify-center rounded-full border border-gray-300 text-sm text-gray-600 hover:bg-gray-50 dark:border-gray-700 dark:text-gray-300" aria-label="Ajuda sobre variações">?</button>
            </div>
            <button type="button" onclick="adicionarAtributo()" class="rounded-lg border border-gray-300 px-3 py-1.5 text-sm font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-700 dark:text-gray-300">+ Atributo</button>
        </div>
        <div id="variacoes-help" class="mb-4 hidden rounded-lg border border-brand-100 bg-brand-50 p-4 text-sm text-gray-700 dark:border-brand-900/40 dark:bg-gray-900 dark:text-gray-300">
            <p class="mb-2 font-medium text-gray-800 dark:text-white/90">Como funciona</p>
            <p>Use variações quando o produto tem opções como tamanho, cor ou voltagem. Você cadastra os atributos e o sistema gera combinações.</p>
            <p class="mt-2 font-medium text-gray-800 dark:text-white/90">Como cadastrar</p>
            <p>1) Clique em “+ Atributo” e informe o nome (ex: Cor) e as opções separadas por vírgula (ex: Preto, Branco).</p>
            <p>2) Clique em “Gerar Variações” para criar as combinações automaticamente.</p>
            <p>3) Ajuste o SKU, EAN e quantidade de cada variação se necessário. Use “+ Variação Manual” para criar uma combinação específica.</p>
            <p class="mt-2 font-medium text-gray-800 dark:text-white/90">Como usar</p>
            <p>O estoque e o SKU serão controlados por variação. Marque “Herdar do pai” se a variação deve usar dados básicos do produto principal.</p>
        </div>
        <div id="atributos_container">
            <?php $__currentLoopData = $variacaoAtributos; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $index => $atributo): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                <div class="mb-3 grid grid-cols-1 gap-4 md:grid-cols-3" data-atributo-index="<?php echo e($index); ?>">
                    <input type="text" name="variacao_atributos[<?php echo e($index); ?>][atributo_nome]" value="<?php echo e($atributo['atributo_nome'] ?? ''); ?>" placeholder="Atributo (ex: Voltagem)" class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm text-gray-800 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90">
                    <input type="text" name="variacao_atributos[<?php echo e($index); ?>][opcoes]" value="<?php echo e(is_array($atributo['opcoes'] ?? null) ? implode(',', $atributo['opcoes']) : ($atributo['opcoes'] ?? '')); ?>" placeholder="Opções separadas por vírgula" class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm text-gray-800 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90">
                    <button type="button" onclick="removerItem(this)" class="text-error-500">Remover</button>
                </div>
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
        </div>
        <div class="mt-4 flex items-center gap-2">
            <button type="button" onclick="gerarVariacoes()" class="rounded-lg bg-brand-500 px-3 py-1.5 text-sm font-medium text-white hover:bg-brand-600">Gerar Variações</button>
            <button type="button" onclick="adicionarVariacao()" class="rounded-lg border border-gray-300 px-3 py-1.5 text-sm font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-700 dark:text-gray-300">+ Variação Manual</button>
        </div>

        <div id="variacoes_container" class="mt-4">
            <?php $__currentLoopData = $variacoes; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $index => $variacao): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                <div class="mb-3 grid grid-cols-1 gap-4 md:grid-cols-4" data-variacao-index="<?php echo e($index); ?>">
                    <input type="text" name="variacoes[<?php echo e($index); ?>][referencia_sku]" value="<?php echo e($variacao['referencia_sku'] ?? ''); ?>" placeholder="SKU Variação" class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm text-gray-800 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90">
                    <input type="text" name="variacoes[<?php echo e($index); ?>][opcoes_valores]" value="<?php echo e(is_array($variacao['opcoes_valores'] ?? null) ? implode(',', $variacao['opcoes_valores']) : ($variacao['opcoes_valores'] ?? '')); ?>" placeholder="Valores (ex: 25V)" class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm text-gray-800 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90">
                    <input type="text" name="variacoes[<?php echo e($index); ?>][ean_variacao]" value="<?php echo e($variacao['ean_variacao'] ?? ''); ?>" placeholder="EAN" class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm text-gray-800 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90">
                    <div class="flex items-center gap-2">
                        <input type="number" step="0.000001" min="0" name="variacoes[<?php echo e($index); ?>][quantidade]" value="<?php echo e($variacao['quantidade'] ?? ''); ?>" placeholder="Qtd" class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm text-gray-800 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90">
                        <button type="button" onclick="removerItem(this)" class="text-error-500">X</button>
                    </div>
                    <label class="flex items-center gap-2 text-sm text-gray-600 dark:text-gray-400">
                        <input type="checkbox" name="variacoes[<?php echo e($index); ?>][herdar_do_pai]" value="1" <?php echo e(($variacao['herdar_do_pai'] ?? false) ? 'checked' : ''); ?> class="rounded border-gray-300 text-brand-500 focus:ring-brand-500">
                        Herdar do pai
                    </label>
                </div>
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
        </div>
    </div>

    <!-- ABA 8: Composição -->
    <div class="tab-panel mb-6 hidden rounded-lg border border-gray-200 bg-white p-6 shadow-theme-sm dark:border-gray-800 dark:bg-gray-800" data-tab-panel="composicao">
        <div class="mb-4 flex flex-wrap items-center justify-between gap-2">
            <div class="flex items-center gap-2">
                <h2 class="text-lg font-semibold text-gray-800 dark:text-white/90">Composição / Kit</h2>
                <button type="button" data-help-toggle="composicao-help" class="inline-flex h-7 w-7 items-center justify-center rounded-full border border-gray-300 text-sm text-gray-600 hover:bg-gray-50 dark:border-gray-700 dark:text-gray-300" aria-label="Ajuda sobre composição">?</button>
            </div>
            <button type="button" onclick="adicionarComponente()" class="rounded-lg bg-brand-500 px-3 py-1.5 text-sm font-medium text-white hover:bg-brand-600">+ Adicionar Componente</button>
        </div>
        <div id="composicao-help" class="mb-4 hidden rounded-lg border border-brand-100 bg-brand-50 p-4 text-sm text-gray-700 dark:border-brand-900/40 dark:bg-gray-900 dark:text-gray-300">
            <p class="mb-2 font-medium text-gray-800 dark:text-white/90">Como funciona</p>
            <p>Use composição para montar um kit (produto pai) com itens já cadastrados (componentes). O estoque do kit pode depender da disponibilidade dos componentes.</p>
            <p class="mt-2 font-medium text-gray-800 dark:text-white/90">Como cadastrar</p>
            <p>1) Clique em “+ Adicionar Componente”.</p>
            <p>2) Selecione o produto componente e informe a quantidade usada no kit.</p>
            <p>3) Informe o custo unitário se quiser sobrescrever o custo do componente (senão, ele será usado automaticamente).</p>
            <p class="mt-2 font-medium text-gray-800 dark:text-white/90">Como usar</p>
            <p>Crie o kit como um produto normal e, nesta aba, adicione os itens que o compõem. Salve com o formato “Composição” selecionado.</p>
        </div>
        <div id="composicao_container">
            <?php $__currentLoopData = $composicoes; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $index => $comp): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                <div class="mb-3 grid grid-cols-1 gap-4 md:grid-cols-4" data-composicao-index="<?php echo e($index); ?>">
                    <select name="composicoes[<?php echo e($index); ?>][componente_id]" class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm text-gray-800 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90">
                        <option value="">Selecione o componente...</option>
                        <?php $__currentLoopData = $produtos; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $p): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                        <option value="<?php echo e($p->id); ?>" <?php echo e(($comp['componente_id'] ?? null) == $p->id ? 'selected' : ''); ?>><?php echo e($p->nome); ?></option>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                    </select>
                    <input type="number" min="1" name="composicoes[<?php echo e($index); ?>][quantidade]" value="<?php echo e(format_quantity($comp['quantidade'] ?? 1)); ?>" placeholder="Qtd" class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm text-gray-800 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90">
                    <input type="number" step="0.01" min="0" name="composicoes[<?php echo e($index); ?>][custo_unitario]" value="<?php echo e($comp['custo_unitario'] ?? ''); ?>" placeholder="Custo Unitário" class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm text-gray-800 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90">
                    <button type="button" onclick="removerItem(this)" class="text-error-500">Remover</button>
                </div>
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
        </div>
    </div>

    <?php if(function_exists('do_action')): ?>
    <?php do_action('admin.produtos.form.extra', $produto ?? null); ?>
    <?php endif; ?>
    <div class="flex justify-end">
        <button type="submit" class="rounded-lg bg-brand-500 px-6 py-2.5 text-sm font-medium text-white hover:bg-brand-600">Salvar</button>
    </div>
</form>

<script>
let fornecedorIndex = <?php echo e(count($fornecedoresSelecionados)); ?>;
let imagemIndex = 0;
let atributoIndex = <?php echo e(count($variacaoAtributos)); ?>;
let variacaoIndex = <?php echo e(count($variacoes)); ?>;
let composicaoIndex = <?php echo e(count($composicoes)); ?>;

let formatoSelect = null;
let toggleFormatoPanels = null;

function initTabs() {
    const buttons = document.querySelectorAll('.tab-btn');
    const panels = document.querySelectorAll('[data-tab-panel]');
    buttons.forEach(btn => {
        btn.addEventListener('click', () => {
            buttons.forEach(b => b.classList.remove('bg-brand-500', 'text-white'));
            buttons.forEach(b => b.classList.add('border', 'border-gray-300'));
            btn.classList.add('bg-brand-500', 'text-white');
            panels.forEach(p => p.classList.add('hidden'));
            const panel = document.querySelector(`[data-tab-panel="${btn.dataset.tab}"]`);
            if (panel) panel.classList.remove('hidden');

            if (formatoSelect && btn.dataset.tab === 'variacoes') {
                formatoSelect.value = 'variacao';
                if (toggleFormatoPanels) toggleFormatoPanels();
            }
            if (formatoSelect && btn.dataset.tab === 'composicao') {
                formatoSelect.value = 'composicao';
                if (toggleFormatoPanels) toggleFormatoPanels();
            }
        });
    });
}

function removerItem(btn) {
    const wrapper = btn.closest('[data-fornecedor-index], [data-atributo-index], [data-variacao-index], [data-composicao-index], [data-imagem-index]');
    if (wrapper) wrapper.remove();
}

function adicionarFornecedor() {
    const container = document.getElementById('fornecedores_container');
    const fornecedores = <?php echo json_encode($fornecedores ?? [], 15, 512) ?>;
    const options = fornecedores.map(f => `<option value="${f.id}">${f.nome}${f.cliente ? ' - ' + f.cliente.nome : ''}</option>`).join('');
    const html = `
        <div class="mb-4 rounded-lg border border-gray-200 p-4 dark:border-gray-700" data-fornecedor-index="${fornecedorIndex}">
            <div class="grid grid-cols-1 gap-4 md:grid-cols-3">
                <div>
                    <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300">Fornecedor</label>
                    <select name="fornecedores[${fornecedorIndex}][fornecedor_id]" class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm text-gray-800 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90">
                        <option value="">Selecione...</option>
                        ${options}
                    </select>
                </div>
                <div>
                    <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300">Descrição</label>
                    <input type="text" name="fornecedores[${fornecedorIndex}][descricao]" class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm text-gray-800 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90">
                </div>
                <div>
                    <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300">Código do Fornecedor</label>
                    <input type="text" name="fornecedores[${fornecedorIndex}][codigo_fornecedor]" class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm text-gray-800 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90">
                </div>
                <div>
                    <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300">Preço Compra</label>
                    <input type="text" inputmode="decimal" name="fornecedores[${fornecedorIndex}][preco_compra]" placeholder="0,00" class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm text-gray-800 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90">
                </div>
                <div>
                    <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300">Preço Custo</label>
                    <input type="text" inputmode="decimal" name="fornecedores[${fornecedorIndex}][preco_custo]" placeholder="0,00" class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm text-gray-800 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90">
                </div>
                <div>
                    <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300">Garantia (meses)</label>
                    <input type="number" min="0" name="fornecedores[${fornecedorIndex}][garantia_meses]" class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm text-gray-800 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90">
                </div>
            </div>
            <div class="mt-3 text-right">
                <button type="button" onclick="removerItem(this)" class="text-error-500 hover:underline">Remover</button>
            </div>
        </div>
    `;
    container.insertAdjacentHTML('beforeend', html);
    fornecedorIndex++;
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
                <button type="button" onclick="removerItem(this)" class="text-error-500 hover:underline">Remover</button>
            </div>
        </div>
    `;
    container.insertAdjacentHTML('beforeend', html);
    imagemIndex++;
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

function adicionarAtributo() {
    const container = document.getElementById('atributos_container');
    const html = `
        <div class="mb-3 grid grid-cols-1 gap-4 md:grid-cols-3" data-atributo-index="${atributoIndex}">
            <input type="text" name="variacao_atributos[${atributoIndex}][atributo_nome]" placeholder="Atributo (ex: Voltagem)" class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm text-gray-800 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90">
            <input type="text" name="variacao_atributos[${atributoIndex}][opcoes]" placeholder="Opções separadas por vírgula" class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm text-gray-800 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90">
            <button type="button" onclick="removerItem(this)" class="text-error-500">Remover</button>
        </div>
    `;
    container.insertAdjacentHTML('beforeend', html);
    atributoIndex++;
}

function adicionarVariacao(data = {}) {
    const container = document.getElementById('variacoes_container');
    const html = `
        <div class="mb-3 grid grid-cols-1 gap-4 md:grid-cols-4" data-variacao-index="${variacaoIndex}">
            <input type="text" name="variacoes[${variacaoIndex}][referencia_sku]" value="${data.sku || ''}" placeholder="SKU Variação" class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm text-gray-800 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90">
            <input type="text" name="variacoes[${variacaoIndex}][opcoes_valores]" value="${data.opcoes || ''}" placeholder="Valores (ex: 25V)" class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm text-gray-800 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90">
            <input type="text" name="variacoes[${variacaoIndex}][ean_variacao]" value="" placeholder="EAN" class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm text-gray-800 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90">
            <div class="flex items-center gap-2">
                <input type="number" step="0.001" min="0" name="variacoes[${variacaoIndex}][quantidade]" value="" placeholder="Qtd" class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm text-gray-800 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90">
                <button type="button" onclick="removerItem(this)" class="text-error-500">X</button>
            </div>
            <label class="flex items-center gap-2 text-sm text-gray-600 dark:text-gray-400">
                <input type="checkbox" name="variacoes[${variacaoIndex}][herdar_do_pai]" value="1" checked class="rounded border-gray-300 text-brand-500 focus:ring-brand-500">
                Herdar do pai
            </label>
        </div>
    `;
    container.insertAdjacentHTML('beforeend', html);
    variacaoIndex++;
}

function gerarVariacoes() {
    const atributos = Array.from(document.querySelectorAll('[data-atributo-index]')).map(row => {
        const inputs = row.querySelectorAll('input');
        return {
            nome: inputs[0]?.value?.trim(),
            opcoes: inputs[1]?.value?.split(',').map(v => v.trim()).filter(Boolean) || []
        };
    }).filter(a => a.nome && a.opcoes.length);

    if (!atributos.length) return;

    const skuBase = (document.querySelector('input[name="codigo_sku"]')?.value || document.querySelector('input[name="nome"]')?.value || 'SKU')
        .toUpperCase().replace(/\s+/g, '-').replace(/[^A-Z0-9-]/g, '');

    const combos = atributos.reduce((acc, attr) => {
        const values = attr.opcoes;
        if (!acc.length) return values.map(v => [v]);
        const out = [];
        acc.forEach(prev => values.forEach(v => out.push([...prev, v])));
        return out;
    }, []);

    const container = document.getElementById('variacoes_container');
    container.innerHTML = '';
    variacaoIndex = 0;
    combos.forEach(vals => {
        const sku = `${skuBase}-${vals.join('-').toUpperCase().replace(/\s+/g, '')}`;
        adicionarVariacao({ sku, opcoes: vals.join(',') });
    });
}

function adicionarComponente() {
    const container = document.getElementById('composicao_container');
    const produtos = <?php echo json_encode($produtos ?? [], 15, 512) ?>;
    const options = produtos.map(p => `<option value="${p.id}">${p.nome}</option>`).join('');
    const html = `
        <div class="mb-3 grid grid-cols-1 gap-4 md:grid-cols-4" data-composicao-index="${composicaoIndex}">
            <select name="composicoes[${composicaoIndex}][componente_id]" class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm text-gray-800 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90">
                <option value="">Selecione o componente...</option>
                ${options}
            </select>
            <input type="number" min="1" name="composicoes[${composicaoIndex}][quantidade]" value="1" class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm text-gray-800 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90">
            <input type="number" step="0.01" min="0" name="composicoes[${composicaoIndex}][custo_unitario]" placeholder="Custo Unitário" class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm text-gray-800 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90">
            <button type="button" onclick="removerItem(this)" class="text-error-500">Remover</button>
        </div>
    `;
    container.insertAdjacentHTML('beforeend', html);
    composicaoIndex++;
}

function validarEAN13(ean) {
    const clean = (ean || '').replace(/\D/g, '');
    if (clean.length !== 13) return false;
    let sum = 0;
    for (let i = 0; i < 12; i++) {
        const digit = parseInt(clean[i], 10);
        sum += (i % 2 === 0) ? digit : digit * 3;
    }
    const check = (10 - (sum % 10)) % 10;
    return check === parseInt(clean[12], 10);
}

document.addEventListener('DOMContentLoaded', () => {
    initTabs();

    formatoSelect = document.getElementById('formato');
    if (formatoSelect) {
        toggleFormatoPanels = () => {
            const isVariacao = formatoSelect.value === 'variacao';
            const isComposicao = formatoSelect.value === 'composicao';
            document.querySelector('[data-tab-panel="variacoes"]').classList.toggle('hidden', !isVariacao);
            document.querySelector('[data-tab-panel="composicao"]').classList.toggle('hidden', !isComposicao);
        };
        formatoSelect.addEventListener('change', toggleFormatoPanels);
        toggleFormatoPanels();
    }

    const ean = document.getElementById('ean');
    if (ean) {
        ean.addEventListener('blur', (e) => {
            if (!e.target.value) {
                e.target.setCustomValidity('');
                return;
            }
            if (!validarEAN13(e.target.value)) {
                e.target.setCustomValidity('EAN inválido.');
            } else {
                e.target.setCustomValidity('');
            }
        });
    }

    const ncm = document.getElementById('tributacao_ncm');
    if (ncm) {
        ncm.addEventListener('blur', (e) => {
            if (!e.target.value) {
                e.target.setCustomValidity('');
                return;
            }
            if (!/^\d{8}$/.test(e.target.value)) {
                e.target.setCustomValidity('NCM deve ter 8 dígitos.');
            } else {
                e.target.setCustomValidity('');
            }
        });
    }

    document.querySelectorAll('[data-help-toggle]').forEach(btn => {
        btn.addEventListener('click', () => {
            const targetId = btn.getAttribute('data-help-toggle');
            const target = document.getElementById(targetId);
            if (target) {
                target.classList.toggle('hidden');
            }
        });
    });
});
</script>
<?php /**PATH C:\laragon\www\LivreOS\LivreOS\sistema_livreos\resources\views\produtos\_form.blade.php ENDPATH**/ ?>