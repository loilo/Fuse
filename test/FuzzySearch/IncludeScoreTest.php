<?php

declare(strict_types=1);

namespace Fuse\Test;

use PHPUnit\Framework\TestCase;
use Fuse\Fuse;

class IncludeScoreTest extends TestCase
{
    private Fuse $fuse;

    public function setUp(): void
    {
        $this->fuse = new Fuse(
            ['Apple', 'Orange', 'Banana'],
            [
                'includeScore' => true,
            ],
        );
    }

    public function testWhenSearchingForTheTermApple(): void
    {
        $result = $this->fuse->search('Apple');

        // we get a list of exactly 1 item
        $this->assertCount(1, $result);

        // whose value is the index 0, representing ["Apple"]
        $this->assertEquals(0, $result[0]['refIndex']);
        $this->assertEquals(0, $result[0]['score']);
    }

    public function testWhenPerformingAFuzzySearchForTheTermRan(): void
    {
        $result = $this->fuse->search('ran');

        // we get a list of containing 2 items
        $this->assertCount(2, $result);

        // whose values represent the indices, and have non-zero scores
        $this->assertSame(1, $result[0]['refIndex']);
        $this->assertNotEquals(0, $result[0]['score']);
        $this->assertSame(2, $result[1]['refIndex']);
        $this->assertNotEquals(0, $result[1]['score']);
    }
}
