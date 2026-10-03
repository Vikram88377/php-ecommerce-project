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
| Coupon ID
|--------------------------------------------------------------------------
*/

$id = (int) ($_POST['id'] ?? 0);

if ($id <= 0) {
    header("Location: index.php");
    exit;
}


/*
|--------------------------------------------------------------------------
| Get Current Status
|--------------------------------------------------------------------------
*/

$stmt = $pdo->prepare("
    SELECT status
    FROM coupons
    WHERE id = ?
    LIMIT 1
");

$stmt->execute([$id]);

$coupon = $stmt->fetch();

if (!$coupon) {
    header("Location: index.php");
    exit;
}


/*
|--------------------------------------------------------------------------
| Toggle Status
|--------------------------------------------------------------------------
*/

$newStatus =
    $coupon['status'] === 'active'
        ? 'inactive'
        : 'active';


$updateStmt = $pdo->prepare("
    UPDATE coupons
    SET
        status = ?,
        updated_at = NOW()
    WHERE id = ?
");

$updateStmt->execute([
    $newStatus,
    $id
]);


/*
|--------------------------------------------------------------------------
| Redirect
|--------------------------------------------------------------------------
*/

header(
    "Location: index.php?success=status"
);

exit;