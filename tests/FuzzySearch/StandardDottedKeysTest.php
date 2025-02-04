<?php

declare(strict_types=1);

use Fuse\Fuse;

$list = [
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
            'firstName' => 'rmy',
            'lastName' => 'Brown',
        ],
    ],
];

test('we get matches', function () use ($list) {
    $fuse = new Fuse($list, [
        'keys' => ['title', ['author', 'firstName']],
        'includeMatches' => true,
        'includeScore' => true,
    ]);

    $result = $fuse->search('remy');

    expect($result)->toHaveCount(2);
});

test('we get a result with no matches', function () {
    $fuse = new Fuse(
        [
            [
                'title' => 'HTML5',
                'author' => [
                    'first.name' => 'Remy',
                    'last.name' => 'Sharp',
                ],
            ],
            [
                'title' => 'Angels & Demons',
                'author' => [
                    'first.name' => 'rmy',
                    'last.name' => 'Brown',
                ],
            ],
        ],
        [
            'keys' => ['title', ['author', 'first.name']],
            'includeMatches' => true,
            'includeScore' => true,
        ],
    );

    $result = $fuse->search('remy');

    expect($result)->toHaveCount(2);
});

test('keys with weights', function () use ($list) {
    $fuse = new Fuse($list, [
        'keys' => [
            [
                'name' => 'title',
            ],
            [
                'name' => ['author', 'firstName'],
            ],
        ],
        'includeMatches' => true,
        'includeScore' => true,
    ]);

    $result = $fuse->search('remy');

    expect($result)->toHaveCount(2);
});
