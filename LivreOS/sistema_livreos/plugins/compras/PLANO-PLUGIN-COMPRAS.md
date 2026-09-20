# Plano do plugin Compras

## Objetivo

Implementar um módulo de compras no padrão do ERP LivreOS, aproveitando os cadastros já existentes de fornecedores e produtos, com fluxo de:

1. Cadastro/seleção de fornecedor
2. Criação de ordem de compra
3. Aprovação da ordem
4. Geração da ordem de compra para o fornecedor
5. Recebimento do pedido
6. Lançamento no estoque seguindo o fluxo já usado pelo sistema

---

## Padrão a seguir

O módulo deve respeitar a arquitetura já adotada no sistema:

- plugin em `plugins/compras`
- `plugin.php` como entry point
- `routes.php` com rotas do módulo
- `src/` para Models, Services e Controllers
- helpers globais do sistema (`add_action`, `add_filter`, `erp_view`, `erp_json`)
- uso de entidades já existentes do core, como `Contato`, `Produto`, `Cliente` e regras do estoque
- sem quebrar o fluxo principal do ERP

---

## Entidades e integrações do sistema

### 1) Fornecedores

Usar o cadastro atual de fornecedores do ERP, com base em `Contato` e `Cliente` já existentes no sistema.

Regras previstas:

- fornecedor = `Contato` com `is_fornecedor = true`
- relacionamento com `Cliente` já existente
- uso de `ProdutoFornecedor` para mapear código do fornecedor x produto
- seleção da empresa fornecedora pela tela do módulo

Arquivos relevantes do core:

- `app/Models/Contato.php`
- `app/Models/ProdutoFornecedor.php`
- `app/Http/Controllers/FornecedorController.php`
- `app/Models/Cliente.php`

### 2) Produtos

Usar os produtos já cadastrados em `Produto`.

Regras previstas:

- os itens da ordem de compra devem usar produtos do sistema
- o produto deve ter vínculo com fornecedor via `produto_fornecedores`
- o código do fornecedor deve ser persistido e usado na importação de ordem/compra
- ao receber mercadoria, o ajuste de estoque deve seguir o comportamento já implementado no ERP

Arquivos relevantes:

- `app/Models/Produto.php`
- `app/Models/ProdutoFornecedor.php`
- `app/Http/Controllers/ProdutoController.php`

### 3) Estoque

O recebimento da compra deve lançar entradas no estoque seguindo o mesmo fluxo do sistema atual, e não um mecanismo improvisado.

Regras esperadas:

- usar a mesma lógica de atualização de `estoque_quantidade`
- revisar se o sistema já usa campos como `estoque_quantidade`, `estoque_preco_custo_unitario`, `estoque_deposito_id`
- seguir as convenções já usadas em produtos e movimentação interna do ERP
- se houver helper de baixa/entrada, reaproveitar a mesma rotina

Arquivos relevantes:

- `app/Services/PedidoVendaEstoqueHelper.php`
- `app/Models/Produto.php`
- `app/Http/Controllers/OrdemServicoController.php` (pontos de atualização de estoque)

---

## Objetivo funcional do módulo

### Fluxo principal

1. Usuário acessa módulo de compras
2. Seleciona fornecedor cadastrado
3. Cria uma ordem de compra
4. Adiciona itens com produtos já existentes
5. Salva como rascunho ou envia para aprovação
6. Quando aprovada, gera a compra/ordem para o fornecedor
7. Quando a mercadoria chega, registra a entrada
8. O sistema atualiza estoque, custo e demais dados do produto
9. Se necessário, gera movimentação financeira/contas a pagar conforme o ERP

---

## Estrutura do plugin

### Estrutura inicial sugerida

```text
plugins/compras/
  plugin.php
  routes.php
  PLANO-PLUGIN-COMPRAS.md
  src/
    Models/
      OrdemCompra.php
      OrdemCompraItem.php
      RecebimentoCompra.php
      RecebimentoCompraItem.php
    Services/
      OrdemCompraService.php
      AprovarOrdemCompraService.php
      RecebimentoCompraService.php
      EstoqueCompraService.php
    Http/
      Controllers/
        ComprasController.php
    Support/
      StatusOrdemCompra.php
```

---

## Banco de dados sugerido

### Tabela de ordens de compra

`plugin_compras_ordens`

Campos principais:

- id
- fornecedor_id (FK para contatos/clientes)
- numero_ordem
- status (`rascunho`, `pendente_aprovacao`, `aprovada`, `rejeitada`, `emitida`, `recebida`, `finalizada`)
- data_emissao
- data_previsao
- data_aprovacao
- observacoes
- valor_total
- created_by
- approved_by
- timestamps

### Tabela de itens da ordem

`plugin_compras_ordens_itens`

Campos principais:

- id
- ordem_compra_id
- produto_id
- fornecedor_produto_codigo
- descricao
- quantidade
- unidade
- preco_unitario
- valor_total
- quantidade_recebida
- status_item
- timestamps

### Tabela de recebimentos

`plugin_compras_recebimentos`

Campos principais:

- id
- ordem_compra_id
- data_recebimento
- documento_referencia
- observacoes
- usuario_id
- timestamps

### Tabela de itens recebidos

`plugin_compras_recebimento_itens`

Campos principais:

- id
- recebimento_id
- ordem_item_id
- produto_id
- quantidade_recebida
- lote
- validade
- valor_unitario
- timestamps

---

## Fluxo detalhado

### Fase 1 — Cadastro e seleção do fornecedor

