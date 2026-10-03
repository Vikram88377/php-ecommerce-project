<?php

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/security.php';

$pageTitle = "Login";

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
            password_verify($password, $user['password'])
        ) {

            if ($user['status'] !== 'active') {
                $errors[] = "Your account is not active.";
            } else {

                session_regenerate_id(true);

                $_SESSION['user_id'] = $user['id'];
                $_SESSION['user_name'] = $user['name'];
                $_SESSION['user_email'] = $user['email'];
                $_SESSION['user_role'] = $user['role'];

                header("Location: ../user/dashboard.php");
                exit;
            }

        } else {

            $errors[] = "Invalid email or password.";
        }
    }
}

require_once __DIR__ . '/../includes/header.php';
?>

<div class="container py-5">

    <div class="row justify-content-center">

        <div class="col-md-6 col-lg-5">

            <div class="card border-0 shadow-sm">

                <div class="card-body p-4 p-md-5">

                    <div class="text-center mb-4">

                        <i class="bi bi-person-circle fs-1 text-primary"></i>

                        <h2 class="fw-bold mt-2">
                            Welcome Back
                        </h2>

                        <p class="text-muted">
                            Login to your account
                        </p>

                    </div>


                    <?php if (isset($_GET['registered'])): ?>

                        <div class="alert alert-success">
                            Account created successfully.
                            Please login.
                        </div>

                    <?php endif; ?>


                    <?php if (!empty($errors)): ?>

                        <div class="alert alert-danger">

                            <ul class="mb-0">

                                <?php foreach ($errors as $error): ?>

                                    <li><?= e($error) ?></li>

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
                                value="<?= e($_POST['email'] ?? '') ?>"
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
                            Login
                        </button>

                    </form>


                    <p class="text-center mt-4 mb-0">

                        Don't have an account?

                        <a href="register.php">
                            Create Account
                        </a>

                    </p>

                </div>

            </div>

        </div>

    </div>

</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>