<?php
/**
 * includes/functions.php
 * Shared helpers used by both the public page and the admin panel.
 */

require_once __DIR__ . '/db.php';
require_once __DIR__ . '/social.php';

// --- Settings ----------------------------------------------------------

function get_settings(): array
{
    $row = db()->query('SELECT * FROM settings WHERE id = 1')->fetch();
    return $row ?: [];
}

function update_settings(array $fields): void
{
    $allowed = [
        'church_name', 'logo_filename', 'intro_text', 'copyright_text',
        'admin_password_hash', 'setup_complete',
        'social_whatsapp', 'social_instagram', 'social_facebook',
        'social_spotify', 'social_apple_music', 'social_youtube',
    ];
    $set = [];
    $params = [];
    foreach ($fields as $key => $value) {
        if (!in_array($key, $allowed, true)) {
            continue;
        }
        $set[] = "$key = :$key";
        $params[":$key"] = $value;
    }
    if (!$set) {
        return;
    }
    $sql = 'UPDATE settings SET ' . implode(', ', $set) . ' WHERE id = 1';
    $stmt = db()->prepare($sql);
    $stmt->execute($params);
}

// --- Links: reads ------------------------------------------------------

/** Visible, non-deleted links for the public page, in manual sort order. */
function get_public_links(): array
{
    return db()->query("
        SELECT * FROM links
        WHERE deleted_at IS NULL AND visible = 1
        ORDER BY sort_order ASC, id ASC
    ")->fetchAll();
}

/** All non-deleted links for the admin dashboard (visible + hidden). */
function get_admin_links(): array
{
    return db()->query("
        SELECT * FROM links
        WHERE deleted_at IS NULL
        ORDER BY sort_order ASC, id ASC
    ")->fetchAll();
}

/** Soft-deleted links, most recently deleted first. */
function get_trashed_links(): array
{
    return db()->query("
        SELECT * FROM links
        WHERE deleted_at IS NOT NULL
        ORDER BY deleted_at DESC
    ")->fetchAll();
}

function get_link(int $id): ?array
{
    $stmt = db()->prepare('SELECT * FROM links WHERE id = :id');
    $stmt->execute([':id' => $id]);
    $row = $stmt->fetch();
    return $row ?: null;
}

// --- Links: writes -------------------------------------------------------

function create_link(array $data): int
{
    $pdo = db();
    $nextOrder = (int) $pdo->query('SELECT COALESCE(MAX(sort_order), 0) + 1 m FROM links')->fetch()['m'];

    $stmt = $pdo->prepare("
        INSERT INTO links (name, url, image_filename, description, visible, sort_order, created_at, updated_at)
        VALUES (:name, :url, :image_filename, :description, :visible, :sort_order, datetime('now'), datetime('now'))
    ");
    $stmt->execute([
        ':name' => $data['name'],
        ':url' => $data['url'],
        ':image_filename' => $data['image_filename'] ?? null,
        ':description' => $data['description'] ?? null,
        ':visible' => !empty($data['visible']) ? 1 : 0,
        ':sort_order' => $nextOrder,
    ]);
    return (int) $pdo->lastInsertId();
}

function update_link(int $id, array $data): void
{
    $stmt = db()->prepare("
        UPDATE links SET
            name = :name,
            url = :url,
            image_filename = :image_filename,
            description = :description,
            visible = :visible,
            updated_at = datetime('now')
        WHERE id = :id
    ");
    $stmt->execute([
        ':id' => $id,
        ':name' => $data['name'],
        ':url' => $data['url'],
        ':image_filename' => $data['image_filename'] ?? null,
        ':description' => $data['description'] ?? null,
        ':visible' => !empty($data['visible']) ? 1 : 0,
    ]);
}

function set_link_visibility(int $id, bool $visible): void
{
    $stmt = db()->prepare('UPDATE links SET visible = :v, updated_at = datetime(\'now\') WHERE id = :id');
    $stmt->execute([':v' => $visible ? 1 : 0, ':id' => $id]);
}

function soft_delete_link(int $id): void
{
    $stmt = db()->prepare("UPDATE links SET deleted_at = datetime('now') WHERE id = :id");
    $stmt->execute([':id' => $id]);
}

function restore_link(int $id): void
{
    $pdo = db();
    $nextOrder = (int) $pdo->query('SELECT COALESCE(MAX(sort_order), 0) + 1 m FROM links')->fetch()['m'];
    $stmt = $pdo->prepare('UPDATE links SET deleted_at = NULL, sort_order = :o WHERE id = :id');
    $stmt->execute([':o' => $nextOrder, ':id' => $id]);
}

function permanently_delete_link(int $id): void
{
    $link = get_link($id);
    if ($link && $link['image_filename']) {
        $path = UPLOADS_LINKS_DIR . '/' . $link['image_filename'];
        if (is_file($path)) {
            @unlink($path);
        }
    }
    $stmt = db()->prepare('DELETE FROM links WHERE id = :id');
    $stmt->execute([':id' => $id]);
}

/** Permanently remove anything that's been in the trash longer than the retention window. */
function prune_trash(): void
{
    $stmt = db()->prepare("
        SELECT id FROM links
        WHERE deleted_at IS NOT NULL
        AND deleted_at <= datetime('now', :window)
    ");
    $stmt->execute([':window' => '-' . TRASH_RETENTION_DAYS . ' days']);
    foreach ($stmt->fetchAll() as $row) {
        permanently_delete_link((int) $row['id']);
    }
}

function move_link(int $id, string $direction): void
{
    $pdo = db();
    $links = get_admin_links(); // ordered by sort_order
    $index = null;
    foreach ($links as $i => $l) {
        if ((int) $l['id'] === $id) {
            $index = $i;
            break;
        }
    }
    if ($index === null) {
        return;
    }
    $swapWith = $direction === 'up' ? $index - 1 : $index + 1;
    if ($swapWith < 0 || $swapWith >= count($links)) {
        return; // already at the edge
    }

    $a = $links[$index];
    $b = $links[$swapWith];

    $pdo->beginTransaction();
    $stmt = $pdo->prepare('UPDATE links SET sort_order = :o WHERE id = :id');
    $stmt->execute([':o' => $b['sort_order'], ':id' => $a['id']]);
    $stmt->execute([':o' => $a['sort_order'], ':id' => $b['id']]);
    $pdo->commit();
}

function record_click(int $id): void
{
    $stmt = db()->prepare('UPDATE links SET clicks = clicks + 1 WHERE id = :id');
    $stmt->execute([':id' => $id]);
}

// --- Images ----------------------------------------------------------------

/**
 * Validate and store an uploaded image. Returns the stored filename, or null
 * if no file was uploaded. Throws RuntimeException on validation failure.
 */
function handle_image_upload(array $file, string $targetDir): ?string
{
    if (!isset($file['error']) || $file['error'] === UPLOAD_ERR_NO_FILE) {
        return null;
    }
    if ($file['error'] !== UPLOAD_ERR_OK) {
        throw new RuntimeException('Image upload failed. Please try again.');
    }
    if ($file['size'] > MAX_UPLOAD_BYTES) {
        throw new RuntimeException('Image is too large — please keep it under 2MB.');
    }

    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mime = finfo_file($finfo, $file['tmp_name']);
    finfo_close($finfo);

    if (!isset(ALLOWED_IMAGE_TYPES[$mime])) {
        throw new RuntimeException('Please upload a JPG, PNG, or WebP image.');
    }

    $ext = ALLOWED_IMAGE_TYPES[$mime];
    $filename = bin2hex(random_bytes(8)) . '.' . $ext;

    if (!is_dir($targetDir)) {
        mkdir($targetDir, 0755, true);
    }

    if (!move_uploaded_file($file['tmp_name'], $targetDir . '/' . $filename)) {
        throw new RuntimeException('Could not save the uploaded image.');
    }

    return $filename;
}

function delete_image_if_exists(?string $filename, string $dir): void
{
    if (!$filename) {
        return;
    }
    $path = $dir . '/' . $filename;
    if (is_file($path)) {
        @unlink($path);
    }
}

// --- Small utilities ---------------------------------------------------

function h(?string $value): string
{
    return htmlspecialchars($value ?? '', ENT_QUOTES, 'UTF-8');
}

function redirect(string $path): void
{
    header('Location: ' . $path);
    exit;
}

function is_valid_url(string $url): bool
{
    return (bool) filter_var($url, FILTER_VALIDATE_URL);
}
