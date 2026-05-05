<?php

/**
 * Parser de NF-e (XML autorizado SEFAZ — nfeProc ou NFe).
 *
 * @copyright 2024-2026 LivreOS
 * @license   https://www.gnu.org/licenses/agpl-3.0.txt AGPL-3.0
 */
namespace Compras\Services;

use SimpleXMLElement;

class NfeXmlParserService
{
    private const NS = 'http://www.portalfiscal.inf.br/nfe';

    /**
     * @return array{
     *   ok: bool,
     *   erro?: string,
     *   chave_acesso?: string,
     *   numero?: string,
     *   serie?: string,
     *   data_emissao?: string,
     *   emitente?: array,
     *   destinatario?: array,
     *   totais?: array,
     *   itens?: list<array>,
     *   duplicatas?: list<array{nDup?:string,dVenc:string,vDup:float}>,
     *   warnings?: list<string>
     * }
     */
    public function parse(string $xmlContent): array
    {
        $warnings = [];
        libxml_use_internal_errors(true);
        $xml = @simplexml_load_string($xmlContent);
        if ($xml === false) {
            return ['ok' => false, 'erro' => 'XML inválido ou malformado.'];
        }

        $nfe = null;
        if ($xml->getName() === 'nfeProc' && isset($xml->NFe)) {
            $nfe = $xml->NFe;
        } elseif ($xml->getName() === 'NFe') {
            $nfe = $xml;
        }
        if ($nfe === null) {
            return ['ok' => false, 'erro' => 'Estrutura não reconhecida (esperado nfeProc ou NFe).'];
        }

        $inf = $this->obterInfNfe($nfe);
        if ($inf === null) {
            return ['ok' => false, 'erro' => 'Elemento infNFe não encontrado.'];
        }

        $attrs = $inf->attributes();
        $id = (string) ($attrs['Id'] ?? '');
        $chave = $id !== '' && str_starts_with($id, 'NFe') ? substr($id, 3) : '';
        if ($chave === '') {
            $warnings[] = 'Atributo Id da infNFe ausente; chave extraída do ide se possível.';
        }

        $inf->registerXPathNamespace('n', self::NS);
        $ide = $inf->xpath('n:ide');
        $ide = $ide[0] ?? $this->child($inf, 'ide');
        $emit = $inf->xpath('n:emit');
        $emit = $emit[0] ?? $this->child($inf, 'emit');
        $dest = $inf->xpath('n:dest');
        $dest = $dest[0] ?? $this->child($inf, 'dest');

        if ($ide === null) {
            return ['ok' => false, 'erro' => 'Grupo ide não encontrado.'];
        }

        $cNF = (string) ($this->child($ide, 'cNF') ?? '');
        $nNF = (string) ($this->child($ide, 'nNF') ?? '');
        $serie = (string) ($this->child($ide, 'serie') ?? '');
        $dhEmi = (string) ($this->child($ide, 'dhEmi') ?? $this->child($ide, 'dEmi') ?? '');
        $dataEmissao = $this->normalizarData($dhEmi);

        if ($chave === '' && $cNF !== '') {
            $warnings[] = 'Chave incompleta no XML; valide manualmente antes de confirmar.';
        }

        $emitente = $this->montarEmitente($emit);
        $destinatario = $this->montarDestinatario($dest);

        $totalNode = $inf->xpath('n:total/n:ICMSTot');
        $totalNode = $totalNode[0] ?? $this->deepChild($inf, ['total', 'ICMSTot']);
        $totais = $this->montarTotais($totalNode);

        $itens = [];
        $dets = $inf->xpath('n:det');
        if (count($dets) === 0) {
            foreach ($inf->children(self::NS) as $el) {
                if ($el->getName() === 'det') {
                    $dets[] = $el;
                }
            }
        }
        $idx = 0;
        foreach ($dets as $det) {
            $idx++;
            $prod = $det->xpath('n:prod');
            $prod = $prod[0] ?? $this->child($det, 'prod');
            if ($prod === null) {
                $warnings[] = "Item {$idx} sem grupo prod.";
                continue;
            }
            $imposto = $det->xpath('n:imposto');
            $imposto = $imposto[0] ?? $this->child($det, 'imposto');
            $eanCom = $this->normalizarGtin((string) ($this->child($prod, 'cEAN') ?? ''));
            $eanTrib = $this->normalizarGtin((string) ($this->child($prod, 'cEANTrib') ?? ''));
            $cestRaw = (string) ($this->child($prod, 'CEST') ?? '');
            $cestDigits = preg_replace('/\D/', '', $cestRaw);
            $cest = strlen($cestDigits) >= 7 ? substr($cestDigits, 0, 7) : null;
            $uCom = trim((string) ($this->child($prod, 'uCom') ?? ''));
            $extIpi = trim((string) ($this->child($prod, 'EXTIPI') ?? ''));
            $pesoL = $this->floatNode($prod, 'pesoL');
            $pesoB = $this->floatNode($prod, 'pesoB');
            $itens[] = [
                'indice' => $idx,
                'c_prod' => (string) ($this->child($prod, 'cProd') ?? ''),
                'x_prod' => (string) ($this->child($prod, 'xProd') ?? ''),
                'ncm' => (string) ($this->child($prod, 'NCM') ?? ''),
                'cfop' => (string) ($this->child($prod, 'CFOP') ?? ''),
                'ean' => $eanCom ?? '',
                'ean_tributario' => $eanTrib,
                'u_com' => $uCom !== '' ? $uCom : null,
                'cest' => $cest,
                'ext_ipi' => $extIpi !== '' ? substr($extIpi, 0, 10) : null,
                'peso_liquido' => $pesoL > 0 ? $pesoL : null,
                'peso_bruto' => $pesoB > 0 ? $pesoB : null,
                'q_com' => (float) str_replace(',', '.', (string) ($this->child($prod, 'qCom') ?? '0')),
                'v_un_com' => (float) str_replace(',', '.', (string) ($this->child($prod, 'vUnCom') ?? '0')),
                'v_prod' => (float) str_replace(',', '.', (string) ($this->child($prod, 'vProd') ?? '0')),
                'cst_icms' => $this->extrairCstIcms($imposto),
                'orig_icms' => $this->extrairOrigemIcms($imposto),
                'impostos' => $this->extrairImpostosResumo($imposto),
            ];
        }

        $duplicatas = [];
        $cobr = $inf->xpath('n:cobr');
        $cobr = $cobr[0] ?? $this->child($inf, 'cobr');
        if ($cobr !== null) {
            $dupNodes = $cobr->xpath('n:dup');
            if (count($dupNodes) === 0) {
                foreach ($cobr->children(self::NS) as $el) {
                    if ($el->getName() === 'dup') {
                        $dupNodes[] = $el;
                    }
                }
            }
            foreach ($dupNodes as $dup) {
                $dVenc = (string) ($this->child($dup, 'dVenc') ?? '');
                $vDup = (float) str_replace(',', '.', (string) ($this->child($dup, 'vDup') ?? '0'));
                if ($dVenc !== '' && $vDup > 0) {
                    $duplicatas[] = [
                        'nDup' => (string) ($this->child($dup, 'nDup') ?? ''),
                        'dVenc' => $dVenc,
                        'vDup' => $vDup,
                    ];
                }
            }
        }

        if ($chave !== '') {
            $val = NfeChaveValidator::validar($chave);
            if (! $val['ok']) {
                $warnings[] = 'Chave de acesso: ' . ($val['motivo'] ?? 'inválida');
            }
        }

        return [
            'ok' => true,
            'chave_acesso' => $chave ?: null,
            'numero' => $nNF,
            'serie' => $serie,
            'data_emissao' => $dataEmissao,
            'emitente' => $emitente,
            'destinatario' => $destinatario,
            'totais' => $totais,
            'itens' => $itens,
            'duplicatas' => $duplicatas,
            'warnings' => $warnings,
        ];
    }

