<?php
declare(strict_types=1);
require dirname(__DIR__) . '/app/Bootstrap.php';
$root = dirname(__DIR__);
if (App::installed() || is_file($root . '/storage/install.lock')) {
    http_response_code(403);
    exit('النظام مثبت بالفعل. احذف storage/install.lock يدويًا فقط إذا كنت تقصد إعادة التثبيت.');
}

$errors = [];
$success = false;
$requirements = [
    'PHP 8.1 أو أحدث' => version_compare(PHP_VERSION, '8.1.0', '>='),
    'امتداد PDO' => extension_loaded('pdo'),
    'مجلد storage قابل للكتابة' => is_writable($root . '/storage'),
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $driver = ($_POST['db_driver'] ?? 'mysql') === 'sqlite' ? 'sqlite' : 'mysql';
    if (!array_reduce($requirements, fn($ok, $v) => $ok && $v, true)) $errors[] = 'متطلبات الخادم غير مكتملة.';
    if (!filter_var($_POST['admin_email'] ?? '', FILTER_VALIDATE_EMAIL)) $errors[] = 'البريد الإلكتروني للمدير غير صالح.';
    if (mb_strlen((string)($_POST['admin_password'] ?? '')) < 10) $errors[] = 'كلمة المرور يجب ألا تقل عن 10 أحرف.';

    if (!$errors) {
        try {
            if ($driver === 'sqlite') {
                if (!extension_loaded('pdo_sqlite')) throw new RuntimeException('امتداد pdo_sqlite غير مثبت.');
                $database = $root . '/storage/database.sqlite';
                if (!is_file($database)) touch($database);
                $pdo = new PDO('sqlite:' . $database);
                $schema = file_get_contents($root . '/database/schema.sqlite.sql');
            } else {
                if (!extension_loaded('pdo_mysql')) throw new RuntimeException('امتداد pdo_mysql غير مثبت.');
                $host = trim((string)($_POST['db_host'] ?? '127.0.0.1'));
                $port = (int)($_POST['db_port'] ?? 3306);
                $database = trim((string)($_POST['db_name'] ?? ''));
                $username = trim((string)($_POST['db_user'] ?? ''));
                $password = (string)($_POST['db_password'] ?? '');
                if (!preg_match('/^[a-zA-Z0-9_\-]+$/', $database)) throw new RuntimeException('اسم قاعدة البيانات غير صالح. أنشئ القاعدة أولًا من لوحة الاستضافة.');
                $pdo = new PDO("mysql:host={$host};port={$port};dbname={$database};charset=utf8mb4", $username, $password, [PDO::ATTR_EMULATE_PREPARES => false]);
                $schema = file_get_contents($root . '/database/schema.mysql.sql');
            }
            $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $pdo->exec((string)$schema);
            $bundledVersions = ['2026_07_20_002_core_hr','2026_07_20_003_attendance_payroll','2026_07_20_004_supply_assets','2026_07_20_005_contracts_revenue','2026_07_20_006_project_controls','2026_07_20_007_procurement_match','2026_07_20_008_operations_compliance','2026_07_20_009_finance_tax','2026_07_20_010_employee_identity_access','2026_07_21_011_employee_edit_permissions'];
            foreach ($bundledVersions as $bundledVersion) {
                $bundledSchema = file_get_contents($root . "/database/migrations/{$bundledVersion}.{$driver}.sql");
                if ($bundledSchema === false) throw new RuntimeException('ملف بنية النظام غير موجود: '.$bundledVersion);
                $pdo->exec($bundledSchema);
                if ($driver === 'sqlite') $pdo->exec("INSERT OR IGNORE INTO schema_migrations(version) VALUES ('{$bundledVersion}')");
                else $pdo->exec("INSERT IGNORE INTO schema_migrations(version) VALUES ('{$bundledVersion}')");
            }
            $q = $pdo->prepare('INSERT INTO users (name,email,password_hash,role,is_active) VALUES (?,?,?,?,1)');
            $q->execute([trim((string)$_POST['admin_name']), mb_strtolower(trim((string)$_POST['admin_email'])), password_hash((string)$_POST['admin_password'], PASSWORD_DEFAULT), 'general-manager']);
            $pdo->exec("INSERT INTO companies (code,name,status) VALUES ('SAC','شركة سواعد عربية للمقاولات','active')");
            $companyId = (int)$pdo->lastInsertId();
            $b = $pdo->prepare('INSERT INTO branches (company_id,code,name,city) VALUES (?,?,?,?)');
            $b->execute([$companyId, 'MKK', 'المركز الرئيسي', 'مكة المكرمة']);

            $env = "APP_NAME=\"سواعد المتكامل\"\nAPP_INSTALLED=true\nAPP_ENV=production\nAPP_TIMEZONE=Asia/Riyadh\n";
            $env .= "DB_CONNECTION={$driver}\n";
            if ($driver === 'sqlite') {
                $env .= "DB_DATABASE={$database}\n";
            } else {
                $env .= "DB_HOST={$host}\nDB_PORT={$port}\nDB_DATABASE={$database}\nDB_USERNAME={$username}\nDB_PASSWORD=\"" . addcslashes($password, "\\\"") . "\"\n";
            }
            if (file_put_contents($root . '/.env', $env, LOCK_EX) === false) throw new RuntimeException('تعذر كتابة ملف .env.');
            @chmod($root . '/.env', 0600);
            file_put_contents($root . '/storage/install.lock', date(DATE_ATOM));
            $success = true;
        } catch (Throwable $e) {
            $errors[] = 'فشل التثبيت: ' . $e->getMessage();
        }
    }
}
?>
<!doctype html><html lang="ar" dir="rtl"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>تثبيت سواعد المتكامل</title>
<style>*{box-sizing:border-box}body{margin:0;background:#eef1f4;color:#1b2733;font-family:Tahoma,Arial}.wrap{max-width:820px;margin:35px auto;padding:0 18px}.card{background:white;border:1px solid #d8dee5;border-radius:12px;padding:24px;margin-bottom:16px}h1{margin-top:0}.grid{display:grid;grid-template-columns:1fr 1fr;gap:14px}.full{grid-column:1/-1}label{display:block;font-size:13px;margin-bottom:6px}input,select{width:100%;padding:11px;border:1px solid #cbd3dc;border-radius:7px;font:inherit}button{padding:12px 22px;background:#1b2733;color:#fff;border:0;border-radius:7px;font:inherit;font-weight:bold;cursor:pointer}.ok{color:#16805b}.bad,.error{color:#ad3030}.error{background:#fff0f0;padding:10px;border-radius:7px}.success{background:#edf9f2;padding:20px;border-radius:8px}.hint{font-size:12px;color:#6d7780}@media(max-width:650px){.grid{grid-template-columns:1fr}.full{grid-column:auto}}</style>
<script>function toggleDb(v){document.getElementById('mysqlFields').style.display=v==='mysql'?'contents':'none'}</script></head><body><main class="wrap">
<div class="card"><h1>تثبيت نظام سواعد المتكامل</h1><p>معالج إعداد قاعدة البيانات وحساب المدير العام.</p><?php foreach($requirements as $label=>$ok): ?><div class="<?= $ok?'ok':'bad' ?>"><?= $ok?'✓':'✕' ?> <?= htmlspecialchars($label) ?></div><?php endforeach; ?></div>
<?php if ($success): ?><div class="card success"><h2>تم التثبيت بنجاح</h2><p>احذف ملف <code>public/install.php</code> بعد التأكد من الدخول، ثم افتح النظام.</p><a href="index.php">الانتقال إلى تسجيل الدخول ←</a></div>
<?php else: ?><div class="card"><?php foreach($errors as $e): ?><p class="error"><?= htmlspecialchars($e) ?></p><?php endforeach; ?>
<form method="post"><div class="grid"><div><label>نوع قاعدة البيانات</label><select name="db_driver" onchange="toggleDb(this.value)"><option value="mysql">MySQL (الإنتاج)</option><option value="sqlite">SQLite (تجربة فقط)</option></select></div><div></div>
<div id="mysqlFields" style="display:contents"><div><label>خادم قاعدة البيانات</label><input name="db_host" value="127.0.0.1"></div><div><label>المنفذ</label><input name="db_port" value="3306"></div><div><label>اسم قاعدة البيانات</label><input name="db_name" value="sawaed_erp"></div><div><label>مستخدم قاعدة البيانات</label><input name="db_user"></div><div class="full"><label>كلمة مرور قاعدة البيانات</label><input name="db_password" type="password"></div></div>
<div><label>اسم المدير العام</label><input name="admin_name" value="المدير العام" required></div><div><label>البريد الإلكتروني</label><input name="admin_email" type="email" required></div><div class="full"><label>كلمة المرور (10 أحرف على الأقل)</label><input name="admin_password" type="password" minlength="10" required><p class="hint">استخدم كلمة مرور قوية وفريدة، ثم فعّل HTTPS على النطاق.</p></div><div class="full"><button>بدء التثبيت</button></div></div></form></div><?php endif; ?></main></body></html>
