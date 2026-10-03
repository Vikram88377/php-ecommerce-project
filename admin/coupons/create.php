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


$error = '';


/*
|--------------------------------------------------------------------------
| Handle Form
|--------------------------------------------------------------------------
*/

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) {
        die("Invalid CSRF token.");
    }


    $code = strtoupper(trim($_POST['code'] ?? ''));

    $type = $_POST['type'] ?? '';

    $value = (float) ($_POST['value'] ?? 0);

    $minimumAmount = (float) (
        $_POST['minimum_amount'] ?? 0
    );

    $maximumDiscount = trim(
        $_POST['maximum_discount'] ?? ''
    );

    $usageLimit = trim(
        $_POST['usage_limit'] ?? ''
    );

    $startsAt = $_POST['starts_at'] ?? '';

    $expiresAt = $_POST['expires_at'] ?? '';

    $status = $_POST['status'] ?? 'active';


    /*
    |--------------------------------------------------------------------------
    | Validation
    |--------------------------------------------------------------------------
    */

    if ($code === '') {

        $error = "Coupon code is required.";

    } elseif (!preg_match('/^[A-Z0-9_-]+$/', $code)) {

        $error = "Coupon code contains invalid characters.";

    } elseif (!in_array(
        $type,
        ['fixed', 'percentage'],
        true
    )) {

        $error = "Invalid coupon type.";

    } elseif ($value <= 0) {

        $error = "Coupon value must be greater than zero.";

    } elseif (
        $type === 'percentage'
        && $value > 100
    ) {

        $error = "Percentage cannot be greater than 100.";

    } elseif ($minimumAmount < 0) {

        $error = "Minimum amount cannot be negative.";

    } elseif (
        $maximumDiscount !== ''
        && (float) $maximumDiscount < 0
    ) {

        $error = "Maximum discount cannot be negative.";

    } elseif (
        $usageLimit !== ''
        && (int) $usageLimit <= 0
    ) {

        $error = "Usage limit must be greater than zero.";

    } elseif ($startsAt === '') {

        $error = "Start date is required.";

    } elseif (
        $expiresAt !== ''
        && strtotime($expiresAt) < strtotime($startsAt)
    ) {

        $error = "Expiry date cannot be before start date.";

    }


    /*
    |--------------------------------------------------------------------------
    | Check Duplicate Code
    |--------------------------------------------------------------------------
    */

    if ($error === '') {

        $checkStmt = $pdo->prepare("
            SELECT id
            FROM coupons
            WHERE code = ?
            LIMIT 1
        ");

        $checkStmt->execute([$code]);

        if ($checkStmt->fetch()) {

            $error = "Coupon code already exists.";

        }

    }


    /*
    |--------------------------------------------------------------------------
    | Insert Coupon
    |--------------------------------------------------------------------------
    */

    if ($error === '') {

        $maximumDiscountValue =
            $maximumDiscount === ''
                ? null
                : (float) $maximumDiscount;

        $usageLimitValue =
            $usageLimit === ''
                ? null
                : (int) $usageLimit;

        $expiresAtValue =
            $expiresAt === ''
                ? null
                : $expiresAt;


        $stmt = $pdo->prepare("
            INSERT INTO coupons (
                code,
                type,
                value,
                minimum_amount,
                maximum_discount,
                usage_limit,
                used_count,
                starts_at,
                expires_at,
                status,
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
                0,
                ?,
                ?,
                ?,
                NOW(),
                NOW()
            )
        ");

        $stmt->execute([
            $code,
            $type,
            $value,
            $minimumAmount,
            $maximumDiscountValue,
            $usageLimitValue,
            $startsAt,
            $expiresAtValue,
            $status
        ]);


        header(
            "Location: index.php?success=created"
        );

        exit;
    }
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

<title>Create Coupon</title>

<link
    href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
    rel="stylesheet"
>

</head>

<body class="bg-light">

<div class="container py-5">

    <div class="row justify-content-center">

        <div class="col-lg-7">

            <div class="d-flex justify-content-between align-items-center mb-4">

                <h2 class="fw-bold">
                    Create Coupon
                </h2>

                <a
                    href="index.php"
                    class="btn btn-outline-secondary"
                >
                    Back
                </a>

            </div>


            <div class="card border-0 shadow-sm">

                <div class="card-body p-4">


                    <?php if ($error): ?>

                        <div class="alert alert-danger">
                            <?= e($error); ?>
                        </div>

                    <?php endif; ?>


                    <form method="POST">

                        <input
                            type="hidden"
                            name="csrf_token"
                            value="<?= csrfToken(); ?>"
                        >


                        <div class="mb-3">

                            <label class="form-label">
                                Coupon Code
                            </label>

                            <input
                                type="text"
                                name="code"
                                class="form-control"
                                placeholder="SAVE20"
                                value="<?= e($_POST['code'] ?? ''); ?>"
                                required
                            >

                        </div>


                        <div class="mb-3">

                            <label class="form-label">
                                Discount Type
                            </label>

                            <select
                                name="type"
                                class="form-select"
                                required
                            >

                                <option value="">
                                    Select Type
                                </option>

                                <option
                                    value="fixed"
                                    <?= (
                                        ($_POST['type'] ?? '')
                                        === 'fixed'
                                    )
                                        ? 'selected'
                                        : ''
                                    ?>
                                >
                                    Fixed Amount
                                </option>

                                <option
                                    value="percentage"
                                    <?= (
                                        ($_POST['type'] ?? '')
                                        === 'percentage'
                                    )
                                        ? 'selected'
                                        : ''
                                    ?>
                                >
                                    Percentage
                                </option>

                            </select>

                        </div>


                        <div class="mb-3">

                            <label class="form-label">
                                Discount Value
                            </label>

                            <input
                                type="number"
                                name="value"
                                class="form-control"
                                step="0.01"
                                min="0.01"
                                value="<?= e($_POST['value'] ?? ''); ?>"
                                required
                            >

                        </div>


                        <div class="mb-3">

                            <label class="form-label">
                                Minimum Order Amount
                            </label>

                            <input
                                type="number"
                                name="minimum_amount"
                                class="form-control"
                                step="0.01"
                                min="0"
                                value="<?= e($_POST['minimum_amount'] ?? '0'); ?>"
                            >

                        </div>


                        <div class="mb-3">

                            <label class="form-label">
                                Maximum Discount
                            </label>

                            <input
                                type="number"
                                name="maximum_discount"
                                class="form-control"
                                step="0.01"
                                min="0"
                                placeholder="Leave blank for no limit"
                                value="<?= e($_POST['maximum_discount'] ?? ''); ?>"
                            >

                        </div>


                        <div class="mb-3">

                            <label class="form-label">
                                Usage Limit
                            </label>

                            <input
                                type="number"
                                name="usage_limit"
                                class="form-control"
                                min="1"
                                placeholder="Leave blank for unlimited"
                                value="<?= e($_POST['usage_limit'] ?? ''); ?>"
                            >

                        </div>


                        <div class="row">

                            <div class="col-md-6 mb-3">

                                <label class="form-label">
                                    Starts At
                                </label>

                                <input
                                    type="datetime-local"
                                    name="starts_at"
                                    class="form-control"
                                    value="<?= e($_POST['starts_at'] ?? date('Y-m-d\TH:i')); ?>"
                                    required
                                >

                            </div>


                            <div class="col-md-6 mb-3">

                                <label class="form-label">
                                    Expires At
                                </label>

                                <input
                                    type="datetime-local"
                                    name="expires_at"
                                    class="form-control"
                                    value="<?= e($_POST['expires_at'] ?? ''); ?>"
                                >

                            </div>

                        </div>


                        <div class="mb-4">

                            <label class="form-label">
                                Status
                            </label>

                            <select
                                name="status"
                                class="form-select"
                            >

                                <option value="active">
                                    Active
                                </option>

                                <option value="inactive">
                                    Inactive
                                </option>

                            </select>

                        </div>


                        <button
                            type="submit"
                            class="btn btn-dark w-100"
                        >
                            Create Coupon
                        </button>

                    </form>

                </div>

            </div>

        </div>

    </div>

</div>

</body>
</html>