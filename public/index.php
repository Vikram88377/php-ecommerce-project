<?php

require_once __DIR__ . '/../config/database.php';

$pageTitle = "Home";

require_once __DIR__ . '/../includes/header.php';

?>

<!-- Hero Section -->
<section class="hero-section py-5">
    <div class="container">
        <div class="row align-items-center">

            <div class="col-lg-6">
                <span class="badge bg-primary mb-3">
                    Latest Collection
                </span>

                <h1 class="display-4 fw-bold">
                    Shop Smart.<br>
                    Live Better.
                </h1>

                <p class="lead text-muted">
                    Discover quality products at the best prices.
                    Fast delivery and secure payment.
                </p>

                <a href="#" class="btn btn-primary btn-lg px-4">
                    Shop Now
                </a>

                <a href="#" class="btn btn-outline-dark btn-lg px-4 ms-2">
                    Explore Products
                </a>
            </div>

            <div class="col-lg-6 text-center mt-4 mt-lg-0">
                <div class="hero-image">
                    <i class="bi bi-bag-heart display-1 text-primary"></i>
                </div>
            </div>

        </div>
    </div>
</section>


<!-- Categories -->
<section class="py-5 bg-light">

    <div class="container">

        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2 class="fw-bold mb-0">Shop by Category</h2>

            <a href="#" class="text-decoration-none">
                View All
            </a>
        </div>

        <div class="row g-4">

            <?php
            $categories = [
                ['name' => 'Mobiles', 'icon' => 'bi-phone'],
                ['name' => 'Laptops', 'icon' => 'bi-laptop'],
                ['name' => 'Fashion', 'icon' => 'bi-bag'],
                ['name' => 'Electronics', 'icon' => 'bi-headphones']
            ];
            ?>

            <?php foreach ($categories as $category): ?>

                <div class="col-6 col-md-3">

                    <div class="card category-card border-0 shadow-sm text-center p-4">

                        <i class="bi <?= $category['icon'] ?> fs-1 text-primary"></i>

                        <h5 class="mt-3 mb-0">
                            <?= htmlspecialchars($category['name']) ?>
                        </h5>

                    </div>

                </div>

            <?php endforeach; ?>

        </div>

    </div>

</section>


<!-- Featured Products -->
<section class="py-5">

    <div class="container">

        <div class="d-flex justify-content-between align-items-center mb-4">

            <div>
                <h2 class="fw-bold mb-1">
                    Featured Products
                </h2>

                <p class="text-muted mb-0">
                    Explore our popular products
                </p>
            </div>

            <a href="#" class="btn btn-outline-primary">
                View All
            </a>

        </div>


        <div class="row g-4">

            <?php for ($i = 1; $i <= 4; $i++): ?>

                <div class="col-sm-6 col-lg-3">

                    <div class="card product-card h-100 border-0 shadow-sm">

                        <div class="product-image bg-light">
                            <i class="bi bi-image fs-1 text-secondary"></i>
                        </div>

                        <div class="card-body">

                            <small class="text-muted">
                                Electronics
                            </small>

                            <h5 class="card-title mt-1">
                                Premium Product <?= $i ?>
                            </h5>

                            <div class="mb-2">
                                <span class="text-warning">
                                    ★★★★★
                                </span>

                                <small class="text-muted">
                                    (<?= $i + 10 ?>)
                                </small>
                            </div>

                            <h5 class="fw-bold">
                                ₹<?= number_format(999 * $i) ?>
                            </h5>

                        </div>

                        <div class="card-footer bg-white border-0 pb-3">

                            <button class="btn btn-primary w-100">
                                <i class="bi bi-cart-plus"></i>
                                Add to Cart
                            </button>

                        </div>

                    </div>

                </div>

            <?php endfor; ?>

        </div>

    </div>

</section>


<!-- Features -->
<section class="py-5 bg-light">

    <div class="container">

        <div class="row g-4 text-center">

            <div class="col-md-4">

                <i class="bi bi-truck fs-1 text-primary"></i>

                <h5 class="mt-3">
                    Fast Delivery
                </h5>

                <p class="text-muted">
                    Fast and reliable delivery at your doorstep.
                </p>

            </div>


            <div class="col-md-4">

                <i class="bi bi-shield-check fs-1 text-primary"></i>

                <h5 class="mt-3">
                    Secure Payment
                </h5>

                <p class="text-muted">
                    Your payments are protected with secure gateways.
                </p>

            </div>


            <div class="col-md-4">

                <i class="bi bi-headset fs-1 text-primary"></i>

                <h5 class="mt-3">
                    Customer Support
                </h5>

                <p class="text-muted">
                    Our support team is always ready to help.
                </p>

            </div>

        </div>

    </div>

</section>


<?php require_once __DIR__ . '/../includes/footer.php'; ?>