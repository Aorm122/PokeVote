<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Security;
use App\Repositories\CategoryRepository;
use App\Repositories\PairRepository;
use App\Repositories\PokemonRepository;
use App\Repositories\RatingRepository;
use App\Repositories\VoteRepository;
use DateTimeImmutable;
use PDO;
use RuntimeException;

final class VoteService
{
    public function __construct(
        private readonly PDO $pdo,
        private readonly CategoryRepository $categoryRepository,
        private readonly PokemonRepository $pokemonRepository,
        private readonly RatingRepository $ratingRepository,
        private readonly VoteRepository $voteRepository,
        private readonly PairRepository $pairRepository,
        private readonly EloService $eloService,
        private readonly int $overallCategoryId,
        private readonly int $ipHourlyVoteLimit,
        private readonly int $voteCooldownSeconds,
        private readonly int $maxPairVotesPerIpPerDay
    ) {
    }

    public function validateRateLimit(string $ip, string $userAgent, int $categoryId, int $leftPokemonId, int $rightPokemonId): array
    {
        $ipHash = Security::hashWithSecret($ip);
        $userAgentHash = Security::hashWithSecret($userAgent);

        $hourAgo = (new DateTimeImmutable('-1 hour'))->format('Y-m-d H:i:s');
        $votesInLastHour = $this->voteRepository->countVotesForIpSince($ipHash, $hourAgo);

        if ($votesInLastHour >= $this->ipHourlyVoteLimit) {
            $this->voteRepository->recordRateLimitEvent($ipHash, 'hourly_limit');
            return [false, 'Hourly vote limit reached. Please try again later.'];
        }

        $lastVoteAt = $this->voteRepository->getLastVoteAtForIp($ipHash);
        if ($lastVoteAt !== null) {
            $lastVoteEpoch = strtotime($lastVoteAt);
            if ($lastVoteEpoch !== false && (time() - $lastVoteEpoch) < $this->voteCooldownSeconds) {
                $this->voteRepository->recordRateLimitEvent($ipHash, 'cooldown');
                return [false, 'Please wait a moment before voting again.'];
            }
        }

        $dayAgo = (new DateTimeImmutable('-1 day'))->format('Y-m-d H:i:s');
        $pairVotes = $this->voteRepository->countPairVotesForIpSince(
            $ipHash,
            $categoryId,
            $leftPokemonId,
            $rightPokemonId,
            $dayAgo
        );

        if ($pairVotes >= $this->maxPairVotesPerIpPerDay) {
            $this->voteRepository->recordRateLimitEvent($ipHash, 'pair_limit');
            return [false, 'You have voted on this pair too many times today.'];
        }

        return [true, ['ip_hash' => $ipHash, 'user_agent_hash' => $userAgentHash]];
    }

    public function castVote(
        int $categoryId,
        int $leftPokemonId,
        int $rightPokemonId,
        int $winnerPokemonId,
        string $ipHash,
        string $userAgentHash
    ): array {
        if ($leftPokemonId === $rightPokemonId) {
            throw new RuntimeException('Invalid pair.');
        }

        if ($winnerPokemonId !== $leftPokemonId && $winnerPokemonId !== $rightPokemonId) {
            throw new RuntimeException('Winner must match one of the displayed pokemon.');
        }

        $category = $this->categoryRepository->findById($categoryId);
        if ($category === null || (int) $category['is_active'] !== 1) {
            throw new RuntimeException('Category not found or inactive.');
        }

        $loserPokemonId = $winnerPokemonId === $leftPokemonId ? $rightPokemonId : $leftPokemonId;

        $pokemonMap = $this->pokemonRepository->findByIds([$leftPokemonId, $rightPokemonId]);
        if (!isset($pokemonMap[$leftPokemonId], $pokemonMap[$rightPokemonId])) {
            throw new RuntimeException('Pokemon pair no longer valid.');
        }

        $this->pdo->beginTransaction();

        try {
            $categoryWinnerRating = $this->ratingRepository->getForUpdate($winnerPokemonId, $categoryId);
            $categoryLoserRating = $this->ratingRepository->getForUpdate($loserPokemonId, $categoryId);

            $overallWinnerRating = $this->ratingRepository->getForUpdate($winnerPokemonId, $this->overallCategoryId);
            $overallLoserRating = $this->ratingRepository->getForUpdate($loserPokemonId, $this->overallCategoryId);

            if ($categoryWinnerRating === null || $categoryLoserRating === null || $overallWinnerRating === null || $overallLoserRating === null) {
                throw new RuntimeException('Ratings are missing for one or more pokemon.');
            }

            $categoryNew = $this->eloService->calculateNewRatings(
                (float) $categoryWinnerRating['elo'],
                (float) $categoryLoserRating['elo']
            );

            $overallNew = $this->eloService->calculateNewRatings(
                (float) $overallWinnerRating['elo'],
                (float) $overallLoserRating['elo']
            );

            $this->ratingRepository->updateAfterMatch($winnerPokemonId, $categoryId, $categoryNew['winner'], true);
            $this->ratingRepository->updateAfterMatch($loserPokemonId, $categoryId, $categoryNew['loser'], false);

            $this->ratingRepository->updateAfterMatch($winnerPokemonId, $this->overallCategoryId, $overallNew['winner'], true);
            $this->ratingRepository->updateAfterMatch($loserPokemonId, $this->overallCategoryId, $overallNew['loser'], false);

            $this->voteRepository->insertVote(
                $categoryId,
                $leftPokemonId,
                $rightPokemonId,
                $winnerPokemonId,
                $loserPokemonId,
                $ipHash,
                $userAgentHash
            );

            $this->pairRepository->touchVoted($categoryId, $leftPokemonId, $rightPokemonId);

            $this->pdo->commit();

            return [
                'winner_id' => $winnerPokemonId,
                'loser_id' => $loserPokemonId,
                'category_ratings' => [
                    'winner' => round($categoryNew['winner'], 2),
                    'loser' => round($categoryNew['loser'], 2),
                ],
                'overall_ratings' => [
                    'winner' => round($overallNew['winner'], 2),
                    'loser' => round($overallNew['loser'], 2),
                ],
            ];
        } catch (\Throwable $throwable) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }

            throw $throwable;
        }
    }
}
