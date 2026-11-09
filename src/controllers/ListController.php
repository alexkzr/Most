<?php

require_once __DIR__ . '/../../config.php';

$project_id = $_GET['project'] ?? null;

$projects = db()->query('SELECT * FROM projects WHERE is_archived = 0 ORDER BY sort_order, name')->fetchAll();

if (!$project_id && $projects) {
    $project_id = $projects[0]['id'];
}

$statusLabels = [
    'incoming'        => 'Входящие',
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

// Export uses the same project and active task selection as the list.
if (($_GET['export'] ?? '') === 'csv') {
    header('Content-Type: text/csv; charset=UTF-8');
    header('Content-Disposition: attachment; filename="most-project-' . (int)$project_id . '-' . date('Y-m-d') . '.csv"');
    header('Cache-Control: no-store');

    $output = fopen('php://output', 'w');
    fwrite($output, "\xEF\xBB\xBF"); // UTF-8 BOM for Excel.

    // Neutralize spreadsheet formulas in user-provided text.
    $writeRow = static function (array $row) use ($output): void {
        $row = array_map(static function ($value): string {
            $value = (string)($value ?? '');
            if (preg_match('/^(?:[\t\r\n]|\s*[=+@-])/u', $value)) {
                $value = "'" . $value;
            }
            return $value;
        }, $row);
        fputcsv($output, $row, ';', '"', '');
    };

    $writeRow(['ID', 'Задача', 'Проект', 'Статус', 'Отдел', 'Заказчик',
        'Исполнитель', 'Тип работ', 'Приоритет', 'Сложность', 'Оценка, ч',
        'Начало', 'Завершение', 'Президентство', 'Создана', 'Обновлена']);

    foreach ($tasks as $task) {
        $writeRow([
            $task['id'], $task['title'], $task['project_name'],
            $statusLabels[$task['status']] ?? $task['status'],
            $task['department_name'], $task['customer_name'], $task['assignee_name'],
            $workTypeLabels[$task['work_type'] ?? ''] ?? '',
            $priorityLabels[$task['priority']] ?? $task['priority'],
            $task['complexity'], $task['estimated_hours'],
            $task['date_start'], $task['date_end'],
            $task['is_presidency'] ? 'Да' : 'Нет',
            $task['created_at'], $task['updated_at'],
        ]);
    }
    fclose($output);
    exit;
}

require __DIR__ . '/../views/list.php';
