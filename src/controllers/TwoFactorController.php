<?php

require_once __DIR__ . '/../../config.php';
require_once __DIR__ . '/../lib/GoogleAuthenticator.php';

$method = $_SERVER['REQUEST_METHOD'];
$error  = '';

// Если нет pending_2fa — на логин
if (empty($_SESSION['pending_2fa'])) {
    header('Location: /login');
    exit;
}

if ($method === 'POST') {
    csrfVerify();

    $code   = trim($_POST['code'] ?? '');
    $userId = $_SESSION['pending_user_id'] ?? null;

    if (!$userId) {
        header('Location: /login');
        exit;
    }

    $stmt = db()->prepare('SELECT totp_secret FROM users WHERE id = ?');
    $stmt->execute([$userId]);
    $user = $stmt->fetch();

    if (!$user || !$user['totp_secret']) {
        header('Location: /login');
        exit;
    }

    $ga     = new PHPGangsta_GoogleAuthenticator();
    $secret = decryptSecret($user['totp_secret']);

    if ($ga->verifyCode($secret, $code, 1)) {
        session_regenerate_id(true);
        $_SESSION['user_id']   = $_SESSION['pending_user_id'];
        $_SESSION['user_name'] = $_SESSION['pending_user_name'];
        unset($_SESSION['pending_2fa'], $_SESSION['pending_user_id'], $_SESSION['pending_user_name']);
        header('Location: /');
        exit;
    } else {
        $error = 'Неверный код. Попробуйте ещё раз.';
    }
}

require __DIR__ . '/../views/two_factor.php';