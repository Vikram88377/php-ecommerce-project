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
| Get Order ID
|--------------------------------------------------------------------------
*/

$orderId = (int) ($_GET['order_id'] ?? 0);

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
        Payment Failed
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
        style="max-width: 650px;"
    >

        <div class="card-body p-5 text-center">


            <!-- Error Icon -->

            <div class="mb-4">

                <div
                    class="rounded-circle bg-danger text-white d-inline-flex align-items-center justify-content-center"
                    style="width: 80px; height: 80px;"
                >

                    <span class="fs-2">
                        !
                    </span>

                </div>

            </div>


            <!-- Heading -->

            <h2 class="fw-bold text-danger mb-2">

                Payment Failed

            </h2>


            <p class="text-muted mb-4">

                We could not complete your Razorpay payment.

                Your order is still available for payment.

            </p>


            <!-- Order Information -->

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
                            Amount
                        </small>

                        <div class="fw-bold">

                            ₹<?= number_format(
                                $order['total_amount'],
                                2
                            ); ?>

                        </div>

                    </div>


                    <div class="col-md-6">

                        <small class="text-muted">
                            Order Status
                        </small>

                        <div>

                            <span class="badge bg-warning text-dark">

                                <?= e(
                                    ucfirst(
                                        $order['status']
                                    )
                                ); ?>

                            </span>

                        </div>

                    </div>


                    <div class="col-md-6">

                        <small class="text-muted">
                            Payment Status
                        </small>

                        <div>

                            <span class="badge bg-danger">
                                Failed
                            </span>

                        </div>

                    </div>

                </div>

            </div>


            <!-- Retry Message -->

            <div class="alert alert-warning text-start">

                <strong>
                    What can you do?
                </strong>

                <ul class="mb-0 mt-2">

                    <li>
                        Check your payment details.
                    </li>

                    <li>
                        Make sure your payment method is working.
                    </li>

                    <li>
                        Try the payment again.
                    </li>

                </ul>

            </div>


            <!-- Buttons -->

            <div class="d-flex gap-2 mt-4">

                <a
                    href="create-payment.php?order_id=<?= $orderId; ?>"
                    class="btn btn-success w-50"
                    onclick="event.preventDefault(); document.getElementById('retryPaymentForm').submit();"
                >
                    Retry Payment
                </a>

                <a
                    href="order-details.php?id=<?= $orderId; ?>"
                    class="btn btn-outline-dark w-50"
                >
                    View Order
                </a>

            </div>


            <!-- Hidden Retry Form -->

            <form
                id="retryPaymentForm"
                action="create-payment.php"
                method="POST"
                class="d-none"
            >

                <input
                    type="hidden"
                    name="csrf_token"
                    value="<?= csrfToken(); ?>"
                >

                <input
                    type="hidden"
                    name="order_id"
                    value="<?= $orderId; ?>"
                >

            </form>


            <div class="mt-4">

                <a
                    href="orders.php"
                    class="text-decoration-none"
                >
                    ← Back to My Orders
                </a>

            </div>

        </div>

    </div>

</div>

</body>

</html>