<?php

declare(strict_types=1);

namespace Fuse\Test;

use PHPUnit\Framework\TestCase;
use Fuse\Fuse;

class ExtendedSearchTest extends TestCase
{
    private static Fuse $fuse;

    public static function setUpBeforeClass(): void
    {
        $list = [
            [
                'text' => 'hello word',
            ],
            [
                'text' => 'how are you',
            ],
            [
                'text' => 'indeed fine hello foo',
            ],
            [
                'text' => 'I am fine',
            ],
            [
                'text' => 'smithee',
            ],
            [
                'text' => 'smith',
            ],
        ];

        $options = [
            'useExtendedSearch' => true,
            'includeMatches' => true,
            'includeScore' => true,
            'threshold' => 0.5,
            'minMatchCharLength' => 4,
            'keys' => ['text'],
        ];

        static::$fuse = new Fuse($list, $options);
    }

    // Searching using extended search
    public function testSearchExactMatch(): void
    {
        $result = static::$fuse->search('=smith');
        $this->assertEquals(
            [
                [
                    'item' => [
                        'text' => 'smith',
                    ],
                    'matches' => [
                        [
                            'indices' => [[0, 4]],
                            'key' => 'text',
                            'value' => 'smith',
                        ],
                    ],
                    'refIndex' => 5,
                    'score' => 2.220446049250313e-16,
                ],
            ],
            $result,
        );
    }

    public function testSearchIncludeMatch(): void
    {
        $result = static::$fuse->search("'hello");
        $this->assertEquals(
            [
                [
                    'item' => [
                        'text' => 'hello word',
                    ],
                    'matches' => [
                        [
                            'indices' => [[0, 4]],
                            'key' => 'text',
                            'value' => 'hello word',
                        ],
                    ],
                    'refIndex' => 0,
                    'score' => 8.569061098350962e-12,
                ],
                [
                    'item' => [
                        'text' => 'indeed fine hello foo',
                    ],
                    'matches' => [
                        [
                            'indices' => [[12, 16]],
                            'key' => 'text',
                            'value' => 'indeed fine hello foo',
                        ],
                    ],
                    'refIndex' => 2,
                    'score' => 1.4901161193847656e-8,
                ],
            ],
            $result,
        );
    }

    public function testSearchPrefixExactMatch(): void
    {
        $result = static::$fuse->search('^hello');
        $this->assertEquals(
            [
                [
                    'item' => [
                        'text' => 'hello word',
                    ],
                    'matches' => [
                        [
                            'indices' => [[0, 4]],
                            'key' => 'text',
                            'value' => 'hello word',
                        ],
                    ],
                    'refIndex' => 0,
                    'score' => 8.569061098350962e-12,
                ],
            ],
            $result,
        );
    }

    public function testSearchSuffixExactMatch(): void
    {
        $result = static::$fuse->search('fine$');
        $this->assertEquals(
            [
                [
                    'item' => [
                        'text' => 'I am fine',
                    ],
                    'matches' => [
                        [
                            'indices' => [[5, 8]],
                            'key' => 'text',
                            'value' => 'I am fine',
                        ],
                    ],
                    'refIndex' => 3,
                    'score' => 9.287439764962262e-10,
                ],
            ],
            $result,
        );
    }

    public function testSearchInverseExactMatch(): void
    {
        $result = static::$fuse->search('!indeed');
        $this->assertEquals(
            [
                [
                    'item' => [
                        'text' => 'smithee',
                    ],
                    'matches' => [
                        [
                            'indices' => [[0, 6]],
                            'key' => 'text',
                            'value' => 'smithee',
                        ],
                    ],
                    'refIndex' => 4,
                    'score' => 2.220446049250313e-16,
                ],
                [
                    'item' => [
                        'text' => 'smith',
                    ],
                    'matches' => [
                        [
                            'indices' => [[0, 4]],
                            'key' => 'text',
                            'value' => 'smith',
                        ],
                    ],
                    'refIndex' => 5,
                    'score' => 2.220446049250313e-16,
                ],
                [
                    'item' => [
                        'text' => 'hello word',
                    ],
                    'matches' => [
                        [
                            'indices' => [[0, 9]],
                            'key' => 'text',
                            'value' => 'hello word',
                        ],
                    ],
                    'refIndex' => 0,
                    'score' => 8.569061098350962e-12,
                ],
                [
                    'item' => [
                        'text' => 'how are you',
                    ],
                    'matches' => [
                        [
                            'indices' => [[0, 10]],
                            'key' => 'text',
                            'value' => 'how are you',
                        ],
                    ],
                    'refIndex' => 1,
                    'score' => 9.287439764962262e-10,
                ],
                [
                    'item' => [
                        'text' => 'I am fine',
                    ],
                    'matches' => [
                        [
                            'indices' => [[0, 8]],
                            'key' => 'text',
                            'value' => 'I am fine',
                        ],
                    ],
                    'refIndex' => 3,
                    'score' => 9.287439764962262e-10,
                ],
            ],
            $result,
        );
    }

