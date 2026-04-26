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

if (!function_exists('valor_por_extenso_ptbr')) {
    /**
     * Converte valor em reais (float) para extenso em português (ex.: 1234.56 → "um mil, duzentos e trinta e quatro reais e cinquenta e seis centavos").
     * Útil para recibos e documentos fiscais.
     */
    function valor_por_extenso_ptbr(float $valor): string
    {
        $valor = round($valor, 2);
        $inteiro = (int) floor($valor);
        $centavos = (int) round(($valor - $inteiro) * 100);

        $unidades = ['', 'um', 'dois', 'três', 'quatro', 'cinco', 'seis', 'sete', 'oito', 'nove'];
        $dez = ['', 'dez', 'vinte', 'trinta', 'quarenta', 'cinquenta', 'sessenta', 'setenta', 'oitenta', 'noventa'];
        $especiais = [10 => 'dez', 11 => 'onze', 12 => 'doze', 13 => 'treze', 14 => 'catorze', 15 => 'quinze', 16 => 'dezesseis', 17 => 'dezessete', 18 => 'dezoito', 19 => 'dezenove'];
        $centenas = ['', 'cento', 'duzentos', 'trezentos', 'quatrocentos', 'quinhentos', 'seiscentos', 'setecentos', 'oitocentos', 'novecentos'];

        $escrever_inteiro = function ($n) use (&$escrever_inteiro, $unidades, $dez, $especiais, $centenas) {
            if ($n === 0) {
                return '';
            }
            if ($n === 100) {
                return 'cem';
            }
            if ($n < 10) {
                return $unidades[$n];
            }
            if ($n < 20) {
                return $especiais[$n];
            }
            if ($n < 100) {
                $d = (int) floor($n / 10);
                $u = $n % 10;
                return $dez[$d] . ($u > 0 ? ' e ' . $unidades[$u] : '');
            }
            if ($n < 1000) {
                $c = (int) floor($n / 100);
                $resto = $n % 100;
                return $centenas[$c] . ($resto > 0 ? ' e ' . $escrever_inteiro($resto) : '');
            }
            if ($n < 1000000) {
                $mil = (int) floor($n / 1000);
                $resto = $n % 1000;
                $txtMil = $mil === 1 ? 'mil' : $escrever_inteiro($mil) . ' mil';
                return $txtMil . ($resto > 0 ? ', ' . $escrever_inteiro($resto) : '');
            }
            if ($n < 1000000000) {
                $milhao = (int) floor($n / 1000000);
                $resto = $n % 1000000;
                $txtMilhao = $milhao === 1 ? 'um milhão' : $escrever_inteiro($milhao) . ' milhões';
                return $txtMilhao . ($resto > 0 ? ', ' . $escrever_inteiro($resto) : '');
            }
            return (string) $n;
        };

        $reais = $escrever_inteiro($inteiro);
        $real_plural = ($inteiro === 1 ? 'real' : 'reais');
        $centavos_plural = ($centavos === 1 ? 'centavo' : 'centavos');
        $centavos_txt = $escrever_inteiro($centavos);

        if ($inteiro === 0 && $centavos === 0) {
            return 'zero reais';
        }
        if ($inteiro === 0) {
            return $centavos_txt . ' ' . $centavos_plural;
        }
        if ($centavos === 0) {
            return $reais . ' ' . $real_plural;
        }
        return $reais . ' ' . $real_plural . ' e ' . $centavos_txt . ' ' . $centavos_plural;
    }
}

if (!function_exists('format_br_decimal')) {
    /**
     * Formata número no padrão brasileiro (vírgula decimal, ponto milhar).
     * Ex: 279.9 → "279,90", 1250 → "1.250,00". Retorna '' para null/vazio.
     */
    function format_br_decimal($value, int $decimals = 2): string
    {
        if ($value === null || $value === '') {
            return '';
        }
        $num = (float) $value;

        return number_format($num, $decimals, ',', '.');
    }
}

if (!function_exists('parse_br_number')) {
    /**
     * Converte valor numérico em formato brasileiro (1.738,36) para float.
     * Aceita: 1.738,36 | 1738,36 | 1738.36
     */
    function parse_br_number(mixed $value): float
    {
        if ($value === null || $value === '') {
            return 0.0;
        }
        if (is_numeric($value) && !is_string($value)) {
            return (float) $value;
        }
        $str = trim((string) $value);
        if ($str === '') {
            return 0.0;
        }
        $lastComma = strrpos($str, ',');
        $lastDot = strrpos($str, '.');

        if ($lastComma !== false && ($lastDot === false || $lastComma > $lastDot)) {
            $str = str_replace('.', '', $str);
            $str = str_replace(',', '.', $str);
        } else {
            $str = str_replace(',', '', $str);
        }

        return (float) $str;
    }
}

