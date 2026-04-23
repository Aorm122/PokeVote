<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Core\Security;
use PDO;

final class CategoryRepository
{
    public function __construct(private readonly PDO $pdo)
    {
    }

    public function getActive(): array
    {
        $statement = $this->pdo->query('SELECT id, slug, name, is_active FROM categories WHERE is_active = 1 ORDER BY name ASC');
        return $statement->fetchAll();
    }

    public function getAll(): array
    {
        $statement = $this->pdo->query('SELECT id, slug, name, is_active, created_at, updated_at FROM categories ORDER BY created_at DESC');
        return $statement->fetchAll();
    }

    public function findById(int $categoryId): ?array
    {
        $statement = $this->pdo->prepare('SELECT id, slug, name, is_active FROM categories WHERE id = :id LIMIT 1');
        $statement->execute(['id' => $categoryId]);
        $category = $statement->fetch();

        return $category === false ? null : $category;
    }

    public function findBySlug(string $slug): ?array
    {
        $statement = $this->pdo->prepare('SELECT id, slug, name, is_active FROM categories WHERE slug = :slug LIMIT 1');
        $statement->execute(['slug' => $slug]);
        $category = $statement->fetch();

        return $category === false ? null : $category;
    }

    public function create(string $name, ?string $slug = null): int
    {
        $finalSlug = $slug !== null && $slug !== '' ? Security::slugify($slug) : Security::slugify($name);

        $statement = $this->pdo->prepare(
            'INSERT INTO categories (slug, name, is_active, created_at, updated_at)
             VALUES (:slug, :name, 1, :created_at, :updated_at)'
        );

        $now = gmdate('Y-m-d H:i:s');

        $statement->execute([
            'slug' => $finalSlug,
            'name' => trim($name),
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        return (int) $this->pdo->lastInsertId();
    }

    public function setActive(int $categoryId, bool $isActive): void
    {
        $statement = $this->pdo->prepare('UPDATE categories SET is_active = :is_active, updated_at = :updated_at WHERE id = :id');

        $statement->execute([
            'id' => $categoryId,
            'is_active' => $isActive ? 1 : 0,
            'updated_at' => gmdate('Y-m-d H:i:s'),
        ]);
    }
}
