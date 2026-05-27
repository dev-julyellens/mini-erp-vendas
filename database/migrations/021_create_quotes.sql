-- Orçamentos (propostas comerciais sem baixa de estoque até conversão em venda)

CREATE TABLE quotes (
    id SERIAL PRIMARY KEY,
    company_id INTEGER NOT NULL REFERENCES companies (id),
    customer_id INTEGER NOT NULL REFERENCES customers (id) ON DELETE RESTRICT,
    total_amount NUMERIC(14, 2) NOT NULL DEFAULT 0,
    status VARCHAR(20) NOT NULL DEFAULT 'draft',
    valid_until DATE,
    notes TEXT,
    converted_order_id INTEGER REFERENCES orders (id) ON DELETE SET NULL,
    created_by INTEGER REFERENCES users (id) ON DELETE SET NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT quotes_total_non_negative CHECK (total_amount >= 0),
    CONSTRAINT quotes_status_check CHECK (
        status IN ('draft', 'sent', 'approved', 'canceled', 'converted')
    )
);

CREATE INDEX idx_quotes_company ON quotes (company_id);
CREATE INDEX idx_quotes_customer ON quotes (customer_id);
CREATE INDEX idx_quotes_status ON quotes (status);
CREATE INDEX idx_quotes_valid_until ON quotes (valid_until);
CREATE INDEX idx_quotes_created_at ON quotes (created_at DESC);

CREATE TABLE quote_items (
    id SERIAL PRIMARY KEY,
    quote_id INTEGER NOT NULL REFERENCES quotes (id) ON DELETE CASCADE,
    product_id INTEGER NOT NULL REFERENCES products (id) ON DELETE RESTRICT,
    quantity INTEGER NOT NULL,
    unit_price NUMERIC(12, 2) NOT NULL,
    subtotal NUMERIC(14, 2) NOT NULL,
    CONSTRAINT quote_items_quantity_positive CHECK (quantity > 0),
    CONSTRAINT quote_items_prices_positive CHECK (unit_price > 0 AND subtotal > 0)
);

CREATE INDEX idx_quote_items_quote ON quote_items (quote_id);
CREATE INDEX idx_quote_items_product ON quote_items (product_id);
