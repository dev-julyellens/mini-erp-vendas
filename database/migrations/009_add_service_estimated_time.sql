-- Migration: tempo estimado para serviços
-- Execute: php database/run_migration.php

ALTER TABLE products
    ADD COLUMN IF NOT EXISTS estimated_time_minutes INTEGER;

DO $$
BEGIN
    IF NOT EXISTS (
        SELECT 1 FROM pg_constraint WHERE conname = 'products_estimated_time_non_negative'
    ) THEN
        ALTER TABLE products ADD CONSTRAINT products_estimated_time_non_negative CHECK (
            estimated_time_minutes IS NULL OR estimated_time_minutes >= 0
        );
    END IF;
END $$;

UPDATE products
SET estimated_time_minutes = NULL
WHERE type = 'product' AND estimated_time_minutes IS NOT NULL;

INSERT INTO products (
    name, description, sku, category_id, unit_of_measure,
    cost_price, margin_percent, markup_percent, price,
    stock, min_stock, type, estimated_time_minutes
)
SELECT
    'Manutenção preventiva',
    'Revisão periódica de equipamentos',
    'SRV-000002',
    c.id,
    'HR',
    0.00,
    100.00,
    NULL,
    89.90,
    0,
    0,
    'service',
    60
FROM categories c
WHERE c.name = 'Serviços'
  AND NOT EXISTS (SELECT 1 FROM products WHERE sku = 'SRV-000002')
LIMIT 1;

INSERT INTO products (
    name, description, sku, category_id, unit_of_measure,
    cost_price, margin_percent, markup_percent, price,
    stock, min_stock, type, estimated_time_minutes
)
SELECT
    'Consultoria técnica',
    'Atendimento por hora',
    'SRV-000003',
    c.id,
    'HR',
    0.00,
    100.00,
    NULL,
    120.00,
    0,
    0,
    'service',
    60
FROM categories c
WHERE c.name = 'Serviços'
  AND NOT EXISTS (SELECT 1 FROM products WHERE sku = 'SRV-000003')
LIMIT 1;

UPDATE products
SET estimated_time_minutes = 90
WHERE sku = 'SRV-000001' AND type = 'service' AND estimated_time_minutes IS NULL;
