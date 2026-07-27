<?php
declare(strict_types=1);

function supplier_portal_tables(): array
{
    return [
        'supplier_portal_accounts','supplier_portal_invitations','supplier_portal_access_grants',
        'supplier_purchase_order_responses','supplier_shipment_notices','supplier_shipment_notice_lines',
        'supplier_invoice_submissions','supplier_invoice_submission_lines','supplier_document_submissions',
        'supplier_sourcing_submissions','supplier_quality_responses','supplier_collaboration_messages','supplier_portal_events',
    ];
}

function supplier_portal_tables_ready(): bool
{
    if (data_is_demo()) return true;
    $pdo=production_database_connection();
    if (!$pdo) return false;
    try {
        $tables=supplier_portal_tables();
        $stmt=$pdo->prepare('SELECT COUNT(*) FROM information_schema.tables WHERE table_schema=DATABASE() AND table_name IN ('.implode(',',array_fill(0,count($tables),'?')).')');
        $stmt->execute($tables);
        return (int)$stmt->fetchColumn()===count($tables);
    } catch (Throwable) {
        return false;
    }
}

function supplier_portal_require_tables(): void
{
    if (!supplier_portal_tables_ready()) {
        throw new RuntimeException('Section 22 migration is required before Supplier Portal writes can be used.');
    }
}

function supplier_portal_demo_collection(string $key, callable $seed): array
{
    if (!isset($_SESSION['gruber_demo_state'][$key])) $_SESSION['gruber_demo_state'][$key]=$seed();
    return array_values($_SESSION['gruber_demo_state'][$key]);
}

function supplier_portal_demo_save(string $key,array $record,callable $seed): array
{
    $rows=supplier_portal_demo_collection($key,$seed);
    $id=(int)($record['id']??0);
    if ($id<=0) {
        $id=max([0,...array_map('intval',array_column($rows,'id'))])+1;
        $record['id']=$id;
        $rows[]=$record;
    } else {
        $found=false;
        foreach ($rows as $index=>$row) {
            if ((int)$row['id']===$id) {
                $rows[$index]=array_replace($row,$record);
                $record=$rows[$index];
                $found=true;
                break;
            }
        }
        if (!$found) $rows[]=$record;
    }
    $_SESSION['gruber_demo_state'][$key]=array_values($rows);
    return $record;
}

function supplier_portal_internal_supplier_ids(): array
{
    return array_map('intval',array_column(data_visible_collection('suppliers'),'id'));
}

function supplier_portal_supplier_visible(int $supplierId): bool
{
    return in_array($supplierId,supplier_portal_internal_supplier_ids(),true);
}

function supplier_portal_rows(string $table,string $demoKey,callable $seed,string $order='id DESC'): array
{
    if (data_is_demo()) return supplier_portal_demo_collection($demoKey,$seed);
    if (!supplier_portal_tables_ready()) return [];
    return production_database_connection()->query("SELECT * FROM {$table} ORDER BY {$order}")->fetchAll();
}

function supplier_portal_context_supplier_ids(): array
{
    if(function_exists('supplier_portal_current_account')){
        $account=supplier_portal_current_account();
        if($account)return[(int)$account['supplier_id']];
    }
    return supplier_portal_internal_supplier_ids();
}

function supplier_portal_filter_internal(array $rows,string $supplierField='supplier_id'): array
{
    $visible=supplier_portal_context_supplier_ids();
    return array_values(array_filter($rows,static fn(array $row):bool=>in_array((int)($row[$supplierField]??0),$visible,true)));
}

function supplier_portal_accounts(): array
{
    return supplier_portal_filter_internal(supplier_portal_rows('supplier_portal_accounts','supplier_portal_accounts','supplier_portal_demo_accounts','updated_at DESC,id DESC'));
}

function supplier_portal_invitations(): array
{
    return supplier_portal_filter_internal(supplier_portal_rows('supplier_portal_invitations','supplier_portal_invitations','supplier_portal_demo_invitations','created_at DESC,id DESC'));
}

function supplier_portal_access_grants(?int $accountId=null): array
{
    $rows=supplier_portal_rows('supplier_portal_access_grants','supplier_portal_access_grants','supplier_portal_demo_grants','id');
    $accountMap=[];foreach(supplier_portal_accounts() as $account)$accountMap[(int)$account['id']]=true;
    $rows=array_values(array_filter($rows,static fn(array $row):bool=>isset($accountMap[(int)$row['account_id']])));
    return $accountId===null?$rows:array_values(array_filter($rows,static fn(array $row):bool=>(int)$row['account_id']===$accountId));
}

