-- Gruber AI Procurement Intelligence System
-- MySQL 8.0+ schema
-- Version: 2.0 (Demo Mode + Admin Console foundation)
-- Purpose: centralized procurement, inventory, supplier, project/work-order,
-- savings, reporting, security administration, and AI briefing data for six Gruber businesses.

SET NAMES utf8mb4;
SET time_zone = '+00:00';
SET FOREIGN_KEY_CHECKS = 0;

CREATE DATABASE IF NOT EXISTS gruber_ai_procurement
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;
USE gruber_ai_procurement;

CREATE TABLE IF NOT EXISTS companies (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    code VARCHAR(16) NOT NULL UNIQUE,
    name VARCHAR(160) NOT NULL,
    legal_name VARCHAR(200) NULL,
    description TEXT NULL,
    is_active BOOLEAN NOT NULL DEFAULT TRUE,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS locations (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    company_id BIGINT UNSIGNED NOT NULL,
    code VARCHAR(32) NOT NULL,
    name VARCHAR(160) NOT NULL,
    location_type ENUM('warehouse','office','service_center','project_site','vehicle','storage_facility','other') NOT NULL DEFAULT 'other',
    address_line1 VARCHAR(200) NULL,
    address_line2 VARCHAR(200) NULL,
    city VARCHAR(100) NULL,
    state_region VARCHAR(100) NULL,
    postal_code VARCHAR(32) NULL,
    country_code CHAR(2) NOT NULL DEFAULT 'US',
    is_active BOOLEAN NOT NULL DEFAULT TRUE,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uq_locations_company_code (company_id, code),
    CONSTRAINT fk_locations_company FOREIGN KEY (company_id) REFERENCES companies(id)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS departments (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    company_id BIGINT UNSIGNED NOT NULL,
    code VARCHAR(32) NOT NULL,
    name VARCHAR(160) NOT NULL,
    is_active BOOLEAN NOT NULL DEFAULT TRUE,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uq_departments_company_code (company_id, code),
    CONSTRAINT fk_departments_company FOREIGN KEY (company_id) REFERENCES companies(id)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS roles (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    code VARCHAR(64) NOT NULL UNIQUE,
    name VARCHAR(120) NOT NULL,
    description VARCHAR(500) NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS users (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    primary_company_id BIGINT UNSIGNED NULL,
    primary_location_id BIGINT UNSIGNED NULL,
    name VARCHAR(160) NOT NULL,
    email VARCHAR(190) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    job_title VARCHAR(160) NULL,
    status ENUM('active','inactive','locked','pending') NOT NULL DEFAULT 'active',
    last_login_at TIMESTAMP NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_users_company FOREIGN KEY (primary_company_id) REFERENCES companies(id),
    CONSTRAINT fk_users_location FOREIGN KEY (primary_location_id) REFERENCES locations(id)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS user_roles (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id BIGINT UNSIGNED NOT NULL,
    role_id BIGINT UNSIGNED NOT NULL,
    company_id BIGINT UNSIGNED NULL COMMENT 'NULL means enterprise-wide role',
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uq_user_role_scope (user_id, role_id, company_id),
    CONSTRAINT fk_user_roles_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    CONSTRAINT fk_user_roles_role FOREIGN KEY (role_id) REFERENCES roles(id) ON DELETE CASCADE,
    CONSTRAINT fk_user_roles_company FOREIGN KEY (company_id) REFERENCES companies(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS purchasing_categories (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    parent_id BIGINT UNSIGNED NULL,
    code VARCHAR(64) NOT NULL UNIQUE,
    name VARCHAR(160) NOT NULL,
    description TEXT NULL,
    is_active BOOLEAN NOT NULL DEFAULT TRUE,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_categories_parent FOREIGN KEY (parent_id) REFERENCES purchasing_categories(id)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS suppliers (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    supplier_number VARCHAR(64) NOT NULL UNIQUE,
    normalized_name VARCHAR(200) NOT NULL,
    legal_name VARCHAR(240) NULL,
    tax_id_last4 CHAR(4) NULL,
    website VARCHAR(255) NULL,
    primary_category_id BIGINT UNSIGNED NULL,
    payment_terms VARCHAR(100) NULL,
    preferred_status ENUM('preferred','approved','candidate','conditional','blocked') NOT NULL DEFAULT 'approved',
    risk_rating ENUM('low','medium','high','critical') NOT NULL DEFAULT 'medium',
    active BOOLEAN NOT NULL DEFAULT TRUE,
    notes TEXT NULL,
    created_by BIGINT UNSIGNED NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_suppliers_name (normalized_name),
    INDEX idx_suppliers_status (preferred_status, active),
    CONSTRAINT fk_suppliers_category FOREIGN KEY (primary_category_id) REFERENCES purchasing_categories(id),
    CONSTRAINT fk_suppliers_created_by FOREIGN KEY (created_by) REFERENCES users(id)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS supplier_companies (
    supplier_id BIGINT UNSIGNED NOT NULL,
    company_id BIGINT UNSIGNED NOT NULL,
    account_number VARCHAR(100) NULL,
    account_status ENUM('active','inactive','on_hold') NOT NULL DEFAULT 'active',
    company_specific_terms VARCHAR(100) NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (supplier_id, company_id),
    CONSTRAINT fk_supplier_companies_supplier FOREIGN KEY (supplier_id) REFERENCES suppliers(id) ON DELETE CASCADE,
    CONSTRAINT fk_supplier_companies_company FOREIGN KEY (company_id) REFERENCES companies(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS supplier_contacts (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    supplier_id BIGINT UNSIGNED NOT NULL,
    contact_type ENUM('sales','service','billing','quality','executive','other') NOT NULL DEFAULT 'sales',
    name VARCHAR(160) NOT NULL,
    email VARCHAR(190) NULL,
    phone VARCHAR(64) NULL,
    is_primary BOOLEAN NOT NULL DEFAULT FALSE,
    notes TEXT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_supplier_contacts_supplier FOREIGN KEY (supplier_id) REFERENCES suppliers(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS supplier_contracts (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    supplier_id BIGINT UNSIGNED NOT NULL,
    company_id BIGINT UNSIGNED NULL COMMENT 'NULL means enterprise agreement',
    contract_number VARCHAR(100) NULL,
    title VARCHAR(240) NOT NULL,
    start_date DATE NULL,
    end_date DATE NULL,
    auto_renew BOOLEAN NOT NULL DEFAULT FALSE,
    payment_terms VARCHAR(100) NULL,
    freight_terms VARCHAR(160) NULL,
    warranty_terms TEXT NULL,
    service_level_terms TEXT NULL,
    estimated_annual_value DECIMAL(15,2) NULL,
    status ENUM('draft','active','expiring','expired','terminated') NOT NULL DEFAULT 'draft',
    document_path VARCHAR(500) NULL,
    created_by BIGINT UNSIGNED NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_contracts_expiry (end_date, status),
    CONSTRAINT fk_contracts_supplier FOREIGN KEY (supplier_id) REFERENCES suppliers(id),
    CONSTRAINT fk_contracts_company FOREIGN KEY (company_id) REFERENCES companies(id),
    CONSTRAINT fk_contracts_created_by FOREIGN KEY (created_by) REFERENCES users(id)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS items (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    item_number VARCHAR(80) NOT NULL UNIQUE,
    company_id BIGINT UNSIGNED NULL COMMENT 'NULL means shared enterprise item',
    sku VARCHAR(120) NULL,
    normalized_description VARCHAR(500) NOT NULL,
    category_id BIGINT UNSIGNED NOT NULL,
    subcategory VARCHAR(160) NULL,
    manufacturer VARCHAR(180) NULL,
    manufacturer_part_number VARCHAR(160) NULL,
    unit_of_measure VARCHAR(32) NOT NULL,
    standard_cost DECIMAL(15,4) NULL,
    last_cost DECIMAL(15,4) NULL,
    lead_time_days INT UNSIGNED NULL,
    minimum_quantity DECIMAL(15,4) NULL,
    maximum_quantity DECIMAL(15,4) NULL,
    reorder_point DECIMAL(15,4) NULL,
    strategic_legacy BOOLEAN NOT NULL DEFAULT FALSE,
    serialized BOOLEAN NOT NULL DEFAULT FALSE,
    lot_tracked BOOLEAN NOT NULL DEFAULT FALSE,
    active BOOLEAN NOT NULL DEFAULT TRUE,
    created_by BIGINT UNSIGNED NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_items_company_category (company_id, category_id),
    INDEX idx_items_mfr_part (manufacturer_part_number),
    CONSTRAINT fk_items_company FOREIGN KEY (company_id) REFERENCES companies(id),
    CONSTRAINT fk_items_category FOREIGN KEY (category_id) REFERENCES purchasing_categories(id),
    CONSTRAINT fk_items_created_by FOREIGN KEY (created_by) REFERENCES users(id)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS item_suppliers (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    item_id BIGINT UNSIGNED NOT NULL,
    supplier_id BIGINT UNSIGNED NOT NULL,
    supplier_part_number VARCHAR(160) NULL,
    is_primary BOOLEAN NOT NULL DEFAULT FALSE,
    contract_unit_cost DECIMAL(15,4) NULL,
    minimum_order_quantity DECIMAL(15,4) NULL,
    order_multiple DECIMAL(15,4) NULL,
    quoted_lead_time_days INT UNSIGNED NULL,
    valid_from DATE NULL,
    valid_to DATE NULL,
    active BOOLEAN NOT NULL DEFAULT TRUE,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uq_item_supplier (item_id, supplier_id),
    CONSTRAINT fk_item_suppliers_item FOREIGN KEY (item_id) REFERENCES items(id) ON DELETE CASCADE,
    CONSTRAINT fk_item_suppliers_supplier FOREIGN KEY (supplier_id) REFERENCES suppliers(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS projects_workorders (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    company_id BIGINT UNSIGNED NOT NULL,
    number VARCHAR(100) NOT NULL,
    type ENUM('customer_order','manufacturing_order','service_work_order','construction_project','vehicle_repair','restoration','facility','capital_project') NOT NULL,
    customer_name VARCHAR(240) NULL,
    title VARCHAR(240) NOT NULL,
    status ENUM('planned','open','on_hold','completed','closed','canceled') NOT NULL DEFAULT 'planned',
    budget_material_cost DECIMAL(15,2) NULL,
    actual_material_cost DECIMAL(15,2) NULL,
    start_date DATE NULL,
    target_date DATE NULL,
    completed_date DATE NULL,
    owner_user_id BIGINT UNSIGNED NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uq_project_company_number (company_id, number),
    INDEX idx_projects_status (company_id, status, target_date),
    CONSTRAINT fk_projects_company FOREIGN KEY (company_id) REFERENCES companies(id),
    CONSTRAINT fk_projects_owner FOREIGN KEY (owner_user_id) REFERENCES users(id)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS purchase_requests (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    request_number VARCHAR(80) NOT NULL UNIQUE,
    company_id BIGINT UNSIGNED NOT NULL,
    location_id BIGINT UNSIGNED NULL,
    department_id BIGINT UNSIGNED NULL,
    requested_by BIGINT UNSIGNED NOT NULL,
    project_workorder_id BIGINT UNSIGNED NULL,
    business_purpose ENUM('inventory_replenishment','customer_order','manufacturing_order','service_work_order','construction_project','vehicle_repair_restoration','facility_expense','capital_investment') NOT NULL,
    required_date DATE NULL,
    urgency ENUM('normal','urgent','critical') NOT NULL DEFAULT 'normal',
    status ENUM('draft','submitted','in_review','approved','rejected','converted','canceled') NOT NULL DEFAULT 'draft',
    justification TEXT NULL,
    estimated_total DECIMAL(15,2) NULL,
    submitted_at TIMESTAMP NULL,
    approved_at TIMESTAMP NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_purchase_requests_status (company_id, status, required_date),
    CONSTRAINT fk_purchase_requests_company FOREIGN KEY (company_id) REFERENCES companies(id),
    CONSTRAINT fk_purchase_requests_location FOREIGN KEY (location_id) REFERENCES locations(id),
    CONSTRAINT fk_purchase_requests_department FOREIGN KEY (department_id) REFERENCES departments(id),
    CONSTRAINT fk_purchase_requests_requester FOREIGN KEY (requested_by) REFERENCES users(id),
    CONSTRAINT fk_purchase_requests_project FOREIGN KEY (project_workorder_id) REFERENCES projects_workorders(id)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS purchase_request_lines (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    purchase_request_id BIGINT UNSIGNED NOT NULL,
    item_id BIGINT UNSIGNED NULL,
    requested_description VARCHAR(500) NOT NULL,
    quantity DECIMAL(15,4) NOT NULL,
    unit_of_measure VARCHAR(32) NOT NULL,
    estimated_unit_cost DECIMAL(15,4) NULL,
    preferred_supplier_id BIGINT UNSIGNED NULL,
    specification_notes TEXT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_pr_lines_request FOREIGN KEY (purchase_request_id) REFERENCES purchase_requests(id) ON DELETE CASCADE,
    CONSTRAINT fk_pr_lines_item FOREIGN KEY (item_id) REFERENCES items(id),
    CONSTRAINT fk_pr_lines_supplier FOREIGN KEY (preferred_supplier_id) REFERENCES suppliers(id)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS approval_actions (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    entity_type ENUM('purchase_request','purchase_order','supplier','contract','savings_opportunity') NOT NULL,
    entity_id BIGINT UNSIGNED NOT NULL,
    sequence_number INT UNSIGNED NOT NULL DEFAULT 1,
    approver_user_id BIGINT UNSIGNED NOT NULL,
    status ENUM('pending','approved','rejected','skipped') NOT NULL DEFAULT 'pending',
    comments TEXT NULL,
    acted_at TIMESTAMP NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_approvals_entity (entity_type, entity_id, status),
    CONSTRAINT fk_approvals_user FOREIGN KEY (approver_user_id) REFERENCES users(id)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS purchase_orders (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    po_number VARCHAR(80) NOT NULL UNIQUE,
    company_id BIGINT UNSIGNED NOT NULL,
    location_id BIGINT UNSIGNED NULL,
    department_id BIGINT UNSIGNED NULL,
    supplier_id BIGINT UNSIGNED NOT NULL,
    buyer_user_id BIGINT UNSIGNED NULL,
    purchase_request_id BIGINT UNSIGNED NULL,
    project_workorder_id BIGINT UNSIGNED NULL,
    business_purpose ENUM('inventory_replenishment','customer_order','manufacturing_order','service_work_order','construction_project','vehicle_repair_restoration','facility_expense','capital_investment') NOT NULL,
    order_date DATE NOT NULL,
    required_date DATE NULL,
    expected_date DATE NULL,
    status ENUM('draft','pending_approval','open','partially_received','received','past_due','canceled','closed') NOT NULL DEFAULT 'draft',
    currency_code CHAR(3) NOT NULL DEFAULT 'USD',
    subtotal DECIMAL(15,2) NOT NULL DEFAULT 0,
    freight_amount DECIMAL(15,2) NOT NULL DEFAULT 0,
    tax_amount DECIMAL(15,2) NOT NULL DEFAULT 0,
    total_amount DECIMAL(15,2) NOT NULL DEFAULT 0,
    expedite_reason VARCHAR(500) NULL,
    supplier_acknowledged_at TIMESTAMP NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_pos_company_status (company_id, status, expected_date),
    INDEX idx_pos_supplier_date (supplier_id, order_date),
    CONSTRAINT fk_pos_company FOREIGN KEY (company_id) REFERENCES companies(id),
    CONSTRAINT fk_pos_location FOREIGN KEY (location_id) REFERENCES locations(id),
    CONSTRAINT fk_pos_department FOREIGN KEY (department_id) REFERENCES departments(id),
    CONSTRAINT fk_pos_supplier FOREIGN KEY (supplier_id) REFERENCES suppliers(id),
    CONSTRAINT fk_pos_buyer FOREIGN KEY (buyer_user_id) REFERENCES users(id),
    CONSTRAINT fk_pos_request FOREIGN KEY (purchase_request_id) REFERENCES purchase_requests(id),
    CONSTRAINT fk_pos_project FOREIGN KEY (project_workorder_id) REFERENCES projects_workorders(id)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS purchase_order_lines (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    purchase_order_id BIGINT UNSIGNED NOT NULL,
    line_number INT UNSIGNED NOT NULL,
    item_id BIGINT UNSIGNED NULL,
    description VARCHAR(500) NOT NULL,
    category_id BIGINT UNSIGNED NULL,
    quantity_ordered DECIMAL(15,4) NOT NULL,
    quantity_received DECIMAL(15,4) NOT NULL DEFAULT 0,
    unit_of_measure VARCHAR(32) NOT NULL,
    unit_cost DECIMAL(15,4) NOT NULL,
    standard_cost_at_order DECIMAL(15,4) NULL,
    contract_cost_at_order DECIMAL(15,4) NULL,
    line_total DECIMAL(15,2) GENERATED ALWAYS AS (ROUND(quantity_ordered * unit_cost, 2)) STORED,
    required_date DATE NULL,
    expected_date DATE NULL,
    status ENUM('open','partially_received','received','canceled') NOT NULL DEFAULT 'open',
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uq_po_line (purchase_order_id, line_number),
    INDEX idx_po_lines_item (item_id),
    CONSTRAINT fk_po_lines_po FOREIGN KEY (purchase_order_id) REFERENCES purchase_orders(id) ON DELETE CASCADE,
    CONSTRAINT fk_po_lines_item FOREIGN KEY (item_id) REFERENCES items(id),
    CONSTRAINT fk_po_lines_category FOREIGN KEY (category_id) REFERENCES purchasing_categories(id)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS receipts (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    receipt_number VARCHAR(80) NOT NULL UNIQUE,
    purchase_order_id BIGINT UNSIGNED NOT NULL,
    location_id BIGINT UNSIGNED NOT NULL,
    received_by BIGINT UNSIGNED NOT NULL,
    received_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    packing_slip_number VARCHAR(100) NULL,
    carrier VARCHAR(160) NULL,
    tracking_number VARCHAR(160) NULL,
    notes TEXT NULL,
    CONSTRAINT fk_receipts_po FOREIGN KEY (purchase_order_id) REFERENCES purchase_orders(id),
    CONSTRAINT fk_receipts_location FOREIGN KEY (location_id) REFERENCES locations(id),
    CONSTRAINT fk_receipts_user FOREIGN KEY (received_by) REFERENCES users(id)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS receipt_lines (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    receipt_id BIGINT UNSIGNED NOT NULL,
    purchase_order_line_id BIGINT UNSIGNED NOT NULL,
    quantity_received DECIMAL(15,4) NOT NULL,
    quantity_accepted DECIMAL(15,4) NOT NULL,
    quantity_rejected DECIMAL(15,4) NOT NULL DEFAULT 0,
    condition_status ENUM('accepted','damaged','incorrect','quality_hold','returned') NOT NULL DEFAULT 'accepted',
    serial_or_lot_reference VARCHAR(240) NULL,
    notes TEXT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_receipt_lines_receipt FOREIGN KEY (receipt_id) REFERENCES receipts(id) ON DELETE CASCADE,
    CONSTRAINT fk_receipt_lines_po_line FOREIGN KEY (purchase_order_line_id) REFERENCES purchase_order_lines(id)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS inventory_locations (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    location_id BIGINT UNSIGNED NOT NULL,
    code VARCHAR(80) NOT NULL,
    name VARCHAR(160) NOT NULL,
    inventory_type ENUM('stock','service_vehicle','project_site','production','repair_bench','donor_stock','quarantine','strategic_legacy','customer_owned','other') NOT NULL DEFAULT 'stock',
    is_active BOOLEAN NOT NULL DEFAULT TRUE,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uq_inventory_location_code (location_id, code),
    CONSTRAINT fk_inventory_locations_location FOREIGN KEY (location_id) REFERENCES locations(id)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS inventory_balances (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    inventory_location_id BIGINT UNSIGNED NOT NULL,
    item_id BIGINT UNSIGNED NOT NULL,
    quantity_on_hand DECIMAL(15,4) NOT NULL DEFAULT 0,
    quantity_allocated DECIMAL(15,4) NOT NULL DEFAULT 0,
    average_unit_cost DECIMAL(15,4) NULL,
    last_receipt_date DATE NULL,
    last_usage_date DATE NULL,
    last_count_date DATE NULL,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uq_inventory_balance (inventory_location_id, item_id),
    INDEX idx_inventory_item_qty (item_id, quantity_on_hand),
    CONSTRAINT fk_inventory_balances_location FOREIGN KEY (inventory_location_id) REFERENCES inventory_locations(id),
    CONSTRAINT fk_inventory_balances_item FOREIGN KEY (item_id) REFERENCES items(id)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS inventory_transactions (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    item_id BIGINT UNSIGNED NOT NULL,
    from_inventory_location_id BIGINT UNSIGNED NULL,
    to_inventory_location_id BIGINT UNSIGNED NULL,
    project_workorder_id BIGINT UNSIGNED NULL,
    transaction_type ENUM('receipt','issue','transfer','return_to_stock','return_to_supplier','adjustment','cycle_count','scrap','core_return','donor_harvest','refurbish','sale','reservation','release') NOT NULL,
    quantity DECIMAL(15,4) NOT NULL,
    unit_cost DECIMAL(15,4) NULL,
    reason_code VARCHAR(80) NULL,
    reference_type VARCHAR(80) NULL,
    reference_id BIGINT UNSIGNED NULL,
    notes TEXT NULL,
    performed_by BIGINT UNSIGNED NOT NULL,
    performed_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_inventory_transactions_item_date (item_id, performed_at),
    INDEX idx_inventory_transactions_project (project_workorder_id),
    CONSTRAINT fk_inventory_transactions_item FOREIGN KEY (item_id) REFERENCES items(id),
    CONSTRAINT fk_inventory_transactions_from FOREIGN KEY (from_inventory_location_id) REFERENCES inventory_locations(id),
    CONSTRAINT fk_inventory_transactions_to FOREIGN KEY (to_inventory_location_id) REFERENCES inventory_locations(id),
    CONSTRAINT fk_inventory_transactions_project FOREIGN KEY (project_workorder_id) REFERENCES projects_workorders(id),
    CONSTRAINT fk_inventory_transactions_user FOREIGN KEY (performed_by) REFERENCES users(id)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS cycle_counts (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    inventory_location_id BIGINT UNSIGNED NOT NULL,
    count_reference VARCHAR(80) NOT NULL UNIQUE,
    status ENUM('planned','open','submitted','approved','posted','canceled') NOT NULL DEFAULT 'planned',
    scheduled_date DATE NULL,
    completed_date DATE NULL,
    assigned_to BIGINT UNSIGNED NULL,
    approved_by BIGINT UNSIGNED NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_cycle_counts_location FOREIGN KEY (inventory_location_id) REFERENCES inventory_locations(id),
    CONSTRAINT fk_cycle_counts_assignee FOREIGN KEY (assigned_to) REFERENCES users(id),
    CONSTRAINT fk_cycle_counts_approver FOREIGN KEY (approved_by) REFERENCES users(id)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS cycle_count_lines (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    cycle_count_id BIGINT UNSIGNED NOT NULL,
    item_id BIGINT UNSIGNED NOT NULL,
    system_quantity DECIMAL(15,4) NOT NULL,
    counted_quantity DECIMAL(15,4) NULL,
    variance_quantity DECIMAL(15,4) GENERATED ALWAYS AS (counted_quantity - system_quantity) STORED,
    variance_reason VARCHAR(500) NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uq_cycle_count_item (cycle_count_id, item_id),
    CONSTRAINT fk_cycle_count_lines_count FOREIGN KEY (cycle_count_id) REFERENCES cycle_counts(id) ON DELETE CASCADE,
    CONSTRAINT fk_cycle_count_lines_item FOREIGN KEY (item_id) REFERENCES items(id)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS supplier_scorecards (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    supplier_id BIGINT UNSIGNED NOT NULL,
    company_id BIGINT UNSIGNED NULL,
    period_start DATE NOT NULL,
    period_end DATE NOT NULL,
    annualized_spend DECIMAL(15,2) NULL,
    on_time_delivery_pct DECIMAL(6,3) NULL,
    complete_delivery_pct DECIMAL(6,3) NULL,
    quality_acceptance_pct DECIMAL(6,3) NULL,
    invoice_accuracy_pct DECIMAL(6,3) NULL,
    responsiveness_score DECIMAL(5,2) NULL COMMENT '1 to 5',
    contract_compliance_pct DECIMAL(6,3) NULL,
    risk_score DECIMAL(6,3) NULL COMMENT '0 low risk, 1 high risk',
    weighted_score DECIMAL(6,3) NULL,
    grade ENUM('preferred','approved','conditional','improvement_required','replacement_candidate') NULL,
    review_owner_id BIGINT UNSIGNED NULL,
    improvement_action TEXT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uq_supplier_scorecard_period (supplier_id, company_id, period_start, period_end),
    CONSTRAINT fk_scorecards_supplier FOREIGN KEY (supplier_id) REFERENCES suppliers(id),
    CONSTRAINT fk_scorecards_company FOREIGN KEY (company_id) REFERENCES companies(id),
    CONSTRAINT fk_scorecards_owner FOREIGN KEY (review_owner_id) REFERENCES users(id)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS quality_events (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    supplier_id BIGINT UNSIGNED NOT NULL,
    item_id BIGINT UNSIGNED NULL,
    purchase_order_line_id BIGINT UNSIGNED NULL,
    company_id BIGINT UNSIGNED NOT NULL,
    event_type ENUM('defect','wrong_item','short_shipment','shipping_damage','warranty','invoice_error','service_failure','other') NOT NULL,
    event_date DATE NOT NULL,
    quantity_affected DECIMAL(15,4) NULL,
    direct_cost DECIMAL(15,2) NULL,
    rework_cost DECIMAL(15,2) NULL,
    freight_cost DECIMAL(15,2) NULL,
    customer_impact_cost DECIMAL(15,2) NULL,
    supplier_recovery DECIMAL(15,2) NULL,
    status ENUM('open','investigating','supplier_action','resolved','closed') NOT NULL DEFAULT 'open',
    description TEXT NOT NULL,
    resolution TEXT NULL,
    owner_user_id BIGINT UNSIGNED NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_quality_supplier FOREIGN KEY (supplier_id) REFERENCES suppliers(id),
    CONSTRAINT fk_quality_item FOREIGN KEY (item_id) REFERENCES items(id),
    CONSTRAINT fk_quality_po_line FOREIGN KEY (purchase_order_line_id) REFERENCES purchase_order_lines(id),
    CONSTRAINT fk_quality_company FOREIGN KEY (company_id) REFERENCES companies(id),
    CONSTRAINT fk_quality_owner FOREIGN KEY (owner_user_id) REFERENCES users(id)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS savings_opportunities (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    opportunity_number VARCHAR(80) NOT NULL UNIQUE,
    company_id BIGINT UNSIGNED NULL COMMENT 'NULL means enterprise opportunity',
    category_id BIGINT UNSIGNED NULL,
    supplier_id BIGINT UNSIGNED NULL,
    title VARCHAR(240) NOT NULL,
    description TEXT NOT NULL,
    opportunity_type ENUM('price','supplier_consolidation','freight','payment_terms','inventory_reduction','transfer','process','quality','warranty_recovery','specification','other') NOT NULL,
    current_annual_cost DECIMAL(15,2) NULL,
    expected_annual_savings DECIMAL(15,2) NULL,
    implementation_cost DECIMAL(15,2) NOT NULL DEFAULT 0,
    realized_savings DECIMAL(15,2) NOT NULL DEFAULT 0,
    status ENUM('identified','analyzing','negotiating','approved','implementing','completed','rejected') NOT NULL DEFAULT 'identified',
    risk_rating ENUM('low','medium','high','critical') NOT NULL DEFAULT 'medium',
    owner_user_id BIGINT UNSIGNED NULL,
    target_date DATE NULL,
    accounting_validation ENUM('not_requested','pending','validated','rejected') NOT NULL DEFAULT 'not_requested',
    accounting_validated_by BIGINT UNSIGNED NULL,
    accounting_validated_at TIMESTAMP NULL,
    next_step TEXT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_savings_status (status, target_date),
    CONSTRAINT fk_savings_company FOREIGN KEY (company_id) REFERENCES companies(id),
    CONSTRAINT fk_savings_category FOREIGN KEY (category_id) REFERENCES purchasing_categories(id),
    CONSTRAINT fk_savings_supplier FOREIGN KEY (supplier_id) REFERENCES suppliers(id),
    CONSTRAINT fk_savings_owner FOREIGN KEY (owner_user_id) REFERENCES users(id),
    CONSTRAINT fk_savings_accounting_user FOREIGN KEY (accounting_validated_by) REFERENCES users(id)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS imports (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    company_id BIGINT UNSIGNED NULL,
    import_type ENUM('supplier_master','item_master','purchase_orders','receipts','inventory_snapshot','sales_usage','projects_workorders','quality_events','other') NOT NULL,
    original_filename VARCHAR(255) NOT NULL,
    stored_path VARCHAR(500) NOT NULL,
    status ENUM('uploaded','mapping','validating','ready','processing','completed','failed','canceled') NOT NULL DEFAULT 'uploaded',
    total_rows INT UNSIGNED NOT NULL DEFAULT 0,
    accepted_rows INT UNSIGNED NOT NULL DEFAULT 0,
    rejected_rows INT UNSIGNED NOT NULL DEFAULT 0,
    mapping_json JSON NULL,
    validation_summary JSON NULL,
    imported_by BIGINT UNSIGNED NOT NULL,
    started_at TIMESTAMP NULL,
    completed_at TIMESTAMP NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_imports_company FOREIGN KEY (company_id) REFERENCES companies(id),
    CONSTRAINT fk_imports_user FOREIGN KEY (imported_by) REFERENCES users(id)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS ai_briefings (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    company_id BIGINT UNSIGNED NULL COMMENT 'NULL means enterprise briefing',
    briefing_type ENUM('weekly_executive','supplier_risk','inventory_risk','po_risk','savings_opportunities','custom') NOT NULL,
    title VARCHAR(240) NOT NULL,
    date_start DATE NULL,
    date_end DATE NULL,
    prompt_version VARCHAR(80) NULL,
    model_name VARCHAR(120) NULL,
    source_snapshot JSON NULL,
    summary_markdown MEDIUMTEXT NOT NULL,
    status ENUM('draft','reviewed','published','archived') NOT NULL DEFAULT 'draft',
    generated_by_user_id BIGINT UNSIGNED NULL,
    reviewed_by_user_id BIGINT UNSIGNED NULL,
    reviewed_at TIMESTAMP NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_ai_briefings_type_date (briefing_type, created_at),
    CONSTRAINT fk_ai_briefings_company FOREIGN KEY (company_id) REFERENCES companies(id),
    CONSTRAINT fk_ai_briefings_generator FOREIGN KEY (generated_by_user_id) REFERENCES users(id),
    CONSTRAINT fk_ai_briefings_reviewer FOREIGN KEY (reviewed_by_user_id) REFERENCES users(id)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS ai_findings (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    briefing_id BIGINT UNSIGNED NULL,
    company_id BIGINT UNSIGNED NULL,
    finding_type ENUM('duplicate_supplier','price_variance','late_po','stockout_risk','transfer_opportunity','excess_inventory','supplier_risk','savings_opportunity','data_quality','other') NOT NULL,
    severity ENUM('information','low','medium','high','critical') NOT NULL DEFAULT 'medium',
    title VARCHAR(240) NOT NULL,
    finding_text TEXT NOT NULL,
    recommended_action TEXT NULL,
    evidence_json JSON NULL,
    entity_type VARCHAR(80) NULL,
    entity_id BIGINT UNSIGNED NULL,
    confidence_score DECIMAL(6,3) NULL,
    status ENUM('new','reviewing','accepted','dismissed','actioned','closed') NOT NULL DEFAULT 'new',
    assigned_to BIGINT UNSIGNED NULL,
    due_date DATE NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_ai_findings_status (status, severity, due_date),
    CONSTRAINT fk_ai_findings_briefing FOREIGN KEY (briefing_id) REFERENCES ai_briefings(id) ON DELETE SET NULL,
    CONSTRAINT fk_ai_findings_company FOREIGN KEY (company_id) REFERENCES companies(id),
    CONSTRAINT fk_ai_findings_assignee FOREIGN KEY (assigned_to) REFERENCES users(id)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS notifications (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id BIGINT UNSIGNED NOT NULL,
    notification_type VARCHAR(80) NOT NULL,
    title VARCHAR(240) NOT NULL,
    message TEXT NOT NULL,
    severity ENUM('info','success','warning','critical') NOT NULL DEFAULT 'info',
    entity_type VARCHAR(80) NULL,
    entity_id BIGINT UNSIGNED NULL,
    read_at TIMESTAMP NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_notifications_user_read (user_id, read_at, created_at),
    CONSTRAINT fk_notifications_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS audit_logs (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id BIGINT UNSIGNED NULL,
    company_id BIGINT UNSIGNED NULL,
    action VARCHAR(120) NOT NULL,
    entity_type VARCHAR(120) NOT NULL,
    entity_id BIGINT UNSIGNED NULL,
    before_data JSON NULL,
    after_data JSON NULL,
    ip_address VARCHAR(64) NULL,
    user_agent VARCHAR(500) NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_audit_entity (entity_type, entity_id, created_at),
    INDEX idx_audit_user_date (user_id, created_at),
    CONSTRAINT fk_audit_user FOREIGN KEY (user_id) REFERENCES users(id),
    CONSTRAINT fk_audit_company FOREIGN KEY (company_id) REFERENCES companies(id)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS system_settings (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    setting_key VARCHAR(160) NOT NULL UNIQUE,
    setting_value JSON NOT NULL,
    description VARCHAR(500) NULL,
    is_secret BOOLEAN NOT NULL DEFAULT FALSE,
    updated_by BIGINT UNSIGNED NULL,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_system_settings_user FOREIGN KEY (updated_by) REFERENCES users(id)
) ENGINE=InnoDB;

-- Useful reporting views
CREATE OR REPLACE VIEW vw_open_po_commitments AS
SELECT
    po.id,
    po.po_number,
    po.company_id,
    c.name AS company_name,
    po.supplier_id,
    s.normalized_name AS supplier_name,
    po.order_date,
    po.required_date,
    po.expected_date,
    po.status,
    po.total_amount,
    GREATEST(DATEDIFF(CURRENT_DATE, COALESCE(po.expected_date, po.required_date)), 0) AS days_past_due
FROM purchase_orders po
JOIN companies c ON c.id = po.company_id
JOIN suppliers s ON s.id = po.supplier_id
WHERE po.status IN ('open','partially_received','past_due');

CREATE OR REPLACE VIEW vw_inventory_aging AS
SELECT
    ib.id AS inventory_balance_id,
    c.id AS company_id,
    c.name AS company_name,
    l.name AS location_name,
    il.name AS inventory_location_name,
    i.id AS item_id,
    i.item_number,
    i.normalized_description,
    i.strategic_legacy,
    ib.quantity_on_hand,
    ib.average_unit_cost,
    ROUND(ib.quantity_on_hand * COALESCE(ib.average_unit_cost, 0), 2) AS inventory_value,
    ib.last_usage_date,
    CASE
        WHEN i.strategic_legacy = 1 THEN 'Strategic Legacy'
        WHEN ib.last_usage_date IS NULL THEN 'Review'
        WHEN DATEDIFF(CURRENT_DATE, ib.last_usage_date) <= 90 THEN 'Active'
        WHEN DATEDIFF(CURRENT_DATE, ib.last_usage_date) <= 180 THEN 'Monitor'
        WHEN DATEDIFF(CURRENT_DATE, ib.last_usage_date) <= 365 THEN 'Slow-Moving'
        WHEN DATEDIFF(CURRENT_DATE, ib.last_usage_date) <= 730 THEN 'Excess'
        ELSE 'Obsolete'
    END AS aging_classification
FROM inventory_balances ib
JOIN inventory_locations il ON il.id = ib.inventory_location_id
JOIN locations l ON l.id = il.location_id
JOIN companies c ON c.id = l.company_id
JOIN items i ON i.id = ib.item_id;

CREATE OR REPLACE VIEW vw_purchase_price_variance AS
SELECT
    pol.id AS purchase_order_line_id,
    po.company_id,
    po.supplier_id,
    po.order_date,
    pol.item_id,
    pol.quantity_ordered,
    pol.unit_cost,
    pol.standard_cost_at_order,
    pol.contract_cost_at_order,
    ROUND((pol.unit_cost - COALESCE(pol.contract_cost_at_order, pol.standard_cost_at_order, pol.unit_cost)) * pol.quantity_ordered, 2) AS price_variance
FROM purchase_order_lines pol
JOIN purchase_orders po ON po.id = pol.purchase_order_id;

-- Seed data
INSERT IGNORE INTO companies (code, name, description) VALUES
('GC', 'Gruber Communications', 'Manufacturing, distribution, custom cabling and communications infrastructure'),
('GPS', 'Gruber Power Services', 'Critical power equipment, field service, repair, maintenance and asset recovery'),
('GTS', 'Gruber Technical Services', 'Electrical, structured cabling, data-center construction and raised flooring'),
('GMC', 'Gruber Motor Company', 'Tesla and EV repair, engineering, restoration, parts and vehicle support'),
('EVP', 'EV Preserve', 'Managed storage, monitoring and preservation for collectible electric vehicles'),
('GCP', 'Gruber Commercial Properties', 'Commercial property operations, facilities, projects and vendor management');

INSERT IGNORE INTO roles (code, name, description) VALUES
('super_admin', 'Super Admin', 'Full enterprise administration'),
('executive_viewer', 'Executive Viewer', 'Enterprise dashboards and approved reports'),
('procurement_lead', 'Procurement Lead', 'Supplier strategy, governance, approvals and reporting'),
('buyer', 'Buyer', 'Purchase requests, quotes, purchase orders and supplier follow-up'),
('inventory_manager', 'Inventory Manager', 'Receiving, balances, transfers, cycle counts and adjustments'),
('accounting_reviewer', 'Accounting Reviewer', 'Cost, invoice, savings and financial validation'),
('company_manager', 'Company Manager', 'Company-level operational management'),
('project_manager', 'Project Manager', 'Project and work-order material management'),
('ai_reviewer', 'AI Reviewer', 'Reviews AI-generated briefings and findings');

INSERT IGNORE INTO purchasing_categories (code, name, description) VALUES
('BATTERIES', 'Batteries and Energy Storage', 'UPS, EV, security, emergency-lighting and related batteries'),
('UPS', 'UPS and Critical Power Equipment', 'UPS systems, modules, power distribution and critical power components'),
('CABLE', 'Cable and Connectivity', 'Copper, fiber, connectors, wire and communication accessories'),
('ELECTRICAL', 'Electrical Materials', 'Commercial electrical components, switchgear and installation materials'),
('METAL', 'Metal and Fabrication Materials', 'Racks, panels, shelves, sheet metal and fabrication inputs'),
('VEHICLE_PARTS', 'Vehicle Parts and Components', 'Tesla, EV, Roadster and general vehicle components'),
('FREIGHT', 'Freight and Transportation', 'Inbound, outbound, expedited and vehicle transportation'),
('FACILITY', 'Facility and Operating Supplies', 'Facility, security, cleaning, storage and operating supplies'),
('IT', 'Information Technology', 'Software, hardware, networks and technology services'),
('PROFESSIONAL', 'Professional and Subcontracted Services', 'Consultants, subcontractors and technical services');



-- Admin Console, security, workflow, and data-governance extensions.
-- These tables are separated from the operational schema so production records
-- remain distinct from the session-isolated Demo Mode dataset.

CREATE TABLE IF NOT EXISTS permissions (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    permission_key VARCHAR(160) NOT NULL UNIQUE,
    area VARCHAR(80) NOT NULL,
    action VARCHAR(80) NOT NULL,
    description VARCHAR(500) NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_permissions_area_action (area, action)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS role_permissions (
    role_id BIGINT UNSIGNED NOT NULL,
    permission_id BIGINT UNSIGNED NOT NULL,
    granted_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    granted_by BIGINT UNSIGNED NULL,
    PRIMARY KEY (role_id, permission_id),
    CONSTRAINT fk_role_permissions_role FOREIGN KEY (role_id) REFERENCES roles(id) ON DELETE CASCADE,
    CONSTRAINT fk_role_permissions_permission FOREIGN KEY (permission_id) REFERENCES permissions(id) ON DELETE CASCADE,
    CONSTRAINT fk_role_permissions_granted_by FOREIGN KEY (granted_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS user_profiles (
    user_id BIGINT UNSIGNED PRIMARY KEY,
    first_name VARCHAR(100) NULL,
    last_name VARCHAR(100) NULL,
    phone VARCHAR(64) NULL,
    department VARCHAR(160) NULL,
    employment_status ENUM('active','suspended','archived','pending') NOT NULL DEFAULT 'active',
    password_reset_required BOOLEAN NOT NULL DEFAULT FALSE,
    administrative_notes TEXT NULL,
    archived_at TIMESTAMP NULL,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_user_profiles_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS company_memberships (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id BIGINT UNSIGNED NOT NULL,
    company_id BIGINT UNSIGNED NOT NULL,
    is_primary BOOLEAN NOT NULL DEFAULT FALSE,
    membership_status ENUM('active','suspended','archived','pending') NOT NULL DEFAULT 'active',
    assigned_by BIGINT UNSIGNED NULL,
    assigned_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uq_company_membership (user_id, company_id),
    INDEX idx_memberships_company_status (company_id, membership_status),
    CONSTRAINT fk_company_memberships_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    CONSTRAINT fk_company_memberships_company FOREIGN KEY (company_id) REFERENCES companies(id) ON DELETE CASCADE,
    CONSTRAINT fk_company_memberships_assigner FOREIGN KEY (assigned_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS access_requests (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    requester_name VARCHAR(160) NOT NULL,
    requester_email VARCHAR(190) NOT NULL,
    requested_company_id BIGINT UNSIGNED NULL,
    requested_role_code VARCHAR(64) NULL,
    request_reason TEXT NULL,
    status ENUM('pending','approved','declined','withdrawn') NOT NULL DEFAULT 'pending',
    review_note TEXT NULL,
    reviewed_by BIGINT UNSIGNED NULL,
    reviewed_at TIMESTAMP NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_access_requests_status_created (status, created_at),
    CONSTRAINT fk_access_requests_company FOREIGN KEY (requested_company_id) REFERENCES companies(id),
    CONSTRAINT fk_access_requests_reviewer FOREIGN KEY (reviewed_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS user_sessions (
    id CHAR(64) PRIMARY KEY,
    user_id BIGINT UNSIGNED NOT NULL,
    ip_address VARCHAR(64) NULL,
    user_agent VARCHAR(500) NULL,
    device_label VARCHAR(160) NULL,
    started_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    last_activity_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    expires_at TIMESTAMP NOT NULL,
    revoked_at TIMESTAMP NULL,
    revoked_by BIGINT UNSIGNED NULL,
    INDEX idx_user_sessions_user_active (user_id, revoked_at, expires_at),
    CONSTRAINT fk_user_sessions_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    CONSTRAINT fk_user_sessions_revoker FOREIGN KEY (revoked_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS password_reset_tokens (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id BIGINT UNSIGNED NOT NULL,
    token_hash CHAR(64) NOT NULL UNIQUE,
    expires_at TIMESTAMP NOT NULL,
    used_at TIMESTAMP NULL,
    revoked_at TIMESTAMP NULL,
    requested_ip VARCHAR(64) NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_password_reset_user_expiry (user_id, expires_at),
    CONSTRAINT fk_password_reset_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS security_events (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id BIGINT UNSIGNED NULL,
    event_type VARCHAR(120) NOT NULL,
    severity ENUM('info','warning','high','critical') NOT NULL DEFAULT 'info',
    email VARCHAR(190) NULL,
    ip_address VARCHAR(64) NULL,
    user_agent VARCHAR(500) NULL,
    details JSON NULL,
    occurred_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_security_events_type_date (event_type, occurred_at),
    INDEX idx_security_events_user_date (user_id, occurred_at),
    CONSTRAINT fk_security_events_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS discovery_assignments (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    company_id BIGINT UNSIGNED NOT NULL,
    module_code VARCHAR(80) NOT NULL,
    title VARCHAR(240) NOT NULL,
    description TEXT NULL,
    owner_user_id BIGINT UNSIGNED NULL,
    reviewer_user_id BIGINT UNSIGNED NULL,
    due_date DATE NULL,
    status ENUM('draft','assigned','in_progress','submitted','changes_requested','validated','approved') NOT NULL DEFAULT 'draft',
    completion_pct TINYINT UNSIGNED NOT NULL DEFAULT 0,
    created_by BIGINT UNSIGNED NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_discovery_company_status (company_id, status),
    CONSTRAINT fk_discovery_assignment_company FOREIGN KEY (company_id) REFERENCES companies(id),
    CONSTRAINT fk_discovery_assignment_owner FOREIGN KEY (owner_user_id) REFERENCES users(id) ON DELETE SET NULL,
    CONSTRAINT fk_discovery_assignment_reviewer FOREIGN KEY (reviewer_user_id) REFERENCES users(id) ON DELETE SET NULL,
    CONSTRAINT fk_discovery_assignment_creator FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS discovery_responses (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    assignment_id BIGINT UNSIGNED NOT NULL,
    question_key VARCHAR(160) NOT NULL,
    question_text VARCHAR(500) NOT NULL,
    response_text MEDIUMTEXT NULL,
    evidence_json JSON NULL,
    response_status ENUM('unanswered','draft','submitted','validated','changes_requested') NOT NULL DEFAULT 'unanswered',
    answered_by BIGINT UNSIGNED NULL,
    reviewed_by BIGINT UNSIGNED NULL,
    answered_at TIMESTAMP NULL,
    reviewed_at TIMESTAMP NULL,
    UNIQUE KEY uq_discovery_response_question (assignment_id, question_key),
    CONSTRAINT fk_discovery_response_assignment FOREIGN KEY (assignment_id) REFERENCES discovery_assignments(id) ON DELETE CASCADE,
    CONSTRAINT fk_discovery_response_answerer FOREIGN KEY (answered_by) REFERENCES users(id) ON DELETE SET NULL,
    CONSTRAINT fk_discovery_response_reviewer FOREIGN KEY (reviewed_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS workflow_approvals (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    company_id BIGINT UNSIGNED NULL,
    module VARCHAR(80) NOT NULL,
    entity_type VARCHAR(120) NOT NULL,
    entity_id BIGINT UNSIGNED NOT NULL,
    requested_action VARCHAR(120) NOT NULL,
    status ENUM('pending','changes_requested','approved','declined','canceled') NOT NULL DEFAULT 'pending',
    requested_by BIGINT UNSIGNED NOT NULL,
    assigned_reviewer_id BIGINT UNSIGNED NULL,
    decision_note TEXT NULL,
    requested_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    decided_at TIMESTAMP NULL,
    INDEX idx_workflow_approval_queue (status, assigned_reviewer_id, requested_at),
    INDEX idx_workflow_approval_entity (entity_type, entity_id),
    CONSTRAINT fk_workflow_approval_company FOREIGN KEY (company_id) REFERENCES companies(id),
    CONSTRAINT fk_workflow_approval_requester FOREIGN KEY (requested_by) REFERENCES users(id),
    CONSTRAINT fk_workflow_approval_reviewer FOREIGN KEY (assigned_reviewer_id) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS record_comments (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    company_id BIGINT UNSIGNED NULL,
    entity_type VARCHAR(120) NOT NULL,
    entity_id BIGINT UNSIGNED NOT NULL,
    author_user_id BIGINT UNSIGNED NOT NULL,
    comment_text TEXT NOT NULL,
    is_internal BOOLEAN NOT NULL DEFAULT TRUE,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_record_comments_entity (entity_type, entity_id, created_at),
    CONSTRAINT fk_record_comments_company FOREIGN KEY (company_id) REFERENCES companies(id),
    CONSTRAINT fk_record_comments_author FOREIGN KEY (author_user_id) REFERENCES users(id)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS import_validation_errors (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    import_id BIGINT UNSIGNED NOT NULL,
    source_row_number INT UNSIGNED NOT NULL,
    column_name VARCHAR(160) NULL,
    error_code VARCHAR(80) NOT NULL,
    error_message VARCHAR(500) NOT NULL,
    raw_value TEXT NULL,
    resolution_status ENUM('open','ignored','corrected') NOT NULL DEFAULT 'open',
    resolved_by BIGINT UNSIGNED NULL,
    resolved_at TIMESTAMP NULL,
    INDEX idx_import_errors_job_status (import_id, resolution_status),
    CONSTRAINT fk_import_errors_import FOREIGN KEY (import_id) REFERENCES imports(id) ON DELETE CASCADE,
    CONSTRAINT fk_import_errors_resolver FOREIGN KEY (resolved_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS import_receipts (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    import_id BIGINT UNSIGNED NOT NULL UNIQUE,
    receipt_number VARCHAR(100) NOT NULL UNIQUE,
    committed_by BIGINT UNSIGNED NOT NULL,
    committed_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    accepted_rows INT UNSIGNED NOT NULL DEFAULT 0,
    rejected_rows INT UNSIGNED NOT NULL DEFAULT 0,
    checksum_sha256 CHAR(64) NULL,
    summary_json JSON NULL,
    CONSTRAINT fk_import_receipt_import FOREIGN KEY (import_id) REFERENCES imports(id) ON DELETE CASCADE,
    CONSTRAINT fk_import_receipt_user FOREIGN KEY (committed_by) REFERENCES users(id)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS data_quality_exceptions (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    company_id BIGINT UNSIGNED NULL,
    module VARCHAR(80) NOT NULL,
    entity_type VARCHAR(120) NOT NULL,
    entity_id BIGINT UNSIGNED NULL,
    severity ENUM('low','medium','high','critical') NOT NULL DEFAULT 'medium',
    exception_code VARCHAR(100) NOT NULL,
    title VARCHAR(240) NOT NULL,
    description TEXT NOT NULL,
    status ENUM('open','assigned','in_review','resolved','accepted_risk') NOT NULL DEFAULT 'open',
    owner_user_id BIGINT UNSIGNED NULL,
    resolved_by BIGINT UNSIGNED NULL,
    resolved_at TIMESTAMP NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_data_quality_queue (status, severity, company_id),
    CONSTRAINT fk_data_quality_company FOREIGN KEY (company_id) REFERENCES companies(id),
    CONSTRAINT fk_data_quality_owner FOREIGN KEY (owner_user_id) REFERENCES users(id) ON DELETE SET NULL,
    CONSTRAINT fk_data_quality_resolver FOREIGN KEY (resolved_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS health_checks (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    environment VARCHAR(40) NOT NULL,
    database_status VARCHAR(40) NOT NULL,
    storage_status VARCHAR(40) NOT NULL,
    php_version VARCHAR(40) NOT NULL,
    extension_status JSON NULL,
    email_queue_status VARCHAR(80) NULL,
    notes VARCHAR(500) NULL,
    checked_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_health_checks_date (checked_at)
) ENGINE=InnoDB;

INSERT IGNORE INTO roles (code, name, description) VALUES
('system_administrator', 'System Administrator', 'Full platform, security, user, role, and company administration'),
('executive', 'Executive', 'Enterprise dashboards, approved reports, and read-only audit visibility'),
('company_administrator', 'Company Administrator', 'Company-scoped administration and operational review'),
('procurement_manager', 'Procurement Manager', 'Procurement ownership, validation, sourcing and approvals'),
('data_contributor', 'Data Contributor', 'Create and submit company-scoped operational records'),
('reviewer', 'Reviewer', 'Review, request changes, validate, and approve records'),
('read_only', 'Read Only', 'View permitted companies and approved records without mutation access');

INSERT IGNORE INTO permissions (permission_key, area, action, description) VALUES
('platform.view','platform','view','View the application'),
('platform.administer','platform','administer','Administer the platform'),
('users.view','users','view','View users'),
('users.create','users','create','Create users'),
('users.edit','users','edit','Edit users'),
('users.delete','users','delete','Archive or delete users'),
('users.assign','users','assign','Assign users to companies and roles'),
('users.administer','users','administer','Administer users and sessions'),
('roles.view','roles','view','View roles'),
('roles.create','roles','create','Create roles'),
('roles.edit','roles','edit','Edit roles'),
('roles.delete','roles','delete','Archive custom roles'),
('roles.assign','roles','assign','Assign roles'),
('roles.administer','roles','administer','Administer role permissions'),
('companies.view','companies','view','View companies'),
('companies.create','companies','create','Create companies'),
('companies.edit','companies','edit','Edit companies'),
('companies.delete','companies','delete','Archive companies'),
('companies.assign','companies','assign','Assign company access'),
('companies.administer','companies','administer','Administer companies'),
('discovery.view','discovery','view','View discovery records'),
('discovery.create','discovery','create','Create discovery records'),
('discovery.edit','discovery','edit','Edit discovery records'),
('discovery.assign','discovery','assign','Assign discovery ownership'),
('discovery.submit','discovery','submit','Submit discovery records'),
('discovery.review','discovery','review','Review discovery records'),
('discovery.approve','discovery','approve','Approve discovery records'),
('discovery.export','discovery','export','Export discovery data'),
('suppliers.view','suppliers','view','View suppliers'),
('suppliers.create','suppliers','create','Create suppliers'),
('suppliers.edit','suppliers','edit','Edit suppliers'),
('suppliers.assign','suppliers','assign','Assign supplier owners'),
('suppliers.submit','suppliers','submit','Submit suppliers'),
('suppliers.review','suppliers','review','Review suppliers'),
('suppliers.approve','suppliers','approve','Approve suppliers'),
('suppliers.export','suppliers','export','Export suppliers'),
('items.view','items','view','View items'),
('items.create','items','create','Create items'),
('items.edit','items','edit','Edit items'),
('items.assign','items','assign','Assign item owners'),
('items.submit','items','submit','Submit items'),
('items.review','items','review','Review items'),
('items.approve','items','approve','Approve items'),
('items.export','items','export','Export items'),
('purchase_orders.view','purchase_orders','view','View purchase orders'),
('purchase_orders.create','purchase_orders','create','Create purchase orders'),
('purchase_orders.edit','purchase_orders','edit','Edit purchase orders'),
('purchase_orders.assign','purchase_orders','assign','Assign purchase orders'),
('purchase_orders.submit','purchase_orders','submit','Submit purchase orders'),
('purchase_orders.review','purchase_orders','review','Review purchase orders'),
('purchase_orders.approve','purchase_orders','approve','Approve purchase orders'),
('purchase_orders.export','purchase_orders','export','Export purchase orders'),
('inventory.view','inventory','view','View inventory'),
('inventory.create','inventory','create','Create inventory records'),
('inventory.edit','inventory','edit','Edit inventory records'),
('inventory.assign','inventory','assign','Assign inventory owners'),
('inventory.submit','inventory','submit','Submit inventory records'),
('inventory.review','inventory','review','Review inventory records'),
('inventory.approve','inventory','approve','Approve inventory records'),
('inventory.export','inventory','export','Export inventory'),
('savings.view','savings','view','View savings opportunities'),
('savings.create','savings','create','Create savings opportunities'),
('savings.edit','savings','edit','Edit savings opportunities'),
('savings.assign','savings','assign','Assign savings owners'),
('savings.submit','savings','submit','Submit savings opportunities'),
('savings.review','savings','review','Review savings opportunities'),
('savings.approve','savings','approve','Approve savings opportunities'),
('savings.export','savings','export','Export savings'),
('scorecards.view','scorecards','view','View supplier scorecards'),
('scorecards.create','scorecards','create','Create scorecards'),
('scorecards.edit','scorecards','edit','Edit scorecards'),
('scorecards.submit','scorecards','submit','Submit scorecards'),
('scorecards.review','scorecards','review','Review scorecards'),
('scorecards.approve','scorecards','approve','Approve scorecards'),
('scorecards.export','scorecards','export','Export scorecards'),
('imports.view','imports','view','View imports'),
('imports.create','imports','create','Create imports'),
('imports.edit','imports','edit','Edit import mappings'),
('imports.delete','imports','delete','Delete failed imports'),
('imports.submit','imports','submit','Submit imports'),
('imports.review','imports','review','Review import errors'),
('imports.approve','imports','approve','Commit imports'),
('imports.export','imports','export','Export import results'),
('imports.administer','imports','administer','Administer import policy'),
('approvals.view','approvals','view','View approvals'),
('approvals.submit','approvals','submit','Submit approval requests'),
('approvals.review','approvals','review','Review approval requests'),
('approvals.approve','approvals','approve','Approve requests'),
('approvals.administer','approvals','administer','Administer workflows'),
('reports.view','reports','view','View reports'),
('reports.export','reports','export','Export reports'),
('audit.view','audit','view','View immutable audit logs'),
('audit.export','audit','export','Export audit logs'),
('audit.administer','audit','administer','Administer audit retention'),
('agent.view','agent','view','Use Agent Workspace'),
('agent.administer','agent','administer','Administer Agent Workspace'),
('settings.view','settings','view','View settings'),
('settings.edit','settings','edit','Edit personal settings'),
('settings.administer','settings','administer','Administer platform settings'),
('security.view','security','view','View security status'),
('security.administer','security','administer','Administer security policy');

-- Seed the system administrator role with every permission. Other production role
-- grants can be reviewed and assigned in the Admin Console after installation.
INSERT IGNORE INTO role_permissions (role_id, permission_id)
SELECT r.id, p.id FROM roles r CROSS JOIN permissions p WHERE r.code IN ('super_admin','system_administrator');

INSERT INTO system_settings (setting_key, setting_value, description)
VALUES
('platform.name', JSON_QUOTE('Gruber Procurement Intelligence'), 'Public platform name'),
('platform.default_timezone', JSON_QUOTE('America/Phoenix'), 'Default application timezone'),
('platform.currency', JSON_QUOTE('USD'), 'Default currency'),
('security.session_lifetime_minutes', '120', 'Default session lifetime'),
('security.password_reset_lifetime_minutes', '60', 'Password reset token lifetime'),
('security.max_login_attempts', '5', 'Failed login threshold'),
('security.lockout_minutes', '15', 'Login lockout duration'),
('demo.available', 'true', 'Whether intentional Demo Mode entry is available'),
('agent.enabled', 'true', 'Whether Agent Workspace is available'),
('agent.approved_data_only', 'true', 'Restrict Agent Workspace to approved evidence')
ON DUPLICATE KEY UPDATE description = VALUES(description);

SET FOREIGN_KEY_CHECKS = 1;
