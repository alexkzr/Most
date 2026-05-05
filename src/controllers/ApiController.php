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
    $text = strip_tags($body['text'] ?? '');

    if (!$text) {
        echo json_encode(['error' => 'Empty text']);
        exit;
    }

    $prompt = "Ты помощник по форматированию технических заданий. 
Тебе дают сырой текст технического задания или описания задачи. 
Твоя задача — отформатировать его в чистый HTML для отображения в редакторе Quill.

Правила:
- Используй только теги: h1, h2, h3, p, ul, ol, li, strong, em, code, pre, blockquote
- Выяви структуру: заголовки, списки, шаги, код
- Убери лишние пробелы и переносы
- Сохрани весь смысл и содержание без изменений
- Верни ТОЛЬКО HTML без обёртки в markdown блок и без пояснений

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