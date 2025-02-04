<?php

declare(strict_types=1);

use Fuse\Fuse;

it('finds no matches when string is larger than 32 characters', function () {
    $list = [
        ['text' => 'pizza'],
        ['text' => 'feast'],
        ['text' => 'where in the world is carmen san diego'],
    ];

    $fuse = new Fuse($list, [
        'shouldSort' => true,
        'includeScore' => true,
        'threshold' => 0.6,
        'keys' => ['text'],
    ]);

    $result = $fuse->search('where exctly is carmen in the world san diego');

    expect($result)->toHaveCount(1);
    expect($result[0]['item']['text'])->toBe($list[2]['text']);
});

it('matches with very long patterns', function () {
    $fuse = new Fuse(
        [
            ['text' => 'pizza'],
            ['text' => 'feast'],
            ['text' => 'where in the world is carmen san diego'],
        ],
        [
            'shouldSort' => true,
            'includeScore' => true,
            'threshold' => 0.6,
            'keys' => ['text'],
        ],
    );

    $patterns = [];
    for ($i = 0; $i < 66; ++$i) {
        $patterns[] = str_repeat('w', $i);
    }

    foreach ([32, 33, 34, 64, 65] as $index) {
        expect($fuse->search($patterns[$index]))->toBeEmpty();
    }
});

it('handles search with hyphens', function () {
    $searchText = 'leverage-streams-to';

    $fuse = new Fuse(
        [
            [
                'name' => 'Streaming Service',
                'description' => 'Leverage-streams-to-ingest, analyze, monitor.',
                'tag' => 'Free',
            ],
        ],
        [
            'distance' => 1000,
            'includeScore' => true,
            'includeMatches' => true,
            'keys' => ['name', 'tag', 'description'],
            'minMatchCharLength' => floor(mb_strlen($searchText) * 0.6),
            'shouldSort' => false,
        ],
    );

    $results = $fuse->search($searchText);

    expect($results[0]['matches'])->toEqual([
        [
            'indices' => [[0, 18]],
            'key' => 'description',
            'value' => 'Leverage-streams-to-ingest, analyze, monitor.',
        ],
    ]);
});

it('handles search with spaces', function () {
    $searchText = 'leverage streams to';

    $fuse = new Fuse(
        [
            [
                'name' => 'Streaming Service',
                'description' => 'Leverage streams to ingest, analyze, monitor.',
                'tag' => 'Free',
            ],
        ],
        [
            'distance' => 1000,
            'includeScore' => true,
            'includeMatches' => true,
            'keys' => ['name', 'tag', 'description'],
            'minMatchCharLength' => floor(mb_strlen($searchText) * 0.6),
            'shouldSort' => false,
        ],
    );

    $results = $fuse->search($searchText);

    expect($results[0]['matches'])->toEqual([
        [
            'indices' => [[0, 18]],
            'key' => 'description',
            'value' => 'Leverage streams to ingest, analyze, monitor.',
        ],
    ]);
});
