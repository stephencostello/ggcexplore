<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/csrf.php';
require_login();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('dashboard.php');
}
csrf_verify();

$id = (int) ($_POST['id'] ?? 0);
$visible = !empty($_POST['visible']);
if ($id) {
    set_link_visibility($id, $visible);
}
redirect('dashboard.php');
