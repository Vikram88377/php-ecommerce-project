<?php

require_once "../config/database.php";
require_once "../includes/security.php";

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: products.php");
    exit;
}

 if (!verifyCsrfToken($_POST['csrf_token'] ?? '')){
    die("Invalid CSRF token.");
}

$productId = (int) ($_POST['product_id'] ?? 0);
$quantity = (int) ($_POST['quantity'] ?? 1);

if ($productId <= 0 || $quantity <= 0) {
    header("Location: products.php");
    exit;
}

$stmt = $pdo->prepare("
    SELECT id, name, price, sale_price, stock
    FROM products
    WHERE id = ? AND status = 'active'
");

$stmt->execute([$productId]);

$product = $stmt->fetch();

if (!$product) {
    die("Product not found.");
}

if ($quantity > $product['stock']) {
    die("Requested quantity is not available.");
}

if (!isset($_SESSION['cart'])) {
    $_SESSION['cart'] = [];
}

if (isset($_SESSION['cart'][$productId])) {

    $_SESSION['cart'][$productId] += $quantity;

} else {

    $_SESSION['cart'][$productId] = $quantity;
}

header("Location: cart.php");
exit;