    private function obterInfNfe(SimpleXMLElement $nfe): ?SimpleXMLElement
    {
        $nfe->registerXPathNamespace('n', self::NS);
        $found = $nfe->xpath('n:infNFe');
        if ($found && count($found) > 0) {
            return $found[0];
        }
        foreach ($nfe->children(self::NS) as $child) {
            if ($child->getName() === 'infNFe') {
                return $child;
            }
        }

        return null;
    }

    private function child(?SimpleXMLElement $el, string $name): ?SimpleXMLElement
    {
        if ($el === null) {
            return null;
        }
        $c = $el->children(self::NS)->{$name};
        if ($c !== null && count($c) > 0) {
            return $c[0];
        }
        $c = $el->{$name};

        return ($c !== null && count($c) > 0) ? $c[0] : null;
    }

    private function deepChild(SimpleXMLElement $inf, array $path): ?SimpleXMLElement
    {
        $cur = $inf;
        foreach ($path as $p) {
            $cur = $this->child($cur, $p);
            if ($cur === null) {
                return null;
            }
        }

        return $cur;
    }

    private function montarEmitente(?SimpleXMLElement $emit): array
    {
        if ($emit === null) {
            return [];
        }

        $ender = $this->child($emit, 'enderEmit');
        $endereco = [];
        if ($ender !== null) {
            $cep = preg_replace('/\D/', '', (string) ($this->child($ender, 'CEP') ?? ''));
            $endereco = [
                'cep' => $cep,
                'logradouro' => (string) ($this->child($ender, 'xLgr') ?? ''),
                'numero' => (string) ($this->child($ender, 'nro') ?? ''),
                'complemento' => (string) ($this->child($ender, 'xCpl') ?? ''),
                'bairro' => (string) ($this->child($ender, 'xBairro') ?? ''),
                'cidade' => (string) ($this->child($ender, 'xMun') ?? ''),
                'estado' => strtoupper(substr((string) ($this->child($ender, 'UF') ?? ''), 0, 2)),
                'pais' => (string) ($this->child($ender, 'xPais') ?? 'Brasil'),
            ];
        }

        // Layout NF-e: <fone> costuma ficar em <emit>; em notas de marketplaces (ex.: Mercado Livre) frequentemente vem só em <enderEmit>.
        $foneRaw = trim((string) ($this->child($emit, 'fone') ?? ''));
        if ($foneRaw === '' && $ender !== null) {
            $foneRaw = trim((string) ($this->child($ender, 'fone') ?? ''));
        }
        $emailRaw = trim((string) ($this->child($emit, 'email') ?? ''));
        if ($emailRaw === '' && $ender !== null) {
            $emailRaw = trim((string) ($this->child($ender, 'email') ?? ''));
        }

        return [
            'cnpj' => preg_replace('/\D/', '', (string) ($this->child($emit, 'CNPJ') ?? $this->child($emit, 'CPF') ?? '')),
            'xnome' => (string) ($this->child($emit, 'xNome') ?? ''),
            'xfant' => (string) ($this->child($emit, 'xFant') ?? ''),
            'ie' => (string) ($this->child($emit, 'IE') ?? ''),
            'fone' => preg_replace('/\D/', '', $foneRaw),
            'email' => $emailRaw,
            'endereco' => $endereco !== [] ? $endereco : null,
        ];
    }

