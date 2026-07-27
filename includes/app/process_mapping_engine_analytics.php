<?php
declare(strict_types=1);

function process_mapping_analytics(): array
{
    $instances = process_mapping_instances();
    $stepInstances = process_mapping_step_instances();
    $processNames = [];
    foreach (process_mapping_processes() as $process) $processNames[(int)$process['id']] = $process['process_name'];
    $byProcess = [];
    foreach ($instances as $instance) {
        $name = $processNames[(int)$instance['process_id']] ?? 'Unknown';
        $byProcess[$name]['volume'] = ($byProcess[$name]['volume'] ?? 0) + 1;
        $byProcess[$name]['completed'] = ($byProcess[$name]['completed'] ?? 0) + ($instance['status'] === 'completed' ? 1 : 0);
        $byProcess[$name]['cycle_total'] = ($byProcess[$name]['cycle_total'] ?? 0) + (int)$instance['cycle_seconds'];
    }
    foreach ($byProcess as &$row) {
        $row['average_cycle_seconds'] = $row['completed'] ? intdiv($row['cycle_total'], $row['completed']) : 0;
        $row['completion_percent'] = round(($row['completed'] / $row['volume']) * 100, 1);
    }
    unset($row);
    $bottlenecks = [];
    foreach ($stepInstances as $stepInstance) {
        $step = process_mapping_find_step((int)$stepInstance['step_id']);
        if (!$step) continue;
        $name = $step['step_name'];
        $bottlenecks[$name]['elapsed'] = ($bottlenecks[$name]['elapsed'] ?? 0) + (int)$stepInstance['elapsed_seconds'];
        $bottlenecks[$name]['count'] = ($bottlenecks[$name]['count'] ?? 0) + 1;
        $bottlenecks[$name]['exceptions'] = ($bottlenecks[$name]['exceptions'] ?? 0) + ($stepInstance['status'] === 'exception' ? 1 : 0);
    }
    foreach ($bottlenecks as &$row) $row['average_seconds'] = $row['count'] ? intdiv($row['elapsed'], $row['count']) : 0;
    unset($row);
    uasort($bottlenecks, static fn(array $a, array $b): int => $b['average_seconds'] <=> $a['average_seconds']);
    return ['by_process'=>$byProcess,'bottlenecks'=>$bottlenecks];
}
