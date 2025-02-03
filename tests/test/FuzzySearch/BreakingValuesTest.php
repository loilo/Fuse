<?php

declare(strict_types=1);

use Fuse\Fuse;

it('processes booleans correctly', function () {
    $fuse = new Fuse(
        [
            [
                'first' => false,
            ],
        ],
        [
            'keys' => [
                [
                    'name' => 'first',
                ],
            ],
        ],
    );

    $result = $fuse->search('fa');

    expect($result)->toHaveCount(1);
});

it('ignores object values', function () {
    $fuse = new Fuse(
        [
            [
                'a' => 'hello',
            ],
            [
                'a' => [],
            ],
        ],
        [
            'keys' => ['a'],
        ],
    );

    $result = $fuse->search('hello');

    expect($result)->toHaveCount(1);
});
