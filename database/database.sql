-- Mini ERP de Vendas - PostgreSQL schema + seed
-- Encoding: UTF-8

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
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT orders_total_non_negative CHECK (total_amount >= 0)
);

CREATE INDEX idx_orders_customer ON orders (customer_id);
CREATE INDEX idx_orders_created_at ON orders (created_at);

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
