<?php

declare(strict_types=1);

require_once dirname(__DIR__, 2) . '/src/bootstrap.php';

use App\Core\AppFactory;
use App\Core\Http;

try {
    $app = AppFactory::make();

    $categoryId = isset($_GET['category_id']) ? (int) $_GET['category_id'] : 0;
    if ($categoryId <= 0) {
        Http::badRequest('Missing or invalid category_id.');
    }

    /** @var App\Repositories\CategoryRepository $categoryRepository */
    $categoryRepository = $app['repositories']['category'];

    $category = $categoryRepository->findById($categoryId);
    if ($category === null || (int) $category['is_active'] !== 1) {
        Http::badRequest('Category is invalid or inactive.');
    }

    /** @var App\Services\NextPairService $nextPairService */
    $nextPairService = $app['services']['nextPair'];

    $pair = $nextPairService->nextPair($categoryId);

    if ($pair === null) {
        Http::json([
            'success' => false,
            'error' => 'No available pair right now. Import pokemon and seed ratings first.',
        ], 404);
    }

    Http::json([
        'success' => true,
        'category' => [
            'id' => (int) $category['id'],
            'name' => (string) $category['name'],
        ],
        'pair' => [
            'left' => [
                'id' => (int) $pair['left']['id'],
                'pokedex_id' => (int) $pair['left']['pokedex_id'],
                'name' => (string) $pair['left']['name'],
                'sprite_url' => (string) $pair['left']['sprite_url'],
                'official_artwork_url' => (string) $pair['left']['official_artwork_url'],
            ],
            'right' => [
                'id' => (int) $pair['right']['id'],
                'pokedex_id' => (int) $pair['right']['pokedex_id'],
                'name' => (string) $pair['right']['name'],
                'sprite_url' => (string) $pair['right']['sprite_url'],
                'official_artwork_url' => (string) $pair['right']['official_artwork_url'],
            ],
        ],
    ]);
} catch (Throwable $throwable) {
    Http::serverError($throwable->getMessage());
}
