<?php
/**
 * config.php
 * Central configuration. Edit these values for your environment if needed —
 * everything else in the app reads from here.
 */

// --- Paths -----------------------------------------------------------------
define('BASE_DIR', __DIR__);
define('DATA_DIR', BASE_DIR . '/data');
// Normally the one database under /data. Set GRACELINKS_DB_PATH to point a
// throwaway/preview instance at a different file without touching real data.
define('DB_PATH', getenv('GRACELINKS_DB_PATH') ?: DATA_DIR . '/church.sqlite');
define('UPLOADS_DIR', BASE_DIR . '/uploads');
define('UPLOADS_LINKS_DIR', UPLOADS_DIR . '/links');
define('UPLOADS_LOGO_DIR', UPLOADS_DIR . '/logo');

// Public URL path to the uploads folder (relative to site root).
// Change this if you deploy the app into a subfolder rather than the
// subdomain's document root.
define('UPLOADS_URL', '/uploads');

// --- Session / auth ----------------------------------------------------
// Admins are logged out after this many seconds of inactivity.
define('SESSION_TIMEOUT_SECONDS', 2 * 60 * 60); // 2 hours

// --- Uploads -------------------------------------------------------------
define('MAX_UPLOAD_BYTES', 2 * 1024 * 1024); // 2MB
define('ALLOWED_IMAGE_TYPES', ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp']);

// --- Trash -----------------------------------------------------------------
define('TRASH_RETENTION_DAYS', 7);

// --- Misc --------------------------------------------------------------
date_default_timezone_set('Europe/London');

error_reporting(E_ALL);
ini_set('display_errors', '0'); // keep errors out of the public page; check your host's error log if something breaks

session_name('gracelinks_admin');
if (session_status() === PHP_SESSION_NONE) {
    session_start([
        'cookie_httponly' => true,
        'cookie_samesite' => 'Lax',
        // 'cookie_secure' => true, // uncomment once confirmed running on https
    ]);
}
