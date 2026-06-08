<?php

require_once __DIR__ . '/../../config.php';

$method  = $_SERVER['REQUEST_METHOD'];
$success = $_GET['success'] ?? null;
$error   = '';

// Все POST-запросы проходят CSRF-проверку
if ($method === 'POST') {
    csrfVerify();
}

// ===========================
// ПРОЕКТЫ
// ===========================

if ($method === 'POST' && ($_POST['form'] ?? '') === 'add_project') {
    $name = mb_substr(trim($_POST['name'] ?? ''), 0, 255);
    if ($name) {
        db()->prepare('INSERT INTO projects (name) VALUES (?)')->execute([$name]);
        header('Location: /settings?success=project_added');
        exit;
    } else {
        $error = 'Введите название проекта';
    }
}

if ($method === 'POST' && ($_POST['form'] ?? '') === 'rename_project') {
    $id   = (int)$_POST['id'];
    $name = mb_substr(trim($_POST['name'] ?? ''), 0, 255);
    if ($id && $name) {
        db()->prepare('UPDATE projects SET name = ? WHERE id = ?')->execute([$name, $id]);
        header('Location: /settings?success=project_renamed');
        exit;
    }
}

if ($method === 'POST' && ($_POST['form'] ?? '') === 'toggle_project') {
    $id  = (int)$_POST['id'];
    $val = (int)(bool)$_POST['archived']; // строго 0 или 1
    db()->prepare('UPDATE projects SET is_archived = ? WHERE id = ?')->execute([$val, $id]);
    header('Location: /settings?success=project_updated');
    exit;
}

// ===========================
// ТЕГИ
// ===========================

if ($method === 'POST' && ($_POST['form'] ?? '') === 'add_tag') {
    $name  = mb_substr(trim($_POST['name'] ?? ''), 0, 100);
    $color = preg_match('/^#[0-9a-fA-F]{6}$/', $_POST['color'] ?? '') ? $_POST['color'] : '#888888';
    if ($name) {
        db()->prepare('INSERT INTO tags (name, color) VALUES (?, ?)')->execute([$name, $color]);
        header('Location: /settings?success=tag_added');
        exit;
    } else {
        $error = 'Введите название тега';
    }
}

if ($method === 'POST' && ($_POST['form'] ?? '') === 'delete_tag') {
    $id = (int)$_POST['id'];
    db()->prepare('DELETE FROM tags WHERE id = ?')->execute([$id]);
    header('Location: /settings?success=tag_deleted');
    exit;
}

// ===========================
// ТЕМА
// ===========================

if ($method === 'POST' && ($_POST['form'] ?? '') === 'set_theme') {
    $allowed = ['dark-default', 'dark-blue', 'dark-green', 'light-default', 'light-warm', 'light-purple'];
    $theme   = in_array($_POST['theme'] ?? '', $allowed) ? $_POST['theme'] : 'dark-default';
    db()->prepare('UPDATE users SET theme = ? WHERE id = ?')->execute([$theme, $_SESSION['user_id']]);
    header('Location: /settings?success=theme');
    exit;
}

// ===========================
// ОТДЕЛЫ
// ===========================

if ($method === 'POST' && ($_POST['form'] ?? '') === 'add_department') {
    $name = mb_substr(trim($_POST['name'] ?? ''), 0, 255);
    if ($name) {
        db()->prepare('INSERT INTO departments (name) VALUES (?)')->execute([$name]);
        header('Location: /settings?success=1');
        exit;
    }
}

if ($method === 'POST' && ($_POST['form'] ?? '') === 'rename_department') {
    $id   = (int)$_POST['id'];
    $name = mb_substr(trim($_POST['name'] ?? ''), 0, 255);
    if ($id && $name) {
        db()->prepare('UPDATE departments SET name = ? WHERE id = ?')->execute([$name, $id]);
        header('Location: /settings?success=1');
        exit;
    }
}

if ($method === 'POST' && ($_POST['form'] ?? '') === 'delete_department') {
    $id = (int)$_POST['id'];
    db()->prepare('DELETE FROM departments WHERE id = ?')->execute([$id]);
    header('Location: /settings?success=1');
    exit;
}

// ===========================
// ЗАКАЗЧИКИ
// ===========================

if ($method === 'POST' && ($_POST['form'] ?? '') === 'add_customer') {
    $name          = mb_substr(trim($_POST['name'] ?? ''), 0, 255);
    $department_id = (int)$_POST['department_id'];
    if ($name && $department_id) {
        db()->prepare('INSERT INTO customers (department_id, name) VALUES (?, ?)')->execute([$department_id, $name]);
        header('Location: /settings?success=1');
        exit;
    }
}

