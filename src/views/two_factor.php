<?php $pageTitle = 'Двухфакторная аутентификация'; require __DIR__ . '/head.php'; ?>
<body class="login-page">

<div class="login-box">
    <div class="login-logo">Most</div>
    <div class="login-subtitle">Введите код из приложения</div>

    <?php if ($error): ?>
        <div class="alert alert-error"><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></div>
    <?php endif; ?>

    <form method="POST" action="/login/2fa">
        <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">
        <div class="form-group">
            <label for="code">6-значный код</label>
            <input type="text"
                   id="code"
                   name="code"
                   maxlength="6"
                   inputmode="numeric"
                   pattern="[0-9]{6}"
                   placeholder="000000"
                   autocomplete="one-time-code"
                   autofocus
                   style="font-size:24px;letter-spacing:8px;text-align:center">
        </div>
        <button type="submit" class="btn btn-primary btn-block">Подтвердить</button>
    </form>

    <a href="/logout" style="display:block;text-align:center;margin-top:16px;font-size:13px;color:var(--text-muted)">
        ← Назад к логину
    </a>
</div>

</body>
</html>