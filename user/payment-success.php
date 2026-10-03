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
| GET Request
|--------------------------------------------------------------------------
|
| Used for displaying successful payment page.
|
*/

if ($_SERVER['REQUEST_METHOD'] === 'GET') {

    $orderId = (int) ($_GET['order_id'] ?? 0);

    $success = (int) ($_GET['success'] ?? 0);

    if (
        $orderId <= 0
        || $success !== 1
    ) {
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
            payment_status,
            created_at
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
            product_name,
            price,
            quantity,
            total
        FROM order_items
        WHERE order_id = ?
        ORDER BY id ASC
    ");

    $itemStmt->execute([
        $orderId
    ]);

    $items = $itemStmt->fetchAll();

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
            Payment Successful
        </title>

        <link
            href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
            rel="stylesheet"
        >

    </head>

    <body>

    <div class="container py-5">

        <div
            class="card border-0 shadow-sm mx-auto"
            style="max-width: 700px;"
        >

            <div class="card-body p-5 text-center">

                <div class="mb-4">

                    <div
                        class="rounded-circle bg-success text-white d-inline-flex align-items-center justify-content-center"
                        style="width: 80px; height: 80px;"
                    >

                        <span class="fs-2">
                            ✓
                        </span>

                    </div>

                </div>


                <h2 class="fw-bold text-success mb-2">

                    Payment Successful

                </h2>


                <p class="text-muted mb-4">

                    Your payment has been successfully received.

                </p>


                <div class="border rounded p-4 mb-4">

                    <div class="row">

                        <div class="col-md-6 mb-3">

                            <small class="text-muted">
                                Order Number
                            </small>

                            <div class="fw-bold">

                                <?= e(
                                    $order['order_number']
                                ); ?>

                            </div>

                        </div>


                        <div class="col-md-6 mb-3">

                            <small class="text-muted">
                                Payment Status
                            </small>

                            <div>

                                <span class="badge bg-success">
                                    Paid
                                </span>

                            </div>

                        </div>


                        <div class="col-md-6">

                            <small class="text-muted">
                                Order Status
                            </small>

                            <div class="fw-bold">

                                <?= e(
                                    ucfirst(
                                        $order['status']
                                    )
                                ); ?>

                            </div>

                        </div>


                        <div class="col-md-6">

                            <small class="text-muted">
                                Amount Paid
                            </small>

                            <div class="fw-bold">

                                ₹<?= number_format(
                                    $order['total_amount'],
                                    2
                                ); ?>

                            </div>

                        </div>

                    </div>

                </div>


                <!-- Order Items -->

                <?php if (!empty($items)): ?>

                    <div class="text-start mb-4">

                        <h5 class="fw-bold mb-3">
                            Order Items
                        </h5>

                        <?php foreach ($items as $item): ?>

                            <div
                                class="d-flex justify-content-between border-bottom py-2"
                            >

                                <div>

                                    <?= e(
                                        $item['product_name']
                                    ); ?>

                                    <small class="text-muted">

                                        ×
                                        <?= (int) $item['quantity']; ?>

                                    </small>

                                </div>

                                <strong>

                                    ₹<?= number_format(
                                        $item['total'],
                                        2
                                    ); ?>

                                </strong>

                            </div>

                        <?php endforeach; ?>

                    </div>

                <?php endif; ?>


                <div class="d-flex gap-2">

                    <a
                        href="order-details.php?id=<?= $order['id']; ?>"
                        class="btn btn-outline-dark w-50"
                    >
                        View Order
                    </a>

                    <a
                        href="products.php"
                        class="btn btn-dark w-50"
                    >
                        Continue Shopping
                    </a>

                </div>

            </div>

        </div>

    </div>

    </body>

    </html>

    <?php

    exit;
}


/*
|--------------------------------------------------------------------------
| Only POST From Razorpay
|--------------------------------------------------------------------------
*/

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: orders.php");
    exit;
}


/*
|--------------------------------------------------------------------------
| CSRF Protection
|--------------------------------------------------------------------------
*/

if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) {
    die("Invalid CSRF token.");
}


/*
|--------------------------------------------------------------------------
| Get Payment Data
|--------------------------------------------------------------------------
*/

$orderId = (int) ($_POST['order_id'] ?? 0);

