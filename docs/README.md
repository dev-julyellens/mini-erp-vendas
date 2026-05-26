# Documentação técnica — Mini ERP de Vendas

Documentação para manutenção e desenvolvimento assistido por IA.

## Estrutura

```
docs/
├── arquitetura/      # Visão por módulo técnico (banco, auth, vendas, …)
├── implementacoes/   # Histórico por feature/prompt (01–20)
├── regras-negocio/   # Fluxos e regras de domínio
└── bugs/             # Conhecidos, limitações, débito técnico
```

## Arquitetura (`docs/arquitetura/`)

| Arquivo | Conteúdo |
|---------|----------|
| [banco.md](arquitetura/banco.md) | Schema, migrations, multi-tenant |
| [autenticacao.md](arquitetura/autenticacao.md) | Sessão, JWT, multi-empresa |
| [vendas.md](arquitetura/vendas.md) | Pedidos e itens |
| [estoque.md](arquitetura/estoque.md) | Movimentações |
| [financeiro.md](arquitetura/financeiro.md) | AR, parcelas, PIX, fluxo |
| [produtos.md](arquitetura/produtos.md) | Catálogo e categorias |
| [api.md](arquitetura/api.md) | REST e rate limit |
| [frontend.md](arquitetura/frontend.md) | Views e assets |
| [permissoes.md](arquitetura/permissoes.md) | ACL por papel |
| [multiempresa.md](arquitetura/multiempresa.md) | Tenant, troca de empresa, vínculos |
| [usuarios.md](arquitetura/usuarios.md) | Gestão de usuários e perfil |
| [saas.md](arquitetura/saas.md) | Administração SaaS da plataforma |
| [devops.md](arquitetura/devops.md) | CI, `.env`, deploy, migrations |

Cada documento segue: visão geral, fluxo, banco, services, repositories, controllers, regras, fluxo de dados, pontos críticos, dependências, melhorias.

## Regras de negócio (`docs/regras-negocio/`)

- [fluxo-vendas.md](regras-negocio/fluxo-vendas.md)
- [fluxo-financeiro.md](regras-negocio/fluxo-financeiro.md)
- [controle-estoque.md](regras-negocio/controle-estoque.md)
- [permissoes.md](regras-negocio/permissoes.md)
- [cancelamentos.md](regras-negocio/cancelamentos.md)
- [auditoria.md](regras-negocio/auditoria.md)

## Checklists de evolução (`docs/implementacoes/`)

| Arquivo | Conteúdo |
|---------|----------|
| [checklist-refatoracao-ui.md](implementacoes/checklist-refatoracao-ui.md) | UI/UX — telas, componentes, design system |
| [checklist-melhorias-projeto.md](implementacoes/checklist-melhorias-projeto.md) | Melhorias globais — segurança, backend, API, testes, CI, a11y |

## Bugs e qualidade (`docs/bugs/`)

- [bugs-conhecidos.md](bugs/bugs-conhecidos.md)
- [limitacoes.md](bugs/limitacoes.md)
- [debito-tecnico.md](bugs/debito-tecnico.md)
- [pontos-atencao.md](bugs/pontos-atencao.md)

## Código de referência

- Rotas: `public/index.php`
- Bootstrap: `app/bootstrap.php`
- Schema: `database/database.sql`, `database/migrations/`

**Importante:** documentação descreve o comportamento **atual** do código. Não substitui leitura do código em mudanças críticas.
