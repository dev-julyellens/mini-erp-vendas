-- Mini ERP de Vendas - PostgreSQL schema + seed
-- Encoding: UTF-8

DROP TABLE IF EXISTS backup_logs CASCADE;
DROP TABLE IF EXISTS backup_settings CASCADE;
DROP TABLE IF EXISTS api_logs CASCADE;
DROP TABLE IF EXISTS api_rate_limit_buckets CASCADE;
DROP TABLE IF EXISTS cash_flow CASCADE;
DROP TABLE IF EXISTS payments CASCADE;
DROP TABLE IF EXISTS installments CASCADE;
DROP TABLE IF EXISTS accounts_receivable CASCADE;
DROP TABLE IF EXISTS stock_movements CASCADE;
DROP TABLE IF EXISTS audit_logs CASCADE;
DROP TABLE IF EXISTS role_permissions CASCADE;
DROP TABLE IF EXISTS permissions CASCADE;
DROP TABLE IF EXISTS password_reset_tokens CASCADE;
DROP TABLE IF EXISTS order_items CASCADE;
DROP TABLE IF EXISTS orders CASCADE;
DROP TABLE IF EXISTS products CASCADE;
DROP TABLE IF EXISTS categories CASCADE;
DROP TABLE IF EXISTS customers CASCADE;
DROP TABLE IF EXISTS user_companies CASCADE;
DROP TABLE IF EXISTS companies CASCADE;
DROP TABLE IF EXISTS users CASCADE;

CREATE TABLE companies (
    id SERIAL PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    tax_id VARCHAR(20),
    active BOOLEAN NOT NULL DEFAULT TRUE,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT companies_name_unique UNIQUE (name)
);

CREATE INDEX idx_companies_active ON companies (active) WHERE active = TRUE;

INSERT INTO companies (id, name, active) VALUES (1, 'Empresa Padrão', TRUE);
SELECT setval(pg_get_serial_sequence('companies', 'id'), 1);

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

CREATE TABLE user_companies (
    user_id INTEGER NOT NULL REFERENCES users (id) ON DELETE CASCADE,
    company_id INTEGER NOT NULL REFERENCES companies (id) ON DELETE CASCADE,
    CONSTRAINT user_companies_pkey PRIMARY KEY (user_id, company_id)
);

CREATE INDEX idx_user_companies_company ON user_companies (company_id);

CREATE TABLE customers (
    id SERIAL PRIMARY KEY,
    company_id INTEGER NOT NULL REFERENCES companies (id),
    name VARCHAR(255) NOT NULL,
    email VARCHAR(255) NOT NULL,
    phone VARCHAR(50),
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
);

CREATE UNIQUE INDEX customers_company_email_unique ON customers (company_id, LOWER(email));
CREATE INDEX idx_customers_company ON customers (company_id);

CREATE TABLE categories (
    id SERIAL PRIMARY KEY,
    company_id INTEGER NOT NULL REFERENCES companies (id),
    name VARCHAR(120) NOT NULL,
    description TEXT,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
);

CREATE UNIQUE INDEX categories_company_name_unique ON categories (company_id, LOWER(name));

CREATE INDEX idx_categories_company ON categories (company_id);
CREATE INDEX idx_categories_name ON categories (name);

CREATE TABLE products (
    id SERIAL PRIMARY KEY,
    company_id INTEGER NOT NULL REFERENCES companies (id),
    name VARCHAR(255) NOT NULL,
    description TEXT,
    sku VARCHAR(50) NOT NULL,
    barcode VARCHAR(50),
    category_id INTEGER REFERENCES categories (id) ON DELETE SET NULL,
    unit_of_measure VARCHAR(10) NOT NULL DEFAULT 'UN',
    cost_price NUMERIC(12, 2) NOT NULL DEFAULT 0,
    margin_percent NUMERIC(8, 2),
    markup_percent NUMERIC(8, 2),
    price NUMERIC(12, 2) NOT NULL,
    stock INTEGER NOT NULL,
    min_stock INTEGER NOT NULL DEFAULT 5,
    type VARCHAR(20) NOT NULL DEFAULT 'product',
    estimated_time_minutes INTEGER,
    CONSTRAINT products_price_positive CHECK (price > 0),
    CONSTRAINT products_stock_non_negative CHECK (stock >= 0),
    CONSTRAINT products_min_stock_non_negative CHECK (min_stock >= 0),
    CONSTRAINT products_cost_non_negative CHECK (cost_price >= 0),
    CONSTRAINT products_type_check CHECK (type IN ('product', 'service')),
    CONSTRAINT products_estimated_time_non_negative CHECK (
        estimated_time_minutes IS NULL OR estimated_time_minutes >= 0
    )
);

