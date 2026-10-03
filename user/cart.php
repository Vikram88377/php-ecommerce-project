<?php

require_once "../config/database.php";
require_once "../includes/security.php";

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$cart = $_SESSION['cart'] ?? [];

$products = [];
$grandTotal = 0;

if (!empty($cart)) {

    $productIds = array_keys($cart);

    $placeholders = implode(',', array_fill(0, count($productIds), '?'));

    $stmt = $pdo->prepare("
        SELECT
            p.id,
            p.name,
            p.price,
            p.sale_price,
            p.stock,
            pi.image
        FROM products p
        LEFT JOIN product_images pi
            ON pi.product_id = p.id
            AND pi.is_primary = 1
        WHERE p.id IN ($placeholders)
        AND p.status = 'active'
    ");

    $stmt->execute($productIds);

    $products = $stmt->fetchAll();
}

?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta name="viewport" content="width=device-width, initial-scale=1">

    <title>Shopping Cart</title>

    <link
        href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
        rel="stylesheet"
    >

</head>

<body>

<div class="container py-5">

    <div class="d-flex justify-content-between align-items-center mb-4">

        <h2 class="fw-bold">
            Shopping Cart
        </h2>

        <a href="products.php" class="btn btn-outline-dark">
            Continue Shopping
        </a>

    </div>


    <?php if (empty($products)): ?>

        <div class="text-center py-5">

            <h4 class="text-muted">
                Your cart is empty.
            </h4>

            <a href="products.php" class="btn btn-dark mt-3">
                Browse Products
            </a>

        </div>

    <?php else: ?>


        <div class="row">

            <!-- Cart Items -->

            <div class="col-lg-8">

                <?php foreach ($products as $product): ?>

                    <?php

                    $productId = $product['id'];

                    $quantity = $cart[$productId] ?? 1;

                    $price = !empty($product['sale_price'])
                        ? $product['sale_price']
                        : $product['price'];

                    $itemTotal = $price * $quantity;

                    $grandTotal += $itemTotal;

                    $imagePath = !empty($product['image'])
                        ? "../admin/products/uploads/" . $product['image']
                        : null;

                    ?>

                    <div class="card border-0 shadow-sm mb-3">

                        <div class="card-body">

                            <div class="row align-items-center">

                                <!-- Product Image -->

                                <div class="col-md-2 mb-3 mb-md-0">

                                    <?php if ($imagePath): ?>

                                        <img
                                            src="<?= e($imagePath); ?>"
                                            alt="<?= e($product['name']); ?>"
                                            class="img-fluid rounded"
                                            style="height: 100px; width: 100%; object-fit: contain;"
                                        >

                                    <?php else: ?>

                                        <div
                                            class="bg-light rounded d-flex align-items-center justify-content-center"
                                            style="height: 100px;"
                                        >
                                            <small class="text-muted">
                                                No Image
                                            </small>
                                        </div>

                                    <?php endif; ?>

                                </div>


                                <!-- Product Details -->

                                <div class="col-md-4">

                                    <h5 class="fw-bold mb-1">
                                        <?= e($product['name']); ?>
                                    </h5>

                                    <p class="text-muted mb-1">
                                        ₹<?= number_format($price, 2); ?>
                                    </p>

                                    <?php if (!empty($product['sale_price'])): ?>

                                        <small class="text-muted text-decoration-line-through">
                                            ₹<?= number_format($product['price'], 2); ?>
                                        </small>

                                    <?php endif; ?>

                                </div>


                                <!-- Quantity -->

                                <div class="col-md-3">

                                    <form
                                        action="update-cart.php"
                                        method="POST"
                                    >

                                        <input
                                            type="hidden"
                                            name="csrf_token"
                                            value="<?= csrfToken(); ?>"
                                        >

                                        <input
                                            type="hidden"
                                            name="product_id"
                                            value="<?= $productId; ?>"
                                        >

                                        <label class="form-label small text-muted">
                                            Quantity
                                        </label>

                                        <input
                                            type="number"
                                            name="quantity"
                                            value="<?= $quantity; ?>"
                                            min="1"
                                            max="<?= $product['stock']; ?>"
                                            class="form-control"
                                            onchange="this.form.submit()"
                                        >

                                    </form>

                                </div>


                                <!-- Item Total -->

                                <div class="col-md-2 text-md-end mt-3 mt-md-0">

                                    <strong>
                                        ₹<?= number_format($itemTotal, 2); ?>
                                    </strong>

                                </div>


                                <!-- Remove -->

                                <div class="col-md-1 text-md-end mt-3 mt-md-0">

                                    <form
                                        action="remove-from-cart.php"
                                        method="POST"
                                    >

                                        <input
                                            type="hidden"
                                            name="csrf_token"
                                            value="<?= csrfToken(); ?>"
                                        >

                                        <input
                                            type="hidden"
                                            name="product_id"
                                            value="<?= $productId; ?>"
                                        >

                                        <button
                                            type="submit"
                                            class="btn btn-sm btn-outline-danger"
                                            title="Remove"
                                        >
                                            ×
                                        </button>

                                    </form>

                                </div>

                            </div>

                        </div>

                    </div>

                <?php endforeach; ?>

            </div>


            <!-- Cart Summary -->

            <div class="col-lg-4">

                <div class="card border-0 shadow-sm">

                    <div class="card-body p-4">

                        <h4 class="fw-bold mb-4">
                            Order Summary
                        </h4>


                        <div class="d-flex justify-content-between mb-3">

                            <span>
                                Subtotal
                            </span>

                            <span>
                                ₹<?= number_format($grandTotal, 2); ?>
                            </span>

                        </div>


                        <div class="d-flex justify-content-between mb-3">

                            <span>
                                Shipping
                            </span>

                            <span class="text-success">
                                Free
                            </span>

                        </div>


                        <hr>


                        <div class="d-flex justify-content-between mb-4">

                            <strong>
                                Total
                            </strong>

                            <strong class="fs-5">
                                ₹<?= number_format($grandTotal, 2); ?>
                            </strong>

                        </div>


                        <!-- Checkout -->

                        <a
                            href="checkout.php"
                            class="btn btn-dark w-100"
                        >
                            Proceed to Checkout
                        </a>


                        <a
                            href="products.php"
                            class="btn btn-outline-secondary w-100 mt-2"
                        >
                            Continue Shopping
                        </a>

                    </div>

                </div>

            </div>

        </div>

    <?php endif; ?>

</div>

</body>

</html>