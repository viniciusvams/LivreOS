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

use App\Models\CategoriaProduto;
use App\Models\Deposito;
use App\Models\Empresa;
use App\Models\Produto;
use App\Models\ProdutoComposicao;
use App\Models\ProdutoImagem;
use App\Models\ProdutoVariacao;
use App\Models\ProdutoVariacaoAtributo;
use App\Models\TabelaPreco;
use App\Models\User;
use Database\Seeders\AdquirenteSeeder;
use Database\Seeders\AdquirenteSumUpFormasPagamentoSeeder;
use Database\Seeders\AdquirenteSumUpSeeder;
use Database\Seeders\CategoriaDocumentoSeeder;
use Database\Seeders\CentrosCustoSecer;
use Database\Seeders\ConfiguracoesFinanceiroSeeder;
use Database\Seeders\ConfiguracoesOsPadraoSeeder;
use Database\Seeders\ConfiguracoesVendasEPropostasPadraoSeeder;
use Database\Seeders\ContaBancariaSeeder;
use Database\Seeders\FinanceiroCategoriasTagsPadraoSeeder;
use Database\Seeders\FormaPagamentoSeeder;
use Database\Seeders\PlanoContaSeeder;
use Database\Seeders\PropostaDocumentoModeloPadraoSeeder;
use Database\Seeders\StatusOsSeeder;
use Database\Seeders\TabelaPrecoSeeder;
use Database\Seeders\TagSeeder;
use Database\Seeders\TermoGarantiaSeeder;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;

class ZerarFabricaService
{
    /** Tabelas que devem ser preservadas (acesso e permissões). */
    protected const TABELAS_PRESERVAR = [
        'users',
        'roles',
        'permissions',
        'permission_role',
        'role_user',
        'migrations',
    ];

    /**
     * Seeders após zerar (alinhado ao DatabaseSeeder, exceto RolePermissionSeeder,
     * ProdutoSeeder e OrdemServicoSeeder; produtos vêm de seedProdutosIniciais()).
     * Inclui SumUp como no DatabaseSeeder.
     *
     * @return list<class-string>
     */
    public static function classesSeedersAposZerar(): array
    {
        return [
            CategoriaDocumentoSeeder::class,
            TabelaPrecoSeeder::class,
            TagSeeder::class,
            TermoGarantiaSeeder::class,
            StatusOsSeeder::class,
            ContaBancariaSeeder::class,
            FormaPagamentoSeeder::class,
            PlanoContaSeeder::class,
            ConfiguracoesFinanceiroSeeder::class,
            FinanceiroCategoriasTagsPadraoSeeder::class,
            CentrosCustoSecer::class,
            ConfiguracoesOsPadraoSeeder::class,
            AdquirenteSeeder::class,
            AdquirenteSumUpSeeder::class,
            AdquirenteSumUpFormasPagamentoSeeder::class,
            ConfiguracoesVendasEPropostasPadraoSeeder::class,
            PropostaDocumentoModeloPadraoSeeder::class,
        ];
    }

    /**
     * Zera o sistema (truncar tabelas exceto acesso/permissões), roda seeders e limpa anexos.
     * Deve ser chamado apenas após backup completo ter sido criado com sucesso.
     *
     * @throws \RuntimeException
     */
    public function executar(): void
    {
        $driver = config('database.default');

        if ($driver === 'mysql' || $driver === 'mariadb') {
            $this->zerarMysql();
        } elseif ($driver === 'sqlite') {
            $this->zerarSqlite();
        } else {
            throw new \RuntimeException('Zerar fábrica não suportado para o driver: '.$driver);
        }

        $this->rodarSeeders();
        $this->limparAnexos();
        $this->seedEmpresaPadrao();
        $this->seedProdutosIniciais();
    }

