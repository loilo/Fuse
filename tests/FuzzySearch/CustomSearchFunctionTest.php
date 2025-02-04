<?php

declare(strict_types=1);

use Fuse\Fuse;

beforeEach(function () {
    $this->fuse = new Fuse(
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
        ],
        [
            'keys' => ['title', 'author.firstName'],
            'getFn' => function ($obj) {
                if (!$obj) {
                    return null;
                }
                $obj = $obj['author']['lastName'];
                return $obj;
            },
        ],
    );
});

it('returns at least one result when searching for "Hmlt"', function () {
    $result = $this->fuse->search('Hmlt');

    expect($result)->toHaveCount(1);
    expect($result[0]['item'])->toMatchArray([
        'title' => 'The Lock Artist',
        'author' => [
            'firstName' => 'Steve',
            'lastName' => 'Hamilton',
        ],
    ]);
});

it('returns no results when searching for "Stve"', function () {
    $result = $this->fuse->search('Stve');

    expect($result)->toHaveCount(0);
});
