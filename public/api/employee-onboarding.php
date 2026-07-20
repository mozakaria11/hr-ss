<?php
declare(strict_types=1);
require dirname(__DIR__,2).'/app/Bootstrap.php';
$actor=App::requireAuth(true);
if (!App::can('hr.employee.create',$actor)) App::json(['ok'=>false,'message'=>'لا تملك صلاحية إضافة الموظفين'],403);
if ($_SERVER['REQUEST_METHOD']!=='POST') App::json(['ok'=>false,'message'=>'الطريقة غير مسموحة'],405);
App::verifyCsrf();
if (!App::columnExists('employees','national_id')) App::json(['ok'=>false,'message'=>'نفّذ ترقية قاعدة البيانات إلى الإصدار 1.9 أولًا'],409);

$in=$_POST;
$digits=fn(string $v):string=>strtr(trim($v),['٠'=>'0','١'=>'1','٢'=>'2','٣'=>'3','٤'=>'4','٥'=>'5','٦'=>'6','٧'=>'7','٨'=>'8','٩'=>'9']);
$name=trim((string)($in['name']??''));$nationalId=$digits((string)($in['national_id']??''));$email=mb_strtolower(trim((string)($in['email']??'')));
$password=(string)($in['password']??'');$jobTitle=trim((string)($in['job_title']??''));$role=(string)($in['role']??'worker');
$employeeType=trim((string)($in['employee_type']??''));$startedOn=(string)($in['started_on']??'');$mobile=$digits((string)($in['mobile_number']??''));
$allRoles=['worker','supervisor','site-engineer','project-manager','projects-director','hr-manager','executive-director','general-manager'];
$delegatedRoles=['worker','supervisor','site-engineer','project-manager'];
if ($actor['role']!=='general-manager' && !in_array($role,$delegatedRoles,true)) App::json(['ok'=>false,'message'=>'هذا الدور لا يمكن منحه إلا بواسطة المدير العام'],403);
if (!in_array($role,$allRoles,true)) App::json(['ok'=>false,'message'=>'دور المستخدم غير صالح'],422);
$errors=[];
if (mb_strlen($name)<3) $errors[]='اسم الموظف مطلوب';
if (!preg_match('/^\d{10}$/',$nationalId)) $errors[]='رقم الهوية/الإقامة يجب أن يتكون من 10 أرقام';
if (!filter_var($email,FILTER_VALIDATE_EMAIL)) $errors[]='البريد الإلكتروني غير صالح';
if (mb_strlen($password)<10) $errors[]='كلمة المرور يجب ألا تقل عن 10 أحرف';
if ($jobTitle==='') $errors[]='الوظيفة مطلوبة';
if ($employeeType==='') $errors[]='نوع الموظف مطلوب';
if (!preg_match('/^\d{4}-\d{2}-\d{2}$/',$startedOn)) $errors[]='تاريخ بداية العمل مطلوب';
if ($mobile!==''&&!preg_match('/^5\d{8}$/',$mobile)) $errors[]='رقم الجوال السعودي يجب أن يكون 9 أرقام ويبدأ بـ 5';
if ($errors) App::json(['ok'=>false,'message'=>implode('، ',$errors)],422);

$db=App::db();$profilePath=null;$absoluteProfile=null;
if (!empty($_FILES['profile_image']['name'])) {
    $file=$_FILES['profile_image'];
    if (($file['error']??UPLOAD_ERR_NO_FILE)!==UPLOAD_ERR_OK || (int)$file['size']>5*1024*1024) App::json(['ok'=>false,'message'=>'تعذر رفع الصورة أو أن حجمها يتجاوز 5 MB'],422);
    $mime=(new finfo(FILEINFO_MIME_TYPE))->file($file['tmp_name']);$ext=['image/jpeg'=>'jpg','image/png'=>'png','image/webp'=>'webp'][$mime]??null;
    if (!$ext) App::json(['ok'=>false,'message'=>'الصورة يجب أن تكون JPG أو PNG أو WebP'],422);
    $dir=dirname(__DIR__,2).'/storage/uploads/profiles';if(!is_dir($dir)&&!mkdir($dir,0770,true)&&!is_dir($dir))App::json(['ok'=>false,'message'=>'تعذر تجهيز مجلد الصور'],500);
    $profilePath='profiles/'.bin2hex(random_bytes(18)).'.'.$ext;$absoluteProfile=dirname(__DIR__,2).'/storage/uploads/'.$profilePath;
    if(!move_uploaded_file($file['tmp_name'],$absoluteProfile))App::json(['ok'=>false,'message'=>'تعذر حفظ الصورة الشخصية'],500);
}

