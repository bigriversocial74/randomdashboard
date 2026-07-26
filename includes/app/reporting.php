<?php
declare(strict_types=1);

/**
 * Build permission-scoped operational reports from the same repository data
 * used by the dashboard modules. Values are intentionally rendered as plain
 * strings so the same dataset can be displayed in HTML or streamed as CSV.
 */
function gruber_report_definitions(): array
{
    return [
        'executive-summary' => [
            'title' => 'Executive procurement summary',
            'description' => 'Spend, commitments, supplier risk, inventory, savings, approvals and data-quality status.',
            'module' => 'Enterprise',
        ],
        'open-commitments' => [
            'title' => 'Open commitments and delivery risk',
            'description' => 'Open, partial and past-due purchase orders with supplier and company ownership.',
            'module' => 'Purchase Orders',
        ],
        'inventory-aging' => [
            'title' => 'Inventory aging and transfer opportunities',
            'description' => 'Inventory value, available stock, usage age and internal transfer candidates.',
            'module' => 'Inventory',
        ],
        'savings-validation' => [
            'title' => 'Savings pipeline validation',
            'description' => 'Annualized value, weighted forecast, realized value, finance status and next steps.',
            'module' => 'Savings',
        ],
        'supplier-performance' => [
            'title' => 'Supplier performance portfolio',
            'description' => 'Scorecard results, risk, spend, delivery and corrective-action indicators.',
            'module' => 'Suppliers',
        ],
        'data-quality' => [
            'title' => 'Data completeness and exception report',
            'description' => 'Discovery progress, unresolved exceptions, import validation and pending approvals.',
            'module' => 'Data Governance',
        ],
    ];
}

