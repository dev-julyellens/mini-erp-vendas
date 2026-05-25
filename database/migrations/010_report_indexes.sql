-- Migration: índices para consultas de relatórios
-- Execute: php database/run_migration.php

CREATE INDEX IF NOT EXISTS idx_orders_status_created_at
    ON orders (status, created_at DESC);

CREATE INDEX IF NOT EXISTS idx_order_items_product_order
    ON order_items (product_id, order_id);

CREATE INDEX IF NOT EXISTS idx_products_low_stock
    ON products (type, stock, min_stock)
    WHERE type = 'product';

CREATE INDEX IF NOT EXISTS idx_cash_flow_occurred_type
    ON cash_flow (occurred_at DESC, type);
