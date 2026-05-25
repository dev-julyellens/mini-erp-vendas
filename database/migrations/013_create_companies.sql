-- Multiempresa: companies, user_companies e company_id nas entidades de negócio

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

INSERT INTO companies (id, name, active)
VALUES (1, 'Empresa Padrão', TRUE);

SELECT setval(pg_get_serial_sequence('companies', 'id'), 1);

CREATE TABLE user_companies (
    user_id INTEGER NOT NULL REFERENCES users (id) ON DELETE CASCADE,
    company_id INTEGER NOT NULL REFERENCES companies (id) ON DELETE CASCADE,
    CONSTRAINT user_companies_pkey PRIMARY KEY (user_id, company_id)
);

CREATE INDEX idx_user_companies_company ON user_companies (company_id);

INSERT INTO user_companies (user_id, company_id)
SELECT id, 1 FROM users;

ALTER TABLE customers ADD COLUMN company_id INTEGER;
UPDATE customers SET company_id = 1 WHERE company_id IS NULL;
ALTER TABLE customers ALTER COLUMN company_id SET NOT NULL;
ALTER TABLE customers
    ADD CONSTRAINT customers_company_fk FOREIGN KEY (company_id) REFERENCES companies (id);
ALTER TABLE customers DROP CONSTRAINT IF EXISTS customers_email_key;
CREATE UNIQUE INDEX customers_company_email_unique ON customers (company_id, LOWER(email));
CREATE INDEX idx_customers_company ON customers (company_id);

ALTER TABLE categories ADD COLUMN company_id INTEGER;
UPDATE categories SET company_id = 1 WHERE company_id IS NULL;
ALTER TABLE categories ALTER COLUMN company_id SET NOT NULL;
ALTER TABLE categories
    ADD CONSTRAINT categories_company_fk FOREIGN KEY (company_id) REFERENCES companies (id);
ALTER TABLE categories DROP CONSTRAINT IF EXISTS categories_name_unique;
CREATE UNIQUE INDEX categories_company_name_unique ON categories (company_id, LOWER(name));
CREATE INDEX idx_categories_company ON categories (company_id);

ALTER TABLE products ADD COLUMN company_id INTEGER;
UPDATE products SET company_id = 1 WHERE company_id IS NULL;
ALTER TABLE products ALTER COLUMN company_id SET NOT NULL;
ALTER TABLE products
    ADD CONSTRAINT products_company_fk FOREIGN KEY (company_id) REFERENCES companies (id);
ALTER TABLE products DROP CONSTRAINT IF EXISTS products_sku_unique;
ALTER TABLE products DROP CONSTRAINT IF EXISTS products_barcode_unique;
CREATE UNIQUE INDEX products_company_sku_unique ON products (company_id, UPPER(sku));
CREATE UNIQUE INDEX products_company_barcode_unique ON products (company_id, barcode)
    WHERE barcode IS NOT NULL;
CREATE INDEX idx_products_company ON products (company_id);

ALTER TABLE orders ADD COLUMN company_id INTEGER;
UPDATE orders o
SET company_id = c.company_id
FROM customers c
WHERE o.customer_id = c.id AND o.company_id IS NULL;
UPDATE orders SET company_id = 1 WHERE company_id IS NULL;
ALTER TABLE orders ALTER COLUMN company_id SET NOT NULL;
ALTER TABLE orders
    ADD CONSTRAINT orders_company_fk FOREIGN KEY (company_id) REFERENCES companies (id);
CREATE INDEX idx_orders_company ON orders (company_id);

ALTER TABLE cash_flow ADD COLUMN company_id INTEGER;
UPDATE cash_flow cf
SET company_id = o.company_id
FROM payments p
INNER JOIN accounts_receivable ar ON ar.id = p.accounts_receivable_id
INNER JOIN orders o ON o.id = ar.order_id
WHERE cf.reference_type = 'payment' AND cf.reference_id = p.id;
UPDATE cash_flow SET company_id = 1 WHERE company_id IS NULL;
ALTER TABLE cash_flow ALTER COLUMN company_id SET NOT NULL;
ALTER TABLE cash_flow
    ADD CONSTRAINT cash_flow_company_fk FOREIGN KEY (company_id) REFERENCES companies (id);
CREATE INDEX idx_cash_flow_company ON cash_flow (company_id);

ALTER TABLE audit_logs ADD COLUMN company_id INTEGER REFERENCES companies (id) ON DELETE SET NULL;
CREATE INDEX idx_audit_logs_company ON audit_logs (company_id);
