<?php

declare(strict_types=1);

namespace Fuse\Test;

use Fuse\Fuse;
use PHPUnit\Framework\TestCase;

class NestedConditionsTest extends TestCase
{
    private static Fuse $fuse1;
    private static Fuse $fuse2;

    public static function setUpBeforeClass(): void
    {
        $options = [
            'includeScore' => true,
            'useExtendedSearch' => true,
            'keys' => ['title', 'author.firstName', 'author.lastName', 'author.age'],
        ];

        $list1 = [
            [
                'title' => 'Old Man\'s War',
                'author' => [
                    'firstName' => 'John',
                    'lastName' => 'Scalzi',
                    'age' => '61',
                ],
            ],
        ];

        $list2 = array_merge($list1, [
            [
                'title' => 'Old Man\'s War',
                'author' => [
                    'firstName' => 'John',
                    'lastName' => 'Scalzi',
                    'age' => '62',
                ],
            ],
        ]);

        static::$fuse1 = new Fuse($list1, $options);
        static::$fuse2 = new Fuse($list2, $options);
    }

    public function testSearchNestedAndOr(): void
    {
        $result = static::$fuse1->search([
            '$and' => [
                [
                    'title' => 'old',
                ],
                [
                    '$or' => [
                        [
                            'author.firstName' => 'j',
                        ],
                        [
                            'author.lastName' => 'Sa',
                        ],
                    ],
                ],
                [
                    '$or' => [
                        [
                            'author.age' => '\'62',
                        ],
                    ],
                ],
            ],
        ]);

        $this->assertCount(0, $result);
    }

    public function testSearchDeepNestedAndOr(): void
    {
        $result = static::$fuse1->search([
            '$and' => [
                [
                    'title' => 'old',
                ],
                [
                    '$or' => [
                        [
                            'author.firstName' => 'jon',
                        ],
                        [
                            'author.lastName' => 'Sazi',
                        ],
                    ],
                ],
                [
                    '$or' => [
                        [
                            'author.age' => '\'62',
                        ],
                        [
                            '$and' => [
                                [
                                    'title' => 'old',
                                ],
                                [
                                    'author.age' => '\'61',
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ]);

        $this->assertCount(1, $result);
        $this->assertArrayHasKey('score', $result[0]);
        $this->assertGreaterThan(0, $result[0]['score']);
    }

    public function testSearchDeepNestedAndOr2(): void
    {
        $result = static::$fuse2->search([
            '$and' => [
                [
                    'title' => 'old',
                ],
                [
                    '$and' => [
                        [
                            'author.firstName' => 'jon',
                        ],
                        [
                            'author.lastName' => 'Sazi',
                        ],
                    ],
                ],
                [
                    '$or' => [
                        [
                            'author.age' => '\'62',
                        ],
                        [
                            '$and' => [
                                [
                                    'title' => 'old',
                                ],
                                [
                                    'author.age' => '\'62',
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ]);

        $this->assertCount(1, $result);
        $this->assertArrayHasKey('score', $result[0]);
        $this->assertGreaterThan(0, $result[0]['score']);
    }
}
