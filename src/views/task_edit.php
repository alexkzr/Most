<?php $pageTitle = 'Редактирование задачи'; require __DIR__ . '/head.php'; ?>
<body>

<header class="header">
    <div style="display:flex;align-items:center;gap:24px">
        <a href="/" class="header-logo">Most</a>
    </div>
    <div class="header-nav">
        <span class="header-user"><?= htmlspecialchars($_SESSION['user_name']) ?></span>
        <a href="/logout" class="btn btn-ghost">Выйти</a>
    </div>
</header>

<div class="container">
    <div class="page-header">
        <h1 class="page-title">Редактирование задачи</h1>
        <a href="/tasks/<?= $task['id'] ?>" class="btn btn-ghost">← Назад</a>
    </div>

    <?php if ($error): ?>
        <div class="alert alert-error"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <form method="POST" action="/tasks/<?= $task['id'] ?>/edit">
        <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">
        <div class="form-card">

            <!-- Название -->
            <div class="form-group">
                <label for="title">Название <span class="required">*</span></label>
                <input type="text" id="title" name="title"
                       value="<?= htmlspecialchars($_POST['title'] ?? $task['title']) ?>" autofocus>
            </div>

            <!-- Описание -->
            <div class="form-group">
                <div class="desc-spoiler" id="desc-spoiler-edit">
                    <button type="button" class="desc-spoiler-toggle" onclick="this.closest('.desc-spoiler').classList.toggle('open')">
                        <span class="desc-spoiler-icon">▶</span>
                        <span>Описание задачи</span>
                    </button>
                    <div class="desc-spoiler-body">
                        <div class="desc-editor">
                            <div id="desc-input-mode">
                                <textarea id="desc-textarea" rows="10"
                                          placeholder="Вставьте текст или HTML из Битрикса..."></textarea>
                                <div class="desc-actions">
                                    <button type="button" id="ai-format-btn" class="btn btn-ghost btn-sm">
                                        <span id="ai-btn-text">✨ Отформатировать через ИИ</span>
                                    </button>
                                </div>
                            </div>
                            <div id="desc-preview-mode" style="display:none">
                                <div id="desc-preview" class="desc-preview"></div>
                                <div class="desc-actions">
                                    <button type="button" id="desc-edit-btn" class="btn btn-ghost btn-sm">✏️ Редактировать снова</button>
                                </div>
                            </div>
                            <input type="hidden" name="description" id="desc-hidden"
                                   value="<?= htmlspecialchars($task['description'] ?? '') ?>">
                        </div>
                    </div>
                </div>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label for="project_id">Проект <span class="required">*</span></label>
                    <select id="project_id" name="project_id">
                        <?php foreach ($projects as $p): ?>
                            <option value="<?= $p['id'] ?>"
                                <?= ($task['project_id'] == $p['id']) ? 'selected' : '' ?>>
                                <?= htmlspecialchars($p['name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-group">
                    <label for="assignee_id">Исполнитель</label>
                    <select id="assignee_id" name="assignee_id">
                        <option value="">— не назначен —</option>
                        <?php foreach ($assignees as $a): ?>
                            <option value="<?= $a['id'] ?>"
                                <?= ($task['assignee_id'] == $a['id']) ? 'selected' : '' ?>>
                                <?= htmlspecialchars($a['name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label for="department_id">Отдел</label>
                    <select id="department_id" name="department_id" onchange="filterCustomers(this.value)">
                        <option value="">— выберите отдел —</option>
                        <?php foreach ($departments as $d): ?>
                            <option value="<?= $d['id'] ?>"
                                <?= $task['department_id'] == $d['id'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($d['name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-group">
                    <label for="customer_id">Заказчик</label>
                    <select id="customer_id" name="customer_id">
                        <option value="">— выберите заказчика —</option>
                        <?php foreach ($customers_all as $c): ?>
                            <option value="<?= $c['id'] ?>"
                                    data-dept="<?= $c['department_id'] ?>"
                                <?= $task['customer_id'] == $c['id'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($c['name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>

            <div class="form-group">
                <label class="checkbox-label">
                    <input type="checkbox" name="is_presidency" value="1"
                        <?= $task['is_presidency'] ? 'checked' : '' ?>>
                    <span class="checkbox-text">🔴 Задача от Area Presidency</span>
                </label>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label for="priority">Приоритет</label>
                    <select id="priority" name="priority">
                        <option value="high"   <?= $task['priority'] === 'high'   ? 'selected' : '' ?>>Высокий</option>
                        <option value="medium" <?= $task['priority'] === 'medium' ? 'selected' : '' ?>>Средний</option>
                        <option value="low"    <?= $task['priority'] === 'low'    ? 'selected' : '' ?>>Низкий</option>
                    </select>
                </div>

                <div class="form-group">
                    <label for="work_type">Тип работ</label>
                    <select id="work_type" name="work_type">
                        <option value="">— не указан —</option>
                        <option value="new_project" <?= $task['work_type'] === 'new_project' ? 'selected' : '' ?>>Новый проект</option>
                        <option value="improvement" <?= $task['work_type'] === 'improvement' ? 'selected' : '' ?>>Доработка</option>
                        <option value="bugfix"      <?= $task['work_type'] === 'bugfix'      ? 'selected' : '' ?>>Исправление ошибки</option>
                    </select>
                </div>
            </div>

            <div class="form-group">
                <label>Сложность</label>
                <div class="stars-input">
                    <?php for ($i = 1; $i <= 5; $i++): ?>
                        <input type="radio" name="complexity" id="star<?= $i ?>" value="<?= $i ?>"
                            <?= ($task['complexity'] ?? '') == $i ? 'checked' : '' ?>>
                        <label for="star<?= $i ?>" class="star" data-value="<?= $i ?>">★</label>
                    <?php endfor; ?>
                </div>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label for="estimated_hours">Оценка (часов)</label>
                    <input type="number" id="estimated_hours" name="estimated_hours"
                        min="0.5" max="999" step="0.5"
                        value="<?= htmlspecialchars($_POST['estimated_hours'] ?? $task['estimated_hours']) ?>">
                </div>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label for="date_start">Дата начала</label>
                    <input type="date" id="date_start" name="date_start"
                        value="<?= htmlspecialchars($_POST['date_start'] ?? $task['date_start']) ?>">
                </div>
                <div class="form-group">
                    <label for="date_end">Дата завершения</label>
                    <input type="date" id="date_end" name="date_end"
                        value="<?= htmlspecialchars($_POST['date_end'] ?? $task['date_end']) ?>">
                </div>
            </div>

            <?php if ($tags): ?>
            <div class="form-group">
                <label>Теги</label>
                <div class="tags-grid">
                    <?php foreach ($tags as $tag): ?>
                        <label class="tag-checkbox">
                            <input type="checkbox" name="tags[]" value="<?= $tag['id'] ?>"
                                <?= in_array($tag['id'], $selectedTags) ? 'checked' : '' ?>>
                            <span class="tag-label" style="border-color:<?= htmlspecialchars($tag['color']) ?>">
                                <?= htmlspecialchars($tag['name']) ?>
                            </span>
                        </label>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endif; ?>

        </div>

        <div class="form-actions">
            <a href="/tasks/<?= $task['id'] ?>" class="btn btn-ghost">Отмена</a>
            <button type="submit" class="btn btn-primary">Сохранить изменения</button>
        </div>
    </form>
</div>

<script nonce="<?= htmlspecialchars($_SERVER['CSP_NONCE'], ENT_QUOTES, 'UTF-8') ?>">
const CSRF_TOKEN = '<?= csrfToken() ?>';
const textarea    = document.getElementById('desc-textarea');
const inputMode   = document.getElementById('desc-input-mode');
const previewMode = document.getElementById('desc-preview-mode');
const preview     = document.getElementById('desc-preview');
const hiddenInput = document.getElementById('desc-hidden');

const existing = hiddenInput.value.trim();
if (existing) {
    preview.innerHTML         = existing;
    inputMode.style.display   = 'none';
    previewMode.style.display = 'block';
    document.getElementById('desc-spoiler-edit').classList.add('open');
}

document.getElementById('desc-edit-btn').addEventListener('click', function() {
    previewMode.style.display = 'none';
    inputMode.style.display   = 'block';
    textarea.value = hiddenInput.value;
});

document.getElementById('ai-format-btn').addEventListener('click', async function() {
    const text = textarea.value.trim();
    if (!text) { alert('Сначала введите текст'); return; }

    const btn     = this;
    const btnText = document.getElementById('ai-btn-text');
    btn.disabled  = true;
    btnText.textContent = '⏳ Форматирую...';

    try {
        const res = await fetch('/api/format', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({ text: text, csrf_token: CSRF_TOKEN })
        });
        const data = await res.json();
        if (data.html) {
            hiddenInput.value         = data.html;
            preview.innerHTML         = data.html;
            inputMode.style.display   = 'none';
            previewMode.style.display = 'block';
        } else {
            alert('Ошибка форматирования');
        }
    } catch(e) {
        alert('Ошибка соединения');
    } finally {
        btn.disabled = false;
        btnText.textContent = '✨ Отформатировать через ИИ';
    }
});

document.querySelector('form').addEventListener('submit', function() {
    if (inputMode.style.display !== 'none') {
        hiddenInput.value = textarea.value;
    }
});

function filterCustomers(deptId) {
    const select  = document.getElementById('customer_id');
    const options = select.querySelectorAll('option');
    options.forEach(opt => {
        if (!opt.value) return;
        opt.style.display = (!deptId || opt.dataset.dept === deptId) ? '' : 'none';
    });
    select.value = '';
}

const deptSelect = document.getElementById('department_id');
if (deptSelect.value) filterCustomers(deptSelect.value);
</script>

</body>
</html>