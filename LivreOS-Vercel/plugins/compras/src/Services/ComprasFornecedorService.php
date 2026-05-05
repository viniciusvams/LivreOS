<?php

/**
 * Fornecedor = cliente PJ; criação enriquecida com dados da NF-e (prioridade) e APIs públicas de CNPJ
 * (mesmas bases do cadastro de cliente em resources/views/clientes/_form.blade.php).
 *
 * Filtro: `compras.fornecedor.dados_cnpj_api` — recebe (?array $api, string $cnpj14, array $emitente) e retorna array ou null.
 *
 * @copyright 2024-2026 LivreOS
 * @license   https://www.gnu.org/licenses/agpl-3.0.txt AGPL-3.0
 */
namespace Compras\Services;

use App\Models\Cliente;
use App\Models\Contato;
use App\Models\Endereco;
use App\Models\Socio;
use App\Services\CnpjConsultaPublicaService;
use Illuminate\Support\Facades\DB;

class ComprasFornecedorService
{
    public static function normalizarCnpj(?string $cnpj): string
    {
        return preg_replace('/\D/', '', (string) $cnpj);
    }

    public function buscarPorCnpj(string $cnpj): ?Cliente
    {
        $limpo = self::normalizarCnpj($cnpj);
        if (strlen($limpo) !== 14) {
            return null;
        }

        return Cliente::query()
            ->where('tipo_pessoa', 'J')
            ->whereRaw(
                "REPLACE(REPLACE(REPLACE(REPLACE(TRIM(COALESCE(cnpj,'')),' ',''),'.',''),'/',''),'-','') = ?",
                [$limpo]
            )
            ->first();
    }

