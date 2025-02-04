<?php

declare(strict_types=1);

namespace Fuse\Test;

use Fuse\Fuse;

it('searches for the term "test" and returns correct match indices', function () {
    $fuse = new Fuse(
        ['t te tes test tes te t'],
        [
            'includeMatches' => true,
            'minMatchCharLength' => 2,
        ],
    );

    $result = $fuse->search('test');

    expect($result[0]['matches'][0]['indices'])->toHaveCount(3);
    expect($result[0]['matches'][0]['indices'][0][0])->toBe(2);
    expect($result[0]['matches'][0]['indices'][0][1])->toBe(3);
});

it('returns no results when searching for a string shorter than minMatchCharLength', function () {
    $fuse = new Fuse(
        ['t te tes test tes te t'],
        [
            'includeMatches' => true,
            'minMatchCharLength' => 2,
        ],
    );

    $result = $fuse->search('t');

    expect($result)->toBeEmpty();
});

it('performs the main functionality correctly', function () {
    $fuse = new Fuse(
        [
            [
                'title' => 'HTML5',
                'author' => [
                    'firstName' => 'Remy',
                    'lastName' => 'Sharp',
                ],
            ],
            [
                'title' => 'Angels & Demons',
                'author' => [
                    'firstName' => 'Dan',
                    'lastName' => 'Brown',
                ],
            ],
        ],
        [
            'keys' => ['title', 'author.firstName'],
            'includeMatches' => true,
            'includeScore' => true,
            'minMatchCharLength' => 3,
        ],
    );

    $result = $fuse->search('remy');

    expect($result)->toHaveCount(1);
    expect($result[0]['matches'])->toHaveCount(1);
});
