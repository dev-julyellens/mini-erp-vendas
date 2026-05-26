# Implementação — Gestão de empresas

## Entregue

- Listagem com busca, filtro de status e paginação
- Cadastro/edição (nome, slug, documento)
- Ativação/desativação (sem exclusão física)
- Validação de slug e documento únicos

## Arquivos principais

- `app/Controllers/CompanyController.php`
- `app/Services/CompanyService.php`
- Extensão `app/Repositories/CompanyRepository.php`
- `app/Views/companies/*`

## Rotas

`/admin/companies` (apenas administrador da plataforma).