    /**
     * @param  array<string, mixed>|null  $emitenteNfe Retorno de `emitente` do parser (xnome, xfant, endereco, fone, email, ie…)
     */
    public function criarFornecedor(string $cnpj, string $razaoSocial, ?string $nomeFantasia = null, ?array $emitenteNfe = null): Cliente
    {
        $limpo = self::normalizarCnpj($cnpj);
        $emit = is_array($emitenteNfe) ? $emitenteNfe : [];

        $api = null;
        if (strlen($limpo) === 14 && class_exists(CnpjConsultaPublicaService::class)) {
            $api = app(CnpjConsultaPublicaService::class)->consultar($limpo);
        }
        if (function_exists('apply_filters')) {
            $api = apply_filters('compras.fornecedor.dados_cnpj_api', $api, $limpo, $emit);
        }

        $razaoFinal = trim((string) (($emit['xnome'] ?? '') !== '' ? $emit['xnome'] : ($razaoSocial !== '' ? $razaoSocial : ($api['razao_social'] ?? ''))));
        if ($razaoFinal === '') {
            $razaoFinal = 'Fornecedor CNPJ '.$limpo;
        }

        $fantasiaFinal = trim((string) (($emit['xfant'] ?? '') !== '' ? $emit['xfant'] : ($nomeFantasia ?? ($api['nome_fantasia'] ?? ''))));
        $nomeExibicao = $fantasiaFinal !== '' ? $fantasiaFinal : $razaoFinal;

        $attrs = [
            'tipo_pessoa' => 'J',
            'pais_origem' => 'Brasil',
            'nome' => mb_substr($nomeExibicao, 0, 100),
            'razao_social' => mb_substr($razaoFinal, 0, 255),
            'cnpj' => $this->formatarCnpj($limpo),
            'observacoes' => 'Cadastro automático — plugin Compras (importação NF-e).',
        ];

        $ie = trim((string) ($emit['ie'] ?? ''));
        if ($ie !== '' && strcasecmp($ie, 'ISENTO') !== 0 && strcasecmp($ie, 'ISENTA') !== 0) {
            $attrs['inscricao_estadual'] = mb_substr($ie, 0, 30);
        }

        if (! empty($emit['endereco']['estado'])) {
            $attrs['uf_ie'] = strtoupper(substr((string) $emit['endereco']['estado'], 0, 2));
        } elseif (is_array($api) && ! empty($api['uf'])) {
            $attrs['uf_ie'] = strtoupper(substr((string) $api['uf'], 0, 2));
        }

        if (is_array($api)) {
            if (! empty($api['natureza_juridica'])) {
                $attrs['natureza_juridica'] = mb_substr((string) $api['natureza_juridica'], 0, 255);
            }
            if (! empty($api['porte_empresa'])) {
                $attrs['porte_empresa'] = mb_substr((string) $api['porte_empresa'], 0, 255);
            }
            if (! empty($api['capital_social'])) {
                $attrs['capital_social'] = mb_substr((string) $api['capital_social'], 0, 50);
            }
            if (! empty($api['data_inicio_atividade'])) {
                try {
                    $attrs['data_inicio_atividade'] = \Carbon\Carbon::parse($api['data_inicio_atividade'])->toDateString();
                } catch (\Throwable) {
                }
            }
            if (! empty($api['ramo_atividade'])) {
                $attrs['ramo_atividade'] = mb_substr((string) $api['ramo_atividade'], 0, 255);
            }
            $opt = $this->optanteSimplesParaBool($api['optante_simples'] ?? null);
            if ($opt !== null) {
                $attrs['optante_simples_nacional'] = $opt;
            }
        }

        return DB::transaction(function () use ($attrs, $emit, $api) {
            $cliente = Cliente::create($attrs);

            $endPayload = $this->resolverEnderecoPreferindoNfe($emit, $api);
            if ($endPayload !== null) {
                $endPayload['cliente_id'] = $cliente->id;
                $endPayload['principal'] = true;
                $endPayload['cobranca'] = false;
                $endPayload['entrega'] = false;
                if (empty($endPayload['pais'])) {
                    $endPayload['pais'] = 'Brasil';
                }
                Endereco::create($endPayload);
            }

            $this->criarContatosFornecedor($cliente, $emit, $api);

            if (is_array($api) && ! empty($api['socios']) && is_array($api['socios'])) {
                $n = 0;
                foreach ($api['socios'] as $s) {
                    if ($n >= 30) {
                        break;
                    }
                    if (! is_array($s)) {
                        continue;
                    }
                    $nomeSoc = trim((string) ($s['nome_completo'] ?? ''));
                    $cpfSoc = trim((string) ($s['cpf'] ?? ''));
                    if ($nomeSoc === '' && $cpfSoc === '') {
                        continue;
                    }
                    Socio::create([
                        'cliente_id' => $cliente->id,
                        'nome_completo' => mb_substr($nomeSoc !== '' ? $nomeSoc : 'Sócio', 0, 255),
                        'cpf' => $cpfSoc !== '' ? mb_substr($cpfSoc, 0, 20) : null,
                        'socio_administrador' => ($s['socio_administrador'] ?? null) === true,
                    ]);
                    $n++;
                }
            }

            return $cliente->fresh();
        });
    }

