<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/src/bootstrap.php';

use App\Core\Database;
use App\Core\Env;
use App\Repositories\CategoryRepository;
use App\Repositories\RatingRepository;
use App\Services\CategoryService;

$pdo = Database::connection();
$categoryRepository = new CategoryRepository($pdo);
$ratingRepository = new RatingRepository($pdo);

$baseElo = Env::getFloat('ELO_BASE', 1500.0);
$service = new CategoryService($categoryRepository, $ratingRepository, $baseElo);

$defaults = [
    ['name' => 'Overall', 'slug' => 'overall'],
    ['name' => 'Cuteness', 'slug' => 'cuteness'],
    ['name' => 'Coolest', 'slug' => 'coolest'],
    ['name' => 'Hottest', 'slug' => 'hottest'],
];

foreach ($defaults as $category) {
    $existing = $categoryRepository->findBySlug($category['slug']);

    if ($existing !== null) {
        $ratingRepository->ensureRatingsForCategory((int) $existing['id'], $baseElo);
        echo "Ensured ratings for {$category['name']}\n";
        continue;
    }

    $id = $service->addCategory($category['name'], $category['slug']);
    echo "Created {$category['name']} ({$id})\n";
}

echo "Category seed complete.\n";
