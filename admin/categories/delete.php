<?php

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/security.php';
require_once __DIR__ . '/../../middleware/admin_auth.php';

require_once __DIR__ . '/../../repositories/CategoryRepository.php';
require_once __DIR__ . '/../../services/CategoryService.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    exit("Method Not Allowed");
}

if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) {
    exit("Invalid request.");
}

$id = (int) ($_POST['id'] ?? 0);

try {

    $repository = new CategoryRepository($pdo);
    $service = new CategoryService($repository);

    $service->delete($id);

    header("Location: index.php?message=Category deleted successfully");
    exit;

} catch (Exception $e) {

    header(
        "Location: index.php?error=" .
        urlencode($e->getMessage())
    );

    exit;
}