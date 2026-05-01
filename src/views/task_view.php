<?php
$statusLabels = [
    'new'             => 'Новая',
    'in_progress'     => 'В работе',
    'testing'         => 'Тестирование',
    'done'            => 'Готово',
    'pending_archive' => 'Ожидает архивирования',
];
$priorityLabels = [
    'high'   => 'Высокий',
    'medium' => 'Средний',
    'low'    => 'Низкий',
];
$archiveReasonLabels = [
    'done'       => 'Завершена',
    'irrelevant' => 'Не актуальна',
    'rejected'   => 'Не одобрена',
    'duplicate'  => 'Дубликат',
    'other'      => 'Другое',
];
$activeTab = $_GET['tab'] ?? 'comments';
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Most — <?= htmlspecialchars($task['title']) ?></title>
    <link rel="stylesheet" href="/public/css/style.css">
</head>
<body>

<header class="header">
    <div style="display:flex;align-items:center;gap:24px">
        <a href="/" class="header-logo">Most</a>
    </div>
    <div class="header-nav">
        <a href="/archive" class="btn btn-ghost">Архив</a>
        <a href="/settings" class="btn btn-ghost">Настройки</a>
        <span class="header-user"><?= htmlspecialchars($_SESSION['user_name']) ?></span>
        <a href="/logout" class="btn btn-ghost">Выйти</a>
    </div>
</header>

