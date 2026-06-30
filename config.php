<?php

// Читаем .env
$env = parse_ini_file(__DIR__ . '/.env');

// База данных
define('DB_HOST', $env['DB_HOST']);
define('DB_NAME', $env['DB_NAME']);
define('DB_USER', $env['DB_USER']);
define('DB_PASS', $env['DB_PASS']);

// Приложение
define('APP_NAME', $env['APP_NAME']);
define('APP_URL',  $env['APP_URL'] ?? '');

// API
define('ANTHROPIC_API_KEY', $env['ANTHROPIC_API_KEY']);

// Сессия
define('SESSION_NAME', $env['SESSION_NAME']);

// Подключение к БД
function db(): PDO {
    static $pdo = null;
    if ($pdo === null) {
        try {
            $pdo = new PDO(
                'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4',
                DB_USER,
                DB_PASS,
                [
                    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES   => false,
                ]
            );
        } catch (PDOException $e) {
            // Детали ошибки — только в лог, пользователю — общая фраза
            error_log('[DB ERROR] ' . $e->getMessage());
            http_response_code(500);
            require __DIR__ . '/src/views/500.php';
            exit;
        }
    }
    return $pdo;
}

// ─── CSRF ────────────────────────────────────────────────────────────────────

/**
 * Возвращает CSRF-токен текущей сессии (генерирует при необходимости)
 */
function csrfToken(): string {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * Проверяет CSRF-токен из POST-данных или заголовка X-CSRF-Token.
 * При несовпадении — завершает запрос с 403.
 */
function csrfVerify(): void {
    $token = $_POST['csrf_token']
          ?? $_SERVER['HTTP_X_CSRF_TOKEN']
          ?? '';
    $expected = $_SESSION['csrf_token'] ?? '';

    if (!$expected || !hash_equals($expected, $token)) {
        http_response_code(403);
        error_log('[CSRF] Invalid token from IP: ' . ($_SERVER['REMOTE_ADDR'] ?? 'unknown'));
        die('Запрос отклонён: неверный токен безопасности.');
    }
}

// ─── HTML-очистка (защита от XSS в description) ──────────────────────────────

/**
 * Очищает HTML-строку, оставляя только безопасные теги.
 * Используется перед сохранением description и перед выводом.
 */
function sanitizeHtml(string $html): string {
    $allowed_tags = [
        'h2', 'h3', 'p', 'br',
        'ul', 'ol', 'li',
        'strong', 'em', 'code', 'pre',
        'blockquote',
        'table', 'thead', 'tbody', 'tr', 'th', 'td',
        'img',
    ];
    if (!$html || trim($html) === '') return '';

    $html = strip_tags($html, '<' . implode('><', $allowed_tags) . '>');

    $dom = new DOMDocument('1.0', 'UTF-8');
    libxml_use_internal_errors(true);
    $dom->loadHTML(
        '<?xml encoding="UTF-8">' . $html,
        LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD
    );
    libxml_clear_errors();
    $allowedAttrsByTag = [
        'img' => ['src', 'alt', 'class'],
    ];
    $xpath = new DOMXPath($dom);
    foreach ($xpath->query('//@*') as $attr) {
        $tagName = $attr->ownerElement->nodeName;
        $allowedAttrs = $allowedAttrsByTag[$tagName] ?? [];
        if (!in_array($attr->nodeName, $allowedAttrs)) {
            $attr->ownerElement->removeAttribute($attr->nodeName);
        }
    }

    $result = '';
    foreach ($dom->childNodes as $node) {
        if ($node->nodeType === XML_PI_NODE) continue; // пропускаем <?xml ...
        $result .= $dom->saveHTML($node);
    }

    return $result;
}
// ─── Тема пользователя ───────────────────────────────────────────────────────

function getUserTheme(): string {
    if (!isset($_SESSION['user_id'])) return 'dark-default';
    $stmt = db()->prepare('SELECT theme FROM users WHERE id = ?');
    $stmt->execute([$_SESSION['user_id']]);
    $user = $stmt->fetch();
    return $user['theme'] ?? 'dark-default';
}

// ─── Open Redirect — безопасный редирект ─────────────────────────────────────

/**
 * Редирект только на наш домен (защита от Open Redirect через HTTP_REFERER)
 */
function safeRedirect(string $url, string $fallback = '/'): void {
    $appHost    = parse_url(APP_URL, PHP_URL_HOST) ?: '';
    $targetHost = parse_url($url, PHP_URL_HOST) ?: '';

    // Если хост совпадает с нашим или URL относительный — разрешаем
    if ($targetHost === '' || $targetHost === $appHost) {
        header('Location: ' . $url);
    } else {
        header('Location: ' . $fallback);
    }
    exit;
}

// ─── TOTP — шифрование/расшифровка секрета ───────────────────────────────────

define('TOTP_ENCRYPTION_KEY', $env['TOTP_ENCRYPTION_KEY'] ?? '');

function encryptSecret(string $secret): string {
    $key = TOTP_ENCRYPTION_KEY;
    $iv  = random_bytes(16);
    $encrypted = openssl_encrypt($secret, 'AES-256-CBC', $key, 0, $iv);
    return base64_encode($iv . $encrypted);
}

function decryptSecret(string $data): string {
    $key  = TOTP_ENCRYPTION_KEY;
    $raw  = base64_decode($data);
    $iv   = substr($raw, 0, 16);
    $encrypted = substr($raw, 16);
    return openssl_decrypt($encrypted, 'AES-256-CBC', $key, 0, $iv);
}