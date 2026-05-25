-- Migration: stock_movements (rastreabilidade de estoque)
-- Execute: php database/run_migration.php

CREATE TABLE IF NOT EXISTS stock_movements (
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

CREATE INDEX IF NOT EXISTS idx_stock_movements_product_id ON stock_movements (product_id);
CREATE INDEX IF NOT EXISTS idx_stock_movements_type ON stock_movements (type);
CREATE INDEX IF NOT EXISTS idx_stock_movements_created_at ON stock_movements (created_at DESC);
CREATE INDEX IF NOT EXISTS idx_stock_movements_product_created ON stock_movements (product_id, created_at DESC);
CREATE INDEX IF NOT EXISTS idx_stock_movements_reference ON stock_movements (reference_type, reference_id);
