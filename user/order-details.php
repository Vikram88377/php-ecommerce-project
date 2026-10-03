<?php

require_once "../config/database.php";
require_once "../includes/security.php";

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$userId = $_SESSION['user_id'] ?? 0;

if ($userId <= 0) {
    header("Location: login.php");
    exit;
}

$orderId = (int) ($_GET['id'] ?? 0);

if ($orderId <= 0) {
    header("Location: orders.php");
    exit;
}

/*
|--------------------------------------------------------------------------
| Get Order
|--------------------------------------------------------------------------
*/

$stmt = $pdo->prepare("
    SELECT
        o.id,
        o.order_number,
        o.subtotal,
        o.discount,
        o.shipping_amount,
        o.total_amount,
        o.status,
        o.payment_status,
        o.created_at,

        a.name AS address_name,
        a.phone AS address_phone,
        a.address_line1,
        a.address_line2,
        a.city,
        a.state,
        a.postal_code,
        a.country

    FROM orders o

    LEFT JOIN addresses a
        ON a.id = o.address_id

    WHERE o.id = ?
    AND o.user_id = ?

    LIMIT 1
");

$stmt->execute([
    $orderId,
    $userId
]);

$order = $stmt->fetch();

if (!$order) {
    header("Location: orders.php");
    exit;
}

/*
|--------------------------------------------------------------------------
| Get Order Items
|--------------------------------------------------------------------------
*/

$itemStmt = $pdo->prepare("
    SELECT
        id,
        product_id,
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

$items = $itemStmt->fetchAll();

/*
|--------------------------------------------------------------------------
| Get Order Status History
|--------------------------------------------------------------------------
*/

$historyStmt = $pdo->prepare("
    SELECT
        status,
        note,
        created_at
    FROM order_status_history
    WHERE order_id = ?
    ORDER BY id DESC
");

$historyStmt->execute([$orderId]);

$history = $historyStmt->fetchAll();

?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1"
    >

    <title>
        Order Details - <?= e($order['order_number']); ?>
    </title>

    <link
        href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
        rel="stylesheet"
    >

</head>

<body>

<div class="container py-5">

    <!-- Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">

        <div>

            <h2 class="fw-bold mb-1">
                Order Details
            </h2>

            <p class="text-muted mb-0">
                Order #<?= e($order['order_number']); ?>
            </p>

        </div>

        <a
            href="orders.php"
            class="btn btn-outline-secondary"
        >
            ← My Orders
        </a>

    </div>


    <!-- Order Information -->

    <div class="card border-0 shadow-sm mb-4">

        <div class="card-body">

            <div class="row">

                <!-- Order Number -->

                <div class="col-md-3 mb-3">

                    <small class="text-muted">
                        Order Number
                    </small>

                    <div class="fw-bold">
                        <?= e($order['order_number']); ?>
                    </div>

                </div>


                <!-- Order Date -->

                <div class="col-md-3 mb-3">

                    <small class="text-muted">
                        Order Date
                    </small>

                    <div class="fw-bold">

                        <?= date(
                            'd M Y, h:i A',
                            strtotime($order['created_at'])
                        ); ?>

                    </div>

                </div>


                <!-- Order Status -->

                <div class="col-md-3 mb-3">

                    <small class="text-muted">
                        Order Status
                    </small>

                    <div>

                        <?php if ($order['status'] === 'pending'): ?>

                            <span class="badge bg-warning text-dark">
                                Pending
                            </span>

                        <?php elseif ($order['status'] === 'confirmed'): ?>

                            <span class="badge bg-primary">
                                Confirmed
                            </span>

                        <?php elseif ($order['status'] === 'processing'): ?>

                            <span class="badge bg-info text-dark">
                                Processing
                            </span>

                        <?php elseif ($order['status'] === 'shipped'): ?>

                            <span class="badge bg-info">
                                Shipped
                            </span>

                        <?php elseif ($order['status'] === 'delivered'): ?>

                            <span class="badge bg-success">
                                Delivered
                            </span>

                        <?php elseif ($order['status'] === 'cancelled'): ?>

                            <span class="badge bg-danger">
                                Cancelled
                            </span>

                        <?php else: ?>

                            <span class="badge bg-secondary">
                                <?= e(ucfirst($order['status'])); ?>
                            </span>

                        <?php endif; ?>

                    </div>

                </div>


                <!-- Payment Status -->

                <div class="col-md-3 mb-3">

                    <small class="text-muted">
                        Payment Status
                    </small>

                    <div>

                        <?php if ($order['payment_status'] === 'paid'): ?>

                            <span class="badge bg-success">
                                Paid
                            </span>

                        <?php elseif ($order['payment_status'] === 'failed'): ?>

                            <span class="badge bg-danger">
                                Failed
                            </span>

                        <?php elseif ($order['payment_status'] === 'refunded'): ?>

                            <span class="badge bg-secondary">
                                Refunded
                            </span>

                        <?php else: ?>

                            <span class="badge bg-warning text-dark">
                                Pending
                            </span>

                        <?php endif; ?>

                    </div>

                </div>

            </div>


            <!-- Pay Now -->

            <?php if (
                $order['payment_status'] !== 'paid'
                && $order['status'] !== 'cancelled'
            ): ?>

                <div class="border-top pt-3 mt-2">

                    <div class="d-flex justify-content-between align-items-center">

                        <div>

                            <h6 class="fw-bold mb-1">
                                Payment Pending
                            </h6>

                            <small class="text-muted">
                                Complete your payment to confirm the order.
                            </small>

                        </div>

                        <form
                            action="create-payment.php"
                            method="POST"
                        >

                            <input
                                type="hidden"
                                name="csrf_token"
                                value="<?= csrfToken(); ?>"
                            >

                            <input
                                type="hidden"
                                name="order_id"
                                value="<?= $order['id']; ?>"
                            >

                            <button
                                type="submit"
                                class="btn btn-success px-4"
                            >
                                Pay Now
                            </button>

                        </form>

                    </div>

                </div>

            <?php endif; ?>

        </div>

    </div>


    <div class="row">

        <!-- Left Side -->

        <div class="col-lg-8">


            <!-- Shipping Address -->

            <div class="card border-0 shadow-sm mb-4">

                <div class="card-body">

                    <h5 class="fw-bold mb-4">
                        Shipping Address
                    </h5>

                    <?php if (!empty($order['address_name'])): ?>

                        <div class="mb-2">

                            <strong>
                                <?= e($order['address_name']); ?>
                            </strong>

                        </div>

                        <div class="text-muted">

                            <?= e($order['address_line1']); ?>

                            <?php if (!empty($order['address_line2'])): ?>

                                <br>

                                <?= e($order['address_line2']); ?>

                            <?php endif; ?>

                            <br>

                            <?= e($order['city']); ?>,
                            <?= e($order['state']); ?> -
                            <?= e($order['postal_code']); ?>

                            <br>

                            <?= e($order['country']); ?>

                            <br>

                            Phone:
                            <?= e($order['address_phone']); ?>

                        </div>

                    <?php else: ?>

                        <p class="text-muted mb-0">
                            Address information is not available.
                        </p>

                    <?php endif; ?>

                </div>

            </div>


            <!-- Order Items -->

            <div class="card border-0 shadow-sm mb-4">

                <div class="card-body">

                    <h5 class="fw-bold mb-4">
                        Ordered Products
                    </h5>

                    <?php if (empty($items)): ?>

                        <p class="text-muted">
                            No items found.
                        </p>

                    <?php else: ?>

                        <?php foreach ($items as $item): ?>

                            <div class="row align-items-center border-bottom py-3">

                                <div class="col-md-6">

                                    <h6 class="fw-bold mb-1">

                                        <?= e(
                                            $item['product_name']
                                        ); ?>

                                    </h6>

                                    <small class="text-muted">

                                        SKU:
                                        <?= e(
                                            $item['product_sku']
                                        ); ?>

                                    </small>

                                </div>


                                <div class="col-md-2">

                                    <small class="text-muted">
                                        Price
                                    </small>

                                    <div>
                                        ₹<?= number_format(
                                            $item['price'],
                                            2
                                        ); ?>
                                    </div>

                                </div>


                                <div class="col-md-2">

                                    <small class="text-muted">
                                        Quantity
                                    </small>

                                    <div>
                                        <?= (int) $item['quantity']; ?>
                                    </div>

                                </div>


                                <div class="col-md-2 text-md-end">

                                    <small class="text-muted">
                                        Total
                                    </small>

                                    <div class="fw-bold">

                                        ₹<?= number_format(
                                            $item['total'],
                                            2
                                        ); ?>

                                    </div>

                                </div>

                            </div>

                        <?php endforeach; ?>

                    <?php endif; ?>

                </div>

            </div>


            <!-- Order History -->

            <div class="card border-0 shadow-sm mb-4">

                <div class="card-body">

                    <h5 class="fw-bold mb-4">
                        Order Status History
                    </h5>

                    <?php if (empty($history)): ?>

                        <p class="text-muted mb-0">
                            No status history available.
                        </p>

                    <?php else: ?>

                        <?php foreach ($history as $historyItem): ?>

                            <div class="d-flex mb-3">

                                <div class="me-3">

                                    <span
                                        class="badge bg-dark"
                                    >
                                        <?= e(
                                            ucfirst(
                                                $historyItem['status']
                                            )
                                        ); ?>
                                    </span>

                                </div>

                                <div>

                                    <?php if (!empty($historyItem['note'])): ?>

                                        <div class="fw-semibold">
                                            <?= e(
                                                $historyItem['note']
                                            ); ?>
                                        </div>

                                    <?php endif; ?>

                                    <small class="text-muted">

                                        <?= date(
                                            'd M Y, h:i A',
                                            strtotime(
                                                $historyItem['created_at']
                                            )
                                        ); ?>

                                    </small>

                                </div>

                            </div>

                        <?php endforeach; ?>

                    <?php endif; ?>

                </div>

            </div>

        </div>


        <!-- Right Side -->

        <div class="col-lg-4">


            <!-- Price Summary -->

            <div class="card border-0 shadow-sm mb-4">

                <div class="card-body p-4">

                    <h5 class="fw-bold mb-4">
                        Order Summary
                    </h5>


                    <div class="d-flex justify-content-between mb-3">

                        <span>
                            Subtotal
                        </span>

                        <span>
                            ₹<?= number_format(
                                $order['subtotal'],
                                2
                            ); ?>
                        </span>

                    </div>


                    <div class="d-flex justify-content-between mb-3">

                        <span>
                            Discount
                        </span>

                        <span class="text-success">

                            - ₹<?= number_format(
                                $order['discount'],
                                2
                            ); ?>

                        </span>

                    </div>


                    <div class="d-flex justify-content-between mb-3">

                        <span>
                            Shipping
                        </span>

                        <span>

                            <?php if ($order['shipping_amount'] > 0): ?>

                                ₹<?= number_format(
                                    $order['shipping_amount'],
                                    2
                                ); ?>

                            <?php else: ?>

                                <span class="text-success">
                                    Free
                                </span>

                            <?php endif; ?>

                        </span>

                    </div>


                    <hr>


                    <div class="d-flex justify-content-between">

                        <strong>
                            Total
                        </strong>

                        <strong class="fs-5">

                            ₹<?= number_format(
                                $order['total_amount'],
                                2
                            ); ?>

                        </strong>

                    </div>


                    <?php if (
                        $order['payment_status'] !== 'paid'
                        && $order['status'] !== 'cancelled'
                    ): ?>

                        <form
                            action="create-payment.php"
                            method="POST"
                            class="mt-4"
                        >

                            <input
                                type="hidden"
                                name="csrf_token"
                                value="<?= csrfToken(); ?>"
                            >

                            <input
                                type="hidden"
                                name="order_id"
                                value="<?= $order['id']; ?>"
                            >

                            <button
                                type="submit"
                                class="btn btn-success w-100"
                            >
                                Pay ₹<?= number_format(
                                    $order['total_amount'],
                                    2
                                ); ?>
                            </button>

                        </form>

                    <?php elseif ($order['payment_status'] === 'paid'): ?>

                        <div class="alert alert-success mt-4 mb-0">

                            <strong>
                                Payment Completed
                            </strong>

                            <br>

                            Your payment has been successfully received.

                        </div>

                    <?php endif; ?>

                </div>

            </div>


            <!-- Navigation -->

            <div class="card border-0 shadow-sm">

                <div class="card-body">

                    <a
                        href="orders.php"
                        class="btn btn-outline-dark w-100 mb-2"
                    >
                        My Orders
                    </a>

                    <a
                        href="products.php"
                        class="btn btn-dark w-100"
                    >
                        Continue Shopping
                    </a>

                </div>

            </div>

        </div>

    </div>

</div>

</body>

</html>