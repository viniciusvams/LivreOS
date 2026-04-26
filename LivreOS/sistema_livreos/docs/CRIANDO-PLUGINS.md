# Guia completo para desenvolvedores: como criar plugins para o ERP

Este documento é um guia completo para criar plugins no ERP. A extensão do sistema acontece por **hooks** (actions e filters), sem modificar o código do core. Toda a lógica do plugin fica isolada em `plugins/` e pode ser ativada ou desativada sem quebrar o sistema.

---

## Índice

1. [Conceitos fundamentais](#1-conceitos-fundamentais)
2. [Estrutura de um plugin](#2-estrutura-de-um-plugin)
3. [Arquivo obrigatório: plugin.php e metadados](#3-arquivo-obrigatório-pluginphp-e-metadados)
4. [Ciclo de vida: ativação, desativação e desinstalação](#4-ciclo-de-vida-ativação-desativação-e-desinstalação)
5. [Hooks: actions e filters](#5-hooks-actions-e-filters)
6. [Rotas do plugin](#6-rotas-do-plugin)
7. [Views e namespaces](#7-views-e-namespaces)
8. [Adicionando itens ao menu](#8-adicionando-itens-ao-menu)
9. [Options API: armazenando configurações](#9-options-api-armazenando-configurações)
10. [Página de configurações do plugin](#10-página-de-configurações-do-plugin)
11. [Validação de formulários e listagens](#11-validação-de-formulários-e-listagens)
12. [Estendendo formulários e telas de detalhe](#12-estendendo-formulários-e-telas-de-detalhe)
13. [Ciclo de vida dos models (Eloquent)](#13-ciclo-de-vida-dos-models-eloquent)
14. [Helpers e utilitários](#14-helpers-e-utilitários)
15. [Transients (cache)](#15-transients-cache)
16. [Princípio de isolamento e boas práticas](#16-princípio-de-isolamento-e-boas-práticas)
17. [Exemplo passo a passo: do zero ao plugin funcional](#17-exemplo-passo-a-passo-do-zero-ao-plugin-funcional)
18. [Referência rápida de hooks](#18-referência-rápida-de-hooks)
19. [Resolução de problemas](#19-resolução-de-problemas)

---

## 1. Conceitos fundamentais

### O que é um plugin?

Um plugin é uma **extensão** do ERP que:

- Fica em uma pasta própria em `plugins/{slug}/` (o **slug** é o nome da pasta, por exemplo `meu-plugin`).
- Possui um arquivo obrigatório `plugin.php` com metadados no cabeçalho.
- Usa **hooks** para se integrar ao sistema: **actions** (executar código em um ponto) e **filters** (modificar dados antes do uso).
- Pode ter rotas, views, classes PHP e assets sem que o core precise conhecê-lo pelo nome.

### Actions vs Filters

| Tipo      | Função no core     | Função no plugin   | Retorno   |
|----------|--------------------|--------------------|-----------|
| **Action** | `do_action('hook', $arg)` | `add_action('hook', function($arg) { ... })` | Nenhum. Só executa código. |
| **Filter** | `$x = apply_filters('hook', $valor)` | `add_filter('hook', function($valor) { return $valorModificado; })` | O callback **deve** retornar o valor (modificado ou não). |

O core **não sabe** que seu plugin existe: ele apenas dispara hooks. Seu plugin **escuta** esses hooks e adiciona ou altera comportamento. Assim, ao desativar o plugin, o sistema continua funcionando normalmente.

### Ativação de plugins

Os plugins ativos são listados em `storage/app/plugins_active.json`. A ativação e desativação podem ser feitas pela interface em **Configurações do Sistema → Plugins** (quando o usuário tem permissão `manage-plugins`). Via código:

```php
use App\Services\PluginManager;

PluginManager::instance()->activate('meu-plugin');
PluginManager::instance()->deactivate('meu-plugin');
```

---

## 2. Estrutura de um plugin

Estrutura mínima e recomendada:

```
plugins/
  meu-plugin/              # Slug do plugin (use hífens, sem espaços)
    plugin.php             # Obrigatório: bootstrap e metadados no cabeçalho
    routes.php             # Opcional: rotas (prefixo automático /plugin/meu-plugin)
    views/                 # Opcional: views Blade (namespace: meu-plugin::)
      pagina.blade.php
      settings.blade.php
    src/                   # Opcional: classes PHP (você pode usar PSR-4 com autoload)
      MeuPluginController.php
      Models/
        MinhaEntidade.php
    autoload.php           # Opcional: require das classes ou mapa de autoload
    uninstall.php          # Opcional: script executado na desinstalação (além do hook)
    README.md              # Opcional: documentação do plugin
```

Regras importantes:

- O **slug** é o nome da pasta (ex.: `meu-plugin`). Use apenas letras minúsculas, números e hífens.
- Toda URL do plugin será prefixada com `/plugin/meu-plugin/`.
- Views do plugin são referenciadas como `meu-plugin::nome-da-view` (o namespace é o slug).

---

## 3. Arquivo obrigatório: plugin.php e metadados

O arquivo `plugin.php` é o ponto de entrada. No **cabeçalho** (comentários PHP) você declara os metadados exibidos na tela de Plugins.

### Exemplo completo de cabeçalho

```php
<?php
/**
 * Plugin Name: Nome Amigável do Plugin
 * Description: Descrição curta do que o plugin faz. Aparece na listagem de plugins.
 * Version: 1.0.0
 * Author: Seu Nome ou Empresa
 * Requires PHP: 8.1
 * Requires Plugins: outro-plugin, area-cliente
 */
```

- **Plugin Name**: obrigatório. Nome exibido na interface.
- **Description**: recomendado. Texto curto explicando a funcionalidade.
- **Version**: recomendado. Formato semver (ex.: 1.0.0).
- **Author**: opcional.
- **Requires PHP**: opcional. Versão mínima de PHP (ex.: 8.1). Se não cumprir, o plugin não é carregado.
- **Requires Plugins**: opcional. Lista de slugs de outros plugins separados por vírgula. Se algum não estiver ativo, a ativação falha.

Após o cabeçalho, você pode registrar hooks de ciclo de vida e usar `add_action` / `add_filter`. Exemplo mínimo:

```php
<?php
/**
 * Plugin Name: Meu Primeiro Plugin
 * Description: Apenas um exemplo.
 * Version: 1.0.0
 */

// Registrar um item no menu
add_filter('menu.items', function ($items) {
    $items[] = [
        'name'   => 'Meu Plugin',
        'icon'   => 'task',
        'subItems' => [
            ['name' => 'Página do Plugin', 'path' => '/plugin/meu-plugin', 'pro' => false],
        ],
    ];
    return $items;
}, 20);
```

Não é obrigatório ter rotas ou views: um plugin pode só modificar o menu, a validação ou os dados de uma view via filters.

---

## 4. Ciclo de vida: ativação, desativação e desinstalação

O sistema chama callbacks em três momentos. Eles devem ser registrados no `plugin.php` com as funções globais.

### 4.1 Ativação (`erp_register_activation_hook`)

Executado **uma vez**, quando o plugin é ativado. Use para:

- Criar tabelas no banco
- Inserir dados iniciais (seeds)
- Adicionar colunas em tabelas existentes (com cuidado e checando se já existem)
- Gravar opções padrão

**Exemplo: criar tabela ao ativar**

```php
erp_register_activation_hook(__FILE__, function () {
    if (!\Illuminate\Support\Facades\Schema::hasTable('plugin_meu_plugin_registros')) {
        \Illuminate\Support\Facades\Schema::create('plugin_meu_plugin_registros', function (\Illuminate\Database\Schema\Blueprint $table) {
            $table->id();
            $table->string('nome');
            $table->text('descricao')->nullable();
            $table->timestamps();
        });
    }
});
```

Sempre use o prefixo `plugin_{slug}_` nas tabelas (substituindo hífens por underscores) para evitar conflito com tabelas do core. Verifique com `Schema::hasTable()` e `Schema::hasColumn()` antes de criar ou alterar.

### 4.2 Desativação (`erp_register_deactivation_hook`)

Executado quando o plugin é **desativado**. Use para:

- Limpar cache (transients)
- Pausar jobs ou agendamentos
- Não remover tabelas nem dados (o usuário pode reativar depois)

**Exemplo**

```php
erp_register_deactivation_hook(__FILE__, function () {
    if (function_exists('delete_transient')) {
        delete_transient('meu_plugin_cache');
    }
});
```

### 4.3 Desinstalação (`erp_register_uninstall_hook`)

Executado quando o plugin é **excluído** (antes de remover os arquivos). Use para:

- Dropar tabelas do plugin
- Remover opções em `storage/app/plugin_options/{slug}.json`
- Limpar arquivos em `storage` gerados pelo plugin

**Exemplo**

```php
erp_register_uninstall_hook(__FILE__, function () {
    \Illuminate\Support\Facades\Schema::dropIfExists('plugin_meu_plugin_registros');
    $path = storage_path('app/plugin_options/meu-plugin.json');
    if (file_exists($path)) {
        @unlink($path);
    }
});
```

Você também pode ter um arquivo `uninstall.php` na raiz do plugin. Ele é incluído durante o processo de desinstalação; pode chamar a mesma lógica de limpeza. O hook `erp_register_uninstall_hook` é a forma preferida para manter tudo no `plugin.php`.

---

## 5. Hooks: actions e filters

### 5.1 Como funcionam

- **Actions**: o core chama `do_action('nome.do.hook', $arg1, $arg2)`. Todo callback registrado com `add_action('nome.do.hook', ...)` é executado. Nada é retornado.
- **Filters**: o core chama `$valor = apply_filters('nome.do.hook', $valor, $arg1)`. Cada callback registrado com `add_filter('nome.do.hook', ...)` recebe o valor e retorna um novo valor (que será passado ao próximo). O último retorno é o que o core usa.

### 5.2 Prioridade

Tanto `add_action` quanto `add_filter` aceitam um terceiro parâmetro opcional: **prioridade** (inteiro, padrão 10). Callbacks com prioridade menor são executados primeiro.

```php
add_filter('menu.items', function ($items) {
    $items[] = ['name' => 'Primeiro', 'path' => '/primeiro'];
    return $items;
}, 5);

add_filter('menu.items', function ($items) {
    $items[] = ['name' => 'Depois', 'path' => '/depois'];
    return $items;
}, 20);
```

### 5.3 Exemplo: action (apenas executar código)

```php
add_action('model.created', function ($model) {
    if ($model instanceof \App\Models\Cliente) {
        \Illuminate\Support\Facades\Log::info('Novo cliente criado: ' . $model->id);
    }
});
```

### 5.4 Exemplo: filter (modificar dados)

```php
add_filter('menu.items', function ($items) {
    if (!auth()->check() || !auth()->user()->canAccessOperational()) {
        return $items;
    }
    $items[] = [
        'name'      => 'Relatórios',
        'icon'      => 'charts',
        'subItems'  => [
            ['name' => 'Meu Relatório', 'path' => '/plugin/meu-plugin/relatorio', 'pro' => false],
        ],
    ];
    return $items;
}, 20);
```

Sempre retorne o valor no filter; se não retornar, o valor passado ao próximo callback pode ser `null`.

### 5.5 Verificar se as funções existem

Em ambientes onde o core pode não ter carregado os helpers (ex.: alguns crons), use `function_exists`:

```php
if (function_exists('add_filter')) {
    add_filter('menu.items', function ($items) {
        // ...
        return $items;
    }, 20);
}
```

---

## 6. Rotas do plugin

Se o seu plugin tiver telas próprias, crie o arquivo `routes.php` na raiz do plugin. As rotas são registradas automaticamente com:

- **Prefixo de URL**: `/plugin/{slug}/`
- **Prefixo de nome**: `plugin.{slug}.`

Exemplo: para o plugin `meu-plugin`, a rota nomeada `pagina` vira `plugin.meu-plugin.pagina` e a URL base é `/plugin/meu-plugin/`.

### 6.1 Exemplo básico de routes.php

```php
<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return erp_view('meu-plugin::pagina', ['title' => 'Página do Plugin']);
})->middleware('operational')->name('pagina');

Route::get('/relatorio', function () {
    return erp_view('meu-plugin::relatorio', ['title' => 'Relatório']);
})->middleware('operational')->name('relatorio');
```

- Acessos: `https://seusite.com/plugin/meu-plugin` e `https://seusite.com/plugin/meu-plugin/relatorio`.
- Nomes das rotas: `route('plugin.meu-plugin.pagina')` e `route('plugin.meu-plugin.relatorio')`.

### 6.2 Usando um controller

Se você tiver classes em `src/`, faça o require no `plugin.php` (ou use autoload) e referencie o controller no `routes.php`:

```php
// routes.php
Route::get('/', [\MeuPlugin\MeuPluginController::class, 'index'])->middleware('operational')->name('pagina');
Route::get('/criar', [\MeuPlugin\MeuPluginController::class, 'create'])->middleware('operational')->name('criar');
Route::post('/criar', [\MeuPlugin\MeuPluginController::class, 'store'])->middleware('operational')->name('store');
```

No controller, use o namespace do plugin (ex.: `MeuPlugin`) para não colidir com o core.

### 6.3 Middlewares comuns

- `web`: já aplicado a todas as rotas de plugin (sessão, CSRF).
- `operational`: usuário logado e com acesso operacional (ou admin).
- `auth`: apenas exige login.
- Permissões: `permission:nome-da-permissao` (quando o sistema tiver a permissão cadastrada).

### 6.4 Regra de ouro das rotas

Todas as URLs e rotas do seu plugin devem estar no **próprio plugin** (em `routes.php`). O core **não** deve definir rotas que apontem para classes do seu plugin. Assim, ao desativar o plugin, essas rotas deixam de existir e não haverá erro "class not found" no sistema principal.

---

## 7. Views e namespaces

As views do plugin ficam na pasta `views/` do plugin. O Laravel registra o namespace com o **slug** do plugin. Para renderizar, use o nome da view no formato `{slug}::nome-da-view`.

### 7.1 Estrutura de pastas de views

```
plugins/meu-plugin/views/
  pagina.blade.php
  relatorio.blade.php
  settings.blade.php
  partials/
    cabecalho.blade.php
```

### 7.2 Renderizando no plugin

Use `erp_view()` em vez de `view()` para que o core e outros plugins possam alterar os dados via hooks `view.data` e `view.render.before`:

```php
return erp_view('meu-plugin::pagina', [
    'title' => 'Título',
    'lista' => $lista,
]);
```

Para partials:

```blade
@include('meu-plugin::partials.cabecalho', ['titulo' => $titulo])
```

### 7.3 Usando o layout do sistema

Para manter o mesmo layout (sidebar, header) do ERP:

```blade
@extends('layouts.app')

@section('content')
<div class="p-6">
    <h1 class="text-2xl font-bold mb-4">{{ $title }}</h1>
    <p>Conteúdo do plugin.</p>
</div>
@endsection
```

O layout `layouts.app` é do core; suas views só precisam estender e preencher as seções.

---

## 8. Adicionando itens ao menu

O menu lateral é montado a partir do filter `menu.items`. Cada item pode ter `name`, `icon` e `subItems` (array de itens com `name`, `path`, e opcionalmente `pro`).

### 8.1 Estrutura de um item de menu

```php
[
    'name'      => 'Nome do Grupo',
    'icon'      => 'task',   // ícone (conforme conjunto de ícones do sistema)
    'subItems'  => [
        ['name' => 'Subitem 1', 'path' => '/caminho/1', 'pro' => false],
        ['name' => 'Subitem 2', 'path' => '/plugin/meu-plugin/pagina', 'pro' => false],
    ],
]
```

- `path`: URL absoluta ou relativa (ex.: `/plugin/meu-plugin`).
- `pro`: opcional; usado pelo tema para marcar itens “pro”.

### 8.2 Exemplo: adicionar um grupo ao menu

```php
add_filter('menu.items', function ($items) {
    if (!auth()->check() || !auth()->user()->canAccessOperational()) {
        return $items;
    }
    $items[] = [
        'name'     => 'Meu Plugin',
        'icon'     => 'task',
        'subItems' => [
            ['name' => 'Página principal', 'path' => '/plugin/meu-plugin', 'pro' => false],
            ['name' => 'Relatório', 'path' => '/plugin/meu-plugin/relatorio', 'pro' => false],
        ],
    ];
    return $items;
}, 20);
```

### 8.3 Exemplo: adicionar subitem a um grupo existente

Se você quiser colocar um link dentro de um grupo já existente (ex.: “Financeiro”), é preciso encontrar esse grupo no array, adicionar o subitem e retornar o array modificado. O core não expõe um hook “adicionar subitem ao grupo X”; você trabalha no array completo de `menu.items`.

### 8.4 Menu para um tipo de usuário (ex.: portal do cliente)

O plugin “Área do Cliente” substitui todo o menu quando o usuário é um cliente do portal: no filter `menu.items`, verifica se o usuário tem `cliente_id` e se o cliente tem portal habilitado; nesse caso retorna apenas os itens do portal (prioridade 5). Assim, usuários do sistema veem o menu normal (outros filters com prioridade maior) e clientes do portal veem só o menu do plugin.

---

## 9. Options API: armazenando configurações

O sistema oferece uma API de opções por plugin. As opções são salvas em `storage/app/plugin_options/{slug}.json`.

### 9.1 Funções disponíveis

- `get_option($key, $default = null, $slug = null)`  
  Lê uma opção do plugin atual (o slug é inferido quando o código roda no contexto do plugin). Em cron/job, use `get_plugin_option($slug, $key, $default)`.

- `get_plugin_option($slug, $key, $default = null)`  
  Lê opção de um plugin pelo slug. Use em comandos, jobs e crons.

- `add_option($key, $value, $slug = null)`  
  Adiciona opção se a chave não existir. Retorna `true` se inseriu.

- `update_option($key, $value, $slug = null)`  
  Cria ou atualiza a opção. Use para salvar configurações.

- `delete_option($key, $slug = null)`  
  Remove uma opção.

O “plugin atual” é definido pelo contexto de carregamento (variável global usada pelo PluginManager). Dentro de um controller ou view carregados pelas rotas do plugin, o slug costuma estar correto. Fora disso (ex.: comando Artisan), use sempre `get_plugin_option('meu-plugin', 'chave', default)`.

### 9.2 Exemplo: salvar e ler API key

```php
// Ao salvar configuração (ex.: em um controller do plugin)
update_option('api_key', $request->input('api_key'));

// Ao usar em outro lugar
$apiKey = get_option('api_key', '');
if ($apiKey === '') {
    return redirect()->back()->with('error', 'Configure a API key nas configurações do plugin.');
}
```

### 9.3 Exemplo: em um comando (fora do contexto do plugin)

```php
$apiKey = get_plugin_option('meu-plugin', 'api_key', '');
```

---

## 10. Página de configurações do plugin

Quando o usuário abre um plugin em **Configurações do Sistema → Plugins** e clica em um plugin ativo, o sistema exibe a página de detalhe do plugin. Se o plugin quiser uma tela de configuração própria, ele usa o filter `configuracoes.plugins.settings_view` para informar qual view mostrar.

### 10.1 Registrar a view de configurações

No `plugin.php`:

```php
add_filter('configuracoes.plugins.settings_view', function ($view, $plugin) {
    if (($plugin['slug'] ?? '') === 'meu-plugin') {
        return 'meu-plugin::settings';
    }
    return $view;
}, 10, 2);
```

Assim, ao abrir o plugin “Meu Plugin” na tela de Plugins, o conteúdo da view `meu-plugin::settings` é exibido (geralmente dentro do layout da página de configurações).

### 10.2 Conteúdo da view settings

Na view `views/settings.blade.php` do plugin você pode:

- Exibir opções atuais (lidas com `get_option`).
- Incluir um formulário que envia para uma rota **do próprio plugin** (ex.: `POST /plugin/meu-plugin/configuracoes/salvar`), e no controller do plugin você chama `update_option` e redireciona com mensagem de sucesso.

O core não processa o POST das configurações do plugin; isso é responsabilidade das rotas e controllers do plugin.

### 10.3 add_options_page e register_setting

Para integrar com um futuro painel unificado de opções (se o core implementar), existem as funções:

- `add_options_page($pageTitle, $menuTitle, $routeName, $pluginSlug)`  
  Registra uma página de opções no menu de configurações.

- `register_setting($pluginSlug, $optionKey, $args)`  
  Registra um campo (ex.: `type`, `default`). Útil para documentar e para futuras UIs genéricas.

Por enquanto, a forma mais comum é usar o filter `configuracoes.plugins.settings_view` e uma view própria com formulário e rota de salvamento no plugin.

---

## 11. Validação de formulários e listagens

### 11.1 Adicionar regras de validação (request.validation.rules)

Os FormRequests do ERP usam um filter para permitir que plugins acrescentem ou alterem regras. O filter recebe: `$rules` (array), `$routeName` (string), `$request` (o Request).

Exemplo: adicionar campo `portal_suporte` no cadastro de cliente:

```php
add_filter('request.validation.rules', function ($rules, $routeName) {
    if (in_array($routeName, ['clientes.store', 'clientes.update'], true)) {
        $rules['portal_suporte'] = 'boolean';
    }
    return $rules;
}, 10, 2);
```

Nos controllers que usam validação inline (não FormRequest), o core usa `validateWithFilters($request, $rules)`, que também passa pelo mesmo tipo de filtro, permitindo que plugins alterem as regras.

### 11.2 Modificar a query de listagens (entity.index.query)

Todos os controllers de listagem aplicam o filter `entity.index.query`, passando a **query** (Builder) e o **nome da entidade** (string). Entidades comuns: `cliente`, `produto`, `servico`, `fornecedor`, `ordem_servico`, `conta_receber`, `conta_pagar`, `movimentacao_financeira`, `user`, `role`, `permission`, etc.

Exemplo: filtrar apenas clientes com um atributo customizado (ex.: “premium”):

```php
add_filter('entity.index.query', function ($query, $entity) {
    if ($entity !== 'cliente') {
        return $query;
    }
    if (request()->boolean('apenas_premium')) {
        $query->where('premium', true);
    }
    return $query;
}, 10, 2);
```

Sempre retorne a query no filter.

---

## 12. Estendendo formulários e telas de detalhe

O core dispara **actions** em formulários (create/edit) e em telas de detalhe (show). Seu plugin pode adicionar blocos de HTML ou campos extras.

### 12.1 Formulários (form.extra)

Cada entidade tem um hook no formato `admin.{entidade}.form.extra` ou `financeiro.{entidade}.form.extra`. O parâmetro é o model da entidade (ou `null` no create).

Exemplos de hooks:

- `admin.clientes.form.extra` → parâmetro: `$cliente` ou `null`
- `admin.clientes.form.portal` → específico para “portal” no cadastro de cliente
- `admin.fornecedores.form.extra` → `$fornecedor` ou `null`
- `admin.produtos.form.extra` → `$produto` ou `null`
- `admin.servicos.form.extra` → `$servico` ou `null`
- `ordens_servico.form.extra` → `$ordem` ou `null`
- `financeiro.contas-pagar.form.extra` → `$conta` ou `null`
- `financeiro.contas-receber.form.extra` → `$conta` ou `null`
- `admin.roles.form.extra` → `$role` ou `null`
- `admin.users.form.extra` → não listado no PLUGINS.md mas existe para usuários

Exemplo: adicionar um bloco no formulário de cliente:

```php
add_action('admin.clientes.form.portal', function ($cliente) {
    $valor = $cliente ? ($cliente->portal_suporte ?? false) : old('portal_suporte', false);
    ?>
    <div class="mb-4">
        <label class="flex items-center gap-2">
            <input type="checkbox" name="portal_suporte" value="1" <?= $valor ? 'checked' : '' ?>>
            <span>Portal do cliente / Portal suporte</span>
        </label>
    </div>
    <?php
});
```

Para não poluir o `plugin.php`, você pode incluir um arquivo PHP:

```php
add_action('admin.clientes.form.portal', function ($cliente) {
    include __DIR__ . '/views/cliente-form-portal.php';
});
```

Nesse arquivo você usa as variáveis `$cliente`, `old()`, etc.

### 12.2 Persistir campos extras

Só adicionar o campo no formulário não grava no banco. Você precisa:

1. Incluir o campo no **fillable** (ou equivalente) do model via filter, por exemplo `cliente.fillable`, `user.fillable`.
2. Incluir regras de validação via `request.validation.rules` para as rotas de store/update.
3. Garantir que o controller do core (ou um filter de dados) envie esse campo para o model no store/update. Alguns controllers usam filters como `admin.user.store.data` e `admin.user.update.data` para que plugins acrescentem dados extras ao array de criação/atualização.

Exemplo (campo `cliente_id` no User):

```php
add_filter('user.fillable', function ($default) {
    $default[] = 'cliente_id';
    return $default;
});
add_filter('admin.user.store.rules', function ($rules) {
    $rules['cliente_id'] = 'nullable|exists:clientes,id';
    return $rules;
});
add_filter('admin.user.store.data', function ($extra, $request) {
    $extra['cliente_id'] = $request->filled('cliente_id') ? $request->input('cliente_id') : null;
    return $extra;
}, 10, 2);
```

### 12.3 Telas de detalhe (show.extra)

Hooks no formato `admin.{entidade}.show.extra` ou `{contexto}.{entidade}.show.extra` recebem o model e permitem imprimir seções extras na tela de detalhe.

Exemplos: `admin.clientes.show.extra`, `admin.clientes.show.portal`, `admin.produtos.show.extra`, `ordens_servico.show.extra`, etc.

```php
add_action('admin.clientes.show.portal', function ($cliente) {
    ?>
    <div class="mt-4 p-4 bg-gray-100 rounded">
        <strong>Portal:</strong> <?= ($cliente->portal_suporte ?? false) ? 'Sim' : 'Não' ?>
    </div>
    <?php
});
```

---

## 13. Ciclo de vida dos models (Eloquent)

O `PluginServiceProvider` registra listeners globais para os eventos Eloquent: `creating`, `created`, `updating`, `updated`, `saving`, `saved`, `deleting`, `deleted`. Cada evento é mapeado para um hook:

- `model.creating`, `model.created`
- `model.updating`, `model.updated`
- `model.saving`, `model.saved`
- `model.deleting`, `model.deleted`

O parâmetro do callback é sempre o **model** (a instância).

Exemplo: logar quando um cliente é criado:

```php
add_action('model.created', function ($model) {
    if ($model instanceof \App\Models\Cliente) {
        \Illuminate\Support\Facades\Log::info('Cliente criado: ' . $model->id);
    }
});
```

Exemplo: enviar e-mail quando o status de uma Ordem de Serviço muda (model.updated + wasChanged('status')).

---

## 14. Helpers e utilitários

Funções globais úteis para plugins:

### 14.1 Caminhos e URLs

- **plugin_dir_path($pluginFile)**  
  Retorna o diretório do plugin (ex.: `.../plugins/meu-plugin/`). Passe `__FILE__` quando estiver no `plugin.php`.

- **plugin_dir_url($pluginFile)**  
  Retorna a URL base do plugin (ex.: `https://seusite.com/plugin/meu-plugin/`). Útil para referenciar assets. Os assets costumam ser servidos por uma rota que o plugin registra (ex.: `/plugin/meu-plugin/assets/...`).

- **plugin_basename($pluginFile)**  
  Retorna algo como `meu-plugin/plugin.php`.

- **erp_current_plugin_slug()**  
  Retorna o slug do plugin no contexto atual (quando definido pelo carregador).

### 14.2 Avisos na área admin

- **add_admin_notice($message, $type = 'info')**  
  Adiciona uma mensagem ao array de avisos exibidos na área admin. Tipos: `success`, `error`, `warning`, `info`.

Exemplo:

```php
add_admin_notice('Configurações do Meu Plugin salvas com sucesso.', 'success');
```

### 14.3 Remover hooks

- **remove_action($hook, $callback, $priority = 10)**  
  Remove um callback previamente registrado em uma action (precisa ser a mesma instância de closure ou referência exata).

- **remove_filter($hook, $callback, $priority = 10)**  
  Idem para filter.

Uso típico: guardar a referência do callback para poder removê-lo depois em condições específicas.

---

## 15. Transients (cache)

Para cache temporário por plugin, use transients. Eles são armazenados no sistema de cache do Laravel, com chave prefixada pelo plugin.

- **set_transient($key, $value, $expiration = 0)**  
  Salva o valor. `$expiration` em segundos; 0 = sem expiração (até limpar manualmente).

- **get_transient($key)**  
  Lê o valor.

- **delete_transient($key)**  
  Remove o valor.

Exemplo:

```php
$cache = get_transient('minha_lista');
if ($cache === null) {
    $cache = MinhaClasse::buscarListaPesada();
    set_transient('minha_lista', $cache, 3600); // 1 hora
}
```

---

## 16. Princípio de isolamento e boas práticas

### 16.1 Isolamento

- O **core não deve referenciar** seu plugin por nome (rotas como `plugin.meu-plugin.xyz`, classes como `MeuPlugin\Algo`). O core só expõe hooks genéricos e rotas genéricas (ex.: `/configuracoes-sistema/plugins/{slug}`).
- **Todas as rotas do plugin** devem estar em `plugins/{slug}/routes.php`. Ao desativar, essas rotas deixam de existir.
- **Links para o plugin** no core devem usar filtros. Ex.: o core chama `apply_filters('header.user.portal_profile', null, $context)`; se o plugin “Área do Cliente” estiver ativo, ele retorna `['path' => ..., 'label' => ...]`. O core não usa `route('plugin.area-cliente.perfil')` diretamente.
- Ao **desativar o plugin**, o sistema principal deve continuar funcionando. Nenhuma rota do core pode depender de uma classe do plugin.

### 16.2 Boas práticas

1. Use **namespace** próprio para classes (ex.: `MeuPlugin\`) para evitar conflito com o core e outros plugins.
2. **Sempre verifique** `function_exists('add_filter')` (e semelhantes) se o código puder rodar fora do request web.
3. Use **prioridades** nos hooks quando a ordem importar (padrão 10).
4. **Não modifique o core** para implementar funcionalidade do plugin; peça hooks no core e implemente no plugin.
5. Em **ativação**, verifique se tabelas/colunas já existem antes de criar (Schema::hasTable, Schema::hasColumn).
6. **Nomeie tabelas** com prefixo `plugin_{slug}_` (ex.: `plugin_meu_plugin_log`).
7. Para **Contas a Receber/Pagar** criadas pelo plugin, use os campos de controle: `origem_tipo`, `entidade_tipo`, `entidade_id`, `metadata`, `referencia_externa` (ver PLUGINS.md). Assim relatórios e integrações permanecem consistentes.

---

## 17. Exemplo passo a passo: do zero ao plugin funcional

Vamos criar um plugin “Minha Lista” que: (1) adiciona um item no menu, (2) tem uma página listando itens, (3) grava itens em uma tabela própria e (4) usa opções e uma página de configurações.

### Passo 1: Estrutura de pastas

Crie:

```
plugins/minha-lista/
  plugin.php
  routes.php
  views/
    index.blade.php
    settings.blade.php
  src/
    MinhaListaController.php
    Models/
      ItemLista.php
```

### Passo 2: plugin.php (metadados e hooks)

```php
<?php
/**
 * Plugin Name: Minha Lista
 * Description: Mantenha uma lista simples de itens com data e observação.
 * Version: 1.0.0
 * Author: Dev Comunidade
 * Requires PHP: 8.1
 */

// Autoload das classes (simples require)
require_once __DIR__ . '/src/Models/ItemLista.php';
require_once __DIR__ . '/src/MinhaListaController.php';

erp_register_activation_hook(__FILE__, function () {
    if (!\Illuminate\Support\Facades\Schema::hasTable('plugin_minha_lista_itens')) {
        \Illuminate\Support\Facades\Schema::create('plugin_minha_lista_itens', function (\Illuminate\Database\Schema\Blueprint $table) {
            $table->id();
            $table->string('titulo');
            $table->date('data')->nullable();
            $table->text('observacao')->nullable();
            $table->timestamps();
        });
    }
});

erp_register_uninstall_hook(__FILE__, function () {
    \Illuminate\Support\Facades\Schema::dropIfExists('plugin_minha_lista_itens');
});

add_filter('configuracoes.plugins.settings_view', function ($view, $plugin) {
    if (($plugin['slug'] ?? '') === 'minha-lista') {
        return 'minha-lista::settings';
    }
    return $view;
}, 10, 2);

add_filter('menu.items', function ($items) {
    if (!auth()->check() || !auth()->user()->canAccessOperational()) {
        return $items;
    }
    $items[] = [
        'name'     => 'Minha Lista',
        'icon'     => 'task',
        'subItems' => [
            ['name' => 'Ver lista', 'path' => '/plugin/minha-lista', 'pro' => false],
        ],
    ];
    return $items;
}, 20);
```

### Passo 3: Model ItemLista (src/Models/ItemLista.php)

```php
<?php

namespace MinhaLista\Models;

use Illuminate\Database\Eloquent\Model;

class ItemLista extends Model
{
    protected $table = 'plugin_minha_lista_itens';

    protected $fillable = ['titulo', 'data', 'observacao'];

    protected $casts = ['data' => 'date'];
}
```

### Passo 4: Controller (src/MinhaListaController.php)

```php
<?php

namespace MinhaLista;

use MinhaLista\Models\ItemLista;
use Illuminate\Http\Request;

class MinhaListaController
{
    public function index()
    {
        $itens = ItemLista::orderByDesc('data')->orderByDesc('created_at')->paginate(15);
        return erp_view('minha-lista::index', [
            'title' => 'Minha Lista',
            'itens' => $itens,
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'titulo' => 'required|string|max:255',
            'data'   => 'nullable|date',
            'observacao' => 'nullable|string|max:2000',
        ]);
        ItemLista::create($request->only('titulo', 'data', 'observacao'));
        return redirect()->route('plugin.minha-lista.index')
            ->with('success', 'Item adicionado.');
    }

    public function destroy(ItemLista $item)
    {
        $item->delete();
        return redirect()->route('plugin.minha-lista.index')
            ->with('success', 'Item removido.');
    }
}
```

Para usar route model binding com uma tabela do plugin, registre o model no RouteServiceProvider ou use `ItemLista::findOrFail($id)` no controller e passe o id na rota.

### Passo 5: routes.php

```php
<?php

use Illuminate\Support\Facades\Route;
use MinhaLista\MinhaListaController;

Route::get('/', [MinhaListaController::class, 'index'])
    ->middleware('operational')
    ->name('index');

Route::post('/', [MinhaListaController::class, 'store'])
    ->middleware('operational')
    ->name('store');

Route::delete('/{id}', function ($id) {
    $item = \MinhaLista\Models\ItemLista::findOrFail($id);
    app(MinhaListaController::class)->destroy($item);
    return redirect()->route('plugin.minha-lista.index')->with('success', 'Item removido.');
})->middleware('operational')->name('destroy');
```

### Passo 6: views/minha-lista/index.blade.php

```blade
@extends('layouts.app')

@section('content')
<div class="p-6">
    <h1 class="text-2xl font-bold mb-4">Minha Lista</h1>

    <form action="{{ route('plugin.minha-lista.store') }}" method="POST" class="mb-6 flex gap-2 flex-wrap">
        @csrf
        <input type="text" name="titulo" required placeholder="Título" class="rounded border px-3 py-2">
        <input type="date" name="data" class="rounded border px-3 py-2">
        <input type="text" name="observacao" placeholder="Observação" class="rounded border px-3 py-2 flex-1 min-w-[200px]">
        <button type="submit" class="rounded bg-brand-500 px-4 py-2 text-white">Adicionar</button>
    </form>

    <ul class="space-y-2">
        @foreach($itens as $item)
        <li class="flex justify-between items-center rounded border p-3">
            <div>
                <strong>{{ $item->titulo }}</strong>
                @if($item->data) <span class="text-gray-500"> — {{ $item->data->format('d/m/Y') }}</span> @endif
                @if($item->observacao) <p class="text-sm text-gray-600">{{ $item->observacao }}</p> @endif
            </div>
            <form action="{{ route('plugin.minha-lista.destroy', $item->id) }}" method="POST" onsubmit="return confirm('Remover?');">
                @csrf
                @method('DELETE')
                <button type="submit" class="text-red-600 text-sm">Remover</button>
            </form>
        </li>
        @endforeach
    </ul>

    {{ $itens->links() }}
</div>
@endsection
```

### Passo 7: views/minha-lista/settings.blade.php

Conteúdo simples para a tela de configurações do plugin (ex.: texto de ajuda e um campo opcional salvo com `update_option`):

```blade
<div class="rounded-lg border p-6">
    <h2 class="text-lg font-semibold mb-2">Configurações do Minha Lista</h2>
    <p class="text-gray-600 text-sm">Aqui você pode documentar como usar o plugin ou adicionar opções no futuro.</p>
</div>
```

Ative o plugin em **Configurações do Sistema → Plugins**. O item “Minha Lista” aparecerá no menu e a lista estará em `/plugin/minha-lista`.

---

## 18. Referência rápida de hooks

Consulte sempre o arquivo **docs/PLUGINS.md** para a lista atualizada de hooks. Abaixo, um resumo enxuto.

- **Menu**: `menu.items` (filter), `menu.groups` (filter), `menu.items.before` (action).
- **Auth**: `auth.login.success` (action), `auth.login.redirect` (filter).
- **Models**: `model.creating`, `model.created`, `model.saving`, `model.saved`, `model.updating`, `model.updated`, `model.deleting`, `model.deleted` (actions).
- **Validação**: `request.validation.rules` (filter: $rules, $routeName, $request).
- **Listagem**: `entity.index.query` (filter: $query, $entity).
- **Views**: `view.data` (filter), `view.render.before` (action).
- **Resposta JSON**: `response.json` (filter). Use `erp_json()` no core.
- **Formulários**: `admin.clientes.form.extra`, `admin.clientes.form.portal`, `admin.fornecedores.form.extra`, `admin.produtos.form.extra`, `admin.servicos.form.extra`, `ordens_servico.form.extra`, `financeiro.contas-pagar.form.extra`, `financeiro.contas-receber.form.extra`, entre outros (action com $model ou null).
- **Show**: `admin.clientes.show.extra`, `admin.clientes.show.portal`, etc. (action com $model).
- **Configurações**: `configuracoes.plugins.settings_view` (filter: $view, $plugin).
- **Dashboard**: `dashboard.main.data`, `dashboard.stats`, `dashboard.extra_cards`, `admin.dashboard.data`, `admin.dashboard.cards`, `financeiro.dashboard.data`, `financeiro.dashboard.cards`, etc.
- **Notificações**: `header.notifications` (filter), `header.notifications.view_all_url` (filter).
- **Index (blocos)**: `entity.index.before_table`, `entity.index.after_table` (actions com $entity).

---

## 19. Resolução de problemas

### Plugin não aparece na listagem

- Verifique se a pasta está em `plugins/{slug}/` e se existe `plugin.php`.
- O cabeçalho deve ter pelo menos `Plugin Name:` nos comentários.
- Se houver `Requires PHP` ou `Requires Plugins`, confira se a versão de PHP e os plugins dependentes estão ok.

### Ao ativar dá erro

- Veja a mensagem de erro (ex.: falha na migration no activation hook). Corrija o hook de ativação e tente ativar de novo.
- Confira se o nome da tabela usa o prefixo `plugin_{slug}_` e se não colide com tabelas existentes.

### Rotas 404 após ativar

- Confirme que o plugin está em `storage/app/plugins_active.json`.
- As rotas do plugin são carregadas com prefixo `/plugin/{slug}/`. A URL correta é essa.
- Verifique se em `routes.php` você não está repetindo o prefixo na string do path (o sistema já adiciona `/plugin/meu-plugin`).

### View não encontrada

- O namespace da view é o **slug** do plugin (ex.: `minha-lista::index`).
- A view deve estar em `plugins/meu-plugin/views/index.blade.php` (ou no subdiretório correspondente).

### Opções não persistem

- Opções são salvas em `storage/app/plugin_options/{slug}.json`. Confira permissões da pasta.
- Use `update_option($key, $value)` dentro do contexto do plugin ou `get_plugin_option($slug, $key)` quando não houver contexto (ex.: comando).

### Menu não aparece ou some

- O filter `menu.items` recebe o array completo. Sempre **retorne** o array (modificado).
- Verifique permissões: muitos menus checam `auth()->user()->canAccessOperational()` ou permissões específicas. Se seu callback retornar cedo sem adicionar o item, o item não aparece.

### Erro "class not found" ao acessar rota do plugin

- O autoload do plugin não está carregando a classe. Use `require_once` no `plugin.php` para os arquivos do plugin ou implemente um autoload em `autoload.php` e inclua esse arquivo no `plugin.php`.

---

Este guia cobre a criação de plugins do zero até recursos avançados. Para a lista exata de hooks e detalhes de cada um, use **docs/PLUGINS.md**. Para exemplos reais, consulte os plugins em `plugins/exemplo-plugin`, `plugins/area-cliente` e `plugins/pdv`.
