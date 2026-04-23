<?php

declare(strict_types=1);

namespace App\Repositories;

use PDO;

final class VoteRepository
{
    public function __construct(private readonly PDO $pdo)
    {
    }

    public function insertVote(
        int $categoryId,
        int $leftPokemonId,
        int $rightPokemonId,
        int $winnerPokemonId,
        int $loserPokemonId,
        string $ipHash,
        string $userAgentHash
    ): void {
        $statement = $this->pdo->prepare(
            'INSERT INTO votes
             (
                category_id,
                left_pokemon_id,
                right_pokemon_id,
                winner_pokemon_id,
                loser_pokemon_id,
                ip_hash,
                user_agent_hash,
                created_at
             )
             VALUES
             (
                :category_id,
                :left_pokemon_id,
                :right_pokemon_id,
                :winner_pokemon_id,
                :loser_pokemon_id,
                :ip_hash,
                :user_agent_hash,
                :created_at
             )'
        );

        $statement->execute([
            'category_id' => $categoryId,
            'left_pokemon_id' => $leftPokemonId,
            'right_pokemon_id' => $rightPokemonId,
            'winner_pokemon_id' => $winnerPokemonId,
            'loser_pokemon_id' => $loserPokemonId,
            'ip_hash' => $ipHash,
            'user_agent_hash' => $userAgentHash,
            'created_at' => gmdate('Y-m-d H:i:s'),
        ]);
    }

    public function countVotesForIpSince(string $ipHash, string $sinceDateTime): int
    {
        $statement = $this->pdo->prepare(
            'SELECT COUNT(*) AS count
             FROM votes
             WHERE ip_hash = :ip_hash
               AND created_at >= :since_datetime'
        );

        $statement->execute([
            'ip_hash' => $ipHash,
            'since_datetime' => $sinceDateTime,
        ]);

        $row = $statement->fetch();

        return isset($row['count']) ? (int) $row['count'] : 0;
    }

    public function getLastVoteAtForIp(string $ipHash): ?string
    {
        $statement = $this->pdo->prepare(
            'SELECT created_at
             FROM votes
             WHERE ip_hash = :ip_hash
             ORDER BY created_at DESC
             LIMIT 1'
        );

        $statement->execute(['ip_hash' => $ipHash]);
        $row = $statement->fetch();

        if ($row === false || !isset($row['created_at'])) {
            return null;
        }

        return (string) $row['created_at'];
    }

    public function countPairVotesForIpSince(
        string $ipHash,
        int $categoryId,
        int $pokemonA,
        int $pokemonB,
        string $sinceDateTime
    ): int {
        [$first, $second] = PairRepository::normalize($pokemonA, $pokemonB);

        $statement = $this->pdo->prepare(
            'SELECT COUNT(*) AS count
             FROM votes
             WHERE ip_hash = :ip_hash
               AND category_id = :category_id
               AND created_at >= :since_datetime
               AND (
                    (left_pokemon_id = :first_a AND right_pokemon_id = :second_a)
                    OR
                    (left_pokemon_id = :first_b AND right_pokemon_id = :second_b)
               )'
        );

        $statement->execute([
            'ip_hash' => $ipHash,
            'category_id' => $categoryId,
            'since_datetime' => $sinceDateTime,
            'first_a' => $first,
            'second_a' => $second,
            'first_b' => $second,
            'second_b' => $first,
        ]);

        $row = $statement->fetch();

        return isset($row['count']) ? (int) $row['count'] : 0;
    }

    public function recordRateLimitEvent(string $ipHash, string $eventType): void
    {
        $statement = $this->pdo->prepare(
            'INSERT INTO rate_limit_events (ip_hash, event_type, created_at)
             VALUES (:ip_hash, :event_type, :created_at)'
        );

        $statement->execute([
            'ip_hash' => $ipHash,
            'event_type' => $eventType,
            'created_at' => gmdate('Y-m-d H:i:s'),
        ]);
    }
}
