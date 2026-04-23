<?php

declare(strict_types=1);

namespace App\Repositories;

use PDO;

final class PairRepository
{
    public function __construct(private readonly PDO $pdo)
    {
    }

    public static function normalize(int $pokemonA, int $pokemonB): array
    {
        if ($pokemonA <= $pokemonB) {
            return [$pokemonA, $pokemonB];
        }

        return [$pokemonB, $pokemonA];
    }

    public static function pairKey(int $pokemonA, int $pokemonB): string
    {
        [$first, $second] = self::normalize($pokemonA, $pokemonB);
        return $first . ':' . $second;
    }

    public function wasShownRecently(int $categoryId, int $pokemonA, int $pokemonB, int $cooldownSeconds): bool
    {
        $pairKey = self::pairKey($pokemonA, $pokemonB);

        $statement = $this->pdo->prepare(
            'SELECT last_shown_at
             FROM vote_pairs
             WHERE category_id = :category_id
               AND pair_key = :pair_key
             LIMIT 1'
        );

        $statement->execute([
            'category_id' => $categoryId,
            'pair_key' => $pairKey,
        ]);

        $row = $statement->fetch();

        if ($row === false || !isset($row['last_shown_at']) || $row['last_shown_at'] === null) {
            return false;
        }

        $lastShownEpoch = strtotime((string) $row['last_shown_at']);

        if ($lastShownEpoch === false) {
            return false;
        }

        return (time() - $lastShownEpoch) < $cooldownSeconds;
    }

    public function touchShown(int $categoryId, int $pokemonA, int $pokemonB): void
    {
        [$first, $second] = self::normalize($pokemonA, $pokemonB);
        $pairKey = self::pairKey($first, $second);
        $now = gmdate('Y-m-d H:i:s');

        $update = $this->pdo->prepare(
            'UPDATE vote_pairs
             SET show_count = show_count + 1,
                 last_shown_at = :last_shown_at
             WHERE category_id = :category_id
               AND pair_key = :pair_key'
        );

        $update->execute([
            'category_id' => $categoryId,
            'pair_key' => $pairKey,
            'last_shown_at' => $now,
        ]);

        if ($update->rowCount() > 0) {
            return;
        }

        $insert = $this->pdo->prepare(
            'INSERT INTO vote_pairs
             (category_id, pokemon_a_id, pokemon_b_id, pair_key, show_count, last_shown_at, created_at)
             VALUES
             (:category_id, :pokemon_a_id, :pokemon_b_id, :pair_key, 1, :last_shown_at, :created_at)'
        );

        $insert->execute([
            'category_id' => $categoryId,
            'pokemon_a_id' => $first,
            'pokemon_b_id' => $second,
            'pair_key' => $pairKey,
            'last_shown_at' => $now,
            'created_at' => $now,
        ]);
    }

    public function touchVoted(int $categoryId, int $pokemonA, int $pokemonB): void
    {
        [$first, $second] = self::normalize($pokemonA, $pokemonB);
        $pairKey = self::pairKey($first, $second);

        $statement = $this->pdo->prepare(
            'UPDATE vote_pairs
             SET vote_count = vote_count + 1,
                 last_voted_at = :last_voted_at
             WHERE category_id = :category_id
               AND pair_key = :pair_key'
        );

        $statement->execute([
            'last_voted_at' => gmdate('Y-m-d H:i:s'),
            'category_id' => $categoryId,
            'pair_key' => $pairKey,
        ]);
    }
}
