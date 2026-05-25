# Implementação: Movimentação de estoque

**Prompt de origem:** `.cursor/prompts/4-movimentacao-estoque.md`  
**Data de referência:** maio/2026  
**Escopo:** Rastreabilidade via `stock_movements`, saldo em `products.stock` mantido e sincronizado automaticamente.

---

## Visão geral

O controle de estoque passa a registrar **movimentações** (entrada, saída, ajuste, devolução, perda, inventário). O campo `products.stock` permanece como saldo atual e é atualizado na mesma transação de cada movimento.

### Fluxo

```
Venda / cadastro / tela manual → StockService::apply() → stock_movements + UPDATE products.stock
Consulta: StockMovementController → StockService::searchMovements() → view com filtros
```

---

## Arquivos criados

| Arquivo | Responsabilidade |
|---------|------------------|
| `database/migrations/004_create_stock_movements.sql` | Tabela, FKs, CHECK e índices |
| `app/Models/StockMovement.php` | Modelo de leitura |
| `app/Repositories/StockMovementRepository.php` | Insert e busca paginada |
| `app/Services/StockService.php` | Regras de movimento e atualização de saldo |
| `app/Controllers/StockMovementController.php` | Listagem e registro manual |
| `app/Views/stock/index.php` | Histórico com filtros |
| `app/Views/stock/create.php` | Formulário de movimentação manual |
| `docs/implementacoes/04-movimentacao-estoque.md` | Este documento |

---

## Arquivos alterados

| Arquivo | Alteração |
|---------|-----------|
| `database/database.sql` | Tabela `stock_movements` em instalação nova |
| `app/Repositories/ProductRepository.php` | `adjustStock`, `incrementStock`, `getConnection` |
| `app/Services/OrderService.php` | Saída de estoque via `StockService` (tipo `saida`, ref. `order`) |
| `app/Services/ProductService.php` | Entrada inicial e ajuste manual via `StockService` |
| `public/index.php` | Rotas `/stock-movements` |
| `app/Services/RoutePermissionMap.php` | ACL módulo `estoque` |
| `app/Views/layouts/main.php` | Link Estoque ativo no menu |

---

## Tabela `stock_movements`

| Coluna | Tipo | Descrição |
|--------|------|-----------|
| `id` | SERIAL | PK |
| `product_id` | INTEGER FK | Produto |
| `type` | VARCHAR(20) | entrada, saida, ajuste, devolucao, perda, inventario |
| `quantity` | INTEGER | Positiva para entrada/saída/etc.; com sinal para ajuste/inventário |
| `reference_type` | VARCHAR(50) | Ex.: `order`, `product`, `manual` |
| `reference_id` | INTEGER | ID da referência |
| `notes` | TEXT | Observações |
| `created_by` | INTEGER FK | Usuário |
| `created_at` | TIMESTAMP | Data/hora |

---

## Integrações

| Operação | Tipo | Onde |
|----------|------|------|
| Venda finalizada | `saida` | `OrderService::placeOrder` (dentro da transação) |
| Produto criado com estoque | `entrada` | `ProductService::create` |
| Ajuste no formulário de produto | `ajuste` | `ProductService::update` |
| Movimentação manual | conforme formulário | `StockMovementController::store` |
| Cancelamento de venda (futuro) | `devolucao` | `StockService::registerReturn` (pronto para prompt 5) |

A auditoria (`Audit::record` / `saida_estoque`) foi **mantida** para compatibilidade.

---

## Permissões

| Rota | Módulo | Ação |
|------|--------|------|
| `GET /stock-movements` | estoque | visualizar |
| `GET /stock-movements/create` | estoque | criar |
| `POST /stock-movements/store` | estoque | criar |

Papel `estoque` e `admin` têm acesso conforme `002_create_permissions.sql`.

---

## Como testar

1. Executar migration: `php database/run_migration.php`
2. Login como admin ou usuário papel `estoque`
3. Menu **Estoque** → listagem (vazia no início)
4. Registrar entrada manual → conferir saldo no cadastro de produtos
5. Registrar venda → ver movimentação `saida` com referência `order`
6. Editar estoque no produto → movimentação `ajuste`
7. Filtrar por produto e período na listagem

---

## Critérios de aceite

- [x] Movimentações registradas em `stock_movements`
- [x] `products.stock` atualizado automaticamente
- [x] Histórico com filtros por produto e período
- [x] Compatibilidade com fluxo anterior (campo `stock`, auditoria, vendas)
