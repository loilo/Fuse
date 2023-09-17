<?php

declare(strict_types=1);

namespace Fuse\Test;

use PHPUnit\Framework\TestCase;
use DMS\PHPUnitExtensions\ArraySubset\ArraySubsetAsserts;
use Fuse\Fuse;

class SearchTest extends TestCase
{
    use ArraySubsetAsserts;

    private static array $options = [
        'useExtendedSearch' => true,
        'includeMatches' => true,
        'includeScore' => true,
        'keys' => ['title', 'author.firstName', 'author.lastName'],
    ];
    private static array $books;
    private static Fuse $fuse;

    public static function setUpBeforeClass(): void
    {
        static::$books = require __DIR__ . '/../fixtures/books.php';
        static::$fuse = new Fuse(static::$books, static::$options);
    }

    private function idx(array $results): array
    {
        return array_map(fn(array $result): int => $result['refIndex'], $results);
    }

    public function testSearchImplicitAnd(): void
    {
        $result = static::$fuse->search(['title' => 'old man']);

        $this->assertCount(1, $result);
        $this->assertSame(0, $result[0]['refIndex']);
        $this->assertArraySubset([[0, 2], [4, 6]], $result[0]['matches'][0]['indices']);
    }

    public function testSearchAndWithSingleItem(): void
    {
        $result = static::$fuse->search(['$and' => [['title' => 'old man']]]);

        $this->assertCount(1, $result);
        $this->assertArraySubset([0], $this->idx($result));
        $this->assertArraySubset([[0, 2], [4, 6]], $result[0]['matches'][0]['indices']);
    }

    public function testSearchAndWithMultipleEntries(): void
    {
        $result = static::$fuse->search([
            '$and' => [
                [
                    'author.lastName' => 'Woodhose',
                ],
                [
                    'title' => 'the',
                ],
            ],
        ]);

        $this->assertCount(2, $result);
        $this->assertArraySubset([4, 5], $this->idx($result));
    }

    public function testSearchAndWithMultipleEntriesAndExactMatch(): void
    {
        $result = static::$fuse->search([
            '$and' => [
                [
                    'author.lastName' => 'Woodhose',
                ],
                [
                    'title' => '\'The',
                ],
            ],
        ]);

        $this->assertCount(1, $result);
        $this->assertArraySubset([4], $this->idx($result));
    }

    public function testSearchOrWithMultipleEntries(): void
    {
        $result = static::$fuse->search([
            '$or' => [
                [
                    'title' => 'angls',
                ],
                [
                    'title' => 'incmpetnce',
                ],
            ],
        ]);

        $this->assertCount(3, $result);
        $this->assertArraySubset([14, 7, 0], $this->idx($result));
    }

    public function testSearchOrWithNestedEntries(): void
    {
        $result = static::$fuse->search([
            '$or' => [
                [
                    'title' => 'angls',
                ],
                [
                    '$and' => [
                        [
                            'title' => '!dwarf',
                        ],
                        [
                            'title' => 'bakwrds',
                        ],
                    ],
                ],
            ],
        ]);

        $this->assertCount(2, $result);
        $this->assertArraySubset([7, 0], $this->idx($result));
    }

    public function testSearchWithLogicalOrWithSameQueryAcrossFieldsForWood(): void
    {
        $options = [
            'keys' => ['title', 'author.lastName'],
        ];
        $fuse = new Fuse(static::$books, $options);

        $query = [
            '$or' => [
                [
                    'title' => 'wood',
                ],
                [
                    'author.lastName' => 'wood',
                ],
            ],
        ];
        $result = $fuse->search($query);

        // we get the top three results scored based matches from all their fields
        $this->assertEquals([4, 3, 5], $this->idx(array_slice($result, 0, 3)));
    }
}
