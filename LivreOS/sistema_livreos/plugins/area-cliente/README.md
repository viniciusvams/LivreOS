# Área do Cliente

Oferece um portal para que o cliente visualize e acompanhe suas ordens de serviço online.

## Funcionalidades

- **Dashboard**: Lista todas as ordens do cliente com status, equipamento e totais
- **Filtros**: Por status e busca por código/equipamento/relato
- **Detalhes da OS**: Visualização completa (produtos, serviços, totais, datas)
- **Chamados (Tickets)**: O cliente pode abrir chamados com assunto, descrição, prioridade (Baixa/Média/Alta/Urgente) e anexo (foto ou PDF)
- **Suporte a grupo econômico**: Cliente vê OS da matriz, filiais e do grupo
- **Permissão de portal**: coluna `portal_suporte` em `clientes`; usuários com `cliente_id` em `users`

## Configuração

1. **Ative o plugin** em Configurações > Plugins
2. **Habilite o portal por cliente** em **Área do Cliente → Configuração** (`/plugin/area-cliente/configuracoes/clientes`): na coluna **Status**, use **Ativar** ou **Desativar** (use o filtro por **ID** se precisar de um cliente específico)
3. **Crie/edite um usuário** em Admin > Usuários e vincule ao cliente no campo "Cliente (Portal)", ou use **Gerenciar usuário** na mesma tela de configuração
4. O cliente acessa com email e senha e será redirecionado à área do cliente

## Migração

Na ativação o plugin:
- Adiciona `cliente_id` na tabela `users` (se não existir)
- Cria as tabelas `plugin_area_cliente_chamados` e `plugin_area_cliente_chamado_anexos` (se não existirem)

Se você atualizou o plugin e as tabelas de chamados ainda não existem, desative e ative novamente o plugin em Configurações > Plugins para rodar o hook de ativação.

## Rotas

- `/plugin/area-cliente` — Lista de ordens
- `/plugin/area-cliente/chamados` — Lista de chamados
- `/plugin/area-cliente/chamados/novo` — Abrir novo chamado
- `/plugin/area-cliente/chamados/{id}` — Detalhe do chamado
- `/plugin/area-cliente/os/{id}` — Detalhes da ordem
