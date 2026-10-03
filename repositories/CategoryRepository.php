<?php

class CategoryRepository
{
    private PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    public function getAll()
    {
        $sql = "SELECT *
                FROM categories
                ORDER BY id DESC";

        return $this->pdo->query($sql)->fetchAll();
    }

    public function findById(int $id)
    {
        $stmt = $this->pdo->prepare(
            "SELECT * FROM categories WHERE id = ?"
        );

        $stmt->execute([$id]);

        return $stmt->fetch();
    }

    public function findBySlug(string $slug, ?int $excludeId = null)
    {
        if ($excludeId) {
            $stmt = $this->pdo->prepare(
                "SELECT id FROM categories
                 WHERE slug = ? AND id != ?"
            );

            $stmt->execute([$slug, $excludeId]);
        } else {
            $stmt = $this->pdo->prepare(
                "SELECT id FROM categories WHERE slug = ?"
            );

            $stmt->execute([$slug]);
        }

        return $stmt->fetch();
    }

    public function create(string $name, string $slug, string $status)
    {
        $stmt = $this->pdo->prepare(
            "INSERT INTO categories (name, slug, status, created_at, updated_at)
             VALUES (?, ?, ?, NOW(), NOW())"
        );

        return $stmt->execute([
            $name,
            $slug,
            $status
        ]);
    }

    public function update(
        int $id,
        string $name,
        string $slug,
        string $status
    ) {
        $stmt = $this->pdo->prepare(
            "UPDATE categories
             SET name = ?, slug = ?, status = ?, updated_at = NOW()
             WHERE id = ?"
        );

        return $stmt->execute([
            $name,
            $slug,
            $status,
            $id
        ]);
    }

    public function countProducts(int $categoryId)
    {
        $stmt = $this->pdo->prepare(
            "SELECT COUNT(*) FROM products WHERE category_id = ?"
        );

        $stmt->execute([$categoryId]);

        return (int) $stmt->fetchColumn();
    }

    public function countSubCategories(int $categoryId)
    {
        $stmt = $this->pdo->prepare(
            "SELECT COUNT(*) FROM sub_categories WHERE category_id = ?"
        );

        $stmt->execute([$categoryId]);

        return (int) $stmt->fetchColumn();
    }

    public function delete(int $id)
    {
        $stmt = $this->pdo->prepare(
            "DELETE FROM categories WHERE id = ?"
        );

        return $stmt->execute([$id]);
    }
}