<?php

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/security.php';
require_once __DIR__ . '/../../middleware/admin_auth.php';

require_once __DIR__ . '/../../repositories/ProductRepository.php';
require_once __DIR__ . '/../../services/ProductService.php';

$repository = new ProductRepository($pdo);
$service = new ProductService($repository);

$categories = $service->getCategories();

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) {

        $error = "Invalid request.";

    } else {

        try {

            $categoryId = (int) ($_POST['category_id'] ?? 0);
            $name = $_POST['name'] ?? '';
            $sku = $_POST['sku'] ?? '';
            $description = $_POST['description'] ?? '';
            $price = (float) ($_POST['price'] ?? 0);
            $stock = (int) ($_POST['stock'] ?? 0);
            $status = $_POST['status'] ?? 'active';

            /*
             * Image upload
             */
            $imageName = null;

            if (
                isset($_FILES['image']) &&
                $_FILES['image']['error'] !== UPLOAD_ERR_NO_FILE
            ) {

                if ($_FILES['image']['error'] !== UPLOAD_ERR_OK) {
                    throw new Exception("Image upload failed.");
                }

                if ($_FILES['image']['size'] > 2 * 1024 * 1024) {
                    throw new Exception(
                        "Image size must be less than 2MB."
                    );
                }

                $allowedTypes = [
                    'image/jpeg',
                    'image/png',
                    'image/webp'
                ];

                $tmpFile = $_FILES['image']['tmp_name'];

                $mimeType = mime_content_type($tmpFile);

                if (!in_array($mimeType, $allowedTypes)) {
                    throw new Exception(
                        "Only JPG, PNG and WEBP images are allowed."
                    );
                }

                $extension = match ($mimeType) {
                    'image/jpeg' => 'jpg',
                    'image/png' => 'png',
                    'image/webp' => 'webp',
                    default => ''
                };

                if ($extension === '') {
                    throw new Exception("Invalid image.");
                }

                $imageName = bin2hex(random_bytes(16))
                    . '.' . $extension;

                $uploadDirectory = __DIR__ . '/uploads/';

                if (!is_dir($uploadDirectory)) {
                    mkdir(
                        $uploadDirectory,
                        0755,
                        true
                    );
                }

                $uploadPath = $uploadDirectory . $imageName;

                if (!move_uploaded_file(
                    $tmpFile,
                    $uploadPath
                )) {
                    throw new Exception(
                        "Unable to save image."
                    );
                }
            }

            $service->create(
                $categoryId,
                $name,
                $sku,
                $description,
                $price,
                $stock,
                $status,
                $imageName
            );

            header(
                "Location: index.php?message=" .
                urlencode("Product created successfully")
            );

            exit;

        } catch (Exception $e) {

            /*
             * Remove uploaded image if database insert fails.
             */
            if (
                !empty($imageName) &&
                isset($uploadPath) &&
                file_exists($uploadPath)
            ) {
                unlink($uploadPath);
            }

            $error = $e->getMessage();
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
        content="width=device-width, initial-scale=1">

    <title>Add Product</title>

    <link
        href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
        rel="stylesheet">

</head>

<body class="bg-light">

<div class="container py-5">

    <div class="card border-0 shadow-sm">

        <div class="card-body p-4 p-md-5">

            <div class="d-flex justify-content-between
                        align-items-center mb-4">

                <div>

                    <h3 class="fw-bold mb-1">
                        Add Product
                    </h3>

                    <p class="text-muted mb-0">
                        Create a new store product
                    </p>

                </div>

                <a
                    href="index.php"
                    class="btn btn-light">

                    Back

                </a>

            </div>


            <?php if ($error): ?>

                <div class="alert alert-danger">
                    <?= e($error) ?>
                </div>

            <?php endif; ?>


            <form
                method="POST"
                enctype="multipart/form-data">

                <input
                    type="hidden"
                    name="csrf_token"
                    value="<?= e(csrfToken()) ?>">


                <div class="row">

                    <!-- Category -->

                    <div class="col-md-6 mb-3">

                        <label class="form-label">
                            Category
                        </label>

                        <select
                            name="category_id"
                            class="form-select"
                            required>

                            <option value="">
                                Select Category
                            </option>

                            <?php foreach ($categories as $category): ?>

                                <option
                                    value="<?= $category['id'] ?>"
                                    <?= ($_POST['category_id'] ?? '') == $category['id']
                                        ? 'selected'
                                        : '' ?>>

                                    <?= e($category['name']) ?>

                                </option>

                            <?php endforeach; ?>

                        </select>

                    </div>


                    <!-- Status -->

                    <div class="col-md-6 mb-3">

                        <label class="form-label">
                            Status
                        </label>

                        <select
                            name="status"
                            class="form-select">

                            <option value="active">
                                Active
                            </option>

                            <option value="inactive">
                                Inactive
                            </option>

                        </select>

                    </div>


                    <!-- Name -->

                    <div class="col-md-8 mb-3">

                        <label class="form-label">
                            Product Name
                        </label>

                        <input
                            type="text"
                            name="name"
                            class="form-control"
                            value="<?= e($_POST['name'] ?? '') ?>"
                            placeholder="Enter product name"
                            required>

                    </div>


                    <!-- SKU -->

                    <div class="col-md-4 mb-3">

                        <label class="form-label">
                            SKU
                        </label>

                        <input
                            type="text"
                            name="sku"
                            class="form-control"
                            value="<?= e($_POST['sku'] ?? '') ?>"
                            placeholder="e.g. IPHONE-15"
                            required>

                    </div>


                    <!-- Price -->

                    <div class="col-md-6 mb-3">

                        <label class="form-label">
                            Price
                        </label>

                        <div class="input-group">

                            <span class="input-group-text">
                                ₹
                            </span>

                            <input
                                type="number"
                                name="price"
                                class="form-control"
                                step="0.01"
                                min="0"
                                value="<?= e($_POST['price'] ?? '') ?>"
                                required>

                        </div>

                    </div>


                    <!-- Stock -->

                    <div class="col-md-6 mb-3">

                        <label class="form-label">
                            Stock
                        </label>

                        <input
                            type="number"
                            name="stock"
                            class="form-control"
                            min="0"
                            value="<?= e($_POST['stock'] ?? '0') ?>"
                            required>

                    </div>


                    <!-- Description -->

                    <div class="col-12 mb-3">

                        <label class="form-label">
                            Description
                        </label>

                        <textarea
                            name="description"
                            class="form-control"
                            rows="5"
                            placeholder="Enter product description"><?= e($_POST['description'] ?? '') ?></textarea>

                    </div>


                    <!-- Image -->

                    <div class="col-md-6 mb-4">

                        <label class="form-label">
                            Product Image
                        </label>

                        <input
                            type="file"
                            name="image"
                            class="form-control"
                            accept=".jpg,.jpeg,.png,.webp">

                        <small class="text-muted">
                            JPG, PNG or WEBP. Maximum 2MB.
                        </small>

                    </div>

                </div>


                <div class="border-top pt-4">

                    <button
                        type="submit"
                        class="btn btn-primary">

                        Create Product

                    </button>

                    <a
                        href="index.php"
                        class="btn btn-light ms-2">

                        Cancel

                    </a>

                </div>

            </form>

        </div>

    </div>

</div>

</body>

</html>