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

use App\Models\Cliente;
use App\Models\Empresa;
use App\Models\Endereco;
use App\Models\PropostaComercial;
use Illuminate\Support\Facades\Storage;

/**
 * Variáveis ${...} partilhadas entre modelos Word (.docx) e HTML (.html).
 *
 * - {@see empresa_logo}: no HTML, data URI (`data:image/...;base64,...`) ou vazio. No Word, usar marcador de imagem `${empresa_logo}` e tratar em {@see PropostaComercialDocxService}.
 *
 * Filtro: {@see apply_filters('proposta_comercial.docx.variaveis', ...)} (nome histórico; aplica-se também ao HTML).
 */
class PropostaComercialModeloVariaveisService
{
    /**
     * @return array<string, string>
     */
    public function variaveisEscalaresParaTemplate(PropostaComercial $proposta): array
    {
        $proposta->loadMissing(['cliente.enderecos', 'vendedor', 'tabelaPreco', 'itens']);

        $c = $proposta->cliente;
        $empresa = class_exists(Empresa::class) ? Empresa::query()->first() : null;

        $docCliente = '';
        if ($c) {
            $docCliente = trim((string) ($c->cnpj ?: $c->cpf ?: $c->documento_estrangeiro ?: ''));
        }

        $razao = $c?->razao_social ? trim((string) $c->razao_social) : '';
        $nomeCliente = $c?->nome ? trim((string) $c->nome) : '';

        $tipoPessoa = $c && in_array($c->tipo_pessoa, ['F', 'J', 'E'], true) ? (string) $c->tipo_pessoa : '';
        $tipoLabel = match ($tipoPessoa) {
            'F' => 'Pessoa física',
            'J' => 'Pessoa jurídica',
            'E' => 'Estrangeira',
            default => '',
        };
        $classeBodyTipo = match ($tipoPessoa) {
            'F', 'J', 'E' => 'tipo-cliente-'.$tipoPessoa,
            default => 'tipo-cliente-nenhum',
        };

        $cpfFmt = ($c && $tipoPessoa === 'F') ? $this->formatarCpfParaExibicao($c->cpf) : '';
        $cnpjFmt = ($c && $tipoPessoa === 'J') ? $this->formatarCnpjParaExibicao($c->cnpj) : '';
        $docEstr = ($c && $tipoPessoa === 'E') ? trim((string) ($c->documento_estrangeiro ?? '')) : '';
        $rg = ($c && $tipoPessoa === 'F') ? trim((string) ($c->rg ?? '')) : '';
        $dataNasc = ($c && $tipoPessoa === 'F' && $c->data_nascimento) ? $c->data_nascimento->format('d/m/Y') : '';
        $ie = ($c && $tipoPessoa === 'J') ? trim((string) ($c->inscricao_estadual ?? '')) : '';
        $ieUf = ($c && $tipoPessoa === 'J') ? trim((string) ($c->uf_ie ?? '')) : '';
        $im = ($c && $tipoPessoa === 'J') ? trim((string) ($c->inscricao_municipal ?? '')) : '';
        $pais = $c ? trim((string) ($c->pais_origem ?? '')) : '';
        $docRotulo = match ($tipoPessoa) {
            'F' => 'CPF',
            'J' => 'CNPJ',
            'E' => 'Documento',
            default => 'Documento',
        };

        $descPlain = $this->textoPlanoParaDocx($proposta->descricao);
        $obs = $this->textoPlanoParaDocx($proposta->observacoes);
        $obsInt = $this->textoPlanoParaDocx($proposta->observacoes_internas);

        $linhaEndCliente = $this->linhaEnderecoCliente($c);

        $vars = [
            'proposta_id' => (string) $proposta->id,
            'proposta_versao' => (string) $proposta->versao,
            'proposta_titulo' => $proposta->titulo ? (string) $proposta->titulo : '',
            'proposta_descricao' => $descPlain,
            'proposta_status' => (string) $proposta->status,
            'proposta_status_label' => $proposta->status_label,
            'proposta_referencia' => $proposta->referenciaGrupo(),
            'proposta_numero_sequencial' => $proposta->numero_sequencial ? (string) $proposta->numero_sequencial : '',
            'data_emissao' => $proposta->data_emissao?->format('d/m/Y') ?? '',
            'validade_em' => $proposta->validade_em?->format('d/m/Y') ?? '',
            'validade_dias' => $proposta->validade_dias !== null ? (string) $proposta->validade_dias : '',
            'cliente_nome' => $nomeCliente,
            'cliente_razao_social' => $razao,
            'cliente_documento' => $docCliente,
            'cliente_classe_tipo_body' => $classeBodyTipo,
            'cliente_tipo_pessoa' => $tipoPessoa,
            'cliente_tipo_pessoa_label' => $tipoLabel,
            'cliente_documento_rotulo' => $docRotulo,
            'cliente_cpf' => $cpfFmt,
            'cliente_cnpj' => $cnpjFmt,
            'cliente_rg' => $rg,
            'cliente_data_nascimento' => $dataNasc,
            'cliente_inscricao_estadual' => $ie,
            'cliente_ie_uf' => $ieUf,
            'cliente_inscricao_municipal' => $im,
            'cliente_documento_estrangeiro' => $docEstr,
            'cliente_pais_origem' => $pais,
            'cliente_endereco' => $linhaEndCliente === '' ? '' : 'Endereço: '.$linhaEndCliente,
            'vendedor_nome' => $proposta->vendedor?->name ?? '',
            'tabela_preco_nome' => $proposta->tabelaPreco?->nome ?? '',
            'total_produtos' => $this->moedaBr($proposta->total_produtos),
            'total_servicos' => $this->moedaBr($proposta->total_servicos),
            'total_descontos' => $this->moedaBr($proposta->total_descontos),
            'total_geral' => $this->moedaBr($proposta->total_geral),
            'observacoes' => $obs,
            'observacoes_internas' => $obsInt,
            'empresa_nome' => $empresa?->nome ? (string) $empresa->nome : (string) config('app.name', 'ERP'),
            'empresa_razao_social' => $empresa?->razao_social ? trim((string) $empresa->razao_social) : '',
            'empresa_cnpj' => $empresa?->cnpj ? (string) $empresa->cnpj : '',
            'empresa_endereco' => $this->linhaEnderecoEmpresa($empresa),
            'empresa_telefone' => $empresa?->telefone ? trim((string) $empresa->telefone) : '',
            'empresa_celular' => $empresa?->celular ? trim((string) $empresa->celular) : '',
            'empresa_email' => $empresa?->email ? trim((string) $empresa->email) : '',
            'empresa_logo' => '',
            'gerado_em' => now()->format('d/m/Y H:i'),
        ];

        if (function_exists('apply_filters')) {
            $filtered = apply_filters('proposta_comercial.docx.variaveis', $vars, $proposta);
            if (is_array($filtered)) {
                $vars = $filtered;
            }
        }

        foreach ($vars as $chave => $valor) {
            if (is_string($valor)) {
                $vars[$chave] = $this->sanitizarControlesInvalidosXml($valor);
            }
        }

        return $vars;
    }

