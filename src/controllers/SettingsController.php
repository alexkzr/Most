<?php

require_once __DIR__ . '/../../config.php';

$method  = $_SERVER['REQUEST_METHOD'];
$action  = $_GET['action'] ?? null;
$success = $_GET['success'] ?? null;
$error   = '';

// ===========================
// ПРОЕКТЫ
// ===========================

// Добавить проект
if ($method === 'POST' && $_POST['form'] === 'add_project') {
    $name = trim($_POST['name'] ?? '');
    if ($name) {
        db()->prepare('INSERT INTO projects (name) VALUES (?)')->execute([$name]);
        header('Location: /settings?success=project_added');
        exit;
    } else {
        $error = 'Введите название проекта';
    }
}

// Переименовать проект
if ($method === 'POST' && $_POST['form'] === 'rename_project') {
    $id   = (int)$_POST['id'];
    $name = trim($_POST['name'] ?? '');
    if ($id && $name) {
        db()->prepare('UPDATE projects SET name = ? WHERE id = ?')->execute([$name, $id]);
        header('Location: /settings?success=project_renamed');
        exit;
    }
}

// Архивировать/разархивировать проект
if ($method === 'POST' && $_POST['form'] === 'toggle_project') {
    $id  = (int)$_POST['id'];
    $val = (int)$_POST['archived'];
    db()->prepare('UPDATE projects SET is_archived = ? WHERE id = ?')->execute([$val, $id]);
    header('Location: /settings?success=project_updated');
    exit;
}

// ===========================
// ТЕГИ
// ===========================

// Добавить тег
if ($method === 'POST' && $_POST['form'] === 'add_tag') {
    $name  = trim($_POST['name'] ?? '');
    $color = $_POST['color'] ?? '#888888';
    if ($name) {
        db()->prepare('INSERT INTO tags (name, color) VALUES (?, ?)')->execute([$name, $color]);
        header('Location: /settings?success=tag_added');
        exit;
    } else {
        $error = 'Введите название тега';
    }
}

// Удалить тег
if ($method === 'POST' && $_POST['form'] === 'delete_tag') {
    $id = (int)$_POST['id'];
    db()->prepare('DELETE FROM tags WHERE id = ?')->execute([$id]);
    header('Location: /settings?success=tag_deleted');
    exit;
}

// Сменить тему
if ($method === 'POST' && $_POST['form'] === 'set_theme') {
    $allowed = ['dark-default', 'dark-blue', 'dark-green', 'light-default', 'light-warm', 'light-purple'];
    $theme   = $_POST['theme'] ?? 'dark-default';
    if (in_array($theme, $allowed)) {
        db()->prepare('UPDATE users SET theme = ? WHERE id = ?')->execute([$theme, $_SESSION['user_id']]);
        $_SESSION['theme'] = $theme;
    }
    header('Location: /settings?success=theme');
    exit;
}

// ===========================
// ОТДЕЛЫ
// ===========================

if ($method === 'POST' && $_POST['form'] === 'add_department') {
    $name = trim($_POST['name'] ?? '');
    if ($name) {
        db()->prepare('INSERT INTO departments (name) VALUES (?)')->execute([$name]);
        header('Location: /settings?success=1');
        exit;
    }
}

if ($method === 'POST' && $_POST['form'] === 'rename_department') {
    $id   = (int)$_POST['id'];
    $name = trim($_POST['name'] ?? '');
    if ($id && $name) {
        db()->prepare('UPDATE departments SET name = ? WHERE id = ?')->execute([$name, $id]);
        header('Location: /settings?success=1');
        exit;
    }
}

if ($method === 'POST' && $_POST['form'] === 'delete_department') {
    $id = (int)$_POST['id'];
    db()->prepare('DELETE FROM departments WHERE id = ?')->execute([$id]);
    header('Location: /settings?success=1');
    exit;
}

// ===========================
// ЗАКАЗЧИКИ
// ===========================

if ($method === 'POST' && $_POST['form'] === 'add_customer') {
    $name          = trim($_POST['name'] ?? '');
    $department_id = (int)$_POST['department_id'];
    if ($name && $department_id) {
        db()->prepare('INSERT INTO customers (department_id, name) VALUES (?, ?)')->execute([$department_id, $name]);
        header('Location: /settings?success=1');
        exit;
    }
}

if ($method === 'POST' && $_POST['form'] === 'rename_customer') {
    $id   = (int)$_POST['id'];
    $name = trim($_POST['name'] ?? '');
    if ($id && $name) {
        db()->prepare('UPDATE customers SET name = ? WHERE id = ?')->execute([$name, $id]);
        header('Location: /settings?success=1');
        exit;
    }
}

if ($method === 'POST' && $_POST['form'] === 'delete_customer') {
    $id = (int)$_POST['id'];
    db()->prepare('DELETE FROM customers WHERE id = ?')->execute([$id]);
    header('Location: /settings?success=1');
    exit;
}

// Данные для отображения
// Данные для отображения
$projects    = db()->query('SELECT * FROM projects ORDER BY is_archived ASC, name ASC')->fetchAll();
$tags        = db()->query('SELECT * FROM tags ORDER BY name')->fetchAll();
$departments = db()->query('SELECT * FROM departments ORDER BY name')->fetchAll();
$customers   = db()->query('SELECT c.*, d.name AS department_name FROM customers c JOIN departments d ON d.id = c.department_id ORDER BY d.name, c.name')->fetchAll();

require __DIR__ . '/../views/settings.php';