<?php

declare(strict_types=1);

namespace Fuse\Test;

use PHPUnit\Framework\TestCase;
use Fuse\Fuse;

class RecurseIntoObjectsInArraysWithNullObjectInArrayTest extends TestCase
{
    private Fuse $fuse;

    public function setUp(): void
    {
        $this->fuse = new Fuse(
            [
                [
                    'ISBN' => '0765348276',
                    'title' => 'Old Man\'s War',
                    'author' => [
                        'name' => 'John Scalzi',
                        'tags' => [
                            [
                                'value' => 'American',
                            ],
                            null,
                        ],
                    ],
                ],
                [
                    'ISBN' => '0312696957',
                    'title' => 'The Lock Artist',
                    'author' => [
                        'name' => 'Steve Hamilton',
                        'tags' => [
                            [
                                'value' => 'American',
                            ],
                        ],
                    ],
                ],
                [
                    'ISBN' => '0321784421',
                    'title' => 'HTML5',
                    'author' => [
                        'name' => 'Remy Sharp',
                        'tags' => [
                            [
                                'value' => 'British',
                            ],
                            null,
                        ],
                    ],
                ],
            ],
            [
                'keys' => ['author.tags.value'],
                'threshold' => 0,
            ],
        );
    }

    public function testWhenSearchingForTheAuthorTagBritish(): void
    {
        $result = $this->fuse->search('British');

        // we get a list containing exactly 1 item
        $this->assertCount(1, $result);

        // whose value is the ISBN of the book
        $this->assertSame('0321784421', $result[0]['item']['ISBN']);
    }
}
