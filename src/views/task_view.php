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
// Валидация tab — только допустимые значения
$allowedTabs = ['comments', 'code', 'history'];
$activeTab   = in_array($_GET['tab'] ?? '', $allowedTabs) ? $_GET['tab'] : 'comments';
?>
<!DOCTYPE html>
<html lang="ru">
<?php $pageTitle = 'Вид таски'; require __DIR__ . '/head.php'; ?>
<body>

<header class="header">
    <div style="display:flex;align-items:center;gap:24px">
        <a href="/" class="header-logo">Most</a>
    </div>
    <div class="header-nav">
        <a href="/archive" class="btn btn-ghost">Архив</a>
        <a href="/settings" class="btn btn-ghost">Настройки</a>
        <span class="header-user"><?= htmlspecialchars($_SESSION['user_name'], ENT_QUOTES, 'UTF-8') ?></span>
        <a href="/logout" class="btn btn-ghost">Выйти</a>
    </div>
</header>

<div class="container">

    <div class="page-header">
        <a href="/" class="btn btn-ghost">← Доска</a>
        <?php if ($task['status'] !== 'pending_archive'): ?>
            <a href="/tasks/<?= (int)$task['id'] ?>/edit" class="btn btn-ghost">Редактировать</a>
        <?php endif; ?>
    </div>

    <?php if (isset($_GET['error']) && $_GET['error'] === 'same_user'): ?>
        <div class="alert alert-error">Архивирование должен подтвердить другой пользователь.</div>
    <?php endif; ?>

    <?php if ($task['status'] === 'pending_archive'): ?>
        <div class="alert alert-warning">
            <strong>⚠ Ожидает архивирования</strong> —
            запросил <?= htmlspecialchars($task['archive_requested_by_name'], ENT_QUOTES, 'UTF-8') ?>.
            Причина: <?= htmlspecialchars($archiveReasonLabels[$task['archive_reason']] ?? $task['archive_reason'], ENT_QUOTES, 'UTF-8') ?>
            <?php if ($task['archive_reason'] === 'other' && $task['archive_reason_custom']): ?>
                — <?= htmlspecialchars($task['archive_reason_custom'], ENT_QUOTES, 'UTF-8') ?>
            <?php endif; ?>

            <?php if ($task['archive_requested_by'] != $_SESSION['user_id']): ?>
                <div style="margin-top:10px;display:flex;gap:8px">
                    <form method="POST" action="/tasks/<?= (int)$task['id'] ?>/archive">
                        <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">
                        <button class="btn btn-danger">Подтвердить архивирование</button>
                    </form>
                    <form method="POST" action="/tasks/<?= (int)$task['id'] ?>/unarchive">
                        <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">
                        <button class="btn btn-ghost">Отменить</button>
                    </form>
                </div>
            <?php else: ?>
                <div style="margin-top:10px">
                    <form method="POST" action="/tasks/<?= (int)$task['id'] ?>/unarchive">
                        <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">
                        <button class="btn btn-ghost">Отменить запрос</button>
                    </form>
                </div>
            <?php endif; ?>
        </div>
    <?php endif; ?>

    <div class="task-layout">

        <div class="task-main">
            <h1 class="task-title"><?= htmlspecialchars($task['title'], ENT_QUOTES, 'UTF-8') ?></h1>

        <?php if ($task['description']): ?>
            <div class="desc-spoiler">
                <button type="button" class="desc-spoiler-toggle" onclick="this.closest('.desc-spoiler').classList.toggle('open')">
                    <span class="desc-spoiler-icon">▶</span>
                    <span>Описание задачи</span>
                </button>
                <div class="desc-spoiler-body">
                    <div class="desc-preview">
                        <?= $task['description'] ?>
                    </div>
                </div>
            </div>
        <?php endif; ?>

            <?php if ($taskTags): ?>
                <div class="task-tags">
                    <?php foreach ($taskTags as $tag): ?>
                        <span class="badge badge-tag"><?= htmlspecialchars($tag['name'], ENT_QUOTES, 'UTF-8') ?></span>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

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

            <?php if ($activeTab === 'comments'): ?>
                <div id="comments" class="tab-content">
                    <?php if ($comments): ?>
                        <div class="comments-list">
                            <?php foreach ($comments as $c): ?>
                                <div class="comment">
                                    <div class="comment-meta">
                                        <strong><?= htmlspecialchars($c['user_name'], ENT_QUOTES, 'UTF-8') ?></strong>
                                        <span><?= date('d.m.Y H:i', strtotime($c['created_at'])) ?></span>
                                    </div>
                                    <div class="comment-body"><?= nl2br(htmlspecialchars($c['content'], ENT_QUOTES, 'UTF-8')) ?></div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <div class="empty-state">Комментариев пока нет</div>
                    <?php endif; ?>

                    <form method="POST" action="/tasks/<?= (int)$task['id'] ?>" class="comment-form">
                        <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">
                        <textarea name="comment" placeholder="Напишите комментарий..." rows="3"></textarea>
                        <button type="submit" class="btn btn-primary">Отправить</button>
                    </form>
                </div>
            <?php endif; ?>

            <?php if ($activeTab === 'code'): ?>
                <div id="code" class="tab-content">
                    <?php if ($snippets): ?>
                        <?php foreach ($snippets as $s): ?>
                            <div class="snippet">
                                <div class="snippet-header">
                                    <span class="snippet-toggle">▶</span>
                                    <span><?= htmlspecialchars($s['description'] ?: 'Сниппет от ' . $s['user_name'], ENT_QUOTES, 'UTF-8') ?></span>
                                    <span class="snippet-meta"><?= date('d.m.Y H:i', strtotime($s['created_at'])) ?></span>
                                    <button type="button" class="snippet-expand-btn" title="Развернуть">⤢</button>
                                </div>
                                <div class="snippet-body">
                                    <div class="diff">
                                        <?php if ($s['code_before']): ?>
                                            <div class="diff-side">
                                                <div class="diff-label">Было</div>
                                                <pre><code class="language-1c"><?= htmlspecialchars($s['code_before'], ENT_QUOTES, 'UTF-8') ?></code></pre>
                                            </div>
                                        <?php endif; ?>
                                        <div class="diff-side">
                                            <div class="diff-label">Стало</div>
                                            <pre><code class="language-1c"><?= htmlspecialchars($s['code_after'], ENT_QUOTES, 'UTF-8') ?></code></pre>
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
                        <form method="POST" action="/tasks/<?= (int)$task['id'] ?>">
                            <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">
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

            <?php if ($activeTab === 'history'): ?>
                <div id="history" class="tab-content">
                    <?php if ($history): ?>
                        <div class="history-list">
                            <?php foreach (array_reverse($history) as $h): ?>
                                <div class="history-item">
                                    <div class="history-meta">
                                        <strong><?= htmlspecialchars($h['user_name'], ENT_QUOTES, 'UTF-8') ?></strong>
                                        <span><?= date('d.m.Y H:i', strtotime($h['created_at'])) ?></span>
                                    </div>
                                    <div class="history-action">
                                        <?= htmlspecialchars($h['action'], ENT_QUOTES, 'UTF-8') ?>
                                        <?php if ($h['old_value'] && $h['new_value']): ?>
                                            <span class="history-change">
                                                «<?= htmlspecialchars($h['old_value'], ENT_QUOTES, 'UTF-8') ?>»
                                                →
                                                «<?= htmlspecialchars($h['new_value'], ENT_QUOTES, 'UTF-8') ?>»
                                            </span>
                                        <?php elseif ($h['new_value']): ?>
                                            <span class="history-change">«<?= htmlspecialchars($h['new_value'], ENT_QUOTES, 'UTF-8') ?>»</span>
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

        <div class="task-sidebar">

            <div class="sidebar-block">
                <div class="sidebar-label">Статус</div>
                <form method="POST" action="/tasks/<?= (int)$task['id'] ?>/move">
                    <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">
                    <select name="status" onchange="this.form.submit()"
                            <?= $task['status'] === 'pending_archive' ? 'disabled' : '' ?>>
                        <?php foreach (['new' => 'Очередь', 'in_progress' => 'В работе', 'testing' => 'Тестирование', 'done' => 'Завершено'] as $s => $label): ?>
                            <option value="<?= $s ?>" <?= $task['status'] === $s || ($task['status'] === 'pending_archive' && $s === 'done') ? 'selected' : '' ?>>
                                <?= htmlspecialchars($label, ENT_QUOTES, 'UTF-8') ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </form>
            </div>

            <div class="sidebar-block">
                <div class="sidebar-label">Проект</div>
                <div class="sidebar-value"><?= htmlspecialchars($task['project_name'], ENT_QUOTES, 'UTF-8') ?></div>
            </div>

            <div class="sidebar-block">
                <div class="sidebar-label">Исполнитель</div>
                <div class="sidebar-value"><?= htmlspecialchars($task['assignee_name'] ?? 'Не назначен', ENT_QUOTES, 'UTF-8') ?></div>
            </div>

            <?php if ($task['customer_id']): ?>
            <div class="sidebar-block">
                <div class="sidebar-label">Заказчик</div>
                <div class="sidebar-value">
                    <?php
                    $stmtC = db()->prepare('
                        SELECT c.name AS customer_name, d.name AS dept_name
                        FROM customers c
                        JOIN departments d ON d.id = c.department_id
                        WHERE c.id = ?
                    ');
                    $stmtC->execute([$task['customer_id']]);
                    $customerInfo = $stmtC->fetch();
                    ?>
                    <?php if ($customerInfo): ?>
                        <span style="color:var(--text-muted);font-size:12px"><?= htmlspecialchars($customerInfo['dept_name'], ENT_QUOTES, 'UTF-8') ?></span><br>
                        <?= htmlspecialchars($customerInfo['customer_name'], ENT_QUOTES, 'UTF-8') ?>
                    <?php endif; ?>
                </div>
            </div>
            <?php endif; ?>

            <div class="sidebar-block">
                <div class="sidebar-label">Приоритет</div>
                <span class="badge badge-<?= htmlspecialchars($task['priority'], ENT_QUOTES, 'UTF-8') ?>">
                    <?= htmlspecialchars($priorityLabels[$task['priority']] ?? $task['priority'], ENT_QUOTES, 'UTF-8') ?>
                </span>
            </div>

            <?php if ($task['complexity']): ?>
                <span class="card-complexity">
                    <?php for ($i = 1; $i <= 5; $i++): ?>
                        <span class="<?= $i <= $task['complexity'] ? 'star-on' : 'star-off' ?>">★</span>
                    <?php endfor; ?>
                </span>
            <?php endif; ?>

            <?php if ($task['work_type']): ?>
            <div class="sidebar-block">
                <div class="sidebar-label">Тип работ</div>
                <?php
                $workTypeLabels = [
                    'new_project' => ['label' => 'Новый проект',       'class' => 'badge-work-new'],
                    'improvement' => ['label' => 'Доработка',           'class' => 'badge-work-imp'],
                    'bugfix'      => ['label' => 'Исправление ошибки',  'class' => 'badge-work-bug'],
                ];
                $wt = $workTypeLabels[$task['work_type']] ?? null;
                ?>
                <?php if ($wt): ?>
                    <span class="badge <?= $wt['class'] ?>"><?= $wt['label'] ?></span>
                <?php endif; ?>
            </div>
            <?php endif; ?>

            <?php if ($task['date_start'] || $task['date_end']): ?>
            <div class="sidebar-block">
                <div class="sidebar-label">Даты</div>
                <?php if ($task['date_start']): ?>
                    <div class="sidebar-value" style="font-size:12px;color:var(--text-muted)">Начало</div>
                    <div class="sidebar-value"><?= date('d.m.Y', strtotime($task['date_start'])) ?></div>
                <?php endif; ?>
                <?php if ($task['date_end']): ?>
                    <div class="sidebar-value" style="font-size:12px;color:var(--text-muted);margin-top:6px">Завершение</div>
                    <div class="sidebar-value"><?= date('d.m.Y', strtotime($task['date_end'])) ?></div>
                <?php endif; ?>
            </div>
            <?php endif; ?>

            <?php if ($task['estimated_hours']): ?>
            <div class="sidebar-block">
                <div class="sidebar-label">Оценка</div>
                <div class="sidebar-value"><?= (float)$task['estimated_hours'] ?> ч</div>
            </div>
            <?php endif; ?>

            <div class="sidebar-block">
                <div class="sidebar-label">Создана</div>
                <div class="sidebar-value"><?= date('d.m.Y H:i', strtotime($task['created_at'])) ?></div>
            </div>

            <?php if ($task['status'] !== 'pending_archive'): ?>
            <div class="sidebar-block" style="margin-top:24px">
                <button class="btn btn-ghost btn-block"
                        onclick="document.getElementById('archive-form').style.display='block';this.style.display='none'">
                    В архив
                </button>
                <div id="archive-form" style="display:none;margin-top:12px">
                    <form method="POST" action="/tasks/<?= (int)$task['id'] ?>/archive">
                        <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">
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
                            <input type="text" name="archive_reason_custom" placeholder="Укажите причину" maxlength="500">
                        </div>
                        <button type="submit" class="btn btn-danger btn-block">Отправить на архивирование</button>
                    </form>
                </div>
            </div>
            <?php endif; ?>

        </div>
    </div>
</div>

<script nonce="<?= htmlspecialchars($_SERVER['CSP_NONCE'], ENT_QUOTES, 'UTF-8') ?>">
// Сниппеты — раскрытие
document.querySelectorAll('.snippet-header').forEach(h => {
    h.addEventListener('click', (e) => {
        if (e.target.closest('.snippet-expand-btn')) return;
        const snippet = h.parentElement;
        const toggle  = h.querySelector('.snippet-toggle');
        snippet.classList.toggle('open');
        toggle.textContent = snippet.classList.contains('open') ? '▼' : '▶';
    });
});

// Diff + подсветка
document.querySelectorAll('.snippet').forEach(snippet => {
    const sides    = snippet.querySelectorAll('.diff-side');
    const beforeEl = sides.length >= 2 ? sides[0].querySelector('code') : null;
    const afterEl  = sides.length >= 2 ? sides[1].querySelector('code') : sides[0]?.querySelector('code');

    if (beforeEl && afterEl && beforeEl !== afterEl) {
        const beforeText = beforeEl.textContent;
        const afterText  = afterEl.textContent;

        const diff = Diff.diffLines(beforeText, afterText);

        let beforeHtml = '';
        let afterHtml  = '';

        diff.forEach(part => {
            // Подсвечиваем каждую строку
            const highlighted = hljs.highlight(part.value, { language: '1c' }).value;

            if (part.removed) {
                beforeHtml += `<mark class="diff-removed">${highlighted}</mark>`;
            } else if (part.added) {
                afterHtml += `<mark class="diff-added">${highlighted}</mark>`;
            } else {
                beforeHtml += highlighted;
                afterHtml  += highlighted;
            }
        });

        beforeEl.innerHTML = beforeHtml;
        afterEl.innerHTML  = afterHtml;
    } else {
        // Только "стало" — просто подсветка
        snippet.querySelectorAll('pre code').forEach(block => {
            hljs.highlightElement(block);
        });
    }
});
// Кнопка развернуть
document.querySelectorAll('.snippet-expand-btn').forEach(btn => {
    btn.addEventListener('click', function(e) {
        e.stopPropagation(); // не триггерим раскрытие сниппета
        const snippet = this.closest('.snippet');
        const isExpanded = snippet.classList.toggle('expanded');
        this.textContent = isExpanded ? '⤡' : '⤢';
        // Убедимся что сниппет открыт
        if (isExpanded) snippet.classList.add('open');
    });
});

// Закрытие по Escape
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        const expanded = document.querySelector('.snippet.expanded');
        if (expanded) {
            expanded.classList.remove('expanded');
            expanded.querySelector('.snippet-expand-btn').textContent = '⤢';
        } else {
            window.location.href = '/';
        }
    }
});
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') window.location.href = '/';
});
</script>
</body>
</html>
