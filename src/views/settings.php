<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Most — Настройки</title>
    <link rel="stylesheet" href="/public/css/style.css">
</head>
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

</body>
</html>