    public function testSearchInversePrefixExactMatch(): void
    {
        $result = static::$fuse->search('!^hello');
        $this->assertEquals(
            [
                [
                    'item' => [
                        'text' => 'smithee',
                    ],
                    'matches' => [
                        [
                            'indices' => [[0, 6]],
                            'key' => 'text',
                            'value' => 'smithee',
                        ],
                    ],
                    'refIndex' => 4,
                    'score' => 2.220446049250313e-16,
                ],
                [
                    'item' => [
                        'text' => 'smith',
                    ],
                    'matches' => [
                        [
                            'indices' => [[0, 4]],
                            'key' => 'text',
                            'value' => 'smith',
                        ],
                    ],
                    'refIndex' => 5,
                    'score' => 2.220446049250313e-16,
                ],
                [
                    'item' => [
                        'text' => 'how are you',
                    ],
                    'matches' => [
                        [
                            'indices' => [[0, 10]],
                            'key' => 'text',
                            'value' => 'how are you',
                        ],
                    ],
                    'refIndex' => 1,
                    'score' => 9.287439764962262e-10,
                ],
                [
                    'item' => [
                        'text' => 'I am fine',
                    ],
                    'matches' => [
                        [
                            'indices' => [[0, 8]],
                            'key' => 'text',
                            'value' => 'I am fine',
                        ],
                    ],
                    'refIndex' => 3,
                    'score' => 9.287439764962262e-10,
                ],
                [
                    'item' => [
                        'text' => 'indeed fine hello foo',
                    ],
                    'matches' => [
                        [
                            'indices' => [[0, 20]],
                            'key' => 'text',
                            'value' => 'indeed fine hello foo',
                        ],
                    ],
                    'refIndex' => 2,
                    'score' => 1.4901161193847656e-8,
                ],
            ],
            $result,
        );
    }

    public function testSearchInverseSuffixExactMatch(): void
    {
        $result = static::$fuse->search('!foo$');
        $this->assertEquals(
            [
                [
                    'item' => [
                        'text' => 'smithee',
                    ],
                    'matches' => [
                        [
                            'indices' => [[0, 6]],
                            'key' => 'text',
                            'value' => 'smithee',
                        ],
                    ],
                    'refIndex' => 4,
                    'score' => 2.220446049250313e-16,
                ],
                [
                    'item' => [
                        'text' => 'smith',
                    ],
                    'matches' => [
                        [
                            'indices' => [[0, 4]],
                            'key' => 'text',
                            'value' => 'smith',
                        ],
                    ],
                    'refIndex' => 5,
                    'score' => 2.220446049250313e-16,
                ],
                [
                    'item' => [
                        'text' => 'hello word',
                    ],
                    'matches' => [
                        [
                            'indices' => [[0, 9]],
                            'key' => 'text',
                            'value' => 'hello word',
                        ],
                    ],
                    'refIndex' => 0,
                    'score' => 8.569061098350962e-12,
                ],
                [
                    'item' => [
                        'text' => 'how are you',
                    ],
                    'matches' => [
                        [
                            'indices' => [[0, 10]],
                            'key' => 'text',
                            'value' => 'how are you',
                        ],
                    ],
                    'refIndex' => 1,
                    'score' => 9.287439764962262e-10,
                ],
                [
                    'item' => [
                        'text' => 'I am fine',
                    ],
                    'matches' => [
                        [
                            'indices' => [[0, 8]],
                            'key' => 'text',
                            'value' => 'I am fine',
                        ],
                    ],
                    'refIndex' => 3,
                    'score' => 9.287439764962262e-10,
                ],
            ],
            $result,
        );
    }

