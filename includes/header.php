<?php
if (!isset($pageTitle)) {
    $pageTitle = "Enterprise Store";
}
?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta name="viewport"
          content="width=device-width, initial-scale=1.0">

    <meta name="description"
          content="Enterprise E-Commerce Store">

    <title><?= htmlspecialchars($pageTitle) ?> - Enterprise Store</title>


    <!-- Bootstrap 5 -->
    <link
        href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
        rel="stylesheet"
    >


    <!-- Bootstrap Icons -->
    <link
        href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css"
        rel="stylesheet"
    >


    <!-- Custom CSS -->
    <link
        rel="stylesheet"
        href="/enterprise_ecommerce/public/assets/css/style.css"
    >

</head>

<body>


<!-- Navbar -->

<nav class="navbar navbar-expand-lg bg-white shadow-sm sticky-top">

    <div class="container">

        <a class="navbar-brand fw-bold fs-4" href="/enterprise_ecommerce/public/index.php">

            <i class="bi bi-bag-heart-fill text-primary"></i>

            Enterprise<span class="text-primary">Store</span>

        </a>


        <button
            class="navbar-toggler"
            type="button"
            data-bs-toggle="collapse"
            data-bs-target="#mainNavbar"
        >

            <span class="navbar-toggler-icon"></span>

        </button>


        <div class="collapse navbar-collapse" id="mainNavbar">

            <ul class="navbar-nav mx-auto">

                <li class="nav-item">
                    <a class="nav-link active" href="#">
                        Home
                    </a>
                </li>

                <li class="nav-item">
                    <a class="nav-link" href="#">
                        Products
                    </a>
                </li>

                <li class="nav-item">
                    <a class="nav-link" href="#">
                        Categories
                    </a>
                </li>

                <li class="nav-item">
                    <a class="nav-link" href="#">
                        Deals
                    </a>
                </li>

            </ul>


            <div class="d-flex align-items-center gap-3">

                <a href="#" class="text-dark fs-5">
                    <i class="bi bi-search"></i>
                </a>

                <a href="#" class="text-dark fs-5 position-relative">

                    <i class="bi bi-heart"></i>

                </a>

                <a href="#" class="text-dark fs-5 position-relative">

                    <i class="bi bi-cart3"></i>

                    <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger">
                        0
                    </span>

                </a>

                <a href="#" class="btn btn-primary btn-sm">
                    Login
                </a>

            </div>

        </div>

    </div>

</nav>