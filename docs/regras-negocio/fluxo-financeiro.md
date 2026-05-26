# Regras de negócio — Fluxo financeiro

## Conta a receber (AR)

- Criada automaticamente em toda venda (`AccountsReceivableService::createFromApprovedOrder`).
- Status inicial: `pending`.
- Valor = total do pedido.
- Vencimento: +30 dias (à vista) ou data da 1ª parcela (parcelado).
- Cancelamento de venda cancela AR abertas associadas.

## Recebimento à vista (sem parcelas)

- Rota: `/finance/accounts-receivable/receive`.
- Permissão: `financeiro.criar`.
- Registra em `payments`, atualiza AR para `paid` quando quitada, lança `cash_flow` tipo entrada.
- Valida método de pagamento e valor normalizado (`Money`).
- **Bloqueio:** se o pedido possui parcelas (`installments`), o recebimento deve ser feito por parcela, não pela AR diretamente.

## Parcelamento

- Mínimo 2, máximo 24 parcelas.
- Intervalo de 30 dias entre vencimentos.
- Pagamento em `/finance/installments/pay` atualiza parcela, AR e fluxo de caixa.
- Telas: abertas, vencidas, histórico.

## Fluxo de caixa

- Registro derivado de pagamentos (entradas).
- Consulta em `/finance/cash-flow` e relatório `/reports/cash-flow`.
- Export Excel/PDF disponível.

## PIX

- Cobrança vinculada a AR ou parcela (`PixChargeService`).
- Gateway atual: **mock** (`MockPixGateway`).
- Webhook de teste: `POST /webhooks/pix/mock` (público).
- Simulação manual: `/finance/pix/simulate-pay` (ambiente de desenvolvimento).

## Dashboard financeiro

- `/finance` — KPIs via `FinanceDashboardService` (totais em aberto, recebidos, etc.).

## Referências

- `app/Services/PaymentService.php`
- `app/Services/InstallmentService.php`
- `app/Services/AccountsReceivableService.php`
- `docs/arquitetura/financeiro.md`
- `docs/implementacoes/06-financeiro-basico.md`, `07-parcelamento.md`, `18-integracao-pix.md`
