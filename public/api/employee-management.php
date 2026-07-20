<?php
declare(strict_types=1);
require dirname(__DIR__,2).'/app/Bootstrap.php';
$actor=App::requireAuth(true);
if($_SERVER['REQUEST_METHOD']!=='PATCH'&&$_SERVER['REQUEST_METHOD']!=='PUT')App::json(['ok'=>false,'message'=>'الطريقة غير مسموحة'],405);
App::verifyCsrf();
if(!App::can('hr.employee.update',$actor))App::json(['ok'=>false,'message'=>'لا تملك صلاحية تعديل بيانات الموظفين'],403);
if(!App::columnExists('employees','national_id'))App::json(['ok'=>false,'message'=>'نفّذ ترقية قاعدة البيانات أولًا'],409);
$in=App::input();$code=trim((string)($in['emp_code']??''));$db=App::db();
$q=$db->prepare('SELECT e.*,l.user_id FROM employees e LEFT JOIN user_employee_links l ON l.employee_id=e.id WHERE e.emp_code=? LIMIT 1');$q->execute([$code]);$employee=$q->fetch();
if(!$employee)App::json(['ok'=>false,'message'=>'الموظف غير موجود'],404);
$digits=fn(string $v):string=>strtr(trim($v),['٠'=>'0','١'=>'1','٢'=>'2','٣'=>'3','٤'=>'4','٥'=>'5','٦'=>'6','٧'=>'7','٨'=>'8','٩'=>'9']);
$name=trim((string)($in['name']??''));$nationalId=$digits((string)($in['national_id']??''));$email=mb_strtolower(trim((string)($in['work_email']??'')));$mobile=$digits((string)($in['mobile_number']??''));
$jobTitle=trim((string)($in['job_title']??''));$employeeType=trim((string)($in['employee_type']??''));$status=(string)($in['status']??'active');
$errors=[];if(mb_strlen($name)<3)$errors[]='اسم الموظف غير صالح';if(!preg_match('/^\d{10}$/',$nationalId))$errors[]='الهوية يجب أن تكون 10 أرقام';if(!filter_var($email,FILTER_VALIDATE_EMAIL))$errors[]='البريد غير صالح';if($mobile!==''&&!preg_match('/^5\d{8}$/',$mobile))$errors[]='الجوال يجب أن يكون 9 أرقام ويبدأ بـ 5';if($jobTitle==='')$errors[]='الوظيفة مطلوبة';if($employeeType==='')$errors[]='نوع الموظف مطلوب';if(!in_array($status,['active','leave','inactive','terminated'],true))$errors[]='حالة الموظف غير صالحة';
foreach(['birth_date','started_on','iqama_expiry'] as $field){$value=(string)($in[$field]??'');if($value!==''&&!preg_match('/^\d{4}-\d{2}-\d{2}$/',$value))$errors[]='صيغة التاريخ غير صحيحة';}
if($errors)App::json(['ok'=>false,'message'=>implode('، ',$errors)],422);
$projectCode=trim((string)($in['project_code']??''));$projectId=null;if($projectCode!==''){$p=$db->prepare('SELECT id FROM projects WHERE code=?');$p->execute([$projectCode]);$projectId=$p->fetchColumn()?:null;if(!$projectId)App::json(['ok'=>false,'message'=>'المشروع المحدد غير موجود'],422);}
$values=[$projectId,$nationalId,$name,$jobTitle,$email,$mobile?:null,($in['gender']??'')?:null,($in['nationality']??'')?:null,($in['birth_date']??'')?:null,$employeeType,($in['sponsor_name']??'')?:null,($in['preferred_language']??'ar')==='en'?'en':'ar',($in['started_on']??'')?:null,(float)($in['base_salary']??0),($in['iqama_expiry']??'')?:null,$status,$employee['id']];
try{
    $db->beginTransaction();
    $db->prepare('UPDATE employees SET project_id=?,national_id=?,name=?,job_title=?,work_email=?,mobile_number=?,gender=?,nationality=?,birth_date=?,employee_type=?,sponsor_name=?,preferred_language=?,started_on=?,base_salary=?,iqama_expiry=?,status=? WHERE id=?')->execute($values);
    if($employee['user_id'])$db->prepare('UPDATE users SET name=?,email=? WHERE id=?')->execute([$name,$email,$employee['user_id']]);
    $db->prepare("UPDATE employee_contracts SET basic_salary=? WHERE employee_id=? AND status='active'")->execute([(float)($in['base_salary']??0),$employee['id']]);
    $state=$db->query("SELECT state_json FROM app_state WHERE state_key='company'")->fetchColumn();
    if($state){$data=json_decode((string)$state,true);if(is_array($data)&&isset($data['employees'])){foreach($data['employees'] as &$row){if(($row['id']??'')===$code){$row['name']=$name;$row['role']=$jobTitle;$row['project']=$projectCode;$row['status']=['active'=>'نشط','leave'=>'إجازة','inactive'=>'غير نشط','terminated'=>'منتهي الخدمة'][$status];$row['salary']=(float)($in['base_salary']??0);$row['iqamaExpiry']=(string)($in['iqama_expiry']??'');$row['joined']=(string)($in['started_on']??'');$row['nationalId']=$nationalId;$row['email']=$email;$row['mobile']=$mobile;break;}}unset($row);$db->prepare("UPDATE app_state SET state_json=?,version=version+1,updated_at=CURRENT_TIMESTAMP WHERE state_key='company'")->execute([json_encode($data,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES)]);}}
    $db->commit();App::audit('employee.updated','employees',$code,['fields'=>['project','identity','name','job','email','mobile','personal','employment','salary','status']]);App::json(['ok'=>true,'message'=>'تم تحديث بيانات الموظف','data'=>['emp_code'=>$code]]);
}catch(Throwable $e){if($db->inTransaction())$db->rollBack();App::json(['ok'=>false,'message'=>'تعذر حفظ التعديلات؛ تحقق من عدم تكرار الهوية أو البريد'],422);}
