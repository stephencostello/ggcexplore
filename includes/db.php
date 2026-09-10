<?php
/**
 * includes/db.php
 * PDO SQLite connection. Creates the database file and schema automatically
 * the first time it's needed — no separate install/migration step required.
 */

require_once __DIR__ . '/../config.php';

function db(): PDO
{
    static $pdo = null;
    if ($pdo !== null) {
        return $pdo;
    }

    $isNew = !file_exists(DB_PATH);

    if (!is_dir(DATA_DIR)) {
        mkdir(DATA_DIR, 0755, true);
    }

    $pdo = new PDO('sqlite:' . DB_PATH);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
    $pdo->exec('PRAGMA foreign_keys = ON');

    if ($isNew) {
        migrate($pdo);
    }

    return $pdo;
}

function migrate(PDO $pdo): void
{
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS settings (
            id INTEGER PRIMARY KEY CHECK (id = 1),
            church_name TEXT NOT NULL DEFAULT 'Grace Generation Church',
            logo_filename TEXT,
            intro_text TEXT NOT NULL DEFAULT '',
            copyright_text TEXT NOT NULL DEFAULT '',
            admin_password_hash TEXT,
            setup_complete INTEGER NOT NULL DEFAULT 0
        )
    ");

    $pdo->exec("
        CREATE TABLE IF NOT EXISTS links (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            name TEXT NOT NULL,
            url TEXT NOT NULL,
            image_filename TEXT,
            description TEXT,
            pinned INTEGER NOT NULL DEFAULT 0,
            visible INTEGER NOT NULL DEFAULT 1,
            sort_order INTEGER NOT NULL DEFAULT 0,
            clicks INTEGER NOT NULL DEFAULT 0,
            deleted_at TEXT,
            created_at TEXT NOT NULL DEFAULT (datetime('now')),
            updated_at TEXT NOT NULL DEFAULT (datetime('now'))
        )
    ");

    // Seed the single settings row if it doesn't exist yet.
    $count = $pdo->query('SELECT COUNT(*) c FROM settings WHERE id = 1')->fetch()['c'];
    if ($count == 0) {
        $pdo->exec("INSERT INTO settings (id, setup_complete) VALUES (1, 0)");
    }
}
