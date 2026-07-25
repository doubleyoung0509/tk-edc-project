<?php
declare(strict_types=1);

const CLOUD_META_PREFIX = '__TKEDC_META__:';

function respond(array $payload, int $status = 200): void
{
    http_response_code($status);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

function fail_request(string $message, int $status = 400): void
{
    respond(['error' => ['message' => $message]], $status);
}

function request_body(): array
{
    $raw = file_get_contents('php://input');
    if ($raw === false || $raw === '') {
        return [];
    }
    $data = json_decode($raw, true);
    if (!is_array($data)) {
        fail_request('请求数据不是有效的 JSON');
    }
    return $data;
}

function current_user_id(): int
{
    $id = isset($_SESSION['user_id']) ? (int) $_SESSION['user_id'] : 0;
    if ($id < 1) {
        fail_request('请先登录', 401);
    }
    return $id;
}

function clean_string($value, int $maxLength = 0): string
{
    $text = is_scalar($value) ? trim((string) $value) : '';
    if ($maxLength > 0 && function_exists('mb_substr')) {
        return mb_substr($text, 0, $maxLength, 'UTF-8');
    }
    return $maxLength > 0 ? substr($text, 0, $maxLength) : $text;
}

function valid_date($value): ?string
{
    $date = clean_string($value, 10);
    if ($date === '') {
        return null;
    }
    $parsed = DateTime::createFromFormat('Y-m-d', $date);
    return $parsed && $parsed->format('Y-m-d') === $date ? $date : null;
}

function decode_cloud_files($value): array
{
    $files = is_string($value) ? $value : '';
    if (strpos($files, CLOUD_META_PREFIX) !== 0) {
        return ['paymentStatus' => '未收款', 'files' => $files];
    }
    $meta = json_decode(substr($files, strlen(CLOUD_META_PREFIX)), true);
    if (!is_array($meta)) {
        return ['paymentStatus' => '未收款', 'files' => ''];
    }
    return [
        'paymentStatus' => clean_string($meta['paymentStatus'] ?? '未收款', 50),
        'files' => clean_string($meta['files'] ?? ''),
    ];
}

function encode_cloud_files(string $paymentStatus, string $files): string
{
    return CLOUD_META_PREFIX . json_encode(
        ['paymentStatus' => $paymentStatus, 'files' => $files],
        JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
    );
}

$configFile = __DIR__ . '/config.php';
if (!is_file($configFile)) {
    fail_request('服务器尚未配置 api/config.php', 503);
}

$config = require $configFile;
if (!is_array($config) || empty($config['db'])) {
    fail_request('服务器数据库配置无效', 503);
}

$appConfig = isset($config['app']) && is_array($config['app']) ? $config['app'] : [];
$allowedOrigin = clean_string($appConfig['allowed_origin'] ?? '');
$requestOrigin = isset($_SERVER['HTTP_ORIGIN']) ? clean_string($_SERVER['HTTP_ORIGIN']) : '';
if ($requestOrigin !== '') {
    if ($allowedOrigin !== '' && $requestOrigin !== $allowedOrigin) {
        fail_request('不允许的请求来源', 403);
    }
    if ($allowedOrigin === '') {
        $originHost = strtolower((string) parse_url($requestOrigin, PHP_URL_HOST));
        $serverHost = strtolower(explode(':', (string) ($_SERVER['HTTP_HOST'] ?? ''))[0]);
        if ($originHost === '' || $originHost !== $serverHost) {
            fail_request('不允许的请求来源', 403);
        }
    } else {
        header('Access-Control-Allow-Origin: ' . $allowedOrigin);
        header('Access-Control-Allow-Credentials: true');
        header('Vary: Origin');
    }
}
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    header('Access-Control-Allow-Methods: GET, POST');
    header('Access-Control-Allow-Headers: Content-Type');
    http_response_code(204);
    exit;
}

$secureCookie = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off');
session_name(clean_string($appConfig['session_name'] ?? 'tk_edc_session', 64));
session_set_cookie_params([
    'lifetime' => 0,
    'path' => '/',
    'domain' => '',
    'secure' => $secureCookie,
    'httponly' => true,
    'samesite' => 'Lax',
]);
session_start();

$db = $config['db'];
$host = clean_string($db['host'] ?? '127.0.0.1');
$port = (int) ($db['port'] ?? 3306);
$name = clean_string($db['name'] ?? '');
$charset = clean_string($db['charset'] ?? 'utf8mb4');
if ($name === '') {
    fail_request('未填写数据库名称', 503);
}

