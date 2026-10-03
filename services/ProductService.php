<?php

class ProductService
{
    private ProductRepository $repository;

    public function __construct(ProductRepository $repository)
    {
        $this->repository = $repository;
    }

    private function makeSlug(string $name): string
    {
        $slug = strtolower(trim($name));

        $slug = preg_replace('/[^a-z0-9]+/', '-', $slug);

        return trim($slug, '-');
    }

    private function validate(
        string $name,
        string $sku,
        float $price,
        int $stock,
        string $status
    ): void {

        if (strlen($name) < 2 || strlen($name) > 255) {
            throw new Exception(
                "Product name must be between 2 and 255 characters."
            );
        }

        if ($sku === '') {
            throw new Exception("SKU is required.");
        }

        if ($price < 0) {
            throw new Exception("Price cannot be negative.");
        }

        if ($stock < 0) {
            throw new Exception("Stock cannot be negative.");
        }

        if (!in_array($status, ['active', 'inactive'])) {
            throw new Exception("Invalid product status.");
        }
    }

    public function getAll()
    {
        return $this->repository->getAll();
    }

    public function getById(int $id)
    {
        return $this->repository->findById($id);
    }

    public function getCategories()
    {
        return $this->repository->getCategories();
    }

    public function create(
        int $categoryId,
        string $name,
        string $sku,
        string $description,
        float $price,
        int $stock,
        string $status,
        ?string $image
    ) {

        $name = trim($name);
        $sku = trim($sku);

        $this->validate(
            $name,
            $sku,
            $price,
            $stock,
            $status
        );

        if ($this->repository->findBySku($sku)) {
            throw new Exception("SKU already exists.");
        }

        $slug = $this->makeSlug($name);

        if (!$slug) {
            throw new Exception("Invalid product name.");
        }

        return $this->repository->create(
            $categoryId,
            $name,
            $slug,
            $sku,
            trim($description),
            $price,
            $stock,
            $status,
            $image
        );
    }

    public function update(
        int $id,
        int $categoryId,
        string $name,
        string $sku,
        string $description,
        float $price,
        int $stock,
        string $status,
        ?string $image
    ) {

        $name = trim($name);
        $sku = trim($sku);

        $this->validate(
            $name,
            $sku,
            $price,
            $stock,
            $status
        );

        $product = $this->repository->findById($id);

        if (!$product) {
            throw new Exception("Product not found.");
        }

        if ($this->repository->findBySku($sku, $id)) {
            throw new Exception("SKU already exists.");
        }

        $slug = $this->makeSlug($name);

        return $this->repository->update(
            $id,
            $categoryId,
            $name,
            $slug,
            $sku,
            trim($description),
            $price,
            $stock,
            $status,
            $image
        );
    }

    public function delete(int $id)
    {
        $product = $this->repository->findById($id);

        if (!$product) {
            throw new Exception("Product not found.");
        }

        return $this->repository->delete($id);
    }
}