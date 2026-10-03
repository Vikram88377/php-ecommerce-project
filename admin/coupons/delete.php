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
| Check Existing Usage
|--------------------------------------------------------------------------
*/

$usageStmt = $pdo->prepare("
    SELECT COUNT(*)
    FROM coupon_usages
    WHERE coupon_id = ?
");

$usageStmt->execute([$id]);

$usageCount = (int) $usageStmt->fetchColumn();


/*
|--------------------------------------------------------------------------
| Delete
|--------------------------------------------------------------------------
*/

if ($usageCount > 0) {

    /*
    | Coupon has already been used.
    | Do not delete it because order history
    | may depend on it.
    */

    $stmt = $pdo->prepare("
        UPDATE coupons
        SET
            status = 'inactive',
            updated_at = NOW()
        WHERE id = ?
    ");

    $stmt->execute([$id]);

} else {

    $stmt = $pdo->prepare("
        DELETE FROM coupons
        WHERE id = ?
    ");

    $stmt->execute([$id]);
}


/*
|--------------------------------------------------------------------------
| Redirect
|--------------------------------------------------------------------------
*/

header(
    "Location: index.php?success=deleted"
);

exit;