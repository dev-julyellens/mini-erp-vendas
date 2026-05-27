# Pontos de atenção

Checklist para deploy, revisão de PR e uso do Cursor Agent.

## Segurança

- [ ] Definir `JWT_SECRET` forte em produção.
- [ ] `APP_DEBUG=false` em produção.
- [ ] Revisar exposição de `POST /webhooks/pix/mock`.
- [ ] Cookie de sessão em HTTPS (`session.cookie_secure` conforme ambiente).
- [ ] Rotacionar senha do seed `admin@mini-erp.local` após primeira instalação.
- [ ] Configurar `PIX_WEBHOOK_SECRET` se gateway real for adicionado.

## Dados

- [ ] Rodar migrations após deploy: `php database/run_migration.php`.
- [ ] Validar backup agendado (`scripts/` + `BackupService`).
- [ ] Retenção de `api_logs` e `access_logs` — crescimento indefinido.

## Concorrência

- [ ] Vendas simultâneas no mesmo SKU — dependem de lock em `OrderService`; monitorar deadlocks em PostgreSQL.
- [ ] Rate limit API por instância — escalar horizontalmente requer bucket compartilhado.

## Negócio

- [ ] Cancelamento: validar AR/parcelas pagas antes de permitir na UI.
- [ ] Parcelado: orientar operadores a receber via tela de parcelas, não AR.
- [x] Status do pedido na criação — pedido `pending`, AR `pending`; pedido vira `paid` ao quitar a conta.

## Desenvolvimento assistido por IA

- Consultar `docs/arquitetura/` antes de alterar módulos.
- Consultar `docs/regras-negocio/` para não violar fluxos transacionais.
- Não inferir features ausentes em `docs/bugs/limitacoes.md`.
- Após mudança de código, atualizar doc correspondente.

## Comandos de verificação

```bash
composer install
composer check   # PHPStan + PHPUnit
```