    /**
     * Preenche campos ainda vazios no cliente PJ usando NF-e (prioridade) e APIs públicas de CNPJ.
     * Usado na confirmação da importação quando o fornecedor já existia (upload já tinha vinculado por CNPJ).
     */
    public function enriquecerFornecedorComDadosNfe(Cliente $cliente, array $emit, string $cnpj): void
    {
        if ($cliente->tipo_pessoa !== 'J') {
            return;
        }
        $limpo = self::normalizarCnpj($cnpj);
        if (strlen($limpo) !== 14) {
            return;
        }

        $api = null;
        if (class_exists(CnpjConsultaPublicaService::class)) {
            $api = app(CnpjConsultaPublicaService::class)->consultar($limpo);
        }
        if (function_exists('apply_filters')) {
            $api = apply_filters('compras.fornecedor.dados_cnpj_api', $api, $limpo, $emit);
        }

        DB::transaction(function () use ($cliente, $emit, $api, $limpo) {
            $cliente->refresh();

            $razao = trim((string) (($emit['xnome'] ?? '') !== '' ? $emit['xnome'] : ($api['razao_social'] ?? '')));
            $fant = trim((string) ($emit['xfant'] ?? ($api['nome_fantasia'] ?? '')));
            $nomeAlvo = $fant !== '' ? $fant : $razao;

            $updates = [];
            if ($razao !== '' && $this->strClienteVazio($cliente->razao_social)) {
                $updates['razao_social'] = mb_substr($razao, 0, 255);
            }
            if ($nomeAlvo !== '' && $this->strClienteVazio($cliente->nome)) {
                $updates['nome'] = mb_substr($nomeAlvo, 0, 100);
            }
            if ($this->strClienteVazio($cliente->cnpj)) {
                $updates['cnpj'] = $this->formatarCnpj($limpo);
            }

            $ie = trim((string) ($emit['ie'] ?? ''));
            if ($ie !== '' && strcasecmp($ie, 'ISENTO') !== 0 && strcasecmp($ie, 'ISENTA') !== 0 && $this->strClienteVazio($cliente->inscricao_estadual)) {
                $updates['inscricao_estadual'] = mb_substr($ie, 0, 30);
            }

            if (! empty($emit['endereco']['estado']) && $this->strClienteVazio($cliente->uf_ie)) {
                $updates['uf_ie'] = strtoupper(substr((string) $emit['endereco']['estado'], 0, 2));
            } elseif (is_array($api) && ! empty($api['uf']) && $this->strClienteVazio($cliente->uf_ie)) {
                $updates['uf_ie'] = strtoupper(substr((string) $api['uf'], 0, 2));
            }

            if (is_array($api)) {
                if (! empty($api['natureza_juridica']) && $this->strClienteVazio($cliente->natureza_juridica)) {
                    $updates['natureza_juridica'] = mb_substr((string) $api['natureza_juridica'], 0, 255);
                }
                if (! empty($api['porte_empresa']) && $this->strClienteVazio($cliente->porte_empresa)) {
                    $updates['porte_empresa'] = mb_substr((string) $api['porte_empresa'], 0, 100);
                }
                if (! empty($api['capital_social']) && $this->strClienteVazio($cliente->capital_social)) {
                    $updates['capital_social'] = mb_substr((string) $api['capital_social'], 0, 50);
                }
                if (! empty($api['ramo_atividade']) && $this->strClienteVazio($cliente->ramo_atividade)) {
                    $updates['ramo_atividade'] = mb_substr((string) $api['ramo_atividade'], 0, 255);
                }
                if (! empty($api['data_inicio_atividade']) && $cliente->data_inicio_atividade === null) {
                    try {
                        $updates['data_inicio_atividade'] = \Carbon\Carbon::parse($api['data_inicio_atividade'])->toDateString();
                    } catch (\Throwable) {
                    }
                }
            }

            if ($updates !== []) {
                $cliente->update($updates);
            }

            $cliente->refresh();

            if (! $cliente->enderecos()->where('principal', true)->exists()) {
                $endPayload = $this->resolverEnderecoPreferindoNfe($emit, $api);
                if ($endPayload !== null) {
                    $endPayload['cliente_id'] = $cliente->id;
                    $endPayload['principal'] = true;
                    $endPayload['cobranca'] = false;
                    $endPayload['entrega'] = false;
                    if (empty($endPayload['pais'])) {
                        $endPayload['pais'] = 'Brasil';
                    }
                    Endereco::create($endPayload);
                }
            }

            $cliente->refresh();

            if (! $cliente->contatos()->where('is_fornecedor', true)->exists()) {
                $this->criarContatosFornecedor($cliente, $emit, $api);
            }

            if (is_array($api) && ! empty($api['socios']) && is_array($api['socios']) && $cliente->socios()->count() === 0) {
                $n = 0;
                foreach ($api['socios'] as $s) {
                    if ($n >= 30) {
                        break;
                    }
                    if (! is_array($s)) {
                        continue;
                    }
                    $nomeSoc = trim((string) ($s['nome_completo'] ?? ''));
                    $cpfSoc = trim((string) ($s['cpf'] ?? ''));
                    if ($nomeSoc === '' && $cpfSoc === '') {
                        continue;
                    }
                    Socio::create([
                        'cliente_id' => $cliente->id,
                        'nome_completo' => mb_substr($nomeSoc !== '' ? $nomeSoc : 'Sócio', 0, 255),
                        'cpf' => $cpfSoc !== '' ? mb_substr($cpfSoc, 0, 20) : null,
                        'socio_administrador' => ($s['socio_administrador'] ?? null) === true,
                    ]);
                    $n++;
                }
            }
        });
    }

