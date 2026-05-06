<?php

require_once __DIR__ . '/../../config.php';

$project_id = $_GET['project'] ?? null;

$projects = db()->query('SELECT * FROM projects WHERE is_archived = 0 ORDER BY name')->fetchAll();

if (!$project_id && $projects) {
    $project_id = $projects[0]['id'];
}

$statusLabels = [
    'new'             => 'Очередь',
    'in_progress'     => 'В работе',
    'testing'         => 'Тестирование',
    'done'            => 'Завершено',
    'pending_archive' => 'Ожидает архивирования',
];

$priorityLabels = [
    'high'   => 'Высокий',
    'medium' => 'Средний',
    'low'    => 'Низкий',
];

$workTypeLabels = [
    'new_project' => 'Новый проект',
    'improvement' => 'Доработка',
    'bugfix'      => 'Исправление ошибки',
];

$tasks = [];
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

    $stmt = db()->prepare('
        SELECT t.*,
               a.name  AS assignee_name,
               d.name  AS department_name,
               c.name  AS customer_name,
               p.name  AS project_name
        FROM tasks t
        LEFT JOIN assignees a   ON a.id = t.assignee_id
        LEFT JOIN departments d ON d.id = t.department_id
        LEFT JOIN customers c   ON c.id = t.customer_id
        LEFT JOIN projects p    ON p.id = t.project_id
        WHERE t.project_id = ? AND t.is_archived = 0
        ORDER BY
            t.is_presidency DESC,
            FIELD(t.priority, "high", "medium", "low"),
            t.created_at ASC
    ');
    $stmt->execute([$project_id]);
    $tasks = $stmt->fetchAll();
}

require __DIR__ . '/../views/list.php';