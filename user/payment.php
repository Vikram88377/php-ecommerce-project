<?php

require_once "../config/database.php";
require_once "../includes/security.php";
require_once "../config/razorpay.php";

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
| Get Latest Payment
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
    ORDER BY id DESC
    LIMIT 1
");

$paymentStmt->execute([
    $orderId
]);

$payment = $paymentStmt->fetch();

if (!$payment) {
    die("Payment record not found.");
}


/*
|--------------------------------------------------------------------------
| Payment Amount Validation
|--------------------------------------------------------------------------
*/

$orderAmount = (float) $order['total_amount'];

$paymentAmount = (float) $payment['amount'];

if (abs($orderAmount - $paymentAmount) > 0.01) {
    die("Payment amount mismatch.");
}


/*
|--------------------------------------------------------------------------
| Razorpay Order ID
|--------------------------------------------------------------------------
*/

$razorpayOrderId = $payment['razorpay_order_id'];

if (empty($razorpayOrderId)) {
    die("Invalid Razorpay order.");
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

<title>Make Payment</title>

<link
    href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
    rel="stylesheet"
>

</head>

<body class="bg-light">

<div class="container py-5">

    <div class="row justify-content-center">

        <div class="col-md-6">

            <div class="card border-0 shadow-sm">

                <div class="card-body p-5 text-center">

                    <h2 class="fw-bold mb-4">
                        Complete Your Payment
                    </h2>

                    <p class="text-muted mb-4">
                        Order:
                        <strong>
                            <?= e($order['order_number']); ?>
                        </strong>
                    </p>


                    <div class="bg-light rounded p-4 mb-4">

                        <small class="text-muted">
                            Amount to Pay
                        </small>

                        <h2 class="fw-bold mt-2">
                            ₹<?= number_format($paymentAmount, 2); ?>
                        </h2>

                    </div>


                    <button
                        type="button"
                        id="payButton"
                        class="btn btn-dark btn-lg w-100"
                    >
                        Pay Now
                    </button>


                    <a
                        href="order-details.php?id=<?= $orderId; ?>"
                        class="btn btn-outline-secondary w-100 mt-2"
                    >
                        Back to Order
                    </a>

                </div>

            </div>

        </div>

    </div>

</div>


<!-- Razorpay Checkout -->

<script src="https://checkout.razorpay.com/v1/checkout.js"></script>


<script>

document.getElementById('payButton').addEventListener('click', function () {

    const button = this;

    button.disabled = true;

    button.innerText = 'Opening Payment...';


    const options = {

        key: <?= json_encode(RAZORPAY_KEY_ID); ?>,

        amount: <?= (int) round($paymentAmount * 100); ?>,

        currency: "INR",

        name: "Enterprise E-commerce",

        description: "Order <?= e($order['order_number']); ?>",

        order_id: <?= json_encode($razorpayOrderId); ?>,


        handler: function (response) {

            /*
            |--------------------------------------------------------------------------
            | Create POST Form
            |--------------------------------------------------------------------------
            */

            const form = document.createElement('form');

            form.method = 'POST';

            form.action = 'payment-success.php';


            /*
            | CSRF
            */

            const csrf = document.createElement('input');

            csrf.type = 'hidden';

            csrf.name = 'csrf_token';

            csrf.value =
                <?= json_encode(csrfToken()); ?>;

            form.appendChild(csrf);


            /*
            | Order ID
            */

            const orderId = document.createElement('input');

            orderId.type = 'hidden';

            orderId.name = 'order_id';

            orderId.value =
                <?= (int) $orderId; ?>;

            form.appendChild(orderId);


            /*
            | Razorpay Order ID
            */

            const razorpayOrderId = document.createElement('input');

            razorpayOrderId.type = 'hidden';

            razorpayOrderId.name = 'razorpay_order_id';

            razorpayOrderId.value =
                response.razorpay_order_id;

            form.appendChild(razorpayOrderId);


            /*
            | Razorpay Payment ID
            */

            const razorpayPaymentId = document.createElement('input');

            razorpayPaymentId.type = 'hidden';

            razorpayPaymentId.name = 'razorpay_payment_id';

            razorpayPaymentId.value =
                response.razorpay_payment_id;

            form.appendChild(razorpayPaymentId);


            /*
            | Razorpay Signature
            */

            const razorpaySignature = document.createElement('input');

            razorpaySignature.type = 'hidden';

            razorpaySignature.name = 'razorpay_signature';

            razorpaySignature.value =
                response.razorpay_signature;

            form.appendChild(razorpaySignature);


            document.body.appendChild(form);

            form.submit();

        },


        /*
        |--------------------------------------------------------------------------
        | Payment Failed
        |--------------------------------------------------------------------------
        */

        modal: {

            ondismiss: function () {

                button.disabled = false;

                button.innerText = 'Pay Now';

            }

        },


        theme: {
            color: "#212529"
        }

    };


    /*
    |--------------------------------------------------------------------------
    | Open Razorpay
    |--------------------------------------------------------------------------
    */

    const razorpay = new Razorpay(options);


    razorpay.on('payment.failed', function () {

        window.location.href =
            'payment-failed.php?order_id='
            + <?= (int) $orderId; ?>;

    });


    razorpay.open();

});

</script>

</body>

</html>