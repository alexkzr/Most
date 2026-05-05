<!DOCTYPE html>
<html lang="ru">
<?php $pageTitle = 'Форма Таски'; require __DIR__ . '/head.php'; ?>
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
                <label for="description">Описание</label>
                <div style="position:relative">
                    <button type="button" id="ai-format-btn" class="btn btn-ghost btn-sm"
                            style="position:absolute;top:-34px;right:0;z-index:10;display:flex;align-items:center;gap:6px">
                        <span id="ai-btn-text">✨ Отформатировать через ИИ</span>
                    </button>
                    <div id="description-editor" style="min-height:200px"></div>
                    <textarea id="description" name="description" style="display:none"><?= htmlspecialchars($_POST['description'] ?? '') ?></textarea>
                </div>
            </div>

            <!-- Описание -->
            <div class="form-group">
                <label for="description">Описание</label>
                <textarea id="description" name="description"
                          placeholder="Подробности, требования, ссылки..."><?= htmlspecialchars($_POST['description'] ?? '') ?></textarea>
            </div>

            <div class="form-row">
                <!-- Проект -->
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

                <!-- Исполнитель -->
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
                <!-- Заказчик -->
                <div class="form-group">
                    <label for="customer">Заказчик</label>
                    <input type="text" id="customer" name="customer"
                           value="<?= htmlspecialchars($_POST['customer'] ?? '') ?>"
                           placeholder="Кто инициировал задачу">
                </div>

                <!-- Приоритет -->
                <div class="form-group">
                    <label for="priority">Приоритет</label>
                    <select id="priority" name="priority">
                        <option value="high"   <?= ($_POST['priority'] ?? '') === 'high'   ? 'selected' : '' ?>>Высокий</option>
                        <option value="medium" <?= ($_POST['priority'] ?? 'medium') === 'medium' ? 'selected' : '' ?>>Средний</option>
                        <option value="low"    <?= ($_POST['priority'] ?? '') === 'low'    ? 'selected' : '' ?>>Низкий</option>
                    </select>
                </div>
            </div>

            <div class="form-row">
                <!-- Срок -->
                <div class="form-group">
                    <label for="deadline">Срок</label>
                    <input type="date" id="deadline" name="deadline"
                           value="<?= htmlspecialchars($_POST['deadline'] ?? '') ?>">
                </div>

                <!-- Оценка -->
                <div class="form-group">
                    <label for="estimated_hours">Оценка (часов)</label>
                    <input type="number" id="estimated_hours" name="estimated_hours"
                           min="0.5" max="999" step="0.5"
                           value="<?= htmlspecialchars($_POST['estimated_hours'] ?? '') ?>"
                           placeholder="например 4">
                </div>
            </div>

            <!-- Теги -->
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
// Инициализация Quill
const quill = new Quill('#description-editor', {
    theme: 'snow',
    placeholder: 'Подробности, требования, ссылки...',
    modules: {
        toolbar: [
            [{ header: [1, 2, 3, false] }],
            ['bold', 'italic', 'underline', 'strike'],
            [{ list: 'ordered' }, { list: 'bullet' }],
            ['code-block', 'blockquote'],
            [{ table: true }],
            ['clean']
        ]
    }
});

// Если есть сохранённое значение — загружаем
const savedContent = document.getElementById('description').value;
if (savedContent) {
    quill.root.innerHTML = savedContent;
}

// Перед сабмитом — копируем HTML из Quill в textarea
document.querySelector('form').addEventListener('submit', function() {
    document.getElementById('description').value = quill.root.innerHTML;
});

// ИИ форматирование
document.getElementById('ai-format-btn').addEventListener('click', async function() {
    const text = quill.getText().trim();
    if (!text) {
        alert('Сначала введите текст');
        return;
    }

    const btn     = this;
    const btnText = document.getElementById('ai-btn-text');
    btn.disabled  = true;
    btnText.textContent = '⏳ Форматирую...';

    try {
        const res = await fetch('/api/format', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ text: quill.root.innerHTML })
        });

        const data = await res.json();
        if (data.html) {
            quill.root.innerHTML = data.html;
        } else {
            alert('Ошибка форматирования');
        }
    } catch (e) {
        alert('Ошибка соединения');
    } finally {
        btn.disabled = false;
        btnText.textContent = '✨ Отформатировать через ИИ';
    }
});
</script>
</body>
</html>