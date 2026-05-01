<?php

require_once __DIR__ . '/../../config.php';

$uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$method = $_SERVER['REQUEST_METHOD'];

// Выход
if ($uri === '/logout') {
    session_destroy();
    header('Location: /login');
    exit;
}

// Уже авторизован — на главную
if (isset($_SESSION['user_id'])) {
    header('Location: /');
    exit;
}

$error = '';

// Обработка формы логина
if ($method === 'POST') {
    $login    = trim($_POST['login'] ?? '');
    $password = trim($_POST['password'] ?? '');

    if ($login && $password) {
        $stmt = db()->prepare('SELECT id, name, password_hash FROM users WHERE login = ?');
        $stmt->execute([$login]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['password_hash'])) {
            $_SESSION['user_id']   = $user['id'];
            $_SESSION['user_name'] = $user['name'];
            header('Location: /');
            exit;
        } else {
            $error = 'Неверный логин или пароль';
        }
    } else {
        $error = 'Заполните все поля';
    }
}

// Показываем страницу логина
require __DIR__ . '/../views/login.php';