if (!function_exists('listar_bandeiras_pagamento')) {
    /**
     * Bandeiras com taxa ativa em algum adquirente (core — model TaxaAdquirente, sem plugin).
     *
     * @return array<int, array{value: string, label: string}>
     */
    function listar_bandeiras_pagamento(): array
    {
        $bandeirasValidas = [
            'master' => 'Mastercard',
            'visa' => 'Visa',
            'elo' => 'Elo',
            'amex' => 'Amex',
            'hipercard' => 'Hipercard',
            'outros' => 'Outros',
        ];
        $valores = \App\Models\TaxaAdquirente::where('ativo', true)
            ->where(function ($q) {
                $q->whereNull('data_inicio')->orWhere('data_inicio', '<=', now());
            })
            ->where(function ($q) {
                $q->whereNull('data_fim')->orWhere('data_fim', '>=', now());
            })
            ->distinct()
            ->orderBy('bandeira')
            ->pluck('bandeira')
            ->values()
            ->all();
        $lista = [];
        foreach ($valores as $value) {
            $value = strtolower((string) $value);
            if (isset($bandeirasValidas[$value])) {
                $lista[] = ['value' => $value, 'label' => $bandeirasValidas[$value]];
            } else {
                $lista[] = ['value' => $value, 'label' => ucfirst($value)];
            }
        }

        return $lista;
    }
}

if (!function_exists('format_quantidade_br')) {
    /**
     * Formata quantidade para exibição no padrão brasileiro (vírgula decimal, ponto milhar),
     * sem zeros decimais desnecessários. Ex: 10 → "10", 10,5 → "10,5", 1000 → "1.000".
     */
    function format_quantidade_br($value): string
    {
        if ($value === null || $value === '') {
            return '—';
        }
        if (!is_numeric($value)) {
            return (string) $value;
        }
        $f = (float) $value;
        if (abs($f - round($f)) < 0.0000001) {
            return number_format((int) round($f), 0, ',', '.');
        }
        $s = rtrim(rtrim(number_format($f, 6, ',', '.'), '0'), ',');

        return $s === '' ? '0' : $s;
    }
}

if (!function_exists('format_quantity')) {
    /**
     * Formata quantidade para exibição, removendo zeros decimais desnecessários.
     * Ex: 16.000000 -> "16", 16.5 -> "16.5", 16.500000 -> "16.5"
     */
    function format_quantity($value): string
    {
        if ($value === null || $value === '') {
            return '';
        }
        if (!is_numeric($value)) {
            return (string) $value;
        }
        $f = (float) $value;
        if ($f == (int) $f) {
            return (string) (int) $f;
        }
        $s = sprintf('%.6f', $f);
        $s = rtrim($s, '0');
        $s = rtrim($s, '.');

        return $s === '' ? '0' : $s;
    }
}

if (!function_exists('format_os_display')) {
    /**
     * Formata o número da OS para exibição elegante, sem zeros à esquerda.
     * Ex: "O.S nº 17" em vez de "OS-000017"
     */
    function format_os_display($ordem): string
    {
        if (!$ordem) {
            return '-';
        }
        $rawSeq = $ordem->numero_sequencial ?? null;
        $numero = ($rawSeq !== null && $rawSeq !== '') ? $rawSeq : null;
        if ($numero === null && !empty($ordem->codigo_interno) && preg_match('/(\d+)/', $ordem->codigo_interno, $m)) {
            $numero = (int) $m[1];
        }
        $numero = $numero ?? $ordem->id ?? '-';

        return 'O.S nº ' . $numero;
    }
}

if (!function_exists('format_status_os_display')) {
    /**
     * Exibe o status da OS para o usuário. Normaliza "Encerrado" para "Encerrada".
     */
    function format_status_os_display(?string $status): string
    {
        if ($status === null || $status === '') {
            return '-';
        }
        return in_array($status, ['Encerrado', 'Encerrada'], true) ? 'Encerrada' : $status;
    }
}

if (!function_exists('erp_mail_from')) {
    /**
     * Retorna o remetente padrão dos e-mails: dados do emitente (Cadastro da Empresa).
     * Se não houver empresa com e-mail configurado, usa config mail.from e app.name.
     *
     * @return array{address: string, name: string}
     */
    function erp_mail_from(): array
    {
        $empresa = \App\Models\Empresa::first();
        if ($empresa && !empty(trim((string) $empresa->email))) {
            return [
                'address' => trim($empresa->email),
                'name' => trim((string) ($empresa->nome ?? $empresa->razao_social ?? config('app.name'))),
            ];
        }
        return [
            'address' => config('mail.from.address', 'noreply@example.com'),
            'name' => config('mail.from.name', config('app.name', 'ERP')),
        ];
    }
}
