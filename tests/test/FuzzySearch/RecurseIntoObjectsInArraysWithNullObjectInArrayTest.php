<?php

declare(strict_types=1);

use Fuse\Fuse;

beforeEach(function () {
    $this->fuse = new Fuse(
        [
            [
                'ISBN' => '0765348276',
                'title' => 'Old Man\'s War',
                'author' => [
                    'name' => 'John Scalzi',
                    'tags' => [
                        [
                            'value' => 'American',
                        ],
                        null,
                    ],
                ],
            ],
            [
                'ISBN' => '0312696957',
                'title' => 'The Lock Artist',
                'author' => [
                    'name' => 'Steve Hamilton',
                    'tags' => [
                        [
                            'value' => 'American',
                        ],
                    ],
                ],
            ],
            [
                'ISBN' => '0321784421',
                'title' => 'HTML5',
                'author' => [
                    'name' => 'Remy Sharp',
                    'tags' => [
                        [
                            'value' => 'British',
                        ],
                        null,
                    ],
                ],
            ],
        ],
        [
            'keys' => ['author.tags.value'],
            'threshold' => 0,
        ],
    );
});

test('when searching for the author tag British', function () {
    $result = $this->fuse->search('British');

    // we get a list containing exactly 1 item
    expect($result)->toHaveCount(1);

    // whose value is the ISBN of the book
    expect($result[0]['item']['ISBN'])->toBe('0321784421');
});
