<?php

require_once __DIR__ . '/../../config.php';

$projects = db()->query('SELECT * FROM projects ORDER BY name')->fetchAll();

$project_id = (int)($_GET['project'] ?? 0) ?: null;

$search = trim($_GET['search'] ?? '');

$sql = '
    SELECT t.*,
           a.name AS assignee_name,
           p.name AS project_name,
           u.name AS created_by_name,
           d.name AS department_name,
           c.name AS customer_name
    FROM tasks t
    LEFT JOIN assignees a ON a.id = t.assignee_id
    LEFT JOIN projects  p ON p.id = t.project_id
    LEFT JOIN users     u ON u.id = t.created_by
    LEFT JOIN departments d ON d.id = t.department_id
    LEFT JOIN customers c ON c.id = t.customer_id
    WHERE t.is_archived = 1
';

$params = [];

if ($project_id) {
    $sql .= ' AND t.project_id = ?';
    $params[] = $project_id;
}

if ($search) {
    $sql .= ' AND (t.title LIKE ? OR t.customer LIKE ?)';
    $params[] = '%' . $search . '%';
    $params[] = '%' . $search . '%';
}

$sql .= ' ORDER BY t.updated_at DESC';

$stmt = db()->prepare($sql);
$stmt->execute($params);
$tasks = $stmt->fetchAll();

$archiveReasonLabels = [
    'done'       => 'Завершена',
    'irrelevant' => 'Не актуальна',
    'rejected'   => 'Не одобрена',
    'duplicate'  => 'Дубликат',
    'other'      => 'Другое',
];

if (($_GET['export'] ?? '') === 'csv') {
    $completion = db()->prepare('
        SELECT MAX(created_at) FROM history
        WHERE task_id = ? AND (
            (action = "Статус изменён" AND new_value = "Завершено") OR
            (action = "Запрошено архивирование" AND new_value IN ("Завершена", "done"))
        )
    ');
    foreach ($tasks as &$task) {
        $task['completed_at'] = null;
        if (($task['archive_reason'] ?? '') === 'done') {
            $completion->execute([$task['id']]);
            $task['completed_at'] = $completion->fetchColumn() ?: null;
        }
    }
    unset($task);
    require_once __DIR__ . '/../lib/TaskCsv.php';
    exportTasksCsv($tasks, 'most-archive-' . ($project_id ?: 'all') . '-' . date('Y-m-d') . '.csv', true);
}

require __DIR__ . '/../views/archive.php';