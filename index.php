<?php

require_once __DIR__ . '/config.php';

// Запускаем сессию
session_name(SESSION_NAME);
session_start();

// Простой роутер
$uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$uri = rtrim($uri, '/');
$method = $_SERVER['REQUEST_METHOD'];

// Если не авторизован — только страница логина доступна
if (!isset($_SESSION['user_id']) && $uri !== '/login') {
    header('Location: /login');
    exit;
}

// Роутинг
match(true) {
    // Авторизация
    $uri === '/login' && $method === 'GET'  => require __DIR__ . '/src/controllers/AuthController.php',
    $uri === '/login' && $method === 'POST' => require __DIR__ . '/src/controllers/AuthController.php',
    $uri === '/logout'                       => require __DIR__ . '/src/controllers/AuthController.php',

    // Канбан (главная)
    $uri === '' || $uri === '/'             => require __DIR__ . '/src/controllers/BoardController.php',

    // Задачи
    str_starts_with($uri, '/tasks')         => require __DIR__ . '/src/controllers/TaskController.php',

    // Архив
    $uri === '/archive'                     => require __DIR__ . '/src/controllers/ArchiveController.php',

    // Настройки
    $uri === '/settings'                    => require __DIR__ . '/src/controllers/SettingsController.php',

    // 404
    default => (function() {
        http_response_code(404);
        echo '404 — страница не найдена';
    })()
};