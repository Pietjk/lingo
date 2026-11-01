<?php

namespace App\Game;

class RoundScorer
{
    public const TOTAL_ROUNDS = 3;

    public const MAX_GUESSES = 5;

    public const ROUND_SECONDS = 300;

    public const FORFEIT_SECONDS = 30;

    public const SOLVE_POINTS = 10;

    public const REMAINING_ROW_BONUS = 2;

    public const FIRST_SOLVER_BONUS = 5;

    public static function points(int $attemptsUsed, bool $solvedFirst): int
    {
        $remainingRows = max(0, self::MAX_GUESSES - $attemptsUsed);

        return self::SOLVE_POINTS
            + $remainingRows * self::REMAINING_ROW_BONUS
            + ($solvedFirst ? self::FIRST_SOLVER_BONUS : 0);
    }
}
