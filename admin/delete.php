<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/csrf.php';
require_login();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('dashboard.php');
}
csrf_verify();

$id = (int) ($_POST['id'] ?? 0);
if ($id) {
    soft_delete_link($id);
    $_SESSION['flash'] = 'Link moved to trash. It will be kept for ' . TRASH_RETENTION_DAYS . ' days.';
}
redirect('dashboard.php');
