<?php

namespace App\Game;

use InvalidArgumentException;

class GuessEvaluator
{
    public const WORD_LENGTH = 5;

    /**
     * @return list<LetterStatus>
     */
    public function evaluate(string $guess, string $solution): array
    {
        $guess = mb_strtolower($guess);
        $solution = mb_strtolower($solution);

        if (mb_strlen($guess) !== self::WORD_LENGTH || mb_strlen($solution) !== self::WORD_LENGTH) {
            throw new InvalidArgumentException('Guess and solution must be '.self::WORD_LENGTH.' letters.');
        }

        $guessLetters = mb_str_split($guess);
        $solutionLetters = mb_str_split($solution);
        $result = array_fill(0, self::WORD_LENGTH, LetterStatus::Absent);

        /** @var array<string, int> $remaining */
        $remaining = [];

        foreach ($solutionLetters as $i => $letter) {
            if ($guessLetters[$i] === $letter) {
                $result[$i] = LetterStatus::Correct;
            } else {
                $remaining[$letter] = ($remaining[$letter] ?? 0) + 1;
            }
        }

        foreach ($guessLetters as $i => $letter) {
            if ($result[$i] === LetterStatus::Correct) {
                continue;
            }

            if (($remaining[$letter] ?? 0) > 0) {
                $result[$i] = LetterStatus::Present;
                $remaining[$letter]--;
            }
        }

        return $result;
    }
}
