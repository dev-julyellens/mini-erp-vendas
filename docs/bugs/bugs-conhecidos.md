# Bugs conhecidos

Lista baseada no código e comportamento observável. Não inclui issues externas de tracker.

## Comportamento vs schema

| Item | Descrição |
|------|-----------|
| Status `pending` em pedidos | Coluna existe e migration documenta fluxo, mas `OrderService` grava `paid` na criação. Relatórios/filtros que assumem `pending` podem não refletir a operação real. |

## Segurança / ambiente

| Item | Descrição |
|------|-----------|
| JWT secret padrão | `mini-erp-dev-jwt-secret-change-in-production` se `JWT_SECRET` não definido. |
| Webhook PIX mock público | `POST /webhooks/pix/mock` em rotas públicas — aceitável só em dev; expor em produção sem validação forte é risco. |
| Reset de senha | Token gerado, mas **e-mail não enviado**; link só em `APP_DEBUG`. Usuário em produção sem debug não recebe instruções automáticas. |

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
