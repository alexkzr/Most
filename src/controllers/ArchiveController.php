<?php

require_once __DIR__ . '/../../config.php';

$projects = db()->query('SELECT * FROM projects WHERE is_archived = 0 ORDER BY name')->fetchAll();

$project_id = $_GET['project'] ?? null;
if (!$project_id && $projects) {
    $project_id = $projects[0]['id'];
}

$search = trim($_GET['search'] ?? '');

$sql = '
    SELECT t.*,
           a.name AS assignee_name,
           p.name AS project_name,
           u.name AS created_by_name
    FROM tasks t
    LEFT JOIN assignees a ON a.id = t.assignee_id
    LEFT JOIN projects  p ON p.id = t.project_id
    LEFT JOIN users     u ON u.id = t.created_by
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

require __DIR__ . '/../views/archive.php';