<?php

declare(strict_types=1);

namespace Fuse\Test;

use PHPUnit\Framework\TestCase;
use Fuse\Fuse;

class IncludeIdAndScoreTest extends TestCase
{
    private Fuse $fuse;

    public function setUp(): void
    {
        $this->fuse = new Fuse(
            [
                [
                    'ISBN' => '0765348276',
                    'title' => 'Old Man\'s War',
                    'author' => 'John Scalzi',
                ],
                [
                    'ISBN' => '0312696957',
                    'title' => 'The Lock Artist',
                    'author' => 'Steve Hamilton',
                ],
            ],
            [
                'keys' => ['title', 'author'],
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
        $this->assertSame('0312696957', $result[0]['item']['ISBN']);

        // and has a score that is not zero
        $this->assertNotEquals(0, $result[0]['score']);
    }
}
