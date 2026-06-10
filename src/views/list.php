<?php $pageTitle = 'Список задач'; require __DIR__ . '/head.php'; ?>
<body>

<header class="header">
    <div style="display:flex;align-items:center;gap:24px">
        <a href="/" class="header-logo">Most</a>

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
                        <a href="/?view=list&project=<?= (int)$p['id'] ?>"
                           class="project-dropdown-item <?= $p['id'] == $project_id ? 'active' : '' ?>">
                            <?= htmlspecialchars($p['name'], ENT_QUOTES, 'UTF-8') ?>
                        </a>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>

    <div class="header-nav">
        <div class="view-switcher">
            <a href="/?project=<?= $project_id ?>" class="view-btn" title="Канбан">⊞</a>
            <a href="/?view=list&project=<?= $project_id ?>" class="view-btn active" title="Список">☰</a>
        </div>
        <a href="/tasks/create" class="btn btn-primary">+ Задача</a>
        <a href="/archive" class="btn btn-ghost">Архив</a>
        <a href="/settings" class="btn btn-ghost">Настройки</a>
        <span class="header-user"><?= htmlspecialchars($_SESSION['user_name'], ENT_QUOTES, 'UTF-8') ?></span>
        <a href="/logout" class="btn btn-ghost">Выйти</a>
    </div>
</header>

<div class="container" style="max-width:100%;padding:24px">

    <?php if ($tasks): ?>
    <table class="list-table" id="list-table">
        <thead>
            <tr>
                <th class="sortable" data-col="0">Задача <span class="sort-icon">↕</span></th>
                <th class="sortable" data-col="1">Статус <span class="sort-icon">↕</span></th>
                <th class="sortable" data-col="2">Заказчик <span class="sort-icon">↕</span></th>
                <th class="sortable" data-col="3">Исполнитель <span class="sort-icon">↕</span></th>
                <th class="sortable" data-col="4">Тип <span class="sort-icon">↕</span></th>
                <th class="sortable" data-col="5">Приоритет <span class="sort-icon">↕</span></th>
                <th class="sortable" data-col="6">Сложность <span class="sort-icon">↕</span></th>
                <th class="sortable" data-col="7">Начало <span class="sort-icon">↕</span></th>
                <th class="sortable" data-col="8">Завершение <span class="sort-icon">↕</span></th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($tasks as $task): ?>
                <tr class="list-row <?= $task['is_presidency'] ? 'row-presidency' : '' ?>"
                    onclick="window.location='/tasks/<?= (int)$task['id'] ?>'"
                    data-priority="<?= $task['priority'] === 'high' ? 1 : ($task['priority'] === 'medium' ? 2 : 3) ?>"
                    data-complexity="<?= (int)($task['complexity'] ?? 0) ?>">
                    <td class="list-title">
                        <?php if ($task['is_presidency']): ?>
                            <span class="presidency-badge">🔴</span>
                        <?php endif; ?>
                        <?= htmlspecialchars($task['title'], ENT_QUOTES, 'UTF-8') ?>
                        <?php if ($task['status'] === 'pending_archive'): ?>
                            <span style="font-size:11px;color:#fca5a5;margin-left:6px">⚠ архив</span>
                        <?php endif; ?>
                    </td>
                    <td data-val="<?= htmlspecialchars($statusLabels[$task['status']] ?? $task['status'], ENT_QUOTES, 'UTF-8') ?>">
                        <span class="badge badge-status-<?= $task['status'] ?>">
                            <?= $statusLabels[$task['status']] ?? $task['status'] ?>
                        </span>
                    </td>
                    <td class="list-meta" data-val="<?= htmlspecialchars($task['customer_name'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
                        <?php if ($task['department_name']): ?>
                            <span class="list-dept"><?= htmlspecialchars($task['department_name'], ENT_QUOTES, 'UTF-8') ?></span>
                        <?php endif; ?>
                        <?= htmlspecialchars($task['customer_name'] ?? '—', ENT_QUOTES, 'UTF-8') ?>
                    </td>
                    <td class="list-meta"><?= htmlspecialchars($task['assignee_name'] ?? '—', ENT_QUOTES, 'UTF-8') ?></td>
                    <td data-val="<?= htmlspecialchars($workTypeLabels[$task['work_type']] ?? '', ENT_QUOTES, 'UTF-8') ?>">
                        <?php if ($task['work_type'] && isset($workTypeLabels[$task['work_type']])): ?>
                            <span class="badge badge-work-<?= $task['work_type'] === 'new_project' ? 'new' : ($task['work_type'] === 'improvement' ? 'imp' : 'bug') ?>">
                                <?= $workTypeLabels[$task['work_type']] ?>
                            </span>
                        <?php else: ?>
                            <span class="list-meta">—</span>
                        <?php endif; ?>
                    </td>
                    <td data-val="<?= $task['priority'] === 'high' ? 1 : ($task['priority'] === 'medium' ? 2 : 3) ?>">
                        <span class="badge badge-<?= $task['priority'] ?>">
                            <?= $priorityLabels[$task['priority']] ?>
                        </span>
                    </td>
                    <td data-val="<?= (int)($task['complexity'] ?? 0) ?>">
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
                    <td class="list-meta" data-val="<?= $task['date_start'] ?? '' ?>">
                        <?= $task['date_start'] ? date('d.m.Y', strtotime($task['date_start'])) : '—' ?>
                    </td>
                    <td class="list-meta <?= ($task['date_end'] && strtotime($task['date_end']) < time() && $task['status'] !== 'done') ? 'text-danger' : '' ?>"
                        data-val="<?= $task['date_end'] ?? '' ?>">
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