function supplier_portal_po_responses(?int $supplierId=null): array
{
    $rows=supplier_portal_filter_internal(supplier_portal_rows('supplier_purchase_order_responses','supplier_purchase_order_responses','supplier_portal_demo_po_responses','updated_at DESC,id DESC'));
    return $supplierId===null?$rows:array_values(array_filter($rows,static fn(array $r):bool=>(int)$r['supplier_id']===$supplierId));
}
function supplier_portal_asns(?int $supplierId=null): array
{
    $rows=supplier_portal_filter_internal(supplier_portal_rows('supplier_shipment_notices','supplier_shipment_notices','supplier_portal_demo_asns','updated_at DESC,id DESC'));
    return $supplierId===null?$rows:array_values(array_filter($rows,static fn(array $r):bool=>(int)$r['supplier_id']===$supplierId));
}
function supplier_portal_asn_lines(?int $asnId=null): array
{
    $rows=supplier_portal_rows('supplier_shipment_notice_lines','supplier_shipment_notice_lines','supplier_portal_demo_asn_lines','id');
    if ($asnId===null) return $rows;
    return array_values(array_filter($rows,static fn(array $r):bool=>(int)$r['shipment_notice_id']===$asnId));
}
function supplier_portal_invoice_submissions(?int $supplierId=null): array
{
    $rows=supplier_portal_filter_internal(supplier_portal_rows('supplier_invoice_submissions','supplier_invoice_submissions','supplier_portal_demo_invoice_submissions','updated_at DESC,id DESC'));
    return $supplierId===null?$rows:array_values(array_filter($rows,static fn(array $r):bool=>(int)$r['supplier_id']===$supplierId));
}
function supplier_portal_invoice_lines(?int $submissionId=null): array
{
    $rows=supplier_portal_rows('supplier_invoice_submission_lines','supplier_invoice_submission_lines','supplier_portal_demo_invoice_lines','id');
    if ($submissionId===null) return $rows;
    return array_values(array_filter($rows,static fn(array $r):bool=>(int)$r['invoice_submission_id']===$submissionId));
}
function supplier_portal_documents(?int $supplierId=null): array
{
    $rows=supplier_portal_filter_internal(supplier_portal_rows('supplier_document_submissions','supplier_document_submissions','supplier_portal_demo_documents','updated_at DESC,id DESC'));
    return $supplierId===null?$rows:array_values(array_filter($rows,static fn(array $r):bool=>(int)$r['supplier_id']===$supplierId));
}
function supplier_portal_sourcing_submissions(?int $supplierId=null): array
{
    $rows=supplier_portal_filter_internal(supplier_portal_rows('supplier_sourcing_submissions','supplier_sourcing_submissions','supplier_portal_demo_sourcing_submissions','updated_at DESC,id DESC'));
    return $supplierId===null?$rows:array_values(array_filter($rows,static fn(array $r):bool=>(int)$r['supplier_id']===$supplierId));
}
function supplier_portal_quality_responses(?int $supplierId=null): array
{
    $rows=supplier_portal_filter_internal(supplier_portal_rows('supplier_quality_responses','supplier_quality_responses','supplier_portal_demo_quality_responses','updated_at DESC,id DESC'));
    return $supplierId===null?$rows:array_values(array_filter($rows,static fn(array $r):bool=>(int)$r['supplier_id']===$supplierId));
}
function supplier_portal_messages(?int $supplierId=null): array
{
    $rows=supplier_portal_filter_internal(supplier_portal_rows('supplier_collaboration_messages','supplier_collaboration_messages','supplier_portal_demo_messages','updated_at DESC,id DESC'));
    return $supplierId===null?$rows:array_values(array_filter($rows,static fn(array $r):bool=>(int)$r['supplier_id']===$supplierId));
}
function supplier_portal_events(?int $supplierId=null): array
{
    $rows=supplier_portal_filter_internal(supplier_portal_rows('supplier_portal_events','supplier_portal_events','supplier_portal_demo_events','created_at DESC,id DESC'));
    return $supplierId===null?$rows:array_values(array_filter($rows,static fn(array $r):bool=>(int)$r['supplier_id']===$supplierId));
}

function supplier_portal_find(array $rows,int $id): ?array
{
    foreach($rows as $row) if((int)$row['id']===$id) return $row;
    return null;
}
function supplier_portal_find_account(int $id): ?array{return supplier_portal_find(supplier_portal_accounts(),$id);}
function supplier_portal_find_invitation(int $id): ?array{return supplier_portal_find(supplier_portal_invitations(),$id);}
function supplier_portal_find_po_response(int $id): ?array{return supplier_portal_find(supplier_portal_po_responses(),$id);}
function supplier_portal_find_asn(int $id): ?array{return supplier_portal_find(supplier_portal_asns(),$id);}
function supplier_portal_find_invoice_submission(int $id): ?array{return supplier_portal_find(supplier_portal_invoice_submissions(),$id);}
function supplier_portal_find_document(int $id): ?array{return supplier_portal_find(supplier_portal_documents(),$id);}
function supplier_portal_find_sourcing_submission(int $id): ?array{return supplier_portal_find(supplier_portal_sourcing_submissions(),$id);}
function supplier_portal_find_quality_response(int $id): ?array{return supplier_portal_find(supplier_portal_quality_responses(),$id);}
function supplier_portal_find_message(int $id): ?array{return supplier_portal_find(supplier_portal_messages(),$id);}

