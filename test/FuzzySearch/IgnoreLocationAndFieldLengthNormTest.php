<?php

declare(strict_types=1);

namespace Fuse\Test;

use PHPUnit\Framework\TestCase;
use Fuse\Fuse;

class IgnoreLocationAndFieldLengthNormTest extends TestCase
{
    private array $list = [
        'beforeEach',
        'async beforeEach test',
        'assert.async in beforeEach',
        'Module with Promise-aware beforeEach',
        'Promise-aware return values without beforeEach/afterEach',
        'Module with Promise-aware afterEach',
        'before',
        'before (skip)',
    ];

    public function testCheckOrderOfEntriesWhenLocationAndFieldLengthNormAreIgnored(): void
    {
        $fuse = new Fuse($this->list, [
            'includeScore' => true,
            'ignoreLocation' => true,
            'ignoreFieldNorm' => true,
        ]);

        $result = $fuse->search('promiseawarebeforeEach');

        $this->assertEquals(
            [
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
            ],
            $result,
        );
    }

    public function testCheckOrderOfEntriesWhenLocationAndFieldLengthNormAreNotIgnored(): void
    {
        $fuse = new Fuse($this->list, [
            'includeScore' => true,
        ]);

        $result = $fuse->search('beforeEach');

        $this->assertEquals(
            [
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
            ],
            $result,
        );
    }
}
