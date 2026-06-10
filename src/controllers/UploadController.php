<?php

require_once __DIR__ . '/../../config.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_SESSION['user_id'])) {
    http_response_code(403);
    echo json_encode(['error' => 'Forbidden']);
    exit;
}

csrfVerify();

$allowedMimes = [
    'image/jpeg', 'image/png', 'image/gif',
    'application/pdf',
    'application/msword',
    'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
    'application/vnd.ms-excel',
    'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
    'application/zip',
    'application/x-zip-compressed',
];

$maxSize = 2 * 1024 * 1024; // 2MB

// ─── Загрузка файла-вложения ──────────────────────────────────────────────────
if (isset($_FILES['file'])) {
    $taskId = (int)($_POST['task_id'] ?? 0);
    if (!$taskId) {
        http_response_code(400);
        echo json_encode(['error' => 'task_id required']);
        exit;
    }

    $file = $_FILES['file'];

    if ($file['error'] !== UPLOAD_ERR_OK) {
        http_response_code(400);
        echo json_encode(['error' => 'Upload error: ' . $file['error']]);
        exit;
    }

    if ($file['size'] > $maxSize) {
        http_response_code(400);
        echo json_encode(['error' => 'Файл слишком большой. Максимум 2MB.']);
        exit;
    }

    // Проверяем MIME через finfo — надёжнее чем $_FILES['type']
    $finfo    = new finfo(FILEINFO_MIME_TYPE);
    $mimeType = $finfo->file($file['tmp_name']);

    if (!in_array($mimeType, $allowedMimes)) {
        http_response_code(400);
        echo json_encode(['error' => 'Недопустимый тип файла.']);
        exit;
    }

    // Генерируем уникальное имя
    $ext      = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    $filename = bin2hex(random_bytes(16)) . '.' . $ext;
    $destDir  = __DIR__ . '/../../public/uploads/tasks/';
    $destPath = $destDir . $filename;

    if (!move_uploaded_file($file['tmp_name'], $destPath)) {
        http_response_code(500);
        echo json_encode(['error' => 'Не удалось сохранить файл.']);
        exit;
    }

    // Сохраняем в БД
    $stmt = db()->prepare('
        INSERT INTO task_files (task_id, user_id, filename, original_name, file_size, mime_type)
        VALUES (?, ?, ?, ?, ?, ?)
    ');
    $stmt->execute([
        $taskId,
        $_SESSION['user_id'],
        $filename,
        mb_substr($file['name'], 0, 255),
        $file['size'],
        $mimeType,
    ]);

    $fileId = db()->lastInsertId();

    echo json_encode([
        'id'            => $fileId,
        'filename'      => $filename,
        'original_name' => $file['name'],
        'url'           => '/public/uploads/tasks/' . $filename,
        'mime_type'     => $mimeType,
        'size'          => $file['size'],
    ]);
    exit;
}

// ─── Загрузка изображения из буфера (Ctrl+V) ─────────────────────────────────
if (isset($_POST['image_data'])) {
    $taskId    = (int)($_POST['task_id'] ?? 0);
    $imageData = $_POST['image_data'];

    if (!$taskId) {
        http_response_code(400);
        echo json_encode(['error' => 'task_id required']);
        exit;
    }

    // Парсим base64
    if (!preg_match('/^data:(image\/(?:jpeg|png|gif));base64,(.+)$/', $imageData, $matches)) {
        http_response_code(400);
        echo json_encode(['error' => 'Invalid image data']);
        exit;
    }

    $mimeType  = $matches[1];
    $imageBody = base64_decode($matches[2]);

    if (strlen($imageBody) > $maxSize) {
        http_response_code(400);
        echo json_encode(['error' => 'Изображение слишком большое. Максимум 2MB.']);
        exit;
    }

    $ext      = $mimeType === 'image/png' ? 'png' : ($mimeType === 'image/gif' ? 'gif' : 'jpg');
    $filename = bin2hex(random_bytes(16)) . '.' . $ext;
    $destDir  = __DIR__ . '/../../public/uploads/tasks/';
    $destPath = $destDir . $filename;

    file_put_contents($destPath, $imageBody);

    $stmt = db()->prepare('
        INSERT INTO task_files (task_id, user_id, filename, original_name, file_size, mime_type)
        VALUES (?, ?, ?, ?, ?, ?)
    ');
    $stmt->execute([
        $taskId,
        $_SESSION['user_id'],
        $filename,
        'screenshot_' . date('Y-m-d_H-i-s') . '.' . $ext,
        strlen($imageBody),
        $mimeType,
    ]);

    $fileId = db()->lastInsertId();

    echo json_encode([
        'id'  => $fileId,
        'url' => '/public/uploads/tasks/' . $filename,
    ]);
    exit;
}

http_response_code(400);
echo json_encode(['error' => 'No file provided']);