    public function testSearchAll(): void
    {
        $result = static::$fuse->search('!foo$ !^how');
        $this->assertEquals(
            [
                [
                    'item' => [
                        'text' => 'smithee',
                    ],
                    'matches' => [
                        [
                            'indices' => [[0, 6], [0, 6]],
                            'key' => 'text',
                            'value' => 'smithee',
                        ],
                    ],
                    'refIndex' => 4,
                    'score' => 2.220446049250313e-16,
                ],
                [
                    'item' => [
                        'text' => 'smith',
                    ],
                    'matches' => [
                        [
                            'indices' => [[0, 4], [0, 4]],
                            'key' => 'text',
                            'value' => 'smith',
                        ],
                    ],
                    'refIndex' => 5,
                    'score' => 2.220446049250313e-16,
                ],
                [
                    'item' => [
                        'text' => 'hello word',
                    ],
                    'matches' => [
                        [
                            'indices' => [[0, 9], [0, 9]],
                            'key' => 'text',
                            'value' => 'hello word',
                        ],
                    ],
                    'refIndex' => 0,
                    'score' => 8.569061098350962e-12,
                ],
                [
                    'item' => [
                        'text' => 'I am fine',
                    ],
                    'matches' => [
                        [
                            'indices' => [[0, 8], [0, 8]],
                            'key' => 'text',
                            'value' => 'I am fine',
                        ],
                    ],
                    'refIndex' => 3,
                    'score' => 9.287439764962262e-10,
                ],
            ],
            $result,
        );
    }

    public function testSearchSingleLiteralMatch(): void
    {
        $result = static::$fuse->search('\'"indeed fine"');
        $this->assertEquals(
            [
                [
                    'item' => [
                        'text' => 'indeed fine hello foo',
                    ],
                    'matches' => [
                        [
                            'indices' => [[0, 10]],
                            'key' => 'text',
                            'value' => 'indeed fine hello foo',
                        ],
                    ],
                    'refIndex' => 2,
                    'score' => 1.4901161193847656e-8,
                ],
            ],
            $result,
        );
    }

    public function testSearchLiteralMatchWithRegularMatch(): void
    {
        $result = static::$fuse->search('\'"indeed fine" foo$ | \'are');
        $this->assertEquals(
            [
                [
                    'item' => [
                        'text' => 'how are you',
                    ],
                    'matches' => [
                        [
                            'indices' => [[4, 6]],
                            'key' => 'text',
                            'value' => 'how are you',
                        ],
                    ],
                    'refIndex' => 1,
                    'score' => 9.287439764962262e-10,
                ],
                [
                    'item' => [
                        'text' => 'indeed fine hello foo',
                    ],
                    'matches' => [
                        [
                            'indices' => [[0, 10], [18, 20]],
                            'key' => 'text',
                            'value' => 'indeed fine hello foo',
                        ],
                    ],
                    'refIndex' => 2,
                    'score' => 1.4901161193847656e-8,
                ],
            ],
            $result,
        );
    }

    public function testSearchLiteralMatchWithFuzzyMatch(): void
    {
        $result = static::$fuse->search('\'"indeed fine" foo$ | helol');
        $this->assertEquals(
            [
                [
                    'item' => [
                        'text' => 'indeed fine hello foo',
                    ],
                    'matches' => [
                        [
                            'indices' => [[0, 10], [18, 20]],
                            'key' => 'text',
                            'value' => 'indeed fine hello foo',
                        ],
                    ],
                    'refIndex' => 2,
                    'score' => 1.4901161193847656e-8,
                ],
                [
                    'item' => [
                        'text' => 'hello word',
                    ],
                    'matches' => [
                        [
                            'indices' => [[0, 4]],
                            'key' => 'text',
                            'value' => 'hello word',
                        ],
                    ],
                    'refIndex' => 0,
                    'score' => 0.3205001277290518,
                ],
            ],
            $result,
        );
    }

    // ignoreLocation when useExtendedSearch is true
    public function testSearchLiteralMatchWithFuzzyMatch2(): void
    {
        $list = [
            [
                'document' =>
                    'Lorem ipsum dolor sit amet, consectetur adipiscing elit, sed do eiusmod tempor incididunt ut ' .
                    'labore et dolore magna aliqua. Ut enim ad minim veniam, quis nostrud exercitation ullamco ' .
                    'laboris nisi ut aliquip ex ea commodo consequat. Duis aute irure dolor in reprehenderit in ' .
                    'voluptate velit esse cillum dolore eu fugiat nulla pariatur. Excepteur sint occaecat cupidatat ' .
                    'non proident, sunt in culpa qui officia deserunt mollit anim id est laborum apple.',
            ],
        ];

        $options = [
            'threshold' => 0.2,
            'useExtendedSearch' => true,
            'ignoreLocation' => true,
            'keys' => ['document'],
        ];

        $fuse = new Fuse($list, $options);

        $result = $fuse->search('Apple');
        $this->assertCount(1, $result);
    }
}
