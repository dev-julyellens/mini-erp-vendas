# Produtos e serviços

## 1. Visão geral do módulo

Cadastro unificado na tabela `products` com discriminação por `type`: **`product`** (físico, com estoque) ou **`service`** (sem movimentação de estoque). Categorias, SKU, código de barras, preço, custo e margem. Serviços expostos em rotas/UI separadas.

## 2. Fluxo funcional

### Produtos físicos

```
/products → ProductController → ProductService
  → validação (SKU único por empresa, preço, estoque inicial)
  → ProductRepository
  → PlanLimitService (limite products_max do plano SaaS)
```

### Serviços

```
/services → ServiceController → ProductService (filtro type=service)
  → campo estimated_time_minutes
```

### Categorias

```
/categories → CategoryController → CategoryService → CategoryRepository
```

### API

`GET /api/products` — lista ordenada por nome (ACL `produtos.visualizar`).

## 3. Estrutura de banco relacionada

| Tabela | Campos relevantes |
|--------|-------------------|
| `products` | `company_id`, `category_id`, `sku`, `barcode`, `name`, `type`, `price`, `cost`, `margin`, `markup`, `stock`, `min_stock`, `active`, `estimated_time_minutes` |
| `categories` | `company_id`, `name`, `description` |

Constraint: `type IN ('product', 'service')`.

## 4. Services envolvidos

| Service | Função |
|---------|--------|
| `ProductService` | CRUD produtos e serviços |
| `CategoryService` | CRUD categorias |
| `PlanLimitService` | Bloqueio ao atingir limite do plano |
| `StockService` | Estoque inicial na criação (produtos) |
| `ReportService` | Top produtos, vendas por produto |

## 5. Repositories envolvidos

- `ProductRepository` — queries com `CompanyScope`
- `CategoryRepository`

## 6. Controllers envolvidos

| Controller | Rotas |
|------------|-------|
| `ProductController` | `/products/*` CRUD |
| `ServiceController` | `/services/*` CRUD |
| `CategoryController` | `/categories/*` CRUD |
| `ApiProductController` | `GET /api/products` |

ACL: módulo `produtos` (serviços e categorias mapeados ao mesmo módulo).

## 7. Regras de negócio

- SKU único por `company_id`.
- Serviços ignoram validação de estoque em vendas.
- Estoque mínimo (`min_stock`) alimenta notificações e relatório low-stock.
- Preço na venda é snapshot em `order_items`, não altera vendas passadas.
- Exclusão sujeita a integridade referencial (pedidos existentes).

## 8. Fluxo de dados

```
Form → Controller → ProductService → ProductRepository → PostgreSQL
                                              ↓
                                    (criação) StockService se estoque inicial > 0
```

## 9. Pontos críticos

- Mesma tabela para produto e serviço — filtros `type` obrigatórios em `ServiceController`.
- `PlanLimitExceededException` ao exceder limite SaaS.
- Margem/markup calculados no service conforme custo/preço informados.

## 10. Dependências

- Módulo categorias
- Módulo estoque (produtos físicos)
- Módulo vendas (consumo de catálogo)
- Views: `app/Views/products/`, `services/`, `categories/`
- JS: `public/assets/js/product_form.js`

## 11. Possíveis melhorias futuras

- Variantes (tamanho/cor) e kits.
- Importação CSV de produtos.
- Imagens e anexos.
- Tabela separada de serviços se regras divergirem muito.