    protected function zerarMysql(): void
    {
        $tables = $this->listarTabelasMysql();
        $paraTruncar = array_diff($tables, self::TABELAS_PRESERVAR);
        if (empty($paraTruncar)) {
            return;
        }

        DB::statement('SET FOREIGN_KEY_CHECKS = 0');
        try {
            foreach ($paraTruncar as $table) {
                DB::statement('TRUNCATE TABLE `'.str_replace('`', '``', $table).'`');
            }
        } finally {
            DB::statement('SET FOREIGN_KEY_CHECKS = 1');
        }
    }

    /**
     * @return list<string>
     */
    protected function listarTabelasMysql(): array
    {
        $result = DB::select('SHOW TABLES');
        if ($result === []) {
            return [];
        }
        // Coluna devolvida pelo MySQL/MariaDB: Tables_in_<nome_da_base> (usar a base da conexão ativa)
        $first = (array) $result[0];
        $key = array_key_first($first);
        if ($key === null || ! str_starts_with((string) $key, 'Tables_in_')) {
            $dbName = DB::connection()->getDatabaseName();
            $key = 'Tables_in_'.$dbName;
        }
        $tables = [];
        foreach ($result as $row) {
            $name = $row->{$key} ?? null;
            if (is_string($name) && $name !== '') {
                $tables[] = $name;
            }
        }

        return $tables;
    }

    protected function zerarSqlite(): void
    {
        $tables = $this->listarTabelasSqlite();
        $paraTruncar = array_diff($tables, self::TABELAS_PRESERVAR);
        if (empty($paraTruncar)) {
            return;
        }

        DB::statement('PRAGMA foreign_keys = OFF');
        try {
            foreach ($paraTruncar as $table) {
                DB::statement('DELETE FROM '.$this->quoteSqliteIdentifier($table));
            }
            foreach ($paraTruncar as $table) {
                DB::statement('DELETE FROM sqlite_sequence WHERE name = '.DB::getPdo()->quote($table));
            }
        } finally {
            DB::statement('PRAGMA foreign_keys = ON');
        }
    }

    /**
     * @return list<string>
     */
    protected function listarTabelasSqlite(): array
    {
        $result = DB::select("SELECT name FROM sqlite_master WHERE type='table' AND name NOT LIKE 'sqlite_%'");

        return array_map(fn ($row) => $row->name, $result);
    }

    protected function quoteSqliteIdentifier(string $name): string
    {
        return '"'.str_replace('"', '""', $name).'"';
    }

    /**
     * Executa todos os seeders após zerar, exceto RolePermissionSeeder e DemoDataSeeder.
     * Em produção o comando db:seed exige --force (ConfirmableTrait); sem isso aborta sem semear.
     * Falhas em código de saída ou exceção propagam.
     */
    protected function rodarSeeders(): void
    {
        foreach (self::classesSeedersAposZerar() as $seederClass) {
            $exitCode = Artisan::call('db:seed', [
                '--class' => $seederClass,
                '--force' => true,
            ]);
            if ($exitCode !== 0) {
                $out = trim(Artisan::output());
                $base = class_basename($seederClass);
                throw new \RuntimeException(
                    "Seeder {$base} não concluiu (código {$exitCode}). "
                    .($out !== '' ? $out : 'Em produção confirme se db:seed está autorizado (--force).')
                );
            }
        }
    }

    protected function limparAnexos(): void
    {
        $storagePath = storage_path('app/public');
        if (! File::isDirectory($storagePath)) {
            return;
        }
        $items = File::allFiles($storagePath);
        foreach ($items as $file) {
            File::delete($file->getPathname());
        }
        $dirs = File::directories($storagePath);
        foreach ($dirs as $dir) {
            File::deleteDirectory($dir);
        }
    }

