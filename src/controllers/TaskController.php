<?php

require_once __DIR__ . '/../../config.php';

$uri    = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$method = $_SERVER['REQUEST_METHOD'];

// Определяем действие
// /tasks/create
// /tasks/123
// /tasks/123/edit
// /tasks/123/move
// /tasks/123/archive

$parts = explode('/', trim($uri, '/'));
// $parts[0] = 'tasks'
// $parts[1] = 'create' или ID
// $parts[2] = 'edit', 'move', 'archive' (опционально)

$action = $parts[1] ?? null;
$sub    = $parts[2] ?? null;

// Вспомогательная функция — логируем изменение в историю
function logHistory(int $taskId, int $userId, string $action, string $oldValue = '', string $newValue = ''): void {
    $stmt = db()->prepare('
        INSERT INTO history (task_id, user_id, action, old_value, new_value)
        VALUES (?, ?, ?, ?, ?)
    ');
    $stmt->execute([$taskId, $userId, $action, $oldValue, $newValue]);
}

// ===========================
// СОЗДАНИЕ ЗАДАЧИ
// ===========================
if ($action === 'create') {

    // Данные для формы
    $projects  = db()->query('SELECT * FROM projects WHERE is_archived = 0 ORDER BY name')->fetchAll();
    $assignees = db()->query('SELECT * FROM assignees ORDER BY name')->fetchAll();
    $tags      = db()->query('SELECT * FROM tags ORDER BY name')->fetchAll();
    $error     = '';

    if ($method === 'POST') {
        $title        = trim($_POST['title'] ?? '');
        $description  = trim($_POST['description'] ?? '');
        $project_id   = (int)($_POST['project_id'] ?? 0);
        $assignee_id  = (int)($_POST['assignee_id'] ?? 0) ?: null;
        $customer     = trim($_POST['customer'] ?? '');
        $priority     = $_POST['priority'] ?? 'medium';
        $deadline     = $_POST['deadline'] ?? null ?: null;
        $estimated    = $_POST['estimated_hours'] ?? null ?: null;
        $selected_tags = $_POST['tags'] ?? [];

        if (!$title || !$project_id) {
            $error = 'Заполните название и проект';
        } else {
            $stmt = db()->prepare('
                INSERT INTO tasks
                    (title, description, project_id, assignee_id, customer, priority, deadline, estimated_hours, created_by)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
            ');
            $stmt->execute([
                $title, $description, $project_id, $assignee_id,
                $customer, $priority, $deadline, $estimated,
                $_SESSION['user_id']
            ]);
            $taskId = db()->lastInsertId();

            // Теги
            if ($selected_tags) {
                $stmtTag = db()->prepare('INSERT INTO task_tags (task_id, tag_id) VALUES (?, ?)');
                foreach ($selected_tags as $tagId) {
                    $stmtTag->execute([$taskId, (int)$tagId]);
                }
            }

            // История
            logHistory($taskId, $_SESSION['user_id'], 'Задача создана', '', $title);

            header('Location: /tasks/' . $taskId);
            exit;
        }
    }

    require __DIR__ . '/../views/task_form.php';
    exit;
}

// ===========================
// ПРОСМОТР ЗАДАЧИ
// ===========================
if (is_numeric($action) && !$sub) {
    $taskId = (int)$action;

    $stmt = db()->prepare('
        SELECT t.*,
               a.name  AS assignee_name,
               p.name  AS project_name,
               u.name  AS created_by_name,
               u2.name AS archive_requested_by_name
        FROM tasks t
        LEFT JOIN assignees a ON a.id = t.assignee_id
        LEFT JOIN projects  p ON p.id = t.project_id
        LEFT JOIN users     u  ON u.id  = t.created_by
        LEFT JOIN users     u2 ON u2.id = t.archive_requested_by
        WHERE t.id = ?
    ');
    $stmt->execute([$taskId]);
    $task = $stmt->fetch();

    if (!$task) {
        http_response_code(404);
        echo '404 — задача не найдена';
        exit;
    }

    // Теги задачи
    $stmt2 = db()->prepare('
        SELECT tg.* FROM task_tags tt
        JOIN tags tg ON tg.id = tt.tag_id
        WHERE tt.task_id = ?
    ');
    $stmt2->execute([$taskId]);
    $taskTags = $stmt2->fetchAll();

    // Комментарии
    $stmt3 = db()->prepare('
        SELECT c.*, u.name AS user_name
        FROM comments c
        JOIN users u ON u.id = c.user_id
        WHERE c.task_id = ?
        ORDER BY c.created_at ASC
    ');
    $stmt3->execute([$taskId]);
    $comments = $stmt3->fetchAll();

    // Сниппеты кода
    $stmt4 = db()->prepare('
        SELECT cs.*, u.name AS user_name
        FROM code_snippets cs
        JOIN users u ON u.id = cs.user_id
        WHERE cs.task_id = ?
        ORDER BY cs.created_at ASC
    ');
    $stmt4->execute([$taskId]);
    $snippets = $stmt4->fetchAll();

    // История
    $stmt5 = db()->prepare('
        SELECT h.*, u.name AS user_name
        FROM history h
        JOIN users u ON u.id = h.user_id
        WHERE h.task_id = ?
        ORDER BY h.created_at ASC
    ');
    $stmt5->execute([$taskId]);
    $history = $stmt5->fetchAll();

    // Добавление комментария
    if ($method === 'POST' && isset($_POST['comment'])) {
        $content = trim($_POST['comment']);
        if ($content) {
            $stmt = db()->prepare('INSERT INTO comments (task_id, user_id, content) VALUES (?, ?, ?)');
            $stmt->execute([$taskId, $_SESSION['user_id'], $content]);
            logHistory($taskId, $_SESSION['user_id'], 'Добавлен комментарий');
            header('Location: /tasks/' . $taskId . '#comments');
            exit;
        }
    }

    // Добавление сниппета
    if ($method === 'POST' && isset($_POST['snippet_after'])) {
        $desc   = trim($_POST['snippet_desc'] ?? '');
        $before = trim($_POST['snippet_before'] ?? '');
        $after  = trim($_POST['snippet_after']);
        if ($after) {
            $stmt = db()->prepare('
                INSERT INTO code_snippets (task_id, user_id, description, code_before, code_after)
                VALUES (?, ?, ?, ?, ?)
            ');
            $stmt->execute([$taskId, $_SESSION['user_id'], $desc, $before, $after]);
            logHistory($taskId, $_SESSION['user_id'], 'Добавлен сниппет кода', '', $desc);
            header('Location: /tasks/' . $taskId . '#code');
            exit;
        }
    }

    require __DIR__ . '/../views/task_view.php';
    exit;
}

// ===========================
// ПЕРЕМЕЩЕНИЕ (смена статуса)
// ===========================
if (is_numeric($action) && $sub === 'move' && $method === 'POST') {
    $taskId    = (int)$action;
    $newStatus = $_POST['status'] ?? '';

    $allowed = ['new', 'in_progress', 'testing', 'done'];
    if (!in_array($newStatus, $allowed)) {
        http_response_code(400);
        exit;
    }

    $stmt = db()->prepare('SELECT status FROM tasks WHERE id = ?');
    $stmt->execute([$taskId]);
    $task = $stmt->fetch();

    if ($task) {
        $statusLabels = [
            'new'         => 'Новые',
            'in_progress' => 'В работе',
            'testing'     => 'Тестирование',
            'done'        => 'Готово',
        ];
        db()->prepare('UPDATE tasks SET status = ? WHERE id = ?')->execute([$newStatus, $taskId]);
        logHistory(
            $taskId,
            $_SESSION['user_id'],
            'Статус изменён',
            $statusLabels[$task['status']] ?? $task['status'],
            $statusLabels[$newStatus] ?? $newStatus
        );
    }

    header('Location: ' . ($_SERVER['HTTP_REFERER'] ?? '/'));
    exit;
}

// ===========================
// АРХИВИРОВАНИЕ
// ===========================
if (is_numeric($action) && $sub === 'archive' && $method === 'POST') {
    $taskId = (int)$action;

    $stmt = db()->prepare('SELECT * FROM tasks WHERE id = ?');
    $stmt->execute([$taskId]);
    $task = $stmt->fetch();

    if (!$task) { http_response_code(404); exit; }

    // Если уже ожидает архивирования — подтверждаем
    if ($task['status'] === 'pending_archive') {
        if ($task['archive_requested_by'] == $_SESSION['user_id']) {
            // Тот же пользователь — нельзя
            header('Location: /tasks/' . $taskId . '?error=same_user');
            exit;
        }
        // Второй пользователь подтверждает
        db()->prepare('UPDATE tasks SET is_archived = 1, status = "done" WHERE id = ?')->execute([$taskId]);
        logHistory($taskId, $_SESSION['user_id'], 'Задача архивирована');
        header('Location: /');
        exit;
    }

    // Первый запрос на архивирование
    $reason        = $_POST['archive_reason'] ?? 'other';
    $reasonCustom  = trim($_POST['archive_reason_custom'] ?? '');

    db()->prepare('
        UPDATE tasks
        SET status = "pending_archive",
            archive_requested_by = ?,
            archive_reason = ?,
            archive_reason_custom = ?
        WHERE id = ?
    ')->execute([$_SESSION['user_id'], $reason, $reasonCustom, $taskId]);

    logHistory($taskId, $_SESSION['user_id'], 'Запрошено архивирование', '', $reason);
    header('Location: /tasks/' . $taskId);
    exit;
}

// ===========================
// ОТМЕНА АРХИВИРОВАНИЯ
// ===========================
if (is_numeric($action) && $sub === 'unarchive' && $method === 'POST') {
    $taskId = (int)$action;
    db()->prepare('
        UPDATE tasks
        SET status = "in_progress",
            archive_requested_by = NULL,
            archive_reason = NULL,
            archive_reason_custom = NULL
        WHERE id = ?
    ')->execute([$taskId]);
    logHistory($taskId, $_SESSION['user_id'], 'Архивирование отменено');
    header('Location: /tasks/' . $taskId);
    exit;
}

http_response_code(404);
echo '404';