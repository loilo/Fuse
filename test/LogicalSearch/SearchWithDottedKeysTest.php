<?php

declare(strict_types=1);

namespace Fuse\Test;

use Fuse\Fuse;
use PHPUnit\Framework\TestCase;

class SearchWithDottedKeysTest extends TestCase
{
    private static Fuse $fuse;

    public static function setUpBeforeClass(): void
    {
        $options = [
            'useExtendedSearch' => true,
            'includeScore' => true,
            'keys' => ['title', ['author', 'first.name'], ['author', 'last.name'], 'author.age'],
        ];

        $list = [
            [
                'title' => 'Old Man\'s War',
                'author' => [
                    'first.name' => 'John',
                    'last.name' => 'Scalzi',
                    'age' => '61',
                ],
            ],
        ];

        static::$fuse = new Fuse($list, $options);
    }

    public function testSearchDeepNestedAndOr(): void
    {
        $result = static::$fuse->search([
            '$and' => [
                [
                    '$path' => ['author', 'first.name'],
                    '$val' => 'jon',
                ],
                [
                    '$path' => ['author', 'last.name'],
                    '$val' => 'scazi',
                ],
            ],
        ]);

        $this->assertCount(1, $result);
    }
}
