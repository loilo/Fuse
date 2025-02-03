<?php

declare(strict_types=1);

namespace Fuse\Test;

use Fuse\Fuse;

beforeEach(function () {
    $books = require __DIR__ . '/../fixtures/books.php';
    $fuse = new Fuse($books, [
        'useExtendedSearch' => true,
        'includeMatches' => true,
        'includeScore' => true,
        'keys' => ['title', 'author.firstName', 'author.lastName'],
    ]);
    $this->books = $books;
    $this->fuse = $fuse;
    $this->idx = function (array $results): array {
        return array_map(fn(array $result): int => $result['refIndex'], $results);
    };
});

it('searches implicitly using AND', function () {
    $result = $this->fuse->search(['title' => 'old man']);

    expect($result)->toHaveCount(1);
    expect($result[0]['refIndex'])->toBe(0);
    expect($result[0]['matches'][0]['indices'])
        ->toBeArray()
        ->toContain([0, 2], [4, 6]);
});

it('searches AND with a single item', function () {
    $result = $this->fuse->search(['$and' => [['title' => 'old man']]]);

    expect($result)->toHaveCount(1);
    expect(($this->idx)($result))->toEqual([0]);
    expect($result[0]['matches'][0]['indices'])
        ->toBeArray()
        ->toContain([0, 2], [4, 6]);
});

it('searches AND with multiple entries', function () {
    $result = $this->fuse->search([
        '$and' => [['author.lastName' => 'Woodhose'], ['title' => 'the']],
    ]);

    expect($result)->toHaveCount(2);
    expect(($this->idx)($result))->toEqual([4, 5]);
});

it('searches AND with multiple entries and exact match', function () {
    $result = $this->fuse->search([
        '$and' => [['author.lastName' => 'Woodhose'], ['title' => '\'The']],
    ]);

    expect($result)->toHaveCount(1);
    expect(($this->idx)($result))->toEqual([4]);
});

it('searches OR with multiple entries', function () {
    $result = $this->fuse->search([
        '$or' => [['title' => 'angls'], ['title' => 'incmpetnce']],
    ]);

    expect($result)->toHaveCount(3);
    expect(($this->idx)($result))->toEqual([14, 7, 0]);
});

it('searches OR with nested entries', function () {
    $result = $this->fuse->search([
        '$or' => [
            ['title' => 'angls'],
            [
                '$and' => [['title' => '!dwarf'], ['title' => 'bakwrds']],
            ],
        ],
    ]);

    expect($result)->toHaveCount(2);
    expect(($this->idx)($result))->toEqual([7, 0]);
});

it('searches with logical OR with the same query across fields for wood', function () {
    $options = ['keys' => ['title', 'author.lastName']];
    $fuse = new Fuse($this->books, $options);

    $query = [
        '$or' => [['title' => 'wood'], ['author.lastName' => 'wood']],
    ];
    $result = $fuse->search($query);

    // we get the top three results scored based matches from all their fields
    expect(($this->idx)(array_slice($result, 0, 3)))->toEqual([4, 3, 5]);
});
