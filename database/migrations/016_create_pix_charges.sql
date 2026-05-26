-- Migration: cobranças PIX (gateway desacoplado, conciliação com payments)

CREATE TABLE pix_charges (
    id SERIAL PRIMARY KEY,
    company_id INTEGER NOT NULL REFERENCES companies(id) ON DELETE RESTRICT,
    accounts_receivable_id INTEGER NOT NULL REFERENCES accounts_receivable(id) ON DELETE RESTRICT,
    installment_id INTEGER REFERENCES installments(id) ON DELETE RESTRICT,
    gateway VARCHAR(50) NOT NULL,
    external_id VARCHAR(100) NOT NULL,
    amount NUMERIC(14, 2) NOT NULL,
    status VARCHAR(20) NOT NULL DEFAULT 'pending',
    qr_payload TEXT,
    qr_image_url TEXT,
    receipt_reference VARCHAR(255),
    expires_at TIMESTAMP NOT NULL,
    paid_at TIMESTAMP,
    payment_id INTEGER REFERENCES payments(id) ON DELETE SET NULL,
    created_by INTEGER REFERENCES users(id) ON DELETE SET NULL,
    raw_webhook JSONB,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT pix_charges_amount_check CHECK (amount > 0),
    CONSTRAINT pix_charges_status_check CHECK (
        status IN ('pending', 'paid', 'expired', 'canceled')
    ),
    CONSTRAINT pix_charges_gateway_external_unique UNIQUE (gateway, external_id)
);

CREATE INDEX idx_pix_charges_company_id ON pix_charges (company_id);
CREATE INDEX idx_pix_charges_accounts_receivable_id ON pix_charges (accounts_receivable_id);
CREATE INDEX idx_pix_charges_installment_id ON pix_charges (installment_id)
    WHERE installment_id IS NOT NULL;
CREATE INDEX idx_pix_charges_status ON pix_charges (status);
CREATE INDEX idx_pix_charges_pending_company ON pix_charges (company_id, status)
    WHERE status = 'pending';

COMMENT ON TABLE pix_charges IS 'Cobranças PIX geradas via gateway; conciliadas em payments ao confirmar pagamento';
