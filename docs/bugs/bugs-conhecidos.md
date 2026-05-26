# Bugs conhecidos

Lista baseada no código e comportamento observável. Não inclui issues externas de tracker.

## Comportamento vs schema

| Item | Descrição |
|------|-----------|
| Status `pending` em pedidos | **Comportamento intencional (mai/2026):** `placeOrder()` grava `paid` porque a venda é finalizada na hora (estoque + AR). `pending` fica reservado para rascunho/orçamento futuro (`OrderService::STATUS_PENDING`). |

## Segurança / ambiente

| Item | Descrição |
|------|-----------|
| JWT secret padrão | Mitigado (mai/2026): boot falha se `APP_DEBUG=false` e `JWT_SECRET` ausente/fraco; fallback só em debug. |
| Webhook PIX mock público | Mitigado (mai/2026): rota só com `APP_DEBUG=true` ou `PIX_MOCK_WEBHOOK_ENABLED=true`; assinatura obrigatória se `PIX_WEBHOOK_SECRET` definido. |
| Reset de senha | Mitigado (mai/2026): `MailService` envia e-mail (`MAIL_DRIVER`); link na tela permanece só em `APP_DEBUG`. |

## ACL

| Item | Descrição |
|------|-----------|
| Hub de relatórios aberto | `GET /reports` sem entrada em `RoutePermissionMap` — qualquer usuário autenticado acessa o índice; sub-rotas exigem permissão. |

## Integrações

| Item | Descrição |
|------|-----------|
| PIX produção | Apenas `MockPixGateway`; cobranças reais não processadas. |

## Instalação

| Item | Descrição |
|------|-----------|
| `database.sql` parcial | Instalação só com dump antigo pode faltar tabelas de migrations recentes (PIX, SaaS) — usar `run_migration.php`. |

## Mensagens

| Item | Descrição |
|------|-----------|
| Idioma misto | Services/API retornam mensagens em inglês; UI em português — inconsistência para o usuário final em erros expostos na web. |

---

Para correções planejadas, ver `docs/bugs/debito-tecnico.md`.
