<?php

declare(strict_types=1);

namespace App\Repositories;

use PDO;

final class PokemonRepository
{
    public function __construct(private readonly PDO $pdo)
    {
    }

    public function countAll(): int
    {
        $statement = $this->pdo->query('SELECT COUNT(*) AS count FROM pokemon');
        $row = $statement->fetch();

        return isset($row['count']) ? (int) $row['count'] : 0;
    }

    public function upsertByPokedexId(int $pokedexId, string $name, string $spriteUrl, string $artworkUrl): void
    {
        $now = gmdate('Y-m-d H:i:s');

        $statement = $this->pdo->prepare(
            'INSERT INTO pokemon (pokedex_id, name, sprite_url, official_artwork_url, created_at)
             VALUES (:pokedex_id, :name, :sprite_url, :official_artwork_url, :created_at)
             ON DUPLICATE KEY UPDATE
                name = VALUES(name),
                sprite_url = VALUES(sprite_url),
                official_artwork_url = VALUES(official_artwork_url)'
        );

        $statement->execute([
            'pokedex_id' => $pokedexId,
            'name' => $name,
            'sprite_url' => $spriteUrl,
            'official_artwork_url' => $artworkUrl,
            'created_at' => $now,
        ]);
    }

    public function getRandomByCategory(int $categoryId, int $limit = 2): array
    {
        $safeLimit = max(2, $limit);

        $sql = sprintf(
            'SELECT p.id, p.pokedex_id, p.name, p.sprite_url, p.official_artwork_url
             FROM ratings r
             INNER JOIN pokemon p ON p.id = r.pokemon_id
             WHERE r.category_id = :category_id
             ORDER BY RAND()
             LIMIT %d',
            $safeLimit
        );

        $statement = $this->pdo->prepare($sql);
        $statement->execute(['category_id' => $categoryId]);

        return $statement->fetchAll();
    }

    public function findByIds(array $pokemonIds): array
    {
        $pokemonIds = array_values(array_unique(array_map('intval', $pokemonIds)));

        if ($pokemonIds === []) {
            return [];
        }

        $placeholders = implode(',', array_fill(0, count($pokemonIds), '?'));

        $statement = $this->pdo->prepare(
            sprintf(
                'SELECT id, pokedex_id, name, sprite_url, official_artwork_url
                 FROM pokemon
                 WHERE id IN (%s)',
                $placeholders
            )
        );

        $statement->execute($pokemonIds);
        $rows = $statement->fetchAll();

        $mapped = [];
        foreach ($rows as $row) {
            $mapped[(int) $row['id']] = $row;
        }

        return $mapped;
    }
}
