<?php

require_once __DIR__ . '/../../config.php';
require_once __DIR__ . '/../lib/TaskCsv.php';

// No project, archive or search filters: this export always includes every task.
$tasks = db()->query('
    SELECT t.*,
           p.name AS project_name,
           a.name AS assignee_name,
           d.name AS department_name,
           c.name AS customer_name,
           CASE
               WHEN t.status IN ("done", "pending_archive") AND t.archive_reason = "done"
               THEN completion.completed_at
               ELSE NULL
           END AS completed_at
    FROM tasks t
    LEFT JOIN projects p ON p.id = t.project_id
    LEFT JOIN assignees a ON a.id = t.assignee_id
    LEFT JOIN departments d ON d.id = t.department_id
    LEFT JOIN customers c ON c.id = t.customer_id
    LEFT JOIN (
        SELECT task_id, MAX(created_at) AS completed_at
        FROM history
        WHERE (action = "Статус изменён" AND new_value = "Завершено")
           OR (action = "Запрошено архивирование" AND new_value IN ("Завершена", "done"))
        GROUP BY task_id
    ) completion ON completion.task_id = t.id
    ORDER BY p.name, t.is_archived, t.created_at, t.id
');

exportTasksCsv($tasks, 'most-full-export-' . date('Y-m-d') . '.csv', true, true);
