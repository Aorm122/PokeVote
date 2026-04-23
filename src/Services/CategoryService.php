<?php

declare(strict_types=1);

namespace App\Services;

use App\Repositories\CategoryRepository;
use App\Repositories\RatingRepository;

final class CategoryService
{
    public function __construct(
        private readonly CategoryRepository $categoryRepository,
        private readonly RatingRepository $ratingRepository,
        private readonly float $baseElo
    ) {
    }

    public function addCategory(string $name, ?string $slug = null): int
    {
        $categoryId = $this->categoryRepository->create($name, $slug);
        $this->ratingRepository->ensureRatingsForCategory($categoryId, $this->baseElo);

        return $categoryId;
    }
}
