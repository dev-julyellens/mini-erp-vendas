# Débito técnico

Consolidado de `docs/implementacoes/20-refatoracao-tecnica-geral.md` e revisão do código (mai/2026).

**Relacionados:** [bugs-conhecidos.md](bugs-conhecidos.md) · [plano-implementacao-debito-tecnico.md](plano-implementacao-debito-tecnico.md) · [checklist-melhorias-projeto.md](../implementacoes/checklist-melhorias-projeto.md) · `bin/check-route-permission-map.php`

**Legenda:** ✅ concluído ou mitigado · 🟡 em progresso · ⬜ pendente

---

## Repositórios

| Status | Detalhe |
|--------|---------|
| ✅ | **33/33** repositórios estendem `BaseRepository` (PDO injetável via construtor da base). |
| ✅ | Nenhum construtor duplicado com `Database::getConnection()` nos filhos (mai/2026). |

**Impacto:** padrão único para testes com `$pdo` mockado ou transação compartilhada.

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
| 🟡 | ~26 arquivos em `tests/` (Unit + Integration): financeiro (`PaymentService`), estoque (`StockService`), cancelamento (`OrderCancelService`), vendas, tenant, PIX. |
| ⬜ | Cobertura ainda concentrada — poucos services com PDO mock; inventário físico sem teste de integração dedicado. |
| ✅ | PHPStan no **nível 6** (`phpstan.neon`); PHPDoc de iteráveis corrigidos em mai/2026. |

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
| P3 | Avaliar `company_id` em `stock_movements` (migration + impacto) | Alto |
| P3 | Remover `Env::loadLegacy` após validar deploy só com Composer | Baixo |

---

## Itens já resolvidos (não reabrir)

- ~~Implementar gateway PIX real~~ → `PaymentGatewayInterface` + Mercado Pago + mock.
- ~~ACL hub de relatórios sem controle~~ → `canAccessReportsHub()` no middleware.
- ~~Mensagens em inglês nos fluxos principais de estoque/venda~~ → corrigido (mai/2026).
