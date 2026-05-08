<?php

require_once __DIR__ . '/config.php';

// ─── Заголовки безопасности (до session_start) ────────────────────────────────

function applySecurityHeaders(): void {
    $nonce = base64_encode(random_bytes(16));
    $_SERVER['CSP_NONCE'] = $nonce;

    header("Content-Security-Policy: " .
           "default-src 'self'; " .
           "script-src 'self' 'nonce-$nonce' cdnjs.cloudflare.com; " .
           "style-src 'self' 'nonce-$nonce' cdnjs.cloudflare.com; " .
           "img-src 'self' data:; " .
           "font-src 'self' cdnjs.cloudflare.com; " .
           "connect-src 'self'; " .
           "frame-ancestors 'none'; " .
           "form-action 'self'; " .
           "base-uri 'self';");
}

applySecurityHeaders();
// ─── Безопасные настройки сессии ─────────────────────────────────────────────
// Настраиваем ДО session_start()
ini_set('session.cookie_httponly', '1');   // JS не может читать куки — защита от XSS
ini_set('session.cookie_secure',   '1');   // Только по HTTPS
ini_set('session.cookie_samesite', 'Strict'); // Защита от CSRF через куки
ini_set('session.use_strict_mode', '1');   // Запрет принятия чужих session ID
ini_set('session.gc_maxlifetime',  '28800'); // Сессия живёт максимум 8 часов

session_name(SESSION_NAME);
session_start();

// ─── Роутер ───────────────────────────────────────────────────────────────────
$uri    = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$uri    = rtrim($uri, '/');
$method = $_SERVER['REQUEST_METHOD'];

// Если не авторизован — только страница логина
if (!isset($_SESSION['user_id']) && $uri !== '/login') {
    header('Location: /login');
    exit;
}

// Роутинг
match(true) {
    // Авторизация
    $uri === '/login'  => require __DIR__ . '/src/controllers/AuthController.php',
    $uri === '/logout' => require __DIR__ . '/src/controllers/AuthController.php',

    // Главная — канбан или список
    ($uri === '' || $uri === '/') && ($_GET['view'] ?? 'board') === 'board' => require __DIR__ . '/src/controllers/BoardController.php',
    ($uri === '' || $uri === '/') && ($_GET['view'] ?? '') === 'list'       => require __DIR__ . '/src/controllers/ListController.php',

    // Задачи
    str_starts_with($uri, '/tasks') => require __DIR__ . '/src/controllers/TaskController.php',

    // Архив
    $uri === '/archive' => require __DIR__ . '/src/controllers/ArchiveController.php',

    // Настройки
    $uri === '/settings' => require __DIR__ . '/src/controllers/SettingsController.php',

    // API
    str_starts_with($uri, '/api') => require __DIR__ . '/src/controllers/ApiController.php',

    // 404
    default => (function() {
        http_response_code(404);
        echo '404 — страница не найдена';
    })()
};
