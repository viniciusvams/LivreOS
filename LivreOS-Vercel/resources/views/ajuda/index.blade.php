{{--
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
 --}}

@extends('layouts.app')

@section('content')
<div class="mx-auto max-w-4xl">
    <div class="mb-8">
        <h1 class="mb-2 text-3xl font-bold text-gray-800 dark:text-white/90">Central de Ajuda</h1>
        <p class="text-lg text-gray-600 dark:text-gray-400">Tudo o que você precisa para usar o sistema, explicado de forma simples.</p>
    </div>

    {{-- Índice --}}
    <nav class="mb-10 rounded-xl border border-gray-200 bg-gray-50 p-6 dark:border-gray-700 dark:bg-gray-800/50">
        <h2 class="mb-4 text-lg font-semibold text-gray-800 dark:text-white/90">Navegue pelo manual</h2>
        <ul class="space-y-2 text-sm">
            <li><a href="#o-que-e" class="text-brand-600 hover:underline dark:text-brand-400">O que é este sistema?</a></li>
            <li><a href="#entrar" class="text-brand-600 hover:underline dark:text-brand-400">Entrando no sistema (login)</a></li>
            <li><a href="#tela-inicial" class="text-brand-600 hover:underline dark:text-brand-400">Tela inicial (Dashboard)</a></li>
            <li><a href="#clientes" class="text-brand-600 hover:underline dark:text-brand-400">Clientes</a></li>
            <ul class="ml-4 mt-1 space-y-1">
                <li><a href="#clientes-cadastro" class="text-brand-600/80 hover:underline dark:text-brand-400/80">Cadastro e edição</a></li>
                <li><a href="#clientes-grupos" class="text-brand-600/80 hover:underline dark:text-brand-400/80">Grupos econômicos</a></li>
            </ul>
            <li><a href="#produtos" class="text-brand-600 hover:underline dark:text-brand-400">Produtos</a></li>
            <ul class="ml-4 mt-1 space-y-1">
                <li><a href="#produtos-cadastro" class="text-brand-600/80 hover:underline dark:text-brand-400/80">Cadastro e listagem</a></li>
                <li><a href="#produtos-categorias-fornecedores" class="text-brand-600/80 hover:underline dark:text-brand-400/80">Categorias e fornecedores</a></li>
            </ul>
            <li><a href="#servicos" class="text-brand-600 hover:underline dark:text-brand-400">Serviços</a></li>
            <ul class="ml-4 mt-1 space-y-1">
                <li><a href="#servicos-cadastro" class="text-brand-600/80 hover:underline dark:text-brand-400/80">Cadastro e listagem</a></li>
                <li><a href="#servicos-categorias" class="text-brand-600/80 hover:underline dark:text-brand-400/80">Categorias</a></li>
            </ul>
            <li><a href="#vendas" class="text-brand-600 hover:underline dark:text-brand-400">Vendas</a></li>
            <ul class="ml-4 mt-1 space-y-1">
                <li><a href="#vendas-pedidos" class="text-brand-600/80 hover:underline dark:text-brand-400/80">Pedidos de venda</a></li>
                <li><a href="#vendas-propostas" class="text-brand-600/80 hover:underline dark:text-brand-400/80">Propostas comerciais</a></li>
            </ul>
            <li><a href="#ordens-servico" class="text-brand-600 hover:underline dark:text-brand-400">Ordens de Serviço (OS)</a></li>
            <ul class="ml-4 mt-1 space-y-1">
                <li><a href="#os-cadastro" class="text-brand-600/80 hover:underline dark:text-brand-400/80">Cadastro e dados principais</a></li>
                <li><a href="#os-equipamento" class="text-brand-600/80 hover:underline dark:text-brand-400/80">Equipamento</a></li>
                <li><a href="#os-checklists" class="text-brand-600/80 hover:underline dark:text-brand-400/80">Checklists</a></li>
                <li><a href="#os-itens" class="text-brand-600/80 hover:underline dark:text-brand-400/80">Itens (produtos e serviços)</a></li>
                <li><a href="#os-equipe-sla" class="text-brand-600/80 hover:underline dark:text-brand-400/80">Equipe e SLA</a></li>
                <li><a href="#os-anexos" class="text-brand-600/80 hover:underline dark:text-brand-400/80">Anexos</a></li>
                <li><a href="#os-garantia-aprovacao" class="text-brand-600/80 hover:underline dark:text-brand-400/80">Garantia, contrato e aprovação</a></li>
                <li><a href="#os-assinatura" class="text-brand-600/80 hover:underline dark:text-brand-400/80">Assinatura (cliente e técnico)</a></li>
                <li><a href="#os-totais-encerrar" class="text-brand-600/80 hover:underline dark:text-brand-400/80">Totais, encerramento e impressões</a></li>
            </ul>
            <li><a href="#financeiro" class="text-brand-600 hover:underline dark:text-brand-400">Financeiro</a></li>
            <ul class="ml-4 mt-1 space-y-1">
                <li><a href="#fin-contas-receber" class="text-brand-600/80 hover:underline dark:text-brand-400/80">Contas a receber</a></li>
                <li><a href="#fin-contas-pagar" class="text-brand-600/80 hover:underline dark:text-brand-400/80">Contas a pagar</a></li>
                <li><a href="#fin-movimentacoes" class="text-brand-600/80 hover:underline dark:text-brand-400/80">Movimentações e transferências</a></li>
                <li><a href="#fin-recorrentes" class="text-brand-600/80 hover:underline dark:text-brand-400/80">Recorrências e tarefas</a></li>
                <li><a href="#fin-cadastros" class="text-brand-600/80 hover:underline dark:text-brand-400/80">Contas bancárias, formas de pagamento e plano de contas</a></li>
                <li><a href="#fin-relatorios" class="text-brand-600/80 hover:underline dark:text-brand-400/80">Relatórios e dashboard</a></li>
            </ul>
            <li><a href="#configuracoes" class="text-brand-600 hover:underline dark:text-brand-400">Configurações</a></li>
            <ul class="ml-4 mt-1 space-y-1">
                <li><a href="#config-geral" class="text-brand-600/80 hover:underline dark:text-brand-400/80">Configurações do sistema e empresa</a></li>
                <li><a href="#config-tags-status" class="text-brand-600/80 hover:underline dark:text-brand-400/80">Tags e status das OS</a></li>
                <li><a href="#config-notificacoes" class="text-brand-600/80 hover:underline dark:text-brand-400/80">Notificações e administração</a></li>
            </ul>
            <li><a href="#plugins-dev" class="text-brand-600 hover:underline dark:text-brand-400">Plugins (Guia para desenvolvedores)</a></li>
            <li><a href="#dicas" class="text-brand-600 hover:underline dark:text-brand-400">Dicas rápidas</a></li>
        </ul>
    </nav>

    <div class="space-y-12 text-gray-700 dark:text-gray-300">
        {{-- Seção 1: O que é --}}
        <section id="o-que-e" class="scroll-mt-8">
            <h2 class="mb-4 border-b border-gray-200 pb-2 text-2xl font-bold text-gray-800 dark:border-gray-700 dark:text-white/90">O que é este sistema?</h2>
            <p class="mb-3">Este sistema é um <strong>ERP</strong> (Enterprise Resource Planning) — uma ferramenta que centraliza e organiza as informações da sua empresa em um único lugar.</p>
            <p class="mb-3"><strong>Licença do sistema:</strong> o <strong>nosso sistema</strong> é distribuído sob licença <strong>AGPL</strong>. O ERP também suporta extensões por <strong>plugins</strong>, e os plugins criados para este sistema seguem a mesma licença <strong>AGPL</strong>. Componentes externos, integrações e softwares de terceiros mantêm suas próprias licenças, conforme os respectivos autores.</p>
            <h3 class="mb-2 text-lg font-semibold text-gray-800 dark:text-white/90">Principais módulos</h3>
            <ul class="mb-3 list-inside list-disc space-y-1">
                <li><strong>Clientes</strong> — cadastro de pessoas e empresas que você atende, com endereços, contatos e documentos.</li>
                <li><strong>Produtos</strong> — itens que você vende ou usa (peças, materiais), com preços, estoque e categorias.</li>
                <li><strong>Serviços</strong> — trabalhos que sua empresa oferece (instalação, manutenção, etc.) com preços e categorias.</li>
                <li><strong>Vendas</strong> — pedidos de venda (orçamentos e vendas com produtos/serviços), propostas comerciais e modelos de documento (Word/HTML), conforme permissão.</li>
                <li><strong>Ordens de Serviço</strong> — registro completo de cada atendimento: cliente, itens, valores, status, assinaturas e impressões.</li>
                <li><strong>Financeiro</strong> — contas a receber, contas a pagar, movimentações bancárias, transferências, relatórios (fluxo de caixa, DRE) e recorrências.</li>
                <li><strong>Configurações</strong> — dados da empresa, tags, status das OS, notificações e opções administrativas.</li>
            </ul>
            <p class="mb-3">Tudo fica guardado de forma organizada: você consulta, filtra e imprime quando precisar. Não é necessário conhecimento técnico avançado; basta seguir os passos desta Central de Ajuda.</p>
            <p><strong>Permissões:</strong> cada usuário tem um perfil (role) com permissões definidas pelo administrador. Se um item do menu não aparecer para você, é porque seu perfil não tem acesso àquela área. O administrador pode liberar em <strong>Admin → Perfis (Roles)</strong> ou <strong>Permissões</strong>.</p>
        </section>

        {{-- Seção 2: Login --}}
        <section id="entrar" class="scroll-mt-8">
            <h2 class="mb-4 border-b border-gray-200 pb-2 text-2xl font-bold text-gray-800 dark:border-gray-700 dark:text-white/90">Entrando no sistema (login)</h2>
            <p class="mb-3">O acesso é restrito a <strong>usuários cadastrados</strong>. O administrador cria seu usuário (e-mail e senha inicial) em <strong>Admin → Usuários</strong>.</p>
            <h3 class="mb-2 text-lg font-semibold text-gray-800 dark:text-white/90">Como entrar</h3>
            <ol class="list-inside list-decimal space-y-2">
                <li>Abra o navegador e digite o endereço do sistema (ex.: <code class="rounded bg-gray-200 px-1 dark:bg-gray-700">http://erp.suaempresa.com</code>).</li>
                <li>Na tela de login, informe <strong>E-mail</strong> e <strong>Senha</strong>.</li>
                <li>Clique em <strong>Entrar</strong> (ou pressione Enter).</li>
            </ol>
            <p class="mt-3">Se aparecer <em>credenciais inválidas</em>, confira o e-mail e a senha (e se o Caps Lock está desligado). <strong>Esqueceu a senha?</strong> Use a opção “Alterar senha” no menu do seu usuário (canto superior direito) após entrar — ou peça ao administrador para redefinir sua senha em Admin → Usuários.</p>
            <h3 class="mb-2 mt-4 text-lg font-semibold text-gray-800 dark:text-white/90">Saindo do sistema</h3>
            <p class="mb-2">Clique no seu nome ou avatar no <strong>canto superior direito</strong> para abrir o menu do usuário. Em seguida clique em <strong>Sair</strong>. Isso encerra sua sessão e protege o acesso em computadores compartilhados.</p>
            <p class="text-sm text-gray-600 dark:text-gray-400">No mesmo menu você encontra <strong>Configurações da conta</strong> e <strong>Alterar senha</strong>.</p>
        </section>

        {{-- Seção 3: Dashboard --}}
        <section id="tela-inicial" class="scroll-mt-8">
            <h2 class="mb-4 border-b border-gray-200 pb-2 text-2xl font-bold text-gray-800 dark:border-gray-700 dark:text-white/90">Tela inicial (Dashboard)</h2>
            <p class="mb-3">Após o login você é levado à <strong>tela inicial</strong> (Dashboard). Ela reúne resumos e atalhos para acelerar o dia a dia.</p>
            <h3 class="mb-2 text-lg font-semibold text-gray-800 dark:text-white/90">O que aparece no Dashboard</h3>
            <ul class="mb-3 list-inside list-disc space-y-1">
                <li><strong>Cards de totais</strong> — quantidade de clientes, produtos, serviços, OS e garantias (conforme permissões).</li>
                <li><strong>Resumo financeiro</strong> — receita e despesa do dia, contas a receber e a pagar vencendo hoje (se você tiver acesso ao financeiro).</li>
                <li><strong>Últimas OS</strong> — listagem das ordens de serviço mais recentes, com link para abrir ou editar.</li>
                <li><strong>Agenda / próximos compromissos</strong> — quando houver dados de agenda vinculados às OS.</li>
            </ul>
            <p class="mb-3">Cada bloco pode ter filtros ou links que levam direto à tela correspondente (ex.: clicar em uma OS abre o resumo ou a edição).</p>
            <h3 class="mb-2 text-lg font-semibold text-gray-800 dark:text-white/90">Menu lateral</h3>
            <p class="mb-2">No <strong>lado esquerdo</strong> fica o menu principal. Os itens visíveis dependem das permissões do seu usuário:</p>
            <ul class="list-inside list-disc space-y-1">
                <li><strong>Início</strong> — volta para o Dashboard.</li>
                <li><strong>Ajuda</strong> — Central de Ajuda (esta página).</li>
                <li><strong>Clientes</strong> — Listar Clientes, Novo Cliente, Grupos Econômicos.</li>
                <li><strong>Produtos</strong> — Listar Produtos, Novo Produto, Fornecedores, Categorias, Depósitos (se admin).</li>
                <li><strong>Serviços</strong> — Listar Serviços, Novo Serviço, Categorias.</li>
                <li><strong>Vendas</strong> — Listar Pedidos, Novo Pedido, Propostas comerciais, Nova proposta e (para quem tem permissão) Modelos de proposta (Word/HTML).</li>
                <li><strong>Ordens de Serviço</strong> — Listar OS, Nova OS, Modelos de Checklist.</li>
                <li><strong>Financeiro</strong> — Dashboard, Contas a Receber, Recebimento Recorrente, Tarefas de recorrência, Contas a Pagar, Movimentações, Transferências, Contas Bancárias, Formas de Pagamento, Adquirentes, Simulador, Plano de Contas, Centros de Custo, Configurações do financeiro, Relatórios, Fluxo de Caixa, DRE, etc. (itens exatos dependem do seu perfil).</li>
                <li><strong>Admin</strong> — Dashboard Admin, Usuários, Roles, Permissões e (administradores) auditorias: cancelamentos/exclusões, login e acesso de clientes.</li>
                <li><strong>Configurações</strong> — Configurações do Sistema, Empresa, Tags, Limites de Desconto, Plugins (conforme permissão).</li>
            </ul>
            <p class="mt-3">No <strong>canto superior direito</strong> ficam o botão de tema (claro/escuro), o sino de notificações e o menu do usuário (nome, configurações, sair). Se algum item do menu não aparecer, seu perfil não tem permissão para aquela área — o administrador pode ajustar em Admin → Perfis ou Permissões.</p>
        </section>

        {{-- Seção 4: Clientes --}}
        <section id="clientes" class="scroll-mt-8">
            <h2 class="mb-4 border-b border-gray-200 pb-2 text-2xl font-bold text-gray-800 dark:border-gray-700 dark:text-white/90">Clientes</h2>
            <p class="mb-4">O módulo de <strong>Clientes</strong> guarda as pessoas e empresas que você atende: nome, documento (CPF/CNPJ), endereços, contatos e vínculos com grupos econômicos. Tudo isso é usado em Ordens de Serviço, contas a receber e relatórios.</p>

            <h3 id="clientes-cadastro" class="scroll-mt-8 mb-2 text-lg font-semibold text-gray-800 dark:text-white/90">Cadastro e edição</h3>
            <p class="mb-2"><strong>O que cadastrar:</strong> para cada cliente você informa se é <strong>Pessoa Física</strong> (CPF) ou <strong>Pessoa Jurídica</strong> (CNPJ), nome ou razão social, nome fantasia (opcional), documento, inscrição estadual (se PJ), endereço (CEP, logradouro, número, bairro, cidade, UF), telefone e e-mail. Quanto mais completo, melhor para cobranças, envio de links de assinatura e impressões.</p>
            <p class="mb-2"><strong>Quando usar:</strong> cadastre um cliente antes de abrir uma OS ou lançar uma conta a receber. Use a listagem para consultar dados, alterar endereço ou contato e vincular a um grupo econômico.</p>
            <p class="mb-2"><strong>Como acessar:</strong></p>
            <ul class="mb-3 list-inside list-disc space-y-1">
                <li><strong>Novo cliente:</strong> menu <strong>Clientes</strong> → <strong>Novo Cliente</strong> (ou em Listar Clientes → botão Novo).</li>
                <li><strong>Listar / buscar:</strong> menu <strong>Clientes</strong> → <strong>Listar Clientes</strong>. Use o campo de busca no topo (nome, documento, e-mail). Filtros por grupo econômico e tipo (PF/PJ) podem estar disponíveis.</li>
                <li><strong>Editar:</strong> na lista, clique no nome do cliente ou no ícone de editar; altere os campos e salve.</li>
            </ul>
            <p class="mb-2"><strong>Passo a passo para cadastrar:</strong></p>
            <ol class="list-inside list-decimal space-y-2">
                <li>Clientes → Novo Cliente.</li>
                <li>Selecione <strong>Pessoa Física</strong> ou <strong>Pessoa Jurídica</strong>.</li>
                <li>Preencha <strong>Nome</strong> (ou Razão Social) e <strong>Documento</strong> (CPF ou CNPJ). Os demais campos são opcionais mas recomendados.</li>
                <li>Se informar CEP, o sistema pode preencher logradouro, bairro, cidade e UF automaticamente.</li>
                <li>Clique em <strong>Salvar</strong>. O cliente passa a aparecer na lista e pode ser escolhido em OS e financeiro.</li>
            </ol>

            <h3 id="clientes-grupos" class="scroll-mt-8 mb-2 mt-6 text-lg font-semibold text-gray-800 dark:text-white/90">Grupos econômicos</h3>
            <p class="mb-2"><strong>O que são:</strong> grupos econômicos reúnem vários clientes que pertencem ao mesmo grupo (ex.: holding e filiais, matriz e unidades). Isso permite relatórios e regras por grupo (limite de crédito, cobrança unificada, etc.), conforme a configuração do sistema.</p>
            <p class="mb-2"><strong>Como usar:</strong> em <strong>Clientes</strong> → <strong>Grupos Econômicos</strong> você cria o grupo (nome, descrição) e depois, na edição de cada cliente, seleciona o grupo ao qual ele pertence. A listagem de clientes pode ser filtrada por grupo.</p>
        </section>

        {{-- Seção 5: Produtos --}}
        <section id="produtos" class="scroll-mt-8">
            <h2 class="mb-4 border-b border-gray-200 pb-2 text-2xl font-bold text-gray-800 dark:border-gray-700 dark:text-white/90">Produtos</h2>
            <p class="mb-4">Produtos são os <strong>itens que você vende ou usa</strong>: peças, equipamentos, materiais. Cada um tem nome, preço (e/ou custo), código (SKU), categoria e pode ter controle de estoque e tabelas de preço (varejo, empresa, urgência) para uso nas OS.</p>

            <h3 id="produtos-cadastro" class="scroll-mt-8 mb-2 text-lg font-semibold text-gray-800 dark:text-white/90">Cadastro e listagem</h3>
            <p class="mb-2"><strong>O que cadastrar:</strong> nome, código (SKU), categoria, preço de venda, custo (opcional), unidade (un, cx, etc.), controle de estoque (ativo/inativo), quantidade mínima em estoque e observações. Se o sistema tiver tabelas de preço, você pode definir preço por tabela (ex.: Varejo, Empresa, Urgência) para que na OS o valor seja preenchido automaticamente conforme a tabela escolhida.</p>
            <p class="mb-2"><strong>Quando usar:</strong> cadastre os produtos antes de incluir na ordem de serviço ou em movimentações de estoque. A listagem serve para consultar preço, estoque e editar dados.</p>
            <p class="mb-2"><strong>Como acessar:</strong></p>
            <ul class="mb-3 list-inside list-disc space-y-1">
                <li><strong>Novo produto:</strong> menu <strong>Produtos</strong> → <strong>Novo Produto</strong>.</li>
                <li><strong>Listar / buscar:</strong> menu <strong>Produtos</strong> → <strong>Listar Produtos</strong>. Busca por nome ou código; filtros por categoria e fornecedor podem estar disponíveis.</li>
                <li><strong>Editar:</strong> na lista, clique no produto ou no ícone de editar.</li>
            </ul>
            <p class="mb-2"><strong>Passo a passo para cadastrar:</strong></p>
            <ol class="list-inside list-decimal space-y-2">
                <li>Produtos → Novo Produto.</li>
                <li>Preencha <strong>Nome</strong> e <strong>Preço de venda</strong>. Opcional: código (SKU), categoria, custo, unidade, controle de estoque.</li>
                <li>Clique em <strong>Salvar</strong>. O produto passa a aparecer na lista e pode ser escolhido na aba Itens da OS.</li>
            </ol>

            <h3 id="produtos-categorias-fornecedores" class="scroll-mt-8 mb-2 mt-6 text-lg font-semibold text-gray-800 dark:text-white/90">Categorias e fornecedores</h3>
            <p class="mb-2"><strong>Categorias:</strong> em <strong>Produtos</strong> → <strong>Categorias</strong> você cria categorias (ex.: Peças, Materiais, Equipamentos) e associa cada produto a uma delas. Isso organiza a listagem e permite filtros e relatórios por categoria.</p>
            <p class="mb-2"><strong>Fornecedores:</strong> em <strong>Produtos</strong> → <strong>Fornecedores</strong> você cadastra quem fornece os produtos (nome, CNPJ, contato, endereço). Depois pode vincular um fornecedor ao produto na edição do produto. Útil para compras e relatórios. Em alguns sistemas o fornecedor pode ser um cliente (pessoa/jurídica) usado como fornecedor.</p>
            <p class="mb-2">Se o menu tiver <strong>Depósitos</strong>, são os locais de estoque (almoxarifado, loja 1, etc.). O controle de estoque pode ser por depósito; ao dar baixa em uma OS, o sistema debita do depósito configurado.</p>
        </section>

        {{-- Seção 6: Serviços --}}
        <section id="servicos" class="scroll-mt-8">
            <h2 class="mb-4 border-b border-gray-200 pb-2 text-2xl font-bold text-gray-800 dark:border-gray-700 dark:text-white/90">Serviços</h2>
            <p class="mb-4">Serviços são os <strong>trabalhos que sua empresa cobra</strong>: instalação, manutenção, consultoria, reparo, visita técnica. Cada serviço tem nome, preço e pode ter categoria e cobrança por horas (valor/hora) para uso na OS.</p>

            <h3 id="servicos-cadastro" class="scroll-mt-8 mb-2 text-lg font-semibold text-gray-800 dark:text-white/90">Cadastro e listagem</h3>
            <p class="mb-2"><strong>O que cadastrar:</strong> nome do serviço, descrição (opcional), preço (valor fixo por unidade ou valor por hora, conforme configuração), categoria e se será cobrado por <strong>quantidade</strong> ou por <strong>horas</strong> na OS. Na OS, ao adicionar o serviço, você informa quantidade ou horas e o técnico responsável; o total é calculado automaticamente.</p>
            <p class="mb-2"><strong>Quando usar:</strong> cadastre todos os serviços que você oferece antes de montar ordens de serviço. Assim os itens aparecem padronizados na aba Itens da OS, com o valor correto.</p>
            <p class="mb-2"><strong>Como acessar:</strong></p>
            <ul class="mb-3 list-inside list-disc space-y-1">
                <li><strong>Novo serviço:</strong> menu <strong>Serviços</strong> → <strong>Novo Serviço</strong>.</li>
                <li><strong>Listar / buscar:</strong> menu <strong>Serviços</strong> → <strong>Listar Serviços</strong>. Busca por nome; filtro por categoria se existir.</li>
                <li><strong>Editar:</strong> na lista, clique no serviço ou no ícone de editar.</li>
            </ul>
            <p class="mb-2"><strong>Passo a passo para cadastrar:</strong></p>
            <ol class="list-inside list-decimal space-y-2">
                <li>Serviços → Novo Serviço.</li>
                <li>Preencha <strong>Nome</strong> e <strong>Preço</strong>. Opcional: descrição, categoria, cobrança por horas.</li>
                <li>Clique em <strong>Salvar</strong>. O serviço passa a estar disponível na aba Itens da OS (botão + Serviço).</li>
            </ol>

            <h3 id="servicos-categorias" class="scroll-mt-8 mb-2 mt-6 text-lg font-semibold text-gray-800 dark:text-white/90">Categorias</h3>
            <p class="mb-2">Em <strong>Serviços</strong> → <strong>Categorias</strong> você cria categorias (ex.: Instalação, Manutenção, Emergencial) e associa cada serviço a uma delas. Isso organiza a listagem e permite filtros e relatórios por tipo de serviço.</p>
        </section>

        {{-- Seção: Vendas --}}
        <section id="vendas" class="scroll-mt-8">
            <h2 class="mb-4 border-b border-gray-200 pb-2 text-2xl font-bold text-gray-800 dark:border-gray-700 dark:text-white/90">Vendas</h2>
            <p class="mb-4">O menu <strong>Vendas</strong> concentra <strong>pedidos de venda</strong> (orçamentos e vendas com itens de produto e serviço) e <strong>propostas comerciais</strong> com documentos personalizáveis. O acesso depende das permissões do seu perfil (ex.: ver ou criar pedidos, ver propostas).</p>

            <h3 id="vendas-pedidos" class="scroll-mt-8 mb-2 text-lg font-semibold text-gray-800 dark:text-white/90">Pedidos de venda</h3>
            <p class="mb-2"><strong>O que são:</strong> um pedido reúne cliente, vendedor (opcional), itens (produtos e/ou serviços do cadastro ou linhas avulsas, conforme permissão), totais, descontos e status do fluxo comercial.</p>
            <p class="mb-2"><strong>Status em linhas gerais:</strong> <em>Rascunho</em> e <em>Confirmado</em> permitem editar o pedido (conforme regras do sistema); <em>Faturado</em> e <em>Entregue</em> indicam pós-venda; <em>Cancelado</em> encerra o pedido. A interface e as ações disponíveis mudam conforme o status.</p>
            <ul class="mb-3 list-inside list-disc space-y-1">
                <li><strong>Onde:</strong> <strong>Vendas</strong> → <strong>Listar Pedidos</strong> ou <strong>Novo Pedido</strong>.</li>
                <li><strong>Itens:</strong> adicione linhas de produto ou serviço; o sistema calcula subtotais e total geral. Descontos por item ou globais podem exigir permissão específica e respeitar limites definidos em <strong>Configurações → Limites de Desconto</strong>.</li>
                <li><strong>Importar de outro orçamento:</strong> na edição/criação, em <strong>Itens do Pedido</strong>, use <strong>Importar de orçamento</strong> para trazer linhas de outro pedido em rascunho ou confirmado (acrescenta às linhas atuais).</li>
                <li><strong>Validade da proposta:</strong> em <strong>Configurações do Sistema</strong> há padrão de dias de validade do orçamento; no próprio pedido você pode definir validade específica (dias corridos a partir da data do pedido), usada no resumo e em impressões/PDF quando aplicável.</li>
                <li><strong>Numeração:</strong> administradores configuram máscara e sequência em <strong>Configurações do Sistema</strong> (área Pedidos de venda), similar à numeração de OS.</li>
                <li><strong>Financeiro:</strong> ao <strong>faturar</strong> (ou conforme o fluxo da sua instalação), o sistema pode gerar <strong>contas a receber</strong> vinculadas ao pedido. Se o campo indicar que o financeiro já foi gerado, evite duplicar títulos manualmente sem conferir o financeiro.</li>
                <li><strong>Cancelamento após faturamento:</strong> cancelamentos (total ou parcial) podem gerar lançamentos de estorno no financeiro, ajustar estoque e alterar quantidades nas linhas — conforme permissões e configuração de plano de contas de estorno.</li>
            </ul>
            <p class="mb-6 text-sm text-gray-600 dark:text-gray-400">Documentação técnica complementar no repositório: <code class="rounded bg-gray-200 px-1 dark:bg-gray-700">docs/PEDIDOS_VENDA.md</code> (descontos, numeração, validade, cancelamento).</p>

            <h3 id="vendas-propostas" class="scroll-mt-8 mb-2 text-lg font-semibold text-gray-800 dark:text-white/90">Propostas comerciais</h3>
            <p class="mb-2"><strong>Propostas</strong> são documentos comerciais (geralmente com texto rico, valores e condições) que você pode gerar, versionar e enviar ao cliente. O menu <strong>Propostas comerciais</strong> lista e permite criar novas propostas.</p>
            <ul class="mb-4 list-inside list-disc space-y-1">
                <li><strong>Modelos de documento:</strong> usuários com permissão de gestão de modelos acessam <strong>Modelos de proposta (Word/HTML)</strong> para cadastrar templates (.docx com tags ou HTML) usados na geração do PDF/documento da proposta.</li>
                <li>Tags e variáveis dos modelos são descritas na documentação do projeto (<code class="rounded bg-gray-200 px-1 dark:bg-gray-700">docs/PROPOSTAS_COMERCIAIS.md</code>, <code class="rounded bg-gray-200 px-1 dark:bg-gray-700">docs/PROPOSTA_DOCX_TAGS.md</code>).</li>
                <li><strong>Texto rico em DOCX:</strong> campos longos como descrição/observações podem sair com formatação no Word; se uma tag não substituir, recrie a tag manualmente no Word para evitar caracteres especiais invisíveis.</li>
            </ul>
            <p class="mb-2 text-sm text-gray-600 dark:text-gray-400"><strong>Novidade:</strong> na ficha do cliente é possível consultar listas de <strong>propostas comerciais</strong> e <strong>vendas PDV</strong>.</p>
            <p class="mb-2"><strong>Plugin PDV:</strong> se a sua empresa usa o plugin de <strong>Ponto de Venda (PDV)</strong>, as vendas no caixa podem seguir regras próprias (atalhos, cancelamento, integração com estoque e financeiro). Consulte a ajuda dentro da tela do PDV (ex.: tecla <kbd class="rounded border border-gray-300 bg-gray-100 px-1 text-xs dark:border-gray-600 dark:bg-gray-800">F1</kbd> para dicas).</p>
        </section>

        {{-- Seção 7: Ordens de Serviço (guia completo) --}}
        <section id="ordens-servico" class="scroll-mt-8">
            <h2 class="mb-4 border-b border-gray-200 pb-2 text-2xl font-bold text-gray-800 dark:border-gray-700 dark:text-white/90">Ordens de Serviço (OS)</h2>
            <p class="mb-4">Uma <strong>Ordem de Serviço (OS)</strong> é o registro completo de um atendimento: quem é o cliente, o que foi feito (produtos e serviços), quanto custa e em que etapa está. O sistema organiza tudo em <strong>abas</strong>. Abaixo está o guia de cada funcionalidade.</p>

            <h3 class="mb-2 text-lg font-semibold text-gray-800 dark:text-white/90">Como acessar</h3>
            <ul class="mb-6 list-inside list-disc space-y-1">
                <li><strong>Nova OS:</strong> menu <strong>Ordens de Serviço</strong> → <strong>Nova OS</strong>.</li>
                <li><strong>Listar / buscar OS:</strong> menu <strong>Ordens de Serviço</strong> → <strong>Listar OS</strong>. Use o campo de busca no topo (número, cliente, etc.) e os filtros por status, data e cliente.</li>
                <li><strong>Editar uma OS:</strong> na lista, clique no ícone de editar ou no número da OS; na tela de resumo, use o botão <strong>Editar</strong>.</li>
                <li><strong>Ações em massa:</strong> ao selecionar OS na listagem, aparece o painel para aplicar status, prioridade e tags em lote.</li>
                <li><strong>Ordenação:</strong> clique no nome das colunas da listagem para ordenar (asc/desc) mantendo os filtros atuais.</li>
            </ul>

            {{-- Cadastro --}}
            <h3 id="os-cadastro" class="scroll-mt-8 mb-2 text-lg font-semibold text-gray-800 dark:text-white/90">1. Cadastro (dados principais)</h3>
            <p class="mb-2">Primeira aba da OS. Aqui você define:</p>
            <ul class="mb-4 list-inside list-disc space-y-1">
                <li><strong>Cliente:</strong> obrigatório. Selecione na lista; se não existir, cadastre antes em Clientes. Você pode editar cliente, endereço e contato direto na OS (botão <strong>Editar</strong> ao lado dos dados do cliente).</li>
                <li><strong>Tipo de serviço:</strong> manutenção, instalação, garantia, visita técnica, projeto, contrato ou outro.</li>
                <li><strong>Origem:</strong> como a demanda chegou (balcão, telefone, app, site, indicação, contrato).</li>
                <li><strong>Prioridade:</strong> baixa, normal, alta ou crítica.</li>
                <li><strong>Status:</strong> por exemplo Aberta, Em execução, Aguardando peça, Concluída. Você pode alterar em qualquer momento. Em <strong>Configurações → Status das OS</strong> o administrador define a lista de status.</li>
                <li><strong>Relato do cliente / descrição:</strong> texto livre com o que o cliente relatou ou o que será feito. Use as abas de descrição (Relato, Solução, etc.) se estiverem disponíveis.</li>
            </ul>
            <p class="mb-6">Salve a OS com <strong>Salvar</strong> (ou o botão de salvar no topo). Em edição, o sistema pode salvar automaticamente em segundo plano.</p>

            {{-- Equipamento --}}
            <h3 id="os-equipamento" class="scroll-mt-8 mb-2 text-lg font-semibold text-gray-800 dark:text-white/90">2. Equipamento</h3>
            <p class="mb-4">Registre o <strong>equipamento</strong> relacionado ao atendimento (ex.: ar-condicionado, câmera, central de alarme). Você pode vincular um equipamento já cadastrado ao cliente ou informar apenas o nome do equipamento. Isso ajuda em garantias e histórico. Se houver equipamento, na tela de edição da OS aparece o botão <strong>Imprimir QR do Equipamento</strong>, que gera uma etiqueta com QR Code para colar no equipamento.</p>

            {{-- Checklists --}}
            <h3 id="os-checklists" class="scroll-mt-8 mb-2 text-lg font-semibold text-gray-800 dark:text-white/90">3. Checklists</h3>
            <p class="mb-2">Checklists são <strong>listas de verificação</strong> (ex.: “Conferir cabo de energia”, “Testar funcionamento”).</p>
            <ul class="mb-4 list-inside list-disc space-y-1">
                <li>Os <strong>modelos</strong> de checklist são cadastrados em <strong>Ordens de Serviço → Modelos de Checklist</strong>. Cada modelo tem itens (perguntas ou tarefas).</li>
                <li>Na aba <strong>Checklists</strong> da OS você <strong>adiciona</strong> um ou mais modelos à OS e marca os itens como concluídos conforme for executando o serviço.</li>
            </ul>

            {{-- Itens (produtos e serviços) --}}
            <h3 id="os-itens" class="scroll-mt-8 mb-2 text-lg font-semibold text-gray-800 dark:text-white/90">4. Itens (produtos e serviços)</h3>
            <p class="mb-2">Aqui você inclui tudo que será <strong>cobrado</strong> do cliente.</p>
            <ul class="mb-2 list-inside list-disc space-y-1">
                <li><strong>Produtos / peças:</strong> clique em <strong>+ Produto</strong>, escolha o produto na lista. O sistema preenche o valor unitário conforme a <strong>tabela de preços</strong> (Varejo, Empresa, Urgência) selecionada no topo. Informe a quantidade; pode aplicar desconto por item (em R$ ou %). A coluna <strong>Estoque</strong> mostra o estoque atual do produto ao selecioná-lo. Se seu perfil tiver permissão, você pode adicionar <strong>produto avulso</strong> (item sem cadastro, só descrição e valor).</li>
                <li><strong>Serviços:</strong> clique em <strong>+ Serviço</strong>, escolha o serviço. Pode cobrar por <strong>unidade</strong> (quantidade) ou por <strong>horas</strong>; informe o técnico responsável e o valor. Também é possível adicionar <strong>serviço avulso</strong> se tiver permissão.</li>
            </ul>
            <p class="mb-4">Os <strong>totais</strong> (subtotal, descontos, total) são calculados automaticamente e aparecem abaixo da lista de itens e na aba <strong>Totais</strong>. Se houver <strong>limite de desconto</strong> para seu usuário, o sistema pode bloquear descontos acima do permitido.</p>

            {{-- Equipe e SLA --}}
            <h3 id="os-equipe-sla" class="scroll-mt-8 mb-2 text-lg font-semibold text-gray-800 dark:text-white/90">5. Equipe e SLA</h3>
            <p class="mb-2"><strong>Equipe:</strong> na aba Equipe você vincula <strong>técnicos</strong> (usuários do sistema) à OS. Isso define quem está responsável pelo atendimento e pode ser usado em relatórios e na cobrança por horas dos serviços.</p>
            <p class="mb-4"><strong>SLA:</strong> na aba SLA você pode registrar prazos e metas (ex.: prazo para conclusão). Os campos disponíveis dependem da configuração do sistema.</p>

            {{-- Anexos --}}
            <h3 id="os-anexos" class="scroll-mt-8 mb-2 text-lg font-semibold text-gray-800 dark:text-white/90">6. Anexos</h3>
            <p class="mb-4">Na aba <strong>Anexos</strong> você envia <strong>arquivos</strong> (fotos, PDFs, documentos) relacionados à OS. São úteis para fotos do equipamento, comprovantes ou laudos. Adicione o arquivo, opcionalmente informe uma descrição ou tags, e salve. Os anexos ficam listados na mesma aba e podem ser visualizados ou removidos.</p>

            {{-- Garantia, contrato e aprovação --}}
            <h3 id="os-garantia-aprovacao" class="scroll-mt-8 mb-2 text-lg font-semibold text-gray-800 dark:text-white/90">7. Garantia e contrato / Aprovação</h3>
            <p class="mb-2"><strong>Garantia e contrato:</strong> você pode vincular um <strong>termo de garantia</strong> (cadastrado em Configurações → Termos de Garantia) e informar dados de contrato, se aplicável.</p>
            <p class="mb-4"><strong>Aprovação:</strong> campos para registro de aprovação do cliente ou responsável (valor aprovado, observações). Usado quando o serviço precisa de aceite formal antes da cobrança.</p>

            {{-- Assinatura --}}
            <h3 id="os-assinatura" class="scroll-mt-8 mb-2 text-lg font-semibold text-gray-800 dark:text-white/90">8. Assinatura (cliente e técnico)</h3>
            <p class="mb-2">A OS pode ter <strong>duas assinaturas digitais</strong>: do técnico e do cliente.</p>
            <ul class="mb-2 list-inside list-disc space-y-1">
                <li><strong>Assinatura do técnico:</strong> na aba Assinatura, o técnico assina direto na tela (ou usa uma assinatura salva, se configurado). O link para a assinatura do técnico pode ser acessado por quem tem permissão na OS.</li>
                <li><strong>Assinatura do cliente:</strong> o sistema gera um <strong>link único</strong> (token) que é enviado ao cliente por e-mail ou WhatsApp. O cliente abre o link em qualquer dispositivo, lê os termos, marca que concorda e assina na tela. Quando ambas as assinaturas estiverem feitas, o sistema pode gerar automaticamente o <strong>documento assinado</strong> (PDF).</li>
            </ul>
            <p class="mb-4">Para enviar o link ao cliente: na aba Assinatura, use o botão para <strong>gerar link do cliente</strong> e depois envie o link por e-mail ou WhatsApp (ou copie e envie manualmente). O administrador pode revogar ou reenviar o link em Assinaturas (menu operacional).</p>

            {{-- Totais, encerramento e impressões --}}
            <h3 id="os-totais-encerrar" class="scroll-mt-8 mb-2 text-lg font-semibold text-gray-800 dark:text-white/90">9. Totais, encerramento e impressões</h3>
            <p class="mb-2"><strong>Totais:</strong> a aba <strong>Totais</strong> mostra o resumo financeiro: subtotal de produtos, subtotal de serviços, descontos, acréscimos e <strong>total geral</strong>. Você pode aplicar um <strong>desconto global</strong> na OS (em R$ ou %), respeitando o limite do seu perfil, se houver.</p>
            <p class="mb-2"><strong>Obs. internas:</strong> a aba <strong>Obs. internas</strong> é para anotações que <strong>não</strong> devem aparecer no documento impresso para o cliente. Use para comentários da equipe.</p>
            <p class="mb-2"><strong>Histórico:</strong> se seu perfil tiver permissão (<strong>Ver histórico de OS</strong>), a aba <strong>Histórico</strong> mostra o log de alterações da OS (quem alterou o quê e quando), inclusive mudanças antes/depois em abas principais e em ações em massa.</p>
            <p class="mb-2"><strong>Encerrar OS:</strong> quando o atendimento estiver concluído e você quiser “fechar” a OS, clique em <strong>Encerrar OS</strong> (botão verde na tela de edição). O sistema pode gerar as <strong>contas a receber</strong> com base nos totais da OS, conforme a configuração (adiantamentos, valor a receber, etc.). Depois de encerrada, a OS não pode mais ser alterada em alguns pontos (dependendo da configuração).</p>
            <p class="mb-2"><strong>Impressões e notificações:</strong> na tela de edição você encontra:</p>
            <ul class="mb-4 list-inside list-disc space-y-1">
                <li><strong>Imprimir OS</strong> — imprime a ordem de serviço (resumo para o cliente).</li>
                <li><strong>Imprimir Comprovante</strong> — disponível após encerrar; imprime o comprovante de conclusão.</li>
                <li><strong>Imprimir QR do Equipamento</strong> — gera etiqueta com QR Code do equipamento (se a OS tiver equipamento vinculado).</li>
                <li><strong>Notificar cliente</strong> — abre um modal para enviar e-mail ou abrir o WhatsApp com mensagem pré-preenchida sobre a OS (útil para avisar o cliente sobre status ou link de assinatura).</li>
                <li><strong>Compartilhar WhatsApp</strong> — abre diretamente o WhatsApp com número do cliente e mensagem montada pelo template da OS.</li>
            </ul>

            <h3 class="mb-2 text-lg font-semibold text-gray-800 dark:text-white/90">Tags</h3>
            <p class="mb-4">Na tela de edição da OS (acima do formulário) você pode adicionar <strong>tags</strong> (ex.: “Urgente”, “Garantia”). As tags são cadastradas em <strong>Configurações → Tags</strong> e ajudam a filtrar e organizar as OS na lista.</p>

            <h3 class="mb-2 text-lg font-semibold text-gray-800 dark:text-white/90">Resumo do fluxo</h3>
            <ol class="list-inside list-decimal space-y-2">
                <li>Criar a OS (Nova OS) e escolher o cliente.</li>
                <li>Preencher cadastro, equipamento (se houver), checklists (se usar), itens (produtos e serviços) e equipe.</li>
                <li>Opcional: enviar link de assinatura ao cliente e coletar assinatura do técnico.</li>
                <li>Revisar totais na aba Totais e aplicar descontos se necessário.</li>
                <li>Encerrar a OS quando o serviço estiver concluído — o sistema pode gerar as contas a receber.</li>
                <li>Usar Imprimir OS ou Comprovante e Notificar cliente conforme a necessidade.</li>
            </ol>
        </section>

        {{-- Seção 8: Financeiro --}}
        <section id="financeiro" class="scroll-mt-8">
            <h2 class="mb-4 border-b border-gray-200 pb-2 text-2xl font-bold text-gray-800 dark:border-gray-700 dark:text-white/90">Financeiro</h2>
            <p class="mb-4">O módulo <strong>Financeiro</strong> controla entradas e saídas de dinheiro: contas a receber, contas a pagar, movimentações bancárias, transferências, cadastros auxiliares (contas bancárias, formas de pagamento, plano de contas) e relatórios (fluxo de caixa, DRE). Abaixo cada funcionalidade é explicada em detalhe.</p>

            <h3 id="fin-contas-receber" class="scroll-mt-8 mb-2 text-lg font-semibold text-gray-800 dark:text-white/90">Contas a receber</h3>
            <p class="mb-2">São os <strong>valores que os clientes devem pagar</strong>. Podem ser gerados ao encerrar uma OS, ao <strong>faturar um pedido de venda</strong>, por um <strong>recebimento recorrente</strong> (mensalidade, assinatura), por importação de sistemas externos (ex.: backup no formato M-OS, quando configurado) ou lançados manualmente.</p>
            <ul class="mb-2 list-inside list-disc space-y-1">
                <li><strong>Onde:</strong> menu <strong>Financeiro</strong> → <strong>Contas a Receber</strong>.</li>
                <li><strong>Listar / filtrar:</strong> use a lista para ver todas as contas; filtre por período de vencimento, cliente, status (pendente, pago, vencido). A busca pode ser por descrição ou número.</li>
                <li><strong>Nova conta:</strong> botão Novo (ou equivalente) para lançar uma conta a receber manual: cliente, descrição, valor, vencimento, forma de pagamento e conta bancária (se aplicável). Você pode anexar comprovantes na edição da conta.</li>
                <li><strong>Dar baixa (receber):</strong> na conta pendente, use o botão de receber/baixar. Informe a data do recebimento, valor (pode ser parcial), forma de pagamento e conta bancária. O sistema registra a movimentação na conta escolhida e marca a conta como paga (total ou parcial).</li>
            </ul>

            <h3 id="fin-contas-pagar" class="scroll-mt-8 mb-2 text-lg font-semibold text-gray-800 dark:text-white/90">Contas a pagar</h3>
            <p class="mb-2">São as <strong>despesas que a empresa precisa pagar</strong>: fornecedores, impostos, aluguel, etc.</p>
            <ul class="mb-2 list-inside list-disc space-y-1">
                <li><strong>Onde:</strong> menu <strong>Financeiro</strong> → <strong>Contas a Pagar</strong>.</li>
                <li><strong>Listar / filtrar:</strong> filtre por vencimento, fornecedor, status (pendente, pago).</li>
                <li><strong>Nova conta:</strong> cadastre a despesa (descrição, valor, vencimento, fornecedor, categoria/plano de contas, centro de custo). Anexos podem ser permitidos para comprovantes.</li>
                <li><strong>Registrar pagamento:</strong> na conta pendente, use o botão de pagar. Informe data, valor (pode ser parcial), forma de pagamento e conta bancária de saída. O sistema gera a movimentação de saída e atualiza o status da conta.</li>
            </ul>

            <h3 id="fin-movimentacoes" class="scroll-mt-8 mb-2 text-lg font-semibold text-gray-800 dark:text-white/90">Movimentações e transferências</h3>
            <p class="mb-2"><strong>Movimentações:</strong> toda entrada ou saída de dinheiro nas contas bancárias aparece em <strong>Financeiro → Movimentações</strong>. Ao dar baixa em uma conta a receber ou pagar uma conta a pagar, o sistema gera a movimentação na conta escolhida. Você também pode lançar <strong>movimentações manuais</strong> (ajustes, juros, tarifas) se tiver permissão. Use os filtros por conta, período e tipo para encontrar um lançamento. Conforme permissão, é possível <strong>conciliar ou desconciliar</strong> movimentações com títulos a receber ou a pagar e, em alguns casos, <strong>cancelar transferências</strong> entre contas registradas como transferência.</p>
            <p class="mb-2"><strong>Transferências:</strong> em <strong>Financeiro → Transferência entre contas</strong> você registra a saída de uma conta e a entrada em outra (ex.: corrente → poupança). Informe valor, data e as duas contas; o sistema cria as movimentações vinculadas e mantém o saldo coerente.</p>

            <h3 id="fin-recorrentes" class="scroll-mt-8 mb-2 text-lg font-semibold text-gray-800 dark:text-white/90">Recorrências e tarefas</h3>
            <p class="mb-2"><strong>Recebimento recorrente:</strong> para cobranças que se repetem (mensalidade, assinatura), use <strong>Financeiro → Recebimento Recorrente</strong> (ou Pagamentos Recorrentes, conforme o menu). Cadastre o cliente, valor, periodicidade (mensal, etc.), dia de vencimento e se há reajuste por índice. O sistema gera automaticamente as contas a receber nas datas configuradas (via tarefa agendada).</p>
            <p class="mb-2"><strong>Pagamento recorrente:</strong> despesas fixas (aluguel, condomínio) podem ser cadastradas como recorrentes; o sistema gera as contas a pagar periodicamente.</p>
            <p class="mb-2"><strong>Tarefas recorrentes:</strong> em <strong>Financeiro → Tarefas Recorrentes</strong> (ou Configurações → Tarefas Agendadas) o administrador pode executar manualmente a geração de contas recorrentes e ver o log das execuções. A execução automática é feita por um agendador (cron) configurado no servidor.</p>

            <h3 id="fin-cadastros" class="scroll-mt-8 mb-2 text-lg font-semibold text-gray-800 dark:text-white/90">Contas bancárias, formas de pagamento e plano de contas</h3>
            <p class="mb-2"><strong>Contas bancárias:</strong> em <strong>Financeiro → Contas Bancárias</strong> cadastre cada conta (banco, agência, número, nome/título). São essas contas que aparecem ao dar baixa em contas a receber, pagar contas a pagar e ao registrar transferências.</p>
            <p class="mb-2"><strong>Formas de pagamento:</strong> em <strong>Financeiro → Formas de Pagamento</strong> cadastre dinheiro, PIX, cartão de crédito, boleto, etc. Ao receber ou pagar, você escolhe a forma e a conta (quando aplicável).</p>
            <p class="mb-2"><strong>Plano de contas:</strong> em <strong>Financeiro → Plano de Contas</strong> organize as receitas e despesas por tipo (ex.: Venda de serviços, Material de consumo, Impostos). Ao lançar uma conta a receber ou a pagar, você pode informar a conta contábil; isso alimenta relatórios como DRE.</p>
            <p class="mb-2"><strong>Centros de custo:</strong> em <strong>Financeiro → Centros de Custo</strong> (se existir no menu) cadastre departamentos ou projetos. Ao lançar contas você informa o centro de custo para relatórios por área.</p>

            <h3 id="fin-relatorios" class="scroll-mt-8 mb-2 text-lg font-semibold text-gray-800 dark:text-white/90">Relatórios e dashboard</h3>
            <p class="mb-2"><strong>Dashboard Financeiro:</strong> em <strong>Financeiro → Dashboard Financeiro</strong> (ou na primeira tela do menu Financeiro) aparecem resumos: receitas e despesas do período, contas a vencer, saldo por conta, etc.</p>
            <p class="mb-2"><strong>Fluxo de caixa:</strong> relatório que mostra entradas e saídas por período, permitindo ver o saldo projetado.</p>
            <p class="mb-2"><strong>DRE:</strong> Demonstração do Resultado do Exercício — receitas e despesas por plano de contas em um período, com resultado (lucro/prejuízo).</p>
            <p class="mb-2">Outros relatórios podem estar em <strong>Financeiro → Relatórios</strong> (ou Inteligência): inadimplência, contas vencidas, por cliente, por centro de custo. Use os filtros de data e agrupamento conforme a necessidade.</p>
        </section>

        {{-- Seção 9: Configurações --}}
        <section id="configuracoes" class="scroll-mt-8">
            <h2 class="mb-4 border-b border-gray-200 pb-2 text-2xl font-bold text-gray-800 dark:border-gray-700 dark:text-white/90">Configurações</h2>
            <p class="mb-4">Em <strong>Configurações</strong> ficam os dados da empresa, preferências do sistema e opções administrativas. O menu pode estar em <strong>Configurações</strong> no lateral ou em um submenu “Configurações do Sistema”. Os itens visíveis dependem das permissões do seu usuário.</p>

            <h3 id="config-geral" class="scroll-mt-8 mb-2 text-lg font-semibold text-gray-800 dark:text-white/90">Configurações do sistema e empresa</h3>
            <p class="mb-2"><strong>Configurações do Sistema</strong> é a página central que reúne links para as demais telas de configuração (empresa, tags, status, pedidos de venda, plugins, etc.). Use como ponto de partida para acessar cada item.</p>
            <p class="mb-2"><strong>Empresa (cadastro da empresa):</strong> nome ou razão social, CNPJ, endereço, telefone, e-mail, logotipo. Esses dados aparecem em impressões de OS, comprovantes e relatórios. Mantenha atualizado para que os documentos saiam corretos.</p>
            <p class="mb-2">Outras opções gerais podem incluir: moeda, formato de data, fuso horário, <strong>numeração e máscara de pedidos de venda</strong>, validade padrão de orçamento, e parâmetros que afetam o comportamento do sistema (ex.: gerar conta a receber ao encerrar OS, número de parcelas padrão).</p>
            <p class="mb-2"><strong>Utilidades (administradores):</strong> em <strong>Configurações → Utilidades</strong> ficam operações sensíveis, como importação de dados (ex.: backup de sistema externo em ZIP/SQL no formato M-OS, quando o plugin importador estiver ativo), restauração ou manutenção. Use apenas com orientação e backup; a importação pode criar clientes, OS, financeiro e <strong>pedidos de venda</strong> a partir do export compatível.</p>

            <h3 id="config-tags-status" class="scroll-mt-8 mb-2 mt-6 text-lg font-semibold text-gray-800 dark:text-white/90">Tags e status das OS</h3>
            <p class="mb-2"><strong>Tags:</strong> em <strong>Configurações → Tags</strong> (ou Cadastro de Tags) você cria as etiquetas usadas nas ordens de serviço (ex.: “Urgente”, “Garantia”, “Contrato”). Na tela de edição da OS você seleciona uma ou mais tags; na listagem de OS é possível filtrar por tag.</p>
            <p class="mb-2"><strong>Status das OS:</strong> em <strong>Configurações → Status das Ordens de Serviço</strong> você define a lista de status (Aberta, Em execução, Aguardando peça, Concluída, Cancelada, etc.). Cada OS deve ter um status; você pode reordenar e definir cores para exibição. Ajuste os status ao fluxo de trabalho da sua empresa.</p>
            <p class="mb-2"><strong>Termos de Garantia:</strong> textos padrão de garantia que podem ser vinculados à OS (em Configurações → Termos de Garantia). Útil para padronizar o texto que aparece no documento da OS ou no comprovante.</p>
            <p class="mb-2"><strong>Limites de desconto:</strong> o administrador pode definir um limite de desconto (em % ou valor) por perfil de usuário. Quem ultrapassar o limite pode ser bloqueado ou precisar de aprovação (conforme configuração).</p>

            <h3 id="config-notificacoes" class="scroll-mt-8 mb-2 mt-6 text-lg font-semibold text-gray-800 dark:text-white/90">Notificações e administração</h3>
            <p class="mb-2"><strong>Notificações de OS:</strong> configure o envio de e-mail e/ou WhatsApp quando uma OS for criada ou quando o status mudar. É necessário configurar o servidor de e-mail (SMTP) nas configurações do sistema; para WhatsApp, depende da integração disponível (API ou manual). Em muitos cenários o modo padrão é <strong>manual</strong> (gera texto para copiar e enviar).</p>
            <p class="mb-2"><strong>Plugins:</strong> em <strong>Configurações → Plugins</strong> o administrador vê a lista de plugins instalados, pode ativar ou desativar e acessar configurações específicas de cada plugin. Plugins estendem o sistema sem alterar o núcleo.</p>
            <ul class="mb-2 list-inside list-disc space-y-1">
                <li><strong>Área do Cliente</strong> — portal para o cliente acompanhar OS e chamados.</li>
                <li><strong>PDV</strong> — ponto de venda integrado (caixa, venda, pagamentos, impressão e modo offline).</li>
                <li><strong>Importar de sistema externo (M-OS)</strong> — importação de backup externo na tela de Utilidades.</li>
                <li><strong>Atendimento</strong> — gestão de atendimentos externos com conversão para OS.</li>
                <li><strong>Boas-vindas</strong> — notificação de boas-vindas no primeiro acesso da sessão.</li>
                <li><strong>Exemplo de Plugin</strong> — referência técnica para criação de novos plugins.</li>
            </ul>
            <p class="mb-2"><strong>Tarefas agendadas:</strong> em <strong>Configurações → Tarefas Agendadas</strong> o administrador pode ver e executar manualmente tarefas em segundo plano (geração de contas recorrentes, reajuste de recorrências, rotinas ligadas a plugins, etc.). A execução automática depende de um <strong>cron</strong> no servidor chamando o agendador do Laravel (<code class="rounded bg-gray-200 px-1 dark:bg-gray-700">php artisan schedule:run</code>).</p>
            <p class="mb-2"><strong>Admin / auditoria:</strong> no menu <strong>Admin</strong>, além de <strong>Usuários</strong>, <strong>Roles</strong> e <strong>Permissões</strong>, administradores podem acessar <strong>Histórico de Cancelamentos/Exclusões</strong> (rastreio de exclusões e cancelamentos com motivo), <strong>Auditoria de Login</strong>, <strong>Auditoria de Acesso de Clientes</strong> e <strong>Auditoria Financeira</strong> (contas a receber/pagar com antes/depois). Se você não vir algum item de Configurações ou Admin, seu perfil não tem permissão — o administrador pode liberar em Admin → Perfis ou Permissões.</p>
            <p class="mb-2"><strong>Backups em partes (Utilidades):</strong> em <strong>Configurações → Utilidades</strong> você pode exportar backup completo/anexos em partes (com tamanho configurável), baixar as partes geradas e importar por partes com log de progresso na própria tela.</p>
        </section>

        {{-- Seção: Plugins (Dev) --}}
        <section id="plugins-dev" class="scroll-mt-8">
            <h2 class="mb-4 border-b border-gray-200 pb-2 text-2xl font-bold text-gray-800 dark:border-gray-700 dark:text-white/90">Plugins (Guia para desenvolvedores)</h2>
            <p class="mb-3">O ERP usa um sistema de plugins inspirado no WordPress: você estende funcionalidades via <strong>hooks</strong> e rotas do próprio plugin, sem alterar o core.</p>
            <p class="mb-4">Regra de ouro: o plugin deve ser <strong>isolado</strong>. Se ele for desativado, o sistema principal deve continuar funcionando sem erros.</p>

            <h3 class="mb-2 text-lg font-semibold text-gray-800 dark:text-white/90">Passo a passo (do zero)</h3>
            <ol class="mb-4 list-inside list-decimal space-y-2">
                <li><strong>Crie a pasta do plugin</strong> em <code class="rounded bg-gray-200 px-1 dark:bg-gray-700">plugins/meu-plugin</code> (slug em minúsculo com hífen).</li>
                <li><strong>Crie o arquivo obrigatório</strong> <code class="rounded bg-gray-200 px-1 dark:bg-gray-700">plugin.php</code> com metadados (<em>Plugin Name</em>, <em>Description</em>, <em>Version</em>).</li>
                <li><strong>Registre ciclo de vida</strong> com hooks de ativação/desativação/desinstalação para criar/limpar dados do plugin.</li>
                <li><strong>Adicione rotas no plugin</strong> em <code class="rounded bg-gray-200 px-1 dark:bg-gray-700">plugins/meu-plugin/routes.php</code> (URL base automática: <code class="rounded bg-gray-200 px-1 dark:bg-gray-700">/plugin/meu-plugin</code>).</li>
                <li><strong>Crie views do plugin</strong> em <code class="rounded bg-gray-200 px-1 dark:bg-gray-700">plugins/meu-plugin/views</code> e renderize com namespace <code class="rounded bg-gray-200 px-1 dark:bg-gray-700">meu-plugin::nome-da-view</code>.</li>
                <li><strong>Integre por hooks</strong> usando <code class="rounded bg-gray-200 px-1 dark:bg-gray-700">add_action</code> e <code class="rounded bg-gray-200 px-1 dark:bg-gray-700">add_filter</code> (menu, validação, listagens, telas, models).</li>
                <li><strong>Ative em Configurações → Plugins</strong> e teste ativar/desativar para validar isolamento.</li>
            </ol>

            <h3 class="mb-2 text-lg font-semibold text-gray-800 dark:text-white/90">Estrutura recomendada</h3>
            <pre class="mb-4 overflow-x-auto rounded-lg border border-gray-200 bg-gray-50 p-3 text-xs dark:border-gray-700 dark:bg-gray-900"><code>plugins/
  meu-plugin/
    plugin.php
    routes.php
    views/
      index.blade.php
      settings.blade.php
    src/
      MeuPluginController.php
      Models/
        MinhaEntidade.php</code></pre>

            <h3 class="mb-2 text-lg font-semibold text-gray-800 dark:text-white/90">Exemplo mínimo de <code class="rounded bg-gray-200 px-1 dark:bg-gray-700">plugin.php</code></h3>
            <pre class="mb-4 overflow-x-auto rounded-lg border border-gray-200 bg-gray-50 p-3 text-xs dark:border-gray-700 dark:bg-gray-900"><code>&lt;?php
/**
 * Plugin Name: Meu Plugin
 * Description: Exemplo simples.
 * Version: 1.0.0
 */

add_filter('menu.items', function ($items) {
    $items[] = [
        'name' =&gt; 'Meu Plugin',
        'icon' =&gt; 'task',
        'subItems' =&gt; [
            ['name' =&gt; 'Página', 'path' =&gt; '/plugin/meu-plugin', 'pro' =&gt; false],
        ],
    ];
    return $items;
}, 20);</code></pre>

            <h3 class="mb-2 text-lg font-semibold text-gray-800 dark:text-white/90">Exemplo mínimo de <code class="rounded bg-gray-200 px-1 dark:bg-gray-700">routes.php</code></h3>
            <pre class="mb-4 overflow-x-auto rounded-lg border border-gray-200 bg-gray-50 p-3 text-xs dark:border-gray-700 dark:bg-gray-900"><code>&lt;?php
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return erp_view('meu-plugin::index', ['title' =&gt; 'Meu Plugin']);
})-&gt;middleware('operational')-&gt;name('index');</code></pre>

            <h3 class="mb-2 text-lg font-semibold text-gray-800 dark:text-white/90">Hooks mais usados</h3>
            <ul class="mb-4 list-inside list-disc space-y-1">
                <li><code class="rounded bg-gray-200 px-1 dark:bg-gray-700">menu.items</code> — adicionar itens no menu lateral.</li>
                <li><code class="rounded bg-gray-200 px-1 dark:bg-gray-700">request.validation.rules</code> — estender validações.</li>
                <li><code class="rounded bg-gray-200 px-1 dark:bg-gray-700">entity.index.query</code> — alterar query de listagens.</li>
                <li><code class="rounded bg-gray-200 px-1 dark:bg-gray-700">model.created / model.updated</code> — reagir a eventos dos models.</li>
                <li><code class="rounded bg-gray-200 px-1 dark:bg-gray-700">configuracoes.plugins.settings_view</code> — exibir tela de configurações do plugin.</li>
            </ul>

            <h3 class="mb-2 text-lg font-semibold text-gray-800 dark:text-white/90">Checklist de qualidade</h3>
            <ul class="mb-4 list-inside list-disc space-y-1">
                <li>Não adicionar rota do plugin no core (<code class="rounded bg-gray-200 px-1 dark:bg-gray-700">routes/web.php</code>).</li>
                <li>Não referenciar classe do plugin diretamente no core.</li>
                <li>Usar hooks genéricos e filtros do sistema.</li>
                <li>Testar ativação/desativação sem quebrar o ERP.</li>
                <li>Documentar no README do plugin e versionar corretamente.</li>
            </ul>

            <p class="text-sm text-gray-600 dark:text-gray-400">Documentação técnica completa: <code class="rounded bg-gray-200 px-1 dark:bg-gray-700">docs/CRIANDO-PLUGINS.md</code> e <code class="rounded bg-gray-200 px-1 dark:bg-gray-700">docs/PLUGINS.md</code>.</p>
        </section>

        {{-- Seção 10: Dicas --}}
        <section id="dicas" class="scroll-mt-8">
            <h2 class="mb-4 border-b border-gray-200 pb-2 text-2xl font-bold text-gray-800 dark:border-gray-700 dark:text-white/90">Dicas rápidas</h2>
            <ul class="list-inside list-disc space-y-2">
                <li><strong>Salve sempre:</strong> após preencher um formulário, clique em Salvar ou Cadastrar. Sair da página sem salvar pode fazer perder as alterações.</li>
                <li><strong>Use a busca:</strong> nas listas (clientes, produtos, serviços, OS, contas a receber/pagar), use o campo de pesquisa no topo para achar por nome, documento, código ou descrição.</li>
                <li><strong>Filtros:</strong> use filtros por data, status, cliente, categoria para ver só o que interessa e agilizar o trabalho.</li>
                <li><strong>Notificações:</strong> o ícone de sino no topo exibe notificações do sistema (ex.: contas vencendo, OS atualizadas). Confira de vez em quando.</li>
                <li><strong>Alterar senha:</strong> no menu do seu usuário (canto superior direito) use “Alterar senha”. Prefira uma senha forte e que só você conheça.</li>
                <li><strong>Tema claro/escuro:</strong> o botão de tema no topo alterna entre aparência clara e escura; a preferência é guardada.</li>
                <li><strong>Central de Ajuda:</strong> use o índice no topo desta página para ir direto ao assunto (Clientes, OS, Financeiro, etc.).</li>
                <li><strong>Cadastre antes de usar:</strong> cadastre cliente antes de abrir OS ou pedido de venda; cadastre produtos e serviços antes de incluir nos itens da OS ou do pedido; cadastre contas bancárias e formas de pagamento antes de dar baixa em contas.</li>
                <li><strong>Vendas:</strong> use <strong>Pedidos de venda</strong> para orçamentos e vendas formais; <strong>Propostas comerciais</strong> para documentos e modelos personalizados. Confira permissões se algum botão não aparecer.</li>
                <li><strong>Encerrar OS:</strong> ao concluir o atendimento, use o botão Encerrar OS para gerar as contas a receber (se configurado) e bloquear alterações indevidas.</li>
                <li><strong>Dúvidas:</strong> se algo não funcionar ou uma opção não aparecer, anote a tela e o que estava fazendo e fale com o administrador ou com o suporte técnico.</li>
            </ul>
        </section>
    </div>

    <div class="mt-12 rounded-xl border border-gray-200 bg-gray-50 p-6 text-center dark:border-gray-700 dark:bg-gray-800/50">
        <p class="text-sm text-gray-600 dark:text-gray-400">Você está na Central de Ajuda. Use o índice no topo para pular para o assunto que precisar.</p>
    </div>
</div>
@endsection
