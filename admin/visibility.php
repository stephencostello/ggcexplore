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

// Toggled from the entry's own editor page? Go back there instead of the
// list, so staff can keep editing without losing their place.
$returnId = (int) ($_POST['return_id'] ?? 0);
redirect($returnId ? 'link-form.php?id=' . $returnId : 'dashboard.php');