    /**
     * Caminho absoluto do ficheiro de logótipo: primeiro cadastro da empresa (storage public),
     * depois logo do sistema em {@see config('app.logo_path')} (relativo a public/). Null se não existir.
     */
    public function caminhoAbsolutoLogoParaTemplates(): ?string
    {
        $empresa = class_exists(Empresa::class) ? Empresa::query()->first() : null;
        if ($empresa?->logo_path) {
            $rel = ltrim(str_replace('\\', '/', (string) $empresa->logo_path), '/');
            if ($rel !== '') {
                $full = storage_path('app/public/'.$rel);
                if (is_readable($full)) {
                    return $full;
                }
            }
        }

        $systemRel = trim(str_replace('\\', '/', (string) config('app.logo_path', '')), '/');
        if ($systemRel !== '') {
            $full = public_path($systemRel);
            if (is_readable($full)) {
                return $full;
            }
        }

        return null;
    }

    /**
     * Data URI para usar em atributo src em modelos HTML; string vazia se não houver ficheiro.
     */
    public function empresaLogoDataUriParaModeloHtml(): string
    {
        $path = $this->caminhoAbsolutoLogoParaTemplates();
        if ($path === null) {
            return '';
        }

        $mime = $this->mimeTypeImagemParaPath($path);
        $data = @file_get_contents($path);
        if ($data === false) {
            return '';
        }

        return 'data:'.$mime.';base64,'.base64_encode($data);
    }

