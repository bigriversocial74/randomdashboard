<?php
declare(strict_types=1);
require_once dirname(__DIR__) . '/includes/app/bootstrap.php';
require_once dirname(__DIR__) . '/includes/app/demand_management.php';
require_app_user();
if(request_method()!=='POST')redirect_to(app_url('demand.php'));
verify_csrf();

function demand_valid_date(string $value,bool $allowBlank=false): ?string
{
    if($allowBlank&&$value==='')return null;
    $date=DateTimeImmutable::createFromFormat('!Y-m-d',$value);
    if(!$date||$date->format('Y-m-d')!==$value)throw new RuntimeException('Valid dates are required.');
    return $value;
}

try{
    $action=post_string('action');
    if($action==='save_request'){
        require_permission('purchase_orders.create');$id=post_int('id');$before=$id?demand_find_request($id):null;if($id&&!$before)throw new RuntimeException('The purchase request is outside the active company scope.');
        $companyId=post_int('company_id',data_default_company_id(current_user()));if(!data_company_within_scope($companyId))throw new RuntimeException('The selected company is outside your permitted scope.');
        $purpose=post_string('business_purpose','inventory_replenishment');if(!in_array($purpose,demand_business_purposes(),true))throw new RuntimeException('Select a valid business purpose.');
        $urgency=post_string('urgency','normal');if(!in_array($urgency,demand_urgencies(),true))throw new RuntimeException('Select a valid urgency.');
        $capex=post_string('capex_opex','operating_expense');if(!in_array($capex,demand_capex_opex(),true))throw new RuntimeException('Select operating or capital expense.');
        $justification=trim(post_string('justification'));$evidence=trim(post_string('evidence_note'));if($justification===''||$evidence==='')throw new RuntimeException('Business justification and governance evidence are required.');
        $required=demand_valid_date(post_string('required_date',date('Y-m-d',strtotime('+30 days'))));
        $requestNumber=trim(post_string('request_number',$before['request_number']??('REQ-'.date('Y').'-'.str_pad((string)(count(demand_requests())+1),4,'0',STR_PAD_LEFT))));
        if($requestNumber==='')throw new RuntimeException('A request number is required.');
        $record=['id'=>$id?:null,'request_number'=>$requestNumber,'company_id'=>$companyId,'location_id'=>post_int('location_id')?:null,'department_id'=>post_int('department_id')?:null,'requested_by'=>$before['requested_by']??(int)current_user()['id'],'project_workorder_id'=>post_int('project_workorder_id')?:null,'business_purpose'=>$purpose,'required_date'=>$required,'urgency'=>$urgency,'status'=>$before['status']??'draft','justification'=>mb_substr($justification,0,5000),'estimated_total'=>$before['estimated_total']??0,'submitted_at'=>$before['submitted_at']??null,'approved_at'=>$before['approved_at']??null,'owner_id'=>max(1,post_int('owner_id',(int)current_user()['id'])),'reviewer_id'=>max(1,post_int('reviewer_id',6)),'approval_id'=>$before['approval_id']??null,'budget_envelope_id'=>post_int('budget_envelope_id')?:($before['budget_envelope_id']??null),'sourcing_assessment_id'=>$before['sourcing_assessment_id']??null,'converted_po_id'=>$before['converted_po_id']??null,'capex_opex'=>$capex,'unplanned_demand'=>!empty($_POST['unplanned_demand']),'source_status'=>$before['source_status']??'not_assessed','evidence_note'=>mb_substr($evidence,0,5000),'created_at'=>$before['created_at']??date('Y-m-d H:i:s'),'updated_at'=>date('Y-m-d H:i:s')];
        $saved=demand_save_request($record);demand_add_event((int)$saved['id'],$before?'request_updated':'request_created',$before['status']??null,$saved['status'],'medium',$before?'Purchase request governance evidence updated.':'Purchase request created.');
        data_add_audit('Demand Governance',$before?'request_updated':'request_created','purchase_request',$saved['id'],$before,$saved,$saved['company_id']);flash('success','Purchase request saved: '.$saved['request_number'].'.');redirect_to(app_url('demand.php?id='.(int)$saved['id']));
    }
    if($action==='save_line'){
        require_permission('purchase_orders.create');$id=post_int('id');$before=$id?demand_find_line($id):null;if($id&&!$before)throw new RuntimeException('The request line is outside the active scope.');
        $requestId=post_int('purchase_request_id');$request=demand_find_request($requestId);if(!$request)throw new RuntimeException('The purchase request is outside the active scope.');if(!in_array(demand_effective_status($request),['draft','changes_requested'],true))throw new RuntimeException('Lines can be changed only while the request is draft or changes requested.');
        $description=trim(post_string('requested_description'));$quantity=max(0,(float)post_string('quantity','0'));$unitCost=max(0,(float)post_string('estimated_unit_cost','0'));$uom=strtoupper(trim(post_string('unit_of_measure','EA')));if($description===''||$quantity<=0||$uom==='')throw new RuntimeException('Description, quantity, and unit of measure are required.');
        $supplierId=post_int('preferred_supplier_id');if($supplierId>0&&!data_find('suppliers',$supplierId))throw new RuntimeException('Select a valid preferred supplier.');
        $record=['id'=>$id?:null,'purchase_request_id'=>$requestId,'item_id'=>post_int('item_id')?:null,'requested_description'=>mb_substr($description,0,500),'quantity'=>$quantity,'unit_of_measure'=>mb_substr($uom,0,32),'estimated_unit_cost'=>$unitCost,'preferred_supplier_id'=>$supplierId?:null,'specification_notes'=>mb_substr(trim(post_string('specification_notes')),0,5000),'created_at'=>$before['created_at']??date('Y-m-d H:i:s'),'updated_at'=>date('Y-m-d H:i:s')];
        $saved=demand_save_line($record);$request['estimated_total']=demand_request_total(demand_request_lines($requestId));$request['source_status']='not_assessed';$request['sourcing_assessment_id']=null;demand_save_request($request);demand_add_event($requestId,$before?'line_updated':'line_created',$request['status'],$request['status'],'medium',$saved['requested_description'].' · '.money(demand_line_total($saved)));
        data_add_audit('Demand Governance',$before?'request_line_updated':'request_line_created','purchase_request_line',$saved['id'],$before,$saved,$request['company_id']);flash('success','Purchase request line saved.');redirect_to(app_url('demand.php?id='.$requestId));
    }
    if($action==='save_budget'){
        require_permission('purchase_orders.approve');$companyId=post_int('company_id',data_default_company_id(current_user()));if(!data_company_within_scope($companyId))throw new RuntimeException('The selected company is outside your permitted scope.');
        $start=demand_valid_date(post_string('period_start',date('Y-01-01')));$end=demand_valid_date(post_string('period_end',date('Y-12-31')));if($end<$start)throw new RuntimeException('Budget end date must follow its start date.');
        $amount=max(0,(float)post_string('budget_amount','0'));$evidence=trim(post_string('evidence_note'));if($amount<=0||$evidence==='')throw new RuntimeException('Budget amount and evidence are required.');
        $saved=demand_save_budget(['id'=>null,'company_id'=>$companyId,'department_id'=>post_int('department_id')?:null,'project_workorder_id'=>post_int('project_workorder_id')?:null,'category_id'=>post_int('category_id')?:null,'period_start'=>$start,'period_end'=>$end,'budget_amount'=>$amount,'requested_amount'=>0,'approved_amount'=>0,'committed_amount'=>0,'actual_amount'=>0,'owner_id'=>max(1,post_int('owner_id',(int)current_user()['id'])),'status'=>'active','evidence_note'=>mb_substr($evidence,0,5000)]);
        data_add_audit('Demand Governance','budget_created','procurement_budget_envelope',$saved['id'],null,$saved,$companyId);flash('success','Budget envelope saved: '.$saved['budget_number'].'.');redirect_to(app_url('demand.php'));
    }
    if($action==='save_forecast'){
        require_permission('purchase_orders.create');$companyId=post_int('company_id',data_default_company_id(current_user()));if(!data_company_within_scope($companyId))throw new RuntimeException('The selected company is outside your permitted scope.');
        $start=demand_valid_date(post_string('period_start',date('Y-m-01',strtotime('+1 month'))));$end=demand_valid_date(post_string('period_end',date('Y-m-t',strtotime('+3 months'))));if($end<$start)throw new RuntimeException('Forecast end date must follow its start date.');
        $note=trim(post_string('source_note'));if($note==='')throw new RuntimeException('Forecast source evidence is required.');
        $saved=demand_save_forecast(['id'=>null,'company_id'=>$companyId,'category_id'=>post_int('category_id')?:null,'period_start'=>$start,'period_end'=>$end,'forecast_quantity'=>max(0,(float)post_string('forecast_quantity','0')),'forecast_value'=>max(0,(float)post_string('forecast_value','0')),'confidence_pct'=>max(0,min(100,(float)post_string('confidence_pct','50'))),'source_note'=>mb_substr($note,0,5000),'owner_id'=>max(1,post_int('owner_id',(int)current_user()['id'])),'status'=>'active']);
        data_add_audit('Demand Governance','forecast_created','procurement_demand_forecast',$saved['id'],null,$saved,$companyId);flash('success','Demand forecast saved: '.$saved['forecast_number'].'.');redirect_to(app_url('demand.php'));
    }
    if($action==='assess_request'){
        require_permission('purchase_orders.create');$id=post_int('id');$request=demand_find_request($id);if(!$request)throw new RuntimeException('The purchase request is outside the active scope.');$lines=demand_request_lines($id);$blueprint=demand_assessment_blueprint($request,$lines);
        $note=trim(post_string('evidence_note',$request['evidence_note']));if($note==='')throw new RuntimeException('Sourcing assessment evidence is required.');
        $assessment=demand_save_assessment(array_replace($blueprint,['purchase_request_id'=>$id,'company_id'=>$request['company_id'],'evidence_note'=>mb_substr($note,0,5000),'created_by'=>(int)current_user()['id']]));
        $before=$request;$request['sourcing_assessment_id']=(int)$assessment['id'];$request['budget_envelope_id']=$blueprint['budget']['id']??$request['budget_envelope_id'];$request['source_status']=$blueprint['recommended_action']==='approve'?'ready':($blueprint['recommended_action']==='revise_quantity'?'opportunity':'exception');$request['estimated_total']=$blueprint['requested_value'];demand_save_request($request);
        $severity=$blueprint['assessment_score']>=80?'medium':($blueprint['assessment_score']>=60?'high':'critical');demand_add_event($id,'assessment_completed',$before['source_status'],$request['source_status'],$severity,$assessment['assessment_number'].' · '.$assessment['evidence_note']);
        if($blueprint['inventory_avoidance_value']>0)demand_add_event($id,'inventory_opportunity',$request['status'],$request['status'],'medium','Potential inventory avoidance: '.money($blueprint['inventory_avoidance_value']).'.');
        if($blueprint['consolidation_value']>0)demand_add_event($id,'consolidation_opportunity',$request['status'],$request['status'],'medium','Potential consolidation value: '.money($blueprint['consolidation_value']).'.');
        data_add_audit('Demand Governance','sourcing_assessed','purchase_request',$id,$before,['request'=>$request,'assessment'=>$assessment],$request['company_id']);flash('success','Demand and sourcing assessment completed.');redirect_to(app_url('demand.php?id='.$id));
    }
    if($action==='submit_request'){
        require_permission('approvals.submit');$id=post_int('id');$request=demand_find_request($id);if(!$request)throw new RuntimeException('The purchase request is outside the active scope.');if(!in_array(demand_effective_status($request),['draft','changes_requested'],true))throw new RuntimeException('This request is already routed or closed.');
        $lines=demand_request_lines($id);$assessment=demand_assessments($id)[0]??null;if(!$assessment)throw new RuntimeException('Complete the sourcing assessment before submission.');$metrics=demand_metrics($request,$lines,$assessment);$before=$request;
        if(demand_requires_approval($request,$metrics)){
            $approval=data_upsert('workflow_approvals',['id'=>null,'company_id'=>$request['company_id'],'module'=>'purchase_orders','entity_type'=>'purchase_request','entity_id'=>$id,'title'=>$request['request_number'].' · '.money($metrics['requested_value']),'submitted_by'=>(int)current_user()['id'],'assigned_to'=>$request['reviewer_id'],'status'=>'pending','submitted_at'=>date('Y-m-d H:i:s'),'due_date'=>date('Y-m-d',strtotime($request['urgency']==='critical'?'+1 day':'+3 days')),'notes'=>'Review budget, inventory, duplicate demand, contract coverage, supplier performance, required-date risk, off-contract exposure, forecast, and sourcing recommendation.']);
            $request['approval_id']=(int)$approval['id'];$request['status']='submitted';$request['submitted_at']=date('Y-m-d H:i:s');demand_notify((int)$request['reviewer_id'],(int)$request['company_id'],'Purchase request awaiting approval',$request['request_number'].' requests '.money($metrics['requested_value']).'.',$request['urgency']==='critical'?'critical':'warning');
        }else{$request['status']='approved';$request['approved_at']=date('Y-m-d H:i:s');}
        demand_save_request($request);demand_add_event($id,'submitted',$before['status'],$request['status'],$request['urgency']==='critical'?'critical':'medium','Purchase request routed with sourcing and budget evidence.');data_add_audit('Demand Governance','request_submitted','purchase_request',$id,$before,$request,$request['company_id']);flash('success',$request['status']==='approved'?'Purchase request approved under policy.':'Purchase request routed to Reviews & Approvals.');redirect_to(app_url('demand.php?id='.$id));
    }
    if($action==='convert_request'){
        require_permission('purchase_orders.create');$id=post_int('id');$request=demand_find_request($id);if(!$request)throw new RuntimeException('The purchase request is outside the active scope.');$assessment=demand_assessments($id)[0]??null;if(!$assessment)throw new RuntimeException('A sourcing assessment is required.');$po=demand_convert_to_po($request,$assessment,demand_request_lines($id));data_add_audit('Demand Governance','converted_to_purchase_order','purchase_request',$id,$request,['purchase_order'=>$po],$request['company_id']);flash('success','Purchase request converted to '.$po['po_number'].'.');redirect_to(app_url('purchase-orders.php'));
    }
    if($action==='cancel_request'){
        require_permission('purchase_orders.edit');$id=post_int('id');$request=demand_find_request($id);if(!$request)throw new RuntimeException('The purchase request is outside the active scope.');if(in_array(demand_effective_status($request),['converted','canceled'],true))throw new RuntimeException('This request cannot be cancelled.');$before=$request;$request['status']='canceled';demand_save_request($request);demand_add_event($id,'canceled',$before['status'],'canceled','medium',mb_substr(trim(post_string('evidence_note','Request cancelled by an authorized procurement user.')),0,5000));data_add_audit('Demand Governance','request_canceled','purchase_request',$id,$before,$request,$request['company_id']);flash('success','Purchase request cancelled.');redirect_to(app_url('demand.php?id='.$id));
    }
    throw new RuntimeException('Unknown demand-governance action.');
}catch(Throwable $exception){flash('error','The demand-governance action could not be completed: '.$exception->getMessage());redirect_to(app_url('demand.php'));}
