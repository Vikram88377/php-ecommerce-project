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

$cart = $_SESSION['cart'] ?? [];

if (empty($cart)) {
    header("Location: cart.php");
    exit;
}

/*
|--------------------------------------------------------------------------
| Get Cart Products
|--------------------------------------------------------------------------
*/

$productIds = array_keys($cart);

$placeholders = implode(',', array_fill(0, count($productIds), '?'));

$stmt = $pdo->prepare("
    SELECT 
        p.id,
        p.name,
        p.price,
        p.sale_price,
        p.stock,
        p.status,
        pi.image
    FROM products p
    LEFT JOIN product_images pi
        ON pi.product_id = p.id
        AND pi.is_primary = 1
    WHERE p.id IN ($placeholders)
");

$stmt->execute($productIds);

$products = $stmt->fetchAll();

$subtotal = 0;

foreach ($products as $product) {

    $productId = $product['id'];
    $quantity = (int) ($cart[$productId] ?? 0);

    if ($quantity <= 0) {
        continue;
    }

    $price = $product['sale_price'] !== null
        ? (float) $product['sale_price']
        : (float) $product['price'];

    $subtotal += $price * $quantity;
}

/*
|--------------------------------------------------------------------------
| Shipping
|--------------------------------------------------------------------------
*/

$shipping = 0;

/*
|--------------------------------------------------------------------------
| Coupon
|--------------------------------------------------------------------------
*/

$discount = 0;
$appliedCoupon = null;

if (!empty($_SESSION['coupon']['id'])) {

    $couponStmt = $pdo->prepare("
        SELECT *
        FROM coupons
        WHERE id = ?
        LIMIT 1
    ");

    $couponStmt->execute([
        (int) $_SESSION['coupon']['id']
    ]);

    $coupon = $couponStmt->fetch();

    if ($coupon) {

        $couponResult = getValidCoupon(
            $pdo,
            $coupon['code'],
            $subtotal
        );

        if ($couponResult['success']) {

            $appliedCoupon = $couponResult['coupon'];
            $discount = $couponResult['discount'];

        } else {

            unset($_SESSION['coupon']);

            $appliedCoupon = null;
            $discount = 0;
        }
    } else {

        unset($_SESSION['coupon']);
    }
}

$total = $subtotal - $discount + $shipping;

/*
|--------------------------------------------------------------------------
| User Addresses
|--------------------------------------------------------------------------
*/

$addressStmt = $pdo->prepare("
    SELECT *
    FROM addresses
    WHERE user_id = ?
    ORDER BY is_default DESC, id DESC
");

$addressStmt->execute([$userId]);

$addresses = $addressStmt->fetchAll();

?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta name="viewport" content="width=device-width, initial-scale=1">

    <title>Checkout</title>

    <link
        href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
        rel="stylesheet"
    >

    <style>

        body {
            background: #f8f9fa;
        }

        .checkout-card {
            border: 0;
            border-radius: 12px;
        }

        .payment-option {
            border: 1px solid #dee2e6;
            border-radius: 10px;
            padding: 15px;
            cursor: pointer;
            transition: 0.2s;
        }

        .payment-option.active {
            border-color: #0d6efd;
            background: #f0f6ff;
        }

        .summary-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 12px;
        }

        .total-row {
            font-size: 20px;
            font-weight: 700;
        }

    </style>

</head>

<body>

<div class="container py-5">

    <div class="row g-4">

        <!-- LEFT SIDE -->

        <div class="col-lg-7">

            <div class="card checkout-card shadow-sm">

                <div class="card-body p-4">

                    <h3 class="fw-bold mb-4">
                        Checkout
                    </h3>


                    <!-- Coupon -->

                    <div class="mb-4">

                        <h5 class="fw-bold">
                            Coupon Code
                        </h5>

                        <?php if (isset($_GET['coupon_error'])): ?>

                            <div class="alert alert-danger">
                                <?= e($_GET['coupon_error']) ?>
                            </div>

                        <?php endif; ?>


                        <?php if (isset($_GET['coupon']) && $_GET['coupon'] === 'applied'): ?>

                            <div class="alert alert-success">
                                Coupon applied successfully.
                            </div>

                        <?php endif; ?>


                        <?php if (isset($_GET['coupon']) && $_GET['coupon'] === 'removed'): ?>

                            <div class="alert alert-info">
                                Coupon removed.
                            </div>

                        <?php endif; ?>


                        <?php if ($appliedCoupon): ?>

                            <div class="alert alert-success d-flex justify-content-between align-items-center">

                                <div>

                                    <strong>
                                        <?= e($appliedCoupon['code']) ?>
                                    </strong>

                                    <br>

                                    <small>
                                        Discount:
                                        ₹<?= number_format($discount, 2) ?>
                                    </small>

                                </div>


                                <form
                                    method="POST"
                                    action="remove-coupon.php"
                                >

                                    <input
                                        type="hidden"
                                        name="csrf_token"
                                        value="<?= e(csrfToken()) ?>"
                                    >

                                    <button
                                        type="submit"
                                        class="btn btn-sm btn-outline-danger"
                                    >
                                        Remove
                                    </button>

                                </form>

                            </div>

                        <?php else: ?>

                            <form
                                method="POST"
                                action="apply-coupon.php"
                                class="row g-2"
                            >

                                <div class="col">

                                    <input
                                        type="text"
                                        name="coupon_code"
                                        class="form-control"
                                        placeholder="Enter coupon code"
                                        required
                                    >

                                </div>

                                <div class="col-auto">

                                    <input
                                        type="hidden"
                                        name="csrf_token"
                                        value="<?= e(csrfToken()) ?>"
                                    >

                                    <button
                                        type="submit"
                                        class="btn btn-dark"
                                    >
                                        Apply
                                    </button>

                                </div>

                            </form>

                        <?php endif; ?>

                    </div>


                    <!-- Address -->

                    <h5 class="fw-bold mb-3">
                        Delivery Address
                    </h5>

                    <?php if (empty($addresses)): ?>

                        <div class="alert alert-warning">

                            No address found.

                            <a href="address.php">
                                Add Address
                            </a>

                        </div>

                    <?php else: ?>

                        <?php foreach ($addresses as $address): ?>

                            <div class="form-check border rounded p-3 mb-2">

                                <input
                                    class="form-check-input"
                                    type="radio"
                                    name="address_select"
                                    id="address_<?= $address['id'] ?>"
                                    value="<?= $address['id'] ?>"
                                    <?= $address['is_default'] ? 'checked' : '' ?>
                                    onclick="selectAddress(<?= $address['id'] ?>)"
                                >

                                <label
                                    class="form-check-label ms-2"
                                    for="address_<?= $address['id'] ?>"
                                >

                                    <strong>
                                        <?= e($address['name']) ?>
                                    </strong>

                                    <br>

                                    <?= e($address['phone']) ?>

                                    <br>

                                    <?= e($address['address_line1']) ?>

                                    <?php if (!empty($address['address_line2'])): ?>

                                        , <?= e($address['address_line2']) ?>

                                    <?php endif; ?>

                                    <br>

                                    <?= e($address['city']) ?>,
                                    <?= e($address['state']) ?> -
                                    <?= e($address['postal_code']) ?>

                                </label>

                            </div>

                        <?php endforeach; ?>

                        <a
                            href="address.php"
                            class="btn btn-outline-primary btn-sm mt-2"
                        >
                            + Add New Address
                        </a>

                    <?php endif; ?>


                    <hr class="my-4">


                    <!-- Payment -->

                    <h5 class="fw-bold mb-3">
                        Payment Method
                    </h5>


                    <div
                        class="payment-option active mb-3"
                        onclick="selectPayment('cod')"
                        id="codOption"
                    >

                        <div class="form-check">

                            <input
                                class="form-check-input"
                                type="radio"
                                name="payment_method_radio"
                                id="cod"
                                value="cod"
                                checked
                            >

                            <label
                                class="form-check-label"
                                for="cod"
                            >

                                <strong>
                                    Cash on Delivery
                                </strong>

                                <br>

                                <small class="text-muted">
                                    Pay when your order is delivered.
                                </small>

                            </label>

                        </div>

                    </div>


                    <div
                        class="payment-option"
                        onclick="selectPayment('razorpay')"
                        id="razorpayOption"
                    >

                        <div class="form-check">

                            <input
                                class="form-check-input"
                                type="radio"
                                name="payment_method_radio"
                                id="razorpay"
                                value="razorpay"
                            >

                            <label
                                class="form-check-label"
                                for="razorpay"
                            >

                                <strong>
                                    Razorpay
                                </strong>

                                <br>

                                <small class="text-muted">
                                    Pay securely online.
                                </small>

                            </label>

                        </div>

                    </div>

                </div>

            </div>

        </div>


        <!-- RIGHT SIDE -->

        <div class="col-lg-5">

            <div class="card checkout-card shadow-sm">

                <div class="card-body p-4">

                    <h4 class="fw-bold mb-4">
                        Order Summary
                    </h4>


                    <?php foreach ($products as $product): ?>

                        <?php

                        $quantity = (int) ($cart[$product['id']] ?? 0);

                        $price = $product['sale_price'] !== null
                            ? (float) $product['sale_price']
                            : (float) $product['price'];

                        ?>

                        <div class="d-flex justify-content-between mb-3">

                            <div>

                                <strong>
                                    <?= e($product['name']) ?>
                                </strong>

                                <br>

                                <small class="text-muted">
                                    Qty: <?= $quantity ?>
                                </small>

                            </div>

                            <div>

                                ₹<?= number_format($price * $quantity, 2) ?>

                            </div>

                        </div>

                    <?php endforeach; ?>


                    <hr>


                    <div class="summary-row">

                        <span>
                            Subtotal
                        </span>

                        <span>
                            ₹<?= number_format($subtotal, 2) ?>
                        </span>

                    </div>


                    <?php if ($discount > 0): ?>

                        <div class="summary-row text-success">

                            <span>
                                Coupon Discount
                            </span>

                            <span>
                                - ₹<?= number_format($discount, 2) ?>
                            </span>

                        </div>

                    <?php endif; ?>


                    <div class="summary-row">

                        <span>
                            Shipping
                        </span>

                        <span>
                            ₹<?= number_format($shipping, 2) ?>
                        </span>

                    </div>


                    <hr>


                    <div class="summary-row total-row">

                        <span>
                            Total
                        </span>

                        <span>
                            ₹<?= number_format($total, 2) ?>
                        </span>

                    </div>


                    <!-- Final Order Form -->

                    <?php if (!empty($addresses)): ?>

                        <form
                            method="POST"
                            action="place-order.php"
                            class="mt-4"
                        >

                            <input
                                type="hidden"
                                name="csrf_token"
                                value="<?= e(csrfToken()) ?>"
                            >

                            <input
                                type="hidden"
                                name="address_id"
                                id="selectedAddress"
                                value="<?= (int) ($addresses[0]['id'] ?? 0) ?>"
                            >

                            <input
                                type="hidden"
                                name="payment_method"
                                id="selectedPayment"
                                value="cod"
                            >

                            <button
                                type="submit"
                                class="btn btn-primary btn-lg w-100"
                            >
                                Place Order
                            </button>

                        </form>

                    <?php endif; ?>


                    <a
                        href="cart.php"
                        class="btn btn-outline-secondary w-100 mt-2"
                    >
                        Back to Cart
                    </a>

                </div>

            </div>

        </div>

    </div>

</div>


<script>

function selectAddress(id)
{
    document.getElementById('selectedAddress').value = id;
}


function selectPayment(method)
{
    document.getElementById('selectedPayment').value = method;

    document
        .getElementById('codOption')
        .classList.remove('active');

    document
        .getElementById('razorpayOption')
        .classList.remove('active');

    if (method === 'cod') {

        document
            .getElementById('codOption')
            .classList.add('active');

        document.getElementById('cod').checked = true;

    } else {

        document
            .getElementById('razorpayOption')
            .classList.add('active');

        document.getElementById('razorpay').checked = true;
    }
}

</script>

</body>

</html>