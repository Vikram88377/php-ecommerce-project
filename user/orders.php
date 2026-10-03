<?php

require_once "../config/database.php";
require_once "../includes/security.php";

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}


/*
|--------------------------------------------------------------------------
| User
|--------------------------------------------------------------------------
*/

$userId = $_SESSION['user_id'] ?? 1;


/*
|--------------------------------------------------------------------------
| Get Orders
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
    WHERE user_id = ?
    ORDER BY id DESC
");

$stmt->execute([$userId]);

$orders = $stmt->fetchAll();

?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1"
    >

    <title>My Orders</title>

    <link
        href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
        rel="stylesheet"
    >

</head>

<body>

<div class="container py-5">

    <!-- Header -->

    <div class="d-flex justify-content-between align-items-center mb-4">

        <h2 class="fw-bold mb-0">
            My Orders
        </h2>

        <a
            href="products.php"
            class="btn btn-outline-dark"
        >
            Continue Shopping
        </a>

    </div>


    <?php if (empty($orders)): ?>

        <!-- No Orders -->

        <div class="card border-0 shadow-sm">

            <div class="card-body text-center py-5">

                <h4 class="text-muted">
                    You have no orders yet.
                </h4>

                <p class="text-muted">
                    Start shopping and place your first order.
                </p>

                <a
                    href="products.php"
                    class="btn btn-dark"
                >
                    Browse Products
                </a>

            </div>

        </div>


    <?php else: ?>

        <!-- Orders Table -->

        <div class="card border-0 shadow-sm">

            <div class="card-body p-0">

                <div class="table-responsive">

                    <table class="table table-hover align-middle mb-0">

                        <thead class="table-light">

                            <tr>

                                <th class="px-4">
                                    Order #
                                </th>

                                <th>
                                    Date
                                </th>

                                <th>
                                    Total
                                </th>

                                <th>
                                    Order Status
                                </th>

                                <th>
                                    Payment
                                </th>

                                <th>
                                    Action
                                </th>

                            </tr>

                        </thead>


                        <tbody>

                        <?php foreach ($orders as $order): ?>

                            <tr>

                                <!-- Order Number -->

                                <td class="px-4">

                                    <strong>
                                        <?= e($order['order_number']); ?>
                                    </strong>

                                </td>


                                <!-- Date -->

                                <td>

                                    <?= date(
                                        'd M Y',
                                        strtotime($order['created_at'])
                                    ); ?>

                                </td>


                                <!-- Total -->

                                <td>

                                    <strong>
                                        ₹<?= number_format(
                                            $order['total_amount'],
                                            2
                                        ); ?>
                                    </strong>

                                </td>


                                <!-- Order Status -->

                                <td>

                                    <?php

                                    $statusClass = 'bg-secondary';

                                    if ($order['status'] === 'pending') {
                                        $statusClass = 'bg-warning text-dark';
                                    } elseif ($order['status'] === 'confirmed') {
                                        $statusClass = 'bg-info text-dark';
                                    } elseif ($order['status'] === 'processing') {
                                        $statusClass = 'bg-primary';
                                    } elseif ($order['status'] === 'shipped') {
                                        $statusClass = 'bg-dark';
                                    } elseif ($order['status'] === 'delivered') {
                                        $statusClass = 'bg-success';
                                    } elseif ($order['status'] === 'cancelled') {
                                        $statusClass = 'bg-danger';
                                    }

                                    ?>

                                    <span class="badge <?= $statusClass; ?>">

                                        <?= e(
                                            ucfirst($order['status'])
                                        ); ?>

                                    </span>

                                </td>


                                <!-- Payment Status -->

                                <td>

                                    <?php

                                    $paymentClass = 'bg-secondary';

                                    if ($order['payment_status'] === 'paid') {
                                        $paymentClass = 'bg-success';
                                    } elseif ($order['payment_status'] === 'failed') {
                                        $paymentClass = 'bg-danger';
                                    } elseif ($order['payment_status'] === 'refunded') {
                                        $paymentClass = 'bg-dark';
                                    } elseif ($order['payment_status'] === 'pending') {
                                        $paymentClass = 'bg-warning text-dark';
                                    }

                                    ?>

                                    <span class="badge <?= $paymentClass; ?>">

                                        <?= e(
                                            ucfirst($order['payment_status'])
                                        ); ?>

                                    </span>

                                </td>


                                <!-- Action -->

                                <td>

                                    <a
                                        href="order-details.php?id=<?= $order['id']; ?>"
                                        class="btn btn-sm btn-outline-dark"
                                    >
                                        View
                                    </a>

                                </td>

                            </tr>

                        <?php endforeach; ?>

                        </tbody>

                    </table>

                </div>

            </div>

        </div>

    <?php endif; ?>

</div>

</body>

</html>