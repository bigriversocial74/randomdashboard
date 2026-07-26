<?php
declare(strict_types=1);

function mitigation_operational_status(array $record): string
{
    $stored=(string)($record['status']??'draft');
    if(in_array($stored,['active','contained','closed'],true))return $stored;
    return mitigation_effective_status($record);
}

function mitigation_export_record(array $record): array
{
    if(in_array((string)($record['status']??''),['active','contained','closed'],true))$record['approval_id']=null;
    return $record;
}
