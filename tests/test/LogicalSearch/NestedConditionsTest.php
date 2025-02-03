<?php

declare(strict_types=1);

use Fuse\Fuse;

beforeEach(function () {
    $options = [
        'includeScore' => true,
        'useExtendedSearch' => true,
        'keys' => ['title', 'author.firstName', 'author.lastName', 'author.age'],
    ];

    $list1 = [
        [
            'title' => 'Old Man\'s War',
            'author' => [
                'firstName' => 'John',
                'lastName' => 'Scalzi',
                'age' => '61',
            ],
        ],
    ];

    $list2 = array_merge($list1, [
        [
            'title' => 'Old Man\'s War',
            'author' => [
                'firstName' => 'John',
                'lastName' => 'Scalzi',
                'age' => '62',
            ],
        ],
    ]);

    test()->fuse1 = new Fuse($list1, $options);
    test()->fuse2 = new Fuse($list2, $options);
});

it('searches with nested and/or conditions', function () {
    $result = test()->fuse1->search([
        '$and' => [
            [
                'title' => 'old',
            ],
            [
                '$or' => [
                    [
                        'author.firstName' => 'j',
                    ],
                    [
                        'author.lastName' => 'Sa',
                    ],
                ],
            ],
            [
                '$or' => [
                    [
                        'author.age' => '\'62',
                    ],
                ],
            ],
        ],
    ]);

    expect($result)->toHaveCount(0);
});

it('searches with deep nested and/or conditions', function () {
    $result = test()->fuse1->search([
        '$and' => [
            [
                'title' => 'old',
            ],
            [
                '$or' => [
                    [
                        'author.firstName' => 'jon',
                    ],
                    [
                        'author.lastName' => 'Sazi',
                    ],
                ],
            ],
            [
                '$or' => [
                    [
                        'author.age' => '\'62',
                    ],
                    [
                        '$and' => [
                            [
                                'title' => 'old',
                            ],
                            [
                                'author.age' => '\'61',
                            ],
                        ],
                    ],
                ],
            ],
        ],
    ]);

    expect($result)->toHaveCount(1);
    expect($result[0])->toHaveKey('score');
    expect($result[0]['score'])->toBeGreaterThan(0);
});

it('searches with deep nested and/or conditions 2', function () {
    $result = test()->fuse2->search([
        '$and' => [
            [
                'title' => 'old',
            ],
            [
                '$and' => [
                    [
                        'author.firstName' => 'jon',
                    ],
                    [
                        'author.lastName' => 'Sazi',
                    ],
                ],
            ],
            [
                '$or' => [
                    [
                        'author.age' => '\'62',
                    ],
                    [
                        '$and' => [
                            [
                                'title' => 'old',
                            ],
                            [
                                'author.age' => '\'62',
                            ],
                        ],
                    ],
                ],
            ],
        ],
    ]);

    expect($result)->toHaveCount(1);
    expect($result[0])->toHaveKey('score');
    expect($result[0]['score'])->toBeGreaterThan(0);
});
