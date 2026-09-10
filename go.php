<?php
require_once __DIR__ . '/includes/functions.php';

$id = isset($_GET['id']) ? (int) $_GET['id'] : 0;
$link = $id ? get_link($id) : null;

if (!$link || $link['deleted_at'] !== null || !$link['visible']) {
    http_response_code(404);
    exit('Link not found.');
}

if (!is_valid_url($link['url'])) {
    http_response_code(400);
    exit('This link is not set up correctly.');
}

record_click($id);
header('Location: ' . $link['url'], true, 302);
exit;
