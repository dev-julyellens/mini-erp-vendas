# Débito técnico

Consolidado de `docs/implementacoes/20-refatoracao-tecnica-geral.md` e análise do código.

## Repositórios

- Apenas `CustomerRepository` estende `BaseRepository` com PDO injetável.
- Demais repositórios instanciam PDO diretamente — duplicação e dificuldade de teste.

## Transações

- Vários services usam `beginTransaction` / `commit` / `rollBack` manual repetido.
- `Database::transaction()` existe na camada Core mas adoção é parcial.

## Logging

- `App\Core\Logger` (Monolog) adotado em handlers API; `error_log` pode permanecer em pontos legados.
- Meta: substituir logs remanescentes por Logger estruturado.

## Testes e qualidade

- PHPUnit cobre principalmente `tests/Unit` (Helpers, Core).
- PHPStan nível 5 (`composer analyse`) — subir gradualmente.
- Poucos testes de integração com banco mockado.

## Padrões

- Mensagens de domínio em inglês nos services; padronizar ou i18n.
- `RoutePermissionMap` estático — toda rota nova precisa entrada manual.
- `Env::load` marcado legado — manter até remoção planejada.

## Infraestrutura de schema

- `database.sql` nem sempre reflete última migration sem processo de regeneração.
- `stock_movements` sem `company_id` — join obrigatório, complexidade em queries.

## Próximos passos sugeridos (incrementais)

1. Migrar repositórios para `BaseRepository`.
2. Refatorar transações para `Database::transaction()`.
3. Ampliar testes de services com PDO mock.
4. Fechar gaps de ACL no mapa de rotas.
5. Implementar gateway PIX real atrás de `PixGatewayInterface`.
