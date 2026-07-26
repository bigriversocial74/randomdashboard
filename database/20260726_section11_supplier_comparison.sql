-- Gruber Procurement Intelligence
-- Section 11: Supplier Comparison & Strategic Sourcing Workspace
-- Apply after database/gruber_ai_procurement_single_install_v3.sql or the Phase 2 v3 upgrade.
-- Idempotent for MySQL 8.0 and MariaDB 10.11.

SET NAMES utf8mb4;
SET time_zone = '+00:00';

CREATE TABLE IF NOT EXISTS sourcing_comparisons (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    comparison_number VARCHAR(40) NOT NULL UNIQUE,
    company_id BIGINT UNSIGNED NULL,
    title VARCHAR(190) NOT NULL,
    category VARCHAR(190) NOT NULL DEFAULT 'Strategic sourcing',
    selected_supplier_ids_json LONGTEXT NOT NULL,
    criteria_weights_json LONGTEXT NOT NULL,
    preferred_supplier_id BIGINT UNSIGNED NULL,
    alternate_supplier_id BIGINT UNSIGNED NULL,
    decision_status VARCHAR(32) NOT NULL DEFAULT 'draft',
    rationale TEXT NOT NULL,
    owner_id BIGINT UNSIGNED NOT NULL,
    approval_id BIGINT UNSIGNED NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    KEY idx_sourcing_company_status (company_id, decision_status),
    KEY idx_sourcing_owner (owner_id),
    KEY idx_sourcing_preferred_supplier (preferred_supplier_id),
    KEY idx_sourcing_approval (approval_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO schema_migrations (version, description, checksum_sha256)
VALUES ('4.0-section11', 'Supplier comparison and strategic sourcing decision records', NULL)
ON DUPLICATE KEY UPDATE description = VALUES(description);