$razorpayOrderId =
    trim($_POST['razorpay_order_id'] ?? '');

$razorpayPaymentId =
    trim($_POST['razorpay_payment_id'] ?? '');

$razorpaySignature =
    trim($_POST['razorpay_signature'] ?? '');


/*
|--------------------------------------------------------------------------
| Validate Data
|--------------------------------------------------------------------------
*/

if (
    $orderId <= 0
    || empty($razorpayOrderId)
    || empty($razorpayPaymentId)
    || empty($razorpaySignature)
) {

    die("Invalid payment response.");

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
| Get Payment Record
|--------------------------------------------------------------------------
*/

$paymentStmt = $pdo->prepare("
    SELECT
        id,
        razorpay_order_id,
        amount,
        status
    FROM payments
    WHERE order_id = ?
    AND gateway = 'razorpay'
    AND razorpay_order_id = ?
    LIMIT 1
");

$paymentStmt->execute([
    $orderId,
    $razorpayOrderId
]);

$payment = $paymentStmt->fetch();

if (!$payment) {
    die("Payment record not found.");
}


/*
|--------------------------------------------------------------------------
| Verify Amount
|--------------------------------------------------------------------------
*/

$orderAmount = (float) $order['total_amount'];

$paymentAmount = (float) $payment['amount'];

if (
    abs($orderAmount - $paymentAmount)
    > 0.01
) {

    die("Payment amount mismatch.");

}


/*
|--------------------------------------------------------------------------
| Verify Razorpay Signature
|--------------------------------------------------------------------------
*/

$paymentService = new PaymentService();

$isValidSignature =
    $paymentService->verifySignature(
        $razorpayOrderId,
        $razorpayPaymentId,
        $razorpaySignature
    );


if (!$isValidSignature) {

    /*
    |--------------------------------------------------------------------------
    | Mark Payment Failed
    |--------------------------------------------------------------------------
    */

    $failedStmt = $pdo->prepare("
        UPDATE payments
        SET
            status = 'failed',
            updated_at = NOW()
        WHERE id = ?
    ");

    $failedStmt->execute([
        $payment['id']
    ]);


    header(
        "Location: payment-failed.php?order_id="
        . $orderId
    );

    exit;
}


/*
|--------------------------------------------------------------------------
| Start Transaction
|--------------------------------------------------------------------------
*/

$pdo->beginTransaction();

try {

    /*
    |--------------------------------------------------------------------------
    | Update Payment
    |--------------------------------------------------------------------------
    */

    $updatePayment = $pdo->prepare("
        UPDATE payments
        SET
            razorpay_payment_id = ?,
            razorpay_signature = ?,
            status = 'paid',
            paid_at = NOW(),
            updated_at = NOW()
        WHERE id = ?
        AND status != 'paid'
    ");

    $updatePayment->execute([
        $razorpayPaymentId,
        $razorpaySignature,
        $payment['id']
    ]);


    /*
    |--------------------------------------------------------------------------
    | Update Order
    |--------------------------------------------------------------------------
    */

    $updateOrder = $pdo->prepare("
        UPDATE orders
        SET
            payment_status = 'paid',
            status = 'confirmed',
            updated_at = NOW()
        WHERE id = ?
    ");

    $updateOrder->execute([
        $orderId
    ]);


    /*
    |--------------------------------------------------------------------------
    | Add Order History
    |--------------------------------------------------------------------------
    */

    $historyStmt = $pdo->prepare("
        INSERT INTO order_status_history (
            order_id,
            status,
            note,
            created_by,
            created_at
        )
        VALUES (
            ?,
            'confirmed',
            ?,
            ?,
            NOW()
        )
    ");

    $historyStmt->execute([
        $orderId,
        'Payment received successfully through Razorpay.',
        $userId
    ]);


    /*
    |--------------------------------------------------------------------------
    | Commit
    |--------------------------------------------------------------------------
    */

    $pdo->commit();


    /*
    |--------------------------------------------------------------------------
    | Redirect Success Page
    |--------------------------------------------------------------------------
    */

    header(
        "Location: payment-success.php?order_id="
        . $orderId
        . "&success=1"
    );

    exit;


} catch (Throwable $e) {

    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }

    die(
        "Unable to process payment: "
        . e($e->getMessage())
    );

}