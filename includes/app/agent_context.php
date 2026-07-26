<?php
declare(strict_types=1);

/**
 * Build the supervised Agent Workspace from the same scoped repository data
 * used by the application pages. Demo Mode therefore answers from the active
 * fictional dataset, while production answers from the configured MySQL data.
 */

function gruber_agent_escape(mixed $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function gruber_agent_status(mixed $value): string
{
    return gruber_agent_escape(status_label((string) $value));
}

function gruber_agent_date(mixed $value): string
{
    if (!$value) {
        return 'Not set';
    }
    return gruber_agent_escape(date_us((string) $value));
}

function gruber_agent_collection_map(array $records): array
{
    $map = [];
    foreach ($records as $record) {
        if (isset($record['id'])) {
            $map[(int) $record['id']] = $record;
        }
    }
    return $map;
}

function gruber_agent_company_records(array $records, int $companyId): array
{
    return array_values(array_filter($records, static function (array $record) use ($companyId): bool {
        if (isset($record['company_id']) && $record['company_id'] !== null && $record['company_id'] !== '') {
            return (int) $record['company_id'] === $companyId;
        }
        if (isset($record['company_ids']) && is_array($record['company_ids'])) {
            return in_array($companyId, array_map('intval', $record['company_ids']), true);
        }
        return false;
    }));
}

function gruber_agent_record_link(string $path, string $label): string
{
    return '<a class="agent-inline-link" href="' . gruber_agent_escape(app_url($path)) . '">' . gruber_agent_escape($label) . ' →</a>';
}

function gruber_agent_build_transfer_candidates(
    array $purchaseOrders,
    array $purchaseOrderLines,
    array $inventory,
    array $items,
    array $companies
): array {
    $poMap = gruber_agent_collection_map($purchaseOrders);
    $itemMap = gruber_agent_collection_map($items);
    $companyMap = gruber_agent_collection_map($companies);
    $openStatuses = ['open', 'past_due', 'partially_received'];
    $candidates = [];

    foreach ($purchaseOrderLines as $line) {
        $poId = (int) ($line['purchase_order_id'] ?? 0);
        $po = $poMap[$poId] ?? null;
        if (!$po || !in_array((string) ($po['status'] ?? ''), $openStatuses, true)) {
            continue;
        }

        $itemId = (int) ($line['item_id'] ?? 0);
        if ($itemId <= 0) {
            continue;
        }
        $requestedCompanyId = (int) ($po['company_id'] ?? 0);
        $ordered = (float) ($line['quantity_ordered'] ?? $line['quantity'] ?? 0);
        $unitCost = (float) ($line['unit_cost'] ?? 0);

        foreach ($inventory as $snapshot) {
            if ((int) ($snapshot['item_id'] ?? 0) !== $itemId) {
                continue;
            }
            $sourceCompanyId = (int) ($snapshot['company_id'] ?? 0);
            if ($sourceCompanyId <= 0 || $sourceCompanyId === $requestedCompanyId) {
                continue;
            }
            $available = (float) ($snapshot['available']
                ?? ((float) ($snapshot['quantity_on_hand'] ?? $snapshot['quantity'] ?? 0) - (float) ($snapshot['allocated'] ?? 0)));
            if ($available <= 0) {
                continue;
            }

            $candidateQuantity = min($available, max(0, $ordered));
            if ($candidateQuantity <= 0) {
                continue;
            }
            $item = $itemMap[$itemId] ?? [];
            $candidates[] = [
                'po_number' => (string) ($po['po_number'] ?? ('PO #' . $poId)),
                'item_number' => (string) ($item['item_number'] ?? ('Item #' . $itemId)),
                'description' => (string) ($item['description'] ?? $line['description'] ?? 'Inventory item'),
                'source_company_id' => $sourceCompanyId,
                'destination_company_id' => $requestedCompanyId,
                'item_id' => $itemId,
                'source_company' => (string) ($companyMap[$sourceCompanyId]['name'] ?? data_company_name($sourceCompanyId)),
                'destination_company' => (string) ($companyMap[$requestedCompanyId]['name'] ?? data_company_name($requestedCompanyId)),
                'available' => $available,
                'candidate_quantity' => $candidateQuantity,
                'uom' => (string) ($item['uom'] ?? 'units'),
                'estimated_value' => $candidateQuantity * $unitCost,
            ];
        }
    }

    usort($candidates, static fn(array $a, array $b): int => $b['estimated_value'] <=> $a['estimated_value']);
    return array_slice($candidates, 0, 8);
}

function gruber_agent_build_workspace(): array
{
    $user = current_user() ?? [];
    $companies = data_permitted_companies($user);
    $selectedCompany = current_company_id();
    if ($selectedCompany !== 'enterprise') {
        $selectedId = (int) $selectedCompany;
        $companies = array_values(array_filter($companies, static fn(array $company): bool => (int) $company['id'] === $selectedId));
    }

    $purchaseOrders = data_visible_collection('purchase_orders', $user);
    $visiblePoIds = array_fill_keys(array_map(static fn(array $po): int => (int) $po['id'], $purchaseOrders), true);
    $purchaseOrderLines = array_values(array_filter(
        data_collection('purchase_order_lines'),
        static fn(array $line): bool => isset($visiblePoIds[(int) ($line['purchase_order_id'] ?? 0)])
    ));
    $suppliers = data_visible_collection('suppliers', $user);
    $items = data_visible_collection('items', $user);
    $inventory = data_visible_collection('inventory_snapshots', $user);
    $savings = data_visible_collection('savings_opportunities', $user);
    $scorecards = data_visible_collection('supplier_scorecards', $user);
    $exceptions = data_visible_collection('data_quality_exceptions', $user);
    $approvals = data_visible_collection('workflow_approvals', $user);
    $discovery = data_visible_collection('discovery_assignments', $user);
    $supplierContacts = data_collection('supplier_contacts');

    $companyMap = gruber_agent_collection_map(data_collection('companies'));
    $supplierMap = gruber_agent_collection_map(data_collection('suppliers'));
    $itemMap = gruber_agent_collection_map(data_collection('items'));
    $supplierContactMap = [];
    foreach ($supplierContacts as $contact) {
        $supplierId = (int) ($contact['supplier_id'] ?? 0);
        if ($supplierId > 0 && (!isset($supplierContactMap[$supplierId]) || !empty($contact['primary']))) {
            $supplierContactMap[$supplierId] = $contact;
        }
    }

    $openStatuses = ['open', 'past_due', 'partially_received'];
    $openPos = array_values(array_filter($purchaseOrders, static fn(array $po): bool => in_array((string) ($po['status'] ?? ''), $openStatuses, true)));
    $pastDuePos = array_values(array_filter($purchaseOrders, static fn(array $po): bool => (string) ($po['status'] ?? '') === 'past_due'));
    $openExceptions = array_values(array_filter($exceptions, static fn(array $row): bool => !in_array((string) ($row['status'] ?? ''), ['resolved', 'closed'], true)));
    $pendingApprovals = array_values(array_filter($approvals, static fn(array $row): bool => (string) ($row['status'] ?? '') === 'pending'));
    $highRiskSuppliers = array_values(array_filter($suppliers, static fn(array $row): bool => in_array((string) ($row['risk'] ?? ''), ['high', 'critical'], true)));

    usort($pastDuePos, static fn(array $a, array $b): int => strcmp((string) ($a['expected_date'] ?? ''), (string) ($b['expected_date'] ?? '')));
    usort($openPos, static function (array $a, array $b): int {
        $priority = ['past_due' => 0, 'partially_received' => 1, 'open' => 2];
        return ($priority[(string) ($a['status'] ?? '')] ?? 9) <=> ($priority[(string) ($b['status'] ?? '')] ?? 9)
            ?: ((float) ($b['total_amount'] ?? 0) <=> (float) ($a['total_amount'] ?? 0));
    });
    usort($savings, static fn(array $a, array $b): int => (float) ($b['annualized_value'] ?? 0) <=> (float) ($a['annualized_value'] ?? 0));
    usort($scorecards, static fn(array $a, array $b): int => (float) ($a['overall'] ?? 0) <=> (float) ($b['overall'] ?? 0));
    usort($openExceptions, static function (array $a, array $b): int {
        $severity = ['critical' => 0, 'high' => 1, 'medium' => 2, 'low' => 3];
        return ($severity[(string) ($a['severity'] ?? '')] ?? 9) <=> ($severity[(string) ($b['severity'] ?? '')] ?? 9);
    });
    usort($discovery, static fn(array $a, array $b): int => (int) ($a['completion'] ?? 0) <=> (int) ($b['completion'] ?? 0));

    $openCommitmentValue = array_sum(array_map(static fn(array $po): float => (float) ($po['total_amount'] ?? 0), $openPos));
    $inventoryValue = array_sum(array_map(static fn(array $row): float => (float) ($row['value'] ?? 0), $inventory));
    $savingsValue = array_sum(array_map(static fn(array $row): float => (float) ($row['annualized_value'] ?? 0), $savings));
    $weightedSavingsValue = array_sum(array_map(static fn(array $row): float => (float)($row['annualized_value'] ?? 0) * ((int)($row['confidence'] ?? 0) / 100), $savings));
    $realizedSavingsValue = array_sum(array_map(static fn(array $row): float => (float)($row['realized_savings'] ?? 0), $savings));
    $avgDiscovery = $discovery
        ? (int) round(array_sum(array_map(static fn(array $row): int => (int) ($row['completion'] ?? 0), $discovery)) / count($discovery))
        : 0;

    $transfers = gruber_agent_build_transfer_candidates($purchaseOrders, $purchaseOrderLines, $inventory, $items, data_collection('companies'));

    $leadershipItems = [];
    foreach (array_slice($pastDuePos, 0, 2) as $po) {
        $leadershipItems[] = '<li><strong>Past-due commitment:</strong> '
            . gruber_agent_escape($po['po_number'] ?? '') . ' for '
            . gruber_agent_escape(data_company_name((int) ($po['company_id'] ?? 0))) . ' with '
            . gruber_agent_escape(data_supplier_name((int) ($po['supplier_id'] ?? 0))) . ' totals '
            . gruber_agent_escape(money((float) ($po['total_amount'] ?? 0))) . '.</li>';
    }
    foreach (array_slice($openExceptions, 0, max(0, 3 - count($leadershipItems))) as $exception) {
        $leadershipItems[] = '<li><strong>' . gruber_agent_status($exception['severity'] ?? 'review') . ' data exception:</strong> '
            . gruber_agent_escape($exception['issue'] ?? $exception['title'] ?? 'Review required') . '.</li>';
    }
    if (!$leadershipItems) {
        $leadershipItems[] = '<li>No past-due commitments or unresolved scoped data exceptions are currently visible.</li>';
    }

    $openPoRows = [];
    foreach (array_slice($openPos, 0, 6) as $po) {
        $openPoRows[] = '<li><strong>' . gruber_agent_escape($po['po_number'] ?? '') . '</strong> · '
            . gruber_agent_escape(data_company_name((int) ($po['company_id'] ?? 0))) . ' · '
            . gruber_agent_escape(data_supplier_name((int) ($po['supplier_id'] ?? 0))) . ' · '
            . gruber_agent_status($po['status'] ?? '') . ' · '
            . gruber_agent_escape(money((float) ($po['total_amount'] ?? 0))) . ' · expected '
            . gruber_agent_date($po['expected_date'] ?? null) . '</li>';
    }
    if (!$openPoRows) {
        $openPoRows[] = '<li>No open commitments are visible in the current company scope.</li>';
    }

    $transferRows = [];
    foreach (array_slice($transfers, 0, 5) as $candidate) {
        $transferRows[] = '<li><strong>' . gruber_agent_escape($candidate['item_number']) . '</strong> · move up to '
            . gruber_agent_escape(number_format((float) $candidate['candidate_quantity'], 0)) . ' '
            . gruber_agent_escape($candidate['uom']) . ' from '
            . gruber_agent_escape($candidate['source_company']) . ' to '
            . gruber_agent_escape($candidate['destination_company']) . ' before fulfilling '
            . gruber_agent_escape($candidate['po_number']) . ' · potential purchase offset '
            . gruber_agent_escape(money((float) $candidate['estimated_value'])) . '.</li>';
    }
    if (!$transferRows) {
        $transferRows[] = '<li>No cross-company stock candidate is visible for the currently open scoped purchase-order lines.</li>';
    }

    $supplierRows = [];
    foreach (array_slice($scorecards, 0, 4) as $scorecard) {
        $supplier = $supplierMap[(int) ($scorecard['supplier_id'] ?? 0)] ?? [];
        $supplierRows[] = '<li><strong>' . gruber_agent_escape($supplier['name'] ?? 'Supplier') . '</strong> · overall '
            . gruber_agent_escape(number_format((float) ($scorecard['overall'] ?? 0), 1)) . '% · on-time '
            . gruber_agent_escape(number_format((float) ($scorecard['on_time_delivery'] ?? 0), 1)) . '% · quality '
            . gruber_agent_escape(number_format((float) ($scorecard['quality'] ?? 0), 1)) . '%.</li>';
    }
    foreach ($highRiskSuppliers as $supplier) {
        if (count($supplierRows) >= 5) {
            break;
        }
        $supplierRows[] = '<li><strong>' . gruber_agent_escape($supplier['name'] ?? 'Supplier') . '</strong> is marked '
            . gruber_agent_status($supplier['risk'] ?? 'high') . ' risk with '
            . gruber_agent_escape(money((float) ($supplier['annual_spend'] ?? 0))) . ' in annual spend.</li>';
    }
    if (!$supplierRows) {
        $supplierRows[] = '<li>No supplier scorecards or high-risk supplier records are visible in this scope.</li>';
    }

    $savingsRows = [];
    foreach (array_slice($savings, 0, 6) as $opportunity) {
        $savingsRows[] = '<li><strong>' . gruber_agent_escape($opportunity['title'] ?? 'Savings opportunity') . '</strong> · '
            . gruber_agent_escape(data_company_name($opportunity['company_id'] ?? null)) . ' · '
            . gruber_agent_escape(money((float) ($opportunity['annualized_value'] ?? 0))) . ' annualized · '
            . gruber_agent_escape((string) ($opportunity['confidence'] ?? 0)) . '% confidence · '
            . gruber_agent_status($opportunity['stage'] ?? '') . '.</li>';
    }
    if (!$savingsRows) {
        $savingsRows[] = '<li>No savings opportunities are visible in the current scope.</li>';
    }

    $approvalRows = [];
    foreach (array_slice($pendingApprovals, 0, 6) as $approval) {
        $approvalRows[] = '<li><strong>' . gruber_agent_escape($approval['title'] ?? 'Approval') . '</strong> · '
            . gruber_agent_escape(data_company_name($approval['company_id'] ?? null)) . ' · due '
            . gruber_agent_date($approval['due_date'] ?? null) . ' · assigned to '
            . gruber_agent_escape(data_user_name($approval['assigned_to'] ?? null)) . '.</li>';
    }
    if (!$approvalRows) {
        $approvalRows[] = '<li>No pending approvals are visible for this account and company scope.</li>';
    }

    $exceptionRows = [];
    foreach (array_slice($openExceptions, 0, 6) as $exception) {
        $exceptionRows[] = '<li><strong>' . gruber_agent_status($exception['severity'] ?? 'review') . ':</strong> '
            . gruber_agent_escape($exception['issue'] ?? $exception['title'] ?? 'Data review') . ' · '
            . gruber_agent_escape(data_company_name($exception['company_id'] ?? null)) . ' · '
            . gruber_agent_status($exception['status'] ?? '') . '.</li>';
    }
    if (!$exceptionRows) {
        $exceptionRows[] = '<li>No unresolved data-quality exceptions are visible in the current scope.</li>';
    }

    $discoveryRows = [];
    foreach (array_slice($discovery, 0, 6) as $assignment) {
        $discoveryRows[] = '<li><strong>' . gruber_agent_escape($assignment['title'] ?? 'Discovery assignment') . '</strong> · '
            . gruber_agent_escape(data_company_name($assignment['company_id'] ?? null)) . ' · '
            . gruber_agent_escape((string) ($assignment['completion'] ?? 0)) . '% complete · '
            . gruber_agent_status($assignment['status'] ?? '') . ' · due '
            . gruber_agent_date($assignment['due_date'] ?? null) . '.</li>';
    }
    if (!$discoveryRows) {
        $discoveryRows[] = '<li>No discovery assignments are visible in the current scope.</li>';
    }

    $companyRows = [];
    foreach ($companies as $company) {
        $companyId = (int) $company['id'];
        $companyPos = gruber_agent_company_records($purchaseOrders, $companyId);
        $companyInventory = gruber_agent_company_records($inventory, $companyId);
        $companySavings = gruber_agent_company_records($savings, $companyId);
        $companyExceptions = gruber_agent_company_records($openExceptions, $companyId);
        $companyDiscovery = gruber_agent_company_records($discovery, $companyId);
        $companyRows[] = '<li><strong>' . gruber_agent_escape($company['code'] ?? '') . ' · '
            . gruber_agent_escape($company['name'] ?? '') . '</strong> · '
            . count($companyPos) . ' POs · '
            . gruber_agent_escape(money(array_sum(array_map(static fn(array $row): float => (float) ($row['value'] ?? 0), $companyInventory)))) . ' inventory · '
            . gruber_agent_escape(money(array_sum(array_map(static fn(array $row): float => (float) ($row['annualized_value'] ?? 0), $companySavings)))) . ' savings pipeline · '
            . count($companyExceptions) . ' open exceptions · '
            . ($companyDiscovery ? (int) round(array_sum(array_map(static fn(array $row): int => (int) ($row['completion'] ?? 0), $companyDiscovery)) / count($companyDiscovery)) : 0)
            . '% discovery.</li>';
    }
    if (!$companyRows) {
        $companyRows[] = '<li>No company operating records are available in the current scope.</li>';
    }

    $briefingPriorities = [];
    foreach (array_slice($pastDuePos, 0, 3) as $po) {
        $supplier = $supplierMap[(int) ($po['supplier_id'] ?? 0)] ?? [];
        $briefingPriorities[] = [
            'severity' => 'critical',
            'category' => 'Past-due commitment',
            'title' => 'Escalate ' . (string) ($po['po_number'] ?? 'purchase order'),
            'description' => data_company_name((int) ($po['company_id'] ?? 0)) . ' is waiting on ' . ($supplier['name'] ?? data_supplier_name((int) ($po['supplier_id'] ?? 0))) . ' for a ' . money((float) ($po['total_amount'] ?? 0)) . ' commitment expected ' . date_us((string) ($po['expected_date'] ?? '')) . '.',
            'meta' => 'Buyer ' . data_user_name((int) ($po['buyer_id'] ?? 0)) . ' · ' . status_label((string) ($po['status'] ?? 'past_due')),
            'href' => app_url('purchase-orders.php?q=' . rawurlencode((string) ($po['po_number'] ?? ''))),
            'action_label' => 'Open purchase order',
            'agent_prompt' => 'Analyze ' . (string) ($po['po_number'] ?? 'this purchase order') . ' and give me a three-step supplier escalation plan with evidence.',
        ];
    }
    foreach (array_slice($openExceptions, 0, 3) as $exception) {
        $briefingPriorities[] = [
            'severity' => (string) ($exception['severity'] ?? 'medium'),
            'category' => 'Data quality',
            'title' => (string) ($exception['issue'] ?? $exception['title'] ?? 'Resolve data exception'),
            'description' => data_company_name((int) ($exception['company_id'] ?? 0)) . ' has an unresolved ' . status_label((string) ($exception['severity'] ?? 'medium')) . ' exception in ' . (string) ($exception['module'] ?? 'the data workspace') . '.',
            'meta' => 'Owner ' . data_user_name((int) ($exception['owner_id'] ?? 0)) . ' · ' . status_label((string) ($exception['status'] ?? 'open')),
            'href' => app_url('data-collection.php'),
            'action_label' => 'Review data exception',
            'agent_prompt' => 'Explain the operational risk of this data exception and recommend the evidence needed to resolve it: ' . (string) ($exception['issue'] ?? ''),
        ];
    }
    foreach (array_slice($pendingApprovals, 0, 2) as $approval) {
        $briefingPriorities[] = [
            'severity' => 'high',
            'category' => 'Approval decision',
            'title' => (string) ($approval['title'] ?? 'Pending approval'),
            'description' => data_company_name((int) ($approval['company_id'] ?? 0)) . ' has a decision due ' . date_us((string) ($approval['due_date'] ?? '')) . '.',
            'meta' => 'Reviewer ' . data_user_name((int) ($approval['assigned_to'] ?? 0)) . ' · submitted by ' . data_user_name((int) ($approval['submitted_by'] ?? 0)),
            'href' => app_url('approvals.php?approval_id=' . (int) ($approval['id'] ?? 0) . '#approval-' . (int) ($approval['id'] ?? 0)),
            'action_label' => 'Open approval',
            'agent_prompt' => 'Summarize the evidence and decision questions for approval #' . (int) ($approval['id'] ?? 0) . ': ' . (string) ($approval['title'] ?? ''),
        ];
    }

    $briefingOpportunities = [];
    foreach (array_slice($transfers, 0, 2) as $candidate) {
        $briefingOpportunities[] = [
            'icon' => '↔',
            'title' => 'Review internal transfer for ' . (string) $candidate['item_number'],
            'description' => 'Move up to ' . number_format((float) $candidate['candidate_quantity'], 0) . ' ' . (string) $candidate['uom'] . ' from ' . (string) $candidate['source_company'] . ' to ' . (string) $candidate['destination_company'] . ' before purchasing for ' . (string) $candidate['po_number'] . '.',
            'value' => money((float) $candidate['estimated_value']) . ' potential offset',
            'href' => app_url('agent.php?prompt=' . rawurlencode('Validate the cross-company transfer candidate for ' . (string) $candidate['item_number'] . ' and ' . (string) $candidate['po_number'] . '.')),
            'action_label' => 'Analyze transfer',
        ];
    }
    foreach (array_slice($savings, 0, max(0, 3 - count($briefingOpportunities))) as $opportunity) {
        $briefingOpportunities[] = [
            'icon' => '$',
            'title' => (string) ($opportunity['title'] ?? 'Savings opportunity'),
            'description' => data_company_name((int) ($opportunity['company_id'] ?? 0)) . ' · ' . (int) ($opportunity['confidence'] ?? 0) . '% confidence · ' . status_label((string) ($opportunity['stage'] ?? 'draft')),
            'value' => money((float) ($opportunity['annualized_value'] ?? 0)) . ' annualized',
            'href' => app_url('savings.php'),
            'action_label' => 'Open savings pipeline',
        ];
    }

    $briefingHeadline = count($pastDuePos) > 0
        ? count($pastDuePos) . ' past-due commitment' . (count($pastDuePos) === 1 ? '' : 's') . ' require supplier escalation.'
        : (count($pendingApprovals) > 0
            ? count($pendingApprovals) . ' approval decision' . (count($pendingApprovals) === 1 ? '' : 's') . ' require review.'
            : 'No critical purchasing escalation is visible in the current scope.');

    $briefing = [
        'date_label' => date('l, F j, Y'),
        'generated_at' => date('Y-m-d H:i:s'),
        'status' => count($pastDuePos) > 0 || count(array_filter($openExceptions, static fn(array $row): bool => in_array((string) ($row['severity'] ?? ''), ['critical', 'high'], true))) > 0 ? 'Action required' : 'Stable',
        'headline' => $briefingHeadline,
        'summary' => 'The briefing combines purchasing commitments, inventory transfer opportunities, supplier performance, savings, approvals and data-quality records visible to ' . current_scope_label() . '.',
        'priorities' => array_slice($briefingPriorities, 0, 6),
        'opportunities' => array_slice($briefingOpportunities, 0, 3),
        'metrics' => [
            ['label' => 'Open commitments', 'value' => compact_money($openCommitmentValue), 'detail' => count($openPos) . ' purchase orders'],
            ['label' => 'Past due', 'value' => (string) count($pastDuePos), 'detail' => 'Supplier escalation'],
            ['label' => 'Pending approvals', 'value' => (string) count($pendingApprovals), 'detail' => 'Human decisions'],
            ['label' => 'Data exceptions', 'value' => (string) count($openExceptions), 'detail' => 'Unresolved records'],
            ['label' => 'Savings pipeline', 'value' => compact_money($savingsValue), 'detail' => count($savings) . ' opportunities'],
            ['label' => 'Inventory value', 'value' => compact_money($inventoryValue), 'detail' => count($inventory) . ' positions'],
        ],
    ];

    $prompts = [
        [
            'icon' => '!',
            'title' => 'Leadership risk briefing',
            'description' => 'Combine past-due commitments, exceptions and approvals into today’s operating brief.',
            'prompt' => 'Give me the current leadership risk briefing from the demo records.',
            'keywords' => ['leadership', 'risk', 'briefing', 'today', 'issues', 'executive'],
            'response_title' => 'Executive Briefing Agent',
            'response_html' => '<p><strong>Current scoped leadership review</strong></p><ul>' . implode('', $leadershipItems) . '</ul><p>Human action: confirm business impact, assign an accountable owner and record the decision in the appropriate workflow.</p>' . gruber_agent_record_link('dashboard.php', 'Open executive dashboard'),
            'evidence' => [count($pastDuePos) . ' past-due POs', count($openExceptions) . ' open data exceptions', count($pendingApprovals) . ' pending approvals'],
        ],
        [
            'icon' => 'PO',
            'title' => 'Open commitments and POs',
            'description' => 'Review the actual open, past-due and partially received demo purchase orders.',
            'prompt' => 'Show the open purchase orders and commitments in the current demo scope.',
            'keywords' => ['purchase order', 'open po', 'commitment', 'past due', 'expected date', 'po'],
            'response_title' => 'Purchase Order Agent',
            'response_html' => '<p><strong>' . count($openPos) . ' open commitments total ' . gruber_agent_escape(money($openCommitmentValue)) . '.</strong></p><ul>' . implode('', $openPoRows) . '</ul>' . gruber_agent_record_link('purchase-orders.php', 'Open purchase orders'),
            'evidence' => [count($purchaseOrders) . ' scoped POs', gruber_agent_escape(money($openCommitmentValue)) . ' open value', count($pastDuePos) . ' past due'],
        ],
        [
            'icon' => '↔',
            'title' => 'Cross-company transfer scan',
            'description' => 'Match available inventory to open PO lines before duplicate purchasing occurs.',
            'prompt' => 'Find cross-company inventory transfers supported by the demo purchase orders and inventory snapshots.',
            'keywords' => ['transfer', 'inventory', 'stock', 'duplicate purchase', 'available', 'avoid purchase'],
            'response_title' => 'Inventory Intelligence Agent',
            'response_html' => '<p><strong>Transfer candidates matched from actual scoped demo records</strong></p><ul>' . implode('', $transferRows) . '</ul><p>These are screening candidates only. Confirm specification, custody, reservation, condition, freight and required date before changing a PO.</p>' . gruber_agent_record_link('inventory.php', 'Open inventory intelligence'),
            'evidence' => [count($inventory) . ' inventory snapshots', count($purchaseOrderLines) . ' PO lines reviewed', count($transfers) . ' transfer candidates'],
        ],
        [
            'icon' => '◇',
            'title' => 'Supplier performance review',
            'description' => 'Compare scorecards, risk flags, annual spend and delivery performance.',
            'prompt' => 'Which suppliers need attention based on the demo scorecards and risk records?',
            'keywords' => ['supplier', 'vendor', 'scorecard', 'delivery', 'quality', 'performance', 'risk'],
            'response_title' => 'Supplier Intelligence Agent',
            'response_html' => '<p><strong>Supplier attention queue</strong></p><ul>' . implode('', $supplierRows) . '</ul><p>Human action: validate the scorecard period and source evidence before issuing a corrective action or sourcing decision.</p>' . gruber_agent_record_link('scorecards.php', 'Open supplier scorecards'),
            'evidence' => [count($suppliers) . ' suppliers', count($scorecards) . ' scorecards', count($highRiskSuppliers) . ' high-risk suppliers'],
        ],
        [
            'icon' => '$',
            'title' => 'Prioritize savings pipeline',
            'description' => 'Rank actual demo opportunities by annualized value, confidence and stage.',
            'prompt' => 'Prioritize the current savings opportunities from the demo data.',
            'keywords' => ['saving', 'cost', 'opportunity', 'pipeline', 'annualized', 'avoidance', 'value'],
            'response_title' => 'Savings Opportunity Agent',
            'response_html' => '<p><strong>' . count($savings) . ' scoped opportunities total ' . gruber_agent_escape(money($savingsValue)) . ' in annualized value, with a ' . gruber_agent_escape(money($weightedSavingsValue)) . ' confidence-weighted forecast and ' . gruber_agent_escape(money($realizedSavingsValue)) . ' recorded as realized.</strong></p><ul>' . implode('', $savingsRows) . '</ul><p>Accounting should validate the baseline, one-time implementation costs and timing before value is reported as realized.</p>' . gruber_agent_record_link('savings.php', 'Open savings pipeline'),
            'evidence' => [count($savings) . ' opportunities', gruber_agent_escape(money($savingsValue)) . ' gross pipeline', gruber_agent_escape(money($weightedSavingsValue)) . ' weighted forecast', gruber_agent_escape(money($realizedSavingsValue)) . ' realized'],
        ],
        [
            'icon' => '✓',
            'title' => 'Pending approval queue',
            'description' => 'Show decisions awaiting reviewer action, due dates and assigned owners.',
            'prompt' => 'What demo records are waiting for approval and who owns the review?',
            'keywords' => ['approval', 'review', 'pending', 'decision', 'due', 'assigned'],
            'response_title' => 'Approval Workflow Agent',
            'response_html' => '<p><strong>' . count($pendingApprovals) . ' pending approvals are visible.</strong></p><ul>' . implode('', $approvalRows) . '</ul>' . gruber_agent_record_link('approvals.php', 'Open reviews and approvals'),
            'evidence' => [count($pendingApprovals) . ' pending', 'Assigned reviewer', 'Due date and notes'],
        ],
        [
            'icon' => 'DQ',
            'title' => 'Data-quality exception review',
            'description' => 'Surface duplicate, incomplete and unsupported records from the demo queue.',
            'prompt' => 'Review the unresolved demo data-quality exceptions in priority order.',
            'keywords' => ['data quality', 'exception', 'duplicate', 'missing', 'validation', 'normalize', 'error'],
            'response_title' => 'Data Quality Agent',
            'response_html' => '<p><strong>' . count($openExceptions) . ' unresolved exceptions are visible.</strong></p><ul>' . implode('', $exceptionRows) . '</ul><p>No master-data merge or financial correction should be committed until the responsible owner validates the source evidence.</p>' . gruber_agent_record_link('imports.php', 'Open import and validation workspace'),
            'evidence' => [count($openExceptions) . ' unresolved exceptions', 'Severity and company scope', 'Assigned data owner'],
        ],
        [
            'icon' => '30',
            'title' => 'Baseline discovery progress',
            'description' => 'Review the six-company discovery assignments, completion and overdue work.',
            'prompt' => 'Summarize the demo baseline discovery progress across the companies.',
            'keywords' => ['discovery', 'baseline', 'progress', 'completion', 'assignment', '30 day'],
            'response_title' => 'Baseline Assessment Agent',
            'response_html' => '<p><strong>Average scoped discovery completion is ' . $avgDiscovery . '%.</strong></p><ul>' . implode('', $discoveryRows) . '</ul>' . gruber_agent_record_link('discovery.php', 'Open discovery assignments'),
            'evidence' => [count($discovery) . ' assignments', $avgDiscovery . '% average completion', 'Owner, reviewer and due date'],
        ],
        [
            'icon' => '6',
            'title' => 'Compare company operations',
            'description' => 'Compare POs, inventory, savings, exceptions and discovery across the active scope.',
            'prompt' => 'Compare the operating picture for the Gruber companies in the current demo scope.',
            'keywords' => ['company', 'companies', 'compare', 'operating', 'enterprise', 'scope', 'gc', 'gps', 'gts', 'gmc', 'evp', 'gcp'],
            'response_title' => 'Enterprise Operating Agent',
            'response_html' => '<p><strong>Company operating comparison</strong></p><ul>' . implode('', $companyRows) . '</ul><p>Use a company code such as GC, GPS, GTS, GMC, EVP or GCP for a focused response.</p>',
            'evidence' => [count($companies) . ' companies in scope', count($purchaseOrders) . ' POs', gruber_agent_escape(money($inventoryValue)) . ' inventory'],
        ],
        [
            'icon' => '⌕',
            'title' => 'Look up a demo record',
            'description' => 'Ask about a PO number, supplier, item number or Gruber company code.',
            'prompt' => 'Show me examples of purchase orders, suppliers, items and company records I can ask about.',
            'keywords' => ['lookup', 'search', 'record', 'examples', 'po number', 'item number', 'supplier name'],
            'response_title' => 'Demo Record Navigator',
            'response_html' => '<p>You can ask about a specific record by typing its identifier or name. Examples from the current scope:</p><ul><li><strong>Purchase order:</strong> ' . gruber_agent_escape($purchaseOrders[0]['po_number'] ?? 'GPS-10428') . '</li><li><strong>Supplier:</strong> ' . gruber_agent_escape($suppliers[0]['name'] ?? 'Copper State Critical Power') . '</li><li><strong>Item:</strong> ' . gruber_agent_escape($items[0]['item_number'] ?? 'BAT-UPS-12V-100') . '</li><li><strong>Company:</strong> ' . gruber_agent_escape(($companies[0]['code'] ?? 'GPS') . ' or ' . ($companies[0]['name'] ?? 'Gruber Power Services')) . '</li></ul><p>The chat will return the corresponding demo evidence visible to your role and company scope.</p>',
            'evidence' => ['PO numbers', 'Supplier names and numbers', 'Item numbers and SKUs', 'Company codes'],
        ],
    ];

    $prompts[] = [
        'icon' => '＋',
        'title' => 'Explore workspace capabilities',
        'description' => 'See the demo records, operational questions and evidence this supervised workspace can analyze.',
        'prompt' => 'What can the Agent Workspace analyze in this demo?',
        'keywords' => ['what can you do', 'capabilities', 'help', 'examples', 'demo content', 'analyze'],
        'response_title' => 'Gruber Workspace Guide',
        'response_html' => '<p><strong>This workspace reads the same scoped demo records used throughout the application.</strong></p><ul><li>Executive risk: past-due POs, open exceptions and pending approvals.</li><li>Purchasing: open commitments, supplier exposure and required dates.</li><li>Inventory: company-level stock and cross-company transfer candidates.</li><li>Suppliers: risk, annual spend, scorecards, delivery and quality performance.</li><li>Value: savings opportunities, confidence, stage and annualized impact.</li><li>Governance: approvals, data-quality exceptions and discovery progress.</li><li>Direct lookup: ask for a PO number, supplier, item/SKU or Gruber company code.</li></ul><p>Every answer is evidence-based and remains subject to human review before an operational decision is made.</p>',
        'evidence' => [count($purchaseOrders) . ' POs', count($suppliers) . ' suppliers', count($items) . ' items', count($companies) . ' companies in scope'],
    ];

    $attentionSupplier = $highRiskSuppliers[0] ?? ($scorecards ? ($supplierMap[(int) ($scorecards[0]['supplier_id'] ?? 0)] ?? null) : null);
    $attentionSupplierContact = $attentionSupplier ? ($supplierContactMap[(int) ($attentionSupplier['id'] ?? 0)] ?? null) : null;
    $topTransfer = $transfers[0] ?? null;
    $topPendingApproval = $pendingApprovals[0] ?? null;

    $promptActions = [
        'Leadership risk briefing' => [
            ['type' => 'link', 'icon' => '☀', 'label' => 'Open daily briefing', 'description' => 'Prioritized operating summary', 'href' => app_url('briefing.php')],
            ['type' => 'link', 'icon' => '✓', 'label' => 'Review approvals', 'description' => count($pendingApprovals) . ' decisions awaiting review', 'href' => app_url('approvals.php')],
            ['type' => 'prompt', 'icon' => '3', 'label' => 'Build action plan', 'description' => 'Convert the brief into owner-based next steps', 'prompt' => 'Turn the current leadership briefing into a three-step action plan with owners, due dates and evidence requirements.'],
        ],
        'Open commitments and POs' => [
            ['type' => 'link', 'icon' => 'PO', 'label' => 'Open purchase orders', 'description' => 'Review filtered commitments and workflow', 'href' => app_url('purchase-orders.php')],
            ['type' => 'link', 'icon' => '✓', 'label' => 'Open approval queue', 'description' => 'Review decisions tied to commitments', 'href' => app_url('approvals.php')],
            ['type' => 'prompt', 'icon' => '↗', 'label' => 'Draft escalation plan', 'description' => 'Create a supplier follow-up sequence', 'prompt' => 'Draft a concise supplier escalation plan for the highest-risk open purchase order, including the evidence to confirm before sending.'],
        ],
        'Cross-company transfer scan' => [
            ['type' => 'link', 'icon' => '▤', 'label' => 'Open inventory', 'description' => 'Confirm custody and availability', 'href' => app_url('inventory.php')],
            ['type' => 'link', 'icon' => '$', 'label' => 'Create savings opportunity', 'description' => 'Prefill a transfer-avoidance opportunity', 'href' => $topTransfer ? app_url('savings.php?new=1&title=' . rawurlencode('Cross-company transfer: ' . (string) $topTransfer['item_number']) . '&category=' . rawurlencode('Demand avoidance') . '&annualized_value=' . rawurlencode((string) round((float) $topTransfer['estimated_value'], 2)) . '&confidence=65&company_id=' . (int) $topTransfer['destination_company_id']) : app_url('savings.php?new=1')],
            ['type' => 'prompt', 'icon' => '?', 'label' => 'Validate transfer', 'description' => 'Check specification, dates and freight', 'prompt' => $topTransfer ? 'Validate the transfer candidate for ' . (string) $topTransfer['item_number'] . ' and ' . (string) $topTransfer['po_number'] . '. List every human confirmation required before changing the purchase order.' : 'Explain the evidence needed to approve an internal inventory transfer.'],
        ],
        'Supplier performance review' => [
            ['type' => 'link', 'icon' => '★', 'label' => 'Open scorecards', 'description' => 'Review delivery and quality evidence', 'href' => app_url('scorecards.php')],
            ['type' => 'link', 'icon' => '◇', 'label' => 'Open supplier master', 'description' => 'Review ownership, risk and contracts', 'href' => $attentionSupplier ? app_url('suppliers.php?q=' . rawurlencode((string) ($attentionSupplier['name'] ?? ''))) : app_url('suppliers.php')],
            ['type' => 'link', 'icon' => '@', 'label' => 'Draft supplier email', 'description' => 'Open a corrective-action message', 'href' => $attentionSupplierContact ? 'mailto:' . rawurlencode((string) ($attentionSupplierContact['email'] ?? '')) . '?subject=' . rawurlencode('Supplier performance review') . '&body=' . rawurlencode('Please review the current delivery, quality and risk findings. We would like to confirm the underlying evidence and agree on corrective actions.') : app_url('suppliers.php')],
        ],
        'Prioritize savings pipeline' => [
            ['type' => 'link', 'icon' => '$', 'label' => 'Open savings pipeline', 'description' => 'Review stage, confidence and value', 'href' => app_url('savings.php')],
            ['type' => 'link', 'icon' => '＋', 'label' => 'Create opportunity', 'description' => 'Open a prefilled savings record', 'href' => app_url('savings.php?new=1&title=' . rawurlencode('Agent-identified procurement opportunity') . '&confidence=50')],
            ['type' => 'prompt', 'icon' => '1', 'label' => 'Recommend first move', 'description' => 'Select the most actionable opportunity', 'prompt' => 'Choose the single most actionable savings opportunity and explain the baseline, evidence, owner and next decision required.'],
        ],
        'Pending approval queue' => [
            ['type' => 'link', 'icon' => '✓', 'label' => 'Open approval queue', 'description' => 'Review evidence and decisions', 'href' => app_url('approvals.php')],
            ['type' => 'link', 'icon' => '✎', 'label' => 'Prepare review comment', 'description' => 'Prefill a human review note', 'href' => $topPendingApproval ? app_url('approvals.php?approval_id=' . (int) $topPendingApproval['id'] . '&body=' . rawurlencode('Evidence reviewed. Please confirm the remaining business-impact, source-data and ownership questions before a final decision.') . '#approval-' . (int) $topPendingApproval['id']) : app_url('approvals.php')],
            ['type' => 'prompt', 'icon' => '?', 'label' => 'List decision questions', 'description' => 'Identify what the reviewer must confirm', 'prompt' => 'List the unresolved decision questions for the highest-priority pending approval and distinguish facts from assumptions.'],
        ],
        'Data-quality exception review' => [
            ['type' => 'link', 'icon' => '⇪', 'label' => 'Open import validation', 'description' => 'Review row-level validation evidence', 'href' => app_url('imports.php')],
            ['type' => 'link', 'icon' => '⇩', 'label' => 'Open collection hub', 'description' => 'Review owners and source readiness', 'href' => app_url('data-collection.php')],
            ['type' => 'prompt', 'icon' => 'DQ', 'label' => 'Create correction plan', 'description' => 'Prioritize safe data remediation', 'prompt' => 'Create a correction plan for the highest-severity data-quality exception, including owner, source evidence, validation and rollback checks.'],
        ],
        'Baseline discovery progress' => [
            ['type' => 'link', 'icon' => '30', 'label' => 'Open discovery', 'description' => 'Review assignments and completion', 'href' => app_url('discovery.php')],
            ['type' => 'prompt', 'icon' => '!', 'label' => 'Identify blockers', 'description' => 'Find work most likely to delay baseline', 'prompt' => 'Identify the three discovery blockers most likely to delay the baseline and recommend owners and next steps.'],
        ],
        'Compare company operations' => [
            ['type' => 'link', 'icon' => '⌂', 'label' => 'Open dashboard', 'description' => 'Compare company-level operating metrics', 'href' => app_url('dashboard.php')],
            ['type' => 'link', 'icon' => '▥', 'label' => 'Open reports', 'description' => 'Review reporting views', 'href' => app_url('reports.php')],
            ['type' => 'prompt', 'icon' => '↔', 'label' => 'Find consolidation', 'description' => 'Identify shared supplier and item opportunities', 'prompt' => 'Identify the strongest cross-company consolidation opportunity using suppliers, items, inventory and purchase commitments.'],
        ],
        'Look up a demo record' => [
            ['type' => 'prompt', 'icon' => 'PO', 'label' => 'Show highest-risk PO', 'description' => 'Open the most urgent commitment', 'prompt' => 'Show me the highest-risk purchase order and its supporting evidence.'],
            ['type' => 'prompt', 'icon' => '◇', 'label' => 'Show supplier risk', 'description' => 'Find the supplier needing attention', 'prompt' => 'Show me the supplier that needs the most attention and explain why.'],
            ['type' => 'prompt', 'icon' => '□', 'label' => 'Show item opportunity', 'description' => 'Find an item with transfer or cost potential', 'prompt' => 'Show me an item with a cross-company transfer or savings opportunity.'],
        ],
        'Explore workspace capabilities' => [
            ['type' => 'link', 'icon' => '☀', 'label' => 'Open daily briefing', 'description' => 'See the complete operating summary', 'href' => app_url('briefing.php')],
            ['type' => 'prompt', 'icon' => '!', 'label' => 'Review today’s risks', 'description' => 'Start with critical issues', 'prompt' => 'Give me today’s leadership risk briefing.'],
            ['type' => 'prompt', 'icon' => '$', 'label' => 'Find value', 'description' => 'Start with savings and transfers', 'prompt' => 'Find the best current savings or inventory-transfer opportunity.'],
        ],
    ];
    foreach ($prompts as &$promptItem) {
        $promptItem['actions'] = $promptActions[(string) ($promptItem['title'] ?? '')] ?? [];
        $promptItem['confidence'] = count((array)($promptItem['evidence'] ?? [])) >= 3 ? 'High' : 'Moderate';
        $promptItem['generated_at'] = date('Y-m-d H:i:s');
        $promptItem['human_review'] = 'Required before any operational change';
        $promptItem['missing_data'] = [];
    }
    unset($promptItem);

    $lookups = [];
    foreach ($purchaseOrders as $po) {
        $supplier = $supplierMap[(int) ($po['supplier_id'] ?? 0)] ?? [];
        $company = $companyMap[(int) ($po['company_id'] ?? 0)] ?? [];
        $lookups[] = [
            'aliases' => [strtolower((string) ($po['po_number'] ?? ''))],
            'response_title' => 'Purchase Order Agent',
            'response_html' => '<p><strong>' . gruber_agent_escape($po['po_number'] ?? 'Purchase order') . '</strong></p><ul><li>Company: ' . gruber_agent_escape($company['name'] ?? data_company_name($po['company_id'] ?? null)) . '</li><li>Supplier: ' . gruber_agent_escape($supplier['name'] ?? data_supplier_name($po['supplier_id'] ?? null)) . '</li><li>Status: ' . gruber_agent_status($po['status'] ?? '') . '</li><li>Total: ' . gruber_agent_escape(money((float) ($po['total_amount'] ?? 0))) . '</li><li>Required: ' . gruber_agent_date($po['required_date'] ?? null) . '</li><li>Expected: ' . gruber_agent_date($po['expected_date'] ?? null) . '</li><li>Review status: ' . gruber_agent_status($po['review_status'] ?? '') . '</li></ul>' . gruber_agent_record_link('purchase-orders.php', 'Open purchase-order workspace'),
            'evidence' => [(string) ($po['po_number'] ?? ''), (string) ($company['code'] ?? ''), (string) ($supplier['supplier_number'] ?? '')],
        ];
    }

    foreach ($suppliers as $supplier) {
        $supplierScorecards = array_values(array_filter($scorecards, static fn(array $row): bool => (int) ($row['supplier_id'] ?? 0) === (int) ($supplier['id'] ?? 0)));
        $score = $supplierScorecards[0] ?? null;
        $lookups[] = [
            'aliases' => array_values(array_filter([
                strtolower((string) ($supplier['name'] ?? '')),
                strtolower((string) ($supplier['legal_name'] ?? '')),
                strtolower((string) ($supplier['supplier_number'] ?? '')),
            ])),
            'response_title' => 'Supplier Intelligence Agent',
            'response_html' => '<p><strong>' . gruber_agent_escape($supplier['name'] ?? 'Supplier') . '</strong> · ' . gruber_agent_escape($supplier['supplier_number'] ?? '') . '</p><ul><li>Category: ' . gruber_agent_escape($supplier['category'] ?? '') . '</li><li>Status: ' . gruber_agent_status($supplier['status'] ?? '') . '</li><li>Risk: ' . gruber_agent_status($supplier['risk'] ?? '') . '</li><li>Annual spend: ' . gruber_agent_escape(money((float) ($supplier['annual_spend'] ?? 0))) . '</li><li>Payment terms: ' . gruber_agent_escape($supplier['payment_terms'] ?? '') . '</li>' . ($score ? '<li>Latest scorecard: ' . gruber_agent_escape(number_format((float) ($score['overall'] ?? 0), 1)) . '% overall, ' . gruber_agent_escape(number_format((float) ($score['on_time_delivery'] ?? 0), 1)) . '% on time</li>' : '<li>No scoped scorecard is available.</li>') . '</ul>' . gruber_agent_record_link('suppliers.php', 'Open supplier workspace'),
            'evidence' => [(string) ($supplier['supplier_number'] ?? ''), (string) ($supplier['category'] ?? ''), (string) ($supplier['review_status'] ?? '')],
        ];
    }

    foreach ($items as $item) {
        $itemInventory = array_values(array_filter($inventory, static fn(array $row): bool => (int) ($row['item_id'] ?? 0) === (int) ($item['id'] ?? 0)));
        $itemLines = array_values(array_filter($purchaseOrderLines, static fn(array $line): bool => (int) ($line['item_id'] ?? 0) === (int) ($item['id'] ?? 0)));
        $lookups[] = [
            'aliases' => array_values(array_filter([
                strtolower((string) ($item['item_number'] ?? '')),
                strtolower((string) ($item['sku'] ?? '')),
                strtolower((string) ($item['description'] ?? '')),
            ])),
            'response_title' => 'Item and Inventory Agent',
            'response_html' => '<p><strong>' . gruber_agent_escape($item['item_number'] ?? 'Item') . ' · ' . gruber_agent_escape($item['description'] ?? '') . '</strong></p><ul><li>SKU: ' . gruber_agent_escape($item['sku'] ?? '') . '</li><li>Standard cost: ' . gruber_agent_escape(money((float) ($item['standard_cost'] ?? 0))) . '</li><li>Unit: ' . gruber_agent_escape($item['uom'] ?? '') . '</li><li>Review status: ' . gruber_agent_status($item['review_status'] ?? '') . '</li><li>Scoped inventory snapshots: ' . count($itemInventory) . '</li><li>Scoped purchase-order lines: ' . count($itemLines) . '</li></ul>' . gruber_agent_record_link('items.php', 'Open item master'),
            'evidence' => [(string) ($item['item_number'] ?? ''), (string) ($item['sku'] ?? ''), count($itemInventory) . ' inventory snapshots'],
        ];
    }

    foreach ($companies as $company) {
        $companyId = (int) $company['id'];
        $companyPos = gruber_agent_company_records($purchaseOrders, $companyId);
        $companyInventory = gruber_agent_company_records($inventory, $companyId);
        $companySavings = gruber_agent_company_records($savings, $companyId);
        $companyExceptions = gruber_agent_company_records($openExceptions, $companyId);
        $companyDiscovery = gruber_agent_company_records($discovery, $companyId);
        $lookups[] = [
            'aliases' => [strtolower((string) ($company['code'] ?? '')), strtolower((string) ($company['name'] ?? ''))],
            'response_title' => 'Company Operating Agent',
            'response_html' => '<p><strong>' . gruber_agent_escape($company['code'] ?? '') . ' · ' . gruber_agent_escape($company['name'] ?? '') . '</strong></p><ul><li>Industry: ' . gruber_agent_escape($company['industry'] ?? '') . '</li><li>Data completion: ' . gruber_agent_escape((string) ($company['completion'] ?? 0)) . '%</li><li>Purchase orders: ' . count($companyPos) . '</li><li>Inventory value: ' . gruber_agent_escape(money(array_sum(array_map(static fn(array $row): float => (float) ($row['value'] ?? 0), $companyInventory)))) . '</li><li>Savings pipeline: ' . gruber_agent_escape(money(array_sum(array_map(static fn(array $row): float => (float) ($row['annualized_value'] ?? 0), $companySavings)))) . '</li><li>Open exceptions: ' . count($companyExceptions) . '</li><li>Discovery assignments: ' . count($companyDiscovery) . '</li></ul>',
            'evidence' => [(string) ($company['code'] ?? ''), count($companyPos) . ' POs', count($companyExceptions) . ' exceptions'],
        ];
    }

    foreach ($lookups as &$lookupItem) {
        $lookupItem['confidence'] = count((array)($lookupItem['evidence'] ?? [])) >= 3 ? 'High' : 'Moderate';
        $lookupItem['generated_at'] = date('Y-m-d H:i:s');
        $lookupItem['human_review'] = 'Required before any operational change';
        $lookupItem['missing_data'] = [];
        $aliases = array_values(array_filter(array_map('strval', (array) ($lookupItem['aliases'] ?? []))));
        $primaryAlias = $aliases[0] ?? '';
        $title = (string) ($lookupItem['response_title'] ?? '');
        if ($title === 'Purchase Order Agent') {
            $lookupItem['actions'] = [
                ['type' => 'link', 'icon' => 'PO', 'label' => 'Open purchase order', 'description' => 'View commitment and workflow', 'href' => app_url('purchase-orders.php?q=' . rawurlencode($primaryAlias))],
                ['type' => 'prompt', 'icon' => '3', 'label' => 'Build escalation plan', 'description' => 'Generate human-supervised next steps', 'prompt' => 'Create a three-step escalation plan for ' . $primaryAlias . ' with evidence and owners.'],
            ];
        } elseif ($title === 'Supplier Intelligence Agent') {
            $lookupItem['actions'] = [
                ['type' => 'link', 'icon' => '◇', 'label' => 'Open supplier', 'description' => 'Review master data and workflow', 'href' => app_url('suppliers.php?q=' . rawurlencode($primaryAlias))],
                ['type' => 'link', 'icon' => '★', 'label' => 'Open scorecards', 'description' => 'Review performance evidence', 'href' => app_url('scorecards.php')],
            ];
        } elseif ($title === 'Item and Inventory Agent') {
            $lookupItem['actions'] = [
                ['type' => 'link', 'icon' => '□', 'label' => 'Open item master', 'description' => 'Review normalized item data', 'href' => app_url('items.php?q=' . rawurlencode($primaryAlias))],
                ['type' => 'link', 'icon' => '▤', 'label' => 'Open inventory', 'description' => 'Review company positions', 'href' => app_url('inventory.php')],
                ['type' => 'prompt', 'icon' => '↔', 'label' => 'Scan transfer potential', 'description' => 'Match stock to open demand', 'prompt' => 'Check cross-company inventory and open demand for ' . $primaryAlias . '.'],
            ];
        } elseif ($title === 'Company Operating Agent') {
            $lookupItem['actions'] = [
                ['type' => 'link', 'icon' => '⌂', 'label' => 'Open dashboard', 'description' => 'Review the active company scope', 'href' => app_url('dashboard.php')],
                ['type' => 'prompt', 'icon' => '$', 'label' => 'Find company value', 'description' => 'Identify savings and transfer options', 'prompt' => 'Find the strongest savings, supplier or inventory opportunity for ' . $primaryAlias . '.'],
            ];
        }
    }
    unset($lookupItem);

    $exampleQueries = array_values(array_filter([
        $purchaseOrders[0]['po_number'] ?? null,
        $suppliers[0]['name'] ?? null,
        $items[0]['item_number'] ?? null,
        $companies[0]['code'] ?? null,
    ]));

    return [
        'user_name' => (string) ($user['name'] ?? 'Gruber User'),
        'environment' => data_is_demo() ? 'demo' : 'production',
        'scope' => current_scope_label(),
        'prompts' => $prompts,
        'lookups' => $lookups,
        'example_queries' => $exampleQueries,
        'briefing' => $briefing,
        'policy' => [
            'approved_data_only' => (bool)(data_settings()['approved_data_only_agent'] ?? true),
            'generated_at' => date('Y-m-d H:i:s'),
            'human_review' => 'Required before any operational change',
            'environment' => data_is_demo() ? 'Fictional demo evidence' : 'Production repository evidence',
        ],
        'metrics' => [
            'purchase_orders' => count($purchaseOrders),
            'open_commitments' => count($openPos),
            'suppliers' => count($suppliers),
            'inventory_value' => compact_money($inventoryValue),
            'savings_opportunities' => count($savings),
            'savings_value' => compact_money($savingsValue),
            'pending_approvals' => count($pendingApprovals),
            'data_exceptions' => count($openExceptions),
        ],
    ];
}
