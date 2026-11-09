<?php

/** Export task rows without mixing planned dates with lifecycle timestamps. */
function exportTasksCsv(iterable $tasks, string $filename, bool $archive = false, bool $full = false): void {
    $statusLabels = [
        'incoming'        => 'Почта',
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

    header('Content-Type: text/csv; charset=UTF-8');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
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

    $headers = ['ID', 'Задача', 'Проект', 'Статус', 'Отдел', 'Заказчик',
        'Исполнитель', 'Тип работ', 'Приоритет', 'Сложность', 'Оценка, ч',
        'Плановое начало', 'Плановое завершение', 'Президентство', 'Дата создания', 'Обновлена'];
    if ($archive) {
        $headers = array_merge($headers, ['Фактическое завершение', 'Дата архивирования', 'Причина архивирования']);
    }
    if ($full) {
        $headers[] = 'В архиве';
    }
    $writeRow($headers);

    foreach ($tasks as $task) {
        $row = [
            $task['id'], $task['title'], $task['project_name'],
            $statusLabels[$task['status']] ?? $task['status'],
            $task['department_name'], $task['customer_name'] ?: ($task['customer'] ?? ''), $task['assignee_name'],
            $workTypeLabels[$task['work_type'] ?? ''] ?? '',
            $priorityLabels[$task['priority']] ?? $task['priority'],
            $task['complexity'], $task['estimated_hours'],
            $task['date_start'], $task['date_end'],
            $task['is_presidency'] ? 'Да' : 'Нет',
            $task['created_at'], $task['updated_at'],
        ];
        if ($archive) {
            $row[] = $task['completed_at'] ?? '';
            $row[] = $task['archived_at'] ?? '';
            $reasons = ['done' => 'Завершена', 'irrelevant' => 'Не актуальна',
                'rejected' => 'Не одобрена', 'duplicate' => 'Дубликат', 'other' => 'Другое'];
            $row[] = ($task['archive_reason'] ?? '') === 'other'
                ? ($task['archive_reason_custom'] ?? 'Другое')
                : ($reasons[$task['archive_reason'] ?? ''] ?? '');
        }
        if ($full) {
            $row[] = $task['is_archived'] ? 'Да' : 'Нет';
        }
        $writeRow($row);
    }
    fclose($output);
    exit;
}
