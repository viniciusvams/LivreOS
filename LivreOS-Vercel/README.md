# LivreOS — Sistema de Ordem de Serviço ERP Open Source

**LivreOS** é um sistema de gestão (ERP) open source gratuito com módulo completo de 
**Ordem de Serviço (OS)**, PDV, financeiro, estoque e clientes.  
Desenvolvido para MEI, microempresas e pequenas empresas brasileiras.  
Licença AGPL 3.0 — sem mensalidade, sem limitações.

[![Stars](https://img.shields.io/github/stars/viniciusvams/LivreOS?style=social)](https://github.com/viniciusvams/LivreOS)
[![License](https://img.shields.io/badge/license-AGPL--3.0-green)](LICENSE)
[![PHP](https://img.shields.io/badge/PHP-8.2+-blue)](https://php.net)
[![Laravel](https://img.shields.io/badge/Laravel-11-red)](https://laravel.com)

## 🚀 Deploy para Vercel (Gratuito)

O LivreOS é totalmente compatível com a **Vercel**. Como a Vercel não fornece um banco de dados MySQL nativo, recomendamos o uso de um provedor externo gratuito como o **[Aiven](https://aiven.io/mysql)** ou **[TiDB Serverless](https://www.pingcap.com/tidb-serverless/)**.

[![Deploy with Vercel](https://vercel.com/button)](https://vercel.com/new/clone?repository-url=https://github.com/viniciusvams/LivreOS/tree/main/LivreOS-Vercel&env=DB_HOST,DB_PORT,DB_DATABASE,DB_USERNAME,DB_PASSWORD,APP_KEY,SETUP_TOKEN&envDescription=Preencha%20os%20dados%20do%20seu%20banco%20MySQL%20externo%20e%20gere%20uma%20APP_KEY%20(php%20artisan%20key:generate%20--show))

### Passos após o Deploy:
Após a conclusão do deploy na Vercel, você precisa criar as tabelas no seu banco de dados externo:
1. Acesse: `https://seu-projeto.vercel.app/setup-database?token=SEU_SETUP_TOKEN` (use o token que você definiu nas variáveis de ambiente).
2. O sistema irá configurar o banco de dados automaticamente e você poderá fazer o login!

---

🚀 Apresentação: LivreOS Beta 1.0
O LivreOS é um sistema ERP de código aberto projetado para ser seguro e fácil de instalar, mesmo em ambientes de hospedagem sem acesso ao terminal (SSH). Para garantir a segurança dos seus dados, o sistema utiliza uma estrutura de pastas profissional:

public_html: Contém apenas os arquivos acessíveis pelo navegador (imagens, CSS, JS).

sistema_livreos: Contém o "cérebro" do sistema, protegido fora da área pública do servidor.

📖 Guia de Instalação (Hospedagem Compartilhada)
1. Preparação dos Arquivos
Faça o upload via FTP ou Gerenciador de Arquivos do seu host seguindo esta estrutura:

Suba o conteúdo da pasta public_html para o diretório raiz do seu site (geralmente chamado de public_html, www ou httpdocs).

Suba a pasta sistema_livreos um nível acima da pasta pública.

Exemplo visual:

Plaintext
/home/usuario/
├── sistema_livreos/  <-- (Pasta do Sistema)
└── public_html/      <-- (Pasta Pública onde o site abre)
2. Acessando o Instalador
Abra o seu navegador e acesse o endereço do seu site. Você será recebido pelo Instalador LivreOS. O sistema verificará se o seu servidor possui o PHP >= 8.2 necessário para o funcionamento.

3. Configuração de Caminhos (Etapa 2)
Nesta fase, você deve informar ao sistema onde os arquivos estão fisicamente no servidor.

Caminho da Pasta Pública: É o endereço absoluto onde está o seu site.

Exemplo: /home/usuario/public_html

Caminho da Pasta do Sistema: É onde você colocou a pasta com o arquivo artisan.

Exemplo: /home/usuario/sistema_livreos

Dica Legal: O instalador tentará detectar esses caminhos automaticamente para você. Se você usar nomes de pastas diferentes, basta corrigir nos campos indicados.

4. Configuração do Banco de Dados (Etapa 3)
Você pode escolher entre duas opções de banco de dados:

SQLite: Ideal para quem quer testar rápido. O sistema cria um arquivo de banco de dados automaticamente dentro da pasta do sistema.

MySQL: Recomendado para produção. Você precisará criar um banco de dados e um usuário no painel do seu host (cPanel/Plesk) e informar os dados (Host, Nome do Banco, Usuário e Senha).

5. Finalização e Segurança
Após clicar em "Executar Instalação", o sistema criará as tabelas e configurará os acessos.

Acesso Padrão: * E-mail: admin@admin.com

Senha: password

⚖️ Aviso Jurídico Importante
Segurança Pós-Instalação: Conforme indicado no instalador, após validar que o sistema está funcionando, apague a pasta install ou bloqueie o acesso a ela via .htaccess. Deixar o instalador ativo permite que terceiros tentem reinstalar o sistema e apagar seus dados.

Ao utilizar o LivreOS, você concorda com os termos da licença AGPLv3, que garante a liberdade do software enquanto protege o acesso ao código-fonte para a comunidade.



# LivreOS
ERP Open Source Livre

# LivreOS

**LivreOS** — ERP Open Source Livre

Sistema de gestão com ordens de serviço, financeiro, clientes, produtos, serviços e módulo de plugins.

- **Site:** [https://www.livreos.com.br](https://www.livreos.com.br)
- **Licença:** [GNU AGPL v3.0](LICENSE) — texto integral no repositório e em [https://www.gnu.org/licenses/agpl-3.0.txt](https://www.gnu.org/licenses/agpl-3.0.txt)

## Autor e copyright

**viniciusvams**

```
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
```

---

## 📋 Módulo: Ordem de Serviço (OS)

### Visão Geral

O módulo de Ordem de Serviço é o núcleo operacional do LivreOS. Ele centraliza todo o ciclo de atendimento ao cliente: abertura, acompanhamento, encerramento e integração automática com estoque e financeiro.

---

## 🗂️ Tela de Listagem (Index)

### Como acessar
Acesse **Menu → Ordens de Serviço** para ver a lista de todas as OS.

### Filtros disponíveis

| Filtro | Descrição |
|---|---|
| **Status** | Filtra por status (ex: Aberta, Em Andamento, Encerrada) |
| **Cliente** | Filtra pelo cliente vinculado |
| **Código** | Busca pelo código interno da OS |
| **Data de abertura** | Intervalo de datas (data início / data fim) |
| **Prioridade** | baixa, normal, alta ou crítica |
| **Técnico** | Filtra OS onde o técnico está alocado como responsável ou em serviços |
| **SLA** | Cumprido, não cumprido, com SLA ou sem SLA |
| **Tag** | Filtra por tags personalizadas |
| **Busca geral** | Pesquisa em código, relato do cliente, diagnóstico, serviços realizados, observações, equipamento (marca, modelo, série, patrimônio) e nome/CPF/CNPJ do cliente |

### Ordenação de colunas
Clique no cabeçalho de qualquer coluna para ordenar: **Código, Cliente, Status, Prioridade, Abertura, Conclusão, Total**.

### Paginação
Escolha quantos itens exibir por página: 5, 10, 15, 25, 50 ou 100.

### Exportação
- **CSV** — Botão "Exportar CSV": gera arquivo com até 10.000 OS, respeitando os filtros ativos. Inclui: Código, Cliente, Status, Prioridade, Abertura, Conclusão e Total.
- **PDF** — Botão "Exportar PDF": gera relatório em A4 paisagem com até 500 OS.

---

## ➕ Criar uma OS — Passo a Passo

1. Clique em **+ Nova OS** na tela de listagem.
2. Preencha as abas na sequência (o formulário é dividido em etapas/abas):

### Aba 1 — Dados Básicos
- **Cliente**: pesquise pelo nome, CPF ou CNPJ. O campo usa busca em tempo real.
- **Filial/Unidade** *(opcional)*: se o cliente possuir filiais cadastradas, selecione qual unidade.
- **Contato** e **Endereço**: preenchidos automaticamente após selecionar o cliente.
- **Tipo de OS**: ex. Manutenção, Instalação, Suporte etc.
- **Origem**: como chegou a OS (telefone, e-mail, presencial etc.).
- **Status**: estado inicial (padrão: Aberta).
- **Prioridade**: baixa / normal / alta / crítica.
- **Data de abertura**: preenchida automaticamente com a data/hora atual.

### Aba 2 — Equipamento
- **Vincular equipamento do cadastro**: clique em "Buscar equipamento" para ligar a OS a um equipamento já cadastrado. Marca, modelo, série e patrimônio são preenchidos automaticamente.
- **Informar manualmente**: preencha diretamente os campos de marca, modelo, número de série, patrimônio e acessórios.
- **Desbloqueio** *(opcional)*: tipo de desbloqueio (PIN, senha, padrão/pattern) e código correspondente.

### Aba 3 — Descrição
- **Relato do cliente**: o que o cliente relatou (texto rico).
- **Diagnóstico técnico**: diagnóstico realizado pelo técnico.
- **Serviços realizados**: descrição dos serviços executados.
- **Observações internas**: visíveis apenas internamente.
- **Observações para o cliente**: aparecem na impressão e no e-mail enviado ao cliente.

### Aba 4 — Itens (Produtos e Serviços)

**Adicionar Produto:**
1. Clique em **+ Produto**.
2. Selecione o produto do cadastro ou preencha a descrição avulsa (requer permissão `ordem_servico.produto_servico_avulso`).
3. Informe quantidade, valor unitário.
4. Aplique desconto em **valor (R$)** ou **percentual (%)** — sujeito ao limite de desconto do seu perfil.
5. Tributos/impostos são calculados automaticamente conforme configuração do produto.

**Adicionar Serviço:**
1. Clique em **+ Serviço**.
2. Selecione o serviço ou preencha avulso.
3. Informe o tipo de cobrança: **por unidade** ou **por horas**.
4. Informe quantidade ou horas trabalhadas.
5. Informe valor unitário e desconto.
6. Vincule um **técnico responsável** pelo serviço (opcional).

**Desconto global:** campo ao final dos itens. Aplica-se sobre o total geral.

**Acréscimos e impostos globais:** campos específicos para taxas adicionais.

> ⚠️ O sistema bloqueia a venda se o estoque do produto estiver zerado (conforme configuração). Usuários com permissão `produtos.vender_estoque_zero` podem ignorar o bloqueio.

### Aba 5 — Equipe e Apontamentos
- **Adicionar técnico**: vincule um ou mais técnicos responsáveis pela OS.
- **Apontamentos de horas**: registre intervalos de início/fim de trabalho por técnico (data/hora inicio → data/hora fim).

### Aba 6 — Prazos e SLA
- **Prevista inicio / Prevista conclusão**: datas estimadas.
- **Real inicio / Real conclusão**: preenchidas automaticamente quando o status muda para um que "marca início" ou "marca conclusão" (configurável em Cadastros → Status OS).
- **SLA**: valor + unidade (horas, dias). O sistema calcula automaticamente se o SLA foi cumprido ao encerrar a OS.

### Aba 7 — Outras Informações
- **Garantia**: dias de garantia e tipo (sem garantia, dentro da garantia, garantia estendida).
- **Termo de garantia**: selecione um modelo de termo cadastrado.
- **Contrato**: número, tipo, se é coberto por contrato, se é recorrente e periodicidade.
- **Aprovação**: registre se o cliente aprovou o orçamento (nome, data, canal — presencial, e-mail, WhatsApp etc.).
- **Quilometragem / Horímetro**: campos para veículos e equipamentos com horímetro.
- **Tags**: adicione tags personalizadas para classificar a OS.

3. Clique em **Salvar** para criar a OS.

---

## ✏️ Editar uma OS

1. Na listagem, clique no ícone de **editar (lápis)** ou clique no código da OS.
2. Todas as abas da criação ficam disponíveis para edição.
3. O sistema bloqueia a edição de OS com status **Encerrada**.
4. Cada salvamento registra automaticamente no **Histórico/Log** os campos alterados (antes x depois).

### Alterar Status rapidamente
Na tela de edição, use o seletor de status no topo para alterar sem precisar salvar o formulário inteiro. O sistema aciona automaticamente:
- Preenchimento de `real_inicio` (se o status "marca início").
- Preenchimento de `real_conclusao` + cálculo do SLA (se o status "marca conclusão").
- Baixa automática de estoque e geração de conta a receber no financeiro (se o status marca conclusão).
- Envio de notificação por **e-mail** e/ou **WhatsApp** para o cliente (conforme configuração).

---

## 📦 Anexos

### Adicionar anexos
1. Na aba **Anexos** da OS, clique em **Selecionar arquivos**.
2. Selecione um ou mais arquivos (limite: 10 MB por arquivo).
3. Para cada arquivo, informe: **tipo** (foto, documento etc.), **tags** e **descrição** (opcional).
4. Clique em **Salvar anexos**.

### Gerenciar anexos
- **Visualizar**: clique no nome do arquivo para abrir/baixar.
- **Editar metadados**: clique no ícone de editar do anexo para alterar tipo, tags e descrição.
- **Excluir**: clique no ícone de lixeira. Não é possível excluir anexos de OS encerradas.

> Imagens (jpg, png, gif, webp etc.) são exibidas como miniatura na listagem de anexos.

---

## 🏷️ Tags

Tags permitem classificar as OS com rótulos coloridos personalizados.

### Aplicar tags em uma OS
- Na aba de edição da OS, seção **Tags**, marque as tags desejadas e salve.

### Aplicar tags em massa
1. Na listagem, marque as OS desejadas com o checkbox.
2. No painel **Ações em Massa**, selecione as tags.
3. Clique em **Aplicar**.

> Tags do tipo `padrão` (como "Pago" e "Pagamento Pendente") são controladas automaticamente pelo sistema e **não** podem ser adicionadas manualmente em massa.

---

## 🔄 Status em Massa

1. Na listagem, marque as OS desejadas.
2. No painel **Ações em Massa**, escolha o novo status e/ou prioridade.
3. Clique em **Aplicar**.

**Regras:**
- OS encerradas são ignoradas e contabilizadas separadamente.
- Status "Encerrada" e "Cancelada" **não** podem ser aplicados em massa — use os botões específicos de Encerrar/Cancelar na OS individual.
- O log de cada OS alterada é registrado automaticamente.

---

## 🔁 Duplicar uma OS

1. Na tela de detalhes ou edição da OS, clique em **Duplicar**.
2. O sistema cria uma nova OS com status **Aberta**, copiando:
   - Dados do cliente (cliente, unidade, contato, endereço)
   - Equipamento
   - Relato, diagnóstico, serviços realizados, observações
   - Todos os produtos e serviços (com quantidades e valores)
   - Tipo, origem e prioridade
3. Você é redirecionado para a tela de edição da nova OS para ajustar o que for necessário.

---

## 💳 Adiantamento de Pagamento

Registre pagamentos parciais **antes** do encerramento da OS.

### Como registrar um adiantamento
1. Na tela de edição da OS, clique em **Adiantamento**.
2. Informe a data e adicione uma ou mais formas de pagamento:
   - **Dinheiro/PIX**: baixa automática com conciliação imediata.
   - **Cartão de crédito**: informar adquirente, bandeira e parcelas. O sistema simula as taxas e cria uma conta a receber para cada parcela com previsão de recebimento conforme agenda do adquirente.
   - **Cartão de débito**: informar adquirente e bandeira. Baixa automática.
   - **Transferência/Boleto**: cria conta a receber com status **aberto** aguardando confirmação manual no financeiro.
3. O valor total dos adiantamentos não pode ultrapassar o valor da OS.
4. Clique em **Registrar adiantamento**.

### Estornar um adiantamento
1. Na seção de adiantamentos da OS, clique em **Estornar** no adiantamento desejado.
2. Informe o motivo (mínimo 10 caracteres).
3. **Se o adiantamento estava aberto**: é cancelado.
4. **Se o adiantamento estava pago**: a baixa é estornada e uma **Conta a Pagar** de devolução é gerada automaticamente para o cliente.

---

## ✅ Encerrar uma OS — Passo a Passo

1. Na OS, clique no botão **Encerrar**.
2. O modal de encerramento abre com o resumo financeiro.
3. Aplique **desconto de encerramento** (valor ou percentual) se necessário — sujeito ao limite do seu perfil.
4. Selecione o tipo de pagamento:
   - **Pagar agora**: adicione as formas de pagamento (mesmo fluxo do adiantamento).
   - **Faturar**: gera conta a receber com vencimento em 7 dias para cobrança posterior.
5. Se já houver adiantamentos confirmados, o valor restante é calculado automaticamente.
6. Clique em **Encerrar OS**.

**O que acontece ao encerrar:**
- Status muda para **Encerrada**.
- `real_conclusao` é preenchida automaticamente.
- SLA é calculado e registrado (`sla_cumprido`).
- **Estoque é baixado** automaticamente para todos os produtos da OS (produto unitário, variação ou kit/composição).
- **Conta a receber** é criada no financeiro.
- Log de encerramento é registrado.
- Notificação de e-mail e/ou WhatsApp é enviada ao cliente (conforme configuração).

> ⚠️ OS encerradas **não podem ser editadas**. Para corrigir, é necessário duplicar a OS ou reabrir (se a regra do sistema permitir).

---

## 🖨️ Impressão

### Imprimir OS
1. Na OS, clique em **Imprimir**.
2. A página de impressão inclui dados do cliente, equipamento, serviços realizados, produtos, totais e espaço para assinatura.
3. É possível configurar uma **observação antes da assinatura** em Configurações → OS → Impressão.

### Etiqueta do equipamento
1. Na OS, clique em **Etiqueta**.
2. Gera uma etiqueta com QR Code que aponta para a página pública do equipamento.
3. O cliente pode escanear o QR para consultar o status da OS sem precisar fazer login.

---

## 📧 Notificações ao Cliente

### Enviar e-mail manualmente
1. Na OS, clique em **Notificar → E-mail**.
2. Preencha o e-mail de destino, assunto e mensagem (opcionais — o sistema usa o template configurado).
3. Clique em **Enviar**. O envio é processado em fila (não é instantâneo, aguarde alguns instantes).

### Enviar WhatsApp
1. Na OS, clique em **Notificar → WhatsApp**.
2. **Modo automático**: a mensagem é enviada via API configurada.
3. **Modo manual**: o sistema exibe o texto formatado para você copiar e enviar manualmente pelo WhatsApp.

### Notificações automáticas na mudança de status
Configure em **Configurações → Notificações OS** para enviar e-mail e/ou WhatsApp automaticamente sempre que o status da OS for alterado.

---

## 📝 Checklist

1. Na aba **Checklist** da OS, clique em **+ Adicionar Checklist**.
2. Selecione um modelo de checklist cadastrado em Cadastros → Checklists.
3. Preencha as respostas dos itens do checklist.
4. Salve a OS.

---

## 📜 Histórico / Log

Todo evento da OS é registrado automaticamente:
- Criação, edição (campos alterados antes x depois), encerramento, cancelamento, exclusão.
- Alterações de status, prioridade e tags.
- Adição/remoção de anexos.
- Alterações de itens (produtos/serviços), equipe e apontamentos.
- Acesso às telas de edição e detalhes (quem acessou e quando).

Para visualizar o histórico, abra a OS e vá até a aba **Histórico**. Requer permissão `view-os-history`.

---

## 🗑️ Excluir uma OS

1. Na OS, clique em **Excluir**.
2. Informe o **motivo** da exclusão (mínimo 10 caracteres).
3. Todos os anexos físicos são removidos do armazenamento.
4. A exclusão é auditada (registrada no log de auditoria do sistema).

> Requer permissão `ordem_servico.excluir`.

---

## 🔌 Extensibilidade (Hooks/Plugins)

O módulo expõe hooks para plugins:

| Hook | Momento |
|---|---|
| `ordens_servico.store.data` | Antes de salvar os dados na criação (filter) |
| `ordens_servico.stored` | Após criação da OS (action) |
| `ordens_servico.update.data` | Antes de salvar os dados na edição (filter) |
| `ordens_servico.updated` | Após atualização da OS (action) |
| `ordens_servico.status.changing` | Antes de mudar o status (action) |
| `ordens_servico.status.changed` | Após mudar o status (action) |

---

## ⚙️ Configurações Relacionadas

Acesse **Configurações** para ajustar o comportamento do módulo:

- **Status OS** (Cadastros → Status OS): crie status personalizados, defina quais "marcam início" e quais "marcam conclusão".
- **Notificações OS**: templates de e-mail e WhatsApp, modo de envio (automático/manual), gatilhos de disparo.
- **Limite de desconto**: defina o percentual/valor máximo de desconto por perfil de usuário.
- **Estoque**: se deve bloquear venda com estoque zero.
- **Financeiro OS**: plano de contas e centro de custo padrão para integração ao encerrar.
- **Impressão OS**: observação customizada antes da assinatura na impressão.

---

## Licença

Este projeto (código desenvolvido pelo LivreOS) está sob a **GNU Affero General Public License v3.0** (AGPL-3.0). Veja o arquivo [LICENSE](LICENSE) para o aviso de copyright do projeto e o texto integral da licença.

Bibliotecas de terceiros (por exemplo em `vendor/` e `node_modules/`) permanecem sob as respetivas licenças.
