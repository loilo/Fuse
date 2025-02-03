<?php

declare(strict_types=1);

use Fuse\Fuse;
use Fuse\Exception\InvalidKeyWeightValueException;
use Fuse\Exception\MissingKeyPropertyException;

beforeEach(function () {
    $this->createFuse = function (array $options = []): Fuse {
        return new Fuse(
            [
                [
                    'title' => 'Old Man\'s War fiction',
                    'author' => 'John X',
                    'tags' => ['war'],
                ],
                [
                    'title' => 'Right Ho Jeeves',
                    'author' => 'P.D. Mans',
                    'tags' => ['fiction', 'war'],
                ],
                [
                    'title' => 'The life of Jane',
                    'author' => 'John Smith',
                    'tags' => ['john', 'smith'],
                ],
                [
                    'title' => 'John Smith',
                    'author' => 'Steve Pearson',
                    'tags' => ['steve', 'pearson'],
                ],
            ],
            $options,
        );
    };
});

test('invalid key entries', function () {
    expect(
        fn() => ($this->createFuse)([
            'keys' => [
                [
                    'name' => 'title',
                    'weight' => -10,
                ],
                [
                    'name' => 'author',
                    'weight' => 0.7,
                ],
            ],
        ]),
    )->toThrow(InvalidKeyWeightValueException::class);
});

test('missing key properties', function () {
    expect(
        fn() => ($this->createFuse)([
            'keys' => [
                [
                    'weight' => 10,
                ],
                [
                    'name' => 'author',
                    'weight' => 0.7,
                ],
            ],
        ]),
    )->toThrow(MissingKeyPropertyException::class);
});

test('when searching for the term John Smith with author weighted higher', function () {
    $fuse = ($this->createFuse)([
        'keys' => [
            [
                'name' => 'title',
                'weight' => 0.3,
            ],
            [
                'name' => 'author',
                'weight' => 0.7,
            ],
        ],
    ]);

    $result = $fuse->search('John Smith');

    // We get the the exactly matching object
    expect($result[0])->toMatchArray([
        'item' => [
            'title' => 'The life of Jane',
            'author' => 'John Smith',
            'tags' => ['john', 'smith'],
        ],
        'refIndex' => 2,
    ]);
});

test(
    'when searching for the term John Smith with author weighted higher with mixed key types',
    function () {
        $fuse = ($this->createFuse)([
            'keys' => [
                'title',
                [
                    'name' => 'author',
                    'weight' => 2,
                ],
            ],
        ]);

        $result = $fuse->search('John Smith');

        // We get the the exactly matching object
        expect($result[0])->toMatchArray([
            'item' => [
                'title' => 'The life of Jane',
                'author' => 'John Smith',
                'tags' => ['john', 'smith'],
            ],
            'refIndex' => 2,
        ]);
    },
);

test(
    'when searching for the term John Smith with author weighted higher with mixed key types 2',
    function () {
        // Throws when key does not have a name property
        expect(
            fn() => ($this->createFuse)([
                'keys' => [
                    'title',
                    [
                        'weight' => 2,
                    ],
                ],
            ]),
        )->toThrow(MissingKeyPropertyException::class);
    },
);

test('when searching for the term John Smith with title weighted higher', function () {
    $fuse = ($this->createFuse)([
        'keys' => [
            [
                'name' => 'title',
                'weight' => 0.7,
            ],
            [
                'name' => 'author',
                'weight' => 0.3,
            ],
        ],
    ]);

    $result = $fuse->search('John Smith');

    // We get the the exactly matching object
    expect($result[0])->toMatchArray([
        'item' => [
            'title' => 'John Smith',
            'author' => 'Steve Pearson',
            'tags' => ['steve', 'pearson'],
        ],
        'refIndex' => 3,
    ]);
});

test('when searching for the term Man where the author is weighted higher than title', function () {
    $fuse = ($this->createFuse)([
        'keys' => [
            [
                'name' => 'title',
                'weight' => 0.3,
            ],
            [
                'name' => 'author',
                'weight' => 0.7,
            ],
        ],
    ]);

    $result = $fuse->search('Man');

    // We get the the exactly matching object
    expect($result[0])->toMatchArray([
        'item' => [
            'title' => 'Right Ho Jeeves',
            'author' => 'P.D. Mans',
            'tags' => ['fiction', 'war'],
        ],
        'refIndex' => 1,
    ]);
});

test('when searching for the term Man where the title is weighted higher than author', function () {
    $fuse = ($this->createFuse)([
        'keys' => [
            [
                'name' => 'title',
                'weight' => 0.7,
            ],
            [
                'name' => 'author',
                'weight' => 0.3,
            ],
        ],
    ]);

    $result = $fuse->search('Man');

    // We get the the exactly matching object
    expect($result[0])->toMatchArray([
        'item' => [
            'title' => 'Old Man\'s War fiction',
            'author' => 'John X',
            'tags' => ['war'],
        ],
        'refIndex' => 0,
    ]);
});

test(
    'when searching for the term War where tags are weighted higher than all other keys',
    function () {
        $fuse = ($this->createFuse)([
            'keys' => [
                [
                    'name' => 'title',
                    'weight' => 0.4,
                ],
                [
                    'name' => 'author',
                    'weight' => 0.1,
                ],
                [
                    'name' => 'tags',
                    'weight' => 0.5,
                ],
            ],
        ]);

        $result = $fuse->search('War');

        // We get the the exactly matching object
        expect($result[0])->toMatchArray([
            'item' => [
                'title' => 'Old Man\'s War fiction',
                'author' => 'John X',
                'tags' => ['war'],
            ],
            'refIndex' => 0,
        ]);
    },
);
