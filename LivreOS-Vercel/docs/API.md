# API do ERP — Documentação

A API REST permite integrar o sistema com aplicativos móveis, portais e outros serviços. Todas as respostas são em **JSON**. A autenticação é feita por **token Bearer** (campo `api_token` do usuário).

## Índice

1. [Base URL e formato](#base-url-e-formato)
2. [Autenticação](#autenticação)
3. [Respostas e códigos HTTP](#respostas-e-códigos-http)
4. [Paginação](#paginação)
5. [Endpoints](#endpoints)
6. [Exemplos (cURL e JavaScript)](#exemplos-curl-e-javascript)
7. [Permissões por recurso](#permissões-por-recurso)
8. [Extensibilidade (plugins)](#extensibilidade-plugins)

---

## Base URL e formato

- **Base URL:** `https://seu-dominio.com/api` (em desenvolvimento: `http://localhost/erp/public/api` ou conforme sua instalação).
- **Content-Type:** use `Content-Type: application/json` nas requisições com corpo.
- **Accept:** a API sempre retorna JSON; o header `Accept: application/json` é recomendado.
- **Erros:** Todas as requisições sob `/api/*` recebem exceções (404, 500, etc.) em JSON, com formato `{ "success": false, "message": "...", "error": "..." }`.

---

## Autenticação

### Obter token (login)

Envie **email** e **password** para receber um token. O token deve ser enviado em todas as requisições protegidas no header `Authorization: Bearer {token}`.

**Request**

```http
POST /api/auth/login
Content-Type: application/json

{
  "email": "usuario@empresa.com",
  "password": "sua_senha"
}
```

**Resposta de sucesso (200)**

```json
{
  "success": true,
  "message": "Login realizado com sucesso.",
  "data": {
    "token": "abc123...",
    "token_type": "Bearer",
    "user": {
      "id": 1,
      "name": "Nome do Usuário",
      "email": "usuario@empresa.com",
      "is_admin": false,
      "ativo": true,
      "roles": ["seller", "attendant"]
    }
  }
}
```

**Resposta de erro (401)**

```json
{
  "success": false,
  "message": "Credenciais inválidas."
}
```

**Resposta de erro (429)** — após várias tentativas falhas com o mesmo e-mail e IP (limite: 5 falhas em 5 minutos). O header `Retry-After` indica os segundos até poder tentar de novo.

```json
{
  "success": false,
  "message": "Muitas tentativas de login. Tente novamente em 120 segundos.",
  "error": "too_many_attempts"
}
```

### Usar o token

Inclua o header em todas as requisições às rotas protegidas:

```http
Authorization: Bearer SEU_TOKEN_AQUI
```

### Logout (invalidar token)

```http
POST /api/auth/logout
Authorization: Bearer SEU_TOKEN_AQUI
```

**Resposta (200)**

```json
{
  "success": true,
  "message": "Logout realizado com sucesso.",
  "data": null
}
```

### Dados do usuário logado

```http
GET /api/auth/me
Authorization: Bearer SEU_TOKEN_AQUI
```

**Resposta (200)**

```json
{
  "success": true,
  "message": "OK",
  "data": {
    "id": 1,
    "name": "Nome",
    "email": "usuario@empresa.com",
    "is_admin": false,
    "ativo": true,
    "roles": ["seller"]
  }
}
```

---

## Respostas e códigos HTTP

| Código | Significado |
|--------|-------------|
| 200 | Sucesso |
| 201 | Recurso criado |
| 400 | Requisição inválida |
| 401 | Não autenticado (token ausente ou inválido) |
| 403 | Sem permissão para o recurso |
| 404 | Recurso não encontrado |
| 422 | Erro de validação (campos inválidos) |
| 500 | Erro interno |

Formato padrão de **sucesso**:

```json
{
  "success": true,
  "message": "OK",
  "data": { ... }
}
```

Formato padrão de **erro**:

```json
{
  "success": false,
  "message": "Descrição do erro",
  "errors": { "campo": ["mensagem de validação"] }
}
```

Em **403**, pode existir o campo `permission_required` indicando a permissão necessária.

---

## Paginação

Listagens (clientes, produtos, serviços, etc.) usam paginação. Parâmetros:

| Parâmetro | Descrição | Padrão |
|-----------|-----------|--------|
| `per_page` | Itens por página (1–100) | 15 |
| `page`     | Página atual              | 1      |

**Exemplo de resposta paginada**

```json
{
  "success": true,
  "message": "OK",
  "data": {
    "data": [ ... ],
    "meta": {
      "current_page": 1,
      "last_page": 5,
      "per_page": 15,
      "total": 72
    }
  }
}
```

---

## Endpoints

### Informações da API (público)

| Método | Endpoint | Descrição |
|--------|----------|-----------|
| GET | `/api` | Retorna nome, versão e lista de endpoints disponíveis (útil para o app verificar a API). Não exige token. |

**Exemplo de resposta GET /api**

```json
{
  "success": true,
  "message": "OK",
  "data": {
    "name": "ERP API",
    "version": "1.0",
    "documentation": "Consulte docs/API.md para autenticação e exemplos.",
    "endpoints": [
      { "method": "POST", "path": "/api/auth/login" },
      { "method": "GET", "path": "/api/clientes", "auth": true },
      ...
    ]
  }
}
```

### Autenticação

| Método | Endpoint | Descrição |
|--------|----------|-----------|
| POST | `/api/auth/login` | Login (retorna token) |
| POST | `/api/auth/logout` | Logout (invalida token) |
| GET  | `/api/auth/me` | Dados do usuário logado |
| POST | `/api/auth/alterar-senha` | Alterar senha do usuário logado (requer `senha_atual`, `password`, `password_confirmation`) |

### Clientes

| Método | Endpoint | Permissão | Descrição |
|--------|----------|-----------|-----------|
| GET    | `/api/clientes` | view-clients | Listar (com filtros e paginação) |
| GET    | `/api/clientes/{id}` | view-clients | Ver um cliente |
| POST   | `/api/clientes` | create-clients | Criar cliente |
| PUT/PATCH | `/api/clientes/{id}` | edit-clients | Atualizar cliente |
| DELETE | `/api/clientes/{id}` | delete-clients | Excluir cliente |

**Filtros (query) para GET /api/clientes**

- `tipo_pessoa` — F, J ou E
- `nome` — busca parcial
- `documento` — CPF/CNPJ/documento estrangeiro (parcial)
- `per_page`, `page` — paginação

### Produtos

| Método | Endpoint | Permissão | Descrição |
|--------|----------|-----------|-----------|
| GET    | `/api/produtos` | view-products | Listar |
| GET    | `/api/produtos/{id}` | view-products | Ver um produto |
| POST   | `/api/produtos` | create-products | Criar produto |
| PUT/PATCH | `/api/produtos/{id}` | edit-products | Atualizar produto |
| DELETE | `/api/produtos/{id}` | delete-products | Excluir produto |

**Filtros para GET /api/produtos**

- `nome`, `codigo_sku`, `categoria_id`
- `order_by` — nome, codigo_sku, preco_venda, estoque_quantidade, updated_at
- `direction` — asc ou desc
- `per_page`, `page`

### Serviços

| Método | Endpoint | Permissão | Descrição |
|--------|----------|-----------|-----------|
| GET    | `/api/servicos` | view-services | Listar |
| GET    | `/api/servicos/{id}` | view-services | Ver um serviço |
| POST   | `/api/servicos` | create-services | Criar serviço |
| PUT/PATCH | `/api/servicos/{id}` | edit-services | Atualizar serviço |
| DELETE | `/api/servicos/{id}` | delete-services | Excluir serviço |

**Filtros para GET /api/servicos**

- `nome`, `codigo_sku`, `categoria_id`
- `order_by` — nome, codigo_sku, preco, updated_at
- `direction` — asc ou desc
- `per_page`, `page`

### Categorias (para selects no app)

| Método | Endpoint | Permissão | Descrição |
|--------|----------|-----------|-----------|
| GET    | `/api/categorias-produtos` | view-products | Listar categorias de produtos |
| GET    | `/api/categorias-servicos` | view-services | Listar categorias de serviços |

**Query opcional:** `ativo_only=1` — retorna apenas categorias ativas. `per_page`, `page` para paginação.

### Fornecedores

Fornecedores são contatos com `is_fornecedor = true` (vinculados ou não a um cliente).

| Método | Endpoint | Permissão | Descrição |
|--------|----------|-----------|-----------|
| GET    | `/api/fornecedores` | view-products | Listar fornecedores |
| GET    | `/api/fornecedores/{id}` | view-products | Ver um fornecedor |

**Filtros:** `nome`, `cliente_id`, `per_page`, `page`.

### Grupos econômicos

| Método | Endpoint | Permissão | Descrição |
|--------|----------|-----------|-----------|
| GET    | `/api/grupos-economicos` | view-clients | Listar grupos econômicos |
| GET    | `/api/grupos-economicos/{id}` | view-clients | Ver um grupo |

**Query opcional:** `ativo_only=1`, `nome`, `per_page`, `page`.

### Ordens de Serviço

| Método | Endpoint | Permissão | Descrição |
|--------|----------|-----------|-----------|
| GET    | `/api/ordens-servico` | view-os-history | Listar OS |
| GET    | `/api/ordens-servico/{id}` | view-os-history | Ver uma OS |

**Filtros para GET /api/ordens-servico**

- `cliente_id`, `status`, `codigo_interno`
- `per_page`, `page`

### Status de OS

Lista de status disponíveis para ordens de serviço (filtros e formulários).

| Método | Endpoint | Permissão | Descrição |
|--------|----------|-----------|-----------|
| GET    | `/api/status-os` | view-os-history | Listar status de OS |

**Query opcional:** `ativo_only=1`, `per_page`, `page`.

### Tags

Tags usadas em ordens de serviço e outros recursos.

| Método | Endpoint | Permissão | Descrição |
|--------|----------|-----------|-----------|
| GET    | `/api/tags` | view-os-history | Listar tags |

**Query opcional:** `ativo_only=1`, `nome`, `per_page`, `page`.

### Notificações do usuário

Notificações do usuário logado (qualquer usuário operacional).

| Método | Endpoint | Descrição |
|--------|----------|-----------|
| GET    | `/api/notificacoes` | Listar notificações do usuário |
| POST   | `/api/notificacoes/{id}/ler` | Marcar uma como lida |
| POST   | `/api/notificacoes/ler-todas` | Marcar todas como lidas |
| DELETE | `/api/notificacoes/{id}` | Excluir uma notificação |

**Query para listagem:** `per_page`, `page` (padrão 20, máx. 50).

### Financeiro

| Método | Endpoint | Permissão | Descrição |
|--------|----------|-----------|-----------|
| GET    | `/api/contas-receber` | access-financeiro | Listar contas a receber |
| GET    | `/api/contas-receber/{id}` | access-financeiro | Ver uma conta a receber |
| GET    | `/api/contas-pagar` | access-financeiro | Listar contas a pagar |
| GET    | `/api/contas-pagar/{id}` | access-financeiro | Ver uma conta a pagar |
| GET    | `/api/contas-bancarias` | access-financeiro | Listar contas bancárias |
| GET    | `/api/contas-bancarias/{id}` | access-financeiro | Ver uma conta bancária |
| GET    | `/api/formas-pagamento` | access-financeiro | Listar formas de pagamento |
| GET    | `/api/formas-pagamento/{id}` | access-financeiro | Ver uma forma de pagamento |
| GET    | `/api/plano-contas` | access-financeiro | Listar plano de contas |
| GET    | `/api/plano-contas/{id}` | access-financeiro | Ver uma conta do plano |
| GET    | `/api/centros-custo` | access-financeiro | Listar centros de custo |
| GET    | `/api/centros-custo/{id}` | access-financeiro | Ver um centro de custo |
| GET    | `/api/movimentacoes` | access-financeiro | Listar movimentações financeiras |
| GET    | `/api/movimentacoes/{id}` | access-financeiro | Ver uma movimentação |
| GET    | `/api/adquirentes` | access-financeiro | Listar adquirentes |
| GET    | `/api/adquirentes/{id}` | access-financeiro | Ver um adquirente |

**Filtros movimentações:** `conta_bancaria_id`, `tipo`, `data_de`, `data_ate` (Y-m-d), `per_page`, `page`.

**Query opcional para listagens:** `ativo_only=1` (contas bancárias, formas de pagamento, plano de contas, centros de custo, adquirentes). Para plano de contas: `tipo`. `per_page`, `page`.

**Filtros contas a receber**

- `cliente_id`, `status`
- `data_vencimento_de`, `data_vencimento_ate` (formato Y-m-d)
- `per_page`, `page`

**Filtros contas a pagar**

- `fornecedor_id`, `status`
- `data_vencimento_de`, `data_vencimento_ate` (formato Y-m-d)
- `per_page`, `page`

---

## Exemplos (cURL e JavaScript)

### 1. Login e guardar o token

**cURL**

```bash
curl -X POST "https://seu-dominio.com/api/auth/login" \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -d '{"email":"usuario@empresa.com","password":"sua_senha"}'
```

**JavaScript (fetch)**

```javascript
const response = await fetch('https://seu-dominio.com/api/auth/login', {
  method: 'POST',
  headers: {
    'Content-Type': 'application/json',
    'Accept': 'application/json',
  },
  body: JSON.stringify({
    email: 'usuario@empresa.com',
    password: 'sua_senha',
  }),
});
const json = await response.json();
const token = json.data?.token; // guarde para as próximas requisições
```

### 2. Listar clientes (com token)

**cURL**

```bash
curl -X GET "https://seu-dominio.com/api/clientes?per_page=10&nome=Silva" \
  -H "Accept: application/json" \
  -H "Authorization: Bearer SEU_TOKEN"
```

**JavaScript (fetch)**

```javascript
const token = 'SEU_TOKEN';
const response = await fetch('https://seu-dominio.com/api/clientes?per_page=10&nome=Silva', {
  headers: {
    'Accept': 'application/json',
    'Authorization': `Bearer ${token}`,
  },
});
const { data } = await response.json();
console.log(data.data);   // lista de clientes
console.log(data.meta);   // current_page, last_page, total, etc.
```

### 3. Criar um cliente

**cURL**

```bash
curl -X POST "https://seu-dominio.com/api/clientes" \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -H "Authorization: Bearer SEU_TOKEN" \
  -d '{
    "tipo_pessoa": "F",
    "nome": "João da Silva",
    "cpf": "12345678909",
    "observacoes": "Cliente novo"
  }'
```

**JavaScript (fetch)**

```javascript
const response = await fetch('https://seu-dominio.com/api/clientes', {
  method: 'POST',
  headers: {
    'Content-Type': 'application/json',
    'Accept': 'application/json',
    'Authorization': `Bearer ${token}`,
  },
  body: JSON.stringify({
    tipo_pessoa: 'F',
    nome: 'João da Silva',
    cpf: '12345678909',
    observacoes: 'Cliente novo',
  }),
});
const json = await response.json();
if (json.success) {
  console.log('Cliente criado:', json.data);
}
```

### 4. Listar ordens de serviço de um cliente

**cURL**

```bash
curl -X GET "https://seu-dominio.com/api/ordens-servico?cliente_id=5&per_page=20" \
  -H "Accept: application/json" \
  -H "Authorization: Bearer SEU_TOKEN"
```

### 5. App móvel — fluxo típico

1. **Login:** `POST /api/auth/login` com email e senha.
2. **Guardar token** de forma segura (ex.: armazenamento seguro do app).
3. **Requisições:** sempre enviar `Authorization: Bearer {token}`.
4. **Se receber 401:** redirecionar para a tela de login e limpar o token.
5. **Se receber 429:** aguardar o tempo indicado em `Retry-After` (ou na mensagem) antes de tentar login de novo.
6. **Logout:** chamar `POST /api/auth/logout` e depois limpar o token local.

---

## Permissões por recurso

O usuário precisa ter **acesso operacional** (role configurado em `config('access.operational_roles')`) e a **permissão** específica do recurso. Administradores (`is_admin`) têm todas as permissões.

| Recurso | Listar / Ver | Criar | Editar | Excluir |
|---------|----------------|-------|--------|---------|
| Clientes | view-clients | create-clients | edit-clients | delete-clients |
| Produtos | view-products | create-products | edit-products | delete-products |
| Serviços | view-services | create-services | edit-services | delete-services |
| Ordens de Serviço | view-os-history | — | — | — |
| Contas a receber/pagar | access-financeiro | — | — | — |
| Contas bancárias / Formas de pagamento | access-financeiro | — | — | — |
| Plano de contas / Centros de custo | access-financeiro | — | — | — |
| Categorias de produtos | view-products | — | — | — |
| Categorias de serviços | view-services | — | — | — |
| Fornecedores | view-products | — | — | — |
| Grupos econômicos | view-clients | — | — | — |
| Status de OS | view-os-history | — | — | — |
| Tags | view-os-history | — | — | — |
| Notificações | (qualquer operacional) | — | — | — |
| Movimentações / Adquirentes | access-financeiro | — | — | — |

Sem a permissão, a API retorna **403** com mensagem e, quando aplicável, `permission_required`.

---

## Extensibilidade (plugins)

Plugins podem registrar rotas adicionais na API através do **action** `api.routes.register`:

```php
add_action('api.routes.register', function ($router) {
    $router->middleware(['api.auth', 'api.operational'])
        ->prefix('api')
        ->group(function () {
            Route::get('/meu-recurso', [MeuController::class, 'index']);
        });
});
```

As rotas do plugin devem usar os mesmos middlewares (`api.auth`, `api.operational`) para manter autenticação e controle de acesso.

---

## Migração e token

Para habilitar a API, execute a migração que adiciona o campo `api_token` na tabela `users`:

```bash
php artisan migrate
```

Após o primeiro login via API, o usuário receberá um token que permanece válido até que seja revogado (logout ou alteração manual no banco). Para maior segurança em produção, considere implementar expiração de token (ex.: coluna `api_token_expires_at`) ou o uso de **Laravel Sanctum** para tokens com expiração e escopos.
