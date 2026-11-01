<?php

namespace App\Game;

use RuntimeException;

class WordList
{
    /** @var list<string>|null */
    private ?array $solutions = null;

    /** @var array<string, true>|null */
    private ?array $validGuesses = null;

    public function __construct(
        private readonly string $solutionsPath = __DIR__.'/../../resources/words/solutions.json',
        private readonly string $guessesPath = __DIR__.'/../../resources/words/guesses.json',
    ) {}

    /**
     * @return list<string>
     */
    public function solutions(): array
    {
        return $this->solutions ??= $this->load($this->solutionsPath);
    }

    public function randomSolution(): string
    {
        $solutions = $this->solutions();

        if ($solutions === []) {
            throw new RuntimeException('The solutions list is empty.');
        }

        return $solutions[array_rand($solutions)];
    }

    public function isSolution(string $word): bool
    {
        return in_array($this->normalise($word), $this->solutions(), true);
    }

    public function isValidGuess(string $word): bool
    {
        if ($this->validGuesses === null) {
            $this->validGuesses = array_fill_keys(
                [...$this->solutions(), ...$this->load($this->guessesPath)],
                true,
            );
        }

        return isset($this->validGuesses[$this->normalise($word)]);
    }

    /**
     * @return list<string>
     */
    private function load(string $path): array
    {
        $contents = is_file($path) ? file_get_contents($path) : false;

        if ($contents === false) {
            throw new RuntimeException("Word list not readable: {$path}");
        }

        $decoded = json_decode($contents, true);

        if (! is_array($decoded)) {
            throw new RuntimeException("Word list is not valid JSON: {$path}");
        }

        $words = [];
        foreach ($decoded as $entry) {
            if (! is_string($entry)) {
                continue;
            }

            $word = $this->normalise($entry);
            if (preg_match('/^[a-z]{'.GuessEvaluator::WORD_LENGTH.'}$/', $word) === 1) {
                $words[$word] = $word;
            }
        }

        return array_values($words);
    }

    private function normalise(string $word): string
    {
        return mb_strtolower(trim($word));
    }
}
