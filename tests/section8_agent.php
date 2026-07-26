<?php
declare(strict_types=1);
$root=dirname(__DIR__);
$context=file_get_contents($root.'/includes/app/agent_context.php');
$js=file_get_contents($root.'/app/assets/js/app.js');
foreach (['confidence','human_review','missing_data','approved_data_only'] as $needle) {
    if (!str_contains($context,$needle)) { fwrite(STDERR,"Agent context missing {$needle}.\n"); exit(1); }
}
foreach (['sanitizeHistory','activeThreadId','findPrompt(null, query)','messages: threadMessages.map'] as $needle) {
    if (!str_contains($js,$needle)) { fwrite(STDERR,"Agent history missing {$needle}.\n"); exit(1); }
}
if (str_contains($js,'entry.item?.response_title') || str_contains($js,'entry.item||fallbackFor')) {
    fwrite(STDERR,"Agent history still trusts persisted response objects.\n"); exit(1);
}
fwrite(STDOUT,"Section 8 Agent Workspace gates passed.\n");
