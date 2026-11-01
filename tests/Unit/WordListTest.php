<?php

use App\Game\WordList;

beforeEach(function () {
    $this->dir = sys_get_temp_dir().'/lingo-'.uniqid();
    mkdir($this->dir);
    file_put_contents($this->dir.'/solutions.json', json_encode(['appel', 'Stoel', '', 'brood', 'toolong', 'ab1de', ' appel ', 42]));
    file_put_contents($this->dir.'/guesses.json', json_encode(['tafel', 'lepel']));
    $this->words = new WordList($this->dir.'/solutions.json', $this->dir.'/guesses.json');
});

afterEach(function () {
    array_map('unlink', glob($this->dir.'/*'));
    rmdir($this->dir);
});

it('loads normalised, unique five-letter solutions only', function () {
    expect($this->words->solutions())->toBe(['appel', 'stoel', 'brood']);
});

it('accepts solutions and extra guesses as valid guesses', function () {
    expect($this->words->isValidGuess('appel'))->toBeTrue()
        ->and($this->words->isValidGuess('TAFEL'))->toBeTrue()
        ->and($this->words->isValidGuess('zzzzz'))->toBeFalse();
});

it('only treats solution words as solutions', function () {
    expect($this->words->isSolution('stoel'))->toBeTrue()
        ->and($this->words->isSolution('tafel'))->toBeFalse();
});

it('picks a random solution from the solutions list', function () {
    expect($this->words->randomSolution())->toBeIn(['appel', 'stoel', 'brood']);
});

it('throws when a file is missing', function () {
    (new WordList('/nope.json', '/nope2.json'))->solutions();
})->throws(RuntimeException::class);

it('throws when a file is not valid json', function () {
    file_put_contents($this->dir.'/solutions.json', 'appel');

    $this->words->solutions();
})->throws(RuntimeException::class);

it('ships a bundled word list whose solutions are all valid guesses', function () {
    $words = new WordList;

    expect($words->solutions())->not->toBeEmpty();
    foreach ($words->solutions() as $word) {
        expect($word)->toMatch('/^[a-z]{5}$/')
            ->and($words->isValidGuess($word))->toBeTrue();
    }
});