    private function montarDestinatario(?SimpleXMLElement $dest): array
    {
        if ($dest === null) {
            return [];
        }
        $cnpj = preg_replace('/\D/', '', (string) ($this->child($dest, 'CNPJ') ?? ''));
        $cpf = preg_replace('/\D/', '', (string) ($this->child($dest, 'CPF') ?? ''));

        return [
            'cnpj' => $cnpj,
            'cpf' => $cpf,
            'xnome' => (string) ($this->child($dest, 'xNome') ?? ''),
        ];
    }

    private function montarTotais(?SimpleXMLElement $icmsTot): array
    {
        if ($icmsTot === null) {
            return [];
        }

        return [
            'vBC' => $this->floatNode($icmsTot, 'vBC'),
            'vICMS' => $this->floatNode($icmsTot, 'vICMS'),
            'vBCST' => $this->floatNode($icmsTot, 'vBCST'),
            'vST' => $this->floatNode($icmsTot, 'vST'),
            'vProd' => $this->floatNode($icmsTot, 'vProd'),
            'vFrete' => $this->floatNode($icmsTot, 'vFrete'),
            'vSeg' => $this->floatNode($icmsTot, 'vSeg'),
            'vDesc' => $this->floatNode($icmsTot, 'vDesc'),
            'vII' => $this->floatNode($icmsTot, 'vII'),
            'vIPI' => $this->floatNode($icmsTot, 'vIPI'),
            'vPIS' => $this->floatNode($icmsTot, 'vPIS'),
            'vCOFINS' => $this->floatNode($icmsTot, 'vCOFINS'),
            'vOutro' => $this->floatNode($icmsTot, 'vOutro'),
            'vNF' => $this->floatNode($icmsTot, 'vNF'),
        ];
    }

