# Bugs conhecidos

Lista baseada no código e comportamento observável. Não inclui issues externas de tracker.

## Comportamento vs schema

| Item | Descrição |
|------|-----------|
| Status do pedido vs AR | **Corrigido (mai/2026):** pedido nasce `pending`; vira `paid` ao quitar a conta a receber. Estoque baixa na criação; recebimento financeiro é separado. |

## Segurança / ambiente

| Item | Descrição |
|------|-----------|
| JWT secret padrão | Mitigado (mai/2026): boot falha se `APP_DEBUG=false` e `JWT_SECRET` ausente/fraco; fallback só em debug. |
| Webhook PIX mock público | Mitigado (mai/2026): rota só com `APP_DEBUG=true` ou `PIX_MOCK_WEBHOOK_ENABLED=true`; assinatura obrigatória se `PIX_WEBHOOK_SECRET` definido. |
| Reset de senha | Mitigado (mai/2026): `MailService` envia e-mail (`MAIL_DRIVER`); link na tela permanece só em `APP_DEBUG`. |

## ACL

| Item | Descrição |
|------|-----------|
| Hub de relatórios | **Corrigido (mai/2026):** `PermissionService::canAccessReportsHub()` — exige `vendas`, `estoque` ou `financeiro` (visualizar); aplicado no middleware e em `ReportController`. |

## Integrações

| Item | Descrição |
|------|-----------|
| PIX produção | Usar `PIX_DEFAULT_GATEWAY=mercadopago` + credenciais MP; mock só em dev. |

## Instalação

| Item | Descrição |
|------|-----------|
| `database.sql` parcial | Instalação só com dump antigo pode faltar tabelas de migrations recentes (PIX, SaaS) — usar `run_migration.php`. |

## Mensagens

| Item | Descrição |
|------|-----------|
| Idioma misto | **Corrigido (mai/2026):** mensagens de estoque e controllers web principais em português (`StockService`, `OrderController`, `StockMovementController`). Revisar pontualmente novos services. |

---

Para correções planejadas, ver `docs/bugs/debito-tecnico.md`.
