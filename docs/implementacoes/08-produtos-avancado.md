# 08 — Produtos avançado

## Objetivo

Profissionalizar o cadastro de produtos com SKU, categorias, precificação (custo, margem, markup), tipos produto/serviço e filtros na listagem.

## Tabela `categories`

| Campo | Descrição |
|-------|-----------|
| `name` | Nome único da categoria |
| `description` | Descrição opcional |

## Campos novos em `products`

| Campo | Descrição |
|-------|-----------|
| `sku` | Código interno único (obrigatório) |
| `barcode` | Código de barras (opcional, único) |
| `category_id` | FK para `categories` |
| `unit_of_measure` | UN, CX, KG, HR, etc. |
| `cost_price` | Preço de custo |
| `margin_percent` | Margem % (calculada ao salvar) |
| `markup_percent` | Markup % (calculada ao salvar) |
| `min_stock` | Estoque mínimo para alerta |
| `type` | `product` ou `service` |

## Tipos

- **product** — controla estoque e alerta quando `stock < min_stock`
- **service** — estoque fixo em zero; vendas não validam saldo; movimentações de estoque bloqueadas

## Rotas

| Método | Rota | Descrição |
|--------|------|-----------|
| GET | `/categories` | Listagem de categorias |
| GET/POST | `/categories/create`, `/categories/store` | Nova categoria |
| GET/POST | `/categories/edit`, `/categories/update` | Editar categoria |
| POST | `/categories/delete` | Excluir (bloqueado se houver produtos) |

Filtros em `/products`: `q`, `category_id`, `type`, `low_stock=1`.

## Migration

```bash
php database/run_migration.php
```

Arquivo: `database/migrations/008_create_categories_and_product_fields.sql`

Produtos existentes recebem SKU automático `SKU-000001`, etc.

## Verificação manual

1. Rodar migration e abrir `/products` — listagem com SKU, categoria e filtros.
2. Criar categoria em `/categories` e vincular a um produto.
3. Tentar cadastrar SKU duplicado — mensagem de validação.
4. Informar custo e preço de venda — conferir margem/markup no formulário e após salvar.
5. Cadastrar serviço — estoque desabilitado; venda sem erro de estoque.
6. Produto com estoque abaixo do mínimo — badge na listagem e card no dashboard.
