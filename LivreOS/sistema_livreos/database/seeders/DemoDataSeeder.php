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

namespace Database\Seeders;

use App\Models\BaixaTitulo;
use App\Models\CentroCusto;
use App\Models\Cliente;
use App\Models\ContaBancaria;
use App\Models\ContaPagar;
use App\Models\ContaPagarRecorrente;
use App\Models\ContaReceber;
use App\Models\Contato;
use App\Models\Endereco;
use App\Models\FormaPagamento;
use App\Models\MovimentacaoFinanceira;
use App\Models\OrdemServico;
use App\Models\OrdemServicoProduto;
use App\Models\OrdemServicoServico;
use App\Models\PagamentoRecorrente;
use App\Models\PlanoConta;
use App\Models\Produto;
use App\Models\Servico;
use App\Models\Socio;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class DemoDataSeeder extends Seeder
{
    private ?User $user;
    private array $clientes = [];
    private array $produtos = [];
    private array $servicos = [];
    private array $relatorio = [];
    private ?ContaBancaria $contaCaixa;
    private ?ContaBancaria $contaCorrente;
    private ?FormaPagamento $fpPix;
    private ?FormaPagamento $fpDinheiro;
    private ?FormaPagamento $fpBoleto;
    private ?FormaPagamento $fpCredito;
    private ?FormaPagamento $fpDebito;
    private ?PlanoConta $planoReceitaServico;
    private ?PlanoConta $planoReceitaVenda;
    private ?PlanoConta $planoDespAluguel;
    private ?PlanoConta $planoDespEnergia;
    private ?PlanoConta $planoDespAgua;
    private ?PlanoConta $planoDespTelefone;
    private ?PlanoConta $planoDespMaterial;
    private ?CentroCusto $ccOperacional;
    private ?CentroCusto $ccAdministrativo;
    private ?CentroCusto $ccComercial;

    public function run(): void
    {
        $this->user = User::first();
        if (!$this->user) {
            $this->command->error('Nenhum usuário encontrado. Execute RolePermissionSeeder primeiro.');
            return;
        }

        $this->command->info('=== DEMO DATA SEEDER ===');
        $this->command->newLine();

        $this->limparDados();
        $this->carregarReferencias();
        $this->criarClientes();
        $this->criarServicos();
        $this->criarOrdensServico();
        $this->criarVendasPdv();
        $this->criarFinanceiro();
        $this->gerarRelatorio();

        $this->command->newLine();
        $this->command->info('=== SEED COMPLETO ===');
    }

    private function limparDados(): void
    {
        $this->command->info('Limpando dados existentes...');

        $driver = DB::connection()->getDriverName();
        if ($driver === 'mysql') {
            DB::statement('SET FOREIGN_KEY_CHECKS=0');
        } else {
            DB::statement('PRAGMA foreign_keys = OFF');
        }

        $clear = function (string $table) {
            if (Schema::hasTable($table)) {
                DB::table($table)->delete();
            }
        };

        $clear('baixas_titulos');
        $clear('movimentacoes_financeiras');
        $clear('conta_receber_anexos');
        $clear('conta_pagar_anexos');
        $clear('contas_receber');
        $clear('contas_pagar');
        $clear('pagamentos_recorrentes');
        $clear('contas_pagar_recorrentes');

        $clear('plugin_pdv_venda_pagamentos');
        $clear('plugin_pdv_venda_itens');
        $clear('plugin_pdv_vendas');
        $clear('plugin_pdv_caixa_movimentacoes');
        $clear('plugin_pdv_caixa_fechamento_quebra_sobra');
        $clear('plugin_pdv_aviso_quebra_sobra_oculto');
        $clear('plugin_pdv_orcamento_itens');
        $clear('plugin_pdv_orcamentos');
        $clear('plugin_pdv_caixas');

        $clear('ordem_servico_produtos');
        $clear('ordem_servico_servicos');
        $clear('ordem_servico_logs');
        $clear('ordem_servico_tecnicos');
        $clear('ordem_servico_apontamentos');
        $clear('ordem_servico_anexos');
        $clear('ordem_servico_assinaturas');
        $clear('ordem_servico_assinatura_events');
        $clear('ordem_servico_assinatura_tokens');
        $clear('ordem_servico_documentos');
        $clear('checklist_answers');
        $clear('ordem_servicos');

        $clear('documentos');
        $clear('enderecos');
        $clear('contatos');
        $clear('socios');

        Cliente::withTrashed()->forceDelete();

        if ($driver === 'mysql') {
            DB::statement('SET FOREIGN_KEY_CHECKS=1');
        } else {
            DB::statement('PRAGMA foreign_keys = ON');
        }

        $this->command->info('Dados limpos com sucesso.');
    }

    private function carregarReferencias(): void
    {
        $this->contaCaixa = ContaBancaria::where('tipo', 'caixa')->first();
        $this->contaCorrente = ContaBancaria::where('tipo', 'conta_corrente')->first();

        $this->fpPix = FormaPagamento::where('tipo', 'pix')->first();
        $this->fpDinheiro = FormaPagamento::where('tipo', 'dinheiro')->first();
        $this->fpBoleto = FormaPagamento::where('tipo', 'boleto')->first();
        $this->fpCredito = FormaPagamento::where('tipo', 'cartao_credito')->first();
        $this->fpDebito = FormaPagamento::where('tipo', 'cartao_debito')->first();

        $this->planoReceitaServico = PlanoConta::where('codigo', '1.1')->first();
        $this->planoReceitaVenda = PlanoConta::where('codigo', '1.2')->first();
        $this->planoDespAluguel = PlanoConta::where('codigo', '3.2.2')->first();
        $this->planoDespEnergia = PlanoConta::where('codigo', '3.2.3')->first();
        $this->planoDespAgua = PlanoConta::where('codigo', '3.2.4')->first();
        $this->planoDespTelefone = PlanoConta::where('codigo', '3.2.5')->first();
        $this->planoDespMaterial = PlanoConta::where('codigo', '3.2.6')->first();

        $this->ccOperacional = CentroCusto::where('codigo', 'OPE')->first();
        $this->ccAdministrativo = CentroCusto::where('codigo', 'ADM')->first();
        $this->ccComercial = CentroCusto::where('codigo', 'COM')->first();

        $this->produtos = Produto::all()->all();
        $this->servicos = Servico::all()->all();
    }

    private function criarClientes(): void
    {
        $this->command->info('Criando clientes...');

        // 1. Cliente Balcão
        $balcao = Cliente::create([
            'tipo_pessoa' => 'F',
            'pais_origem' => 'BR',
            'nome' => 'Cliente Balcão',
            'cpf' => '000.000.000-00',
            'observacoes' => 'Cliente genérico para vendas de balcão sem identificação.',
        ]);
        $this->criarEnderecoContato($balcao, 'Rua do Comércio', '100', 'Centro', 'São Paulo', 'SP', '01001-000', 'Atendimento Balcão', '(11) 99999-0000', 'balcao@empresa.com.br');
        $this->clientes[] = $balcao;

        // 2. 5 Clientes Pessoa Física
        $pfs = [
            ['nome' => 'Maria Silva Santos', 'cpf' => '123.456.789-09', 'rg' => '12.345.678-9', 'data_nascimento' => '1985-03-15', 'sexo' => 'F', 'rua' => 'Av. Paulista', 'num' => '1578', 'bairro' => 'Bela Vista', 'cidade' => 'São Paulo', 'uf' => 'SP', 'cep' => '01310-100', 'fone' => '(11) 98765-4321', 'email' => 'maria.silva@email.com'],
            ['nome' => 'João Pedro Oliveira', 'cpf' => '987.654.321-00', 'rg' => '23.456.789-0', 'data_nascimento' => '1990-07-22', 'sexo' => 'M', 'rua' => 'Rua Augusta', 'num' => '2340', 'bairro' => 'Consolação', 'cidade' => 'São Paulo', 'uf' => 'SP', 'cep' => '01305-100', 'fone' => '(11) 97654-3210', 'email' => 'joao.oliveira@email.com'],
            ['nome' => 'Ana Carolina Pereira', 'cpf' => '456.789.123-45', 'rg' => '34.567.890-1', 'data_nascimento' => '1978-11-08', 'sexo' => 'F', 'rua' => 'Rua Oscar Freire', 'num' => '890', 'bairro' => 'Jardins', 'cidade' => 'São Paulo', 'uf' => 'SP', 'cep' => '01426-001', 'fone' => '(11) 96543-2109', 'email' => 'ana.pereira@email.com'],
            ['nome' => 'Carlos Eduardo Lima', 'cpf' => '321.654.987-12', 'rg' => '45.678.901-2', 'data_nascimento' => '1982-01-30', 'sexo' => 'M', 'rua' => 'Rua Haddock Lobo', 'num' => '456', 'bairro' => 'Cerqueira César', 'cidade' => 'São Paulo', 'uf' => 'SP', 'cep' => '01414-001', 'fone' => '(11) 95432-1098', 'email' => 'carlos.lima@email.com'],
            ['nome' => 'Fernanda Rodrigues Costa', 'cpf' => '654.321.987-65', 'rg' => '56.789.012-3', 'data_nascimento' => '1995-05-12', 'sexo' => 'F', 'rua' => 'Rua da Consolação', 'num' => '1234', 'bairro' => 'Consolação', 'cidade' => 'São Paulo', 'uf' => 'SP', 'cep' => '01302-001', 'fone' => '(11) 94321-0987', 'email' => 'fernanda.costa@email.com'],
        ];

        foreach ($pfs as $pf) {
            $c = Cliente::create([
                'tipo_pessoa' => 'F', 'pais_origem' => 'BR', 'nome' => $pf['nome'],
                'cpf' => $pf['cpf'], 'rg' => $pf['rg'], 'data_nascimento' => $pf['data_nascimento'],
                'sexo' => $pf['sexo'], 'limite_credito' => rand(1000, 10000),
                'classificacao_rating' => rand(1, 5),
            ]);
            $this->criarEnderecoContato($c, $pf['rua'], $pf['num'], $pf['bairro'], $pf['cidade'], $pf['uf'], $pf['cep'], $pf['nome'], $pf['fone'], $pf['email']);
            $this->clientes[] = $c;
        }

        // 3. 10 Empresas PJ
        $pjs = [
            ['nome' => 'TechSeg Soluções', 'razao' => 'TechSeg Soluções em Segurança Ltda', 'cnpj' => '11.222.333/0001-44', 'ie' => '123.456.789.001', 'ramo' => 'Segurança eletrônica'],
            ['nome' => 'Condomínio Solar dos Lagos', 'razao' => 'Condomínio Residencial Solar dos Lagos', 'cnpj' => '22.333.444/0001-55', 'ie' => '', 'ramo' => 'Condomínio residencial'],
            ['nome' => 'Supermercado Bom Preço', 'razao' => 'Bom Preço Comércio de Alimentos Ltda', 'cnpj' => '33.444.555/0001-66', 'ie' => '234.567.890.002', 'ramo' => 'Varejo alimentício'],
            ['nome' => 'Padaria Pão Quente', 'razao' => 'Pão Quente Alimentos e Bebidas ME', 'cnpj' => '44.555.666/0001-77', 'ie' => '345.678.901.003', 'ramo' => 'Padaria e confeitaria'],
            ['nome' => 'Auto Peças Central', 'razao' => 'Central Auto Peças e Serviços Ltda', 'cnpj' => '55.666.777/0001-88', 'ie' => '456.789.012.004', 'ramo' => 'Comércio de autopeças'],
            ['nome' => 'Escola Futuro Brilhante', 'razao' => 'Instituto Educacional Futuro Brilhante S/S', 'cnpj' => '66.777.888/0001-99', 'ie' => '', 'ramo' => 'Educação infantil'],
            ['nome' => 'Clínica Saúde Total', 'razao' => 'Saúde Total Serviços Médicos Ltda', 'cnpj' => '77.888.999/0001-00', 'ie' => '', 'ramo' => 'Serviços médicos'],
            ['nome' => 'Restaurante Sabor da Terra', 'razao' => 'Sabor da Terra Gastronomia Ltda', 'cnpj' => '88.999.000/0001-11', 'ie' => '567.890.123.005', 'ramo' => 'Gastronomia'],
            ['nome' => 'Farmácia Vida & Saúde', 'razao' => 'Vida e Saúde Drogaria Ltda', 'cnpj' => '99.000.111/0001-22', 'ie' => '678.901.234.006', 'ramo' => 'Farmácia'],
            ['nome' => 'Escritório ContMais', 'razao' => 'ContMais Contabilidade e Assessoria Ltda', 'cnpj' => '10.111.222/0001-33', 'ie' => '', 'ramo' => 'Contabilidade'],
        ];

        $ruasPj = ['Av. Brasil', 'Rua Vergueiro', 'Av. Santo Amaro', 'Rua Domingos de Morais', 'Av. Brigadeiro Faria Lima', 'Rua Cardeal Arcoverde', 'Av. Rebouças', 'Rua dos Pinheiros', 'Av. Nove de Julho', 'Rua Funchal'];

        foreach ($pjs as $i => $pj) {
            $c = Cliente::create([
                'tipo_pessoa' => 'J', 'pais_origem' => 'BR', 'nome' => $pj['nome'],
                'razao_social' => $pj['razao'], 'cnpj' => $pj['cnpj'],
                'inscricao_estadual' => $pj['ie'] ?: null, 'ramo_atividade' => $pj['ramo'],
                'natureza_juridica' => 'Sociedade Limitada', 'porte_empresa' => 'ME',
                'classificacao_rating' => rand(2, 5), 'limite_credito' => rand(5000, 50000),
                'optante_simples_nacional' => true,
            ]);
            $this->criarEnderecoContato($c, $ruasPj[$i], (string)rand(100, 3000), 'Centro', 'São Paulo', 'SP', sprintf('0%d%03d-000', $i + 1, rand(100, 999)), 'Responsável ' . $pj['nome'], '(11) 3' . rand(100, 999) . '-' . rand(1000, 9999), 'contato@' . Str::slug($pj['nome']) . '.com.br');

            Contato::create([
                'cliente_id' => $c->id, 'nome' => 'Financeiro ' . $pj['nome'],
                'telefone' => '(11) 3' . rand(100, 999) . '-' . rand(1000, 9999),
                'email' => 'financeiro@' . Str::slug($pj['nome']) . '.com.br',
                'cargo' => 'Financeiro', 'principal' => false, 'cobranca' => true,
            ]);

            $this->clientes[] = $c;
        }

        // 4. 2 Empresas com 2 filiais cada
        $matrizes = [
            ['nome' => 'Grupo Vigília Total', 'razao' => 'Vigília Total Segurança Patrimonial SA', 'cnpj' => '20.300.400/0001-50', 'ramo' => 'Segurança patrimonial', 'filiais' => [
                ['nome' => 'Vigília Total - Filial Campinas', 'cnpj' => '20.300.400/0002-31', 'cidade' => 'Campinas', 'uf' => 'SP'],
                ['nome' => 'Vigília Total - Filial Curitiba', 'cnpj' => '20.300.400/0003-12', 'cidade' => 'Curitiba', 'uf' => 'PR'],
            ]],
            ['nome' => 'Rede Proteger', 'razao' => 'Rede Proteger Tecnologia e Monitoramento SA', 'cnpj' => '30.400.500/0001-60', 'ramo' => 'Monitoramento eletrônico', 'filiais' => [
                ['nome' => 'Rede Proteger - Filial RJ', 'cnpj' => '30.400.500/0002-41', 'cidade' => 'Rio de Janeiro', 'uf' => 'RJ'],
                ['nome' => 'Rede Proteger - Filial BH', 'cnpj' => '30.400.500/0003-22', 'cidade' => 'Belo Horizonte', 'uf' => 'MG'],
            ]],
        ];

        foreach ($matrizes as $m) {
            $matriz = Cliente::create([
                'tipo_pessoa' => 'J', 'pais_origem' => 'BR', 'nome' => $m['nome'],
                'razao_social' => $m['razao'], 'cnpj' => $m['cnpj'],
                'ramo_atividade' => $m['ramo'], 'natureza_juridica' => 'Sociedade Anônima',
                'porte_empresa' => 'Médio', 'classificacao_rating' => 5,
                'limite_credito' => 100000, 'tipo_unidade' => 'matriz',
                'capital_social' => 500000,
            ]);
            $this->criarEnderecoContato($matriz, 'Av. Faria Lima', (string)rand(1000, 5000), 'Itaim Bibi', 'São Paulo', 'SP', '04538-000', 'Diretor ' . $m['nome'], '(11) 3' . rand(100, 999) . '-' . rand(1000, 9999), 'diretoria@' . Str::slug($m['nome']) . '.com.br');

            Socio::create([
                'cliente_id' => $matriz->id, 'nome_completo' => 'Roberto ' . explode(' ', $m['nome'])[1],
                'cpf' => rand(100, 999) . '.' . rand(100, 999) . '.' . rand(100, 999) . '-' . rand(10, 99),
                'nacionalidade' => 'Brasileira', 'estado_civil' => 'Casado(a)',
                'profissao' => 'Empresário', 'socio_administrador' => true,
                'percentual_participacao' => 60.00, 'score_credito' => 850,
                'cep' => '04538-000', 'logradouro' => 'Av. Faria Lima', 'numero' => '3000',
                'bairro' => 'Itaim Bibi', 'cidade' => 'São Paulo', 'uf' => 'SP',
            ]);

            $this->clientes[] = $matriz;

            foreach ($m['filiais'] as $f) {
                $filial = Cliente::create([
                    'tipo_pessoa' => 'J', 'pais_origem' => 'BR', 'nome' => $f['nome'],
                    'razao_social' => $m['razao'], 'cnpj' => $f['cnpj'],
                    'ramo_atividade' => $m['ramo'], 'natureza_juridica' => 'Sociedade Anônima',
                    'porte_empresa' => 'Médio', 'tipo_unidade' => 'filial',
                    'matriz_id' => $matriz->id, 'limite_credito' => 50000,
                ]);
                $this->criarEnderecoContato($filial, 'Rua Principal', (string)rand(100, 2000), 'Centro', $f['cidade'], $f['uf'], rand(10000, 99999) . '-000', 'Gerente ' . $f['nome'], '(' . rand(11, 99) . ') 3' . rand(100, 999) . '-' . rand(1000, 9999), 'gerencia@' . Str::slug($m['nome']) . '.com.br');
                $this->clientes[] = $filial;
            }
        }

        $this->command->info('  ' . count($this->clientes) . ' clientes criados.');
    }

    private function criarEnderecoContato(Cliente $c, string $rua, string $num, string $bairro, string $cidade, string $uf, string $cep, string $nomeContato, string $fone, string $email): void
    {
        Endereco::create([
            'cliente_id' => $c->id, 'cep' => $cep, 'logradouro' => $rua,
            'numero' => $num, 'bairro' => $bairro, 'cidade' => $cidade,
            'estado' => $uf, 'pais' => 'Brasil', 'principal' => true, 'cobranca' => true, 'entrega' => true,
        ]);
        Contato::create([
            'cliente_id' => $c->id, 'nome' => $nomeContato, 'telefone' => $fone,
            'email' => $email, 'principal' => true, 'cobranca' => true,
        ]);
    }

    private function criarServicos(): void
    {
        $this->command->info('Criando serviços...');

        if (count($this->servicos) < 5) {
            $catServ = \App\Models\CategoriaServico::firstOrCreate(['slug' => 'video-seguranca'], ['nome' => 'Vídeo e segurança', 'ativo' => true]);
            $catManu = \App\Models\CategoriaServico::firstOrCreate(['slug' => 'manutencao'], ['nome' => 'Manutenção', 'ativo' => true]);

            $lista = [
                ['nome' => 'Instalação de ponto IP', 'preco' => 150.00, 'unidade' => 'unidade', 'cat' => $catServ->id, 'descricao' => 'Instalação completa de ponto IP com configuração de rede e ajuste de ângulo.', 'valor_custo' => 60.00],
                ['nome' => 'Instalação de ponto analógico', 'preco' => 120.00, 'unidade' => 'unidade', 'cat' => $catServ->id, 'descricao' => 'Instalação de ponto analógico com passagem de cabo e teste.', 'valor_custo' => 45.00],
                ['nome' => 'Configuração de DVR/NVR', 'preco' => 250.00, 'unidade' => 'unidade', 'cat' => $catServ->id, 'descricao' => 'Configuração de gravador com acesso remoto, detecção de movimento e backup.', 'valor_custo' => 80.00],
                ['nome' => 'Manutenção preventiva de vídeo', 'preco' => 350.00, 'unidade' => 'visita', 'cat' => $catManu->id, 'descricao' => 'Limpeza de lentes, verificação de cabos, teste de gravação e atualização de firmware.', 'valor_custo' => 100.00],
                ['nome' => 'Manutenção corretiva de vídeo', 'preco' => 200.00, 'unidade' => 'hora', 'cat' => $catManu->id, 'descricao' => 'Diagnóstico e reparo de falhas no sistema de monitoramento.', 'valor_custo' => 70.00],
                ['nome' => 'Instalação de Sistema de Alarme', 'preco' => 400.00, 'unidade' => 'projeto', 'cat' => $catServ->id, 'descricao' => 'Instalação completa com central, sensores e sirene.', 'valor_custo' => 150.00],
                ['nome' => 'Passagem de Infraestrutura', 'preco' => 80.00, 'unidade' => 'hora', 'cat' => $catServ->id, 'descricao' => 'Passagem de cabos, conduítes e canaletas.', 'valor_custo' => 30.00],
                ['nome' => 'Projeto de vídeo vigilância', 'preco' => 800.00, 'unidade' => 'projeto', 'cat' => $catServ->id, 'descricao' => 'Elaboração de projeto técnico com planta e memorial descritivo.', 'valor_custo' => 200.00],
                ['nome' => 'Treinamento do Sistema', 'preco' => 180.00, 'unidade' => 'hora', 'cat' => $catServ->id, 'descricao' => 'Treinamento do cliente para uso do sistema de monitoramento.', 'valor_custo' => 50.00],
                ['nome' => 'Suporte remoto de vídeo', 'preco' => 100.00, 'unidade' => 'hora', 'cat' => $catManu->id, 'descricao' => 'Suporte técnico remoto para configuração e resolução de problemas.', 'valor_custo' => 30.00],
            ];

            foreach ($lista as $s) {
                $srv = Servico::create([
                    'nome' => $s['nome'], 'preco' => $s['preco'], 'unidade' => $s['unidade'],
                    'categoria_id' => $s['cat'], 'descricao' => $s['descricao'],
                    'valor_custo' => $s['valor_custo'], 'created_by' => $this->user->id,
                ]);
                $this->servicos[] = $srv;
            }
            $this->command->info('  ' . count($this->servicos) . ' serviços no total.');
        } else {
            $this->command->info('  ' . count($this->servicos) . ' serviços já existem. OK.');
        }
    }

    private function criarOrdensServico(): void
    {
        $this->command->info('Criando 100 ordens de serviço...');

        $clientesParaOs = array_filter($this->clientes, fn($c) => $c->nome !== 'Cliente Balcão');
        if (empty($clientesParaOs)) { return; }

        $statusDist = [
            'Aberta' => 15, 'Em análise/orçamento' => 8, 'Aguardando aprovação' => 5,
            'Aprovado pelo cliente' => 8, 'Aguardando peças' => 5, 'Em execução' => 12,
            'Em teste' => 5, 'Concluída' => 10, 'Encerrada' => 25, 'Cancelada' => 7,
        ];

        $tiposOs = ['Instalação', 'Manutenção', 'Reparo', 'Suporte', 'Locação'];
        $origensOs = ['App', 'Site', 'Telefone', 'Email', 'WhatsApp', 'Presencial'];
        $prioridades = ['Baixa', 'Normal', 'Alta', 'Urgente'];

        $osNum = 0;
        $totalGeralOs = 0;
        $totalFinanceiroGerado = 0;

        foreach ($statusDist as $status => $qty) {
            for ($i = 0; $i < $qty; $i++) {
                $osNum++;
                $cliente = $clientesParaOs[array_rand($clientesParaOs)];
                $dataAbertura = Carbon::now()->subDays(rand(1, 90));

                $prevInicio = $dataAbertura->copy()->addDays(rand(1, 5));
                $prevConclusao = $prevInicio->copy()->addDays(rand(1, 10));

                $realInicio = null;
                $realConclusao = null;
                $encerrada = false;
                $financGerado = false;
                $estoqueBaixado = false;

                if (in_array($status, ['Em execução', 'Em teste', 'Concluída', 'Encerrada', 'Aprovado pelo cliente'])) {
                    $realInicio = $prevInicio->copy()->addHours(rand(0, 48));
                }
                if (in_array($status, ['Concluída', 'Encerrada'])) {
                    $realConclusao = $prevConclusao->copy()->addHours(rand(-24, 48));
                    $estoqueBaixado = true;
                }
                if ($status === 'Encerrada') {
                    $encerrada = true;
                    $financGerado = true;
                }

                $numProd = rand(1, 4);
                $numServ = rand(1, 3);
                $totalProd = 0;
                $totalServ = 0;

                $osProdutos = [];
                $osServicos = [];

                if (!empty($this->produtos)) {
                    $prodsEscolhidos = (array) array_rand($this->produtos, min($numProd, count($this->produtos)));
                    foreach ($prodsEscolhidos as $pi) {
                        $p = $this->produtos[$pi];
                        $qtd = rand(1, 5);
                        $val = (float)($p->preco_venda ?? 100);
                        $tot = round($qtd * $val, 2);
                        $totalProd += $tot;
                        $osProdutos[] = ['produto_id' => $p->id, 'descricao' => $p->nome, 'quantidade' => $qtd, 'valor_unitario' => $val, 'total' => $tot];
                    }
                }

                if (!empty($this->servicos)) {
                    $servsEscolhidos = (array) array_rand($this->servicos, min($numServ, count($this->servicos)));
                    foreach ($servsEscolhidos as $si) {
                        $s = $this->servicos[$si];
                        $qtd = rand(1, 3);
                        $val = (float)($s->preco ?? 100);
                        $tot = round($qtd * $val, 2);
                        $totalServ += $tot;
                        $osServicos[] = ['servico_id' => $s->id, 'descricao' => $s->nome, 'quantidade' => $qtd, 'valor_unitario' => $val, 'total' => $tot];
                    }
                }

                $desconto = round(($totalProd + $totalServ) * (rand(0, 10) / 100), 2);
                $totalGeral = round($totalProd + $totalServ - $desconto, 2);
                $totalGeralOs += $totalGeral;

                $os = OrdemServico::create([
                    'cliente_id' => $cliente->id,
                    'tipo_os' => $tiposOs[array_rand($tiposOs)],
                    'origem_os' => $origensOs[array_rand($origensOs)],
                    'prioridade' => $prioridades[array_rand($prioridades)],
                    'status' => $status,
                    'data_abertura' => $dataAbertura,
                    'prevista_inicio' => $prevInicio,
                    'prevista_conclusao' => $prevConclusao,
                    'real_inicio' => $realInicio,
                    'real_conclusao' => $realConclusao,
                    'sla_valor' => rand(24, 72),
                    'sla_unidade' => 'horas',
                    'relato_cliente' => 'Solicitação de serviço de segurança eletrônica #' . $osNum,
                    'diagnostico_tecnico' => $status !== 'Aberta' ? 'Diagnóstico técnico realizado. Equipamentos verificados.' : null,
                    'servicos_realizados' => $encerrada ? 'Todos os serviços foram executados conforme especificação técnica.' : null,
                    'observacoes_internas' => 'OS de demonstração #' . $osNum,
                    'garantia_dias' => rand(30, 365),
                    'equipamento_nome' => 'Sistema de monitoramento',
                    'equipamento_marca' => ['VisionTech', 'SecureCam', 'Intelbras', 'Hikvision'][rand(0, 3)],
                    'equipamento_modelo' => 'MOD-' . rand(1000, 9999),
                    'equipamento_numero_serie' => 'SN' . rand(10000000, 99999999),
                    'garantia_tipo' => rand(0, 1) ? 'fabricante' : 'prestador',
                    'total_produtos' => $totalProd,
                    'total_servicos' => $totalServ,
                    'desconto_global' => $desconto,
                    'total_descontos' => $desconto,
                    'total_geral' => $totalGeral,
                    'estoque_baixado' => $estoqueBaixado,
                    'financeiro_gerado' => $financGerado,
                    'created_by' => $this->user->id,
                    'updated_by' => $this->user->id,
                    'ip_criacao' => '127.0.0.1',
                ]);

                foreach ($osProdutos as $op) {
                    OrdemServicoProduto::create(array_merge($op, ['ordem_servico_id' => $os->id]));
                }
                foreach ($osServicos as $osv) {
                    OrdemServicoServico::create(array_merge($osv, ['ordem_servico_id' => $os->id]));
                }

                if ($encerrada && $totalGeral > 0) {
                    $this->criarFinanceiroOs($os, $totalGeral, $cliente);
                    $totalFinanceiroGerado += $totalGeral;
                }
            }
        }

        $this->relatorio['os_total'] = $osNum;
        $this->relatorio['os_valor_total'] = $totalGeralOs;
        $this->relatorio['os_financeiro_gerado'] = $totalFinanceiroGerado;
        $this->relatorio['os_por_status'] = $statusDist;
        $this->command->info("  $osNum OS criadas. Total: R$ " . number_format($totalGeralOs, 2, ',', '.'));
    }

    private function criarFinanceiroOs(OrdemServico $os, float $total, Cliente $cliente): void
    {
        $formas = [$this->fpPix, $this->fpDinheiro, $this->fpDebito, $this->fpCredito];
        $fp = $formas[array_rand(array_filter($formas))] ?? $this->fpDinheiro;
        if (!$fp) return;

        $contaBancaria = $fp->conta_bancaria_id ? ContaBancaria::find($fp->conta_bancaria_id) : $this->contaCorrente;
        $dataPgto = ($os->real_conclusao ?? now())->copy();

        $taxa = round($total * (($fp->taxa_percentual ?? 0) / 100) + ($fp->taxa_fixa ?? 0), 2);
        $liquido = round($total - $taxa, 2);

        $cr = ContaReceber::create([
            'cliente_id' => $cliente->id, 'ordem_servico_id' => $os->id,
            'descricao' => 'OS ' . ($os->codigo_interno ?? '#' . $os->id),
            'valor' => $total, 'valor_original' => $total, 'valor_recebido' => $total,
            'data_vencimento' => $dataPgto->toDateString(), 'data_recebimento' => $dataPgto->toDateString(),
            'forma_pagamento_id' => $fp->id,
            'plano_conta_id' => $this->planoReceitaServico?->id,
            'centro_custo_id' => $this->ccOperacional?->id,
            'status' => 'pago', 'numero_parcela' => 1, 'total_parcelas' => 1,
            'created_by' => $this->user->id,
        ]);

        if ($contaBancaria) {
            $mov = MovimentacaoFinanceira::create([
                'conta_bancaria_id' => $contaBancaria->id, 'tipo' => 'entrada',
                'origem' => 'baixa_titulo', 'conta_receber_id' => $cr->id,
                'plano_conta_id' => $this->planoReceitaServico?->id,
                'centro_custo_id' => $this->ccOperacional?->id,
                'valor' => $liquido, 'data_movimentacao' => $dataPgto->toDateString(),
                'descricao' => 'Recebimento OS ' . ($os->codigo_interno ?? '#' . $os->id),
                'conciliado' => true, 'data_conciliacao' => $dataPgto->toDateString(),
                'conciliado_por' => $this->user->id,
                'created_by' => $this->user->id,
            ]);

            BaixaTitulo::create([
                'tipo_titulo' => 'conta_receber', 'titulo_id' => $cr->id,
                'movimentacao_id' => $mov->id, 'valor_baixa' => $total,
                'data_baixa' => $dataPgto->toDateString(),
                'created_by' => $this->user->id,
            ]);
        }
    }

    private function criarVendasPdv(): void
    {
        if (!Schema::hasTable('plugin_pdv_caixas') || !Schema::hasTable('plugin_pdv_vendas')) {
            $this->command->warn('  Tabelas PDV não encontradas. Pulando vendas PDV.');
            return;
        }

        $this->command->info('Criando vendas PDV...');

        $caixa = DB::table('plugin_pdv_caixas')->insertGetId([
            'numero_caixa' => '1', 'user_id' => $this->user->id,
            'valor_abertura' => 200.00, 'status' => 'aberto',
            'opened_at' => now()->subDays(30), 'created_at' => now(), 'updated_at' => now(),
        ]);

        $balcao = $this->clientes[0] ?? null;
        $totalVendasPdv = 0;
        $qtdVendas = 30;

        for ($v = 0; $v < $qtdVendas; $v++) {
            $clienteVenda = rand(0, 2) === 0 ? $balcao : ($this->clientes[array_rand($this->clientes)] ?? $balcao);
            $dataVenda = Carbon::now()->subDays(rand(0, 30));
            $numItens = rand(1, 5);
            $totalVenda = 0;
            $itensVenda = [];

            $prodDisponiveis = array_values(array_filter($this->produtos, fn($p) => ($p->preco_venda ?? 0) > 0));
            if (empty($prodDisponiveis)) continue;

            for ($it = 0; $it < $numItens; $it++) {
                $prod = $prodDisponiveis[array_rand($prodDisponiveis)];
                $qtd = rand(1, 3);
                $preco = (float)$prod->preco_venda;
                $totItem = round($qtd * $preco, 2);
                $totalVenda += $totItem;
                $itensVenda[] = [
                    'tipo' => 'produto', 'produto_id' => $prod->id,
                    'descricao' => $prod->nome, 'quantidade' => $qtd,
                    'preco_unitario' => $preco, 'total' => $totItem,
                    'created_at' => $dataVenda, 'updated_at' => $dataVenda,
                ];
            }

            $desconto = rand(0, 5) === 0 ? round($totalVenda * 0.05, 2) : 0;
            $totalFinal = round($totalVenda - $desconto, 2);
            $totalVendasPdv += $totalFinal;

            $vendaId = DB::table('plugin_pdv_vendas')->insertGetId([
                'caixa_id' => $caixa, 'cliente_id' => $clienteVenda?->id,
                'tipo' => 'venda', 'status' => 'finalizada',
                'total' => $totalVenda, 'desconto' => $desconto, 'total_final' => $totalFinal,
                'created_at' => $dataVenda, 'updated_at' => $dataVenda,
            ]);

            foreach ($itensVenda as $item) {
                DB::table('plugin_pdv_venda_itens')->insert(array_merge($item, ['venda_id' => $vendaId]));
            }

            $fp = [$this->fpPix, $this->fpDinheiro, $this->fpDebito, $this->fpCredito];
            $fpEscolhida = $fp[array_rand(array_filter($fp))];
            if ($fpEscolhida) {
                DB::table('plugin_pdv_venda_pagamentos')->insert([
                    'venda_id' => $vendaId, 'forma_pagamento_id' => $fpEscolhida->id,
                    'valor' => $totalFinal, 'parcelas' => 1,
                    'conta_bancaria_id' => $fpEscolhida->conta_bancaria_id,
                    'created_at' => $dataVenda, 'updated_at' => $dataVenda,
                ]);
            }

            $contaBancaria = $fpEscolhida?->conta_bancaria_id ? ContaBancaria::find($fpEscolhida->conta_bancaria_id) : $this->contaCorrente;
            if ($contaBancaria && $clienteVenda) {
                $cr = ContaReceber::create([
                    'cliente_id' => $clienteVenda->id,
                    'descricao' => 'Venda PDV #' . $vendaId,
                    'valor' => $totalFinal, 'valor_original' => $totalFinal, 'valor_recebido' => $totalFinal,
                    'data_vencimento' => $dataVenda->toDateString(), 'data_recebimento' => $dataVenda->toDateString(),
                    'forma_pagamento_id' => $fpEscolhida->id,
                    'plano_conta_id' => $this->planoReceitaVenda?->id,
                    'centro_custo_id' => $this->ccComercial?->id,
                    'status' => 'pago', 'numero_parcela' => 1, 'total_parcelas' => 1,
                    'created_by' => $this->user->id,
                ]);

                $taxa = round($totalFinal * (($fpEscolhida->taxa_percentual ?? 0) / 100) + ($fpEscolhida->taxa_fixa ?? 0), 2);
                $liquido = round($totalFinal - $taxa, 2);

                $mov = MovimentacaoFinanceira::create([
                    'conta_bancaria_id' => $contaBancaria->id, 'tipo' => 'entrada',
                    'origem' => 'baixa_titulo', 'conta_receber_id' => $cr->id,
                    'plano_conta_id' => $this->planoReceitaVenda?->id,
                    'valor' => $liquido, 'data_movimentacao' => $dataVenda->toDateString(),
                    'descricao' => 'Venda PDV #' . $vendaId,
                    'conciliado' => true, 'data_conciliacao' => $dataVenda->toDateString(),
                    'conciliado_por' => $this->user->id, 'created_by' => $this->user->id,
                ]);

                BaixaTitulo::create([
                    'tipo_titulo' => 'conta_receber', 'titulo_id' => $cr->id,
                    'movimentacao_id' => $mov->id, 'valor_baixa' => $totalFinal,
                    'data_baixa' => $dataVenda->toDateString(), 'created_by' => $this->user->id,
                ]);
            }
        }

        $this->relatorio['pdv_vendas'] = $qtdVendas;
        $this->relatorio['pdv_total'] = $totalVendasPdv;
        $this->command->info("  $qtdVendas vendas PDV. Total: R$ " . number_format($totalVendasPdv, 2, ',', '.'));
    }

    private function criarFinanceiro(): void
    {
        $this->command->info('Criando lançamentos financeiros...');

        $clientesPj = array_values(array_filter($this->clientes, fn($c) => $c->tipo_pessoa === 'J'));
        $clientesPf = array_values(array_filter($this->clientes, fn($c) => $c->tipo_pessoa === 'F' && $c->nome !== 'Cliente Balcão'));

        // Contas a Receber avulsas (20 lançamentos)
        $totalCrAberto = 0;
        $totalCrPago = 0;
        $crCount = 0;

        for ($i = 0; $i < 20; $i++) {
            $cli = !empty($clientesPj) ? $clientesPj[array_rand($clientesPj)] : ($this->clientes[array_rand($this->clientes)] ?? null);
            if (!$cli) continue;

            $valor = round(rand(50000, 1500000) / 100, 2);
            $vencimento = Carbon::now()->addDays(rand(-30, 60));
            $pago = rand(0, 1) === 1;

            $fp = [$this->fpPix, $this->fpBoleto, $this->fpCredito];
            $fpEscolhida = $fp[array_rand(array_filter($fp))];

            $cr = ContaReceber::create([
                'cliente_id' => $cli->id,
                'descricao' => 'Serviço de monitoramento ' . Carbon::now()->format('m/Y') . ' - ' . ($i + 1),
                'valor' => $valor, 'valor_original' => $valor,
                'valor_recebido' => $pago ? $valor : 0,
                'data_vencimento' => $vencimento->toDateString(),
                'data_recebimento' => $pago ? $vencimento->toDateString() : null,
                'forma_pagamento_id' => $fpEscolhida?->id,
                'plano_conta_id' => $this->planoReceitaServico?->id,
                'centro_custo_id' => $this->ccOperacional?->id,
                'status' => $pago ? 'pago' : 'aberto',
                'numero_parcela' => 1, 'total_parcelas' => 1,
                'created_by' => $this->user->id,
            ]);

            if ($pago) {
                $totalCrPago += $valor;
                $contaBancaria = $fpEscolhida?->conta_bancaria_id ? ContaBancaria::find($fpEscolhida->conta_bancaria_id) : $this->contaCorrente;
                if ($contaBancaria) {
                    $mov = MovimentacaoFinanceira::create([
                        'conta_bancaria_id' => $contaBancaria->id, 'tipo' => 'entrada',
                        'origem' => 'baixa_titulo', 'conta_receber_id' => $cr->id,
                        'plano_conta_id' => $this->planoReceitaServico?->id,
                        'valor' => $valor, 'data_movimentacao' => $vencimento->toDateString(),
                        'descricao' => $cr->descricao, 'conciliado' => true,
                        'data_conciliacao' => $vencimento->toDateString(),
                        'conciliado_por' => $this->user->id, 'created_by' => $this->user->id,
                    ]);
                    BaixaTitulo::create([
                        'tipo_titulo' => 'conta_receber', 'titulo_id' => $cr->id,
                        'movimentacao_id' => $mov->id, 'valor_baixa' => $valor,
                        'data_baixa' => $vencimento->toDateString(), 'created_by' => $this->user->id,
                    ]);
                }
            } else {
                $totalCrAberto += $valor;
            }
            $crCount++;
        }

        // Contas a Pagar avulsas (25 lançamentos)
        $totalCpAberto = 0;
        $totalCpPago = 0;
        $cpCount = 0;

        $descricoesCp = [
            'Material de escritório', 'Peças e componentes', 'Combustível', 'Manutenção veículo',
            'Material de limpeza', 'Alimentação equipe', 'Hospedagem viagem', 'Pedágio',
            'Impressão de materiais', 'Ferramentas', 'EPI', 'Licença de software',
            'Serviço de TI', 'Seguro veicular', 'Manutenção escritório',
        ];

        $planosDespesa = array_filter([$this->planoDespMaterial, $this->planoDespEnergia, $this->planoDespTelefone]);

        for ($i = 0; $i < 25; $i++) {
            $fornecedor = !empty($clientesPj) ? $clientesPj[array_rand($clientesPj)] : null;
            $valor = round(rand(5000, 500000) / 100, 2);
            $vencimento = Carbon::now()->addDays(rand(-20, 45));
            $pago = rand(0, 1) === 1;

            $planoCp = !empty($planosDespesa) ? $planosDespesa[array_rand($planosDespesa)] : null;

            $cp = ContaPagar::create([
                'fornecedor_id' => $fornecedor?->id,
                'descricao' => $descricoesCp[array_rand($descricoesCp)] . ' - ' . ($i + 1),
                'valor' => $valor, 'valor_original' => $valor,
                'valor_pago' => $pago ? $valor : 0,
                'data_vencimento' => $vencimento->toDateString(),
                'data_pagamento' => $pago ? $vencimento->toDateString() : null,
                'tipo' => ['operacional', 'insumo', 'outro'][rand(0, 2)],
                'forma_pagamento_id' => $this->fpPix?->id,
                'conta_bancaria_id' => $this->contaCorrente?->id,
                'plano_conta_id' => $planoCp?->id,
                'centro_custo_id' => $this->ccAdministrativo?->id,
                'status' => $pago ? 'pago' : 'aberto',
                'created_by' => $this->user->id,
            ]);

            if ($pago && $this->contaCorrente) {
                $totalCpPago += $valor;
                $mov = MovimentacaoFinanceira::create([
                    'conta_bancaria_id' => $this->contaCorrente->id, 'tipo' => 'saida',
                    'origem' => 'baixa_titulo', 'conta_pagar_id' => $cp->id,
                    'plano_conta_id' => $planoCp?->id,
                    'valor' => $valor, 'data_movimentacao' => $vencimento->toDateString(),
                    'descricao' => $cp->descricao, 'conciliado' => true,
                    'data_conciliacao' => $vencimento->toDateString(),
                    'conciliado_por' => $this->user->id, 'created_by' => $this->user->id,
                ]);
                BaixaTitulo::create([
                    'tipo_titulo' => 'conta_pagar', 'titulo_id' => $cp->id,
                    'movimentacao_id' => $mov->id, 'valor_baixa' => $valor,
                    'data_baixa' => $vencimento->toDateString(), 'created_by' => $this->user->id,
                ]);
            } else {
                $totalCpAberto += $valor;
            }
            $cpCount++;
        }

        // Pagamentos Recorrentes a Receber (5)
        $recorrentesReceberCount = 0;
        $clientesRecorrente = array_slice($clientesPj, 0, 5);
        $descRecorrente = ['Monitoramento mensal', 'Contrato manutenção', 'Locação de equipamentos', 'Suporte técnico mensal', 'Contrato de monitoramento'];

        foreach ($clientesRecorrente as $idx => $cli) {
            PagamentoRecorrente::create([
                'cliente_id' => $cli->id,
                'descricao' => ($descRecorrente[$idx] ?? 'Recorrente') . ' - ' . $cli->nome,
                'tipo' => ['aluguel', 'contrato', 'assinatura', 'manutencao', 'outro'][$idx] ?? 'contrato',
                'valor' => round(rand(30000, 300000) / 100, 2),
                'data_inicio' => Carbon::now()->subMonths(3)->toDateString(),
                'data_fim' => Carbon::now()->addMonths(9)->toDateString(),
                'frequencia' => 'mensal',
                'proxima_geracao_em' => Carbon::now()->addDays(rand(1, 30))->toDateString(),
                'forma_pagamento_id' => $this->fpBoleto?->id,
                'conta_bancaria_id' => $this->contaCorrente?->id,
                'plano_conta_id' => $this->planoReceitaServico?->id,
                'ativo' => true,
                'observacoes' => 'Gerado pelo DemoDataSeeder',
                'created_by' => $this->user->id,
            ]);
            $recorrentesReceberCount++;
        }

        // Contas a Pagar Recorrentes (5 despesas fixas)
        $recorrentesPagarCount = 0;
        if (Schema::hasTable('contas_pagar_recorrentes')) {
            $despFixas = [
                ['desc' => 'Aluguel escritório', 'tipo' => 'aluguel', 'valor' => 3500.00, 'plano' => $this->planoDespAluguel],
                ['desc' => 'Energia elétrica', 'tipo' => 'luz', 'valor' => 850.00, 'plano' => $this->planoDespEnergia],
                ['desc' => 'Água e esgoto', 'tipo' => 'agua', 'valor' => 280.00, 'plano' => $this->planoDespAgua],
                ['desc' => 'Telefone e internet', 'tipo' => 'telefone', 'valor' => 450.00, 'plano' => $this->planoDespTelefone],
                ['desc' => 'Material de limpeza', 'tipo' => 'outro', 'valor' => 150.00, 'plano' => $this->planoDespMaterial],
            ];

            foreach ($despFixas as $df) {
                $fornecedor = !empty($clientesPj) ? $clientesPj[array_rand($clientesPj)] : null;
                ContaPagarRecorrente::create([
                    'fornecedor_id' => $fornecedor?->id,
                    'descricao' => $df['desc'],
                    'tipo' => $df['tipo'],
                    'valor' => $df['valor'],
                    'data_inicio' => Carbon::now()->subMonths(6)->toDateString(),
                    'frequencia' => 'mensal',
                    'proxima_geracao_em' => Carbon::now()->addDays(rand(1, 28))->toDateString(),
                    'forma_pagamento_id' => $this->fpBoleto?->id,
                    'conta_bancaria_id' => $this->contaCorrente?->id,
                    'plano_conta_id' => $df['plano']?->id,
                    'ativo' => true,
                    'observacoes' => 'Despesa fixa mensal - DemoDataSeeder',
                    'created_by' => $this->user->id,
                ]);
                $recorrentesPagarCount++;
            }
        }

        $this->relatorio['cr_avulso_count'] = $crCount;
        $this->relatorio['cr_avulso_pago'] = $totalCrPago;
        $this->relatorio['cr_avulso_aberto'] = $totalCrAberto;
        $this->relatorio['cp_avulso_count'] = $cpCount;
        $this->relatorio['cp_avulso_pago'] = $totalCpPago;
        $this->relatorio['cp_avulso_aberto'] = $totalCpAberto;
        $this->relatorio['recorrente_receber'] = $recorrentesReceberCount;
        $this->relatorio['recorrente_pagar'] = $recorrentesPagarCount;

        $this->command->info("  CR: $crCount (pago: R$ " . number_format($totalCrPago, 2, ',', '.') . " / aberto: R$ " . number_format($totalCrAberto, 2, ',', '.') . ')');
        $this->command->info("  CP: $cpCount (pago: R$ " . number_format($totalCpPago, 2, ',', '.') . " / aberto: R$ " . number_format($totalCpAberto, 2, ',', '.') . ')');
        $this->command->info("  Recorrentes: $recorrentesReceberCount a receber, $recorrentesPagarCount a pagar");
    }

    private function gerarRelatorio(): void
    {
        $this->command->info('Gerando relatório...');

        $totalCr = ContaReceber::sum('valor');
        $totalCrPago = ContaReceber::where('status', 'pago')->sum('valor');
        $totalCrAberto = ContaReceber::where('status', 'aberto')->sum('valor');

        $totalCp = ContaPagar::sum('valor');
        $totalCpPago = ContaPagar::where('status', 'pago')->sum('valor');
        $totalCpAberto = ContaPagar::where('status', 'aberto')->sum('valor');

        $totalMovEntrada = MovimentacaoFinanceira::where('tipo', 'entrada')->whereNull('data_cancelamento')->sum('valor');
        $totalMovSaida = MovimentacaoFinanceira::where('tipo', 'saida')->whereNull('data_cancelamento')->sum('valor');

        $saldoCaixa = $this->contaCaixa ? ($this->contaCaixa->saldo_inicial + MovimentacaoFinanceira::where('conta_bancaria_id', $this->contaCaixa->id)->where('tipo', 'entrada')->whereNull('data_cancelamento')->sum('valor') - MovimentacaoFinanceira::where('conta_bancaria_id', $this->contaCaixa->id)->where('tipo', 'saida')->whereNull('data_cancelamento')->sum('valor')) : 0;
        $saldoCC = $this->contaCorrente ? ($this->contaCorrente->saldo_inicial + MovimentacaoFinanceira::where('conta_bancaria_id', $this->contaCorrente->id)->where('tipo', 'entrada')->whereNull('data_cancelamento')->sum('valor') - MovimentacaoFinanceira::where('conta_bancaria_id', $this->contaCorrente->id)->where('tipo', 'saida')->whereNull('data_cancelamento')->sum('valor')) : 0;

        $osTotal = OrdemServico::count();
        $osPorStatus = OrdemServico::select('status', DB::raw('count(*) as total, sum(total_geral) as valor'))
            ->groupBy('status')->get()->mapWithKeys(fn($r) => [$r->status => ['qty' => $r->total, 'valor' => $r->valor]])->toArray();

        $pdvTotal = Schema::hasTable('plugin_pdv_vendas') ? DB::table('plugin_pdv_vendas')->where('status', 'finalizada')->sum('total_final') : 0;
        $pdvQtd = Schema::hasTable('plugin_pdv_vendas') ? DB::table('plugin_pdv_vendas')->where('status', 'finalizada')->count() : 0;

        $recorrentesReceber = PagamentoRecorrente::where('ativo', true)->get();
        $recorrentesPagar = Schema::hasTable('contas_pagar_recorrentes') ? ContaPagarRecorrente::where('ativo', true)->get() : collect();

        $fmt = fn($v) => 'R$ ' . number_format((float)$v, 2, ',', '.');

        $lines = [];
        $lines[] = '=============================================================';
        $lines[] = '  RELATÓRIO DE DADOS DE DEMONSTRAÇÃO - ' . now()->format('d/m/Y H:i');
        $lines[] = '  LivreOS - Base para conferência';
        $lines[] = '=============================================================';
        $lines[] = '';

        $lines[] = '--- CLIENTES ---';
        $lines[] = 'Total: ' . Cliente::count();
        $lines[] = 'Pessoa Física: ' . Cliente::where('tipo_pessoa', 'F')->count();
        $lines[] = 'Pessoa Jurídica: ' . Cliente::where('tipo_pessoa', 'J')->count();
        $lines[] = 'Matrizes: ' . Cliente::where('tipo_unidade', 'matriz')->count();
        $lines[] = 'Filiais: ' . Cliente::where('tipo_unidade', 'filial')->count();
        $lines[] = '';

        $lines[] = '--- PRODUTOS ---';
        $lines[] = 'Total: ' . Produto::count();
        $lines[] = '';

        $lines[] = '--- SERVIÇOS ---';
        $lines[] = 'Total: ' . Servico::count();
        $lines[] = '';

        $lines[] = '--- ORDENS DE SERVIÇO ---';
        $lines[] = 'Total: ' . $osTotal;
        foreach ($osPorStatus as $st => $info) {
            $lines[] = "  $st: {$info['qty']} OS - Total: " . $fmt($info['valor']);
        }
        $lines[] = 'Total geral OS: ' . $fmt(OrdemServico::sum('total_geral'));
        $lines[] = '';

        $lines[] = '--- VENDAS PDV ---';
        $lines[] = 'Vendas finalizadas: ' . $pdvQtd;
        $lines[] = 'Total vendas PDV: ' . $fmt($pdvTotal);
        $lines[] = '';

        $lines[] = '--- CONTAS A RECEBER ---';
        $lines[] = 'Total registros: ' . ContaReceber::count();
        $lines[] = 'Valor total: ' . $fmt($totalCr);
        $lines[] = 'Total pago: ' . $fmt($totalCrPago) . ' (' . ContaReceber::where('status', 'pago')->count() . ' títulos)';
        $lines[] = 'Total aberto: ' . $fmt($totalCrAberto) . ' (' . ContaReceber::where('status', 'aberto')->count() . ' títulos)';
        $lines[] = '';

        $lines[] = '--- CONTAS A PAGAR ---';
        $lines[] = 'Total registros: ' . ContaPagar::count();
        $lines[] = 'Valor total: ' . $fmt($totalCp);
        $lines[] = 'Total pago: ' . $fmt($totalCpPago) . ' (' . ContaPagar::where('status', 'pago')->count() . ' títulos)';
        $lines[] = 'Total aberto: ' . $fmt($totalCpAberto) . ' (' . ContaPagar::where('status', 'aberto')->count() . ' títulos)';
        $lines[] = '';

        $lines[] = '--- MOVIMENTAÇÕES FINANCEIRAS ---';
        $lines[] = 'Total entradas: ' . $fmt($totalMovEntrada) . ' (' . MovimentacaoFinanceira::where('tipo', 'entrada')->whereNull('data_cancelamento')->count() . ')';
        $lines[] = 'Total saídas: ' . $fmt($totalMovSaida) . ' (' . MovimentacaoFinanceira::where('tipo', 'saida')->whereNull('data_cancelamento')->count() . ')';
        $lines[] = 'Saldo movimentações: ' . $fmt($totalMovEntrada - $totalMovSaida);
        $lines[] = '';

        $lines[] = '--- SALDOS BANCÁRIOS (calculado) ---';
        $lines[] = 'Caixa: ' . $fmt($saldoCaixa);
        $lines[] = 'Conta Corrente: ' . $fmt($saldoCC);
        $lines[] = 'Total: ' . $fmt($saldoCaixa + $saldoCC);
        $lines[] = '';

        $lines[] = '--- RECORRENTES A RECEBER ---';
        foreach ($recorrentesReceber as $r) {
            $lines[] = "  {$r->descricao}: " . $fmt($r->valor) . "/mês - Próx: " . ($r->proxima_geracao_em ? Carbon::parse($r->proxima_geracao_em)->format('d/m/Y') : '-');
        }
        $lines[] = 'Total mensal estimado: ' . $fmt($recorrentesReceber->sum('valor'));
        $lines[] = '';

        $lines[] = '--- RECORRENTES A PAGAR ---';
        foreach ($recorrentesPagar as $r) {
            $lines[] = "  {$r->descricao}: " . $fmt($r->valor) . "/mês - Próx: " . ($r->proxima_geracao_em ? Carbon::parse($r->proxima_geracao_em)->format('d/m/Y') : '-');
        }
        $lines[] = 'Total mensal estimado: ' . $fmt($recorrentesPagar->sum('valor'));
        $lines[] = '';

        $lines[] = '--- RESUMO FINANCEIRO ---';
        $lines[] = 'Total a receber (aberto): ' . $fmt($totalCrAberto);
        $lines[] = 'Total a pagar (aberto): ' . $fmt($totalCpAberto);
        $lines[] = 'Saldo a receber - a pagar: ' . $fmt($totalCrAberto - $totalCpAberto);
        $lines[] = '';
        $lines[] = '=============================================================';
        $lines[] = '  Use este relatório para conferir com os relatórios do sistema.';
        $lines[] = '=============================================================';

        $report = implode("\n", $lines);
        $path = storage_path('app/relatorio_demo_data.txt');
        file_put_contents($path, $report);

        $this->command->newLine();
        $this->command->info($report);
        $this->command->newLine();
        $this->command->info("Relatório salvo em: $path");
    }
}
