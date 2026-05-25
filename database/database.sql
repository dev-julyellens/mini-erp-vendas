-- Mini ERP de Vendas - PostgreSQL schema + seed
-- Encoding: UTF-8

DROP TABLE IF EXISTS stock_movements CASCADE;
DROP TABLE IF EXISTS audit_logs CASCADE;
DROP TABLE IF EXISTS role_permissions CASCADE;
DROP TABLE IF EXISTS permissions CASCADE;
DROP TABLE IF EXISTS password_reset_tokens CASCADE;
DROP TABLE IF EXISTS order_items CASCADE;
DROP TABLE IF EXISTS orders CASCADE;
DROP TABLE IF EXISTS products CASCADE;
DROP TABLE IF EXISTS customers CASCADE;
DROP TABLE IF EXISTS users CASCADE;

CREATE TABLE users (
    id SERIAL PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    email VARCHAR(255) NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    role VARCHAR(50) NOT NULL,
    active BOOLEAN NOT NULL DEFAULT TRUE,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT users_email_unique UNIQUE (email),
    CONSTRAINT users_role_check CHECK (role IN ('admin', 'vendedor', 'financeiro', 'estoque'))
);

CREATE INDEX idx_users_email_lower ON users (LOWER(email));
CREATE INDEX idx_users_active ON users (active) WHERE active = TRUE;

CREATE TABLE password_reset_tokens (
    id SERIAL PRIMARY KEY,
    user_id INTEGER NOT NULL REFERENCES users (id) ON DELETE CASCADE,
    token_hash VARCHAR(64) NOT NULL,
    expires_at TIMESTAMP NOT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
);

CREATE INDEX idx_password_reset_token_hash ON password_reset_tokens (token_hash);
CREATE INDEX idx_password_reset_user ON password_reset_tokens (user_id);

