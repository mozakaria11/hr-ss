<?php
declare(strict_types=1);
require dirname(__DIR__) . '/app/Bootstrap.php';

if (!App::installed()) {
    header('Location: install.php');
    exit;
}
$mobileMode = (($_GET['mobile'] ?? $_POST['mobile'] ?? '') === 'employee');
if (App::user()) {
    header('Location: app.php' . ($mobileMode ? '?mobile=employee' : ''));
    exit;
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!hash_equals(App::csrf(), (string)($_POST['_csrf'] ?? ''))) {
        $error = 'تعذر التحقق من الطلب. أعد المحاولة.';
    } else {
        $login = mb_strtolower(trim((string)($_POST['login'] ?? $_POST['email'] ?? '')));
        $login = strtr($login, ['٠'=>'0','١'=>'1','٢'=>'2','٣'=>'3','٤'=>'4','٥'=>'5','٦'=>'6','٧'=>'7','٨'=>'8','٩'=>'9']);
        $lockedUntil = (int)($_SESSION['login_locked_until'] ?? 0);
        if ($lockedUntil > time()) {
            $error = 'محاولات كثيرة. انتظر بضع دقائق ثم أعد المحاولة.';
            goto render_login;
        }
        if (App::columnExists('employees','national_id') && App::tableExists('user_employee_links')) {
            $q = App::db()->prepare('SELECT u.* FROM users u LEFT JOIN user_employee_links l ON l.user_id=u.id LEFT JOIN employees e ON e.id=l.employee_id WHERE u.is_active=1 AND (LOWER(u.email)=? OR e.national_id=?) LIMIT 1');
            $q->execute([$login,$login]);
        } else {
            $q = App::db()->prepare('SELECT * FROM users WHERE LOWER(email)=? AND is_active=1 LIMIT 1');
            $q->execute([$login]);
        }
        $user = $q->fetch();
        if ($user && password_verify((string)($_POST['password'] ?? ''), $user['password_hash'])) {
            session_regenerate_id(true);
            $_SESSION['user_id'] = (int)$user['id'];
            unset($_SESSION['login_failures'],$_SESSION['login_locked_until']);
            if (App::columnExists('users','last_login_at')) App::db()->prepare('UPDATE users SET last_login_at=CURRENT_TIMESTAMP WHERE id=?')->execute([$user['id']]);
            App::audit('login', 'users', (string)$user['id']);
            header('Location: app.php' . ($mobileMode ? '?mobile=employee' : ''));
            exit;
        }
        $_SESSION['login_failures'] = (int)($_SESSION['login_failures'] ?? 0) + 1;
        if ($_SESSION['login_failures'] >= 5) { $_SESSION['login_locked_until'] = time()+300; $_SESSION['login_failures']=0; }
        $error = 'رقم الهوية/البريد الإلكتروني أو كلمة المرور غير صحيحة.';
        usleep(350000);
    }
}
render_login:
?>
<!doctype html><html lang="ar" dir="rtl"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>تسجيل الدخول — سواعد المتكامل</title>
<style>*{box-sizing:border-box}body{margin:0;min-height:100vh;display:grid;place-items:center;background:#eef1f4;font-family:Tahoma,Arial;color:#1b2733}.box{width:min(440px,92vw);background:#fff;border:1px solid #d8dee5;border-radius:14px;padding:32px;box-shadow:0 18px 55px #1b27331a}.brand{display:flex;align-items:center;gap:12px;margin-bottom:28px}.mark{width:48px;height:48px;border-radius:10px;background:#bc923d;color:white;display:grid;place-items:center;font-weight:800}.sub{color:#6c7884;font-size:12px;margin-top:4px}label{display:block;font-size:13px;margin:14px 0 6px}input{width:100%;padding:12px;border:1px solid #cbd3dc;border-radius:8px;font:inherit}button{width:100%;margin-top:20px;padding:12px;border:0;border-radius:8px;background:#1b2733;color:white;font:inherit;font-weight:700;cursor:pointer}.error{background:#fff1f1;color:#a52b2b;padding:10px;border-radius:7px;font-size:13px;margin-bottom:12px}.help{display:block;text-align:center;margin-top:16px;color:#88691f;font-size:13px;text-decoration:none}</style></head><body>
<main class="box"><div class="brand"><div class="mark">سو</div><div><strong>سواعد المتكامل</strong><div class="sub">نظام إدارة المقاولات والموارد المؤسسية</div></div></div>
<?php if ($error): ?><div class="error"><?= htmlspecialchars($error) ?></div><?php endif; ?>
<form method="post" autocomplete="on"><input type="hidden" name="_csrf" value="<?= htmlspecialchars(App::csrf()) ?>"><?php if($mobileMode):?><input type="hidden" name="mobile" value="employee"><?php endif;?><label>رقم الهوية أو البريد الإلكتروني</label><input name="login" inputmode="text" autocomplete="username" required autofocus><label>كلمة المرور</label><input name="password" type="password" autocomplete="current-password" required><button>تسجيل الدخول</button></form><a class="help" href="forgot-password.php">نسيت كلمة المرور؟ استعادة الحساب بالبريد</a></main></body></html>
