<?php
declare(strict_types=1);
require dirname(__DIR__, 2) . '/app/Bootstrap.php';
$admin = App::requireAuth(true);
if (!App::can('hr.employee.account.manage',$admin) && $admin['role']!=='general-manager') App::json(['ok'=>false,'message'=>'لا تملك صلاحية إدارة حسابات الموظفين'], 403);
$db = App::db();
$roles = ['worker','supervisor','site-engineer','project-manager','projects-director','hr-manager','executive-director','general-manager'];

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    App::json(['ok'=>true,'data'=>$db->query('SELECT u.id,u.name,u.email,u.role,u.is_active,u.created_at,e.emp_code,e.national_id,e.name AS employee_name FROM users u LEFT JOIN user_employee_links l ON l.user_id=u.id LEFT JOIN employees e ON e.id=l.employee_id ORDER BY u.id')->fetchAll()]);
}
App::verifyCsrf();
$input = App::input();
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = mb_strtolower(trim((string)($input['email'] ?? '')));
    $password = (string)($input['password'] ?? '');
    $role = (string)($input['role'] ?? 'worker');
    if($admin['role']!=='general-manager'&&!in_array($role,['worker','supervisor','site-engineer','project-manager'],true)) App::json(['ok'=>false,'message'=>'هذا الدور لا يمنحه إلا المدير العام'],403);
    $nationalId=strtr(trim((string)($input['national_id']??'')),['٠'=>'0','١'=>'1','٢'=>'2','٣'=>'3','٤'=>'4','٥'=>'5','٦'=>'6','٧'=>'7','٨'=>'8','٩'=>'9']);
    if (!filter_var($email, FILTER_VALIDATE_EMAIL) || mb_strlen($password) < 10 || !in_array($role,$roles,true) || !preg_match('/^\d{10}$/',$nationalId)) App::json(['ok'=>false,'message'=>'تحقق من الهوية (10 أرقام) والبريد وكلمة المرور والدور'], 422);
    $employeeId=null;if(!empty($input['emp_code'])){$e=$db->prepare('SELECT id FROM employees WHERE emp_code=?');$e->execute([$input['emp_code']]);$employeeId=$e->fetchColumn();if(!$employeeId)App::json(['ok'=>false,'message'=>'الرقم الوظيفي غير موجود'],422);}
    if(!$employeeId)App::json(['ok'=>false,'message'=>'اختر الموظف المراد تفعيل حسابه'],422);
    try{$db->beginTransaction();$db->prepare('UPDATE employees SET national_id=?,work_email=? WHERE id=?')->execute([$nationalId,$email,$employeeId]);$q=$db->prepare('INSERT INTO users(name,email,password_hash,role,is_active) VALUES(?,?,?,?,1)');$q->execute([trim((string)$input['name']),$email,password_hash($password,PASSWORD_DEFAULT),$role]);$id=(string)$db->lastInsertId();$db->prepare('INSERT INTO user_employee_links(user_id,employee_id,linked_by) VALUES(?,?,?)')->execute([$id,$employeeId,$admin['id']]);$db->commit();}catch(Throwable $e){if($db->inTransaction())$db->rollBack();App::json(['ok'=>false,'message'=>'تعذر إنشاء الحساب؛ تحقق من عدم تكرار الهوية أو البريد ومن عدم وجود حساب مرتبط مسبقًا'],422);}
    App::audit('created','users',$id,['role'=>$role,'emp_code'=>$input['emp_code']??null]);
    App::json(['ok'=>true,'id'=>(int)$id],201);
}
if ($_SERVER['REQUEST_METHOD'] === 'PATCH') {
    $id=(int)($_GET['id']??0); if($id<1) App::json(['ok'=>false,'message'=>'المعرّف مطلوب'],422);
    $updates=[];$values=[];
    if(isset($input['role'])&&in_array($input['role'],$roles,true)){ if($admin['role']!=='general-manager'&&!in_array($input['role'],['worker','supervisor','site-engineer','project-manager'],true))App::json(['ok'=>false,'message'=>'هذا الدور لا يعدله إلا المدير العام'],403);$updates[]='role=?';$values[]=$input['role']; }
    if(isset($input['is_active'])){ $updates[]='is_active=?';$values[]=(int)(bool)$input['is_active']; }
    if(!empty($input['password'])&&mb_strlen((string)$input['password'])>=10){ $updates[]='password_hash=?';$values[]=password_hash((string)$input['password'],PASSWORD_DEFAULT); }
    if(!$updates) App::json(['ok'=>false,'message'=>'لا توجد تغييرات صالحة'],422);
    $values[]=$id;$q=$db->prepare('UPDATE users SET '.implode(',',$updates).' WHERE id=?');$q->execute($values);
    if(isset($input['emp_code'])){$e=$db->prepare('SELECT id FROM employees WHERE emp_code=?');$e->execute([$input['emp_code']]);$employeeId=$e->fetchColumn();if(!$employeeId)App::json(['ok'=>false,'message'=>'الرقم الوظيفي غير موجود'],422);if(App::env('DB_CONNECTION','mysql')==='sqlite')$link=$db->prepare('INSERT INTO user_employee_links(user_id,employee_id,linked_by) VALUES(?,?,?) ON CONFLICT(user_id) DO UPDATE SET employee_id=excluded.employee_id,linked_by=excluded.linked_by,linked_at=CURRENT_TIMESTAMP');else$link=$db->prepare('INSERT INTO user_employee_links(user_id,employee_id,linked_by) VALUES(?,?,?) ON DUPLICATE KEY UPDATE employee_id=VALUES(employee_id),linked_by=VALUES(linked_by),linked_at=CURRENT_TIMESTAMP');$link->execute([$id,$employeeId,$admin['id']]);}
    App::audit('updated','users',(string)$id,['fields'=>$updates]); App::json(['ok'=>true]);
}
App::json(['ok'=>false,'message'=>'الطريقة غير مسموحة'],405);
