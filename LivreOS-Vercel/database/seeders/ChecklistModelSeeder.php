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

use App\Models\ChecklistModel;
use Illuminate\Database\Seeder;

class ChecklistModelSeeder extends Seeder
{
    public function run(): void
    {
        // Verificar se já existem modelos
        if (ChecklistModel::where('nome', 'Checklist de Manutenção de Informática')->exists()) {
            $this->command->info('Modelos de checklist já existem. Pulando criação...');
            return;
        }

        // Modelo 1: Checklist de Informática
        ChecklistModel::create([
            'nome' => 'Checklist de Manutenção de Informática',
            'descricao' => 'Checklist padrão para manutenção e reparo de equipamentos de informática',
            'campos' => [
                [
                    'label' => 'Tipo de Equipamento',
                    'tipo' => 'selecao',
                    'obrigatorio' => true,
                    'opcoes' => ['Desktop', 'Notebook', 'Servidor', 'Impressora', 'Monitor', 'Outro']
                ],
                [
                    'label' => 'Marca do Equipamento',
                    'tipo' => 'texto',
                    'obrigatorio' => true
                ],
                [
                    'label' => 'Modelo do Equipamento',
                    'tipo' => 'texto',
                    'obrigatorio' => true
                ],
                [
                    'label' => 'Número de Série',
                    'tipo' => 'texto',
                    'obrigatorio' => false
                ],
                [
                    'label' => 'Problema Relatado pelo Cliente',
                    'tipo' => 'texto',
                    'obrigatorio' => true
                ],
                [
                    'label' => 'Diagnóstico Técnico',
                    'tipo' => 'texto',
                    'obrigatorio' => true
                ],
                [
                    'label' => 'Sistema Operacional',
                    'tipo' => 'selecao',
                    'obrigatorio' => false,
                    'opcoes' => ['Windows 10', 'Windows 11', 'Windows 7', 'Linux', 'macOS', 'Não aplicável']
                ],
                [
                    'label' => 'Antivírus Instalado',
                    'tipo' => 'checkbox',
                    'obrigatorio' => false
                ],
                [
                    'label' => 'Backup Realizado',
                    'tipo' => 'checkbox',
                    'obrigatorio' => false
                ],
                [
                    'label' => 'Limpeza Física Realizada',
                    'tipo' => 'checkbox',
                    'obrigatorio' => false
                ],
                [
                    'label' => 'Pasta de Temperatura (CPU)',
                    'tipo' => 'texto',
                    'obrigatorio' => false
                ],
                [
                    'label' => 'Teste de Memória RAM',
                    'tipo' => 'selecao',
                    'obrigatorio' => false,
                    'opcoes' => ['OK', 'Com Erros', 'Não Testado']
                ],
                [
                    'label' => 'Teste de HD/SSD',
                    'tipo' => 'selecao',
                    'obrigatorio' => false,
                    'opcoes' => ['OK', 'Com Erros', 'Não Testado']
                ],
                [
                    'label' => 'Peças Substituídas',
                    'tipo' => 'texto',
                    'obrigatorio' => false
                ],
                [
                    'label' => 'Tempo de Atendimento (horas)',
                    'tipo' => 'numero',
                    'obrigatorio' => false
                ],
                [
                    'label' => 'Equipamento Testado e Funcionando',
                    'tipo' => 'checkbox',
                    'obrigatorio' => true
                ],
                [
                    'label' => 'Data do Teste',
                    'tipo' => 'data',
                    'obrigatorio' => false
                ]
            ],
            'ativo' => true,
            'created_by' => 1,
        ]);

        // Modelo 2: Checklist de vídeo / segurança eletrônica
        ChecklistModel::create([
            'nome' => 'Checklist de instalação e manutenção de vídeo',
            'descricao' => 'Checklist padrão para instalação e manutenção de sistemas de vídeo vigilância',
            'campos' => [
                [
                    'label' => 'Tipo de Serviço',
                    'tipo' => 'selecao',
                    'obrigatorio' => true,
                    'opcoes' => ['Instalação Nova', 'Manutenção', 'Expansão', 'Migração', 'Configuração']
                ],
                [
                    'label' => 'Quantidade de pontos de captura',
                    'tipo' => 'numero',
                    'obrigatorio' => true
                ],
                [
                    'label' => 'Tipo de dispositivo',
                    'tipo' => 'selecao',
                    'obrigatorio' => false,
                    'opcoes' => ['Dome', 'Bullet', 'PTZ', 'Cilíndrica', 'Mini', 'Mista']
                ],
                [
                    'label' => 'Resolução dos dispositivos',
                    'tipo' => 'selecao',
                    'obrigatorio' => false,
                    'opcoes' => ['720p', '1080p', '2MP', '4MP', '5MP', '8MP', 'Mista']
                ],
                [
                    'label' => 'Tipo de DVR/NVR',
                    'tipo' => 'selecao',
                    'obrigatorio' => false,
                    'opcoes' => ['DVR', 'NVR', 'Híbrido', 'Cloud', 'Não aplicável']
                ],
                [
                    'label' => 'Marca do DVR/NVR',
                    'tipo' => 'texto',
                    'obrigatorio' => false
                ],
                [
                    'label' => 'Modelo do DVR/NVR',
                    'tipo' => 'texto',
                    'obrigatorio' => false
                ],
                [
                    'label' => 'Capacidade do HD (TB)',
                    'tipo' => 'numero',
                    'obrigatorio' => false
                ],
                [
                    'label' => 'Tempo de Gravação Estimado (dias)',
                    'tipo' => 'numero',
                    'obrigatorio' => false
                ],
                [
                    'label' => 'Tipo de Conexão',
                    'tipo' => 'selecao',
                    'obrigatorio' => false,
                    'opcoes' => ['Cabo Coaxial', 'Cabo UTP', 'Wi-Fi', 'Fibra Óptica', 'Mista']
                ],
                [
                    'label' => 'Comprimento Total de Cabos (metros)',
                    'tipo' => 'numero',
                    'obrigatorio' => false
                ],
                [
                    'label' => 'Acesso Remoto Configurado',
                    'tipo' => 'checkbox',
                    'obrigatorio' => false
                ],
                [
                    'label' => 'App Mobile Instalado',
                    'tipo' => 'checkbox',
                    'obrigatorio' => false
                ],
                [
                    'label' => 'Notificação por Email Configurada',
                    'tipo' => 'checkbox',
                    'obrigatorio' => false
                ],
                [
                    'label' => 'Detecção de Movimento Ativada',
                    'tipo' => 'checkbox',
                    'obrigatorio' => false
                ],
                [
                    'label' => 'Infraestrutura de Rede Verificada',
                    'tipo' => 'checkbox',
                    'obrigatorio' => false
                ],
                [
                    'label' => 'Teste de todos os pontos',
                    'tipo' => 'selecao',
                    'obrigatorio' => true,
                    'opcoes' => ['Todas OK', 'Algumas com Problema', 'Não Testado']
                ],
                [
                    'label' => 'Qualidade de Imagem Verificada',
                    'tipo' => 'selecao',
                    'obrigatorio' => false,
                    'opcoes' => ['Excelente', 'Boa', 'Regular', 'Ruim']
                ],
                [
                    'label' => 'Gravação Funcionando',
                    'tipo' => 'checkbox',
                    'obrigatorio' => true
                ],
                [
                    'label' => 'Playback Testado',
                    'tipo' => 'checkbox',
                    'obrigatorio' => false
                ],
                [
                    'label' => 'Altura de instalação dos dispositivos (metros)',
                    'tipo' => 'numero',
                    'obrigatorio' => false
                ],
                [
                    'label' => 'Proteção contra Intempéries',
                    'tipo' => 'checkbox',
                    'obrigatorio' => false
                ],
                [
                    'label' => 'Iluminação do Local Verificada',
                    'tipo' => 'selecao',
                    'obrigatorio' => false,
                    'opcoes' => ['Suficiente', 'Insuficiente', 'Necessita Iluminação Auxiliar']
                ],
                [
                    'label' => 'Treinamento do Cliente Realizado',
                    'tipo' => 'checkbox',
                    'obrigatorio' => false
                ],
                [
                    'label' => 'Manual Entregue',
                    'tipo' => 'checkbox',
                    'obrigatorio' => false
                ],
                [
                    'label' => 'Data da Instalação/Manutenção',
                    'tipo' => 'data',
                    'obrigatorio' => true
                ],
                [
                    'label' => 'Tempo Total de Serviço (horas)',
                    'tipo' => 'numero',
                    'obrigatorio' => false
                ]
            ],
            'ativo' => true,
            'created_by' => 1,
        ]);

        $this->command->info('2 modelos de checklist criados com sucesso!');
        $this->command->info('- Checklist de Manutenção de Informática');
        $this->command->info('- Checklist de Instalação e Manutenção de CFTV');
    }
}
