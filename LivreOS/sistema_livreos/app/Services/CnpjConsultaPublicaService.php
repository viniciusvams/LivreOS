<?php

/**
 * Consulta CNPJ em APIs públicas (mesmas URLs usadas no cadastro de cliente em resources/views/clientes/_form.blade.php).
 *
 * @copyright 2024-2026 LivreOS
 * @license   https://www.gnu.org/licenses/agpl-3.0.txt AGPL-3.0
 */

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class CnpjConsultaPublicaService
{
    /**
     * @return array<string, mixed>|null Estrutura compatível com o merge do formulário de cliente (razao_social, contatos, socios, etc.)
     */
    public function consultar(string $cnpj14): ?array
    {
        $cnpj14 = preg_replace('/\D/', '', $cnpj14);
        if (strlen($cnpj14) !== 14) {
            return null;
        }

        $urls = [
            'https://publica.cnpj.ws/cnpj/'.$cnpj14,
            'https://api.opencnpj.org/'.$cnpj14,
        ];

        $normalizados = [];
        foreach ($urls as $url) {
            try {
                $response = Http::timeout(14)
                    ->withHeaders([
                        'Accept' => 'application/json',
                        'User-Agent' => 'LivreOS-ERP/1.0',
                    ])
                    ->get($url);
                if (! $response->successful()) {
                    continue;
                }
                $data = $response->json();
                if (! is_array($data) || $data === []) {
                    continue;
                }
                $normalizados[] = $this->normalizarResposta($data);
            } catch (\Throwable $e) {
                Log::debug('cnpj.consulta_publica', ['url' => $url, 'erro' => $e->getMessage()]);
            }
        }

        if ($normalizados === []) {
            return null;
        }

        return $this->mesclarLista($normalizados);
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function normalizarResposta(array $data): array
    {
        $estabelecimento = $data['estabelecimento'] ?? $data['endereco'] ?? $data['address'] ?? $data;

        $atividadePrincipal = $data['estabelecimento']['atividade_principal']
            ?? $data['atividade_principal']
            ?? $data['atividadePrincipal']
            ?? $data['cnae_principal']
            ?? $data['main_activity']
            ?? $data['mainActivity']
            ?? null;

        $atividadeDescricao = '';
        if (is_array($atividadePrincipal)) {
            if (isset($atividadePrincipal[0]) && is_array($atividadePrincipal[0])) {
                $atividadeDescricao = (string) ($atividadePrincipal[0]['descricao'] ?? $atividadePrincipal[0]['text'] ?? '');
            } elseif (isset($atividadePrincipal['descricao']) || isset($atividadePrincipal['text'])) {
                $atividadeDescricao = (string) ($atividadePrincipal['descricao'] ?? $atividadePrincipal['text'] ?? '');
            }
        } elseif (is_string($atividadePrincipal)) {
            $atividadeDescricao = $atividadePrincipal;
        }

        $cidade = $estabelecimento['cidade']['nome']
            ?? $estabelecimento['municipio']['nome']
            ?? $estabelecimento['cidade']
            ?? $estabelecimento['municipio']
            ?? $data['municipio']
            ?? $estabelecimento['localidade']
            ?? '';

        if (! is_string($cidade)) {
            $cidade = (string) $cidade;
        }

        $uf = $estabelecimento['estado']['sigla']
            ?? $estabelecimento['estado']['uf']
            ?? $estabelecimento['estado']
            ?? $estabelecimento['uf']
            ?? $data['uf']
            ?? '';

        if (! is_string($uf)) {
            $uf = (string) $uf;
        }

        return [
            'razao_social' => (string) ($data['razao_social'] ?? $data['razaoSocial'] ?? $data['nome'] ?? $data['company']['name'] ?? ''),
            'nome_fantasia' => (string) ($estabelecimento['nome_fantasia']
                ?? $estabelecimento['nomeFantasia']
                ?? $data['nome_fantasia']
                ?? $data['nomeFantasia']
                ?? $data['fantasia']
                ?? $data['nome_fantasia_empresarial']
                ?? $data['alias']
                ?? ''),
            'ramo_atividade' => $atividadeDescricao,
            'natureza_juridica' => $this->stringNaturezaJuridica($data),
            'porte_empresa' => $this->stringPorte($data),
            'capital_social' => (string) ($data['capital_social']
                ?? $data['capitalSocial']
                ?? $data['capital_social_empresa']
                ?? ''),
            'data_inicio_atividade' => $this->normalizarData(
                $estabelecimento['data_inicio_atividade']
                ?? $estabelecimento['dataInicioAtividade']
                ?? $data['data_inicio_atividade']
                ?? $data['dataInicioAtividade']
                ?? $data['data_abertura']
                ?? $data['abertura']
                ?? ''
            ),
            'optante_simples' => $data['simples']['opcao_simples']
                ?? $data['simples']['simples']
                ?? $data['opcao_simples']
                ?? $data['opcaoSimples']
                ?? $data['simples']
                ?? null,
            'cep' => (string) ($estabelecimento['cep'] ?? $data['cep'] ?? ''),
            'logradouro' => (string) ($estabelecimento['logradouro'] ?? $data['logradouro'] ?? $estabelecimento['endereco'] ?? $estabelecimento['street'] ?? ''),
            'numero' => (string) ($estabelecimento['numero'] ?? $data['numero'] ?? $estabelecimento['number'] ?? ''),
            'bairro' => (string) ($estabelecimento['bairro'] ?? $data['bairro'] ?? $estabelecimento['district'] ?? ''),
            'cidade' => $cidade,
            'uf' => $uf,
            'contatos' => $this->extrairContatos($data),
            'socios' => $this->extrairSocios($data),
        ];
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function stringNaturezaJuridica(array $data): string
    {
        $nj = $data['natureza_juridica'] ?? null;
        if (is_array($nj)) {
            return (string) ($nj['descricao'] ?? $nj['codigo'] ?? '');
        }

        return (string) ($nj ?? $data['naturezaJuridica'] ?? $data['natureza_juridica_codigo'] ?? '');
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function stringPorte(array $data): string
    {
        $p = $data['porte'] ?? null;
        if (is_array($p)) {
            return (string) ($p['descricao'] ?? $p['codigo'] ?? '');
        }
        if (is_scalar($p) && trim((string) $p) !== '') {
            return trim((string) $p);
        }

        return (string) ($data['porte_empresa'] ?? $data['porteEmpresa'] ?? '');
    }

    private function normalizarData(mixed $value): string
    {
        if ($value === null || $value === '') {
            return '';
        }
        $raw = trim((string) $value);
        if ($raw === '') {
            return '';
        }
        if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $raw)) {
            return $raw;
        }
        if (preg_match('/^(\d{2})\/(\d{2})\/(\d{4})$/', $raw, $m)) {
            return $m[3].'-'.$m[2].'-'.$m[1];
        }

        return '';
    }

    /**
     * @param  array<string, mixed>  $data
     * @return list<array{nome: string, email: string, telefone: string, telefone2: string}>
     */
    private function extrairContatos(array $data): array
    {
        $est = $data['estabelecimento'] ?? [];
        $email = (string) ($data['email'] ?? $est['email'] ?? $data['contact']['email'] ?? '');
        $nomeContato = (string) (
            $data['nome_fantasia']
            ?? $data['nomeFantasia']
            ?? $data['razao_social']
            ?? $data['razaoSocial']
            ?? $data['nome']
            ?? ''
        );
        $telefones = [];

        if (isset($data['telefones']) && is_array($data['telefones'])) {
            foreach ($data['telefones'] as $tel) {
                if (! is_array($tel)) {
                    continue;
                }
                $ddd = (string) ($tel['ddd'] ?? '');
                $numero = (string) ($tel['numero'] ?? '');
                if ($ddd !== '' || $numero !== '') {
                    $telefones[] = ($ddd !== '' ? '('.$ddd.') ' : '').$numero;
                }
            }
        }

        $tel1 = (string) ($est['telefone1'] ?? $data['telefone1'] ?? $data['telefone'] ?? '');
        $tel2 = (string) ($est['telefone2'] ?? $data['telefone2'] ?? '');
        $ddd1 = (string) ($est['ddd1'] ?? $data['ddd1'] ?? '');
        $ddd2 = (string) ($est['ddd2'] ?? $data['ddd2'] ?? '');

        if ($tel1 !== '') {
            $telefones[] = ($ddd1 !== '' ? '('.$ddd1.') ' : '').$tel1;
        }
        if ($tel2 !== '') {
            $telefones[] = ($ddd2 !== '' ? '('.$ddd2.') ' : '').$tel2;
        }

        if ($email === '' && $telefones === []) {
            return [];
        }

        return [[
            'nome' => $nomeContato,
            'email' => $email,
            'telefone' => $telefones[0] ?? '',
            'telefone2' => $telefones[1] ?? '',
        ]];
    }

    /**
     * @param  array<string, mixed>  $data
     * @return list<array{nome_completo: string, cpf: string, socio_administrador: bool|null}>
     */
    private function extrairSocios(array $data): array
    {
        $listas = array_filter([
            $data['socios'] ?? null,
            $data['QSA'] ?? null,
            $data['qsa'] ?? null,
            is_array($data['socios'] ?? null) ? ($data['socios']['socios'] ?? null) : null,
            is_array($data['qsa'] ?? null) ? ($data['qsa']['socios'] ?? null) : null,
            is_array($data['qsa'] ?? null) ? ($data['qsa']['qsa'] ?? null) : null,
        ]);

        $lista = [];
        foreach ($listas as $item) {
            if (is_array($item) && $item !== []) {
                $lista = $item;
                break;
            }
        }

        $out = [];
        if (! is_array($lista)) {
            return $out;
        }

        foreach ($lista as $socio) {
            if (! is_array($socio)) {
                continue;
            }
            $nome = (string) (
                $socio['nome_socio']
                ?? $socio['nome']
                ?? $socio['nome_razao']
                ?? $socio['razao_social']
                ?? $socio['razaoSocial']
                ?? $socio['nomeFantasia']
                ?? $socio['nome_socio_ou_nome']
                ?? ''
            );
            $cpf = (string) (
                $socio['cnpj_cpf_socio']
                ?? $socio['cnpj_cpf']
                ?? $socio['cpf_cnpj']
                ?? $socio['cpf']
                ?? $socio['documento']
                ?? $socio['cnpj']
                ?? ''
            );
            $qualificacao = (string) (
                $socio['qualificacao_socio']
                ?? $socio['qualificacao']
                ?? $socio['qualificacao_do_socio']
                ?? $socio['qualificacao_responsavel']
                ?? ''
            );
            $isAdmin = $qualificacao !== '' && preg_match('/administrador/i', $qualificacao) === 1;

            if ($nome !== '' || $cpf !== '') {
                $out[] = [
                    'nome_completo' => $nome,
                    'cpf' => $cpf,
                    'socio_administrador' => $isAdmin ? true : null,
                ];
            }
        }

        return $out;
    }

    /**
     * @param  list<array<string, mixed>>  $lista
     * @return array<string, mixed>
     */
    private function mesclarLista(array $lista): array
    {
        usort($lista, fn ($a, $b) => $this->contarCampos($b) <=> $this->contarCampos($a));
        $merged = $lista[0];

        foreach (array_slice($lista, 1) as $item) {
            foreach (array_keys($merged) as $key) {
                if ($this->isEmptyValue($merged[$key] ?? null) && ! $this->isEmptyValue($item[$key] ?? null)) {
                    $merged[$key] = $item[$key];
                }
            }
        }

        return $merged;
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function contarCampos(array $data): int
    {
        $fields = ['razao_social', 'nome_fantasia', 'ramo_atividade', 'cep', 'logradouro', 'numero', 'bairro', 'cidade', 'uf', 'natureza_juridica', 'porte_empresa', 'capital_social', 'data_inicio_atividade'];
        $count = 0;
        foreach ($fields as $key) {
            if (! $this->isEmptyValue($data[$key] ?? null)) {
                $count++;
            }
        }
        if (isset($data['contatos']) && is_array($data['contatos']) && $data['contatos'] !== []) {
            $count++;
        }
        if (isset($data['socios']) && is_array($data['socios']) && $data['socios'] !== []) {
            $count++;
        }

        return $count;
    }

    private function isEmptyValue(mixed $value): bool
    {
        if ($value === null) {
            return true;
        }
        if (is_string($value)) {
            return trim($value) === '';
        }
        if (is_array($value)) {
            return $value === [];
        }

        return false;
    }
}
