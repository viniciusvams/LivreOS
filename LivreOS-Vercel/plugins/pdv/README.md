# Plugin PDV

Ponto de Venda integrado ao ERP, **totalmente isolado** do sistema principal (rotas e tabelas no plugin).

## Funcionalidades

- **Abertura de caixa**: valor de fundo obrigatório ao iniciar.
- **Reforço (Suprimento)**: entrada de dinheiro vinculada ao plano de contas (ex.: Aporte de Caixa).
- **Sangria**: retirada com justificativa obrigatória; gera comprovante e abate do saldo.
- **Fechamento cego**: operador informa o valor contado; o sistema compara com o saldo e gera relatório de quebra de caixa.
- **Checkout**: bipagem por código de barras/EAN, pesquisa por nome (F2), carrinho com itens, desconto.
- **Múltiplos pagamentos**: N formas de pagamento por venda (tabela `plugin_pdv_venda_pagamentos`).
- **Orçamentos**: salvar orçamento, listar e importar orçamento para a tela para finalizar venda.
- **Produtos e serviços**: venda de itens cadastrados no sistema (leitura dos modelos do core).
- **Serial**: opção por produto/categoria para exigir número de série (IMEI/SN); garantia no recibo via configuração do plugin.

## Tabelas (prefixo `plugin_pdv_`)

- `plugin_pdv_caixas` – sessões de caixa (abertura/fechamento).
- `plugin_pdv_caixa_movimentacoes` – abertura, reforço, sangria, fechamento.
- `plugin_pdv_vendas` – vendas e totais.
- `plugin_pdv_venda_itens` – itens da venda (produto/serviço, quantidade, preço, serial, kit_componentes).
- `plugin_pdv_venda_pagamentos` – múltiplos pagamentos por venda.
- `plugin_pdv_orcamentos` – orçamentos salvos.
- `plugin_pdv_orcamento_itens` – itens do orçamento.

## Configurações (opções do plugin)

- `plano_conta_aporte_id` – ID da conta do plano de contas para “Aporte de Caixa” (reforço).
- `plano_conta_estorno_pdv_id` – ID da conta do plano de contas (tipo despesa) para o lançamento em Contas a Pagar ao cancelar uma venda no PDV. Se não configurado, o título é criado sem plano de conta (pode ser definido ao editar a conta a pagar).
- `categorias_com_serial` – array de IDs de categorias que exigem serial.
- `produtos_com_serial` – array de IDs de produtos que exigem serial.

## Rotas (todas sob `/plugin/pdv`)

- `GET /` – tela do PDV.
- `GET /api/caixa/status` – status do caixa atual.
- `POST /api/caixa/abrir` – abrir caixa (valor_fundo).
- `POST /api/caixa/reforco` – reforço (valor, plano_conta_id).
- `POST /api/caixa/sangria` – sangria (valor, justificativa).
- `POST /api/caixa/fechar` – fechamento cego (valor_informado).
- `GET /api/busca/produtos`, `GET /api/busca/servicos`, `GET /api/busca/clientes`.
- `GET /api/formas-pagamento`, `GET /api/plano-contas-aporte`.
- `GET/POST /api/vendas`, `GET /api/vendas/{id}`.
- `POST /api/vendas/{id}/cancelar` – cancelamento **total** (`tipo: total`) ou **parcial** (`tipo: parcial`, `itens: [{ item_id, quantidade_cancelar }]`). Gera conta a pagar de estorno, estorna estoque (produtos) na quantidade cancelada; desconto da venda é recalculado proporcionalmente no parcial. Permissões: `pdv.cancelar_venda_total`, `pdv.cancelar_venda_parcial` (papéis) ou flags por usuário nas configurações do plugin; administrador (`is_admin`) pode sempre.
- `GET/POST /api/orcamentos`, `GET /api/orcamentos/{id}`, `POST /api/orcamentos/{id}/importar-venda`.

## Atalhos

- **F2** – abrir pesquisa de produto/serviço.
- **F3** – finalizar venda (com carrinho preenchido).

## Próximos passos (evolução)

- Baixa de estoque por lote/kit (explosão de kits usando `ProdutoComposicao`).
- Integração financeira (contas a receber/taxas ao finalizar venda).
- Impressão térmica (58mm/80mm) e termo de garantia com seriais no rodapé.
- PDV offline (LocalStorage/IndexedDB + sincronização ao reconectar).
