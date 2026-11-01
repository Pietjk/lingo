<?php

use App\Game\GuessEvaluator;
use App\Game\LetterStatus;

function evaluate(string $guess, string $solution): string
{
    return implode('', array_map(
        fn (LetterStatus $status) => match ($status) {
            LetterStatus::Correct => 'C',
            LetterStatus::Present => 'P',
            LetterStatus::Absent => 'A',
        },
        (new GuessEvaluator)->evaluate($guess, $solution),
    ));
}

it('marks every letter correct for the exact word', function () {
    expect(evaluate('appel', 'appel'))->toBe('CCCCC');
});

it('marks every letter absent when nothing matches', function () {
    expect(evaluate('fruit', 'balen'))->toBe('AAAAA');
});

it('marks letters in the wrong position as present', function () {
    expect(evaluate('leeft', 'eleft'))->toBe('PPCCC');
});

it('handles duplicate letters in the guess against a single letter in the solution', function () {
    expect(evaluate('allee', 'appel'))->toBe('CPACA');
});

it('handles lelie against ellen', function () {
    expect(evaluate('lelie', 'ellen'))->toBe('PPCAP');
});

it('does not mark a duplicate present when the solution letter is already correct', function () {
    expect(evaluate('eerst', 'weken'))->toBe('PCAAA');
});

it('gives correct matches priority over earlier present matches', function () {
    expect(evaluate('geeft', 'lente'))->toBe('ACPAP');
});

it('handles duplicate letters in the solution', function () {
    expect(evaluate('lekke', 'ellen'))->toBe('PPAAP');
});

it('is case insensitive', function () {
    expect(evaluate('LELIE', 'ellen'))->toBe('PPCAP');
});

it('rejects words that are not five letters', function () {
    (new GuessEvaluator)->evaluate('appels', 'appel');
})->throws(InvalidArgumentException::class);
