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
    $body = json_decode(file_get_contents('php://input'), true);
    $text = $body['text'] ?? '';

    if (!$text) {
        echo json_encode(['error' => 'Empty text']);
        exit;
    }

   $prompt = "Ты помощник по форматированию технических заданий.
Тебе дают HTML или сырой текст из Битрикс24 или другой системы.
Твоя задача — очистить и отформатировать его в чистый HTML для редактора Quill.

Правила очистки:
- Удали все пустые теги: <p></p>, <p><br></p>, <p> </p> и подобные
- Удали множественные переносы строк и лишние пробелы между блоками
- Убери избыточные вложенные теги (например <p><span><span>текст</span></span></p> → <p>текст</p>)
- Убери inline стили и классы Битрикс24

Правила форматирования:
- Используй только теги: h1, h2, h3, p, ul, ol, li, strong, em, code, pre, blockquote, table, thead, tbody, tr, th, td
- Если видишь данные в виде строк с разделителями или выровненные колонками — построй HTML таблицу с thead и tbody
- Заголовки разделов оформляй как h2 или h3
- Нумерованные и маркированные списки оформляй как ol/ul
- Код и технические строки оформляй как code или pre
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
        echo json_encode(['error' => 'API error']);
        exit;
    }

    $data = json_decode($response, true);
    $html = $data['content'][0]['text'] ?? '';

    // Чистим на случай если Claude обернул в ```html
    $html = preg_replace('/^```html\s*/i', '', $html);
    $html = preg_replace('/\s*```$/', '', $html);

    echo json_encode(['html' => $html]);
    exit;
}

http_response_code(404);
echo json_encode(['error' => 'Not found']);