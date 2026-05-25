-- Migration: ACL permissions + role_permissions
-- Execute: php database/run_migration.php

CREATE TABLE IF NOT EXISTS permissions (
    id SERIAL PRIMARY KEY,
    module VARCHAR(50) NOT NULL,
    action VARCHAR(50) NOT NULL,
    CONSTRAINT permissions_module_action_unique UNIQUE (module, action),
    CONSTRAINT permissions_module_check CHECK (
        module IN ('produtos', 'clientes', 'vendas', 'estoque', 'financeiro', 'usuarios')
    ),
    CONSTRAINT permissions_action_check CHECK (
        action IN ('visualizar', 'criar', 'editar', 'excluir')
    )
);

CREATE INDEX IF NOT EXISTS idx_permissions_module ON permissions (module);

CREATE TABLE IF NOT EXISTS role_permissions (
    role VARCHAR(50) NOT NULL,
    permission_id INTEGER NOT NULL REFERENCES permissions (id) ON DELETE CASCADE,
    CONSTRAINT role_permissions_pkey PRIMARY KEY (role, permission_id),
    CONSTRAINT role_permissions_role_check CHECK (
        role IN ('admin', 'vendedor', 'financeiro', 'estoque')
    )
);

CREATE INDEX IF NOT EXISTS idx_role_permissions_role ON role_permissions (role);

-- Permissões base (24 = 6 módulos × 4 ações)
INSERT INTO permissions (module, action)
SELECT m.module, a.action
FROM (
    VALUES
        ('produtos'),
        ('clientes'),
        ('vendas'),
        ('estoque'),
        ('financeiro'),
        ('usuarios')
) AS m (module)
CROSS JOIN (
    VALUES
        ('visualizar'),
        ('criar'),
        ('editar'),
        ('excluir')
) AS a (action)
ON CONFLICT (module, action) DO NOTHING;

-- vendedor: clientes e vendas completos; produtos/estoque só visualizar
INSERT INTO role_permissions (role, permission_id)
SELECT 'vendedor', p.id
FROM permissions p
WHERE (p.module = 'clientes' AND p.action IN ('visualizar', 'criar', 'editar', 'excluir'))
   OR (p.module = 'vendas' AND p.action IN ('visualizar', 'criar', 'editar'))
   OR (p.module IN ('produtos', 'estoque') AND p.action = 'visualizar')
ON CONFLICT DO NOTHING;

-- financeiro: financeiro completo; clientes e vendas visualizar; produtos visualizar
INSERT INTO role_permissions (role, permission_id)
SELECT 'financeiro', p.id
FROM permissions p
WHERE (p.module = 'financeiro' AND p.action IN ('visualizar', 'criar', 'editar', 'excluir'))
   OR (p.module IN ('clientes', 'vendas', 'produtos') AND p.action = 'visualizar')
ON CONFLICT DO NOTHING;

-- estoque: estoque completo; produtos visualizar/editar; vendas visualizar
INSERT INTO role_permissions (role, permission_id)
SELECT 'estoque', p.id
FROM permissions p
WHERE (p.module = 'estoque' AND p.action IN ('visualizar', 'criar', 'editar', 'excluir'))
   OR (p.module = 'produtos' AND p.action IN ('visualizar', 'editar'))
   OR (p.module = 'vendas' AND p.action = 'visualizar')
ON CONFLICT DO NOTHING;
