<?php

/**
 * Extração de dados de PDF (boleto / NFS-e).
 *
 * Ordem: filtro `compras.pdf.extrair` → pdftotext (Poppler) → biblioteca PHP (smalot/pdfparser,
 * adequada a hospedagem compartilhada, só PDF digital com texto) → OCR (pdftoppm + Tesseract).
 *
 * Windows: `ERP_POPPLER_BIN`, `ERP_TESSERACT`, `ERP_TESSERACT_LANG` quando binários não estão no PATH.
 *
 * @copyright 2024-2026 LivreOS
 * @license   https://www.gnu.org/licenses/agpl-3.0.txt AGPL-3.0
 */
namespace Compras\Services;

class CompraPdfExtratorService
{
    /** Máximo de páginas para OCR (evita travar em PDFs enormes). */
    private const OCR_MAX_PAGES = 5;

    /**
     * @return array{ok: bool, texto_bruto?: string, campos?: array, aviso?: string, erro?: string}
     */
    public function extrair(string $pathPdf): array
    {
        if (function_exists('apply_filters')) {
            $custom = apply_filters('compras.pdf.extrair', null, $pathPdf);
            if (is_array($custom) && isset($custom['ok'])) {
                return $custom;
            }
        }

        if (! is_readable($pathPdf)) {
            return ['ok' => false, 'erro' => 'Arquivo PDF ilegível.'];
        }

        $texto = '';
        $pdftotext = $this->resolverExecutavel('pdftotext');
        if ($pdftotext !== null) {
            $tmp = tempnam(sys_get_temp_dir(), 'erp_pdf_');
            if ($tmp !== false) {
                $null = PHP_OS_FAMILY === 'Windows' ? 'nul' : '/dev/null';
                $cmd = sprintf(
                    '%s -layout %s %s 2>%s',
                    $this->quoteCmd($pdftotext),
                    escapeshellarg($pathPdf),
                    escapeshellarg($tmp),
                    $null
                );
                @exec($cmd);
                $texto = @file_get_contents($tmp) ?: '';
                @unlink($tmp);
            }
        }

        $viaOcr = false;
        $viaPhp = false;
        if (trim($texto) === '') {
            $phpTexto = $this->extrairTextoPdfDigitalPhp($pathPdf);
            if (trim($phpTexto) !== '') {
                $texto = $phpTexto;
                $viaPhp = true;
            }
        }
        if (trim($texto) === '') {
            $ocr = $this->extrairTextoOcr($pathPdf);
            if ($ocr !== null) {
                $texto = $ocr;
                $viaOcr = true;
            }
        }

        if (trim($texto) === '') {
            return [
                'ok' => false,
                'erro' => 'Não foi possível extrair texto do PDF. PDFs escaneados exigem Tesseract (e Poppler) ou o filtro compras.pdf.extrair. PDF digital: confira se o arquivo tem texto selecionável; em hospedagem compartilhada o pacote smalot/pdfparser (Composer) deve estar instalado.',
            ];
        }

        $campos = $this->heuristicaBoletoOuNota($texto);
        $aviso = 'Dados extraídos por heurística; conferência manual obrigatória. XML da NF-e é o método recomendado.';
        if ($viaPhp) {
            $aviso = 'Texto extraído em PHP (PDF digital); ordem/layout podem diferir do original. ' . $aviso;
        }
        if ($viaOcr) {
            $aviso = 'Texto obtido por OCR (Tesseract); pode conter erros. ' . $aviso;
        }

        return [
            'ok' => true,
            'texto_bruto' => $texto,
            'campos' => $campos,
            'aviso' => $aviso,
        ];
    }

    /**
     * Extrai texto de PDF “digital” (fontes/texto embutido) sem binários — útil em shared hosting.
     */
    private function extrairTextoPdfDigitalPhp(string $pathPdf): string
    {
        if (! class_exists(\Smalot\PdfParser\Parser::class)) {
            return '';
        }

        try {
            $parser = new \Smalot\PdfParser\Parser;
            $pdf = $parser->parseFile($pathPdf);
            $raw = $pdf->getText();

            return is_string($raw) ? $raw : '';
        } catch (\Throwable) {
            return '';
        }
    }

