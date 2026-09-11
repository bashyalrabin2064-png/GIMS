<?php
session_start();

if (empty($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Not logged in']);
    exit;
}

header('Content-Type: application/json');

$productId = (int)($_GET['id'] ?? 0);

if ($productId < 1) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid product ID']);
    exit;
}

require_once '../php/db.php';
$pdo = get_db_connection();

$stmt = $pdo->prepare("SELECT id, name, quantity, unit, cost_price, sell_price FROM products WHERE id = ?");
$stmt->execute([$productId]);
$product = $stmt->fetch();

if (!$product) {
    http_response_code(404);
    echo json_encode(['error' => 'Product not found']);
    exit;
}

$unit = $product['unit'] ?? 'pcs';
echo json_encode([
    'id'              => (int)$product['id'],
    'name'            => $product['name'],
    'quantity'        => (int)$product['quantity'],
    'unit'            => $unit,
    'cost_price'      => (float)$product['cost_price'],
    'sell_price'      => (float)$product['sell_price'],
    'formatted_stock' => format_quantity($product['quantity'], $unit)
]);

