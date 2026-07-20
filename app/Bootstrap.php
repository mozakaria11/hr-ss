<?php
declare(strict_types=1);

final class App
{
    private static ?PDO $pdo = null;
    private static array $config = [];

    public static function boot(): void
    {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            session_name('SAWAED_ERP_SESSION');
            session_set_cookie_params([
                'httponly' => true,
                'secure' => (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off'),
                'samesite' => 'Lax',
                'path' => '/',
            ]);
            session_start();
        }

        $root = dirname(__DIR__);
        self::$config = self::readEnv($root . '/.env');
        date_default_timezone_set(self::env('APP_TIMEZONE', 'Asia/Riyadh'));

        if (!headers_sent()) {
            header('X-Content-Type-Options: nosniff');
            header('X-Frame-Options: SAMEORIGIN');
            header('Referrer-Policy: strict-origin-when-cross-origin');
            header("Permissions-Policy: camera=(), microphone=(), geolocation=(self)");
        }
    }

    private static function readEnv(string $path): array
    {
        if (!is_file($path)) return [];
        $out = [];
        foreach (file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) ?: [] as $line) {
            $line = trim($line);
            if ($line === '' || str_starts_with($line, '#') || !str_contains($line, '=')) continue;
            [$key, $value] = explode('=', $line, 2);
            $value = trim($value);
            if (strlen($value) >= 2 && $value[0] === '"' && $value[strlen($value)-1] === '"') {
                $value = stripcslashes(substr($value, 1, -1));
            } elseif (strlen($value) >= 2 && $value[0] === "'" && $value[strlen($value)-1] === "'") {
                $value = substr($value, 1, -1);
            }
            $out[trim($key)] = $value;
        }
        return $out;
    }

    public static function env(string $key, ?string $default = null): ?string
    {
        if (array_key_exists($key, self::$config)) return self::$config[$key];
        $value = getenv($key);
        return $value !== false ? (string)$value : $default;
    }

    public static function installed(): bool
    {
        return is_file(dirname(__DIR__) . '/.env') && self::env('APP_INSTALLED', 'false') === 'true';
    }

    public static function db(): PDO
    {
        if (self::$pdo) return self::$pdo;
        $driver = self::env('DB_CONNECTION', 'mysql');
        if ($driver === 'sqlite') {
            $file = self::env('DB_DATABASE', dirname(__DIR__) . '/storage/database.sqlite');
            self::$pdo = new PDO('sqlite:' . $file);
            self::$pdo->exec('PRAGMA foreign_keys = ON');
        } else {
            $host = self::env('DB_HOST', '127.0.0.1');
            $port = self::env('DB_PORT', '3306');
            $name = self::env('DB_DATABASE', 'sawaed_erp');
            $charset = 'utf8mb4';
            self::$pdo = new PDO("mysql:host={$host};port={$port};dbname={$name};charset={$charset}", self::env('DB_USERNAME', 'root'), self::env('DB_PASSWORD', ''));
        }
        self::$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        self::$pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
        self::$pdo->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);
        return self::$pdo;
    }

    public static function user(): ?array
    {
        if (empty($_SESSION['user_id']) || !self::installed()) return null;
        $q = self::db()->prepare('SELECT id, name, email, role, is_active FROM users WHERE id = ? LIMIT 1');
        $q->execute([(int) $_SESSION['user_id']]);
        $user = $q->fetch();
        return $user && (int)$user['is_active'] === 1 ? $user : null;
    }

    public static function permissions(?int $userId = null): array
    {
        $user = $userId === null ? self::user() : null;
        if ($userId === null && !$user) return [];
        try {
            if ($userId !== null) { $q=self::db()->prepare('SELECT role FROM users WHERE id=? AND is_active=1');$q->execute([$userId]);$role=$q->fetchColumn(); }
            else $role=$user['role'];
            $q=self::db()->prepare('SELECT p.permission_key FROM role_permissions rp JOIN roles r ON r.id=rp.role_id JOIN permissions p ON p.id=rp.permission_id WHERE r.role_key=? ORDER BY p.permission_key');
            $q->execute([$role]);
            return $q->fetchAll(PDO::FETCH_COLUMN);
        } catch (Throwable) { return []; }
    }

    public static function can(string $permission, ?array $user = null): bool
    {
        $user ??= self::user();
        if (!$user) return false;
        if (($user['role'] ?? '') === 'general-manager') return true;
        return in_array($permission, self::permissions((int)$user['id']), true);
    }

    public static function requireAuth(bool $json = false): array
    {
        $user = self::user();
        if ($user) return $user;
        if ($json) self::json(['ok' => false, 'message' => 'انتهت الجلسة، سجل الدخول مجددًا'], 401);
        header('Location: index.php');
        exit;
    }

    public static function csrf(): string
    {
        if (empty($_SESSION['csrf'])) $_SESSION['csrf'] = bin2hex(random_bytes(32));
        return $_SESSION['csrf'];
    }

    public static function verifyCsrf(): void
    {
        $token = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? $_POST['_csrf'] ?? '';
        if (!hash_equals(self::csrf(), (string)$token)) self::json(['ok' => false, 'message' => 'رمز الحماية غير صالح'], 419);
    }

    public static function json(array $payload, int $status = 200): never
    {
        http_response_code($status);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        exit;
    }

    public static function input(): array
    {
        $raw = file_get_contents('php://input') ?: '';
        $data = json_decode($raw, true);
        return is_array($data) ? $data : $_POST;
    }

    public static function tableExists(string $table): bool
    {
        if (!preg_match('/^[a-zA-Z0-9_]+$/', $table)) return false;
        try {
            if (self::env('DB_CONNECTION', 'mysql') === 'sqlite') {
                $q = self::db()->prepare("SELECT COUNT(*) FROM sqlite_master WHERE type='table' AND name=?");
                $q->execute([$table]);
            } else {
                $q = self::db()->prepare('SELECT COUNT(*) FROM information_schema.tables WHERE table_schema=DATABASE() AND table_name=?');
                $q->execute([$table]);
            }
            return (bool)$q->fetchColumn();
        } catch (Throwable) { return false; }
    }

    public static function columnExists(string $table, string $column): bool
    {
        if (!preg_match('/^[a-zA-Z0-9_]+$/', $table.$column)) return false;
        try {
            if (self::env('DB_CONNECTION', 'mysql') === 'sqlite') {
                foreach (self::db()->query("PRAGMA table_info({$table})")->fetchAll() as $row) if ($row['name'] === $column) return true;
                return false;
            }
            $q = self::db()->prepare('SELECT COUNT(*) FROM information_schema.columns WHERE table_schema=DATABASE() AND table_name=? AND column_name=?');
            $q->execute([$table,$column]);
            return (bool)$q->fetchColumn();
        } catch (Throwable) { return false; }
    }

    public static function baseUrl(): string
    {
        $configured = rtrim((string)self::env('APP_URL', ''), '/');
        if ($configured !== '') return $configured;
        $https = !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off';
        $host = preg_replace('/[^a-zA-Z0-9.:-]/', '', (string)($_SERVER['HTTP_HOST'] ?? 'localhost'));
        $path = rtrim(str_replace('\\', '/', dirname((string)($_SERVER['SCRIPT_NAME'] ?? '/'))), '/');
        return ($https ? 'https' : 'http').'://'.$host.($path === '' ? '' : $path);
    }

    public static function sendPasswordResetMail(string $email, string $name, string $url): bool
    {
        $subject = 'استعادة كلمة المرور — سواعد المتكامل';
        $body = "مرحبًا {$name}،\n\nوصلنا طلب لاستعادة حسابك في نظام سواعد المتكامل.\nاستخدم الرابط التالي خلال 30 دقيقة:\n{$url}\n\nإذا لم تطلب ذلك فتجاهل الرسالة.";
        $from = (string)self::env('MAIL_FROM_ADDRESS', 'noreply@'.preg_replace('/:\\d+$/', '', (string)($_SERVER['HTTP_HOST'] ?? 'localhost')));
        $headers = "From: Sawaed ERP <{$from}>\r\nContent-Type: text/plain; charset=UTF-8\r\nContent-Transfer-Encoding: 8bit";
        return @mail($email, '=?UTF-8?B?'.base64_encode($subject).'?=', $body, $headers);
    }

    public static function audit(string $action, string $entity, ?string $entityId = null, array $meta = []): void
    {
        $q = self::db()->prepare('INSERT INTO audit_logs (user_id, action, entity, entity_id, metadata, ip_address, created_at) VALUES (?, ?, ?, ?, ?, ?, CURRENT_TIMESTAMP)');
        $q->execute([$_SESSION['user_id'] ?? null, $action, $entity, $entityId, json_encode($meta, JSON_UNESCAPED_UNICODE), $_SERVER['REMOTE_ADDR'] ?? null]);
    }
}

App::boot();