try {
    $db->beginTransaction();
    $check=$db->prepare('SELECT COUNT(*) FROM users WHERE LOWER(email)=?');$check->execute([$email]);if($check->fetchColumn())throw new DomainException('البريد الإلكتروني مستخدم لحساب آخر');
    $check=$db->prepare('SELECT COUNT(*) FROM employees WHERE national_id=?');$check->execute([$nationalId]);if($check->fetchColumn())throw new DomainException('رقم الهوية مسجل لموظف آخر');
    $companyId=(int)$db->query('SELECT id FROM companies ORDER BY id LIMIT 1')->fetchColumn();$branchId=(int)$db->query('SELECT id FROM branches ORDER BY id LIMIT 1')->fetchColumn();
    if(!$companyId)throw new DomainException('يجب إنشاء الشركة أولًا');
    $projectId=null;$projectCode=trim((string)($in['project_code']??''));if($projectCode!==''){$q=$db->prepare('SELECT id FROM projects WHERE code=?');$q->execute([$projectCode]);$projectId=$q->fetchColumn()?:null;if(!$projectId)throw new DomainException('المشروع المحدد غير موجود');}
    do{$empCode='EMP-'.date('ymd').'-'.strtoupper(bin2hex(random_bytes(2)));$q=$db->prepare('SELECT COUNT(*) FROM employees WHERE emp_code=?');$q->execute([$empCode]);}while($q->fetchColumn());
    $q=$db->prepare('INSERT INTO employees(company_id,branch_id,project_id,emp_code,national_id,name,job_title,work_email,mobile_country_code,mobile_number,gender,nationality,birth_date,employee_type,sponsor_name,preferred_language,profile_image,started_on,base_salary,iqama_expiry,status) VALUES(?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)');
    $q->execute([$companyId,$branchId?:null,$projectId,$empCode,$nationalId,$name,$jobTitle,$email,'+966',$mobile?:null,($in['gender']??'')?:null,($in['nationality']??'')?:null,($in['birth_date']??'')?:null,$employeeType,($in['sponsor_name']??'')?:null,($in['preferred_language']??'ar')==='en'?'en':'ar',$profilePath,$startedOn,(float)($in['basic_salary']??0),($in['iqama_expiry']??'')?:null,'active']);
    $employeeId=(int)$db->lastInsertId();
    $q=$db->prepare('INSERT INTO users(name,email,password_hash,role,is_active) VALUES(?,?,?,?,1)');$q->execute([$name,$email,password_hash($password,PASSWORD_DEFAULT),$role]);$userId=(int)$db->lastInsertId();
    $db->prepare('INSERT INTO user_employee_links(user_id,employee_id,linked_by) VALUES(?,?,?)')->execute([$userId,$employeeId,$actor['id']]);
    $db->prepare('INSERT INTO employee_roles(employee_id,role_key,assigned_by) VALUES(?,?,?)')->execute([$employeeId,$role,$actor['id']]);
    $db->prepare('INSERT INTO employee_contracts(employee_id,contract_type,starts_on,weekly_hours,basic_salary,housing_allowance,transport_allowance,other_allowances,status) VALUES(?,?,?,?,?,?,?,?,?)')->execute([$employeeId,($in['contract_type']??'غير محدد المدة'),$startedOn,(float)($in['weekly_hours']??48),(float)($in['basic_salary']??0),(float)($in['housing_allowance']??0),(float)($in['transport_allowance']??0),(float)($in['other_allowances']??0),'active']);
    $state=$db->query("SELECT state_json FROM app_state WHERE state_key='company'")->fetchColumn();
    if($state){$data=json_decode((string)$state,true);if(is_array($data)){$data['employees'][]=['id'=>$empCode,'name'=>$name,'role'=>$jobTitle,'project'=>$projectCode,'branch'=>'المركز الرئيسي','status'=>'نشط','joined'=>$startedOn,'salary'=>(float)($in['basic_salary']??0),'iqamaExpiry'=>($in['iqama_expiry']??''),'nationalId'=>$nationalId,'email'=>$email,'mobile'=>$mobile,'accountActive'=>true];$db->prepare("UPDATE app_state SET state_json=?,version=version+1,updated_at=CURRENT_TIMESTAMP WHERE state_key='company'")->execute([json_encode($data,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES)]);}}
    $db->commit();
    App::audit('employee.onboarded','employees',$empCode,['user_id'=>$userId,'role'=>$role]);
    App::json(['ok'=>true,'message'=>'تم إنشاء الموظف وحساب الدخول والعقد بنجاح','data'=>['emp_code'=>$empCode,'name'=>$name,'email'=>$email,'national_id'=>$nationalId]],201);
} catch(DomainException $e) {
    if($db->inTransaction())$db->rollBack();if($absoluteProfile&&is_file($absoluteProfile))unlink($absoluteProfile);App::json(['ok'=>false,'message'=>$e->getMessage()],422);
} catch(Throwable $e) {
    if($db->inTransaction())$db->rollBack();if($absoluteProfile&&is_file($absoluteProfile))unlink($absoluteProfile);App::json(['ok'=>false,'message'=>'تعذر إنشاء الموظف. تحقق من عدم تكرار الهوية أو البريد ثم أعد المحاولة.'],500);
}