CREATE UNIQUE INDEX products_company_sku_unique ON products (company_id, UPPER(sku));
CREATE UNIQUE INDEX products_company_barcode_unique ON products (company_id, barcode) WHERE barcode IS NOT NULL;
CREATE INDEX idx_products_company ON products (company_id);
CREATE INDEX idx_products_sku ON products (sku);
CREATE INDEX idx_products_category ON products (category_id);
CREATE INDEX idx_products_type ON products (type);
CREATE INDEX idx_products_low_stock ON products (type, stock, min_stock)
    WHERE type = 'product';

CREATE TABLE orders (
    id SERIAL PRIMARY KEY,
    company_id INTEGER NOT NULL REFERENCES companies (id),
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

CREATE INDEX idx_orders_company ON orders (company_id);
CREATE INDEX idx_orders_customer ON orders (customer_id);
CREATE INDEX idx_orders_created_at ON orders (created_at);
CREATE INDEX idx_orders_status ON orders (status);
CREATE INDEX idx_orders_status_created_at ON orders (status, created_at DESC);

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
CREATE INDEX idx_order_items_product_order ON order_items (product_id, order_id);

CREATE TABLE accounts_receivable (
    id SERIAL PRIMARY KEY,
    order_id INTEGER NOT NULL REFERENCES orders (id) ON DELETE RESTRICT,
    customer_id INTEGER NOT NULL REFERENCES customers (id) ON DELETE RESTRICT,
    amount NUMERIC(14, 2) NOT NULL,
    due_date DATE NOT NULL,
    status VARCHAR(20) NOT NULL DEFAULT 'pending',
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT accounts_receivable_order_unique UNIQUE (order_id),
    CONSTRAINT accounts_receivable_amount_positive CHECK (amount > 0),
    CONSTRAINT accounts_receivable_status_check CHECK (
        status IN ('pending', 'partial', 'paid', 'canceled')
    )
);

CREATE INDEX idx_accounts_receivable_customer ON accounts_receivable (customer_id);
CREATE INDEX idx_accounts_receivable_status ON accounts_receivable (status);
CREATE INDEX idx_accounts_receivable_due_date ON accounts_receivable (due_date);
CREATE INDEX idx_accounts_receivable_order ON accounts_receivable (order_id);

CREATE TABLE payments (
    id SERIAL PRIMARY KEY,
    accounts_receivable_id INTEGER NOT NULL REFERENCES accounts_receivable (id) ON DELETE RESTRICT,
    amount NUMERIC(14, 2) NOT NULL,
    payment_method VARCHAR(20) NOT NULL,
    paid_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    received_by INTEGER REFERENCES users (id) ON DELETE SET NULL,
    notes TEXT,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT payments_amount_positive CHECK (amount > 0),
    CONSTRAINT payments_method_check CHECK (
        payment_method IN ('dinheiro', 'pix', 'cartao', 'boleto')
    )
);

CREATE INDEX idx_payments_accounts_receivable ON payments (accounts_receivable_id);
CREATE INDEX idx_payments_paid_at ON payments (paid_at DESC);
CREATE INDEX idx_payments_method ON payments (payment_method);

CREATE TABLE installments (
    id SERIAL PRIMARY KEY,
    order_id INTEGER NOT NULL REFERENCES orders (id) ON DELETE RESTRICT,
    installment_number SMALLINT NOT NULL,
    amount NUMERIC(14, 2) NOT NULL,
    due_date DATE NOT NULL,
    paid_at TIMESTAMP,
    status VARCHAR(20) NOT NULL DEFAULT 'pending',
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT installments_order_number_unique UNIQUE (order_id, installment_number),
    CONSTRAINT installments_number_positive CHECK (installment_number > 0),
    CONSTRAINT installments_amount_positive CHECK (amount > 0),
    CONSTRAINT installments_status_check CHECK (
        status IN ('pending', 'overdue', 'paid', 'canceled')
    )
);

CREATE INDEX idx_installments_order ON installments (order_id);
CREATE INDEX idx_installments_status ON installments (status);
CREATE INDEX idx_installments_due_date ON installments (due_date);
CREATE INDEX idx_installments_paid_at ON installments (paid_at DESC);

CREATE TABLE cash_flow (
    id SERIAL PRIMARY KEY,
    company_id INTEGER NOT NULL REFERENCES companies (id),
    type VARCHAR(10) NOT NULL,
    amount NUMERIC(14, 2) NOT NULL,
    payment_method VARCHAR(20),
    reference_type VARCHAR(50),
    reference_id INTEGER,
    description TEXT,
    occurred_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    created_by INTEGER REFERENCES users (id) ON DELETE SET NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT cash_flow_type_check CHECK (type IN ('entrada', 'saida')),
    CONSTRAINT cash_flow_amount_positive CHECK (amount > 0),
    CONSTRAINT cash_flow_method_check CHECK (
        payment_method IS NULL
        OR payment_method IN ('dinheiro', 'pix', 'cartao', 'boleto')
    )
);

CREATE INDEX idx_cash_flow_type ON cash_flow (type);
CREATE INDEX idx_cash_flow_occurred_at ON cash_flow (occurred_at DESC);
CREATE INDEX idx_cash_flow_reference ON cash_flow (reference_type, reference_id);
CREATE INDEX idx_cash_flow_company ON cash_flow (company_id);
CREATE INDEX idx_cash_flow_occurred_type ON cash_flow (occurred_at DESC, type);

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
    company_id INTEGER REFERENCES companies (id) ON DELETE SET NULL,
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
            'entrada_estoque',
            'conta_receber',
            'recebimento',
            'cancelamento_conta_receber',
            'parcelamento',
            'recebimento_parcela',
            'cancelamento_parcelas'
        )
    ),
    CONSTRAINT audit_logs_entity_check CHECK (
        entity IN ('produtos', 'clientes', 'vendas', 'estoque', 'usuarios', 'financeiro')
    )
);

