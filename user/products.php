<?php

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/security.php';

$stmt = $pdo->query(
    "SELECT
        p.id,
        p.name,
        p.slug,
        p.price,
        p.sale_price,
        p.stock,
        c.name AS category_name,
        pi.image
     FROM products p

     LEFT JOIN categories c
        ON c.id = p.category_id

     LEFT JOIN product_images pi
        ON pi.product_id = p.id
        AND pi.is_primary = 1

     WHERE p.status = 'active'

     ORDER BY p.id DESC"
);

$products = $stmt->fetchAll();

?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1">

    <title>Products</title>

    <link
        href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
        rel="stylesheet">

</head>

<body class="bg-light">

<div class="container py-5">

    <!-- Header -->

    <div class="d-flex
                justify-content-between
                align-items-center
                mb-4">

        <div>

            <h2 class="fw-bold mb-1">
                Products
            </h2>

            <p class="text-muted mb-0">
                Browse our latest products
            </p>

        </div>

    </div>


    <!-- Products -->

    <div class="row g-4">

        <?php if (!$products): ?>

            <div class="col-12">

                <div class="alert alert-info">

                    No products available.

                </div>

            </div>

        <?php endif; ?>


        <?php foreach ($products as $product): ?>

            <div class="col-md-6 col-lg-4 col-xl-3">

                <div class="card
                            border-0
                            shadow-sm
                            h-100">


                    <!-- Image -->

                    <?php if ($product['image']): ?>

                        <img
                            src="../admin/products/uploads/<?= e($product['image']) ?>"
                            class="card-img-top"
                            style="height:220px;
                                   object-fit:cover;"
                            alt="<?= e($product['name']) ?>">

                    <?php else: ?>

                        <div
                            class="bg-light
                                   d-flex
                                   align-items-center
                                   justify-content-center"
                            style="height:220px;">

                            <span class="text-muted">
                                No Image
                            </span>

                        </div>

                    <?php endif; ?>


                    <div class="card-body">


                        <!-- Category -->

                        <small class="text-muted">

                            <?= e(
                                $product['category_name']
                                ?? 'Uncategorized'
                            ) ?>

                        </small>


                        <!-- Product Name -->

                        <h5 class="card-title mt-2">

                            <?= e($product['name']) ?>

                        </h5>


                        <!-- Price -->

                        <div class="mb-3">

                            <?php if (
                                $product['sale_price'] !== null &&
                                $product['sale_price']
                                < $product['price']
                            ): ?>

                                <span class="fw-bold text-danger">

                                    ₹<?= number_format(
                                        $product['sale_price'],
                                        2
                                    ) ?>

                                </span>

                                <del
                                    class="text-muted ms-2">

                                    ₹<?= number_format(
                                        $product['price'],
                                        2
                                    ) ?>

                                </del>

                            <?php else: ?>

                                <span class="fw-bold">

                                    ₹<?= number_format(
                                        $product['price'],
                                        2
                                    ) ?>

                                </span>

                            <?php endif; ?>

                        </div>


                        <!-- Stock -->

                        <?php if ($product['stock'] > 0): ?>

                            <span
                                class="badge text-bg-success">

                                In Stock

                            </span>

                        <?php else: ?>

                            <span
                                class="badge text-bg-danger">

                                Out of Stock

                            </span>

                        <?php endif; ?>


                    </div>


                    <div class="card-footer
                                bg-white
                                border-0
                                pb-3">


                        <a
                            href="product.php?id=<?= $product['id'] ?>"
                            class="btn btn-primary w-100">

                            View Product

                        </a>


                    </div>

                </div>

            </div>

        <?php endforeach; ?>

    </div>

</div>

</body>

</html>