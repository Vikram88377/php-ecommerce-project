<?php

require_once "../config/database.php";
require_once "../includes/security.php";
require_once "../includes/coupon.php";

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$userId = $_SESSION['user_id'] ?? 0;

if ($userId <= 0) {
    header("Location: login.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: checkout.php");
    exit;
}

if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) {
    die("Invalid CSRF token.");
}

$code = strtoupper(trim($_POST['coupon_code'] ?? ''));

$cart = $_SESSION['cart'] ?? [];

if (empty($cart)) {
    header("Location: cart.php");
    exit;
}

/*
|--------------------------------------------------------------------------
| Calculate Current Cart Subtotal
|--------------------------------------------------------------------------
*/

$productIds = array_keys($cart);

$placeholders = implode(',', array_fill(0, count($productIds), '?'));

$stmt = $pdo->prepare("
    SELECT id, price, sale_price, stock, status
    FROM products
    WHERE id IN ($placeholders)
");

$stmt->execute($productIds);

$products = $stmt->fetchAll();

$subtotal = 0;

foreach ($products as $product) {

    $productId = $product['id'];

    if (!isset($cart[$productId])) {
        continue;
    }

    $price = $product['sale_price'] !== null
        ? (float) $product['sale_price']
        : (float) $product['price'];

    $quantity = (int) $cart[$productId];

    $subtotal += $price * $quantity;
}

/*
|--------------------------------------------------------------------------
| Validate Coupon
|--------------------------------------------------------------------------
*/

$result = getValidCoupon($pdo, $code, $subtotal);

if (!$result['success']) {

    header(
        "Location: checkout.php?coupon_error="
        . urlencode($result['message'])
    );

    exit;
}

/*
|--------------------------------------------------------------------------
| Store Coupon In Session
|--------------------------------------------------------------------------
*/

$_SESSION['coupon'] = [
    'id' => (int) $result['coupon']['id'],
    'code' => $result['coupon']['code']
];

header("Location: checkout.php?coupon=applied");
exit;