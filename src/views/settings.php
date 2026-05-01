<!DOCTYPE html>
<html lang="ru">
<?php $pageTitle = 'Настройки'; require __DIR__ . '/head.php'; ?>
<body>

<header class="header">
    <div style="display:flex;align-items:center;gap:24px">
        <a href="/" class="header-logo">Most</a>
    </div>
    <div class="header-nav">
        <a href="/" class="btn btn-ghost">← Доска</a>
        <span class="header-user"><?= htmlspecialchars($_SESSION['user_name']) ?></span>
        <a href="/logout" class="btn btn-ghost">Выйти</a>
    </div>
</header>

<div class="container" style="max-width:800px">

    <div class="page-header">
        <h1 class="page-title">Настройки</h1>
    </div>

    <?php if ($success): ?>
        <div class="alert" style="background:rgba(34,197,94,0.1);border:1px solid rgba(34,197,94,0.3);color:#86efac;margin-bottom:20px">
            ✓ Изменения сохранены
        </div>
    <?php endif; ?>

    <?php if ($error): ?>
        <div class="alert alert-error"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <!-- Проекты -->
    <div class="settings-section">
        <h2 class="settings-title">Проекты</h2>

        <div class="settings-list">
            <?php foreach ($projects as $p): ?>
                <div class="settings-item">
                    <span class="settings-item-name <?= $p['is_archived'] ? 'archived' : '' ?>">
                        <?= htmlspecialchars($p['name']) ?>
                        <?= $p['is_archived'] ? '<span class="badge badge-tag">архив</span>' : '' ?>
                    </span>
                    <div class="settings-item-actions">
                        <!-- Переименовать -->
                        <button class="btn btn-ghost btn-sm"
                                onclick="document.getElementById('rename-<?= $p['id'] ?>').style.display='block';this.style.display='none'">
                            Переименовать
                        </button>
                        <!-- Архивировать/восстановить -->
                        <form method="POST" action="/settings" style="display:inline">
                            <input type="hidden" name="form" value="toggle_project">
                            <input type="hidden" name="id" value="<?= $p['id'] ?>">
                            <input type="hidden" name="archived" value="<?= $p['is_archived'] ? 0 : 1 ?>">
                            <button type="submit" class="btn btn-ghost btn-sm">
                                <?= $p['is_archived'] ? 'Восстановить' : 'Архивировать' ?>
                            </button>
                        </form>
                    </div>
                </div>
                <!-- Форма переименования -->
                <div id="rename-<?= $p['id'] ?>" style="display:none;padding:10px 0">
                    <form method="POST" action="/settings" style="display:flex;gap:8px">
                        <input type="hidden" name="form" value="rename_project">
                        <input type="hidden" name="id" value="<?= $p['id'] ?>">
                        <input type="text" name="name" value="<?= htmlspecialchars($p['name']) ?>" style="flex:1">
                        <button type="submit" class="btn btn-primary btn-sm">Сохранить</button>
                        <button type="button" class="btn btn-ghost btn-sm"
                                onclick="this.closest('div').style.display='none'">Отмена</button>
                    </form>
                </div>
            <?php endforeach; ?>
        </div>
        <!-- Тема -->
        <div class="settings-section">
            <h2 class="settings-title">Оформление</h2>
            <?php $currentTheme = getUserTheme(); ?>

            <form method="POST" action="/settings">
                <input type="hidden" name="form" value="set_theme">
                <input type="hidden" name="theme" id="theme-input" value="<?= $currentTheme ?>">

                <div class="themes-grid">
                    <?php
                    $themes = [
                        'dark-default'  => ['name' => 'Тёмная',        'bg' => '#0f1117', 'accent' => '#4f6ef7'],
                        'dark-blue'     => ['name' => 'Ночной океан',   'bg' => '#060d1a', 'accent' => '#64ffda'],
                        'dark-green'    => ['name' => 'Матрица',        'bg' => '#0a0f0a', 'accent' => '#69db7c'],
                        'light-default' => ['name' => 'Светлая',        'bg' => '#f5f6fa', 'accent' => '#4f6ef7'],
                        'light-warm'    => ['name' => 'Тёплая',         'bg' => '#faf8f5', 'accent' => '#c2762a'],
                        'light-purple'  => ['name' => 'Лавандовая',     'bg' => '#f8f5ff', 'accent' => '#7c3aed'],
                    ];
                    foreach ($themes as $key => $t): ?>
                        <div class="theme-card <?= $currentTheme === $key ? 'active' : '' ?>"
                            onclick="selectTheme('<?= $key ?>')">
                            <div class="theme-preview" style="background:<?= $t['bg'] ?>">
                                <div class="theme-preview-bar" style="background:<?= $t['accent'] ?>"></div>
                                <div class="theme-preview-lines">
                                    <div style="background:<?= $t['accent'] ?>33;height:6px;border-radius:3px;margin-bottom:4px"></div>
                                    <div style="background:<?= $t['accent'] ?>22;height:6px;border-radius:3px;width:70%"></div>
                                </div>
                            </div>
                            <div class="theme-name"><?= $t['name'] ?></div>
                            <div class="theme-check">✓</div>
                        </div>
                    <?php endforeach; ?>
                </div>

                <button type="submit" class="btn btn-primary" style="margin-top:16px">Применить тему</button>
            </form>
        </div>                                      
        <!-- Добавить проект -->
        <form method="POST" action="/settings" style="display:flex;gap:8px;margin-top:16px">
            <input type="hidden" name="form" value="add_project">
            <input type="text" name="name" placeholder="Название нового проекта" style="flex:1">
            <button type="submit" class="btn btn-primary">Добавить</button>
        </form>
    </div>

    <!-- Теги -->
    <div class="settings-section">
        <h2 class="settings-title">Теги</h2>

        <div class="settings-list">
            <?php if ($tags): ?>
                <?php foreach ($tags as $tag): ?>
                    <div class="settings-item">
                        <span class="settings-item-name">
                            <span class="tag-dot" style="background:<?= htmlspecialchars($tag['color']) ?>"></span>
                            <?= htmlspecialchars($tag['name']) ?>
                        </span>
                        <form method="POST" action="/settings">
                            <input type="hidden" name="form" value="delete_tag">
                            <input type="hidden" name="id" value="<?= $tag['id'] ?>">
                            <button type="submit" class="btn btn-ghost btn-sm"
                                    onclick="return confirm('Удалить тег «<?= htmlspecialchars($tag['name']) ?>»?')">
                                Удалить
                            </button>
                        </form>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="empty-state" style="padding:16px">Тегов пока нет</div>
            <?php endif; ?>
        </div>

        <!-- Добавить тег -->
        <form method="POST" action="/settings" style="display:flex;gap:8px;margin-top:16px;align-items:center">
            <input type="hidden" name="form" value="add_tag">
            <input type="text" name="name" placeholder="Название тега" style="flex:1">
            <input type="color" name="color" value="#4f6ef7" style="width:40px;height:38px;border:1px solid var(--border);border-radius:var(--radius);background:var(--bg-secondary);cursor:pointer;padding:2px">
            <button type="submit" class="btn btn-primary">Добавить</button>
        </form>
    </div>

</div>
<script>
function selectTheme(key) {
    document.getElementById('theme-input').value = key;
    document.querySelectorAll('.theme-card').forEach(c => c.classList.remove('active'));
    document.querySelector('.theme-card[onclick="selectTheme(\'' + key + '\')"]').classList.add('active');
}
</script>
</body>
</html>