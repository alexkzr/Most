<?php

/**
 * Очистка файлов архивных задач старше 30 дней
 * Запускается по cron раз в сутки
 */

require_once __DIR__ . '/../config.php';

// Находим файлы задач которые в архиве больше 30 дней
$stmt = db()->prepare('
    SELECT f.id, f.filename
    FROM task_files f
    JOIN tasks t ON t.id = f.task_id
    WHERE t.is_archived = 1
      AND t.archived_at IS NOT NULL
      AND t.archived_at < DATE_SUB(NOW(), INTERVAL 30 DAY)
');
$stmt->execute();
$files = $stmt->fetchAll();

$deleted = 0;

foreach ($files as $file) {
    $path = '/var/www/u3393472/data/www/most.askraft.ru/public/uploads/tasks/' . $file['filename'];
    if (file_exists($path)) {
        unlink($path);
    }
    db()->prepare('DELETE FROM task_files WHERE id = ?')->execute([$file['id']]);
    $deleted++;
}

error_log('[CLEANUP] Удалено файлов: ' . $deleted);