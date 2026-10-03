<?php

require_once "../config/database.php";
require_once "../includes/security.php";
require_once "../services/PaymentService.php";

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
| GET / POST Support
|--------------------------------------------------------------------------
|
| GET  -> First Razorpay payment after order creation
| POST -> Retry payment from payment-failed.php
|
*/

if (!in_array($_SERVER['REQUEST_METHOD'], ['GET', 'POST'], true)) {
    header("Location: orders.php");
    exit;
}


/*
|--------------------------------------------------------------------------
| CSRF
|--------------------------------------------------------------------------
|
| Required only for POST retry request.
|
*/

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) {
        die("Invalid CSRF token.");
    }
}


/*
|--------------------------------------------------------------------------
| Get Order ID
|--------------------------------------------------------------------------
*/

$orderId = (int) (
    $_POST['order_id']
    ?? $_GET['order_id']
    ?? 0
);

if ($orderId <= 0) {
    header("Location: orders.php");
    exit;
}


/*
|--------------------------------------------------------------------------
| Get Order
|--------------------------------------------------------------------------
*/

$orderStmt = $pdo->prepare("
    SELECT
        id,
        order_number,
        total_amount,
        status,
        payment_status
    FROM orders
    WHERE id = ?
    AND user_id = ?
    LIMIT 1
");

$orderStmt->execute([
    $orderId,
    $userId
]);

$order = $orderStmt->fetch();

if (!$order) {
    die("Order not found.");
}


/*
|--------------------------------------------------------------------------
| Already Paid
|--------------------------------------------------------------------------
*/

if ($order['payment_status'] === 'paid') {

    header(
        "Location: payment-success.php?order_id="
        . $orderId
        . "&success=1"
    );

    exit;
}


/*
|--------------------------------------------------------------------------
| Cancelled Order
|--------------------------------------------------------------------------
*/

if ($order['status'] === 'cancelled') {
    die("This order has been cancelled.");
}


/*
|--------------------------------------------------------------------------
| Get Amount From Database
|--------------------------------------------------------------------------
|
| Never trust amount from browser.
|
*/

$amount = (float) $order['total_amount'];

if ($amount <= 0) {
    die("Invalid order amount.");
}


/*
|--------------------------------------------------------------------------
| Create New Razorpay Order
|--------------------------------------------------------------------------
|
| Every payment attempt gets a fresh Razorpay order.
| This is important for retry functionality.
|
*/

$paymentService = new PaymentService();

try {

    $razorpayOrder = $paymentService->createOrder(
        $order['order_number'],
        $amount,
        'INR'
    );

} catch (Throwable $e) {

    die(
        "Unable to create Razorpay order: "
        . e($e->getMessage())
    );
}


/*
|--------------------------------------------------------------------------
| Validate Razorpay Response
|--------------------------------------------------------------------------
*/

if (
    empty($razorpayOrder['id'])
    || !isset($razorpayOrder['amount'])
) {
    die("Invalid response from Razorpay.");
}


/*
|--------------------------------------------------------------------------
| Verify Amount
|--------------------------------------------------------------------------
*/

$razorpayAmount = ((int) $razorpayOrder['amount']) / 100;

if (abs($razorpayAmount - $amount) > 0.01) {
    die("Razorpay amount mismatch.");
}


/*
|--------------------------------------------------------------------------
| Save Payment
|--------------------------------------------------------------------------
*/

try {

    $paymentStmt = $pdo->prepare("
        INSERT INTO payments (
            order_id,
            gateway,
            razorpay_order_id,
            amount,
            status,
            created_at,
            updated_at
        )
        VALUES (
            ?,
            'razorpay',
            ?,
            ?,
            'created',
            NOW(),
            NOW()
        )
    ");

    $paymentStmt->execute([
        $orderId,
        $razorpayOrder['id'],
        $amount
    ]);

} catch (PDOException $e) {

    die(
        "Unable to save payment record: "
        . e($e->getMessage())
    );
}


/*
|--------------------------------------------------------------------------
| Update Order Payment Status
|--------------------------------------------------------------------------
*/

$updateStmt = $pdo->prepare("
    UPDATE orders
    SET
        payment_status = 'pending',
        updated_at = NOW()
    WHERE id = ?
");

$updateStmt->execute([
    $orderId
]);


/*
|--------------------------------------------------------------------------
| Redirect To Payment Page
|--------------------------------------------------------------------------
*/

header(
    "Location: payment.php?order_id="
    . $orderId
);

exit;