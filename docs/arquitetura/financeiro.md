# Financeiro

## 1. Visão geral do módulo

Gestão de **contas a receber**, **recebimentos**, **parcelas**, **fluxo de caixa** e **cobrança PIX** (gateway mock). Integrado à criação de vendas e ao dashboard gerencial.

## 2. Fluxo funcional

### Geração na venda

`OrderService` → `AccountsReceivableService::createFromApprovedOrder()` (status `pending`, vencimento configurável).

Se `installment_count >= 2`: `InstallmentService::generateForOrder()` cria parcelas com vencimentos escalonados (30 dias entre parcelas).

### Recebimento à vista (sem parcelas)

```
/finance/accounts-receivable/receive → PaymentService::receive()
  → payments + atualiza AR + cash_flow (entrada)
```

### Recebimento parcelado

```
/finance/installments/pay → InstallmentService (pagamento por parcela)
```

**Regra:** se existem parcelas abertas, recebimento deve ser pela tela de parcelas, não diretamente na AR (`PaymentService` valida).

### PIX

```
/finance/pix/create-account|create-installment → PixChargeService
  → MockPixGateway (QR Code)
POST /webhooks/pix/mock → conciliação simulada
```

### Consultas

- `/finance` — dashboard (`FinanceDashboardService`)
- `/finance/cash-flow` — extrato
- `/reports/cash-flow` — relatório exportável

## 3. Estrutura de banco relacionada

| Tabela | Uso |
|--------|-----|
| `accounts_receivable` | Título por venda; status `pending`/`paid`/`canceled` |
| `payments` | Recebimentos vinculados à AR |
| `installments` | Parcelas por pedido |
| `cash_flow` | Entradas/saídas consolidadas |
| `pix_charges` | Cobranças PIX e status |

## 4. Services envolvidos

| Service | Responsabilidade |
|---------|------------------|
| `AccountsReceivableService` | CRUD lógico de AR, criação na venda, cancelamento |
| `PaymentService` | Recebimento em conta (à vista) |
| `InstallmentService` | Geração e pagamento de parcelas |
| `CashFlowService` | Consultas de fluxo |
| `FinanceDashboardService` | KPIs em `/finance` |
| `PixChargeService` | Cobrança e conciliação PIX |
| `ReportService` / `ReportExportService` | Relatório cash-flow |

## 5. Repositories envolvidos

- `AccountsReceivableRepository`
- `PaymentRepository`
- `InstallmentRepository`
- `CashFlowRepository`
- `PixChargeRepository`
- `DashboardRepository` (métricas financeiras)

## 6. Controllers envolvidos

| Controller | Rotas principais |
|------------|------------------|
| `FinanceController` | `/finance`, `/finance/cash-flow` |
| `AccountsReceivableController` | `/finance/accounts-receivable/*` |
| `InstallmentController` | `/finance/installments/*` |
| `PixChargeController` | `/finance/pix/*` |
| `PixWebhookController` | `POST /webhooks/pix/mock` |
| `ReportController` | `/reports/cash-flow` |

ACL: módulo `financeiro`.

## 7. Regras de negócio

- Valores com `Money::normalizeDecimal` e operações BCMath.
- AR cancelada junto com venda cancelada (quando aplicável).
- Pagamento de parcela atualiza AR e gera `cash_flow`.
- PIX habilitado via `PIX_ENABLED` e gateway `PIX_DEFAULT_GATEWAY` (mock).
- Cancelamento de venda bloqueado se AR/parcelas já recebidas.

## 8. Fluxo de dados

```
Venda → AR (pending) → [Parcelas?] → Pagamento → payments + cash_flow
                    → PIX charge → webhook → PaymentService/InstallmentService
```

Auditoria: entidades `conta_receber`, `parcelamento`, `recebimento` (conforme operações).

## 9. Pontos críticos

- Consistência transacional entre `payments`, `accounts_receivable` e `cash_flow`.
- Webhook PIX mock é rota **pública** (lista em `AuthMiddleware`).
- Gateway **Mercado Pago** (`mercadopago`) quando `PIX_MERCADOPAGO_ACCESS_TOKEN` está definido; **mock** para desenvolvimento.
- Simulação de pagamento PIX: `POST /finance/pix/simulate-pay` (dev/teste).

## 10. Dependências

- Módulo vendas (origem das AR)
- `App\Helpers\Money`, `App\Helpers\Audit`
- Integração: `app/Integrations/Payment/`
- Views: `app/Views/finance/`

## 11. Possíveis melhorias futuras

- Gateway PIX real (PSP) com assinatura de webhook.
- Contas a pagar e conciliação bancária.
- Juros/multa em parcelas vencidas.
- DRE e categorização de fluxo de caixa.
