<?php

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/security.php';

$pageTitle = "Register";

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) {
        $errors[] = "Invalid request.";
    }

    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirmPassword = $_POST['confirm_password'] ?? '';

    if ($name === '') {
        $errors[] = "Name is required.";
    }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Enter a valid email.";
    }

    if (strlen($password) < 8) {
        $errors[] = "Password must be at least 8 characters.";
    }

    if ($password !== $confirmPassword) {
        $errors[] = "Passwords do not match.";
    }

    if (empty($errors)) {

        $stmt = $pdo->prepare(
            "SELECT id FROM users WHERE email = ? LIMIT 1"
        );

        $stmt->execute([$email]);

        if ($stmt->fetch()) {

            $errors[] = "Email already exists.";

        } else {

            $passwordHash = password_hash(
                $password,
                PASSWORD_DEFAULT
            );

            $stmt = $pdo->prepare(
                "INSERT INTO users (name, email, password)
                 VALUES (?, ?, ?)"
            );

            $stmt->execute([
                $name,
                $email,
                $passwordHash
            ]);

            header("Location: login.php?registered=1");
            exit;
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

                    <h2 class="fw-bold mb-2">
                        Create Account
                    </h2>

                    <p class="text-muted mb-4">
                        Register to start shopping.
                    </p>


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
                                Full Name
                            </label>

                            <input
                                type="text"
                                name="name"
                                class="form-control"
                                value="<?= e($_POST['name'] ?? '') ?>"
                                required
                            >

                        </div>


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


                        <div class="mb-3">

                            <label class="form-label">
                                Password
                            </label>

                            <input
                                type="password"
                                name="password"
                                class="form-control"
                                required
                            >

                            <small class="text-muted">
                                Minimum 8 characters
                            </small>

                        </div>


                        <div class="mb-4">

                            <label class="form-label">
                                Confirm Password
                            </label>

                            <input
                                type="password"
                                name="confirm_password"
                                class="form-control"
                                required
                            >

                        </div>


                        <button
                            type="submit"
                            class="btn btn-primary w-100"
                        >
                            Create Account
                        </button>

                    </form>


                    <p class="text-center mt-4 mb-0">

                        Already have an account?

                        <a href="login.php">
                            Login
                        </a>

                    </p>

                </div>

            </div>

        </div>

    </div>

</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>