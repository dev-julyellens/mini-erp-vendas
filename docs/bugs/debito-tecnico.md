# Débito técnico

Consolidado de `docs/implementacoes/20-refatoracao-tecnica-geral.md` e revisão do código (mai/2026).

**Relacionados:** [bugs-conhecidos.md](bugs-conhecidos.md) · [plano-implementacao-debito-tecnico.md](plano-implementacao-debito-tecnico.md) · [checklist-melhorias-projeto.md](../implementacoes/checklist-melhorias-projeto.md) · `bin/check-route-permission-map.php`

**Legenda:** ✅ concluído ou mitigado · 🟡 em progresso · ⬜ pendente

---

## Repositórios

| Status | Detalhe |
|--------|---------|
| 🟡 | **13 de 33** repositórios estendem `BaseRepository` (PDO injetável): núcleo venda/estoque/financeiro (`Product`, `Order`, `OrderItem`, `Payment`, `AccountsReceivable`, `PixCharge`, `StockMovement`) + orçamentos/inventário/clientes. |
| ⬜ | **~20** repositórios administrativos ainda instanciam `Database::getConnection()` diretamente (`User`, `Company`, `Category`, `Report`, etc.). |

**Impacto:** duplicação de construtor/PDO e maior dificuldade para testes com banco em memória ou mock.

---

## Transações

| Status | Detalhe |
|--------|---------|
| ✅ | Todos os services em `app/Services/` usam `Database::transaction()`; nenhum `beginTransaction` manual fora de `Database.php` (mai/2026). |
| ✅ | Inclui estoque/produtos (`StockService`, `ProductService`) e admin/SaaS (`AuthService`, `UserService`, `CompanyService`, `OnboardingService`, `SubscriptionBillingService`). |

**Meta:** manter padrão em novos fluxos multi-tabela; aninhar com `$manageTransaction = false` quando um service chama outro dentro da mesma transação.

---

## Logging

| Status | Detalhe |
|--------|---------|
| ✅ | `App\Core\Logger` (Monolog) adotado nos services; `ApiLogService` e `AuditService` migrados em mai/2026. |
| ✅ | Nenhum `error_log` em `app/Services/` — falhas de log/auditoria usam `Logger::exception()` com contexto. |

**Meta:** manter padrão em novos services (evitar `error_log` direto).

---

## Testes e qualidade

| Status | Detalhe |
|--------|---------|
| 🟡 | ~23 arquivos em `tests/` (Unit + Integration): Helpers, Core, `OrderService`, `QuoteService`, API auth, tenant, Mercado Pago PIX. |
| ⬜ | Cobertura ainda concentrada — poucos services com PDO mock; não há suíte ampla de integração por módulo. |
| ⬜ | PHPStan permanece no **nível 5** (`phpstan.neon`); subir para 6 exige corrigir ~14 avisos de `array` sem tipo de valor (ver checklist 5.7). |

**Comandos:** `composer test` · `composer analyse` · `composer check`

---

## Padrões

| Status | Detalhe |
|--------|---------|
| ✅ | Mensagens de domínio nos fluxos web/estoque principais em português (`StockService`, controllers de venda/movimentação). Revisar novos services ao criar. |
| 🟡 | `RoutePermissionMap` continua estático (entrada manual por rota). Mitigações: `bin/check-route-permission-map.php`; hub `GET /reports` com `PermissionService::canAccessReportsHub()` (OR vendas/estoque/financeiro). |
| ⬜ | `Env::loadLegacy()` mantido como fallback sem Composer; Dotenv (`vlucas/phpdotenv`) é o caminho principal — remover legado quando política de deploy permitir. |

---

## Infraestrutura de schema

| Status | Detalhe |
|--------|---------|
| 🟡 | `database.sql` consolidado inclui módulos recentes (PIX, SaaS, quotes, inventário), mas **não substitui** `php database/run_migration.php` em ambientes já instalados. |
| ⬜ | `stock_movements` **sem** `company_id` — isolamento via join com `products`; migration futura exigiria revisão de queries e índices. |

---

## Integrações (fora da lista original)

| Status | Detalhe |
|--------|---------|
| ✅ | Gateway PIX Mercado Pago implementado: `MercadoPagoPixGateway`, `GatewayFactory`, webhook `/webhooks/pix/mercadopago`, testes em `MercadoPagoPixGatewayTest`. |
| 🟡 | Produção depende de configuração: `PIX_DEFAULT_GATEWAY=mercadopago` + `PIX_MERCADOPAGO_*` no `.env` (ver `config/.env.example`). |

---

## Próximos passos sugeridos (prioridade)

| Prioridade | Tarefa | Esforço estimado |
|------------|--------|------------------|
| P2 | Ampliar testes de integração (financeiro, estoque, cancelamento) | Médio |
| P2 | PHPStan 5 → 6 (corrigir arrays sem value-type) | Médio |
| P3 | Migrar repositórios administrativos restantes para `BaseRepository` | Alto |
| P3 | Avaliar `company_id` em `stock_movements` (migration + impacto) | Alto |
| P3 | Remover `Env::loadLegacy` após validar deploy só com Composer | Baixo |

---

## Itens já resolvidos (não reabrir)

- ~~Implementar gateway PIX real~~ → `PaymentGatewayInterface` + Mercado Pago + mock.
- ~~ACL hub de relatórios sem controle~~ → `canAccessReportsHub()` no middleware.
- ~~Mensagens em inglês nos fluxos principais de estoque/venda~~ → corrigido (mai/2026).
