<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Most — Архив</title>
    <link rel="stylesheet" href="/public/css/style.css">
</head>
<body>

<header class="header">
    <div style="display:flex;align-items:center;gap:24px">
        <a href="/" class="header-logo">Most</a>
        <div class="project-switcher">
            <a href="/archive" class="project-tab <?= !$project_id ? 'active' : '' ?>">Все</a>
            <?php foreach ($projects as $p): ?>
                <a href="/archive?project=<?= $p['id'] ?>"
                   class="project-tab <?= $p['id'] == $project_id ? 'active' : '' ?>">
                    <?= htmlspecialchars($p['name']) ?>
                </a>
            <?php endforeach; ?>
        </div>
    </div>
    <div class="header-nav">
        <a href="/" class="btn btn-ghost">← Доска</a>
        <span class="header-user"><?= htmlspecialchars($_SESSION['user_name']) ?></span>
        <a href="/logout" class="btn btn-ghost">Выйти</a>
    </div>
</header>

<div class="container">
    <div class="page-header">
        <h1 class="page-title">Архив <span style="color:var(--text-muted);font-size:16px">(<?= count($tasks) ?>)</span></h1>

        <!-- Поиск -->
        <form method="GET" action="/archive" style="display:flex;gap:8px">
            <?php if ($project_id): ?>
                <input type="hidden" name="project" value="<?= $project_id ?>">
            <?php endif; ?>
            <input type="text" name="search" value="<?= htmlspecialchars($search) ?>"
                   placeholder="Поиск по названию или заказчику"
                   style="width:280px">
            <button type="submit" class="btn btn-ghost">Найти</button>
            <?php if ($search): ?>
                <a href="/archive<?= $project_id ? '?project=' . $project_id : '' ?>" class="btn btn-ghost">✕</a>
            <?php endif; ?>
        </form>
    </div>

    <?php if ($tasks): ?>
        <div class="archive-list">
            <?php foreach ($tasks as $task): ?>
                <a href="/tasks/<?= $task['id'] ?>" class="archive-item">
                    <div class="archive-item-main">
                        <div class="archive-item-title"><?= htmlspecialchars($task['title']) ?></div>
                        <div class="archive-item-meta">
                            <span><?= htmlspecialchars($task['project_name']) ?></span>
                            <?php if ($task['assignee_name']): ?>
                                <span>· <?= htmlspecialchars($task['assignee_name']) ?></span>
                            <?php endif; ?>
                            <?php if ($task['customer']): ?>
                                <span>· <?= htmlspecialchars($task['customer']) ?></span>
                            <?php endif; ?>
                        </div>
                    </div>
                    <div class="archive-item-right">
                        <?php if ($task['archive_reason']): ?>
                            <span class="badge badge-tag">
                                <?= htmlspecialchars($archiveReasonLabels[$task['archive_reason']] ?? $task['archive_reason']) ?>
                            </span>
                        <?php endif; ?>
                        <span class="archive-item-date">
                            <?= date('d.m.Y', strtotime($task['updated_at'])) ?>
                        </span>
                    </div>
                </a>
            <?php endforeach; ?>
        </div>
    <?php else: ?>
        <div class="empty-state" style="margin-top:80px">
            <?= $search ? 'Ничего не найдено' : 'Архив пуст' ?>
        </div>
    <?php endif; ?>
</div>

</body>
</html>