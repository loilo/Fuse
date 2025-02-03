<?php

declare(strict_types=1);

use Fuse\Fuse;

beforeEach(function () {
    $this->fuse = new Fuse(
        [
            [
                'ISBN' => '0765348276',
                'title' => 'Old Man\'s War',
                'author' => 'John Scalzi',
                'tags' => ['fiction'],
            ],
            [
                'ISBN' => '0312696957',
                'title' => 'The Lock Artist',
                'author' => 'Steve Hamilton',
                'tags' => ['fiction'],
            ],
            [
                'ISBN' => '0321784421',
                'title' => 'HTML5',
                'author' => 'Remy Sharp',
                'tags' => ['web development', 'nonfiction'],
            ],
        ],
        [
            'keys' => ['tags'],
            'threshold' => 0,
            'includeMatches' => true,
        ],
    );
});

test('when searching for the tag nonfiction', function () {
    $result = $this->fuse->search('nonfiction');

    // we get a list containing exactly 1 item
    expect($result)->toHaveCount(1);

    // whose value is the ISBN of the book
    expect($result[0]['item']['ISBN'])->toBe('0321784421');

    // with matched tag provided
    expect($result[0]['matches'][0])->toMatchArray([
        'indices' => [[0, 9]],
        'value' => 'nonfiction',
        'key' => 'tags',
        'refIndex' => 1,
    ]);
});