<script nonce="<?= htmlspecialchars($_SERVER['CSP_NONCE'], ENT_QUOTES, 'UTF-8') ?>">
// Закрытие дропдауна проектов
document.addEventListener('click', function(e) {
    if (!e.target.closest('.project-dropdown')) {
        document.querySelectorAll('.project-dropdown.open').forEach(d => d.classList.remove('open'));
    }
});

// Сортировка таблицы
const table   = document.getElementById('list-table');
if (table) {
    let sortCol = -1;
    let sortAsc = true;

    document.querySelectorAll('.sortable').forEach(th => {
        th.addEventListener('click', function() {
            const col = parseInt(this.dataset.col);

            if (sortCol === col) {
                sortAsc = !sortAsc;
            } else {
                sortCol = col;
                sortAsc = true;
            }

            // Обновляем иконки
            document.querySelectorAll('.sortable').forEach(h => {
                h.querySelector('.sort-icon').textContent = '↕';
                h.classList.remove('sort-asc', 'sort-desc');
            });
            this.querySelector('.sort-icon').textContent = sortAsc ? '↑' : '↓';
            this.classList.add(sortAsc ? 'sort-asc' : 'sort-desc');

            // Сортируем строки
            const tbody = table.querySelector('tbody');
            const rows  = Array.from(tbody.querySelectorAll('tr'));

            rows.sort((a, b) => {
                const aCell = a.querySelectorAll('td')[col];
                const bCell = b.querySelectorAll('td')[col];

                // Берём data-val если есть, иначе текст
                const aVal = aCell.dataset.val !== undefined ? aCell.dataset.val : aCell.textContent.trim();
                const bVal = bCell.dataset.val !== undefined ? bCell.dataset.val : bCell.textContent.trim();

                // Числовое сравнение если оба числа
                const aNum = parseFloat(aVal);
                const bNum = parseFloat(bVal);
                if (!isNaN(aNum) && !isNaN(bNum)) {
                    return sortAsc ? aNum - bNum : bNum - aNum;
                }

                // Строковое
                return sortAsc
                    ? aVal.localeCompare(bVal, 'ru')
                    : bVal.localeCompare(aVal, 'ru');
            });

            rows.forEach(r => tbody.appendChild(r));
        });
    });
}
</script>

</body>
</html>