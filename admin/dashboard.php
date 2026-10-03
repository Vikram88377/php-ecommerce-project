<?php

require_once __DIR__ . '/../middleware/admin_auth.php';
require_once __DIR__ . '/../config/database.php';

$pageTitle = "Admin Dashboard";

$totalUsers = $pdo
    ->query("SELECT COUNT(*) FROM users WHERE role = 'user'")
    ->fetchColumn();

$totalProducts = $pdo
    ->query("SELECT COUNT(*) FROM products")
    ->fetchColumn();

$totalOrders = $pdo
    ->query("SELECT COUNT(*) FROM orders")
    ->fetchColumn();

$totalRevenue = $pdo
    ->query("
        SELECT COALESCE(SUM(total_amount), 0)
        FROM orders
        WHERE payment_status = 'paid'
    ")
    ->fetchColumn();

?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title><?= e($pageTitle) ?></title>

    <link
        href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
        rel="stylesheet"
    >

    <link
        href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css"
        rel="stylesheet"
    >

</head>

<body class="bg-light">


<!-- Navbar -->

<nav class="navbar navbar-dark bg-dark">

    <div class="container-fluid">

        <a class="navbar-brand fw-bold" href="dashboard.php">

            <i class="bi bi-speedometer2"></i>

            Enterprise Admin

        </a>


        <div class="d-flex align-items-center">

            <span class="text-white me-3">

                <i class="bi bi-person-circle"></i>

                <?= e($_SESSION['user_name']) ?>

            </span>

            <a
                href="logout.php"
                class="btn btn-outline-light btn-sm"
            >
                Logout
            </a>

        </div>

    </div>

</nav>


<div class="container-fluid">

    <div class="row">


        <!-- Sidebar -->

        <aside class="col-md-3 col-lg-2 bg-white min-vh-100 border-end p-3">

            <div class="list-group list-group-flush">

                <a
                    href="dashboard.php"
                    class="list-group-item list-group-item-action active"
                >
                    <i class="bi bi-speedometer2 me-2"></i>
                    Dashboard
                </a>

                <a
                    href="users/index.php"
                    class="list-group-item list-group-item-action"
                >
                    <i class="bi bi-people me-2"></i>
                    Users
                </a>

                <a
                    href="categories/index.php"
                    class="list-group-item list-group-item-action"
                >
                    <i class="bi bi-grid me-2"></i>
                    Categories
                </a>

                <a
                    href="products/index.php"
                    class="list-group-item list-group-item-action"
                >
                    <i class="bi bi-box-seam me-2"></i>
                    Products
                </a>

                <a
                    href="orders/index.php"
                    class="list-group-item list-group-item-action"
                >
                    <i class="bi bi-cart-check me-2"></i>
                    Orders
                </a>

                <a
                    href="#"
                    class="list-group-item list-group-item-action"
                >
                    <i class="bi bi-credit-card me-2"></i>
                    Payments
                </a>

                <a
                    href="#"
                    class="list-group-item list-group-item-action"
                >
                    <i class="bi bi-ticket-perforated me-2"></i>
                    Coupons
                </a>

                <a
                    href="#"
                    class="list-group-item list-group-item-action"
                >
                    <i class="bi bi-star me-2"></i>
                    Reviews
                </a>

                <a
                    href="#"
                    class="list-group-item list-group-item-action"
                >
                    <i class="bi bi-file-earmark-text me-2"></i>
                    Audit Logs
                </a>

            </div>

        </aside>


        <!-- Main Content -->

        <main class="col-md-9 col-lg-10 p-4">

            <div class="mb-4">

                <h2 class="fw-bold">
                    Dashboard
                </h2>

                <p class="text-muted">
                    Welcome back, <?= e($_SESSION['user_name']) ?>.
                </p>

            </div>


            <!-- Statistics -->

            <div class="row g-4">


                <div class="col-sm-6 col-xl-3">

                    <div class="card border-0 shadow-sm">

                        <div class="card-body">

                            <div class="d-flex justify-content-between">

                                <div>

                                    <p class="text-muted mb-1">
                                        Total Users
                                    </p>

                                    <h3 class="fw-bold">
                                        <?= $totalUsers ?>
                                    </h3>

                                </div>

                                <i class="bi bi-people fs-1 text-primary"></i>

                            </div>

                        </div>

                    </div>

                </div>


                <div class="col-sm-6 col-xl-3">

                    <div class="card border-0 shadow-sm">

                        <div class="card-body">

                            <div class="d-flex justify-content-between">

                                <div>

                                    <p class="text-muted mb-1">
                                        Products
                                    </p>

                                    <h3 class="fw-bold">
                                        <?= $totalProducts ?>
                                    </h3>

                                </div>

                                <i class="bi bi-box-seam fs-1 text-success"></i>

                            </div>

                        </div>

                    </div>

                </div>


                <div class="col-sm-6 col-xl-3">

                    <div class="card border-0 shadow-sm">

                        <div class="card-body">

                            <div class="d-flex justify-content-between">

                                <div>

                                    <p class="text-muted mb-1">
                                        Orders
                                    </p>

                                    <h3 class="fw-bold">
                                        <?= $totalOrders ?>
                                    </h3>

                                </div>

                                <i class="bi bi-cart-check fs-1 text-warning"></i>

                            </div>

                        </div>

                    </div>

                </div>


                <div class="col-sm-6 col-xl-3">

                    <div class="card border-0 shadow-sm">

                        <div class="card-body">

                            <div class="d-flex justify-content-between">

                                <div>

                                    <p class="text-muted mb-1">
                                        Revenue
                                    </p>

                                    <h3 class="fw-bold">
                                        ₹<?= number_format($totalRevenue, 2) ?>
                                    </h3>

                                </div>

                                <i class="bi bi-currency-rupee fs-1 text-danger"></i>

                            </div>

                        </div>

                    </div>

                </div>

            </div>


            <!-- Quick Actions -->

            <div class="card border-0 shadow-sm mt-4">

                <div class="card-body">

                    <h5 class="fw-bold mb-4">
                        Quick Actions
                    </h5>

                    <div class="d-flex flex-wrap gap-2">

                        <a
                            href="categories/create.php"
                            class="btn btn-primary"
                        >
                            <i class="bi bi-plus-circle"></i>
                            Add Category
                        </a>

                        <a
                            href="products/create.php"
                            class="btn btn-success"
                        >
                            <i class="bi bi-plus-circle"></i>
                            Add Product
                        </a>

                        <a
                            href="orders/index.php"
                            class="btn btn-warning"
                        >
                            <i class="bi bi-cart-check"></i>
                            View Orders
                        </a>

                    </div>

                </div>

            </div>

        </main>

    </div>

</div>


<script
    src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js">
</script>

</body>
</html>