CREATE INDEX idx_audit_logs_user_id ON audit_logs (user_id);
CREATE INDEX idx_audit_logs_entity ON audit_logs (entity);
CREATE INDEX idx_audit_logs_entity_id ON audit_logs (entity_id);
CREATE INDEX idx_audit_logs_created_at ON audit_logs (created_at DESC);
CREATE INDEX idx_audit_logs_user_created ON audit_logs (user_id, created_at DESC);
CREATE INDEX idx_audit_logs_company ON audit_logs (company_id);

CREATE TABLE api_logs (
    id SERIAL PRIMARY KEY,
    user_id INTEGER REFERENCES users (id) ON DELETE SET NULL,
    ip_address VARCHAR(45) NOT NULL,
    http_method VARCHAR(10) NOT NULL,
    endpoint VARCHAR(255) NOT NULL,
    payload JSONB,
    status_code SMALLINT,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
);

CREATE INDEX idx_api_logs_user_id ON api_logs (user_id);
CREATE INDEX idx_api_logs_endpoint ON api_logs (endpoint);
CREATE INDEX idx_api_logs_created_at ON api_logs (created_at DESC);
CREATE INDEX idx_api_logs_ip_created ON api_logs (ip_address, created_at DESC);

CREATE TABLE api_rate_limit_buckets (
    bucket_key VARCHAR(255) PRIMARY KEY,
    request_count INTEGER NOT NULL DEFAULT 0,
    reset_at TIMESTAMP NOT NULL
);

CREATE INDEX idx_api_rate_limit_reset_at ON api_rate_limit_buckets (reset_at);

CREATE TABLE backup_settings (
    id SMALLINT PRIMARY KEY DEFAULT 1,
    enabled BOOLEAN NOT NULL DEFAULT FALSE,
    run_hour SMALLINT NOT NULL DEFAULT 2,
    run_minute SMALLINT NOT NULL DEFAULT 0,
    frequency VARCHAR(20) NOT NULL DEFAULT 'daily',
    last_run_at TIMESTAMP NULL,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_by_user_id INTEGER REFERENCES users (id) ON DELETE SET NULL,
    CONSTRAINT backup_settings_singleton CHECK (id = 1),
    CONSTRAINT backup_settings_run_hour_check CHECK (run_hour >= 0 AND run_hour <= 23),
    CONSTRAINT backup_settings_run_minute_check CHECK (run_minute >= 0 AND run_minute <= 59),
    CONSTRAINT backup_settings_frequency_check CHECK (frequency IN ('daily'))
);

CREATE TABLE backup_logs (
    id SERIAL PRIMARY KEY,
    operation VARCHAR(20) NOT NULL,
    trigger_type VARCHAR(20) NOT NULL,
    filename VARCHAR(255),
    file_size BIGINT,
    status VARCHAR(20) NOT NULL,
    message TEXT,
    user_id INTEGER REFERENCES users (id) ON DELETE SET NULL,
    duration_ms INTEGER,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT backup_logs_operation_check CHECK (
        operation IN ('backup', 'restore', 'cleanup')
    ),
    CONSTRAINT backup_logs_trigger_check CHECK (
        trigger_type IN ('manual', 'automatic', 'cron')
    ),
    CONSTRAINT backup_logs_status_check CHECK (
        status IN ('success', 'failed', 'running')
    )
);

