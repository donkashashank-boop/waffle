<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/auth.php';
$action   = $_POST['action']   ?? '';
$redirect = $_POST['redirect'] ?? SITE_URL . '/pages/cart.php';
$sid      = getCartSessionId();

if ($action === 'add') {
    $food_id = (int)($_POST['food_id'] ?? 0);
    if ($food_id) {
        $existing = fetchOne("SELECT id, quantity FROM cart WHERE session_id=? AND food_id=?", 'si', $sid, $food_id);
        if ($existing) {
            query("UPDATE cart SET quantity=quantity+1 WHERE id=?", 'i', $existing['id']);
        } else {
            query("INSERT INTO cart (session_id, food_id, quantity) VALUES (?,?,1)", 'si', $sid, $food_id);
        }
    }
    header("Location: $redirect"); exit;
}

if ($action === 'update') {
    $cart_id  = (int)($_POST['cart_id']  ?? 0);
    $quantity = (int)($_POST['quantity'] ?? 1);
    $delta    = (int)($_POST['delta']    ?? 0);
    if ($delta) { $row = fetchOne("SELECT quantity FROM cart WHERE id=?", 'i', $cart_id); $quantity = max(1, ($row['quantity'] ?? 1) + $delta); }
    query("UPDATE cart SET quantity=? WHERE id=? AND session_id=?", 'iis', $quantity, $cart_id, $sid);
    header("Location: " . SITE_URL . "/pages/cart.php"); exit;
}

if ($action === 'remove') {
    $cart_id = (int)($_POST['cart_id'] ?? 0);
    query("DELETE FROM cart WHERE id=? AND session_id=?", 'is', $cart_id, $sid);
    header("Location: " . SITE_URL . "/pages/cart.php"); exit;
}