if ($method === 'POST' && ($_POST['form'] ?? '') === 'rename_customer') {
    $id   = (int)$_POST['id'];
    $name = mb_substr(trim($_POST['name'] ?? ''), 0, 255);
    if ($id && $name) {
        db()->prepare('UPDATE customers SET name = ? WHERE id = ?')->execute([$name, $id]);
        header('Location: /settings?success=1');
        exit;
    }
}

if ($method === 'POST' && ($_POST['form'] ?? '') === 'delete_customer') {
    $id = (int)$_POST['id'];
    db()->prepare('DELETE FROM customers WHERE id = ?')->execute([$id]);
    header('Location: /settings?success=1');
    exit;
}

// ===========================
// 2FA — НАСТРОЙКА
// ===========================

// Показать QR-код
if ($method === 'GET' && isset($_GET['setup_2fa'])) {
    require_once __DIR__ . '/../lib/GoogleAuthenticator.php';
    $ga     = new PHPGangsta_GoogleAuthenticator();
    $secret = $ga->createSecret();
    $_SESSION['totp_setup_secret'] = $secret;
    $label  = urlencode('Most (' . $_SESSION['user_name'] . ')');
    $qrUrl  = $ga->getQRCodeGoogleUrl($label, $secret);
    require __DIR__ . '/../views/setup_2fa.php';
    exit;
}

// Подтвердить и включить 2FA
if ($method === 'POST' && ($_POST['form'] ?? '') === 'enable_2fa') {
    csrfVerify();
    require_once __DIR__ . '/../lib/GoogleAuthenticator.php';
    $ga     = new PHPGangsta_GoogleAuthenticator();
    $secret = $_SESSION['totp_setup_secret'] ?? '';
    $code   = trim($_POST['code'] ?? '');

    if ($secret && $ga->verifyCode($secret, $code, 1)) {
        $encrypted = encryptSecret($secret);
        db()->prepare('UPDATE users SET totp_secret = ?, totp_enabled = 1 WHERE id = ?')
            ->execute([$encrypted, $_SESSION['user_id']]);
        unset($_SESSION['totp_setup_secret']);
        header('Location: /settings?success=2fa_enabled');
        exit;
    } else {
        $error = 'Неверный код — попробуйте ещё раз';
        require_once __DIR__ . '/../lib/GoogleAuthenticator.php';
        $ga     = new PHPGangsta_GoogleAuthenticator();
        $secret = $_SESSION['totp_setup_secret'];
        $label  = urlencode('Most (' . $_SESSION['user_name'] . ')');
        $qrUrl  = $ga->getQRCodeGoogleUrl($label, $secret);
        require __DIR__ . '/../views/setup_2fa.php';
        exit;
    }
}

// Отключить 2FA
if ($method === 'POST' && ($_POST['form'] ?? '') === 'disable_2fa') {
    csrfVerify();
    require_once __DIR__ . '/../lib/GoogleAuthenticator.php';
    $ga   = new PHPGangsta_GoogleAuthenticator();
    $stmt = db()->prepare('SELECT totp_secret FROM users WHERE id = ?');
    $stmt->execute([$_SESSION['user_id']]);
    $user = $stmt->fetch();

    $secret = decryptSecret($user['totp_secret'] ?? '');
    $code   = trim($_POST['code'] ?? '');

    if ($secret && $ga->verifyCode($secret, $code, 1)) {
        db()->prepare('UPDATE users SET totp_secret = NULL, totp_enabled = 0 WHERE id = ?')
            ->execute([$_SESSION['user_id']]);
        header('Location: /settings?success=2fa_disabled');
        exit;
    } else {
        $error = 'Неверный код — 2FA не отключена';
    }
}

// Данные для отображения
$projects    = db()->query('SELECT * FROM projects ORDER BY is_archived ASC, name ASC')->fetchAll();
$tags        = db()->query('SELECT * FROM tags ORDER BY name')->fetchAll();
$departments = db()->query('SELECT * FROM departments ORDER BY name')->fetchAll();
$customers   = db()->query('SELECT c.*, d.name AS department_name FROM customers c JOIN departments d ON d.id = c.department_id ORDER BY d.name, c.name')->fetchAll();



require __DIR__ . '/../views/settings.php';
