<?php
declare(strict_types=1);

function supplier_portal_number(string $prefix,array $rows): string
{
    return $prefix.'-'.date('Y').'-'.str_pad((string)(count($rows)+1),4,'0',STR_PAD_LEFT);
}

function supplier_portal_internal_metrics(): array
{
    $pending=static fn(array $rows):int=>count(array_filter($rows,static fn(array $r):bool=>in_array((string)($r['status']??''),['submitted','changes_requested'],true)));
    return [
        'active_accounts'=>count(array_filter(supplier_portal_accounts(),static fn(array $r):bool=>($r['status']??'')==='active')),
        'pending_invitations'=>count(array_filter(supplier_portal_invitations(),static fn(array $r):bool=>($r['status']??'')==='pending')),
        'po_responses'=>$pending(supplier_portal_po_responses()),'asns'=>$pending(supplier_portal_asns()),
        'invoices'=>$pending(supplier_portal_invoice_submissions()),'documents'=>$pending(supplier_portal_documents()),
        'sourcing'=>$pending(supplier_portal_sourcing_submissions()),'quality'=>$pending(supplier_portal_quality_responses()),
        'open_messages'=>count(array_filter(supplier_portal_messages(),static fn(array $r):bool=>($r['status']??'')==='open')),
    ];
}

function supplier_portal_account_metrics(array $account): array
{
    $pos=supplier_portal_account_purchase_orders($account);
    $pastDue=count(array_filter($pos,static fn(array $po):bool=>($po['status']??'')==='past_due'||(!empty($po['expected_date'])&&$po['expected_date']<date('Y-m-d')&&!in_array((string)$po['status'],['received','closed','canceled'],true))));
    $messages=supplier_portal_account_records($account,'messages');
    $documents=supplier_portal_account_records($account,'documents');
    return [
        'open_pos'=>count($pos),'past_due_pos'=>$pastDue,
        'open_value'=>array_sum(array_map(static fn(array $po):float=>(float)($po['total_amount']??0),$pos)),
        'submitted_asns'=>count(supplier_portal_account_records($account,'asns')),
        'submitted_invoices'=>count(supplier_portal_account_records($account,'invoices')),
        'required_messages'=>count(array_filter($messages,static fn(array $m):bool=>($m['status']??'')==='open')),
        'expiring_documents'=>count(array_filter($documents,static fn(array $d):bool=>!empty($d['expiration_date'])&&$d['expiration_date']<=date('Y-m-d',strtotime('+45 days')))),
    ];
}
