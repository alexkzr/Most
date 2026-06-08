<?php

require_once __DIR__ . '/../../config.php';

$uri    = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$method = $_SERVER['REQUEST_METHOD'];

// ─── Выход ────────────────────────────────────────────────────────────────────
if ($uri === '/logout') {
    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $p = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000,
            $p['path'], $p['domain'], $p['secure'], $p['httponly']);
    }
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

// ─── Обработка формы логина ───────────────────────────────────────────────────
if ($method === 'POST') {

    // Брутфорс-защита
    $attempts    = $_SESSION['login_attempts']     ?? 0;
    $lastAttempt = $_SESSION['login_last_attempt'] ?? 0;
    $lockoutTime = 300;

    if ($attempts >= 5 && (time() - $lastAttempt) < $lockoutTime) {
        $remaining = $lockoutTime - (time() - $lastAttempt);
        $error = 'Слишком много попыток. Подождите ' . ceil($remaining / 60) . ' мин.';
        require __DIR__ . '/../views/login.php';
        exit;
    }

    $login    = trim($_POST['login']    ?? '');
    $password = trim($_POST['password'] ?? '');

    if ($login && $password) {
        $stmt = db()->prepare('SELECT id, name, password_hash, totp_enabled FROM users WHERE login = ?');
        $stmt->execute([$login]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['password_hash'])) {
            $_SESSION['login_attempts']     = 0;
            $_SESSION['login_last_attempt'] = 0;

            session_regenerate_id(true);

            if ($user['totp_enabled']) {
                $_SESSION['pending_2fa']       = true;
                $_SESSION['pending_user_id']   = $user['id'];
                $_SESSION['pending_user_name'] = $user['name'];
                header('Location: /login/2fa');
                exit;
            } else {
                $_SESSION['user_id']   = $user['id'];
                $_SESSION['user_name'] = $user['name'];
                header('Location: /');
                exit;
            }
        } else {
            $_SESSION['login_attempts']     = $attempts + 1;
            $_SESSION['login_last_attempt'] = time();
            $error = 'Неверный логин или пароль';
            usleep(300000);
        }
    } else {
        $error = 'Заполните все поля';
    }
}

require __DIR__ . '/../views/login.php';