    private function floatNode(SimpleXMLElement $el, string $name): float
    {
        $n = $this->child($el, $name);
        if ($n === null) {
            return 0.0;
        }

        return (float) str_replace(',', '.', (string) $n);
    }

    private function extrairCstIcms(?SimpleXMLElement $imposto): ?string
    {
        if ($imposto === null) {
            return null;
        }
        foreach ($imposto->children(self::NS) as $node) {
            $name = $node->getName();
            if (str_starts_with($name, 'ICMS')) {
                foreach ($node->children(self::NS) as $icms) {
                    $cst = $icms->CST ?? $icms->CSOSN ?? null;
                    if ($cst !== null) {
                        return (string) $cst;
                    }
                }
            }
        }

        return null;
    }

    /**
     * Origem da mercadoria (0–8) no grupo ICMS da NF-e.
     */
    private function extrairOrigemIcms(?SimpleXMLElement $imposto): ?string
    {
        if ($imposto === null) {
            return null;
        }
        foreach ($imposto->children(self::NS) as $node) {
            if (! str_starts_with($node->getName(), 'ICMS')) {
                continue;
            }
            foreach ($node->children(self::NS) as $icms) {
                $orig = $icms->orig ?? null;
                if ($orig !== null) {
                    $v = (string) $orig;
                    if (preg_match('/^[0-8]$/', $v)) {
                        return $v;
                    }
                }
            }
        }

        return null;
    }

    /**
     * @return ?string GTIN 8–14 dígitos ou null (ignora SEM GTIN / inválido)
     */
    private function normalizarGtin(string $raw): ?string
    {
        $s = preg_replace('/\s+/', '', trim($raw));
        if ($s === '') {
            return null;
        }
        $upper = strtoupper($s);
        if (str_contains($upper, 'SEMGTIN') || str_contains($upper, 'SEM_GTIN')) {
            return null;
        }
        if (preg_match('/^\d{8,14}$/', $s)) {
            return $s;
        }

        return null;
    }

    private function extrairImpostosResumo(?SimpleXMLElement $imposto): array
    {
        if ($imposto === null) {
            return [];
        }
        $out = [];
        foreach ($imposto->children(self::NS) as $grp) {
            $tag = $grp->getName();
            if (in_array($tag, ['ICMS', 'IPI', 'II', 'PIS', 'COFINS', 'ICMSUFDest'], true)) {
                $out[$tag] = $this->xmlToArray($grp);
            }
        }

        return $out;
    }

    private function xmlToArray(SimpleXMLElement $el): array
    {
        $a = [];
        foreach ($el->children(self::NS) as $child) {
            $a[$child->getName()] = (string) $child;
        }

        return $a;
    }

    private function normalizarData(string $dh): ?string
    {
        $dh = trim($dh);
        if ($dh === '') {
            return null;
        }
        try {
            if (str_contains($dh, 'T')) {
                return \Carbon\Carbon::parse($dh)->toDateString();
            }

            return \Carbon\Carbon::createFromFormat('Y-m-d', substr($dh, 0, 10))->toDateString();
        } catch (\Throwable) {
            return null;
        }
    }
}
