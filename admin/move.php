<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/csrf.php';
require_login();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('dashboard.php');
}
csrf_verify();

$id = (int) ($_POST['id'] ?? 0);
$direction = $_POST['direction'] ?? '';
if ($id && in_array($direction, ['up', 'down'], true)) {
    move_link($id, $direction);
}
redirect('dashboard.php');