CREATE TABLE permissions (
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

CREATE INDEX idx_permissions_module ON permissions (module);

CREATE TABLE role_permissions (
    role VARCHAR(50) NOT NULL,
    permission_id INTEGER NOT NULL REFERENCES permissions (id) ON DELETE CASCADE,
    CONSTRAINT role_permissions_pkey PRIMARY KEY (role, permission_id),
    CONSTRAINT role_permissions_role_check CHECK (
        role IN ('admin', 'vendedor', 'financeiro', 'estoque')
    )
);

CREATE INDEX idx_role_permissions_role ON role_permissions (role);

CREATE TABLE customers (
    id SERIAL PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    email VARCHAR(255) NOT NULL UNIQUE,
    phone VARCHAR(50),
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE products (
    id SERIAL PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    description TEXT,
    price NUMERIC(12, 2) NOT NULL,
    stock INTEGER NOT NULL,
    CONSTRAINT products_price_positive CHECK (price > 0),
    CONSTRAINT products_stock_non_negative CHECK (stock >= 0)
);

CREATE TABLE orders (
    id SERIAL PRIMARY KEY,
    customer_id INTEGER NOT NULL REFERENCES customers (id) ON DELETE RESTRICT,
    total_amount NUMERIC(14, 2) NOT NULL DEFAULT 0,
    status VARCHAR(20) NOT NULL DEFAULT 'paid',
    canceled_by INTEGER REFERENCES users (id) ON DELETE SET NULL,
    canceled_at TIMESTAMP,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT orders_total_non_negative CHECK (total_amount >= 0),
    CONSTRAINT orders_status_check CHECK (
        status IN ('pending', 'paid', 'canceled', 'refunded')
    )
);

CREATE INDEX idx_orders_customer ON orders (customer_id);
CREATE INDEX idx_orders_created_at ON orders (created_at);
CREATE INDEX idx_orders_status ON orders (status);

CREATE TABLE order_items (
    id SERIAL PRIMARY KEY,
    order_id INTEGER NOT NULL REFERENCES orders (id) ON DELETE CASCADE,
    product_id INTEGER NOT NULL REFERENCES products (id) ON DELETE RESTRICT,
    quantity INTEGER NOT NULL,
    unit_price NUMERIC(12, 2) NOT NULL,
    subtotal NUMERIC(14, 2) NOT NULL,
    CONSTRAINT order_items_quantity_positive CHECK (quantity > 0),
    CONSTRAINT order_items_prices_positive CHECK (unit_price > 0 AND subtotal > 0)
);

CREATE INDEX idx_order_items_order ON order_items (order_id);
CREATE INDEX idx_order_items_product ON order_items (product_id);

CREATE TABLE stock_movements (
    id SERIAL PRIMARY KEY,
    product_id INTEGER NOT NULL REFERENCES products (id) ON DELETE RESTRICT,
    type VARCHAR(20) NOT NULL,
    quantity INTEGER NOT NULL,
    reference_type VARCHAR(50),
    reference_id INTEGER,
    notes TEXT,
    created_by INTEGER REFERENCES users (id) ON DELETE SET NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT stock_movements_type_check CHECK (
        type IN ('entrada', 'saida', 'ajuste', 'devolucao', 'perda', 'inventario')
    ),
    CONSTRAINT stock_movements_quantity_non_zero CHECK (quantity <> 0)
);

CREATE INDEX idx_stock_movements_product_id ON stock_movements (product_id);
CREATE INDEX idx_stock_movements_type ON stock_movements (type);
CREATE INDEX idx_stock_movements_created_at ON stock_movements (created_at DESC);
CREATE INDEX idx_stock_movements_product_created ON stock_movements (product_id, created_at DESC);
CREATE INDEX idx_stock_movements_reference ON stock_movements (reference_type, reference_id);

CREATE TABLE audit_logs (
    id SERIAL PRIMARY KEY,
    user_id INTEGER REFERENCES users (id) ON DELETE SET NULL,
    action VARCHAR(50) NOT NULL,
    entity VARCHAR(50) NOT NULL,
    entity_id INTEGER,
    old_values JSONB,
    new_values JSONB,
    ip_address VARCHAR(45),
    user_agent TEXT,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT audit_logs_action_check CHECK (
        action IN (
            'criar',
            'editar',
            'excluir',
            'login',
            'logout',
            'solicitar_redefinir_senha',
            'redefinir_senha',
            'venda',
            'saida_estoque',
            'cancelamento_venda',
            'entrada_estoque'
        )
    ),
    CONSTRAINT audit_logs_entity_check CHECK (
        entity IN ('produtos', 'clientes', 'vendas', 'estoque', 'usuarios')
    )
);

CREATE INDEX idx_audit_logs_user_id ON audit_logs (user_id);
CREATE INDEX idx_audit_logs_entity ON audit_logs (entity);
CREATE INDEX idx_audit_logs_entity_id ON audit_logs (entity_id);
CREATE INDEX idx_audit_logs_created_at ON audit_logs (created_at DESC);
CREATE INDEX idx_audit_logs_user_created ON audit_logs (user_id, created_at DESC);

-- ---------------------------------------------------------------------------
-- Seed data
-- ---------------------------------------------------------------------------

INSERT INTO customers (name, email, phone) VALUES
    ('Maria Silva', 'maria.silva@example.com', '(11) 98888-1111'),
    ('João Santos', 'joao.santos@example.com', '(21) 97777-2222'),
    ('Tech LTDA', 'contato@techltda.example.com', '(31) 96666-3333');

INSERT INTO users (name, email, password_hash, role, active) VALUES
    ('Administrador', 'admin@mini-erp.local', '$2y$10$XNyBEjcS0aobvO6spDzXWOSwE.SxMSJhh59KUBcOOFTGSlAZdSwxe', 'admin', TRUE);

INSERT INTO products (name, description, price, stock) VALUES
    ('Notebook Pro 14', 'Ultrafino, 16GB RAM', 4599.90, 8),
    ('Mouse sem fio', 'Sensor óptico, ergonomia', 129.90, 40),
    ('Teclado mecânico', 'Switch brown, RGB', 549.00, 12),
    ('Monitor 27" 4K', 'IPS, 60Hz', 1899.00, 3),
    ('Webcam HD', '1080p, microfone integrado', 249.90, 2);

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
) AS a (action);

INSERT INTO role_permissions (role, permission_id)
SELECT 'vendedor', p.id
FROM permissions p
WHERE (p.module = 'clientes' AND p.action IN ('visualizar', 'criar', 'editar', 'excluir'))
   OR (p.module = 'vendas' AND p.action IN ('visualizar', 'criar', 'editar'))
   OR (p.module IN ('produtos', 'estoque') AND p.action = 'visualizar');

INSERT INTO role_permissions (role, permission_id)
SELECT 'financeiro', p.id
FROM permissions p
WHERE (p.module = 'financeiro' AND p.action IN ('visualizar', 'criar', 'editar', 'excluir'))
   OR (p.module IN ('clientes', 'vendas', 'produtos') AND p.action = 'visualizar');

INSERT INTO role_permissions (role, permission_id)
SELECT 'estoque', p.id
FROM permissions p
WHERE (p.module = 'estoque' AND p.action IN ('visualizar', 'criar', 'editar', 'excluir'))
   OR (p.module = 'produtos' AND p.action IN ('visualizar', 'editar'))
   OR (p.module = 'vendas' AND p.action = 'visualizar');