    private function strClienteVazio(mixed $v): bool
    {
        if ($v === null) {
            return true;
        }

        return trim((string) $v) === '';
    }

    private function optanteSimplesParaBool(mixed $v): ?bool
    {
        if ($v === null) {
            return null;
        }
        if (is_bool($v)) {
            return $v;
        }
        $s = strtolower(trim((string) $v));
        if (in_array($s, ['true', '1', 's', 'sim', 'yes'], true)) {
            return true;
        }
        if (in_array($s, ['false', '0', 'n', 'nao', 'não', 'no'], true)) {
            return false;
        }

        return null;
    }

    /**
     * @param  array<string, mixed>  $emit
     * @param  array<string, mixed>|null  $api
     * @return array<string, mixed>|null
     */
    private function resolverEnderecoPreferindoNfe(array $emit, ?array $api): ?array
    {
        $nf = isset($emit['endereco']) && is_array($emit['endereco']) ? $emit['endereco'] : null;
        $nfTemDados = $nf && (
            (strlen(preg_replace('/\D/', '', (string) ($nf['cep'] ?? ''))) >= 5)
            || trim((string) ($nf['logradouro'] ?? '')) !== ''
            || trim((string) ($nf['cidade'] ?? '')) !== ''
        );

        if ($nfTemDados && $nf !== null) {
            $cepFmt = $this->formatarCepExibicao((string) ($nf['cep'] ?? ''));

            return array_filter([
                'cep' => $cepFmt,
                'logradouro' => $this->trimOrNull($nf['logradouro'] ?? null, 255),
                'numero' => $this->trimOrNull($nf['numero'] ?? null, 20),
                'complemento' => $this->trimOrNull($nf['complemento'] ?? null, 100),
                'bairro' => $this->trimOrNull($nf['bairro'] ?? null, 100),
                'cidade' => $this->trimOrNull($nf['cidade'] ?? null, 100),
                'estado' => $this->trimOrNull(strtoupper(substr((string) ($nf['estado'] ?? ''), 0, 2)), 2),
                'pais' => $this->trimOrNull($nf['pais'] ?? 'Brasil', 100) ?? 'Brasil',
            ], fn ($v) => $v !== null && $v !== '');
        }

        if (is_array($api) && (
            strlen(preg_replace('/\D/', '', (string) ($api['cep'] ?? ''))) >= 5
            || trim((string) ($api['logradouro'] ?? '')) !== ''
        )) {
            return array_filter([
                'cep' => $this->formatarCepExibicao(preg_replace('/\D/', '', (string) ($api['cep'] ?? ''))),
                'logradouro' => $this->trimOrNull($api['logradouro'] ?? null, 255),
                'numero' => $this->trimOrNull($api['numero'] ?? null, 20),
                'complemento' => null,
                'bairro' => $this->trimOrNull($api['bairro'] ?? null, 100),
                'cidade' => $this->trimOrNull($api['cidade'] ?? null, 100),
                'estado' => $this->trimOrNull(strtoupper(substr((string) ($api['uf'] ?? ''), 0, 2)), 2),
                'pais' => 'Brasil',
            ], fn ($v) => $v !== null && $v !== '');
        }

        return null;
    }

