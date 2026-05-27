-- Migration: company_id em stock_movements (isolamento multi-tenant)
-- Execute: php database/run_migration.php

ALTER TABLE stock_movements
    ADD COLUMN IF NOT EXISTS company_id INTEGER;

UPDATE stock_movements m
SET company_id = p.company_id
FROM products p
WHERE m.product_id = p.id
  AND m.company_id IS NULL;

ALTER TABLE stock_movements
    ALTER COLUMN company_id SET NOT NULL;

DO $$
BEGIN
    IF NOT EXISTS (
        SELECT 1 FROM pg_constraint WHERE conname = 'stock_movements_company_id_fkey'
    ) THEN
        ALTER TABLE stock_movements
            ADD CONSTRAINT stock_movements_company_id_fkey
            FOREIGN KEY (company_id) REFERENCES companies (id);
    END IF;
END $$;

CREATE INDEX IF NOT EXISTS idx_stock_movements_company_created
    ON stock_movements (company_id, created_at DESC);
