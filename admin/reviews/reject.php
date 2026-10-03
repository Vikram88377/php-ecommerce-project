<?php

require_once "../../config/database.php";
require_once "../../includes/security.php";

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}


/*
|--------------------------------------------------------------------------
| Admin Authentication
|--------------------------------------------------------------------------
*/

$userId = $_SESSION['user_id'] ?? 0;
$userRole = $_SESSION['role'] ?? '';

if ($userId <= 0 || $userRole !== 'admin') {
    header("Location: ../login.php");
    exit;
}


/*
|--------------------------------------------------------------------------
| Only POST
|--------------------------------------------------------------------------
*/

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: index.php");
    exit;
}


/*
|--------------------------------------------------------------------------
| CSRF
|--------------------------------------------------------------------------
*/

if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) {
    die("Invalid CSRF token.");
}


/*
|--------------------------------------------------------------------------
| Review ID
|--------------------------------------------------------------------------
*/

$reviewId = (int) ($_POST['review_id'] ?? 0);

if ($reviewId <= 0) {
    header("Location: index.php");
    exit;
}


/*
|--------------------------------------------------------------------------
| Reject Review
|--------------------------------------------------------------------------
*/

$stmt = $pdo->prepare("
    UPDATE reviews
    SET
        status = 'rejected',
        updated_at = NOW()
    WHERE id = ?
    AND status = 'pending'
");

$stmt->execute([
    $reviewId
]);


/*
|--------------------------------------------------------------------------
| Redirect
|--------------------------------------------------------------------------
*/

header(
    "Location: index.php?success=rejected"
);

exit;