<?php

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/security.php';

$pageTitle = "Admin Login";

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) {
        $errors[] = "Invalid request.";
    }

    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Enter a valid email.";
    }

    if ($password === '') {
        $errors[] = "Password is required.";
    }

    if (empty($errors)) {

        $stmt = $pdo->prepare(
            "SELECT id, name, email, password, role, status
             FROM users
             WHERE email = ?
             LIMIT 1"
        );

        $stmt->execute([$email]);

        $user = $stmt->fetch();

        if (
            $user &&
            $user['role'] === 'admin' &&
            $user['status'] === 'active' &&
            password_verify($password, $user['password'])
        ) {

            session_regenerate_id(true);

            $_SESSION['user_id'] = $user['id'];
            $_SESSION['user_name'] = $user['name'];
            $_SESSION['user_email'] = $user['email'];
            $_SESSION['user_role'] = $user['role'];

            header("Location: dashboard.php");
            exit;

        } else {

            $errors[] = "Invalid admin credentials.";
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
        content="width=device-width, initial-scale=1.0"
    >

    <title>Admin Login</title>

    <link
        href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
        rel="stylesheet"
    >

    <link
        href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css"
        rel="stylesheet"
    >

</head>

<body class="bg-light">

<div class="container">

    <div class="row justify-content-center align-items-center min-vh-100">

        <div class="col-md-5 col-lg-4">

            <div class="card border-0 shadow">

                <div class="card-body p-4 p-md-5">

                    <div class="text-center mb-4">

                        <i class="bi bi-shield-lock-fill fs-1 text-primary"></i>

                        <h3 class="fw-bold mt-2">
                            Admin Login
                        </h3>

                        <p class="text-muted">
                            Enterprise Store Administration
                        </p>

                    </div>


                    <?php if (!empty($errors)): ?>

                        <div class="alert alert-danger">

                            <ul class="mb-0">

                                <?php foreach ($errors as $error): ?>

                                    <li>
                                        <?= e($error) ?>
                                    </li>

                                <?php endforeach; ?>

                            </ul>

                        </div>

                    <?php endif; ?>


                    <form method="POST">

                        <input
                            type="hidden"
                            name="csrf_token"
                            value="<?= e(csrfToken()) ?>"
                        >


                        <div class="mb-3">

                            <label class="form-label">
                                Email
                            </label>

                            <input
                                type="email"
                                name="email"
                                class="form-control"
                                required
                            >

                        </div>


                        <div class="mb-4">

                            <label class="form-label">
                                Password
                            </label>

                            <input
                                type="password"
                                name="password"
                                class="form-control"
                                required
                            >

                        </div>


                        <button
                            type="submit"
                            class="btn btn-primary w-100"
                        >
                            <i class="bi bi-box-arrow-in-right"></i>
                            Login
                        </button>

                    </form>

                </div>

            </div>

        </div>

    </div>

</div>

</body>
</html>