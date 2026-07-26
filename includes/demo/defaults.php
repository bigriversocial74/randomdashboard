<?php
declare(strict_types=1);

function demo_default_state(): array
{
    $now = new DateTimeImmutable('now', new DateTimeZone('America/Phoenix'));
    $today = $now->format('Y-m-d');
    $stamp = $now->format('Y-m-d H:i:s');
    $rolePermissions = role_permission_defaults();

    $companies = [
        ['id' => 1, 'code' => 'GC', 'name' => 'Gruber Communications', 'status' => 'active', 'industry' => 'Communications manufacturing and distribution', 'phone' => '(602) 555-0101', 'email' => 'procurement.gc@example.test', 'data_owner_id' => 4, 'procurement_owner_id' => 3, 'accounting_reviewer_id' => 6, 'completion' => 88, 'modules' => ['discovery','suppliers','items','purchase_orders','inventory','savings','scorecards','imports'], 'retention_days' => 2555],
        ['id' => 2, 'code' => 'GPS', 'name' => 'Gruber Power Services', 'status' => 'active', 'industry' => 'Critical power service and equipment', 'phone' => '(602) 555-0102', 'email' => 'procurement.gps@example.test', 'data_owner_id' => 5, 'procurement_owner_id' => 3, 'accounting_reviewer_id' => 6, 'completion' => 92, 'modules' => ['discovery','suppliers','items','purchase_orders','inventory','savings','scorecards','imports'], 'retention_days' => 2555],
        ['id' => 3, 'code' => 'GTS', 'name' => 'Gruber Technical Services', 'status' => 'active', 'industry' => 'Technical field services and infrastructure', 'phone' => '(602) 555-0103', 'email' => 'procurement.gts@example.test', 'data_owner_id' => 5, 'procurement_owner_id' => 3, 'accounting_reviewer_id' => 6, 'completion' => 79, 'modules' => ['discovery','suppliers','items','purchase_orders','inventory','savings','scorecards','imports'], 'retention_days' => 2555],
        ['id' => 4, 'code' => 'GMC', 'name' => 'Gruber Motor Company', 'status' => 'active', 'industry' => 'Electric vehicle service and restoration', 'phone' => '(602) 555-0104', 'email' => 'procurement.gmc@example.test', 'data_owner_id' => 4, 'procurement_owner_id' => 3, 'accounting_reviewer_id' => 6, 'completion' => 84, 'modules' => ['discovery','suppliers','items','purchase_orders','inventory','savings','scorecards','imports'], 'retention_days' => 2555],
        ['id' => 5, 'code' => 'EVP', 'name' => 'EV Preserve', 'status' => 'active', 'industry' => 'Managed electric vehicle preservation', 'phone' => '(602) 555-0105', 'email' => 'procurement.evp@example.test', 'data_owner_id' => 5, 'procurement_owner_id' => 3, 'accounting_reviewer_id' => 6, 'completion' => 71, 'modules' => ['discovery','suppliers','items','purchase_orders','inventory','savings','scorecards','imports'], 'retention_days' => 2555],
        ['id' => 6, 'code' => 'GCP', 'name' => 'Gruber Commercial Properties', 'status' => 'active', 'industry' => 'Commercial property operations', 'phone' => '(602) 555-0106', 'email' => 'procurement.gcp@example.test', 'data_owner_id' => 4, 'procurement_owner_id' => 3, 'accounting_reviewer_id' => 6, 'completion' => 68, 'modules' => ['discovery','suppliers','purchase_orders','inventory','savings','imports'], 'retention_days' => 2555],
    ];

    $roles = [
        ['id' => 1, 'code' => 'system_administrator', 'name' => 'System Administrator', 'description' => 'Full enterprise administration, security, settings, and all company data.', 'system' => true, 'status' => 'active', 'permissions' => $rolePermissions['system_administrator']],
        ['id' => 2, 'code' => 'executive', 'name' => 'Executive', 'description' => 'Enterprise dashboards, reports, approved records, and audit visibility.', 'system' => true, 'status' => 'active', 'permissions' => $rolePermissions['executive']],
        ['id' => 3, 'code' => 'company_administrator', 'name' => 'Company Administrator', 'description' => 'Company-scoped user, access, data, and workflow administration.', 'system' => true, 'status' => 'active', 'permissions' => $rolePermissions['company_administrator']],
        ['id' => 4, 'code' => 'procurement_manager', 'name' => 'Procurement Manager', 'description' => 'Supplier, purchasing, savings, import, and approval leadership.', 'system' => true, 'status' => 'active', 'permissions' => $rolePermissions['procurement_manager']],
        ['id' => 5, 'code' => 'data_contributor', 'name' => 'Data Contributor', 'description' => 'Creates and maintains operational records and submits them for review.', 'system' => true, 'status' => 'active', 'permissions' => $rolePermissions['data_contributor']],
        ['id' => 6, 'code' => 'reviewer', 'name' => 'Reviewer', 'description' => 'Reviews, validates, approves, or requests changes to submitted records.', 'system' => true, 'status' => 'active', 'permissions' => $rolePermissions['reviewer']],
        ['id' => 7, 'code' => 'read_only', 'name' => 'Read Only', 'description' => 'Views permitted company records without modifying data.', 'system' => true, 'status' => 'active', 'permissions' => $rolePermissions['read_only']],
    ];

    $users = [
        ['id' => 1, 'first_name' => 'Avery', 'last_name' => 'Morgan', 'name' => 'Avery Morgan', 'email' => 'admin@demo.gruber.test', 'phone' => '(602) 555-1001', 'job_title' => 'Platform Administrator', 'department' => 'Enterprise Technology', 'status' => 'active', 'primary_company_id' => 1, 'company_ids' => [1,2,3,4,5,6], 'role_codes' => ['system_administrator'], 'last_login' => $stamp, 'require_password_reset' => false, 'created_at' => '2026-05-12 09:10:00', 'updated_at' => $stamp, 'admin_notes' => 'Seeded system administrator account for isolated demonstration only.'],
        ['id' => 2, 'first_name' => 'Jordan', 'last_name' => 'Blake', 'name' => 'Jordan Blake', 'email' => 'executive@demo.gruber.test', 'phone' => '(602) 555-1002', 'job_title' => 'Executive Sponsor', 'department' => 'Executive Leadership', 'status' => 'active', 'primary_company_id' => 1, 'company_ids' => [1,2,3,4,5,6], 'role_codes' => ['executive'], 'last_login' => $now->modify('-2 hours')->format('Y-m-d H:i:s'), 'require_password_reset' => false, 'created_at' => '2026-05-15 11:30:00', 'updated_at' => $stamp, 'admin_notes' => 'Enterprise read and reporting access.'],
        ['id' => 3, 'first_name' => 'Taylor', 'last_name' => 'Reed', 'name' => 'Taylor Reed', 'email' => 'procurement@demo.gruber.test', 'phone' => '(602) 555-1003', 'job_title' => 'Enterprise Procurement Manager', 'department' => 'Procurement', 'status' => 'active', 'primary_company_id' => 2, 'company_ids' => [1,2,3,4,5,6], 'role_codes' => ['procurement_manager'], 'last_login' => $now->modify('-45 minutes')->format('Y-m-d H:i:s'), 'require_password_reset' => false, 'created_at' => '2026-05-18 08:45:00', 'updated_at' => $stamp, 'admin_notes' => 'Enterprise procurement workflow owner.'],
        ['id' => 4, 'first_name' => 'Morgan', 'last_name' => 'Lee', 'name' => 'Morgan Lee', 'email' => 'companyadmin@demo.gruber.test', 'phone' => '(602) 555-1004', 'job_title' => 'Operations Administrator', 'department' => 'Operations', 'status' => 'active', 'primary_company_id' => 1, 'company_ids' => [1,4,6], 'role_codes' => ['company_administrator'], 'last_login' => $now->modify('-1 day')->format('Y-m-d H:i:s'), 'require_password_reset' => false, 'created_at' => '2026-05-20 10:00:00', 'updated_at' => $stamp, 'admin_notes' => 'Administrative scope limited to assigned companies.'],
        ['id' => 5, 'first_name' => 'Casey', 'last_name' => 'Nguyen', 'name' => 'Casey Nguyen', 'email' => 'contributor@demo.gruber.test', 'phone' => '(602) 555-1005', 'job_title' => 'Procurement Data Specialist', 'department' => 'Procurement Operations', 'status' => 'active', 'primary_company_id' => 2, 'company_ids' => [2,3,5], 'role_codes' => ['data_contributor'], 'last_login' => $now->modify('-3 hours')->format('Y-m-d H:i:s'), 'require_password_reset' => false, 'created_at' => '2026-06-01 08:30:00', 'updated_at' => $stamp, 'admin_notes' => 'Responsible for source collection and master-data preparation.'],
        ['id' => 6, 'first_name' => 'Riley', 'last_name' => 'Patel', 'name' => 'Riley Patel', 'email' => 'reviewer@demo.gruber.test', 'phone' => '(602) 555-1006', 'job_title' => 'Accounting Reviewer', 'department' => 'Accounting', 'status' => 'active', 'primary_company_id' => 1, 'company_ids' => [1,2,3,4,5,6], 'role_codes' => ['reviewer'], 'last_login' => $now->modify('-4 hours')->format('Y-m-d H:i:s'), 'require_password_reset' => false, 'created_at' => '2026-06-03 09:15:00', 'updated_at' => $stamp, 'admin_notes' => 'Validates savings, financial fields, and approval evidence.'],
        ['id' => 7, 'first_name' => 'Jamie', 'last_name' => 'Cruz', 'name' => 'Jamie Cruz', 'email' => 'readonly@demo.gruber.test', 'phone' => '(602) 555-1007', 'job_title' => 'Business Analyst', 'department' => 'Business Intelligence', 'status' => 'active', 'primary_company_id' => 3, 'company_ids' => [1,2,3,4,5,6], 'role_codes' => ['read_only'], 'last_login' => $now->modify('-2 days')->format('Y-m-d H:i:s'), 'require_password_reset' => false, 'created_at' => '2026-06-10 13:20:00', 'updated_at' => $stamp, 'admin_notes' => 'Read-only account for reporting review.'],
        ['id' => 8, 'first_name' => 'Parker', 'last_name' => 'Stone', 'name' => 'Parker Stone', 'email' => 'suspended@demo.gruber.test', 'phone' => '(602) 555-1008', 'job_title' => 'Former Contract Buyer', 'department' => 'Procurement', 'status' => 'suspended', 'primary_company_id' => 4, 'company_ids' => [4], 'role_codes' => ['data_contributor'], 'last_login' => $now->modify('-21 days')->format('Y-m-d H:i:s'), 'require_password_reset' => true, 'created_at' => '2026-04-02 09:00:00', 'updated_at' => $now->modify('-6 days')->format('Y-m-d H:i:s'), 'admin_notes' => 'Suspended after contract completion.'],
        ['id' => 9, 'first_name' => 'Skyler', 'last_name' => 'Diaz', 'name' => 'Skyler Diaz', 'email' => 'archived@demo.gruber.test', 'phone' => '(602) 555-1009', 'job_title' => 'Legacy Data Coordinator', 'department' => 'Operations', 'status' => 'archived', 'primary_company_id' => 5, 'company_ids' => [5], 'role_codes' => ['read_only'], 'last_login' => $now->modify('-90 days')->format('Y-m-d H:i:s'), 'require_password_reset' => false, 'created_at' => '2025-11-12 09:00:00', 'updated_at' => $now->modify('-30 days')->format('Y-m-d H:i:s'), 'admin_notes' => 'Archived for historical audit continuity.'],
    ];

    $categories = [
        ['id' => 1, 'code' => 'BAT', 'name' => 'Batteries & Energy Storage', 'status' => 'active'],
        ['id' => 2, 'code' => 'ELEC', 'name' => 'Electrical Materials', 'status' => 'active'],
        ['id' => 3, 'code' => 'CAB', 'name' => 'Cable & Connectivity', 'status' => 'active'],
        ['id' => 4, 'code' => 'EVP', 'name' => 'EV Components', 'status' => 'active'],
        ['id' => 5, 'code' => 'FAC', 'name' => 'Facilities & Property', 'status' => 'active'],
        ['id' => 6, 'code' => 'MRO', 'name' => 'MRO & Service Parts', 'status' => 'active'],
        ['id' => 7, 'code' => 'TECH', 'name' => 'Technology & Software', 'status' => 'active'],
    ];

    $suppliers = [
        ['id' => 1, 'supplier_number' => 'SUP-1001', 'name' => 'Copper State Critical Power', 'legal_name' => 'Copper State Critical Power LLC', 'company_ids' => [1,2,3], 'category_id' => 1, 'category' => 'Batteries & Energy Storage', 'status' => 'preferred', 'risk' => 'low', 'owner_id' => 3, 'annual_spend' => 1284400, 'payment_terms' => 'Net 30', 'website' => 'https://example.test/copper-state', 'review_status' => 'approved', 'sample' => true],
        ['id' => 2, 'supplier_number' => 'SUP-1002', 'name' => 'Desert Electrical Distribution', 'legal_name' => 'Desert Electrical Distribution Inc.', 'company_ids' => [1,2,3,6], 'category_id' => 2, 'category' => 'Electrical Materials', 'status' => 'approved', 'risk' => 'medium', 'owner_id' => 3, 'annual_spend' => 842760, 'payment_terms' => 'Net 45', 'website' => 'https://example.test/desert-electrical', 'review_status' => 'validated', 'sample' => true],
        ['id' => 3, 'supplier_number' => 'SUP-1003', 'name' => 'Southwest Fiber & Copper', 'legal_name' => 'Southwest Fiber and Copper Manufacturing Co.', 'company_ids' => [1,3], 'category_id' => 3, 'category' => 'Cable & Connectivity', 'status' => 'approved', 'risk' => 'low', 'owner_id' => 5, 'annual_spend' => 619320, 'payment_terms' => 'Net 30', 'website' => 'https://example.test/southwest-fiber', 'review_status' => 'submitted', 'sample' => true],
        ['id' => 4, 'supplier_number' => 'SUP-1004', 'name' => 'Legacy Motion Components', 'legal_name' => 'Legacy Motion Components Corporation', 'company_ids' => [2,4,5], 'category_id' => 4, 'category' => 'EV Components', 'status' => 'conditional', 'risk' => 'high', 'owner_id' => 3, 'annual_spend' => 318950, 'payment_terms' => 'Prepaid', 'website' => 'https://example.test/legacy-motion', 'review_status' => 'changes_requested', 'sample' => true],
        ['id' => 5, 'supplier_number' => 'SUP-1005', 'name' => 'Phoenix Facility Supply', 'legal_name' => 'Phoenix Facility Supply LLC', 'company_ids' => [1,2,3,4,5,6], 'category_id' => 5, 'category' => 'Facilities & Property', 'status' => 'approved', 'risk' => 'medium', 'owner_id' => 4, 'annual_spend' => 241600, 'payment_terms' => 'Net 30', 'website' => 'https://example.test/phoenix-facility', 'review_status' => 'approved', 'sample' => true],
        ['id' => 6, 'supplier_number' => 'SUP-1006', 'name' => 'Sonoran Industrial Service', 'legal_name' => 'Sonoran Industrial Service Group', 'company_ids' => [2,3,4], 'category_id' => 6, 'category' => 'MRO & Service Parts', 'status' => 'preferred', 'risk' => 'low', 'owner_id' => 5, 'annual_spend' => 407880, 'payment_terms' => 'Net 30', 'website' => 'https://example.test/sonoran-industrial', 'review_status' => 'validated', 'sample' => true],
        ['id' => 7, 'supplier_number' => 'SUP-1007', 'name' => 'Mesa Cloud Operations', 'legal_name' => 'Mesa Cloud Operations Inc.', 'company_ids' => [1,2,3,4,5,6], 'category_id' => 7, 'category' => 'Technology & Software', 'status' => 'candidate', 'risk' => 'medium', 'owner_id' => 1, 'annual_spend' => 162000, 'payment_terms' => 'Net 30', 'website' => 'https://example.test/mesa-cloud', 'review_status' => 'draft', 'sample' => true],
        ['id' => 8, 'supplier_number' => 'SUP-1008', 'name' => 'Valley Fleet & Logistics', 'legal_name' => 'Valley Fleet and Logistics LLC', 'company_ids' => [1,2,3,4,5,6], 'category_id' => 6, 'category' => 'MRO & Service Parts', 'status' => 'approved', 'risk' => 'medium', 'owner_id' => 3, 'annual_spend' => 526400, 'payment_terms' => 'Net 30', 'website' => 'https://example.test/valley-logistics', 'review_status' => 'submitted', 'sample' => true],
    ];

    $supplierContacts = [];
    foreach ($suppliers as $supplier) {
        $supplierContacts[] = [
            'id' => count($supplierContacts) + 1,
            'supplier_id' => $supplier['id'],
            'name' => ['Mia Chen','Noah Williams','Olivia Garcia','Ethan Bennett','Sophia Ramirez','Liam Carter','Emma Brooks','Lucas Turner'][$supplier['id'] - 1],
            'title' => $supplier['id'] % 2 ? 'Account Executive' : 'Customer Operations Manager',
            'email' => 'contact' . $supplier['id'] . '@supplier-demo.test',
            'phone' => '(480) 555-' . str_pad((string) (2000 + $supplier['id']), 4, '0', STR_PAD_LEFT),
            'primary' => true,
        ];
    }

    $supplierRelationships = [];
    foreach ($suppliers as $supplier) {
        foreach ($supplier['company_ids'] as $companyId) {
            $supplierRelationships[] = [
                'id' => count($supplierRelationships) + 1,
                'supplier_id' => $supplier['id'],
                'company_id' => $companyId,
                'account_number' => 'A-' . str_pad((string) $supplier['id'], 3, '0', STR_PAD_LEFT) . '-' . $companyId,
                'status' => 'active',
                'terms' => $supplier['payment_terms'],
            ];
        }
    }

    $contracts = [
        ['id' => 1, 'supplier_id' => 1, 'company_id' => 2, 'number' => 'CTR-GPS-026', 'title' => 'Battery Supply and Recovery Agreement', 'start_date' => '2026-01-01', 'end_date' => '2027-12-31', 'annual_value' => 950000, 'status' => 'active', 'owner_id' => 3],
        ['id' => 2, 'supplier_id' => 2, 'company_id' => null, 'number' => 'CTR-ENT-014', 'title' => 'Enterprise Electrical Distribution Agreement', 'start_date' => '2025-10-01', 'end_date' => '2026-09-30', 'annual_value' => 760000, 'status' => 'expiring', 'owner_id' => 3],
        ['id' => 3, 'supplier_id' => 3, 'company_id' => 1, 'number' => 'CTR-GC-041', 'title' => 'Cable Manufacturing Supply Terms', 'start_date' => '2026-04-01', 'end_date' => '2028-03-31', 'annual_value' => 540000, 'status' => 'active', 'owner_id' => 5],
        ['id' => 4, 'supplier_id' => 4, 'company_id' => 4, 'number' => 'CTR-GMC-009', 'title' => 'Legacy EV Component Purchase Terms', 'start_date' => '2025-01-01', 'end_date' => '2026-08-31', 'annual_value' => 285000, 'status' => 'expiring', 'owner_id' => 3],
        ['id' => 5, 'supplier_id' => 5, 'company_id' => 6, 'number' => 'CTR-GCP-003', 'title' => 'Property Maintenance Supplies', 'start_date' => '2026-02-01', 'end_date' => '2027-01-31', 'annual_value' => 168000, 'status' => 'active', 'owner_id' => 4],
        ['id' => 6, 'supplier_id' => 7, 'company_id' => null, 'number' => 'CTR-ENT-DRAFT', 'title' => 'Shared Analytics Hosting', 'start_date' => null, 'end_date' => null, 'annual_value' => 144000, 'status' => 'draft', 'owner_id' => 1],
    ];

    $items = [
        ['id' => 1, 'item_number' => 'BAT-UPS-12V-100', 'sku' => 'CSCP-12-100', 'description' => '12V 100Ah sealed UPS battery', 'category_id' => 1, 'company_ids' => [1,2,3], 'uom' => 'EA', 'standard_cost' => 189.50, 'owner_id' => 5, 'status' => 'active', 'review_status' => 'approved', 'sample' => true],
        ['id' => 2, 'item_number' => 'ELEC-BKR-200A', 'sku' => 'DED-200-MCCB', 'description' => '200A molded-case circuit breaker', 'category_id' => 2, 'company_ids' => [2,3,6], 'uom' => 'EA', 'standard_cost' => 448.00, 'owner_id' => 5, 'status' => 'active', 'review_status' => 'validated', 'sample' => true],
        ['id' => 3, 'item_number' => 'CAB-CAT6A-BLU', 'sku' => 'SWFC-C6A-B-1000', 'description' => 'CAT6A plenum cable, blue, 1,000 ft', 'category_id' => 3, 'company_ids' => [1,3], 'uom' => 'BOX', 'standard_cost' => 426.75, 'owner_id' => 5, 'status' => 'active', 'review_status' => 'submitted', 'sample' => true],
        ['id' => 4, 'item_number' => 'EV-CTRL-LEG-01', 'sku' => 'LMC-CONTROL-01', 'description' => 'Legacy EV motor controller assembly', 'category_id' => 4, 'company_ids' => [4,5], 'uom' => 'EA', 'standard_cost' => 3750.00, 'owner_id' => 3, 'status' => 'active', 'review_status' => 'changes_requested', 'sample' => true],
        ['id' => 5, 'item_number' => 'FAC-FLTR-MERV13', 'sku' => 'PFS-M13-2025', 'description' => 'MERV 13 HVAC filter, 20x25x2', 'category_id' => 5, 'company_ids' => [1,2,3,4,5,6], 'uom' => 'EA', 'standard_cost' => 22.90, 'owner_id' => 4, 'status' => 'active', 'review_status' => 'approved', 'sample' => true],
        ['id' => 6, 'item_number' => 'MRO-BRG-6205', 'sku' => 'SIS-6205-2RS', 'description' => '6205 sealed service bearing', 'category_id' => 6, 'company_ids' => [2,3,4], 'uom' => 'EA', 'standard_cost' => 18.65, 'owner_id' => 5, 'status' => 'active', 'review_status' => 'validated', 'sample' => true],
        ['id' => 7, 'item_number' => 'TECH-SEAT-ANL', 'sku' => 'MCO-ANALYTICS', 'description' => 'Annual procurement analytics user seat', 'category_id' => 7, 'company_ids' => [1,2,3,4,5,6], 'uom' => 'YR', 'standard_cost' => 1200.00, 'owner_id' => 1, 'status' => 'draft', 'review_status' => 'draft', 'sample' => true],
        ['id' => 8, 'item_number' => 'CAB-FIB-OS2-12', 'sku' => 'SWFC-OS2-12', 'description' => '12-strand OS2 indoor/outdoor fiber', 'category_id' => 3, 'company_ids' => [1,3], 'uom' => 'FT', 'standard_cost' => 1.82, 'owner_id' => 5, 'status' => 'active', 'review_status' => 'approved', 'sample' => true],
        ['id' => 9, 'item_number' => 'BAT-EV-MOD-R2', 'sku' => 'CSCP-EVM-R2', 'description' => 'Reconditioned EV battery module, revision 2', 'category_id' => 1, 'company_ids' => [4,5], 'uom' => 'EA', 'standard_cost' => 780.00, 'owner_id' => 3, 'status' => 'active', 'review_status' => 'submitted', 'sample' => true],
        ['id' => 10, 'item_number' => 'FAC-LED-4FT', 'sku' => 'PFS-LED-4-40', 'description' => '4-foot LED utility fixture', 'category_id' => 5, 'company_ids' => [2,3,6], 'uom' => 'EA', 'standard_cost' => 54.10, 'owner_id' => 4, 'status' => 'active', 'review_status' => 'approved', 'sample' => true],
    ];

    $purchaseOrders = [
        ['id' => 1, 'po_number' => 'GPS-10428', 'company_id' => 2, 'supplier_id' => 1, 'order_date' => '2026-07-02', 'required_date' => '2026-07-16', 'expected_date' => '2026-07-16', 'status' => 'past_due', 'total_amount' => 94800, 'buyer_id' => 3, 'review_status' => 'approved'],
        ['id' => 2, 'po_number' => 'GC-08714', 'company_id' => 1, 'supplier_id' => 3, 'order_date' => '2026-07-11', 'required_date' => '2026-07-29', 'expected_date' => '2026-07-28', 'status' => 'open', 'total_amount' => 36520, 'buyer_id' => 5, 'review_status' => 'approved'],
        ['id' => 3, 'po_number' => 'GTS-06341', 'company_id' => 3, 'supplier_id' => 2, 'order_date' => '2026-07-14', 'required_date' => '2026-08-04', 'expected_date' => '2026-08-02', 'status' => 'open', 'total_amount' => 48760, 'buyer_id' => 3, 'review_status' => 'validated'],
        ['id' => 4, 'po_number' => 'GMC-02918', 'company_id' => 4, 'supplier_id' => 4, 'order_date' => '2026-07-08', 'required_date' => '2026-07-24', 'expected_date' => '2026-07-30', 'status' => 'open', 'total_amount' => 22500, 'buyer_id' => 3, 'review_status' => 'changes_requested'],
        ['id' => 5, 'po_number' => 'EVP-01103', 'company_id' => 5, 'supplier_id' => 6, 'order_date' => '2026-07-18', 'required_date' => '2026-08-01', 'expected_date' => '2026-07-31', 'status' => 'partially_received', 'total_amount' => 15780, 'buyer_id' => 5, 'review_status' => 'approved'],
        ['id' => 6, 'po_number' => 'GCP-00412', 'company_id' => 6, 'supplier_id' => 5, 'order_date' => '2026-07-20', 'required_date' => '2026-08-08', 'expected_date' => '2026-08-06', 'status' => 'open', 'total_amount' => 18840, 'buyer_id' => 4, 'review_status' => 'submitted'],
        ['id' => 7, 'po_number' => 'ENT-00017', 'company_id' => 1, 'supplier_id' => 7, 'order_date' => '2026-07-22', 'required_date' => '2026-08-15', 'expected_date' => '2026-08-15', 'status' => 'draft', 'total_amount' => 72000, 'buyer_id' => 1, 'review_status' => 'draft'],
        ['id' => 8, 'po_number' => 'GPS-10439', 'company_id' => 2, 'supplier_id' => 8, 'order_date' => '2026-07-23', 'required_date' => '2026-07-28', 'expected_date' => '2026-07-28', 'status' => 'open', 'total_amount' => 12640, 'buyer_id' => 3, 'review_status' => 'approved'],
    ];

    $purchaseOrderLines = [
        ['id'=>1,'purchase_order_id'=>1,'item_id'=>1,'description'=>'12V 100Ah sealed UPS battery','quantity'=>500,'unit_cost'=>189.60,'line_total'=>94800],
        ['id'=>2,'purchase_order_id'=>2,'item_id'=>3,'description'=>'CAT6A plenum cable, blue','quantity'=>80,'unit_cost'=>426.75,'line_total'=>34140],
        ['id'=>3,'purchase_order_id'=>2,'item_id'=>8,'description'=>'OS2 fiber cable','quantity'=>1308,'unit_cost'=>1.82,'line_total'=>2380.56],
        ['id'=>4,'purchase_order_id'=>3,'item_id'=>2,'description'=>'200A molded-case circuit breaker','quantity'=>80,'unit_cost'=>448,'line_total'=>35840],
        ['id'=>5,'purchase_order_id'=>3,'item_id'=>10,'description'=>'4-foot LED utility fixture','quantity'=>240,'unit_cost'=>53.83,'line_total'=>12919.20],
        ['id'=>6,'purchase_order_id'=>4,'item_id'=>4,'description'=>'Legacy EV motor controller assembly','quantity'=>6,'unit_cost'=>3750,'line_total'=>22500],
        ['id'=>7,'purchase_order_id'=>5,'item_id'=>6,'description'=>'6205 sealed service bearing','quantity'=>300,'unit_cost'=>18.60,'line_total'=>5580],
        ['id'=>8,'purchase_order_id'=>5,'item_id'=>9,'description'=>'Reconditioned EV battery module','quantity'=>13,'unit_cost'=>784.62,'line_total'=>10200.06],
        ['id'=>9,'purchase_order_id'=>6,'item_id'=>5,'description'=>'MERV 13 HVAC filter','quantity'=>300,'unit_cost'=>22.80,'line_total'=>6840],
        ['id'=>10,'purchase_order_id'=>6,'item_id'=>10,'description'=>'4-foot LED utility fixture','quantity'=>220,'unit_cost'=>54.55,'line_total'=>12001],
        ['id'=>11,'purchase_order_id'=>7,'item_id'=>7,'description'=>'Annual procurement analytics user seat','quantity'=>60,'unit_cost'=>1200,'line_total'=>72000],
        ['id'=>12,'purchase_order_id'=>8,'item_id'=>6,'description'=>'Preventive maintenance service kits','quantity'=>80,'unit_cost'=>158,'line_total'=>12640],
    ];

    $openCommitments = [];
    foreach ($purchaseOrders as $po) {
        if (in_array($po['status'], ['open','past_due','partially_received'], true)) {
            $openCommitments[] = [
                'id' => count($openCommitments) + 1,
                'purchase_order_id' => $po['id'],
                'company_id' => $po['company_id'],
                'po_number' => $po['po_number'],
                'supplier_id' => $po['supplier_id'],
                'amount' => $po['total_amount'],
                'expected_date' => $po['expected_date'],
                'status' => $po['status'],
            ];
        }
    }

    $inventoryLocations = [
        ['id'=>1,'company_id'=>1,'code'=>'GC-PHX-MAIN','name'=>'GC Phoenix Main Warehouse','type'=>'warehouse','status'=>'active'],
        ['id'=>2,'company_id'=>2,'code'=>'GPS-SVC-01','name'=>'GPS Service Parts Warehouse','type'=>'warehouse','status'=>'active'],
        ['id'=>3,'company_id'=>3,'code'=>'GTS-FIELD','name'=>'GTS Field Staging','type'=>'service_center','status'=>'active'],
        ['id'=>4,'company_id'=>4,'code'=>'GMC-RESTORE','name'=>'GMC Restoration Parts','type'=>'warehouse','status'=>'active'],
        ['id'=>5,'company_id'=>5,'code'=>'EVP-VAULT','name'=>'EV Preserve Parts Vault','type'=>'storage_facility','status'=>'active'],
        ['id'=>6,'company_id'=>6,'code'=>'GCP-MAINT','name'=>'GCP Maintenance Stores','type'=>'warehouse','status'=>'active'],
    ];

    $inventorySnapshots = [
        ['id'=>1,'company_id'=>1,'location_id'=>1,'item_id'=>3,'snapshot_date'=>$today,'quantity_on_hand'=>46,'allocated'=>12,'available'=>34,'unit_cost'=>426.75,'value'=>19630.50],
        ['id'=>2,'company_id'=>1,'location_id'=>1,'item_id'=>8,'snapshot_date'=>$today,'quantity_on_hand'=>1800,'allocated'=>400,'available'=>1400,'unit_cost'=>1.82,'value'=>3276],
        ['id'=>3,'company_id'=>2,'location_id'=>2,'item_id'=>1,'snapshot_date'=>$today,'quantity_on_hand'=>182,'allocated'=>140,'available'=>42,'unit_cost'=>188.20,'value'=>34252.40],
        ['id'=>4,'company_id'=>2,'location_id'=>2,'item_id'=>6,'snapshot_date'=>$today,'quantity_on_hand'=>420,'allocated'=>88,'available'=>332,'unit_cost'=>18.40,'value'=>7728],
        ['id'=>5,'company_id'=>3,'location_id'=>3,'item_id'=>2,'snapshot_date'=>$today,'quantity_on_hand'=>31,'allocated'=>22,'available'=>9,'unit_cost'=>441.50,'value'=>13686.50],
        ['id'=>6,'company_id'=>4,'location_id'=>4,'item_id'=>4,'snapshot_date'=>$today,'quantity_on_hand'=>4,'allocated'=>2,'available'=>2,'unit_cost'=>3610,'value'=>14440],
        ['id'=>7,'company_id'=>5,'location_id'=>5,'item_id'=>9,'snapshot_date'=>$today,'quantity_on_hand'=>21,'allocated'=>8,'available'=>13,'unit_cost'=>748,'value'=>15708],
        ['id'=>8,'company_id'=>6,'location_id'=>6,'item_id'=>5,'snapshot_date'=>$today,'quantity_on_hand'=>118,'allocated'=>24,'available'=>94,'unit_cost'=>21.80,'value'=>2572.40],
        ['id'=>9,'company_id'=>6,'location_id'=>6,'item_id'=>10,'snapshot_date'=>$today,'quantity_on_hand'=>36,'allocated'=>6,'available'=>30,'unit_cost'=>52.90,'value'=>1904.40],
    ];

    $inventoryAging = [
        ['id'=>1,'company_id'=>1,'item_id'=>3,'location_id'=>1,'classification'=>'active','days_since_use'=>18,'quantity'=>46,'value'=>19630.50],
        ['id'=>2,'company_id'=>1,'item_id'=>8,'location_id'=>1,'classification'=>'slow_moving','days_since_use'=>221,'quantity'=>1800,'value'=>3276],
        ['id'=>3,'company_id'=>2,'item_id'=>1,'location_id'=>2,'classification'=>'active','days_since_use'=>4,'quantity'=>182,'value'=>34252.40],
        ['id'=>4,'company_id'=>3,'item_id'=>2,'location_id'=>3,'classification'=>'monitor','days_since_use'=>124,'quantity'=>31,'value'=>13686.50],
        ['id'=>5,'company_id'=>4,'item_id'=>4,'location_id'=>4,'classification'=>'strategic_legacy','days_since_use'=>410,'quantity'=>4,'value'=>14440],
        ['id'=>6,'company_id'=>5,'item_id'=>9,'location_id'=>5,'classification'=>'monitor','days_since_use'=>137,'quantity'=>21,'value'=>15708],
        ['id'=>7,'company_id'=>6,'item_id'=>5,'location_id'=>6,'classification'=>'active','days_since_use'=>33,'quantity'=>118,'value'=>2572.40],
    ];
    $savings = [
        ['id'=>1,'opportunity_number'=>'SAV-2026-0001','company_id'=>1,'category_id'=>2,'supplier_id'=>3,'title'=>'Enterprise cable-volume agreement','description'=>'Consolidate cable demand across operating companies into a single forecast and negotiated volume agreement.','opportunity_type'=>'supplier_consolidation','category'=>'Strategic sourcing','owner_id'=>3,'stage'=>'validated','operational_status'=>'approved','annualized_value'=>86500,'current_annual_cost'=>742000,'implementation_cost'=>8500,'realized_savings'=>0,'confidence'=>82,'risk'=>'medium','due_date'=>'2026-08-15','accounting_validation'=>'pending','next_step'=>'Accounting validates the demand baseline before final supplier award.','review_status'=>'validated'],
        ['id'=>2,'opportunity_number'=>'SAV-2026-0002','company_id'=>2,'category_id'=>5,'supplier_id'=>1,'title'=>'Battery core-return credit recovery','description'=>'Recover supplier credits for eligible battery cores and standardize the return documentation process.','opportunity_type'=>'warranty_recovery','category'=>'Supplier recovery','owner_id'=>3,'stage'=>'approved','operational_status'=>'implementing','annualized_value'=>124000,'current_annual_cost'=>510000,'implementation_cost'=>12000,'realized_savings'=>41800,'confidence'=>91,'risk'=>'low','due_date'=>'2026-08-01','accounting_validation'=>'validated','next_step'=>'Reconcile July credit memos and close the first realization period.','review_status'=>'approved'],
        ['id'=>3,'opportunity_number'=>'SAV-2026-0003','company_id'=>3,'category_id'=>3,'supplier_id'=>0,'title'=>'Internal transfer before field purchase','description'=>'Search available inventory across Gruber companies before authorizing new field purchases.','opportunity_type'=>'transfer','category'=>'Demand avoidance','owner_id'=>5,'stage'=>'submitted','operational_status'=>'analyzing','annualized_value'=>42000,'current_annual_cost'=>186000,'implementation_cost'=>3500,'realized_savings'=>9600,'confidence'=>74,'risk'=>'medium','due_date'=>'2026-08-20','accounting_validation'=>'not_requested','next_step'=>'Validate transfer freight, custody, and replenishment rules.','review_status'=>'submitted'],
        ['id'=>4,'opportunity_number'=>'SAV-2026-0004','company_id'=>4,'category_id'=>4,'supplier_id'=>4,'title'=>'Legacy component repair-versus-buy program','description'=>'Create repair thresholds for scarce legacy EV components and compare repair yield against replacement cost.','opportunity_type'=>'specification','category'=>'Value engineering','owner_id'=>3,'stage'=>'review','operational_status'=>'negotiating','annualized_value'=>67500,'current_annual_cost'=>298000,'implementation_cost'=>18500,'realized_savings'=>0,'confidence'=>68,'risk'=>'high','due_date'=>'2026-09-01','accounting_validation'=>'not_requested','next_step'=>'Complete repair partner quote comparison and warranty-risk review.','review_status'=>'in_review'],
        ['id'=>5,'opportunity_number'=>'SAV-2026-0005','company_id'=>5,'category_id'=>6,'supplier_id'=>0,'title'=>'Shared climate-monitoring subscription','description'=>'Consolidate duplicate environmental-monitoring subscriptions into one enterprise agreement.','opportunity_type'=>'supplier_consolidation','category'=>'Contract consolidation','owner_id'=>4,'stage'=>'draft','operational_status'=>'identified','annualized_value'=>18600,'current_annual_cost'=>86000,'implementation_cost'=>1200,'realized_savings'=>0,'confidence'=>55,'risk'=>'low','due_date'=>'2026-09-10','accounting_validation'=>'not_requested','next_step'=>'Confirm active sites, device counts, and renewal dates.','review_status'=>'draft'],
        ['id'=>6,'opportunity_number'=>'SAV-2026-0006','company_id'=>6,'category_id'=>7,'supplier_id'=>5,'title'=>'Facility supply catalog standardization','description'=>'Move common property operating supplies to an approved item catalog and negotiated supplier matrix.','opportunity_type'=>'process','category'=>'Catalog compliance','owner_id'=>4,'stage'=>'validated','operational_status'=>'approved','annualized_value'=>31800,'current_annual_cost'=>265000,'implementation_cost'=>6800,'realized_savings'=>7200,'confidence'=>79,'risk'=>'medium','due_date'=>'2026-08-30','accounting_validation'=>'pending','next_step'=>'Approve the first 60 standard items and publish the buying guide.','review_status'=>'validated'],
        ['id'=>7,'opportunity_number'=>'SAV-2026-0007','company_id'=>2,'category_id'=>6,'supplier_id'=>6,'title'=>'Inbound freight consolidation','description'=>'Combine inbound orders by lane and delivery window to reduce fragmented freight charges.','opportunity_type'=>'freight','category'=>'Logistics','owner_id'=>3,'stage'=>'submitted','operational_status'=>'analyzing','annualized_value'=>28000,'current_annual_cost'=>144000,'implementation_cost'=>4500,'realized_savings'=>0,'confidence'=>76,'risk'=>'medium','due_date'=>'2026-08-12','accounting_validation'=>'not_requested','next_step'=>'Validate shipment history and carrier minimums with Accounting.','review_status'=>'submitted'],
    ];

    $scorecards = [
        ['id'=>1,'supplier_id'=>1,'company_id'=>2,'period'=>'2026 Q2','on_time_delivery'=>94.2,'quality'=>98.1,'responsiveness'=>96.0,'cost_competitiveness'=>92.0,'overall'=>95.1,'status'=>'approved'],
        ['id'=>2,'supplier_id'=>2,'company_id'=>3,'period'=>'2026 Q2','on_time_delivery'=>88.4,'quality'=>96.7,'responsiveness'=>90.0,'cost_competitiveness'=>89.0,'overall'=>91.0,'status'=>'validated'],
        ['id'=>3,'supplier_id'=>3,'company_id'=>1,'period'=>'2026 Q2','on_time_delivery'=>91.1,'quality'=>97.4,'responsiveness'=>93.0,'cost_competitiveness'=>94.0,'overall'=>93.9,'status'=>'submitted'],
        ['id'=>4,'supplier_id'=>4,'company_id'=>4,'period'=>'2026 Q2','on_time_delivery'=>72.6,'quality'=>92.8,'responsiveness'=>69.0,'cost_competitiveness'=>74.0,'overall'=>77.1,'status'=>'changes_requested'],
        ['id'=>5,'supplier_id'=>5,'company_id'=>6,'period'=>'2026 Q2','on_time_delivery'=>89.8,'quality'=>95.0,'responsiveness'=>88.0,'cost_competitiveness'=>90.0,'overall'=>90.7,'status'=>'approved'],
        ['id'=>6,'supplier_id'=>6,'company_id'=>2,'period'=>'2026 Q2','on_time_delivery'=>96.1,'quality'=>97.6,'responsiveness'=>95.0,'cost_competitiveness'=>91.0,'overall'=>94.9,'status'=>'validated'],
    ];

    $discoveryAssignments = [
        ['id'=>1,'company_id'=>1,'title'=>'Supplier master and duplicate review','owner_id'=>4,'reviewer_id'=>6,'due_date'=>'2026-08-01','status'=>'in_progress','completion'=>82,'priority'=>'high'],
        ['id'=>2,'company_id'=>2,'title'=>'Open purchase order baseline','owner_id'=>5,'reviewer_id'=>3,'due_date'=>'2026-07-31','status'=>'submitted','completion'=>100,'priority'=>'high'],
        ['id'=>3,'company_id'=>3,'title'=>'Inventory location and custody map','owner_id'=>5,'reviewer_id'=>6,'due_date'=>'2026-08-08','status'=>'in_progress','completion'=>64,'priority'=>'medium'],
        ['id'=>4,'company_id'=>4,'title'=>'Legacy component source inventory','owner_id'=>4,'reviewer_id'=>3,'due_date'=>'2026-08-12','status'=>'changes_requested','completion'=>78,'priority'=>'high'],
        ['id'=>5,'company_id'=>5,'title'=>'Storage maintenance purchasing profile','owner_id'=>5,'reviewer_id'=>6,'due_date'=>'2026-08-18','status'=>'not_started','completion'=>18,'priority'=>'medium'],
        ['id'=>6,'company_id'=>6,'title'=>'Property operating-supply baseline','owner_id'=>4,'reviewer_id'=>6,'due_date'=>'2026-08-20','status'=>'in_progress','completion'=>52,'priority'=>'medium'],
    ];

    $discoveryResponses = [
        ['id'=>1,'assignment_id'=>1,'company_id'=>1,'question'=>'Where is the supplier master maintained?','response'=>'Primary export from accounting plus local buyer spreadsheets.','status'=>'validated','owner_id'=>4],
        ['id'=>2,'assignment_id'=>1,'company_id'=>1,'question'=>'How are duplicate suppliers identified?','response'=>'Currently by manual legal-name and remit-address review.','status'=>'submitted','owner_id'=>4],
        ['id'=>3,'assignment_id'=>2,'company_id'=>2,'question'=>'Who owns past-due PO follow-up?','response'=>'Buyer initiates follow-up; procurement manager escalates after five business days.','status'=>'approved','owner_id'=>5],
        ['id'=>4,'assignment_id'=>3,'company_id'=>3,'question'=>'Are field truck inventories counted?','response'=>'Monthly for high-value parts; lower-value consumables are reviewed quarterly.','status'=>'draft','owner_id'=>5],
        ['id'=>5,'assignment_id'=>4,'company_id'=>4,'question'=>'Which legacy parts must be preserved?','response'=>'Motor controllers, battery electronics, drive-unit boards, and discontinued body components.','status'=>'changes_requested','owner_id'=>4],
        ['id'=>6,'assignment_id'=>6,'company_id'=>6,'question'=>'Are facility supplies centrally ordered?','response'=>'Partially. Major sites use approved catalogs; smaller properties purchase locally.','status'=>'submitted','owner_id'=>4],
    ];

    $exceptions = [
        ['id'=>1,'company_id'=>1,'module'=>'Suppliers','entity_type'=>'supplier','entity_id'=>3,'issue'=>'Possible duplicate legal entity','severity'=>'high','owner_id'=>4,'status'=>'open','created_at'=>$now->modify('-2 hours')->format('Y-m-d H:i:s')],
        ['id'=>2,'company_id'=>2,'module'=>'Purchase Orders','entity_type'=>'purchase_order','entity_id'=>1,'issue'=>'Expected date is past due without acknowledgement','severity'=>'critical','owner_id'=>3,'status'=>'open','created_at'=>$now->modify('-1 hour')->format('Y-m-d H:i:s')],
        ['id'=>3,'company_id'=>4,'module'=>'Items','entity_type'=>'item','entity_id'=>4,'issue'=>'Unit cost source requires validation','severity'=>'medium','owner_id'=>5,'status'=>'in_review','created_at'=>$now->modify('-1 day')->format('Y-m-d H:i:s')],
        ['id'=>4,'company_id'=>6,'module'=>'Inventory','entity_type'=>'inventory_snapshot','entity_id'=>8,'issue'=>'Snapshot has no cycle-count reference','severity'=>'medium','owner_id'=>4,'status'=>'open','created_at'=>$now->modify('-3 days')->format('Y-m-d H:i:s')],
        ['id'=>5,'company_id'=>5,'module'=>'Discovery','entity_type'=>'discovery_assignment','entity_id'=>5,'issue'=>'Source-system owner not assigned','severity'=>'low','owner_id'=>5,'status'=>'open','created_at'=>$now->modify('-4 days')->format('Y-m-d H:i:s')],
    ];

    $notifications = [
        ['id'=>1,'user_id'=>1,'company_id'=>2,'title'=>'Battery order is nine days late','message'=>'PO GPS-10428 may affect two service commitments.','severity'=>'critical','module'=>'purchase_orders','entity_id'=>1,'read'=>false,'created_at'=>$now->modify('-5 minutes')->format('Y-m-d H:i:s')],
        ['id'=>2,'user_id'=>1,'company_id'=>1,'title'=>'Internal cable transfer identified','message'=>'Available GC stock may prevent a duplicate GTS purchase.','severity'=>'warning','module'=>'inventory','entity_id'=>2,'read'=>false,'created_at'=>$now->modify('-18 minutes')->format('Y-m-d H:i:s')],
        ['id'=>3,'user_id'=>1,'company_id'=>1,'title'=>'Supplier duplicate requires review','message'=>'Three supplier aliases share a legal address and domain.','severity'=>'warning','module'=>'suppliers','entity_id'=>3,'read'=>false,'created_at'=>$now->modify('-1 hour')->format('Y-m-d H:i:s')],
        ['id'=>4,'user_id'=>1,'company_id'=>2,'title'=>'Savings opportunity submitted','message'=>'Inbound freight consolidation is ready for validation.','severity'=>'info','module'=>'savings','entity_id'=>7,'read'=>false,'created_at'=>$now->modify('-3 hours')->format('Y-m-d H:i:s')],
        ['id'=>5,'user_id'=>3,'company_id'=>4,'title'=>'Changes requested on EV component source','message'=>'Reviewer requested cost-source evidence.','severity'=>'warning','module'=>'items','entity_id'=>4,'read'=>false,'created_at'=>$now->modify('-1 day')->format('Y-m-d H:i:s')],
        ['id'=>6,'user_id'=>6,'company_id'=>6,'title'=>'Property baseline submitted','message'=>'Review assignment is available.','severity'=>'info','module'=>'discovery','entity_id'=>6,'read'=>true,'created_at'=>$now->modify('-2 days')->format('Y-m-d H:i:s')],
    ];

    $approvals = [
        ['id'=>1,'company_id'=>2,'module'=>'purchase_orders','entity_type'=>'purchase_order','entity_id'=>1,'title'=>'Past-due PO escalation plan','submitted_by'=>3,'assigned_to'=>6,'status'=>'pending','submitted_at'=>$now->modify('-2 hours')->format('Y-m-d H:i:s'),'due_date'=>'2026-07-26','notes'=>'Validate customer-impact estimate and supplier escalation evidence.'],
        ['id'=>2,'company_id'=>1,'module'=>'suppliers','entity_type'=>'supplier','entity_id'=>3,'title'=>'Southwest Fiber supplier master','submitted_by'=>5,'assigned_to'=>6,'status'=>'pending','submitted_at'=>$now->modify('-1 day')->format('Y-m-d H:i:s'),'due_date'=>'2026-07-28','notes'=>'Review tax profile and duplicate-search evidence.'],
        ['id'=>3,'company_id'=>3,'module'=>'discovery','entity_type'=>'discovery_assignment','entity_id'=>2,'title'=>'Open purchase order baseline','submitted_by'=>5,'assigned_to'=>3,'status'=>'approved','submitted_at'=>$now->modify('-2 days')->format('Y-m-d H:i:s'),'due_date'=>'2026-07-27','notes'=>'Approved with one follow-up task.'],
        ['id'=>4,'company_id'=>2,'module'=>'savings','entity_type'=>'savings_opportunity','entity_id'=>7,'title'=>'Inbound freight consolidation','submitted_by'=>3,'assigned_to'=>6,'status'=>'pending','submitted_at'=>$now->modify('-3 hours')->format('Y-m-d H:i:s'),'due_date'=>'2026-07-29','notes'=>'Validate annualized baseline and implementation costs.'],
        ['id'=>5,'company_id'=>4,'module'=>'items','entity_type'=>'item','entity_id'=>4,'title'=>'Legacy motor controller cost basis','submitted_by'=>4,'assigned_to'=>6,'status'=>'changes_requested','submitted_at'=>$now->modify('-3 days')->format('Y-m-d H:i:s'),'due_date'=>'2026-07-30','notes'=>'Attach supplier quote and repair alternative.'],
    ];

    $comments = [
        ['id'=>1,'company_id'=>2,'entity_type'=>'purchase_order','entity_id'=>1,'user_id'=>3,'body'=>'Supplier escalation call scheduled for 10:30 AM.','created_at'=>$now->modify('-50 minutes')->format('Y-m-d H:i:s')],
        ['id'=>2,'company_id'=>2,'entity_type'=>'purchase_order','entity_id'=>1,'user_id'=>6,'body'=>'Please attach the customer commitment list before approval.','created_at'=>$now->modify('-35 minutes')->format('Y-m-d H:i:s')],
        ['id'=>3,'company_id'=>4,'entity_type'=>'item','entity_id'=>4,'user_id'=>6,'body'=>'Cost source is older than twelve months and needs refresh.','created_at'=>$now->modify('-1 day')->format('Y-m-d H:i:s')],
        ['id'=>4,'company_id'=>1,'entity_type'=>'supplier','entity_id'=>3,'user_id'=>5,'body'=>'Legal-name verification is complete; remit address is pending.','created_at'=>$now->modify('-2 days')->format('Y-m-d H:i:s')],
        ['id'=>5,'company_id'=>6,'entity_type'=>'discovery_assignment','entity_id'=>6,'user_id'=>4,'body'=>'Two property managers have returned source-system questionnaires.','created_at'=>$now->modify('-3 days')->format('Y-m-d H:i:s')],
    ];

    $importJobs = [
        ['id'=>1,'company_id'=>1,'name'=>'GC Supplier Master July 2026','file_name'=>'gc_supplier_master_202607.csv','type'=>'suppliers','status'=>'validation_errors','submitted_by'=>5,'rows_total'=>428,'rows_valid'=>419,'rows_error'=>9,'created_at'=>$now->modify('-2 hours')->format('Y-m-d H:i:s'),'mapping'=>['Supplier Name'=>'name','Vendor Number'=>'supplier_number','Terms'=>'payment_terms']],
        ['id'=>2,'company_id'=>2,'name'=>'GPS Open PO Baseline','file_name'=>'gps_open_po_202607.xlsx','type'=>'purchase_orders','status'=>'ready_to_commit','submitted_by'=>5,'rows_total'=>186,'rows_valid'=>186,'rows_error'=>0,'created_at'=>$now->modify('-1 day')->format('Y-m-d H:i:s'),'mapping'=>['PO Number'=>'po_number','Vendor'=>'supplier','Required Date'=>'required_date','Amount'=>'total_amount']],
        ['id'=>3,'company_id'=>3,'name'=>'GTS Inventory Snapshot','file_name'=>'gts_inventory_snapshot.csv','type'=>'inventory','status'=>'committed','submitted_by'=>5,'rows_total'=>1264,'rows_valid'=>1259,'rows_error'=>5,'created_at'=>$now->modify('-3 days')->format('Y-m-d H:i:s'),'mapping'=>['Part'=>'item_number','Location'=>'location','On Hand'=>'quantity_on_hand']],
        ['id'=>4,'company_id'=>4,'name'=>'GMC Legacy Items','file_name'=>'gmc_legacy_items.xlsx','type'=>'items','status'=>'mapping','submitted_by'=>4,'rows_total'=>614,'rows_valid'=>0,'rows_error'=>0,'created_at'=>$now->modify('-4 hours')->format('Y-m-d H:i:s'),'mapping'=>[]],
    ];

    $importErrors = [
        ['id'=>1,'import_job_id'=>1,'row_number'=>47,'field'=>'supplier_number','value'=>'','message'=>'Supplier number is required.','status'=>'open'],
        ['id'=>2,'import_job_id'=>1,'row_number'=>112,'field'=>'email','value'=>'purchasing@','message'=>'Contact email is not valid.','status'=>'open'],
        ['id'=>3,'import_job_id'=>1,'row_number'=>187,'field'=>'name','value'=>'Desert Electrical Dist.','message'=>'Possible duplicate of SUP-1002.','status'=>'review'],
        ['id'=>4,'import_job_id'=>3,'row_number'=>219,'field'=>'quantity_on_hand','value'=>'-4','message'=>'Negative on-hand quantity requires review.','status'=>'accepted'],
        ['id'=>5,'import_job_id'=>3,'row_number'=>741,'field'=>'item_number','value'=>'CAB-UNKNOWN','message'=>'Item does not exist in the approved item master.','status'=>'open'],
    ];

    $importReceipts = [
        ['id'=>1,'import_job_id'=>3,'receipt_number'=>'IMP-20260722-003','committed_by'=>3,'committed_at'=>$now->modify('-3 days')->format('Y-m-d H:i:s'),'records_created'=>18,'records_updated'=>1241,'records_skipped'=>5,'hash'=>substr(hash('sha256','demo-import-3'),0,24)],
        ['id'=>2,'import_job_id'=>2,'receipt_number'=>'SIM-PENDING-002','committed_by'=>null,'committed_at'=>null,'records_created'=>0,'records_updated'=>0,'records_skipped'=>0,'hash'=>null],
    ];

    $sessions = [
        ['id'=>1,'user_id'=>1,'ip_address'=>'192.0.2.11','device'=>'Chrome on Windows','started_at'=>$now->modify('-3 hours')->format('Y-m-d H:i:s'),'last_activity'=>$stamp,'expires_at'=>$now->modify('+2 hours')->format('Y-m-d H:i:s'),'current'=>true,'status'=>'active'],
        ['id'=>2,'user_id'=>2,'ip_address'=>'192.0.2.24','device'=>'Safari on macOS','started_at'=>$now->modify('-4 hours')->format('Y-m-d H:i:s'),'last_activity'=>$now->modify('-2 hours')->format('Y-m-d H:i:s'),'expires_at'=>$now->modify('+35 minutes')->format('Y-m-d H:i:s'),'current'=>false,'status'=>'active'],
        ['id'=>3,'user_id'=>3,'ip_address'=>'198.51.100.8','device'=>'Edge on Windows','started_at'=>$now->modify('-2 hours')->format('Y-m-d H:i:s'),'last_activity'=>$now->modify('-45 minutes')->format('Y-m-d H:i:s'),'expires_at'=>$now->modify('+1 hour')->format('Y-m-d H:i:s'),'current'=>false,'status'=>'active'],
        ['id'=>4,'user_id'=>5,'ip_address'=>'198.51.100.44','device'=>'Chrome on Android','started_at'=>$now->modify('-5 hours')->format('Y-m-d H:i:s'),'last_activity'=>$now->modify('-3 hours')->format('Y-m-d H:i:s'),'expires_at'=>$now->modify('-1 hour')->format('Y-m-d H:i:s'),'current'=>false,'status'=>'expired'],
        ['id'=>5,'user_id'=>6,'ip_address'=>'203.0.113.17','device'=>'Chrome on macOS','started_at'=>$now->modify('-1 day')->format('Y-m-d H:i:s'),'last_activity'=>$now->modify('-4 hours')->format('Y-m-d H:i:s'),'expires_at'=>$now->modify('+20 minutes')->format('Y-m-d H:i:s'),'current'=>false,'status'=>'active'],
    ];

    $accessRequests = [
        ['id'=>1,'first_name'=>'Drew','last_name'=>'Foster','email'=>'drew.foster@example.test','job_title'=>'Field Operations Coordinator','company_id'=>3,'requested_role'=>'data_contributor','status'=>'pending','submitted_at'=>$now->modify('-2 hours')->format('Y-m-d H:i:s'),'review_note'=>''],
        ['id'=>2,'first_name'=>'Alex','last_name'=>'Kim','email'=>'alex.kim@example.test','job_title'=>'Property Accounting Analyst','company_id'=>6,'requested_role'=>'reviewer','status'=>'pending','submitted_at'=>$now->modify('-1 day')->format('Y-m-d H:i:s'),'review_note'=>'Manager confirmation attached in source request.'],
        ['id'=>3,'first_name'=>'Quinn','last_name'=>'Murphy','email'=>'quinn.murphy@example.test','job_title'=>'Vendor Analyst','company_id'=>2,'requested_role'=>'read_only','status'=>'declined','submitted_at'=>$now->modify('-5 days')->format('Y-m-d H:i:s'),'review_note'=>'Duplicate request; existing account was restored.'],
    ];

    $securityEvents = [
        ['id'=>1,'user_id'=>8,'event'=>'account_suspended','severity'=>'warning','ip_address'=>'198.51.100.31','details'=>'Administrator suspended user after contract end.','created_at'=>$now->modify('-6 days')->format('Y-m-d H:i:s')],
        ['id'=>2,'user_id'=>null,'event'=>'failed_sign_in','severity'=>'warning','ip_address'=>'203.0.113.71','details'=>'Three failed sign-in attempts for an unknown email.','created_at'=>$now->modify('-4 hours')->format('Y-m-d H:i:s')],
        ['id'=>3,'user_id'=>2,'event'=>'password_reset_requested','severity'=>'info','ip_address'=>'192.0.2.24','details'=>'Administrator required a password change at next sign-in; no password was emailed.','created_at'=>$now->modify('-2 days')->format('Y-m-d H:i:s')],
        ['id'=>4,'user_id'=>1,'event'=>'role_assignment_changed','severity'=>'info','ip_address'=>'192.0.2.11','details'=>'Company Administrator scope updated.','created_at'=>$now->modify('-3 days')->format('Y-m-d H:i:s')],
        ['id'=>5,'user_id'=>3,'event'=>'new_device_sign_in','severity'=>'info','ip_address'=>'198.51.100.8','details'=>'Successful sign-in from a new Windows device.','created_at'=>$now->modify('-8 hours')->format('Y-m-d H:i:s')],
        ['id'=>6,'user_id'=>null,'event'=>'failed_sign_in','severity'=>'critical','ip_address'=>'203.0.113.99','details'=>'Login throttling activated after five failed attempts.','created_at'=>$now->modify('-7 days')->format('Y-m-d H:i:s')],
        ['id'=>7,'user_id'=>6,'event'=>'session_revoked','severity'=>'info','ip_address'=>'192.0.2.11','details'=>'Administrator revoked a stale reviewer session.','created_at'=>$now->modify('-9 days')->format('Y-m-d H:i:s')],
        ['id'=>8,'user_id'=>1,'event'=>'settings_updated','severity'=>'info','ip_address'=>'192.0.2.11','details'=>'Password reset lifetime changed from 45 to 60 minutes.','created_at'=>$now->modify('-10 days')->format('Y-m-d H:i:s')],
    ];

    $auditEvents = [
        ['id'=>1,'user_id'=>1,'company_id'=>null,'module'=>'Admin','action'=>'demo_dataset_initialized','entity_type'=>'environment','entity_id'=>null,'before'=>null,'after'=>['version'=>'2.0-demo-admin'],'ip_address'=>'127.0.0.1','created_at'=>$stamp,'immutable'=>true],
        ['id'=>2,'user_id'=>3,'company_id'=>2,'module'=>'Purchase Orders','action'=>'submitted_for_review','entity_type'=>'purchase_order','entity_id'=>1,'before'=>['review_status'=>'draft'],'after'=>['review_status'=>'submitted'],'ip_address'=>'198.51.100.8','created_at'=>$now->modify('-2 hours')->format('Y-m-d H:i:s'),'immutable'=>true],
        ['id'=>3,'user_id'=>6,'company_id'=>4,'module'=>'Items','action'=>'requested_changes','entity_type'=>'item','entity_id'=>4,'before'=>['review_status'=>'submitted'],'after'=>['review_status'=>'changes_requested'],'ip_address'=>'203.0.113.17','created_at'=>$now->modify('-1 day')->format('Y-m-d H:i:s'),'immutable'=>true],
        ['id'=>4,'user_id'=>5,'company_id'=>1,'module'=>'Suppliers','action'=>'updated','entity_type'=>'supplier','entity_id'=>3,'before'=>['payment_terms'=>'Net 15'],'after'=>['payment_terms'=>'Net 30'],'ip_address'=>'198.51.100.44','created_at'=>$now->modify('-2 days')->format('Y-m-d H:i:s'),'immutable'=>true],
        ['id'=>5,'user_id'=>3,'company_id'=>3,'module'=>'Imports','action'=>'committed','entity_type'=>'import_job','entity_id'=>3,'before'=>['status'=>'ready_to_commit'],'after'=>['status'=>'committed','records_updated'=>1241],'ip_address'=>'198.51.100.8','created_at'=>$now->modify('-3 days')->format('Y-m-d H:i:s'),'immutable'=>true],
        ['id'=>6,'user_id'=>1,'company_id'=>null,'module'=>'Admin','action'=>'suspended','entity_type'=>'user','entity_id'=>8,'before'=>['status'=>'active'],'after'=>['status'=>'suspended'],'ip_address'=>'192.0.2.11','created_at'=>$now->modify('-6 days')->format('Y-m-d H:i:s'),'immutable'=>true],
        ['id'=>7,'user_id'=>4,'company_id'=>6,'module'=>'Discovery','action'=>'assigned','entity_type'=>'discovery_assignment','entity_id'=>6,'before'=>['owner_id'=>null],'after'=>['owner_id'=>4],'ip_address'=>'192.0.2.55','created_at'=>$now->modify('-7 days')->format('Y-m-d H:i:s'),'immutable'=>true],
        ['id'=>8,'user_id'=>6,'company_id'=>2,'module'=>'Savings','action'=>'validated','entity_type'=>'savings_opportunity','entity_id'=>2,'before'=>['stage'=>'submitted'],'after'=>['stage'=>'validated'],'ip_address'=>'203.0.113.17','created_at'=>$now->modify('-8 days')->format('Y-m-d H:i:s'),'immutable'=>true],
        ['id'=>9,'user_id'=>1,'company_id'=>null,'module'=>'Admin','action'=>'role_scope_updated','entity_type'=>'user','entity_id'=>4,'before'=>['company_ids'=>[1,4]],'after'=>['company_ids'=>[1,4,6]],'ip_address'=>'192.0.2.11','created_at'=>$now->modify('-9 days')->format('Y-m-d H:i:s'),'immutable'=>true],
        ['id'=>10,'user_id'=>5,'company_id'=>5,'module'=>'Inventory','action'=>'snapshot_created','entity_type'=>'inventory_snapshot','entity_id'=>7,'before'=>null,'after'=>['quantity_on_hand'=>21],'ip_address'=>'198.51.100.44','created_at'=>$now->modify('-10 days')->format('Y-m-d H:i:s'),'immutable'=>true],
        ['id'=>11,'user_id'=>3,'company_id'=>1,'module'=>'Contracts','action'=>'renewal_review_started','entity_type'=>'contract','entity_id'=>2,'before'=>['status'=>'active'],'after'=>['status'=>'expiring'],'ip_address'=>'198.51.100.8','created_at'=>$now->modify('-11 days')->format('Y-m-d H:i:s'),'immutable'=>true],
        ['id'=>12,'user_id'=>1,'company_id'=>null,'module'=>'Admin','action'=>'security_policy_updated','entity_type'=>'setting','entity_id'=>5,'before'=>['value'=>45],'after'=>['value'=>60],'ip_address'=>'192.0.2.11','created_at'=>$now->modify('-12 days')->format('Y-m-d H:i:s'),'immutable'=>true],
    ];

    $settings = [
        'platform_name' => 'Gruber Procurement Intelligence',
        'support_contact' => 'procurement-support@example.test',
        'default_timezone' => 'America/Phoenix',
        'date_format' => 'M j, Y',
        'currency' => 'USD',
        'email_sender' => 'no-reply@example.test',
        'session_lifetime' => 120,
        'password_reset_lifetime' => 60,
        'import_row_limit' => 25000,
        'upload_limit_mb' => 25,
        'allowed_file_types' => ['csv','xlsx'],
        'notification_defaults' => ['email'=>true,'in_app'=>true],
        'approval_requirements' => ['savings_over'=>25000,'po_over'=>100000],
        'data_retention_days' => 2555,
        'demo_mode_available' => true,
        'maintenance_mode' => false,
        'agent_workspace_access' => true,
        'approved_data_only_agent' => true,
        'password_min_length' => 12,
        'password_require_mixed_case' => true,
        'password_require_number' => true,
        'password_require_symbol' => true,
        'login_attempt_limit' => 5,
        'lockout_minutes' => 15,
    ];

    return [
        'meta' => [
            'version' => '2.0-demo-admin',
            'seeded_at' => $stamp,
            'last_reset_at' => $stamp,
            'sample_notice' => 'All records and figures in Demo Mode are fictional sample data for workflow review.',
        ],
        'companies' => $companies,
        'roles' => $roles,
        'users' => $users,
        'categories' => $categories,
        'discovery_assignments' => $discoveryAssignments,
        'discovery_responses' => $discoveryResponses,
        'suppliers' => $suppliers,
        'supplier_contacts' => $supplierContacts,
        'supplier_company_relationships' => $supplierRelationships,
        'contracts' => $contracts,
        'items' => $items,
        'purchase_orders' => $purchaseOrders,
        'purchase_order_lines' => $purchaseOrderLines,
        'open_commitments' => $openCommitments,
        'inventory_locations' => $inventoryLocations,
        'inventory_snapshots' => $inventorySnapshots,
        'inventory_aging' => $inventoryAging,
        'savings_opportunities' => $savings,
        'supplier_scorecards' => $scorecards,
        'data_quality_exceptions' => $exceptions,
        'notifications' => $notifications,
        'workflow_approvals' => $approvals,
        'comments' => $comments,
        'audit_events' => $auditEvents,
        'import_jobs' => $importJobs,
        'import_validation_errors' => $importErrors,
        'import_receipts' => $importReceipts,
        'sessions' => $sessions,
        'access_requests' => $accessRequests,
        'security_events' => $securityEvents,
        'settings' => $settings,
    ];
}
