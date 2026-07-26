<?php if ($error): ?><div class="flash error" role="alert"><?= h($error) ?></div><?php endif; ?>
<?php if (data_is_production() && !scenario_tables_ready()): ?><div class="flash warning" role="status"><strong>Production scenario saving is not active yet.</strong> Import <code>database/20260726_section12_procurement_scenarios.sql</code> during the final deployment window. Simulation and CSV export remain available.</div><?php endif; ?>

<section class="scenario-hero">
    <div>
        <span class="eyebrow">Scenario Planning &amp; Procurement Risk Simulation</span>
        <h2><?= h($supplier['name'] ?? ($category['name'] ?? 'Enterprise procurement scenario')) ?></h2>
        <p>Model the annual cost, cash, service, open-commitment, and inventory impact before approving a sourcing decision or purchase response.</p>
        <div class="company-chip-row"><span><?= h(current_scope_label()) ?></span><span><?= count($simulation['baseline']['orders']) ?> affected POs</span><span><?= count($simulation['baseline']['item_ids']) ?> affected items</span><span><?= compact_money($simulation['baseline']['inventory_value']) ?> inventory exposure</span></div>
        <div class="active-assumption-grid" aria-label="Active scenario assumptions"><span><?= h(status_label($simulation['inputs']['scenario_type'])) ?></span><span><?= number_format((float)$active['price_change_pct'],1) ?>% price</span><span><?= number_format((float)$active['demand_change_pct'],1) ?>% demand</span><span><?= (int)$active['lead_time_delay_days'] ?> delay days</span><span><?= number_format((float)$active['disruption_pct'],1) ?>% disruption</span></div>
    </div>
    <div class="risk-orb <?= h(status_class($simulation['risk_level'])) ?>"><span>Expected risk score</span><strong><?= number_format((float)$simulation['risk_score'],1) ?></strong><small><?= h(status_label($simulation['risk_level'])) ?> procurement risk</small></div>
</section>

<section class="panel">
<header class="panel-head"><div><span class="eyebrow">Scenario controls</span><h2>Adjust assumptions</h2><p>Inputs are constrained to safe operational ranges. The chosen scenario type activates only its relevant assumptions.</p></div><span class="panel-meta">Generated <?= h(date_us($simulation['generated_at'],true)) ?></span></header>
<form method="get" class="stacked-form">
<div class="scenario-controls">
<label><span>Scenario type</span><select name="scenario_type"><?php foreach(['compound'=>'Compound disruption','price_increase'=>'Price increase','lead_time_delay'=>'Lead-time delay','supply_disruption'=>'Supply disruption','demand_spike'=>'Demand spike'] as $value=>$label): ?><option value="<?= h($value) ?>" <?= $simulation['inputs']['scenario_type']===$value?'selected':'' ?>><?= h($label) ?></option><?php endforeach; ?></select></label>
<label><span>Supplier</span><select name="supplier_id"><option value="0">Category / enterprise view</option><?php foreach(data_visible_collection('suppliers') as $row): ?><option value="<?= (int)$row['id'] ?>" <?= (int)$simulation['inputs']['supplier_id']===(int)$row['id']?'selected':'' ?>><?= h($row['name']) ?></option><?php endforeach; ?></select></label>
<label><span>Category</span><select name="category_id"><?php foreach(data_collection('categories') as $row): ?><option value="<?= (int)$row['id'] ?>" <?= (int)$simulation['inputs']['category_id']===(int)$row['id']?'selected':'' ?>><?= h($row['name']) ?></option><?php endforeach; ?></select></label>
<label><span>Price change %</span><input type="number" step="0.1" min="-50" max="250" name="price_change_pct" value="<?= h((string)$simulation['inputs']['price_change_pct']) ?>"></label>
<label><span>Demand change %</span><input type="number" step="0.1" min="-80" max="300" name="demand_change_pct" value="<?= h((string)$simulation['inputs']['demand_change_pct']) ?>"></label>
<label><span>Lead-time delay days</span><input type="number" min="0" max="365" name="lead_time_delay_days" value="<?= h((string)$simulation['inputs']['lead_time_delay_days']) ?>"></label>
<label><span>Supply disruption %</span><input type="number" step="0.1" min="0" max="100" name="disruption_pct" value="<?= h((string)$simulation['inputs']['disruption_pct']) ?>"></label>
<label><span>Savings offset %</span><input type="number" step="0.1" min="0" max="100" name="savings_offset_pct" value="<?= h((string)$simulation['inputs']['savings_offset_pct']) ?>"></label>
<label><span>Transfer recovery %</span><input type="number" step="0.1" min="0" max="100" name="transfer_recovery_pct" value="<?= h((string)$simulation['inputs']['transfer_recovery_pct']) ?>"></label>
</div><div><button class="button primary" type="submit">Run simulation</button></div>
</form>
</section>
