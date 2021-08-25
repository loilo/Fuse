<?php

declare(strict_types=1);

namespace Fuse\Test;

use PHPUnit\Framework\TestCase;

use function Fuse\Core\parse;

class ParserTest extends TestCase
{
    private static array $options = [
        'useExtendedSearch' => true,
        'includeMatches' => true,
        'includeScore' => true,
        'keys' => ['title', 'author.firstName', 'author.lastName'],
    ];

    public function testTreeStructure(): void
    {
        $query = [
            '$and' => [
                [
                    'title' => 'old war',
                ],
                [
                    '$or' => [
                        [
                            'title' => '!arts',
                        ],
                        [
                            'title' => '^lock',
                        ],
                    ],
                ],
            ],
        ];

        $root = parse($query, static::$options, ['auto' => false]);

        $this->assertEquals(
            [
                'children' => [
                    [
                        'keyId' => 'title',
                        'pattern' => 'old war',
                    ],
                    [
                        'children' => [
                            [
                                'keyId' => 'title',
                                'pattern' => '!arts',
                            ],
                            [
                                'keyId' => 'title',
                                'pattern' => '^lock',
                            ],
                        ],
                        'operator' => '$or',
                    ],
                ],
                'operator' => '$and',
            ],
            $root,
        );
    }

    public function testImplicitOperations(): void
    {
        $query = [
            '$and' => [
                [
                    'title' => 'old war',
                ],
                [
                    '$or' => [
                        [
                            'title' => '!arts',
                            'tags' => 'kiro',
                        ],
                        [
                            'title' => '^lock',
                        ],
                    ],
                ],
            ],
        ];

        $root = parse($query, static::$options, ['auto' => false]);

        $this->assertEquals(
            [
                'children' => [
                    [
                        'keyId' => 'title',
                        'pattern' => 'old war',
                    ],
                    [
                        'children' => [
                            [
                                'children' => [
                                    [
                                        'keyId' => 'title',
                                        'pattern' => '!arts',
                                    ],
                                    [
                                        'keyId' => 'tags',
                                        'pattern' => 'kiro',
                                    ],
                                ],
                                'operator' => '$and',
                            ],
                            [
                                'keyId' => 'title',
                                'pattern' => '^lock',
                            ],
                        ],
                        'operator' => '$or',
                    ],
                ],
                'operator' => '$and',
            ],
            $root,
        );
    }
}
