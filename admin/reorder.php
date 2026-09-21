<?php
/**
 * admin/reorder.php
 * Drag-and-drop reorder endpoint, called via fetch() from dashboard.php's
 * inline script (not a regular form submit — see the JS at the bottom of
 * that file). Takes the full new order of link ids and persists it.
 */
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/csrf.php';
require_login();

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['ok' => false, 'error' => 'Method not allowed']);
    exit;
}
csrf_verify();

$order = $_POST['order'] ?? [];
if (!is_array($order) || !$order) {
    http_response_code(400);
    echo json_encode(['ok' => false, 'error' => 'Missing order']);
    exit;
}

reorder_links(array_map('intval', $order));
echo json_encode(['ok' => true]);
