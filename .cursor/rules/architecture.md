# Arquitetura do Projeto

Este projeto é um mini ERP PHP + PostgreSQL.

Padrões obrigatórios:
- Controllers não possuem SQL
- Controllers não possuem regra de negócio
- Toda regra deve ficar em Services
- Toda persistência deve ficar em Repositories
- Utilizar PostgreSQL
- Utilizar transações em operações críticas
- Utilizar prepared statements
- Nunca duplicar lógica
- Aplicar SOLID
- Aplicar DRY

Frontend:
- Bootstrap
- Responsivo
- UX limpa

Segurança:
- Sanitizar inputs
- Validar autenticação
- Validar permissões

IMPORTANTE:
Nunca refatorar módulos não relacionados à tarefa atual.