<?php

declare(strict_types=1);

use function Fuse\Core\parse;

beforeEach(function () {
    test()->fuseOptions = [
        'useExtendedSearch' => true,
        'includeMatches' => true,
        'includeScore' => true,
        'keys' => ['title', 'author.firstName', 'author.lastName'],
    ];
});

it('parses tree structure correctly', function () {
    $query = [
        '$and' => [
            [
                'title' => 'old war',
            ],
            [
                '$or' => [
                    [
                        'title' => '!arts',
                    ],
                    [
                        'title' => '^lock',
                    ],
                ],
            ],
        ],
    ];

    $root = parse($query, test()->fuseOptions, ['auto' => false]);

    expect($root)->toEqual([
        'children' => [
            [
                'keyId' => 'title',
                'pattern' => 'old war',
            ],
            [
                'children' => [
                    [
                        'keyId' => 'title',
                        'pattern' => '!arts',
                    ],
                    [
                        'keyId' => 'title',
                        'pattern' => '^lock',
                    ],
                ],
                'operator' => '$or',
            ],
        ],
        'operator' => '$and',
    ]);
});

it('handles implicit operations correctly', function () {
    $query = [
        '$and' => [
            [
                'title' => 'old war',
            ],
            [
                '$or' => [
                    [
                        'title' => '!arts',
                        'tags' => 'kiro',
                    ],
                    [
                        'title' => '^lock',
                    ],
                ],
            ],
        ],
    ];

    $root = parse($query, test()->fuseOptions, ['auto' => false]);

    expect($root)->toEqual([
        'children' => [
            [
                'keyId' => 'title',
                'pattern' => 'old war',
            ],
            [
                'children' => [
                    [
                        'children' => [
                            [
                                'keyId' => 'title',
                                'pattern' => '!arts',
                            ],
                            [
                                'keyId' => 'tags',
                                'pattern' => 'kiro',
                            ],
                        ],
                        'operator' => '$and',
                    ],
                    [
                        'keyId' => 'title',
                        'pattern' => '^lock',
                    ],
                ],
                'operator' => '$or',
            ],
        ],
        'operator' => '$and',
    ]);
});
