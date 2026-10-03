<?php

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/security.php';
require_once __DIR__ . '/../../middleware/admin_auth.php';

require_once __DIR__ . '/../../repositories/ProductRepository.php';
require_once __DIR__ . '/../../services/ProductService.php';

$repository = new ProductRepository($pdo);
$service = new ProductService($repository);

$error = $_GET['error'] ?? '';
$message = $_GET['message'] ?? '';

/*
 * Delete Product
 */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) {

        $error = "Invalid request.";

    } else {

        try {

            $id = (int) ($_POST['id'] ?? 0);

            if ($id <= 0) {
                throw new Exception("Invalid product.");
            }

            $product = $service->getById($id);

            if (!$product) {
                throw new Exception("Product not found.");
            }


            /*
             * Get product images
             */
            $stmt = $pdo->prepare(
                "SELECT image
                 FROM product_images
                 WHERE product_id = ?"
            );

            $stmt->execute([$id]);

            $images = $stmt->fetchAll();


            /*
             * Delete image records
             */
            $stmt = $pdo->prepare(
                "DELETE FROM product_images
                 WHERE product_id = ?"
            );

            $stmt->execute([$id]);


            /*
             * Delete product
             */
            $service->delete($id);


            /*
             * Delete image files
             */
            foreach ($images as $image) {

                $imagePath =
                    __DIR__ . '/uploads/' . $image['image'];

                if (
                    !empty($image['image']) &&
                    file_exists($imagePath)
                ) {
                    unlink($imagePath);
                }
            }


            header(
                "Location: index.php?message=" .
                urlencode("Product deleted successfully")
            );

            exit;

        } catch (Exception $e) {

            $error = $e->getMessage();
        }
    }
}


$products = $service->getAll();

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

    <div class="d-flex justify-content-between
                align-items-center mb-4">

        <div>

            <h3 class="fw-bold mb-1">
                Products
            </h3>

            <p class="text-muted mb-0">
                Manage store products
            </p>

        </div>

        <a
            href="create.php"
            class="btn btn-primary">

            + Add Product

        </a>

    </div>


    <?php if ($message): ?>

        <div class="alert alert-success">
            <?= e($message) ?>
        </div>

    <?php endif; ?>


    <?php if ($error): ?>

        <div class="alert alert-danger">
            <?= e($error) ?>
        </div>

    <?php endif; ?>


    <div class="card border-0 shadow-sm">

        <div class="card-body p-0">

            <div class="table-responsive">

                <table class="table table-hover
                              align-middle mb-0">

                    <thead class="table-light">

                    <tr>

                        <th>Image</th>

                        <th>ID</th>

                        <th>Product</th>

                        <th>SKU</th>

                        <th>Category</th>

                        <th>Price</th>

                        <th>Stock</th>

                        <th>Status</th>

                        <th class="text-end">
                            Action
                        </th>

                    </tr>

                    </thead>

                    <tbody>

                    <?php if (!$products): ?>

                        <tr>

                            <td
                                colspan="9"
                                class="text-center
                                       text-muted py-5">

                                No products found.

                            </td>

                        </tr>

                    <?php endif; ?>


                    <?php foreach ($products as $product): ?>

                        <?php

                        $imageStmt = $pdo->prepare(
                            "SELECT image
                             FROM product_images
                             WHERE product_id = ?
                             AND is_primary = 1
                             LIMIT 1"
                        );

                        $imageStmt->execute([
                            $product['id']
                        ]);

                        $image = $imageStmt->fetch();

                        ?>


                        <tr>

                            <!-- Image -->

                            <td>

                                <?php if ($image): ?>

                                    <img
                                        src="uploads/<?= e($image['image']) ?>"
                                        width="60"
                                        height="60"
                                        class="rounded border"
                                        style="object-fit: cover;">

                                <?php else: ?>

                                    <div
                                        class="bg-light border
                                               rounded d-flex
                                               align-items-center
                                               justify-content-center"
                                        style="width:60px;height:60px;">

                                        <small
                                            class="text-muted">
                                            No Image
                                        </small>

                                    </div>

                                <?php endif; ?>

                            </td>


                            <!-- ID -->

                            <td>
                                <?= (int) $product['id'] ?>
                            </td>


                            <!-- Product -->

                            <td>

                                <strong>
                                    <?= e($product['name']) ?>
                                </strong>

                            </td>


                            <!-- SKU -->

                            <td>
                                <?= e($product['sku']) ?>
                            </td>


                            <!-- Category -->

                            <td>
                                <?= e(
                                    $product['category_name']
                                    ?? '-'
                                ) ?>
                            </td>


                            <!-- Price -->

                            <td>
                                ₹<?= number_format(
                                    $product['price'],
                                    2
                                ) ?>
                            </td>


                            <!-- Stock -->

                            <td>

                                <?php if ($product['stock'] > 0): ?>

                                    <span
                                        class="badge text-bg-success">

                                        <?= (int) $product['stock'] ?>

                                    </span>

                                <?php else: ?>

                                    <span
                                        class="badge text-bg-danger">

                                        Out of Stock

                                    </span>

                                <?php endif; ?>

                            </td>


                            <!-- Status -->

                            <td>

                                <?php if (
                                    $product['status'] === 'active'
                                ): ?>

                                    <span
                                        class="badge text-bg-success">

                                        Active

                                    </span>

                                <?php else: ?>

                                    <span
                                        class="badge text-bg-secondary">

                                        Inactive

                                    </span>

                                <?php endif; ?>

                            </td>


                            <!-- Actions -->

                            <td class="text-end">

                                <a
                                    href="edit.php?id=<?= $product['id'] ?>"
                                    class="btn btn-sm btn-outline-primary">

                                    Edit

                                </a>


                                <form
                                    method="POST"
                                    class="d-inline"
                                    onsubmit="return confirm(
                                        'Are you sure you want to delete this product?'
                                    );">

                                    <input
                                        type="hidden"
                                        name="csrf_token"
                                        value="<?= e(csrfToken()) ?>">

                                    <input
                                        type="hidden"
                                        name="id"
                                        value="<?= (int) $product['id'] ?>">

                                    <button
                                        type="submit"
                                        class="btn btn-sm btn-outline-danger">

                                        Delete

                                    </button>

                                </form>

                            </td>

                        </tr>

                    <?php endforeach; ?>

                    </tbody>

                </table>

            </div>

        </div>

    </div>

</div>

</body>

</html>