<?php

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/security.php';
require_once __DIR__ . '/../../middleware/admin_auth.php';

require_once __DIR__ . '/../../repositories/CategoryRepository.php';
require_once __DIR__ . '/../../services/CategoryService.php';

$repository = new CategoryRepository($pdo);
$service = new CategoryService($repository);

$categories = $service->getAll();

$message = $_GET['message'] ?? '';
$error = $_GET['error'] ?? '';

?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta name="viewport"
          content="width=device-width, initial-scale=1">

    <title>Categories</title>

    <link
        href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
        rel="stylesheet">

</head>

<body class="bg-light">

<div class="container py-5">

    <div class="d-flex justify-content-between align-items-center mb-4">

        <div>
            <h2 class="fw-bold mb-1">Categories</h2>
            <p class="text-muted mb-0">
                Manage product categories
            </p>
        </div>

        <a href="create.php"
           class="btn btn-primary">
            + Add Category
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

        <div class="card-body">

            <div class="table-responsive">

                <table class="table align-middle">

                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Name</th>
                            <th>Slug</th>
                            <th>Status</th>
                            <th>Created</th>
                            <th class="text-end">Actions</th>
                        </tr>
                    </thead>

                    <tbody>

                    <?php if (!$categories): ?>

                        <tr>
                            <td colspan="6"
                                class="text-center text-muted py-4">
                                No categories found.
                            </td>
                        </tr>

                    <?php endif; ?>

                    <?php foreach ($categories as $category): ?>

                        <tr>

                            <td>
                                <?= e($category['id']) ?>
                            </td>

                            <td class="fw-semibold">
                                <?= e($category['name']) ?>
                            </td>

                            <td>
                                <?= e($category['slug']) ?>
                            </td>

                            <td>

                                <?php if ($category['status'] === 'active'): ?>

                                    <span class="badge bg-success">
                                        Active
                                    </span>

                                <?php else: ?>

                                    <span class="badge bg-secondary">
                                        Inactive
                                    </span>

                                <?php endif; ?>

                            </td>

                            <td>
                                <?= e($category['created_at']) ?>
                            </td>

                            <td class="text-end">

                                <a href="edit.php?id=<?= $category['id'] ?>"
                                   class="btn btn-sm btn-outline-primary">
                                    Edit
                                </a>

                                <form
                                    action="delete.php"
                                    method="POST"
                                    class="d-inline"
                                    onsubmit="return confirm('Delete this category?');">

                                    <input
                                        type="hidden"
                                        name="csrf_token"
                                        value="<?= e(csrfToken()) ?>">

                                    <input
                                        type="hidden"
                                        name="id"
                                        value="<?= $category['id'] ?>">

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