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
define('APP_URL',  $env['APP_URL']);

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
            die('Ошибка подключения к БД: ' . $e->getMessage());
        }
    }
    return $pdo;
}

function getUserTheme(): string {
    if (!isset($_SESSION['user_id'])) return 'dark-default';
    $stmt = db()->prepare('SELECT theme FROM users WHERE id = ?');
    $stmt->execute([$_SESSION['user_id']]);
    $user = $stmt->fetch();
    return $user['theme'] ?? 'dark-default';
}