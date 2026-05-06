<?php
$statusLabels = [
    'new'         => 'Очередь',
    'in_progress' => 'В работе',
    'testing'     => 'Тестирование',
    'done'        => 'Завершено',
];
$priorityLabels = [
    'high'   => 'Высокий',
    'medium' => 'Средний',
    'low'    => 'Низкий',
];
?>
<!DOCTYPE html>
<html lang="ru">
<?php $pageTitle = 'Доска'; require __DIR__ . '/head.php'; ?>
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
        <div class="header-stat">Очередь <span><?= $stats['new'] ?? 0 ?></span></div>
        <div class="header-stat">В работе <span><?= $stats['in_progress'] ?? 0 ?></span></div>
        <div class="header-stat">Тестирование <span><?= $stats['testing'] ?? 0 ?></span></div>
        <div class="header-stat">Завершено <span><?= $stats['done'] ?? 0 ?></span></div>
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
                    <div class="card <?= $task['status'] === 'pending_archive' ? 'pending-archive' : '' ?> <?= $task['is_presidency'] ? 'card-presidency' : '' ?>"
                        data-task-id="<?= $task['id'] ?>"
                        onclick="window.location='/tasks/<?= $task['id'] ?>'">

                        <div class="card-title"><?= htmlspecialchars($task['title']) ?></div>
                        <?php if ($task['is_presidency']): ?>
                            <div class="presidency-badge">🔴 Area Presidency</div>
                        <?php endif; ?>
                        <div class="card-meta">
                            <!-- Приоритет -->
                            <span class="badge badge-<?= $task['priority'] ?>">
                                <?= $priorityLabels[$task['priority']] ?>
                            </span>
                            <?php
                                $workTypeLabels = [
                                    'new_project' => ['label' => 'Новый проект',      'class' => 'badge-work-new'],
                                    'improvement' => ['label' => 'Доработка',          'class' => 'badge-work-imp'],
                                    'bugfix'      => ['label' => 'Исправление ошибки', 'class' => 'badge-work-bug'],
                                ];
                                if ($task['work_type'] && isset($workTypeLabels[$task['work_type']])): ?>
                                    <span class="badge <?= $workTypeLabels[$task['work_type']]['class'] ?>">
                                        <?= $workTypeLabels[$task['work_type']]['label'] ?>
                                    </span>
                                <?php endif; ?>
                            <!-- Теги -->
                            <?php foreach ($task['tags'] as $tag): ?>
                                <span class="badge badge-tag"><?= htmlspecialchars($tag['name']) ?></span>
                            <?php endforeach; ?>

                            <!-- Дедлайн -->
                            <?php if ($task['date_end']): ?>
                                <?php
                                $overdue = $task['date_end'] && strtotime($task['date_end']) < time() && $task['status'] !== 'done';
                                ?>
                                <span class="card-deadline <?= $overdue ? 'overdue' : '' ?>">
                                    <?= date('d.m', strtotime($task['date_end'])) ?>
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
<script src="https://cdnjs.cloudflare.com/ajax/libs/Sortable/1.15.2/Sortable.min.js"></script>
<script>
document.querySelectorAll('.column-body').forEach(col => {
    Sortable.create(col, {
        group: 'tasks',
        animation: 150,
        ghostClass: 'card-ghost',
        dragClass: 'card-drag',
        onEnd: function(evt) {
            const taskId    = evt.item.dataset.taskId;
            const newStatus = evt.to.closest('.column').dataset.status;

            if (newStatus === 'pending_archive') {
                evt.from.insertBefore(evt.item, evt.from.children[evt.oldIndex]);
                return;
            }

            fetch('/tasks/' + taskId + '/move', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: 'status=' + encodeURIComponent(newStatus)
            }).then(r => {
                if (!r.ok) {
                    evt.from.insertBefore(evt.item, evt.from.children[evt.oldIndex]);
                }
                document.querySelectorAll('.column').forEach(c => {
                    const count = c.querySelector('.column-body').children.length;
                    c.querySelector('.column-count').textContent = count;
                });
            });
        }
    });
});
</script>
</body>
</html>