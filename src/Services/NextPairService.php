<?php

declare(strict_types=1);

namespace App\Services;

use App\Repositories\PairRepository;
use App\Repositories\PokemonRepository;

final class NextPairService
{
    public function __construct(
        private readonly PokemonRepository $pokemonRepository,
        private readonly PairRepository $pairRepository,
        private readonly int $pairCooldownSeconds,
        private readonly int $maxAttempts = 25
    ) {
    }

    public function nextPair(int $categoryId): ?array
    {
        $attempts = 0;
        $candidateRows = [];

        while ($attempts < $this->maxAttempts) {
            $attempts++;
            $candidateRows = $this->pokemonRepository->getRandomByCategory($categoryId, 2);

            if (count($candidateRows) < 2) {
                continue;
            }

            $left = $candidateRows[0];
            $right = $candidateRows[1];

            $leftId = (int) $left['id'];
            $rightId = (int) $right['id'];

            if ($leftId === $rightId) {
                continue;
            }

            $recent = $this->pairRepository->wasShownRecently(
                $categoryId,
                $leftId,
                $rightId,
                $this->pairCooldownSeconds
            );

            if ($recent && $attempts < $this->maxAttempts) {
                continue;
            }

            $this->pairRepository->touchShown($categoryId, $leftId, $rightId);

            return [
                'left' => $left,
                'right' => $right,
            ];
        }

        if (count($candidateRows) >= 2) {
            $left = $candidateRows[0];
            $right = $candidateRows[1];

            if ((int) $left['id'] !== (int) $right['id']) {
                $this->pairRepository->touchShown($categoryId, (int) $left['id'], (int) $right['id']);

                return [
                    'left' => $left,
                    'right' => $right,
                ];
            }
        }

        return null;
    }
}
