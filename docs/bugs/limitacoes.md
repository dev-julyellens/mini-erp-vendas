# Limitações atuais

## Funcionais

- **Sem contas a pagar** — apenas contas a receber e fluxo de caixa de entradas derivadas de recebimentos.
- **Sem NF-e / fiscal** — não há emissão de documentos fiscais eletrônicos.
- **Um depósito de estoque** — sem multi-armazém ou lote/série.
- **PIX mock** — QR e conciliação simulados; sem PSP real.
- **E-mail transacional** — reset de senha e notificações críticas não usam SMTP integrado.
- **Usuários** — CRUD de usuários não exposto como módulo completo na UI (gestão limitada ao seed/admin).
- **Moeda única** — sem multi-moeda ou câmbio.

## Operacionais

- **Backup** depende de `pg_dump`/`psql` no servidor e paths configurados.
- **Rate limit API** — baseado em buckets no banco; não distribuído para múltiplos nós sem store compartilhado.
- **Sessão** — sticky session implícita (armazenamento em arquivo/PHP default); cluster exige configuração externa.

## SaaS

- Limites de plano (`PlanLimitService`) cobrem entidades principais (ex.: clientes, produtos); não há billing automático com gateway real documentado além do fluxo interno de assinatura.

## Plataforma

- PHP monolito síncrono — sem filas/workers para tarefas pesadas (relatórios grandes, exports massivos).
- Frontend server-rendered — sem PWA/offline.

## Documentação

- `docs/implementacoes/` cobre features por prompt; `docs/arquitetura/` cobre visão transversal — manter sincronizado após mudanças de código.
