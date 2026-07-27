<?php
declare(strict_types=1);
require_once dirname(__DIR__) . '/includes/app/bootstrap.php';
require_once dirname(__DIR__) . '/includes/app/fulfillment_management.php';
require_app_user();
if(request_method()!=='POST')redirect_to(app_url('fulfillment.php'));
verify_csrf();

function fulfillment_valid_date(string $value,bool $allowBlank=false): ?string
{
    $value=trim($value);if($allowBlank&&$value==='')return null;$date=DateTimeImmutable::createFromFormat('!Y-m-d',$value);if(!$date||$date->format('Y-m-d')!==$value)throw new RuntimeException('Valid dates are required.');return $value;
}
function fulfillment_valid_datetime(string $value,bool $allowBlank=false): ?string
{
    $value=trim($value);if($allowBlank&&$value==='')return null;$timestamp=strtotime($value);if($timestamp===false)throw new RuntimeException('A valid date and time are required.');return date('Y-m-d H:i:s',$timestamp);
}
function fulfillment_contract_id_for_po(array $po): ?int
{
    $contract=demand_contract_for_supplier((int)$po['supplier_id'],(int)$po['company_id']);return $contract?(int)$contract['id']:null;
}

try{
    $action=post_string('action');
    require __DIR__ . '/fulfillment_action_receiving.php';
    require __DIR__ . '/fulfillment_action_invoices.php';
    require __DIR__ . '/fulfillment_action_exceptions.php';
    throw new RuntimeException('Unknown fulfillment-governance action.');
}catch(Throwable $exception){flash('error','The fulfillment-governance action could not be completed: '.$exception->getMessage());redirect_to(app_url('fulfillment.php'));}