<div class="container">

    <div class="page-header">
        <a href="/" class="btn btn-ghost">← Доска</a>
    </div>

    <?php if (isset($_GET['error']) && $_GET['error'] === 'same_user'): ?>
        <div class="alert alert-error">Архивирование должен подтвердить другой пользователь.</div>
    <?php endif; ?>

    <!-- Баннер ожидания архивирования -->
    <?php if ($task['status'] === 'pending_archive'): ?>
        <div class="alert alert-warning">
            <strong>⚠ Ожидает архивирования</strong> —
            запросил <?= htmlspecialchars($task['archive_requested_by_name']) ?>.
            Причина: <?= htmlspecialchars($archiveReasonLabels[$task['archive_reason']] ?? $task['archive_reason']) ?>
            <?php if ($task['archive_reason'] === 'other' && $task['archive_reason_custom']): ?>
                — <?= htmlspecialchars($task['archive_reason_custom']) ?>
            <?php endif; ?>

            <?php if ($task['archive_requested_by'] != $_SESSION['user_id']): ?>
                <div style="margin-top:10px;display:flex;gap:8px">
                    <form method="POST" action="/tasks/<?= $task['id'] ?>/archive">
                        <button class="btn btn-danger">Подтвердить архивирование</button>
                    </form>
                    <form method="POST" action="/tasks/<?= $task['id'] ?>/unarchive">
                        <button class="btn btn-ghost">Отменить</button>
                    </form>
                </div>
            <?php else: ?>
                <div style="margin-top:10px">
                    <form method="POST" action="/tasks/<?= $task['id'] ?>/unarchive">
                        <button class="btn btn-ghost">Отменить запрос</button>
                    </form>
                </div>
            <?php endif; ?>
        </div>
    <?php endif; ?>

    <div class="task-layout">

        <!-- Левая колонка — основная инфа -->
        <div class="task-main">
            <h1 class="task-title"><?= htmlspecialchars($task['title']) ?></h1>

            <?php if ($task['description']): ?>
                <div class="task-description"><?= nl2br(htmlspecialchars($task['description'])) ?></div>
            <?php endif; ?>

            <!-- Теги -->
            <?php if ($taskTags): ?>
                <div class="task-tags">
                    <?php foreach ($taskTags as $tag): ?>
                        <span class="badge badge-tag"><?= htmlspecialchars($tag['name']) ?></span>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

            <!-- Табы -->
            <div class="tabs">
                <a href="?tab=comments" class="tab <?= $activeTab === 'comments' ? 'active' : '' ?>">
                    Комментарии <span class="tab-count"><?= count($comments) ?></span>
                </a>
                <a href="?tab=code" class="tab <?= $activeTab === 'code' ? 'active' : '' ?>">
                    Код <span class="tab-count"><?= count($snippets) ?></span>
                </a>
                <a href="?tab=history" class="tab <?= $activeTab === 'history' ? 'active' : '' ?>">
                    История <span class="tab-count"><?= count($history) ?></span>
                </a>
            </div>

            <!-- Комментарии -->
            <?php if ($activeTab === 'comments'): ?>
                <div id="comments" class="tab-content">
                    <?php if ($comments): ?>
                        <div class="comments-list">
                            <?php foreach ($comments as $c): ?>
                                <div class="comment">
                                    <div class="comment-meta">
                                        <strong><?= htmlspecialchars($c['user_name']) ?></strong>
                                        <span><?= date('d.m.Y H:i', strtotime($c['created_at'])) ?></span>
                                    </div>
                                    <div class="comment-body"><?= nl2br(htmlspecialchars($c['content'])) ?></div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <div class="empty-state">Комментариев пока нет</div>
                    <?php endif; ?>

                    <form method="POST" action="/tasks/<?= $task['id'] ?>" class="comment-form">
                        <textarea name="comment" placeholder="Напишите комментарий..." rows="3"></textarea>
                        <button type="submit" class="btn btn-primary">Отправить</button>
                    </form>
                </div>
            <?php endif; ?>

            <!-- Код -->
            <?php if ($activeTab === 'code'): ?>
                <div id="code" class="tab-content">
                    <?php if ($snippets): ?>
                        <?php foreach ($snippets as $s): ?>
                            <div class="snippet">
                                <div class="snippet-header" onclick="this.parentElement.classList.toggle('open')">
                                    <span class="snippet-toggle">▶</span>
                                    <span><?= htmlspecialchars($s['description'] ?: 'Сниппет от ' . $s['user_name']) ?></span>
                                    <span class="snippet-meta"><?= date('d.m.Y H:i', strtotime($s['created_at'])) ?></span>
                                </div>
                                <div class="snippet-body">
                                    <div class="diff">
                                        <?php if ($s['code_before']): ?>
                                            <div class="diff-side">
                                                <div class="diff-label">Было</div>
                                                <pre><?= htmlspecialchars($s['code_before']) ?></pre>
                                            </div>
                                        <?php endif; ?>
                                        <div class="diff-side">
                                            <div class="diff-label">Стало</div>
                                            <pre><?= htmlspecialchars($s['code_after']) ?></pre>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div class="empty-state">Сниппетов пока нет</div>
                    <?php endif; ?>

                    <div class="snippet-form">
                        <h3 style="margin-bottom:12px;font-size:14px">Добавить сниппет</h3>
                        <form method="POST" action="/tasks/<?= $task['id'] ?>">
                            <div class="form-group">
                                <input type="text" name="snippet_desc" placeholder="Описание (что изменили)">
                            </div>
                            <div class="diff-inputs">
                                <div class="form-group">
                                    <label>Было (старый код)</label>
                                    <textarea name="snippet_before" rows="6" placeholder="Старый код..."></textarea>
                                </div>
                                <div class="form-group">
                                    <label>Стало (новый код) <span class="required">*</span></label>
                                    <textarea name="snippet_after" rows="6" placeholder="Новый код..."></textarea>
                                </div>
                            </div>
                            <button type="submit" class="btn btn-primary">Добавить</button>
                        </form>
                    </div>
                </div>
            <?php endif; ?>

            <!-- История -->
            <?php if ($activeTab === 'history'): ?>
                <div id="history" class="tab-content">
                    <?php if ($history): ?>
                        <div class="history-list">
                            <?php foreach (array_reverse($history) as $h): ?>
                                <div class="history-item">
                                    <div class="history-meta">
                                        <strong><?= htmlspecialchars($h['user_name']) ?></strong>
                                        <span><?= date('d.m.Y H:i', strtotime($h['created_at'])) ?></span>
                                    </div>
                                    <div class="history-action">
                                        <?= htmlspecialchars($h['action']) ?>
                                        <?php if ($h['old_value'] && $h['new_value']): ?>
                                            <span class="history-change">
                                                «<?= htmlspecialchars($h['old_value']) ?>»
                                                →
                                                «<?= htmlspecialchars($h['new_value']) ?>»
                                            </span>
                                        <?php elseif ($h['new_value']): ?>
                                            <span class="history-change">«<?= htmlspecialchars($h['new_value']) ?>»</span>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <div class="empty-state">История пуста</div>
                    <?php endif; ?>
                </div>
            <?php endif; ?>

        </div>

        <!-- Правая колонка — мета -->
        <div class="task-sidebar">

            <!-- Статус -->
            <div class="sidebar-block">
                <div class="sidebar-label">Статус</div>
                <form method="POST" action="/tasks/<?= $task['id'] ?>/move">
                    <select name="status" onchange="this.form.submit()"
                            <?= $task['status'] === 'pending_archive' ? 'disabled' : '' ?>>
                        <?php foreach (['new','in_progress','testing','done'] as $s): ?>
                            <option value="<?= $s ?>" <?= $task['status'] === $s ? 'selected' : '' ?>>
                                <?= $statusLabels[$s] ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </form>
            </div>

            <!-- Проект -->
            <div class="sidebar-block">
                <div class="sidebar-label">Проект</div>
                <div class="sidebar-value"><?= htmlspecialchars($task['project_name']) ?></div>
            </div>

            <!-- Исполнитель -->
            <div class="sidebar-block">
                <div class="sidebar-label">Исполнитель</div>
                <div class="sidebar-value"><?= htmlspecialchars($task['assignee_name'] ?? 'Не назначен') ?></div>
            </div>

            <!-- Заказчик -->
            <?php if ($task['customer']): ?>
            <div class="sidebar-block">
                <div class="sidebar-label">Заказчик</div>
                <div class="sidebar-value"><?= htmlspecialchars($task['customer']) ?></div>
            </div>
            <?php endif; ?>

            <!-- Приоритет -->
            <div class="sidebar-block">
                <div class="sidebar-label">Приоритет</div>
                <span class="badge badge-<?= $task['priority'] ?>">
                    <?= $priorityLabels[$task['priority']] ?>
                </span>
            </div>

            <!-- Срок -->
            <?php if ($task['deadline']): ?>
            <div class="sidebar-block">
                <div class="sidebar-label">Срок</div>
                <?php $overdue = strtotime($task['deadline']) < time() && $task['status'] !== 'done'; ?>
                <div class="sidebar-value <?= $overdue ? 'text-danger' : '' ?>">
                    <?= date('d.m.Y', strtotime($task['deadline'])) ?>
                    <?= $overdue ? '⚠ просрочено' : '' ?>
                </div>
            </div>
            <?php endif; ?>

            <!-- Оценка -->
            <?php if ($task['estimated_hours']): ?>
            <div class="sidebar-block">
                <div class="sidebar-label">Оценка</div>
                <div class="sidebar-value"><?= $task['estimated_hours'] ?> ч</div>
            </div>
            <?php endif; ?>

            <!-- Создана -->
            <div class="sidebar-block">
                <div class="sidebar-label">Создана</div>
                <div class="sidebar-value"><?= date('d.m.Y H:i', strtotime($task['created_at'])) ?></div>
            </div>

            <!-- Архивировать -->
            <?php if ($task['status'] !== 'pending_archive'): ?>
            <div class="sidebar-block" style="margin-top:24px">
                <button class="btn btn-ghost btn-block"
                        onclick="document.getElementById('archive-form').style.display='block';this.style.display='none'">
                    В архив
                </button>
                <div id="archive-form" style="display:none;margin-top:12px">
                    <form method="POST" action="/tasks/<?= $task['id'] ?>/archive">
                        <div class="form-group">
                            <select name="archive_reason" id="archive_reason"
                                    onchange="document.getElementById('custom-reason').style.display=this.value==='other'?'block':'none'">
                                <option value="done">Завершена</option>
                                <option value="irrelevant">Не актуальна</option>
                                <option value="rejected">Не одобрена</option>
                                <option value="duplicate">Дубликат</option>
                                <option value="other">Другое</option>
                            </select>
                        </div>
                        <div id="custom-reason" class="form-group" style="display:none">
                            <input type="text" name="archive_reason_custom" placeholder="Укажите причину">
                        </div>
                        <button type="submit" class="btn btn-danger btn-block">Отправить на архивирование</button>
                    </form>
                </div>
            </div>
            <?php endif; ?>

        </div>
    </div>
</div>

<script>
// Спойлеры сниппетов
document.querySelectorAll('.snippet-header').forEach(h => {
    h.addEventListener('click', () => {
        const snippet = h.parentElement;
        const toggle  = h.querySelector('.snippet-toggle');
        snippet.classList.toggle('open');
        toggle.textContent = snippet.classList.contains('open') ? '▼' : '▶';
    });
});
</script>
<script>
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        window.location.href = '/';
    }
});
</script>
</body>
</html>