    /**
     * URL pública do logótipo (ex.: /storage/empresa/logos/… ou /logo/…), para usar em src quando o data URI falhar ou for indesejado.
     * Usa o mesmo critério de ficheiro que {@see caminhoAbsolutoLogoParaTemplates()}.
     */
    public function empresaLogoPublicUrlParaModeloHtml(): string
    {
        $empresa = class_exists(Empresa::class) ? Empresa::query()->first() : null;
        if ($empresa?->logo_path) {
            $rel = ltrim(str_replace('\\', '/', (string) $empresa->logo_path), '/');
            if ($rel !== '' && Storage::disk('public')->exists($rel)) {
                return Storage::disk('public')->url($rel);
            }
        }

        $systemRel = trim(str_replace('\\', '/', (string) config('app.logo_path', '')), '/');
        if ($systemRel !== '' && is_readable(public_path($systemRel))) {
            return asset($systemRel);
        }

        return '';
    }

    private function mimeTypeImagemParaPath(string $path): string
    {
        $mime = @mime_content_type($path);
        if (is_string($mime) && str_starts_with($mime, 'image/')) {
            return $mime;
        }

        if (function_exists('finfo_open')) {
            $f = @finfo_open(FILEINFO_MIME_TYPE);
            if ($f !== false) {
                $detected = @finfo_file($f, $path);
                finfo_close($f);
                if (is_string($detected) && str_starts_with($detected, 'image/')) {
                    return $detected;
                }
            }
        }

        $ext = strtolower(pathinfo($path, PATHINFO_EXTENSION));
        $map = [
            'jpg' => 'image/jpeg',
            'jpeg' => 'image/jpeg',
            'png' => 'image/png',
            'gif' => 'image/gif',
            'webp' => 'image/webp',
            'svg' => 'image/svg+xml',
            'bmp' => 'image/bmp',
            'ico' => 'image/x-icon',
        ];

        return $map[$ext] ?? 'image/png';
    }

    /**
     * @return list<array<string, string>>
     */
    public function linhasItens(PropostaComercial $proposta): array
    {
        $proposta->loadMissing(['itens']);

        $linhas = [];
        foreach ($proposta->itens as $idx => $it) {
            $q = is_numeric($it->quantidade) ? (float) $it->quantidade : 0.0;
            $pu = is_numeric($it->preco_unitario) ? (float) $it->preco_unitario : 0.0;
            $subLinha = $q * $pu;
            $descVal = is_numeric($it->desconto) ? (float) $it->desconto : 0.0;
            $pctDesc = $subLinha > 1e-9 ? ($descVal / $subLinha) * 100.0 : 0.0;

            $linhas[] = [
                'item_linha' => (string) ($idx + 1),
                'item_tipo' => $it->tipo === 'servico' ? 'Serviço' : 'Produto',
                'item_descricao' => $this->textoSimplesParaDocx((string) $it->descricao),
                'item_quantidade' => function_exists('format_quantidade_br') ? format_quantidade_br($it->quantidade) : (string) $it->quantidade,
                'item_preco_unitario' => 'R$ '.$this->moedaBr($it->preco_unitario),
                'item_desconto' => ((float) $it->desconto) > 0 ? '- R$ '.$this->moedaBr($it->desconto) : '—',
                'item_desconto_percentual' => number_format($pctDesc, 2, ',', '.').' %',
                'item_total' => 'R$ '.$this->moedaBr($it->total),
            ];
        }

        if ($linhas === []) {
            $linhas[] = [
                'item_linha' => '—',
                'item_tipo' => '',
                'item_descricao' => 'Nenhum item cadastrado nesta proposta.',
                'item_quantidade' => '',
                'item_preco_unitario' => '',
                'item_desconto' => '',
                'item_desconto_percentual' => '',
                'item_total' => '',
            ];
        }

        foreach ($linhas as &$linha) {
            foreach ($linha as $k => $v) {
                $linha[$k] = $this->sanitizarControlesInvalidosXml((string) $v);
            }
        }
        unset($linha);

        return $linhas;
    }

