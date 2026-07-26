<?php
declare(strict_types=1);
require_once dirname(__DIR__) . '/includes/app/bootstrap.php';
require_once dirname(__DIR__) . '/includes/app/agent_context.php';
require_permission('platform.view');

$workspace = gruber_agent_build_workspace();
$briefing = $workspace['briefing'];
$actions = '';
if (can('agent.view')) {
    $actions .= '<a class="button secondary" href="' . h(app_url('agent.php?prompt=' . rawurlencode('Turn today’s executive briefing into a prioritized action plan with owners and evidence requirements.'))) . '">Analyze with Agent</a>';
}
$actions .= '<a class="button primary" href="' . h(app_url('dashboard.php')) . '">Open Dashboard</a>';

render_app_start(
    'Daily Executive Briefing',
    'briefing',
    'Leadership operating brief',
    'A prioritized summary of commitments, supplier risk, inventory opportunities, approvals, savings and data quality for ' . current_scope_label() . '.',
    $actions
);
?>
<section class="briefing-hero panel">
    <div>
        <span class="briefing-date"><?= h($briefing['date_label']) ?></span>
        <div class="briefing-status <?= h(status_class(strtolower(str_replace(' ', '_', (string) $briefing['status'])))) ?>"><?= h($briefing['status']) ?></div>
        <h2><?= h($briefing['headline']) ?></h2>
        <p><?= h($briefing['summary']) ?></p>
        <small>Generated <?= h(date_us($briefing['generated_at'], true)) ?> · <?= h($workspace['scope']) ?> · Human review required before operational changes.</small>
    </div>
    <aside>
        <span>Briefing focus</span>
        <strong><?= count($briefing['priorities']) ?> priority items</strong>
        <strong><?= count($briefing['opportunities']) ?> value opportunities</strong>
        <a href="<?= h(app_url('agent.php?prompt=' . rawurlencode('Give me the current leadership risk briefing from the active records.'))) ?>">Discuss this briefing →</a>
    </aside>
</section>

<section class="metric-grid metric-grid-6 briefing-metrics">
    <?php foreach ($briefing['metrics'] as $metric): ?>
        <article class="metric-card"><span><?= h($metric['label']) ?></span><strong><?= h($metric['value']) ?></strong><small><?= h($metric['detail']) ?></small></article>
    <?php endforeach; ?>
</section>

<div class="briefing-layout">
    <section class="panel briefing-priority-panel">
        <header class="panel-head"><div><span class="eyebrow">Today’s operating priorities</span><h2>Decisions and escalations</h2></div><span class="panel-meta"><?= count($briefing['priorities']) ?> items</span></header>
        <div class="briefing-priority-list">
            <?php foreach ($briefing['priorities'] as $index => $priority): ?>
                <article class="briefing-priority-card severity-<?= h($priority['severity']) ?>">
                    <div class="briefing-priority-number"><?= str_pad((string) ($index + 1), 2, '0', STR_PAD_LEFT) ?></div>
                    <div class="briefing-priority-copy">
                        <span><?= h($priority['category']) ?></span>
                        <h3><?= h($priority['title']) ?></h3>
                        <p><?= h($priority['description']) ?></p>
                        <small><?= h($priority['meta']) ?></small>
                    </div>
                    <div class="briefing-priority-actions">
                        <a class="button secondary" href="<?= h($priority['href']) ?>"><?= h($priority['action_label']) ?></a>
                        <?php if (can('agent.view')): ?><a class="button ghost" href="<?= h(app_url('agent.php?prompt=' . rawurlencode($priority['agent_prompt']))) ?>">Ask Agent</a><?php endif; ?>
                    </div>
                </article>
            <?php endforeach; ?>
            <?php if (!$briefing['priorities']): ?><div class="dropdown-empty">No priority items are visible in this scope.</div><?php endif; ?>
        </div>
    </section>

    <aside class="panel briefing-opportunity-panel">
        <header class="panel-head"><div><span class="eyebrow">Value and avoidance</span><h2>Opportunities to review</h2></div></header>
        <div class="briefing-opportunity-list">
            <?php foreach ($briefing['opportunities'] as $opportunity): ?>
                <article>
                    <i><?= h($opportunity['icon']) ?></i>
                    <div><strong><?= h($opportunity['title']) ?></strong><p><?= h($opportunity['description']) ?></p><b><?= h($opportunity['value']) ?></b></div>
                    <a href="<?= h($opportunity['href']) ?>"><?= h($opportunity['action_label']) ?> →</a>
                </article>
            <?php endforeach; ?>
        </div>
        <div class="briefing-next-step">
            <span>Recommended cadence</span>
            <strong>Review priorities each morning, assign owners, then record decisions in the source workflow.</strong>
            <a href="<?= h(app_url('approvals.php')) ?>">Open Reviews & Approvals →</a>
        </div>
    </aside>
</div>
<?php render_app_end(); ?>
