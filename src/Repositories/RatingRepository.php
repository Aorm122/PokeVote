<?php

declare(strict_types=1);

namespace App\Repositories;

use PDO;

final class RatingRepository
{
    public function __construct(private readonly PDO $pdo)
    {
    }

    public function ensureRatingsForCategory(int $categoryId, float $baseElo): void
    {
        $statement = $this->pdo->prepare(
            'INSERT INTO ratings (pokemon_id, category_id, elo, votes_count, wins_count, losses_count, updated_at)
             SELECT p.id, ?, ?, 0, 0, 0, ?
             FROM pokemon p
             LEFT JOIN ratings r ON r.pokemon_id = p.id AND r.category_id = ?
             WHERE r.id IS NULL'
        );

        $statement->execute([
            $categoryId,
            $baseElo,
            gmdate('Y-m-d H:i:s'),
            $categoryId,
        ]);
    }

    public function getForUpdate(int $pokemonId, int $categoryId): ?array
    {
        $statement = $this->pdo->prepare(
            'SELECT id, pokemon_id, category_id, elo, votes_count, wins_count, losses_count
             FROM ratings
             WHERE pokemon_id = :pokemon_id AND category_id = :category_id
             LIMIT 1
             FOR UPDATE'
        );

        $statement->execute([
            'pokemon_id' => $pokemonId,
            'category_id' => $categoryId,
        ]);

        $rating = $statement->fetch();

        return $rating === false ? null : $rating;
    }

    public function updateAfterMatch(int $pokemonId, int $categoryId, float $newElo, bool $isWinner): void
    {
        $statement = $this->pdo->prepare(
            'UPDATE ratings
             SET elo = :elo,
                 votes_count = votes_count + 1,
                 wins_count = wins_count + :wins_delta,
                 losses_count = losses_count + :losses_delta,
                 updated_at = :updated_at
             WHERE pokemon_id = :pokemon_id
               AND category_id = :category_id'
        );

        $statement->execute([
            'elo' => round($newElo, 4),
            'wins_delta' => $isWinner ? 1 : 0,
            'losses_delta' => $isWinner ? 0 : 1,
            'updated_at' => gmdate('Y-m-d H:i:s'),
            'pokemon_id' => $pokemonId,
            'category_id' => $categoryId,
        ]);
    }

    public function getLeaderboard(int $categoryId, int $limit = 200): array
    {
        $safeLimit = max(1, $limit);

        $sql = sprintf(
            'SELECT p.id AS pokemon_id,
                    p.pokedex_id,
                    p.name,
                    p.sprite_url,
                    p.official_artwork_url,
                    r.elo,
                    r.votes_count,
                    r.wins_count,
                    r.losses_count
             FROM ratings r
             INNER JOIN pokemon p ON p.id = r.pokemon_id
             WHERE r.category_id = :category_id
             ORDER BY r.elo DESC, r.votes_count DESC, p.pokedex_id ASC
             LIMIT %d',
            $safeLimit
        );

        $statement = $this->pdo->prepare($sql);
        $statement->execute(['category_id' => $categoryId]);

        return $statement->fetchAll();
    }
}
