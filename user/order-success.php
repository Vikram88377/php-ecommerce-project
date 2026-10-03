<?php

require_once "../config/database.php";
require_once "../includes/security.php";

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$orderId = (int) ($_GET['order_id'] ?? 0);

if ($orderId <= 0) {
    header("Location: products.php");
    exit;
}


/*
|--------------------------------------------------------------------------
| User
|--------------------------------------------------------------------------
*/

$userId = $_SESSION['user_id'] ?? 1;


/*
|--------------------------------------------------------------------------
| Get Order
|--------------------------------------------------------------------------
*/

$stmt = $pdo->prepare("
    SELECT
        id,
        order_number,
        subtotal,
        discount,
        shipping_amount,
        total_amount,
        status,
        payment_status,
        created_at
    FROM orders
    WHERE id = ?
    AND user_id = ?
    LIMIT 1
");

$stmt->execute([
    $orderId,
    $userId
]);

$order = $stmt->fetch();

if (!$order) {
    header("Location: products.php");
    exit;
}


/*
|--------------------------------------------------------------------------
| Get Order Items
|--------------------------------------------------------------------------
*/

$itemStmt = $pdo->prepare("
    SELECT
        product_name,
        product_sku,
        price,
        quantity,
        total
    FROM order_items
    WHERE order_id = ?
    ORDER BY id ASC
");

$itemStmt->execute([$orderId]);

$orderItems = $itemStmt->fetchAll();

?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1"
    >

    <title>Order Successful</title>

    <link
        href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
        rel="stylesheet"
    >

</head>

<body>

<div class="container py-5">

    <!-- Success Message -->

    <div class="text-center mb-5">

        <div
            class="mx-auto mb-3 d-flex align-items-center justify-content-center bg-success text-white rounded-circle"
            style="width: 70px; height: 70px;"
        >
            ✓
        </div>

        <h1 class="fw-bold">
            Order Placed Successfully!
        </h1>

        <p class="text-muted">
            Thank you for your order. Your order has been received.
        </p>

    </div>


    <div class="row justify-content-center">

        <div class="col-lg-8">

            <!-- Order Information -->

            <div class="card border-0 shadow-sm mb-4">

                <div class="card-body p-4">

                    <div class="row">

                        <div class="col-md-4 mb-3">

                            <small class="text-muted d-block">
                                Order Number
                            </small>

                            <strong>
                                <?= e($order['order_number']); ?>
                            </strong>

                        </div>


                        <div class="col-md-4 mb-3">

                            <small class="text-muted d-block">
                                Order Status
                            </small>

                            <span class="badge bg-warning text-dark">
                                <?= e(ucfirst($order['status'])); ?>
                            </span>

                        </div>


                        <div class="col-md-4 mb-3">

                            <small class="text-muted d-block">
                                Payment Status
                            </small>

                            <span class="badge bg-secondary">
                                <?= e(ucfirst($order['payment_status'])); ?>
                            </span>

                        </div>

                    </div>

                </div>

            </div>


            <!-- Order Items -->

            <div class="card border-0 shadow-sm mb-4">

                <div class="card-body p-4">

                    <h4 class="fw-bold mb-4">
                        Order Items
                    </h4>


                    <?php foreach ($orderItems as $item): ?>

                        <div class="d-flex justify-content-between border-bottom py-3">

                            <div>

                                <h6 class="fw-bold mb-1">
                                    <?= e($item['product_name']); ?>
                                </h6>

                                <small class="text-muted">
                                    SKU: <?= e($item['product_sku']); ?>
                                </small>

                                <div class="small text-muted mt-1">
                                    ₹<?= number_format($item['price'], 2); ?>
                                    ×
                                    <?= (int) $item['quantity']; ?>
                                </div>

                            </div>


                            <div class="fw-bold">

                                ₹<?= number_format($item['total'], 2); ?>

                            </div>

                        </div>

                    <?php endforeach; ?>

                </div>

            </div>


            <!-- Price Summary -->

            <div class="card border-0 shadow-sm mb-4">

                <div class="card-body p-4">

                    <h4 class="fw-bold mb-4">
                        Order Summary
                    </h4>


                    <div class="d-flex justify-content-between mb-2">

                        <span>
                            Subtotal
                        </span>

                        <span>
                            ₹<?= number_format($order['subtotal'], 2); ?>
                        </span>

                    </div>


                    <div class="d-flex justify-content-between mb-2">

                        <span>
                            Discount
                        </span>

                        <span class="text-success">
                            - ₹<?= number_format($order['discount'], 2); ?>
                        </span>

                    </div>


                    <div class="d-flex justify-content-between mb-3">

                        <span>
                            Shipping
                        </span>

                        <span class="text-success">
                            <?php if ($order['shipping_amount'] > 0): ?>

                                ₹<?= number_format($order['shipping_amount'], 2); ?>

                            <?php else: ?>

                                Free

                            <?php endif; ?>
                        </span>

                    </div>


                    <hr>


                    <div class="d-flex justify-content-between">

                        <strong>
                            Total
                        </strong>

                        <strong class="fs-5">
                            ₹<?= number_format($order['total_amount'], 2); ?>
                        </strong>

                    </div>

                </div>

            </div>


            <!-- Buttons -->

            <div class="text-center">

                <a
                    href="products.php"
                    class="btn btn-dark px-4"
                >
                    Continue Shopping
                </a>

                <a
                    href="orders.php"
                    class="btn btn-outline-dark px-4 ms-2"
                >
                    My Orders
                </a>

            </div>

        </div>

    </div>

</div>

</body>

</html>