function gruber_report_dataset(string $key): array
{
    $definitions = gruber_report_definitions();
    if (!isset($definitions[$key])) {
        $key = 'executive-summary';
    }
    $definition = $definitions[$key];
    $columns = [];
    $rows = [];

    switch ($key) {
        case 'open-commitments':
            $columns = ['PO', 'Company', 'Supplier', 'Buyer', 'Status', 'Order date', 'Required date', 'Expected date', 'Amount'];
            $records = array_values(array_filter(
                data_visible_collection('purchase_orders'),
                static fn(array $row): bool => in_array((string)($row['status'] ?? ''), ['open','partially_received','past_due'], true)
            ));
            usort($records, static fn(array $a, array $b): int => strcmp((string)($a['expected_date'] ?? ''), (string)($b['expected_date'] ?? '')));
            foreach ($records as $row) {
                $rows[] = [
                    $row['po_number'] ?? '',
                    data_company_name($row['company_id'] ?? null),
                    data_supplier_name($row['supplier_id'] ?? null),
                    data_user_name($row['buyer_id'] ?? null),
                    status_label((string)($row['status'] ?? '')),
                    date_us((string)($row['order_date'] ?? '')),
                    date_us((string)($row['required_date'] ?? '')),
                    date_us((string)($row['expected_date'] ?? '')),
                    money((float)($row['total_amount'] ?? 0)),
                ];
            }
            break;

        case 'inventory-aging':
            $columns = ['Company', 'Location', 'Item', 'Description', 'Available', 'Inventory value', 'Aging class', 'Days since use'];
            $agingByItem = [];
            foreach (data_visible_collection('inventory_aging') as $aging) {
                $agingByItem[(int)($aging['item_id'] ?? 0) . ':' . (int)($aging['company_id'] ?? 0)] = $aging;
            }
            foreach (data_visible_collection('inventory_snapshots') as $row) {
                $item = data_find('items', (int)($row['item_id'] ?? 0)) ?? [];
                $location = data_find('inventory_locations', (int)($row['location_id'] ?? 0)) ?? [];
                $aging = $agingByItem[(int)($row['item_id'] ?? 0) . ':' . (int)($row['company_id'] ?? 0)] ?? [];
                $rows[] = [
                    data_company_name($row['company_id'] ?? null),
                    $location['name'] ?? 'Unknown location',
                    $item['item_number'] ?? ('Item #' . (int)($row['item_id'] ?? 0)),
                    $item['description'] ?? '',
                    number_format((float)($row['available'] ?? 0), 2),
                    money((float)($row['value'] ?? 0)),
                    status_label((string)($aging['classification'] ?? 'not_classified')),
                    isset($aging['days_since_use']) ? number_format((int)$aging['days_since_use']) : 'Not available',
                ];
            }
            break;

        case 'savings-validation':
            $columns = ['Opportunity', 'Company', 'Supplier', 'Stage', 'Risk', 'Gross annual value', 'Confidence', 'Weighted forecast', 'Realized savings', 'Accounting validation', 'Owner', 'Target date', 'Next step'];
            foreach (sort_records(data_visible_collection('savings_opportunities'), 'annualized_value', 'desc') as $row) {
                $gross = (float)($row['annualized_value'] ?? 0);
                $confidence = (int)($row['confidence'] ?? 0);
                $rows[] = [
                    ($row['opportunity_number'] ?? '') . ' · ' . ($row['title'] ?? ''),
                    data_company_name($row['company_id'] ?? null),
                    data_supplier_name($row['supplier_id'] ?? null),
                    status_label((string)($row['stage'] ?? '')),
                    status_label((string)($row['risk'] ?? '')),
                    money($gross),
                    $confidence . '%',
                    money($gross * ($confidence / 100)),
                    money((float)($row['realized_savings'] ?? 0)),
                    status_label((string)($row['accounting_validation'] ?? 'not_requested')),
                    data_user_name($row['owner_id'] ?? null),
                    date_us((string)($row['due_date'] ?? '')),
                    $row['next_step'] ?? '',
                ];
            }
            break;

        case 'supplier-performance':
            $columns = ['Supplier', 'Company', 'Period', 'Overall', 'On-time delivery', 'Quality', 'Responsiveness', 'Cost competitiveness', 'Supplier risk', 'Annual spend', 'Review status'];
            foreach (sort_records(data_visible_collection('supplier_scorecards'), 'overall', 'desc') as $row) {
                $supplier = data_find('suppliers', (int)($row['supplier_id'] ?? 0)) ?? [];
                $rows[] = [
                    $supplier['name'] ?? data_supplier_name($row['supplier_id'] ?? null),
                    data_company_name($row['company_id'] ?? null),
                    $row['period'] ?? '',
                    number_format((float)($row['overall'] ?? 0), 1) . '%',
                    number_format((float)($row['on_time_delivery'] ?? 0), 1) . '%',
                    number_format((float)($row['quality'] ?? 0), 1) . '%',
                    number_format((float)($row['responsiveness'] ?? 0), 1) . '%',
                    number_format((float)($row['cost_competitiveness'] ?? 0), 1) . '%',
                    status_label((string)($supplier['risk'] ?? 'not_set')),
                    money((float)($supplier['annual_spend'] ?? 0)),
                    status_label((string)($row['status'] ?? '')),
                ];
            }
            break;

        case 'data-quality':
            $columns = ['Type', 'Company', 'Record', 'Severity / priority', 'Status', 'Owner / reviewer', 'Due / created', 'Detail'];
            foreach (data_visible_collection('data_quality_exceptions') as $row) {
                $rows[] = [
                    'Data-quality exception',
                    data_company_name($row['company_id'] ?? null),
                    $row['module'] ?? '',
                    status_label((string)($row['severity'] ?? '')),
                    status_label((string)($row['status'] ?? '')),
                    data_user_name($row['owner_id'] ?? null),
                    isset($row['created_at']) ? date_us((string)$row['created_at'], true) : 'Not available',
                    $row['issue'] ?? '',
                ];
            }
            foreach (data_visible_collection('workflow_approvals') as $row) {
                if (($row['status'] ?? '') !== 'pending') continue;
                $rows[] = [
                    'Pending approval',
                    data_company_name($row['company_id'] ?? null),
                    $row['title'] ?? '',
                    'Review',
                    status_label((string)($row['status'] ?? '')),
                    data_user_name($row['assigned_to'] ?? null),
                    date_us((string)($row['due_date'] ?? '')),
                    $row['notes'] ?? '',
                ];
            }
            foreach (data_visible_collection('discovery_assignments') as $row) {
                if ((int)($row['completion'] ?? 0) >= 100) continue;
                $rows[] = [
                    'Discovery assignment',
                    data_company_name($row['company_id'] ?? null),
                    $row['title'] ?? '',
                    status_label((string)($row['priority'] ?? '')),
                    status_label((string)($row['status'] ?? '')),
                    data_user_name($row['owner_id'] ?? null),
                    date_us((string)($row['due_date'] ?? '')),
                    number_format((int)($row['completion'] ?? 0)) . '% complete',
                ];
            }
            break;

        case 'executive-summary':
        default:
            $columns = ['Metric', 'Value', 'Interpretation'];
            $metrics = data_dashboard_metrics();
            $suppliers = data_visible_collection('suppliers');
            $pendingApprovals = array_filter(data_visible_collection('workflow_approvals'), static fn(array $row): bool => ($row['status'] ?? '') === 'pending');
            $exceptions = array_filter(data_visible_collection('data_quality_exceptions'), static fn(array $row): bool => !in_array(($row['status'] ?? ''), ['resolved','closed'], true));
            $rows = [
                ['Visible purchasing spend', money((float)($metrics['spend'] ?? 0)), 'Purchase-order value in the selected company scope.'],
                ['Open commitments', money((float)($metrics['open_commitments'] ?? 0)), 'Open, partially received and past-due commitments.'],
                ['Inventory value', money((float)($metrics['inventory_value'] ?? 0)), 'Latest visible inventory snapshots.'],
                ['Savings pipeline', money((float)($metrics['savings_pipeline'] ?? 0)), 'Gross annualized opportunity value before confidence weighting.'],
                ['High-risk suppliers', (string)count(array_filter($suppliers, static fn(array $row): bool => in_array(($row['risk'] ?? ''), ['high','critical'], true))), 'Supplier records requiring active mitigation.'],
                ['Pending approvals', (string)count($pendingApprovals), 'Human decisions awaiting assigned reviewers.'],
                ['Open data exceptions', (string)count($exceptions), 'Unresolved issues affecting data confidence.'],
            ];
            break;
    }

    return [
        'key' => $key,
        'title' => $definition['title'],
        'description' => $definition['description'],
        'module' => $definition['module'],
        'columns' => $columns,
        'rows' => $rows,
        'generated_at' => date('Y-m-d H:i:s'),
        'scope' => current_scope_label(),
    ];
}
