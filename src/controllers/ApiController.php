<?php

require_once __DIR__ . '/../../config.php';

header('Content-Type: application/json');

$uri    = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$method = $_SERVER['REQUEST_METHOD'];

// Только POST и только авторизованные
if ($method !== 'POST' || !isset($_SESSION['user_id'])) {
    http_response_code(403);
    echo json_encode(['error' => 'Forbidden']);
    exit;
}

// /api/format
if ($uri === '/api/format') {

    // Читаем тело запроса один раз
    $body     = json_decode(file_get_contents('php://input'), true);
    $token    = $body['csrf_token'] ?? '';
    $expected = $_SESSION['csrf_token'] ?? '';

    if (!$expected || !hash_equals($expected, $token)) {
        http_response_code(403);
        echo json_encode(['error' => 'CSRF token mismatch']);
        exit;
    }

    $text = $body['text'] ?? '';

    if (!$text) {
        echo json_encode(['error' => 'Empty text']);
        exit;
    }

    if (mb_strlen($text) > 50000) {
        http_response_code(400);
        echo json_encode(['error' => 'Text too long (max 50000 chars)']);
        exit;
    }

    $prompt = "Ты помощник по форматированию технических заданий.
Тебе дают HTML или сырой текст из Битрикс24 или другой системы.
Твоя задача — очистить и отформатировать его в красивый HTML для отображения в веб-приложении.

Правила очистки:
- Удали все пустые теги: <p></p>, <p><br></p>, <p> </p> и подобные
- Удали множественные переносы строк и лишние пробелы между блоками
- Убери inline стили и классы Битрикс24 (ui-typography-*, etc)
- Убери кнопки и служебные элементы Битрикса (<button>, etc)

Правила форматирования:
- Используй только теги: h2, h3, p, ul, ol, li, strong, em, code, pre, blockquote, table, thead, tbody, tr, th, td
- Нумерованные разделы (1. Цель, 2. Справочник) оформляй как <h2>
- Подразделы (2.1, 3.1) оформляй как <h3>
- Ненумерованные подзаголовки внутри разделов оформляй как <h3>
- Таблицы оформляй с <thead> для первой строки заголовков и <tbody> для данных
- Маркированные и нумерованные списки оформляй как <ul>/<ol> с <li>
- Код и технические строки оформляй как <code>
- Выделенные блоки с примерами оформляй как <blockquote>
- Сохрани весь смысл и содержание без изменений

Верни ТОЛЬКО HTML без обёртки в markdown блок, без пояснений и комментариев.

Текст для форматирования:
{$text}";

    $response = file_get_contents('https://api.proxyapi.ru/anthropic/v1/messages', false, stream_context_create([
        'http' => [
            'method'  => 'POST',
            'header'  => implode("\r\n", [
                'Content-Type: application/json',
                'x-api-key: ' . ANTHROPIC_API_KEY,
                'anthropic-version: 2023-06-01',
            ]),
            'content' => json_encode([
                'model'      => 'claude-sonnet-4-20250514',
                'max_tokens' => 4096,
                'messages'   => [
                    ['role' => 'user', 'content' => $prompt]
                ]
            ]),
            'ignore_errors' => true,
        ]
    ]));

    if (!$response) {
        http_response_code(502);
        echo json_encode(['error' => 'API error']);
        exit;
    }

    $data = json_decode($response, true);
    $html = $data['content'][0]['text'] ?? '';

    $html = preg_replace('/^```html\s*/i', '', $html);
    $html = preg_replace('/\s*```$/', '', $html);
    $html = sanitizeHtml($html);

    echo json_encode(['html' => $html]);
    exit;
}

http_response_code(404);
echo json_encode(['error' => 'Not found']);