    private function extrairTextoOcr(string $pathPdf): ?string
    {
        $pdftoppm = $this->resolverExecutavel('pdftoppm');
        $tesseract = $this->resolverTesseract();
        if ($pdftoppm === null || $tesseract === null) {
            return null;
        }

        $dir = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'erp_pdf_ocr_' . bin2hex(random_bytes(8));
        if (! @mkdir($dir, 0700, true)) {
            return null;
        }

        $prefix = $dir . DIRECTORY_SEPARATOR . 'pg';
        $null = PHP_OS_FAMILY === 'Windows' ? 'nul' : '/dev/null';
        $cmdPpm = sprintf(
            '%s -png -f 1 -l %d %s %s 2>%s',
            $this->quoteCmd($pdftoppm),
            self::OCR_MAX_PAGES,
            escapeshellarg($pathPdf),
            escapeshellarg($prefix),
            $null
        );
        @exec($cmdPpm);

        $imagens = glob($dir . DIRECTORY_SEPARATOR . 'pg-*.png') ?: [];
        natsort($imagens);
        $partes = [];
        $lang = getenv('ERP_TESSERACT_LANG') ?: 'por';
        foreach ($imagens as $png) {
            $cmdTs = sprintf(
                '%s %s stdout -l %s 2>%s',
                $this->quoteCmd($tesseract),
                escapeshellarg($png),
                escapeshellarg($lang),
                $null
            );
            $out = [];
            @exec($cmdTs, $out);
            $partes[] = implode("\n", $out);
        }

        foreach ($imagens as $png) {
            @unlink($png);
        }
        @rmdir($dir);

        $junto = trim(implode("\n\n", array_filter($partes)));

        return $junto !== '' ? $junto : null;
    }

    private function resolverTesseract(): ?string
    {
        $env = getenv('ERP_TESSERACT');
        if ($env !== false && $env !== '' && is_file($env)) {
            return $env;
        }

        return $this->resolverExecutavel('tesseract');
    }

    private function quoteCmd(string $path): string
    {
        if (PHP_OS_FAMILY === 'Windows' && str_contains($path, ' ')) {
            return '"' . str_replace('"', '\\"', $path) . '"';
        }

        return escapeshellarg($path);
    }

    private function resolverExecutavel(string $nomeSemExt): ?string
    {
        $ext = PHP_OS_FAMILY === 'Windows' ? '.exe' : '';
        $which = PHP_OS_FAMILY === 'Windows' ? 'where' : 'which';
        $out = @shell_exec($which . ' ' . escapeshellarg($nomeSemExt) . (PHP_OS_FAMILY === 'Windows' ? ' 2>nul' : ' 2>/dev/null'));
        if ($out !== null && $out !== '') {
            $first = trim(explode("\n", trim($out))[0]);
            if ($first !== '' && (PHP_OS_FAMILY !== 'Windows' || is_file($first))) {
                return $first;
            }
        }

        $candidatos = [];
        if (PHP_OS_FAMILY === 'Windows') {
            $popplerBin = getenv('ERP_POPPLER_BIN');
            if ($popplerBin !== false && $popplerBin !== '') {
                $candidatos[] = rtrim($popplerBin, '\\/') . DIRECTORY_SEPARATOR . $nomeSemExt . $ext;
            }
            $candidatos = array_merge($candidatos, [
                'C:\\ProgramData\\chocolatey\\lib\\poppler\\tools\\Library\\bin\\' . $nomeSemExt . $ext,
                'C:\\Program Files\\poppler\\Library\\bin\\' . $nomeSemExt . $ext,
                'C:\\poppler\\Library\\bin\\' . $nomeSemExt . $ext,
            ]);
        }

        foreach ($candidatos as $p) {
            if ($p !== '' && @is_file($p)) {
                return $p;
            }
        }

        return null;
    }

    /**
     * @return array<string, mixed>
     */
    private function heuristicaBoletoOuNota(string $texto): array
    {
        $out = [];
        if (preg_match('/(\d{5}\.\d{5}\s+\d{5}\.\d{6}\s+\d{5}\.\d{6}\s+\d\s+[\d.]+)/', $texto, $m)) {
            $out['linha_digitavel_detectada'] = preg_replace('/\s+/', '', $m[1]);
        }
        if (preg_match('/(\d{44})/', preg_replace('/\D/', '', $texto), $m)) {
            $out['possivel_chave_44'] = $m[1];
        }
        if (preg_match('/(\d{2}\.\d{3}\.\d{3}\/\d{4}-\d{2})/', $texto, $m)) {
            $out['cnpj_formatado'] = $m[1];
        }

        return $out;
    }
}
