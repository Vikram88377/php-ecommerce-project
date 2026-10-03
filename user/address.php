<?php

require_once "../config/database.php";
require_once "../includes/security.php";

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/*
|--------------------------------------------------------------------------
| User ID
|--------------------------------------------------------------------------
*/

$userId = $_SESSION['user_id'] ?? 1;

$errors = [];
$success = "";

/*
|--------------------------------------------------------------------------
| Add Address
|--------------------------------------------------------------------------
*/

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) {
        die("Invalid CSRF token.");
    }

    $name = trim($_POST['name'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $addressLine1 = trim($_POST['address_line1'] ?? '');
    $addressLine2 = trim($_POST['address_line2'] ?? '');
    $city = trim($_POST['city'] ?? '');
    $state = trim($_POST['state'] ?? '');
    $postalCode = trim($_POST['postal_code'] ?? '');
    $country = trim($_POST['country'] ?? 'India');

    /*
    |--------------------------------------------------------------------------
    | Validation
    |--------------------------------------------------------------------------
    */

    if ($name === '') {
        $errors[] = "Name is required.";
    }

    if ($phone === '') {
        $errors[] = "Phone is required.";
    }

    if ($addressLine1 === '') {
        $errors[] = "Address is required.";
    }

    if ($city === '') {
        $errors[] = "City is required.";
    }

    if ($state === '') {
        $errors[] = "State is required.";
    }

    if ($postalCode === '') {
        $errors[] = "Postal code is required.";
    }

    /*
    |--------------------------------------------------------------------------
    | Save Address
    |--------------------------------------------------------------------------
    */

    if (empty($errors)) {

        try {

            $pdo->beginTransaction();

            /*
            | Check existing addresses
            */

            $countStmt = $pdo->prepare("
                SELECT COUNT(*)
                FROM addresses
                WHERE user_id = ?
            ");

            $countStmt->execute([$userId]);

            $addressCount = (int) $countStmt->fetchColumn();

            /*
            | First address becomes default
            */

            $isDefault = ($addressCount === 0) ? 1 : 0;

            $stmt = $pdo->prepare("
                INSERT INTO addresses
                (
                    user_id,
                    name,
                    phone,
                    address_line1,
                    address_line2,
                    city,
                    state,
                    postal_code,
                    country,
                    is_default
                )
                VALUES
                (
                    ?, ?, ?, ?, ?, ?, ?, ?, ?, ?
                )
            ");

            $stmt->execute([
                $userId,
                $name,
                $phone,
                $addressLine1,
                $addressLine2 ?: null,
                $city,
                $state,
                $postalCode,
                $country ?: 'India',
                $isDefault
            ]);

            $pdo->commit();

            header("Location: checkout.php");
            exit;

        } catch (PDOException $e) {

            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }

            $errors[] = "Unable to save address.";
        }
    }
}

/*
|--------------------------------------------------------------------------
| Existing Addresses
|--------------------------------------------------------------------------
*/

$stmt = $pdo->prepare("
    SELECT *
    FROM addresses
    WHERE user_id = ?
    ORDER BY is_default DESC, id DESC
");

$stmt->execute([$userId]);

$addresses = $stmt->fetchAll();

?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1"
    >

    <title>My Address</title>

    <link
        href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
        rel="stylesheet"
    >

</head>

<body>

<div class="container py-5">

    <div class="row justify-content-center">

        <div class="col-lg-8">

            <div class="d-flex justify-content-between align-items-center mb-4">

                <h2 class="fw-bold">
                    Delivery Address
                </h2>

                <a
                    href="checkout.php"
                    class="btn btn-outline-secondary"
                >
                    Back to Checkout
                </a>

            </div>


            <!-- Errors -->

            <?php if (!empty($errors)): ?>

                <div class="alert alert-danger">

                    <ul class="mb-0">

                        <?php foreach ($errors as $error): ?>

                            <li>
                                <?= e($error); ?>
                            </li>

                        <?php endforeach; ?>

                    </ul>

                </div>

            <?php endif; ?>


            <!-- Add Address -->

            <div class="card border-0 shadow-sm mb-4">

                <div class="card-body p-4">

                    <h5 class="fw-bold mb-4">
                        Add New Address
                    </h5>


                    <form method="POST">

                        <input
                            type="hidden"
                            name="csrf_token"
                            value="<?= csrfToken(); ?>"
                        >


                        <div class="row g-3">

                            <!-- Name -->

                            <div class="col-md-6">

                                <label class="form-label">
                                    Full Name
                                </label>

                                <input
                                    type="text"
                                    name="name"
                                    class="form-control"
                                    value="<?= e($_POST['name'] ?? ''); ?>"
                                    required
                                >

                            </div>


                            <!-- Phone -->

                            <div class="col-md-6">

                                <label class="form-label">
                                    Phone
                                </label>

                                <input
                                    type="text"
                                    name="phone"
                                    class="form-control"
                                    value="<?= e($_POST['phone'] ?? ''); ?>"
                                    required
                                >

                            </div>


                            <!-- Address Line 1 -->

                            <div class="col-12">

                                <label class="form-label">
                                    Address Line 1
                                </label>

                                <input
                                    type="text"
                                    name="address_line1"
                                    class="form-control"
                                    value="<?= e($_POST['address_line1'] ?? ''); ?>"
                                    placeholder="House no, Street, Area"
                                    required
                                >

                            </div>


                            <!-- Address Line 2 -->

                            <div class="col-12">

                                <label class="form-label">
                                    Address Line 2
                                </label>

                                <input
                                    type="text"
                                    name="address_line2"
                                    class="form-control"
                                    value="<?= e($_POST['address_line2'] ?? ''); ?>"
                                    placeholder="Apartment, Landmark etc."
                                >

                            </div>


                            <!-- City -->

                            <div class="col-md-4">

                                <label class="form-label">
                                    City
                                </label>

                                <input
                                    type="text"
                                    name="city"
                                    class="form-control"
                                    value="<?= e($_POST['city'] ?? ''); ?>"
                                    required
                                >

                            </div>


                            <!-- State -->

                            <div class="col-md-4">

                                <label class="form-label">
                                    State
                                </label>

                                <input
                                    type="text"
                                    name="state"
                                    class="form-control"
                                    value="<?= e($_POST['state'] ?? ''); ?>"
                                    required
                                >

                            </div>


                            <!-- Postal Code -->

                            <div class="col-md-4">

                                <label class="form-label">
                                    Postal Code
                                </label>

                                <input
                                    type="text"
                                    name="postal_code"
                                    class="form-control"
                                    value="<?= e($_POST['postal_code'] ?? ''); ?>"
                                    required
                                >

                            </div>


                            <!-- Country -->

                            <div class="col-md-6">

                                <label class="form-label">
                                    Country
                                </label>

                                <input
                                    type="text"
                                    name="country"
                                    class="form-control"
                                    value="<?= e($_POST['country'] ?? 'India'); ?>"
                                >

                            </div>


                            <!-- Submit -->

                            <div class="col-12">

                                <button
                                    type="submit"
                                    class="btn btn-dark px-4"
                                >
                                    Save Address
                                </button>

                            </div>

                        </div>

                    </form>

                </div>

            </div>


            <!-- Existing Addresses -->

            <?php if (!empty($addresses)): ?>

                <h5 class="fw-bold mb-3">
                    Saved Addresses
                </h5>

                <?php foreach ($addresses as $address): ?>

                    <div class="card border-0 shadow-sm mb-3">

                        <div class="card-body">

                            <div class="d-flex justify-content-between">

                                <div>

                                    <strong>
                                        <?= e($address['name']); ?>
                                    </strong>

                                    <?php if ($address['is_default']): ?>

                                        <span class="badge bg-success ms-2">
                                            Default
                                        </span>

                                    <?php endif; ?>

                                    <p class="mb-1 mt-2">
                                        <?= e($address['phone']); ?>
                                    </p>

                                    <p class="mb-1">
                                        <?= e($address['address_line1']); ?>
                                    </p>

                                    <?php if (!empty($address['address_line2'])): ?>

                                        <p class="mb-1">
                                            <?= e($address['address_line2']); ?>
                                        </p>

                                    <?php endif; ?>

                                    <p class="mb-0">
                                        <?= e($address['city']); ?>,
                                        <?= e($address['state']); ?> -
                                        <?= e($address['postal_code']); ?>
                                    </p>

                                </div>

                            </div>

                        </div>

                    </div>

                <?php endforeach; ?>

            <?php endif; ?>

        </div>

    </div>

</div>

</body>

</html>