<?php

class CategoryService
{
    private CategoryRepository $repository;

    public function __construct(CategoryRepository $repository)
    {
        $this->repository = $repository;
    }

    private function makeSlug(string $name): string
    {
        $slug = strtolower(trim($name));

        $slug = preg_replace('/[^a-z0-9]+/', '-', $slug);

        return trim($slug, '-');
    }

    private function validate(string $name, string $status): void
    {
        if (strlen($name) < 2 || strlen($name) > 100) {
            throw new Exception(
                "Category name must be between 2 and 100 characters."
            );
        }

        if (!in_array($status, ['active', 'inactive'])) {
            throw new Exception("Invalid category status.");
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

    public function create(string $name, string $status)
    {
        $name = trim($name);

        $this->validate($name, $status);

        $slug = $this->makeSlug($name);

        if (!$slug) {
            throw new Exception("Invalid category name.");
        }

        if ($this->repository->findBySlug($slug)) {
            throw new Exception("Category already exists.");
        }

        return $this->repository->create(
            $name,
            $slug,
            $status
        );
    }

    public function update(
        int $id,
        string $name,
        string $status
    ) {
        $name = trim($name);

        $this->validate($name, $status);

        $category = $this->repository->findById($id);

        if (!$category) {
            throw new Exception("Category not found.");
        }

        $slug = $this->makeSlug($name);

        if ($this->repository->findBySlug($slug, $id)) {
            throw new Exception("Category already exists.");
        }

        return $this->repository->update(
            $id,
            $name,
            $slug,
            $status
        );
    }

    public function delete(int $id)
    {
        $category = $this->repository->findById($id);

        if (!$category) {
            throw new Exception("Category not found.");
        }

        if ($this->repository->countProducts($id) > 0) {
            throw new Exception(
                "Cannot delete category because products are linked to it."
            );
        }

        if ($this->repository->countSubCategories($id) > 0) {
            throw new Exception(
                "Cannot delete category because sub-categories are linked to it."
            );
        }

        return $this->repository->delete($id);
    }
}