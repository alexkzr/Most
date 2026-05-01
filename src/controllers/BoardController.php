<?php

require_once __DIR__ . '/../../config.php';

$project_id = $_GET['project'] ?? null;

// Берём все проекты для переключателя
$projects = db()->query('SELECT * FROM projects WHERE is_archived = 0 ORDER BY name')->fetchAll();

// Если проект не выбран — берём первый
if (!$project_id && $projects) {
    $project_id = $projects[0]['id'];
}

// Статистика для шапки
$stats = [];
if ($project_id) {
    $stmt = db()->prepare('
        SELECT status, COUNT(*) as cnt
        FROM tasks
        WHERE project_id = ? AND is_archived = 0
        GROUP BY status
    ');
    $stmt->execute([$project_id]);
    foreach ($stmt->fetchAll() as $row) {
        $stats[$row['status']] = $row['cnt'];
    }
}

// Задачи по колонкам
$columns = [
    'new'             => ['title' => 'Новые',       'tasks' => []],
    'in_progress'     => ['title' => 'В работе',    'tasks' => []],
    'testing'         => ['title' => 'Тестирование','tasks' => []],
    'done'            => ['title' => 'Готово',       'tasks' => []],
    'pending_archive' => ['title' => 'В архив',     'tasks' => []],
];

if ($project_id) {
    $stmt = db()->prepare('
        SELECT t.*,
               a.name AS assignee_name,
               u.name AS created_by_name
        FROM tasks t
        LEFT JOIN assignees a ON a.id = t.assignee_id
        LEFT JOIN users u ON u.id = t.created_by
        WHERE t.project_id = ? AND t.is_archived = 0
        ORDER BY
            FIELD(t.priority, "high", "medium", "low"),
            t.created_at ASC
    ');
    $stmt->execute([$project_id]);
    $tasks = $stmt->fetchAll();

    // Теги для каждой задачи
    foreach ($tasks as &$task) {
        $stmt2 = db()->prepare('
            SELECT tg.name, tg.color
            FROM task_tags tt
            JOIN tags tg ON tg.id = tt.tag_id
            WHERE tt.task_id = ?
        ');
        $stmt2->execute([$task['id']]);
        $task['tags'] = $stmt2->fetchAll();

        if (isset($columns[$task['status']])) {
            $columns[$task['status']]['tasks'][] = $task;
        }
    }
    unset($task);
}

require __DIR__ . '/../views/board.php';