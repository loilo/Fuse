<?php

declare(strict_types=1);

namespace Fuse\Test;

use Fuse\Fuse;

it('checks order of entries when location and field length norm are ignored', function () {
    $list = [
        'beforeEach',
        'async beforeEach test',
        'assert.async in beforeEach',
        'Module with Promise-aware beforeEach',
        'Promise-aware return values without beforeEach/afterEach',
        'Module with Promise-aware afterEach',
        'before',
        'before (skip)',
    ];

    $fuse = new Fuse($list, [
        'includeScore' => true,
        'ignoreLocation' => true,
        'ignoreFieldNorm' => true,
    ]);

    $result = $fuse->search('promiseawarebeforeEach');

    expect($result)->toEqual([
        [
            'item' => 'Module with Promise-aware beforeEach',
            'refIndex' => 3,
            'score' => 0.09090909090909091,
        ],
        [
            'item' => 'Module with Promise-aware afterEach',
            'refIndex' => 5,
            'score' => 0.2727272727272727,
        ],
        [
            'item' => 'Promise-aware return values without beforeEach/afterEach',
            'refIndex' => 4,
            'score' => 0.4090909090909091,
        ],
        [
            'item' => 'async beforeEach test',
            'refIndex' => 1,
            'score' => 0.5,
        ],
        [
            'item' => 'assert.async in beforeEach',
            'refIndex' => 2,
            'score' => 0.5,
        ],
        [
            'item' => 'beforeEach',
            'refIndex' => 0,
            'score' => 0.5454545454545454,
        ],
    ]);
});

it('checks order of entries when location and field length norm are not ignored', function () {
    $list = [
        'beforeEach',
        'async beforeEach test',
        'assert.async in beforeEach',
        'Module with Promise-aware beforeEach',
        'Promise-aware return values without beforeEach/afterEach',
        'Module with Promise-aware afterEach',
        'before',
        'before (skip)',
    ];

    $fuse = new Fuse($list, [
        'includeScore' => true,
    ]);

    $result = $fuse->search('beforeEach');

    expect($result)->toEqual([
        [
            'item' => 'beforeEach',
            'refIndex' => 0,
            'score' => 0,
        ],
        [
            'item' => 'async beforeEach test',
            'refIndex' => 1,
            'score' => 0.1972392177586917,
        ],
        [
            'item' => 'before',
            'refIndex' => 6,
            'score' => 0.4,
        ],
        [
            'item' => 'assert.async in beforeEach',
            'refIndex' => 2,
            'score' => 0.4493775633055149,
        ],
        [
            'item' => 'before (skip)',
            'refIndex' => 7,
            'score' => 0.5231863610884103,
        ],
        [
            'item' => 'Module with Promise-aware beforeEach',
            'refIndex' => 3,
            'score' => 0.5916079783099616,
        ],
        [
            'item' => 'Promise-aware return values without beforeEach/afterEach',
            'refIndex' => 4,
            'score' => 0.699819425905295,
        ],
    ]);
});
