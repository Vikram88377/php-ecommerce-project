<?php

class ProductRepository
{
    private PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    public function getAll()
    {
        $sql = "SELECT
                    p.*,
                    c.name AS category_name
                FROM products p
                LEFT JOIN categories c
                    ON c.id = p.category_id
                ORDER BY p.id DESC";

        return $this->pdo->query($sql)->fetchAll();
    }

    public function findById(int $id)
    {
        $stmt = $this->pdo->prepare(
            "SELECT * FROM products WHERE id = ?"
        );

        $stmt->execute([$id]);

        return $stmt->fetch();
    }

    public function findBySku(string $sku, ?int $excludeId = null)
    {
        if ($excludeId) {

            $stmt = $this->pdo->prepare(
                "SELECT id FROM products
                 WHERE sku = ? AND id != ?"
            );

            $stmt->execute([$sku, $excludeId]);

        } else {

            $stmt = $this->pdo->prepare(
                "SELECT id FROM products
                 WHERE sku = ?"
            );

            $stmt->execute([$sku]);
        }

        return $stmt->fetch();
    }

    public function getCategories()
    {
        $stmt = $this->pdo->query(
            "SELECT id, name
             FROM categories
             WHERE status = 'active'
             ORDER BY name ASC"
        );

        return $stmt->fetchAll();
    }

    public function create(
        int $categoryId,
        string $name,
        string $slug,
        string $sku,
        string $description,
        float $price,
        int $stock,
        string $status,
        ?string $image
    ) {

        $this->pdo->beginTransaction();

        try {

            /*
             * Create product
             */
            $stmt = $this->pdo->prepare(
                "INSERT INTO products
                (
                    category_id,
                    name,
                    slug,
                    sku,
                    description,
                    price,
                    stock,
                    status
                )
                VALUES (?, ?, ?, ?, ?, ?, ?, ?)"
            );

            $stmt->execute([
                $categoryId,
                $name,
                $slug,
                $sku,
                $description,
                $price,
                $stock,
                $status
            ]);

            $productId = (int) $this->pdo->lastInsertId();


            /*
             * Save product image
             */
            if ($image !== null) {

                $stmt = $this->pdo->prepare(
                    "INSERT INTO product_images
                    (
                        product_id,
                        image,
                        is_primary
                    )
                    VALUES (?, ?, 1)"
                );

                $stmt->execute([
                    $productId,
                    $image
                ]);
            }

            $this->pdo->commit();

            return $productId;

        } catch (Exception $e) {

            $this->pdo->rollBack();

            throw $e;
        }
    }

    public function update(
        int $id,
        int $categoryId,
        string $name,
        string $slug,
        string $sku,
        string $description,
        float $price,
        int $stock,
        string $status,
        ?string $image = null
    ) {

        $this->pdo->beginTransaction();

        try {

            $stmt = $this->pdo->prepare(
                "UPDATE products
                 SET category_id = ?,
                     name = ?,
                     slug = ?,
                     sku = ?,
                     description = ?,
                     price = ?,
                     stock = ?,
                     status = ?,
                     updated_at = NOW()
                 WHERE id = ?"
            );

            $stmt->execute([
                $categoryId,
                $name,
                $slug,
                $sku,
                $description,
                $price,
                $stock,
                $status,
                $id
            ]);


            /*
             * Save new image
             */
            if ($image !== null) {

                $stmt = $this->pdo->prepare(
                    "UPDATE product_images
                     SET is_primary = 0
                     WHERE product_id = ?"
                );

                $stmt->execute([$id]);


                $stmt = $this->pdo->prepare(
                    "INSERT INTO product_images
                    (
                        product_id,
                        image,
                        is_primary
                    )
                    VALUES (?, ?, 1)"
                );

                $stmt->execute([
                    $id,
                    $image
                ]);
            }

            $this->pdo->commit();

            return true;

        } catch (Exception $e) {

            $this->pdo->rollBack();

            throw $e;
        }
    }

    public function delete(int $id)
    {
        $stmt = $this->pdo->prepare(
            "DELETE FROM products WHERE id = ?"
        );

        return $stmt->execute([$id]);
    }
}