<?php

declare(strict_types=1);

namespace App\Core;

use App\Repositories\CategoryRepository;
use App\Repositories\PairRepository;
use App\Repositories\PokemonRepository;
use App\Repositories\RatingRepository;
use App\Repositories\VoteRepository;
use App\Services\CategoryService;
use App\Services\EloService;
use App\Services\NextPairService;
use App\Services\VoteService;
use RuntimeException;

final class AppFactory
{
    public static function make(): array
    {
        $config = require dirname(__DIR__) . '/Config/app.php';
        $pdo = Database::connection();

        $categoryRepository = new CategoryRepository($pdo);
        $pokemonRepository = new PokemonRepository($pdo);
        $ratingRepository = new RatingRepository($pdo);
        $pairRepository = new PairRepository($pdo);
        $voteRepository = new VoteRepository($pdo);

        $overallCategory = $categoryRepository->findBySlug((string) $config['overall_slug']);

        if ($overallCategory === null) {
            throw new RuntimeException('Overall category is missing. Run migrations and seeds first.');
        }

        $eloService = new EloService((float) $config['elo_k']);

        return [
            'config' => $config,
            'pdo' => $pdo,
            'repositories' => [
                'category' => $categoryRepository,
                'pokemon' => $pokemonRepository,
                'rating' => $ratingRepository,
                'pair' => $pairRepository,
                'vote' => $voteRepository,
            ],
            'services' => [
                'elo' => $eloService,
                'nextPair' => new NextPairService(
                    $pokemonRepository,
                    $pairRepository,
                    (int) $config['pair_cooldown_seconds']
                ),
                'vote' => new VoteService(
                    $pdo,
                    $categoryRepository,
                    $pokemonRepository,
                    $ratingRepository,
                    $voteRepository,
                    $pairRepository,
                    $eloService,
                    (int) $overallCategory['id'],
                    (int) $config['ip_hourly_vote_limit'],
                    (int) $config['vote_cooldown_seconds'],
                    (int) $config['max_pair_votes_per_ip_per_day']
                ),
                'category' => new CategoryService(
                    $categoryRepository,
                    $ratingRepository,
                    (float) $config['elo_base']
                ),
            ],
            'overall_category' => $overallCategory,
        ];
    }
}
