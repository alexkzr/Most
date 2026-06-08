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
        <span class="header-user"><?= htmlspecialchars($_SESSION['user_name'], ENT_QUOTES, 'UTF-8') ?></span>
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
                        <?= htmlspecialchars($p['name'], ENT_QUOTES, 'UTF-8') ?>
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
        <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">
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
        <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">
                        <input type="hidden" name="form" value="rename_project">
                        <input type="hidden" name="id" value="<?= $p['id'] ?>">
                        <input type="text" name="name" value="<?= htmlspecialchars($p['name'], ENT_QUOTES, 'UTF-8') ?>" style="flex:1">
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
        <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">
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
        <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">
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
                            <span class="tag-dot" style="background:<?= htmlspecialchars($tag['color'], ENT_QUOTES, 'UTF-8') ?>"></span>
                            <?= htmlspecialchars($tag['name'], ENT_QUOTES, 'UTF-8') ?>
                        </span>
                        <form method="POST" action="/settings">
        <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">
                            <input type="hidden" name="form" value="delete_tag">
                            <input type="hidden" name="id" value="<?= $tag['id'] ?>">
                            <button type="submit" class="btn btn-ghost btn-sm"
                                    onclick="return confirm('Удалить тег «<?= htmlspecialchars($tag['name'], ENT_QUOTES, 'UTF-8') ?>»?')">
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
        <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">
            <input type="hidden" name="form" value="add_tag">
            <input type="text" name="name" placeholder="Название тега" style="flex:1">
            <input type="color" name="color" value="#4f6ef7" style="width:40px;height:38px;border:1px solid var(--border);border-radius:var(--radius);background:var(--bg-secondary);cursor:pointer;padding:2px">
            <button type="submit" class="btn btn-primary">Добавить</button>
        </form>
    </div>
    <!-- Отделы и заказчики -->
    <div class="settings-section">
        <h2 class="settings-title">Отделы и заказчики</h2>

        <?php foreach ($departments as $dept): ?>
            <div class="dept-block">
                <div class="dept-header">
                    <span class="dept-name"><?= htmlspecialchars($dept['name'], ENT_QUOTES, 'UTF-8') ?></span>
                    <div class="settings-item-actions">
                        <button class="btn btn-ghost btn-sm"
                                onclick="document.getElementById('rename-dept-<?= $dept['id'] ?>').style.display='block';this.style.display='none'">
                            Переименовать
                        </button>
                        <form method="POST" action="/settings" style="display:inline">
        <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">
                            <input type="hidden" name="form" value="delete_department">
                            <input type="hidden" name="id" value="<?= $dept['id'] ?>">
                            <button type="submit" class="btn btn-ghost btn-sm"
                                    onclick="return confirm('Удалить отдел и всех заказчиков?')">
                                Удалить
                            </button>
                        </form>
                    </div>
                </div>

                <!-- Форма переименования отдела -->
                <div id="rename-dept-<?= $dept['id'] ?>" style="display:none;padding:8px 0">
                    <form method="POST" action="/settings" style="display:flex;gap:8px">
        <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">
                        <input type="hidden" name="form" value="rename_department">
                        <input type="hidden" name="id" value="<?= $dept['id'] ?>">
                        <input type="text" name="name" value="<?= htmlspecialchars($dept['name'], ENT_QUOTES, 'UTF-8') ?>" style="flex:1">
                        <button type="submit" class="btn btn-primary btn-sm">Сохранить</button>
                        <button type="button" class="btn btn-ghost btn-sm"
                                onclick="this.closest('div').style.display='none'">Отмена</button>
                    </form>
                </div>

                <!-- Заказчики отдела -->
                <div class="customers-list">
                    <?php foreach ($customers as $c): ?>
                        <?php if ($c['department_id'] != $dept['id']) continue; ?>
                        <div class="settings-item" style="padding-left:16px">
                            <span class="settings-item-name">— <?= htmlspecialchars($c['name'], ENT_QUOTES, 'UTF-8') ?></span>
                            <div class="settings-item-actions">
                                <button class="btn btn-ghost btn-sm"
                                        onclick="document.getElementById('rename-customer-<?= $c['id'] ?>').style.display='block';this.style.display='none'">
                                    Изменить
                                </button>
                                <form method="POST" action="/settings" style="display:inline">
        <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">
                                    <input type="hidden" name="form" value="delete_customer">
                                    <input type="hidden" name="id" value="<?= $c['id'] ?>">
                                    <button type="submit" class="btn btn-ghost btn-sm"
                                            onclick="return confirm('Удалить заказчика?')">
                                        Удалить
                                    </button>
                                </form>
                            </div>
                        </div>
                        <div id="rename-customer-<?= $c['id'] ?>" style="display:none;padding:8px 16px">
                            <form method="POST" action="/settings" style="display:flex;gap:8px">
        <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">
                                <input type="hidden" name="form" value="rename_customer">
                                <input type="hidden" name="id" value="<?= $c['id'] ?>">
                                <input type="text" name="name" value="<?= htmlspecialchars($c['name'], ENT_QUOTES, 'UTF-8') ?>" style="flex:1">
                                <button type="submit" class="btn btn-primary btn-sm">Сохранить</button>
                                <button type="button" class="btn btn-ghost btn-sm"
                                        onclick="this.closest('div').style.display='none'">Отмена</button>
                            </form>
                        </div>
                    <?php endforeach; ?>

                    <!-- Добавить заказчика в этот отдел -->
                    <form method="POST" action="/settings" style="display:flex;gap:8px;padding:8px 16px">
        <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">
                        <input type="hidden" name="form" value="add_customer">
                        <input type="hidden" name="department_id" value="<?= $dept['id'] ?>">
                        <input type="text" name="name" placeholder="Имя нового заказчика" style="flex:1">
                        <button type="submit" class="btn btn-ghost btn-sm">+ Добавить</button>
                    </form>
                </div>
            </div>
        <?php endforeach; ?>

        <!-- Добавить отдел -->
        <div style="margin-top:16px;padding-top:16px;border-top:1px solid var(--border)">
            <form method="POST" action="/settings" style="display:flex;gap:8px">
        <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">
                <input type="hidden" name="form" value="add_department">
                <input type="text" name="name" placeholder="Название нового отдела" style="flex:1">
                <button type="submit" class="btn btn-primary">Добавить отдел</button>
            </form>
        </div>
    </div>
