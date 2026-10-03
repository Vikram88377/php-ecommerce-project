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
| Get Coupons
|--------------------------------------------------------------------------
*/

$stmt = $pdo->query("
    SELECT
        id,
        code,
        type,
        value,
        minimum_amount,
        maximum_discount,
        usage_limit,
        used_count,
        starts_at,
        expires_at,
        status
    FROM coupons
    ORDER BY id DESC
");

$coupons = $stmt->fetchAll();

?>
<!DOCTYPE html>
<html lang="en">

<head>

<meta charset="UTF-8">

<meta
    name="viewport"
    content="width=device-width, initial-scale=1"
>

<title>Manage Coupons</title>

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
                Coupons
            </h2>

            <p class="text-muted mb-0">
                Manage discount coupons
            </p>

        </div>


        <div class="d-flex gap-2">

            <a
                href="create.php"
                class="btn btn-dark"
            >
                + Create Coupon
            </a>

            <a
                href="../dashboard.php"
                class="btn btn-outline-secondary"
            >
                Dashboard
            </a>

        </div>

    </div>


    <?php if (isset($_GET['success'])): ?>

        <?php if ($_GET['success'] === 'created'): ?>

            <div class="alert alert-success">
                Coupon created successfully.
            </div>

        <?php elseif ($_GET['success'] === 'updated'): ?>

            <div class="alert alert-success">
                Coupon updated successfully.
            </div>

        <?php elseif ($_GET['success'] === 'deleted'): ?>

            <div class="alert alert-success">
                Coupon deleted successfully.
            </div>

        <?php elseif ($_GET['success'] === 'status'): ?>

            <div class="alert alert-success">
                Coupon status updated successfully.
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

                            <th>Code</th>

                            <th>Type</th>

                            <th>Value</th>

                            <th>Minimum</th>

                            <th>Max Discount</th>

                            <th>Usage</th>

                            <th>Validity</th>

                            <th>Status</th>

                            <th>Action</th>

                        </tr>

                    </thead>


                    <tbody>

                    <?php if (empty($coupons)): ?>

                        <tr>

                            <td
                                colspan="10"
                                class="text-center py-4"
                            >
                                No coupons found.
                            </td>

                        </tr>

                    <?php else: ?>

                        <?php foreach ($coupons as $coupon): ?>

                            <tr>

                                <td>
                                    <?= (int) $coupon['id']; ?>
                                </td>


                                <td>

                                    <strong>
                                        <?= e($coupon['code']); ?>
                                    </strong>

                                </td>


                                <td>

                                    <?php if (
                                        $coupon['type'] === 'percentage'
                                    ): ?>

                                        <span class="badge bg-primary">
                                            Percentage
                                        </span>

                                    <?php else: ?>

                                        <span class="badge bg-secondary">
                                            Fixed
                                        </span>

                                    <?php endif; ?>

                                </td>


                                <td>

                                    <?php if (
                                        $coupon['type'] === 'percentage'
                                    ): ?>

                                        <?= number_format(
                                            $coupon['value'],
                                            2
                                        ); ?>%

                                    <?php else: ?>

                                        ₹<?= number_format(
                                            $coupon['value'],
                                            2
                                        ); ?>

                                    <?php endif; ?>

                                </td>


                                <td>

                                    ₹<?= number_format(
                                        $coupon['minimum_amount'],
                                        2
                                    ); ?>

                                </td>


                                <td>

                                    <?php if (
                                        $coupon['maximum_discount'] !== null
                                    ): ?>

                                        ₹<?= number_format(
                                            $coupon['maximum_discount'],
                                            2
                                        ); ?>

                                    <?php else: ?>

                                        <span class="text-muted">
                                            No Limit
                                        </span>

                                    <?php endif; ?>

                                </td>


                                <td>

                                    <?= (int) $coupon['used_count']; ?>

                                    /

                                    <?php if (
                                        $coupon['usage_limit']
                                        !== null
                                    ): ?>

                                        <?= (int) $coupon['usage_limit']; ?>

                                    <?php else: ?>

                                        ∞

                                    <?php endif; ?>

                                </td>


                                <td>

                                    <small>

                                        <?= date(
                                            'd M Y',
                                            strtotime($coupon['starts_at'])
                                        ); ?>

                                        <br>

                                        <?php if ($coupon['expires_at']): ?>

                                            to
                                            <?= date(
                                                'd M Y',
                                                strtotime($coupon['expires_at'])
                                            ); ?>

                                        <?php else: ?>

                                            No expiry

                                        <?php endif; ?>

                                    </small>

                                </td>


                                <td>

                                    <?php if (
                                        $coupon['status'] === 'active'
                                    ): ?>

                                        <span class="badge bg-success">
                                            Active
                                        </span>

                                    <?php else: ?>

                                        <span class="badge bg-danger">
                                            Inactive
                                        </span>

                                    <?php endif; ?>

                                </td>


                                <td>

                                    <div class="d-flex gap-1">

                                        <a
                                            href="edit.php?id=<?= (int) $coupon['id']; ?>"
                                            class="btn btn-sm btn-outline-primary"
                                        >
                                            Edit
                                        </a>


                                        <form
                                            action="toggle-status.php"
                                            method="POST"
                                        >

                                            <input
                                                type="hidden"
                                                name="csrf_token"
                                                value="<?= csrfToken(); ?>"
                                            >

                                            <input
                                                type="hidden"
                                                name="id"
                                                value="<?= (int) $coupon['id']; ?>"
                                            >

                                            <button
                                                type="submit"
                                                class="btn btn-sm btn-outline-warning"
                                            >
                                                Toggle
                                            </button>

                                        </form>


                                        <form
                                            action="delete.php"
                                            method="POST"
                                            onsubmit="return confirm('Delete this coupon?');"
                                        >

                                            <input
                                                type="hidden"
                                                name="csrf_token"
                                                value="<?= csrfToken(); ?>"
                                            >

                                            <input
                                                type="hidden"
                                                name="id"
                                                value="<?= (int) $coupon['id']; ?>"
                                            >

                                            <button
                                                type="submit"
                                                class="btn btn-sm btn-outline-danger"
                                            >
                                                Delete
                                            </button>

                                        </form>

                                    </div>

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