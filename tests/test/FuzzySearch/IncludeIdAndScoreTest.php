<?php

declare(strict_types=1);

use Fuse\Fuse;

it('returns a result with the correct ISBN and score when searching for "Stve"', function () {
    $fuse = new Fuse(
        [
            [
                'ISBN' => '0765348276',
                'title' => 'Old Man\'s War',
                'author' => 'John Scalzi',
            ],
            [
                'ISBN' => '0312696957',
                'title' => 'The Lock Artist',
                'author' => 'Steve Hamilton',
            ],
        ],
        [
            'keys' => ['title', 'author'],
            'includeScore' => true,
        ],
    );

    $result = $fuse->search('Stve');

    expect($result)->toHaveCount(1);
    expect($result[0]['item']['ISBN'])->toBe('0312696957');
    expect($result[0]['score'])->not()->toBe(0);
});
