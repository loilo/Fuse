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

describe('Recurse into arrays with empty/undefined elements', function () {
    test('refIndex is correct when array has undefined gaps', function () {
        $list = [
            ['tags' => ['alpha', 'beta', null, 'delta']],
        ];

        $fuse = new Fuse($list, [
            'keys' => ['tags'],
            'threshold' => 0,
            'includeMatches' => true,
        ]);

        $result = $fuse->search('delta');

        expect($result)->toHaveCount(1);
        expect($result[0]['matches'][0])->toMatchArray([
            'value' => 'delta',
            'key' => 'tags',
            'refIndex' => 3,
        ]);
    });

    test('refIndex is correct when nested content array is empty', function () {
        $list = [[
            'blocks' => [
                ['content' => [['text' => 'first']]],
                ['content' => []],
                ['content' => [['text' => 'third']]],
            ],
        ]];

        $fuse = new Fuse($list, [
            'keys' => ['blocks.content.text'],
            'threshold' => 0,
            'includeMatches' => true,
        ]);

        $result = $fuse->search('third');

        expect($result)->toHaveCount(1);

        // refIndex is the innermost array index (position within content[])
        expect($result[0]['matches'][0])->toMatchArray([
            'value' => 'third',
            'key' => 'blocks.content.text',
            'refIndex' => 0,
        ]);
    });
});