    /**
     * Recria o cadastro da empresa com dados iniciais padrão.
     */
    protected function seedEmpresaPadrao(): void
    {
        $logoRelPath = 'empresa/logos/logo-livre-os.jpg';
        $logoOrigemPublic = public_path('images/logo-livre-os.jpg');
        $logoDestinoStorage = storage_path('app/public/'.$logoRelPath);
        if (File::exists($logoOrigemPublic)) {
            $logoDir = dirname($logoDestinoStorage);
            if (! File::isDirectory($logoDir)) {
                File::makeDirectory($logoDir, 0775, true);
            }
            File::copy($logoOrigemPublic, $logoDestinoStorage);
        } else {
            $logoRelPath = null;
        }

        Empresa::query()->delete();
        Empresa::query()->create([
            'nome' => 'LivreOS - ERP Open Source Livre',
            'razao_social' => 'LivreOS - ERP Open Source Livre',
            'cnpj' => '00.000.000/0000-00',
            'email' => 'contato@livreos.com.br',
            'telefone' => '(00) 0000-0000',
            'celular' => '(00) 00000-0000',
            'celular_whatsapp' => true,
            'site' => 'https://www.livreos.com.br',
            'cep' => '00000-000',
            'logradouro' => 'Rua Exemplo',
            'numero' => '100',
            'complemento' => 'Dados iniciais',
            'bairro' => 'Centro',
            'cidade' => 'São Paulo',
            'estado' => 'SP',
            'pais' => 'Brasil',
            'logo_path' => $logoRelPath,
            'observacoes' => 'Cadastro inicial após zerar sistema.',
        ]);
    }

