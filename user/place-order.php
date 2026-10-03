<?php

require_once "../config/database.php";
require_once "../includes/security.php";
require_once "../includes/coupon.php";

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
    header("Location: checkout.php");
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
| Request Data
|--------------------------------------------------------------------------
*/

$addressId = (int) ($_POST['address_id'] ?? 0);

$paymentMethod = $_POST['payment_method'] ?? '';

if ($addressId <= 0) {
    die("Please select a valid address.");
}

if (!in_array($paymentMethod, ['cod', 'razorpay'], true)) {
    die("Invalid payment method.");
}

/*
|--------------------------------------------------------------------------
| Cart
|--------------------------------------------------------------------------
*/

$cart = $_SESSION['cart'] ?? [];

if (empty($cart)) {
    header("Location: cart.php");
    exit;
}

try {

    $pdo->beginTransaction();

    /*
    |--------------------------------------------------------------------------
    | Validate Address
    |--------------------------------------------------------------------------
    */

    $addressStmt = $pdo->prepare("
        SELECT id
        FROM addresses
        WHERE id = ?
        AND user_id = ?
        LIMIT 1
    ");

    $addressStmt->execute([
        $addressId,
        $userId
    ]);

    $address = $addressStmt->fetch();

    if (!$address) {
        throw new Exception("Invalid delivery address.");
    }

    /*
    |--------------------------------------------------------------------------
    | Get Product IDs
    |--------------------------------------------------------------------------
    */

    $productIds = array_keys($cart);

    if (empty($productIds)) {
        throw new Exception("Your cart is empty.");
    }

    $placeholders = implode(
        ',',
        array_fill(0, count($productIds), '?')
    );

    /*
    |--------------------------------------------------------------------------
    | Lock Products
    |--------------------------------------------------------------------------
    */

    $productStmt = $pdo->prepare("
        SELECT
            id,
            name,
            sku,
            price,
            sale_price,
            stock,
            status
        FROM products
        WHERE id IN ($placeholders)
        FOR UPDATE
    ");

    $productStmt->execute($productIds);

    $products = $productStmt->fetchAll();

    if (count($products) !== count($productIds)) {
        throw new Exception("One or more products are unavailable.");
    }

    /*
    |--------------------------------------------------------------------------
    | Calculate Subtotal
    |--------------------------------------------------------------------------
    */

    $subtotal = 0;

    $orderItems = [];

    foreach ($products as $product) {

        $productId = (int) $product['id'];

        $quantity = (int) ($cart[$productId] ?? 0);

        if ($quantity <= 0) {
            throw new Exception("Invalid product quantity.");
        }

        /*
        |--------------------------------------------------------------------------
        | Product Status
        |--------------------------------------------------------------------------
        */

        if ($product['status'] !== 'active') {
            throw new Exception(
                "Product is no longer available: "
                . $product['name']
            );
        }

        /*
        |--------------------------------------------------------------------------
        | Stock
        |--------------------------------------------------------------------------
        */

        if ($quantity > (int) $product['stock']) {
            throw new Exception(
                "Insufficient stock for: "
                . $product['name']
            );
        }

        /*
        |--------------------------------------------------------------------------
        | Price
        |--------------------------------------------------------------------------
        */

        $price = $product['sale_price'] !== null
            ? (float) $product['sale_price']
            : (float) $product['price'];

        $itemTotal = $price * $quantity;

        $subtotal += $itemTotal;

        $orderItems[] = [
            'product_id' => $productId,
            'product_name' => $product['name'],
            'product_sku' => $product['sku'],
            'price' => $price,
            'quantity' => $quantity,
            'total' => $itemTotal
        ];
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

    $couponId = null;
    $discount = 0;

    if (!empty($_SESSION['coupon']['id'])) {

        /*
        |--------------------------------------------------------------------------
        | Lock Coupon Row
        |--------------------------------------------------------------------------
        */

        $couponStmt = $pdo->prepare("
            SELECT *
            FROM coupons
            WHERE id = ?
            FOR UPDATE
        ");

        $couponStmt->execute([
            (int) $_SESSION['coupon']['id']
        ]);

        $coupon = $couponStmt->fetch();

        if (!$coupon) {
            throw new Exception("Coupon not found.");
        }

        /*
        |--------------------------------------------------------------------------
        | Coupon Status
        |--------------------------------------------------------------------------
        */

        if ($coupon['status'] !== 'active') {
            throw new Exception("This coupon is no longer active.");
        }

        /*
        |--------------------------------------------------------------------------
        | Start Date
        |--------------------------------------------------------------------------
        */

        if (
            !empty($coupon['starts_at']) &&
            strtotime($coupon['starts_at']) > time()
        ) {
            throw new Exception("This coupon is not active yet.");
        }

        /*
        |--------------------------------------------------------------------------
        | Expiry
        |--------------------------------------------------------------------------
        */

        if (
            !empty($coupon['expires_at']) &&
            strtotime($coupon['expires_at']) < time()
        ) {
            throw new Exception("This coupon has expired.");
        }

        /*
        |--------------------------------------------------------------------------
        | Usage Limit
        |--------------------------------------------------------------------------
        */

        if (
            $coupon['usage_limit'] !== null &&
            (int) $coupon['used_count'] >=
            (int) $coupon['usage_limit']
        ) {
            throw new Exception(
                "This coupon usage limit has been reached."
            );
        }

        /*
        |--------------------------------------------------------------------------
        | Minimum Amount
        |--------------------------------------------------------------------------
        */

        if (
            $subtotal <
            (float) $coupon['minimum_amount']
        ) {
            throw new Exception(
                "Minimum order amount for this coupon is ₹"
                . number_format(
                    $coupon['minimum_amount'],
                    2
                )
            );
        }

        /*
        |--------------------------------------------------------------------------
        | Calculate Discount
        |--------------------------------------------------------------------------
        */

        if ($coupon['type'] === 'percentage') {

            $discount =
                ($subtotal * (float) $coupon['value'])
                / 100;

            /*
            |--------------------------------------------------------------------------
            | Maximum Discount
            |--------------------------------------------------------------------------
            */

            if (
                $coupon['maximum_discount'] !== null &&
                $discount >
                (float) $coupon['maximum_discount']
            ) {
                $discount =
                    (float) $coupon['maximum_discount'];
            }

        } else {

            /*
            |--------------------------------------------------------------------------
            | Fixed Discount
            |--------------------------------------------------------------------------
            */

            $discount = (float) $coupon['value'];
        }

        /*
        |--------------------------------------------------------------------------
        | Discount Cannot Exceed Subtotal
        |--------------------------------------------------------------------------
        */

        if ($discount > $subtotal) {
            $discount = $subtotal;
        }

        $discount = round($discount, 2);

        $couponId = (int) $coupon['id'];
    }

    /*
    |--------------------------------------------------------------------------
    | Final Total
    |--------------------------------------------------------------------------
    */

    $totalAmount =
        $subtotal
        - $discount
        + $shipping;

    if ($totalAmount < 0) {
        $totalAmount = 0;
    }

    /*
    |--------------------------------------------------------------------------
    | Generate Order Number
    |--------------------------------------------------------------------------
    */

    $orderNumber =
        'ORD-'
        . date('YmdHis')
        . '-'
        . strtoupper(
            bin2hex(random_bytes(3))
        );

    /*
    |--------------------------------------------------------------------------
    | Create Order
    |--------------------------------------------------------------------------
    */

    $orderStmt = $pdo->prepare("
        INSERT INTO orders (
            user_id,
            address_id,
            coupon_id,
            order_number,
            subtotal,
            discount,
            shipping_amount,
            total_amount,
            status,
            payment_status,
            created_at,
            updated_at
        )
        VALUES (
            ?,
            ?,
            ?,
            ?,
            ?,
            ?,
            ?,
            ?,
            ?,
            ?,
            NOW(),
            NOW()
        )
    ");

    /*
    |--------------------------------------------------------------------------
    | Initial Order Status
    |--------------------------------------------------------------------------
    */

    if ($paymentMethod === 'cod') {

        $orderStatus = 'confirmed';
        $paymentStatus = 'pending';

    } else {

        $orderStatus = 'pending';
        $paymentStatus = 'pending';
    }

    $orderStmt->execute([
        $userId,
        $addressId,
        $couponId,
        $orderNumber,
        $subtotal,
        $discount,
        $shipping,
        $totalAmount,
        $orderStatus,
        $paymentStatus
    ]);

    $orderId = (int) $pdo->lastInsertId();

    /*
    |--------------------------------------------------------------------------
    | Insert Order Items
    |--------------------------------------------------------------------------
    */

    $itemStmt = $pdo->prepare("
        INSERT INTO order_items (
            order_id,
            product_id,
            product_name,
            product_sku,
            price,
            quantity,
            total,
            created_at
        )
        VALUES (?, ?, ?, ?, ?, ?, ?, NOW())
    ");

    foreach ($orderItems as $item) {

        $itemStmt->execute([
            $orderId,
            $item['product_id'],
            $item['product_name'],
            $item['product_sku'],
            $item['price'],
            $item['quantity'],
            $item['total']
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | Reduce Product Stock
    |--------------------------------------------------------------------------
    */

    $stockStmt = $pdo->prepare("
        UPDATE products
        SET
            stock = stock - ?,
            status = CASE
                WHEN stock - ? <= 0
                THEN 'out_of_stock'
                ELSE status
            END,
            updated_at = NOW()
        WHERE id = ?
    ");

    foreach ($orderItems as $item) {

        $stockStmt->execute([
            $item['quantity'],
            $item['quantity'],
            $item['product_id']
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | Coupon Usage
    |--------------------------------------------------------------------------
    */

    if ($couponId !== null) {

        /*
        |--------------------------------------------------------------------------
        | Save Coupon Usage
        |--------------------------------------------------------------------------
        */

        $usageStmt = $pdo->prepare("
            INSERT INTO coupon_usages (
                coupon_id,
                user_id,
                order_id,
                created_at
            )
            VALUES (?, ?, ?, NOW())
        ");

        $usageStmt->execute([
            $couponId,
            $userId,
            $orderId
        ]);

        /*
        |--------------------------------------------------------------------------
        | Increase Used Count
        |--------------------------------------------------------------------------
        */

        $couponUpdateStmt = $pdo->prepare("
            UPDATE coupons
            SET used_count = used_count + 1,
                updated_at = NOW()
            WHERE id = ?
        ");

        $couponUpdateStmt->execute([
            $couponId
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | Order Status History
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
        VALUES (?, ?, ?, ?, NOW())
    ");

    if ($paymentMethod === 'cod') {

        $historyStmt->execute([
            $orderId,
            'confirmed',
            'Order placed using Cash on Delivery.',
            $userId
        ]);

    } else {

        $historyStmt->execute([
            $orderId,
            'pending',
            'Order created. Awaiting Razorpay payment.',
            $userId
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | Clear Coupon From Session
    |--------------------------------------------------------------------------
    */

    unset($_SESSION['coupon']);

    /*
    |--------------------------------------------------------------------------
    | COD
    |--------------------------------------------------------------------------
    */

    if ($paymentMethod === 'cod') {

        $_SESSION['cart'] = [];

        $pdo->commit();

        header(
            "Location: order-success.php?order_id="
            . $orderId
        );

        exit;
    }

    /*
    |--------------------------------------------------------------------------
    | Razorpay
    |--------------------------------------------------------------------------
    */

    $_SESSION['cart'] = [];

    $pdo->commit();

    header(
        "Location: create-payment.php?order_id="
        . $orderId
    );

    exit;

} catch (Throwable $e) {

    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }

    die(
        "Unable to place order: "
        . e($e->getMessage())
    );
}