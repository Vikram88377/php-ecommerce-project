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

if ($productId <= 0) {
    header("Location: cart.php");
    exit;
}

/*
|--------------------------------------------------------------------------
| Remove Product
|--------------------------------------------------------------------------
*/

if (isset($_SESSION['cart'][$productId])) {

    unset($_SESSION['cart'][$productId]);

}

header("Location: cart.php");
exit;