CREATE INDEX idx_backup_logs_created_at ON backup_logs (created_at DESC);
CREATE INDEX idx_backup_logs_operation ON backup_logs (operation);
CREATE INDEX idx_backup_logs_status ON backup_logs (status);

-- ---------------------------------------------------------------------------
-- Seed data
-- ---------------------------------------------------------------------------

INSERT INTO customers (company_id, name, email, phone) VALUES
    (1, 'Maria Silva', 'maria.silva@example.com', '(11) 98888-1111'),
    (1, 'João Santos', 'joao.santos@example.com', '(21) 97777-2222'),
    (1, 'Tech LTDA', 'contato@techltda.example.com', '(31) 96666-3333');

INSERT INTO users (name, email, password_hash, role, active) VALUES
    ('Administrador', 'admin@mini-erp.local', '$2y$10$XNyBEjcS0aobvO6spDzXWOSwE.SxMSJhh59KUBcOOFTGSlAZdSwxe', 'admin', TRUE);

INSERT INTO user_companies (user_id, company_id)
SELECT id, 1 FROM users;

INSERT INTO backup_settings (id, enabled, run_hour, run_minute, frequency)
VALUES (1, FALSE, 2, 0, 'daily');

INSERT INTO categories (company_id, name, description) VALUES
    (1, 'Informática', 'Hardware e periféricos'),
    (1, 'Serviços', 'Prestação de serviços');

INSERT INTO products (
    company_id, name, description, sku, category_id, unit_of_measure, cost_price, margin_percent, markup_percent,
    price, stock, min_stock, type, estimated_time_minutes
) VALUES
    (
        1, 'Notebook Pro 14', 'Ultrafino, 16GB RAM', 'SKU-000001',
        (SELECT id FROM categories WHERE name = 'Informática' AND company_id = 1 LIMIT 1),
        'UN', 3200.00, 30.43, 43.75, 4599.90, 8, 5, 'product', NULL
    ),
    (
        1, 'Mouse sem fio', 'Sensor óptico, ergonomia', 'SKU-000002',
        (SELECT id FROM categories WHERE name = 'Informática' AND company_id = 1 LIMIT 1),
        'UN', 65.00, 50.00, 100.00, 129.90, 40, 10, 'product', NULL
    ),
    (
        1, 'Teclado mecânico', 'Switch brown, RGB', 'SKU-000003',
        (SELECT id FROM categories WHERE name = 'Informática' AND company_id = 1 LIMIT 1),
        'UN', 380.00, 30.78, 44.47, 549.00, 12, 5, 'product', NULL
    ),
    (
        1, 'Monitor 27" 4K', 'IPS, 60Hz', 'SKU-000004',
        (SELECT id FROM categories WHERE name = 'Informática' AND company_id = 1 LIMIT 1),
        'UN', 1400.00, 26.28, 35.64, 1899.00, 3, 5, 'product', NULL
    ),
    (
        1, 'Webcam HD', '1080p, microfone integrado', 'SKU-000005',
        (SELECT id FROM categories WHERE name = 'Informática' AND company_id = 1 LIMIT 1),
        'UN', 150.00, 39.98, 66.60, 249.90, 2, 5, 'product', NULL
    ),
    (
        1, 'Instalação e configuração', 'Serviço presencial ou remoto', 'SRV-000001',
        (SELECT id FROM categories WHERE name = 'Serviços' AND company_id = 1 LIMIT 1),
        'HR', 0.00, 100.00, NULL, 150.00, 0, 0, 'service', 90
    ),
    (
        1, 'Manutenção preventiva', 'Revisão periódica de equipamentos', 'SRV-000002',
        (SELECT id FROM categories WHERE name = 'Serviços' AND company_id = 1 LIMIT 1),
        'HR', 0.00, 100.00, NULL, 89.90, 0, 0, 'service', 60
    ),
    (
        1, 'Consultoria técnica', 'Atendimento por hora', 'SRV-000003',
        (SELECT id FROM categories WHERE name = 'Serviços' AND company_id = 1 LIMIT 1),
        'HR', 0.00, 100.00, NULL, 120.00, 0, 0, 'service', 60
    );

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
