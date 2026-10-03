<?php

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/security.php';
require_once __DIR__ . '/../../middleware/admin_auth.php';

require_once __DIR__ . '/../../repositories/CategoryRepository.php';
require_once __DIR__ . '/../../services/CategoryService.php';

$repository = new CategoryRepository($pdo);
$service = new CategoryService($repository);

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) {
        $error = "Invalid request.";
    } else {

        try {

            $service->create(
                $_POST['name'] ?? '',
                $_POST['status'] ?? 'active'
            );

            header("Location: index.php?message=Category created successfully");
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

    <title>Add Category</title>

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
                Add Category
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
                        value="<?= e($_POST['name'] ?? '') ?>"
                        required>

                </div>

                <div class="mb-4">

                    <label class="form-label">
                        Status
                    </label>

                    <select name="status"
                            class="form-select">

                        <option value="active">
                            Active
                        </option>

                        <option value="inactive">
                            Inactive
                        </option>

                    </select>

                </div>

                <div class="d-flex gap-2">

                    <button class="btn btn-primary">
                        Create Category
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