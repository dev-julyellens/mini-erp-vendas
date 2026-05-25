-- Migration: categorias e campos avançados em produtos
-- Execute: php database/run_migration.php

CREATE TABLE IF NOT EXISTS categories (
    id SERIAL PRIMARY KEY,
    name VARCHAR(120) NOT NULL,
    description TEXT,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT categories_name_unique UNIQUE (name)
);

CREATE INDEX IF NOT EXISTS idx_categories_name ON categories (name);

ALTER TABLE products
    ADD COLUMN IF NOT EXISTS sku VARCHAR(50),
    ADD COLUMN IF NOT EXISTS barcode VARCHAR(50),
    ADD COLUMN IF NOT EXISTS category_id INTEGER REFERENCES categories (id) ON DELETE SET NULL,
    ADD COLUMN IF NOT EXISTS unit_of_measure VARCHAR(10) NOT NULL DEFAULT 'UN',
    ADD COLUMN IF NOT EXISTS cost_price NUMERIC(12, 2) NOT NULL DEFAULT 0,
    ADD COLUMN IF NOT EXISTS margin_percent NUMERIC(8, 2),
    ADD COLUMN IF NOT EXISTS markup_percent NUMERIC(8, 2),
    ADD COLUMN IF NOT EXISTS min_stock INTEGER NOT NULL DEFAULT 5,
    ADD COLUMN IF NOT EXISTS type VARCHAR(20) NOT NULL DEFAULT 'product';

UPDATE products
SET sku = 'SKU-' || LPAD(id::text, 6, '0')
WHERE sku IS NULL OR TRIM(sku) = '';

ALTER TABLE products
    ALTER COLUMN sku SET NOT NULL;

DO $$
BEGIN
    IF NOT EXISTS (
        SELECT 1 FROM pg_constraint WHERE conname = 'products_sku_unique'
    ) THEN
        ALTER TABLE products ADD CONSTRAINT products_sku_unique UNIQUE (sku);
    END IF;
END $$;

DO $$
BEGIN
    IF NOT EXISTS (
        SELECT 1 FROM pg_constraint WHERE conname = 'products_barcode_unique'
    ) THEN
        ALTER TABLE products ADD CONSTRAINT products_barcode_unique UNIQUE (barcode);
    END IF;
END $$;

DO $$
BEGIN
    IF NOT EXISTS (
        SELECT 1 FROM pg_constraint WHERE conname = 'products_type_check'
    ) THEN
        ALTER TABLE products ADD CONSTRAINT products_type_check CHECK (
            type IN ('product', 'service')
        );
    END IF;
END $$;

DO $$
BEGIN
    IF NOT EXISTS (
        SELECT 1 FROM pg_constraint WHERE conname = 'products_min_stock_non_negative'
    ) THEN
        ALTER TABLE products ADD CONSTRAINT products_min_stock_non_negative CHECK (min_stock >= 0);
    END IF;
END $$;

DO $$
BEGIN
    IF NOT EXISTS (
        SELECT 1 FROM pg_constraint WHERE conname = 'products_cost_non_negative'
    ) THEN
        ALTER TABLE products ADD CONSTRAINT products_cost_non_negative CHECK (cost_price >= 0);
    END IF;
END $$;

CREATE INDEX IF NOT EXISTS idx_products_sku ON products (sku);
CREATE INDEX IF NOT EXISTS idx_products_category ON products (category_id);
CREATE INDEX IF NOT EXISTS idx_products_type ON products (type);
CREATE INDEX IF NOT EXISTS idx_products_barcode ON products (barcode) WHERE barcode IS NOT NULL;

INSERT INTO categories (name, description)
VALUES
    ('Informática', 'Hardware e periféricos'),
    ('Serviços', 'Prestação de serviços')
ON CONFLICT (name) DO NOTHING;

UPDATE products p
SET category_id = c.id
FROM categories c
WHERE c.name = 'Informática'
  AND p.category_id IS NULL;
