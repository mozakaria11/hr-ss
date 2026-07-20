<?php
declare(strict_types=1);
require dirname(__DIR__).'/app/Bootstrap.php';
$user=App::requireAuth();
if($user['role']!=='general-manager'){http_response_code(403);exit('الترقية متاحة للمدير العام فقط.');}
$db=App::db();
$versions=['2026_07_20_002_core_hr','2026_07_20_003_attendance_payroll','2026_07_20_004_supply_assets','2026_07_20_005_contracts_revenue','2026_07_20_006_project_controls','2026_07_20_007_procurement_match','2026_07_20_008_operations_compliance','2026_07_20_009_finance_tax','2026_07_20_010_employee_identity_access','2026_07_21_011_employee_edit_permissions'];
$pending=[];foreach($versions as $version){$q=$db->prepare('SELECT COUNT(*) FROM schema_migrations WHERE version=?');$q->execute([$version]);if(!(bool)$q->fetchColumn())$pending[]=$version;}
$already=count($pending)===0;$message='';$error='';
if($_SERVER['REQUEST_METHOD']==='POST'&&!$already){
    if(!hash_equals(App::csrf(),(string)($_POST['_csrf']??'')))$error='رمز الحماية غير صالح.';
    else try{$driver=App::env('DB_CONNECTION','mysql')==='sqlite'?'sqlite':'mysql';foreach($pending as $version){$path=dirname(__DIR__)."/database/migrations/{$version}.{$driver}.sql";$sql=file_get_contents($path);if($sql===false)throw new RuntimeException('ملف الترقية غير موجود: '.$version);$db->exec($sql);$save=$db->prepare('INSERT INTO schema_migrations(version) VALUES(?)');$save->execute([$version]);App::audit('system.upgraded','schema_migrations',$version);}$message='تمت ترقية قاعدة البيانات إلى Version 1.10 بنجاح.';$already=true;}catch(Throwable $e){$error='فشلت الترقية: '.$e->getMessage();}
}
?>
<!doctype html><html lang="ar" dir="rtl"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>ترقية سواعد 1.10</title><style>body{font-family:Tahoma,Arial;background:#eef1f4;color:#1b2733;margin:0}.card{max-width:680px;margin:60px auto;background:#fff;border:1px solid #d7dde4;border-radius:12px;padding:28px}.ok{background:#eaf8f0;color:#176b45;padding:12px;border-radius:7px}.err{background:#fff0f0;color:#a62e2e;padding:12px;border-radius:7px}button{background:#1b2733;color:#fff;border:0;border-radius:7px;padding:12px 20px;font:inherit;cursor:pointer}a{color:#88691f}</style></head><body><main class="card"><h1>ترقية نظام سواعد — Version 1.10</h1><p>تضيف صلاحيات إنشاء وتعديل بيانات الموظفين وإدارة حساباتهم، مع واجهة موظف مهيأة لتطبيق Android WebView.</p><?php if($message):?><p class="ok"><?=htmlspecialchars($message)?></p><?php endif;?><?php if($error):?><p class="err"><?=htmlspecialchars($error)?></p><?php endif;?><?php if($already):?><p class="ok">قاعدة البيانات محدثة بالفعل.</p><a href="app.php">فتح النظام ←</a><?php else:?><form method="post"><input type="hidden" name="_csrf" value="<?=htmlspecialchars(App::csrf())?>"><button>تنفيذ الترقية الآن</button></form><?php endif;?></main></body></html>
