<?php
declare(strict_types=1);

function process_mapping_template_catalog(): array
{
    return [
        'procure_to_pay' => [
            'name' => 'Procure-to-Pay',
            'category' => 'source_to_pay',
            'value_chain' => 'Demand → Source → Purchase → Receive → Pay → Reconcile',
            'purpose' => 'Coordinate local demand, shared procurement, supplier collaboration, receiving, invoice matching, payment, and reconciliation.',
            'modules' => ['demand','purchase_orders','supplier_portal','fulfillment','accounts_payable'],
            'lanes' => [
                ['code'=>'requesting_business','name'=>'Requesting Business','participant_type'=>'entity','entity_code'=>'GPS','role_code'=>'requester'],
                ['code'=>'shared_procurement','name'=>'Shared Procurement','participant_type'=>'entity','entity_code'=>'SHARED-PROC','role_code'=>'procurement_manager'],
                ['code'=>'supplier','name'=>'Supplier','participant_type'=>'external','entity_code'=>null,'role_code'=>'supplier'],
                ['code'=>'receiving_business','name'=>'Receiving Business','participant_type'=>'entity','entity_code'=>'GPS','role_code'=>'receiver'],
                ['code'=>'shared_finance','name'=>'Shared Finance / AP','participant_type'=>'entity','entity_code'=>'SHARED-FIN','role_code'=>'reviewer'],
            ],
            'steps' => [
                ['code'=>'start','name'=>'Purchase need identified','type'=>'start','lane'=>'requesting_business','x'=>220,'y'=>70,'permission'=>'platform.view','sla'=>0,'evidence'=>false,'record'=>'purchase_requisition'],
                ['code'=>'create_request','name'=>'Create purchase request','type'=>'task','lane'=>'requesting_business','x'=>380,'y'=>70,'permission'=>'purchase_orders.create','sla'=>480,'evidence'=>true,'record'=>'purchase_requisition'],
                ['code'=>'budget_review','name'=>'Budget validation','type'=>'approval','lane'=>'requesting_business','x'=>570,'y'=>70,'permission'=>'approvals.review','sla'=>480,'evidence'=>true,'record'=>'purchase_requisition'],
                ['code'=>'approved_gateway','name'=>'Approved?','type'=>'gateway','lane'=>'requesting_business','x'=>760,'y'=>70,'permission'=>'approvals.approve','sla'=>120,'evidence'=>true,'record'=>'purchase_requisition'],
                ['code'=>'source_supplier','name'=>'Validate supplier and contract','type'=>'task','lane'=>'shared_procurement','x'=>920,'y'=>230,'permission'=>'suppliers.review','sla'=>960,'evidence'=>true,'record'=>'supplier'],
                ['code'=>'issue_po','name'=>'Issue purchase order','type'=>'approval','lane'=>'shared_procurement','x'=>1110,'y'=>230,'permission'=>'purchase_orders.approve','sla'=>480,'evidence'=>true,'record'=>'purchase_order','event'=>'purchase_order.issued'],
                ['code'=>'supplier_ack','name'=>'Acknowledge purchase order','type'=>'external','lane'=>'supplier','x'=>1110,'y'=>390,'permission'=>'supplier_portal.view','sla'=>1440,'evidence'=>true,'record'=>'supplier_purchase_order_response','event'=>'purchase_order.acknowledged'],
                ['code'=>'submit_asn','name'=>'Submit advance shipment notice','type'=>'integration','lane'=>'supplier','x'=>920,'y'=>390,'permission'=>'supplier_portal.view','sla'=>2880,'evidence'=>true,'record'=>'supplier_shipment_notice','event'=>'shipment_notice.submitted'],
                ['code'=>'receive_goods','name'=>'Receive and inspect goods','type'=>'task','lane'=>'receiving_business','x'=>730,'y'=>550,'permission'=>'inventory.edit','sla'=>480,'evidence'=>true,'record'=>'purchase_order_receipt','event'=>'receipt.completed'],
                ['code'=>'invoice_match','name'=>'Three-way invoice match','type'=>'control','lane'=>'shared_finance','x'=>920,'y'=>710,'permission'=>'accounts_payable.review','sla'=>480,'evidence'=>true,'record'=>'supplier_invoice','event'=>'invoice.matched'],
                ['code'=>'payment_batch','name'=>'Schedule and approve payment','type'=>'approval','lane'=>'shared_finance','x'=>1110,'y'=>710,'permission'=>'accounts_payable.approve','sla'=>1440,'evidence'=>true,'record'=>'ap_payment_batch','event'=>'payment_batch.approved'],
                ['code'=>'settle_reconcile','name'=>'Settle and reconcile','type'=>'integration','lane'=>'shared_finance','x'=>1300,'y'=>710,'permission'=>'accounts_payable.reconcile','sla'=>1440,'evidence'=>true,'record'=>'ap_reconciliation','event'=>'payment.reconciled'],
                ['code'=>'return_request','name'=>'Return for correction','type'=>'exception','lane'=>'requesting_business','x'=>760,'y'=>120,'permission'=>'purchase_orders.edit','sla'=>240,'evidence'=>true,'record'=>'purchase_requisition'],
                ['code'=>'end','name'=>'Process completed','type'=>'end','lane'=>'shared_finance','x'=>1490,'y'=>710,'permission'=>'platform.view','sla'=>0,'evidence'=>false,'record'=>'ap_reconciliation'],
            ],
            'transitions' => [
                ['from'=>'start','to'=>'create_request','label'=>'Begin','default'=>true],
                ['from'=>'create_request','to'=>'budget_review','label'=>'Submitted','default'=>true],
                ['from'=>'budget_review','to'=>'approved_gateway','label'=>'Reviewed','default'=>true],
                ['from'=>'approved_gateway','to'=>'source_supplier','label'=>'Approved','default'=>true],
                ['from'=>'approved_gateway','to'=>'return_request','label'=>'Changes required','default'=>false,'exception'=>true],
                ['from'=>'return_request','to'=>'create_request','label'=>'Resubmit','default'=>true],
                ['from'=>'source_supplier','to'=>'issue_po','label'=>'Supplier validated','default'=>true],
                ['from'=>'issue_po','to'=>'supplier_ack','label'=>'PO sent','default'=>true],
                ['from'=>'supplier_ack','to'=>'submit_asn','label'=>'Acknowledged','default'=>true],
                ['from'=>'submit_asn','to'=>'receive_goods','label'=>'Shipment received','default'=>true],
                ['from'=>'receive_goods','to'=>'invoice_match','label'=>'Receipt posted','default'=>true],
                ['from'=>'invoice_match','to'=>'payment_batch','label'=>'Match cleared','default'=>true],
                ['from'=>'payment_batch','to'=>'settle_reconcile','label'=>'Released','default'=>true],
                ['from'=>'settle_reconcile','to'=>'end','label'=>'Reconciled','default'=>true],
            ],
        ],
        'supplier_onboarding' => process_mapping_simple_template('Supplier Onboarding','supplier_management','Request → Validate → Risk Review → Approve → Activate',['suppliers','supplier_portal'],['Requesting Business','Shared Procurement','Supplier','Risk & Compliance'],['Submit supplier request','Collect supplier profile','Validate tax and insurance','Risk and compliance review','Approve supplier','Activate supplier']),
        'strategic_sourcing' => process_mapping_simple_template('Strategic Sourcing','source_to_contract','Demand → Market Analysis → Event → Evaluate → Award',['strategy','suppliers'],['Category Team','Business Stakeholders','Suppliers','Approval Board'],['Confirm sourcing need','Build sourcing strategy','Publish sourcing event','Receive proposals','Evaluate and compare','Approve award','Record decision']),
        'contract_renewal' => process_mapping_simple_template('Contract Renewal','contract_lifecycle','Monitor → Review → Negotiate → Approve → Renew or Exit',['strategy','suppliers'],['Contract Owner','Shared Procurement','Legal / Risk','Supplier','Executive Approver'],['Detect renewal window','Review performance and obligations','Select renewal strategy','Negotiate terms','Approve renewal decision','Execute renewal or exit']),
        'inventory_replenishment' => process_mapping_simple_template('Inventory Replenishment','plan_to_stock','Demand Signal → Replenish → Receive → Put Away → Verify',['inventory','purchase_orders'],['Inventory Operations','Shared Procurement','Supplier','Receiving'],['Detect replenishment need','Check transfer availability','Approve replenishment','Issue replenishment order','Receive stock','Put away and verify']),
        'invoice_to_payment' => process_mapping_simple_template('Invoice-to-Payment','accounts_payable','Invoice → Match → Approve → Pay → Reconcile',['fulfillment','accounts_payable'],['Supplier','Accounts Payable','Procurement','Treasury / Bank'],['Receive supplier invoice','Validate duplicate and terms','Three-way match','Resolve exceptions','Approve payment','Execute settlement','Reconcile payment']),
        'supplier_corrective_action' => process_mapping_simple_template('Supplier Corrective Action','supplier_quality','Issue → Contain → Root Cause → Correct → Verify → Close',['scorecards','supplier_portal'],['Quality Team','Supplier','Procurement Owner','Executive Reviewer'],['Open corrective action','Supplier containment','Root cause analysis','Corrective action plan','Verify effectiveness','Approve closure']),
        'savings_realization' => process_mapping_simple_template('Savings Realization','value_management','Identify → Validate → Approve → Implement → Finance Certify',['savings','strategy'],['Procurement','Business Owner','Finance','Executive Sponsor'],['Identify opportunity','Build business case','Approve initiative','Implement change','Measure actuals','Finance validate','Certify realization']),
        'financial_close' => process_mapping_simple_template('Financial Close','record_to_report','Cutoff → Accrue → Reconcile → Certify → Lock',['accounts_payable'],['Operating Companies','Shared Finance / AP','Controller','Executive'],['Open close checklist','Apply invoice and receipt cutoff','Calculate GRNI accruals','Reconcile unsettled payments','Review credits and exceptions','Controller certification','Lock accounting period']),
    ];
}