try {
    $pdo = new PDO(
        "mysql:host={$host};port={$port};dbname={$name};charset={$charset}",
        clean_string($db['user'] ?? ''),
        (string) ($db['password'] ?? ''),
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]
    );
} catch (Throwable $error) {
    fail_request('数据库连接失败，请检查 MySQL 配置', 503);
}

$action = clean_string($_GET['action'] ?? 'health', 32);
$method = strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET');

try {
    if ($action === 'health' && $method === 'GET') {
        $version = (string) $pdo->query('SELECT VERSION()')->fetchColumn();
        respond(['data' => ['ok' => true, 'database' => 'MySQL', 'version' => $version]]);
    }

    if ($action === 'session' && $method === 'GET') {
        if (empty($_SESSION['user_id'])) {
            respond(['data' => ['user' => null]]);
        }
        $stmt = $pdo->prepare('SELECT id, email FROM users WHERE id = ? LIMIT 1');
        $stmt->execute([(int) $_SESSION['user_id']]);
        $user = $stmt->fetch() ?: null;
        respond(['data' => ['user' => $user]]);
    }

    if ($action === 'login' && $method === 'POST') {
        $body = request_body();
        $email = strtolower(clean_string($body['email'] ?? '', 191));
        $password = (string) ($body['password'] ?? '');
        $stmt = $pdo->prepare('SELECT id, email, password_hash FROM users WHERE email = ? LIMIT 1');
        $stmt->execute([$email]);
        $user = $stmt->fetch();
        if (!$user || !password_verify($password, $user['password_hash'])) {
            fail_request('邮箱或密码错误', 401);
        }
        session_regenerate_id(true);
        $_SESSION['user_id'] = (int) $user['id'];
        respond(['data' => ['user' => ['id' => (int) $user['id'], 'email' => $user['email']]]);
    }

    if ($action === 'register' && $method === 'POST') {
        $body = request_body();
        $email = strtolower(clean_string($body['email'] ?? '', 191));
        $password = (string) ($body['password'] ?? '');
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            fail_request('邮箱格式不正确');
        }
        if (strlen($password) < 8) {
            fail_request('密码至少需要 8 位');
        }
        $firstOnly = !array_key_exists('first_user_registration_only', $appConfig)
            || (bool) $appConfig['first_user_registration_only'];
        if ($firstOnly && (int) $pdo->query('SELECT COUNT(*) FROM users')->fetchColumn() > 0) {
            fail_request('系统已有账号，已关闭新账号注册', 403);
        }
        $stmt = $pdo->prepare('INSERT INTO users (email, password_hash) VALUES (?, ?)');
        $stmt->execute([$email, password_hash($password, PASSWORD_DEFAULT)]);
        $userId = (int) $pdo->lastInsertId();
        session_regenerate_id(true);
        $_SESSION['user_id'] = $userId;
        respond(['data' => ['user' => ['id' => $userId, 'email' => $email]]], 201);
    }

    if ($action === 'logout' && $method === 'POST') {
        $_SESSION = [];
        if (ini_get('session.use_cookies')) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000, $params['path'], $params['domain'], $params['secure'], $params['httponly']);
        }
        session_destroy();
        respond(['data' => ['ok' => true]]);
    }

    if ($action === 'list' && $method === 'GET') {
        $userId = current_user_id();
        $table = clean_string($_GET['table'] ?? '', 32);
        if ($table === 'projects') {
            $stmt = $pdo->prepare(
                'SELECT id, category, project_name, amount, cost, start_date, owner, client,
                        payment_status, statuses, files, note, created_at, updated_at
                 FROM projects WHERE user_id = ? ORDER BY start_date DESC, id DESC'
            );
            $stmt->execute([$userId]);
            $rows = [];
            foreach ($stmt->fetchAll() as $row) {
                $statuses = json_decode((string) $row['statuses'], true);
                $row['statuses'] = is_array($statuses) ? $statuses : [];
                $row['files'] = encode_cloud_files((string) $row['payment_status'], (string) $row['files']);
                unset($row['payment_status']);
                $rows[] = $row;
            }
            respond(['data' => $rows]);
        }
        if ($table === 'presets') {
            $stmt = $pdo->prepare(
                'SELECT preset_type, preset_value, created_at, updated_at
                 FROM presets WHERE user_id = ? ORDER BY id ASC'
            );
            $stmt->execute([$userId]);
            respond(['data' => $stmt->fetchAll()]);
        }
        fail_request('不支持的数据表');
    }

    if ($action === 'upsert' && $method === 'POST') {
        $userId = current_user_id();
        $table = clean_string($_GET['table'] ?? '', 32);
        $body = request_body();
        $rows = isset($body['rows']) && is_array($body['rows']) ? $body['rows'] : [];
        $pdo->beginTransaction();
        if ($table === 'projects') {
            $sql = 'INSERT INTO projects
                    (user_id, id, category, project_name, amount, cost, start_date, owner, client,
                     payment_status, statuses, files, note, updated_at)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())
                    ON DUPLICATE KEY UPDATE
                    category = VALUES(category), project_name = VALUES(project_name),
                    amount = VALUES(amount), cost = VALUES(cost), start_date = VALUES(start_date),
                    owner = VALUES(owner), client = VALUES(client),
                    payment_status = VALUES(payment_status), statuses = VALUES(statuses),
                    files = VALUES(files), note = VALUES(note), updated_at = NOW()';
            $stmt = $pdo->prepare($sql);
            foreach ($rows as $row) {
                if (!is_array($row)) {
                    continue;
                }
                $id = clean_string($row['id'] ?? '', 100);
                if ($id === '') {
                    continue;
                }
                $meta = decode_cloud_files($row['files'] ?? '');
                $statuses = isset($row['statuses']) && is_array($row['statuses']) ? $row['statuses'] : [];
                $stmt->execute([
                    $userId,
                    $id,
                    clean_string($row['category'] ?? '', 191),
                    clean_string($row['project_name'] ?? '', 191),
                    max(0, (float) ($row['amount'] ?? 0)),
                    max(0, (float) ($row['cost'] ?? 0)),
                    valid_date($row['start_date'] ?? null),
                    clean_string($row['owner'] ?? '', 191),
                    clean_string($row['client'] ?? '', 255),
                    clean_string($meta['paymentStatus'], 50),
                    json_encode(array_values($statuses), JSON_UNESCAPED_UNICODE),
                    clean_string($meta['files']),
                    clean_string($row['note'] ?? ''),
                ]);
            }
            $pdo->commit();
            respond(['data' => ['count' => count($rows)]]);
        }
        if ($table === 'presets') {
            $stmt = $pdo->prepare(
                'INSERT INTO presets (user_id, preset_type, preset_hash, preset_value)
                 VALUES (?, ?, ?, ?)
                 ON DUPLICATE KEY UPDATE preset_value = VALUES(preset_value), updated_at = NOW()'
            );
            foreach ($rows as $row) {
                if (!is_array($row)) {
                    continue;
                }
                $type = clean_string($row['preset_type'] ?? '', 100);
                $value = clean_string($row['preset_value'] ?? '');
                if ($type === '' || $value === '') {
                    continue;
                }
                $stmt->execute([$userId, $type, hash('sha256', $value), $value]);
            }
            $pdo->commit();
            respond(['data' => ['count' => count($rows)]]);
        }
        $pdo->rollBack();
        fail_request('不支持的数据表');
    }

    if ($action === 'delete' && $method === 'POST') {
        $userId = current_user_id();
        $table = clean_string($_GET['table'] ?? '', 32);
        $body = request_body();
        $filters = isset($body['filters']) && is_array($body['filters']) ? $body['filters'] : [];
        if ($table === 'projects') {
            $id = clean_string($filters['id'] ?? '', 100);
            $stmt = $pdo->prepare('DELETE FROM projects WHERE user_id = ? AND id = ?');
            $stmt->execute([$userId, $id]);
            respond(['data' => ['count' => $stmt->rowCount()]]);
        }
        if ($table === 'presets') {
            $type = clean_string($filters['preset_type'] ?? '', 100);
            $value = clean_string($filters['preset_value'] ?? '');
            $stmt = $pdo->prepare(
                'DELETE FROM presets
                 WHERE user_id = ? AND preset_type = ? AND preset_hash = ?'
            );
            $stmt->execute([$userId, $type, hash('sha256', $value)]);
            respond(['data' => ['count' => $stmt->rowCount()]]);
        }
        fail_request('不支持的数据表');
    }

    fail_request('接口不存在', 404);
} catch (PDOException $error) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    error_log($error->getMessage());
    fail_request('数据库操作失败', 500);
} catch (Throwable $error) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    error_log($error->getMessage());
    fail_request('服务器处理请求失败', 500);
}