- listar fornecedores ativos do sistema
- permitir escolher fornecedor por nome/razão social ou CNPJ
- validar que o fornecedor é de fato um fornecedor
- manter vínculo com produtos por código do fornecedor

### Fase 2 — Criação da ordem de compra

- criar rascunho da ordem
- adicionar itens com produtos do catálogo do ERP
- validar estoque mínimo e quantidade do item
- salvar documento com totais e observações
- permitir enviar para aprovação

### Fase 3 — Aprovação da ordem

- status inicial: `pendente_aprovacao`
- regra: somente usuário autorizado pode aprovar/rejeitar
- ao aprovar: gerar documento de compra/ordem para o fornecedor
- ao rejeitar: registrar motivo e retornar para rascunho ou status rejeitado

### Fase 4 — Geração da ordem do fornecedor

- gerar uma ordem de compra como documento interno/externo no módulo
- manter vínculo com o fornecedor e itens selecionados
- permitir imprimir/exportar em PDF ou visualizar para envio ao fornecedor

### Fase 5 — Recebimento da compra

- ao receber a mercadoria, registrar entrada da compra
- informar quantidade recebida, lote/opcional e valores
- validar contra a quantidade solicitada
- registrar histórico de recebimento por item

### Fase 6 — Lançamento no estoque

- ao confirmar recebimento, chamar o fluxo existente do ERP para entrada em estoque
- atualizar `Produto::estoque_quantidade`
- ajustar custo unitário conforme política do sistema
- manter controle do histórico de movimentação
- se houver fluxo já implementado para entradas, reaproveitar

### Fase 7 — Contabilidade/financeiro

- após o recebimento, preparar integração com contas a pagar
- uso do fluxo de fornecedores/contas a pagar já existente no sistema
- manter compatibilidade com a estrutura atual do ERP

---

## Regras do estoque

A entrada de compra deve seguir o mesmo comportamento do ERP, ou seja:

- não criar lógica isolada para estoque sem reaproveitar os pontos do sistema
- atualizar a quantidade do produto em estoque
- recalcular custo médio ou custo de aquisição, conforme regra do sistema
- validar se o produto tem controle de estoque ativo
- bloquear ou avisar se houver inconsistência

Se existir alguma rotina central de entrada/saída de estoque, este módulo deve chamar esse serviço ao invés de duplicar lógica.

---

## Fluxo de aprovação e status

```text
rascunho
  -> pendente_aprovacao
      -> aprovada
      -> rejeitada

aprovada
  -> emitida
      -> recebida
          -> finalizada
```

Também pode haver:

- `cancelada`
- `parcialmente_recebida`
- `atrasada`

---

## Etapas de implementação

### Etapa 1 — Mapeamento de entidades e regras

- confirmar como o ERP representa fornecedores e produtos
- validar campos de estoque e custo
- definir status da ordem de compra

### Etapa 2 — Criação dos models do plugin

- `OrdemCompra`
- `OrdemCompraItem`
- `RecebimentoCompra`
- `RecebimentoCompraItem`

### Etapa 3 — Migrations e seed de apoio

- criar tabelas no plugin
- inicializar permissões do módulo
- criar status iniciais e registros de apoio, se necessário

### Etapa 4 — Serviços de negócio

- `OrdemCompraService`
- `AprovarOrdemCompraService`
- `RecebimentoCompraService`
- `EstoqueCompraService`

### Etapa 5 — Views e rotas

- listagem de ordens
- criação/edição da ordem
- aprovação
- geração da ordem para fornecedor
- tela de recebimento
- dashboard do módulo

### Etapa 6 — Entrada no estoque

- disparar processo de entrada do ERP
- atualizar `estoque_quantidade`
- revisar custo e histórico

### Etapa 7 — Integração financeira

- preparar vínculo com contas a pagar
- permitir ligação com contas do fornecedor

### Etapa 8 — Testes e validação

- criação de ordem com produto existente
- aprovação da ordem
- entrada parcial e total
- rastreamento de estoque
- rejeição e cancelamento

---

## Arquivos e pontos de atenção para a implementação real

### Core que deve ser reutilizado

- `app/Models/Contato.php`
- `app/Models/Produto.php`
- `app/Models/ProdutoFornecedor.php`
- `app/Models/Cliente.php`
- `app/Services/PedidoVendaEstoqueHelper.php`
- `app/Models/ContaPagar.php`
- `app/Http/Controllers/FornecedorController.php`
- `app/Http/Controllers/ProdutoController.php`

### Plugin que será montado

- `plugins/compras/plugin.php`
- `plugins/compras/routes.php`
- `plugins/compras/src/Models/*`
- `plugins/compras/src/Services/*`
- `plugins/compras/src/Http/Controllers/ComprasController.php`

---

## Observações importantes

- a solução precisa respeitar o ERP e não inventar um fluxo paralelo
- o melhor caminho é reaproveitar o cadastro de fornecedor e produto já existentes
- o estoque deve ser atualizado pelo mesmo mecanismo do sistema já usado em outras operações
- a aprovação deve ser tratada como gate de negócio e não como simples flag
- o módulo deve gerar um histórico claro e rastreável para cada etapa

---

## Próximo passo sugerido

A partir deste plano, o próximo passo será:

1. criar a estrutura inicial do plugin em `src/Models` e `src/Services`
2. montar as migrations do módulo
3. implementar `OrdemCompra` e `OrdemCompraItem`
4. criar os endpoints de criação e aprovação
5. integrar o recebimento com a lógica de estoque do ERP
