<?php

declare(strict_types=1);

use Fuse\Fuse;

beforeEach(function () {
    $this->fuse = new Fuse(
        [
            [
                'title' => 'Right Ho Jeeves',
                'author' => [
                    'firstName' => 'P.D',
                    'lastName' => 'Woodhouse',
                ],
            ],
            [
                'title' => 'The Code of the Wooster',
                'author' => [
                    'firstName' => 'P.D',
                    'lastName' => 'Woodhouse',
                ],
            ],
            [
                'title' => 'Thank You Jeeves',
                'author' => [
                    'firstName' => 'P.D',
                    'lastName' => 'Woodhouse',
                ],
            ],
        ],
        [
            'keys' => ['title', 'author.firstName', 'author.lastName'],
        ],
    );
});

test('when searching for the term Wood', function () {
    $result = $this->fuse->search('wood');

    // We get the properly ordered results
    expect($result[0]['item']['title'])->toBe('The Code of the Wooster');
    expect($result[1]['item']['title'])->toBe('Right Ho Jeeves');
    expect($result[2]['item']['title'])->toBe('Thank You Jeeves');
});
