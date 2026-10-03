<?php

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/security.php';
require_once __DIR__ . '/../../middleware/admin_auth.php';

require_once __DIR__ . '/../../repositories/CategoryRepository.php';
require_once __DIR__ . '/../../services/CategoryService.php';

$repository = new CategoryRepository($pdo);
$service = new CategoryService($repository);

$id = (int) ($_GET['id'] ?? 0);

$category = $service->getById($id);

if (!$category) {
    exit("Category not found.");
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) {

        $error = "Invalid request.";

    } else {

        try {

            $service->update(
                $id,
                $_POST['name'] ?? '',
                $_POST['status'] ?? 'active'
            );

            header("Location: index.php?message=Category updated successfully");
            exit;

        } catch (Exception $e) {

            $error = $e->getMessage();
        }
    }
}

?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta name="viewport"
          content="width=device-width, initial-scale=1">

    <title>Edit Category</title>

    <link
        href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
        rel="stylesheet">

</head>

<body class="bg-light">

<div class="container py-5">

    <div class="card border-0 shadow-sm mx-auto"
         style="max-width: 600px;">

        <div class="card-body p-4">

            <h3 class="fw-bold mb-4">
                Edit Category
            </h3>

            <?php if ($error): ?>

                <div class="alert alert-danger">
                    <?= e($error) ?>
                </div>

            <?php endif; ?>

            <form method="POST">

                <input
                    type="hidden"
                    name="csrf_token"
                    value="<?= e(csrfToken()) ?>">

                <div class="mb-3">

                    <label class="form-label">
                        Category Name
                    </label>

                    <input
                        type="text"
                        name="name"
                        class="form-control"
                        value="<?= e($_POST['name'] ?? $category['name']) ?>"
                        required>

                </div>

                <div class="mb-4">

                    <label class="form-label">
                        Status
                    </label>

                    <select name="status"
                            class="form-select">

                        <option
                            value="active"
                            <?= ($_POST['status'] ?? $category['status']) === 'active'
                                ? 'selected'
                                : '' ?>>
                            Active
                        </option>

                        <option
                            value="inactive"
                            <?= ($_POST['status'] ?? $category['status']) === 'inactive'
                                ? 'selected'
                                : '' ?>>
                            Inactive
                        </option>

                    </select>

                </div>

                <div class="d-flex gap-2">

                    <button class="btn btn-primary">
                        Update Category
                    </button>

                    <a href="index.php"
                       class="btn btn-light">
                        Cancel
                    </a>

                </div>

            </form>

        </div>

    </div>

</div>

</body>
</html>