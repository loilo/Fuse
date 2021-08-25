<?php

declare(strict_types=1);

namespace Fuse\Test;

use PHPUnit\Framework\TestCase;
use DMS\PHPUnitExtensions\ArraySubset\ArraySubsetAsserts;
use Fuse\Fuse;

class RecurseIntoArraysTest extends TestCase
{
    use ArraySubsetAsserts;

    private Fuse $fuse;

    public function setUp(): void
    {
        $this->fuse = new Fuse(
            [
                [
                    'ISBN' => '0765348276',
                    'title' => 'Old Man\'s War',
                    'author' => 'John Scalzi',
                    'tags' => ['fiction'],
                ],
                [
                    'ISBN' => '0312696957',
                    'title' => 'The Lock Artist',
                    'author' => 'Steve Hamilton',
                    'tags' => ['fiction'],
                ],
                [
                    'ISBN' => '0321784421',
                    'title' => 'HTML5',
                    'author' => 'Remy Sharp',
                    'tags' => ['web development', 'nonfiction'],
                ],
            ],
            [
                'keys' => ['tags'],
                'threshold' => 0,
                'includeMatches' => true,
            ],
        );
    }

    public function testWhenSearchingForTheTagNonfiction(): void
    {
        $result = $this->fuse->search('nonfiction');

        // we get a list containing exactly 1 item
        $this->assertCount(1, $result);

        // whose value is the ISBN of the book
        $this->assertSame('0321784421', $result[0]['item']['ISBN']);

        // with matched tag provided
        $this->assertArraySubset(
            [
                'indices' => [[0, 9]],
                'value' => 'nonfiction',
                'key' => 'tags',
                'refIndex' => 1,
            ],
            $result[0]['matches'][0],
        );
    }
}