<!-- Двухфакторная аутентификация -->
<div class="settings-section">
    <h2 class="settings-title">Двухфакторная аутентификация</h2>

    <?php if ($success === '2fa_enabled'): ?>
        <div class="alert" style="background:rgba(34,197,94,0.1);border:1px solid rgba(34,197,94,0.3);color:#86efac;margin-bottom:16px">
            ✓ 2FA успешно включена
        </div>
    <?php elseif ($success === '2fa_disabled'): ?>
        <div class="alert" style="background:rgba(245,158,11,0.1);border:1px solid rgba(245,158,11,0.3);color:#fcd34d;margin-bottom:16px">
            2FA отключена
        </div>
    <?php endif; ?>

    <?php if ($me['totp_enabled']): ?>
        <div style="display:flex;align-items:center;gap:10px;margin-bottom:20px">
            <span style="color:var(--success);font-size:20px">✓</span>
            <span style="font-weight:500">2FA включена</span>
        </div>

        <form method="POST" action="/settings">
            <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">
            <input type="hidden" name="form" value="disable_2fa">
            <div class="form-group">
                <label for="disable_code">Введите код из приложения для отключения</label>
                <input type="text"
                       id="disable_code"
                       name="code"
                       maxlength="6"
                       inputmode="numeric"
                       pattern="[0-9]{6}"
                       placeholder="000000"
                       style="font-size:20px;letter-spacing:6px;text-align:center;max-width:200px">
            </div>
            <button type="submit" class="btn btn-danger">Отключить 2FA</button>
        </form>

    <?php else: ?>
        <div style="display:flex;align-items:center;gap:10px;margin-bottom:20px">
            <span style="color:var(--text-muted);font-size:20px">○</span>
            <span style="color:var(--text-muted)">2FA не включена</span>
        </div>
        <a href="/settings?setup_2fa=1" class="btn btn-primary">Настроить 2FA</a>
    <?php endif; ?>
</div>
</div>
<script nonce="<?= htmlspecialchars($_SERVER['CSP_NONCE'], ENT_QUOTES, 'UTF-8') ?>">
function selectTheme(key) {
    document.getElementById('theme-input').value = key;
    document.querySelectorAll('.theme-card').forEach(c => c.classList.remove('active'));
    document.querySelector('.theme-card[onclick="selectTheme(\'' + key + '\')"]').classList.add('active');
}
</script>
</body>
</html>