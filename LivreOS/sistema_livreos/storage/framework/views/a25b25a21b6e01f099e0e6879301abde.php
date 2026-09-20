

<?php
    // Função para processar HTML mantendo emojis para PDF
    function processarTextoParaPDF($html) {
        if (empty($html)) {
            return '';
        }
        
        // Converter HTML entities
        $html = html_entity_decode($html, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        
        // Não remover emojis - mantê-los no texto
        
        // Limpar HTML mantendo apenas tags básicas suportadas pelo DomPDF
        // Permitir: p, br, strong, b, em, i, u, ul, ol, li, div, span, h1-h6
        $html = strip_tags($html, '<p><br><br/><strong><b><em><i><u><ul><ol><li><div><span><h1><h2><h3><h4><h5><h6>');
        
        // Converter quebras de linha em <br> (apenas fora de tags HTML)
        $html = preg_replace('/(?<!>)\r\n|\r|\n(?!<)/', '<br>', $html);
        
        // Limpar múltiplos espaços (mas preservar dentro de tags)
        $html = preg_replace('/[ \t]+/', ' ', $html);
        
        // Limpar múltiplos <br> consecutivos (máximo 2)
        $html = preg_replace('/(<br\s*\/?>){3,}/i', '<br><br>', $html);
        
        // Garantir que parágrafos tenham espaçamento adequado
        $html = str_replace('</p>', '</p>', $html);
        $html = preg_replace('/<p>/i', '<p>', $html);
        
        // Limpar espaços no início e fim de cada tag
        $html = preg_replace('/>\s+/', '>', $html);
        $html = preg_replace('/\s+</', '<', $html);
        
        // Remover tags vazias
        $html = preg_replace('/<p>\s*<\/p>/i', '', $html);
        $html = preg_replace('/<div>\s*<\/div>/i', '', $html);
        
        return trim($html);
    }
    
    $empresaNome = $empresa?->nome ?? 'Empresa';
    $empresaRazao = $empresa?->razao_social ?? null;
    $empresaCnpj = $empresa?->cnpj ?? null;
    $empresaTelefone = $empresa?->telefone ?? null;
    $empresaEmail = $empresa?->email ?? null;
    $empresaLogo = null;
    if ($empresa?->logo_path) {
        try {
            $logoPathRel = ltrim(str_replace('\\', '/', (string) $empresa->logo_path), '/');
            $logoPath = storage_path('app/public/' . $logoPathRel);
            if ($logoPathRel !== '' && file_exists($logoPath) && is_readable($logoPath)) {
                $mime = @mime_content_type($logoPath) ?: 'image/png';
                $empresaLogo = 'data:' . $mime . ';base64,' . base64_encode(file_get_contents($logoPath));
            } else {
                $empresaLogo = asset('storage/' . $logoPathRel);
            }
        } catch (\Throwable $e) {
            $logoPathRel = ltrim(str_replace('\\', '/', (string) $empresa->logo_path), '/');
            $empresaLogo = $logoPathRel !== '' ? asset('storage/' . $logoPathRel) : null;
        }
    }

    $clienteBase = $ordem->unidade ?? $ordem->cliente;
    $contato = $ordem->contato;
    $endereco = $ordem->endereco ?? $clienteBase?->enderecos?->firstWhere('principal', true);

    $enderecoTexto = $endereco
        ? trim($endereco->logradouro . ' ' . $endereco->numero . ($endereco->complemento ? ' - ' . $endereco->complemento : '') . ', ' . $endereco->bairro . ' - ' . $endereco->cidade . '/' . $endereco->estado)
        : '-';

    $dataAbertura = optional($ordem->data_abertura)->format('d/m/Y H:i') ?: '-';
    $previstaInicio = optional($ordem->prevista_inicio)->format('d/m/Y')
        ?: optional($ordem->data_abertura)->format('d/m/Y')
        ?: '-';
    $previstaConclusao = optional($ordem->prevista_conclusao)->format('d/m/Y')
        ?: optional($ordem->real_conclusao)->format('d/m/Y')
        ?: '-';

    $totalProdutos = $ordem->produtos->sum('total');
    $totalServicos = $ordem->servicos->sum('total');
    $totalGeral = $ordem->total_geral ?? ($totalProdutos + $totalServicos);
    $descontos = $ordem->total_descontos ?? 0;
    $acrescimos = $ordem->total_acrescimos ?? $ordem->acrescimos_global ?? 0;
    $impostos = $ordem->total_impostos ?? $ordem->impostos_global ?? 0;
    $garantiaDias = $ordem->garantia_dias ?? null;
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="utf-8">
    <title>Comprovante de Entrega - <?php echo e(format_os_display($ordem)); ?> - <?php echo e($clienteBase->nome ?? '-'); ?></title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <style>
        * { box-sizing: border-box; }
        @page { size: A4; margin: 12mm; }
        body { font-family: Arial, Helvetica, sans-serif; color: #111827; margin: 0; background: #f3f4f6; }
        .page { max-width: 960px; margin: 24px auto; background: #fff; padding: 20px; position: relative; }
        header { display: grid; grid-template-columns: 140px 1fr 220px; gap: 16px; align-items: center; border-bottom: 2px solid #e5e7eb; padding-bottom: 16px; }
        .logo img { max-width: 140px; height: auto; }
        .emitente h1 { font-size: 18px; margin: 0 0 6px 0; }
        .emitente p, .contato p { margin: 2px 0; font-size: 13px; color: #374151; }
        .contato { text-align: right; }
        .titulo { margin: 18px 0 12px; display: flex; justify-content: space-between; align-items: baseline; }
        .titulo h2 { margin: 0; font-size: 20px; }
        .titulo span { font-size: 12px; color: #6b7280; }
        .bloco { margin-top: 16px; }
        .bloco h3 { margin: 0 0 8px 0; font-size: 14px; text-transform: uppercase; letter-spacing: .04em; color: #374151; }
        .dados { display: grid; grid-template-columns: 1fr 1fr; gap: 12px; font-size: 13px; color: #374151; }
        .dados p { margin: 2px 0; }
        table { width: 100%; border-collapse: collapse; font-size: 13px; margin-top: 8px; }
        th, td { border: 1px solid #e5e7eb; padding: 8px; }
        th { background: #f3f4f6; text-align: left; }
        .text-right { text-align: right; }
        .text-center { text-align: center; }
        .totais { margin-top: 12px; display: flex; justify-content: flex-end; }
        .totais table { width: 320px; }
        .assinaturas { display: grid; grid-template-columns: 1fr 1fr; gap: 24px; margin-top: 36px; }
        .assinaturas div { border-top: 1px solid #d1d5db; padding-top: 6px; text-align: center; font-size: 12px; color: #6b7280; }
        .observacao { border: 1px solid #e5e7eb; padding: 10px; background: #f9fafb; }
        .checklist-print { border: 1px solid #e5e7eb; padding: 12px; margin-top: 8px; border-radius: 6px; }
        .checklist-print h4 { margin: 0 0 8px 0; font-size: 14px; color: #374151; }
        .checklist-print .campo { display: flex; margin-bottom: 6px; font-size: 12px; }
        .checklist-print .campo-label { font-weight: 600; min-width: 140px; color: #4b5563; }
        .checklist-print .campo-valor { color: #111827; }
        .comprovante-texto { margin-top: 24px; padding: 16px; border: 2px solid #374151; background: #f9fafb; font-size: 14px; line-height: 1.6; }
        .comprovante-texto p { margin: 8px 0; }
        @media print {
            html, body {
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
            }
            body { background: #fff; }
            .page { margin: 0; box-shadow: none; padding: 0; }
            th {
                background: #f3f4f6 !important;
                color: #111827 !important;
            }
        }
    </style>
</head>
<body>
    <div class="page">
        <header>
            <div class="logo">
                <?php if($empresaLogo): ?>
                    <img src="<?php echo e($empresaLogo); ?>" alt="Logotipo">
                <?php endif; ?>
            </div>
            <div class="emitente">
                <h1><?php echo e($empresaNome); ?></h1>
                <?php if($empresaRazao): ?><p><?php echo e($empresaRazao); ?></p><?php endif; ?>
                <?php if($empresaCnpj): ?><p>CNPJ: <?php echo e($empresaCnpj); ?></p><?php endif; ?>
                <?php if($empresa && ($empresa->logradouro || $empresa->cidade)): ?>
                    <p><?php echo e(trim($empresa->logradouro . ' ' . $empresa->numero . ($empresa->complemento ? ' - ' . $empresa->complemento : '') . ', ' . $empresa->bairro . ' - ' . $empresa->cidade . '/' . $empresa->estado)); ?></p>
                    <?php if($empresa->cep): ?><p>CEP: <?php echo e($empresa->cep); ?></p><?php endif; ?>
                <?php endif; ?>
            </div>
            <div class="contato">
                <?php if($empresaTelefone): ?><p><strong>Tel:</strong> <?php echo e($empresaTelefone); ?></p><?php endif; ?>
                <?php if($empresaEmail): ?><p><strong><?php echo e($empresaEmail); ?></strong></p><?php endif; ?>
            </div>
        </header>

        <div class="titulo">
            <h2>COMPROVANTE DE ENTREGA - <?php echo e(format_os_display($ordem)); ?></h2>
            <span>Emissão: <?php echo e($dataAbertura); ?></span>
        </div>

        <table>
            <thead>
                <tr>
                    <th class="text-center">Status</th>
                    <th class="text-center">Data inicial</th>
                    <th class="text-center">Data final</th>
                    <th class="text-center">Garantia</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td class="text-center"><?php echo e(format_status_os_display($ordem->status)); ?></td>
                    <td class="text-center"><?php echo e($previstaInicio); ?></td>
                    <td class="text-center"><?php echo e($previstaConclusao); ?></td>
                    <td class="text-center">
                        <?php if($garantiaDias): ?>
                            <?php echo e($ordem->garantia_tipo ? $ordem->garantia_tipo . ' ' : ''); ?>(<?php echo e($garantiaDias); ?> dias)
                        <?php else: ?>
                            <?php echo e($ordem->garantia_tipo ?? '-'); ?>

                        <?php endif; ?>
                    </td>
                </tr>
            </tbody>
        </table>

        <div class="bloco">
            <h3>Dados do Cliente</h3>
            <div class="dados">
                <div>
                    <p><strong><?php echo e($clienteBase->nome ?? '-'); ?></strong></p>
                    <p>CPF/CNPJ: <?php echo e($clienteBase->cnpj ?? $clienteBase->cpf ?? $clienteBase->documento_estrangeiro ?? '-'); ?></p>
                    <p><?php echo e($contato->nome ?? '-'); ?> <?php echo e($contato?->telefone ? '(' . $contato->telefone . ')' : ''); ?></p>
                    <p><?php echo e($contato->email ?? '-'); ?></p>
                </div>
                <div class="text-right">
                    <p><?php echo e($enderecoTexto); ?></p>
                    <p>CEP: <?php echo e($endereco->cep ?? '-'); ?></p>
                </div>
            </div>
        </div>

        <?php if($ordem->equipamento_nome || $ordem->equipamento_marca || $ordem->equipamento_modelo || $ordem->equipamento_numero_serie || $ordem->equipamento_numero_patrimonio || $ordem->equipamento_acessorios || $ordem->equipamento): ?>
        <div class="bloco">
            <h3>Equipamento</h3>
            <div class="dados">
                <div>
                    <p><strong><?php echo e($ordem->equipamento_nome ?? $ordem->equipamento?->nome ?? '-'); ?></strong></p>
                    <p>Marca: <?php echo e($ordem->equipamento_marca ?? '-'); ?></p>
                    <p>Modelo: <?php echo e($ordem->equipamento_modelo ?? '-'); ?></p>
                </div>
                <div>
                    <p>Nº Série: <?php echo e($ordem->equipamento_numero_serie ?? '-'); ?></p>
                    <p>Nº Patrimônio: <?php echo e($ordem->equipamento_numero_patrimonio ?? '-'); ?></p>
                </div>
            </div>
            <?php if($ordem->equipamento_acessorios): ?>
                <div class="observacao"><strong>Acessórios:</strong> <?php echo $ordem->equipamento_acessorios; ?></div>
            <?php endif; ?>
        </div>
        <?php endif; ?>

        <?php if($ordem->relato_cliente): ?>
        <div class="bloco">
            <h3>Problemas/Relato do Cliente</h3>
            <div class="observacao"><?php echo processarTextoParaPDF($ordem->relato_cliente); ?></div>
        </div>
        <?php endif; ?>

        <?php if($ordem->diagnostico_tecnico): ?>
        <div class="bloco">
            <h3>Diagnóstico Técnico</h3>
            <div class="observacao"><?php echo processarTextoParaPDF($ordem->diagnostico_tecnico); ?></div>
        </div>
        <?php endif; ?>

        <?php if($ordem->servicos_realizados): ?>
        <div class="bloco">
            <h3>Descrição</h3>
            <div class="observacao"><?php echo processarTextoParaPDF($ordem->servicos_realizados); ?></div>
        </div>
        <?php endif; ?>

        <?php
            $checklistsPreenchidos = $ordem->checklists->filter(function($c) {
                $respostas = $c->respostas ?? [];
                $temRespostas = collect($respostas)->filter(fn($v) => $v !== '' && $v !== null && $v !== false)->count() > 0;
                $temObservacoes = !empty(trim($c->observacoes ?? ''));
                return $temRespostas || $temObservacoes;
            });
        ?>
        <?php if($checklistsPreenchidos->count() > 0): ?>
        <div class="bloco">
            <h3>Checklists</h3>
            <?php $__currentLoopData = $checklistsPreenchidos; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $checklist): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                <?php
                    $modelo = $checklist->checklistModel;
                    $campos = $modelo->campos ?? [];
                    $respostas = $checklist->respostas ?? [];
                ?>
                <div class="checklist-print">
                    <h4><?php echo e($modelo->nome); ?></h4>
                    <?php $__currentLoopData = $campos; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $index => $campo): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                        <?php
                            $valor = $respostas[$index] ?? null;
                            if ($valor === '' || $valor === null) continue;
                            if (($campo['tipo'] ?? '') === 'checkbox') {
                                $valor = $valor ? 'Sim' : 'Não';
                            }
                            if (($campo['tipo'] ?? '') === 'data' && $valor) {
                                try { $valor = \Carbon\Carbon::parse($valor)->format('d/m/Y'); } catch (\Exception $e) {}
                            }
                        ?>
                        <div class="campo">
                            <span class="campo-label"><?php echo e($campo['label'] ?? 'Campo ' . ($index + 1)); ?>:</span>
                            <span class="campo-valor"><?php echo e(is_string($valor) ? $valor : (is_bool($valor) ? ($valor ? 'Sim' : 'Não') : (string) $valor)); ?></span>
                        </div>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                    <?php if(!empty(trim($checklist->observacoes ?? ''))): ?>
                        <div class="campo" style="margin-top: 8px; border-top: 1px dashed #e5e7eb; padding-top: 8px;">
                            <span class="campo-label">Observações:</span>
                            <span class="campo-valor"><?php echo e($checklist->observacoes); ?></span>
                        </div>
                    <?php endif; ?>
                </div>
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
        </div>
        <?php endif; ?>

        <?php if($ordem->servicos->count()): ?>
        <div class="bloco">
            <table>
                <thead>
                    <tr>
                        <th>Serviço</th>
                        <th class="text-center" width="10%">Qtd</th>
                        <th class="text-center" width="15%">Unt</th>
                        <th class="text-right" width="15%">Subtotal</th>
                    </tr>
                </thead>
                <tbody>
                    <?php $__currentLoopData = $ordem->servicos; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $item): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                    <tr>
                        <td><?php echo e($item->descricao ?? $item->servico?->nome ?? '-'); ?></td>
                        <td class="text-center"><?php echo e($item->cobranca_tipo === 'horas' ? $item->quantidade_horas : $item->quantidade); ?></td>
                        <td class="text-center"><?php echo e(number_format($item->valor_unitario ?? 0, 2, ',', '.')); ?></td>
                        <td class="text-right">R$ <?php echo e(number_format($item->total ?? 0, 2, ',', '.')); ?></td>
                    </tr>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                    <tr>
                        <td colspan="3" class="text-right"><strong>Total serviços</strong></td>
                        <td class="text-right"><strong>R$ <?php echo e(number_format($totalServicos, 2, ',', '.')); ?></strong></td>
                    </tr>
                </tbody>
            </table>
        </div>
        <?php endif; ?>

        <?php if($ordem->produtos->count()): ?>
        <div class="bloco">
            <table>
                <thead>
                    <tr>
                        <th>Produto</th>
                        <th class="text-center" width="10%">Qtd</th>
                        <th class="text-center" width="15%">Unt</th>
                        <th class="text-right" width="15%">Subtotal</th>
                    </tr>
                </thead>
                <tbody>
                    <?php $__currentLoopData = $ordem->produtos; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $item): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                    <?php
                        $produto = $item->produto;
                        $ehKit = $produto && $produto->formato === 'composicao' && $produto->composicoes->count() > 0;
                        $itensKit = $ehKit ? $produto->composicoes->map(fn($c) => ($c->componente?->nome ?? 'Componente'))->implode(', ') : '';
                        $ehVariacao = !$ehKit && $item->variacao;
                        $labelVariacao = $ehVariacao ? (($item->variacao->referencia_sku ?? '') . (is_array($item->variacao->opcoes_valores ?? null) ? (' (' . implode(', ', $item->variacao->opcoes_valores) . ')') : ($item->variacao->opcoes_valores ? (' (' . $item->variacao->opcoes_valores . ')') : ''))) : '';
                        $labelVariacao = trim($labelVariacao);
                    ?>
                    <tr>
                        <td>
                            <?php if($ehKit): ?>
                                <?php echo e($produto?->nome ?? '-'); ?>

                                <?php if($itensKit): ?><span class="text-gray-500">(<?php echo e($itensKit); ?>)</span><?php endif; ?>
                            <?php elseif($ehVariacao): ?>
                                <?php echo e(($produto?->nome ?? '-') . ($labelVariacao ? ' - ' . $labelVariacao : '')); ?>

                            <?php else: ?>
                                <?php echo e($item->descricao ?? $produto?->nome ?? '-'); ?>

                            <?php endif; ?>
                        </td>
                        <td class="text-center"><?php echo e(format_quantity($item->quantidade ?? 0)); ?></td>
                        <td class="text-center"><?php echo e(number_format($item->valor_unitario ?? 0, 2, ',', '.')); ?></td>
                        <td class="text-right">R$ <?php echo e(number_format($item->total ?? 0, 2, ',', '.')); ?></td>
                    </tr>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                    <tr>
                        <td colspan="3" class="text-right"><strong>Total produtos</strong></td>
                        <td class="text-right"><strong>R$ <?php echo e(number_format($totalProdutos, 2, ',', '.')); ?></strong></td>
                    </tr>
                </tbody>
            </table>
        </div>
        <?php endif; ?>

        <div class="totais">
            <table>
                <tbody>
                    <tr>
                        <td>Total serviços</td>
                        <td class="text-right">R$ <?php echo e(number_format($totalServicos, 2, ',', '.')); ?></td>
                    </tr>
                    <tr>
                        <td>Total produtos</td>
                        <td class="text-right">R$ <?php echo e(number_format($totalProdutos, 2, ',', '.')); ?></td>
                    </tr>
                    <tr>
                        <td>Descontos</td>
                        <td class="text-right">R$ <?php echo e(number_format($descontos, 2, ',', '.')); ?></td>
                    </tr>
                    <tr>
                        <td>Acréscimos</td>
                        <td class="text-right">R$ <?php echo e(number_format($acrescimos, 2, ',', '.')); ?></td>
                    </tr>
                    <tr>
                        <td><strong>Total geral</strong></td>
                        <td class="text-right"><strong>R$ <?php echo e(number_format($totalGeral, 2, ',', '.')); ?></strong></td>
                    </tr>
                </tbody>
            </table>
        </div>

        <?php
            $htmlAntesAssinatura = trim((string) ($comprovanteHtmlAntesAssinatura ?? ''));
        ?>
        <?php if($htmlAntesAssinatura !== ''): ?>
        <div class="comprovante-texto observacao">
            <?php echo processarTextoParaPDF($htmlAntesAssinatura); ?>

        </div>
        <?php endif; ?>

        <div class="assinaturas">
            <div>Assinatura do cliente</div>
            <div>Assinatura do técnico</div>
        </div>
    </div>

    <script>
        window.print();
    </script>
</body>
</html>
<?php /**PATH C:\laragon\www\LivreOS\LivreOS\sistema_livreos\resources\views\ordens_servico\comprovante.blade.php ENDPATH**/ ?>