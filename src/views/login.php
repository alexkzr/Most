<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Most — Вход</title>
    <link rel="stylesheet" href="/public/css/style.css">
</head>
<body class="login-page">

<div class="login-box">
    <div class="login-logo">Most</div>
    <div class="login-subtitle">Трекер задач</div>

    <?php if ($error): ?>
        <div class="alert alert-error"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <form method="POST" action="/login">
        <div class="form-group">
            <label for="login">Логин</label>
            <input
                type="text"
                id="login"
                name="login"
                autocomplete="username"
                value="<?= htmlspecialchars($_POST['login'] ?? '') ?>"
                autofocus
            >
        </div>
        <div class="form-group">
            <label for="password">Пароль</label>
            <input
                type="password"
                id="password"
                name="password"
                autocomplete="current-password"
            >
        </div>
        <button type="submit" class="btn btn-primary btn-block">Войти</button>
    </form>
</div>

</body>
</html>