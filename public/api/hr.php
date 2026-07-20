<?php
declare(strict_types=1);
require dirname(__DIR__, 2) . '/app/Bootstrap.php';
$user=App::requireAuth(true);$db=App::db();
$readRoles=['general-manager','executive-director','projects-director','project-manager','hr-manager','supervisor','site-engineer','worker'];
if(!in_array($user['role'],$readRoles,true)) App::json(['ok'=>false,'message'=>'غير مصرح'],403);

function employeeByCode(PDO $db,string $code): array {
    $q=$db->prepare('SELECT * FROM employees WHERE emp_code=? LIMIT 1');$q->execute([$code]);$e=$q->fetch();
    if(!$e) App::json(['ok'=>false,'message'=>'الموظف غير موجود'],404);return $e;
}

if($_SERVER['REQUEST_METHOD']==='GET'){
    if(($_GET['pending']??'')==='1'){
        if(!in_array($user['role'],['general-manager','executive-director','projects-director','project-manager','hr-manager'],true)) App::json(['ok'=>false,'message'=>'غير مصرح'],403);
        $leaves=$db->query("SELECT lr.id,e.emp_code,e.name AS employee_name,lt.name_ar AS request_name,lr.days_count,lr.starts_on,lr.status,'leave' AS request_kind FROM leave_requests lr JOIN employees e ON e.id=lr.employee_id JOIN leave_types lt ON lt.id=lr.leave_type_id WHERE lr.status='pending' ORDER BY lr.id DESC")->fetchAll();
        $transfers=$db->query("SELECT et.id,e.emp_code,e.name AS employee_name,tp.name AS request_name,et.effective_on AS starts_on,et.status,'transfer' AS request_kind FROM employee_transfers et JOIN employees e ON e.id=et.employee_id JOIN projects tp ON tp.id=et.to_project_id WHERE et.status='pending' ORDER BY et.id DESC")->fetchAll();
        App::json(['ok'=>true,'data'=>array_merge($leaves,$transfers)]);
    }
    $code=(string)($_GET['emp_code']??'');$e=employeeByCode($db,$code);$id=(int)$e['id'];
    $managerRead=in_array($user['role'],['general-manager','executive-director','projects-director','project-manager','hr-manager'],true);
    if(!$managerRead){$own=$db->prepare('SELECT employee_id FROM user_employee_links WHERE user_id=?');$own->execute([$user['id']]);if((int)$own->fetchColumn()!==$id)App::json(['ok'=>false,'message'=>'لا يمكنك الاطلاع على ملف موظف آخر'],403);}
    $one=function(string $sql)use($db,$id){$q=$db->prepare($sql);$q->execute([$id]);return $q->fetch()?:null;};
    $many=function(string $sql)use($db,$id){$q=$db->prepare($sql);$q->execute([$id]);return $q->fetchAll();};
    $contract=$one("SELECT * FROM employee_contracts WHERE employee_id=? AND status='active' ORDER BY id DESC LIMIT 1");
    $leaves=$many('SELECT lr.*,lt.name_ar AS leave_name FROM leave_requests lr JOIN leave_types lt ON lt.id=lr.leave_type_id WHERE lr.employee_id=? ORDER BY lr.id DESC LIMIT 30');
    $documents=$many('SELECT * FROM employee_documents WHERE employee_id=? ORDER BY id DESC');
    $custodies=$many('SELECT ca.*,c.code,c.name,c.category,c.serial_number FROM custody_assignments ca JOIN custodies c ON c.id=ca.custody_id WHERE ca.employee_id=? ORDER BY ca.id DESC');
    $transfers=$many('SELECT et.*,fp.code AS from_project_code,tp.code AS to_project_code,tp.name AS to_project_name FROM employee_transfers et LEFT JOIN projects fp ON fp.id=et.from_project_id JOIN projects tp ON tp.id=et.to_project_id WHERE et.employee_id=? ORDER BY et.id DESC LIMIT 20');
    $attendance=$many('SELECT * FROM attendance_records WHERE employee_id=? ORDER BY work_date DESC LIMIT 31');
    App::json(['ok'=>true,'data'=>['employee'=>$e,'contract'=>$contract,'leaves'=>$leaves,'documents'=>$documents,'custodies'=>$custodies,'transfers'=>$transfers,'attendance'=>$attendance]]);
}

