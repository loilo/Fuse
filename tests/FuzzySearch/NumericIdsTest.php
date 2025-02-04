<?php

declare(strict_types=1);

use Fuse\Fuse;

beforeEach(function () {
    $this->fuse = new Fuse(
        [
            [
                'ISBN' => 1111,
                'title' => 'Old Man\'s War',
                'author' => 'John Scalzi',
            ],
            [
                'ISBN' => 2222,
                'title' => 'The Lock Artist',
                'author' => 'Steve Hamilton',
            ],
        ],
        [
            'keys' => ['title', 'author'],
            'id' => 'ISBN',
            'includeScore' => true,
        ],
    );
});

test('when searching for the term Stve', function () {
    $result = $this->fuse->search('Stve');

    // we get a list containing exactly 1 item
    expect($result)->toHaveCount(1);

    // whose value is the ISBN of the book
    expect($result[0]['item']['ISBN'])->toBe(2222);

    // and has a score that is not zero
    expect($result[0]['score'])->not->toBe(0);
});
