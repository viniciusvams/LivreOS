

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <title>DRE - <?php echo e($dataInicio); ?> a <?php echo e($dataFim); ?></title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 11px; color: #333; }
        h1 { font-size: 16px; margin: 0 0 4px 0; }
        .subtitle { font-size: 11px; color: #666; margin-bottom: 16px; }
        table { width: 100%; border-collapse: collapse; }
        td { padding: 6px 0; border-bottom: 1px solid #eee; }
        td:last-child { text-align: right; font-weight: 500; }
        .total { border-top: 2px solid #333; font-weight: bold; font-size: 12px; padding-top: 8px; }
        .valor-pos { color: #0d9488; }
        .valor-neg { color: #dc2626; }
        .footer { margin-top: 24px; font-size: 9px; color: #888; }
    </style>
</head>
<body>
    <h1>DRE - Demonstrativo do Resultado do Exercício</h1>
    <p class="subtitle">Período: <?php echo e(\Carbon\Carbon::parse($dataInicio)->format('d/m/Y')); ?> a <?php echo e(\Carbon\Carbon::parse($dataFim)->format('d/m/Y')); ?><?php if(!empty($centroCusto)): ?> | Centro de custo: <?php echo e($centroCusto->nome); ?><?php endif; ?></p>

    <table>
        <tr>
            <td>Receitas Operacionais</td>
            <td class="valor-pos">R$ <?php echo e(number_format($receitasOperacionais, 2, ',', '.')); ?></td>
        </tr>
        <tr>
            <td>(-) Despesas Operacionais</td>
            <td class="valor-neg">R$ <?php echo e(number_format($despesasOperacionais, 2, ',', '.')); ?></td>
        </tr>
        <tr class="total">
            <td>Receita Líquida</td>
            <td class="<?php echo e($receitaLiquida >= 0 ? 'valor-pos' : 'valor-neg'); ?>">R$ <?php echo e(number_format($receitaLiquida, 2, ',', '.')); ?></td>
        </tr>
        <tr>
            <td>(+) Receitas Não Operacionais</td>
            <td class="valor-pos">R$ <?php echo e(number_format($receitasNaoOperacionais, 2, ',', '.')); ?></td>
        </tr>
        <tr>
            <td>(-) Despesas Financeiras</td>
            <td class="valor-neg">R$ <?php echo e(number_format($despesasFinanceiras, 2, ',', '.')); ?></td>
        </tr>
        <tr class="total">
            <td>Resultado Antes do IR</td>
            <td class="<?php echo e($resultadoAntesIR >= 0 ? 'valor-pos' : 'valor-neg'); ?>">R$ <?php echo e(number_format($resultadoAntesIR, 2, ',', '.')); ?></td>
        </tr>
    </table>

    <p class="footer">Gerado em <?php echo e(now()->format('d/m/Y H:i')); ?> — Relatório Financeiro</p>
</body>
</html>
<?php /**PATH C:\laragon\www\LivreOS\LivreOS\sistema_livreos\resources\views\financeiro\relatorios\pdf\dre.blade.php ENDPATH**/ ?>