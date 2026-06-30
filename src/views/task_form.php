<!DOCTYPE html>
<html lang="ru">
<?php $pageTitle = 'Новая задача'; require __DIR__ . '/head.php'; ?>
<body>

<header class="header">
    <div style="display:flex;align-items:center;gap:24px">
        <a href="/" class="header-logo">Most</a>
    </div>
    <div class="header-nav">
        <span class="header-user"><?= htmlspecialchars($_SESSION['user_name'], ENT_QUOTES, 'UTF-8') ?></span>
        <a href="/logout" class="btn btn-ghost">Выйти</a>
    </div>
</header>

<div class="container">
    <div class="page-header">
        <h1 class="page-title">Новая задача</h1>
        <a href="/" class="btn btn-ghost">← Назад</a>
    </div>

    <?php if ($error): ?>
        <div class="alert alert-error"><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></div>
    <?php endif; ?>

    <form method="POST" action="/tasks/create" id="task-form">
        <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">

        <div class="form-card">

            <div class="form-group">
                <label for="title">Название <span class="required">*</span></label>
                <input type="text" id="title" name="title"
                       value="<?= htmlspecialchars($_POST['title'] ?? '', ENT_QUOTES, 'UTF-8') ?>"
                       placeholder="Кратко опишите задачу" autofocus maxlength="500">
            </div>

            <!-- Описание — contenteditable -->
            <div class="form-group">
                <label>Описание</label>
                <div class="desc-editor">
                    <div class="desc-toolbar">
                        <button type="button" id="ai-format-btn" class="btn btn-ghost btn-sm">
                            <span id="ai-btn-text">✨ Отформатировать через ИИ</span>
                        </button>
                        <span class="desc-toolbar-hint">Ctrl+V для вставки скрина</span>
                    </div>
                    <div id="desc-content"
                         class="desc-contenteditable"
                         contenteditable="true"
                         data-placeholder="Вставьте текст или HTML из Битрикса, или скрин через Ctrl+V..."></div>
                    <input type="hidden" name="description" id="desc-hidden"
                           value="<?= htmlspecialchars($_POST['description'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
                    <div id="desc-upload-status" style="display:none;font-size:12px;color:var(--text-muted);margin-top:6px">
                        ⏳ Загружаю изображение...
                    </div>
                </div>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label for="project_id">Проект <span class="required">*</span></label>
                    <select id="project_id" name="project_id">
                        <option value="">— выберите —</option>
                        <?php foreach ($projects as $p): ?>
                            <option value="<?= (int)$p['id'] ?>"
                                <?= ($_POST['project_id'] ?? '') == $p['id'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($p['name'], ENT_QUOTES, 'UTF-8') ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-group">
                    <label for="assignee_id">Исполнитель</label>
                    <select id="assignee_id" name="assignee_id">
                        <option value="">— не назначен —</option>
                        <?php foreach ($assignees as $a): ?>
                            <option value="<?= (int)$a['id'] ?>"
                                <?= ($_POST['assignee_id'] ?? '') == $a['id'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($a['name'], ENT_QUOTES, 'UTF-8') ?>
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
                            <option value="<?= (int)$d['id'] ?>"
                                <?= ($_POST['department_id'] ?? '') == $d['id'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($d['name'], ENT_QUOTES, 'UTF-8') ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-group">
                    <label for="customer_id">Заказчик</label>
                    <select id="customer_id" name="customer_id">
                        <option value="">— выберите заказчика —</option>
                        <?php foreach ($customers_all as $c): ?>
                            <option value="<?= (int)$c['id'] ?>"
                                    data-dept="<?= (int)$c['department_id'] ?>"
                                <?= ($_POST['customer_id'] ?? '') == $c['id'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($c['name'], ENT_QUOTES, 'UTF-8') ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label class="checkbox-label">
                        <input type="checkbox" name="is_presidency" value="1"
                            <?= ($_POST['is_presidency'] ?? '') ? 'checked' : '' ?>>
                        <span class="checkbox-text">🔴 Задача от Area Presidency</span>
                    </label>
                </div>
            </div>

            <div class="form-group">
                <label for="priority">Приоритет</label>
                <select id="priority" name="priority">
                    <option value="high"   <?= ($_POST['priority'] ?? '') === 'high'   ? 'selected' : '' ?>>Высокий</option>
                    <option value="medium" <?= ($_POST['priority'] ?? 'medium') === 'medium' ? 'selected' : '' ?>>Средний</option>
                    <option value="low"    <?= ($_POST['priority'] ?? '') === 'low'    ? 'selected' : '' ?>>Низкий</option>
                </select>
            </div>

            <div class="form-group">
                <label for="work_type">Тип работ</label>
                <select id="work_type" name="work_type">
                    <option value="">— не указан —</option>
                    <option value="new_project" <?= ($_POST['work_type'] ?? '') === 'new_project' ? 'selected' : '' ?>>Новый проект</option>
                    <option value="improvement" <?= ($_POST['work_type'] ?? '') === 'improvement' ? 'selected' : '' ?>>Доработка</option>
                    <option value="bugfix"      <?= ($_POST['work_type'] ?? '') === 'bugfix'      ? 'selected' : '' ?>>Исправление ошибки</option>
                </select>
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
                    <label for="estimated_hours">Оценка (часов)</label>
                    <input type="number" id="estimated_hours" name="estimated_hours"
                        min="0.5" max="999" step="0.5"
                        value="<?= htmlspecialchars($_POST['estimated_hours'] ?? '', ENT_QUOTES, 'UTF-8') ?>"
                        placeholder="например 4">
                </div>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label for="date_start">Дата начала</label>
                    <input type="date" id="date_start" name="date_start"
                        value="<?= htmlspecialchars($_POST['date_start'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
                </div>
                <div class="form-group">
                    <label for="date_end">Дата завершения</label>
                    <input type="date" id="date_end" name="date_end"
                        value="<?= htmlspecialchars($_POST['date_end'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
                </div>
            </div>

            <?php if ($tags): ?>
            <div class="form-group">
                <label>Теги</label>
                <div class="tags-grid">
                    <?php foreach ($tags as $tag): ?>
                        <label class="tag-checkbox">
                            <input type="checkbox" name="tags[]" value="<?= (int)$tag['id'] ?>"
                                <?= in_array($tag['id'], $_POST['tags'] ?? []) ? 'checked' : '' ?>>
                            <span class="tag-label" style="border-color:<?= htmlspecialchars($tag['color'], ENT_QUOTES, 'UTF-8') ?>">
                                <?= htmlspecialchars($tag['name'], ENT_QUOTES, 'UTF-8') ?>
                            </span>
                        </label>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endif; ?>
            <div class="form-group">
                <label>Цвет карточки</label>
                <div class="color-picker-row">
                    <label class="color-option">
                        <input type="radio" name="color" value="" <?= ($_POST['color'] ?? '') === '' ? 'checked' : '' ?>>
                        <span class="color-swatch color-swatch-none">✕</span>
                    </label>
                    <?php foreach (['#ef4444','#f97316','#eab308','#22c55e','#3b82f6','#8b5cf6','#ec4899','#06b6d4'] as $c): ?>
                        <label class="color-option">
                            <input type="radio" name="color" value="<?= $c ?>"
                                <?= ($_POST['color'] ?? '') === $c ? 'checked' : '' ?>>
                            <span class="color-swatch" style="background:<?= $c ?>"></span>
                        </label>
                    <?php endforeach; ?>
                </div>
            </div>                            
        </div>

        <div class="form-actions">
            <a href="/" class="btn btn-ghost">Отмена</a>
            <button type="submit" class="btn btn-primary">Создать задачу</button>
        </div>
        <input type="hidden" name="temp_id" value="">
    </form>
</div>

<script nonce="<?= htmlspecialchars($_SERVER['CSP_NONCE'], ENT_QUOTES, 'UTF-8') ?>">
const TEMP_ID = crypto.randomUUID();
const CSRF_TOKEN   = '<?= csrfToken() ?>';
const descContent  = document.getElementById('desc-content');
const hiddenInput  = document.getElementById('desc-hidden');
const uploadStatus = document.getElementById('desc-upload-status');

// Загружаем существующее описание
const existing = hiddenInput.value.trim();
if (existing) {
    descContent.innerHTML = existing;
}

// Перед сабмитом — берём HTML из contenteditable
document.getElementById('task-form').addEventListener('submit', function() {
    hiddenInput.value = descContent.innerHTML.trim();
    document.querySelector('input[name="temp_id"]').value = TEMP_ID;
});

// ИИ форматирование
document.getElementById('ai-format-btn').addEventListener('click', async function() {
    const html = descContent.innerHTML.trim();
    if (!html || html === '') { alert('Сначала введите текст'); return; }

    const btn     = this;
    const btnText = document.getElementById('ai-btn-text');
    btn.disabled  = true;
    btnText.textContent = '⏳ Форматирую...';

    // Извлекаем изображения перед отправкой
    const images = [];
    descContent.querySelectorAll('img').forEach((img, i) => {
        images.push({ index: i, src: img.src, alt: img.alt });
        img.replaceWith(`__IMG_${i}__`);
    });

    const textToFormat = descContent.innerHTML;

    // Восстанавливаем изображения обратно на случай ошибки
    images.forEach(img => {
        descContent.innerHTML = descContent.innerHTML.replace(
            `__IMG_${img.index}__`,
            `<img src="${img.src}" alt="${img.alt}" class="desc-img">`
        );
    });

    try {
        const res  = await fetch('/api/format', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ text: textToFormat, csrf_token: CSRF_TOKEN })
        });
        const data = await res.json();

        if (data.html) {
            const txt = document.createElement('textarea');
            txt.innerHTML = data.html;
            let formatted = txt.value;

            // Вставляем изображения обратно
            images.forEach(img => {
                formatted = formatted.replace(
                    `__IMG_${img.index}__`,
                    `<img src="${img.src}" alt="${img.alt}" class="desc-img">`
                );
            });

            descContent.innerHTML = formatted;
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

// Вставка скрина через Ctrl+V
descContent.addEventListener('paste', async function(e) {
    const items = e.clipboardData?.items;
    if (!items) return;

    for (const item of items) {
        if (item.type.startsWith('image/')) {
            e.preventDefault();

            const blob   = item.getAsFile();
            const reader = new FileReader();

            reader.onload = async function() {
                uploadStatus.style.display = 'block';

                try {
                    const formData = new FormData();
                    formData.append('image_data', reader.result);
                    formData.append('temp_id', TEMP_ID); // 0 — задача ещё не создана
                    formData.append('csrf_token', CSRF_TOKEN);

                    const res  = await fetch('/upload', { method: 'POST', body: formData });
                    const data = await res.json();

                    if (data.url) {
                        // Вставляем картинку в позицию курсора
                        const img = document.createElement('img');
                        img.src       = data.url;
                        img.className = 'desc-img';
                        img.dataset.fileId = data.id;

                        const sel = window.getSelection();
                        if (sel.rangeCount) {
                            const range = sel.getRangeAt(0);
                            range.deleteContents();
                            range.insertNode(img);
                            range.setStartAfter(img);
                            range.collapse(true);
                            sel.removeAllRanges();
                            sel.addRange(range);
                        } else {
                            descContent.appendChild(img);
                        }
                    } else {
                        alert(data.error || 'Ошибка загрузки');
                    }
                } catch(e) {
                    alert('Ошибка загрузки изображения');
                } finally {
                    uploadStatus.style.display = 'none';
                }
            };

            reader.readAsDataURL(blob);
            break;
        }
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