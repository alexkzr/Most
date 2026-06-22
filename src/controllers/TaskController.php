<?php

require_once __DIR__ . '/../../config.php';

$uri    = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$method = $_SERVER['REQUEST_METHOD'];

$parts  = explode('/', trim($uri, '/'));
$action = $parts[1] ?? null;
$sub    = $parts[2] ?? null;

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

    $projects      = db()->query('SELECT * FROM projects WHERE is_archived = 0 ORDER BY name')->fetchAll();
    $assignees     = db()->query('SELECT * FROM assignees ORDER BY name')->fetchAll();
    $departments   = db()->query('SELECT * FROM departments ORDER BY name')->fetchAll();
    $customers_all = db()->query('SELECT * FROM customers ORDER BY name')->fetchAll();
    $tags          = db()->query('SELECT * FROM tags ORDER BY name')->fetchAll();
    $error         = '';

    if ($method === 'POST') {
        csrfVerify(); // ← CSRF-защита

        $title         = trim($_POST['title'] ?? '');
        $description   = sanitizeHtml(trim($_POST['description'] ?? '')); // ← очищаем HTML
        $project_id    = (int)($_POST['project_id'] ?? 0);
        $assignee_id   = (int)($_POST['assignee_id'] ?? 0) ?: null;
        $department_id = (int)($_POST['department_id'] ?? 0) ?: null;
        $customer_id   = (int)($_POST['customer_id'] ?? 0) ?: null;
        $is_presidency = isset($_POST['is_presidency']) ? 1 : 0;
        $priority      = in_array($_POST['priority'] ?? '', ['high','medium','low']) ? $_POST['priority'] : 'medium';
        $estimated     = is_numeric($_POST['estimated_hours'] ?? '') ? (float)$_POST['estimated_hours'] : null;
        $complexity    = in_array((int)($_POST['complexity'] ?? 0), [1,2,3,4,5]) ? (int)$_POST['complexity'] : null;
        $work_type     = in_array($_POST['work_type'] ?? '', ['new_project','improvement','bugfix']) ? $_POST['work_type'] : null;
        $date_start    = preg_match('/^\d{4}-\d{2}-\d{2}$/', $_POST['date_start'] ?? '') ? $_POST['date_start'] : null;
        $date_end      = preg_match('/^\d{4}-\d{2}-\d{2}$/', $_POST['date_end']   ?? '') ? $_POST['date_end']   : null;
        $selected_tags = array_map('intval', $_POST['tags'] ?? []);
        $color = preg_match('/^#[0-9a-fA-F]{6}$/', $_POST['color'] ?? '') ? $_POST['color'] : null;

        if (!$title || !$project_id) {
            $error = 'Заполните название и проект';
        } else {
            $stmt = db()->prepare('
                INSERT INTO tasks
                    (title, description, project_id, assignee_id, department_id, customer_id,
                     is_presidency, priority, complexity, work_type, color, estimated_hours,
                     date_start, date_end, created_by)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ');
            $stmt->execute([
                $title, $description, $project_id, $assignee_id,
                $department_id, $customer_id, $is_presidency,
                $priority, $complexity, $work_type, $color,
                $estimated, $date_start, $date_end,
                $_SESSION['user_id']
            ]);
            $taskId = db()->lastInsertId();

            if ($selected_tags) {
                $stmtTag = db()->prepare('INSERT INTO task_tags (task_id, tag_id) VALUES (?, ?)');
                foreach ($selected_tags as $tagId) {
                    $stmtTag->execute([$taskId, $tagId]);
                }
            }

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

    $stmt2 = db()->prepare('
        SELECT tg.* FROM task_tags tt
        JOIN tags tg ON tg.id = tt.tag_id
        WHERE tt.task_id = ?
    ');
    $stmt2->execute([$taskId]);
    $taskTags = $stmt2->fetchAll();

    $stmt3 = db()->prepare('
        SELECT c.*, u.name AS user_name
        FROM comments c
        JOIN users u ON u.id = c.user_id
        WHERE c.task_id = ?
        ORDER BY c.created_at ASC
    ');
    $stmt3->execute([$taskId]);
    $comments = $stmt3->fetchAll();

    $stmt4 = db()->prepare('
        SELECT cs.*, u.name AS user_name
        FROM code_snippets cs
        JOIN users u ON u.id = cs.user_id
        WHERE cs.task_id = ?
        ORDER BY cs.created_at ASC
    ');
    $stmt4->execute([$taskId]);
    $snippets = $stmt4->fetchAll();

    $stmt5 = db()->prepare('
        SELECT h.*, u.name AS user_name
        FROM history h
        JOIN users u ON u.id = h.user_id
        WHERE h.task_id = ?
        ORDER BY h.created_at ASC
    ');
    $stmt5->execute([$taskId]);
    $history = $stmt5->fetchAll();
    // Файлы
    $stmt6 = db()->prepare('
        SELECT f.*, u.name AS user_name
        FROM task_files f
        JOIN users u ON u.id = f.user_id
        WHERE f.task_id = ?
        ORDER BY f.created_at ASC
    ');
    $stmt6->execute([$taskId]);
    $files = $stmt6->fetchAll();

    if ($method === 'POST' && isset($_POST['comment'])) {
        csrfVerify(); // ← CSRF-защита
        $content = trim($_POST['comment']);
        if ($content) {
            $stmt = db()->prepare('INSERT INTO comments (task_id, user_id, content) VALUES (?, ?, ?)');
            $stmt->execute([$taskId, $_SESSION['user_id'], $content]);
            logHistory($taskId, $_SESSION['user_id'], 'Добавлен комментарий');
            header('Location: /tasks/' . $taskId . '#comments');
            exit;
        }
    }

    if ($method === 'POST' && isset($_POST['snippet_after'])) {
        csrfVerify(); // ← CSRF-защита
        $desc   = trim($_POST['snippet_desc']   ?? '');
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
    // Удаление файла
    if ($method === 'POST' && isset($_POST['delete_file_id'])) {
        csrfVerify();
        $fileId = (int)$_POST['delete_file_id'];
        $stmt = db()->prepare('SELECT filename FROM task_files WHERE id = ? AND task_id = ?');
        $stmt->execute([$fileId, $taskId]);
        $file = $stmt->fetch();
        if ($file) {
            $path = __DIR__ . '/../../public/uploads/tasks/' . $file['filename'];
            if (file_exists($path)) unlink($path);
            db()->prepare('DELETE FROM task_files WHERE id = ?')->execute([$fileId]);
        }
        header('Location: /tasks/' . $taskId . '?tab=files');
        exit;
    }
    require __DIR__ . '/../views/task_view.php';
    exit;
}

// ===========================
// РЕДАКТИРОВАНИЕ ЗАДАЧИ
// ===========================
if (is_numeric($action) && $sub === 'edit') {
    $taskId = (int)$action;

    $stmt = db()->prepare('SELECT * FROM tasks WHERE id = ?');
    $stmt->execute([$taskId]);
    $task = $stmt->fetch();

    if (!$task) {
        http_response_code(404);
        echo '404 — задача не найдена';
        exit;
    }

    $projects      = db()->query('SELECT * FROM projects WHERE is_archived = 0 ORDER BY name')->fetchAll();
    $assignees     = db()->query('SELECT * FROM assignees ORDER BY name')->fetchAll();
    $tags          = db()->query('SELECT * FROM tags ORDER BY name')->fetchAll();
    $departments   = db()->query('SELECT * FROM departments ORDER BY name')->fetchAll();
    $customers_all = db()->query('SELECT * FROM customers ORDER BY name')->fetchAll();

    $stmt2 = db()->prepare('SELECT tag_id FROM task_tags WHERE task_id = ?');
    $stmt2->execute([$taskId]);
    $selectedTags = array_column($stmt2->fetchAll(), 'tag_id');

    $error = '';

    if ($method === 'POST') {
        csrfVerify(); // ← CSRF-защита

        $title         = trim($_POST['title'] ?? '');
        $description   = sanitizeHtml(trim($_POST['description'] ?? '')); // ← очищаем HTML
        $project_id    = (int)($_POST['project_id'] ?? 0);
        $assignee_id   = (int)($_POST['assignee_id'] ?? 0) ?: null;
        $department_id = (int)($_POST['department_id'] ?? 0) ?: null;
        $customer_id   = (int)($_POST['customer_id'] ?? 0) ?: null;
        $is_presidency = isset($_POST['is_presidency']) ? 1 : 0;
        $priority      = in_array($_POST['priority'] ?? '', ['high','medium','low']) ? $_POST['priority'] : 'medium';
        $estimated     = is_numeric($_POST['estimated_hours'] ?? '') ? (float)$_POST['estimated_hours'] : null;
        $complexity    = in_array((int)($_POST['complexity'] ?? 0), [1,2,3,4,5]) ? (int)$_POST['complexity'] : null;
        $work_type     = in_array($_POST['work_type'] ?? '', ['new_project','improvement','bugfix']) ? $_POST['work_type'] : null;
        $date_start    = preg_match('/^\d{4}-\d{2}-\d{2}$/', $_POST['date_start'] ?? '') ? $_POST['date_start'] : null;
        $date_end      = preg_match('/^\d{4}-\d{2}-\d{2}$/', $_POST['date_end']   ?? '') ? $_POST['date_end']   : null;
        $newTags       = array_map('intval', $_POST['tags'] ?? []);
        $color = preg_match('/^#[0-9a-fA-F]{6}$/', $_POST['color'] ?? '') ? $_POST['color'] : null;

        if (!$title || !$project_id) {
            $error = 'Заполните название и проект';
        } else {
            $fields = [
                'title'           => ['Название',        $task['title'],           $title],
                'department_id'   => ['Отдел',           $task['department_id'],   $department_id],
                'customer_id'     => ['Заказчик',        $task['customer_id'],     $customer_id],
                'is_presidency'   => ['Президентство',   $task['is_presidency'],   $is_presidency],
                'priority'        => ['Приоритет',       $task['priority'],        $priority],
                'complexity'      => ['Сложность',       $task['complexity'],      $complexity],
                'work_type'       => ['Тип работ',       $task['work_type'],       $work_type],
                'estimated_hours' => ['Оценка (ч)',      $task['estimated_hours'], $estimated],
                'date_start'      => ['Дата начала',     $task['date_start'],      $date_start],
                'date_end'        => ['Дата завершения', $task['date_end'],        $date_end],
                'color' => ['Цвет', $task['color'], $color],    
            ];

            foreach ($fields as [$label, $old, $new]) {
                if ((string)$old !== (string)$new) {
                    logHistory($taskId, $_SESSION['user_id'], "Изменено: $label", (string)$old, (string)$new);
                }
            }

            db()->prepare('
                UPDATE tasks
                SET title = ?, description = ?, project_id = ?, assignee_id = ?,
                    department_id = ?, customer_id = ?, is_presidency = ?,
                    priority = ?, complexity = ?, work_type = ?, color = ?, estimated_hours = ?, date_start = ?, date_end = ?
                WHERE id = ?
            ')->execute([
                $title, $description, $project_id, $assignee_id,
                $department_id, $customer_id, $is_presidency,
                $priority, $complexity, $work_type, $color,
                $estimated, $date_start, $date_end,
                $taskId
            ]);

            db()->prepare('DELETE FROM task_tags WHERE task_id = ?')->execute([$taskId]);
            if ($newTags) {
                $stmtTag = db()->prepare('INSERT INTO task_tags (task_id, tag_id) VALUES (?, ?)');
                foreach ($newTags as $tagId) {
                    $stmtTag->execute([$taskId, $tagId]);
                }
            }

            header('Location: /tasks/' . $taskId);
            exit;
        }
    }

    require __DIR__ . '/../views/task_edit.php';
    exit;
}

// ===========================
// ПЕРЕМЕЩЕНИЕ (смена статуса)
// ===========================
if (is_numeric($action) && $sub === 'move' && $method === 'POST') {
    csrfVerify(); // ← CSRF-защита

    $taskId    = (int)$action;
    $newStatus = $_POST['status'] ?? '';

    $allowed = ['incoming', 'new', 'in_progress', 'testing', 'done'];
    if (!in_array($newStatus, $allowed)) {
        http_response_code(400);
        exit;
    }

    $stmt = db()->prepare('SELECT * FROM tasks WHERE id = ?');
    $stmt->execute([$taskId]);
    $task = $stmt->fetch();

    if ($task) {
        $statusLabels = [
            'incoming'    => 'Входящие',
            'new'         => 'Очередь',
            'in_progress' => 'В работе',
            'testing'     => 'Тестирование',
            'done'        => 'Завершено',
        ];

        db()->prepare('UPDATE tasks SET status = ? WHERE id = ?')
            ->execute([$newStatus, $taskId]);

        logHistory(
            $taskId,
            $_SESSION['user_id'],
            'Статус изменён',
            $statusLabels[$task['status']] ?? $task['status'],
            $statusLabels[$newStatus] ?? $newStatus
        );

        if ($newStatus === 'done') {
            db()->prepare('
                UPDATE tasks
                SET status = "pending_archive",
                    archive_requested_by = ?,
                    archive_reason = "done",
                    archive_reason_custom = NULL
                WHERE id = ?
            ')->execute([$_SESSION['user_id'], $taskId]);

            logHistory($taskId, $_SESSION['user_id'], 'Запрошено архивирование', '', 'Завершена');
        }
    }

    // Используем safeRedirect вместо прямого header с HTTP_REFERER
    safeRedirect($_SERVER['HTTP_REFERER'] ?? '/');
}

// ===========================
// АРХИВИРОВАНИЕ
// ===========================
if (is_numeric($action) && $sub === 'archive' && $method === 'POST') {
    csrfVerify(); // ← CSRF-защита

    $taskId = (int)$action;

    $stmt = db()->prepare('SELECT * FROM tasks WHERE id = ?');
    $stmt->execute([$taskId]);
    $task = $stmt->fetch();

    if (!$task) { http_response_code(404); exit; }

    if ($task['status'] === 'pending_archive') {
        if ($task['archive_requested_by'] == $_SESSION['user_id']) {
            header('Location: /tasks/' . $taskId . '?error=same_user');
            exit;
        }
        db()->prepare('UPDATE tasks SET is_archived = 1, status = "done", archived_at = NOW() WHERE id = ?')->execute([$taskId]);
        logHistory($taskId, $_SESSION['user_id'], 'Задача архивирована');
        header('Location: /');
        exit;
    }

    $allowedReasons = ['done', 'irrelevant', 'rejected', 'duplicate', 'other'];
    $reason         = in_array($_POST['archive_reason'] ?? '', $allowedReasons) ? $_POST['archive_reason'] : 'other';
    $reasonCustom   = mb_substr(trim($_POST['archive_reason_custom'] ?? ''), 0, 500);

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
    csrfVerify(); // ← CSRF-защита

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
