# Regras de negócio — Fluxo de vendas

Documentação derivada de `OrderService`, `OrderController` e `ApiOrderController`.

## Pré-condições

- Usuário autenticado com permissão `vendas.criar` (web ou API).
- Empresa ativa em `CompanyContext`.
- Cliente existente e pertencente à empresa.
- Assinatura SaaS ativa (middleware `SubscriptionMiddleware`).

## Criação da venda

1. **Itens obrigatórios** — pelo menos uma linha com `product_id` e `quantity` positivos.
2. **Mesclagem** — linhas duplicadas do mesmo produto somam quantidade.
3. **Produto** — deve existir na empresa; lock `FOR UPDATE` durante a transação.
4. **Estoque** — para `type=product`, `stock >= quantity`; serviços ignoram estoque.
5. **Preço** — `unit_price` gravado em `order_items` = preço do catálogo no momento da venda.
6. **Total** — soma dos subtotais (`Money::mul` / `Money::add`).
7. **Status do pedido** — gravado como `paid` na criação (auditoria inclui `status: paid`).
8. **Estoque** — baixa automática tipo `saida`, referência `order`.
9. **Financeiro** — sempre gera `accounts_receivable` com status `pending`.
10. **Parcelamento** — opcional:
    - `installment_count = 1` (default): uma AR, vencimento +30 dias (`AccountsReceivableService::DEFAULT_DUE_DAYS`).
    - `installment_count` de 2 a 24: gera parcelas; vencimento da AR = primeira parcela.

## Consulta e listagem

- Filtros por período e cliente conforme repository.
- Pedidos cancelados permanecem visíveis com status `canceled`.
- Detalhe exibe itens com preços históricos.

## Integridade

- Transação atômica: falha em estoque, AR ou parcelas reverte tudo.
- Ordenação de produtos por ID antes dos locks (prevenção de deadlock).

## Referências

- Código: `app/Services/OrderService.php`
- Arquitetura: `docs/arquitetura/vendas.md`
- Implementação histórica: `docs/implementacoes/04-movimentacao-estoque.md` (estoque na venda)
