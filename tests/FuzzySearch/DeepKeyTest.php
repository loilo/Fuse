<?php

declare(strict_types=1);

namespace Fuse\Test;

use Fuse\Fuse;

it('searches for the term "Stve"', function () {
    $fuse = new Fuse(
        [
            [
                'title' => 'Old Man\'s War',
                'author' => [
                    'firstName' => 'John',
                    'lastName' => 'Scalzi',
                ],
            ],
            [
                'title' => 'The Lock Artist',
                'author' => [
                    'firstName' => 'Steve',
                    'lastName' => 'Hamilton',
                ],
            ],
            [
                'title' => 'HTML5',
            ],
            [
                'title' => 'A History of England',
                'author' => [
                    'firstName' => 1066,
                    'lastName' => 'Hastings',
                ],
            ],
        ],
        [
            'keys' => ['title', 'author.firstName'],
        ],
    );

    $result = $fuse->search('Stve');

    expect(sizeof($result))->toBeGreaterThanOrEqual(1);
    expect($result[0]['item'])->toMatchArray([
        'title' => 'The Lock Artist',
        'author' => [
            'firstName' => 'Steve',
            'lastName' => 'Hamilton',
        ],
    ]);
});

it('searches for the term "106"', function () {
    $fuse = new Fuse(
        [
            [
                'title' => 'Old Man\'s War',
                'author' => [
                    'firstName' => 'John',
                    'lastName' => 'Scalzi',
                ],
            ],
            [
                'title' => 'The Lock Artist',
                'author' => [
                    'firstName' => 'Steve',
                    'lastName' => 'Hamilton',
                ],
            ],
            [
                'title' => 'HTML5',
            ],
            [
                'title' => 'A History of England',
                'author' => [
                    'firstName' => 1066,
                    'lastName' => 'Hastings',
                ],
            ],
        ],
        [
            'keys' => ['title', 'author.firstName'],
        ],
    );

    $result = $fuse->search('106');

    expect($result)->toHaveCount(1);
    expect($result[0]['item'])->toMatchArray([
        'title' => 'A History of England',
        'author' => [
            'firstName' => 1066,
            'lastName' => 'Hastings',
        ],
    ]);
});
