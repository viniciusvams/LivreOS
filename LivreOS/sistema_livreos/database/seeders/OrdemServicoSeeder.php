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

use App\Models\Cliente;
use App\Models\OrdemServico;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;

class OrdemServicoSeeder extends Seeder
{
    public function run(): void
    {
        // Buscar clientes existentes
        $clientes = Cliente::limit(10)->get();
        
        if ($clientes->isEmpty()) {
            $this->command->warn('Nenhum cliente encontrado. Crie clientes primeiro antes de executar este seeder.');
            return;
        }

        // Buscar usuário para created_by
        $user = User::first();
        if (!$user) {
            $this->command->warn('Nenhum usuário encontrado. Crie um usuário primeiro.');
            return;
        }

        $tiposOs = ['Instalação', 'Manutenção', 'Reparo', 'Locação', 'Suporte'];
        $origensOs = ['App', 'Site', 'Telefone', 'Email', 'WhatsApp', 'Presencial'];
        $prioridades = ['Baixa', 'Normal', 'Alta', 'Urgente'];
        $status = ['Aberta', 'Em Andamento', 'Aguardando Cliente', 'Aguardando Peças', 'Concluída', 'Aprovado pelo cliente', 'Cancelada'];
        
        $equipamentosDemo = [
            ['nome' => 'Unidade IP dome', 'marca' => 'Hikvision', 'modelo' => 'DS-2CD2142FWD-I'],
            ['nome' => 'Unidade bullet IP', 'marca' => 'Dahua', 'modelo' => 'IPC-HDW2431T-AS'],
            ['nome' => 'DVR 8 Canais', 'marca' => 'Intelbras', 'modelo' => 'DVR 8008'],
            ['nome' => 'NVR 16 Canais', 'marca' => 'Hikvision', 'modelo' => 'DS-7616NI-I2'],
            ['nome' => 'Unidade PTZ', 'marca' => 'Dahua', 'modelo' => 'SD6AL433F-HN'],
            ['nome' => 'Unidade analógica', 'marca' => 'Intelbras', 'modelo' => 'ICM 2.1'],
            ['nome' => 'Switch PoE', 'marca' => 'TP-Link', 'modelo' => 'TL-SG1008P'],
            ['nome' => 'Fonte Alimentação', 'marca' => 'Intelbras', 'modelo' => 'Fonte 12V 5A'],
            ['nome' => 'Cabo de Rede', 'marca' => 'Genérico', 'modelo' => 'Cat5e 305m'],
            ['nome' => 'Cabo Coaxial', 'marca' => 'Genérico', 'modelo' => 'RG59 500m'],
        ];

        $relatos = [
            'Cliente relatou que o sistema de monitoramento parou de funcionar após queda de energia.',
            'Necessário instalação de novo sistema de monitoramento com 8 pontos.',
            'Manutenção preventiva do sistema de vídeo conforme contrato de manutenção.',
            'Cliente solicitou locação de equipamentos para evento temporário.',
            'Reparo necessário em unidade que apresentou falha na transmissão de imagem.',
            'Atualização do sistema com novas unidades de alta resolução.',
            'Instalação de sistema completo de segurança com vídeo e alarme.',
            'Cliente relatou problemas na visualização remota do sistema.',
            'Manutenção corretiva em DVR que apresentou falha no armazenamento.',
            'Instalação de pontos adicionais para cobertura de área externa.',
        ];

        $diagnosticos = [
            'Verificado problema na fonte de alimentação. Substituição realizada.',
            'Sistema instalado com sucesso. Todos os pontos funcionando corretamente.',
            'Realizada limpeza e verificação de todos os componentes do sistema.',
            'Equipamentos de locação instalados e configurados conforme solicitado.',
            'Unidade com defeito no sensor. Substituição realizada.',
            'Sistema atualizado com sucesso. Novas funcionalidades ativadas.',
            'Instalação completa realizada. Sistema testado e aprovado pelo cliente.',
            'Problema identificado na configuração de rede. Corrigido e testado.',
            'DVR substituído. Backup de configurações restaurado com sucesso.',
            'Pontos adicionais instalados. Sistema expandido conforme solicitado.',
        ];

        $servicosRealizados = [
            'Substituição de fonte de alimentação, teste de todos os pontos, verificação de gravações.',
            'Instalação de 8 pontos IP, configuração de DVR, teste de sistema completo.',
            'Limpeza de lentes, verificação de cabos, atualização de firmware, teste de gravação.',
            'Instalação de equipamentos de locação, configuração de acesso remoto, treinamento do cliente.',
            'Substituição de unidade defeituosa, ajuste de posicionamento, teste de transmissão.',
            'Atualização de firmware, instalação de novos pontos, configuração de sistema.',
            'Instalação completa de sistema de segurança, configuração de alarme, integração com vídeo.',
            'Correção de configuração de rede, teste de acesso remoto, verificação de conectividade.',
            'Substituição de DVR, restauração de backup, verificação de gravações antigas.',
            'Instalação de 4 pontos adicionais, expansão de sistema, teste de cobertura.',
        ];

        for ($i = 0; $i < 10; $i++) {
            $cliente = $clientes->random();
            $equipamento = $equipamentosDemo[$i];
            $statusSelecionado = $status[array_rand($status)];
            $dataAbertura = Carbon::now()->subDays(rand(0, 60));
            
            // Calcular datas baseadas no status
            $previstaInicio = $dataAbertura->copy()->addDays(rand(1, 3));
            $previstaConclusao = $previstaInicio->copy()->addDays(rand(1, 5));
            
            $realInicio = null;
            $realConclusao = null;
            if (in_array($statusSelecionado, ['Em Andamento', 'Concluída', 'Aprovado pelo cliente'])) {
                $realInicio = $previstaInicio->copy()->addHours(rand(0, 24));
            }
            if (in_array($statusSelecionado, ['Concluída', 'Aprovado pelo cliente'])) {
                $realConclusao = $previstaConclusao->copy()->addHours(rand(-12, 12));
            }

            // Calcular totais
            $totalProdutos = rand(10000, 50000) / 100; // Entre 100 e 500
            $totalServicos = rand(15000, 80000) / 100; // Entre 150 e 800
            $desconto = rand(0, 5000) / 100; // Entre 0 e 50
            $acrescimos = rand(0, 3000) / 100; // Entre 0 e 30
            $impostos = ($totalProdutos + $totalServicos) * 0.05; // 5% de impostos
            $totalGeral = $totalProdutos + $totalServicos - $desconto + $acrescimos + $impostos;

            $os = OrdemServico::create([
                'cliente_id' => $cliente->id,
                'tipo_os' => $tiposOs[array_rand($tiposOs)],
                'origem_os' => $origensOs[array_rand($origensOs)],
                'prioridade' => $prioridades[array_rand($prioridades)],
                'status' => $statusSelecionado,
                'data_abertura' => $dataAbertura,
                'prevista_inicio' => $previstaInicio,
                'prevista_conclusao' => $previstaConclusao,
                'real_inicio' => $realInicio,
                'real_conclusao' => $realConclusao,
                'sla_valor' => rand(24, 72),
                'sla_unidade' => 'horas',
                'sla_cumprido' => $realConclusao ? ($realConclusao <= $previstaConclusao) : null,
                'relato_cliente' => $relatos[$i],
                'diagnostico_tecnico' => $diagnosticos[$i],
                'servicos_realizados' => $servicosRealizados[$i],
                'observacoes_internas' => 'OS gerada automaticamente pelo seeder para testes.',
                'observacoes_cliente' => 'Cliente informado sobre o serviço realizado.',
                'garantia_dias' => rand(30, 180),
                'equipamento_nome' => $equipamento['nome'],
                'equipamento_marca' => $equipamento['marca'],
                'equipamento_modelo' => $equipamento['modelo'],
                'equipamento_numero_serie' => 'SN' . str_pad((string)rand(100000, 999999), 8, '0', STR_PAD_LEFT),
                'garantia_tipo' => rand(0, 1) ? 'fabricante' : 'prestador',
                'coberto_por_contrato' => rand(0, 1) == 1,
                'total_produtos' => $totalProdutos,
                'total_servicos' => $totalServicos,
                'desconto_global' => $desconto,
                'acrescimos_global' => $acrescimos,
                'impostos_global' => $impostos,
                'total_descontos' => $desconto,
                'total_acrescimos' => $acrescimos,
                'total_impostos' => $impostos,
                'total_geral' => $totalGeral,
                'estoque_baixado' => in_array($statusSelecionado, ['Concluída', 'Aprovado pelo cliente']),
                'financeiro_gerado' => in_array($statusSelecionado, ['Concluída', 'Aprovado pelo cliente']) && rand(0, 1) == 1,
                'aprovado_cliente' => $statusSelecionado === 'Aprovado pelo cliente',
                'created_by' => $user->id,
                'updated_by' => $user->id,
                'ip_criacao' => '127.0.0.1',
                'ip_atualizacao' => '127.0.0.1',
            ]);

            // O código interno é gerado automaticamente pelo boot method
            // Mas vamos garantir que está correto
            if (empty($os->codigo_interno)) {
                $os->gerarCodigoInterno();
                $os->save();
            }
        }

        $this->command->info('10 ordens de serviço criadas com sucesso!');
    }
}
