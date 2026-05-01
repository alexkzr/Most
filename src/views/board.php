<?php
$statusLabels = [
    'new'             => 'Новые',
    'in_progress'     => 'В работе',
    'testing'         => 'Тестирование',
    'done'            => 'Готово',
    'pending_archive' => 'В архив',
];
$priorityLabels = [
    'high'   => 'Высокий',
    'medium' => 'Средний',
    'low'    => 'Низкий',
];
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Most — Канбан</title>
    <link rel="stylesheet" href="/public/css/style.css">
</head>
<body>

<!-- Шапка -->
<header class="header">
    <div style="display:flex;align-items:center;gap:24px">
        <div class="header-logo">Most</div>

        <!-- Переключатель проектов -->
        <div class="project-switcher">
            <?php foreach ($projects as $p): ?>
                <a href="/?project=<?= $p['id'] ?>"
                   class="project-tab <?= $p['id'] == $project_id ? 'active' : '' ?>">
                    <?= htmlspecialchars($p['name']) ?>
                </a>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- Статистика -->
    <div class="header-stats">
        <div class="header-stat">Новые <span><?= $stats['new'] ?? 0 ?></span></div>
        <div class="header-stat">В работе <span><?= $stats['in_progress'] ?? 0 ?></span></div>
        <div class="header-stat">Тестирование <span><?= $stats['testing'] ?? 0 ?></span></div>
        <div class="header-stat">Готово <span><?= $stats['done'] ?? 0 ?></span></div>
    </div>

    <!-- Навигация -->
    <div class="header-nav">
        <a href="/tasks/create" class="btn btn-primary">+ Задача</a>
        <a href="/archive" class="btn btn-ghost">Архив</a>
        <a href="/settings" class="btn btn-ghost">Настройки</a>
        <span class="header-user"><?= htmlspecialchars($_SESSION['user_name']) ?></span>
        <a href="/logout" class="btn btn-ghost">Выйти</a>
    </div>
</header>

<!-- Канбан -->
<div class="board">
    <?php foreach ($columns as $status => $column): ?>
        <div class="column" data-status="<?= $status ?>">
            <div class="column-header">
                <span class="column-title"><?= $column['title'] ?></span>
                <span class="column-count"><?= count($column['tasks']) ?></span>
            </div>
            <div class="column-body" id="col-<?= $status ?>">
                <?php foreach ($column['tasks'] as $task): ?>
                    <div class="card <?= $task['status'] === 'pending_archive' ? 'pending-archive' : '' ?>"
                         onclick="window.location='/tasks/<?= $task['id'] ?>'">

                        <div class="card-title"><?= htmlspecialchars($task['title']) ?></div>

                        <div class="card-meta">
                            <!-- Приоритет -->
                            <span class="badge badge-<?= $task['priority'] ?>">
                                <?= $priorityLabels[$task['priority']] ?>
                            </span>

                            <!-- Теги -->
                            <?php foreach ($task['tags'] as $tag): ?>
                                <span class="badge badge-tag"><?= htmlspecialchars($tag['name']) ?></span>
                            <?php endforeach; ?>

                            <!-- Дедлайн -->
                            <?php if ($task['deadline']): ?>
                                <?php
                                $overdue = strtotime($task['deadline']) < time() && $task['status'] !== 'done';
                                ?>
                                <span class="card-deadline <?= $overdue ? 'overdue' : '' ?>">
                                    <?= date('d.m', strtotime($task['deadline'])) ?>
                                </span>
                            <?php endif; ?>

                            <!-- Исполнитель -->
                            <?php if ($task['assignee_name']): ?>
                                <span class="card-assignee"><?= htmlspecialchars($task['assignee_name']) ?></span>
                            <?php endif; ?>
                        </div>

                        <?php if ($task['status'] === 'pending_archive'): ?>
                            <div style="margin-top:8px;font-size:12px;color:#fca5a5">
                                ⚠ Ожидает архивирования
                            </div>
                        <?php endif; ?>

                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    <?php endforeach; ?>
</div>

</body>
</html>