function process_mapping_simple_template(string $name,string $category,string $valueChain,array $modules,array $laneNames,array $stepNames): array
{
    $lanes=[];foreach($laneNames as$i=>$laneName)$lanes[]=['code'=>'lane_'.($i+1),'name'=>$laneName,'participant_type'=>str_contains(strtolower($laneName),'supplier')?'external':'role','entity_code'=>null,'role_code'=>strtolower(str_replace([' ','/','&'],['_','','and'],$laneName))];
    $steps=[];$transitions=[];$x=220;$laneCount=max(1,count($lanes));foreach($stepNames as$i=>$stepName){$lane='lane_'.(($i%$laneCount)+1);$type=$i===0?'start':($i===count($stepNames)-1?'end':(str_contains(strtolower($stepName),'approve')||str_contains(strtolower($stepName),'certif')?'approval':'task'));$steps[]=['code'=>'step_'.($i+1),'name'=>$stepName,'type'=>$type,'lane'=>$lane,'x'=>$x+($i*185),'y'=>70+(($i%$laneCount)*160),'permission'=>'platform.view','sla'=>$type==='start'||$type==='end'?0:480,'evidence'=>$type!=='start'&&$type!=='end','record'=>'business_process'];if($i>0)$transitions[]=['from'=>'step_'.$i,'to'=>'step_'.($i+1),'label'=>'Continue','default'=>true];}
    return ['name'=>$name,'category'=>$category,'value_chain'=>$valueChain,'purpose'=>'Reusable governed '.$name.' operating model.','modules'=>$modules,'lanes'=>$lanes,'steps'=>$steps,'transitions'=>$transitions];
}

function process_mapping_template_preview(string $code): array
{
    $catalog=process_mapping_template_catalog();$template=$catalog[$code]??null;if(!$template)return ['valid'=>false,'errors'=>['Unknown process template.']];
    $stepCodes=array_column($template['steps'],'code');$errors=[];if(count($stepCodes)!==count(array_unique($stepCodes)))$errors[]='Step codes must be unique.';foreach($template['transitions'] as$t){if(!in_array($t['from'],$stepCodes,true)||!in_array($t['to'],$stepCodes,true))$errors[]='Every transition must reference valid steps.';}
    return ['valid'=>!$errors,'errors'=>$errors,'code'=>$code,'name'=>$template['name'],'purpose'=>$template['purpose'],'value_chain'=>$template['value_chain'],'modules'=>$template['modules'],'lane_count'=>count($template['lanes']),'step_count'=>count($template['steps']),'transition_count'=>count($template['transitions']),'template'=>$template];
}
