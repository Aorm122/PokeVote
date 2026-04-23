<?php

declare(strict_types=1);

namespace App\Services;

final class EloService
{
    public function __construct(
        private readonly float $kFactor = 32.0,
        private readonly float $scale = 400.0
    ) {
    }

    public function expectedScore(float $ratingA, float $ratingB): float
    {
        return 1.0 / (1.0 + pow(10.0, ($ratingB - $ratingA) / $this->scale));
    }

    public function calculateNewRatings(float $winnerRating, float $loserRating): array
    {
        $winnerExpected = $this->expectedScore($winnerRating, $loserRating);
        $loserExpected = $this->expectedScore($loserRating, $winnerRating);

        $winnerNew = $winnerRating + ($this->kFactor * (1.0 - $winnerExpected));
        $loserNew = $loserRating + ($this->kFactor * (0.0 - $loserExpected));

        return [
            'winner' => max(100.0, $winnerNew),
            'loser' => max(100.0, $loserNew),
        ];
    }
}
