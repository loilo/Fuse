<?php

declare(strict_types=1);

namespace Fuse\Test;

use Fuse\Fuse;

it('shows the entry with the shorter field length first', function () {
    $list = [
        [
            'ISBN' => '0312696957',
            'title' => 'The Lock war Artist nonficon',
            'author' => 'Steve Hamilton',
            'tags' => ['fiction war hello no way'],
        ],
        [
            'ISBN' => '0765348276',
            'title' => 'Old Man\'s War',
            'author' => 'John Scalzi',
            'tags' => ['fiction no'],
        ],
    ];

    $fuse = new Fuse($list, [
        'keys' => ['title'],
    ]);

    $result = $fuse->search('war');

    expect($result)->toHaveCount(2);
    expect($result[0]['item']['ISBN'])->toBe('0765348276');
    expect($result[1]['item']['ISBN'])->toBe('0312696957');
});

it('gives high precedence to weighted entries', function () {
    $list = [
        [
            'ISBN' => '0312696957',
            'title' => 'The Lock war Artist nonficon',
            'author' => 'Steve Hamilton',
            'tags' => ['fiction war hello no way'],
        ],
        [
            'ISBN' => '0765348276',
            'title' => 'Old Man\'s War',
            'author' => 'John Scalzi',
            'tags' => ['fiction no'],
        ],
    ];

    $fuse = new Fuse($list, [
        'keys' => [
            [
                'name' => 'tags',
                'weight' => 0.8,
            ],
            [
                'name' => 'title',
                'weight' => 0.2,
            ],
        ],
    ]);

    $result = $fuse->search('war');

    expect($result)->toHaveCount(2);
    expect($result[0]['item']['ISBN'])->toBe('0312696957');
    expect($result[1]['item']['ISBN'])->toBe('0765348276');
});
