-- Inventário físico (contagem e ajuste de estoque)

CREATE TABLE inventory_counts (
    id SERIAL PRIMARY KEY,
    company_id INTEGER NOT NULL REFERENCES companies (id),
    status VARCHAR(20) NOT NULL DEFAULT 'open',
    notes TEXT,
    created_by INTEGER REFERENCES users (id) ON DELETE SET NULL,
    finalized_by INTEGER REFERENCES users (id) ON DELETE SET NULL,
    finalized_at TIMESTAMP,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT inventory_counts_status_check CHECK (
        status IN ('open', 'finalized', 'canceled')
    )
);

CREATE INDEX idx_inventory_counts_company ON inventory_counts (company_id);
CREATE INDEX idx_inventory_counts_status ON inventory_counts (status);
CREATE INDEX idx_inventory_counts_created_at ON inventory_counts (created_at DESC);

CREATE TABLE inventory_count_lines (
    id SERIAL PRIMARY KEY,
    inventory_count_id INTEGER NOT NULL REFERENCES inventory_counts (id) ON DELETE CASCADE,
    product_id INTEGER NOT NULL REFERENCES products (id) ON DELETE RESTRICT,
    system_qty INTEGER NOT NULL DEFAULT 0,
    counted_qty INTEGER,
    CONSTRAINT inventory_count_lines_unique_product UNIQUE (inventory_count_id, product_id),
    CONSTRAINT inventory_count_lines_system_non_negative CHECK (system_qty >= 0),
    CONSTRAINT inventory_count_lines_counted_non_negative CHECK (counted_qty IS NULL OR counted_qty >= 0)
);

CREATE INDEX idx_inventory_count_lines_count ON inventory_count_lines (inventory_count_id);
CREATE INDEX idx_inventory_count_lines_product ON inventory_count_lines (product_id);
