<?php

/**
 * Componente da aplicação LivreOS
 *
 * @author    viniciusvams
 * @copyright 2024-2026 LivreOS
 * @license   https://www.gnu.org/licenses/agpl-3.0.txt AGPL-3.0
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 */

namespace App\Services;

use PhpOffice\PhpWord\Element\Table;
use PhpOffice\PhpWord\IOFactory;
use PhpOffice\PhpWord\PhpWord;
use PhpOffice\PhpWord\SimpleType\Jc;

/**
 * Gera .docx com tags ${...} alinhado ao layout do PDF/impressão padrão do ERP
 * ({@see resources/views/propostas-comerciais/partials/_proposta-padrao-corpo.blade.php}).
 */
class PropostaDocumentoModeloDocxFabrica
{
    private const INDIGO = '4F46E5';

    private const INDIGO_DARK = '1E1B4B';

    private const GRAY600 = '4B5563';

    private const GRAY500 = '6B7280';

    private const RED600 = 'DC2626';

    public static function gerarEstiloImpressaoOs(string $caminhoAbsoluto): void
    {
        $dir = dirname($caminhoAbsoluto);
        if (! is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        $word = new PhpWord;
        $word->setDefaultFontName('Arial');
        $word->setDefaultFontSize(10);

        $section = $word->addSection([
            'marginTop' => 1200,
            'marginBottom' => 1200,
            'marginLeft' => 1200,
            'marginRight' => 1200,
        ]);

        $borderCabRodape = ['borderBottomSize' => 12, 'borderBottomColor' => self::INDIGO];

        // ── Cabeçalho (tabela 3 colunas: logo | dados emitente | contato) ──
        $cab = $section->addTable([
            'borderSize' => 0,
            'cellMargin' => 100,
            'width' => 100 * 50,
            'unit' => 'pct',
        ]);
        $cab->addRow();
        $cab->addCell(1400, $borderCabRodape)->addText('${empresa_logo}', ['size' => 8, 'color' => '9CA3AF']);
        $mid = $cab->addCell(5200, $borderCabRodape);
        $mid->addText('${empresa_nome}', ['bold' => true, 'size' => 16, 'color' => self::INDIGO_DARK]);
        $mid->addText('${empresa_razao_social}', ['size' => 9.5, 'color' => '374151']);
        $mid->addText('CNPJ: ${empresa_cnpj}', ['size' => 9.5, 'color' => '374151']);
        $mid->addText('${empresa_endereco}', ['size' => 9.5, 'color' => '374151']);
        $cont = $cab->addCell(2800, array_merge($borderCabRodape, ['valign' => 'top']));
        $cont->addText('Tel. ${empresa_telefone}', ['size' => 9.5, 'color' => self::GRAY600], ['alignment' => Jc::END]);
        $cont->addText('Cel. ${empresa_celular}', ['size' => 9.5, 'color' => self::GRAY600], ['alignment' => Jc::END]);
        $cont->addText('${empresa_email}', ['size' => 9.5, 'color' => self::GRAY600], ['alignment' => Jc::END]);

        $section->addTextBreak(1);

        // ── Faixa documento (título + nº / status) ──
        $faixa = $section->addTable(['borderSize' => 0, 'cellMargin' => 60, 'width' => 100 * 50, 'unit' => 'pct']);
        $faixa->addRow();
        $faixa->addCell(5200)->addText('PROPOSTA COMERCIAL', [
            'bold' => true,
            'size' => 13,
            'color' => self::INDIGO,
        ]);
        $faixa->addCell(4200)->addText('#${proposta_id} · v${proposta_versao}', [
            'bold' => true,
            'size' => 18,
            'color' => self::INDIGO,
        ], ['alignment' => Jc::END]);
        $faixa->addRow();
        $faixa->addCell(5200)->addText('${proposta_titulo}', ['size' => 10, 'color' => self::GRAY500]);
        $faixa->addCell(4200)->addText('${proposta_status_label}', ['size' => 9, 'color' => self::GRAY500], ['alignment' => Jc::END]);

        $section->addTextBreak(1);

        // ── Secção Dados (como o PDF padrão) ──
        $section->addText('DADOS', [
            'bold' => true,
            'size' => 9.5,
            'color' => self::GRAY500,
        ]);
        $section->addText(str_repeat(' ', 1), ['size' => 3]); // micro espaço após título
        $dados = $section->addTable(['borderSize' => 0, 'cellMargin' => 50, 'width' => 100 * 50, 'unit' => 'pct']);
        self::linhaDado($dados, 'Cliente', '${cliente_nome}');
        self::linhaDado($dados, 'Emissão', '${data_emissao}');
        self::linhaDado($dados, 'Válido até', '${validade_em}');
        self::linhaDado($dados, 'Vendedor', '${vendedor_nome}');

        $section->addTextBreak(1);

        // ── Itens (cabeçalho roxo como no PDF) ──
        $section->addText('ITENS', [
            'bold' => true,
            'size' => 9.5,
            'color' => self::GRAY500,
        ]);
        $section->addTextBreak(1);

        $th = ['bgColor' => self::INDIGO, 'valign' => 'center'];
        $txW = ['bold' => true, 'size' => 9, 'color' => 'FFFFFF'];

        $itens = $section->addTable([
            'borderSize' => 6,
            'borderColor' => 'E5E7EB',
            'cellMargin' => 80,
            'width' => 100 * 50,
            'unit' => 'pct',
        ]);
        $itens->addRow(null, ['tblHeader' => true]);
        $itens->addCell(450, $th)->addText('#', $txW);
        $itens->addCell(900, $th)->addText('Tipo', $txW);
        $itens->addCell(4000, $th)->addText('Descrição', $txW);
        $itens->addCell(800, $th)->addText('Qtd', $txW, ['alignment' => Jc::END]);
        $itens->addCell(1100, $th)->addText('Unit.', $txW, ['alignment' => Jc::END]);
        $itens->addCell(900, $th)->addText('Desc.', $txW, ['alignment' => Jc::END]);
        $itens->addCell(1150, $th)->addText('Total', $txW, ['alignment' => Jc::END]);

        $itens->addRow();
        $itens->addCell()->addText('${item_linha}', ['size' => 9]);
        $itens->addCell()->addText('${item_tipo}', ['size' => 9]);
        $itens->addCell()->addText('${item_descricao}', ['size' => 9]);
        $itens->addCell()->addText('${item_quantidade}', ['size' => 9], ['alignment' => Jc::END]);
        $itens->addCell()->addText('${item_preco_unitario}', ['size' => 9], ['alignment' => Jc::END]);
        $itens->addCell()->addText('${item_desconto}', ['size' => 9], ['alignment' => Jc::END]);
        $itens->addCell()->addText('${item_total}', ['size' => 9], ['alignment' => Jc::END]);

        $section->addTextBreak(1);

        // ── Totais (estilo rodapé da tabela do PDF) ──
        $tot = $section->addTable(['borderSize' => 0, 'cellMargin' => 50, 'width' => 100 * 50, 'unit' => 'pct']);
        self::linhaTotal($tot, 'Subtotal produtos', 'R$ ${total_produtos}', false, false);
        self::linhaTotal($tot, 'Subtotal serviços', 'R$ ${total_servicos}', false, false);
        self::linhaTotal($tot, 'Descontos', '- R$ ${total_descontos}', true, false);
        self::linhaTotal($tot, 'Total geral', 'R$ ${total_geral}', false, true);

        $section->addTextBreak(1);

        // ── Textos longos: sempre texto plano no Word (tags HTML removidas pelo ERP).
        //    Variáveis *_html existem só em modelos .html — não as use aqui. ──
        $section->addText('DESCRIÇÃO (texto plano)', [
            'bold' => true,
            'size' => 9.5,
            'color' => self::GRAY500,
        ]);
        $section->addTextBreak(1);
        $section->addText('${proposta_descricao}', ['size' => 10, 'color' => '374151']);

        $section->addTextBreak(1);

        $section->addText('OBSERVAÇÕES AO CLIENTE (texto plano)', [
            'bold' => true,
            'size' => 9.5,
            'color' => self::GRAY500,
        ]);
        $section->addTextBreak(1);
        $section->addText('${observacoes}', ['size' => 10, 'color' => '374151']);

        $section->addTextBreak(1);

        $section->addText('OBSERVAÇÕES INTERNAS (texto plano)', [
            'bold' => true,
            'size' => 9.5,
            'color' => self::GRAY500,
        ]);
        $section->addTextBreak(1);
        $section->addText('${observacoes_internas}', ['size' => 10, 'color' => '374151']);

        $section->addTextBreak(1);
        $section->addText(
            'No modelo HTML do ERP pode usar variáveis com sufixo _html (descrição e observações) para manter negrito e listas; no Word use apenas as três variáveis de texto plano acima — ver docs/PROPOSTA_DOCX_TAGS.md.',
            ['size' => 8, 'color' => '9CA3AF', 'italic' => true]
        );

        $section->addTextBreak(2);
        $section->addText(
            'Gerado em ${gerado_em} · Proposta #${proposta_id} v${proposta_versao} · LivreOS — ERP Open Source Livre',
            ['size' => 8.5, 'color' => '9CA3AF'],
            ['alignment' => Jc::CENTER]
        );

        $writer = IOFactory::createWriter($word, 'Word2007');
        $writer->save($caminhoAbsoluto);
    }

    private static function linhaDado(Table $table, string $label, string $valor): void
    {
        $table->addRow();
        $table->addCell(2200)->addText($label, ['bold' => true, 'size' => 9.5, 'color' => self::GRAY600]);
        $table->addCell(8000)->addText($valor, ['size' => 10, 'color' => '111827']);
    }

    private static function linhaTotal(
        Table $table,
        string $label,
        string $valor,
        bool $vermelho,
        bool $destaque
    ): void {
        $table->addRow();
        $color = $vermelho ? self::RED600 : ($destaque ? self::INDIGO : '111827');
        $size = $destaque ? 11 : 10;
        $font = ['size' => $size, 'color' => $color, 'bold' => $destaque];
        $para = ['alignment' => Jc::END];

        $table->addCell(null, ['gridSpan' => 6])->addText($label, $font, $para);
        $table->addCell(2000)->addText($valor, $font, $para);
    }
}
