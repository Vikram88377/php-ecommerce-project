<?php

require_once __DIR__ . '/../middleware/auth.php';

$pageTitle = "My Dashboard";

require_once __DIR__ . '/../includes/header.php';
?>

<div class="container py-5">

    <div class="row">

        <div class="col-lg-3 mb-4">

            <div class="list-group shadow-sm">

                <a
                    href="dashboard.php"
                    class="list-group-item list-group-item-action active"
                >
                    <i class="bi bi-speedometer2"></i>
                    Dashboard
                </a>

                <a
                    href="#"
                    class="list-group-item list-group-item-action"
                >
                    <i class="bi bi-box"></i>
                    My Orders
                </a>

                <a
                    href="#"
                    class="list-group-item list-group-item-action"
                >
                    <i class="bi bi-heart"></i>
                    Wishlist
                </a>

                <a
                    href="#"
                    class="list-group-item list-group-item-action"
                >
                    <i class="bi bi-geo-alt"></i>
                    Addresses
                </a>

                <a
                    href="#"
                    class="list-group-item list-group-item-action"
                >
                    <i class="bi bi-person"></i>
                    Profile
                </a>

                <a
                    href="../public/logout.php"
                    class="list-group-item list-group-item-action text-danger"
                >
                    <i class="bi bi-box-arrow-right"></i>
                    Logout
                </a>

            </div>

        </div>


        <div class="col-lg-9">

            <div class="mb-4">

                <h2 class="fw-bold">
                    Welcome,
                    <?= htmlspecialchars($_SESSION['user_name']) ?> 👋
                </h2>

                <p class="text-muted">
                    Manage your account and orders.
                </p>

            </div>


            <div class="row g-4">

                <div class="col-md-4">

                    <div class="card border-0 shadow-sm">

                        <div class="card-body">

                            <i class="bi bi-box-seam fs-2 text-primary"></i>

                            <h6 class="mt-3">
                                Total Orders
                            </h6>

                            <h3 class="fw-bold">
                                0
                            </h3>

                        </div>

                    </div>

                </div>


                <div class="col-md-4">

                    <div class="card border-0 shadow-sm">

                        <div class="card-body">

                            <i class="bi bi-heart fs-2 text-danger"></i>

                            <h6 class="mt-3">
                                Wishlist
                            </h6>

                            <h3 class="fw-bold">
                                0
                            </h3>

                        </div>

                    </div>

                </div>


                <div class="col-md-4">

                    <div class="card border-0 shadow-sm">

                        <div class="card-body">

                            <i class="bi bi-wallet2 fs-2 text-success"></i>

                            <h6 class="mt-3">
                                Total Spent
                            </h6>

                            <h3 class="fw-bold">
                                ₹0
                            </h3>

                        </div>

                    </div>

                </div>

            </div>

        </div>

    </div>

</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>