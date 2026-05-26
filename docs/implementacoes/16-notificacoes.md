# Implementação: Notificações

**Prompt de origem:** `.cursor/prompts/16-notificacoes.md`  
**Data de referência:** maio/2026  
**Escopo:** alertas operacionais persistidos, badge no menu, histórico e toasts visuais.

---

## Visão geral

Sistema de notificações por empresa com quatro tipos de alerta:

| Tipo | Gatilho |
|------|---------|
| `low_stock` | Produto abaixo do estoque mínimo |
| `overdue_account` | Conta a receber ou parcela vencida |
| `order_canceled` | Cancelamento de venda |
| `critical_error` | Exceção não tratada na API |

---

## Banco de dados

| Arquivo | Conteúdo |
|---------|----------|
| `database/migrations/014_create_notifications.sql` | Tabela `notifications` + índices |

Campos principais: `company_id`, `type`, `title`, `message`, `level`, `link_url`, `dedupe_key`, `read_at`.

Índice único parcial em `(company_id, dedupe_key)` evita alertas duplicados enquanto não lidos.

---

## Camadas

| Camada | Arquivo |
|--------|---------|
| Model | `app/Models/Notification.php` |
| Repository | `app/Repositories/NotificationRepository.php` |
| Service | `app/Services/NotificationService.php` |
| Controller | `app/Controllers/NotificationController.php` |
| Views | `app/Views/notifications/index.php`, `app/Views/partials/notifications-bell.php` |

---

## Rotas

- `GET /notifications` — histórico com filtros
- `POST /notifications/open` — marca como lida (CSRF) e redireciona ao destino
- `POST /notifications/read` — marcar uma como lida
- `POST /notifications/read-all` — marcar todas como lidas

Rotas liberadas para qualquer usuário autenticado (sem ACL por módulo).

---

## Integrações

- **Estoque:** `StockService::apply()` chama `notifyLowStock()` após ajuste de saldo
- **Cancelamento:** `OrderCancelService::cancel()` registra `order_canceled`
- **API:** `ApiErrorHandlerMiddleware` registra `critical_error` em falhas 500
- **Sync periódico:** contas/parcelas vencidas sincronizadas no layout (throttle 60s na sessão)

---

## Interface

- Badge no menu lateral e sino no topbar
- Dropdown com últimas notificações
- Toasts Bootstrap para novas não lidas desde a última visita
- Página de histórico com filtros por tipo e status de leitura

---

## Como testar

1. Aplicar migration `014_create_notifications.sql`
2. Login no sistema e acessar `/notifications`
3. Reduzir estoque de um produto abaixo do mínimo → alerta `low_stock`
4. Cancelar uma venda → alerta `order_canceled`
5. Verificar badge no menu e toasts ao navegar