    private function trimOrNull(mixed $v, int $max): ?string
    {
        if ($v === null) {
            return null;
        }
        $s = trim((string) $v);

        return $s === '' ? null : mb_substr($s, 0, $max);
    }

    private function formatarCepExibicao(string $cepDigits): ?string
    {
        $d = preg_replace('/\D/', '', $cepDigits);
        if (strlen($d) === 8) {
            return substr($d, 0, 5).'-'.substr($d, 5, 3);
        }
        if (strlen($d) >= 5) {
            return $d;
        }

        return null;
    }

    private function formatarTelefoneNfe(?string $somenteDigitos): ?string
    {
        $d = preg_replace('/\D/', '', (string) $somenteDigitos);
        if (strlen($d) < 8) {
            return null;
        }
        if (strlen($d) === 11) {
            return '('.substr($d, 0, 2).') '.substr($d, 2, 5).'-'.substr($d, 7);
        }
        if (strlen($d) === 10) {
            return '('.substr($d, 0, 2).') '.substr($d, 2, 4).'-'.substr($d, 6);
        }

        return mb_substr($d, 0, 20);
    }

    /**
     * @param  array<string, mixed>  $emit
     * @param  array<string, mixed>|null  $api
     */
    private function criarContatosFornecedor(Cliente $cliente, array $emit, ?array $api): void
    {
        $nfFone = $this->formatarTelefoneNfe(isset($emit['fone']) ? (string) $emit['fone'] : null);
        $nfEmail = trim((string) ($emit['email'] ?? ''));
        if ($nfEmail !== '' && ! filter_var($nfEmail, FILTER_VALIDATE_EMAIL)) {
            $nfEmail = '';
        }

        $apiTel1 = '';
        $apiTel2 = '';
        $apiEmail = '';
        $apiNome = '';
        if (is_array($api) && isset($api['contatos'][0]) && is_array($api['contatos'][0])) {
            $c = $api['contatos'][0];
            $apiTel1 = trim((string) ($c['telefone'] ?? ''));
            $apiTel2 = trim((string) ($c['telefone2'] ?? ''));
            $apiEmail = trim((string) ($c['email'] ?? ''));
            $apiNome = trim((string) ($c['nome'] ?? ''));
            if ($apiEmail !== '' && ! filter_var($apiEmail, FILTER_VALIDATE_EMAIL)) {
                $apiEmail = '';
            }
        }

        $telefone = $nfFone ?? '';
        if ($telefone === '' && $apiTel1 !== '') {
            $telefone = $apiTel1;
        }

        $telefone2 = '';
        if ($nfFone && $apiTel1 !== '' && $apiTel1 !== $nfFone) {
            $telefone2 = $apiTel1;
        } elseif ($apiTel2 !== '') {
            $telefone2 = $apiTel2;
        }

        $email = $nfEmail !== '' ? $nfEmail : $apiEmail;

        if ($telefone === '' && $telefone2 === '' && $email === '') {
            return;
        }

        $nomeContato = mb_substr($apiNome !== '' ? $apiNome : $cliente->nome, 0, 100);

        Contato::create([
            'cliente_id' => $cliente->id,
            'nome' => $nomeContato,
            'telefone' => $telefone !== '' ? mb_substr($telefone, 0, 20) : null,
            'telefone2' => $telefone2 !== '' ? mb_substr($telefone2, 0, 20) : null,
            'email' => $email !== '' ? mb_substr($email, 0, 255) : null,
            'principal' => true,
            'is_fornecedor' => true,
        ]);
    }

    private function formatarCnpj(string $d14): string
    {
        if (strlen($d14) !== 14) {
            return $d14;
        }

        return substr($d14, 0, 2).'.'.substr($d14, 2, 3).'.'.substr($d14, 5, 3).'/'.substr($d14, 8, 4).'-'.substr($d14, 12, 2);
    }
}
