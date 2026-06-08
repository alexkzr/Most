<?php $pageTitle = 'Настройка 2FA'; require __DIR__ . '/head.php'; ?>
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

<div class="container" style="max-width:500px">
    <div class="page-header">
        <h1 class="page-title">Настройка 2FA</h1>
        <a href="/settings" class="btn btn-ghost">← Назад</a>
    </div>

    <?php if (!empty($error)): ?>
        <div class="alert alert-error"><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></div>
    <?php endif; ?>

    <div class="form-card">
        <p style="margin-bottom:20px;color:var(--text-muted);line-height:1.7">
            Установите приложение <strong style="color:var(--text)">Google Authenticator</strong> или
            <strong style="color:var(--text)">Authy</strong> на телефон и отсканируйте QR-код.
        </p>

        <div style="text-align:center;margin-bottom:20px;padding:16px;background:var(--bg-secondary);border-radius:var(--radius)">
            <img src="<?= htmlspecialchars($qrUrl, ENT_QUOTES, 'UTF-8') ?>"
                 alt="QR-код для 2FA"
                 style="width:200px;height:200px;border-radius:8px;background:#fff;padding:8px">
        </div>

        <div style="text-align:center;margin-bottom:24px">
            <p style="font-size:12px;color:var(--text-muted);margin-bottom:6px">Или введите секрет вручную:</p>
            <code style="font-size:15px;letter-spacing:3px;color:var(--accent);background:var(--bg-secondary);padding:8px 16px;border-radius:var(--radius);display:inline-block">
                <?= htmlspecialchars($_SESSION['totp_setup_secret'], ENT_QUOTES, 'UTF-8') ?>
            </code>
        </div>

        <form method="POST" action="/settings">
            <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">
            <input type="hidden" name="form" value="enable_2fa">
            <div class="form-group">
                <label for="code">Введите код из приложения для подтверждения</label>
                <input type="text"
                       id="code"
                       name="code"
                       maxlength="6"
                       inputmode="numeric"
                       pattern="[0-9]{6}"
                       placeholder="000000"
                       autocomplete="one-time-code"
                       autofocus
                       style="font-size:22px;letter-spacing:8px;text-align:center">
            </div>
            <button type="submit" class="btn btn-primary btn-block">Включить 2FA</button>
        </form>
    </div>
</div>

</body>
</html>