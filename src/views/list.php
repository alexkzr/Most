<?php $pageTitle = 'Список задач'; require __DIR__ . '/head.php'; ?>
<body>

<header class="header">
    <div style="display:flex;align-items:center;gap:24px">
        <a href="/" class="header-logo">Most</a>

        <div class="project-switcher">
            <?php foreach ($projects as $p): ?>
                <a href="/?view=list&project=<?= $p['id'] ?>"
                   class="project-tab <?= $p['id'] == $project_id ? 'active' : '' ?>">
                    <?= htmlspecialchars($p['name']) ?>
                </a>
            <?php endforeach; ?>
        </div>
    </div>

    <div class="header-stats">
        <div class="header-stat">Очередь <span><?= $stats['new'] ?? 0 ?></span></div>
        <div class="header-stat">В работе <span><?= $stats['in_progress'] ?? 0 ?></span></div>
        <div class="header-stat">Тестирование <span><?= $stats['testing'] ?? 0 ?></span></div>
        <div class="header-stat">Завершено <span><?= $stats['done'] ?? 0 ?></span></div>
    </div>

    <div class="header-nav">
        <!-- Переключатель вида -->
        <div class="view-switcher">
            <a href="/?project=<?= $project_id ?>" class="view-btn" title="Канбан">⊞</a>
            <a href="/?view=list&project=<?= $project_id ?>" class="view-btn active" title="Список">☰</a>
        </div>
        <a href="/tasks/create" class="btn btn-primary">+ Задача</a>
        <a href="/archive" class="btn btn-ghost">Архив</a>
        <a href="/settings" class="btn btn-ghost">Настройки</a>
        <span class="header-user"><?= htmlspecialchars($_SESSION['user_name']) ?></span>
        <a href="/logout" class="btn btn-ghost">Выйти</a>
    </div>
</header>

<div class="container" style="max-width:100%;padding:24px">

    <?php if ($tasks): ?>
    <table class="list-table">
        <thead>
            <tr>
                <th>Задача</th>
                <th>Статус</th>
                <th>Заказчик</th>
                <th>Исполнитель</th>
                <th>Тип</th>
                <th>Приоритет</th>
                <th>Сложность</th>
                <th>Начало</th>
                <th>Завершение</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($tasks as $task): ?>
                <tr class="list-row <?= $task['is_presidency'] ? 'row-presidency' : '' ?>"
                    onclick="window.location='/tasks/<?= $task['id'] ?>'">
                    <td class="list-title">
                        <?php if ($task['is_presidency']): ?>
                            <span class="presidency-badge">🔴</span>
                        <?php endif; ?>
                        <?= htmlspecialchars($task['title']) ?>
                        <?php if ($task['status'] === 'pending_archive'): ?>
                            <span style="font-size:11px;color:#fca5a5;margin-left:6px">⚠ архив</span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <span class="badge badge-status-<?= $task['status'] ?>">
                            <?= $statusLabels[$task['status']] ?? $task['status'] ?>
                        </span>
                    </td>
                    <td class="list-meta">
                        <?php if ($task['department_name']): ?>
                            <span class="list-dept"><?= htmlspecialchars($task['department_name']) ?></span>
                        <?php endif; ?>
                        <?= htmlspecialchars($task['customer_name'] ?? '—') ?>
                    </td>
                    <td class="list-meta"><?= htmlspecialchars($task['assignee_name'] ?? '—') ?></td>
                    <td>
                        <?php if ($task['work_type'] && isset($workTypeLabels[$task['work_type']])): ?>
                            <span class="badge badge-work-<?= $task['work_type'] === 'new_project' ? 'new' : ($task['work_type'] === 'improvement' ? 'imp' : 'bug') ?>">
                                <?= $workTypeLabels[$task['work_type']] ?>
                            </span>
                        <?php else: ?>
                            <span class="list-meta">—</span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <span class="badge badge-<?= $task['priority'] ?>">
                            <?= $priorityLabels[$task['priority']] ?>
                        </span>
                    </td>
                    <td>
                        <?php if ($task['complexity']): ?>
                            <span class="list-stars">
                                <?php for ($i = 1; $i <= 5; $i++): ?>
                                    <span class="<?= $i <= $task['complexity'] ? 'star-on' : 'star-off' ?>">★</span>
                                <?php endfor; ?>
                            </span>
                        <?php else: ?>
                            <span class="list-meta">—</span>
                        <?php endif; ?>
                    </td>
                    <td class="list-meta">
                        <?= $task['date_start'] ? date('d.m.Y', strtotime($task['date_start'])) : '—' ?>
                    </td>
                    <td class="list-meta <?= ($task['date_end'] && strtotime($task['date_end']) < time() && $task['status'] !== 'done') ? 'text-danger' : '' ?>">
                        <?= $task['date_end'] ? date('d.m.Y', strtotime($task['date_end'])) : '—' ?>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
    <?php else: ?>
        <div class="empty-state" style="margin-top:80px">Задач нет</div>
    <?php endif; ?>

</div>

</body>
</html>