-- Gruber Procurement Intelligence
-- Section 18: PO Fulfillment, Receiving, Invoice Match & Exception Governance
-- Apply after Version 3 and Sections 11 through 17 migrations.
-- Idempotent for MySQL 8.0 and MariaDB 10.11.

SET NAMES utf8mb4;
SET time_zone = '+00:00';

CREATE TABLE IF NOT EXISTS purchase_order_fulfillment_profiles (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    purchase_order_id BIGINT UNSIGNED NOT NULL,
    owner_id BIGINT UNSIGNED NOT NULL,
    reviewer_id BIGINT UNSIGNED NOT NULL,
    shipment_status VARCHAR(32) NOT NULL DEFAULT 'not_acknowledged',
    asn_number VARCHAR(100) NULL,
    carrier VARCHAR(160) NULL,
    tracking_number VARCHAR(160) NULL,
    shipment_reference TEXT NOT NULL,
    fulfillment_evidence TEXT NOT NULL,
    last_status_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uq_po_fulfillment_profile (purchase_order_id),
    KEY idx_po_fulfillment_owner (owner_id),
    KEY idx_po_fulfillment_reviewer (reviewer_id),
    KEY idx_po_fulfillment_status (shipment_status,last_status_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS supplier_invoices (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    invoice_number VARCHAR(100) NOT NULL,
    company_id BIGINT UNSIGNED NOT NULL,
    supplier_id BIGINT UNSIGNED NOT NULL,
    purchase_order_id BIGINT UNSIGNED NOT NULL,
    contract_id BIGINT UNSIGNED NULL,
    invoice_date DATE NOT NULL,
    due_date DATE NOT NULL,
    payment_terms VARCHAR(100) NULL,
    currency_code CHAR(3) NOT NULL DEFAULT 'USD',
    subtotal DECIMAL(18,2) NOT NULL DEFAULT 0,
    freight_amount DECIMAL(18,2) NOT NULL DEFAULT 0,
    tax_amount DECIMAL(18,2) NOT NULL DEFAULT 0,
    total_amount DECIMAL(18,2) NOT NULL DEFAULT 0,
    document_path VARCHAR(500) NULL,
    status VARCHAR(32) NOT NULL DEFAULT 'received',
    owner_id BIGINT UNSIGNED NOT NULL,
    reviewer_id BIGINT UNSIGNED NOT NULL,
    approval_id BIGINT UNSIGNED NULL,
    hold_reason TEXT NOT NULL,
    duplicate_fingerprint CHAR(64) NOT NULL,
    released_at DATETIME NULL,
    paid_at DATETIME NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uq_supplier_invoice_number (supplier_id,invoice_number),
    KEY idx_supplier_invoice_company_status (company_id,status,due_date),
    KEY idx_supplier_invoice_po (purchase_order_id,invoice_date),
    KEY idx_supplier_invoice_contract (contract_id),
    KEY idx_supplier_invoice_approval (approval_id),
    KEY idx_supplier_invoice_fingerprint (duplicate_fingerprint)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS supplier_invoice_lines (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    invoice_id BIGINT UNSIGNED NOT NULL,
    purchase_order_line_id BIGINT UNSIGNED NOT NULL,
    item_id BIGINT UNSIGNED NULL,
    description VARCHAR(500) NOT NULL,
    quantity_invoiced DECIMAL(18,4) NOT NULL,
    unit_price DECIMAL(18,4) NOT NULL,
    line_total DECIMAL(18,2) NOT NULL,
    tax_amount DECIMAL(18,2) NOT NULL DEFAULT 0,
    freight_amount DECIMAL(18,2) NOT NULL DEFAULT 0,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    KEY idx_supplier_invoice_line_invoice (invoice_id,id),
    KEY idx_supplier_invoice_line_po_line (purchase_order_line_id),
    KEY idx_supplier_invoice_line_item (item_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS procurement_match_results (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    match_number VARCHAR(40) NOT NULL UNIQUE,
    invoice_id BIGINT UNSIGNED NOT NULL,
    purchase_order_id BIGINT UNSIGNED NOT NULL,
    po_value DECIMAL(18,2) NOT NULL DEFAULT 0,
    accepted_receipt_value DECIMAL(18,2) NOT NULL DEFAULT 0,
    invoice_value DECIMAL(18,2) NOT NULL DEFAULT 0,
    quantity_variance DECIMAL(18,2) NOT NULL DEFAULT 0,
    price_variance DECIMAL(18,2) NOT NULL DEFAULT 0,
    freight_variance DECIMAL(18,2) NOT NULL DEFAULT 0,
    tax_variance DECIMAL(18,2) NOT NULL DEFAULT 0,
    contract_variance DECIMAL(18,2) NOT NULL DEFAULT 0,
    unreceived_value DECIMAL(18,2) NOT NULL DEFAULT 0,
    held_rejected_value DECIMAL(18,2) NOT NULL DEFAULT 0,
    remaining_po_balance DECIMAL(18,2) NOT NULL DEFAULT 0,
    duplicate_flag BOOLEAN NOT NULL DEFAULT FALSE,
    tolerance_amount DECIMAL(18,2) NOT NULL DEFAULT 100,
    tolerance_pct DECIMAL(8,4) NOT NULL DEFAULT 2,
    match_status VARCHAR(32) NOT NULL,
    match_score DECIMAL(8,3) NOT NULL DEFAULT 0,
    evidence_note TEXT NOT NULL,
    created_by BIGINT UNSIGNED NOT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    KEY idx_match_invoice (invoice_id,created_at),
    KEY idx_match_po (purchase_order_id,created_at),
    KEY idx_match_status (match_status,created_at),
    KEY idx_match_creator (created_by)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS procurement_invoice_exceptions (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    exception_number VARCHAR(40) NOT NULL UNIQUE,
    invoice_id BIGINT UNSIGNED NOT NULL,
    match_result_id BIGINT UNSIGNED NOT NULL,
    purchase_order_id BIGINT UNSIGNED NOT NULL,
    exception_type VARCHAR(50) NOT NULL,
    severity VARCHAR(20) NOT NULL DEFAULT 'medium',
    status VARCHAR(32) NOT NULL DEFAULT 'open',
    owner_id BIGINT UNSIGNED NOT NULL,
    reviewer_id BIGINT UNSIGNED NOT NULL,
    due_date DATE NOT NULL,
    disputed_amount DECIMAL(18,2) NOT NULL DEFAULT 0,
    waiver_reason TEXT NOT NULL,
    resolution_note TEXT NOT NULL,
    supplier_credit_amount DECIMAL(18,2) NOT NULL DEFAULT 0,
    approval_id BIGINT UNSIGNED NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    KEY idx_invoice_exception_invoice (invoice_id,status),
    KEY idx_invoice_exception_match (match_result_id),
    KEY idx_invoice_exception_po (purchase_order_id,status),
    KEY idx_invoice_exception_owner (owner_id,due_date),
    KEY idx_invoice_exception_reviewer (reviewer_id,status),
    KEY idx_invoice_exception_approval (approval_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS procurement_fulfillment_events (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    purchase_order_id BIGINT UNSIGNED NOT NULL,
    invoice_id BIGINT UNSIGNED NULL,
    event_type VARCHAR(50) NOT NULL,
    entity_type VARCHAR(50) NOT NULL,
    entity_id BIGINT UNSIGNED NOT NULL,
    from_status VARCHAR(32) NULL,
    to_status VARCHAR(32) NULL,
    severity VARCHAR(20) NOT NULL DEFAULT 'medium',
    evidence_note TEXT NOT NULL,
    created_by BIGINT UNSIGNED NOT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    KEY idx_fulfillment_event_po (purchase_order_id,created_at),
    KEY idx_fulfillment_event_invoice (invoice_id,created_at),
    KEY idx_fulfillment_event_entity (entity_type,entity_id),
    KEY idx_fulfillment_event_type (event_type,created_at),
    KEY idx_fulfillment_event_severity (severity)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO schema_migrations (version, description, checksum_sha256)
VALUES ('4.7-section18', 'PO fulfillment, receiving, supplier invoices, three-way matching, exception governance, and payment release', NULL)
ON DUPLICATE KEY UPDATE description = VALUES(description);
