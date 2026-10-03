<?php

require_once "../config/database.php";
require_once "../includes/security.php";

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}


/*
|--------------------------------------------------------------------------
| Authentication
|--------------------------------------------------------------------------
*/

$userId = $_SESSION['user_id'] ?? 0;

if ($userId <= 0) {
    header("Location: login.php");
    exit;
}


/*
|--------------------------------------------------------------------------
| Only POST
|--------------------------------------------------------------------------
*/

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: products.php");
    exit;
}


/*
|--------------------------------------------------------------------------
| CSRF
|--------------------------------------------------------------------------
*/

if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) {
    die("Invalid CSRF token.");
}


/*
|--------------------------------------------------------------------------
| Get Data
|--------------------------------------------------------------------------
*/

$productId = (int) ($_POST['product_id'] ?? 0);

$orderId = (int) ($_POST['order_id'] ?? 0);

$rating = (int) ($_POST['rating'] ?? 0);

$comment = trim($_POST['comment'] ?? '');


/*
|--------------------------------------------------------------------------
| Basic Validation
|--------------------------------------------------------------------------
*/

if ($productId <= 0 || $orderId <= 0) {
    die("Invalid review request.");
}

if ($rating < 1 || $rating > 5) {
    die("Rating must be between 1 and 5.");
}

if ($comment === '') {
    die("Please write a review.");
}

if (strlen($comment) > 1000) {
    die("Review cannot exceed 1000 characters.");
}


/*
|--------------------------------------------------------------------------
| Check Order
|--------------------------------------------------------------------------
|
| User must have purchased this product.
|
*/

$orderStmt = $pdo->prepare("
    SELECT o.id
    FROM orders o
    INNER JOIN order_items oi
        ON oi.order_id = o.id
    WHERE o.id = ?
    AND o.user_id = ?
    AND oi.product_id = ?
    AND o.status = 'delivered'
    LIMIT 1
");

$orderStmt->execute([
    $orderId,
    $userId,
    $productId
]);

$purchased = $orderStmt->fetch();


if (!$purchased) {
    die(
        "You can review this product only after purchasing and receiving it."
    );
}


/*
|--------------------------------------------------------------------------
| Check Existing Review
|--------------------------------------------------------------------------
*/

$reviewStmt = $pdo->prepare("
    SELECT id
    FROM reviews
    WHERE user_id = ?
    AND product_id = ?
    LIMIT 1
");

$reviewStmt->execute([
    $userId,
    $productId
]);

if ($reviewStmt->fetch()) {
    die("You have already reviewed this product.");
}


/*
|--------------------------------------------------------------------------
| Insert Review
|--------------------------------------------------------------------------
*/

$insertStmt = $pdo->prepare("
    INSERT INTO reviews (
        user_id,
        product_id,
        order_id,
        rating,
        comment,
        status,
        created_at,
        updated_at
    )
    VALUES (
        ?,
        ?,
        ?,
        ?,
        ?,
        'pending',
        NOW(),
        NOW()
    )
");

$insertStmt->execute([
    $userId,
    $productId,
    $orderId,
    $rating,
    $comment
]);


/*
|--------------------------------------------------------------------------
| Success
|--------------------------------------------------------------------------
*/

header(
    "Location: product.php?id="
    . $productId
    . "&review=success"
);

exit;