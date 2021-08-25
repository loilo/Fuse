<?php

declare(strict_types=1);

namespace Fuse\Test;

use PHPUnit\Framework\TestCase;
use Fuse\Fuse;

class NumericIdsTest extends TestCase
{
    private Fuse $fuse;

    public function setUp(): void
    {
        $this->fuse = new Fuse(
            [
                [
                    'ISBN' => 1111,
                    'title' => 'Old Man\'s War',
                    'author' => 'John Scalzi',
                ],
                [
                    'ISBN' => 2222,
                    'title' => 'The Lock Artist',
                    'author' => 'Steve Hamilton',
                ],
            ],
            [
                'keys' => ['title', 'author'],
                'id' => 'ISBN',
                'includeScore' => true,
            ],
        );
    }

    public function testWhenSearchingForTheTermStve(): void
    {
        $result = $this->fuse->search('Stve');

        // we get a list containing exactly 1 item
        $this->assertCount(1, $result);

        // whose value is the ISBN of the book
        $this->assertSame(2222, $result[0]['item']['ISBN']);

        // and has a score that is not zero
        $this->assertNotEquals(0, $result[0]['score']);
    }
}
