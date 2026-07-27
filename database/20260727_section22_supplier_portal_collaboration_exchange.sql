-- Gruber Procurement Intelligence
-- Section 22: Supplier Portal, Digital Collaboration & External Document Exchange
-- Apply after Version 3 and Sections 11 through 21 migrations.
-- Idempotent for MySQL 8.0 and MariaDB 10.11.

SET NAMES utf8mb4;
SET time_zone = '+00:00';

CREATE TABLE IF NOT EXISTS supplier_portal_accounts (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    supplier_id BIGINT UNSIGNED NOT NULL,
    supplier_contact_id BIGINT UNSIGNED NULL,
    name VARCHAR(160) NOT NULL,
    email VARCHAR(190) NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    portal_role VARCHAR(40) NOT NULL DEFAULT 'account_administrator',
    status VARCHAR(24) NOT NULL DEFAULT 'active',
    email_verified_at DATETIME NULL,
    mfa_required BOOLEAN NOT NULL DEFAULT FALSE,
    last_login_at DATETIME NULL,
    locked_until DATETIME NULL,
    created_by BIGINT UNSIGNED NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uq_supplier_portal_account_email (email),
    KEY idx_supplier_portal_account_supplier (supplier_id,status),
    KEY idx_supplier_portal_account_contact (supplier_contact_id),
    CONSTRAINT fk_supplier_portal_account_supplier FOREIGN KEY (supplier_id) REFERENCES suppliers(id) ON DELETE CASCADE,
    CONSTRAINT fk_supplier_portal_account_contact FOREIGN KEY (supplier_contact_id) REFERENCES supplier_contacts(id) ON DELETE SET NULL,
    CONSTRAINT fk_supplier_portal_account_creator FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS supplier_portal_invitations (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    supplier_id BIGINT UNSIGNED NOT NULL,
    supplier_contact_id BIGINT UNSIGNED NULL,
    invited_name VARCHAR(160) NOT NULL,
    email VARCHAR(190) NOT NULL,
    portal_role VARCHAR(40) NOT NULL,
    token_hash CHAR(64) NOT NULL,
    expires_at DATETIME NOT NULL,
    status VARCHAR(24) NOT NULL DEFAULT 'pending',
    invited_by BIGINT UNSIGNED NOT NULL,
    accepted_account_id BIGINT UNSIGNED NULL,
    accepted_at DATETIME NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uq_supplier_portal_invitation_token (token_hash),
    KEY idx_supplier_portal_invitation_email (email,status,expires_at),
    KEY idx_supplier_portal_invitation_supplier (supplier_id,status),
    CONSTRAINT fk_supplier_portal_invitation_supplier FOREIGN KEY (supplier_id) REFERENCES suppliers(id) ON DELETE CASCADE,
    CONSTRAINT fk_supplier_portal_invitation_contact FOREIGN KEY (supplier_contact_id) REFERENCES supplier_contacts(id) ON DELETE SET NULL,
    CONSTRAINT fk_supplier_portal_invitation_inviter FOREIGN KEY (invited_by) REFERENCES users(id),
    CONSTRAINT fk_supplier_portal_invitation_account FOREIGN KEY (accepted_account_id) REFERENCES supplier_portal_accounts(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS supplier_portal_access_grants (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    account_id BIGINT UNSIGNED NOT NULL,
    company_id BIGINT UNSIGNED NOT NULL,
    access_scope VARCHAR(24) NOT NULL DEFAULT 'company',
    can_acknowledge_po BOOLEAN NOT NULL DEFAULT FALSE,
    can_submit_asn BOOLEAN NOT NULL DEFAULT FALSE,
    can_submit_invoice BOOLEAN NOT NULL DEFAULT FALSE,
    can_submit_documents BOOLEAN NOT NULL DEFAULT FALSE,
    can_message BOOLEAN NOT NULL DEFAULT FALSE,
    can_quality BOOLEAN NOT NULL DEFAULT FALSE,
    can_sourcing BOOLEAN NOT NULL DEFAULT FALSE,
    starts_at DATE NOT NULL,
    expires_at DATE NULL,
    status VARCHAR(24) NOT NULL DEFAULT 'active',
    granted_by BIGINT UNSIGNED NOT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uq_supplier_portal_grant (account_id,company_id),
    KEY idx_supplier_portal_grant_company (company_id,status),
    CONSTRAINT fk_supplier_portal_grant_account FOREIGN KEY (account_id) REFERENCES supplier_portal_accounts(id) ON DELETE CASCADE,
    CONSTRAINT fk_supplier_portal_grant_company FOREIGN KEY (company_id) REFERENCES companies(id) ON DELETE CASCADE,
    CONSTRAINT fk_supplier_portal_grant_user FOREIGN KEY (granted_by) REFERENCES users(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS supplier_purchase_order_responses (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    response_number VARCHAR(50) NOT NULL UNIQUE,
    supplier_id BIGINT UNSIGNED NOT NULL,
    purchase_order_id BIGINT UNSIGNED NOT NULL,
    account_id BIGINT UNSIGNED NOT NULL,
    response_type VARCHAR(32) NOT NULL,
    proposed_delivery_date DATE NULL,
    proposed_total_amount DECIMAL(18,2) NULL,
    currency_code CHAR(3) NOT NULL DEFAULT 'USD',
    notes TEXT NOT NULL,
    evidence_reference VARCHAR(500) NOT NULL,
    status VARCHAR(32) NOT NULL DEFAULT 'submitted',
    reviewed_by BIGINT UNSIGNED NULL,
    reviewed_at DATETIME NULL,
    review_note TEXT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    KEY idx_supplier_po_response_supplier (supplier_id,status,created_at),
    KEY idx_supplier_po_response_po (purchase_order_id,status),
    KEY idx_supplier_po_response_account (account_id,created_at),
    CONSTRAINT fk_supplier_po_response_supplier FOREIGN KEY (supplier_id) REFERENCES suppliers(id),
    CONSTRAINT fk_supplier_po_response_po FOREIGN KEY (purchase_order_id) REFERENCES purchase_orders(id),
    CONSTRAINT fk_supplier_po_response_account FOREIGN KEY (account_id) REFERENCES supplier_portal_accounts(id),
    CONSTRAINT fk_supplier_po_response_reviewer FOREIGN KEY (reviewed_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS supplier_shipment_notices (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    asn_number VARCHAR(100) NOT NULL,
    supplier_id BIGINT UNSIGNED NOT NULL,
    purchase_order_id BIGINT UNSIGNED NOT NULL,
    account_id BIGINT UNSIGNED NOT NULL,
    ship_date DATE NOT NULL,
    estimated_arrival DATE NOT NULL,
    carrier VARCHAR(160) NOT NULL,
    tracking_number VARCHAR(160) NOT NULL,
    package_count INT UNSIGNED NOT NULL DEFAULT 0,
    pallet_count INT UNSIGNED NOT NULL DEFAULT 0,
    packing_slip_reference VARCHAR(160) NOT NULL,
    status VARCHAR(32) NOT NULL DEFAULT 'submitted',
    reviewed_by BIGINT UNSIGNED NULL,
    reviewed_at DATETIME NULL,
    review_note TEXT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uq_supplier_asn_number (supplier_id,asn_number),
    KEY idx_supplier_asn_po (purchase_order_id,status),
    KEY idx_supplier_asn_arrival (estimated_arrival,status),
    CONSTRAINT fk_supplier_asn_supplier FOREIGN KEY (supplier_id) REFERENCES suppliers(id),
    CONSTRAINT fk_supplier_asn_po FOREIGN KEY (purchase_order_id) REFERENCES purchase_orders(id),
    CONSTRAINT fk_supplier_asn_account FOREIGN KEY (account_id) REFERENCES supplier_portal_accounts(id),
    CONSTRAINT fk_supplier_asn_reviewer FOREIGN KEY (reviewed_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS supplier_shipment_notice_lines (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    shipment_notice_id BIGINT UNSIGNED NOT NULL,
    purchase_order_line_id BIGINT UNSIGNED NOT NULL,
    quantity_shipped DECIMAL(18,4) NOT NULL,
    lot_or_serial_reference VARCHAR(240) NULL,
    package_reference VARCHAR(240) NULL,
    notes TEXT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    KEY idx_supplier_asn_line_notice (shipment_notice_id,id),
    KEY idx_supplier_asn_line_po_line (purchase_order_line_id),
    CONSTRAINT fk_supplier_asn_line_notice FOREIGN KEY (shipment_notice_id) REFERENCES supplier_shipment_notices(id) ON DELETE CASCADE,
    CONSTRAINT fk_supplier_asn_line_po_line FOREIGN KEY (purchase_order_line_id) REFERENCES purchase_order_lines(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS supplier_invoice_submissions (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    submission_number VARCHAR(50) NOT NULL UNIQUE,
    supplier_id BIGINT UNSIGNED NOT NULL,
    purchase_order_id BIGINT UNSIGNED NOT NULL,
    account_id BIGINT UNSIGNED NOT NULL,
    invoice_number VARCHAR(100) NOT NULL,
    invoice_date DATE NOT NULL,
    due_date DATE NOT NULL,
    currency_code CHAR(3) NOT NULL DEFAULT 'USD',
    subtotal DECIMAL(18,2) NOT NULL DEFAULT 0,
    freight_amount DECIMAL(18,2) NOT NULL DEFAULT 0,
    tax_amount DECIMAL(18,2) NOT NULL DEFAULT 0,
    total_amount DECIMAL(18,2) NOT NULL DEFAULT 0,
    document_reference VARCHAR(500) NOT NULL,
    status VARCHAR(32) NOT NULL DEFAULT 'submitted',
    duplicate_flag BOOLEAN NOT NULL DEFAULT FALSE,
    reviewed_by BIGINT UNSIGNED NULL,
    reviewed_at DATETIME NULL,
    review_note TEXT NULL,
    canonical_invoice_id BIGINT UNSIGNED NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    KEY idx_supplier_invoice_submission_supplier (supplier_id,status,invoice_date),
    KEY idx_supplier_invoice_submission_po (purchase_order_id,status),
    KEY idx_supplier_invoice_submission_number (supplier_id,invoice_number),
    KEY idx_supplier_invoice_submission_canonical (canonical_invoice_id),
    CONSTRAINT fk_supplier_invoice_submission_supplier FOREIGN KEY (supplier_id) REFERENCES suppliers(id),
    CONSTRAINT fk_supplier_invoice_submission_po FOREIGN KEY (purchase_order_id) REFERENCES purchase_orders(id),
    CONSTRAINT fk_supplier_invoice_submission_account FOREIGN KEY (account_id) REFERENCES supplier_portal_accounts(id),
    CONSTRAINT fk_supplier_invoice_submission_reviewer FOREIGN KEY (reviewed_by) REFERENCES users(id) ON DELETE SET NULL,
    CONSTRAINT fk_supplier_invoice_submission_canonical FOREIGN KEY (canonical_invoice_id) REFERENCES supplier_invoices(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS supplier_invoice_submission_lines (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    invoice_submission_id BIGINT UNSIGNED NOT NULL,
    purchase_order_line_id BIGINT UNSIGNED NOT NULL,
    description VARCHAR(500) NOT NULL,
    quantity_invoiced DECIMAL(18,4) NOT NULL,
    unit_price DECIMAL(18,4) NOT NULL,
    tax_amount DECIMAL(18,2) NOT NULL DEFAULT 0,
    freight_amount DECIMAL(18,2) NOT NULL DEFAULT 0,
    line_total DECIMAL(18,2) NOT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    KEY idx_supplier_invoice_line_submission (invoice_submission_id,id),
    KEY idx_supplier_invoice_line_po_line (purchase_order_line_id),
    CONSTRAINT fk_supplier_invoice_line_submission FOREIGN KEY (invoice_submission_id) REFERENCES supplier_invoice_submissions(id) ON DELETE CASCADE,
    CONSTRAINT fk_supplier_invoice_line_po_line FOREIGN KEY (purchase_order_line_id) REFERENCES purchase_order_lines(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS supplier_document_submissions (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    supplier_id BIGINT UNSIGNED NOT NULL,
    account_id BIGINT UNSIGNED NOT NULL,
    entity_type VARCHAR(50) NOT NULL,
    entity_id BIGINT UNSIGNED NOT NULL,
    document_type VARCHAR(60) NOT NULL,
    title VARCHAR(240) NOT NULL,
    document_reference VARCHAR(500) NOT NULL,
    effective_date DATE NULL,
    expiration_date DATE NULL,
    status VARCHAR(32) NOT NULL DEFAULT 'submitted',
    reviewed_by BIGINT UNSIGNED NULL,
    reviewed_at DATETIME NULL,
    review_note TEXT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    KEY idx_supplier_document_supplier (supplier_id,status,expiration_date),
    KEY idx_supplier_document_entity (entity_type,entity_id),
    CONSTRAINT fk_supplier_document_supplier FOREIGN KEY (supplier_id) REFERENCES suppliers(id),
    CONSTRAINT fk_supplier_document_account FOREIGN KEY (account_id) REFERENCES supplier_portal_accounts(id),
    CONSTRAINT fk_supplier_document_reviewer FOREIGN KEY (reviewed_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS supplier_sourcing_submissions (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    submission_number VARCHAR(50) NOT NULL UNIQUE,
    supplier_id BIGINT UNSIGNED NOT NULL,
    account_id BIGINT UNSIGNED NOT NULL,
    sourcing_reference VARCHAR(120) NOT NULL,
    title VARCHAR(240) NOT NULL,
    bid_deadline DATETIME NOT NULL,
    currency_code CHAR(3) NOT NULL DEFAULT 'USD',
    proposal_value DECIMAL(18,2) NOT NULL DEFAULT 0,
    lead_time_days INT UNSIGNED NOT NULL DEFAULT 0,
    payment_terms VARCHAR(100) NULL,
    freight_terms VARCHAR(160) NULL,
    exceptions_and_assumptions TEXT NOT NULL,
    document_reference VARCHAR(500) NOT NULL,
    revision_number INT UNSIGNED NOT NULL DEFAULT 1,
    status VARCHAR(32) NOT NULL DEFAULT 'submitted',
    locked_at DATETIME NULL,
    reviewed_by BIGINT UNSIGNED NULL,
    reviewed_at DATETIME NULL,
    review_note TEXT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    KEY idx_supplier_sourcing_reference (sourcing_reference,supplier_id,status),
    KEY idx_supplier_sourcing_deadline (bid_deadline,status),
    CONSTRAINT fk_supplier_sourcing_supplier FOREIGN KEY (supplier_id) REFERENCES suppliers(id),
    CONSTRAINT fk_supplier_sourcing_account FOREIGN KEY (account_id) REFERENCES supplier_portal_accounts(id),
    CONSTRAINT fk_supplier_sourcing_reviewer FOREIGN KEY (reviewed_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS supplier_quality_responses (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    response_number VARCHAR(50) NOT NULL UNIQUE,
    supplier_id BIGINT UNSIGNED NOT NULL,
    account_id BIGINT UNSIGNED NOT NULL,
    quality_reference VARCHAR(120) NOT NULL,
    response_type VARCHAR(40) NOT NULL,
    containment_response TEXT NOT NULL,
    root_cause TEXT NOT NULL,
    corrective_action TEXT NOT NULL,
    preventive_action TEXT NOT NULL,
    target_date DATE NOT NULL,
    evidence_reference VARCHAR(500) NOT NULL,
    status VARCHAR(32) NOT NULL DEFAULT 'submitted',
    reviewed_by BIGINT UNSIGNED NULL,
    reviewed_at DATETIME NULL,
    review_note TEXT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    KEY idx_supplier_quality_supplier (supplier_id,status,target_date),
    KEY idx_supplier_quality_reference (quality_reference,status),
    CONSTRAINT fk_supplier_quality_supplier FOREIGN KEY (supplier_id) REFERENCES suppliers(id),
    CONSTRAINT fk_supplier_quality_account FOREIGN KEY (account_id) REFERENCES supplier_portal_accounts(id),
    CONSTRAINT fk_supplier_quality_reviewer FOREIGN KEY (reviewed_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS supplier_collaboration_messages (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    supplier_id BIGINT UNSIGNED NOT NULL,
    account_id BIGINT UNSIGNED NULL,
    company_id BIGINT UNSIGNED NULL,
    entity_type VARCHAR(50) NOT NULL,
    entity_id BIGINT UNSIGNED NOT NULL,
    direction VARCHAR(32) NOT NULL,
    subject VARCHAR(240) NOT NULL,
    message_body TEXT NOT NULL,
    supplier_visible BOOLEAN NOT NULL DEFAULT TRUE,
    required_response_date DATE NULL,
    status VARCHAR(32) NOT NULL DEFAULT 'open',
    read_at DATETIME NULL,
    acknowledged_at DATETIME NULL,
    created_by_internal BIGINT UNSIGNED NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    KEY idx_supplier_message_supplier (supplier_id,status,created_at),
    KEY idx_supplier_message_entity (entity_type,entity_id,created_at),
    KEY idx_supplier_message_due (required_response_date,status),
    CONSTRAINT fk_supplier_message_supplier FOREIGN KEY (supplier_id) REFERENCES suppliers(id),
    CONSTRAINT fk_supplier_message_account FOREIGN KEY (account_id) REFERENCES supplier_portal_accounts(id) ON DELETE SET NULL,
    CONSTRAINT fk_supplier_message_company FOREIGN KEY (company_id) REFERENCES companies(id) ON DELETE SET NULL,
    CONSTRAINT fk_supplier_message_internal FOREIGN KEY (created_by_internal) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS supplier_portal_events (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    supplier_id BIGINT UNSIGNED NOT NULL,
    account_id BIGINT UNSIGNED NULL,
    company_id BIGINT UNSIGNED NULL,
    entity_type VARCHAR(50) NOT NULL,
    entity_id BIGINT UNSIGNED NOT NULL,
    event_type VARCHAR(80) NOT NULL,
    from_status VARCHAR(32) NULL,
    to_status VARCHAR(32) NULL,
    severity VARCHAR(20) NOT NULL DEFAULT 'medium',
    evidence_note TEXT NOT NULL,
    actor_type VARCHAR(20) NOT NULL,
    actor_internal_user_id BIGINT UNSIGNED NULL,
    actor_portal_account_id BIGINT UNSIGNED NULL,
    ip_address VARCHAR(64) NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    KEY idx_supplier_portal_event_supplier (supplier_id,created_at),
    KEY idx_supplier_portal_event_entity (entity_type,entity_id,created_at),
    KEY idx_supplier_portal_event_company (company_id,created_at),
    CONSTRAINT fk_supplier_portal_event_supplier FOREIGN KEY (supplier_id) REFERENCES suppliers(id),
    CONSTRAINT fk_supplier_portal_event_account FOREIGN KEY (account_id) REFERENCES supplier_portal_accounts(id) ON DELETE SET NULL,
    CONSTRAINT fk_supplier_portal_event_company FOREIGN KEY (company_id) REFERENCES companies(id) ON DELETE SET NULL,
    CONSTRAINT fk_supplier_portal_event_internal FOREIGN KEY (actor_internal_user_id) REFERENCES users(id) ON DELETE SET NULL,
    CONSTRAINT fk_supplier_portal_event_portal FOREIGN KEY (actor_portal_account_id) REFERENCES supplier_portal_accounts(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT IGNORE INTO permissions(permission_key,area,action,description) VALUES
('supplier_portal.view','supplier_portal','view','View supplier portal accounts, staged collaboration records, and supplier activity'),
('supplier_portal.invite','supplier_portal','invite','Create controlled supplier portal invitations'),
('supplier_portal.review','supplier_portal','review','Review supplier submissions and convert approved records into canonical workflows'),
('supplier_portal.export','supplier_portal','export','Export authorized supplier collaboration records'),
('supplier_portal.administer','supplier_portal','administer','Administer supplier portal identities, grants, and account status');

INSERT IGNORE INTO role_permissions(role_id,permission_id)
SELECT r.id,p.id
FROM roles r
JOIN permissions p ON p.area='supplier_portal'
WHERE r.code='system_administrator'
   OR (r.code='company_administrator')
   OR (r.code='procurement_manager')
   OR (r.code='reviewer' AND p.action IN ('view','review','export'))
   OR (r.code='executive' AND p.action IN ('view','export'))
   OR (r.code='data_contributor' AND p.action IN ('view'))
   OR (r.code='read_only' AND p.action IN ('view'));

INSERT INTO schema_migrations(version,description,checksum_sha256)
VALUES('5.1-section22','Supplier portal identities, tenant grants, PO responses, ASNs, staged invoices, document exchange, sourcing, quality collaboration, messages, and immutable events',NULL)
ON DUPLICATE KEY UPDATE description=VALUES(description);
