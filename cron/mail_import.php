<?php

/**
 * Импорт задач из почты (Exchange IMAP)
 * Запускается по cron каждые 5 минут
 */

require_once __DIR__ . '/../config.php';
// Читаем .env напрямую
$env = parse_ini_file(__DIR__ . '/../.env');

// ─── Настройки почты (добавить в .env) ───────────────────────────────────────
$mailHost     = $env['MAIL_HOST']     ?? '';
$mailPort     = $env['MAIL_PORT']     ?? '993';
$mailUser     = $env['MAIL_USER']     ?? '';
$mailPass     = $env['MAIL_PASS']     ?? '';
$mailFolder   = $env['MAIL_FOLDER']   ?? 'INBOX';
$mailProject  = $env['MAIL_PROJECT_ID'] ?? 1; // ID проекта по умолчанию

// ─── Подключение к IMAP ───────────────────────────────────────────────────────
$mailbox = '{' . $mailHost . ':' . $mailPort . '/imap/ssl/novalidate-cert}' . $mailFolder;

$imap = imap_open($mailbox, $mailUser, $mailPass);

if (!$imap) {
    error_log('[MAIL IMPORT] Не удалось подключиться к почте: ' . imap_last_error());
    exit(1);
}

// Берём только непрочитанные письма
$emails = imap_search($imap, 'UNSEEN');

if (!$emails) {
    imap_close($imap);
    exit(0);
}

$created = 0;

foreach ($emails as $msgId) {
    $header  = imap_headerinfo($imap, $msgId);
    $subject = isset($header->subject) ? imap_utf8($header->subject) : '(без темы)';
    $from    = isset($header->from[0]) ? imap_utf8($header->from[0]->mailbox . '@' . $header->from[0]->host) : '';
    $fromName = isset($header->from[0]->personal) ? imap_utf8($header->from[0]->personal) : $from;

    // Получаем тело письма
    $body = getEmailBody($imap, $msgId);

    // Очищаем тело от HTML если нужно
    $bodyClean = sanitizeHtml(trim($body));
    $attachments = getEmailAttachments($imap, $msgId);
    $savedFiles = [];

    foreach ($attachments as $attachment) {
        $ext      = pathinfo($attachment['filename'], PATHINFO_EXTENSION);
        $filename = bin2hex(random_bytes(16)) . '.' . $ext;
        $destDir  = __DIR__ . '/../public/uploads/tasks/';
        $destPath = $destDir . $filename;

        file_put_contents($destPath, $attachment['data']);

        $savedFiles[] = [
            'filename'      => $filename,
            'original_name' => $attachment['filename'],
            'size'          => strlen($attachment['data']),
            'mime'          => $attachment['mime'],
        ];
    }
    // Создаём задачу
    try {
        $stmt = db()->prepare('
            INSERT INTO tasks
                (title, description, project_id, status, priority, created_by, customer)
            VALUES (?, ?, ?, "incoming", "medium", 1, ?)
        ');
        $stmt->execute([
            mb_substr('Письмо - ' . $subject, 0, 500),
            $bodyClean,
            $mailProject,
            mb_substr($fromName ?: $from, 0, 255),
        ]);

        $taskId = db()->lastInsertId();

        // Логируем создание
        $logStmt = db()->prepare('
            INSERT INTO history (task_id, user_id, action, old_value, new_value)
            VALUES (?, 1, "Задача создана из письма", "", ?)
        ');
        $logStmt->execute([$taskId, $from]);

        // Помечаем письмо как прочитанное
        imap_setflag_full($imap, (string)$msgId, '\\Seen');

        $created++;
        error_log('[MAIL IMPORT] Создана задача #' . $taskId . ' из письма от ' . $from);

    } catch (Exception $e) {
        error_log('[MAIL IMPORT] Ошибка создания задачи: ' . $e->getMessage());
    }
}

imap_close($imap);
error_log('[MAIL IMPORT] Готово. Создано задач: ' . $created);

// ─── Вспомогательная функция ──────────────────────────────────────────────────

function getEmailBody($imap, int $msgId): string {
    $structure = imap_fetchstructure($imap, $msgId);

    // Простое письмо без вложений
    if (!isset($structure->parts)) {
        $body = imap_fetchbody($imap, $msgId, '1');
        return decodeEmailBody($body, $structure->encoding);
    }

    // Multipart — ищем text/plain или text/html
    foreach ($structure->parts as $i => $part) {
        $subtype = strtolower($part->subtype ?? '');
        if ($subtype === 'plain') {
            $body = imap_fetchbody($imap, $msgId, (string)($i + 1));
            return decodeEmailBody($body, $part->encoding);
        }
    }

    // Если plain не нашли — берём html
    foreach ($structure->parts as $i => $part) {
        $subtype = strtolower($part->subtype ?? '');
        if ($subtype === 'html') {
            $body = imap_fetchbody($imap, $msgId, (string)($i + 1));
            return decodeEmailBody($body, $part->encoding);
        }
    }

    return '';
}

function decodeEmailBody(string $body, int $encoding): string {
    switch ($encoding) {
        case 3: return base64_decode($body);           // BASE64
        case 4: return quoted_printable_decode($body); // QP
        default: return $body;
    }
    
}
function getEmailAttachments($imap, int $msgId): array {
    $attachments = [];
    $structure = imap_fetchstructure($imap, $msgId);

    if (!isset($structure->parts)) {
        return $attachments;
    }

    foreach ($structure->parts as $i => $part) {
        $disposition = strtolower($part->disposition ?? '');
        $subtype = strtolower($part->subtype ?? '');

        if ($disposition !== 'attachment' && $disposition !== 'inline') {
            continue;
        }

        // Имя файла
        $filename = '';
        if (!empty($part->dparameters)) {
            foreach ($part->dparameters as $param) {
                if (strtolower($param->attribute) === 'filename') {
                    $filename = imap_utf8($param->value);
                }
            }
        }
        if (!$filename && !empty($part->parameters)) {
            foreach ($part->parameters as $param) {
                if (strtolower($param->attribute) === 'name') {
                    $filename = imap_utf8($param->value);
                }
            }
        }
        if (!$filename) continue;

        $data = imap_fetchbody($imap, $msgId, (string)($i + 1));
        $data = decodeEmailBody($data, $part->encoding);

        $attachments[] = [
            'filename' => $filename,
            'data'     => $data,
            'mime'     => strtolower($part->type . '/' . $subtype),
        ];
    }

    return $attachments;
}