<?php

declare(strict_types=1);

use Fuse\Fuse;

beforeEach(function () {
    $options = [
        'useExtendedSearch' => true,
        'includeScore' => true,
        'keys' => ['title', ['author', 'first.name'], ['author', 'last.name'], 'author.age'],
    ];

    $list = [
        [
            'title' => 'Old Man\'s War',
            'author' => [
                'first.name' => 'John',
                'last.name' => 'Scalzi',
                'age' => '61',
            ],
        ],
    ];

    $this->fuse = new Fuse($list, $options);
});

it('performs deep nested and/or search', function () {
    $result = $this->fuse->search([
        '$and' => [
            [
                '$path' => ['author', 'first.name'],
                '$val' => 'jon',
            ],
            [
                '$path' => ['author', 'last.name'],
                '$val' => 'scazi',
            ],
        ],
    ]);

    expect($result)->toHaveCount(1);
});