    /**
     * HTML da proposta → texto para .docx (strip_tags + entidades + remove controlos ilegais em XML).
     */
    private function textoPlanoParaDocx(?string $html): string
    {
        if ($html === null || trim($html) === '') {
            return '';
        }
        $plain = trim(strip_tags($html));
        $plain = html_entity_decode($plain, ENT_QUOTES | ENT_HTML5, 'UTF-8');

        return $this->sanitizarControlesInvalidosXml($plain);
    }

    /**
     * Campo já em texto (ex.: descrição de linha) — decodifica entidades para não duplicar &amp; com o escape do PhpWord.
     */
    private function textoSimplesParaDocx(string $texto): string
    {
        $texto = html_entity_decode(trim($texto), ENT_QUOTES | ENT_HTML5, 'UTF-8');

        return $this->sanitizarControlesInvalidosXml($texto);
    }

    /**
     * XML 1.0 não permite a maioria dos caracteres U+0000–U+001F (exceto tab, LF, CR).
     */
    private function sanitizarControlesInvalidosXml(string $s): string
    {
        $out = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/u', '', $s);

        return is_string($out) ? $out : $s;
    }

    private function linhaEnderecoEmpresa(?Empresa $empresa): string
    {
        if ($empresa === null || (! $empresa->logradouro && ! $empresa->cidade)) {
            return '';
        }

        return trim(
            ($empresa->logradouro ? $empresa->logradouro.' ' : '')
            .($empresa->numero ?? '')
            .($empresa->complemento ? ' - '.$empresa->complemento : '')
            .($empresa->bairro ? ', '.$empresa->bairro : '')
            .($empresa->cidade ? ' - '.$empresa->cidade.'/'.($empresa->estado ?? '') : '')
        );
    }

    /**
     * Endereço do cliente para modelos (principal primeiro; senão o primeiro cadastrado).
     */
    private function linhaEnderecoCliente(?Cliente $cliente): string
    {
        if ($cliente === null) {
            return '';
        }

        if (! $cliente->relationLoaded('enderecos')) {
            $cliente->load('enderecos');
        }

        if ($cliente->enderecos->isEmpty()) {
            return '';
        }

        $e = $cliente->enderecos->sortByDesc(fn (Endereco $x): int => $x->principal ? 1 : 0)->first();
        if ($e === null) {
            return '';
        }

        $cepDigits = preg_replace('/\D/', '', (string) ($e->cep ?? '')) ?? '';
        $cepFmt = strlen($cepDigits) === 8
            ? substr($cepDigits, 0, 5).'-'.substr($cepDigits, 5, 3)
            : trim((string) ($e->cep ?? ''));

        $linha = trim(
            (($e->logradouro ?? '') !== '' ? $e->logradouro.' ' : '')
            .($e->numero ?? '')
            .(($e->complemento ?? '') !== '' ? ' - '.$e->complemento : '')
            .(($e->bairro ?? '') !== '' ? ', '.$e->bairro : '')
            .(($e->cidade ?? '') !== '' ? ' - '.$e->cidade.'/'.($e->estado ?? '') : '')
        );

        if ($cepFmt !== '') {
            $linha .= ($linha !== '' ? ' · ' : '').'CEP '.$cepFmt;
        }

        $pais = trim((string) ($e->pais ?? ''));
        if ($pais !== '' && ! in_array(strtoupper($pais), ['BR', 'BRASIL', 'BRAZIL'], true)) {
            $linha .= ($linha !== '' ? ' · ' : '').$pais;
        }

        return $linha;
    }

    private function moedaBr(mixed $valor): string
    {
        $n = is_numeric($valor) ? (float) $valor : 0.0;

        return number_format($n, 2, ',', '.');
    }

    private function formatarCpfParaExibicao(mixed $cpf): string
    {
        $d = preg_replace('/\D/', '', (string) $cpf) ?? '';

        return strlen($d) === 11
            ? substr($d, 0, 3).'.'.substr($d, 3, 3).'.'.substr($d, 6, 3).'-'.substr($d, 9, 2)
            : trim((string) $cpf);
    }

    private function formatarCnpjParaExibicao(mixed $cnpj): string
    {
        $d = preg_replace('/\D/', '', (string) $cnpj) ?? '';

        return strlen($d) === 14
            ? substr($d, 0, 2).'.'.substr($d, 2, 3).'.'.substr($d, 5, 3).'/'.substr($d, 8, 4).'-'.substr($d, 12, 2)
            : trim((string) $cnpj);
    }
}
