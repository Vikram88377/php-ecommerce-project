<?php

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/security.php';
require_once __DIR__ . '/../../middleware/admin_auth.php';

require_once __DIR__ . '/../../repositories/ProductRepository.php';
require_once __DIR__ . '/../../services/ProductService.php';

$repository = new ProductRepository($pdo);
$service = new ProductService($repository);

$id = (int) ($_GET['id'] ?? 0);

if ($id <= 0) {
    header("Location: index.php");
    exit;
}

try {

    $product = $service->getById($id);

    if (!$product) {
        throw new Exception("Product not found.");
    }

    /*
     * Get product images
     */
    $stmt = $pdo->prepare(
        "SELECT image
         FROM product_images
         WHERE product_id = ?"
    );

    $stmt->execute([$id]);

    $images = $stmt->fetchAll();


    /*
     * Delete product images from database
     */
    $stmt = $pdo->prepare(
        "DELETE FROM product_images
         WHERE product_id = ?"
    );

    $stmt->execute([$id]);


    /*
     * Delete product
     */
    $service->delete($id);


    /*
     * Delete image files
     */
    foreach ($images as $image) {

        $imagePath = __DIR__ . '/uploads/' . $image['image'];

        if (
            !empty($image['image']) &&
            file_exists($imagePath)
        ) {
            unlink($imagePath);
        }
    }


    header(
        "Location: index.php?message=" .
        urlencode("Product deleted successfully")
    );

    exit;

} catch (Exception $e) {

    header(
        "Location: index.php?error=" .
        urlencode($e->getMessage())
    );

    exit;
}