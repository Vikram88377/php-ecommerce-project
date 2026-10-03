<?php

require_once "../../config/database.php";
require_once "../../includes/security.php";

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}


/*
|--------------------------------------------------------------------------
| Admin Authentication
|--------------------------------------------------------------------------
*/

$userId = $_SESSION['user_id'] ?? 0;
$userRole = $_SESSION['role'] ?? '';

if ($userId <= 0 || $userRole !== 'admin') {
    header("Location: ../login.php");
    exit;
}


/*
|--------------------------------------------------------------------------
| Get Reviews
|--------------------------------------------------------------------------
*/

$stmt = $pdo->prepare("
    SELECT
        r.id,
        r.rating,
        r.comment,
        r.status,
        r.created_at,

        u.name AS user_name,
        u.email AS user_email,

        p.name AS product_name,

        o.order_number

    FROM reviews r

    INNER JOIN users u
        ON u.id = r.user_id

    INNER JOIN products p
        ON p.id = r.product_id

    LEFT JOIN orders o
        ON o.id = r.order_id

    ORDER BY
        CASE
            WHEN r.status = 'pending' THEN 1
            WHEN r.status = 'approved' THEN 2
            WHEN r.status = 'rejected' THEN 3
        END,
        r.created_at DESC
");

$stmt->execute();

$reviews = $stmt->fetchAll();

?>
<!DOCTYPE html>
<html lang="en">

<head>

<meta charset="UTF-8">

<meta
    name="viewport"
    content="width=device-width, initial-scale=1"
>

<title>Manage Reviews</title>

<link
    href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
    rel="stylesheet"
>

</head>

<body class="bg-light">

<div class="container-fluid py-4">

    <div class="d-flex justify-content-between align-items-center mb-4">

        <div>

            <h2 class="fw-bold mb-1">
                Product Reviews
            </h2>

            <p class="text-muted mb-0">
                Manage customer product reviews
            </p>

        </div>

        <a
            href="../dashboard.php"
            class="btn btn-outline-dark"
        >
            ← Dashboard
        </a>

    </div>


    <?php if (isset($_GET['success'])): ?>

        <?php if ($_GET['success'] === 'approved'): ?>

            <div class="alert alert-success">
                Review approved successfully.
            </div>

        <?php elseif ($_GET['success'] === 'rejected'): ?>

            <div class="alert alert-success">
                Review rejected successfully.
            </div>

        <?php endif; ?>

    <?php endif; ?>


    <div class="card border-0 shadow-sm">

        <div class="card-body">

            <div class="table-responsive">

                <table class="table table-hover align-middle">

                    <thead class="table-dark">

                        <tr>

                            <th>#</th>

                            <th>Customer</th>

                            <th>Product</th>

                            <th>Order</th>

                            <th>Rating</th>

                            <th>Review</th>

                            <th>Status</th>

                            <th>Date</th>

                            <th>Action</th>

                        </tr>

                    </thead>


                    <tbody>

                    <?php if (empty($reviews)): ?>

                        <tr>

                            <td
                                colspan="9"
                                class="text-center py-4"
                            >
                                No reviews found.
                            </td>

                        </tr>

                    <?php else: ?>

                        <?php foreach ($reviews as $review): ?>

                            <tr>

                                <td>
                                    <?= (int) $review['id']; ?>
                                </td>


                                <td>

                                    <strong>
                                        <?= e($review['user_name']); ?>
                                    </strong>

                                    <br>

                                    <small class="text-muted">
                                        <?= e($review['user_email']); ?>
                                    </small>

                                </td>


                                <td>
                                    <?= e($review['product_name']); ?>
                                </td>


                                <td>

                                    <?php if ($review['order_number']): ?>

                                        <?= e($review['order_number']); ?>

                                    <?php else: ?>

                                        <span class="text-muted">
                                            N/A
                                        </span>

                                    <?php endif; ?>

                                </td>


                                <td>

                                    <span class="text-warning">

                                        <?= str_repeat(
                                            '★',
                                            (int) $review['rating']
                                        ); ?>

                                    </span>

                                    <br>

                                    <small>
                                        <?= (int) $review['rating']; ?>/5
                                    </small>

                                </td>


                                <td style="min-width: 250px;">

                                    <?= nl2br(
                                        e($review['comment'])
                                    ); ?>

                                </td>


                                <td>

                                    <?php if (
                                        $review['status'] === 'pending'
                                    ): ?>

                                        <span class="badge bg-warning text-dark">
                                            Pending
                                        </span>

                                    <?php elseif (
                                        $review['status'] === 'approved'
                                    ): ?>

                                        <span class="badge bg-success">
                                            Approved
                                        </span>

                                    <?php else: ?>

                                        <span class="badge bg-danger">
                                            Rejected
                                        </span>

                                    <?php endif; ?>

                                </td>


                                <td>

                                    <?= date(
                                        'd M Y',
                                        strtotime($review['created_at'])
                                    ); ?>

                                </td>


                                <td>

                                    <?php if (
                                        $review['status'] === 'pending'
                                    ): ?>

                                        <div class="d-flex gap-2">

                                            <form
                                                action="approve.php"
                                                method="POST"
                                            >

                                                <input
                                                    type="hidden"
                                                    name="csrf_token"
                                                    value="<?= csrfToken(); ?>"
                                                >

                                                <input
                                                    type="hidden"
                                                    name="review_id"
                                                    value="<?= (int) $review['id']; ?>"
                                                >

                                                <button
                                                    type="submit"
                                                    class="btn btn-sm btn-success"
                                                >
                                                    Approve
                                                </button>

                                            </form>


                                            <form
                                                action="reject.php"
                                                method="POST"
                                            >

                                                <input
                                                    type="hidden"
                                                    name="csrf_token"
                                                    value="<?= csrfToken(); ?>"
                                                >

                                                <input
                                                    type="hidden"
                                                    name="review_id"
                                                    value="<?= (int) $review['id']; ?>"
                                                >

                                                <button
                                                    type="submit"
                                                    class="btn btn-sm btn-danger"
                                                >
                                                    Reject
                                                </button>

                                            </form>

                                        </div>

                                    <?php else: ?>

                                        <span class="text-muted">
                                            No action
                                        </span>

                                    <?php endif; ?>

                                </td>

                            </tr>

                        <?php endforeach; ?>

                    <?php endif; ?>

                    </tbody>

                </table>

            </div>

        </div>

    </div>

</div>

</body>
</html>