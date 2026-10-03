<?php

require_once "../config/database.php";
require_once "../includes/security.php";

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}


/*
|--------------------------------------------------------------------------
| Product ID
|--------------------------------------------------------------------------
*/

$id = (int) ($_GET['id'] ?? 0);

if ($id <= 0) {
    header("Location: products.php");
    exit;
}


/*
|--------------------------------------------------------------------------
| Get Product
|--------------------------------------------------------------------------
*/

$stmt = $pdo->prepare("
    SELECT
        p.id,
        p.name,
        p.slug,
        p.sku,
        p.description,
        p.price,
        p.sale_price,
        p.stock,
        c.name AS category_name
    FROM products p
    LEFT JOIN categories c
        ON c.id = p.category_id
    WHERE p.id = ?
    AND p.status = 'active'
    LIMIT 1
");

$stmt->execute([$id]);

$product = $stmt->fetch();

if (!$product) {
    header("Location: products.php");
    exit;
}


/*
|--------------------------------------------------------------------------
| Product Image
|--------------------------------------------------------------------------
*/

$imageStmt = $pdo->prepare("
    SELECT image
    FROM product_images
    WHERE product_id = ?
    AND is_primary = 1
    LIMIT 1
");

$imageStmt->execute([$id]);

$image = $imageStmt->fetchColumn();

$imagePath = $image
    ? "../admin/products/uploads/" . $image
    : null;


/*
|--------------------------------------------------------------------------
| Average Rating
|--------------------------------------------------------------------------
*/

$ratingStmt = $pdo->prepare("
    SELECT
        AVG(rating) AS average_rating,
        COUNT(*) AS review_count
    FROM reviews
    WHERE product_id = ?
    AND status = 'approved'
");

$ratingStmt->execute([$id]);

$ratingData = $ratingStmt->fetch();

$averageRating = round(
    (float) ($ratingData['average_rating'] ?? 0),
    1
);

$reviewCount = (int) ($ratingData['review_count'] ?? 0);


/*
|--------------------------------------------------------------------------
| Approved Reviews
|--------------------------------------------------------------------------
*/

$reviewsStmt = $pdo->prepare("
    SELECT
        r.rating,
        r.comment,
        r.created_at,
        u.name
    FROM reviews r
    INNER JOIN users u
        ON u.id = r.user_id
    WHERE r.product_id = ?
    AND r.status = 'approved'
    ORDER BY r.created_at DESC
");

$reviewsStmt->execute([$id]);

$reviews = $reviewsStmt->fetchAll();


/*
|--------------------------------------------------------------------------
| Logged-in User Review Eligibility
|--------------------------------------------------------------------------
*/

$userId = $_SESSION['user_id'] ?? 0;

$canReview = false;

$alreadyReviewed = false;

$reviewOrderId = 0;


if ($userId > 0) {

    /*
    | Already Reviewed?
    */

    $existingReviewStmt = $pdo->prepare("
        SELECT id
        FROM reviews
        WHERE user_id = ?
        AND product_id = ?
        LIMIT 1
    ");

    $existingReviewStmt->execute([
        $userId,
        $id
    ]);

    $alreadyReviewed = (bool) $existingReviewStmt->fetch();


    /*
    | Find Delivered Order
    */

    if (!$alreadyReviewed) {

        $purchaseStmt = $pdo->prepare("
            SELECT o.id
            FROM orders o
            INNER JOIN order_items oi
                ON oi.order_id = o.id
            WHERE o.user_id = ?
            AND oi.product_id = ?
            AND o.status = 'delivered'
            ORDER BY o.id DESC
            LIMIT 1
        ");

        $purchaseStmt->execute([
            $userId,
            $id
        ]);

        $purchase = $purchaseStmt->fetch();

        if ($purchase) {

            $canReview = true;

            $reviewOrderId = (int) $purchase['id'];
        }
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

<title><?= e($product['name']); ?></title>

<link
    href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
    rel="stylesheet"
>

<style>

.rating-stars {
    letter-spacing: 2px;
}

.review-card {
    border: 0;
    box-shadow: 0 2px 10px rgba(0,0,0,0.06);
}

</style>

</head>

<body>

<div class="container py-5">


    <!-- Product -->

    <div class="row mb-5">


        <!-- Image -->

        <div class="col-md-6 mb-4">

            <?php if ($imagePath): ?>

                <img
                    src="<?= e($imagePath); ?>"
                    alt="<?= e($product['name']); ?>"
                    class="img-fluid rounded shadow-sm"
                    style="
                        width: 100%;
                        max-height: 500px;
                        object-fit: contain;
                    "
                >

            <?php else: ?>

                <div
                    class="bg-light rounded d-flex align-items-center justify-content-center"
                    style="height: 400px;"
                >
                    <span class="text-muted">
                        No Image Available
                    </span>
                </div>

            <?php endif; ?>

        </div>


        <!-- Details -->

        <div class="col-md-6">

            <p class="text-muted mb-2">
                <?= e($product['category_name'] ?? 'Uncategorized'); ?>
            </p>


            <h1 class="fw-bold mb-3">
                <?= e($product['name']); ?>
            </h1>


            <p class="text-muted">
                SKU: <?= e($product['sku']); ?>
            </p>


            <!-- Rating -->

            <div class="mb-3">

                <?php if ($reviewCount > 0): ?>

                    <span class="text-warning rating-stars">
                        <?= str_repeat('★', (int) round($averageRating)); ?>
                    </span>

                    <strong>
                        <?= number_format($averageRating, 1); ?>
                    </strong>

                    <span class="text-muted">
                        (<?= $reviewCount; ?> reviews)
                    </span>

                <?php else: ?>

                    <span class="text-muted">
                        No reviews yet
                    </span>

                <?php endif; ?>

            </div>


            <!-- Price -->

            <div class="mb-3">

                <?php if (!empty($product['sale_price'])): ?>

                    <span class="fs-3 fw-bold text-danger">
                        ₹<?= number_format($product['sale_price'], 2); ?>
                    </span>

                    <span class="text-muted text-decoration-line-through ms-2">
                        ₹<?= number_format($product['price'], 2); ?>
                    </span>

                <?php else: ?>

                    <span class="fs-3 fw-bold">
                        ₹<?= number_format($product['price'], 2); ?>
                    </span>

                <?php endif; ?>

            </div>


            <!-- Stock -->

            <?php if ($product['stock'] > 0): ?>

                <span class="badge bg-success mb-3">
                    In Stock (<?= $product['stock']; ?> available)
                </span>

            <?php else: ?>

                <span class="badge bg-danger mb-3">
                    Out of Stock
                </span>

            <?php endif; ?>


            <!-- Description -->

            <div class="mt-3 mb-4">

                <h5 class="fw-bold">
                    Description
                </h5>

                <p class="text-muted">
                    <?= nl2br(e(
                        $product['description']
                        ?? 'No description available.'
                    )); ?>
                </p>

            </div>


            <!-- Add To Cart -->

            <?php if ($product['stock'] > 0): ?>

                <form
                    action="add-to-cart.php"
                    method="POST"
                    class="mt-3"
                >

                    <input
                        type="hidden"
                        name="csrf_token"
                        value="<?= csrfToken(); ?>"
                    >

                    <input
                        type="hidden"
                        name="product_id"
                        value="<?= $product['id']; ?>"
                    >


                    <div
                        class="mb-3"
                        style="max-width: 120px;"
                    >

                        <label class="form-label">
                            Quantity
                        </label>

                        <input
                            type="number"
                            name="quantity"
                            class="form-control"
                            value="1"
                            min="1"
                            max="<?= $product['stock']; ?>"
                        >

                    </div>


                    <button
                        type="submit"
                        class="btn btn-dark px-4"
                    >
                        Add to Cart
                    </button>

                </form>

            <?php else: ?>

                <button
                    class="btn btn-secondary"
                    disabled
                >
                    Out of Stock
                </button>

            <?php endif; ?>


        </div>

    </div>


    <!-- Reviews Section -->

    <div class="border-top pt-5">

        <div class="row">


            <!-- Reviews List -->

            <div class="col-lg-8">

                <h3 class="fw-bold mb-4">
                    Customer Reviews
                </h3>


                <?php if (empty($reviews)): ?>

                    <div class="alert alert-light">
                        No approved reviews yet.
                    </div>

                <?php else: ?>

                    <?php foreach ($reviews as $review): ?>

                        <div class="card review-card mb-3">

                            <div class="card-body">

                                <div class="d-flex justify-content-between">

                                    <div>

                                        <strong>
                                            <?= e($review['name']); ?>
                                        </strong>

                                        <div class="text-warning rating-stars">

                                            <?= str_repeat(
                                                '★',
                                                (int) $review['rating']
                                            ); ?>

                                        </div>

                                    </div>

                                    <small class="text-muted">

                                        <?= date(
                                            'd M Y',
                                            strtotime($review['created_at'])
                                        ); ?>

                                    </small>

                                </div>


                                <p class="mt-3 mb-0">

                                    <?= nl2br(
                                        e($review['comment'])
                                    ); ?>

                                </p>

                            </div>

                        </div>

                    <?php endforeach; ?>

                <?php endif; ?>

            </div>


            <!-- Review Form -->

            <div class="col-lg-4">

                <?php if ($canReview): ?>

                    <div class="card border-0 shadow-sm">

                        <div class="card-body">

                            <h5 class="fw-bold mb-3">
                                Write a Review
                            </h5>


                            <?php if (
                                isset($_GET['review'])
                                && $_GET['review'] === 'success'
                            ): ?>

                                <div class="alert alert-success">

                                    Review submitted successfully.
                                    It will appear after admin approval.

                                </div>

                            <?php endif; ?>


                            <form
                                action="review-submit.php"
                                method="POST"
                            >

                                <input
                                    type="hidden"
                                    name="csrf_token"
                                    value="<?= csrfToken(); ?>"
                                >

                                <input
                                    type="hidden"
                                    name="product_id"
                                    value="<?= $product['id']; ?>"
                                >

                                <input
                                    type="hidden"
                                    name="order_id"
                                    value="<?= $reviewOrderId; ?>"
                                >


                                <div class="mb-3">

                                    <label class="form-label">
                                        Rating
                                    </label>

                                    <select
                                        name="rating"
                                        class="form-select"
                                        required
                                    >

                                        <option value="">
                                            Select Rating
                                        </option>

                                        <option value="5">
                                            ★★★★★ - Excellent
                                        </option>

                                        <option value="4">
                                            ★★★★☆ - Good
                                        </option>

                                        <option value="3">
                                            ★★★☆☆ - Average
                                        </option>

                                        <option value="2">
                                            ★★☆☆☆ - Poor
                                        </option>

                                        <option value="1">
                                            ★☆☆☆☆ - Very Poor
                                        </option>

                                    </select>

                                </div>


                                <div class="mb-3">

                                    <label class="form-label">
                                        Your Review
                                    </label>

                                    <textarea
                                        name="comment"
                                        class="form-control"
                                        rows="5"
                                        maxlength="1000"
                                        required
                                    ></textarea>

                                </div>


                                <button
                                    type="submit"
                                    class="btn btn-dark w-100"
                                >
                                    Submit Review
                                </button>

                            </form>

                        </div>

                    </div>


                <?php elseif ($alreadyReviewed): ?>

                    <div class="alert alert-success">

                        You have already reviewed this product.

                    </div>


                <?php elseif ($userId <= 0): ?>

                    <div class="alert alert-light">

                        Please login to review this product.

                    </div>


                <?php else: ?>

                    <div class="alert alert-light">

                        You can review this product after
                        your order has been delivered.

                    </div>

                <?php endif; ?>

            </div>

        </div>

    </div>


    <!-- Back -->

    <div class="mt-5">

        <a
            href="products.php"
            class="btn btn-outline-secondary"
        >
            ← Back to Products
        </a>

    </div>

</div>

</body>
</html>