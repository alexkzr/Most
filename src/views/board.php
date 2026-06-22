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

<header class="header">
    <div style="display:flex;align-items:center;gap:24px">
        <div class="header-logo">Most</div>
        <div class="project-switcher">
            <div class="project-dropdown" id="project-dropdown">
                <button class="project-dropdown-btn" onclick="this.closest('.project-dropdown').classList.toggle('open')">
                    <?php
                    $currentProject = array_filter($projects, fn($p) => $p['id'] == $project_id);
                    $currentProject = reset($currentProject);
                    ?>
                    <span><?= $currentProject ? htmlspecialchars($currentProject['name'], ENT_QUOTES, 'UTF-8') : 'Выберите проект' ?></span>
                    <span class="project-dropdown-arrow">▾</span>
                </button>
                <div class="project-dropdown-menu">
                    <?php foreach ($projects as $p): ?>
                        <a href="/?project=<?= (int)$p['id'] ?>"
                        class="project-dropdown-item <?= $p['id'] == $project_id ? 'active' : '' ?>">
                            <?= htmlspecialchars($p['name'], ENT_QUOTES, 'UTF-8') ?>
                        </a>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>
    <div class="view-switcher">
        <a href="/?project=<?= (int)$project_id ?>" class="view-btn active" title="Канбан">⊞</a>
        <a href="/?view=list&project=<?= (int)$project_id ?>" class="view-btn" title="Список">☰</a>
    </div>
    <div class="header-nav">
        <a href="/tasks/create" class="btn btn-primary">Создать</a>
        <a href="/archive" class="btn btn-ghost">Архив</a>
        <a href="/settings" class="btn btn-ghost">Настройки</a>
        <span class="header-user"><?= htmlspecialchars($_SESSION['user_name'], ENT_QUOTES, 'UTF-8') ?></span>
        <a href="/logout" class="btn btn-ghost">Выйти</a>
    </div>
</header>

<!-- CSRF-токен для JS-запросов -->
 <script nonce="<?= htmlspecialchars($_SERVER['CSP_NONCE'], ENT_QUOTES, 'UTF-8') ?>">const CSRF_TOKEN = '<?= csrfToken() ?>';</script>

<div class="board">
    <?php foreach ($columns as $status => $column): ?>
        <div class="column" data-status="<?= htmlspecialchars($status, ENT_QUOTES, 'UTF-8') ?>">
            <div class="column-header">
                <span class="column-title"><?= htmlspecialchars($column['title'], ENT_QUOTES, 'UTF-8') ?></span>
                <span class="column-count"><?= count($column['tasks']) ?></span>
            </div>
            <div class="column-body" id="col-<?= htmlspecialchars($status, ENT_QUOTES, 'UTF-8') ?>">
                <?php foreach ($column['tasks'] as $task): ?>
                    <div class="card <?= $task['status'] === 'pending_archive' ? 'pending-archive' : '' ?> <?= $task['is_presidency'] ? 'card-presidency' : '' ?>"
                        data-task-id="<?= (int)$task['id'] ?>"
                        style="<?= $task['color'] ? '--card-color:' . htmlspecialchars($task['color'], ENT_QUOTES, 'UTF-8') . ';background-color:color-mix(in srgb,' . htmlspecialchars($task['color'], ENT_QUOTES, 'UTF-8') . ' 15%,var(--bg-card))' : '' ?>"
                        onclick="window.location='/tasks/<?= (int)$task['id'] ?>'">

                        <div class="card-title"><?= htmlspecialchars($task['title'], ENT_QUOTES, 'UTF-8') ?></div>
                        <?php if ($task['is_presidency']): ?>
                            <div class="presidency-badge">🔴 Area Presidency</div>
                        <?php endif; ?>
                        <div class="card-meta">
                            <span class="badge badge-<?= htmlspecialchars($task['priority'], ENT_QUOTES, 'UTF-8') ?>">
                                <?= htmlspecialchars($priorityLabels[$task['priority']] ?? $task['priority'], ENT_QUOTES, 'UTF-8') ?>
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
                            <?php foreach ($task['tags'] as $tag): ?>
                                <span class="badge badge-tag"><?= htmlspecialchars($tag['name'], ENT_QUOTES, 'UTF-8') ?></span>
                            <?php endforeach; ?>

                            <?php if ($task['date_end']): ?>
                                <?php $overdue = strtotime($task['date_end']) < time() && $task['status'] !== 'done'; ?>
                                <span class="card-deadline <?= $overdue ? 'overdue' : '' ?>">
                                    <?= date('d.m', strtotime($task['date_end'])) ?>
                                </span>
                            <?php endif; ?>

                            <?php if ($task['assignee_name']): ?>
                                <span class="card-assignee"><?= htmlspecialchars($task['assignee_name'], ENT_QUOTES, 'UTF-8') ?></span>
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
<script nonce="<?= htmlspecialchars($_SERVER['CSP_NONCE'], ENT_QUOTES, 'UTF-8') ?>">
document.addEventListener('click', function(e) {
    if (!e.target.closest('.project-dropdown')) {
        document.querySelectorAll('.project-dropdown.open').forEach(d => d.classList.remove('open'));
    }
});
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

            // CSRF-токен передаём в теле запроса
            fetch('/tasks/' + taskId + '/move', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: 'status=' + encodeURIComponent(newStatus) + '&csrf_token=' + encodeURIComponent(CSRF_TOKEN)
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
