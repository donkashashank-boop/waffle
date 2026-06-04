<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/auth.php';
header('Content-Type: application/json');
if (!isAdmin()) { echo json_encode(['success'=>false,'message'=>'Unauthorized']); exit; }
$data   = json_decode(file_get_contents('php://input'), true);
$action = $data['action'] ?? '';
if ($action === 'delete_food') {
    query("DELETE FROM food_items WHERE id=?", 'i', (int)$data['id']);
    echo json_encode(['success'=>true]);
} elseif ($action === 'toggle_avail') {
    $new = $data['current'] ? 0 : 1;
    query("UPDATE food_items SET is_available=? WHERE id=?", 'ii', $new, (int)$data['id']);
    echo json_encode(['success'=>true]);
} else { echo json_encode(['success'=>false,'message'=>'Unknown action']); }
