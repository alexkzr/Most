<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>404 — Страница не найдена | Most</title>
    <style>
        :root {
            --bg: #0f1117;
            --text: #e8eaf0;
            --muted: #6b7280;
            --accent: #4f6ef7;
        }
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            background: var(--bg);
            color: var(--text);
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 100vh;
            text-align: center;
        }
        .error-code {
            font-size: 120px;
            font-weight: 700;
            color: var(--accent);
            line-height: 1;
            letter-spacing: -4px;
            margin-bottom: 16px;
        }
        .error-title {
            font-size: 22px;
            font-weight: 600;
            margin-bottom: 8px;
        }
        .error-text {
            color: var(--muted);
            font-size: 14px;
            margin-bottom: 32px;
            line-height: 1.6;
        }
        .btn {
            display: inline-block;
            padding: 10px 20px;
            background: var(--accent);
            color: #fff;
            border-radius: 6px;
            text-decoration: none;
            font-size: 14px;
            font-weight: 500;
            transition: opacity 0.2s;
        }
        .btn:hover { opacity: 0.9; }
    </style>
</head>
<body>
    <div>
        <div class="error-code">404</div>
        <div class="error-title">Страница не найдена</div>
        <div class="error-text">
            Такой страницы не существует.<br>
            Возможно, она была удалена или вы перешли по неверной ссылке.
        </div>
        <a href="/" class="btn">← На главную</a>
    </div>
</body>
</html>