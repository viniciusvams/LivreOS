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

use App\Models\CategoriaProduto;
use App\Models\Cliente;
use App\Models\Contato;
use App\Models\Deposito;
use App\Models\Produto;
use App\Models\ProdutoComposicao;
use App\Models\ProdutoFornecedor;
use App\Models\ProdutoImagem;
use App\Models\TabelaPreco;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;

class ProdutoSeeder extends Seeder
{
    public function run(): void
    {
        $usuario = User::query()->first();

        $categorias = collect([
            'Vídeo IP',
            'Vídeo analógico',
            'Gravadores',
            'Acessórios',
            'Fontes e Energia',
            'Redes',
            'Alarmes',
        ])->map(function ($nome) {
            return CategoriaProduto::firstOrCreate(
                ['slug' => Str::slug($nome)],
                ['nome' => $nome, 'ativo' => true]
            );
        });

        $deposito = Deposito::firstOrCreate(
            ['nome' => 'Depósito Central'],
            ['localizacao' => 'Rua Edelweiss,251', 'ativo' => true]
        );

        $clienteFornecedorA = Cliente::firstOrCreate(
            ['cnpj' => '99.999.999/0001-99'],
            [
                'tipo_pessoa' => 'J',
                'nome' => 'Empresa de Teste Alpha',
                'razao_social' => 'Teste de Sistema e Integração Ltda ME',
                'ramo_atividade' => 'Distribuição de equipamentos eletrônicos',
                'classificacao_rating' => 3,
                'produtor_rural' => false,
            ]
        );
        $clienteFornecedorB = Cliente::firstOrCreate(
            ['cnpj' => '99.999.999/0001-98'],
            [
                'tipo_pessoa' => 'J',
                'nome' => 'Lorem Ipsum Segurança',
                'razao_social' => 'Lorem Ipsum Distribuidora de Eletrônicos S.A.',
                'ramo_atividade' => 'Distribuição de segurança eletrônica',
                'classificacao_rating' => 2,
                'produtor_rural' => false,
            ]
        );

        $fornecedores = [
            Contato::firstOrCreate(
                ['cliente_id' => $clienteFornecedorA->id, 'email' => 'acm@edsffdfdsfsdfdsfsd.com.br'],
                [
                    'nome' => 'ACME Equipamentos',
                    'telefone' => '(11) 3333-3333',
                    'cargo' => 'Comercial',
                    'principal' => true,
                    'cobranca' => false,
                ]
            ),
            Contato::firstOrCreate(
                ['cliente_id' => $clienteFornecedorB->id, 'email' => 'comercial@acmesddsffdsdfsfdfd.com.br'],
                [
                    'nome' => 'ACME Equipamentos',
                    'telefone' => '(11) 6666-6666',
                    'cargo' => 'Vendas',
                    'principal' => false,
                    'cobranca' => false,
                ]
            ),
        ];

        $tabelas = TabelaPreco::query()->orderBy('nome')->get();
        if ($tabelas->isEmpty()) {
            $tabelas = collect([
                TabelaPreco::create(['nome' => 'Varejo', 'descricao' => 'Preço de balcão', 'ativo' => true]),
                TabelaPreco::create(['nome' => 'Atacado', 'descricao' => 'Preço para revenda', 'ativo' => true]),
            ]);
        }

        Produto::unguard();

        $produtosBase = [
            [
                'nome' => 'Unidade IP dome 2MP PoE',
                'codigo_sku' => 'CAM-IP-DOME-2MP-POE',
                'preco_venda' => 389.90,
                'unidade' => 'UN',
                'formato' => 'simples',
                'tipo' => 'produto',
                'condicao' => 'novo',
                'linha_produto' => 'Linha Vision IP',
                'categoria' => 'Vídeo IP',
                'marca' => 'VisionTech',
                'producao' => 'terceiros',
                'data_validade' => Carbon::now()->addYears(3)->toDateString(),
                'frete_gratis' => true,
                'peso_liquido' => 0.45,
                'peso_bruto' => 0.6,
                'largura' => 11.2,
                'altura' => 9.0,
                'profundidade' => 11.2,
                'volumes' => 1,
                'itens_por_caixa' => 10,
                'ean' => '7891234567895',
                'ean_tributario' => '7891234567895',
                'descricao_curta' => 'Dome IP 2MP com PoE e IR 30m',
                'descricao_complementar' => 'Unidade IP com lente 2.8mm, WDR digital e proteção IP67.',
                'link_externo' => 'https://example.com/cam-ip-dome-2mp',
                'video_url' => 'https://example.com/video-cam-ip-dome',
                'observacoes' => 'Compatível com NVRs ONVIF.',
                'tags' => ['camera', 'ip', 'poe', 'dome'],
                'estoque_minimo' => 5,
                'estoque_maximo' => 80,
                'estoque_crossdocking' => 7,
                'estoque_localizacao' => 'A1-01',
                'estoque_quantidade' => 34.000000,
                'estoque_preco_compra_unitario' => 240.00,
                'estoque_preco_custo_unitario' => 260.00,
                'estoque_observacao' => 'Lote 2026/01',
                'tributacao_origem' => '0',
                'tributacao_ncm' => '85258029',
                'tributacao_cest' => '2109400',
                'tributacao_tipo_item' => '01',
                'tributacao_percentual_tributos' => 18.50,
                'tributacao_grupo_produtos' => 'Eletrônicos',
                'tributacao_info_adicionais' => 'Produto sujeito à substituição tributária.',
                'tributacao_icms' => ['aliquota' => 18, 'cst' => '00'],
                'tributacao_pis_cofins' => ['pis' => 1.65, 'cofins' => 7.6],
                'imagens' => [
                    ['tipo' => 'url', 'url' => 'https://example.com/img/cam-ip-dome-1.jpg', 'ordem' => 0],
                    ['tipo' => 'url', 'url' => 'https://example.com/img/cam-ip-dome-2.jpg', 'ordem' => 1],
                ],
            ],
            [
                'nome' => 'Unidade bullet Full HD 1080p IR',
                'codigo_sku' => 'CAM-BULLET-1080P',
                'preco_venda' => 219.90,
                'unidade' => 'UN',
                'formato' => 'simples',
                'tipo' => 'produto',
                'condicao' => 'novo',
                'linha_produto' => 'Linha Secure Analog',
                'categoria' => 'Vídeo analógico',
                'marca' => 'SecureCam',
                'producao' => 'terceiros',
                'data_validade' => Carbon::now()->addYears(2)->toDateString(),
                'frete_gratis' => false,
                'peso_liquido' => 0.38,
                'peso_bruto' => 0.5,
                'largura' => 6.8,
                'altura' => 6.5,
                'profundidade' => 17.0,
                'volumes' => 1,
                'itens_por_caixa' => 20,
                'ean' => '7891234567896',
                'ean_tributario' => '7891234567896',
                'descricao_curta' => 'Bullet 1080p com IR 30m',
                'descricao_complementar' => 'Compatível com DVRs AHD/HDCVI/HDTVI.',
                'link_externo' => 'https://example.com/cam-bullet-1080p',
                'video_url' => 'https://example.com/video-cam-bullet',
                'observacoes' => 'Usar fonte 12V 1A.',
                'tags' => ['camera', 'bullet', 'analoga'],
                'estoque_minimo' => 10,
                'estoque_maximo' => 120,
                'estoque_crossdocking' => 5,
                'estoque_localizacao' => 'A1-02',
                'estoque_quantidade' => 58.000000,
                'estoque_preco_compra_unitario' => 120.00,
                'estoque_preco_custo_unitario' => 140.00,
                'estoque_observacao' => 'Controle de lotes mensal.',
                'tributacao_origem' => '0',
                'tributacao_ncm' => '85258029',
                'tributacao_cest' => '2109400',
                'tributacao_tipo_item' => '01',
                'tributacao_percentual_tributos' => 18.50,
                'tributacao_grupo_produtos' => 'Eletrônicos',
                'tributacao_info_adicionais' => 'Venda com garantia de 12 meses.',
                'tributacao_icms' => ['aliquota' => 18, 'cst' => '00'],
                'tributacao_pis_cofins' => ['pis' => 1.65, 'cofins' => 7.6],
                'imagens' => [
                    ['tipo' => 'url', 'url' => 'https://example.com/img/cam-bullet-1.jpg', 'ordem' => 0],
                ],
            ],
            [
                'nome' => 'NVR 16 Canais H.265',
                'codigo_sku' => 'NVR-16CH-H265',
                'preco_venda' => 1299.90,
                'unidade' => 'UN',
                'formato' => 'simples',
                'tipo' => 'produto',
                'condicao' => 'novo',
                'linha_produto' => 'Linha Vision IP',
                'categoria' => 'Gravadores',
                'marca' => 'VisionTech',
                'producao' => 'terceiros',
                'data_validade' => Carbon::now()->addYears(3)->toDateString(),
                'frete_gratis' => true,
                'peso_liquido' => 2.1,
                'peso_bruto' => 2.6,
                'largura' => 32.0,
                'altura' => 4.8,
                'profundidade' => 24.0,
                'volumes' => 1,
                'itens_por_caixa' => 5,
                'ean' => '7891234567897',
                'ean_tributario' => '7891234567897',
                'descricao_curta' => 'NVR 16 canais com compressão H.265',
                'descricao_complementar' => 'Suporta até 8MP, acesso remoto e detecção inteligente.',
                'link_externo' => 'https://example.com/nvr-16ch',
                'video_url' => 'https://example.com/video-nvr-16ch',
                'observacoes' => 'Não acompanha HDD.',
                'tags' => ['nvr', 'gravador', 'ip'],
                'estoque_minimo' => 2,
                'estoque_maximo' => 30,
                'estoque_crossdocking' => 10,
                'estoque_localizacao' => 'B2-01',
                'estoque_quantidade' => 12.000000,
                'estoque_preco_compra_unitario' => 880.00,
                'estoque_preco_custo_unitario' => 930.00,
                'estoque_observacao' => 'Último firmware 2026.1.',
                'tributacao_origem' => '0',
                'tributacao_ncm' => '85219090',
                'tributacao_cest' => '2109400',
                'tributacao_tipo_item' => '01',
                'tributacao_percentual_tributos' => 17.00,
                'tributacao_grupo_produtos' => 'Eletrônicos',
                'tributacao_info_adicionais' => 'Equipamento eletrônico.',
                'tributacao_icms' => ['aliquota' => 18, 'cst' => '00'],
                'tributacao_pis_cofins' => ['pis' => 1.65, 'cofins' => 7.6],
                'imagens' => [
                    ['tipo' => 'url', 'url' => 'https://example.com/img/nvr-16ch-1.jpg', 'ordem' => 0],
                ],
            ],
            [
                'nome' => 'DVR 8 Canais 5MP',
                'codigo_sku' => 'DVR-8CH-5MP',
                'preco_venda' => 799.90,
                'unidade' => 'UN',
                'formato' => 'simples',
                'tipo' => 'produto',
                'condicao' => 'novo',
                'linha_produto' => 'Linha Secure Analog',
                'categoria' => 'Gravadores',
                'marca' => 'SecureCam',
                'producao' => 'terceiros',
                'data_validade' => Carbon::now()->addYears(3)->toDateString(),
                'frete_gratis' => false,
                'peso_liquido' => 1.8,
                'peso_bruto' => 2.2,
                'largura' => 31.0,
                'altura' => 4.5,
                'profundidade' => 22.0,
                'volumes' => 1,
                'itens_por_caixa' => 6,
                'ean' => '7891234567898',
                'ean_tributario' => '7891234567898',
                'descricao_curta' => 'DVR 8 canais com gravação 5MP',
                'descricao_complementar' => 'Compatível com AHD/TVI/CVI/CVBS.',
                'link_externo' => 'https://example.com/dvr-8ch',
                'video_url' => 'https://example.com/video-dvr-8ch',
                'observacoes' => 'Fonte inclusa.',
                'tags' => ['dvr', 'gravador', 'ahd'],
                'estoque_minimo' => 3,
                'estoque_maximo' => 40,
                'estoque_crossdocking' => 8,
                'estoque_localizacao' => 'B2-02',
                'estoque_quantidade' => 18.000000,
                'estoque_preco_compra_unitario' => 540.00,
                'estoque_preco_custo_unitario' => 590.00,
                'estoque_observacao' => 'Compatível com HD.',
                'tributacao_origem' => '0',
                'tributacao_ncm' => '85219090',
                'tributacao_cest' => '2109400',
                'tributacao_tipo_item' => '01',
                'tributacao_percentual_tributos' => 17.00,
                'tributacao_grupo_produtos' => 'Eletrônicos',
                'tributacao_info_adicionais' => 'Equipamento eletrônico.',
                'tributacao_icms' => ['aliquota' => 18, 'cst' => '00'],
                'tributacao_pis_cofins' => ['pis' => 1.65, 'cofins' => 7.6],
                'imagens' => [
                    ['tipo' => 'url', 'url' => 'https://example.com/img/dvr-8ch-1.jpg', 'ordem' => 0],
                ],
            ],
            [
                'nome' => 'Fonte 12V 5A Bivolt',
                'codigo_sku' => 'FONTE-12V-5A',
                'preco_venda' => 89.90,
                'unidade' => 'UN',
                'formato' => 'simples',
                'tipo' => 'produto',
                'condicao' => 'novo',
                'linha_produto' => 'Linha Power',
                'categoria' => 'Fontes e Energia',
                'marca' => 'PowerSafe',
                'producao' => 'terceiros',
                'data_validade' => Carbon::now()->addYears(2)->toDateString(),
                'frete_gratis' => false,
                'peso_liquido' => 0.55,
                'peso_bruto' => 0.7,
                'largura' => 9.0,
                'altura' => 4.0,
                'profundidade' => 14.0,
                'volumes' => 1,
                'itens_por_caixa' => 30,
                'ean' => '7891234567899',
                'ean_tributario' => '7891234567899',
                'descricao_curta' => 'Fonte 12V 5A com proteção',
                'descricao_complementar' => 'Bivolt automático e proteção contra surto.',
                'link_externo' => 'https://example.com/fonte-12v-5a',
                'video_url' => 'https://example.com/video-fonte-12v',
                'observacoes' => 'Ideal para até 4 pontos de captura.',
                'tags' => ['fonte', 'energia', '12v'],
                'estoque_minimo' => 10,
                'estoque_maximo' => 200,
                'estoque_crossdocking' => 4,
                'estoque_localizacao' => 'C3-01',
                'estoque_quantidade' => 75.000000,
                'estoque_preco_compra_unitario' => 48.00,
                'estoque_preco_custo_unitario' => 55.00,
                'estoque_observacao' => 'Modelo bivolt automático.',
                'tributacao_origem' => '0',
                'tributacao_ncm' => '85044090',
                'tributacao_cest' => '2109400',
                'tributacao_tipo_item' => '01',
                'tributacao_percentual_tributos' => 15.00,
                'tributacao_grupo_produtos' => 'Energia',
                'tributacao_info_adicionais' => 'Fonte chaveada.',
                'tributacao_icms' => ['aliquota' => 18, 'cst' => '00'],
                'tributacao_pis_cofins' => ['pis' => 1.65, 'cofins' => 7.6],
                'imagens' => [
                    ['tipo' => 'url', 'url' => 'https://example.com/img/fonte-12v-5a.jpg', 'ordem' => 0],
                ],
            ],
            [
                'nome' => 'Fonte 12V 10A Bivolt',
                'codigo_sku' => 'FONTE-12V-10A',
                'preco_venda' => 139.90,
                'unidade' => 'UN',
                'formato' => 'simples',
                'tipo' => 'produto',
                'condicao' => 'novo',
                'linha_produto' => 'Linha Power',
                'categoria' => 'Fontes e Energia',
                'marca' => 'PowerSafe',
                'producao' => 'terceiros',
                'data_validade' => Carbon::now()->addYears(2)->toDateString(),
                'frete_gratis' => false,
                'peso_liquido' => 0.7,
                'peso_bruto' => 0.9,
                'largura' => 10.0,
                'altura' => 4.5,
                'profundidade' => 15.0,
                'volumes' => 1,
                'itens_por_caixa' => 20,
                'ean' => '7891234567800',
                'ean_tributario' => '7891234567800',
                'descricao_curta' => 'Fonte 12V 10A com proteção',
                'descricao_complementar' => 'Capacidade para até 8 pontos de captura.',
                'link_externo' => 'https://example.com/fonte-12v-10a',
                'video_url' => 'https://example.com/video-fonte-12v-10a',
                'observacoes' => 'Instalar em local ventilado.',
                'tags' => ['fonte', 'energia', '12v'],
                'estoque_minimo' => 8,
                'estoque_maximo' => 120,
                'estoque_crossdocking' => 4,
                'estoque_localizacao' => 'C3-02',
                'estoque_quantidade' => 42.000000,
                'estoque_preco_compra_unitario' => 78.00,
                'estoque_preco_custo_unitario' => 90.00,
                'estoque_observacao' => 'Fonte com proteção térmica.',
                'tributacao_origem' => '0',
                'tributacao_ncm' => '85044090',
                'tributacao_cest' => '2109400',
                'tributacao_tipo_item' => '01',
                'tributacao_percentual_tributos' => 15.00,
                'tributacao_grupo_produtos' => 'Energia',
                'tributacao_info_adicionais' => 'Fonte chaveada.',
                'tributacao_icms' => ['aliquota' => 18, 'cst' => '00'],
                'tributacao_pis_cofins' => ['pis' => 1.65, 'cofins' => 7.6],
                'imagens' => [
                    ['tipo' => 'url', 'url' => 'https://example.com/img/fonte-12v-10a.jpg', 'ordem' => 0],
                ],
            ],
            [
                'nome' => 'Cabo Coaxial RG59 100m',
                'codigo_sku' => 'CABO-RG59-100M',
                'preco_venda' => 279.90,
                'unidade' => 'ROLO',
                'formato' => 'simples',
                'tipo' => 'produto',
                'condicao' => 'novo',
                'linha_produto' => 'Linha Cabos',
                'categoria' => 'Acessórios',
                'marca' => 'CablePro',
                'producao' => 'terceiros',
                'data_validade' => Carbon::now()->addYears(5)->toDateString(),
                'frete_gratis' => false,
                'peso_liquido' => 5.2,
                'peso_bruto' => 5.8,
                'largura' => 28.0,
                'altura' => 12.0,
                'profundidade' => 28.0,
                'volumes' => 1,
                'itens_por_caixa' => 4,
                'ean' => '7891234567801',
                'ean_tributario' => '7891234567801',
                'descricao_curta' => 'Cabo RG59 com dupla blindagem',
                'descricao_complementar' => 'Ideal para vídeo analógico e HD.',
                'link_externo' => 'https://example.com/cabo-rg59',
                'video_url' => 'https://example.com/video-cabo-rg59',
                'observacoes' => 'Bobina com 100 metros.',
                'tags' => ['cabo', 'rg59', 'acessorio'],
                'estoque_minimo' => 4,
                'estoque_maximo' => 60,
                'estoque_crossdocking' => 6,
                'estoque_localizacao' => 'D1-01',
                'estoque_quantidade' => 26.000000,
                'estoque_preco_compra_unitario' => 190.00,
                'estoque_preco_custo_unitario' => 210.00,
                'estoque_observacao' => 'Usar conectores BNC.',
                'tributacao_origem' => '0',
                'tributacao_ncm' => '85442000',
                'tributacao_cest' => '2109400',
                'tributacao_tipo_item' => '01',
                'tributacao_percentual_tributos' => 12.00,
                'tributacao_grupo_produtos' => 'Cabos',
                'tributacao_info_adicionais' => 'Produto para instalação.',
                'tributacao_icms' => ['aliquota' => 18, 'cst' => '00'],
                'tributacao_pis_cofins' => ['pis' => 1.65, 'cofins' => 7.6],
                'imagens' => [
                    ['tipo' => 'url', 'url' => 'https://example.com/img/cabo-rg59.jpg', 'ordem' => 0],
                ],
            ],
            [
                'nome' => 'Switch PoE 8 Portas 120W',
                'codigo_sku' => 'SW-POE-8P-120W',
                'preco_venda' => 549.90,
                'unidade' => 'UN',
                'formato' => 'simples',
                'tipo' => 'produto',
                'condicao' => 'novo',
                'linha_produto' => 'Linha Redes',
                'categoria' => 'Redes',
                'marca' => 'NetSecure',
                'producao' => 'terceiros',
                'data_validade' => Carbon::now()->addYears(3)->toDateString(),
                'frete_gratis' => true,
                'peso_liquido' => 1.4,
                'peso_bruto' => 1.8,
                'largura' => 20.0,
                'altura' => 4.5,
                'profundidade' => 12.0,
                'volumes' => 1,
                'itens_por_caixa' => 8,
                'ean' => '7891234567802',
                'ean_tributario' => '7891234567802',
                'descricao_curta' => 'Switch PoE 8 portas com 120W',
                'descricao_complementar' => 'Ideal para dispositivos IP em rede e access points.',
                'link_externo' => 'https://example.com/switch-poe-8p',
                'video_url' => 'https://example.com/video-switch-poe',
                'observacoes' => 'Gerenciamento via web.',
                'tags' => ['switch', 'poe', 'rede'],
                'estoque_minimo' => 3,
                'estoque_maximo' => 40,
                'estoque_crossdocking' => 6,
                'estoque_localizacao' => 'E2-01',
                'estoque_quantidade' => 16.000000,
                'estoque_preco_compra_unitario' => 360.00,
                'estoque_preco_custo_unitario' => 390.00,
                'estoque_observacao' => 'Compatível com IEEE 802.3af/at.',
                'tributacao_origem' => '0',
                'tributacao_ncm' => '85176249',
                'tributacao_cest' => '2109400',
                'tributacao_tipo_item' => '01',
                'tributacao_percentual_tributos' => 14.00,
                'tributacao_grupo_produtos' => 'Redes',
                'tributacao_info_adicionais' => 'Equipamento de rede.',
                'tributacao_icms' => ['aliquota' => 18, 'cst' => '00'],
                'tributacao_pis_cofins' => ['pis' => 1.65, 'cofins' => 7.6],
                'imagens' => [
                    ['tipo' => 'url', 'url' => 'https://example.com/img/switch-poe-8p.jpg', 'ordem' => 0],
                ],
            ],
            [
                'nome' => 'Detector de Movimento Infravermelho',
                'codigo_sku' => 'DET-MOV-IR',
                'preco_venda' => 69.90,
                'unidade' => 'UN',
                'formato' => 'simples',
                'tipo' => 'produto',
                'condicao' => 'novo',
                'linha_produto' => 'Linha Alarmes',
                'categoria' => 'Alarmes',
                'marca' => 'AlarmSafe',
                'producao' => 'terceiros',
                'data_validade' => Carbon::now()->addYears(3)->toDateString(),
                'frete_gratis' => false,
                'peso_liquido' => 0.12,
                'peso_bruto' => 0.18,
                'largura' => 6.0,
                'altura' => 10.0,
                'profundidade' => 4.0,
                'volumes' => 1,
                'itens_por_caixa' => 50,
                'ean' => '7891234567803',
                'ean_tributario' => '7891234567803',
                'descricao_curta' => 'Sensor PIR com alcance de 12m',
                'descricao_complementar' => 'Imune a animais domésticos até 20kg.',
                'link_externo' => 'https://example.com/detector-mov',
                'video_url' => 'https://example.com/video-detector-mov',
                'observacoes' => 'Recomendado para áreas internas.',
                'tags' => ['alarme', 'sensor', 'pir'],
                'estoque_minimo' => 10,
                'estoque_maximo' => 200,
                'estoque_crossdocking' => 5,
                'estoque_localizacao' => 'F1-01',
                'estoque_quantidade' => 90.000000,
                'estoque_preco_compra_unitario' => 38.00,
                'estoque_preco_custo_unitario' => 45.00,
                'estoque_observacao' => 'Lote 2026/02.',
                'tributacao_origem' => '0',
                'tributacao_ncm' => '85311090',
                'tributacao_cest' => '2109400',
                'tributacao_tipo_item' => '01',
                'tributacao_percentual_tributos' => 12.00,
                'tributacao_grupo_produtos' => 'Alarmes',
                'tributacao_info_adicionais' => 'Sensor de segurança.',
                'tributacao_icms' => ['aliquota' => 18, 'cst' => '00'],
                'tributacao_pis_cofins' => ['pis' => 1.65, 'cofins' => 7.6],
                'imagens' => [
                    ['tipo' => 'url', 'url' => 'https://example.com/img/detector-mov.jpg', 'ordem' => 0],
                ],
            ],
            [
                'nome' => 'Sirene Piezoelétrica 120dB',
                'codigo_sku' => 'SIRENE-120DB',
                'preco_venda' => 59.90,
                'unidade' => 'UN',
                'formato' => 'simples',
                'tipo' => 'produto',
                'condicao' => 'novo',
                'linha_produto' => 'Linha Alarmes',
                'categoria' => 'Alarmes',
                'marca' => 'AlarmSafe',
                'producao' => 'terceiros',
                'data_validade' => Carbon::now()->addYears(3)->toDateString(),
                'frete_gratis' => false,
                'peso_liquido' => 0.22,
                'peso_bruto' => 0.28,
                'largura' => 10.0,
                'altura' => 10.0,
                'profundidade' => 7.0,
                'volumes' => 1,
                'itens_por_caixa' => 40,
                'ean' => '7891234567804',
                'ean_tributario' => '7891234567804',
                'descricao_curta' => 'Sirene externa 120dB',
                'descricao_complementar' => 'Carcaça resistente e fácil instalação.',
                'link_externo' => 'https://example.com/sirene-120db',
                'video_url' => 'https://example.com/video-sirene',
                'observacoes' => 'Uso externo.',
                'tags' => ['alarme', 'sirene'],
                'estoque_minimo' => 8,
                'estoque_maximo' => 150,
                'estoque_crossdocking' => 5,
                'estoque_localizacao' => 'F1-02',
                'estoque_quantidade' => 60.000000,
                'estoque_preco_compra_unitario' => 32.00,
                'estoque_preco_custo_unitario' => 38.00,
                'estoque_observacao' => 'Modelo com proteção UV.',
                'tributacao_origem' => '0',
                'tributacao_ncm' => '85311090',
                'tributacao_cest' => '2109400',
                'tributacao_tipo_item' => '01',
                'tributacao_percentual_tributos' => 12.00,
                'tributacao_grupo_produtos' => 'Alarmes',
                'tributacao_info_adicionais' => 'Sirene de segurança.',
                'tributacao_icms' => ['aliquota' => 18, 'cst' => '00'],
                'tributacao_pis_cofins' => ['pis' => 1.65, 'cofins' => 7.6],
                'imagens' => [
                    ['tipo' => 'url', 'url' => 'https://example.com/img/sirene-120db.jpg', 'ordem' => 0],
                ],
            ],
        ];

        $produtosCriados = [];
        foreach ($produtosBase as $dados) {
            $categoria = $categorias->firstWhere('nome', $dados['categoria']);
            $payload = Arr::except($dados, ['categoria', 'imagens']);
            $produto = Produto::firstOrCreate(
                ['codigo_sku' => $dados['codigo_sku']],
                array_merge($payload, [
                    'categoria_id' => $categoria?->id,
                    'estoque_deposito_id' => $deposito->id,
                    'created_by' => $usuario?->id,
                    'updated_by' => $usuario?->id,
                ])
            );

            $produto->tabelasPrecos()->sync($tabelas->mapWithKeys(function ($tabela) use ($produto) {
                $ajuste = $tabela->nome === 'Atacado' ? -20 : 0;
                return [$tabela->id => ['preco' => max(0, ($produto->preco_venda ?? 0) + $ajuste)]];
            })->all());

            ProdutoFornecedor::firstOrCreate(
                ['produto_id' => $produto->id, 'fornecedor_id' => $fornecedores[0]->id],
                [
                    'descricao' => 'Fornecedor principal',
                    'codigo_fornecedor' => 'FOR-' . Str::upper(Str::random(6)),
                    'preco_compra' => $produto->estoque_preco_compra_unitario,
                    'preco_custo' => $produto->estoque_preco_custo_unitario,
                    'garantia_meses' => 12,
                ]
            );

            if (isset($dados['imagens'])) {
                foreach ($dados['imagens'] as $img) {
                    ProdutoImagem::firstOrCreate(
                        ['produto_id' => $produto->id, 'url_externa' => $img['url']],
                        [
                            'tipo' => $img['tipo'],
                            'caminho_local' => null,
                            'ordem' => $img['ordem'] ?? 0,
                        ]
                    );
                }
            }

            $produtosCriados[$produto->codigo_sku] = $produto;
        }

        $kits = [
            [
                'nome' => 'Kit vídeo IP 4 pontos + NVR',
                'codigo_sku' => 'KIT-IP-4CAM-NVR',
                'preco_venda' => 2899.90,
                'unidade' => 'KIT',
                'formato' => 'composicao',
                'tipo' => 'produto',
                'condicao' => 'novo',
                'linha_produto' => 'Kits Profissionais',
                'categoria' => 'Vídeo IP',
                'descricao_curta' => 'Kit IP com 4 unidades dome 2MP e NVR 16 canais',
                'descricao_complementar' => 'Ideal para pequenos comércios, com acesso remoto.',
                'observacoes' => 'Não acompanha HDD.',
                'tags' => ['kit', 'video', 'ip'],
                'componentes' => [
                    ['sku' => 'CAM-IP-DOME-2MP-POE', 'quantidade' => 4],
                    ['sku' => 'NVR-16CH-H265', 'quantidade' => 1],
                    ['sku' => 'SW-POE-8P-120W', 'quantidade' => 1],
                ],
            ],
            [
                'nome' => 'Kit vídeo analógico 4 pontos + DVR',
                'codigo_sku' => 'KIT-ANALOG-4CAM-DVR',
                'preco_venda' => 1699.90,
                'unidade' => 'KIT',
                'formato' => 'composicao',
                'tipo' => 'produto',
                'condicao' => 'novo',
                'linha_produto' => 'Kits Profissionais',
                'categoria' => 'Vídeo analógico',
                'descricao_curta' => 'Kit analógico com 4 unidades bullet e DVR 8 canais',
                'descricao_complementar' => 'Solução econômica para vigilância.',
                'observacoes' => 'Inclui fonte 12V 5A.',
                'tags' => ['kit', 'video', 'analogico'],
                'componentes' => [
                    ['sku' => 'CAM-BULLET-1080P', 'quantidade' => 4],
                    ['sku' => 'DVR-8CH-5MP', 'quantidade' => 1],
                    ['sku' => 'FONTE-12V-5A', 'quantidade' => 1],
                    ['sku' => 'CABO-RG59-100M', 'quantidade' => 1],
                ],
            ],
            [
                'nome' => 'Kit Alarmes Residencial',
                'codigo_sku' => 'KIT-ALARME-RES',
                'preco_venda' => 499.90,
                'unidade' => 'KIT',
                'formato' => 'composicao',
                'tipo' => 'produto',
                'condicao' => 'novo',
                'linha_produto' => 'Kits Alarmes',
                'categoria' => 'Alarmes',
                'descricao_curta' => 'Kit com sensores e sirene',
                'descricao_complementar' => 'Ideal para ambientes internos.',
                'observacoes' => 'Central não inclusa.',
                'tags' => ['kit', 'alarme', 'residencial'],
                'componentes' => [
                    ['sku' => 'DET-MOV-IR', 'quantidade' => 2],
                    ['sku' => 'SIRENE-120DB', 'quantidade' => 1],
                ],
            ],
            [
                'nome' => 'Kit vídeo IP 8 pontos + NVR',
                'codigo_sku' => 'KIT-IP-8CAM-NVR',
                'preco_venda' => 4899.90,
                'unidade' => 'KIT',
                'formato' => 'composicao',
                'tipo' => 'produto',
                'condicao' => 'novo',
                'linha_produto' => 'Kits Profissionais',
                'categoria' => 'Vídeo IP',
                'descricao_curta' => 'Kit IP com 8 pontos e NVR 16 canais',
                'descricao_complementar' => 'Para comércios com maior área.',
                'observacoes' => 'Switch PoE incluso.',
                'tags' => ['kit', 'video', 'ip'],
                'componentes' => [
                    ['sku' => 'CAM-IP-DOME-2MP-POE', 'quantidade' => 8],
                    ['sku' => 'NVR-16CH-H265', 'quantidade' => 1],
                    ['sku' => 'SW-POE-8P-120W', 'quantidade' => 1],
                    ['sku' => 'FONTE-12V-10A', 'quantidade' => 1],
                ],
            ],
            [
                'nome' => 'Kit instalação vídeo básico',
                'codigo_sku' => 'KIT-INST-VIDEO-BAS',
                'preco_venda' => 349.90,
                'unidade' => 'KIT',
                'formato' => 'composicao',
                'tipo' => 'produto',
                'condicao' => 'novo',
                'linha_produto' => 'Kits Acessórios',
                'categoria' => 'Acessórios',
                'descricao_curta' => 'Kit de acessórios para instalação',
                'descricao_complementar' => 'Cabo RG59 e fonte para até 4 pontos.',
                'observacoes' => 'Ideal para instalações pequenas.',
                'tags' => ['kit', 'instalacao', 'acessorios'],
                'componentes' => [
                    ['sku' => 'CABO-RG59-100M', 'quantidade' => 1],
                    ['sku' => 'FONTE-12V-5A', 'quantidade' => 1],
                ],
            ],
        ];

        foreach ($kits as $kit) {
            $categoria = $categorias->firstWhere('nome', $kit['categoria']);
            $payload = Arr::except($kit, ['categoria', 'componentes']);
            $produtoKit = Produto::firstOrCreate(
                ['codigo_sku' => $kit['codigo_sku']],
                array_merge($payload, [
                    'categoria_id' => $categoria?->id,
                    'estoque_deposito_id' => $deposito->id,
                    'created_by' => $usuario?->id,
                    'updated_by' => $usuario?->id,
                    'frete_gratis' => true,
                    'estoque_quantidade' => 5,
                    'estoque_preco_compra_unitario' => 0,
                    'estoque_preco_custo_unitario' => 0,
                    'tributacao_origem' => '0',
                    'tributacao_ncm' => '85258029',
                    'tributacao_cest' => '2109400',
                    'tributacao_tipo_item' => '01',
                    'tributacao_percentual_tributos' => 18.00,
                    'tributacao_grupo_produtos' => 'Kits',
                    'tributacao_info_adicionais' => 'Kit promocional.',
                    'tributacao_icms' => ['aliquota' => 18, 'cst' => '00'],
                    'tributacao_pis_cofins' => ['pis' => 1.65, 'cofins' => 7.6],
                ])
            );

            $produtoKit->tabelasPrecos()->sync($tabelas->mapWithKeys(function ($tabela) use ($produtoKit) {
                $ajuste = $tabela->nome === 'Atacado' ? -50 : 0;
                return [$tabela->id => ['preco' => max(0, ($produtoKit->preco_venda ?? 0) + $ajuste)]];
            })->all());

            foreach ($kit['componentes'] as $comp) {
                $componente = $produtosCriados[$comp['sku']] ?? null;
                if (!$componente) {
                    continue;
                }
                ProdutoComposicao::firstOrCreate(
                    [
                        'produto_id' => $produtoKit->id,
                        'componente_id' => $componente->id,
                    ],
                    [
                        'quantidade' => $comp['quantidade'],
                        'custo_unitario' => $componente->estoque_preco_custo_unitario,
                        'custo_total' => $componente->estoque_preco_custo_unitario * $comp['quantidade'],
                    ]
                );
            }
        }

        Produto::reguard();
    }
}
