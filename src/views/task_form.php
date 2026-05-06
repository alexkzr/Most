<!DOCTYPE html>
<html lang="ru">
<?php $pageTitle = 'Новая задача'; require __DIR__ . '/head.php'; ?>
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
        <h1 class="page-title">Новая задача</h1>
        <a href="/" class="btn btn-ghost">← Назад</a>
    </div>

    <?php if ($error): ?>
        <div class="alert alert-error"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <form method="POST" action="/tasks/create">
        <div class="form-card">

            <!-- Название -->
            <div class="form-group">
                <label for="title">Название <span class="required">*</span></label>
                <input type="text" id="title" name="title"
                       value="<?= htmlspecialchars($_POST['title'] ?? '') ?>"
                       placeholder="Кратко опишите задачу" autofocus>
            </div>

            <!-- Описание -->
            <div class="form-group">
                <label>Описание</label>
                <div class="desc-editor">

                    <!-- Режим ввода -->
                    <div id="desc-input-mode">
                        <textarea id="desc-textarea" rows="10"
                                  placeholder="Вставьте текст или HTML из Битрикса..."><?= htmlspecialchars($_POST['description'] ?? '') ?></textarea>
                        <div class="desc-actions">
                            <button type="button" id="ai-format-btn" class="btn btn-ghost btn-sm">
                                <span id="ai-btn-text">✨ Отформатировать через ИИ</span>
                            </button>
                        </div>
                    </div>

                    <!-- Режим предпросмотра -->
                    <div id="desc-preview-mode" style="display:none">
                        <div id="desc-preview" class="desc-preview"></div>
                        <div class="desc-actions">
                            <button type="button" id="desc-edit-btn" class="btn btn-ghost btn-sm">✏️ Редактировать снова</button>
                        </div>
                    </div>

                    <!-- Скрытый input — уходит в БД -->
                    <input type="hidden" name="description" id="desc-hidden"
                           value="<?= htmlspecialchars($_POST['description'] ?? '') ?>">
                </div>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label for="project_id">Проект <span class="required">*</span></label>
                    <select id="project_id" name="project_id">
                        <option value="">— выберите —</option>
                        <?php foreach ($projects as $p): ?>
                            <option value="<?= $p['id'] ?>"
                                <?= ($_POST['project_id'] ?? '') == $p['id'] ? 'selected' : '' ?>>
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
                                <?= ($_POST['assignee_id'] ?? '') == $a['id'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($a['name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label for="customer">Заказчик</label>
                    <input type="text" id="customer" name="customer"
                           value="<?= htmlspecialchars($_POST['customer'] ?? '') ?>"
                           placeholder="Кто инициировал задачу">
                </div>

                <div class="form-group">
                    <label for="priority">Приоритет</label>
                    <select id="priority" name="priority">
                        <option value="high"   <?= ($_POST['priority'] ?? '') === 'high'   ? 'selected' : '' ?>>Высокий</option>
                        <option value="medium" <?= ($_POST['priority'] ?? 'medium') === 'medium' ? 'selected' : '' ?>>Средний</option>
                        <option value="low"    <?= ($_POST['priority'] ?? '') === 'low'    ? 'selected' : '' ?>>Низкий</option>
                    </select>
                </div>
            </div>
           <div class="form-group">
                <label>Сложность</label>
                <div class="stars-input">
                    <?php for ($i = 1; $i <= 5; $i++): ?>
                        <input type="radio" name="complexity" id="star<?= $i ?>" value="<?= $i ?>"
                            <?= ($_POST['complexity'] ?? '') == $i ? 'checked' : '' ?>>
                        <label for="star<?= $i ?>" class="star" data-value="<?= $i ?>">★</label>
                    <?php endfor; ?>
                </div>
            </div>                    
            <div class="form-row">
                <div class="form-group">
                    <label for="deadline">Срок</label>
                    <input type="date" id="deadline" name="deadline"
                           value="<?= htmlspecialchars($_POST['deadline'] ?? '') ?>">
                </div>

                <div class="form-group">
                    <label for="estimated_hours">Оценка (часов)</label>
                    <input type="number" id="estimated_hours" name="estimated_hours"
                           min="0.5" max="999" step="0.5"
                           value="<?= htmlspecialchars($_POST['estimated_hours'] ?? '') ?>"
                           placeholder="например 4">
                </div>
            </div>

            <?php if ($tags): ?>
            <div class="form-group">
                <label>Теги</label>
                <div class="tags-grid">
                    <?php foreach ($tags as $tag): ?>
                        <label class="tag-checkbox">
                            <input type="checkbox" name="tags[]" value="<?= $tag['id'] ?>"
                                <?= in_array($tag['id'], $_POST['tags'] ?? []) ? 'checked' : '' ?>>
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
            <a href="/" class="btn btn-ghost">Отмена</a>
            <button type="submit" class="btn btn-primary">Создать задачу</button>
        </div>
    </form>
</div>

<script>
const textarea    = document.getElementById('desc-textarea');
const inputMode   = document.getElementById('desc-input-mode');
const previewMode = document.getElementById('desc-preview-mode');
const preview     = document.getElementById('desc-preview');
const hiddenInput = document.getElementById('desc-hidden');

// Если уже есть сохранённый HTML — показываем предпросмотр
const existing = hiddenInput.value.trim();
if (existing) {
    preview.innerHTML        = existing;
    inputMode.style.display  = 'none';
    previewMode.style.display = 'block';
}

// Кнопка "Редактировать снова"
document.getElementById('desc-edit-btn').addEventListener('click', function() {
    previewMode.style.display = 'none';
    inputMode.style.display   = 'block';
    textarea.value = hiddenInput.value;
});

// ИИ форматирование
document.getElementById('ai-format-btn').addEventListener('click', async function() {
    const text = textarea.value.trim();
    if (!text) { alert('Сначала введите текст'); return; }

    const btn     = this;
    const btnText = document.getElementById('ai-btn-text');
    btn.disabled  = true;
    btnText.textContent = '⏳ Форматирую...';

    try {
        const res  = await fetch('/api/format', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ text: text })
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

// Перед сабмитом — если остались в режиме ввода, берём текст из textarea
document.querySelector('form').addEventListener('submit', function() {
    if (inputMode.style.display !== 'none') {
        hiddenInput.value = textarea.value;
    }
});
</script>

</body>
</html>