App::verifyCsrf();
$writeRoles=['general-manager','executive-director','projects-director','project-manager','hr-manager'];
if(!in_array($user['role'],$writeRoles,true)) App::json(['ok'=>false,'message'=>'لا تملك صلاحية تنفيذ الإجراء'],403);
$in=App::input();$action=(string)($in['action']??'');
if($action==='approve_leave'||$action==='approve_transfer'){
    $table=$action==='approve_leave'?'leave_requests':'employee_transfers';$id=(int)($in['id']??0);$status=($in['decision']??'approved')==='rejected'?'rejected':'approved';
    $q=$db->prepare("UPDATE {$table} SET status=?,approved_by=?,approved_at=CURRENT_TIMESTAMP WHERE id=? AND status='pending'");$q->execute([$status,$user['id'],$id]);
    if($action==='approve_transfer'&&$status==='approved'&&$q->rowCount()>0){
        $t=$db->prepare('SELECT et.employee_id,e.emp_code,et.to_project_id,p.code AS project_code FROM employee_transfers et JOIN employees e ON e.id=et.employee_id JOIN projects p ON p.id=et.to_project_id WHERE et.id=?');$t->execute([$id]);$transfer=$t->fetch();
        if($transfer){
            $db->prepare('UPDATE employees SET project_id=? WHERE id=?')->execute([$transfer['to_project_id'],$transfer['employee_id']]);
            $s=$db->query("SELECT state_json FROM app_state WHERE state_key='company'")->fetch();
            if($s){
                $state=json_decode($s['state_json'],true);
                if(isset($state['employees'])&&is_array($state['employees'])){
                    foreach($state['employees'] as &$employee){if(($employee['id']??'')===$transfer['emp_code'])$employee['project']=$transfer['project_code'];}
                    unset($employee);
                }
                $db->prepare("UPDATE app_state SET state_json=?,version=version+1 WHERE state_key='company'")->execute([json_encode($state,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES)]);
            }
        }
    }
    App::audit($action,$table,(string)$id,['status'=>$status]);App::json(['ok'=>true,'message'=>'تم تحديث الطلب']);
}
$e=employeeByCode($db,(string)($in['emp_code']??''));$employeeId=(int)$e['id'];

if($action==='contract'){
    foreach(['contract_type','starts_on','basic_salary'] as $f) if(empty($in[$f])) App::json(['ok'=>false,'message'=>'بيانات العقد الأساسية ناقصة'],422);
    $db->prepare("UPDATE employee_contracts SET status='superseded' WHERE employee_id=? AND status='active'")->execute([$employeeId]);
    $q=$db->prepare('INSERT INTO employee_contracts(employee_id,contract_type,starts_on,ends_on,probation_ends_on,weekly_hours,basic_salary,housing_allowance,transport_allowance,other_allowances,status) VALUES(?,?,?,?,?,?,?,?,?,?,?)');
    $q->execute([$employeeId,$in['contract_type'],$in['starts_on'],($in['ends_on']??'')?:null,($in['probation_ends_on']??'')?:null,(float)($in['weekly_hours']??48),(float)$in['basic_salary'],(float)($in['housing_allowance']??0),(float)($in['transport_allowance']??0),(float)($in['other_allowances']??0),'active']);
    App::audit('contract.created','employees',$e['emp_code']);App::json(['ok'=>true,'message'=>'تم حفظ عقد الموظف'],201);
}
if($action==='leave'){
    $type=(string)($in['leave_type']??'annual');$t=$db->prepare('SELECT id FROM leave_types WHERE type_key=?');$t->execute([$type]);$typeId=$t->fetchColumn();
    if(empty($in['starts_on'])||empty($in['ends_on'])) App::json(['ok'=>false,'message'=>'تاريخا بداية ونهاية الإجازة مطلوبان'],422);
    try{$start=new DateTimeImmutable((string)$in['starts_on']);$end=new DateTimeImmutable((string)$in['ends_on']);}catch(Throwable $x){App::json(['ok'=>false,'message'=>'صيغة التاريخ غير صحيحة'],422);}$days=(int)$start->diff($end)->days+1;
    if(!$typeId||$end<$start) App::json(['ok'=>false,'message'=>'بيانات الإجازة غير صحيحة'],422);
    $q=$db->prepare('INSERT INTO leave_requests(employee_id,leave_type_id,starts_on,ends_on,days_count,reason,status,requested_by) VALUES(?,?,?,?,?,?,?,?)');$q->execute([$employeeId,$typeId,$in['starts_on'],$in['ends_on'],$days,$in['reason']??null,'pending',$user['id']]);
    App::audit('leave.requested','employees',$e['emp_code'],['days'=>$days]);App::json(['ok'=>true,'message'=>'تم إرسال طلب الإجازة للاعتماد'],201);
}
if($action==='transfer'){
    $p=$db->prepare('SELECT id FROM projects WHERE code=?');$p->execute([(string)$in['to_project_code']]);$toId=$p->fetchColumn();if(!$toId) App::json(['ok'=>false,'message'=>'المشروع الهدف غير موجود'],422);
    $q=$db->prepare('INSERT INTO employee_transfers(employee_id,from_project_id,to_project_id,effective_on,cost_allocation_percent,reason,status,requested_by) VALUES(?,?,?,?,?,?,?,?)');
    $q->execute([$employeeId,$e['project_id']?:null,$toId,$in['effective_on'],(float)($in['cost_allocation_percent']??100),$in['reason']??null,'pending',$user['id']]);
    App::audit('transfer.requested','employees',$e['emp_code'],['to_project_code'=>$in['to_project_code']]);App::json(['ok'=>true,'message'=>'تم إرسال طلب النقل للاعتماد'],201);
}
App::json(['ok'=>false,'message'=>'الإجراء غير معروف'],422);
