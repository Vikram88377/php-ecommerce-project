<?php

require_once "../config/database.php";
require_once "../includes/security.php";

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: cart.php");
    exit;
}

if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) {
    die("Invalid CSRF token.");
}

$productId = (int) ($_POST['product_id'] ?? 0);
$quantity = (int) ($_POST['quantity'] ?? 0);

if ($productId <= 0 || $quantity <= 0) {
    header("Location: cart.php");
    exit;
}

/*
|--------------------------------------------------------------------------
| Check Product
|--------------------------------------------------------------------------
*/

$stmt = $pdo->prepare("
    SELECT id, stock
    FROM products
    WHERE id = ?
    AND status = 'active'
");

$stmt->execute([$productId]);

$product = $stmt->fetch();

if (!$product) {
    header("Location: cart.php");
    exit;
}

/*
|--------------------------------------------------------------------------
| Check Stock
|--------------------------------------------------------------------------
*/

if ($quantity > $product['stock']) {
    $quantity = $product['stock'];
}

/*
|--------------------------------------------------------------------------
| Update Cart
|--------------------------------------------------------------------------
*/

if ($quantity > 0) {

    $_SESSION['cart'][$productId] = $quantity;

} else {

    unset($_SESSION['cart'][$productId]);

}

header("Location: cart.php");
exit;