    /**
     * Cria 20 produtos iniciais de informática (simples, variações e kits).
     */
    protected function seedProdutosIniciais(): void
    {
        $user = User::query()->first();
        $categoria = CategoriaProduto::query()->firstOrCreate(
            ['slug' => 'informatica'],
            ['nome' => 'Informática', 'ativo' => true]
        );
        $deposito = Deposito::query()->firstOrCreate(
            ['nome' => 'Depósito Principal'],
            ['localizacao' => 'Estoque Informática', 'ativo' => true]
        );

        $tabelas = TabelaPreco::query()->orderBy('nome')->get();
        $base = [
            ['nome' => 'Mouse Sem Fio', 'sku' => 'INF-MOU-001', 'preco' => 89.90, 'formato' => 'variacao', 'variacoes' => [['Cor' => 'Preto'], ['Cor' => 'Branco'], ['Cor' => 'Azul']]],
            ['nome' => 'Teclado Mecânico', 'sku' => 'INF-TEC-002', 'preco' => 259.90, 'formato' => 'variacao', 'variacoes' => [['Layout' => 'ABNT2'], ['Layout' => 'US']]],
            ['nome' => 'Monitor LED', 'sku' => 'INF-MON-003', 'preco' => 899.90, 'formato' => 'variacao', 'variacoes' => [['Tamanho' => '24'], ['Tamanho' => '27']]],
            ['nome' => 'Headset Gamer', 'sku' => 'INF-HEA-004', 'preco' => 179.90, 'formato' => 'variacao', 'variacoes' => [['Cor' => 'Preto'], ['Cor' => 'Branco']]],
            ['nome' => 'Webcam Full HD', 'sku' => 'INF-WEB-005', 'preco' => 219.90, 'formato' => 'simples'],
            ['nome' => 'SSD SATA 480GB', 'sku' => 'INF-SSD-006', 'preco' => 289.90, 'formato' => 'simples'],
            ['nome' => 'SSD NVMe 1TB', 'sku' => 'INF-SSD-007', 'preco' => 529.90, 'formato' => 'simples'],
            ['nome' => 'Memória RAM DDR4 8GB', 'sku' => 'INF-RAM-008', 'preco' => 169.90, 'formato' => 'simples'],
            ['nome' => 'Memória RAM DDR4 16GB', 'sku' => 'INF-RAM-009', 'preco' => 299.90, 'formato' => 'simples'],
            ['nome' => 'Roteador Wi-Fi 6', 'sku' => 'INF-ROT-010', 'preco' => 479.90, 'formato' => 'simples'],
            ['nome' => 'Switch Gigabit 8 Portas', 'sku' => 'INF-SWI-011', 'preco' => 229.90, 'formato' => 'simples'],
            ['nome' => 'Impressora Multifuncional', 'sku' => 'INF-IMP-012', 'preco' => 799.90, 'formato' => 'simples'],
            ['nome' => 'Notebook i5', 'sku' => 'INF-NBK-013', 'preco' => 3499.90, 'formato' => 'variacao', 'variacoes' => [['RAM' => '8GB'], ['RAM' => '16GB']]],
            ['nome' => 'Notebook i7', 'sku' => 'INF-NBK-014', 'preco' => 4899.90, 'formato' => 'variacao', 'variacoes' => [['RAM' => '16GB'], ['RAM' => '32GB']]],
            ['nome' => 'Cadeira Ergonômica', 'sku' => 'INF-CAD-015', 'preco' => 1199.90, 'formato' => 'variacao', 'variacoes' => [['Cor' => 'Preto'], ['Cor' => 'Cinza']]],
            ['nome' => 'Mesa para Escritório', 'sku' => 'INF-MES-016', 'preco' => 699.90, 'formato' => 'simples'],
        ];

        $bySku = [];
        foreach ($base as $index => $item) {
            $produto = Produto::query()->create([
                'nome' => $item['nome'],
                'codigo_sku' => $item['sku'],
                'preco_venda' => $item['preco'],
                'unidade' => 'UN',
                'formato' => $item['formato'],
                'condicao' => 'novo',
                'linha_produto' => 'Informática Inicial',
                'categoria_id' => $categoria->id,
                'marca' => 'LivreOS Tech',
                'producao' => 'terceiros',
                'frete_gratis' => false,
                'estoque_deposito_id' => $deposito->id,
                'estoque_quantidade' => 25,
                'estoque_preco_compra_unitario' => max(1, round($item['preco'] * 0.60, 2)),
                'estoque_preco_custo_unitario' => max(1, round($item['preco'] * 0.70, 2)),
                'tributacao_origem' => '0',
                'tributacao_ncm' => '00000000',
                'tributacao_tipo_item' => '01',
                'tributacao_percentual_tributos' => 12.00,
                'created_by' => $user?->id,
                'updated_by' => $user?->id,
            ]);

            $bySku[$item['sku']] = $produto;

            if ($tabelas->isNotEmpty()) {
                $sync = [];
                foreach ($tabelas as $tabela) {
                    $nomeTabela = strtolower((string) $tabela->nome);
                    $preco = $item['preco'];
                    if (str_contains($nomeTabela, 'empresa')) {
                        $preco *= 0.9;
                    } elseif (str_contains($nomeTabela, 'urg')) {
                        $preco *= 1.2;
                    }
                    $sync[$tabela->id] = ['preco' => round($preco, 2)];
                }
                $produto->tabelasPrecos()->sync($sync);
            }

            if (! empty($item['variacoes'])) {
                $atributos = [];
                foreach ($item['variacoes'] as $v) {
                    foreach ($v as $attr => $value) {
                        $atributos[$attr] = $atributos[$attr] ?? [];
                        if (! in_array($value, $atributos[$attr], true)) {
                            $atributos[$attr][] = $value;
                        }
                    }
                }
                foreach ($atributos as $attr => $opcoes) {
                    ProdutoVariacaoAtributo::query()->create([
                        'produto_id' => $produto->id,
                        'atributo_nome' => $attr,
                        'opcoes' => $opcoes,
                    ]);
                }
                $usedSkus = [];
                foreach ($item['variacoes'] as $v) {
                    $suffix = implode('-', array_map(
                        static fn ($x) => strtoupper(preg_replace('/[^A-Za-z0-9]/', '', substr((string) $x, 0, 4)) ?: 'V'),
                        array_values($v)
                    ));
                    $sku = $item['sku'].'-'.$suffix;
                    $i = 2;
                    while (in_array($sku, $usedSkus, true)) {
                        $sku = $item['sku'].'-'.$suffix.$i;
                        $i++;
                    }
                    $usedSkus[] = $sku;
                    ProdutoVariacao::query()->create([
                        'produto_id' => $produto->id,
                        'referencia_sku' => $sku,
                        'opcoes_valores' => $v,
                        'ean_variacao' => null,
                        'quantidade' => 8,
                        'herdar_do_pai' => true,
                    ]);
                }
            }

            ProdutoImagem::query()->create([
                'produto_id' => $produto->id,
                'tipo' => 'url',
                'url_externa' => 'https://picsum.photos/seed/inf-'.($index + 1).'/900/900',
                'ordem' => 0,
            ]);
            ProdutoImagem::query()->create([
                'produto_id' => $produto->id,
                'tipo' => 'url',
                'url_externa' => 'https://picsum.photos/seed/inf-'.($index + 101).'/900/900',
                'ordem' => 1,
            ]);
        }

        $kits = [
            ['nome' => 'Kit Home Office', 'sku' => 'INF-KIT-017', 'preco' => 2399.90, 'componentes' => [['INF-NBK-013', 1], ['INF-MOU-001', 1], ['INF-HEA-004', 1]]],
            ['nome' => 'Kit Setup Gamer Inicial', 'sku' => 'INF-KIT-018', 'preco' => 3999.90, 'componentes' => [['INF-NBK-014', 1], ['INF-TEC-002', 1], ['INF-MOU-001', 1], ['INF-HEA-004', 1]]],
            ['nome' => 'Kit Escritório Completo', 'sku' => 'INF-KIT-019', 'preco' => 3299.90, 'componentes' => [['INF-MON-003', 1], ['INF-IMP-012', 1], ['INF-MES-016', 1], ['INF-CAD-015', 1]]],
            ['nome' => 'Kit Rede para Escritório', 'sku' => 'INF-KIT-020', 'preco' => 899.90, 'componentes' => [['INF-ROT-010', 1], ['INF-SWI-011', 1], ['INF-WEB-005', 1]]],
        ];

        foreach ($kits as $index => $kit) {
            $produtoKit = Produto::query()->create([
                'nome' => $kit['nome'],
                'codigo_sku' => $kit['sku'],
                'preco_venda' => $kit['preco'],
                'unidade' => 'KIT',
                'formato' => 'composicao',
                'condicao' => 'novo',
                'linha_produto' => 'Informática Inicial',
                'categoria_id' => $categoria->id,
                'marca' => 'LivreOS Tech',
                'producao' => 'terceiros',
                'frete_gratis' => true,
                'estoque_deposito_id' => $deposito->id,
                'estoque_quantidade' => 6,
                'estoque_preco_compra_unitario' => max(1, round($kit['preco'] * 0.60, 2)),
                'estoque_preco_custo_unitario' => max(1, round($kit['preco'] * 0.70, 2)),
                'tributacao_origem' => '0',
                'tributacao_ncm' => '00000000',
                'tributacao_tipo_item' => '01',
                'tributacao_percentual_tributos' => 12.00,
                'created_by' => $user?->id,
                'updated_by' => $user?->id,
            ]);

            foreach ($kit['componentes'] as [$sku, $qtd]) {
                $componente = $bySku[$sku] ?? null;
                if (! $componente) {
                    continue;
                }
                $custo = (float) ($componente->estoque_preco_custo_unitario ?? 0);
                ProdutoComposicao::query()->create([
                    'produto_id' => $produtoKit->id,
                    'componente_id' => $componente->id,
                    'quantidade' => $qtd,
                    'custo_unitario' => $custo,
                    'custo_total' => $custo * $qtd,
                ]);
            }

            ProdutoImagem::query()->create([
                'produto_id' => $produtoKit->id,
                'tipo' => 'url',
                'url_externa' => 'https://picsum.photos/seed/kit-inf-'.($index + 1).'/900/900',
                'ordem' => 0,
            ]);
        }
    }
}
