# Mini ERP de Vendas

Sistema web em **PHP (>=7.4)**, arquitetura **MVC**, com **Repositories**, **Services** (regras de negócio) e **PostgreSQL** (PDO).

## Requisitos

- PHP 7.4+ com extensões: `pdo`, `pdo_pgsql`, `json`, `mbstring`
- Composer
- PostgreSQL 12+ (recomendado)
- Apache com `mod_rewrite` (XAMPP) **ou** outro servidor apontando o *document root* para a pasta `public/`

> **Autenticação:** login obrigatório para o painel e APIs. Usuário padrão após instalação: `admin@mini-erp.local` / `Admin@123` (altere após o primeiro acesso).

## Instalação

### 1) Banco de dados

1. Crie o banco (exemplo):

```sql
CREATE DATABASE mini_erp_vendas;
```

2. Importe o script:

```bash
psql -U postgres -d mini_erp_vendas -f database/database.sql
```

O arquivo cria tabelas, constraints, índices e **dados iniciais** (usuário admin, clientes e produtos).

Se o banco já existia antes da autenticação ou permissões, aplique as migrations:

```bash
php database/run_migration.php
```

(O script executa todos os arquivos em `database/migrations/` em ordem.)

### 2) Configuração

Edite `config/app.php` **ou** defina variáveis de ambiente:

- `DB_HOST` (padrão: `127.0.0.1`)
- `DB_PORT` (padrão: `5432`)
- `DB_NAME` (padrão: `mini_erp_vendas`)
- `DB_USER` (padrão: `postgres`)
- `DB_PASSWORD` (padrão: `postgres`)
- `APP_BASE_URL` (ex.: `http://localhost/mini-erp-vendas/public`)
- `APP_DEBUG` (`true` em desenvolvimento: exibe dica de login e link de reset de senha na tela)

### 3) Composer

Na raiz do projeto:

```bash
composer install
```

### 4) Apache (XAMPP)

Aponte o virtual host (ou acesse) para:

`http://localhost/mini-erp-vendas/public/`

Ajuste o `RewriteBase` em `public/.htaccess` se o caminho do projeto for diferente.

## Funcionalidades

- **Autenticação:** login, logout, sessão segura, CSRF, recuperação de senha, perfis (`admin`, `vendedor`, `financeiro`, `estoque`)
- **Permissões (ACL):** controle por módulo e ação (`visualizar`, `criar`, `editar`, `excluir`); `admin` com acesso total; middleware + menus condicionais
- **Clientes:** CRUD com e-mail único
- **Produtos:** CRUD com `price > 0` e `stock >= 0`
- **Vendas:** múltiplos itens, total automático, bloqueio de estoque insuficiente, baixa de estoque transacional
- **Consulta de vendas:** listagem com filtros (cliente e período) + detalhe com itens
- **Dashboard:** totais + alerta de estoque baixo (< 5)
- **API REST básica:**
  - `GET /api/products`
  - `GET /api/orders` (aceita `customer_id`, `date_from`, `date_to`, `page`, `per_page`)
  - `POST /api/orders` (JSON: `customer_id`, `items: [{product_id, quantity}]`)

## Regra de negócio importante (histórico de preço)

Na finalização da venda, o sistema grava em `order_items.unit_price` o preço vigente do produto **no momento da venda**, preservando histórico mesmo se o cadastro do produto mudar depois. Isso está documentado no `App\Services\OrderService`.

## Estrutura

```
/app
  /Controllers
  /Core
  /Helpers
  /Models
  /Repositories
  /Services
  /Views
/config
/database
/public
```

## Observações de UX

- Layout responsivo (Bootstrap 5) com painel (sidebar + topo)
- Registro de venda na tela **Nova venda** usa `fetch` (AJAX) + feedback via *toast*

## Formato de valores

Nos formulários web de produto, use **ponto** como separador decimal (ex.: `19.90`).
